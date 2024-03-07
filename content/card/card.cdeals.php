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
error_reporting( E_ERROR );

header( "Pragma: no-cache" );

$rootpath = realpath( __DIR__.'/../../' );

include $rootpath."/inc/config.php";
include $rootpath."/inc/dbconnector.php";
include $rootpath."/inc/auth.php";
include $rootpath."/inc/func.php";
include $rootpath."/inc/settings.php";
include $rootpath."/inc/language/".$language.".php";

$thisfile = basename( __FILE__ );

$id = (int)$_REQUEST['clid'];

$list = [];

$query = "
	SELECT
		{$sqlname}dogovor.did,
		{$sqlname}dogovor.title as deal,
		{$sqlname}dogovor.kol as summa,
		{$sqlname}dogovor.marga,
		{$sqlname}dogovor.datum as dstart, 
		{$sqlname}dogovor.datum_plan as dplan,
		{$sqlname}dogovor.datum_close as dend,
		{$sqlname}dogovor.close,
		{$sqlname}dogovor.coid1 as concurents,
		{$sqlname}dogovor.coid as winner,
		{$sqlname}clientcat.clid as clid,
		{$sqlname}clientcat.title as client
	FROM {$sqlname}dogovor 
		LEFT JOIN {$sqlname}clientcat ON {$sqlname}clientcat.clid = {$sqlname}dogovor.clid
	WHERE 
		(
			{$sqlname}dogovor.coid = '$id' OR
			FIND_IN_SET('$id', REPLACE({$sqlname}dogovor.coid1, ';',',')) > 0
		) and
		{$sqlname}dogovor.identity = '$identity' 
	ORDER BY {$sqlname}dogovor.datum_plan
";

$result = $db -> query( $query );
while ($data = $db -> fetch( $result )) {

	$do = ($data['do'] != NULL) ? 1 : 0;

	$list[] = [
		"did"        => $data['did'],
		"dstart"     => $data['dstart'],
		"dplan"      => $data['dplan'],
		"dend"       => $data['dend'],
		"deal"       => $data['deal'],
		"clid"       => $data['clid'],
		"client"     => $data['client'],
		"summa"      => $data['summa'],
		"marga"      => $data['marga'],
		"close"      => $data['close'],
		"winner"     => $data['winner'],
		"concurents" => yexplode( ";", $data['concurents'] ),
	];

}

$str1 = $str2 = '';

foreach ( $list as $item ) {

	$deal = ($item['did'] > 0) ? '<a href="javascript:void(0)" onClick="viewDogovor(\''.$item['did'].'\')" title="Просмотр"><i class="icon-briefcase-1 broun"></i> '.$item['deal'].'</a>' : '<span class="gray">Не привязано к сделке</span>';

	$client = ($item['clid'] > 0) ? '<a href="javascript:void(0)" onClick="viewClient(\''.$item['clid'].'\')" title="Просмотр"><i class="icon-building broun"></i> '.$item['client'].'</a>' : '-';

	$icon = '';

	if ( $item['close'] != 'yes' ) {

		$str1 .= '
			<tr class="ha redbg-sub" height="40">
				<td align="center" width="100">'.format_date_rus( $item['dstart'] ).'</td>
				<td align="center" width="100">'.format_date_rus( $item['dplan'] ).'</td>
				<td align="center" width="100">'.format_date_rus( $item['dend'] ).'</td>
				<td align="right" width="100">'.num_format( $item['summa'] ).'</td>
				<td width="250"><div class="ellipsis"><a href="javascript:void(0)" onClick="viewDogovor(\''.$item['did'].'\')" title="Просмотр"><i class="icon-briefcase-1 broun"></i> '.$item['deal'].'</a></div></td>
				<td><div class="ellipsis">'.$client.'</div></td>
			</tr>
		';

	}
	else {

		$icon = ($item['winner'] == $id) ? '<i class="icon-trophy green"></i>' : '<i class="icon-lock red"></i>';

		$str2 .= '
			<tr class="ha hand greenbg-sub" height="40" onclick="editBudjet(\''.$item['bid'].'\',\'view\')">
				<td align="center" width="100">'.format_date_rus( $item['dstart'] ).'</td>
				<td align="center" width="100">'.format_date_rus( $item['dplan'] ).'</td>
				<td align="center" width="100">'.format_date_rus( $item['dend'] ).'</td>
				<td align="right" width="100">'.num_format( $item['summa'] ).'</td>
				<td width="250"><div class="ellipsis"><a href="javascript:void(0)" onClick="viewDogovor(\''.$item['did'].'\')" title="Просмотр">'.$icon.' '.$item['deal'].'</a></div></td>
				<td><div class="ellipsis">'.$client.'</div></td>
			</tr>
		';
	}

}
?>

<div class="viewdiv mb10">
	Список сделок, в которых участвует конкурент
</div>

<table width="100%" border="0" cellpadding="5" cellspacing="0" id="rowtable1">
	<thead>
	<tr>
		<th width="100">Дата.Старт</th>
		<th width="100">Дата.План</th>
		<th width="100">Дата.Факт</th>
		<th width="100">Сумма</th>
		<th width="250">Сделка</th>
		<th class="">Клиент</th>
	</tr>
	</thead>
	<tbody>
	<?= $str1 ?>
	<?= $str2 ?>
	</tbody>
</table>