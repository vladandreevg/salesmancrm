<?php
/* ============================ */
/*         SalesMan CRM         */
/* ============================ */
/* (C) 2016 Vladislav Andreev   */
/*       SalesMan Project       */
/*        www.isaler.ru         */
/*        ver. 2017.x           */
/* ============================ */

$title = "Файлы";

$year = date('Y');
$y1 = $year - 1;
$y2 = $year + 1;

global $rootpath;
require_once $rootpath."/inc/head.php";
flush();

if ($acs_files != 'on') {
	print '
	<div class="warning" style="width:600px">
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
		<a href="javascript:void(0)" onclick="configpage();" title="Обновить представление"><i class="icon-arrows-cw"></i></a>

		<?php require_once $rootpath."/content/leftnav/leftpop.php"; flush();?>

	</div>

	<?php require_once $rootpath."/content/leftnav/counters.php"; flush();?>

</DIV>

<DIV class="ui-layout-north mainbg">

	<?php require_once $rootpath."/inc/menu.php"; flush();?>

</DIV>
<DIV class="ui-layout-west disable--select compact">

	<?php require_once $rootpath."/modules/upload/navi.upload.php";?>

</DIV>
<DIV class="ui-layout-center disable--select compact" style="overflow: hidden">

	<DIV class="mainbg listHead p0 hidden-iphone">

		<div class="flex-container p10">

			<div class="column flex-column wp50 fs-11 pl5 border-box">

				<b>СЕРВИС&nbsp;/&nbsp;Файлы&nbsp;/&nbsp;</b><span id="tips">[все]</span>

			</div>
			<div class="column flex-column wp50 text-right">

				<div class="menu_container" data-step="9" data-intro="<h1>Меню действий.</h1>Выполнение доступных действий" data-position="left">

					<a href="javascript:void(0)" onclick="submenu('sub')" class="tagsmenuToggler"><b>Действия</b>&nbsp;<i class="icon-angle-down" id="mapi"></i></a>

					<div class="tagsmenu toright hidden">

						<div class="items noBold fs-09">

							<div onclick="editUpload('','add','');" title="Загрузить" class="item ha hand"><span><i class="icon-plus-circled green"></i></span>&nbsp;&nbsp;Загрузить</div>
							<div onclick="massSend();" title="Удалить" class="item ha hand"><span><i class="icon-cancel-circled red"></i></span>&nbsp;&nbsp;Удалить</div>

						</div>

					</div>

				</div>
				<a href="javascript:void(0)" title="Обновить представление" onclick="configpage();"><i class="icon-arrows-cw blue"></i>Обновить</a>&nbsp;&nbsp;

			</div>

		</div>

	</DIV>

	<form name="cform" id="cform">
	<div class="nano1 relativ noscroll" id="clientlist" style="height: 100%;">

		<div class="nano-content">
			<div class="ui-layout-content" id="contentdiv"></div>
		</div>

	</div>
	</form>

	<div class="pagecontainer">
		<div class="page pbottom mainbg" id="pagediv"></div>
	</div>

	<div class="multi--buttons box--child hidden">

		<a href="javascript:void(0)" onclick="multiRowMove()" class="button bluebtn box-shadow amultidel" title="Перенести"><i class="icon-shuffle"></i>Перенести <span class="task--count"></span></a>
		<a href="javascript:void(0)" onclick="multiRowDel()" class="button redbtn box-shadow amultidel" title="Удалить"><i class="icon-cancel-circled-1"></i>Удалить <span class="task--count"></span></a>
		<a href="javascript:void(0)" onclick="multiRowClearCheck()" class="button greenbtn box-shadow amultidel" title="Снять выделение"><i class="icon-th"></i>Снять выделение <span class="task--count"></span></a>

	</div>

</DIV>
<DIV class="ui-layout-east"></DIV>
<DIV class="ui-layout-south"></DIV>

<script src="/assets/js/jquery.liTextLength.js"></script>
<script>

includeJS('/assets/js/jquery.liTextLength.js');

var $display = 'upload';
var $folder = 0;

if (isMobile || $(window).width() < 767) {

	$('.lpToggler').toggleClass('open');

}

$( function() {

	$.Mustache.load('/modules/upload/tpl.upload.mustache');

	//$('.ui-layout-center').append('<div class="tableHeader" style="position:absolute; width:100%"></div>');

	constructSpace();

	clear();
	configpage();

	//$(".nano").nanoScroller();

	changeMounth();

});

$(document).on('click', '.xfolder', function(){

	var id = $(this).data('id');
	var title = $(this).data('title');

	console.log(id)

	$folder = parseInt(id);

	$('.xfolder').removeClass('fol_it');
	$(this).addClass('fol_it');

	$('.ui-layout-west').find('#idcategory').val(id);
	$('#tips').html(title);
	$('#page').val('');

	configpage();

	multiRowClearCheck()

});

$(document).on('change', '#ftype', function () {

	preconfigpage();

});

$('#folder').on('change', function(){

	if(!isMobile) constructSpace();

});

function changeCategoryHeight(){

	var hsub  = $('#folder').height();
	var hmain = $('#category .nano1').height();
	var hwin  = $(document).height();

	if(hsub > 0.5 * hwin && hsub > hmain) $('#category .nano').height( 0.8 * hwin + 'px');
	else $('#category .nano1').height( 0.53 * hwin + 'px');

	//$("#category").find('.nano').nanoScroller();

}

function constructSpace(){

	var hf = $('.ui-layout-center').actual('height') - $('.contaner[data-id="stat"]').actual('outerHeight') - 30;
	$('.contaner[data-id="filter"]').css({"height": hf + "px", "max-height": hf + "px"});

	var hsub  = $('#folder').height();
	var hmain = $('#category .nano1').height();
	var hwin  = $(document).height();

	if(hsub > 0.5 * hwin && hsub > hmain) {
		$('#category .nano1').height(0.8 * hwin + 'px');
	}
	else {
		$('#category .nano1').height(0.53 * hwin + 'px');
	}

	//$("#category").find('.nano').nanoScroller();
	//$('.nano').nanoScroller();

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

	}, 200);

	//$('.ui-layout-center').find('.tableHeader').css({"width": "100%"});
	$('.ui-layout-content').css({"width": "100%"});
	$('#list_header').css({"width": "100%"});

});

