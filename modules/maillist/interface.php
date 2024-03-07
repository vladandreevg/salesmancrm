<?php
/* ============================ */
/*         SalesMan CRM         */
/* ============================ */
/* (C) 2016 Vladislav Andreev   */
/*       SalesMan Project       */
/*        www.isaler.ru         */
/*        ver. 2017.x           */
/* ============================ */

$title = 'Рассылки';

global $rootpath;
require_once $rootpath."/inc/head.php";
flush();

if (($mailout != 'yes' && $acs_maillist != 'on') || $isCloud ) {
	print '
	<div class="warning" align="left" style="width:600px">
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
<DIV class="" id="rmenu">

	<div class="tabs">

		<a href="javascript:void(0)" class="lpToggler open" title="Фильтры"><i class="icon-toggler"></i></a>

		<?php require_once $rootpath."/content/leftnav/leftpop.php"; flush();?>

	</div>

	<?php require_once $rootpath."/content/leftnav/counters.php"; flush();?>

</DIV>

<DIV class="ui-layout-north mainbg">

	<?php require_once $rootpath."/inc/menu.php"; flush();?>

</DIV>
<DIV class="ui-layout-west disable--select compact">

	<?php require_once $rootpath."/modules/maillist/navi.maillist.php"; flush();?>

</DIV>
<DIV class="ui-layout-center disable--select compact" style="overflow: hidden">

	<DIV class="mainbg listHead p0">

		<div class="pt5 pb10 flex-container">
			<div class="column flex-column wp50 fs-11 pl5 border-box">
				<b>СЕРВИС&nbsp;/&nbsp;Рассылки&nbsp;/&nbsp;</b><div id="place" style="display:inline-block">Список рассылок</div>
			</div>
			<div class="column flex-column wp50 text-right">

				<a href="javascript:void(0)" title="Обновить" onclick="configpage()"><i class="icon-arrows-cw blue"></i>&nbsp;Обновить</a>&nbsp;&nbsp;

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

<div id="startinto">

	<div class="relativ">

		<div class="showintro" title="Запустить гид для знакомства с CRM">
			<span><i class="icon-help-circled-1"></i></span>Знакомство
		</div>
		<div id="hideintro" title="Больше не показывать гид"><i class="icon-cancel-circled"></i></div>

	</div>

</div>

<script>

var $display = 'maillist';

$( function() {

	//$('.ui-layout-center').append('<div class="tableHeader" style="position:absolute; width: 100%"></div>');

	$.Mustache.load('/modules/maillist/tpl.maillist.mustache');

	constructSpace();

	configpage();

	$(".nano").nanoScroller();

	changeMounth();


});

window.onhashchange = function() {

	var hash = window.location.hash.substring(1);
	razdel(hash);

};

function razdel(hesh){

	$('.razdel a').removeClass('active');

	if(!hesh) hesh = window.location.hash.replace('#','');
	if(!hesh) hesh = 'group';

	switch ( hesh ){
		case 'group':
			$('#gname').val('');
			$('#gid').val('');
			$('#place').html('Группы');
			$('.menu-group').removeClass('hidden');
			$('.menu-glist').addClass('hidden');
			$('.contaner-glist').addClass('hidden');
			break;
		case 'glist':
			var name = $('#gname').val();
			$('#place').html('Подписчики группы&nbsp;/&nbsp;' + name);
			$('.menu-glist').removeClass('hidden');
			$('.menu-group').addClass('hidden');
			$('.contaner-glist').removeClass('hidden');
			break;
	}

	$('#tar').val(hesh);
	$('#tuda').val('desc');

	$('.razdel .'+hesh).addClass('active');//.css('border','1px solid red');

	preconfigpage();

}

function settip(tip){

	$('#tip').val(tip);

	if(tip === 'list') $('#place').html('Список рассылок');
	if(tip === 'list.tpl') $('#place').html('Список шаблонов');

	preconfigpage();

}

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

	$('.ui-layout-west').toggleClass('compact simple');
	$('.ui-layout-center').toggleClass('compact simple');
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

	var tip, url, tpl;

	$('#contentdiv').parent(".nano").nanoScroller({ scroll: 'top' });

	tip = $('#tip').val();

	if(tip === 'list'){

		tpl = 'maillist';
		url = '/modules/maillist/list.maillist.php';

	}
	if(tip === 'list.tpl') {

		tpl = 'tpl.maillist';
		url = '/modules/maillist/list.tpl.maillist.php';

	}
	var str = $('#pageform').serialize();

	$('#contentdiv').append('<div class="contentloader"><img src="/assets/images/Services.svg" width="50px" height="50px"></div>');

	var cdheight = $('#contentdiv').height();
	var cdwidth = $('#contentdiv').width();

	$('.contentloader').height(cdheight).width(cdwidth);

	/*------------*/

	$.getJSON(url + '?' + str, function(viewData) {

		$('#contentdiv').empty().mustache(tpl+'Tpl', viewData);

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

		});
}
/*
Вызываем при применении фильтров, чтобы начинать с 1 страницы
 */
function preconfigpage() {

	$('#page').val('1');
	configpage();

}

function change_page(page){

	$('#page').val(page);
	configpage();

}

function configpage2(){

	$('#contentdiv').load('/modules/maillist/list.maillist.php?word='+$('#word').val()+'&tip='+$('#tip option:selected').val());

}

</script>
<?php require_once $rootpath."/inc/panel.php"; flush();?>
</body>
</html>