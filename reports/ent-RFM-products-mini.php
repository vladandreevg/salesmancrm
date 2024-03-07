<?php
/**
 * @license  http://isaler.ru/
 * @author   Vladislav Andreev, http://iandreyev.ru/
 * @charset  UTF-8
 * @version  6.4
 */
error_reporting( E_ERROR );
ini_set( 'display_errors', 1 );
header( "Pragma: no-cache" );

$rootpath = realpath( __DIR__.'/../' );

include $rootpath."/inc/config.php";
include $rootpath."/inc/dbconnector.php";
include $rootpath."/inc/auth.php";
include $rootpath."/inc/func.php";
include $rootpath."/inc/settings.php";
include $rootpath."/inc/language/".$language.".php";

$action    = $_REQUEST[ 'action' ];
$da1       = $_REQUEST[ 'da1' ];
$da2       = $_REQUEST[ 'da2' ];

$user_list   = (array)$_REQUEST[ 'user_list' ];
$fields      = (array)$_REQUEST[ 'field' ];
$field_query = (array)$_REQUEST[ 'field_query' ];

$sort        = '';
$sortt       = '';

$thisfile = basename( $_SERVER[ 'PHP_SELF' ] );

//формирование групп по-умолчанию
if ( file_exists( '../cash/'.$fpath.'rfmp.json' ) ) {
	$groups = json_decode( file_get_contents( '../cash/'.$fpath.'rfmp.json' ), true );
}
else $groups = [
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

$user_list = (!empty( $user_list )) ? $user_list : (array)get_people( $iduser1, "yes" );
if (!empty($user_list)) {
	$sort .= " AND {$sqlname}dogovor.iduser IN (".yimplode( ",", $user_list ).")";
}

//составляем запрос по параметрам сделок
if ( !in_array( "close", $fields ) ) {
	$sort  .= " and {$sqlname}dogovor.close = 'yes'";//если в доп.параметрах нет статуса, то считаем только закрытые
	$datum = "datum_close";
	$kol   = "kol_fact";
}
else {
	
	$index = array_search( 'close', $fields );

	$sort .= " and {$sqlname}dogovor.close = '".$field_query[ $index ]."'";
	if ( $field_query[ $index ] == 'yes' ) {
		$datum = "datum_close";
		$kol   = "kol_fact";
	}
	else {
		$datum = "datum_plan";
		$kol   = "kol";
	}
}

$ar = [
	'close',
	'sid'
];
foreach ( $fields as $i => $field ) {

	if ( !in_array( $field, $ar ) && !in_array( $field, [
			'close',
			'mcid'
		] ) ) {
		$sort .= " {$sqlname}dogovor.".$field." = '".$field_query[ $i ]."' AND ";
	}
	elseif ( $field == 'close' ) {
		$sort .= $field_query[ $i ] != 'yes' ? " COALESCE({$sqlname}dogovor.{$field}, 'no') != 'yes' AND " : " COALESCE({$sqlname}dogovor.{$field}, 'no') == 'yes' AND ";
	}
	elseif ( $field == 'mcid' ) {
		$mc = $field_query[ $i ];
	}

}

if ( $action == "view" ) {

	$prid = $_REQUEST[ 'prid' ];

	$query  = "
	SELECT
		{$sqlname}speca.spid as spid,
		{$sqlname}speca.title as title,
		{$sqlname}speca.prid as prid,
		{$sqlname}speca.price as price,
		{$sqlname}speca.price_in as zakup,
		{$sqlname}speca.kol as kol,
		{$sqlname}speca.did as did,
		{$sqlname}dogovor.".$datum." as datum,
		{$sqlname}dogovor.close as close,
		{$sqlname}dogovor.iduser as iduser
	FROM {$sqlname}speca
		LEFT JOIN {$sqlname}dogovor ON {$sqlname}speca.did = {$sqlname}dogovor.did
		LEFT JOIN {$sqlname}price ON {$sqlname}speca.prid = {$sqlname}price.n_id
		LEFT JOIN {$sqlname}user ON {$sqlname}dogovor.iduser = {$sqlname}user.iduser
	WHERE
		{$sqlname}speca.spid > 0
		$sort
		$sortt
		and {$sqlname}speca.prid = '$prid'
		and ({$sqlname}dogovor.".$datum." BETWEEN '".$da1." 00:00:01' and '".$da2." 23:59:59')
		and {$sqlname}speca.identity = '$identity'
	GROUP BY {$sqlname}dogovor.did
	ORDER BY {$sqlname}dogovor.".$datum."
	";
	$result = $db -> query( $query );

	$product = $db -> getOne( "SELECT title FROM {$sqlname}price WHERE n_id = '$prid' and identity = '$identity'" );

	?>
	<hr>
	<div class="success" style="background: #FFF;">
		<div class="zagolovok_rep green">Данные по запросу</div>
		<?php if ( $datum == 'datum_close' ) { ?>
			<div class="margtop10">Закрытые сделки с продажей продукта "<?= $product ?>" за период <?= format_date_rus( $da1 ) ?>&nbsp;по&nbsp;<?= format_date_rus( $da2 ) ?></div>
		<?php } ?>
		<?php if ( $datum == 'datum_plan' ) { ?>
			<div class="margtop10">Активные сделки с плановой датой продажи продукта "<?= $product ?>" в период <?= format_date_rus( $da1 ) ?>&nbsp;по&nbsp;<?= format_date_rus( $da2 ) ?></div>
		<?php } ?>
		<hr>
		<table width="100%" border="0" cellspacing="0" cellpadding="5" id="border" style="background: #FFF;">
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
			while ( $data = $db -> fetch( $result ) ) {
				$i++;
				$data[ 'price_all' ] = $data[ 'price' ] * $data[ 'kol' ];

				$skol  = $skol + $data[ 'kol' ];
				$summa = $summa + $data[ 'price_all' ];
				?>
				<tr class="ha" height="35">
					<td align="center"><?= $i ?></td>
					<td align="center" class="smalltxt"><?= format_date_rus( $data[ 'datum' ] ) ?></td>
					<td align="center" class="smalltxt"><?= round( diffDate2( $data[ 'datum' ] ), 0 ) ?></td>
					<td>
						<span class="ellipsis" title="<?= $data[ 'title' ] ?>"><a href="javascript:void(0)" onclick="openDogovor('<?= $data[ 'did' ] ?>','7')" title="Карточка"><i class="icon-briefcase broun"></i>&nbsp;<?= current_dogovor( $data[ 'did' ] ) ?></a></span>
					</td>
					<td align="right">
						<span title="<?= num_format( $data[ 'kol' ] ) ?>"><?= num_format( $data[ 'kol' ] ) ?></span>
					</td>
					<td align="right">
						<span title="<?= num_format( $data[ 'price' ] ) ?>"><?= num_format( $data[ 'price' ] ) ?> <?= $valuta ?></span>
					</td>
					<td align="right">
						<span title="<?= num_format( $data[ 'price_all' ] ) ?>"><?= num_format( $data[ 'price_all' ] ) ?> <?= $valuta ?></span>
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
					<span title="<?= num_format( $skol ) ?>"><strong><?= num_format( $skol ) ?></strong></span></td>
				<td align="right"></td>
				<td align="right"><span title="<?= num_format( $summa ) ?>"><strong><?= num_format( $summa ) ?></strong></span>
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
						<input name="recencyMin[<?= $i ?>]" type="text" id="recencyMin[<?= $i ?>]" value="<?= $groups[ 'recency' ][ $i ][ 'min' ] ?>" style="width:97%"/>
					</td>
					<td align="center">
						<input name="recencyMax[<?= $i ?>]" type="text" id="recencyMax[<?= $i ?>]" value="<?= $groups[ 'recency' ][ $i ][ 'max' ] ?>" style="width:97%"/>
					</td>
					<td align="center">
						<input name="frequencyMin[<?= $i ?>]" type="text" id="frequencyMin[<?= $i ?>]" value="<?= $groups[ 'frequency' ][ $i ][ 'min' ] ?>" style="width:97%"/>
					</td>
					<td align="center">
						<input name="frequencyMax[<?= $i ?>]" type="text" id="frequencyMax[<?= $i ?>]" value="<?= $groups[ 'frequency' ][ $i ][ 'max' ] ?>" style="width:97%"/>
					</td>
					<td align="center">
						<input name="monetaryMin[<?= $i ?>]" type="text" id="monetaryMin[<?= $i ?>]" value="<?= $groups[ 'monetary' ][ $i ][ 'min' ] ?>" style="width:97%"/>
					</td>
					<td align="center">
						<input name="monetaryMax[<?= $i ?>]" type="text" id="monetaryMax[<?= $i ?>]" value="<?= $groups[ 'monetary' ][ $i ][ 'max' ] ?>" style="width:97%"/>
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
if ( $action == 'save' ) {

	$recencyMin   = $_REQUEST[ 'recencyMin' ];
	$recencyMax   = $_REQUEST[ 'recencyMax' ];
	$frequencyMin = $_REQUEST[ 'frequencyMin' ];
	$frequencyMax = $_REQUEST[ 'frequencyMax' ];
	$monetaryMin  = $_REQUEST[ 'monetaryMin' ];
	$monetaryMax  = $_REQUEST[ 'monetaryMax' ];

	$groups = [
		"recency"   => [
			"1" => [
				"min" => $recencyMin[ 1 ],
				"max" => $recencyMax[ 1 ]
			],
			"2" => [
				"min" => $recencyMin[ 2 ],
				"max" => $recencyMax[ 2 ]
			],
			"3" => [
				"min" => $recencyMin[ 3 ],
				"max" => $recencyMax[ 3 ]
			],
			"4" => [
				"min" => $recencyMin[ 4 ],
				"max" => $recencyMax[ 4 ]
			],
			"5" => [
				"min" => $recencyMin[ 5 ],
				"max" => $recencyMax[ 5 ]
			]
		],
		"frequency" => [
			"1" => [
				"min" => $frequencyMin[ 1 ],
				"max" => $frequencyMax[ 1 ]
			],
			"2" => [
				"min" => $frequencyMin[ 2 ],
				"max" => $frequencyMax[ 2 ]
			],
			"3" => [
				"min" => $frequencyMin[ 3 ],
				"max" => $frequencyMax[ 3 ]
			],
			"4" => [
				"min" => $frequencyMin[ 4 ],
				"max" => $frequencyMax[ 4 ]
			],
			"5" => [
				"min" => $frequencyMin[ 5 ],
				"max" => $frequencyMax[ 5 ]
			]
		],
		"monetary"  => [
			"1" => [
				"min" => $monetaryMin[ 1 ],
				"max" => $monetaryMax[ 1 ]
			],
			"2" => [
				"min" => $monetaryMin[ 2 ],
				"max" => $monetaryMax[ 2 ]
			],
			"3" => [
				"min" => $monetaryMin[ 3 ],
				"max" => $monetaryMax[ 3 ]
			],
			"4" => [
				"min" => $monetaryMin[ 4 ],
				"max" => $monetaryMax[ 4 ]
			],
			"5" => [
				"min" => $monetaryMin[ 5 ],
				"max" => $monetaryMax[ 5 ]
			]
		]
	];

	$groups = json_encode_cyr( $groups );

	$f    = '../cash/'.$fpath.'rfmp.json';
	$file = fopen( $f, "w" );

	if ( !$file ) $rez = 'Не могу открыть файл';
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

function getRFMGroup( $tip, $value ) {

	$group  = 5;
	$groups = $GLOBALS[ 'groups' ];

	for ( $i = 1; $i <= 5; $i++ ) {

		if ( $groups[ $tip ][ $i ][ 'max' ] == '' ) $groups[ $tip ][ $i ][ 'max' ] = '1000000000000';

		if ( $value <= $groups[ $tip ][ $i ][ 'max' ] and $value >= $groups[ $tip ][ $i ][ 'min' ] ) $group = $i;

	}

	return $group;

}

//проходим спецификации в сделках
$query = "
SELECT
	{$sqlname}speca.spid as spid,
	{$sqlname}speca.title as title,
	{$sqlname}speca.prid as prid,
	{$sqlname}speca.price as price,
	{$sqlname}speca.price_in as zakup,
	{$sqlname}speca.kol as kol,
	{$sqlname}speca.did as did,
	{$sqlname}dogovor.".$datum." as datum,
	{$sqlname}dogovor.close as close,
	{$sqlname}dogovor.iduser as iduser
FROM {$sqlname}speca
	LEFT JOIN {$sqlname}dogovor ON {$sqlname}speca.did = {$sqlname}dogovor.did
	LEFT JOIN {$sqlname}price ON {$sqlname}speca.prid = {$sqlname}price.n_id
	LEFT JOIN {$sqlname}user ON {$sqlname}dogovor.iduser = {$sqlname}user.iduser
WHERE
	{$sqlname}speca.spid > 0 and
	{$sqlname}speca.prid > 0
	$sort
	$sortt
	and ({$sqlname}dogovor.".$datum." BETWEEN '".$da1." 00:00:01' and '".$da2." 23:59:59')
	and {$sqlname}speca.identity = '$identity'
ORDER BY {$sqlname}dogovor.".$datum."
";

//формируем массивы по каждому продукту
$result = $db -> query( $query );
while ( $data = $db -> fetch( $result ) ) {
	$da[ $data[ 'prid' ] ][] = [
		"datum"     => $data[ 'datum' ],
		"title"     => $data[ 'title' ],
		"day"       => round( abs( diffDate2( $data[ 'datum' ] ) ), 0 ),
		"price"     => $data[ 'price' ],
		"price_in"  => $data[ 'price_in' ],
		"kol"       => $data[ 'kol' ],
		"price_all" => $data[ 'kol' ] * $data[ 'price' ]
	];

}

//print_r($da);

//формируем массив показателей для каждого клиента
foreach ( $da as $key => $value ) {

	$numbers = array_map( function( $details ) {
		return $details[ 'price_all' ];
	}, $value );
	$sum     = array_sum( $numbers );

	$numbers = array_map( function( $details ) {
		return $details[ 'day' ];
	}, $value );
	$day     = min( $numbers );

	$numbers = array_map( function( $details ) {
		return $details[ 'kol' ];
	}, $value );
	$kol     = array_sum( $numbers );

	//print_r($value);

	$rfm = getRFMGroup( 'recency', $day ) + getRFMGroup( 'frequency', count( $value ) ) + getRFMGroup( 'monetary', $sum );

	$nda[] = [
		"prid"           => $key,
		"title"          => $value[ 0 ][ 'title' ],
		"recency"        => $day,
		"recencyGroup"   => getRFMGroup( 'recency', $day ),
		"frequency"      => $kol,
		"frequencyGroup" => getRFMGroup( 'frequency', count( $value ) ),
		"monetary"       => $sum,
		"monetaryGroup"  => getRFMGroup( 'monetary', $sum ),
		"rfm"            => $rfm
	];
}

//print_r($nda);

function cmp( $a, $b ) { return $b[ 'rfm' ] < $a[ 'rfm' ]; }

usort( $nda, 'cmp' );

if ( $action == "get_csv" ) {

	$otchet = ["#;Клиент;Куратор;F (дней);R (кол-во);M (сумма);F;R;M;FRM"];

	for ( $i = 0; $i < count( $nda ); $i++ ) {

		$j = $i + 1;

		$otchet[] = $j.'.;"'.$nda[ $i ][ 'client' ].'";"'.$nda[ $i ][ 'user' ].'";"'.$nda[ $i ][ 'recency' ].'";'.$nda[ $i ][ 'frequency' ].';'.$nda[ $i ][ 'monetary' ].';'.$nda[ $i ][ 'recencyGroup' ].';'.$nda[ $i ][ 'frequencyGroup' ].';'.$nda[ $i ][ 'monetaryGroup' ].';'.$nda[ $i ][ 'rfm' ];

	}

	//создаем файл csv
	$filename = 'export_rfmreport.csv';
	$handle   = fopen( "../files/".$filename, 'w' );
	for ( $g = 0; $g < count( $otchet ); $g++ ) {
		$otchet[ $g ] = iconv( "UTF-8", "CP1251", str_replace( "<br>", "\t", $otchet[ $g ] ) );
		fwrite( $handle, "$otchet[$g]\n" );
	}
	fclose( $handle );
	header( 'Content-type: application/csv' );
	header( 'Content-Disposition: attachment; filename="'.$filename.'"' );

	readfile( "../files/".$filename );
	unlink( "../files/".$filename );
	exit();
}
?>
<style>
	<!--
	.color1 {
		background : rgba(41, 128, 185, 0.1);
		color      : #222;
	}
	.color2 {
		background : rgba(41, 128, 185, 0.4);
		color      : #222;
	}
	.color3 {
		background : rgba(41, 128, 185, 0.6);
		color      : #222;
	}
	.color4 {
		background : rgba(41, 128, 185, 0.8);
		color      : #FFF;
	}
	.color5 {
		background : rgba(41, 128, 185, 1.0);
		color      : #FFF;
	}
	.itog {
		background : rgba(46, 204, 113, 0.3);
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
		font-size          : 1.4em;
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
<div class="zagolovok_rep pad5 paddtop10 block">
	<span class="txt-medium">RFM-анализ "Продажи - Продукты" за период <?= format_date_rus( $da1 ) ?>&nbsp;по&nbsp;<?= format_date_rus( $da2 ) ?>:</span>
	<span class="noprint paddright10"><a href="javascript:void(0)" onClick="generate_csv()" title="Экспорт в Excel" class="blue"><i class="icon-file-excel"></i>Скачать CSV</a></span>
</div>

<hr class="paddtop10">

<TABLE width="99%" align="center" cellpadding="5" cellspacing="0" id="bborder">
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
		<td rowspan="2" width="100" align="center" class="itog"><b>RFM</b></td>
	</TR>
	<TR class="header_contaner" height="28">
		<td width="100" align="center">Дней</td>
		<td width="100" align="center">Кол-во</td>
		<td width="100" align="center">Сумма</td>
		<td colspan="3" width="100" align="center" class="color"><b>Группа</b></td>
	</TR>
	</thead>
	<?php
	$colors = [
		"1" => "color5",
		"2" => "color4",
		"3" => "color3",
		"4" => "color2",
		"5" => "color1"
	];
	for ( $i = 0; $i < count( $nda ); $i++ ) {
		?>
		<TR class="ha" height="30">
			<TD align="right"><?= $i + 1 ?>.</TD>
			<TD align="center" onclick="openClient('<?= $nda[ $i ][ 'prid' ] ?>')" title="Открыть карточку">
				<span class="icon-2x"><i class="icon-archive broun hand"></i></span></TD>
			<TD>
				<span class="ellipsis hand" onclick="editPrice('<?= $nda[ $i ][ 'prid' ] ?>','view');" title="Просмотр"><?= $nda[ $i ][ 'title' ] ?></span><br>
			</TD>
			<TD align="right" class="color hand more" data-prid="<?= $nda[ $i ][ 'prid' ] ?>" data-tip="recency" title="Детали"><?= $nda[ $i ][ 'recency' ] ?></TD>
			<TD align="right" class="color hand more" data-prid="<?= $nda[ $i ][ 'prid' ] ?>" data-tip="frequency" title="Детали"><?= $nda[ $i ][ 'frequency' ] ?></TD>
			<TD align="right" class="color hand more" data-prid="<?= $nda[ $i ][ 'prid' ] ?>" data-tip="monetary" title="Детали"><?= num_format( $nda[ $i ][ 'monetary' ] ) ?></TD>
			<TD align="center" class="color <?= strtr( $nda[ $i ][ 'recencyGroup' ], $colors ) ?>"><?= $nda[ $i ][ 'recencyGroup' ] ?></TD>
			<TD align="center" class="color <?= strtr( $nda[ $i ][ 'frequencyGroup' ], $colors ) ?>"><?= $nda[ $i ][ 'frequencyGroup' ] ?></TD>
			<TD align="center" class="color <?= strtr( $nda[ $i ][ 'monetaryGroup' ], $colors ) ?>"><?= $nda[ $i ][ 'monetaryGroup' ] ?></TD>
			<TD align="center" class="itog"><?= $nda[ $i ][ 'rfm' ] ?></TD>
		</TR>
	<?php } ?>
</TABLE>
<div id="detail" class="paddtop10 paddbott10"></div>
<hr>
<div class="infodiv">
	<div class="pad5 Bold">Расшифровка показателей <i class="icon-angle-down toggler hand"></i></div>
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
				for ( $i = 1; $i <= 5; $i++ ) {
					?>
					<tr height="30" class="ha">
						<td align="center"><?= $i ?></td>
						<td align="center"><?= $groups[ 'recency' ][ $i ][ 'min' ] ?> &divide; <?= $groups[ 'recency' ][ $i ][ 'max' ] ?></td>
						<td align="center"><?= $groups[ 'frequency' ][ $i ][ 'min' ] ?> &divide; <?= $groups[ 'frequency' ][ $i ][ 'max' ] ?></td>
						<td align="center"><?= $groups[ 'monetary' ][ $i ][ 'min' ] ?> &divide; <?= $groups[ 'monetary' ][ $i ][ 'max' ] ?></td>
					</tr>
					<?php
				}
				?>
			</table>
			<?php
			if ( $tipuser == 'Руководитель организации' or $isadmin == 'on' ) {
				?>
				<hr>
				<span id="greenbutton" class="noprint"><a href="javascript:void(0)" onclick="doLoad('reports/<?= $thisfile ?>?action=edit')" class="button"><i class="icon-pencil"></i>Изменить</a></span>
			<?php } ?>
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

<script>
	$('.more').on('click', function () {

		var prid = $(this).data('prid');
		var str = $('#selectreport').serialize();
		var url = '/reports/<?=$thisfile?>?action=view&prid=' + prid + '&' + str;

		$('#detail').empty().append('<div id="loader" class="loader"><img src=/assets/images/loading.gif> Вычисление...</div>');

		$.get(url, function (data) {
			$('#detail').html(data);
		})
			.done(function () {
				$(".nano").nanoScroller({scrollTo: $('#detail')});
			});

	});

	$('.toggler').on('click', function () {

		$(this).toggleClass('icon-angle-up,icon-angle-down');
		$('#detaile').toggleClass('hidden');
		$(".nano").nanoScroller({scrollTo: $('.toggler')});

	});

</script>