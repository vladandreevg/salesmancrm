<?php
/**
 * @license  http://isaler.ru/
 * @author   Vladislav Andreev, http://iandreyev.ru/
 * @charset  UTF-8
 * @version  6.4
 */

error_reporting(E_ERROR);
ini_set( 'display_errors', 1 );
header("Pragma: no-cache");

$rootpath = realpath( __DIR__.'/../' );

include $rootpath."/inc/config.php";
include $rootpath."/inc/dbconnector.php";
include $rootpath."/inc/auth.php";
include $rootpath."/inc/func.php";
include $rootpath."/inc/settings.php";
include $rootpath."/inc/language/".$language.".php";

$action    = $_REQUEST['action'];
$da1       = $_REQUEST['da1'];
$da2       = $_REQUEST['da2'];
$user_list = (array)$_REQUEST['user_list'];
$tips      = (array)$_REQUEST['tips'];
$type      = $_REQUEST['type'];
$period    = $_REQUEST['period'];

$cpath = [];

$period = ($period == '') ? getPeriod('month') : getPeriod($period);

$da1 = ($da1 != '') ? $da1 : $period[0];
$da2 = ($da2 != '') ? $da2 : $period[1];

if (!isset($type)) {
	$type = 'tip';
}

$taskonly    = $_REQUEST['taskonly'];

if ($taskonly == 'taskonlyGood') {
	$toTitle = '<i class="icon-ok green"></i>&nbsp;Успешное';
}
elseif ($taskonly == 'taskonlyBad') {
	$toTitle = '<i class="icon-block red"></i>&nbsp;Не успешное';
}
elseif ($taskonly == 'notaskonly') {
	$toTitle = 'Без напоминаний';
}
else {
	$toTitle = 'Не учитывать';
}

/**
 * Оборачивает каждый элемент массива в кавычки
 * @param $arr
 * @return mixed
 */
function arraykavichki_extend($arr) {

	foreach ($arr as $k => $v) {
		$arr[ $k ] = "'".$v."'";
	}

	return $arr;

}

$thisfile = basename($_SERVER['PHP_SELF']);

$colors = $tiplist = [];

if (!empty($tips)) {//сохраним параметры, если они есть

	$settings = [
		"user_list" => $user_list,
		"tips"      => $tips
	];

	$f        = '../cash/report_'.$thisfile.'_'.$iduser1.'.txt';
	file_put_contents($f, json_encode_cyr($settings));

}
else {//в противном случае загружаем сохраненные

	$file = '../cash/report_'.$thisfile.'_'.$iduser1.'.txt';
	if (file_exists($file)) {

		$rSet = json_decode((string)file_get_contents($file), true);
		$tips = $rSet['tips'];

	}


}

//print_r($tips);

$sort  = '';
$sort2 = '';

$color = [
	'#F7C1BB',
	'#F29E95',
	'#ED786B',
	'#E74B3B',
	'#E74B3B'
];

//активные сотрудники
$activeUsers = (array)$db -> getCol("SELECT iduser FROM {$sqlname}user WHERE secrty = 'yes' AND identity = '".$identity."'");

//если пользователи не выбраны, то учитываем всех подчиненных
if (empty($user_list)) {

	$sort      .= " and iduser IN (".implode(",", (array)get_people($iduser1, "yes", true)).") and iduser IN (".implode(",", $activeUsers).")";
	$user_list = (array)get_people($iduser1, "yes");

}
//массив выбранных пользователей
else {
	$sort = " and iduser IN (".implode( ",", $user_list ).") and iduser IN (".implode( ",", $activeUsers ).")";
}

//выбранные типы активности
if (!empty($tips)) {

	$cpath = $tips;

	$s = " and title IN (".yimplode(",", $tips, "'").") ";
	$t = " and {$sqlname}history.tip IN (".yimplode(",", $tips, "'").") ";

}
//или все
else {

	$tips  = $db -> getCol("SELECT title FROM {$sqlname}activities WHERE id > 0 and identity = '$identity'");
	$cpath = $tips;

	//$tips = arraykavichki_extend($tips);

	$s = " and title IN (".yimplode(",", $tips, "'").") ";
	$t = " and tip IN (".yimplode(",", $tips, "'").") ";

}

$res = $db -> getAll("SELECT * FROM {$sqlname}activities WHERE id > 0 and identity = '$identity'");
foreach ($res as $data) {

	$tiplist[ mb_strtoupper($data['title'], 'utf-8') ] = $data['color'];
	$tiplists[ $data['title'] ]                        = $data['color'];
	$tlist[]                                           = "'".$data['title']."'";
	$colors[]                                          = $data['color'];

}

$tiplist['ЛОГCRM']     = $tiplists['ЛогCRM'] = '#607D8B';
$colors[]              = '#607D8B';
$tiplist['СОБЫТИЕCRM'] = $tiplists['СобытиеCRM'] = '#9E9E9E';
$colors[]              = '#9E9E9E';

