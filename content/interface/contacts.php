<?php
/* ============================ */
/*         SalesMan CRM         */
/* ============================ */
/* (C) 2016 Vladislav Andreev   */
/*       SalesMan Project       */
/*        www.isaler.ru         */
/*        ver. 2017.x           */
/* ============================ */

$title = "Контакты";

$filter = 'my';
if($tipuser == 'Поддержка продаж') {
	$filter = 'all';
}

$tip = 'person';

global $rootpath;
require_once $rootpath."/inc/head.php";
flush();
?>
<DIV class="" id="rmenu">

	<div class="tabs">

		<a href="javascript:void(0)" class="lpToggler open" title="Фильтры"><i class="icon-toggler"></i></a>
		<a href="javascript:void(0)" onclick="configpage();" title="Обновить представление"><i class="icon-arrows-cw"></i></a>

		<a href="#my" class="razdel pl5 pr5" data-id="my" title="Мои контакты"><i class="icon-user-1"><i class="sup icon-user-1 fs-05"></i></i></a>
		<?php if ($tipuser!="Менеджер продаж" || $userRights['alls']){?>
		<a href="#all" class="razdel pl5 pr5" data-id="all" title="Все контакты"><i class="icon-user-1"><i class="sup icon-users-1 fs-05"></i></i></a>
		<?php } ?>

		<?php require_once $rootpath."/content/leftnav/leftpop.php"; flush();?>

	</div>

	<?php require_once $rootpath."/content/leftnav/counters.php"; flush();?>

</DIV>

<DIV class="ui-layout-north mainbg">

	<?php require_once $rootpath."/inc/menu.php"; flush();?>

</DIV>
<DIV class="ui-layout-west disable--select compact">

	<?php require_once $rootpath."/content/leftnav/client.php"; flush();?>

