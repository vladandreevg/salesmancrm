<?php
/* ============================ */
/*         SalesMan CRM         */
/* ============================ */
/* (C) 2026 Vladislav Andreev   */
/*       SalesMan Project       */
/*        www.isaler.ru         */
/* ============================ */

/**
 * Окно «Инструкция для разработчиков» (открывается кнопкой со страницы плагина).
 *
 * Почему отдельная страница, а не правая колонка со справкой:
 *  - справка для разработчика длинная и с примерами кода: её читают подряд, в
 *    отдельном окне, а не «между делом» рядом с формой настроек;
 *  - в правой колонке основной страницы нет места под оглавление и широкие
 *    примеры, и текст пришлось бы верстать в 30% ширины окна;
 *  - окно можно оставить открытым рядом с редактором — так с ней и работают.
 *
 * Доступ только для администратора: справка описывает внутреннее устройство ядра
 * (таблицы, хук оповещений, работа с ключами), и рядовому сотруднику она не нужна.
 *
 * Файл `developers.md` закрыт .htaccess от прямой отдачи по HTTP: документ
 * разбирается на сервере (Neuro\Docs) и попадает в браузер уже разметкой.
 */

$rootpath = dirname(__DIR__, 2);

error_reporting(E_ERROR);

require_once $rootpath."/inc/config.php";
require_once $rootpath."/inc/dbconnector.php";
require_once $rootpath."/inc/auth_main.php";
require_once $rootpath."/inc/settings.php";
require_once $rootpath."/inc/func.php";
require_once __DIR__."/php/autoload.php";

use Neuro\Docs;
use Neuro\Security;

$about = json_decode(str_replace(["  ", "\t", "\n", "\r"], "", (string)file_get_contents(__DIR__."/plugin.json")), true);

$package = (string)($about['package'] ?? 'neuroCore');
$name    = (string)($about['name'] ?? 'Нейроядро');
$version = (string)($about['version'] ?? '');

/**
 * Страница-сообщение вместо документа (нет прав, нет файла справки, сбой разбора).
 *
 * Отдельная функция, а не «print» по месту: таких случаев три, и каждый должен
 * выглядеть одинаково — понятной причиной, а не пустым окном.
 *
 * @param string $title
 * @param string $message
 *
 * @return void
 */
function nc_docs_error(string $title, string $message): void {

	print '<!DOCTYPE html><html lang="ru"><head><meta charset="utf-8">';
	print '<title>'.htmlspecialchars($title, ENT_QUOTES, 'UTF-8').'</title>';
	print '<link type="text/css" rel="stylesheet" href="assets/css/neurocore.docs.css">';
	print '</head><body>';
	print '<div class="nc-doc-error">';
	print '<div class="nc-doc-error-title">'.htmlspecialchars($title, ENT_QUOTES, 'UTF-8').'</div>';
	print '<div>'.htmlspecialchars($message, ENT_QUOTES, 'UTF-8').'</div>';
	print '</div>';
	print '</body></html>';

}

// Доступ только для администратора
if (!Security::isAdmin($db, $sqlname, (int)$iduser1, (int)$identity, $isadmin, $tipuser)) {

	http_response_code(403);

	nc_docs_error('Доступ запрещён', 'Инструкция для разработчиков открывается только администратором аккаунта.');

	exit();

}

$error = '';
$doc   = ['html' => '', 'toc' => [], 'title' => ''];

$file = __DIR__.'/'.Docs::FILE;

if (!is_readable($file)) {

	$error = 'Файл справки '.Docs::FILE.' не найден в каталоге плагина. Переустановите плагин из дистрибутива.';

}
else {

	try {

		$doc = Docs::render((string)file_get_contents($file), [
			'package'     => $package,
			'version'     => $version,
			'versiondate' => (string)($about['versiondate'] ?? ''),
		]);

	}
	catch (Throwable $e) {

		$error = 'Не удалось разобрать справку: '.$e->getMessage();

	}

}

$title = ($doc['title'] !== '' ? $doc['title'] : 'Инструкция для разработчиков').' — '.$name;

$toc = Docs::toc($doc['toc']);

?>
<!DOCTYPE html>
<html lang="ru">
<head>
	<meta charset="utf-8">
	<title><?php echo htmlspecialchars($title, ENT_QUOTES, 'UTF-8') ?></title>
	<meta content="text/html; charset=utf-8" http-equiv="content-type">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<meta name="robots" content="noindex, nofollow">

	<!-- Тема подсветки синтаксиса: готовая сборка Prism (лицензия MIT) -->
	<link type="text/css" rel="stylesheet" href="assets/css/prism.css?v=<?php echo urlencode($version) ?>">
	<link type="text/css" rel="stylesheet" href="assets/css/neurocore.docs.css?v=<?php echo urlencode($version) ?>">

	<!-- Prism подключается первым: он находит блоки с классом language-* и
	     подсвечивает их на DOMContentLoaded до того, как отработает наш скрипт -->
	<script type="text/javascript" src="assets/js/prism.min.js?v=<?php echo urlencode($version) ?>"></script>
	<script type="text/javascript" src="assets/js/prism.neurocore.js?v=<?php echo urlencode($version) ?>"></script>
	<script type="text/javascript" src="assets/js/neurocore.docs.js?v=<?php echo urlencode($version) ?>"></script>
</head>
<body>

<div class="nc-doc">

	<aside class="nc-doc-side">

		<div class="nc-doc-side-head">Разделы</div>

		<?php if ($toc !== '') { ?>

			<input type="text" class="nc-doc-filter" placeholder="Фильтр по названию…" aria-label="Фильтр по названию раздела">

			<nav class="nc-doc-nav">
				<?php echo $toc ?>
			</nav>

		<?php } ?>

	</aside>

	<main class="nc-doc-main">

		<div class="nc-doc-top">

			<div>
				<div class="nc-doc-top-title">Инструкция для разработчиков</div>
				<div class="nc-doc-top-meta"><?php echo htmlspecialchars($name, ENT_QUOTES, 'UTF-8') ?>, версия <?php echo htmlspecialchars($version, ENT_QUOTES, 'UTF-8') ?></div>
			</div>

			<div class="nc-doc-actions">
				<a href="/plugins/<?php echo htmlspecialchars($package, ENT_QUOTES, 'UTF-8') ?>/" target="_blank" rel="noopener" class="nc-doc-btn">Открыть Нейроядро</a>
				<a href="javascript:void(0)" onclick="window.print()" class="nc-doc-btn">Печать</a>
			</div>

		</div>

		<?php if ($error !== '') { ?>

			<div class="nc-doc-error">
				<div class="nc-doc-error-title">Справка недоступна</div>
				<div><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
			</div>

		<?php } else { ?>

			<article class="nc-doc-body">
				<?php echo $doc['html'] ?>
			</article>

			<div class="nc-doc-foot">
				Документ: plugins/<?php echo htmlspecialchars($package, ENT_QUOTES, 'UTF-8') ?>/<?php echo htmlspecialchars(Docs::FILE, ENT_QUOTES, 'UTF-8') ?>.
				Оформление страницы и примеры кода — часть плагина; вопросы по интерфейсу ядра — к его разработчику.
			</div>

		<?php } ?>

	</main>

</div>

</body>
</html>
