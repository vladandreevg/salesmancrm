<?php
/* ============================ */
/*         SalesMan CRM         */
/* ============================ */
/* (C) 2016 Vladislav Andreev   */
/*       SalesMan Project       */
/*        www.isaler.ru         */
/*        ver. 2017.x           */
/* ============================ */

$title = 'Отчеты';

global $rootpath;
require_once $rootpath."/inc/head.php";
flush();

//Подключаем или загружаем базу отчетов
$rURL = "https://salesman.pro/docs/reports/";

//файл кэша
$cash = "cash/reports.json";

$size = filesize( $cash );
//print "size = $size\n";

if ( !file_exists( $cash ) || (int)$size == 0 ) {

	//print "file not exist\n";

	a1:

	//print sendRequestStream($rURL."reportDB.json");
	//print file_get_contents( $rURL."reportDB.json" );

	$reportList = str_replace( [
		"  ",
		"\t",
		"\n",
		"\r"
	], "", sendRequestStream($rURL."reportDB.json") );
	$reportBase = json_decode( $reportList, true );

	//запишем в файл
	file_put_contents( $cash, $reportList );

	//print $rURL."reportDB.json\n";
	//print $reportList;

}
else {

	$time = filemtime( $cash );
	$diff = datetimetoday( unix_to_datetime($time) );

	//print "time = $time\n";
	//print "date = ".unix_to_datetime($time)."\n";
	//print "diff = $diff\n";

	if ( abs( $diff ) > 30 )
		goto a1;

	$reportList = str_replace( [
		"  ",
		"\t",
		"\n",
		"\r"
	], "", file_get_contents( $cash ) );
	$reportBase = json_decode( $reportList, true );

}

//print $rURL."reportDB.json";
//print_r($reportList);

if($reportList == ''){
	$reportList = '{}';
}

if ( $acs_analitics != 'on' ) {
	print '
	<div class="warning text-left" style="width:600px">
		<span><i class="icon-attention red icon-5x pull-left"></i></span>
		<b class="red uppercase">Внимание:</b><br><br>К сожалению Вы не можете просматривать данную информацию<br>У Вас отсутствует разрешение.<br>
	</div>
	<script type="text/javascript">
		$(".warning").center();
	</script>
	';
	exit;
}
?>
<style>

	.popup {

		position   : fixed;
		height     : 100vh;
		width      : 100vw;
		top        : 0;
		left       : 0;
		display    : none;
		text-align : center;

	}

	.popup_bg {

		background : rgba(0, 0, 0, 0.4);
		position   : absolute;
		z-index    : 70;
		height     : 100%;
		width      : 100%;

	}

	.popup_img {

		position   : relative;
		margin-top : 3%;
		z-index    : 100;
		max-height : 90vh;
		max-width  : 90vw;

	}

</style>
<DIV class="" id="rmenu">

	<div class="tabs">

		<a href="javascript:void(0)" class="lpToggler open" title="Фильтры"><i class="icon-toggler"></i></a>

		<?php
		require_once $rootpath."/content/leftnav/leftpop.php";
		flush();
		?>

	</div>

	<?php
	require_once $rootpath."/content/leftnav/counters.php";
	flush();
	?>

</DIV>

<DIV class="ui-layout-north mainbg">

	<?php
	require_once $rootpath."/inc/menu.php";
	flush();
	?>

</DIV>

