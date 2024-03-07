<?php
/* ============================ */
/*         SalesMan CRM         */
/* ============================ */
/* (C) 2023 Vladislav Andreev   */
/*       SalesMan Project       */
/*        www.isaler.ru         */
/*        ver. 2023.x           */
/* ============================ */

set_time_limit(0);
error_reporting(E_ERROR);
ini_set( 'display_errors', 1 );
header("Pragma: no-cache");

$rootpath = dirname(__DIR__);

include $rootpath."/inc/config.php";
include $rootpath."/inc/dbconnector.php";
include $rootpath."/inc/auth.php";
include $rootpath."/inc/func.php";
include $rootpath."/inc/settings.php";
include $rootpath."/inc/language/".$language.".php";

$reportName = basename( __FILE__ );

function dateFormat($date_orig, $format = 'excel'){

	$date_new = '';

	if($format == 'excel'){

		if($date_orig != '0000-00-00' && $date_orig != '' && $date_orig != null){

			$date_new = $date_orig;

		}

	}
	elseif($format == 'date'){

		if ($date_orig && $date_orig != '0000-00-00') {

			$date_new = explode("-", $date_orig);
			$date_new = $date_new[1] . "." . $date_new[2] . "." . $date_new[0];

		}

	}

	return $date_new;

}
function num2excelExt($string, $s = 2): string {

	$string = str_replace(",", ".", $string);
	$string = str_replace(" ", "", $string);

	return number_format($string, $s, '.', '');

}

$action = $_REQUEST['action'];
$da1    = $_REQUEST['da1'];
$da2    = $_REQUEST['da2'];

$roles     = (array)$_REQUEST['roles'];
$user_list = (array)$_REQUEST['user_list'];
$tips      = (array)$_REQUEST['tips'];

$thisfile = basename($_SERVER['PHP_SELF']);

//заголовки этапов
$header = $xsteps = [];
$res = $db -> query("SELECT * FROM {$sqlname}dogcategory WHERE idcategory > 0 and identity = '$identity' ORDER BY title");
while ($data = $db -> fetch($res)) {

	$header[] = [
		"name"    => $data['title'],
		"content" => $data['content'],
		"space"   => '',
		"log"     => ''
	];

	$xsteps[$data['idcategory']] = $data['title'];

}

$sort = '';

$color = [
	'#F7C1BB',
	'#F29E95',
	'#ED786B',
	'#E74B3B',
	'#E74B3B'
];

//массив выбранных пользователей
if ( !empty( $user_list ) ) {
	$sort .= " deal.iduser IN (".yimplode( ",", $user_list ).") AND ";
}
else {
	$sort .= " deal.iduser IN (".yimplode( ",", (array)get_people( $iduser1, 'yes' ) ).") AND ";
}

$rez   = [];
$steps = [];
$step  = [];
$g     = 0;

$format = ($action == 'export') ? 'excel' : 'date';

//перебираем сделки
$q  = "
	SELECT
		deal.did as did,
		deal.title as title,
		deal.datum as dcreate,
		deal.datum_plan as dplan,
		deal.datum_close as dclose,
		deal.datum_start as dstart,
		deal.datum_end as dend,
		deal.idcategory as idstep,
		deal.tip as tip,
		deal.clid as clid,
		deal.pid as pid,
		deal.kol as kol,
		deal.kol_fact as kolf,
		COALESCE(deal.close, 'no') as close,
		deal.iduser as iduser,
		deal.des_fact as des_fact,
		deal.adres as adres,
		substring(deal.content, 0, 250) as des,
		us.title as user,
		cc.title as client,
		dc.title as step,
		dc.content as steptitle,
		dt.title as tips,
		ds.title as dstatus,
		dr.title as direction
	FROM {$sqlname}dogovor `deal`
		LEFT JOIN {$sqlname}user `us` ON deal.iduser = us.iduser
		LEFT JOIN {$sqlname}clientcat `cc` ON deal.clid = cc.clid
		LEFT JOIN {$sqlname}dogcategory `dc` ON deal.idcategory = dc.idcategory
		LEFT JOIN {$sqlname}dogtips `dt` ON deal.tip = dt.tid
		LEFT JOIN {$sqlname}dogstatus `ds` ON deal.sid = ds.sid
		LEFT JOIN {$sqlname}direction `dr` ON deal.direction = dr.id
	WHERE
		deal.did > 0 and
		$sort
		deal.did IN (SELECT did FROM {$sqlname}steplog WHERE did > 0 and datum BETWEEN '$da1 00:00:01' and '$da2 23:59:59') and
		deal.identity = '$identity'
		ORDER BY deal.title
