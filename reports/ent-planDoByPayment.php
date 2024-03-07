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

//TODO: переделать расчеты на оплаты. сейчас реализовано выполнение по закрытым сделкам

$action = $_REQUEST['action'];
$da1    = $_REQUEST['da1'];
$da2    = $_REQUEST['da2'];
$mon    = $_REQUEST['mon'];
$year   = $_REQUEST['year'];
$period = $_REQUEST['period'];

$users  = (array)$_REQUEST['user_list'];
$fields = (array)$_REQUEST['field'];
$query  = (array)$_REQUEST['field_query'];

$users = (!empty( $users )) ? $users : (array)get_people( $iduser1, "yes" );

if ( !$mon )
	$mon = date( 'n' );
if ( !$year )
	$year = date( 'Y' );

$thisfile = basename( $_SERVER['PHP_SELF'] );
$prefix   = $_SERVER['DOCUMENT_ROOT']."/";

$list    = [];
$user    = [];
$sumUser = [];
$so      = '';
$sord    = '';
$color   = [
	"#E74C3C",
	"#F1C40F",
	"#1ABC9C"
]; //цвета первого круга - красный-желтый-зеленый

//кол-во дней в текущем месяце
$dd = (int)date( "t", mktime( (int)date( 'H' ), (int)date( 'i' ), (int)date( 's' ), (int)$mon, 1, (int)$year ) );
$nd = (int)date( 'd' );

//Формируем доп.параметры запроса
$so = "iduser IN (".yimplode( ",", (array)$users ).") AND ";

//доп.параметрык сделкам
$ar = [
	'close',
	'idcategory'
];
foreach ( $fields as $i => $field ) {

	if ( !in_array( $field, $ar ) && $field != 'close' ) {
		$sord .= " deal.{$field} = '".$field_query[ $i ]."' AND ";
	}
	elseif ( $field == 'close' ) {
		$sord .= $field_query[ $i ] != 'yes' ? " COALESCE(deal.{$field}, 'no') != 'yes' AND " : " COALESCE(deal.{$field}, 'no') == 'yes' AND ";
	}

}

//все пользователи
$username = $db -> getIndCol( "iduser", "SELECT title, iduser FROM {$sqlname}user WHERE acs_plan = 'on' and identity = '$identity' ORDER BY secrty DESC, title" );

if ( !$otherSettings['credit'] ) {
	$text = '<li>В отчет попадают ВСЕ <b>активные</b> сделки и <b>закрытые</b> сделки, Дата.Закрытия которых совпадают с указанным месяцем</li>';
}
if ( $otherSettings['credit'] && !$otherSettings['planByClosed'] ) {
	$text = '<li>Расчеты строятся по <b>оплаченным счетам в периоде</b> в соответствии с настройками системы</li>';
}
if ( $otherSettings['credit'] && $otherSettings['planByClosed'] ) {
	$text = '<li>Расчеты строятся по <b>оплаченным счетам</b> в Сделках, <b>закрытых в отчетном периоде</b> в соответствии с настройками системы</li>';
}

