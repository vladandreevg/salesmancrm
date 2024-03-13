<?php
/* ============================ */
/*         SalesMan CRM         */
/* ============================ */
/* (C) 2016 Vladislav Andreev   */
/*       SalesMan Project       */
/*        www.isaler.ru         */
/*        ver. 2017.x           */
/* ============================ */


$title = 'Календарь дел';

$tip = $_REQUEST['tip'];

global $rootpath;
require_once $rootpath."/inc/head.php";
flush();

?>
<DIV class="" id="rmenu">

	<div class="tabs">

		<a href="javascript:void(0)" class="lpToggler open" title="Фильтры"><i class="icon-toggler"></i></a>
		<a href="javascript:void(0)" onclick="configpage();" title="Обновить представление"><i class="icon-arrows-cw"></i></a>

		<a href="#my" class="razdel pl5 pr5" data-id="my" title="Мои дела"><i class="icon-calendar-1"><i class="sup icon-user-1 fs-05"></i></i></a>
		<a href="#other" class="razdel pl5 pr5" data-id="other" title="Назначенные мной"><i class="icon-calendar-1"><i class="sup icon-flag fs-05"></i></i></a>
		<?php if ($tipuser >= "Руководитель") {?>
		<a href="#all" class="razdel pl5 pr5" data-id="all" title="Дела сотрудников"><i class="icon-calendar-1"><i class="sup icon-users-1 fs-05"></i></i></a>
		<?php }?>

		<?php require_once $rootpath."/content/leftnav/leftpop.php"; flush();?>

	</div>

	<?php require_once $rootpath."/content/leftnav/counters.php"; flush();?>

</DIV>

<DIV class="ui-layout-north mainbg">

	<?php require_once $rootpath."/inc/menu.php"; flush();?>

</DIV>
<DIV class="ui-layout-west disable--select compact">

	<?php require_once $rootpath."/content/leftnav/calendar.php"; flush();?>

</DIV>
<DIV class="ui-layout-center disable--select compact" style="overflow: hidden">

	<DIV class="mainbg listHead p0 hidden-iphone">

		<div class="flex-container p10">

			<div class="column flex-column wp50 fs-11 border-box">
				<b class="shado hidden-ipad">Дела</b><span class="hidden-ipad"> / </span><span id="tips">Мои дела</span>
			</div>
			<div class="column flex-column wp50 text-right">

				<a href="javascript:void(0)" onclick="getTaskCSV()" title="Скачать в формате CSV" class="hidden-ipad"><i class="icon-download blue"></i>Экспорт</a>&nbsp;&nbsp;
				<a href="javascript:void(0)" title="Обновить представление" onclick="page_refresh();"><i class="icon-arrows-cw blue"></i><span class="hidden-ipad">Обновить</span></a>&nbsp;

			</div>

		</div>

	</DIV>

	<form name="cform" id="cform">
	<div class="nano relativ" id="clientlist">

		<div class="nano-content">
			<div class="ui-layout-content modules" id="contentdiv"></div>
		</div>

	</div>
	</form>

	<div class="multi--buttons box--child hidden">

		<a href="javascript:void(0)" onclick="multiTaskMove()" class="button bluebtn box-shadow amultidel" title="Перенести"><i class="icon-shuffle"></i>Перенести <span class="task--count"></span></a>
		<a href="javascript:void(0)" onclick="multiTaskDel()" class="button redbtn box-shadow amultidel" title="Удалить"><i class="icon-cancel-circled-1"></i>Удалить <span class="task--count"></span></a>
		<a href="javascript:void(0)" onclick="multiTaskClearCheck()" class="button greenbtn box-shadow amultidel" title="Снять выделение"><i class="icon-th"></i>Снять выделение <span class="task--count"></span></a>

	</div>

</DIV>
<DIV class="ui-layout-east"></DIV>
<DIV class="ui-layout-south"></DIV>

<script>

var $display = 'calendar';
var $hash = 'my';
var $checkedTask = [];

if(isMobile || $(window).width() < 767){

	$('.lpToggler').toggleClass('open');

}

$.Mustache.load('/content/tpl/tpl.tasks.mustache');

$( function() {

	$hash = window.location.hash.substring(1);
	if($hash === '') $hash = 'my';

	var txt = $('.razdel[data-id="'+$hash+'"]').attr('title');
	$('#tips').html(txt);

	$(window).trigger('onhashchange');

	$('#tar').val($hash);

	$('#rmenu').find('a').removeClass('active');
	$('#rmenu').find('a[data-id="'+$hash+'"]').addClass('active');

	changeMounth();

	/**
	 * При запуске страница загружается в функции changeMounth
	 * для того, чтобы обеспечить реакцию при изменении месяца
	 */
	//configpage();

	constructSpace();

	$(".nano").nanoScroller();

	/*tooltips*/
	$('#pptt .tooltips').append("<span></span>");
	$('#pptt .tooltips:not([tooltip-position])').attr('tooltip-position','bottom');
	$("#pptt .tooltips").on('mouseenter', function(){
		$(this).find('span').empty().append($(this).attr('tooltip'));
	});
	/*tooltips*/


});

window.onhashchange = function() {

	$hash = window.location.hash.substring(1);
	var txt = $('.razdel[data-id="'+$hash+'"]').attr('title');

	$('#rmenu').find('a').removeClass('active');
	$('#rmenu').find('a[data-id="'+$hash+'"]').addClass('active');

	if($hash === 'my'){

		$('#users').addClass('hidden');
		$('#tome').removeClass('hidden');

	}
	else {
		$('#users').removeClass('hidden');
		$('#tome').addClass('hidden');
	}

	$('#tar').val($hash);
	$('#tips').html(txt);

	configpage();

};

