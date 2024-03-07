<?php
/* ============================ */
/*         SalesMan CRM         */
/* ============================ */
/* (C) 2019 Vladislav Andreev   */
/*       SalesMan Project       */
/*        www.isaler.ru         */
/*        ver. 2019.x           */
/* ============================ */

$title = "Почтовый клиент";

global $rootpath;
require_once $rootpath."/inc/head.php";
flush();

?>
<DIV class="" id="rmenu">

	<div class="tabs ymailer" data-step="11" data-intro="<h1>Дополнительная панель</h1>" data-position="right">

		<a href="javascript:void(0)" class="lpToggler" title="Фильтры" data-step="10" data-intro="<h1>Переключатель доп.панели</h1>" data-position="right"><i class="icon-toggler"></i></a>

		<A href="javascript:void(0)" onclick="configmpage()" class="razdel pl5 pr5" title="Обновить список писем"><i class=" icon-arrows-cw"></i></A>
		<A href="javascript:void(0)" onclick="$mailer.get('yes')" class="razdel pl5 pr5" title="Проверить вручную"><i class="icon-download"></i></A>

		<A href="#conversation" class="razdel pl5 pr5 relativ" data-id="conversation" title="Вся почта">
			<i class="icon-mail-alt"></i>
			<i class="icon-chat-1 orange sub fs-07"></i>
			<span class="bullet fs-05"></span>
		</A>
		<A href="#inbox" class="razdel pl5 pr5 relativ" data-id="inbox" title="Входящие">
			<i class="icon-mail-alt"></i>
			<i class="icon-forward-1 green sub fs-07"></i>
			<span class="bullet fs-05"></span>
		</A>
		<A href="#sended" class="razdel pl5 pr5 relativ hidden-min-h590 visible-min-h700" data-id="sended" title="Отправленные">
			<i class="icon-mail"></i>
			<i class="icon-reply blue sub fs-07"></i>
			<span class="bullet fs-05"></span>
		</A>
		<A href="#draft" class="razdel pl5 pr5 hidden-min-h590 visible-min-h700" data-id="draft" title="Черновики">
			<i class="icon-doc-text"></i>
			<span class="bullet fs-05"></span>
		</A>
		<A href="#trash" class="razdel pl5 pr5 hidden-min-h590 visible-min-h700" data-id="trash" title="Корзина">
			<i class="icon-trash"></i>
			<span class="bullet fs-05"></span>
		</A>

		<div title="<?= $lang['face']['More'] ?>" class="leftpop">

			<i class="icon-dot-3"></i>

			<ul class="menu">

				<li class="hidden-min-h700">
					<A href="#sended" class="razdel nowrap" data-id="sended" title="Отправленные"><i class="icon-mail"></i>Отправленные<span class="bullet fs-05"></span></A>
				</li>
				<li class="hidden-min-h700">
					<A href="#draft" class="razdel nowrap" data-id="draft" title="Черновики"><i class="icon-doc-text"></i>Черновики<span class="bullet fs-05"></span></A>
				</li>
				<li class="hidden-min-h700">
					<A href="#trash" class="razdel nowrap" data-id="trash" title="Корзина"><i class="icon-trash"></i>Корзина</A>
				</li>
				<li class="hidden-min-h700">
					<hr class="p0 m0">
				</li>
				<li>
					<A href="javascript:void(0)" onclick="$mailer.signature()" class="razdel nowrap" title="Автоподписи"><i class="icon-vcard"></i>Автоподписи</A>
				</li>
				<li>
					<A href="javascript:void(0)" onclick="$mailer.account()" class="razdel nowrap" title="Настройка"><i class="icon-cog-alt"></i>Настройка</A>
				</li>
				<li>
					<A href="javascript:void(0)" onclick="$mailer.tpl()" class="razdel nowrap" title="Шаблоны"><i class="icon-doc-text"></i>Шаблоны</A>
				</li>
				<li>
					<A href="javascript:void(0)" onclick="$mailer.blacklist()" class="razdel nowrap" title="Черный список"><i class="icon-thumbs-down-alt"></i>&nbsp;Черный список</A>
				</li>

			</ul>

		</div>

		<A href="javascript:void(0)" onclick="$mailer.compose()" class="razdel bluebg-dark pl5 pr5" title="Написать"><i class="icon-mail-alt white"><i class="icon-plus-circled sup fs-07"></i></i></A>

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
<DIV class="ui-layout-west disable--select outlook--close" data-step="11" data-intro="<h1>Дополнительная панель</h1>" data-position="right">

	<?php
	require_once $rootpath."/modules/mailer/nav.mailer.php";
	flush();
	?>

