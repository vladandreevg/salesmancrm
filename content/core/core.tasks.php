<?php
/* ============================ */
/*         SalesMan CRM         */
/* ============================ */
/* (C) 2018 Vladislav Andreev   */
/*       SalesMan Project       */
/*        www.isaler.ru         */
/*        ver. 2018.x           */
/* ============================ */

use Salesman\Todo;
use Salesman\Upload;

error_reporting(E_ERROR);
ini_set('display_errors', 1);
header("Pragma: no-cache");

$rootpath = dirname( __DIR__, 2 );

include $rootpath."/inc/config.php";
include $rootpath."/inc/dbconnector.php";
include $rootpath."/inc/auth.php";
include $rootpath."/inc/settings.php";
include $rootpath."/inc/func.php";
include $rootpath."/developer/events.php";

//file_put_contents($rootpath."/cash/request.json", json_encode_cyr($_REQUEST)."\n", FILE_APPEND);

$action = $_REQUEST['action'];
$tzone = $GLOBALS['tzone'];

if ($action == "viewtasks") {

	$datum = $_REQUEST['datum'];

	$all = $db -> getOne("SELECT COUNT(*) FROM ".$sqlname."tasks WHERE iduser = '$iduser1' and datum = '$datum' and active = 'yes' and identity = '$identity'") + 0;

	$apx = $all > 0 ? '&nbsp;<a href="javascript:void(0)" onclick="$(\'.datumTasksView\').load(\'/content/view/task.view.php?action=list&zag=none&datum='.$datum.'\').append(\'<img src=images/loading.gif>\').show();" title="Список дел"><i class="icon-list-alt blue"></i></a>' : '&nbsp;<i class="icon-list-alt gray2"></i>';

	print 'Число дел: <b>'.$all.'</b>'.$apx;

	exit();

}
if ($action == "viewtasksnew") {

	$datum = datetime2date($_REQUEST['datum']);

	$list = '';

	if( $datum != 'undefined' ) {

		$res = $db -> getAll( "SELECT * FROM ".$sqlname."tasks WHERE iduser = '$iduser1' AND datum = '$datum' AND active = 'yes' AND identity = '$identity' ORDER BY totime" );
		foreach ( $res as $da ) {

			$s1 = ((int)$da['clid'] > 0) ? current_client( (int)$da['clid'] ) : "";
			$d1 = ((int)$da['clid'] > 0) ? '<br><div class="ellipsis em gray2 fs-09" title="'.$s1.'"><i class="icon-building"></i>'.$s1.'</div>' : "";

			$s2 = ((int)$da['pid'] > 0) ? current_person( (int)$da['pid'] ) : "";
			$d2 = ((int)$da['pid'] > 0) ? '<br><div class="ellipsis em gray2 fs-09" title="'.$s2.'"><i class="icon-user-1"></i>'.$s2.'</div>' : "";

			$list .= '
		<div class="pad5 ha list flex-container border-bottom" title="'.$da['title'].'">
			<div class="flex-string wp30 border-box">'.get_ticon( $da['tip'] ).' <b>'.getTime( (string)$da['totime'] ).'</b> </div>
			<div class="flex-string wp70 border-box">
				<div class="ellipsis">'.$da['title'].'</div>
				'.$d1.'
				'.$d2.'
			</div>
		</div>';

		}

	}

	if ($list == '') {
		$count = 0;
		$list  .= '<div class="pad5 list em gray2" title="">Дел не найдено</div>';
	}

	print json_encode_cyr([
		"count" => count((array)$res),
		"list"  => $list
	]);

	exit();

}
if ($action == "hideAlert") {

	$tid = (int)$_REQUEST['tid'];

	$db -> query("update ".$sqlname."tasks set alert = 'no' where tid = '$tid' and identity = '$identity'");

	print 'ok';

	exit();

}

