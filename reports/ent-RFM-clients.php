<?php
/**
 * @license  http://isaler.ru/
 * @author   Vladislav Andreev, http://iandreyev.ru/
 * @charset  UTF-8
 * @version  6.4
 */
error_reporting(E_ERROR);
header("Pragma: no-cache");

$rootpath = dirname(__DIR__);

include $rootpath."/inc/config.php";
include $rootpath."/inc/dbconnector.php";
include $rootpath."/inc/auth.php";
include $rootpath."/inc/func.php";
include $rootpath."/inc/settings.php";
include $rootpath."/inc/language/".$language.".php";

global $userRights;

$action = $_REQUEST['action'];
$da1    = $_REQUEST['da1'];
$da2    = $_REQUEST['da2'];
$tar    = $_REQUEST['list'];

$user_list   = (array)$_REQUEST['user_list'];
$fields      = (array)$_REQUEST['field'];
$field_query = (array)$_REQUEST['field_query'];

$sort  = '';
$sortt = '';

$thisfile = basename($_SERVER['PHP_SELF']);

//формирование групп по-умолчанию
if (file_exists($rootpath.'/cash/'.$fpath.'rfm.json')) {

	$groups = json_decode(file_get_contents($rootpath.'/cash/'.$fpath.'rfm.json'), true);

}
else {

	$groups = [
		"recency"   => [
			"1" => [
				"min" => "0",
				"max" => "30"
			],
			"2" => [
				"min" => "31",
				"max" => "60"
			],
			"3" => [
				"min" => "61",
				"max" => "90"
			],
			"4" => [
				"min" => "91",
				"max" => "180"
			],
			"5" => [
				"min" => "181",
				"max" => "365"
			]
		],
		"frequency" => [
			"1" => [
				"min" => "16",
				"max" => ""
			],
			"2" => [
				"min" => "9",
				"max" => "15"
			],
			"3" => [
				"min" => "6",
				"max" => "8"
			],
			"4" => [
				"min" => "2",
				"max" => "5"
			],
			"5" => [
				"min" => "0",
				"max" => "1"
			]
		],
		"monetary"  => [
			"1" => [
				"min" => "1000001",
				"max" => ""
			],
			"2" => [
				"min" => "600001",
				"max" => "1000000"
			],
			"3" => [
				"min" => "400001",
				"max" => "600000"
			],
			"4" => [
				"min" => "200001",
				"max" => "400000"
			],
			"5" => [
				"min" => "0",
				"max" => "200000"
			]
		]
	];

}

$user_list = ( !empty($user_list) ) ? $user_list : (array)get_people($iduser1, "yes");
if (!empty($user_list)) {
	$sort .= " AND deal.iduser IN (".yimplode(",", $user_list).")";
}

//составляем фильтр по представлениям
if ($tar != '') {

	$queryArray = getFilterQuery('client', $param = [
		'filter' => $tar
	]);

	$sortt = str_replace("{$sqlname}clientcat", "cc", $queryArray['sort']);

}

//составляем запрос по параметрам сделок
if (!in_array("close", $fields)) {

	$sort  .= " and COALESCE(deal.close, 'no') = 'yes'";//если в доп.параметрах нет статуса, то считаем только закрытые
	$datum = "datum_close";
	$kol   = "kol_fact";

}
else {

	$index = array_search('close', $fields);

	$sort .= " and COALESCE(deal.close, 'no') = '".$field_query[$index]."'";
	if ($field_query[$index] == 'yes') {
		$datum = "datum_close";
		$kol   = "kol_fact";
	}
	else {
		$datum = "datum_plan";
		$kol   = "kol";
	}

}

$ar = [
	'close'
];
foreach ($fields as $i => $field) {

	if (!in_array($field, $ar)) {
		$sort .= " AND deal.{$field} = '".$field_query[$i]."'";
	}

}

