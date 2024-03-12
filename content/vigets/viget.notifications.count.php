<?php
/* ============================ */
/*         SalesMan CRM         */
/* ============================ */
/* (C) 2014 Vladislav Andreev   */
/*       SalesMan Project       */
/*        www.isaler.ru         */
/*           ver. 7.75          */
/* ============================ */

error_reporting( E_ERROR );
header( "Pragma: no-cache" );

$rootpath = dirname( __DIR__, 2 );

include $rootpath."/inc/config.php";
include $rootpath."/inc/dbconnector.php";
include $rootpath."/inc/auth.php";
include $rootpath."/inc/func.php";
include $rootpath."/inc/settings.php";
include $rootpath."/inc/language/".$language.".php";

$thisfile = basename( __FILE__ );

$Interval  = $_COOKIE[ 'tparameterInterval' ];
$action    = $_REQUEST[ 'action' ];
$tip_id    = $_REQUEST[ 'tip_deal' ];
$setperiod = ( strlen( $Interval ) > 1 && $Interval != 'undefined' ) ? $Interval : 'today';
//$setperiod = (strlen( $Interval ) > 1 && $Interval != 'undefined') ? $Interval : 'month';

$period = getPeriod( $setperiod );


$da1 = $period[ 0 ];
$da2 = $period[ 1 ];

$tip   = [];
$color = [
	'#1f77b4',
	'#aec7e8',
	'#ff7f0e',
	'#ffbb78',
	'#2ca02c',
	'#98df8a',
	'#d62728',
	'#ff9896',
	'#9467bd',
	'#c5b0d5',
	'#8c564b',
	'#c49c94',
	'#e377c2',
	'#f7b6d2',
	'#7f7f7f',
	'#c7c7c7',
	'#bcbd22',
	'#dbdb8d',
	'#17becf',
	'#9edae5',
	'#393b79',
	'#5254a3',
	'#6b6ecf',
	'#9c9ede',
	'#637939',
	'#8ca252',
	'#b5cf6b',
	'#cedb9c',
	'#8c6d31',
	'#bd9e39',
	'#e7ba52',
	'#e7cb94',
	'#843c39',
	'#ad494a',
	'#d6616b',
	'#e7969c',
	'#7b4173',
	'#a55194',
	'#ce6dbd',
	'#de9ed6',
	'#FF0F00',
	'#000099',
	'#006600',
	'#CC6600',
	'#666699',
	'#990099',
	'#999900',
	'#0066CC',
	'#FF6600',
	'#996666',
	'#FF0033',
	'#0099FF',
	'#663300',
	'#666600',
	'#FF00CC',
	'#9900FF',
	'#FFCC00',
	'#003366',
	'#333333',
	'#99FF00'
];

$sort = $sqlname."dogovor.iduser IN (".yimplode( ",", get_people( $iduser1, "yes" ) ).") and ";

if ( !$action ) $action = 'list';

