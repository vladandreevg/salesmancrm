<?php
/* ============================ */
/*         SalesMan CRM         */
/* ============================ */
/* (C) 2016 Vladislav Andreev   */
/*       SalesMan Project       */
/*        www.isaler.ru         */
/*        ver. 2017.x           */
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

$Interval = $_COOKIE['parameterInterval'];
$action   = $_REQUEST['action'];
$param    = $_REQUEST['param'];

$setperiod = (strlen( $Interval ) > 1 && $Interval != 'undefined') ? $Interval : 'month';

$period = getPeriod( $setperiod );

//print_r($period);

$da1 = $period[0];
$da2 = $period[1];

$sort = '';

$modOn = (int)$db -> getOne( "SELECT id FROM {$sqlname}modules WHERE active = 'on' and mpath = 'callcenter' and identity = '$identity'" );
if ( $modOn == 0 ) {

	print '
		<div class="warning flex-container">
			<div class="flex-string wp30">
				<span class="pull-left"><i class="icon-attention red icon-5x pull-left"></i></span>
			</div>
			<div class="flex-string wp70 pb20">
				<h1 class="red uppercase mt5">Внимание</h1>
				Модуль не установлен или не активен! Работа виджета не возможна!
			</div>
		</div>
	';

	exit();

}

if ( !$action )
	$action = 'list';

