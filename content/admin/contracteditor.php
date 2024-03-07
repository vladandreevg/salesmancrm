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

$rootpath = dirname( __DIR__, 2 );

include $rootpath."/inc/config.php";
include $rootpath."/inc/dbconnector.php";
include $rootpath."/inc/auth.php";
include $rootpath."/inc/func.php";
include $rootpath."/inc/settings.php";

$thisfile = basename( __FILE__ );

$action = $_REQUEST['action'];
$id     = (int)$_REQUEST['id'];

$uploaddir = $rootpath.'/cash/'.$fpath.'templates/';

if ($action == "delete") {

	$db -> query("DELETE FROM {$sqlname}contract_type WHERE id = '$id' AND identity = '$identity'");

	$action = false;

}

if ($action == "edit.on") {

	$title  = $_REQUEST['title'];
	$type   = $_REQUEST['type'];
	$role   = implode(",", (array)$_REQUEST[ 'role']);
	$users  = implode(",", (array)$_REQUEST[ 'users']);
	$num    = $_REQUEST['doc_num'];
	$format = $_REQUEST['doc_format'];

	if ($type == 'get_dogovor' || $type == 'get_akt') {

		$num    = '';
		$format = '';

	}

	if($id > 0)
		$db -> query("UPDATE {$sqlname}contract_type SET ?u WHERE id = '$id' and identity = '$identity'", arrayNullClean([
		'title'  => $title,
		'type'   => $type,
		'role'   => $role,
		'users'  => $users,
		'num'    => $num,
		'format' => $format
		]));

	else
		$db -> query("INSERT INTO {$sqlname}contract_type SET ?u", arrayNullClean([
			'title'    => $title,
			'type'     => $type,
			'role'     => $role,
			'users'    => $users,
			'num'      => $num,
			'format'   => $format,
			'identity' => $identity
		]));

	print 'Сделано';

	exit();

}

