<?php
/* ============================ */
/*         SalesMan CRM         */
/* ============================ */
/* (C) 2016 Vladislav Andreev   */
/*       SalesMan Project       */
/*        www.isaler.ru         */
/*        ver. 2019.2           */
/* ============================ */

error_reporting(E_ERROR);
header("Pragma: no-cache");

$rootpath = realpath( __DIR__.'/../../' );

include $rootpath."/inc/config.php";
include $rootpath."/inc/dbconnector.php";
include $rootpath."/inc/auth.php";
include $rootpath."/inc/func.php";
include $rootpath."/inc/settings.php";
include $rootpath."/inc/language/".$language.".php";

$thisfile = basename( __FILE__ );

$msettings = $db -> getOne("SELECT settings FROM ".$sqlname."modcatalog_set WHERE identity = '$identity'");
$msettings = json_decode($msettings, true);
$msettings['mcSklad'] = 'yes';

if($msettings['mcDBoardSkladName'] == '')     $msettings['mcDBoardSkladName']   = 'Склад';
if($msettings['mcDBoardZayavkaName'] == '')   $msettings['mcDBoardZayavkaName'] = 'Заявки';
if($msettings['mcDBoardOfferName'] == '')     $msettings['mcDBoardOfferName']   = 'Предложения';

$tabs = ''; $count = 0; $current = "board";

if(in_array($iduser1, $msettings['mcSpecialist']) || in_array($iduser1, $msettings['mcCoordinator'])){

	if($msettings['mcDBoardSklad'] == 'yes') {

		//$tabs .= '<li class="flex-string fs-12 hidden" data-url="board">' . $msettings['mcDBoardSkladName'] . '</li>';
		//$count++;

	}
	else
		$current = "zboard";

	if($msettings['mcDBoardZayavka'] == 'yes') {

		$tabs .= '<li class="flex-string fs-12" data-url="zboard">' . $msettings['mcDBoardZayavkaName'] . '</li>';
		$count++;

	}
	else $current = "oboard";

	if($msettings['mcDBoardOffer'] == 'yes') {

		$tabs .= '<li class="flex-string fs-12" data-url="oboard">' . $msettings['mcDBoardOfferName'] . '</li>';
		$count++;

	}

}

if($count > 1){

	$class = '';
	$margin = '50';

}
else{

	$class = 'hidden';
	$margin = '0';

}
?>

<div class="catalog--container" data-id="container">

	<div id="ytabs" class="catalog--tabs fixedHeader <?=$class?>">

		<ul class="gray flex-container blue">

			<?=$tabs?>

		</ul>

	</div>
	<div id="container" style="margin-top: <?=$margin?>px">

		<div class="catalog--board cbox hidden"></div>
		<div class="catalog--zboard cbox"></div>
		<div class="catalog--oboard cbox"></div>

	</div>
</div>

<script>

	//includeJS('../../js/popper.js');

	$(document).ready( function() {

		$('.catalog--tabs').each(function(){

			$(this).find('ul li').removeClass('active');
			$(this).find('ul li:first-child').addClass('active');

			$(this).find('.cbox').addClass('hidden');
			$(this).find('.cbox:first-child').removeClass('hidden');

			$('.catalog--board').load('modules/modcatalog/dt.<?=$current?>.php').append('<img src="/assets/images/loading.gif">');

		});

	});

	/*$(window).bind('resizeEnd', function() {

		var cdwidth = $('#last').width();
		var fhLeft  = $('#last').offset().left;
		var fhTop   = $('#last').offset().top;
		var current = $('#ytabs').find('li.active').data('url');

		$('#tabs-10').find('.fixedHeader').css({'width':cdwidth,'top':fhTop,'left':fhLeft});

		if (typeof boardResize === 'function' && current == 'board') boardResize();
		if (typeof zboardResize === 'function' && current == 'zboard') zboardResize();
		if (typeof oboardResize === 'function' && current == 'oboard') oboardResize();

	});*/

	$('.catalog--tabs ul li').bind('click', function(){

		var url = $(this).data('url');
		var element  = $(this).closest('.catalog--container');

		element.find('li').removeClass('active');
		$(this).addClass('active');

		element.find('.cbox').addClass('hidden');
		element.find('.catalog--'+ url).removeClass('hidden');

		$('.catalog--'+ url).load('modules/modcatalog/dt.'+ url +'.php').append('<img src="/assets/images/loading.gif">');

	});

</script>

