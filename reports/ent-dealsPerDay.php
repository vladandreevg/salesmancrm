<?php /** @noinspection ALL */
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

$action = $_REQUEST['action'];
$da1    = $_REQUEST['da1'];
$da2    = $_REQUEST['da2'];
$da     = $_REQUEST['da'];
$act    = $_REQUEST['act'];
$per    = $_REQUEST['per'];

if ( !$per )
	$per = 'nedelya';

$user_list   = (array)$_REQUEST['user_list'];
$fields      = (array)$_REQUEST['field'];
$field_query = (array)$_REQUEST['field_query'];
$period      = $_REQUEST['period'];

$period = ($period == '') ? getPeriod( 'month' ) : getPeriod( $period );

$da1 = ($da1 != '') ? $da1 : $period[0];
$da2 = ($da2 != '') ? $da2 : $period[1];

$sort   = '';
$kolSum = 0;

//массив выбранных пользователей
$sort .= (!empty( $user_list )) ? "deal.iduser IN (".yimplode( ",", $user_list ).") and " : "deal.iduser IN (".yimplode( ",", (array)get_people( $iduser1, "yes" ) ).") and ";

//составляем запрос по параметрам сделок
//составляем запрос по параметрам сделок
$ar = [
	'sid',
	'close'
];
foreach ( $fields as $i => $field ) {

	if ( !in_array( $field, $ar ) && !in_array( $field, [
			'close',
			'mcid'
		] ) ) {
		$sort .= " deal.".$field." = '".$field_query[ $i ]."' AND ";
	}
	elseif ( $field == 'close' ) {
		$sort .= $field_query[ $i ] != 'yes' ? " COALESCE(deal.{$field}, 'no') != 'yes' AND " : " COALESCE(deal.{$field}, 'no') == 'yes' AND ";
	}
	elseif ( $field == 'mcid' ) {
		$mc = $field_query[ $i ];
	}

}

$nd      = current_datum();
$nd_unix = date_to_unix( $nd );

$first_step = $db -> getOne( "SELECT idcategory FROM {$sqlname}dogcategory WHERE title = '0' and identity = '$identity'" );
$end_step   = $db -> getOne( "SELECT idcategory FROM {$sqlname}dogcategory WHERE title = '100' and identity = '$identity'" );

$i = 0;

$q = "
	SELECT 
		deal.did as did,
		deal.title as dogovor,
		deal.datum as dcreate,
		deal.datum_plan as dplan,
		deal.datum_close as dclose,
		deal.idcategory as idstep,
		deal.tip as tip,
		deal.clid as clid,
		deal.pid as pid,
		deal.kol as kol,
		deal.marga as marga,
		deal.kol_fact as kolf,
		deal.close as close,
		deal.iduser as iduser,
		us.title as user,
		cc.title as client,
		dc.title as step,
		dc.content as steptitle,
		dt.title as tips,
		ds.title as dstatus
	FROM {$sqlname}dogovor  `deal`
		LEFT JOIN {$sqlname}user `us` ON deal.iduser = us.iduser
		LEFT JOIN {$sqlname}clientcat `cc` ON deal.clid = cc.clid
		LEFT JOIN {$sqlname}dogcategory `dc` ON deal.idcategory = dc.idcategory
		LEFT JOIN {$sqlname}dogtips `dt` ON deal.tip = dt.tid
		LEFT JOIN {$sqlname}dogstatus `ds` ON deal.sid = ds.sid
	WHERE 
		deal.datum_plan BETWEEN '$da1 00:00:00' and '$da2 23:59:59' and 
		deal.idcategory != '$first_step' and 
		deal.idcategory != '$end_step' and 
		$sort
		COALESCE(deal.close, 'no') != 'yes' and 
		deal.identity = '$identity' 
	ORDER BY deal.datum_plan";

$da = $db -> getAll( $q );

