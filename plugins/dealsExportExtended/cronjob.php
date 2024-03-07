<?php
/* ============================ */
/*         SalesMan CRM         */
/* ============================ */
/* (C) 2022 Vladislav Andreev   */
/*       SalesMan Project       */
/*        www.isaler.ru         */
/*        ver. 2022.x           */

/* ============================ */

use Cronman\Cronman;
use Salesman\Guides;
use Salesman\Notify;
use Salesman\User;

set_time_limit(0);
error_reporting(E_ERROR);

$rootpath = realpath(__DIR__.'/../../');

require_once $rootpath."/inc/config.php";
require_once $rootpath."/inc/dbconnector.php";

require_once $rootpath."/inc/auth.php";

require_once $rootpath."/inc/func.php";
require_once $rootpath."/inc/settings.php";
require_once $rootpath."/inc/language/".$language.".php";
require_once $rootpath."/plugins/cronManager/php/autoload.php";

$thisfile = basename(__FILE__);

// очистим старые
$cmd0 = 'find '.$path.' -maxdepth 1 -type f -name "*.xlsx" -mtime +5 -exec rm -f {} \;';
exec($cmd0, $list, $exit2 );

function cleanall($string) {
	$string = strip_tags_smart($string);
	$string = trim($string);
	$string = ltrim($string);
	$string = str_replace("\"", "'", $string);
	$string = str_replace("\n", " ", $string);
	$string = str_replace("\r", " ", $string);
	$string = str_replace("\n\r", " ", $string);
	$string = str_replace("\\n", " ", $string);
	$string = str_replace("\\r", " ", $string);
	$string = str_replace("\\n\\r", " ", $string);
	$string = str_replace("<", "'", $string);
	$string = str_replace(">", "'", $string);
	$string = str_replace("?", "", $string);
	$string = str_replace("„", "", $string);
	$string = str_replace("«", "", $string);
	$string = str_replace("»", "", $string);
	/*$string = str_replace("?","&euro;",$string);
	$string = str_replace("„","&bdquo;",$string);
	$string = str_replace("«","&laquo;",$string);
	$string = str_replace("»","&raquo;",$string);*/
	$string = str_replace("?", "", $string);
	$string = str_replace("„", "", $string);
	$string = str_replace("«", "", $string);
	$string = str_replace("»", "", $string);
	$string = str_replace(">", "", $string);
	$string = str_replace("<", "", $string);
	$string = str_replace("&amp;", "", $string);
	$string = str_replace("#8220;", "", $string);
	$string = str_replace("“", "", $string);
	$string = str_replace("”", "", $string);
	$string = str_replace("'", "", $string);
	$string = str_replace("=", "", $string);
	$string = str_replace(";", ". ", $string);
	$string = str_replace("&laquo;", "", $string);
	$string = str_replace("&raquo;", "", $string);
	$string = str_replace("&rdquo;", "", $string);
	$string = str_replace("&ldquo;", "", $string);

	return $string;
}

function clll($string) {
	$string = strip_tags($string);
	$string = trim($string);
	$string = ltrim($string);
	$string = str_replace("(", "'", $string);
	$string = str_replace(")", "", $string);
	$string = str_replace("&", "", $string);
	$string = str_replace(";", ". ", $string);
	$string = str_replace("javascript:void(0)", "", $string);
	$string = str_replace("/", "", $string);

	return $string;
}

$taskID = (int)$argv[1];

$cron   = new Cronman();
$task   = $cron -> getTask($taskID);
$params = json_decode($task['task'], true);

//print_r($params);
//print_r($params['include']['client']);
//exit();

// деактивируем таску
$cron -> disableTask($taskID);

$fields = [];

/*поля*/
$fieldsNames = [
	'did'          => 'Deal ID',
	'uid'          => 'Deal UID',
	'clientUID'    => 'Client UID',
	'title'        => 'Название',
	'idcategory'   => 'Этап',
	'stepDate'     => 'Дата смены этапа',
];

if($otherSettings['dateFieldForFreeze'] != ''){
	$fieldsNames["isFrozen"] = 'Заморожена';
	$fieldsNames[$otherSettings['dateFieldForFreeze']] = 'Заморозка до';
}

$fieldsNames = array_merge($fieldsNames, [
	'stepDate'     => 'Дата смены этапа',
	'datum_plan'   => 'Дата план.',
	'kol'          => 'Сумма план.',
	'marga'        => 'Маржа',
	'direction'    => 'Направление',
	'datum'        => 'Дата создания',
	'datum_izm'    => 'Дата изменения',
	'datum_start'  => 'Период старт',
	'datum_end'    => 'Период финиш',
	'close'        => 'Закрыта',
	'datum_close'  => 'Дата закрытия',
	'status_close' => 'Статус закрытия',
	'des_fact'     => 'Комментарий закрытия',
	'kol_fact'     => 'Сумма факт',
	'clid'         => 'Клиент',
	'date_create'  => 'Дата добавления Клиента',
	'iduser'       => 'Куратор',
	'autor'        => 'Автор',
	'path'         => 'Канал продаж'
]);

