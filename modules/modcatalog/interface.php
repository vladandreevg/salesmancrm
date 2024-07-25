<?php
/* ============================ */
/*         SalesMan CRM         */
/* ============================ */
/* (C) 2016 Vladislav Andreev   */
/*       SalesMan Project       */
/*        www.isaler.ru         */
/*        ver. 2017.x           */
/* ============================ */

$title = "Каталог. Склад";

global $rootpath;
require_once $rootpath."/inc/head.php";
flush();

$msettings            = $db -> getOne( "SELECT settings FROM ".$sqlname."modcatalog_set WHERE identity = '$identity'" );
$msettings            = json_decode( (string)$msettings, true );
$msettings['mcSklad'] = 'yes';

//print_r($msettings);

if ( !in_array( $iduser1, (array)$msettings['mcSpecialist'] ) && !in_array( $iduser1, (array)$msettings['mcCoordinator'] ) ) {

	print '<div class="bad pad10" align="center" style="margin-top: 100px"><br />Доступ запрещен.<br />Обратитесь к администратору.<br /><br /></div>';
	exit;

}

define( "modulePath", $msettings['mpath'] );
const moduleName   = "Каталог. Склад";
const moduleBDName = "modcatalog";
?>

<DIV class="" id="rmenu">

	<div class="tabs">

		<a href="javascript:void(0)" class="lpToggler open" title="Фильтры"><i class="icon-toggler"></i></a>

		<A href="#catalog" class="razdel pl5 pr5" data-id="catalog" title="Каталог"><i class="icon-archive"></i></A>
		<A href="#sklad" class="razdel pl5 pr5 visible-min-h700" data-id="sklad" title="Позиции по складам"><i class="icon-archive"><i class="sup icon-docs fs-05"></i></i></A>
		<A href="#zayavka" class="razdel pl5 pr5 visible-min-h590" data-id="zayavka" title="Заявки"><i class="icon-doc-text-inv"><i class="sup icon-folder-1 fs-05"></i></i></A>
		<A href="#poz" class="razdel pl5 pr5 visible-min-h700" data-id="poz" title="Позиции заявок"><i class="icon-doc-text-inv"><i class="sup icon-tasks fs-05"></i></i></A>
		<A href="#order" class="razdel pl5 pr5 visible-min-h700" data-id="order" title="Ордеры"><i class="icon-doc-text-inv"><i class="sup icon-list fs-05"></i></i></A>
		<A href="#move" class="razdel pl5 pr5 visible-min-h700" data-id="move" title="История перемещений"><i class="icon-shuffle-1"></i></A>

		<div title="<?= $lang['face']['More'] ?>" class="leftpop hidden-min-h700">

			<i class="icon-dot-3"></i>

			<ul class="menu" style="width: 200px !important;">

				<li>
					<A href="#sklad" class="razdel nowrap" data-id="sklad" title="Позиции по складам"><i class="icon-archive"><i class="sup icon-docs fs-07"></i></i>Позиции по складам</A>
				</li>
				<li>
					<A href="#poz" class="razdel nowrap" data-id="poz" title="Позиции заявок"><i class="icon-doc-text-inv"><i class="sup icon-tasks fs-07"></i></i>Позиции заявок</A>
				</li>
				<li>
					<A href="#order" class="razdel nowrap" data-id="order" title="Ордеры"><i class="icon-doc-text-inv"><i class="sup icon-list fs-07"></i></i>Ордеры</A>
				</li>
				<li>
					<A href="#move" class="razdel pl5 pr5 visible-min-h700" data-id="move" title="История перемещений"><i class="icon-archive"><i class="sup icon-shuffle-1 fs-07"></i></i>Перемещения</A>
				</li>

			</ul>

		</div>

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

	<?php require_once $rootpath."/modules/modcatalog/navi.sklad.php"; ?>

