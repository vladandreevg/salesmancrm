<?php
/**
 * @license  http://isaler.ru/
 * @author   Vladislav Andreev, http://iandreyev.ru/
 * @charset  UTF-8
 * @version  6.4
 */

error_reporting( E_ERROR );
header( "Pragma: no-cache" );

$rootpath = dirname(__DIR__);

include $rootpath."/inc/config.php";
include $rootpath."/inc/dbconnector.php";
include $rootpath."/inc/auth.php";
include $rootpath."/inc/func.php";
include $rootpath."/inc/settings.php";
include $rootpath."/inc/language/".$language.".php";

global $userRights;

$action    = $_REQUEST['action'];
$da1       = $_REQUEST['da1'];
$da2       = $_REQUEST['da2'];

$user_list   = (array)$_REQUEST['user_list'];
$fields      = (array)$_REQUEST['field'];
$field_query = (array)$_REQUEST['field_query'];

$sort        = '';
$sortt       = '';

$thisfile = basename( $_SERVER['PHP_SELF'] );

$user_list = ( !empty($user_list) ) ? $user_list : (array)get_people($iduser1, "yes");
if (!empty($user_list)) {
	$sort .= " AND deal.iduser IN (".yimplode(",", $user_list).")";
}

//составляем запрос по параметрам сделок, учитываем только закрытые сделки, иначе нет смысла в отчете
$sort  .= " and COALESCE(deal.close, 'no') = 'yes'";//если в доп.параметрах нет статуса, то считаем только закрытые

$datum = "datum_close";
$kol   = "kol_fact";

$ar = ['close'];
foreach ($fields as $i => $field) {

	if (
		!in_array($field, $ar) && !in_array($field, [
			'close',
			'mcid'
		])
	) {
		$sort .= " AND deal.{$field} = '".$field_query[$i]."'";
	}
	elseif ($field == 'close') {
		$sort .= $field_query[$i] != 'yes' ? " AND COALESCE(deal.{$field}, 'no') != 'yes' AND " : " COALESCE(deal.{$field}, 'no') == 'yes'";
	}
	elseif ($field == 'mcid') {
		$mc = $field_query[$i];
	}

}

