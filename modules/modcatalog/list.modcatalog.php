<?php
/* ============================ */
/*         SalesMan CRM         */
/* ============================ */
/* (C) 2016 Vladislav Andreev   */
/*       SalesMan Project       */
/*        www.isaler.ru         */
/*        ver. 2016.20          */
/* ============================ */

use Salesman\Price;
use Salesman\Storage;

error_reporting( E_ERROR );
header( "Pragma: no-cache" );

ini_set('display_errors', 1);

$rootpath = dirname(__DIR__, 2);

require_once $rootpath."/inc/config.php";
require_once $rootpath."/inc/dbconnector.php";
require_once $rootpath."/inc/auth.php";
require_once $rootpath."/inc/func.php";
require_once $rootpath."/inc/settings.php";
require_once $rootpath."/inc/language/".$language.".php";

$thisfile = basename( __FILE__ );

//require_once "mcfunc.php";

//print_r($_REQUEST);

$page       = $_REQUEST['page'];
$idcategory = (int)$_REQUEST['idcat'];
$wordc      = str_replace( " ", "%", $_REQUEST['wordc'] );
$wordr      = str_replace( " ", "%", $_REQUEST['wordr'] );
$wordo      = str_replace( " ", "%", $_REQUEST['wordo'] );
$words      = str_replace( " ", "%", $_REQUEST['words'] );
$tar        = $_REQUEST['tar'];
$who        = $_REQUEST['who'];
$statuss    = (string)$_REQUEST['status'];
$statusz    = (array)$_REQUEST['zstatus'];
$sklad      = (array)$_REQUEST['sklad'];
$tip        = (array)$_REQUEST['tip'];
$iduser     = (int)$_REQUEST['iduser'];
$ziduser    = (int)$_REQUEST['ziduser'];
$tuda       = $_REQUEST['tuda'];
$ord        = $_REQUEST['ord'];

//настройки модуля
$settings            = $db -> getOne( "SELECT settings FROM {$sqlname}modcatalog_set WHERE identity = '$identity'" );
$settings            = json_decode( (string)$settings, true );
$settings['mcSklad'] = 'yes';

if ( $settings['mcSkladPoz'] == "yes" ) {
	$pozzi = " and status != 'out'";
}

$dname  = $dvar = $don = [];
$fields = [];
$result = $db -> getAll( "SELECT * FROM {$sqlname}field WHERE fld_tip='price' AND fld_on='yes' and identity = '$identity' ORDER BY fld_order" );
foreach ( $result as $data ) {

	$dname[ $data['fld_name'] ] = $data['fld_title'];
	$dvar[ $data['fld_name'] ]  = $data['fld_var'];
	$don[]                      = $data['fld_name'];

	if($data['fld_name'] != 'price_in' && $data['fld_on'] == 'yes') {

		$fields[] = [
			"field" => $data['fld_name'],
			"title" => $data['fld_title'],
			"value" => $data['fld_var'],
		];

	}

}

$skladlist = Storage ::getSkladList();

$sort           = '';
$lines_per_page = 50; //Стоимость записей на страницу

