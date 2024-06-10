<?php
/* ============================ */
/*         SalesMan CRM         */
/* ============================ */
/* (C) 2016 Vladislav Andreev   */
/*       SalesMan Project       */
/*        www.isaler.ru         */
/*        ver. 2017.x           */
/* ============================ */

error_reporting(E_ERROR);
header("Pragma: no-cache");

$rootpath = dirname(__DIR__, 2);

include $rootpath."/inc/config.php";
include $rootpath."/inc/dbconnector.php";
include $rootpath."/inc/auth.php";
include $rootpath."/inc/func.php";
include $rootpath."/inc/settings.php";
include $rootpath."/inc/language/".$language.".php";

$thisfile = basename( __FILE__ );

$page     = $_REQUEST['page'];
$iduser   = $_REQUEST['iduser'];
$word     = $_REQUEST['word'];
$dword    = trim(untag($_REQUEST['word']));
$alf      = $_REQUEST['alf'];
$tbl_list = $_REQUEST['tbl_list'];
$filter   = $_REQUEST['list'];
if ($filter == '') $filter = 'my';
$tar        = $_REQUEST['tar'];
$clientpath = $_GET['clientpath'];
$loyalty    = $_REQUEST['loyalty'];

$showHistTip = $_REQUEST['showHistTip'];

$haveEmail    = $_REQUEST['haveEmail'];
$havePhone    = $_REQUEST['havePhone'];
$haveMobPhone = $_REQUEST['haveMobPhone'];
$haveTask     = $_REQUEST['haveTask'];

$ord  = $_REQUEST['ord'];
$tuda = $_REQUEST['tuda'];

$queryArray = getFilterQuery('person', [
	'iduser'       => $iduser,
	'word'         => $word,
	'alf'          => $alf,
	'tbl_list'     => $tbl_list,
	'filter'       => $filter,
	'clientpath'   => $clientpath,
	'loyalty'      => $loyalty,
	'haveEmail'    => $haveEmail,
	'havePhone'    => $havePhone,
	'haveMobPhone' => $haveMobPhone,
	'haveTask'     => $haveTask,
	'fields'       => [
		'person',
		'ptitle',
		'clid',
		'tel',
		'mob',
		'mail',
		'iduser',
		'rol',
		'clientpath',
		'date_create'
	]
]);

//print_r($queryArray);

if ($ord == '') $ord = "person"; //параметр сортировки
elseif ($ord == 'title') $ord = 'person';
elseif ($ord == 'email') $ord = 'mail';
elseif ($ord == 'phone') $ord = 'tel';
elseif ($ord == 'category') $ord = 'idcategory';
elseif ($ord == 'user') $ord = 'iduser';
elseif ($ord == 'relation') $ord = 'loyalty';
elseif ($ord == 'role') $ord = 'rol';

//Загрузка настроек колонок для текущего пользователя
$f = $rootpath.'/cash/persons_columns_'.$iduser1.'.txt';
if (file_exists($f)) $file = $f;
else $file = $rootpath.'/cash/columns_default_person.json';

//направление сортировки
$des = ($tuda == 'desc') ? 'up' : 'down';

//заголовки колонок, настройки ширины и порядок вывода колонок
$cols = json_decode(str_replace("px", "", file_get_contents($file)), true);

if ($_COOKIE['width'] < 700) $cols['mail'] = [
	"on"    => "yes",
	"name"  => "Email",
	"width" => "100"
];

foreach ($cols as $key => $value) {

	if ($ord == $key) {
		$order = 'yes';
		$desc  = $des;
	}
	else {
		$order = '';
		$desc  = '';
	}

	if ($key == 'person') $key = 'title';
	if ($key == 'mail') $key = 'email';
	if ($key == 'tel') $key = 'phone';
	if ($key == 'iduser') $key = 'user';
	if ($key == 'loyalty') $key = 'relation';
	if ($key == 'last_history') $key = 'history';

	if (in_array($key, [
		'email-',
		'role',
		'clientpath',
		'history',
		'last_history_descr'
	])) {
		$class = 'hidden-netbook';
	}
	elseif (in_array($key, [
		'ptitle-',
		'phone-',
		'relation',
		'user-'
	])) {
		$class = 'hidden-ipad';
	}
	elseif ( in_array( $key, [
		'mob-',
		'email-',
		''
	], true ) ) {
		$class = 'ipad-100';
	}
	elseif ( in_array( $key, [
		'title',
		'client',
		''
	], true ) ) {
		$class = 'ipad-200';
	}
	else $class = '';

	if ($value['on'] == 'yes') {

		$header[ $key ] = [
			"title" => $value['name'],
			"width" => toWidth($value['width']),
			"sort"  => (in_array($key, ['last_history_descr','history'])) ? null : $key,
			"order" => $order,
			"desc"  => $desc,
			"clas"  => $class,
			"icon"  => ($key == 'relation') ? '<i class="icon-smile"></i>' : null
		];

	}

}

