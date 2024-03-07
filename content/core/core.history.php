<?php
/* ============================ */
/*         SalesMan CRM         */
/* ============================ */
/* (C) 2016 Vladislav Andreev   */
/*       SalesMan Project       */
/*        www.isaler.ru         */
/*        ver. 2017.x           */
/* ============================ */

use Salesman\Todo;
use Salesman\Upload;

error_reporting( E_ERROR );
ini_set('display_errors', 1);
header( "Pragma: no-cache" );

$action = $_REQUEST['action'];

if ( $action == 'setparam' ) {

	$nolog = $_REQUEST['nolog'] != 'yes' ? 'no' : 'yes';
	setcookie( "nolog", $nolog, time() + 31536000, "/", ".".$_SERVER['HTTP_HOST'] );
	exit();

}

$rootpath = dirname( __DIR__, 2 );

include $rootpath."/inc/config.php";
include $rootpath."/inc/dbconnector.php";
include $rootpath."/inc/auth.php";
include $rootpath."/inc/settings.php";
include $rootpath."/inc/func.php";
include $rootpath."/developer/events.php";


$rezult = [
	'ANSWERED'  => 'Отвеченный',
	'NO ANSWER' => 'Не отвечен',
	'BUSY'      => 'Занято'
];
$colors = [
	'ANSWERED'  => 'green',
	'NO ANSWER' => 'red',
	'BUSY'      => 'broun'
];

