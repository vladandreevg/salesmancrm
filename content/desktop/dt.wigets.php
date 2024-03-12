<?php
/* ============================ */
/*         SalesMan CRM         */
/* ============================ */
/* (C) 2016 Vladislav Andreev   */
/*       SalesMan Project       */
/*        www.isaler.ru         */
/*        ver. 2017.x           */
/* ============================ */

error_reporting( E_ERROR );

header( "Pragma: no-cache" );

$rootpath = dirname( __DIR__, 2 );

include $rootpath."/inc/config.php";
include $rootpath."/inc/dbconnector.php";
include $rootpath."/inc/auth.php";
include $rootpath."/inc/func.php";
include $rootpath."/inc/settings.php";
include $rootpath."/inc/language/".$language.".php";

$thisfile = basename( __FILE__ );

global $userRights;

$y  = date( 'Y' );
$m  = date( 'm' );
$nd = date( 'd' );

$nd2 = strftime( '%Y-%m-%d', mktime( date( 'H' ), date( 'i' ), date( 's' ), date( 'm' ), date( 'd' ), date( 'Y' ) ) + $tm * 3600 );

$st  = mktime( 0, 0, 0, $m + 1, 0, $y ); //сформировали дату для дальнейшей обработки - первый день месяца $m года $y
$dd  = (int)date( "t", $st ); //получили Стоимость дней в месяце
$d11 = strftime( '%d.%m', mktime( 0, 0, 0, $m, '01', $y ) );
$d12 = strftime( '%d.%m', mktime( 0, 0, 0, $m, $dd, $y ) );

$d1 = strftime( '%d.%m', mktime( 0, 0, 0, $m, $nd - 7, $y ) );
$d2 = strftime( '%d.%m', mktime( 0, 0, 0, $m, $nd, $y ) );

$voronkaInterval = $_COOKIE['voronkaInterval'];

$sort  = get_people( $iduser1 );
$sort2 = get_people( $iduser1, "yes" );

