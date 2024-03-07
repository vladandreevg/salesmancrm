<?php
/* ============================ */
/*         SalesMan CRM         */
/* ============================ */
/* (C) 2016 Vladislav Andreev   */
/*       SalesMan Project       */
/*        www.isaler.ru         */
/*        ver. 2017.x           */

/* ============================ */

use Salesman\Currency;
use Salesman\Invoice;
use Salesman\Speka;

error_reporting( E_ERROR );
header( "Pragma: no-cache" );

$rootpath = dirname(__DIR__, 2);

include $rootpath."/inc/config.php";
include $rootpath."/inc/dbconnector.php";
include $rootpath."/inc/auth.php";
include $rootpath."/inc/func.php";
include $rootpath."/inc/settings.php";
include $rootpath."/inc/language/".$language.".php";

$thisfile = basename( __FILE__ );

$did    = (int)$_REQUEST['did'];
$crid   = (int)$_REQUEST['crid'];
$action = untag( $_REQUEST['action'] );

//Найдем тип сделки, которая является Сервисной
if ( isServices( (int)$did ) ) {
	$isper = 'yes';
}

//Проверим реквизит для счета, если включено выставление счетов
$result = $db -> getRow( "SELECT * FROM {$sqlname}dogovor WHERE did = '$did' and identity = '$identity'" );
$clid   = (int)$result["clid"];
$payer  = (int)$result["payer"];
$kol    = pre_format( $result["kol"] );
$mcid   = (int)$result["mcid"];

$deal = get_dog_info( $did, 'yes' );

$json  = get_client_recv( $payer );
$recvz = json_decode( $json, true );

if ( ($recvz['castName'] == '' || $recvz['castInn'] == '') && $otherSettings[ 'printInvoice'] ) {

	print '
	<div class="warning m0 mb10">
		<b class="red">Внимание!</b> Не заполнены реквизиты плательщика. 
		<a href="javascript:void(0)" onclick="editClient(\''.$payer.'\',\'change.recvisites\');" class="button0 Bold"><i class="icon-edit"></i>Заполнить?</a>
	</div>
	';

}

$invoices    = (new Invoice()) -> getCreditData( $did );
$nalogScheme = getNalogScheme( 0, (int)$deal['mcid'] );

//print_r($invoices);

$Speka = (new Speka()) -> getSpekaData( $did );
$acss  = get_accesse( 0, 0, $did );
$summa = 0;
$dvdr  = '&nbsp;<span class="gray">|</span>&nbsp;';

$icount   = count( $invoices );
$icountDo = (int)$db -> getOne( "SELECT COUNT(*) FROM {$sqlname}credit WHERE did = '$did' AND do = 'on' AND identity = '$identity'" );

if ( $icount == 0 ) {

	print '<div class="fcontainer mp10">Выставленных счетов нет</div>';
	goto ext;

}

