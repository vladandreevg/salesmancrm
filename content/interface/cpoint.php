<?php
/* ============================ */
/*         SalesMan CRM         */
/* ============================ */
/* (C) 2016 Vladislav Andreev   */
/*       SalesMan Project       */
/*        salesman.pro          */
/*        ver. 2018.x           */
/* ============================ */

$title="Контрольные точки";

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

	<?php require_once $rootpath."/content/leftnav/cpoint.php"; flush();?>

</DIV>
<DIV class="ui-layout-center disable--select compact" style="overflow: hidden">

	<DIV class="mainbg listHead p0 hidden-iphone">

		<div class="pt5 pb10 flex-container">
			<div class="column flex-column wp50 fs-11 pl5 border-box">
				<b class="shado" id="tips">Контрольные точки</b>&nbsp;
			</div>
			<div class="column flex-column wp50 text-right">

				<a href="javascript:void(0)" onclick="configpage()"><i class="icon-arrows-cw blue"></i> <span class="hidden-iphone">Обновить&nbsp;</span></a>&nbsp;

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

var $display = 'entry';

if(isMobile || $(window).width() < 767){

	$('.lpToggler').toggleClass('open');

}

$( function() {

	$.Mustache.load('/content/tpl/cpoint.mustache');

	//$('.ui-layout-center').append('<div class="tableHeader" style="position:absolute; width: 100%"></div>');

	constructSpace();

	configpage();

	$('.inputdate').each(function(){

		if(!isMobile) $(this).datepicker({ dateFormat: 'yy-mm-dd', numberOfMonths:2, firstDay: 1, dayNamesMin: ['Вс','Пн','Вт','Ср','Чт','Пт','Сб'], monthNamesShort: ['Январь','Февраль','Март','Апрель','Май','Июнь','Июль','Август','Сентябрь','Октябрь','Ноябрь','Декабрь'], changeMonth: true, changeYear: true, yearRange: '1940:2030', minDate: new Date(1940, 1 - 1, 1), showButtonPanel: true, currentText: 'Сегодня', closeText: 'Готово'});

	});

	$(".nano").nanoScroller();

	/*tooltips*/
	$('#pptt .tooltips').append("<span></span>");
	$('#pptt .tooltips:not([tooltip-position])').attr('tooltip-position','bottom');
	$("#pptt .tooltips").mouseenter(function(){
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

	if(isMobile || $(window).width() < 767) {

		$('.ui-layout-west').toggleClass('open');
		$('.ui-layout-center').toggleClass('open');

	}
	else{

		$('.ui-layout-west').toggleClass('compact simple');
		$('.ui-layout-center').toggleClass('compact simple');

	}
	$(this).toggleClass('open');

});

$('.ui-layout-center').onPositionChanged(function(){

	if(this.resizeTO) clearTimeout(this.resizeTO);
	this.resizeTO = setTimeout(function() {

	}, 200);

	//$('.tableHeader').css({"width": "100%"});
	$('.ui-layout-content').css({"width": "100%"});
	$('#list_header').css({"width": "100%"});

});

$(".showintro").click(function() {
	var intro = introJs();

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
	var url = '/content/lists/list.controlpoint.php';

	$('#contentdiv').append('<div class="contentloader"><img src="/assets/images/Services.svg" width="50px" height="50px"></div>');

	var cdheight = $('#contentdiv').height();
	var cdwidth = $('#contentdiv').width();

	$('.contentloader').height(cdheight).width(cdwidth);

	/*------------*/

	if( $('.lpToggler').hasClass('open') && isMobile ) $('.lpToggler').trigger('click');


	$.getJSON(url, str, function(viewData) {

		$('#contentdiv').empty().mustache('controlpointTpl', viewData);

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
				$('.ui-layout-center').find('table').rtResponsiveTables();

		});

	/*------------*/
}
/*
Вызываем при применении фильтров, чтобы начинать с 1 страницы
 */
function preconfigpage() {

	$('#page').val('1');
	configpage();

}

function change_page(num){

	$('#page').val(num);
	configpage();

}

function changesort(param){

	$('#page').val('1');

	var tt = $('#ord').val();

	$('#ord').val(param);

	if (param === tt){

		if ($('#tuda').val() === '')
			$('#tuda').val('desc');

		else
			$('#tuda').val('');

	}

	configpage();

}

function clearall(){

	$('#pageform')[0].reset();
	$('#page').val('1');

	emptySelect();
	configpage();

	$(window).trigger('onhashchange');

}

</script>
<?php require_once $rootpath."/inc/panel.php"; flush();?>
</body>
</html>