</DIV>
<DIV class="ui-layout-center disable--select compact">

	<DIV class="mainbg listHead p0 hidden-iphone">

		<div class="flex-container p10">

			<div class="column flex-column wp50 fs-11 border-box">
				<b class="shado">Контакты</b><span class="hidden-iphone"> / </span><span id="tips">Мои контакты</span>
			</div>

			<div class="column flex-column wp50 text-right">

				<div class="menu_container hidden-ipad" data-step="9" data-intro="<h1>Меню действий.</h1>Выполнение доступных действий" data-position="left">

					<a href="javascript:void(0)" onclick="submenu('sub')" class="tagsmenuToggler" title="Действия"><b>Действия</b>&nbsp;<i class="icon-angle-down" id="mapi"></i></a>

					<div class="tagsmenu toright hidden">

						<div class="items noBold fs-09">

							<?php if($userRights['person']['create']){?>
							<div onclick="editPerson('','add');" title="Добавить Контакт" class="item ha hand"><span><i class="icon-user-1 blue"><i class="sup icon-plus red"></i></i></span>&nbsp;&nbsp;Добавить Контакт</div>
							<?php } ?>
							<?php if($userRights['client']['create']){?>
							<div onclick="editClient('','add');" title="Добавить Клиента" class="item ha hand"><span><i class="icon-commerical-building blue"><i class="sup icon-plus red"></i></i></span>&nbsp;&nbsp;Добавить Клиента</div>
							<?php } ?>
							<?php if($isadmin == 'on' || $tipuser=='Администратор' || $userRights['import']) {?>
							<div onclick="doLoad('/content/card/client.import.php?action=import');" title="Импорт" class="item ha hand"><span><i class="icon-database broun"><i class="sup icon-exchange red"></i></i></span>&nbsp;&nbsp;Импорт в базу</div>
							<div onclick="doLoad('/content/card/client.update.php?action=import');" title="Обновление" class="item ha hand"><span><i class="icon-database green"><i class="sup icon-exchange green"></i></i></span>&nbsp;&nbsp;Обновление записей</div>
							<?php } ?>
							<?php if($isadmin=='on' || $tipuser=='Администратор' || $userRights['groupactions']) {?>
							<div onclick="massSend()" title="Групповые действия" class="item ha hand"><span><i class="icon-magic blue"><i class="sup icon-direction red"></i></i></span>&nbsp;&nbsp;Групповые действия</div>
							<?php } ?>

						</div>

					</div>

				</div>

				<a href="javascript:void(0)" title="Снять все фильтры" onclick="clearall();"><i class="icon-filter"><i class="sup icon-cancel red"></i></i><span class="hidden-netbook">&nbsp;&nbsp;Снять фильтры</span></a>&nbsp;&nbsp;&nbsp;
				<a href="javascript:void(0)" title="Обновить представление" onclick="configpage();"><i class="icon-arrows-cw blue"></i><span class="hidden-netbook">Обновить</span></a>&nbsp;

			</div>

		</div>

		<DIV class="hidden-ipad">

			<TABLE class="mainbg">
			<TR class="th30 text-center" id="alfabet">
				<TD onclick="getColumnEditor('person')" class="alf" width="3%" data-step="8" data-intro="<h1>Редактор списка.</h1>Поможет настроить вывод списка - порядок колонок, включить/отключить колонки и задать их ширину" data-position="right"><i class="icon-th blue" title="Настроить отображение колонок"></i></TD>
				<TD onclick="getalf('')" data-a="" class="alf" width="4%" title="Снять фильтр по Алфавиту">ВСЕ</TD>
				<TD onclick="getalf('09')" data-a="09" class="alf" width="5%" title="Начинается с цифры">0-9</TD>
				<TD class="alf eng" width="5%" title="Название латинскими буквами">

					<div class="relativ">

						<a href="javascript:void(0)" class="menu_container overmenu tagsmenuToggler" style="width: 100%; height: 100%;">A-Z</a>

						<div class="tagsmenu left hidden w160">

							<div class="items noBold fs-10 text-center p5">

								<div class="alfabet" onclick="getalf('A')" data-a="A">A</div>
								<div class="alfabet" onclick="getalf('B')" data-a="B">B</div>
								<div class="alfabet" onclick="getalf('C')" data-a="C">C</div>
								<div class="alfabet" onclick="getalf('D')" data-a="D">D</div>
								<div class="alfabet" onclick="getalf('E')" data-a="E">E</div>
								<div class="alfabet" onclick="getalf('F')" data-a="F">F</div>
								<div class="alfabet" onclick="getalf('G')" data-a="G">G</div>
								<div class="alfabet" onclick="getalf('H')" data-a="H">H</div>
								<div class="alfabet" onclick="getalf('I')" data-a="I">I</div>
								<div class="alfabet" onclick="getalf('J')" data-a="J">J</div>
								<div class="alfabet" onclick="getalf('K')" data-a="K">K</div>
								<div class="alfabet" onclick="getalf('L')" data-a="L">L</div>
								<div class="alfabet" onclick="getalf('M')" data-a="M">M</div>
								<div class="alfabet" onclick="getalf('N')" data-a="N">N</div>
								<div class="alfabet" onclick="getalf('O')" data-a="O">O</div>
								<div class="alfabet" onclick="getalf('P')" data-a="P">P</div>
								<div class="alfabet" onclick="getalf('Q')" data-a="Q">Q</div>
								<div class="alfabet" onclick="getalf('R')" data-a="R">R</div>
								<div class="alfabet" onclick="getalf('S')" data-a="S">S</div>
								<div class="alfabet" onclick="getalf('T')" data-a="T">T</div>
								<div class="alfabet" onclick="getalf('U')" data-a="U">U</div>
								<div class="alfabet" onclick="getalf('V')" data-a="V">V</div>
								<div class="alfabet" onclick="getalf('W')" data-a="W">W</div>
								<div class="alfabet" onclick="getalf('X')" data-a="X">X</div>
								<div class="alfabet" onclick="getalf('Y')" data-a="Y">Y</div>
								<div class="alfabet" onclick="getalf('Z')" data-a="Z">Z</div>

							</div>

						</div>

					</div>

				</TD>
				<TD onclick="getalf('А')" class="alf" width="3%" title="Первая буква - А" data-a="А">А</TD>
				<TD onclick="getalf('Б')" class="alf" width="3%" title="Первая буква - Б" data-a="Б">Б</TD>
				<TD onclick="getalf('В')" class="alf" width="3%" title="Первая буква - В" data-a="В">В</TD>
				<TD onclick="getalf('Г')" class="alf" width="3%" title="Первая буква - Г" data-a="Г">Г</TD>
				<TD onclick="getalf('Д')" class="alf" width="3%" title="Первая буква - Д" data-a="Д">Д</TD>
				<TD onclick="getalf('Е')" class="alf" width="3%" title="Первая буква - Е" data-a="Е">Е</TD>
				<TD onclick="getalf('Ж')" class="alf" width="3%" title="Первая буква - Ж" data-a="Ж">Ж</TD>
				<TD onclick="getalf('З')" class="alf" width="3%" title="Первая буква - З" data-a="З">З</TD>
				<TD onclick="getalf('И')" class="alf" width="3%" title="Первая буква - И" data-a="И">И</TD>
				<TD onclick="getalf('К')" class="alf" width="3%" title="Первая буква - К" data-a="К">К</TD>
				<TD onclick="getalf('Л')" class="alf" width="3%" title="Первая буква - Л" data-a="Л">Л</TD>
				<TD onclick="getalf('М')" class="alf" width="3%" title="Первая буква - М" data-a="М">М</TD>
				<TD onclick="getalf('Н')" class="alf" width="3%" title="Первая буква - Н" data-a="Н">Н</TD>
				<TD onclick="getalf('О')" class="alf" width="3%" title="Первая буква - О" data-a="О">О</TD>
				<TD onclick="getalf('П')" class="alf" width="3%" title="Первая буква - П" data-a="П">П</TD>
				<TD onclick="getalf('Р')" class="alf" width="3%" title="Первая буква - Р" data-a="Р">Р</TD>
				<TD onclick="getalf('С')" class="alf" width="3%" title="Первая буква - С" data-a="С">С</TD>
				<TD onclick="getalf('Т')" class="alf" width="3%" title="Первая буква - Т" data-a="Т">Т</TD>
				<TD onclick="getalf('У')" class="alf" width="3%" title="Первая буква - У" data-a="У">У</TD>
				<TD onclick="getalf('Ф')" class="alf" width="3%" title="Первая буква - Ф" data-a="Ф">Ф</TD>
				<TD onclick="getalf('Х')" class="alf" width="3%" title="Первая буква - Х" data-a="Х">Х</TD>
				<TD onclick="getalf('Ц')" class="alf" width="3%" title="Первая буква - Ц" data-a="Ц">Ц</TD>
				<TD onclick="getalf('Ч')" class="alf" width="3%" title="Первая буква - Ч" data-a="Ч">Ч</TD>
				<TD onclick="getalf('Ш')" class="alf" width="3%" title="Первая буква - Ш" data-a="Ш">Ш</TD>
				<TD onclick="getalf('Щ')" class="alf" width="3%" title="Первая буква - Щ" data-a="Щ">Щ</TD>
				<TD onclick="getalf('Э')" class="alf" width="3%" title="Первая буква - Э" data-a="Э">Э</TD>
				<TD onclick="getalf('Ю')" class="alf" width="3%" title="Первая буква - Ю" data-a="Ю">Ю</TD>
				<TD onclick="getalf('Я')" class="alf" width="3%" title="Первая буква - Я" data-a="Я">Я</TD>
			</TR>
			</TABLE>

		</DIV>

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

	<?php if ( $isadmin == 'on' || $tipuser == 'Администратор' || $userRights['groupactions'] ) { ?>
		<div class="multi--buttons box--child hidden">

			<a href="javascript:void(0)" onclick="massSend()" class="button bluebtn box-shadow amultidel" title="Групповые действия">
				<i class="icon-shuffle"></i>Групповые действия <span class="task--count">0</span>
			</a>
			<a href="javascript:void(0)" onclick="multiTaskClearCheck()" class="button greenbtn box-shadow amultidel" title="Снять выделение">
				<i class="icon-th"></i>Снять выделение <span class="task--count">0</span>
			</a>

		</div>
	<?php } ?>

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

