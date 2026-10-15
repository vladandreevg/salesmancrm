<?php
/* ============================ */
/*         SalesMan CRM         */
/* ============================ */
/* (C) 2026 Vladislav Andreev   */
/*       SalesMan Project       */
/*        www.isaler.ru         */
/* ============================ */

namespace Neuro;

/**
 * Разбор документации плагина: markdown → разметка для чтения.
 *
 * Зачем отдельный класс, а не `echo $Parsedown->text($md)`:
 *
 *  - справочник для разработчиков длинный, и по нему нужно ориентироваться —
 *    значит, у заголовков должны быть устойчивые якоря, а из них собирается
 *    оглавление (Parsedown их не ставит);
 *  - примеры кода нужно подписывать языком и давать копировать: в документации
 *    для разработчиков код читают глазами и копируют руками, и «серое полотно»
 *    без подписи здесь дороже лишней разметки;
 *  - широкие таблицы в колонке текста нужно ограничивать по ширине, иначе они
 *    растягивают страницу и ломают читаемость.
 *
 * Класс намеренно не зависит ни от базы, ни от inc/func.php: это чистые функции
 * от строки, поэтому проверяются тестом без БД и веб-контекста
 * (.dsh/tests/neuro_docs_test.php) — как и остальные классы ядра.
 *
 * Class Docs
 *
 * @package Neuro
 */
class Docs {

	/**
	 * Файл справки для разработчиков (в каталоге плагина).
	 */
	public const FILE = 'developers.md';

	/**
	 * Подписи языков в шапке блока кода.
	 *
	 * Ключи — то, что пишут в ``` после открывающих кавычек. Без подписи блок
	 * выглядит одинаково, а РНР-пример и ответ модели путать нельзя.
	 */
	public const LANGUAGES = [
		'php'        => 'PHP',
		'json'       => 'JSON',
		'javascript' => 'JavaScript',
		'js'         => 'JavaScript',
		'bash'       => 'Консоль',
		'shell'      => 'Консоль',
		'sql'        => 'SQL',
		'ini'        => 'Конфигурация',
		'apache'     => 'Apache',
		'nginx'      => 'nginx',
		'html'       => 'HTML',
		'css'        => 'CSS',
		'markdown'   => 'Markdown',
		'md'         => 'Markdown',
		'text'       => 'Текст',
		'txt'        => 'Текст',
	];

	/**
	 * Подстановка значений плагина в текст справки.
	 *
	 * Заменяются только три известных плейсхолдера: в примерах кода справки
	 * встречаются конструкции вида `{{client}}` — это переменные промптов, и
	 * общий поиск «всё в двойных фигурных скобках» испортил бы половину примеров.
	 *
	 * @param string $markdown
	 * @param array  $vars - package, version, versiondate
	 *
	 * @return string
	 */
	public static function substitute(string $markdown, array $vars = []): string {

		$map = [];

		foreach (['package', 'version', 'versiondate'] as $key) {

			if (isset($vars[$key])) {
				$map['{{'.$key.'}}'] = (string)$vars[$key];
			}

		}

		if ($map === []) {
			return $markdown;
		}

		return str_replace(array_keys($map), array_values($map), $markdown);

	}

	/**
	 * Заголовок документа (первый h1) — идёт в <title> окна.
	 *
	 * @param string $markdown
	 *
	 * @return string
	 */
	public static function title(string $markdown): string {

		if (preg_match('/^#\s+(.+)$/m', $markdown, $m) === 1) {
			return trim(strip_tags((string)$m[1]));
		}

		return '';

	}

	/**
	 * Разбор справки: markdown → HTML, оглавление, заголовок.
	 *
	 * @param string $markdown
	 * @param array  $vars - значения плейсхолдеров
	 *
	 * @return array - html, toc, title
	 * @throws \RuntimeException - нет Parsedown (в CRM он приходит из composer)
	 */
	public static function render(string $markdown, array $vars = []): array {

		if (!class_exists('Parsedown')) {
			throw new \RuntimeException('Не найден разборщик markdown (Parsedown): в CRM он подключается через composer-автозагрузку из inc/dbconnector.php');
		}

		$source = self::substitute($markdown, $vars);
		$title  = self::title($source);

		$parsedown = new \Parsedown();

		$html = (string)$parsedown->text($source);

		$toc = [];

		$html = self::headings($html, $toc);
		$html = self::tables($html);
		$html = self::codeBlocks($html);

		return [
			'html'  => $html,
			'toc'   => $toc,
			'title' => $title,
		];

	}

	/**
	 * Заголовки: идентификаторы для ссылок, якорь-ссылка и запись в оглавление.
	 *
	 * Идентификатор берётся из явной пометки `{#id}` в конце заголовка, а если её
	 * нет — транслитерируется из названия: ссылка вида `#bystryy-start` читается и
	 * переносится, а `#%D0%B1...` — нет. Явная пометка нужна там, где ссылку
	 * упоминают в тексте: переименование раздела не должно её ломать.
	 *
	 * @param string $html
	 * @param array  $toc
	 *
	 * @return string
	 */
	private static function headings(string $html, array &$toc): string {

		$used = [];

		return (string)preg_replace_callback(
			'/<h([123])>(.*?)<\/h\1>/su',
			static function (array $m) use (&$toc, &$used) {

				$level = (int)$m[1];

				// пометку убираем из выводимого заголовка: в тексте она не нужна
				$inner = trim((string)preg_replace('/\s*\{#[a-z0-9-]+\}\s*$/u', '', (string)$m[2]));
				$text  = trim((string)preg_replace('/\s+/u', ' ', strip_tags($inner)));

				$id = '';

				if (preg_match('/\{#([a-z0-9][a-z0-9-]*)\}$/u', trim(strip_tags((string)$m[2])), $explicit) === 1) {
					$id = (string)$explicit[1];
				}

				if ($id === '') {
					$id = self::slug($text !== '' ? $text : 'section');
				}

				// два раздела с одинаковым названием не должны делить один якорь:
				// вторая ссылка вела бы в первое место
				if (isset($used[$id])) {
					$used[$id]++;
					$id .= '-'.$used[$id];
				}
				else {
					$used[$id] = 1;
				}

				if ($level > 1) {
					$toc[] = ['level' => $level, 'id' => $id, 'title' => $text];
				}

				return '<h'.$level.' id="'.$id.'">'.$inner
					.'<a class="nc-doc-anchor" href="#'.$id.'" title="Ссылка на раздел">#</a>'
					.'</h'.$level.'>';

			},
			$html
		);

	}

