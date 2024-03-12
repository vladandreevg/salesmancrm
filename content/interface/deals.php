<?php
/* ============================ */
/*         SalesMan CRM         */
/* ============================ */
/* (C) 2016 Vladislav Andreev   */
/*       SalesMan Project       */
/*        www.isaler.ru         */
/*        ver. 2017.x           */
/* ============================ */

$title = "Продажи";

$tip = $_REQUEST[ 'tip' ];

$preset = $_COOKIE[ 'deal_list' ] != '' ? json_decode( str_replace( '\\', '', $_COOKIE[ 'deal_list' ] ), true ) : [];

//print_r($preset);

$prcat = ( $preset[ 'idcategory' ] != '' ) ? $preset[ 'idcategory' ] : [];

if ( $tip == '' ) {

	$tip = $preset['tar'] != '' ? $preset['tar'] : 'my';

}
if ( $tipuser == 'Поддержка продаж' ) {
	$tip = 'all';
}


global $rootpath;
require_once $rootpath."/inc/head.php";
flush();
?>
<DIV class="" id="rmenu">

	<div class="tabs">

		<a href="javascript:void(0)" class="lpToggler open" title="Фильтры"><i class="icon-toggler"></i></a>
		<a href="javascript:void(0)" onclick="configpage();" title="Обновить представление"><i class="icon-arrows-cw"></i></a>

		<a href="#my" class="razdel pl5 pr5" data-id="my" title="Мои <?= $lang[ 'face' ][ 'DealsName' ][ '0' ] ?>"><i class="icon-briefcase-1"><i class="sup icon-user-1 fs-05 blue"></i></i></a>
		<?php if ( $tipuser != "Менеджер продаж" || $userRights['alls'] ) { ?>
			<a href="#all" class="razdel pl5 pr5" data-id="all" title="Все <?= $lang[ 'face' ][ 'DealsName' ][ '0' ] ?>"><i class="icon-briefcase-1"><i class="sup icon-users-1 fs-05 red"></i></i></a>
		<?php } ?>
		<a href="#otdel" class="razdel pl5 pr5" data-id="otdel" title="<?= $lang[ 'face' ][ 'DealsName' ][ '0' ] ?> Подчиненных"><i class="icon-briefcase-1"><i class="sup icon-user-1 fs-05 orange"></i></i></a>

		<?php 
		require_once $rootpath."/content/leftnav/leftpop.php";
		flush(); 
		?>

	</div>

	<?php 
	require_once $rootpath."/content/leftnav/counters.php";
	flush(); 
	?>

</DIV>

<DIV class="ui-layout-north mainbg">

	<?php
	require_once $rootpath."/inc/menu.php";
	flush(); 
	?>

</DIV>
<DIV class="ui-layout-west disable--select compact">

	<?php
	require_once $rootpath."/content/leftnav/deal.php";
	flush(); 
	?>

