<?php
/* ============================ */
/*         SalesMan CRM         */
/* ============================ */
/* (C) 2016 Vladislav Andreev   */
/*       SalesMan Project       */
/*        www.isaler.ru         */
/*        ver. 2017.x           */
/* ============================ */

error_reporting(0);

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

if ($action == "edit_on") {

	$user_id  = rij_crypt($_REQUEST['user_id'], $skey, $ivc);
	$user_key = rij_crypt($_REQUEST['user_key'], $skey, $ivc);

	try {

		$db -> query("update ".$sqlname."services set user_id = '".$user_id."', user_key = '".$user_key."' where id = '".$id."' and identity = '$identity'");

		print "Сделано";

	}
	catch (Exception $e) {
		echo $e -> getMessage();
	}

	unlink($rootpath."/cash/".$fpath."settings.all.json");

	exit();

}

if (!$action) {
	?>
	<h2>&nbsp;Раздел: "Интеграция с сервисами рассылок"</h2>

	<table id="zebra">
		<thead class="hidden-iphone sticked--top">
		<tr class="" height="30">
			<th width="160" align="center">Сервис</th>
			<th width="120" align="center">ID пользователя</th>
			<th width="160" align="center">Ключ API</th>
			<th width="100" align="center">Действия</th>
			<th></th>
		</tr>
		</thead>
		<tbody>
		<?php
		$result = $db -> getAll("SELECT * FROM ".$sqlname."services WHERE tip = 'mail' and identity = '$identity' ORDER by name");
		foreach ($result as $data) {
			?>
			<tr class="ha" height="40">
				<td align="left" class="Bold"><?= $data['name'] ?></td>
				<td align="left"><?= rij_decrypt($data['user_id'], $skey, $ivc); ?></td>
				<td align="left">***********</td>
				<td width="50" align="center">
					<a href="javascript:void(0)" onclick="doLoad('content/admin/<?php echo $thisfile; ?>?action=edit&id=<?= $data['id'] ?>');" title="Редактировать"><i class="icon-pencil blue"></i></a>
				</td>
				<td></td>
			</tr>
		<?php } ?>
		</tbody>
	</table>
<?php
}
if ($action == "edit") {
	$result   = $db -> getRow("SELECT * FROM ".$sqlname."services where id = '".$_REQUEST['id']."' and identity = '$identity'");
	$user_id  = rij_decrypt($result["user_id"], $skey, $ivc);
	$user_key = rij_decrypt($result["user_key"], $skey, $ivc);
	?>
	<div class="zagolovok">Изменить параметры</div>
	<form method="post" action="content/admin/<?php echo $thisfile; ?>" enctype="multipart/form-data" name="form" id="form" autocomplete="off">
		<input name="action" type="hidden" value="edit_on"/>
		<input name="id" type="hidden" value="<?= $_REQUEST['id'] ?>"/>
		<div style="max-height:300px; overflow:auto !important">
			<table width="100%" border="0" cellspacing="2" cellpadding="2">
				<tr>
					<td width="70"><b>Аккаунт:</b></td>
					<td><input type="text" id="user_id" name="user_id" style="width:99%" value="<?= $user_id ?>"></td>
				</tr>
				<tr>
					<td><b>Ключ к API:</b><sup class="red">*</sup></td>
					<td><input type="text" id="user_key" name="user_key" style="width:99%" value="<?= $user_key ?>">
					</td>
				</tr>
			</table>
		</div>
		<br>
		<hr/>
		<div align="right">
			<a href="javascript:void(0)" onclick="$('#form').trigger('submit')" class="button">Выполнить</a>&nbsp;
			<a href="javascript:void(0)" onclick="DClose()" class="button"><span>Отмена</span></a>
		</div>
		<div class="setSaveButton">
			<a href="<?= $productInfo['site'] ?>/docs/58" target="blank" title="Документация"><i class="icon-help white icon-2x"></i></a>
		</div>
	</form>
	<script type="text/javascript">

		$(function () {
			$('#dialog').css('width', '502px');
			$('#form').ajaxForm({
				beforeSubmit: function () {

					var $out = $('#message');
					var em = checkRequired();

					if (!em)
						return false;

					$('#dialog').css('display', 'none');
					$('#dialog_container').css('display', 'none');

					$out.css('display', 'block').append('<div id=loader><img src=/assets/images/loader.gif> Загрузка данных. Пожалуйста подождите...</div>');

					return true;

				},
				success: function (data) {

					$('#contentdiv').load('content/admin/<?php echo $thisfile; ?>');

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
}
?>