	/**
	 * Таблицы — в прокручиваемую обёртку.
	 *
	 * Таблица из справки шире колонки текста: без обёртки она растягивает
	 * страницу целиком, и читать становится нечего.
	 *
	 * @param string $html
	 *
	 * @return string
	 */
	private static function tables(string $html): string {

		return (string)preg_replace(
			'/<table>(.*?)<\/table>/su',
			'<div class="nc-doc-table"><table>$1</table></div>',
			$html
		);

	}

	/**
	 * Блоки кода: подпись языка и кнопка копирования.
	 *
	 * Кнопка в разметке есть всегда, а показывает её CSS — только когда работает
	 * скрипт (`html.nc-js`). Без JS страница остаётся читаемой и не обещает
	 * действий, которых не будет.
	 *
	 * @param string $html
	 *
	 * @return string
	 */
	private static function codeBlocks(string $html): string {

		return (string)preg_replace_callback(
			'/<pre><code( class="language-([a-z0-9+#-]+)")?>(.*?)<\/code><\/pre>/su',
			static function (array $m) {

				$lang  = isset($m[2]) ? strtolower((string)$m[2]) : 'text';
				$class = isset($m[1]) ? (string)$m[1] : ' class="language-text"';

				return '<div class="nc-code" data-lang="'.htmlspecialchars($lang, ENT_QUOTES, 'UTF-8').'">'
					.'<div class="nc-code-head">'
					.'<span class="nc-code-lang">'.htmlspecialchars(self::languageTitle($lang), ENT_QUOTES, 'UTF-8').'</span>'
					.'<button type="button" class="nc-code-copy" data-copy>Копировать</button>'
					.'</div>'
					.'<pre><code'.$class.'>'.$m[3].'</code></pre>'
					.'</div>';

			},
			$html
		);

	}

	/**
	 * Человекочитаемое название языка (чистая функция).
	 *
	 * @param string $code
	 *
	 * @return string
	 */
	public static function languageTitle(string $code): string {

		$code = strtolower(trim($code));

		if (isset(self::LANGUAGES[$code])) {
			return self::LANGUAGES[$code];
		}

		return $code === '' ? 'Код' : strtoupper($code);

	}

	/**
	 * Оглавление: вложенный список ссылок на разделы.
	 *
	 * @param array $toc - записи вида ['level' => 2|3, 'id' => ..., 'title' => ...]
	 *
	 * @return string
	 */
	public static function toc(array $toc): string {

		if ($toc === []) {
			return '';
		}

		$html  = '<ul class="nc-toc">';
		$open  = false;

		foreach ($toc as $item) {

			$level = (int)($item['level'] ?? 2);
			$id    = (string)($item['id'] ?? '');
			$title = (string)($item['title'] ?? '');

			if ($id === '' || $title === '') {
				continue;
			}

			if ($level >= 3) {

				if (!$open) {
					$html .= '<ul class="nc-toc-sub">';
					$open = true;
				}

			}
			elseif ($open) {

				$html .= '</ul>';
				$open = false;

			}

			$html .= '<li><a href="#'.$id.'">'.htmlspecialchars($title, ENT_QUOTES, 'UTF-8').'</a></li>';

		}

		if ($open) {
			$html .= '</ul>';
		}

		return $html.'</ul>';

	}

	/**
	 * Идентификатор раздела из его названия (чистая функция).
	 *
	 * @param string $text
	 *
	 * @return string
	 */
	public static function slug(string $text): string {

		$text = function_exists('mb_strtolower') ? mb_strtolower($text, 'UTF-8') : strtolower($text);

		$text = strtr($text, self::translitMap());

		$text = (string)preg_replace('/[^a-z0-9]+/u', '-', $text);
		$text = trim($text, '-');

		return $text === '' ? 'section' : substr($text, 0, 80);

	}

	/**
	 * Русские буквы в латиницу — только для якорей.
	 *
	 * @return array
	 */
	private static function translitMap(): array {

		return [
			'а' => 'a', 'б' => 'b', 'в' => 'v', 'г' => 'g', 'д' => 'd', 'е' => 'e',
			'ё' => 'e', 'ж' => 'zh', 'з' => 'z', 'и' => 'i', 'й' => 'y', 'к' => 'k',
			'л' => 'l', 'м' => 'm', 'н' => 'n', 'о' => 'o', 'п' => 'p', 'р' => 'r',
			'с' => 's', 'т' => 't', 'у' => 'u', 'ф' => 'f', 'х' => 'h', 'ц' => 'c',
			'ч' => 'ch', 'ш' => 'sh', 'щ' => 'sch', 'ъ' => '', 'ы' => 'y', 'ь' => '',
			'э' => 'e', 'ю' => 'yu', 'я' => 'ya',
			'«' => '', '»' => '', '—' => '-', '–' => '-', '№' => 'n',
		];

	}

}
