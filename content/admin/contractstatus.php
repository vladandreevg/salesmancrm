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

$rootpath = dirname( __DIR__, 2 );

include $rootpath."/inc/config.php";
include $rootpath."/inc/dbconnector.php";
include $rootpath."/inc/auth.php";
include $rootpath."/inc/func.php";
include $rootpath."/inc/settings.php";

$thisfile = basename( __FILE__ );

$action = $_REQUEST['action'];

if ( $action == "delete.do" ) {

	$id        = $_REQUEST['id'];
	$multi     = $_REQUEST['multi'];
	$status    = $_REQUEST['status'];
	$oldstatus = $_REQUEST['oldstatus'];

	if ( $multi == '' ) {

		$db -> query( "update {$sqlname}contract set status = '$status' WHERE status = '$oldstatus' and identity = '$identity'" );

		$db -> query( "delete from {$sqlname}contract_status where id = '$id' and identity = '$identity'" );

		print "Сделано";

	}
	else {

		$db -> query( "update {$sqlname}contract set status = '$status' WHERE status IN (".$multi.") and identity = '$identity'" );

		$db -> query( "delete from {$sqlname}contract_status where id IN (".$multi.") and identity = '$identity'" );

		print "Сделано";

	}

	exit();

}

if ( $action == "edit.on" ) {

	$id    = (int)$_REQUEST['id'];
	$title = $_REQUEST['title'];
	$color = $_REQUEST['color'];
	$tip   = yimplode( ";", (array)$_REQUEST['tip'] );

	if ( $id == 0 ) {

		//расчитаем порядок элемента
		$order = $db -> getOne( "SELECT MAX(ord) FROM {$sqlname}contract_status where identity = '$identity'" ) + 1;

		$db -> query( "INSERT INTO {$sqlname}contract_status SET ?u", [
			'title'    => $title,
			'tip'      => $tip,
			'color'    => $color,
			'ord'      => $order,
			'identity' => $identity
		] );

	}
	else {

		$db -> query( "UPDATE {$sqlname}contract_status SET ?u WHERE id = '$id' and identity = '$identity'", [
			'title' => $title,
			'tip'   => $tip,
			'color' => $color
		] );

	}

	print "Сделано";

	exit();

}

if ( $action == "edit.order" ) {

	$table = explode( ';', implode( ';', $_REQUEST[ 'table-1'] ) );

	//Обновляем данные для текущей записи
	foreach ( $table as $i => $id ) {

		$db -> query( "update {$sqlname}contract_status set ord = '$i' where id = '$id' and identity = '$identity'" );

	}

	print "Обновлено";

	exit();

}