</DIV>
<DIV class="ui-layout-center disable--select compact" style="overflow: hidden">

	<DIV class="mainbg listHead p0 hidden-ipad">

		<div class="flex-container p10">

			<div class="column flex-column wp50 fs-11 pl5 border-box">

				<b><?= moduleName ?></b>&nbsp;/&nbsp;<span id="tips"></span>

			</div>
			<div class="column flex-column wp50 text-right">

				<div class="menu_container inline hidden menu-catalog">
					<?php
					if ( in_array( $iduser1, $msettings['mcCoordinator'] ) ) {
						?>
						<div class="menu_container">

							<a href="javascript:void(0)" onclick="submenu('sub3')" class="tagsmenuToggler"><b>Действия</b>&nbsp;<i class="icon-angle-down" id="mapi"></i></a>

							<div class="tagsmenu toright hidden">

								<div class="items noBold fs-09 autoHeight">

									<div onclick="doLoad('/modules/modcatalog/form.modcatalog.php?action=edit')" class="item ha hand">
										<span><i class="icon-archive green"><i class="sup icon-plus-circled red"></i></i></span>&nbsp;&nbsp;Добавить
									</div>
									<div onclick="editPrice('','cat.list');" class="item ha hand">
										<span><i class="icon-folder blue"><i class="sup icon-pencil red"></i></i></span>&nbsp;&nbsp;Редактор категорий
									</div>
									<?php
									if ( $msettings['mcUseOrder'] == 'yes' ) {
										?>
										<div onclick="doLoad('/modules/modcatalog/form.modcatalog.php?action=editakt&tip=income');" class="item ha hand">
											<span><i class="icon-doc-text-inv blue"><i class="sup icon-plus-circled-1 green"></i></i></span>&nbsp;&nbsp;Приходный ордер
										</div>
										<div onclick="doLoad('/modules/modcatalog/form.modcatalog.php?action=editakt&tip=outcome');" class="item ha hand">
											<span><i class="icon-doc-text-inv blue"><i class="sup icon-minus-circled red"></i></i></span>&nbsp;&nbsp;Расходный ордер
										</div>
									<?php } ?>
									<div onclick="doLoad('/modules/modcatalog/autoreserve.php?action=reserv&print=yes');" class="item ha hand">
										<span><i class="icon-box-1 blue"><i class="sup icon-arrows-cw green"></i></i></span>&nbsp;&nbsp;Обновить резерв
									</div>
									<div onclick="editPrice('','import');" class="item ha hand">
										<span><i class="icon-database broun"><i class="sup icon-exchange red"></i></i></span>&nbsp;&nbsp;Импорт из Excel
									</div>
									<div onclick="editPrice('','export')" class="item ha hand">
										<span><i class="icon-doc-text-inv blue"><i class="sup icon-exchange red"></i></i></span>&nbsp;&nbsp;Экспорт в Excel
									</div>
									<?php if ( $isadmin == 'on' || $tipuser == 'Администратор' || $userRights['groupactions'] ) { ?>
										<div onclick="massSend()" title="Групповые действия" class="item ha hand"><span><i class="icon-magic blue"><i class="sup icon-direction red"></i></i></span>&nbsp;&nbsp;Групповые действия
										</div>
									<?php } ?>

								</div>

							</div>

						</div>
					<?php } ?>
				</div>
				<div class="menu_container inline hidden menu-zayavka">

					<div class="menu_container">

						<a href="javascript:void(0)" onclick="submenu('sub2')" class="tagsmenuToggler"><b>Действия</b>&nbsp;<i class="icon-angle-down" id="mapi"></i></a>

						<div class="tagsmenu toright hidden">

							<div class="items noBold fs-09">

								<div onclick="doLoad('/modules/modcatalog/form.modcatalog.php?action=editzayavka');" class="item ha hand">
									<span><i class="icon-doc-text-inv blue"><i class="sup icon-plus-circled-1 green"></i></i></span>&nbsp;&nbsp;Добавить заявку
								</div>
								<div onclick="doLoad('/modules/modcatalog/form.modcatalog.php?action=editzayavka&tip=cold');" class="item ha hand">
									<span><i class="icon-doc-text-inv blue"><i class="sup icon-plus-circled-1 green"></i></i></span>&nbsp;&nbsp;Добавить заявку на поиск
								</div>
								<?php
								if ( $msettings['mcUseOrder'] == 'yes' ) {
									?>
									<div onclick="doLoad('/modules/modcatalog/form.modcatalog.php?action=editakt&tip=income');" class="item ha hand">
										<span><i class="icon-doc-text-inv blue"><i class="sup icon-plus-circled-1 green"></i></i></span>&nbsp;&nbsp;Приходный ордер
									</div>
									<div onclick="doLoad('/modules/modcatalog/form.modcatalog.php?action=editakt&tip=outcome');" class="item ha hand">
										<span><i class="icon-doc-text-inv blue"><i class="sup icon-minus-circled red"></i></i></span>&nbsp;&nbsp;Расходный ордер
									</div>
								<?php } ?>
								<div onclick="doLoad('/modules/modcatalog/autoreserve.php?action=reserv&print=yes');" class="item ha hand">
									<span><i class="icon-box-1 blue"><i class="sup icon-arrows-cw green"></i></i></span>&nbsp;&nbsp;Обновить резерв
								</div>
								<div onclick="doLoad('/modules/modcatalog/autoreserve.php?action=status&print=yes');" class="item ha hand">
									<span><i class="icon-database blue"><i class="sup icon-arrows-cw red"></i></i></span>&nbsp;&nbsp;Обновить статусы
								</div>

							</div>

						</div>

					</div>

				</div>
				<div class="menu_container inline hidden menu-order">

					<div class="menu_container">

						<a href="javascript:void(0)" onclick="submenu('sub')" class="tagsmenuToggler"><b>Действия</b>&nbsp;<i class="icon-angle-down" id="mapi"></i></a>

						<div class="tagsmenu toright hidden">

							<div class="items noBold fs-09">

								<div onclick="doLoad('/modules/modcatalog/form.modcatalog.php?action=editakt&tip=income');" class="item ha hand">
									<span><i class="icon-doc-text-inv blue"><i class="sup icon-plus-circled-1 green"></i></i></span>&nbsp;&nbsp;Приходный ордер
								</div>
								<div onclick="doLoad('/modules/modcatalog/form.modcatalog.php?action=editakt&tip=outcome');" class="item ha hand">
									<span><i class="icon-doc-text-inv blue"><i class="sup icon-minus-circled red"></i></i></span>&nbsp;&nbsp;Расходный ордер
								</div>

							</div>

						</div>

					</div>

				</div>
				<div class="menu_container inline hidden menu-sklad">

					<div class="menu_container">

						<a href="javascript:void(0)" onclick="submenu('sub4')" class="tagsmenuToggler"><b>Действия</b>&nbsp;<i class="icon-angle-down" id="mapi"></i></a>

						<div class="tagsmenu toright hidden">

							<div class="items noBold fs-09">

								<div onclick="moveToSklad()" class="item ha hand">
									<span><i class="icon-shuffle blue"><i class="sup icon-direction red"></i></i></span>&nbsp;&nbsp;Переместить выделенное
								</div>
								<div onclick="exportPoz()" class="item ha hand"><span><i class="icon-doc-text-inv blue"><i class="sup icon-exchange red"></i></i></span>&nbsp;&nbsp;Экспорт в Excel
								</div>

							</div>

						</div>

					</div>

				</div>

				<A href="javascript:void(0)" onclick="configpage()"><i class="icon-arrows-cw blue"></i>&nbsp;Обновить</A>&nbsp;&nbsp;

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