<FORM name="selectreport" id="selectreport" action="report_print.php" method="post" target="blank">
	<DIV class="ui-layout-west disable--select compact">

		<?php
		require_once $rootpath."/content/leftnav/reports.php";
		flush();
		?>

	</DIV>
	<DIV class="ui-layout-center disable--select compact" style="overflow: hidden">

		<DIV class="mainbg listHead p0">

			<div class="pt5 pb10 flex-container">

				<div class="flex-string wp100">

					&nbsp;<b>Выбор отчета:</b>&nbsp;
					<select name="report" id="report" class="w300" data-step="9" data-intro="<h1>Выбор отчета.</h1>Выбор доступных и активных отчетов" data-position="right">
						<option value="" selected>--Выбрать отчет--</option>
						<?php
						if ( $acs_analitics == 'on' ) {

							$data  = [
								'Планирование',
								'Активности',
								'Продажи',
								'Эффективность',
								'Рейтинг',
								'Связи'
							];
							$counts = count( $data );

							foreach ($data as $xda) {

								print '<optgroup label="'.$xda.'">';

								$result = $db -> query( "select * from ".$sqlname."reports where ron = 'yes' and category = '".$xda."' and identity = '$identity' ORDER by title" );
								if ( $db -> numRows( $result ) > 0 ) {

									while ($da = $db -> fetch( $result )) {

										$show = $title = "";

										$r     = $db -> getRow( "SELECT roles, users FROM ".$sqlname."reports WHERE rid = '$da[rid]' AND identity = '$identity'" );
										$roles = yexplode( ",", (string)$r["roles"] );
										$users = yexplode( ",", (string)$r["users"] );

										if ( !empty( $roles ) || !empty( $users ) ) {

											$show  = (in_array( $tipuser, $roles ) || in_array( $iduser1, $users )) ? "" : "disabled";
											$title = (in_array( $tipuser, $roles ) || in_array( $iduser1, $users )) ? "" : "[ x ] ";

										}

										if ( file_exists( 'reports/'.$da['file'] ) ) {
											print '<option value="'.$da['file'].'" '.$show.'>'.$title.$da['title'].'</option>';
										}

									}

								}
								print '</optgroup>';

							}

						}
						?>
					</select>&nbsp;&nbsp;
					<div onclick="generateReport()" class="button white"><i class="icon-ok"></i>Сформировать</div>&nbsp;
					<A href="javascript:void(0)" onclick="forprint()" class="button greenbtn white"><i class="icon-print"></i>Печать</A>

					<div class="inline" id="reportInfo">

						<A href="javascript:void(0)" onclick="reportInfo()" class="button orangebtn"><i class="icon-info-circled-1"></i>Об отчете</A>&nbsp;

					</div>

				</div>


			</div>

		</DIV>

		<div class="nano" id="clientlist">

			<div class="nano-content">
				<div class="ui-layout-content ui-border nano-content original p5 enable--select" id="contentdiv"></div>
			</div>

		</div>

		<div class="setSaveButton">

			<a href="javascript:void(0)" onclick="$('#clientlist.nano').nanoScroller({scrollTo: 'top'});" title="Наверх"><i class="icon-up-open-big"></i></a>

		</div>

	</DIV>
</FORM>

<DIV class="ui-layout-east"></DIV>
<DIV class="ui-layout-south"></DIV>

<div id="startinto">

	<div class="relativ">

		<div class="showintro" title="Запустить гид для знакомства с CRM">
			<span><i class="icon-help-circled-1"></i></span>Знакомство
		</div>
		<div id="hideintro" title="Больше не показывать гид"><i class="icon-cancel-circled"></i></div>

	</div>

</div>

