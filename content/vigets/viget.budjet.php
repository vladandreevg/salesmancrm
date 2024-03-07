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

$rootpath = dirname( __DIR__, 2 );

include $rootpath."/inc/config.php";
include $rootpath."/inc/dbconnector.php";
include $rootpath."/inc/auth.php";
include $rootpath."/inc/func.php";
include $rootpath."/inc/settings.php";
include $rootpath."/inc/language/".$language.".php";

$thisfile = basename( __FILE__ );

$sort = get_people($iduser1);
$budo = $bunot = [];

function cmp($a, $b) { return $a['datum'] - $b['datum']; }

$query = "
SELECT
	{$sqlname}budjet.id as id,
	{$sqlname}budjet.title as title,
	{$sqlname}budjet.summa as summa,
	{$sqlname}budjet.do as do,
	{$sqlname}budjet_cat.title as ctitle,
	{$sqlname}budjet_cat.tip as ctip
FROM {$sqlname}budjet
	LEFT JOIN {$sqlname}budjet_cat ON {$sqlname}budjet.cat = {$sqlname}budjet_cat.id
WHERE
	{$sqlname}budjet.year = '".date('Y')."' and {$sqlname}budjet.mon = '".date('m')."' and
	{$sqlname}budjet.cat != '0' and
	{$sqlname}budjet.identity = '$identity'
ORDER BY {$sqlname}budjet.datum";

$result = $db -> query($query);
while ($data = $db -> fetch($result)) {

	$tip = '';

	if ($data['ctip'] == 'dohod')
		$tip = '<b class="green" title="Поступление"><i class="icon-up-big green"></i> Поступление</b>';

	elseif ($data['ctip'] == 'rashod')
		$tip = '<b class="red" title="Расход"><i class="icon-down-big red"></i> Расход</b>';

	if ($data['do'] == 'on')
		$budo[] = [
			"id"     => $data['id'],
			"tip"    => $tip,
			"title"  => $data['title'],
			"summa"  => num_format($data['summa']),
			"ctitle" => $data['ctitle'],
			"datum"  => $data['datum']
		];

	else
		$bunot[] = [
			"id"     => $data['id'],
			"tip"    => $tip,
			"title"  => $data['title'],
			"summa"  => num_format($data['summa']),
			"ctitle" => $data['ctitle'],
			"datum"  => $data['datum']
		];

}

print '
	<div class="flex-container p10 graybg-sub red Bold uppercase">

		<div class="flex-string">
			Не проведенные
		</div>

	</div>';

//пересортируем массив
usort($bunot, "cmp");

foreach ($bunot as $bu) {

	print '
		<div class="flex-container float border-bottom p10 ha hand" onClick="editBudjet(\''.$bu['id'].'\',\'view\')" title="Просмотр">
			
			<div class="flex-string float">
				<span class="ellipsis fs-12 Bold blue">'.$bu['title'].'</span><br>
				<span class="ellipsis gray2">'.$bu['ctitle'].'</span>
			</div>
			<div class="flex-string w40">
				<div class="gray" onClick="editBudjet(\''.$bu['id'].'\',\'edit\')"><i class="icon-plus-circled broun clearevents"></i></div>
			</div>
			<div class="flex-string w100">
				<div class="fs-12 Bold">'.$bu['summa'].'</div>
				<div class="fs-09 gray">'.$bu['tip'].'</div>
			</div>
			
		</div>
		';

}

if (empty($bunot)) {
	print '
		<div class="flex-container p10 gray">
	
			<div class="flex-string">
				Нет планируемых
			</div>
	
		</div>
		';
}

print '
	<div class="flex-container p10 graybg-sub green Bold uppercase">

		<div class="flex-string">
			Проведенные
		</div>

	</div>';

//пересортируем массив
usort($budo, "cmp");

foreach ($budo as $bu) {

	print '
		<div class="flex-container float border-bottom p10 ha hand" onClick="editBudjet(\''.$bu['id'].'\',\'view\')" title="Просмотр">
			
			<div class="flex-string float">
				<span class="ellipsis fs-12 Bold blue">'.$bu['title'].'</span><br>
				<span class="ellipsis gray2">'.$bu['ctitle'].'</span>
			</div>
			<div class="flex-string w100">
				<div class="fs-12 Bold">'.$bu['summa'].'</div>
				<div class="fs-09 gray">'.$bu['tip'].'</div>
			</div>
			
		</div>
		';
}

if (empty($budo)) {
	print '
		<div class="flex-container p10 gray">
	
			<div class="flex-string">
				Нет планируемых
			</div>
	
		</div>
		';
}
