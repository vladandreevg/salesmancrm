<?php
/* ============================ */
/*         SalesMan CRM         */
/* ============================ */
/* (C) 2018 Vladislav Andreev   */
/*       SalesMan Project       */
/*        www.isaler.ru         */
/*        ver. 2018.x           */
/* ============================ */

/**
 * Формирование основных настроек системы. Подключение и его порядок:
 * include $rootpath."/inc/config.php";
 * include $rootpath."/inc/dbconnector.php";
 * include $rootpath."/inc/auth_main.php";
 * include $rootpath."/inc/settings.php";
 * include $rootpath."/inc/func.php";
 */

error_reporting(E_ALL);

$rootpath = dirname(__DIR__);

if (!file_exists($rootpath."/cash/salesman_error.log")) {

	$file = fopen($rootpath."/cash/salesman_error.log", 'wb');
	fclose($file);

}
ini_set('log_errors', 'On');
ini_set('error_log', $rootpath.'/cash/salesman_error.log');

error_reporting(E_ERROR);

if (!isset($productInfo)) {
	$productInfo = [
		"name"      => "SalesMan CRM",
		"site"      => "https://salesman.pro",
		"crmurl"    => "",
		"email"     => "info@isaler.ru",
		"support"   => "support@isaler.ru",
		"info"      => "info@isaler.ru",
		"lastcalls" => true,
		"sipeditor" => true
	];
}

if (!isset($productInfo['crmurl']) || $productInfo['crmurl'] == '') {
	$productInfo['crmurl'] = $_SERVER['HTTP_SCHEME'] ?? ( ( ( isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'off' ) || 443 == $_SERVER['SERVER_PORT'] ) ? 'https://' : 'http://' ).$_SERVER["HTTP_HOST"];
}

if (!isset($productInfo['info'])) {
	$productInfo['info'] = 'info@'.$_SERVER["HTTP_HOST"];
}
if (!isset($productInfo['phone'])) {
	$productInfo['phone'] = '+7(922)3289466';
}

require_once $rootpath."/inc/dbconnector.php";
require_once $rootpath."/vendor/mobiledetect/mobiledetectlib/Mobile_Detect.php";

$detect = new Mobile_Detect();

$isApple = false;

$isMobile = $detect -> isMobile();
$isTablet = $detect -> isTablet();
$isPad    = $detect -> is('ipad');
$isMac    = $detect -> is('Mac');

if ($isTablet || $isPad) {
	$isMobile = false;
}

if ($isPad || $isMac) {
	$isApple = true;
}

$db = $GLOBALS['db'];

$identity = (int)$db -> getOne("SELECT identity FROM {$sqlname}user WHERE ses='".$_COOKIE['ses']."'");

//перенос в inc/config.php
//$isCloud = false;

global $isCloud;
global $logo;
global $settingsMore;

$isCloud = $GLOBALS['isCloud'];


$fpath = '';
$identity = 1;

//также используется в функции mymail, mailer, mailCal
$skey = 'vanilla'.( ( $identity + 7 ) ** 3 ).'round'.( ( $identity + 3 ) ** 2 ).'robin';

/**
 * @DEPRECATED
 */
$apath = '';//папка, в которую установлена CRM на сервере в виде "/crm"

$field = $db -> getRow("SHOW COLUMNS FROM {$sqlname}price_cat LIKE 'type'");
if ($field['Field'] == '') {

	$db -> query("ALTER TABLE {$sqlname}price_cat ADD COLUMN `type` TINYINT(1) NULL DEFAULT NULL COMMENT 'тип: 0 - товар, 1 - услуга, 2 - материал' AFTER `title`");

}

$settingsMore = [];