$string = '';
$credit = $creditDo = [];
foreach ( $invoices as $data ) {

	$btns = [];
	$day  = '';

	$do = ($data['do'] == 'on') ? '<span class="green">да</span>' : '<span class="red">нет</span>';

	$day = $data['day'];

	if ( $day < 0 && $data['do'] != "on" ) {
		$day = '<div class="fs-14"><b class="red">'.$day.'</b> дн.</div><span class="fs-09 mt5">Просрочено</span>';
	}

	elseif ( $day >= 0 && $data['do'] != "on" ) {
		$day = '<div class="fs-14"><b class="green">+'.$day.' дн</b>.</div><span class="fs-09 mt5">Ожидается</span>';
	}

	else {
		$day = '';
	}

	$summa += pre_format( $data['summa'] );
	$datum = explode( " ", $data['dcreate'] );

	$nds_credit = $data['nds'];

	if ( $nds_credit <= 0 ) {

		$nds_credit = getNalog( $data['summa'], $nalogScheme['nalog'], $ndsRaschet );
		$nds_credit = $nds_credit['nalog'];

	}

	if ( $Speka['summaNalog'] == 0 && $Speka['summaInvoice'] > 0 ) {
		$nds_credit = 0;
	}

	//если это доплата по счету, то берем ID основного счета
	$crid = ($data['crid'] != $data['crid_main']) ? $data['crid_main'] : $data['crid'];

	/**
	 * Формируем кнопки
	 */
	if ( $data['do'] != "on" ) {

		if ( ($acss == "yes" || $tipuser == "Администратор" || $isadmin == "on") && $acs_credit == "on" ) {
			$btns[] = '<a href="javascript:void(0)" onclick="cf=confirm(\'Вы действительно хотите поставить отметку о поступлении платежа?\');if (cf)editCredit(\''.$data['crid'].'\',\'credit.doit\');" title="Отметить о поступлении оплаты" class="green"><i class="icon-ok"></i>Провести оплату</a>';
		}

		else {
			$btns[] = '<a href="javascript:void(0)" title="У вас нет достаточных прав. Отметить о поступлении оплаты" class="gray"><i class="icon-cancel-circled"></i>Провести оплату</a>';
		}

	}

	if ( $otherSettings[ 'price'] && $otherSettings[ 'printInvoice'] == 'yes' ) {

		$btns[] = '<a href="javascript:void(0)" onclick="editCredit(\''.$data['crid'].'\',\'invoice.mail\');" title="Отправить счет по Email"><i class="icon-mail broun"></i></a>';
		$btns[] = '<a href="javascript:void(0)" onclick="editCredit(\''.$data['crid'].'\',\'invoice.print\');" title="Получить счет"><i class="icon-print green"></i></a>';

	}

	if ( $acss == "yes" || $tipuser == "Администратор" || $isadmin == "on" ) {

		if ( $data['do'] != "on" ) {

			$btns[] = '<a href="javascript:void(0)" onclick="editCredit(\''.$data['crid'].'\',\'credit.edit\');" title="Изменить"><i class="icon-pencil blue"></i></a>';
			$btns[] = '<a href="javascript:void(0)" onclick="cf=confirm(\'Вы действительно хотите удалить платеж из графика?\');if (cf) editCredit(\''.$data['crid'].'\',\'credit.delete\');" title="Удалить платеж"><i class="icon-cancel-circled red"></i></a>';

		}
		elseif ( $tipuser == "Администратор" || $isadmin == "on" ) {

			$btns[] = '<a href="javascript:void(0)" onclick="editCredit(\''.$data['crid'].'\',\'credit.edit\');" title="Изменить"><i class="icon-pencil blue"></i></a>';

		}

	}

	if ( ($acss == "yes" || $tipuser == "Администратор" || $isadmin == "on") && $acs_credit == "on" && $data['do'] == "on" ) {

		$btns[] = '<a href="javascript:void(0)" onclick="cf=confirm(\'Вы действительно отменить платеж?\');if (cf) editCredit(\''.$data['crid'].'\',\'credit.undoit\');" title="Отменить платеж"><i class="icon-ccw blue"></i></a>';

	}
	elseif ( $data['do'] == "on" ) {

		$btns[] = '<a href="javascript:void(0)" title="У вас нет достаточных прав. Отменить платеж"><i class="icon-ccw gray"></i></a>';

	}

	$currency        = (new Currency) -> currencyInfo( $deal['idcurrency'] );
	$summaInCurrency = Currency ::currencyConvert( $data['summa'], $deal['idcourse'], true, true );

	$string = '
		<tbody class="focused box-shadow '.($data['do'] == "on" ? "gray" : "").'">
			<tr class="th40 graybg-sub">
				<td colspan="5" class="text-right">
					<div class="gray fs-09 pull-left">
						Квота по счету: <b class="blue">'.current_user( $data['iduser'] ).'</b>
					</div>
					<span class="hidden-iphone">'.yimplode( $dvdr, $btns ).'</span>
				</td>
			</tr>
			<tr class="th30 credit border-bottom1 mb5 bgwhite">
				<td class="wp20">
					<div class="fs-14 flh-14"><b>Счет №'.$data['invoice'].'</b></div>
					<div><span class="fs-09 mt5">от '.format_date_rus( $datum[0] ).'</span></div>
				</td>
				<td class="text-right">
					<div class="fs-14 flh-14"><b>'.num_format( $data['summa'] ).'</b></div>
					<div><span class="fs-09 mt5">НДС '.num_format( $nds_credit ).'</span></div>
					'.($deal['idcurrency'] > 0 ? '<div class="fs-11 flh-12">В валюте: <b class="blue">'.$summaInCurrency.'</b></div>' : '').'
				</td>
				<td class="text-right wp20">
					'.($data['do'] != "on" ? '<div class="fs-14 flh-14"><b class="red">'.format_date_rus( $data['dplan'] ).'</b></div><div><span class="fs-09 mt5">Ожидаемая дата</span></div>' : '').'
					'.($data['do'] == "on" ? '<div class="fs-14 flh-14"><b class="green">Оплачен</b></div><div><span class="fs-09 mt5">'.format_date_rus( $data['dfact'] ).'</span></div>' : '').'
				</td>
				<td class="text-right wp15">'.$day.'</td>
				<td class="text-right wp20">
					<div class="fs-14 flh-14 Bold">
						'.($data['contract'] != '' ? $data['contract'] : 'Без договора').'
					</div>
					<div class="fs-09 mt5">Договор</div>
				</td>
				<td class="visible-iphone">
					<div class="mob-pull-right pr5 border-box">
						'.yimplode( "&nbsp;", $btns ).'
					</div>
				</td>
			</tr>
		</tbody>
		<tr class="th5 transparent hidden-iphone">
			<td colspan="5"></td>
		</tr>';

	if ( (empty( $credit ) && ($icount - $icountDo) == 0) || //если это НЕ сервисная сделка и число оплаченных счетов меньше 2-х
		(empty( $credit ) && $icountDo <= 2 && $isper != 'yes') || //если счет не оплачен
		$data['do'] != 'on' ) {
		$credit[] = $string;
	}

	else {
		$creditDo[] = $string;
	}

	$nds_credit = 0;

}