</DIV>
<DIV class="ui-layout-center disable--select outlook--close relativ" style="overflow: hidden" data-step="12" data-intro="<h1>Список сообщений</h1>" data-position="right">

	<DIV class="mainbg listHead originalcolor pt10 pb10">

		<span class=""><input type="checkbox" id="checkall" title="Выделить все видимые"></span>
		<span id="place" class="Bold">Список сообщений</span>

		<span id="trashMessButton" class="hidden pull-aright">
			<a href="javascript:void(0)" onclick="$mailer.action('0','emptytrash')" title="Удалить выбранные сообщения из CRM" class="blue"><i class="icon-block"></i>Очистить</a>
		</span>
		<span id="trashAllMessButton" class="hidden pull-aright">
			<a href="javascript:void(0)" onclick="$mailer.multitrash()" title="Удалить выбранные сообщения в Корзину" class="blue"><i class="icon-trash"></i></a>
		</span>
		<span id="deltMessButton" class="hidden pull-aright red">
			<a href="javascript:void(0)" onclick="$mailer.multidel()" title="Удалить выбранные сообщения из CRM" class="red"><i class="icon-minus-circled"></i></a>&nbsp;&nbsp;
		</span>
		<span id="readallMessButton" class="hidden pull-aright">
			<a href="javascript:void(0)" onclick="$mailer.multiread()" title="Отметить все прочитанными" class="green"><i class="icon-shareable green"></i></a>&nbsp;&nbsp;
		</span>
		<span id="conversationButton" class="hidden pull-aright">
			<a href="javascript:void(0)" onclick="emptyconversation()" title="Показать все" class="green"><i class="icon-chat-1 green"></i></a>&nbsp;&nbsp;
		</span>

	</DIV>

	<form name="params" id="params">
		<div class="nano ymailer" id="kblist">

			<div class="nano-content" id="kblist">
				<div class="ui-layout-content" id="contentdiv">
					<div id="listmes" class="relativ"></div>
				</div>
			</div>

		</div>
	</form>

	<!--для построничной реализации-->
	<div class="pagecontainer short">
		<div class="page pbottom mainbg" id="pagediv"></div>
	</div>

</DIV>
<DIV class="ui-layout-east relativ outlook--close" style="overflow-y: auto !important;" data-step="13" data-intro="<h1>Загруженное сообщение</h1>" data-position="left">

	<div class="nano ui-layout-content ui-border bgwhite">

		<DIV class="nano-content pad101 relativ" id="messagediv" style="overflow-y: auto !important;"></DIV>

	</div>

