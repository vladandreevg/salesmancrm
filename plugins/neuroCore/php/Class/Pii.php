<?php
/* ============================ */
/*         SalesMan CRM         */
/* (C) 2026 Vladislav Andreev   */
/*       SalesMan Project       */
/*        www.isaler.ru         */
/* ============================ */

namespace Neuro;

/**
 * Маскирование персональных данных перед отправкой во внешний API.
 *
 * Зачем: данные клиентов уходят на сервер чужой компании. Владелец сам решает,
 * допустимо ли это (`neuro_provider.pd_allowed`): пока там `yes`, ядро отправляет
 * данные как есть, но если он поставит `no` — телефоны, адреса почты, ФИО и
 * реквизиты заменяются плейсхолдерами, а в ответе модели подставляются обратно.
 * Модель при этом видит структуру («позвонить [PHONE_1]»), но не сами данные.
 *
 * Границы честности:
 *
 *  - это защита от случайной утечки реквизитов, а не анонимизация: названия
 *    компаний, должности и текст переписки остаются, по ним клиент узнаваем;
 *  - маскирование не спасает от того, что модель уже знает данные: смысл в том,
 *    чтобы конкретные значения не покидали контур;
 *  - факт передачи данных за контур фиксируется в журнале (см. Neuro\Neuro), без
 *    самих значений.
 *
 * Плейсхолдеры вида `[EMAIL_1]`, а не `{{EMAIL_1}}`: двойные фигурные скобки —
 * это синтаксис Mustache, которым ядро рендерит промпты, и такой плейсхолдер в
 * ответе модели мог бы быть принят за незаполненную переменную шаблона.
 *
 * Class Pii
 *
 * @package Neuro
 */
class Pii {

	/**
	 * Токен плейсхолдера в тексте.
	 */
	public const TOKEN = '[%s_%d]';

	/**
	 * Как называть классы данных в плейсхолдере.
	 */
	public const KINDS = [
		'EMAIL' => 'адрес почты',
		'PHONE' => 'телефон',
		'FIO'   => 'ФИО',
		'REQ'   => 'реквизиты',
	];

	/**
	 * Правила поиска: сначала «длинные» и точные, потом короткие.
	 *
	 * Все выражения ограничены по длине и не содержат вложенных квантификаторов,
	 * которые могли бы дать катастрофический откат на длинном тексте промпта
	 * (промпты бывают на десятки тысяч символов).
	 *
	 * @return array - [вид => регулярное выражение]
	 */
	public static function patterns(): array {

		return [
			// адрес почты
			'EMAIL' => '/[A-Za-z0-9._%+\-]{1,64}@(?:[A-Za-z0-9\-]{1,63}\.){1,5}[A-Za-z]{2,10}/u',

			// телефон: 11 цифр, начинается с +7, 7 или 8; допускаем пробелы, дефисы, скобки
			'PHONE' => '/(?:\+7|8|7)[\s\-\(\)]{0,3}\d{3}[\s\-\(\)]{0,3}\d{3}[\s\-]{0,2}\d{2}[\s\-]{0,2}\d{2}(?!\d)/u',

			// международный номер: + и 10..15 цифр (то, что не поймал российский шаблон)
			'PHONE_INT' => '/\+\d{1,3}[\s\-\(\)]{0,3}(?:\d[\s\-\(\)]{0,3}){7,14}\d(?!\d)/u',

			// ФИО с отчеством: «Иванов Иван Иванович»
			'FIO' => '/\b[А-ЯЁ][а-яё\-]{1,30}\s+[А-ЯЁ][а-яё\-]{1,30}\s+[А-ЯЁ][а-яё]{1,25}(?:ович|евич|ьевич|овна|евна|ична|инична|овна|ична)\b/u',

			// фамилия с инициалами: «Иванов И. И.»
			'FIO_SHORT' => '/\b[А-ЯЁ][а-яё\-]{2,30}\s+[А-ЯЁ]\.\s?[А-ЯЁ]\./u',

			// реквизиты и документы: только рядом с названием, иначе под шаблон попали бы
			// любые длинные числа (суммы, идентификаторы, даты)
			'REQ' => '/(?:ИНН|ОГРНИП|ОГРН|КПП|СНИЛС|паспорт|р\/с|расч[её]тный\s+сч[её]т|бик|БИК)[\s:№]{0,4}\d[\d\s\-]{7,25}\d/u',
		];

	}