if ($action == "edit") {

	$tid = (int)$_REQUEST['tid'];

	if($tid > 0) {
		$_REQUEST = $hooks -> apply_filters( "task_editfilter", $_REQUEST );
	}
	else {
		$_REQUEST = $hooks -> apply_filters( "task_addfilter", $_REQUEST );
	}


	$tparam['title']    = untag($_REQUEST['title']);
	$tparam['des']      = str_replace("\r\n", "\n", untag($_REQUEST['des']));
	$tparam['clid']     = (int)$_REQUEST['clid'];
	$tparam['did']      = (int)$_REQUEST['did'];
	//$tparam['pid']      = $_REQUEST['pid'];
	$tparam['pid']      = is_array( $_REQUEST["pid"] ) ? yimplode( ";", $_REQUEST["pid"] ) : (int)$_REQUEST["pid"];
	$tparam['datum']    = untag($_REQUEST['datum']);
	$tparam['totime']   = untag($_REQUEST['totime']);
	$tparam['tip']      = untag($_REQUEST['tip']);
	$tparam['users']    = (array)$_REQUEST['users'];
	$tparam['priority'] = $_REQUEST['priority'];
	$tparam['speed']    = $_REQUEST['speed'];
	$tparam['alert']    = $_REQUEST['alert'];
	$tparam['readonly'] = $_REQUEST['readonly'];
	$tparam['day']      = $_REQUEST['day'];

	$mess = [];
	$err  = [];

	if (isset($_REQUEST['datumtime'])) {

		$_REQUEST['datumtime'] = str_replace( [
			"T",
			"Z"
		], [
			" ",
			""
		], $_REQUEST['datumtime'] );

		$tparam['datum'] = datetime2date($_REQUEST['datumtime']);
		$tparam['totime'] = getTime((string)$_REQUEST['datumtime']);

	}

	if ($tid > 0) {

		if (count((array)$tparam['users']) == 1) {
			$tparam['iduser'] = $tparam['users'][0];
		}
		else {
			$tparam['iduser'] = $iduser1;
		}

		$tparam['autor'] = $iduser1;

		// выполним фильтр
		$tparam['tid'] = $tid;
		//$tparam = $hooks -> apply_filters("task_editfilter", $tparam);

		$todo = new Todo();
		$task = $todo -> edit($tid, $tparam);

		if($task['result'] == 'Success') {

			$hooks -> do_action( "task_edit", $_REQUEST, $tparam );
			//$hooks ->current_filter();

		}

	}
	else {

		if (count($tparam['users']) == 1) {
			$iduser = $tparam['users'][0];
		}
		else {
			$iduser = $iduser1;
		}

		$tid = 0;

		// выполним фильтр
		$tparam['tid'] = $tid;
		//$tparam = $hooks -> apply_filters("task_addfilter", $tparam);

		if($iduser < 1 && $tparam['iduser'] > 0) {
			$iduser = $tparam['iduser'];
		}

		//print_r($tparam);

		$todo = new Todo();
		$task = $todo -> add((int)$iduser, $tparam);

		if($task['result'] == 'Success') {

			$_REQUEST[ 'tid' ] = (int)$task[ 'id' ];

			$hooks -> do_action( "task_add", $_REQUEST, $tparam );

		}

	}

	if ($task['result'] == 'Success') {

		$mess[] = implode("<br>", $task['text']);
		$tid    = $task['id'];

	}
	else {
		$err[] = implode( "<br>", $task['notice'] );
	}

	print implode("<br>", (array)$mess);

	if (count((array)$err) > 0) {
		print "<br>Есть ошибки: ".implode( "<br>", $err );
	}

	exit();

}

if ($action == "mass.edit"){

	$ids = yexplode(",", (string)$_REQUEST['ids']);
	$datum = $_REQUEST['datum'];

	//print_r($_REQUEST);

	$err = $mess = [];

	foreach($ids as $tid){

		$todo = new Todo();
		$task = $todo -> editdate($tid, $datum);

		//print_r($task);

		if($task['result'] == 'Success') {

			$mess[] = implode("<br>", $task['text']);
			$tid    = $task['id'];

			$hooks -> do_action( "task_edit", $_REQUEST, ['datum' => $datum, "mass" => true] );

		}
		else {
			$err[] = implode( "<br>", $task['notice'] );
		}

	}

	print "Обновлено ".count($mess)." записей";
	
	if (!empty($err)) {
		print "<br>Есть ошибки: ".implode( "<br>", $err );
	}

	exit();

}
if ($action == "mass.delete"){

	$ids = yexplode(",", (string)$_REQUEST['ids']);

	//print_r($_REQUEST);

	$err = $mess = [];

	foreach($ids as $tid){

		$todo = new Todo();
		$task = $todo -> remove($tid);

		if($task['result'] == 'Success') {

			$mess[] = implode("<br>", $task['text']);
			$tid    = $task['id'];

			$hooks -> do_action( "task_edit", $_REQUEST, ['datum' => $datum] );

		}
		else
			$err[] = implode("<br>", $task['notice']);

	}

	print "Удалено ".count($mess)." записей";
	
	if (!empty($err)) {
		print "<br>Есть ошибки: ".implode( "<br>", $err );
	}

	exit();

}

