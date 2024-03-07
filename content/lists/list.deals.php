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

$page        = $_REQUEST['page'];
$iduser      = $_REQUEST['iduser'];
$idcategory  = $_REQUEST['idcategory'];
$word        = $_REQUEST['word'];
$tbl_list    = $_REQUEST['tbl_list'];
$tid         = $_REQUEST['tid'];
$tar         = $_REQUEST['list'];
$direction   = $_REQUEST['direction'];
$mcid        = $_REQUEST['mcid'];
$isOld       = $_REQUEST['isOld'];
$haveCredit  = $_REQUEST['haveCredit'];
$haveHistory = $_REQUEST['haveHistory'];
$haveTask    = $_REQUEST['haveTask'];
$isFrozen    = $_REQUEST['isFrozen'];

$ord  = $xord = $_REQUEST['ord'];
$tuda = $_REQUEST['tuda'];

//включим доп.поля
$fields = $db -> getCol( "SELECT fld_name FROM ".$sqlname."field WHERE fld_tip='dogovor' AND fld_on = 'yes' AND fld_name LIKE '%input%' AND identity = '$identity'" );

// этап заморозки
$stepInHold = customSettings( 'stepInHold' );

//добавим базовые поля
array_push( $fields, 'title', 'datum', 'datum_plan', 'datum_close', 'idcategory', 'tip', 'clid', 'pid', 'kol', 'marga', 'kol_fact', 'close', 'iduser', 'adres', 'mcid' );

if ( $otherSettings['dateFieldForFreeze'] != '' ) {

	$fi = $db -> getRow( "SHOW COLUMNS FROM ".$sqlname."dogovor LIKE 'isFrozen'" );
	if ( $fi['Field'] != '' ) {
		$fields[] = 'isFrozen';
		$fields[] = $otherSettings['dateFieldForFreeze'];
	}

}

$prms = [
	'iduser'      => $iduser,
	'word'        => $word,
	'tbl_list'    => $tbl_list,
	'filter'      => $tar,
	'idcategory'  => $idcategory,
	'tid'         => $tid,
	'direction'   => $direction,
	'mcid'        => $mcid,
	'isOld'       => $isOld,
	'haveCredit'  => $haveCredit,
	'haveHistory' => $haveHistory,
	'haveTask'    => $haveTask,
	'dostup'      => $_REQUEST['dostup'],
	'fields'      => $fields,
	'client'      => $_REQUEST['client'] ?? NULL,
	'namereplace' => true
];

if ( $otherSettings['dateFieldForFreeze'] != '' ) {
	$fi = $db -> getRow( "SHOW COLUMNS FROM ".$sqlname."dogovor LIKE 'isFrozen'" );
	if ( $fi['Field'] != '' ) {
		$prms['isFrozen'] = $isFrozen;
	}
}

//print_r($prms);

//формируем запрос
$queryArray = getFilterQuery( 'dogovor', $prms );

//print_r($queryArray);
//exit();

//print $queryArray['query'];

$showHistTip = $_REQUEST['showHistTip'];

if ( $ord == '' ) {
	$ord = "datum_plan";
}
elseif ( $ord == 'dcreate' ) {
	$ord = 'datum';
}
elseif ( $ord == 'datum_plan' ) {
	$ord = 'datum_plan';
}
elseif ( $ord == 'dplan' ) {
	$ord = 'datum_plan';
}
elseif ( $ord == 'status' ) {
	$ord = 'idcategory';
}
elseif ( $ord == 'user' ) {
	$ord = 'iduser';
}
elseif ( $ord == 'marg' ) {
	$ord = 'marga';
}
elseif ( $ord == 'history' ) {
	$ord = 'last_hist';
}
elseif ( $ord == 'last_hist' ) {
	$ord = 'last_history';
}

//сохраним настройки в cookie
setcookie( "deal_list", "" );
$json = [
	"iduser"     => $iduser,
	"idcategory" => $_REQUEST['idcategory'],
	"alf"        => $alf,
	"tar"        => $tar,
	"direction"  => $_REQUEST['direction'],
	"mcid"       => $_REQUEST['mcid'],
	"ord"        => $ord,
	"tuda"       => $tuda,
	"tid"        => $_REQUEST['tid']
];