$xdelta = (float)pre_format( $kol ) - (float)pre_format( $summa );
$delta = num_format( $xdelta );

/**
 * Выводим счета
 */
print '
<div class="fcontainer1">

	<table id="" class="credit mrowtable nomob">
		'.yimplode( "", $credit ).'
	</table>
	
	'.(!empty( $creditDo ) ? '
	<div class="">
	
		<div class="togglerbox Bold fs-12 blue p10 hand graybg-sub" data-id="creditDo">
			<i class="icon-angle-down" id="mapic"></i> ещё оплаченные счета
			<div class="pull-aright">'.count( $creditDo ).' счета</div>
		</div>
		
		<div class="pt10 hidden" id="creditDo">
		
			<table id="" class="credit mrowtable nomob">
				'.yimplode( "", $creditDo ).'
			</table>
			
		</div>
		
	</div>
	' : '').'
	
	'.($xdelta != 0 && $close != 'yes' ? '
		<div class="warning m0 mt10 text-left">
	
			<span><i class="icon-attention red icon-3x pull-left"></i></span>
			<b class="red">Внимание:</b><br>
			Сумма в графике отличается от плановой суммы сделки на
			<b>'.$delta.'</b> '.$valuta.'<br>
			<b>Откорректируйте график платежей!</b>
	
		</div>' : '').'
		
</div>';

ext:

/**
 * Выводим блок кнопок, если оплата не на всю спеку или для сервисных сделок
 */
if ( (pre_format( $kol ) != pre_format( $summa ) && $close != 'yes') || $isper == 'yes' ) {
	print '
	<hr>
	<div class="mp10">
		<a href="javascript:void(0)" onclick="editCredit(\''.$did.'\',\'credit.add\');" class="button"><i class="icon-plus-circled-1"></i>Добавить счет</a>&nbsp;
		'.($isper != 'yes' ? '<a href="javascript:void(0)" onclick="editCredit(\''.$did.'\',\'credit.express\');" class="button orangebtn"><i class="icon-ok-circled"></i>Внести оплату</a>' : '').'
	</div>
	';
}

else {
	print '
	<div class="attention m0 mt10">Выставлены все возможные счета по сделке</div>
	<script>
		$(\'.button-credit-add\').addClass(\'hidden\');
	</script>
	';
}