if ( $action == "edit" ) {

	$cid  = (int)$_REQUEST['cid'];

	$_REQUEST['datum'] = str_replace( [
		"T",
		"Z"
	], [
		" ",
		""
	], $_REQUEST['datum'] );

	$post = $_REQUEST;

	if($cid > 0) {

		$_REQUEST = $hooks -> apply_filters( "history_editfilter", $_REQUEST );

		$tip  = $_REQUEST['tip'];
		$des  = untag( $_REQUEST['des'] );
		//$pid  = yimplode( ";", (array)$_REQUEST["pid"] );
		$pid = (is_array( $_REQUEST["pid"] )) ? yimplode( ";", $_REQUEST["pid"] ) : (int)$_REQUEST["pid"];
		$clid = (int)$_REQUEST["clid"];
		$did  = (int)$_REQUEST["did"];

		$offset           = getServerTimeOffset( $identity );
		$offset['offset'] = 0;
		$datum            = DateTimeToServerDate( $_REQUEST['datum'].":".date( "s" ), -$offset['offset'] );

		//находим папку
		$folder = (int)$db -> getOne( "SELECT idcategory FROM ".$sqlname."file_cat WHERE title = 'Файлы истории' and identity = '$identity'" );

		//если такой папки нет, то добавляем
		if ( $folder == 0 ) {

			$db -> query( "insert into ".$sqlname."file_cat (idcategory, title, identity) values(null, 'Файлы истории', '$identity')" );
			$folder = $db -> insertId();

		}

		$message = [];

		//загружаем файлы
		$upload = Upload ::upload();

		$message = array_merge( $message, $upload['message'] );

		foreach ( $upload['data'] as $file ) {

			$arg = [
				'ftitle'   => $file['title'],
				'fname'    => $file['name'],
				'ftype'    => $file['type'],
				'fver'     => '1',
				'iduser'   => $iduser1,
				'clid'     => $clid,
				'pid'      => $pid,
				'did'      => $did,
				'coid'     => $coid,
				'folder'   => $folder,
				"size"     => $file['size'],
				"datum"    => current_datumtime(),
				'identity' => $identity
			];

			$fid[] = Upload ::edit( 0, $arg );

		}

		$fileo = ($_REQUEST['fid_old'] != '') ? explode( ";", (string)$_REQUEST['fid_old'] ) : [];

		$fidn = (!empty( (array)$fid )) ? array_merge_recursive( $fileo, (array)$fid ) : $fileo;

		//массив файлов
		$fida = implode( ";", $fidn );

		$hst = [
			'pid'        => $pid,
			'clid'       => $clid,
			'did'        => $did,
			'datum'      => $datum,
			'datum_izm'  => current_datumtime(),
			'iduser_izm' => $iduser1,
			'des'        => $des,
			'tip'        => $tip,
			'fid'        => yimplode( ";", $fidn )
		];

		$hid = editHistorty( $cid, $hst );

		$hst['сid'] = $cid;

		$hooks -> do_action( "history_edit", $post, $hst );

		if ( (int)$hid > 0 ) {
			$message[] = "Информация обновлена";
		}

	}
	else{

		$_REQUEST = $hooks -> apply_filters( "history_addfilter", $_REQUEST );

		$des = untag( $_REQUEST['des'] );

		$pid = (is_array( $_REQUEST["pid"] )) ? yimplode( ";", $_REQUEST["pid"] ) : (int)$_REQUEST["pid"];

		$clid        = (int)$_REQUEST["clid"];
		$did         = (int)$_REQUEST["did"];
		$cid         = (int)$_REQUEST['cid'];
		$tip         = $_REQUEST['tip'];
		$theme       = $_REQUEST['theme'];
		$tip_task    = $_REQUEST['tip_task'];
		$des_task    = untag( str_replace( "\\r\\n", "\r\n", $_REQUEST['des_task'] ) );
		$datum_task  = $_REQUEST['datum_task'];
		$totime_task = $_REQUEST['totime_task'];
		$priority    = untag( $_REQUEST['priority'] );
		$speed       = untag( $_REQUEST['speed'] );
		$iduser      = (int)$_REQUEST['iduser'];
		$alert       = $_REQUEST['alert'];

		if ( $iduser1 != $iduser ) {
			$autor = $iduser1;
		}
		if ( $iduser < 1 ) {

			$iduser = $iduser1;
			$autor  = "";

		}

		$offset           = getServerTimeOffset( $identity );
		$offset['offset'] = 0;
		$datum            = DateTimeToServerDate( $_REQUEST['datum'].":".date( "s" ), -$offset['offset'] + $tzone );

		//находим папку
		$folder = (int)$db -> getOne( "SELECT idcategory FROM ".$sqlname."file_cat WHERE title = 'Файлы истории' and identity = '$identity'" );

		//если такой папки нет, то добавляем
		if ( $folder == 0 ) {

			$db -> query( "insert into ".$sqlname."file_cat (idcategory, title, identity) values(null, 'Файлы истории', '$identity')" );
			$folder = $db -> insertId();

		}

		$message = [];
		$fid     = [];

		//загружаем файлы
		$upload = Upload ::upload();

		$message = array_merge( $message, $upload['message'] );

		foreach ( $upload['data'] as $file ) {

			$arg = [
				'ftitle'   => $file['title'],
				'fname'    => $file['name'],
				'ftype'    => $file['type'],
				'fver'     => '1',
				'iduser'   => $iduser1,
				'clid'     => $clid,
				'pid'      => $pid,
				'did'      => $did,
				'coid'     => $coid,
				'folder'   => $folder,
				"size"     => $file['size'],
				"datum"    => current_datumtime(),
				'identity' => $identity
			];

			$fid[] = Upload ::edit( 0, $arg );

		}

		//записываем историю
		$hst = [
			'pid'       => $pid,
			'clid'      => $clid,
			'did'       => $did,
			'datum'     => $datum,
			'datum_izm' => current_datumtime(),
			'iduser'    => $iduser1,
			'des'       => $des,
			'tip'       => $tip,
			'fid'       => yimplode( ";", $fid )
		];
		$hid = addHistorty( $hst );

		if ( (int)$hid > 0 ) {

			$hst['cid'] = $cid;

			$hooks -> do_action( "history_add", $post, $hst );

			$message[] = "Информация успешно добавлена в историю";

			//сделаем отметку в карточке клиента
			$db -> query( "update ".$sqlname."clientcat set last_hist = '".current_datumtime()."' where clid = '".$clid."' and identity = '$identity'" );

		}

		//если надо добавить напоминание
		$todo = $_REQUEST['todo'];
		if ( $todo['theme'] != '' ) {

			$iduser = $todo['touser'] = ($todo['touser'] < 1) ? $iduser1 : $todo['touser'];

			$tparam = [
				"iduser"   => $todo['touser'],
				"clid"     => $clid,
				"pid"      => $pid,
				"did"      => $did,
				"datum"    => $todo['datum'],
				"totime"   => $todo['totime'],
				"title"    => untag( $todo['theme'] ),
				"des"      => untag( $todo['des'] ),
				"tip"      => $todo['tip'],
				"active"   => 'yes',
				"autor"    => $iduser1,
				"priority" => untag( $todo['priority'] ),
				"speed"    => untag( $todo['speed'] ),
				"created"  => current_datumtime(),
				"alert"    => $todo['alert'],
				"readonly" => $todo['readonly'],
				"identity" => $identity
			];

			if ( isset( $todo['datumtime'] ) ) {

				$todo['datumtime'] = str_replace( [
					"T",
					"Z"
				], [
					" ",
					""
				], $todo['datumtime'] );

				$tparam['datum'] = datetime2date( $todo['datumtime'] );
				$tparam['totime'] = getTime( (string)$todo['datumtime'] );

			}

			$todo = new Todo();
			$task = $todo -> add( (int)$iduser, $tparam );

			/*
			$db -> query("INSERT INTO ".$sqlname."tasks SET ?u", $task);
			$newtid = $db -> insertId();

			createCal($newtid, "false");
			*/

			if ( $task['result'] == 'Success' ) {

				$mes[]  = implode( "<br>", $task['text'] );
				$newtid = $task['id'];

			}

		}

		$args = [
			"id"    => (int)$hid,
			"autor" => $iduser1
		];
		event ::fire( 'history.add', $args );

	}

	$message = implode( '<br>', $message );

	print $message;

	exit();

}

