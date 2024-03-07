<?php
/* ============================ */
/*         SalesMan CRM         */
/* ============================ */
/* (C) 2016 Vladislav Andreev   */
/*       SalesMan Project       */
/*        www.isaler.ru         */
/*        ver. 2017.x           */

/* ============================ */

use Salesman\Elements;

error_reporting(E_ERROR);
header("Pragma: no-cache");

if (empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) != 'xmlhttprequest') {

	print '<div class="bad" align="center"><br>Доступ запрещен.<br>Обратитесь к администратору.<br><br></div>';
	exit();

}

$rootpath = dirname(__DIR__, 2);

include $rootpath."/inc/config.php";
include $rootpath."/inc/dbconnector.php";
include $rootpath."/inc/auth.php";
include $rootpath."/inc/func.php";
include $rootpath."/inc/settings.php";

$thisfile = basename(__FILE__);

$page = (int)$_GET['page'];
$word = (string)$_GET['word'];
$word = str_replace(" ", "%", $word);
$ord  = (string)$_GET['ord'];
if ($ord == '') {
	$ord = "datum";
} //параметр сортировки

$tuda    = (string)$_GET['tuda'];
$iduser  = (int)$_GET['iduser'];
$filter  = (string)$_GET['filter'];
$type    = (array)$_GET['type'];
$speriod = $_GET['speriod'];

$da1 = $_REQUEST['da1'];
$da2 = $_REQUEST['da2'];

if (!isset($iduser)) {
	$iduser = 0;
}

//неделя
$week = getPeriod('calendarweek');
$w1   = $week[0];
$w2   = $week[1];

$week = getPeriod('calendarweekprev');
$w1o  = $week[0];
$w2o  = $week[1];

//текущий месяц
$m  = getPeriod('month');
$m1 = $m[0];
$m2 = $m[1];

//предыдущий месяц
$mo  = getPeriod('prevmonth');
$m1o = $mo[0];
$m2o = $mo[1];

//квартал текущий
$q  = getPeriod('quart');
$q1 = $q[0];
$q2 = $q[1];

//квартал текущий
$q  = getPeriod('prevquart');
$q3 = $q[0];
$q4 = $q[1];

//год
$y  = getPeriod('year');
$y1 = $y[0];
$y2 = $y[1];

$types = [
	"Авторизация"               => "icon-light-up green",
	"Начало дня"                => "icon-light-up green",
	"Выход"                     => "icon-off blue",
	"Администрирование"         => "icon-cog-alt red",
	"Восстановление пароля"     => "icon-arrows-ccw blue",
	"Экспорт организаций"       => "icon-download-2 fiolet",
	"Экспорт персон"            => "icon-download-2 fiolet",
	"Экспорт сделок"            => "icon-download-2 fiolet",
	"Импорт организаций"        => "icon-upload-2 fiolet",
	"Импорт прайса"             => "icon-upload-2 orange",
	"Импорт Входящего интереса" => "icon-upload-2 orange",
	"Скачивание БД"             => "icon-buffer gray2",
	"Удаление организации"      => "icon-trash-1 red",
	"Удаление персоны"          => "icon-trash-1 red",
	"Удаление сделки"           => "icon-trash-1 red"
];

$sort1 = '';

if ($iduser > 0) {
	$sort1 .= " and iduser = '$iduser'";
}

if ($filter != '') {
	$sort1 .= " and (type LIKE '%$filter%' or content LIKE '%$filter%')";
}

if ($word != "") {
	$sort1 .= " and content LIKE '%$word%'";
}

if (!empty($type)) {
	$sort1 .= " and type IN (".yimplode(",", $type, "'").")";
}


if ($da1 != "" && $da2 != "") {
	$sort1 .= " AND datum BETWEEN '$da1 00:00:00' and '$da2 23:59:59'";
}

elseif ($da1 != "" && $da2 == "") {
	$sort1 .= " AND datum >= '$da1 00:00:00'";
}

elseif ($da1 == "" && $da2 != "") {
	$sort1 .= " AND datum <= '$da2 23:59:59'";
}


$lines_per_page = 30; //Стоимость записей на страницу

$query     = "SELECT * FROM {$sqlname}logs WHERE id > 0 $sort1 and identity = '$identity' ORDER BY datum DESC";
$result    = $db -> query($query);
$all_lines = $db -> numRows($result);

if (empty($page) || $page <= 0) {
	$page = 1;
}
else {
	$page = (int)$page;
}

$page_for_query = $page - 1;
$lpos           = $page_for_query * $lines_per_page;

$query .= " LIMIT $lpos,$lines_per_page";

$result      = $db -> getAll($query);
$count_pages = ceil($all_lines / $lines_per_page);

$ss = ( $tuda == 'desc' ) ? '&#9660;' : '&#9650;';
?>