if ( $action == "edit" ) {

	$id = $_REQUEST['id'];

	$docstatus = $db -> getRow( "SELECT * FROM {$sqlname}contract_status where id = '$id' and identity = '$identity'" );

	?>
	<div class="zagolovok"><b>Изменить / Добавить</b></div>
	<FORM method="post" action="content/admin/<?php echo $thisfile; ?>" name="form" id="form">
		<INPUT name="action" id="action" type="hidden" value="edit.on">
		<INPUT name="id" id="id" type="hidden" value="<?= $id ?>">

		<div class="row">

			<div class="column12 grid-2 right-text fs-12 gray2 pt10">Название:</div>
			<div class="column12 grid-10">
				<input type="text" name="title" id="title" value="<?= $docstatus['title'] ?>" class="wp97 required">
			</div>

		</div>

		<div class="row">

			<div class="column12 grid-2 right-text fs-12 gray2 pt5">Цвет:</div>
			<div class="column12 grid-10">
				<input type="text" name="color" id="color" value="<?= $docstatus['color'] ?>" class="wp97 required">
			</div>

		</div>

		<div class="row">

			<div class="column12 grid-2 right-text fs-12 gray2 pt20">Тип документа:</div>
			<div class="column12 grid-10">
				<select name="tip[]" id="tip[]" class="required wp97 multiselect" multiple="multiple">
					<?php
					$result = $db -> getAll( "SELECT * FROM {$sqlname}contract_type WHERE identity = '$identity' ORDER BY title" );
					foreach ( $result as $data ) {
						$s = (in_array( $data['id'], yexplode( ";", (string)$docstatus['tip'] ) )) ? "selected" : "";
						print '<option value="'.$data['id'].'" '.$s.'>'.$data['title'].'</option>';
					}
					?>
				</select>
			</div>

		</div>

		<hr>

		<div align="right">
			<A href="javascript:void(0)" onclick="$('#form').trigger('submit')" class="button">Сохранить</A>&nbsp;
			<A href="javascript:void(0)" onclick="DClose();" class="button">Отмена</A>
		</div>

	</form>

	<script type="text/javascript" src="/assets/js/jquery/jquery.colorPicker.js"></script>
	<script>

		$('#color').colorPicker();

		$(".multiselect").multiselect({sortable: true, searchable: true});
		$(".connected-list").css('height', "200px");

		$('#dialog').css('width', '850px').center();

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

	$id = $_REQUEST['id'];

	$oldstatus = $db -> getOne( "SELECT title FROM {$sqlname}contract_status WHERE id = '$id' and identity = '$identity'" );

	$multi = (array)$_REQUEST['multi'];
	$count = count( $multi );
	$multi = yimplode( ",", $multi );
	?>
	<div class="zagolovok">Удалить <?= $oldstatus ?></div>
	<FORM action="content/admin/<?php echo $thisfile; ?>" method="POST" name="form" id="form">
		<input type="hidden" id="id" name="id" value="<?= $id ?>">
		<input type="hidden" id="oldstatus" name="oldstatus" value="<?= $oldstatus ?>">
		<input type="hidden" id="action" name="action" value="delete.do">
		<input type="hidden" id="multi" name="multi" value="<?= $multi ?>">

		<div class="infodiv">
			В случае удаления, данный тип останется в существующих записях и они не будут участвовать в отчетах. Вы можете перевести их на новый тип.
		</div>

		<hr>

		<div class="row">

			<div class="column12 grid-3 right-text fs-12 gray2 pt10">Новый статус:</div>
			<div class="column12 grid-9">
				<select name="status" id="status" class="required wp97">
					<option value="">--выбор--</option>
					<?php
					if ( $multi != '' )
						$s = " or id NOT IN (".$multi.")";

					$result = $db -> getAll( "SELECT * FROM {$sqlname}contract_status WHERE id != '$id' $s and identity = '$identity' ORDER by title" );
					foreach ( $result as $data ) {
						?>
						<option value="<?= $data['id'] ?>"><?= $data['title'] ?></option>
					<?php } ?>
					?>
				</select>
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

				//$('#contentdiv').load('content/admin/<?php echo $thisfile; ?>');
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
	print '<div class="bad" align="center"><br>Доступ запрещен.<br>Обратитесь к администратору.<br><br></div>';
	exit();
}

