<?php
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
include $rootpath."/developer/events.php";
include $rootpath."/inc/language/".$language.".php";

$thisfile = basename( __FILE__ );

$action  = $_REQUEST['action'];
$service = $_REQUEST['service'];

if ( $action == "addgroup" ) {

	$approve = "'Unisender','Smartresponder'";//сервисы, которые поддерживают добавление групп
	?>
	<div class="zagolovok">Добавить группу</div>
	<form method="post" action="/modules/group/core.group.php" enctype="multipart/form-data" name="form" id="form" autocomplete="off">
		<input name="action" type="hidden" value="addgroup"/>
		<input name="service" type="hidden" value="0"/>

		<table width="100%" border="0" cellspacing="2" cellpadding="2">
			<tr>
				<td width="105">
					<div class="fpole">Название:</div>
				</td>
				<td><input type="text" id="name" name="name" value="" style="width:99%"></td>
			</tr>
		</table>

		<hr>

		<div align="right">

			<a href="javascript:void(0)" onclick="$('#form').trigger('submit')" class="button">Сохранить</a>&nbsp;
			<a href="javascript:void(0)" onClick="DClose()" class="button">Отмена</a>

		</div>
	</form>
	<script type="text/javascript">

		$(function () {

			$('#dialog').css('width', '502px');

			$('#form').ajaxForm({
				beforeSubmit: function () {

					var $out = $('#message');
					var em = checkRequired();

					if (em === false) return false;

					$('#dialog').css('display', 'none');
					$('#dialog_container').css('display', 'none');

					$out.empty().css('display', 'block').append('<div id="loader"><img src="/assets/images/loading.gif"> Загрузка данных. Пожалуйста подождите...</div>');

					return true;

				},
				success: function (data) {
					$('#dialog').css('display', 'none');
					$('#resultdiv').empty();
					$('#dialog_container').css('display', 'none');

					configpage();

					$('#message').fadeTo(1, 1).css('display', 'block').html(data);
					setTimeout(function () {
						$('#message').fadeTo(1000, 0);
					}, 20000);
				}
			});

			$('#dialog').center();

		});
	</script>
	<?php

}
if ( $action == "editgroup" ) {

	$name = $db -> getOne( "SELECT name FROM ".$sqlname."group where id = '".$_REQUEST['id']."' and identity = '$identity'" );
	?>
	<div class="zagolovok">Изменить группу</div>
	<form method="post" action="/modules/group/core.group.php" enctype="multipart/form-data" name="form" id="form" autocomplete="off">
		<input name="action" type="hidden" value="editgroup"/>
		<input name="group" type="hidden" value="<?= $_REQUEST['id'] ?>"/>
		<table width="100%" border="0" cellspacing="2" cellpadding="2">
			<tr>
				<td width="125">
					<div class="fpole">Название группы:</div>
				</td>
				<td><input type="text" id="name" name="name" value="<?= $name ?>" style="width:99%"></td>
			</tr>
		</table>
		<hr>
		<div align="right">
			<a href="javascript:void(0)" onclick="$('#form').trigger('submit')" class="button">Сохранить</a>&nbsp;
			<a href="javascript:void(0)" onClick="DClose()" class="button">Отмена</a>
		</div>
	</form>
	<script>

		$(function () {
			$('#dialog').css('width', '502px');
			$('#form').ajaxForm({
				beforeSubmit: function () {

					var $out = $('#message');
					var em = checkRequired();

					if (em === false) return false;

					$('#dialog').css('display', 'none');
					$('#dialog_container').css('display', 'none');

					$out.empty().css('display', 'block').append('<div id="loader"><img src="/assets/images/loading.gif"> Загрузка данных. Пожалуйста подождите...</div>');

					return true;

				},
				success: function (data) {
					$('#dialog').css('display', 'none');
					$('#resultdiv').empty();
					$('#dialog_container').css('display', 'none');

					configpage();

					$('#message').fadeTo(1, 1).css('display', 'block').html(data);
					setTimeout(function () {
						$('#message').fadeTo(1000, 0);
					}, 20000);
				}

			});

			$('#dialog').center();

		});
	</script>
	<?php
}
if ( $action == "importgroup" ) {
	?>
	<div class="zagolovok">Импорт групп рассылки из сервиса</div>
	<form method="post" action="/modules/group/core.group.php" enctype="multipart/form-data" name="form" id="form" autocomplete="off">
		<input name="action" type="hidden" value="importgroup"/>
		<table width="100%" border="0" cellspacing="2" cellpadding="2">
			<tr>
				<td width="115">
					<div class="fpole">Выбор сервиса:</div>
				</td>
				<td>
					<select name="service" id="service" class="required" style="width:99%">
						<option value="none">--Выбор--</option>
						<?php
						$result = $db -> query( "SELECT * FROM ".$sqlname."services where tip='mail' and user_key != '' and identity = '$identity' ORDER by name" );
						while ($data = $db -> fetch( $result )) {
							?>
							<option value="<?= $data['folder'] ?>"><?= $data['name'] ?></option>
						<?php } ?>
					</select>
				</td>
			</tr>
		</table>
		<hr>
		<div align="right">
			<a href="javascript:void(0)" onclick="$('#form').trigger('submit')" class="button">Получить</a>&nbsp;
			<a href="javascript:void(0)" onClick="DClose()" class="button">Отмена</a>
		</div>
	</form>
	<script>
		$(function () {
			$('#dialog').css('width', '502px');

			$('#form').ajaxForm({
				beforeSubmit: function () {

					var $out = $('#message');
					var em = checkRequired();

					if (em === false) return false;

					$('#dialog').css('display', 'none');
					$('#dialog_container').css('display', 'none');

					$out.empty().fadeTo(1, 1).css('display', 'block').append('<div id="loader"><img src="/assets/images/loading.gif"> Загрузка данных. Пожалуйста подождите...</div>');

					return true;

				},
				success: function (data) {
					$('#dialog').css('display', 'none');
					$('#resultdiv').empty();
					$('#dialog_container').css('display', 'none');

					$('#message').fadeTo(1, 1).css('display', 'block').html(data);
					setTimeout(function () {
						$('#message').fadeTo(1000, 0);
					}, 20000);

					configpage();
				}
			});
			$('#dialog').center();


		});
	</script>
	<?php
	exit();
}
if ( $action == "deletegroup" ) {

	$result      = $db -> getRow( "SELECT * FROM ".$sqlname."group where id='".$_REQUEST['group']."' and identity = '$identity'" );
	$serviceName = $result["service"];
	$groupName   = $result["name"];

	$mess = '<strong class="red">Внимание!</strong> В результате данной операции будет удалена группа <b>'.$groupName.'</b> и все её подписчики, записанные в CRM.';
	?>
	<div class="zagolovok">Удаление группы</div>
	<form method="post" action="/modules/group/core.group.php" enctype="multipart/form-data" name="form" id="form" autocomplete="off">
		<input name="action" type="hidden" value="deletegroup"/>
		<input name="group" type="hidden" value="<?= $_REQUEST['id'] ?>"/>
		<table width="100%" border="0" cellspacing="2" cellpadding="2">
			<tr>
				<td><?= $mess ?></td>
			</tr>
		</table>
		<hr>
		<div align="right">
			<a href="javascript:void(0)" onclick="$('#form').trigger('submit')" class="button">Выполнить</a>&nbsp;
			<a href="javascript:void(0)" onclick="DClose()" class="button">Отмена</a>
		</div>
	</form>
	<script>

		$(function () {

			$('#dialog').css('width', '502px');

			$('#form').ajaxForm({
				beforeSubmit: function () {

					var $out = $('#message');
					var em = checkRequired();

					if (em === false) return false;

					$('#dialog').css('display', 'none');
					$('#dialog_container').css('display', 'none');

					$out.empty().css('display', 'block').append('<div id="loader"><img src="/assets/images/loader.gif"> Загрузка данных. Пожалуйста подождите...</div>');

					return true;

				},
				success: function (data) {

					$('#dialog').css('display', 'none');
					$('#resultdiv').empty();
					$('#dialog_container').css('display', 'none');

					configpage();

					$('#message').fadeTo(1, 1).css('display', 'block').html(data);
					setTimeout(function () {
						$('#message').fadeTo(1000, 0);
					}, 20000);
				}
			});
			$('#dialog').center();

		});
	</script>
	<?php
}