//Обходим пользователей
$re = $db -> getAll( "SELECT * FROM {$sqlname}user WHERE iduser > 0 and $so acs_plan = 'on' and identity = '$identity' ORDER BY secrty DESC, title" );
foreach ( $re as $da ) {

	$uset = yexplode( ";", (string)$da['acs_import'] );
	$sort = '';

	//Расчет плановых показателей для заданного пользователя
	$res             = $db -> getRow( "SELECT SUM(kol_plan) as kol, SUM(marga) as marga FROM {$sqlname}plan WHERE mon = '$mon' and year = '$year' and iduser = '".$da['iduser']."' and identity = '$identity'" );
	$planTotalOborot = (float)$res['kol'];
	$planTotalMarga  = (float)$res['marga'];

	//расчет плана на текущий день
	$planOborot = round( ($planTotalOborot / $dd) * $nd );
	$planMarga  = round( ($planTotalMarga / $dd) * $nd );

	$user[ $da['iduser'] ] = [
		"title"    => $da['title'],
		"uset"     => $uset[19],
		"secrty"   => $da['secrty'],
		"plan"     => $planTotalOborot,
		"dayplan"  => $planOborot,
		"mplan"    => $planTotalMarga,
		"daymplan" => $planMarga
	];

	if ( !$otherSettings['credit'] ) {

		//учитываем всех подчиненных текущего пользователя
		$sort = ($uset[19] != 'on') ? "deal.iduser IN (".implode( ",", (array)get_people( $da['iduser'], 'yes' ) ).") and " : "deal.iduser = '".$da['iduser']."' and ";

		//Обходим закрытые сделки, чтобы посчитать по ним маржу
		$sort2 = "$sord $sort COALESCE(deal.close, 'no') = 'yes' and DATE_FORMAT(deal.datum_close, '%Y-%c') = '$year-$mon' AND ";

		//print
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
			{$sqlname}dogcategory.title as step
		FROM {$sqlname}dogovor `deal`
			LEFT JOIN {$sqlname}dogcategory ON deal.idcategory = {$sqlname}dogcategory.idcategory
		WHERE
			deal.did > 0 and 
			$sort2
			deal.identity = '$identity'
		ORDER BY deal.datum
		";

		//перебираем сделки и считаем показатели
		$rez = $db -> getAll( $q2 );
		foreach ( $rez as $daz ) {

			if ( $daz['close'] != "yes" ) {

				$aKol = (float)$daz['marga'];
				$fKol = 0;

			}
			else {

				$aKol = 0;
				$fKol = (float)$daz['marga'];

			}

			if ( $daz['dcreate2'] == $year.'-'.$mon ) {
				$sumUser[ $da['iduser'] ]['num']++;
			}

			$sumUser[ $da['iduser'] ]['aSum'] += (float)$aKol;
			$sumUser[ $da['iduser'] ]['fSum'] += (float)$fKol;

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

		//Обходим активные сделки

		$sort1 = "$sord $sort and COALESCE(deal.close, 'no') != 'yes'";

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
			{$sqlname}dogcategory.title as step
		FROM {$sqlname}dogovor `deal`
			LEFT JOIN {$sqlname}dogcategory ON deal.idcategory = {$sqlname}dogcategory.idcategory
		WHERE
			deal.did > 0 and 
			$sort1
			deal.identity = '$identity'
		ORDER BY deal.datum
		";

	}
	if ( $otherSettings['credit'] ) {

		//учитываем всех подчиненных текущего пользователя
		$sort = ($uset[19] != 'on') ? "cr.iduser IN (".implode( ",", (array)get_people( $da['iduser'], 'yes' ) ).") and " : "cr.iduser = '".$da['iduser']."' and ";
		$dsort = ($uset[19] != 'on') ? "deal.iduser IN (".implode( ",", (array)get_people( $da['iduser'], 'yes' ) ).") and " : "deal.iduser = '".$da['iduser']."' and ";

		//выполнение планов по оплатам
		if ( !$otherSettings['planByClosed'] ) {
			$q = "
			SELECT
				cr.did as did,
				cr.iduser as iduser,
				cr.summa_credit as summa,
				cr.datum_credit as dplan,
				cr.invoice_date as dfact,
				cr.invoice as invoice,
				deal.title as dogovor,
				deal.kol as dsumma,
				deal.marga as dmarga,
				deal.iduser as diduser,
				deal.close as close,
				deal.clid as clid
			FROM {$sqlname}credit `cr`
			LEFT JOIN {$sqlname}dogovor `deal` ON cr.did = deal.did
			WHERE
				cr.do = 'on' and
				DATE_FORMAT(cr.invoice_date, '%Y-%c') = '$year-$mon' and
				$sort
				$dsort
				cr.identity = '$identity'
			ORDER by cr.invoice_date";
		}

		//выполнение учет только оплат по закрытым сделкам в указанном периоде
		if ( $otherSettings['planByClosed'] ) {
			$q = "
			SELECT
				cr.did as did,
				cr.iduser as iduser,
				cr.summa_credit as summa,
				cr.datum_credit as dplan,
				cr.invoice_date as dfact,
				cr.invoice as invoice,
				deal.title as dogovor,
				deal.kol as dsumma,
				deal.marga as dmarga,
				deal.iduser as diduser,
				deal.close as close,
				deal.clid as clid
			FROM {$sqlname}credit `cr`
			LEFT JOIN {$sqlname}dogovor `deal` ON cr.did = deal.did
			WHERE
				cr.do = 'on' and
				COALESCE(deal.close, 'no') = 'yes' and
				DATE_FORMAT(deal.datum_close, '%Y-%c') = '$year-$mon') and
				$sort
				$dsort
				cr.identity = '$identity'
			ORDER by cr.invoice_date";
		}

		//проходим оплаты
		$rez = $db -> getAll( $q );
		foreach ( $rez as $daz ) {

			$dolya = 0;

			//оплачено
			$sumUser[ $da['iduser'] ]['aSum'] += (float)$daz['summa'];

			//доля оплаты в сумме сделки
			if ( (float)$daz['dsumma'] > 0 ) {
				$dolya = (float)$daz['summa'] / (float)$daz['dsumma'];
			}

			//print $daz['did'].": ".$daz['dmarga']." : ".$dolya."\n";

			//маржа
			$sumUser[ $da['iduser'] ]['aMarg'] += (float)$daz['dmarga'] * $dolya;

			$list[ $da['iduser'] ][] = [
				"did"     => $daz['did'],
				"invoice" => $daz['invoice'],
				"date"    => $daz['dfact'],
				"deal"    => $daz['dogovor'],
				"dsumma"  => $daz['dsumma'],
				"dmarga"  => $daz['dmarga'],
				"iduser"  => $daz['iduser'],
				"diduser" => $daz['diduser'],
				"aSum"    => $daz['summa'],
				"aMarg"   => $daz['summa'] * $dolya,
				"close"   => $daz['close']
			];

		}

	}

}