//кэшируем общие настройки
$settingsFile = $rootpath."/cash/".$fpath."settings.all.json";
if (file_exists($settingsFile)) {

	$settings = json_decode(file_get_contents($settingsFile), true);

	$company       = $settings["company"];
	$company_full  = $settings["company_full"];
	$company_site  = $settings["company_site"];
	$company_mail  = $settings["company_mail"];
	$company_phone = $settings["company_phone"];
	$company_fax   = $settings["company_fax"];
	if (!$gkey) {
		$gkey = $settings["gkey"];
	}
	$num_client   = $settings["num_client"];
	$num_con      = $settings["num_con"];
	$num_person   = $settings["num_person"];
	$num_dogs     = $settings["num_dogs"];
	$format_phone = $settings["format_phone"];
	$format_fax   = $settings["format_fax"];
	$format_tel   = $settings["format_tel"];
	$format_mob   = $settings["format_mob"];
	$format_dogs  = $settings["format_dogs"];
	$session      = $settings["session"];
	$tmzone       = $settings["tmzone"];

	$ivc       = $settings["ivc"];
	$maxupload = $settings["maxupload"];
	$mailout   = $settings["mailout"];
	$ext_allow = $settings["ext_allow"];
	$logo      = $settings["logo"];
	//$my_dir_name     = $settings["my_dir_name"];
	//$my_dir_shot     = $settings["my_dir_shot"];
	$outClientUrl    = $settings["outClientUrl"];
	$outDealUrl      = $settings["outDealUrl"];
	$my_dir_status   = $settings["my_dir_status"];
	$dir_prava       = $settings["dir_prava"];
	$acs_view        = $settings["acs_view"];
	$valuta          = $settings["valuta"];
	$complect_on     = $settings["complect_on"];
	$zayavka_on      = $settings["zayavka_on"];
	$contract_format = $settings["contract_format"];
	$contract_num    = $settings["contract_num"];
	$inum            = $settings["inum"];
	$iformat         = $settings["iformat"];
	$akt_num         = $settings["akt_num"];
	$akt_step        = $settings["akt_step"];
	$export_lock     = $settings["export_lock"];
	$other           = $settings["other"];
	$defaultDealName = $settings["defaultDealName"];

	//---------------------
	$isCatalog = $settings["isCatalog"];
	$isEntry   = $settings["isEntry"];
	$setEntry  = $settings["setEntry"];

	$isDialog  = $settings["isDialog"];
	$setDialog = $settings["setDialog"];

	$rsDefault  = (int)$settings["rsDefault"];
	$mcDefault  = (int)$settings["mcDefault"];
	$ndsDefault = (float)$settings["ndsDefault"];

	$dirDefault = (int)$settings["dirDefault"];
	$tipDefault = (int)$settings["tipDefault"];

	$relDefault      = (int)$settings["relDefault"];
	$relTitleDefault = $settings["relTitleDefault"];

	$actDefault      = (int)$settings["actDefault"];
	$actTitleDefault = $settings["actTitleDefault"];

	$pathDefault = (int)$settings["pathDefault"];

	$loyalDefault      = (int)$settings["loyalDefault"];
	$loyalTitleDefault = $settings["loyalTitleDefault"];

	$sip_active = $settings["sip_active"];
	$sip_tip    = $settings["sip_tip"];

	$modLeadActive = $settings["modLeadActive"];

	//доп.опции телефонии
	//$sipOptions = $db -> getOne("SELECT params FROM {$sqlname}customsettings WHERE tip = 'sip' AND identity = '$identity'");
	//$sipOptions = json_decode($sipOptions, true);
	$sipOptions = $settings['sipOptions'];
	//---------------------

	//доп.опции интеграции
	$dadataKey = $settings['apiServices']['dadata']['key'];
	//---------------------

	$settingsApp = $settings;

}
else {

	$result_set    = $db -> getRow("select * from {$sqlname}settings WHERE id = '$identity'");
	$company       = $result_set["company"];
	$company_full  = $result_set["company_full"];
	$company_site  = $result_set["company_site"];
	$company_mail  = $result_set["company_mail"];
	$company_phone = $result_set["company_phone"];
	$company_fax   = $result_set["company_fax"];
	if (!$gkey) {
		$gkey = $result_set["gkey"];
	}
	$num_client      = $result_set["num_client"];
	$num_con         = $result_set["num_con"];
	$num_person      = $result_set["num_person"];
	$num_dogs        = $result_set["num_dogs"];
	$format_phone    = $result_set["format_phone"];
	$format_fax      = $result_set["format_fax"];
	$format_tel      = $result_set["format_tel"];
	$format_mob      = $result_set["format_mob"];
	$format_dogs     = $result_set["format_dogs"];
	$session         = $result_set["session"];
	$tmzone          = $result_set["timezone"];
	$defaultDealName = $result_set["defaultDealName"];

	$ivc             = $result_set["ivc"];
	$maxupload       = $result_set["maxupload"];
	$mailout         = $result_set["mailout"];
	$ext_allow       = $result_set["ext_allow"];
	$logo            = $result_set["logo"];
	$outClientUrl    = $result_set["outClientUrl"];
	$outDealUrl      = $result_set["outDealUrl"];
	$my_dir_status   = $result_set["my_dir_status"];
	$dir_prava       = $result_set["dir_prava"];
	$acs_view        = $result_set["acs_view"];
	$valuta          = $result_set["valuta"];
	$complect_on     = $result_set["complect_on"];
	$zayavka_on      = $result_set["zayavka_on"];
	$contract_format = $result_set["contract_format"];
	$contract_num    = $result_set["contract_num"];
	$inum            = $result_set["inum"];
	$iformat         = $result_set["iformat"];
	$akt_num         = $result_set["akt_num"];
	$akt_step        = $result_set["akt_step"];
	$export_lock     = $result_set["export_lock"];
	$other           = $result_set["other"];

	//---------------------
	$isCatalog = $db -> getOne("SELECT active FROM {$sqlname}modules WHERE mpath = 'modcatalog' and identity = '$identity'");

	$result   = $db -> getRow("SELECT * FROM {$sqlname}modules WHERE mpath = 'entry' and identity = '$identity'");
	$isEntry  = $result["active"];
	$setEntry = json_decode($result["content"], true);

	$result    = $db -> getRow("SELECT * FROM {$sqlname}modules WHERE mpath = 'wcdialog' and identity = '$identity'");
	$isDialog  = $result["active"];
	$setDialog = json_decode($result["content"], true);

	// берем последюю использовавшуюся компанию
	$mcDefault = $db -> getOne("SELECT mcid FROM {$sqlname}dogovor WHERE identity = '$identity' ORDER BY did DESC LIMIT 1");

	$result_mcr = $db -> getRow("SELECT * FROM {$sqlname}mycomps_recv WHERE cid = '$mcDefault' AND isDefault='yes' and identity = '$identity'");
	$rsDefault  = $result_mcr["id"];
	//$mcDefault  = $result_mcr["cid"];
	$ndsDefault = $result_mcr["ndsDefault"];

	$dirDefault    = $db -> getOne("SELECT id FROM {$sqlname}direction WHERE isDefault='yes' and identity = '$identity'");
	$tipDefault    = $db -> getOne("SELECT tid FROM {$sqlname}dogtips WHERE isDefault='yes' and identity = '$identity'");
	$pathDefault   = $db -> getOne("SELECT id FROM {$sqlname}clientpath WHERE isDefault='yes' and identity = '$identity'");
	$modLeadActive = $db -> getOne("select active from {$sqlname}modules WHERE mpath = 'leads' and identity = '$identity'");

	$result_rel      = $db -> getRow("SELECT * FROM {$sqlname}relations WHERE isDefault='yes' and identity = '$identity'");
	$relDefault      = $result_rel["id"];
	$relTitleDefault = $result_rel["title"];

	$result_act      = $db -> getRow("SELECT * FROM {$sqlname}activities WHERE isDefault='yes' and identity = '$identity'");
	$actDefault      = $result_act["id"];
	$actTitleDefault = $result_act["title"];

	$result_path       = $db -> getRow("SELECT * FROM {$sqlname}loyal_cat WHERE isDefault='yes' and identity = '$identity'");
	$loyalDefault      = $result_path["idcategory"];
	$loyalTitleDefault = $result_path["title"];

	$result_sett = $db -> getRow("select * from {$sqlname}sip WHERE identity = '$identity'");
	$sip_active  = $result_sett["active"];
	$sip_tip     = $result_sett["tip"];

	$sipOptions = $db -> getOne("SELECT params FROM {$sqlname}customsettings WHERE tip = 'sip' and identity = '$identity'");
	$sipOptions = json_decode($sipOptions, true);

	$apiServices['dadata'] = json_decode($db -> getOne("SELECT params FROM {$sqlname}customsettings WHERE tip = 'dadata' and identity = '$identity'"), true);
	$dadataKey             = $apiServices['dadata']['key'];

	$themesTasks = json_decode($db -> getOne("SELECT params FROM {$sqlname}customsettings WHERE tip = 'themesTasks' and identity = '$identity'"), true);

	//---------------------

	$settingsApp = [
		"company"           => $company,
		"company_full"      => $company_full,
		"company_site"      => $company_site,
		"company_mail"      => $company_mail,
		"company_phone"     => $company_phone,
		"company_fax"       => $company_fax,
		"gkey"              => $gkey,
		"num_client"        => $num_client,
		"num_con"           => $num_con,
		"num_person"        => $num_person,
		"num_dogs"          => $num_dogs,
		"format_phone"      => $format_phone,
		"format_fax"        => $format_fax,
		"format_tel"        => $format_tel,
		"format_mob"        => $format_mob,
		"format_dogs"       => $format_dogs,
		"session"           => $session,
		"tmzone"            => $tmzone,
		"ivc"               => $ivc,
		"maxupload"         => $maxupload,
		"mailout"           => $mailout,
		"acs_view"          => $acs_view,
		"valuta"            => $valuta,
		"complect_on"       => $complect_on,
		"zayavka_on"        => $zayavka_on,
		"contract_format"   => $contract_format,
		"contract_num"      => $contract_num,
		"inum"              => $inum,
		"iformat"           => $iformat,
		"akt_num"           => $akt_num,
		"akt_step"          => $akt_step,
		"export_lock"       => $export_lock,
		"other"             => $other,
		"isCatalog"         => $isCatalog,
		"rsDefault"         => (int)$rsDefault,
		"mcDefault"         => (int)$mcDefault,
		"ndsDefault"        => (float)$ndsDefault,
		"dirDefault"        => (int)$dirDefault,
		"tipDefault"        => (int)$tipDefault,
		"relDefault"        => (int)$relDefault,
		"relTitleDefault"   => $relTitleDefault,
		"actDefault"        => (int)$actDefault,
		"actTitleDefault"   => $actTitleDefault,
		"pathDefault"       => (int)$pathDefault,
		"loyalDefault"      => (int)$loyalDefault,
		"loyalTitleDefault" => $loyalTitleDefault,
		"isEntry"           => $isEntry,
		"setEntry"          => $setEntry,
		"isDialog"          => $isDialog,
		"setDialog"         => $setDialog,
		"sip_active"        => $sip_active,
		"sip_tip"           => $sip_tip,
		"modLeadActive"     => $modLeadActive,
		"ext_allow"         => $ext_allow,
		"outClientUrl"      => $outClientUrl,
		"outDealUrl"        => $outDealUrl,
		"my_dir_status"     => $my_dir_status,
		"dir_prava"         => $dir_prava,
		"logo"              => $logo,
		"sipOptions"        => $sipOptions,
		"apiServices"       => $apiServices,
		"themesTasks"       => $themesTasks
	];

	file_put_contents($settingsFile, json_encode((array)$settingsApp));
	//fclose( $file );

}

