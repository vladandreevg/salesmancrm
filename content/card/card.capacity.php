<?php
/* ============================ */
/*         SalesMan CRM         */
/* ============================ */
/* (C) 2016 Vladislav Andreev   */
/*       SalesMan Project       */
/*        www.isaler.ru         */
/*        ver. 2017.x           */
/* ============================ */
?>
<?php
error_reporting(E_ERROR);
header("Pragma: no-cache");

$rootpath = realpath( __DIR__.'/../../' );

include $rootpath."/inc/config.php";
include $rootpath."/inc/dbconnector.php";
include $rootpath."/inc/auth.php";
include $rootpath."/inc/func.php";
include $rootpath."/inc/settings.php";
include $rootpath."/inc/language/".$language.".php";

$thisfile = basename( __FILE__ );

$clid   = (int)$_REQUEST['clid'];
$action = $_REQUEST['action'];

if ($action == 'add_do') {

	$direction = (int)$_REQUEST['direction'];
	$year      = (int)$_REQUEST['year'];
	$mon       = (int)$_REQUEST['mon'];
	$summa     = pre_format($_REQUEST['summa']);

	try {

		$db -> query("insert into ".$sqlname."capacity_client (id, direction, clid, year, mon, sumplan,identity) values (null, '".$direction."', '".$clid."', '".$year."', '".$mon."', '".$summa."','$identity')");
		print 'Успешно';

	}
	catch (Exception $e) {

		print 'Ошибка'.$e -> getMessage().' в строке '.$e -> getCode();

	}

}

if ($action == 'edit_do') {

	$direction = (int)$_REQUEST['direction'];
	$year      = (int)$_REQUEST['year'];
	$mon       = (int)$_REQUEST['mon'];
	$id        = (int)$_REQUEST['id'];
	$summa     = pre_format($_REQUEST['summa']);

	try {

		$db -> query("update ".$sqlname."capacity_client set direction = '".$direction."', year = '".$year."', mon = '".$mon."', sumplan = '".$summa."' WHERE id = '".$id."' and identity = '$identity'");
		print 'Сделано';

	}
	catch (Exception $e) {

		print 'Ошибка'.$e -> getMessage().' в строке '.$e -> getCode();

	}

}

if ($_REQUEST['act'] == 'del') {

	$clid = (int)$_REQUEST['clid'];
	$id   = (int)$_REQUEST['id'];

	try {

		$db -> query("delete from ".$sqlname."capacity_client WHERE id = '".$id."' and identity = '$identity'");
		print 'Успешно';

	}
	catch (Exception $e) {

		print 'Ошибка'.$e -> getMessage().' в строке '.$e -> getCode();

	}
}

if ($action == 'add') {
	?>
	<FORM method="post" action="content/card/card.capacity.php" enctype="multipart/form-data" name="Form" id="Form">
		<input name="action" id="action" type="hidden" value="add_do"/>
		<input name="clid" id="clid" type="hidden" value="<?= $clid ?>"/>
		<DIV class="zagolovok">Добавить план:</DIV>

		<table>
			<tr>
				<td width="130"><b>Направление:</b></td>
				<td>
					<select name="direction" id="direction" class="required" style="width:90%">
						<option value="" selected>--Выбор--</option>
						<?php
						$resultt = $db -> query("SELECT * FROM ".$sqlname."direction WHERE identity = '$identity' ORDER BY title");
						while ($data = $db -> fetch($resultt)) {
							?>
							<option value="<?= $data['id'] ?>" <?php if ($data['id'] == $direction) print "selected"; ?>><?= $data['title'] ?></option>
						<?php } ?>
					</select>
				</td>
			</tr>
			<tr>
				<td><b>Год:</b></td>
				<td>
					<select name="year" id="year" class="required">
						<?php
						$ys = date('Y') - 3;
						$ye = date('Y') + 5;
						for ($j = $ys; $j < $ye; $j++) {
							?>
							<option value="<?= $j ?>" <?php if ($j == date('Y')) print "selected"; ?>><?= $j ?></option>
						<?php } ?>
					</select>
				</td>
			</tr>
			<tr>
				<td><b>Месяц:</b></td>
				<td>
					<select name="mon" id="mon" class="required">
						<?php
						$ys = 1;
						$ye = 12;
						for ($j = $ys; $j <= $ye; $j++) {
							?>
							<option value="<?= $j ?>" <?php if ($j == date('m')) print "selected"; ?>><?= smonth($j) ?></option>
						<?php } ?>
					</select>
				</td>
			</tr>
			<tr>
				<td><b>Сумма план:</b></td>
				<td>
					<input name="summa" id="summa" type="text" class="required" value="<?= $sumplan ?>"/>&nbsp;<?= $valuta ?>
				</td>
			</tr>
		</table>
		<hr>
		<DIV class="button--pane text-right">
			<A href="#" onClick="$('#Form').submit()" class="button">Сохранить</A>&nbsp;
			<A href="#" onClick="DClose();" class="button">Отмена</A>
		</DIV>
	</FORM>
	<SCRIPT type="text/javascript">
		$(document).ready(function () {
			$('#dialog').css('width', '500px');
		});
		$('#Form').ajaxForm({
			beforeSubmit: function () {

				var $out = $('#message');
				var em = checkRequired();

				if (em === false) return false;

				$('#dialog').css('display', 'none');
				$('#dialog_container').css('display', 'none');

				$('#message').css('display', 'block').append('<div id=loader><img src=/assets/images/loader.gif> Загрузка данных. Пожалуйста подождите...</div>');

				return true;

			},
			success: function (data) {

				$('#tab11').load('content/card/card.capacity.php?clid=<?=$clid?>');
				$('#message').css('display', 'block').html(data).fadeOut(5000);

			}
		});

		$('#dialog').center();

	</SCRIPT>
	<?php
}