if ($action == "doit") {

	$tid  = (int)$_REQUEST['tid'];
	$redo = untag($_REQUEST['redo']);

	$_REQUEST = $hooks -> apply_filters("task_doitfilter", $_REQUEST);

	//данные для выполнения
	$dparam['rezultat'] = str_replace("\r\n", "\n", untag($_REQUEST['rezultat']));
	$dparam['tip']      = untag($_REQUEST['oldtip']);
	$dparam['datum']    = untag($_REQUEST['datumdo']);
	$dparam['status']   = $_REQUEST['status'];

	//данные для нового напоминания
	$tparam['title']    = untag($_REQUEST['title']);
	$tparam['des']      = str_replace("\r\n", "\n", untag($_REQUEST['des']));
	$tparam['clid']     = (int)$_REQUEST['clid'];
	$tparam['did']      = (int)$_REQUEST['did'];
	//$tparam['pid']      = $_REQUEST["pid"];
	$tparam['pid']      = is_array( $_REQUEST["pid"] ) ? yimplode( ";", $_REQUEST["pid"] ) : (int)$_REQUEST["pid"];
	$tparam['datum']    = untag($_REQUEST['datum']);
	$tparam['totime']   = untag($_REQUEST['totime']);
	$tparam['tip']      = untag($_REQUEST['tip']);
	$tparam['users']    = $_REQUEST['users'];
	$tparam['priority'] = $_REQUEST['priority'];
	$tparam['speed']    = $_REQUEST['speed'];
	$tparam['alert']    = $_REQUEST['alert'];
	$tparam['readonly'] = $_REQUEST['readonly'];
	$tparam['day']      = $_REQUEST['day'];

	if (isset($_REQUEST['datumtime'])) {

		$_REQUEST['datumtime'] = str_replace( [
			"T",
			"Z"
		], [
			" ",
			""
		], $_REQUEST['datumtime'] );

		$tparam['datum'] = datetime2date($_REQUEST['datumtime']);
		$tparam['totime'] = getTime((string)$_REQUEST['datumtime']);

	}

	$fid = $message = $err = [];

	//находим папку
	$folder = (int)$db -> getOne("SELECT idcategory FROM ".$sqlname."file_cat WHERE title = 'Файлы истории' AND identity = '$identity'");

	//если такой папки нет, то добавляем
	if ($folder == 0) {

		$db -> query("INSERT INTO ".$sqlname."file_cat SET ?u", [
			'title'    => 'Файлы истории',
			'shared'   => 'yes',
			'identity' => $identity
		]);
		$folder = $db -> insertId();

	}

	//загрузим файлы
	//$rez    = new Upload();
	$upload = Upload::upload();

	$err = $upload['message'];

	if(!empty($upload['data'])){

		foreach ($upload['data'] as $files){

			$fid[] = Upload::edit(0, [
				'ftitle'   => $files['title'],
				'fname'    => $files['name'],
				'ftype'    => $files['type'],
				'fver'     => '1',
				'iduser'   => (int)$iduser1,
				'clid'     => (int)$tparam['clid'],
				'pid'      => (int)$tparam['pid'],
				'did'      => (int)$tparam['did'],
				'folder'   => (int)$folder,
				'identity' => $identity
			]);

		}

	}

	//массив файлов
	$dparam['files'] = $fid;

	$todo = new Todo();
	$task = $todo -> doit($tid, $dparam);

	if ($task['result'] == 'Success') {

		$hooks -> do_action( "task_doit", $_REQUEST, $dparam );

		$message[] = implode("<br>", $task['text']);
		$hid       = $task['cid'];

	}
	else $err[] = implode("<br>", $task['notice']);

	//Добавим новое напоминание

	if ($redo == 'yes') {

		$todo = new Todo();
		$task = $todo -> add((int)$iduser1, $tparam);

		if ($task['result'] == 'Success') {

			$message[] = implode("<br>", $task['text']);
			$tid       = $task['id'];

		}
		else $err[] = implode("<br>", $task['notice']);

	}

	print implode("<br>", $message);
	if (count($err) > 0) {
        print "<br>Есть ошибки: " . implode("<br>", $err);
    }

	exit();

}

