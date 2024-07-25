<?php
/* ============================ */
/*         SalesMan CRM         */
/* ============================ */
/* (C) 2016 Vladislav Andreev   */
/*       SalesMan Project       */
/*        www.isaler.ru         */
/*        ver. 2017.x           */
/* ============================ */

error_reporting( E_ERROR );
header( "Pragma: no-cache" );

$rootpath = dirname( __DIR__, 2 );

include $rootpath."/inc/config.php";
include $rootpath."/inc/dbconnector.php";
include $rootpath."/inc/auth.php";
include $rootpath."/inc/func.php";
include $rootpath."/inc/settings.php";
include $rootpath."/inc/language/".$language.".php";

$thisfile = basename( __FILE__ );

$ord        = untag( $_REQUEST['ord'] );
$tuda       = untag( $_REQUEST['tuda'] );
$page       = (int)$_REQUEST['page'];
$iduser     = (int)$_REQUEST['iduser'];
$idcategory = is_array( $_REQUEST['idcategory'] ) ? implode( ",", $_REQUEST['idcategory'] ) : [];
$word       = untag( $_REQUEST['word'] );
$alf        = untag( $_REQUEST['alf'] );
$tbl_list   = untag( $_REQUEST['tbl_list'] );
$filter     = $xfilter = $_REQUEST['list'];
$groups     = $_REQUEST['groups'];

$tip_cmr = $_REQUEST['tip_cmr'];

$clientpath = $_REQUEST['clientpath'];

if ( isset( $_REQUEST['clientpath0'] ) ) {
	$clientpath[] = $_REQUEST['clientpath0'];
}

//$clientpath = implode(",", $clientpath);

$territory = (array)$_REQUEST['territory'];
$type      = $_REQUEST['type'];

$haveEmail   = $_REQUEST['haveEmail'];
$havePhone   = $_REQUEST['havePhone'];
$haveTask    = $_REQUEST['haveTask'];
$haveHistory = $_REQUEST['haveHistory'];
$otherParam  = $_REQUEST['otherParam'];

$dog_history    = $_REQUEST['dog_history'];
$client_history = $_REQUEST['client_history'];

$showHistTip = $_REQUEST['showHistTip'];


if ( $ord == '' )
	$ord = "title"; //параметр сортировки
elseif ( $ord == 'email' )
	$ord = 'mail_url';
elseif ( $ord == 'site' )
	$ord = 'site_url';
elseif ( $ord == 'category' )
	$ord = 'idcategory';
elseif ( $ord == 'user' )
	$ord = 'iduser';
elseif ( $ord == 'relation' )
	$ord = 'tip_cmr';
elseif ( $ord == 'dcreate' )
	$ord = 'date_create';
elseif ( $ord == 'history' )
	$ord = 'last_hist';
elseif ( $ord == 'dogovor' )
	$ord = 'last_dog';
elseif ( $ord == 'last_hist' )
	$ord = 'last_history';


//направление сортировки
if ( $tuda == 'desc' ) {
	$des = 'up';
}
else {
	$des = 'down';
}

if(!in_array($xfilter, ['partner','contractor','concurent'])){
	$xfilter = "client";
}

//Загрузка настроек колонок для текущего пользователя
$f = $rootpath."/cash/{$xfilter}s_columns_{$iduser1}.txt";

$file = file_exists( $f ) ? $f : $rootpath.'/cash/columns_default_client.json';

