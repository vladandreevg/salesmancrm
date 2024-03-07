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

$reportName = basename( __FILE__ );

$action = $_REQUEST['action'];
$da1    = $_REQUEST['da1'];
$da2    = $_REQUEST['da2'];

$users  = (array)$_REQUEST['user_list'];
$fields = (array)$_REQUEST['field'];
$query  = (array)$_REQUEST['field_query'];

$mon  = (int)$_REQUEST['mon'];
$year = (int)$_REQUEST['year'];

if ( !$mon ) {
	$mon = (int)date( 'n' );
}
if ( !$year ) {
	$year = (int)date( 'Y' );
}

$thisfile = basename( $_SERVER['PHP_SELF'] );

$list    = [];
$user    = [];
$sumUser = [];
$so      = '';
$sort    = '';
$color   = [
	"#E74C3C",
	"#F1C40F",
	"#1ABC9C"
]; //цвета первого круга - красный-желтый-зеленый

//кол-во дней в текущем месяце
$dd = date( "t", mktime( date( 'H' ), date( 'i' ), date( 's' ), $mon, 1, $year ) );
$nd = date( 'd' );

//Формируем доп.параметры запроса
if ( !empty( $users ) ) {
	$so = "iduser IN (".yimplode( ",", $users ).") and ";
	//$sort .= "deal.iduser IN (".yimplode( ",", $users ).") and ";
}
else {
	$so = "iduser IN (".yimplode( ",", get_people( $iduser1, "yes" ) ).") and ";
	//$sort .= "deal.iduser IN (".yimplode( ",", get_people( $iduser1, "yes" ) ).") and ";
}

//доп.параметрык сделкам
$ar = [
	'sid',
	'close',
	'mcid'
];
foreach ( $fields as $i => $field ) {

	if ( !in_array( $field, $ar ) && $field != '' ) {
		$sort .= " deal.{$field} = '".$field_query[ $i ]."' AND ";
	}
	elseif ( $field == 'close' ) {
		$sort .= $field_query[ $i ] != 'yes' ? " COALESCE(deal.{$field}, 'no') != 'yes' AND " : " COALESCE(deal.{$field}, 'no') == 'yes' AND ";
	}
	elseif ( $field == 'mcid' ) {
		$mc = $field_query[ $i ];
	}

}

//все пользователи
$re = $db -> getAll( "SELECT iduser, title FROM ".$sqlname."user WHERE iduser > 0 and acs_plan = 'on' and identity = '$identity' ORDER BY secrty DESC, title" );
foreach ( $re as $da ) {
	$username[ $da['iduser'] ] = $da['title'];
}