if ( $action == "delete" ) {

	$cid = (int)$_REQUEST['cid'];

	$db -> query( "DELETE FROM {$sqlname}history WHERE cid = '$cid' and identity = '$identity'" );

	if ( $hooks ) {
		$hooks -> do_action( "history_delete", $cid );
	}

	//смотрим, если запись привязана к почтовику
	$messageid = (int)$db -> getOne( "SELECT id FROM {$sqlname}ymail_messages WHERE hid = '$cid'" );

	if ( $messageid > 0 ) {
		$db -> query( "UPDATE {$sqlname}ymail_messages SET hid = NULL WHERE id = '$messageid' and identity = '$identity'" );
	}

	exit();

}
if ( $action == "setparam" ) {

	if ( $_REQUEST['nolog'] != 'yes' )
		$nolog = 'no';
	else $nolog = 'yes';

	setcookie( "nolog", $nolog, time() + 31536000, "/", ".".$_SERVER['HTTP_HOST'] );

	exit();
}

if ( $action == "export" ) {

	$task = (int)$_REQUEST['tsk'];
	$d1   = $_REQUEST['da1'];
	$d2   = $_REQUEST['da2'];

	$sort = '';

	$user = implode( ",", (array)$_REQUEST['user'] );
	if ( $user != '' ) {
		$sort = " hst.iduser IN (".$user.") AND ";
	}

	if ( $d1 != '' ) {
		$sort .= " (hst.datum BETWEEN '".$d1." 00:00:01' and '".$d2." 23:59:59') AND ";
	}

	$sort .= !empty($task) ? " hst.tip IN (".yimplode(",", $task, "'").") AND " : "";

	$otchet[] = [
		"Дата",
		"Тип",
		"Описание",
		"Ответственный",
		"Клиент",
		"Сделка"
	];

	//выполненные дела
	//$result = $db -> query( "SELECT * FROM ".$sqlname."history WHERE cid > 0 $sort $stg $day and identity = '$identity' ORDER BY datum DESC" );
	$result = $db -> query( "
		SELECT 
			hst.des,
			hst.datum,
			hst.tip,
			hst.clid,
			hst.did,
			cl.title as client,
			dg.title as deal,
			us.title as user
		FROM ".$sqlname."history `hst`
			LEFT JOIN ".$sqlname."clientcat `cl` ON cl.clid = hst.clid
			LEFT JOIN ".$sqlname."dogovor `dg` ON dg.did = hst.did
			LEFT JOIN ".$sqlname."user `us` ON us.iduser = hst.iduser
		WHERE 
			hst.cid > 0 AND 
			$sort
			hst.identity = '$identity' 
		ORDER BY hst.datum DESC
	" );
	while ($data = $db -> fetch( $result )) {

		$content = htmlspecialchars_decode( $data['des'] );
		if ( preg_match( '|<body.*?>(.*)</body>|si', $content, $arr ) ) {
			$content = $arr[1];
		}
		$content = mb_substr( untag( $content ), 0, 101, 'utf-8' );

		$otchet[] = [
			$data['datum'],
			$data['tip'],
			cleanTotal(stripWhitespaces( $content )),
			$data['user'],
			cleanTotal( $data['client'] ),
			cleanTotal( $data['deal'] )
		];

	}

	/*
	$xls = new Excel_XML( 'UTF-8', true, 'Data' );
	$xls -> addArray( $otchet );

	$xls -> generateXML( 'exportHistory.xls' );
	*/

	Shuchkin\SimpleXLSXGen::fromArray( $otchet )->downloadAs('export.history.xlsx');

	exit();

}