//заголовки колонок, настройки ширины и порядок вывода колонок
$cols = json_decode( str_replace( "px", "", file_get_contents( $file ) ), true );
$dops = [];
foreach ( $cols as $key => $value ) {

	if ( $ord == $key ) {
		$order = 'yes';
		$desc  = $des;
	}
	else {
		$order = '';
		$desc  = '';
	}

	//доп.поля для запроса
	if ( in_array( $key, $fieldsOn['client'], true ) )
		$dops[] = $key;

	if ( $key == 'mail' )
		$key = 'email';
	if ( $key == 'site' )
		$key = 'site';
	if ( $key == 'idcategory' )
		$key = 'category';
	if ( $key == 'iduser' )
		$key = 'user';
	if ( $key == 'tip_cmr' )
		$key = 'relation';
	if ( $key == 'date_create' )
		$key = 'dcreate';
	if ( $key == 'last_history' )
		$key = 'history';
	if ( $key == 'last_dog' )
		$key = 'dogovor';

	$class = '';

	if ( in_array( $key, [
		'email',
		'site',
		'category',
		'territory',
		'clientpath',
		'history',
		'dogovor',
		'relation',
		'tip_cmrr',
		'last_history_descr'
	] ) ) {
		$class = 'hidden-netbook';
	}

	elseif ( in_array( $key, [
		'user',
		'zakaz_kol',
		'zakaz_sum',
		'dcreate'
	] ) ) {
		$class = 'hidden-ipad';
	}

	elseif ( stripos( $key, 'input' ) !== false ) {
		$class = 'hidden-ipad';
	}


	if ( $value['on'] == 'yes' ) {

		$header[ $key ] = [
			"id"    => $key,
			"title" => $value['name'],
			"width" => ($key != 'dogovor') ? toWidth( $value['width'] ) : "80",
			"sort"  => (in_array( $key, [
				'last_history_descr',
				'zakaz_sum',
				'zakaz_kol'
			] )) ? "" : $key,
			"order" => $order,
			"desc"  => $desc,
			"clas"  => $class,
			"icon"  => ($key == 'relation') ? '<i class="icon-smile"></i>' : NULL
		];

	}

}

//включим доп.поля
$fields = $db -> getCol( "select fld_name from {$sqlname}field where fld_tip='client' and fld_on = 'yes' and fld_name LIKE '%input%' and identity = '$identity'" );

array_push( $fields, 'title', 'idcategory', 'date_create', 'pid', 'type', 'phone', 'site_url', 'mail_url', 'iduser', 'tip_cmr', 'clientpath', 'last_hist', 'last_dog', 'address' );

//$fields = array_unique(array_merge($fields, $dops));
//print_r($fields);

$closeStatusWin = $db -> getCol( "SELECT sid FROM {$sqlname}dogstatus WHERE result_close = 'win' AND identity = '$identity' ORDER by title" );

$queryArray = getFilterQuery( 'client', $param = [
	'iduser'         => $iduser,
	'iduser1'        => $iduser1,
	'word'           => $word,
	'alf'            => $alf,
	'tbl_list'       => $tbl_list,
	'filter'         => $filter,
	'idcategory'     => $idcategory,
	'clientpath'     => $clientpath,
	'clientpath0'    => $clientpath0,
	'territory'      => $territory,
	'tip_cmr'        => $tip_cmr,
	'type'           => $type,
	'haveEmail'      => $haveEmail,
	'havePhone'      => $havePhone,
	'haveTask'       => $haveTask,
	'haveHistory'    => $haveHistory,
	'otherParam'     => $otherParam,
	'groups'         => $groups,
	'dog_history'    => $dog_history,
	'client_history' => $client_history,
	'dostup'         => $_REQUEST['dostup'],
	'fields'         => $fields
] );

//print_r($param);
//print_r($queryArray);

//print $queryArray['query'];
//exit();


$tp   = [
	'client'     => 'icon-building',
	'contractor' => 'icon-building',
	'partner'    => 'icon-building',
	'concurent'  => 'icon-building',
	'person'     => 'icon-user-1'
];
$sups = [
	"partner"    => "blue",
	"contractor" => "green",
	"concurent"  => "red"
];