$res = $db -> query("SELECT * FROM {$sqlname}field WHERE fld_tip='dogovor' and fld_on='yes' and fld_name NOT IN ('kol_fact','money','pid_list','oborot','period','des','kol','marga') and identity = '$identity'");
while ($do = $db -> fetch($res)) {

	if ($do['fld_name'] == 'marg') $do['fld_name'] = 'marga';

	$fieldsNames[ $do['fld_name'] ] = $do['fld_title'];
	$fields[]                       = $do['fld_name'];

}

//print_r($fieldsNames);
//exit();

$fieldsClientNames = $db -> getIndCol("fld_name", "SELECT fld_name, fld_title FROM ".$sqlname."field WHERE fld_tip = 'client' AND fld_on = 'yes' AND identity = '$identity'");

//print_r($_REQUEST);
//exit();

$iduser = $UserID = $params['UserID'];

$includes = $params['include'];
$excludes = $params['exclude'];

$users       = $db -> getIndCol("iduser", "SELECT iduser, title FROM {$sqlname}user WHERE identity = '$identity'");
$clientpaths = $db -> getIndCol("id", "SELECT id, name FROM {$sqlname}clientpath WHERE identity = '$identity'");

$tar = ($params['list'] == '') ? "my" : $params['list'];

//Исключаем поля
$exclude_array = ['did'];

//получим поля, которые надо также исключить по запросу
foreach ($excludes as $ex) {
	$exclude_array[] = $ex;
}

//array_unshift($fields, 'did', 'uid', 'clid', 'iduser', 'autor', 'datum', 'datum_plan', 'title', 'idcategory', 'kol', 'marga', 'datum_izm', 'datum_start', 'datum_end', 'direction', 'close', 'datum_close', 'sid', 'des_fact', 'kol_fact', 'pid_list','isFrozen');

array_unshift($fields, 'kol', 'marga', 'datum_izm', 'datum_start', 'datum_end', 'direction', 'close', 'datum_close', 'sid', 'des_fact', 'kol_fact', 'pid_list');

if($otherSettings['dateFieldForFreeze'] != ''){
	array_unshift($fields, 'isFrozen',$otherSettings['dateFieldForFreeze']);
}

array_unshift($fields, 'did', 'uid', 'clid', 'iduser', 'autor', 'datum', 'datum_plan', 'title', 'idcategory');

foreach ($fields as $key => $field) {

	if (in_array($field, $exclude_array)) {
		unset($fields[ $key ]);
	}

}

$fields = array_unique($fields);

$xq      = '';
$cfields = $db -> getAll("SELECT fld_title,fld_name FROM {$sqlname}field WHERE fld_tip = 'client' AND fld_on = 'yes' AND fld_name LIKE 'input%' AND identity = '$identity'");
foreach ($cfields as $cfield) {

	if ( in_array($cfield['fld_name'], $includes['client']) ) {

		$xq .= "{$sqlname}clientcat.".$cfield['fld_name']." as client".$cfield['fld_name'].",";

	}

}

//print
$query = getFilterQuery('dogovor', [
	'filter'        => $tar,
	'fields'        => $fields,
	'excludeDostup' => true,
	'selectplus'    => "
		{$sqlname}dogcategory.title as step,
		{$sqlname}dogcategory.content as steptitle,
		{$sqlname}dogtips.title as tips,
		{$sqlname}dogstatus.title as dstatus,
		{$sqlname}direction.title as direction,
		".(!in_array('path', $exclude_array) ? "{$sqlname}clientcat.clientpath as clientpath," : "")."
		".(in_array('tipcmr', $includes) ? "{$sqlname}clientcat.tip_cmr as tip_cmr," : "")."
		".(in_array('clientcategory', $includes) ? "{$sqlname}clientcat.idcategory as clidcategory," : "")."
		".(in_array('territory', $includes) ? "{$sqlname}clientcat.territory as territory," : "")."
		$xq
		{$sqlname}clientcat.date_create as cldcreate,
		{$sqlname}clientcat.uid as cluid
	",
	'freplace'      => [],
	'namereplace'   => false
], false);

$query .= " ORDER BY {$sqlname}dogovor.datum_plan";

$territories = Guides ::Territory();
$categories  = Guides ::Industry();
$otdel       = User ::otdel();

