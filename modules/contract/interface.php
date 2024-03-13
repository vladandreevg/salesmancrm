<?php
/* ============================ */
/*         SalesMan CRM         */
/* ============================ */
/* (C) 2016 Vladislav Andreev   */
/*       SalesMan Project       */
/*        www.isaler.ru         */
/*        ver. 2017.x           */
/* ============================ */

$title = 'Документы';

global $rootpath;
require_once $rootpath."/inc/head.php";
flush();

$y = $_REQUEST['y']; if(!$y) $y = date('Y');
$m = $_REQUEST['m']; if(!$m) $m = date('m');

if($tar == 'payment') $ord = 'datum_credit';
if($tar == 'cpoint') $ord = 'data_plan';
?>

<DIV class="" id="rmenu">

	<div class="tabs">

		<a href="javascript:void(0)" class="lpToggler open" title="Фильтры"><i class="icon-toggler"></i></a>
		<a href="javascript:void(0)" onclick="configpage();" title="Обновить представление"><i class="icon-arrows-cw"></i></a>

		<a href="#contract" class="razdel pl5 pr5" data-id="contract" title="<?=$lang['docs']['Doc'][1]?>"><i class="icon-doc-text-inv"></i></a>
		<a href="#payment" class="razdel pl5 pr5" data-id="payment" title="<?=$lang['docs']['AddedInvoices']?>"><i class="icon-rouble"></i></a>
		<a href="#akt" class="razdel pl5 pr5" data-id="akt" title="<?=$lang['docs']['Act'][1]?>"><i class="icon-doc-inv"></i></a>

		<?php require_once $rootpath."/content/leftnav/leftpop.php"; flush();?>

	</div>

	<?php require_once $rootpath."/content/leftnav/counters.php"; flush();?>

</DIV>

<DIV class="ui-layout-north mainbg">

	<?php require_once $rootpath."/inc/menu.php"; flush();?>

</DIV>
<DIV class="ui-layout-west disable--select compact">

	<?php require_once $rootpath."/modules/contract/navi.contract.php"; flush();?>

</DIV>
<DIV class="ui-layout-center disable--select compact" style="overflow: hidden">

	<DIV class="mainbg listHead p0 hidden-iphone">

		<div class="flex-container p10">

			<div class="column flex-column wp50 fs-11 pl5 border-box">
				<span class="shado Bold"><?=$title?></span><span class="">&nbsp;/&nbsp;</span><span id="tips"></span>
			</div>
			<div class="column flex-column wp50 text-right">

				<span class="menu-akt">
				<a href="javascript:void(0)" onclick="editContract('','akt.export');" title="Экспорт в Excel" class="hidden-iphone"><i class="icon-doc-text-inv blue"></i>Экспорт в Excel</a>&nbsp;&nbsp;
			</span>
				<span class="menu-payment">
				<a href="javascript:void(0)" onclick="editContract('','payment.export');" title="Экспорт" class="hidden-iphone"><i class="icon-doc-text-inv blue"></i> Экспорт</a>
			</span>
				<A href="javascript:void(0)" onclick="configpage()"><i class="icon-arrows-cw blue"></i><span class="hidden-iphone">&nbsp;Обновить</span></A>&nbsp;&nbsp;

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

<script>

var hash = window.location.hash.substring(1);

if(hash === '') hash = 'contract';

if(isMobile || $(window).width() < 767){

	$('.lpToggler').toggleClass('open');

}

$.Mustache.load('/modules/contract/tpl.contract.mustache');

$( function() {

	$('.ui-layout-center').append('<div class="tableHeader" style="position:absolute; width: 100%"></div>');

	constructSpace();

	$('#rmenu').find('a').removeClass('active');
	$('#rmenu').find('a[data-id="'+hash+'"]').addClass('active');

	$(window).trigger('onhashchange');

	razdel(hash);

	$(".nano").nanoScroller();

	changeMounth();

});

window.onhashchange = function() {

	var hash = window.location.hash.substring(1);

	razdel(hash);

	$('#rmenu').find('a').removeClass('active');
	$('#rmenu').find('a[data-id="'+hash+'"]').addClass('active');

};

function constructSpace(){

	//var hw = $('.ui-layout-center').width();
	//var ht = ( $('.listHead').is(':visible') ) ? $('.listHead').actual('outerHeight') : 0;
	//var hh = $('.ui-layout-center').actual('height');

	//$('.ui-layout-center').find('.tableHeader').css({"top": ht + 'px', "left" : "0px"});

	var hf = $('#lmenu').actual('height') - $('.contaner[data-id="stat"]').actual('outerHeight') - $('#cfilter').actual('outerHeight') - 10;
	$('.contaner[data-id="filter"]').css({"height": hf + "px", "max-height": hf + "px"});

	$('.nano').nanoScroller();

}

