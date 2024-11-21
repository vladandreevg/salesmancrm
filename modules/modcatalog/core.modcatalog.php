<?php
/* ============================ */

/*         SalesMan CRM         */
/* ============================ */
/* (C) 2016 Vladislav Andreev   */
/*       SalesMan Project       */
/*        www.isaler.ru         */
/*        ver. 2016.20          */

/* ============================ */

use Salesman\Storage;

error_reporting(E_ERROR);
ini_set('display_errors', 1);
header("Pragma: no-cache");

$rootpath = dirname(__DIR__, 2);

include $rootpath."/inc/config.php";
include $rootpath."/inc/dbconnector.php";
include $rootpath."/inc/auth.php";
include $rootpath."/inc/func.php";
include $rootpath."/inc/settings.php";
include $rootpath."/inc/language/".$language.".php";

$thisfile = basename(__FILE__);

//include "mcfunc.php";

//настройки модуля
$settings            = $db -> getOne("SELECT settings FROM {$sqlname}modcatalog_set WHERE identity = '$identity'");
$settings            = json_decode($settings, true);
$settings['mcSklad'] = 'yes';

//
//prid в таблице modcatalog - это ссылка на id в прайсе (n_id)
//prid в остальных таблицах - это id записи в таблице modcatalog
//

if ($settings['mcSkladPoz'] != "yes") {
	$pozzi = " and status != 'out'";
}

$n_id   = $_REQUEST['n_id'];
$action = $_REQUEST['action'];

//createDir($rootpath."/cash/modcatalog");

$tabsOn = 'no';

$dname  = [];
$fields = [];
$result = $db -> getAll("SELECT * FROM {$sqlname}field WHERE fld_tip='price' AND fld_on='yes' and identity = '$identity' ORDER BY fld_order");
foreach ($result as $data) {

	$dname[$data['fld_name']] = $data['fld_title'];
	$dvar[$data['fld_name']]  = $data['fld_var'];
	$don[]                    = $data['fld_name'];

	if($data['fld_name'] != 'price_in' && $data['fld_on'] == 'yes') {

		$fields[] = [
			"field" => $data['fld_name'],
			"title" => $data['fld_title'],
			"value" => $data['fld_var'],
		];

	}

}

function mclogger($tip, $param = [], $oldparams = []): string {

	global $logerror;
	global $iduser1;

	$db       = $GLOBALS['db'];
	$sqlname  = $GLOBALS['sqlname'];
	$identity = $GLOBALS['identity'];

	$old    = '';
	$new    = '';
	$filter = [
		'action',
		'id',
		'artikul',
		'title',
		'descr',
		'contentt',
		'edizm',
		'nds',
		'idcat',
		'price_in',
		'price_1',
		'price_2',
		'file',
		'idcategory',
	];

	$fields = $db -> getCol("SELECT fld_name FROM {$sqlname}field WHERE fld_tip='price' AND fld_on='yes' and identity = '$identity' ORDER BY fld_order");

	if (in_array('price_3', $fields)) {
		$filter[] = 'price_3';
	}
	if (in_array('price_4', $fields)) {
		$filter[] = 'price_4';
	}
	if (in_array('price_5', $fields)) {
		$filter[] = 'price_5';
	}

	if (!empty($oldparams)) {
		$old = json_encode_cyr($oldparams);
	}

	$new = json_encode_cyr($param);

	//print_r($oldparams);
	//print_r($param);

	$diff = array_diff_ext($param, $oldparams);

	$ndiff = [];
	foreach ($diff as $key => $val) {
		if (in_array($key, $filter)) {
			$ndiff[$key] = $val;
		}
	}

	//print_r($ndiff);

	if (!empty($ndiff)) {

		try {
			//$db -> query( "INSERT INTO {$sqlname}modcatalog_log (id,tip,dopzid,prid,datum,new,old,iduser,identity) value (null,'$tip','".$param['dopzid']."','".$param['prid']."','".current_datumtime()."','$new','$old','".$GLOBALS['iduser1']."','".$identity."')" );

			$db -> query("INSERT INTO {$sqlname}modcatalog_log SET ?u", [
				"tip"      => $tip,
				"dopzid"   => $param['dopzid'],
				"prid"     => $param['prid'],
				"datum"    => current_datumtime(),
				"new"      => $new,
				"old"      => $old,
				"iduser"   => $iduser1,
				"identity" => $identity,
			]);
		}
		catch (Exception $e) {
			$logerror = 'Ошибка'.$e -> getMessage().' в строке '.$e -> getCode();
		}

		$logerror = 'Событие добавлено в Историю<br>';

	}
	else {
		$logerror = 'Не обнаружено изменений<br>';
	}

	return $logerror;
}

function convertcsv($string) {
	//$string = iconv("UTF-8","CP1251", $string);
	return $string;
}

function clnall($ostring) {
	$string = trim($ostring);

	$string = str_replace([
		"\n\r",
		"\n",
		"<br><br>",
		"&nbsp;",
	], [
		"<br>",
		"<br>",
		"<br>",
		" ",
	], $string);

	return $string;
}

$statear = [
	0 => 'Продан',
	1 => 'Под заказ',
	2 => 'Ожидается',
	3 => 'В наличии',
	4 => 'Резерв',
];

if ($action == "removereserv") {
	$params = $_REQUEST;

	$sklad  = new Storage();
	$result = $sklad -> removereserve($params);

	$mes = $result['result'];

	print $mes;

	exit();
}

if ($action == "removezayavka") {
	$params = $_REQUEST;

	$sklad  = new Storage();
	$result = $sklad -> removezayavka($params);

	$mes = $result['result'];

	print $mes;

	exit();
}

if ($action == "removeorder") {
	$params = $_REQUEST;

	$sklad  = new Storage();
	$result = $sklad -> removeorder($params);

	$mes = $result['result'];

	print $mes;

	exit();
}

if ($action == "likeoffer") {
	$id = $_REQUEST['id'];

	//прочитаем, кто уже лайкнул
	$users = $db -> getOne("select users from {$sqlname}modcatalog_offer where id='".$id."' and identity = '$identity'");
	$users = json_decode($users, true);

	$users[] = $iduser1;

	$nusers = json_encode_cyr($users);

	$db -> query("update {$sqlname}modcatalog_offer set users = '".$nusers."' WHERE id = '".$id."' and identity = '$identity'");
	print "Готово";

	exit();
}

if ($action == "delete") {
	$params = $_REQUEST;

	$sklad  = new Storage();
	$result = $sklad -> delete($params);

	$mes = $result['result'];

	print $mes;

	exit();
}

if ($action == "deleteskladpoz") {
	$params = $_REQUEST;

	$sklad  = new Storage();
	$result = $sklad -> deletepoz($params);

	$mes = $result['result'];

	print $mes;

	exit();
}

if ($action == "didlist") {

	$clid = $_REQUEST['clid'];
	$ida  = $_REQUEST['ida'];
	$dids = [];

	if ($_REQUEST['t'] == 'order') {
		$t = " and tip = 'outcome'";
	}
	if ($_REQUEST['t'] == 'zayavka') {
		$z = " and did NOT IN (SELECT did FROM {$sqlname}modcatalog_zayavka WHERE identity = '$identity')";
	}

	$dids = $db -> getCol("SELECT did FROM {$sqlname}modcatalog_akt where did > 0 $t and isdo != 'yes' and identity = '$identity' ORDER BY datum DESC");

	if ($dids[0] > 0) {
		$dids = "and did NOT IN (".implode(",", $dids).")";
	}
	else {
		$dids = '';
	}

	print '<option value="">--Выбор--</option>';

	//print "SELECT * FROM {$sqlname}dogovor WHERE clid = '$clid' $dids $z and close != 'yes' and identity = '$identity'";

	$result = $db -> getAll("SELECT * FROM {$sqlname}dogovor WHERE clid = '$clid' $dids $z and close != 'yes' and identity = '$identity'");
	foreach ($result as $data) {
		if ($_REQUEST['t'] == 'order') {
			//количество позиций в актах
			$acount = $db -> getOne("SELECT SUM(kol) as count FROM {$sqlname}modcatalog_aktpoz WHERE ida IN (SELECT id FROM {$sqlname}modcatalog_akt WHERE did = '".$data['did']."' and tip = 'outcome' and identity = '$identity') and identity = '$identity'");

			//количество позиций в спеке
			$scount = $db -> getOne("SELECT SUM(kol) as count FROM {$sqlname}speca WHERE did = '".$data['did']."' and prid > 0 and identity = '$identity'");

			if ($acount < $scount) {
				$ap = 1;
			}
			else {
				$ap = 0;
			}
		}
		else {
			$ap = 1;
		}

		if ($ap == 1) {
			print '<option value="'.$data['did'].'">'.$data['title'].' - '.num_format($data['kol']).'</option>';
		}
	}

	exit();

}
if ($action == "deallist") {

	$clid = $_REQUEST['clid'];
	$ida  = $_REQUEST['ida'];
	$dids = [];

	$string = [];

	if ($_REQUEST['t'] == 'order') {
		$t = " and tip = 'outcome'";
	}
	//if($_REQUEST['t'] == 'zayavka') $z = " and did NOT IN (SELECT did FROM {$sqlname}modcatalog_zayavka WHERE identity = '$identity')";

	$dids = $db -> getCol("SELECT did FROM {$sqlname}modcatalog_akt where did > 0 $t and isdo != 'yes' and identity = '$identity' ORDER BY datum DESC");

	if ($dids[0] > 0) {
		$dids = "and did NOT IN (".implode(",", $dids).")";
	}
	else {
		$dids = '';
	}

	$result = $db -> getAll("SELECT * FROM {$sqlname}dogovor WHERE clid = '$clid' $dids $z and close != 'yes' and identity = '$identity'");
	foreach ($result as $data) {

		if ($_REQUEST['t'] == 'order') {
			//количество позиций в актах
			$acount = $db -> getOne("SELECT SUM(kol) as count FROM {$sqlname}modcatalog_aktpoz WHERE ida IN (SELECT id FROM {$sqlname}modcatalog_akt WHERE did = '".$data['did']."' and tip = 'outcome' and identity = '$identity') and identity = '$identity'");

			//количество позиций в спеке
			$scount = $db -> getOne("SELECT SUM(kol) as count FROM {$sqlname}speca WHERE did = '".$data['did']."' and prid > 0 and identity = '$identity'");

			if ($acount < $scount) {
				$ap = 1;
			}
			else {
				$ap = 0;
			}
		}
		else {
			$ap = 1;
		}

		if ($ap == 1) {
			$string[] = [
				"did"   => $data['did'],
				"title" => $data['title'],
				"summa" => num_format($data['kol']),
			];
		}
	}

	print json_encode_cyr($string);

	exit();
}

