<?php
/* ============================ */
/*         SalesMan CRM         */
/* ============================ */
/* (C) 2020 Vladislav Andreev   */
/*       SalesMan Project       */
/*        www.isaler.ru         */
/*        ver. 2020.x           */
/* ============================ */
error_reporting( E_ERROR );
ini_set( 'display_errors', 1 );
header( "Pragma: no-cache" );

$rootpath = realpath( __DIR__.'/../' );

include $rootpath."/inc/config.php";
include $rootpath."/inc/dbconnector.php";
include $rootpath."/inc/auth.php";
include $rootpath."/inc/func.php";
include $rootpath."/inc/settings.php";
include $rootpath."/inc/language/".$language.".php";

$action = $_REQUEST['action'];
$da1    = $_REQUEST['da1'];
$da2    = $_REQUEST['da2'];
$da     = $_REQUEST['da'];
$act    = $_REQUEST['act'];
$per    = $_REQUEST['per'];
$did    = (int)$_REQUEST['did'];

if (!$per) $per = 'nedelya';

$user_list    = (array)$_REQUEST['user_list'];
$clients_list = (array)$_REQUEST['clients_list'];
$persons_list = (array)$_REQUEST['persons_list'];
$fields       = (array)$_REQUEST['field'];
$field_query  = (array)$_REQUEST['field_query'];

//массив пользователей
$user_list = (!empty( $user_list )) ? $user_list : (array)get_people( $iduser1, "yes" );
if (!empty($user_list)) {
	$sort .= " deal.iduser IN (".yimplode( ",", $user_list ).") AND ";
}

//составляем запрос по клиентам и персонам
if ( !empty($clients_list) && !empty($persons_list) ) {
	$sort .= "(deal.clid IN (".yimplode( ",", $clients_list).") OR deal.pid IN (".yimplode( ",", $persons_list ).")) AND ";
}
elseif ( !empty($clients_list) ) {
	$sort .= "deal.clid IN (".yimplode( ",", $clients_list).") AND ";
}
elseif ( !empty($persons_list) ) {
	$sort .= "deal.pid IN (".yimplode( ",", $persons_list ).") AND ";
}

//составляем запрос по параметрам сделок
$ar = [
	'sid','close'
];
foreach ( $fields as $i => $field ) {

	if ( !in_array( $field, $ar ) && !in_array( $field, [
			'close',
			'mcid'
		] ) ) {
		$sort .= " deal.".$field." = '".$field_query[ $i ]."' AND ";
	}
	elseif ( $field == 'close' ) {
		$sort .= $field_query[ $i ] != 'yes' ? " COALESCE(deal.{$field}, 'no') != 'yes' AND " : " COALESCE(deal.{$field}, 'no') == 'yes' AND ";
	}
	elseif ( $field == 'mcid' ) {
		$mc = $field_query[ $i ];
	}

}

$nd      = current_datum();
$nd_unix = date_to_unix($nd);

$first_step1 = $db -> getOne("SELECT idcategory FROM ".$sqlname."dogcategory WHERE title = '0' and identity = '$identity'");

$first_step2 = $db -> getOne("SELECT idcategory FROM ".$sqlname."dogcategory WHERE title = '100' and identity = '$identity'");

$i = 0;

$kolSum_fact = $kolSum = $kolMarg = 0;

