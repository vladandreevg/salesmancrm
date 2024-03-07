<?php
/* ============================ */
/*         SalesMan CRM         */
/* ============================ */
/* (C) 2018 Vladislav Andreev   */
/*       SalesMan Project       */
/*        www.isaler.ru         */
/*        ver. 2018.x           */

/* ============================ */

use Salesman\Metrics;

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

$year   = (int)$_REQUEST['year'];
$iduser = (int)$_REQUEST['iduser'];
$action = $_REQUEST['action'];

function prenum($num) {

	return str_replace( ".", ",", $num );

}

/**
 * Управление планом пользователя
 */
if ( $action == "edit.plan" ) {

	$users = (array)$_REQUEST['iduser'];
	$plan  = $_REQUEST['plan'];

	foreach ( $users as $iduser ) {

		foreach ( $plan as $mon => $val ) {

			$id = (int)$db -> getOne( "SELECT plid FROM ".$sqlname."plan WHERE iduser = '$iduser' and year = '$year' and mon = '$mon' and identity = '$identity'" );

			if ( $id == 0 ) {

				$db -> query( "INSERT INTO ".$sqlname."plan SET ?u", [
					'year'     => (int)$year,
					'mon'      => (int)$mon,
					'iduser'   => (int)$iduser,
					'kol_plan' => pre_format( $val['summa'] ),
					'marga'    => pre_format( $val['marga'] ),
					'identity' => $identity
				] );

			}
			else {

				$db -> query( "UPDATE ".$sqlname."plan SET ?u WHERE plid = '$id' and identity = '$identity'", [
					'kol_plan' => pre_format( $val['summa'] ),
					'marga'    => pre_format( $val['marga'] ),
					'identity' => $identity
				] );

			}

		}

	}

	print json_encode_cyr(["result" => 'Сделано']);

	exit();

}

/**
 * Загрузка планов для сотрудников
 */