if ( $tar == 'catalog' ) {

	$status = [
		'0' => 'Продан',
		'1' => 'Под заказ',
		'2' => 'Ожидается',
		'3' => 'В наличии',
		'4' => 'Резерв'
	];
	$colors = [
		'0' => 'gray',
		'1' => 'broun',
		'2' => 'blue',
		'3' => 'green',
		'4' => 'red'
	];

	if ( $idcategory > 0 ) {

		$listcat = [];
		$catalog = Price::getPriceCatalog( $idcategory );
		foreach ( $catalog as $value ) {
			$listcat[] = $value['id'];
		}

		$ss = (!empty( $listcat )) ? " or prc.pr_cat IN (".implode( ",", $listcat ).")" : '';

		$sort .= " and (prc.pr_cat = '$idcategory' $ss)";

		//$sort.= " and (pr_cat='".$idcategory."' or pr_cat IN (SELECT idcategory FROM {$sqlname}price_cat WHERE sub='$idcategory' and identity = '$identity'))";

	}
	elseif ( !empty( $settings['mcPriceCat'] ) ){
		$sort .= " and prc.pr_cat IN (".yimplode( ",", (array)$settings['mcPriceCat'] ).")";
	}

	if ( $wordc != '' ) {
		$sort .= " and (prc.artikul LIKE '%".$wordc."%' OR prc.title LIKE '%".$wordc."%' OR prc.descr LIKE '%$wordc%')";
	}

	if ( $statuss != '' ) {
		$sort .= " and prc.n_id IN (SELECT prid FROM {$sqlname}modcatalog where status= '$statuss' and identity = '$identity')";
	}

	if ( !empty( $sklad ) ) {
		$sort .= " and prc.n_id IN (SELECT prid FROM {$sqlname}modcatalog_skladpoz where sklad IN (".implode(",", $sklad).") and identity = '$identity')";
	}

	$qfields = [];
	foreach ($fields as $field ) {
		$qfields[] = "prc.".$field['field']." as ".$field['field'];
	}

	//print
	$query = "
	SELECT
		prc.n_id as id,
		prc.datum as datum,
		prc.pr_cat as idcat,
		prc.title as title,
		SUBSTRING(prc.descr, 1, 100) as content,
		prc.artikul as artikul,
		prc.edizm as edizm,
		prc.price_in as price_in,
		".yimplode(",", $qfields).",
		prc.archive as archive,
		{$sqlname}price_cat.title as category
	FROM {$sqlname}price `prc`
		LEFT JOIN {$sqlname}price_cat ON prc.pr_cat = {$sqlname}price_cat.idcategory
	WHERE
		prc.n_id > 0
		$sort and
		prc.identity = '$identity'
	";

	$result    = $db -> query( $query );
	$all_lines = $db -> numRows( $result );

	$page = ( empty( $page ) || $page <= 0 ) ? 1 : (INT)$page;

	$page_for_query = $page - 1;
	$lpos           = $page_for_query * $lines_per_page;

	$count_pages = ceil( $all_lines / $lines_per_page );
	if ( $count_pages < 1 ) {
		$count_pages = 1;
	}

	$query .= " ORDER BY prc.$ord $tuda LIMIT $lpos,$lines_per_page";

	$result = $db -> getAll( $query );
	foreach ( $result as $da ) {

		$cat   = '';
		$kol   = 0;
		$rez   = '';
		$files = '';

		$dat   = explode( " ", $da['datum'] );
		$ddate = format_date_rus( $dat['0'] )." ".$dat['1'];

		$cat = $db -> getOne( "select title from {$sqlname}price_cat where idcategory = '".$da['idcat']."' and identity = '$identity'" );

		$res = $db -> getRow( "select * from {$sqlname}modcatalog where prid='".$da['id']."' and identity = '$identity'" );
		$files = (string)$res["files"];

		$s = (!empty( $sklad )) ? " and sklad IN (".implode( ",", $sklad ).")" : "";

		//расчет количества на складе
		$kol = $db -> getOne( "select SUM(kol) as kol from {$sqlname}modcatalog_skladpoz where status != 'out' and prid = '".$da['id']."' $pozzi $s and identity = '$identity'" ) + 0;

		//print $db -> lastQuery();

		if ( !$state ) {
			$state = 0;
		}
		//if ($kol == '') $kol = "--";

		$kol_res = $db -> getOne( "select SUM(kol) as kol from {$sqlname}modcatalog_reserv where prid='".$da['id']."' and identity = '$identity'" ) + 0;

		if ( $kol_res == 0 ) {
			$rez .= '<b class="gray">'.num_format( $kol_res ).'</b>';
		}
		else {
			$rez .= '<a a href="javascript:void(0)" onclick="doLoad(\'modules/modcatalog/form.modcatalog.php?action=viewzrezerv&prid='.$da['id'].'\')" title="Зарезервировано под сделки"><b class="green">'.num_format($kol_res).'</b></a>';
		}

		$kol_zay = (float)$db -> getOne( "select SUM(kol) as count from {$sqlname}modcatalog_zayavkapoz where prid='".$da['id']."' and idz NOT IN (select idz from {$sqlname}modcatalog_zayavka where status IN (2, 3) and identity = '$identity') and identity = '$identity'" );

		if ( $kol_zay > 0 ) {

			if ( $kol_res > 0 ) {
				$rez .= " / ";
			}

			$rez .= '<a a href="javascript:void(0)" onclick="doLoad(\'modules/modcatalog/form.modcatalog.php?action=viewzayavkapoz&prid='.$da['id'].'\')" title="В заявках под сделки"><b class="red">'.$kol_zay.'</b></a>';

		}

		$files = json_decode( $files, true );

		if ( $files[0]['file'] != '' ) {
			$image = 'style="background: url(\'/content/helpers/get.file.php?file=modcatalog/'.$files[0]['file'].'\') top no-repeat; background-size:cover;" onclick="window.open(\'content/helpers/get.file.php?file=modcatalog/'.$files[0]['file'].'\')" title="Просмотр" class="zoom"';
		}
		else {
			$image = 'style="background: url(\'/modules/modcatalog/images/noimage.png\') top no-repeat; background-size:cover;"';
		}

		$list[] = [
			"id"      => $da['id'],
			"artikul" => $da['artikul'],
			"cat"     => $cat,
			"title"   => $da['title'],
			"datum"   => $da['datum'],
			"status"  => strtr( $state, $status ),
			"color"   => strtr( $state, $colors ),
			"price1"  => num_format( $da['price_1'] ),
			"edizm"   => $da['edizm'],
			"kol"     => num_format( $kol ),
			"res"     => $rez,
			"content" => $da['content'],
			"image"   => $image
		];

	}

	$lists = [
		"list"          => $list,
		"page"          => $page,
		"pageall"       => $count_pages,
		"mcArtikul"     => ( $settings['mcArtikul'] == 'yes' ) ? '1' : '',
		"price_1"       => ( in_array( 'price_1', $don ) ) ? $dname['price_1'] : '',
		"mcKolEdit"     => ( $settings['mcKolEdit'] == 'yes' and in_array( $iduser1, $settings['mcCoordinator'] ) ) ? '1' : '',
		"mcCoordinator" => ( in_array( $iduser1, $settings['mcCoordinator'] ) ) ? '1' : '',
		"valuta"        => $valuta
	];

	print json_encode_cyr( $lists );

	exit();

}

