<?php
/* ============================ */
/*         SalesMan CRM         */
/* ============================ */
/* (C) 2016 Vladislav Andreev   */
/*       SalesMan Project       */
/*        www.isaler.ru         */
/*        ver. 2017.6           */
/* ============================ */
use Salesman\DealAnketa;

set_time_limit( 0 );

error_reporting( E_ERROR );
header( "Pragma: no-cache" );

$rootpath = dirname(__DIR__, 2);

require_once $rootpath."/inc/config.php";
require_once $rootpath."/inc/dbconnector.php";
require_once $rootpath."/inc/auth.php";
require_once $rootpath."/inc/func.php";
require_once $rootpath."/inc/settings.php";
require_once $rootpath."/inc/language/".$language.".php";

$action = $_REQUEST[ 'action' ];

$anketa = new DealAnketa();

//вывод списка анкет по сделкам
if ( $action == 'list' ) {

	$page           = $_REQUEST[ 'page' ];
	$word           = $_REQUEST[ 'word' ];
	$ida            = $_REQUEST[ 'ida' ];
	$lines_per_page = 50;

	$list = [];
	$sort = '';

	$sort .= ( !empty( $word ) ) ? $sqlname."deal_anketa.value LIKE '%$word%' and" : "";
	$sort .= ( $ida > 0 ) ? $sqlname."deal_anketa.ida = '$ida' and" : "";

	$q1 = "
	SELECT
		CONCAT({$sqlname}deal_anketa.did, '-', {$sqlname}deal_anketa.ida) as grcon
	FROM {$sqlname}deal_anketa
		LEFT JOIN {$sqlname}dogovor ON {$sqlname}dogovor.did = {$sqlname}deal_anketa.did
	WHERE 
		$sort
		{$sqlname}deal_anketa.identity = '$identity' AND
		(
			{$sqlname}dogovor.iduser IN (".implode( ",", get_people( $iduser1, "yes" ) ).") OR 
			{$sqlname}deal_anketa.did IN (SELECT did FROM {$sqlname}dostup WHERE iduser = '$iduser1' and identity = '$identity')
		)
	GROUP BY grcon
	";

	$r         = $db -> getCol( $q1 );
	$all_lines = count( $r );

	$q = "
	SELECT
		{$sqlname}deal_anketa.id as id,
		{$sqlname}deal_anketa.did as did,
		{$sqlname}deal_anketa.ida as ida,
		CONCAT({$sqlname}deal_anketa.did, '-', {$sqlname}deal_anketa.ida) as grcon
	FROM {$sqlname}deal_anketa
		LEFT JOIN {$sqlname}dogovor ON {$sqlname}dogovor.did = {$sqlname}deal_anketa.did
	WHERE 
		$sort
		{$sqlname}deal_anketa.identity = '$identity' AND
		(
			{$sqlname}dogovor.iduser IN (".implode( ",", get_people( $iduser1, "yes" ) ).") OR 
			{$sqlname}deal_anketa.did IN (SELECT did FROM {$sqlname}dostup WHERE iduser = '$iduser1' and identity = '$identity')
		)
	GROUP BY grcon
	";

	if ( $page > ceil( $all_lines / $lines_per_page ) ) {
		$page = 1;
	}

	if ( empty( $page ) || $page <= 0 ) {
		$page = 1;
	}
	else {
		$page = (int)$page;
	}
	$page_for_query = $page - 1;
	$lpos           = $page_for_query * $lines_per_page;

	$count_pages = ceil( $all_lines / $lines_per_page );

	if ( $count_pages < 1 ) {
		$count_pages = 1;
	}

	$limit = "LIMIT $lpos,$lines_per_page";

	$q      .= " ORDER BY {$sqlname}deal_anketa.id DESC $limit";
	$result = $db -> query( $q );
	while ( $da = $db -> fetch( $result ) ) {

		$clid = getDogData( $da[ 'did' ], 'clid' );

		$atitle = $anketa -> anketainfo( $da[ 'ida' ] );

		$list[] = [
			"id"     => $da[ 'ida' ],
			"anketa" => $atitle[ 'title' ],
			"clid"   => ( $clid > 0 ) ? $clid : '',
			"client" => ( $clid > 0 ) ? current_client( $clid ) : '',
			"did"    => ( $da[ 'did' ] > 0 ) ? $da[ 'did' ] : '',
			"deal"   => ( $da[ 'did' ] > 0 ) ? current_dogovor( $da[ 'did' ] ) : '',
			"color"  => $color,
			"user"   => current_user( getDogData( $da[ 'did' ], 'iduser' ) )
		];

	}

	$data = [
		"list"    => $list,
		"page"    => $page,
		"pageall" => $count_pages
	];

	$list = json_encode_cyr( $data );

	print $list;

	exit();

}