if ( $action == "import.plan" ) {

	$mess = $oborot = $marga = [];

	//если загружается файл
	if ( filesize( $_FILES['file']['tmp_name'] ) > 0 ) {

		//разбираем запрос из файла
		$ftitle = basename( $_FILES['file']['name'] );
		$fname  = time().".".getExtention( $ftitle );//переименуем файл
		$ftype  = $_FILES['file']['type'];

		if ( $maxupload == '' )
			$maxupload = str_replace( [
				'M',
				'm'
			], '', @ini_get( 'upload_max_filesize' ) );

		$uploaddir = $rootpath.'/files/'.$fpath;
		$url       = $uploaddir.$fname;

		$ext_allow = [
			'csv',
			'xls',
			'xlsx'
		];
		$cur_ext   = texttosmall( getExtention( $ftitle ) );

		$res = 'error';

		//проверим тип файла на поддерживаемые типы
		if ( in_array( $cur_ext, $ext_allow ) ) {

			if ( (filesize( $_FILES['file']['tmp_name'] ) / 1000000) > $maxupload )
				$mess[] = 'Ошибка при загрузке файла <b>"'.$ftitle.'"</b> - Превышает допустимые размеры!';

			else {

				if ( move_uploaded_file( $_FILES['file']['tmp_name'], $url ) )
					$res = "ok";

				else
					$mess[] = 'Ошибка при загрузке файла <b>"'.$ftitle.'"</b> - '.$_FILES['file']['error'].'<br />';

			}

		}
		else
			$mess[] = 'Ошибка при загрузке файла <b>"'.$ftitle.'"</b> - Файлы такого типа не разрешено загружать.';


		if ( $cur_ext == 'xls' ) {

			$datas = new Spreadsheet_Excel_Reader();
			$datas -> setOutputEncoding( 'UTF-8' );
			$datas -> read( $url, false );
			$data1 = $datas -> dumptoarray();//получили двумерный массив с данными

			for ( $j = 0, $jMax = count( $data1 ); $j < $jMax; $j++ ) {

				for ( $g = 0, $gMax = count( $data1[ $j + 1 ] ); $g < $gMax; $g++ ) {

					$plan[ $j ][] = untag( $data1[ $j + 1 ][ $g + 1 ] );

				}

			}

		}
		if ( $cur_ext == 'csv' || $cur_ext == 'xlsx' ) {

			$datas = new SpreadsheetReader( $url );
			$datas -> ChangeSheet( 0 );

			foreach ( $datas as $k => $Row ) {

				foreach ( $Row as $key => $value ) {

					$data[ $k ][] = ($cur_ext == 'csv') ? enc_detect( untag( $value ) ) : untag( $value );

				}

			}

			$plan = array_values( $data );

		}

		$k = 0;
		for ( $i = 1, $iMax = count( $plan ); $i <= $iMax; $i++ ) {

			$g = 0;
			for ( $j = 0, $jMax = count( $plan[ $i ] ); $j <= $jMax; $j++ ) {

				if ( $j != 1 && $j != 2 ) {

					if ( $i / 2 == ceil( $i / 2 ) )
						$oborot[ $k ][ $g ] = $plan[ $i ][ $j ];

					else
						$marga[ $k ][ $g ] = $plan[ $i ][ $j ];

					$g++;

				}

			}

			$k++;

		}

		//загружаем массив. колонка №1 - Ответственный сотрудник
		$oborot = array_values( $oborot );
		$marga  = array_values( $marga );

		//конец загрузки спеки из поля
		unlink( $url );

		//print_r($oborot);
		//exit();

		$plus = $upd = $all = $plus = 0;

		if ( !empty( $oborot ) ) {

			for ( $i = 0, $iMax = count( $oborot ); $i < $iMax; $i++ ) {

				$iduser = $oborot[ $i ][0];

				for ( $j = 1, $jMax = count( $oborot[ $i ] ); $j <= $jMax; $j++ ) {

					$result = $db -> getRow( "SELECT *, COUNT(plid) as count FROM ".$sqlname."plan WHERE iduser = '$iduser' and year = '$year' and mon = '$j' and identity = '$identity'" );
					$plid   = $result["plid"];
					$count  = $result["count"];

					if ( $count < 1 && $plid < 1 ) {

						$plan = [
							"year"     => $year,
							"mon"      => $j,
							"iduser"   => $iduser,
							"kol_plan" => pre_format( $oborot[ $i ][ $j ] ) + 0,
							"marga"    => pre_format( $marga[ $i ][ $j ] ) + 0,
							"identity" => $identity
						];
						$db -> query( "INSERT INTO ".$sqlname."plan SET ?u", $plan );

						$plus++;

					}
					else {

						$plan = [
							"kol_plan" => pre_format( $oborot[ $i ][ $j ] ) + 0,
							"marga"    => pre_format( $marga[ $i ][ $j ] ) + 0
						];
						$db -> query( "UPDATE ".$sqlname."plan SET ?u WHERE plid = '$plid' and identity = '$identity'", $plan );

						$upd++;

					}
					$all++;

				}

			}

		}

	}

	$mess[] = 'Всего обработано: <b>'.count( $oborot ).'</b> сотрудников<br>Обновлено: <b>'.$upd.' записей</b><br>Добавлено: <b>'.$plus.' записей</b>';

	print yimplode( "<br>", $mess );

	exit();

}

/**
 * Выгрузка планов для сотрудников
 */