if ( $action == "addtoGroup" ) {
	?>
	<div class="zagolovok">Добавить в группу</div>
	<form method="post" action="/modules/group/core.group.php" enctype="multipart/form-data" name="form" id="form" autocomplete="off">
		<input name="action" type="hidden" value="addtoGroup"/>
		<input name="clid" type="hidden" value="<?= $_REQUEST['clid'] ?>"/>
		<input name="pid" type="hidden" value="<?= $_REQUEST['pid'] ?>"/>

		<div style="max-height:calc(70vh - 200px); overflow:auto !important">

			<table width="100%" border="0" cellspacing="0" cellpadding="3" class="rowtable">
				<thead class="sticked--top">
				<tr height="30" class="header_contaner">
					<th width=""><b>Группа</b></th>
					<th width="120"></th>
					<th width="80"></th>
				</tr>
				</thead>
				<?php
				$day    = current_datum();
				$result = $db -> query( "SELECT * FROM ".$sqlname."group WHERE identity = '$identity' ORDER by name" );
				while ($data = $db -> fetch( $result )) {

					$todaySub = $db -> getOne( "SELECT COUNT(*) as count FROM ".$sqlname."grouplist WHERE gid = '".$data['id']."' and datum between '".$day." 00:00:00' and '".$day." 23:59:59' and identity = '$identity'" );

					$total = $db -> getOne( "SELECT COUNT(*) as count FROM ".$sqlname."grouplist WHERE gid = '".$data['id']."' and identity = '$identity'" );
					?>
					<tr class="ha" height="30">
						<td width="">
							<label class="block wp100 p10 Bold blue">
								<div class="fs-12 ellipsis">
									<input type="checkbox" name="id[]" id="id[]" value="<?= $data['id'] ?>">&nbsp;<?= $data['name'] ?>
								</div>
							</label>
						</td>
						<td width="120" align="left">
							<div class="noBold fs-09 gray2 ml20 p0 pl5"><?= $data['service'] ?></div>
						</td>
						<td width="80" align="left">

							<div class="p5">
								<span title="Число добавленных сегодня"><?= $todaySub ?></span> /
								<span title="Всего в группе"><?= intval( $total ) ?></span>
							</div>

						</td>
					</tr>
					<?php
				}
				?>
			</table>

		</div>

		<hr>

		<div class="p5">
			<b>Метки:</b><br>
			<textarea name="tags" id="tags" rows="2" style="width: 98%;"></textarea>
			<div class="fs-09 gray">Укажите метки, которые хотите добавить к записи. Разделяйте запятыми.</div>
		</div>

		<hr>

		<div align="right">

			<a href="javascript:void(0)" onclick="$('#form').trigger('submit')" class="button">Выполнить</a>&nbsp;
			<a href="javascript:void(0)" onClick="DClose()" class="button">Отмена</a>

		</div>
	</form>
	<script>
		$(function () {

			$('#dialog').css('width', '702px');

			$('#form').ajaxForm({
				beforeSubmit: function () {

					var $out = $('#message');
					var em = checkRequired();

					if (em === false) return false;

					$('#dialog').css('display', 'none');
					$('#dialog_container').css('display', 'none');

					$out.empty().fadeTo(1, 1).css('display', 'block').append('<div id="loader"><img src="/assets/images/loading.gif"> Загрузка данных. Пожалуйста подождите...</div>');

					return true;

				},
				success: function (data) {

					$('#dialog').css('display', 'none');
					$('#resultdiv').empty();
					$('#dialog_container').css('display', 'none');

					var str = '';

					if ($('#ctitle #isCard').val() == 'yes') {

						var clid = parseInt($('#ctitle #clid').val());
						var pid = parseInt($('#ctitle #pid').val());

						if (clid != 'undefined' && clid > 0) str = str + '&clid=' + clid;
						if (pid != 'undefined' && pid > 0) str = str + '&pid=' + pid;

					}

					if ($('#isCard').val() == 'yes') {
						$('#tabgroup').load('/content/card/card.group.php?clid=' + clid + '&pid=' + pid);
					}
					else configpage();

					$('#message').fadeTo(1, 1).css('display', 'block').html(data);
					setTimeout(function () {
						$('#message').fadeTo(1000, 0);
					}, 20000);

				}
			});

			$('#dialog').center();

		});
	</script>
	<?php
}
if ( $action == "removefromGroup" ) {

	$gid = $_REQUEST['gid'];

	?>
	<div class="zagolovok">Отписать от рассылки / Удалить из группы</div>
	<form method="post" action="/modules/group/core.group.php" enctype="multipart/form-data" name="form" id="form" autocomplete="off">
		<input name="action" type="hidden" value="removefromGroup"/>
		<input name="id" type="hidden" value="<?= $_REQUEST['id'] ?>"/>
		<input name="gid" type="hidden" value="<?= $gid ?>"/>
		<div style="max-height:300px; overflow:auto !important" align="center">
			<br>Вы действительно хотите отписать Пользователя от данной рассылки?<br>
		</div>
		<hr>
		<div align="right">
			<a href="javascript:void(0)" onclick="$('#form').trigger('submit')" class="button">Да</a>&nbsp;
			<a href="javascript:void(0)" onClick="DClose()" class="button">Нет</a>
		</div>
	</form>
	<script>
		$(function () {

			$('#dialog').css('width', '502px');

			$('#form').ajaxForm({
				beforeSubmit: function () {

					var $out = $('#message');
					var em = checkRequired();

					if (em === false) return false;

					$('#dialog').css('display', 'none');
					$('#dialog_container').css('display', 'none');

					$out.empty().fadeTo(1, 1).css('display', 'block').append('<div id="loader"><img src="/assets/images/loading.gif"> Загрузка данных. Пожалуйста подождите...</div>');

					return true;

				},
				success: function (data) {
					$('#dialog').css('display', 'none');
					$('#resultdiv').empty();
					$('#dialog_container').css('display', 'none');

					configpage();

					$('#message').fadeTo(1, 1).css('display', 'block').html(data);
					setTimeout(function () {
						$('#message').fadeTo(1000, 0);
					}, 20000);
				}
			});
			$('#dialog').center();

		});
	</script>
	<?php
}