var $display = 'persons';
var $face = 'compact simple';

if(isMobile || $(window).width() < 767){

	$('.lpToggler').toggleClass('open');

}

$.Mustache.load('/content/tpl/tpl.persons.mustache');

$( function() {

	if(isPad) $('.lpToggler').trigger('click');

	$('.ui-layout-center').append('<div class="tableHeader" style="position:absolute; width:100%"></div>');

	var hash = window.location.hash.substring(1);

	if(hash === '') hash = 'my';

	$(window).trigger('onhashchange');

	$('#list').not('[value="'+hash+'"]').prop("selected", false);
	$('#list').find('[value="'+hash+'"]').prop("selected", true);

	$('#rmenu').find('a').removeClass('active');
	$('#rmenu').find('a[data-id="'+hash+'"]').addClass('active');

	change_us();

	constructSpace();

	configpage();

	changeMounth();

	$(".nano").nanoScroller();

	/*tooltips*/
	$('#pptt .tooltips').append("<span></span>");
	$('#pptt .tooltips:not([tooltip-position])').attr('tooltip-position','bottom');
	$("#pptt .tooltips").mouseenter(function(){
		$(this).find('span').empty().append($(this).attr('tooltip'));
	});
	/*tooltips*/


});

window.onhashchange = function() {

	var hash = window.location.hash.substring(1);

	$('#list').not('[value="'+hash+'"]').prop("selected", false);
	$('#list').find('[value="'+hash+'"]').prop("selected", true);

	configpage();

	$('#rmenu').find('a').removeClass('active');
	$('#rmenu').find('a[data-id="'+hash+'"]').addClass('active');

	if(hash === 'my') $('#iduser').prop('disabled', true);
	else             $('#iduser').prop('disabled', false);

};