if ($action == "view") {

	$clid = $_REQUEST['clid'];
	$tip  = $_REQUEST['tip'];

	$sort .= "(deal.{$datum} BETWEEN '$da1 00:00:01' and '$da2 23:59:59') and ";

	?>
	<hr>
	<div class="success" id="dataview" style="background: #FFF;">
		<div class="zagolovok_rep green">Данные по запросу
			<div class="pull-aright hand" onclick="$('#detail').toggleClass('hidden');">
				<i class="icon-cancel-circled gray"></i>
			</div>
		</div>
		<hr>
		<table id="border" style="background: #FFF;">
			<thead>
			<tr class="header_contaner" style="background: rgba(46,204,113,0.65); color:#000">
				<TH class="w20"></TH>
				<TH class="yw80">Дата</TH>
				<TH class="yw40">Дней</TH>
				<TH class="yw400">Название</TH>
				<TH class="yw200">Клиент</TH>
				<TH class="yw120">Сумма, <?= $valuta ?></TH>
				<TH class="yw120">Ответств.</TH>
			</tr>
			</thead>
			<?php
			$result = $db -> getAll("SELECT * FROM {$sqlname}dogovor `deal` WHERE deal.did > 0 and deal.clid = '$clid' and $sort deal.identity = '$identity'");
			foreach ($result as $data) {
				$i++;
				if ($data['clid'] > 0) {
					$client = '<div onclick="openClient(\''.$data['clid'].'\')" title="Карточка"><i class="icon-building broun"></i>'.current_client($data['clid']).'</b></div>';
				}
				elseif ($data['pid'] > 0) {
					$client = '<div onclick="openPerson(\''.$data['pid'].'\')" title="Карточка"><i class="icon-user-1 blue"></i>'.current_person($data['pid']).'</b></div>';
				}
				$summa += $data[$kol];
				?>
				<tr class="ha">
					<td class="text-center"><?= $i ?></td>
					<td class="text-center smalltxt"><?= format_date_rus($data[$datum]) ?></td>
					<td class="text-center smalltxt"><?= round(diffDate2($data[$datum]), 0) ?></td>
					<td>
						<span class="ellipsis" title="<?= $data['title'] ?>"><i class="icon-briefcase broun"></i><a href="javascript:void(0)" onclick="openDogovor('<?= $data['did'] ?>','7')" title="Карточка: <?= current_dogovor($data['did']) ?>"><?= current_dogovor($data['did']) ?></a></span>
					</td>
					<td>
						<div class="ellipsis"><?= $client ?></div>
					</td>
					<td class="text-right">
						<span title="<?= num_format($data[$kol]) ?>"><?= num_format($data[$kol]) ?> <?= $valuta ?></span>
					</td>
					<td>
						<span class="ellipsis"><a href="javascript:void(0)" onClick="viewUser('<?= $data['iduser'] ?>');"><?= current_user($data['iduser']) ?></a></span>
					</td>
				</tr>
				<?php
			}
			?>
			<tr>
				<td></td>
				<td></td>
				<td></td>
				<td></td>
				<td class="text-right"><b>Итого:</b></td>
				<td class="text-right">
					<span title="<?= num_format($summa) ?>"><strong><?= num_format($summa) ?></strong> <?= $valuta ?></span>
				</td>
				<td></td>
			</tr>
		</table>
	</div>
	<?php
	exit();
}
if ($action == "edit") {
	?>
	<div class="zagolovok">Редактор настроек групп</div>
	<FORM action="reports/<?= $thisfile ?>" method="post" enctype="multipart/form-data" name="iform" id="iform" autocomplete="off">
		<INPUT type="hidden" name="action" id="action" value="save">
		<table class="noborder">
			<thead class="header_contaner" style="border: 2px solid #000">
			<tr>
				<th rowspan="2" width="30">Группа</th>
				<th colspan="2" align="center">R<br>(давность сделки, дн.)</th>
				<th colspan="2" align="center">F<br>(количество сделок, шт.)</th>
				<th colspan="2" align="center">M<br>(сумма сделок, <?= $valuta ?>)</th>
			</tr>
			<tr>
				<th align="center">min</th>
				<th align="center">max</th>
				<th align="center">min</th>
				<th align="center">max</th>
				<th align="center">min</th>
				<th align="center">max</th>
			</tr>
			</thead>
			<?php
			for ($i = 1; $i <= 5; $i++) {
				?>
				<tr class="ha">
					<td align="center"><?= $i ?></td>
					<td align="center">
						<input name="recencyMin[<?= $i ?>]" type="text" id="recencyMin[<?= $i ?>]" value="<?= $groups['recency'][$i]['min'] ?>" style="width:97%"/>
					</td>
					<td align="center">
						<input name="recencyMax[<?= $i ?>]" type="text" id="recencyMax[<?= $i ?>]" value="<?= $groups['recency'][$i]['max'] ?>" style="width:97%"/>
					</td>
					<td align="center">
						<input name="frequencyMin[<?= $i ?>]" type="text" id="frequencyMin[<?= $i ?>]" value="<?= $groups['frequency'][$i]['min'] ?>" style="width:97%"/>
					</td>
					<td align="center">
						<input name="frequencyMax[<?= $i ?>]" type="text" id="frequencyMax[<?= $i ?>]" value="<?= $groups['frequency'][$i]['max'] ?>" style="width:97%"/>
					</td>
					<td align="center">
						<input name="monetaryMin[<?= $i ?>]" type="text" id="monetaryMin[<?= $i ?>]" value="<?= $groups['monetary'][$i]['min'] ?>" style="width:97%"/>
					</td>
					<td align="center">
						<input name="monetaryMax[<?= $i ?>]" type="text" id="monetaryMax[<?= $i ?>]" value="<?= $groups['monetary'][$i]['max'] ?>" style="width:97%"/>
					</td>
				</tr>
				<?php
			}
			?>
		</table>
		<hr>
		<div class="infodiv">Показатели действуют по всей компании.</div>
		<hr>
		<div class="text-right">
			<A href="javascript:void(0)" onClick="$('#iform').submit()" class="button">Сохранить</A>&nbsp;
			<A href="javascript:void(0)" onClick="DClose()" class="button">Отмена</A>
		</div>
	</form>
	<script>

		$(function () {
			$('#dialog').width('800px');
			$('#iform').ajaxForm({
				dataType: 'json',
				beforeSubmit: function () {
					var $out = $('#message');
					var em = 0;

					$(".required").removeClass("empty").css({"color": "inherit", "background": "#FFF"});
					$(".required").each(function () {

						if ($(this).val() === '') {
							$(this).addClass("empty").css({"color": "red", "background": "#FF8080"});
							em = em + 1;
						}

					});

					$out.empty();

					if (em > 0) {

						alert("Не заполнены обязательные поля\n\rОни выделены цветом");
						return false;

					}
					if (em === 0) {
						$('#dialog').css('display', 'none');
						$('#dialog_container').css('display', 'none');
						$('#message').fadeTo(1, 1).css('display', 'block').append('<div id=loader><img src=/assets/images/loader.gif> Загрузка данных. Пожалуйста подождите...</div>');
						return true;
					}
				},
				success: function (data) {

					$('#dialog_container').css('display', 'none');
					$('#dialog').css('display', 'none');

					$('#message').fadeTo(1, 1).css('display', 'block').html('Результат: ' + data.rez);
					setTimeout(function () {
						$('#message').fadeTo(1000, 0);
					}, 20000);

					generate();

				}
			});
		});
	</script>
	<?php
	exit();
}
if ($action == 'save') {

	$recencyMin   = $_REQUEST['recencyMin'];
	$recencyMax   = $_REQUEST['recencyMax'];
	$frequencyMin = $_REQUEST['frequencyMin'];
	$frequencyMax = $_REQUEST['frequencyMax'];
	$monetaryMin  = $_REQUEST['monetaryMin'];
	$monetaryMax  = $_REQUEST['monetaryMax'];

	$groups = [
		"recency"   => [
			"1" => [
				"min" => $recencyMin[1],
				"max" => $recencyMax[1]
			],
			"2" => [
				"min" => $recencyMin[2],
				"max" => $recencyMax[2]
			],
			"3" => [
				"min" => $recencyMin[3],
				"max" => $recencyMax[3]
			],
			"4" => [
				"min" => $recencyMin[4],
				"max" => $recencyMax[4]
			],
			"5" => [
				"min" => $recencyMin[5],
				"max" => $recencyMax[5]
			]
		],
		"frequency" => [
			"1" => [
				"min" => $frequencyMin[1],
				"max" => $frequencyMax[1]
			],
			"2" => [
				"min" => $frequencyMin[2],
				"max" => $frequencyMax[2]
			],
			"3" => [
				"min" => $frequencyMin[3],
				"max" => $frequencyMax[3]
			],
			"4" => [
				"min" => $frequencyMin[4],
				"max" => $frequencyMax[4]
			],
			"5" => [
				"min" => $frequencyMin[5],
				"max" => $frequencyMax[5]
			]
		],
		"monetary"  => [
			"1" => [
				"min" => $monetaryMin[1],
				"max" => $monetaryMax[1]
			],
			"2" => [
				"min" => $monetaryMin[2],
				"max" => $monetaryMax[2]
			],
			"3" => [
				"min" => $monetaryMin[3],
				"max" => $monetaryMax[3]
			],
			"4" => [
				"min" => $monetaryMin[4],
				"max" => $monetaryMax[4]
			],
			"5" => [
				"min" => $monetaryMin[5],
				"max" => $monetaryMax[5]
			]
		]
	];

	$groups = json_encode_cyr($groups);

	$f    = '../cash/'.$fpath.'rfm.json';
	$file = fopen($f, "w");

	if (!$file) {
		$rez = 'Не могу открыть файл';
	}
	else {

		if (fputs($file, $groups) === false) {
			$rez = 'Ошибка записи';
		}
		else {
			$rez = 'Записано';
		}

		fclose($file);

	}

	print '{"rez":"'.$rez.'"}';

	exit();

}

