<?php
/* ============================ */
/*         SalesMan CRM         */
/* ============================ */
/* (C) 2016 Vladislav Andreev   */
/*       SalesMan Project       */
/*        www.isaler.ru         */
/*        ver. 2017.x           */
/* ============================ */

$title = 'Звонки';

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

	<?php require_once $rootpath."/content/leftnav/calls.php"; flush();?>

</DIV>
<DIV class="ui-layout-center disable--select compact" style="overflow: hidden">

	<DIV class="mainbg listHead p0 hidden-iphone">

		<div class="flex-container p10">

			<div class="column flex-column wp50 fs-11 border-box">
				<b class="shado" id="tips">История звонков</b>
			</div>
			<div class="column flex-column wp50 text-right">

				<?php if($userRights['export']){?>
					<a href="javascript:void(0)" title="Экспорт" onclick="doLoad('/content/lists/list.calls.php?action=export');" class="hidden-iphone"><i class="icon-upload-1 blue"></i>Экспорт в Excel</a>&nbsp;&nbsp;
					<?php
				}
				if($sip_active == 'yes' && in_array($sip_tip, $sipHasCDR)){?>
					<a href="javascript:void(0)" title="Обновить" onclick="getCDR()"><i class="icon-download red"></i><span class="hidden-iphone">Получить вручную</span></a>
					<?php
				}
				?>
				&nbsp;&nbsp;<a href="javascript:void(0)" title="Обновить" onclick="configpage()"><i class="icon-arrows-cw blue"></i><span class="hidden-iphone"> Обновить</span></a>&nbsp;&nbsp;

			</div>

		</div>

	</DIV>

	<form name="cform" id="cform">
	<div class="nano relativ" id="clientlist">

		<div class="nano-content">
			<div class="ui-layout-content" id="contentdiv"></div>
		</div>

		<div class="pagecontainer">
			<div class="page pbottom mainbg" id="pagediv"></div>
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

var $display = 'history';

if(isMobile || $(window).width() < 767){

	$('.lpToggler').toggleClass('open');

}

$( function() {

	$('.ui-layout-center').append('<div class="tableHeader" style="position:absolute; width: 100%;"></div>');

	$.Mustache.load('/content/tpl/tpl.calls.mustache');

	constructSpace();

	configpage();

	$('.inputdate').each(function(){

		if(!isMobile)
			$(this).datepicker({ dateFormat: 'yy-mm-dd', numberOfMonths:2, firstDay: 1, dayNamesMin: ['Вс','Пн','Вт','Ср','Чт','Пт','Сб'], monthNamesShort: ['Январь','Февраль','Март','Апрель','Май','Июнь','Июль','Август','Сентябрь','Октябрь','Ноябрь','Декабрь'], changeMonth: true, changeYear: true, yearRange: '1940:2030', minDate: new Date(1940, 1 - 1, 1), showButtonPanel: true, currentText: 'Сегодня', closeText: 'Готово'});

	});

	$(".nano").nanoScroller();

	/*tooltips*/
	$('.tooltips').append("<span></span>");
	$(".tooltips").on('mouseenter', function(){
		$(this).find('span').empty().append($(this).attr('tooltip')).css({"bottom":"70px"});
	});
	/*tooltips*/

	changeMounth();


});

function constructSpace(){

	//var hw = $('.ui-layout-center').width();
	//var hh = $('.ui-layout-center').actual('height');
	//var ht = ( $('.listHead').is(':visible') ) ? $('.listHead').actual('outerHeight') : 0;

	//$('.ui-layout-center').find('.tableHeader').css({"top": ht + 'px', "left" : "0px"});

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

		/*var hw = $('.ui-layout-center').width();

		$('.tableHeader').css({"width": hw + "px"});
		$('.ui-layout-content').css({"width": hw + "px"});
		$('#list_header').css({"width": hw + "px"});*/

	}, 200);

	//$('.ui-layout-center').find('.tableHeader').css({"width": "100%"});
	$('.ui-layout-content').css({"width": "100%"});
	$('#list_header').css({"width": "100%"});

});

