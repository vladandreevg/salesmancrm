<?php
/* ============================ */
/*         SalesMan CRM         */
/* ============================ */
/* (C) 2016 Vladislav Andreev   */
/*       SalesMan Project       */
/*        www.isaler.ru         */
/*        ver. 2017.x           */
/* ============================ */
?>
<?php
error_reporting(E_ERROR);

$rootpath = realpath( __DIR__.'/../../' );

include $rootpath."/inc/config.php";
include $rootpath."/inc/dbconnector.php";
include $rootpath."/inc/auth.php";
include $rootpath."/inc/func.php";
include $rootpath."/inc/settings.php";
include $rootpath."/inc/language/".$language.".php";

$thisfile = basename( __FILE__ );

$tip = $_REQUEST['tip'];

if ($tip != 'person') {

	$clients_all = $db -> getOne("SELECT COUNT(*) as count FROM ".$sqlname."clientcat WHERE clid > 0 and trash != 'yes' and type IN ('client','person') and identity = '$identity'");

	$clients_otdel = $db -> getOne("SELECT COUNT(*) as count FROM ".$sqlname."clientcat WHERE clid > 0 and trash != 'yes' and type IN ('client','person') ".get_people($iduser1)." and identity = '$identity'");

	$clients_lo = $db -> getOne("SELECT COUNT(*) as count FROM ".$sqlname."clientcat WHERE clid > 0 and trash != 'yes' and type IN ('client','person') and DATE_FORMAT(date_create, '%Y-%m') = '".date('Y')."-".date('m')."' ".get_people($iduser1)." and identity = '$identity'");

	$clients_mo = $db -> getOne("SELECT COUNT(*) as count FROM ".$sqlname."clientcat WHERE clid > 0 and trash != 'yes' and type IN ('client','person') and DATE_FORMAT(date_create, '%Y-%m-%d') ='".current_datum()."' ".get_people($iduser1)." and identity = '$identity'");

	?>
	<table class="smalltxt border-bottom mt10">
		<tr>
			<td class="w120">Показано:</td>
			<td class="text-right"><b id="alls"></b><input type="hidden" name="allSelected" id="allSelected" value="">
			</td>
		</tr>
		<tr>
			<td>В базе:</td>
			<td class="text-right"><b><?= $clients_all ?></b></td>
		</tr>
		<tr>
			<td>В аккаунте:</td>
			<td class="text-right"><b><?= $clients_otdel ?></b></td>
		</tr>
		<tr>
			<td>Новых за месяц:</td>
			<td class="text-right"><b>+ <?= $clients_lo ?></b></td>
		</tr>
		<tr>
			<td>Новых за день:</td>
			<td class="text-right"><b>+ <?= $clients_mo ?></b></td>
		</tr>
	</table>
	<?php
}
else {

	$person_all = $db -> getOne("SELECT COUNT(*) as count FROM ".$sqlname."personcat WHERE pid > 0 and identity = '$identity'");

	$person_otdel = $db -> getOne("SELECT COUNT(*) as count FROM ".$sqlname."personcat WHERE pid > 0 ".get_people($iduser1)." and identity = '$identity'");

	$person_lo = $db -> getOne("SELECT COUNT(*) as count FROM ".$sqlname."personcat WHERE pid > 0 and DATE_FORMAT(date_create, '%Y-%m') = '".date('Y')."-".date('m')."' ".get_people($iduser1)." and identity = '$identity'");

	$person_mo = $db -> getOne("SELECT COUNT(*) as count FROM ".$sqlname."personcat WHERE pid > 0 and DATE_FORMAT(date_create, '%Y-%m-%d') ='".current_datum()."' ".get_people($iduser1)." and identity = '$identity'");

	?>
	<table class="smalltxt border-bottom mt10">
		<tr>
			<td class="w120">Выбрано:</td>
			<td class="text-right">
				<b id="alls"></b>
				<input type="hidden" name="allSelected" id="allSelected" value="">
			</td>
		</tr>
		<tr>
			<td>В базе:</td>
			<td class="text-right"><b><?= $person_all ?></b></td>
		</tr>
		<tr>
			<td>В аккаунте:</td>
			<td class="text-right"><b><?= $person_otdel ?></b></td>
		</tr>
		<tr>
			<td>Новых за месяц:</td>
			<td class="text-right"><b>+ <?= $person_lo ?></b></td>
		</tr>
		<tr>
			<td>Новых за день:</td>
			<td class="text-right"><b>+ <?= $person_mo ?></b></td>
		</tr>
	</table>
<?php } ?>