$data_set = json_encode_cyr( $json );
setcookie( "deal_list", $data_set, time() + 365 * 86400, "/" );

//определяем цветовую подсветку по дате добавления
$color = [
	'#FFD9D9',
	'#FFFF99',
	'#FF99CC',
	'#FFD700'
];

$lines_per_page = $GLOBALS['num_dogs'];

//типы сервисных сделок
$servicesTips = isServices();
$service      = implode( ",", $servicesTips );

if ( $ord == 'idcategory' ) {
	$ord2 = $sqlname."dogcategory.title";
}
elseif ( $ord == 'client' ) {
	$ord2 = $sqlname."clientcat.title";
}
elseif ( $ord == 'iduser' ) {
	$ord2 = $sqlname."user.title";
}
elseif ( $ord == 'last_hist' ) {
	$ord2 = 'last_history';
}
elseif ( $ord == 'last_history' ) {
	$ord2 = 'last_history';
}
else {
	$ord2 = $sqlname."dogovor.".$ord;
}

//поля, которые являются суммами
$sum_array = [
	'kol',
	'kol_fact',
	'co_kol',
	'marga'
];

//Загрузка настроек колонок для текущего пользователя
$f = $rootpath.'/cash/dogs_columns_'.$iduser1.'.txt';

if ( file_exists( $f ) ) {
	$file = $f;
}
else {
	$file = $rootpath.'/cash/columns_default_deal.json';
}

$cols = json_decode( str_replace( "px", "", file_get_contents( $file ) ), true );

/*
 * Поля, включенные в выборку
 */
$inputs = [];
foreach ( $cols as $key => $value ) {

	if ( strpos( $key, 'input' ) !== false && $value['on'] == 'yes' ) {
		$inputs[]  = $key;
		$inputss[] = "'".$key."'";
	}

}

$result    = $db -> getCol( $queryArray['queryCount'] );
$all_lines = count( $result );

if ( $page > ceil( $all_lines / $lines_per_page ) ) {
	$page = 1;
}

$page = (empty( $page ) || $page <= 0) ? 1 : $page;

$page_for_query = $page - 1;
$lpos           = $page_for_query * $lines_per_page;
$count_pages    = ceil( $all_lines / $lines_per_page );
if ( $count_pages < 1 ) {
	$count_pages = 1;
}

$dname  = [];
$result = $db -> query( "SELECT fld_title, fld_name FROM ".$sqlname."field WHERE fld_tip='dogovor' AND fld_on='yes' and identity = '$identity' ORDER BY fld_order" );
while ($data = $db -> fetch( $result )) {
	$dname[ $data['fld_name'] ] = $data['fld_title'];
}

$dname['mcid']    = 'Компания';
$dname['dcreate'] = 'Создана';
$dname['dplan']   = $dname['datum_plan'];
$dname['title']   = 'Название';
$dname['client']  = 'Клиент';
$dname['user']    = $dname['iduser'];
$dname['history'] = '';
$dname['status']  = '';

if ( $tar == 'close' ) {

	$dname['dplan'] = 'Закрыта';
	$dname['kol']   = $dname['kol_fact'];

}

//направление сортировки
$des = ($tuda == 'desc') ? 'up' : 'down';

//заголовки колонок, настройки ширины и порядок вывода колонок
foreach ( $cols as $key => $value ) {

	$order   = '';
	$desc    = '';
	$origkey = $key;

	if ( $origkey == 'idcategory' ) {
		$key = 'status';
	}
	if ( $origkey == 'iduser' ) {
		$key = 'user';
	}
	if ( $origkey == 'datum' ) {
		$key = 'dcreate';
	}
	if ( $origkey == 'datum_plan' ) {
		$key = 'dplan';
	}
	if ( $origkey == 'last_history' ) {
		$key = 'history';
	}

	if ( $xord == $origkey ) {
		$order = 'yes';
		$desc  = $des;
	}

	if ( in_array( $key, [
		'direction',
		'adres',
		'tip',
		'history',
		'adres',
		'last_history_descr'
	] ) ) {
		$class = 'hidden-netbook1 hidden-ipad';
	}
	elseif ( in_array( $key, [
		'datum1',
		'dplan1',
		'client',
		'credit'
	] ) ) {
		$class = 'hidden-ipad';
	}
	elseif ( $key == 'iduser' ) {
		$class = 'ipad-user';
	}
	elseif ( stripos( $key, 'input' ) !== false ) {
		$class = 'hidden-ipad';
	}
	elseif ( $key == '' ) {
		$class = 'hidden-ipad';
	}
	else {
		$class = '';
	}

	if ( $value['on'] == 'yes' ) {

		if ( $dname[ $key ] == '' ) {
			$dname[ $key ] = $value['name'];
		}

		$header[ $key ] = [
			"id"    => $key,
			"title" => $dname[ $key ],
			"width" => ($key != 'title') ? toWidth( $value['width'] ) : '',
			"sort"  => (in_array( $key, [
				'credit',
				'last_history_descr'
			] )) ? "" : $origkey,
			"order" => $order,
			"desc"  => $desc,
			"clas"  => $class
		];

	}

}