foreach ( $da as $data ) {

	$dfact = '';
	$prim  = '';
	$color = '';

	$datum     = format_date_rus( $data['datum'] );//сдесь дата создания сделки
	$datum_min = $data['datum_plan']; //задаем начальную минимальную дату как плановую дату сделки

	if ( $complect_on == 'yes' && $tarif != ' Base' ) {

		$dmin = $db -> getOne( "SELECT MIN(data_plan) as min FROM {$sqlname}complect WHERE did = '".$data['did']."' and doit != 'yes' and identity = '$identity'" );
		if ( date_to_unix( $datum_min ) > date_to_unix( $dmin ) )
			$datum_min = $dmin;

	}

	//Сформируем сумму оплаченных счетов
	$resultc = $db -> query( "SELECT * FROM {$sqlname}credit WHERE did = '".$data['did']."' and identity = '$identity'" );
	while ($datac = $db -> fetch( $resultc )) {

		if ( $datac['do'] == 'on' )
			$summa[ $i ] = $summa[ $i ] + $datac['summa_credit'];
		if ( date_to_unix( $datum_min ) > date_to_unix( $datac['datum_credit'] ) && $datac['do'] != 'on' && $datac['datum_credit'] != '0000-00-00' )
			$datum_min = $datac['datum_credit'];
		if ( date_to_unix( $datum_min ) > date_to_unix( $datac['invoice_date'] ) && $datac['do'] != 'on' && $datac['invoice_date'] != '0000-00-00' )
			$datum_min = $datac['invoice_date'];

	}

	$day[ $i ] = round( ((int)date_to_unix( $datum_min ) - (int)$nd_unix) / 86400 );

	$kolSum += $data['kol'];

	//цветовая схема
	if ( $data['close'] == 'yes' && $data['kolf'] > 0 )
		$color = 'greenbg-sub';
	if ( $data['close'] == 'yes' && $data['kolf'] == 0 )
		$color = 'redbg-sub';

	//Сформируем записи активностей, последние 3
	for ( $k = 0; $k < 1; $k++ ) {

		$j = $k + 1;

		$rh    = $db -> getRow( "select * from {$sqlname}history WHERE did='".$data['did']."' and tip != 'СобытиеCRM' and (datum between '".$da1." 00:00:00' and '".$da2." 23:59:59') and identity = '$identity' ORDER BY cid DESC LIMIT ".$k.", ".$j );
		$datum = format_date_rus( cut_date_short( $rh["datum"] ) );
		$tip   = $rh["tip"];
		$hdes  = $rh["des"];

		if ( $datum != '01.01.1970' )
			$prim .= str_replace( ";", ",", '<strong>'.$datum.'</strong>: '.$tip.', '.$hdes.' <br>' );

	}

	$dogs[ $data['dplan'] ][] = [
		"did"        => $data['did'],
		"step"       => $data['step'],
		"dcreate"    => $data['dcreate'],
		"datum_plan" => $data['datum_plan'],
		"dclose"     => $data['dclose'],
		"client"     => $data['client'],
		"clid"       => $data['clid'],
		"pid"        => $data['pid'],
		"person"     => $data['person'],
		"dogovor"    => $data['dogovor'],
		"des"        => $prim,
		"kol_fact"   => $data['kolf'],
		"kol"        => $data['kol'],
		"user"       => $data['user'],
		"day"        => $day[ $i ],
		"color"      => $color,
		"close"      => $data['close'],
		"dstatus"    => $data['dstatus']
	];

	$i++;
	$datum_min = 0;
}

//print_r($dogs);
//exit();

if ( $action == "export" ) {

	$otchet[] = [
		'#',
		'Дата создан.',
		'Дата план.',
		'Этап сделки',
		'Сделка',
		'Заказчик',
		'Ответств.',
		'Описание',
		'Сумма сделки, р.',
		'URL'
	];
	$j      = 1;

	foreach ( $dogs as $key => $val ) {

		foreach ( $val as $k => $v ) {

			$otchet[] = [
				$j,
				$v['dcreate'],
				$key,
				$v['step'].'%',
				$v['dogovor'],
				$v['client'],
				$v['user'],
				preg_replace( "/\r\n|\r|\n/u", "", untag( $v['des'] ) ),
				pre_format( $v['kol'] ),
				$productInfo['crmurl'].'/card.deal?did='.$v['did']
			];

			$j++;

		}

	}

	//создаем файл csv
	$filename = 'export_doganaliz.xlsx';
	Shuchkin\SimpleXLSXGen ::fromArray( $otchet ) -> downloadAs( $filename );

	exit();
}
?>

<div class="zagolovok_rep div-center">
	<h2 class="fs-12 uppercase">Сделки в работе</h2>
	<span class="noBold">за период&nbsp;с&nbsp;<?= format_date_rus( $da1 ) ?>&nbsp;по&nbsp;<?= format_date_rus( $da2 ) ?> ( <a href="javascript:void(0)" onclick="Export()" style="color:blue">Экспорт</a> )</span>
	<div class="gray2 em fs-09 noBold mt5">Открытые сделки с этапом не равным 0% и 100%</div>
</div>

