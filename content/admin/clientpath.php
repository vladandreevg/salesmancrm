<?php
/* ============================ */
/*         SalesMan CRM         */
/* ============================ */
/* (C) 2016 Vladislav Andreev   */
/*       SalesMan Project       */
/*        www.isaler.ru         */
/*        ver. 2019.x           */
/* ============================ */

error_reporting(E_ERROR);

header("Pragma: no-cache");

$rootpath = dirname(__DIR__, 2);

include $rootpath."/inc/config.php";
include $rootpath."/inc/dbconnector.php";
include $rootpath."/inc/auth.php";
include $rootpath."/inc/func.php";
include $rootpath."/inc/settings.php";

$thisfile = basename( __FILE__ );


$action = $_REQUEST['action'];
$id     = (int)$_REQUEST['id'];

if ($action == "delete.do") {

	$multi = $_REQUEST['multi'];

	if ($multi == '') {

		$db -> query("update {$sqlname}clientcat set clientpath = '".$_REQUEST['newid']."' WHERE clientpath = '".$id."' and identity = '$identity'");

		$db -> query("update {$sqlname}personcat set clientpath = '".$_REQUEST['newid']."' WHERE clientpath = '".$id."' and identity = '$identity'");
		$db -> query("update {$sqlname}leads set clientpath = '".$_REQUEST['newid']."' WHERE clientpath = '".$id."' and identity = '$identity'");
		$db -> query("delete from {$sqlname}clientpath where id = '".$id."' and identity = '$identity'");

	}
	else {

		$db -> query("update {$sqlname}clientcat set clientpath = '".$_REQUEST['newid']."' WHERE clientpath IN (".$multi.") and identity = '$identity'");

		$db -> query("update {$sqlname}personcat set clientpath = '".$_REQUEST['newid']."' WHERE clientpath IN (".$multi.") and identity = '$identity'");
		$db -> query("update {$sqlname}leads set clientpath = '".$_REQUEST['newid']."' WHERE clientpath IN (".$multi.") and identity = '$identity'");
		$db -> query("delete from {$sqlname}clientpath where id IN (".$multi.") and identity = '$identity'");

	}
	print "Сделано";

	unlink($rootpath."/cash/".$fpath."settings.all.json");

	exit();

}

if ($action == "edit.do") {

	$name        = $_REQUEST['name'];
	$isDefault   = $_REQUEST['isDefault'];
	$utm_source  = $_REQUEST['utm_source'];
	$destination = prepareMobPhone($_REQUEST['destination']);

	//снимем умолчания
	if ($isDefault == 'yes') {

		$db -> query("update {$sqlname}clientpath set isDefault = '' where identity = '$identity'");

	}

	if ($id == 0) {

		$db -> query("INSERT INTO {$sqlname}clientpath SET ?u", [
			'name'        => $name,
			'isDefault'   => ($isDefault == 'yes') ? $isDefault : 'no',
			'utm_source'  => $utm_source,
			'destination' => $destination,
			'identity'    => $identity
		]);

		echo 'Сделано';

	}
	else {

		$db -> query("UPDATE {$sqlname}clientpath SET ?u WHERE id = '$id' and identity = '$identity'", [
			'name'        => $name,
			'isDefault'   => ($isDefault == 'yes') ? $isDefault : 'no',
			'destination' => $destination,
			'utm_source'  => $utm_source
		]);

		echo 'Сделано';

	}

	unlink($rootpath."/cash/".$fpath."settings.all.json");

	exit();

}

if (empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) != 'xmlhttprequest') {

	print '<div class="warning text-center"><br>Доступ запрещен.<br>Обратитесь к администратору.<br><br></div>';
	exit();

}