if ( $tar == 'zayavka' ) {

	$status = [
		'0' => 'Создана',
		'1' => 'В работе',
		'2' => 'Выполнена',
		'3' => 'Отменена'
	];
	$colors = [
		'0' => 'broun',
		'1' => 'blue',
		'2' => 'green',
		'3' => 'Отменена'
	];
	$bgcolor = [
		'bgwhite',
		'bluebg-sub',
		'greenbg-sub',
		'orangebg-sub',
		'redbg-sub'
	];

	if ( !empty( $statusz ) ) {
		$sort .= " and status IN (".yimplode(",", $statusz, "'").") ";
	}

	if ( (int)$ziduser > 0 ) {
		$sort .= "and iduser = '$ziduser'";
	}

	if ( in_array( $iduser1, (array)$settings['mcSpecialist'] ) ) {
		$sort .= "and iduser = '$iduser1'";
	}

	$query = "SELECT * FROM {$sqlname}modcatalog_zayavka where id > 0 $sort and identity = '$identity'";

	$result = $db -> query( $query );

	$all_lines = $db -> numRows( $result );

	if ( empty( $page ) || $page <= 0 ) {
		$page = 1;
	}
	else {
		$page = (int)$page;
	}
	$page_for_query = $page - 1;
	$lpos           = $page_for_query * $lines_per_page;

	$count_pages = ceil( $all_lines / $lines_per_page );
	if ( $count_pages < 1 ) {
		$count_pages = 1;
	}

	if ( $ord == 'status' ) {
		$ordd = "FIELD(`status`, 0,1,2,3)";
	}
	elseif ( $ord == 'number' ) {
		$ordd = "CAST(number AS UNSIGNED)";
	}
	else {
		$ordd = $ord;
	}

	$query .= " ORDER BY $ordd $tuda LIMIT $lpos,$lines_per_page";

	$result = $db -> getAll( $query );
	foreach ( $result as $da ) {

		$de         = '';
		$dess       = '';
		$change     = '';
		$Complete   = 0;
		$toorder    = '';
		$isorder    = '';
		$orders     = '';
		$persent    = 0;
		$countOrder = 0;
		$colr       = '';
		$isDo       = '';
		$tip        = '<i class="icon-archive blue" title="По каталогу"></i>';

		$kol_zay = $db -> getOne( "select COUNT(*) as kol from {$sqlname}modcatalog_zayavkapoz where idz='".$da['id']."' and identity = '$identity'" );

		$zayavka = json_decode( $da['des'], true );

		if ( $zayavka['zTitle'] != '' ) {
			$tip = '<i class="icon-search red" title="Поиск новой позиции"></i>';
		}

		if ( $da['datum_start'] != '0000-00-00 00:00:00' && $da['status'] > 0 ) {
			$dess .= "Принята в работу - ".get_sfdate($da['datum_start']);
		}

		if ( $da['datum_end'] != '0000-00-00 00:00:00' ) {
			$dess .= ", Выполнена - ".get_sfdate( $da['datum_end'] );
			$de   = '<br><b class="red">'.format_date_rus( get_smdate( $da['datum_end'] ) ).'</b>';
		}

		if ( $dess == '' ) {
			$dess = 'Создана';
		}

		$des = $da['content'];

		//if(abs(diffDate2($data['datum'])) <= 1 and $data['datum_start'] == '0000-00-00 00:00:00') $bg = '#FFFF99';

		if ( $da['isHight'] == 'yes' && $da['datum_start'] == '0000-00-00 00:00:00' ) {
			$bg = '#FFFFE1';
		}
		else {
			$bg = strtr($da['status'], $bgcolor);
		}

		if ( $da['isHight'] == 'yes' && $da['status'] != 2 ) {
			$hi = '<i class="icon-attention red smalltxt" title="Срочно"></i>';
		}
		else {
			$hi = '';
		}

		if ( (int)$da['status'] != 2 && (int)$da['status'] != 1 && ((int)$da['iduser'] == $iduser1 || in_array( $iduser1, (array)$settings['mcCoordinator'] )) ) {
			$change = '1';
		}

		if ( $da['sotrudnik'] == 0 ) {
			$da['sotrudnik'] = '';
		}

		$des = json_decode( $da['des'], true );

		if ( $da['did'] == 0 && $kol_zay > 0 && $des['zTitle'] == '' ) {
			$des['zTitle'] = 'Заявка на склад';
		}

		if ( in_array( $da['status'], [
				0,
				1,
				2
			] ) && ($iduser == $da['iduser'] || in_array( $iduser1, (array)$settings['mcCoordinator'] )) ) {
			$editor = 1;
		}
		else {
			$editor = '';
		}

		if ( $da['did'] > 0 ) {

			$Compl    = (new Storage()) -> mcCompleteStatus( $da['did'] );
			$Complete = ($Compl['kolAll'] > 0) ? round( ($Compl['kolResAll'] + $Compl['kolZakAll']) / $Compl['kolAll'] * 100, 2 ) : 0;

		}

		//посчитаем количество позиций в ордерах, перекрытых по этой заявке
		//$countOrder = $db -> getOne("SELECT SUM(kol) as kol FROM {$sqlname}modcatalog_aktpoz WHERE ida IN (SELECT id FROM {$sqlname}modcatalog_akt WHERE idz = '".$da['id']."' and identity = '$identity') and identity = '$identity'");

		$countOrder = $db -> getOne( "SELECT SUM(kol) as kol FROM {$sqlname}modcatalog_aktpoz WHERE ida IN (SELECT id FROM {$sqlname}modcatalog_akt WHERE idz = '".$da['id']."' and idz > 0 and identity = '$identity') and identity = '$identity'" ) + 0;

		//print $da['number']." :: ".$countOrder."\n";

		//посчитаем количество позиций в заявке
		$countZayavka = $db -> getOne( "SELECT SUM(kol) as kol FROM {$sqlname}modcatalog_zayavkapoz WHERE idz = '".$da['id']."' and identity = '$identity'" );

		if ( (int)$da['status'] == 2 && in_array( $iduser1, (array)$settings['mcCoordinator'] ) ) {

			if ( $countOrder < $countZayavka ) {
				$toorder = '1';
			}
			if ( $countOrder > 0 ) {
				$isorder = '1';
			}

			$change = '1';

		}

		if ( in_array( (int)$da['status'], [
			2,
			3
		] ) ) {
			$isDo = 1;
		}

		$persent = ($countZayavka > 0) ? round( $countOrder / $countZayavka * 100, 1 ) : 0;

		$order = [];
		$r     = $db -> getCol( "SELECT number FROM {$sqlname}modcatalog_akt WHERE idz = '".$da['id']."' and identity = '$identity'" );
		foreach ( $r as $v ) {
			$order[] .= ( $v > 0 ) ? $v : '<span title="Не проведен">!б/н</span>';
		}

		$orders = yimplode( ", ", $order );

		if ( (int)$da['status'] == 2 && $persent < 100 ) {

			$bg   = 'rgba(255,152,150,0.3) !important';
			$colr = "red";

		}
		if ( (int)$da['status'] == 3 ) {

			$bg   = '#ddd !important';
			$colr = "gray2";

		}

		//$Complete = array2string($Compl);

		$list[] = [
			"id"        => $da['id'],
			"number"    => $da['number'],
			"datum"     => str_replace( ",", "<br>", get_sfdate( $da['datum'] ) ),
			"priority"  => str_replace( ",", "<br>", get_date( $da['datum_priority'] ) ),
			"tip"       => $tip,
			"de"        => $de,
			"bg"        => $bg,
			"color"     => strtr( $da['status'], $colors ),
			"status"    => strtr( $da['status'], $status ),
			"iduser"    => $da['iduser'],
			"user"      => current_user( $da['iduser'] ),
			"sotid"     => $da['sotrudnik'],
			"sotrudnik" => current_user( $da['sotrudnik'] ),
			"did"       => $da['did'],
			"deal"      => current_dogovor( $da['did'] ),
			"change"    => $change,
			"hi"        => $hi,
			"content"   => mb_substr( clean( $da['content'] ), 0, 101, 'utf-8' ),
			"zTitle"    => $des['zTitle'],
			"editor"    => $editor,
			"Complete"  => $Complete,
			"dess"      => $dess,
			"toorder"   => $toorder,
			"isorder"   => $isorder,
			"isDo"      => $isDo,
			"orderNum"  => $orders,
			"persent"   => $persent,
			"colr"      => $colr,
			"kols"      => $countOrder." / ".$countZayavka
		];

	}

	if ( in_array( $iduser1, $settings['mcCoordinator'] ) ) {
		$mcCoordinator = '1';
	}

	else {
		$mcCoordinator = '';
	}

	$lists = [
		"list"          => $list,
		"page"          => $page,
		"pageall"       => $count_pages,
		"mcCoordinator" => $mcCoordinator,
		"valuta"        => $valuta
	];

	//print $lists;
	//print $query."\n";
	//print_r($lists);

	print json_encode_cyr( $lists );

	exit();

}

