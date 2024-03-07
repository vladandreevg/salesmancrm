<?php
/* ============================ */
/*         SalesMan CRM         */
/* ============================ */
/* (C) 2016 Vladislav Andreev   */
/*       SalesMan Project       */
/*        www.isaler.ru         */
/*        ver. 2017.x           */
/* ============================ */

use Salesman\Guides;
use Salesman\User;

set_time_limit(0);
error_reporting(E_ERROR);

if (isset($_GET['code'])) {

	$_COOKIE['ses'] = $_GET['code'];

}

$rootpath = dirname(__DIR__, 2);

require_once $rootpath."/inc/config.php";
require_once $rootpath."/inc/dbconnector.php";
require_once $rootpath."/inc/auth.php";
require_once $rootpath."/inc/func.php";
require_once $rootpath."/inc/settings.php";
require_once $rootpath."/inc/language/".$language.".php";

global $userRights;

$thisfile = basename(__FILE__);

function cleanall($string) {
	$string = strip_tags_smart($string);
	$string = trim($string);
	$string = ltrim($string);
	$string = str_replace("\"", "'", $string);
	$string = str_replace("\n", " ", $string);
	$string = str_replace("\r", " ", $string);
	$string = str_replace("\n\r", " ", $string);
	$string = str_replace("\\n", " ", $string);
	$string = str_replace("\\r", " ", $string);
	$string = str_replace("\\n\\r", " ", $string);
	$string = str_replace("<", "'", $string);
	$string = str_replace(">", "'", $string);
	$string = str_replace("?", "", $string);
	$string = str_replace("„", "", $string);
	$string = str_replace("«", "", $string);
	$string = str_replace("»", "", $string);
	/*$string = str_replace("?","&euro;",$string);
	$string = str_replace("„","&bdquo;",$string);
	$string = str_replace("«","&laquo;",$string);
	$string = str_replace("»","&raquo;",$string);*/
	$string = str_replace("?", "", $string);
	$string = str_replace("„", "", $string);
	$string = str_replace("«", "", $string);
	$string = str_replace("»", "", $string);
	$string = str_replace(">", "", $string);
	$string = str_replace("<", "", $string);
	$string = str_replace("&amp;", "", $string);
	$string = str_replace("#8220;", "", $string);
	$string = str_replace("“", "", $string);
	$string = str_replace("”", "", $string);
	$string = str_replace("'", "", $string);
	$string = str_replace("=", "", $string);
	$string = str_replace(";", ". ", $string);
	$string = str_replace("&laquo;", "", $string);
	$string = str_replace("&raquo;", "", $string);
	$string = str_replace("&rdquo;", "", $string);
	$string = str_replace("&ldquo;", "", $string);

	return $string;
}

function clll($string) {
	$string = strip_tags($string);
	$string = trim($string);
	$string = ltrim($string);
	$string = str_replace("(", "'", $string);
	$string = str_replace(")", "", $string);
	$string = str_replace("&", "", $string);
	$string = str_replace(";", ". ", $string);
	$string = str_replace("javascript:void(0)", "", $string);
	$string = str_replace("/", "", $string);

	return $string;
}

function n_format($string) {
	$string = str_replace(",", ".", $string);
	$string = str_replace(" ", "", $string);
	$string = number_format($string, 2, ',', '');

	return $string;
}

function date2excel($datum) {

	if ($datum != '0000-00-00' && $datum != '') {
		$dstart = $datum;
		$dend   = '1970-01-01';
		$day    = (int)((date_to_unix($dstart) - date_to_unix($dend)) / 86400) + 25570;
	}
	else $day = '';

	return $day;

}

$action = $_GET['action'];
$format = $_GET['format'];
$code   = $_GET['code'];

$scheme = $_SERVER['HTTP_SCHEME'] ?? (((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'off') || 443 == $_SERVER['SERVER_PORT']) ? 'https://' : 'http://');

if (!$format) $format = 'xml';

$fields = [];

/*поля*/
$fieldsNames = [
	'did'          => 'Deal ID',
	'uid'          => 'Deal UID',
	'clientUID'    => 'Client UID',
	'title'        => 'Название',
	'idcategory'   => 'Этап',
	'stepDate'     => 'Дата смены этапа',
];

