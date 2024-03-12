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

$action     = $_REQUEST['action'];
$idcategory = (int)$_REQUEST['idcategory'];
$title      = $_REQUEST['title'];
$tip        = $_REQUEST['tip'];

if ( $action == "delete.do" ) {

	$multi = $_REQUEST['multi'];

	if ( $multi == '' ) {

		try {
			$db -> query( "update {$sqlname}clientcat set idcategory = '".$_REQUEST['newid']."' WHERE idcategory = '".$_REQUEST['idcategory']."' and identity = '$identity'" );
			$db -> query( "delete from {$sqlname}category where idcategory = '".$_REQUEST['idcategory']."' and identity = '$identity'" );
			print "Сделано";
		}
		catch ( Exception $e ) {
			echo $e -> getMessage();
		}

	}
	else {

		try {
			$db -> query( "update {$sqlname}clientcat set idcategory = '".$_REQUEST['newid']."' WHERE idcategory IN (".$multi.") and identity = '$identity'" );
			$db -> query( "delete from {$sqlname}category where idcategory IN (".$multi.") and identity = '$identity'" );
			print "Сделано";
		}
		catch ( Exception $e ) {
			echo $e -> getMessage();
		}

	}


	exit();
}
if ( $action == "edit.do" ) {

	if ( $idcategory== 0 ) {

		$title = explode( "\n", $_REQUEST['title'] );
		$good  = 0;
		$err   = [];

		for ( $i = 0, $iMax = count( $title ); $i < $iMax; $i++ ) {

			try {
				$db -> query( "insert into {$sqlname}category (idcategory,title,tip,identity) values(null, '".$title[ $i ]."', '$tip','$identity')" );
				$good++;
			}
			catch ( Exception $e ) {
				$err[] = $e -> getMessage();
			}

		}

		if ( count( $err ) > 0 ) {
			print "Есть ошибки:<br>".implode("<br>", $err);
		}
		else {
			print "Сделано";
		}

	}

	if ( $idcategory > 0 ) {

		try {
			$db -> query( "update {$sqlname}category set title = '".$title."', tip = '".$tip."' where idcategory = '".$idcategory."' and identity = '$identity'" );
			print "Сделано";
		}
		catch ( Exception $e ) {
			$err[] = $e -> getMessage();
		}

	}

	exit();
}

if ( $action == "edit" ) {

	$result     = $db -> getRow( "SELECT * FROM {$sqlname}category where idcategory='".$idcategory."' and identity = '$identity'" );
	$title      = $result["title"];
	$idcategory = (int)$result["idcategory"];
	$tip        = $result["tip"];
	?>
	<div class="zagolovok"><b>Изменить / Добавить</b></div>
	<FORM action="/content/admin/<?php echo $thisfile; ?>" method="POST" name="form" id="form">
		<input type="hidden" name="idcategory" id="idcategory" value="<?= $idcategory ?>">
		<input type="hidden" name="action" id="action" value="edit.do">

		<div class="row">

			<div class="column12 grid-3 right-text fs-12 gray2 pt10">Тип:</div>
			<div class="column12 grid-9">
				<SELECT name="tip" id="tip" class="required w100">
					<OPTION value="client" <?php if ( $tip == 'client' )
						print "selected" ?>>Клиент
					</OPTION>
					<OPTION value="concurent" <?php if ( $tip == 'concurent' )
						print "selected" ?>>Конкурент
					</OPTION>
					<OPTION value="contractor" <?php if ( $tip == 'contractor' )
						print "selected" ?>>Поставщик
					</OPTION>
					<OPTION value="partner" <?php if ( $tip == 'partner' )
						print "selected" ?>>Партнер
					</OPTION>
				</SELECT>
			</div>

		</div>
		<div class="row">

			<div class="column12 grid-3 right-text fs-12 gray2 pt10">Название:</div>
			<div class="column12 grid-9">
				<?php
				if ( $idcategory > 0 ) {
					?>
					<INPUT name="title" type="text" id="title" class="wp100 required" value="<?= $title ?>">
					<?php
				}
				else {
					?>
					<textarea name="title" id="title" rows="5" class="wp100 required"><?= $title ?></textarea>
				<?php } ?>
			</div>

		</div>

		<div class="infodiv">Каждый новый вариант начинайте с новой строки</div>

		<hr>

		<div class="button--pane text-right">

			<A href="javascript:void(0)" onclick="$('#form').trigger('submit')" class="button">Сохранить</A>&nbsp;
			<A href="javascript:void(0)" onclick="DClose()" class="button">Отмена</A>

		</div>
	</FORM>
	<script>
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

	$result = $db -> getRow( "SELECT * FROM {$sqlname}category WHERE idcategory = '".$idcategory."' and identity = '$identity'" );
	$tip    = $result['title'];
	$multi  = $_REQUEST['multi'];
	$count  = count( $multi );
	$multi  = implode( ",", $multi );
	?>
	<div class="zagolovok">Удалить отрасль "<?= $tip ?>"</div>
	<FORM action="/content/admin/<?php echo $thisfile; ?>" method="POST" name="form" id="form">
		<input type="hidden" id="idcategory" name="idcategory" value="<?= $idcategory ?>">
		<input id="multi" name="multi" type="hidden" value="<?= $multi ?>">
		<input id="action" name="action" type="hidden" value="delete.do">

		<div class="infodiv">В случае удаления, ссылка на данную отрасль останется в существующих записях и они не будут участвовать в отчетах. Вы можете перевести их на новый тип.</div>
		<hr>

		<div class="row">

			<div class="column12 grid-3 fs-12 pt10 right-text">Новый тип:</div>
			<div class="column12 grid-9">
				<select name="newid" id="newid" class="required wp100">
					<option value="">--выбрать--</option>
					<?php
					if ( $multi != '' )
						$m = ' and idcategory NOT IN ('.$multi.')';
					$result_a = $db -> getAll( "SELECT * FROM {$sqlname}category WHERE (idcategory != '".$idcategory."' $m) and identity = '$identity' ORDER by title" );
					foreach ( $result_a as $data_arraya ) {
						?>
						<option value="<?= $data_arraya['idcategory'] ?>"><?= $data_arraya['title'] ?></option>
					<?php } ?>
				</select>
			</div>

		</div>
		<?php
		if ( $count > 0 ) {
			print '<div class="infodiv">Удаляется <b>'.$count.'</b> записей.</div>';
		}
		?>

		<hr>

		<div class="button--pane text-right">

			<A href="javascript:void(0)" onclick="$('#form').submit()" class="button">Сохранить</A>&nbsp;
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
	print '<div class="warning text-center"><br>Доступ запрещен.<br>Обратитесь к администратору.<br><br></div>';
	exit();
}