if ( $tar == 'poz' ) {

	$colors  = [
		'0' => 'gray',
		'1' => 'blue',
		'2' => 'green'
	];
	$statusa = [
		'0' => 'На рассмотрении',
		'1' => 'В работе',
		'2' => 'Выполнена'
	];

	if ( !empty( $statusz ) ) {
		$sort .= " and status IN (".yimplode(",", $statusz, "'").")";
	}

	if ( $iduser > 0 ) {
		$sort .= "and iduser = '$iduser'";
	}

	$query = "
	SELECT * 
	FROM {$sqlname}modcatalog_zayavkapoz 
	WHERE 
		id > 0 AND 
		idz IN (SELECT id FROM {$sqlname}modcatalog_zayavka where id > 0 $sort and identity = '$identity') AND 
		identity = '$identity' 
	ORDER BY id DESC";

	$result    = $db -> query( $query );
	$all_lines = $db -> numRows( $result );

	if ( empty( $page ) || $page <= 0 ) {
		$page = 1;
	}
	else {
		$page = (int)$page;
	}
	$page_for_query = $page - 1;
	$lpos           = $page_for_query * $lines_per_page;

	$count_pages = ceil( $all_lines / $lines_per_page );
	if ( $count_pages < 1 ) {
		$count_pages = 1;
	}

	$query .= " LIMIT $lpos,$lines_per_page";

	$result = $db -> getAll( $query );
	foreach ( $result as $da ) {

		$did   = '';
		$title = '';

		$res     = $db -> getRow( "select * from {$sqlname}modcatalog_zayavka where id='".$da['idz']."' and identity = '$identity'" );
		$datum   = $res['datum'];
		$statuss = $res['status'];
		$did     = (int)$res['did'];

		$title = $db -> getOne( "select title from {$sqlname}price where n_id = '".$da['prid']."' and identity = '$identity'" );

		$list[] = [
			"id"     => (int)$da['idz'],
			"prid"   => (int)$da['prid'],
			"datum"  => str_replace( ",", "<br>", get_sfdate( $datum ) ),
			"color"  => strtr( $statuss, $colors ),
			"status" => strtr( $statuss, $statusa ),
			"title"  => $title,
			"kol"    => $da['kol'],
			"did"    => $did,
			"deal"   => current_dogovor( $did )
		];

	}

	$lists = [
		"list"    => $list,
		"page"    => $page,
		"pageall" => $count_pages
	];

	print json_encode_cyr( $lists );

	exit();
}