</DIV>
<DIV class="ui-layout-center disable--select compact">

	<DIV class="mainbg listHead p0 hidden-iphone">

		<div class="flex-container p10">

			<div class="column flex-column wp50 fs-11 pl5 border-box">

				<b class="shado"><?= $lang[ 'face' ][ 'DealsName' ][ 0 ] ?></b><span class="hidden-iphone"> / </span><span id="tips">Мои сделки</span>

			</div>
			<div class="column flex-column wp50 text-right">

				<div class="menu_container" data-step="9" data-intro="<h1>Меню действий.</h1>Выполнение доступных действий" data-position="left">

					<a href="javascript:void(0)" onclick="submenu('sub')" title="Действия" class="tagsmenuToggler hidden-ipad"><b>Действия</b>&nbsp;<i class="icon-angle-down" id="mapi"></i></a>

					<div class="tagsmenu toright hidden">

						<div class="items noBold fs-09">

							<?php if ( $userRights['deal']['create'] ) { ?>
								<div onclick="editDogovor('','add');" class="item ha hand" title="Добавить <?= $lang[ 'face' ][ 'DealName' ][ 3 ] ?>">
									<span><i class="icon-briefcase broun"><i class="sup icon-plus red"></i></i></span>&nbsp;&nbsp;Добавить <?= $lang[ 'face' ][ 'DealName' ][ 3 ] ?>
								</div>
							<?php } ?>
							<?php if ( $isadmin == 'on' || $tipuser == 'Администратор' || $userRights['groupactions'] ) { ?>
								<div onclick="massSend()" title="Групповые действия" class="item ha hand">
									<span><i class="icon-magic blue"><i class="sup icon-direction red"></i></i></span>&nbsp;&nbsp;Групповые действия
								</div>
							<?php } ?>
							<?php if ( $isadmin == 'on' || $tipuser == 'Администратор' || $userRights['import'] ) { ?>
								<div onclick="doLoad('/content/helpers/deal.import.php?action=import');" title="Импорт" class="item ha hand">
									<span><i class="icon-database green"></i></span>&nbsp;&nbsp;Импорт <?= $lang[ 'face' ][ 'DealsName' ][ 1 ] ?>
								</div>
							<?php } ?>

						</div>

					</div>

				</div>

				<a href="javascript:void(0)" title="Снять все фильтры" onclick="clearall();"><i class="icon-filter"><i class="sup icon-cancel red"></i></i><span class="hidden-netbook">&nbsp;&nbsp;Снять фильтры</span>&nbsp;&nbsp;&nbsp;</a>
				<a href="javascript:void(0)" title="Обновить представление" onclick="configpage();"><i class="icon-arrows-cw blue"></i><span class="hidden-netbook">Обновить</span>&nbsp;</a>
				<a href="javascript:void(0)" onclick="getColumnEditor('deal')" title="Настроить колонки" class="hidden-ipad" data-step="8" data-intro="<h1>Редактор списка.</h1>Поможет настроить вывод списка - порядок колонок, включить/отключить колонки и задать их ширину" data-position="left"><i class="icon-th blue"></i>&nbsp;&nbsp;</a>

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
	var $display = 'deals';
	var $face = 'compact simple';

	//нужно ли менять статистику записей
	var $dstatic = 'yes';

	if (isMobile || $(window).width() < 767) {

		$('.lpToggler').toggleClass('open');

	}

	$.Mustache.load('/content/tpl/tpl.deals.mustache');

	$(function () {

		if (isPad) $('.lpToggler').trigger('click');

		$('.ui-layout-center').append('<div class="tableHeader" style="position:absolute"></div>');

		var hash = window.location.hash.substring(1);

		//загружаем сохраненные фильтры. начало

		var preset = getCookie('deal_list');
		var presetAr = JSON.parse(preset);

		if (preset !== null) {

			if (presetAr.tar !== '' && presetAr.tar != null) hash = presetAr.tar;
			if (presetAr.ord !== '' && presetAr.ord != null) $('#ord').val(presetAr.ord);
			if (presetAr.tuda !== '' && presetAr.tuda != null) $('#tuda').val(presetAr.tuda);
			if (presetAr.tid !== '' && presetAr.tid != null) $('#tid').find('[value="' + presetAr.tid + '"]').prop("selected", true);
			if (presetAr.direction !== '' && presetAr.direction != null) $('#direction').find('[value="' + presetAr.direction + '"]').prop("selected", true);
			if (presetAr.mcid !== '' && presetAr.mcid != null) $('#mcid').find('[value="' + presetAr.mcid + '"]').prop("selected", true);

			if (typeof presetAr.idcategory === 'array' && presetAr.idcategory != null) {

				var ar = presetAr.idcategory;

				for (var i in ar) {

					$('#idcategory\\[\\]').find('[value="' + ar[i] + '"]').prop("selected", true);

				}

			}

		}

		//загружаем сохраненные фильтры. конец

		if (hash === '')
			hash = 'my';

		$(window).trigger('onhashchange');

		$('#list').not('[value="' + hash + '"]').prop("selected", false);
		$('#list').find('[value="' + hash + '"]').prop("selected", true);

		$('#rmenu').find('a').removeClass('active');
		$('#rmenu').find('a[data-id="' + hash + '"]').addClass('active');

		change_us();

		constructSpace();

		configpage();

		changeMounth();

		$(".nano").nanoScroller();

		/*tooltips*/
		$('#pptt .tooltips').append("<span></span>");
		$('#pptt .tooltips:not([tooltip-position])').attr('tooltip-position', 'bottom');
		$("#pptt .tooltips").on('mouseenter', function () {
			$(this).find('span').empty().append($(this).attr('tooltip'));
		});
		/*tooltips*/

		includeJS("/assets/js/dragtable-master/jquery.dragtable.js");
		includeCSS("/assets/js/dragtable-master/dragtable.css");


	});

	window.onhashchange = function () {

		var hash = window.location.hash.substring(1);

		$('#list').not('[value="' + hash + '"]').prop("selected", false);
		$('#list').find('[value="' + hash + '"]').prop("selected", true);

		configpage();

		$('#rmenu').find('a').removeClass('active');
		$('#rmenu').find('a[data-id="' + hash + '"]').addClass('active');

		if (hash === 'my') $('#iduser').prop('disabled', true);
		else $('#iduser').prop('disabled', false);

	};

	function constructSpace() {

		//var hw = $('.ui-layout-center').width();
		var ht = ($('.listHead').is(':visible')) ? $('.listHead').actual('outerHeight') : 0;
		var hh = $('.ui-layout-center').actual('height') - ht - 5;

		$('#clientlist').css({"height": hh + 'px'});
		//$('.ui-layout-center').find('.tableHeader').css({"top": ht + 'px', "left" : "0px"});

		var hf = $('.ui-layout-center').actual('height') - $('.contaner[data-id="stat"]').actual('outerHeight') - 80;
		$('.contaner[data-id="filterform"]').css({"height": hf + "px", "max-height": hf + "px"});


		if ($('#list').val() === 'my') $('#iduser').prop('disabled', true);
		else $('#iduser').prop('disabled', false);

		$('.nano').nanoScroller();

	}

	$(window).on('resize', function () {

		if (!isMobile)
			constructSpace();

	});
	$(window).on('resizeend', 200, function () {

		if (!isMobile) {

			constructSpace();
			$('.ui-layout-center').trigger('onPositionChanged');

			//корректор показа колонок
			//fixColumns();

		}

	});

	$('#list').on('change', function () {

		var id = $(this).val();

		//меняем статистику
		$dstatic = 'yes';

		$('#rmenu').find('a').removeClass('active');
		$('#rmenu').find('a[data-id="' + id + '"]').addClass('active');

		window.location.hash = id;

		return false;

	});

	$('.lpToggler').on('click', function () {

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

	$('.ui-layout-center').onPositionChanged(function () {

		if (this.resizeTO) clearTimeout(this.resizeTO);
		this.resizeTO = setTimeout(function () {

			//корректор показа колонок
			//fixColumns();

		}, 1);

		$('.ui-layout-content').css({"width": "100%"});
		$('#list_header').css({"width": "100%"});

	});

	$(".showintro").on('click', function () {

		var intro = introJs();

		intro.setOptions({
			'nextLabel': 'Дальше',
			'prevLabel': 'Вернуть',
			'skipLabel': 'Пропустить',
			'doneLabel': 'Я понял',
			'showStepNumbers': false
		});

		intro.start().goToStep(4)
			.onbeforechange(function (targetElement) {

				switch ($(targetElement).attr("data-step")) {
					case "2":
						$('#menuclients').css('display', 'none');
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

	function configpage(istatic) {

		var tiplist = $('#list option:selected').val();

		$('#tips').html($('#list option:selected').text());

		if (!in_array(tiplist, ['my', 'all', 'otdel', 'close', 'alldealsday', 'other']))
			window.location.hash = 'search';

		var se = tiplist.split(':');

		if (se[0] === 'search') {

			$('#pptt').removeClass('hidden');

			$.get('/content/helpers/search.editor.deal.php?action=view&seid=' + se[1], function (data) {

				$('#pptt').find('span').empty().html(data);
				$('#pptt .tooltips').attr('tooltip', data);

			});

		}
		else {

			$('#pptt').addClass('hidden');
			$('#pptt .tooltips').attr('tooltip', 'Здесь будет расшифровка пользовательского представления');

		}

		$('#contentdiv').parent(".nano").nanoScroller({scroll: 'top'});

		$dstatic = (istatic === 'yes') ? 'no' : 'yes';

		//dealListConstruct();
		constructor();

		if ($('.lpToggler').hasClass('open') && isMobile)
			$('.lpToggler').trigger('click');

	}

	function constructor() {

		let str = $('#pageform').serialize() + '&showHistTip=' + showHistTip;
		let url = '/content/lists/list.deals.php';

		let cdheight = $('#contentdiv').height();
		let cdwidth = $('#contentdiv').width();

		$('#contentdiv').append('<div class="contentloader"><img src="/assets/images/Services.svg" width="50px" height="50px"></div>');
		$('.contentloader').height(cdheight).width(cdwidth);

		$.get(url, str, function (data) {

			$('#contentdiv').empty().mustache('dealsTpl', data);

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

			if ($dstatic !== 'no') {

				$('#alls').html(data.count);
				$('#allSelected').val(data.count);
				$('#dealKol').html(data.dealKol);
				$('#dealMarga').html(data.dealMarga);

			}

		}, 'json')
			.done(function () {

				if (!isMobile) {
					$(".nano").nanoScroller();
				}
				else {
					$('#contentdiv').find('table').rtResponsiveTables();
				}

				$dstatic = 'yes';

				//перемещаемые столбцы
				$('#list_header').dragtable({
					persistState: '/content/helpers/deal.helpers.php?action=columnOrderSave',
					dragaccept: '.drag--accept',
					dragHandle: '.thandler'
				});

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

				clearNBSP();

			});

	}

	function fixColumns() {

		if (!isMobile)
			$('#list_header').smTableColumnFixer({
				goal: '#title',
				donor: '#user',
				donors: ['#last_history_descr', '#history', '#mcid', '#tip', '#dcreate', '#direction']
			});

	}

	/*
	 Вызываем при применении фильтров, чтобы начинать с 1 страницы
	 */
	function preconfigpage() {

		$('#page').val('1');
		configpage();

	}

	function clearall() {

		$('#pageform')[0].reset();
		$('#word').val('');
		$('#tid').val('');
		$('#idcategory').val('');
		$('#direction').val('');
		$('#iduser').val('');
		$('#page').val('1');
		$('#isOld').val('');

		emptySelect();
		configpage();

		$(window).trigger('onhashchange');

	}

	function changesort(param) {

		var tt = $('#ord').val();

		$('#ord').val(param);
		$('#page').val('1');

		if (param === tt) {

			if ($('#tuda').val() === '')
				$('#tuda').val('desc');

			else
				$('#tuda').val('');

		}

		configpage('yes');

	}

	function change_page(page) {

		$('#ch').prop('checked', false);
		$('#page').val(page);

		configpage('yes');

	}

	function massSend() {

		var str = $("#cform").serialize();
		var count = $('.mc:checked').length;
		var url = '/content/forms/form.deal.php?action=mass&count=' + count + '&';

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
<?php
require_once $rootpath."/inc/panel.php";
flush();
?>
</body>
</html>