if ($iduser1 > 0) {

	// не работает, т.к. функции еще не подключены
	//$settingsMore = customSettings( 'settingsMore' );

	$settingsMore = json_decode($db -> getOne("SELECT params FROM {$sqlname}customsettings WHERE tip = 'settingsMore' and identity = '$identity'"), true);

	if ($dirDefault == '') {
		$dirDefault = $db -> getOne("SELECT MIN(id) FROM {$sqlname}direction WHERE identity = '$identity'");
	}

	//кэшируем настройки плагинов
	$pluginJS      = [];
	$pluginEnabled = '';

	$pluginFile = $rootpath."/cash/".$fpath."plugins.json";
	if (file_exists($pluginFile)) {

		$pluginEnabled = file_get_contents($pluginFile);

	}
	else {

		$xpluginEnabled = $db -> getCol("SELECT name FROM {$sqlname}plugins WHERE active != 'off' and identity = '$identity'");
		$pluginEnabled  = json_encode($xpluginEnabled);

		//print_r($pluginEnabled);

		file_put_contents($rootpath."/cash/".$fpath."plugins.json", $pluginEnabled);

	}

	//print_r($pluginEnabled);

	//настройки плагинов, у которых есть js для выполнения
	$pluginBase = $rootpath."/plugins/map.json";
	if (file_exists($pluginBase)) {

		$pluginEnabledArray = json_decode($pluginEnabled, true);
		$pliginListCastom   = ( file_exists($rootpath."/plugins/map.castom.json") ) ? json_decode(file_get_contents($rootpath."/plugins/map.castom.json"), true) : [];

		$pJS = json_decode(str_replace([
			"  ",
			"\t",
			"\n",
			"\r"
		], "", file_get_contents($pluginBase)), true);

		$pJS = !empty($pliginListCastom) ? array_merge($pJS, $pliginListCastom) : $pJS;
		foreach ($pJS as $key => $value) {

			//print $key."<br>";

			if ($value['js'] != '' && in_array($key, $pluginEnabledArray)) {
				$pluginJS[] = "'".$value['js']."'";
			}

		}

	}

	//настройки поиска дублей
	$doublesFile = $rootpath."/cash/".$fpath."settings.checkdoubles.json";
	if (file_exists($doublesFile)) {

		$dblSettings = json_decode(file_get_contents($doublesFile), true);

	}
	else {

		$dbl         = $db -> getOne("SELECT params FROM {$sqlname}customsettings WHERE tip = 'doubles' AND identity = '$identity'");
		$dblSettings = json_decode($dbl, true);

		file_put_contents($doublesFile, $dbl);

	}

}

