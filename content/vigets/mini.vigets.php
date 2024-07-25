<?php
/* ============================ */
/*         SalesMan CRM         */
/* ============================ */
/* (C) 2016 Vladislav Andreev   */
/*       SalesMan Project       */
/*        www.isaler.ru         */
/*        ver. 2017.x           */
/* ============================ */

$voronkaInterval = $_COOKIE['voronkaInterval'];

$sort  = get_people( $iduser1 );
$sort2 = get_people( $iduser1, "yes" );

$countHealth = 0;

$htaddr = $_SERVER[ 'HTTP_SCHEME' ] ?? ( ( isset( $_SERVER[ 'HTTPS' ] ) && $_SERVER[ 'HTTPS' ] != 'off' ) || 443 == $_SERVER[ 'SERVER_PORT' ] ) ? 'https://' : 'http://';

/**
 * Здоровье сделок
 */
if ( $_REQUEST['hidedeals'] != 'yes' && $settingsMore['dealHealthOn'] == 'yes' ) {

	$stat['health'] = [
		'count'   => $countHealth + 0,
		'summa'   => "плохих сделок",
		'title'   => 'Здоровье',
		'tooltip' => 'Число сделок с нарушенными сроками или без напоминаний',
		'color'   => 'red'
	];

}

/**
 * Заявки
 */
$mdwset = $db -> getRow( "SELECT * FROM {$sqlname}modules WHERE mpath = 'leads' and identity = '$identity'" );
if ( $mdwset['active'] == 'on' ) {

	$leadsettings = json_decode( (string)$mdwset['content'], true );
	$coordinator  = $leadsettings["leadСoordinator"];
	$operators    = (array)$leadsettings["leadOperator"];

	//заявки для координатора или для всех, при режиме "Свободная касса"
	if ( $iduser1 == $coordinator || $leadsettings['leadMethod'] == 'free' ) {

		$l1 = $db -> getOne( "SELECT COUNT(id) as count FROM {$sqlname}leads WHERE id > 0 and status = '1' and identity = '$identity'" );
		$l2 = $db -> getOne( "SELECT COUNT(id) as count FROM {$sqlname}leads WHERE id > 0 and status = '2' and DATE_FORMAT(datum_do, '%Y-%m') = '".date( 'Y' )."-".date( 'm' )."' and identity = '$identity'" );

		$stat['leadscold'] = [
			'count'   => $db -> getOne( "SELECT COUNT(id) as count FROM {$sqlname}leads WHERE id > 0 and status = '0' and identity = '$identity'" ),
			'summa'   => '<span title="В работе">'.$l1.'</span> / <span title="Обработано">'.$l2.'</span>',
			'title'   => 'Открытые заявки',
			'tooltip' => 'Не распределенные заявки',
			'color'   => 'deepblue'
		];

	}

	//заявки для операторов
	if ( in_array( $iduser1, $operators ) ) {

		$l1 = $db -> getOne( "SELECT COUNT(id) as count FROM {$sqlname}leads WHERE id > 0 and iduser = '$iduser1' and status = '2' and DATE_FORMAT(datum_do, '%Y-%m') = '".date( 'Y' )."-".date( 'm' )."' and identity = '$identity'" );

		$l2 = $db -> getOne( "SELECT COUNT(id) as count FROM {$sqlname}leads WHERE id > 0 and iduser = '$iduser1' and status = '3' and DATE_FORMAT(datum_do, '%Y-%m') = '".date( 'Y' )."-".date( 'm' )."' and identity = '$identity'" );

		$stat['leads'] = [
			'count'   => $db -> getOne( "SELECT COUNT(id) as count FROM {$sqlname}leads WHERE id > 0 and iduser = '$iduser1' and status = '1' and identity = '$identity'" ),
			'summa'   => '<span title="Обработано"><i class="icon-ok green"></i>'.$l1.'</span> / <span title="Закрыто"><i class="icon-block red"></i>'.$l2.'</span><div class="popmenu-top cursor-default" style="top:110px !important; right:inherit; left: 0;"><div class="top-triangle" style="left:20%"></div><div class="top-triangle-white" style="left:20%"></div><div class="popcontent info34 yw350" style="right: 0; max-height:45vh"></div></div>',
			'title'   => 'Заявки',
			'tooltip' => 'Заявки в работе',
			'color'   => 'blue'
		];

	}

}

/**
 * Обращения
 */
