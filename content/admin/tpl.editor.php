<?php
/* ============================ */
/*         SalesMan CRM         */
/* ============================ */
/* (C) 2016 Vladislav Andreev   */
/*       SalesMan Project       */
/*        www.isaler.ru         */
/*        ver. 2017.x           */
/* ============================ */

error_reporting(0);
header("Pragma: no-cache");

$rootpath = dirname(__DIR__, 2);

include $rootpath."/inc/config.php";
include $rootpath."/inc/dbconnector.php";
include $rootpath."/inc/auth.php";
include $rootpath."/inc/func.php";
include $rootpath."/inc/settings.php";

$thisfile = basename( __FILE__ );

$action = $_REQUEST['action'];

if ($action == "edit_tpl") {

	$content = htmlspecialchars_decode($_REQUEST['new_tpl']);
	$tid     = $_REQUEST['tid'];

	try {

		$db -> query("update {$sqlname}tpl set content = '".$content."' where tid = ".$tid);
		print "Запись сохранена";

	}
	catch (Exception $e) {
		echo $e -> getMessage();
	}

}
if ($action == "edit") {

	$tid     = (int)$_GET['tid'];
	$result  = $db -> getRow("select * from {$sqlname}tpl where tid='".$tid."' and identity = '$identity'");
	$name    = $result["name"];
	$content = htmlspecialchars_decode($result["content"]);
	?>
	<DIV class="zagolovok">Изменение Шаблона "<?= $name ?>"</DIV>
	<hr>
	<FORM action="/content/admin/<?php echo $thisfile; ?>" method="post" enctype="multipart/form-data" name="Form" id="Form">
		<input name="action" id="action" type="hidden" value="edit_tpl">
		<input name="tid" id="tid" type="hidden" value="<?= $tid ?>">
		<TABLE width="800" border=0 cellpadding=2 cellspacing=3>
			<TR>
				<TD>
					<A href="javascript:void(0)" onclick="addItem('new_tpl','link');" class="sbutton" title="Вставить ссылку">Ссылка</A>
					<hr>
					<textarea name="new_tpl" rows="14" class="des" id="new_tpl" style="width: 97%;"><?= $content ?></textarea>
				</TD>
			</TR>
		</TABLE>
		<div align="right">
			<A href="javascript:void(0)" id="sender" onclick="saveForm()" class="button">Сохранить</A>&nbsp;
			<A href="javascript:void(0)" onclick="removeEditor(); DClose()" class="button">Отмена</A>
		</div>
	</FORM>
	<SCRIPT>

		var editor;

		$(function () {

			createEditor();

			$('#dialog').css('width', '802px');

			$('#Form').ajaxForm({
				beforeSubmit: function () {

					var $out = $('#message');
					var em = checkRequired();

					if (!em)
						return false;

					removeEditor();

					$('#dialog').css('display', 'none');
					$('#dialog_container').css('display', 'none');

					$out.css('display', 'block').append('<div id=loader><img src=/assets/images/loader.gif> Загрузка данных. Пожалуйста подождите...</div>');

					return true;

				},
				success: function (data) {

					$('#contentdiv').load('/content/admin/<?php echo $thisfile; ?>');

					$('#message').fadeTo(1, 1).css('display', 'block').html(data);
					setTimeout(function () {
						$('#message').fadeTo(1000, 0);
					}, 20000);

					removeEditor();

					DClose();

				}
			});

			$('#dialog').center();

		});

		function saveForm() {

			CKEDITOR.instances['new_tpl'].updateElement();

			$('#Form').trigger('submit');

		}

		function createEditor() {

			editor = CKEDITOR.replace('new_tpl',
				{
					//width : 740,
					height: '50vh',
					toolbar:
						[
							['Source', '-', 'Bold', 'Italic', 'Underline', '-', 'NumberedList', 'BulletedList', '-'],
							['Undo', 'Redo', '-', 'Replace', '-', 'RemoveFormat', '-', 'PasteText', 'PasteFromWord', 'HorizontalRule'],
							['TextColor', 'BGColor', 'FontSize'],
							['JustifyLeft', 'JustifyCenter', 'JustifyBlock']
						]
				});

			CKEDITOR.on("instanceReady", function (event) {

				$('#dialog').center();

			});

		}

		function removeEditor() {
			html = $('#cke_editor_new_tpl').html();
			if (editor) {
				$('#new_tpl').val(html);
				editor.destroy();
				editor = null;
			}
		}

		function addItem(txtar, myitem) {
			html = $('#cke_editor_new_tpl').html();
			//alert (html);
			if (!editor) {
				var textt = $('#' + txtar).val();
				$('#' + txtar).val(textt + '{' + myitem + '}');
			} else {
				var oEditor = CKEDITOR.instances.new_tpl;
				oEditor.insertHtml('{' + myitem + '}');
			}
		}

	</SCRIPT>
	<?php
}
if ($action == "") {

	$query  = "SELECT * FROM {$sqlname}tpl WHERE identity = '$identity' order by name";
	$result = $db -> getAll($query);
	?>
	<TABLE width="100%" cellpadding="5" cellspacing="0" border="0" id="zebra">
		<thead class="hidden-iphone sticked--top">
		<tr height="40">
			<TH width="250">Тема сообщения</TH>
			<TH width="70">Действие</TH>
			<TH>Шаблон</TH>
		</tr>
		</thead>
		<TBODY>
		<?php
		foreach ($result as $data_array) {
			?>
			<TR height="45" class="ha">
				<TD width="250">
					<SPAN class="ellipsis" title="<?= $data_array['name'] ?>"><B><?= $data_array['name'] ?></B></SPAN>
				</TD>
				<TD width="70" align="center" nowrap>
					<A href="javascript:void(0)" onclick="doLoad('/content/admin/<?php echo $thisfile; ?>?tid=<?= $data_array['tid'] ?>&action=edit');" title="Изменить"><i class="icon-pencil blue"></i></A>
				</TD>
				<TD>
					<SPAN class="ellipsis" title="<?= clean($data_array['content']) ?>"><?= clean($data_array['content']) ?></SPAN>
				</TD>
			</TR>
			<?php
		}
		?>
		</TBODY>
	</TABLE>

	<div style="height: 90px">&nbsp;</div>

	<SCRIPT>
		$(function () {
			$('#dialog').css('width', '802px');
		});
		$('#dialog').center();
	</SCRIPT>
<?php } ?>