<TABLE width="100%" align="center" cellpadding="5" cellspacing="0">
	<thead>
	<TR height="35">
		<th width="20" align="center"><b>#</b></th>
		<th width="70" align="center"><b>Дата<br>создан.</b></th>
		<th width="70" align="center"><b>Этап</b></th>
		<th align="center"><b>Сделка</b> / <b>Заказчик</b></th>
		<th width="120" align="center"><b>Ответств.</b></th>
		<th width="200" align="center"><b>Активности</b></th>
		<th width="150" align="center"><b>&sum; сделки, <?= $valuta ?></b></th>
	</TR>
	</thead>
	<tbody>
	<?php
	foreach ( $dogs as $key => $val ) {

		$num = 1;

		print '
		<tr height="40" class="datetoggle greenbg-sub ha hand" data-key="'.$key.'">
			<td colspan="7"><i class="icon-plus-circled gray2"></i> Плановая дата: <span class="Bold blue">'.format_date_rus( $key ).'</span> [ Количество сделок: <b>'.count( $val ).'</b> ]</td>
		</tr>
		';

		foreach ( $val as $k => $v ) {
			?>
			<TR class="ha <?= $v['color'] ?> hidden sub" data-date="<?= $key ?>">
				<TD width="2" align="right"><?= $num ?>.</TD>
				<TD align="center"><?= format_date_rus( $v['dcreate'] ) ?></TD>
				<TD align="right"><?= $v['step'] ?>%</TD>
				<TD>
					<div class="ellipsis">
						<A href="#" onclick="openDogovor('<?= $v['did'] ?>')" title="Открыть в новом окне"><i class="icon-briefcase blue"></i> <?= $v['dogovor'] ?>
						</A></div>
					<br>
					<div class="ellipsis fs-09">
						<?php if ( $v['clid'] > 0 ) { ?>
							<A href="#" onclick="openClient('<?= $v['clid'] ?>')" class="gray"><i class="icon-building broun"></i>&nbsp;<?= $v['client'] ?>
							</A>
						<?php } else { ?>
							<A href="#" onclick="openPerson('<?= $v['pid'] ?>')" class="gray"><i class="icon-user-1 blue"></i>&nbsp;<?= $v['person'] ?>
							</A>
						<?php } ?>
					</div>
				</TD>
				<TD>
					<div class="ellipsis"><?= $v['user'] ?></div>
				</TD>
				<TD>
					<div class="ellipsis1"><?= $v['des'] ?></div>
				</TD>
				<TD align="right"><?= num_format( $v['kol'] ) ?></TD>
			</TR>
			<?php
			$num++;
		}
	}
	?>
	</tbody>
	<tfoot>
	<TR bgcolor="#FFCC33" height="28">
		<td colspan="6"></td>
		<td align="right"><strong><?= num_format( $kolSum ) ?></strong></td>
	</TR>
	</tfoot>
</TABLE>
<div style="height:150px"></div>

<DIV class="fixAddBotButton" style="left:auto; right: 50px" onclick="ToggleAll()" data-state="collapse">
	<i class="icon-plus"></i> <span>Развернуть всё</span>
</div>

<script>

	$('.datetoggle').on('click', function () {

		var key = $(this).data('key');

		$('tr.sub').not('[data-date="' + key + '"]').addClass('hidden');
		$('tr.sub[data-date="' + key + '"]').toggleClass('hidden');

		$(this).find('i').toggleClass('icon-plus-circled icon-minus-circled');

	});

	function Toggle(date) {

		$('tr.sub').addClass('hidden');
		$('tr[data-date="' + date + '"]').toggleClass('hidden show');

	}

	function ToggleAll() {

		var state = $('.fixAddBotButton').data('state');

		//console.log(state);

		if (state == 'collapse') {
			$('.fixAddBotButton').data('state', 'expand');
			$('.fixAddBotButton').find('span').html('Свернуть всё');
			$('.fixAddBotButton').find('i').removeClass('icon-plus').addClass('icon-minus');
			$('tr.sub').removeClass('hidden');
		}
		if (state == 'expand') {
			$('.fixAddBotButton').data('state', 'collapse');
			$('.fixAddBotButton').find('span').html('Развернуть всё');
			$('.fixAddBotButton').find('i').addClass('icon-plus').removeClass('icon-minus');
			$('tr.sub').addClass('hidden');
		}
	}

	function Export() {

		var str = $('#selectreport').serialize();
		window.open('reports/' + $('#report option:selected').val() + '?action=export&' + str);

	}

</script>