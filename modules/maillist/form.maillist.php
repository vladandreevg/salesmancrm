<?php
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

$mid    = $_REQUEST['id'];
$action = $_REQUEST['action'];

if ($action == "edit") {

	if ($_REQUEST['opt'] != 'undefined' and $_REQUEST['opt'] != '') {

		$mail = $db -> getRow("select * from ".$sqlname."mail where mid='".$mid."' and identity = '$identity'");
		$mid  = '0';

	}
	elseif ($mid > 1) {

		$mail = $db -> getRow("select * from ".$sqlname."mail where mid='".$mid."' and identity = '$identity'");

	}
	?>
	<DIV class="zagolovok">Создание/Изменение Рассылки</DIV>
	<FORM action="/modules/maillist/core.maillist.php" method="post" enctype="multipart/form-data" name="mailForm" id="mailForm">
		<input name="action" id="action" type="hidden" value="edit"/>
		<input name="mid" id="mid" type="hidden" value="<?= $mid ?>"/>

		<DIV id="formtabs" style="border:0; background: none; width: 100%">

			<UL>
				<LI><A href="#tform-1">Общие данные</A></LI>
				<LI><A href="#tform-4">+ Текст сообщения</A></LI>
				<LI><A href="#tform-2" class="lx" data-id="tform-2">+ Клиенты</A></LI>
				<LI><A href="#tform-3" class="lx" data-id="tform-3">+ Контакты</A></LI>
			</UL>

			<div id="tform-1" class="subtab">

				<div class="row">

					<div class="column12 grid-2 fs-12 gray2 text-right pt10">Название:</div>
					<div class="column12 grid-10">
						<input name="title" type="text" id="title" class="required" style="width: 100%;" value="<?= $mail['title'] ?>"/>
					</div>

					<div class="column12 grid-2 fs-12 gray2 text-right pt10">Описание:</div>
					<div class="column12 grid-10">
						<TEXTAREA name="descr" rows="3" class="des" id="descr" style="width: 100%; resize: none;"><?= $mail['descr'] ?></TEXTAREA>
					</div>

					<div class="column12 grid-2 fs-12 gray2 text-right pt10">Отправитель:</div>
					<div class="column12 grid-10">
						<select name="iduser" id="iduser">
							<option value="">--Выбор--</option>
							<option value="0" selected="selected">От имени Компании</option>
							<?php
							$result = $db -> getAll("SELECT * FROM ".$sqlname."user WHERE identity = '$identity'");
							foreach ($result as $data) {
								?>
								<option <?php if ($data['iduser'] == $mail['iduser']) print "selected"; ?> value="<?= $data['iduser'] ?>"><?= $data['title'] ?></option>
								<?php
							}
							?>
						</select>
					</div>

					<div class="column12 grid-2 fs-12 gray2 text-right pt10">Тема письма:</div>
					<div class="column12 grid-10">
						<input name="theme" type="text" class="required" id="theme" style="width: 100%;" value="<?= $mail['theme'] ?>"/>
					</div>

					<div class="column12 grid-12">

						<?php
						include $rootpath."/ajax/check_disk.php";
						if ($diskLimit > 0) {
							?>
							<div class="infodiv pad10" align="center">
								<?php
								print '<b>Ипользование диска:</b> Лимит: <b>'.$diskUsage['total'].'</b> Мб, Занято: <b class="red">'.$diskUsage['current'].'</b> Mb ( <b>'.$diskUsage['percent'].'</b> % )<br>';

								if ($maxupload == '') $maxupload = str_replace(array(
									'M',
									'm'
								), '', @ini_get('upload_max_filesize'));
								?>
							</div>
						<?php } ?>

						<div id="filelist" class="viewdiv pad10 wp100"></div>

						<DIV id="uploads" style="width:100%; max-height:99%; overflow-x:hidden; overflow-y:auto !important">
							<?php if ($diskLimit == 0 or $diskUsage['percent'] < 100) { ?>

								<div class="viewdiv pad10 wp100">
									<b class="red">Информация:</b> максимальный размер файла = <?= $maxupload ?>mb
								</div>

								<div id="file-1" class="filebox wp100">
									<input name="file[]" type="file" class="file wp100" id="file[]" onchange="addfile();" multiple>
									<div class="delfilebox hand" onclick="deleteFilebox('file-1')" title="Очистить">
										<i class="icon-cancel-circled red"></i></div>
								</div>

							<?php }
							else print '<div class="warning" align="center" style="width: 98%"><b class="red">Превышен лимит использования диска</b></div>';
							?>
						</DIV>

					</div>

				</div>

			</div>
			<div id="tform-4" class="subtab">

				<div class="row relativ templ">

					<div class="column12 grid-2 fs-12 gray2 text-right pt10">Из шаблона:</div>
					<div class="column12 grid-10">
						<select name="tpl_id" id="tpl_id" style="width: 300px;" onchange="select_tpl()">
							<option value="0">--Выбор--</option>
							<?php
							$result = $db -> getAll("SELECT * FROM ".$sqlname."mail_tpl WHERE identity = '$identity'");
							foreach ($result as $data) {
								?>
								<option <?php if ($data['tpl_id'] == $mail['tpl_id']) print "selected" ?> value="<?= $data['tpl_id'] ?>"><?= $data['name_tpl'] ?></option>
								<?php
							}
							?>
						</select>
					</div>

					<div class="pull-right mt10">
						<a href="javascript:void(0)" title="Действия" class="tagsmenuToggler"><b class="blue">Вставить тэг</b>&nbsp;<i class="icon-angle-down" id="mapii"></i></a>
						<div class="tagsmenu hidden" style="right: 0;">
							<ul>
								<li title="Клиент: Ф.И.О. или Название"><b>{client}</b></li>
								<li title="Тел.:мой"><b>{phone}</b></li>
								<li title="Факс:мой"><b>{fax}</b></li>
								<li title="Моб.:мой"><b>{mob}</b></li>
								<li title="Email:мой"><b>{email}</b></li>
								<li title="Подпись"><b>{manager}</b></li>
								<li title="Компания:кратко"><b>{company}</b></li>
								<li title="Компания:полное"><b>{company_full}</b></li>
								<li title="Сайт компании"><b>{company_site}</b></li>
							</ul>
						</div>
					</div>

				</div>

				<div class="row">

					<textarea name="content" rows="24" class="des" id="content" style="width: 100%;"><?= htmlspecialchars_decode($mail['template']) ?></textarea>

				</div>

			</div>
			<div id="tform-2" class="subtab">

				<div class="row xselectors" data-id="tform-2">

					<div class="column12 grid-2 fs-12 gray2 text-right pt10">Поисковый фильтр:</div>
					<div class="column12 grid-4">

						<select name="clist" id="clist" class="wp100" onchange="SeachClients()">
							<option value="">--выбор--</option>
							<optgroup label="Стандартные представления">
								<?php if ($tipuser != "Поддержка продаж") { ?>
									<option value="my" selected>Мои клиенты</option>
									<option value="fav">Ключевые клиенты</option>
									<option value="otdel">Клиенты Подчиненных</option>
									<?php
								}
								if ($tipuser != "Менеджер продаж" || $userRights['alls']) {
									?>
									<option value="all">Все клиенты</option>
								<?php } ?>
								<option value="trash">Холодные клиенты</option>
							</optgroup>
							<optgroup label="Настраиваемые представления">
								<?php
								$result = $db -> query("select * from ".$sqlname."search where tip='client' and iduser='".$iduser1."' and identity = '$identity' order by sorder");
								while ($data = $db -> fetch($result)) {
									print '<option value="search:'.$data['seid'].'">'.$data['title'].'</option>';
								}
								?>
							</optgroup>
						</select>

					</div>
					<div class="column12 grid-2 fs-12 gray2 text-right pt10">Территория:</div>
					<div class="column12 grid-4">

						<div class="ydropDown border">
							<span>Территория</span>
							<span class="ydropCount"><?= count($territory) ?> выбрано</span><i class="icon-angle-down pull-aright"></i>
							<div class="yselectBox" style="height: 200px;">
								<div class="yunSelect"><i class="icon-cancel-circled2"></i>Снять выделение</div>
								<div class="ydropString ellipsis">
									<label>
										<input class="taskss" name="territory[]" type="checkbox" id="territory[]" value="0" onchange="SeachClients()">&nbsp;Не указано
									</label>
								</div>
								<?php
								$result = $db -> query("SELECT * FROM ".$sqlname."territory_cat WHERE identity = '$identity' ORDER BY title");
								while ($data = $db -> fetch($result)) {
									?>
									<div class="ydropString ellipsis">
										<label>
											<input class="taskss" name="territory[]" type="checkbox" id="territory[]" value="<?= $data['idcategory'] ?>" onchange="SeachClients()">&nbsp;<?= $data['title'] ?>
										</label>
									</div>
								<?php } ?>
							</div>
						</div>

					</div>

					<div class="column12 grid-2 fs-12 gray2 text-right pt10">Тип отношений:</div>
					<div class="column12 grid-4">

						<div class="ydropDown border">
							<span>По Типу отношений</span>
							<span class="ydropCount"><?= count($tip_cmr) ?> выбрано</span><i class="icon-angle-down pull-aright"></i>
							<div class="yselectBox" style="height: 200px;">
								<div class="yunSelect"><i class="icon-cancel-circled2"></i>Снять выделение</div>
								<div class="ydropString ellipsis">
									<label>
										<input class="taskss" name="tip_cmr[]" type="checkbox" id="tip_cmr[]" value="0" <?php if (in_array("0", $tip_cmr)) print 'checked'; ?> onchange="SeachClients()">&nbsp;Не указано
									</label>
								</div>
								<?php
								$result = $db -> query("SELECT * FROM ".$sqlname."relations WHERE identity = '$identity' ORDER BY title");
								while ($data = $db -> fetch($result)) {
									?>
									<div class="ydropString ellipsis">
										<label>
											<input class="taskss" name="tip_cmr[]" type="checkbox" id="tip_cmr[]" value="<?= $data['title'] ?>" onchange="SeachClients()">&nbsp;<?= $data['title'] ?>
										</label>
									</div>
								<?php } ?>
							</div>
						</div>

					</div>
					<div class="column12 grid-2 fs-12 gray2 text-right pt10">Отрасль:</div>
					<div class="column12 grid-4">

						<div class="ydropDown border">
							<span>По Отрасли</span>
							<span class="ydropCount"><?= count($prcat) ?> выбрано</span><i class="icon-angle-down pull-aright"></i>
							<div class="yselectBox" style="height: 200px;">
								<div class="yunSelect"><i class="icon-cancel-circled2"></i>Снять выделение</div>
								<?php
								$result = $db -> query("SELECT * FROM ".$sqlname."category WHERE identity = '$identity' ORDER BY title");
								while ($data = $db -> fetch($result)) {
									?>
									<div class="ydropString ellipsis">
										<label>
											<input class="taskss" name="category[]" type="checkbox" id="category[]" value="<?= $data['idcategory'] ?>" onchange="SeachClients()">&nbsp;<?= $data['title'] ?>
										</label>
									</div>
								<?php } ?>
							</div>
						</div>

					</div>

					<div class="column12 grid-2 fs-12 gray2 text-right pt10">Источник клиента:</div>
					<div class="column12 grid-4">

						<div class="ydropDown border">
							<span>По Источнику клиента</span><span class="ydropCount"><?= count($cpath) ?> выбрано</span><i class="icon-angle-down pull-aright"></i>
							<div class="yselectBox" style="height: 200px;">
								<div class="yunSelect"><i class="icon-cancel-circled2"></i>Снять выделение</div>
								<div class="ydropString ellipsis">
									<label>
										<input class="taskss" name="clientpath0" type="checkbox" id="clientpath0" value="0" onchange="SeachClients()">&nbsp;Не указано
									</label>
								</div>
								<?php
								$result = $db -> query("SELECT * FROM ".$sqlname."clientpath WHERE identity = '$identity' ORDER BY name");
								while ($data = $db -> fetch($result)) {
									?>
									<div class="ydropString ellipsis">
										<label>
											<input class="taskss" name="clientpath[]" type="checkbox" id="clientpath[]" value="<?= $data['id'] ?>" onchange="SeachClients()">&nbsp;<?= $data['name'] ?>
										</label>
									</div>
									<?php
								}
								?>
							</div>
						</div>

					</div>

					<div class="column12 grid-2 fs-12 gray2 text-right pt10"></div>
					<div class="column12 grid-4"></div>

					<div class="column12 grid-6">Выбрано:</div>
					<div class="column12 grid-6">Результат поиска:</div>

				</div>

				<div class="row xlists" data-id="tform-2">

					<div class="column12 grid-6">

						<select name="client_list[]" id="client_list[]" multiple="multiple" class="wp100 bluebg-sub">
							<?php
							if ($mail['client_list'] != '') {

								$clients = yexplode(";", $mail['client_list']);
								$count   = count($clients);
								for ($i = 0; $i < $count; $i++) {

									print '<option value="'.$clients[ $i ].'">'.current_client($clients[ $i ]).'</option>';

								}

							}
							?>
						</select>

					</div>
					<div class="column12 grid-6">

						<select name="client_org" multiple="multiple" id="client_org" ondblclick="openClient($('#client_org option:selected').val())" class="wp100"></select>

					</div>

				</div>

				<div class="row xcounts" data-id="tform-2">

					<div class="column12 grid-6">

						Выбрано: <b><span id="sel_value" class="red">0</span></b>&nbsp;

						<div class="pull-aright">

							<a href="javascript:void(0)" onclick="removeAll();" class="button redbtn fs-07 m0 p5 pt2 pb2"><i class="icon-users-1"></i>&nbsp;Все</a>
							<a href="javascript:void(0)" onclick="removeSel();" class="button fs-07 m0 p5 pt2 pb2"><i class="icon-user-1"></i>&nbsp;Выбранное</a>

						</div>

					</div>
					<div class="column12 grid-6">

						Всего: <b><span id="all_value" class="red">0</span></b>&nbsp;

						<div class="pull-aright">

							<a href="javascript:void(0)" onclick="addAll()" class="button redbtn fs-07 m0 p5 pt2 pb2"><i class="icon-plus"></i>&nbsp;Все</a>
							<a href="javascript:void(0)" onclick="addSel()" class="button fs-07 m0 p5 pt2 pb2"><i class="icon-plus-circled"></i>&nbsp;Выбранное</a>

						</div>

					</div>

				</div>

			</div>
			<div id="tform-3" class="subtab">

				<div class="row xselectors" data-id="tform-3">

					<div class="column12 grid-2 fs-12 gray2 text-right pt10">Поисковый фильтр:</div>
					<div class="column12 grid-4">

						<select name="plist" id="plist" class="jcontent" style="width: 100%;" onchange="SeachPerson()">
							<option value="">--Выбор--</option>
							<optgroup label="Стандартные представления">
								<option value="my" selected>Мои Контакты</option>
								<option value="otdel">Контакты отдела</option>
								<?php
								if ($userRights['alls']) {
									?>
									<option value="all">Все Контакты</option>
								<?php } ?>
							</optgroup>
							<optgroup label="Настраиваемые представления">
								<?php
								$result = $db -> query("select * from ".$sqlname."search where tip = 'person' and iduser = '".$iduser1."' and identity = '$identity' order by sorder");
								while ($data = $db -> fetch($result)) {
									print '<option value="search:'.$data['seid'].'">'.$data['title'].'</option>';
								}
								?>
							</optgroup>
						</select>

					</div>
					<div class="column12 grid-2 fs-12 gray2 text-right pt10">Лояльность:</div>
					<div class="column12 grid-4">

						<select name="loyalty_p" id="loyalty_p" onchange="SeachPerson()" style="width:99%">
							<option value="">--Выбор--</option>
							<?php
							$result = $db -> query("SELECT * FROM ".$sqlname."loyal_cat WHERE identity = '$identity'");
							while ($data = $db -> fetch($result)) {
								?>
								<option value="<?= $data['idcategory'] ?>"><?= $data['title'] ?></option>
								<?php
							}
							?>
						</select>

					</div>

					<div class="column12 grid-2 fs-12 gray2 text-right pt10">Ответственный:</div>
					<div class="column12 grid-4">

						<select name="iduser_p" id="iduser_p" onchange="SeachPerson()" style="width:99%">
							<option value="">--Выбор--</option>
							<?php
							$result = $db -> query("SELECT * FROM ".$sqlname."user");
							while ($data = $db -> fetch($result)) {
								?>
								<option value="<?= $data['iduser'] ?>"><?= $data['title'] ?></option>
								<?php
							}
							?>
						</select>

					</div>

					<div class="column12 grid-2 fs-12 gray2 text-right pt10"></div>
					<div class="column12 grid-4"></div>

					<div class="column12 grid-6">Выбрано:</div>
					<div class="column12 grid-6">Результат поиска:</div>

				</div>

				<div class="row xlists" data-id="tform-3">

					<div class="column12 grid-6">

						<select name="person_list[]" id="person_list[]" multiple="multiple" class="wp100 bluebg-sub">
							<?php
							if ($mail['person_list'] != '') {

								$person = yexplode(";", $mail['person_list']);
								$count  = count($person);
								for ($i = 0; $i < $count; $i++) {

									print '<option value="'.$person[ $i ].'">'.current_person($person[ $i ]).'</option>';

								}

							}
							?>
						</select>

					</div>
					<div class="column12 grid-6">

						<select name="person_org" multiple="multiple" id="person_org" ondblclick="openClient($('#person_org option:selected').val())" class="wp100"></select>

					</div>

				</div>

				<div class="row xcounts" data-id="tform-3">

					<div class="column12 grid-6">

						Выбрано: <b><span id="sel_value_p" class="red">0</span></b>&nbsp;

						<div class="pull-aright">

							<a href="javascript:void(0)" onclick="removeAllp();" class="button redbtn fs-07 m0 p5 pt2 pb2"><i class="icon-users-1"></i>&nbsp;Все</a>
							<a href="javascript:void(0)" onclick="removeSelp();" class="button fs-07 m0 p5 pt2 pb2"><i class="icon-user-1"></i>&nbsp;Выбранное</a>

						</div>

					</div>
					<div class="column12 grid-6">

						Всего: <b><span id="all_value_p" class="red">0</span></b>&nbsp;

						<div class="pull-aright">

							<a href="javascript:void(0)" onclick="addAllp()" class="button redbtn fs-07 m0 p5 pt2 pb2"><i class="icon-plus"></i>&nbsp;Все</a>
							<a href="javascript:void(0)" onclick="addSelp()" class="button fs-07 m0 p5 pt2 pb2"><i class="icon-plus-circled"></i>&nbsp;Выбранное</a>

						</div>

					</div>

				</div>

			</div>
		</DIV>

		<hr class="wp100">

		<div class="button--pane">

			<div class="pt10 pull-left pl10">

				<div class="checkbox mt5">
					<label>
						<input name="do" type="checkbox" id="do" value="yes">
						<span class="custom-checkbox"><i class="icon-ok"></i></span>
						&nbsp;Произвести рассылку&nbsp;
					</label>
				</div>

			</div>
			<div class="pull-aright">

				<A href="javascript:void(0)" onClick="saveForm()" class="button">Сохранить</A>&nbsp;
				<A href="javascript:void(0)" onClick="DClose2()" class="button">Отмена</A>

			</div>


		</div>

	</FORM>

	<script>

		$('#filelist').load('modules/maillist/fileview.php?mid=<?=$mid?>');

	</script>
	<?php
}

