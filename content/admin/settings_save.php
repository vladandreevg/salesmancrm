<?php
/* ============================ */
/*         SalesMan CRM         */
/* ============================ */
/* (C) 2016 Vladislav Andreev   */
/*       SalesMan Project       */
/*        www.isaler.ru         */
/*        ver. 2017.x           */
/* ============================ */

error_reporting(E_ERROR);
header("Pragma: no-cache");

$rootpath = dirname(__DIR__, 2);

include $rootpath."/inc/config.php";
include $rootpath."/inc/dbconnector.php";
include $rootpath."/inc/auth.php";
include $rootpath."/inc/func.php";
include $rootpath."/inc/settings.php";

$thisfile = basename( __FILE__ );

$action = $_POST['action'];

if ($action == "save") {

	//print_r($_POST);
	$company       = clean_all($_POST['company']);
	$company_full  = clean_all($_POST['company_full']);
	$company_site  = untag($_POST['company_site']);
	$company_mail  = untag($_POST['company_mail']);
	$company_phone = untag($_POST['company_phone']);
	$company_fax   = untag($_POST['company_fax']);
	$gkey          = $_POST['gkey'];
	$num_client    = untag($_POST['num_client']);
	$num_con       = untag($_POST['num_con']);
	$num_person    = untag($_POST['num_person']);
	$num_dogs      = $_POST['num_dogs'];
	$format_phone  = $_POST['format_phone'];
	$format_fax    = $_POST['format_fax'];
	$format_tel    = $_POST['format_tel'];
	$format_mob    = $_POST['format_mob'];
	$format_dogs   = $_POST['format_dogs'];
	$session       = $_POST['session'];
	$pogoda_code   = $_POST['pogoda_code'];
	$valuta        = $_POST['valuta'];
	$api_key       = $_POST['api_key'];
	$ipaccesse     = $_POST['ipaccesse'];
	$ipstart       = $_POST['ipstart'];
	$ipend         = $_POST['ipend'];
	$ipmask        = $_POST['ipmask'];
	$iplist        = str_replace(" ", "", $_POST['iplist']);
	$maxupload     = $_POST['maxupload'];
	$ext_allow     = $_POST['ext_allow'];
	$mailme        = $_POST['mailme'];
	$mailout       = $_POST['mailout'];

	$dNum            = $_POST['dNum'];
	$dFormat         = $_POST['dFormat'];
	$defaultDealName = $_POST['defaultDealName'];

	$partner_on   = $_POST['partner_on'];
	$concurent_on = $_POST['concurent_on'];
	$payment_on   = $_POST['payment_on'];
	$price_do     = $_POST['price_do'];
	$period_do    = $_POST['period_do'];
	$contract_do  = $_POST['contract_do'];
	$credit_day   = $_POST['credit_day'];
	$dog_day      = $_POST['dog_day'];

	/*
	$my_dir_name = $_POST['my_dir_name'];
	$my_dir_shot = $_POST['my_dir_shot'];
	$my_dir_status = $_POST['my_dir_status'];
	$dir_prava = $_POST['dir_prava'];
	*/

	$acs_view = $_POST['acs_view'];

	$complect_on = $_POST['complect_on'];
	$zayavka_on  = $_POST['zayavka_on'];
	$coordinator = $_POST['coordinator'];

	$contract_format = $_POST['contract_format'];
	$contract_num    = $_POST['contract_num'];
	$inum            = $_POST['inum'];
	$iformat         = $_POST['iformat'];

	$time_zone = $_POST['time_zone'];

	$other = $_POST['other'];

	$outClientUrl = $_POST['outClientUrl'];
	$outDealUrl   = $_POST['outDealUrl'];

	ksort($other);
	$other1 = [];

	//for ($i = 0; $i <= max(array_keys($other1)); $i++) {
	for ($i = 0; $i <= 50; $i++) {

		if ($other[ $i ] == '') $other1[ $i ] = 'no';
		elseif (!$other[ $i ]) $other1[ $i ] = 'no';
		else $other1[ $i ] = $other[ $i ];

	}

	//print_r($other1);

	$other = implode(";", $other1);

	$recv = untag(implode(";", (array)$_POST['recv']));

	//загрузка логотипа
	$ftitle    = basename($_FILES['logo']['name']);
	$file_orig = $_FILES['logo']['name'];
	$fname     = "logo_".translit($ftitle);
	$ftype     = $_FILES['logo']['type'];

	//поиск абсолютного пути сайта
	//$path  = getenv(SCRIPT_NAME);
	//$path1 = explode("/", $path);
	//if ($path1[1] == 'admin') $path = '';
	//if ($path1[1] != 'admin') $path = '/'.$path1[1];

	$result_set = $db -> getRow("select * from ".$sqlname."settings WHERE id = '$identity'");

	$uploaddir  = $rootpath.'/cash/logo/';
	$uploadfile = $uploaddir.$fname;

	$col            = 0;
	$file_ext_allow = explode(",", str_replace(" ", "", $ext_allow));
	$cur_ext        = texttosmall(getExtention($ftitle));

	if ( in_array( $cur_ext, $file_ext_allow, true ) ) $col = 1;

	if ($col > 0 && $ftitle != '') {

		if ((filesize($_FILES['logo']['tmp_name']) / 1000000) > $maxupload) {
			$message = 'Ошибка при загрузке файла <b>'.$ftitle.'</b>!<br /> <b class="yelw">Ошибка:</b> Превышает допустимые размеры!<br />';
		}
		elseif (move_uploaded_file($_FILES['logo']['tmp_name'], $uploadfile)) {

			$message = 'Файл <b>"'.$ftitle.'"</b> успешно загружен.<br />';
			$logo    = $fname;

		}
		else{
			$message = 'Ошибка при загрузке файла <b>'.$ftitle.'</b>!<br /> <b class="yelw">Ошибка:</b> '.$_FILES['file']['error'].'<br />';

		}

	}
	else {
		$logo = $result_set["logo"];
	}

	if ( ($ipaccesse == 'yes') && $ipstart == '' && $ipend == '' && $ipmask == '' && $iplist == '' ) {
		$ipaccesse = '';
	}


	$set = [
		'company'         => $company,
		'company_full'    => $company_full,
		'company_site'    => $company_site,
		'company_mail'    => $company_mail,
		'company_phone'   => $company_phone,
		'company_fax'     => $company_fax,
		//'gkey'            => $gkey,
		'num_client'      => $num_client,
		'num_con'         => $num_con,
		'num_person'      => $num_person,
		'num_dogs'        => $num_dogs,
		'format_phone'    => $format_phone,
		'format_fax'      => $format_fax,
		'format_tel'      => $format_tel,
		'format_mob'      => $format_mob,
		'format_dogs'     => $format_dogs,
		'session'         => $session,
		'valuta'          => $valuta,
		'ipaccesse'       => $ipaccesse,
		'ipstart'         => $ipstart,
		'ipend'           => $ipend,
		'ipmask'          => $ipmask,
		'iplist'          => $iplist,
		'maxupload'       => $maxupload,
		'api_key'         => $api_key,
		'ext_allow'       => $ext_allow,
		'mailme'          => $mailme,
		'mailout'         => $mailout,
		'other'           => $other,
		'logo'            => $logo,
		'outDealUrl'      => $outDealUrl,
		'outClientUrl'    => $outClientUrl,
		//'recv'            => $recv,
		'acs_view'        => $acs_view,
		'zayavka_on'      => $zayavka_on,
		'complect_on'     => $complect_on,
		'coordinator'     => $coordinator,
		'dNum'            => $dNum,
		'dFormat'         => $dFormat,
		'defaultDealName' => $defaultDealName,
		'timezone'        => $time_zone
	];

	$db -> query("UPDATE ".$sqlname."settings SET ?u WHERE id = '$identity'", $set);

	//print_r($_REQUEST['custom']);

	if( empty($_REQUEST['custom']['budjetEnableVijets']) ){
		$_REQUEST['custom']['budjetEnableVijets'] = 'no';
	}
	if( empty($_REQUEST['custom']['viewNotSelfTasks']) ){
		$_REQUEST['custom']['viewNotSelfTasks'] = 'no';
	}

	$r = customSettings('settingsMore', 'put', ['params' => $_REQUEST['custom']]);
	//print_r($r);

	$message .= "Сделано";

	unlink($rootpath."/cash/".$fpath."settings.all.json");
	unlink($rootpath."/cash/".$fpath."otherSettings.json");

	print $message;

}