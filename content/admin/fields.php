<?php
/* ============================ */
/*         SalesMan CRM         */
/* ============================ */
/* (C) 2018 Vladislav Andreev   */
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

$thisfile = basename(__FILE__);

$action = $_REQUEST['action'];

$helper = json_decode(file_get_contents($rootpath.'/cash/helper.json'), true);
$ttip   = [
	'select',
	'multiselect',
	'inputlist',
	'radio'
];

$xtype  = [
	"client"     => "Клиент",
	"partner"    => "Партнер",
	"contractor" => "Поставщик",
	"concurent"  => "Конкурент",
];
$xcolor = [
	"client"     => "blue",
	"partner"    => "green",
	"contractor" => "broun",
	"concurent"  => "red",
];

$fieldMessage = [
	'iduser'     => 'Поле связано со справочником <b>Сотрудники</b>.',
	'idcategory' => 'Это поле связано со справочником "<b>Отрасли</b>".',
	'territory'  => 'Это поле связано со справочником "<b>Территория</b>".',
	'clientpath' => 'Это поле связано со справочником "<b>Источник клиента / Канал</b>".',
	'pid'        => 'Это поле связано со справочником "<b>Контакты</b>".',
	'loyalty'    => 'Это поле связано со справочником "<b>Лояльность</b>".',
	'tip_cmr'    => 'Это поле связано со справочником "<b>Тип отношений</b>".',
	'recv'       => 'Это не поле, а блок полей для заполнения платежных реквизитов Клиента. Важны для Документов, Счетов и Актов',
	'social'     => 'Это не поле, а блок полей для социальных ссылок',
	'head_clid'  => 'Это поле связано со списком Клиентов. Позволяет связать клиента с головной организацией',
	'clid'       => 'Это поле связывает Контакт с Клиентом.'
];

if ($action == "edit.on") {

	$fld_id       = $_REQUEST['fld_id'];
	$fld_tip      = $_REQUEST['fld_tip'];
	$fld_title    = $_REQUEST['fld_title'];
	$fld_required = $_REQUEST['fld_required'];
	$fld_temp     = $_REQUEST['fld_temp'];
	$fld_on       = $_REQUEST['fld_on'];
	$fld_var      = $_REQUEST['fld_var'];
	$fld_name     = $_REQUEST['fld_name'];
	$fld_sub      = $_REQUEST['fld_sub'];
	$tip          = $fld_tip;

	if (in_array($fld_temp, $ttip) || $fld_name == 'ptitle') {

		$fld_var = str_replace([
			"\\r\\n",
			"\r\n"
		], ",", $fld_var);

		/*$vars = yexplode( ",", (string)$fld_var );

		$varr = [];
		foreach ($vars as $var) {

			$varr[] = trim( str_replace( [
				"\\n\\r",
				"\\n",
				"\\r",
				"\n",
				"\r",
				","
			], "", $var ) );
		}*/

	}

	if (
		in_array($fld_name, [
			'clid',
			'iduser',
			'title',
			'pid',
			'person',
			'mcid',
			'datum_plan'
		])
	) {
		$fld_required = 'required';
	}

	//Обновляем данные для текущей записи
	$db -> query("UPDATE {$sqlname}field SET ?u WHERE fld_id = '$fld_id' and identity = '$identity'", [
		'fld_title'    => $fld_title,
		'fld_temp'     => $fld_temp,
		'fld_required' => $fld_required,
		'fld_on'       => $fld_on,
		'fld_var'      => $fld_var,
		"fld_sub"      => $fld_sub ?? NULL
	]);

	print "Запись обновлена";

	exit();

}
if ($action == "edit.order") {

	$table1 = explode(';', implode(';', (array)$_REQUEST['table-1']));
	$table2 = explode(';', implode(';', (array)$_REQUEST['table-2']));

	$count1 = count((array)$_REQUEST['table-1']);
	$count2 = count((array)$_REQUEST['table-2']);
	$err    = 0;

	//Обновляем данные для текущей записи
	if ($count1 > 0) {
		for ($i = 1; $i < $count1; $i++) {
			if (!$db -> query("update {$sqlname}field set fld_order = '".$i."' where fld_id = '".$table1[$i]."' and identity = '$identity'")) {
				$err++;
			}
		}
		print "Обновлено. Ошибок: ".$err;
	}

	if ($count2 > 0) {
		for ($i = 1; $i < $count2; $i++) {
			if (!$db -> query("update {$sqlname}field set fld_order = '$i' where fld_id = '".$table2[$i]."' and identity = '$identity'")) {
				$err++;
			}
		}
		print "Обновлено. Ошибок: ".$err;
	}

	exit();

}