function configpage(){

	$('#contentdiv').parent('.nano').nanoScroller({ scroll: 'top' });

	var str = $('#pageform').serialize();
	var url = '/modules/upload/list.upload.php';

	$('#contentdiv').append('<div class="contentloader"><img src="/assets/images/Services.svg" width="50px" height="50px"></div>');

	var cdheight = $('#contentdiv').height();
	var cdwidth = $('#contentdiv').width();

	$('.contentloader').height(cdheight).width(cdwidth);


	if( $('.lpToggler').hasClass('open') && isMobile ) $('.lpToggler').trigger('click');

	var all;

	$.getJSON(url, str, function(viewData) {

		$('#contentdiv').empty().mustache('uploadTpl', viewData);

		var page = viewData.page;
		var pageall = viewData.pageall;

		all = viewData.all;

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

			var order = $('#ord').val();
			var desc  = $('#tuda').val();
			var icn   = '<i class="icon-angle-down"></i>';

			$('#fcount').val(all);

			if (desc === 'desc') icn = '<i class="icon-angle-up"></i>';

			$('.header_contaner').find('#x-' + order).prepend(icn);

			var $w = [45, 90, 50];

			if($(window).width() > 1500)
				$w = [60, 120, 80];
			else if($(window).width() > 1700)
				$w = [100, 200, 160];

			$(".name-ellipsis").liTextLength({
				length: $w[2],
				afterLength: '...',
				fullText:false
			});

			$(".dot-ellipsis").liTextLength({
				length: $w[1],
				afterLength: '...',
				fullText:false
			});

			$('input.mc')
				.off('change')
				.on('change', function (){

					if( $(this).prop('checked') ) {
						$(this).closest('tr').addClass('yellowbg-sub');
					}
					else {
						$(this).closest('tr').removeClass('yellowbg-sub');
					}

				})

			$('tr[data-type="row"] td:first-child')
				.on('mousedown', function(){

					//$(this).closest('tr').toggleClass('yellowbg-sub');

					$('tr[data-type="row"] td:first-child').on('mouseenter',function(){

						//var $elm = $('input[type=checkbox]', this);
						var $elm = $(this).closest('tr').find('input:checkbox');

						//$elm.click();

						//var checkBoxes = $("input[type=checkbox]", this);
						//checkBoxes.prop("checked", !checkBoxes.prop("checked"));

						//console.log( $elm.prop('checked') );

						if( $elm.prop('checked') ) {
							$elm.prop('checked', false);
							$(this).closest('tr').removeClass('yellowbg-sub');
						}
						else {
							$elm.prop('checked', true);
							$(this).closest('tr').addClass('yellowbg-sub');
						}

						//$(this).closest('tr').toggleClass('yellowbg-sub');

						//console.log( $elm.prop('checked') );

					});

				})
				.on('mouseup', function(){

					$('tr[data-type="row"] td:first-child').off('mouseenter');

					setTimeout(function() {

						if( $('input:checkbox:checked').length > 0 ) {
							$('.multi--buttons').removeClass('hidden');
						}
						else {
							$('.multi--buttons').addClass('hidden');
						}

						$('.task--count').html( '( <b>' + $('input:checkbox:checked').length + '</b> )' );

					}, 100)

				});

			$(".nano").nanoScroller();
			multiRowClearCheck()

			if (isMobile)
				$('.ui-layout-center').find('table').rtResponsiveTables();

		});

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

	$('#page').val('1');

	var tt = $('#ord').val();

	$('#ord').val(param);

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

	$folder = 0;

}

function massSend(){

	var str = $("#cform").serialize();
	var count = $('.mc:checked').length;
	var url = '/modules/upload/form.upload.php?action=mass&count='+count+'&all='+$('#fcount').val()+'&';

	doLoad(url + str);

	return false;

}

function multiRowDel(){

	Swal.fire({
			title: 'Вы уверены?',
			text: "Записи будут удалены безвозвратно!",
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

			multiRowDelDo();

		}
	).then((result) => {

		if (result.value) {

			multiRowDelDo();

		}

	});

	function multiRowDelDo(){

		var str = $("#cform").serialize();
		var count = $('.mc:checked').length;
		var url = '/modules/upload/form.upload.php?action=mass&sub=delete&count='+count+'&all='+$('#fcount').val()+'&';

		doLoad(url + str);

		multiTaskClearCheck();

		return false;

	}

}

function multiRowMove(){

	var str = $("#cform").serialize();
	var strs = $("#contentdiv tr.yellowbg-sub input:checkbox").map(function(){
		return $(this).val();
	}).get();

	//console.log( strs.join(",") );

	doLoad('/modules/upload/form.upload.php?action=mass&sub=move&count='+ strs.length+'&all='+$('#fcount').val() + '&' + str);

	multiTaskClearCheck();

}

function multiRowClearCheck() {

	$('tr[data-type="row"]').removeClass('yellowbg-sub');
	$("input[type=checkbox]:checked").prop('checked',false);
	$('.multi--buttons').addClass('hidden');

}

</script>
<?php require_once $rootpath."/inc/panel.php"; flush();?>
</body>
</html>