if ($action == "edit") {

	$res = [];

	$res["name"] = "";

	if ($id > 0) {

		$res         = $db -> getRow("SELECT * FROM {$sqlname}clientpath where id = '".$id."' and identity = '$identity'");
		$name        = $res["name"];
		$isDefault   = $res["isDefault"];
		$utm_source  = $res["utm_source"];
		$destination = $res["destination"];

	}
	?>
	<div class="zagolovok"><b>Изменить / Добавить источник</b></div>
	<FORM action="/content/admin/<?php echo $thisfile; ?>" method="post" enctype="multipart/form-data" name="Form" id="Form">
		<input name="action" id="action" type="hidden" value="edit.do"/>
		<input name="id" id="id" type="hidden" value="<?= $id ?>"/>

		<div class="row">

			<div class="column12 grid-3 right-text fs-12 gray2 pt10">Название:</div>
			<div class="column12 grid-9">
				<input type="text" name="name" id="name" value="<?= $res['name'] ?>" class="wp97 required">
			</div>

		</div>
		<div class="row">

			<div class="column12 grid-3 right-text fs-12 gray2">По-умолчанию:</div>
			<div class="column12 grid-9">
				<label><input id="isDefault" name="isDefault" type="checkbox" value="yes" <?php if ($res['isDefault'] == 'yes') print 'checked' ?> />&nbsp;Использовать по-умолчанию&nbsp;</label>
			</div>

		</div>
		<div class="row">

			<div class="column12 grid-3 right-text fs-12 gray2 pt10">Источник:</div>
			<div class="column12 grid-9">
				<input name="utm_source" type="text" id="utm_source" class="wp97" value="<?= $res['utm_source'] ?>">
				<div class="em gray2 fs-09">( utm_source )</div>
			</div>

		</div>
		<div class="row">

			<div class="column12 grid-3 right-text fs-12 gray2 pt10">Телефон:</div>
			<div class="column12 grid-9">
				<input name="destination" type="text" id="destination" class="wp60" value="<?= $res['destination'] ?>">
				<div class="em gray2 fs-09">( Номер входящей линии )</div>
			</div>

		</div>

		<hr>

		<div class="button--pane text-right">

			<A href="javascript:void(0)" onclick="$('#Form').trigger('submit')" class="button">Сохранить</A>&nbsp;
			<A href="javascript:void(0)" onclick="DClose()" class="button">Отмена</A>

		</div>
	</FORM>

	<script>

		$(function () {

			$('#dialog').css('width', '600px').center();

			$("input").bind("change", function () {

				var v = $(this).val();

				//console.log(v);

				$(this).val(v);
				//$(this).setAttribute("value", v);

			});

		});

		$('#Form').ajaxForm({

			beforeSubmit: function () {

				var $out = $('#message');
				var em = checkRequired();

				if (em === false) return false;

				$out.fadeTo(1, 1).empty();
				$('#dialog').css('display', 'none');
				$('#dialog_container').css('display', 'none');
				//remEditor2();
				$out.css('display', 'block').append('<div id="loader"><img src="/assets/images/loader.gif"> Загрузка данных. Пожалуйста подождите...</div>');
				return true;

			},
			success: function (data) {

				//$('#contentdiv').load('content/admin/<?php echo $thisfile; ?>');
				razdel(hash);

				$('#message').fadeTo(1, 1).css('display', 'block').html(data);
				setTimeout(function () {
					$('#message').fadeTo(1000, 0);
				}, 20000);

				DClose();

			}

		});

	</script>
	<?php
	exit();
}

if ($action == "delete") {

	$multi = $_REQUEST['multi'];

	if (empty($multi)) {

		$tip = $db -> getOne("SELECT name FROM {$sqlname}clientpath WHERE id = '$id' and identity = '$identity'");

		$countC = (int)$db -> getOne("SELECT COUNT(*) FROM {$sqlname}clientcat WHERE clientpath = '$id' and identity = '$identity'");
		$countP = (int)$db -> getOne("SELECT COUNT(*) FROM {$sqlname}personcat WHERE clientpath = '$id' and identity = '$identity'");

	}
	else {

		$multi = implode(",", $multi);
		$tip   = $db -> getCol("SELECT name FROM {$sqlname}clientpath WHERE id IN ($multi) and identity = '$identity'");
		$tip   = yimplode(", ", $tip);

		$countC = (int)$db -> getOne("SELECT COUNT(*) FROM {$sqlname}clientcat WHERE clientpath IN ($multi) and identity = '$identity'");
		$countP = (int)$db -> getOne("SELECT COUNT(*) FROM {$sqlname}personcat WHERE clientpath IN ($multi) and identity = '$identity'");

	}
	$count = $countC + $countP;
	?>

	<div class="zagolovok">Удалить <?= $tip ?></div>

	<FORM action="/content/admin/<?php echo $thisfile; ?>" method="POST" name="form" id="form">
		<input type="hidden" id="id" name="id" value="<?= $id ?>">
		<input id="multi" name="multi" type="hidden" value="<?= $multi ?>">
		<input name="action" type="hidden" value="delete.do" id="action"/>

		<div class="infodiv">
			В случае удаления, данный тип останется в существующих записях и они не будут участвовать в отчетах. Вы можете перевести их на новый тип.
		</div>

		<hr>

		<div class="row">

			<div class="column12 grid-3 right-text fs-12 gray2">Новый источник:</div>
			<div class="column12 grid-9">
				<select name="newid" id="newid" style="width: 100%;" class="required">
					<option value="">--выбрать--</option>
					<?php
					if ($multi != '') $m = ' and id NOT IN ('.$multi.')';
					$result_a = $db -> getAll("SELECT * FROM {$sqlname}clientpath WHERE (id != '".$id."' $m) and identity = '$identity' ORDER by name");
					foreach ($result_a as $data_arraya) {
						?>
						<option value="<?= $data_arraya['id'] ?>"><?= $data_arraya['name'] ?></option>
					<?php } ?>
				</select>
			</div>

		</div>

		<div class="infodiv div-center">Будет затронуто <b><?= $count ?></b> записей Клиентов/Контактов.</div>

		<hr>

		<div class="button--pane text-right">

			<A href="javascript:void(0)" onclick="$('#form').trigger('submit')" class="button">Сохранить</A>&nbsp;
			<A href="javascript:void(0)" onclick="DClose()" class="button">Отмена</A>

		</div>
	</FORM>
	<script>

		$('#dialog').css('width', '600px');

		$(function () {

			$('#form').ajaxForm({

				beforeSubmit: function () {

					var $out = $('#message');
					var em = checkRequired();

					if (em === false) return false;

					$('#dialog').css('display', 'none');
					$('#dialog_container').css('display', 'none');
					$out.css('display', 'block').append('<div id=loader><img src=/assets/images/loader.gif> Загрузка данных. Пожалуйста подождите...</div>');
					return true;

				},
				success: function (data) {

					razdel(hash);

					$('#message').fadeTo(1, 1).css('display', 'block').html(data);
					setTimeout(function () {
						$('#message').fadeTo(1000, 0);
					}, 20000);

					DClose();
				}
			});

		});

	</script>
	<?php
	exit();
}