if ($action == "delete") {

	$tid = (int)$_REQUEST['tid'];

	$hooks -> do_action( "task_delete", $tid );

	$task = new Todo();
	$rez  = $task -> remove($tid);

	if ($rez['result'] == 'Success') {
		print yimplode( "<br>", $rez['text'] );
	}
	else {
		print yimplode( "<br>", $rez['error']['text'] );
	}

	exit();

}

if ($action == "izmdatum") {

	$tid      = (int)$_REQUEST['tid'];
	$datum    = untag($_REQUEST['newdatum']);
	$olddatum = untag($_REQUEST['olddatum']);
	$oldhour  = untag($_REQUEST['oldhour']);
	$newhour  = untag($_REQUEST['newhour']);

	$mess = [];
	$err  = [];

	if (diffDate2($datum) >= 0) {

		$tparam          = Todo ::info($tid);
		$tparam['datum'] = $datum;

		if ($newhour != '') {

			$oh = getDateTimeArray($olddatum);
			$nh = getDateTimeArray($datum);

			$tparam['totime'] = $nh['H'].":".$oh['i'].":00";
			$tparam['datum']  = datetime2date($datum);

		}

		//print_r($tparam);

		$todo = new Todo();
		$task = $todo -> edit($tid, $tparam);

		if ($task['result'] == 'Success') {

			$tparam['tid'] = $tid;
			$hooks -> do_action("task_change_date", $tparam);

			$mess[] = implode("<br>", $task['text']);
			$tid    = $task['id'];

		}
		else $err[] = implode("<br>", $task['notice']);

		$args = [
			"id"    => $tid,
			"autor" => $iduser1
		];
		event ::fire('task.edit', $args);

	}
	else $mess[] = 'Отменено - новая дата меньше текущей';

	print implode("<br>", $mess);
	if (count($err) > 0)
		print "<br>Есть ошибки: ".implode("<br>", $err);

	exit();

}