//проходим записи
$lines_per_page = $GLOBALS['num_person']; //Стоимость записей на страницу

//print $queryArray['queryCount'];

//$all_lines = count($db -> getCol($queryArray['queryCount']));
$all_lines = $db -> getOne($queryArray['queryCount']);

if ($page > ceil($all_lines / $lines_per_page)) $page = 1;

if (!isset($page) || empty($page) || $page <= 0) $page = 1;
else $page = (int)$page;
$page_for_query = $page - 1;
$lpos           = $page_for_query * $lines_per_page;

$query = $queryArray['query']." ORDER by ".($ord == 'client' ? $sqlname."clientcat.title" : $sqlname."personcat.".$ord)." $tuda LIMIT $lpos,$lines_per_page";

$rest        = $db -> query($query);
$count_pages = ceil($all_lines / $lines_per_page);

while ($da = $db -> fetch($rest)) {

	$phone        = '';
	$mob          = '';
	$email        = '';
	$relation     = '';
	$history      = '';
	$historyDate  = '';
	$color        = '';
	$sup          = '';
	$isaccess     = '';
	$historyDate  = '';
	$clientpath   = '';
	$lastHistTip  = '';
	$lastHistDesc = '';

	if ($cols['loyalty']['on'] == 'yes' && $da['relation'] != '') {

		if ($da['relation'] != '') {

			$relation      = $da['relation'];
			$relationShort = substr($da['relation'], 0, 2);

		}
		else {

			$relation      = 'Не определено';
			$relationShort = '?';

		}

	}

	if ($cols['clientpath']['on'] == 'yes')
		$clientpath = $da['clientpath'];

	if ($da['clid'] < 1) {

		$da['clid']   = '';
		$da['client'] = '';

	}

	$isaccess = get_accesse(0, (int)$da['pid']);

	if ($cols['last_history']['on'] == 'yes' || $cols['last_history_descr']['on'] == 'yes') {

		$re = $db -> getRow("SELECT datum, tip, substring(des, 1, 100) as des FROM ".$sqlname."history WHERE pid = '".$da['pid']."' and tip NOT IN ('СобытиеCRM','ЛогCRM') and identity = '$identity' ORDER BY datum DESC LIMIT 1");

		if ($cols['last_history_descr']['on'] == 'yes')
			$lastHistDesc = strip_tags($re['des']);


		if ($cols['last_history']['on'] == 'yes') {

			$hist = $re['datum'];

			if ($showHistTip == 'yes' && $re['tip'] != '')
				$lastHistTip = $re['tip'];

			if ($hist != null) {
				$history     = strip_tags(diffDateTime($hist)).' назад';
				$historyDate = diffDateTime($hist);
			}

		}

	}

	if ($cols['tel']['on'] == 'yes') {

		$phone = '';

		if ($da['tel'] != '') {
			$phone = yexplode(",", str_replace(";", ",", $da['tel']), 0);
		}

		if ($phone != '') {
			//$phone = ($isaccess != 'yes' && $acs_prava != 'on') && $userSettings['hideAllContacts'] == 'yes' ? '<span class="gray">'.yimplode( ",", hidePhone( $phone ) ).'</span>' : formatPhoneUrl( $phone, (int)$da['clid'], (int)$da['pid'] );
			$phone = ($isaccess == 'yes' || $acs_prava == 'on') && $userSettings['hideAllContacts'] != 'yes' ? formatPhoneUrl( $phone, $da['clid'], $da['pid'] ) : '<span class="gray">'.hidePhone( $phone ).'</span>';
		}

	}

	if ($cols['mob']['on'] == 'yes') {

		$mobi = [];

		if ($da['mob'] != '') {
			$mobi = yexplode(",", str_replace(";", ",", str_replace(" ", "", $da['mob'])), 0);
		}

		if (!empty($mobi)) {
			//$mob = ( $isaccess != 'yes' && $acs_prava != 'on' ) && $userSettings['hideAllContacts'] == 'yes' ? '<span class="gray">'.yimplode(",", hidePhone($mobi)).'</span>' : formatPhoneUrl($mobi, (int)$da['clid'], (int)$da['pid']);
			$mob = ($isaccess == 'yes' || $acs_prava == 'on') && $userSettings['hideAllContacts'] != 'yes' ? formatPhoneUrl( $mobi, $da['clid'], $da['pid'] ) : '<span class="gray">'.hidePhone( $mobi ).'</span>';
		}

	}

	if ($cols['mail']['on'] == 'yes') {

		$email = '';
		$mail = [];

		if ($da['mail'] != '') {
			$mail = yexplode(",", str_replace(";", ",", str_replace(" ", "", $da['mail'])), 0);
		}

		if (!empty($mail)) {
			//$email = ( $isaccess != 'yes' && $acs_prava != 'on' ) && $userSettings['hideAllContacts'] == 'yes' ? '<span class="gray">'.yimplode(",", hideEmail($mail)).'</span>' : link_it($mail);
			$email = ($isaccess == 'yes' || $acs_prava == 'on') && $userSettings['hideAllContacts'] != 'yes' ? link_it( $mail ) : '<span class="gray">'.hideEmail( $mail ).'</span>';
		}

	}


	//проверим права на просмотр данных по конкретной организации
	if ( ( $isaccess != 'yes' ) && !$userRights['showhistory'] ) {

		$history      = '***';
		$lastHistDesc = '***';

	}

	//формируем массив данных
	$row = [
		"pid"         => $da['pid'],
		"date_create" => get_date($da['date_create']),
		"title"       => $da['person'],
		"ptitle"      => ($da['ptitle'] != '') ? $da['ptitle'] : '--',
		//"relation"     => $da['relation'],
		"color"       => $color,
		"phone"       => ($phone != '') ? $phone : '-',
		"mob"         => ($mob != '') ? $mob : '-',
		"email"       => ($email != '') ? $email : '-',
		"role"        => $da['rol'],
		"clientpath"  => $clientpath,
		"clid"        => $da['clid'],
		"client"      => $da['client'],
		"iduser"      => $da['iduser'],
		"user"        => $da['user'],
		"change"      => $isaccess == 'yes' ? true : null,
		//"history"      => $history,
		//"hday"         => $historyDate,
		//"lastHistTip"  => $lastHistTip,
		//"lastHistDesc" => $lastHistDesc
	];

	$columns = [];
	foreach (array_keys($header) as $key) {

		switch ($key) {

			case "title":

				$columns[] = [
					"isTitle" => 1,
					"pid"     => $da['pid'],
					"title"   => $da['person'],
					"sub"     => ( array_key_exists( "ptitle", $header ) && $da[ 'ptitle'] == '' && array_key_exists( "client", $header ) ) ? null :
						[
							"ptitle" => ($da['ptitle'] != '' && !array_key_exists( "ptitle", $header ) ) ? $da[ 'ptitle'] : null,
							"client" => ( array_key_exists( "client", $header ) || $da[ 'client'] == '') ? null :
								[
									"clid"   => $da['clid'],
									"client" => ($da['client'] != '') ? $da['client'] : null
								]
						],
					"clas"    => $header[ $key ]['clas']
				];

			break;
			case "client":

				$columns[] = [
					"isClient" => 1,
					"id"       => ($da['client'] != '') ? $da['clid'] : null,
					"title"    => ($da['client'] != '') ? $da['client'] : null,
					"clas"     => $header[ $key ]['clas']
				];

			break;
			case "history":

				$columns[] = [
					"isHistory" => 1,
					"title"     => $history,
					"comment"   => $lastHistTip,
					"clas"      => $header[ $key ]['clas']
				];

			break;
			case "last_history_descr":

				$columns[] = [
					"isLastHistory" => 1,
					"title"         => $lastHistTip,
					"comment"       => $lastHistDesc,
					"clas"          => $header[ $key ]['clas']
				];

			break;
			case "relation":

				$columns[] = [
					"isRelation" => 1,
					"title"      => $relationShort,
					"tooltip"    => $da['relation'],
					"color"      => (!$da['color']) ? 'transparent' : $da['color'],
					"clas"       => $header[ $key ]['clas']
				];

			break;
			default:

				$columns[] = [
					"isString" => 1,
					"title"    => $row[ $key ],
					"tooltip"  => untag($row[ $key ]),
					"clas"     => $header[ $key ]['clas']
				];

			break;

		}

	}

	$row['columns'] = $columns;

	$person[] = $row;

}

//print_r($header);

$persons = [
	"header"  => $header,
	"head"    => array_values($header),
	"person"  => $person,
	"page"    => (int)$page,
	"pageall" => (int)$count_pages,
	"count"   => (int)$all_lines
];

print json_encode_cyr($persons);