if ($action == "specalist") {

	$did = $_REQUEST['did'];
	$i   = $_REQUEST['count'];

	$result = $db -> getAll("SELECT * FROM {$sqlname}speca WHERE did = '$did' and identity = '$identity'");
	foreach ($result as $data) {
		$res      = $db -> getRow("SELECT * FROM {$sqlname}price WHERE title = '".$data['title']."' and identity = '$identity'");
		$prid     = $res['n_id'];
		$price_in = $res['price_in'];

		$kol_in = $db -> getOne("SELECT SUM(kol) as kol FROM {$sqlname}modcatalog WHERE prid = '".$prid."' and identity = '$identity'");

		$kol_res = $db -> getOne("SELECT SUM(kol) as kol FROM {$sqlname}modcatalog_reserv WHERE prid = '".$data['prid']."' and did = '$did' and identity = '$identity'");

		$kol_in += $kol_res;

		if ($kol_in < (int)$data['kol']) {
			$ss = 'border:1px solid red';
			$tt = '<span class="red smalltxt">Нет достаточного количества на складе. На складе - <b>'.num_format($kol_in).'</b> позиций.</span>';
		}
		else {
			$ss = '';
			$tt = '';
		}

		print '
		<tr id="pr_">
			<td><input name="idp[]" id="idp[]" type="hidden" value=""><input name="prid[]" id=prid[]" type="hidden" value="'.$prid.'"><input name="speca_title[]" type="text" id="speca_title[]" value="'.$data['title'].'" style="width:98%" class="requered"/>'.$tt.'</td>
			<td class="text-center"><input name="speca_kol[]" id="speca_kol[]" type="text" value="'.num_format($data['kol']).'" style="width:70%; '.$ss.'" class="requered"/></td>
			<td class="text-center"><input name="speca_price[]" id="speca_price[]" type="text" min="1" value="'.num_format($data['price']).'" style="width:90%" class="requered"/></td>
			<td class="text-center"></td>
		</tr>';
		$i++;
	}

	exit();
}