if ($action == 'switchShow') {

	$tip    = $_REQUEST['tip'];
	$fld_id = $_REQUEST['id'];

	$result = $db -> getRow("select * from {$sqlname}field where fld_id = '$fld_id' and identity = '$identity'");
	$fld_on = $result['fld_on'];

	$fld_on = ( $fld_on == 'yes' ) ? '' : 'yes';

	//Обновляем данные для текущей записи
	if ($db -> query("update {$sqlname}field set fld_on = '$fld_on' where fld_id = '$fld_id' and identity = '$identity'")) {
		print "Запись обновлена";
	}

	exit();

}
if ($action == 'switchReq') {

	$tip    = $_REQUEST['tip'];
	$fld_id = $_REQUEST['id'];

	$result       = $db -> getRow("select * from {$sqlname}field where fld_id = '$fld_id' and identity = '$identity'");
	$fld_required = $result['fld_required'];

	$fld_required = $fld_required == 'required' ? '' : 'required';

	//Обновляем данные для текущей записи
	if ($db -> query("update {$sqlname}field set fld_required = '$fld_required' where fld_id = '$fld_id' and identity = '$identity'")) {
		print "Запись обновлена";
	}
	else {
		print "Ошибка";
	}


	exit();

}

/*
 * Только для коробочного варианта
 */
if ($action == 'addfield') {

	$tip = $_REQUEST['tip'];

	$field = [];

	//считаем все доп.поля
	$result = $db -> getAll("SELECT fld_name FROM {$sqlname}field WHERE fld_name LIKE '%input%' and fld_tip = '$tip' and identity = '$identity'");
	foreach ($result as $data) {

		$field[$data['fld_name']] = (int)preg_replace("/\D/", "", $data['fld_name']);

	}

	//print_r($field);

	$last = max($field);

	//print $last;

	$next = (int)$last + 1;

	$table = ( $tip == 'client' ) ? "clientcat" : "personcat";

	$db -> query("ALTER TABLE `{$sqlname}{$table}` ADD `input{$next}` VARCHAR(512) NULL DEFAULT NULL AFTER `input{$last}`");

	$order = (int)$db -> getOne("select MAX(fld_order) from {$sqlname}field where identity = '".$identity."' and fld_tip = '$tip'") + 1;

	$fieldAdd = [
		"fld_tip"      => $tip,
		"fld_name"     => "input".$next,
		"fld_title"    => 'доп.поле',
		"fld_order"    => $order,
		"fld_on"       => NULL,
		"fld_required" => NULL,
		"fld_stat"     => 'no',
		"identity"     => $identity
	];
	$db -> query("INSERT INTO {$sqlname}field SET ?u", arrayNullClean($fieldAdd));
	$id = $db -> insertId();

	$pretext = 'Поле добавлено. Предлагаем задать его параметры.';

	print json_encode_cyr([
		"text" => $pretext,
		"id"   => $id
	]);

	exit();

}

