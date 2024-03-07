<?php
/* ============================ */
/*         SalesMan CRM         */
/* ============================ */
/* (C) 2016 Vladislav Andreev   */
/*       SalesMan Project       */
/*        www.isaler.ru         */
/*        ver. 2017.x           */
/* ============================ */

error_reporting(0);
header("Pragma: no-cache");

$rootpath = dirname(__DIR__, 2);

include $rootpath."/inc/config.php";
include $rootpath."/inc/dbconnector.php";
include $rootpath."/inc/auth.php";
include $rootpath."/inc/func.php";
include $rootpath."/inc/settings.php";

$thisfile = basename( __FILE__ );

$iduser = $_REQUEST['iduser'];
$action = $_REQUEST['action'];

if(empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) != 'xmlhttprequest') {
	print '<div class="bad" align="center"><br>Доступ запрещен.<br>Обратитесь к администратору.<br><br></div>';
	exit();
}

$res = $db -> getRow("SELECT * FROM ".$sqlname."user WHERE iduser = '$iduser' and identity = '$identity'");
$title     = $res["title"];
$tip       = $res["tip"];
$otdel     = (int)$res["otdel"];
$mid       = (int)$res["mid"];
$territory = (int)$res["territory"];
$office    = $res["office"];
$phone     = $res["phone"];
$phone_in  = $res["phone_in"];
$fax       = $res["fax"];
$mob       = $res["mob"];
$mail_url  = $res["email"];
$bday      = $res["bday"];
$user_post = $res["user_post"];
$CompStart = $res["CompStart"];
$CompEnd   = $res["CompEnd"];
$secrty    = $res["secrty"];
$avatarc   = $res["avatar"];

if($avatarc == '') {
	$avatarc = '/assets/images/noavatar.png';
}
else {
	$avatarc = "/cash/avatars/".$avatarc;
}

$rukov = $db -> getOne("select title from ".$sqlname."user where iduser='".$mid."' and identity = '$identity'");

if($secrty=='no'){
	$ig = '<i class="icon-flag red" title="Доступ Запрещен"></i>';
}
?>
<DIV class="zagolovok">Сотрудник: <b><?=$title?></b> <?=$ig?></DIV>
<div style="float: left; width: 130px;" class="paddleft10">
	<div class="avatarbig" style="background: url(<?=$avatarc?>); background-size:cover;"></div>
</div>
<div style="float: left; width: 440px;" class="inline">
	<table>
	<tr>
		<td width="130"><div class="fnameForm">Роль:</div></td>
		<td><div class="fpole"><b><?=$tip?></b></div></td>
	</tr>
	<?php
	if ($bday!='0000-00-00'){?>
	<tr>
		<td><div class="fnameForm">День рождения:</div></td>
		<td><div class="fpole"><b class="green"><i class="icon-gift green"></i><?=format_date_rus_name($bday)?></b></div></td>
	</tr>
	<?php
	}
	if ($CompStart!='0000-00-00'){?>
	<tr>
		<td><div class="fnameForm">Дата приема:</div></td>
		<td><b class="blue"><?=format_date_rus($CompStart)?></b></td>
	</tr>
	<?php
	}
	if ($CompEnd!='0000-00-00'){?>
	<tr>
		<td><div class="fnameForm">Дата увольнения:</div></td>
		<td><b class="red"><?=format_date_rus($CompEnd)?></b></td>
	</tr>
	<?php
	}
	if ($user_post!='') {?>
	<tr>
		<td><div class="fnameForm">Должность:</div></td>
		<td><b><?=$user_post?></b>&nbsp;</td>
	</tr>
	<?php } ?>
	<?php if($otdel!=""){
		$otdel = $db -> getOne("SELECT title FROM ".$sqlname."otdel_cat WHERE idcategory='".$otdel."' and identity = '$identity'");
	?>
	<tr>
		<td><div class="fnameForm">Отдел:</div></td>
		<td><?=$otdel?></td>
	</tr>
	<? }?>
	<?php if ($rukov!="") {?>
	<tr>
		<td><div class="fnameForm">Руководитель:</div></td>
		<td><?=$rukov?></td>
	</tr>
	<? } ?>
	<?php if($office>"0") {
		$office = $db -> getOne("SELECT title FROM ".$sqlname."office_cat WHERE idcategory='".$office."' and identity = '$identity'");
	?>
	<tr>
		<td><div class="fnameForm">Офис:</div></td>
		<td><?=$office?></td>
	</tr>
	<? } ?>
	<?php if ($territory>0) {
		$territory = $db -> getOne("SELECT title FROM ".$sqlname."territory_cat WHERE idcategory='".$territory."' and identity = '$identity'");
	?>
	<tr>
		<td><div class="fnameForm">Территория:</div></td>
		<td><b class="green"><?=$territory?></b>&nbsp;</td>
	</tr>
	<? } ?>
	<tr>
		<td colspan="2"><hr></td>
	</tr>
	<?php if ($phone!="") {?>
	<tr>
		<td><div class="fnameForm">Телефон:</div></td>
		<td><?=$phone?>&nbsp;</td>
	</tr>
	<? } ?>
	<?php if ($fax!="") {?>
	<tr>
		<td><div class="fnameForm">Факс:</div></td>
		<td><?=$fax?>&nbsp;</td>
	</tr>
	<? } ?>
	<?php if ($mob!="") {?>
	<tr>
		<td><div class="fnameForm">Мобильный:</div></td>
		<td><?=$mob?></td>
	</tr>
	<? } ?>
	<?php if ($phone_in!="") {?>
	<tr>
		<td><div class="fnameForm">Внутр.номер:</div></td>
		<td><?=$phone_in?></td>
	</tr>
	<? } ?>
	<?php if ($mail_url!="") {?>
	<tr>
		<td><div class="fnameForm">Email:</div></td>
		<td><a href="mailto:<?=str_replace("mailto:","", $mail_url)?>"><?=str_replace("mailto:","", $mail_url)?></a></td>
	</tr>
	<? } ?>
	</table>
</div>
<script>
	$('#dialog').css('width','600px');

	$( function() {

		$('#dialog').center();
	});
</script>