<script src="/assets/js/jquery.liTextLength.js"></script>
<script>

	var hash = window.location.hash.substring(1);
	var $display = 'sklad';

	if (hash == '') hash = 'catalog';

	if (isMobile || $(window).width() < 767) {

		$('.lpToggler').toggleClass('open');

	}

	$(function () {

		//$('.ui-layout-center').append('<div class="tableHeader" style="position:absolute"></div>');

		$('#rmenu').find('a').removeClass('active');
		$('#rmenu').find('a[data-id="' + hash + '"]').addClass('active');

		$(window).trigger('onhashchange');

		$.Mustache.load('/modules/modcatalog/tpl.modcatalog.mustache');

		razdel(hash);

		$(".nano").nanoScroller();

		changeMounth();

	});

	window.onhashchange = function () {

		var hash = window.location.hash.substring(1);

		razdel(hash);

		$('#rmenu').find('a').removeClass('active');
		$('#rmenu').find('a[data-id="' + hash + '"]').addClass('active');

	};


	$(document).on('click', '.ifolder a', function () {

		var id = $(this).data('id');
		var title = $(this).data('title');

		$('.ifolder a').removeClass('fol_it');
		$(this).addClass('fol_it');

		$('#idcat').val(id);
		$('#tips').html(title);
		$('#page').val('');

        if($(this).hasClass("local-file-item")){
            return;
        }

		preconfigpage();

	});

	function configpage() {

		$('#contentdiv').parent(".nano").nanoScroller({scroll: 'top'});

		var str = $('#pageform').serialize();
		var url = '/modules/modcatalog/list.modcatalog.php';
		var tar = $('#tar').val();

		$('#contentdiv').append('<div class="contentloader"><img src="/assets/images/Services.svg" width="50px" height="50px"></div>');

		var cdheight = $('#contentdiv').height();
		var cdwidth = $('#contentdiv').width();

		$('.contentloader').height(cdheight).width(cdwidth);

		/*------------*/

		$.getJSON(url, str, function (viewData) {

			$('#contentdiv').empty().mustache(tar + 'Tpl', viewData);

			var page = viewData.page;
			var pageall = viewData.pageall;

			var pg = 'Стр. ' + page + ' из ' + pageall;

			if (pageall > 1) {

				var prev = page - 1;
				var next = page + 1;

				if (page === 1)
					pg = pg + '&nbsp;<a href="javascript:void(0)" onclick="change_page(\'' + next + '\')" title="Следующая"><i class="icon-angle-right"></i></a>&nbsp;&nbsp;<a href="javascript:void(0)" onclick="change_page(\'' + pageall + '\')" title="Последняя"><i class="icon-angle-double-right"></i></a>&nbsp;';

				else if (page === pageall)
					pg = pg + '&nbsp;<a href="javascript:void(0)" onclick="change_page(\'1\')" title="Начало"><i class="icon-angle-double-left"></i></a>&nbsp;&nbsp;<a href="javascript:void(0)" onclick="change_page(\'' + prev + '\')" title="Предыдущая"><i class="icon-angle-left"></i></a>&nbsp;';

				else
					pg = '&nbsp;<a href="javascript:void(0)" onclick="change_page(\'1\')" title="Начало"><i class="icon-angle-double-left"></i></a>&nbsp;&nbsp;<a href="javascript:void(0)" onclick="change_page(\'' + prev + '\')" title="Предыдущая"><i class="icon-angle-left"></i></a>&nbsp;' + pg + '&nbsp;<a href="javascript:void(0)" onclick="change_page(\'' + next + '\')" title="Следующая"><i class="icon-angle-right"></i></a>&nbsp;&nbsp;<a href="javascript:void(0)" onclick="change_page(\'' + pageall + '\')" title="Последняя"><i class="icon-angle-double-right"></i></a>&nbsp;';

			}

			$('#pagediv').removeClass('hidden').html(pg);

		})
			.done(function () {

				var tar = $('#tar').val();
				var order = $('#ord').val();
				var desc = $('#tuda').val();
				var icn = '<i class="icon-angle-down"></i>';

				//console.log(tar);

				cdwidth = $('#contentdiv table thead').actual('width');

				//var header = $('#contentdiv table thead').html();
				//var html = '<table cellpadding="5" width="100%" cellspacing="0" border="0" id="list_header" height="30"><thead>' + header + '</thead></table>';
				//$('.ui-layout-center').find('.tableHeader').width('100%').html(html).removeClass('hidden');

				if (desc === 'desc') icn = '<i class="icon-angle-up"></i>';

				//$('.header_contaner').find('th').remove('i');
				$('.header_contaner').find('#x-' + order).prepend(icn);

				//console.log(order + ' : ' + icn);

				$(".nano").nanoScroller();

				if (isMobile)
					$('.ui-layout-center').find('table').rtResponsiveTables();

				if (!isMobile) {

					var $w = [45, 90, 50];

					if ($(window).width() > 1500) $w = [60, 120, 80];
					else if ($(window).width() > 1700) $w = [100, 200, 160];

					$(".dot-ellipsis").liTextLength({
						length: $w[0],
						afterLength: '...',
						fullText: false
					});
					$(".work-ellipsis").liTextLength({
						length: $w[1],
						afterLength: '...',
						fullText: false
					});
					$(".name-ellipsis").liTextLength({
						length: $w[2],
						afterLength: '...',
						fullText: false
					});

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

	function razdel(hesh) {

		$('.razdel a').removeClass('active');

		if (!hesh) hesh = window.location.hash.replace('#', '');
		if (!hesh) hesh = 'catalog';

		hash = hesh;

		setHeight();

		switch (hesh) {
			case 'catalog':
				$('#tips').html('Каталог-склад');
				$('#ord').val('title');

				$('.menu-catalog').removeClass('hidden');
				$('.menu-zayavka').addClass('hidden');
				$('.menu-order').addClass('hidden');
				$('.menu-sklad').addClass('hidden');

				$('.contaner-catalog').removeClass('hidden');
				$('.contaner-zayavka').addClass('hidden');
				$('.contaner-order').addClass('hidden');
				$('.contaner-offer').addClass('hidden');
				$('.contaner-sklad').removeClass('hidden');
				$('.contaner-sklad-sub').addClass('hidden');
				$('.contaner-reserv').addClass('hidden');

				$('#ord').val('title');
				$('#tuda').val('');

				break;
			case 'zayavka':
			case 'poz':
				$('#tips').html('Заявки');
				$('#ord').val('datum');

				$('.menu-zayavka').removeClass('hidden');
				$('.menu-catalog').addClass('hidden');
				$('.menu-order').addClass('hidden');
				$('.menu-sklad').addClass('hidden');

				$('.contaner-zayavka').removeClass('hidden');
				$('.contaner-catalog').addClass('hidden');
				$('.contaner-order').addClass('hidden');
				$('.contaner-offer').addClass('hidden');
				$('.contaner-sklad').addClass('hidden');
				$('.contaner-sklad-sub').addClass('hidden');
				$('.contaner-reserv').addClass('hidden');

				$('#ord').val('number');
				$('#tuda').val('');

				break;
			/*case 'poz':
				$('#place').html('Позиции заявок');
				$('#ord').val('datum');

				$('.menu-zayavka').removeClass('hidden');
				$('.menu-catalog').addClass('hidden');
				$('.menu-order').addClass('hidden');
				$('.menu-sklad').addClass('hidden');

				$('.contaner-zayavka').addClass('hidden');
				$('.contaner-catalog').addClass('hidden');
				$('.contaner-order').addClass('hidden');
				$('.contaner-offer').addClass('hidden');
				$('.contaner-sklad').addClass('hidden');
				$('.contaner-sklad-sub').addClass('hidden');
				$('.contaner-reserv').addClass('hidden');
				break;*/
			case 'rez':
				$('#tips').html('Позиции резерва');
				$('#ord').val('datum');

				$('.menu-zayavka').addClass('hidden');
				$('.menu-catalog').addClass('hidden');
				$('.menu-order').addClass('hidden');
				$('.menu-sklad').addClass('hidden');

				$('.contaner-zayavka').addClass('hidden');
				$('.contaner-catalog').addClass('hidden');
				$('.contaner-order').addClass('hidden');
				$('.contaner-offer').addClass('hidden');
				$('.contaner-sklad').removeClass('hidden');
				$('.contaner-sklad-sub').addClass('hidden');
				$('.contaner-reserv').removeClass('hidden');
				break;
			case 'order':
				$('#tips').html('Акты');
				$('#ord').val('datum');

				$('.menu-order').removeClass('hidden');
				$('.menu-zayavka').addClass('hidden');
				$('.menu-catalog').addClass('hidden');
				$('.menu-sklad').addClass('hidden');

				$('.contaner-order').removeClass('hidden');
				$('.contaner-zayavka').addClass('hidden');
				$('.contaner-catalog').addClass('hidden');
				$('.contaner-offer').addClass('hidden');
				//$('.contaner-sklad').addClass('hidden');
				$('.contaner-sklad-sub').addClass('hidden');
				$('.contaner-reserv').addClass('hidden');

				$('#ord').val('datum');
				$('#tuda').val('desc');

				break;
			case 'offer':
				$('#tips').html('Предложения');
				$('#ord').val('datum');

				$('.menu-zayavka').addClass('hidden');
				$('.menu-catalog').addClass('hidden');
				$('.menu-order').addClass('hidden');
				$('.menu-sklad').addClass('hidden');

				$('.contaner-zayavka').addClass('hidden');
				$('.contaner-catalog').addClass('hidden');
				$('.contaner-order').addClass('hidden');
				$('.contaner-offer').removeClass('hidden');
				$('.contaner-sklad').addClass('hidden');
				$('.contaner-sklad-sub').addClass('hidden');
				$('.contaner-reserv').addClass('hidden');
				break;
			case 'sklad':
				$('#tips').html('Товар по складам');
				$('#ord').val('date_in');

				$('.menu-sklad').removeClass('hidden');
				$('.menu-zayavka').addClass('hidden');
				$('.menu-catalog').addClass('hidden');
				$('.menu-order').addClass('hidden');

				$('.contaner-sklad').removeClass('hidden');
				$('.contaner-sklad-sub').removeClass('hidden');
				$('.contaner-zayavka').addClass('hidden');
				$('.contaner-catalog').addClass('hidden');
				$('.contaner-order').addClass('hidden');
				$('.contaner-offer').addClass('hidden');
				$('.contaner-reserv').addClass('hidden');
				break;
			case 'move':
				$('#tips').html('История перемещений');
				$('#ord').val('datum');

				$('.menu-catalog').removeClass('hidden');
				$('.menu-zayavka').addClass('hidden');
				$('.menu-order').addClass('hidden');
				$('.menu-sklad').addClass('hidden');

				$('.contaner-catalog').removeClass('hidden');
				$('.contaner-zayavka').addClass('hidden');
				$('.contaner-order').addClass('hidden');
				$('.contaner-offer').addClass('hidden');
				$('.contaner-sklad').removeClass('hidden');
				$('.contaner-sklad-sub').addClass('hidden');
				$('.contaner-reserv').addClass('hidden');
				break;
		}

		$('#tar').val(hesh);
		//$('#page').val('1');
		$('#tuda').val('');

		$('.contaner-sklad').find('input[type="checkbox"]').removeAttr("checked");
		$('.contaner-zayavka').find('input[type="checkbox"]').removeAttr("checked");

		$('.razdel .' + hesh).addClass('active');//.css('border','1px solid red');

		preconfigpage();
		constructSpace();

	}

	$(window).on('resize', function () {

		setHeight();

		var fhLeft = $('#contentdiv').offset().left;
		var fhTop = $('#contentdiv').offset().top;

		$('#contentdiv').find('.fixedHeader2').css({'width': '100%', 'top': fhTop, 'left': fhLeft});
		constructSpace();

	});
	$(window).on('resizeEnd', function () {

		setHeight();

		var fhLeft = $('#contentdiv').offset().left;
		var fhTop = $('#contentdiv').offset().top;

		$('#contentdiv').find('.fixedHeader2').css({'width': '100%', 'top': fhTop, 'left': fhLeft});
		constructSpace();

	});

	function constructSpace() {

		//var hw = $('.ui-layout-center').width();
		//var hh = $('.ui-layout-center').actual('height');// - $('.contaner:first-child').actual('height');
		var ht = $('.listHead').actual('outerHeight');
		var hf = $('.ui-layout-center').actual('height') - $('.contaner[data-id="stat"]').actual('outerHeight') - 30;

		//$('.contaner:last-child').css({"height": hh + 'px',"border":"1px solid red"});
		$('.ui-layout-center').find('.tableHeader').css({"width": "100%", "top": ht + 'px', "left": "0px"});

		$('.contaner[data-id="filter"]').css({"height": hf + "px", "max-height": hf + "px"});

		$('.nano').nanoScroller();

	}

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

	function setHeight() {

		var hf = $('.ui-layout-center').actual('height') - $('.contaner[data-id="stat"]').actual('outerHeight') - 30;
		$('.contaner[data-id="filter"]').css({"height": hf + "px", "max-height": hf + "px"});

		if (hash === 'contract') {

			var hmm = 0;

			$('.mm').each(function () {

				hmm += $(this).outerHeight();

			});

			//console.log('hmm = ' + hmm);

			var hc = hf - hmm - 60;

			$('#doctype').css({"max-height": hc + 'px'});

		}

	}

	function change_page(num) {

		$('#page').val(num);
		configpage();

	}

	function changesort(param) {


		var tt = $('#ord').val();

		$('#ord').val(param);
		$('#page').val('1');

		if (param === tt) {

			if ($('#tuda').val() == '') $('#tuda').val('desc');
			else $('#tuda').val('');

		}

		configpage();
	}

	function massSend() {

		var str = $("#cform").serialize() + '&' + $('#pageform').serialize();
		var url = '/modules/modcatalog/form.modcatalog.php?action=mass&';

		doLoad(url + str).append('<div id="loader" class="loader">Загрузка данных...</div>');

		return false;

	}

	function moveToSklad() {

		var str = $("#cform").serialize() + '&' + $('#pageform').serialize();
		var url = '/modules/modcatalog/form.modcatalog.php?action=movetoskald&';

		doLoad(url + str).append('<div id="loader" class="loader">Загрузка данных...</div>');
		return false;
	}

    function getLocalFiles() {
        fetch('/content/ajax/file.system.php')
            .then(response => {
                if (!response.ok) {
                    throw new Error(response.statusText)
                }
                console.log("dasd");
                return response.json()
            })
            .then(data => {
                //$('#addLocalFileButton').addClass('hidden')
                const html = createHtmlTree(data);

                document.getElementById('localFileFiller').innerHTML = html;

            })
            .then(() => {
                fetchCategoryData()
            })
            .then(() => {
                // $('#dialog').css('height', $(window).height());
                // $('#dialog').css('width', $(window).width());
                $('#dialog').css('height', '80vh');
                $('#dialog').css('width', '70vw');
                // $('#resultdiv').css('height', $(window).height());
                $('#formtabs').css('height', 'calc(80vh - 130px)');
                $('#tabse').css('height', '100%;');
                $('#btn-add-lf').css('display', 'none');
                $('#dialog').center();


                // $('#resultdiv').css('height', '100%;');
                // $('#pole').css('max-height: 100%;');
                // $('#tabse').css('height', '100%;');
                // $('#dialog').center();
            })
            .catch(error => {
                Swal.showValidationMessage(
                    'Ошибка:' + error
                )
            })
    }
    function createHtmlTree(data) {
        var html = '<div style="display:flex;"> <div class="contaner p5" style="width: 250px" id="pricecategory">';

        html += '<div class="mb10">';
        html += '<b class="shad"><i class="icon-menu blue"></i>&nbsp;ПАПКИ</b>';
        html += '<div class="pull-aright inline">';
        html += '</div>';
        html += '</div>';

        html += '<div class="nano has-scrollbar" style="height: 489.19px;">';
        html += '<div class="ifolder nano-content" style="min-height: 200px; right: -10px;" tabindex="0">';

        // Start processing the data to generate the folder structure
        for (var i = 0; i < data.length; i++) {
            html += createHtmlTreeNode(data[i], 0);
        }

        html += '</div>'; // Close #folder
        html += '<div class="nano-pane" style="display: none;"><div class="nano-slider" style="height: 480px; transform: translate(0px, 0px);"></div></div>';
        html += '</div>'; // Close .nano

        // Add a container for loading the content dynamically
        html += '</div>';
        html += '<div class="contaner p5" style="flex-grow: 1;padding-bottom: 35px !important" id="content-container"></div>';
        html += '<div class="pagecontainer">';
        html += '<div class="page-modal pbottom mainbg" id="pagediv-new" style="bottom: 10px !important;">тест</div>'
        html += '</div>';
        html += '</div>';

        return html;
    }
    function createHtmlTreeNode(node, level) {
        var html = '';

        var folderClass = 'icon-folder';
        if (level === 0) {
            folderClass += ' blue';
        } else {
            folderClass += ' gray2';
        }

        var paddingClass = level > 0 ? 'pl10' : '';

        html += '<a href="javascript:void(0)" class="fol_it block text-left link-localFile-category mt5 mb5 local-file-item" data-id="' + node.idcategory + '" data-title="' + node.title + '" id="link-localFile-category-' + node.idcategory + '">';
        html += '<div class="ellipsis-files ' + paddingClass + '">';
        if (level > 0) {
            html += '<div class="strelka w5 mr10"></div>';
        }
        html += '<i class="' + folderClass + '"></i>&nbsp;';
        if (level > 1) {
            html += '<i class="icon-users-1 sup green" title="Общая папка"></i>';
        }
        html += node.title + '</div></a>';

        if (node.children && node.children.length > 0) {
            for (var i = 0; i < node.children.length; i++) {
                html += createHtmlTreeNode(node.children[i], level + 1);
            }
        }

        return html;
    }

    function fetchCategoryData(clickedId) {
        var url;
        if (!clickedId) {
            url = "/modules/upload/list.upload.php?idcat&ord=fid&per_page=10";
        } else {
            url = "/modules/upload/list.upload.php?idcat=" + clickedId + '&ord=fid&per_page=10';
        }
        $.ajax({
            url: url,
            type: 'GET',
            success: function(response) {
                var data = JSON.parse(response);
                var html = '';
                console.log(data);

                if (data.list === null) {
                    html += '<div class="mb10"> Файлов в данной категории еще нет</div>';
                } else {
                    html += '<table>';
                    html += '<thead><tr>';
                    html += '<th>Дата</th>';
                    html += '<th>Имя</th>';
                    html += '<th>Размер</th>';
                    // html += '<th>...</th>';
                    html += '</tr></thead>';
                    html += '<tbody>';

                    for (var i = 0; i < data.list.length; i++) {
                        html += '<tr style="text-align: left">';
                        html += '<td>' + data.list[i].datum + '</td>';
                        html += '<td class="localfile_name" data-id="' + data.list[i].id + '" data-name="' + с + '" id="selectLocalFile">' + data.list[i].title + '</td>';
                        html += '<td>' + data.list[i].size + ' kb</td>';
                        // html += '<td><input  type="checkbox" id="checkboxLocalFile" value= "' + data.list[i].id + '"></td>';
                        // html += '<td><span style="visibility: visible" class="actions"><a href="javascript:void(0)" class="gray green mpr0 cu--preview" data-id="' + data.list[i].id + '" data-type="task" title="Просмотр"><i class="icon-eye green"></i></a></span></td>'
                        html += '</tr>';
                    }

                    html += '</tbody>';
                    html += '</table>';
                }



                // var selectedFile = $('#localFiles').val();
                //
                // if (selectedFile) {
                //     html += '<button type="submit" class="button">Прикрепить</button>';
                // } else {
                //     html += '<div class="button" onclick="getLocalFiles()">Вернуться назад</div>';
                // }

                // html += '<button type="submit" id="chooseLocalFileButton" class="chooseLocalFileButton hidden" ">Прикрепить файл</button>';

                $('#content-container').html(html);
                updateCheckboxState();
            },
            error: function(xhr, status, error) {
                console.error('Error fetching data:', error);
            }
        });
    }

	function delCat(id) {

		url = '/modules/modcatalog/core.modcatalog.php?action=delete&n_id=' + id;
		$.post(url, function (data) {

			configpage();

			$('#message').fadeTo(1, 1).css('display', 'block').html(data);

			setTimeout(function () {
				$('#message').fadeTo(1000, 0);
			}, 20000);

			return true;

		});

	}

	function exportDo() {

		var str = $('#params').serialize();
		var url = '/modules/modcatalog/core.modcatalog.php?action=export&' + str;

		window.open(url + str);

	}

	function removeReserve(id) {

		var url = '/modules/modcatalog/core.modcatalog.php?id=' + id + '&action=removereserv';

		$('#message').css('display', 'block').append('<div id=loader><img src=/assets/images/loader.svg> Загрузка данных. Пожалуйста подождите...</div>');

		$.get(url, function (data) {

			configpage();

			$('#message').fadeTo(1, 1).css('display', 'block').html(data);
			setTimeout(function () {
				$('#message').fadeTo(1000, 0);
			}, 20000);
		});

	}

	function removeZayavka(id) {

		var url = '/modules/modcatalog/core.modcatalog.php?id=' + id + '&action=removezayavka';

		$('#message').css('display', 'block').append('<div id=loader><img src=/assets/images/loader.svg> Загрузка данных. Пожалуйста подождите...</div>');

		$.get(url, function (data) {

			configpage();

			$('#message').fadeTo(1, 1).css('display', 'block').html(data);
			setTimeout(function () {
				$('#message').fadeTo(1000, 0);
			}, 20000);

		});

	}

	function removeOrder(id) {

		var url = '/modules/modcatalog/core.modcatalog.php?id=' + id + '&action=removeorder';

		$('#message').css('display', 'block').append('<div id=loader><img src=/assets/images/loader.svg> Загрузка данных. Пожалуйста подождите...</div>');

		$.get(url, function (data) {

			configpage();

			$('#message').fadeTo(1, 1).css('display', 'block').html(data);
			setTimeout(function () {
				$('#message').fadeTo(1000, 0);
			}, 20000);
		});

	}

	function deleteSkladpoz(id) {

		var url = '/modules/modcatalog/core.modcatalog.php?id=' + id + '&action=deleteskladpoz';

		$('#message').css('display', 'block').append('<div id=loader><img src=/assets/images/loader.svg> Загрузка данных. Пожалуйста подождите...</div>');

		$.get(url, function (data) {

			configpage();

			$('#message').fadeTo(1, 1).css('display', 'block').html(data);
			setTimeout(function () {
				$('#message').fadeTo(1000, 0);
			}, 20000);

		});

	}

	function catalogResize() {

		var h = $('#catbox').height();
		//var hf = $(window).height() * 0.55;

		//console.log(h + ':' + hf);

		if (h <= 250) $('#catbox').css({'max-height': '70vh'});
		else $('#catbox').css({'max-height': '250px'});

		$('#resizer').find('i').toggleClass('icon-resize-full icon-resize-small');
		$('.nano').nanoScroller();

	}

	function exportPoz() {

		var str = $("#cform").serialize() + '&' + $('#pageform').serialize();

		window.open('/modules/modcatalog/core.modcatalog.php?action=exportPoz&' + str);

	}

</script>
<?php
require_once $rootpath."/inc/panel.php";
flush();
?>
</body>
</html>