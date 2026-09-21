<?php
/* ============================ */
/*         SalesMan CRM         */
/* ============================ */
/* (C) 2016 Vladislav Andreev   */
/*       SalesMan Project       */
/*        www.isaler.ru         */
/*         ver. 2017.x          */
/* ============================ */

error_reporting(E_ERROR);
header("Pragma: no-cache");

set_time_limit(0);
ini_set('memory_limit', '1024M');

$rootpath = dirname( __DIR__, 2 );

include $rootpath."/inc/config.php";
include $rootpath."/inc/dbconnector.php";
include $rootpath."/inc/auth.php";
include $rootpath."/inc/func.php";
include $rootpath."/inc/settings.php";
include $rootpath."/inc/language/".$language.".php";

require_once $rootpath."/content/helpers/todo.columns.php";

$thisfile = basename(__FILE__);

/**
 * Используется в разделе "Дела"
 * Шаблон "tpl.tasks.html"
 *
 * Колонки списка настраиваются пользователем (состав, ширина, порядок) - см. content/helpers/todo.columns.php
 */

$action   = $_REQUEST['action'];
$tar      = $_REQUEST['tar'];
$iduser   = (int)$_REQUEST['iduser'];
$task     = $_REQUEST['tsk'];
$priority = $_REQUEST['priority'];
$speed    = $_REQUEST['speed'];
$page     = (int)$_REQUEST['page'];
$d1       = $_REQUEST['da1'];
$d2       = $_REQUEST['da2'];
$word     = trim($_REQUEST['word']);
$users    = $_REQUEST['user'];
$ord     = untag($_REQUEST['ord']);

setcookie("todo_list", json_encode_cyr($_REQUEST), time() + 365 * 86400, "/");

$sort           = '';
$lines_per_page = 50;

$usersa = $db -> getIndCol("iduser", "SELECT title,iduser FROM ".$sqlname."user WHERE identity = '$identity' ORDER by title");

$y = (strlen($y) < 4) ? $y + 2000 : $y;
$m = str_pad($m, 2, '0', STR_PAD_LEFT);

$showuser = 'yes';

if ($tar == 'my' || $tar == '') {

	$sort     .= " (tsk.iduser = '$iduser1') and";
	$showuser = '';

	if (isset($_REQUEST['to_me'])) {
		$sort .= " (tsk.autor != 0 AND tsk.autor != '$iduser1' AND tsk.autor IS NOT NULL) and ";
	}

}
elseif ($tar == 'other') {

	$sort .= " (tsk.autor = '$iduser1' and tsk.iduser != '$iduser1') and";

	if ($iduser > 0 && $iduser != $iduser1) {
		$sort .= " tsk.iduser = '$iduser' and";
	}

}
elseif ($tar == 'all') {

	$sort .= "(tsk.iduser IN (".implode(',', (array)get_people($iduser1, 'yes')).") and tsk.iduser != '$iduser1') and ";

	if ($iduser > 0 && $iduser != $iduser1) {
		$sort .= " tsk.iduser = '$iduser' and ";
	}

}

if (!empty($users)) {
	$sort .= " tsk.iduser IN (".yimplode(",", (array)$users).") AND ";
}

if ($word != '') {
	$sort .= " (tsk.title LIKE '%".$word."%' OR tsk.des LIKE '%".$word."%') AND";
}

if (!empty($task)) {
	$sort .= " tsk.tip IN (".yimplode(",", (array)$task, "'").") and ";
}

if (isset($_REQUEST['to_deal'])) {
	$sort .= " tsk.did != 0 and ";
}

if (isset($_REQUEST['onlydo'])) {

	if ($_REQUEST['onlydo'] == 'yes') {
		$sort .= " tsk.active != 'yes' and ";
	}
	elseif ($_REQUEST['onlydo'] == 'no') {
		$sort .= " tsk.active = 'yes' and ";
	}
	elseif ($_REQUEST['onlydo'] == 'old') {
		$sort .= " tsk.active = 'yes' and CONCAT(tsk.datum, ' ', tsk.totime) < NOW() AND";
	}

}

if (!empty($priority)) {
	$sort .= " tsk.priority IN (".implode(',', (array)$priority).") and ";
}

if (!empty($speed)) {
	$sort .= " tsk.speed IN (".implode(',', (array)$speed).") and ";
}

$sort .= ($d1 != '') ? " ( DATE(tsk.datum) >= '$d1' AND DATE(tsk.datum) <= '$d2' ) AND" : "";

/* ============================ */
/*    Настройки колонок списка  */
/* ============================ */