if ( $action == "export.plan" ) {

	$dname  = [];
	$result = $db -> query( "SELECT * FROM ".$sqlname."field WHERE fld_tip='dogovor' AND fld_on='yes' and identity = '$identity' ORDER BY fld_order" );
	while ($data = $db -> fetch( $result )) {
		$dname[ $data['fld_name'] ] = $data['fld_title'];
	}

	$mth = [];
	for ( $m = 1; $m <= 12; $m++ ) {
		$mth[] = ru_month( $m );
	}
	$mth = implode( ";", $mth );

	$xstring[] = "UserID;Ответственный;Показатель;".$mth;

	$i = 1;

	#Компания
	$result = $db -> query( "SELECT * FROM ".$sqlname."user WHERE tip='Руководитель организации' and acs_plan = 'on' and secrty = 'yes' and identity = '$identity' ORDER BY bid, mid" );
	while ($data = $db -> fetch( $result )) {

		for ( $m = 1; $m <= 12; $m++ ) {

			$res        = $db -> getRow( "SELECT SUM(kol_plan) as kol_all, SUM(marga) as marga_all FROM ".$sqlname."plan WHERE iduser = '".$data['iduser']."' and year='".$year."' and mon='".$m."' and identity = '$identity'" );
			$kol[ $m ]  = prenum( $res["kol_all"] );
			$marg[ $m ] = prenum( $res["marga_all"] );

		}

		$xstring[] = $data['iduser'].";".current_user( $data['iduser'] ).";".$dname['oborot'].";".implode( ";", $kol );
		$xstring[] = $data['iduser'].";".current_user( $data['iduser'] ).";".$dname['marg'].";".implode( ";", $marg );
		$i         += 2;

		#Подразделение
		$res = $db -> query( "SELECT * FROM ".$sqlname."user WHERE mid='".$data['iduser']."' and tip!='Поддержка продаж' and acs_plan = 'on' and identity = '$identity' ORDER BY bid, mid" );
		while ($data0 = $db -> fetch( $res )) {

			$kol0 = $marg0 = [];

			for ( $m = 1; $m <= 12; $m++ ) {

				$res0        = $db -> getRow( "SELECT SUM(kol_plan) as kol_all, SUM(marga) as marga_all FROM ".$sqlname."plan WHERE iduser = '".$data0['iduser']."' and year='".$year."' and mon='".$m."' and identity = '$identity'" );
				$kol0[ $m ]  = prenum( $res0["kol_all"] );
				$marg0[ $m ] = prenum( $res0["marga_all"] );

			}

			$xstring[] = $data0['iduser'].";|  ".current_user( $data0['iduser'] ).";".$dname['oborot'].";".implode( ";", $kol0 );
			$xstring[] = $data0['iduser'].";|  ".current_user( $data0['iduser'] ).";".$dname['marg'].";".implode( ";", $marg0 );
			$i         += 2;

			#Отдел
			$result_2 = $db -> query( "SELECT * FROM ".$sqlname."user WHERE mid='".$data0['iduser']."' and tip!='Поддержка продаж' and acs_plan = 'on' and secrty = 'yes' and identity = '$identity' ORDER BY bid, mid" );
			while ($data2 = $db -> fetch( $result_2 )) {

				$kol2 = $marg2 = [];

				for ( $m = 1; $m <= 12; $m++ ) {

					$result2     = $db -> getRow( "SELECT SUM(kol_plan) as kol_all, SUM(marga) as marga_all FROM ".$sqlname."plan WHERE iduser = '".$data2['iduser']."' and year='".$year."' and mon='".$m."' and identity = '$identity'" );
					$kol2[ $m ]  = prenum( $result2["kol_all"] );
					$marg2[ $m ] = prenum( $result2["marga_all"] );

				}

				$xstring[] = $data2['iduser'].";  ||  ".current_user( $data2['iduser'] ).";".$dname['oborot'].";".implode( ";", $kol2 );
				$xstring[] = $data2['iduser'].";  ||  ".current_user( $data2['iduser'] ).";".$dname['marg'].";".implode( ";", $marg2 );
				$i         += 2;

				#Сотрудники отдела
				$result_3 = $db -> query( "SELECT * FROM ".$sqlname."user WHERE tip='Менеджер продаж' and mid='".$data2['iduser']."' and acs_plan = 'on' and secrty = 'yes' and identity = '$identity' ORDER BY bid, mid" );
				while ($data3 = $db -> fetch( $result_3 )) {

					$kol3 = $marg3 = [];

					for ( $m = 1; $m <= 12; $m++ ) {

						$result3     = $db -> getRow( "SELECT SUM(kol_plan) as kol_all, SUM(marga) as marga_all FROM ".$sqlname."plan WHERE iduser = '".$data3['iduser']."' and year='".$year."' and mon='".$m."' and identity = '$identity'" );
						$kol3[ $m ]  = $result3["kol_all"];
						$marg3[ $m ] = $result3["marga_all"];

					}
					$xstring[] = $data3['iduser'].";    |||    ".current_user( $data3['iduser'] ).";".$dname['oborot'].";".implode( ";", $kol3 );
					$xstring[] = $data3['iduser'].";    |||    ".current_user( $data3['iduser'] ).";".$dname['marg'].";".implode( ";", $marg3 );
					$i         += 2;
				}
				##Сотрудники отдела
			}
			##Отдел
		}
		##Подразделение
	}
	##Компания

	$string2 = implode( "\n", $string );

	$content = iconv( "UTF-8", "CP1251", $string2 );
	//проходим массив и формируем csv-файл
	$filename = 'plan'.$_REQUEST['year'].$identity.'.csv';
	$handle   = fopen( $rootpath."/files/".$fpath.$filename, 'wb' );

	fwrite( $handle, $content );
	fclose( $handle );
	header( 'Content-type: application/csv' );
	header( 'Content-Disposition: attachment; filename="'.$filename.'"' );

	readfile( $rootpath."/files/".$fpath.$filename );
	unlink( $rootpath."/files/".$fpath.$filename );

	exit();

}