//Обходим пользователей
$re = $db -> getAll( "SELECT * FROM ".$sqlname."user WHERE iduser > 0 and $so acs_plan = 'on' and identity = '$identity' ORDER BY secrty DESC, title" );
foreach ( $re as $da ) {

	//учитываем всех подчиненных текущего пользователя
	if ( $ac_import[19] != 'on' ) {

		$sd = (array)get_people( $da['iduser'], 'yes' );

		if ( count( $sd ) > 0 ) {
			$xsort = " and deal.iduser IN (".yimplode( ",", $sd ).")";
		}

	}
	else {
		$xsort = " and deal.iduser = '".$da['iduser']."'";
	}

	//Расчет плановых показателей для заданного пользователя
	$res             = $db -> getRow( "SELECT SUM(kol_plan) as kol, SUM(marga) as marga FROM ".$sqlname."plan WHERE mon='".$mon."' and year='".$year."' and iduser = '".$da['iduser']."' and identity = '$identity'" );
	$planTotalOborot = $res['kol'];
	$planTotalMarga  = $res['marga'];

	//расчет плана на текущий день
	$planOborot = round( ($planTotalOborot / $dd) * $nd );
	$planMarga  = round( ($planTotalMarga / $dd) * $nd );

	$user[ $da['iduser'] ] = [
		"title"   => $da['title'],
		"secrty"  => $da['secrty'],
		"plan"    => $planTotalMarga,
		"dayplan" => $planMarga
	];

	$xsort = "deal.close = 'yes' and DATE_FORMAT(deal.datum_close, '%Y-%c') = '$year-$mon' AND ";

	$q2 = "
	SELECT
		DATE_FORMAT(deal.datum, '%d.%m.%Y') as dcreate,
		DATE_FORMAT(deal.datum, '%Y-%c') as dcreate2,
		DATE_FORMAT(deal.datum_plan, '%Y-%c') as dplan,
		DATE_FORMAT(deal.datum_close, '%Y-%c') as dfact,
		deal.did as did,
		deal.title as dogovor,
		deal.close as close,
		deal.kol as summa,
		deal.kol_fact as fsumma,
		deal.marga as marga,
		deal.iduser as iduser,
		dc.title as step
	FROM ".$sqlname."dogovor `deal`
		LEFT JOIN ".$sqlname."dogcategory `dc` ON deal.idcategory = dc.idcategory
	WHERE
		deal.did > 0 and
		$sort
		$xsort
		deal.identity = '$identity'
	ORDER BY deal.datum
	";

	//перебираем сделки и считаем показатели
	$rez = $db -> query( $q2 );
	while ($daz = $db -> fetch( $rez )) {

		if ( $daz['close'] != "yes" ) {
			$aKol = $daz['marga'];
			$fKol = 0;
		}
		else {
			$aKol = 0;
			$fKol = $daz['marga'];
		}

		if ( $daz['dcreate2'] == $year.'-'.$mon ) {
			$sumUser[ $da['iduser'] ]['num']++;
		}

		$sumUser[ $da['iduser'] ]['aSum'] += $aKol;
		$sumUser[ $da['iduser'] ]['fSum'] += $fKol;

		$daz['step'] = 'Закрыта';

		if ( $daz['payer'] < 1 && $daz['clid'] > 0 ) {
			$daz['payer'] = $daz['clid'];
		}

		$list[ $da['iduser'] ][] = [
			"did"     => $daz['did'],
			"dcreate" => $daz['dcreate'],
			"deal"    => $daz['dogovor'],
			"iduser"  => $daz['iduser'],
			"aKol"    => $aKol,
			"fKol"    => $fKol,
			"summa"   => $daz['fsumma'],
			"marga"   => $daz['marga'],
			"clid"    => $daz['payer'],
			"client"  => current_client( $daz['payer'] ),
			"close"   => $daz['close'],
			"step"    => $daz['step']
		];

	}

	$xsort = "deal.close != 'yes' AND";

	$q1 = "
	SELECT
		DATE_FORMAT(deal.datum, '%d.%m.%Y') as dcreate,
		DATE_FORMAT(deal.datum, '%Y-%c') as dcreate2,
		DATE_FORMAT(deal.datum_plan, '%Y-%c') as dplan,
		DATE_FORMAT(deal.datum_close, '%Y-%c') as dfact,
		deal.did as did,
		deal.title as dogovor,
		deal.close as close,
		deal.kol as summa,
		deal.kol_fact as fsumma,
		deal.marga as marga,
		deal.iduser as iduser,
		dc.title as step
	FROM ".$sqlname."dogovor `deal`
		LEFT JOIN ".$sqlname."dogcategory `dc` ON deal.idcategory = dc.idcategory
	WHERE
		deal.did > 0 and
		$sort
		$xsort
		deal.identity = '$identity'
	ORDER BY deal.datum
	";

	//перебираем сделки и считаем показатели
	$rez = $db -> query( $q1 );
	while ($daz = $db -> fetch( $rez )) {

		$aKol = $daz['marga'];
		$fKol = 0;

		if ( $daz['dcreate2'] == $year.'-'.$mon ) {
			$sumUser[ $da['iduser'] ]['num']++;
		}

		$sumUser[ $da['iduser'] ]['aSum'] += $aKol;
		$sumUser[ $da['iduser'] ]['fSum'] += $fKol;

		if ( $daz['payer'] < 1 && $daz['clid'] > 0 ) {
			$daz['payer'] = $daz['clid'];
		}

		$list[ $da['iduser'] ][] = [
			"did"     => $daz['did'],
			"dcreate" => $daz['dcreate'],
			"deal"    => $daz['dogovor'],
			"iduser"  => $daz['iduser'],
			"aKol"    => $aKol,
			"fKol"    => $fKol,
			"summa"   => $daz['summa'],
			"marga"   => $daz['marga'],
			"clid"    => $daz['payer'],
			"client"  => current_client( $daz['payer'] ),
			"close"   => $daz['close'],
			"step"    => $daz['step'].'%'
		];

	}

}
?>
<STYLE type="text/css">
	<!--
	.color1 {
		background : rgba(231, 76, 60, 0.5);
	}
	.color2 {
		background : rgba(241, 196, 15, 0.5);
	}
	.color3 {
		background : rgba(26, 188, 150, 0.5);
	}
	.w80 {
		width : 80px;
	}
	-->