if ($action == 'edit') {

	$id = (int)$_REQUEST['id'];

	$result = $db -> getRow("SELECT * FROM ".$sqlname."capacity_client WHERE id = '".$id."' and identity = '$identity'");
	if (!empty($result)) {

		$direction = $result["direction"];
		$year      = $result["year"];
		$mon       = $result["mon"];
		$sumplan   = num_format($result["sumplan"]);

	}
	?>
	<FORM method="post" action="content/card/card.capacity.php" enctype="multipart/form-data" name="Form" id="Form">
		<input name="action" id="action" type="hidden" value="edit_do"/>
		<input name="clid" id="clid" type="hidden" value="<?= $clid ?>"/>
		<input name="id" id="id" type="hidden" value="<?= $id ?>"/>
		<DIV class="zagolovok">Изменить план:</DIV>
		<table width="500" border="0" cellspacing="2" cellpadding="2">
			<tr>
				<td width="130"><b>Направление:</b></td>
				<td>
					<select name="direction" id="direction" class="required" style="width:90%">
						<option value="">--Выбор--</option>
						<?php
						$resultt = $db -> query("SELECT * FROM ".$sqlname."direction WHERE identity = '$identity' ORDER BY title");
						while ($data = $db -> fetch($resultt)) {
							?>
							<option value="<?= $data['id'] ?>" <?php if ($data['id'] == $direction) print "selected"; ?>><?= $data['title'] ?></option>
						<?php } ?>
					</select>
				</td>
			</tr>
			<tr>
				<td><b>Год:</b></td>
				<td>
					<select name="year" id="year" class="required">
						<?php
						$ys = date('Y') - 3;
						$ye = date('Y') + 5;
						for ($j = $ys; $j < $ye; $j++) {
							?>
							<option value="<?= $j ?>" <?php if ($j == $year) print "selected"; ?>><?= $j ?></option>
						<?php } ?>
					</select>
				</td>
			</tr>
			<tr>
				<td><b>Месяц:</b></td>
				<td>
					<select name="mon" id="mon" class="required">
						<?php
						$ys = 1;
						$ye = 12;
						for ($j = $ys; $j <= $ye; $j++) {
							?>
							<option value="<?= $j ?>" <?php if ($j == $mon) print "selected"; ?>><?= smonth($j) ?></option>
						<?php } ?>
					</select>
				</td>
			</tr>
			<tr>
				<td><b>Сумма план:</b></td>
				<td>
					<input name="summa" id="summa" type="text" class="required" value="<?= $sumplan ?>"/>&nbsp;<?= $valuta ?>
				</td>
			</tr>
		</table>
		<hr>
		<DIV class="button--pane text-right">
			<A href="#" onClick="$('#Form').submit()" class="button">Сохранить</A>&nbsp;
			<A href="#" onClick="DClose();" class="button">Отмена</A>
		</DIV>
	</FORM>
	<SCRIPT type="text/javascript">
		$(document).ready(function () {
			$('#dialog').css('width', '500px');
		});
		$('#Form').ajaxForm({
			beforeSubmit: function () {

				var $out = $('#message');
				var em = checkRequired();

				if (em === false) return false;

				$('#dialog').css('display', 'none');
				$('#dialog_container').css('display', 'none');

				$('#message').css('display', 'block').append('<div id=loader><img src=/assets/images/loader.gif> Загрузка данных. Пожалуйста подождите...</div>');

				return true;

			},
			success: function (data) {
				$('#tab11').load('card/card.capacity.php?clid=<?=$clid?>');
				$('#message').css('display', 'block').html(data).fadeOut(5000);
			}
		});

		$('#dialog').center();
	</SCRIPT>
	<?php
}

