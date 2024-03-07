<?php
/* ============================ */
/*         SalesMan CRM         */
/* ============================ */
/* (C) 2018 Vladislav Andreev   */
/*       Salesman Project       */
/*        www.isaler.ru         */
/*          ver. 2019.3         */
/* ============================ */

/**
 * Набор функций, облегчающих труд разработчика :).
 * Содержит функции для манипуляции данными
 *
 * @package     Salesman
 * @author      Vladislav Andreev <v@salesman.pro>
 * @version     v.1.0 (06/09/2019)
 */

use BaconQrCode\Renderer\Image\ImagickImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\PHPMailer;
use Salesman\Speka;
use Shuchkin\SimpleXLS;
use Shuchkin\SimpleXLSX;
use voku\helper\Hooks;

// инициируем хуки
$hooks = Hooks ::getInstance();

loadIncludes();

require_once dirname(__DIR__).'/vendor/strip_tags_smart/strip_tags_smart.php';
require_once dirname(__DIR__)."/inc/func.helpers.php";

/**
 * @package Func
 */

$IconArray  = [
	"txt.png"   => "txt",
	"html.png"  => "html htm shtml htm",
	"pdf.png"   => "pdf",
	"doc.png"   => "doc docx rtf",
	"xls.png"   => "xls xlsx",
	"ppt.png"   => "ppt pptx",
	"image.png" => "jpeg jpe jpg gif png bmp",
	"box.png"   => "zip tar gz tgz z ace rar arj cab bz2 exe com dll bin dat rpm deb",
	"sound.png" => "wav mp1 mp2 mp3 mid",
	"movie.png" => "mpeg mpg mov avi rm",
];
$MIMEtypes  = [
	"application/andrew-inset"                                            => "ez",
	"application/mac-binhex40"                                            => "hqx",
	"application/mac-compactpro"                                          => "cpt",
	"application/msword"                                                  => "doc",
	"application/openxmlformats-officedocument.wordprocessingml.document" => "docx",
	"application/octet-stream"                                            => "bin dms lha lzh exe class so dll",
	"application/oda"                                                     => "oda",
	"application/pdf"                                                     => "pdf",
	"application/postscript"                                              => "ai eps ps",
	"application/smil"                                                    => "smi smil",
	"application/vnd.ms-excel"                                            => "xls xlsx",
	"application/vnd.ms-powerpoint"                                       => "ppt pptx",
	"application/vnd.wap.wbxml"                                           => "wbxml",
	"application/vnd.wap.wmlc"                                            => "wmlc",
	"application/vnd.wap.wmlscriptc"                                      => "wmlsc",
	"application/x-bcpio"                                                 => "bcpio",
	"application/x-cdlink"                                                => "vcd",
	"application/x-chess-pgn"                                             => "pgn",
	"application/x-cpio"                                                  => "cpio",
	"application/x-csh"                                                   => "csh",
	"application/x-director"                                              => "dcr dir dxr",
	"application/x-dvi"                                                   => "dvi",
	"application/x-futuresplash"                                          => "spl",
	"application/x-gtar"                                                  => "gtar",
	"application/x-hdf"                                                   => "hdf",
	"application/x-javascript"                                            => "js",
	"application/x-koan"                                                  => "skp skd skt skm",
	"application/x-latex"                                                 => "latex",
	"application/x-netcdf"                                                => "nc cdf",
	"application/x-sh"                                                    => "sh",
	"application/x-shar"                                                  => "shar",
	"application/x-shockwave-flash"                                       => "swf",
	"application/x-stuffit"                                               => "sit",
	"application/x-sv4cpio"                                               => "sv4cpio",
	"application/x-sv4crc"                                                => "sv4crc",
	"application/x-tar"                                                   => "tar",
	"application/x-tcl"                                                   => "tcl",
	"application/x-tex"                                                   => "tex",
	"application/x-texinfo"                                               => "texinfo texi",
	"application/x-troff"                                                 => "t tr roff",
	"application/x-troff-man"                                             => "man",
	"application/x-troff-me"                                              => "me",
	"application/x-troff-ms"                                              => "ms",
	"application/x-ustar"                                                 => "ustar",
	"application/x-wais-source"                                           => "src",
	"application/zip"                                                     => "zip",
	"audio/basic"                                                         => "au snd",
	"audio/midi"                                                          => "mid midi kar",
	"audio/mpeg"                                                          => "mpga mp2 mp3",
	"audio/x-aiff"                                                        => "aif aiff aifc",
	"audio/x-mpegurl"                                                     => "m3u",
	"audio/x-pn-realaudio"                                                => "ram rm",
	"audio/x-pn-realaudio-plugin"                                         => "rpm",
	"audio/x-realaudio"                                                   => "ra",
	"audio/x-wav"                                                         => "wav",
	"chemical/x-pdb"                                                      => "pdb",
	"chemical/x-xyz"                                                      => "xyz",
	"image/bmp"                                                           => "bmp",
	"image/gif"                                                           => "gif",
	"image/ief"                                                           => "ief",
	"image/jpeg"                                                          => "jpeg jpg jpe",
	"image/png"                                                           => "png",
	"image/tiff"                                                          => "tiff tif",
	"image/vnd.wap.wbmp"                                                  => "wbmp",
	"image/x-cmu-raster"                                                  => "ras",
	"image/x-portable-anymap"                                             => "pnm",
	"image/x-portable-bitmap"                                             => "pbm",
	"image/x-portable-graymap"                                            => "pgm",
	"image/x-portable-pixmap"                                             => "ppm",
	"image/x-rgb"                                                         => "rgb",
	"image/x-xbitmap"                                                     => "xbm",
	"image/x-xpixmap"                                                     => "xpm",
	"image/x-xwindowdump"                                                 => "xwd",
	"model/iges"                                                          => "igs iges",
	"model/mesh"                                                          => "msh mesh silo",
	"model/vrml"                                                          => "wrl vrml",
	"text/css"                                                            => "css",
	"text/html"                                                           => "html htm",
	"text/plain"                                                          => "asc txt",
	"text/richtext"                                                       => "rtx",
	"text/calendar"                                                       => "ics",
	"text/rtf"                                                            => "rtf",
	"text/sgml"                                                           => "sgml sgm",
	"text/tab-separated-values"                                           => "tsv",
	"text/vnd.wap.wml"                                                    => "wml",
	"text/vnd.wap.wmlscript"                                              => "wmls",
	"text/x-setext"                                                       => "etx",
	"text/xml"                                                            => "xml xsl",
	"video/mpeg"                                                          => "mpeg mpg mpe",
	"video/quicktime"                                                     => "qt mov",
	"video/vnd.mpegurl"                                                   => "mxu",
	"video/x-msvideo"                                                     => "avi",
	"video/x-sgi-movie"                                                   => "movie",
	"x-conference/x-cooltalk"                                             => "ice",
];
$IconArray2 = [
	'<i class="icon-doc-text blue"></i>'          => "txt html htm shtml htm",
	'<i class="icon-file-pdf red"></i>'           => "pdf",
	'<i class="icon-file-word blue"></i>'         => "doc docx rtf",
	'<i class="icon-file-excel green"></i>'       => "xls xlsx",
	'<i class="icon-file-powerpoint orange"></i>' => "ppt pptx",
	'<i class="icon-file-image yelw"></i>'        => "jpeg jpe jpg gif png bmp",
	'<i class="icon-file-archive broun"></i>'     => "zip tar gz tgz z ace rar arj cab bz2 exe com dll bin dat rpm deb",
	'<i class="icon-file-audio"></i>'             => "wav mp1 mp2 mp3 mid",
	'<i class="icon-file-video"></i>'             => "mpeg mpg mov avi rm",
];
$IconArray3 = [
	'icon-doc-text blue'          => "txt html htm shtml htm",
	'icon-file-pdf red'           => "pdf",
	'icon-file-word blue'         => "doc docx rtf",
	'icon-file-excel green'       => "xls xlsx",
	'icon-file-powerpoint orange' => "ppt pptx",
	'icon-file-image yelw'        => "jpeg jpe jpg gif png bmp",
	'icon-file-archive broun'     => "zip tar gz tgz z ace rar arj cab bz2 exe com dll bin dat rpm deb",
	'icon-file-audio'             => "wav mp1 mp2 mp3 mid",
	'icon-file-video'             => "mpeg mpg mov avi rm mp4",
];

/**
 * Подключение hook
 */
function loadIncludes(): void {

	if (!isset($GLOBALS['isInstaller'])) {

		$identity = $GLOBALS['identity'];
		$sqlname  = $GLOBALS['sqlname'];
		$db       = $GLOBALS['db'];
		$fpath    = $GLOBALS['fpath'];
		$rootpath = dirname(__DIR__);

		/**
		 * Исключим стандартные модули, которые подключаются через таблицу **modules**
		 */
		$list = [
			'workplan',
			'projects',
			'leads',
			'modcatalog',
			'corpuniver',
			'soiskatel',
			'callcenter'
		];

		global $hooks;

		/**
		 * Подключаем хуки модулей
		 */

		$folders = getDirList($rootpath."/modules/");

		// проходим подключенные модули
		$resultm = $db -> query("SELECT * FROM {$sqlname}modules WHERE identity = '$identity' ORDER by id");
		while ($data = $db -> fetch($resultm)) {

			if ($data['mpath'] == 'modworkplan') {
				$data['mpath'] = 'workplan';
			}

			$list[] = $data['mpath'];
			$p      = $data['mpath'];

			if (( $data['active'] == 'on' ) && file_exists($rootpath."/modules/{$p}/hook_{$p}.php")) {

				require_once $rootpath."/modules/{$p}/hook_{$p}.php";

			}

		}

		/**
		 * проходим остальные папки с модулями - Обсуждения, База знаний и пр.
		 * и подключаем хуки их этих папок
		 */

		foreach ($folders as $folder) {

			if (file_exists($rootpath."/modules/{$folder}/hook_{$folder}.php") && !in_array($folder, $list)) {

				require_once $rootpath."/modules/{$folder}/hook_{$folder}.php";

			}

		}

		/**
		 * Подключаем хуки для плагинов
		 */

		$pluginFile = $rootpath."/cash/".$fpath."plugins.json";
		if (file_exists($pluginFile)) {

			$pluginEnabled = file_get_contents($pluginFile);

		}
		else {

			$xpluginEnabled = $db -> getAll("SELECT name, active FROM {$sqlname}plugins WHERE active = 'on' and identity = '$identity' ORDER by name");
			//print_r($xpluginEnabled);
			//print $db -> lastQuery();
			//$pluginEnabled  = json_encode( $ipluginEnabled );
			//$pluginEnabled  = $ipluginEnabled;

			$pluginEnabled = [];
			foreach ($xpluginEnabled as $item) {
				if ($item['active'] == 'on') {
					$pluginEnabled[] = $item['name'];
				}
			}

			//print_r($pluginEnabled);

		}

		if ($pluginEnabled != '') {

			$pluginEnabledAr = is_array($pluginEnabled) ? $pluginEnabled : json_decode($pluginEnabled, true);

			foreach ($pluginEnabledAr as $plg) {

				if (file_exists($rootpath."/plugins/".$plg."/".strtolower((string)$plg).".php")) {

					require_once $rootpath."/plugins/".$plg."/".strtolower((string)$plg).".php";

				}

			}

		}

		/**
		 * Подключаем хуки
		 */

		$files = getDirFiles("developer/hooks/");

		//print_r($files);

		foreach ($files as $file) {

			if (file_exists($rootpath."/developer/hooks/{$file}") && $file != '') {
				require_once $rootpath."/developer/hooks/{$file}";
			}

		}

	}

}

/**
 * Функция автозагрузки классов в пространстве имен \Salesman и доп.классы без репозитория
 *
 * @param $class
 */
spl_autoload_register(static function ($class) {

	$rootpath = dirname(__DIR__);

	$name = yexplode("\\", (string)$class);

	//print $class."\n";

	if ($name[0] == 'Salesman' && !class_exists($class, false)) {

		unset($name[0]);
		$name1 = array_values($name);
		$class = yimplode("//", $name1);

		require_once $rootpath."/inc/class/".$class.".php";

	}
	// перенесено в класс \Salesman\Sklad
	/*elseif ( $class == 'Sklad' ) {

		require_once $rootpath."/modules/modcatalog/mcfunc.php";

	}*/
	elseif ($class == 'Soiskatel') {

		require_once $rootpath."/modules/soiskatel/Soiskatel.php";

	}
	elseif ($name[0] == 'Imap') {

		require_once $rootpath."/vendor/ImapUtf7/ImapUtf7.php";

	}
	elseif ($name[0] == 'Viber') {

		require_once $rootpath."/vendor/viber-bot-api/Viber.php";

	}
	elseif ($class == 'AmiLib') {

		require_once $rootpath."/vendor/ami/AmiLib.php";

	}
	/*elseif ($class == 'File_FGetCSV') {

		require_once $rootpath."/vendor/FGetCSV/FGetCSV.php";

	}*/
	elseif ($class == 'EasyPeasyICS') {

		require_once $rootpath."/vendor/EasyPeasyICS/EasyPeasyICS.php";

	}
	elseif ($class == 'Spreadsheet_Excel_Reader') {

		require_once $rootpath."/vendor/excel_reader/excel_reader2.php";

	}
	// репозиторий не поддерживает установку чрз composer
	elseif ($class == 'Excel_XML') {

		require_once $rootpath."/vendor/php-excel/php-excel.class.php";

	}
	/*elseif ( $class == 'Ftp' ) {

		require_once $rootpath."/vendor/ftp/Ftp.class.php";

	}*/
	/*
	elseif ($class == 'clsOpenTBS') {

		require_once $rootpath."/vendor/tbs_us/tbs_class.php";
		require_once $rootpath."/vendor/tbs_us/plugins/tbs_plugin_opentbs.php";

	}
	*/
	// заменен на comodojo/zip
	/*elseif (
		in_array($class, [
			'zip_file',
			'tar_file',
			'tar_file',
			'bzip_file'
		])
	) {

		require_once $rootpath."/vendor/archive/archive.php";

	}*/

	// приходится подключать вручную, т.к. автолоад не подгружает его
	require_once $rootpath."/vendor/tinybutstrong/opentbs/tbs_plugin_opentbs.php";

});

/**
 * Получает список активных модулей или указанного модуля с настройками
 *
 * @param string|null $module
 *
 * @return array
 * @category Core
 * @package  Func
 */
function getModules(string $module = NULL): array {

	$identity = $GLOBALS['identity'];
	$sqlname  = $GLOBALS['sqlname'];
	$db       = $GLOBALS['db'];

	$result = [];

	$s = ( isset($module) && $module != '' ) ? "mpath = '$module' AND" : "";

	$set = $db -> query("SELECT * FROM {$sqlname}modules WHERE $s active = 'on' AND identity = '$identity'");
	while ($da = $db -> fetch($set)) {

		$result[$da['mpath']] = [
			"id"      => $da['id'],
			"title"   => $da['title'],
			"icon"    => $da['icon'],
			"content" => json_decode($da['content'], true),
			"date"    => $da['activateDate'],
			"secret"  => $da['secret']
		];

	}

	return $result;

}

/**
 * Сохраняет произвольные настройки, либо возвращает значение
 *
 * @param string|null $name - тип настройки
 * @param string $action - действие ( get | put )
 * @param array $params - параметры для сохранения
 *
 * @return mixed
 * @category Core
 * @package  Func
 */
function customSettings(string $name = NULL, string $action = 'get', array $params = []) {

	$identity = ( $params['identity'] > 0 ) ? $params['identity'] : $GLOBALS['identity'];
	$sqlname  = $GLOBALS['sqlname'];
	$db       = $GLOBALS['db'];
	$iduser1  = $GLOBALS['iduser1'];

	$xresult = $result = [];

	//если указано имя настройки, то выводим её
	if ($name != '') {

		$set = (array)$db -> getRow("SELECT * FROM {$sqlname}customsettings WHERE tip = '$name' and identity = '$identity'");

		//сохранение настроек
		if ( !empty($params) && in_array($action, ['put', 'set']) ) {

			//поля, которые есть в таблице
			$allowed = [
				'datum',
				'tip',
				'params',
				'iduser',
				'identity'
			];

			//очищаем от мусора
			$data           = $db -> filterArray($params, $allowed);
			$data['params'] = is_array($params['params']) ? json_encode_cyr($params['params']) : $params['params'];

			//если запись уже есть, то обновляем
			if (!isset($data['iduser'])) {
				$data['iduser'] = $iduser1;
			}

			if ((int)$set['id'] > 0) {

				unset($data['identity']);
				$db -> query("UPDATE {$sqlname}customsettings SET ?u WHERE id = '$set[id]' and identity = '$identity'", arrayNullClean($data));

			}

			//или добавляем новую
			else {

				$data['tip'] = $name;
				$db -> query("INSERT INTO {$sqlname}customsettings SET ?u", arrayNullClean($data));

			}

			//print $db -> lastQuery();

			$xresult = $data;

		}
		//удаление
		elseif ($action == 'delete') {

			$db -> query("DELETE FROM {$sqlname}customsettings WHERE tip = '$name' and identity = '$identity'");

		}
		//выдача настроек
		else {
			$xresult = $set;
		}

		if(( !empty($xresult['params']) )){

			if( isJson($xresult['params']) ){
				$result = json_decode($xresult['params'], true);
			}
			/*
			elseif(is_array($xresult['params'])){
				$result = (array)$xresult['params'];
			}
			*/
			else{
				$result = $xresult['params'];
			}

		}

		//$result = ( !empty($xresult['params']) ) ? ( isJson($xresult['params']) ? json_decode($xresult['params'], true) : $xresult['params'] ) : [];

	}
	//если не указано, то выводим все настройки
	else {

		$set = $db -> query("SELECT * FROM {$sqlname}customsettings WHERE identity = '$identity'");
		while ($da = $db -> fetch($set)) {

			$s = json_decode($da['params'], true);

			$result[$da['tip']] = is_array($s) ? $s : $da['params'];

		}

	}

	return $result;

}

/**
 * Полифилы функций PHP
 */
// отсутствует в PHP < 8.0
if (!function_exists('str_contains')) {
	function str_contains(string $haystack, string $needle): bool {
		return empty($needle) || strpos($haystack, $needle) !== false;
	}
}

// исключена в PHP 8.1+
if (!function_exists('strftime')) {
	function strftime(string $format, string $timestamp): string {
		return date(str_replace("%","",$format), $timestamp);
	}
}

// отсутствует в PHP
if (!function_exists('mb_ucfirst') && extension_loaded('mbstring')) {
	/**
	 * Преобразование строки в указанную кодировку с проверкой на наличие функции mb_ucfirst
	 * https://stackoverflow.com/questions/2517947/ucfirst-function-for-multibyte-character-encodings
	 *
	 * @param        $str
	 * @param string $encoding
	 * @return string
	 * @category Core
	 * @package  Func
	 */
	function mb_ucfirst($str, string $encoding = 'UTF-8'): string {
		$str = mb_ereg_replace('^[\ ]+', '', $str);
		return mb_strtoupper(mb_substr($str, 0, 1, $encoding), $encoding).mb_substr($str, 1, mb_strlen($str), $encoding);
	}
}

/**
 * Регистронезависимая подсветка слов в строке
 *
 * @param        $words - искомая строка
 * @param        $source - исходный текст, в котором идет поиск
 * @param string $format - стиль css, который будет применен к подсвечиваемому элементу. default = red
 *
 * @return array|string|string[]|null
 * @package  Func
 * @category Core
 */
function highlighter($words, $source, string $format = 'red'): string {

	// режем запрос на слова (можно использовать explode(' ', $searchq),
	// но при появлении повторяющихся разделителей они не будут группироваться)
	$words = preg_split('/ +/', str_replace("/", " ", $words));
	// экранируем символы "опасные" для регулярных выражений
	$quoted_words = array_map('preg_quote', $words);
	// собираем регулярное выражение
	$pattern = '/'.implode('|', $quoted_words).'/ui';

	// "подсвечиваем" каждое слово
	return preg_replace($pattern, '<span class="'.$format.'">\\0</span>', $source);

}

/**
 * Простая очистка текста от html-говна
 *
 * @param $string
 *
 * @return string
 * @package  Func
 * @category Core
 */
function untag2($string): string {
	return trim(nl2br(htmlspecialchars($string)));
}

/**
 * Глубокая очистка текста от html-говна
 *
 * @param $string
 *
 * @return string
 * @category Core
 * @package  Func
 */
function untag($string): string {

	$string = strip_tags($string);
	$string = trim($string);
	$string = ltrim($string);

	return str_replace([
		"\\r\\n",
		"\"",
		"\\",
		"'",
		"&laquo;",
		"&raquo;",
		"`",
		"«",
		"»",
		"<",
		">"
	], [
		"\r\n",
		"",
		"",
		"",
		"",
		"",
		"",
		"",
		"",
		"",
		""
	], $string);

}

/**
 * Глубокая очистка текста от html-говна
 *
 * @param $string
 *
 * @return string
 * @package  Func
 * @category Core
 */
function untag3($string): string {

	$string = strip_tags($string);
	$string = trim($string);
	$string = ltrim($string);

	$string = str_replace([
		"<",
		">",
		"\"",
		"&laquo;",
		"&raquo;",
		"/",
		"«",
		"»"
	], [
		"",
		"",
		"”",
		"",
		"",
		"-",
		"”",
		"”"
	], $string);

	return strip_tags_smart($string);

}

/**
 * Очистка ячеек полей форм Клиент, Контакт, Сделка
 * Меняет кавычки, поддерживает ссылки
 *
 * @param $string
 *
 * @return string
 * @package  Func
 * @category Core
 */
function fieldClean($string): string {

	$string = strip_tags($string);
	$string = trim($string);
	$string = ltrim($string);

	$string = str_replace([
		"<",
		">",
		"\"",
		"&laquo;",
		"&raquo;",
		"«",
		"»"
	], [
		"[",
		"]",
		"”",
		"",
		"",
		"”",
		"”"
	], $string);

	return strip_tags_smart($string);

}

/**
 * Простая очистка текста от говна, символов <,> и пробелов
 *
 * @param $string
 *
 * @return string
 * @category Core
 * @package  Func
 */
function clean($string): string {

	$string = strip_tags($string);
	$string = trim($string);
	$string = ltrim($string);
	$string = rtrim($string);

	return str_replace([
		"<",
		">"
	], "", $string);

}

/**
 * Преобразование числа в вид 89123.23
 *
 * @param float|null $string
 *
 * @return string
 * @category Core
 * @package  Func
 */
function clean_format(float $string = NULL): string {

	$string = pre_format($string);

	return (string)str_replace([
		" ",
		","
	], [
		"",
		"."
	], $string);

}

/**
 * Простая очистка текста от html-говна
 *
 * @param $string
 *
 * @return string
 * @category Core
 * @package  Func
 */
function clean_all($string): string {

	$string = trim($string);

	return str_replace([
		'"',
		"\n\r",
		"\n",
		"\r",
		"\t",
		"'"
	], [
		'”',
		'',
		'',
		'',
		"&acute;"
	], $string);

}

/**
 * Тотальная чистка от говна
 *
 * @param $string
 * @return array|string|string[]
 * @category Core
 * @package  Func
 */
function cleanTotal($string): string {

	$string = strip_tags($string);
	$string = trim($string);
	$string = ltrim($string);

	return str_replace([
		"；",
		"\"",
		"\n",
		"\r",
		"\t",
		"\n\r",
		"\\n",
		"\\r",
		"\\n\\r",
		"<",
		">",
		"?",
		"„",
		"«",
		"»",
		"?",
		"„",
		"«",
		"»",
		">",
		"<",
		"&amp;",
		"#8220;",
		"“",
		"'",
		"=",
		";",
		"&laquo;",
		"&raquo;",
		"'",
		"&lsquo;",
		"&rsquo;"
	], [
		"'",
		"'",
		" ",
		" ",
		" ",
		" ",
		" ",
		" ",
		" ",
		"'",
		"'",
		"",
		"",
		"",
		"",
		"",
		"",
		"",
		"",
		"",
		"",
		"",
		"",
		"",
		"",
		"",
		". ",
		"",
		"",
		"",
		"",
		""
	], $string);

}

/**
 * Простая очистка текста от двойных пробелов
 *
 * @param string|null $string $string
 *
 * @return string
 * @category Core
 * @package  Func
 */
function stripWhitespaces(string $string = NULL): string {

	$old_string = $string;
	$string     = strip_tags($string);
	$string     = preg_replace('/([^\pL\pN\pP\pS\pZ])|([\xC2\xA0])/u', ' ', $string);
	$string     = str_replace('  ', ' ', $string);
	$string     = trim($string);

	if ($string === $old_string) {
		return $string;
	}

	return stripWhitespaces($string);

}

/**
 * Преобразование чисел в читаемый ввид 1 234 899,56
 *
 * @param float|string|null $string
 *
 * @param int $num - количество цифр после запятой
 * @return string
 * @package  Func
 * @category Core
 */
function num_format($string = NULL, int $num = 2): string {

	if (empty($string)) {
		$string = 0.00;
	}

	$string = str_replace([
		",",
		" "
	], [
		".",
		""
	], $string);

	return number_format($string, $num, ',', ' ');

}

/**
 * То же, что и num_format, но целую часть можно отформатировать css-классом
 *
 * @param             $string
 * @param string|null $class
 * @return string
 * @category Core
 * @package  Func
 */
function xnum_format($string, string $class = NULL): string {

	if (!$string) {
		$string = 0.00;
	}

	$string = str_replace([
		",",
		" "
	], [
		".",
		""
	], $string);

	$string = explode(",", number_format($string, 2, ',', ' '));

	return '<b class="'.$class.'">'.$string[0].'</b>,'.$string[1];

}

/**
 * Функция, обратная num_format
 *
 * @param string|NULL $string
 *
 * @return float
 * @category Core
 * @package  Func
 */
function pre_format(string $string = NULL): float {

	if (!is_null($string)) {

		return (float)str_replace([
			",",
			" "
		], [
			".",
			""
		], trim(untag($string)));

	}

	return 0.00;

}

/**
 * Склонение "год", "день"
 * year (default) - год, day - день
 *
 * @param string|null $str
 * @param string $tip : day, month, year
 *
 * @return string
 * @category Core
 * @package  Func
 */
function getMorph(string $str = NULL, string $tip = 'year'): string {

	if ($tip == 'year') {

		$m = $str[strlen($str) - 1];
		$l = substr($str, -2, 2);

		$str = ( ( ( $m == 1 ) && ( $l != 11 ) ) ? 'год' : ( ( ( ( $m == 2 ) && ( $l != 12 ) ) || ( ( $m == 3 ) && ( $l != 13 ) ) || ( ( $m == 4 ) && ( $l != 14 ) ) ) ? 'года' : 'лет' ) );

	}
	elseif ($tip == 'month') {

		$m = $str[strlen($str) - 1];
		$l = substr($str, -2, 2);

		$str = ( ( ( $m == 1 ) && ( $l != 11 ) ) ? 'месяц' : ( ( ( ( $m == 2 ) && ( $l != 12 ) ) || ( ( $m == 3 ) && ( $l != 13 ) ) || ( ( $m == 4 ) && ( $l != 14 ) ) ) ? 'месяца' : 'месяцев' ) );

	}
	elseif ($tip == 'day') {

		$m = $str[strlen($str) - 1];
		$l = substr($str, -2, 2);

		$str = ( ( ( $m == 1 ) && ( $l != 11 ) ) ? 'день' : ( ( ( ( $m == 2 ) && ( $l != 12 ) ) || ( ( $m == 3 ) && ( $l != 13 ) ) || ( ( $m == 4 ) && ( $l != 14 ) ) ) ? 'дня' : 'дней' ) );

	}

	return $str;

}

/**
 * Склонение любого слова
 *
 * @param            $str
 * @param array|null $morf : массив вариантов склонений
 *                         - один 'голос',
 *                         - два 'голоса',
 *                         - семь 'голосов'
 *
 * @return mixed
 * @category Core
 * @package  Func
 */
function getMorph2($str, array $morf = NULL) {

	//(((substr($likes,-1,1)==1)&&(substr($likes,-2,2)!=11))?'голос':(((substr($likes,-1,1)==2)&&($l!=12)||(substr($likes,-1,1)==3)&&(substr($likes,-2,2)!=13)||(substr($likes,-1,1)==4)&&(substr($likes,-2,2)!=14))?'голоса':'голосов'))

	//$morf = array('голос','голоса','голосов');

	$m = substr($str, -1, 1);
	$l = substr($str, -2, 2);

	return ( ( ( $m == 1 ) && ( $l != 11 ) ) ? $morf[0] : ( ( ( ( $m == 2 ) && ( $l != 12 ) ) || ( ( $m == 3 ) && ( $l != 13 ) ) || ( ( $m == 4 ) && ( $l != 14 ) ) ) ? $morf[1] : $morf[2] ) );

}

/**
 * Преобразование даты для Excel
 *
 * @param $datum
 *
 * @return int|string
 * @throws \Exception
 * @package  Func
 * @category Core
 */
function excel_date($datum) {

	$day = '';

	if ($datum != '0000-00-00' && $datum != '') {

		$dstart = $datum;
		$dend   = '1970-01-01';

		$day = (int)( ( date_to_unix($dstart) - date_to_unix($dend) ) / 86400 - 1 ) + 25570;

	}

	return $day;

}

/**
 * Преобразует числовое значение месяца в краткую русскую форму "Янв", "Фев"..
 *
 * @param $mounth
 *
 * @return mixed
 * @category Core
 * @package  Func
 */
function ru_month($mounth) {

	global $language;

	if (empty($language)) {
		$language = 'ru-RU';
	}

	$rootpath = dirname(__DIR__);

	require_once $rootpath."/inc/language/{$language}.php";

	$lang = $GLOBALS['lang'];

	$months = $lang['face']['MounthNameShort'];

	return ( $months[$mounth - 1] );

}

/**
 * Преобразует числовое значение месяца в полную русскую форму "Января", "Февраля"..
 *
 * @param $month
 *
 * @return mixed
 * @category Core
 * @package  Func
 */
function ru_mon2($month) {

	global $language;

	if (empty($language)) {
		$language = 'ru-RU';
	}

	$rootpath = dirname(__DIR__);

	require_once $rootpath."/inc/language/{$language}.php";

	$lang = $GLOBALS['lang'];

	$months = $lang['face']['MounthNameRod'];

	return ( $months[$month - 1] );

}

/**
 * Преобразует числовое значение месяца в полную русскую форму "Январь", "Февраль"..
 *
 * @param $month
 *
 * @return mixed
 * @category Core
 * @package  Func
 */
function ru_mon($month) {

	global $language;

	if (empty($language)) {
		$language = 'ru-RU';
	}

	$rootpath = dirname(__DIR__);

	require_once $rootpath."/inc/language/{$language}.php";

	$lang = $GLOBALS['lang'];

	$months = $lang['face']['MounthName'];

	return $months[$month - 1];

}

/**
 * Преобразует числовое значение месяца в краткую русскую форму "Янв", "Фев".. аналог ru_month
 *
 * @param $month
 *
 * @return mixed
 * @category Core
 * @package  Func
 */
function smonth($month) {

	global $language;

	if (empty($language)) {
		$language = 'ru-RU';
	}

	$rootpath = dirname(__DIR__);

	require_once $rootpath."/inc/language/{$language}.php";

	$lang = $GLOBALS['lang'];

	$months = $lang['face']['MounthNameShort'];

	return $months[$month - 1];

}

/**
 * Возвращает возраст по дате рождения
 *
 * @param string|null $birthday : 1976-02-29
 *
 * @return string
 * @category Core
 * @package  Func
 */
function calculate_age(string $birthday = NULL): string {

	$birthday_timestamp = strtotime($birthday);
	$age                = date('Y') - date('Y', $birthday_timestamp);

	if (date('md', $birthday_timestamp) > date('md')) {
		$age--;
	}

	return $age;

}

/**
 * Преобразует дату в UNIX формат с учетом смещения времени пользователя
 *
 * @param null $date_orig : 1976-02-29
 *
 * @return int
 * @throws \Exception
 * @package  Func
 * @category Core
 */
function date_to_unix($date_orig = NULL): int {

	$tm       = (int)$GLOBALS['tzone'];
	$date_new = '';

	if (!empty($date_orig)) {

		/*$date_new = explode( "-", $date_orig );
		$date_new = mktime( (int)date( 'H' ), (int)date( 'i' ), (int)date( 's' ), (int)$date_new[1], (int)$date_new[2], (int)$date_new[0] ) + $tm * 3600;*/

		$tz = ( $GLOBALS['tzone'] ) ? new DateTimeZone($GLOBALS['tzone']) : NULL;
		$a  = new DateTime($date_orig, $tz);

		if ($tm != 0) {
			$a -> modify($tm." hour");
		}

		$date_new = $a -> getTimestamp();

	}

	return (int)$date_new;

}

/**
 * Преобразует дату в UNIX формат без учета смещения времени пользователя
 *
 * @param $date_orig : 1976-02-29
 *
 * @return false|int
 * @throws \Exception
 * @package  Func
 * @category Core
 */
function date2unix($date_orig) {

	$dnew = false;

	if ($date_orig != '') {

		$a    = new DateTime($date_orig);
		$dnew = $a -> getTimestamp();

	}

	return $dnew;

}

/**
 * Смещение текущей зоны от GMT в часах
 *
 * @param $date
 *
 * @return float|false|int
 * @throws \Exception
 * @package  Func
 * @category Core
 */
function gmtOffset($date): int {

	if ($date != '') {

		$a = new DateTime($date);

		return $a -> getOffset() / 3600;

	}

	return false;

}

/**
 * Преобразует дату в формате UNIX в дату в формате %Y-%m-%d
 *
 * @param $date_orig
 *
 * @return string
 * @category Core
 * @package  Func
 */
function unix_to_date($date_orig): string {

	if ($date_orig != NULL && $date_orig != '0000-00-00') {
		return strftime('%Y-%m-%d', $date_orig);
	}

	return '';

}

/**
 * Преобразует дату в формате UNIX в дату в формате Y-m-d H:i:s
 *
 * @param $date_orig
 *
 * @return string
 * @category Core
 * @package  Func
 */
function unix_to_datetime($date_orig): string {

	if ($date_orig != NULL && $date_orig != '0000-00-00') {
		return date('Y-m-d H:i:s', $date_orig);
	}

	return '';

}

/**
 * Преобразует дату в формате dd-mm-yyyy
 *
 * @param $date_orig
 *
 * @return string
 * @throws \Exception
 * @package  Func
 * @category Core
 */
function format_date($date_orig): string {

	if ($date_orig != NULL && $date_orig != '0000-00-00') {

		return modifyDatetime($date_orig, ['format' => "d-m-Y"]);

	}

	return '';

}

/**
 * Преобразует дату в формате dd-mm
 *
 * @param $date_orig
 *
 * @return string
 * @throws \Exception
 * @package  Func
 * @category Core
 */
function format_date_shot($date_orig): string {

	$date_new = '';

	if ($date_orig != NULL && $date_orig != '0000-00-00') {

		return modifyDatetime($date_orig, ['format' => "d-m"]);

	}

	return $date_new;
}

/**
 * Преобразует дату в формате dd.mm.yyyy
 *
 * @param $date_orig
 *
 * @return string
 * @throws \Exception
 * @package  Func
 * @category Core
 */
function format_date_rus($date_orig): string {

	if ($date_orig != NULL && $date_orig != '0000-00-00') {

		$date_new = explode("-", $date_orig);
		if ($date_new[2] != '00') {

			return $date_new[2].".".$date_new[1].".".$date_new[0];

		}

	}

	return '';
}

/**
 * Преобразует дату в формате 23 Января 2017
 *
 * @param $date_orig
 *
 * @return string
 * @category Core
 * @package  Func
 */
function format_date_rus_name($date_orig): string {

	$date_new = '';

	if ($date_orig != NULL && $date_orig != '0000-00-00') {
		$date_new = explode("-", $date_orig);
		$date_new = $date_new[2]." ".ru_mon2($date_new[1])." ".$date_new[0];
	}

	return $date_new;
}

/**
 * Преобразует дату для печати счетов в формате 17 Октября 2016 г.
 *
 * @param $date_orig
 *
 * @return string
 * @category Core
 * @package  Func
 */
function format_date_rus_name_akt($date_orig): string {

	$date_new = '';

	if ($date_orig != NULL && $date_orig != '0000-00-00') {
		$date_new = explode("-", $date_orig);
		$date_new = "&laquo;".$date_new[2]."&raquo; ".ru_mon2($date_new[1])." ".$date_new[0]." г.";
	}

	return $date_new;
}

/**
 * Преобразует дату меняя местами день и год
 *
 * @param $date_orig
 *
 * @return string
 * @category Core
 * @package  Func
 */
function format_date_import($date_orig): string {

	$date_new = '';

	if ($date_orig != NULL && $date_orig != '0000-00-00') {
		$date_new = explode(".", $date_orig);
		$date_new = $date_new[2]."-".$date_new[1]."-".$date_new[0];
	}

	return $date_new;
}

/**
 * Преобразует дату + время в формат 25/02/17 13:00
 *
 * @param $date_orig
 *
 * @return string
 * @category Core
 * @package  Func
 */
function format_datetime($date_orig): string {

	$date_new = '';

	if ($date_orig != NULL && $date_orig != '0000-00-00') {
		$date_orig = explode(" ", $date_orig);
		$datum     = explode("-", $date_orig[0]);
		$time      = explode(":", $date_orig[1]);
		$date_new  = $datum[0]."/".$datum[1]."/".substr($datum[2], 2)." ".$time[0].":".$time[1];
	}

	return $date_new;
}

/**
 * Преобразует дату + время в формат 23.02.2017 18:20:00
 *
 * @param $date_orig
 *
 * @return string
 * @category Core
 * @package  Func
 */
function datetimeru2datetime($date_orig): string {

	$date_new = '';

	if ($date_orig != NULL && $date_orig != '0000-00-00') {
		$date_orig = explode(" ", $date_orig);
		$datum     = explode("-", $date_orig[0]);
		$date_new  = $datum[2].".".$datum[1].".".$datum[0]." ".$date_orig[1];
	}

	return $date_new;
}

/**
 * Преобразует дату + время в формат dd.mm.yyyy
 *
 * @param $date_orig
 *
 * @return string
 * @throws \Exception
 * @package  Func
 * @category Core
 */
function get_date($date_orig): string {

	//$date_new = '';

	if ($date_orig != NULL && $date_orig != '0000-00-00') {

		return modifyDatetime($date_orig, ['format' => "d.m.Y"]);

	}

	return '';

}

/**
 * Выделяет дату из строки Дата + Время
 *
 * @param $date_orig
 *
 * @return string
 * @throws \Exception
 * @package  Func
 * @category Core
 */
function datetime2date($date_orig): string {

	$date_new = '';

	if ($date_orig != NULL && $date_orig != '0000-00-00') {

		return modifyDatetime($date_orig, ['format' => "Y-m-d"]);

	}

	return $date_new;
}

/**
 * Возвращает текущую дату без учета смещения времени
 *
 * @return string
 * @category Core
 * @package  Func
 */
function current_date(): string {

	return date('Y')."-".date('m')."-".date('d');

}

/**
 * Получение текущего времени с учетом указанной Timezone с дополнительными манипуляциями
 *
 * Example:
 * ```php
 *  print modifyDatetime( $date, [
 *     "hours"    => "+2",
 *     "minutes"  => "-20",
 *     //"modify"   => "+2 hour +10 minutes",
 *     "format"   => "d.m.y, H:i",
 *     "timezone" => "Asia/Tokyo"
 *  ] );
 * ```
 *
 * @param string|null $date
 * @param array|null $params
 *                      - timezone ( Asia/Yekaterinburg ) - временная зона
 *                      - format ( Y-m-d H:i:s ) - формат возвращаемого времени
 *                      - hours ( +2 ) - смещение в часах
 *                      - minutes ( +5 ) - смещение в минутах
 *                      - modify ( +2 hour +10 minutes ) - описание смещения
 * @return string
 *
 * @throws \Exception
 * @category Core
 * @package  Func
 */
function modifyDatetime(string $date = NULL, array $params = NULL): string {

	if ($date == '' && !is_null($date)) {
		$date = current_datumtime();
	}

	if (!isset($params['format'])) {

		$params['format'] = 'Y-m-d H:i:s';

	}

	$tz2 = $tz = new DateTimeZone($GLOBALS['tmzone']);

	if ($params['timezone'] != '') {
		$tz2 = new DateTimeZone($params['timezone']);
	}

	$a = new DateTime($date, $tz);
	$a -> setTimezone($tz2);

	// смещаем время на часы
	if (isset($params['hours'])) {

		$a -> modify($params['hours']." hour");

	}

	// смещаем время на минуты
	if (isset($params['minutes'])) {

		$a -> modify($params['minutes']." minutes");

	}

	// свободное смещение
	if (isset($params['modify'])) {

		$a -> modify($params['modify']);

	}

	return $a -> format($params['format']);

}

/**
 * Возвращает день
 *
 * @param $datum
 *
 * @return string
 * @category Core
 * @package  Func
 */
function getDay($datum): string {

	$d = explode("-", $datum);

	return $d[2];

}

/**
 * Возвращает время в формате HH:ss
 *
 * @param $time
 *
 * @return string
 * @package  Func
 * @category Core
 */
function getTime($time = NULL): string {

	if (!empty($time)) {

		//если пришло Дата-время
		$str = yexplode(" ", (string)$time);

		if (!empty($str[1])) {
			$time = $str[1];
		}

		$time = explode(":", $time);
		return $time[0].":".$time[1];

	}

	return '';

}

/**
 * Возвращает год
 *
 * @param $date_orig
 *
 * @return string
 * @category Core
 * @package  Func
 */
function get_year($date_orig): string {

	$date_new = explode("-", $date_orig);

	return $date_new[0];

}

/**
 * Возвращает месяц
 *
 * @param $date_orig
 *
 * @return string
 * @category Core
 * @package  Func
 */
function getMonth($date_orig): string {

	$date_new = explode("-", $date_orig);

	return $date_new[1];

}

/**
 * Возвращает дату в формате 2 Февраля
 *
 * @param $date_orig
 *
 * @return string
 * @category Core
 * @package  Func
 */
function get_dateru($date_orig): string {

	$date_new = '';

	if ($date_orig != NULL && $date_orig != '0000-00-00') {

		$date_orig = explode(" ", $date_orig);
		$datum     = explode("-", $date_orig[0]);

		$date_new = (int)$datum[2]."&nbsp;".ru_mon2($datum[1]);

	}

	return $date_new;
}

/**
 * Возвращает время
 *
 * @param $date_orig
 *
 * @return false|string
 * @category Core
 * @package  Func
 */
function get_time($date_orig) {

	//require_once "config.php";

	$tzone = $GLOBALS['tzone'];

	$date_orig = explode(" ", $date_orig);
	$datum     = explode("-", $date_orig[0]);
	$time      = explode(":", $date_orig[1]);
	$date_zone = mktime((int)$time[0], (int)$time[1], (int)$time[2], (int)$datum[1], (int)$datum[2], (int)$datum[0]) + $tzone * 3600;

	return date("H:i", $date_zone);

}

/**
 * Возвращает отформатированную строку в формате <b>H:i</b>  d.m
 *
 * @param $date_orig
 *
 * @return false|string
 * @category Core
 * @package  Func
 */
function get_sdate($date_orig) {

	$date_new = '';

	if ($date_orig != NULL && $date_orig != '0000-00-00 00:00:00') {

		$tzone = $GLOBALS['tzone'];
		//$tzone = mysql_result($result, 0 , "tzone");
		$date_orig = explode(" ", $date_orig);
		$datum     = explode("-", $date_orig[0]);
		$time      = explode(":", $date_orig[1]);
		$date_zone = mktime((int)$time[0], (int)$time[1], (int)$time[2], (int)$datum[1], (int)$datum[2], (int)$datum[0]) + $tzone * 3600;
		$date_new  = date("<b>H:i</b>  d.m", $date_zone);

	}

	return $date_new;
}

/**
 * Возвращает строку datetime в формате d.m.y,  H:i
 *
 * @param $date_orig
 *
 * @return false|string
 * @category Core
 * @package  Func
 */
function get_sfdate($date_orig) {

	$date_new = '';

	if ($date_orig != NULL && $date_orig != '0000-00-00 00:00:00') {

		//обнуляем смещение, т.к. в бд эта ячейка timestamp
		$tzone = $GLOBALS['tzone'] = 0;

		$date_orig = explode(" ", $date_orig);
		$datum     = explode("-", $date_orig[0]);
		$time      = explode(":", $date_orig[1]);
		$date_zone = mktime((int)$time[0], (int)$time[1], (int)$time[2], (int)$datum[1], (int)$datum[2], (int)$datum[0]) + $tzone * 3600;
		$date_new  = date("d.m.y,  H:i", $date_zone);

	}

	return $date_new;
}

/**
 * Возвращает строку datetime в формате d.m.y
 *
 * @param $date_orig
 *
 * @return false|string
 * @category Core
 * @package  Func
 */
function get_sfdate2($date_orig) {

	$tzone    = $GLOBALS['tzone'];
	$date_new = '';

	if ($date_orig != NULL && $date_orig != '0000-00-00 00:00:00') {

		$date_orig = explode(" ", $date_orig);
		$datum     = explode("-", $date_orig[0]);
		$time      = explode(":", $date_orig[1]);
		$date_zone = mktime((int)$time[0], (int)$time[1], (int)$time[2], (int)$datum[1], (int)$datum[2], (int)$datum[0]) + $tzone * 3600;
		$date_new  = date("d.m.y", $date_zone);

	}

	return $date_new;

}

/**
 * Возвращает строку datetime в формате d.m.Y H:i:s
 *
 * @param $date_orig
 *
 * @return false|string
 * @category Core
 * @package  Func
 */
function get_sfdate3($date_orig) {

	$date_new = '';

	if ($date_orig != NULL && $date_orig != '0000-00-00 00:00:00') {

		$tzone = $GLOBALS['tzone'];
		//$tzone = mysql_result($result, 0 , "tzone");
		$date_orig = explode(" ", $date_orig);
		$datum     = explode("-", $date_orig[0]);
		$time      = explode(":", $date_orig[1]);
		$date_zone = mktime((int)$time[0], (int)$time[1], (int)$time[2], (int)$datum[1], (int)$datum[2], (int)$datum[0]) + $tzone * 3600;
		$date_new  = date("d.m.Y H:i:s", $date_zone);

	}

	return $date_new;
}

/**
 * Возвращает строку datetime в формате YYYY-mm-dd
 *
 * @param $date_orig
 *
 * @return false|string
 * @category Core
 * @package  Func
 */
function get_smdate($date_orig) {

	$tzone    = $GLOBALS['tzone'];
	$date_new = '';

	if ($date_orig != NULL && $date_orig != '0000-00-00 00:00:00') {

		$date_orig = explode(" ", $date_orig);
		$datum     = explode("-", $date_orig[0]);
		$time      = explode(":", $date_orig[1]);
		$date_zone = mktime((int)$time[0], (int)$time[1], (int)$time[2], (int)$datum[1], (int)$datum[2], (int)$datum[0]) + $tzone * 3600;
		$date_new  = date("Y-m-d", $date_zone);

	}

	return $date_new;
}

/**
 * Возвращает строку datetime в формате d-m-Y H:i:s
 *
 * @param $date_orig
 *
 * @return false|string
 * @category Core
 * @package  Func
 */
function get_hist($date_orig) {

	$tzone    = $GLOBALS['tzone'];
	$date_new = '';

	if ($date_orig != NULL && $date_orig != '0000-00-00 00:00:00') {

		$date_orig = explode(" ", $date_orig);
		$datum     = explode("-", $date_orig[0]);
		$time      = explode(":", $date_orig[1]);
		$date_zone = mktime((int)$time[0], (int)$time[1], (int)$time[2], (int)$datum[1], (int)$datum[2], (int)$datum[0]) + $tzone * 3600;
		$date_new  = date("d-m-Y H:i:s", $date_zone);

	}

	return $date_new;

}

/**
 * Возвращает строку datetime в формате Y-m-d H:i:s
 *
 * @param $date_orig
 *
 * @return false|string
 * @category Core
 * @package  Func
 */
function get_unhist($date_orig) {

	$date_orig = explode(" ", $date_orig);
	$datum     = explode("-", $date_orig[0]);
	$time      = explode(":", $date_orig[1]);
	$date_zone = mktime((int)$time[0], (int)$time[1], (int)$time[2], (int)$datum[1], (int)$datum[0], (int)$datum[2]);

	return date("Y-m-d H:i:s", $date_zone);

}

/**
 * Возвращает строку datetime в виде массива
 *
 * @param string|null $date
 *
 * @return array
 * @category Core
 * @package  Func
 */
function getDateTimeArray(string $date = NULL): array {

	$date_orig = explode(" ", (string)$date);
	$datum     = explode("-", $date_orig[0]);
	$time      = explode(":", $date_orig[1]);

	return [
		"Y" => $datum[0],
		"m" => $datum[1],
		"d" => $datum[2],
		"H" => $time[0],
		"i" => $time[1],
		"s" => $time[2]
	];

}

/**
 * Возвращает разницу между временными зонами сервера в php.ini и заданными в системе в часах
 *
 * @param int $identity
 *
 * @return array
 * @throws Exception
 * @package  Func
 * @category Core
 */
function getServerTimeOffset(int $identity = 0): array {

	$identity = ( (int)$identity < 1 ) ? (int)$GLOBALS['identity'] : $identity;
	$sqlname  = $GLOBALS['sqlname'];
	$db       = $GLOBALS['db'];

	$clientTimeZone = $db -> getOne("select timezone from {$sqlname}settings WHERE id = '$identity'");

	$serverTimeZone = ini_get('date.timezone');

	$serverTimeZone = ( $serverTimeZone != '' ) ? $serverTimeZone : $clientTimeZone;

	$tz  = new DateTimeZone($serverTimeZone);
	$dz  = new DateTime();
	$dzz = $tz -> getOffset($dz);

	$serverOffset = $dzz / 3600;

	$tz  = new DateTimeZone($clientTimeZone);
	$dz  = new DateTime();
	$dzz = $tz -> getOffset($dz);

	$clientOffset = $dzz / 3600;

	$offset = $clientOffset - $serverOffset;

	return [
		"offset"         => $offset,
		"serverTimeZone" => $serverTimeZone,
		"serverOffset"   => $serverOffset,
		"clientTimeZone" => $clientTimeZone,
		"clientOffset"   => $clientOffset
	];

}

/**
 * Возвращает время с учетом смещения временных зон php.ini и заданной в системе в формате Y-m-d H:i:s
 *
 * @param     $datetime
 * @param int $offset
 *
 * @return false|string
 * @category Core
 * @package  Func
 */
function DateTimeToServerDate($datetime, int $offset) {

	$s = explode(" ", $datetime);

	$datum = explode("-", $s[0]);
	$time  = explode(":", $s[1]);

	return date("Y-m-d H:i:s", mktime((int)$time[0] - (int)$offset, (int)$time[1], (int)$time[2], (int)$datum[1], (int)$datum[2], (int)$datum[0]));

}

/**
 * Преобразует дату в UTC: Y-m-d H:i:s
 *
 * @param $datetime
 *
 * @return string
 * @throws \Exception
 * @category Core
 * @package  Func
 */
function DateTimeToUTC($datetime): string {

	$given = new DateTime($datetime);
	$given -> setTimezone(new DateTimeZone("UTC"));

	return $given -> format("Y-m-d H:i:s");

}

/**
 * Преобразует дату из UTC в локальное время с учетом смещения
 *
 * @param $string
 *
 * @return false|string
 * @package  Func
 * @category Core
 */
function UTCtoDateTime($string) {

	$datum  = substr($string, 0, 10);
	$time   = substr($string, 11, 8);
	$offset = substr($string, 19, 3);

	$d = getServerTimeOffset();

	$offset = $d['clientOffset'] - $offset;

	//print $datum." ".$time." ".$offset."<br>";

	return DateTimeToServerDate($datum." ".$time, -$offset);

}

/**
 * Возвращает текст в котором все ссылки преобразованые в ссылки a
 *
 * @param string|null $text
 * @return string
 * @category Core
 * @package  Func
 */
function link_it(string $text = NULL): string {

	$text = preg_replace("/(^|[\n ])(\w*?)((ht|f)tp(s)?:\/\/\w+[^ ,\"\n\r\t<]*)/i", "$1$2<a href=\"$3\" target='_blank' >$3</a>", $text);
	$text = preg_replace("/(^|[\n ])(\w*?)((www|ftp)\.[^ ,\"\t\n\r<]*)/i", "$1$2<a href=\"https://$3\" target='_blank'>$3</a>", $text);
	$text = preg_replace("/(^|[\n ])([a-z0-9&\-_.]+?)@([\w\-]+\.([\w\-\.]+)+)/i", "$1<a href=\"mailto:$2@$3\">$2@$3</a>", $text);

	return ( $text );

}

/**
 * Возвращает массив периода времени по названию периода
 * Периоды:
 *          today, yestoday, week, calendarweek, calendarweekprev
 *          calendarweeknext, prevweek, nextweek, month, prevmonth
 *          nextmonth, quart, prevquart, nextquart, year, prevyear, nextyear
 *
 * @param string $type
 *
 * @return array
 * @throws \Exception
 * @package  Func
 * @category Core
 */
function getPeriod(string $type = 'today'): array {

	$curQuartal = static function ($str) {

		$quartal = 1;

		$new = explode("-", $str);
		$mon = $new[1]; //это текущий месяц

		$q1 = [
			1,
			2,
			3
		];
		$q2 = [
			4,
			5,
			6
		];
		$q3 = [
			7,
			8,
			9
		];
		$q4 = [
			10,
			11,
			12
		];

		if (in_array($mon, $q2)) {
			$quartal = 2;
		}
		elseif (in_array($mon, $q3)) {
			$quartal = 3;
		}
		elseif (in_array($mon, $q4)) {
			$quartal = 4;
		}

		return $quartal;

	};
	$getQuartal = static function ($quartal, $year) {

		$q1 = $q2 = '';

		if ($quartal == 1) {
			$q1 = $year.'-01-01';
			$q2 = $year.'-03-31';
		}
		if ($quartal == 2) {
			$q1 = $year.'-04-01';
			$q2 = $year.'-06-30';
		}
		if ($quartal == 3) {
			$q1 = $year.'-07-01';
			$q2 = $year.'-09-30';
		}
		if ($quartal == 4) {
			$q1 = $year.'-10-01';
			$q2 = $year.'-12-31';
		}

		return [
			$q1,
			$q2
		];
	};

	$d1 = $d2 = '';

	switch ($type) {
		case "today":
			$d1 = current_datum();
			$d2 = current_datum();
			break;
		case "yestoday":
			$d1 = strftime('%Y-%m-%d', mktime(1, 0, 0, (int)date('m'), (int)date('d') - 1, (int)date('Y')));
			$d2 = strftime('%Y-%m-%d', mktime(1, 0, 0, (int)date('m'), (int)date('d') - 1, (int)date('Y')));
			break;
		case "week":
			$d1 = strftime('%Y-%m-%d', mktime(1, 0, 0, (int)date('m'), (int)date('d'), (int)date('Y')));
			$d2 = strftime('%Y-%m-%d', mktime(1, 0, 0, (int)date('m'), (int)date('d') + 6, (int)date('Y')));
			break;
		case "calendarweek":

			$today = date('w');

			if ($today == 1) {
				$d1 = current_datum();
				$d2 = current_datum(-6);
			}
			else {

				$first = strtotime("last Monday");//monday

				$d1 = strftime('%Y-%m-%d', $first);
				$d2 = strftime('%Y-%m-%d', $first + 6 * 86400);

			}
			break;
		case "calendarweekprev":
		case "prevcalendarweek":

			$today = date('w');

			if ($today == 1) {

				$first = date_to_unix(current_datum());

			}
			else {

				$first = strtotime("last Monday");//monday

			}

			//дата текущего понедельника
			$prevMon = date_to_unix(strftime('%Y-%m-%d', $first)) - 7 * 86400;

			$d1 = strftime('%Y-%m-%d', $prevMon);
			$d2 = strftime('%Y-%m-%d', $prevMon + 6 * 86400);

			break;
		case "calendarweeknext":

			$today = date('w');

			if ($today == 1) {

				$first = date_to_unix(current_datum());

			}
			else {

				$first = strtotime("last Monday");//monday

			}

			//дата текущего понедельника
			$nextMon = (int)date_to_unix(strftime('%Y-%m-%d', $first)) + 7 * 86400;

			$d1 = strftime('%Y-%m-%d', $nextMon);
			$d2 = strftime('%Y-%m-%d', $nextMon + 6 * 86400);

			break;
		case "prevweek":
		case "weekprev":
			$d1 = strftime('%Y-%m-%d', mktime(1, 0, 0, (int)date('m'), (int)date('d') - 7, (int)date('Y')));
			$d2 = strftime('%Y-%m-%d', mktime(1, 0, 0, (int)date('m'), (int)date('d') - 1, (int)date('Y')));
			break;
		case "nextweek":
			$d1 = strftime('%Y-%m-%d', mktime(1, 0, 0, (int)date('m'), (int)date('d') + 1, (int)date('Y')));
			$d2 = strftime('%Y-%m-%d', mktime(1, 0, 0, (int)date('m'), (int)date('d') + 7, (int)date('Y')));
			break;
		case "month":
			$dsf = (int)date("t", mktime(1, 0, 0, (int)date('m'), 1, (int)date('Y')));
			$d1  = strftime('%Y-%m-%d', mktime(1, 0, 0, (int)date('m'), 1, (int)date('Y')));
			$d2  = strftime('%Y-%m-%d', mktime(1, 0, 0, (int)date('m'), (int)$dsf, (int)date('Y')));
			break;
		case "prevmonth":
		case "monthprev":
			$dsf = (int)date("t", mktime(1, 0, 0, date('m') - 1, 1, date('Y')));
			$d1  = strftime('%Y-%m-%d', mktime(1, 0, 0, date('m') - 1, 1, date('Y')));
			$d2  = strftime('%Y-%m-%d', mktime(1, 0, 0, date('m') - 1, $dsf, date('Y')));
			break;
		case "nextmonth":
			$dsf = (int)date("t", mktime(1, 0, 0, (int)date('m') + 1, 1, (int)date('Y')));
			$d1  = strftime('%Y-%m-%d', mktime(1, 0, 0, (int)date('m') + 1, 1, (int)date('Y')));
			$d2  = strftime('%Y-%m-%d', mktime(1, 0, 0, (int)date('m') + 1, $dsf, (int)date('Y')));
			break;
		case "quart":
		case "quartal":

			$q  = $getQuartal($curQuartal(current_datum()), date('Y'));
			$d1 = $q[0];
			$d2 = $q[1];

			break;
		case "prevquart":
		case "quartprev":
			$q = $curQuartal(current_datum());
			if ($q > 1) {
				$qq = $q - 1;
				$yy = date('Y');
			}
			else {
				$qq = 4;
				$yy = date('Y') - 1;
			}
			$q  = $getQuartal($qq, $yy);
			$d1 = $q[0];
			$d2 = $q[1];
			break;
		case "nextquart":
			$q = $curQuartal(current_datum());
			if ($q < 4) {
				$qq = $q + 1;
				$yy = date('Y');
			}
			else {
				$qq = 1;
				$yy = date('Y') + 1;
			}
			$q  = $getQuartal($qq, $yy);
			$d1 = $q[0];
			$d2 = $q[1];
			break;
		case "year":
			$d1 = date('Y')."-01-01";
			$d2 = date('Y')."-12-31";
			break;
		case "prevyear":
		case "yearprev":
			$y  = date('Y') - 1;
			$d1 = $y."-01-01";
			$d2 = $y."-12-31";
			break;
		case "nextyear":
			$y  = date('Y') + 1;
			$d1 = $y."-01-01";
			$d2 = $y."-12-31";
			break;
	}

	return [
		$d1,
		$d2
	];

}

/**
 * Возвращает массив Дата.Старт - Дата.Финиш для каждой календарной недели месяца
 *
 * @param null $month
 * @param null $year
 *
 * @return array
 * @throws \Exception
 */
function getPeriodByWeekOfMonth($month = NULL, $year = NULL): array {

	$weeks = [];

	if (!isset($month)) {
		$month = date('m');
	}

	if (!isset($year)) {
		$year = date('Y');
	}

	//число недель
	$count = date('w');

	//первый день месяца
	$firstDay = $year."-".$month."-01";

	//начальное значение текущего дня
	$currentUnix = date_to_unix($firstDay);

	for ($week = 1; $week <= $count; $week++) {

		//текущая дата (не всегда месяц начинается в Понедельник)
		$d1 = date('Y-m-d', $currentUnix);
		//дата воскресенья
		$d2 = date('Y-m-d', strtotime("next Sunday", $currentUnix));

		//если дата больше, чем последний день месяца, то устанавливаем как последний день месяца
		if (getDay($d2) > date("t", $firstDay)) {
			$d2 = $year."-".$month."-".date("t", $firstDay);
		}

		$weeks[] = [
			$d1,
			$d2
		];

		//следующий понедельник, для следующей итерации
		$currentUnix = strtotime("next Monday", $currentUnix);

	}

	return $weeks;

}

/**
 * Возвращает иконку для типа активности по её названию
 *
 * @param string|null $tip - тип активности
 * @param string|null $color - цвет иконки
 * @param bool $textonly
 *
 * @return string
 * @category Core
 * @package  Func
 */
function get_ticon(string $tip = NULL, string $color = NULL, bool $textonly = false): string {

	$identity = $GLOBALS['identity'];
	$sqlname  = $GLOBALS['sqlname'];
	$db       = $GLOBALS['db'];

	$icn = '';

	/**
	 * Проверим наличие колонки в таблице. Если нет, то добавим
	 */
	$field = $db -> getRow("SHOW COLUMNS FROM ".$sqlname."activities LIKE 'icon'");
	if ($field['Field'] == '') {

		$db -> query("
			ALTER TABLE ".$sqlname."activities
			ADD COLUMN `icon` VARCHAR(100) NULL DEFAULT NULL COMMENT 'иконка' AFTER `color`
		");

	}
	/**
	 * Или возвращаем иконку
	 */
	else {
		$icn = $db -> getOne("SELECT icon FROM ".$sqlname."activities WHERE title = '$tip' AND identity = '$identity'");
	}

	if ($icn == '') {

		setlocale(LC_ALL, 'ru_RU.CP1251');
		$tipa = "..".texttosmall($tip);

		if (str_contains($tipa, 'звон')) {
			$icn = 'icon-phone-squared';
		}
		elseif (str_contains($tipa, 'фак')) {
			$icn = 'icon-print';
		}
		elseif (str_contains($tipa, 'встреч')) {
			$icn = 'icon-users-1';
		}
		elseif (str_contains($tipa, 'зада')) {
			$icn = 'icon-check';
		}
		elseif (str_contains($tipa, 'предлож')) {
			$icn = 'icon-doc-text';
		}
		elseif (str_contains($tipa, 'событ')) {
			$icn = 'icon-calendar-empty';
		}
		elseif (str_contains($tipa, 'почт')) {
			$icn = 'icon-mail-alt';
		}
		elseif (str_contains($tipa, 'чат')) {
			$icn = 'icon-chat-1';
		}
		elseif (str_contains($tipa, 'сообщен')) {
			$icn = 'icon-chat-1';
		}
		elseif (str_contains($tipa, 'запись')) {
			$icn = 'icon-volume-up';
		}
		elseif (str_contains($tipa, 'лог')) {
			$icn = 'icon-doc-text';
		}
		elseif (str_contains($tipa, 'ваканс')) {
			$icn = 'icon-graduation-cap-1';
		}
		elseif (str_contains($tipa, 'проект')) {
			$icn = 'icon-article-alt';
		}
		else {
			$icn = 'icon-certificate';
		}

	}

	if (!$textonly) {
		return '<i class="'.$icn.'" title="'.$tip.'" '.( $color != '' ? 'style="color:'.$color.'"' : '' ).'></i>';
	}

	return $icn;

}

/**
 * Возвращает иконку типа активности
 *
 * @param string|null $tip
 *
 * @return string: phone, email, address
 * @category Core
 * @package  Func
 */
function get_activtip(string $tip = NULL): string {

	setlocale(LC_ALL, 'ru_RU.CP1251');
	$tipa = texttosmall($tip);

	if (str_contains($tipa, 'звон')) {
		$tip = 'phone';
	}
	elseif (str_contains($tipa, 'фак')) {
		$tip = 'phone';
	}
	elseif (str_contains($tipa, 'отправ')) {
		$tip = 'email';
	}
	elseif (str_contains($tipa, 'почт')) {
		$tip = 'email';
	}
	elseif (str_contains($tipa, 'предлож')) {
		$tip = 'email';
	}
	elseif (str_contains($tipa, 'встреч')) {
		$tip = 'address';
	}
	else {
		$tip = '';
	}

	return $tip;

}

/**
 * Преобразует текст в нижний регистр
 *
 * @param string|null $string $string
 *
 * @return string
 * @category Core
 * @package  Func
 */
function texttosmall(string $string = NULL): string {

	return mb_strtolower($string, mb_detect_encoding($string));

}

/**
 * Возвращает содержимое html текста, который содержит заголовки, стили и пр. в формате html
 * обрезая не нужное
 *
 * @param string|null $text
 *
 * @return string
 * @category Core
 * @package  Func
 */
function getHtmlBody(string $text = NULL): string {

	$html = htmlspecialchars_decode($text);
	$pre  = '';

	$dom = new DOMDocument('1.0', 'UTF-8');
	$dom -> loadHTML("\xEF\xBB\xBF".$html, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);

	$elem = $dom -> getElementsByTagName('body') -> item(0);

	if (!is_null($elem)) {

		foreach ($elem -> childNodes as $child) {
			$pre .= $dom -> saveHTML($child);
		}

	}
	else {

		if (preg_match('|<body.*?>(.*)</body>|si', $text, $arr)) {
			$text = $arr[1];
		}
		$pre = preg_replace("!<style>(.*?)</style>!si", "", $text);

	}

	return str_replace([
		"<p> </p>",
		"<p>&nbsp;</p>",
		"<p>&nbsp; </p>",
		"<p class=\"MsoNormal\"></p>"
	], "", $pre);

}

/**
 * Функция удаляет дочерние элементы $params['element'] с уровня $params['index'] при их вложенности
 *
 * @param            $text
 * @param array|null $params
 *
 * @return string
 * @category Core
 * @package  Func
 */
function removeChild($text, array $params = NULL): string {

	//идея - очистить тэг от мусора
	//$text = preg_replace('<blockquote(*?)>|sei', "<blockquote>", $text);

	$element = $params['element'] ?? 'blockquote';
	$index   = $params['index'] ?? '1';

	$dom = new DOMDocument('1.0', 'UTF-8');
	$dom -> loadHTML("\xEF\xBB\xBF".$text, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
	$elem = $dom -> getElementsByTagName($element) -> item($index);

	if (is_null($elem)) {
		return $text;
	}

	if (is_object($elem)) {

		$elem -> parentNode -> removeChild($elem);

		return html_entity_decode($dom -> saveHTML());

	}

	return $text;

}

/**
 * Преобразует html в текст
 *
 * @param $html
 *
 * @return array|string|string[]|null
 * @category Core
 * @package  Func
 */
function html2text($html) {

	$html = htmlspecialchars_decode($html);

	$dom = new DOMDocument('1.0', 'UTF-8');
	$dom -> loadHTML("\xEF\xBB\xBF".$html, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
	$elem = $dom -> getElementsByTagName('body') -> item(0);

	if (!is_null($elem)) {

		$html = $elem -> textContent;
		$html = trim(preg_replace("/(\r\n|\r|\n|\t){".( 1 ).",}/u", str_repeat("\r\n", 1), $html));

	}
	else {

		$tags = [
			'~<h[123][^>]+>~si',
			'~<h[456][^>]+>~si',
			'~<table[^>]+>~si',
			'~<tr[^>]+>~si',
			'~<li[^>]+>~si',
			'~<br>~si',
			'~<br[^>]+>~si',
			'~<p[^>]+>~si',
			'~<div[^>]+>~si',
		];

		if (preg_match('|<body.*?>(.*)</body>|si', $html, $arr)) {
			$html = $arr[1];
		}

		$html = preg_replace("!<style>(.*?)</style>!si", "", $html);
		$html = preg_replace('|<style.*?>(.*)</style>|si', "", $html);

		$html = preg_replace($tags, "\n", $html);
		$html = preg_replace('~</t([dh])>\s*<t([dh])[^>]+>~si', ' - ', $html);
		$html = preg_replace('~<[^>]+>~s', '', $html);
		$html = preg_replace('~ +~s', ' ', $html);
		$html = preg_replace('~^\s+~m', '', $html);
		$html = preg_replace('~\s+$~m', '', $html);
		$html = preg_replace('~\n+~s', "\n", $html);

	}

	return $html;

}


/**
 * Возвращает массив, содержащий все phone, email, url, ip, найденные в тексте
 *
 * @param $text
 *
 * @return array
 * @category Core
 * @package  Func
 */
function html2data($text): array {

	$rez = $l = [];

	/*находит в тексте email, url и телефон*/
	$text = str_replace("|", ",", html2text(htmlspecialchars_decode($text)));

	//игнорируемые конакты сотрудников
	$ignore = getUsersPhones();

	$ie = (array)$ignore['email'];
	$it = (array)$ignore['phone'];

	//print_r($ignore);
	//print_r($it);

	if (getEmailFromText($text)) {

		$t = getEmailFromText($text);
		foreach ($t as $item) {
			if (!in_array($item, $ie)) {
				$l[] = untag($item);
			}
		}

		$l = array_unique($l);

		$rez['email'][] = implode(", ", $l);

	}
	if (getPhoneFromText($text)) {

		$t = getPhoneFromText($text);
		foreach ($t as $item) {
			if (!in_array($item, $it)) {
				$l[] = untag($item);
			}
		}

		$l = array_unique($l);

		$rez['phone'][] = implode(", ", $l);

	}
	if (getUrlFromText($text)) {

		$rez['site'][] = implode(", ", getUrlFromText($text));

	}
	if (filter_var($text, FILTER_VALIDATE_IP)) {

		//todo: нужен парсер ip-адресов
		$rez['ip'][] = $text;

	}

	$rez['phone'] = implode(", ", (array)$rez['phone']);
	$rez['email'] = implode(", ", (array)$rez['email']);
	$rez['site']  = implode(", ", (array)$rez['site']);
	$rez['ip']    = implode(", ", (array)$rez['ip']);

	$rez['description'] = $text;

	return $rez;

}

/**
 * Получение всех ссылок из HTML
 *
 * @param $html
 *
 * @return array
 * @category Core
 * @package  Func
 */
function linkFromHtml($html): array {

	$urls = [];

	$dom = new DOMDocument();
	@$dom -> loadHTML($html);

	// захватить все на странице
	$xpath = new DOMXPath($dom);
	$hrefs = $xpath -> evaluate("//a");

	for ($i = 0; $i < $hrefs -> length; $i++) {

		$href = $hrefs -> item($i);

		$url   = $href -> getAttribute('href');
		$title = utf8_decode($href -> getAttribute('title'));
		$text  = utf8_decode($href -> textContent);

		if ($title == '' && $text != '') {
			$title = $text;
		}

		if ($title == '' && $text == '') {
			$title = 'Ссылка';
		}

		$urls[] = [
			"url"   => urldecode($url),
			"title" => $title,
			"text"  => $text
		];

	}

	return $urls;

}

/**
 * Возвращает массив ссылок на изображения из html-кода
 *
 * @param $html
 *
 * @return array
 * @category Core
 * @package  Func
 */
function imagesFromHtml($html): array {

	$list   = [];
	$images = [];

	//находим ссылки на изображения в массив $images
	preg_match_all('/<img[^>]+src="?\'?([^"\']+)"?\'?[^>]*>/i', $html, $images, PREG_SET_ORDER);

	// включаем изображения в теле письма как base64
	foreach ($images as $img) {

		$list[] = $img[1];

	}

	return $list;

}

/**
 * Скрывает часть email - заменяет на *
 *
 * @param string|array $email
 *
 * @return array|string
 * @category Core
 * @package  Func
 */
function hideEmail($email) {

	$mail = [];

	//уничтожаем функцию, если она уже объявлена
	unset($hide);

	$hide = static function ($item) {

		$name   = yexplode("@", (string)$item, 0);
		$domain = yexplode("@", (string)$item, 1);

		$len = strlen($name);

		if ($len > 1) {

			$showLen = (int)( $len / 2 );
			$str_arr = str_split($name);

			for ($i = $showLen; $i < $len; $i++) {
				$str_arr[$i] = '*';
			}

			$name = implode('', $str_arr);

		}
		else {
			$name .= "*";
		}

		return $name.'@'.$domain;

	};

	$emails = ( !is_array($email) ) ? yexplode(",", (string)str_replace(";", ",", $email)) : $email;

	foreach ($emails as $item) {
		$mail[] = $hide($item);
	}


	return ( count($mail) > 1 ) ? $mail : $mail[0];

}

/**
 * Скрывает часть телефона - заменяет на *
 *
 * @param string|array $phone
 *
 * @return array|string
 * @category Core
 * @package  Func
 */
function hidePhone($phone) {

	$tels = [];

	//уничтожаем функцию, если она уже объявлена
	unset($hide);

	$hide = static function ($item) {

		$item = eformatPhone($item);

		$len = str_split($item);
		$tel = '';
		$k   = [];
		$f   = 0;

		foreach ($len as $i => $char) {

			if (
				in_array($char, [
					"(",
					")",
					"-",
					"+",
					" "
				])
			) {
				$k[] = $i;
			}
			elseif ($f == 0) {
				$f = $i;
			}

		}

		$f += 3;

		if ($len > 5) {

			foreach ($len as $i => $char) {

				if (
					in_array($char, [
						"(",
						")",
						"-",
						"+",
						" "
					])
				) {
					$tel .= $char;
				}
				elseif (
					$i > $f && $i < strlen($item) - 4 && !in_array($char, [
						"(",
						")",
						"-",
						"+",
						" "
					]) && $i != $k
				) {
					$tel .= "*";
				}
				else {
					$tel .= $char;
				}

			}

		}
		else {
			$tel = $item;
		}

		return $tel;

	};

	$phones = ( !is_array($phone) ) ? yexplode(",", (string)str_replace(";", ",", $phone)) : $phone;

	foreach ($phones as $item) {
		$tels[] = $hide($item);
	}

	return ( count($tels) > 1 ) ? $tels : $tels[0];

}

/**
 * Возвращает массив, содержащий все email, найденные в тексте
 *
 * @param string|null $text
 *
 * @return array
 * @category Core
 * @package  Func
 */
function getEmailFromText(string $text = NULL): array {

	$email = [];

	preg_match_all('/(\S+)@([a-z0-9.]+)/is', str_replace([
		"mailto:",
		"[",
		"]"
	], "", $text), $p);

	foreach ($p[0] as $val) {

		if ($val != '') {
			$email[] = $val;
		}

	}

	return array_unique($email);

}

/**
 * Возвращает массив, содержащий все phone, найденные в тексте
 *
 * @param string|null $text
 *
 * @return array
 * @category Core
 * @package  Func
 */
function getPhoneFromText(string $text = NULL): array {

	$phone = [];

	$pattern = '/(\+?\d+[\s\-\.]?)?((\(\d+\)|\d+)[\s\-\.]?)?((\d[\s\-\.]?){6}\d)/x';
	preg_match_all($pattern, $text, $p);

	foreach ($p[0] as $val) {

		$v = substr(preg_replace("/[^0-9]/", "", $val), 0, 1);
		$s = strlen($v);

		if ($val != '' && $v > 6 && filter_var($val, FILTER_VALIDATE_IP) === false) {
			$phone[] = prepareMobPhone($val);
		}

	}

	return array_unique($phone);

}

/**
 * Возвращает массив, содержащий все url, найденные в тексте
 *
 * @param string|null $text
 *
 * @return array
 * @category Core
 * @package  Func
 */
function getUrlFromText(string $text = NULL): array {

	$url = [];

	$pattern = '/\s(www\.([a-z][a-z0-9_\..-]*[a-z]{2,6})([a-zA-Z0-9\/*-?&%]*))\s/i';
	preg_match_all($pattern, $text, $p);

	foreach ($p[0] as $val) {

		if ($val != '') {
			$url[] = str_replace("\n", "", trim($val));
		}

	}

	return array_unique($url);

}

/**
 * Проверка текста на наличие html
 *
 * @param string|null $string $string
 *
 * @return bool
 * @category Core
 * @package  Func
 */
function isHTML(string $string = NULL): bool {

	return $string != strip_tags(htmlspecialchars_decode($string));

}

/**
 * Возвращает иконку по расширению файла
 *
 * @param string|null $filename
 *
 * @return string
 * @category Core
 * @package  Func
 */
function get_icon2(string $filename = NULL): string {

	global $IconArray2;

	$icons = $IconArray2;

	$extension = strtolower(substr(strrchr($filename, "."), 1));

	if ($extension == "") {
		return '<i class="icon-file-code gray"></i>';
	}

	foreach ($icons as $icon => $types) {

		$i = explode(" ", $types);

		foreach ($i as $type) {

			if ($extension == $type) {
				return $icon;
			}

		}

	}

	return '<i class="icon-file-code gray"></i>';

}

/**
 * Возвращает css-класс иконки по расширению файла
 *
 * @param string|NULL $filename
 *
 * @return string
 * @category Core
 * @package  Func
 */
function get_icon3(string $filename = NULL): string {

	global $IconArray3;

	$icons = $IconArray3;

	$extension = strtolower(substr(strrchr($filename, "."), 1));

	if ($extension == "") {
		return 'icon-file-code gray';
	}

	foreach ($icons as $icon => $types) {

		$i = explode(" ", $types);

		foreach ($i as $type) {

			if ($extension == $type) {
				return $icon;
			}

		}

	}

	return 'icon-file-code gray';

}

/**
 * Возвращает MIMEtype по расширению файла
 *
 * @param string|NULL $filename
 *
 * @return string
 * @category Core
 * @package  Func
 */
function get_mimetype(string $filename = NULL): string {

	global $MIMEtypes;

	$mime = $MIMEtypes;

	$extension = getExtention($filename);

	if ($extension == "") {
		return "Unknown/Unknown";
	}

	foreach ($mime as $mimetype => $file_extensions) {

		$f = explode(" ", $file_extensions);

		foreach ($f as $file_extension) {

			if ($extension == $file_extension) {
				return $mimetype;
			}

		}

	}

	return "Unknown/Unknown";
}

/**
 * Возвращает возможность открытия файла в браузере на просмотр
 * 'png','jpeg','jpg','gif','pdf' - можно просмотреть
 *
 * @param $file
 *
 * @return bool
 * @category Core
 * @package  Func
 */
function isViewable($file): bool {

	$extension = mb_strtolower(substr(strrchr($file, "."), 1), 'utf-8');
	$viewable  = [
		'png',
		'jpeg',
		'jpg',
		'gif',
		'pdf'
	];//,'docx');

	if (in_array($extension, $viewable)) {
		return true;
	}

	return false;

}

/**
 * Возвращает расширение файла
 *
 * @param string|null $file
 *
 * @return string
 * @category Core
 * @package  Func
 */
function getExtention(string $file = NULL): string {

	return texttosmall(substr(strrchr($file, "."), 1));

}

/**
 * Работа с ip-адресом
 *
 * @param $addr
 * @param $cidrs
 *
 * @return bool
 * @category Core
 * @package  Func
 */
function IP_match($addr, $cidrs): bool {

	if (!is_array($cidrs)) {
		$cidrs = [$cidrs];
	}

	foreach ($cidrs as $cidr) {

		if (strpos($cidr, "/")) { // Для записей типа 82.208.77.243/32

			$fack = explode("/", $cidr, 2);
			$ip   = $fack[0];
			$mask = $fack[1];

			//[ $ip, $mask ] = explode( "/", $cidr, 2 );

			if (strpos(".", $mask)) {
				$mask = 0xffffffff & ip2long($mask);
			}
			else {
				$mask = 0xffffffff << 32 - (int)$mask;
			}

			if (( ip2long($addr) & $mask ) == ( ip2long($ip) & $mask )) {
				return true;
			}

		}
		elseif (strpos($cidr, "-")) { // Для записей типа 82.208.77.243-85.95.168.249

			$fack = explode("/", $cidr, 2);
			$ip_1 = $fack[0];
			$ip_2 = $fack[1];

			//[ $ip_1, $ip_2 ] = explode( "-", $cidr, 2 );

			if (( ( ip2long($ip_2) > ip2long($ip_1) ) && ( ( ( ip2long($addr) - ip2long($ip_1) ) >= 0 ) && ( ( ip2long($ip_2) - ip2long($addr) ) >= 0 ) ) ) || ( ( ip2long($ip_2) < ip2long($ip_1) ) && ( ( ( ip2long($addr) - ip2long($ip_1) ) <= 0 ) && ( ( ip2long($ip_2) - ip2long($addr) ) <= 0 ) ) ) || ( ( ip2long($ip_1) == ip2long($ip_2) ) && ( ip2long($ip_1) == ip2long($addr) ) )) {
				return true;
			}
		}
		elseif ($addr === $cidr) {
			return true; // Для одиночных IP
		}

	}

	return false;
}

/**
 * Отправка уведомлений. Новое
 *
 * @param $tip
 * @param $params
 *
 * @return string
 * @throws Exception
 * @category Core
 * @package  Func
 */
function sendNotify($tip, $params): string {

	global $mailme_rez;
	global $mailsender_rez;

	//print_r($params);

	$hidenotice = $params['notice'];
	$iduser     = $params['iduser'];

	$db          = $GLOBALS['db'];
	$identity    = $GLOBALS['identity'];
	$valuta      = $GLOBALS['valuta'];
	$sqlname     = $GLOBALS['sqlname'];
	$productInfo = $GLOBALS['productInfo'];
	$iduser1     = $GLOBALS['iduser1'];

	if ($params['identity'] > 0) {
		$identity = $params['identity'];
	}

	$server = $_SERVER['HTTP_HOST'];
	$scheme = $_SERVER['HTTP_SCHEME'] ?? ( ( isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'off' ) || 443 == $_SERVER['SERVER_PORT'] ) ? 'https://' : 'http://';


	$arr  = [
		'send_client',
		'send_person',
		'send_dog'
	];
	$arr2 = [
		'lead_add',
		'lead_setuser',
		'lead_do'
	];

	$msg = '';
	$cc  = [];

	$secrty = 'yes';
	$link   = $description = $theme = '';
	$sendo  = 'off';
	$to     = $toname = $from = $fromname = '';

	//print $tip."\n";

	$mailme = $db -> getOne("SELECT mailme FROM {$sqlname}settings WHERE id = '$identity'");

	if ($params['did'] > 0) {

		$qq = "
			SELECT
				{$sqlname}dostup.iduser as iduser,
				{$sqlname}user.email as email,
				{$sqlname}user.title as name,
				{$sqlname}user.subscription as subscribe
			FROM {$sqlname}dostup
				LEFT JOIN {$sqlname}user ON {$sqlname}dostup.iduser = {$sqlname}user.iduser
			WHERE
				{$sqlname}dostup.did = '$params[did]' and
				{$sqlname}dostup.identity = '$identity'
		";

		$re = $db -> query($qq);
		while ($data = $db -> fetch($re)) {

			$subscribe = yexplode(";", (string)$data['subscribe']);
			if ($subscribe[6] == "on") {
				$cc[] = [
					"name"  => $data['name'],
					"email" => $data['email']
				];
			}

		}

	}

	if (( $mailme == 'yes' || in_array($tip, $arr2) ) /*&& $iduser1 != $iduser*/) {

		//формируем ссылку в зависимости от типа события
		switch ($tip) {

			case 'new_client':

				$link        = '<a href="'.$scheme.$server.'/card.client?clid='.$params['clid'].'">Ссылка</a>';
				$title       = $params['title'];
				$description = "Название клиента: <b>".$title."</b><br>";
				$description .= "Ответственный: <b>".current_user($params['iduser'])."</b><br>";
				$theme       = "CRM. Создан новый Клиент";

				break;
			case 'send_client':

				$link        = '<a href="'.$scheme.$server.'/card.client?clid='.$params['clid'].'">Ссылка</a>';
				$title       = $params['title'];
				$iduser      = $params['iduser'];
				$description = "Название клиента: <b>".$title."</b><br>";
				$description .= "Ответственный: <b>".current_user($params['iduser'])."</b><br>";

				if ($params['comment'] != '') {
					$description .= "<br>Комментарий: <b>".$params['comment']."</b><br>";
				}

				$theme = "CRM. Вам назначен Клиент";

				break;
			case 'delete_client':

				$link        = '<a href="'.$scheme.$server.'/card.client?clid='.$params['clid'].'">Ссылка</a>';
				$title       = $params['title'];
				$iduser      = $params['iduser'];
				$description = "Название клиента: <b>".$title."</b><br>";
				$description .= "Ответственный: <b>".current_user($params['iduser'])."</b><br>";

				if ($params['comment'] != '') {
					$description .= "<br>Комментарий: <b>".$params['comment']."</b><br>";
				}

				$theme = "CRM. Клиент удален";

				break;

			case 'send_person':

				$link   = '<a href="'.$scheme.$server.'/card.person?pid='.$params['pid'].'">Ссылка</a>';
				$person = $params['person'];
				$iduser = $params['iduser'];
				$clid   = $params['clid'];

				$description = "Ф.И.О.: <b>".$person."</b><br>";

				if ($clid > 0) {
					$description .= 'Клиент: <b><A href="'.$scheme.$server.'/card.client?clid='.$clid.'" target="_blank" title="Открыть в новом окне">'.current_client($clid).'</a></b><br>';
				}

				$description .= "Ответственный: <b>".current_user($params['iduser'])."</b><br>";

				if ($params['comment'] != '') {
					$description .= "<br>Комментарий: <b>".$params['comment']."</b><br>";
				}

				$theme = "CRM. Вам назначен Контакт";

				break;

			case 'new_dog':

				$link = '<a href="'.$scheme.$server.'/card.deal?did='.$params['did'].'">Ссылка</a>';

				$title      = ( $params['title'] != '' ) ? $params['title'] : getDogData($params['did'], 'title');
				$kol        = $params['kol'] ?? getDogData($params['did'], 'kol');
				$clid       = $params['clid'] ?? getDogData($params['did'], 'clid');
				$client     = ( $params['client'] != '' ) ? $params['client'] : current_client($clid);
				$pid        = $params['pid'] ?? getDogData($params['did'], 'pid');
				$person     = $params['person'] ?? current_person($pid);
				$dogstatus  = $params['dogstatus'] ?? current_dogstepname(getDogData($params['did'], 'idcategory'));
				$datum_plan = ( $params['datum_plan'] != '' ) ? $params['datum_plan'] : getDogData($params['did'], 'datum_plan');
				$iduser     = $params['iduser'] ?? getDogData($params['did'], 'iduser');

				/**
				 * Для сервисной сделки сумму считаем по спецификации
				 */
				if (isServices((int)$params['did'])) {

					$spekaData = ( new Speka() ) -> getSpekaData((int)$params['did']);
					$kol       = $spekaData['summaItog'];

				}

				$description = "Название сделки: <b>".$title."</b><br>Текущий статус: <b>".$dogstatus."%</b><br>Плановая сумма: <b>".num_format($kol).$valuta."</b><br>Плановая дата: <b>".format_date_rus($datum_plan)."</b><br>";

				if ($clid > 0) {
					$description .= 'Клиент: <b><A href="'.$scheme.$server.'/card.client?clid='.$params['clid'].'" target="_blank" title="Открыть в новом окне">'.$client.'</a></b><br>';
				}

				if ($pid > 0) {
					$description .= 'Контакт: <b><A href="'.$scheme.$server.'/card.person?pid='.$params['pid'].'" target="_blank" title="Открыть в новом окне">'.$person.'</a></b><br>';
				}

				$description .= "Ответственный: <b>".current_user($params['iduser'])."</b><br>";

				if ($params['comment'] != '') {
					$description .= "<br>Комментарий: <b>".$params['comment']."</b><br>";
				}

				$theme = "CRM. Создана новая Сделка";

				break;
			case 'edit_dog':

				$link = '<a href="'.$scheme.$server.'/card.deal?did='.$params['did'].'">Ссылка</a>';

				$title      = ( $params['title'] != '' ) ? $params['title'] : getDogData($params['did'], 'title');
				$kol        = $params['kol'] ?? getDogData($params['did'], 'kol');
				$clid       = $params['clid'] ?? getDogData($params['did'], 'clid');
				$client     = ( $params['client'] != '' ) ? $params['client'] : current_client($clid);
				$pid        = $params['pid'] ?? getDogData($params['did'], 'pid');
				$person     = $params['person'] ?? current_person($pid);
				$dogstatus  = $params['dogstatus'] ?? current_dogstepname(getDogData($params['did'], 'idcategory'));
				$datum_plan = ( $params['datum_plan'] != '' ) ? $params['datum_plan'] : getDogData($params['did'], 'datum_plan');
				$iduser     = $params['iduser'] ?? getDogData($params['did'], 'iduser');
				$log        = nl2br(str_replace('Изменены параметры записи:', '', $params['log']));

				/**
				 * Для сервисной сделки сумму считаем по спецификации
				 */
				if (isServices((int)$params['did'])) {

					$spekaData = ( new Speka() ) -> getSpekaData((int)$params['did']);
					$kol       = $spekaData['summaItog'];

				}

				$description = "Название сделки: <b>".$title."</b><br>Текущий этап: <b>".$dogstatus."%</b><br>Плановая сумма: <b>".num_format($kol)."</b><br>Плановая дата: <b>".format_date_rus($datum_plan)."</b><br>";

				if ($clid > 0) {
					$description .= 'Клиент: <b><A href="'.$scheme.$server.'/card.client?clid='.$params['clid'].'" target="_blank" title="Открыть в новом окне">'.$client.'</a></b><br>';
				}

				if ($pid > 0) {
					$description .= 'Контакт: <b><A href="'.$scheme.$server.'/card.person?pid='.$params['pid'].'" target="_blank" title="Открыть в новом окне">'.$person.'</a></b><br>';
				}

				$description .= "Ответственный: <b>".current_user($params['iduser'])."</b><br>";
				$description .= "<br><br><em>".$log."</em><br>";

				if ($params['comment'] != '') {
					$description .= "<br>Комментарий: <b>".$params['comment']."</b><br>";
				}

				$theme = "CRM. Изменена Сделка";

				break;
			case 'send_dog':

				$link = '<a href="'.$scheme.$server.'/card.deal?did='.$params['did'].'">Ссылка</a>';

				$title      = ( $params['title'] != '' ) ? $params['title'] : getDogData($params['did'], 'title');
				$kol        = $params['kol'] ?? getDogData($params['did'], 'kol');
				$clid       = $params['clid'] ?? (int)getDogData($params['did'], 'clid');
				$client     = ( $params['client'] != '' ) ? $params['client'] : current_client($clid);
				$pid        = $params['pid'] ?? (int)getDogData($params['did'], 'pid');
				$person     = $params['person'] ?? current_person($pid);
				$dogstatus  = $params['dogstatus'] ?? current_dogstepname(getDogData($params['did'], 'idcategory'));
				$datum_plan = ( $params['datum_plan'] != '' ) ? $params['datum_plan'] : getDogData($params['did'], 'datum_plan');
				$iduser     = $params['iduser'] ?? getDogData($params['did'], 'iduser');

				/**
				 * Для сервисной сделки сумму считаем по спецификации
				 */
				if (isServices((int)$params['did'])) {

					$spekaData = ( new Speka() ) -> getSpekaData((int)$params['did']);
					$kol       = $spekaData['summaItog'];

				}

				$description = "Название сделки: <b>".$title."</b><br>Текущий этап: <b>".$dogstatus."%</b><br>Плановая сумма: <b>".num_format($kol)."</b><br>Плановая дата: <b>".format_date_rus($datum_plan)."</b><br>";

				if ($clid > 0) {
					$description .= 'Клиент: <b><A href="'.$scheme.$server.'/card.client?clid='.$params['clid'].'" target="_blank" title="Открыть в новом окне">'.$client.'</a></b><br>';
				}

				if ($pid > 0) {
					$description .= 'Контакт: <b><A href="'.$scheme.$server.'/card.person?pid='.$params['pid'].'" target="_blank" title="Открыть в новом окне">'.$person.'</a></b><br>';
				}

				$description .= "Ответственный: <b>".current_user($params['iduser'])."</b><br>";

				if ($params['comment'] != '') {
					$description .= "<br>Комментарий: <b>".$params['comment']."</b><br>";
				}

				$theme = "CRM. Вы назначены куратором Сделки";

				break;
			case 'step_dog':

				$link = '<a href="'.$scheme.$server.'/card.deal?did='.$params['did'].'">Ссылка</a>';

				$title        = ( $params['title'] != '' ) ? $params['title'] : getDogData($params['did'], 'title');
				$kol          = $params['kol'] ?? getDogData($params['did'], 'kol');
				$clid         = $params['clid'] ?? getDogData($params['did'], 'clid');
				$client       = ( $params['client'] != '' ) ? $params['client'] : current_client($clid);
				$pid          = $params['pid'] ?? getDogData($params['did'], 'pid');
				$person       = $params['person'] ?? current_person($pid);
				$dogstatus    = $params['dogstatus'] ?? current_dogstepname(getDogData($params['did'], 'idcategory'));
				$datum_plan   = ( $params['datum_plan'] != '' ) ? $params['datum_plan'] : getDogData($params['did'], 'datum_plan');
				$iduser       = $params['iduser'] ?? getDogData($params['did'], 'iduser');
				$dogstatusold = $params['dogstatusold'];

				/**
				 * Для сервисной сделки сумму считаем по спецификации
				 */
				if (isServices((int)$params['did'])) {

					$spekaData = ( new Speka() ) -> getSpekaData((int)$params['did']);
					$kol       = $spekaData['summaItog'];

				}

				$description = "Название сделки: <b>".$title."</b><br>Текущий этап: <b>".$dogstatus."%</b><br>Предыдущий этап: <b>".$dogstatusold."%</b><br>Плановая сумма: <b>".num_format($kol)."</b><br>Плановая дата: <b>".format_date_rus($datum_plan)."</b><br>";

				if ($clid > 0) {
					$description .= 'Клиент: <b><A href="'.$scheme.$server.'/card.client?clid='.$params['clid'].'&action=view" target="_blank" title="Открыть в новом окне">'.$client.'</a></b><br>';
				}

				if ($pid > 0) {
					$description .= 'Контакт: <b><A href="'.$scheme.$server.'/card.person?pid='.$params['pid'].'&action=view" target="_blank" title="Открыть в новом окне">'.$person.'</a></b><br>';
				}

				$description .= "Ответственный: <b>".current_user($params['iduser'])."</b><br>";

				if ($params['comment'] != '') {
					$description .= "<br>Комментарий: <b>".$params['comment']."</b><br>";
				}

				$theme = "CRM. Изменен этап сделки Сделки";

				break;
			case 'close_dog':

				$link = '<a href="'.$scheme.$server.'/card.deal?did='.$params['did'].'">Ссылка</a>';

				$deal = get_dog_info($params['did'], 'yes');

				$title      = ( $params['title'] != '' ) ? $params['title'] : $deal['title'];
				$kol        = $params['kol'] ?? $deal['kol'];
				$clid       = $params['clid'] ?? $deal['clid'];
				$client     = ( $params['client'] != '' ) ? $params['client'] : current_client($clid);
				$pid        = $params['pid'] ?? $deal['pid'];
				$person     = $params['person'] ?? current_person($pid);
				$dogstatus  = $params['dogstatus'] ?? current_dogstepname($deal['idcategory']);
				$datum_plan = ( $params['datum_plan'] != '' ) ? $params['datum_plan'] : $deal['datum_plan'];
				$iduser     = $params['iduser'] ?? $deal['iduser'];

				$datum_close = $deal['datum_close'];//
				$marga       = $deal['marga'];      //
				$kol_fact    = $deal['kol_fact'];   //
				$status      = $params['status'];   //

				$description = "
					Название сделки: <b>".$title."</b><br>
					Текущий этап: <b>".$dogstatus."%</b><br>
					Статус закрытия: <b>".$status."</b><br>
					Плановая сумма: <b>".num_format($kol)."</b><br>
					Фактическая сумма: <b>".num_format($kol_fact)."</b><br>
					Фактическая маржа: <b>".num_format($marga)."</b><br>
					Плановая дата: <b>".format_date_rus($datum_plan)."</b><br>
					Фактическая дата: <b>".format_date_rus($datum_close)."</b><br>
				";

				if ($clid > 0) {
					$description .= 'Клиент: <b><A href="'.$scheme.$server.'/card.client?clid='.$clid.'" target="_blank" title="Открыть в новом окне">'.$client.'</a></b><br>';
				}

				if ($pid > 0) {
					$description .= 'Контакт: <b><A href="'.$scheme.$server.'/card.person?pid='.$pid.'" target="_blank" title="Открыть в новом окне">'.$person.'</a></b><br>';
				}

				$description .= "Ответственный: <b>".current_user($params['iduser'])."</b><br>";

				if ($params['comment'] != '') {
					$description .= "<br>Комментарий: <b>".$params['comment']."</b><br>";
				}

				$theme = "CRM. Закрыта сделка";

				break;

			case 'invoice_doit':

				$link = '<a href="'.$scheme.$server.'/card.deal?did='.$params['did'].'#7">Ссылка</a>';

				$deal = get_dog_info($params['did'], 'yes');

				$credit = $db -> getRow("SELECT * FROM {$sqlname}credit WHERE crid = '".$params['crid']."' AND identity='identity'");

				$clid    = $params['clid'] ?? $deal['clid'];
				$client  = ( $params['client'] != '' ) ? $params['client'] : current_client($clid);
				$iduser  = $params['iduser'] ?? $deal['iduser'];
				$invoice = ( isset($params['invoice']) ) ? " № ".$params['invoice'] : " № ".$credit['invoice'];
				$datum   = $params['invoice_date'] ?? $credit['invoice_date'];
				$summa   = $params['summa_credit'] ?? $credit['summa_credit'];
				$type    = $params['tip'] ?? $credit['tip'];
				$rs      = $params['rs'] ?? $credit['rs'];

				$description = "
					Счет <b>".$invoice."</b><br>
					Дата оплаты: <b>".format_date_rus($datum)."</b><br>
					Сумма: <b>".num_format($summa)." ".$valuta."</b><br>
					Тип счета: <b>".$type."</b><br>
					Расчетный счет: <b>".$rs."</b><br>
				";

				$description .= '<br>Сделка: <b><A href="'.$scheme.$server.'/card.deal?did='.$params['did'].'" target="_blank" title="Открыть в новом окне">'.$client.'</a></b><br>';
				$description .= "Ответственный: <b>".current_user($params['iduser'])."</b><br><br>";

				if ($clid > 0) {
					$description .= 'Клиент: <b><A href="'.$scheme.$server.'/card.client?clid='.$clid.'" target="_blank" title="Открыть в новом окне">'.$client.'</a></b><br>';
				}

				if ($params['des'] != '') {
					$description .= "<br>Комментарий: <b>".$params['des']."</b><br>";
				}

				$theme = "CRM. Получена оплата";

				break;

			case 'lead_add':

				$link = '<a href="'.$scheme.$server.'/leads?id='.$params['id'].'">Ссылка</a>';

				$result  = $db -> getRow("SELECT * FROM {$sqlname}leads WHERE id = '".$params['id']."'");
				$datum   = $result['datum'];
				$title   = $result['title'];
				$email   = $result['email'];
				$phone   = $result['phone'];
				$des     = $result['description'];
				$city    = $result['city'];
				$country = $result['country'];
				$iduser  = $result['iduser'];

				$user = ( $iduser ) ? current_user($iduser) : 'Необходимо назначить';

				$description = '
				<table width="100%" border="0" cellspacing="2" cellpadding="2" id="zebra">
				<tr height="25" class="noborder">
					<td width="150" class="cherta"><b>Дата получения:</b></td>
					<td>'.get_sfdate($datum).'</td>
				</tr>
				<tr height="25" class="noborder">
					<td><b>Имя:</b></td>
					<td><b>'.$title.'</b></td>
				</tr>
				<tr height="25" class="noborder">
					<td><b>Email:</b></td>
					<td><b>'.link_it($email).'</b></td>
				</tr>
				<tr height="25" class="noborder">
					<td><b>Телефон:</b></td>
					<td><b>'.formatPhoneUrl($phone).'</b></td>
				</tr>
				<tr height="25" class="noborder">
					<td><b>Страна, Город:</b></td>
					<td><b>'.$country.', '.$city.'</b></td>
				</tr>
				<tr height="25" class="noborder">
					<td><b>Ответственный:</b></td>
					<td><b>'.$user.'</b></td>
				</tr>
				<tr height="25" class="noborder">
					<td><b>Описание:</b></td>
					<td>'.str_replace("\n", "<br>", $des).'</td>
				</tr>
				</table>
				<br>';
				$theme       = "CRM. Новый Входящий интерес (Лид). ID = ".$params['id'];

				break;
			case 'lead_setuser':

				$link = '<a href="'.$scheme.$server.'/leads.php?id='.$params['id'].'">Ссылка</a>';

				$result  = $db -> getRow("SELECT * FROM {$sqlname}leads WHERE id = '".$params['id']."' AND identity = '".$identity."'");
				$datum   = $result['datum'];
				$title   = $result['title'];
				$email   = $result['email'];
				$phone   = $result['phone'];
				$des     = $result['description'];
				$city    = $result['city'];
				$country = $result['country'];
				$iduser  = $result['iduser'];

				$description = '
				<table width="100%" border="0" cellspacing="2" cellpadding="2" id="zebra">
				<tr height="25" class="noborder">
					<td width="150" class="cherta"><b>Дата получения:</b></td>
					<td>'.get_sfdate($datum).'</td>
				</tr>
				<tr height="25" class="noborder">
					<td><b>Имя:</b></td>
					<td><b>'.$title.'</b></td>
				</tr>
				<tr height="25" class="noborder">
					<td><b>Email:</b></td>
					<td><b>'.link_it($email).'</b></td>
				</tr>
				<tr height="25" class="noborder">
					<td><b>Телефон:</b></td>
					<td><b>'.formatPhoneUrl($phone).'</b></td>
				</tr>
				<tr height="25" class="noborder">
					<td><b>Страна, Город:</b></td>
					<td><b>'.$country.', '.$city.'</b></td>
				</tr>
				<tr height="25" class="noborder">
					<td><b>Описание:</b></td>
					<td>'.str_replace("\n", "<br>", $des).'</td>
				</tr>
				</table>
				<br>';
				$theme       = "CRM. Вы назначены Ответственным за обработку Входящего интереса (Лида)";

				break;
			case 'lead_do':

				$link   = '<a href="'.$scheme.$server.'/leads?id='.$params['id'].'">Ссылка</a>';
				$result = $db -> getRow("SELECT * FROM {$sqlname}leads WHERE id = '".$params['id']."' AND identity = '".$identity."'");
				$iduser = $result[''];
				$clid   = $result['clid'];
				$pid    = $result['pid'];
				$did    = $result['did'];
				$rez    = $result['rezult'];

				$rezult = [
					'1' => 'Спам',
					'2' => 'Дубль',
					'3' => 'Другое',
					'4' => 'Не целевой'
				];

				$description = '';

				if ($rez > 0) {
					$description .= 'Входящий интерес <b>дисквалифицирован</b> с результатом: <b>'.strtr($rez, $rezult).'</b>';
				}
				else {
					$description .= 'Входящий интерес <b>квалифицирован</b>'.( $did > 0 ? ': Создана сделка.<br>' : '' );
				}


				if ($clid > 0) {
					$description .= '<br>Клиент: <b><A href="'.$scheme.$server.'/card.client?clid='.$clid.'" target="_blank" title="Открыть в новом окне">'.current_client($clid).'</a></b><br>';
				}

				if ($pid > 0) {
					$description .= '<br>Контакт: <b><A href="'.$scheme.$server.'/card.person?pid='.$pid.'" target="_blank" title="Открыть в новом окне">'.current_person($pid).'</a></b><br>';
				}

				if ($did > 0) {
					$description .= 'Сделка: <b><A href="'.$scheme.$server.'/card.deal?did='.$did.'" target="_blank" title="Открыть в новом окне">'.current_dogovor($did).'</a></b><br>';
				}

				$theme = "CRM. Обработан входящий интерес (Лид)";

				break;

		}

		if (!in_array($tip, $arr2)) {

			//если это не передача, то отправляем уведомление руководителю
			if (!in_array($tip, $arr)) {

				//от кого отправляем
				$res      = $db -> getRow("SELECT title, email, mid FROM {$sqlname}user WHERE iduser = '$iduser1' AND identity = '$identity'");
				$fromname = $res["title"];
				$from     = $res["email"];
				$mid      = $res["mid"];//это руководитель

				//кому отправляем
				$res          = $db -> getRow("SELECT * FROM {$sqlname}user WHERE iduser = '$mid' AND identity = '$identity'");
				$toname       = $res["title"];
				$to           = $res["email"];
				$secrty       = $res["secrty"];      //проверка на активность юзера
				$subscription = $res["subscription"];//массив подписок на уведомления

				$subscribe = explode(";", $subscription);

				//print $iduser1."\n";
				//print $mid."\n";
				//print_r($subscribe);

				switch ($tip) {
					case 'new_client':
						$sendo = $subscribe[0];
						break;
					case 'send_client':
						$sendo = $subscribe[1];
						break;
					case 'delete_client':
						$sendo = $subscribe[2];
						break;
					case 'new_person':
						$sendo = $subscribe[3];
						break;
					case 'send_person':
						$sendo = $subscribe[4];
						break;
					case 'new_dog':
						$sendo = $subscribe[5];
						break;
					case 'edit_dog':
					case 'send_dog':
					case 'step_dog':
						$sendo = $subscribe[6];
						break;
					case 'close_dog':
						$sendo = $subscribe[7];
						break;
					case 'ical':
						$sendo = $subscribe[8];
						break;
					case 'new_task':
						$sendo = $subscribe[9];
						break;
					case 'task_do':
						$sendo = $subscribe[10];
						break;
					case 'invoice_doit':
						$sendo = $subscribe[11];
						break;
				}

				if ($mid < 1) {
					$sendo = 'off';
				}

			}

			//если это передача, то отправляем текущему ответственному
			else {

				//от кого отправляем
				$res      = $db -> getRow("SELECT title, email FROM {$sqlname}user WHERE iduser = '$iduser1' AND identity = '$identity'");
				$fromname = $res["title"];
				$from     = $res["email"];

				if ($iduser > 0) {

					$res          = $db -> getRow("SELECT title, email, secrty, subscription FROM {$sqlname}user WHERE iduser = '$iduser' AND identity = '$identity'");
					$toname       = $res["title"];
					$to           = $res["email"];
					$secrty       = $res["secrty"];      //проверка на активность юзера
					$subscription = $res["subscription"];//массив подписок на уведомления

					$subscribe = explode(";", $subscription);

					//print $iduser1."\n";
					//print $iduser."\n";
					//print_r($subscribe);

					switch ($tip) {
						case 'new_client':
							$sendo = $subscribe[0];
							break;
						case 'send_client':
							$sendo = $subscribe[1];
							break;
						case 'trash_client':
							$sendo = $subscribe[2];
							break;
						case 'new_person':
							$sendo = $subscribe[3];
							break;
						case 'send_person':
							$sendo = $subscribe[4];
							break;
						case 'new_dog':
							$sendo = $subscribe[5];
							break;
						case 'edit_dog':
						case 'send_dog':
						case 'step_dog':
							$sendo = $subscribe[6];
							break;
						case 'close_dog':
							$sendo = $subscribe[7];
							break;
						case 'ical':
							$sendo = $subscribe[8];
							break;
						case 'new_task':
							$sendo = $subscribe[9];
							break;
						case 'task_do':
							$sendo = $subscribe[10];
							break;
						case 'invoice_doit':
							$sendo = $subscribe[11];
							break;

					}

				}

			}

		}

		//если это лид
		if ($tip == 'lead_add') {

			$res      = $db -> getRow("SELECT company_mail, coordinator FROM {$sqlname}settings WHERE id = '$identity'");
			$fromname = "CRM";
			$from     = $res["company_mail"];
			//$coordinator = $res["coordinator"];

			$mdwset       = $db -> getRow("SELECT * FROM {$sqlname}modules WHERE mpath = 'leads' and identity = '$identity'");
			$leadsettings = json_decode($mdwset['content'], true);
			$coordinator  = $leadsettings["leadСoordinator"];

			$sendo = 'on';

			if ($iduser == 0) {

				$res    = $db -> getRow("SELECT * FROM {$sqlname}user WHERE iduser = '$coordinator' AND identity = '$identity'");
				$toname = $res["title"];
				$to     = $res["email"];
				$secrty = $res["secrty"];

			}
			elseif ($iduser > 0) {

				$res    = $db -> getRow("SELECT * FROM {$sqlname}user WHERE iduser = '$iduser' AND identity = '$identity'");
				$toname = $res["title"];
				$to     = $res["email"];
				$secrty = $res["secrty"];

			}

		}
		if ($tip == 'lead_setuser') {

			$sendo = 'on';

			$res      = $db -> getRow("SELECT * FROM {$sqlname}user WHERE iduser = '$iduser1' AND identity = '$identity'");
			$fromname = $res["title"];
			$from     = $res["email"];

			$res    = $db -> getRow("SELECT * FROM {$sqlname}user WHERE iduser = '$iduser' AND identity = '$identity'");
			$toname = $res["title"];
			$to     = $res["email"];
			$secrty = $res["secrty"];

		}
		if ($tip == 'lead_do') {

			$sendo = 'on';

			$coordinator = $db -> getOne("SELECT coordinator FROM {$sqlname}settings WHERE id = '$identity'");

			$res      = $db -> getRow("SELECT * FROM {$sqlname}user WHERE iduser = '$iduser1' AND identity = '$identity'");
			$fromname = $res["title"];
			$from     = $res["email"];

			$res    = $db -> getRow("SELECT * FROM {$sqlname}user WHERE iduser = '$coordinator' AND identity = '$identity'");
			$toname = $res["title"];
			$to     = $res["email"];

		}

		//получаем данные шаблона
		$content_tpl = $db -> getOne("SELECT content FROM {$sqlname}tpl WHERE tip = '$tip' AND identity = '$identity'");

		if ($tip == 'send_dog') {
			$content_tpl = 'Изменен ответственный за Сделку - {link}';
		}

		if ($tip == 'step_dog') {
			$content_tpl = 'Изменен этап сделки - {link}';
		}

		if ($tip == 'invoice_doit') {
			$content_tpl = 'Получена оплата по счету  - {link}';
		}

		//формируем сообщение с заменой слов-шаблонов
		$content_tpl = str_replace([
			'{link}',
			"\n"
		], [
			$link,
			'<br>'
		], $content_tpl);

		$content_tpl .= "<br>===============================<br>";
		$content_tpl .= $description;

		//добавляем подпись отправителя
		$content_tpl .= "===============================";
		$content_tpl .= "<br>Уведомления CRM";

		$content_tpl .= '<br><br><div style="font-size:12px; color:#34495E">Вы получили это уведомление потому-что подписаны на получение сообщений такого характера в Системе управления продажами '.$productInfo['name'].'. Чтобы не получать сообщения такого характера необходимо произвести настройку <a href="'.$productInfo['site'].'/docs/26" title="Как отписаться от уведомлений">по этой инструкции</a>.</div>';

		//отправляем сообщение
		$html = '<html lang="ru"><head><title>'.$theme.'</title></head><body>';
		$html .= $content_tpl;
		$html .= "</body></html>";

		if ($secrty != 'no' && $sendo == 'on' && $to != '') {

			if (
				mailto([
					$to,
					$toname,
					$from,
					$fromname,
					$theme,
					$html,
					[],
					$cc
				]) == ''
			) {

				if ($hidenotice != 'no') {
					$msg .= '<br>Отправлено уведомление '.$toname;
				}

			}
			elseif ($hidenotice != 'no') {
				$msg .= '<br>'.$mailsender_rez;
			}

		}

	}

	return $msg;

}

/**
 * Массовые уведомления
 *
 * @param $tip
 * @param $params
 *
 * @return string
 * @throws Exception
 * @category Core
 * @package  Func
 */
function sendMassNotify($tip, $params): string {

	global $mailme_rez;
	global $mailsender_rez;

	$hidenotice = $params['notice'];

	$db          = $GLOBALS['db'];
	$identity    = $GLOBALS['identity'];
	$valuta      = $GLOBALS['valuta'];
	$sqlname     = $GLOBALS['sqlname'];
	$productInfo = $GLOBALS['productInfo'];
	$iduser1     = $GLOBALS['iduser1'];

	global $mailsender_rez;

	$description = '';
	$des         = '';

	$server = $_SERVER['HTTP_HOST'];
	$scheme = $_SERVER['HTTP_SCHEME'] ?? ( ( isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'off' ) || 443 == $_SERVER['SERVER_PORT'] ) ? 'https://' : 'http://';

	$msg   = '';
	$sendo = 'on';

	$mailme = $db -> getOne("SELECT mailme FROM {$sqlname}settings WHERE id = '$identity'");

	if ($mailme == 'yes' && ( !empty($params['clients']) || !empty($params['dogovor']) )) {

		//формируем ссылку в зависимости от типа события
		switch ($tip) {
			case 'send_client':

				foreach ($params['clients'] as $clnt) {

					if ($clnt['clid'] > 0) {
						$des .= '<li>Клиент: <b>'.$clnt['title'].'</b>&nbsp;<a href="'.$scheme.$server.'/card.client.php?clid='.$clnt['clid'].'">Ссылка</a></li>';
					}

				}

				$description = '<ol>'.$des.'</ol>';

				if ($params['comment'] != '') {
					$description .= "<br>Комментарий: <b>".$params['comment']."</b><br>";
				}

				$theme = "CRM. Вам назначены Клиенты";

				break;
			case 'send_dog':

				foreach ($params['dogovor'] as $deal) {

					if ($deal['did'] > 0) {
						$des .= '<li>Сделка: <b>'.$deal['title'].'</b>&nbsp;<a href="'.$scheme.$server.'/card.deal.php?did='.$deal['did'].'">Ссылка</a></li>';
					}

				}

				$description = '<ol>'.$des.'</ol>';

				if ($params['comment'] != '') {
					$description .= "<br>Комментарий: <b>".$params['comment']."</b><br>";
				}

				$theme = "CRM. Вы назначены куратором Сделок";

				break;
		}

		//от кого отправляем
		$res      = $db -> getRow("SELECT * FROM {$sqlname}user WHERE iduser = '$iduser1' AND identity = '$identity'");
		$fromname = $res["title"];
		$from     = $res["email"];

		if ($params['iduser'] > 0) {

			$res          = $db -> getRow("SELECT * FROM {$sqlname}user WHERE iduser = '$params[iduser]' AND identity = '$identity'");
			$toname       = $res["title"];
			$to           = $res["email"];
			$secrty       = $res["secrty"];
			$subscription = $res["subscription"];

			$subscribe = explode(";", $subscription);

			switch ($tip) {
				case 'send_client':
					$sendo = $subscribe[1];
					break;
				case 'send_dog':
					$sendo = 'on';
					break;
			}

		}

		/*if ( $tip == 'send_client' ) {
			$content_tpl = 'Вы назначены ответственным за Клиентов';
		}
		if ( $tip == 'send_dog' ) {
			$content_tpl = 'Вы назначены ответственным за Сделки';
		}*/

		//формируем сообщение с заменой слов-шаблонов
		$content_tpl = $description;

		//добавляем подпись отправителя
		$content_tpl .= "===============================";
		$content_tpl .= "<br>Уведомления ".$productInfo['name']." CRM";
		$content_tpl .= '<br><br><br><div style="font-size:12px; color:#34495E">Вы получили это уведомление потому-что подписаны на получение сообщений такого характера в Системе управления продажами '.$productInfo['name'].'. Чтобы не получать сообщения такого характера необходимо произвести настройку <a href="http://'.$productInfo['site'].'.ru/docs/26" title="Как отписаться от уведомлений">по этой инструкции</a>.</div>';

		//отправляем сообщение
		$html = "<html><head><title>".$theme."</title></head><body>";
		$html .= $content_tpl;
		$html .= "</body></html>";

		if ($secrty != 'no' && $sendo == 'on') {

			//if ( mailer( $to, $toname, $from, $fromname, $theme, $html ) == '' ) {
			if (
				mailto([
					$to,
					$toname,
					$from,
					$fromname,
					$theme,
					$html
				]) == ''
			) {

				if ($hidenotice != 'no') {
					$msg .= 'Отправлено уведомление '.$toname;
				}

			}
			elseif ($hidenotice != 'no') {
				$msg .= ''.$mailsender_rez;
			}
		}
	}

	return $msg;
}

/**
 * @param string $to - email адресата
 * @param string $toname - имя адресата
 * @param string $from - email отправителя
 * @param string $fromname - имя Отправителя
 * @param string $subject - тема сообщения
 * @param string $html - содержимое сообщения в формате HTML
 * @param array|null $files - вложение файлов
 *                             - file - реальное имя файла в crm
 *                             - name - отображаемое имя файла
 * @param array|null $cc - копия
 *                             - email
 *                             - name - имя адресата
 *
 * @return string
 * @throws Exception
 * Отправка писем. Новая
 * Использует настроенный SMTP-сервер. Если не настроен ( не активен ), то попробует отправить черезе Sendmail
 *
 * @deprecated use mailto()
 * @see      mailto()
 * @package  Func
 * @category Core
 */
function mailer(string $to, string $toname, string $from, string $fromname, string $subject, string $html, array $files = NULL, array $cc = NULL): string {

	global $mailsender_rez;

	$db       = $GLOBALS['db'];
	$fpath    = $GLOBALS['fpath'];
	$isCloud  = $GLOBALS['isCloud'];
	$skey     = $GLOBALS['skey'];
	$ivc      = $GLOBALS['ivc'];
	$identity = $GLOBALS['identity'];
	$sqlname  = $GLOBALS['sqlname'];
	//$productInfo = $GLOBALS['productInfo'];

	$prefix = dirname(__DIR__);

	//require_once $prefix."/opensource/PHPMailer/class.phpmailer.php";
	//require_once $prefix."/opensource/PHPMailer/class.smtp.php";

	//для внешних запросов требуется определить переменную прямо в функции, иначе ошибка
	if (!$skey) {
		$skey = 'vanilla'.( ( $identity + 7 ) ** 3 ).'round'.( ( $identity + 3 ) ** 2 ).'robin';
	}
	if (!$ivc) {
		$ivc = $db -> getOne("SELECT ivc FROM {$sqlname}settings WHERE id = '".$identity."'");
	}

	$result      = $db -> getRow("SELECT * FROM {$sqlname}smtp WHERE identity = '".$identity."' AND tip = 'send'");
	$active      = $result['active'];
	$smtp_host   = $result['smtp_host'];
	$smtp_port   = $result['smtp_port'];
	$smtp_auth   = $result['smtp_auth'];
	$smtp_secure = $result['smtp_secure'];
	$smtp_from   = $result['smtp_from'];
	$smtp_user   = rij_decrypt($result['smtp_user'], $skey, $ivc);
	$smtp_pass   = rij_decrypt($result['smtp_pass'], $skey, $ivc);
	$charset     = $result['name'];

	if (!$charset) {
		$charset = 'utf-8';
	}

	//получим данные сервера smtp для подключения
	$mail = new PHPMailer();

	if ($active == 'yes') {

		$mail -> IsSMTP();
		$mail -> SMTPAuth   = $smtp_auth;
		$mail -> SMTPSecure = $smtp_secure;
		$mail -> Host       = $smtp_host;
		$mail -> Port       = $smtp_port;
		$mail -> Username   = $smtp_user;
		$mail -> Password   = $smtp_pass;
		//$mail -> SMTPDebug = 0;
		$mail -> SMTPOptions = [
			'ssl' => [
				'verify_peer'       => false,
				'verify_peer_name'  => false,
				'allow_self_signed' => true
			]
		];

		$from = $smtp_from;

	}
	else {
		$mail -> isSendmail();
		$charset = 'utf-8';
	}

	if ($charset != 'utf-8') {

		$fromname = iconv("utf-8", $charset, $fromname);
		$toname   = iconv("utf-8", $charset, $toname);
		$subject  = iconv("utf-8", $charset, $subject);
		$html     = iconv("utf-8", $charset, $html);

	}

	$mail -> CharSet = $charset;
	$mail -> setLanguage('ru', $prefix.'/vendor/phpmailer/phpmailer/language/');
	$mail -> IsHTML(true);
	$mail -> SetFrom($from, $fromname);


	$mail -> AddAddress($to, $toname);

	foreach ($cc as $c) {

		$mail -> AddCC($c['email'], $c['name']);

	}

	$mail -> Subject = $subject;
	$mail -> Body    = $html;

	//$files - переданный массив
	if (!empty($files)) {

		foreach ($files as $f) {

			if ($charset != 'utf-8') {
				$f['name'] = iconv("utf-8", $charset, $f['name']);
			}

			$file = $prefix."/files/".$fpath.$f['file'];
			$mail -> AddAttachment($file, $f['name']);

		}

	}

	$mailsender_rez = ( !$mail -> Send() ) ? 'Ошибка: '.$mail -> ErrorInfo : '';

	$mail -> ClearAddresses();
	$mail -> ClearAttachments();
	$mail -> IsHTML(false);

	return $mailsender_rez;

}

/**
 * Улучшенная отправка email
 *
 * @param array $params - массив параметров (ассоциативный или индексированный)
 * ```php
 * $params = [string $to, string $toname, string $from, string $fromname, string $subject, string $html, array $files, array $cc, array $bcc]
 *
 * $params = [
 *   "to"       => string $to,
 *   "toname"   => string $toname,
 *   "from"     => string $from,
 *   "fromname" => string $fromname,
 *   "subject"  => string $subject,
 *   "html"     => string $html,
 *   "files"    => array $files,
 *   "cc"       => array $cc,
 *   "bcc"      => array $bcc
 * ]
 * ```
 * @return string
 * @throws Exception
 * @package  Func
 * @category Core
 */
function mailto(array $params): string {

	global $mailsender_rez;

	$db       = $GLOBALS['db'];
	$fpath    = $GLOBALS['fpath'];
	$skey     = $GLOBALS['skey'];
	$ivc      = $GLOBALS['ivc'];
	$identity = $GLOBALS['identity'];
	$sqlname  = $GLOBALS['sqlname'];

	$prefix = dirname(__DIR__);

	//$to, $toname = '', $from, $fromname = '', $subject, $html, $files = [], $cc = []
	//$mailData = array_combine(['to', 'toname', 'from', 'fromname', 'subject', 'html', 'files', 'cc'], $params);

	// проверка массива на принадлежность к индексному
	$isIndexed = array_values($params) === $params;

	// по-умолчанию принимаем ассоциативный массив
	$mailData = $params;

	// если пришелл индексный массив
	if ($isIndexed) {

		$mailData = [
			'to'       => $params[0],
			'toname'   => $params[1],
			'from'     => $params[2],
			'fromname' => $params[3],
			'subject'  => $params[4],
			'html'     => $params[5],
			'files'    => (array)$params[6],
			'cc'       => (array)$params[7],
			'bcc'      => (array)$params[8]
		];

	}

	//print_r($mailData);

	//для внешних запросов требуется определить переменную прямо в функции, иначе ошибка
	if (!$skey) {
		$skey = 'vanilla'.( ( $identity + 7 ) ** 3 ).'round'.( ( $identity + 3 ) ** 2 ).'robin';
	}
	if (!$ivc) {
		$ivc = $db -> getOne("SELECT ivc FROM {$sqlname}settings WHERE id = '".$identity."'");
	}

	$result      = $db -> getRow("SELECT * FROM {$sqlname}smtp WHERE identity = '".$identity."' AND tip = 'send'");
	$active      = $result['active'];
	$smtp_host   = $result['smtp_host'];
	$smtp_port   = $result['smtp_port'];
	$smtp_auth   = $result['smtp_auth'];
	$smtp_secure = $result['smtp_secure'];
	$smtp_from   = $result['smtp_from'];
	$smtp_user   = rij_decrypt($result['smtp_user'], $skey, $ivc);
	$smtp_pass   = rij_decrypt($result['smtp_pass'], $skey, $ivc);
	$charset     = $result['name'];

	if (!$charset) {
		$charset = 'utf-8';
	}

	//получим данные сервера smtp для подключения
	$mail = new PHPMailer();

	if ($active == 'yes') {

		$mail -> IsSMTP();
		$mail -> SMTPAuth   = $smtp_auth;
		$mail -> SMTPSecure = $smtp_secure;
		$mail -> Host       = $smtp_host;
		$mail -> Port       = $smtp_port;
		$mail -> Username   = $smtp_user;
		$mail -> Password   = $smtp_pass;
		//$mail -> SMTPDebug = 0;
		$mail -> SMTPOptions = [
			'ssl' => [
				'verify_peer'       => false,
				'verify_peer_name'  => false,
				'allow_self_signed' => true
			]
		];

		$mailData['from'] = $smtp_from;

	}
	else {
		$mail -> isSendmail();
		$charset = 'utf-8';
	}

	if ($charset != 'utf-8') {

		$mailData['fromname'] = iconv("utf-8", $charset, $mailData['fromname']);
		$mailData['toname']   = iconv("utf-8", $charset, $mailData['toname']);
		$mailData['subject']  = iconv("utf-8", $charset, $mailData['subject']);
		$mailData['html']     = iconv("utf-8", $charset, $mailData['html']);

	}

	$mail -> CharSet = $charset;
	$mail -> setLanguage('ru', $prefix.'/vendor/phpmailer/phpmailer/language/');
	$mail -> IsHTML(true);
	$mail -> SetFrom($mailData['from'], $mailData['fromname']);


	$mail -> AddAddress($mailData['to'], $mailData['toname']);

	foreach ((array)$mailData['cc'] as $c) {

		$mail -> AddCC($c['email'], $c['name']);

	}

	foreach ((array)$mailData['bcc'] as $c) {

		$mail -> AddBCC($c['email'], $c['name']);

	}

	$mail -> Subject = $mailData['subject'];
	$mail -> Body    = $mailData['html'];

	//$files - переданный массив
	if (!empty((array)$mailData['files'])) {

		foreach ($mailData['files'] as $f) {

			if ($charset != 'utf-8') {
				$f['name'] = iconv("utf-8", $charset, $f['name']);
			}

			$file = $prefix."/files/".$fpath.$f['file'];
			$mail -> AddAttachment($file, $f['name']);

		}

	}


	$mailsender_rez = ( !$mail -> Send() ) ? 'Ошибка: '.$mail -> ErrorInfo : '';

	$mail -> ClearAddresses();
	$mail -> ClearAttachments();
	$mail -> IsHTML(false);

	return $mailsender_rez;

}

/**
 * Для работы с календарем
 *
 * @param $date_orig
 *
 * @return false|int
 * @category Core
 * @package  Func
 */
function getTimestamp($date_orig) {

	$tzone = $GLOBALS['tzone'];

	$date_orig = explode(" ", $date_orig);
	$datum     = explode("-", $date_orig[0]);
	$time      = explode(":", $date_orig[1]);

	return mktime($time[0], $time[1], (int)$time[2] + (int)$tzone, (int)$datum[1], (int)$datum[2], (int)$datum[0]);

}

/**
 * Отправка календаря по почте
 * array $params - [$to, $toname, $from, $fromname, $subject, $html, $ical, $file]
 *
 * @param array $params
 * @return string
 * @throws Exception
 * @category Core
 * @package  Func
 */
function mailCal(array $params): string {

	$rootpath = dirname(__DIR__);

	global $mailsender_rez;

	//$to, $toname = '', $from, $fromname = '', $subject, $html, $ical, $file

	$mailData = array_combine([
		'to',
		'toname',
		'from',
		'fromname',
		'subject',
		'html',
		'ical',
		'file'
	], $params);

	$db       = $GLOBALS['db'];
	$fpath    = $GLOBALS['fpath'];
	$isCloud  = $GLOBALS['isCloud'];
	$skey     = $GLOBALS['skey'];
	$ivc      = $GLOBALS['ivc'];
	$identity = $GLOBALS['identity'];
	$sqlname  = $GLOBALS['sqlname'];

	//для внешних запросов требуется определить переменную прямо в функции, иначе ошибка
	if (!$skey) {
		$skey = 'vanilla'.( ( $identity + 7 ) ** 3 ).'round'.( ( $identity + 3 ) ** 2 ).'robin';
	}
	if (!$ivc) {
		$ivc = $db -> getOne("SELECT ivc FROM {$sqlname}settings WHERE id = '$identity'");
	}

	$result      = $db -> getRow("SELECT * FROM {$sqlname}smtp WHERE identity = '$identity' AND tip = 'send'");
	$active      = $result['active'];
	$smtp_host   = $result['smtp_host'];
	$smtp_port   = $result['smtp_port'];
	$smtp_auth   = $result['smtp_auth'];
	$smtp_secure = $result['smtp_secure'];
	$smtp_from   = $result['smtp_from'];
	$smtp_user   = rij_decrypt($result['smtp_user'], $skey, $ivc);
	$smtp_pass   = rij_decrypt($result['smtp_pass'], $skey, $ivc);
	$charset     = $result['name'];

	if (!$charset) {
		$charset = 'utf-8';
	}

	//получим данные сервера smtp для подключения
	$mail = new PHPMailer();

	if ($active == 'yes') {

		$mail -> IsSMTP();
		$mail -> SMTPAuth   = $smtp_auth;
		$mail -> SMTPSecure = $smtp_secure;
		$mail -> Host       = $smtp_host;
		$mail -> Port       = $smtp_port;
		$mail -> Username   = $smtp_user;
		$mail -> Password   = $smtp_pass;
		//$mail->SMTPDebug  = 1;
		$mail -> SMTPOptions = [
			'ssl' => [
				'verify_peer'       => false,
				'verify_peer_name'  => false,
				'allow_self_signed' => true
			]
		];

		$mailData['from'] = $smtp_from;

	}
	else {
		$mail -> isSendmail();
		$charset = 'utf-8';
	}

	if ($charset != 'utf-8') {

		$mailData['fromname'] = iconv("utf-8", $charset, $mailData['fromname']);
		$mailData['toname']   = iconv("utf-8", $charset, $mailData['toname']);
		$mailData['subject']  = iconv("utf-8", $charset, $mailData['subject']);
		$mailData['html']     = iconv("utf-8", $charset, $mailData['html']);

	}

	$mail -> CharSet = $charset;
	$mail -> setLanguage('ru', $rootpath.'/vendor/phpmailer/phpmailer/language/');
	$mail -> IsHTML(false);
	$mail -> SetFrom($mailData['from'], $mailData['fromname']);
	$mail -> AddAddress($mailData['to'], $mailData['toname']);

	$mail -> Subject = $mailData['subject'];

	$mail -> Body    = $mailData['html'];
	$mail -> AltBody = html2text($mailData['html']);

	$mail -> addAttachment($mailData['file'], "invite.ics");

	$mail -> AddStringAttachment($mailData['ical'], "meeting.ics", "8bit", "text/calendar;charset=$charset;method=REQUEST");

	$mailsender_rez = ( !$mail -> Send() ) ? 'Ошибка: '.$mail -> ErrorInfo : '';

	$mail -> ClearAddresses();
	$mail -> ClearAttachments();
	$mail -> IsHTML(false);

	return $mailsender_rez;

}

/**
 * Конвертация текста в UTF-8
 *
 * @param $txt
 * @return string
 * @category Core
 * @package  Func
 */
function enc_detect($txt): string {

	$text = texttosmall($txt);

	if (mb_check_encoding($text, 'windows-1251')) {
		$txt = iconv("windows-1251", "utf-8", $txt);
	}

	elseif (mb_check_encoding($text, 'koi8-r')) {
		$txt = iconv("koi8-r", "utf-8", $txt);
	}

	return $txt;
}

/**
 * Преобразует массив в текст для удобного просмотра. Рекурсивная
 *
 * @param        $data - массив
 * @param string $end - символ перевода строки
 * @param string $probel - символ пробела
 * @param int $level - внутренний параметр для рекурсии
 *
 * @return string
 * @category Core
 * @package  Func
 */
function array2string($data, string $end = "\n", string $probel = "\t", int $level = 0): string {

	$log_a = "";
	$level++;
	foreach ($data as $key => $value) {

		if (is_array($value)) {
			$log_a .= str_repeat($probel, $level)."[ ".$key." ] => ($end ".array2string($value, $end, $probel, $level).str_repeat($probel, $level)." )".$end;
		}
		else {
			$log_a .= str_repeat($probel, $level)."[ ".$key." ] => ".$value.$end;
		}

	}
	$level--;

	return $log_a;

}

/**
 * Indents a flat JSON string to make it more human-readable.
 *
 * @param string $json The original JSON string to process.
 * @param string $indentStr The string used for indenting nested structures. Defaults to 4 spaces.
 *
 * @return string Indented version of the original JSON string.
 * @category Core
 * @package  Func
 */
function pretty_json(string $json, string $indentStr = '    '): string {

	$result         = '';
	$level          = 0;
	$strLen         = strlen($json);
	$newLine        = "\n";
	$prevChar       = '';
	$outOfQuotes    = true;
	$openingBracket = false;

	for ($i = 0; $i <= $strLen; $i++) {

		// Grab the next character in the string.
		$char = $json[$i];

		// Add spaces before and after :
		if (( $char == ':' && $prevChar != ' ' ) || ( $prevChar == ':' && $char != ' ' )) {
			if ($outOfQuotes) {
				$result .= ' ';
			}
		}

		// Are we inside a quoted string?
		if ($char == '"' && $prevChar != '\\') {
			$outOfQuotes = !$outOfQuotes;

			// If this character is the end of a non-empty element,
			// output a new line and indent the next line.
		}
		elseif (( $char == '}' || $char == ']' ) && $outOfQuotes) {
			$level--;
			if (!$openingBracket) {
				$result .= $newLine.str_repeat($indentStr, $level);
			}
		}

		// Add the character to the result string.
		$result .= $char;

		// If the last character was the beginning of a non-empty element,
		// output a new line and indent the next line.
		$openingBracket = ( $char == '{' || $char == '[' );
		if (( $char == ',' || $openingBracket ) && $outOfQuotes) {
			if ($openingBracket) {
				$level++;
			}

			$nextChar = $json[$i + 1];
			if (!( $openingBracket && ( $nextChar == '}' || $nextChar == ']' ) )) {
				$result .= $newLine.str_repeat($indentStr, $level);
			}
		}

		$prevChar = $char;
	}

	return $result;

}

/**
 *  функция соединяет массив в строку с проверкой на пустые значения
 *
 * @param string $divider - разделитель массива
 * @param array|string $array - массив для слияния
 * @param string|null $format - оформление значений, можно заключить в кавычки
 * @param string|null $xformat - для тегов добавляем свой закрывающий тэг
 * @return string
 * @category Core
 * @package  Func
 */
function yimplode(string $divider, $array, string $format = NULL, string $xformat = NULL): string {

	$string = '';

	if (!is_null($array)) {

		if (is_array($array)) {

			for ($i = 0, $iMax = count($array); $i < $iMax; $i++) {

				if (!is_array($array[$i])) {

					if (trim($array[$i]) != '') {

						if ($i == 0) {
							$string .= $format.trim($array[$i]).( !is_null($xformat) ? $xformat : $format );
						}
						else {
							$string .= $divider.$format.trim($array[$i]).( !is_null($xformat) ? $xformat : $format );
						}

					}

				}
				else {

					$string .= yimplode($divider, $array[$i], $format, $xformat);

				}

			}
		}
		else {
			$string = $array;
		}

	}

	return $string;

}

/**
 *  функция разбивает строку на массив с проверкой на пустые значения
 *
 * @param string $divider - разделитель массива
 * @param string|null $str - строка для разбиения
 * @param integer $num - индекс элемента массива, который нужно вернуть или возвращает весь массив
 *
 * @return array|string
 * @category Core
 * @package  Func
 */
function yexplode(string $divider, string $str = NULL, int $num = -1) {

	$arr = [];

	if ($str != '') {
		$ar = explode($divider, $str);
		foreach ($ar as $key => $value) {
			if ($value != '') {
				$arr[] = trim($value);
			}
		}
	}

	if ($num >= 0) {
		return $arr[$num];
	}

	return $arr;

}

/**
 * Перемещает элемент массива в новый индекс: $indexFrom -> $move
 *
 * @param array $array - массив для обработки
 * @param int $indexFrom - индекс перемещаемого элемента
 * @param int $move - индекс, который нужно присвоить элементу
 * @return array
 * @category Core
 * @package  Func
 */
function arrayMoveToIndex(array $array = [], int $indexFrom = 0, int $move = 1): array {

	//временная переменная = перемещаемому элементу
	$tmp = $array[$indexFrom];

	//удаляем указанный элемент
	unset($array[$indexFrom]);

	//добавляем его по новому индексу
	array_splice($array, $indexFrom + $move, 0, $tmp);

	return $array;

}

/**
 * сумма элементов массива с именем $element
 *
 * @param array $arr
 * @param string|null $element
 * @param bool $isMoney
 *
 * @return int
 * @category Core
 * @package  Func
 */
function arraysum(array $arr, string $element = NULL, bool $isMoney = false) {

	$summ = 0;

	foreach ($arr as $v) {

		$e = !$isMoney ? $v[$element] : pre_format($v[$element]);

		$summ += $e;

	}

	return $summ;

}

/**
 * Удаление элемента массива по его значению
 *
 * @param array $arr
 * @param string|null $element
 *
 * @return array
 * @category Core
 * @package  Func
 */
function arraydel(array $arr = [], string $element = NULL): array {

	if (( $key = array_search($element, $arr) ) !== false) {
		unset($arr[$key]);
	}

	return $arr;

}

/**
 * Возвращает массив, содержащий изменения в массиве $array2 по отношению к массиву $array1
 *
 * @param $array1
 * @param $array2
 *
 * @return array
 * @category Core
 * @package  Func
 */
function array_diff_ext($array1, $array2): array {

	$differents = [];
	foreach ($array1 as $key => $val) {

		if ($val != $array2[$key]) {
			$differents[$key] = $array2[$key];
		}

	}

	return $differents;
}

/**
 * Максимальное значение элемента массива
 *
 * @param array $arr
 * @param string|null $element
 *
 * @return stdClass
 * @category Core
 * @package  Func
 */
function arrayMax(array $arr = [], string $element = NULL): stdClass {

	$max    = 0;
	$index  = 0;
	$result = new stdClass();

	foreach ($arr as $key => $val) {

		if ($element != '' || !is_null($element)) {

			if ($val[$element] > $max) {

				$max   = $val[$element];
				$index = $key;

			}

		}
		elseif ($val > $max) {

			$max   = $val;
			$index = $key;

		}

	}

	$result -> max   = $max;
	$result -> index = $index;

	return $result;

}

/**
 * Поиск минимального значения в массиве
 *
 * @param array|null $arr
 * @param string $element
 *
 * @return stdClass
 * @category Core
 * @package  Func
 */
function arrayMin(array $arr = NULL, string $element = ''): stdClass {

	$min    = 1000000000;
	$index  = 0;
	$result = new stdClass();

	if (!empty($arr)) {

		foreach ($arr as $key => $val) {

			if (is_array($val) && isset($element) && $val[$element] < $min) {
				$min   = $val[$element];
				$index = $key;
			}
			elseif ($val < $min) {
				$min   = $val;
				$index = $key;
			}

		}

	}

	$result -> min   = $min;
	$result -> index = $index;

	return $result;

}

/**
 * Рекурсивно суммирует значения массива
 *
 * @param       $arr
 * @param float $summa
 *
 * @return float
 * @category Core
 * @package  Func
 */
function arraySumma($arr, float $summa = 0) {

	foreach ($arr as $value) {

		if (!is_array($value)) {
			$summa += $value;
		}
		else {
			$summa += arraySumma($value);
		}

	}

	return $summa;
}

/**
 * Рекурсивно удаляет пустые элементы массива
 * с учетом 0 - если значение = 0, то элемент не будет исключен из массива
 * если = null или пусто, то будет исключен
 * Применимость: очистка массивов для SQL, когда не уверен в том, что параметр не нулевой
 *
 * @param $arr
 *
 * @return mixed
 * @category Core
 * @package  Func
 */
function arrayNullClean($arr) {

	foreach ($arr as $key => $value) {

		if ($value == 0 && $value != '') {
			continue;
		}

		if (is_array($value)) {
			$arr[$key] = arrayNullClean($value);
		}
		elseif (( empty($value) && !is_int($value) )) {
			unset($arr[$key]);
		}

	}

	return $arr;

}

/**
 * Для трехмерных массивов
 * возвращает массив значений с ключем $key
 *
 * @param $array
 * @param $key
 *
 * @return array
 * @category Core
 * @package  Func
 */
function arraySubSearch($array, $key): array {

	$result = [];

	foreach ($array as $item) {
		$result[] = $item[$key];
	}

	return $result;

}

/**
 * Поиск вхождения элементов массива $array в строку $string
 *
 * @param string|null $string $string
 * @param array $array
 *
 * @return bool
 * @category Core
 * @package  Func
 */
function arrayFindInSet(string $string = NULL, array $array = []): bool {

	setlocale(LC_ALL, 'ru_RU.CP1251');

	$count  = 0;
	$string = texttosmall($string);

	foreach ($array as $item) {
		if (str_contains($string, texttosmall($item))) {
			$count++;
		}
	}

	return $count > 0;

}

/**
 * Позволяет добавить новый элемент массива после указанного индекса
 * Решение: https://stackoverflow.com/questions/3353745/how-to-insert-element-into-arrays-at-specific-position
 *
 * @param array $array
 * @param int $after
 * @param array $element
 *
 * @return array
 * @category Core
 * @package  Func
 */
function arrayAddAfter(array $array = [], int $after = 0, array $element = []): array {

	return array_slice($array, 0, $after, true) + $element + array_slice($array, $after, count((array)$array) - 1, true);

}

/**
 * Возвращает первый элемент ассоциативного массива с ключем
 *
 * @param array $array
 *
 * @return array
 * @category Core
 * @package  Func
 */
function arrayShift(array $array = []): array {

	//получаем первый элемент
	$array1 = array_keys($array);
	$k      = array_shift($array1);
	//получаем значение первого элемента
	$v = $array[$k];

	//возвращаем пару ключ = значение
	return [$k => $v];

}

/**
 * Возвращает следующий элемент массива по значению или false - для последнего элемента
 *
 * @param       $value
 * @param array $array
 *
 * @return bool|mixed
 * @category Core
 * @package  Func
 */
function arrayNext($value, array $array = []) {

	$index = false;

	foreach ($array as $k => $v) {

		if ($v == $value) {

			$index = $k + 1;
			goto ex123;

		}

	}

	ex123:

	return ( $index ? $array[$index] : false );

}

/**
 * Позволяет выудить значения следующего и предыдущего элемента для одномерных и двумерных массивов (по ключу $key)
 * Если массив двумерный, то $key обязательно должен быть указан
 *
 * @param             $currentValue - текущее значение
 * @param array $array - массив
 * @param string|null $key - ключ, по которому будет искаться значение в подмассивах
 *
 * @return bool|array
 * @category Core
 * @package  Func
 */
function arrayElements($currentValue, array $array = [], string $key = NULL) {

	if (is_array($array[0]) && $key == '') {

		return false;

	}

	$ar = [];

	if (isset($key) && $key != '' && is_array($array[0])) {

		foreach ($array as $v) {

			$ar[] = $v[$key];

		}

		$array = $ar;

	}

	$index = array_search($currentValue, $array);

	return [
		"current" => $currentValue,
		"next"    => $array[$index + 1],
		"prev"    => $array[$index - 1]
	];

}

/**
 * Возвращает предыдущий элемент по значению или false - для первого элемента
 *
 * @param       $value
 * @param array $array
 *
 * @return bool|mixed
 * @category Core
 * @package  Func
 */
function arrayPrev($value, array $array = []) {

	$index = false;

	foreach ($array as $k => $v) {

		if ($v == $value) {

			$index = $k - 1;
			goto ex124;

		}

	}

	ex124:

	return ( $index ? $array[$index] : false );

}

/**
 * Проверяет значение на нахождение его между двумя значениями
 *
 * @param int|float $val - искомое
 * @param int|float $min - минимальное
 * @param int|float $max - максимальное
 *
 * @return bool
 * @category Core
 * @package  Func
 */
function is_between($val, $min, $max): bool {

	return ( $val >= $min && $val <= $max );

}

/**
 * Замена функции json_encode с поддержкой кириллицы
 *
 * @param $str
 *
 * @return array|false|string|string[]
 * @category Core
 * @package  Func
 */
function json_encode_cyr($str) {

	$arr_replace_utf = [
		'\u0410',
		'\u0430',
		'\u0411',
		'\u0431',
		'\u0412',
		'\u0432',
		'\u0413',
		'\u0433',
		'\u0414',
		'\u0434',
		'\u0415',
		'\u0435',
		'\u0401',
		'\u0451',
		'\u0416',
		'\u0436',
		'\u0417',
		'\u0437',
		'\u0418',
		'\u0438',
		'\u0419',
		'\u0439',
		'\u041a',
		'\u043a',
		'\u041b',
		'\u043b',
		'\u041c',
		'\u043c',
		'\u041d',
		'\u043d',
		'\u041e',
		'\u043e',
		'\u041f',
		'\u043f',
		'\u0420',
		'\u0440',
		'\u0421',
		'\u0441',
		'\u0422',
		'\u0442',
		'\u0423',
		'\u0443',
		'\u0424',
		'\u0444',
		'\u0425',
		'\u0445',
		'\u0426',
		'\u0446',
		'\u0427',
		'\u0447',
		'\u0428',
		'\u0448',
		'\u0429',
		'\u0449',
		'\u042a',
		'\u044a',
		'\u042b',
		'\u044b',
		'\u042c',
		'\u044c',
		'\u042d',
		'\u044d',
		'\u042e',
		'\u044e',
		'\u042f',
		'\u044f'
	];
	$arr_replace_cyr = [
		'А',
		'а',
		'Б',
		'б',
		'В',
		'в',
		'Г',
		'г',
		'Д',
		'д',
		'Е',
		'е',
		'Ё',
		'ё',
		'Ж',
		'ж',
		'З',
		'з',
		'И',
		'и',
		'Й',
		'й',
		'К',
		'к',
		'Л',
		'л',
		'М',
		'м',
		'Н',
		'н',
		'О',
		'о',
		'П',
		'п',
		'Р',
		'р',
		'С',
		'с',
		'Т',
		'т',
		'У',
		'у',
		'Ф',
		'ф',
		'Х',
		'х',
		'Ц',
		'ц',
		'Ч',
		'ч',
		'Ш',
		'ш',
		'Щ',
		'щ',
		'Ъ',
		'ъ',
		'Ы',
		'ы',
		'Ь',
		'ь',
		'Э',
		'э',
		'Ю',
		'ю',
		'Я',
		'я'
	];
	$str1            = json_encode($str);

	return str_replace($arr_replace_utf, $arr_replace_cyr, $str1);

}

/**
 * Обрабатывает строку или массив и удаляет дубли. Возвращает строку или массив
 *
 * @param string | array $string
 * @param string $divider
 * @param boolean $isarray
 *
 * @return string | array
 * @category Core
 * @package  Func
 */
function prepareStringSmart($string, string $divider = ",", bool $isarray = false) {

	$string = ( !is_array($string) ) ? yexplode($divider, $string) : $string;

	foreach ($string as $k => $item) {

		$string[$k] = trim($item);

	}

	$new = array_unique($string);

	if (!$isarray) {
		return implode($divider." ", $new);
	}

	return $new;

}

/**
 * Сокращает ФИО до ФИ
 *
 * @param string|null $string $string
 *
 * @return string
 * @category Core
 * @package  Func
 */
function toShort(string $string = NULL): string {

	$p = yexplode(" ", (string)$string);

	return $p[0]." ".$p[1];

}

/**
 * выдает текущую дату или дату, смещенную на минус Х дней от текущей
 *
 * @param int|null $day
 *
 * @return false|string
 * @category Core
 * @package  Func
 */
function current_datum(int $day = NULL) {

	$tzone = $GLOBALS['tzone'];

	return date("Y-m-d", mktime((int)date('H'), (int)date('i'), (int)date('s'), (int)date('m'), (int)date('d') - (int)$day, (int)date('Y')) + $tzone * 3600);

}

/**
 * выдает текущую дату+время
 * если заданы параметры, то со смещением минус Х часов, Х минут
 *
 * @param int|null $hours
 * @param int|null $minutes
 *
 * @return false|string
 * @category Core
 * @package  Func
 */
function current_datumtime(int $hours = NULL, int $minutes = NULL) {

	$tzone = $GLOBALS['tzone'];

	if (!$hours) {
		$hours = 0;
	}
	if (!$minutes) {
		$minutes = 0;
	}

	return date('Y-m-d H:i:s', mktime((int)date('H') - $hours, (int)date('i') - $minutes, (int)date('s'), (int)date('m'), (int)date('d'), (int)date('Y')) + $tzone * 3600);

}

/**
 * рассчитывает количество дней между нужной датой и текущей
 *
 * @param $datum
 *
 * @return float
 * @throws \Exception
 * @package  Func
 * @category Core
 */
function datestoday($datum): float {

	$tzone = $GLOBALS['tzone'];

	$today = mktime((int)date('H'), (int)date('i'), (int)date('s'), (int)date('m'), (int)date('d'), (int)date('Y')) + $tzone * 3600;

	return round(( date_to_unix($datum) - $today ) / 86400);

}

/**
 * рассчитывает количество дней между нужной датой и текущей
 *
 * @param $datum
 *
 * @return float|string
 * @throws \Exception
 * @package  Func
 * @category Core
 */
function datetimetoday($datum) {

	$datum = explode(" ", (string)$datum);
	$datum = $datum[0];

	if ($datum != '0000-00-00') {

		$today = mktime((int)date('H'), (int)date('i'), (int)date('s'), (int)date('m'), (int)date('d'), (int)date('Y'));

		return round(( date_to_unix($datum) - $today ) / 86400);

	}

	return "-";

}

/**
 * рассчитывает разницу в часах между 2-х дат-время
 *
 * @param $date_orig - datetime
 *
 * @return float
 * @category Core
 * @package  Func
 */
function difftime($date_orig): float {

	global $userRights, $tzone;

	//$tzone     = $GLOBALS['tzone'];
	//$ac_import = $GLOBALS['ac_import'];

	$date_orig = explode(" ", $date_orig);
	$datum     = explode("-", $date_orig[0]);
	$time      = explode(":", $date_orig[1]);

	$date_zone = mktime((int)$time[0], (int)$time[1], (int)$time[2], (int)$datum[1], (int)$datum[2], (int)$datum[0]) + (int)$tzone * 3600;

	//текущее время
	$today = mktime((int)date('H'), (int)date('i'), (int)date('s'), (int)date('m'), (int)date('d'), (int)date('Y')) + (int)$tzone * 3600;

	//кол-во часов разницы
	$hours = abs(round(( $date_zone - $today ) / 3600, 2));

	//если функция блокировки редактирования выключена, то ставим принудительно 2 часа, чтобы можно было редактировать
	if ($userRights['changetask']) {
		$hours = 0.5;
	}

	return $hours;

}

/**
 * кол-во часов разницы между двух дат
 *
 * @param $date_orig - datetime
 *
 * @return float
 * @category Core
 * @package  Func
 */
function difftimefull($date_orig): float {

	$tmzone = $GLOBALS['settingsApp']['tmzone'];

	if ($tmzone == '') {
		$tmzone = 'Europe/Moscow';
	}

	date_default_timezone_set($tmzone);

	$tzone = $GLOBALS['tzone'];

	$date_orig = explode(" ", $date_orig);
	$datum     = explode("-", $date_orig[0]);
	$time      = explode(":", $date_orig[1]);

	$date_zone = mktime((int)$time[0], (int)$time[1], (int)$time[2], (int)$datum[1], (int)$datum[2], (int)$datum[0]) + (int)$tzone * 3600;

	//текущее время
	$today = mktime((int)date('H'), (int)date('i'), (int)date('s'), (int)date('m'), (int)date('d'), (int)date('Y')) + $tzone * 3600;

	//кол-во часов разницы

	return round(( $date_zone - $today ) / 3600, 2);

}

/**
 * кол-во дней разницы между двух дат с учетом времени
 * если второй параметр не задан, то он принимается как текущая дата-время
 *
 * @param string|null $date_1 - datetime
 * @param string|null $date_2 - datetime
 *
 * @return float|int
 * @category Core
 * @package  Func
 */
function diffDate(string $date_1 = NULL, string $date_2 = NULL) {

	$tmzone = $GLOBALS['settingsApp']['tmzone'];

	if ($tmzone == '') {
		$tmzone = 'Europe/Moscow';
	}

	date_default_timezone_set($tmzone);

	$delta = 0;

	if (empty($date_2)) {
		$date_2 = current_datumtime();
	}

	if (!empty($date_1) && !empty($date_2)) {

		$xdate_1 = explode(" ", $date_1);
		$datum_1 = explode("-", $xdate_1[0]);
		$time_1  = explode(":", $xdate_1[1]);

		$date_22 = explode(" ", $date_2);
		$datum_2 = explode("-", $date_22[0]);
		$time_2  = explode(":", $date_22[1]);

		$date_10 = mktime((int)$time_1[0], (int)$time_1[1], (int)$time_1[2], (int)$datum_1[1], (int)$datum_1[2], (int)$datum_1[0]);
		$date_20 = mktime((int)$time_2[0], (int)$time_2[1], (int)$time_2[2], (int)$datum_2[1], (int)$datum_2[2], (int)$datum_2[0]);

		//кол-во дней разницы
		$delta = round(( $date_20 - $date_10 ) / 86400, 0);

	}

	return $delta;

}

/**
 * кол-во дней разницы между двух дат без учета времени
 * если второй параметр не задан, то он принимается как текущая дата-время
 *
 * @param  $date_1 - date
 * @param string|null $date_2 - date
 *
 * @return float|int
 * @category Core
 * @package  Func
 */
function diffDate2($date_1, string $date_2 = NULL) {

	$tmzone = $GLOBALS['settingsApp']['tmzone'];

	if ($tmzone == '') {
		$tmzone = 'Europe/Moscow';
	}

	date_default_timezone_set($tmzone);

	$delta = 0;

	if (empty($date_2)) {
		//текущее время
		$date_2 = current_datum();
	}
	if (!empty($date_1) && !empty($date_2)) {

		$datum1 = explode("-", $date_1);
		$datum2 = explode("-", $date_2);

		$date_10 = mktime(1, 0, 0, (int)$datum1[1], (int)$datum1[2], (int)$datum1[0]);
		$date_20 = mktime(1, 0, 0, (int)$datum2[1], (int)$datum2[2], (int)$datum2[0]);

		$delta = ( $date_10 - $date_20 ) / 86400;//кол-во дней разницы
	}

	return $delta;

}

/**
 * Возвращает количество пройденного времени с текущего момента, либо между двух дат
 * Используется в комментариях и пр.
 * Округляет до минут, либо часов, либо дней
 *
 * @param  $date_1 - datetime
 * @param string|null $date_2 - datetime
 *
 * @return int|string
 * @category Core
 * @package  Func
 */
function diffDateTime($date_1, string $date_2 = NULL) {

	$tmzone = $GLOBALS['settingsApp']['tmzone'];

	if ($tmzone == '') {
		$tmzone = 'Europe/Moscow';
	}

	date_default_timezone_set($tmzone);

	$diff = 0;

	if (empty($date_2)) {

		$date_2 = date('Y-m-d H:i:s', mktime((int)date('H'), (int)date('i'), (int)date('s'), (int)date('m'), (int)date('d'), (int)date('Y')));
		//$date_2 = current_datumtime();

	}

	if (!empty($date_1) && !empty($date_2)) {

		$date_1 = explode(" ", $date_1);

		$datum_1 = explode("-", $date_1[0]);
		$time_1  = explode(":", $date_1[1]);

		$date_22 = explode(" ", $date_2);

		$datum_2 = explode("-", $date_22[0]);
		$time_2  = explode(":", $date_22[1]);

		$date_10 = mktime((int)$time_1[0], (int)$time_1[1], (int)$time_1[2], (int)$datum_1[1], (int)$datum_1[2], (int)$datum_1[0]);// + $tzone*3600;
		$date_20 = mktime((int)$time_2[0], (int)$time_2[1], (int)$time_2[2], (int)$datum_2[1], (int)$datum_2[2], (int)$datum_2[0]);

		$minutes = abs(round(( $date_20 - $date_10 ) / 60, 0)); //разница в минутах

		if ($minutes == 0) {
			$diff = '< <b>1</b> мин.';
		}

		//минут разницы
		elseif ($minutes <= 60) {
			$diff = '<b>'.$minutes.'</b> мин.';
		}

		elseif ($minutes <= 3600) {

			$delta = abs(round(( $date_20 - $date_10 ) / 3600, 0));

			//часов разницы
			if ($delta <= 24) {

				$diff = '<b>'.$delta.'</b> час.';

			}
			else {

				$delta = abs(round(( $date_20 - $date_10 ) / 86400, 0));
				$diff  = '<b>'.$delta.'</b> дн.';

			}

		}
		else {

			$delta = abs(round(( $date_20 - $date_10 ) / 86400, 0));
			$diff  = '<b>'.$delta.'</b> дн.';//кол-во дней разницы

		}

	}

	return $diff;

}

/**
 * Возвращает количество пройденного времени с текущего момента, либо между двух дат
 * Используется в комментариях и пр.
 * Округляет до минут, либо часов + минут, либо дней
 *
 * @param        $date_1
 * @param string|null $date_2
 * @param bool $format = false - возвращать разницу в секундах
 *
 * @return int|string
 * @category Core
 * @package  Func
 */
function diffDateTime2($date_1, string $date_2 = NULL, bool $format = true) {

	$tmzone = $GLOBALS['settingsApp']['tmzone'];

	if ($tmzone == '') {
		$tmzone = 'Europe/Moscow';
	}

	date_default_timezone_set($tmzone);

	$diff = 0;

	if (empty($date_2)) {

		$date_2 = date('Y-m-d H:i:s', mktime((int)date('H'), (int)date('i'), (int)date('s'), (int)date('m'), (int)date('d'), (int)date('Y')));

	}

	if (!empty($date_1) && !empty($date_2)) {

		$date_1  = explode(" ", $date_1);
		$datum_1 = explode("-", $date_1[0]);
		$time_1  = explode(":", $date_1[1]);

		$date_22 = explode(" ", $date_2);
		$datum_2 = explode("-", $date_22[0]);
		$time_2  = explode(":", $date_22[1]);

		$date_10 = mktime((int)$time_1[0], (int)$time_1[1], (int)$time_1[2], (int)$datum_1[1], (int)$datum_1[2], (int)$datum_1[0]);// + $GLOBALS['tmzone']*3600;
		$date_20 = mktime((int)$time_2[0], (int)$time_2[1], (int)$time_2[2], (int)$datum_2[1], (int)$datum_2[2], (int)$datum_2[0]);

		$minutes = abs(round(( $date_20 - $date_10 ) / 60, 0)); //разница в минутах

		if ($format) {

			if ($minutes == 0) {
				$diff = '< <b>1</b> мин.';
			}
			//минут разницы
			elseif ($minutes <= 60) {

				$diff = '<b>'.$minutes.'</b> мин.';

			}
			elseif ($minutes <= 3600) {

				$delta = abs(round(( $date_20 - $date_10 ) / 3600 - 0.5, 0));
				if ($delta <= 24) {

					$min = $minutes - $delta * 60;

					if ($min < 10) {
						$min = '0'.$min;
					}
					$diff = '<b>'.$delta.'</b> ч. '.$min.'м.';//часов разницы

				}
				else {

					$delta = abs(round(( $date_20 - $date_10 ) / 86400, 0));
					$diff  = '<b>'.$delta.'</b> дн.';

				}

			}
			//кол-во дней разницы
			else {

				$delta = abs(round(( $date_20 - $date_10 ) / 86400, 0));
				$diff  = '<b>'.$delta.'</b> дн.';

			}

		}
		else {
			$diff = ( $date_20 - $date_10 );
		}

	}

	return $diff;

}

/**
 * Возвращает число дней, между двух дат
 * @param $date_1
 * @param $date_2
 * @param bool $format = false - возвращать разницу в секундах
 *
 * @return int|string
 * @category Core
 * @package  Func
 */
function diffDateTime3($date_1, $date_2 = NULL, bool $format = true) {

	$tmzone = $GLOBALS['settingsApp']['tmzone'];

	if ($tmzone == '') {
		$tmzone = 'Europe/Moscow';
	}

	date_default_timezone_set($tmzone);

	$diff = 0;

	if (empty($date_2)) {

		$date_2 = date('Y-m-d H:i:s', mktime((int)date('H'), (int)date('i'), (int)date('s'), (int)date('m'), (int)date('d'), (int)date('Y')));

	}

	if (!empty($date_1) && !empty($date_2)) {

		$date_1  = explode(" ", $date_1);
		$datum_1 = explode("-", $date_1[0]);
		$time_1  = explode(":", $date_1[1]);

		$date_22 = explode(" ", $date_2);
		$datum_2 = explode("-", $date_22[0]);
		$time_2  = explode(":", $date_22[1]);

		$date_10 = mktime((int)$time_1[0], (int)$time_1[1], (int)$time_1[2], (int)$datum_1[1], (int)$datum_1[2], (int)$datum_1[0]);// + $GLOBALS['tmzone']*3600;
		$date_20 = mktime((int)$time_2[0], (int)$time_2[1], (int)$time_2[2], (int)$datum_2[1], (int)$datum_2[2], (int)$datum_2[0]);

		$minutes = round(( $date_20 - $date_10 ) / 60, 0); //разница в минутах

		if ($format) {

			if ($minutes == 0) {
				$diff = '< <b>1</b> мин.';
			}
			elseif (abs($minutes) <= 60) {
				$diff = '<b>'.$minutes.'</b> мин.';//минут разницы
			}
			elseif (abs($minutes) <= 3600) {

				$delta = round(( $date_20 - $date_10 ) / 3600 - 0.5, 0);

				if (abs($delta) <= 24) {

					$min = $minutes - $delta * 60;

					if ($min < 10) {
						$min = '0'.$min;
					}

					$diff = '<b>'.$delta.'</b> ч. '.$min.'м.';//часов разницы

				}
				else {
					$delta = round(( $date_20 - $date_10 ) / 86400, 0);
					$diff  = '<b>'.$delta.'</b> дн.';
				}

			}
			else {

				$delta = round(( $date_20 - $date_10 ) / 86400, 0);
				$diff  = '<b>'.$delta.'</b> дн.';//кол-во дней разницы

			}

		}
		else {
			$diff = ( $date_20 - $date_10 );
		}

	}

	return $diff;

}

/**
 * Разница в секундах
 *
 * @param        $date_1
 * @param string|null $date_2
 *
 * @return float|int
 * @category Core
 * @package  Func
 */
function diffDateTimeSeq($date_1, string $date_2 = NULL) {

	$tmzone = $GLOBALS['settingsApp']['tmzone'];

	if ($tmzone == '') {
		$tmzone = 'Europe/Moscow';
	}

	date_default_timezone_set($tmzone);

	if (empty($date_2)) {

		$date_2 = date('Y-m-d H:i:s', mktime((int)date('H'), (int)date('i'), (int)date('s'), (int)date('m'), (int)date('d'), (int)date('Y')));

	}

	if (!empty($date_1) && !empty($date_2)) {

		$date_1  = explode(" ", $date_1);
		$datum_1 = explode("-", $date_1[0]);
		$time_1  = explode(":", $date_1[1]);

		$date_22 = explode(" ", $date_2);
		$datum_2 = explode("-", $date_22[0]);
		$time_2  = explode(":", $date_22[1]);

		$date_10 = mktime((int)$time_1[0], (int)$time_1[1], (int)$time_1[2], (int)$datum_1[1], (int)$datum_1[2], (int)$datum_1[0]);// + $GLOBALS['tmzone']*3600;
		$date_20 = mktime((int)$time_2[0], (int)$time_2[1], (int)$time_2[2], (int)$datum_2[1], (int)$datum_2[2], (int)$datum_2[0]);

		//разница в секундах
		return abs($date_20 - $date_10);

	}

	return 0;

}

/**
 * Возвращает первый (first) или послдений день месяца для выбранной даты
 *
 * @param        $date
 * @param string $type
 *
 * @return string
 * @category Core
 * @package  Func
 */
function monthData($date, string $type = 'first'): string {

	//получаем массив для даты
	$d = getDateTimeArray($date);

	//число дней в месяце
	$dsf = (int)date("t", mktime(1, 0, 0, (int)$d['m'], 1, (int)$d['Y']));

	return ( $type == 'first' ) ? strftime('%Y-%m-%d', mktime(1, 0, 0, (int)$d['m'], 1, (int)$d['Y'])) : strftime('%Y-%m-%d', mktime(1, 0, 0, $d['m'], $dsf, $d['Y']));

}

/**
 * преобразует datetime в date с учетом смещения времени пользователя
 *
 * @param $date_orig
 *
 * @return false|string
 * @category Core
 * @package  Func
 */
function cut_date($date_orig) {

	//require_once "config.php";

	$tmzone = $GLOBALS['settingsApp']['tmzone'];

	if ($tmzone == '') {
		$tmzone = 'Europe/Moscow';
	}

	date_default_timezone_set($tmzone);

	$tzone = $GLOBALS['tzone'];

	$date_orig = explode(" ", $date_orig);
	$datum     = explode("-", $date_orig[0]);
	$time      = explode(":", $date_orig[1]);

	return date("Y-m-d", mktime((int)$time[0], (int)$time[1], (int)$time[2], (int)$datum[1], (int)$datum[2], (int)$datum[0]) + $tzone * 3600);

}

/**
 * преобразует datetime в date без учета смещения времени пользователя
 *
 * @param $date_orig
 *
 * @return false|string
 * @category Core
 * @package  Func
 */
function cut_date_short($date_orig) {

	$date_orig = explode(" ", $date_orig);
	$datum     = explode("-", $date_orig[0]);
	$time      = explode(":", $date_orig[1]);

	return date("Y-m-d", mktime((int)$time[0], (int)$time[1], (int)$time[2], (int)$datum[1], (int)$datum[2], (int)$datum[0]));
}

/**
 * Возвращает дату, увеличенную на $range дней
 *
 * @param     $date
 * @param int $range
 *
 * @return string
 * @throws \Exception
 * @package  Func
 * @category Core
 */
function addDateRange($date, int $range = 0): string {

	return unix_to_date(date_to_unix($date) + $range * 3600 * 24);

}

/**
 * Возвращает отформатированное значение приоритета/срочности для напоминаний
 *
 * @param $tip
 * @param $num
 *
 * @return string
 * @category Core
 * @package  Func
 */
function get_priority($tip, $num): string {

	$pr = '';

	if ($tip == 'priority') {

		$title    = "Приоритет";
		$subtitle = [
			'Важно',
			'Обычный',
			'Не важно'
		];

		switch ((int)$num) {

			case 2:
				$pr = '<b title="'.$title.' - '.$subtitle[0].'" class="red">&uArr;</b>';
				break;
			case 0:
				$pr = '<b title="'.$title.' - '.$subtitle[1].'" class="blue">&#9632;</b>';
				break;
			case 1:
				$pr = '<b title="'.$title.' - '.$subtitle[2].'" class="green">&dArr;</b>';
				break;
			default:
				$pr = '<b title="'.$title.' - Не отмечено">&#9632;</b>';
				break;

		}

	}
	if ($tip == 'speed') {

		$title    = "Срочность";
		$subtitle = [
			'Срочно',
			'Обычный',
			'Не срочно'
		];

		switch ((int)$num) {
			case 2:
				$pr = '<b title="'.$title.' - '.$subtitle[0].'" class="red">&uArr;</b>&nbsp;';
				break;
			case 0:
				$pr = '<b title="'.$title.' - '.$subtitle[1].'" class="blue">&#9632;</b>&nbsp;';
				break;
			case 1:
				$pr = '<b title="'.$title.' - '.$subtitle[2].'" class="green">&dArr;</b>&nbsp;';
				break;
			default:
				$pr = '<b title="'.$title.' - Не отмечено">&#9632;</b>&nbsp;';
				break;
		}

	}

	return $pr;

}

/**
 * Возвращает текстовое значение приоритета/срочности для напоминаний
 *
 * @param $tip
 * @param $num
 * @return string
 * @category Core
 * @package  Func
 */
function getPriority($tip, $num): string {

	$pr = '';

	if ($tip == 'priority') {

		$title    = "Приоритет";
		$subtitle = [
			'Важно',
			'Обычный',
			'Не важно'
		];

		switch ((int)$num) {

			case 2:
				$pr = $subtitle[0];
				break;
			case 0:
				$pr = $subtitle[1];
				break;
			case 1:
				$pr = $subtitle[2];
				break;
			default:
				$pr = 'Не отмечено';
				break;

		}

	}
	if ($tip == 'speed') {

		$title    = "Срочность";
		$subtitle = [
			'Срочно',
			'Обычный',
			'Не срочно'
		];

		switch ($num) {
			case 2:
				$pr = $subtitle[0];
				break;
			case 0:
				$pr = $subtitle[1];
				break;
			case 1:
				$pr = $subtitle[2];
				break;
			default:
				$pr = 'Не отмечено';
				break;
		}

	}

	return $pr;

}

/**
 * Возвращает отформатированное значение приоритета/срочности для напоминаний
 *
 * @param $tip
 * @param $num
 *
 * @return string
 * @category Core
 * @package  Func
 */
function get_priority2($tip, $num): string {

	$pr = '';

	if ($tip == 'priority') {

		$title    = "Приоритет";
		$subtitle = [
			'Важно',
			'Обычный',
			'Не важно'
		];

		switch ((int)$num) {
			case 2:
				$pr = '<b title="'.$title.' - '.$subtitle[0].'">&uArr;</b>';
				break;
			case 0:
				$pr = '<b title="'.$title.' - '.$subtitle[1].'">&#9632;</b>';
				break;
			case 1:
				$pr = '<b title="'.$title.' - '.$subtitle[2].'">&dArr;</b>';
				break;
			default:
				$pr = '<b title="'.$title.' - Не отмечено">&#9632;</b>';
				break;
		}

	}
	if ($tip == 'speed') {

		$title    = "Срочность";
		$subtitle = [
			'Срочно',
			'Обычный',
			'Не срочно'
		];

		switch ((int)$num) {
			case 2:
				$pr = '<b title="'.$title.' - '.$subtitle[0].'">&uArr;</b>&nbsp;';
				break;
			case 0:
				$pr = '<b title="'.$title.' - '.$subtitle[1].'">&#9632;</b>&nbsp;';
				break;
			case 1:
				$pr = '<b title="'.$title.' - '.$subtitle[2].'">&dArr;</b>&nbsp;';
				break;
			default:
				$pr = '<b title="'.$title.' - Не отмечено">&#9632;</b>&nbsp;';
				break;
		}

	}

	return $pr;

}

/**
 * Название активности по id
 *
 * @param $id
 *
 * @return string
 * @category Core
 * @package  Func
 */
function current_activities($id): string {

	$identity = $GLOBALS['identity'];
	$sqlname  = $GLOBALS['sqlname'];
	$db       = $GLOBALS['db'];

	return (string)$db -> getOne("SELECT title FROM {$sqlname}activities WHERE id='".$id."' AND identity = '".$identity."'");

}

/**
 * логгер изменений в записях
 *
 * @param     $type
 * @param     $content
 * @param int $user
 *
 * @return bool
 * @category Core
 * @package  Func
 */
function logger($type, $content, int $user = 0): bool {

	$identity = $GLOBALS['identity'];
	$sqlname  = $GLOBALS['sqlname'];
	$db       = $GLOBALS['db'];

	$ltype = [
		0  => "Авторизация",
		1  => "Выход",
		2  => "Администрирование",
		3  => "Экспорт организаций",
		4  => "Экспорт персон",
		5  => "Экспорт сделок",
		6  => "Импорт организаций",
		7  => "Импорт прайса",
		8  => "Скачивание БД",
		9  => "Начало дня",
		10 => "Удаление организации",
		11 => "Удаление персоны",
		12 => "Удаление сделки",
		13 => "Восстановление пароля",
		14 => "Импорт Входящего интереса",
		15 => "Восстановление пароля"
	];

	$current_type = $ltype[$type];

	if ($user > 0) {
		$db -> query("INSERT INTO {$sqlname}logs (id, type, iduser, content, identity) VALUES(NULL, '".$current_type."', '".$user."', '".$content."', '".$identity."')");
	}

	return true;

}

/**
 * Проверка каталога на соответствие правам
 *
 * @param $dir
 * @param $chm
 *
 * @return string
 * @category Core
 * @package  Func
 */
function end_chmod($dir, $chm): string {

	$d = '';

	if (file_exists($dir) && isset($chm)) {

		$pdir = decoct(fileperms($dir));
		$per  = substr($pdir, -3);
		if ($per != $chm) {
			$d = $dir." Не соответствуют права CHMOD - ".$chm;
		}

	}

	return $d;

}

/**
 * Проверка каталога на соответствие правам
 *
 * @param $dir
 *
 * @return string
 * @category Core
 * @package  Func
 */
function getPerms($dir): string {

	$str = '';

	if (file_exists($dir)) {

		if (!is_writable($dir)) {
			$str = '<i class="icon-attention red" title="Проверьте права на запись папки: '.str_replace("../", "", $dir).'. Они должны быть 0777"></i>';
		}
		else {
			$str = '<i class="icon-thumbs-up-alt green list" title="Папка '.str_replace("../", "", $dir).' имеет права на запись."></i>';
		}

	}

	return $str;

}

function getChmod($dir): string {

	$per = 'Not Found';

	if (file_exists($dir)) {

		//print substr(sprintf('%o', fileperms($dir)), -4)."<br>";

		/*
		$pdir = decoct(fileperms($dir));
		$per  = substr($pdir, -3);
		*/

		$per = substr(sprintf('%o', fileperms($dir)), -4);

	}

	return $per;

}

/**
 * Еще одно форматирование номеров телефона
 *
 * @param $phone
 * @return string|string[]|null
 * @category Core
 * @package  Func
 */
function eformatPhone($phone) {

	$phone_number = preg_replace("/[^0-9]/", "", $phone);

	//номера вида 220-2332
	if (strlen($phone_number) == 5) {
		$phone_url = preg_replace("/([0-9]{2})([0-9]{3})/", "$1-$2", $phone_number);
	}

	//номера вида 220-2332
	elseif (strlen($phone_number) == 6) {
		$phone_url = preg_replace("/([0-9]{3})([0-9]{3})/", "$1-$2", $phone_number);
	}

	//номера вида 220-2332
	elseif (strlen($phone_number) == 7) {
		$phone_url = preg_replace("/([0-9]{3})([0-9]{2})([0-9]{2})/", "$1-$2-$3", $phone_number);
	}

	//номера с кодом вида 342 220-2332
	elseif (strlen($phone_number) == 10) {
		$phone_url = preg_replace("/([0-9]{3})([0-9]{3})([0-9]{4})/", "($1) $2-$3", $phone_number);
	}

	//номера с кодом вида 7(8) 342 220-2332
	elseif (strlen($phone_number) == 11) {
		$phone_url = preg_replace("/([0-9]{1})([0-9]{3})([0-9]{3})([0-9]{4})/", "+$1 ($2) $3-$4", $phone_number);
	}

	//номера с кодом вида 38 342 220-2332
	elseif (strlen($phone_number) == 12) {
		$phone_url = preg_replace("/([0-9]{2})([0-9]{3})([0-9]{3})([0-9]{4})/", "+$1 ($2) $3-$4", $phone_number);
	}

	else {
		$phone_url = $phone;
	}

	return $phone_url;

}

/**
 * форматирование номера телефона в вид
 *
 * @param $phone
 *
 * @return mixed
 * @category Core
 * @package  Func
 */
function formatPhone($phone) {

	$identity = $GLOBALS['identity'];
	$sqlname  = $GLOBALS['sqlname'];
	$opts     = $GLOBALS['opts'];

	//unset($db);
	$db = new SafeMySQL($opts);

	$pbx_active = $db -> getOne("SELECT active FROM {$sqlname}sip WHERE identity = '".$identity."'");

	//очистка от символов кроме цифр
	if ($pbx_active == 'yes') {

		$phone_number = preg_replace("/[^0-9]/", "", $phone);

		if (strlen($phone_number) == 5) {//номера вида 220-2332
			$phone_url = preg_replace("/([0-9]{2})([0-9]{3})/", "$1-$2", $phone_number);
		}
		elseif (strlen($phone_number) == 6) {//номера вида 220-2332
			$phone_url = preg_replace("/([0-9]{3})([0-9]{3})/", "$1-$2", $phone_number);
		}
		elseif (strlen($phone_number) == 7) {//номера вида 220-2332
			$phone_url = preg_replace("/([0-9]{3})([0-9]{4})/", "$1-$2", $phone_number);
		}
		elseif (strlen($phone_number) == 10) {//номера с кодом вида 342 220-2332
			$phone_url = preg_replace("/([0-9]{3})([0-9]{3})([0-9]{4})/", "($1)$2-$3", $phone_number);
		}
		elseif (strlen($phone_number) == 11) {//номера с кодом вида 7(8) 342 220-2332
			$phone_url = preg_replace("/([0-9]{3})([0-9]{3})([0-9]{4})/", "($1)$2-$3", $phone_number);
		}
		elseif (strlen($phone_number) == 12) {//номера с кодом вида 38 342 220-2332
			$phone_url = preg_replace("/([0-9]{2})([0-9]{3})([0-9]{3})([0-9]{4})/", "$1 ($2) $3-$4", $phone_number);
		}
		else {
			$phone_url = $phone;
		}

	}
	else {
		$phone_url = $phone;
	}

	return $phone_url;

}

/**
 * форматирование номера телефона в вид
 *
 * @param $phone
 *
 * @return mixed
 * @category Core
 * @package  Func
 */
function formatPhone2($phone) {

	$identity = $GLOBALS['identity'];
	$sqlname  = $GLOBALS['sqlname'];
	$opts     = $GLOBALS['opts'];

	//unset($db);
	$db = new SafeMySQL($opts);

	$pbx_active = $db -> getOne("SELECT active FROM {$sqlname}sip WHERE identity = '".$identity."'");

	//очистка от символов кроме цифр
	if ($pbx_active == 'yes') {

		$phone_number = preg_replace("/[^0-9]/", "", $phone);

		if (strlen($phone_number) == 5) {//номера вида 220-2332
			$phone_url = preg_replace("/([0-9]{2})([0-9]{3})/", "$1-$2", $phone_number);
		}
		elseif (strlen($phone_number) == 6) {//номера вида 220-2332
			$phone_url = preg_replace("/([0-9]{3})([0-9]{3})/", "$1-$2", $phone_number);
		}
		elseif (strlen($phone_number) == 7) {//номера вида 220-2332
			$phone_url = preg_replace("/([0-9]{3})([0-9]{4})/", "$1-$2", $phone_number);
		}
		elseif (strlen($phone_number) == 10) {//номера с кодом вида 342 220-2332
			$phone_url = preg_replace("/([0-9]{3})([0-9]{3})([0-9]{4})/", "($1)$2-$3", $phone_number);
		}
		elseif (strlen($phone_number) == 11) {//номера с кодом вида 7(8) 342 220-2332
			$phone_url = preg_replace("/([0-9]{1})([0-9]{3})([0-9]{3})([0-9]{4})/", "$1($2)$3-$4", $phone_number);
		}
		elseif (strlen($phone_number) == 12) {//номера с кодом вида 38 342 220-2332
			$phone_url = preg_replace("/([0-9]{2})([0-9]{3})([0-9]{3})([0-9]{4})/", "$1($2)$3-$4", $phone_number);
		}
		else {
			$phone_url = $phone;
		}

	}
	else {
		$phone_url = $phone;
	}

	return $phone_url;

}

/**
 * форматирование номера телефона в вид ссылки с учетом интеграции с телефонией
 *
 * @param          $phone
 * @param int|string|null $clid
 * @param int|string|null $pid
 *
 * @return string
 * @category Core
 * @package  Func
 */
function formatPhoneUrl($phone, int $clid = NULL, int $pid = NULL): string {

	$identity = $GLOBALS['identity'];
	$sqlname  = $GLOBALS['sqlname'];
	$db       = $GLOBALS['db'];

	$phone_url = !empty($phone) ? $phone : '';

	//print $phone."<br>";
	//print strlen($phone)."<br>";

	if ($phone != '') {

		$res        = $db -> getRow("SELECT * FROM {$sqlname}sip WHERE identity = '".$identity."'");
		$pbx_active = $res["active"];
		$pbx_tip    = $res["tip"];

		$phone_number = preg_replace("/[^0-9]/", "", $phone);

		if (strlen($phone_number) == 5) {//номера вида 220-2332
			$phone_url = preg_replace("/([0-9]{2})([0-9]{3})/", "$1-$2", $phone_number);
		}
		elseif (strlen($phone_number) == 6) {//номера вида 220-2332
			$phone_url = preg_replace("/([0-9]{3})([0-9]{3})/", "$1-$2", $phone_number);
		}
		elseif (strlen($phone_number) == 7) {//номера вида 220-2332
			$phone_url = preg_replace("/([0-9]{3})([0-9]{4})/", "$1-$2", $phone_number);
		}
		elseif (strlen($phone_number) == 10) {//номера с кодом вида 342 220-2332
			$phone_url = preg_replace("/([0-9]{3})([0-9]{3})([0-9]{4})/", "($1)$2-$3", $phone_number);
		}
		elseif (strlen($phone_number) == 11) {//номера с кодом вида 7(8) 342 220-2332

			$phone_url = preg_replace("/([0-9]{1})([0-9]{3})([0-9]{3})([0-9]{4})/", "$1($2)$3-$4", $phone_number);
		}
		elseif (strlen($phone_number) == 12) {//номера с кодом вида 38 342 220-2332
			$phone_url = preg_replace("/([0-9]{2})([0-9]{3})([0-9]{3})([0-9]{4})/", "$1 $2 $3-$4", $phone_number);
		}

		if ($phone_url) {

			//сформируем ссылки для разных сервисов ip-телефонии
			if ($pbx_active == 'yes' && $pbx_tip != '') {

				$phone_url = '<a href="javascript:void(0)" class="blue" onclick="showCallWindow(\''.'/content/pbx/'.$pbx_tip.'/callto.php?action=inicialize&clid='.$clid.'&pid='.$pid.'&phone='.prepareMobPhone($phone_url).'\')" title="Позвонить: '.$phone_url.'"><i class="icon icon-phone smaller blue"></i>'.$phone_url.'</a>';

			}
			else {

				$phone_url = '<a href="callto:'.prepareMobPhone($phone_url).'" class="blue" title="Позвонить: '.$phone_url.'"><i class="icon-phone smaller grey"></i>&nbsp;'.$phone_url.'</a>';

			}

		}

	}

	return $phone_url;

}

/**
 * форматирование номера телефона в вид ссылки с учетом интеграции с телефонией
 *
 * @param          $phone
 * @param int|null $clid
 * @param int|null $pid
 *
 * @return mixed
 * @category Core
 * @package  Func
 */
function formatPhoneUrl2($phone, int $clid = NULL, int $pid = NULL) {

	$identity = $GLOBALS['identity'];
	$sqlname  = $GLOBALS['sqlname'];
	$db       = $GLOBALS['db'];

	if (empty($phone)) {
		return "";
	}

	$res        = $db -> getRow("SELECT * FROM {$sqlname}sip WHERE identity = '".$identity."'");
	$pbx_active = $res["active"];
	$pbx_tip    = $res["tip"];

	$phone_number = preg_replace("/\D/", "", $phone);

	if (strlen($phone_number) == 5) {//номера вида 220-2332
		$phone_url = preg_replace("/(\D{2})(\D{3})/", "$1-$2", $phone_number);
	}
	elseif (strlen($phone_number) == 6) {//номера вида 220-2332
		$phone_url = preg_replace("/(\D{3})(\D{3})/", "$1-$2", $phone_number);
	}
	elseif (strlen($phone_number) == 7) {//номера вида 220-2332
		$phone_url = preg_replace("/(\D{3})(\D{4})/", "$1-$2", $phone_number);
	}
	elseif (strlen($phone_number) == 10) {//номера с кодом вида 342 220-2332
		$phone_url = preg_replace("/(\D{3})(\D{3})(\D{4})/", "($1)$2-$3", $phone_number);
	}
	elseif (strlen($phone_number) == 11) {//номера с кодом вида 7(8) 342 220-2332

		$phone_url = preg_replace("/(\D{1})(\D{3})(\D{3})(\D{4})/", "$1($2)$3-$4", $phone_number);
	}
	elseif (strlen($phone_number) == 12) {//номера с кодом вида 38 342 220-2332
		$phone_url = preg_replace("/(\D{2})(\D{3})(\D{3})(\D{4})/", "$1($2)$3-$4", $phone_number);
	}
	else {
		$phone_url = $phone;
	}

	//сформируем ссылки для разных сервисов ip-телефонии
	if ($pbx_active == 'yes' && $pbx_tip != '') {

		if ($phone_url) {
			$phone_url = '<div class="ellipsis"><a href="javascript:void(0)" onclick="showCallWindow(\''.'/content/pbx/'.$pbx_tip.'/callto.php?action=inicialize&clid='.$clid.'&pid='.$pid.'&phone='.prepareMobPhone($phone_url).'\')" title="Позвонить: '.$phone_url.'" class="blue"><i class="icon-phone smalltxt"></i>'.$phone.'</a></div>';
		}

	}
	elseif ($phone_url) {
		$phone_url = '<div class="ellipsis"><a href="callto:'.prepareMobPhone($phone_url).'" title="Позвонить через программу"><i class="icon-phone"></i>'.$phone.'</a></div>';
	}

	return $phone_url;

}

/**
 * форматирование номера телефона в вид ссылки с иконкой с учетом интеграции с телефонией
 *
 * @param          $phone
 * @param int|null $clid
 * @param int|null $pid
 *
 * @return mixed
 * @category Core
 * @package  Func
 */
function formatPhoneUrlIcon($phone, int $clid = NULL, int $pid = NULL) {

	$identity = $GLOBALS['identity'];
	$sqlname  = $GLOBALS['sqlname'];
	$db       = $GLOBALS['db'];

	if (empty($phone)) {
		return "";
	}

	$res        = $db -> getRow("SELECT * FROM {$sqlname}sip WHERE identity = '".$identity."'");
	$pbx_active = $res["active"];
	$pbx_tip    = $res["tip"];

	$phone_number = preg_replace("/\D/", "", $phone);

	if (strlen($phone_number) == 5) {//номера вида 220-2332
		$phone_url = preg_replace("/(\D{2})(\D{3})/", "$1-$2", $phone_number);
	}
	elseif (strlen($phone_number) == 6) {//номера вида 220-2332
		$phone_url = preg_replace("/(\D{3})(\D{3})/", "$1-$2", $phone_number);
	}
	elseif (strlen($phone_number) == 7) {//номера вида 220-2332
		$phone_url = preg_replace("/(\D{3})(\D{4})/", "$1-$2", $phone_number);
	}
	elseif (strlen($phone_number) == 10) {//номера с кодом вида 342 220-2332
		$phone_url = preg_replace("/(\D{3})(\D{3})(\D{4})/", "($1)$2-$3", $phone_number);
	}
	elseif (strlen($phone_number) == 11) {//номера с кодом вида 7(8) 342 220-2332

		$phone_url = preg_replace("/(\D{1})(\D{3})(\D{3})(\D{4})/", "$1($2)$3-$4", $phone_number);
	}
	elseif (strlen($phone_number) == 12) {//номера с кодом вида 38 342 220-2332
		$phone_url = preg_replace("/(\D{2})(\D{3})(\D{3})(\D{4})/", "$1($2)$3-$4", $phone_number);
	}
	else {
		$phone_url = $phone;
	}

	//сформируем ссылки для разных сервисов ip-телефонии
	if ($pbx_active == 'yes' && $pbx_tip != '') {

		if ($phone_url) {
			$phone_url = '<span class="pull-aright"><a href="javascript:void(0)" onclick="showCallWindow(\''.'content/pbx/'.$pbx_tip.'/callto.php?action=inicialize&clid='.$clid.'&pid='.$pid.'&phone='.prepareMobPhone($phone_url).'\')" title="Позвонить: '.$phone_url.'"><i class="icon-phone-squared"></i></a></span>';
		}

	}
	elseif ($phone_url) {
		$phone_url = '<span class="pull-aright"><a href="callto:'.prepareMobPhone($phone_url).'" title="Позвонить софтфоном"><i class="icon-phone-squared"></i></a></span>';
	}

	return $phone_url;

}

/**
 * Форматирует строку с номерами и возвращает массив, в котором содержатся:
 *  - number - очищенный номер
 *  - isMobile - является ли номер мобильным
 *  - formated - форматированный номер с учетом интеграции с телефонией
 * @param $xphone
 * @param int|NULL $clid
 * @param int|NULL $pid
 * @return array
 */
function preparePhoneData($xphone, int $clid = NULL, int $pid = NULL): array {

	$phone_list = [];
	$phones     = yexplode(",", str_replace(";", ",", str_replace(" ", "", $xphone)));
	foreach ($phones as $phone) {

		$number   = prepareMobPhone($phone);
		$isMobile = is_mobile($number);

		$phone_list[] = [
			"number"   => $number,
			"isMobile" => $isMobile,
			"formated" => formatPhoneUrl($phone, $clid, $pid)
		];

	}

	return $phone_list;

}

/**
 * Форматирует строку с email и возвращает массив, в котором содержатся:
 *  - email
 *  - ссылка mailto
 *  - appendix - ссылка на составление письма, если почтовик включен
 * @param $xmail
 * @param int|NULL $clid
 * @param int|NULL $pid
 * @return array
 */
function prepareEmailData($xmail, int $clid = NULL, int $pid = NULL): array {

	global $ymEnable;
	$list = [];

	$emails = explode(",", str_replace(";", ",", (string)$xmail));

	foreach ($emails as $email) {

		$list[] = [
			"email"    => $email,
			"link"     => link_it($email),
			"isMailer" => $ymEnable,
			"appendix" => $ymEnable ? '&nbsp;(<A href="javascript:void(0)" onclick="$mailer.composeCard(\''.$clid.'\',\''.$pid.'\',\''.trim($email).'\');" title="Написать сообщение"><i class="icon-mail blue"></i></A>)&nbsp;' : ""
		];

	}

	return $list;

}

/**
 * очистка от символов кроме цифр
 *
 * @param $phone
 *
 * @return array|string|string[]|null
 * @category Core
 * @package  Func
 */
function prepareMobPhone($phone) {

	return preg_replace("/\D/", "", $phone);

}

/**
 * Очистка номера если включена телефония
 *
 * @param $phone
 *
 * @return mixed
 * @category Core
 * @package  Func
 */
function preparePhone($phone) {

	$identity = $GLOBALS['identity'];
	$sqlname  = $GLOBALS['sqlname'];
	$db       = $GLOBALS['db'];

	$pbx_active = $db -> getOne("SELECT active FROM {$sqlname}sip WHERE identity = '$identity'");

	//очистка от символов кроме цифр
	if ($pbx_active == 'yes') {
		$phone = preg_replace("/\D/", "", $phone);
	}

	return $phone;

}

/**
 * Проверка номера на принадлежность к мобильным номерам
 *
 * @param $phone
 *
 * @return bool
 * @category Core
 * @package  Func
 */
function isPhoneMobile($phone): bool {

	$phone = preg_replace("/\D/", "", $phone);

	return str_split($phone)[1] == '9';

}

/**
 * Возвращает url для инициализации звонка
 *
 * @param string|null $phone
 *
 * @return string
 * @category Core
 * @package  Func
 */
function getCallUrl(string $phone = NULL): string {

	$identity = $GLOBALS['identity'];
	$sqlname  = $GLOBALS['sqlname'];
	$db       = $GLOBALS['db'];
	$url      = '';

	$res        = $db -> getRow("SELECT * FROM {$sqlname}sip WHERE identity = '".$identity."'");
	$pbx_active = $res["active"];
	$pbx_tip    = $res["tip"];

	$phone_number = preg_replace("/\D/", "", $phone);

	if (strlen($phone_number) == 5) {//номера вида 220-2332
		$phone_url = preg_replace("/(\D{2})(\D{3})/", "$1-$2", $phone_number);
	}
	elseif (strlen($phone_number) == 6) {//номера вида 220-2332
		$phone_url = preg_replace("/(\D{3})(\D{3})/", "$1-$2", $phone_number);
	}
	elseif (strlen($phone_number) == 7) {//номера вида 220-2332
		$phone_url = preg_replace("/(\D{3})(\D{4})/", "$1-$2", $phone_number);
	}
	elseif (strlen($phone_number) == 10) {//номера с кодом вида 342 220-2332
		$phone_url = preg_replace("/(\D{3})(\D{3})(\D{4})/", "($1)$2-$3", $phone_number);
	}
	elseif (strlen($phone_number) == 11) {//номера с кодом вида 7(8) 342 220-2332

		$phone_url = preg_replace("/(\D{1})(\D{3})(\D{3})(\D{4})/", "$1($2)$3-$4", $phone_number);
	}
	elseif (strlen($phone_number) == 12) {//номера с кодом вида 38 342 220-2332
		$phone_url = preg_replace("/(\D{2})(\D{3})(\D{3})(\D{4})/", "$1($2)$3-$4", substr($phone_number, 1));
	}
	else {
		$phone_url = $phone;
	}

	//сформируем ссылки для разных сервисов ip-телефонии
	if ($pbx_active == 'yes' && $pbx_tip != '') {

		if ($phone_url != '') {
			$url = '/content/pbx/'.$pbx_tip.'/callto.php?action=inicialize&clid={clid}&pid={pid}&phone='.prepareMobPhone($phone_url);
		}
		else {
			$url = '/content/pbx/'.$pbx_tip.'/callto.php?action=inicialize&clid={clid}&pid={pid}&phone={phone}';
		}

	}

	return $url;

}

/**
 * Функция умного форматирования и удаление дубликатов в строке номеров телефонов
 *
 * @param      $phone - строка, содержащая список номеров через запятую
 * @param bool $format - нужно ли форматировать номер (true/false), default = false
 * @param bool $isarray - возврат результата в виде массива  (true/false), default = false
 *
 * @return array|string
 * @category Core
 * @package  Func
 */
function preparePhoneSmart($phone, bool $format = false, bool $isarray = false) {

	$nphone = ( !is_array($phone) ) ? yexplode(",", (string)str_replace(";", ",", $phone)) : $phone;

	$new = [];
	foreach ($nphone as $k => $item) {

		if (trim($item) != '') {

			$str = prepareMobPhone($item);

			$new[] = ( str_split($str)[0] == 8 ) ? "7".substr($str, 1) : $str;

		}

	}
	$nphone = array_unique($new);

	if ($format) {

		$new = [];
		foreach ($nphone as $k => $item) {

			$new[] = formatPhone2($item);

		}

	}
	else {
		$new = $nphone;
	}

	return $isarray ? $new : implode(", ", $new);

}

/**
 * Проверяет номер на принадлежность к мобильным
 *
 * @param $phone
 *
 * @return bool
 * @category Core
 * @package  Func
 */
function is_mobile($phone): bool {

	$phone = preg_replace("/\D/", "", $phone);

	return str_split($phone)[1] == '9';

}

/**
 * Возвращает строку в транслите
 *
 * @param $str
 *
 * @return array|string|string[]
 * @category Core
 * @package  Func
 */
function translit($str) {
	$trans = [
		"а" => "a",
		"б" => "b",
		"в" => "v",
		"г" => "g",
		"д" => "d",
		"е" => "e",
		"ё" => "yo",
		"ж" => "j",
		"з" => "z",
		"и" => "i",
		"й" => "i",
		"к" => "k",
		"л" => "l",
		"м" => "m",
		"н" => "n",
		"о" => "o",
		"п" => "p",
		"р" => "r",
		"с" => "s",
		"т" => "t",
		"у" => "y",
		"ф" => "f",
		"х" => "h",
		"ц" => "c",
		"ч" => "ch",
		"ш" => "sh",
		"щ" => "shh",
		"ы" => "i",
		"э" => "e",
		"ю" => "u",
		"я" => "ya",
		"ї" => "i",
		"'" => "",
		"ь" => "",
		"Ь" => "",
		"ъ" => "",
		"Ъ" => "",
		"і" => "i",
		"А" => "A",
		"Б" => "B",
		"В" => "V",
		"Г" => "G",
		"Д" => "D",
		"Е" => "E",
		"Ё" => "Yo",
		"Ж" => "J",
		"З" => "Z",
		"И" => "I",
		"Й" => "I",
		"К" => "K",
		"Л" => "L",
		"М" => "M",
		"Н" => "N",
		"О" => "O",
		"П" => "P",
		"Р" => "R",
		"С" => "S",
		"Т" => "T",
		"У" => "Y",
		"Ф" => "F",
		"Х" => "H",
		"Ц" => "C",
		"Ч" => "Ch",
		"Ш" => "Sh",
		"Щ" => "Sh",
		"Ы" => "I",
		"Э" => "E",
		"Ю" => "U",
		"Я" => "Ya",
		"Ї" => "I",
		"І" => "I",
		"№" => "No"
	];

	return str_replace(" ", " ", strtr($str, $trans));

}

/**
 * Автосмена раскладки клавиатуры при вводе текста в input
 * arrow:
 *   0 - перевод (рус -> eng)
 *   1 - перевод (eng -> рус)
 *   2 - перевод (комбо)
 *
 * @param     $text
 * @param int $arrow
 *
 * @return string
 * @category Core
 * @package  Func
 */
function switcher($text, int $arrow = 0): string {

	$str[0] = [
		'й' => 'q',
		'ц' => 'w',
		'у' => 'e',
		'к' => 'r',
		'е' => 't',
		'н' => 'y',
		'г' => 'u',
		'ш' => 'i',
		'щ' => 'o',
		'з' => 'p',
		//'х' => '\[',
		//'ъ' => '\]',
		'ф' => 'a',
		'ы' => 's',
		'в' => 'd',
		'а' => 'f',
		'п' => 'g',
		'р' => 'h',
		'о' => 'j',
		'л' => 'k',
		'д' => 'l',
		'ж' => '',
		//'\;',
		'э' => '',
		//'\'',
		'я' => 'z',
		'ч' => 'x',
		'с' => 'c',
		'м' => 'v',
		'и' => 'b',
		'т' => 'n',
		'ь' => 'm',
		'б' => '',
		//'\,',
		'ю' => '',
		//'\.',
		'Й' => 'Q',
		'Ц' => 'W',
		'У' => 'E',
		'К' => 'R',
		'Е' => 'T',
		'Н' => 'Y',
		'Г' => 'U',
		'Ш' => 'I',
		'Щ' => 'O',
		'З' => 'P',
		//'Х' => '\[',
		//'Ъ' => '\]',
		'Ф' => 'A',
		'Ы' => 'S',
		'В' => 'D',
		'А' => 'F',
		'П' => 'G',
		'Р' => 'H',
		'О' => 'J',
		'Л' => 'K',
		'Д' => 'L',
		'Ж' => '',
		//'\;',
		'Э' => '',
		//'\'',
		'?' => 'Z',
		'Ч' => 'X',
		'С' => 'C',
		'М' => 'V',
		'И' => 'B',
		'Т' => 'N',
		'Ь' => 'M',
		'Б' => '',
		//'\<',
		'Ю' => '',
		//'\>',
	];
	$str[1] = [
		'q'  => 'й',
		'w'  => 'ц',
		'e'  => 'у',
		'r'  => 'к',
		't'  => 'е',
		'y'  => 'н',
		'u'  => 'г',
		'i'  => 'ш',
		'o'  => 'щ',
		'p'  => 'з',
		'['  => 'х',
		']'  => 'ъ',
		'a'  => 'ф',
		's'  => 'ы',
		'd'  => 'в',
		'f'  => 'а',
		'g'  => 'п',
		'h'  => 'р',
		'j'  => 'о',
		'k'  => 'л',
		'l'  => 'д',
		';'  => 'ж',
		'\'' => 'э',
		'z'  => 'я',
		'x'  => 'ч',
		'c'  => 'с',
		'v'  => 'м',
		'b'  => 'и',
		'n'  => 'т',
		'm'  => 'ь',
		','  => 'б',
		'.'  => 'ю',
		'Q'  => 'Й',
		'W'  => 'Ц',
		'E'  => 'У',
		'R'  => 'К',
		'T'  => 'Е',
		'Y'  => 'Н',
		'U'  => 'Г',
		'I'  => 'Ш',
		'O'  => 'Щ',
		'P'  => 'З',
		'{'  => 'Х',
		'}'  => 'Ъ',
		'A'  => 'Ф',
		'S'  => 'Ы',
		'D'  => 'В',
		'F'  => 'А',
		'G'  => 'П',
		'H'  => 'Р',
		'J'  => 'О',
		'K'  => 'Л',
		'L'  => 'Д',
		':'  => 'Ж',
		'\"' => 'Э',
		'Z'  => 'Я',
		'X'  => 'Ч',
		'C'  => 'С',
		'V'  => 'М',
		'B'  => 'И',
		'N'  => 'Т',
		'M'  => 'Ь',
		'<'  => 'Б',
		'>'  => 'Ю',
	];

	return strtr($text, $str[$arrow] ?? array_merge($str[0], $str[1]));

}

/**
 * Возвращает сумму прописью с учетом локализации
 *
 * @param float $num
 *
 * @return string
 * @category Core
 * @package  Func
 * @author   runcore
 * @uses     morph(...)
 */
function num2str(float $num = 0): string {

	$fpath = $GLOBALS['fpath'];

	$root = dirname(__DIR__);

	if (file_exists($root.'/cash/'.$fpath.'requisites.json')) {
		$file = file_get_contents($root.'/cash/'.$fpath.'requisites.json');
	}
	else {
		$file = file_get_contents($root.'/cash/requisites.json');
	}

	$recvName = json_decode(str_replace([
		"  ",
		"\t",
		"\n",
		"\r"
	], "", $file), true);

	$val1 = explode(",", $recvName['valutaTrans']);
	$val2 = explode(",", $recvName['valutaTransSub']);

	if (file_exists($root.'/cash/'.$fpath.'valuta.json')) {
		$file = file_get_contents($root.'/cash/'.$fpath.'valuta.json');
	}
	else {
		$file = file_get_contents($root.'/cash/valuta.json');
	}
	$name = json_decode($file, true);

	$valuta = [
		[
			$val2[0],
			$val2[1],
			$val2[2],
			1
		],
		[
			$val1[0],
			$val1[1],
			$val1[2],
			0
		]
	];

	array_unshift($name['unit'], $valuta[0], $valuta[1]);

	//[ $rub, $kop ] = explode( '.', sprintf( "%015.2f", floatval( $num ) ) );

	$fk  = explode('.', sprintf("%015.2f", (float)$num));
	$rub = $fk[0];
	$kop = $fk[1];

	$out = [];
	if ((int)$rub > 0) {
		foreach (str_split($rub, 3) as $uk => $v) { // by 3 symbols
			if (!(int)$v) {
				continue;
			}
			$uk     = count((array)$name['unit']) - $uk - 1; // unit key
			$gender = $name['unit'][$uk][3];

			//[ $i1, $i2, $i3 ] = array_map( 'intval', str_split( $v, 1 ) );

			$fkf = array_map('intval', str_split($v, 1));
			$i1  = $fkf[0];
			$i2  = $fkf[1];
			$i3  = $fkf[2];

			// mega-logic
			$out[] = $name['hundred'][$i1];                  # 1xx-9xx
			if ($i2 > 1) {
				$out[] = $name['tens'][$i2].' '.$name['ten'][$gender][$i3];
			} # 20-99
			else {
				$out[] = $i2 > 0 ? $name['a20'][$i3] : $name['ten'][$gender][$i3];
			} # 10-19 | 1-9
			// units without rub & kop
			if ($uk > 1) {
				$out[] = morph($v, $name['unit'][$uk][0], $name['unit'][$uk][1], $name['unit'][$uk][2]);
			}
		} //foreach
	}
	else {
		$out[] = $name['nul'];
	}

	$out[] = morph((int)$rub, $name['unit'][1][0], $name['unit'][1][1], $name['unit'][1][2]);     // rub
	$out[] = $kop.' '.morph($kop, $name['unit'][0][0], $name['unit'][0][1], $name['unit'][0][2]); // kop

	return trim(preg_replace('/ {2,}/', ' ', implode(' ', $out)));
}

/**
 * Склоняем словоформу в зависимости от числа
 * @ author runcore
 *
 * @param $n
 * @param $f1
 * @param $f2
 * @param $f5
 *
 * @return mixed
 * @category Core
 * @package  Func
 */
function morph($n, $f1, $f2, $f5) {

	$n = abs((int)$n) % 100;

	if ($n > 10 && $n < 20) {
		return $f5;
	}

	$n %= 10;

	if ($n > 1 && $n < 5) {
		return $f2;
	}

	if ($n == 1) {
		return $f1;
	}

	return $f5;

}

/**
 * Генератор "соли" для кодирования паролей
 *
 * @param int $max
 *
 * @return null|string
 * @throws \Exception
 * @category Core
 * @package  Func
 */
function generateSalt(int $max = 32): ?string {

	if (!$max) {
		$max = 32;
	}

	$chars = "qazxswedcvfrtgbnhyujmkiolp1234567890QAZXSWEDCVFRTGBNHYUJMKIOLP";
	$size  = StrLen($chars) - 1;
	$salt  = NULL;
	while ($max--) {
		$salt .= $chars[random_int(0, $size)];
	}

	return $salt;

}

/**
 * Расшифровка пароля на основе "соли"
 *
 * @param $pass
 * @param $salt
 *
 * @return string
 * @category Core
 * @package  Func
 */
function encodePass($pass, $salt): string {

	return hash('sha512', ( hash('sha512', $salt.$pass).$salt.strlen($salt) * 37 )).substr(hash('sha512', $pass.$salt), 0, 42);

}

/**
 * Не будет работать в PHP >= 7.1.0
 *
 * @return string
 * @throws \Exception
 */
function rij_iv(): string {

	//$iv_size = mcrypt_get_iv_size(MCRYPT_RIJNDAEL_256, MCRYPT_MODE_CFB);
	//$iv = mcrypt_create_iv($iv_size, MCRYPT_DEV_RANDOM);

	//$iv = openssl_random_pseudo_bytes( 16 );
	$iv = random_bytes(16);

	return base64_encode($iv);

}

/**
 * @param $text
 * @param $key
 * @param $iv
 *
 * @return string
 * @category Core
 * @package  Func
 */
function rij_crypt($text, $key, $iv): string {

	$crypttext = openssl_encrypt($text, 'AES-256-CTR', $key, OPENSSL_RAW_DATA, base64_decode($iv));

	return base64_encode($crypttext);

}

/**
 * @param $text
 * @param $key
 * @param $iv
 *
 * @return string
 * @category Core
 * @package  Func
 */
function rij_decrypt($text, $key, $iv): string {
	return openssl_decrypt(base64_decode($text), 'AES-256-CTR', $key, OPENSSL_RAW_DATA, base64_decode($iv));
}

/**
 * Перемещает файл из папки в папку копированием либо перемещением
 *
 * @param        $from
 * @param        $new_dir
 * @param string $del
 *
 * @return string
 * @category Core
 * @package  Func
 */
function copyFile($from, $new_dir, string $del = 'no'): string {

	$frez = '';

	if (!file_exists($from)) {
		$frez = 'Файл нет существует: '.$from;
	}# Если файл не существует
	if (!is_dir($new_dir)) {
		$frez = 'Директория нет существует: '.$new_dir;
	}# Если директории

	$filename = $new_dir.DIRECTORY_SEPARATOR.basename($from);# Путь к файлу

	if (copy($from, $filename)) {# Копируем файл

		if ($del == 'yes') {
			unlink($from);
		}

	}

	return $frez;

}

/**
 * Конвертация цвета из HEX в RGB(a)
 *
 * @param      $hex
 * @param bool $alpha
 *
 * @return array
 * @category Core
 * @package  Func
 */
function hexToRgb($hex, bool $alpha = false): array {

	$hex    = str_replace('#', '', $hex);
	$length = strlen($hex);

	$rgb['r'] = hexdec($length == 6 ? substr($hex, 0, 2) : ( $length == 3 ? str_repeat($hex[0], 2) : 0 ));
	$rgb['g'] = hexdec($length == 6 ? substr($hex, 2, 2) : ( $length == 3 ? str_repeat($hex[1], 2) : 0 ));
	$rgb['b'] = hexdec($length == 6 ? substr($hex, 4, 2) : ( $length == 3 ? str_repeat($hex[2], 2) : 0 ));

	if ($alpha) {
		$rgb['a'] = $alpha;
	}

	return $rgb;

}

/**
 * Возвращает массив с информацией по использованию диска
 *
 * @param string $myDir
 *
 * @return array
 * @category Core
 * @package  Func
 */
function getFileLimit(string $myDir = ''): array {

	$prefix = dirname(__DIR__);

	$identity  = $GLOBALS['identity'];
	$sqlname   = $GLOBALS['sqlname'];
	$db        = $GLOBALS['db'];
	$fpath     = $GLOBALS['fpath'];
	$diskAddon = $GLOBALS['diskAddon'];
	$opts      = $GLOBALS['opts'];
	$diskLimit = $GLOBALS['diskLimit'];

	unset($db);
	$db = new SafeMySQL($opts);

	$countuser = $db -> getOne("SELECT COUNT(iduser) as count FROM {$sqlname}user WHERE secrty != 'no' and identity = '$identity'");

	//Ограничение на размер диска
	$diskLimit  = ( !isset($diskLimit) ) ? 0 : $diskLimit * $countuser + $diskAddon;
	$persent    = 0;
	$total_size = 0;

	$myDir = ( $myDir == '' ) ? $prefix."/files/".$fpath : $myDir;

	//if (PHP_OS != "Linux") $myDir = str_replace("/","\\",$myDir);

	if ($diskLimit > 0) {

		$total_size = round(( getDirSize($myDir) / 1048576 ), 2);
		$persent    = 100 * ( $total_size / $diskLimit );

	}

	return [
		"total"   => $diskLimit,
		"current" => $total_size,
		"percent" => round($persent, 2)
	];

}

/**
 * Получение списка файлов в указанной папке c помощью команды exec
 *
 * @param $folder
 *
 * @return array
 * @category Core
 * @package  Func
 */
function getDirFiles($folder): array {

	//папка, в которой ищем файлы
	$path = dirname(__DIR__)."/$folder";

	$listNames = [];

	if (PHP_OS != "Linux") {

		$path = str_replace("/", "\\", $path);

		/**
		 * Команда выводит список файлов в каталоге
		 * /m *.* - маска "все файлы"
		 * /p - задаем путь
		 */
		$cmd = "forfiles /m *.* /p $path";
		exec($cmd, $list, $exit);

		//возвращает только имена файлов, без кавычек
		$listNames = array_map(static function ($var) {
			return str_replace("\"", "", $var);
		}, $list);

	}
	else {

		/**
		 * команда для вывода списка файлов в каталоге, созданные за последние 2 дня
		 * -maxdepth - глубина поиска (1 - текущий каталог)
		 * -mtime - фильтр по времени
		 * -type f - учитывать только файлы
		 * $list - массив файлов
		 */
		$cmd = "find $path -maxdepth 1 -type f";

		exec($cmd, $list, $exit);

		//возвращает только имена файлов, без пути
		$listNames = array_map(static function ($var) {
			return basename($var);
		}, $list);

	}

	return $listNames;

}

/**
 * Возвращает список поддирректорий в указанной
 *
 * @param $folder
 *
 * @return array
 * @category Core
 * @package  Func
 */
function getDirList($folder): array {

	$list = [];

	if ($dh = opendir($folder)) {

		while (( $file = readdir($dh) ) !== false) {

			if ($file != "." && $file != ".." && is_dir($folder."/".$file)) {

				$list[] = $file;

			}

		}

	}

	closedir($dh);

	return $list;

}

/**
 * Расчет места на диске по указанному пути
 *
 * @param $dir_name
 *
 * @return float
 * @category Core
 * @package  Func
 */
function getDirSize($dir_name) {

	$dir_size = 0;

	if (PHP_OS != "Linux") {

		//print "dir=".$dir_name."\n";

		if (is_dir($dir_name)) {

			if ($dh = opendir($dir_name)) {

				while (( $file = readdir($dh) ) !== false) {

					if ($file != "." && $file != "..") {

						//print "file=".$dir_name."/".$file."\n";

						if (is_file($dir_name."/".$file)) {
							$dir_size += filesize($dir_name."/".$file);
						}

						if (is_dir($dir_name."/".$file)) {
							$dir_size += (float)getDirSize($dir_name."/".$file);
						}

					}

				}

			}

			closedir($dh);

		}

	}
	else {

		$cmd = "du -s $dir_name";
		exec($cmd, $result, $exit);

		$dir_size = yexplode("\t", (string)$result[0], 0);

	}

	return $dir_size;

}

/**
 * Создает каталог, если его нет
 *
 * @param $directory
 * @category Core
 * @package  Func
 */
function createDir($directory) {

	if (!file_exists($directory)) {

		if (!mkdir($directory, 0777) && !is_dir($directory)) {
			throw new RuntimeException(sprintf('Directory "%s" was not created', $directory));
		}
		chmod($directory, 0777);

	}

}

/**
 * Удаляет папку с файлами
 *
 * @param $directory
 * @category Core
 * @package  Func
 */
function removeDir($directory) {

	$recursive = new RecursiveDirectoryIterator($directory, RecursiveDirectoryIterator::SKIP_DOTS);
	$files     = new RecursiveIteratorIterator($recursive, RecursiveIteratorIterator::CHILD_FIRST);
	foreach ($files as $file) {
		if ($file -> isDir()) {
			rmdir($file -> getRealPath());
		}
		else {
			unlink($file -> getRealPath());
		}
	}

	rmdir($directory);

}

/**
 * Проверка строки на соответствие формату json
 *
 * @param $string
 * @return bool
 * @category Core
 * @package  Func
 */
function isJson($string): bool {

	json_decode($string);

	return json_last_error() === JSON_ERROR_NONE;

}

/**
 * Округляет значение ширины до ближайшего большего значения из массива ширин
 * Эти ширины имеют соответствующие css-классы
 *
 * @param $value
 *
 * @return mixed
 * @category Core
 * @package  Func
 */
function toWidth($value): int {

	if ((int)$value > 0) {

		$numbers = [
			5,
			10,
			15,
			20,
			25,
			30,
			35,
			40,
			45,
			50,
			55,
			60,
			65,
			70,
			75,
			80,
			85,
			90,
			95,
			100,
			110,
			120,
			130,
			140,
			150,
			160,
			170,
			180,
			190,
			200,
			250,
			300,
			350,
			400
		];
		$result  = $value;

		foreach ($numbers as $n) {

			if ($value <= $n) {
				$result = $n;
				break;
			}

		}

	}
	else {
		$result = (int)$value;
	}

	return $result;

}

/**
 * Функция рачета CRC файла
 * Можно рассмотреть в качестве генератора имени файла
 *
 * @param $file
 *
 * @return string
 *
 * @category Core
 * @package  Func
 * @see      https://www.php.net/manual/ru/function.crc32.php#56215
 *
 * Example:
 * ```php
 * $crc = file_crc($file);
 * ```
 */
function fileCRC($file): string {

	$file_string = file_get_contents($file);

	$crc = crc32($file_string);

	return sprintf("%u", $crc);

}

/**
 * Converts bytes into human readable file size.
 *
 * @param string|float $bytes
 *
 * @return string human readable file size (2,87 Мб)
 * @category Core
 * @package  Func
 * @author   Mogilev Arseny
 */
function FileSize2Human($bytes): string {

	$result = '';

	$bytes   = (float)$bytes;
	$arBytes = [
		0 => [
			"UNIT"  => "TB",
			"VALUE" => 1024 ** 4
		],
		1 => [
			"UNIT"  => "GB",
			"VALUE" => 1024 ** 3
		],
		2 => [
			"UNIT"  => "MB",
			"VALUE" => 1024 ** 2
		],
		3 => [
			"UNIT"  => "KB",
			"VALUE" => 1024
		],
		4 => [
			"UNIT"  => "B",
			"VALUE" => 1
		],
	];

	foreach ($arBytes as $arItem) {
		if ($bytes >= $arItem["VALUE"]) {
			$result = $bytes / $arItem["VALUE"];
			$result = str_replace(".", ",", (string)round($result, 2))." ".$arItem["UNIT"];
			break;
		}
	}

	return $result;

}

/**
 * Converts human readable file size (e.g. 10 MB, 200.20 GB) into bytes.
 *
 * @param string $str
 *
 * @return int the result is in bytes
 * @category Core
 * @package  Func
 * @author   Svetoslav Marinov
 * @author   http://slavi.biz
 */
function FileSize2MBytes(string $str) {

	$str    .= "B";
	$output = '';

	preg_match('/(\d+)(\w+)/', $str, $matches);
	$type = strtolower($matches[2]);

	switch ($type) {
		case "b":
			$output = $matches[1];
			break;
		case "kb":
			$output = $matches[1] * 1024;
			break;
		case "mb":
			$output = $matches[1] * 1024 * 1024;
			break;
		case "gb":
			$output = $matches[1] * 1024 * 1024 * 1024;
			break;
		case "tb":
			$output = $matches[1] * 1024 * 1024 * 1024 * 1024;
			break;
	}

	$output /= ( 1024 * 1024 );

	return $output;

}

/**
 * Изменяет расширение файла на новое с сохранением пути
 *
 * @param        $path
 * @param string $newextention
 *
 * @return array
 * @category Core
 * @package  Func
 */
function changeFileExt($path, string $newextention = 'jpg'): array {

	$pathinfo = pathinfo($path);

	$dir  = $pathinfo['dirname'];
	$name = $pathinfo['filename'];

	return [
		"pathOld" => $path,
		"extOld"  => $pathinfo['extention'],
		"extNew"  => $newextention,
		"pathNew" => "{$dir}/{$name}.{$newextention}"
	];

}

/**
 * Очищает строку от эмоджи
 *
 * @param $text
 *
 * @return string|string[]|null
 * @category Core
 * @package  Func
 */
function remove_emoji($text) {
	$text = preg_replace('/[\x{1F3F4}](?:\x{E0067}\x{E0062}\x{E0077}\x{E006C}\x{E0073}\x{E007F})|[\x{1F3F4}](?:\x{E0067}\x{E0062}\x{E0073}\x{E0063}\x{E0074}\x{E007F})|[\x{1F3F4}](?:\x{E0067}\x{E0062}\x{E0065}\x{E006E}\x{E0067}\x{E007F})|[\x{1F3F4}](?:\x{200D}\x{2620}\x{FE0F})|[\x{1F3F3}](?:\x{FE0F}\x{200D}\x{1F308})|[\x{0023}\x{002A}\x{0030}\x{0031}\x{0032}\x{0033}\x{0034}\x{0035}\x{0036}\x{0037}\x{0038}\x{0039}](?:\x{FE0F}\x{20E3})|[\x{1F441}](?:\x{FE0F}\x{200D}\x{1F5E8}\x{FE0F})|[\x{1F468}\x{1F469}](?:\x{200D}\x{1F467}\x{200D}\x{1F467})|[\x{1F468}\x{1F469}](?:\x{200D}\x{1F467}\x{200D}\x{1F466})|[\x{1F468}\x{1F469}](?:\x{200D}\x{1F467})|[\x{1F468}\x{1F469}](?:\x{200D}\x{1F466}\x{200D}\x{1F466})|[\x{1F468}\x{1F469}](?:\x{200D}\x{1F466})|[\x{1F468}](?:\x{200D}\x{1F468}\x{200D}\x{1F467}\x{200D}\x{1F467})|[\x{1F468}](?:\x{200D}\x{1F468}\x{200D}\x{1F466}\x{200D}\x{1F466})|[\x{1F468}](?:\x{200D}\x{1F468}\x{200D}\x{1F467}\x{200D}\x{1F466})|[\x{1F468}](?:\x{200D}\x{1F468}\x{200D}\x{1F467})|[\x{1F468}](?:\x{200D}\x{1F468}\x{200D}\x{1F466})|[\x{1F468}\x{1F469}](?:\x{200D}\x{1F469}\x{200D}\x{1F467}\x{200D}\x{1F467})|[\x{1F468}\x{1F469}](?:\x{200D}\x{1F469}\x{200D}\x{1F466}\x{200D}\x{1F466})|[\x{1F468}\x{1F469}](?:\x{200D}\x{1F469}\x{200D}\x{1F467}\x{200D}\x{1F466})|[\x{1F468}\x{1F469}](?:\x{200D}\x{1F469}\x{200D}\x{1F467})|[\x{1F468}\x{1F469}](?:\x{200D}\x{1F469}\x{200D}\x{1F466})|[\x{1F469}](?:\x{200D}\x{2764}\x{FE0F}\x{200D}\x{1F469})|[\x{1F469}\x{1F468}](?:\x{200D}\x{2764}\x{FE0F}\x{200D}\x{1F468})|[\x{1F469}](?:\x{200D}\x{2764}\x{FE0F}\x{200D}\x{1F48B}\x{200D}\x{1F469})|[\x{1F469}\x{1F468}](?:\x{200D}\x{2764}\x{FE0F}\x{200D}\x{1F48B}\x{200D}\x{1F468})|[\x{1F468}\x{1F469}](?:\x{1F3FF}\x{200D}\x{1F9B3})|[\x{1F468}\x{1F469}](?:\x{1F3FE}\x{200D}\x{1F9B3})|[\x{1F468}\x{1F469}](?:\x{1F3FD}\x{200D}\x{1F9B3})|[\x{1F468}\x{1F469}](?:\x{1F3FC}\x{200D}\x{1F9B3})|[\x{1F468}\x{1F469}](?:\x{1F3FB}\x{200D}\x{1F9B3})|[\x{1F468}\x{1F469}](?:\x{200D}\x{1F9B3})|[\x{1F468}\x{1F469}](?:\x{1F3FF}\x{200D}\x{1F9B2})|[\x{1F468}\x{1F469}](?:\x{1F3FE}\x{200D}\x{1F9B2})|[\x{1F468}\x{1F469}](?:\x{1F3FD}\x{200D}\x{1F9B2})|[\x{1F468}\x{1F469}](?:\x{1F3FC}\x{200D}\x{1F9B2})|[\x{1F468}\x{1F469}](?:\x{1F3FB}\x{200D}\x{1F9B2})|[\x{1F468}\x{1F469}](?:\x{200D}\x{1F9B2})|[\x{1F468}\x{1F469}](?:\x{1F3FF}\x{200D}\x{1F9B1})|[\x{1F468}\x{1F469}](?:\x{1F3FE}\x{200D}\x{1F9B1})|[\x{1F468}\x{1F469}](?:\x{1F3FD}\x{200D}\x{1F9B1})|[\x{1F468}\x{1F469}](?:\x{1F3FC}\x{200D}\x{1F9B1})|[\x{1F468}\x{1F469}](?:\x{1F3FB}\x{200D}\x{1F9B1})|[\x{1F468}\x{1F469}](?:\x{200D}\x{1F9B1})|[\x{1F468}\x{1F469}](?:\x{1F3FF}\x{200D}\x{1F9B0})|[\x{1F468}\x{1F469}](?:\x{1F3FE}\x{200D}\x{1F9B0})|[\x{1F468}\x{1F469}](?:\x{1F3FD}\x{200D}\x{1F9B0})|[\x{1F468}\x{1F469}](?:\x{1F3FC}\x{200D}\x{1F9B0})|[\x{1F468}\x{1F469}](?:\x{1F3FB}\x{200D}\x{1F9B0})|[\x{1F468}\x{1F469}](?:\x{200D}\x{1F9B0})|[\x{1F575}\x{1F3CC}\x{26F9}\x{1F3CB}](?:\x{FE0F}\x{200D}\x{2640}\x{FE0F})|[\x{1F575}\x{1F3CC}\x{26F9}\x{1F3CB}](?:\x{FE0F}\x{200D}\x{2642}\x{FE0F})|[\x{1F46E}\x{1F575}\x{1F482}\x{1F477}\x{1F473}\x{1F471}\x{1F9D9}\x{1F9DA}\x{1F9DB}\x{1F9DC}\x{1F9DD}\x{1F64D}\x{1F64E}\x{1F645}\x{1F646}\x{1F481}\x{1F64B}\x{1F647}\x{1F926}\x{1F937}\x{1F486}\x{1F487}\x{1F6B6}\x{1F3C3}\x{1F9D6}\x{1F9D7}\x{1F9D8}\x{1F3CC}\x{1F3C4}\x{1F6A3}\x{1F3CA}\x{26F9}\x{1F3CB}\x{1F6B4}\x{1F6B5}\x{1F938}\x{1F93D}\x{1F93E}\x{1F939}](?:\x{1F3FF}\x{200D}\x{2640}\x{FE0F})|[\x{1F46E}\x{1F575}\x{1F482}\x{1F477}\x{1F473}\x{1F471}\x{1F9D9}\x{1F9DA}\x{1F9DB}\x{1F9DC}\x{1F9DD}\x{1F64D}\x{1F64E}\x{1F645}\x{1F646}\x{1F481}\x{1F64B}\x{1F647}\x{1F926}\x{1F937}\x{1F486}\x{1F487}\x{1F6B6}\x{1F3C3}\x{1F9D6}\x{1F9D7}\x{1F9D8}\x{1F3CC}\x{1F3C4}\x{1F6A3}\x{1F3CA}\x{26F9}\x{1F3CB}\x{1F6B4}\x{1F6B5}\x{1F938}\x{1F93D}\x{1F93E}\x{1F939}](?:\x{1F3FE}\x{200D}\x{2640}\x{FE0F})|[\x{1F46E}\x{1F575}\x{1F482}\x{1F477}\x{1F473}\x{1F471}\x{1F9D9}\x{1F9DA}\x{1F9DB}\x{1F9DC}\x{1F9DD}\x{1F64D}\x{1F64E}\x{1F645}\x{1F646}\x{1F481}\x{1F64B}\x{1F647}\x{1F926}\x{1F937}\x{1F486}\x{1F487}\x{1F6B6}\x{1F3C3}\x{1F9D6}\x{1F9D7}\x{1F9D8}\x{1F3CC}\x{1F3C4}\x{1F6A3}\x{1F3CA}\x{26F9}\x{1F3CB}\x{1F6B4}\x{1F6B5}\x{1F938}\x{1F93D}\x{1F93E}\x{1F939}](?:\x{1F3FD}\x{200D}\x{2640}\x{FE0F})|[\x{1F46E}\x{1F575}\x{1F482}\x{1F477}\x{1F473}\x{1F471}\x{1F9D9}\x{1F9DA}\x{1F9DB}\x{1F9DC}\x{1F9DD}\x{1F64D}\x{1F64E}\x{1F645}\x{1F646}\x{1F481}\x{1F64B}\x{1F647}\x{1F926}\x{1F937}\x{1F486}\x{1F487}\x{1F6B6}\x{1F3C3}\x{1F9D6}\x{1F9D7}\x{1F9D8}\x{1F3CC}\x{1F3C4}\x{1F6A3}\x{1F3CA}\x{26F9}\x{1F3CB}\x{1F6B4}\x{1F6B5}\x{1F938}\x{1F93D}\x{1F93E}\x{1F939}](?:\x{1F3FC}\x{200D}\x{2640}\x{FE0F})|[\x{1F46E}\x{1F575}\x{1F482}\x{1F477}\x{1F473}\x{1F471}\x{1F9D9}\x{1F9DA}\x{1F9DB}\x{1F9DC}\x{1F9DD}\x{1F64D}\x{1F64E}\x{1F645}\x{1F646}\x{1F481}\x{1F64B}\x{1F647}\x{1F926}\x{1F937}\x{1F486}\x{1F487}\x{1F6B6}\x{1F3C3}\x{1F9D6}\x{1F9D7}\x{1F9D8}\x{1F3CC}\x{1F3C4}\x{1F6A3}\x{1F3CA}\x{26F9}\x{1F3CB}\x{1F6B4}\x{1F6B5}\x{1F938}\x{1F93D}\x{1F93E}\x{1F939}](?:\x{1F3FB}\x{200D}\x{2640}\x{FE0F})|[\x{1F46E}\x{1F9B8}\x{1F9B9}\x{1F482}\x{1F477}\x{1F473}\x{1F471}\x{1F9D9}\x{1F9DA}\x{1F9DB}\x{1F9DC}\x{1F9DD}\x{1F9DE}\x{1F9DF}\x{1F64D}\x{1F64E}\x{1F645}\x{1F646}\x{1F481}\x{1F64B}\x{1F647}\x{1F926}\x{1F937}\x{1F486}\x{1F487}\x{1F6B6}\x{1F3C3}\x{1F46F}\x{1F9D6}\x{1F9D7}\x{1F9D8}\x{1F3C4}\x{1F6A3}\x{1F3CA}\x{1F6B4}\x{1F6B5}\x{1F938}\x{1F93C}\x{1F93D}\x{1F93E}\x{1F939}](?:\x{200D}\x{2640}\x{FE0F})|[\x{1F46E}\x{1F575}\x{1F482}\x{1F477}\x{1F473}\x{1F471}\x{1F9D9}\x{1F9DA}\x{1F9DB}\x{1F9DC}\x{1F9DD}\x{1F64D}\x{1F64E}\x{1F645}\x{1F646}\x{1F481}\x{1F64B}\x{1F647}\x{1F926}\x{1F937}\x{1F486}\x{1F487}\x{1F6B6}\x{1F3C3}\x{1F9D6}\x{1F9D7}\x{1F9D8}\x{1F3CC}\x{1F3C4}\x{1F6A3}\x{1F3CA}\x{26F9}\x{1F3CB}\x{1F6B4}\x{1F6B5}\x{1F938}\x{1F93D}\x{1F93E}\x{1F939}](?:\x{1F3FF}\x{200D}\x{2642}\x{FE0F})|[\x{1F46E}\x{1F575}\x{1F482}\x{1F477}\x{1F473}\x{1F471}\x{1F9D9}\x{1F9DA}\x{1F9DB}\x{1F9DC}\x{1F9DD}\x{1F64D}\x{1F64E}\x{1F645}\x{1F646}\x{1F481}\x{1F64B}\x{1F647}\x{1F926}\x{1F937}\x{1F486}\x{1F487}\x{1F6B6}\x{1F3C3}\x{1F9D6}\x{1F9D7}\x{1F9D8}\x{1F3CC}\x{1F3C4}\x{1F6A3}\x{1F3CA}\x{26F9}\x{1F3CB}\x{1F6B4}\x{1F6B5}\x{1F938}\x{1F93D}\x{1F93E}\x{1F939}](?:\x{1F3FE}\x{200D}\x{2642}\x{FE0F})|[\x{1F46E}\x{1F575}\x{1F482}\x{1F477}\x{1F473}\x{1F471}\x{1F9D9}\x{1F9DA}\x{1F9DB}\x{1F9DC}\x{1F9DD}\x{1F64D}\x{1F64E}\x{1F645}\x{1F646}\x{1F481}\x{1F64B}\x{1F647}\x{1F926}\x{1F937}\x{1F486}\x{1F487}\x{1F6B6}\x{1F3C3}\x{1F9D6}\x{1F9D7}\x{1F9D8}\x{1F3CC}\x{1F3C4}\x{1F6A3}\x{1F3CA}\x{26F9}\x{1F3CB}\x{1F6B4}\x{1F6B5}\x{1F938}\x{1F93D}\x{1F93E}\x{1F939}](?:\x{1F3FD}\x{200D}\x{2642}\x{FE0F})|[\x{1F46E}\x{1F575}\x{1F482}\x{1F477}\x{1F473}\x{1F471}\x{1F9D9}\x{1F9DA}\x{1F9DB}\x{1F9DC}\x{1F9DD}\x{1F64D}\x{1F64E}\x{1F645}\x{1F646}\x{1F481}\x{1F64B}\x{1F647}\x{1F926}\x{1F937}\x{1F486}\x{1F487}\x{1F6B6}\x{1F3C3}\x{1F9D6}\x{1F9D7}\x{1F9D8}\x{1F3CC}\x{1F3C4}\x{1F6A3}\x{1F3CA}\x{26F9}\x{1F3CB}\x{1F6B4}\x{1F6B5}\x{1F938}\x{1F93D}\x{1F93E}\x{1F939}](?:\x{1F3FC}\x{200D}\x{2642}\x{FE0F})|[\x{1F46E}\x{1F575}\x{1F482}\x{1F477}\x{1F473}\x{1F471}\x{1F9D9}\x{1F9DA}\x{1F9DB}\x{1F9DC}\x{1F9DD}\x{1F64D}\x{1F64E}\x{1F645}\x{1F646}\x{1F481}\x{1F64B}\x{1F647}\x{1F926}\x{1F937}\x{1F486}\x{1F487}\x{1F6B6}\x{1F3C3}\x{1F9D6}\x{1F9D7}\x{1F9D8}\x{1F3CC}\x{1F3C4}\x{1F6A3}\x{1F3CA}\x{26F9}\x{1F3CB}\x{1F6B4}\x{1F6B5}\x{1F938}\x{1F93D}\x{1F93E}\x{1F939}](?:\x{1F3FB}\x{200D}\x{2642}\x{FE0F})|[\x{1F46E}\x{1F9B8}\x{1F9B9}\x{1F482}\x{1F477}\x{1F473}\x{1F471}\x{1F9D9}\x{1F9DA}\x{1F9DB}\x{1F9DC}\x{1F9DD}\x{1F9DE}\x{1F9DF}\x{1F64D}\x{1F64E}\x{1F645}\x{1F646}\x{1F481}\x{1F64B}\x{1F647}\x{1F926}\x{1F937}\x{1F486}\x{1F487}\x{1F6B6}\x{1F3C3}\x{1F46F}\x{1F9D6}\x{1F9D7}\x{1F9D8}\x{1F3C4}\x{1F6A3}\x{1F3CA}\x{1F6B4}\x{1F6B5}\x{1F938}\x{1F93C}\x{1F93D}\x{1F93E}\x{1F939}](?:\x{200D}\x{2642}\x{FE0F})|[\x{1F468}\x{1F469}](?:\x{1F3FF}\x{200D}\x{1F692})|[\x{1F468}\x{1F469}](?:\x{1F3FE}\x{200D}\x{1F692})|[\x{1F468}\x{1F469}](?:\x{1F3FD}\x{200D}\x{1F692})|[\x{1F468}\x{1F469}](?:\x{1F3FC}\x{200D}\x{1F692})|[\x{1F468}\x{1F469}](?:\x{1F3FB}\x{200D}\x{1F692})|[\x{1F468}\x{1F469}](?:\x{200D}\x{1F692})|[\x{1F468}\x{1F469}](?:\x{1F3FF}\x{200D}\x{1F680})|[\x{1F468}\x{1F469}](?:\x{1F3FE}\x{200D}\x{1F680})|[\x{1F468}\x{1F469}](?:\x{1F3FD}\x{200D}\x{1F680})|[\x{1F468}\x{1F469}](?:\x{1F3FC}\x{200D}\x{1F680})|[\x{1F468}\x{1F469}](?:\x{1F3FB}\x{200D}\x{1F680})|[\x{1F468}\x{1F469}](?:\x{200D}\x{1F680})|[\x{1F468}\x{1F469}](?:\x{1F3FF}\x{200D}\x{2708}\x{FE0F})|[\x{1F468}\x{1F469}](?:\x{1F3FE}\x{200D}\x{2708}\x{FE0F})|[\x{1F468}\x{1F469}](?:\x{1F3FD}\x{200D}\x{2708}\x{FE0F})|[\x{1F468}\x{1F469}](?:\x{1F3FC}\x{200D}\x{2708}\x{FE0F})|[\x{1F468}\x{1F469}](?:\x{1F3FB}\x{200D}\x{2708}\x{FE0F})|[\x{1F468}\x{1F469}](?:\x{200D}\x{2708}\x{FE0F})|[\x{1F468}\x{1F469}](?:\x{1F3FF}\x{200D}\x{1F3A8})|[\x{1F468}\x{1F469}](?:\x{1F3FE}\x{200D}\x{1F3A8})|[\x{1F468}\x{1F469}](?:\x{1F3FD}\x{200D}\x{1F3A8})|[\x{1F468}\x{1F469}](?:\x{1F3FC}\x{200D}\x{1F3A8})|[\x{1F468}\x{1F469}](?:\x{1F3FB}\x{200D}\x{1F3A8})|[\x{1F468}\x{1F469}](?:\x{200D}\x{1F3A8})|[\x{1F468}\x{1F469}](?:\x{1F3FF}\x{200D}\x{1F3A4})|[\x{1F468}\x{1F469}](?:\x{1F3FE}\x{200D}\x{1F3A4})|[\x{1F468}\x{1F469}](?:\x{1F3FD}\x{200D}\x{1F3A4})|[\x{1F468}\x{1F469}](?:\x{1F3FC}\x{200D}\x{1F3A4})|[\x{1F468}\x{1F469}](?:\x{1F3FB}\x{200D}\x{1F3A4})|[\x{1F468}\x{1F469}](?:\x{200D}\x{1F3A4})|[\x{1F468}\x{1F469}](?:\x{1F3FF}\x{200D}\x{1F4BB})|[\x{1F468}\x{1F469}](?:\x{1F3FE}\x{200D}\x{1F4BB})|[\x{1F468}\x{1F469}](?:\x{1F3FD}\x{200D}\x{1F4BB})|[\x{1F468}\x{1F469}](?:\x{1F3FC}\x{200D}\x{1F4BB})|[\x{1F468}\x{1F469}](?:\x{1F3FB}\x{200D}\x{1F4BB})|[\x{1F468}\x{1F469}](?:\x{200D}\x{1F4BB})|[\x{1F468}\x{1F469}](?:\x{1F3FF}\x{200D}\x{1F52C})|[\x{1F468}\x{1F469}](?:\x{1F3FE}\x{200D}\x{1F52C})|[\x{1F468}\x{1F469}](?:\x{1F3FD}\x{200D}\x{1F52C})|[\x{1F468}\x{1F469}](?:\x{1F3FC}\x{200D}\x{1F52C})|[\x{1F468}\x{1F469}](?:\x{1F3FB}\x{200D}\x{1F52C})|[\x{1F468}\x{1F469}](?:\x{200D}\x{1F52C})|[\x{1F468}\x{1F469}](?:\x{1F3FF}\x{200D}\x{1F4BC})|[\x{1F468}\x{1F469}](?:\x{1F3FE}\x{200D}\x{1F4BC})|[\x{1F468}\x{1F469}](?:\x{1F3FD}\x{200D}\x{1F4BC})|[\x{1F468}\x{1F469}](?:\x{1F3FC}\x{200D}\x{1F4BC})|[\x{1F468}\x{1F469}](?:\x{1F3FB}\x{200D}\x{1F4BC})|[\x{1F468}\x{1F469}](?:\x{200D}\x{1F4BC})|[\x{1F468}\x{1F469}](?:\x{1F3FF}\x{200D}\x{1F3ED})|[\x{1F468}\x{1F469}](?:\x{1F3FE}\x{200D}\x{1F3ED})|[\x{1F468}\x{1F469}](?:\x{1F3FD}\x{200D}\x{1F3ED})|[\x{1F468}\x{1F469}](?:\x{1F3FC}\x{200D}\x{1F3ED})|[\x{1F468}\x{1F469}](?:\x{1F3FB}\x{200D}\x{1F3ED})|[\x{1F468}\x{1F469}](?:\x{200D}\x{1F3ED})|[\x{1F468}\x{1F469}](?:\x{1F3FF}\x{200D}\x{1F527})|[\x{1F468}\x{1F469}](?:\x{1F3FE}\x{200D}\x{1F527})|[\x{1F468}\x{1F469}](?:\x{1F3FD}\x{200D}\x{1F527})|[\x{1F468}\x{1F469}](?:\x{1F3FC}\x{200D}\x{1F527})|[\x{1F468}\x{1F469}](?:\x{1F3FB}\x{200D}\x{1F527})|[\x{1F468}\x{1F469}](?:\x{200D}\x{1F527})|[\x{1F468}\x{1F469}](?:\x{1F3FF}\x{200D}\x{1F373})|[\x{1F468}\x{1F469}](?:\x{1F3FE}\x{200D}\x{1F373})|[\x{1F468}\x{1F469}](?:\x{1F3FD}\x{200D}\x{1F373})|[\x{1F468}\x{1F469}](?:\x{1F3FC}\x{200D}\x{1F373})|[\x{1F468}\x{1F469}](?:\x{1F3FB}\x{200D}\x{1F373})|[\x{1F468}\x{1F469}](?:\x{200D}\x{1F373})|[\x{1F468}\x{1F469}](?:\x{1F3FF}\x{200D}\x{1F33E})|[\x{1F468}\x{1F469}](?:\x{1F3FE}\x{200D}\x{1F33E})|[\x{1F468}\x{1F469}](?:\x{1F3FD}\x{200D}\x{1F33E})|[\x{1F468}\x{1F469}](?:\x{1F3FC}\x{200D}\x{1F33E})|[\x{1F468}\x{1F469}](?:\x{1F3FB}\x{200D}\x{1F33E})|[\x{1F468}\x{1F469}](?:\x{200D}\x{1F33E})|[\x{1F468}\x{1F469}](?:\x{1F3FF}\x{200D}\x{2696}\x{FE0F})|[\x{1F468}\x{1F469}](?:\x{1F3FE}\x{200D}\x{2696}\x{FE0F})|[\x{1F468}\x{1F469}](?:\x{1F3FD}\x{200D}\x{2696}\x{FE0F})|[\x{1F468}\x{1F469}](?:\x{1F3FC}\x{200D}\x{2696}\x{FE0F})|[\x{1F468}\x{1F469}](?:\x{1F3FB}\x{200D}\x{2696}\x{FE0F})|[\x{1F468}\x{1F469}](?:\x{200D}\x{2696}\x{FE0F})|[\x{1F468}\x{1F469}](?:\x{1F3FF}\x{200D}\x{1F3EB})|[\x{1F468}\x{1F469}](?:\x{1F3FE}\x{200D}\x{1F3EB})|[\x{1F468}\x{1F469}](?:\x{1F3FD}\x{200D}\x{1F3EB})|[\x{1F468}\x{1F469}](?:\x{1F3FC}\x{200D}\x{1F3EB})|[\x{1F468}\x{1F469}](?:\x{1F3FB}\x{200D}\x{1F3EB})|[\x{1F468}\x{1F469}](?:\x{200D}\x{1F3EB})|[\x{1F468}\x{1F469}](?:\x{1F3FF}\x{200D}\x{1F393})|[\x{1F468}\x{1F469}](?:\x{1F3FE}\x{200D}\x{1F393})|[\x{1F468}\x{1F469}](?:\x{1F3FD}\x{200D}\x{1F393})|[\x{1F468}\x{1F469}](?:\x{1F3FC}\x{200D}\x{1F393})|[\x{1F468}\x{1F469}](?:\x{1F3FB}\x{200D}\x{1F393})|[\x{1F468}\x{1F469}](?:\x{200D}\x{1F393})|[\x{1F468}\x{1F469}](?:\x{1F3FF}\x{200D}\x{2695}\x{FE0F})|[\x{1F468}\x{1F469}](?:\x{1F3FE}\x{200D}\x{2695}\x{FE0F})|[\x{1F468}\x{1F469}](?:\x{1F3FD}\x{200D}\x{2695}\x{FE0F})|[\x{1F468}\x{1F469}](?:\x{1F3FC}\x{200D}\x{2695}\x{FE0F})|[\x{1F468}\x{1F469}](?:\x{1F3FB}\x{200D}\x{2695}\x{FE0F})|[\x{1F468}\x{1F469}](?:\x{200D}\x{2695}\x{FE0F})|[\x{1F476}\x{1F9D2}\x{1F466}\x{1F467}\x{1F9D1}\x{1F468}\x{1F469}\x{1F9D3}\x{1F474}\x{1F475}\x{1F46E}\x{1F575}\x{1F482}\x{1F477}\x{1F934}\x{1F478}\x{1F473}\x{1F472}\x{1F9D5}\x{1F9D4}\x{1F471}\x{1F935}\x{1F470}\x{1F930}\x{1F931}\x{1F47C}\x{1F385}\x{1F936}\x{1F9D9}\x{1F9DA}\x{1F9DB}\x{1F9DC}\x{1F9DD}\x{1F64D}\x{1F64E}\x{1F645}\x{1F646}\x{1F481}\x{1F64B}\x{1F647}\x{1F926}\x{1F937}\x{1F486}\x{1F487}\x{1F6B6}\x{1F3C3}\x{1F483}\x{1F57A}\x{1F9D6}\x{1F9D7}\x{1F9D8}\x{1F6C0}\x{1F6CC}\x{1F574}\x{1F3C7}\x{1F3C2}\x{1F3CC}\x{1F3C4}\x{1F6A3}\x{1F3CA}\x{26F9}\x{1F3CB}\x{1F6B4}\x{1F6B5}\x{1F938}\x{1F93D}\x{1F93E}\x{1F939}\x{1F933}\x{1F4AA}\x{1F9B5}\x{1F9B6}\x{1F448}\x{1F449}\x{261D}\x{1F446}\x{1F595}\x{1F447}\x{270C}\x{1F91E}\x{1F596}\x{1F918}\x{1F919}\x{1F590}\x{270B}\x{1F44C}\x{1F44D}\x{1F44E}\x{270A}\x{1F44A}\x{1F91B}\x{1F91C}\x{1F91A}\x{1F44B}\x{1F91F}\x{270D}\x{1F44F}\x{1F450}\x{1F64C}\x{1F932}\x{1F64F}\x{1F485}\x{1F442}\x{1F443}](?:\x{1F3FF})|[\x{1F476}\x{1F9D2}\x{1F466}\x{1F467}\x{1F9D1}\x{1F468}\x{1F469}\x{1F9D3}\x{1F474}\x{1F475}\x{1F46E}\x{1F575}\x{1F482}\x{1F477}\x{1F934}\x{1F478}\x{1F473}\x{1F472}\x{1F9D5}\x{1F9D4}\x{1F471}\x{1F935}\x{1F470}\x{1F930}\x{1F931}\x{1F47C}\x{1F385}\x{1F936}\x{1F9D9}\x{1F9DA}\x{1F9DB}\x{1F9DC}\x{1F9DD}\x{1F64D}\x{1F64E}\x{1F645}\x{1F646}\x{1F481}\x{1F64B}\x{1F647}\x{1F926}\x{1F937}\x{1F486}\x{1F487}\x{1F6B6}\x{1F3C3}\x{1F483}\x{1F57A}\x{1F9D6}\x{1F9D7}\x{1F9D8}\x{1F6C0}\x{1F6CC}\x{1F574}\x{1F3C7}\x{1F3C2}\x{1F3CC}\x{1F3C4}\x{1F6A3}\x{1F3CA}\x{26F9}\x{1F3CB}\x{1F6B4}\x{1F6B5}\x{1F938}\x{1F93D}\x{1F93E}\x{1F939}\x{1F933}\x{1F4AA}\x{1F9B5}\x{1F9B6}\x{1F448}\x{1F449}\x{261D}\x{1F446}\x{1F595}\x{1F447}\x{270C}\x{1F91E}\x{1F596}\x{1F918}\x{1F919}\x{1F590}\x{270B}\x{1F44C}\x{1F44D}\x{1F44E}\x{270A}\x{1F44A}\x{1F91B}\x{1F91C}\x{1F91A}\x{1F44B}\x{1F91F}\x{270D}\x{1F44F}\x{1F450}\x{1F64C}\x{1F932}\x{1F64F}\x{1F485}\x{1F442}\x{1F443}](?:\x{1F3FE})|[\x{1F476}\x{1F9D2}\x{1F466}\x{1F467}\x{1F9D1}\x{1F468}\x{1F469}\x{1F9D3}\x{1F474}\x{1F475}\x{1F46E}\x{1F575}\x{1F482}\x{1F477}\x{1F934}\x{1F478}\x{1F473}\x{1F472}\x{1F9D5}\x{1F9D4}\x{1F471}\x{1F935}\x{1F470}\x{1F930}\x{1F931}\x{1F47C}\x{1F385}\x{1F936}\x{1F9D9}\x{1F9DA}\x{1F9DB}\x{1F9DC}\x{1F9DD}\x{1F64D}\x{1F64E}\x{1F645}\x{1F646}\x{1F481}\x{1F64B}\x{1F647}\x{1F926}\x{1F937}\x{1F486}\x{1F487}\x{1F6B6}\x{1F3C3}\x{1F483}\x{1F57A}\x{1F9D6}\x{1F9D7}\x{1F9D8}\x{1F6C0}\x{1F6CC}\x{1F574}\x{1F3C7}\x{1F3C2}\x{1F3CC}\x{1F3C4}\x{1F6A3}\x{1F3CA}\x{26F9}\x{1F3CB}\x{1F6B4}\x{1F6B5}\x{1F938}\x{1F93D}\x{1F93E}\x{1F939}\x{1F933}\x{1F4AA}\x{1F9B5}\x{1F9B6}\x{1F448}\x{1F449}\x{261D}\x{1F446}\x{1F595}\x{1F447}\x{270C}\x{1F91E}\x{1F596}\x{1F918}\x{1F919}\x{1F590}\x{270B}\x{1F44C}\x{1F44D}\x{1F44E}\x{270A}\x{1F44A}\x{1F91B}\x{1F91C}\x{1F91A}\x{1F44B}\x{1F91F}\x{270D}\x{1F44F}\x{1F450}\x{1F64C}\x{1F932}\x{1F64F}\x{1F485}\x{1F442}\x{1F443}](?:\x{1F3FD})|[\x{1F476}\x{1F9D2}\x{1F466}\x{1F467}\x{1F9D1}\x{1F468}\x{1F469}\x{1F9D3}\x{1F474}\x{1F475}\x{1F46E}\x{1F575}\x{1F482}\x{1F477}\x{1F934}\x{1F478}\x{1F473}\x{1F472}\x{1F9D5}\x{1F9D4}\x{1F471}\x{1F935}\x{1F470}\x{1F930}\x{1F931}\x{1F47C}\x{1F385}\x{1F936}\x{1F9D9}\x{1F9DA}\x{1F9DB}\x{1F9DC}\x{1F9DD}\x{1F64D}\x{1F64E}\x{1F645}\x{1F646}\x{1F481}\x{1F64B}\x{1F647}\x{1F926}\x{1F937}\x{1F486}\x{1F487}\x{1F6B6}\x{1F3C3}\x{1F483}\x{1F57A}\x{1F9D6}\x{1F9D7}\x{1F9D8}\x{1F6C0}\x{1F6CC}\x{1F574}\x{1F3C7}\x{1F3C2}\x{1F3CC}\x{1F3C4}\x{1F6A3}\x{1F3CA}\x{26F9}\x{1F3CB}\x{1F6B4}\x{1F6B5}\x{1F938}\x{1F93D}\x{1F93E}\x{1F939}\x{1F933}\x{1F4AA}\x{1F9B5}\x{1F9B6}\x{1F448}\x{1F449}\x{261D}\x{1F446}\x{1F595}\x{1F447}\x{270C}\x{1F91E}\x{1F596}\x{1F918}\x{1F919}\x{1F590}\x{270B}\x{1F44C}\x{1F44D}\x{1F44E}\x{270A}\x{1F44A}\x{1F91B}\x{1F91C}\x{1F91A}\x{1F44B}\x{1F91F}\x{270D}\x{1F44F}\x{1F450}\x{1F64C}\x{1F932}\x{1F64F}\x{1F485}\x{1F442}\x{1F443}](?:\x{1F3FC})|[\x{1F476}\x{1F9D2}\x{1F466}\x{1F467}\x{1F9D1}\x{1F468}\x{1F469}\x{1F9D3}\x{1F474}\x{1F475}\x{1F46E}\x{1F575}\x{1F482}\x{1F477}\x{1F934}\x{1F478}\x{1F473}\x{1F472}\x{1F9D5}\x{1F9D4}\x{1F471}\x{1F935}\x{1F470}\x{1F930}\x{1F931}\x{1F47C}\x{1F385}\x{1F936}\x{1F9D9}\x{1F9DA}\x{1F9DB}\x{1F9DC}\x{1F9DD}\x{1F64D}\x{1F64E}\x{1F645}\x{1F646}\x{1F481}\x{1F64B}\x{1F647}\x{1F926}\x{1F937}\x{1F486}\x{1F487}\x{1F6B6}\x{1F3C3}\x{1F483}\x{1F57A}\x{1F9D6}\x{1F9D7}\x{1F9D8}\x{1F6C0}\x{1F6CC}\x{1F574}\x{1F3C7}\x{1F3C2}\x{1F3CC}\x{1F3C4}\x{1F6A3}\x{1F3CA}\x{26F9}\x{1F3CB}\x{1F6B4}\x{1F6B5}\x{1F938}\x{1F93D}\x{1F93E}\x{1F939}\x{1F933}\x{1F4AA}\x{1F9B5}\x{1F9B6}\x{1F448}\x{1F449}\x{261D}\x{1F446}\x{1F595}\x{1F447}\x{270C}\x{1F91E}\x{1F596}\x{1F918}\x{1F919}\x{1F590}\x{270B}\x{1F44C}\x{1F44D}\x{1F44E}\x{270A}\x{1F44A}\x{1F91B}\x{1F91C}\x{1F91A}\x{1F44B}\x{1F91F}\x{270D}\x{1F44F}\x{1F450}\x{1F64C}\x{1F932}\x{1F64F}\x{1F485}\x{1F442}\x{1F443}](?:\x{1F3FB})|[\x{1F1E6}\x{1F1E7}\x{1F1E8}\x{1F1E9}\x{1F1F0}\x{1F1F2}\x{1F1F3}\x{1F1F8}\x{1F1F9}\x{1F1FA}](?:\x{1F1FF})|[\x{1F1E7}\x{1F1E8}\x{1F1EC}\x{1F1F0}\x{1F1F1}\x{1F1F2}\x{1F1F5}\x{1F1F8}\x{1F1FA}](?:\x{1F1FE})|[\x{1F1E6}\x{1F1E8}\x{1F1F2}\x{1F1F8}](?:\x{1F1FD})|[\x{1F1E6}\x{1F1E7}\x{1F1E8}\x{1F1EC}\x{1F1F0}\x{1F1F2}\x{1F1F5}\x{1F1F7}\x{1F1F9}\x{1F1FF}](?:\x{1F1FC})|[\x{1F1E7}\x{1F1E8}\x{1F1F1}\x{1F1F2}\x{1F1F8}\x{1F1F9}](?:\x{1F1FB})|[\x{1F1E6}\x{1F1E8}\x{1F1EA}\x{1F1EC}\x{1F1ED}\x{1F1F1}\x{1F1F2}\x{1F1F3}\x{1F1F7}\x{1F1FB}](?:\x{1F1FA})|[\x{1F1E6}\x{1F1E7}\x{1F1EA}\x{1F1EC}\x{1F1ED}\x{1F1EE}\x{1F1F1}\x{1F1F2}\x{1F1F5}\x{1F1F8}\x{1F1F9}\x{1F1FE}](?:\x{1F1F9})|[\x{1F1E6}\x{1F1E7}\x{1F1EA}\x{1F1EC}\x{1F1EE}\x{1F1F1}\x{1F1F2}\x{1F1F5}\x{1F1F7}\x{1F1F8}\x{1F1FA}\x{1F1FC}](?:\x{1F1F8})|[\x{1F1E6}\x{1F1E7}\x{1F1E8}\x{1F1EA}\x{1F1EB}\x{1F1EC}\x{1F1ED}\x{1F1EE}\x{1F1F0}\x{1F1F1}\x{1F1F2}\x{1F1F3}\x{1F1F5}\x{1F1F8}\x{1F1F9}](?:\x{1F1F7})|[\x{1F1E6}\x{1F1E7}\x{1F1EC}\x{1F1EE}\x{1F1F2}](?:\x{1F1F6})|[\x{1F1E8}\x{1F1EC}\x{1F1EF}\x{1F1F0}\x{1F1F2}\x{1F1F3}](?:\x{1F1F5})|[\x{1F1E6}\x{1F1E7}\x{1F1E8}\x{1F1E9}\x{1F1EB}\x{1F1EE}\x{1F1EF}\x{1F1F2}\x{1F1F3}\x{1F1F7}\x{1F1F8}\x{1F1F9}](?:\x{1F1F4})|[\x{1F1E7}\x{1F1E8}\x{1F1EC}\x{1F1ED}\x{1F1EE}\x{1F1F0}\x{1F1F2}\x{1F1F5}\x{1F1F8}\x{1F1F9}\x{1F1FA}\x{1F1FB}](?:\x{1F1F3})|[\x{1F1E6}\x{1F1E7}\x{1F1E8}\x{1F1E9}\x{1F1EB}\x{1F1EC}\x{1F1ED}\x{1F1EE}\x{1F1EF}\x{1F1F0}\x{1F1F2}\x{1F1F4}\x{1F1F5}\x{1F1F8}\x{1F1F9}\x{1F1FA}\x{1F1FF}](?:\x{1F1F2})|[\x{1F1E6}\x{1F1E7}\x{1F1E8}\x{1F1EC}\x{1F1EE}\x{1F1F2}\x{1F1F3}\x{1F1F5}\x{1F1F8}\x{1F1F9}](?:\x{1F1F1})|[\x{1F1E8}\x{1F1E9}\x{1F1EB}\x{1F1ED}\x{1F1F1}\x{1F1F2}\x{1F1F5}\x{1F1F8}\x{1F1F9}\x{1F1FD}](?:\x{1F1F0})|[\x{1F1E7}\x{1F1E9}\x{1F1EB}\x{1F1F8}\x{1F1F9}](?:\x{1F1EF})|[\x{1F1E6}\x{1F1E7}\x{1F1E8}\x{1F1EB}\x{1F1EC}\x{1F1F0}\x{1F1F1}\x{1F1F3}\x{1F1F8}\x{1F1FB}](?:\x{1F1EE})|[\x{1F1E7}\x{1F1E8}\x{1F1EA}\x{1F1EC}\x{1F1F0}\x{1F1F2}\x{1F1F5}\x{1F1F8}\x{1F1F9}](?:\x{1F1ED})|[\x{1F1E6}\x{1F1E7}\x{1F1E8}\x{1F1E9}\x{1F1EA}\x{1F1EC}\x{1F1F0}\x{1F1F2}\x{1F1F3}\x{1F1F5}\x{1F1F8}\x{1F1F9}\x{1F1FA}\x{1F1FB}](?:\x{1F1EC})|[\x{1F1E6}\x{1F1E7}\x{1F1E8}\x{1F1EC}\x{1F1F2}\x{1F1F3}\x{1F1F5}\x{1F1F9}\x{1F1FC}](?:\x{1F1EB})|[\x{1F1E6}\x{1F1E7}\x{1F1E9}\x{1F1EA}\x{1F1EC}\x{1F1EE}\x{1F1EF}\x{1F1F0}\x{1F1F2}\x{1F1F3}\x{1F1F5}\x{1F1F7}\x{1F1F8}\x{1F1FB}\x{1F1FE}](?:\x{1F1EA})|[\x{1F1E6}\x{1F1E7}\x{1F1E8}\x{1F1EC}\x{1F1EE}\x{1F1F2}\x{1F1F8}\x{1F1F9}](?:\x{1F1E9})|[\x{1F1E6}\x{1F1E8}\x{1F1EA}\x{1F1EE}\x{1F1F1}\x{1F1F2}\x{1F1F3}\x{1F1F8}\x{1F1F9}\x{1F1FB}](?:\x{1F1E8})|[\x{1F1E7}\x{1F1EC}\x{1F1F1}\x{1F1F8}](?:\x{1F1E7})|[\x{1F1E7}\x{1F1E8}\x{1F1EA}\x{1F1EC}\x{1F1F1}\x{1F1F2}\x{1F1F3}\x{1F1F5}\x{1F1F6}\x{1F1F8}\x{1F1F9}\x{1F1FA}\x{1F1FB}\x{1F1FF}](?:\x{1F1E6})|[\x{00A9}\x{00AE}\x{203C}\x{2049}\x{2122}\x{2139}\x{2194}-\x{2199}\x{21A9}-\x{21AA}\x{231A}-\x{231B}\x{2328}\x{23CF}\x{23E9}-\x{23F3}\x{23F8}-\x{23FA}\x{24C2}\x{25AA}-\x{25AB}\x{25B6}\x{25C0}\x{25FB}-\x{25FE}\x{2600}-\x{2604}\x{260E}\x{2611}\x{2614}-\x{2615}\x{2618}\x{261D}\x{2620}\x{2622}-\x{2623}\x{2626}\x{262A}\x{262E}-\x{262F}\x{2638}-\x{263A}\x{2640}\x{2642}\x{2648}-\x{2653}\x{2660}\x{2663}\x{2665}-\x{2666}\x{2668}\x{267B}\x{267E}-\x{267F}\x{2692}-\x{2697}\x{2699}\x{269B}-\x{269C}\x{26A0}-\x{26A1}\x{26AA}-\x{26AB}\x{26B0}-\x{26B1}\x{26BD}-\x{26BE}\x{26C4}-\x{26C5}\x{26C8}\x{26CE}-\x{26CF}\x{26D1}\x{26D3}-\x{26D4}\x{26E9}-\x{26EA}\x{26F0}-\x{26F5}\x{26F7}-\x{26FA}\x{26FD}\x{2702}\x{2705}\x{2708}-\x{270D}\x{270F}\x{2712}\x{2714}\x{2716}\x{271D}\x{2721}\x{2728}\x{2733}-\x{2734}\x{2744}\x{2747}\x{274C}\x{274E}\x{2753}-\x{2755}\x{2757}\x{2763}-\x{2764}\x{2795}-\x{2797}\x{27A1}\x{27B0}\x{27BF}\x{2934}-\x{2935}\x{2B05}-\x{2B07}\x{2B1B}-\x{2B1C}\x{2B50}\x{2B55}\x{3030}\x{303D}\x{3297}\x{3299}\x{1F004}\x{1F0CF}\x{1F170}-\x{1F171}\x{1F17E}-\x{1F17F}\x{1F18E}\x{1F191}-\x{1F19A}\x{1F201}-\x{1F202}\x{1F21A}\x{1F22F}\x{1F232}-\x{1F23A}\x{1F250}-\x{1F251}\x{1F300}-\x{1F321}\x{1F324}-\x{1F393}\x{1F396}-\x{1F397}\x{1F399}-\x{1F39B}\x{1F39E}-\x{1F3F0}\x{1F3F3}-\x{1F3F5}\x{1F3F7}-\x{1F3FA}\x{1F400}-\x{1F4FD}\x{1F4FF}-\x{1F53D}\x{1F549}-\x{1F54E}\x{1F550}-\x{1F567}\x{1F56F}-\x{1F570}\x{1F573}-\x{1F57A}\x{1F587}\x{1F58A}-\x{1F58D}\x{1F590}\x{1F595}-\x{1F596}\x{1F5A4}-\x{1F5A5}\x{1F5A8}\x{1F5B1}-\x{1F5B2}\x{1F5BC}\x{1F5C2}-\x{1F5C4}\x{1F5D1}-\x{1F5D3}\x{1F5DC}-\x{1F5DE}\x{1F5E1}\x{1F5E3}\x{1F5E8}\x{1F5EF}\x{1F5F3}\x{1F5FA}-\x{1F64F}\x{1F680}-\x{1F6C5}\x{1F6CB}-\x{1F6D2}\x{1F6E0}-\x{1F6E5}\x{1F6E9}\x{1F6EB}-\x{1F6EC}\x{1F6F0}\x{1F6F3}-\x{1F6F9}\x{1F910}-\x{1F93A}\x{1F93C}-\x{1F93E}\x{1F940}-\x{1F945}\x{1F947}-\x{1F970}\x{1F973}-\x{1F976}\x{1F97A}\x{1F97C}-\x{1F9A2}\x{1F9B0}-\x{1F9B9}\x{1F9C0}-\x{1F9C2}\x{1F9D0}-\x{1F9FF}]/u', '', $text);

	return preg_replace('/\x{1F3F4}\x{E0067}\x{E0062}(?:\x{E0077}\x{E006C}\x{E0073}|\x{E0073}\x{E0063}\x{E0074}|\x{E0065}\x{E006E}\x{E0067})\x{E007F}|(?:\x{1F9D1}\x{1F3FF}\x{200D}\x{2764}(?:\x{FE0F}\x{200D}(?:\x{1F48B}\x{200D})?|\x{200D}(?:\x{1F48B}\x{200D})?)\x{1F9D1}|\x{1F469}\x{1F3FF}\x{200D}\x{1F91D}\x{200D}[\x{1F468}\x{1F469}]|\x{1FAF1}\x{1F3FF}\x{200D}\x{1FAF2})[\x{1F3FB}-\x{1F3FE}]|(?:\x{1F9D1}\x{1F3FE}\x{200D}\x{2764}(?:\x{FE0F}\x{200D}(?:\x{1F48B}\x{200D})?|\x{200D}(?:\x{1F48B}\x{200D})?)\x{1F9D1}|\x{1F469}\x{1F3FE}\x{200D}\x{1F91D}\x{200D}[\x{1F468}\x{1F469}]|\x{1FAF1}\x{1F3FE}\x{200D}\x{1FAF2})[\x{1F3FB}-\x{1F3FD}\x{1F3FF}]|(?:\x{1F9D1}\x{1F3FD}\x{200D}\x{2764}(?:\x{FE0F}\x{200D}(?:\x{1F48B}\x{200D})?|\x{200D}(?:\x{1F48B}\x{200D})?)\x{1F9D1}|\x{1F469}\x{1F3FD}\x{200D}\x{1F91D}\x{200D}[\x{1F468}\x{1F469}]|\x{1FAF1}\x{1F3FD}\x{200D}\x{1FAF2})[\x{1F3FB}\x{1F3FC}\x{1F3FE}\x{1F3FF}]|(?:\x{1F9D1}\x{1F3FC}\x{200D}\x{2764}(?:\x{FE0F}\x{200D}(?:\x{1F48B}\x{200D})?|\x{200D}(?:\x{1F48B}\x{200D})?)\x{1F9D1}|\x{1F469}\x{1F3FC}\x{200D}\x{1F91D}\x{200D}[\x{1F468}\x{1F469}]|\x{1FAF1}\x{1F3FC}\x{200D}\x{1FAF2})[\x{1F3FB}\x{1F3FD}-\x{1F3FF}]|(?:\x{1F9D1}\x{1F3FB}\x{200D}\x{2764}(?:\x{FE0F}\x{200D}(?:\x{1F48B}\x{200D})?|\x{200D}(?:\x{1F48B}\x{200D})?)\x{1F9D1}|\x{1F469}\x{1F3FB}\x{200D}\x{1F91D}\x{200D}[\x{1F468}\x{1F469}]|\x{1FAF1}\x{1F3FB}\x{200D}\x{1FAF2})[\x{1F3FC}-\x{1F3FF}]|\x{1F468}(?:\x{1F3FB}(?:\x{200D}(?:\x{2764}(?:\x{FE0F}\x{200D}(?:\x{1F48B}\x{200D}\x{1F468}[\x{1F3FB}-\x{1F3FF}]|\x{1F468}[\x{1F3FB}-\x{1F3FF}])|\x{200D}(?:\x{1F48B}\x{200D}\x{1F468}[\x{1F3FB}-\x{1F3FF}]|\x{1F468}[\x{1F3FB}-\x{1F3FF}]))|\x{1F91D}\x{200D}\x{1F468}[\x{1F3FC}-\x{1F3FF}]|[\x{2695}\x{2696}\x{2708}]\x{FE0F}|[\x{2695}\x{2696}\x{2708}]|[\x{1F33E}\x{1F373}\x{1F37C}\x{1F393}\x{1F3A4}\x{1F3A8}\x{1F3EB}\x{1F3ED}\x{1F4BB}\x{1F4BC}\x{1F527}\x{1F52C}\x{1F680}\x{1F692}\x{1F9AF}-\x{1F9B3}\x{1F9BC}\x{1F9BD}]))?|[\x{1F3FC}-\x{1F3FF}]\x{200D}\x{2764}(?:\x{FE0F}\x{200D}(?:\x{1F48B}\x{200D}\x{1F468}[\x{1F3FB}-\x{1F3FF}]|\x{1F468}[\x{1F3FB}-\x{1F3FF}])|\x{200D}(?:\x{1F48B}\x{200D}\x{1F468}[\x{1F3FB}-\x{1F3FF}]|\x{1F468}[\x{1F3FB}-\x{1F3FF}]))|\x{200D}(?:\x{2764}(?:\x{FE0F}\x{200D}(?:\x{1F48B}\x{200D})?|\x{200D}(?:\x{1F48B}\x{200D})?)\x{1F468}|[\x{1F468}\x{1F469}]\x{200D}(?:\x{1F466}\x{200D}\x{1F466}|\x{1F467}\x{200D}[\x{1F466}\x{1F467}])|\x{1F466}\x{200D}\x{1F466}|\x{1F467}\x{200D}[\x{1F466}\x{1F467}]|[\x{1F33E}\x{1F373}\x{1F37C}\x{1F393}\x{1F3A4}\x{1F3A8}\x{1F3EB}\x{1F3ED}\x{1F4BB}\x{1F4BC}\x{1F527}\x{1F52C}\x{1F680}\x{1F692}\x{1F9AF}-\x{1F9B3}\x{1F9BC}\x{1F9BD}])|\x{1F3FF}\x{200D}(?:\x{1F91D}\x{200D}\x{1F468}[\x{1F3FB}-\x{1F3FE}]|[\x{1F33E}\x{1F373}\x{1F37C}\x{1F393}\x{1F3A4}\x{1F3A8}\x{1F3EB}\x{1F3ED}\x{1F4BB}\x{1F4BC}\x{1F527}\x{1F52C}\x{1F680}\x{1F692}\x{1F9AF}-\x{1F9B3}\x{1F9BC}\x{1F9BD}])|\x{1F3FE}\x{200D}(?:\x{1F91D}\x{200D}\x{1F468}[\x{1F3FB}-\x{1F3FD}\x{1F3FF}]|[\x{1F33E}\x{1F373}\x{1F37C}\x{1F393}\x{1F3A4}\x{1F3A8}\x{1F3EB}\x{1F3ED}\x{1F4BB}\x{1F4BC}\x{1F527}\x{1F52C}\x{1F680}\x{1F692}\x{1F9AF}-\x{1F9B3}\x{1F9BC}\x{1F9BD}])|\x{1F3FD}\x{200D}(?:\x{1F91D}\x{200D}\x{1F468}[\x{1F3FB}\x{1F3FC}\x{1F3FE}\x{1F3FF}]|[\x{1F33E}\x{1F373}\x{1F37C}\x{1F393}\x{1F3A4}\x{1F3A8}\x{1F3EB}\x{1F3ED}\x{1F4BB}\x{1F4BC}\x{1F527}\x{1F52C}\x{1F680}\x{1F692}\x{1F9AF}-\x{1F9B3}\x{1F9BC}\x{1F9BD}])|\x{1F3FC}\x{200D}(?:\x{1F91D}\x{200D}\x{1F468}[\x{1F3FB}\x{1F3FD}-\x{1F3FF}]|[\x{1F33E}\x{1F373}\x{1F37C}\x{1F393}\x{1F3A4}\x{1F3A8}\x{1F3EB}\x{1F3ED}\x{1F4BB}\x{1F4BC}\x{1F527}\x{1F52C}\x{1F680}\x{1F692}\x{1F9AF}-\x{1F9B3}\x{1F9BC}\x{1F9BD}])|(?:\x{1F3FF}\x{200D}[\x{2695}\x{2696}\x{2708}]|\x{1F3FE}\x{200D}[\x{2695}\x{2696}\x{2708}]|\x{1F3FD}\x{200D}[\x{2695}\x{2696}\x{2708}]|\x{1F3FC}\x{200D}[\x{2695}\x{2696}\x{2708}]|\x{200D}[\x{2695}\x{2696}\x{2708}])\x{FE0F}|\x{200D}(?:[\x{1F468}\x{1F469}]\x{200D}[\x{1F466}\x{1F467}]|[\x{1F466}\x{1F467}])|\x{1F3FF}\x{200D}[\x{2695}\x{2696}\x{2708}]|\x{1F3FE}\x{200D}[\x{2695}\x{2696}\x{2708}]|\x{1F3FD}\x{200D}[\x{2695}\x{2696}\x{2708}]|\x{1F3FC}\x{200D}[\x{2695}\x{2696}\x{2708}]|\x{1F3FF}|\x{1F3FE}|\x{1F3FD}|\x{1F3FC}|\x{200D}[\x{2695}\x{2696}\x{2708}])?|(?:\x{1F469}(?:\x{1F3FB}\x{200D}\x{2764}(?:\x{FE0F}\x{200D}(?:\x{1F48B}\x{200D}[\x{1F468}\x{1F469}]|[\x{1F468}\x{1F469}])|\x{200D}(?:\x{1F48B}\x{200D}[\x{1F468}\x{1F469}]|[\x{1F468}\x{1F469}]))|[\x{1F3FC}-\x{1F3FF}]\x{200D}\x{2764}(?:\x{FE0F}\x{200D}(?:\x{1F48B}\x{200D}[\x{1F468}\x{1F469}]|[\x{1F468}\x{1F469}])|\x{200D}(?:\x{1F48B}\x{200D}[\x{1F468}\x{1F469}]|[\x{1F468}\x{1F469}])))|\x{1F9D1}[\x{1F3FB}-\x{1F3FF}]\x{200D}\x{1F91D}\x{200D}\x{1F9D1})[\x{1F3FB}-\x{1F3FF}]|\x{1F469}\x{200D}\x{1F469}\x{200D}(?:\x{1F466}\x{200D}\x{1F466}|\x{1F467}\x{200D}[\x{1F466}\x{1F467}])|\x{1F469}(?:\x{200D}(?:\x{2764}(?:\x{FE0F}\x{200D}(?:\x{1F48B}\x{200D}[\x{1F468}\x{1F469}]|[\x{1F468}\x{1F469}])|\x{200D}(?:\x{1F48B}\x{200D}[\x{1F468}\x{1F469}]|[\x{1F468}\x{1F469}]))|[\x{1F33E}\x{1F373}\x{1F37C}\x{1F393}\x{1F3A4}\x{1F3A8}\x{1F3EB}\x{1F3ED}\x{1F4BB}\x{1F4BC}\x{1F527}\x{1F52C}\x{1F680}\x{1F692}\x{1F9AF}-\x{1F9B3}\x{1F9BC}\x{1F9BD}])|\x{1F3FF}\x{200D}[\x{1F33E}\x{1F373}\x{1F37C}\x{1F393}\x{1F3A4}\x{1F3A8}\x{1F3EB}\x{1F3ED}\x{1F4BB}\x{1F4BC}\x{1F527}\x{1F52C}\x{1F680}\x{1F692}\x{1F9AF}-\x{1F9B3}\x{1F9BC}\x{1F9BD}]|\x{1F3FE}\x{200D}[\x{1F33E}\x{1F373}\x{1F37C}\x{1F393}\x{1F3A4}\x{1F3A8}\x{1F3EB}\x{1F3ED}\x{1F4BB}\x{1F4BC}\x{1F527}\x{1F52C}\x{1F680}\x{1F692}\x{1F9AF}-\x{1F9B3}\x{1F9BC}\x{1F9BD}]|\x{1F3FD}\x{200D}[\x{1F33E}\x{1F373}\x{1F37C}\x{1F393}\x{1F3A4}\x{1F3A8}\x{1F3EB}\x{1F3ED}\x{1F4BB}\x{1F4BC}\x{1F527}\x{1F52C}\x{1F680}\x{1F692}\x{1F9AF}-\x{1F9B3}\x{1F9BC}\x{1F9BD}]|\x{1F3FC}\x{200D}[\x{1F33E}\x{1F373}\x{1F37C}\x{1F393}\x{1F3A4}\x{1F3A8}\x{1F3EB}\x{1F3ED}\x{1F4BB}\x{1F4BC}\x{1F527}\x{1F52C}\x{1F680}\x{1F692}\x{1F9AF}-\x{1F9B3}\x{1F9BC}\x{1F9BD}]|\x{1F3FB}\x{200D}[\x{1F33E}\x{1F373}\x{1F37C}\x{1F393}\x{1F3A4}\x{1F3A8}\x{1F3EB}\x{1F3ED}\x{1F4BB}\x{1F4BC}\x{1F527}\x{1F52C}\x{1F680}\x{1F692}\x{1F9AF}-\x{1F9B3}\x{1F9BC}\x{1F9BD}])|\x{1F9D1}(?:\x{200D}(?:\x{1F91D}\x{200D}\x{1F9D1}|[\x{1F33E}\x{1F373}\x{1F37C}\x{1F384}\x{1F393}\x{1F3A4}\x{1F3A8}\x{1F3EB}\x{1F3ED}\x{1F4BB}\x{1F4BC}\x{1F527}\x{1F52C}\x{1F680}\x{1F692}\x{1F9AF}-\x{1F9B3}\x{1F9BC}\x{1F9BD}])|\x{1F3FF}\x{200D}[\x{1F33E}\x{1F373}\x{1F37C}\x{1F384}\x{1F393}\x{1F3A4}\x{1F3A8}\x{1F3EB}\x{1F3ED}\x{1F4BB}\x{1F4BC}\x{1F527}\x{1F52C}\x{1F680}\x{1F692}\x{1F9AF}-\x{1F9B3}\x{1F9BC}\x{1F9BD}]|\x{1F3FE}\x{200D}[\x{1F33E}\x{1F373}\x{1F37C}\x{1F384}\x{1F393}\x{1F3A4}\x{1F3A8}\x{1F3EB}\x{1F3ED}\x{1F4BB}\x{1F4BC}\x{1F527}\x{1F52C}\x{1F680}\x{1F692}\x{1F9AF}-\x{1F9B3}\x{1F9BC}\x{1F9BD}]|\x{1F3FD}\x{200D}[\x{1F33E}\x{1F373}\x{1F37C}\x{1F384}\x{1F393}\x{1F3A4}\x{1F3A8}\x{1F3EB}\x{1F3ED}\x{1F4BB}\x{1F4BC}\x{1F527}\x{1F52C}\x{1F680}\x{1F692}\x{1F9AF}-\x{1F9B3}\x{1F9BC}\x{1F9BD}]|\x{1F3FC}\x{200D}[\x{1F33E}\x{1F373}\x{1F37C}\x{1F384}\x{1F393}\x{1F3A4}\x{1F3A8}\x{1F3EB}\x{1F3ED}\x{1F4BB}\x{1F4BC}\x{1F527}\x{1F52C}\x{1F680}\x{1F692}\x{1F9AF}-\x{1F9B3}\x{1F9BC}\x{1F9BD}]|\x{1F3FB}\x{200D}[\x{1F33E}\x{1F373}\x{1F37C}\x{1F384}\x{1F393}\x{1F3A4}\x{1F3A8}\x{1F3EB}\x{1F3ED}\x{1F4BB}\x{1F4BC}\x{1F527}\x{1F52C}\x{1F680}\x{1F692}\x{1F9AF}-\x{1F9B3}\x{1F9BC}\x{1F9BD}])|\x{1F469}\x{200D}\x{1F466}\x{200D}\x{1F466}|\x{1F469}\x{200D}\x{1F469}\x{200D}[\x{1F466}\x{1F467}]|\x{1F469}\x{200D}\x{1F467}\x{200D}[\x{1F466}\x{1F467}]|(?:\x{1F441}\x{FE0F}?\x{200D}\x{1F5E8}|\x{1F9D1}(?:\x{1F3FF}\x{200D}[\x{2695}\x{2696}\x{2708}]|\x{1F3FE}\x{200D}[\x{2695}\x{2696}\x{2708}]|\x{1F3FD}\x{200D}[\x{2695}\x{2696}\x{2708}]|\x{1F3FC}\x{200D}[\x{2695}\x{2696}\x{2708}]|\x{1F3FB}\x{200D}[\x{2695}\x{2696}\x{2708}]|\x{200D}[\x{2695}\x{2696}\x{2708}])|\x{1F469}(?:\x{1F3FF}\x{200D}[\x{2695}\x{2696}\x{2708}]|\x{1F3FE}\x{200D}[\x{2695}\x{2696}\x{2708}]|\x{1F3FD}\x{200D}[\x{2695}\x{2696}\x{2708}]|\x{1F3FC}\x{200D}[\x{2695}\x{2696}\x{2708}]|\x{1F3FB}\x{200D}[\x{2695}\x{2696}\x{2708}]|\x{200D}[\x{2695}\x{2696}\x{2708}])|\x{1F636}\x{200D}\x{1F32B}|\x{1F3F3}\x{FE0F}?\x{200D}\x{26A7}|\x{1F43B}\x{200D}\x{2744}|(?:[\x{1F3C3}\x{1F3C4}\x{1F3CA}\x{1F46E}\x{1F470}\x{1F471}\x{1F473}\x{1F477}\x{1F481}\x{1F482}\x{1F486}\x{1F487}\x{1F645}-\x{1F647}\x{1F64B}\x{1F64D}\x{1F64E}\x{1F6A3}\x{1F6B4}-\x{1F6B6}\x{1F926}\x{1F935}\x{1F937}-\x{1F939}\x{1F93D}\x{1F93E}\x{1F9B8}\x{1F9B9}\x{1F9CD}-\x{1F9CF}\x{1F9D4}\x{1F9D6}-\x{1F9DD}][\x{1F3FB}-\x{1F3FF}]|[\x{1F46F}\x{1F9DE}\x{1F9DF}])\x{200D}[\x{2640}\x{2642}]|[\x{26F9}\x{1F3CB}\x{1F3CC}\x{1F575}](?:[\x{FE0F}\x{1F3FB}-\x{1F3FF}]\x{200D}[\x{2640}\x{2642}]|\x{200D}[\x{2640}\x{2642}])|\x{1F3F4}\x{200D}\x{2620}|[\x{1F3C3}\x{1F3C4}\x{1F3CA}\x{1F46E}\x{1F470}\x{1F471}\x{1F473}\x{1F477}\x{1F481}\x{1F482}\x{1F486}\x{1F487}\x{1F645}-\x{1F647}\x{1F64B}\x{1F64D}\x{1F64E}\x{1F6A3}\x{1F6B4}-\x{1F6B6}\x{1F926}\x{1F935}\x{1F937}-\x{1F939}\x{1F93C}-\x{1F93E}\x{1F9B8}\x{1F9B9}\x{1F9CD}-\x{1F9CF}\x{1F9D4}\x{1F9D6}-\x{1F9DD}]\x{200D}[\x{2640}\x{2642}]|[\xA9\xAE\x{203C}\x{2049}\x{2122}\x{2139}\x{2194}-\x{2199}\x{21A9}\x{21AA}\x{231A}\x{231B}\x{2328}\x{23CF}\x{23ED}-\x{23EF}\x{23F1}\x{23F2}\x{23F8}-\x{23FA}\x{24C2}\x{25AA}\x{25AB}\x{25B6}\x{25C0}\x{25FB}\x{25FC}\x{25FE}\x{2600}-\x{2604}\x{260E}\x{2611}\x{2614}\x{2615}\x{2618}\x{2620}\x{2622}\x{2623}\x{2626}\x{262A}\x{262E}\x{262F}\x{2638}-\x{263A}\x{2640}\x{2642}\x{2648}-\x{2653}\x{265F}\x{2660}\x{2663}\x{2665}\x{2666}\x{2668}\x{267B}\x{267E}\x{267F}\x{2692}\x{2694}-\x{2697}\x{2699}\x{269B}\x{269C}\x{26A0}\x{26A7}\x{26AA}\x{26B0}\x{26B1}\x{26BD}\x{26BE}\x{26C4}\x{26C8}\x{26CF}\x{26D1}\x{26D3}\x{26E9}\x{26F0}-\x{26F5}\x{26F7}\x{26F8}\x{26FA}\x{2702}\x{2708}\x{2709}\x{270F}\x{2712}\x{2714}\x{2716}\x{271D}\x{2721}\x{2733}\x{2734}\x{2744}\x{2747}\x{2763}\x{27A1}\x{2934}\x{2935}\x{2B05}-\x{2B07}\x{2B1B}\x{2B1C}\x{2B55}\x{3030}\x{303D}\x{3297}\x{3299}\x{1F004}\x{1F170}\x{1F171}\x{1F17E}\x{1F17F}\x{1F202}\x{1F237}\x{1F321}\x{1F324}-\x{1F32C}\x{1F336}\x{1F37D}\x{1F396}\x{1F397}\x{1F399}-\x{1F39B}\x{1F39E}\x{1F39F}\x{1F3CD}\x{1F3CE}\x{1F3D4}-\x{1F3DF}\x{1F3F5}\x{1F3F7}\x{1F43F}\x{1F4FD}\x{1F549}\x{1F54A}\x{1F56F}\x{1F570}\x{1F573}\x{1F576}-\x{1F579}\x{1F587}\x{1F58A}-\x{1F58D}\x{1F5A5}\x{1F5A8}\x{1F5B1}\x{1F5B2}\x{1F5BC}\x{1F5C2}-\x{1F5C4}\x{1F5D1}-\x{1F5D3}\x{1F5DC}-\x{1F5DE}\x{1F5E1}\x{1F5E3}\x{1F5E8}\x{1F5EF}\x{1F5F3}\x{1F5FA}\x{1F6CB}\x{1F6CD}-\x{1F6CF}\x{1F6E0}-\x{1F6E5}\x{1F6E9}\x{1F6F0}\x{1F6F3}])\x{FE0F}|\x{1F441}\x{FE0F}?\x{200D}\x{1F5E8}|\x{1F9D1}(?:\x{1F3FF}\x{200D}[\x{2695}\x{2696}\x{2708}]|\x{1F3FE}\x{200D}[\x{2695}\x{2696}\x{2708}]|\x{1F3FD}\x{200D}[\x{2695}\x{2696}\x{2708}]|\x{1F3FC}\x{200D}[\x{2695}\x{2696}\x{2708}]|\x{1F3FB}\x{200D}[\x{2695}\x{2696}\x{2708}]|\x{200D}[\x{2695}\x{2696}\x{2708}])|\x{1F469}(?:\x{1F3FF}\x{200D}[\x{2695}\x{2696}\x{2708}]|\x{1F3FE}\x{200D}[\x{2695}\x{2696}\x{2708}]|\x{1F3FD}\x{200D}[\x{2695}\x{2696}\x{2708}]|\x{1F3FC}\x{200D}[\x{2695}\x{2696}\x{2708}]|\x{1F3FB}\x{200D}[\x{2695}\x{2696}\x{2708}]|\x{200D}[\x{2695}\x{2696}\x{2708}])|\x{1F3F3}\x{FE0F}?\x{200D}\x{1F308}|\x{1F469}\x{200D}\x{1F467}|\x{1F469}\x{200D}\x{1F466}|\x{1F636}\x{200D}\x{1F32B}|\x{1F3F3}\x{FE0F}?\x{200D}\x{26A7}|\x{1F635}\x{200D}\x{1F4AB}|\x{1F62E}\x{200D}\x{1F4A8}|\x{1F415}\x{200D}\x{1F9BA}|\x{1FAF1}(?:\x{1F3FF}|\x{1F3FE}|\x{1F3FD}|\x{1F3FC}|\x{1F3FB})?|\x{1F9D1}(?:\x{1F3FF}|\x{1F3FE}|\x{1F3FD}|\x{1F3FC}|\x{1F3FB})?|\x{1F469}(?:\x{1F3FF}|\x{1F3FE}|\x{1F3FD}|\x{1F3FC}|\x{1F3FB})?|\x{1F43B}\x{200D}\x{2744}|(?:[\x{1F3C3}\x{1F3C4}\x{1F3CA}\x{1F46E}\x{1F470}\x{1F471}\x{1F473}\x{1F477}\x{1F481}\x{1F482}\x{1F486}\x{1F487}\x{1F645}-\x{1F647}\x{1F64B}\x{1F64D}\x{1F64E}\x{1F6A3}\x{1F6B4}-\x{1F6B6}\x{1F926}\x{1F935}\x{1F937}-\x{1F939}\x{1F93D}\x{1F93E}\x{1F9B8}\x{1F9B9}\x{1F9CD}-\x{1F9CF}\x{1F9D4}\x{1F9D6}-\x{1F9DD}][\x{1F3FB}-\x{1F3FF}]|[\x{1F46F}\x{1F9DE}\x{1F9DF}])\x{200D}[\x{2640}\x{2642}]|[\x{26F9}\x{1F3CB}\x{1F3CC}\x{1F575}](?:[\x{FE0F}\x{1F3FB}-\x{1F3FF}]\x{200D}[\x{2640}\x{2642}]|\x{200D}[\x{2640}\x{2642}])|\x{1F3F4}\x{200D}\x{2620}|\x{1F1FD}\x{1F1F0}|\x{1F1F6}\x{1F1E6}|\x{1F1F4}\x{1F1F2}|\x{1F408}\x{200D}\x{2B1B}|\x{2764}(?:\x{FE0F}\x{200D}[\x{1F525}\x{1FA79}]|\x{200D}[\x{1F525}\x{1FA79}])|\x{1F441}\x{FE0F}?|\x{1F3F3}\x{FE0F}?|[\x{1F3C3}\x{1F3C4}\x{1F3CA}\x{1F46E}\x{1F470}\x{1F471}\x{1F473}\x{1F477}\x{1F481}\x{1F482}\x{1F486}\x{1F487}\x{1F645}-\x{1F647}\x{1F64B}\x{1F64D}\x{1F64E}\x{1F6A3}\x{1F6B4}-\x{1F6B6}\x{1F926}\x{1F935}\x{1F937}-\x{1F939}\x{1F93C}-\x{1F93E}\x{1F9B8}\x{1F9B9}\x{1F9CD}-\x{1F9CF}\x{1F9D4}\x{1F9D6}-\x{1F9DD}]\x{200D}[\x{2640}\x{2642}]|\x{1F1FF}[\x{1F1E6}\x{1F1F2}\x{1F1FC}]|\x{1F1FE}[\x{1F1EA}\x{1F1F9}]|\x{1F1FC}[\x{1F1EB}\x{1F1F8}]|\x{1F1FB}[\x{1F1E6}\x{1F1E8}\x{1F1EA}\x{1F1EC}\x{1F1EE}\x{1F1F3}\x{1F1FA}]|\x{1F1FA}[\x{1F1E6}\x{1F1EC}\x{1F1F2}\x{1F1F3}\x{1F1F8}\x{1F1FE}\x{1F1FF}]|\x{1F1F9}[\x{1F1E6}\x{1F1E8}\x{1F1E9}\x{1F1EB}-\x{1F1ED}\x{1F1EF}-\x{1F1F4}\x{1F1F7}\x{1F1F9}\x{1F1FB}\x{1F1FC}\x{1F1FF}]|\x{1F1F8}[\x{1F1E6}-\x{1F1EA}\x{1F1EC}-\x{1F1F4}\x{1F1F7}-\x{1F1F9}\x{1F1FB}\x{1F1FD}-\x{1F1FF}]|\x{1F1F7}[\x{1F1EA}\x{1F1F4}\x{1F1F8}\x{1F1FA}\x{1F1FC}]|\x{1F1F5}[\x{1F1E6}\x{1F1EA}-\x{1F1ED}\x{1F1F0}-\x{1F1F3}\x{1F1F7}-\x{1F1F9}\x{1F1FC}\x{1F1FE}]|\x{1F1F3}[\x{1F1E6}\x{1F1E8}\x{1F1EA}-\x{1F1EC}\x{1F1EE}\x{1F1F1}\x{1F1F4}\x{1F1F5}\x{1F1F7}\x{1F1FA}\x{1F1FF}]|\x{1F1F2}[\x{1F1E6}\x{1F1E8}-\x{1F1ED}\x{1F1F0}-\x{1F1FF}]|\x{1F1F1}[\x{1F1E6}-\x{1F1E8}\x{1F1EE}\x{1F1F0}\x{1F1F7}-\x{1F1FB}\x{1F1FE}]|\x{1F1F0}[\x{1F1EA}\x{1F1EC}-\x{1F1EE}\x{1F1F2}\x{1F1F3}\x{1F1F5}\x{1F1F7}\x{1F1FC}\x{1F1FE}\x{1F1FF}]|\x{1F1EF}[\x{1F1EA}\x{1F1F2}\x{1F1F4}\x{1F1F5}]|\x{1F1EE}[\x{1F1E8}-\x{1F1EA}\x{1F1F1}-\x{1F1F4}\x{1F1F6}-\x{1F1F9}]|\x{1F1ED}[\x{1F1F0}\x{1F1F2}\x{1F1F3}\x{1F1F7}\x{1F1F9}\x{1F1FA}]|\x{1F1EC}[\x{1F1E6}\x{1F1E7}\x{1F1E9}-\x{1F1EE}\x{1F1F1}-\x{1F1F3}\x{1F1F5}-\x{1F1FA}\x{1F1FC}\x{1F1FE}]|\x{1F1EB}[\x{1F1EE}-\x{1F1F0}\x{1F1F2}\x{1F1F4}\x{1F1F7}]|\x{1F1EA}[\x{1F1E6}\x{1F1E8}\x{1F1EA}\x{1F1EC}\x{1F1ED}\x{1F1F7}-\x{1F1FA}]|\x{1F1E9}[\x{1F1EA}\x{1F1EC}\x{1F1EF}\x{1F1F0}\x{1F1F2}\x{1F1F4}\x{1F1FF}]|\x{1F1E8}[\x{1F1E6}\x{1F1E8}\x{1F1E9}\x{1F1EB}-\x{1F1EE}\x{1F1F0}-\x{1F1F5}\x{1F1F7}\x{1F1FA}-\x{1F1FF}]|\x{1F1E7}[\x{1F1E6}\x{1F1E7}\x{1F1E9}-\x{1F1EF}\x{1F1F1}-\x{1F1F4}\x{1F1F6}-\x{1F1F9}\x{1F1FB}\x{1F1FC}\x{1F1FE}\x{1F1FF}]|\x{1F1E6}[\x{1F1E8}-\x{1F1EC}\x{1F1EE}\x{1F1F1}\x{1F1F2}\x{1F1F4}\x{1F1F6}-\x{1F1FA}\x{1F1FC}\x{1F1FD}\x{1F1FF}]|[#\*0-9]\x{FE0F}?\x{20E3}|\x{1F93C}[\x{1F3FB}-\x{1F3FF}]|\x{2764}\x{FE0F}?|[\x{1F3C3}\x{1F3C4}\x{1F3CA}\x{1F46E}\x{1F470}\x{1F471}\x{1F473}\x{1F477}\x{1F481}\x{1F482}\x{1F486}\x{1F487}\x{1F645}-\x{1F647}\x{1F64B}\x{1F64D}\x{1F64E}\x{1F6A3}\x{1F6B4}-\x{1F6B6}\x{1F926}\x{1F935}\x{1F937}-\x{1F939}\x{1F93D}\x{1F93E}\x{1F9B8}\x{1F9B9}\x{1F9CD}-\x{1F9CF}\x{1F9D4}\x{1F9D6}-\x{1F9DD}][\x{1F3FB}-\x{1F3FF}]|[\x{26F9}\x{1F3CB}\x{1F3CC}\x{1F575}][\x{FE0F}\x{1F3FB}-\x{1F3FF}]?|\x{1F3F4}|[\x{270A}\x{270B}\x{1F385}\x{1F3C2}\x{1F3C7}\x{1F442}\x{1F443}\x{1F446}-\x{1F450}\x{1F466}\x{1F467}\x{1F46B}-\x{1F46D}\x{1F472}\x{1F474}-\x{1F476}\x{1F478}\x{1F47C}\x{1F483}\x{1F485}\x{1F48F}\x{1F491}\x{1F4AA}\x{1F57A}\x{1F595}\x{1F596}\x{1F64C}\x{1F64F}\x{1F6C0}\x{1F6CC}\x{1F90C}\x{1F90F}\x{1F918}-\x{1F91F}\x{1F930}-\x{1F934}\x{1F936}\x{1F977}\x{1F9B5}\x{1F9B6}\x{1F9BB}\x{1F9D2}\x{1F9D3}\x{1F9D5}\x{1FAC3}-\x{1FAC5}\x{1FAF0}\x{1FAF2}-\x{1FAF6}][\x{1F3FB}-\x{1F3FF}]|[\x{261D}\x{270C}\x{270D}\x{1F574}\x{1F590}][\x{FE0F}\x{1F3FB}-\x{1F3FF}]|[\x{261D}\x{270A}-\x{270D}\x{1F385}\x{1F3C2}\x{1F3C7}\x{1F408}\x{1F415}\x{1F43B}\x{1F442}\x{1F443}\x{1F446}-\x{1F450}\x{1F466}\x{1F467}\x{1F46B}-\x{1F46D}\x{1F472}\x{1F474}-\x{1F476}\x{1F478}\x{1F47C}\x{1F483}\x{1F485}\x{1F48F}\x{1F491}\x{1F4AA}\x{1F574}\x{1F57A}\x{1F590}\x{1F595}\x{1F596}\x{1F62E}\x{1F635}\x{1F636}\x{1F64C}\x{1F64F}\x{1F6C0}\x{1F6CC}\x{1F90C}\x{1F90F}\x{1F918}-\x{1F91F}\x{1F930}-\x{1F934}\x{1F936}\x{1F93C}\x{1F977}\x{1F9B5}\x{1F9B6}\x{1F9BB}\x{1F9D2}\x{1F9D3}\x{1F9D5}\x{1FAC3}-\x{1FAC5}\x{1FAF0}\x{1FAF2}-\x{1FAF6}]|[\x{1F3C3}\x{1F3C4}\x{1F3CA}\x{1F46E}\x{1F470}\x{1F471}\x{1F473}\x{1F477}\x{1F481}\x{1F482}\x{1F486}\x{1F487}\x{1F645}-\x{1F647}\x{1F64B}\x{1F64D}\x{1F64E}\x{1F6A3}\x{1F6B4}-\x{1F6B6}\x{1F926}\x{1F935}\x{1F937}-\x{1F939}\x{1F93D}\x{1F93E}\x{1F9B8}\x{1F9B9}\x{1F9CD}-\x{1F9CF}\x{1F9D4}\x{1F9D6}-\x{1F9DD}]|[\x{1F46F}\x{1F9DE}\x{1F9DF}]|[\xA9\xAE\x{203C}\x{2049}\x{2122}\x{2139}\x{2194}-\x{2199}\x{21A9}\x{21AA}\x{231A}\x{231B}\x{2328}\x{23CF}\x{23ED}-\x{23EF}\x{23F1}\x{23F2}\x{23F8}-\x{23FA}\x{24C2}\x{25AA}\x{25AB}\x{25B6}\x{25C0}\x{25FB}\x{25FC}\x{25FE}\x{2600}-\x{2604}\x{260E}\x{2611}\x{2614}\x{2615}\x{2618}\x{2620}\x{2622}\x{2623}\x{2626}\x{262A}\x{262E}\x{262F}\x{2638}-\x{263A}\x{2640}\x{2642}\x{2648}-\x{2653}\x{265F}\x{2660}\x{2663}\x{2665}\x{2666}\x{2668}\x{267B}\x{267E}\x{267F}\x{2692}\x{2694}-\x{2697}\x{2699}\x{269B}\x{269C}\x{26A0}\x{26A7}\x{26AA}\x{26B0}\x{26B1}\x{26BD}\x{26BE}\x{26C4}\x{26C8}\x{26CF}\x{26D1}\x{26D3}\x{26E9}\x{26F0}-\x{26F5}\x{26F7}\x{26F8}\x{26FA}\x{2702}\x{2708}\x{2709}\x{270F}\x{2712}\x{2714}\x{2716}\x{271D}\x{2721}\x{2733}\x{2734}\x{2744}\x{2747}\x{2763}\x{27A1}\x{2934}\x{2935}\x{2B05}-\x{2B07}\x{2B1B}\x{2B1C}\x{2B55}\x{3030}\x{303D}\x{3297}\x{3299}\x{1F004}\x{1F170}\x{1F171}\x{1F17E}\x{1F17F}\x{1F202}\x{1F237}\x{1F321}\x{1F324}-\x{1F32C}\x{1F336}\x{1F37D}\x{1F396}\x{1F397}\x{1F399}-\x{1F39B}\x{1F39E}\x{1F39F}\x{1F3CD}\x{1F3CE}\x{1F3D4}-\x{1F3DF}\x{1F3F5}\x{1F3F7}\x{1F43F}\x{1F4FD}\x{1F549}\x{1F54A}\x{1F56F}\x{1F570}\x{1F573}\x{1F576}-\x{1F579}\x{1F587}\x{1F58A}-\x{1F58D}\x{1F5A5}\x{1F5A8}\x{1F5B1}\x{1F5B2}\x{1F5BC}\x{1F5C2}-\x{1F5C4}\x{1F5D1}-\x{1F5D3}\x{1F5DC}-\x{1F5DE}\x{1F5E1}\x{1F5E3}\x{1F5E8}\x{1F5EF}\x{1F5F3}\x{1F5FA}\x{1F6CB}\x{1F6CD}-\x{1F6CF}\x{1F6E0}-\x{1F6E5}\x{1F6E9}\x{1F6F0}\x{1F6F3}]|[\x{23E9}-\x{23EC}\x{23F0}\x{23F3}\x{25FD}\x{2693}\x{26A1}\x{26AB}\x{26C5}\x{26CE}\x{26D4}\x{26EA}\x{26FD}\x{2705}\x{2728}\x{274C}\x{274E}\x{2753}-\x{2755}\x{2757}\x{2795}-\x{2797}\x{27B0}\x{27BF}\x{2B50}\x{1F0CF}\x{1F18E}\x{1F191}-\x{1F19A}\x{1F201}\x{1F21A}\x{1F22F}\x{1F232}-\x{1F236}\x{1F238}-\x{1F23A}\x{1F250}\x{1F251}\x{1F300}-\x{1F320}\x{1F32D}-\x{1F335}\x{1F337}-\x{1F37C}\x{1F37E}-\x{1F384}\x{1F386}-\x{1F393}\x{1F3A0}-\x{1F3C1}\x{1F3C5}\x{1F3C6}\x{1F3C8}\x{1F3C9}\x{1F3CF}-\x{1F3D3}\x{1F3E0}-\x{1F3F0}\x{1F3F8}-\x{1F407}\x{1F409}-\x{1F414}\x{1F416}-\x{1F43A}\x{1F43C}-\x{1F43E}\x{1F440}\x{1F444}\x{1F445}\x{1F451}-\x{1F465}\x{1F46A}\x{1F479}-\x{1F47B}\x{1F47D}-\x{1F480}\x{1F484}\x{1F488}-\x{1F48E}\x{1F490}\x{1F492}-\x{1F4A9}\x{1F4AB}-\x{1F4FC}\x{1F4FF}-\x{1F53D}\x{1F54B}-\x{1F54E}\x{1F550}-\x{1F567}\x{1F5A4}\x{1F5FB}-\x{1F62D}\x{1F62F}-\x{1F634}\x{1F637}-\x{1F644}\x{1F648}-\x{1F64A}\x{1F680}-\x{1F6A2}\x{1F6A4}-\x{1F6B3}\x{1F6B7}-\x{1F6BF}\x{1F6C1}-\x{1F6C5}\x{1F6D0}-\x{1F6D2}\x{1F6D5}-\x{1F6D7}\x{1F6DD}-\x{1F6DF}\x{1F6EB}\x{1F6EC}\x{1F6F4}-\x{1F6FC}\x{1F7E0}-\x{1F7EB}\x{1F7F0}\x{1F90D}\x{1F90E}\x{1F910}-\x{1F917}\x{1F920}-\x{1F925}\x{1F927}-\x{1F92F}\x{1F93A}\x{1F93F}-\x{1F945}\x{1F947}-\x{1F976}\x{1F978}-\x{1F9B4}\x{1F9B7}\x{1F9BA}\x{1F9BC}-\x{1F9CC}\x{1F9D0}\x{1F9E0}-\x{1F9FF}\x{1FA70}-\x{1FA74}\x{1FA78}-\x{1FA7C}\x{1FA80}-\x{1FA86}\x{1FA90}-\x{1FAAC}\x{1FAB0}-\x{1FABA}\x{1FAC0}-\x{1FAC2}\x{1FAD0}-\x{1FAD9}\x{1FAE0}-\x{1FAE7}]/u', '', $text);


}

/**
 * Возвращает структуру таблицы БД в формате массива
 * имя поля => тип данных
 *
 * @param $table
 * @return array
 */
function db_columns_types($table): array {

	$database = $GLOBALS['database'];
	$db       = $GLOBALS['db'];

	return (array)$db -> getIndCol("COLUMN_NAME", "
		SELECT DATA_TYPE, COLUMN_NAME
		FROM information_schema.columns 
		WHERE table_schema = '$database' 
		AND table_name = '$table'
	");

}

/**
 * Возвращает массив в котором только указанные поля
 *
 * @param $input
 * @param $allowed
 * @return mixed
 */
function FilterArray($input, $allowed): array {

	$newarr = [];

	foreach ($input as $item) {

		$newitem = [];

		foreach ($item as $key => $value) {

			if (in_array($key, $allowed)) {
				$newitem[$key] = $value;
			}

		}

		$newarr[] = $newitem;

	}

	return $newarr;

}

/**
 * Очистка от говна в соответствие с заданными типами полей
 *
 * @param       $arr
 * @param array $rule
 * @return mixed
 */
function arrayCleanold($arr, array $rule = []) {

	foreach ($arr as $key => $value) {

		$type = $rule[$key];

		switch ($type) {

			case "integer":

				$arr[$key] = (int)$value;

				break;
			case "string":

				$arr[$key] = is_null($value) ? NULL : untag($value);

				break;
			case "original":
			case "url":

				$arr[$key] = is_null($value) ? NULL : $value;

				break;
			case "html":

				$arr[$key] = is_null($value) ? NULL : htmlspecialchars($value);

				break;
			case "phone":

				$arr[$key] = prepareMobPhone(untag($value));

				break;
			case "float":

				$arr[$key] = pre_format(untag($value));

				break;
			case "bool":

				$arr[$key] = $value == "false" || !$value ? "false" : "true";

				break;


		}

	}

	return $arr;

}

/**
 * Очистка и приведение данных в соответствие с правилами
 * @param $arr
 * @param array $rule
 * @return mixed
 */
function arrayClean($arr, array $rule = []) {

	foreach ($arr as $key => $value) {

		$type = $rule[$key];

		switch ($type) {

			case "integer":
			case "int":

				$arr[$key] = (int)$value;

				break;
			case "string":
			case "varchar":

				$arr[$key] = is_null($value) ? NULL : untag($value);

				if($key == 'iduser'){
					$arr[$key] = (int)$value;
				}

				break;
			case "original":
			case "url":

				$arr[$key] = is_null($value) ? NULL : $value;

				break;
			case "html":

				$arr[$key] = is_null($value) ? NULL : htmlspecialchars($value);

				break;
			case "phone":

				$arr[$key] = prepareMobPhone(untag($value));

				break;
			case "float":
			case "double":

				$arr[$key] = pre_format(untag($value));

				break;
			case "bool":

				$arr[$key] = $value == "false" || !$value ? "false" : "true";

				break;


		}

	}

	return $arr;

}

/**
 * Приведение типа данных к данным таблицы в БД
 *
 * @param array $array
 * @param string|null $dbtable
 *
 * @return void
 */
function data2dbtypes(array $array = [], string $dbtable = NULL): array {

	if (empty($dbtable)) {
		return $array;
	}

	$structure = db_columns_types($dbtable);

	return arrayClean($array, $structure);

}

/**
 * Получение данных с помощью функции file_get_contents
 * Позволяет получать данные там, где запрещается сервисом
 * Например, Dadata не позволяет получать данные по curl
 * https://dadata.userecho.com/communities/1/topics/1201-podskazki-oshibka-v-otvete-rest-familyclient_errorreasonbad-requestmessageunexpected-character
 *
 * @param            $url - адрес
 * @param string|array|null $postdata - массив отправляемых данных
 * @param array|null $headers - массив заголовков
 * @param string $format - формат данных (json, form)
 * @param string $method - метод отправки (POST - по умолчанию, GET)
 *
 * @return false|string
 *
 * Пример:
 * ```php
 * $dadataurl = "https://suggestions.dadata.ru/suggestions/api/4_1/rs/iplocate/address";
 * $response = sendRequestStream( $dadataurl, ['ip' => '94.25.100.7'], [
 *      'Accept'        => 'application/json',
 *      'Authorization' => 'Token 084355515b46bf12c598bf1258d632283544bce28'
 * ], 'json', 'POST' );
 * ```
 * @category Core
 * @package  Func
 */
function sendRequestStream($url, $postdata = NULL, array $headers = NULL, string $format = 'json', string $method = 'POST') {

	$header  = [];
	$content = "";
	$format  = strtoupper($format);

	if ($format == 'JSON') {
		$header[] = "Content-Type: application/json";
	}

	elseif ($format == 'FORM') {
		$header[] = "Content-Type: application/x-www-form-urlencoded";
	}

	if (!empty($headers)) {

		foreach ($headers as $key => $head) {
			$header[] = $key.": ".$head;
		}

	}

	if (!empty($postdata)) {

		$content = ( is_array($postdata) ) ? ( $format == 'JSON' ? json_encode($postdata) : http_build_query($postdata) ) : $postdata;

	}

	$HTTP = [
		// Обертка, которая будет использоваться
		'http' => [
			// Request Method
			'method'  => $method,
			// Ниже задаются заголовки запроса
			'header'  => yimplode("\n", $header),
			'content' => $content
		],
		'ssl'  => [
			'verify_peer'       => false,
			'verify_peer_name'  => false,
			'allow_self_signed' => true
		]
	];

	//print_r($HTTP);

	$context = stream_context_create($HTTP);

	return file_get_contents($url, false, $context);

}

/**
 * Отправка данных через cURL
 *
 * @param            $url
 * @param null $postdata - массив отправляемых данных
 * @param string|array|null $header - массив заголовков
 * @param string $format - формат отправки данных
 *                             - json - отправлять в формате json (добавляет заголовок)
 *                             - form - отправлять как форму (Content-type: application/x-www-form-urlencoded;)
 *                             - иначе формат указать вручную в массиве заголовков
 * @param string $method - метод отправки (POST - по умолчанию, GET, PUT, PATCH)
 *                             - если GET, то данные обрабатываются http_build_query
 *
 * @return stdClass
 *                    - response
 *                    - info
 *                    - error
 *                    - headers
 * @category Core
 * @package  Func
 */
function SendRequestCurl($url, $postdata = NULL, array $header = NULL, string $format = 'json', string $method = 'POST'): stdClass {

	$result = new stdClass();

	$headers = [];
	$format  = strtoupper($format);

	if ($format == 'JSON') {
		$headers[] = "Content-Type: application/json";
	}
	elseif ($format == 'FORM') {
		$headers[] = "Content-Type: application/x-www-form-urlencoded";
	}

	if (!empty($header)) {

		foreach ($header as $key => $head) {
			$headers[] = $key.": ".$head;
		}

	}

	$ch = curl_init();

	curl_setopt($ch, CURLOPT_HEADER, 0);

	if (!empty($headers)) {
		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
	}

	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

	if ($method == 'POST') {

		$POST = ( is_array($postdata) ) ? ( $format == 'JSON' ? json_encode_cyr($postdata) : http_build_query($postdata) ) : $postdata;

		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $POST);

	}
	if ($method == 'GET') {

		$url .= !empty($postdata) ? '?'.http_build_query($postdata) : "";

	}
	if ($method == 'PUT') {

		$POST = is_array($postdata) ? ( $format == 'JSON' ? json_encode($postdata) : http_build_query($postdata) ) : $postdata;

		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
		curl_setopt($ch, CURLOPT_POSTFIELDS, $POST);

		//print $POST;

	}
	if ($method == 'PATCH') {

		$POST = is_array($postdata) ? ( $format == 'JSON' ? json_encode($postdata) : http_build_query($postdata) ) : $postdata;

		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PATCH");
		curl_setopt($ch, CURLOPT_POSTFIELDS, $POST);

		//print $POST;

	}

	curl_setopt($ch, CURLOPT_TIMEOUT, 100);
	curl_setopt($ch, CURLOPT_URL, $url);

	$result -> response = curl_exec($ch);
	$result -> info     = curl_getinfo($ch);
	$result -> error    = curl_error($ch);
	$result -> headers  = $headers;

	return $result;

}

/**
 * Принимает массив тэгов, где key = имя тэга, value = значение тэга
 * Если указан файл, то будет сгенерирован файл и возвращен полный абсолютный путь до файла
 * в противном случае вернет кодированную в base64 строку
 *
 * @param array $tags
 * @param int $size - размер изображения в пикселях (по умолчанию 400)
 * @param string $file - for example "/cash/qrcode-invoice.png"
 * @return string
 * @category Core
 * @package  Func
 */
function generateCustomQR(array $tags, int $size = 400, string $file = ''): string {

	$rootpath = dirname(__DIR__, 2);

	// qrcode
	$renderer = new ImageRenderer(new RendererStyle($size), new ImagickImageBackEnd());
	$writer   = new Writer($renderer);

	$strings = ['ST00012'];

	foreach ($tags as $name => $tag) {

		$strings[] = $name."=".trim(str_replace("”", "\"", $tag));

	}

	if (!isset($file) || $file == '') {

		$writer -> writeFile(implode("|", $strings), $rootpath.$file, "UTF-8");

		return $rootpath.$file;

	}

	return base64_encode($writer -> writeString(implode("|", $strings), "UTF-8"));

}

/**
 * Http-статусы
 *
 * @param $num
 * @return array
 * @category Core
 * @package  Func
 */
function HTTPStatus($num): array {
	$http = [
		100 => 'HTTP/1.1 100 Continue',
		101 => 'HTTP/1.1 101 Switching Protocols',
		200 => 'HTTP/1.1 200 OK',
		201 => 'HTTP/1.1 201 Created',
		202 => 'HTTP/1.1 202 Accepted',
		203 => 'HTTP/1.1 203 Non-Authoritative Information',
		204 => 'HTTP/1.1 204 No Content',
		205 => 'HTTP/1.1 205 Reset Content',
		206 => 'HTTP/1.1 206 Partial Content',
		300 => 'HTTP/1.1 300 Multiple Choices',
		301 => 'HTTP/1.1 301 Moved Permanently',
		302 => 'HTTP/1.1 302 Found',
		303 => 'HTTP/1.1 303 See Other',
		304 => 'HTTP/1.1 304 Not Modified',
		305 => 'HTTP/1.1 305 Use Proxy',
		307 => 'HTTP/1.1 307 Temporary Redirect',
		400 => 'HTTP/1.1 400 Bad Request',
		401 => 'HTTP/1.1 401 Unauthorized',
		402 => 'HTTP/1.1 402 Payment Required',
		403 => 'HTTP/1.1 403 Forbidden',
		404 => 'HTTP/1.1 404 Not Found',
		405 => 'HTTP/1.1 405 Method Not Allowed',
		406 => 'HTTP/1.1 406 Not Acceptable',
		407 => 'HTTP/1.1 407 Proxy Authentication Required',
		408 => 'HTTP/1.1 408 Request Time-out',
		409 => 'HTTP/1.1 409 Conflict',
		410 => 'HTTP/1.1 410 Gone',
		411 => 'HTTP/1.1 411 Length Required',
		412 => 'HTTP/1.1 412 Precondition Failed',
		413 => 'HTTP/1.1 413 Request Entity Too Large',
		414 => 'HTTP/1.1 414 Request-URI Too Large',
		415 => 'HTTP/1.1 415 Unsupported Media Type',
		416 => 'HTTP/1.1 416 Requested Range Not Satisfiable',
		417 => 'HTTP/1.1 417 Expectation Failed',
		500 => 'HTTP/1.1 500 Internal Server Error',
		501 => 'HTTP/1.1 501 Not Implemented',
		502 => 'HTTP/1.1 502 Bad Gateway',
		503 => 'HTTP/1.1 503 Service Unavailable',
		504 => 'HTTP/1.1 504 Gateway Time-out',
		505 => 'HTTP/1.1 505 HTTP Version Not Supported',
	];

	header($http[$num]);

	return [
		'code'  => $num,
		'error' => $http[$num],
	];
}

/**
 * Парсит данные из xls, xlsx, csv файлов
 * $file - файл с указанием полного абсолютного пути
 *
 * @throws \Exception
 * @category Core
 * @package  Func
 */
function parceExcel(string $file, int $fromrow = 1): array {

	$cur_ext = getExtention($file);
	$data    = [];

	if ($cur_ext == 'xls') {

		$xls = new SimpleXLS($file);
		if ($xls -> success()) {

			$datas = $xls -> rows();

			foreach ($datas as $k => $Row) {

				if ($k >= $fromrow) {

					foreach ($Row as $value) {

						if (DateTime ::createFromFormat('m-d-y', $value) !== false) {
							$data[$k][] = DateTime ::createFromFormat('m-d-y', $value) -> format("Y-m-d");
						}
						elseif (DateTime ::createFromFormat('d.m.Y', $value) !== false) {
							$data[$k][] = DateTime ::createFromFormat('d.m.Y', $value) -> format("Y-m-d");
						}
						elseif (DateTime ::createFromFormat('d-m-Y', $value) !== false) {
							$data[$k][] = DateTime ::createFromFormat('d-m-Y', $value) -> format("Y-m-d");
						}
						elseif (is_numeric($value)) {
							$data[$k][] = $value;
						}
						else {
							$data[$k][] = untag($value);
						}

					}

				}

			}

		}

	}
	elseif ($cur_ext == 'csv') {

		$datas = new SpreadsheetReader($file);
		$datas -> ChangeSheet(0);

		foreach ($datas as $k => $Row) {

			if ($k >= $fromrow) {

				foreach ($Row as $value) {

					if (DateTime ::createFromFormat('m-d-y', $value) !== false) {
						$data[$k][] = DateTime ::createFromFormat('m-d-y', $value) -> format("Y-m-d");
					}
					elseif (DateTime ::createFromFormat('d.m.Y', $value) !== false) {
						$data[$k][] = DateTime ::createFromFormat('d.m.Y', $value) -> format("Y-m-d");
					}
					elseif (DateTime ::createFromFormat('d-m-Y', $value) !== false) {
						$data[$k][] = DateTime ::createFromFormat('d-m-Y', $value) -> format("Y-m-d");
					}
					elseif (is_numeric($value)) {
						$data[$k][] = $value;
					}
					else {
						$data[$k][] = untag($value);
					}

				}

			}

		}

	}
	elseif ($cur_ext == 'xlsx') {

		if ($xlsx = SimpleXLSX ::parse($file)) {

			$datas = $xlsx -> rows();

			foreach ($datas as $k => $Row) {

				if ($k >= $fromrow) {

					foreach ($Row as $value) {

						if (DateTime ::createFromFormat('m-d-y', $value) !== false) {
							$data[$k][] = DateTime ::createFromFormat('m-d-y', $value) -> format("Y-m-d");
						}
						elseif (DateTime ::createFromFormat('d.m.Y', $value) !== false) {
							$data[$k][] = DateTime ::createFromFormat('d.m.Y', $value) -> format("Y-m-d");
						}
						elseif (DateTime ::createFromFormat('d-m-Y', $value) !== false) {
							$data[$k][] = DateTime ::createFromFormat('d-m-Y', $value) -> format("Y-m-d");
						}
						elseif (is_numeric($value)) {
							$data[$k][] = $value;
						}
						else {
							$data[$k][] = untag($value);
						}

					}

				}

			}

		}

	}

	return array_values($data);

}

/**
 * Старый вариант
 * Парсит данные из xls, xlsx, csv файлов
 * $file - файл с указанием полного абсолютного пути
 *
 * @deprecated use parceExcel
 * @throws \Exception
 * @category Core
 * @package  Func
 */
function parceExcelOld(string $file): array {

	//print $file;

	$cur_ext = getExtention($file);
	$data    = [];

	if ($cur_ext == 'xls') {

		$datas = new Spreadsheet_Excel_Reader();
		$datas -> setOutputEncoding('UTF-8');
		$datas -> read($file);
		$data = $datas -> dumptoarray();//получили двумерный массив с данными

		//print_r($datas);
		//exit();

		foreach ($data as $k => $Row) {

			if ($k > 1) {

				foreach ($Row as $key => $value) {

					$data[$k][] = ( $cur_ext == 'csv' ) ? enc_detect(untag($value)) : untag($value);

				}

			}

		}

		$data = array_reverse($data);

	}
	elseif (
		in_array($cur_ext, [
			'csv',
			'xlsx'
		])
	) {

		$datas = new SpreadsheetReader($file);
		$datas -> ChangeSheet(0);

		foreach ($datas as $k => $Row) {

			//print_r($Row);

			if ($k > 0) {

				foreach ($Row as $key => $value) {

					//print $key ." : ".$value."\n";

					if (DateTime ::createFromFormat('m-d-y', $value) !== false) {
						$data[$k][] = DateTime ::createFromFormat('m-d-y', $value) -> format("Y-m-d");
					}
					elseif (DateTime ::createFromFormat('d.m.Y', $value) !== false) {
						$data[$k][] = DateTime ::createFromFormat('d.m.Y', $value) -> format("Y-m-d");
					}
					elseif (DateTime ::createFromFormat('d-m-Y', $value) !== false) {
						$data[$k][] = DateTime ::createFromFormat('d-m-Y', $value) -> format("Y-m-d");
					}
					elseif (is_numeric($value)) {
						$data[$k][] = $value;
					}
					else {
						$data[$k][] = ( $cur_ext == 'csv' ) ? enc_detect(untag($value)) : untag($value);
					}

				}

			}

		}

		p:
		$data = array_values($data);

	}

	return $data;

}

/**
 * Парсер аргументов командной строки
 * вида php myfile.php type=daily foo=bar
 *
 * @param array $argv
 * @return array
 * @example
 *    ```php
 *    $req = parse_argv($argv);
 *    foreach ($req as $r => $v){
 *    $$r = $v;
 *    }
 *    ```
 * @category Core
 * @package  Func
 */
function parse_argv(array $argv): array {
	$request = [];

	foreach ($argv as $i => $a) {

		if (!$i) {
			continue;
		}

		if (preg_match('/^-*(.+?)=(.+)$/', $a, $matches)) {
			$request[$matches[1]] = $matches[2];
		}
		else {
			$request[$a] = true;
		}
	}

	return $request;

}

/**
 * Возвращает версии PHP
 * @return array
 */
function getPhpInfo(): array {

	exec("php -v", $php, $exit);

	$cliPHP = substr($php[0], 4, 3);
	$webPHP = substr(PHP_VERSION, 0, 3);

	return [
		"cli"  => $cliPHP,
		"web"  => $webPHP,
		"bin"  => str_replace("-cgi", "", PHP_BINARY),
		"full" => $php
	];

}

/**
 * Класс для работы системы событий
 * Class event
 */
class event {

	/**
	 * Массив зарегистрированных событий
	 *
	 * @var array
	 */
	public static $events = [];

	/**
	 * Функция запуска события
	 *
	 * @param       $event
	 * @param array $args
	 */
	public static function fire($event, array $args = []): void {
		if (isset(self::$events[$event])) {
			foreach (self::$events[$event] as $func) {
				$func($args);
				//call_user_func($func, $args);
			}
		}
	}

	/**
	 * Функция регистрации события
	 *
	 * @param         $event
	 * @param Closure $func
	 */
	public static function register($event, Closure $func): void {
		self::$events[$event][] = $func;
	}

}