if ( $tar == 'rez' ) {

	if ( $iduser > 0 ) {
		$sort .= " and iduser = '".$iduser."'";
	}

	if ( $wordr != '' ) {
		$sort .= " and prid IN (SELECT n_id FROM {$sqlname}price where (artikul LIKE '%".$wordr."%' or title LIKE '%".$wordr."%' or descr LIKE '%".$wordr."%') and identity = '$identity')";
	}

	if ( !empty( $sklad ) ) {
		$sort .= " and sklad IN (".yimplode(",", $sklad).")";
	}

	$query = "SELECT * FROM {$sqlname}modcatalog_reserv where id > 0 ".$sort." and identity = '$identity' ORDER BY id DESC";

	$result    = $db -> query( $query );
	$all_lines = $db -> numRows( $result );

	if ( empty( $page ) || $page <= 0 ) {
		$page = 1;
	}
	else {
		$page = (int)$page;
	}
	$page_for_query = $page - 1;
	$lpos           = $page_for_query * $lines_per_page;

	$count_pages = ceil( $all_lines / $lines_per_page );
	if ( $count_pages < 1 ) {
		$count_pages = 1;
	}

	$query .= " LIMIT $lpos,$lines_per_page";

	$result = $db -> getAll( $query );
	foreach ( $result as $da ) {

		$did   = '';
		$title = $db -> getOne( "select title from {$sqlname}price where n_id='".$da['prid']."' and identity = '$identity'" );

		//вывод заявки, акта, склада, по которой стоит резерв
		$zayavkaNumber = $db -> getOne( "select number from {$sqlname}modcatalog_zayavka where id='".$da['idz']."' and identity = '$identity'" );

		$orderNumber = $db -> getOne( "select number from {$sqlname}modcatalog_akt where id='".$da['ida']."' and identity = '$identity'" );

		$sklad = $db -> getOne( "select title from {$sqlname}modcatalog_sklad where id='".$da['sklad']."' and identity = '$identity'" );

		$list[] = [
			"id"      => (int)$da['id'],
			"prid"    => (int)$da['prid'],
			"datum"   => str_replace( ",", "<br>", get_sfdate( $da['datum'] ) ),
			"title"   => $title,
			"kol"     => $da['kol'],
			"did"     => $da['did'],
			"deal"    => current_dogovor( $da['did'] ),
			"idz"     => (int)$da['idz'],
			"zayavka" => $zayavkaNumber,
			"ida"     => (int)$da['ida'],
			"order"   => $orderNumber,
			"sklad"   => $sklad,
		];

	}

	$mcCoordinator = ( in_array( $iduser1, (array)$settings['mcCoordinator'] ) ) ? '1' : '';

	$lists = [
		"list"          => $list,
		"page"          => $page,
		"pageall"       => $count_pages,
		"mcCoordinator" => $mcCoordinator
	];

	//print $query."\n";
	//print_r($lists);

	print json_encode_cyr( $lists );

	exit();
}