//print ru_mon( 1 );

//print_r($sumUser);
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

	<h2>Выполнение планов за <span class="blue"><?= ru_mon( $mon ) ?></span> <?= $year ?>:</h2>

</div>

<DIV class="infodiv margbot10 pad10">

	<input type="hidden" id="period" name="period" value="<?= $_REQUEST['period'] ?>">

	<div class="inline select"><b class="blue">Год:</b>
		<select name="year" id="year">
			<?php
			for ( $i = date( 'Y' ) - 2, $iMax = date( 'Y' ); $i <= $iMax; $i++ ) {

				if ( (!$year && $i == date( 'Y' )) || ($year && $i == $year) )
					$s = 'selected';
				else $s = '';

				print '<option value="'.$i.'" '.$s.'>'.$i.'&nbsp;&nbsp;</option>';
			}
			?>
		</select>
	</div>
	<div class="inline select margleft10"><b class="blue">Месяц:</b>
		<select name="mon" id="mon">
			<?php
			for ( $i = 1; $i <= 12; $i++ ) {

				if ( !$mon && $i == (int)date( 'n' ) )
					$s = 'selected';
				elseif ( $i == $mon )
					$s = 'selected';
				else $s = '';

				print '<option value="'.$i.'" '.$s.'>'.ru_mon( $i ).'</option>';

			}
			?>
		</select>
	</div>
	<?php if ( !isset( $_REQUEST['da1'] ) ) { ?>
		<div class="inline margleft10">

			<a href="javascript:void(0)" onclick="getReportPlan()" class="button greenbtn dotted1 ptb5lr15">Расчет</a>

		</div>
	<?php } ?>
	<?php if ( isset( $_REQUEST['da1'] ) ) { ?>
		<div class="inline margleft10">

			<a href="javascript:void(0)" onclick="generateReport()" class="button bluebtn dotted1 ptb5lr15">Расчет</a>

		</div>
	<?php } ?>

</DIV>

<hr>

<?php