if ( $action == '' ) {

	$tips = $db -> getIndCol( "id", "SELECT title, id FROM {$sqlname}contract_type WHERE identity = '$identity' ORDER BY title" );

	?>

	<h2>&nbsp;Раздел: "Статусы документов"</h2>

	<?php
	if(!$otherSettings['contract']){

		print '<div class="warning mb10">Ведение Договоров <b>отключено</b> (см. Общие настройки / Дополнения к сделкам)</div>';

	}
	?>

	<form id="list">

		<table id="table-1">
			<thead class="hidden-iphone sticked--top">
			<tr class="" height="35">
				<Th class="w30 hidden-iphone"></Th>
				<th class="w350">Название</th>
				<th class="w50 hidden-iphone">Порядок</th>
				<th class="text-center">Типы</th>
				<th></th>
			</tr>
			</thead>
			<tbody>
			<?php
			$result = $db -> getAll( "SELECT * FROM {$sqlname}contract_status WHERE identity = '$identity' ORDER by ord" );
			foreach ( $result as $da ) {
				?>
				<tr class="ha th50" id="<?= $da['id'] ?>">
					<td class="text-center hidden-iphone">
						<input type="checkbox" onclick="chbCheck()" class="mm" name="multi[<?= $i ?>]" id="multi[<?= $i ?>]" value="<?= $da['id'] ?>">
					</td>
					<td class="text-left">
						<div class="fs-12 Bold">
							<div class="colordiv inline mr10 mb5" style="background-color:<?= $da['color'] ?>"></div><?= $da['title'] ?>
						</div>
					</td>
					<td class="text-center hidden-iphone">
						<div><?= $da['ord'] ?></div>
					</td>
					<td class="text-left">
						<div class="tagbox">
							<?php
							$t = yexplode( ";", $da['tip'] );
							foreach ( $t as $tip ) {
								print '<div class="tag">'.strtr( $tip, $tips ).'</div>';
							}
							?>
						</div>
					</td>
					<td class="text-center">
						<a href="javascript:void(0)" onclick="doLoad('content/admin/<?php echo $thisfile; ?>?action=edit&id=<?= $da['id'] ?>');" class="button dotted bluebtn"><i class="icon-pencil"></i></a>&nbsp;
						<a href="javascript:void(0)" onclick="doLoad('content/admin/<?php echo $thisfile; ?>?id=<?= $da['id'] ?>&action=delete')" class="button dotted redbtn"><i class="icon-cancel-circled"></i></a>
					</td>
				</tr>
				<?php
			}
			?>
			</tbody>
		</table>

	</form>

	<div class="button--group1 box--child" style="position: fixed; bottom: 40px; left: 380px; z-index: 100;">

		<a href="javascript:void(0)" onclick="doLoad('content/admin/<?php echo $thisfile; ?>?action=edit');" class="button bluebtn box-shadow" title="Добавить"><i class="icon-plus-circled"></i>Добавить</a>
		<a href="javascript:void(0)" onclick="preDel()" class="button redbtn box-shadow hidden amultidel" title="Удалить"><i class="icon-minus-circled"></i>Удалить выбранное</a>
		<a href="javascript:void(0)" onclick="clearCheck()" class="button greenbtn box-shadow hidden amultidel" title="Снять выделение"><i class="icon-th"></i>Снять выделение</a>

	</div>

	<div class="pagerefresh refresh--icon admn green" onclick="doLoad('content/admin/<?php echo $thisfile; ?>?action=edit');" title="Добавить"><i class="icon-plus-circled"></i></div>
	<div class="pagerefresh refresh--icon admn orange" onclick="openlink('https://salesman.pro/docs/32')" title="Документация"><i class="icon-help"></i></div>

	<div class="space-100"></div>

	<script type="text/javascript">

		if (isMobile) $('#table-1').addClass('rowtable');

		function multidel() {
			var str = $('#list').serialize();
			doLoad('content/admin/<?php echo $thisfile; ?>?action=delete&' + str);
		}

		function chbCheck() {

			var col = $('#table-1 input:checkbox:checked').length;

			if (col > 0) $('.amultidel').removeClass('hidden');
			else $('.amultidel').addClass('hidden');

		}

		function clearCheck() {

			$('#table-1 input:checkbox:checked').prop('checked', false);
			$('.amultidel').addClass('hidden');

		}

		function preDel() {

			Swal.fire({
					title: 'Вы уверены?',
					text: "Записи будут удалены безвозвратно",
					type: 'question',
					showCancelButton: true,
					confirmButtonColor: '#3085D6',
					cancelButtonColor: '#D33',
					confirmButtonText: 'Да, выполнить',
					cancelButtonText: 'Отменить',
					confirmButtonClass: 'greenbtn',
					cancelButtonClass: 'redbtn'
				},
				function () {
					multidel();
				}
			).then((result) => {

				if (result.value) {

					multidel();

				}

			});

		}

		$("#table-1").disableSelection().tableDnD({
			onDragClass: "tableDrag",
			onDrop: function (table, row) {

				var str = '' + $('#table-1').tableDnDSerialize();
				var url = 'content/admin/<?php echo $thisfile; ?>?action=edit.order&';

				$.post(url, str, function (data) {

					//$('#contentdiv').load('content/admin/<?php echo $thisfile; ?>');
					razdel($hash);

					$('#message').fadeTo(1, 1).css('display', 'block').html(data);
					setTimeout(function () {
						$('#message').fadeTo(1000, 0);
					}, 20000);

				});

			}
		});

	</script>
<?php } ?>