if ( $isEntry == 'on' ) {

	$c1 = $db -> getOne( "SELECT COUNT(ide) as count FROM {$sqlname}entry WHERE ide > 0 and status = '1' and DATE_FORMAT(datum_do, '%Y-%m') = '".date( 'Y' )."-".date( 'm' )."' and identity = '$identity'" );
	$c2 = $db -> getOne( "SELECT COUNT(ide) as count FROM {$sqlname}entry WHERE ide > 0 and status = '2' and DATE_FORMAT(datum_do, '%Y-%m') = '".date( 'Y' )."-".date( 'm' )."' and identity = '$identity'" );

	$stat['entry'] = [
		'count'   => $db -> getOne( "SELECT COUNT(ide) as count FROM {$sqlname}entry WHERE ide > 0 and status = '0' $sort and identity = '$identity'" ),
		'summa'   => '<span onclick="$(\'.info33\').empty().load(\'/content/vigets/viget.entry.pop.php?status=1\')" title="Обработано"><i class="icon-ok green"></i>'.$c1.'</span> / <span onclick="$(\'.info33\').empty().load(\'/vigets/viget.entry.pop.php?status=2\')" title="Закрыто"><i class="icon-block red"></i>'.$c2.'</span><div class="popmenu-top left cursor-default" style="top:110px !important; left: 0; right: initial"><div class="popcontent info33 yw350" style="left:0; max-height:35vh"></div></div>',
		'title'   => 'Обращения',
		'tooltip' => 'Не обработанные обращения',
		'color'   => 'fiolet'
	];

}

/**
 * напоминалки
 */
$stat['todo'] = [
	'count'   => $db -> getOne( "SELECT COUNT(tid) as count FROM {$sqlname}tasks WHERE tid > 0 and active = 'yes' and iduser = '$iduser1' and datum <= '".current_datum()."' and identity = '$identity'" ),
	'summa'   => 'в т.ч. просроченные',
	'title'   => 'ToDo',
	'tooltip' => 'Не выполненные напоминания',
	'color'   => 'orange'
];

if ( $_REQUEST['hidedeals'] != 'yes' ) {

	//новые сделки
	//$res                      = $db -> getRow("SELECT COUNT(*) as count, SUM(kol) as summa FROM {$sqlname}dogovor WHERE did > 0 $sort and DATE_FORMAT(datum, '%Y-%m') = '".date('Y')."-".date('m')."' and identity = '$identity'");
	$res = $db -> getRow( "SELECT COUNT(did) as count, SUM(kol) as summa FROM {$sqlname}dogovor WHERE did > 0 AND autor IN (".yimplode( ",", (array)get_people( $iduser1, "yes" ) ).") and DATE_FORMAT(datum, '%Y-%m') = '".date( 'Y' )."-".date( 'm' )."' and identity = '$identity'" );

	$stat['deals']['count']   = $res['count'];
	$stat['deals']['summa']   = num_format( $res['summa'] ).' '.$valuta.'';
	$stat['deals']['title']   = 'Новые сделки';
	$stat['deals']['tooltip'] = 'Количество созданных сделок за месяц';
	$stat['deals']['color']   = 'blue';

	//выставленные счета
	$res                         = $db -> getRow( "SELECT COUNT(crid) as count, SUM(summa_credit) as summa FROM {$sqlname}credit WHERE crid > 0 $sort and DATE_FORMAT(datum_credit, '%Y-%m') = '".date( 'Y' )."-".date( 'm' )."' and identity = '$identity'" );
	$stat['invoices']['count']   = $res['count'];
	$stat['invoices']['summa']   = num_format( $res['summa'] ).' '.$valuta.'<div class="popmenu-top cursor-default" style="top:110px !important; right: 0;"><div class="popcontent info31 yw350" style="right: 0; max-height:35vh"></div></div>';
	$stat['invoices']['title']   = 'Новые счета';
	$stat['invoices']['tooltip'] = 'Количество выставленных счетов за месяц';
	$stat['invoices']['color']   = 'broun';

	//ожидаемые оплаты
	$res = $db -> getRow( "SELECT COUNT(*) as count, SUM({$sqlname}credit.summa_credit) as summa FROM {$sqlname}credit LEFT JOIN {$sqlname}dogovor ON {$sqlname}dogovor.did = {$sqlname}credit.did WHERE {$sqlname}credit.do != 'on' and COALESCE({$sqlname}dogovor.close, 'no') != 'yes' and {$sqlname}credit.iduser IN (".implode( ",", $sort2 ).") and {$sqlname}credit.identity = '$identity'" );

	$stat['credit']['count']   = $res['count'];
	$stat['credit']['summa']   = num_format( $res['summa'] ).' '.$valuta.'<div class="popmenu-top right cursor-default" style="top:110px !important; right: 0;"><div class="popcontent info32 yw350" style="right: 0; max-height:35vh"></div></div>';
	$stat['credit']['title']   = 'Ждем оплату';
	$stat['credit']['tooltip'] = 'Не оплаенные счета';
	$stat['credit']['color']   = 'red';

	//оплачено
	$res                    = $db -> getRow( "SELECT COUNT(crid) as count, SUM(summa_credit) as summa FROM {$sqlname}credit WHERE crid > 0 and do = 'on' $sort and DATE_FORMAT(invoice_date, '%Y-%m') = '".date( 'Y' )."-".date( 'm' )."' and identity = '$identity'" );
	$stat['pay']['count']   = $res['count'];
	$stat['pay']['summa']   = num_format( $res['summa'] ).' '.$valuta;
	$stat['pay']['title']   = 'Оплачено';
	$stat['pay']['tooltip'] = 'Оплачено счетов за месяц';
	$stat['pay']['color']   = 'green';

}