<DIV class="infodiv enable--select mb10">
	
	<FORM method="post" enctype="multipart/form-data" name="lForm" id="lForm">
		<input type="hidden" name="speriod" id="speriod" value="<?= $speriod ?>">
		
		<div class="flex-container wp80">
			
			<div class="flex-string wp30">
				
				<div class="Bold uppercase fs-07 gray2 mb2">Поиск</div>
				<input type="text" id="filter" name="filter" class="wp97" value="<?= $filter ?>" onkeydown="if(event.keyCode==13){ changepagepay('1'); return false }">
			
			</div>
			<div class="flex-string wp30">
				
				<div class="Bold uppercase fs-07 gray2 mb2">Сотрудник</div>
				<span class="select wp97">
				<?php
				$u = new Elements();
				print $u -> UsersSelect("iduser", [
					"sel"   => ( $iduser > 0 ) ? $iduser : '-1',
					"class" => "wp100"
				]);
				?>
				</span>
			
			</div>
			<div class="flex-string wp30">
				
				<div class="Bold uppercase fs-07 gray2 mb2">События</div>
				<div class="ydropDown selects wp97 fs-10">
					
					<span class="ydropCount"><?= count($type) ?> выбрано</span><i class="icon-angle-down pull-aright"></i>
					<div class="yselectBox">
						<div class="right-text">
							<div class="ySelectAll w0 inline" title="Выделить всё"><i class="icon-plus-circled"></i>Всё</div>
							<div class="yunSelect w0 inline" title="Снять выделение"><i class="icon-minus-circled"></i>Ничего</div>
						</div>
						<?php
						foreach (array_keys($types) as $item) {

							print '
							<div class="ydropString ellipsis">
								<label>
									<input name="type[]" type="checkbox" id="type[]" value="'.$item.'" '.( in_array($item, $type) ? "checked" : "" ).'>
									<span><i class="'.$types[$item].'"></i>'.$item.'</span>
								</label>
							</div>
							';

						}
						?>
					</div>
				
				</div>
			
			</div>
			<div class="flex-string wp50">
				
				<div class="Bold uppercase fs-07 gray2 mb2">Период</div>
				<div class="row" id="period">
					
					<div class="inline w160">
						
						<INPUT name="da1" type="text" id="da1" value="<?= $da1 ?>" onchange="preconfigpage()" class="dstart inputdate wp100">
					
					</div>
					<div class="inline w20 text-center pt10 flh-20">&divide;</div>
					<div class="inline w160">
						
						<INPUT name="da2" type="text" id="da2" value="<?= $da2 ?>" onchange="preconfigpage()" class="dend inputdate wp100">
					
					</div>
					<div class="inline pl10">

						<span class="select bgwhite">
						<select name="period" id="period" class="w140 p5 pt7 clean bgwhite" data-goal="period" data-action="period" data-selected="<?= $speriod ?>">
							<option selected="selected" value="">-за всё время-</option>
							<option data-period="today">Сегодня</option>
							<option data-period="yestoday">Вчера</option>

							<option data-period="calendarweekprev">Неделя прошлая</option>
							<option data-period="calendarweek">Неделя текущая</option>

							<option data-period="monthprev">Месяц прошлый</option>
							<option data-period="month">Месяц текущий</option>

							<option data-period="quartprev">Квартал прошлый</option>
							<option data-period="quart">Квартал текущий</option>

							<option data-period="year">Год</option>
						</select>
						</span>
					
					</div>
				
				</div>
			
			</div>
		
		</div>
		
		<hr>
		
		<a href="javascript:void(0)" onclick="changepagepay(1)" class="button">Применить</a>
		<a href="javascript:void(0)" onclick="resetForma()" class="button cancelbtn">Сброс</a>
	
	</FORM>

</DIV>

<div>
	
	<TABLE id="zebra">
		<thead class="hidden-iphone sticked--top">
		<TR class="th40">
			<TH class="w130">Дата</TH>
			<TH class="w80">Давность</TH>
			<TH class="w200"><b>Тип события</b></TH>
			<TH class="w160"><b>Сотрудник</b></TH>
			<TH class="text-left">Описание</TH>
		</TR>
		</thead>
		<TBODY>
		<?php
		foreach ($result as $data) {
			?>
			<TR class="ha th40">
				<TD title="<?= get_sfdate($data['datum']) ?>"><SPAN class="ellipsis"><?= get_sfdate($data['datum']) ?></SPAN></TD>
				<TD><?= diffDateTime2($data['datum']) ?></TD>
				<TD><SPAN title="<?= $data['type'] ?>" class="ellipsis"><i class="<?= $types[$data['type']] ?>"></i>&nbsp;<?= $data['type'] ?></SPAN></TD>
				<TD><SPAN title="<?= current_user($data['iduser']) ?>" class="ellipsis"><B><?= current_user($data['iduser']) ?></B></SPAN></TD>
				<TD><SPAN title="<?= $data['content'] ?>" class="ellipsis"><?= $data['content'] ?></SPAN></TD>
			</TR>
			<?php
			$img = '';
		}
		?>
		</TBODY>
	</TABLE>