if ( $tar == 'offer' ) {

	$statuss    = (array)$_REQUEST['status'];

	$status  = [
		'0' => 'Актуально',
		'1' => 'Закрыто'
	];
	$colors  = [
		'0' => 'green',
		'1' => 'gray'
	];
	$bgcolor = [
		'#FFF',
		'#D7EBFF',
		'#D9FDDB',
		'#FFFF99',
		'#FFD9D9'
	];

	if ( !empty( $statuss ) ) {
		$sort .= " and status IN (".yimplode( ",", $statuss, "'" ).")";
	}
	if ( $iduser > 0 ) {
		$sort .= "and iduser = '".$iduser."'";
	}

	$query = "SELECT * FROM {$sqlname}modcatalog_offer where id > 0 ".$sort." and identity = '$identity' ORDER BY datum DESC, FIELD(`status`, 0,1,2)";

	$result    = $db -> query( $query );
	$all_lines = $db -> numRows( $result );

	if ( empty( $page ) || $page <= 0 ) {
		$page = 1;
	}
	else {
		$page = (int)$page;
	}
	$page_for_query = $page - 1;
	$lpos           = $page_for_query * $lines_per_page;

	$count_pages = ceil( $all_lines / $lines_per_page );
	if ( $count_pages < 1 ) {
		$count_pages = 1;
	}

	$query .= " LIMIT $lpos,$lines_per_page";

	$result = $db -> getAll( $query );
	foreach ( $result as $da ) {

		$zayavka = json_decode( (string)$da['des'], true );
		$des = $da['content'];

		if ( (int)$da['prid'] > 0 ) {

			$prtitle = $db -> getOne( "SELECT title FROM {$sqlname}price WHERE n_id = '".$da['prid']."' and identity = '$identity'" );

			$purl = '<br><span class="ellipsis"><a href="javascript:void(0)" onclick="doLoad(\'/modules/modcatalog/form.modcatalog.php?action=view&n_id='.$da['prid'].'\');"><i class="icon-archive broun"></i>'.$prtitle.'</a></span>';

		}
		else {
			$purl = '';
		}

		if ( abs( diffDate2( $da['datum'] ) ) < 1 ) {
			$bg = '#FFFFE1';
		}
		else {
			$bg = '';
		}

		$users = json_decode( (string)$da['users'], true );
		$likes = count( $users );
		if ( $likes > 0 ) {
			$likes = "+".$likes;
		}

		$like = $likes." ".getMorph2( $likes, [
				'голос',
				'голоса',
				'голосов'
			] );

		$list[] = [
			"id"      => $da['id'],
			"datum"   => str_replace( ",", "<br>", get_sfdate( $da['datum'] ) ),
			"bg"      => $bg,
			"color"   => strtr( $da['status'], $colors ),
			"status"  => strtr( $da['status'], $status ),
			"like"    => $like,
			"likes"   => $likes,
			"purl"    => $purl,
			"iduser"  => $da['iduser'],
			"user"    => current_user( $da['iduser'] ),
			"content" => mb_substr( clean( $des ), 0, 101, 'utf-8' ),
			"zTitle"  => $zayavka['zTitle']
		];

	}

	if ( ($data['status'] != '1' && $data['iduser'] == $iduser1) || in_array( $iduser1, $settings['mcCoordinator'] ) ) {
		$mcCoordinator = '1';
	}
	else {
		$mcCoordinator = '';
	}

	$lists = [
		"list"          => $list,
		"page"          => $page,
		"pageall"       => $count_pages,
		"mcCoordinator" => $mcCoordinator
	];

	//print $query."\n";
	//print_r($lists);

	print json_encode_cyr( $lists );

	exit();
}