$micro = '';
foreach ( $stat as $tip => $val ) {

	$url   = $dataid = '';
	$summa = ($val['summa'] != '') ? '<div class="gray2 fs-10 Bold mt15">'.$val['summa'].'</div>' : '';

	switch ($tip) {

		case 'health':
			$url    = ' onclick="window.location.hash=\'health\'"';
			$dataid = ' data-id="deals"';
		break;
		case 'todo':
			$url = ' onclick="window.location.hash=\'todo\'"';
		break;
		case 'leadscold':
			$url = ' onclick="goodlink(\'/leads\')"';
		break;
		case 'leads':
			$url = ' onclick="$(\'.info34\').empty().load(\'/content/vigets/viget.leads.pop.php?action=get_leads\').append(\'<div id=loader><img src=/assets/images/loading.svg></div> Погодь..Вроде чё-та прислали\')"';
		break;
		case 'credit':
			$url    = ' onclick="$(\'.info32\').empty().load(\'/content/vigets/viget.invoices.pop.php?tip=creditonly\').append(\'<div id=loader><img src=/assets/images/loading.svg></div> Кто тут у нас чего должен..\')"';
			$dataid = ' data-id="deals"';
		break;
		case 'invoices':
			$url    = ' onclick="$(\'.info31\').empty().load(\'/content/vigets/viget.invoices.pop.php?tip=invoiceonly\').append(\'<div id=loader><img src=/assets/images/loading.svg></div> Щас посчитаю и покажу..\')"';
			$dataid = ' data-id="deals"';
		break;
		case 'entry':
			$url = ' onclick="$(\'.info33\').empty().load(\'/content/vigets/viget.entry.pop.php?status=0\').append(\'<div id=loader><img src=/assets/images/loading.svg></div> Уже гружу..Вот ведь неугомонный :)\')"';
		break;
		case 'pay':
			$url    = ' onclick="getSwindow(\'/reports/ent-PaymentsByUser.php\', \'Оплаты по сотрудникам\')"';
			$dataid = ' data-id="deals"';
		break;
		case 'deals':
			$url    = ' onclick="getSwindow(\'/reports/ent-newDeals.php\', \'Новые сделки\')"';
			$dataid = ' data-id="deals"';
		break;
		default:
			$url = '';
		break;

	}

	$micro .= '
			<div class="flex-string swiper-slide viget-micro div-center pt15 hand pop popright" '.$url.' '.$dataid.'>
				<div class="gray fs-11 Bold uppercase">'.$val['title'].'</div>
				<div class="'.$val['color'].' fs-30 Bold pt10 mt10 tooltips" tooltip="'.$val['tooltip'].'" tooltip-position="top" tooltip-type="primary" data-id="viget-'.$tip.'">'.$val['count'].'</div>
				'.$summa.'
			</div>
		';

}
?>
<div class="flex-container pr10 mpr5 micro--container" data-step="70" data-intro="<h1>Информационные виджеты</h1>" data-position="bottom">

	<?php
	if ( !$isMobile ) {
		print $micro;
	}
	else {

		print '
			<div class="swiper-wrapper mb10 p5">'.$micro.'</div>
			<div class="swiper-pagination"></div>
		';

	}
	?>

</div>
<script>

	if (isMobile) {

		var mswiper = new Swiper('.micro--container', {
			/*slidesPerView: 1,*/
			spaceBetween: 10,
			pagination: {
				el: '.swiper-pagination'
			}
		});

	}

</script>