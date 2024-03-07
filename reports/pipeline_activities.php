<?php
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
$top    = $_REQUEST['top'];

if ( $top == '' )
	$top = 10;

$act = $_REQUEST['act'];
$per = $_REQUEST['per'];

if ( !$per )
	$per = 'nedelya';

$user_list    = (array)$_REQUEST['user_list'];
$clients_list = (array)$_REQUEST['clients_list'];
$persons_list = (array)$_REQUEST['persons_list'];
$field        = (array)$_REQUEST['field'];
$field_query  = (array)$_REQUEST['field_query'];

//Обработка get_параметров
$get_name   = array_keys( $_GET );
$get_params = $_GET;

$param = [];

foreach ( $get_params as $i => $xp ) {

	$name[ $i ] = $get_name[ $i ];

	if ( is_array( $xp[ $name[ $i ] ] ) ) {
		$param[ $i ] = implode( ":", (array)$xp[ $name[ $i ] ] );
	}
	else {
		$param[ $i ] = $xp[ $name[ $i ] ];
	}

}

foreach ( $get_name as $i => $xp ) {
	if ( $i == count( $get_name ) - 1 ) {
		$squery .= $name[ $i ]."::".$param[ $i ];
	}
	else {
		$squery .= $name[ $i ]."::".$param[ $i ]."|";
	}
}

$ar = [
	'user_list',
	'field',
	'field_query'
];
foreach ( $nparam as $i => $np ) {

	if ( $np['name'] == 'user_list' ) {
		$$np['name'] = explode( ":", (string)$np['param'] );
	}
	elseif ( $np['name'] == 'field' ) {
		$$np['name'] = explode( ":", $np['param'] );
	}
	elseif ( $np['name'] == 'field_query' ) {
		$$np['name'] = explode( ":", $np['param'] );
	}
	elseif ( in_array( $np['name'], $ar ) ) {
		$$np['name'] = $np['param'];
	}
}

if ( empty( $user_list ) ) {
	$user_list = (array)get_people( $iduser1, 'yes' );
}

$sort .= " iduser IN (".yimplode( ",", $user_list ).") AND ";

if ( !empty( $clients_list ) ) {
	$clients_list = implode( ",", $clients_list );
}
if ( !empty( $persons_list ) ) {
	$persons_list = implode( ",", $persons_list );
}

if ( $action == '' ) {

	// массив типов активностей и цветов
	$types = $db -> getIndCol( "title", "SELECT title, color FROM ".$sqlname."activities WHERE identity = '$identity'" );

	// массив сотрудников
	$users = $db -> getIndCol( "iduser", "SELECT iduser, title FROM ".$sqlname."user WHERE identity = '$identity'" );

	$newQuery = "
		SELECT 
			hs.iduser, hs.tip, COUNT(hs.cid) as count 
		FROM ".$sqlname."history `hs`
		WHERE 
			hs.iduser IN (".yimplode( ",", $user_list ).") AND 
			DATE(hs.datum) >= '$da1' AND DATE(hs.datum) <= '$da2' AND 
			hs.identity = '$identity'
		GROUP BY 1, 2
		";
	$data     = $db -> getAll( $newQuery );
	$list     = $xlist = [];
	foreach ( $data as $row ) {

		$list[ $row['iduser'] ][ $row['tip'] ] = $row['count'];
		$xlist[ $row['iduser'] ][]             = $row['count'];

	}
	?>

	<div class="zagolovok_rep fs-12 text-center">
		<h1>Статистика активностей по сотрудникам</h1>
	</div>

	<div id="graphic" style="margin:10px 30px 10px 30px">

		<table id="zebra" class="setka bordered top">
			<thead>
			<tr class="head_tbl text-center">
				<th class="w200">Сотрудник</th>
				<th>Количество активностей</th>
				<th class="w100">Всего</th>
			</tr>
			</thead>
			<?php
			foreach ( $list as $iduser => $rows ) {

				$all = array_sum( $xlist[ $iduser ] );
				$max = max( $xlist[ $iduser ] );
				?>
				<tr>
					<td><?= $users[ $iduser ] ?></td>
					<td>
						<?php
						foreach ( $rows as $tip => $ac ) {

							$t = $max > 0 ? round( $ac / $max * 100 ) : 0;

							if ( $t < 1 ) {
								$t = "1px";
							}
							else {
								$t .= "%";
							}

							?>

							<div class="chartdiv fs-07 p5" style="width:<?= $t ?>; background:<?= $types[ $tip ] ?>" title="<?= $tip ?> - <?= $ac ?>">
								<div style="float:left"><?= $ac ?></div>
							</div>
						<?php } ?>
					</td>
					<td class="text-right"><b><?= $all ?></b></td>
				</tr>
			<?php } ?>
		</table>

	</div>

	<div id="bshow" style="margin:10px 0px 10px 30px">
		<a href="javascript:void(0)" onclick="$('#spisok').show(); $('#bshow').hide();" class="button">Показать в цифрах</a>
	</div>

	<div id="spisok" style="display:none">

		<div id="bhide" style="margin:0px 0px 10px 20px">
			<a href="javascript:void(0)" onclick="$('#spisok').hide(); $('#bshow').show();" class="button">Скрыть цифры</a>
		</div>

		<div style="margin:10px 20px 10px 20px">

			<table id="zebra" class="setka">
				<thead class="text-center">
				<tr>
					<td><b>Сотрудник</b></td>
					<?php
					foreach ( $types as $tip => $color ) {
						print '<td class="info" style="background-color:'.$color.'; width:40px;" title="'.$tip.'"><div class="ellipsis">'.$tip.'</div></td>';
					}
					?>
				<tr>
				</thead>
				<tbody>
				<?php
				$summa = [];
				foreach ( $users as $iduser => $name ) {

					print '
					<tr class="ha">
					<td>&nbsp;'.$name.'</td>';

					foreach ( $types as $tip => $color ) {

						print '<td class="text-center '.((int)$list[$iduser][$tip] == 0 ? 'gray': 'Bold').'">'.(int)$list[$iduser][$tip].'</td>';

						$summa[ $tip ] += (int)$list[ $iduser ][ $tip ];


					}

					print '</tr>';

				}
				?>
				</tbody>
				<tfoot>
				<!--Итоги-->
				<tr style="background:#cfc">
					<td class="text-right"><b>ИТОГО:</b></td>
					<?php
					foreach ( $types as $tip => $color ) {
						print '<td class="text-center"><b>'.$summa[ $tip ].'</b></td>';
					}
					?>
				</tr>
				</tfoot>
			</table>

			<div class="infodiv dotted bgwhite legend" style="margin:10px 0px 10px 30px">

				<?php foreach ( $types as $tip => $color ) { ?>
				<div class="cub" style="background:<?= $color ?>" title="<?= $tip ?>"></div><?= $tip ?>
				<?php } ?>

			</div>

		</div>

	</div>

	<hr>

	<div class="formdiv">Вы можете использовать параметры: Период, Сотрудники</div>

<?php } ?>