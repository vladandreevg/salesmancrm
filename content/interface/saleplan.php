<?php
/* ============================ */
/*         SalesMan CRM         */
/* ============================ */
/* (C) 2016 Vladislav Andreev   */
/*       SalesMan Project       */
/*        www.isaler.ru         */
/*        ver. 2017.x           */
/* ============================ */

$title = 'План продаж';

$year = date('Y');
$y1 = $year - 1;
$y2 = $year + 1;

global $rootpath;
require_once $rootpath."/inc/head.php";
flush();

if(stripos($tipuser,'Руководитель') === false){
	print '
	<div class="warning text-left" style="width:600px">
		<span><i class="icon-attention red icon-5x pull-left"></i></span>
		<b class="red uppercase">Внимание:</b><br><br>К сожалению у вас нет доступа в раздел.<br>
	</div>
	<script type="text/javascript">
		$(".warning").center();
	</script>
	';
	exit();
}
?>
<DIV class="" id="rmenu">

	<div class="tabs">

		<a href="javascript:void(0)" class="lpToggler" title="Фильтры"><i class="icon-toggler"></i></a>

		<?php require_once $rootpath."/content/leftnav/leftpop.php"; flush();?>

	</div>

	<?php require_once $rootpath."/content/leftnav/counters.php"; flush();?>

</DIV>

<DIV class="ui-layout-north mainbg">

	<?php require_once $rootpath."/inc/menu.php"; flush();?>

</DIV>
<DIV class="ui-layout-west disable--select simple">

	<?php require_once $rootpath."/content/leftnav/saleplan.php"; flush();?>

</DIV>
<DIV class="ui-layout-center disable--select simple" style="overflow: hidden">

	<DIV class="mainbg listHead p0 hidden-ipad">

		<div class="flex-container border-box pb5">

			<div class="flex-column wp40 p5 fs-11 border-box">

				<b class="shado" id="tips">План продаж</b>&nbsp;

			</div>
			<div class="flex-column wp60 border-box text-right">

				<a href="javascript:void(0)" onclick="changeyear('prev');"><i class="icon-angle-double-left"></i><span class="prev"><?=$y1?></span></a>&nbsp;|&nbsp;
				<span class="red Bold miditxt current"><?=$year?></span>&nbsp;|&nbsp;
				<a href="javascript:void(0)" onclick="changeyear('next');"><span class="next"><?=$y2?></span><i class="icon-angle-double-right"></i></a>&nbsp;&nbsp;

				[ <a href="javascript:void(0)" onclick="changeView()" title="Изменить вид"><i class="icon-sitemap broun"></i></a> ]&nbsp;[ <a href="javascript:void(0)" title="Обновить" onclick="configpage();"><i class="icon-arrows-cw blue"></i></a> ]&nbsp;[ <a href="javascript:void(0)" title="Импорт" onclick="editPlan('','import');"><i class="icon-upload blue"></i></a> ]&nbsp;[ <a href="javascript:void(0)" onclick="editPlan('','export');" title="Экспорт"><i class="icon-download green"></i></a> ]&nbsp;&nbsp;

			</div>

		</div>

	</DIV>

	<form name="cform" id="cform">
	<div class="nano" id="clientlist">

		<div class="nano-content">
			<div class="ui-layout-content" id="contentdiv"></div>
		</div>

	</div>
	</form>

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

var $display = 'saleplan';

if (isMobile || $(window).width() < 767) {

	$('.lpToggler').toggleClass('open');

}

$( function() {

	constructSpace();

	configpage();

	$('.inputdate').each(function(){

		if(!isMobile) $(this).datepicker({ dateFormat: 'yy-mm-dd', numberOfMonths:2, firstDay: 1, dayNamesMin: ['Вс','Пн','Вт','Ср','Чт','Пт','Сб'], monthNamesShort: ['Январь','Февраль','Март','Апрель','Май','Июнь','Июль','Август','Сентябрь','Октябрь','Ноябрь','Декабрь'], changeMonth: true, changeYear: true, yearRange: '1940:2030', minDate: new Date(1940, 1 - 1, 1), showButtonPanel: true, currentText: 'Сегодня', closeText: 'Готово'});

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

	constructSpace();

});
$(window).on('resizeend', 200, function(){

	constructSpace();

	$('.ui-layout-center').trigger('onPositionChanged');

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

		var hw = $('.ui-layout-center').width();

		//$('.ui-layout-center').find('.tableHeader').css({"width": hw + "px"});
		$('.ui-layout-content').css({"width": hw + "px"});
		$('#list_header').css({"width": hw + "px"});

	}, 200);

});

$(".showintro").on('click', function() {
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
	var url = '/content/lists/list.plan.php';

	$('#contentdiv').append('<div class="contentloader"><img src="/assets/images/Services.svg" width="50px" height="50px"></div>');

	var cdheight = $('.ui-layout-center').height();
	var cdwidth = $('.ui-layout-center').width();

	$('.contentloader').height(cdheight).width(cdwidth);

	/*------------*/

	$.get(url, str, function(data){

		$('#contentdiv').html(data);

	})
		.done(function(){

			//var header = $('#contentdiv table thead').html();
			//var html = '<table cellpadding="5" width="100%" cellspacing="0" border="0" id="list_header" height="30"><thead>'+header+'</thead></table>';
			//$('.ui-layout-center').find('.tableHeader').width(cdwidth).html(html);

			$(".nano").nanoScroller();

			if (isMobile)
				$('.ui-layout-center').find('table').rtResponsiveTables();

		});

	/*------------*/
}

function changeyear(dir){

	var year = parseInt($('#year').val());

	if(dir === 'prev') year = year - 1;
	if(dir === 'next') year = year + 1;

	var prev = year - 1;
	var next = year + 1;

	$('#year').val(year);
	$('.prev').html(prev);
	$('.next').html(next);
	$('.current').html(year);

	configpage();
}
function changeView(){

	var view = $('#view').val();

	if(view === '') {

		view = 'org';
		$('#userlist').removeClass('hidden');

	}
	else if(view === 'org') {

		view = '';
		$('#userlist').addClass('hidden');

	}

	$('#view').val(view);

	configpage();
}

</script>
<?php require_once $rootpath."/inc/panel.php"; flush();?>
</body>
</html>