if ( $action == "exportlists" ) {

	if ( $_REQUEST['url'] == 'client' ) {
		$clid = $_REQUEST['id'];
	}
	if ( $_REQUEST['url'] == 'person' ) {
		$pid = $_REQUEST['id'];
	}
	?>
	<div class="zagolovok">Массовое добавление в группу</div>
	<form method="post" action="/modules/group/core.group.php" enctype="multipart/form-data" name="form" id="form" autocomplete="off">
		<input name="action" type="hidden" value="exportlists"/>
		<input name="clid" id="clid" type="hidden" value="<?= $clid ?>"/>
		<input name="pid" id="pid" type="hidden" value="<?= $pid ?>"/>
		<br>
		<strong>Выбор типа группы:</strong><br>
		<select name="service" id="service" class="required" onChange="getServices()" style="width:99%">
			<option value="">Группа CRM</option>
			<?php
			$result = $db -> query( "SELECT * FROM ".$sqlname."services where tip='mail' and user_key != '' and identity = '$identity' ORDER by name" );
			while ($data = $db -> fetch( $result )) {
				?>
				<option value="<?= $data['name'] ?>"><?= $data['name'] ?></option>
			<?php } ?>
		</select>
		<hr>
		<div id="gglist"></div>
		<strong>Метки:</strong><br>
		<textarea name="tags" id="tags" rows="2" style="width: 98%;"></textarea>
		<div class="smalltxt">Укажите метки, которые хотите добавить к записи. Разделяйте запятыми.</div>
		<hr>
		<div class="smalltxt">
			<strong class="red">Примечание:</strong> если группа является частью сервиса рассылок, то произойдет подписка выбранных клиентов в указанную группу<strong> при наличии Email</strong>. Записи без Email будут игнорированы. Скорее всего, сервис отправит объекту ссылку для подтверждения подписки.
		</div>
		<hr/>
		<div align="right">
			<a href="javascript:void(0)" onclick="$('#form').trigger('submit')" class="button">Выполнить</a>&nbsp;
			<a href="javascript:void(0)" onClick="DClose()" class="button">Отмена</a>
		</div>
	</form>
	<script>
		$(function () {

			$('#dialog').css('width', '602px');

			getServices();

			$('#form').ajaxForm({
				beforeSubmit: function () {

					var $out = $('#message');
					var em = checkRequired();

					if (em === false) return false;

					$('#dialog').css('display', 'none');
					$('#dialog_container').css('display', 'none');

					$out.fadeTo(1, 1).css('display', 'block').append('<div id="loader"><img src="/assets/images/loading.gif"> Загрузка данных. Пожалуйста подождите...</div>');

					return true;

				},
				success: function (data) {
					$('#dialog').css('display', 'none');
					$('#resultdiv').empty();
					$('#dialog_container').css('display', 'none');
					$('#ch').attr('checked', false);

					$('#message').fadeTo(1, 1).css('display', 'block').html(data);
					setTimeout(function () {
						$('#message').fadeTo(1000, 0);
					}, 20000);

					configpage();
				}
			});
			$('#dialog').center();

		});

		function getServices() {

			var name = $('#service option:selected').val();

			$('#gglist').load('/modules/group/form.group.php?action=getServices&service=' + name).append('<div id="loader"><img src="/assets/images/loading.gif"> Загрузка данных. Пожалуйста подождите...</div>');

		}
	</script>
	<?php
}
if ( $action == "importlists" ) {

	$gid = $_REQUEST['gid'];

	?>
	<div class="zagolovok">Массовый импорт в CRM из выбранного сервиса</div>
	<form method="post" action="/modules/group/core.group.php" enctype="multipart/form-data" name="form" id="form" autocomplete="off">
		<input name="action" type="hidden" value="importlists"/>
		<input name="gid" type="hidden" value="<?= $gid ?>">
		<strong>Выбор сервиса:</strong>
		<select name="service" id="service" class="required" onChange="getGroup()" style="width:80%">
			<option value="">--не выбрано--</option>
			<?php
			$result = $db -> query( "SELECT * FROM ".$sqlname."services where tip='mail' and user_key != '' and identity = '$identity' ORDER by name" );
			while ($data = $db -> fetch( $result )) {
				?>
				<option value="<?= $data['name'] ?>"><?= $data['name'] ?></option>
			<?php } ?>
		</select>
		<hr>
		<div id="gglist"></div>
		<hr>
		<div align="right">
			<a href="javascript:void(0)" onclick="$('#form').trigger('submit')" class="button">Выполнить</a>&nbsp;
			<a href="javascript:void(0)" onClick="DClose()" class="button">Отмена</a>
		</div>
	</form>
	<script>
		$(function () {

			$('#dialog').css('width', '602px');

			$('#form').ajaxForm({
				beforeSubmit: function () {

					var $out = $('#message');
					var em = checkRequired();

					if (em === false) return false;

					$('#dialog').css('display', 'none');
					$('#dialog_container').css('display', 'none');

					$out.empty().fadeTo(1, 1).css('display', 'block').append('<div id="loader"><img src="/assets/images/loader.gif"> Загрузка данных. Пожалуйста подождите...</div>');

					return true;

				},
				success: function (data) {
					$('#dialog').css('display', 'none');
					$('#resultdiv').empty();
					$('#dialog_container').css('display', 'none');

					$('#message').fadeTo(1, 1).css('display', 'block').html(data);
					setTimeout(function () {
						$('#message').fadeTo(1000, 0);
					}, 20000);
					configpage();
				}
			});
			$('#dialog').center();

		});

		function getGroup() {
			var name = $('#service option:selected').val();
			$('#gglist').append('<div id="loader"><img src="/assets/images/loading.gif"> Загрузка данных. Пожалуйста подождите...</div>');
			$.get('/modules/group/form.group.php?action=getGroup&service=' + name, function (data) {
				$('#gglist').html(data);
			})
				.done(function () {
					$('#dialog').center();
				});
		}
	</script>
	<?php
}