</DIV>
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

	var $display = 'mailer';
	var $messageid = 0;

	$.Mustache.load('modules/mailer/tpl/interface.mustache');

	$(function () {

		var hash = window.location.hash.replace('#', '');

		//скрыть иконку почтовика в левой панелиБ если находишся в самом почтовике
		$('li.pop[data-id="ymail"]').addClass('hidden');

		$('#rmenu').find('a').removeClass('active');
		$('#rmenu').find('a[data-id="' + hash + '"]').addClass('active');

		$('.inputdate').datepicker({
			dateFormat: 'yy-mm-dd',
			firstDay: 1,
			dayNamesMin: ['Вс', 'Пн', 'Вт', 'Ср', 'Чт', 'Пт', 'Сб'],
			monthNamesShort: ['Январь', 'Февраль', 'Март', 'Апрель', 'Май', 'Июнь', 'Июль', 'Август', 'Сентябрь', 'Октябрь', 'Ноябрь', 'Декабрь'],
			changeMonth: true,
			changeYear: true
		});

		constructSpace();

		razdel(hash);
		folderCheck();

		$(".nano").nanoScroller();

		changeMounth();

	});

	$(document).on('click', '.onemessage', function () {

		$messageid = parseInt($(this).closest('.messagelist').data('id'));

		loadMes($messageid);

	});
	$(document).on('click', '#checkall', function () {

		if ($(this).prop('checked'))
			$('#contentdiv').find('.mcheck:enabled').prop('checked', true);
		else
			$('#contentdiv').find('.mcheck:enabled').prop('checked', false);

		chbCheck();

	});
	$(document).on('click', '.clear', function () {

		$(this).parents('.cleared').find('input').val('');
		configmpage();

	});
	$(document).on('click', '.quoteTitle', function () {

		$(this).parent('.quote').children('blockquote').toggleClass('hidden');
		$(this).find('i').toggleClass('icon-angle-down icon-angle-up');

		constructSpace();

	});

	function constructSpace() {

		var hw = $('.ui-layout-center').width();
		var ht = $('.listHead').actual('outerHeight');
		var hh = $('.ui-layout-center').actual('height');// - $('.contaner:first-child').actual('height');
		var hm = $('.ui-layout-center').actual('height') - $('#kbmenu').actual('outerHeight') - 15;

		$('#kblist').css({"height": hh + "px"});
		$('#messagediv').css({"height": hm + "px", "max-height": hm + "px"});

		var hf = $('.ui-layout-center').actual('height') - $('.contaner[data-id="stat"]').actual('outerHeight') - 30;
		$('.contaner[data-id="filter"]').css({"height": hf + "px", "max-height": hf + "px"});

		setTimeout(function () {

			$('.nano').nanoScroller();

			var right = Math.abs(parseInt($('#messagediv').css('right'))) - 5;
			var mwidth = $('#messagediv').width() - right;
			var mheight = $('#messagediv').actual('height');
			var vheight = $('.viewblock').actual('height');

			if (vheight < mheight) $('.viewblock').css({'margin-right': right + 'px'});
			else $('.viewblock').css({'margin-right': 'inherit'});

		}, 100);

	}

	$(window).on('resize', function () {

		constructSpace();

	});
	$(window).on('resizeend', 200, function () {

		constructSpace();

		$('.ui-layout-center').trigger('onPositionChanged');

	});

	$('.lpToggler').on('click', function () {

		$('.ui-layout-west').toggleClass('outlook outlook--close');
		$('.ui-layout-center').toggleClass('outlook outlook--close');
		$('.ui-layout-east').toggleClass('outlook outlook--close');
		$(this).toggleClass('open');

	});
	$('.ui-layout-center').onPositionChanged(function () {

		if (this.resizeTO) clearTimeout(this.resizeTO);
		this.resizeTO = setTimeout(function () {

			var hw = $('.ui-layout-center').width();

			$elcenter.find('.tableHeader').css({"width": hw + "px"});
			$('#list_header').css({"width": hw + "px"});

		}, 200);

	});

	window.onhashchange = function () {

		var hash = window.location.hash.substring(1);

		razdel(hash);

		$('#rmenu').find('a').removeClass('active');
		$('#rmenu').find('a[data-id="' + hash + '"]').addClass('active');

	};

	function folderCheck() {

		var url = '/modules/mailer/core.mailer.php?action=folder.count';
		$.post(url, function (data) {

			if (data.inbox !== '') {

				$('.conversation .boxcount').html(data.totalUnread);
				$('.conversation .uboxcount').html('/&nbsp;'+data.total);

				$('.inbox .boxcount').html(data.inboxUnread);
				$('.inbox .uboxcount').html('/&nbsp;'+data.inbox);
				$('.sended .boxcount').html(data.sended);
				$('.draft .boxcount').html(data.draft);
				$('.blacklist .boxcount').html(data.blacklist);

				$('.razdel[data-id="inbox"]').find('span').html(data.inboxUnread);
				$('.razdel[data-id="sended"]').find('span').html(data.sended);
				$('.razdel[data-id="draft"]').find('span').html(data.draft);

			}

		}, 'json');

	}

	function razdel(hesh) {

		$('.mailfolder div').removeClass('active');

		if (!hesh) hesh = 'conversation';

		switch (hesh) {
			case 'conversation':
				$('#place').html('<i class="icon-mail relati"><i class=" icon-chat-1 orange my3"></i></i>&nbsp;&nbsp;&nbsp;&nbsp;Вся почта');
				break;
			case 'inbox':
				$('#place').html('<i class="icon-mail relati"><i class=" icon-forward-1 green my3"></i></i>&nbsp;&nbsp;&nbsp;&nbsp;Входящие');
				break;
			case 'outbox':
				$('#place').html('<i class="icon-upload-1 broun"></i>&nbsp;Исходящие');
				break;
			case 'sended':
				$('#place').html('<i class="icon-mail relati"><i class=" icon-reply green my3"></i></i>&nbsp;&nbsp;&nbsp;&nbsp;Отправленные');
				break;
			case 'draft':
				$('#place').html('<i class="icon-doc-text gray"></i>&nbsp;Черновики');
				break;
			case 'trash':
				$('#place').html('<i class="icon-trash gray"></i>&nbsp;Корзина');
				$('#trashMessButton').removeClass('hidden');
				$('#trashAllMessButton').addClass('hidden');
				break;
			case 'template':
				$('#place').html('<i class="icon-file-code blue"></i>&nbsp;Шаблоны');
				break;
		}

		$('#trashMessButton').addClass('hidden');

		$('.' + hesh).addClass('active');

		$('#page').val('1');
		$('#folder').val(hesh);

		if (hesh === 'inbox')
			$('#readallMessButton').removeClass('hidden');
		else
			$('#readallMessButton').addClass('hidden');

		configmpage();

	}

	function loadMes(id) {

		if (id) {

			$messageid = parseInt(id);
			$('#mid').val($messageid);

		}

		var url = '/modules/mailer/editor.php?id=' + $messageid + '&action=view';

		$('#listmes').find('.messagelist').removeClass('current');

		$(".messagelist[data-id='" + $messageid + "']").addClass('current').removeClass('unseen');

		$('.mcheck').prop('checked', false);

		$(".ui-layout-content .nano").nanoScroller({scroll: 'top'});

		$.getJSON(url, function (data) {

			$('#messagediv').empty().mustache('view', data);

			$('#msgbody').find('img').each(function () {

				var src = $(this).attr('src');
				var wi = $(this).width();
				var hi = $(this).attr('height');

				if (wi > 300 && hi > 200)
					$(this).after('<div><a href="' + src + '" target="blank" class="blue">Просмотр изображения</a></div>');

			});

		})
			.done(function () {

				chbCheck();
				$mailer.count();
				folderCheck();
				formatQuote();
				imagePreview();

				$('.mbody a').attr("target", "_blank");

				constructSpace();

			});

	}

	function chbCheck() {

		var col = $('#listmes input:checkbox:checked').length;
		var hesh = window.location.hash.replace('#', '');

		if (col > 0) {

			$('#deltMessButton').removeClass('hidden');
			if (hesh !== 'trash')
				$('#trashAllMessButton').removeClass('hidden');

		}
		else {

			$('#deltMessButton').addClass('hidden');
			if (hesh !== 'trash')
				$('#trashAllMessButton').addClass('hidden');

		}

	}

	function configmpage() {

		var str = $('#pageform').serialize();
		var url = '/modules/mailer/list.mailer.php?';
		var next = parseInt($('.messagelist[data-id="' + $messageid + '"]').next().data('id'));
		var pg;

		$('#checkall').prop('checked', false);

		$('#listmes').empty().append('<div id="loader" class="pad10"><img src="/assets/images/loading.svg"></div>');

		$.getJSON(url, str, function (data) {

			$('#listmes').empty().mustache('list', data);

			var page = data.page;
			var pageall = data.pageall;

			pg = 'Стр. ' + page + ' из ' + pageall;

			if (pageall > 1) {

				var prev = page - 1;
				var next = page + 1;

				if (page === 1) pg = pg + '&nbsp;<a href="javascript:void(0)" onclick="changePage(\'' + next + '\')" title="Следующая"><i class="icon-angle-right"></i></a>&nbsp;&nbsp;<a href="javascript:void(0)" onclick="changePage(\'' + pageall + '\')" title="Последняя"><i class="icon-angle-double-right"></i></a>&nbsp;';
				else if (page === pageall) pg = pg + '&nbsp;<a href="javascript:void(0)" onclick="changePage(\'1\')" title="Начало"><i class="icon-angle-double-left"></i></a>&nbsp;&nbsp;<a href="javascript:void(0)" onclick="changePage(\'' + prev + '\')" title="Предыдущая"><i class="icon-angle-left"></i></a>&nbsp;';
				else pg = '&nbsp;<a href="javascript:void(0)" onclick="changePage(\'1\')" title="Начало"><i class="icon-angle-double-left"></i></a>&nbsp;&nbsp;<a href="javascript:void(0)" onclick="changePage(\'' + prev + '\')" title="Предыдущая"><i class="icon-angle-left"></i></a>&nbsp;' + pg + '&nbsp;<a href="javascript:void(0)" onclick="changePage(\'' + next + '\')" title="Следующая"><i class="icon-angle-right"></i></a>&nbsp;&nbsp;<a href="javascript:void(0)" onclick="changePage(\'' + pageall + '\')" title="Последняя"><i class="icon-angle-double-right"></i></a>&nbsp;';

			}

			$('.pagecontainer').css({"position": "unset"});

			$('#pagediv').html(pg);

		})
			.done(function () {

				//при стартовой загрузке выбираем первое сообщение
				if ($messageid === 0 || $('.messagelist[data-id="' + $messageid + '"]').is('div') === false) {

					$messageid = parseInt($('.messagelist:first').data('id'));

					loadMes($messageid);

				}
				//при удалении сообщения выбираем следующее сообщение
				else {

					if ($messageid > 0) loadMes($messageid);
					else if (next > 0) loadMes(next);
					else loadMes($('.messagelist:first').data('id'));

				}

				folderCheck();

			});

	}

	function changePage(page) {

		$('#page').val(page);
		configmpage();

	}

	function clearFilter() {

		$('#word').val('');
		$('#date1').val('');
		$('#date2').val('');
		$('#period option[data-period="all"]').prop('selected', true);

		configmpage();

	}

	function imagePreview() {

		var list = '';

		$('#fileList').find('a[data-tip="pic"]').each(function () {

			var file = $(this).data('file');
			var name = $(this).data('fname');
			var url = $(this).attr('href');

			list += '<div class="picpreview hand relativ" style="background: url(./files/' + file + ') no-repeat center center;" onclick="window.open(\'./files/' + file + '\')" title="Открыть в новом окне"><span class="fs-09 bottom">' + name + '</span></div>';

		});

		$('.ymImagePreview').empty().html(list);

	}

	function formatQuote() {

		$('#msgbody').find('blockquote').each(function () {

			$(this).addClass('hidden');
			$(this).wrap('<div class="quote"></div>');

		});

		$('.quote').prepend('<div class="quoteTitle">Показать цитату <i class="icon-angle-down"></i></div>');

		setTimeout(function () {

			$('.nano').nanoScroller();

		}, 100);

	}

	async function viewconversation(email){

		var $melm = $('#pageform');

		$('#word').val(email);
		$('#conversationButton').removeClass('hidden');

		window.location.hash = 'conversation';

		await configmpage();

	}

	async function emptyconversation(){

		var $melm = $('#pageform');

		$melm.find('#word').val('');
		$('#conversationButton').addClass('hidden');

		$mailbox = 'conversation';

		await configmpage();

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
					case "1":
					case "2":
					case "3":
					case "4":
					case "5":
					case "6":
					case "7":
					case "8":
						$(targetElement).show();
						break;
					case "9":
						$(targetElement).show();
						break;
					case "10":
						$(targetElement).show();
						break;
					case "11":
						//$(targetElement).show();
						$('.lpToggler').trigger('click');
						break;
					case "12":
						//$(targetElement).show();
						//$('.lpToggler').trigger('click');
						break;
					case "13":
						$(targetElement).show();
						break;
					case "14":
						$(targetElement).show();
						break;
					case "15":
						$('.tagsmenuToggler').trigger('click');
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