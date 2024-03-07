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

$action = $_REQUEST['action'];
$da1    = $_REQUEST['da1'];
$da2    = $_REQUEST['da2'];
$da     = $_REQUEST['da'];
$act    = $_REQUEST['act'];
$per    = $_REQUEST['per'];

if ( !$per )
	$per = 'nedelya';

$user_list    = (array)$_REQUEST['user_list'];
$clients_list = (array)$_REQUEST['clients_list'];
$persons_list = (array)$_REQUEST['persons_list'];
$fields       = (array)$_REQUEST['field'];
$field_query  = (array)$_REQUEST['field_query'];
$steps        = (array)$_REQUEST['idcategory'];

//массив пользователей
$user_list = (!empty( $user_list )) ? $user_list : (array)get_people( $iduser1, "yes" );

$sort = $clist = $plist = '';

//массив выбранных пользователей
if (!empty($user_list)) {
	$sort .= " deal.iduser IN (".yimplode( ",", $user_list ).") AND ";
}

//составляем запрос по клиентам и персонам
if ( !empty($clients_list) && !empty($persons_list) ) {
	$sort .= "(deal.clid IN (".yimplode( ",", $clients_list).") OR deal.pid IN (".yimplode( ",", $persons_list ).")) AND ";
}
elseif ( !empty($clients_list) ) {
	$sort .= "deal.clid IN (".yimplode( ",", $clients_list).") AND ";
}
elseif ( !empty($persons_list) ) {
	$sort .= "deal.pid IN (".yimplode( ",", $persons_list ).") AND ";
}