//сохраним настройки в cookie
setcookie( "client_list", "" );
$json = [
	"iduser"     => $iduser,
	"idcategory" => $idcategory,
	"alf"        => $alf,
	"tar"        => $filter,
	"tip_cmrr"   => $tip_cmr,
	"clientpath" => $clientpath,
	"territory"  => $territory,
	"ord"        => $ord,
	"tuda"       => $tuda,
	"groups"     => $groups
];

$data_set = json_encode_cyr( $json );
setcookie( "client_list", $data_set, time() + 365 * 86400, "/" );

//конечный запрос
$query = $queryArray['queryCount'];
//exit();

//запрос по кол-ву записей
//$all_lines = count($db -> getCol($query));
$all_lines = $db -> getOne( $query );

$lines_per_page = $GLOBALS['num_client'];

if ( $page > ceil( $all_lines / $lines_per_page ) ) {
	$page = 1;
}

if ( empty( $page ) || $page <= 0 ) {
	$page = 1;
}

$page_for_query = $page - 1;
$lpos           = $page_for_query * $lines_per_page;

$count_pages = ceil( $all_lines / $lines_per_page );

if ( $count_pages < 1 ) {
	$count_pages = 1;
}

//названия заголовков таблицы
$result = $db -> getAll( "select fld_title,fld_name from {$sqlname}field where fld_tip='client' and (fld_name IN ('date_create','title','pid','tip_cmr','idcategory','iduser','phone','mail','site','territory','clientpath','last_dog','last_history','address') or fld_name LIKE '%input%') and identity = '$identity'" );
foreach ( $result as $data ) {

	$h[ $data['fld_name'] ] = $data['fld_title'];

}

//записи, к которомы у текущего пользователя есть доступ
$dostup_array = $db -> getCol( "SELECT clid FROM {$sqlname}dostup WHERE iduser = '".$iduser1."' and identity = '$identity'" );

if ( $ord == 'last_hist' ) {
	$ord = 'last_history';
}
elseif ( $ord == 'last_history' ) {
	$ord = 'last_history';
}
elseif ( $ord == 'dogovor' ) {
	$ord = 'last_deal';
}
elseif ( $ord == 'last_dog' ) {
	$ord = 'last_deal';
}
else {
	$ord = $sqlname."clientcat.".$ord;
}