function getRFMGroup($tip, $value): int {

	$group  = 5;
	$groups = $GLOBALS['groups'];

	for ($i = 1; $i <= 5; $i++) {

		if ($groups[$tip][$i]['max'] == '') {
			$groups[$tip][$i]['max'] = '1000000000000';
		}

		if ($value <= $groups[$tip][$i]['max'] && $value >= $groups[$tip][$i]['min']) {
			$group = $i;
		}

	}

	return $group;

}

//проходим клиентов
$query = "
	SELECT
		cc.clid as clid,
		cc.title as title,
		cc.date_create as date_create,
		cc.iduser as iduser,
		deal.did as did,
		deal.{$kol} as summa,
		deal.{$datum} as datum,
		deal.close as close,
		us.title as user
	FROM {$sqlname}clientcat `cc`
		LEFT JOIN {$sqlname}user `us` ON cc.iduser = us.iduser
		LEFT JOIN {$sqlname}dogovor `deal` ON cc.clid = deal.clid
	WHERE
		cc.clid > 0 
		$sort
		$sortt
		and (deal.{$datum} BETWEEN '$da1 00:00:01' and '$da2 23:59:59')
		and deal.kol_fact > 0
		and cc.identity = '$identity'
	ORDER BY cc.clid
";

//формируем массивы по каждому клиенту
$result = $db -> query($query);
while ($data = $db -> fetch($result)) {

	$da[$data['clid']][] = [
		"datum"  => $data['datum'],
		"day"    => round(abs(diffDate2($data['datum'])), 0),
		"summa"  => $data['summa'],
		"user"   => $data['user'],
		"iduser" => $data['iduser'],
		"client" => $data['title']
	];

}

$gr = $nda = [];