//виджеты финансовые
if ( $userRights['budjet'] && $settingsMore['budjetEnableVijets'] == 'yes' && $userSettings['dostup']['budjet']['money'] == 'yes' ) {

	$screenWidth = $_COOKIE['width'];

	// остатки на счетах
	$xsumma = $db -> getAll( "SELECT tip, SUM(ostatok) as summa FROM {$sqlname}mycomps_recv WHERE tip IN ('kassa','bank') and identity = '$identity' GROUP BY 1" );
	$summa  = [];
	foreach ( $xsumma as $x ) {
		$summa[ $x['tip'] ] = $x['summa'];
	}

	// расходы
	$xsumma    = $db -> getAll( "
		SELECT 
		   COALESCE(bj.do, 'off'), SUM(bj.summa) as summa
		FROM {$sqlname}budjet `bj`
		WHERE 
		    bj.year = '".date('Y')."' AND 
		    bj.mon = '".date('m')."' AND 
		    bj.cat IN (SELECT id FROM {$sqlname}budjet_cat WHERE tip = 'rashod' and rs2 = '' and identity = '$identity') and 
		    bj.identity = '$identity'
		GROUP BY 1
	" );
	$rsumma  = [];
	foreach ( $xsumma as $x ) {
		$x['tip'] = $x['tip'] == '' ? 'off' : 'on';
		$rsumma[ $x['tip'] ] = $x['summa'];
	}

	$summa_pay   = number_format( $db -> getOne( "
		SELECT 
		    SUM(cr.summa_credit) as summa 
		FROM {$sqlname}credit `cr`
		WHERE 
		    DATE_FORMAT(cr.invoice_date, '%Y-%m') = '".date( 'Y' )."-".date( 'm' )."' and 
		    cr.do = 'on' and 
		    cr.identity = '$identity'"
	), 2, ',', '`' );
	$summa_nopay = number_format( $db -> getOne( "
		SELECT 
		    SUM(cr.summa_credit) as summa 
		FROM {$sqlname}credit `cr`
		    LEFT JOIN {$sqlname}dogovor `deal` ON deal.did = cr.did 
		WHERE 
		    cr.do != 'on' AND 
		    deal.close != 'yes' AND 
		    cr.iduser IN (".implode( ",", $sort2 ).") AND 
		    cr.identity = '$identity'" ), 2, ',', '`'
	);

	if ( !$isMobile ) {

		print '

		<div class="flex-container pr5 mpr0" data-step="60" data-intro="<h1>Виджеты финансового блока</h1>" data-position="bottom">

			<DIV class="viget-mini">

				<div id="vmini1" onclick="$(\'.info1\').load(\'/content/vigets/viget.cash.php\')" class="hand" title="Показать счета">

					<i class="icon-town-hall gray3 icon-5x pull-left"></i>
					<div class="pop popright">

						<div class="cifra text-right">
							<b class="miditxt">Денег на счетах:</b><br><br>
							Банк:
							<b class="text-3x red" title="'.num_format( $summa['bank'] ).'">'.number_format( $summa['bank'], 2, ',', '`' ).'</b> '.$valuta.'
							<br>
							Касса:
							<b class="text-3x blue" title="'.num_format( $summa['kassa'] ).'">'.number_format( $summa['kassa'], 2, ',', '`' ).'</b> '.$valuta.'
						</div>

						<div class="popmenu-top left cursor-default" style="top:90px; right:inherit; left: 20px !important;">
							
							<div class="popcontent info1 w400" style="right: 0; overflow-y: auto; max-height: 60vh"></div>
							
						</div>

					</div>

				</div>

			</DIV>
			<DIV class="viget-mini">
			
				<div id="vmini2" onclick="$(\'.info2\').load(\'/content/vigets/viget.budjet.php\')" class="hand" title="Показать расходы">
				
					<i class="icon-book gray3 icon-5x pull-left"></i>
					
					<div class="pop popright">
					
						<div class="cifra text-right">
						
							<div class="Bold miditxt mb10">Расходы за месяц:</div>
							
							<div>
								Выполнено:
								<div class="Bold text-3x red inline" title="'.num_format( $rsumma['on'] ).'">'.number_format( $rsumma['on'], 2, ',', '`').'&nbsp;'.$valuta.'</div>
							</div>
							
							<div>
								Планируется:
								<div class="Bold text-3x blue inline" title="'.num_format( $rsumma['off'] ).'">'.number_format( $rsumma['off'], 2, ',', '`').'&nbsp;'.$valuta.'</div>
							</div>
							
						</div>
						
						<div class="popmenu-top center cursor-default" style="top:90px; right:0">
							
							<div class="popcontent info2 w400" style="right: 20px; min-height:50px; max-height:60vh"></div>
							
						</div>
						
					</div>
					
				</div>
				
			</DIV>
			<DIV class="viget-mini">
			
				<div id="vmini3" onclick="$(\'.info3\').load(\'/content/vigets/viget.invoices.php\')" class="hand" title="Показать счета">
				
					<i class="icon-chart-line gray3 icon-5x pull-left"></i>
					
					<div class="pop popright">
					
						<div align="right" class="cifra">
							<b class="miditxt">Счета за месяц:</b><br><br>
							Оплачено:&nbsp;<b class="text-3x red" title="'.$summa_pay.'">'.$summa_pay.'</b>&nbsp;'.$valuta.'
							<br>
							Ожидаем:&nbsp;<b class="text-3x blue" title="С учетом всех неоплаченных счетов в активных сделках:'.$summa_nopay.'">'.$summa_nopay.'</b>&nbsp;'.$valuta.'
						</div>
						
						<div class="popmenu-top cursor-default" style="top:90px; right:0">
						
							<div class="popcontent info3 w400" style="right: 20px; overflow-y: auto; max-height: 60vh"></div>
							
						</div>
						
					</div>
					
				</div>
				
			</DIV>

		</div>
		';

	}
	else {

		print '

		<!-- Swiper -->
		<div class="swiper-container mt5 mb10">
		
			<div class="swiper-wrapper">
			
				<div class="swiper-slide viget-mini">
				
					<div id="vmini1" onclick="$(\'.info1\').load(\'/content/vigets/viget.cash.php\')" class="hand" title="Показать счета">
	
						<i class="icon-town-hall gray3 icon-5x pull-left"></i>
						
						<div class="pop popright">
	
							<div align="right" class="cifra">
								<b class="miditxt">Денег на счетах:</b><br><br>
								Банк:
								<b class="text-3x red" title="'.$summa_bank.'">'.$summa_bank.'</b> '.$valuta.'
								<br>
								Касса:
								<b class="text-3x blue" title="'.$summa_kassa.'">'.$summa_kassa.'</b> '.$valuta.'
							</div>
	
							<div class="popmenu-top left cursor-default" style="top:90px; right:inherit; left: 20px !important;">
								
								<div class="popcontent left info1 w300 pad10" style="right: 0; overflow-y: auto; max-height: 60vh"></div>
								
							</div>
	
						</div>
	
					</div>
					
				</div>
				<div class="swiper-slide viget-mini">
				
					<div id="vmini2" onclick="$(\'.info2\').load(\'/content/vigets/viget.budjet.php\')" class="hand" title="Показать расходы">
						
						<i class="icon-book gray3 icon-5x pull-left"></i>
						
						<div class="pop popright">
						
							<div align="right" class="cifra">
								<b class="miditxt">Расходы за месяц:</b><br><br>
								Выполнено:
								<b class="text-3x red" title="'.num_format( $summa_do ).'">'.$summa_do.'</b>&nbsp;'.$valuta.'
								<br>
								Планируется:
								<b class="text-3x blue" title="'.num_format( $summa_pdo ).'">'.$summa_pdo.'</b>&nbsp;'.$valuta.'
							</div>
							<div class="popmenu-top left cursor-default" style="top:90px; right:0">
								
								<div class="popcontent info2 w400 pad5" style="right: 20px; min-height:50px; max-height:300px"></div>
								
							</div>
							
						</div>
						
					</div>
				
				</div>
				<div class="swiper-slide viget-mini">
				
					<div id="vmini3" onclick="$(\'.info3\').load(\'/content/vigets/viget.invoices.php\')" class="hand" title="Показать счета">
						<i class="icon-chart-line gray3 icon-5x pull-left"></i>
						<div class="pop popright">
							<div align="right" class="cifra">
								<b class="miditxt">Счета за месяц:</b><br><br>
								Оплачено:&nbsp;<b class="text-3x red" title="'.$summa_pay.'">'.$summa_pay.'</b>&nbsp;'.$valuta.'
								<br>
								Ожидаем:&nbsp;<b class="text-3x blue" title="С учетом всех неоплаченных счетов в активных сделках:'.$summa_nopay.'">'.$summa_nopay.'</b>&nbsp;'.$valuta.'
							</div>
							<div class="popmenu-top cursor-default" style="top:90px; right:0">
								<div class="top-triangle"></div>
								<div class="top-triangle-white"></div>
								<div class="popcontent info3 w400 pad5" style="right: 20px; overflow-y: auto; max-height: 60vh"></div>
							</div>
						</div>
					</div>
				
				</div>
				
			</div>
			
			<!-- Add Pagination -->
			<div class="swiper-pagination"></div>
			
			<!-- Add Arrows -->
			<!--<div class="swiper-button-next"></div>
			<div class="swiper-button-prev"></div>-->
			
		</div>
		
		';

	}

}

include $rootpath."/content/vigets/mini.vigets.php";
?>

<form action="" id="vigetname" name="vigetname" method="post" enctype="multipart/form-data">

	<div class="vigetdiv flex-container1 mpr5">

		<?php
		$result      = $db -> getRow( "SELECT * FROM {$sqlname}user WHERE iduser = '$iduser1' and identity = '$identity'" );
		$viget_on    = yexplode( ";", $result["viget_on"] );
		$viget_order = yexplode( ";", $result["viget_order"] );
		$num         = count( $viget_order );

		/**
		 * Формируем виджеты
		 */
		$vigetsCustom = [];
		$vigetsBase   = json_decode( str_replace( [
			"  ",
			"\t",
			"\n",
			"\r"
		], "", file_get_contents( $rootpath."/cash/map.vigets.json" ) ), true );

		if ( file_exists( $rootpath."/cash/map.vigets.castom.json" ) ) {
			
			$vigetsCustom = json_decode( str_replace( [
				"  ",
				"\t",
				"\n",
				"\r"
			], "", file_get_contents( $rootpath."/cash/map.vigets.castom.json" )), true );
			
		}

		//print_r($vigetsCustom);

		$vigetsBase = array_merge( (array)$vigetsBase, (array)$vigetsCustom );

		$vigetsBody = '';

		$vigets = $userSettings['vigets'];
		if ( empty( $vigets ) ) {
			$vigets = $vigetsBase;
		}

		foreach ( $vigets as $viget => $param ) {

			if ( !empty( $param['module'] ) ) {

				$isActive = $db -> getOne( "SELECT active FROM {$sqlname}modules WHERE mpath = '$param[module]' and identity = '$identity'" );

				if ( $isActive != 'on' ) {
					continue;
				}

			}

			if ( is_array( $param ) ) {
				$param = $vigetsBase[ $viget ]['active'];
			}

			if ( $param == 'on' && !empty($vigetsBase[ $viget ]['name']) ) {

				$toolClass = $toolTip = $toolPos = '';

				if ( $vigetsBase[ $viget ]['tooltips'] != '' ) {

					$toolClass = ' tooltips';
					$toolTip   = ' tooltip="'.$vigetsBase[ $viget ]['tooltips'].'"';
					$toolPos   = ' tooltip-position="'.$vigetsBase[ $viget ]['tooltips-position'].'"';

				}

				if ( !file_exists( $rootpath.'/'.$vigetsBase[ $viget ]['url'] ) ) {
					$vigetsBase[ $viget ]['url'] = '/content/'.$vigetsBase[ $viget ]['url'];
				}

				$expressReport = ($vigetsBase[ $viget ]['expressReport'] != '') ? '<a href="javascript:void(0)" onclick="getSwindow(\''.$vigetsBase[ $viget ]['expressReport'].'\', \''.$vigetsBase[ $viget ]['expressReportTitle'].'\')" class="pull-aright refresh gray blue mr5" title="Показать аналитику"><i class="icon-chart-line blue"></i></a>' : '';

				$settingsURL = ($vigetsBase[ $viget ]['settingsURL'] != '') ? '<a href="javascript:void(0)" onclick="doLoad(\''.$vigetsBase[ $viget ]['settingsURL'].'\')" class="pull-aright gray blue mr5" title="Настройки"><i class="icon-tools blue"></i></a>' : '';

				$vigetsBody .= '
				<DIV class="viget flx-basis-x'.$vigetsBase[ $viget ]['width'].' '.$vigetsBase[ $viget ]['containerclass'].' '.$vigetsBase[ $viget ]['height'].'" data-id="'.$viget.'" data-url="'.$vigetsBase[ $viget ]['url'].'">
			
					<div class="vigetHeader '.$toolClass.'" '.$toolTip.$toolPos.'>
			
						<a href="javascript:void(0)" class="pull-aright handle inline gray hidden-iphone" title="'.$lang['all']['Move'].'"><i class="icon-shuffle-1 blue"></i></a>
			
						<a href="javascript:void(0)" class="pull-aright refresh gray blue mr5" title="Обновить"><i class="icon-arrows-cw blue"></i></a>
						
						'.$settingsURL.'
			
						'.$vigetsBase[ $viget ]['actionPlus'].'
			
						'.$expressReport.'
			
						<div class="inline Bold gray2"><i class="'.$vigetsBase[ $viget ]['icon'].' gray2"></i>&nbsp;'.str_replace( ["{{DealsName}}"], [$lang['face']['DealName'][1]], $vigetsBase[ $viget ]['name'] ).'</div>
			
					</div>
			
					<div id="'.$viget.'" class="'.$vigetsBase[ $viget ]['class'].'"></div>
					<input type="hidden" name="vg[]" id="vg[]" value="'.$viget.'">
			
				</DIV>
				';

			}

		}

		print $vigetsBody;
		?>

	</div>

</form>

<div style="height:10px; display:inline-block; width:100%"></div>

<script>

	$(function () {

		if (!isMobile) $(".nano").nanoScroller();

		//Загрузка виджетов. Начало
		$('.viget').each(function () {

			var id = $(this).data('id');
			var url = $(this).data('url');

			setTimeout(function () {

				$("#" + id).empty().load(
					url,
					function () {

						if (isMobile) $("#" + id).find('table').rtResponsiveTables({"id": "table-" + id});

					}
				).append('<div id="loader"><img src="/assets/images/loading.svg"></div>');

			}, 50);

		});

		if (isMobile) {

			var swiper = new Swiper('.swiper-container', {
				slidesPerView: 1,
				spaceBetween: 10,
				pagination: {
					el: '.swiper-pagination',
					type: 'fraction'
				}
			});
			var ww = $('.viget-mini').width() + 5;

		}
		//Загрузка виджетов. Конец

		$(".vigetdiv").sortable({
			handle: ".handle",
			cursor: "move",
			opacity: "0.85",
			placeholder: "viget-placeholder",
			start: function (event, ui) {
				$('.viget-placeholder').width($(".viget").width() + 10);
			},
			stop: function (event, ui) {
				var str = $('#vigetname').serialize();
				var url = '/content/ajax/user.settings.php?action=order&' + str;
				//console.log(str);
				$.post(url, function (data) {
					$('#message').fadeTo(1, 1).css('display', 'block').html(data);
					setTimeout(function () {
						$('#message').fadeTo(1000, 0);
					}, 5000);
				});
			}
		}).disableSelection();

		/*tooltips*/
		if (!isMobile) {

			$('.tooltips').append("<span></span>");
			$('.tooltips:not([tooltip-position])').attr('tooltip-position', 'bottom');
			$(".tooltips").mouseenter(function () {

				$(this).find('span').empty().append($(this).attr('tooltip'));

				$(document).find('.tooltips').each(function () {

					var element = $(this).closest('div');
					var eposition = element.position();
					var tposition = $(this).attr('tooltip-position');

					if (tposition === 'top' && eposition.top < 100) $(this).attr('tooltip-position', 'bottom');
					else if (tposition === 'right' && eposition.right < 100) $(this).attr('tooltip-position', 'left');
					else if (tposition === 'left' && eposition.right < 100) $(this).attr('tooltip-position', 'right');

					$('.viget-micro').find('.tooltips').attr('tooltip-position', 'bottom');
					$('.viget-micro').find('.tooltips').find('span').css({'top': '250%'});

				});

			});

		}

		/*tooltips*/

		$(document).off('click', '.refresh');
		$(document).on('click', '.refresh', function () {

			var id = $(this).closest('.viget').data('id');
			var url = $(this).closest('.viget').data('url');

			$('#' + id).empty().append('<div><img src="/assets/images/loading.svg"> Загрузка данных...</div>');

			$.get(url, function (data) {

				$('#message').empty().css('display', 'none');
				$('#' + id).html(data).find('table').rtResponsiveTables();

			});

		});

		//регулируем направление элементов pop в зависимости от положения к левой/правой границе
		$(document).find('.viget-micro').each(function () {

			var element = $(this);
			var eposition = element.position();

			if (eposition.left < 200) {

				element.find('.popmenu-top').css({'right': 'inherit', 'left': '20% !important'});
				element.find('.top-triangle').css({'right': 'inherit', 'left': '10%'});
				element.find('.top-triangle-white').css({'right': 'inherit', 'left': '10%'});

			}
			else if (eposition.right < 200) {

				element.find('.popmenu-top').css({'left': 'inherit', 'right': '20% !important'});
				element.find('.top-triangle').css({'left': 'inherit', 'right': '10%'});
				element.find('.top-triangle-white').css({'left': 'inherit', 'right': '10%'});

			}

		});

		$('div[data-id="viget-health"]').load('/content/desktop/dt.health.php?view=count&verify=0&verifyname=0');

	});

</script>