</div>

<div id="pagecontainer" class="pagediv">
	
	<div class="page mainbg" id="pagediv">
		<?php

		if ($count_pages == 0) {
			$count_pages = 1;
		}

		print " Стр.".$page." из ".$count_pages."&nbsp;";

		if ($count_pages > 1) {

			for ($g = 1; $g <= $count_pages; $g++) {

				if ($page == $g && $g == 1) {
					?>
					&nbsp;<a href="javascript:void(0)" onClick="changepagepay('<?= ( $g + 1 ) ?>')" title="Следующая"><i class="icon-angle-right"></i></a>&nbsp;&nbsp;
					<a href="javascript:void(0)" onClick="changepagepay('<?= $count_pages ?>')" title="Последняя"><i class="icon-angle-double-right"></i></a>&nbsp;
					<?php
				}
				if ($page == $g && $g == 2) {
					?>
					&nbsp;<a href="javascript:void(0)" onClick="changepagepay('1')" title="Начало"><i class="icon-angle-double-left"></i></a>&nbsp;
					<?php
					if ($count_pages > 2) { ?>
						&nbsp;<a href="javascript:void(0)" onClick="changepagepay('<?= ( $g + 1 ) ?>')" title="Следующая"><i class="icon-angle-right"></i></a>&nbsp;&nbsp;
						<a href="javascript:void(0)" onClick="changepagepay('<?= $count_pages ?>')" title="Последняя"><i class="icon-angle-double-right"></i></a>&nbsp;
						<?php
					}
				}
				if ($page == $g && $g > 2) {
					?>
					&nbsp;<a href="javascript:void(0)" onClick="changepagepay('1')" title="Начало"><i class="icon-angle-double-left"></i></a>&nbsp;&nbsp;
					<a href="javascript:void(0)" onClick="changepagepay('<?= ( $g - 1 ) ?>')" title="Предыдущая"><i class="icon-angle-left"></i></a>&nbsp;
					<?php
					if ($g < $count_pages) { ?>
						&nbsp;<a href="javascript:void(0)" onClick="changepagepay('<?= ( $g + 1 ) ?>')" title="Следующая"><i class="icon-angle-right"></i></a>&nbsp;&nbsp;
						<a href="javascript:void(0)" onClick="changepagepay('<?= $count_pages ?>')" title="Последняя"><i class="icon-angle-double-right"></i></a>&nbsp;
						<?php
					}
				}
			}
		}
		?>
	</div>

</div>

<script>
	
	$(function () {
		
		var $def = $('#period[data-action="period"]').data('selected');
		$('option[data-period="' + $def + '"]', '#period').prop('selected', true);
		
	});
	
	$('.inputdate').each(function () {
		
		if (!isMobile)
			$(this).datepicker({
				dateFormat: 'yy-mm-dd',
				numberOfMonths: 2,
				firstDay: 1,
				dayNamesMin: ['Вс', 'Пн', 'Вт', 'Ср', 'Чт', 'Пт', 'Сб'],
				monthNamesShort: ['Январь', 'Февраль', 'Март', 'Апрель', 'Май', 'Июнь', 'Июль', 'Август', 'Сентябрь', 'Октябрь', 'Ноябрь', 'Декабрь'],
				changeMonth: true,
				changeYear: true,
				yearRange: '1940:2030',
				minDate: new Date(1940, 1 - 1, 1),
				showButtonPanel: true,
				currentText: 'Сегодня',
				closeText: 'Готово'
			});
		
	});
	
	$(document).on('change', '#period', function () {
		
		var $period = $('option:selected', this).data('period');
		var $goal = $(this).data('goal');
		var $elm = $('#' + $goal);
		
		if ($period !== undefined) {
			
			$elm.find('.dstart').val(period[$period][0]);
			$elm.find('.dend').val(period[$period][1]);
			
		}
		else {
			
			$elm.find('.dstart').val('');
			$elm.find('.dend').val('');
			
		}
		
		$('#speriod').val($period);
		
		return false;
		
	});
	
	function resetForma() {
		
		$('#lForm')[0].reset();
		$('input[type="checkbox"]').prop("checked", false);
		$('input[type="text"]').val("");
		$('select option:selected').val("");
		
		changepagepay(1);
		
	}
	
	function changepagepay(num) {
		
		$('#contentdiv').load('/content/admin/system.logs.php?page=' + num + '&' + $('#lForm').serialize());
		
	}
</script>