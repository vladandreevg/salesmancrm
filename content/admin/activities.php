<?php
/* ============================ */
/*         SalesMan CRM         */
/* ============================ */
/* (C) 2016 Vladislav Andreev   */
/*       SalesMan Project       */
/*        www.isaler.ru         */
/*        ver. 2019.x           */
/* ============================ */

error_reporting( E_ERROR );
header( "Pragma: no-cache" );

$rootpath = dirname(__DIR__, 2);

include $rootpath."/inc/config.php";
include $rootpath."/inc/dbconnector.php";
include $rootpath."/inc/auth.php";
include $rootpath."/inc/func.php";
include $rootpath."/inc/settings.php";

$thisfile = basename( __FILE__ );


$action = $_REQUEST['action'];
$id     = (int)$_REQUEST['id'];

if ( $action == "delete.do" ) {

	//название нового типа активности, которому передаем удалемые записи
	$result = $db -> getRow( "SELECT * FROM {$sqlname}activities WHERE id = '".$_REQUEST['newid']."' and identity = '$identity'" );
	$newtip = $result['title'];

	//название старого типа активности, который удаляем
	$result = $db -> getRow( "SELECT * FROM {$sqlname}activities WHERE id = '".$_REQUEST['id']."' and identity = '$identity'" );
	$tip    = $result['title'];

	//обрабатываем все записи
	if ( $db -> query( "update {$sqlname}tasks set tip = '".$newtip."' WHERE tip = '".$tip."' and identity = '$identity'" ) ) {

		//исменяем напоминания
		$db -> query( "update {$sqlname}history set tip = '".$newtip."' WHERE tip = '".$tip."' and identity = '$identity'" );
		//удаляем тип
		$db -> query( "delete from {$sqlname}activities where id = '".$_REQUEST['id']."' and identity = '$identity'" );

		print "Сделано";

	}

	unlink( $rootpath."/cash/".$fpath."settings.all.json" );

	exit();
}
if ( $action == "edit.do" ) {

	$title     = untag($_REQUEST['title']);
	$old_tip   = $_REQUEST['old_tip'];
	$color1    = $_REQUEST['color1'];
	$isDefault = $_REQUEST['isDefault'];
	$filter    = $_REQUEST['filter'];
	$icon      = $_REQUEST['icon'];

	$resultat = str_replace( "\n", ";", (string)$_REQUEST['resultat'] );
	$xvars     = explode( ";", $resultat );

	$vars = [];
	foreach ( $xvars as $var ) {
		$vars[] = trim( $var );
	}

	$resultat = implode( ";", $vars );

	$tipOldName = $db -> getOne( "SELECT title FROM {$sqlname}activities where id='".$id."' and identity = '$identity'" );

	//снимем умолчания
	if ( $isDefault == 'yes' ) {

		$result = $db -> getAll( "SELECT * FROM {$sqlname}activities WHERE identity = '$identity'ORDER by id" );
		foreach ( $result as $datar ) {
			$db -> query( "update {$sqlname}activities set isDefault = '' WHERE id = '".$datar['id']."' and identity = '$identity'" );
		}

	}

	if ( $id == 0 ) {

		$db -> query( "INSERT INTO {$sqlname}activities SET ?u", arrayNullClean([
			'title'     => $title,
			'color'     => $color1,
			'icon'      => $icon,
			'resultat'  => $resultat,
			'isDefault' => $isDefault,
			'filter'    => $filter,
			'identity'  => $identity
		]) );

		print 'Успешно';

	}
	else {

		$db -> query( "UPDATE {$sqlname}activities SET ?u WHERE id = '".$id."' and identity = '$identity'", arrayNullClean([
			'title'     => $title,
			'color'     => $color1,
			'icon'      => $icon,
			'resultat'  => $resultat,
			'isDefault' => $isDefault,
			'filter'    => $filter,
			'identity'  => $identity
		]) );

		$good  = 0;
		$error = 0;
		$all   = 0;

		//проверена работоспособность переименования активностей
		if ( $tipOldName != $title ) {

			$db -> query( "UPDATE {$sqlname}tasks SET tip = '$title' WHERE tip = '$tipOldName' and identity = '$identity'" );
			$db -> query( "UPDATE {$sqlname}history SET tip = '$title' WHERE tip = '$tipOldName' and identity = '$identity'" );

		}

		print 'Успешно обновлено';

	}

	unlink( $rootpath."/cash/".$fpath."settings.all.json" );

	exit();

}

if ( $action == "edit_order" ) {

	$table1 = explode( ';', implode( ';', (array)$_REQUEST['table-1'] ) );

	$count1 = count( $_REQUEST['table-1'] );
	$err    = 0;

	//Обновляем данные для текущей записи
	for ( $i = 1; $i < $count1; $i++ ) {

		if ( !$db -> query( "update {$sqlname}activities set aorder = '".$i."' where id = '".$table1[ $i ]."' and identity = '$identity'" ) ) {
			$err++;
		}

	}

	print "Обновлено. Ошибок: ".$err;

	exit();
}