$da = $db -> getAll("SELECT * FROM ".$sqlname."dogovor `deal` WHERE deal.did > 0 and deal.idcategory != '$first_step1' and deal.idcategory != '$first_step2' and $sort deal.close != 'yes' and deal.identity = '$identity' ORDER BY deal.datum_plan");
foreach ($da as $data) {

	$summa = 0;
	$datum_min = 0;
	$prim = $color = '';

	$client = ($data['clid'] > 0) ? current_client($data['clid']) : current_person($data['pid']);

	$datum     = format_date_rus($data['datum']);//сдесь дата создания сделки
	$datum_min = $data['datum_plan']; //задаем начальную минимальную дату как плановую дату сделки

	if ($complect_on == 'yes') {

		$dmin = $db -> getOne("SELECT MIN(data_plan) as min FROM ".$sqlname."complect WHERE did = '".$data['did']."' and doit != 'yes' and identity = '$identity'");

		if (date_to_unix($datum_min) > date_to_unix($dmin)) $datum_min = $dmin;

	}

	//Сформируем сумму оплаченных счетов
	$resultc = $db -> query("SELECT * FROM ".$sqlname."credit WHERE did = '".$data['did']."' and identity = '$identity'");
	while ($datac = $db -> fetch($resultc)) {

		if ($datac['do'] == 'on') $summa += $datac['summa_credit'];
		else {

			if (date_to_unix($datum_min) > date_to_unix($datac['datum_credit']) and $datac['datum_credit'] != '0000-00-00') $datum_min = $datac['datum_credit'];
			if (date_to_unix($datum_min) > date_to_unix($datac['invoice_date']) and $datac['invoice_date'] != '0000-00-00') $datum_min = $datac['invoice_date'];

		}

	}

	$day = round(diffDate2($datum_min));

	$kol      = $data['kol'];
	$marga    = $data['marga'];
	$kol_fact = $data['kol_fact'];

	$kolSum += $kol;
	$kolMarg += $marga;
	$kolSum_fact += $kol_fact;


	//цветовая схема
	if ($data['close'] == 'yes' && $kol_fact > 0) $color = 'greenbg-sub';
	if ($data['close'] == 'yes' && $kol_fact == 0) $color = 'redbg-sub';

	//Сформируем записи активностей, последние 3
	for ($k=0;$k<3;$k++){

		$j = $k + 1;
		$rh = $db->getRow("select * from ".$sqlname."history WHERE did='".$data['did']."' and tip != 'СобытиеCRM' and datum between '".$da1." 00:00:00' and '".$da2." 23:59:59' and identity = '$identity' ORDER BY cid DESC LIMIT $k, $j");
		$datum = format_date_rus(cut_date_short($rh["datum"]));
		$tip = $rh["tip"];
		$hdes = $rh["des"];

		if($datum!='01.01.1970') $prim.= str_replace(";", ",", '<b>'.$datum.'</b>: '.$tip.', '.$hdes.' <br>');
		if($data['close']=='yes') $dfact = format_date_rus($data['datum_close']);
		else $dfact = '';

	}

	//Последнее движение по сделке
	$md      = $db -> getOne("SELECT MAX(datum) as datum FROM ".$sqlname."steplog WHERE did = '".$data['did']."' and identity = '$identity'");
	$stepDay = ($md != '') ? abs(round(diffDate2($md))) : abs(round(diffDate2($data['datum'])));

	//Здоровье сделки. конец.
	$dogs[] = array(
		"did"          => $data['did'],
		"step"         => current_dogstep($data['did']),
		"stepDay"      => $stepDay,
		"datum"        => $data['datum'],
		"datum_plan"   => $data['datum_plan'],
		"datum_fact"   => ($data['close'] == 'yes') ? format_date_rus($data['datum_close']) : '',
		"client"       => $client,
		"clid"         => $data['clid'],
		"pid"          => $data['pid'],
		"clientpath"   => current_clientpath($data['clid']),
		"zayavka"      => $data['zayavka'],
		"title"        => $data['title'],
		"des"          => $prim,
		"kol_fact"     => $kol_fact,
		"kol"          => $kol,
		"summa"        => $summa,
		"url"          => $url,
		"manager"      => current_user($data['iduser']),
		"day"          => $day,
		"color"        => $color,
		"close"        => $data['close'],
		"close_status" => current_dogstatus($data['did'])
	);

	$i++;

}

function cmp($a, $b) { return $b['day'] < $a['day']; }

usort($dogs, 'cmp');

if ($_REQUEST['action'] == "get_csv") {

	$otchet = array("#;Дата создан.;Дата план.;Этап сделки;Заказчик;Источник клиента;Ответств.;Описание;Сумма сделки, р.");

	foreach ($dogs as $i => $dog) {

		$j = $i + 1;

		$client = ($dog['clid'] > 0) ? current_client($dog['clid']) : current_person($dog['pid']);

		$otchet[] = $j.'.;"'.$dog['datum'].'";"'.$dog['datum_plan'].'";"'.$dog['step'].'%";'.$client.';'.$dog['clientpath'].';'.$dog['manager'].';'.preg_replace("/\r\n|\r|\n/u", "", untag($dog['des'])).';"'.pre_format($dog['kol']).'"';
	}

	//создаем файл csv
	$filename = 'export_doganaliz.csv';
	$handle   = fopen("../files/".$filename, 'w');

	for ($g = 0; $g < count($otchet); $g++) {
		$otchet[ $g ] = iconv("UTF-8", "CP1251", str_replace("<br>", "\t", $otchet[ $g ]));
		fwrite($handle, "$otchet[$g]\n");
	}

	fclose($handle);
	header('Content-type: application/csv');
	header('Content-Disposition: attachment; filename="'.$filename.'"');

	readfile("../files/".$filename);
	unlink("../files/".$filename);

	exit();

}
?>

