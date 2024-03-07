<?php
/* ============================ */
/* (C) 2016 Vladislav Andreev   */
/*       SalesMan Project       */
/*        www.isaler.ru         */
/*        ver. 2017.x           */
/* ============================ */

use Salesman\Price;

error_reporting(E_ERROR);
header("Pragma: no-cache");

$rootpath = dirname(__DIR__, 2);

include $rootpath."/inc/config.php";
include $rootpath."/inc/dbconnector.php";
include $rootpath."/inc/auth.php";
include $rootpath."/inc/func.php";
include $rootpath."/inc/settings.php";
include $rootpath."/inc/language/".$language.".php";

$dname = $don = [];
$price_in = '';

$result = $db -> query("SELECT * FROM {$sqlname}field WHERE fld_tip='price' AND fld_on='yes' and identity = '$identity' ORDER BY fld_order");
while ($data = $db -> fetch($result)) {

	$dname[$data['fld_name']] = $data['fld_title'];
	$dvar[$data['fld_name']]  = $data['fld_var'];
	$don[]                    = $data['fld_name'];

}

if ($show_marga == 'yes') {
	$price_in = $dname['price_in'];
}

$price_1 = ( in_array('price_1', $don) ) ? $dname['price_1'] : '';
$price_2 = ( in_array('price_2', $don) ) ? $dname['price_2'] : '';
$price_3 = ( in_array('price_3', $don) ) ? $dname['price_3'] : '';

$header = [
	"nprice_in" => $price_in,
	"nprice_1"  => $price_1,
	"nprice_2"  => $price_2,
	"nprice_3"  => $price_3,
	"valuta"    => $valuta
];

$lists = Price::getPriceList($_REQUEST);

$lists['header'] = $header;

print json_encode_cyr($lists);

exit();