/**
 * Варианты для конкретного показателя
 */
if ( $action == "get.KPIvariants" ) {

	$tip = $_REQUEST['tip'];

	$subitems = [];

	$items = Metrics ::getElements( $tip );

	if ( !empty( $items ) )
		$subitems = Metrics ::MetricSubList( $tip );

	print json_encode_cyr( [
		"items" => $items,
		"sub"   => $subitems
	] );

	exit();

}

/**
 * Управление базовыми показателями
 */
if ( $action == "edit.kpiBase" ) {

	$id                  = (int)$_REQUEST['id'];
	$params['tip']       = $_REQUEST['tip'];
	$params['title']     = $_REQUEST['title'];
	$params['values']    = $_REQUEST['values'];
	$params['subvalues'] = $_REQUEST['subvalues'];

	$r      = new Metrics();
	$result = $r -> saveKPIbase( (int)$id, $params );

	print json_encode_cyr( $result );

	exit();

}
if ( $action == "delete.kpiBase" ) {

	$id = (int)$_REQUEST['id'];

	$r      = new Metrics();
	$result = $r -> deleteKPIBase( $id );

	print $result;

	exit();

}

/**
 * Управление показателями сотрудника
 */
if ( $action == "edit.kpi" ) {

	$id                   = (int)$_REQUEST['id'];
	$params['kpi']        = (int)$_REQUEST['kpi'];
	$params['year']       = (int)$_REQUEST['year'];
	$params['period']     = $_REQUEST['period'];
	$params['iduser']     = (int)$_REQUEST['iduser'];
	$params['val']        = $_REQUEST['val'];
	$params['isPersonal'] = $_REQUEST['isPersonal'];
	$users                = $_REQUEST['users'];

	//print_r($params);

	$res = [];

	$r      = new Metrics();
	$result = $r -> saveKPI( $id, $params );

	$addon = 0;

	if ( !empty( $users ) ) {

		foreach ( $users as $iduser ) {

			$params['iduser'] = $iduser;

			$res = $r -> saveKPI( $id, $params );

			if ( (int)$res['id'] > 0 ) {

				$addon++;

			}

		}

	}

	if ( $addon > 0 ) {

		$result['result'] .= "<br>Добавлено ещё для $addon сотрудников";

	}

	print json_encode_cyr( $result );

	exit();

}
if ( $action == "delete.kpi" ) {

	$id = (int)$_REQUEST['id'];

	$r      = new Metrics();
	$result = $r -> deleteKPI( $id );

	print $result;

	exit();

}

/*Управление сезонными коэффициентами*/
if ( $action == "edit.season" ) {

	//print_r($_REQUEST);

	$metrika = new Metrics();
	$result  = $metrika -> setSeason( (int)$_REQUEST['year'], ["rate" => $_REQUEST['season']] );

	print json_encode_cyr( ["result" => $result] );

}