//формируем массив показателей для каждого клиента
foreach ($da as $key => $value) {

	$numbers = array_map(static function ($details) {
		return $details['summa'];
	}, $value);
	$sum     = array_sum($numbers);

	$numbers = array_map(static function ($details) {
		return $details['day'];
	}, $value);
	$day     = min($numbers);

	$rfm = getRFMGroup('recency', $day) + getRFMGroup('frequency', count($value)) + getRFMGroup('monetary', $sum);
	$rf  = getRFMGroup('recency', $day).getRFMGroup('frequency', count($value));

	$grp1 = getRFMGroup('recency', $day);
	$grp2 = getRFMGroup('frequency', count($value));
	$grp3 = getRFMGroup('monetary', $sum);

	$nda[] = [
		"clid"           => $key,
		"recency"        => $day,
		"recencyGroup"   => $grp1,
		"frequency"      => count($value),
		"frequencyGroup" => $grp2,
		"monetary"       => $sum,
		"monetaryGroup"  => $grp3,
		"rfm"            => $rfm,
		"rf"             => $rf,
		"user"           => $value[0]['user'],
		"iduser"         => $value[0]['iduser'],
		"client"         => $value[0]['client']
	];

	//счетчики по группам для таблиц по группам
	$gr['recency'][$grp1]['count'] += 1;
	++$gr['rcount'];

	$gr['frequency'][$grp2]['count'] += 1;
	++$gr['frcount'];

	$gr['monetary'][$grp3]['count'] += 1;
	++$gr['mcount'];

	//счетчик для матрицы
	$matrix[$grp1][$grp2] += 1;

}

$colors = [
	"",
	"#27AE60",
	"#2980B9",
	"#F1C40F",
	"#9B59B6",
	"#95A5A6"
];
//print_r($gr);

function cmp($a, $b): bool {
	return $b['rf'] < $a['rf'];
}

usort($nda, 'cmp');

if ($action == "get_csv") {

	$otchet[] = [
		"#",
		"Клиент",
		"Куратор",
		"F (дней)",
		"R (кол-во)",
		"M (сумма)",
		"F",
		"R",
		"M",
		"FRM"
	];

	foreach ($nda as $i => $item) {

		$j = $i + 1;

		$otchet[] = [
			$j,
			$item['client'],
			$item['user'],
			$item['recency'],
			$item['frequency'],
			$item['monetary'],
			$item['recencyGroup'],
			$item['frequencyGroup'],
			$item['monetaryGroup'],
			$item['rfm']
		];

	}

	Shuchkin\SimpleXLSXGen ::fromArray($otchet) -> downloadAs('export.rfm-client.xlsx');

	exit();

}
?>
<style>
	<!--
	.color1 {
		background: rgba(41, 128, 185, 0.1);
		color: #222;
	}

	.color2 {
		background: rgba(41, 128, 185, 0.4);
		color: #222;
	}

	.color3 {
		background: rgba(41, 128, 185, 0.6);
		color: #222;
	}

	.color4 {
		background: rgba(41, 128, 185, 0.8);
		color: #FFF;
	}

	.color5 {
		background: rgba(41, 128, 185, 1.0);
		color: #FFF;
	}

	.itog {
		background: rgba(46, 204, 113, 0.3);
	}

	/*.color{
		background: rgba(241,196,15,0.3);
	}*/
	thead td.color {
		background: rgba(241, 196, 15, 0.5);
	}

	.color:hover, .color:active {
		background: #F1C40F;
		color: #222;
		font-weight: 700;
		font-size: 1.4em;
		transition: all 400ms ease;
		-webkit-transition: all 400ms ease;
		-moz-transition: all 400ms ease;
	}

	td.this:hover, td.this:active {
		background: none;
		background: #F1C40F;
		color: #222;
		font-weight: 700;
	}

	.itog:hover, .itog:active {
		background: rgba(46, 204, 113, 1.0);;
		color: #FFF;
		font-weight: 700;
		font-size: 1.4em;
		transition: all 400ms ease;
		-webkit-transition: all 400ms ease;
		-moz-transition: all 400ms ease;
	}

	.hist:hover, .hist:active {
		background: rgba(41, 128, 185, 1.0);
		color: #FFF;
		font-weight: 700;
		font-size: 1.4em;
		transition: all 400ms ease;
		-webkit-transition: all 400ms ease;
		-moz-transition: all 400ms ease;
	}

	-->
</style>

<div class="zagolovok_rep pad5 mt20 block">
	<span class="txt-medium">RFM-анализ "Клиенты - Продажи" (<?= format_date_rus($da1) ?>&nbsp;по&nbsp;<?= format_date_rus($da2) ?>):</span>
</div>

<hr class="paddtop10">

<div class="pad10" style="position: absolute; top:5px; right: 20px">
	<span class="select">
	<select name="list" id="list" style="max-width: 250px" onchange="generate();">
		<optgroup label="Стандартные представления">
		<option value="my" <?php if ($tar == 'my') print "selected" ?>>Мои Клиенты</option>
		<option value="fav" <?php if ($tar == 'fav') print "selected" ?>>Ключевые Клиенты</option>
		<option value="otdel" <?php if ($tar == 'otdel') print "selected" ?>>Клиенты Подчиненных</option>
			<?php
			if ($tipuser != "Менеджер продаж" || $userRights['alls']) { ?>
				<option value="all" <?php if ($tar == 'all') print "selected" ?>>Все Клиенты</option>
			<?php
			} ?>
			<option value="trash" <?php if ($tar == 'trash') print "selected" ?>>Корзина, Свободные</option>
		</optgroup>
		<optgroup label="Группы клиентов">
		<?php
		$result = $db -> query("select * from {$sqlname}group WHERE identity = $identity ORDER by name");
		while ($data = $db -> fetch($result)) {
			if ($data['service']) {
				$s = ' *';
			}
			print '<option value="group:'.$data['id'].'">'.$data['name'].$s.'</option>';
		}
		?>
		</optgroup>
		<optgroup label="Настраиваемые представления">
		<?php
		$result = $db -> query("select * from {$sqlname}search where tip='client' and (iduser='".$iduser1."' or share = 'yes') and identity = '$identity' order by sorder");
		while ($data_array = $db -> fetch($result)) {
			if ($tar == "search:".$data_array['seid']) {
				$s = "selected";
			}
			else {
				$s = '';
			}
			print '<option value="search:'.$data_array['seid'].'" '.$s.'>'.$data_array['title'].'</option>';
		}
		?>
		</optgroup>
	</select>
	</span>
