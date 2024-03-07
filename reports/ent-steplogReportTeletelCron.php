<?php
/**
 * @license  http://isaler.ru/
 * @author   Vladislav Andreev, http://iandreyev.ru/
 * @charset  UTF-8
 * @version  6.4
 */

use Cronman\Cronman;
use Salesman\Notify;

?>
<?php
set_time_limit(0);
error_reporting(E_ERROR);
header("Pragma: no-cache");
ini_set('memory_limit', '1024M');

$rootpath = realpath(__DIR__.'/../');

$action = $_REQUEST['action'];

require_once $rootpath."/inc/config.php";
require_once $rootpath."/inc/dbconnector.php";

if ($action != 'export') {
	require_once $rootpath."/inc/auth.php";
}

require_once $rootpath."/inc/func.php";
require_once $rootpath."/inc/settings.php";
require_once $rootpath."/inc/language/".$language.".php";
require_once $rootpath."/plugins/cronManager/php/autoload.php";

$reportName = basename(__FILE__);

$thisfile = basename($_SERVER['PHP_SELF']);

//print_r($argv);

$taskID   = (int)$argv[1];

if($taskID > 0){
	$action = 'export';
}

if ($action == 'crontask') {

	//$params            = $_REQUEST;
	$params['UserID']    = $iduser1;
	$params['created']   = str_replace(" ", "T", current_datumtime());
	$params['da1']       = $_REQUEST['da1'];
	$params['da2']       = $_REQUEST['da2'];
	$params['user_list'] = $_REQUEST['user_list'];
	//$params['url']    = $_SERVER['HTTP_SCHEME'] ?? (((isset( $_SERVER['HTTPS'] ) && $_SERVER['HTTPS'] != 'off') || 443 == $_SERVER['SERVER_PORT']) ? 'https://' : 'http://').$_SERVER["HTTP_HOST"];
	$params['url']    = 'https://dc-b2b.takemycall.ru';

	$task = new Cronman();
	$task -> setTask(0, [
		"uid"    => time(),
		"name"   => "steplogReportTeletel",
		"bin"    => "php",
		"script" => $rootpath."/reports/ent-steplogReportTeletelCron.php",
		"task"   => json_encode_cyr($params),
		"active" => "on",
		"parent" => "once"
	]);

	$response['result'] = [
		"result"  => "ok",
		"error"   => "",
		"message" => "Задание добавлено в очередь. По выполнении вам придет уведомление (колокольчик рядом с аватаркой)",
		"params" => $params
	];

	print json_encode_cyr($response);
	exit();

}
if ($action == 'export') {

	$cron   = new Cronman();
	$task   = $cron -> getTask($taskID);
	$params = json_decode($task['task'], true);

	//print_r($task);
	//exit(100);

	// деактивируем таску
	if($task['parent'] == 'once') {
		$cron -> disableTask( $taskID );
	}

	$da1       = $params['da1'];
	$da2       = $params['da2'] ?? current_datum();
	$user_list = $params['user_list'];
	$UserID    = $params['UserID'];

	//заголовки этапов
	$res = $db -> query("SELECT * FROM ".$sqlname."dogcategory WHERE idcategory > 0 and identity = '$identity' ORDER BY title");
	while ($data = $db -> fetch($res)) {

		$header[] = [
			"name"    => $data['title'],
			"content" => $data['content'],
			"space"   => '',
			"log"     => ''
		];

	}

	$userNames = $db -> getIndCol("iduser", "SELECT iduser, title FROM {$sqlname}user");

	$sort = '';

	if(!empty($task['period'])) {

		//массив выбранных пользователей
		$sort .= ($user_list[0] != '') ? " and ".$sqlname."dogovor.iduser IN (".implode( ",", $user_list ).")" : str_replace( "iduser", $sqlname."dogovor.iduser", get_people( $iduser1 ) );

	}

	$rez   = [];
	$steps = [];
	$step  = [];
	$g     = 0;

	//перебираем сделки
	$q  = "
		SELECT
			".$sqlname."dogovor.did as did,
			".$sqlname."dogovor.title as title,
			".$sqlname."dogovor.datum as dcreate,
			".$sqlname."dogovor.datum_plan as dplan,
			".$sqlname."dogovor.datum_close as dclose,
			".$sqlname."dogovor.datum_start as dstart,
			".$sqlname."dogovor.datum_end as dend,
			".$sqlname."dogovor.idcategory as idstep,
			".$sqlname."dogovor.tip as tip,
			".$sqlname."dogovor.clid as clid,
			".$sqlname."dogovor.pid as pid,
			".$sqlname."dogovor.kol as kol,
			".$sqlname."dogovor.kol_fact as kolf,
			".$sqlname."dogovor.close as close,
			".$sqlname."dogovor.iduser as iduser,
			".$sqlname."dogovor.des_fact as des_fact,
			".$sqlname."dogovor.adres as adres,
			".$sqlname."dogovor.input19 as products,
			".$sqlname."dogovor.isFrozen as isFrozen,
			substring(".$sqlname."dogovor.content, 0, 250) as des,
			".$sqlname."user.title as user,
			".$sqlname."clientcat.title as client,
			".$sqlname."dogcategory.title as step,
			".$sqlname."dogcategory.content as steptitle,
			".$sqlname."dogtips.title as tips,
			".$sqlname."dogstatus.title as dstatus,
			".$sqlname."direction.title as direction
		FROM ".$sqlname."dogovor
			LEFT JOIN ".$sqlname."user ON ".$sqlname."dogovor.iduser = ".$sqlname."user.iduser
			LEFT JOIN ".$sqlname."clientcat ON ".$sqlname."dogovor.clid = ".$sqlname."clientcat.clid
			LEFT JOIN ".$sqlname."dogcategory ON ".$sqlname."dogovor.idcategory = ".$sqlname."dogcategory.idcategory
			LEFT JOIN ".$sqlname."dogtips ON ".$sqlname."dogovor.tip = ".$sqlname."dogtips.tid
			LEFT JOIN ".$sqlname."dogstatus ON ".$sqlname."dogovor.sid = ".$sqlname."dogstatus.sid
			LEFT JOIN ".$sqlname."direction ON ".$sqlname."dogovor.direction = ".$sqlname."direction.id
		WHERE
			".$sqlname."dogovor.did > 0
			$sort and
			".$sqlname."dogovor.did IN (SELECT did FROM ".$sqlname."steplog WHERE did > 0 and datum BETWEEN '".$da1." 00:00:01' and '".$da2." 23:59:59') and
			".$sqlname."dogovor.identity = '$identity'
			ORDER BY ".$sqlname."dogovor.title
	";
	$re = $db -> getAll($q);
	foreach ($re as $da) {

		$steps    = [];
		$stepUser = [];

		//перебираем этапы
		$res = $db -> getAll("SELECT idcategory FROM ".$sqlname."dogcategory WHERE identity = '$identity' ORDER BY title");
		foreach ($res as $data) {

			$st         = $db -> getRow("SELECT datum, iduser FROM ".$sqlname."steplog WHERE did = '".$da['did']."' and step = '".$data['idcategory']."' ORDER BY datum DESC LIMIT 1");
			$steps[]    = ($st['datum'] == '0000-00-00 00:00:00' || $st['datum'] == '' || $st['datum'] == null) ? '' : get_sfdate2($st['datum']);
			$stepUser[] = $userNames[ $st['iduser'] ];

		}

		$hist = $db -> getOne("SELECT MAX(datum) as datum FROM ".$sqlname."history WHERE (did = '".$da['did']."') and identity = '$identity'");
		if ($hist == '0000-00-00 00:00:00' || $hist == '' || $hist == null) $hist = '';

		$task = $db -> getOne("SELECT MIN(datum) as datum FROM ".$sqlname."tasks WHERE (did = '".$da['did']."') and datum >= '".current_datum()."' and identity = '$identity'");
		if ($task == '0000-00-00' || $task == '' || $task == null) $task = '';

		$path = current_clientpath($da['clid']);

		$rez[ $g ] = [
			"id"           => $da['did'],
			"path"         => $path,
			"dcreate"      => $da['dcreate'],
			"dplan"        => $da['dplan'],
			"dstart"       => $da['dstart'],
			"dend"         => $da['dend'],
			"tip"          => $da['tips'],
			"summa"        => $da['kol'],
			"marga"        => $da['marga'],
			"did"          => $da['did'],
			"deal"         => $da['title'],
			"adres"        => $da['adres'],
			"products"     => $da['products'],
			"description"  => $da['des'],
			"clid"         => $da['clid'],
			"client"       => $da['client'],
			"user"         => $da['user'],
			"status"       => $da['close'] == 'yes' ? 'Закрыта' : 'Активна',
			"step"         => $da['step'],
			"close"        => $da['close'],
			"closeDate"    => $da['dclose'],
			"closeSumma"   => $da['kolf'],
			"closeStatus"  => $da['dstatus'],
			"closeComment" => $da['des_fact'],
			"isFrozen"     => $da['isFrozen'],
			"history"      => get_sfdate2($hist),
			"task"         => $task,
			"steplog"      => $steps,
			"stepUser"     => $stepUser
		];

		foreach ($steps as $key => $value) {

			$rez[ $g ][ 'log'.$key ] = $value;

		}

		$g++;

	}

	/**
	 * Формируем заголовок
	 */
	$head = [
		"DID",
		"CLID",
		"Название",
		"Продукты",
		"Менеджер",
		"Текущий статус",
		"Текущий этап",
		"Заморозка",
		"Дата создания",
		"Дата последней активности"
	];

	foreach ($header as $key => $val) {
		$head[] = $val['name']."% [".$val['content']."]";
	}

	$head = array_merge($head, [
		"Дата ближайшей запланированной активности",
		"Дата план",
		"Дата закрытия",
		"Статус закрытия",
		"Комментарий закрытия",
		"Тип сделки",
		"Адрес",
		"Описание",
	]);

	$otchet[] = $head;

	foreach ($rez as $key => $value) {

		$string1 = [];

		$string = [
			$value['id'],
			$value['clid'],
			$value['deal'],
			$value['products'],
			$value['user'],
			$value['status'],
			$value['step']."%",
			$value['isFrozen'],
			$value['dcreate'],
			$value['history']
		];

		foreach ($value['steplog'] as $val) {
			$string[] = $val;
		}

		$string1 = array_merge($string, [
			$value['task'],
			$value['dplan'],
			$value['closeDate'],
			$value['closeStatus'],
			$value['closeComment'],
			$value['tip'],
			$value['adres'],
			$value['description'],
		]);

		$otchet[] = $string1;

	}

	Shuchkin\SimpleXLSXGen ::fromArray($otchet) -> saveAs($rootpath.'/cash/export.dealsbystep.xlsx');

	// уведомляем о готовности
	$notify = Notify::edit(0, [
		"iduser"  => $UserID,
		"autor"  => $UserID,
		"tip" => "note",
		"title"   => 'Экспорт Движение сделок по этапам<hr><a href="'.$params['url'].'/cash/export.dealsbystep.xlsx'.'" class="button bluebtn dotted" target="_blank">скачать</a>',
		"content" => 'Файл экспорта, заказанный '.modifyDatetime($params['created'], ["format" => "d.m.Y в H:i"]).' готов. Вы можете получить его по ссылке:<hr><a href="'.$params['url'].'/cash/export.dealsbystep.xlsx'.'" class="button bluebtn dotted" target="_blank">скачать</a>'
	]);

	// удаляем задачу
	if($task['parent'] == 'once') {
		$cron -> deleteTask( $taskID );
	}

	exit();

}
if (!$action) {

	?>

	<div class="relativ mt20 mb0 wp100 text-center" id="head">

		<h1 class="uppercase fs-14 m0 mb10">Движение сделок по этапам. Телетел мод</h1>

		<hr>

		<div class="infodiv" id="info">
			В отчет выводятся сделки, у которых было произведено изменение этапа в указанном периоде
			<div class="mt15">
				<a href="javascript:void(0)" onclick="exportDeal()" class="button">Экспорт в Excel</a>
			</div>
		</div>

	</div>

	<script>
		function exportDeal() {

			var str = $('#selectreport').serialize();

			$.getJSON('/reports/ent-steplogReportTeletelCron.php?action=crontask&' + str, function (data) {
				//Swal.fire('Отлично!', data.message, 'success');
				Swal.fire({
					icon: 'info',
					position: 'bottom-end',
					background: "var(--blue)",
					title: '<div class="white fs-11">Отлично!</div>',
					html: '<div class="white">' + data.result.message + '</div>',
					showConfirmButton: false,
					timer: 3500
				});
			});

		}
	</script>

	<?php
}
?>