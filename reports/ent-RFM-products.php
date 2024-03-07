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

$action = $_REQUEST['action'];
$da1    = $_REQUEST['da1'];
$da2    = $_REQUEST['da2'];

$user_list   = (array)$_REQUEST['user_list'];
$fields      = (array)$_REQUEST['field'];
$field_query = (array)$_REQUEST['field_query'];

$sort  = '';
$sortt = '';

$thisfile = basename($_SERVER['PHP_SELF']);

//формирование групп по-умолчанию
if (file_exists('../cash/'.$fpath.'rfmp.json')) {
	$groups = json_decode(file_get_contents('../cash/'.$fpath.'rfmp.json'), true);
}
else {
	$groups = [
		"recency"   => [
			"1" => [
				"min" => "0",
				"max" => "15"
			],
			"2" => [
				"min" => "16",
				"max" => "30"
			],
			"3" => [
				"min" => "31",
				"max" => "60"
			],
			"4" => [
				"min" => "61",
				"max" => "180"
			],
			"5" => [
				"min" => "181",
				"max" => "365"
			]
		],
		"frequency" => [
			"1" => [
				"min" => "20",
				"max" => ""
			],
			"2" => [
				"min" => "15",
				"max" => "19"
			],
			"3" => [
				"min" => "9",
				"max" => "15"
			],
			"4" => [
				"min" => "4",
				"max" => "8"
			],
			"5" => [
				"min" => "0",
				"max" => "3"
			]
		],
		"monetary"  => [
			"1" => [
				"min" => "100001",
				"max" => ""
			],
			"2" => [
				"min" => "60001",
				"max" => "100000"
			],
			"3" => [
				"min" => "40001",
				"max" => "60000"
			],
			"4" => [
				"min" => "20001",
				"max" => "40000"
			],
			"5" => [
				"min" => "0",
				"max" => "20000"
			]
		]
	];
}

