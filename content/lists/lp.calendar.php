<?php
/* ============================ */
/*         SalesMan CRM         */
/* ============================ */
/* (C) 2016 Vladislav Andreev   */
/*       SalesMan Project       */
/*        www.isaler.ru         */
/*        ver. 2017.x           */
/* ============================ */

error_reporting( E_ERROR );
header( "Pragma: no-cache" );

$rootpath = dirname(__DIR__, 2);

include $rootpath."/inc/config.php";
include $rootpath."/inc/dbconnector.php";
include $rootpath."/inc/auth.php";
include $rootpath."/inc/func.php";
include $rootpath."/inc/settings.php";
include $rootpath."/inc/language/".$language.".php";

$thisfile = basename( __FILE__ );

/**
 * Отрисовка календаря в левом блоке напоминаний
 */

$tm = $tzone;

$y = $_GET['y'];

if ( $y == '' || $y == 'NaN' ) {
	$y = date( "Y", mktime( date( 'H' ), date( 'i' ), date( 's' ), date( 'm' ), date( 'd' ), date( 'Y' ) ) + $tzone * 3600 );
}

$m = $_GET['m'];

if ( $m == '' || $m == 'NaN' ) {
	$m = date( "m", mktime( date( 'H' ), date( 'i' ), date( 's' ), date( 'm' ), date( 'd' ), date( 'Y' ) ) + $tzone * 3600 );
}

$nd = date( "d", mktime( date( 'H' ), date( 'i' ), date( 's' ), date( 'm' ), date( 'd' ), date( 'Y' ) ) + $tzone * 3600 );//текущий день
$nm = date( "m", mktime( date( 'H' ), date( 'i' ), date( 's' ), date( 'm' ), date( 'd' ), date( 'Y' ) ) + $tzone * 3600 );//текущий месяц
$ny = date( "Y", mktime( date( 'H' ), date( 'i' ), date( 's' ), date( 'm' ), date( 'd' ), date( 'Y' ) ) + $tzone * 3600 );//текущий год

$ndatum = date( 'Y-m-d H:i:s', mktime( date( 'H' ), date( 'i' ), date( 's' ), date( 'm' ), date( 'd' ), date( 'Y' ) ) + $tzone * 3600 );

$dd = date( "t", mktime( date( 'H' ), date( 'i' ), date( 's' ), $m, 1, $y ) + $tzone * 3600 ); //кол-во дней в текущем месяце
$d1 = date( "Y-m-d", mktime( date( 'H' ), date( 'i' ), date( 's' ), date( 'm' ), '01', date( 'Y' ) ) + $tzone * 3600 );
$d2 = date( "Y-m-d", mktime( date( 'H' ), date( 'i' ), date( 's' ), date( 'm' ), $dd, date( 'Y' ) ) + $tzone * 3600 );

//возможность перехода по месяцам и годам
if ( $m == 12 ) {
	$m1 = 1;
	$y1 = $y + 1;
	$m2 = $m - 1;
	$y2 = $y;
}
elseif ( $m == 1 ) {
	$m1 = $m + 1;
	$y1 = $y;
	$m2 = 12;
	$y2 = $y - 1;
}
else {
	$m1 = $m + 1;
	$y1 = $y;
	$m2 = $m - 1;
	$y2 = $y;
}

if ( strlen( $m1 ) < 2 )
	$m1 = "0".$m1;
if ( strlen( $m2 ) < 2 )
	$m2 = "0".$m2;

if ( strlen( $y ) < 4 )
	$y += 2000;
if ( strlen( $m ) < 2 )
	$m = "0".$m;

$dd1 = date( "t", mktime( date( 'H' ), date( 'i' ), date( 's' ), $m1, '01', $y1 ) + $tzone * 3600 ); //кол-во дней в следующем месяце
$d11 = strftime( '%Y-%m-%d', mktime( 0, 0, 0, $m1, '01', $y1 ) );
$d21 = strftime( '%Y-%m-%d', mktime( 0, 0, 0, $m1, $dd1, $y1 ) );