</STYLE>

<div class="zagolovok_rep">
	<h2>Выполнение планов за <span class="blue"><?= ru_mon( $mon ) ?></span> <?= $year ?> (по закрытым сделкам):</h2>
</div>

<DIV class="infodiv margbot10 pad10">

	<div class="inline select"><b class="blue">Год:</b>
		<select name="year" id="year">
			<?php
			for ( $i = date( 'Y' ) - 2; $i <= date( 'Y' ); $i++ ) {

				if ( !$year && $i == date( 'Y' ) ) {
					$s = 'selected';
				}
				elseif ( $year && $i == $year ) {
					$s = 'selected';
				}
				else {
					$s = '';
				}

				print '<option value="'.$i.'" '.$s.'>'.$i.'&nbsp;&nbsp;</option>';

			}
			?>
		</select>
	</div>
	<div class="inline select margleft10"><b class="blue">Месяц:</b>
		<select name="mon" id="mon">
			<?php
			for ( $i = 1; $i <= 12; $i++ ) {

				if ( !$mon && $i == date( 'm' ) ) {
					$s = 'selected';
				}
				elseif ( $mon && $i == $mon ) {
					$s = 'selected';
				}
				else {
					$s = '';
				}

				print '<option value="'.$i.'" '.$s.'>'.ru_mon( $i ).'</option>';

			}
			?>
		</select>
	</div>

</DIV>

<hr>