if ($taskonly == 'taskonlyGood') {
	$t .= $sort .= " 
		 AND (SELECT COUNT(tid) FROM {$sqlname}tasks WHERE {$sqlname}tasks.cid = {$sqlname}history.cid AND {$sqlname}tasks.identity = '$identity') > 0 
		 AND (SELECT COUNT(tid) FROM {$sqlname}tasks WHERE {$sqlname}tasks.status = '1') > 0
	";
}
elseif ($taskonly == 'taskonlyBad') {
	$t .= $sort .= " 
		 AND(SELECT COUNT(tid) FROM {$sqlname}tasks WHERE {$sqlname}tasks.cid = {$sqlname}history.cid AND {$sqlname}tasks.identity = '$identity') > 0  
		 AND (SELECT COUNT(tid) FROM {$sqlname}tasks WHERE {$sqlname}tasks.cid = {$sqlname}history.cid AND {$sqlname}tasks.status = '2') > 0
	";
}
elseif ($taskonly == 'notaskonly') {
	$t .= $sort .= " 
		 AND (SELECT COUNT(tid) FROM {$sqlname}tasks WHERE {$sqlname}tasks.cid = {$sqlname}history.cid AND {$sqlname}tasks.identity = '$identity') = 0
	";
}

if ($action == 'view') {

	$time = $_REQUEST['time'];
	$tip  = $_REQUEST['tip'];

	if ($tip != 'undefined' && $type == 'tip') {

		$t   = " and tip = '$tip'";
		$apx = ' для типа <b class="green">'.$tip.'</b>';

	}
	else {

		$apx = ' для сотрудника <b class="green">'.current_user($tip).'</b>';

	}

	switch ($time) {

		case "h7":
			$g = "с 0:00 до 8:00";
			$s = " and (DATE_FORMAT(datum, '%k') >= 0 and DATE_FORMAT(datum, '%k') < 8)";
		break;
		case "h8":
			$g = "с 8:00 до 9:00";
			$s = " and DATE_FORMAT(datum, '%k') = 8";
		break;
		case "h9":
			$g = "с 9:00 до 10:00";
			$s = " and DATE_FORMAT(datum, '%k') = 9";
		break;
		case "h10":
			$g = "с 10:00 до 11:00";
			$s = " and DATE_FORMAT(datum, '%k') = 10";
		break;
		case "h11":
			$g = "с 11:00 до 12:00";
			$s = " and DATE_FORMAT(datum, '%k') = 11";
		break;
		case "h12":
			$g = "с 12:00 до 13:00";
			$s = " and DATE_FORMAT(datum, '%k') = 12";
		break;
		case "h13":
			$g = "с 13:00 до 14:00";
			$s = " and DATE_FORMAT(datum, '%k') = 13";
		break;
		case "h14":
			$g = "с 14:00 до 15:00";
			$s = " and DATE_FORMAT(datum, '%k') = 14";
		break;
		case "h15":
			$g = "с 15:00 до 16:00";
			$s = " and DATE_FORMAT(datum, '%k') = 15";
		break;
		case "h16":
			$g = "с 16:00 до 17:00";
			$s = " and DATE_FORMAT(datum, '%k') = 16";
		break;
		case "h17":
			$g = "с 17:00 до 18:00";
			$s = " and DATE_FORMAT(datum, '%k') = 17";
		break;
		case "h18":
			$g = "с 18:00 до 19:00";
			$s = " and DATE_FORMAT(datum, '%k') = 18";
		break;
		case "h19":
			$g = "с 19:00 до 20:00";
			$s = " and DATE_FORMAT(datum, '%k') = 19";
		break;
		case "h20":
			$g = "с 20:00 до 24:00";
			$s = " and (DATE_FORMAT(datum, '%k') >= 20 and DATE_FORMAT(datum, '%k')  <= 23)";
		break;

	}

	print '
		<br>
		<div class="zagolovok_rep">Активности в период <b class="blue">'.$g.'</b> '.$apx.':</div>
		<hr>
		<TABLE class="wp100">
		<thead class="sticked--top">
		<TR class="header_contaner th40">
			<td class="w120 text-left"><b>Дата. Время</b></td>
			<td class="w100 text-left"><b>Тип</b></td>
			<td class="text-left"><b>Событие</b></td>
			<td class="w100 text-left"><b>Ответственный</b></td>
			<td class="w200 text-left"><b>Клиент</b>/<b>Сделка</b></td>
		</TR>
		</thead>
	';

	if ($type == 'tip') {

		$r = $db -> getAll("
			SELECT 
			    *, 
			    substring(des, 1, 200) as descr, 
			    (SELECT status FROM {$sqlname}tasks WHERE {$sqlname}tasks.cid = {$sqlname}history.cid) as status, 
			    (SELECT tid FROM {$sqlname}tasks WHERE {$sqlname}tasks.cid = {$sqlname}history.cid) as tid 
			FROM {$sqlname}history 
			WHERE 
			    cid > 0 and 
			    (DATE(datum) >= '$da1' and DATE(datum) <= '$da2')
			    $sort $s $t and 
			    identity = '$identity' 
			ORDER BY datum DESC");
		foreach ($r as $da) {

			$client = $person = $dogovor = $ticon = '';

			if ((int)$da['clid'] > 0) {
				$client = '<div class="ellipsis hand" onclick="viewClient(\''.$da['clid'].'\')" title="Просмотр"><i class="icon-building broun"></i>'.current_client( $da['clid'] ).'</div>';
			}

			if ($da['pid'] != '') {
				$pids = yexplode(",", $da['pid']);

				foreach ($pids as $pid) {
					if ((int)$pid > 0) {
						$person = '<div class="ellipsis"><i class="icon-user-1 broun"></i>'.current_person( $pid ).'</div>';
					}
				}
			}

			if ((int)$da['did'] > 0) {
				$dogovor = '<div class="ellipsis hand" onclick="viewDogovor(\''.$da['did'].'\')" title="Просмотр"><i class="icon-briefcase blue"></i>'.current_dogovor( $da['did'] ).'</div>';
			}


			if($da['status'] == 1) {
				$status = '<i class="icon-ok green sup" title="Успешно"></i>';
			}
			elseif($da['status'] == 2) {
				$status = '<i class="icon-block red sup" title="Не успешно"></i>';
			}
			else {
				$status = '<i class="icon-ok green sup" title="Не определено"></i>';
			}

			if((int)$da['tid'] > 0){

				$ticon = '<a href="javascript:void(0)" onclick="viewTask(\''.$da['tid'].'\')"><i class="icon-calendar-inv broun">'.$status.'</i></a>&nbsp;&nbsp;';

			}

			print '
			<tr class="ha th40">
				<TD>'.get_sfdate($da['datum']).'</TD>
				<TD>
				<div class="ellipsis">
					<div class="bullet-mini" style="background: '.$tiplist[ mb_strtoupper($da['tip'], 'utf-8') ].'"></div>&nbsp;'.$da['tip'].'
				</div>
				</TD>
				<TD class="hview hand" data-cid="'.$da['cid'].'">
					'.$ticon.'<span class="ellipsis" onclick="viewHistory(\''.$da['cid'].'\')" title="Просмотр">'.nl2br(untag3(html2text($da['descr']))).'</span>
				</TD>
				<TD><div class="ellipsis">'.current_user($da['iduser']).'</div></TD>
				<TD>
					<div class="ellipsis">'.$client.'</div><br>
					<div class="ellipsis">'.$dogovor.'</div>
				</TD>
			</tr>';

		}

		if (empty($r)) {
			print '<tr class="th40"><td colspan="5">Данных не обнаружено</td></tr>';
		}

	}
	else {

		$s .= $tip != 'undefined' ? " AND iduser = '$tip'" : '';

		$r = $db -> getAll("
			SELECT 
			    *, 
			    substring(des, 1, 200) as descr, 
			    (SELECT status FROM {$sqlname}tasks WHERE {$sqlname}tasks.cid = {$sqlname}history.cid) as status, 
			    (SELECT tid FROM {$sqlname}tasks WHERE {$sqlname}tasks.cid = {$sqlname}history.cid) as tid 
			FROM {$sqlname}history 
			WHERE 
			    cid > 0 and 
			    (DATE(datum) >= '$da1' and DATE(datum) <= '$da2')
			    $s $t and 
			    identity = '$identity' 
			ORDER BY datum DESC");
		foreach ($r as $da) {

			$client = $person = $dogovor = '';

			if ((int)$da['clid'] > 0) {
				$client = '<div class="ellipsis hand" onclick="viewClient(\''.$da['clid'].'\')" title="Просмотр"><i class="icon-building broun"></i>'.current_client( $da['clid'] ).'</div>';
			}

			if ($da['pid'] != '') {
				$pids = explode(",", $da['pid']);
				foreach ($pids as $pid) {
					if ((int)$pid > 0) {
						$person = '<div class="ellipsis"><i class="icon-user-1 broun"></i>'.current_person( $pid ).'</div>';
					}
				}
			}

			if ((int)$da['did'] > 0) {
				$dogovor = '<div class="ellipsis hand" onclick="viewDogovor(\''.$da['did'].'\')" title="Просмотр"><i class="icon-briefcase blue"></i>'.current_dogovor( $da['did'] ).'</div>';
			}


			if($da['status'] == 1) {
				$status = '<i class="icon-ok green sup" title="Успешно"></i>';
			}
			elseif($da['status'] == 2) {
				$status = '<i class="icon-block red sup" title="Не успешно"></i>';
			}
			else {
				$status = '<i class="icon-ok green sup" title="Не определено"></i>';
			}

			if((int)$da['tid'] > 0){

				$ticon = '<a href="javascript:void(0)" onclick="viewTask(\''.$da['tid'].'\')"><i class="icon-calendar-inv broun">'.$status.'</i></a>&nbsp;&nbsp;';

			}

			print '
			<tr class="ha th40">
				<TD>'.get_sfdate($da['datum']).'</TD>
				<TD>
				<div class="ellipsis">
					<div class="bullet-mini" style="background: '.$tiplist[ mb_strtoupper($da['tip'], 'utf-8') ].'"></div>&nbsp;'.$da['tip'].'
				</div>
				</TD>
				<TD class="hview hand" data-cid="'.$da['cid'].'" title="Просмотр">
					'.$ticon.'<span class="ellipsis" onclick="viewHistory(\''.$da['cid'].'\')">'.nl2br(untag3(html2text($da['descr']))).'</span>
				</TD>
				<TD><div class="ellipsis">'.current_user($da['iduser']).'</div></TD>
				<TD>
					<div class="ellipsis">'.$client.'</div><br>
					<div class="ellipsis">'.$dogovor.'</div>
				</TD>
			</tr>';

		}

		if (empty($r)) {
			print '<tr class="th40"><td colspan="5">Данных не обнаружено</td></tr>';
		}

	}

	print '</TABLE>';

	exit();

}

$min   = $max = 0;
$users = [];
$tipChart = $order = [];

foreach ($user_list as $iduser) {

	if (in_array($iduser, $activeUsers)) {
		$users[ $iduser ] = current_user( $iduser, "yes" );
	}

}

//print_r($user_list);

$xrez = [];

if ($type == 'tip') {

	$data = $db -> getAll("
		SELECT 
			tip,
			HOUR(datum) AS h,
			COUNT(cid) as count 
		FROM {$sqlname}history 
		WHERE 
			cid > 0 and 
			(DATE(datum) >= '$da1' and DATE(datum) <= '$da2')
			$sort and 
			tip IN (".yimplode(",", $cpath, "'").") and 
			identity = '$identity'
		GROUP BY 1, 2
		");
	foreach ($data as $row){

		if(is_between((int)$row['h'], 0,  7)){
			$xrez['h7'][ $row['tip'] ] += (int)$row['count'];
		}
		elseif(is_between((int)$row['h'], 20,  24)){
			$xrez['h20'][ $row['tip'] ] += (int)$row['count'];
		}
		else{
			$xrez[ 'h'.$row['h'] ][ $row['tip'] ] += (int)$row['count'];
		}

		if ((int)$row['count'] > $max) {
			$max = (int)$row['count'];
		}

	}

	for($i = 7; $i <= 20; $i++){

		foreach ($cpath as $tip){

			$rez[ 'h'.$i ][ $tip ] += (int)$xrez[ 'h'.$i ][ $tip ];

		}

	}

	/*foreach ($cpath as $tip){

		$count = (int)$db -> getOne("SELECT COUNT(cid) as count FROM {$sqlname}history WHERE cid > 0 and (DATE(datum) >= '$da1' and DATE(datum) <= '$da2') $sort and tip = '$tip' and identity = '$identity'");

		$tipChart[] = '{"Тип":"'.$tip.'","Кол-во":"'.$count.'"}';
		$order[]    = "'".$tip."'";

	}*/

	$u = $db -> getAll("
		SELECT 
		    tip,
		    COUNT(cid) as count 
		FROM {$sqlname}history 
		WHERE 
		    cid > 0 and 
		    (DATE(datum) >= '$da1' and DATE(datum) <= '$da2') 
		    $sort and 
		    tip IN (".yimplode(",", $cpath, "'").") and
		    identity = '$identity'
		GROUP BY 1
		");

	foreach ($u as $r){

		$tipChart[] = '{"Тип":"'.$r['tip'].'","Кол-во":"'.$r['count'].'"}';

	}

	$order = $cpath;

}
else {

	$data = $db -> getAll("
		SELECT 
			iduser,
			HOUR(datum) AS h,
			COUNT(cid) as count 
		FROM {$sqlname}history 
		WHERE 
			cid > 0 and 
			(DATE(datum) >= '$da1' and DATE(datum) <= '$da2')
			$sort and 
			tip IN (".yimplode(",", $cpath, "'").") and 
			identity = '$identity'
		GROUP BY 1, 2
		");
	foreach ($data as $row){

		if(is_between((int)$row['h'], 0,  7)){
			$xrez['h7'][ $users[$row['iduser']] ] += (int)$row['count'];
		}
		elseif(is_between((int)$row['h'], 20,  24)){
			$xrez['h20'][ $users[$row['iduser']] ] += (int)$row['count'];
		}
		else{
			$xrez[ 'h'.$row['h'] ][ $users[$row['iduser']] ] += (int)$row['count'];
		}

		if ((int)$row['count'] > $max) {
			$max = (int)$row['count'];
		}

	}

	for($i = 7; $i <= 20; $i++){

		foreach ($users as $iduser => $name){

			$rez[ 'h'.$i ][ $name ] += (int)$xrez[ 'h'.$i ][ $name ];

		}

	}

	/*foreach ($users as $iduser => $name){

		$count = (int)$db -> getOne("SELECT COUNT(cid) as count FROM {$sqlname}history WHERE cid > 0 and (datum BETWEEN '".$da1." 00:00:00' and '".$da2." 23:59:59') and iduser = '$iduser' $t and identity = '$identity'");

		$tipChart[] = '{"Тип":"'.$name.'","Кол-во":"'.$count.'"}';
		$order[]    = "'".$name."'";

	}*/

	$u = $db -> getAll("
		SELECT 
		    {$sqlname}history.iduser,
		    {$sqlname}user.title as user,
		    COUNT({$sqlname}history.cid) as count 
		FROM {$sqlname}history
		LEFT JOIN {$sqlname}user ON {$sqlname}user.iduser = {$sqlname}history.iduser
		WHERE 
		    {$sqlname}history.cid > 0 and 
		    (DATE({$sqlname}history.datum) >= '$da1' and DATE({$sqlname}history.datum) <= '$da2') and 
		    {$sqlname}history.iduser IN (".yimplode(",", array_keys($users)).") 
		    $t and 
		    {$sqlname}history.identity = '$identity'
		GROUP BY 1
		");

	foreach ($u as $r){

		$tipChart[] = '{"Тип":"'.$r['user'].'","Кол-во":"'.$r['count'].'"}';

	}

	$order = array_values($users);

}

//print_r($tipChart);
//print_r($rez);
//print $max;

$tipChart = yimplode(",", $tipChart);
$order    = yimplode(",", $order, "'");

?>
<STYLE type="text/css">
	<!--
	.color1 {
		background : rgba(181, 59, 46, 0.1);
		color      : #222;
	}

	.color2 {
		background : rgba(181, 59, 46, 0.2);
		color      : #222;
	}

	.color3 {
		background : rgba(181, 59, 46, 0.3);
		color      : #222;
	}

	.color4 {
		background : rgba(181, 59, 46, 0.4);
		color      : #222;
	}

	.color5 {
		background : rgba(181, 59, 46, 0.5);
		color      : #222;
	}

	.color6 {
		background : rgba(181, 59, 46, 0.6);
		color      : #222;
	}

	.color7 {
		background : rgba(181, 59, 46, 0.7);
		color      : #FFF;
	}

	.color8 {
		background : rgba(181, 59, 46, 0.8);
		color      : #FFF;
	}

	.color9 {
		background : rgba(181, 59, 46, 0.9);
		color      : #FFF;
	}

	.color10 {
		background : rgba(181, 59, 46, 1.0);
		color      : #FFF;
	}

	.colorit .color1 {
		background : rgba(41, 128, 185, 0.1);
		color      : #222;
	}

	.colorit .color2 {
		background : rgba(41, 128, 185, 0.2);
		color      : #222;
	}

	.colorit .color3 {
		background : rgba(41, 128, 185, 0.3);
		color      : #222;
	}

	.colorit .color4 {
		background : rgba(41, 128, 185, 0.4);
		color      : #222;
	}

	.colorit .color5 {
		background : rgba(41, 128, 185, 0.5);
		color      : #FFF;
	}

	.colorit .color6 {
		background : rgba(41, 128, 185, 0.6);
		color      : #FFF;
	}

	.colorit .color7 {
		background : rgba(41, 128, 185, 0.7);
		color      : #FFF;
	}

	.colorit .color8 {
		background : rgba(41, 128, 185, 0.8);
		color      : #FFF;
	}

	.colorit .color9 {
		background : rgba(41, 128, 185, 0.9);
		color      : #FFF;
	}

	.colorit .color10 {
		background : rgba(41, 128, 185, 1.0);
		color      : #FFF;
	}

	.itog {
		background : rgba(207, 216, 220, 0.3);
	}

	.graybg-spec {
		background : rgba(207, 216, 220, 0.8);
	}

	.color:hover,
	.color:active {
		background         : #F1C40F;
		color              : #222;
		font-weight        : 700;
		font-size          : 1.4em;
		transition         : all 400ms ease;
		-webkit-transition : all 400ms ease;
		-moz-transition    : all 400ms ease;
	}

	.itog:hover,
	.itog:active {
		background         : rgba(207, 216, 220, 0.8); /*rgba(46,204,113,0.6);*/
		color              : #222;
		font-weight        : 700;
		font-size          : 1.1em;
		transition         : all 400ms ease;
		-webkit-transition : all 400ms ease;
		-moz-transition    : all 400ms ease;
	}

	.hist:hover,
	.hist:active {
		background         : rgba(41, 128, 185, 1.0);
		color              : #FFF;
		font-weight        : 700;
		font-size          : 1.0em;
		transition         : all 400ms ease;
		-webkit-transition : all 400ms ease;
		-moz-transition    : all 400ms ease;
	}

	.timeline {
		height         : 30px;
		display        : flex !important;
		flex-direction : row !important;
		flex-wrap      : wrap !important;
		border-bottom  : 2px solid #222;
		box-sizing     : border-box;
		vertical-align : bottom;
		margin-top     : 30px;
		width          : 99%;
	}

	.time {
		flex-grow     : 1;
		height        : 20px;
		text-align    : center;
		border-left   : 1px solid #222;
		border-bottom : 2px solid #222;
		position      : relative;
		margin-top    : 10px;
		color         : #222 !important;
		font-weight   : 700;
		box-sizing    : border-box;
		width         : calc((100% - 150px) / 16);
		cursor        : pointer;
	}

	.time:first-child {
		border-left : 1px solid #222;
		width       : 150px;
		cursor      : default;
	}

	.time:not(:first-child):hover,
	.time:last-child:hover {
		background : rgba(41, 128, 185, 0.3);
	}

	.time.small {
		position    : relative;
		border-left : 0;
	}

	.time.small:before {
		content     : " ";
		position    : absolute;
		left        : 0;
		bottom      : 0;
		width       : 1px;
		height      : 10px;
		border-left : 1px solid #222;
	}

	.time .number {
		position : absolute;
		top      : -20px;
		left     : -10px;
	}

	.time:nth-child(1) .number {
		right : inherit;
		left  : -5px;
	}

	.time:last-child {
		border-left : 1px solid #222;
		width       : 0 !important;
		flex-grow   : 0;
	}

	.time:last-child .number {
		left  : inherit;
		right : 0;
	}

	.timeline-string {
		height         : 40px;
		display        : flex !important;
		flex-direction : row !important;
		flex-wrap      : wrap !important;
		border-bottom  : 1px solid #ddd;
		border-left    : 1px solid #ddd;
		box-sizing     : border-box;
		width          : 99%;
	}

	.time-string {
		flex-grow    : 1;
		border-right : 1px solid #ddd;
		/*color: #222;*/
		font-size    : 0.95em;
		font-weight  : 400;
		text-align   : center;
		height       : 40px;
		line-height  : 40px;
		box-sizing   : border-box;
		width        : calc((100% - 150px) / 16);
		cursor       : pointer;
		position     : relative;
	}

	.time-string:not(:first-child):hover,
	.time-string:last-child:hover {
		background : rgba(41, 128, 185, 0.3);
	}

	.time-string:last-child {
		width        : 0 !important;
		flex-grow    : 0;
		border-right : 0;
	}

	.time-string:first-child {
		font-weight  : 700;
		box-sizing   : border-box;
		width        : 150px;
		text-align   : left;
		padding-left : 10px;
		cursor       : default;
	}

	.timeline-string.itog .time-string:first-child {
		cursor : pointer;
	}

	.timeline-string.hidden {
		display : none !important;
	}

	.time-string .loaderr {
		z-index    : 5;
		position   : absolute;
		top        : 0px;
		width      : 100%;
		text-align : center;
	}

	.dimple-custom-axis-line {
		stroke       : black !important;
		stroke-width : 1.1;
	}

	.dimple-custom-axis-label {
		font-family : Arial !important;
		font-size   : 11px !important;
		font-weight : 500;
	}

	.dimple-custom-gridline {
		stroke-width     : 1;
		stroke-dasharray : 5;
		fill             : none;
		stroke           : #CFD8DC !important;
	}

	-->
</STYLE>

<br>

<table class="noborder">
	<tr>
		<td class="wp25">
			<div class="ydropDown">
				<span>Только Активности</span>
				<span class="ydropCount"><?= count($cpath) ?> выбрано</span><i class="icon-angle-down pull-aright"></i>
				<div class="yselectBox">
					<div class="right-text">
						<div class="ySelectAll w0 inline" title="Выделить всё"><i class="icon-plus-circled"></i>Всё
						</div>
						<div class="yunSelect w0 inline" title="Снять выделение"><i class="icon-minus-circled"></i>Ничего
						</div>
					</div>
					<?php
					$result = $db -> query("SELECT * FROM {$sqlname}activities WHERE identity = '$identity' ORDER BY title");
					while ($data = $db -> fetch($result)) {
						?>
						<div class="ydropString ellipsis">
							<label>
								<input class="taskss" name="tips[]" type="checkbox" id="tips[]" value="<?= $data['title'] ?>" <?php if (in_array($data['title'], $cpath)) {
									print 'checked';
								} ?>>
								<div class="bullet-mini" style="background: <?= $data['color'] ?>"></div>&nbsp;<?= $data['title'] ?>
							</label>
						</div>
						<?php
					}
					?>
					<div class="ydropString ellipsis">
						<label>
							<input class="taskss" name="tips[]" type="checkbox" id="tips[]" value="СобытиеCRM" <?php if (in_array('СобытиеCRM', $cpath)) {
								print 'checked';
							} ?>>
							<div class="bullet-mini" style="background: #9E9E9E"></div>&nbsp;СобытиеCRM
						</label>
					</div>
					<div class="ydropString ellipsis">
						<label>
							<input class="taskss" name="tips[]" type="checkbox" id="tips[]" value="ЛогCRM" <?php if (in_array('ЛогCRM', $cpath)) {
								print 'checked';
							} ?>>
							<div class="bullet-mini" style="background: #607D8B"></div>&nbsp;ЛогCRM
						</label>
					</div>
				</div>
			</div>
		</td>
		<td class="wp25">
			<div class="ydropDown" data-id="sort">

				<span class="yText Bold fs-09">Напоминания</span>
				<span class="ydropText Bold"><?= $toTitle ?></span>
				<i class="icon-angle-down pull-aright"></i>

				<div class="yselectBox">

					<div class="ydropString yRadio ellipsis">
						<label>
							<input name="taskonly" type="radio" id="taskonly" data-title="Не учитывать" class="hidden" value="" <?= (!isset($taskonly) || $taskonly == '' ? 'checked' : '') ?>>&nbsp;Не учитывать
						</label>
					</div>
					<div class="ydropString yRadio ellipsis">
						<label>
							<input name="taskonly" type="radio" id="taskonly" data-title="Успешное" class="hidden" value="taskonlyGood" <?= ($taskonly == 'taskonlyGood' ? 'checked' : '') ?>>&nbsp;<i class="icon-ok green"></i>&nbsp;Успешное выполнение
						</label>
					</div>
					<div class="ydropString yRadio ellipsis">
						<label>
							<input name="taskonly" type="radio" id="taskonly" data-title="Не успешное" class="hidden" value="taskonlyBad" <?= ($taskonly == 'taskonlyBad' ? 'checked' : '') ?>>&nbsp;<i class="icon-block red"></i>&nbsp;Не успешное выполнение
						</label>
					</div>
					<div class="ydropString yRadio ellipsis">
						<label>
							<input name="taskonly" type="radio" id="taskonly" data-title="Нет" class="hidden" value="notaskonly" <?= ($taskonly == 'notaskonly' ? 'checked' : '') ?>>&nbsp;Только без напоминаний
						</label>
					</div>

				</div>
			</div>
		</td>
		<td class="wp25"></td>
		<td class="wp25"></td>
	</tr>
</table>

<div>

	<div class="inline paddright15 margleft5 mb10">
		<div class="radio mt5">
			<label>
				<input name="type" type="radio" id="type" value="tip" <?php if ($type == 'tip') {
					print "checked";
				} ?>>
				<span class="custom-radio success1"><i class="icon-radio-check"></i></span>
				<span class="title">По активности</span>
			</label>
		</div>
	</div>
	<div class="inline paddright15 margleft5 mb10">
		<div class="radio mt5">
			<label>
				<input name="type" type="radio" id="type" value="user" <?php if ($type == 'user') {
					print "checked";
				} ?>>
				<span class="custom-radio success1"><i class="icon-radio-check"></i></span>
				<span class="title">По сотруднику</span>
			</label>
		</div>
	</div>

</div>

<div class="relativ mt20 mb20 wp95 text-center">

	<h1 class="uppercase fs-14 m0 mb10">Активности по времени</h1>
	<div class="gray2">за период с <?= format_date_rus($da1) ?>&nbsp;по&nbsp;<?= format_date_rus($da2) ?></div>

</div>

<br>

<div class="p10 mt20 pt20 pb20">

	<div class="timeline">

		<div class="time"></div>
		<div class="time" data-time="h7">&nbsp;<div class="number">0<sup>00</sup></div>
		</div>
		<div class="time" data-time="h8">&nbsp;<div class="number">8<sup>00</sup></div>
		</div>
		<div class="time small" data-time="h9">&nbsp;</div>
		<div class="time" data-time="h10">&nbsp;<div class="number">10<sup>00</sup></div>
		</div>
		<div class="time small" data-time="h11">&nbsp;</div>
		<div class="time" data-time="h12">&nbsp;<div class="number">12<sup>00</sup></div>
		</div>
		<div class="time small" data-time="h13">&nbsp;</div>
		<div class="time" data-time="h14">&nbsp;<div class="number">14<sup>00</sup></div>
		</div>
		<div class="time small" data-time="h15">&nbsp;</div>
		<div class="time" data-time="h16">&nbsp;<div class="number">16<sup>00</sup></div>
		</div>
		<div class="time small" data-time="h17">&nbsp;</div>
		<div class="time" data-time="h18">&nbsp;<div class="number">18<sup>00</sup></div>
		</div>
		<div class="time small" data-time="h19">&nbsp;</div>
		<div class="time" data-time="h20">&nbsp;<div class="number">20<sup>00</sup></div>
		</div>
		<div class="time" data-time="h21">&nbsp;<div class="number">24<sup>00</sup></div>
		</div>

	</div>

	<div class="timeline-string itog">

		<div class="time-string"><span class="pull-left ml10"><i class="icon-angle-down"></i></span>Всего</div>
		<div class="time-string" data-time="h7"><?= array_sum($rez['h7']) ?></div>
		<div class="time-string" data-time="h8"><?= array_sum($rez['h8']) ?></div>
		<div class="time-string" data-time="h9"><?= array_sum($rez['h9']) ?></div>
		<div class="time-string" data-time="h10"><?= array_sum($rez['h10']) ?></div>
		<div class="time-string" data-time="h11"><?= array_sum($rez['h11']) ?></div>
		<div class="time-string" data-time="h12"><?= array_sum($rez['h12']) ?></div>
		<div class="time-string" data-time="h13"><?= array_sum($rez['h13']) ?></div>
		<div class="time-string" data-time="h14"><?= array_sum($rez['h14']) ?></div>
		<div class="time-string" data-time="h15"><?= array_sum($rez['h15']) ?></div>
		<div class="time-string" data-time="h16"><?= array_sum($rez['h16']) ?></div>
		<div class="time-string" data-time="h17"><?= array_sum($rez['h17']) ?></div>
		<div class="time-string" data-time="h18"><?= array_sum($rez['h18']) ?></div>
		<div class="time-string" data-time="h19"><?= array_sum($rez['h19']) ?></div>
		<div class="time-string" data-time="h20"><?= array_sum($rez['h20']) ?></div>
		<div class="time-string">&nbsp;</div>

	</div>

	<?php
	if ($type != 'tip') {
		$cpath = $users;
	}

	foreach ($cpath as $k => $item) {

		$v = ($type == 'tip') ? $item : $k;
		?>
		<div class="timeline-string child colorit" data-tip="<?= $v ?>">

			<div class="time-string left-text no--overflow">
				<div class="bullet-mini" style="background: <?= $tiplist[ mb_strtoupper($item, 'utf-8') ] ?>"></div>&nbsp;<?= $item ?>
			</div>
			<div class="time-string time-data" data-time="h7"><?= $rez['h7'][ $item ] ?></div>
			<div class="time-string time-data" data-time="h8"><?= $rez['h8'][ $item ] ?></div>
			<div class="time-string time-data" data-time="h9"><?= $rez['h9'][ $item ] ?></div>
			<div class="time-string time-data" data-time="h10"><?= $rez['h10'][ $item ] ?></div>
			<div class="time-string time-data" data-time="h11"><?= $rez['h11'][ $item ] ?></div>
			<div class="time-string time-data" data-time="h12"><?= $rez['h12'][ $item ] ?></div>
			<div class="time-string time-data" data-time="h13"><?= $rez['h13'][ $item ] ?></div>
			<div class="time-string time-data" data-time="h14"><?= $rez['h14'][ $item ] ?></div>
			<div class="time-string time-data" data-time="h15"><?= $rez['h15'][ $item ] ?></div>
			<div class="time-string time-data" data-time="h16"><?= $rez['h16'][ $item ] ?></div>
			<div class="time-string time-data" data-time="h17"><?= $rez['h17'][ $item ] ?></div>
			<div class="time-string time-data" data-time="h18"><?= $rez['h18'][ $item ] ?></div>
			<div class="time-string time-data" data-time="h19"><?= $rez['h19'][ $item ] ?></div>
			<div class="time-string time-data" data-time="h20"><?= $rez['h20'][ $item ] ?></div>
			<div class="time-string">&nbsp;</div>

		</div>
		<?php
	}
	?>

</div>

<div class="div-center colorit">
	<?php
	$step = round($max / 10);
	$s    = 0;

	for ($i = 1; $i <= 10; $i++) {

		$m = ($i != 10) ? ($s + $step) : $max;

		echo '<div class="color'.$i.' inline p5 pl10 pr10 fs-07">'.$s.' - '.$m.'</div>';

		$s = $s + $step;

	}
	?>
</div>

<div class="charts p20 mt20" id="statica" style="display:block; height:300px">

	<div id="chartGraf" style="padding:5px"></div>

</div>

<div id="detale" class="detale paddtop10 paddbott10"></div>
<a id="dataa"></a>

<div style="height: 100px"></div>

<script src="/assets/js/dimple.js/dimple.min.js"></script>
<!--<script src="/assets/js/d3.min.js"></script>-->
<script>

	var max = <?=$max?>;

	$(document).ready(function () {

		$(".time-data").each(function () {

			var num = parseInt($(this).html());

			if (num == 0) $(this).css({'color': '#ccc'});
			else if (num <= 0.1 * max) $(this).addClass('color1');
			else if (num <= 0.2 * max) $(this).addClass('color2');
			else if (num <= 0.3 * max) $(this).addClass('color3');
			else if (num <= 0.4 * max) $(this).addClass('color4');
			else if (num <= 0.5 * max) $(this).addClass('color5');
			else if (num <= 0.6 * max) $(this).addClass('color6');
			else if (num <= 0.7 * max) $(this).addClass('color7');
			else if (num <= 0.8 * max) $(this).addClass('color8');
			else if (num <= 0.9 * max) $(this).addClass('color9');
			else $(this).addClass('color10');

		});

		tipGraf();

	});

	$('.time-string:not(:first-child), .time').click(function () {

		var time = $(this).data('time');

		var tip = $(this).closest('.timeline-string').data('tip');
		var type = $('#type:checked').val();

		var str = $('#selectreport').serialize();
		var url = './reports/<?=$thisfile?>?action=view&time=' + time + '&tip=' + tip + '&type=' + type + '&' + str + '&period=' + $('#swPeriod').val();

		$(this).append('<div id="loaderr" class="loaderr"><img src="/assets/images/loading.svg" width="40"></div>');

		//if(type == 'tip') url += '&tip='+tip;
		//else url += '&iduser='+tip;

		//var vtop = $('#detale').offset();
		//$(".nano").nanoScroller({scrollTop: vtop.top});

		$('#detale').empty().append('<div id="loader" class="loader"><img src=/assets/images/loading.svg> Вычисление...</div>');

		$.get(url, function (data) {

			$('#detale').html(data);

		}).complete(function () {

			var vtop2 = $('#detale').position();
			$(".nano").nanoScroller({scrollTop: vtop2.top});

			$('.loaderr').remove();

		});

	});

	$('.time-string:first-child').click(function () {

		$('.child').each(function () {
			$(this).toggleClass('hidden');
		});

		$(this).find('i').toggleClass('icon-angle-down icon-angle-up');

	});

	$('.hview').live('click', function () {
		var cid = $(this).data('cid');
		viewHistory(cid);
	});

	function tipGraf() {

		var width = $('.timeline').width() - 40;
		if (width < 1) width = 600;
		var height = 300;
		var svg = dimple.newSvg("#chartGraf", width, height);
		var data = [<?=$tipChart?>];

		var myChart = new dimple.chart(svg, data);

		myChart.setBounds(100, 0, width - 50, height - 100);

		var x = myChart.addCategoryAxis("x", ["Тип"]);
		x.addOrderRule([<?=$order?>]);//порядок вывода, иначе группирует
		x.showGridlines = true;

		var y = myChart.addMeasureAxis("y", "Кол-во");
		y.showGridlines = false;//скрываем линии

		<?php
		foreach($tiplists as $k => $v){
		?>
		myChart.assignColor("<?=$k?>", "<?=$v?>", "<?=$v?>");
		<?php } ?>

		var s = myChart.addSeries(["Тип"], dimple.plot.bar);
		s.lineWeight = 1;
		s.stacked = true;

		myChart.ease = "bounce";
		myChart.staggerDraw = true;

		/*Add prices to line chart*/
		s.afterDraw = function (shape, data) {
			// Get the shape as a d3 selection
			var s = d3.select(shape);
			var i = 0;
			_.forEach(data.points, function (point) {
				var rect = {
					x: parseFloat(point.x),
					y: parseFloat(point.y)
				};
				// Add a text label for the value
				if (data.markerData[i] != undefined) {
					svg.append("text")
						.attr("x", rect.x)
						.attr("y", rect.y - 10)
						// Centre align
						.style("text-anchor", "middle")
						.style("font-size", "10px")
						.style("font-family", "sans-serif")
						// Format the number
						.text(data.markerData[i].y);
				}
				i++
			});
		}

		var myLegend = myChart.addLegend(0, 15, width - 35, 0, "right");
		myChart.setMargins(60, 50, 40, 80);
		myChart.draw(1000);

		$(window).bind('resizeEnd', function () {
			myChart.draw(0, true);
		});

	}

</script>