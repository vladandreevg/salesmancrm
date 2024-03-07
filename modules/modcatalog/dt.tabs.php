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

$tabs = ''; $count = 0; $current = "zboard";

if(in_array($iduser1, $msettings['mcSpecialist']) || in_array($iduser1, $msettings['mcCoordinator'])){

	if($msettings['mcDBoardSklad'] == 'yes') {

		//$tabs .= '<li class="flex-string fs-12 wp30" data-url="board">' . $msettings['mcDBoardSkladName'] . '</li>';
		//$count++;

	}
	//else
		$current = "zboard";

	if($msettings['mcDBoardZayavka'] == 'yes') {
		$tabs .= '<li class="flex-string fs-12 wp30" data-url="zboard">' . $msettings['mcDBoardZayavkaName'] . '</li>';
		$count++;
	}
	else $current = "oboard";

	if($msettings['mcDBoardOffer'] == 'yes') {
		$tabs .= '<li class="flex-string fs-12 wp30" data-url="oboard">' . $msettings['mcDBoardOfferName'] . '</li>';
		$count++;
	}

}
?>

<div class="catalog--container" data-id="container">

	<div id="ytabs" class="catalog--tabs fixedHeader3 sticked--top <?=($count > 1 ? '' : 'hidden')?>">

		<ul class="gray flex-container blue">
			<?=$tabs?>
		</ul>

	</div>
	<div id="container">

		<!--<div class="catalog--board cbox"></div>-->
		<div class="catalog--zboard cbox"></div>
		<div class="catalog--oboard cbox flex-conteiner"></div>

	</div>
</div>

<script>

	$(document).ready( function() {

		$('.catalog--tabs').each(function(){

			$(this).find('ul li').removeClass('active');
			$(this).find('ul li:first-child').addClass('active');

			$(this).find('.cbox').addClass('hidden');
			$(this).find('.cbox:first-child').removeClass('hidden');

		});

		var first = $('.catalog--tabs').find('ul li:first-child').data('url');
		$('.catalog--'+ first).load('modules/modcatalog/dt.<?=$current?>.php').append('<img src="/assets/images/loading.gif">');


	});

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