/**
 * Телефония
 */
//полноценные PBX
$sipHasCDR = [
	'asterisk',
	'asteriskapi',
	'mango',
	'gravitel',
	'telfin',
	'onlinepbx',
	'cloudpbx',
	'yandextel',
	'zadarma',
	'rostelecom'
];

if (file_exists($rootpath.'/modules/mailer')) {
	$ymEnable = true;
}

$plan_form = "datum_close";

$maxuploadini = str_replace([
	'M',
	'm'
], '', @ini_get('upload_max_filesize'));
if ($maxupload > $maxuploadini) {
	$maxupload = $maxuploadini;
}

if ($GLOBALS['isCloud']) {
	$ext_allow = "gif,jpg,jpeg,png,txt,doc,docx,xls,xlsx,ppt,pptx,rtf,pdf,7z,tar,zip,rar,gz,exe";
}

/**
 * @DEPRECATED
 * @see $otherSettings
 */
//$other = explode( ";", $other );


//print_r($other);

/* other =
0 - работа с партнерами
1 - следить за конкурентами
2 - вкл. график платежей
3 - прайсы и спецификации
4 - период сделки
5 - договоры с клиентами
6 - период предупреждения о платеже
7 - период предупреждения о сделке
+ новое 7.2
8 - профилирование
+ новое 7.3
9 - показ и учет маржи
10 - модуль Потенциал
11 - экспресс-формы
12 - печать счетов
13 - если 'yes', то наш клиент - Персона
14 - использовать доп.множитель в спеках
15 - название, для доп.множителя
16 - модуль Обсуждение
17 - модуль Поставщики
18 - расчет планов по закрытым сделкам
19 - контроль количества напоминаний
20 - контроль количества напоминаний - запрет добавления клиента
21 - работем без НДС
22 - запрет сделок на контакты
23 - разрешить добавлять Клиента при добавлении сделки
24 - смена периода сделки
25 - этап сделки по умолчанию при создании сделки
26 - продолжительность сделки по умолчанию
27 - обязательность поля комментария при смене этапа
28 - обязательность поля комментария при смене ответственного
29 - НДС в цене (no) / сверху (yes)
30 - вкл. блок со списком проданных товаров
31 - отключение возможности редактировать справочники
32 - кол-во часов, которое пользователь может редактировать/удалять напоминание, даже если стоит запрет
33 - контроль напоминаний в Здоровье сделок
34 - артикул в счетах
35 - артикул в актах
36 - настройка по почтовику (включить/отключить решим привязки сообщений из других почтовых ящиков в зависимости от связи)
37 - Здоровье. контроль по продолжительности этапов
38 - Бюджет. Дата проведения = сегодня (принудительно)
39 - Шаблон акта для сервисной сделки
40 - Шаблон счета для сервисной сделки
41 - Шаблон акта для сделки
42 - Шаблон счета для сделки
43 - Скрыть поле контакта в Экспресс-форме
44 - Блок Сделка в Экспресс-форме
45 - Поле даты для заморозки
*/