$dd2 = date( "t", mktime( date( 'H' ), date( 'i' ), date( 's' ), $m2, '01', $y2 ) + $tzone * 3600 ); //кол-во дней в прошлом месяце
$d12 = strftime( '%Y-%m-%d', mktime( 0, 0, 0, $m2, '01', $y2 ) );
$d22 = strftime( '%Y-%m-%d', mktime( 0, 0, 0, $m2, $dd2, $y2 ) );

$yy1 = $y1 - 2000;
$yy2 = $y2 - 2000;

$face = $_REQUEST['face'];
?>
<input class="subform" type="hidden" name="sy" id="sy" value="<?= $y ?>">
<input class="subform" type="hidden" name="sm" id="sm" value="<?= $m ?>">

<?php
// для рабочего стола
if($face == 'desktop'){
?>
<div class="calendarHeader">

	<A href="javascript:void(0)" onclick="$desktop.calendar('back')" class="gray fs-09"><i class="icon-angle-double-left"></i><?= ru_month( $m2 ).".".$yy2 ?></A>
	<a href="javascript:void(0)" onclick="$desktop.calendar('')" title="Обновить" class="gray fs-12"><i class="icon-arrows-ccw fs-09 blue"></i><b class="black"><?= ru_month( $m )."<sup class=\"fs-07 gray\">".substr( $y, 2, 3 )."</sup>" ?></b></a>&nbsp;
	<A href="javascript:void(0)" onclick="$desktop.calendar('next')" class="gray fs-09"><?= ru_month( $m1 ).".".$yy1 ?><i class="icon-angle-double-right"></i></A>

</div>
<?php
}
// для раздела Дела
else{
?>
<div class="calendarHeader">

	<A href="javascript:void(0)" onclick="changeMounth('back')" class="gray fs-09"><i class="icon-angle-double-left"></i><?= ru_month( $m2 ).".".$yy2 ?></A>
	<a href="javascript:void(0)" onclick="changeMounth('')" title="Обновить" class="gray fs-12"><i class="icon-arrows-ccw fs-09 blue"></i><b class="black"><?= ru_month( $m )."<sup class=\"fs-07 gray\">".substr( $y, 2, 3 )."</sup>" ?></b></a>&nbsp;
	<A href="javascript:void(0)" onclick="changeMounth('next')" class="gray fs-09"><?= ru_month( $m1 ).".".$yy1 ?><i class="icon-angle-double-right"></i></A>

</div>
<?php } ?>

