<?php
/* ============================ */
/*         SalesMan CRM         */
/* ============================ */
/* (C) 2016 Vladislav Andreev   */
/*       SalesMan Project       */
/*        www.isaler.ru         */
/*        ver. 2017.x           */
/* ============================ */

$rootpath = dirname( __DIR__, 2 );

set_time_limit( 0 );
ini_set( 'memory_limit', '2048M' );
//ini_set('display_errors', 1);

if ( $_REQUEST['action'] == "download" ) {

	include $rootpath."/inc/config.php";
	include $rootpath."/inc/dbconnector.php";
	include $rootpath."/inc/auth.php";
	include $rootpath."/inc/settings.php";
	include $rootpath."/inc/func.php";

	logger( '8', 'Скачан файл резервной копии БД', $iduser1 );

	header( "Content-Type: application/zip" );
	header( "Content-Disposition: attachment; filename=".$_REQUEST['file'] );
	$file = $_REQUEST['file'];
	header( "Content-Length: ".filesize( $rootpath."/files/backup/".$file ) );
	@readfile( $rootpath."/files/backup/".$file );

	exit();

}

$title = 'Панель управления';

require_once $rootpath."/inc/head.php";
flush();

$links = str_replace( [
	"  ",
	"\t",
	"\n",
	"\r"
], "", file_get_contents( $rootpath.'/cash/map.settings.json' ) );

if ( $isadmin != 'on' && $tipuser != 'Администратор' ) {

	logger( '2', '!!! Не санкционированный доступ в раздел Панель управления', $iduser1 );

	print '
	<div class="warning text-left" style="width:600px">
		<span><i class="icon-attention red icon-5x pull-left"></i></span>
		<b class="red uppercase">Внимание:</b><br><br>К сожалению у вас нет прав на работу с этим разделом.<br>Попытка несанкционированного доступа добавлена в журнал.
	</div>
	<script type="text/javascript">
		$(".warning").center();
	</script>
	';
	exit;
}

logger( '2', 'Пользователь вошел в панель администратора', $iduser1 );
?>
<DIV class="" id="rmenu">

	<div class="tabs">

		<a href="javascript:void(0)" class="lpToggler open" title="Фильтры"><i class="icon-toggler"></i></a>

		<A href="#base" class="razdel pl5 pr5" data-id="base" title="Настройка системы"><i class="icon-cog"></i></A>
		<A href="#module" class="razdel pl5 pr5" data-id="module" title="Модули"><i class="icon-puzzle"></i></A>
		<A href="#plugin" class="razdel pl5 pr5" data-id="plugin" title="Интеграция"><i class="icon-cog-alt"></i></A>
		<A href="#service" class="razdel pl5 pr5" data-id="service" title="Обслуживание"><i class="icon-tools"></i></A>

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

<FORM name="selectreport" id="selectreport" action="report_print.php" method="post" target="blank">
	<DIV class="ui-layout-west disable--select compact p0">

		<?php
		require_once $rootpath."/content/leftnav/admin.php";
		flush();
		?>

	</DIV>
	<DIV class="ui-layout-center disable--select compact" style="overflow: hidden">

		<DIV class="mainbg listHead p0 hidden-iphone">

			<div class="flex-container">

				<div class="column flex-column wp100 p10">

					<span id="tips"><b>Администрирование</b></span><span class=""> / <b><span id="razdel">Начало</span></b></span>

				</div>
			</div>

		</DIV>

		<div class="nano" id="clist" style="overflow: auto">

			<div class="nano-content">
				<div class="ui-layout-content block ui-border original p5 nomob" id="contentdiv"></div>
			</div>

		</div>

	</DIV>
</FORM>

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

<div class="pagerefresh refresh--icon" onClick="razdel(hash);" title="<?= $lang[ 'all' ][ 'Refresh' ] ?>">
	<i class="icon-arrows-cw"></i>
</div>

