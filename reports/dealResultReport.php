<?php
/**
 * @license  http://isaler.ru/
 * @author   Vladislav Andreev, http://iandreyev.ru/
 * @charset  UTF-8
 * @version  6.4
 */
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
$dstart = $_REQUEST['dstart'];
$dend   = $_REQUEST['dend'];

$user_list    = (array)$_REQUEST['user_list'];
$clients_list = (array)$_REQUEST['clients_list'];
$persons_list = (array)$_REQUEST['persons_list'];
$fields       = (array)$_REQUEST['field'];
$field_query  = (array)$_REQUEST['field_query'];
$steps        = (array)$_REQUEST['idcategory'];

//Учитываем тип отношений
$tip_cmr = (array)$_REQUEST['tip_cmr'];

//Учитываем источник клиента
$clientpath  = (array)$_REQUEST['clientpath'];

//массив пользователей
$user_list = (!empty( $user_list )) ? $user_list : (array)get_people( $iduser1, "yes" );

$sort  = '';
$sort2 = '';

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

if (!empty($tip_cmr)) {
	$sort .= " cc.tip_cmr IN (".yimplode(",", $tip_cmr, "'").") AND ";
}
if (empty(!$clientpath)) {
	$sort .= " cc.clientpath IN (".yimplode( ",", $clientpath, "'" ).") AND ";
}
if (!empty($user_list)) {
	$sort .= " deal.iduser IN (".yimplode( ",", $user_list ).") AND ";
}

//если указан
if ($dstart != '' && $dend != '') {
	$sort .= " (deal.date_create BETWEEN '$dstart 00:00:01' and '$dend 23:59:59') AND ";
}