if ( $action == "getServices" ) {

	$services = $_REQUEST['services'];

	?>
	<table width="100%" border="0" cellspacing="2" cellpadding="2">
		<tr height="30">
			<th class="cherta"><b>Группа</b></th>
		</tr>
	</table>
	<div style="max-height:100px; overflow:auto !important"><br>
		<table width="100%" border="0" cellspacing="2" cellpadding="2">
			<?php
			$day = current_datum();
			//print "SELECT * FROM ".$sqlname."group WHERE service = '".$service."' ORDER by name";
			$result = $db -> query( "SELECT * FROM ".$sqlname."group WHERE service = '".$service."' and identity = '$identity' ORDER by name" );
			while ($data = $db -> fetch( $result )) {

				$todaySub = $db -> getOne( "SELECT COUNT(*) FROM ".$sqlname."grouplist WHERE service = '".$data['name']."' and datum between '".$day." 00:00:00' and '".$day." 23:59:59' and identity = '$identity'" );

				?>
				<tr class="ha">
					<td>
						<span class="ellipsis"><label><input type="checkbox" name="id[]" id="id[]" value="<?= $data['id'] ?>">&nbsp;<?= $data['name'] ?></label></span>
					</td>
				</tr>
				<?php
				$todaySub = '';
			} ?>
		</table>
	</div>
	<hr><br>
	<?php
	exit();
}