function configpage(){

	$('#contentdiv').parent(".nano").nanoScroller({ scroll: 'top' });

	var str = $('#pageform').serialize();
	var url = '/content/lists/list.calls.php';

	$('#contentdiv').append('<div class="contentloader"><img src="/assets/images/Services.svg" width="50px" height="50px"></div>');

	var cdheight = $('#contentdiv').height();
	var cdwidth = $('#contentdiv').width();

	$('.contentloader').height(cdheight).width(cdwidth);

	/*------------*/

	if( $('.lpToggler').hasClass('open') && isMobile ) $('.lpToggler').trigger('click');

	$.getJSON(url + '?' + str, function(viewData) {

		$('#contentdiv').empty().mustache('callsTpl', viewData);

		var page = viewData.page;
		var pageall = viewData.pageall;

		var pg = 'Стр. '+page+' из '+pageall;

		if(pageall > 1){

			var prev = page - 1;
			var next = page + 1;

			if(page === 1)
				pg = pg + '&nbsp;<a href="javascript:void(0)" onClick="change_page(\''+next+'\')" title="Следующая"><i class="icon-angle-right"></i></a>&nbsp;&nbsp;<a href="javascript:void(0)" onClick="change_page(\''+pageall+'\')" title="Последняя"><i class="icon-angle-double-right"></i></a>&nbsp;';

			else if(page === pageall)
				pg = pg + '&nbsp;<a href="javascript:void(0)" onClick="change_page(\'1\')" title="Начало"><i class="icon-angle-double-left"></i></a>&nbsp;&nbsp;<a href="javascript:void(0)" onClick="change_page(\''+prev+'\')" title="Предыдущая"><i class="icon-angle-left"></i></a>&nbsp;';

			else
				pg = '&nbsp;<a href="javascript:void(0)" onClick="change_page(\'1\')" title="Начало"><i class="icon-angle-double-left"></i></a>&nbsp;&nbsp;<a href="javascript:void(0)" onClick="change_page(\''+prev+'\')" title="Предыдущая"><i class="icon-angle-left"></i></a>&nbsp;'+ pg+ '&nbsp;<a href="javascript:void(0)" onClick="change_page(\''+next+'\')" title="Следующая"><i class="icon-angle-right"></i></a>&nbsp;&nbsp;<a href="javascript:void(0)" onClick="change_page(\''+pageall+'\')" title="Последняя"><i class="icon-angle-double-right"></i></a>&nbsp;';

		}

		$('#pagediv').html(pg);

	})
		.done(function() {

			$(".nano").nanoScroller();

			if (isMobile)
				$('table').rtResponsiveTables();

		});
}

<?php
if($sip_active == 'yes' && in_array($sip_tip, $sipHasCDR)){
?>
function getCDR(){

	var url = '/content/pbx/<?=$sip_tip?>/cdr.php?hours=24&printres=yes&force=1';

	$('#message').fadeTo(10,1).empty().css('display', 'block').append('<div id="loader"><img src="/assets/images/loader.gif"> Загрузка данных...</div>');
	$.getJSON(url, function(data) {

		configpage();
		$('#message').fadeTo(1,1).css('display', 'block').html(data.result);
		setTimeout(function() { $('#message').fadeTo(1000,0); },20000);
		return true;

	});
}
<?php
}
if($sip_active == 'yes' && in_array($sip_tip, ['comtube', 'sipnet'])){
?>
function getCDR(){
	var url ='/content/pbx/<?=$sip_tip?>/callto.php?action=gethistory&hours=24';

	$('#message').fadeTo(1,1).empty().css('display', 'block').append('<div id="loader"><img src="/assets/images/loader.gif"> Загрузка данных...</div>');
	$.post(url, function(data) {
		configpage();
		$('#message').fadeTo(1,1).css('display', 'block').html(data);
		setTimeout(function() { $('#message').fadeTo(1000,0); },20000);
		return true;
	});
}
<?php } ?>

function change_page(page){

	$('#page').val(page);
	configpage();

}

function clearFilter(){

	if($('#ifilter').hasClass('icon-eye-off')){

		$('.taskss').prop('checked', false);
		$('#ifilter').removeClass('icon-eye-off').addClass('icon-eye');

		configpage();

	}
	else{

		$('.taskss').prop('checked', true);
		$('#ifilter').addClass('icon-eye-off').removeClass('icon-eye');

		configpage();

	}
}

</script>
<?php require_once $rootpath."/inc/panel.php"; flush();?>
</body>
</html>