if ($action == "export") {

	$tar = $_REQUEST['tar'];

	$y = ($_REQUEST['y'] == '') ? date("Y", mktime(date('H'), date('i'), date('s'), date('m'), date('d'), date('Y')) + $tzone * 3600) : $_REQUEST['y'];
	$m = ($_REQUEST['m'] == '') ? date("m", mktime(date('H'), date('i'), date('s'), date('m'), date('d'), date('Y')) + $tzone * 3600) : $_REQUEST['m'];

	if (strlen($y) < 4) $y += 2000;

	if ($tar == 'my') {

		$sort     = " AND (iduser = '$iduser1')";
		$showuser = '';

	}

	if ($tar == 'other')
		$sort = " AND autor = '$iduser1' AND iduser != '$iduser1'";

	if ($tar == 'all')
		$sort = " AND (iduser IN (".implode(',', get_people($iduser1, 'yes')).") AND iduser != '$iduser1')";

	$otchet[] = ["Дата,Время","Тип","Важность","Срочность","Сделано","Заголовок","Агенда","Статус","Результат","Ответственный","Клиент","Ссылка"];

	//выполненные дела
	$result = $db -> getAll("SELECT * FROM ".$sqlname."tasks WHERE tid > 0 $sort AND date_format(datum, '%Y-%c') = '$y-$m' AND identity = '$identity' ORDER BY datum, totime");

	foreach ($result as $data) {

		$client = $url = "";
		$do     = 'Да';

		if ((int)$data['clid'] > 0) $client = current_client($data['clid']);
		elseif ((int)$data['pid'] > 0) $client = current_person($data['pid']);

		if ((int)$data['cid'] > 0) {

			$res      = $db -> getRow("SELECT * FROM ".$sqlname."history WHERE cid='".$data['cid']."' and identity = '$identity'");
			$rezultat = get_sfdate($res['datum']).' : '.clean(str_replace(";", ",", $res['des']));

		}
		else $rezultat = '';

		if ($data['active'] == 'yes' && difftimefull($data['datum']." ".$data['totime']) > 0) $do = 'Нет';
		elseif ($data['active'] == 'yes' && difftimefull($data['datum']." ".$data['totime']) < 0) $do = 'Просрочено';

		//создаем массив строк отчета
		if ((int)$data['clid'] > 0) {

			$client = current_client($data['clid']);
			$url    = $_SERVER['HTTP_SCHEME'] ?? ((((isset( $_SERVER['HTTPS'] ) && $_SERVER['HTTPS'] != 'off') || 443 == $_SERVER['SERVER_PORT']) ? 'https://' : 'http://').$_SERVER['HTTP_HOST']."/card.client?clid=".$data['clid']);

		}
		if ($data['pid'] > 0) {

			$client = current_person($data['pid']);
			$url    = $_SERVER['HTTP_SCHEME'] ?? ((((isset( $_SERVER['HTTPS'] ) && $_SERVER['HTTPS'] != 'off') || 443 == $_SERVER['SERVER_PORT']) ? 'https://' : 'http://').$_SERVER['HTTP_HOST']."/card.person?pid=".$data['pid']);

		}

		$status = ($data['status'] == 1) ? "Успешно" : "Не успешно";

		$content = str_replace(["\n","\n\r",";"], ["","",","], stripWhitespaces(untag($data['des'])));

		$otchet[] = [
			$data['datum'].' '.$data['totime'],
			str_replace(";", ",", $data['tip']),
			getPriority( 'priority', $data['priority'] ),
			getPriority( 'speed', $data['speed'] ),
			$do,
			str_replace(";", ",", $data['title']),
			$content,
			$status,
			$rezultat,
			str_replace(";", ",", current_user($data['iduser'])),
			$client,
			$url
		];

	}

	Shuchkin\SimpleXLSXGen::fromArray( $otchet )->downloadAs('export.tasks.xlsx');

	exit();

}

if ($action == "theme") {

	$tip = $_REQUEST["tip"];

	$config = json_decode(file_get_contents($rootpath.'/cash/'.$fpath.'settings.all.json'), true);
	$themes = $config['themesTasks'];

	if (!empty((array)$themes)) {

		foreach ($themes as $data) {

			echo $data."\n";

		}

	}
	else {

		$q      = texttosmall($_REQUEST["q"]);
		$result = $db -> getAll("SELECT DISTINCT LOWER(title), title FROM ".$sqlname."tasks WHERE title LIKE '%".$q."%' and iduser = '$iduser1' and identity = '$identity'");
		foreach ($result as $data) {

			echo $data['title']."\n";

		}

	}

	exit();

}
if ($action == "tags") {

	$tip = $_REQUEST['tip'];

	$tags = (string)$db -> getOne("select resultat from ".$sqlname."activities WHERE title = '".$tip."' and identity = '$identity' ORDER by title");

	if ($tags != '') {

		$tags = explode(";", $tags);

		for ( $i = 0, $iMax = count( (array)$tags ); $i < $iMax; $i++) {

			if ($tags[ $i ] != '')
				print '<div class="tags" id="tag_'.$i.'">'.$tags[ $i ].'</div>';

		}

	}
	else print '<div class="bad">Нет быстрых результатов</div>';

	exit();

}
if ($action == "itags") {

	$tip = $_REQUEST['tip'];

	$tags = (string)$db -> getOne("select resultat from ".$sqlname."activities WHERE title = '".$tip."' and identity = '$identity' ORDER by title");

	if ($tags != '') {

		$tags = explode(";", $tags);

		for ( $i = 0, $iMax = count( (array)$tags ); $i < $iMax; $i++) {

			if ($tags[ $i ] != '') print '<div class="tags tag p5">'.$tags[ $i ].'</div>';

		}

	}
	else print '<div class="bad">Нет быстрых результатов</div>';

	exit();

}