if ( $tar == 'order' ) {

	if ( !empty( $tip ) ) {
		$sort = " and tip IN (".yimplode( ",", $tip, "'" ).")";
	}

	if ( !empty( $sklad ) ) {
		$sort .= " and sklad IN (".yimplode( ",", $sklad ).")";
	}

	$query = "SELECT * FROM {$sqlname}modcatalog_akt where id > 0 ".$sort." and identity = '$identity'";

	$result    = $db -> query( $query );
	$all_lines = $db -> numRows( $result );

	if ( empty( $page ) || $page <= 0 ) {
		$page = 1;
	}
	else {
		$page = (int)$page;
	}
	$page_for_query = $page - 1;
	$lpos           = $page_for_query * $lines_per_page;

	$count_pages = ceil( $all_lines / $lines_per_page );
	if ( $count_pages < 1 ) {
		$count_pages = 1;
	}

	$query .= " ORDER BY $ord $tuda LIMIT $lpos,$lines_per_page";

	$result = $db -> getAll( $query );
	foreach ( $result as $da ) {

		$numberZ = '';

		$number = '-';
		$status = '<span class="red">Черновик</span>';
		$isdo   = '';
		$tip    = '';

		if ( $da['tip'] == 'income' ) {
			$tip  = '<i class="icon-down-big green pull-left"></i> Приходный';
			$man1 = $da['man1'];
			$man2 = $da['man2'];
		}
		else {
			$tip  = '<i class="icon-up-big red pull-left"></i> Расходный';
			$man1 = $da['man2'];
			$man2 = $da['man1'];
		}

		if ( $da['isdo'] == 'yes' ) {
			$status = '<span class="green">Проведен</span>';
			$isdo   = '1';
		}
		if ( $da['number'] > 0 ) {
			$number = $da['number'];
		}

		if ( $da['idz'] > 0 ) {
			$numberZ = $db -> getOne("SELECT number FROM {$sqlname}modcatalog_zayavka WHERE id = '".$da['idz']."' and identity = '$identity'");
		}

		$skladpoz = ($settings['mcSkladPoz'] == 'yes') ? "1" : "";

		$list[] = [
			"id"         => $da['id'],
			"datum"      => str_replace( ",", "<br>", get_sfdate( $da['datum'] ) ),
			"number"     => $number,
			"idz"        => $da['idz'],
			"numberZ"    => $numberZ,
			"isdo"       => $isdo,
			"status"     => $status,
			"tip"        => $tip,
			"man1"       => $man1,
			"man2"       => $man2,
			"posid"      => $da['posid'],
			"contractor" => current_client( $da['posid'] ),
			"clid"       => $da['clid'],
			"client"     => current_client( $da['clid'] ),
			"did"        => $da['did'],
			"deal"       => current_dogovor( $da['did'] ),
			"skladpoz"   => $skladpoz
		];

	}

	if ( in_array( $iduser1, $settings['mcCoordinator'] ) ) {
		$mcCoordinator = '1';
	}
	else {
		$mcCoordinator = '';
	}

	$lists = [
		"list"          => $list,
		"page"          => $page,
		"pageall"       => $count_pages,
		"mcCoordinator" => $mcCoordinator
	];

	//print $query."\n";
	//print_r($lists);

	print json_encode_cyr( $lists );

	exit();
}

if ( $tar == 'sklad' ) {

	$sstatus = (array)$_REQUEST['sstatus'];

	if ( $ord == 'title' ) {
		$ordd = " ORDER BY {$sqlname}price.title";
	}
	else {
		$ordd = " ORDER BY {$sqlname}modcatalog_skladpoz.".$ord;
	}

	if ( $tuda == "desc" ) {
		$icn = '<i class="icon-angle-down"></i>';
	}
	else {
		$icn = '<i class="icon-angle-up"></i>';
	}

	if ( !empty( $sklad ) ) {
		$sort .= " and {$sqlname}modcatalog_skladpoz.sklad IN (".yimplode(",", $sklad).")";
	}
	if ( !empty( $sstatus ) ) {
		$sort .= " and {$sqlname}modcatalog_skladpoz.status IN (".yimplode(",", $sstatus, "'").")";
	}

	if ( $words != '' ) {
		$sort .= " and (({$sqlname}price.artikul LIKE '%".$words."%') or ({$sqlname}price.title LIKE '%".$words."%') or ({$sqlname}price.descr LIKE '%".$words."%') or ({$sqlname}modcatalog_skladpoz.serial LIKE '%".$words."%'))";
	}

	if ( !empty( $settings['mcPriceCat'] ) ) {
		$sort .= " and {$sqlname}price.pr_cat IN (".yimplode(",", (array)$settings['mcPriceCat']).")";
	}

	$status = [
		"in"  => '<span class="green">На складе</span>',
		"out" => '<span class="gray2">Отгружена</span>'
	];

	$q = "
		SELECT 
			{$sqlname}modcatalog_skladpoz.id,
			{$sqlname}modcatalog_skladpoz.prid,
			{$sqlname}modcatalog_skladpoz.did,
			{$sqlname}modcatalog_skladpoz.kol,
			{$sqlname}modcatalog_skladpoz.sklad,
			{$sqlname}modcatalog_skladpoz.status,
			{$sqlname}modcatalog_skladpoz.date_in,
			{$sqlname}modcatalog_skladpoz.date_out,
			{$sqlname}modcatalog_skladpoz.date_create,
			{$sqlname}modcatalog_skladpoz.date_period,
			{$sqlname}modcatalog_skladpoz.serial,
			{$sqlname}price.title as title,
			{$sqlname}dogovor.title as dogovor
		FROM {$sqlname}modcatalog_skladpoz
			LEFT JOIN {$sqlname}price ON {$sqlname}modcatalog_skladpoz.prid = {$sqlname}price.n_id
			LEFT JOIN {$sqlname}dogovor ON {$sqlname}dogovor.did = {$sqlname}modcatalog_skladpoz.did
		WHERE 
			{$sqlname}modcatalog_skladpoz.id > 0 
			".$sort." and 
			{$sqlname}modcatalog_skladpoz.identity = '$identity' and
			{$sqlname}modcatalog_skladpoz.kol > 0
		";

	$result    = $db -> query( $q );
	$all_lines = $db -> numRows( $result );

	if ( empty( $page ) || $page <= 0 ) {
		$page = 1;
	}
	else {
		$page = (int)$page;
	}
	$page_for_query = $page - 1;
	$lpos           = $page_for_query * $lines_per_page;

	$count_pages = ceil( $all_lines / $lines_per_page );
	if ( $count_pages < 1 ) {
		$count_pages = 1;
	}

	$q = $q.$ordd." ".$tuda." LIMIT $lpos,$lines_per_page";

	$result = $db -> getAll( $q );
	foreach ( $result as $da ) {

		$cat   = '';
		$kol   = 0;
		$res   = '';
		$files = '';
		$sklad = '';

		if ( $da['sklad'] > 0 ) {
			$sklad = $db -> getOne("select title from {$sqlname}modcatalog_sklad where id='".$da['sklad']."' and identity = '$identity'");
		}

		$in          = ($da['status'] == 'out') ? "out" : "";
		$statuscolor = ($da['status'] == 'out') ? "bgray" : "";

		$list[] = [
			"id"          => $da['id'],
			"prid"        => $da['prid'],
			"date_in"     => format_date_rus( $da['date_in'] ),
			"date_out"    => format_date_rus( $da['date_out'] ),
			"date_create" => format_date_rus( $da['date_create'] ),
			"date_period" => format_date_rus( $da['date_period'] ),
			"title"       => $da['title'],
			"serial"      => $da['serial'],
			"kol"         => $da['kol'] + 0,
			"sklad"       => $sklad,
			"did"         => $da['did'],
			"deal"        => $da['dogovor'],
			"status"      => strtr( $da['status'], $status ),
			"in"          => $in,
			"statuscolor" => $statuscolor
		];

	}

	if ( $settings['mcSkladPoz'] == 'yes' ) {
		$mcSkladPoz = '1';
	}
	else {
		$mcSkladPoz = '';
	}

	if ( in_array( $iduser1, $settings['mcCoordinator'] ) ) {
		$mcCoordinator = '1';
	}
	else {
		$mcCoordinator = '';
	}

	$lists = [
		"list"          => $list,
		"page"          => $page,
		"pageall"       => $count_pages,
		"mcSkladPoz"    => $mcSkladPoz,
		"mcCoordinator" => $mcCoordinator,
		"orderby"       => $ord,
		"desc"          => $tuda
	];

	print json_encode_cyr( $lists );

	exit();

}