if ($_REQUEST['action'] == '') {

	print '<div class="fcontainer1 pt5">';
	$result = $db -> query("SELECT * FROM ".$sqlname."capacity_client WHERE clid = '".$clid."' and identity = '$identity' ORDER BY year, mon");
	if ($db -> affectedRows($result) > 0) {
		?>
		<table width="100%" border="0" cellspacing="0" cellpadding="5" id="zebra">
			<thead>
			<tr height="40" class="header_contaner">
				<th width="60" align="center">Период</th>
				<th align="center">Направление</th>
				<th width="80" align="center">Статус, %</th>
				<th width="100" align="center">План, <?= $valuta ?></th>
				<th width="100" align="center">Прогноз, <?= $valuta ?></th>
				<th width="100" align="center">Факт, <?= $valuta ?></th>
				<th width="80" align="center">&nbsp;</th>
			</tr>
			</thead>
			<?php
			$i  = 0;
			$yy = date('Y');
			while ($dataa = $db -> fetch($result)) {

				$prognoz = 0;

				$direction = $db -> getOne("SELECT title FROM ".$sqlname."direction WHERE id='".$dataa['direction']."' and identity = '$identity'");

				//Посчитаем прогноз на текущий месяц с учетом этапов
				if ($dataa['mon'] < 10) $mon = '0'.$dataa['mon'];

				$resultp = $db -> query("SELECT * FROM ".$sqlname."dogovor WHERE date_format(datum_plan, '%Y-%m') = '".$dataa['year']."-".$mon."' and close != 'yes' and clid = '".$clid."' and direction = '".$dataa['direction']."' and identity = '$identity'");
				while ($data = $db -> fetch($resultp)) {

					$prognoz = $prognoz + (floatval(current_dogstepname($data['idcategory'])) * $data['kol']) / 100;

				}

				//Посчитаем статус
				$cap_perc = (($dataa['sumfact'] + $prognoz) / $dataa['sumplan']) * 100;
				?>
				<tr height="40">
					<td align="center"><?= smonth($dataa['mon']) ?>&nbsp;<?= $dataa['year'] ?></td>
					<td>
						<div class="ellipsis"><?= $direction ?></div>
					</td>
					<td align="right"><?= num_format($cap_perc) ?>&nbsp;</td>
					<td align="right"><?= num_format($dataa['sumplan']) ?>&nbsp;</td>
					<td align="right"><?= num_format($prognoz) ?>&nbsp;</td>
					<td align="right"><?= num_format($dataa['sumfact']) ?>&nbsp;</td>
					<td align="center">
						<?php
						//Запретим изменение для прошлых периодов
						$dd    = date("t", mktime(1, 0, 0, $dataa['mon'], 1, $dataa['year'])); //число дней в указанном месяце
						$delta = datestoday($dataa['year'].'-'.$dataa['mon'].'-'.$dd);// число дней, прошедших с последнего дня месяца
						if (($delta >= 0 or $isadmin == 'on') && get_accesse((int)$clid) == 'yes') {
							?>
							<a href="#" onclick="doLoad('content/card/card.capacity.php?clid=<?= $clid ?>&action=edit&id=<?= $dataa['id'] ?>')" title="Изменить"><i class="icon-pencil blue"></i></a>&nbsp;
							<span style="color:#ccc"><?php if ($isadmin == 'on') { ?>|</span>&nbsp;
							<a href="#" onclick="cf=confirm('Вы действительно хотите удалить запись?');if (cf)refresh('tab11','content/card/card.capacity.php?clid=<?= $clid ?>&act=del&id=<?= $dataa['id'] ?>');" title="Удалить"><i class="icon-cancel-circled red"></i></a><?php } ?>
							<?php
						}
						?>
					</td>
				</tr>
				<?php
			}
			?>
		</table>
		<?php
	}
	else print '<div>Планы не установлены</div>';
	print '</div>';
}
?>