$user_list = ( !empty($user_list) ) ? $user_list : (array)get_people($iduser1, "yes");
if (!empty($user_list)) {
	$sort .= " AND deal.iduser IN (".yimplode(",", $user_list).")";
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

	$title = $_REQUEST['title'];

	$query  = "
	SELECT
		sp.spid as spid,
		sp.title as title,
		sp.prid as prid,
		sp.price as price,
		sp.price_in as zakup,
		sp.kol as kol,
		sp.did as did,
		deal.{$datum} as datum,
		deal.close as close,
		deal.iduser as iduser
	FROM {$sqlname}speca `sp`
		LEFT JOIN {$sqlname}dogovor `deal` ON sp.did = deal.did
		LEFT JOIN {$sqlname}price `pr` ON sp.prid = pr.n_id
		LEFT JOIN {$sqlname}user `us` ON deal.iduser = us.iduser
	WHERE
		sp.spid > 0
		$sort
		$sortt and 
		sp.title LIKE '%$title%' and 
		(deal.{$datum} BETWEEN '$da1 00:00:01' and '$da2 23:59:59') and 
		deal.kol_fact > 0 and 
		sp.identity = '$identity'
	GROUP BY deal.did
	ORDER BY deal.{$datum}
	";
	$result = $db -> query($query);
	?>
	<hr>
	<div class="success" style="background: #FFF;">
		<div class="zagolovok_rep green">Данные по запросу</div>
		<?php
		if ($datum == 'datum_close') { ?>
			<div class="margtop10">Закрытые сделки с продажей продукта "<b><?= $title ?></b>" за период <?= format_date_rus($da1) ?>&nbsp;по&nbsp;<?= format_date_rus($da2) ?>
				<div class="pull-aright hand" onclick="$('#detail').toggleClass('hidden');">
					<i class="icon-cancel-circled gray"></i></div>
			</div>
		<?php
		} ?>
		<?php
		if ($datum == 'datum_plan') { ?>
			<div class="margtop10">Активные сделки с плановой датой продажи продукта "<b><?= $title ?></b>" в период <?= format_date_rus($da1) ?>&nbsp;по&nbsp;<?= format_date_rus($da2) ?>
				<div class="pull-aright hand" onclick="$('#detail').toggleClass('hidden');">
					<i class="icon-cancel-circled gray"></i></div>
			</div>
		<?php
		} ?>
		<hr>
		<table id="border" style="background: #FFF;">
			<thead>
			<tr class="header_contaner" style="background: rgba(46,204,113,0.65); color:#000">
				<TH width="20" align="center"></TH>
				<TH align="center" class="yw80">Дата</TH>
				<TH align="center" class="yw40">Дней</TH>
				<TH align="center" class="yw400">Сделка</TH>
				<TH align="right" class="yw80">Кол-во</TH>
				<TH align="right" class="yw100">Цена, <?= $valuta ?></TH>
				<TH align="right" class="yw100">Сумма, <?= $valuta ?></TH>
			</tr>
			</thead>
			<?php
			while ($data = $db -> fetch($result)) {
				$i++;
				$data['price_all'] = $data['price'] * $data['kol'];

				$skol  = $skol + $data['kol'];
				$summa = $summa + $data['price_all'];
				?>
				<tr class="ha" height="35">
					<td align="center"><?= $i ?></td>
					<td align="center" class="smalltxt"><?= format_date_rus($data['datum']) ?></td>
					<td align="center" class="smalltxt"><?= round(diffDate2($data['datum']), 0) ?></td>
					<td>
						<span class="ellipsis" title="<?= $data['title'] ?>"><a href="javascript:void(0)" onclick="openDogovor('<?= $data['did'] ?>','7')" title="Карточка"><i class="icon-briefcase broun"></i>&nbsp;<?= current_dogovor($data['did']) ?></a></span>
					</td>
					<td align="right">
						<span title="<?= num_format($data['kol']) ?>"><?= num_format($data['kol']) ?></span>
					</td>
					<td align="right">
						<span title="<?= num_format($data['price']) ?>"><?= num_format($data['price']) ?> <?= $valuta ?></span>
					</td>
					<td align="right">
						<span title="<?= num_format($data['price_all']) ?>"><?= num_format($data['price_all']) ?> <?= $valuta ?></span>
					</td>
				</tr>
				<?php
			}
			?>
			<tr height="35">
				<td></td>
				<td></td>
				<td></td>
				<td align="right"><b>Итого:</b></td>
				<td align="right">
					<span title="<?= num_format($skol) ?>"><strong><?= num_format($skol) ?></strong></span></td>
				<td align="right"></td>
				<td align="right"><span title="<?= num_format($summa) ?>"><strong><?= num_format($summa) ?></strong></span>
				</td>
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
		<table cellpadding="5" cellspacing="0" width="100%" class="noborder">
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
				<tr height="30" class="ha">
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
		<div align="right">
			<A href="javascript:void(0)" onClick="$('#iform').submit()" class="button"><SPAN>Сохранить</SPAN></A>&nbsp;<A href="javascript:void(0)" onClick="DClose()" class="button"><SPAN>Отмена</SPAN></A>
		</div>
	</form>
	<script type="text/javascript">
		$(document).ready(function () {
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

		if ($value <= $groups[$tip][$i]['max'] and $value >= $groups[$tip][$i]['min']) {
			$group = $i;
		}

	}

	return $group;

}

//проходим клиентов
$query = "
SELECT
	sp.spid as spid,
	sp.title as title,
	sp.prid as prid,
	sp.price as price,
	sp.price_in as zakup,
	sp.kol as kol,
	sp.did as did,
	deal.{$datum} as datum,
	deal.close as close,
	deal.iduser as iduser
FROM {$sqlname}speca `sp`
	LEFT JOIN {$sqlname}dogovor `deal` ON sp.did = deal.did
	LEFT JOIN {$sqlname}price `pr` ON sp.prid = pr.n_id
	LEFT JOIN {$sqlname}user `us` ON deal.iduser = us.iduser
WHERE
	sp.spid > 0 and
	sp.prid > 0
	$sort
	$sortt and 
	(deal.{$datum} BETWEEN '$da1 00:00:01' and '$da2 23:59:59') and 
    deal.kol_fact > 0 and 
    sp.identity = '$identity'
ORDER BY deal.{$datum}
";

//формируем массивы по каждому клиенту
$result = $db -> query($query);
while ($data = $db -> fetch($result)) {

	$da[$data['prid']][] = [
		"datum"     => $data['datum'],
		"title"     => $data['title'],
		"day"       => round(abs(diffDate2($data['datum'])), 0),
		"price"     => $data['price'],
		"price_in"  => $data['price_in'],
		"kol"       => $data['kol'],
		"price_all" => $data['kol'] * $data['price']
	];

}

$gr = $nda = [];

//формируем массив показателей для каждого клиента
foreach ($da as $key => $value) {

	$numbers = array_map(static function ($details) {
		return $details['price_all'];
	}, $value);
	$sum     = array_sum($numbers);

	$numbers = array_map(static function ($details) {
		return $details['day'];
	}, $value);
	$day     = min($numbers);

	$numbers = array_map(static function ($details) {
		return $details['kol'];
	}, $value);
	$kol     = array_sum($numbers);

	$rfm = getRFMGroup('recency', $day) + getRFMGroup('frequency', count($value)) + getRFMGroup('monetary', $sum);
	$rf  = getRFMGroup('recency', $day).getRFMGroup('frequency', count($value));

	$grp1 = getRFMGroup('recency', $day);
	$grp2 = getRFMGroup('frequency', count($value));
	$grp3 = getRFMGroup('monetary', $sum);

	$nda[] = [
		"prid"           => $key,
		"recency"        => $day,
		"recencyGroup"   => $grp1,
		"frequency"      => $kol,
		"frequencyGroup" => $grp2,
		"monetary"       => $sum,
		"monetaryGroup"  => $grp3,
		"rfm"            => $rfm,
		"rf"             => $rf,
		"user"           => $value[0]['user'],
		"iduser"         => $value[0]['iduser'],
		"title"          => $value[0]['title']
	];

	//счетчики по группам для таблиц по группам
	$gr['recency'][$grp1]['count'] += $kol;
	$gr['rcount']                  += $kol;

	$gr['frequency'][$grp2]['count'] += $kol;
	$gr['frcount']                   += $kol;

	$gr['monetary'][$grp3]['count'] += $kol;
	$gr['mcount']                   += $kol;

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
		"Продукт",
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
			$item['title'],
			$item['recency'],
			$item['frequency'],
			$item['monetary'],
			$item['recencyGroup'],
			$item['frequencyGroup'],
			$item['monetaryGroup'],
			$item['rfm']
		];

	}

	Shuchkin\SimpleXLSXGen ::fromArray($otchet) -> downloadAs('export.rfm-product.xlsx');

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