";
$re = $db -> getAll($q);
foreach ($re as $da) {

	$steps = [];

	//перебираем этапы
	$res = $db -> getAll("SELECT idcategory FROM {$sqlname}dogcategory WHERE identity = '$identity' ORDER BY title");
	foreach ($res as $data) {

		$st      = $db -> getOne("SELECT MAX(datum) as datum FROM {$sqlname}steplog WHERE did = '".$da['did']."' and step = '".$data['idcategory']."'");
		$steps[] = ($st == '0000-00-00 00:00:00' || $st == '' || $st == null) ? '' : get_sfdate2($st);

	}

	$hist = $db -> getOne("SELECT MAX(datum) as datum FROM {$sqlname}history WHERE (did = '".$da['did']."') and identity = '$identity'");
	if ($hist == '0000-00-00 00:00:00' || $hist == '' || $hist == null) {
		$hist = '';
	}

	$task = $db -> getOne("SELECT MIN(datum) as datum FROM {$sqlname}tasks WHERE did = '".$da['did']."' and datum >= '".current_datum()."' and identity = '$identity'");
	if ($task == '0000-00-00' || $task == '' || $task == null) {
		$task = '';
	}

	$path = current_clientpath($da['clid']);

	$dateStartLog = $db -> getRow("SELECT MIN(datum) as datum, step FROM {$sqlname}steplog WHERE did = '".$da['did']."' and datum BETWEEN '$da1 00:00:01' and '$da2 23:59:59'");

	$rez[ $g ] = [
		"id"           => $da['did'],
		"path"         => $path,
		"dcreate"      => dateFormat($da['dcreate'], $format),
		"dplan"        => dateFormat($da['dplan'], $format),
		"dstart"       => dateFormat($da['dstart'], $format),
		"dend"         => dateFormat($da['dend'], $format),
		"dateLog"      => dateFormat($dateStartLog['datum'], $format),
		"stepLog"      => $xsteps[$dateStartLog['step']],
		"tip"          => $da['tips'],
		"summa"        => $da['kol'],
		"marga"        => $da['marga'],
		"did"          => $da['did'],
		"deal"         => $da['title'],
		"adres"        => $da['adres'],
		"description"  => $da['des'],
		"clid"         => $da['clid'],
		"client"       => $da['client'],
		"user"         => $da['user'],
		"status"       => $da['close'] == 'yes' ? 'Закрыта' : 'Активна',
		"step"         => $da['step'],
		"close"        => $da['close'],
		"closeDate"    => dateFormat($da['dclose'], $format),
		"closeSumma"   => $da['kolf'],
		"closeStatus"  => $da['dstatus'],
		"closeComment" => $da['des_fact'],
		"history"      => get_sfdate2($hist),
		"task"         => $task,
		"steplog"      => $steps,
		"stepcount"    => 0
	];

	foreach ($steps as $key => $value) {
		$rez[ $g ][ 'log'.$key ] = $value;
	}

	$stepcount = (int)$db -> getOne("SELECT COUNT(id) FROM {$sqlname}steplog WHERE did = '".$da['did']."' and datum BETWEEN '$da1 00:00:01' and '$da2 23:59:59'");

	$rez[ $g ]['stepcount'] = $stepcount;

	$g++;

}