if ( $action == "mass" ) {

	$id  = (array)$_REQUEST['ch'];
	$sel = implode( ";", (array)$id );
	$kol = count( $id );

	$gid = $_REQUEST['gid'];

	if ( $gid > 0 ) {

		$sort .= " and gid='".$gid."'";
	}

	$count = $db -> getOne( "SELECT COUNT(*) as count FROM ".$sqlname."grouplist where id > 0 ".$sort." and identity = '$identity'" );
	?>
	<div class="zagolovok"><b>Групповое действие</b></div>
	<form action="/modules/group/core.group.php" id="Form" name="Form" method="post" enctype="multipart/form-data">
		<input name="ids" id="ids" type="hidden" value="<?= $sel ?>"/>
		<input name="gid" id="gid" type="hidden" value="<?= $gid ?>"/>
		<input name="action" id="action" type="hidden" value="mass"/>
		<div id="profile">
			<table id="bborder">
				<tr>
					<td><b>Действие с записями:</b></td>
					<td>
						<select name="doAction" id="doAction" style="width: auto;" onchange="showd()">
							<option value="">--выбор--</option>
							<!--<option value="pSync">Сопоставить с базой клиентов</option>-->
							<option value="pMove">Переместить в Группу</option>
							<option value="pCopy">Скопировать в Группу</option>
							<!--<option value="pDele">Удалить из Сервиса и Группы</option>-->
							<option value="pDeleC">Удалить только из Группы</option>
						</select>
					</td>
				</tr>
				<tr class="hidden" id="cattt">
					<td><b>Группа:</b></td>
					<td>
						<select name="newgid" id="newgid" style="width: 99.7%;">
							<option value="">--выбор--</option>
							<?php
							print '<optgroup label="Группа CRM"></optgroup>';
							$result = $db -> query( "SELECT * FROM ".$sqlname."group WHERE service = '' and identity = '$identity'" );
							while ($data_array = $db -> fetch( $result )) {
								print '<option value="'.$data_array['id'].'">&nbsp;&nbsp;'.$data_array['name'].'</option>';
							}
							$resultt = $db -> query( "SELECT * FROM ".$sqlname."services WHERE user_key != '' and tip = 'mail' and identity = '$identity'" );
							while ($data = $db -> fetch( $resultt )) {
								print '<optgroup label="'.$data['name'].'"></optgroup>';
								$result = $db -> query( "SELECT * FROM ".$sqlname."group WHERE service = '".$data['name']."' and identity = '$identity'" );
								while ($data_array = $db -> fetch( $result )) {
									print '<option value="'.$data_array['id'].'">&nbsp;&nbsp;'.$data_array['name'].'</option>';
								}
							}
							?>
						</select>
						<div class="infodiv">Позиции будут перемещены в выбранную группу</div>
					</td>
				</tr>
				<tr class="hidden" id="copyt">
					<td><b>Группа:</b></td>
					<td>
						<select name="cgid" id="cgid" style="width: 99.7%;">
							<option value="">--выбор--</option>
							<?php
							print '<optgroup label="Группа CRM"></optgroup>';
							$result = $db -> query( "SELECT * FROM ".$sqlname."group WHERE service = '' and identity = '$identity'" );
							while ($data_array = $db -> fetch( $result )) {
								print '<option value="'.$data_array['id'].'">&nbsp;&nbsp;'.$data_array['name'].'</option>';
							}
							$resultt = $db -> query( "SELECT * FROM ".$sqlname."services WHERE user_key != '' and tip = 'mail' and identity = '$identity'" );
							while ($data = $db -> fetch( $resultt )) {
								print '<optgroup label="'.$data['name'].'"></optgroup>';
								$result = $db -> query( "SELECT * FROM ".$sqlname."group WHERE service = '".$data['name']."' and identity = '$identity'" );
								while ($data_array = $db -> fetch( $result )) {
									print '<option value="'.$data_array['id'].'">&nbsp;&nbsp;'.$data_array['name'].'</option>';
								}
							}
							?>
						</select>
						<div class="infodiv">Позиции будут скопированы в выбранную группу</div>
					</td>
				</tr>
				<tr class="hidden" id="synct">
					<td colspan="2">
						<div class="infodiv">Записи группы будут сопоставлены с записями в БД по полю
							<b class="blue">email</b> - если подписчик найден в базе клиентов, то он будет привязан к конкретному Клиенту или Контакту
						</div>
					</td>
				</tr>
				<tr class="hidden" id="delt">
					<td colspan="2">
						<div class="infodiv">Выбранные записи будут удалены как из CRM так и из сервиса подписок, если она привязана к сервису.</div>
					</td>
				</tr>
				<tr class="hidden" id="deltc">
					<td colspan="2">
						<div class="infodiv">Выбранные записи будут удалены из CRM</div>
					</td>
				</tr>
				<tr>
					<td width="160"><b>Выполнить для записей:</b></td>
					<td>
						<label><input name="isSelect" id="isSelect" value="doSel" type="radio" <?php if ( $kol > 0 )
								print "checked"; ?>>&nbsp;Выбранное (<b class="blue"><?= $kol ?></b>)</label>
						<label><input name="isSelect" id="isSelect" value="doAll" type="radio" <?php if ( $kol == 0 )
								print "checked"; ?>>&nbsp;Со всех страниц (<b class="blue"><?= $count ?></b>)</label>
					</td>
				</tr>
			</table>
		</div>
		<hr>
		<div align="right">
			<a href="javascript:void(0)" onclick="$('#Form').trigger('submit')" class="button">Выполнить</a>&nbsp;
			<a href="javascript:void(0)" onclick="DClose()" class="button">Отмена</a>
		</div>
	</form>
	<script>
		$(function () {

			$('#dialog').css('width', '608px');

			$('#Form').ajaxForm({
				beforeSubmit: function () {

					var $out = $('#message');
					var em = checkRequired();

					if (em === false) return false;

					$('#dialog').css('display', 'none');
					$('#dialog_container').css('display', 'none');

					$out.empty().fadeTo(1, 1).css('display', 'block').append('<div id="loader"><img src="/assets/images/loading.gif"> Загрузка данных. Пожалуйста подождите...</div>');

					return true;

				},
				success: function (data) {
					$('#dialog').css('display', 'none');
					$('#dialog_container').css('display', 'none');
					$('#resultdiv').empty();

					configpage();

					$('#message').fadeTo(1, 1).css('display', 'block').html(data);
					setTimeout(function () {
						$('#message').fadeTo(1000, 0);
					}, 20000);
				}
			});
			$('#dialog').center();
		});

		function showd() {
			var cel = $('#doAction option:selected').val();
			if (cel == 'pMove') {
				$('#cattt').removeClass('hidden');
				$('#copyt').addClass('hidden');
				$('#synct').addClass('hidden');
				$('#delt').addClass('hidden');
				$('#deltc').addClass('hidden');
			}
			else if (cel == 'pCopy') {
				$('#copyt').removeClass('hidden');
				$('#cattt').addClass('hidden');
				$('#synct').addClass('hidden');
				$('#delt').addClass('hidden');
				$('#deltc').addClass('hidden');
			}
			else if (cel == 'pSync') {
				$('#synct').removeClass('hidden');
				$('#copyt').addClass('hidden');
				$('#cattt').addClass('hidden');
				$('#delt').addClass('hidden');
				$('#deltc').addClass('hidden');
			}
			else if (cel == 'pDele') {
				$('#delt').removeClass('hidden');
				$('#copyt').addClass('hidden');
				$('#cattt').addClass('hidden');
				$('#synct').addClass('hidden');
				$('#deltc').addClass('hidden');
			}
			else {
				$('#deltc').addClass('hidden');
				$('#copyt').addClass('hidden');
				$('#delt').addClass('hidden');
				$('#cattt').addClass('hidden');
				$('#synct').addClass('hidden');
			}
			$('#dialog').center();
		}
	</script>
	<?php
}