if ($action == "view.message") {

	$html     = $db -> getOne("select template from ".$sqlname."mail where mid='".$mid."' and identity = '$identity'");
	$template = htmlspecialchars_decode($html);
	?>
	<DIV class="zagolovok">Просмотр сообщения:</DIV>
	<div style="overflow:auto;" id="msg">
		<?= $template ?>
	</div>
	<script>

		$('#dialog').css({'width': '90vw', 'height': '90vh'});

		var h = $('#dialog').actual('innerHeight') - $('.zagolovok').actual('outerHeight') - 10;

		$('#msg').css({'height': h + 'px', 'max-height': h + 'px'});

	</script>
	<?php
	exit();
}
if ($action == "view.info") {

	$mail = $db -> getRow("select * from ".$sqlname."mail where mid='".$mid."' and identity = '$identity'");

	$clients = yexplode(";", $mail["client_list"]);//список полуателей клиенты
	$persons = yexplode(";", $mail["person_list"]);//список полуателей контакты

	$clist = yexplode(";", $mail["clist_do"]);//список, кому отправили
	$plist = yexplode(";", $mail["plist_do"]);//список, кому отправили

	$num_client = count($clients);
	$num_person = count($persons);

	$file = yexplode(';', $mail['file']);

	$size_all = 0;
	$files    = '';

	for ($i = 0; $i < count($file); $i++) {

		$re    = $db -> getRow("select * from ".$sqlname."file where fid='".$file[ $i ]."' and identity = '$identity'");
		$title = $re["ftitle"];
		$name  = $re["fname"];

		$size_all += filesize($rootpath."/files/".$fpath.$name) / 1000;

		$files .= '<a href="javascript:void(0)" onclick="editUpload(\''.$file.'\',\'download\')" class="fs-10">'.get_icon2($title).'&nbsp;<b>'.$title.'</b></a>;<br />';

	}

	if ($mail['do'] == 'on') $do = 'Рассылка произведена';

	$usertitle = ($usertitle == '') ? "От имени компании" : current_user($mail['iduser']);

	?>
	<DIV class="zagolovok">Информация о рассылке</DIV>
	<div class="flex-container">

		<div class="flex-string wp50">

			<table width="99%" border="0" cellspacing="0" cellpadding="5" class="noborder">
				<?php
				if ($mail['title'] != '') { ?>
					<tr>
						<td width="100">
							<div class="fnameCold">Название:</div>
						</td>
						<td>
							<div class="fpoleCold Bold"><?= $mail['title'] ?></div>
						</td>
					</tr>
				<?php } ?>
				<tr>
					<td width="120">
						<div class="fnameCold">Отправитель:</div>
					</td>
					<td>
						<div class="fpoleCold blue"><?= $usertitle; ?></div>
					</td>
				</tr>
				<?php
				if ($mail['do'] != '') { ?>
					<tr>
						<td width="100">
							<div class="fnameCold">Сделано:</div>
						</td>
						<td>
							<div class="fpoleCold green">
								<i class="icon-ok-circled green smalltxt"></i>Рассылка произведена
							</div>
						</td>
					</tr>
					<?php
				}
				if ($mail['do'] == '') { ?>
					<tr>
						<td width="100" valign="top" nowrap="nowrap" align="right">&nbsp;Сделано:&nbsp;</td>
						<td><b class="red"><i class="icon-help-circled-1 gray smalltxt"></i>Рассылка не произведена</b>&nbsp;
						</td>
					</tr>
					<?php
				}
				if ($mail['datum'] != '') { ?>
					<tr>
						<td width="100">
							<div class="fnameCold">Дата создания:</div>
						</td>
						<td>
							<div class="fpoleCold"><?= $mail['datum'] ?></div>
						</td>
					</tr>
					<?php
				}
				?>
				<tr>
					<td width="100" valign="top">
						<div class="fnameCold">Получатели:</div>
					</td>
					<td valign="top">
						<ul class="nolist marg0 paddtop5">
							<li>Клиенты: <b class="blue"><?= $num_client ?></b>, Отправлено -
								<b class="red"><?= count($clist) ?></b></li>
							<li>Контакты: <b class="blue"><?= $num_person ?></b>, Отправлено -
								<b class="red"><?= count($plist) ?></b></li>
						</ul>
					</td>
				</tr>
				<?php
				if ($files != "") { ?>
					<tr>
						<td width="100" valign="top">
							<div class="fnameCold">Файлы:</div>
						</td>
						<td>
							<div class="fpoleCold"><?= $files ?></div>
						</td>
					</tr>
					<?php
				}
				if ($size_all != '') { ?>
					<tr class="noborder">
						<td width="100">
							<div class="fnameCold">Размер файлов:</div>
						</td>
						<td>
							<div class="fpoleCold"><?= num_format($size_all) ?>&nbsp;kb</div>
						</td>
					</tr>
				<?php } ?>
			</table>

		</div>
		<div class="flex-string wp50">

			<?php

			for ($i = 0; $i < count($clients); $i++) {

				if (in_array($clients[ $i ], $clist)) {
					$s = '<i class="icon-ok-circled green smalltxt"></i>';
					$t = 'Отправлено';
				}
				else {
					$s = '<i class="icon-help-circled-1 gray smalltxt"></i>';
					$t = 'Не отправлено';
				}

				print '<div class="ellipsis padbot5 hand ha" onclick="openClient(\''.$clients[ $i ].'\')" title="'.$t.'">'.$s.'<i class="icon-commerical-building broun"></i>'.current_client($clients[ $i ]).'</div><br>';

			}

			for ($i = 0; $i < count($persons); $i++) {

				if (in_array($persons[ $i ], $plist)) {
					$s = '<i class="icon-ok-circled green smalltxt"></i>';
					$t = 'Отправлено';
				}
				else {
					$s = '<i class="icon-help-circled-1 gray smalltxt"></i>';
					$t = 'Не отправлено';
				}

				print '<div class="ellipsis padbot5 hand ha" onclick="openPerson(\''.$persons[ $i ].'\')" title="'.$t.'">'.$s.'<i class="icon-user-1 broun"></i>'.current_person($persons[ $i ]).'</div><br>';

			}
			?>

		</div>

	</div>
	<script>

		$('#dialog').css({'width': '802px', 'max-height': '80vh'});

		var h = $('#dialog').actual('innerHeight') - $('.zagolovok').actual('outerHeight') - 10;

		$('#dialog').find('.flex-container').css({'height': h + 'px', 'max-height': h + 'px', 'overflow-y': 'auto'});
		$('#dialog').find('.flex-string:last-child').css({
			'height': h + 'px',
			'max-height': h + 'px',
			'overflow-y': 'auto'
		});

	</script>
	<?php
	exit();
}