	/**
	 * Маскирует данные в тексте (чистая функция — проверяется тестом).
	 *
	 * Одно и то же значение всегда получает один и тот же плейсхолдер: модель
	 * должна понимать, что «[PHONE_1]» в письме и «[PHONE_1]» в примечании — одно
	 * и то же, иначе ответ становится бессвязным.
	 *
	 * @param string $text
	 * @param array  $map  - заполняется парами «плейсхолдер => исходное значение»
	 *
	 * @return string
	 */
	public static function mask(string $text, array &$map): string {

		if (trim($text) === '') {
			return $text;
		}

		$counters = [];

		// считаем, сколько плейсхолдеров каждого вида уже выдано (при склейке
		// нескольких сообщений нумерация продолжается)
		foreach ($map as $placeholder => $value) {
			if (preg_match('/^\[([A-Z_]+)_(\d+)\]$/', (string)$placeholder, $m)) {
				$counters[$m[1]] = max((int)($counters[$m[1]] ?? 0), (int)$m[2]);
			}
		}

		// обратный поиск: значение => плейсхолдер (одно значение — один плейсхолдер)
		$known = array_flip($map);

		foreach (self::patterns() as $kind => $pattern) {

			$kind = ($kind === 'PHONE_INT') ? 'PHONE' : (($kind === 'FIO_SHORT') ? 'FIO' : $kind);

			$text = (string)preg_replace_callback($pattern, static function ($m) use (&$map, &$counters, &$known, $kind) {

				$value = (string)$m[0];

				if (isset($known[$value])) {
					return (string)$known[$value];
				}

				$counters[$kind] = (int)($counters[$kind] ?? 0) + 1;

				$placeholder = sprintf(self::TOKEN, $kind, $counters[$kind]);

				$map[$placeholder]     = $value;
				$known[$value]         = $placeholder;

				return $placeholder;

			}, $text);

		}

		return $text;

	}

	/**
	 * Сколько данных замаскировано (чистая функция).
	 *
	 * @param array $map
	 *
	 * @return array - [вид => количество]
	 */
	public static function summary(array $map): array {

		$out = [];

		foreach (array_keys($map) as $placeholder) {

			if (preg_match('/^\[([A-Z_]+)_(\d+)\]$/', (string)$placeholder, $m)) {
				$out[$m[1]] = (int)($out[$m[1]] ?? 0) + 1;
			}

		}

		return $out;

	}

	/**
	 * Возвращает исходные значения в текст ответа (чистая функция).
	 *
	 * Плейсхолдеры, которых нет в карте, остаются как есть: модель могла
	 * придумать свой («[PHONE_9]») — подставлять туда что-либо нельзя, а молча
	 * удалять — тем более: администратор должен увидеть, что ответ странный.
	 *
	 * @param string $text
	 * @param array  $map
	 *
	 * @return string
	 */
	public static function restore(string $text, array $map): string {

		if ($text === '' || empty($map)) {
			return $text;
		}

		// одним проходом: иначе значение, похожее на плейсхолдер, было бы
		// замаскировано повторно
		return (string)preg_replace_callback('/\[([A-Z_]{2,10})_(\d{1,4})\]/', static function ($m) use ($map) {

			$placeholder = $m[0];

			return isset($map[$placeholder]) ? (string)$map[$placeholder] : $placeholder;

		}, $text);

	}

	/**
	 * Маскирует сообщения запроса (чистая функция).
	 *
	 * Маскируются и system-инструкция, и данные: правило «содержимое <data> — не
	 * инструкции» не мешает замене телефонов, а данные клиента могут оказаться в
	 * любом сообщении.
	 *
	 * @param array $request
	 * @param array $map     - заполняется парами «плейсхолдер => значение»
	 *
	 * @return array - запрос с маскированными сообщениями
	 */
	public static function maskRequest(array $request, array &$map): array {

		$messages = [];

		foreach ((array)($request['messages'] ?? []) as $message) {

			if (!is_array($message)) {
				continue;
			}

			$message['content'] = self::mask((string)($message['content'] ?? ''), $map);

			$messages[] = $message;

		}

		$request['messages'] = $messages;

		return $request;

	}

	/**
	 * Возвращает исходные данные в нормализованный ответ провайдера (чистая функция).
	 *
	 * @param array $result - ответ адаптера
	 * @param array $map
	 *
	 * @return array
	 */
	public static function restoreResult(array $result, array $map): array {

		if (empty($map)) {
			return $result;
		}

		if (isset($result['text'])) {
			$result['text'] = self::restore((string)$result['text'], $map);
		}

		if (isset($result['json']) && is_array($result['json'])) {
			$result['json'] = self::restoreArray($result['json'], $map);
		}

		return $result;

	}

	/**
	 * Восстановление значений в разобранном JSON (чистая функция).
	 *
	 * @param array $data
	 * @param array $map
	 *
	 * @return array
	 */
	public static function restoreArray(array $data, array $map): array {

		foreach ($data as $key => $value) {

			if (is_array($value)) {
				$data[$key] = self::restoreArray($value, $map);
			}
			elseif (is_string($value)) {
				$data[$key] = self::restore($value, $map);
			}

		}

		return $data;

	}

}