// Детализация по типам сделок
if ( $action == 'info' ) {

	$info = [];

	if ( $tip_id != '' ) {
		$sort .= $sqlname."dogovor.tip = '$tip_id' and ";
	}

	// Формирование запроса к БД
	$q = "
	SELECT 
		{$sqlname}dogovor.did as did,
		{$sqlname}dogovor.title as title,
		{$sqlname}dogcategory.title as step,
		{$sqlname}dogcategory.content as stage,
		{$sqlname}dogovor.kol as sum,
		{$sqlname}dogovor.datum_plan as date,
		{$sqlname}user.title as user
	FROM {$sqlname}dogovor
	LEFT JOIN {$sqlname}dogtips ON {$sqlname}dogovor.tip = {$sqlname}dogtips.tid
		LEFT JOIN {$sqlname}tasks ON {$sqlname}dogovor.did = {$sqlname}tasks.did
		LEFT JOIN {$sqlname}user ON {$sqlname}dogovor.iduser = {$sqlname}user.iduser
		LEFT JOIN {$sqlname}dogcategory ON {$sqlname}dogovor.idcategory = {$sqlname}dogcategory.idcategory
	WHERE 
		$sort
		{$sqlname}tasks.datum BETWEEN '$da1 00:00:00' and '$da2 23:59:59' and 
		{$sqlname}tasks.active = 'yes' and 
		{$sqlname}dogtips.identity = '$identity'
	";

	$output = $db -> getAll( $q );
	foreach ( $output as $field ) {

		$info[] = [
			"did"   => $field[ 'did' ],
			"name"  => $field[ 'title' ],
			"step"  => $field[ 'step' ],
			"stage" => $field[ 'stage' ],
			"sum"   => num_format( $field[ 'sum' ] ),
			"date"  => date( "d.m.Y", strtotime( $field[ 'date' ] ) ),
			"user"  => $field[ 'user' ]
		];

	}
	?>

	<!-- Вывод таблицы по выбранному типу сделки-->
	<div class="zagolovok">Детализация по типам сделок:</div>

	<div id="formtabs" style="overflow-x: hidden; overflow-y: auto !important;">

		<table class="bborder" id="zebraTable">
			<thead>
			<TR class="header_contaner" height="50">
				<th width="20" align="center"></th>
				<th align="center"><b>Название сделки</b></th>
				<th width="80" align="center"><b>Этап сделки</b></th>
				<th class="yw120" align="center"><b>Сумма, руб.</b></th>
				<th class="yw120" align="center"><b>Дата план.</b></th>
				<th class="yw120" align="center"><b>Отвественный</b></th>
			</tr>
			</thead>
			<tbody>
			<?php
			foreach ( $info as $i => $item ) {
				?>
				<tr height="40" class="ha bgwhite">
					<td align="left"><?= $i + 1 ?>.</td>
					<td align="left">
						<div class="ellipsis">
							<a href="javascript:void(0)" onclick="openDogovor('<?= $item[ 'did' ] ?>')" title="Карточка: <?= current_dogovor( $item[ 'did' ] ) ?>"><i class="icon-briefcase blue"></i><?= $item[ 'name' ] ?>
							</a>
						</div>
					</td>
					<td align="left" title="<?= $item[ 'stage' ] ?>"><?= $item[ 'step' ] ?>%</td>
					<td align="right"><?= $item[ 'sum' ] ?></td>
					<td align="center"><?= $item[ 'date' ] ?></td>
					<td align="left"><?= $item[ 'user' ] ?></td>
				</tr>
				<?php
			}
			?>
			</tbody>
		</table>

	</div>

	<script>

		includeJS("/assets/js/tableHeadFixer.js");

		if (!isMobile) {

			var hh = $('#dialog_container').actual('height') * 0.85;
			var hh2 = hh - $('.zagolovok').actual('outerHeight') - 70;

			if ($(window).width() > 990) $('#dialog').css({'width': '900px'});
			else if ($(window).width() > 1200) $('#dialog').css({'width': '950px'});
			else $('#dialog').css('width', '90vw');

			$('#formtabs').css('max-height', hh2);

		}
		else {

			var h2 = $(window).height() - $('.zagolovok').actual('outerHeight') - 30;
			$('#formtabs').css({'max-height': h2 + 'px', 'height': h2 + 'px'});

		}

		$(document).ready(function () {

			$('#dialog').center();

			if (!isMobile) $("#zebraTable").tableHeadFixer({
				'head': true,
				'foot': true,
				'z-index': 12000
			}).find('th').css('z-index', '100');
			if (isMobile) $('#dialog').find('table').rtResponsiveTables({id: 'table-<?=$action?>'});

			$('#formtabs').find('.ellipsis').css({"position": "inherit"});
			$('#formtabs').find('i').css({"position": "inherit"});

		});

	</script>
	<?php
	exit();

}

//Вывод количества напоминаний по сделкам

