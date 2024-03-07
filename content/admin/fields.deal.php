<?php
/* ============================ */
/*         SalesMan CRM         */
/* ============================ */
/* (C) 2018 Vladislav Andreev   */
/*       SalesMan Project       */
/*        www.isaler.ru         */
/*        ver. 2019.x           */
/* ============================ */

error_reporting( E_ERROR );
header( "Pragma: no-cache" );

$rootpath = dirname( __DIR__, 2 );

include $rootpath."/inc/config.php";
include $rootpath."/inc/dbconnector.php";
include $rootpath."/inc/auth.php";
include $rootpath."/inc/func.php";
include $rootpath."/inc/settings.php";

$thisfile = basename( __FILE__ );

$action = $_REQUEST['action'];

//$exclude = array('clid', 'iduser', 'title', 'pid', 'person', 'mcid', 'datum_plan', 'idcategory', 'dog_num', 'direction');
$exclude   = [
	'clid',
	'pid',
	'person',
	/*'marg',*/
	//'period1',
	'iduser'
];
$attention = [
	'marg',
	'kol',
	'kol_fact',
	'oborot',
	'idcategory',
	'direction',
	'tip',
	'title',
	'mcid',
	'payer',
	'datum_plan'
];

if ( $action == 'switchShow' ) {

	$fld_id = $_REQUEST['id'];

	$result = $db -> getRow( "select * from {$sqlname}field where fld_id = '$fld_id' and identity = '$identity'" );
	$fld_on = $result['fld_on'];

	if ( $fld_on == 'yes' ) {
		$fld_on = '';
	}
	else {
		$fld_on = 'yes';
	}

	//Обновляем данные для текущей записи
	$db -> query( "update {$sqlname}field set fld_on = '$fld_on' where fld_id = '$fld_id' and identity = '$identity'" );

	print "Запись обновлена";

	exit();

}
if ( $action == 'switchReq' ) {

	$fld_id = $_REQUEST['id'];

	$result       = $db -> getRow( "select * from {$sqlname}field where fld_id = '$fld_id' and identity = '$identity'" );
	$fld_required = $result['fld_required'];

	if ( $fld_required == 'required' ) {
		$fld_required = '';
	}
	else {
		$fld_required = 'required';
	}

	//Обновляем данные для текущей записи
	if ( $db -> query( "update {$sqlname}field set fld_required = '$fld_required' where fld_id = '$fld_id' and identity = '$identity'" ) ) {
		print "Запись обновлена";
	}


	exit();

}