//добавляем в запрос выбранные параметры сделок
$ar = [
	'close',
	'idcategory'
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

//этап 100% учитываем всегда
$step = $db -> getOne("SELECT idcategory FROM ".$sqlname."dogcategory WHERE title = '100' and identity = '$identity'");

if (!empty($steps)) {
	$sort .= " deal.idcategory IN (".yimplode(",", $steps).") AND ";
}
else {
	$sort .= " deal.idcategory = '$step' AND ";
	$steps[] = $step;
}

$result = $db -> query("
	SELECT 
		*,
		cc.title as client,
		cc.date_create as datum_client,
		us.title as user,
		dc.title as step,
		dc.content as steptitle,
		cp.name as clientpath,
		rl.title as relations
	FROM ".$sqlname."dogovor `deal`
		LEFT JOIN ".$sqlname."user `us` ON deal.iduser = us.iduser
		LEFT JOIN ".$sqlname."clientcat `cc` ON deal.clid = cc.clid
		LEFT JOIN ".$sqlname."dogcategory `dc` ON deal.idcategory = dc.idcategory
		LEFT JOIN ".$sqlname."clientpath `cp` ON cc.clientpath = cp.id
		LEFT JOIN ".$sqlname."relations `rl` ON cc.tip_cmr = rl.id
	WHERE 
		deal.did > 0 AND 
		deal.clid > 0 AND 
		deal.close = 'yes' AND 
		deal.kol_fact > 0 AND 
		(deal.datum_close BETWEEN '$da1' and '$da2') AND 
		$sort
		deal.identity = '$identity' 
	ORDER BY datum_close
");
//print $db -> lastQuery();
while ($data = $db -> fetch($result)) {

	$rez[] = [
		"did"          => $data['did'],
		"title"        => $data['title'],
		"step"         => $data['step'],
		"datum_create" => format_date_rus($data['datum']),
		"datum_fact"   => format_date_rus($data['datum_close']),
		"kol"          => $data['kol_fact'],
		"marga"        => $data['marga'],
		"close_status" => current_dogstatus($data['did']),
		"clid"         => $data['clid'],
		"datum_client" => $data['datum_client'],
		"clientpath"   => $data['clientpath'],
		"tip_cmr"      => $data['relations'],
		"user"         => $data['user']
	];

}

if ($_REQUEST['action'] == "export") {

	$otchet[0] = "#;Дата создания клиент;Тип отношений;Дата закрытия сделки;Этап сделки;Заказчик;Источник клиента;Сумма сделки;Прибыль;Ответственный";

	for ($i = 0; $i < count($rez); $i++) {

		$g        = $i + 1;
		$otchet[] = $g.";".$rez[ $i ]['datum_client'].";".$rez[ $i ]['tip_cmr'].";".$rez[ $i ]['datum_fact'].";".$rez[ $i ]['step']."%;".current_client($rez[ $i ]['clid']).";".$rez[ $i ]['clientpath'].";".num_format($rez[ $i ]['kol']).";".num_format($rez[ $i ]['marga']).";".$rez[ $i ]['user'];

	}

	//создаем файл csv
	$filename = 'export_doganaliz.csv';
	$handle   = fopen("../files/".$filename, 'w');

	for ($g = 0; $g < count($otchet); $g++) {
		$otchet[ $g ] = iconv("UTF-8", "CP1251", str_replace("<br>", "\t", $otchet[ $g ]));
		fwrite($handle, $otchet[ $g ]."\n");
	}
	fclose($handle);
	header('Content-type: application/csv');
	header('Content-Disposition: attachment; filename="'.$filename.'"');

	readfile("../files/".$filename);
	unlink("../files/".$filename);
	exit();
}
?>

<div class="zagolovok_rep text-center">
	<h1>Закрытые успешные сделки по площадкам</h1>
	<div class="gray2 fs-09">
		с <?= format_date_rus($da1) ?>&nbsp;по&nbsp;<?= format_date_rus($da2) ?> ( <a href="javascript:void(0)" onclick="Export()" style="color:blue">Экспорт</a> )
	</div>
</div>

<table class="noborder">
	<tr>
		<td class="wp25">
			<div class="ydropDown">
				<span>По Источнику клиента</span>
				<span class="ydropCount"><?= count($clientpath) ?> выбрано</span><i class="icon-angle-down pull-aright"></i>
				<div class="yselectBox">
					<div class="yunSelect"><i class="icon-cancel-circled2"></i>Снять выделение</div>
					<div class="ydropString ellipsis">
						<label>
							<input class="taskss" name="clientpath" type="checkbox" id="clientpath" value="0" <?php if (in_array(0, $clientpath)) print 'checked'; ?>>&nbsp;Не указано
						</label>
					</div>
					<?php
					$result = $db -> query("SELECT * FROM ".$sqlname."clientpath WHERE identity = '$identity' ORDER BY name");
					while ($data = $db -> fetch($result)) {
						?>
						<div class="ydropString ellipsis">
							<label>
								<input class="taskss" name="clientpath[]" type="checkbox" id="clientpath[]" value="<?= $data['id'] ?>" <?php if (in_array($data['id'], $clientpath)) print 'checked'; ?>>&nbsp;<?= $data['name'] ?>
							</label>
						</div>
						<?php
					}
					?>
				</div>
			</div>
		</td>
		<td class="wp25">
			<div class="ydropDown">
				<span>По Типу отношений</span>
				<span class="ydropCount"><?= count($tip_cmr) ?> выбрано</span><i class="icon-angle-down pull-aright"></i>
				<div class="yselectBox">
					<div class="yunSelect"><i class="icon-cancel-circled2"></i>Снять выделение</div>
					<div class="ydropString ellipsis">
						<label>
							<input class="taskss" name="tip_cmr[]" type="checkbox" id="tip_cmr[]" value="0" <?php if (in_array("0", $tip_cmr)) print 'checked'; ?>>&nbsp;Не указано
						</label>
					</div>
					<?php
					$result = $db -> query("SELECT * FROM ".$sqlname."relations WHERE identity = '$identity' ORDER BY title");
					while ($data_array = $db -> fetch($result)) {
						?>
						<div class="ydropString ellipsis">
							<label>
								<input class="taskss" name="tip_cmr[]" type="checkbox" id="tip_cmr[]" value="<?= $data_array['title'] ?>" <?php if (in_array($data_array['title'], $tip_cmr)) print 'checked'; ?>>&nbsp;<?= $data_array['title'] ?>
							</label>
						</div>
					<?php } ?>
				</div>
			</div>
		</td>
		<td class="wp25">
			<div class="ydropDown">
				<span>По Этапу</span>
				<span class="ydropCount"><?= count($steps) ?> выбрано</span><i class="icon-angle-down pull-aright"></i>
				<div class="yselectBox">
					<div class="yunSelect"><i class="icon-cancel-circled2"></i>Снять выделение</div>
					<?php
					$result = $db -> query("SELECT * FROM ".$sqlname."dogcategory WHERE identity = '$identity' ORDER BY title");
					while ($data = $db -> fetch($result)) {
						?>
						<div class="ydropString ellipsis">
							<label>
								<input class="taskss" name="idcategory[]" type="checkbox" id="idcategory[]" value="<?= $data['idcategory'] ?>" <?php if (in_array($data['idcategory'], $steps)) print 'checked'; ?>>&nbsp;<?= $data['title'] ?>% - <?= $data['content'] ?>
							</label>
						</div>
					<?php } ?>
				</div>
			</div>
		</td>
		<td></td>
	</tr>
	<tr>
		<td class="wp60" colspan="2">
			<fieldset>
				<legend>Период создания клиента</legend>
				<div class="pad10 div-center nowrap">
					Начало:&nbsp;<input type="text" name="dstart" id="dstart" value="<?= $dstart ?>">&nbsp;&nbsp;Конец:&nbsp;<input type="text" name="dend" id="dend" value="<?= $dend ?>">
					<div class="pull-aright paddtop5 hand" title="Очистить" onclick="clearDate()">
						<i class="icon-block-1 red"></i>
					</div>
				</div>
			</fieldset>
		</td>
		<td class="wp40" colspan="2"></td>
	</tr>
</table>

<hr>

<TABLE>
	<thead>
	<TR class="sticked--top header_contaner text-center">
		<td class="w20"><b>#</b></td>
		<td class="w100"><b>Дата создания клиента</b></td>
		<td class="w100"><b>Тип отношений</b></td>
		<td class="w100"><b>Дата закрытия сделки</b></td>
		<td class="w70"><b>Этап сделки</b></td>
		<td><b>Заказчик</b></td>
		<td class="w150"><b>Источник клиента</b></td>
		<td class="w100 text-right"><b>&sum;, <?= $valuta ?></b></td>
		<td class="w100 text-right"><b>Прибыль, <?= $valuta ?></b></td>
		<td class="w100"><b>Ответств.</b></td>
	</TR>
	</thead>
	<?php
	for ($i = 0; $i < count($rez); $i++) {
		?>
		<TR class="ha">
			<TD class="text-right"><?= $i + 1 ?>.</TD>
			<TD class="text-right"><?= $rez[ $i ]['datum_client'] ?></TD>
			<TD class="text-right"><?= $rez[ $i ]['tip_cmr'] ?></TD>
			<TD class="text-right"><?= $rez[ $i ]['datum_fact'] ?></TD>
			<TD class="text-right">
				<span class="pull-left"><A href="#" onclick="openDogovor('<?= $rez[ $i ]['did'] ?>')" title="Открыть в новом окне"><i class="icon-briefcase blue"></i></A></span>&nbsp;<?= $rez[ $i ]['step'] ?>%
			</TD>
			<TD>
				<div class="ellipsis" title="<?= current_client($rez[ $i ]['clid']) ?>"><?= current_client($rez[ $i ]['clid']) ?></div>
			</TD>
			<TD>
				<div class="ellipsis"><?= $rez[ $i ]['clientpath'] ?></div>
			</TD>
			<TD class="text-right"><?= num_format($rez[ $i ]['kol']) ?></TD>
			<TD class="text-right"><?= num_format($rez[ $i ]['marga']) ?></TD>
			<TD>
				<div class="ellipsis"><?= $rez[ $i ]['user'] ?></div>
			</TD>
		</TR>
	<?php } ?>
	<TR bgcolor="#FFCC33">
		<td></td>
		<td></td>
		<td></td>
		<td></td>
		<td></td>
		<td></td>
		<td></td>
		<td class="text-right"><?= num_format(arraysum($rez, 'kol')) ?></td>
		<td class="text-right"><?= num_format(arraysum($rez, 'marga')) ?></td>
		<td></td>
	</TR>
</TABLE>

<div class="p10">
	<div class="infodiv">Отчет показывает закрытые сделки за выбранный период. При выборе периода создания клиента можно увидеть сколько сделок закрыто по клиентам созданным в указанный период</div>
</div>

<div style="height:60px"></div>

<script>

	$(function () {
		$("#dstart").datepicker({
			dateFormat: "yy-mm-dd",
			firstDay: 1,
			changeMonth: true,
			changeYear: true,
			numberOfMonths: 2
		});
		$("#dend").datepicker({
			dateFormat: "yy-mm-dd",
			firstDay: 1,
			changeMonth: true,
			changeYear: true,
			numberOfMonths: 2
		});
	});

	function clearDate() {
		$('#dstart').val('');
		$('#dend').val('');
	}

	function Export() {

		var str = $('#selectreport').serialize();
		window.open('reports/' + $('#report option:selected').val() + '?action=export&' + str);

	}
</script>