<DIV id="cal_day" class="">

	<TABLE class="middle">
		<thead>
		<TR class="th20 text-center">
			<th class="wstart"><?= $lang['face']['WeekName'][0] ?></th>
			<th class="wstart"><?= $lang['face']['WeekName'][1] ?></th>
			<th class="wstart"><?= $lang['face']['WeekName'][2] ?></th>
			<th class="wstart"><?= $lang['face']['WeekName'][3] ?></th>
			<th class="wstart"><?= $lang['face']['WeekName'][4] ?></th>
			<th class="wend"><?= $lang['face']['WeekName'][5] ?></th>
			<th class="wend"><?= $lang['face']['WeekName'][6] ?></th>
		</TR>
		</thead>
		<tbody>
		<?php
		$dayofmonth = $dd; // Вычисляем число дней в текущем месяце
		$day_count  = 1; // Счётчик для дней месяца

		// 1. Первая неделя
		$num = 0;
		for ( $i = 0; $i < 7; $i++ ) {

			// Вычисляем номер дня недели для числа
			$dayofweek = date( 'w', mktime( 1, 0, 0, $m, $day_count, $y ) + $tm * 3600 );

			// Приводим к числа к формату 1 - понедельник, ..., 6 - суббота
			$dayofweek = $dayofweek - 1;

			if ( $dayofweek == -1 )
				$dayofweek = 6;
			if ( $dayofweek == $i ) {

				// Если дни недели совпадают,
				// заполняем массив $week
				// числами месяца
				$week[ $num ][ $i ] = $day_count;
				$day_count++;

			}
			else
				$week[ $num ][ $i ] = "";


		}

		// 2. Последующие недели месяца
		while (true) {

			$num++;
			for ( $i = 0; $i < 7; $i++ ) {

				$week[ $num ][ $i ] = $day_count;
				$day_count++;

				// Если достигли конца месяца - выходим
				// из цикла
				if ( $day_count > $dayofmonth )
					break;

			}

			// Если достигли конца месяца - выходим
			// из цикла
			if ( $day_count > $dayofmonth )
				break;

		}

		// 3. Выводим содержимое массива $week
		// в виде календаря
		// Выводим таблицу
		for ( $i = 0; $i < count( $week ); $i++ ) {
			?>
			<tr class="th20">
				<?php
				for ( $j = 0; $j < 7; $j++ ) {

					if ( !empty( $week[ $i ][ $j ] ) ) {

						// Если имеем дело с субботой и воскресенья
						// подсвечиваем их
						$bullet = '';

						$bg = ($j == 5 || $j == 6) ? 'bgray gray' : 'bgwhite';

						$d1 = ($week[ $i ][ $j ] < 10) ? "0".$week[ $i ][ $j ] : $week[ $i ][ $j ];

						$d      = $week[ $i ][ $j ];
						$datum1 = $y."-".$m."-".$d1;

						if ( date_to_unix( $datum1 ) == date_to_unix( $ndatum ) ) {

							$bg     = 'orangebg-sub';
							$bullet = 'today';

						}
						if ( date_to_unix( $datum1 ) < date_to_unix( $ndatum ) ) {

							$bg     = 'bgray gray';
							$bullet = 'old';

						}
						?>
						<td class="cal <?= $bg ?> text-center" id="cdiv_<?= $d ?>" onmouseOver="taskview('<?= $week[ $i ][ $j ] ?>')" onmouseOut="taskhide('<?= $d ?>')">
							<?php
							$jj = 0;
							$s  = $v = '';
							$w  = 'w30';

							$count = $db -> getOne( "select COUNT(*) from ".$sqlname."tasks where datum='".$datum1."' and (iduser='".$iduser1."') and active='yes' and identity = '$identity'" );
							if ( $count > 0 ) {

								$jj = 1;// 0 - нет записей, 1 - есть записи;
								$v  = '<span onclick="viewTaskList(\''.$datum1.'\');" title="'.$lang['face']['BusList'].'"><i class="icon-tasks blue"></i></span>';

							}

							if ( $bullet != 'old' ) {

								$s = '<span onclick="addTask(\''.$datum1.'\');" title="'.$lang['all']['Add'].'"><i class="icon-plus-circled red"></i></span>';

							}

							if ( $count == 0 && $bullet == 'old' )
								$w = 'hidden';
							if ( $count > 0 && $bullet != 'old' )
								$w = 'w60';

							?>
							<div id="caloption_<?= $d ?>" class="cal-option <?= $w ?>">

								<div class="top-triangle"></div>
								<div class="top-triangle-white"></div>
								<?= $s ?>
								<?= $v ?>

							</div>
							<?= ($jj == 1 ? '<span class="relativ cal-day">'.$week[ $i ][ $j ].'<span class="cal-bullet '.$bullet.'">'.$count.'</span></span>' : '<span class="relativ cal-day">'.$week[ $i ][ $j ].'</span>') ?>
						</td>
						<?php
					}
					else echo '<td class="text-center">&nbsp;</td>';

				}
				?>
			</tr>
			<?php
		}
		?>
		</tbody>
	</table>

	<?php
	if ( $y.'-'.$m == date( "Y-m", mktime( date( 'H' ), date( 'i' ), date( 's' ), date( 'm' ), date( 'd' ), date( 'Y' ) ) + $tm * 3600 ) && !isset($_REQUEST['tar']) ) {
		?>
		<div class="flex-container mt5">
			<div class="flex-string p5">
				<div class="grn p3 hand" onclick="taskWeek('old');" title="<?= $lang['face']['NotDoTask'] ?>"></div>
			</div>
			<div class="flex-string p5">
				<div class="rd p3 hand" onclick="taskWeek('');" title="<?= $lang['all']['Today'] ?>"></div>
			</div>
			<div class="flex-string p5">
				<div class="bl p3 hand" onclick="taskWeek('future');" title="<?= $lang['face']['NextThisMounthTask'] ?>"></div>
			</div>
		</div>
	<?php } ?>

</DIV>