function configpage(){

	let elm = $('#contentdiv');

	elm.parent(".nano").nanoScroller({ scroll: 'top' });

	var str = $('#pageform').serialize();
	var url = '/modules/contract/list.contract.php';
	var tar = $('#tar').val();

	elm.append('<div class="contentloader"><img src="/assets/images/Services.svg" width="50px" height="50px"></div>');

	var cdheight = elm.height();
	var cdwidth = elm.width();

	$('.contentloader').height(cdheight).width(cdwidth);

	/*------------*/

	if( $('.lpToggler').hasClass('open') && isMobile )
		$('.lpToggler').trigger('click');

	$.getJSON(url, str, function(viewData) {

		elm.empty().mustache(tar + 'Tpl', viewData);

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

		var header = $('#contentdiv table thead').html();
		//var html = '<table cellpadding="5" width="100%" cellspacing="0" border="0" id="list_header" height="30"><thead>'+header+'</thead></table>';
		var order = $('#ord').val();
		var desc  = $('#tuda').val();
		var icn   = '<i class="icon-angle-up"></i>';

		//$('.ui-layout-center').find('.tableHeader').html(html);

		if (desc === 'desc') icn = '<i class="icon-angle-down"></i>';

		$('.header_contaner').find('#x-' + order).prepend(icn);

		$(".nano").nanoScroller();

		if(isMobile) {

			$('#contentdiv').find('table').rtResponsiveTables();

		}

	});

}
/*
Вызываем при применении фильтров, чтобы начинать с 1 страницы
 */
function preconfigpage() {

	$('#page').val('1');
	configpage();

}
function razdel(hesh){

	$('.razdel a').removeClass('active');

	if(!hesh) hesh = window.location.hash.replace('#','');
	if(!hesh) hesh = 'contract';

	hash = hesh;

	setHeight();

	switch ( hesh ){
		case 'contract':
			$('#tips').html('Документы');
			$('#ord').val('datum_end');

			$('.menu-contract').removeClass('hidden');
			$('.menu-payment').addClass('hidden');
			$('.menu-akt').addClass('hidden');

			$('.contaner-contract').removeClass('hidden');
			$('.contaner-payment').addClass('hidden');
			$('.contaner-akt').addClass('hidden');
			$('.contaner-status').removeClass('hidden');
			//$('.pagecontainer').addClass('hidden');

			$('#ord').val('datum_start');
			$('#tuda').val('desc');

			break;
		case 'payment':
			$('#tips').html('Выставленные счета');
			$('#ord').val('datum_credit');

			$('.menu-payment').removeClass('hidden');
			$('.menu-contract').addClass('hidden');
			$('.menu-akt').addClass('hidden');

			$('.contaner-payment').removeClass('hidden');
			$('.contaner-contract').addClass('hidden');
			$('.contaner-akt').addClass('hidden');
			$('.contaner-status').addClass('hidden');
			//$('.pagecontainer').addClass('hidden');

			$('#ord').val('datum_credit');
			$('#tuda').val('desc');

			break;
		case 'akt':
			$('#tips').html('Акты');
			$('#ord').val('datum');

			$('.menu-payment').addClass('hidden');
			$('.menu-contract').addClass('hidden');
			$('.menu-akt').removeClass('hidden');

			$('.contaner-akt').removeClass('hidden');
			$('.contaner-payment').addClass('hidden');
			$('.contaner-contract').addClass('hidden');
			$('.contaner-status').removeClass('hidden');
			//$('.pagecontainer').removeClass('hidden');

			$('#ord').val('datum');
			$('#tuda').val('desc');

			break;
	}

	$('#tar').val(hesh);
	$('#page').val('1');
	$('#tuda').val('desc');

	$('.razdel .'+hesh).addClass('active');//.css('border','1px solid red');

	configpage();

}

$(window).on('resize', function(){

	if(!isMobile) setHeight();

});
$(window).on('resizeend', 200, function(){

	if(!isMobile) setHeight();

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

	$('.tableHeader').css({"width": "100%"});
	$('.ui-layout-content').css({"width": "100%"});
	$('#list_header').css({"width": "100%"});

});

function setHeight(){

	var hf = $('.ui-layout-center').actual('height') - $('.contaner[data-id="stat"]').actual('outerHeight') - 40;
	$('.contaner[data-id="filter"]').css({"height": hf + "px", "max-height": hf + "px"});

	if(hash === 'contract'){

		var hmm = 0;

		$('.mm').each(function(){

			hmm += $(this).outerHeight();

		});

		//console.log('hmm = ' + hmm);

		var hc = hf - hmm - 60;

		$('#doctype').css({"max-height": hc + 'px'});

	}

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
function search(){

	$('#page').val('1');
	configpage();

}
function change_page(num){

	$('#page').val(num);
	configpage();

}

/*
$('#wordc').on('keydown', function (e) {

	var keycode = e.originalEvent.key;

	if (keycode === 'Enter') { // escape, close box, esc

		search();
		return false;

	}

});
$('#wordp').on('keydown', function (e) {

	var keycode = e.originalEvent.key;

	if (keycode === 'Enter') { // escape, close box, esc

		search();
		return false;

	}

});
$('#worda').on('keydown', function (e) {

	var keycode = e.originalEvent.key;

	if (keycode === 'Enter') { // escape, close box, esc

		search();
		return false;

	}

});
*/

</script>
<?php require_once $rootpath."/inc/panel.php"; flush();?>
</body>
</html>