if ( $action == 'view' ) {

	$ida = $_REQUEST[ 'ida' ];
	$did = $_REQUEST[ 'did' ];

	$ianketa = $anketa -> anketainfo( $ida );

	$print = $anketa::anketaprint( $ida, $did, false );

	?>
	<DIV class="zagolovok">Анкета "<?= $ianketa[ 'title' ] ?>"</DIV>

	<DIV id="formtabs" style="overflow-y: auto; overflow-x: hidden">
		<?= $print ?>
	</DIV>

	<hr>

	<div class="button--pane pull-aright">
		<A href="javascript:void(0)" onclick="DClose()" class="button">Закрыть</A>
	</div>
	<script>

		var hh = $('#dialog_container').actual('height') * 0.90;
		var hh2 = hh - $('.zagolovok').actual('outerHeight') - $('.button--pane').actual('outerHeight') - 30;

		if ($(window).width() > 990) $('#dialog').css({'width': '800px'});
		else $('#dialog').css('width', '90vw');

		$('#formtabs').css('max-height', hh2);

		$(function () {

			$('#dialog').center();

		});

	</script>
	<?php

	exit();

}

if ( $action == 'anketalist' ) {

	$list = $anketa -> anketalist();

	$list = json_encode_cyr( ["list" => $list] );

	print $list;

	exit();

}