if ($action == "edit") {

	$count1 = $db -> getOne("SELECT COUNT(*) as count FROM {$sqlname}contract_type WHERE type IN('get_akt') and identity = '$identity'");
	$count2 = $db -> getOne("SELECT COUNT(*) as count FROM {$sqlname}contract_type WHERE type IN('get_aktper') and identity = '$identity'");

	if($id > 0) {

		$result     = $db -> getRow("SELECT * FROM {$sqlname}contract_type where id = '$id' and identity = '$identity'");
		$title      = $result["title"];
		$role       = explode(",", $result["role"]);
		$type       = $result["type"];
		$doc_num    = $result["num"];
		$doc_format = $result["format"];
		$users      = explode(",", $result["users"]);

	}
	else $id = 0;

	$cont = '';

	if (in_array($type, [
		'get_dogovor',
		'get_akt',
		'get_aktper'
	])) $cont = 'hidden';

	?>
	<div class="zagolovok">Редактирование</div>
	<form method="post" action="content/admin/<?php echo $thisfile; ?>" enctype="multipart/form-data" name="form" id="form" autocomplete="off">
		<input name="id" type="hidden" id="id" value="<?= $id ?>">
		<input type="hidden" id="action" name="action" value="edit.on">

		<div class="row">

			<div class="column12 grid-3 fs-12 pt10 right-text">Название:</div>
			<div class="column12 grid-9">
				<INPUT name="title" type="text" class="wp97" id="title" value="<?= $title ?>">
			</div>

			<div class="column12 grid-3 fs-12 pt10 right-text">Тип в системе:</div>
			<div class="column12 grid-9">
				<SELECT name="type" id="type" class="w250" onchange="changeVis()">
					<OPTION value="" <?php if ($type == "") print "selected" ?>>--прочее--</OPTION>
					<OPTION value="get_akt" <?php if ($type == "get_akt") print "selected" ?> <?php if ($count1 > 0) print "disabled"; ?>>Акт приема-передачи</OPTION>
					<OPTION value="get_aktper" <?php if ($type == "get_aktper") print "selected" ?> <?php if ($count2 > 0) print "disabled"; ?>>Акт ежемесячный</OPTION>
					<OPTION value="get_dogovor" <?php if ($type == "get_dogovor") print "selected" ?>>Договор</OPTION>
				</SELECT>
			</div>

			<div class="column12 grid-3 fs-12 pt10 right-text">Счетчик:</div>
			<div class="column12 grid-9">
				<input name="doc_num" type="text" id="doc_num" size="10" value="<?= $doc_num ?>">
				<div class="smalltxt">Укажите номер последнего документа.<b> Если их не было укажите 0 (ноль)</b>.</div>
			</div>

			<div class="column12 grid-3 fs-12 pt10 right-text">Формат номера:</div>
			<div class="column12 grid-9">

				<div class="ul--group">
					<ul>
						<li onclick="addItem('doc_format','cnum');"><span>Номер</span></li>
						<li onclick="addItem('doc_format','DD');"><span>День</span></li>
						<li onclick="addItem('doc_format','MM');"><span>Месяц</span></li>
						<li onclick="addItem('doc_format','YY');"><span>Год (2 цифры)</span></li>
						<li onclick="addItem('doc_format','YYYY');"><span>Год (4 цифры)</span></li>
					</ul>
				</div>
				<input name="doc_format" type="text" id="doc_format" size="50" value="<?= $doc_format ?>">

			</div>

			<hr>

			<div class="column12 grid-3 fs-12 pt10 right-text">Доступ для ролей:</div>
			<div class="column12 grid-9">

				<div class="flex-container mb5">

					<div class="ellipsis wp40 flex-string">
						<label><input name="role[]" type="checkbox" id="role[]" value="Руководитель организации" <?php if (in_array('Руководитель организации', (array)$role)) print "checked" ?> />&nbsp;Руководитель организации</label>
					</div>
					<div class="ellipsis wp40 flex-string">
						<label><input name="role[]" type="checkbox" id="role[]" value="Руководитель с доступом" <?php if (in_array('Руководитель с доступом', (array)$role)) print "checked" ?> />&nbsp;Руководитель с доступом</label>
					</div>

				</div>
				<div class="flex-container mb5">

					<div class="ellipsis wp40 flex-string">
						<label><input name="role[]" type="checkbox" id="role[]" value="Руководитель подразделения" <?php if (in_array('Руководитель подразделения', (array)$role)) print "checked" ?> />&nbsp;Руководитель подразделения</label>
					</div>
					<div class="ellipsis wp40 flex-string">
						<label><input name="role[]" type="checkbox" id="role[]" value="Руководитель отдела" <?php if (in_array('Руководитель отдела', (array)$role)) print "checked" ?> />&nbsp;Руководитель отдела</label>
					</div>

				</div>
				<div class="flex-container mb5">

					<div class="ellipsis wp40 flex-string">
						<label><input name="role[]" type="checkbox" id="role[]" value="Менеджер продаж" <?php if (in_array('Менеджер продаж', (array)$role)) print "checked" ?> />&nbsp;Менеджер продаж</label>
					</div>
					<div class="ellipsis wp40 flex-string">
						<label><input name="role[]" type="checkbox" id="role[]" value="Поддержка продаж" <?php if (in_array('Поддержка продаж', (array)$role)) print "checked" ?> />&nbsp;Поддержка продаж</label>
					</div>

				</div>
				<div class="flex-container mb5">

					<div class="ellipsis wp40 flex-string">
						<label><input name="role[]" type="checkbox" id="role[]" value="Администратор" <?php if (in_array('Администратор', (array)$role)) print "checked" ?> />&nbsp;Администратор</label>
					</div>

				</div>

			</div>

			<div class="column12 grid-3 fs-12 pt10 right-text">или Доступ для сотрудников:</div>
			<div class="column12 grid-9">
				<SELECT name="users[]" id="users[]" multiple="multiple" class="multiselect">
					<?php
					$result = $db -> getAll("SELECT * FROM {$sqlname}user where secrty='yes' and identity = '$identity' ORDER by title ".$userlim);
					foreach ($result as $data) {

						$s = (in_array($data['iduser'], (array)$users)) ? "selected" : "";
						print '<OPTION value="'.$data['iduser'].'" '.$s.'>'.$data['title'].'</OPTION>';

					}
					?>
				</SELECT>
			</div>

		</div>

		<hr>

		<div class="button--pane text-right">

			<A href="javascript:void(0)" onclick="$('#form').trigger('submit')" class="button">Сохранить</A>&nbsp;
			<A href="javascript:void(0)" onclick="DClose()" class="button">Отмена</A>

		</div>

	</FORM>
	<?php
}

