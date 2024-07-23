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

$thisfile = basename(__FILE__);

global $userRights;

$did    = (int)$_REQUEST['did'];
$action = $_REQUEST['action'];

$deal    = $db -> getRow("SELECT clid, close FROM {$sqlname}dogovor WHERE did = '$did' and identity = '$identity'");
$close   = $deal['close'];
$clid    = (int)$deal['clid'];
$stringContractor = $stingPartner = '';

$result = $db -> getAll("
	SELECT
		dp.id,
		dp.bid,
		dp.conid,
		dp.partid,
		dp.summa,
		dp.recal,
		bj.do,
		bj.invoice,
		bj.invoice_date,
		bj.date_plan,
		us.title as user
	FROM {$sqlname}dogprovider `dp`
		LEFT JOIN {$sqlname}budjet `bj` ON bj.id = dp.bid
		LEFT JOIN {$sqlname}user `us` ON bj.iduser = us.iduser
	WHERE 
	    dp.did = '$did' AND 
	    (dp.conid > 0 OR dp.partid > 0) AND 
	    dp.identity = '$identity' 
	ORDER BY dp.id
");

//print_r($result);

if (!empty($result)) {

	$i = 1;
	foreach ($result as $dataa) {

		$bjid = (int)$dataa['bid'];
		$bjdo = $dataa['do'];

		$btn = '';
		$status = '';
		$xb = false;

		$providerid = (int)$dataa['conid'] > 0 ? (int)$dataa['conid'] : (int)$dataa['partid'];
		$provider = current_client($providerid);
		$providerType = (int)$dataa['conid'] > 0 ? 'contractor' : 'partner';

		if ($userRights['budjet']) {

			if ($bjid > 0) {
				$btn .= '<a href="javascript:void(0)" onclick="editBudjet(\''.$bjid.'\',\'view\')" title="Просмотр расхода"><i class="icon-rouble broun"></i></a>&nbsp;&nbsp;';
				$xb = true;
			}

			if ($bjid == 0) {

				$btn .= '
					<a href="javascript:void(0)" onclick="editProvider(\''.$dataa['id'].'\',\'addprovider\',\''.$did.'\',\''.$providerid.'\')" title="Добавить расход в бюджет"><i class="icon-box broun"><i class="icon-plus-circled red sup"></i></i></a>&nbsp;&nbsp;
					<a href="javascript:void(0)" onclick="editProviderDeal(\'edit\',\''.$dataa['id'].'\',\''.$providerType.'\',\''.$did.'\')" title="Изменить"><i class="icon-pencil blue"></i></a>&nbsp;&nbsp;
				';
				$status = 'Расход не добавлен в бюджет';

			}
			else {
				$btn .= ( $bjdo == 'on' ) ? '<i class="icon-ok green list" title="Расход проведен"></i>&nbsp;&nbsp;' : '<i class="icon-clock blue list" title="Расход занесен в бюджет, но не проведен"></i>&nbsp;&nbsp;';
				$status = ( $bjdo == 'on' ) ? "Расход проведен" : 'Расход занесен в бюджет, но не проведен';
			}

		}

		if ($settingsMore['budjetProviderPlus'] == 'yes') {

			$tip = (int)$dataa['conid'] > 0  ? 'contractor' : 'partner';

			if($xb) {

				$btn .= '<a href="javascript:void(0)" onclick="editBudjet(\''.$bjid.'\',\'edit\',{did:'.$did.',clid:'.$clid.',tip:\''.$tip.'\'})" title="Редактировать"><i class="icon-pencil blue"></i></a>&nbsp;&nbsp;';

			}

		}

		$btn .= '<a href="javascript:void(0)" onclick="cf=confirm(\'Вы действительно хотите удалить запись?\');if (cf)editProviderDeal(\'delete\',\''.$dataa['id'].'\',\'\',\''.$did.'\')" title="Удалить"><i class="icon-cancel-circled red"></i></a>';

		$string = '
		<tr class="ha th40 '.( $bjdo != 'on' ? 'graybg-sub gray2' : '' ).'">
			<td class="text-center">'.$i.'</td>
			<td>
				<div class="Bold fs-11"><a href="javascript:void(0)" onclick="openClient('.$providerid.', \'cbudjet\')" title="">'.$provider.'</a></div>
				<div class="fs-09 blue mt5">'.$status.'</div>
				'.(!empty($dataa['date_plan']) ? '<div class="fs-09 red mt5">Срок оплаты: '.format_date_rus($dataa['date_plan']).' ( '.diffDate2($dataa['date_plan']).' дн. )</div>' : '').'
				'.(!empty($dataa['user']) ? '<div class="fs-09 gray mt5 text-right"><i class="icon-user-1"></i>'.$dataa['user'].'</div>' : '').'
			</td>
			<td class="text-right">
				<div class="Bold fs-11">'.$dataa['invoice'].'</div>
				'.(!empty($dataa['invoice_date']) ? '<div class="fs-09 gray mt5">от '.format_date_rus($dataa['invoice_date']).'</div>' : '-/-').'
			</td>
			<td class="text-right">
				<div class="Bold">'.num_format($dataa['summa']).'</div>
				'.( $dataa['recal'] == 0 ? '<div class="fs-07 blue">уменьшает прибыль</div>' : '' ).'
			</td>
			<td class="text-center">'.$btn.'</td>
		</tr>';

		if( (int)$dataa['conid'] > 0 ){
			$stringContractor .= $string;
		}
		else{
			$stingPartner .= $string;
		}

		$i++;

	}

}
?>

<div class="fcontainer1">

	<table class="bgwhite top">
		<thead>
		<tr class="th40 header_contaner">
			<th class="w20 text-center">№</th>
			<th class="text-center">Поставщик</th>
			<th class="w130 text-center">Счет</th>
			<th class="w130 text-center">Сумма, <?= $valuta ?></th>
			<th class="w120 text-center">&nbsp;</th>
		</tr>
		</thead>
		<?php
		print ( $stringContractor != '' ) ? $stringContractor : '<tr><td colspan="4" class="gray fs-09">Поставщики не определены</td></tr>';
		?>
	</table>

	<?php
	if ($close != 'yes' && get_accesse(0, 0, (int)$did) == "yes") {
		if ($settingsMore['budjetProviderPlus'] != 'yes') {
			print '<div class="mt10"><a href="javascript:void(0)" onclick="editProviderDeal(\'add\',\'\',\'contractor\',\''.$did.'\')" class="button"><i class="icon-plus-circled"></i>Добавить</a></div>';
		}
		else {
			print '<div class="mt10"><a href="javascript:void(0)" onclick="editBudjet(\'0\',\'edit\',{did:'.$did.',clid:'.$clid.',tip:\'contractor\'})" class="button"><i class="icon-plus-circled"></i>Добавить</a></div>';
		}
	}
	?>

</div>

<div class="fcontainer1 mt20">

	<table class="bgwhite top">
		<thead>
		<tr class="th40 header_contaner">
			<th class="w20 text-center">№</th>
			<th class="text-center">Партнер</th>
			<th class="w130 text-center">Счет</th>
			<th class="w130 text-center">Сумма, <?= $valuta ?></th>
			<th class="w120 text-center">&nbsp;</th>
		</tr>
		</thead>
		<?php
		print ( $stingPartner != '' ) ? $stingPartner : '<tr><td colspan="4" class="gray fs-09">Партнеры не определены</td></tr>';
		?>
	</table>

	<?php
	if ($close != 'yes' && get_accesse(0, 0, (int)$did) == "yes") {
		if ($settingsMore['budjetProviderPlus'] != 'yes') {
			print '<div class="mt10"><a href="javascript:void(0)" onClick="editProviderDeal(\'add\',\'\',\'partner\',\''.$did.'\')" class="button"><i class="icon-plus-circled"></i>Добавить</a></div>';
		}
		else {
			print '<div class="mt10"><a href="javascript:void(0)" onclick="editBudjet(\'0\',\'edit\',{did:'.$did.',clid:'.$clid.',tip:\'partner\'})" class="button"><i class="icon-plus-circled"></i>Добавить</a></div>';
		}
	}
	?>

</div>