if ($action == '') {
	?>

	<h2>&nbsp;Раздел: "<?php echo $fieldsNames['client']['clientpath'];?>"</h2>
	<h3 class="gray-dark fs-09 pl10">Стандарт: Источник клиента</h3>

	<form id="list">

		<TABLE id="catlist">
			<thead class="hidden-iphone sticked--top">
			<TR class="th40">
				<th class="w50 text-center"></th>
				<th class="w350 text-center"><b>Название</b></th>
				<th class="w120 text-center"></th>
				<th class="w150">Метка</th>
				<th class="w150">Номер</th>
				<th></th>
			</TR>
			</thead>
			<?php
			$i      = 0;
			$result = $db -> getAll("SELECT * FROM {$sqlname}clientpath WHERE identity = '$identity' ORDER BY name");
			foreach ($result as $data) {

				$df = ($data['isDefault'] == 'yes') ? ' [ <b class="red">По-умолчанию</b> ]' : '';
				?>
				<TR class="ha th40">
					<TD class="text-center">
						<input type="checkbox" onclick="chbCheck()" class="mm" name="multi[<?= $i ?>]" id="multi[<?= $i ?>]" value="<?= $data['id'] ?>">
					</TD>
					<TD>
						<label for="multi[<?= $i ?>]" onclick="chbCheck()">
						<span style="line-height: 25px;" class="Bold fs-11"><span class="gray2">ID <?= $data['id'] ?>:</span> <?= $data['name'] ?><?= $df ?></span>
						</label>
					</TD>
					<TD class="text-center">

						<A href="javascript:void(0)" onclick="doLoad('/content/admin/<?php echo $thisfile; ?>?id=<?= $data['id'] ?>&action=edit')" class="button dotted bluebtn"><i class="icon-pencil"></i></A>
						<A href="javascript:void(0)" onclick="doLoad('/content/admin/<?php echo $thisfile; ?>?id=<?= $data['id'] ?>&action=delete')" class="button dotted redbtn"><i class="icon-cancel-circled"></i></A>

					</TD>
					<TD><?= $data['utm_source'] ?></TD>
					<TD><?= $data['destination'] ?></TD>
					<TD></TD>
				</TR>
				<?php
				$i++;
			}
			?>
		</TABLE>

	</form>

	<div class="button--group1 box--child" style="position: fixed; bottom: 40px; left: 380px; z-index: 100;">

		<a href="javascript:void(0)" onclick="doLoad('/content/admin/<?php echo $thisfile; ?>?action=edit');" class="button bluebtn box-shadow" title="Добавить"><i class="icon-plus-circled"></i>Добавить</a>
		<a href="javascript:void(0)" onclick="multidel()" class="button redbtn box-shadow hidden amultidel" title="Удалить"><i class="icon-minus-circled"></i>Удалить выбранное</a>
		<a href="javascript:void(0)" onclick="clearCheck()" class="button greenbtn box-shadow hidden amultidel" title="Снять выделение"><i class="icon-th"></i>Снять выделение</a>

	</div>

	<div class="pagerefresh refresh--icon admn red" onclick="doLoad('/content/admin/<?php echo $thisfile; ?>?action=edit');" title="Добавить"><i class="icon-plus-circled"></i></div>
	<div class="pagerefresh refresh--icon admn orange" onclick="openlink('https://salesman.pro/docs/18')" title="Документация"><i class="icon-help"></i></div>

	<div class="space-100"></div>

	<script>

		function chbCheck() {

			var col = $('#catlist input:checkbox:checked').length;

			if (col > 0) $('.amultidel').removeClass('hidden');
			else $('.amultidel').addClass('hidden');

		}

		function clearCheck() {

			$('#catlist input:checkbox:checked').prop('checked', false);
			$('.amultidel').addClass('hidden');

		}

		function multidel() {

			var str = $('#list').serialize();
			doLoad('/content/admin/<?php echo $thisfile; ?>?action=delete&' + str);

		}

	</script>
<?php } ?>