$defs    = todoColumnDefs(["ord" => $ord]);
$fc      = todoColumnsLoad((int)$iduser1, $defs);
$visible = todoColumnsVisible($fc, $defs);

//шапка таблицы: только включённые колонки в заданном порядке
$columns = [];
foreach ($visible as $key) {

	$columns[] = [
		"field" => $key,
		"class" => todoColumnClass($defs[$key], $fc[$key]['width']),
		"th"    => $defs[$key]['th'],
	];

}

//колонки из карточки клиента: какие поля clientcat нужно выбрать
$sources      = todoClientColumnSources();
$clientKeys   = [];
$clientFields = [];

foreach ($visible as $key) {

	if (strpos($key, 'client_') !== 0) {
		continue;
	}

	$clientKeys[] = $key;

	foreach ((array)($sources[$key] ?? []) as $field) {

		if (preg_match('/^[a-zA-Z0-9_]+$/', $field)) {
			$clientFields[$field] = $field;
		}

	}

}

$clientSelect = '';
foreach ($clientFields as $field) {
	$clientSelect .= ", cc.`".$field."` as c_".$field;
}

//справочники для колонок клиента (по одному запросу на справочник)
$dicts = [
	"cat"   => [],
	"terr"  => [],
	"path"  => [],
	"users" => $usersa,
];

if ($clientKeys) {

	if (in_array('client_category', $clientKeys, true)) {
		$dicts['cat'] = $db -> getIndCol("idcategory", "SELECT idcategory, title FROM {$sqlname}category WHERE identity = '$identity'");
	}

	if (in_array('client_territory', $clientKeys, true)) {
		$dicts['terr'] = $db -> getIndCol("idcategory", "SELECT idcategory, title FROM {$sqlname}territory_cat WHERE identity = '$identity'");
	}

	if (in_array('client_path', $clientKeys, true)) {
		$dicts['path'] = $db -> getIndCol("id", "SELECT id, name FROM {$sqlname}clientpath WHERE identity = '$identity'");
	}

}

$clientFieldsList = todoClientFields();

$list = [];

$tm = $tzone;
$dd = date("t", mktime((int)date('H'), (int)date('i'), (int)date('s'), (int)$m, (int)date('d'), (int)$y) + $tm * 3600);

$cquery = "
SELECT 
	COUNT(tsk.tid)
FROM ".$sqlname."tasks `tsk`
WHERE 
	tsk.tid > 0 and
	$sort
	tsk.identity = '$identity'
";
$total  = $db -> getOne($cquery);
$page   = (empty($page) || $page <= 0) ? 1 : (int)$page;

$page_for_query = $page - 1;
$lpos           = $page_for_query * $lines_per_page;
$count_pages    = ceil($total / $lines_per_page);

if ($count_pages < 1) {
	$count_pages = 1;
}

$query = "
SELECT 
	DISTINCT (tsk.tid),
	tsk.created as created,
	tsk.datum,
	tsk.totime,
	tsk.tip,
	tsk.clid,
	tsk.pid,
	tsk.did,
	tsk.cid,
	tsk.title,
	tsk.des,
	tsk.iduser,
	tsk.active,
	tsk.autor,
	tsk.priority,
	tsk.speed,
	tsk.status,
	tsk.day,
	tsk.readonly,
	hist.datum as hdate,
	cc.title as client,
	dd.title as deal
	".$clientSelect."
FROM ".$sqlname."tasks `tsk`
	LEFT JOIN ".$sqlname."clientcat `cc` ON cc.clid = tsk.clid
	LEFT JOIN ".$sqlname."dogovor `dd` ON dd.did = tsk.did
	LEFT JOIN ".$sqlname."history `hist` ON hist.cid = tsk.cid
WHERE 
	tsk.tid > 0 and
	$sort
	tsk.identity = '$identity'
ORDER BY tsk.datum $ord, tsk.totime $ord
".($action == 'export' ? "" : "LIMIT $lpos,$lines_per_page");

//print $query;
//exit();

$resultt = $db -> query($query);

$rows = [];
while ($row = $db -> fetch($resultt)) {
	$rows[] = $row;
}

//привязка контакт -> клиент (для напоминаний, привязанных только к контакту)
$pidClid = [];
if ($clientKeys) {

	$pids = [];

	foreach ($rows as $row) {

		$pid = (int)yexplode(";", (string)$row['pid'], 0);

		if ((int)$row['clid'] < 1 && $pid > 0) {
			$pids[$pid] = $pid;
		}

	}

	if ($pids) {

		$re = $db -> getAll("SELECT pid, clid FROM {$sqlname}personcat WHERE pid IN (".implode(',', $pids).") and identity = '$identity'");

		foreach ($re as $v) {
			$pidClid[(int)$v['pid']] = (int)$v['clid'];
		}

	}

}

