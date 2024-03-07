<?php
/* ============================ */
/*         SalesMan CRM         */
/* ============================ */
/* (C) 2016 Vladislav Andreev   */
/*       SalesMan Project       */
/*        www.isaler.ru         */
/*        ver. 2017.x           */
/* ============================ */

error_reporting( 0 );
header( "Pragma: no-cache" );

$rootpath = dirname(__DIR__, 2);

include $rootpath."/inc/config.php";
include $rootpath."/inc/dbconnector.php";
include $rootpath."/inc/auth.php";
include $rootpath."/inc/func.php";
include $rootpath."/inc/settings.php";

$thisfile = basename( __FILE__ );

if ( $_POST['action'] == 'clean' ) {

	if ( empty( $_SERVER['HTTP_X_REQUESTED_WITH'] ) && strtolower( $_SERVER['HTTP_X_REQUESTED_WITH'] ) != 'xmlhttprequest' ) {

		print '<div class="bad" align="center"><br>Доступ запрещен.<br>Обратитесь к администратору.<br><br></div>';
		exit();

	}

	$err  = 0;
	$good = 0;

	if ( $_POST['history'] == 'yes' ) {

		//Удалим всю историю переговоров
		$result1 = $db -> getAll( "select * from ".$sqlname."history WHERE datum < '".$_POST['datum']." 00:00:00' and identity = '$identity'" );
		foreach ( $result1 as $data_array1 ) {

			if ( $db -> query( "delete from ".$sqlname."history where cid = '".$data_array1['cid']."' and identity = '$identity'" ) )
				$good++;
			else $err++;

		}

	}

	if ( $_POST['tasks'] == 'yes' ) {

		//Удалим все напоминания
		$result1 = $db -> getAll( "select * from ".$sqlname."tasks WHERE datum < '".$_POST['datum']."' and identity = '$identity'" );
		foreach ( $result1 as $data_array1 ) {

			if ( $db -> query( "delete from ".$sqlname."tasks where tid = '".$data_array1['tid']."' and identity = '$identity'" ) )
				$good++;
			else $err++;

		}

	}

	if ( $_POST['file'] == 'yes' ) {

		//Удалим всю связанные файлы
		$dd      = date_to_unix( $_POST['datum'] );
		$result1 = $db -> getAll( "select * from ".$sqlname."file WHERE FORMAT( fname, 0 ) < FORMAT( ".$dd.", 0 ) and identity = '$identity'" );
		foreach ( $result1 as $data_array1 ) {

			@unlink( $rootpath."/files/".$fpath.$data_array1['fname'] );
			if ( $db -> query( "delete from ".$sqlname."file where fid = '".$data_array1['fid']."' and identity = '$identity'" ) )
				$good++;
			else $err++;

		}

	}

	if ( $_POST['invoice'] == 'yes' ) {

		//Удалим всю связанные файлы
		$dir   = $rootpath."/files/".$fpath;
		$files = [];
		$totl  = 0;
		foreach ( scandir( $dir ) as $file ) {

			if ( strpos( $file, 'invoice' ) !== false || strpos( $file, 'akt' ) !== false || strpos( $file, '.ics' ) !== false ) {

				$totl = $totl + pre_format( round( filesize( $dir.$file ) / 1024, 2 ) );
				unlink( $dir.$file );

			}

		}

	}

	print "Удалено записей: ".$good.". Ошибок: ".$err.". Овобождено места на диске - ".$totl." Mb";

	exit();

}
if ( $_POST['action'] == '' ) {

	$dir   = $rootpath."/files/".$fpath;
	$files = [];
	$totl  = 0;

	foreach ( scandir( $dir ) as $file ) {

		if ( ($file != "." && $file != "..") && (strpos( $file, 'invoice' ) !== false || strpos( $file, 'akt' ) !== false || strpos( $file, '.ics' ) !== false) ) {

			$totl += filesize( $dir.$file );

			$files[] = '<div class="fs-09 flh-10"><b>'.$file.'</b> <span class="gray2">[ '.num_format( filesize( $dir.$file ) / 1024 ).'KB ]</span></div>';

		}

	}

	$totl = pre_format( round( $totl / 1024 / 1024, 2 ) );

	?>
	<h2>&nbsp;Очистка системы от старых записей</h2>

	<div class="warning">

		Операция очистит выбранные типы данных до указанной даты, не включительно.<br>
		<b class="red">Записи будут удалены безвозвратно.</b>
		<?php if ( $isCloud != 'yes' ) { ?>
			<p>Предварительно сделайте резервную копию БД.</p>
		<?php } ?>

	</div>

	<hr>

	<form action="content/admin/<?php echo $thisfile; ?>" method="post" enctype="application/x-www-form-urlencoded" name="cleanform" id="cleanform">
		<div class="infodiv margbot10">
			<input name="action" id="action" type="hidden" value="clean"/>
			<div class="marg3">
				<label><input name="tasks" type="checkbox" value="yes"/>&nbsp;Напоминания</label>
			</div>
			<div class="marg3">
				<label><input name="history" type="checkbox" value="yes"/>&nbsp;Активности</label>
			</div>
			<div class="marg3">
				<label><input name="file" type="checkbox" value="yes"/>&nbsp;Файлы</label>
			</div>
			<div class="marg3">
				<label><input name="invoice" type="checkbox" value="yes"/>&nbsp;Файлы счетов и актов [без учета даты] - освободится
					<b class="red"><?= $totl ?> Mb</b></label>
				<div style="overflow: auto; max-height: 100px; margin: 10px 0; padding-left: 30px">
					<?php print implode( "", $files ); ?>
				</div>
			</div>
			<br>
			<hr>
			<div class="marg3 margbot10">
				<b>До даты</b>: <input name="datum" id="datum" type="text" class="required datum"/>
			</div>
		</div>
		<hr>
		<div>
			<A href="javascript:void(0)" onClick="$('#cleanform').submit()" class="button">Очистить</A>
		</div>
	</form>

	<script>

		$(function () {

			$(".datum").datepicker({
				dateFormat: 'yy-mm-dd',
				numberOfMonths: 2,
				firstDay: 1,
				dayNamesMin: ['Вс', 'Пн', 'Вт', 'Ср', 'Чт', 'Пт', 'Сб'],
				monthNamesShort: ['Январь', 'Февраль', 'Март', 'Апрель', 'Май', 'Июнь', 'Июль', 'Август', 'Сентябрь', 'Октябрь', 'Ноябрь', 'Декабрь'],
				changeMonth: true,
				changeYear: true,
				yearRange: '2014:2030',
				minDate: new Date(1940, 1 - 1, 1),
				showButtonPanel: true,
				currentText: 'Сегодня',
				closeText: 'Готово'
			});

			$('#cleanform').ajaxForm({
				beforeSubmit: function () {

					var $out = $('#message');
					var em = checkRequired();

					if (em === false) return false;

					$out.css('display', 'block').append('<div id=loader><img src=/assets/images/loader.gif> Загрузка данных. Пожалуйста подождите...</div>');
					return true;

				},
				success: function (data) {

					$('#contentdiv').load('content/admin/<?php echo $thisfile; ?>');
					$('#message').fadeTo(1, 1).css('display', 'block').html(data);

					setTimeout(function () {
						$('#message').fadeTo(1000, 0);
					}, 20000);

				}
			});
		});
	</script>
<?php } ?>