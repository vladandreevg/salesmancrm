<?php
/* ============================ */
/*         SalesMan CRM         */
/* ============================ */
/* (C) 2016 Vladislav Andreev   */
/*       SalesMan Project       */
/*        www.isaler.ru         */
/*        ver. 2017.6           */
/* ============================ */

error_reporting( E_ERROR );
header( "Pragma: no-cache" );

$rootpath = dirname(__DIR__, 2);

include $rootpath."/inc/config.php";
include $rootpath."/inc/dbconnector.php";
include $rootpath."/inc/auth.php";
include $rootpath."/inc/func.php";
include $rootpath."/inc/settings.php";
include $rootpath."/inc/language/".$language.".php";

$thisfile = basename( __FILE__ );

$html = $dos = '';

$header = [
	"tip"       => $fieldsNames['client']['tip_cmr'],
	"title"     => $fieldsNames['client']['title'],
	"territory" => $fieldsNames['client']['territory'],
	"category"  => $fieldsNames['client']['idcategory'],
	"phone"     => $fieldsNames['client']['phone'],
	"email"     => $fieldsNames['client']['mail_url'],
	"user"      => $fieldsNames['client']['iduser'],
	"history"   => "Активность",
	"act"       => "Действия"
];

$dostup = $db -> getCol( "SELECT clid FROM {$sqlname}dostup WHERE iduser = '".$iduser1."' and identity = '$identity'" );
if ( count( $dostup ) > 0 )
	$dos = " or {$sqlname}clientcat.clid IN (".implode( ",", $dostup ).")";

$clients = [];

$q = "
SELECT
	DISTINCT({$sqlname}clientcat.clid) as clid,
	{$sqlname}clientcat.last_hist as dhist,
	{$sqlname}clientcat.title as title,
	{$sqlname}clientcat.pid as pid,
	{$sqlname}clientcat.tip_cmr as cmr,
	{$sqlname}clientcat.phone as phone,
	{$sqlname}clientcat.mail_url as email,
	{$sqlname}clientcat.type as type,
	{$sqlname}clientcat.iduser as iduser,
	{$sqlname}personcat.person as person,
	{$sqlname}personcat.tel as tel,
	{$sqlname}personcat.mob as mob,
	{$sqlname}personcat.mail as pemail,
	{$sqlname}user.title as user,
	{$sqlname}category.title as category,
	{$sqlname}territory_cat.title as territory,
	{$sqlname}relations.color as color
FROM {$sqlname}clientcat
	LEFT JOIN {$sqlname}user ON {$sqlname}clientcat.iduser = {$sqlname}user.iduser
	LEFT JOIN {$sqlname}personcat ON {$sqlname}clientcat.pid = {$sqlname}personcat.pid
	LEFT JOIN {$sqlname}relations ON {$sqlname}relations.title = {$sqlname}clientcat.tip_cmr
	LEFT JOIN {$sqlname}category ON {$sqlname}category.idcategory = {$sqlname}clientcat.idcategory
	LEFT JOIN {$sqlname}territory_cat ON {$sqlname}territory_cat.idcategory = {$sqlname}clientcat.territory
WHERE 
	{$sqlname}clientcat.trash != 'yes' and 
	{$sqlname}clientcat.type IN ('client', 'person','contractor','partner') and
	{$sqlname}clientcat.iduser IN (".implode( ",", get_people( $iduser1, "yes" ) ).") 
	$dos and 
	{$sqlname}clientcat.identity = '$identity' 
-- GROUP BY {$sqlname}clientcat.clid 
ORDER BY clid DESC LIMIT 0, $num_client";

