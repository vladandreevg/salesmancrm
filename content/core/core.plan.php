<?php
/* ============================ */
/*         SalesMan CRM         */
/* ============================ */
/* (C) 2016 Vladislav Andreev   */
/*       SalesMan Project       */
/*        www.isaler.ru         */
/*        ver. 2019.x           */
/* ============================ */

error_reporting( E_ERROR );
header( "Pragma: no-cache" );

$rootpath = realpath( __DIR__.'/../../' );

include $rootpath."/inc/config.php";
include $rootpath."/inc/dbconnector.php";
include $rootpath."/inc/auth.php";
include $rootpath."/inc/settings.php";
include $rootpath."/inc/func.php";
include $rootpath."/developer/events.php";

function prenum($num) {

	return str_replace( ".", ",", $num );

}

function prep($string) {
	$string = str_replace( "|", "", $string );
	$string = trim( $string );

	return $string;
}

$year   = (int)$_REQUEST['year'];
$iduser = (int)$_REQUEST['iduser'];
$action = $_REQUEST['action'];

if ( $action == "edit" ) {

	$plan  = $_REQUEST['plan'];

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

	print 'Сделано';

	exit();

}

if ( $action == "edit.old" ) {

	//print_r($_REQUEST);

	$kol_plan[1]  = $_REQUEST['kol_plan_1'];
	$kol_plan[2]  = $_REQUEST['kol_plan_2'];
	$kol_plan[3]  = $_REQUEST['kol_plan_3'];
	$kol_plan[4]  = $_REQUEST['kol_plan_4'];
	$kol_plan[5]  = $_REQUEST['kol_plan_5'];
	$kol_plan[6]  = $_REQUEST['kol_plan_6'];
	$kol_plan[7]  = $_REQUEST['kol_plan_7'];
	$kol_plan[8]  = $_REQUEST['kol_plan_8'];
	$kol_plan[9]  = $_REQUEST['kol_plan_9'];
	$kol_plan[10] = $_REQUEST['kol_plan_10'];
	$kol_plan[11] = $_REQUEST['kol_plan_11'];
	$kol_plan[12] = $_REQUEST['kol_plan_12'];

	$marga[1]  = $_REQUEST['marga_1'];
	$marga[2]  = $_REQUEST['marga_2'];
	$marga[3]  = $_REQUEST['marga_3'];
	$marga[4]  = $_REQUEST['marga_4'];
	$marga[5]  = $_REQUEST['marga_5'];
	$marga[6]  = $_REQUEST['marga_6'];
	$marga[7]  = $_REQUEST['marga_7'];
	$marga[8]  = $_REQUEST['marga_8'];
	$marga[9]  = $_REQUEST['marga_9'];
	$marga[10] = $_REQUEST['marga_10'];
	$marga[11] = $_REQUEST['marga_11'];
	$marga[12] = $_REQUEST['marga_12'];

	for ( $i = 1; $i <= 12; $i++ ) {

		$plid = (int)$db -> getOne( "SELECT plid FROM ".$sqlname."plan WHERE iduser = '$iduser' and year =  '$year' and mon = '$i' and identity = '$identity'" );

		$kol_plan = pre_format( $kol_plan[ $i ] );
		$marga    = pre_format( $marga[ $i ] );

		if ( $plid == 0 ) {

			$db -> query( "INSERT INTO ".$sqlname."plan SET ?u", [
				"year"     => $year,
				"mon"      => $i,
				"iduser"   => $iduser,
				"kol_plan" => $kol_plan,
				"marga"    => $marga,
				"identity" => $identity
			] );

		}
		else {

			$db -> query( "UPDATE ".$sqlname."plan SET ?u WHERE plid = '$plid' and identity = '$identity'", [
				"year"     => $year,
				"mon"      => $i,
				"kol_plan" => $kol_plan,
				"marga"    => $marga
			] );

		}

	}

	print 'Сделано';

	exit();

}

