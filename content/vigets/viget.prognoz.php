<?php
/* ============================ */
/*         SalesMan CRM         */
/* ============================ */
/* (C) 2016 Vladislav Andreev   */
/*       SalesMan Project       */
/*        www.isaler.ru         */
/*        ver. 2016.20          */
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
?>

<table class="border-bottom">
	<thead class="graybg-sub">
	<tr class="gray2">
		<th>Месяц</th>
		<th class="w120">Вес (оборот)</th>
		<th class="w120">План</th>
		<th class="w50">%</th>
	</tr>
	</thead>
	<TBODY>
	<?php
	//Прогноз на текущий месяц
	$mounth = date('m') + 0;
	$year   = date('y');

	for ($i = $mounth; $i < $mounth + 5; $i++) {

		$m = $i;

		if ($i > 12) {
			$m = $i - 12;
			$y = $year + 1;
		}
		else {
			$m = $i;
			$y = $year;
		}

		if ($m < 10) $m = '0'.$m;

		$summa = 0;

		$result = $db -> getAll("SELECT kol, idcategory FROM ".$sqlname."dogovor WHERE did > 0 and COALESCE(close, 'no') != 'yes' and DATE_FORMAT(datum_plan, '%y-%m') = '".$y."-".$m."' ".$sort." and identity = '$identity'");
		foreach ($result as $data) {

			$proc = $db -> getOne("SELECT (title + 0)/100 FROM ".$sqlname."dogcategory WHERE idcategory='".$data['idcategory']."' and identity = '$identity'");

			$summa += pre_format($data['kol']) * $proc;

		}

		$summa += $db -> getOne("SELECT SUM(kol_fact) FROM ".$sqlname."dogovor WHERE did>0 and close = 'yes' and DATE_FORMAT(datum_close, '%y-%m') = '".$y."-".$m."' ".$sort." and identity = '$identity'");

		$plan = 0;
		$delta = 0;

		//Расчет плановых показателей для заданного пользователя
		$y1 = $y + 2000;

		$plan = $db -> getOne("SELECT SUM(kol_plan) FROM ".$sqlname."plan WHERE mon=".$m." and year=".$y1." and iduser='".$iduser1."' and identity = '$identity'");

		if ($plan > 0) $delta = intval($summa / $plan * 100);

		$summa = num_format($summa);
		$plan  = num_format($plan);

		print '
		<tr class="ha hand th30" onclick="showPrognoz(\''.$y.'-'.$m.'\')" title="Показать данные">
			<td><span>'.$y1.': <b>'.ru_mon($m).'</b></span></td>
			<td class="text-right"><span>'.$summa.'&nbsp;'.$valuta.'</span></td>
			<td class="text-right"><span>'.$plan.'&nbsp;'.$valuta.'</span></td>
			<td class="text-right"><span>'.$delta.'&nbsp;%</span></td>
		<tr>';

		$summa = 0;
		$plan  = 0;
	}
	?>
	</TBODY>
</table>

<div class="pull-aright gray2 em fs-09 pt10">Прогноз составлен с учетом текущих стадий активных сделок и успешно закрытых сделок.</div>

<script>
	function showPrognoz(datum) {
		doLoad('/content/vigets/viget.dataview.php?action=prognozView&datum=' + datum);
	}
</script>