if ($action == 'export') {

	/**
	 * Формируем заголовок
	 */
	$head = [
		"ID",
		"Название",
		"Клиент",
		"Источник",
		"Менеджер",
		"Текущий статус",
		"Текущий этап",
		"Этап на начало периода",
		"Кол-во перемещений",
		"Дата создания",
		"Дата последней активности"
	];

	foreach ($header as $key => $val) {
		$head[] = $val['name']."% [".$val['content']."]";
	}

	$head = array_merge($head, [
		"Дата ближайшей запланированной активности",
		"Дата план",
		"Дата закрытия",
		"Статус закрытия",
		"Комментарий закрытия",
		"Сумма план",
		"Маржа",
		"Сумма факт",
		"Тип сделки",
		"Адрес",
		"Описание",
		"Период старт",
		"Период финиш"
	]);

	$otchet[] = $head;

	foreach ($rez as $key => $value) {

		$string1 = [];

		$string = [
			$value['id'],
			$value['deal'],
			$value['client'],
			$value['path'],
			$value['user'],
			$value['status'],
			$value['step']."%",
			$value['dateLog'],
			$value['stepcount'],
			$value['dcreate'],
			dateFormat($value['history'], $format)
		];

		foreach ($value['steplog'] as $val) {
			$string[] = dateFormat( $val, $format );
		}

		$string1 = array_merge($string, [
			dateFormat($value['task'], $format),
			$value['dplan'],
			$value['closeDate'],
			$value['closeStatus'],
			$value['closeComment'],
			$value['summa'],
			$value['marga'],
			$value['closeSumma'],
			$value['tip'],
			$value['adres'],
			$value['description'],
			$value['dstart'],
			$value['dend']
		]);

		$otchet[] = $string1;

	}

	//создаем файл csv
	$filename = 'export_deals.xlsx';

	Shuchkin\SimpleXLSXGen::fromArray( $otchet )->downloadAs($filename);

	exit();

}
if (!$action) {

	?>

	<style>
		<!--
		table.borderer thead tr th,
		table.borderer tr,
		table.borderer td {
			border-left   : 1px dotted #ccc !important;
			border-bottom : 1px dotted #ccc !important;
			padding       : 2px 3px 2px 3px;
			height        : 30px;
			white-space   : nowrap;
		}

		table.borderer thead th:last-child {
			border-right : 1px dotted #ccc !important;
		}

		table.borderer thead tr:first-child th {
			border-top : 1px dotted #ccc !important;
		}

		table.borderer td:last-child {
			border-right : 1px dotted #ccc !important;
		}

		table.borderer thead {
			border : 1px dotted #ccc !important;
		}

		table.borderer thead td,
		table.borderer thead th {
			background : #E5F0F9;
		}

		table.borderer thead th {
			border-bottom : 1px dotted #666 !important;
		}

		table.borderer thead {
			border : 1px dotted #222 !important;
		}

		table.borderer td i {
			z-index  : 0;
			position : inherit;
		}

		.colorit {
			display       : block;
			height        : 25px;
			line-height   : 25px;
			padding-right : 5px;
		}

		table thead td {
			background    : #E5F0F9;
		}

		tr.ha:hover > .path {
			background : rgba(197, 225, 165, 1) !important;
		}

		@media print {
			.fixAddBotButton {
				display : none;
			}
		}

		-->
	</style>

	<div class="relativ mt20 mb0 wp100 text-center" id="head">

		<h1 class="uppercase fs-14 m0 mb10">Движение сделок по этапам</h1>
		<div class="gray2">в период&nbsp;с&nbsp;<?= $da1 ?>&nbsp;по&nbsp;<?= $da2 ?> [ <a href="#" onClick="toExcel()" style="color:blue">Экспорт в Excel</a> ]</div>

		<hr>

		<div class="infodiv" id="info">
			В отчет выводятся сделки, у которых было произведено изменение этапа в указанном периоде
		</div>

	</div>

	<hr>

	<div style="width: 98.5%; overflow: auto" id="datatable">

		<TABLE id="zebra" class="borderer">
			<thead>
			<TR class="bordered text-center">
				<th rowspan="2" class="w60"><b>ID</b></th>
				<th rowspan="2" class="w300"><b>Название</b></th>
				<th rowspan="2" class="w100"><b>Источник</b></th>
				<th rowspan="2" class="w100"><b>Менеджер</b></th>
				<th rowspan="2" class="w100"><b>Текущий<br>статус</b></th>
				<th rowspan="2" class="w100"><b>Этап<br>текущий</b></th>
				<th rowspan="2" class="w100"><b>Этап<br>на начало периода</b></th>
				<th rowspan="2" class="w100"><b>Кол-во<br>перемещений</b></th>
				<th rowspan="2" class="w120"><b>Дата<br>создания</b></th>
				<th rowspan="2" class="w120" ><b>Дата<br>последней активности</b></th>
				<?php
				foreach ($header as $key => $val) {
				?>
				<th class="w130"><?= $val['name'] ?>%</th>
				<?php } ?>
				<th rowspan="2" class="w120"><b>Дата ближайшей запланированной активности</b></th>
				<th rowspan="2" class="w120"><b>Дата план</b></th>
				<th rowspan="2" class="w120"><b>Дата закрытия</b></th>
				<th rowspan="2" class="w100"><b>Статус закрытия</b></th>
				<th rowspan="2" class="w200"><b>Комментарий закрытия</b></th>
				<th rowspan="2" class="w150"><b>Сумма план</b></th>
				<th rowspan="2" class="w150"><b>Маржа</b></th>
				<th rowspan="2" class="w150"><b>Сумма факт</b></th>
				<th rowspan="2" class="w100"><b>Тип сделки</b></th>
				<th rowspan="2" class="w160"><b>Адрес</b></th>
				<th rowspan="2" class="w200"><b>Описание</b></th>
				<th rowspan="2" class="w100"><b>Период старт</b></th>
				<th rowspan="2" class="w100"><b>Период финиш</b></th>
			</TR>
			<TR class="bordered">
				<?php
				foreach ($header as $key => $val) {
					?>
					<th class="text-left" title="<?= $val['content'] ?>">
						<div class="ellipsis"><?= $val['content'] ?></div>
					</th>
				<?php } ?>
			</TR>
			</thead>
			<tbody>
			<?php
			foreach ($rez as $key => $value) {

				$bg = '';

				if ($value['close'] == 'yes' && $value['closeSumma'] == 0) {
					$bg = 'graybg-sub';
				}
				if ($value['close'] == 'yes' && $value['closeSumma'] > 0) {
					$bg = 'greenbg-sub';
				}

				?>
				<TR class="ha <?= $bg ?>">
					<td class="text-center"><?= $value['id'] ?></td>
					<td class="w300">
						<div class="Bold fs-11 mt5 w300">
							<div class="ellipsis">
								<a href="javascript:void(0)" onclick="viewDogovor('<?= $value['did'] ?>')"><i class="icon-briefcase-1 blue"></i> <?= $value['deal'] ?></a>
							</div>
						</div>
						<div class="ellipsis1 gray2 fs-09 mt5 w300">
							<div class="ellipsis">
								<a href="javascript:void(0)" onclick="viewClient('<?= $value['clid'] ?>')"><i class="icon-building broun"></i> <?= $value['client'] ?></a>
							</div>
						</div>
					</td>
					<td><?= $value['path'] ?></td>
					<td nowrap>
						<div class="ellipsis1"><?= $value['user'] ?></div>
					</td>
					<td class="text-center" class="Bold <?= ($value['close'] == 'yes' ? 'green' : 'blue') ?>"><?= $value['status'] ?></td>
					<td class="text-right"><?= $value['step'] ?>%</td>
					<td class="text-right"><?= $value['stepLog'] ?>%</td>
					<td class="text-center"><?= $value['stepcount'] ?></td>
					<td class="text-center"><?= $value['dcreate'] ?></td>
					<td class="text-center"><?= $value['history'] ?></td>
					<?php
					foreach ($value['steplog'] as $k => $val) {
						?>
						<td><?= $val ?></td>
					<?php } ?>
					<td class="text-center"><?= $value['task'] ?></td>
					<td class="text-center"><?= $value['dplan'] ?></td>
					<td class="text-center"><?= $value['closeDate'] ?></td>
					<td><?= $value['closeStatus'] ?></td>
					<td><?= $value['closeComment'] ?></td>
					<td class="text-right" nowrap=""><?= num_format($value['summa']) ?></td>
					<td class="text-right" nowrap=""><?= num_format($value['marga']) ?></td>
					<td class="text-right" class="Bold <?= ($value['closeSumma'] > 0 ? 'green' : 'gray') ?>" nowrap><?= num_format($value['closeSumma']) ?></td>
					<td><?= $value['tip'] ?></td>
					<td><?= $value['adres'] ?></td>
					<td><?= $value['description'] ?></td>
					<td class="text-center"><?= $value['dstart'] ?></td>
					<td class="text-center"><?= $value['dend'] ?></td>
				</TR>
			<?php } ?>
			</tbody>
		</TABLE>

	</div>

	<div class="space-80"></div>

	<script src="/assets/js/tableHeadFixer/tableHeadFixer.js"></script>
	<script>

		var h = $('#clientlist').height() - $('#head').height() - 50;

		$('#datatable').css('height', h + 'px');

		$(function () {

			$("#zebra").tableHeadFixer({
				'head': true,
				'foot': false,
				'z-index': 12000,
				'left': 2
			}).css('z-index', '100');

			$("#zebra").find('td:nth-child(1)').css('z-index', '110');

			$('th').addClass('bluebg-sub');

		});

		function exportDeal() {
			window.open('reports/' + $('#report option:selected').val() + '?action=export');
		}

	</script>

<?php }

?>