if ( $action == "view" ) {

	$prid  = $_REQUEST['prid'];
	$title = $_REQUEST['title'];

	$sort  .= " and (deal.{$datum} BETWEEN '$da1 00:00:01' and '$da2 23:59:59')";

	$query = "
	SELECT
		speca.spid as spid,
		speca.title as title,
		speca.prid as prid,
		speca.price as price,
		speca.price_in as zakup,
		speca.kol as kol,
		speca.did as did,
		deal.{$datum} as datum,
		deal.title as dealname,
		deal.close as close,
		deal.clid as clid,
		cc.title as client,
		deal.iduser as iduser
	FROM {$sqlname}speca `speca`
		LEFT JOIN {$sqlname}dogovor `deal` ON speca.did = deal.did
		LEFT JOIN {$sqlname}clientcat `cc` ON deal.clid = cc.clid
		LEFT JOIN {$sqlname}user ON deal.iduser = {$sqlname}user.iduser
	WHERE
		speca.spid > 0
		$sort
		and speca.prid = '$prid'
		and (deal.{$datum} BETWEEN '$da1 00:00:01' and '$da2 23:59:59')
		and deal.kol_fact > 0
		and speca.identity = '$identity'
	GROUP BY deal.did
	ORDER BY deal.{$datum}
	";
	?>

	<hr>

	<div class="success" id="dataview" style="background: #FFF;">

		<div class="zagolovok_rep green">Данные по запросу
			<div class="pull-aright hand" onclick="$('#detail').toggleClass('hidden');"><i class="icon-cancel-circled gray"></i></div>
		</div>

		<div class="margtop10">Закрытые сделки с продажей продукта "<b><?= $title ?></b>" за период <?= format_date_rus( $da1 ) ?>&nbsp;по&nbsp;<?= format_date_rus( $da2 ) ?></div>

		<hr>

		<table id="border" style="background: #FFF;">
			<thead>
			<tr class="header_contaner" style="background: rgba(46,204,113,0.65); color:#000">
				<TH width="20" align="center"></TH>
				<TH align="center" class="yw80">Дата</TH>
				<TH align="center" class="yw40">Дней</TH>
				<TH align="center" class="yw250">Продукт</TH>
				<TH align="center" class="yw400">Сделка</TH>
				<TH align="right" class="yw80">Кол-во</TH>
				<TH align="right" class="yw100">Цена, <?= $valuta ?></TH>
				<TH align="right" class="yw100">Сумма, <?= $valuta ?></TH>
			</tr>
			</thead>
			<?php
			$result = $db -> getAll( $query );
			foreach ( $result as $data ) {

				$i++;
				$data['price_all'] = $data['price'] * $data['kol'];

				$skol  += $data['kol'];
				$summa += $data['price_all'];
				?>
				<tr class="ha">
					<td align="center"><?= $i ?></td>
					<td align="center" class="smalltxt"><?= format_date_rus( $data['datum'] ) ?></td>
					<td align="center" class="smalltxt"><?= round( diffDate2( $data['datum'] ), 0 ) ?></td>
					<td>
						<span class="ellipsis" title="<?= $data['client'] ?>"><a href="javascript:void(0)" onclick="openClient('<?= $data['clid'] ?>','4')" title="Карточка"><i class="icon-building broun"></i>&nbsp;<?= $data['client'] ?></a></span>
					</td>
					<td>
						<span class="ellipsis" title="<?= $data['dealname'] ?>"><a href="javascript:void(0)" onclick="openDogovor('<?= $data['did'] ?>','7')" title="Карточка"><i class="icon-briefcase broun"></i>&nbsp;<?= $data['dealname'] ?></a></span>
					</td>
					<td align="right">
						<span title="<?= num_format( $data['kol'] ) ?>"><?= num_format( $data['kol'] ) ?></span>
					</td>
					<td align="right">
						<span title="<?= num_format( $data['price'] ) ?>"><?= num_format( $data['price'] ) ?> <?= $valuta ?></span>
					</td>
					<td align="right">
						<span title="<?= num_format( $data['price_all'] ) ?>"><?= num_format( $data['price_all'] ) ?> <?= $valuta ?></span>
					</td>
				</tr>
				<?php
			}
			?>
			<tr height="35">
				<td></td>
				<td></td>
				<td></td>
				<td></td>
				<td align="right"><b>Итого:</b></td>
				<td align="right">
					<span title="<?= num_format( $skol ) ?>"><strong><?= num_format( $skol ) ?></strong></span>
				</td>
				<td align="right"></td>
				<td align="right">
					<span title="<?= num_format( $summa ) ?>"><strong><?= num_format( $summa ) ?></strong></span>
				</td>
			</tr>
		</table>
	</div>
	<?php

	exit();

}
if ( $action == "edit" ) {

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
			for ( $i = 1; $i <= 5; $i++ ) {
				?>
				<tr height="30" class="ha">
					<td align="center"><?= $i ?></td>
					<td align="center">
						<input name="recencyMin[<?= $i ?>]" type="text" id="recencyMin[<?= $i ?>]" value="<?= $groups['recency'][ $i ]['min'] ?>" style="width:97%"/>
					</td>
					<td align="center">
						<input name="recencyMax[<?= $i ?>]" type="text" id="recencyMax[<?= $i ?>]" value="<?= $groups['recency'][ $i ]['max'] ?>" style="width:97%"/>
					</td>
					<td align="center">
						<input name="frequencyMin[<?= $i ?>]" type="text" id="frequencyMin[<?= $i ?>]" value="<?= $groups['frequency'][ $i ]['min'] ?>" style="width:97%"/>
					</td>
					<td align="center">
						<input name="frequencyMax[<?= $i ?>]" type="text" id="frequencyMax[<?= $i ?>]" value="<?= $groups['frequency'][ $i ]['max'] ?>" style="width:97%"/>
					</td>
					<td align="center">
						<input name="monetaryMin[<?= $i ?>]" type="text" id="monetaryMin[<?= $i ?>]" value="<?= $groups['monetary'][ $i ]['min'] ?>" style="width:97%"/>
					</td>
					<td align="center">
						<input name="monetaryMax[<?= $i ?>]" type="text" id="monetaryMax[<?= $i ?>]" value="<?= $groups['monetary'][ $i ]['max'] ?>" style="width:97%"/>
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
			<A href="javascript:void(0)" onClick="$('#iform').submit()" class="button"><SPAN>Сохранить</SPAN></A>&nbsp;
			<A href="javascript:void(0)" onClick="DClose()" class="button"><SPAN>Отмена</SPAN></A>
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
						$('#message').fadeTo(1, 1).css('display', 'block').append('<div id=loader><img src=images/loader.gif> Загрузка данных. Пожалуйста подождите...</div>');
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
if ( $action == 'save' ) {

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

	$groups = json_encode_cyr( $groups );

	$f    = '../cash/'.$fpath.'rfm.json';
	$file = fopen( $f, "w" );

	if ( !$file )
		$rez = 'Не могу открыть файл';
	else {

		if ( fputs( $file, $groups ) === false ) {
			$rez = 'Ошибка записи';
		}
		else $rez = 'Записано';

		fclose( $file );

	}

	print '{"rez":"'.$rez.'"}';

	exit();

}

//проходим клиентов
$query = "
SELECT
	speca.spid as spid,
	speca.title as title,
	speca.prid as prid,
	speca.price as price,
	speca.price_in as zakup,
	speca.kol as kol,
	speca.did as did,
	deal.{$datum} as datum,
	deal.close as close,
	deal.iduser as iduser
FROM {$sqlname}speca `speca`
	LEFT JOIN {$sqlname}dogovor `deal` ON speca.did = deal.did
	LEFT JOIN {$sqlname}price `price` ON speca.prid = price.n_id
	LEFT JOIN {$sqlname}user ON deal.iduser = {$sqlname}user.iduser
WHERE
	speca.spid > 0 and
	speca.prid > 0
	$sort
	and (deal.{$datum} BETWEEN '$da1 00:00:01' and '$da2 23:59:59')
	and deal.kol_fact > 0
	and speca.identity = '$identity'
ORDER BY deal.{$datum}
";

$summa_all = 0; //объем продаж

//формируем массивы по каждому клиенту
$result = $db -> query( $query );
while ($data = $db -> fetch( $result )) {

	$da[ $data['prid'] ][] = [
		"datum"    => $data['datum'],
		"title"    => $data['title'],
		"day"      => round( abs( diffDate2( $data['datum'] ) ), 0 ),
		"price"    => $data['price'],
		"price_in" => $data['price_in'],
		"kol"      => pre_format( $data['kol'] ),
		"summa"    => pre_format( $data['kol'] ) * $data['price']
	];

	$summa_all = $summa_all + pre_format( $data['kol'] ) * $data['price'];

}

//формируем массив показателей для каждого клиента
$nda = [];
foreach ( $da as $key => $value ) {

	$numbers = array_map( static function($details) {
		return $details['summa'];
	}, $value );
	$sum     = array_sum( $numbers );

	$numbers = array_map( static function($details) {
		return $details['day'];
	}, $value );
	$day     = min( $numbers );

	$numbers = array_map( static function($details) {
		return $details['kol'];
	}, $value );
	$kol     = array_sum( $numbers );

	$segment = ($sum / $summa_all) * 100;

	//$segmentTotal_pre = $segmentTotal; //предыдущее значение

	$nda[] = [
		"prid"         => $key,
		"recency"      => $day,
		"frequency"    => $kol,
		"summa"        => $sum,
		"title"        => $value[0]['title'],
		"segment"      => $segment,
		"segmentTotal" => $segmentTotal,
		"group"        => $group
	];

}

//print_r($nda);

//сортируем массив по убыванию
function cmp($a, $b): bool {
	return $b['summa'] > $a['summa'];
}

usort( $nda, 'cmp' );

//инициируем массив групп
$gcount       = 0;
$segmentTotal = 0;
$da           = [];

//обрабатываем массив с целью разбивки на группы A, B, C
foreach ($nda as $i => $item) {

	$segmentTotal += $item['segment'];

	if ( $gcount == 0 ) {
		if ( $segmentTotal <= 80 || $i == 0 ) {
			$group = "A";
		}
		else {
			$gcount = 1;
			$group  = "B";
		}
	}
	elseif ( $gcount == 1 ) {
		if ( $segmentTotal <= 95 ) {
			$group = "B";
		}
		else {
			$gcount = 2;
			$group  = "C";
		}
	}
	else {
		$gcount = 2;
		$group  = "C";
	}

	$da['count'][ $group ] += 1;
	$da['summa'][ $group ] += $item['summa'];
	$da['kol'][ $group ]   += $item['frequency'];

	if ( $item['frequency'] > pre_format( $da['max'][ $group ] ) ) {
		$da['max'][$group] = $item['frequency'];
	}
	if ( $item['summa'] > pre_format( $da['maxS'][ $group ] ) ) {
		$da['maxS'][$group] = $item['summa'];
	}

	$nda[ $i ]['segmentTotal'] = $segmentTotal;
	$nda[ $i ]['group']        = $group;

}

//print_r($nda);

$chartData = '
	{
		value: '.(array_sum( (array)$da['summa'] ) > 0 ? round( $da['summa']['A'] / array_sum( (array)$da['summa'] ) * 100, 2 ) : 0).',
		color: "rgba(241,196,15,0.9)",
		highlight: "rgba(241,196,15,0.5)",
		label: "Группа A"
	},
	{
		value: '.(array_sum( (array)$da['summa'] ) > 0 ? round( $da['summa']['B'] / array_sum( (array)$da['summa'] ) * 100, 2 ) : 0).',
		color: "rgba(157,198,216,0.9)",
		highlight: "rgba(157,198,216,0.5)",
		label: "Группа B"
	},
	{
		value: '.(array_sum( (array)$da['summa'] ) > 0 ? round( $da['summa']['C'] / array_sum( (array)$da['summa'] ) * 100, 2 ) : 0).',
		color: "rgba(127,140,141,0.9)",
		highlight: "rgba(127,140,141,0.5)",
		label: "Группа C"
	}
';

file_put_contents($rootpath."/cash/abc.json", json_encode_cyr($nda));

if ( $action == "get_csv" ) {

	$otchet[] = [
		"#",
		"Продукт",
		"Давность",
		"Кол-во",
		"Сумма",
		"Доля",
		"Группа"
	];

	foreach ($nda as $i => $item) {

		$j = $i + 1;

		$otchet[] = [
			$j,
			$item['title'],
			$item['recency'],
			$item['frequency'],
			$item['summa'],
			$item['segment'],
			$item['group']
		];

	}

	Shuchkin\SimpleXLSXGen ::fromArray($otchet) -> downloadAs('export.abc-product.xlsx');

	exit();
}
?>
<style>
	<!--
	.colorA1 {
		background : rgba(241, 196, 15, 0.5);
		color      : #222;
	}

	.colorA2 {
		background : rgba(241, 196, 15, 0.3);
		color      : #222;
	}

	.colorB1 {
		background : rgba(157, 198, 216, 0.5);
		color      : #222;
	}

	.colorB2 {
		background : rgba(157, 198, 216, 0.3);
		color      : #222;
	}

	.colorC1 {
		background : rgba(127, 140, 141, 0.3);
		color      : #222;
	}

	.colorC2 {
		background : rgba(127, 140, 141, 0.1);
		color      : #222;
	}

	.itog {
		background  : rgba(46, 204, 113, 0.5);
		font-weight : 700;
	}

	/*.color{
		background: rgba(241,196,15,0.3);
	}*/
	thead td.color {
		background : rgba(241, 196, 15, 0.5);
	}

	.color:hover, .color:active {
		background         : #F1C40F;
		color              : #222;
		font-weight        : 700;
		font-size          : 1.4em;
		transition         : all 400ms ease;
		-webkit-transition : all 400ms ease;
		-moz-transition    : all 400ms ease;
	}

	.itog:hover, .itog:active {
		background         : rgba(46, 204, 113, 1.0);;
		color              : #FFF;
		font-weight        : 700;
		/*font-size:1.4em;*/
		transition         : all 400ms ease;
		-webkit-transition : all 400ms ease;
		-moz-transition    : all 400ms ease;
	}

	.hist:hover, .hist:active {
		background         : rgba(41, 128, 185, 1.0);
		color              : #FFF;
		font-weight        : 700;
		font-size          : 1.4em;
		transition         : all 400ms ease;
		-webkit-transition : all 400ms ease;
		-moz-transition    : all 400ms ease;
	}

	-->
</style>

<br>

<div class="zagolovok_rep pad5 paddtop10 block">
	<span class="txt-medium">ABC-анализ Продуктов за период <?= format_date_rus( $da1 ) ?>&nbsp;по&nbsp;<?= format_date_rus( $da2 ) ?>:</span>
</div>

<div class="pad10 hidden" style="position: absolute; top:15px; right: 20px">
	<span class="select">
	<select name="list" id="list" style="max-width: 250px" onchange="generate();">
		<optgroup label="Стандартные представления">
		<option value="my" <?php if ( $tar == 'my' )
			print "selected" ?>>Мои Клиенты</option>
		<option value="fav" <?php if ( $tar == 'fav' )
			print "selected" ?>>Ключевые Клиенты</option>
		<option value="otdel" <?php if ( $tar == 'otdel' )
			print "selected" ?>>Клиенты Подчиненных</option>
			<?php if ( $tipuser != "Менеджер продаж" || $userRights['alls'] ) { ?>
				<option value="all" <?php if ( $tar == 'all' )
					print "selected" ?>>Все Клиенты</option>
			<?php } ?>
			<option value="trash" <?php if ( $tar == 'trash' )
				print "selected" ?>>Холодные Клиенты</option>
		</optgroup>
		<optgroup label="Группы клиентов">
		<?php
		$result = $db -> query( "select * from {$sqlname}group WHERE identity = $identity ORDER by name" );
		while ($data = $db -> fetch( $result )) {
			if ( $data['service'] )
				$s = ' *';
			print '<option value="group:'.$data['id'].'">'.$data['name'].$s.'</option>';
		}
		?>
		</optgroup>
		<optgroup label="Настраиваемые представления">
		<?php
		$result = $db -> query( "select * from {$sqlname}search where tip='client' and (iduser='".$iduser1."' or share = 'yes') and identity = '$identity' order by sorder" );
		while ($data_array = $db -> fetch( $result )) {
			if ( $tar == "search:".$data_array['seid'] )
				$s = "selected";
			else $s = '';
			print '<option value="search:'.$data_array['seid'].'" '.$s.'>'.$data_array['title'].'</option>';
		}
		?>
		</optgroup>
	</select>
	</span>
	<span class="paddleft10 hidden">
	! Добавить выбор товарных групп (из категорий прайса)
	</span>
</div>

<hr class="paddtop10">

<div class="Bold miditxt paddtop10 block blue">Сводная таблица</div>
<div>
	<div class="pull-left" style="width:70%">
		<table cellpadding="5" cellspacing="0" width="95%" class="border" style="margin-right: 10px;">
			<thead class="header_contaner">
			<tr>
				<th align="left">Наименование</th>
				<th class="colorA1">A</th>
				<th class="colorB1">B</th>
				<th class="colorC1">C</th>
				<th width="100"></th>
			</tr>
			</thead>
			<tr height="30" class="ha txt-small">
				<td align="left">Доля в обороте, %</td>
				<td align="right" class="colorA2"><?= (array_sum( (array)$da['summa'] ) > 0 ? num_format( $da['summa']['A'] / array_sum( $da['summa'] ) * 100 ) : 0) ?></td>
				<td align="right" class="colorB2"><?= (array_sum( (array)$da['summa'] ) > 0 ? num_format( $da['summa']['B'] / array_sum( $da['summa'] ) * 100 ) : 0)?></td>
				<td align="right" class="colorC2"><?= (array_sum( (array)$da['summa'] ) > 0 ? num_format( $da['summa']['C'] / array_sum( $da['summa'] ) * 100 ) : 0) ?></td>
				<td align="right">100</td>
			</tr>
			<tr height="30" class="ha txt-small">
				<td align="left">Продуктов в группе, шт.</td>
				<td align="right" class="colorA2"><?= $da['count']['A'] ?></td>
				<td align="right" class="colorB2"><?= $da['count']['B'] ?></td>
				<td align="right" class="colorC2"><?= $da['count']['C'] ?></td>
				<td align="right"><?= array_sum( (array)$da['count'] ) ?></td>
			</tr>
			<tr height="30" class="ha txt-small">
				<td align="left">Количество продуктов в группе, шт.</td>
				<td align="right" class="colorA2"><?= $da['kol']['A'] ?></td>
				<td align="right" class="colorB2"><?= $da['kol']['B'] ?></td>
				<td align="right" class="colorC2"><?= $da['kol']['C'] ?></td>
				<td align="right"><?= array_sum( (array)$da['kol'] ) ?></td>
			</tr>
			<tr height="30" class="ha txt-small">
				<td align="left">Среднее количество продуктов, шт.</td>
				<td align="right" class="colorA2"><?= $da['count']['A'] > 0 ? num_format( $da['kol']['A'] / $da['count']['A'] ) : 0 ?></td>
				<td align="right" class="colorB2"><?= $da['count']['B'] > 0 ? num_format( $da['kol']['B'] / $da['count']['B'] ) : 0 ?></td>
				<td align="right" class="colorC2"><?= $da['count']['C'] > 0 ? num_format( $da['kol']['C'] / $da['count']['C'] ) : 0 ?></td>
				<td align="right"><?= (array_sum( (array)$da['count'] ) > 0 ? num_format( array_sum( $da['kol'] ) / array_sum( $da['count'] ) ) : 0) ?></td>
			</tr>
			<tr height="30" class="ha txt-small">
				<td align="left">Выручка от продукта, <?= $valuta ?></td>
				<td align="right" class="colorA2"><?= num_format( $da['summa']['A'] ) ?></td>
				<td align="right" class="colorB2"><?= num_format( $da['summa']['B'] ) ?></td>
				<td align="right" class="colorC2"><?= num_format( $da['summa']['C'] ) ?></td>
				<td align="right"><?= num_format( array_sum( (array)$da['summa'] ) ) ?></td>
			</tr>
			<tr height="30" class="ha txt-small">
				<td align="left">Средняя выручка на продукт, <?= $valuta ?></td>
				<td align="right" class="colorA2"><?= $da['kol']['A'] > 0 ? num_format( $da['summa']['A'] / $da['kol']['A'] ) : 0 ?></td>
				<td align="right" class="colorB2"><?= $da['kol']['B'] > 0 ? num_format( $da['summa']['B'] / $da['kol']['B'] ) : 0 ?></td>
				<td align="right" class="colorC2"><?= $da['kol']['B'] > 0 ? num_format( $da['summa']['C'] / $da['kol']['B'] ) : 0 ?></td>
				<td align="right"><?= array_sum( (array)$da['kol'] ) > 0 ? num_format( array_sum( (array)$da['summa'] ) / array_sum( (array)$da['kol'] ) ) : 0 ?></td>
			</tr>
			<tr height="30" class="ha txt-small">
				<td align="left">Макс. количество продукта в группе, шт.</td>
				<td align="right" class="colorA2"><?= $da['max']['A'] ?></td>
				<td align="right" class="colorB2"><?= $da['max']['B'] ?></td>
				<td align="right" class="colorC2"><?= $da['max']['C'] ?></td>
				<td align="right"><?= !empty($da['max']) ? max( $da['max'] ) : 0 ?></td>
			</tr>
			<tr height="30" class="ha txt-small">
				<td align="left">Макс. сумма продукта в группе, <?= $valuta ?></td>
				<td align="right" class="colorA2"><?= num_format( $da['maxS']['A'] ) ?></td>
				<td align="right" class="colorB2"><?= num_format( $da['maxS']['B'] ) ?></td>
				<td align="right" class="colorC2"><?= num_format( $da['maxS']['C'] ) ?></td>
				<td align="right"><?= !empty($da['maxS']) ? num_format( max( $da['maxS'] ) ) : 0 ?></td>
			</tr>
		</table>
		<br>
	</div>
	<div class="pull-left" style="width:30%" id="jsChart">
		<canvas id="myChart" height="250"></canvas>
	</div>
</div>

<hr class="paddtop10">

<div class="Bold miditxt paddtop10 block blue">
	Данные для анализа
	<span class="noprint paddright10 smalltxt pull-aright"><a href="javascript:void(0)" onClick="generate_csv()" title="Экспорт в Excel" class="blue"><i class="icon-file-excel"></i>Скачать CSV</a></span>
</div>

<TABLE width="99%" align="left" cellpadding="5" cellspacing="0" id="bborder">
	<thead style="border: 2px solid #000">
	<TR class="header_contaner" height="35">
		<th width="20" align="center"><b>#</b></th>
		<th width="30" align="center"></th>
		<th class="yw400" align="left"><b>Наименование</b></th>
		<th width="100" align="right"><b>Давность, дн.</b></th>
		<th width="100" align="right"><b>Кол-во</b></th>
		<th width="130" align="right"><b>Сумма,&nbsp;<?= $valuta ?></b></th>
		<th width="100" align="right"><b>Доля,&nbsp;%</b></th>
		<th width="100" align="right"><b>Суммарная<br>доля,&nbsp;%</b></th>
		<th width="100" align="center"><b>Группа</b></th>
	</TR>
	</thead>
	<?php
	$colors = [
		"A" => "color5",
		"B" => "color4",
		"C" => "color3"
	];

	$sum     = 0;
	$segment = 0;
	$kolsum  = 0;

	for ( $i = 0; $i < count( $nda ); $i++ ) {

		$sum     = $sum + $nda[ $i ]['summa'];
		$segment = $segment + $nda[ $i ]['segment'];
		$kolsum  = $kolsum + $nda[ $i ]['frequency'];

		?>
		<TR height="30" class="ha color<?= $nda[ $i ]['group'] ?>2">
			<TD align="right"><?= $i + 1 ?>.</TD>
			<TD align="center"><span class="icon-2x"><i class="icon-archive broun"></i></span></TD>
			<TD>
				<span class="ellipsis hand" onclick="editPrice('<?= $nda[ $i ]['prid'] ?>','view');" title="Просмотр"><?= $nda[ $i ]['title'] ?></span>
			</TD>
			<TD align="right" class="color hand more" data-prid="<?= $nda[ $i ]['prid'] ?>" data-title="<?= $nda[ $i ]['title'] ?>" title="Детали"><?= $nda[ $i ]['recency'] ?></TD>
			<TD align="right" class="color hand more" data-prid="<?= $nda[ $i ]['prid'] ?>" data-title="<?= $nda[ $i ]['title'] ?>" title="Детали"><?= num_format( $nda[ $i ]['frequency'] ) ?></TD>
			<TD align="right" class="color hand more" data-prid="<?= $nda[ $i ]['prid'] ?>" data-title="<?= $nda[ $i ]['title'] ?>" title="Детали"><?= num_format( $nda[ $i ]['summa'] ) ?></TD>
			<TD align="right" class="color"><?= num_format( $nda[ $i ]['segment'] ) ?></TD>
			<TD align="right" class="color"><?= num_format( $segment ) ?></TD>
			<TD align="center" class="itogg color<?= $nda[ $i ]['group'] ?>1"><?= $nda[ $i ]['group'] ?></TD>
		</TR>
	<?php } ?>
	<TR class="itog" height="35" style="border-top:2px solid #222">
		<td></td>
		<td></td>
		<td align="right"></td>
		<td align="right"><b>Итог:</b></td>
		<td align="right"><b><?= $kolsum ?></b></td>
		<td align="right"><b><?= num_format( $sum ) ?></b></td>
		<td align="right"><b><?= num_format( $segment ) ?></b></td>
		<td></td>
		<td></td>
	</TR>
</TABLE>

<div id="detail" class="paddtop10 paddbott10"></div>
<hr>

<div class="infodiv">
	<div class="pad5 Bold toggler hand">Расшифровка показателей <i class="icon-angle-down"></i></div>
	<div id="detaile" class="hidden">
		<hr>

		<p>Отчет поддерживает дополнительные фильтры параметров сделок. Анализируются строго закрытые за указанный период сделки.</p>

		<hr>

		<p>Учитывает время в днях, прошедшее с даты последней закрытой сделки. Рассчитывается как разность между текущей и датой последнего заказа. Клиенты, которые недавно совершали у вас покупки, более предрасположены к повторным заказам, чем те, кто давно уже не проявлял никаких действий. Клиентов, которые покупали давно, можно вернуть только предложениями, которые привлекают вернуться обратно.</p>

	</div>
</div>

<div style="height: 70px;"></div>

<script src="/assets/js/chartjs/Chart.js"></script>
<script>
	$(document).ready(function () {
		$('#myChart').width($('#jsChart').width() - 30);
		drawChart();
	});

	$('.more').click(function () {

		var prid = $(this).data('prid');
		var title = $(this).data('title');
		var str = $('#selectreport').serialize();
		var url = './reports/<?=$thisfile?>?action=view&prid=' + prid + '&title=' + title + '&' + str;

		$('#detail').removeClass('hidden').empty().append('<div id="loader" class="loader"><img src=/assets/images/loading.gif> Вычисление...</div>');

		$.get(url, function (data) {
			$('#detail').html(data);
		})
			.complete(function () {

				var wcoffset = $('#dataview').offset();
				$(".nano").nanoScroller({scrollTop: wcoffset.top});

			});

	});

	$('.toggler').click(function () {

		$('#detaile').toggleClass('hidden');

		var wcoffset = $('.toggler').offset();
		$(".nano").nanoScroller({scrollTop: wcoffset.top});

	});

	function drawChart() {

		var data = [<?=$chartData?>];
		var ctx = document.getElementById("myChart").getContext("2d");
		var myDoughnutChart = new Chart(ctx).Doughnut(data,
			{
				segmentShowStroke: true,//Boolean - Whether we should show a stroke on each segment
				segmentStrokeColor: "#FFF",//String - The colour of each segment stroke
				segmentStrokeWidth: 2,//Number - The width of each segment stroke
				percentageInnerCutout: 50, // This is 0 for Pie charts //Number - The percentage of the chart that we cut out of the middle
				animationSteps: 100,//Number - Amount of animation steps
				animationEasing: "easeOutBounce",//String - Animation easing effect
				animateRotate: true,//Boolean - Whether we animate the rotation of the Doughnut
				animateScale: false,//Boolean - Whether we animate scaling the Doughnut from the centre
				responsive: true,
				tooltipTemplate: "<%if (label){%><%=label%>: <%}%><%= value %>%"
			}
		);

	}
</script>