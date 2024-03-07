<?php
/* ============================ */
/*         SalesMan CRM         */
/* ============================ */
/* (C) 2016 Vladislav Andreev   */
/*       SalesMan Project       */
/*        www.isaler.ru         */
/*        ver. 2017.x           */
/* ============================ */

use Salesman\Speka;
use Salesman\Storage;
use Salesman\Upload;

error_reporting( E_ERROR );
ini_set('display_errors', 1);
header( "Pragma: no-cache" );

$rootpath = dirname( __DIR__, 2 );

include $rootpath."/inc/config.php";
include $rootpath."/inc/dbconnector.php";
include $rootpath."/inc/auth.php";
include $rootpath."/inc/settings.php";
include $rootpath."/inc/func.php";
include $rootpath."/developer/events.php";

$did    = (int)$_REQUEST['did'];
$spid   = (int)$_REQUEST['spid'];
$action = $_REQUEST['action'];

if ( $action == 'edit' ) {

	$params = $_REQUEST;

	$speka = new Speka();

	if ( $spid > 0 ) {

		$params['event'] = true;

		$rez = $speka -> edit( $spid, $params );

	}
	else {

		$params['event'] = !isset( $params['event'] ) && $params['event'] != "false";
		$params['kol'] = pre_format( $params['kol'] );

		$rez = $speka -> edit( 0, $params );

	}

	$rezult = [
		"result" => $rez['result']."<br>".yimplode( "<br>", $rez['text'] ),
		"error"  => $rez['error']
	];

	print json_encode_cyr( $rezult );

}

if ( $action == "delete" ) {

	$params          = $_REQUEST;
	$params['event'] = true;

	$speka = new Speka();
	$rez = $speka -> delete( $spid, $params );

	print json_encode_cyr( [
		"result" => $rez['result']."<br>".yimplode( "<br>", $rez['text'] ),
		"error"  => $rez['error']
	] );

	exit();

}

if ( $action == "change.calculate" ) {

	$db -> query( "UPDATE {$sqlname}dogovor SET calculate = 'yes' WHERE did = '$did' and identity = '$identity'" );

	print '{"result":"Сделано","error":"'.$err.'"}';

	exit();

}
if ( $action == "change.recalculate" ) {

	reCalculate( $did );

	exit();

}

if ( $action == "export" ) {

	$i      = 1;
	$err    = 0;
	$num    = 0;
	$string = [];


	$s = [
		"Наименование",
		"Ед.изм.",
		"НДС,%",
		"Кол-во",
		($otherSettings['dop'] ? $otherSettings['dopName'] : ''),
		"Стоимость ед.",
		"Cумма,{$valuta}",
		"Примечание"
	];

	// доп.поля характеристик (настраиваемые)
	$fields = Storage::getFields();
	foreach ($fields as $fieldid => $field){
		$s[] = $field['name'];
	}
	$string[] = $s;

	$result_s = $db -> query( "SELECT * FROM {$sqlname}speca WHERE did = '$did' and identity = '$identity' ORDER BY spid" );
	while ($data = $db -> fetch( $result_s )) {

		$dop = '';

		$kol_sum = pre_format( $data['kol'] ) * pre_format( $data['price'] ) * pre_format( $data['dop'] );
		$in_sum  = pre_format( $data['price_in'] ) * pre_format( $data['kol'] ) * pre_format( $data['dop'] );

		if ( $show_marga == 'yes' && $otherSettings['marga'] ) {
			$in_sum = '';
		}

		if ( $otherSettings['dop'] ) {
			$dop = $data['dop'];
		}

		$x = [
			"[".$data['artikul']."] ".$data['title'],
			$data['edizm'],
			pre_format( $data['nds'] ),
			pre_format( $data['kol'] ),
			$dop,
			$data['price'],
			$kol_sum,
			$data['comments']
		];

		foreach ($fields as $fielid => $field){
			$x[] = $db -> getOne( "SELECT value FROM {$sqlname}modcatalog_field WHERE n_id = '$data[prid]' and pfid = '$fielid' and identity = '$identity'" );
		}

		$string[] = $x;

	}
	
	Shuchkin\SimpleXLSXGen::fromArray( $string )->downloadAs("specification{$did}.xlsx");

	exit();
}