//если график платежей не включен, то считаем только по закрытым сделкам
if ( !$otherSettings['credit'] ) {
	?>
	<div id="data">

		<table>
			<thead class="sticked--top">
			<tr>
				<th class="">ФИО сотрудника</th>
				<th class="w120">План прибыль<br>(Маржа)</th>
				<th class="w120">Маржа<br>(активные сделки)</th>
				<th class="w120">Маржа<br>(закрытые сделки)</th>
				<th class="w120">Выполнение, %<br>(всего)</th>
				<th class="w120">Выполнение, %<br>(расчет)</th>
				<th class="w60">Кол-во заявок</th>
			</tr>
			</thead>
			<tbody>
			<?php
			foreach ( $user as $key => $value ) {

				$Color  = '';
				$Color2 = '';

				if ( $value['secrty'] == 'yes' )
					$usericon = '<b><i class="icon-user-1 blue"></i>&nbsp;'.$value['title'].'</b>';
				else $usericon = '<b class="gray" title="Не активен"><i class="icon-user-1"></i>&nbsp;'.$value['title'].'</b>';

				$proc  = $value['plan'] > 0 ? ($sumUser[ $key ]['fSum'] / $value['plan']) * 100 : 0;
				$dproc = $value['dayplan'] > 0 ? ($sumUser[ $key ]['fSum'] / $value['dayplan']) * 100 : 0;

				if ( $value['plan'] > 0 ) {

					if ( $sumUser[ $key ]['fSum'] > $value['dayplan'] * 0.9 )
						$Color = "color3";
					elseif ( $sumUser[ $key ]['fSum'] < $value['dayplan'] * 0.7 )
						$Color = "color1";
					else $Color = "color2";


					if ( $sumUser[ $key ]['fSum'] > $value['plan'] * 0.9 )
						$Color2 = "color3";
					elseif ( $sumUser[ $key ]['fSum'] < $value['plan'] * 0.7 )
						$Color2 = "color1";
					else $Color2 = "color2";

				}
				?>
				<tr class="ha th35">
					<td class="hand showdeals" data-user="<?= $key ?>">
						<div class="ellipsis">
							<?php
							print (!empty( $list[ $key ] )) ? '<i class="icon-angle-down angle gray"></i>&nbsp;' : '<i class="icon-angle-down white"></i>&nbsp;';
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
				if ( !empty( $list[ $key ] ) ) {

					if ( $value['uset'] != 'on' ) {

						$sd = get_people( $key, 'yes' );
						if ( !empty( $sd ) )
							$u = $sd;

					}
					else $u = ["0" => $iduser1];

					$ub = [];
					?>
					<tr class="ha hidden th35" id="user<?= $key ?>">
						<td colspan="8">
							<?php
							foreach ( $u as $k => $v ) {
								if ( $v != $key && $username[ $v ] != '' )
									$ub[] = $username[ $v ];
							}
							if ( !empty( $u ) ) {
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
									foreach ( $list[ $key ] as $row ) {

										$icon = ($row['close'] == 'yes') ? '<i class="icon-lock red"></i>' : '<i class="icon-briefcase-1 blue"></i>';
										?>
										<tr>
											<td title="<?= $row['deal'] ?>">
												<div class="ellipsis">
													<a href="javascript:void(0)" onclick="viewDogovor('<?= $row['did'] ?>')"><?= $icon ?> <?= $row['deal'] ?></a>&nbsp;[
													<b class="em"><?= $row['step'] ?></b> ]
												</div>
											</td>
											<td class="text-center"><?= $row['dcreate'] ?></td>
											<td class="text-right"><b><?= num_format( $row['summa'] ) ?></b></td>
											<td class="text-right"><b><?= num_format( $row['marga'] ) ?></b></td>
											<td>
												<div class="ellipsis"><?= $user[ $row['iduser'] ]['title'] ?></div>
											</td>
										</tr>
										<?php
									}
									?>
									</tbody>
								</table>

							</div>
						</td>
					</tr>
				<?php } ?>
				<?php
			}
			?>
			</tbody>
		</table>

	</div>
<?php } ?>

<?php
//если включен график платежей, то считаем по оплатам
if ( $otherSettings['credit'] ) {
	?>
	<div id="data" class="fs-11">

		<table>
			<thead class="sticked--top no-shadow">
			<tr>
				<th rowspan="2" class="fs-12">ФИО сотрудника</th>
				<th colspan="3" class="w100 fs-12 noshadow">Оборот</th>
				<th colspan="3" class="w100 fs-12 noshadow">Маржа</th>
			</tr>
			<tr class="">
				<th class="w120">план, <?= $valuta ?></th>
				<th class="w120">факт, <?= $valuta ?></th>
				<th class="w60">%</th>
				<th class="w120">план, <?= $valuta ?></th>
				<th class="w120">факт, <?= $valuta ?></th>
				<th class="w60">%</th>
			</tr>
			</thead>
			<tbody>
			<?php
			foreach ( $user as $key => $value ) {

				$Color1     = $Color2 = 'color1';
				$procOborot = $procMarga = 0;

				$usericon = ($value['secrty'] == 'yes') ? '<b><i class="icon-user-1 blue"></i>&nbsp;'.$value['title'].'</b>' : '<b class="gray" title="Не активен"><i class="icon-user-1"></i>&nbsp;'.$value['title'].'</b>';

				$procOborot = $value['plan'] > 0 ? ($sumUser[ $key ]['aSum'] / $value['plan']) * 100 : 0;
				$procMarga  = $value['mplan'] > 0 ? ($sumUser[ $key ]['aMarg'] / $value['mplan']) * 100 : 0;

				if ( $value['plan'] > 0 ) {

					if ( is_between( $procOborot, 70, 90 ) ) {
						$Color1 = 'color2';
					}
					elseif ( $procOborot > 90 ) {
						$Color1 = 'color3';
					}

					if ( is_between( $procMarga, 70, 90 ) ) {
						$Color2 = 'color2';
					}
					elseif ( $procMarga > 90 ) {
						$Color2 = 'color3';
					}

				}
				?>
				<tr class="ha th55">
					<td class="hand showdeals" data-user="<?= $key ?>">
						<div class="ellipsis">
							<?= (!empty( $list[ $key ] ) ? '<i class="icon-angle-down angle gray"></i>&nbsp;' : '<i class="icon-angle-down white"></i>&nbsp;') ?>
							<?= $usericon ?>
						</div>
					</td>
					<td class="text-right">
						<div title="План по обороту" class="Bold"><?= num_format( $value['plan'] ) ?></div>
					</td>
					<td class="text-right">
						<?= num_format( (float)$sumUser[ $key ]['aSum'] ) ?>
					</td>
					<td class="text-center <?= $Color1 ?>">
						<b><?= num_format( (float)$procOborot ) ?></b>
					</td>
					<td class="text-right">
						<div title="План по марже" class="Bold"><?= num_format( (float)$value['mplan'] ) ?></div>
					</td>
					<td class="text-right">
						<?= num_format( (float)$sumUser[ $key ]['aMarg'] ) ?>
					</td>
					<td class="text-center <?= $Color2 ?>">
						<b><?= num_format( (float)$procMarga ) ?></b>
					</td>
				</tr>
				<?php
				if ( !empty( $list[ $key ] ) ) {

					$sd = [];

					if ( $value['uset'] != 'on' ) {
						$sd = (array)get_people( $key, 'yes' );
						if ( count( $sd ) > 0 ) {
							$u = $sd;
						}
					}
					else {
						$u = ["0" => $key];
					}

					$ub = [];
					?>
					<tr class="ha hidden th35" id="user<?= $key ?>">
						<td colspan="8">
							<?php
							foreach ( $u as $k => $v ) {
								if ( $v != $key && $username[ $v ] != '' ) {
									$ub[] = $username[$v];
								}
							}
							if ( count( $u ) > 1 ) {
								?>
								<div class="pad10 blue em fs-09">
									<b>Подчиненные:</b> <?php print yimplode( ", ", $ub ); ?>
								</div>
							<?php } ?>
							<div class="viewdiv">

								<table class="bgwhite fs-09">
									<thead>
									<tr>
										<th class="w60">№ счета</th>
										<th class="w100">Сумма счета</th>
										<th class="w80">Дата оплаты</th>
										<th class="text-left w120">Сотрудник</th>
										<th>Сделка</th>
										<th class="text-center w100">Сумма</th>
										<th class="text-center w100">Маржа</th>
										<th class="w100">Куратор сделки</th>
									</tr>
									</thead>
									<tbody>
									<?php
									foreach ( $list[ $key ] as $row ) {

										if ( $row['close'] == 'yes' ) {
											$icon = '<i class="icon-lock red"></i>';
										}
										else {
											$icon = '<i class="icon-briefcase-1 blue"></i>';
										}
										?>
										<tr class="th45">
											<td class="text-center"><?= $row['invoice'] ?></td>
											<td class="text-right">
												<b><?= num_format( $row['aSum'] ) ?></b></td>
											<td class="text-center"><?= format_date_rus( $row['date'] ) ?></td>
											<td>
												<div class="ellipsis"><?= $user[ $row['iduser'] ]['title'] ?></div>
											</td>
											<td title="<?= $row['deal'] ?>">
												<div class="ellipsis">
													<a href="javascript:void(0)" onclick="viewDogovor('<?= $row['did'] ?>')"><?= $icon ?> <?= $row['deal'] ?></a>
												</div>
											</td>
											<td class="text-right">
												<b><?= num_format( $row['dsumma'] ) ?></b></td>
											<td class="text-right">
												<b><?= num_format( $row['dmarga'] ) ?></b></td>
											<td>
												<div class="ellipsis"><?= $user[ $row['diduser'] ]['title'] ?></div>
											</td>
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
<?php } ?>

<hr>
<div class="pad10 infodiv">

	<ul>
		<?= $text ?>
		<li>Учитываются только сотрудники с отметкой "Имеет план продаж" в личных настройках</li>
		<li>В квоту сотрудника попадают все сделки (или оплаты) подчиненных сотрудников, если не указано "План продаж индивидуальный" в личных настройках</li>
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

	});

	function getReportPlan() {

		var year = $('#swindow').find('#year').val();
		var mon = $('#swindow').find('#mon').val();

		$('#swindow').find('.body').empty().append('<div id="loader" class="loader"><img src=/assets/images/loading.gif> Вычисление...</div>').load($('#swUrl').val() + '?year=' + year + '&mon=' + mon + '&period=<?=$period?>');

		return false;

	}
</script>