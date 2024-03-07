<?php
/* ============================ */
/*         SalesMan CRM         */
/* ============================ */
/* (C) 2016 Vladislav Andreev   */
/*       SalesMan Project       */
/*        www.isaler.ru         */
/*        ver. 2017.x           */
/* ============================ */

$title = "Обсуждения";

global $rootpath;
require_once $rootpath."/inc/head.php";
flush();
?>
<DIV class="" id="rmenu">

	<div class="tabs">

		<a href="javascript:void(0)" class="lpToggler" title="Фильтры"><i class="icon-toggler"></i></a>

		<A href="javascript:void(0)" onclick="razdel();" class="razdel pl5 pr5" title="Обновить"><i class="icon-arrows-cw fs-09"></i></A>

		<A href="javascript:void(0)" onclick="editComment('','add');" class="razdel redbg-dark pl5 pr5" title="Добавить"><i class="icon-chat white fs-09"><i class="icon-plus-circled sup fs-07"></i></i></A>

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
<DIV class="ui-layout-west disable--select outlook--close">

	<?php
	require_once $rootpath."/modules/comments/navi.comments.php";
	flush();
	?>

</DIV>
<DIV class="ui-layout-center disable--select outlook--close" style="overflow: hidden">

	<DIV class="mainbg listHead p0 hidden-iphone">

		<div class="pt5 pb10 flex-container">
			<div class="column flex-column wp100 fs-11 pl5 pt10 border-box">
				<span id="tips">Обсуждения</span>
			</div>
		</div>

	</DIV>

	<form name="cform" id="cform">
		<div class="nano" id="kblist">

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

	var $display = 'comments';
	var $toggler = $('.lpToggler');
	var $elcenter = $('.ui-layout-center');
	var $elwest = $('.ui-layout-west');
	var $eleast = $('.ui-layout-east');
	var $content = $('#contentdiv');

	$(function () {

		var tar = $('#tar').val();

		$.Mustache.load('/modules/comments/tpl.comments.mustache');

		constructSpace();

		razdel(tar);

		$(".nano").nanoScroller();

		changeMounth();

	});

	window.onhashchange = function () {
		var hash = window.location.hash.substring(1);
		razdel(hash);
	};

	function razdel(hesh) {

		$('.razdel a').removeClass('active');

		if (!hesh) hesh = window.location.hash.replace('#', '');
		if (!hesh) hesh = 'themes';

		switch (hesh) {
			case 'themes':
				$('#place').html('Темы обсуждения');
				$('.menu-themes').removeClass('hidden');
				$('.contaner-themes').removeClass('hidden');
				$('.menu-comments').addClass('hidden');
				$('.contaner-comments').addClass('hidden');
				break;
			case 'comments':
				$('#place').html('Ответы');
				$('.menu-comments').removeClass('hidden');
				$('.contaner-comments').removeClass('hidden');
				$('.menu-themes').addClass('hidden');
				$('.contaner-themes').addClass('hidden');
				break;

		}

		$('#tar').val(hesh);
		$('#page').val('1');

		$('.razdel .' + hesh).addClass('active');//.css('border','1px solid red');

		configpage();

	}

	function changeCategoryHeight() {

		var hsub = $('#folder').height();
		var hmain = $('#pricecategory .nano').height();
		var hwin = $(document).height();

		if (hsub > 0.5 * hwin && hsub > hmain) $('#pricecategory .nano').height(0.8 * hwin + 'px');
		else $('#pricecategory .nano').height(0.53 * hwin + 'px');

		$("#pricecategory").find('.nano').nanoScroller();

	}

	function constructSpace() {

		var hw = $elcenter.width();
		var ht = ($('.listHead:not(#kbmenu)').is(':visible')) ? $('.listHead:not(#kbmenu)').actual('outerHeight') : 0;
		var hh = $elcenter.actual('height');// - $('.contaner:first-child').actual('height');
		var hm = $elcenter.actual('height') - $('#kbmenu').actual('outerHeight') - 20;

		//$('.contaner:last-child').css({"height": hh + 'px',"border":"1px solid red"});
		//$('.ui-layout-center').find('.tableHeader').css({"width": hw + "px", "top": ht + 'px', "left" : "0px"});

		$('#kblist').css({"height": hh - ht + "px"});
		$('#messagediv').css({"height": hm + "px"});

		var hf = $('.ui-layout-center').actual('height') - $('.contaner[data-id="stat"]').actual('outerHeight') - 30;
		$('.contaner[data-id="filter"]').css({"height": hf + "px", "max-height": hf + "px"});

		var hsub = $('#folder').height();
		var hmain = $('#pricecategory .nano').height();
		var hwin = $(document).height();

		if (hsub > 0.5 * hwin && hsub > hmain) $('#pricecategory .nano').height(0.8 * hwin + 'px');
		else $('#pricecategory .nano').height(0.53 * hwin + 'px');

		$("#pricecategory").find('.nano').nanoScroller();

		$('.nano').nanoScroller();

	}

	$(window).on('resize', function () {

		if (!isMobile) constructSpace();

	});
	$(window).on('resizeend', 200, function () {

		if (!isMobile) constructSpace();

		if (!isMobile) $elcenter.trigger('onPositionChanged');

	});

	$toggler.on('click', function () {

		if (isMobile || $(window).width() < 767) {

			$elwest.toggleClass('open');
			$elcenter.toggleClass('open');

			$eleast.removeClass('open');

		} else {

			$elwest.toggleClass('outlook outlook--close');
			$elcenter.toggleClass('outlook outlook--close');
			$eleast.toggleClass('outlook outlook--close');

		}
		$(this).toggleClass('open');

	});

	$elcenter.onPositionChanged(function () {

		if (this.resizeTO) clearTimeout(this.resizeTO);
		this.resizeTO = setTimeout(function () {

			var hw = $elcenter.width();

			$('.tableHeader').css({"width": hw + "px"});
			//$('.ui-layout-content').css({"width": hw + "px"});
			$('#list_header').css({"width": hw + "px"});

		}, 200);

	});

	function configpage() {

		$content.parent('.nano').nanoScroller({scroll: 'top'});

		var str = $('#pageform').serialize();
		var url = '/modules/comments/list.comments.php';
		var tar = $('#tar').val();
		var word = $('#word').val();

		if (word !== '') $('#themeid').val("0");

		$content.append('<div class="contentloader"><img src="/assets/images/Services.svg" width="50px" height="50px"></div>');

		var cdheight = $content.height();
		var cdwidth = $content.width();

		$('.contentloader').height(cdheight).width(cdwidth);

		/*------------*/

		$.getJSON(url, str, function (viewData) {

			$content.empty().mustache(tar + 'Tpl', viewData);

			var page = parseInt(viewData.page);
			var pageall = parseInt(viewData.pageall);

			var pg = 'Стр. ' + page + ' из ' + pageall;

			if (pageall > 1) {

				var prev = page - 1;
				var next = page + 1;

				if (page === 1) pg = pg + '&nbsp;<a href="javascript:void(0)" onclick="change_page(\'' + next + '\')" title="Следующая"><i class="icon-angle-right"></i></a>&nbsp;&nbsp;<a href="javascript:void(0)" onclick="change_page(\'' + pageall + '\')" title="Последняя"><i class="icon-angle-double-right"></i></a>&nbsp;';
				else if (page === pageall) pg = pg + '&nbsp;<a href="javascript:void(0)" onclick="change_page(\'1\')" title="Начало"><i class="icon-angle-double-left"></i></a>&nbsp;&nbsp;<a href="javascript:void(0)" onclick="change_page(\'' + prev + '\')" title="Предыдущая"><i class="icon-angle-left"></i></a>&nbsp;';
				else pg = '&nbsp;<a href="javascript:void(0)" onclick="change_page(\'1\')" title="Начало"><i class="icon-angle-double-left"></i></a>&nbsp;&nbsp;<a href="javascript:void(0)" onclick="change_page(\'' + prev + '\')" title="Предыдущая"><i class="icon-angle-left"></i></a>&nbsp;' + pg + '&nbsp;<a href="javascript:void(0)" onclick="change_page(\'' + next + '\')" title="Следующая"><i class="icon-angle-right"></i></a>&nbsp;&nbsp;<a href="javascript:void(0)" onclick="change_page(\'' + pageall + '\')" title="Последняя"><i class="icon-angle-double-right"></i></a>&nbsp;';

			}

			$('#pagediv').html(pg);

		})
			.done(function () {

				var id = 0;
				var current = parseInt($('#themeid').val());

				if (current === 0) {

					id = parseInt($('#contentdiv table tr:first-child').data('id'));

					if (id > 0 && !isMobile) editComment(id, 'viewshort', '');
					else $('#messagediv').html('<div id="emptymessage" class="gray miditxt"><i class="icon-chat-1 icon-3x gray"></i><br><b class="red">Упс.</b>&nbsp;&nbsp;<b>Не выбрано обсуждение для просмотра</b></div>');

				} else {

					id = current;

					if (id > 0 && !isMobile) editComment(id, 'viewshort', '');
					else $('#messagediv').html('<div id="emptymessage" class="gray miditxt"><i class="icon-chat-1 icon-3x gray"></i><br><b class="red">Упс.</b>&nbsp;&nbsp;<b>Не выбрано обсуждение для просмотра</b></div>');

				}

				//alert(id);

				if (id === 'undefined' || id === 0) {

					$('#messagediv').html('<div id="emptymessage" class="gray miditxt"><i class="icon-chat-1 icon-3x gray"></i><br><b class="red">Упс.</b>&nbsp;&nbsp;<b>Не выбрано обсуждение для просмотра</b></div>');

				}

				$(".nano").nanoScroller();

				if (isMobile) {

					$content.find('table').rtResponsiveTables();

				}

			});

		/*------------*/

	}

	function change_page(page) {

		$('#page').val(page);
		configpage();

	}

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

</script>
<?php
require_once $rootpath."/inc/panel.php";
flush();
?>
</body>
</html>