if ( $action == "import" ) {

	$mess = '';
	$err  = [];

	//разбираем запрос и формируем в виде массива
	$content = $_REQUEST['content'];

	//наличие заголовка
	$hdr = $_REQUEST['hdr'];
	//строка итогов
	$stri = $_REQUEST['stri'];
	//колонка номеров строк
	$frst = $_REQUEST['frst'];

	if ( $content ) {

		$strings = explode( "\n", $content ); //строки собираем в массив

		$speca = [];

		//переберем элементы массива и каждую строку разобъем также на массивы
		foreach ( $strings as $string ) {
			$speca[] = explode("\t", $string);
		}

		$good = 0;

		// удаляем заголовок
		if($hdr == 'yes'){
			array_shift( $speca );
		}

		//удаляем строку итогов
		if ( $stri == 'yes' ) {
			array_pop($speca);
		}

		//загружаем массив как спецификацию
		foreach ( $speca as $index => $item ) {

			// удаляем первый элемент
			if($frst == 'yes'){
				array_shift($item);
			}

			// удаляем последний элемент

			if ( $item[0] != '' ) {

				//ищем в прайсе по названию
				$prid = $db -> getOne( "SELECT n_id FROM {$sqlname}price WHERE title = '".$item[0]."' and identity = '$identity'" );

				$params = [
					'did'      => $did,
					'prid'     => $prid,
					'title'    => $item[0],
					'edizm'    => $item[1],
					'nds'      => pre_format( $item[2] ),
					'kol'      => pre_format( $item[3] ),
					'price'    => pre_format( $item[4] ),
					'price_in' => pre_format( $item[6] )
				];

				$speka = new Speka();

				$rez = $speka -> edit( 0, $params );

				if ( $rez['result'] != 'Error' ) {
					$good++;
				}

			}

		}

		reCalculate( $did );

	}
	//конец загрузки спеки из поля

	$ext = getExtention( $_FILES['file']['name'] );

	//если загружается файл
	if ( filesize( $_FILES['file']['tmp_name'] ) > 0 && in_array( $ext, [
			"csv",
			"xls",
			"xlsx"
		] ) ) {

		$ext_allow = 'csv,xls,xlsx';

		$upload = Upload ::upload();

		$err = array_merge( $err, $upload['message'] );

		foreach ( $upload['data'] as $file ) {

			$arg = [
				'ftitle'   => $file['title'],
				'fname'    => $file['name'],
				'ftype'    => $file['type'],
				'iduser'   => $iduser1,
				'clid'     => $clid,
				'pid'      => $pid,
				'did'      => $did,
				'coid'     => $coid,
				'identity' => $identity
			];

			$uploaddir  = $rootpath.'/files/'.$fpath;
			$uploadfile = $uploaddir.$file['name'];

			$ext = getExtention( $file['name'] );

			//обрабатываем данные из файла
			$speca = parceExcel($uploadfile);

			/*
			if ( $ext == 'csv' ) {

				$uploadfile = fopen( $url, 'rb');

				while (($data = fgetcsv( $uploadfile, 1000, ";" )) !== false) {

					$speca[] = implode( ";", $data );

				}

			}
			elseif ( $ext == 'xls' ) {

				//require_once '../../opensource/excel_reader/excel_reader2.php';

				$data = new Spreadsheet_Excel_Reader();
				$data -> setOutputEncoding( 'UTF-8' );
				$data -> read( $uploadfile, false );
				$speca = $data -> dumptoarray();//получили двумерный массив с данными

			}
			elseif ( $ext == 'xlsx' ) {

				$data = new SpreadsheetReader( $uploadfile );
				$data -> ChangeSheet( 0 );

				$speca = [];

				foreach ( $data as $k => $Row ) {

					foreach ( $Row as $key => $value ) {

						$speca[ $k ][] = $value;

					}

				}

			}
			*/

			//print_r($speca);
			//exit();

			//переиндексируем массив
			$speca = array_values( $speca );

			//убираем заголовок
			if ( $hdr == 'yes' )
				array_shift( $speca );

			//удаляем строку итогов
			if ( $stri == 'yes' )
				array_pop( $speca );

			//формируем конечный массив
			foreach ( $speca as $i => $string ) {

				//убираем колонку с номером строки
				if ( $frst == 'yes' )
					array_shift( $string );

				$speca[ $i ] = array_values( $string );

			}

			//print_r($speca);
			//exit();

			//загружаем массив как спецификацию
			foreach ( $speca as $string ) {

				if ( $string[0] != '' ) {

					//ищем в прайсе по названию
					$prid = $db -> getOne( "SELECT n_id FROM {$sqlname}price WHERE title = '".$string[0]."' and identity = '$identity'" );

					$arg = [
						'did'      => $did,
						'prid'     => $prid,
						'title'    => $string[0],
						'edizm'    => $string[1],
						'nds'      => $string[2],
						'kol'      => pre_format( $string[3] ),
						'price'    => $string[4],
						'price_in' => $string[6],
						'identity' => $identity
					];

					$speka = new Speka();

					$rez = $speka -> edit( 0, $arg );

					if ( $rez['result'] != 'Error' )
						$good++;

				}

			}

			reCalculate( $did );

			//конец загрузки спеки из поля
			unlink( $uploadfile );

		}

	}
	elseif ( filesize( $_FILES['file']['tmp_name'] ) > 0 )
		$err[] = "Разрешены только файлы CSV или XLS";

	event ::fire( 'deal.edit', $args = [
		"did"     => $did,
		"autor"   => $iduser1,
		"comment" => "Изменена спецификация"
	] );

	$err = 'Загружено: <b>'.$good.'</b> позиций.<br>'.implode( "<br>", $err );

	print '{"result":"Сделано","error":"'.$err.'"}';

	exit();
}