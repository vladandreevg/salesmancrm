<?php
/* ============================ */
/*         SalesMan CRM         */
/* ============================ */
/* (C) 2016 Vladislav Andreev   */
/*       SalesMan Project       */
/*        www.isaler.ru         */
/*        ver. 2017.x           */
/* ============================ */

$title = 'Группы';

global $rootpath;
require_once $rootpath."/inc/head.php";
flush();

if (!$userRights['group']) {
	print '
	<div class="warning" style="width:600px">
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

	<?php require_once $rootpath."/modules/group/navi.group.php"; flush();?>

</DIV>
<DIV class="ui-layout-center disable--select compact" style="overflow: hidden">

	<DIV class="mainbg listHead p0">

		<div class="pt5 pb10 flex-container">
			<div class="column flex-column wp50 fs-11 pl5 border-box">
				<b class="shado" id="pagetitle">Группы</b>&nbsp;/&nbsp;<span id="place">Группы</span>
			</div>
			<div class="column flex-column wp50 text-right">

				<span class="menu-glist">

				<a href="javascript:void(0)" onclick="editGroup('','mass');" title="Групповые действия"><i class="icon-exchange blue"></i>Групповые действия</a>&nbsp;
				<a href="javascript:void(0)" onclick="editGroup('','export');" title="Экспорт"><i class="icon-doc-text-inv blue"></i>&nbsp;Экспорт</a>&nbsp;&nbsp;

				</span>

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

		<?php if ( $isadmin == 'on' || $tipuser == 'Администратор' || $userRights['groupactions'] ) { ?>
			<div class="multi--buttons box--child hidden">

				<a href="javascript:void(0)" onclick="editGroup('','mass');" class="button bluebtn box-shadow amultidel" title="Групповые действия">
					<i class="icon-shuffle"></i>Групповые действия <span class="task--count">0</span>
				</a>
				<a href="javascript:void(0)" onclick="multiTaskClearCheck()" class="button greenbtn box-shadow amultidel" title="Снять выделение">
					<i class="icon-th"></i>Снять выделение <span class="task--count">0</span>
				</a>

			</div>
		<?php } ?>

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

var $display = 'group';

if (isMobile || $(window).width() < 767) {

	$('.lpToggler').toggleClass('open');

}

$( function() {

	//$('.ui-layout-center').append('<div class="tableHeader" style="position:absolute; width: 100%"></div>');

	$.Mustache.load('/modules/group/tpl.group.mustache');

	constructSpace();

	razdel();

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

function constructSpace(){

	var hf = $('.ui-layout-center').actual('height') - $('.contaner[data-id="stat"]').actual('outerHeight') - 30;
	$('.contaner[data-id="filter"]').css({"height": hf + "px", "max-height": hf + "px"});

	$('.nano').nanoScroller();

}

$(window).on('resize',function(){

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


	}, 200);

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

	$('#contentdiv').parent(".nano").nanoScroller({ scroll: 'top' });

	var str = $('#pageform').serialize();
	var url = 'modules/group/list.group.php';
	var tar = $('#tar').val();

	$('#contentdiv').append('<div class="contentloader"><img src="/assets/images/Services.svg" width="50px" height="50px"></div>');

	var cdheight = $('#contentdiv').height();
	var cdwidth = $('#contentdiv').width();

	$('.contentloader').height(cdheight).width(cdwidth);

	/*------------*/

	$.getJSON(url + '?' + str, function(viewData) {

		$('#contentdiv').empty().mustache(tar+'Tpl', viewData);

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

			$('tr[data-type="row"] td:first-child')
				.on('mousedown', function(){

					//$(this).closest('tr').toggleClass('yellowbg-sub');

					$('tr[data-type="row"] td:first-child').on('mouseenter',function(){

						if(isCtrl) {

							var $elm = $('input[type=checkbox]', this);

							//$(this).closest('tr').toggleClass('yellowbg-sub');
							//$elm.prop('checked', !$elm.prop("checked"));

							if ($elm.prop('checked')) {
								$elm.prop('checked', false);
								$(this).closest('tr').removeClass('yellowbg-sub');
							}
							else {
								$elm.prop('checked', true);
								$(this).closest('tr').addClass('yellowbg-sub');
							}

						}

					});

				})
				.on('mouseup', function(){

					$('tr[data-type="row"] td:first-child').off('mouseenter');

					var xcount = $('tr[data-type="row"].yellowbg-sub').length;

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

			$(".nano").nanoScroller();

		});
}

function change_page(page){

	$('#page').val(page);
	configpage();

}
/*
Вызываем при применении фильтров, чтобы начинать с 1 страницы
 */
function preconfigpage() {

	$('#page').val('1');
	configpage();

}
function changesort(param,gid){

	var tt = $('#ord').val();
	$('#ord').val(param);

	if(param === tt){

		if($('#tuda').val() === '')
			$('#tuda').val('desc');

		else
			$('#tuda').val('');

	}

	configpage();
}

function loadGroup(id, name){

	$('#gid').val(id);
	$('#tar').val('glist');
	$('#gname').val(name);

	configpage();

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