<?php
/* ============================ */
/*         SalesMan CRM         */
/* ============================ */
/* (C) 2016 Vladislav Andreev   */
/*       SalesMan Project       */
/*        www.isaler.ru         */
/*        ver. 2017.x           */
/* ============================ */

$title = 'События';

$tip = $_REQUEST['tip'];

global $rootpath;
require_once $rootpath."/inc/head.php";
flush();
?>
<DIV class="" id="rmenu">

	<div class="tabs">

		<a href="javascript:void(0)" class="lpToggler open" title="Фильтры"><i class="icon-toggler"></i></a>
		<a href="javascript:void(0)" onclick="configpage();" title="Обновить представление"><i class="icon-arrows-cw"></i></a>

		<?php require_once $rootpath."/content/leftnav/leftpop.php"; flush();?>

	</div>

	<?php require_once $rootpath."/content/leftnav/counters.php"; flush();?>

</DIV>

<DIV class="ui-layout-north mainbg">

	<?php require_once $rootpath."/inc/menu.php"; flush();?>

</DIV>
<DIV class="ui-layout-west disable--select compact">

	<?php require_once $rootpath."/content/leftnav/history.php"; flush();?>

</DIV>
<DIV class="ui-layout-center disable--select compact" style="overflow: hidden">

	<DIV class="mainbg listHead p0 hidden-iphone">

		<div class="flex-container p10">

			<div class="column flex-column wp50 fs-11 border-box">
				<b class="shado">События</b><span class="hidden-iphone"> / </span><span id="tips">История активности</span>&nbsp;
			</div>
			<div class="column flex-column wp50 text-right">

				<a href="javascript:void(0)" onclick="getHistoryCSV()" title="Скачать в формате CSV" class="hidden-iphone"><i class="icon-download blue"></i>Экспорт</a>&nbsp;&nbsp;
				<a href="javascript:void(0)" title="Обновить представление" onclick="page_refresh();"><i class="icon-arrows-cw blue"></i><span class="hidden-iphone">Обновить</span></a>&nbsp;

			</div>

		</div>

	</DIV>

	<form name="cform" id="cform">
	<div class="nano relativ" id="clientlist">

		<div class="nano-content">
			<div class="ui-layout-content" id="contentdiv"></div>
		</div>

	</div>
	</form>

	<div class="pagecontainer">
		<div class="page pbottom mainbg" id="pagediv"></div>
	</div>

</DIV>
<DIV class="ui-layout-east"></DIV>
<DIV class="ui-layout-south"></DIV>

<div id="startinto" class="hidden">

	<div class="relativ">

		<div class="showintro" title="Запустить гид для знакомства с CRM">
			<span><i class="icon-help-circled-1"></i></span>Знакомство
		</div>
		<div id="hideintro" title="Больше не показывать гид"><i class="icon-cancel-circled"></i></div>

	</div>

</div>

<script>

var $display = 'history';

if (isMobile || $(window).width() < 767) {

	$('.lpToggler').toggleClass('open');

}

includeJS('/assets/js/jquery.liTextLength.js');

$( function() {

	if(isPad)
		$('.lpToggler').trigger('click');

	//$('.ui-layout-center').append('<div class="tableHeader" style="position:absolute; width:100%"></div>');

	$.Mustache.load('/content/tpl/tpl.history.mustache');

	constructSpace();

	configpage();

	$('.inputdate').each(function(){

		if(isMobile !== true)
			$(this).datepicker({ dateFormat: 'yy-mm-dd', numberOfMonths:2, firstDay: 1, dayNamesMin: ['Вс','Пн','Вт','Ср','Чт','Пт','Сб'], monthNamesShort: ['Январь','Февраль','Март','Апрель','Май','Июнь','Июль','Август','Сентябрь','Октябрь','Ноябрь','Декабрь'], changeMonth: true, changeYear: true, yearRange: '1940:2030', minDate: new Date(1940, 1 - 1, 1), showButtonPanel: true, currentText: 'Сегодня', closeText: 'Готово'});

	});

	$(".nano").nanoScroller();

	/*tooltips*/
	$('#pptt .tooltips').append("<span></span>");
	$('#pptt .tooltips:not([tooltip-position])').attr('tooltip-position','bottom');
	$("#pptt .tooltips").on('mouseenter', function(){
		$(this).find('span').empty().append($(this).attr('tooltip'));
	});
	/*tooltips*/

	changeMounth();


});


function constructSpace(){

	var hf = $('.ui-layout-center').actual('height') - $('.contaner[data-id="stat"]').actual('outerHeight') - 30;
	$('.contaner[data-id="filter"]').css({"height": hf + "px", "max-height": hf + "px"});

	$('.nano').nanoScroller();

}

$(window).on('resize', function(){

	if(!isMobile) constructSpace();

});
$(window).on('resizeend', 200, function(){

	if(!isMobile) {

		constructSpace();
		$('.ui-layout-center').trigger('onPositionChanged');

	}

});