$fi = $db -> getRow( "SHOW COLUMNS FROM ".$sqlname."dogovor LIKE 'isFrozen'" );
if($otherSettings['dateFieldForFreeze'] != '' && $fi['Field'] != ''){
	$fieldsNames["isFrozen"] = 'Заморожена';
	$fieldsNames[$otherSettings['dateFieldForFreeze']] = 'Заморозка до';
}

$fieldsNames = array_merge($fieldsNames, [
	'stepDate'     => 'Дата смены этапа',
	'datum_plan'   => 'Дата план.',
	'kol'          => 'Сумма план.',
	'marga'        => 'Маржа',
	'direction'    => 'Направление',
	'datum'        => 'Дата создания',
	'datum_izm'    => 'Дата изменения',
	'datum_start'  => 'Период старт',
	'datum_end'    => 'Период финиш',
	'close'        => 'Закрыта',
	'datum_close'  => 'Дата закрытия',
	'status_close' => 'Статус закрытия',
	'des_fact'     => 'Комментарий закрытия',
	'kol_fact'     => 'Сумма факт',
	'clid'         => 'Клиент',
	'date_create'  => 'Дата добавления Клиента',
	'iduser'       => 'Куратор',
	'autor'        => 'Автор',
	'path'         => 'Канал продаж'
]);

$res = $db -> query("SELECT * FROM {$sqlname}field WHERE fld_tip='dogovor' and fld_on='yes' and fld_name NOT IN ('kol_fact','money','pid_list','oborot','period','des','kol','marga') and identity = '$identity'");
while ($do = $db -> fetch($res)) {

	if ($do['fld_name'] == 'marg') $do['fld_name'] = 'marga';

	$fieldsNames[ $do['fld_name'] ] = $do['fld_title'];
	$fields[]                       = $do['fld_name'];

}

//print_r($fieldsNames);
//exit();

$fieldsClientNames = $db -> getIndCol("fld_name", "SELECT fld_name, fld_title FROM ".$sqlname."field WHERE fld_tip = 'client' AND fld_on = 'yes' AND identity = '$identity'" );

if ($code == '' && $action != 'get_export') {

	print "Доступ запрещен";
	exit();

}