//проходим записи
//print
$query = $queryArray['query']." ORDER BY $ord $tuda LIMIT $lpos,$lines_per_page";
$rest  = $db -> query( $query );
while ($da = $db -> fetch( $rest )) {

	$phone        = NULL;
	$email        = NULL;
	$site         = NULL;
	$history      = NULL;
	$historyDate  = NULL;
	$color        = NULL;
	$sup          = NULL;
	$task         = NULL;
	$deal         = NULL;
	$isaccess     = NULL;
	$category     = NULL;
	$territory    = NULL;
	$clientpath   = NULL;
	$dogovor      = NULL;
	$lastHistTip  = NULL;
	$lastHistDesc = NULL;
	$zakaz_kol    = 0;
	$zakaz_sum    = 0;
	$cd = 'Закрытых: нет';

	if ( $cols['idcategory']['on'] == 'yes' ) {
		$category = $da['category'];
	}
	if ( $cols['territory']['on'] == 'yes' ) {
		$territory = $da['territory'];
	}
	if ( $cols['clientpath']['on'] == 'yes' ) {
		$clientpath = $da['clientpath'];
	}

	if ( (int)$da['pid'] == 0 ) {

		$da['pid']    = NULL;
		$da['person'] = NULL;

	}

	$isaccess = get_accesse( (int)$da['clid'] );

	$color = (!$da['color']) ? 'transparent' : $da['color'];

	if ( $cols['last_history']['on'] == 'yes' || $cols['last_history_descr']['on'] == 'yes' ) {

		$re = $db -> getRow( "SELECT cid, tip as tip, datum, substring(des, 1, 100) as des FROM {$sqlname}history WHERE clid = '".$da['clid']."' and tip NOT IN ('СобытиеCRM','ЛогCRM') and identity = '$identity' ORDER BY cid DESC LIMIT 1" );

		if(!empty($re)) {

			if ($cols['last_history_descr']['on'] == 'yes') {
				$lastHistDesc = strip_tags($re['des']);
			}

			$lastHistTip   = $re['tip'];
			$lastHistDatum = $re['datum'];

			if ( $lastHistDatum != '0000-00-00 00:00:00' && $lastHistDatum != '' ) {

				$history     = strip_tags( diffDateTime( $lastHistDatum ) ).' назад';
				$historyDate = diffDateTime( $lastHistDatum );

			}

		}

	}

	if ( $cols['last_dog']['on'] == 'yes' && $da['last_deal'] != '0000-00-00' && $da['last_deal'] != '' ) {

		$dogovor = strip_tags( diffDateTime( $da['last_deal'].' 00:00:00' ) ).' назад';

	}

	//Проверим налиие напоминаний
	$count = (int)$db -> getOne( "SELECT COUNT(tid) as count FROM {$sqlname}tasks WHERE clid = '".$da['clid']."' and active = 'yes' and identity = '$identity'" );

	if ( $isaccess == 'yes' && $count > 0 ) {
		$task = 'yes';
	}

	//Выведем индикатор наличия сделок
	$countCloseBad = (int)$db -> getOne( "SELECT COUNT(did) as count FROM {$sqlname}dogovor WHERE clid = '".$da['clid']."' and close = 'yes' and sid NOT IN (".yimplode( ",", $closeStatusWin ).") and identity = '$identity'" );

	$re             = $db -> getRow( "SELECT COUNT(did) as count, SUM(kol_fact) as summa FROM {$sqlname}dogovor WHERE clid='".$da['clid']."' and close='yes' and sid IN (".yimplode( ",", $closeStatusWin ).") and identity = '$identity'" );
	$countCloseGood = (int)$re['count'];
	$zakaz_sum      = (float)$re['summa'];

	$countClose = $countCloseBad + $countCloseGood;

	$countActive = (int)$db -> getOne( "SELECT COUNT(did) as count FROM {$sqlname}dogovor WHERE clid='".$da['clid']."' and close != 'yes' and identity = '$identity'" );

	if ( $countClose > 0 ) {
		$cd = 'Закрытые сделки - Всего: '.$countClose.', С прибылью: '.$countCloseGood.', Без прибыли: '.$countCloseBad;
	}

	$countDealTotal = $countActive + $countClose;

	if ( $countActive > 0 ) {
		$deal = '<a href="javascript:void(0)" onclick="doLoad(\'/content/vigets/viget.dataview.php?action=dogsView&clid='.$da['clid'].'\')" class="gray red" title="Активных сделок: '.$countActive.'. '.$cd.'"><i class="icon-briefcase red list"></i></a>';
	}
	if ( $countActive == 0 && $countClose > 0 ) {
		$deal = '<a href="javascript:void(0)" onclick="doLoad(\'/content/vigets/viget.dataview.php?action=dogsView&clid='.$da['clid'].'\')" class="gray gray2" title="Есть закрытые сделки. Всего: '.$countClose.', С прибылью: '.$countCloseGood.', Без прибыли: '.$countCloseBad.'"><i class="icon-briefcase list"></i></a>';
	}


	if ( in_array( $da['clid'], $dostup_array ) ) {
		$sup = '<i class="icon-lock-open green smalltxt sup" title="Вам предоставлен доступ"></i>';
	}

	if ( $cols['phone']['on'] == 'yes' ) {

		$tel = '';

		if ( $da['phone'] != '' ) {
			$tel = yexplode(",", str_replace(";", ",", str_replace(" ", "", $da['phone'])), 0);
		}
		elseif ( $da['mob'] != '' ) {
			$tel = yexplode(",", str_replace(";", ",", str_replace(" ", "", $da['mob'])), 0);
		}
		elseif ( $da['tel'] != '' ) {
			$tel = yexplode(",", str_replace(";", ",", str_replace(" ", "", $da['tel'])), 0);
		}

		if( $userSettings['hideAllContacts'] == 'yes' && ($isaccess != 'yes' && $acs_prava != 'on') ){
			$phone = '<span class="gray">'.hidePhone( $tel ).'</span>';
		}
		else{
			$phone = ($isaccess == 'yes' || $acs_prava == 'on') ? formatPhoneUrl( $tel, $da['clid'], $da['pid'] ) : '<span class="gray">'.hidePhone( $tel ).'</span>';
		}

	}

	if ( $cols['mail']['on'] == 'yes' ) {

		$mail = '';

		if ( $da['mail_url'] != '' ) {
			$mail = yexplode(",", str_replace(";", ",", str_replace(" ", "", $da['mail_url'])), 0);
		}
		elseif ( $da['pemail'] != '' ) {
			$mail = yexplode(",", str_replace(";", ",", str_replace(" ", "", $da['pemail'])), 0);
		}

		if( $userSettings['hideAllContacts'] == 'yes' && ($isaccess != 'yes' && $acs_prava != 'on') ){
			$email = '<span class="gray">'.hideEmail( $mail ).'</span>';
		}
		else {
			$email = ( $isaccess == 'yes' || $acs_prava == 'on' ) ? link_it($mail) : '<span class="gray">'.hideEmail($mail).'</span>';
		}

	}

	if ( $cols['site']['on'] == 'yes' ) {

		$sitearray = explode( ",", str_replace( ";", ",", str_replace( " ", "", $da['site_url'] ) ) );
		$site      = link_it( array_shift( $sitearray ) );

	}

	//проверим права на просмотр данных по конкретной организации
	//if($isaccess!='yes'){
	if ( $isadmin != 'yes' ) {

		if ( $isaccess != 'yes' && !$userRights['showhistory'] ) {

			$history      = '???';
			$lastHistDesc = '???';
			//$phone        = '<span class="gray">--скрыто--</span>';
			//$email        = '<span class="gray">--скрыто--</span>';
			//$site         = '<span class="gray">--скрыто--</span>';
			//$da['person'] = '***';

		}
		if ( $isaccess != 'yes' && $acs_prava != 'on' ) {

			//$history = "***";
			//$phone        = '<span class="gray">--скрыто--</span>';
			//$email        = '<span class="gray">--скрыто--</span>';
			$site = '<span class="gray">???</span>';
			//$da['person'] = '***';

		}

	}

	if ( in_array( $da['type'], [
		'partner',
		'contractor',
		'concurent'
	] ) ) {
		$sup = '<i class="icon-flag '.strtr( $da['type'], $sups ).' smalltxt sup"></i>';
	}


	if ( $da['tip_cmr'] != '' ) {

		$relation      = $da['tip_cmr'];
		$relationShort = substr( $da['tip_cmr'], 0, 2 );

	}
	else {

		$relation      = 'Не определено';
		$relationShort = '?';

	}

	//формируем массив данных
	$row = [
		"clid"          => (int)$da['clid'],
		"title"         => $da['title'],
		"icon"          => strtr( $da['type'], $tp ),
		"relation"      => $relation,
		"color"         => $color,
		"relationShort" => $relationShort,
		"phone"         => $phone,
		"email"         => $email,
		"site"          => $site,
		"category"      => $category,
		"territory"     => $territory,
		"clientpath"    => $clientpath,
		"dcreate"       => get_sfdate( $da['date_create'] ),
		"pid"           => (int)$da['pid'] > 0 ? (int)$da['pid'] : NULL,
		"person"        => (int)$da['pid'] > 0 ? $da['person'] : NULL,
		"iduser"        => (int)$da['iduser'],
		"user"          => $da['user'],
		"address"       => $da['address'],
		"change"        => $isaccess == "yes" ? true : NULL,
		"history"       => $history,
		"hday"          => $historyDate,
		"dostup"        => $sup,
		"task"          => $task,
		"deal"          => $deal,
		"dogovor"       => $dogovor,
		//"lastHistTip"   => $lastHistTip,
		//"lastHistDesc"  => $lastHistDesc,
		//"zakaz_kol"     => $countCloseGood,
		//"zakaz_akol"    => $countActive,
		//"zakaz_sum"     => num_format($zakaz_sum)
	];

	//доп.поля
	$re = $db -> getAll( "select fld_name from {$sqlname}field where fld_tip='client' and fld_on = 'yes' and fld_name LIKE '%input%' and identity = '$identity'" );
	foreach ( $re as $data ) {

		$row[ $data['fld_name'] ] = $da[ $data['fld_name'] ];

	}

	$columns = [];
	foreach ( array_keys( $header ) as $key ) {

		switch ($key) {

			case "title":

				$columns[] = [
					"isTitle" => 1,
					"clid"    => (int)$da['clid'],
					"title"   => remove_emoji($da['title']),
					"task"    => ($task != '') ? $task : NULL,
					"sub"     => (array_key_exists( "pid", $header ) || (int)$da['pid'] == 0) ? NULL : [
						"person" => [
							"pid"    => (int)$da['pid'] > 0 ? (int)$da['pid'] : NULL,
							"person" => (int)$da['pid'] > 0 ? current_person( $da['pid'] ) : NULL
						]
					],
					"clas"    => $header[ $key ]['clas']
				];

			break;
			case "pid":

				$columns[] = [
					"isPerson" => 1,
					"id"       => ((int)$da['pid'] > 0) ? (int)$da['pid'] : NULL,
					"title"    => ((int)$da['pid'] > 0) ? current_person( (int)$da['pid'] ) : NULL,
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
			case "zakaz_kol":

				$columns[] = [
					"isString"   => 1,
					"isSum"      => 1,
					"title"      => $countCloseGood,
					"tooltip"    => "Успешных сделок: ".$countCloseGood,
					"subtitle"   => $countDealTotal > 0 ? $countDealTotal : NULL,
					"subtooltip" => $countDealTotal > 0 ? "Всего сделок: ".$countDealTotal : NULL,
					"clas"       => $header[ $key ]['clas']
				];

			break;
			case "zakaz_sum":

				$columns[] = [
					"isString" => 1,
					"isSum"    => 1,
					"title"    => num_format( $zakaz_sum ),
					"tooltip"  => num_format( $zakaz_sum ),
					"clas"     => $header[ $key ]['clas']
				];

			break;
			case "relation":

				$columns[] = [
					"isRelation" => 1,
					"title"      => $relationShort,
					"subtitle"   => $countDealTotal > 0 ? $countDealTotal : NULL,
					"tooltip"    => $relation,
					"color"      => $color,
					"clas"       => $header[ $key ]['clas']
				];

			break;
			default:

				$columns[] = [
					"isString" => 1,
					"title"    => remove_emoji($row[ $key ]),
					"tooltip"  => untag( $row[ $key ] ),
					"clas"     => $header[ $key ]['clas']
				];

			break;

		}

	}

	$row['columns'] = $columns;

	$client[] = $row;

}

$clients = [
	"header"  => $header,
	"head"    => array_values( $header ),
	"client"  => $client,
	"profile" => ($otherSettings['profile']) ? 'yes' : NULL,
	"page"    => (int)$page,
	"pageall" => (int)$count_pages,
	"count"   => (int)$all_lines,
	//"hideAllContacts" => $userSettings['hideAllContacts']
];

//print_r($client);
//print json_encode( $clients );

//print $query."\n\n";
//print_r($clients);

//file_put_contents($rootpath."/cash/clientlist.json", json_encode( $clients ));

print json_encode_cyr( $clients );

//flush();
//exit();