if ( $action == "edit_on" ) {

	$ttip = [
		'select',
		'multiselect',
		'inputlist',
		'radio'
	];

	$fld_id       = $_REQUEST['fld_id'];
	$fld_tip      = $_REQUEST['fld_tip'];
	$fld_title    = $_REQUEST['fld_title'];
	$fld_required = $_REQUEST['fld_required'];
	$fld_temp     = $_REQUEST['fld_temp'];
	$fld_on       = $_REQUEST['fld_on'];
	$fld_var      = $_REQUEST['fld_var'];
	$fld_vart     = $_REQUEST['fld_vart'];
	$tip          = $fld_tip;

	if ( $fld_temp == 'textarea' ) {
		$fld_var = $fld_vart;
	}

	if ( in_array( $fld_temp, $ttip ) ) {

		$fld_var = str_replace( ["\\r\\n", "\r\n"], ",", $fld_var );

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

	//Обновляем данные для текущей записи
	$db -> query( "UPDATE {$sqlname}field SET ?u WHERE fld_id = '$fld_id' and identity = '$identity'", [
		'fld_title'    => $fld_title,
		'fld_temp'     => $fld_temp,
		'fld_required' => $fld_required,
		'fld_on'       => $fld_on,
		'fld_var'      => $fld_var
	] );

	print "Запись обновлена";


	exit();

}
if ( $action == "edit_order" ) {

	$table1 = explode( ';', implode( ';', $_REQUEST['table-1'] ) );
	$count1 = count( $_REQUEST['table-1'] );
	$err    = 0;

	//Обновляем данные для текущей записи
	if ( $count1 > 0 ) {

		for ( $i = 1; $i < $count1; $i++ ) {

			$db -> query( "update {$sqlname}field set fld_order = '".$i."' where fld_id = '".$table1[ $i ]."' and identity = '$identity'" );

		}

		print "Обновлено";

	}

	exit();

}

/*
 * Только для коробочного варианта
 */
if ( $action == 'addfield' ) {

	$field = [];

	//считаем все доп.поля
	$result = $db -> getAll( "SELECT fld_title,fld_tip,fld_name,fld_required FROM {$sqlname}field WHERE fld_name LIKE '%input%' and fld_tip = 'dogovor' and identity = '$identity'" );
	foreach ( $result as $data ) {

		$field[ $data['fld_name'] ] = (int)preg_replace( "/\D/", "", $data['fld_name'] );

	}

	$last = max( $field );
	$next = (int)$last + 1;

	$db -> query( "ALTER TABLE {$sqlname}dogovor ADD `input".$next."` VARCHAR(512) NULL DEFAULT NULL AFTER `input".$last."`" );


	$order = (int)$db -> getOne( "SELECT MAX(fld_order) FROM {$sqlname}field WHERE identity = '$identity' and fld_tip = 'dogovor'" ) + 1;

	$fieldAdd = [
		"fld_tip"   => 'dogovor',
		"fld_name"  => "input".$next,
		"fld_title" => 'доп.поле',
		"fld_order" => $order,
		"fld_on"  => NULL,
		"fld_required"  => NULL,
		"fld_stat"  => 'no',
		"identity"  => $identity
	];
	$db -> query( "INSERT INTO {$sqlname}field SET ?u", arrayNullClean( $fieldAdd ) );
	$id = $db -> insertId();

	//$action = 'edit';
	$pretext = 'Поле добавлено. Предлагаем задать его параметры.';

	print json_encode_cyr( [
		"text" => $pretext,
		"id"   => $id
	] );

	exit();

}
if ( $action == 'edit' ) {

	$result       = $db -> getRow( "select * from {$sqlname}field where fld_id = '".$_REQUEST['fld_id']."' and identity = '$identity'" );
	$fld_name     = $result["fld_name"];
	$fld_title    = $result["fld_title"];
	$fld_type     = $result["fld_type"];
	$fld_required = $result["fld_required"];
	$fld_on       = $result["fld_on"];
	$fld_tip      = $result["fld_tip"];
	$fld_order    = $result["fld_order"];
	$fld_temp     = $result["fld_temp"];
	$fld_var      = $result["fld_var"];

	$readonly = (in_array( $fld_name, $exclude )) ? "readonly" : "";
	$atsn     = (in_array( $fld_name, $attention )) ? "" : "hidden";
	$vars     = 'hidden';
	$textarea = ($fld_temp == "textarea") ? '' : 'hidden';
	$tips     = (stripos( $fld_name, 'input' ) !== false) ? '' : 'hidden';

	if ( in_array( $fld_temp, [
		"select",
		"multiselect",
		"inputlist",
		"radio"
	] ) ) {

		$vars    = '';
		$fld_var = str_replace( ",", "\n", $fld_var );

	}

	if ( $fld_name == 'mcid' )
		$pretext = 'Поле связано со справочником "Мои компании".';
	elseif ( $fld_name == 'money' )
		$pretext = 'Это не поле, а <b>блок с суммами</b> в форме Сделки.';
	elseif ( $fld_name == 'oborot' )
		$pretext = 'Это не поле, а <b>Название</b> оборота в индикаторах, модуле установки планов.';
	elseif ( $fld_name == 'dog_num' )
		$pretext = 'Это не поле, а <b>блок со списком Договоров</b> у клиента в форме Сделки.';
	elseif ( $fld_name == 'direction' )
		$pretext = 'Это поле связано со справочником "<b>Направления деятельности</b>" вашей компании.';
	elseif ( $fld_name == 'tip' )
		$pretext = 'Это поле связано со справочником "<b>Тип сделок</b>".';
	elseif ( $fld_name == 'idcategory' )
		$pretext = 'Это поле связано со справочником "<b>Этапы сделок</b>".';
	elseif ( $fld_name == 'period' )
		$pretext = 'Это не поле, а блок из 2-х полей типа Дата: "<b>Начало периода</b>" и "<b>Конец периода</b>" в форме сделки. Используется в том числе в <b>Сервисных сделках</b>. Используйте осторожно';

	?>
	<DIV class="zagolovok">Изменение поля</DIV>

	<FORM action="content/admin/<?php echo $thisfile; ?>" method="post" enctype="multipart/form-data" name="Form" id="Form">
		<INPUT type="hidden" name="action" id="action" value="edit_on">
		<INPUT name="fld_id" type="hidden" id="fld_id" value="<?= $_REQUEST['fld_id'] ?>">
		<INPUT name="fld_tip" type="hidden" id="fld_tip" value="<?= $fld_tip ?>">
		<INPUT name="tip" type="hidden" id="tip" value="<?= $_REQUEST['tip'] ?>">

		<div class="row">

			<div class="column12 grid-3 fs-12 right-text gray2 pt10">Название:</div>
			<div class="column12 grid-9">

				<input name="fld_title" id="fld_title" type="text" value="<?= $fld_title ?>" class="wp95" <?= $readonly ?>>
				<div class="<?= $atsn ?> em">
					<b class="red">Внимание:</b> при переименовании этого поля могут возникнуть сложности в понимании некоторых разделов Панели управления, и в целом системы
				</div>

			</div>

		</div>

		<?php if ( stripos( $fld_name, 'input' ) !== false ) { ?>
			<div class="row <?= $tips ?>">

				<div class="column12 grid-3 fs-12 right-text gray2 pt10">Тип поля:</div>
				<div class="column12 grid-9">
					<select name="fld_temp" id="fld_temp">
						<option>--Обычное--</option>
						<option value="hidden" <?php if ( $fld_temp == 'hidden' ) print 'selected' ?>>Скрытое поле</option>
						<option value="inputlist" <?php if ( $fld_temp == 'inputlist' )
							print 'selected' ?>>Поле с вариантами</option>
						<option value="datum" <?php if ( $fld_temp == 'datum' )
							print 'selected' ?>>Дата</option>
						<option value="adres" <?php if ( $fld_temp == 'adres' )
							print 'selected' ?>>Адрес</option>
						<option value="textarea" <?php if ( $fld_temp == 'textarea' )
							print 'selected' ?>>Большой текст</option>
						<option value="select" <?php if ( $fld_temp == 'select' )
							print 'selected' ?>>Список выбора</option>
						<option value="radio" <?php if ( $fld_temp == 'radio' )
							print 'selected' ?>>Одиночный выбор</option>
						<option value="multiselect" <?php if ( $fld_temp == 'multiselect' )
							print 'selected' ?>>Множественный выбор</option>
						<option value="datetime" <?php if ( $fld_temp == 'datetime' )
							print 'selected' ?>>Дата + Время</option>
					</select>
				</div>

			</div>
			<div class="row <?= $vars ?>" id="vars">

				<div class="column12 grid-3 fs-12 right-text gray2 pt10">Варианты выбора:</div>
				<div class="column12 grid-9">
					<textarea name="fld_var" id="fld_var" type="text" style="width:95%; height: 150px;"><?= $fld_var ?></textarea>
					<div class="smalltxt">Каждый вариант начинайте с новой строки</div>
				</div>

			</div>
			<div class="row <?= $textarea ?>" id="varst">

				<div class="column12 grid-3 fs-12 right-text gray2 pt10">Шаблон:</div>
				<div class="column12 grid-9">
					<textarea name="fld_vart" id="fld_vart" type="text" class="wp95" style="height: 250px;"><?= $fld_var ?></textarea>
				</div>

			</div>
			<div class="row">

				<div class="column12 grid-3 fs-12 right-text gray2 pt10"></div>
				<div class="column12 grid-9 fs-12 gray2">
					<label for="fld_required"><input name="fld_required" id="fld_required" type="checkbox" <?php if ( $fld_required == 'required' )
							print "checked" ?> value="required"> Обязательное поле</label>
				</div>

			</div>
		<?php } ?>

		<?php if ( $fld_name == 'adres' ) { ?>
			<div class="row">

				<div class="column12 grid-3 fs-12 right-text gray2 pt10"></div>
				<div class="column12 grid-9 fs-12 gray2">
					<label><input name="fld_required" id="fld_required" type="checkbox" <?php if ( $fld_required == 'required' )
							print "checked" ?> value="required">&nbsp;Обязательное поле</label>
				</div>

			</div>
		<?php } ?>

		<div class="row">

			<div class="column12 grid-3 fs-12 right-text gray2 pt10"></div>
			<div class="column12 grid-9 fs-12 gray2">
				<?php if ( !in_array( $fld_name, $exclude ) && !in_array( $fld_name, $attention ) ) { ?>
					<label for="fld_on"><input name="fld_on" id="fld_on" type="checkbox" <?= $d ?> <?php if ( $fld_on == 'yes' )
							print "checked" ?> value="yes"> Включено</label>
					<?php
				}
				else {
					?>
					<div class="em fs-09 black"><b class="red">Внимание:</b> Это поле всегда должно быть включено</div>
					<input name="fld_on" id="fld_on" type="hidden" value="yes">
				<?php } ?>
			</div>

		</div>

		<?php if ( $pretext != '' ) { ?>
			<hr>
			<div class="attention div-center fs-12"><b class="broun">Примечание:</b> <?= $pretext ?></div>
		<?php } ?>

		<hr>

		<DIV class="button--pane text-right">

			<A href="javascript:void(0)" onclick="$('#Form').trigger('submit')" class="button">Сохранить</A>

		</DIV>
	</FORM>
	<?php
}
if ( $action == '' ) {
	?>

	<h2>&nbsp;Раздел: "Настройка названий полей и блоков модуля Сделки"</h2>

	<div class="tab mb10" style="max-height: 60vh; overflow-x: hidden; overflow-y: auto">

		<table id="table-1" class="rowtable">
			<thead class="hidden-iphone sticked--top">
			<tr class="header_contaner nodrag disable--select Bold th40">
				<th class="w160 nodrop">Системное имя</th>
				<th class="w250 nodrop">Название блока формы</th>
				<TH class="w100 nodrop">Обязательное</TH>
				<th class="w50 nodrop">Вкл.</th>
				<th></th>
			</tr>
			</thead>
			<tbody>
			<?php
			$exclude = [
				'clid',
				'title',
				'pid',
				'person',
				'marg',
				'mcid',
				'iduser',
				'idcategory',
				//'period',
				'kol',
				'direction',
				'kol_fact',
				'tip',
				'oborot',
				'datum_plan',
				'dog_num',
				'pid_list',
				'payer',
				//'content',
				'money'
			];
			$result  = $db -> getAll( "SELECT * FROM {$sqlname}field WHERE fld_tip='dogovor' and fld_name != 'ztitle' and identity = '$identity' order by fld_order" );
			foreach ( $result as $da ) {

				if ( in_array( $da['fld_name'], $exclude ) && $da['fld_on'] != 'yes' ) {

					$db -> query( "UPDATE {$sqlname}field SET fld_on = 'yes' WHERE fld_id = '$da[fld_id]' and identity = '$identity'" );

				}

				$class = '';
				$idd   = '';

				$show = ($da['fld_on'] == 'yes') ? '<a href="javascript:void(0)" onclick="SwitchShow(\''.$da['fld_id'].'\')" title="Отключить"><i class="icon-eye green"></i></a>' : '<a href="javascript:void(0)" onclick="SwitchShow(\''.$da['fld_id'].'\')" title="Включить"><i class="icon-eye-off gray"></i></a>';

				if ( in_array( $da['fld_name'], $exclude ) )
					$show = '<i class="icon-eye blue" title="Должно быть видимо всегда"></i>';

				$req = ($da['fld_required'] == 'required') ? '<a href="javascript:void(0)" onclick="SwitchReq(\''.$da['fld_id'].'\')" title="Отключить"><i class="icon-ok green"></i></a>' : '<a href="javascript:void(0)" onclick="SwitchReq(\''.$da['fld_id'].'\')" title="Включить"><i class="icon-block-1 gray"></i></a>';

				if ( stripos( $da['fld_name'], 'input' ) === false )
					$req = '<i class="icon-ok blue" title="Не управляется"></i>';

				if ( stripos( $da['fld_name'], 'input' ) === false )
					$class = 'nodrag';
				else $idd = 'id = "'.$da['fld_id'].'"';

				if ( in_array( $da['fld_name'], [
					'money',
					'dog_num'
				] ) )
					$class = 'hidden';

				?>
				<tr class="ha <?= $class ?> th40" <?= $idd ?>>
					<td class="w160">
						<div class="fs-12 Bold gray2 clearevents"><?= $da['fld_name'] ?></div>
					</td>
					<td class="w250 relativ">
						<div class="pull-aright">
							<A href="javascript:void(0)" onclick="doLoad('content/admin/<?php echo $thisfile; ?>?action=edit&fld_id=<?= $da['fld_id'] ?>&tip=dogovor');" title="Изменить" class="gray"><i class="icon-pencil"></i></A>
						</div>
						<div class="fs-12 Bold clearevents">
							<?= $da['fld_title'] ?>
						</div>
					</td>
					<td class="w100 text-center"><?= $req ?></td>
					<td class="w50 text-center"><?= $show ?></td>
					<td></td>
				</tr>
				<?php
			}
			?>
			</tbody>
		</table>

	</div>

	<?php if (!$isCloud) { ?>
		<hr>
		<a href="javascript:void(0)" onclick="addField('dogovor')" class="button ml10">Добавить поле</a>
	<?php } ?>

	<hr>

	<div class="infodiv">

		<span><i class="icon-info-circled blue icon-2x pull-left"></i></span>
		<b class="blue uppercase">Помощь</b><br><br>
		<ul>
			<li>Не все поля, предтавленные в данном разделе относятся к формам</li>
			<li>Поле "<b>money</b>" - название блока формы для указания денежных показателей сделки</li>
			<li>Поле "<b>oborot</b>" - используется для названия в индикаторах, модуле установки планов</li>
			<li>Отключение полей "<b>marg</b>" (учет маржи), "<b>period</b>" (период сделки), "<b>zayavka</b>" (поле № заявки) происходит в разделе "Общие настройки"
			</li>
			<!--<li>Отключение поля "<b>direction</b>" (выбор направления деятельности) происходит автоматически, если имеется только одно направление (раздел "Направления")</li>
			<li>Отклюение поля "<b>mcid</b>" (наша компания) происходит автоматически, если имеется только одна собственная компания (раздел "Мои компании и счета")</li>-->
		</ul>

	</div>

	<?php if (!$isCloud) { ?>
	<div class="pagerefresh refresh--icon admn red" onclick="addField('dogovor')" title="Добавить"><i class="icon-plus-circled"></i></div>
	<?php } ?>
	<div class="pagerefresh refresh--icon admn orange" onclick="openlink('https://salesman.pro/docs/31')" title="Документация"><i class="icon-help"></i></div>

	<div class="space-100"></div>

	<script>

		$(function () {

			var h = $('#clist').actual('height') - 170;

			$('.tab').css({'max-height': h + 'px'});

		});

	</script>
	<?php
}
?>
<script>

	$(function () {

		if ($('#action').val() != 'edit_on') $('#dialog').css('width', '800px');
		else $('#dialog').css('width', '600px');

		$('#dialog').center();

	});

	$('#Form').ajaxForm({
		beforeSubmit: function () {

			var $out = $('#message');
			var em = checkRequired();

			if (em === false) return false;

			$('#dialog').css('display', 'none');
			$('#dialog_container').css('display', 'none');

			$out.fadeTo(1, 1).css('display', 'block').append('<div id=loader><img src=/assets/images/loader.gif> Выполняю...</div>');

			return true;

		},
		success: function (data) {

			$('#message').fadeTo(1, 1).css('display', 'block').html(data);

			razdel(hash);

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

	$("#table-1").disableSelection().tableDnD({
		onDragClass: "tableDrag",
		onDrop: function (table, row) {

			var str = '' + $('#table-1').tableDnDSerialize();
			var url = 'content/admin/<?php echo $thisfile; ?>?action=edit_order&';

			$.post(url, str, function (data) {

				//$('#contentdiv').load('content/admin/<?php echo $thisfile; ?>');
				razdel(hash);

				$('#message').fadeTo(1, 1).css('display', 'block').html(data);
				setTimeout(function () {
					$('#message').fadeTo(1000, 0);
				}, 20000);

			});

		}
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

	function SwitchShow(id) {

		var url = 'content/admin/<?php echo $thisfile; ?>?action=switchShow&id=' + id;
		$.post(url, function (data) {

			//$("#contentdiv").load('content/admin/<?php echo $thisfile; ?>');
			razdel(hash);

			$('#message').fadeTo(1, 1).css('display', 'block').html(data);
			setTimeout(function () {
				$('#message').fadeTo(1000, 0);
			}, 20000);

		});

	}

	function SwitchReq(id) {

		var url = 'content/admin/<?php echo $thisfile; ?>?action=switchReq&id=' + id;

		$.post(url, function (data) {

			//$("#contentdiv").load('content/admin/<?php echo $thisfile; ?>');
			razdel(hash);

			$('#message').fadeTo(1, 1).css('display', 'block').html(data);

			setTimeout(function () {
				$('#message').fadeTo(1000, 0);
			}, 20000);

		});

	}

</script>