//для расходных ордеров
if ($action == "specalist2") {

	$did   = $_REQUEST['did'];
	$i     = $_REQUEST['count'];
	$sklad = $_REQUEST['sklad'];
	$tip   = $_REQUEST['t'];

	$speca    = [];
	$specaPre = [];

	//для приходных ордеров убираем уже заказанные позиции
	/*
	if ( $tip != 'outcome' )
		$d = " and prid NOT IN (SELECT prid FROM {$sqlname}modcatalog_zayavkapoz WHERE did = '$did' and identity = '$identity')";
	else
		$d = " and prid NOT IN (SELECT {$sqlname}modcatalog_aktpoz.prid FROM {$sqlname}modcatalog_aktpoz LEFT JOIN {$sqlname}modcatalog_akt ON {$sqlname}modcatalog_akt.id = {$sqlname}modcatalog_aktpoz.ida WHERE {$sqlname}modcatalog_akt.tip = 'outcome' and {$sqlname}modcatalog_akt.did = '$did' and {$sqlname}modcatalog_aktpoz.identity = '$identity')";

	$result = $db -> getAll( "SELECT * FROM {$sqlname}speca WHERE did = '$did' $d and identity = '$identity'" );
	*/

	if ($sklad != '') {
		$s = "sklad = '$sklad'";
	}

	//для приходных ордеров убираем уже заказанные позиции
	/*
	$filter = ( $tip != 'outcome' ) ? "
		and {$sqlname}speca.prid NOT IN (SELECT prid FROM {$sqlname}modcatalog_zayavkapoz WHERE did = '$did' and identity = '$identity')" : "
		and {$sqlname}speca.kol > COALESCE((
			SELECT SUM({$sqlname}modcatalog_aktpoz.kol) 
			FROM {$sqlname}modcatalog_aktpoz 
				LEFT JOIN {$sqlname}modcatalog_akt ON {$sqlname}modcatalog_akt.id = {$sqlname}modcatalog_aktpoz.ida 
			WHERE 
				{$sqlname}modcatalog_akt.tip = 'outcome' and 
				{$sqlname}modcatalog_akt.did = '$did' and 
				{$sqlname}modcatalog_aktpoz.identity = '$identity'
			), 0)
		";
	*/

	$filter = ( $tip != 'outcome' ) ? " and {$sqlname}speca.prid NOT IN (SELECT prid FROM {$sqlname}modcatalog_zayavkapoz WHERE did = '$did' and identity = '$identity')" : "";
	$result = Storage ::totalSpeka($did, $filter);

	//print_r($result);

	foreach ($result as $data) {
		$countKol = $db -> getOne("
			SELECT SUM(kol) 
			FROM {$sqlname}modcatalog_aktpoz 
				LEFT JOIN {$sqlname}modcatalog_akt ON {$sqlname}modcatalog_akt.id = {$sqlname}modcatalog_aktpoz.ida
			WHERE 
				{$sqlname}modcatalog_aktpoz.prid = '$data[prid]' AND
				{$sqlname}modcatalog_akt.tip = 'outcome' AND 
				{$sqlname}modcatalog_akt.did = '$did' AND 
				{$sqlname}modcatalog_aktpoz.identity = '$identity'
			");

		if (( $data['kol'] - $countKol ) > 0) {
			$kol_sklad = 0;
			$acsept    = 'yes';
			$ss        = '';
			$tt        = '';

			$price_in = $db -> getOne("SELECT price_in FROM {$sqlname}price WHERE n_id = '".$data['prid']."' and identity = '$identity'");

			//количество на складе
			if ($sklad > 0) {
				$kol_sklad = $db -> getOne("SELECT SUM(kol) as kol FROM {$sqlname}modcatalog_skladpoz WHERE sklad = '$sklad' and prid = '".$data['prid']."' $pozzi and identity = '$identity'") + 0;
			}

			//количество в резерве под сделку
			$kol_res = $db -> getOne("SELECT SUM(kol) as kol FROM {$sqlname}modcatalog_reserv WHERE prid = '".$data['prid']."' and did = '$did' and identity = '$identity'") + 0;

			//количество в резерве всего
			$kol_resother = $db -> getOne("SELECT SUM(kol) as kol FROM {$sqlname}modcatalog_reserv WHERE prid = '".$data['prid']."' and did != '$did' and identity = '$identity'") + 0;

			//свободно на складе с учетом резервов (нужен для вывода позиций не резервированных под сделку)
			$kol_free  = $kol_sklad - $kol_res - $kol_resother;
			$kol_free2 = $kol_sklad - $kol_resother;

			//print $kol_sklad." :: ".$kol_res." :: ".$kol_resother."<br>";
			//print $kol_free." :: ".$kol_free2."<br><br>";

			if ($kol_free < $data['kol']) {
				$ss = 'border:1px solid red';
				$tt = '<span class="red smalltxt">На складе - <b>'.$kol_sklad.'</b> позиций (В резерве под сделку - '.$kol_res.', В резерве: - '.$kol_res.').</span>';

				if ($tip == 'outcome' && $kol_free2 <= 0) {
					$acsept = 'no';
				}
			}

			//для поштучного учета надо передать список серийников дл отгрузки

			$countDeal = $db -> getAll("SELECT SUM(kol) as kol FROM {$sqlname}modcatalog_skladpoz WHERE did = '$did' and identity = '$identity'");

			$apdx = [];

			if ($tip == 'outcome' && $sklad != '') {
				if ($settings['mcSkladPoz'] == 'yes') {
					$dx = ( $countDeal > 0 ) ? " and (did = '".$did."' or did is null or did = 0)" : " and did is null";

					$re = $db -> getAll("SELECT * FROM {$sqlname}modcatalog_skladpoz WHERE sklad = '$sklad' and prid = '".$data['prid']."' $dx and identity = '$identity'");//and serial != ''
					foreach ($re as $da) {
						$selected = '';

						if ($da['did'] == $did) {
							$selected = 'selected';
						}
						if ($da['serial'] == NULL) {
							$da['serial'] = 'б/н';
						}

						$apdx[] = [
							"id"       => $da['id'],
							"serial"   => $da['serial'],
							"selected" => $selected,
						];
					}
				}
			}
			else {
				goto next;
			}

			$k = 1;

			if ($tip == 'income') {
				if ($kol_res < $data['kol']) {
					$k = 0;
				}
			}
			elseif ($tip == 'outcome') {
				if ($kol_free2 < $data['kol'] && $kol_res < $data['kol']) {
					$k = 0;
				}

				if ($kol_res > 0) {
					$data['kol'] = $kol_res;
					$k           = 1;
				}
			}

			if ($data['prid'] > 0 && $kol_sklad > 0 && $k == 1) {
				$speca[] = [
					"prid"         => $data['prid'],
					"title"        => $data['title'],
					"kol"          => num_format($data['kol']),
					"kol_free"     => $kol_free,
					"kol_free2"    => $kol_free2,
					"skld"         => "skald = $kol_sklad; rez = $kol_res; other = $kol_resother",
					"kol_in"       => $kol_sklad,
					"kol_res"      => $kol_res,
					"kol_resother" => $kol_resother,
					"price"        => num_format($data['price']),
					"apdx"         => $apdx,
				];
			}

			next:

			$i++;
		}
	}

	$mcid = getDogData($did, 'mcid');
	$clid = getDogData($did, 'clid');

	print $speca = json_encode_cyr([
		"speca" => $speca,
		"clid"  => $clid,
		"did"   => $did,
		"mcid"  => $mcid,
	]);

	exit();
}

//для приходных ордеров
if ($action == "specalist3") {
	$did   = $_REQUEST['did'];
	$idz   = $_REQUEST['idz'];
	$i     = $_REQUEST['count'];
	$sklad = $_REQUEST['sklad'];

	$speca = [];
	$mcid  = 0;

	if ($did > 0 && $idz < 1) {
		$idz = $db -> getOne("SELECT id FROM {$sqlname}modcatalog_zayavka WHERE did = '$did' and identity = '$identity'");
	}

	$c     = $db -> getRow("SELECT did, conid FROM {$sqlname}modcatalog_zayavka WHERE id = '$idz' and identity = '$identity'");
	$conid = $c['conid'];
	if ($idz > 0) {
		$did = $c['did'];
	}

	//если привязка к сделке есть, то получаем mcid - id компании, привязанной к сделке, чтобы сделать не доступными склады, привязанные к другим компаниям
	if ($did > 0) {
		$mcid = getDogData($did, 'mcid');
		$clid = getDogData($did, 'clid');
	}

	//формируем запрос
	if ($did > 0 && $idz < 1) {
		$sort = ( count($settings['mcPriceCat']) > 0 ) ? " and {$sqlname}price.pr_cat IN (".yimplode(",", $settings['mcPriceCat']).")" : '';

		$result = Storage ::totalSpeka($did, $sort);
		/*
		$q = "
			SELECT
				{$sqlname}speca.prid,
				 {$sqlname}speca.title,
				 {$sqlname}speca.kol,
				 {$sqlname}speca.price,
				 {$sqlname}price.pr_cat
			FROM {$sqlname}speca
				LEFT JOIN {$sqlname}price ON {$sqlname}speca.prid = {$sqlname}price.n_id
			WHERE
				{$sqlname}speca.did = '$did' and
				$sort
				{$sqlname}speca.identity = '$identity'
		";
		$result = $db -> getAll( $q );
		*/
	}
	elseif ($idz > 0) {
		$q      = "SELECT * FROM {$sqlname}modcatalog_zayavkapoz WHERE id > 0 and idz = '$idz' and identity = '$identity'";
		$result = $db -> getAll($q);
		//print_r($result);

	}

	foreach ($result as $data) {
		$res      = $db -> getRow("SELECT * FROM {$sqlname}price WHERE n_id = '".$data['prid']."' and identity = '$identity'");
		$price_in = $res['price_in'];
		$price    = $res['price_1'];
		$title    = $res['title'];

		//количество на складе при обычном учете
		/*if($settings['mcSkladPoz'] != 'yes')*/
		$kol_in = $db -> getOne("SELECT SUM(kol) as kol FROM {$sqlname}modcatalog_skladpoz WHERE sklad = '$sklad' $pozzi and prid = '".$data['prid']."' and identity = '$identity'");

		//количество на складе при поштучном учете
		//else $kol_in = $db -> getOne("SELECT COUNT(*) as count FROM {$sqlname}modcatalog_skladpoz WHERE prid = '".$data['prid']."' $pozzi and sklad = '".$sklad."' and identity = '$identity'");

		//Количество в резерве
		$kol_res = $db -> getOne("SELECT SUM(kol) as kol FROM {$sqlname}modcatalog_reserv WHERE prid = '".$data['prid']."' and did = '$did' and identity = '$identity'");

		//количество, в приходных ордерах по текущей заявке
		$kol_do = ( $idz > 0 ) ? $db -> getOne("SELECT SUM(kol) as kol FROM {$sqlname}modcatalog_aktpoz WHERE prid = '".$data['prid']."' and ida IN (SELECT id FROM {$sqlname}modcatalog_akt WHERE idz = '".$idz."' and identity = '$identity') and identity = '$identity'") : 0;

		//количество в заявке
		$kol_zay = ( $idz > 0 ) ? $db -> getOne("SELECT SUM(kol) as kol FROM {$sqlname}modcatalog_zayavkapoz WHERE prid = '".$data['prid']."' and idz = '$idz' and identity = '$identity'") : 0;

		//вычисляем количество позиции, которое еще не находится в расходниках
		$delta_z = $kol_zay - $kol_do;// - $kol_res;

		//print $kol_zay." :: ".$kol_do." :: ".$kol_res."\n";

		$kol_in = $kol_in + $kol_res;

		if ($data['prid'] > 0 && $delta_z > 0) {
			$speca[] = [
				"idp"    => "0",
				"prid"   => $data['prid'],
				"title"  => $title,
				"kol"    => num_format($delta_z),
				"kol_in" => $kol_in,
				"price"  => num_format($price_in),
			];
		}

		/*if ( $data['prid'] > 0 && $delta_z >= 0 )
			$speca[] = [
				"idp"    => "0",
				"prid"   => $data['prid'],
				"title"  => $title,
				"kol"    => num_format( $delta_z ),
				"kol_in" => $kol_in,
				"price"  => num_format( $price_in )
			];*/

		$i++;
	}


	print $speca = json_encode_cyr([
		"speca" => $speca,
		"conid" => $conid,
		"clid"  => $clid,
		"did"   => $did,
		"mcid"  => $mcid,
	]);

	exit();
}
if ($action == "specalist4") {
	$did = $_REQUEST['did'];
	$i   = $_REQUEST['count'];
	$idz = $_REQUEST['idz'];

	$speca = [];
	$sort  = '';

	//для заявок убираем уже заказанные позиции
	//if($_REQUEST['t'] != 'outcome') $d = " and prid NOT IN (SELECT prid FROM {$sqlname}modcatalog_zayavkapoz WHERE did = '$did' and identity = '$identity')";
	//else $d = " and prid NOT IN (SELECT prid FROM {$sqlname}modcatalog_akt WHERE tip = 'outcome' and did = '".$did."' and identity = '$identity')";

	if ($did > 0) {
		$pozzi = ( $settings['mcSkladPoz'] != "yes" ) ? " and status != 'out'" : "";

		$skladForDeal = Storage ::getSkladList(getDogData($did, 'mcid'));
		$sklads       = array_keys($skladForDeal);

		/*
		if ( count( $settings['mcPriceCat'] ) > 0 )
			$sort .= $sqlname."price.pr_cat IN (".yimplode( ",", $settings['mcPriceCat'] ).") and ";

		$query = "
			SELECT
				{$sqlname}speca.prid,
				 {$sqlname}speca.title,
				 {$sqlname}speca.kol,
				 {$sqlname}speca.price,
				 {$sqlname}speca.price_in,
				 {$sqlname}price_cat.idcategory
			FROM {$sqlname}speca
				LEFT JOIN {$sqlname}price ON {$sqlname}speca.prid = {$sqlname}price.n_id
				LEFT JOIN {$sqlname}price_cat ON {$sqlname}price.pr_cat = {$sqlname}price_cat.idcategory
			WHERE
				{$sqlname}speca.did = '$did' and
				$sort
				{$sqlname}speca.identity = '$identity'
		";
		$result = $db -> getAll( $query );
		*/

		$sort   = ( count($settings['mcPriceCat']) > 0 ) ? " and {$sqlname}price.pr_cat IN (".yimplode(",", $settings['mcPriceCat']).") " : "";
		$result = Storage ::totalSpeka($did, $sort);

		foreach ($result as $data) {
			$ss = '';
			$tt = '';

			//цена по прайсу
			$price_in = $db -> getOne("SELECT price_in FROM {$sqlname}price WHERE n_id = '".$data['prid']."' and identity = '$identity'");

			//количество на складе
			//$kol_in = $db -> getOne("SELECT SUM(kol) as kol FROM {$sqlname}modcatalog_skladpoz WHERE sklad = '$sklad' $pozzi and prid = '$data[prid]' and identity = '$identity'") + 0;

			$kol_in = ( !empty($sklads) ) ? $db -> getOne("SELECT SUM(kol) as kol FROM {$sqlname}modcatalog_skladpoz WHERE sklad IN (".yimplode(",", $sklads).") $pozzi and prid = '$data[prid]' and identity = '$identity'") + 0 : 0;

			//количество на складе под сделку
			$kol_din = $db -> getOne("SELECT SUM(kol) as kol FROM {$sqlname}modcatalog_skladpoz WHERE did = '$did' $pozzi and prid = '$data[prid]' and identity = '$identity'") + 0;

			//кол-во в резерве
			$kol_res = $db -> getOne("SELECT SUM(kol) as kol FROM {$sqlname}modcatalog_reserv WHERE prid = '".$data['prid']."' and did = '$did' and identity = '$identity'") + 0;

			//смотрим, сколько уже заказано под эту сделку
			if ($did > 0) {
				$q = "
					SELECT
						SUM({$sqlname}modcatalog_zayavkapoz.kol) as kol
					FROM {$sqlname}modcatalog_zayavkapoz
						LEFT JOIN {$sqlname}modcatalog_zayavka ON {$sqlname}modcatalog_zayavka.id = {$sqlname}modcatalog_zayavkapoz.idz
					WHERE
						{$sqlname}modcatalog_zayavkapoz.prid = '".$data['prid']."' and
						{$sqlname}modcatalog_zayavka.did = '$did' and
						{$sqlname}modcatalog_zayavka.id != '$idz' and
						{$sqlname}modcatalog_zayavkapoz.identity = '$identity'
				";

				$kol_zak = $db -> getOne($q) + 0;

				$data['kol'] -= $kol_zak;
			}

			$kol_in = $kol_in + $kol_res - $kol_din;

			if ($kol_in < pre_format($data['kol'])) {
				$ss = 'border:1px solid red';
				$tt = '<span class="red smalltxt">На складе - <b>'.$kol_in.'</b> позиций (с учетом резерва под сделку).</span>';
			}

			$idp = $db -> getOne("SELECT id FROM {$sqlname}modcatalog_zayavkapoz WHERE prid = '".$data['prid']."' and idz = '$idz' and identity = '$identity'") + 0;

			if ($data['prid'] > 0 && $data['kol'] > 0 && ( $data['kol'] - $kol_res ) != 0) {
				$speca[] = [
					"idp"    => $idp,
					"prid"   => $data['prid'],
					"title"  => $data['title'],
					"kol"    => num_format($data['kol']),
					"kol_in" => $kol_in,
					"price"  => num_format($data['price_in']),
				];
			}

			$i++;
		}
	}

	print $speca = json_encode_cyr($speca);

	exit();
}

//позиции на складе
if ($action == "pozfromsklad") {
	$sklad = $_REQUEST['sklad'];
	$list  = [];

	$res = $db -> query("SELECT * FROM {$sqlname}modcatalog_skladpoz WHERE sklad = '$sklad' and status != 'out' and identity = '$identity'");
	while ($data = $db -> fetch($res)) {
		//количество в резерве
		$kol_res = $db -> getOne("select SUM(kol) as kol from {$sqlname}modcatalog_reserv where prid='".$data['prid']."' and identity = '$identity'") + 0;

		$list[] = [
			"id"     => $data['id'],
			"serial" => $data['serial'],
			"prid"   => $data['prid'],
			"did"    => $data['did'],
			"kol"    => $data['kol'],
			"order"  => $data['idorder_in'],
		];
	}

	print json_encode_cyr($list);

	exit();
}

if ($action == "filedelete") {
	$id   = $_REQUEST['id'];
	$file = $_REQUEST['file'];

	$files = $db -> getOne("select files from {$sqlname}modcatalog WHERE id='".$id."' and identity = '$identity'");
	$files = json_decode($files, true);

	$nfiles  = [];
	$nxfiles = '';

	foreach ($files as $xfile) {
		if ($xfile['file'] == $file) {
			unlink($rootpath.'/files/'.$fpath.'modcatalog/'.$file);
		}
		else {
			$nfiles[] = [
				"name" => $xfile['name'],
				"file" => $xfile['file'],
			];
		}
	}

	if (!empty($nfiles)) {
		$nxfiles = json_encode_cyr($nfiles);
	}

	//запишем новый массив файлов, уже без удаляемого
	$db -> query("update {$sqlname}modcatalog set files = '".$nxfiles."' where id = '".$id."' and identity = '$identity'");

	exit();
	//$action = "filelist";
}
if ($action == "filelist") {
	$id = $_REQUEST['id'];

	$result = $db -> getOne("select files from {$sqlname}modcatalog WHERE id='".$id."' and identity = '$identity'");
	$files  = json_decode($result, true);

	foreach ($files as $file) {
		print '
			<div class="tags">
				<a href="/files/'.$fpath.'modcatalog/'.$file['file'].'" target="blank" title="В новом окне">'.get_icon2($file['file']).'&nbsp;'.$file['name'].'</a>&nbsp;
				<a href="javascript:void(0)" onclick="cf=confirm(\'Вы действительно хотите Удалить файл?\\nФайл будет Удален из системы.\');if (cf)deleteFile(\''.$id.'\',\''.$file['file'].'\');" title="Удалить"><i class="icon-cancel red"></i></a>
			</div>';
	}

	exit();
}

if ($action == "export") {

	$idcategory = $_REQUEST['idcategory'];
	$word       = $_REQUEST['word'];
	$statuss    = $_REQUEST['status'];

	$sort = '';

	$dname  = [];
	$result = $db -> getAll("SELECT * FROM {$sqlname}field WHERE fld_tip='price' and identity = '$identity' ORDER BY fld_order");
	foreach ($result as $data) {
		$dname[$data['fld_name']] = $data['fld_title'];
	}

	$h = [
		"Артикул",
		"Категория",
		"Наименование",
		"Статус",
		"Количество",
		"Ед.изм.",
		$dname['price_in']
	];

	foreach ($fields as $field) {
		$h[] = $field['title'];
	}

	$h[] = "НДС";
	$h[] = "Описание краткое";
	$h[] = "Описание полное";
	$h[] = "Изображение 1";
	$h[] = "Изображение 2";
	$h[] = "Изображение 3";
	$h[] = "Изображение 4";
	$h[] = "Изображение 5";

	//поля из характеристик
	$appendix = $db -> getCol("SELECT name FROM {$sqlname}modcatalog_fieldcat WHERE identity = '$identity' ORDER by ord");
	foreach ($appendix as $a){
		$h[] = $a;
	}

	$otchet[] = $h;

	if ($idcategory) {

		$ss = "and pr_cat = '".$idcategory."'";

		$isSub = $db -> getOne("SELECT sub FROM {$sqlname}price_cat WHERE idcategory='".$idcategory."' and identity = '$identity'");

		if ($isSub == 0) {//если это головная категория, то выбираем все записи и из подкатегорий

			$sub  = $db -> getCol("SELECT idcategory FROM {$sqlname}price_cat WHERE sub='".$idcategory."' and identity = '$identity'");
			$subb = yimplode(",", $sub);

			if ($subb != '') {
				$ss .= " or pr_cat IN ($subb)";
			}
		}

		$sort .= " and (pr_cat='".$idcategory."' ".$ss.")";

	}

	if ($word != '') {
		$sort .= $sort." and ((artikul LIKE '%".$word."%') or (title LIKE '%".$word."%') or (descr LIKE '%".$word."%'))";
	}

	if ($statuss != '') {
		//print "SELECT n_id FROM {$sqlname}modcatalog where status IN (".$statuss.") and identity = '$identity'";
		$sta = $db -> getCol("SELECT prid FROM {$sqlname}modcatalog where status= '".$statuss."' and identity = '$identity'");
		$sta = yimplode(",", $sta);

		$sort .= " and n_id IN (".$sta.")";
	}

	$result = $db -> getAll("SELECT * FROM {$sqlname}price WHERE n_id > 0 $sort and identity = '$identity' ORDER BY pr_cat");
	foreach ($result as $data) {

		$res   = $db -> getRow("select title, sub from {$sqlname}price_cat where idcategory='".$data['pr_cat']."' and identity = '$identity'");
		$cat   = $res["title"];
		$idsub = $res["sub"];

		$res      = $db -> getRow("select * from {$sqlname}modcatalog where prid='".$data['n_id']."' and identity = '$identity'");
		$contentt = htmlspecialchars_decode($res["content"]);
		$status   = strtr($res["status"], $statear);
		$kol      = $res["kol"];
		$file     = $res["files"];

		$files = json_decode($file, true);

		$r = [
			$data['artikul']." ",
			convertcsv($cat),
			convertcsv($data['title']),
			convertcsv($status),
			$kol,
			convertcsv($data['edizm']),
			num_format($data['price_in'])
		];

		foreach ($fields as $field) {
			$r[] = $data[$field['field']];
		}

		$r[] = num_format($data['nds']);
		$r[] = clnall(untag(convertcsv($data['descr'])));
		$r[] = convertcsv($contentt);
		$r[] = $files[0]['file'];
		$r[] = $files[1]['file'];
		$r[] = $files[2]['file'];
		$r[] = $files[3]['file'];
		$r[] = $files[3]['file'];

		//доп.поля
		$re = $db -> getAll("SELECT * FROM {$sqlname}modcatalog_fieldcat WHERE identity = '$identity' ORDER by ord");
		foreach ($re as $da) {
			//это ввыбранные варианты в профиле конкретного клиента
			$value          = $db -> getOne("SELECT value FROM {$sqlname}modcatalog_field WHERE n_id = '".$data['n_id']."' and pfid = '".$da['id']."' and identity = '$identity'");
			$r[] = convertcsv($value);
		}

		$otchet[] = $r;

	}

	//---рабочий, только генерит в формате xml
	//require_once("../../opensource/class/php-excel.class.php");

	$xls = new Excel_XML('UTF-8', true, 'Catalog');
	$xls -> addArray($otchet);
	$xls -> generateXML('export_catalog');

	exit();

}
if ($action == "discard") {
	$url = $rootpath.'/files/'.$fpath.$_COOKIE['url_catalog'];
	setcookie("url_catalog", '');
	unlink($url);

	exit();
}

/**
 * Редактирование позиции
 */
if ($action == "edit_on") {

	$params = $_REQUEST;

	$sklad  = new Storage();
	$result = $sklad -> edit($params);

	$apx = ( count($result['error']) > 0 ) ? "<br>Ошибки:<br>".implode("<br>", $result['error']) : '';

	print "Результат: ".$result['result'].$apx;

	exit();
}

/**
 * не используется. Редактирование дополнительных затрат по позиции
 */
if ($action == "editdop_on") {
	$params = [];

	$params['clid']    = $_REQUEST['clid'];
	$params['id']      = $_REQUEST['id'];
	$params['n_id']    = $_REQUEST['n_id'];
	$params['datum']   = $_REQUEST['datum'];
	$params['summa']   = pre_format($_REQUEST['summa']);
	$params['content'] = untag($_REQUEST['content']);

	//print_r($params);

	if ($params['id'] > 0) {
		//сформируем старые данные в массив
		$result               = $db -> getRow("SELECT * FROM {$sqlname}modcatalog_dop WHERE id = '".$params['id']."' and identity = '$identity'");
		$oldparams['clid']    = $result["clid"];
		$oldparams['content'] = $result["content"];
		$oldparams['summa']   = num_format($result["summa"]);
		$oldparams['datum']   = $result["datum"];
		$oldparams['iduser']  = $result["iduser"];
		$prid                 = $result["prid"];

		$oldparams['prid'] = $params['n_id'];
		$params['prid']    = $params['n_id'];

		//внесем эту сумму в колонку provider в сделке
		$db -> query("update {$sqlname}modcatalog_dop set summa = '".$params['summa']."', datum = '".$params['datum']."', content = '".$params['content']."', clid = '".$params['clid']."' WHERE id = '".$params['id']."' and identity = '$identity'");

		$params['dopzid'] = $params['id'];

		mclogger('dop', $params, $oldparams);
	}
	else {
		//найдем id позиции в каталоге
		//$resultp = mysql_query("select * from {$sqlname}modcatalog where prid='".$n_id."' and identity = '$identity'");
		//$prid = mysql_result($resultp, 0 , "n_id");

		//внесем эту сумму расходы по позиции
		$db -> query("insert into {$sqlname}modcatalog_dop (id,prid,datum,content,summa,clid,iduser,identity) value (null,'".$params['n_id']."','".$params['datum']."','".$params['content']."','".$params['summa']."','".$params['clid']."','$iduser1','$identity')");

		$params['dopzid'] = $db -> insertId();
		$params['prid']   = $params['n_id'];

		mclogger('dop', $params);
	}

	//print $logerror;

	print 'Сделано';
	exit();
}

/**
 * Редактирование розничной цены
 */
if ($action == "editone_on") {
	$params = $oldparams = [];

	$params['id']      = $_REQUEST['id'];
	$params['n_id']    = $_REQUEST['n_id'];
	$params['prid']    = $_REQUEST['n_id'];
	$params['descr']   = $_REQUEST['descr'];
	$params['price_1'] = pre_format($_REQUEST['price_1']);

	$sklad  = new Storage();
	$result = $sklad -> editprice($params);

	$apx = ( count($result['error']) > 0 ) ? "<br>Ошибки:<br>".implode("<br>", $result['error']) : '';

	print "Результат: ".$result['result'].$apx;

	exit();
}

/**
 * Работа с ордерами
 */
if ($action == "editakt_on") {
	$idp         = $_REQUEST['idp'];
	$speca_title = $_REQUEST['speca_title'];
	$speca_kol   = $_REQUEST['speca_kol'];
	$speca_price = $_REQUEST['speca_price'];
	$prid        = $_REQUEST['prid'];
	$serial      = $_REQUEST['serial'];

	$mes = [];

	unset($_REQUEST['idp'], $_REQUEST['speca_title'], $_REQUEST['speca_kol'], $_REQUEST['speca_price'], $_REQUEST['prid']);

	$params = $_REQUEST;

	foreach ($idp as $k => $v) {
		$params['speka'][] = [
			"idp"         => $v,
			"prid"        => $prid[$k],
			"speca_title" => $speca_title[$k],
			"speca_kol"   => $speca_kol[$k],
			"speca_price" => $speca_price[$k],
			"serial"      => $serial[$prid[$k]],
		];
	}

	//print_r($params);
	//exit();

	$sklad  = new Storage();
	$result = $sklad -> editakt($params);

	$mes[] = $result['result'];

	if ($result['count']['add'] > 0) {
		$mes[] = "Добавлено ".$result['count']['add']." позиций";
	}
	if ($result['count']['update'] > 0) {
		$mes[] = "Обновлено ".$result['count']['update']." позиций";
	}
	if ($result['count']['delete'] > 0) {
		$mes[] = "Удалено ".$result['count']['delete']." позиций";
	}

	$mes = implode("<br>", $mes);
	$err = implode("<br>", $result['error']);

	print json_encode_cyr([
		"id"      => $result['id'],
		"message" => $mes,
		"error"   => $err,
		"doit"    => $result['doit'],
	]);

	exit();
}

/**
 * Установка серийников с датами и периодами
 */
if ($action == "editaktperpoz_on") {

	$id     = $_REQUEST['id'];
	$prid   = $_REQUEST['prid'];
	$seria  = $_REQUEST['serial'];
	$create = $_REQUEST['date_create'];
	$period = $_REQUEST['date_period'];

	$serial = [];

	foreach ($id as $k => $v) {
		$serial[$v] = [
			"prid"        => $prid[$k],
			"serial"      => $seria[$k],
			"date_create" => $create[$k],
			"date_period" => $period[$k],
		];
	}

	//print_r($serial);
	//exit();

	$sklad  = new Storage();
	$result = $sklad -> serials(["serial" => $serial]);

	print '{"message":"Сделано"}';

	exit();
}

//не реализовано. отмена проведения акта. Задел
if ($action == "editaktundo_on") {

	$id = $_REQUEST['id'];

	$order = $db -> getRow("SELECT * FROM {$sqlname}modcatalog_akt WHERE id = '".$id."' and identity = '$identity'");

	//обработка приходных ордеров
	if ($order['tip'] == "income") {
		//обычный учет
		if ($settings['mcSkladPoz'] != "yes") {
		} //поштучный учет
		else {
		}
	} //обработка расходных ордеров
	else {
		//todo: будут проблемы с резервами, т.к. они удаляются после проведения расхода

		//обычный учет
		if ($settings['mcSkladPoz'] != "yes") {
		} //поштучный учет
		else {
		}
	}

	exit();
}

//редактор позиции на складе для поштучного учета
if ($action == "editskladpozone_on") {

	$params = $_REQUEST;

	$sklad  = new Storage();
	$result = $sklad -> editone($params);

	$mes = $result['result'];

	print $mes;

	exit();
}

//редактор кол-ва позиции на складе для валового учета
if ($action == "editskladpoz_on") {

	$params = $_REQUEST;

	$sklad  = new Storage();
	$result = $sklad -> editone($params);

	$mes = $result['result'];

	print $mes;

	exit();
}

//перемещение м/ складами
if ($action == "movetoskald_on") {

	$params = $_REQUEST;

	$sklad  = new Storage();
	$result = $sklad -> move($params);

	$mes = $result['result'];

	print $mes;

	exit();
}

//редактор заявок
if ($action == "editzayavka_on") {

	$idp         = $_REQUEST['idp'];
	$speca_title = $_REQUEST['speca_title'];
	$speca_kol   = $_REQUEST['speca_kol'];
	$prid        = $_REQUEST['prid'];

	unset($_REQUEST['idp'], $_REQUEST['speca_title'], $_REQUEST['speca_kol'], $_REQUEST['prid']);

	$params = $_REQUEST;

	foreach ($idp as $k => $v) {
		$params['speka'][] = [
			"idp"  => $v,
			"prid" => $prid[$k],
			"kol"  => pre_format($speca_kol[$k]),
		];
	}

	//print_r($params['speka']);
	//exit();

	$sklad  = new Storage();
	$result = $sklad -> editzayavka($params);

	$mes = "Результат: ".$result['result'];

	$mes .= ( $result['sync'] != '' ) ? "<br>Обработка:<br>".$result['sync'] : '';
	$mes .= ( count($result['error']) > 0 ) ? "<br>Ошибки:<br>".implode("<br>", $result['error']) : '';

	print $mes;

	exit();
}

//изменение статуса заявки
if ($action == "editzayavkastatus_on") {

	$params = $_REQUEST;

	$sklad  = new Storage();
	$result = $sklad -> editzstatus($params);

	$mes = "Результат: ".$result['result'];
	$mes .= ( count($result['error']) > 0 ) ? "<br>Ошибки:<br>".implode("<br>", $result['error']) : '';

	print $mes;

	exit();
}

if ($action == "editoffer_on") {
	$params = $_REQUEST;

	$sklad  = new Storage();
	$result = $sklad -> editoffer($params);

	$mes = "Результат: ".$result['result'];
	$mes .= ( count($result['error']) > 0 ) ? "<br>Ошибки:<br>".implode("<br>", $result['error']) : '';

	print $mes;

	exit();
}

/**
 * @deprecated
 * @see price
 */
if ($action == "import_on") {
	//require_once '../../opensource/excel_reader/excel_reader2.php';

	$statuses = [
		'Нет данных' => '0',
		'Заказан'    => '1',
		'Под заказ'  => '2',
		'В наличии'  => '3',
		'Продан'     => '4',
	];
	$isPrice  = [
		"price_in",
		"price_1",
		"price_2",
		"price_3",
		"price_4",
		"price_5",
	];
	$isFile   = [
		"files_1",
		"files_2",
		"files_3",
		"files_4",
		"files_5",
	];

	$url    = $rootpath.'/files/'.$fpath.$_COOKIE['url_catalog'];//файл для расшифровки
	$fields = $_REQUEST['field'];                                //порядок полей

	$date_create = current_datumtime();

	$cc        = 0;
	$pp        = 0;
	$z         = 0;
	$maxImport = 5000;

	foreach ($fields as $i => $field) {

		if (strpos($field, 'catalog') !== false && strpos($field, 'status') === false) {

			$c = str_replace("catalog:", "", $field);
			//индекс названия позиции
			if ($c == 'title') {
				$clx = $i;
			}

			$indexs['catalog'][]  = $i;//массив ключ поля -> номер столбца
			$names['catalog'][$i] = $c;//массив номер столбца -> индекс поля

		}
		if (strpos($field, 'price') !== false && strpos($field, 'category') === false) {

			$c = str_replace("price:", "", $field);
			//индекс названия позиции
			if ($c == 'title') {
				$clx = $i;
			}

			$indexs['price'][]  = $i;//массив ключ поля -> номер столбца
			$names['price'][$i] = $c;//массив номер столбца -> индекс поля

		}
		if (strpos($field, 'profile') !== false) {

			$c = str_replace("profile:", "", $field);

			$indexs['profile'][]  = $i;//массив ключ поля -> номер столбца
			$names['profile'][$i] = $c;//массив номер столбца -> индекс поля

		}
		if (strpos($field, 'status') !== false) {
			$c = str_replace("status:", "", $field);

			$indexs['status'][$c] = $i;//массив ключ поля -> номер столбца
		}
		if (strpos($field, 'category') !== false) {

			$c = str_replace("category:", "", $field);

			$indexs['category'][$c] = $i;//массив ключ поля -> номер столбца

		}

	}

	//print_r($indexs);
	//print_r($names);
	//exit();

	//считываем данные из файла в массив
	//require_once '../../opensource/spreadsheet-reader-master/SpreadsheetReader.php';
	//require_once '../../opensource/spreadsheet-reader-master/php-excel-reader/excel_reader2.php';

	$data = [];

	$datas = new SpreadsheetReader($url);
	$datas -> ChangeSheet(0);

	foreach ($datas as $k => $Row) {
		if ($k <= $maxImport) {
			foreach ($Row as $key => $value) {
				$data[$k][] = enc_detect(untag($value));
			}
		}
		else {
			goto p1;
		}
	}

	p1:
	$data = array_values($data);

	unlink($url);

	$new1 = 0;
	$upd1 = 0;
	$new2 = 0;
	$upd2 = 0;
	$new3 = 0;
	$upd3 = 0;
	$err  = [];

	//print_r($data);
	//exit();

	//импортируем данные из файла
	foreach ($data as $i => $row) {

		$stringPrice  = ''; //для update
		$stringPrice2 = [];
		$namePrice2   = []; //для insert

		$stringCatalog  = '';//для update
		$stringCatalog2 = [];
		$nameCatalog2   = []; //для insert

		$profile = [];
		$files   = [];

		//обработаем отрасль
		if (enc_detect($row[$indexs['status']['title']]) != '') {

			$status[$i] = strtr(enc_detect($row[$indexs['status']['title']]), $statuses);

			//для update
			$stringCatalog .= "status = '".$status[$i]."',";

			//для insert
			$nameCatalog2[]   = "status";
			$stringCatalog2[] = "'".$status[$i]."'";

			$status[$i] = "'".$status[$i]."'";//массив для формирования запроса для вставки новой позиции

		}
		else {
			$nameCatalog2[]   = "status";
			$stringCatalog2[] = "''";
		}

		//обработаем раздел прайса
		if (enc_detect($row[$indexs['category']['title']]) != '') {

			$prcat = $db -> getCol("SELECT title FROM {$sqlname}price_cat WHERE identity = '$identity' ORDER BY title");

			//сопоставляем id раздела текущей позиции, если нет создаем.
			if (in_array(enc_detect(untag($row[$indexs['category']['title']])), $prcat)) {
				//если такое название уже сужествует, то сопоставляем id
				$idcat[$i] = $db -> getOne("SELECT idcategory FROM {$sqlname}price_cat WHERE title = '".enc_detect(untag($row[$indexs['category']['title']]))."' and identity = '$identity'");
			}
			else {
				$db -> query("insert into {$sqlname}price_cat (`idcategory`, `title`, `identity`) values(null, '".enc_detect(untag($row[$indexs['category']['title']]))."','$identity')");
				$idcat[$i] = $db -> insertId();
			}

			//для update
			$stringPrice .= "pr_cat = '".$idcat[$i]."',";

			//для insert
			$namePrice2[]   = "pr_cat";
			$stringPrice2[] = "'".$idcat[$i]."'";
			//$queryp[$i][] = "'".$idcat[$i]."'";

		}
		else {
			$stringPrice .= "pr_cat = '',";

			$namePrice2[]   = "pr_cat";
			$stringPrice2[] = "''";

			//$qup[$i][] = "pr_cat = ''";
			//$queryp[$i][] = '';
		}

		//сформируем запрос
		for ($j = 0; $j < count($indexs['price']); $j++) {

			if ($data[$i][$indexs['price'][$j]] != '') {
				$queryp = 0;

				if ($names['price'][$indexs['price'][$j]] == 'descr') {
					$qup[$i][] = $names['price'][$indexs['price'][$j]]." = '".htmlspecialchars($data[$i][$indexs['price'][$j]])."'";
					$queryp    = "'".htmlspecialchars($data[$i][$indexs['price'][$j]])."'";
				}
				elseif ($names['price'][$indexs['price'][$j]] == 'nds') {
					$qup[$i][] = $names['price'][$indexs['price'][$j]]." = '".intval($data[$i][$indexs['price'][$j]])."'";
					$queryp    = "'".intval($data[$i][$indexs['price'][$j]])."'";
				}
				elseif (in_array($names['price'][$indexs['price'][$j]], $isPrice)) {
					$qup[$i][] = $names['price'][$indexs['price'][$j]]." = '".pre_format($data[$i][$indexs['price'][$j]])."'";
					$queryp    = "'".pre_format($data[$i][$indexs['price'][$j]])."'";
				}
				else {
					$qup[$i][] = $names['price'][$indexs['price'][$j]]." = '".untag($data[$i][$indexs['price'][$j]])."'";
					$queryp    = "'".untag($data[$i][$indexs['price'][$j]])."'";
				}

				//$namePrice[] = $names['price'][$indexs['price'][$j]];

				$namePrice2[]   = $names['price'][$indexs['price'][$j]];
				$stringPrice2[] = $queryp;
			}
		}

		//$stringPrice2 = implode(",", $queryp[$i]);

		for ($j = 0; $j < count($indexs['catalog']); $j++) {
			if ($data[$i][$indexs['catalog'][$j]] != '') {
				$queryc = 0;

				if ($names['catalog'][$indexs['catalog'][$j]] == 'content') {
					$quc[$i][] = $names['catalog'][$indexs['catalog'][$j]]." = '".htmlspecialchars($data[$i][$indexs['catalog'][$j]])."'";
					$queryc    = "'".htmlspecialchars($data[$i][$indexs['catalog'][$j]])."'";
				}
				elseif (in_array($names['catalog'][$indexs['catalog'][$j]], $isFile)) {
					//$quc[$i][] = '';
					//$queryc = '';

					$files[] = [
						"name" => $data[$i][$indexs['catalog'][$j]],
						"file" => $data[$i][$indexs['catalog'][$j]],
					];
				}
				elseif ($names['catalog'][$indexs['catalog'][$j]] == 'kol') {
					$quc[$i][] = $names['catalog'][$indexs['catalog'][$j]]." = '".intval($data[$i][$indexs['catalog'][$j]])."'";
					$queryc    = "'".intval($data[$i][$indexs['catalog'][$j]])."'";
				}
				else {
					$quc[$i][] = $names['catalog'][$indexs['catalog'][$j]]." = '".untag($data[$i][$indexs['catalog'][$j]])."'";
					$queryc    = "'".untag($data[$i][$indexs['catalog'][$j]])."'";
				}

				if (!in_array($names['catalog'][$indexs['catalog'][$j]], $isFile)) {
					$nameCatalog      .= ", ".$names['catalog'][$indexs['catalog'][$j]];
					$nameCatalog2[]   = $names['catalog'][$indexs['catalog'][$j]];
					$stringCatalog2[] = $queryc;
				}
			}
		}

		if (count($files) > 0) {
			$files = json_encode_cyr($files);

			$quc[$i][] = "files = '".$files."'";

			$nameCatalog2[]   = 'files';
			$stringCatalog2[] = "'".$files."'";
		}

		$stringPrice   .= " ".implode(", ", $qup[$i]);//строка для прайса
		$stringCatalog .= " ".implode(", ", $quc[$i]);//строка для каталога
		//$stringCatalog2 = implode(",", $queryc[$i]);

		$namePrice2   = 'n_id,'.implode(",", $namePrice2);
		$stringPrice2 = implode(",", $stringPrice2);

		$nameCatalog2   = 'id,prid,'.implode(",", $nameCatalog2);
		$stringCatalog2 = implode(",", $stringCatalog2);

		//$namePrice = 'n_id'.$namePrice;
		//$nameCatalog = 'id,prid'.$nameCatalog;

		//print $stringPrice.'\n\r';
		//print $stringCatalog.'\n\r';

		//если в данных есть позиция

		//print "\n===".untag(enc_detect($data[$i][$clx]))."\n";

		//поищем клиента в базе
		$n_id = $db -> getOne("select n_id from {$sqlname}price where title='".untag(enc_detect($data[$i][$clx]))."' and identity = '$identity'");

		//print "\n===".$n_id."===".$prid."===\n";

		//print "update {$sqlname}price set ".$stringPrice." WHERE n_id='".$n_id."' and identity = '$identity'\n";
		//print "insert into {$sqlname}price (".$namePrice2.",identity) values (null, ".$stringPrice2.",'$identity')\n";

		if ($n_id > 0) {
			if ($db -> query("update {$sqlname}price set ".$stringPrice." WHERE n_id='".$n_id."' and identity = '".$identity."'")) {
				$upd1++;
			}
		}
		else {
			if ($db -> query("INSERT INTO {$sqlname}price (".$namePrice2.",identity) VALUES (NULL, ".$stringPrice2.",'".$identity."')")) {
				$new1++;
				$n_id = $db -> insertId();
			}
		}

		$prid = $db -> getOne("select prid from {$sqlname}modcatalog where prid='".$n_id."' and identity = '$identity'");

		//if($stringCatalog != ' ') print "update {$sqlname}modcatalog set ".$stringCatalog." WHERE prid='".$prid."' and identity = '$identity'\n";
		//if($nameCatalog2 != ' ') print "insert into {$sqlname}modcatalog (".$nameCatalog2.",identity) values (null, '$n_id', ".$stringCatalog2.",'$identity')\n";

		if ($prid > 0 and $stringCatalog != ' ') {
			if ($db -> query("update {$sqlname}modcatalog set ".$stringCatalog." WHERE prid='".$n_id."' and identity = '$identity'")) {
				$upd2++;
			}
		}
		else {
			if ($nameCatalog2 != ' ') {
				if ($db -> query("insert into {$sqlname}modcatalog (".$nameCatalog2.",identity) values (null, $n_id, ".$stringCatalog2.",'$identity')")) {
					$new2++;
				}
			}
		}

		//добавим доп.поля клиента
		for ($j = 0; $j < count($indexs['profile']); $j++) {
			if ($data[$i][$indexs['profile'][$j]] != '') {
				$profile[$names['profile'][$indexs['profile'][$j]]] = untag($data[$i][$indexs['profile'][$j]]);
			}
		}

		//if(count($profile) > 0) print "===".$n_id."===\n";

		if (count($profile) > 0) {
			//print_r($profile);

			//проходим массив
			foreach ($profile as $key => $value) {
				print "===".$key."===".$value."\n";

				//найдем id каталога профилей
				$idp = $db -> getOne("SELECT id FROM {$sqlname}modcatalog_fieldcat where pole = '".$key."' and identity = '$identity'");

				$idc = $db -> getOne("SELECT id FROM {$sqlname}modcatalog_field where pfid = '".$idp."' and n_id = '$n_id' and identity = '$identity'");

				if ($idc < 1 and $idp > 0) {
					//print "insert into {$sqlname}modcatalog_field (id,pfid,n_id,value,identity) values(null, '".$idp."', '".$n_id."', '".$value."','$identity')\n";

					if ($db -> query("insert into {$sqlname}modcatalog_field (id,pfid,n_id,value,identity) values(null, '".$idp."', '".$n_id."', '".$value."','$identity')")) {
						$new3++;
					}
				}
				elseif ($idc > 0 and $idp > 0) {
					//print "update {$sqlname}modcatalog_field set value = '".$value."' where n_id = '".$n_id."' and pfid = '".$idp."' and identity = '$identity'\n";

					if ($db -> query("update {$sqlname}modcatalog_field set value = '".$value."' where n_id = '".$n_id."' and pfid = '".$idp."' and identity = '$identity'")) {
						$upd3++;
					}
				}
			}
		}

	}

	//print_r($qupr);
	//print_r($querypr);

	//exit();

	unlink($url);

	$mesg = '';
	if (count($err) == 0) {
		$mesg .= "Ошибок: нет<br>";
	}
	else {
		$mesg .= "Есть ошибки. Ошибок: <b>".count($err)."</b><br>";
	}

	if ($new1 > 0) {
		$mesg .= "Добавлено записей прайса: <b>".$new1."</b><br>";
	}
	if ($upd1 > 0) {
		$mesg .= "Обновлено записей прайса: <b>".$upd1."</b><br>";
	}

	if ($new2 > 0) {
		$mesg .= "Добавлено записей каталога: <b>".$new2."</b><br>";
	}
	if ($upd2 > 0) {
		$mesg .= "Обновлено записей каталога: <b>".$upd2."</b><br>";
	}

	if ($new3 > 0) {
		$mesg .= "Добавлено записей профиля: <b>".$new3."</b><br>";
	}
	if ($upd3 > 0) {
		$mesg .= "Обновлено записей профиля: <b>".$upd3."</b><br>";
	}

	logger('6', 'Импорт каталога', $iduser1);

	print $mesg;
	exit();
}

if ($action == "mass_do") {

	$idcategory = $_REQUEST['idcategory'];
	$word       = $_REQUEST['word'];
	$ids        = explode(";", $_REQUEST['ids']);
	$doAction   = $_REQUEST['doAction'];
	$newcat     = $_REQUEST['newcat'];
	$isSelect   = $_REQUEST['isSelect'];

	$good = 0;
	$err  = 0;
	$ss   = '';

	if ($newcat > 0 && $doAction == 'pMove') {//если пеоемещаем

		if ($isSelect == 'doAll') {//все

			if ($idcategory) {
				$ss = "and pr_cat = '".$idcategory."'";

				$isSub = $db -> getOne("SELECT sub FROM {$sqlname}price_cat WHERE idcategory='".$idcategory."' and identity = '$identity'");

				//если это головная категория, то выбираем все записи и из подкатегорий
				if ($isSub == 0) {

					$sub = $db -> getCol("SELECT idcategory FROM {$sqlname}price_cat WHERE sub='".$idcategory."' and identity = '$identity'");

					$subb = implode(",", $sub);

					if ($subb != '') {
						$ss .= " or pr_cat IN ($subb)";
					}

				}

			}
			if ($word != '') {
				$ss .= " and ((artikul LIKE '%".$word."%') or (title LIKE '%".$word."%') or (descr LIKE '%".$word."%'))";
			}

			//print "SELECT * FROM {$sqlname}price WHERE $ss and identity = '$identity' ORDER BY title";
			//exit();

			$res = $db -> getAll("SELECT n_id FROM {$sqlname}price WHERE n_id > 0 $ss and identity = '$identity' ORDER BY title");
			foreach ($res as $datas) {
				if ($db -> query("update {$sqlname}price set pr_cat='$newcat' WHERE n_id='".$datas['n_id']."' and identity = '$identity'")) {
					$good++;
				}
				else {
					$err++;
				}
			}

		}

		//выбранные
		if ($isSelect == 'doSel') {

			foreach ($ids as $id) {

				if ((int)$ids > 0 && $db -> query("update {$sqlname}price set pr_cat='$newcat' WHERE n_id='".$id."' and identity = '$identity'")) {
					$good++;
				}
				else {
					$err++;
				}

			}

		}

	}

	//если удаляем
	if ($doAction == 'pDele') {

		if ($isSelect == 'doAll') {//все

			if ($idcategory) {

				$ss = "and pr_cat = '".$idcategory."'";

				$isSub = $db -> getOne("SELECT sub FROM {$sqlname}price_cat WHERE idcategory='".$idcategory."' and identity = '$identity'");

				if ($isSub == 0) {//если это головная категория, то выбираем все записи и из подкатегорий

					$sub  = $db -> getCol("SELECT idcategory FROM {$sqlname}price_cat WHERE sub='".$idcategory."' and identity = '$identity'");
					$subb = yimplode(",", $sub);

					if ($subb != '') {
						$ss .= " or pr_cat IN ($subb)";
					}

				}

			}
			if ($word != '') {
				$ss .= " and ((artikul LIKE '%".$word."%') or (title LIKE '%".$word."%') or (descr LIKE '%".$word."%'))";
			}

			$res = $db -> getAll("SELECT n_id FROM {$sqlname}price WHERE n_id > 0 ".$ss." and identity = '$identity'");
			foreach ($res as $datas) {

				if ($db -> query("delete from {$sqlname}price where n_id = '".$datas['n_id']."' and identity = '$identity'")) {
					$good++;
				}
				else {
					$err++;
				}

				if ($db -> query("delete from {$sqlname}modcatalog where prid = '".$datas['n_id']."' and identity = '$identity'")) {
					$good++;
				}
				else {
					$err++;
				}

			}

		}

		//выбранные
		if ($isSelect == 'doSel') {

			foreach ($ids as $id) {

				if ((int)$id > 0) {

					if ($db -> query("delete from {$sqlname}price where n_id = '$id' and identity = '$identity'")) {
						$good++;
					}

					if ($db -> query("delete from {$sqlname}modcatalog where prid = '$id' and identity = '$identity'")) {
						$good++;
					}

				}
			}

		}

	}

	print "Выполнено для ".$good." записей. Ошибок:".$err;
	exit();
}

/**
 * @deprecated
 * @see price
 */
if ($action == "upload") {

	$err  = '';
	$list = [];
	$file = '';

	if ($_FILES['file']['name'] != '') {
		//require_once '../../opensource/excel_reader/excel_reader2.php';

		//загружаем из файла
		$cur_ext = getExtention($_FILES['file']['name']);
		if ($cur_ext != 'xls') {
			$err = "Ошибка при загрузке файла!\nОшибка: Допускаются только файлы в формате XLS";
		}
		else {
			$url = '../../files/'.$fpath.translit(str_replace(" ", "", basename($_FILES['file']['name'])));

			//Сначала загрузим файл на сервер
			if (move_uploaded_file($_FILES['file']['tmp_name'], $url)) {
				$file = $url;
			}
			else {
				$err = "Ошибка при загрузке файла!\nОшибка:".$_FILES['file']['error'];
			}
		}

		if ($file != '') {
			$cur_ext = texttosmall(end(explode(".", basename($file))));
			$datas   = new Spreadsheet_Excel_Reader();
			$datas -> setOutputEncoding('UTF-8');
			$datas -> read($file, false);
			$data = $datas -> dumptoarray();//получили двумерный массив с данными

			for ($j = 0; $j <= count($data); $j++) {
				//ищем позицию в прайсе
				$prid = $db -> getOne("SELECT n_id FROM {$sqlname}price WHERE title = '".untag($data[$j][1])."' and identity = '$identity'");

				if ($prid > 0) {
					$list[] = [
						"prid"  => $prid,
						"title" => $data[$j][1],
						"kol"   => $data[$j][2],
						"price" => num_format($data[$j][3]),
					];
				}
			}
		}
	}

	print json_encode_cyr([
		"list"  => $list,
		"error" => $err,
	]);

	exit();
}

if ($action == 'exportPoz') {
	$sklad   = $_REQUEST['sklad'];
	$sstatus = $_REQUEST['sstatus'];
	$sort    = '';

	$otchet[0] = [
		"ID",
		"Артикул",
		"Категория",
		"Наименование",
		"Ед.изм.",
		"Склад",
		"Количество",
	];

	$statuses = [
		"in"  => 'На складе',
		"out" => 'Отгружена',
	];


	if ($settings['mcSkladPoz'] == "yes") {
		array_push($otchet[0], "Серийный номер", "Статус", "Дата поступления", "Прих.ордер", "Дата выбытия", "Расх.ордер");
	}

	if (count($sklad) > 0) {
		$sort .= $sqlname."modcatalog_skladpoz.sklad IN (".yimplode(",", $sklad).") and";
	}
	if (count($sstatus) > 0) {
		$sort .= $sqlname."modcatalog_skladpoz.status IN (".yimplode(",", $sstatus, "'").") and";
	}

	$query = "
		SELECT 
			{$sqlname}modcatalog_skladpoz.id,
			{$sqlname}modcatalog_skladpoz.prid,
			{$sqlname}modcatalog_skladpoz.kol,
			{$sqlname}modcatalog_skladpoz.status,
			DATE_FORMAT({$sqlname}modcatalog_skladpoz.date_in, '%d.%m.%Y') as date_in,
			{$sqlname}modcatalog_skladpoz.idorder_in,
			DATE_FORMAT({$sqlname}modcatalog_skladpoz.date_out, '%d.%m.%Y') as date_out,
			{$sqlname}modcatalog_skladpoz.idorder_out,
			{$sqlname}modcatalog_skladpoz.serial,
			{$sqlname}price.artikul as artikul,
			{$sqlname}price.title as title,
			{$sqlname}price.edizm as edizm,
			{$sqlname}price.pr_cat as category,
			{$sqlname}modcatalog_sklad.title as sklad
		FROM {$sqlname}modcatalog_skladpoz 
			LEFT JOIN {$sqlname}price ON {$sqlname}modcatalog_skladpoz.prid = {$sqlname}price.n_id
			LEFT JOIN {$sqlname}modcatalog_sklad ON {$sqlname}modcatalog_skladpoz.sklad = {$sqlname}modcatalog_sklad.id
		WHERE 
			{$sqlname}modcatalog_skladpoz.id > 0 and 
			$sort
			{$sqlname}modcatalog_skladpoz.identity = '$identity' 
		ORDER BY prid
	";

	$i = 1;

	$result = $db -> query($query);
	while ($data = $db -> fetch($result)) {
		$category = $db -> getOne("select title from {$sqlname}price_cat where idcategory = '$data[category]'");

		$otchet[$i] = [
			$data['prid'],
			$data['artikul'],
			$category,
			$data['title'],
			$data['edizm'],
			$data['sklad'],
			$data['kol'],
		];

		if ($settings['mcSkladPoz'] == "yes") {
			$order_in  = $db -> getOne("select number from {$sqlname}modcatalog_akt where id = '$data[idorder_in]'");
			$order_out = $db -> getOne("select number from {$sqlname}modcatalog_akt where id = '$data[idorder_out]'");

			array_push($otchet[$i], $data['serial'], strtr($data['status'], $statuses), $data['date_in'], $order_in, $data['date_out'], $order_out);
		}

		$i++;
	}

	//require_once("../../opensource/class/php-excel.class.php");

	$xls = new Excel_XML('UTF-8', true, 'Price');
	$xls -> addArray($otchet);
	$xls -> generateXML('export_price');

	exit();
}