$result = $db -> query( $q );
while ($da = $db -> fetch( $result )) {

	$phone   = '';
	$history = '';
	$color   = '';
	$rcolor  = '';
	$sup     = '';
	$task    = '';
	$deal    = '';
	$email   = '';

	$rcolor  = (!$da['color']) ? 'transparent' : $da['color'];
	$sup     = (in_array( $da['clid'], $dostup )) ? '<i class="icon-lock-open green smalltxt sup" title="Вам предоставлен доступ"></i>' : '';
	$history = ($da['dhist'] != '0000-00-00 00:00:00') ? strip_tags( diffDateTime( $da['dhist'] ) ).' назад' : '';

	$count = (int)$db -> getOne( "SELECT COUNT(tid) as count FROM {$sqlname}tasks WHERE clid = '".$da['clid']."' and active = 'yes' and identity = '$identity'" );

	$isaccess = get_accesse( (int)$da['clid'] );

	if ( $isaccess == 'yes' && $count > 0 ) {
		$task = 'yes';
	}

	//$phone = formatPhoneUrl2(array_shift(explode(",", str_replace(";", ",", str_replace(" ", "", $da['phone'])))));

	//Выведем индикатор наличия сделок
	$countCloseBad  = (int)$db -> getOne( "SELECT COUNT(*) as count FROM {$sqlname}dogovor WHERE clid='".$da['clid']."' and close='yes' and kol_fact = 0 and identity = '$identity'" );
	$countCloseGood = (int)$db -> getOne( "SELECT COUNT(*) as count FROM {$sqlname}dogovor WHERE clid='".$da['clid']."' and close='yes' and kol_fact > 0 and identity = '$identity'" );
	$countClose     = $countCloseBad + $countCloseGood;

	$countActive = (int)$db -> getOne( "SELECT COUNT(*) as count FROM {$sqlname}dogovor WHERE clid='".$da['clid']."' and close='no' and identity = '$identity'" );

	$cd = $countClose > 0 ? 'Закрытые. Всего: '.$countClose.', С прибылью: '.$countCloseGood.', Без прибыли: '.$countCloseBad : 'Закрытых: нет';

	if ( $countActive > 0 ) {
		$deal = '<a href="javascript:void(0)" onclick="doLoad(\'/content/vigets/viget.dataview.php?action=dogsView&clid='.$da['clid'].'\')" class="gray red"><i class="icon-briefcase list" title="Активные: '.$countActive.'. '.$cd.'"></i></a>';
	}
	if ( $countActive == 0 && $countClose > 0 ) {
		$deal = '<a href="javascript:void(0)" onclick="doLoad(\'/content/vigets/viget.dataview.php?action=dogsView&clid='.$da['clid'].'\')" class="gray gray2"><i class="icon-briefcase list" title="Есть закрытые. Всего: '.$countClose.', С прибылью: '.$countCloseGood.', Без прибыли: '.$countCloseBad.'"></i></a>';
	}

	if ( $da['cmr'] != '' ) {
		$relation      = $da['cmr'];
		$relationShort = substr( $da['cmr'], 0, 2 );
	}
	else {
		$relation      = 'Не определено';
		$relationShort = 'Н';
	}

	if ( $da['phone'] != '' ) {

		$x     = str_replace( [
			" ",
			";"
		], [
			"",
			","
		], $da['phone'] );
		$phone = formatPhoneUrl( yexplode( ",", $x, 0 ), $da['clid'], $da['pid'] );

	}
	elseif ( $da['mob'] != '' ) {

		$x     = str_replace( [
			" ",
			";"
		], [
			"",
			","
		], $da['mob'] );
		$phone = formatPhoneUrl( yexplode( ",", $x, 0 ), $da['clid'], $da['pid'] );

	}
	elseif ( $da['tel'] != '' ) {

		$x     = str_replace( [
			" ",
			";"
		], [
			"",
			","
		], $da['tel'] );
		$phone = formatPhoneUrl( yexplode( ",", $x, 0 ), $da['clid'], $da['pid'] );

	}

	if ( $da['email'] != '' ) {

		$x     = str_replace( [
			" ",
			";"
		], [
			"",
			","
		], $da['email'] );
		$email = link_it( yexplode( ",", $x, 0 ) );

	}
	elseif ( $da['pemail'] != '' ) {

		$x     = str_replace( [
			" ",
			";"
		], [
			"",
			","
		], $da['pemail'] );
		$email = link_it( yexplode( ",", $x, 0 ) );

	}


	$clients[] = [
		"clid"          => (int)$da['clid'],
		"client"        => $da['title'],
		"relation"      => $relation,
		"category"      => $da['category'],
		"territory"     => $da['territory'],
		"relationShort" => $relationShort,
		"rcolor"        => $rcolor,
		"phone"         => $phone,
		"email"         => $email,
		"pid"           => (int)$da['pid'],
		"person"        => $da['person'],
		"iduser"        => (int)$da['iduser'],
		"user"          => $da['user'],
		"history"       => $history,
		"change"        => $isaccess,
		"color"         => $color,
		"dostup"        => $sup,
		"task"          => $task,
		"deal"          => $deal
	];

}

$data = [
	"header" => $header,
	"client" => $clients
];

//print_r($data);
print json_encode_cyr( $data );