if ($action == 'edit') {

	$result       = $db -> getRow("select * from {$sqlname}field where fld_id='".$_REQUEST['fld_id']."' and identity = '$identity'");
	$fld_name     = $result["fld_name"];
	$fld_title    = $result["fld_title"];
	$fld_type     = $result["fld_type"];
	$fld_required = $result["fld_required"];
	$fld_on       = $result["fld_on"];
	$fld_tip      = $result["fld_tip"];
	$fld_order    = $result["fld_order"];
	$fld_temp     = $result["fld_temp"];
	$fld_var      = $result["fld_var"];
	$fld_sub      = $result["fld_sub"];
	$fld_stat     = $result["fld_stat"];

	$viz = '';
	$xviz = 'hidden';

	if ($fld_temp == "select" || $fld_temp == "multiselect" || $fld_temp == "inputlist" || $fld_temp == "radio") {
		$fld_var = str_replace(",", "\n", $fld_var);
	}
	else {
		$viz = 'hidden';
	}

	$hh = ( stripos($fld_name, 'input') !== false ) ? '' : 'hidden';

	$exclude   = [
		'clid',
		'iduser',
		'title',
		'person',
		//'idcategory',
		//'territory',
		'datum_plan'
	];
	$attention = [
		//'idcategory',
		//'territory',
		'iduser',
		//'clientpath',
		//'pid',
		//'loyalty',
		//'tip_cmr',
	];

	if (in_array($fld_name, $exclude) || stripos($fld_name, 'input') === false) {
		$d = "hidden";
	}
	if($fld_name == 'ptitle'){
		$fld_temp = "textarea";
		$xviz = '';
		$fld_var = str_replace(",", "\n", $fld_var);
	}

	$atsn     = ( in_array($fld_name, $attention) ) ? "" : "hidden";
	$tips     = ( in_array($fld_name, $exclude) || stripos($fld_name, 'input') === false ) ? 'hidden' : '';

	$textarea = ( $fld_temp == "textarea" ) ? '' : 'hidden';

	$pretext = $fieldMessage[$fld_name];

	?>
	<DIV class="zagolovok">Изменение поля</DIV>

	<FORM action="content/admin/<?php
	echo $thisfile; ?>" method="post" enctype="multipart/form-data" name="Form" id="Form">
		<INPUT type="hidden" name="action" id="action" value="edit.on">
		<INPUT name="fld_id" type="hidden" id="fld_id" value="<?= $_REQUEST['fld_id'] ?>">
		<INPUT name="fld_tip" type="hidden" id="fld_tip" value="<?= $fld_tip ?>">
		<INPUT name="fld_name" type="hidden" id="fld_name" value="<?= $fld_name ?>">
		<INPUT name="tip" type="hidden" id="tip" value="<?= $_REQUEST['tip'] ?>">

		<div class="row">

			<div class="column12 grid-3 fs-12 right-text gray2 pt10">Системное имя:</div>
			<div class="column12 grid-9"><input type="text" value="<?= $fld_name ?>" disabled></div>

		</div>
		<div class="row">

			<div class="column12 grid-3 fs-12 right-text gray2 pt10">Название поля:</div>
			<div class="column12 grid-9">
				<input type="text" id="fld_title" name="fld_title" value="<?= $fld_title ?>" class="required" style="width:95%">
				<div class="<?= $atsn ?> em">
					<b class="red">Внимание:</b> при переименовании этого поля могут возникнуть сложности в понимании некоторых разделов Панели управления, и в целом системы
				</div>
			</div>

		</div>
		<div class="row <?= $tips ?>">

			<div class="column12 grid-3 fs-12 right-text gray2 pt10">Тип поля:</div>
			<div class="column12 grid-9">
				<select name="fld_temp" id="fld_temp" <?= $ro ?>>
					<option>--Обычное--</option>
					<option value="hidden" <?php
					print ( $fld_temp == 'hidden' ) ? 'selected' : '' ?>>Скрытое поле
					</option>
					<option value="inputlist" <?php
					print ( $fld_temp == 'inputlist' ) ? 'selected' : '' ?>>Поле с вариантами
					</option>
					<option value="datum" <?php
					print ( $fld_temp == 'datum' ) ? 'selected' : '' ?>>Дата
					</option>
					<option value="adres" <?php
					print ( $fld_temp == 'adres' ) ? 'selected' : '' ?>>Адрес
					</option>
					<option value="textarea" <?php
					print ( $fld_temp == 'textarea' ) ? 'selected' : '' ?>>Большой текст
					</option>
					<option value="select" <?php
					print ( $fld_temp == 'select' ) ? 'selected' : '' ?>>Список выбора
					</option>
					<option value="radio" <?php
					print ( $fld_temp == 'radio' ) ? 'selected' : '' ?>>Одиночный выбор
					</option>
					<option value="multiselect" <?php
					print ( $fld_temp == 'multiselect' ) ? 'selected' : '' ?>>Множественный выбор
					</option>
				</select>
			</div>

		</div>

		<?php
		if (
			$_REQUEST['tip'] == 'client' && ( $fld_stat != 'yes' || in_array($fld_name, ['territory', 'clientpath', 'tip_cmr', 'address']) ) &&
			!in_array($fld_name, $exclude) && !in_array($fld_name, ['recv', 'head_clid', 'pid'])
		) {
			?>
			<div class="row">

				<div class="column12 grid-3 fs-12 right-text gray2 pt10">Принадлежность:</div>
				<div class="column12 grid-9">
					<select name="fld_sub" id="fld_sub" <?= $ro ?>>
						<option value="">--Универсально--</option>
						<?php
						foreach ($xtype as $t => $n) {
							print '<option value="'.$t.'" '.( $fld_sub == $t ? 'selected' : '' ).'>'.$n.'</option>';
						}
						?>
					</select>
				</div>

			</div>
		<?php
		} ?>

		<div class="row <?= $textarea ?>" id="vars">

			<div class="column12 grid-3 fs-12 right-text gray2 pt10">Варианты выбора:</div>
			<div class="column12 grid-9">
				<textarea name="fld_var" id="fld_var" style="width:95%; height: 150px;"><?= $fld_var ?></textarea>
				<div class="smalltxt">Каждый вариант начинайте с новой строки</div>

				<div class="attention <?= $xviz?>"><b class="red">Внимание!</b> Если варианты не заполнены, то система будет предлагать из существующих записей в системе</div>

			</div>

		</div>
		<div class="row">

			<div class="column12 grid-3 fs-12 right-text gray2 pt10"></div>
			<div class="column12 grid-9 fs-12 gray2">
				<?php
				if (!in_array($fld_name, $exclude) && !in_array($fld_name, $attention)) { ?>
					<label for="fld_on"><input name="fld_on" id="fld_on" type="checkbox" <?php
						print ( $fld_on == 'yes' ) ? "checked" : "" ?> value="yes"> Включено</label>
					<?php
				}
				else {
					?>
					<div class="em fs-09 black"><b class="red">Внимание:</b> Это поле всегда должно быть включено</div>
					<input name="fld_on" id="fld_on" type="hidden" value="yes">
				<?php
				} ?>
			</div>

		</div>
		<div class="row">

			<div class="column12 grid-3 fs-12 right-text gray2 pt10"></div>
			<div class="column12 grid-9 fs-12 gray2">
				<?php
				if (!in_array($fld_name, $exclude)) { ?>
					<label for="fld_required"><input name="fld_required" id="fld_required" type="checkbox" <?php
						print ( $fld_required == 'required' ) ? "checked" : "" ?> value="required"> Обязательное поле</label>
					<?php
				}
				else {
					?>
					<div class="em fs-09 black"><b class="red">Внимание:</b> Это поле всегда должно быть заполнено</div>
					<input name="fld_required" id="fld_required" type="hidden" value="required">
				<?php
				} ?>
			</div>

		</div>

		<?php
		if ($pretext != '') { ?>
			<hr>
			<div class="attention div-center fs-10"><b class="broun">Примечание:</b> <?= $pretext ?></div>
		<?php
		}
		?>

		<hr>

		<DIV class="button--pane text-right">

			<A href="javascript:void(0)" onclick="$('#Form').trigger('submit')" class="button">Сохранить</A>

		</DIV>
	</FORM>
	<?php
}
if ($action == '') {
	?>

	<h2>&nbsp;Раздел: "Настройка форм"</h2>

	<DIV id="formtabs" style="border:0" class="nomob">

		<UL>
			<LI><A href="#tab-form-1" data-id="client">Контрагенты</A></LI>
			<LI><A href="#tab-form-2" data-id="person">Контакты</A></LI>
		</UL>

		<div id="tab-form-1">

			<div class="tab mb10" style="max-height: 60vh; overflow-x: hidden; overflow-y: auto">

				<table id="table-1" class="rowtable nomob">
					<thead class="hidden-iphone sticked--top">
					<tr class="header_contaner th40">
						<TH class="w100 nodrop">Порядок</TH>
						<th class="w160 nodrop">Системное имя</th>
						<th class="w350 nodrop">Название блока формы</th>
						<TH class="w100 nodrop">Обязательное</TH>
						<th class="w50 nodrop">Вкл.</th>
						<TH class="nodrop"></TH>
					</tr>
					</thead>
					<tbody>
					<?php
					$exclude = [
						'iduser',
						'title',
						'person',
						'clid',
						'mcid',
						'datum_plan'
					];

					$result = $db -> getAll("SELECT * FROM {$sqlname}field WHERE fld_tip='client' and identity = '$identity' order by fld_order");
					foreach ($result as $da) {

						$sub = 'Универсально';
						$css = '';

						if (in_array($da['fld_name'], $exclude) && $da['fld_on'] != 'yes') {

							$db -> query("UPDATE {$sqlname}field SET fld_on = 'yes' WHERE fld_id = '$da[fld_id]' and identity = '$identity'");

						}

						if ($da['fld_required'] == 'required') {
							$req = '<a href="javascript:void(0)" onclick="SwitchReq(\'client\',\''.$da['fld_id'].'\')" title="Отключить"><i class="icon-ok green"></i></a>';
						}
						else {
							$req = '<a href="javascript:void(0)" onclick="SwitchReq(\'client\',\''.$da['fld_id'].'\')" title="Включить"><i class="icon-block-1 gray"></i></a>';
						}

						if (in_array($da['fld_name'], $exclude)) {
							$req = '<i class="icon-ok blue" title="Должно быть заполнено всегда"></i>';
						}

						if ($da['fld_on'] == 'yes') {
							$show = '<a href="javascript:void(0)" onclick="SwitchShow(\'client\',\''.$da['fld_id'].'\')" title="Отключить"><i class="icon-ok green"></i></a>';
						}
						else {
							$show = '<a href="javascript:void(0)" onclick="SwitchShow(\'client\',\''.$da['fld_id'].'\')" title="Включить"><i class="icon-eye-off gray"></i></a>';
							$sub  = '';
							$css  = 'gray';
						}

						if (in_array($da['fld_name'], $exclude)) {
							$show = '<i class="icon-eye blue" title="Должно быть видимо всегда"></i>';
						}

						if (!empty($da['fld_sub'])) {
							$sub = $xtype[$da['fld_sub']];
						}
						/*elseif ($da['fld_stat'] == 'yes'){
							$sub = '';
						}*/

						?>
						<tr class="ha th40 <?= $css ?>" id="<?= $da['fld_id'] ?>">
							<td class="w100 text-center"><?= $da['fld_order'] ?></td>
							<td class="w160">
								<div class="fs-12 Bold gray2 clearevents"><?= $da['fld_name'] ?></div>
							</td>
							<td class="w350 relativ">
								<div class="fs-12 flh-09 gray-dark Bold clearevents"><?= $da['fld_title'] ?></div>
								<div class="fs-10 <?= $xcolor[$da['fld_sub']] ?> Bold clearevents"><?= $sub ?></div>
								<div class="pull-right">
									<A href="javascript:void(0)" onclick="doLoad('content/admin/<?php
									echo $thisfile; ?>?action=edit&fld_id=<?= $da['fld_id'] ?>&tip=client');" title="Изменить" class="gray"><i class="icon-pencil"></i></A>
								</div>
							</td>
							<td class="w100 text-center"><?= $req ?></td>
							<td class="w50 text-center"><?= $show ?></td>
							<td class="text-center"></td>
						</tr>
					<?php
					} ?>
					</tbody>
				</table>

			</div>

			<?php
			if (!$isCloud) { ?>
				<hr>				<a href="javascript:void(0)" onclick="addField('client')" class="button ml10">Добавить поле</a>
			<?php
			} ?>

		</div>
		<div id="tab-form-2">

			<div class="tab mb10" style="max-height: 60vh; overflow-x: hidden; overflow-y: auto">

				<table id="table-2" class="rowtable nomob">
					<thead class="hidden-iphone sticked--top">
					<tr class="header_contaner noDrag">
						<TH class="w100 nodrop">Порядок</TH>
						<th class="w160 nodrop">Системное имя</th>
						<th class="w350 nodrop">Название блока формы</th>
						<TH class="w100 nodrop">Обязательное</TH>
						<th class="w50 nodrop">Вкл.</th>
						<TH class="nodrop"></TH>
					</tr>
					</thead>
					<?php
					$result = $db -> getAll("select * from {$sqlname}field where fld_tip='person' and identity = '$identity' order by fld_order");
					foreach ($result as $da) {

						if ($da['fld_required'] == 'required') {
							$req = '<a href="javascript:void(0)" onclick="SwitchReq(\'person\',\''.$da['fld_id'].'\')" title="Отключить"><i class="icon-ok green"></i></a>';
						}
						else {
							$req = '<a href="javascript:void(0)" onclick="SwitchReq(\'person\',\''.$da['fld_id'].'\')" title="Включить"><i class="icon-eye-off gray"></i></a>';
						}

						if (in_array($da['fld_name'], $exclude)) {
							$req = '<i class="icon-info-circled blue" title="Не управляется"></i>';
						}

						if ($da['fld_on'] == 'yes') {
							$show = '<a href="javascript:void(0)" onclick="SwitchShow(\'person\',\''.$da['fld_id'].'\')" title="Отключить"><i class="icon-ok green"></i></a>';
						}
						else {
							$show = '<a href="javascript:void(0)" onclick="SwitchShow(\'person\',\''.$da['fld_id'].'\')" title="Включить"><i class="icon-eye-off gray"></i></a>';
						}

						if (in_array($da['fld_name'], $exclude)) {
							$show = '<i class="icon-eye blue" title="Должно быть видимо всегда"></i>';
						}
						?>
						<tr class="ha th40" id="<?= $da['fld_id'] ?>">
							<td class="w100 text-center"><?= $da['fld_order'] ?></td>
							<td class="w160">
								<div class="fs-12 Bold gray2 clearevents"><?= $da['fld_name'] ?></div>
							</td>
							<td class="w350 relativ">
								<div class="fs-12 Bold clearevents"><?= $da['fld_title'] ?></div>
								<div class="pull-right">
									<A href="javascript:void(0)" onclick="doLoad('content/admin/<?php
									echo $thisfile; ?>?action=edit&fld_id=<?= $da['fld_id'] ?>&tip=person');" title="Изменить" class="gray"><i class="icon-pencil"></i></A>
								</div>
							</td>
							<td class="w100 text-center"><?= $req ?></td>
							<td class="w50 text-center"><?= $show ?></td>
							<td class="text-center"></td>
						</tr>
					<?php
					} ?>
				</table>

			</div>

			<?php
			if (!$isCloud) { ?>
				<hr>				<a href="javascript:void(0)" onclick="addField('person')" class="button ml10">Добавить поле</a>
			<?php
			} ?>

		</div>

	</DIV>

	<div class="pagerefresh refresh--icon admn orange" onclick="openlink('https://salesman.pro/docs/13')" title="Документация"><i class="icon-help"></i></div>

	<div class="space-100"></div>

	<script>

		$(function () {

			var h = $('#clist').actual('height') - 200;

			<?php
			if ($_REQUEST['tip'] == 'person') {
				$tab = "{active:1}";
			}
			if ($_REQUEST['tip'] == 'dogovor') {
				$tab = "{active:2}";
			}
			?>

			$('#formtabs').tabs(<?=$tab?>);

			$('.tab').css({'max-height': h + 'px'});

			$("#table-1").disableSelection().tableDnD({
				onDragClass: "tableDrag",
				onDrop: function (table, row) {

					var str = '' + $('#table-1').tableDnDSerialize();
					var url = 'content/admin/<?php echo $thisfile; ?>?action=edit.order&';

					$.post(url, str, function (data) {

						$('#contentdiv').load('content/admin/<?php echo $thisfile; ?>?tip=client');
						$('#message').fadeTo(1, 1).css('display', 'block').html(data);

						setTimeout(function () {
							$('#message').fadeTo(1000, 0);
						}, 20000);

					});

				}
			});

			$("#table-2").disableSelection().tableDnD({
				onDragClass: "tableDrag",
				onDrop: function (table, row) {

					var str = '' + $('#table-2').tableDnDSerialize();
					var url = 'content/admin/<?php echo $thisfile; ?>?action=edit.order&';

					$.post(url, str, function (data) {

						$('#contentdiv').load('content/admin/<?php echo $thisfile; ?>?tip=person');
						$('#message').fadeTo(1, 1).css('display', 'block').html(data);

						setTimeout(function () {
							$('#message').fadeTo(1000, 0);
						}, 20000);

					});

				}
			});

		});

	</script>
	<?php
}
?>