//$other[30] = 'on';


//print_r($other);

/**
 * Все настройки из параметра other
 */
/*
$otherSettings = [
	"partner"                => $other[0] == 'yes',
	"concurent"              => $other[1] == 'yes',
	"credit"                 => $other[2] == 'yes',
	"price"                  => $other[3] == 'yes',
	"dealPeriod"             => $other[4] == 'yes',
	"contract"               => $other[5] == 'yes',
	"creditAlert"            => $other[6],
	"dealAlert"              => $other[7],
	"profile"                => $other[8] == 'yes',
	"marga"                  => $other[9] == 'yes',
	"potential"              => $other[10] == 'yes',
	"expressForm"            => $other[11] == 'yes',
	"printInvoice"           => $other[12] == 'yes',
	"clientIsPerson"         => $other[13] != 'yes',
	"dop"                    => $other[14] == 'yes',
	"dopName"                => $other[15] != 'no' ? $other[15] : NULL,
	"comment"                => $other[16] == 'yes',
	"contractor"             => $other[17] == 'yes',
	"planByClosed"           => $other[18] == 'yes',
	"taskControl"            => (int)$other[19],
	"taskControlClientAdd"   => $other[20] == 'yes',
	"woNDS"                  => $other[21] == 'yes',
	"dealByContact"          => $other[22] == 'yes',
	"addClientWDeal"         => $other[23] == 'yes',
	"changeDealPeriod"       => $other[24],
	"dealStepDefault"        => (int)$other[25],
	"dealPeriodDefault"      => $other[26] != '' && $other[26] != 'no' ? $other[26] : 14,
	"changeDealComment"      => $other[27] == 'yes',
	"changeUserComment"      => $other[28] == 'yes',
	"ndsInOut"               => $other[29] == 'yes',
	"saledProduct"           => $other[30] == 'yes',
	"guidesEdit"             => $other[31] == 'yes',
	"taskEditTime"           => (int)$other[32],
	"taskControlInHealth"    => $other[33] == 'yes',
	"artikulInInvoice"       => $other[34] == 'yes',
	"artikulInAkt"           => $other[35] == 'yes',
	"mailerMsgUnion"         => $other[36] == 'yes',
	"stepControlInHealth"    => $other[37] == 'yes',
	"budjetDayIsNow"         => $other[38] == 'yes',
	"aktTempService"         => (!isset( $other[39] ) || $other[39] == 'no') ? 'akt_full.tpl' : $other[39],
	"invoiceTempService"     => (!isset( $other[40] ) || $other[40] == 'no') ? 'invoice.tpl' : $other[40],
	"aktTemp"                => (!isset( $other[41] ) || $other[41] == 'no') ? 'akt_full.tpl' : $other[41],
	"invoiceTemp"            => (!isset( $other[42] ) || $other[42] == 'no') ? 'invoice.tpl' : $other[42],
	"hideContactFromExpress" => !((!isset( $other[43] ) || $other[43] == 'no')),
	"addDealForExpress"      => (isset( $other[44] ) && $other[44] == 'yes'),
	"dateFieldForFreeze"     => $other[45] != 'no' && $other[45] != 'Не выбрано' ? $other[45] : ''
];*/