//формируем запрос в БД
$result = $db -> query($query);

$deals  = [];
$header = [];
$g      = 0;

while ($da = $db -> fetch($result)) {

	$mcomp = '';

	$header      = [
		"Deal.ID",
	];
	$deals[ $g ] = [
		$da['did'],
	];

	$i = 3;

	foreach ($fieldsNames as $k => $v) {

		if (!in_array($k, $exclude_array)) {

			//$client = get_client_info($da['clid'],"yes");

			switch ($k) {
				case 'iduser':
					$string = cleanall($users[ $da['iduser'] ]);
				break;
				case 'autor':
					$string = cleanall($users[ $da['autor'] ]);
				break;
				case 'clid':
					$string = cleanall($da['client']);
				break;
				case 'date_create':

					$string = $da['cldcreate'];

					if ($string == '0000-00-00') {

						$string = '';

					}

				break;
				case 'clientUID':
					$string = cleanall($da['cluid']);
				break;
				case 'payer':
					$string = cleanall(current_client($da['payer']));
				break;
				case 'idcategory':
					$string = $da['step'];
				break;
				case 'tip':
					$string = $da['tips'];
				break;
				case 'datum':
				case 'datum_plan':
				case 'datum_close':
				case 'datum_izm':
				case 'datum_start':
				case 'datum_end':

					$string = '';

					if ($da[ $k ] != '0000-00-00' && $da[ $k ] != '') {
						$string = $da[ $k ];
					}

				break;
				case 'kol':
				case 'kol_fact':
				case 'marga':
					/*if($format == 'xml') $string = n_format($da[$k]);
					else $string = num_format($da[$k]);*/
					$string = pre_format($da[ $k ]);
				break;
				case 'content':
				case 'des_fact':
					$string = cleanall(clll($da[ $k ]));
				break;
				case 'mcid':
					//$mcomp  = $db -> getOne("SELECT name_shot FROM {$sqlname}mycomps where id = '".$da['mcid']."' and identity = '$identity'");
					//$string = cleanall(clll($mcomp));
					$string = cleanall($da['mcid']);
				break;
				case 'close':
					if ($da['close'] == 'yes') $close = 'Закрыта';
					else $close = 'Активна';
					$string = $close;
				break;
				case 'dog_num':
					$dog_num = ($da['dog_num'] != '' && $da['dog_num'] != 'none') ? $db -> getOne("SELECT number FROM {$sqlname}contract WHERE deid = '".$da['dog_num']."' and identity = '$identity'") : "";
					$string  = $dog_num;
				break;
				case 'status_close':
					$string = cleanall($da['dstatus']);
				break;
				case 'path':
					$string = cleanall($clientpaths[ $da['clientpath'] ]);
					//$v = "Канал";
				break;
				case 'stepDate':

					$stepDay = $db -> getOne("SELECT datum FROM {$sqlname}steplog WHERE did = '".$da['did']."' and step = '".$da['idcategory']."' ORDER BY datum DESC LIMIT 1");
					$string  = $stepDay;

				break;
				default:
					$string = cleanall($da[ $k ]);
				break;

			}

			$deals[ $g ][ $i ] = cleanall($string);
			$header[ $i ]      = str_replace(" ", ".", cleanall($v));
			$i++;

			if ($k == 'clientUID') {

				$deals[ $g ][ $i ] = $da['clid'];
				$header[ $i ]      = 'Client.ID';
				$i++;

			}
			if ($k == 'clid' && in_array('tipcmr', $includes) && $fieldsClientNames['tip_cmr'] != '') {

				$deals[ $g ][ $i ] = $da['tip_cmr'];
				$header[ $i ]      = str_replace(" ", ".", $fieldsClientNames['tip_cmr']);
				$i++;

			}
			if ($k == 'clid' && in_array('clientcategory', $includes) && $fieldsClientNames['idcategory'] != '') {

				$deals[ $g ][ $i ] = $categories[ $da['clidcategory'] ];
				$header[ $i ]      = str_replace(" ", ".", $fieldsClientNames['idcategory']);
				$i++;

			}
			if ($k == 'clid' && in_array('territory', $includes) && $fieldsClientNames['territory'] != '') {

				$deals[ $g ][ $i ] = $territories[ $da['territory'] ];
				$header[ $i ]      = str_replace(" ", ".", $fieldsClientNames['territory']);
				$i++;

			}

			// проходим доп.поля клиентов
			if ($k == 'clid') {

				// $cfields = $db -> getAll("SELECT fld_title,fld_name FROM {$sqlname}field WHERE fld_tip = 'client' AND fld_on = 'yes' AND fld_name LIKE 'input%' AND identity = '$identity'");
				foreach ($cfields as $cfield) {

					if (in_array($cfield['fld_name'], $includes['client'])) {

						$deals[ $g ][ $i ] = cleanall($da[ "client".$cfield['fld_name'] ]);
						$header[ $i ]      = str_replace(" ", ".", $cfield['fld_title']);
						$i++;

					}

				}

			}

			if ($k == 'iduser') {

				$deals[ $g ][ $i ] = $otdel[ $da['iduser'] ];
				$header[ $i ]      = "Отдел";
				$i++;

			}

		}

	}

	if (in_array('lasthist', $includes) == 'yes') {

		$lh = $db -> getOne("SELECT datum FROM {$sqlname}history WHERE did = '".$da['did']."' and tip NOT IN ('ЛогCRM','СобытиеCRM') and identity = '$identity' ORDER BY cid DESC LIMIT 1");

		$deals[ $g ][ $i ] = $lh;
		$header[ $i ]      = 'Последняя активность';

		$i++;

	}

	if (in_array('nexttask', $includes) == 'yes') {

		$lt = $db -> getOne("SELECT CONCAT(datum, ' ', IFNULL(totime, '00:00:00')) FROM {$sqlname}tasks WHERE did = '".$da['did']."' AND active = 'yes' AND identity = '$identity' ORDER BY tid LIMIT 1");

		$deals[ $g ][ $i ] = $lt;
		$header[ $i ]      = 'След.напоминания';

		$i++;

	}

	if (in_array('history', $includes) == 'yes') {

		$hist = '';
		for ($k = 0; $k < 3; $k++) {

			$j = $k + 1;

			$resulth     = $db -> getRow("select datum, des from {$sqlname}history WHERE did='".$da['did']."' and tip NOT IN ('ЛогCRM','СобытиеCRM') and identity = '$identity' ORDER BY cid DESC LIMIT ".$k.", ".$j);
			$datum[ $k ] = $resulth["datum"];
			$des[ $k ]   = cleanall($resulth["des"]);

			if ($des[ $k ]) $hist .= $datum[ $k ].":".$des[ $k ].";";

		}

		$deals[ $g ][ $i ] = cleanall($hist);
		$header[ $i ]      = 'Активности';

		$i++;

	}

	//$g++;

	//контакт
	if (in_array('person', $includes) == 'yes') {

		$p = yexplode(";", getDogData($da['did'], "pid_list"), 0);

		$iperson = get_person_info($p, "yes");

		$header[ $i + 1 ] = 'Контакт';
		$header[ $i + 2 ] = 'Телефон';
		$header[ $i + 3 ] = 'Email';

		$deals[ $g ][ $i + 1 ] = $iperson['person'];
		$deals[ $g ][ $i + 2 ] = prepareMobPhone(yexplode(",", $iperson['tel'], 0));
		$deals[ $g ][ $i + 3 ] = yexplode(",", $iperson['mail'], 0);

		$i += 3;

	}

	$g++;

	//спецификация
	if (in_array('speca', $includes) == 'yes') {

		$r = $db -> getAll("SELECT * FROM {$sqlname}speca WHERE did = '".$da['did']."' and identity = '$identity' ORDER BY title");
		foreach ($r as $d) {

			$deals[ $g ][0] = "*";
			$deals[ $g ][1] = "SPECA";
			$deals[ $g ][2] = $d['title'];
			$deals[ $g ][3] = $d['kol'];
			$deals[ $g ][4] = $d['dop'];
			$deals[ $g ][5] = $d['price'];
			$deals[ $g ][6] = $d['nds'];
			$deals[ $g ][7] = cleanall($d['comments']);

			$g++;

		}

	}

}

array_unshift($deals, $header);

$time = time();
$file = 'export.deals.'.$time.'.xlsx';

Shuchkin\SimpleXLSXGen ::fromArray($deals) -> saveAs($rootpath.'/cash/export/'.$file);

// уведомляем о готовности
$notify = Notify ::edit(0, [
	"iduser"  => $UserID,
	"autor"   => $UserID,
	"tip"     => "note",
	"title"   => 'Экспорт сделок<hr><a href="'.$params['url'].'/cash/export/'.$file.'" class="button bluebtn dotted" target="_blank">скачать</a>',
	"content" => 'Файл экспорта, заказанный '.modifyDatetime($params['created'], ["format" => "d.m.Y в H:i"]).' готов. Вы можете получить его по ссылке:<hr><a href="'.$params['url'].'/cash/export/'.$file.'" class="button bluebtn dotted" target="_blank">скачать</a>'
]);

// удаляем задачу
$cron -> deleteTask($taskID);

exit();