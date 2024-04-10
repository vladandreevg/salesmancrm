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

$id = (int)$_REQUEST['clid'];

$ctip = getClientData($id, 'type');

$sort = '';

if( $userSettings['dostup']['budjet']['onlyself'] == 'yes' ){
	$sort .= "bj.iduser IN (".yimplode(",", get_people( $iduser1, 'yes' )).") AND";
}

$list = [];

$query = "
	SELECT
		dp.id,
		dp.did,
		dp.summa,
		dp.bid,
		bj.datum, 
		COALESCE(bj.do, 'off'), 
		bj.rs,
		bj.invoice,
		bj.invoice_date,
		bj.invoice_paydate as dfact,
		bj.date_plan,
		recv.title as rstitle,
		mc.name_shot as company,
		cc.clid as clid,
		cc.title as client,
		deal.title as deal
	FROM {$sqlname}dogprovider `dp`
		LEFT JOIN {$sqlname}budjet `bj` ON dp.bid = bj.id
		LEFT JOIN {$sqlname}mycomps_recv `recv` ON recv.id = bj.rs
		LEFT JOIN {$sqlname}mycomps `mc` ON mc.id = recv.cid
		LEFT JOIN {$sqlname}dogovor `deal` ON dp.did = deal.did
		LEFT JOIN {$sqlname}clientcat `cc` ON cc.clid = deal.clid
	WHERE 
		$sort
		( dp.conid = '$id' OR dp.partid = '$id' ) AND 
		( 
			COALESCE(bj.do, 'off') = 'off' OR 
			( COALESCE(bj.do, 'off') = 'on' AND DATE(bj.datum) > DATE( NOW() ) - INTERVAL 1 MONTH )
		) AND
		dp.identity = '$identity' 
	ORDER BY bj.date_plan DESC, dp.id
";

$result = $db -> query($query);
while ($data = $db -> fetch($result)) {

	$do = ( $data['do'] == 'on' ) ? 1 : 0;

	$list[] = [
		"id"           => (int)$data['id'],
		"bid"          => (int)$data['bid'],
		"invoice"      => $data['invoice'],
		"dfact"        => $data['dfact'],
		"invoice_date" => $data['invoice_date'],
		"date_plan"    => $data['date_plan'],
		"datum"        => $data['datum'],
		"company"      => $data['company'],
		"rstitle"      => $data['rstitle'],
		"summa"        => (float)$data['summa'],
		"do"           => $data['do'],
		"did"          => (int)$data['did'],
		"deal"         => $data['deal'],
		"clid"         => (int)$data['clid'],
		"client"       => $data['client'],
	];

}

//print_r($list);

$str1 = $str2 = '';