<div id="data">

	<table class="top">
		<thead class="sticked--top">
		<tr>
			<th class="">ФИО сотрудника</th>
			<th class="w120">План прибыль<br>(Маржа)</th>
			<th class="w120">Маржа<br>(активные сделки)</th>
			<th class="w120">Маржа<br>(закрытые сделки)</th>
			<th class="w120">Выполнение, %<br>(всего)</th>
			<th class="w120">Выполнение, %<br>(расчет)</th>
			<th class="w80">Кол-во заявок</th>
		</tr>
		</thead>
		<tbody>
		<?php
		foreach ( $user as $key => $value ) {

			if ( $value['secrty'] == 'yes' ) {
				$usericon = '<b><i class="icon-user-1 blue"></i>&nbsp;'.$value['title'].'</b>';
			}
			else {
				$usericon = '<b class="gray" title="Не активен"><i class="icon-user-1"></i>&nbsp;'.$value['title'].'</b>';
			}

			$proc  = $value['plan'] > 0 ? ($sumUser[ $key ]['fSum'] / $value['plan']) * 100 : 0;
			$dproc = $value['dayplan'] > 0 ? ($sumUser[ $key ]['fSum'] / $value['dayplan']) * 100 : 0;

			if ( $value['plan'] > 0 ) {

				if ( $sumUser[ $key ]['fSum'] > $value['dayplan'] * 0.9 ) {
					$Color = "color3";
				}
				elseif ( $sumUser[ $key ]['fSum'] < $value['dayplan'] * 0.7 ) {
					$Color = "color1";
				}
				else {
					$Color = "color2";
				}

				if ( $sumUser[ $key ]['fSum'] > $value['plan'] * 0.9 ) {
					$Color2 = "color3";
				}
				elseif ( $sumUser[ $key ]['fSum'] < $value['plan'] * 0.7 ) {
					$Color2 = "color1";
				}
				else {
					$Color2 = "color2";
				}

			}
			else {

				$Color  = '';
				$Color2 = '';

			}
			?>
			<tr class="ha">
				<td class="hand showdeals" data-user="<?= $key ?>">
					<div class="ellipsis">
						<?php
						if ( count( $list[ $key ] ) > 0 ) {
							print '<i class="icon-angle-down angle gray"></i>&nbsp;';
						}
						else {
							print '<i class="icon-angle-down white"></i>&nbsp;';
						}
						?>
						<?= $usericon ?>
					</div>
				</td>
				<td class="text-right">
					<div title="План по марже" class="Bold"><?= num_format( $value['plan'] ) ?></div>
					<div class="em gray" title="Расчетный план по марже (с учетом даты)"><?= num_format( $value['dayplan'] ) ?></div>
				</td>
				<td class="text-right"><?= num_format( $sumUser[ $key ]['aSum'] ) ?></td>
				<td class="text-right"><?= num_format( $sumUser[ $key ]['fSum'] ) ?></td>
				<td class="text-right <?= $Color ?>"><b><?= num_format( $proc ) ?></b></td>
				<td class="text-right <?= $Color ?>"><b><?= num_format( $dproc ) ?></b></td>
				<td class="text-right"><?= number_format( $sumUser[ $key ]['num'], 0 ) ?></td>
			</tr>
			<?php
			if ( count( $list[ $key ] ) > 0 ) {

				$u  = get_people( $key, 'yes' );
				$ub = [];
				?>
				<tr class="ha hidden" id="user<?= $key ?>">
					<td colspan="8">
						<?php
						foreach ( $u as $k => $v ) {
							if ( $v != $key && $username[ $v ] != '' ) {
								$ub[] = $username[ $v ];
							}
						}
						if ( count( $u ) > 1 ) {
							?>
							<div class="pad10">
								<b>Подчиненные:</b> <?php print yimplode( ", ", $ub ); ?>
							</div>
						<?php } ?>
						<div class="infodiv">

							<table class="bgwhite fs-09">
								<thead>
								<tr>
									<th class="">Сделка</th>
									<th class="w120">Дата создания</th>
									<th class="text-right yw100">Сумма</th>
									<th class="text-right yw100">Маржа</th>
									<th class="yw160">Сотрудник</th>
								</tr>
								</thead>
								<tbody>
								<?php
								foreach ($list[ $key ] as $row) {

									$icon = ( $row['close'] == 'yes' ) ? '<i class="icon-lock red"></i>' : '<i class="icon-briefcase-1 blue"></i>';

									?>
									<tr>
										<td title="<?= $row['deal'] ?>">
											<div class="ellipsis">
												<a href="javascript:void(0)" onclick="viewDogovor('<?= $row['did'] ?>')"><?= $icon ?> <?= $row['deal'] ?></a>&nbsp;
												[ <b class="em"><?= $row['step'] ?></b> ]
											</div>
										</td>
										<td class="text-center"><?= $row['dcreate'] ?></td>
										<td class="text-right"><b><?= num_format( $row['summa'] ) ?></b></td>
										<td class="text-right"><b><?= num_format( $row['marga'] ) ?></b></td>
										<td><div class="ellipsis"><?= $user[ $row['iduser'] ]['title'] ?></div></td>
									</tr>
									<?php
								}
								?>
								</tbody>
							</table>

						</div>
					</td>
				</tr>
			<?php
			}
		}
		?>
		</tbody>
	</table>

</div>

<hr>
<div class="pad10 infodiv">
	<ul>
		<li>В отчет попадают ВСЕ <b>активные</b> сделки и
			<b>закрытые</b> сделки, Дата.Закрытия которых совпадают с указанным месяцем
		</li>
		<li>Учитываются только сотрудники с отметкой "Имеет план продаж" в личных настройках</li>
		<li>В квоту сотрудника попадают все сделки подчиненных сотрудников, если не указано "План продаж индивидуальный" в личных настройках</li>
	</ul>

</div>

<div class="h40"></div>
<div class="h40"></div>

<script>

	$('.showdeals').on('click', function () {
		var id = $(this).data('user');

		if ($('#user' + id).is('tr')) {

			$('#user' + id).toggleClass('hidden');
			$(this).find('i.angle').toggleClass('icon-angle-down icon-angle-up');

		}
	})

</script>