<script>

	var $display = 'controlpanel';
	var hash = '';

	if (isMobile || $(window).width() < 767) {

		$('.lpToggler').toggleClass('open');

	}

	if (isPad)
		$('.lpToggler').trigger('click');

	$(function () {

		$("#accordion").accordion({heightStyle: true, collapsible: true});

		var tip = window.location.hash.replace('#', '');
		razdel(tip);

		constructSpace();
		changeMounth();

		if (tip === '') $('a.menu[href="#welcome"]').addClass('current');

	});

	window.onhashchange = function () {

		hash = window.location.hash.substring(1);
		razdel(hash);

		if ($('.lpToggler').hasClass('open') && isMobile) $('.lpToggler').trigger('click');

	};

	$('#accordion').on('click', 'h3', function () {

		var id = $(this).data('id');

		$('.razdel').not('[data-id="' + id + '"]').removeClass('active');
		$('.razdel[data-id="' + id + '"]').addClass('active');

	});
	$('.razdel').on('click', function () {

		var id = $(this).data('id');

		$('h3[data-id="' + id + '"]').trigger('click');

	});

	$(window).on('resize', function () {

		if (!isMobile) constructSpace();

	});
	$(window).on('resizeend', 200, function () {

		if (!isMobile) {

			constructSpace();
			$('.ui-layout-center').trigger('onPositionChanged');

		}

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
	$('.tabs > .razdel').on('click', function () {

		$('.razdel a').not(this).removeClass('active');
		$(this).addClass('active');

		if (!$('.lpToggler').hasClass('open') && isMobile) $('.lpToggler').trigger('click');

	});
	$("a.menu").on('click', function () {

		hash = window.location.hash.substring(1);

		$('#accordion').find('a.menu[href="#' + hash + '"]').addClass('current');
		$('#accordion').find('a.menu').not('[href="#' + hash + '"]').removeClass('current');

	});

	$('.ui-layout-center').onPositionChanged(function () {

		if (this.resizeTO) clearTimeout(this.resizeTO);
		this.resizeTO = setTimeout(function () {

			var leftOffset = $('.ui-layout-center').position().left;

			if ($('.ui-layout-center').hasClass('open')) {

				$('.fixedHeader2').css({"left": leftOffset + "px"});

			}
			else {

				$('.fixedHeader2').css({"left": leftOffset + "px"});

			}

			var hw = $('.ui-layout-center').width();

			$('.ui-layout-center').find('.tableHeader').css({"width": hw + "px"});
			//$('.ui-layout-content').css({"width": hw + "px"});
			$('#list_header').css({"width": hw + "px"});

		}, 200);

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

	function constructSpace() {

		var hw = $('.ui-layout-center').width();
		var ht = ($('.listHead').is(':visible')) ? $('.listHead').actual('outerHeight') : 0;
		var hh = $('.ui-layout-center').actual('innerHeight');
		var hah = $('#accordion').find('h3:first-child').actual('outerHeight');
		var count = $('#accordion').find('h3').length;
		var mh = hh - count * hah - 5;
		var ch = hh - ht - 0;

		$('.ui-layout-center').find('.tableHeader').css({"width": hw + "px", "top": ht + 'px', "left": "0px"});
		$('#clist').css({"height": ch + "px"});

		$('#accordion').css({"height": hh + "px", "max-height": hh + "px"});
		$('#accordion').find('.ui-accordion-content').css({"height": mh + "px", "max-height": mh + "px"});

	}

	function razdel(tip) {

		var data = JSON.parse('<?=$links?>');

		if (!tip)
			tip = 'welcome';

		hash = data[tip]['razdel'];

		if (!hash)
			hash = window.location.hash.replace('#', '');

		$('.razdel[data-id="' + data[tip]['razdel'] + '"]').addClass('active');

		$('.refresh--panel').find('.admn').remove();

		$('#accordion').accordion({active: data[tip]['index']});
		$('#accordion').find('a[href^="#' + tip + '"]').trigger('click');

		$('#contentdiv').empty().append('<div id="loader"><img src="/assets/images/loading.gif"> Загрузка данных. Пожалуйста подождите...</div>');

		/*
		$.get(data[tip]['url'], function(html, status, error){

			$('#contentdiv').empty().html(html);

			$('.refresh--panel').prepend( $('.pagerefresh') );
			$(".nano").nanoScroller({scrollTop: 0});

		})
		.fail(function (error){

			Swal.fire({
				title: "Ошибка " + error.status + " !",
				text: error.statusText,
				type: 'error',
			});

		});
		*/

		// новая загрузка содержимого разделов
		let options = {};
		options.headers = options.headers || {}
		options.headers['X-Requested-With'] = 'XMLHttpRequest'

		fetch(data[tip]['url'], options)
			.then(response => response.text())
			.then(viewData => {

				$('#contentdiv').empty().html(viewData);

				$('.refresh--panel').prepend( $('.pagerefresh') );
				$(".nano").nanoScroller({scrollTop: 0});

			})
			.then(function () {

				$('.refresh--panel').prepend( $('.pagerefresh') );
				$(".nano").nanoScroller({scrollTop: 0});

			})
			.then(function () {


			})
			.catch(error => {

				Swal.fire({
					title: "Ошибка " + error.status + " !",
					text: error.statusText,
					type: 'error',
				});

			});

		/*$('#contentdiv')
			.empty()
			.load(data[tip]['url'])
			.append('<div id="loader"><img src="/assets/images/loading.gif"> Загрузка данных. Пожалуйста подождите...</div>');*/

		$('#razdel').html(data[tip]['name']);

		if ($('.lpToggler').hasClass('open') && isMobile)
			$('.lpToggler').trigger('click');

		$('#contentdiv').find('select').not('.multiselect').each(function () {

			if ($(this).closest('span').hasClass('select') === false)
				$(this).wrap("<span class='select'></span>");

		});

		/*setTimeout(function () {

			$('.refresh--panel').prepend( $('.pagerefresh') );
			$(".nano").nanoScroller({scrollTop: 0});

		}, 1000);*/

	}

</script>
<?php
require_once $rootpath."/inc/panel.php";
flush();
?>
</body>
</html>