//составляем запрос по параметрам сделок
$exclude = [
	'sid',
	'close'
];
foreach ( $fields as $i => $field ) {

	if ( !in_array( $field, $exclude ) && !in_array( $field, [
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

$da = $db -> getAll( "
	SELECT 
		* 
	FROM 
		".$sqlname."dogovor `deal`
	WHERE 
		COALESCE(deal.close, 'no') != 'yes' AND 
		deal.datum_plan between '".$da1." 00:00:00' and '".$da2." 23:59:59' and 
		$sort
		deal.identity = '$identity'
	ORDER BY deal.datum_plan
" );
foreach ( $da as $data ) {

	$pid   = yexplode( ";", (string)$data[ 'pid_list' ], 0 );
	$summa = 0;

	if ( $data[ 'clid' ] > '0' ) {

		$url    = "clid='".$data[ 'clid' ]."'";
		$client = current_client( $data[ 'clid' ] );

	}
	else {

		$url    = "pid='".$data[ 'pid' ]."'";
		$client = current_person( $data[ 'pid' ] );

	}

	$datum_min = $data[ 'datum_plan' ]; //задаем начальную минимальную дату как плановую дату сделки

	if ( $complect_on == 'yes' && $tarif != 'Base' ) {

		//Сформируем здоровье сделки относительно плановой даты
		$dmin = $db -> getOne( "SELECT MIN(data_plan) as min FROM ".$sqlname."complect WHERE did = '".$data[ 'did' ]."' and doit != 'yes' and identity = '$identity'" );
		if ( $dmin != '' && diffDate2( $datum_min, $dmin ) > 0 ) $datum_min = $dmin;

	}

	//Сформируем сумму оплаченных счетов
	$resultc = $db -> query( "SELECT * FROM ".$sqlname."credit WHERE did = '".$data[ 'did' ]."' and identity = '$identity'" );
	while ( $datac = $db -> fetch( $resultc ) ) {

		if ( $datac[ 'do' ] == 'on' ) $summa += $datac[ 'summa_credit' ];

		if ( diffDate2( $datum_min, $datac[ 'datum_credit' ] ) > 0 && $datac[ 'do' ] != 'on' && $datac[ 'datum_credit' ] != '0000-00-00' ) $datum_min = $datac[ 'datum_credit' ];
		if ( diffDate2( $datum_min, $datac[ 'invoice_date' ] ) > 0 && $datac[ 'do' ] != 'on' && $datac[ 'invoice_date' ] != '0000-00-00' ) $datum_min = $datac[ 'invoice_date' ];

	}

	$day = diffDate2( $datum_min ); //round((date_to_unix($datum_min) - $nd_unix) / 86400);

	if ( $day < 0 ) {

		$color  = "red";
		$health = 0;

	}
	else if ( $day >= 0 && $day <= 7 ) {

		$color  = "blue";
		$health = 1;

	}
	else {

		$color  = 'green';
		$health = 2;

	}

	$kol = $data[ 'kol' ];

	//Здоровье сделки. конец.
	//if($health != 2)
	$dogs[] = [
		"health"     => $health,
		"diff"       => round( diffDate2( $datum_min ) ),
		"did"        => $data[ 'did' ],
		"datum"      => $data[ 'datum' ],
		"datum_plan" => $data[ 'datum_plan' ],
		"client"     => $client,
		"clid"       => $data[ 'clid' ],
		"zayavka"    => $data[ 'zayavka' ],
		"title"      => $data[ 'title' ],
		"des"        => $data[ 'content' ],
		"kol"        => $kol,
		"summa"      => $summa,
		"pid"        => $pid,
		"url"        => $url,
		"manager"    => current_user( $data[ 'iduser' ] ),
		"day"        => $day,
		"color"      => $color
	];

	$datum_min = 0;

}

function cmp( $a, $b ) { return $b[ 'day' ] < $a[ 'day' ]; }

usort( $dogs, 'cmp' );
?>
<style type="text/css">
	<!--
	@media print {
		.fixAddBotButton {
			display : none;
		}
	}

	-->
</style>

<div class="relativ mt20 mb20 wp95" align="center">
	<h1 class="uppercase fs-14 m0 mb10">Здоровье сделок</h1>
	<div class="blue">за период&nbsp;с&nbsp;<?= format_date_rus( $da1 ) ?>&nbsp;по&nbsp;<?= format_date_rus( $da2 ) ?></div>
</div>

<hr>

<TABLE width="100%" align="center" cellpadding="5" cellspacing="0" id="zebra">
	<thead>
	<TR height="40" class="header_contaner">
		<td width="70" align="center"><b>Дата план.</b></td>
		<td width="40" align="center"></td>
		<td width="60" align="center"></td>
		<td width="250" align="center"><b>Название сделки</b></td>
		<td width="100" align="center"><b>Стоимость, <?= $valuta ?></b></td>
		<td width="100" align="center"><b>Оплата, <?= $valuta ?></b></td>
		<td align="center"><b>Заказчик</b></td>
	</TR>
	</thead>
	<?php
	foreach ( $dogs as $dog ) {
		?>
		<TR class="ha" height="40">
			<TD align="center"><?= format_date_rus( $dog[ 'datum_plan' ] ) ?></TD>
			<td align="center">
				<A href="javascript:void(0)" onclick="doLoad('content/lists/dt.health.php?did=<?= $dog[ 'did' ] ?>&action=view')" title="Информация"><i class="icon-info-circled <?= $dog[ 'color' ] ?>"></i></A>
			</td>
			<td align="right">
				<div class="<?= $dog[ 'color' ] ?>"><?= $dog[ 'diff' ] ?> д.</div>
			</td>
			<TD>
				<div class="ellipsis">
					<A href="javascript:void(0)" onclick="openDogovor('<?= $dog[ 'did' ] ?>')" title="Открыть в новом окне"><i class="icon-briefcase blue"></i>
						<b><?= $dog[ 'title' ] ?></b></A>
				</div>
			</TD>
			<TD align="right"><?= num_format( $dog[ 'kol' ] ) ?></TD>
			<TD align="right"><?= num_format( $dog[ 'summa' ] ) ?></TD>
			<TD>
				<div class="ellipsis">
					<a href="javascript:void(0)" onclick="openClient('<?= $dog[ 'clid' ] ?>')" title="Открыть"><i class="icon-building broun"></i><?= $dog[ 'client' ] ?>
					</a>
				</div>
			</TD>
		</TR>
		<?php
	}
	?>
</TABLE>
<div style="height: 90px;"></div>