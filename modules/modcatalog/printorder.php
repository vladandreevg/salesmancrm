<?php
/* ============================ */
/*         SalesMan CRM         */
/* ============================ */
/* (C) 2016 Vladislav Andreev   */
/*       SalesMan Project       */
/*        www.isaler.ru         */
/*        ver. 2016.20          */
/* ============================ */
?>
<?php
error_reporting(E_ERROR);

$rootpath = realpath( __DIR__.'/../../' );

include $rootpath."/inc/config.php";
include $rootpath."/inc/dbconnector.php";
include $rootpath."/inc/auth.php";
include $rootpath."/inc/func.php";
include $rootpath."/inc/settings.php";
include $rootpath."/inc/language/".$language.".php";

$id = $_REQUEST['id'];
$tip = $_REQUEST['tip'];

$settings = $db -> getOne("SELECT settings FROM ".$sqlname."modcatalog_set WHERE identity = '$identity'");
$settings = json_decode($settings, true);
$settings['mcSklad'] = 'yes';

if($tip == 'order'){

	$result = $db -> getRow("SELECT * FROM ".$sqlname."modcatalog_akt WHERE id = '$id' and identity = '$identity'");
	$clid = $result['clid'];
	$posid = $result['posid'];
	$man1 = $result['man1'];
	$man2 = $result['man2'];
	$datum = $result['datum'];
	$did = $result['did'];
	$tip = $result['tip'];
	$isdo = $result['isdo'];
	$order_num = $result['number'];
	$cFactura = $result['cFactura']; if($cFactura == '') $cFactura = '--';
	$cDate    = $result['cDate']; if($cDate == '0000-00-00') $cDate = '--'; else $cDate = format_date_rus_name_akt($cDate);

	if($order_num < 1) $order_num = '-/-';

	$name_ur = $db -> getOne("SELECT name_ur FROM ".$sqlname."mycomps WHERE id='".$mcDefault."' and identity = '$identity'");

	if($tip == 'income'){
		$prinal = $man2;
		$sdal = $man1;
		$tip2 = "Приходный ордер";

		$datac = json_decode(get_client_recv($posid), true);
		$html = file_get_contents('res/order.htm');
	}
	elseif($tip == 'outcome'){
		$prinal = $man1;
		$sdal = $man2;
		$tip2 = "Расходный ордер";

		$datac = json_decode(get_client_recv($clid), true);
		$html = file_get_contents('res/rorder.htm');
	}


	$i = 1; $stroka = ''; $samma_all = 0; $kol_all = 0;

	$result = $db -> getAll("SELECT * FROM ".$sqlname."modcatalog_aktpoz WHERE ida = '$id' and identity = '$identity'");
	foreach($result as $data) {

		$eprice = $db -> getRow("SELECT * FROM ".$sqlname."price WHERE n_id = '$data[prid]' and identity = '$identity'");
		$title = $eprice['title'];
		$edizm = $eprice['edizm'];

		if($tip == 'outcome') $price = $db -> getOne("SELECT price FROM ".$sqlname."speca WHERE did = '$did' and prid = '$data[prid]' and identity = '$identity'");
		else{

			if($eprice['price_in'] == 0) $price = $db -> getOne("SELECT price_in FROM ".$sqlname."price WHERE n_id = '$data[prid]' and identity = '$identity'");
			else $price = $eprice['price_in'];

		}

		//для приходных - ввод
		//для расходных - спецификация

		$summa = $price * $data['kol'];

		$samma_all = $samma_all + $summa;
		$kol_all = $kol_all + $data['kol'];

		$stroka.= '
		<tr class="small">
			<td align="left" class="bt br bb bl">'.$i.'</td>
			<td class="bb br" align="left">'.$title.'</td>
			<td align="right" class="bb br">'.num_format($data['kol']).'</td>
			<td align="center" class="bb br">'.$edizm.'</td>
			<td align="right" class="bb br" nowrap>'.num_format($price).'</td>
			<td align="right" class="bb br">'.num_format($summa).'</td>
		</tr>
		';

		//вывод серийников для поштучного учета
		if($settings['mcSkladPoz'] == 'yes'){

			$j = 1;

			if($tip == 'income') $q = "SELECT * FROM ".$sqlname."modcatalog_skladpoz WHERE prid = '".$data['prid']."' and idorder_in = '".$id."' and identity = '$identity'";
			else $q = "SELECT * FROM ".$sqlname."modcatalog_skladpoz WHERE did = '$did' and prid = '".$data['prid']."' and idorder_out = '".$id."' and identity = '$identity'";

			$re = $db -> getAll($q);

			foreach($re as $da) {

				$stroka.= '
				<tr class="small">
					<td align="left" class="bt br bb bl">'.$i.'.'.$j.'</td>
					<td class="bb br" align="left"><em>'.$da['serial'].'</em></td>
					<td align="right" class="bb br"><em>'.num_format($da['kol']).'</em></td>
					<td align="center" class="bb br">-</td>
					<td align="right" class="bb br">-</td>
					<td align="right" class="bb br">-</td>
				</tr>
				';

				$j++;

			}

		}

		$i++;
	}

	$datum = explode(" ", $datum);
	$order_date = format_date_rus($datum[0]);
	$summa_propis = mb_ucfirst(trim(num2str((float)$samma_all)));

	$html = str_replace("{tip}",$tip2,$html);
	$html = str_replace("{order_num}",$order_num,$html);
	$html = str_replace("{order_date}",$order_date,$html);
	$html = str_replace("{stroka}",$stroka,$html);
	$html = str_replace("{samma_all}",num_format($samma_all),$html);
	$html = str_replace("{kol_all}",$kol_all,$html);
	$html = str_replace("{prinal}",$prinal,$html);
	$html = str_replace("{sdal}",$sdal,$html);
	$html = str_replace("{castUrName}",$datac['castUrName'],$html);
	$html = str_replace("{castInn}",$datac['castInn'],$html);
	$html = str_replace("{castKpp}",$datac['castKpp'],$html);
	$html = str_replace("{name_ur}",$name_ur,$html);
	$html = str_replace("{summa_propis}",$summa_propis,$html);
	$html = str_replace("{cFactura}",$cFactura,$html);
	$html = str_replace("{cDate}",$cDate,$html);
}

print $html;
?>