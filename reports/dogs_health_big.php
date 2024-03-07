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

$sort = '';

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

$query = "
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
";

$result = $db -> getAll( $query );
foreach ( $result as $data ) {

	$manpro    = current_user( $data[ 'iduser' ] );
	$pid       = yexplode( ";", (string)$data[ 'pid_list' ], 0 );
	$datum_min = '';

	if ( $data[ 'clid' ] > '0' ) {

		$url    = "clid='".$data[ 'clid' ]."'";
		$client = current_client( $data[ 'clid' ] );

	}
	else {

		$url    = "pid='".$data[ 'pid' ]."'";
		$client = current_person( $data[ 'pid' ] );

	}

	$datum_min = $data[ 'datum_plan' ]; //задаем начальную минимальную дату как плановую дату сделки

	if ( $complect_on == 'yes' and $tarif != 'Base' ) {

		$dmin = $db -> getOne( "SELECT MIN(data_plan) as min FROM ".$sqlname."complect WHERE did = '".$data[ 'did' ]."' and doit != 'yes' and identity = '$identity'" );
		if ( $dmin != '' && diffDate2( $datum_min, $dmin ) > 0 ) $datum_min = $dmin;

	}

	//Сформируем сумму оплаченных счетов
	$summa   = 0;
	$resultc = $db -> query( "SELECT * FROM ".$sqlname."credit WHERE did = '".$data[ 'did' ]."' and identity = '$identity'" );
	while ( $datac = $db -> fetch( $resultc ) ) {

		if ( $datac[ 'do' ] == 'on' ) $summa += $datac[ 'summa_credit' ];

		if ( diffDate2( $datum_min, $datac[ 'datum_credit' ] ) > 0 && $datac[ 'do' ] != 'on' && $datac[ 'datum_credit' ] != '0000-00-00' ) $datum_min = $datac[ 'datum_credit' ];
		if ( diffDate2( $datum_min, $datac[ 'invoice_date' ] ) > 0 && $datac[ 'do' ] != 'on' && $datac[ 'invoice_date' ] != '0000-00-00' ) $datum_min = $datac[ 'invoice_date' ];

	}

	$day = diffDate2( $datum_min );//round((date_to_unix($datum_min) - $nd_unix) / 86400, 0);

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
	$dogs[] = [
		"health"     => $health,
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
		"manager"    => $manager,
		"day"        => $day,
		"color"      => $color
	];

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
	<h1 class="uppercase fs-14 m0 mb10">Здоровье сделок [ расширенный ]</h1>
	<div class="blue">за период&nbsp;с&nbsp;<?= format_date_rus( $da1 ) ?>&nbsp;по&nbsp;<?= format_date_rus( $da2 ) ?></div>
</div>

<hr>

<div style="width: 98%; overflow-x: auto;">

	<div class="data" style="max-height: 70vh;">

		<TABLE id="zebra">
			<thead>
			<TR height="40" class="header_contaner">
				<th width="40"></th>
				<th width="300" align="center"><b>Название сделки</b>/<b>Заказчик</b></th>
				<th width="120" align="center"><b>Стоимость, <?= $valuta ?></b></th>
				<th width="120" align="center"><b>Оплата, <?= $valuta ?></b></th>
				<th width="120" align="center"><b>Дата план.</b></th>
				<?php
				//выводим комплектность сделки
				if ( $complect_on == 'yes' and $tarif != 'Base' ) {

					$rh = $db -> getCol( "SELECT title FROM ".$sqlname."complect_cat WHERE identity = '$identity' ORDER BY title" );
					foreach ( $rh as $cpoint ) {
						?>
						<th width="150" align="left" title="<?= $cpoint ?>">
							<div class="ellipsis"><b><?= $cpoint ?></b></div>
						</th>
						<?php
					}

				}
				//конец комплектность сделки
				?>
			</TR>
			</thead>
			<tbody>
			<?php
			foreach ( $dogs as $dog ) {

				$d = diffDate2( $dog[ 'datum_plan' ] );

				if ( $d == 0 ) $p = '<i class="icon-info-circled broun" title="Не выполнено. Срок - сегодня"></i>';
				if ( $d > 0 ) $p = '<i class="icon-info-circled blue" title="Не выполнено. Срок - в порядке"></i>';
				if ( $d < 0 ) $p = '<i class="icon-attention red" title="Не выполнено. Просрочено"></i>';
				?>
				<TR height="40px" class="ha border">
					<td align="center" class="bgwhite">

						<A href="javascript:void(0);" onclick="doLoad('content/lists/dt.health.php?did=<?= $dog[ 'did' ] ?>&action=view')" title="Информация"><i class="icon-info-circled <?= $dog[ 'color' ] ?>"></i></A>

					</td>
					<TD title="Название сделки" class="bgwhite">
						<div class="ellipsis fs-11">
							<a href="javascript:void(0);" onclick="openDogovor('<?= $dog[ 'did' ] ?>')" title="Открыть в новом окне"><i class="icon-briefcase blue"></i><b><?= $dog[ 'title' ] ?></b></a>
						</div>
						<br>
						<div class="ellipsis gray smalltxt"><?= $dog[ 'client' ] ?></div>
					</TD>
					<TD align="right" title="Сумма договора: <?= num_format( $dog[ 'kol' ] ) ?>"><?= num_format( $dog[ 'kol' ] ) ?></TD>
					<TD align="right" title="Оплаченная сумма: <?= num_format( $dog[ 'summa' ] ) ?>"><?= num_format( $dog[ 'summa' ] ) ?></TD>
					<TD align="left" title="Дата закрытия: <?= format_date_rus( $dog[ 'datum_plan' ] ) ?>">
						<div class="ellipsis1"><?= $p ?> <?= format_date_rus( $dog[ 'datum_plan' ] ) ?></div>
					</TD>
					<?php
					//выводим комплектность сделки
					if ( $complect_on == 'yes' and $tarif != 'Base' ) {

						$resulthh = $db -> query( "SELECT * FROM ".$sqlname."complect_cat WHERE identity = '$identity' ORDER BY title" );
						while ( $datahh = $db -> fetch( $resulthh ) ) {

							$resulth   = $db -> getRow( "SELECT * FROM ".$sqlname."complect WHERE did = '".$dog[ 'did' ]."' and ccid='".$datahh[ 'ccid' ]."' and identity = '$identity'" );
							$doit      = $resulth[ "doit" ];
							$data_plan = $resulth[ "data_plan" ];
							$data_fact = $resulth[ "data_fact" ];

							$f = diffDate2( $data_plan );

							if ( $doit == 'yes' ) $doitt = '<i class="icon-ok blue" title="Выполнено"></i>';
							else {

								if ( $f > 0 ) $doitt = '<i class="icon-info-circled blue" title="Не выполнено. Срок - в порядке"></i>';
								if ( $f < 0 ) $doitt = '<i class="icon-attention red" title="Не выполнено. Просрочено"></i>';
								//if($doit!='yes' and date_to_unix($data_plan)>=$nd_unix and format_date_rus($data_plan)!='..') $doit = '<i class="icon-ok green" title="Просрочено"></i>';

							}

							if ( format_date_rus( $data_plan ) != '..' ) {
								$d = format_date_rus( $data_plan );
							}
							else $doitt = '';

							if ( $data_plan == '' && $doit != 'yes' ) $doitt = '';

							?>
							<TD width="100" align="left" title="<?= $datahh[ 'title' ] ?>: <?= $d ?>">
								<div class="ellipsis1"><?= $doitt ?> <?= $d ?></div>
							</TD>
							<?php
							$data_plan = '';
							$doit      = '';
							$d         = '';

						}

					}
					//конец комплектность сделки
					?>
				</TR>
			<?php } ?>
			</tbody>
		</TABLE>

	</div>

</div>

<div style="height: 90px;"></div>

<script src="/assets/js/tableHeadFixer/tableHeadFixer.js"></script>
<script>
	$(function () {

		$("#zebra").tableHeadFixer({
			'head': true,
			'foot': false,
			'z-index': 12000,
			'left': 2
		}).css('z-index', '100');

		$("#zebra").find('td:nth-child(1)').css('z-index', '110');
		$("#zebra").find('td:nth-child(2)').css('z-index', '110');

	});

</script>