//лог перемещений по складам
if ( $tar == 'move' ) {

	$ordd = " ORDER BY {$sqlname}modcatalog_skladmove.".$ord;

	if ( $tuda == "desc" ) {
		$icn = '<i class="icon-angle-down"></i>';
	}
	else {
		$icn = '<i class="icon-angle-up"></i>';
	}

	$status = [
		"in"  => '<span class="green">На складе</span>',
		"out" => '<span class="gray2">Отгружена</span>'
	];

	//print_r($sklad);

	$q = "
		SELECT 
			{$sqlname}modcatalog_skladmove.id,
			{$sqlname}modcatalog_skladmove.skladfrom,
			{$sqlname}modcatalog_skladmove.skladto,
			{$sqlname}modcatalog_skladmove.datum,
			{$sqlname}user.title as user
		FROM {$sqlname}modcatalog_skladmove
			LEFT JOIN {$sqlname}user ON {$sqlname}user.iduser = {$sqlname}modcatalog_skladmove.iduser
		WHERE 
			{$sqlname}modcatalog_skladmove.id > 0 and
			{$sqlname}modcatalog_skladmove.identity = '$identity'
		";

	$result    = $db -> query( $q );
	$all_lines = $db -> numRows( $result );

	if ( empty( $page ) || $page <= 0 ) {
		$page = 1;
	}
	else {
		$page = (int)$page;
	}
	$page_for_query = $page - 1;
	$lpos           = $page_for_query * $lines_per_page;

	$count_pages = ceil( $all_lines / $lines_per_page );
	if ( $count_pages < 1 ) {
		$count_pages = 1;
	}

	$q .= " $ordd $tuda LIMIT $lpos,$lines_per_page";

	$result = $db -> getAll( $q );
	foreach ( $result as $da ) {

		$cat   = '';
		$kol   = 0;
		$res   = '';
		$files = '';
		$sklad = '';

		$kol   = $db -> getOne( "select SUM(kol) from {$sqlname}modcatalog_skladmovepoz where idm='".$da['id']."' and identity = '$identity'" ) + 0;
		$count = $db -> getOne( "select COUNT(*) from {$sqlname}modcatalog_skladmovepoz where idm='".$da['id']."' and identity = '$identity'" );

		$list[] = [
			"id"        => $da['id'],
			"datum"     => get_sdate( $da['datum'] ),
			"kol"       => $kol,
			"count"     => $count,
			"user"      => $da['user'],
			"skladfrom" => $skladlist[ $da['skladfrom'] ],
			"skladto"   => $skladlist[ $da['skladto'] ],
		];

	}

	$lists = [
		"list"          => $list,
		"page"          => $page,
		"pageall"       => $count_pages,
		"mcSkladPoz"    => $mcSkladPoz,
		"mcCoordinator" => $mcCoordinator,
		"orderby"       => $ord,
		"desc"          => $tuda
	];

	print json_encode_cyr( $lists );

	exit();

}