if ($action == "tpl.edit") {

	$id = $_REQUEST['id'];

	if ($id > 0) $tpl = $db -> getRow("select * from ".$sqlname."mail_tpl where tpl_id='".$id."' and identity = '$identity'");
	else {
		$tpl = array();
		$id  = 0;
	}

	?>
	<DIV class="zagolovok">Создание нового Шаблона</DIV>

	<FORM action="/modules/maillist/core.maillist.php" method="post" enctype="multipart/form-data" name="mailForm" id="mailForm">
		<input name="action" id="action" type="hidden" value="tpl.edit"/>
		<input name="tpl_id" id="tpl_id" type="hidden" value="<?= $id ?>"/>

		<div id="tform-1" class="subtab">

			<div class="row relativ templ">

				<input name="name_tpl" class="required wp80" type="text" id="name_tpl" value="<?= $tpl['name_tpl'] ?>" placeholder="Название шаблона"/>

				<hr class="wp100">

				<div class="pull-right mt10">
					<a href="javascript:void(0)" title="Действия" class="tagsmenuToggler"><b class="blue">Вставить тэг</b>&nbsp;<i class="icon-angle-down" id="mapii"></i></a>
					<div class="tagsmenu hidden" style="right: 0;">
						<ul>
							<li title="Клиент: Ф.И.О. или Название"><b>{client}</b></li>
							<li title="Тел.:мой"><b>{phone}</b></li>
							<li title="Факс:мой"><b>{fax}</b></li>
							<li title="Моб.:мой"><b>{mob}</b></li>
							<li title="Email:мой"><b>{email}</b></li>
							<li title="Подпись"><b>{manager}</b></li>
							<li title="Компания:кратко"><b>{company}</b></li>
							<li title="Компания:полное"><b>{company_full}</b></li>
							<li title="Сайт компании"><b>{company_site}</b></li>
						</ul>
					</div>
				</div>

			</div>

			<div class="row">

			<textarea name="content" rows="24" class="des" id="content" style="width: 100%;">
				<?= htmlspecialchars_decode($tpl['content_tpl']) ?>
			</textarea>

			</div>

		</div>

		<hr class="wp100">

		<div class="button--pane">

			<div class="pull-aright">

				<A href="javascript:void(0)" onClick="saveForm()" class="button">Сохранить</A>&nbsp;
				<A href="javascript:void(0)" onClick="DClose2()" class="button">Отмена</A>

			</div>

		</div>

	</FORM>
	<?php
}
if ($action == "tpl.view") {

	$tpl_id = $_REQUEST['id'];

	$result      = $db -> getRow("select * from ".$sqlname."mail_tpl where tpl_id='".$tpl_id."' and identity = '$identity'");
	$name_tpl    = $result["name_tpl"];
	$content_tpl = htmlspecialchars_decode($result["content_tpl"]);
	?>
	<DIV class="zagolovok"><?= $name_tpl ?></DIV>
	<div style="overflow:auto;" id="msg">
		<?= $content_tpl ?>
	</div>
	<script>

		$('#dialog').css({'width': '90vw', 'height': '90vh'});

		var h = $('#dialog').actual('innerHeight') - $('.zagolovok').actual('outerHeight') - 10;

		$('#msg').css({'height': h + 'px', 'max-height': h + 'px'});

	</script>
	<?php
	exit();
}
if ($action == "tpl.get") {

	$tpl_id = $_REQUEST['tpl_id'];

	$content_tpl = $db -> getOne("select content_tpl from ".$sqlname."mail_tpl where tpl_id='".$tpl_id."' and identity = '$identity'");

	print htmlspecialchars_decode($content_tpl);

	exit();
}
?>
<script>

	var eh = 0;
	var bh = 0;
	var lh = 0;
	var sh = 0;
	var action = $('#action').val();

	var dw = '80vw';
	var dh = '90vh';

	var editor2;

	if ($(window).width() < 990) {

		dw = '95%';
		dh = '95vh';

	} else if ($(window).width() > 1500) {

		dw = '1200px';
		dh = '90vh';

	}

	$('#dialog').css({'height': dh, 'width': dw});

	$('#formtabs').tabs();

	if (action != 'tpl.edit') bh = $('#dialog').actual('height') - $('.zagolovok').actual('outerHeight') - $('.button--pane').actual('outerHeight') - $('#formtabs ul').actual('outerHeight') - 70;
	else bh = $('#dialog').actual('height') - $('.zagolovok').actual('outerHeight') - $('.button--pane').actual('outerHeight') - 60;

	$('.subtab').css({"max-height": bh + "px", "height": bh + "px"});

	eh = bh - $('.templ').actual('outerHeight') - $('.cke_top').actual('outerHeight');

	$(document).ready(function () {

		createEditor2();

		$("#rol_p").autocomplete("content/helpers/person.helpers.php?action=get.role", {
			autofill: false,
			minChars: 3,
			cacheLength: 100,
			maxItemsToShow: 100,
			selectFirst: true,
			multiple: true,
			multipleSeparator: "; ",
			delay: 10
		});
		$("#datum_1").datepicker({
			dateFormat: 'yy-mm-dd',
			firstDay: 1,
			dayNamesMin: ['Вс', 'Пн', 'Вт', 'Ср', 'Чт', 'Пт', 'Сб'],
			monthNamesShort: ['Январь', 'Февраль', 'Март', 'Апрель', 'Май', 'Июнь', 'Июль', 'Август', 'Сентябрь', 'Октябрь', 'Ноябрь', 'Декабрь'],
			changeMonth: true,
			changeYear: true
		});
		$("#datum_2").datepicker({
			dateFormat: 'yy-mm-dd',
			firstDay: 1,
			dayNamesMin: ['Вс', 'Пн', 'Вт', 'Ср', 'Чт', 'Пт', 'Сб'],
			monthNamesShort: ['Январь', 'Февраль', 'Март', 'Апрель', 'Май', 'Июнь', 'Июль', 'Август', 'Сентябрь', 'Октябрь', 'Ноябрь', 'Декабрь'],
			changeMonth: true,
			changeYear: true
		});

		SeachClients();
		SeachPerson();

		$('#mailForm').ajaxForm({
			dataType: 'json',
			beforeSubmit: function () {

				var $out = $('#message');

				var em = 0;
				$(".required").removeClass("empty").css({"color": "inherit", "background": "inherit"});
				$(".required").each(function () {

					if ($(this).val() == '') {
						$(this).addClass("empty").css({"color": "#ffffff", "background": "#FF8080"});
						em = em + 1;
					}

				});

				$out.empty();

				if (em > 0) {

					alert("Не заполнены обязательные поля\n\rОни выделены цветом");
					return false;

				}
				if (em == 0) {

					$('#dialog').css('display', 'none');
					$('#dialog_container').css('display', 'none');
					$('#message').empty().fadeTo(1, 1).css('display', 'block').append('<div id=loader><img src=/assets/images/loader.gif> Загрузка данных. Пожалуйста подождите...</div>');
					$('#client_list\\[\\] option').attr('selected', 'yes');
					$('#person_list\\[\\] option').attr('selected', 'yes');

					removeEditor2();

					return true;

				}

			},
			success: function (data) {
				removeEditor2();
				$('#resultdiv').empty();
				$('#dialog_container').css('display', 'none');
				$('#dialog').css('display', 'none').css('width', '500px');

				$('#message').fadeTo(1, 1).css('display', 'block').html('Результат:<br>' + data.result);
				setTimeout(function () {
					$('#message').fadeTo(1000, 0);
				}, 20000);

				configpage();

				if (data.doit == 'yes') {
					var left = screen.availWidth - 360;
					var top = screen.availHeight - 230;
					mailer = window.open('modules/maillist/mailing.php?mid=' + data.mid, 'Yoolla', 'width=350, height=200, menubar=no, location=no, resizable=no, scrollbars=yes, status=no, left=' + left + ', top=' + top);
					mailer.focus();
				}
			}
		});

		$('.yunSelect').click(function () {
			var chk = $(this).parent('.yselectBox').find('input[type=checkbox]:checked').attr('checked', false);
			var $f = $(this).parents('.ydropDown').find('.ydropCount');
			$f.html('0 выбрано');
			SeachClients();
		});

		$('#dialog').center();

	});

	$('.tagsmenu li').click(function () {

		var t = $('b', this).html();
		addTagInEditor(t);

	});
	$('.lx').bind('click', function () {

		var id = $(this).data('id');

		var elSelector = $('.xselectors[data-id="' + id + '"]');
		var elCount = $('.xcounts[data-id="' + id + '"]');
		var elList = $('.xlists[data-id="' + id + '"]');

		setTimeout(function () {

			lh = bh - elSelector.actual('height') - elCount.actual('outerHeight');

			//console.log(id);
			//console.log('xselectors = ' + elSelector.actual('height'));
			//console.log('xcounts = ' + elCount.actual('height'));

			elList.css({"max-height": lh + "px", "height": lh + "px"});
			elList.find('select').css({"height": lh - 10 + "px"});

		}, 10);

	});

	function saveForm(){

		CKEDITOR.instances['content'].updateElement();

		$('#mailForm').submit();

	}
	function createEditor2() {

		editor2 = CKEDITOR.replace('content',
			{
				height: eh - 150 + 'px',
				width: '100%',
				extraPlugins: 'image2,textselection,base64image,codemirror,oembed,widget',//,imageuploader',
				filebrowserUploadUrl: '/modules/ckuploader/upload.php?type=kb',
				toolbar:
					[
						['Source', '-', 'Bold', 'Italic', 'Underline', 'Strike', 'Subscript', 'Superscript', '-', 'NumberedList', 'BulletedList', '-', 'Link', 'Unlink'],
						['Undo', 'Redo', '-', 'Replace', '-', 'SelectAll', 'Maximize', 'RemoveFormat', '-', 'PasteText', 'PasteFromWord', 'Image', 'HorizontalRule'],
						['TextColor', 'Format', 'FontSize'],
						['JustifyLeft', 'JustifyCenter', 'JustifyRight', 'JustifyBlock']
					]
			});

		CKEDITOR.on("instanceReady", function (event) {

			//console.log("bh = " + bh);
			//console.log("templ = " + $('.templ').actual('outerHeight'));
			//console.log("cke_top = " + $('.cke_top').actual('outerHeight'));

			eh = bh - $('.templ').actual('outerHeight') - $('.cke_top').actual('outerHeight') - 60;

			if (action === 'tpl.edit') eh += 30;

			$('.cke_contents').height(eh + 'px');

		});
	}

	function removeEditor2() {
		var html = $('#cke_editor_content').html();
		if (editor2) {
			$('#content').val(html);
			editor2.destroy();
			editor2 = null;
		}
		return true;
	}

	function DClose2() {
		removeEditor2();
		$('.nano').css('height', '100%');
		$('#dialog').css('display', 'none');
		$('#resultdiv').empty();
		$('#dialog_container').css('display', 'none');
		$('#dialog').css('width', '500px').height('auto');
	}

	function addfile() {

		var kol = $('.filebox').size();
		var i = kol + 1;
		var htmltr = '<div id="file-' + i + '" class="filebox wp100"><input name="file[]" type="file" class="file" id="file[]" onchange="addfile();" multiple><div class="delfilebox hand" onclick="deleteFilebox(\'file-' + i + '\')" title="Очистить"><i class="icon-cancel-circled red"></i></div></div>';

		$('#uploads').append(htmltr);
		$('#dialog').center();

	}

	function SeachClients() {

		$('#client_list\\[\\] option').attr('selected', true);

		var url = 'content/helpers/client.helpers.php?action=get_clients&filter=' + $('#clist option:selected').val() + '&' + $('#clientpath\\[\\]').serialize() + '&' + $('#tip_cmr\\[\\]').serialize() + '&' + $('#territory\\[\\]').serialize() + '&' + $('#category\\[\\]').serialize() + '&' + $('#client_list\\[\\]').serialize();

		$.get(url, function (data) {

			$('#client_org').empty().append(data);

		})
			.complete(function () {

				$('#all_value').html($('#client_org option').length);
				$('#sel_value').html($('#client_list\\[\\] option').size());

				$('#client_list\\[\\] option').attr('selected', false);

			});

	}

	function SeachPerson() {

		$('#person_list\\[\\] option').attr('selected', true);

		var url = 'content/helpers/person.helpers.php?action=get_clients&plist=' + $('#plist option:selected').val() + '&loyalty=' + $('#loyalty_p').val() + '&iduser=' + $('#iduser_p option:selected').val() + '&' + $('#person_list\\[\\]').serialize();

		$.get(url, function (data) {

			$('#person_org').empty().append(data);

		})
			.complete(function () {

				$('#all_value_p').html($('#person_org option').length);
				$('#sel_value_p').html($('#person_list\\[\\] option').size());

				$('#person_list\\[\\] option').attr('selected', false);

			});

	}

	function addAll() {

		var sel = $('#client_org option');

		$('#client_list\\[\\]').append(sel);
		$('#client_list\\[\\] option').attr('selected', true);

		SeachClients();

		$('#client_list\\[\\] option').attr('selected', false);

	}

	function removeAll() {

		$('#client_list\\[\\] option').remove();
		$('#client_list\\[\\] option').attr('selected', true);

		SeachClients();

		$('#client_list\\[\\] option').attr('selected', false);

	}

	function addSel() {

		var sel = $('#client_org option:selected');

		$('#client_list\\[\\]').append(sel);
		$('#client_list\\[\\] option').attr('selected', true);

		SeachClients();

		$('#client_list\\[\\] option').attr('selected', false);
	}

	function removeSel() {

		$('#client_list\\[\\] option:selected').remove();
		$('#client_list\\[\\] option').attr('selected', true);

		SeachClients();

		$('#client_list\\[\\] option').attr('selected', false);
	}

	function addAllp() {

		var sel = $('#person_org option');

		$('#person_list\\[\\]').append(sel);
		$('#person_list\\[\\] option').attr('selected', true);

		SeachPerson();

		$('#person_list\\[\\] option').attr('selected', false);

	}

	function removeAllp() {

		$('#person_list\\[\\] option').remove();
		$('#person_list\\[\\] option').attr('selected', true);

		SeachPerson();

		$('#person_list\\[\\] option').attr('selected', false);

	}

	function addSelp() {

		var sel = $('#person_org option:selected');

		$('#person_list\\[\\]').append(sel);
		$('#person_list\\[\\] option').attr('selected', true);

		SeachPerson();

		$('#person_list\\[\\] option').attr('selected', false);
	}

	function removeSelp() {

		$('#person_list\\[\\] option:selected').remove();
		$('#person_list\\[\\] option').attr('selected', true);

		SeachPerson();

		$('#person_list\\[\\] option').attr('selected', false);
	}

	function SaveMail() {

		$('#person_list\\[\\] option').attr('selected', 'yes');
		$('#client_list\\[\\] option').attr('selected', 'yes');

	}

	function select_tpl() {

		var url = 'modules/maillist/form.maillist.php?action=tpl.get&tpl_id=' + $('#tpl_id option:selected').val();

		$.post(url, function (data) {

			editor2.setData(data);

		});

	}

	function addTagInEditor(myitem) {
		html = $('#cke_editor_content').html();

		var oEditor = CKEDITOR.instances.content;
		oEditor.insertHtml(myitem);

		return true;
	}
</script>