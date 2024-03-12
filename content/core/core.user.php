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
ini_set('display_errors', 1);
header("Pragma: no-cache");

$action = $_REQUEST['action'];

$rootpath = dirname(__DIR__, 2);

include $rootpath."/inc/config.php";
include $rootpath."/inc/dbconnector.php";
include $rootpath."/inc/auth.php";
include $rootpath."/inc/settings.php";
include $rootpath."/inc/func.php";
include $rootpath."/developer/events.php";

if($action == "get.select"){

	$tar    = $_REQUEST['tar'];
	$filter = $_REQUEST['filter'];
	$iduser = $_REQUEST['iduser'];

	if ($filter != '') $tar = $filter;

	$sort = ($tar == 'otdel') ? get_people($iduser1) : "";

	$result = $db -> getAll("select * from ".$sqlname."user WHERE title != '' ".$sort." and identity = '$identity' ORDER by title");
	?>
	<OPTION value="">Все</OPTION>
	<?php
	foreach ($result as $data){

		$s = ($data['iduser'] == $iduser) ? "selected" : '';
		print '<OPTION value="'.$data['iduser'].'" '.$s.'>'.$data['title'].'</OPTION>';

	}

	exit();

}
?>