if ($iduser1 > 0 && isset($other)) {

	$other = explode(";", $other);

	if (!file_exists($rootpath."/cash/".$fpath."otherSettings.json")) {

		$otherSettings = [
			"partner"                => $other[0] == 'yes',
			"concurent"              => $other[1] == 'yes',
			"credit"                 => $other[2] == 'yes',
			"price"                  => $other[3] == 'yes',
			"dealPeriod"             => $other[4] == 'yes',
			"contract"               => $other[5] == 'yes',
			"creditAlert"            => $other[6],
			"dealAlert"              => $other[7],
			"profile"                => $other[8] == 'yes',
			"marga"                  => $other[9] == 'yes',
			"potential"              => $other[10] == 'yes',
			"expressForm"            => $other[11] == 'yes',
			"printInvoice"           => $other[12] == 'yes',
			"clientIsPerson"         => $other[13] != 'yes',
			"dop"                    => $other[14] == 'yes',
			"dopName"                => $other[15],
			"comment"                => $other[16] == 'yes',
			"contractor"             => $other[17] == 'yes',
			"planByClosed"           => $other[18] == 'yes',
			"taskControl"            => (int)$other[19],
			"taskControlClientAdd"   => $other[20] == 'yes',
			"woNDS"                  => $other[21] == 'yes',
			"dealByContact"          => $other[22] == 'yes',
			"addClientWDeal"         => $other[23] == 'yes',
			"changeDealPeriod"       => $other[24],
			"dealStepDefault"        => $other[25],
			"dealPeriodDefault"      => $other[26] != '' && $other[26] != 'no' ? $other[26] : 14,
			"changeDealComment"      => $other[27] == 'yes',
			"changeUserComment"      => $other[28] == 'yes',
			"ndsInOut"               => $other[29] == 'yes',
			"saledProduct"           => $other[30] == 'yes',
			"guidesEdit"             => $other[31] == 'yes',
			"taskEditTime"           => $other[32],
			"taskControlInHealth"    => $other[33] == 'yes',
			"artikulInInvoice"       => $other[34] == 'yes',
			"artikulInAkt"           => $other[35] == 'yes',
			"mailerMsgUnion"         => $other[36] == 'yes',
			"stepControlInHealth"    => $other[37] == 'yes',
			"budjetDayIsNow"         => $other[38],
			"aktTempService"         => ( !isset($other[39]) || $other[39] == 'no' ) ? 'akt_full.tpl' : $other[39],
			"invoiceTempService"     => ( !isset($other[40]) || $other[40] == 'no' ) ? 'invoice.tpl' : $other[40],
			"aktTemp"                => ( !isset($other[41]) || $other[41] == 'no' ) ? 'akt_full.tpl' : $other[41],
			"invoiceTemp"            => ( !isset($other[42]) || $other[42] == 'no' ) ? 'invoice.tpl' : $other[42],
			"hideContactFromExpress" => !( ( !isset($other[43]) || $other[43] == 'no' ) ),
			"addDealForExpress"      => ( isset($other[44]) && $other[44] == 'yes' ),
			"dateFieldForFreeze"     => $other[45] != 'no' ? $other[45] : ''
		];

		file_put_contents($rootpath."/cash/".$fpath."otherSettings.json", json_encode($otherSettings));

	}
	else {

		$otherSettings = json_decode(file_get_contents($rootpath."/cash/".$fpath."otherSettings.json"), true);

	}

	//период для плановой даты сделки
	$perDay = ( $otherSettings['dealPeriodDefault'] == '' ) ? 14 : $otherSettings['dealPeriodDefault'];

	// расчет НДС сверху или в сумме
	$ndsRaschet = $otherSettings['ndsInOut'] ? "yes" : "no";

	// этап по умолчанию
	$stepDefault = $otherSettings['dealStepDefault'];

	$hoursControlTime = ( isset($otherSettings['taskEditTime']) ) ? (int)$otherSettings['taskEditTime'] : 1;

	//это признак аккаунта, для локальной версии он пуст
	$account    = '';
	$accountset = 1;

	if ($logo != '') {

		$logo = ( file_exists($rootpath.'/cash/logo/'.$logo) ) ? '/cash/logo/'.$logo : '/cash/logo/logo.png';

	}
	if ($logo == '') {

		$logo = '/assets/images/logo-white.png';

	}

}

$userRights = [];

