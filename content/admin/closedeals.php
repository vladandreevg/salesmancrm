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

$action    = $_REQUEST['action'];
$sid       = (int)$_REQUEST['sid'];
$title     = $_REQUEST['title'];
$content   = $_REQUEST['content'];
$res_close = $_REQUEST['result'];

if ( $action == "delete_do" ) {

	$multi = (string)$_REQUEST['multi'];

	if ( $multi == '' ) {

		$db -> query( "update {$sqlname}dogovor set sid = '".$_REQUEST['newid']."' WHERE sid = '".$_REQUEST['sid']."' and identity = '$identity'" );
		$db -> query( "delete from {$sqlname}dogstatus where sid = '".$_REQUEST['sid']."' and identity = '$identity'" );

	}
	else {

		$db -> query( "update {$sqlname}dogovor set sid = '".$_REQUEST['newid']."' WHERE sid IN (".$multi.") and identity = '$identity'" );
		$db -> query( "delete from {$sqlname}dogstatus where sid IN (".$multi.") and identity = '$identity'" );

	}
	print "Сделано";

	exit();

}
if ( $action == "edit_on" ) {

	if ( $sid == 0 ) {

		$db -> query( "INSERT INTO {$sqlname}dogstatus SET ?u", [
			'title'        => $title,
			'content'      => $content,
			'result_close' => $res_close,
			'identity'     => $identity
		] );

	}
	else {

		$db -> query( "UPDATE {$sqlname}dogstatus SET ?u WHERE sid = '$sid' and identity = '$identity'", [
			'title'        => $title,
			'result_close' => $res_close,
			'content'      => $content
		] );

	}
	print "Сделано";

	exit();
}