$dostup_array = $db -> getCol( "SELECT did FROM ".$sqlname."dostup WHERE iduser = '$iduser1' and identity = '$identity'" );

$deal = $rows = [];

//print
$q = $queryArray['query']." ORDER BY $ord2 $tuda LIMIT $lpos,$lines_per_page";

//file_put_contents($rootpath."/cash/deals.sql", $q);

$rest = $db -> query( $q );
while ($da = $db -> fetch( $rest )) {

	$dplan        = '';
	$dclose       = '';
	$sup          = '';
	$icon         = '';
	$icolor       = '';
	$history      = '';
	$historyDate  = '';
	$status       = '';
	$task         = '';
	$isaccess     = '';
	$marga        = 0;
	$kol          = 0;
	$lastHistTip  = '';
	$lastHistDesc = '';
	$credit       = '';
	$statusColor  = '';

	$isaccess = get_accesse( 0, 0, (int)$da['did'] );

	if ( $isadmin == 'on' ) {
		$isaccess = 'yes';
	}

	$marga = $da['marga'];

	$dplan = format_date_rus( $da['dplan'] );

	if ( $da['close'] == 'yes' ) {

		$dclose = format_date_rus( $da['dclose'] );

		//цвет закрытия сделки
		$statusColor = ($da['kolf'] > 0) ? 'green' : 'red';

		//статус закрытия сделки
		$status = '<span class="'.$statusColor.'"><i class="icon-info-circled-1 '.$statusColor.'"></i>&nbsp;'.$da['dstatus'].'</span>';

		$kol = $da['kolf'];

		$icolor = $statusColor;

	}
	if ( $da['close'] != 'yes' ) {

		$kol = $da['kol'];

		$delta = datestoday( $da['dplan'] );

		//цвет иконки сделки
		if ( $delta == 0 ) {
			$icolor = 'green';
		}
		elseif ( $delta < 0 ) {
			$icolor = 'red';
		}
		else {
			$icolor = 'blue';
		}

	}

	if ( $cols['last_history']['on'] == 'yes' || $cols['last_history_descr']['on'] == 'yes' ) {

		$re = $db -> getRow( "SELECT datum, tip, substring(des, 1, 100) as des FROM ".$sqlname."history WHERE did = '".$da['did']."' and tip NOT IN ('СобытиеCRM','ЛогCRM') and identity = '$identity' ORDER BY datum DESC LIMIT 1" );

		if ( $cols['last_history_descr']['on'] == 'yes' ) {
			$lastHistDesc = strip_tags( $re['des'] );
		}

		//if ($cols['last_history']['on'] == 'yes') {

		$hist = $re['datum'];
		//if ($showHistTip == 'yes')
		$lastHistTip = $re['tip'];

		if ( $hist != '' ) {
			$history     = strip_tags( diffDateTime( $hist ) ).' назад';
			$historyDate = diffDateTime( $hist );
		}

		//}

	}

	//Проверим налиие напоминаний
	$count = $db -> getOne( "SELECT COUNT(tid) as count FROM ".$sqlname."tasks WHERE did = '".$da['did']."' and active = 'yes' and identity = '$identity'" );

	if ( $isaccess == 'yes' && $count > 0 ) {
		$task = 'yes';
	}

	if ( in_array( $da['did'], $dostup_array, true ) ) {

		$sup      = '<i class="icon-lock-open green smalltxt sup" title="Вам предоставлен доступ"></i>';
		$isaccess = 'yes';

	}

	//иконка сделки
	$icon = (!in_array( $da['tip'], $servicesTips, true )) ? 'icon-briefcase' : 'icon-arrows-cw';

	$frozenDate = NULL;

	// для замороженных сделок
	if ( $da['stepid'] == $stepInHold['step'] ) {
		$icon   = 'icon-snowflake-o bluemint';
		$icolor = 'bluemint';
	}
	if ( $da['isFrozen'] == 1 ) {
		$icon       = 'icon-snowflake-o bluemint';
		$icolor     = 'bluemint';
		$frozenDate = $da[ $otherSettings['dateFieldForFreeze'] ] != '' ? modifyDatetime( $da[ $otherSettings['dateFieldForFreeze'] ], ["format" => 'd.m.y'] ) : NULL;
	}

	//цвет этапа
	if ( is_between( (int)$da['step'], 0, 20 ) ) {
		$pcolor = ' progress-gray';
	}
	elseif ( is_between( (int)$da['step'], 20, 60 ) ) {
		$pcolor = ' progress-green';
	}
	elseif ( is_between( (int)$da['step'], 60, 90 ) ) {
		$pcolor = ' progress-blue';
	}
	elseif ( (int)$da['step'] >= 90 ) {
		$pcolor = ' progress-red';
	}

	if ( $isadmin != 'yes' && $isaccess != 'yes' && !$userRights['showhistory'] ) {

		$kol          = '<span class="gray">--hidden--</span>';
		$marga        = '<span class="gray">--hidden--</span>';
		$da['client'] = '';
		$da['person'] = '';

	}

	$md      = $db -> getOne( "SELECT MAX(datum) as datum FROM ".$sqlname."steplog WHERE did='".$da['did']."' and identity = '$identity'" );
	$stepDay = abs( round( diffDate2( $md ) ) );

	//счета и оплаты по сделкам
	if ( $cols['credit']['on'] == 'yes' && $isaccess == 'yes' ) {

		$totalCredit   = 0;
		$doCredit      = 0;
		$doCreditSumma = 0;

		$re = $db -> getAll( "SELECT `do`, `summa_credit` FROM ".$sqlname."credit WHERE did = '".$da['did']."' and identity = '$identity'" );
		foreach ( $re as $data ) {

			$totalCredit++;
			if ( $data['do'] == 'on' ) {
				$doCreditSumma += $data['summa_credit'];
				$doCredit++;
			}

		}

		if ( $doCreditSumma < $kol ) {
			$clr = " red";
			$ttl = ". Не полная оплата по сделке";
		}
		else {
			$clr = " blue";
			$ttl = ". Полная оплата";
		}

		$credit = ($totalCredit > 0) ? '<span class="block Bold'.$clr.'" title="Сумма оплат'.$ttl.'">'.num_format( $doCreditSumma ).'</span><span class="block" title="Количество оплаченных/выставленных счетов"><i><b class="blue">'.$doCredit.'</b> из '.$totalCredit.'</i></span>' : '';

	}

	//формируем массив данных
	$row = [
		"did"         => $da['did'],
		//"title"        => $da['title'],
		"icon"        => $icon,
		"icolor"      => $icolor,
		"frozenDate"  => $frozenDate,
		//"step"         => $da['step'],
		//"steptitle"    => $da['steptitle'],
		//"pcolor"       => $pcolor,
		"status"      => $status,
		"statusColor" => $statusColor,
		"direction"   => $da['direction'],
		"tip"         => $da['tips'],
		"dcreate"     => (format_date_rus( $da['dcreate'] ) != '') ? format_date_rus( $da['dcreate'] ) : "--",
		//"dplan"        => $dplan,
		//"dclose"       => $dclose,
		"kol"         => num_format( (float)$kol ),
		"marg"        => num_format( (float)$marga ),
		"adres"       => $da['adres'],
		"mcid"        => $da['mcid'],
		//"clid"         => $da['clid'] + 0,
		//"client"       => current_client($da['clid']),
		//"pid"          => $da['pid'] + 0,
		//"person"       => current_person($da['pid']),
		"iduser"      => $da['iduser'],
		"user"        => current_user( $da['iduser'] ),
		"change"      => $isaccess == 'yes' ? true : NULL,
		//"history"      => $history,
		//"hday"      => $historyDate,
		"dostup"      => $sup,
		//"task"         => $task,
		//"stepday"      => $stepDay,
		//"lastHistTip"  => $lastHistTip,
		//"lastHistDesc" => $lastHistDesc,
		//"credit"       => ($credit != '') ? $credit : '<span class="gray">Нет</span>',
		//"creditDate"   => $da['creditDate']
	];

	//добавляем в массив прочие поля *input*, включенные в выборку
	foreach ( $inputs as $key => $value ) {
		$row[ $value ] = $da[ $value ];
	}

	$columns = [];
	foreach ( array_keys( $header ) as $key ) {

		switch ($key) {

			case "dplan":

				$columns[] = [
					"isDplan" => 1,
					"title"   => $dplan,
					"class"   => $icolor,
					"comment" => $dclose,
					"clas"    => $header[ $key ]['clas']
				];

			break;
			case "title":

				$columns[] = [
					"isTitle" => 1,
					"id"      => $da['did'],
					"title"   => $da['title'],
					"task"    => ($task != '') ? $task : NULL,
					"sub"     => (array_key_exists( "client", $header )) ? NULL : [
						"client" => (array_key_exists( "client", $header )) ? NULL : [
							"clid"   => $da['clid'],
							"client" => current_client( $da['clid'] )
						],
						"person" => ($da['clid'] == 0 && array_key_exists( "client", $header )) ? NULL : [
							"pid"    => $da['pid'],
							"person" => current_person( $da['pid'] )
						],
						"adres"  => $da['adres']
					],
					"status"  => $status,
					"clas"    => $header[ $key ]['clas']
				];

			break;
			case "client":

				$columns[] = [
					"isClient" => 1,
					"isperson" => ($da['pid'] > 0 && $da['clid'] < 1) ? 1 : NULL,
					"id"       => ($da['pid'] > 0) ? $da['pid'] : $da['clid'],
					"title"    => ($da['pid'] > 0) ? current_person( $da['pid'] ) : current_client( $da['clid'] ),
					"clas"     => $header[ $key ]['clas']
				];

			break;
			case "status":

				$columns[] = [
					"isStep"  => 1,
					"title"   => $da['steptitle'],
					"value"   => $da['step'],
					"day"     => $stepDay,
					"bgcolor" => $pcolor,
					"clas"    => $header[ $key ]['clas']
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
			case "last_history":

				$columns[] = [
					"isHistory" => 1,
					"title"     => $lastHistTip,
					"comment"   => $lastHistDesc,
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
			case "credit":

				$columns[] = [
					"isCredit" => 1,
					"title"    => ($credit != '') ? $credit : '<span class="gray">Нет</span>',
					"clas"     => $header[ $key ]['clas']
				];

			break;
			default:

				$columns[] = [
					"isString" => 1,
					"title"    => $row[ $key ],
					"clas"     => $header[ $key ]['clas']
				];

			break;

		}

	}

	$row['columns'] = $columns;

	$deal[] = $row;

}

if ( $_REQUEST['static'] != 'no' ) {

	//расчет статистики
	$dealKol   = 0;
	$dealMarga = 0;

	/*
	$rest = $db -> query( $queryArray[ 'queryAllCounts' ] );
	while ( $da = $db -> fetch( $rest ) ) {

		$dealKol   += $da[ 'kol' ];
		$dealMarga += $da[ 'marga' ];

	}
	*/

	$rest      = $db -> getRow( $queryArray['queryAllCounts'] );
	$dealKol   = $rest['kol'];
	$dealMarga = $rest['marga'];

}

$deals = [
	"header"    => $header,
	"head"      => array_values( $header ),
	"deal"      => $deal,
	"page"      => (int)$page,
	"pageall"   => (int)$count_pages,
	"count"     => (int)$all_lines,
	"dealKol"   => num_format( $dealKol ),
	"dealMarga" => num_format( $dealMarga )
];

print json_encode_cyr( $deals );

//file_put_contents($rootpath."/cash/deals.json", json_encode_cyr( $deals ));