<div class="zagolovok_rep pad5 paddtop10 block">
	<span class="txt-medium">RFM-анализ "Продажи - Продукты" (<?= format_date_rus($da1) ?>&nbsp;по&nbsp;<?= format_date_rus($da2) ?>):</span>
</div>

<hr class="paddtop10">

<?php
if (!empty($gr)) {
	?>

	<div class="Bold miditxt paddtop10 block blue">Кол-во продуктов, последний раз проданных в период "с - по" дней назад (Recency)</div>

	<div style="width:100%; display:table">

		<div class="pull-left" style="width:60%">

			<table width="95%" class="border" style="margin-right: 10px;">
				<thead class="header_contaner">
				<tr>
					<th width="20%">Группа R</th>
					<th width="20%">с</th>
					<th width="20%">по</th>
					<th width="20%">Кол-во</th>
					<th width="20%">Доля группы, %</th>
				</tr>
				</thead>
				<?php
				$dataset1 = [];
				for ($i = 1; $i <= 5; $i++) {
					$gr['rtotal'][$i] = number_format(round(100 * ( $gr['recency'][$i]['count'] / $gr['rcount'] ), 2), 1);
					?>
					<tr height="30" class="ha">
						<td align="center"><?= $i ?></td>
						<td align="center"><?= $groups['recency'][$i]['min'] ?></td>
						<td align="center"><?= $groups['recency'][$i]['max'] ?></td>
						<td align="center" style="background: <?= $colors[$i] ?>;"><?= $gr['recency'][$i]['count'] ?></td>
						<td align="center" style="background: <?= $colors[$i] ?>;"><b><?= $gr['rtotal'][$i] ?></b>
						</td>
					</tr>
					<?php
					$it         = $it + $gr['rtotal'][$i];
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
			<br>

		</div>
		<div class="pull-left jsChart" style="width:40%">
			<canvas id="myChart1" class="chart" height="250"></canvas>
		</div>

	</div>

	<div class="Bold miditxt paddtop10 block blue">Кол-во продуктов, проданных n раз (Frequency)</div>
	<div style="width:100%; display:table">

		<div class="pull-left" style="width:60%">

			<table cellpadding="5" cellspacing="0" width="95%" class="border" style="margin-right: 10px;">
				<thead class="header_contaner">
				<tr>
					<th width="20%">Группа F</th>
					<th width="20%">с</th>
					<th width="20%">по</th>
					<th width="20%">Кол-во</th>
					<th width="20%">Доля группы, %</th>
				</tr>
				</thead>
				<?php
				$it       = 0;
				$dataset2 = [];
				for ($i = 1; $i <= 5; $i++) {
					$gr['frtotal'][$i] = number_format(round(100 * ( $gr['frequency'][$i]['count'] / $gr['frcount'] ), 2), 1);
					?>
					<tr height="30" class="ha">
						<td align="center"><?= $i ?></td>
						<td align="center"><?= $groups['frequency'][$i]['min'] ?></td>
						<td align="center"><?= $groups['frequency'][$i]['max'] ?></td>
						<td align="center" style="background: <?= $colors[$i] ?>;"><?= $gr['frequency'][$i]['count'] ?></td>
						<td align="center" style="background: <?= $colors[$i] ?>;"><b><?= $gr['frtotal'][$i] ?></b>
						</td>
					</tr>
					<?php
					$it         = $it + $gr['frtotal'][$i];
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
			<br>

		</div>
		<div class="pull-left jsChart" style="width:40%">
			<canvas id="myChart2" class="chart" height="250"></canvas>
		</div>

	</div>

	<div class="Bold miditxt paddtop10 block blue">Кол-во продуктов, проданных на сумму "с - по" (Monetary)</div>
	<div style="width:100%; display:table">

		<div class="pull-left" style="width:60%">

			<table cellpadding="5" cellspacing="0" width="95%" class="border" style="margin-right: 10px;">
				<thead class="header_contaner">
				<tr>
					<th width="20%">Группа M</th>
					<th width="20%">с</th>
					<th width="20%">по</th>
					<th width="20%">Кол-во</th>
					<th width="20%">Доля группы, %</th>
				</tr>
				</thead>
				<?php
				$it       = 0;
				$dataset3 = [];
				for ($i = 1; $i <= 5; $i++) {
					$gr['mtotal'][$i] = number_format(round(100 * ( $gr['monetary'][$i]['count'] / $gr['mcount'] ), 1), 1);
					?>
					<tr height="30" class="ha">
						<td align="center"><?= $i ?></td>
						<td align="center"><?= $groups['monetary'][$i]['min'] ?></td>
						<td align="center"><?= $groups['monetary'][$i]['max'] ?></td>
						<td align="center" style="background: <?= $colors[$i] ?>;"><?= $gr['monetary'][$i]['count'] ?></td>
						<td align="center" style="background: <?= $colors[$i] ?>;"><b><?= $gr['mtotal'][$i] ?></b>
						</td>
					</tr>
					<?php
					$it         = $it + pre_format(round(100 * ( $gr['monetary'][$i]['count'] / $gr['mcount'] ), 2));
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
					<th><?= num_format($it) ?></th>
				</tr>
				</tfoot>
			</table>
			<br>

		</div>
		<div class="pull-left jsChart" style="width:40%">
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
		"Ключевой"
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
		"#3498DB",
		"Есть спрос"
	],
	"15" => [
		"#3498DB",
		"Есть спрос"
	],

	"21" => [
		"#FFCE54",
		"Ключевой"
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
		"#3498DB",
		"Есть спрос"
	],
	"25" => [
		"#3498DB",
		"Есть спрос"
	],

	"31" => [
		"#ED5565",
		"Не продаются"
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
		"Не продаются"
	],
	"42" => [
		"#E3868F",
		"Спад продаж"
	],
	"43" => [
		"#E3868F",
		"Спад продаж"
	],
	"44" => [
		"#E3868F",
		"Спад продаж"
	],
	"45" => [
		"#F79256",
		"Разовые"
	],

	"51" => [
		"#C6CBCC",
		"Не продаются"
	],
	"52" => [
		"#C6CBCC",
		"Не продаются"
	],
	"53" => [
		"#C6CBCC",
		"Не продаются"
	],
	"54" => [
		"#C6CBCC",
		"Не продаются"
	],
	"55" => [
		"#C6CBCC",
		"Не продаются"
	],
];
?>
<div style="width:100%; display:table">

	<div class="pull-left" style="width:40%">

		<div class="Bold miditxt blue">RF - матрица</div>
		<table cellpadding="5" cellspacing="0" width="95%" class="border" style="margin-right: 10px;">
			<thead class="header_contaner">
			<tr>
				<th width="10%"></th>
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
				<tr height="30" class="ha">
					<td align="center">F - <?= $i ?></td>
					<?php
					for ($j = 5; $j > 0; $j--) {
						?>
						<td align="center" data-rf="<?= $j.$i ?>" style="background: <?= $matrixColor[$j.$i][0] ?>;" class="hand this mdata"><?= $matrix[$j][$i] ?></td>
					<?php
					} ?>
				</tr>
				<?php
			}
			?>
		</table>

	</div>
	<div class="pull-left" style="width:60%">

		<div class="Bold miditxt block1">Легенда RF - матрицы</div>
		<table cellpadding="5" cellspacing="0" width="95%" class="border" style="margin-right: 10px;">
			<thead class="header_contaner">
			<tr>
				<th width="10%"></th>
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
				<tr height="30" class="ha smalltxt">
					<td align="center">F - <?= $i ?></td>
					<?php
					for ($j = 5; $j > 0; $j--) {
						?>
						<td align="center" class="hand this mgroup" data-rfname="<?= $matrixColor[$j.$i][1] ?>" style="background: <?= $matrixColor[$j.$i][0] ?>;" title="Показать компании"><?= $matrixColor[$j.$i][1] ?></td>
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
<div id="dataset" class="hidden">

	<TABLE width="99%" align="left" cellpadding="5" cellspacing="0" id="bborder">
		<thead style="border: 2px solid #000">
		<TR class="header_contaner" height="28">
			<td rowspan="2" width="20" align="center"><b>#</b></td>
			<td rowspan="2" width="30" align="center"></td>
			<td rowspan="2" class="yw400" align="left"><b>Клиент</b></td>
			<td width="100" align="center"><b>R</b>&nbsp;(recency)</td>
			<td width="100" align="center"><b>F</b>&nbsp;(frequency)</td>
			<td width="100" align="center"><b>M</b>&nbsp;(monetary)</td>
			<td width="100" align="center"><b>R</b>&nbsp;(recency)</td>
			<td width="100" align="center"><b>F</b>&nbsp;(frequency)</td>
			<td width="100" align="center"><b>M</b>&nbsp;(monetary)</td>
			<td rowspan="2" width="150" align="center" class="itog"><b>RF</b></td>
		</TR>
		<TR class="header_contaner" height="28">
			<td width="100" align="center">Дней</td>
			<td width="100" align="center">Кол-во</td>
			<td width="100" align="center">Сумма</td>
			<td colspan="3" width="100" align="center" class="color"><b>Группа</b></td>
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
		for ($i = 0; $i < count($nda); $i++) {
			?>
			<TR class="ha" height="30" data-rf="<?= $nda[$i]['rf'] ?>" data-rfname="<?= $matrixColor[$nda[$i]['rf']][1] ?>">
				<TD align="right"><?= $i + 1 ?>.</TD>
				<TD align="center"><span class="icon-2x"><i class="icon-archive broun"></i></span></TD>
				<TD>
					<span class="ellipsis hand" onclick="editPrice('<?= $nda[$i]['prid'] ?>','view');" title="Просмотр"><?= $nda[$i]['title'] ?></span>
				</TD>
				<TD align="right" class="color hand more" data-prid="<?= $nda[$i]['prid'] ?>" data-title="<?= $nda[$i]['title'] ?>" title="Детали"><?= $nda[$i]['recency'] ?></TD>
				<TD align="right" class="color hand more" data-prid="<?= $nda[$i]['prid'] ?>" data-title="<?= $nda[$i]['title'] ?>" title="Детали"><?= $nda[$i]['frequency'] ?></TD>
				<TD align="right" class="color hand more" data-prid="<?= $nda[$i]['prid'] ?>" data-title="<?= $nda[$i]['title'] ?>" title="Детали"><?= num_format($nda[$i]['monetary']) ?></TD>
				<TD align="center" class="color <?= strtr($nda[$i]['recencyGroup'], $colors) ?>"><?= $nda[$i]['recencyGroup'] ?></TD>
				<TD align="center" class="color <?= strtr($nda[$i]['frequencyGroup'], $colors) ?>"><?= $nda[$i]['frequencyGroup'] ?></TD>
				<TD align="center" class="color <?= strtr($nda[$i]['monetaryGroup'], $colors) ?>"><?= $nda[$i]['monetaryGroup'] ?></TD>
				<TD align="center" class="itog1" style="background: <?= $matrixColor[$nda[$i]['rf']][0] ?>;">
					<?= $nda[$i]['rf'] ?><br>
					<div class="smalltxt"><?= $matrixColor[$nda[$i]['rf']][1] ?></div>
				</TD>
			</TR>
		<?php
		} ?>
		</tbody>
	</TABLE>

</div>

<div id="detail"></div>
<hr>

<div class="infodiv">
	<div class="pad5 Bold toggler2 hand">Расшифровка показателей <i class="icon-angle-down"></i></div>
	<div id="detaile" class="hidden">
		<hr>
		<div class="margbot10">
			<p>Отчет поддерживает дополнительные фильтры параметров сделок. По умолчанию анализируются закрытые за указанный период сделки. При выборе параметра "Статус" = "Активна" период анализа производится по плановой дате реализации сделки.</p>
			<p>Обратите внимание! RFM-анализ расчитан на закрытые сделки, поэтому анализируя открытые (активные) сделки следует иначе интерпретироать данные.</p>
		</div>
		<hr>
		<div class="pull-left">
			<table cellpadding="5" cellspacing="0" width="400" class="border bgwhite" style="margin-right: 10px;">
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
			} ?>
		</div>
		<div class="paddtop10 margleft10">
			<b>Recency (R) — давность последней покупки (давность покупки продукта, дн.)</b>

			<p>Учитывает время в днях, прошедшее с даты последней закрытой сделки, в которой присутствует данный продукт. Рассчитывается как разность между текущей и датой закрытия сделки. Продукты, которые недавно покупали, пользуются большим спросом, чем те, которые давно уже не покупали. Однако присутствует повод задуматься над продвижением таких позиций или пересмотром маркетинговых программ.</p>

			<b>Frequency (F) — суммарная частота покупок (количество позиций продукта, шт.)</b>

			<p>Показывает количество проданных продуктов в течение определённого периода времени.</p>

			<b>Monetary (M) — объём продаж (сумма проданных продуктов, <?= $valuta ?>)</b>

			<p>Как и предыдущие показатели, рассчитывается за определенный период. Показывает какой доход приносит тот или иной продукт, а точнее, сумма денег, которая была получена от продажи. Возможно следует перераспределить маркетинговые бюджеты на продвижение менее продаваемых продуктов.</p>
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
		}

		var myDoughnutChart1 = new Chart(ctx1).Doughnut(data1, options);
		var myDoughnutChart2 = new Chart(ctx2).Doughnut(data2, options);
		var myDoughnutChart3 = new Chart(ctx3).Doughnut(data3, options);

	}

	$('.more').click(function () {
		var title = $(this).data('title');
		var str = $('#selectreport').serialize();
		var url = './reports/<?=$thisfile?>?action=view&title=' + title + '&' + str;

		$('#detail').removeClass('hidden').empty().append('<div id="loader" class="loader"><img src=/assets/images/loading.gif> Вычисление...</div>');

		$.get(url, function (data) {
			$('#detail').html(data);
		})
			.complete(function () {
				var wcoffset = $('#dataview').offset();
				$(".nano").nanoScroller({scrollTop: wcoffset.top});
			});

	});

	$('.toggler1').click(function () {

		$(this).toggleClass('icon-angle-up,icon-angle-down');
		$('#dataset').toggleClass('hidden');

		var wcoffset = $('#datas').offset();
		$(".nano").nanoScroller({scrollTop: wcoffset.top});

	});
	$('.toggler2').click(function () {

		$(this).toggleClass('icon-angle-up,icon-angle-down');
		$('#detaile').toggleClass('hidden');

		var wcoffset = $('.toggler2').offset();
		$(".nano").nanoScroller({scrollTop: wcoffset.top});

	});
	$('.mdata').click(function () {

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
	$('.mgroup').click(function () {

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
	$('.filter').click(function () {

		$('.filter').addClass('hidden');
		$('#dataset').removeClass('hidden');

		$('#bborder tbody tr').each(function () {

			$(this).removeClass('hidden');

		});

		var wcoffset = $('#dataview').offset();
		$(".nano").nanoScroller({scrollTop: wcoffset.top});

	});
</script>