if ( $_REQUEST[ 'modal' ] == 'true' ) {
	?>
	<div class="p10 viewdiv flex-container hidden" id="anktop">

		<div id="reload" class="flex-string right-text">

			<a href="javascript:void(0)" onclick="AnketaPageRender()" class="gray blue" title="Обновить"><i class="icon-arrows-cw"></i></a>

		</div>

	</div>

	<div class="flex-container box--child">

		<div class="flex-string wp20 anketas graybg-sub bgwhite" style="overflow: hidden">

			<div class="asearch p10 graybg-sub no-border relativ cleared">

				<input id="word" name="word" type="text" class="wp99" onkeydown="if(event.keyCode === 13){ AnketaPageRender(); return false }" placeholder="Поиск">
				<div class="idel mr10 mt10 clearinputs" data-func="AnketaPageRender">
					<i class="icon-block red hand"></i>
				</div>
				<div class="gray2 fs-09 em">По содержимому</div>

			</div>
			<div class="alist" style="overflow-x: hidden; overflow-y: auto"></div>

		</div>
		<div class="flex-string wp80 lists pl5"></div>

	</div>

	<div class="p10 viewdiv flex-container" id="ankbottom">

		<div id="pagination" class="flex-string"></div>
		<div id="reload" class="flex-string right-text">

			<a href="javascript:void(0)" onclick="AnketaPageRender()" class="button1" title="Обновить"><i class="icon-arrows-cw"></i> Обновить</a>

		</div>

	</div>

	<script src="/assets/js/jquery.liTextLength.js"></script>
	<script src="/assets/js/tableHeadFixer/tableHeadFixer.js"></script>
	<script>

		$('.footer').addClass('hidden');

		var $element = $('.lists');
		var $anketas = $('.anketas');
		var $anketalist = $('.alist');
		var $dapage = 1;
		var height = $('#swindow').find('.body').innerHeight() - $('#ankbottom').outerHeight() + 40;
		var aheight = $('#swindow').find('.body').innerHeight() - $('.asearch').outerHeight() - $('#ankbottom').outerHeight() + 40;
		var $ida = 0;

		$.Mustache.load('content/deal.anketa/tpl.mustache');

		$(document).off('change', '#filterAnketa');
		$(document).off('click', '.anketalist');

		$(function () {

			$('#swindow').find('.body').css({"height": "calc(100vh - 60px)"});

			AnketaList();
			AnketaPageRender();

		});

		function AnketaPageRender() {

			var word = $('#swindow').find('#word').val();
			var url = 'content/deal.anketa/list.php?action=list&word=' + word + '&page=' + $dapage + '&ida=' + $ida;

			$element.empty().append('<img src="/assets/images/Services.svg" width="50px" height="50px">');

			$.getJSON(url, function (viewData) {

				$element.empty().mustache('listTpl', viewData);
				$element.css({'height': height + 'px'});
				$element.find("#zebraTable").tableHeadFixer({'z-index': 12000});
				$element.find('.ellipsis').css({"position": "inherit"});
				$element.find('i').css({"position": "inherit"});

				$anketas.css({'height': (height - 5) + 'px'});
				$anketalist.css({'height': (aheight - 5) + 'px', 'max-height': (aheight - 5) + 'px'});

				var page = viewData.page;
				var pageall = viewData.pageall;

				var pg = 'Стр. ' + page + ' из ' + pageall;

				if (pageall > 1) {

					var prev = page - 1;
					var next = page + 1;

					if (page === 1) pg = '&nbsp;<a href="javascript:void(0)" title="Начало"><i class="icon-angle-double-left gray"></i></a>&nbsp;&nbsp;<a href="javascript:void(0)" title="Предыдущая"><i class="icon-angle-left gray"></i></a>&nbsp;' + pg + '&nbsp;<a href="javascript:void(0)" onclick="AnketaPageChange(\'' + next + '\')" title="Следующая"><i class="icon-angle-right"></i></a>&nbsp;&nbsp;<a href="javascript:void(0)" onclick="AnketaPageChange(\'' + pageall + '\')" title="Последняя"><i class="icon-angle-double-right"></i></a>&nbsp;';
					else if (page === pageall) pg = '&nbsp;<a href="javascript:void(0)" onclick="AnketaPageChange(\'1\')" title="Начало"><i class="icon-angle-double-left"></i></a>&nbsp;&nbsp;<a href="javascript:void(0)" onclick="AnketaPageChange(\'' + prev + '\')" title="Предыдущая"><i class="icon-angle-left"></i></a>&nbsp;' + pg + '&nbsp;<a href="javascript:void(0)" title="Следующая"><i class="icon-angle-right gray"></i></a>&nbsp;&nbsp;<a href="javascript:void(0)" title="Последняя"><i class="icon-angle-double-right gray"></i></a>&nbsp;';
					else pg = '&nbsp;<a href="javascript:void(0)" onclick="AnketaPageChange(\'1\')" title="Начало"><i class="icon-angle-double-left"></i></a>&nbsp;&nbsp;<a href="javascript:void(0)" onclick="AnketaPageChange(\'' + prev + '\')" title="Предыдущая"><i class="icon-angle-left"></i></a>&nbsp;' + pg + '&nbsp;<a href="javascript:void(0)" onclick="AnketaPageChange(\'' + next + '\')" title="Следующая"><i class="icon-angle-right"></i></a>&nbsp;&nbsp;<a href="javascript:void(0)" onclick="AnketaPageChange(\'' + pageall + '\')" title="Последняя"><i class="icon-angle-double-right"></i></a>&nbsp;';

				}

				$('#pagination').html(pg);

			});

		}

		function AnketaPageChange(page) {

			$dapage = page;
			DoublesPageRender();

		}

		function AnketaList() {

			var url = 'content/deal.anketa/list.php?action=anketalist';

			$anketalist.empty().append('<img src="/assets/images/Services.svg" width="50px" height="50px">');

			$.getJSON(url, function (viewData) {

				$anketalist.empty().mustache('anketaTpl', viewData);

				$(".acontent").liTextLength({
					length: 200,
					afterLength: '...',
					fullText: false
				});

			});

		}

		$(document).on('click', '.closer', function () {

			$('#swindow').find('.body').css({"height": ""});

		});
		$(document).on('change', '#filterAnketa', function () {

			AnketaPageChange();

		});
		$(document).on('click', '.anketalist', function () {

			var ida = parseInt($(this).data('id'));

			if ($ida !== ida) {

				$ida = ida;

				$('.anketalist').not(this).removeClass('bluebg-sub');
				$(this).addClass('bluebg-sub');

			}
			else {

				$ida = 0;

				$('.anketalist').removeClass('bluebg-sub');

			}

			AnketaPageRender();

		});

	</script>
	<?php

}