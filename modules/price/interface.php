<?php
/* ============================ */
/*         SalesMan CRM         */
/* ============================ */
/* (C) 2016 Vladislav Andreev   */
/*       SalesMan Project       */
/*        www.isaler.ru         */
/*        ver. 2017.x           */
/* ============================ */

$title = 'Прайс-лист';

$year = date('Y');
$y1 = $year - 1;
$y2 = $year + 1;

global $rootpath;
require_once $rootpath."/inc/head.php";
flush();

if ($acs_price != 'on') {
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

		<a href="javascript:void(0)" class="lpToggler open" title="Фильтры"><i class="icon-toggler"></i></a>

		<A href="javascript:void(0)" onclick="editPrice('','edit');" class="razdel redbg-dark pl5 pr5" title="Добавить"><i class="icon-dollar white"><i class="icon-plus-circled sup fs-07"></i></i></A>

		<?php require_once $rootpath."/content/leftnav/leftpop.php"; flush();?>

	</div>

	<?php require_once $rootpath."/content/leftnav/counters.php"; flush();?>

</DIV>

<DIV class="ui-layout-north mainbg">

	<?php require_once $rootpath."/inc/menu.php"; flush();?>

</DIV>
<DIV class="ui-layout-west disable--select compact">

	<?php require_once $rootpath."/modules/price/navi.price.php"; flush();?>

</DIV>
<DIV class="ui-layout-center disable--select compact">

	<DIV class="mainbg listHead p0 hidden-iphone">

		<div class="flex-container p10">

			<div class="flex-column wp50 fs-11 border-box">

				<b>СЕРВИС&nbsp;/&nbsp;Прайс&nbsp;/&nbsp;</b><span id="tips"></span>

			</div>
			<div class="flex-column wp50 text-right">

				<div class="menu_container">

					<a href="javascript:void(0)" onclick="submenu('sub')" class="tagsmenuToggler"><b>Действия</b>&nbsp;<i class="icon-angle-down" id="mapi"></i></a>

					<div class="tagsmenu toright hidden">

						<div class="items noBold fs-09">

							<div onclick="editPrice('','edit');" class="item ha hand"><span><i class="icon-money green"><i class="sup icon-plus-circled red"></i></i></span>&nbsp;&nbsp;Добавить</div>
							<div onclick="editPrice('','cat.list');" class="item ha hand"><span><i class="icon-folder blue"><i class="sup icon-pencil red"></i></i></span>&nbsp;&nbsp;Редактор категорий</div>
							<div onclick="editPrice('','import');" class="item ha hand"><span><i class="icon-database broun"><i class="sup icon-exchange red"></i></i></span>&nbsp;&nbsp;Импорт из Excel</div>
							<div onclick="editPrice('','export')" class="item ha hand"><span><i class="icon-doc-text-inv blue"><i class="sup icon-exchange red"></i></i></span>&nbsp;&nbsp;Экспорт в Excel</div>
							<?php if($isadmin=='on' || $tipuser=='Администратор' || $userRights['groupactions']) {?>
							<div onclick="editPrice('','mass')" title="Групповые действия" class="item ha hand"><span><i class="icon-magic blue"><i class="sup icon-direction red"></i></i></span>&nbsp;&nbsp;Групповые действия</div>
							<?php } ?>

						</div>

					</div>

				</div>
				&nbsp;&nbsp;<a href="javascript:void(0)" title="Обновить представление" onclick="configpage();"><i class="icon-arrows-cw blue"></i>Обновить</a>&nbsp;&nbsp;

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

<script src="/assets/js/jquery.liTextLength.js"></script>
<script>

var $display = 'price';

if (isMobile || $(window).width() < 767) {

	$('.lpToggler').toggleClass('open');

	$('#tips').html('Весь прайс');

}

$( function() {

	$.Mustache.load('/modules/price/tpl.price.mustache');

	//$('.ui-layout-center').append('<div class="tableHeader" style="position:absolute; width:100%;"></div>');

	constructSpace();

	clear();
	configpage();

	$(".nano").nanoScroller();

	changeMounth();

});

$(document).on('click', '.ifolder .block', function(){

	var id = $(this).data('id');
	var title = $(this).data('title');

	$('.ifolder .block').removeClass('fol_it');
	$(this).addClass('fol_it');

	$('#idcat').val(id);
	$('#tips').html(title);
	$('#page').val('');

	preconfigpage();

});

$('#folder').on('change', function(){

	if(!isMobile) constructSpace();

});

function constructSpace(){

	//var hw = $('.ui-layout-center').width();
	var ht = ( $('.listHead').is(':visible') ) ? $('.listHead').actual('outerHeight') : 0;
	var hh = $('.ui-layout-center').actual('height') - ht;

	$('#clientlist').css({"height": hh + 'px'});

	//$('.ui-layout-center').find('.tableHeader').css({"top": ht + 'px', "left" : "0px"});

	var hf = $('.ui-layout-center').actual('height') - $('.contaner[data-id="stat"]').actual('outerHeight') - 30;
	$('.contaner[data-id="filter"]').css({"height": hf + "px", "max-height": hf + "px"});

	var hsub  = $('#folder').height();
	var hmain = $('#pricecategory .nano').height();
	var hwin  = $(document).height();

	if(hsub > 0.5 * hwin && hsub > hmain) $('#pricecategory .nano').height( 0.8 * hwin + 'px');
	else $('#pricecategory .nano').height( 0.53 * hwin + 'px');

	$("#pricecategory").find('.nano').nanoScroller();

	$('.nano').nanoScroller();

}

function changeCategoryHeight(){

	var hsub  = $('#folder').height();
	var hmain = $('#pricecategory .nano').height();
	var hwin  = $(document).height();

	if(hsub > 0.5 * hwin && hsub > hmain) $('#pricecategory .nano').height( 0.8 * hwin + 'px');
	else $('#pricecategory .nano').height( 0.53 * hwin + 'px');

	$("#pricecategory").find('.nano').nanoScroller();

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

		/*var hw = $('.ui-layout-center').width();

		$('.tableHeader').css({"width": hw + "px"});
		$('.ui-layout-content').css({"width": hw + "px"});
		$('#list_header').css({"width": hw + "px"});*/

	}, 200);

	//$('.ui-layout-center').find('.tableHeader').css({"width": "100%"});
	$('.ui-layout-content').css({"width": "100%"});
	$('#list_header').css({"width": "100%"});

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

	$('#contentdiv').parent('.nano').nanoScroller({ scroll: 'top' });

	var str = $('#pageform').serialize();
	var url = '/modules/price/list.price.php';

	$('#contentdiv').append('<div class="contentloader"><img src="/assets/images/Services.svg" width="50px" height="50px"></div>');

	var cdheight = $('#contentdiv').height();
	var cdwidth = $('#contentdiv').width();

	$('.contentloader').height(cdheight).width(cdwidth);

	/*------------*/

	$.getJSON(url, str, function(viewData) {

		$('#contentdiv').empty().mustache('priceTpl', viewData);

		page = viewData.page;
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

			//var header = $('#contentdiv table thead').html();
			//var html = '<table cellpadding="5" width="100%" cellspacing="0" border="0" id="list_header" height="30"><thead>'+header+'</thead></table>';
			var order = $('#ord').val();
			var desc  = $('#tuda').val();
			var icn   = '<i class="icon-angle-down"></i>';

			//$('.ui-layout-center').find('.tableHeader').html(html);

			if (desc === 'desc') icn = '<i class="icon-angle-up"></i>';

			$('.header_contaner').find('#x-' + order).prepend(icn);

			var $w = [45, 90, 50];

			if($(window).width() > 1500) $w = [60, 120, 80];
			else if($(window).width() > 1700) $w = [100, 200, 160];

			$(".name-ellipsis").liTextLength({
				length: $w[2],
				afterLength: '...',
				fullText:false
			});

			$(".nano").nanoScroller();

			if(isMobile) {

				$('#contentdiv').find('table').rtResponsiveTables();

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
function change_page(page){

	$('#page').val(page);
	configpage();

}
function changesort(param){

	var tt = $('#ord').val();

	$('#ord').val(param);
	$('#page').val('1');

	if (param == tt){

		if ($('#tuda').val()=='') $('#tuda').val('desc');
		else $('#tuda').val('');

	}

	configpage();
}
function clear(){

	$('.ifolder').removeClass('fol_it');//.addClass('fol');
	$('.ifolder a:first').addClass('fol_it');

	$('#place').html('');
	$('#idcat').val('');
	$('#word').val('');

}

</script>
<?php require_once $rootpath."/inc/panel.php"; flush();?>
</body>
</html>