if ( $action == '' ) {
	?>

	<h2>&nbsp;Раздел: "<?php echo ($fieldsNames['client']['idcategory'] ?? '<b class="red">Отключено</b>');?>"</h2>
	<h3 class="gray-dark fs-09 pl10">Стандарт: Отрасли</h3>

	<form id="list">

		<TABLE id="catlist">
			<thead class="hidden-iphone sticked--top">
			<TR class="th40">
				<Th class="w50 text-center"></Th>
				<Th class="w400 text-center"><b>Название отраслей</b></Th>
				<Th class="w130 text-left"><b>Тип</b></Th>
				<Th class="w120 text-center"></Th>
				<th></th>
			</TR>
			</thead>
			<tbody>
			<?php
			$i      = 0;
			$otrasl = [
				"client"     => "Клиент",
				"concurent"  => "Конкурент",
				"partner"    => "Партнер",
				"contractor" => "Поставщик"
			];
			$colors = [
				"client"     => "broun",
				"concurent"  => "red",
				"partner"    => "blue",
				"contractor" => "green"
			];

			$result = $db -> getAll( "SELECT * FROM {$sqlname}category WHERE identity = '$identity' ORDER BY title" );
			foreach ( $result as $data_array ) {
				?>
				<TR class="ha th40">
					<TD class="text-center">
						<input type="checkbox" onclick="chbCheck()" class="mm" name="multi[<?= $i ?>]" id="multi[<?= $i ?>]" value="<?= $data_array['idcategory'] ?>">
					</TD>
					<TD>
						<label for="multi[<?= $i ?>]" onclick="chbCheck()">
							<span style="line-height: 25px;">
								<span class="Bold fs-11"><span class="gray2">ID <?= $data_array['idcategory'] ?>:</span> <?= $data_array['title'] ?></span>
							</span>
						</label>
					</TD>
					<TD class="fs-11">
						<i class="icon-flag <?= strtr( $data_array['tip'], $colors ) ?>"></i>&nbsp;<?= strtr( $data_array['tip'], $otrasl ) ?>
					</TD>
					<TD class="text-center">

						<A href="javascript:void(0)" onclick="doLoad('/content/admin/<?php echo $thisfile; ?>?idcategory=<?= $data_array['idcategory'] ?>&action=edit')" class="button dotted bluebtn"><i class="icon-pencil"></i></A>
						<A href="javascript:void(0)" onclick="doLoad('/content/admin/<?php echo $thisfile; ?>?idcategory=<?= $data_array['idcategory'] ?>&action=delete')" class="button dotted redbtn"><i class="icon-cancel-circled"></i></A>

					</TD>
					<TD></TD>
				</TR>
				<?php
				$i++;
			}
			?>
			</tbody>
		</TABLE>

	</form>

	<div class="button--group1 box--child" style="position: fixed; bottom: 40px; left: 380px; z-index: 100;">

		<a href="javascript:void(0)" onclick="doLoad('/content/admin/<?php echo $thisfile; ?>?action=edit');" class="button bluebtn box-shadow" title="Добавить"><i class="icon-plus-circled"></i>Добавить</a>
		<a href="javascript:void(0)" onclick="multidel()" class="button redbtn box-shadow hidden amultidel" title="Удалить"><i class="icon-minus-circled"></i>Удалить выбранное</a>
		<a href="javascript:void(0)" onclick="clearCheck()" class="button greenbtn box-shadow hidden amultidel" title="Снять выделение"><i class="icon-th"></i>Снять выделение</a>

	</div>

	<div class="pagerefresh refresh--icon admn red" onclick="doLoad('/content/admin/<?php echo $thisfile; ?>?action=edit');" title="Добавить"><i class="icon-plus-circled"></i></div>
	<div class="pagerefresh refresh--icon admn orange" onclick="openlink('https://salesman.pro/docs/14')" title="Документация"><i class="icon-help"></i></div>

	<div class="space-100"></div>


	<script type="text/javascript">

		function chbCheck() {

			var col = $('#catlist input:checkbox:checked').length;

			if (col > 0) $('.amultidel').removeClass('hidden');
			else $('.amultidel').addClass('hidden');

		}

		function multidel() {
			var str = $('#list').serialize();
			doLoad('/content/admin/<?php echo $thisfile; ?>?action=delete&' + str);
		}

		function clearCheck() {

			$('#catlist input:checkbox:checked').prop('checked', false);
			$('.amultidel').addClass('hidden');

		}

	</script>

<?php } ?>