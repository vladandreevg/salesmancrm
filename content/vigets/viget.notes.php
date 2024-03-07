<?php
/* ============================ */
/*         SalesMan CRM         */
/* ============================ */
/* (C) 2016 Vladislav Andreev   */
/*       SalesMan Project       */
/*        www.isaler.ru         */
/*        ver. 2018.x           */
/* ============================ */

error_reporting( E_ERROR );
header( "Pragma: no-cache" );

$rootpath = dirname( __DIR__, 2 );

include $rootpath."/inc/config.php";
include $rootpath."/inc/dbconnector.php";
include $rootpath."/inc/auth.php";
include $rootpath."/inc/func.php";
include $rootpath."/inc/settings.php";
include $rootpath."/inc/language/".$language.".php";

$thisfile = basename( __FILE__ );

$identity = $GLOBALS['identity'];
$sqlname  = $GLOBALS['sqlname'];
$iduser1  = $GLOBALS['iduser1'];
$db       = $GLOBALS['db'];

$action   = $_REQUEST['action'];
$id       = $_REQUEST['note'];
$text     = $_REQUEST['text'];
$pinCheck = $_REQUEST['pin'];

if ( !$action )
	$action = 'list';

///если таблицы нет, то создаем её
$da = $db -> getCol( "SELECT COUNT(*) as count FROM INFORMATION_SCHEMA.STATISTICS WHERE table_schema = '$database' and TABLE_NAME = '{$sqlname}notes'" );
if ( $da[0] == 0 ) {

	$db -> query( "
			CREATE TABLE `{$sqlname}notes` (
				`id` INT(20) NOT NULL AUTO_INCREMENT,
				`date` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'дата создания заметки',
				`author` INT(30) NOT NULL DEFAULT '0' COMMENT 'id пользователя, создавшего заметку',
				`pin` int(1) NOT NULL COMMENT 'признак важности заметки',
				`text` VARCHAR(180) NOT NULL COMMENT 'Текст заметки',
				`identity` INT(30) NOT NULL DEFAULT '1' COMMENT 'идентификатор аккаунта (id записи в таблице settings)',
				PRIMARY KEY (`id`),
				UNIQUE INDEX `id` (`id`)
			)
			COMMENT='База заметок пользователей'
			COLLATE='utf8_general_ci'
			ENGINE=InnoDB
		" );
}

// Вывод заметок

if ( $action == 'list' ) {

	// Формирование списка заметок

	$result = $db -> getAll( "SELECT * FROM ".$sqlname."notes WHERE identity = '$identity' ORDER BY pin DESC, date DESC" );

	$count = $db -> getOne( "SELECT COUNT(*) FROM ".$sqlname."notes WHERE identity = '$identity'" );

	if ( $count > 0 ) {
		?>

		<TABLE id="bborder">
			<thead class="hidden">
			<tr>
				<th width="60">Дата</th>
				<th>Содержание</th>
				<th width="80">Автор</th>
			</tr>
			</thead>
			<tbody>

			<?php

			foreach ( $result as $note ) {

				$user = current_user( $note['author'] );

				if ( $note['pin'] == '1' ) {

					$pin       = '<b><i class="icon-star red" title="Закреплено"></i>';
					$closePin  = '</b>';
					$colorNote = 'style="background: rgba(0,255,0,0.2)"';
				}
				else {
					$pin       = '';
					$closePin  = '';
					$colorNote = '';
				}
				?>

				<TR class="ha" height="30" id="note <?= $note['id'] ?>" <?= $colorNote ?>>
					<TD width="30" align="center" valign="top" title="<?= get_sfdate3( $note['date'] ) ?>">
						<div class="smalltxt"><?= get_sfdate( $note['date'] ) ?>

					</TD>

					<TD width="500" valign="top" title="<?= $note['text'] ?>">
						<a href="javascript:void(0)" onClick="viewNote('<?= $note['id'] ?>')">

							<span class="ellipsis margbot5">

								<?= $pin ?><?= $note['text'] ?><?= $closePin ?>

							</span>
							<br>
							<span class="gray2 em fs-09 pt10"><i class="icon-user-1 blue"></i><?= $user ?></span>
						</a>
					</TD>

					<TD width="5" valign="top">

						<div class="action--container">

							<div class="action--block">

								<?php

								// Ищем подчиненных
								$people = get_people( $iduser1, "yes", true );

								// Проверяем пользователя на Администраторские права
								$r       = $db -> getOne( "SELECT isadmin FROM ".$sqlname."user WHERE iduser = '$iduser1'" );
								$isadmin = $r;

								if ( in_array( $note['author'], $people ) || $isadmin == 'on' || $note['author'] == $iduser1 ) {

									// Отображаем кнопки, если редактирование разрешено

									if ( $note['pin'] == '1' ) { ?>
										<a href="javascript:void(0)" onclick="editPin('<?= $note['id'] ?>');" title="Открепить" class="gray orange"><i class="icon-star red"></i></a>
										<?php
									}
									else {
										?>
										<a href="javascript:void(0)" onclick="editPin('<?= $note['id'] ?>');" title="Закрепить" class="gray orange"><i class="icon-star-empty red"></i></a>
										<?php
									}
									?>
									<a href="javascript:void(0)" onClick="updateNote('<?= $note['id'] ?>');" title="Изменить" class="gray blue"><i class="icon-pencil"></i></a>


									<a href="javascript:void(0)" onClick="deleteNote('<?= $note['id'] ?>')" title="Удалить" class="gray red"><i class="icon-trash"></i></a>

								<?php } ?>
							</div>

						</div>

					</TD>

				</TR>
				<?php
			}
			?>
			</tbody>
		</TABLE>

		<?php
	}

	else {

		print 'Заметки еще не добавлялись';

	}

}


// Добавление новой заметки

if ( $action == 'add' ) {

	$count = $db -> getOne( "SELECT COUNT(*) FROM ".$sqlname."notes WHERE author='$iduser1' and identity = '$identity'" );

	if ( $count < 5 ) {
		?>

		<div id="resultdiv" class="addNote">
			<div class="zagolovok">Добавление заметки</div>
			<FORM action="content/vigets/viget.notes.php" method="post" enctype="multipart/form-data" name="addForm" id="addForm" autocomplete="off">
				<textarea name="text" id="text" style="width:98%; height:150px; white-space: pre-line" maxlength="180" autofocus class="required"></textarea>
				<hr>

				<div class="flex-string wp80 pl10">
					<div class="checkbox">
						<label>
							<input name="editpin" value="yes" type="checkbox" class="checkpin">
							<span class="custom-checkbox"><i class="icon-ok"></i></span>
							&nbsp;Закрепить заметку
							&nbsp;<i class="icon-info-circled blue" title="Закрепленная заметка имеет повышенный приоритет."></i>
							<input name="pin" id="pin" type="text" class="editpin hidden">
						</label>
					</div>
				</div>

				<hr>
				<div class="button--pane text-right">
					<A href="#" onClick="saveNote()" class="button">Добавить</A>&nbsp;
					<A href="#" onClick="DClose()" class="button">Отмена</A>
				</div>
			</FORM>
		</div>

		<script>

			function saveNote() {

				var em = 0;

				$(".required").removeClass("empty").css({"color": "inherit", "background": "#FFF"});
				$(".required").each(function () {

					if ($(this).val() === '') {
						$(this).addClass("empty").css({"color": "red", "background": "#FF8080"});
						em = em + 1;
					}

				});

				if (em > 0) {

					alert("Введите текст заметки!");

				}

				if (em === 0) {

					var str = $('#addForm').serialize();

					$('#dialog_container').css('display', 'none');

					$.post("content/vigets/viget.notes.php?action=edit", str, function (data) {

						yNotifyMe("CRM. Результат, Заметка добавлена" + ",signal.png");

						$('#notes').load("content/vigets/viget.notes.php?action=list").append('<div id="loader"><img src="/assets/images/loading.svg"></div>');

						DClose();

					});

				}

			}
		</script>
		<?php
	}
	else {

		?>
		<script>

			DClose();

			Swal.fire({
					title: 'Ошибка',
					text: "Вы исчерпали лимит для добавления заметок!",
					type: 'error',
					showCancelButton: false,
					confirmButtonColor: '#32CD32',
				}
			);

		</script>
		<?php
	}
}

// Вывод информации о заметке

if ( $action == 'view' ) {

	// Формируем данные для окна информации о заметке
	$result = $db -> getRow( "SELECT * FROM ".$sqlname."notes WHERE id='$id' and identity = '$identity'" );

	$user = current_user( $result['iduser'] );

	if ( $result['pin'] == '1' ) {

		$color = 'style="background:firebrick"';

		$html .= '
	<div class="flex-container box--child">
		<div class="flex-string right-text"><i class="icon-star red" title="Закреплено"></i>Закреплено</div>
	</div>
	';
	}

	$html .= '
	<div class="flex-container box--child mt10 mb15">
	
		<div class="flex-string wp5 gray2 fs-12 right-text">Дата:</div>
		<div class="flex-string wp80 fs-12 pl10">'.get_sfdate( $result['date'] ).'</div>
	</div>
	';

	$html .= '
	<div class="flex-container box--child mt10 mb15">
		<div class="flex-string wp5 gray2 fs-12 right-text">Автор:</div>
		<div class="flex-string wp80 fs-12 pl10">'.current_user( $result['author'] ).'</div>
	</div>
	';

	$html .= '
	<hr>
	<div class="flex-container box--child mt10 mb15">
		<div class="flex-string wp5 gray2 fs-12 right-text">Текст:</div>
		<div class="flex-string wp80 fs-11 flh-12 pl10"><div class="viewdib bgwhite p10">'.link_it( nl2br( $result['text'] ) ).'</div></div>
	</div>
	';
	?>
	<!-- Просмотр полной информации о заметке -->
	<div id="resultdiv" class="viewNote">

		<div class="zagolovok" <?= $color ?> ><B>Просмотр заметки</B></div>

		<div class="box--child" style="max-height: 70vh; overflow-y:auto !important; overflow-x:hidden"><?= $html ?></div>

		<div align="right">
			<A href="#" onClick="updateNote('<?= $result['id'] ?>')" class="button"><i class="icon-pencil"></i>&nbspИзменить</A>&nbsp;
			<a href="#" onClick="DClose()" class="button" <?= $color ?>>Закрыть</a>
		</div>

	</div>

	<?php
}

// Изменение заметки

if ( $action == 'update' ) {

	// Формируем данные для окна информации о заметке
	$result = $db -> getRow( "SELECT * FROM ".$sqlname."notes WHERE id='$id' and identity = '$identity'" );

	$user = current_user( $result['iduser'] );

	$html .= '
	<div class="hidden"><input type="text" name="note" id="note" value="'.$id.'" ></div>
	';

	$html .= '
	<div class="flex-container box--child mt10 mb15">
	
		<div class="flex-string wp5 gray2 fs-12 right-text">Дата:</div>
		<div class="flex-string wp80 fs-12 pl10">'.get_sfdate( $result['date'] ).'</div>
	</div>
	';

	$html .= '
	<div class="flex-container box--child mt10 mb15">
		<div class="flex-string wp5 gray2 fs-12 right-text">Автор:</div>
		<div class="flex-string wp80 fs-12 pl10">'.current_user( $result['author'] ).'</div>
	</div>
	';

	if ( $result['pin'] == '1' ) {

		$color = 'style="background:firebrick"';

		$pined = '<input name="editpin" checked="checked" value="yes" type="checkbox" id="editpin" class="checkpin">
					<span class="custom-checkbox"><i class="icon-ok"></i></span>
					<span id="checkText"> Закреплена</span>
					<input name="pin" id="pin" type="text" class="editpin hidden">';

	}
	else {
		$pined = '<input name="editpin" value="yes" type="checkbox" class="checkpin">
					<span class="custom-checkbox"><i class="icon-ok"></i></span>
					<span id="checkText"> Откреплена</span>
					<input name="pin" id="pin" type="text" class="editpin hidden">';

	}

	$html .= '
	<div class="flex-container box--child mt10 mb15">
	<div class="flex-string wp5 gray2 fs-12 right-text"></div>
		<div class="flex-string wp80 fs-11 pl10">
			<div class="checkbox">
				<label>
					'.$pined.'
				</label>
			</div>
		</div>
	</div>
	';


	$html .= '
	<hr>
	<div class="flex-container box--child mt10 mb15">
		<div class="flex-string wp5 gray2 fs-12 right-text">Текст:</div>
		<div class="flex-string wp80 fs-11 flh-12 pl10"><textarea name="text" id="text" style="width:98%; height:150px; white-space: pre-line" maxlength="180" autofocus required class="required">
		'.$result['text'].'</textarea></div>
	</div>
	';
	?>
	<!-- Редактирование заметки -->
	<div id="resultdiv" class="updateNote">

		<div class="zagolovok" <?= $color ?> ><B>Редактирование заметки</B></div>
		<FORM action="content/vigets/viget.notes.php" method="post" enctype="multipart/form-data" name="editForm" id="editForm" autocomplete="off">
			<div class="box--child" style="max-height: 70vh; overflow-y:auto !important; overflow-x:hidden"><?= $html ?></div>

			<div class="button--pane text-right">
				<A href="javascript:void(0)" onClick="saveEdit()" class="button">Сохранить</A>&nbsp;
				<a href="javascript:void(0)" onClick="DClose()" class="button">Отмена</a>
			</div>
		</FORM>
	</div>

	<script>

		function saveEdit() {

			var em = 0;

			$(".required").removeClass("empty").css({"color": "inherit", "background": "#FFF"});
			$(".required").each(function () {

				if ($(this).val() === '') {
					$(this).addClass("empty").css({"color": "red", "background": "#FF8080"});
					em = em + 1;
				}

			});

			if (em > 0) {

				alert("Введите текст заметки!");

			}

			if (em === 0) {

				var str = $('#editForm').serialize();

				$('#dialog_container').css('display', 'none');

				$.post("content/vigets/viget.notes.php?action=edit", str, function (data) {

					yNotifyMe("CRM. Результат, Заметка изменена" + ",signal.png");

					$('#notes').load("content/vigets/viget.notes.php?action=list").append('<div id="loader"><img src="/assets/images/loading.svg"></div>');

					DClose();

				});

			}

		}
	</script>

	<?php
}


// Закрепляем/открепляем заметку
if ( $action == 'editPin' ) {

	$check = $db -> getOne( "SELECT pin FROM ".$sqlname."notes WHERE id = '$id' and identity = '$identity'" );

	$pin = $check == '1' ? '0' : '1';

	$db -> query( "UPDATE ".$sqlname."notes SET pin='$pin' WHERE id = '$id' and identity = '$identity'" );

	if ( $pin == '1' ) {

		?>
		<script>

			yNotifyMe("CRM. Результат, Заметка закреплена" + ",signal.png");

			$('#notes').load("content/vigets/viget.notes.php?action=list").append('<div id="loader"><img src="/assets/images/loading.svg"></div>');

		</script>
		<?php

	}
	else {

		?>
		<script>

			yNotifyMe("CRM. Результат, Заметка откреплена" + ",signal.png");

			$('#notes').load("content/vigets/viget.notes.php?action=list").append('<div id="loader"><img src="/assets/images/loading.svg"></div>');

		</script>

		<?php

	}
}

if ( $action == 'edit' ) {

	$noteX['text']     = $text;
	$noteX['author']   = $iduser1;
	$noteX['pin']      = $pinCheck;
	$noteX['date']     = current_datumtime();
	$noteX['identity'] = $identity;

	if ( $id != '' ) {

		$db -> query( "UPDATE ".$sqlname."notes SET ?u WHERE id='".$id."' and identity = '$identity'", ArrayNullClean( $noteX ) );

	}
	else {

		$db -> query( "INSERT INTO ".$sqlname."notes SET ?u", ArrayNullClean( $noteX ) );

	}

}

// Удаление заметки

if ( $action == 'delete' ) {

	// Удаляем, если это разрешено
	$db -> query( "DELETE FROM ".$sqlname."notes WHERE id = '$id' and identity = '$identity'" );

	?>
	<script>

		yNotifyMe("CRM. Результат, Заметка удалена" + ",signal.png");

		$('#notes').load("content/vigets/viget.notes.php?action=list").append('<div id="loader"><img src="/assets/images/loading.svg"></div>');

	</script>
	<?php

}

?>

<script>

	$(document).ready(function () {

		$('#resultdiv').css({'height': 'unset', 'max-height': 'unset'});

	});

	// Вывод окна для просмотра информации о заметке
	function viewNote(id) {

		doLoad("content/vigets/viget.notes.php?action=view&note=" + id);

	}

	// Вывод окна для добавления заметки
	function addNote() {

		doLoad("content/vigets/viget.notes.php?action=add");

	}

	function updateNote(id) {

		doLoad("content/vigets/viget.notes.php?action=update&note=" + id);

	}

	// Удаление заметки
	function deleteNote(id) {

		//$("#notes").load("vigets/viget.notes.php?action=list").append('<div id="loader"><img src="images/loading.svg"></div>');

		Swal.fire({
			title: 'Вы уверены',
			text: 'Заметка будет безвозвратно удалена',
			type: 'question',
			showCancelButton: true,
			showCloseButton: true,
			confirmButtonColor: '#3085D6',
			cancelButtonColor: '#D33',
			confirmButtonText: 'Да, удалить',
			cancelButtonText: 'Отмена'
		}).then((result) => {
				$("#notes").load("content/vigets/viget.notes.php?action=delete&note=" + id).append('<div id="loader"><img src="/assets/images/loading.svg"></div>');
			}
		);

	}

	// Изменение статуса закрепления
	function editPin(id) {

		$("#notes").load("content/vigets/viget.notes.php?action=editPin&note=" + id).append('<div id="loader"><img src="/assets/images/loading.svg"></div>');

	}

	$('.checkpin').on('change', function () {

		if ($(".checkpin").is(':checked')) {

			$('#checkText').html('Закреплена');
			$('.editpin').val("1");

		}
		else {

			$('#checkText').html('Откреплена');
			$('.editpin').val("0");

		}
	});

</script>