<script>
	$(function () {

		if (isMobile) $('#table-1').addClass('rowtable');
		if (isMobile) $('#table-2').addClass('rowtable');

		if ($('#action').val() !== 'edit_on') $('#dialog').css('width', '800px');
		else $('#dialog').css('width', '600px');

		if( $('#fld_temp').is(":visible") ) {
			$('#fld_temp').trigger('change');
		}

		$('#dialog').center();

	});

	$('#Form').ajaxForm({
		beforeSubmit: function () {

			var $out = $('#message');
			var em = checkRequired();

			if (em === false) return false;

			$('#dialog').css('display', 'none');
			$('#dialog_container').css('display', 'none');

			$out.fadeTo(1, 1).css('display', 'block').append('<div id=loader><img src=/assets/images/loader.gif> Загрузка данных. Пожалуйста подождите...</div>');

			$('.refresh--panel').find('.admn').remove();

			return true;

		},
		success: function (data) {

			$('#dialog').css('display', 'none');
			$('#dialog_container').css('display', 'none');

			$("#contentdiv").load('content/admin/<?php echo $thisfile; ?>?tip=<?=$_REQUEST['tip']?>');
			//razdel(hash);

			setTimeout(function () {

				$('.refresh--panel').prepend($('.pagerefresh'));

			}, 500);

			$('#message').fadeTo(1, 1).css('display', 'block').html(data);
			setTimeout(function () {

				$('#message').fadeTo(1000, 0);

			}, 20000);

		}
	});

	$(document).on('change', '#fld_temp', function () {

		var vr = $(this).val();

		if (in_array(vr, ['select', 'multiselect', 'inputlist', 'radio'])) {

			$('#vars').removeClass('hidden');
			$('#varst').addClass('hidden');

		}
		else if (vr === 'textarea') {

			$('#varst').removeClass('hidden');
			$('#vars').addClass('hidden');

		}
		else {

			$('#vars').addClass('hidden');
			$('#varst').addClass('hidden');

		}

		$('#dialog').center();

	});

	function addField(tip) {

		$('#message').empty().fadeTo(1, 1).css('display', 'block').append('<div id="loader"><img src="/assets/images/loader.gif">Пожалуйста подождите...</div>');

		$.get('content/admin/<?php echo $thisfile; ?>?action=addfield&tip=' + tip, function (data) {

			$('#message').fadeTo(1, 1).css('display', 'block').html(data.text);
			setTimeout(function () {
				$('#message').fadeTo(1000, 0);
			}, 20000);

			doLoad('content/admin/<?php echo $thisfile; ?>?action=edit&fld_id=' + data.id + '&tip=' + tip);

			//$('#contentdiv').load('content/admin/<?php echo $thisfile; ?>');
			razdel(hash);

		}, 'json');

	}

	function SwitchShow(tip, id) {

		var url = 'content/admin/<?php echo $thisfile; ?>?action=switchShow&tip=' + tip + '&id=' + id;
		$('.refresh--panel').find('.admn').remove();

		$.post(url, function (data) {

			$("#contentdiv").load('content/admin/<?php echo $thisfile; ?>?tip=' + tip);
			$('#message').fadeTo(1, 1).css('display', 'block').html(data);

			setTimeout(function () {

				$('.refresh--panel').prepend($('.pagerefresh'));

			}, 500);

			setTimeout(function () {
				$('#message').fadeTo(1000, 0);
			}, 20000);

		});

	}

	function SwitchReq(tip, id) {

		var url = 'content/admin/<?php echo $thisfile; ?>?action=switchReq&tip=' + tip + '&id=' + id;
		$('.refresh--panel').find('.admn').remove();

		$.post(url, function (data) {

			$("#contentdiv").load('content/admin/<?php echo $thisfile; ?>?tip=' + tip);
			$('#message').fadeTo(1, 1).css('display', 'block').html(data);

			setTimeout(function () {

				$('.refresh--panel').prepend($('.pagerefresh'));

			}, 500);

			setTimeout(function () {
				$('#message').fadeTo(1000, 0);
			}, 20000);

		});

	}

</script>