foreach ($list as $item) {

	$deal = ( (int)$item['did'] > 0 ) ? '<a href="javascript:void(0)" onClick="viewDogovor(\''.$item['did'].'\')" title="Просмотр"><i class="icon-briefcase-1 broun"></i> '.$item['deal'].'</a>' : '<span class="gray">Не привязано к сделке</span>';

	$client = ( (int)$item['clid'] > 0 ) ? '<a href="javascript:void(0)" onClick="viewClient(\''.$item['clid'].'\')" title="Просмотр"><i class="icon-building broun"></i> '.$item['client'].'</a>' : '-';

	if ($item['do'] != 'on') {

		$icon = '';
		$bgcolor = '';

		if ($item['bid'] == 0) {

			$icon = '<a href="javascript:void(0)" onclick="editProvider(\''.$item['id'].'\',\'addprovider\',\''.$item['did'].'\',\''.$id.'\')" title="Добавить расход в бюджет"><i class="icon-box broun"><i class="icon-plus-circled red sup"></i></i></a>&nbsp;&nbsp;<a href="javascript:void(0)" onclick="editProviderDeal(\'edit\',\''.$item['id'].'\',\'contractor\',\''.$item['did'].'\')" title="Изменить"><i class="icon-pencil blue"></i></a>&nbsp;&nbsp;';
			$bgcolor = 'redbg-sub';

		}
		elseif ($item['do'] == 'on') {
			$icon .= '<i class="icon-ok green list" title="Расход проведен"></i>&nbsp;&nbsp;<a href="javascript:void(0)" onclick="editBudjet(\''.$item['bid'].'\',\'view\')" title="Просмотр"><i class="icon-eye green"></i></a>&nbsp;&nbsp;';
			$bgcolor = 'greenbg-sub';
		}
		else {
			$icon .= '<i class="icon-clock blue list" title="Расход занесен в бюджет, но не проведен"></i>&nbsp;&nbsp;<a href="javascript:void(0)" onclick="editBudjet(\''.$item['bid'].'\',\'view\')" title="Просмотр"><i class="icon-eye green"></i></a>&nbsp;&nbsp;<a href="javascript:void(0)" onclick="editBudjet(\''.$item['bid'].'\',\'edit\')" title="Изменить"><i class="icon-pencil blue"></i></a>&nbsp;&nbsp;';
			//$bgcolor = 'brounbg-sub';
		}

		$str1 .= '
			<tr class="ha '.$bgcolor.'">
				<td class="text-center">'.get_sfdate2($item['dfact']).'</td>
				<td class="text-center">'.get_sfdate2($item['date_plan']).'</td>
				<td>
					'.(!empty($item['invoice']) ? '
					<div class="Bold fs-11">Счет №'.$item['invoice'].'</div>
					<div class="fs-09 blue">от '.$item['invoice_date'].'</div>' : '--').'
				</td>
				<td class="text-right">'.num_format($item['summa']).'</td>
				<td>
					<div class="ellipsis">'.$deal.'</div><br>
					<div class="ellipsis">'.$client.'</div>
				</td>
				<td>
					'.($userRights['budjet'] ? $icon : '').'
					<a href="javascript:void(0)" onclick="cf=confirm(\'Вы действительно хотите удалить запись?\');if (cf)editProviderDeal(\'delete\',\''.$item['id'].'\',\'\',\''.$item['did'].'\')" title="Удалить"><i class="icon-cancel-circled red"></i></a></td>
				</td>
			</tr>
		';

	}
	else {
		$str2 .= '
		<tr class="ha hand greenbg-sub" onclick="editBudjet(\''.$item['bid'].'\',\'view\')">
			<td class="text-center">'.get_sfdate2($item['datum']).'</td>
			<td class="text-center">'.( !empty($item['date_plan']) ? get_sfdate2($item['date_plan']) : '--').'</td>
			<td>
				'.(!empty($item['invoice']) ? '
				<div class="Bold fs-11">Счет №'.$item['invoice'].'</div>
				<div class="fs-09 blue">от '.$item['invoice_date'].'</div>' : '--').'
			</td>
			<td class="text-right">'.num_format($item['summa']).'</td>
			<td>
					<div class="ellipsis">'.$deal.'</div><br>
					<div class="ellipsis">'.$client.'</div>
				</td>
			<td></td>
		</tr>
	';
	}

}
?>

<div class="viewdiv mb10">
	Список расходов, привязанных к поставщику
</div>

<table id="rowtable1">
	<thead>
	<tr>
		<th class="w100">Факт.дата</th>
		<th class="w100">Срок оплаты</th>
		<th class="w100">Счет</th>
		<th class="w100">Сумма</th>
		<th class="">Сделка / Клиент</th>
		<th class="w140"></th>
	</tr>
	</thead>
	<tbody>
	<?= $str1 ?>
	<?= $str2 ?>
	</tbody>
</table>

<?php
if ($settingsMore['budjetProviderPlus'] != 'yes') {
	print '<div class="mt10 sticked--bottom"><a href="javascript:void(0)" onclick="editProviderDeal(\'add\',\'\',\''.$ctip.'\',\'0\')" class="button"><i class="icon-plus-circled"></i>Добавить</a></div>';
}
else {
	print '<div class="mt10 sticked--bottom"><a href="javascript:void(0)" onclick="editBudjet(\'0\',\'edit\',{clid:'.$id.',tip:\''.$ctip.'\',did:0,agent:'.$id.',tip:\''.$ctip.'\'})" class="button"><i class="icon-plus-circled"></i>Добавить</a></div>';
}
?>

<div class="attention mt10">Отображены не проведенные расходы и проведенные не старше 1 месяца назад</div>