if ($iduser1 > 0) {

	$ym_param = [];

	$thisfile = basename($_SERVER['PHP_SELF']);
	if ($thisfile != '_update.php') {

		//кэшируем настройки почтового ящика пользователя
		$settingsYMail = $rootpath."/cash/".$fpath."settings.ymail.".$iduser1.".json";
		if (file_exists($settingsYMail)) {

			$ym_param = json_decode(file_get_contents($settingsYMail), true);

		}
		else {

			$ym_param = json_decode($db -> getOne("SELECT settings FROM {$sqlname}ymail_settings WHERE iduser = '".$iduser1."' AND identity = '".$identity."'"), true);

			file_put_contents($settingsYMail, json_encode($ym_param));

		}

		//кэшируем настройки подпсей почтового ящика пользователя
		$signatureYMail = $rootpath."/cash/".$fpath."signature.ymail.".$iduser1.".json";
		if (file_exists($signatureYMail)) {

			$ym_param = array_merge((array)$ym_param, (array)json_decode((string)file_get_contents($signatureYMail), true));

		}

	}

	$settingsUser = $rootpath."/cash/".$fpath."settings.user.".$iduser1.".json";

	if (file_exists($settingsUser) && filesize($settingsUser) > 0) {

		$settinguser = (array)json_decode((string)file_get_contents($settingsUser), true);

		$acs_analitics = $settinguser["acs_analitics"];
		$acs_maillist  = $settinguser["acs_maillist"];
		$acs_files     = $settinguser["acs_files"];
		$acs_price     = $settinguser["acs_price"];
		$acs_credit    = $settinguser["acs_credit"];
		$acs_prava     = $settinguser["acs_prava"];//Может просматривать чужие записи
		$acs_import    = $settinguser["acs_import"];
		$acs_plan      = $settinguser["acs_plan"];
		$show_marga    = $settinguser["show_marga"];
		$tzone         = $settinguser["tzone"];
		$isadmin       = $settinguser["isadmin"];
		$tipuser       = $settinguser["tipuser"];
		$avatar        = $settinguser["avatar"];
		$userSettings  = $settinguser["userSettings"];

	}
	else {

		$result        = $db -> getRow("select * from {$sqlname}user WHERE iduser = '$iduser1' and identity = '$identity'");
		$acs_analitics = $result["acs_analitics"];
		$acs_maillist  = $result["acs_maillist"];
		$acs_files     = $result["acs_files"];
		$acs_price     = $result["acs_price"];
		$acs_credit    = $result["acs_credit"];
		$acs_prava     = $result["acs_prava"];
		$acs_import    = $result["acs_import"];
		$acs_plan      = $result["acs_plan"];
		$show_marga    = $result["show_marga"];
		$tzone         = $result["tzone"];
		$isadmin       = $result["isadmin"];
		$tipuser       = $result["tip"];
		$avatar        = $result["avatar"];
		$userSettings  = json_decode($result["usersettings"], true);

		$settingsUserr = [
			"acs_analitics" => $acs_analitics,
			"acs_maillist"  => $acs_maillist,
			"acs_files"     => $acs_files,
			"acs_price"     => $acs_price,
			"acs_credit"    => $acs_credit,
			"acs_prava"     => $acs_prava,
			"acs_import"    => $acs_import,
			"acs_plan"      => $acs_plan,
			"show_marga"    => $show_marga,
			"tzone"         => $tzone,
			"isadmin"       => $isadmin,
			"tipuser"       => $tipuser,
			"avatar"        => $avatar,
			"userSettings"  => $userSettings
		];

		file_put_contents($settingsUser, json_encode($settingsUserr));

	}

	$result    = $db -> getRow("select * from {$sqlname}user WHERE iduser = '$iduser1' and identity = '$identity'");
	$isadmin   = $result["isadmin"];
	$ac_import = explode(";", $result["acs_import"]);

	// права сотрудника
	$userRights = [
		// Доступ к функциям Экспорта
		"export"         => $ac_import[0] == 'on',
		// Доступ к функциям Импорта
		"import"         => $ac_import[1] == 'on',
		// Разрешает удаление файлов и активностей, Редактирование активностей
		"delete"         => $ac_import[2] == 'on',
		// Может видеть чужие Активности
		"showhistory"    => $ac_import[3] == 'on',
		// Разрешает производить групповые действия
		"groupactions"   => $ac_import[4] == 'on',
		// Меню Все Сделки, Клиенты, Контакты
		"alls"           => $ac_import[5] == 'on',
		// Доступ в раздел "Бюджет"
		"budjet"         => $ac_import[6] == 'on',
		// Может редактировать/удалять Напоминания
		"changetask"     => $ac_import[7] == 'on',
		// клиент
		"client"         => [
			"create" => $ac_import[8] == 'on',
			"edit"   => $ac_import[9] == 'on',
			"delete" => $ac_import[10] == 'on',
		],
		// контакт
		"person"         => [
			"create" => $ac_import[11] == 'on',
			"edit"   => $ac_import[12] == 'on',
			"delete" => $ac_import[13] == 'on',
		],
		// сделка
		"deal"           => [
			"create"     => $ac_import[14] == 'on',
			"edit"       => $ac_import[15] == 'on',
			"restore"    => $ac_import[21] == 'on',
			"close"      => $ac_import[22] == 'on',
			"editclosed" => $ac_import[23] == 'on',
			"delete"     => $ac_import[16] == 'on',
		],
		// Доступ в раздел "Группы"
		"group"          => $ac_import[17] == 'on',
		// Доступ в раздел "Связи"
		"contractors"    => $ac_import[18] == 'on',
		// План продаж индивидуальный
		"individualplan" => $ac_import[19] == 'on',
		// Не сможет менять Ответственных
		"nouserchange"   => $ac_import[20] == 'on'
	];

	$userRights['dostup']['rc'] = !empty($userSettings['dostup']['rc']) ? array_map(static function ($x) {
		return (int)$x;
	}, $userSettings['dostup']['rc']) : [];

	$modLeadActive = $db -> getOne("SELECT active FROM {$sqlname}modules WHERE mpath = 'leads' and identity = '$identity'");

	$budjetChart = "no";
	if ($userRights['budjet']){

		$budjetChart = "yes";

		if(
			$userSettings['dostup']['budjet']['journal'] == 'yes' ||
			$userSettings['dostup']['budjet']['statement'] == 'yes' ||
			$userSettings['dostup']['budjet']['payment'] == 'yes' ||
			$userSettings['dostup']['budjet']['agents'] == 'yes'
		){
			$budjetChart = "no";
		}

	}

	if ($userRights['budjet'] && empty($userSettings['dostup']['budjet'])) {

		$userSettings['dostup']['budjet'] = [
			"journal"   => "yes",
			"statement" => "yes",
			"payment"   => "yes",
			"agents"    => "yes",
			"money"     => "yes",
			"action"    => "yes",
			"chart"     => "yes",
			"onlyself"  => NULL
		];

	}
	elseif($userRights['budjet']){
		$userSettings['dostup']['budjet']['chart'] = $budjetChart;
	}

	//print_r($userSettings);

	/*
	0 = Может экспортировать
	1 = Может импортировать
	2 = Может удалять Организации, Персоны, Активности
	3 = Видит чужие активности
	4 = Доступ к массовым операциям
	5 = Доступ к меню "Все сделки"
	6 = Доступ к меню "Бюджет"
	7 = Может редактировать напоминания

	8 = Создание организаций
	9 = изменение организаций
	10 = удаление организаций
	11 = создание персон
	12 = изменение персон
	13 = удаление персон
	14 = создание сделок
	15 = изменение сделок
	16 = удаление сделок
	17 = доступ в Группы
	18 = доступ в Связи
	19 = индивидуальный план продаж - для руководителей
	20 = не может менять ответственных
	21 = может всстанавливать сделки
	22 = может закрывать сделки
	23 = может редактировать закрытые сделки

	*/
	//print_r($ac_import);

}
else {
	$tzone = 0;
}