if ( empty( $_SERVER['HTTP_X_REQUESTED_WITH'] ) && strtolower( $_SERVER['HTTP_X_REQUESTED_WITH'] ) != 'xmlhttprequest' ) {
	print '<div class="bad text-center"><br>Доступ запрещен.<br>Обратитесь к администратору.<br><br></div>';
	exit();
}

if ( $action == "edit" ) {

	$tip       = $db -> getRow( "SELECT * FROM {$sqlname}activities WHERE id = '$id' and identity = '$identity'" );
	$title     = $tip["title"];
	$color1    = $tip["color"];
	$isDefault = $tip["isDefault"];
	$filter    = $tip["filter"];
	$resultat  = str_replace( ";", "\n", $tip["resultat"] );

	if ( !isset( $tip['icon'] ) ) {

		$tip['icon'] = get_ticon( $tip["title"], '', true );

	}

	$icons = [
		'icon-phone-squared',
		'icon-print',
		'icon-users-1',
		'icon-check',
		'icon-doc-text',
		'icon-calendar-empty',
		'icon-mail-alt',
		'icon-chat-1',
		'icon-volume-up',
		'icon-doc-text',
		'icon-graduation-cap-1',
		'icon-article-alt',
		'icon-certificate',
		'icon-handshake-o',
		'icon-lock',
	];
	?>
	<style>
		.ul--group ul {
			border-radius : 4px 4px 4px 4px;
			border-bottom : 1px solid #CCD1D9;
		}
		.ul--group ul li {
			display : inline-block;
		}
		div.color_picker {
			height      : 20px;
			width       : 30px;
			line-height : 16px;
		}
	</style>

	<div class="zagolovok">Добавить/Изменить</div>

	<FORM action="/content/admin/<?php echo $thisfile; ?>" method="POST" name="form" id="form">
		<input type="hidden" id="old_tip" name="old_tip" value="<?= $title ?>">
		<input type="hidden" id="id" name="id" value="<?= $id ?>">
		<input name="action" type="hidden" value="edit.do" id="action">

		<div id="formtabs" style="max-height: 80vh; overflow-y: auto; overflow-x: hidden" class="p5 flex-vertical border--bottom">

			<div class="flex-container mt10">

				<div class="flex-string">Название</div>
				<div class="flex-string">
					<INPUT name="title" type="text" class="wp97" id="title" value="<?= $title ?>">
				</div>

			</div>
			<div class="flex-container mt10">

				<div class="flex-string">Цвет</div>
				<div class="flex-string">
					<input name="color1" type="text" id="color1" value="<?= $color1 ?>" size="7" maxlength="7" class="w30">
				</div>

			</div>
			<div class="flex-container mt10">

				<div class="flex-string">Иконка</div>
				<div class="flex-string">
					<div class="ul--group mb10">
						<ul>
							<?php
							foreach ( $icons as $icon ) {

								print '<li onclick="addStatusItem(\'icon\',\''.$icon.'\');"><span><i class="'.$icon.'"></i></span></li>';

							}
							?>
							<li onclick="window.open('/assets/font/fontello/demo.html')"><span>..</span></li>
						</ul>
					</div>
					<input name="icon" type="text" id="icon" value="<?= $tip['icon'] ?>" class="wp97">
				</div>

			</div>
			<div class="flex-container mt10">

				<div class="flex-string">Где использовать</div>
				<div class="flex-string">
					<select name="filter" id="filter" class="wp97">
						<option value="all" <?php if ( $filter == 'all' )
							print 'selected' ?>>Напоминания и Активности</option>
						<option value="task" <?php if ( $filter == 'task' )
							print 'selected' ?>>Только Напоминания</option>
						<option value="activ" <?php if ( $filter == 'activ' )
							print 'selected' ?>>Только Активности</option>
					</select>
				</div>

			</div>
			<div class="flex-container mt10">

				<div class="flex-string">Результаты</div>
				<div class="flex-string">
					<textarea name="resultat" rows="6" id="resultat" class="wp97"><?= $resultat ?></textarea>
					<div class="smalltxt wp100 gray">варианты, каждый вариант с новой строки (по Enter)</div>
				</div>

			</div>
			<div class="flex-container mt10">

				<div class="flex-string">&nbsp;</div>
				<div class="flex-string">
					<label><input id="isDefault" name="isDefault" type="checkbox" value="yes" <?php if ( $isDefault == 'yes' )
							print 'checked' ?>>&nbsp;Использовать по-умолчанию&nbsp;</label>
				</div>

			</div>

		</div>

		<hr>

		<div class="button--pane text-right">
			<A href="javascript:void(0)" onclick="$('#form').trigger('submit')" class="button">Сохранить</A>
			<A href="javascript:void(0)" onclick="DClose()" class="button">Отмена</A>
		</div>

	</FORM>
	<script type="text/javascript" src="/assets/js/jquery/jquery.colorPicker.js"></script>
	<script>

		$('#dialog').css('width', '700px');
		$('#color1').colorPicker();

		function addStatusItem(txtar, myitem) {

			$('#' + txtar).val(myitem);

		}

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

				//$('#contentdiv').load('content/admin/<?php echo $thisfile; ?>');
				razdel(hash);
				$('#message').html(data).fadeTo(10000, 0);

				DClose();

			}
		});

	</script>
	<?php

	exit();

}
if ( $action == "delete" ) {

	$tip = $db -> getOne( "SELECT title FROM {$sqlname}activities WHERE id = '".$id."' and identity = '$identity'" );

	?>
	<div class="zagolovok">Удалить тип "<?= $tip ?>"</div>

	<FORM action="/content/admin/<?php echo $thisfile; ?>" method="POST" name="form" id="form">
		<input type="hidden" id="id" name="id" value="<?= $id ?>">
		<input name="action" type="hidden" value="delete.do" id="action"/>

		<div class="infodiv">
			В случае удаления, данный тип активности останется в существующих записях и они не будут участвовать в отчетах. Вы можете перевести их на новый тип.
		</div>

		<hr>

		<div id="formtabs" style="overflow-y: auto; max-height: 70vh">

			<div class="flex-container box--child mt10">

				<div class="flex-string wp20 gray2 fs-12 pt7 right-text">Новый тип</div>
				<div class="flex-string wp80 pl10">

					<select name="newid" id="newid" class="required wp97">
						<option value="">--выбрать--</option>
						<?php
						$result_a = $db -> getAll( "SELECT * FROM {$sqlname}activities WHERE id != '$id' and identity = '$identity' ORDER by title" );
						foreach ( $result_a as $dataa ) {
							?>
							<option value="<?= $dataa['id'] ?>"><?= $dataa['title'] ?></option>
						<?php } ?>
					</select>

				</div>

			</div>

		</div>

		<hr>

		<div class="button--pane text-right">
			<A href="javascript:void(0)" onclick="$('#form').trigger('submit')" class="button">Сохранить</A>
			<A href="javascript:void(0)" onclick="DClose()" class="button">Отмена</A>
		</div>

	</FORM>
	<script>

		$('#dialog').css('width', '600px');

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

				$('#contentdiv').load('content/admin/<?php echo $thisfile; ?>');

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

// Функция ввода тем активностей
if ( $action == "editThemes" ) {

	$config = $db -> getOne( "SELECT params FROM {$sqlname}customsettings WHERE tip = 'themesTasks' and identity = '$identity'" );
	$themes = json_decode( (string)$config, true );

	?>
	<div class="zagolovok">Темы активностей</div>
	<FORM action="/content/admin/<?php echo $thisfile; ?>" method="post" enctype="multipart/form-data" name="iform" id="iform" autocomplete="off">

		<input name="action" type="hidden" value="saveThemes" id="action">
		<textarea name="themes" id="themes" style="height:40vh; white-space: pre-line" class="required1 wp100"><?php foreach ( $themes as $i => $theme )
				if ( $theme != '' )
					print $theme.($i < (count( $themes ) - 1) ? "\n" : ""); ?></textarea>

		<hr>

		<div class="infodiv">

			<b class="red">Важно:</b> каждая тема вводится с новой строки (по Enter). Если не указано ниодной темы, система будет предлагать пользователям подсказки из тем существующих напоминаний

		</div>

		<hr>

		<div class="button--pane text-right">

			<A href="javascript:void(0)" onclick="saveThemes()" class="button">Сохранить</A>&nbsp;
			<A href="javascript:void(0)" onclick="DClose()" class="button">Отмена</A>

		</div>

	</form>
	<!-- Обработчик формы-->
	<script>

		$('#dialog').css('width', '60vw');

		function saveThemes() {

			var $out = $('#message');
			var em = checkRequired();

			if (em === false) return false;

			var str = $('#iform').serialize();

			$('#dialog_container').css('display', 'none');

			$.post("/content/admin/<?php echo $thisfile; ?>?action=saveThemes", str, function () {

				yNotifyMe("CRM. Результат, Темы изменены" + ",signal.png");

				DClose();

			});

		}

	</script>
	<?php

	exit();

}
if ( $action == "saveThemes" ) {

	if ( isset( $_REQUEST['themes'] ) ) {

		$text   = yexplode( ";", str_replace( "\r\n", ";", (string)$_REQUEST['themes'] ) );
		$params = json_encode_cyr( $text );

		$id = (int)$db -> getOne( "SELECT id FROM {$sqlname}customsettings WHERE tip = 'themesTasks' and identity = '$identity'" );

		if ( $id > 0 ) {
			$db -> query( "UPDATE {$sqlname}customsettings SET params = '$params' WHERE id = '$id' and identity = '$identity'" );
		}
		else $db -> query( "INSERT INTO {$sqlname}customsettings SET ?u", [
			'params'   => $params,
			'tip'      => 'themesTasks',
			'identity' => $identity
		] );

	}
	else {

		$db -> query( "DELETE FROM {$sqlname}customsettings WHERE tip = 'themesTasks' and identity = '$identity'" );

	}

	unlink( $rootpath."/cash/".$fpath."settings.all.json" );

	exit();

}

/*
Отправить КП клиенту
Подготовить КП клиенту
Провести презентацию
Договориться о встрече
Выставить счет
Подготовить документы
Обсуждение проекта
*/

if ( $action == "" ) {

	$tip = [
		"all"   => '<span class="blue">Универсально</span>',
		"task"  => '<span class="green">Напоминания</span>',
		"activ" => '<span class="broun">Активности</span>'
	];
	?>

	<h2>&nbsp;Раздел: "Редактор тем и типов Активностей"</h2>

	<div class="space-10"></div>

	<div class="success wp100">

		Рекомендуем установить
		<A href="javascript:void(0)" onclick="doLoad('content/admin/<?php echo $thisfile; ?>?action=editThemes')" class="button greenbtn">Темы напоминаний</A>, чтобы удобнее составлять темы для напоминаний. Если темы не установлены, система будет предлагать подходящие темы из существующих напоминаний.

	</div>

	<div class="space-10"></div>

	<TABLE id="table-1" class="top">
		<thead class="hidden-iphone sticked--top">
		<TR class="th30">
			<th class="w250 text-center nodrop"><b>Тип активности</b></th>
			<th class="w50 nodrop">Порядок</th>
			<th class="nodrop">Значения для выбора результата</th>
			<th class="w100 text-center nodrop"><b>Действия</b></th>
		</TR>
		</thead>
		<tbody>
		<?php
		//постраничный вывод записей
		$result = $db -> getAll( "SELECT * FROM {$sqlname}activities WHERE identity = '$identity' ORDER by aorder, title" );
		foreach ( $result as $data ) {
			?>
			<TR class="ha th35" id="<?= $data['id'] ?>">
				<TD>
					<div class="fs-12 Bold">
						<?php echo get_ticon( $data['title'], $data['color'] ); ?>&nbsp;&nbsp;<?= $data['title'] ?>
					</div>
				</TD>
				<td class="text-center"><?= $data['aorder'] ?></td>
				<TD>
					<div class="wp100 tagbox">
						<div class="fs-10 mb10 Bold wp100">
							<?= strtr( $data['filter'], $tip ) ?><?= ($data['isDefault'] == 'yes' ? ' [ <b class="red">По-умолчанию</b> ]' : '') ?>
						</div>
						<?php
						$rs = yexplode( ";", $data['resultat'] );
						foreach ( $rs as $r ) {

							print '<div class="tag">'.$r.'</div>';

						}
						?>
					</div>
				</TD>
				<TD class="w120">

					<A href="javascript:void(0)" onclick="doLoad('content/admin/<?php echo $thisfile; ?>?action=edit&id=<?= $data['id'] ?>')" class="button bluebtn dotted"><i class="icon-pencil"></i></A>
					<A href="javascript:void(0)" onclick="doLoad('content/admin/<?php echo $thisfile; ?>?action=delete&id=<?= $data['id'] ?>')" class="button redbtn dotted"><i class="icon-cancel"></i></A>

				</TD>
			</TR>
			<?php
		}
		?>

		</tbody>
	</TABLE>

	<div class="button--group1 box--child" style="position: fixed; bottom: 40px; left: 380px; z-index: 100;">

		<a href="javascript:void(0)" onclick="doLoad('content/admin/<?php echo $thisfile; ?>?action=edit');" class="button bluebtn box-shadow" title="Добавить"><i class="icon-plus-circled"></i>Добавить</a>

	</div>

	<div class="pagerefresh refresh--icon admn red" onclick="doLoad('content/admin/<?php echo $thisfile; ?>?action=edit');" title="Добавить">
		<i class="icon-plus-circled"></i>
	</div>
	<div class="pagerefresh refresh--icon admn orange" onclick="openlink('https://salesman.pro/docs/37')" title="Документация">
		<i class="icon-help"></i>
	</div>

	<div class="space-100"></div>


<?php } ?>

<script>

	$(function () {

		$('#message2').fadeOut(5000);

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
	});

</script>