if ( $action == "edit" ) {

	$query   = "SELECT * FROM {$sqlname}dogstatus where sid = '$sid' and identity = '$identity'";
	$result  = $db -> getRow( $query );
	$title   = $result["title"];
	$content = $result["content"];
	$type    = $result['result_close'];

	?>
	<div class="zagolovok"><b>Изменить / Добавить</b></div>
	<FORM action="/content/admin/<?php echo $thisfile; ?>" method="POST" name="form" id="form">
		<input type="hidden" id="sid" name="sid" value="<?= $sid ?>">
		<input name="action" type="hidden" value="edit_on" id="action"/>

		<div class="row">

			<div class="column12 grid-3 fs-12 pt10 right-text">Название:</div>
			<div class="column12 grid-9">
				<INPUT name="title" id="title" type="text" class="required wp97" value="<?= $title ?>">
			</div>
			<div class="column12 grid-3 fs-12 pt10 right-text">Результат:</div>
			<div class="column12 grid-9">
				<select name="result" id="result" class="wp97">
					<option value="">--Выбор--</option>
					<option value="win" <?php if ( $type == 'win' )
						print 'selected'; ?>>Победа</option>
					<option value="lose" <?php if ( $type == 'lose' )
						print 'selected'; ?>>Проигрыш</option>
				</select>
			</div>
			<div class="column12 grid-3 fs-12 pt10 right-text">Расшифровка:</div>
			<div class="column12 grid-9">
				<textarea name="content" class="required wp97" id="content"><?= $content ?></textarea>
			</div>

		</div>

		<hr>

		<div class="button--pane text-right">
			<A href="javascript:void(0)" onclick="$('#form').trigger('submit')" class="button">Сохранить</A>&nbsp;
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
if ( $action == "delete" ) {

	$result = $db -> getRow( "SELECT * FROM {$sqlname}dogstatus WHERE sid = '".$sid."' and identity = '$identity'" );
	$tip    = $result['title'];

	$multi = (array)$_REQUEST['multi'];
	$count = count( $multi );
	$multi = implode( ",", $multi );
	?>
	<div class="zagolovok">Удалить <?= $tip ?></div>
	<FORM action="/content/admin/<?php echo $thisfile; ?>" method="POST" name="form" id="form">
		<input type="hidden" id="sid" name="sid" value="<?= $sid ?>">
		<input name="action" type="hidden" value="delete_do" id="action"/>
		<input id="multi" name="multi" type="hidden" value="<?= $multi ?>">

		<div class="infodiv">В случае удаления, данный тип останется в существующих записях и они не будут участвовать в отчетах. Вы можете перевести их на новый тип.</div>

		<hr>

		<div class="row">

			<div class="column12 grid-3 fs-12 pt10 right-text">Новый тип:</div>
			<div class="column12 grid-9">
				<select name="newid" id="newid" class="required wp100">
					<option value="">--выбрать--</option>
					<?php

					$s = ($multi == '') ? "sid != '$sid'" : "sid NOT IN (".$multi.")";

					$result_a = $db -> getAll( "SELECT * FROM {$sqlname}dogstatus WHERE $s and identity = '$identity' ORDER by title" );
					foreach ( $result_a as $data ) {

						print '<option value="'.$data['sid'].'">'.$data['title'].' - '.$data['content'].'</option>';

					}
					?>
				</select>
			</div>

		</div>
		<?php
		if ( $count > 0 ) {
			print '<div class="infodiv text-center">Удаляется <b>'.$count.'</b> записей.</div>';
		}
		?>

		<hr>

		<div class="button--pane text-right">
			<A href="javascript:void(0)" onclick="$('#form').trigger('submit')" class="button">Сохранить</A>&nbsp;
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

if ( empty( $_SERVER['HTTP_X_REQUESTED_WITH'] ) && strtolower( $_SERVER['HTTP_X_REQUESTED_WITH'] ) != 'xmlhttprequest' ) {
	print '<div class="bad"><br>Доступ запрещен.<br>Обратитесь к администратору.<br><br></div>';
	exit();
}

if ( $action == '' ) {
	?>
	<h2>&nbsp;Раздел: "Статусы закрытых сделок"</h2>

	<form id="list">

		<TABLE id="catlist">
			<thead class="hidden-iphone sticked--top">
			<TR class="th40">
				<Th class="w50 text-center"></Th>
				<Th class="w140 text-center">Статус</Th>
				<Th class="w70 text-center">Результат</Th>
				<Th class="text-center">Расшифровка</Th>
				<Th class="w100 text-center">Действия</Th>
			</TR>
			</thead>
			<?php
			//постраничный вывод записей
			$i      = 0;
			$result = $db -> getAll( "SELECT * FROM {$sqlname}dogstatus WHERE identity = '$identity' ORDER by result_close DESC" );
			foreach ( $result as $datar ) {

				if ( $datar['result_close'] == 'win' ) {
					$color = 'green';
					$res   = 'Победа';
				}
				elseif ( $datar['result_close'] == 'lose' ) {
					$color = 'red';
					$res   = 'Проигрыш';
				}
				else {
					$color = '';
					$res   = '---';
				};

				?>
				<TR class="ha th40">
					<TD class="text-center">
						<input type="checkbox" onclick="chbCheck()" class="mm" name="multi[<?= $i ?>]" id="multi[<?= $i ?>]" value="<?= $datar['sid'] ?>">
					</TD>
					<TD class="text-left">
						<div class="gray2 Bold fs-09">ID <?= $datar['sid'] ?></div>
						<label for="multi[<?= $i ?>]" onclick="chbCheck()"><span class="fs-10 Bold"><?= $datar['title'] ?></span></label>
					</TD>
					<TD class="text-left">
						<label for="multi[<?= $i ?>]" onclick="chbCheck()"><span class="fs-10 Bold <?= $color ?>"><?= $res ?></span></label>
					</TD>
					<TD class="w250">
						<label for="multi[<?= $i ?>]" onclick="chbCheck()"><span class="fs-10"><?= $datar['content'] ?></span></label>
					</TD>
					<TD class="w100 text-center">
						<A href="javascript:void(0)" onclick="doLoad('/content/admin/<?php echo $thisfile; ?>?action=edit&sid=<?= $datar['sid'] ?>');" class="button dotted bluebtn"><i class="icon-pencil"></i></A>
						<A href="javascript:void(0)" onclick="doLoad('/content/admin/<?php echo $thisfile; ?>?sid=<?= $datar['sid'] ?>&action=delete')" class="button dotted redbtn"><i class="icon-cancel-circled"></i></A>
					</TD>
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
	<div class="pagerefresh refresh--icon admn orange" onclick="openlink('https://salesman.pro/docs/33')" title="Документация"><i class="icon-help"></i></div>

	<div class="space-100"></div>

	<script>

		function multidel() {

			var str = $('#list').serialize();
			doLoad('/content/admin/<?php echo $thisfile; ?>?action=delete&' + str);

		}

		function chbCheck() {

			var col = $('#catlist input:checkbox:checked').length;

			if (col > 0) $('.amultidel').removeClass('hidden');
			else $('.amultidel').addClass('hidden');

		}

		function clearCheck() {

			$('#catlist input:checkbox:checked').prop('checked', false);
			$('.amultidel').addClass('hidden');

		}
	</script>
<?php } ?>