$('.lpToggler').on('click', function(){

	if (isMobile || $(window).width() < 767) {

		$('.ui-layout-west').toggleClass('open');
		$('.ui-layout-center').toggleClass('open');

	}
	else {

		$('.ui-layout-west').toggleClass('compact simple');
		$('.ui-layout-center').toggleClass('compact simple');

	}
	$(this).toggleClass('open');

});

$('.ui-layout-center').onPositionChanged(function(){

	if(this.resizeTO) clearTimeout(this.resizeTO);
	this.resizeTO = setTimeout(function() {


	}, 200);

	$('.ui-layout-content').css({"width": "100%"});
	$('#list_header').css({"width": "100%"});

});

$(".showintro").on('click', function() {

	var intro = new introJs();

	intro.setOptions({'nextLabel':'Дальше','prevLabel':'Вернуть','skipLabel':'Пропустить','doneLabel':'Я понял','showStepNumbers':false});
	intro.start().goToStep(4)
	.onbeforechange(function(targetElement) {

		switch($(targetElement).attr("data-step")) {
			case "2":
				$('#menuclients').css('display','none');
			break;
			case "1":
			case "6":
			case "7":
			case "8":
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

function configpage(){

	$('#contentdiv').parent(".nano").nanoScroller({ scroll: 'top' });

	var str = $('#pageform').serialize();
	var url = '/content/lists/list.history.php';

	$('#contentdiv').append('<div class="contentloader"><img src="/assets/images/Services.svg" width="50px" height="50px"></div>');

	var cdheight = $('#contentdiv').height();
	var cdwidth = $('#contentdiv').width();

	$('.contentloader').height(cdheight).width(cdwidth);

	/*------------*/

	if( $('.lpToggler').hasClass('open') && isMobile ) $('.lpToggler').trigger('click');

	$.getJSON(url + '?' + str, function(viewData) {

		$('#contentdiv').empty().mustache('historyTpl', viewData);

		var page = viewData.page;
		var pageall = viewData.pageall;

		var pg = 'Стр. '+page+' из '+pageall;

		if(pageall > 1){

			var prev = page - 1;
			var next = page + 1;

			if(page === 1)
				pg = pg + '&nbsp;<a href="javascript:void(0)" onclick="change_page(\''+next+'\')" title="Следующая"><i class="icon-angle-right"></i></a>&nbsp;&nbsp;<a href="javascript:void(0)" onclick="change_page(\''+pageall+'\')" title="Последняя"><i class="icon-angle-double-right"></i></a>&nbsp;';

			else if(page === pageall)
				pg = pg + '&nbsp;<a href="javascript:void(0)" onclick="change_page(\'1\')" title="Начало"><i class="icon-angle-double-left"></i></a>&nbsp;&nbsp;<a href="javascript:void(0)" onclick="change_page(\''+prev+'\')" title="Предыдущая"><i class="icon-angle-left"></i></a>&nbsp;';

			else
				pg = '&nbsp;<a href="javascript:void(0)" onclick="change_page(\'1\')" title="Начало"><i class="icon-angle-double-left"></i></a>&nbsp;&nbsp;<a href="javascript:void(0)" onclick="change_page(\''+prev+'\')" title="Предыдущая"><i class="icon-angle-left"></i></a>&nbsp;'+ pg+ '&nbsp;<a href="javascript:void(0)" onclick="change_page(\''+next+'\')" title="Следующая"><i class="icon-angle-right"></i></a>&nbsp;&nbsp;<a href="javascript:void(0)" onclick="change_page(\''+pageall+'\')" title="Последняя"><i class="icon-angle-double-right"></i></a>&nbsp;';

		}
		$('#pagediv').html(pg);

	})
		.done(function() {

			$(".nano").nanoScroller();

			if (isMobile)
				$('table.list_header').rtResponsiveTables();

			$(".dot-ellipsis").liTextLength({
				length: $w[0],
				afterLength: '...',
				fullText:false
			});

		});
}
/*
Вызываем при применении фильтров, чтобы начинать с 1 страницы
 */
function preconfigpage() {

	$('#page').val('1');
	configpage();

}
function page_refresh(){

	configpage();

}
function change_page(page){

	$('#page').val(page);
	configpage();

}

function clearFilter(){

	if($('#ifilter').hasClass('icon-eye-off')){

		$('.taskss').attr('checked', false);
		$('#ifilter').removeClass('icon-eye-off').addClass('icon-eye');

		configpage();

	}
	else{

		$('.taskss').attr('checked', true);
		$('#ifilter').addClass('icon-eye-off').removeClass('icon-eye');

		configpage();

	}
}

function getHistoryCSV(){

	var st = $('#pageform').serialize();
	window.location.assign('/content/core/core.history.php?action=export&'+st);

}

</script>
<?php require_once $rootpath."/inc/panel.php"; flush();?>
</body>
</html>