</div>

<?php
if (!empty($gr)) {
	?>

	<div class="Bold miditxt paddtop10 block blue">Кол-во клиентов, сделавших последнюю покупку в период "с - по" дней назад (Recency)</div>
	<div style="width:100%; display:table">

		<div class="pull-left wp60">

			<table class="wp95 border mr10">
				<thead class="header_contaner">
				<tr>
					<th class="wp20">Группа R</th>
					<th class="wp20">с</th>
					<th class="wp20">по</th>
					<th class="wp20">Кол-во</th>
					<th class="wp20">Доля группы, %</th>
				</tr>
				</thead>
				<?php
				$dataset1 = [];
				for ($i = 1; $i <= 5; $i++) {

					$gr['rtotal'][$i] = number_format(round(100 * ( $gr['recency'][$i]['count'] / $gr['rcount'] ), 2), 1);
					?>
					<tr class="ha">
						<td><?= $i ?></td>
						<td><?= $groups['recency'][$i]['min'] ?></td>
						<td><?= $groups['recency'][$i]['max'] ?></td>
						<td style="background: <?= $colors[$i] ?>;"><?= $gr['recency'][$i]['count'] ?></td>
						<td style="background: <?= $colors[$i] ?>;"><b><?= $gr['rtotal'][$i] ?></b></td>
					</tr>
					<?php
					$it         += $gr['rtotal'][$i];
					$dataset1[] = '{value: '.$gr['rtotal'][$i].', color: "'.$colors[$i].'", highlight: "rgba(41,128,185,0.5)", label: "Группа '.$i.'"}';
				}
				$dataset1 = implode(",", $dataset1);
				?>
				<tfoot class="header_contaner">
				<tr>
					<th></th>
					<th></th>
					<th></th>
					<th><?= $gr['rcount'] ?></th>
					<th><?= $it ?></th>
				</tr>
				</tfoot>
			</table>

		</div>

		<div class="pull-left jsChart wp40">
			<canvas id="myChart1" class="chart" height="250"></canvas>
		</div>

	</div>

	<div class="Bold miditxt paddtop10 block blue">Кол-во клиентов, сделавших n покупок (Frequency)</div>
	<div style="width:100%; display:table">

		<div class="pull-left wp60">

			<table class="wp95 border mr10">
				<thead class="header_contaner">
				<tr>
					<th class="wp20">Группа F</th>
					<th class="wp20">с</th>
					<th class="wp20">по</th>
					<th class="wp20">Кол-во</th>
					<th class="wp20">Доля группы, %</th>
				</tr>
				</thead>
				<?php
				$it       = 0;
				$dataset2 = [];
				for ($i = 1; $i <= 5; $i++) {

					$gr['frtotal'][$i] = number_format(round(100 * ( $gr['frequency'][$i]['count'] / $gr['frcount'] ), 2), 1);
					?>
					<tr class="ha">
						<td class="text-center"><?= $i ?></td>
						<td class="text-center"><?= $groups['frequency'][$i]['min'] ?></td>
						<td class="text-center"><?= $groups['frequency'][$i]['max'] ?></td>
						<td class="text-center" style="background: <?= $colors[$i] ?>;"><?= $gr['frequency'][$i]['count'] ?></td>
						<td class="text-center" style="background: <?= $colors[$i] ?>;"><b><?= $gr['frtotal'][$i] ?></b>
						</td>
					</tr>
					<?php
					$it         += $gr['frtotal'][$i];
					$dataset2[] = '{value: '.$gr['frtotal'][$i].', color: "'.$colors[$i].'", highlight: "rgba(41,128,185,0.5)", label: "Группа '.$i.'"}';
				}
				$dataset2 = implode(",", $dataset2);
				?>
				<tfoot class="header_contaner">
				<tr>
					<th></th>
					<th></th>
					<th></th>
					<th><?= $gr['frcount'] ?></th>
					<th><?= $it ?></th>
				</tr>
				</tfoot>
			</table>

		</div>

		<div class="pull-left jsChart wp40">
			<canvas id="myChart2" class="chart" height="250"></canvas>
		</div>

	</div>

	<div class="Bold miditxt paddtop10 block blue">Кол-во клиентов, сделавших покупок на сумму "с - по" (Monetary)</div>
	<div style="width:100%; display:table">

		<div class="pull-left wp60">

			<table class="wp95 border mr10">
				<thead class="header_contaner">
				<tr>
					<th class="wp20">Группа M</th>
					<th class="wp20">с</th>
					<th class="wp20">по</th>
					<th class="wp20">Кол-во</th>
					<th class="wp20">Доля группы, %</th>
				</tr>
				</thead>
				<?php
				$it       = 0;
				$dataset3 = [];
				for ($i = 1; $i <= 5; $i++) {

					$gr['mtotal'][$i] = number_format(round(100 * ( $gr['monetary'][$i]['count'] / $gr['mcount'] ), 2), 1);
					?>
					<tr class="ha">
						<td class="text-center"><?= $i ?></td>
						<td class="text-center"><?= $groups['monetary'][$i]['min'] ?></td>
						<td class="text-center"><?= $groups['monetary'][$i]['max'] ?></td>
						<td class="text-center" style="background: <?= $colors[$i] ?>;"><?= $gr['monetary'][$i]['count'] ?></td>
						<td class="text-center" style="background: <?= $colors[$i] ?>;"><b><?= $gr['mtotal'][$i] ?></b></td>
					</tr>
					<?php
					$it         += round(100 * ( $gr['monetary'][$i]['count'] / $gr['mcount'] ), 1);
					$dataset3[] = '{value: '.$gr['mtotal'][$i].', color: "'.$colors[$i].'", highlight: "rgba(41,128,185,0.5)", label: "Группа '.$i.'"}';
				}
				$dataset3 = implode(",", $dataset3);
				?>
				<tfoot class="header_contaner">
				<tr>
					<th></th>
					<th></th>
					<th></th>
					<th><?= $gr['mcount'] ?></th>
					<th><?= $it ?></th>
				</tr>
				</tfoot>
			</table>

		</div>

		<div class="pull-left jsChart wp40">
			<canvas id="myChart3" class="chart" height="250"></canvas>
		</div>

	</div>

<?php
} ?>