<div class="relativ mt20 mb20 wp95 text-center">
	<h1 class="uppercase fs-14 m0 mb10">Сделки в работе</h1>
	<div class="">Открытые сделки с этапом не равным 0% и 100%</div>
	<div class="gray2">за период&nbsp;с&nbsp;<?= format_date_rus($da1) ?>&nbsp;по&nbsp;<?= format_date_rus($da2) ?></div>
	<div class="mt10 Bold blue"><a href="javascript:void(0)" onClick="generate_csv()">Скачать CSV</a></div>
</div>

<hr>

<TABLE id="zebra">
	<thead>
	<TR class="header_contaner" height="30">
		<td width="20" align="center"><b>#</b></td>
		<td class="yw80" align="center"><b>Дата<br>создан.</b></td>
		<td class="yw80" align="center"><b>Дата<br>план.</b></td>
		<td class="yw60" align="center"><b>Этап сделки</b></td>
		<td class="min100" align="center"><b>Заказчик</b></td>
		<td width="150" align="center"><b>Источник клиента</b></td>
		<td width="80" align="center"><b>Ответств.</b></td>
		<td class="yw160" align="center"><b>Активности</b></td>
		<td width="150" align="center"><b>&sum; сделки, <?= $valuta ?></b></td>
	</TR>
	</thead>
	<?php
	foreach ($dogs as $i => $dog) {
		?>
		<TR class="ha <?= $dog['color'] ?>" height="40px">
			<TD width="2" align="right"><?= $i + 1 ?>.</TD>
			<TD align="center"><span class=""><?= format_date_rus($dog['datum']) ?></span></TD>
			<TD align="center"><span class=""><?= format_date_rus($dog['datum_plan']) ?></span></TD>
			<TD align="right">
				<?= $dog['step'] ?>%
			</TD>
			<TD>
				<div class="ellipsis Bold fs-12">
					<?php if ($dog['clid'] > 0) { ?>
					<A href="javascript:void(0)" onclick="openClient('<?= $dog['clid'] ?>')"><i class="icon-building broun"></i>&nbsp;<?= current_client($dog['clid']) ?></A>
					<?php } else { ?>
					<A href="javascript:void(0)" onclick="openPerson('<?= $dog['pid'] ?>')"><i class="icon-user-1 broun"></i>&nbsp;<?= current_person($dog['pid']) ?></A>
					<?php } ?>
				</div>
				<br>
				<div class="ellipsis mt5">
					<A href="javascript:void(0)" onclick="openDogovor('<?= $dog['did'] ?>')" title="Открыть в новом окне"><i class="icon-briefcase blue"></i>&nbsp;<?= current_dogovor($dog['did']) ?></A>
				</div>
			</TD>
			<TD>
				<div class="ellipsis"><?= $dog['clientpath'] ?></div>
			</TD>
			<TD>
				<div class="ellipsis"><?= $dog['manager'] ?></div>
			</TD>
			<TD>
				<div class="dot-ellipsis" title="<?= html2text($dog['des']) ?>"><?= $dog['des'] ?></div>
			</TD>
			<TD align="right"><?= num_format($dog['kol']) ?></TD>
		</TR>
	<?php } ?>
	<TR bgcolor="#FFCC33" height="28">
		<td align="center"></td>
		<td align="center"></td>
		<td align="center"></td>
		<td align="center"></td>
		<td align="center"></td>
		<td align="center"></td>
		<td align="center"></td>
		<td align="center"></td>
		<td align="right"><strong><?= num_format($kolSum) ?></strong></td>
	</TR>
</TABLE>

<div style="height:90px"></div>

<script src="/assets/js/jquery.liTextLength.js"></script>
<script>
	$(".dot-ellipsis").liTextLength({
		length: 100,
		afterLength: '...',
		fullText: true
	});
</script>