//мемоизация данных клиента: несколько напоминаний могут ссылаться на одного клиента
$accessMemo = [];
$clientMemo = [];
$personMemo = [];

foreach ($rows as $data) {

	$rezultat = '';
	$do       = '';
	$change   = '';
	$users    = '';
	$iconuser = '';

	$hours = difftime($data['created']);

	//$change = ($hours <= $hoursControlTime || $ac_import[7] == 'on') ? 'yes' : '';

	if ($data['autor'] == 0 || $data['autor'] == $iduser1 || $data['iduser'] == $iduser1) {
		$do = 'yes';
	}
	elseif ($userRights['changetask']) {
		$change = '';
	}

	$color = $db -> getOne("SELECT color FROM ".$sqlname."activities WHERE title='".$data['tip']."' and identity = '$identity'");

	$pid = yexplode(";", (string)$data['pid'], 0);

	if ($data['cid'] > 0) {

		$hist = $db -> getRow("SELECT * FROM ".$sqlname."history WHERE cid='".$data['cid']."' and identity = '$identity'");

		$txt = mb_substr(untag(html2text($hist['des'])), 0, 101, 'utf-8');

		$rezultat = $action != 'export' ? '<span class="em gray2 fs-07 mb5">Результат:</span><br><div class="ellipsis1 fs-09" title="'.get_sfdate($hist['datum']).': '.$txt.'">'.get_sdate($hist['datum']).' <span class="blue">'.$txt.'</span></b></div>' : $txt;

	}

	if ($showuser == 'yes') {
		$users = '<div class="em fs-09 gray2">Отв.: '.$usersa[ $data['iduser'] ].'</div>';
	}

	if ($data['autor'] != $data['iduser'] && $data['readonly'] == 'yes') {
		$data['readonly'] = 'yes';
	}

	if ($data['autor'] == $data['iduser'] || $data['autor'] == 0 || $data['autor'] == $iduser1) {

		if ($hours <= $hoursControlTime) {
			$change = 'yes';
		}
		elseif ($userRights['changetask']) {
			$change = 'yes';
		}

		$data['readonly'] = '';

	}

	$diff = difftimefull($data['datum']." ".$data['totime']);

	if ($diff > 0 && $diff <= 0.5) {
		$icn = '<i class="icon-ok green" title="Порядок"></i>';
	}
	elseif ($diff < 0) {
		$icn = '<i class="icon-attention red" title="! Не выполнено"></i>';
	}
	elseif ($diff > 0.5) {
		$icn = '<i class="icon-clock blue" title="Порядок"></i>';
	}

	if ($data['autor'] > 0 && $data['iduser'] != $iduser1) {
		$iconuser = '<i class="icon-user-1 blue" title="Назначено мной"></i>';
	}

	$item = [
		"tid"           => $data['tid'],
		"date"          => format_date_rus((string)$data['datum']),
		"time"          => getTime((string)$data['totime']),
		"datetime"      => $data['datum']." ".$data['totime'],
		"histdate"      => (int)$data['cid'] > 0 ? get_sdate($hist['datum']) : null,
		"hdatetime"     => (int)$data['cid'] > 0 ? $hist['datum'] : null,
		"priority"      => get_priority('priority', $data['priority']).get_priority('speed', $data['speed']),
		"title"         => $data['title'],
		"user"          => $data['iduser'] != $iduser1 ? $usersa[ $data['iduser'] ] : NULL,
		"autor"         => ((int)$data['autor'] > 0 && (int)$data['autor'] != (int)$data['iduser']) ? strtr($data['autor'], $usersa) : null,
		"icon"          => get_ticon($data['tip']),
		"tip"           => $data['tip'],
		"color"         => ($color == "") ? "transparent" : $color,
		"did"           => ((int)$data['did'] > 0) ? $data['did'] : null,
		"deal"          => ((int)$data['did'] > 0) ? $data['deal'] : null,
		"clid"          => ((int)$data['clid'] > 0) ? $data['clid'] : null,
		"client"        => ((int)$data['clid'] > 0) ? $data['client'] : null,
		"pid"           => ((int)$data['clid'] < 1 && $pid > 0) ? $pid : null,
		"person"        => ((int)$data['clid'] < 1 && $pid > 0) ? current_person($pid) : null,
		"day"           => ($data['day'] == 'yes') ? 1 : null,
		"status"        => ($data['status'] == "2") ? 1 : null,
		"statusTooltip" => (int)$data['cid'] == 0 ? null : ($data['status'] == 1 ? "Успешно" : " Не успешно"),
		//"change"        => (int)$data['cid'] > 0 ? null : $change,
		"change"        => ($change == "yes") ? true : NULL,
		"rezult"        => nl2br($rezultat),
		"users"         => $users,
		"do"            => $data['active'] == 'yes' ? null : 1,
		"readonly"      => ($data['readonly'] == "yes") ? "yes" : false,
		"doit"          => $userSettings['taskCheckBlock'] == 'yes' && $da['iduser'] != $iduser1 ? null : 1,
		"agenda"        => $data['des'],
		"active"        => $data['active'],
		"iconuser"      => $iconuser
	];

	//данные из карточки клиента (для колонок client_*)
	$item['clientData'] = ["html" => [], "text" => []];

	if ($clientKeys) {

		$clid = (int)$data['clid'];

		if ($clid < 1 && (int)$pid > 0 && isset($pidClid[(int)$pid])) {
			$clid = (int)$pidClid[(int)$pid];
		}

		if ($clid > 0) {

			if (!isset($accessMemo[$clid])) {
				$accessMemo[$clid] = get_accesse($clid);
			}

			$isaccess = $accessMemo[$clid];

			//контакты видны при наличии доступа к клиенту (см. content/lists/list.clients.php:427-501)
			$canSee = ($isaccess == 'yes' || $acs_prava == 'on');

			$canSeeHistory = ($isadmin == 'on' || $isaccess == 'yes' || !empty($userRights['showhistory']));

			if (!isset($clientMemo[$clid])) {

				$clientRow = [];
				foreach ($data as $field => $value) {

					if (strpos($field, 'c_') === 0) {
						$clientRow[substr($field, 2)] = $value;
					}

				}

				$personName = '';
				$cpid       = (int)($clientRow['pid'] ?? 0);

				if ($cpid > 0) {

					if (!isset($personMemo[$cpid])) {
						$personMemo[$cpid] = current_person($cpid);
					}

					$personName = (string)$personMemo[$cpid];

				}

				$clientMemo[$clid] = todoClientValues($clientRow, [
					"clid"          => $clid,
					"pid"           => (int)$pid,
					"canSee"        => $canSee,
					"canSeeHistory" => $canSeeHistory,
					"dicts"         => $dicts,
					"fields"        => $clientFieldsList,
					"person"        => $personName,
				]);

			}

			$item['clientData'] = $clientMemo[$clid];

		}

	}

	//ячейки строки в порядке вывода колонок
	$cols  = [];
	$first = true;

	foreach ($visible as $key) {

		$render = $defs[$key]['td'];
		$td     = $render($item);

		//чекбокс группового выделения размещается в первой выводимой ячейке
		//(как и раньше - в первой колонке, поэтому выделение протягиванием работает без изменений в JS)
		if ($first) {

			$td = preg_replace('/^<TD\s/i', '<TD class="check--cell" ', $td, 1);

			if (empty($item['readonly']) && empty($item['do'])) {

				$chk = '<span class="hidden"><input name="ch[]" type="checkbox" class="mc" id="ch_'.(int)$item['tid'].'" value="'.(int)$item['tid'].'"></span>';

				$td = preg_replace('/^<TD([^>]*)>/i', '<TD$1>'.$chk, $td, 1);

			}

			$first = false;

		}

		$cols[] = [
			"field" => $key,
			"td"    => $td,
		];

	}
	$item['cols'] = $cols;

	$list[] = $item;

}

if( $action == 'export' ){

	//заголовки и значения - по текущим настройкам колонок пользователя
	$head = [];
	foreach ($visible as $key) {
		$head[] = $defs[$key]['title'];
	}
	$export = [$head];

	foreach ($list as $x){

		$row = [];
		foreach ($visible as $key) {

			$render = $defs[$key]['export'] ?? NULL;
			$row[]  = ($render !== NULL) ? $render($x) : '';

		}

		$export[] = $row;

	}

	Shuchkin\SimpleXLSXGen::fromArray( $export )->downloadAs('export.todo.xlsx');

	exit();

}

$lists = [
	"list"    => $list,
	"columns" => $columns,
	"page"    => (int)$page,
	"total"   => (int)$count_pages,
	"ord"     => $ord == 'desc' ? 1 : null
];

print json_encode_cyr($lists);

exit();