if ( $action == "import" ) {

	$mess = $oborot = $marga = $plan = $xplan = [];

	//если загружается файл
	if ( filesize( $_FILES['file']['tmp_name'] ) > 0 ) {

		//разбираем запрос из файла
		$ftitle = basename( $_FILES['file']['name'] );
		$fname  = time().".".getExtention( $ftitle );//переименуем файл
		$ftype  = $_FILES['file']['type'];

		if ( $maxupload == '' ) {
			$maxupload = str_replace( [
				'M',
				'm'
			], '', @ini_get( 'upload_max_filesize' ) );
		}

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

			if ( (filesize( $_FILES['file']['tmp_name'] ) / 1000000) > $maxupload ) {
				$mess[] = 'Ошибка при загрузке файла <b>"'.$ftitle.'"</b> - Превышает допустимые размеры!';
			}
			elseif ( move_uploaded_file( $_FILES['file']['tmp_name'], $url ) ) {
				$res = "ok";
			}
			else {
				$mess[] = 'Ошибка при загрузке файла <b>"'.$ftitle.'"</b> - '.$_FILES['file']['error'].'<br />';
			}

		}
		else {
			$mess[] = 'Ошибка при загрузке файла <b>"'.$ftitle.'"</b> - Файлы такого типа не разрешено загружать.';
		}

		//print $url;
		//$d = parceExcel($url);
		//print_r($d);
		//exit();

		/*
		if ( $cur_ext == 'xls' ) {

			//require_once '../../opensource/excel_reader/excel_reader2.php';

			$datas = new Spreadsheet_Excel_Reader();
			$datas -> setOutputEncoding( 'UTF-8' );
			$datas -> read( $url, false );
			$data1 = $datas -> dumptoarray();//получили двумерный массив с данными

			for ( $j = 0, $jMax = count( (array)$data1 ); $j < $jMax; $j++ ) {

				for ( $g = 0, $gMax = count( (array)$data1[ $j + 1 ] ); $g < $gMax; $g++ ) {

					$plan[ $j ][] = untag( $data1[ $j + 1 ][ $g + 1 ] );

				}

			}

		}
		if ( $cur_ext == 'csv' || $cur_ext == 'xlsx' ) {

			$datas = new SpreadsheetReader( $url );
			$datas -> ChangeSheet(0);

			//print_r($datas);

			foreach ( $datas as $k => $Row ) {

				foreach ( $Row as $key => $value ) {

					$data[ $k ][] = ($cur_ext == 'csv') ? enc_detect( untag( $value ) ) : untag( $value );

				}

			}

			$plan = array_values( $data );

		}
		*/

		$plan = parceExcel( $url );

		$k = 0;
		for ( $i = 0, $iMax = count( $plan ); $i <= $iMax; $i++ ) {

			$g = 0;
			for ( $j = 0, $jMax = count( (array)$plan[ $i ] ); $j <= $jMax; $j++ ) {

				if ( $j != 1 && $j != 2 ) {

					if ( $i / 2 == ceil( $i / 2 ) ) {
						$oborot[ $k ][ $g ] = $plan[ $i ][ $j ];
					}
					else {
						$marga[ $k ][ $g ] = $plan[ $i ][ $j ];
					}

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

		//print_r($marga);
		//exit();

		$plus = $upd = $all = $plus = 0;


		if ( !empty( $oborot ) ) {

			foreach ( array_values( $oborot ) as $i => $row ) {

				$iduser = (int)$row[0];

				array_shift( $row );

				//print_r($row);

				if ( $iduser > 0 ) {

					foreach ( $row as $j => $item ) {

						$mon = $j + 1;

						if ( $j >= 12 ) {
							continue;
						}

						array_shift( $marga[ $i ] );

						$plid = (int)$db -> getOne( "SELECT plid FROM ".$sqlname."plan WHERE iduser = '$iduser' and year = '$year' and mon = '$mon' and identity = '$identity'" );

						if ( $plid == 0 ) {

							$plan = [
								"year"     => $year,
								"mon"      => $mon,
								"iduser"   => $iduser,
								"kol_plan" => (float)prenum( $item ),
								"marga"    => (float)prenum( $marga[ $i ][ $j ] ),
								"identity" => $identity
							];
							$db -> query( "INSERT INTO ".$sqlname."plan SET ?u", $plan );

							$plus++;

						}
						else {

							$plan = [
								"kol_plan" => (float)prenum( $item ),
								"marga"    => (float)prenum( $marga[ $i ][ $j ] )
							];
							$db -> query( "UPDATE ".$sqlname."plan SET ?u WHERE plid = '$plid' and identity = '$identity'", $plan );

							$upd++;

						}

						$all++;

					}

				}

			}

		}


		/*
		if (!empty($oborot)) {

			for ( $i = 0, $iMax = count( $oborot ); $i < $iMax; $i++) {

				$iduser = (int)$oborot[ $i ][0];

				if($iduser == 0){
					continue;
				}

				for ( $j = 1; $j <= 12; $j++) {

					$result = $db -> getRow("SELECT *, COUNT(plid) as count FROM ".$sqlname."plan WHERE iduser = '$iduser' and year = '$year' and mon = '$j' and identity = '$identity'");
					$plid   = $result["plid"];
					$count  = $result["count"];

					if ($count < 1 && $plid < 1) {

						$plan = [
							"year"     => $year,
							"mon"      => $j,
							"iduser"   => $iduser,
							"kol_plan" => pre_format($oborot[ $i ][ $j ]) + 0,
							"marga"    => pre_format($marga[ $i ][ $j ]) + 0,
							"identity" => $identity
						];
						$db -> query("INSERT INTO ".$sqlname."plan SET ?u", $plan);

						$plus++;
						$all++;

					}
					else {

						$plan = [
							"kol_plan" => pre_format($oborot[ $i ][ $j ]) + 0,
							"marga"    => pre_format($marga[ $i ][ $j ]) + 0
						];
						$db -> query("UPDATE ".$sqlname."plan SET ?u WHERE plid = '$plid' and identity = '$identity'", $plan);

						$upd++;
						$all++;

					}

				}

			}

		}
		*/

	}

	$mess[] = 'Всего обработано: <b>'.count( $oborot ).'</b> сотрудников<br>Обновлено: <b>'.$upd.' записей</b><br>Добавлено: <b>'.$plus.' записей</b>';

	print yimplode( "<br>", $mess );

	exit();

}

if ( $action == "export" ) {

	$string = [];

	$dname  = [];
	$result = $db -> query( "SELECT * FROM ".$sqlname."field WHERE fld_tip='dogovor' AND fld_on='yes' and identity = '$identity' ORDER BY fld_order" );
	while ($data = $db -> fetch( $result )) {
		$dname[ $data['fld_name'] ] = $data['fld_title'];
	}

	$mth = [];
	for ( $m = 1; $m <= 12; $m++ ) {
		$mth[] = ru_month( $m );
	}

	//$mth = implode( ";", $mth );

	$string[] = array_merge( [
		"UserID",
		"Ответственный",
		"Показатель"
	], $mth );

	$i    = 1;
	$year = $_REQUEST['year'];

	#Компания
	$result = $db -> query( "SELECT * FROM ".$sqlname."user WHERE tip='Руководитель организации' and acs_plan = 'on' and secrty = 'yes' and identity = '$identity' ORDER BY bid, mid" );
	while ($data = $db -> fetch( $result )) {

		for ( $m = 1; $m <= 12; $m++ ) {

			$res        = $db -> getRow( "SELECT SUM(kol_plan) as kol_all, SUM(marga) as marga_all FROM ".$sqlname."plan WHERE iduser = '".$data['iduser']."' and year='".$year."' and mon='".$m."' and identity = '$identity'" );
			$kol[ $m ]  = prenum( $res["kol_all"] );
			$marg[ $m ] = prenum( $res["marga_all"] );

		}

		$string[] = array_merge( [
			$data['iduser'],
			current_user( $data['iduser'] ),
			$dname['oborot']
		], $kol );
		$string[] = array_merge( [
			$data['iduser'],
			current_user( $data['iduser'] ),
			$dname['marg']
		], $marg );

		$i = $i + 2;

		#Подразделение
		$res = $db -> query( "SELECT * FROM ".$sqlname."user WHERE mid='".$data['iduser']."' and tip!='Поддержка продаж' and acs_plan = 'on' and identity = '$identity' ORDER BY bid, mid" );
		while ($data0 = $db -> fetch( $res )) {

			for ( $m = 1; $m <= 12; $m++ ) {

				$res0        = $db -> getRow( "SELECT SUM(kol_plan) as kol_all, SUM(marga) as marga_all FROM ".$sqlname."plan WHERE iduser = '".$data0['iduser']."' and year='".$year."' and mon='".$m."' and identity = '$identity'" );
				$kol0[ $m ]  = (float)($res0["kol_all"]);
				$marg0[ $m ] = (float)($res0["marga_all"]);

			}

			$string[] = array_merge( [
				$data0['iduser'],
				"|  ".current_user( $data0['iduser'] ),
				$dname['oborot']
			], $kol0 );
			$string[] = array_merge( [
				$data0['iduser'],
				"|  ".current_user( $data0['iduser'] ),
				$dname['marg']
			], $marg0 );

			$i = $i + 2;

			#Отдел
			$result_2 = $db -> query( "SELECT * FROM ".$sqlname."user WHERE mid='".$data0['iduser']."' and tip!='Поддержка продаж' and acs_plan = 'on' and secrty = 'yes' and identity = '$identity' ORDER BY bid, mid" );
			while ($data2 = $db -> fetch( $result_2 )) {

				for ( $m = 1; $m <= 12; $m++ ) {

					$result2     = $db -> getRow( "SELECT SUM(kol_plan) as kol_all, SUM(marga) as marga_all FROM ".$sqlname."plan WHERE iduser = '".$data2['iduser']."' and year='".$year."' and mon='".$m."' and identity = '$identity'" );
					$kol2[ $m ]  = (float)($result2["kol_all"]);
					$marg2[ $m ] = (float)($result2["marga_all"]);

				}

				$string[] = array_merge( [
					$data2['iduser'],
					"  ||  ".current_user( $data2['iduser'] ),
					$dname['oborot']
				], $kol2 );
				$string[] = array_merge( [
					$data2['iduser'],
					"  ||  ".current_user( $data2['iduser'] ),
					$dname['marg']
				], $marg2 );
				$i        = $i + 2;

				#Сотрудники отдела
				$result_3 = $db -> query( "SELECT * FROM ".$sqlname."user WHERE tip='Менеджер продаж' and mid='".$data2['iduser']."' and acs_plan = 'on' and secrty = 'yes' and identity = '$identity' ORDER BY bid, mid" );
				while ($data3 = $db -> fetch( $result_3 )) {

					for ( $m = 1; $m <= 12; $m++ ) {
						$result3     = $db -> getRow( "SELECT SUM(kol_plan) as kol_all, SUM(marga) as marga_all FROM ".$sqlname."plan WHERE iduser = '".$data3['iduser']."' and year='".$year."' and mon='".$m."' and identity = '$identity'" );
						$kol3[ $m ]  = (float)($result3["kol_all"]);
						$marg3[ $m ] = (float)($result3["marga_all"]);
					}

					$string[] = array_merge( [
						$data3['iduser'],
						"    |||    ".current_user( $data3['iduser'] ),
						$dname['oborot']
					], $kol3 );
					$string[] = array_merge( [
						$data3['iduser'],
						"    |||    ".current_user( $data3['iduser'] ),
						$dname['marg']
					], $marg3 );

					$i = $i + 2;
				}
				##Сотрудники отдела
			}
			##Отдел
		}
		##Подразделение
	}
	##Компания

	//print_r($string);
	//exit();

	//проходим массив и формируем csv-файл
	$filename = 'plan'.$_REQUEST['year'].$identity.'.xlsx';

	Shuchkin\SimpleXLSXGen ::fromArray( $string ) -> downloadAs( $filename );

	exit();

}