if ($action == 'get_export') {

	$code = $db -> getOne("SELECT ses FROM {$sqlname}user WHERE iduser='".$iduser1."' and identity = '$identity'");

	$url = $scheme.$_SERVER['HTTP_HOST'].'/content/helpers/deal.export.php?code='.$code;

	?>
	<style>
		label.field {
			background    : var(--white);
			/*margin-right: 1px;*/
			margin-bottom : 1px;
			padding       : 5px;
			border        : 1px dashed var(--gray-superlite);
			border-radius : 3px;
			box-sizing    : border-box !important;
		}
	</style>
	<DIV class="zagolovok">Экспорт данных по сделкам</DIV>

	<div id="formtabs" style="max-height: 70vh; overflow-y: auto; overflow-x: hidden" class="p5">

		<div class="flex-container box--child">

			<div class="flex-string wp95">

				<div class="Bold uppercase fs-07 gray2 mt5">Использовать выборки</div>
				<select name="list" id="list" class="wp100" onchange="furl()">
					<optgroup label="Стандартные представления">
						<option value="my" selected="selected">Мои Сделки</option>
						<option value="otdel">Сделки Подчиненных</option>
						<?php if ($tipuser != "Менеджер продаж" || $userRights['alls']) { ?>
							<option value="all">Все Активные <?= $lang['face']['DealsName']['0'] ?></option>
							<option value="alldeals">Все <?= $lang['face']['DealsName']['0'] ?></option>
							<option value="alldealsday">Все <?= $lang['face']['DealsName']['0'] ?>. За сегодня</option>
							<option value="alldealsweek">Все <?= $lang['face']['DealsName']['0'] ?>. За текущую неделю</option>
							<option value="alldealsmounth">Все <?= $lang['face']['DealsName']['0'] ?>. За текущий месяц</option>
						<?php } ?>
						<option value="close">Закрытые <?= $lang['face']['DealsName']['0'] ?></option>
						<option value="closedealsday">Закрытые <?= $lang['face']['DealsName']['0'] ?>. За сегодня</option>
						<option value="closedealsweek">Закрытые <?= $lang['face']['DealsName']['0'] ?>. За текущую неделю</option>
						<option value="closedealsmounth">Закрытые <?= $lang['face']['DealsName']['0'] ?>. За текущий месяц</option>
					</optgroup>
					<optgroup label="Пользовательские представления">
						<?php
						$result = $db -> query("SELECT * FROM {$sqlname}search WHERE tip = 'dog' and (iduser = '$iduser1' or share = 'yes') and identity = '$identity' order by sorder");
						while ($data = $db -> fetch($result)) {
							print '<option value="search:'.$data['seid'].'">'.$data['title'].'</option>';
						}
						?>
					</optgroup>
				</select>
				<div class="fs-07 gray2">Выборку можно создать в разделе "Сделки"</div>

			</div>
			<div class="flex-string wp5">

				<div class="Bold uppercase fs-07 gray2 mt10">&nbsp;</div>
				<div class="tagsmenuToggler hand relativ mt5" data-id="fhelper">
					<span class="fs-14 blue mt5"><i class="icon-help-circled"></i></span>
					<div class="tagsmenu fly1 right hidden" id="fhelper" style="right:0; top: 100%">
						<div class="blok1 w350 fs-09">
							<ul>
								<li>Ознакомьтесь с Документацией на модуль [
									<a href="https://salesman.pro/docs/54" target="_blank" title="Перейти в Документацию">Справка</a> ]
								</li>
								<li>Вы можете использовать <b>поисковые выборки</b> для большей гибкости [
									<a href="https://salesman.pro/docs/45#searcheditor" target="_blank" title="Перейти в Документацию">Справка</a> ]
								</li>
								<li>Если экспорт идет в формате CSV, то данные необходимо
									<b>Импортировать</b> в Excel - Вкладка "Данные" / Из текста
								</li>
								<li>Чем больше информации экспортируется, тем дольше времени занимает этот процесс!</li>
								<li>Для
									<b class="red">исключения полей</b> укажите их в блоке "Исключить" - они не будут выведены в файле экспорта
								</li>
								<li>Возможна загрузка только данных контакта, присоединенного к сдекле. Если контактов несколько, то выбирается первый</li>
							</ul>

						</div>
					</div>
				</div>

			</div>

		</div>

		<div class="divider mt10 mb10"><i class="icon-plus-circled green"></i> Включить</div>

		<div class="flex-container">

			<div class="flex-string">

				<FORM method="post" enctype="multipart/form-data" name="exportInclude" id="exportInclude">
					<div class="flex-container box--child">
						<label class="flex-string wp50 field"><input name="include" id="include" type="checkbox" value="lasthist" onclick="furl()">&nbsp;Дата активности (сделка)</label>
						<label class="flex-string wp50 field"><input name="include" id="include" type="checkbox" value="nexttask" onclick="furl()">&nbsp;Дата след.напоминания (сделка)</label>
						<label class="flex-string wp50 field"><input name="include" id="include" type="checkbox" value="history" onclick="furl()">&nbsp;3 активности (сделка)</label>
						<label class="flex-string wp50 field"><input name="include" id="include" type="checkbox" value="speca" onclick="furl()">&nbsp;Спецификацию (сделка)</label>
						<label class="flex-string wp50 field"><input name="include" id="include" type="checkbox" value="person" onclick="furl()">&nbsp;Контакт (тел. + email)</label>
						<label class="flex-string wp50 field"><input name="include" id="include" type="checkbox" value="tipcmr" onclick="furl()">&nbsp;Тип отношений (клиент)</label>
						<label class="flex-string wp50 field"><input name="include" id="include" type="checkbox" value="clientcategory" onclick="furl()">&nbsp;Отрасль (клиент)</label>
						<label class="flex-string wp50 field"><input name="include" id="include" type="checkbox" value="territory" onclick="furl()">&nbsp;Территорию (клиент)</label>
						<?php
						// добавим кастомные поля клиента
						$cfields = $db -> getAll("SELECT fld_title,fld_name FROM {$sqlname}field WHERE fld_tip = 'client' AND fld_on = 'yes' AND fld_name LIKE 'input%' AND identity = '$identity'");
						foreach ($cfields as $cfield) {

							print '<label class="flex-string wp50 field"><input name="include" id="include" type="checkbox" value="client['.$cfield['fld_name'].']" onclick="furl()">&nbsp;'.$cfield['fld_title'].' (клиент)</label>';

						}
						?>
					</div>
				</FORM>

			</div>

		</div>

		<div class="divider mt10 mb10"><i class="icon-minus-circled red"></i> Исключить</div>

		<div class="flex-container">

			<div class="flex-string">

				<FORM method="post" enctype="multipart/form-data" name="exportExclude" id="exportExclude">
					<div class="flex-container">
						<?php
						$exclude_array = [
							'did',
							'title',
							'clid',
							'pid'
						];
						foreach ($fieldsNames as $k => $v) {

							if (!in_array($k, (array)$exclude_array)) {
								print '<label class="flex-string wp50 field"><input name="exclude" id="exclude" type="checkbox" value="'.$k.'" onclick="furl()">&nbsp;'.$v.'</label>';
							}

						}
						?>
					</div>
				</FORM>

			</div>

		</div>

		<div class="flex-container box--child">

			<div class="flex-string wp100 mt10">
				<div class="Bold uppercase fs-07 gray2">Ссылка для получения XML-данных</div>
				<textarea name="url" id="url" class="wp100" style="height:120px" readonly></textarea>
			</div>

		</div>

		<div class="space-50"></div>

	</div>

	<hr>

	<div class="button--pane text-right">

		<A href="javascript:void(0)" onClick="getFile('csv')" class="button greenbtn">Получить CSV</A>
		<A href="javascript:void(0)" onClick="getFile('xml')" class="button">Получить Excel</A>
		<A href="javascript:void(0)" onClick="DClose()" class="button">Закрыть</A>

	</div>

	<script>

		var url = '<?=$url?>';
		var fullurl = '';

		$(function () {

			furl();

			$('#dialog').css('width', '600px').center();

		});

		function furl() {

			var tar = $('#list option:selected').val()

			var formInclude = $('#exportInclude').serializeArray()
			var formExclude = $('#exportExclude').serializeArray()

			var include = []
			var exclude = []

			//console.log(formInclude)
			//console.log(formExclude)

			$.each(formInclude, function (i, field) {
				include.push(field.value)
			});
			$.each(formExclude, function (i, field) {
				exclude.push(field.value)
			});

			//console.log(include)
			//console.log(exclude)

			var newurl = url + '&include=' + include.join(",") + '&exclude=' + exclude.join(",") + '&tar=' + tar + '&action=get_xml';

			fullurl = newurl + '&save=yes';

			//console.log(newurl)

			$('#url').val(newurl)

		}

		function getFile(format) {

			window.location.href = fullurl + "&format=" + format;
			new DClose();

		}

	</script>
	<?php
}