<script>

	var $display = 'reports';
	var $toggler = $('.lpToggler');
	var $elcenter = $('.ui-layout-center');
	var $elwest = $('.ui-layout-west');
	var $content = $('#contentdiv');

	var $base = <?=$reportList?>;
	var $baseURL = '<?=$rURL?>';
	var $currentReport = '';
	var $dfilter = 0;

	if (isMobile || $(window).width() < 767) {

		$toggler.toggleClass('open');

	}

	$( function() {

		$('.inputdate').each(function(){

			if(isMobile !== true)
				$(this).datepicker({ dateFormat: 'yy-mm-dd', numberOfMonths:2, firstDay: 1, dayNamesMin: ['Вс','Пн','Вт','Ср','Чт','Пт','Сб'], monthNamesShort: ['Январь','Февраль','Март','Апрель','Май','Июнь','Июль','Август','Сентябрь','Октябрь','Ноябрь','Декабрь'], changeMonth: true, changeYear: true, yearRange: '1940:2030', minDate: new Date(1940, 1 - 1, 1), showButtonPanel: true, currentText: 'Сегодня', closeText: 'Готово'});

		});

		constructSpace();
		changeMounth();

		$('#report').trigger('change');

		$(".nano").nanoScroller();
		$(".nano2").nanoScroller();

		$(document).off('click', '.imgView');
		$(document).on('click', '.imgView', function () {

			var img = $(this).find('img');
			var src = img.attr('src'); // Достаем из этого изображения путь до картинки

			$("#subwindow").append('' +
				'<div class="popup">' + //Добавляем в тело документа разметку всплывающего окна
				'   <div class="popup_bg"></div>' + // Блок, который будет служить фоном затемненным
				'   <img src="' + src + '" class="popup_img hand" onclick="$(\'.popup\').remove()">' + // Само увеличенное фото
				'</div>');

			$(".popup").fadeIn(400); // выводим изображение

			$(".popup_bg").on('click', function () {    // Событие клика на затемненный фон

				$(".popup").fadeOut(400);    // убираем всплывающее окно

				setTimeout(function () {    // Выставляем таймер
					$(".popup").remove(); // Удаляем разметку всплывающего окна
				}, 800);

				reportInfo();

			});

		});

	});

	function constructSpace() {

		var hw = $elcenter.width();
		var ht = $('.listHead').actual('outerHeight');
		var hh = $elcenter.actual('height');

		var hg = hh - ht;

		$elcenter.find('.tableHeader').css({"width": hw + "px", "top": ht + 'px', "left": "0px"});
		$('#clientlist').css({"height": hg + "px"});

		var hf = $elcenter.actual('height') - $('.contaner[data-id="stat"]').actual('outerHeight') - 30;
		$('.contaner[data-id="filter"]').css({"height": hf + "px", "max-height": hf + "px"});

	}

	$(window).on('resize', function () {

		constructSpace();

	});
	$(window).on('resizeend', 200, function () {

		constructSpace();

		$elcenter.trigger('onPositionChanged');

	});


	$('#period').on('change', function () {

		$('#da1').val($('#period option:selected').data('da1'));
		$('#da2').val($('#period option:selected').data('da2'));

		//configpage();

	});
	$('#report').on('change', function () {

		$currentReport = $(this).val().replace(".php", "");

		if ($currentReport !== '') {

			if ($base[$currentReport] !== undefined) $('#reportInfo').removeClass('hidden');
			else $('#reportInfo').addClass('hidden');

			generateReport();

		}

	});

	$toggler.on('click', function () {

		if (isMobile || $(window).width() < 767) {

			$elwest.toggleClass('open');
			$elcenter.toggleClass('open');

		}
		else {

			$elwest.toggleClass('compact simple');
			$elcenter.toggleClass('compact simple');

		}
		$(this).toggleClass('open');

	});

	$elcenter.onPositionChanged(function () {

		if (this.resizeTO) clearTimeout(this.resizeTO);
		this.resizeTO = setTimeout(function () {

			var leftOffset = $elcenter.position().left;

			/*if ($elcenter.hasClass('open')) {

				$('.fixedHeader2').css({"left": leftOffset + "px"});

			}
			else {

				$('.fixedHeader2').css({"left": leftOffset + "px"});

			}*/

			var hw = $elcenter.width();

			//$('.ui-layout-center').find('.tableHeader').css({"width": hw + "px"});
			//$('.ui-layout-content').css({"width": hw + "px"});
			//$('#list_header').css({"width": hw + "px"});

		}, 200);

	});

	function generateReport() {

		$('#clients_list\\[\\] option').prop('selected', true);
		$('#persons_list\\[\\] option').prop('selected', true);

		var str = $('#selectreport').serialize();
		var custom = $('#customForm').serialize();
		var report = $('#report option:selected').val();

		$('#message').empty().css('display', 'block').append('<div id="loader" class="loader"><img src=/assets/images/loader.gif> Вычисление...</div>');

		$content.empty().append('<div id="loader" class="loader"><img src=/assets/images/loading.gif> Вычисление...</div>');

		$.get('/reports/' + report + '?' + str + '&' + custom, function (data) {

			$content.html(data);

			$('#message').empty().css('display', 'none');
			$(".nano").nanoScroller();
			clearNBSP();

		});

		return false;
	}

	function generate_csv() {

		$('#clients_list\\[\\] option').prop('selected', true);
		$('#persons_list\\[\\] option').prop('selected', true);

		var str = $('#selectreport').serialize();
		var report = $('#report option:selected').val();

		window.open('/reports/' + report + '?' + str + '&action=get_csv');

		$('#message').empty().css('display', 'none');

		return false;
	}

	function toExcel() {

		$('#clients_list\\[\\] option').prop('selected', true);
		$('#persons_list\\[\\] option').prop('selected', true);

		var str = $('#selectreport').serialize();
		var report = $('#report option:selected').val();

		window.open('/reports/' + report + '?' + str + '&action=export');

		$('#message').empty().css('display', 'none');

		return false;
	}

	function forprint() {

		$('#clients_list\\[\\] option').prop('selected', true);
		$('#persons_list\\[\\] option').prop('selected', true);

		var str = $('#selectreport').serialize();
		var file = $('#report option:selected').val();
		var custom = $('#customForm').serialize();

		window.open('report_print?file=' + file + '&' + str + '&' + custom, '', 'Toolbar=1,Location=1,Directories=0,Status=0,Menubar=1,Scrollbars=1,Resizable=1');

		return false;
	}

	function addfstring() {

		var htmltr = '<tr id="fld' + $dfilter + '"><td><select id="field' + $dfilter + '" name="field\[\]" style="width:40%" onchange="loadpole(\'inputf' + $dfilter + '\',\'field' + $dfilter + '\')"><option value="">--выбор--</option><optgroup label="Актуальные <?=$lang['face']['DealsName'][0]?>"><option value="idcategory">Этап</option><option value="tip">Тип сделки</option><option value="direction">Направление</option><option value="partner">Партнер</option><option value="con_id">Контрактор</option><option value="mcid">Компания</option></optgroup><optgroup label="Закрытые <?=$lang['face']['DealsName'][0]?>"><option value="close">Статус</option><option value="sid">Статус закрытия</option></optgroup></select>&nbsp;<div id="inputf' + $dfilter + '" style="display:inline-block; width:45%"><input type="text" id="field_query\[\]" name="field_query\[\]" value="" style="width:90%" /></div>&nbsp;&nbsp;<a href="javascript:void(0)" onclick="removestring(\'fld' + $dfilter + '\')" title="Удалить параметр"><i class="icon-cancel-circled red"></i></a></td></tr>';

		$('#fields').append(htmltr);

		$dfilter++;

	}

	function removestring(string) {

		$('#' + string).remove();

	}

	function loadpole(string, pole) {

		$('#' + string).load('/reports/fieldselect.php?action=get_pole&pole=' + $('#' + pole).val());

	}

	function sdelete(str) {

		var url = '/reports/fieldselect.php?action=delete&tip=<?=$tip?>&seid=' + str;

		$.post(url, function (data) {

			$('#message').empty().css('display', 'block').html(data).fadeOut(10000);
			$('#resultdiv').load('/reports/fieldselect.php?tip=<?=$tip?>');
			$('#select_search').load('/reports/fieldselect.php?action=update_select&tip=<?=$tip?>');

		});

	}

	function reportInfo() {

		var $el = $('#subwindow');
		var $content;
		var $rep = $base[$currentReport];

		$content = '' +
			'<h1 class="m0 p0 mb10">' + $rep.name + '</h1>' +
			'<div class="em gray2 mb20"><b>Файл:</b> ' + $rep.file + '</div>' +
			'<div class="infodiv">' +
			'   <div class="Bold mb10">Описание:</div>' +
			'   <div class="flh-12">' + decodeHtml($rep.des) + '</div>' +
			'</div>' +
			'<div class="viewdiv hand imgView"><img src="' + $baseURL + '/images/' + $rep.img + '" class="wp100"></div>';

		$el.empty().css('z-index', '50').addClass('open').append('<div class="body" style="height:calc(100vh - 60px)">' + $content + '</div><div class="footer pl10" style="height:60px"><a href="javascript:void(0)" onclick="closeInfo()" class="button">Закрыть</a></div>');

	}

	function decodeHtml(str) {
		var map = {
			'&amp;': '&',
			'&lt;': '<',
			'&gt;': '>',
			'&quot;': '"',
			'&#039;': "'"
		};
		return str.replace(/&amp;|&lt;|&gt;|&quot;|&#039;/g, function (m) {
			return map[m];
		});
	}

	function closeInfo() {

		$('#subwindow').removeClass('open');

	}

	$(".showintro").on('click', function () {
		var intro = introJs();

		intro.setOptions({
			'nextLabel': 'Дальше',
			'prevLabel': 'Вернуть',
			'skipLabel': 'Пропустить',
			'doneLabel': 'Я понял',
			'showStepNumbers': false
		});
		intro.start().goToStep(4)
			.onbeforechange(function (targetElement) {

				switch ($(targetElement).attr("data-step")) {
					case "2":
						$('#menuclients').css('display', 'none');
						break;
					case "1":
					case "6":
						$(targetElement).show();
						break;
					case "7":
						$('div[data-id="filter2"]').trigger('click');
						$(targetElement).show();
						break;
					case "8":
						$('div[data-id="filter"]').trigger('click');
						$(targetElement).show();
						break;
					case "3":
						$("#subpan3").show();
						$(targetElement).show();
						break;
					case "4":
						$("#subpan3").hide();
						$(targetElement).show();
						break;
					case "5":
						$(targetElement).show();
						break;
					case "9":
						$('#sub3').show();
						$(targetElement).show();
						break;
					case "10":
						$(targetElement).show();
						break;
					case "11":
						$(targetElement).show();
						break;
				}
			})
	});

	function userSelectAll(box){

		var $elm = $('#'+box);

		$elm.find('input[type=checkbox]').prop('checked', true);

	}
	function userUnSelect(box){

		var $elm = $('#'+box);

		$elm.find('input[type=checkbox]').prop('checked', false);

	}

</script>
<?php
require_once $rootpath."/inc/panel.php";
flush();
?>
</body>
</html>