<?php
//цвеовая схема матрицы
$matrixColor = [
	"11" => [
		"#FFCE54",
		"VIP"
	],
	"12" => [
		"#A0D468",
		"Норма"
	],
	"13" => [
		"#A0D468",
		"Норма"
	],
	"14" => [
		"#E6E9ED",
		"Новички"
	],
	"15" => [
		"#E6E9ED",
		"Новички"
	],

	"21" => [
		"#FFCE54",
		"VIP"
	],
	"22" => [
		"#A0D468",
		"Норма"
	],
	"23" => [
		"#A0D468",
		"Норма"
	],
	"24" => [
		"#E6E9ED",
		"Новички"
	],
	"25" => [
		"#E6E9ED",
		"Новички"
	],

	"31" => [
		"#ED5565",
		"Уходящие VIP"
	],
	"32" => [
		"#A0D468",
		"Норма"
	],
	"33" => [
		"#A0D468",
		"Норма"
	],
	"34" => [
		"#A0D468",
		"Норма"
	],
	"35" => [
		"#A0D468",
		"Норма"
	],

	"41" => [
		"#C6CBCC",
		"Потерянные VIP"
	],
	"42" => [
		"#E3868F",
		"Уходящие"
	],
	"43" => [
		"#E3868F",
		"Уходящие"
	],
	"44" => [
		"#E3868F",
		"Уходящие"
	],
	"45" => [
		"#F79256",
		"Разовый"
	],

	"51" => [
		"#C6CBCC",
		"Потерянные VIP"
	],
	"52" => [
		"#C6CBCC",
		"Потерянные"
	],
	"53" => [
		"#C6CBCC",
		"Потерянные"
	],
	"54" => [
		"#C6CBCC",
		"Потерянные"
	],
	"55" => [
		"#C6CBCC",
		"Потерянные"
	],
];
?>
<div style="width:100%; display:table">

	<div class="pull-left wp40">

		<div class="Bold miditxt blue">RF - матрица</div>
		<table width="95%" class="border" style="margin-right: 10px;">
			<thead class="header_contaner">
			<tr>
				<th class="wp10"></th>
				<?php
				for ($j = 5; $j > 0; $j--) {
					?>
					<th width="18%">R - <?= $j ?></th>
				<?php
				} ?>
			</tr>
			</thead>
			<?php
			for ($i = 1; $i <= 5; $i++) {
				?>
				<tr  class="ha">
					<td class="text-center">F - <?= $i ?></td>
					<?php
					for ($j = 5; $j > 0; $j--) {
						?>
						<td data-rf="<?= $j.$i ?>" style="background: <?= $matrixColor[$j.$i][0] ?>;" class="text-center hand this mdata"><?= $matrix[$j][$i] ?></td>
					<?php
					} ?>
				</tr>
				<?php
			}
			?>
		</table>

	</div>
	<div class="pull-left wp60">

		<div class="Bold miditxt block1">Легенда RF - матрицы</div>
		<table class="w95 border" style="margin-right: 10px;">
			<thead class="header_contaner">
			<tr>
				<th class="wp10"></th>
				<?php
				for ($i = 5; $i > 0; $i--) {
					?>
					<th width="18%">R - <?= $i ?></th>
				<?php
				} ?>
			</tr>
			</thead>
			<?php
			for ($i = 1; $i <= 5; $i++) {
				?>
				<tr class="ha smalltxt">
					<td class="text-center">F - <?= $i ?></td>
					<?php
					for ($j = 5; $j > 0; $j--) {
						?>
						<td class="text-center hand this mgroup" data-rfname="<?= $matrixColor[$j.$i][1] ?>" style="background: <?= $matrixColor[$j.$i][0] ?>;" title="Показать компании"><?= $matrixColor[$j.$i][1] ?></td>
					<?php
					} ?>
				</tr>
				<?php
			}
			?>
		</table>

	</div>

</div>

<hr class="paddtop10" id="datas">