if ($tmzone == '') {
	$tmzone = 'Europe/Moscow';
}

date_default_timezone_set($tmzone);

/**
 * Устанавливаем дату в БД с учетом настроек сервера и смещением для пользователя. старт
 */
$tz  = new DateTimeZone($tmzone);
$dz  = new DateTime();
$dzz = $tz -> getOffset($dz);

//print $tzone;
$bdtimezone = $dzz / 3600 + $tzone;

//если значение не корректно (больше 12), то игнорируем смещение временной зоны
if (abs($bdtimezone) > 12) {

	$tzone      = 0;
	$bdtimezone = $dzz / 3600;

}

$bdtimezone = ( $bdtimezone > 0 ) ? "+".abs($bdtimezone) : "-".abs($bdtimezone);

$db -> query("SET time_zone = '".$bdtimezone.":00'");
/*конец*/

if ($avatar == '') {

	$avatar = '/assets/images/noavatar.png';

}
else {

	$avatar = "/cash/avatars/".$avatar;

	if (!file_exists($rootpath.'/'.$avatar)) {
		$avatar = '/assets/images/noavatar.png';
	}

}

if ($tipuser == 'Специалист') {

	$acs_plan          = '';
	$acs['clientbase'] = false;
	$acs['dogbase']    = false;
	$acs['marketing']  = false;
	$acs['finance']    = false;

	/*
	$ac_import = array(
		"0"  => "off",
		"1"  => "off",
		"2"  => "off",
		"3"  => "off",
		"4"  => "off",
		"5"  => "off",
		"6"  => "off",
		"7"  => "off",
		"8"  => "off",
		"9"  => "off",
		"10" => "off",
		"11" => "off",
		"12" => "off",
		"13" => "off",
		"14" => "off",
		"15" => "off",
		"16" => "off",
		"17" => "off",
		"18" => "off",
		"19" => "off",
		"20" => "on",
		"21" => "off"
	);
	*/

}
else {

	$acs['clientbase'] = true;
	$acs['dogbase']    = true;
	$acs['marketing']  = true;
	$acs['finance']    = true;

}

if ($acs_view == 'on') {
	$acs_prava = 'on';
}
else {

	if ($acs_prava == 'on') {
		$acs_prava = 'on';
	}
	if ($acs_prava != 'on') {
		$acs_prava = 'off';
	}

}

$acs_useradd = true;
$userlim     = "";

$client_types = [
	"client"     => "Клиент. Юр.лицо",
	"person"     => "Клиент. Физ.лицо",
	"partner"    => "Партнер",
	"concurent"  => "Конкурент",
	"contractor" => "Поставщик"
];

//$color = array('#CC0000','#000099','#006600','#CC6600','#666699','#990099','#999900','#0066CC','#FF6600','#996666','#FF0033','#0099FF','#663300','#666600','#FF00CC','#9900FF','#FFCC00','#003366','#333333','#FF3300');

$tarif = 'Plus';

$fieldsOn = $fieldsNames = $fieldsRequire = [];

//названия заголовков полей
$result = $db -> query("select fld_title,fld_tip,fld_name,fld_required from {$sqlname}field where fld_on = 'yes' and identity = '$identity'");
while ($data = $db -> fetch($result)) {

	$fieldsOn[$data['fld_tip']][]                       = $data['fld_name'];
	$fieldsNames[$data['fld_tip']][$data['fld_name']]   = $data['fld_title'];
	$fieldsRequire[$data['fld_tip']][$data['fld_name']] = $data['fld_required'];

}

//внешние ссылки
//$outClientUrl = 'http://localhost/{uid}/{login}';
//$outDealUrl = 'http://localhost/{uid}/{login}';

//версия системы и билд берем из файла
$verFile    = ( file_exists($rootpath.'/_whatsnew/version.json') ) ? $rootpath.'/_whatsnew/version.json' : './_whatsnew/version.json';
$sysVersion = json_decode(str_replace([
	"  ",
	"\t",
	"\n",
	"\r"
], "", file_get_contents($verFile)), true);
$bdVersion  = $db -> getOne("SELECT current FROM {$sqlname}ver ORDER BY id DESC LIMIT 1");

$helper = json_decode(file_get_contents($rootpath.'/cash/helper.json'), true);

if (!isset($language)) {
	$language = 'ru-RU';
}

require_once $rootpath."/inc/language/{$language}.php";

$calendarPeriods = [
	"today"            => $lang['period']['today'],
	"yestoday"         => $lang['period']['yestoday'],
	"calendarweekprev" => $lang['period']['calendarweekprev'],
	"calendarweek"     => $lang['period']['calendarweek'],
	"calendarweeknext" => $lang['period']['calendarweeknext'],
	"prevmonth"        => $lang['period']['prevmonth'],
	"month"            => $lang['period']['month'],
	"nextmonth"        => $lang['period']['nextmonth'],
	"prevquart"        => $lang['period']['prevquartal'],
	"quart"            => $lang['period']['quartal'],
	"nextquart"        => $lang['period']['nextquartal'],
	"year"             => $lang['period']['year'],
	"prevyear"         => $lang['period']['prevyear'],
	"nextyear"         => $lang['period']['nextyear']
];