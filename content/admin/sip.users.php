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
$iduser = (int)$_REQUEST['iduser'];

if ($action == "edit_on") {

	$phone    = $_REQUEST['phone'];
	$phone_in = $_REQUEST['phone_in'];

	$db -> query("update ".$sqlname."user set phone = '".$phone."', phone_in = '".$phone_in."' where iduser = '".$iduser."' and identity = '$identity'");
	print 'Сделано';

	exit();

}

if ($action == "edit") {

	$result   = $db -> getRow("SELECT * FROM ".$sqlname."user where iduser='".$iduser."' and identity = '$identity'");
	$phone    = $result["phone"];
	$phone_in = $result["phone_in"];
	?>
	<form method="post" action="/content/admin/<?php echo $thisfile; ?>" enctype="multipart/form-data" name="form" id="form" autocomplete="off">
		<input type="hidden" id="action" name="action" value="edit_on">
		<input type="hidden" id="iduser" name="iduser" value="<?= $iduser ?>">
		<div class="zagolovok">Редактирование</div>
		<table width="100%" border="0" align="center" cellpadding="5" cellspacing="0">
			<tr>
				<td width="120" align="left">Мобильный номер:</td>
				<td><input name="phone" type="text" id="phone" value="<?= $phone ?>" style="width:90%"></td>
			</tr>
			<tr>
				<td align="left">Внутр.номер:</td>
				<td><input name="phone_in" type="text" id="phone_in" value="<?= $phone_in ?>" style="width:90%"></td>
			</tr>
		</table>
		<hr>
		<div class="button--pane text-right">
			<A href="javascript:void(0)" onclick="$('#form').trigger('submit')" class="button">Сохранить</A>&nbsp;
			<A href="javascript:void(0)" onclick="DClose()" class="button">Отмена</A>
		</div>
	</form>
	<?php
}

if ($_REQUEST['action'] == '') {
	?>

	<h2>&nbsp;Раздел: "Телефония. номера сотрудников"</h2>

	<div class="infodiv mb10">В этом разделе необходимо указать внутренние номера сотрудников, для обеспечения функциональности работы модуля.</div>

	<table id="table-1">
		<thead class="hidden-iphone sticked--top">
		<tr class="th40">
			<th class="w350 text-left">Сотрудник</th>
			<Th class="w200 text-center">Вн.номер</Th>
			<th class="w150 text-center">Моб.</th>
			<th class="w100 text-center">Действия</th>
			<th></th>
		</tr>
		</thead>
		<tbody>
		<?php
		$s1     = $s2 = '';
		$result = $db -> getAll("SELECT * FROM ".$sqlname."user WHERE identity = '$identity' ORDER by title");
		foreach ($result as $data) {

			if ($data['avatar']) $avatar = "/cash/avatars/".$data['avatar'];
			else $avatar = "/assets/images/noavatar.png";

			if ($data['secrty'] == 'yes')
				$s1 .= '
					<tr class="ha th45">
						<td>
							<div class="avatar--mini pull-left mr10" style="background: url('.$avatar.'); background-size:cover;" title="'.$data['title'].'"></div>
							<div class="fs-11 Bold">'.$data['title'].'</div>
							<div class="fs-07 gray2">'.$data['tip'].'</div>
						</td>
						<td>'.$data['phone_in'].'</td>
						<td>'.$data['phone'].'</td>
						<td class="text-center"><a href="javascript:void(0)" onclick="doLoad(\'/content/admin/'.$thisfile.'?action=edit&iduser='.$data['iduser'].'\');"><i class="icon-pencil blue"></i></a></td>
						<td></td>
					</tr>
				';
			else
				$s2 .= '
					<tr class="ha graybg-sub gray2 th45" title="Не активен">
						<td>
							<div class="fs-11 Bold"><i class="icon-lock red"></i>'.$data['title'].'</div>
							<div class="fs-07 gray2">'.$data['tip'].'</div>
						</td>
						<td>'.$data['phone_in'].'</td>
						<td>'.$data['phone'].'</td>
						<td class="text-center"><a href="javascript:void(0)" onclick="doLoad(\'/content/admin/'.$thisfile.'?action=edit&iduser='.$data['iduser'].'\');"><i class="icon-pencil blue"></i></a></td>
						<td></td>
					</tr>
				';

		}

		print $s1.$s2;
		?>
		</tbody>
	</table>

	<div class="space-100"></div>
	<?php
}
?>
<script>

	$('#dialog').css('width', '502px');

	$(function () {
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

				//$("#contentdiv").load('content/admin/<?php echo $thisfile; ?>');
				razdel(hash);

				$('#message').fadeTo(1, 1).css('display', 'block').html(data);
				setTimeout(function () {
					$('#message').fadeTo(1000, 0);
				}, 20000);

				DClose();

			}
		});
	});

	$(function () {

		$(".multiselect").multiselect({sortable: true, searchable: true});
		$(".connected-list").css('height', "150px");

	});

</script>