if ($action == 'get_xml') {

	//print_r($_REQUEST);
	//exit();

	$iduser = $db -> getOne("SELECT iduser FROM {$sqlname}user WHERE ses='".$code."' and identity = '$identity'");

	$includes = yexplode(",", (string)$_REQUEST['include']);
	$excludes = yexplode(",", (string)$_REQUEST['exclude']);

	if ($iduser == '') {

		print 'Доступ запрещен';
		exit();

	}

	$users       = $db -> getIndCol("iduser", "SELECT iduser, title FROM {$sqlname}user WHERE identity = '$identity'");
	$clientpaths = $db -> getIndCol("id", "SELECT id, name FROM {$sqlname}clientpath WHERE identity = '$identity'");

	$tar = ($_REQUEST['tar'] == '') ? "my" : $_REQUEST['tar'];

	//Исключаем поля
	$exclude_array = ['did'];

	//получим поля, которые надо также исключить по запросу
	/*foreach ( $fields as $field ) {

		if ( $_REQUEST[ $field ] == 'no' ) $exclude_array[] = $field;

	}*/
	foreach ($excludes as $ex) {
		$exclude_array[] = $ex;
	}

	array_unshift($fields, 'kol', 'marga', 'datum_izm', 'datum_start', 'datum_end', 'direction', 'close', 'datum_close', 'sid', 'des_fact', 'kol_fact', 'pid_list');

	if($otherSettings['dateFieldForFreeze'] != ''){
		array_unshift($fields, 'isFrozen', $otherSettings['dateFieldForFreeze']);
	}

	array_unshift($fields, 'did', 'uid', 'clid', 'iduser', 'autor', 'datum', 'datum_plan', 'title', 'idcategory');

	foreach ($fields as $key => $field) {

		if (in_array($field, (array)$exclude_array)) {
			unset($fields[ $key ]);
		}

	}

	$fields = array_unique($fields);

	$xq      = '';
	$cfields = $db -> getAll("SELECT fld_title,fld_name FROM {$sqlname}field WHERE fld_tip = 'client' AND fld_on = 'yes' AND fld_name LIKE 'input%' AND identity = '$identity'");
	foreach ($cfields as $cfield) {

		if (in_array('client['.$cfield['fld_name'].']', (array)$includes)) {

			$xq .= "{$sqlname}clientcat.".$cfield['fld_name']." as client".$cfield['fld_name'].",";

		}

	}

	//print
	$query = getFilterQuery('dogovor', [
		'filter'        => $tar,
		'fields'        => $fields,
		'excludeDostup' => true,
		'selectplus'    => "
			{$sqlname}dogcategory.title as step,
			{$sqlname}dogcategory.content as steptitle,
			{$sqlname}dogtips.title as tips,
			{$sqlname}dogstatus.title as dstatus,
			{$sqlname}direction.title as direction,
			".( !in_array('path', (array)$exclude_array) ? "{$sqlname}clientcat.clientpath as clientpath," : "" )."
			".( in_array('tipcmr', (array)$includes) ? "{$sqlname}clientcat.tip_cmr as tip_cmr," : "" )."
			".( in_array('clientcategory', (array)$includes) ? "{$sqlname}clientcat.idcategory as clidcategory," : "" )."
			".( in_array('territory', (array)$includes) ? "{$sqlname}clientcat.territory as territory," : "" )."
			$xq
			{$sqlname}clientcat.date_create as cldcreate,
			{$sqlname}clientcat.uid as cluid
		",
		'freplace'      => [],
		'namereplace'   => false
	], false);

	$query .= " ORDER BY {$sqlname}dogovor.datum_plan";

	//print $query;
	//exit();

	$territories = Guides ::Territory();
	$categories  = Guides ::Industry();
	$otdel       = User ::otdel();

	//print_r($fieldsNames);
	//exit();
	/*выборка*/

	//формируем запрос в БД
	$result = $db -> query($query);

	$deals  = [];
	$header = [];
	$g      = 0;

	while ($da = $db -> fetch($result)) {

		$mcomp = '';

		$header      = [
			"Deal.ID",
			"Rurl"
		];
		$deals[ $g ] = [
			$da['did'],
			$scheme.$_SERVER["HTTP_HOST"]."/card.deal?did=".$da['did']
		];

		$i = 3;

		foreach ($fieldsNames as $k => $v) {

			if (!in_array($k, (array)$exclude_array)) {

				//$client = get_client_info($da['clid'],"yes");

				switch ($k) {
					case 'iduser':
						$string = cleanall($users[ $da['iduser'] ]);
					break;
					case 'autor':
						$string = cleanall($users[ $da['autor'] ]);
					break;
					case 'clid':
						$string = cleanall($da['client']);
					break;
					case 'date_create':

						$string = $da['cldcreate'];

						if ($string == '0000-00-00') {

							$string = '';

						}

					break;
					case 'clientUID':
						$string = cleanall($da['cluid']);
					break;
					case 'payer':
						$string = cleanall(current_client($da['payer']));
					break;
					case 'idcategory':
						$string = $da['step'];
					break;
					case 'tip':
						$string = $da['tips'];
					break;
					case 'datum':
					case 'datum_plan':
					case 'datum_close':
					case 'datum_izm':
					case 'datum_start':
					case 'datum_end':

						$string = '';

						if ($da[ $k ] != '0000-00-00' && $da[ $k ] != '') {
							$string = $da[ $k ];
						}

					break;
					case 'kol':
					case 'kol_fact':
					case 'marga':
						/*if($format == 'xml') $string = n_format($da[$k]);
						else $string = num_format($da[$k]);*/
						$string = $da[ $k ];
					break;
					case 'content':
					case 'des_fact':
						$string = cleanall(clll($da[ $k ]));
					break;
					case 'mcid':
						//$mcomp  = $db -> getOne("SELECT name_shot FROM {$sqlname}mycomps where id = '".$da['mcid']."' and identity = '$identity'");
						//$string = cleanall(clll($mcomp));
						$string = cleanall($da['mcid']);
					break;
					case 'close':
						if ($da['close'] == 'yes') $close = 'Закрыта';
						else $close = 'Активна';
						$string = $close;
					break;
					case 'dog_num':
						$dog_num = ($da['dog_num'] != '' && $da['dog_num'] != 'none') ? $db -> getOne("SELECT number FROM {$sqlname}contract WHERE deid = '".$da['dog_num']."' and identity = '$identity'") : "";
						$string  = $dog_num;
					break;
					case 'status_close':
						$string = cleanall($da['dstatus']);
					break;
					case 'path':
						$string = cleanall($clientpaths[ $da['clientpath'] ]);
						//$v = "Канал";
					break;
					case 'stepDate':

						$stepDay = $db -> getOne("SELECT datum FROM {$sqlname}steplog WHERE did = '".$da['did']."' and step = '".$da['idcategory']."' ORDER BY datum DESC LIMIT 1");
						$string  = $stepDay;

					break;
					default:
						$string = cleanall($da[ $k ]);
					break;

				}

				$deals[ $g ][ $i ] = cleanall($string);
				$header[ $i ]      = str_replace(" ", ".", cleanall($v));
				$i++;

				if ($k == 'clientUID') {

					$deals[ $g ][ $i ] = $da['clid'];
					$header[ $i ]      = 'Client.ID';
					$i++;

				}
				if ($k == 'clid' && in_array('tipcmr', (array)$includes) && $fieldsClientNames['tip_cmr'] != '') {

					$deals[ $g ][ $i ] = $da['tip_cmr'];
					$header[ $i ]      = str_replace(" ", ".", $fieldsClientNames['tip_cmr']);
					$i++;

				}
				if ($k == 'clid' && in_array('clientcategory', (array)$includes) && $fieldsClientNames['idcategory'] != '') {

					$deals[ $g ][ $i ] = $categories[ $da['clidcategory'] ];
					$header[ $i ]      = str_replace(" ", ".", $fieldsClientNames['idcategory']);
					$i++;

				}
				if ($k == 'clid' && in_array('territory', (array)$includes) && $fieldsClientNames['territory'] != '') {

					$deals[ $g ][ $i ] = $territories[ $da['territory'] ];
					$header[ $i ]      = str_replace(" ", ".", $fieldsClientNames['territory']);
					$i++;

				}

				// проходим доп.поля клиентов
				if ($k == 'clid') {

					// $cfields = $db -> getAll("SELECT fld_title,fld_name FROM {$sqlname}field WHERE fld_tip = 'client' AND fld_on = 'yes' AND fld_name LIKE 'input%' AND identity = '$identity'");
					foreach ($cfields as $cfield) {

						if (in_array('client['.$cfield['fld_name'].']', (array)$includes)) {

							$deals[ $g ][ $i ] = cleanall($da[ "client".$cfield['fld_name'] ]);
							$header[ $i ]      = str_replace(" ", ".", $cfield['fld_title']);
							$i++;

						}

					}

				}

				if ($k == 'iduser') {

					$deals[ $g ][ $i ] = $otdel[ $da['iduser'] ];
					$header[ $i ]      = "Отдел";
					$i++;

				}

			}

		}

		if (in_array('lasthist', (array)$includes) == 'yes') {

			$lh = $db -> getOne("SELECT datum FROM {$sqlname}history WHERE did = '".$da['did']."' and tip NOT IN ('ЛогCRM','СобытиеCRM') and identity = '$identity' ORDER BY cid DESC LIMIT 1");

			$deals[ $g ][ $i ] = $lh;
			$header[ $i ]      = 'Последняя активность';

			$i++;

		}

		if (in_array('nexttask', (array)$includes) == 'yes') {

			$lt = $db -> getOne("SELECT CONCAT(datum, ' ', IFNULL(totime, '00:00:00')) FROM {$sqlname}tasks WHERE did = '".$da['did']."' AND active = 'yes' AND identity = '$identity' ORDER BY tid LIMIT 1");

			$deals[ $g ][ $i ] = $lt;
			$header[ $i ]      = 'След.напоминания';

			$i++;

		}

		if (in_array('history', (array)$includes) == 'yes') {

			$hist = '';
			for ($k = 0; $k < 3; $k++) {

				$j = $k + 1;

				$resulth     = $db -> getRow("select datum, des from {$sqlname}history WHERE did='".$da['did']."' and tip NOT IN ('ЛогCRM','СобытиеCRM') and identity = '$identity' ORDER BY cid DESC LIMIT ".$k.", ".$j);
				$datum[ $k ] = $resulth["datum"];
				$des[ $k ]   = cleanall($resulth["des"]);

				if ($des[ $k ]) $hist .= $datum[ $k ].":".$des[ $k ].";";

			}

			$deals[ $g ][ $i ] = cleanall($hist);
			$header[ $i ]      = 'Активности';

			$i++;

		}

		//$g++;

		//контакт
		if (in_array('person', (array)$includes) == 'yes') {

			$p = yexplode(";", (string)getDogData($da['did'], "pid_list"), 0);

			$iperson = get_person_info($p, "yes");

			$header[ $i + 1 ] = 'Контакт';
			$header[ $i + 2 ] = 'Телефон';
			$header[ $i + 3 ] = 'Email';

			$deals[ $g ][ $i + 1 ] = $iperson['person'];
			$deals[ $g ][ $i + 2 ] = prepareMobPhone(yexplode(",", (string)$iperson['tel'], 0));
			$deals[ $g ][ $i + 3 ] = yexplode(",", (string)$iperson['mail'], 0);

			$i += 3;

		}

		$g++;

		//спецификация
		if (in_array('speca', (array)$includes) == 'yes') {

			$r = $db -> getAll("SELECT * FROM {$sqlname}speca WHERE did = '".$da['did']."' and identity = '$identity' ORDER BY title");
			foreach ($r as $d) {

				$deals[ $g ][0] = "*";
				$deals[ $g ][1] = "SPECA";
				$deals[ $g ][2] = $d['title'];
				$deals[ $g ][3] = $d['kol'];
				$deals[ $g ][4] = $d['dop'];
				$deals[ $g ][5] = $d['price'];
				$deals[ $g ][6] = $d['nds'];
				$deals[ $g ][7] = cleanall($d['comments']);

				$g++;

			}

		}

	}

	array_unshift($deals, $header);

	//print_r($deals);
	//exit();

	if ($format == 'xml') {

		/*
		$xls = new Excel_XML('UTF-8', true, 'Deals');
		$xls -> addArray($deals);

		if ($_REQUEST['save'] == 'yes') {
			$xls -> generateXML('export.deals');
		}
		else {
			$xls -> printXML();
		}
		*/

		if ($_REQUEST['save'] == 'yes') {

			Shuchkin\SimpleXLSXGen::fromArray( $deals )->downloadAs('export.deals.xlsx');

		}
		else {

			$xls = new Excel_XML('UTF-8', true, 'Deals');
			$xls -> addArray($deals);
			$xls -> printXML();

		}

	}
	if ($format == 'csv') {

		//проходим массив и формируем csv-файл
		$filename = 'export.deals.csv';
		$fp       = fopen($rootpath.'/files/'.$filename, 'w');

		foreach ($deals as $fields) {
			fputcsv($fp, $fields, ";");
		}

		fclose($fp);

		//logger('4', 'Скачивание данных в CSV', $iduser1);

		header('Content-type: text/csv');
		header('Content-Disposition: attachment; filename="'.$filename.'"');
		header('Content-Transfer-Encoding: binary');
		header('Accept-Ranges: bytes');

		readfile($rootpath.'/files/'.$filename);
		unlink($rootpath.'/files/'.$filename);

	}

}