<div class="Bold miditxt paddtop10 block blue relativ">

	<div class="toggler1 hand">
		<span>Данные для анализа <i class="icon-angle-down"></i></span>
		<span class="noprint paddright10 smalltxt"><a href="javascript:void(0)" onClick="generate_csv()" title="Экспорт в Excel" class="blue"><i class="icon-file-excel"></i>Скачать CSV</a></span>
	</div>
	<div class="hidden pull-aright filter hand smalltxt paddright15">
		<i class="icon-filter"><i class="sup icon-cancel red"></i></i>&nbsp;Снять фильтр
	</div>

</div>

<div id="dataset" class="hidden1">

	<TABLE width="99%" id="bborder">
		<thead style="border: 2px solid #000">
		<TR class="header_contaner">
			<th rowspan="2" class="w20"><b>#</b></th>
			<th rowspan="2" class="w30"></th>
			<th rowspan="2" class="yw400 text-left"><b>Клиент</b></th>
			<th class="w100"><b>R</b>&nbsp;(recency)</th>
			<th class="w100"><b>F</b>&nbsp;(frequency)</th>
			<th class="w100"><b>M</b>&nbsp;(monetary)</th>
			<th class="w100"><b>R</b>&nbsp;(recency)</th>
			<th class="w100"><b>F</b>&nbsp;(frequency)</th>
			<th class="w100"><b>M</b>&nbsp;(monetary)</th>
			<th rowspan="2" class="w150 itog"><b>RF</b></th>
		</TR>
		<TR class="header_contaner">
			<th class="w100">Дней</th>
			<th class="w100">Кол-во</th>
			<th class="w100">Сумма</th>
			<th colspan="3" class="w100 color"><b>Группа</b></th>
		</TR>
		</thead>
		<tbody>
		<?php
		$colors = [
			"1" => "color5",
			"2" => "color4",
			"3" => "color3",
			"4" => "color2",
			"5" => "color1"
		];
		foreach ($nda as $i => $item) {
			?>
			<TR class="ha" data-rf="<?= $item['rf'] ?>" data-rfname="<?= $matrixColor[$item['rf']][1] ?>">
				<TD class="text-right"><?= $i + 1 ?>.</TD>
				<TD class="text-center" onclick="openClient('<?= $item['clid'] ?>')" title="Открыть карточку">
					<span class="icon-2x"><i class="icon-building broun hand"></i></span>
				</TD>
				<TD>
					<span class="ellipsis hand" onclick="viewClient('<?= $item['clid'] ?>')" title="Просмотр"><?= $item['client'] ?></span><br>
					<span class="ellipsis blue"><?= $item['user'] ?></span>
				</TD>
				<TD class="text-right color hand more" data-clid="<?= $item['clid'] ?>" data-tip="recency" title="Детали"><?= $item['recency'] ?></TD>
				<TD class="text-right color hand more" data-clid="<?= $item['clid'] ?>" data-tip="frequency" title="Детали"><?= $item['frequency'] ?></TD>
				<TD class="text-right color hand more" data-clid="<?= $item['clid'] ?>" data-tip="monetary" title="Детали"><?= num_format($item['monetary']) ?></TD>
				<TD class="text-center color <?= strtr($item['recencyGroup'], $colors) ?>"><?= $item['recencyGroup'] ?></TD>
				<TD class="text-center color <?= strtr($item['frequencyGroup'], $colors) ?>"><?= $item['frequencyGroup'] ?></TD>
				<TD class="text-center color <?= strtr($item['monetaryGroup'], $colors) ?>"><?= $item['monetaryGroup'] ?></TD>
				<TD class="text-center itog1" style="background: <?= $matrixColor[$item['rf']][0] ?>;">
					<?= $item['rf'] ?><br>
					<div class="smalltxt"><?= $matrixColor[$item['rf']][1] ?></div>
				</TD>
			</TR>
		<?php
		} ?>
		</tbody>
	</TABLE>

</div>

<div id="detail"></div>

<hr>

<div class="viewdiv">

	<div class="pad5 Bold toggler2 hand">Расшифровка показателей <i class="icon-angle-down"></i></div>

	<div id="detaile" class="hidden">

		<hr>

		<div class="margbot10">
			<p>Отчет поддерживает дополнительные фильтры параметров сделок. По умолчанию анализируются закрытые за указанный период сделки. При выборе параметра "Статус" = "Активна" период анализа производится по плановой дате реализации сделки.</p>
			<p>Обратите внимание! RFM-анализ расчитан на закрытые сделки, поэтому анализируя открытые (активные) сделки следует иначе интерпретироать данные.</p>
		</div>

		<hr>

		<div class="pull-left">

			<table width="400" class="border bgwhite" style="margin-right: 10px;">
				<thead>
				<tr>
					<th width="30">Группа</th>
					<th>R</th>
					<th>F</th>
					<th>M</th>
				</tr>
				</thead>
				<?php
				for ($i = 1; $i <= 5; $i++) {
					?>
					<tr height="30" class="ha">
						<td align="center"><?= $i ?></td>
						<td align="center"><?= $groups['recency'][$i]['min'] ?> &divide; <?= $groups['recency'][$i]['max'] ?></td>
						<td align="center"><?= $groups['frequency'][$i]['min'] ?> &divide; <?= $groups['frequency'][$i]['max'] ?></td>
						<td align="center"><?= $groups['monetary'][$i]['min'] ?> &divide; <?= $groups['monetary'][$i]['max'] ?></td>
					</tr>
					<?php
				}
				?>
			</table>
			<?php
			if ($tipuser == 'Руководитель организации' or $isadmin == 'on') {
				?>
				<hr>
				<span id="greenbutton" class="noprint"><a href="javascript:void(0)" onclick="doLoad('reports/<?= $thisfile ?>?action=edit')" class="button"><i class="icon-pencil"></i>Изменить</a></span>
			<?php
			}
			?>

		</div>

		<div class="paddtop10 margleft10">
			<b>Recency (R) — давность последней покупки (давность сделки, дн.)</b>

			<p>Учитывает время в днях, прошедшее с даты последней закрытой сделки. Рассчитывается как разность между текущей и датой закрытия сделки. Клиенты, которые недавно совершали у вас покупки, более предрасположены к повторным заказам, чем те, кто давно уже не проявлял никаких действий. Клиентов, которые покупали давно, можно вернуть только предложениями, которые привлекают вернуться обратно.</p>

			<b>Frequency (F) — суммарная частота покупок (количество сделок, шт.)</b>

			<p>Показывает сколько сделок в течение определённого периода времени было у вас с клиентом. Если обе стороны остались довольны — есть шанс поддержать частоту покупок или увеличить в свою пользу. Чем больше клиент совершал покупок у вас, тем больше вероятность, что он их будет повторять и в будущем. Этот показатель тесно взаимосвязан с давностью покупки.</p>

			<b>Monetary (M) — объём покупок (сумма сделок, <?= $valuta ?>)</b>

			<p>Как и предыдущие показатели, рассчитывается за определенный период. Показывает какой была «стоимость клиентов» с точки зрения доходов и прибыльности, а точнее, сумма денег, которая была получена от продажи. Сгруппированные по денежным показателям анализы часто получают представление клиентов, чьи покупки отражают более высокую ценность для вашего бизнеса.</p>
		</div>

	</div>