if (!$action) {

	$typea = [
		"get_dogovor" => "Договор",
		"get_akt"     => "Акт приема-передачи",
		"get_aktper"  => "Акт ежемесячный"
	];

	//шаблоны документов, кроме счетов
	$templates   = [];
	$result = $db -> getAll( "SELECT * FROM {$sqlname}contract_temp WHERE typeid NOT IN (SELECT id FROM {$sqlname}contract_type WHERE COALESCE(type, '') IN ('invoice','get_akt','get_aktper') AND identity = '$identity') AND identity = '$identity' ORDER by title" );
	foreach ( $result as $data ) {

		$templates[ $data['typeid'] ][] = [
			"id"    => $data['id'],
			"title" => $data['title'],
			"file"  => $data['file'],
		];

	}

	//print_r($templates);

	?>
	<h2>&nbsp;Раздел: "Типы документов"</h2>

	<?php
	if(!$otherSettings['contract']){

		print '<div class="warning mb10">Ведение Договоров <b>отключено</b> (см. Общие настройки / Дополнения к сделкам)</div>';

	}
	?>

	<div class="flex-container float box--child p10 no-border fs-11 graybg Bold hidden-iphone sticked--top">

		<div class="flex-string w350">Название</div>
		<div class="flex-string w120 hidden-iphone">Действие</div>
		<div class="flex-string float hidden-iphone">Ограничения</div>

	</div>

	<?php
	//выводим типы кроме счетов
	$resultt = $db -> getAll("SELECT * FROM {$sqlname}contract_type WHERE COALESCE(type, '') NOT IN ('invoice') AND identity = '$identity' ORDER by title");
	foreach ($resultt as $data) {

		//print $data['type']."; ";

		$users = yexplode(",", $data['users']);
		$u     = [];
		$str   = '';

		foreach ($users as $user) {
			$u[] = current_user((int)$user);
		}

		if ($data['role'] != '' || $data['users'] != '') {

			$roles = yexplode(",", $data['role']);
			$users = yexplode(",", $data['users']);

			$str = '
			<div class="viewdiv">
				'.(!empty($roles) != '' ? '<div class="tagbox"><div class="blue Bold mb5 pl5">Роли:</div>&nbsp;'.yimplode("", $roles, '<div class="tag">', '</div>').'</div>' : '').'
				'.(!empty($users) != '' ? '<div class="tagbox"><div class="blue Bold mb5 pl5">Сотрудники:</div>&nbsp;'.yimplode("", $u, '<div class="tag">', '</div>').'</div>' : '').'
			</div>';

		}

		print '
			<div class="flex-container float box--child p10 border-bottom relativ ha">
		
				<div class="flex-string w350">
					<span class="fs-12 Bold gray-dark">ID '.$data['id'].':</span>&nbsp;<span class="fs-12 Bold">'.$data['title'].'</span>&nbsp;
					'.($data['type'] != '' ? '<div class="blue fs-09 mt5">'.strtr($data['type'], $typea).'</div>' : '<div class="green mt5 fs-09">Собственный</div>').'
					'.($data['format'] ? '<div class="gray2 fs-09 mt5">Шаблон номера: <b>'.$data['format'].'</b></div>' : '').'
				</div>
				<div class="flex-string w120 hidden-iphone">
				
					<A href="javascript:void(0)" onclick="doLoad(\'content/admin/'.$thisfile.'?action=edit&id='.$data['id'].'\');" class="button dotted bluebtn"><i class="icon-pencil"></i></A>
					<A href="javascript:void(0)" onclick="cf=confirm(\'Вы действительно хотите удалить запись?\');if (cf)refresh(\'contentdiv\',\'content/admin/'.$thisfile.'?id='.$data['id'].'&action=delete\');" class="button dotted redbtn"><i class="icon-cancel-circled"></i></A>
					
				</div>
				<div class="flex-string float">
					'.$str.'
				</div>
		
			</div>
		';

		$str = '';

		foreach ( $templates[$data['id']] as $template ) {

			$str .= '
			<div class="p10 border-bottom relativ ha fs-09">
				
				<span class="Bold gray-dark">ID '.$template['id'].':</span>&nbsp;<span class="Bold pt10">'.$template['title'].'</span>&nbsp;
				
				<span class="black">
					[ <a href="cash/'.$fpath.'templates/'.$template['file'].'" title="Загрузить">'.$template['file'].' <i class="icon-attach-1 blue"></i></a>
					<span class="fs-07 blue">
						'.num_format( round( filesize( $uploaddir.$template['file'] ) / 1024, 2 ) ).'&nbsp;Kb / '.date( "d-m-Y", filemtime( $uploaddir.$template['file'] ) ).'
					</span> ]
				</span>

				<A href="javascript:void(0)" onclick="doLoad(\'content/admin/contracttemplate.php?action=edit&id='.$template['id'].'\');" class="gray pl10" title="Редактировать"><i class="icon-pencil"></i></A>
				<A href="javascript:void(0)" onclick="cf=confirm(\'Вы действительно хотите удалить запись?\');if (cf)deleteTemplate(\''.$template['id'].'\');" class="pull-right pr10" title="Удалить"><i class="icon-cancel-circled red"></i></A>
				
		
			</div>
			';

		}

		//if($str != ''){

			print '
				<div class="infodiv dotted p5 mb10">
					<div class="Bold pt5 pb5">Шаблоны. '.$data['title'].'</div>
					<div class="bgwhite">'.$str.'</div>
					<div class="mt5 fs-07">
						<a href="javascript:void(0)" onclick="doLoad(\'content/admin/contracttemplate.php?action=edit&idtype='.$data['id'].'\');" class="button dotted bluebtn p5 box-shadow" title="Добавить"><i class="icon-plus-circled"></i>Добавить</a>
					</div>
				</div>
			';

		//}

	}

	if ($_REQUEST['t'] == '') {
		?>

		<div class="button--group1 box--child" style="position: fixed; bottom: 40px; left: 380px; z-index: 100;">

			<a href="javascript:void(0)" onclick="doLoad('content/admin/<?php echo $thisfile; ?>?action=edit');" class="button bluebtn box-shadow" title="Добавить"><i class="icon-plus-circled"></i>Добавить</a>

		</div>

		<div class="pagerefresh refresh--icon admn green" onclick="doLoad('content/admin/<?php echo $thisfile; ?>?action=edit');" title="Добавить"><i class="icon-plus-circled"></i></div>
		<div class="pagerefresh refresh--icon admn orange" onclick="openlink('https://salesman.pro/docs/9')" title="Документация"><i class="icon-help"></i></div>

		<div class="space-100"></div>
		<?php
	}

}
?>
<script>

	var action = $('#action').val();

	if (!isMobile) $('#dialog').css('width', '850px');

	$(".multiselect").multiselect({sortable: true, searchable: true});
	$(".connected-list").css('height', "100px");

	$(function () {

		if (in_array(action, ['edit.on']))
			changeVis();

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

				//$("#contentdiv").load('content/admin/<?php echo $thisfile; ?>');
				razdel(hash);

				$('#message').fadeTo(1, 1).css('display', 'block').html(data);

				setTimeout(function () {
					$('#message').fadeTo(1000, 0);
				}, 20000);

				DClose();

			}
		});

		$('#dialog').center();

	});

	function addItem(txtar, myitem) {

		var el = $('#' + txtar);
		var textt = el.val();

		if (!el.attr('disabled'))
			el.val(textt + '{' + myitem + '}');

	}

	function changeVis() {

		var tip = $('#type option:selected').val();

		if (in_array(tip, ['get_dogovor', 'get_akt', 'get_aktper'])) {

			/*
			if(tip === 'get_dogovor') {

				$('#doc_num').prop('disabled', true);
				$('#doc_format').prop('disabled', true);

			}
			else{

				$('#doc_num').removeAttr('disabled');
				$('#doc_format').removeAttr('disabled');

			}
			*/
			$('#doc_num').prop('disabled', true);
			$('#doc_format').prop('disabled', true);

			$('.contract').addClass('hidden');
			$('#dialog').center();

		} else {

			$('#doc_num').removeAttr('disabled');
			$('#doc_format').removeAttr('disabled');

			$('.contract').removeClass('hidden');
			$('#dialog').center();

		}

	}

	function deleteTemplate(id){

		$.get("content/admin/contracttemplate.php?action=delete&face=other&id=" + id, function (data){

			$('#message').fadeTo(1, 1).css('display', 'block').html(data);
			razdel(hash);

		})

	}

</script>