// Детализация по параметру
if ( $action == 'info' ) {


	$size = "60";
	$info = [];

	if ( $param == 'do' ) {

		$sort  .= $sqlname."callcenter.status = 'do' AND";
		$tip   = "Обработанные задания";
		$color = "green";

	}
	elseif ( $param == 'active' ) {

		$sort  .= $sqlname."callcenter.status = 'active' AND";
		$tip   = "Активные задания";
		$color = "olive";

	}
	else {

		$tip  = "Новые задания";
		$size = "70";

	}


	// Формирование запроса к БД
	$q = "
	SELECT 
		{$sqlname}callcenter.id as id,
		{$sqlname}callcenter.title as title,
		{$sqlname}callcenter.status as status,
		{$sqlname}callcenter.datum as date_create,
		{$sqlname}callcenter.dstart as dstart,
		{$sqlname}callcenter.dend as dend,
		{$sqlname}user.title as user
	FROM {$sqlname}callcenter
		LEFT JOIN {$sqlname}user ON {$sqlname}callcenter.iduser = {$sqlname}user.iduser
	WHERE 
		$sort
		{$sqlname}callcenter.iduser IN (".yimplode( ",", get_people( $iduser1, "yes" ) ).") AND 
		{$sqlname}callcenter.status != 'draft' AND
		{$sqlname}callcenter.datum BETWEEN '$da1 00:00:00' AND '$da2 23:59:59' AND 
		{$sqlname}callcenter.identity = '$identity'
	ORDER BY {$sqlname}callcenter.datum DESC
	";

	$output = $db -> getAll( $q );
	foreach ( $output as $field ) {

		$info[] = [
			"id"          => $field['id'],
			"title"       => $field['title'],
			"status"      => $field['status'],
			"date_create" => date( "d.m.Y", strtotime( $field['date_create'] ) ),
			"dstart"      => ($field['dstart'] != '') ? date( "d.m.Y", strtotime( $field['dstart'] ) ) : "-",
			"dend"        => ($field['dend'] != '') ? date( "d.m.Y", strtotime( $field['dend'] ) ) : "-",
			"user"        => $field['user']
		];

	}

	?>

	<!-- Вывод таблицы по выбранному типу сделки-->
	<div class="zagolovok" style="background: <?= $color ?>"><?= $tip ?></div>

	<div id="formtabs" style="overflow-x: hidden; overflow-y: auto !important;">

		<table class="bborder" id="zebraTable">
			<thead>
			<TR height="50" class="header_contaner">
				<th width="20" align="center"></th>
				<th align="center"><b>Название задания</b></th>
				<th width="100" align="center"><b>Дата создания</b></th>
				<?php if ( $param == 'all' ) { ?>
					<th width="100" align="center"><b>Cтатус</b></th>
				<?php } ?>
				<th width="100" align="center"><b>Дата начала</b></th>
				<?php if ( $param != 'active' ) { ?>
					<th width="100" align="center"><b>Дата завершения</b></th>
				<?php } ?>
				<?php if ( $param != 'do' ) { ?>
					<th width="100" align="center"><b>Обработано</b></th>
				<?php } ?>
				<th width="120" align="center"><b>Оператор</b></th>
			</tr>
			</thead>
			<tbody>

			<?php
			foreach ( $info as $i => $item ) {
				?>
				<tr height="40" class="ha">
					<td align="left"><?= $i + 1 ?>.</td>
					<td align="left">
						<div class="ellipsis"><b><?= $item['title'] ?></b></div>
					</td>
					<td align="center"><?= $item['date_create'] ?></td>

					<?php if ( $param == 'all' ) {

						$do = ($item['status'] == 'do') ? '<span class="green">Завершено</span>' : '<span class="red">Активно</span>';

						?>
						<td align="center"><?= $do ?></td>
					<?php } ?>

					<td align="center"><?= $item['dstart'] ?></td>

					<?php if ( $param != 'active' ) { ?>
						<td align="center"><?= $item['dend'] ?></td>
					<?php } ?>

					<?php if ( $param != 'do' ) {

						//Все звонки
						$q = "
						SELECT 
							COUNT(*) as count
						FROM {$sqlname}callcenter_list
						LEFT JOIN {$sqlname}callcenter ON {$sqlname}callcenter_list.task = {$sqlname}callcenter.id
						WHERE 
							{$sqlname}callcenter.status!='draft' AND
							{$sqlname}callcenter_list.task='".$item['id']."' AND
						  	{$sqlname}callcenter_list.iduser IN (".yimplode( ",", get_people( $iduser1, "yes" ) ).") AND 
							{$sqlname}callcenter_list.datum BETWEEN '$da1 00:00:00' AND '$da2 23:59:59' AND 
							{$sqlname}callcenter_list.identity = '$identity'
					";

						$callAll = $db -> getOne( $q );

						//Обработанные звонки
						$q = "
						SELECT 
							COUNT(*) as count
						FROM {$sqlname}callcenter_list
						LEFT JOIN {$sqlname}callcenter ON {$sqlname}callcenter_list.task = {$sqlname}callcenter.id
						WHERE 
							{$sqlname}callcenter.status!='draft' AND
							{$sqlname}callcenter_list.task='".$item['id']."' AND
						  	{$sqlname}callcenter_list.iduser IN (".yimplode( ",", get_people( $iduser1, "yes" ) ).") AND 
							{$sqlname}callcenter_list.isdo='yes' AND
							{$sqlname}callcenter_list.datum BETWEEN '$da1 00:00:00' AND '$da2 23:59:59' AND 
							{$sqlname}callcenter_list.identity = '$identity'
					";

						$callSuc = $db -> getOne( $q );

						$progress = round( $callSuc / ($callAll / 100), 1 );

						?>

						<td align="center">

							<?php if ( $progress > 75 ) {
								$clr = "green";
							}
							elseif ( $progress < 50 ) {
								$clr = "red";
							}
							else {
								$clr = "broun";
							}
							?>
							<span class='<?= $clr ?>'><b><?= $progress ?>%</b> </span>(<?= $callSuc."/".$callAll ?>)

						</td>

						<?php

					}

					?>

					<td align="left"><?= $item['user'] ?></td>

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

			if ($(window).width() > 990) $('#dialog').css({'width': '<?=$size ?>vw'});
			else if ($(window).width() > 1200) $('#dialog').css({'width': '<?=$size + 10 ?>vw'});
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

//Главная панель виджета
if ( $action == 'list' ) {

	//Общее кол-во новых заданий
	$tasksCount = $db -> getOne( "SELECT COUNT(*) as count FROM {$sqlname}callcenter WHERE id > 0 ".get_people( $iduser1 )." AND status!='draft' AND datum BETWEEN '".$da1." 00:00:00' AND '".$da2." 23:59:59' and identity = '$identity'" );

	//Кол-во обработанных заданий
	$tasksDo = $db -> getOne( "SELECT COUNT(*) as count FROM {$sqlname}callcenter WHERE id > 0 ".get_people( $iduser1 )." AND status='do' AND datum BETWEEN '".$da1." 00:00:00' AND '".$da2." 23:59:59' AND identity = '$identity'" );

	//Кол-во активных заданий
	$tasksActive = $db -> getOne( "SELECT COUNT(*) as count FROM {$sqlname}callcenter WHERE id > 0 ".get_people( $iduser1 )." AND status='active' AND datum BETWEEN '".$da1." 00:00:00' AND '".$da2." 23:59:59' AND identity = '$identity'" );

	//Кол-во обработанных звонков
	$q = "
	SELECT 
		COUNT(*) as count
	FROM {$sqlname}callcenter_list
		LEFT JOIN {$sqlname}callcenter ON {$sqlname}callcenter_list.task = {$sqlname}callcenter.id
	WHERE 
		{$sqlname}callcenter.status!='draft' AND
		{$sqlname}callcenter_list.iduser IN (".yimplode( ",", get_people( $iduser1, "yes" ) ).") AND 
		{$sqlname}callcenter_list.isdo='yes' AND
		{$sqlname}callcenter_list.datum BETWEEN '$da1 00:00:00' AND '$da2 23:59:59' AND 
		{$sqlname}callcenter_list.identity = '$identity'
	";

	$callsDo = $db -> getOne( $q );

	//Общее кол-во звонков
	$q = "
	SELECT 
		COUNT(*) as count
	FROM {$sqlname}callcenter_list
		LEFT JOIN {$sqlname}callcenter ON {$sqlname}callcenter_list.task = {$sqlname}callcenter.id
	WHERE 
		{$sqlname}callcenter.status!='draft' AND
		{$sqlname}callcenter_list.iduser IN (".yimplode( ",", get_people( $iduser1, "yes" ) ).") AND 
		{$sqlname}callcenter_list.datum BETWEEN '$da1 00:00:00' AND '$da2 23:59:59' AND 
		{$sqlname}callcenter_list.identity = '$identity'
	";

	$callsAll = $db -> getOne( $q );

	?>

	<style>

		#statcallcenter ul.group {
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

		#statcallcenter ul.group > li {
			margin       : 0 !important;
			padding      : 5px 10px !important;
			display      : table-cell;
			text-align   : center;
			cursor       : pointer;
			border-right : 1px solid #CCD1D9;
			box-sizing   : border-box !important;
		}

		#statcallcenter ul.group > li:last-child {
			border-right : 0;
		}

		#statcallcenter ul.group > li:hover,
		#statcallcenter ul.group > li.active {
			color        : #fff;
			background   : #C0392B;
			border-color : #C0392B !important;
		}

		#field:hover {
			background : #E9F1F1;
			cursor     : pointer;
		}

	</style>

	<div class="flex-container box--child" style="justify-content: space-between;">

		<div class="flex-string wp50 flx-2 p10" data-id="all" data-tip="param" id="field">

			<div class="gray fs-10 Bold uppercase">Количество заданий:</div>
			<br>
			<span class="fs-16 Bold pt10 blue"><?= $tasksCount ?> шт.</span>

		</div>

		<div class="flex-string wp50 flx-2 p10" data-id="callsDo" data-tip="">

			<div class="gray fs-10 Bold uppercase">Прогресс выполнения:</div>
			<br>
			<span class="fs-16 Bold pt10 broun"><?= $callsDo ?> / <?= $callsAll ?> шт. </span>
			<?php if ( $callsAll > 0 ) {
				?>
				<span class="sparkpie" data-value="<?= $callsDo ?>,<?= $callsAll - $callsDo ?>"></span>
				<?= round( $callsDo / ($callsAll / 100), 1 ) ?> %
				<?php
			}
			?>
		</div>

		<div class="flex-string wp50 flx-2 p10" data-id="do" data-tip="param" id="field">

			<div class="gray fs-10 Bold uppercase">Обработанных заданий:</div>
			<br>
			<span class="fs-16 Bold pt10 green"><?= $tasksDo ?> шт. </span>
			<?php if ( $tasksDo > 0 ) {
				?>
				<span class="sparkpie" data-value="<?= $tasksDo ?>,<?= $tasksCount - $tasksDo ?>"></span>
				<?= round( $tasksDo / ($tasksCount / 100), 1 ) ?> %
				<?php
			}
			?>

		</div>

		<div class="flex-string wp50 flx-2 p10" data-id="active" data-tip="param" id="field">

			<div class="gray fs-10 Bold uppercase">Заданий в работе:</div>
			<br>
			<span class="fs-16 Bold pt10 red"><?= $tasksActive ?> шт. </span>
			<?php if ( $tasksActive > 0 ) {
				?>
				<span class="sparkpie" data-value="<?= $tasksActive ?>,<?= $tasksCount - $tasksActive ?>"></span>
				<?= round( $tasksActive / ($tasksCount / 100), 1 ) ?> %
				<?php
			}
			?>
		</div>


	</div>

	<div class="pull-aright mt5">
		<ul class="group">
			<li data-id="today">Сегодня</li>
			<li data-id="calendarweek">Неделя</li>
			<li data-id="month">Месяц</li>
			<li data-id="quart">Квартал</li>
			<li data-id="year">Год</li>
		</ul>
	</div>

	<script>

		includeJS("/assets/js/jquery.sparkline.min.js");

		$(document).ready(function () {

			var urll = "vigets/<?= $thisfile ?>";
			var $stat = $('#statcallcenter');
			var $param = $('div[data-tip="param"]');

			// Выбор периода времени
			$stat.find('ul.group').find('li[data-id="<?=$setperiod?>"]').addClass('active');

			$stat.find('li').bind('click', function () {

				var id = $(this).data('id');

				setCookie('parameterInterval', id, {"expires": 1000000});

				$stat.load('/content/vigets/viget.statcallcenter.php');

			});

			/*tooltips*/
			$stat.closest('.viget').find('.tooltips').append("<span></span>");
			$stat.closest('.viget').find('.tooltips:not([tooltip-position])').attr('tooltip-position', 'bottom');
			$stat.closest('.viget').find('.tooltips').mouseenter(function () {
				$(this).find('span').empty().append($(this).attr('tooltip'));
			});

			var id;
			var value = [];

			$stat.find('.sparkpie').each(function () {

				value = $(this).data('value').split(",");

				$(this).sparkline(value, {
					type: 'pie',
					width: '30px',
					height: '30px',
					offset: '-90',
					sliceColors: ['limegreen', 'red']
				});

			});

			// Детализация
			$param.off('click');
			$param.on('click', function () {

				var id = $(this).data('id');
				var str = '?action=info&param=' + id;
				doLoad(urll + str);

			});
		});

	</script>
	<?php
	exit();
}