</div>

<div style="height: 70px;"></div>

<script src="/assets/js/chartjs/Chart.js"></script>

<script>

	$(function () {

		$('.chart').width('250');
		drawChart();

	});

	function drawChart() {

		var data1 = [<?=$dataset1?>];
		var data2 = [<?=$dataset2?>];
		var data3 = [<?=$dataset3?>];

		var ctx1 = document.getElementById("myChart1").getContext("2d");
		var ctx2 = document.getElementById("myChart2").getContext("2d");
		var ctx3 = document.getElementById("myChart3").getContext("2d");

		var options = {
			segmentShowStroke: true,
			segmentStrokeColor: "#FFF",
			segmentStrokeWidth: 1,
			percentageInnerCutout: 30,
			animationSteps: 100,
			animationEasing: "easeOutBounce",
			animateRotate: true,
			animateScale: false,
			responsive: false,
			tooltipTemplate: "<%if (label){%><%=label%>: <%}%><%= value %>%"
		};

		var myDoughnutChart1 = new Chart(ctx1).Doughnut(data1, options);
		var myDoughnutChart2 = new Chart(ctx2).Doughnut(data2, options);
		var myDoughnutChart3 = new Chart(ctx3).Doughnut(data3, options);

	}

	$('.more').on('click', function () {

		var tip = $(this).data('tip');
		var clid = $(this).data('clid');
		var str = $('#selectreport').serialize();
		var url = '/reports/<?=$thisfile?>?action=view&tip=' + tip + '&clid=' + clid + '&' + str;

		$('#detail').removeClass('hidden').empty().append('<div id="loader" class="loader"><img src=/assets/images/loading.gif> Вычисление...</div>');

		$.get(url, function (data) {

			$('#detail').html(data);

		})
			.done(function () {

				var wcoffset = $('#dataview').offset();
				$(".nano").nanoScroller({scrollTop: wcoffset.top});

			});

	});

	$('.toggler1').on('click', function () {

		//$(this + ' i').toggleClass('icon-angle-up,icon-angle-down');
		$('#dataset').toggleClass('hidden');

		var wcoffset = $('#datas').offset();
		$(".nano").nanoScroller({scrollTop: wcoffset.top});

	});

	$('.toggler2').on('click', function () {

		$(this).toggleClass('icon-angle-up,icon-angle-down');
		$('#detaile').toggleClass('hidden');

		var wcoffset = $('.toggler2').offset();
		$(".nano").nanoScroller({scrollTop: wcoffset.top});

	});

	$('.mdata').on('click', function () {

		var tip = $(this).data('rf');

		$('#dataset').removeClass('hidden');
		$('.filter').removeClass('hidden');

		var wcoffset = $('#dataset').offset();
		$(".nano").nanoScroller({scrollTop: wcoffset.top});

		$('#bborder tbody tr').each(function () {

			if ($(this).data('rf') != tip) $(this).addClass('hidden');
			else $(this).removeClass('hidden');

		});

	});
	$('.mgroup').on('click', function () {

		var tip = $(this).data('rfname');

		$('#dataset').removeClass('hidden');
		$('.filter').removeClass('hidden');

		var wcoffset = $('#dataset').offset();
		$(".nano").nanoScroller({scrollTop: wcoffset.top});

		$('#bborder tbody tr').each(function () {

			if ($(this).data('rfname') != tip) $(this).addClass('hidden');
			else $(this).removeClass('hidden');

		});
	});
	$('.filter').on('click', function () {

		$('.filter').addClass('hidden');
		$('#dataset').removeClass('hidden');

		var wcoffset = $('#dataset').offset();
		$(".nano").nanoScroller({scrollTop: wcoffset.top});

		$('#bborder tbody tr').each(function () {

			$(this).removeClass('hidden');

		});

	});

</script>