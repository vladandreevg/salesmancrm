<?php
/* ============================ */
/*         SalesMan CRM         */
/* ============================ */
/* (C) 2016 Vladislav Andreev   */
/*       SalesMan Project       */
/*        www.isaler.ru         */
/*        ver. 2017.x           */
/* ============================ */

$title = "База знаний";

global $rootpath;
require_once $rootpath."/inc/head.php";
flush();

?>
<DIV class="" id="rmenu">

	<div class="tabs">

		<a href="javascript:void(0)" class="lpToggler" title="Фильтры"><i class="icon-toggler"></i></a>

		<A href="javascript:void(0)" onclick="configpage()" class="razdel bluebg pl5 pr5" title="Обновить"><i class="icon-arrows-cw"></i></A>

		<A href="javascript:void(0)" onclick="editKb('','edit')" class="razdel redbg-dark pl5 pr5" title="Добавить"><i class="icon-graduation-cap-1 white"><i class="icon-plus-circled sup fs-07"></i></i></A>

		<?php require_once $rootpath."/content/leftnav/leftpop.php"; flush();?>

	</div>

	<?php require_once $rootpath."/content/leftnav/counters.php"; flush();?>

</DIV>

<DIV class="ui-layout-north mainbg">

	<?php require_once $rootpath."/inc/menu.php"; flush();?>

</DIV>
<DIV class="ui-layout-west disable--select outlook--close">

	<?php require_once $rootpath."/modules/knowledgebase/navi.knowledgebase.php"; flush();?>

</DIV>
<DIV class="ui-layout-center disable--select outlook--close" style="overflow: hidden">

	<DIV class="mainbg listHead p0 hidden-iphone">

		<div class="pt5 pb10 flex-container">
			<div class="column flex-column wp100 fs-11 pl5 pt10 border-box Bold">
				<span class="hidden-iphone">СЕРВИС&nbsp;/&nbsp;</span><span id="tips">База знаний</span>
			</div>
			<div class="column flex-column wp50 text-right hidden">

				<A href="javascript:void(0)" onclick="editKb('','edit');"><i class="icon-plus-circled blue"></i>Добавить</A>&nbsp;&nbsp;
				<?php if($isadmin == 'on'){ ?>
					<A href="javascript:void(0)" onclick="editKb('','cat.list');"><i class="icon-folder blue"></i>Разделы</A>&nbsp;&nbsp;
				<?php } ?>
				<a href="javascript:void(0)" onclick="configpage();"><i class="icon-arrows-cw blue"></i> Обновить</a>&nbsp;&nbsp;

			</div>
		</div>

	</DIV>

	<form name="cform" id="cform">
	<div class="nano relativ" id="kblist">

		<div class="nano-content">
			<div class="ui-layout-content" id="contentdiv"></div>
		</div>

	</div>
	</form>

	<div class="pagecontainer short">
		<div class="page pbottom mainbg" id="pagediv"></div>
	</div>

</DIV>
<DIV class="ui-layout-east relativ outlook--close">

	<DIV class="mainbg h50 listHead text-right pr15" id="kbmenu"></DIV>
	<div class="ui-layout-content ui-border bgwhite">

			<DIV class="pad10 block" id="messagediv"></DIV>

	</div>

</DIV>
<DIV class="ui-layout-south"></DIV>

<div id="startinto" class="hidden">

	<div class="relativ">

		<div class="showintro" title="Запустить гид для знакомства с CRM">
			<span><i class="icon-help-circled-1"></i></span>Знакомство
		</div>
		<div id="hideintro" title="Больше не показывать гид"><i class="icon-cancel-circled"></i></div>

	</div>

</div>

<script src="/assets/js/jquery.liTextLength.js"></script>
<script>

var $display = 'knowledgebase';
var $toggler = $('.lpToggler');
var $elcenter = $('.ui-layout-center');
var $elwest = $('.ui-layout-west');
var $eleast = $('.ui-layout-east');
var $content = $('#contentdiv');
var $rmenu = $('#rmenu');
var $current = 0;

$( function() {

	$.Mustache.load('/modules/knowledgebase/tpl.knowledgebase.mustache');

	constructSpace();

	clear();
	configpage();

	$(".nano").nanoScroller();

	changeMounth();

});

$(document).on('click', '.ifolder a', function(){

	var id = $(this).data('id');
	var title = $(this).data('title');

	$('.ifolder a').removeClass('fol_it');
	$(this).addClass('fol_it');

	$('#idcat').val(id);
	$('#place').html(title);
	$('#page').val('');
	$('#tag').val('');

	configpage();

});
$(document).on('click', '.tags', function(){

	var tag = $(this).data('tag');

	clear();

	$('#tag').val(tag);

	configpage();

});
$(document).on('click', '.messagelist', function(){

	$current = $(this).data('id');

	$(this).addClass('current');
	$eleast.addClass('open');

});