if ( $action == 'list' ) {

	$list = [];

	// Запрос к БД
	$query = "
		SELECT 
			{$sqlname}dogtips.title as tip,
			COUNT({$sqlname}dogovor.tip) as count,
			{$sqlname}dogovor.tip as tid
		FROM {$sqlname}dogovor
			LEFT JOIN {$sqlname}dogtips ON {$sqlname}dogovor.tip = {$sqlname}dogtips.tid
			LEFT JOIN {$sqlname}tasks ON {$sqlname}dogovor.did = {$sqlname}tasks.did
		WHERE 
			$sort
			{$sqlname}tasks.datum BETWEEN '$da1 00:00:00' and '$da2 23:59:59' and 
			{$sqlname}tasks.active = 'yes'and
			{$sqlname}dogtips.identity = '$identity'
		GROUP BY {$sqlname}dogovor.tip
		";

	$result = $db -> getAll( $query );
	foreach ( $result as $data ) {

		$list[] = [
			"tip"      => $data[ 'tip' ],
			"count"    => $data[ 'count' ],
			"tip_deal" => $data[ 'tid' ]
		];

	}

	if ( empty( $list ) ) {
		print "Напоминаний нет";
	}

	$sum = arraysum( $list, 'count' );

	function cmp( $a, $b ) { return $b[ 'count' ] > $a[ 'count' ]; }

	usort( $list, 'cmp' );

	?>
	<table id="bborder">
		<thead class="hidden">
		<tr>
			<th>Тип сделки</th>
			<th>Кол-во напоминаний</th>
			<th></th>
			<th>Доля</th>
		</tr>
		</thead>
		<tbody>
		<?php
		foreach ( $list as $i => $item ) {

			$kol = $item[ 'count' ];
			$res = $sum > 0 ? round( $kol / ( $sum / 100 ), 1 ) : 0;

			?>
			<tr class="ha hand th40 colorer border-bottom" data-tip="tip_deal" data-id="<?= $item[ 'tip_deal' ] ?>">
				<td>
					<div class="Bold"><?= $item[ 'tip' ] ?></div>
				</td>
				<td class="wp20"><span><?= $kol ?></span></td>
				<td class="wp20">
					<span class="sparkpie" id="sp<?= $i ?>" data-value="<?= $kol ?>,<?= $sum - $kol ?>"></span>
				</td>
				<td class="wp20">
					<span><?= $res ?>&nbsp;%</span>
				</td>
			</tr>
			<?php
		}
		?>
		</tbody>
	</table>

	<div class="pull-aright mt5">
		<ul class="group">
			<li data-id="today">Сегодня</li>
			<li data-id="calendarweek">Неделя</li>
			<li data-id="month">Месяц</li>
			<li data-id="quart">Квартал</li>
			<li data-id="year">Год</li>
		</ul>
	</div>

	<style>

		#notifications_count ul.group {
			position              : absolute;
			z-index               : 1;
			top                   : calc(100% - 40px);
			right                 : 10px;
			display               : table;
			list-style            : none;
			background            : rgba(245, 245, 245, 0.3);
			border                : 1px solid #CCD1D9;
			margin-top            : 5px;
			padding               : 0;
			font-size             : 0.9em;
			border-radius         : 4px;
			-moz-border-radius    : 4px;
			-webkit-border-radius : 4px;
		}
		#notifications_count ul.group > li {
			margin       : 0 !important;
			padding      : 5px 10px !important;
			display      : table-cell;
			text-align   : center;
			cursor       : pointer;
			border-right : 1px solid #CCD1D9;
			box-sizing   : border-box !important;
		}
		#notifications_count ul.group > li:last-child {
			border-right : 0;
		}
		#notifications_count ul.group > li:hover,
		#notifications_count ul.group > li.active {
			color        : #fff;
			background   : #C0392B;
			border-color : #C0392B !important;
		}

	</style>

	<script>

		includeJS("/assets/js/jquery.sparkline.min.js");

		$(function () {

			var urll = "/content/vigets/<?= $thisfile ?>";
			var $count = $('#notifications_count');
			var $tr = $('tr[data-tip="tip_deal"]');

			// Выбор периода времени
			$count.find('ul.group').find('li[data-id="<?=$setperiod?>"]').addClass('active');
			$count.find('li').bind('click', function () {

				var id = $(this).data('id');

				setCookie('tparameterInterval', id, {"expires": 1000000});

				$count.load('/content/vigets/viget.notifications.count.php');

			});

			// Выбор типа сделки
			$tr.off('click');
			$tr.on('click', function () {

				var id = $(this).data('id');
				var str = '?action=info&tip_deal=' + id;
				doLoad(urll + str);

			});

			var id;
			var value = [];

			$count.find('.sparkpie').each(function () {

				id = $(this).attr('id');
				value = $(this).data('value').split(",");

				$(this).sparkline(value, {
					type: 'pie',
					width: '20px',
					height: '20px'
				});

			});

		});

	</script>
	<?php
	exit();
}
?>