function constructSpace(){

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

	constructSpace();

});

$('.ui-layout-center').onPositionChanged(function(){

	if(this.resizeTO) clearTimeout(this.resizeTO);
	this.resizeTO = setTimeout(function() {


	}, 200);

	$('.ui-layout-content').css({"width": "100%"});
	$('#list_header').css({"width": "100%"});

});

function configpage(){

	$('#contentdiv').parent(".nano").nanoScroller({ scroll: 'top' });

	var str = $('#pageform').serialize() + '&y=' + $('#sy').val() + '&m='+ $('#sm').val();
	var url = '/content/lists/list.tasks.php';

	$('#contentdiv').append('<div class="contentloader"><img src="/assets/images/Services.svg" width="50px" height="50px"></div>');

	var cdheight = $('#contentdiv').height();
	var cdwidth = $('#contentdiv').width();

	$('.contentloader').height(cdheight).width(cdwidth);

	if( $('.lpToggler').hasClass('open') && isMobile ) $('.lpToggler').trigger('click');

	/*------------*/

	$.getJSON(url + '?' + str, function(viewData) {

		viewData.language = $language;

		$('#contentdiv').empty().mustache('taskTpl', viewData);

	})
		.fail(function(status) {

			console.log(status)

			Swal.fire({
				title: "Ошибка 500: Ошибка сервера!",
				type: "error"
			});

		})
		.done(function() {

			$(".nano").nanoScroller();

			$('tr[data-type="task"] td:first-child')
				.on('mousedown', function(){

					$(this).closest('tr').toggleClass('yellowbg-sub');

					$('tr[data-type="task"] td:first-child').on('mouseenter',function(){

						var $elm = $('input[type=checkbox]', this);

						$(this).closest('tr').toggleClass('yellowbg-sub');
						//$elm.click();

						//var checkBoxes = $("input[type=checkbox]", this);
						//checkBoxes.prop("checked", !checkBoxes.prop("checked"));

						//console.log( $elm.prop('checked') );

						if( $elm.prop('checked') )
							$elm.prop('checked', false);
						else
							$elm.prop('checked', true);

						//console.log( $elm.prop('checked') );

					});

				})
				.on('mouseup', function(){

					$('tr[data-type="task"] td:first-child').off('mouseenter');

					if($('tr[data-type="task"].yellowbg-sub').length > 0)
						$('.multi--buttons').removeClass('hidden');
					else
						$('.multi--buttons').addClass('hidden');

					$('.task--count').html( '( <b>' + $('tr[data-type="task"].yellowbg-sub').length + '</b> )' );

				});

			if(isMobile)
				$('#contentdiv').find('table').rtResponsiveTables();


		});
}

function page_refresh(){

	configpage();

}

function getTaskCSV(){

	var m = parseInt($('#calendar #sm').val());
	var y = parseInt($('#calendar #sy').val());

	window.location.assign('/content/core/core.tasks.php?action=export&tar='+$hash+'&y='+y+'&m='+m);
}

function multiTaskDel(){

	Swal.fire({
			title: 'Вы уверены?',
			text: "Напоминания будут удалены безвозвратно!",
			type: 'warning',
			showCancelButton: true,
			confirmButtonColor: '#3085d6',
			cancelButtonColor: '#d33',
			confirmButtonText: 'Да, выполнить',
			cancelButtonText: 'Отменить',
			confirmButtonClass: 'greenbtn',
			cancelButtonClass: 'redbtn'
		},
		function () {

			multiTaskDelDo();

		}
	)
		.then((result) => {

			if (result.value) {
				multiTaskDelDo();
			}

	});

	function multiTaskDelDo(){

		var strs = $("#contentdiv tr.yellowbg-sub input:checkbox").map(function(){
			return $(this).val();
		}).get();

		console.log( strs.join(",") );

		$.get('/content/core/core.tasks.php?action=mass.delete&count='+ strs.length +'&ids=' + strs.join(","), function (data) {

			$('#message').fadeTo(1, 1).css('display', 'block').html(data);
			setTimeout(function () {
				$('#message').fadeTo(1000, 0);
			}, 20000);

			if ($('#tar').is('input') && typeof configpage === 'function')
				configpage();

			if ($('#isCard').val() === 'yes')
				cardload();
			else if (typeof configpage === 'function')
				configpage();

			if ($display === 'desktop')
				changeMounth();

			if ($('#weekCal').is('div'))
				getWeekCalendar();

		});

		multiTaskClearCheck();

	}

}

function multiTaskMove(){

	var strs = $("#contentdiv tr.yellowbg-sub input:checkbox").map(function(){
		return $(this).val();
	}).get();

	console.log( strs.join(",") );

	doLoad('/content/forms/form.task.php?action=mass&count='+ strs.length +'&ids=' + strs.join(","));

	multiTaskClearCheck();

}

function multiTaskClearCheck() {

	$('tr[data-type="task"]').removeClass('yellowbg-sub');
	$("input[type=checkbox]:checked").prop('checked',false);
	$('.multi--buttons').addClass('hidden');

}

</script>
<?php require_once $rootpath."/inc/panel.php"; flush();?>
</body>
</html>