$('#folder').on('change', function(){

	if(!isMobile) constructSpace();

});

function constructSpace(){

	var hw = $elcenter.width();
	var ht = ( $('.listHead:not(#kbmenu)').is(':visible') ) ? $('.listHead:not(#kbmenu)').actual('outerHeight') : 0;
	var hh = $elcenter.actual('height');
	var hm = $elcenter.actual('height') - $('#kbmenu').actual('outerHeight') - 20;

	$('#kblist').css({"height": hh - ht + "px"});
	$('#messagediv').css({"height": hm + "px"});

	var hf = $elcenter.actual('height') - $('.contaner[data-id="stat"]').actual('outerHeight') - 30;
	$('.contaner[data-id="filter"]').css({"height": hf + "px", "max-height": hf + "px"});

	var hsub  = $('#folder').height();
	var hmain = $('#pricecategory .nano').height();
	var hwin  = $(document).height();

	if(hsub > 0.5 * hwin && hsub > hmain)
		$('#pricecategory .nano').height( 0.8 * hwin + 'px');

	else
		$('#pricecategory .nano').height( 0.53 * hwin + 'px');

	$("#pricecategory").find('.nano').nanoScroller();

	$('.nano').nanoScroller();

}

$(window).on('resize',function(){

	if(!isMobile) constructSpace();

});
$(window).on('resizeend', 200, function(){

	if(!isMobile) constructSpace();

	if(!isMobile) $elcenter.trigger('onPositionChanged');

});

$toggler.on('click', function(){

	if (isMobile || $(window).width() < 767) {

		$elwest.toggleClass('open');
		$elcenter.toggleClass('open');

		$eleast.removeClass('open');

	}
	else {

		$elwest.toggleClass('outlook outlook--close');
		$elcenter.toggleClass('outlook outlook--close');
		$eleast.toggleClass('outlook outlook--close');

	}

	$(this).toggleClass('open');

});

$elcenter.onPositionChanged(function(){

	if(this.resizeTO) clearTimeout(this.resizeTO);
	this.resizeTO = setTimeout(function() {

		var hw = $elcenter.width();

		$elcenter.find('.tableHeader').css({"width": hw + "px"});
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

	$content.parent('.nano').nanoScroller({ scroll: 'top' });

	var str = $('#pageform').serialize();
	var url = '/modules/knowledgebase/list.knowledgebase.php';

	$content.append('<div class="contentloader"><img src="/assets/images/Services.svg" width="50px" height="50px"></div>');

	var cdheight = $content.height();
	var cdwidth = $content.width();

	$('.contentloader').height(cdheight).width(cdwidth);

	/*------------*/

	$.getJSON(url, str, function(viewData) {

		$content.empty().mustache('knowledgebaseshortTpl', viewData);

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

			var id = ($current === 0) ? parseInt( $content.find('table tr:first-child').data('id') ) : $current;

			if(id > 0) {

				if(!isMobile) editKbNew(id);


			}
			else {

				$('#messagediv').html('<div id="emptymessage" class="gray miditxt"><i class="icon-graduation-cap-1 icon-3x gray"></i><br><b class="red">Упс.</b>&nbsp;&nbsp;<b>Не выбрано знание для просмотра</b></div>');

			}

			if(id === 0) {

				$('#messagediv').html('<div id="emptymessage" class="gray miditxt"><i class="icon-graduation-cap-1 icon-3x gray"></i><br><b class="red">Упс.</b>&nbsp;&nbsp;<b>Не выбрано знание для просмотра</b></div>');

			}

			$(".nano").nanoScroller();

			if(isMobile) {

				$content.find('table').rtResponsiveTables();

			}

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
function loadtags(){
	$('#tagbox').load('/modules/knowledgebase/core.knowledgebase.php?action=tags');
}

function change_page(page){

	$('#page').val(page);
	configpage();

}
function clear(){

	$('.ifolder').removeClass('fol_it');//.addClass('fol');
	$('.ifolder a:first').addClass('fol_it');

	$('#place').html('');

	$('#idcat').val('');
	$('#page').val('');
	$('#tag').val('');
	$('#word').val('');

}
function editKbNew(id){

	$.get('/modules/knowledgebase/form.knowledgebase.php?action=viewshort&id='+id, function(data){

		$('#messagediv').html(data);

	})
		.done(function(){

			$('#kbmenu').html( $('.kbaction').html() )

			$content.find('table tr[data-id="'+id+'"]').addClass('current')
			$eleast.addClass('open')
			$current = id

		});

	return false;

}

</script>
<?php require_once $rootpath."/inc/panel.php"; flush();?>
</body>
</html>