function constructSpace(){

	var hw = $('.ui-layout-center').width();
	var ht = ( $('.listHead').is(':visible') ) ? $('.listHead').actual('outerHeight') : 0;
	var hh = $('.ui-layout-center').actual('height') - ht;

	$('#clientlist').css({"height": hh + 'px'});
	//$('.ui-layout-center').find('.tableHeader').css({"top": ht + 'px', "left" : "0px"});

	var hf = $('.ui-layout-center').actual('height') - $('.contaner[data-id="stat"]').actual('outerHeight') - 70;
	$('.contaner[data-id="filterform"]').css({"height": hf + "px", "max-height": hf + "px"});


	if($('#list').val() === 'my') $('#iduser').prop('disabled', true);
	else                         $('#iduser').prop('disabled', false);

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

$('#list').on('change', function(){

	var id = $(this).val();

	$('#rmenu').find('a').removeClass('active');
	$('#rmenu').find('a[data-id="'+id+'"]').addClass('active');

	window.location.hash = id;

	return false;

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

	//$('.tableHeader').css({"width": "100%"});
	$('.ui-layout-content').css({"width": "100%"});
	$('#list_header').css({"width": "100%"});

});

$(".showintro").on('click',function() {
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

	var tiplist = $('#list option:selected').val();

	$('#tips').html( $('#list option:selected').text() );

	var alf = $('#alf').val();

	if(alf === '09') alf = '0-9';
	if(alf === 'ВСЕ') alf = '';

	$('#alfabet .alf').each(function(){

		if( $(this).data('a') === alf ) {

			$('.eng').removeClass('active');
			$('.alfabet').removeClass('active');
			$(this).addClass('active');

		}
		else $(this).removeClass('active');

	});

	$('#alfabet .alfabet').each(function(){

		if($(this).data('a') === alf) {

			$('#alfabet td').removeClass('active');
			$(this).addClass('active');
			$('.eng').addClass('active');

		}
		else $(this).removeClass('active');

	});

	if(!in_array(tiplist, ['my','all','partner','contractor','concurent','other']))
		window.location.hash = 'search';

	var se = tiplist.split(':');
	if(se[0] === 'search'){

		$('#pptt').removeClass('hidden');
		$.get('/content/helpers/search.editor.client.php?action=view&tip=person&seid='+se[1], function(data){

			$('#pptt').find('span').empty().html(data);
			$('#pptt .tooltips').attr('tooltip', data);

		});

	}
	else {

		$('#pptt').addClass('hidden');
		$('#pptt .tooltips').attr('tooltip', 'Здесь будет расшифровка пользовательского представления');

	}

	$('#contentdiv').parent(".nano").nanoScroller({ scroll: 'top' });

	//contactListConstruct();
	constructor();

	if( $('.lpToggler').hasClass('open') && isMobile ) $('.lpToggler').trigger('click');

	$.ajax({
		type: "GET",
		url: '/content/helpers/stat.client.php?tip=person',
		success: function (viewData) {

			$('#stat').html(viewData);

			var hf = $('.ui-layout-center').actual('height') - $('.contaner[data-id="stat"]').actual('outerHeight') - 70;
			$('.contaner[data-id="filterform"]').css({"height": hf + "px", "max-height": hf + "px"});

		}
	});

	//$('#stat').load('content/card/stat.client.php?tip='+tiplist).append('<img src="images/loading.gif">');


}

function constructor() {

	let str = $('#pageform').serialize() + '&showHistTip=' + showHistTip;
	let url = '/content/lists/list.persons.php';
	let elm = $('#contentdiv');

	let cdheight = elm.height();
	let cdwidth = elm.width();

	elm.append('<div class="contentloader"><img src="/assets/images/Services.svg" width="50px" height="50px"></div>');
	$('.contentloader').height(cdheight).width(cdwidth);

	$.get(url, str, function (data) {

		elm.empty().mustache('personsTpl', data);
		$('.contentloader').remove();

		let page = data.page;
		let pageall = data.pageall;

		let pg = 'Стр. ' + page + ' из ' + pageall;

		if (pageall > 1) {

			let prev = page - 1;
			let next = page + 1;

			if (page === 1)
				pg = pg + '&nbsp;<a href="javascript:void(0)" onclick="change_page(\'' + next + '\')" title="Следующая"><i class="icon-angle-right"></i></a>&nbsp;&nbsp;<a href="javascript:void(0)" onclick="change_page(\'' + pageall + '\')" title="Последняя"><i class="icon-angle-double-right"></i></a>&nbsp;';

			else if (page === pageall)
				pg = pg + '&nbsp;<a href="javascript:void(0)" onclick="change_page(\'1\')" title="Начало"><i class="icon-angle-double-left"></i></a>&nbsp;&nbsp;<a href="javascript:void(0)" onclick="change_page(\'' + prev + '\')" title="Предыдущая"><i class="icon-angle-left"></i></a>&nbsp;';

			else
				pg = '&nbsp;<a href="javascript:void(0)" onclick="change_page(\'1\')" title="Начало"><i class="icon-angle-double-left"></i></a>&nbsp;&nbsp;<a href="javascript:void(0)" onclick="change_page(\'' + prev + '\')" title="Предыдущая"><i class="icon-angle-left"></i></a>&nbsp;' + pg + '&nbsp;<a href="javascript:void(0)" onclick="change_page(\'' + next + '\')" title="Следующая"><i class="icon-angle-right"></i></a>&nbsp;&nbsp;<a href="javascript:void(0)" onclick="change_page(\'' + pageall + '\')" title="Последняя"><i class="icon-angle-double-right"></i></a>&nbsp;';

		}

		$('#pagediv').html(pg);

		$('#alls').html(data.count);
		$('#allSelected').val(data.count);

	}, 'json')
		.done(function () {

			if (!isMobile)
				$(".nano").nanoScroller();

			if (isMobile)
				$('#contentdiv').find('table').rtResponsiveTables();

			$dstatic = 'yes';

			//корректор показа колонок
			//fixColumns();

			$('tr[data-type="row"] td:first-child')
				.on('mousedown', function(){

					if(isCtrl) {

						$('tr[data-type="row"] td:first-child').on('mouseenter',function(){

								var $elm = $('input[type=checkbox]', this);

								if ($elm.prop('checked')) {
									$elm.prop('checked', false);
									$(this).closest('tr').removeClass('yellowbg-sub');
								}
								else {
									$elm.prop('checked', true);
									$(this).closest('tr').addClass('yellowbg-sub');
								}

						});

					}

				})
				.on('mouseup', function(){

					$('tr[data-type="row"] td:first-child').off('mouseenter');

					var xcount = $('tr[data-type="row"] input[type=checkbox]:checked').length;

					if(xcount > 0) {
						$('.multi--buttons').removeClass('hidden');
					}
					else {
						$('.multi--buttons').addClass('hidden');
					}

					$('.task--count').html( '( <b>' + xcount + '</b> )' );

				});

			$('input.mc')
				.off('change')
				.on('change', function (){

					if ($(this).prop('checked')) {
						$(this).closest('tr').addClass('yellowbg-sub');
					}
					else {
						$(this).closest('tr').removeClass('yellowbg-sub');
					}

					var xcount = $('input.mc:checked').length;

					if (xcount > 0) {
						$('.multi--buttons').removeClass('hidden');
					}
					else {
						$('.multi--buttons').addClass('hidden');
					}

					$('.task--count').html('( <b>' + xcount + '</b> )');

				})

			clearNBSP();

		});

}

/*
Вызываем при применении фильтров, чтобы начинать с 1 страницы
 */
function preconfigpage() {

	$('#page').val('1');
	configpage();

}

function clearall(){

	$('#pageform')[0].reset();
	$('#alf').val('');
	$('#page').val('1');

	$('.taskss').attr('checked',false);

	emptySelect();
	configpage();

	$(window).trigger('onhashchange');

}
function getalf(alf){

	$('#alf').val(alf);
	$('#page').val('1');

	configpage();
}
function page_refresh(){
	configpage();
}
function changesort(param){

	var tt = $('#ord').val();

	$('#ord').val(param);
	$('#page').val('1');

	if (param === tt){

		if ($('#tuda').val()=='') $('#tuda').val('desc');
		else $('#tuda').val('');

	}
	configpage('');

}
function change_page(page){

	$('#ch').prop('checked', false);
	$('#page').val(page);
	configpage();

}

function massSend(){

	var str = $("#cform").serialize();
	var count = $('.mc:checked').length;
	var url = '/content/forms/form.person.php?action=mass&count='+count+'&';

	doLoad(url + str);

	$('.multi--buttons').addClass('hidden');

	return false;

}

function multiTaskClearCheck() {

	$('tr[data-type="row"]').removeClass('yellowbg-sub');
	$("input[type=checkbox]:checked").prop('checked',false);
	$('.multi--buttons').addClass('hidden');

}

</script>
<?php require_once $rootpath."/inc/panel.php"; flush();?>
</body>
</html>