<?php
/* ============================ */
/* (C) 2016 Vladislav Andreev   */
/*       SalesMan Project       */
/*        www.isaler.ru         */
/*        ver. 2019.x           */
/* ============================ */

use Salesman\Knowledgebase;

error_reporting( E_ERROR );
header( "Pragma: no-cache" );
header( 'Content-Type: text/html; charset=utf-8' );

$rootpath = dirname( __DIR__, 2 );

include $rootpath."/inc/config.php";
include $rootpath."/inc/dbconnector.php";
include $rootpath."/inc/auth.php";
include $rootpath."/inc/func.php";
include $rootpath."/inc/settings.php";
include $rootpath."/developer/events.php";
include $rootpath."/inc/language/".$language.".php";

$thisfile = basename( __FILE__ );

$action = $_REQUEST['action'];

if ( $action == "edit" ) {

	$id        = (int)$_REQUEST['id'];
	$messageid = (int)$_REQUEST['messageid'];

	$content = $title = '';

	//создание статьи из Почтовика
	if ( $messageid > 0 ) {

		$result = $db -> getRow( "SELECT * FROM {$sqlname}ymail_messages WHERE id = '$messageid' and identity = '$identity'" );

		$content = htmlspecialchars_decode( $result['content'] );
		if ( preg_match( '|<body.*?>(.*)</body>|si', $content, $arr ) ) {
			$content = $arr[1];
		}
		$content = preg_replace( "!<style>(.*?)</style>!si", "", $content );

		$title = $result['theme'];

	}

	//если редактируем
	if ( $id > 0 ) {

		$knowledgebase = new Knowledgebase();
		$kb = $knowledgebase -> info($id);

		$title    = $kb["title"];
		$content  = $kb["content"];
		$idcat    = $kb["category"];
		$keywords = $kb["keywords"];
		$active   = $kb["active"];

	}
	?>
	<DIV class="zagolovok">Добавить/Изменить</DIV>
	<FORM action="/modules/knowledgebase/core.knowledgebase.php" method="post" enctype="multipart/form-data" name="form" id="form">
		<INPUT type="hidden" name="action" id="action" value="edit">
		<INPUT type="hidden" name="id" id="id" value="<?= $id ?>">

		<div id="formtabse">

			<div class="dv1 block p5">

				<div class="column12 grid-12">
					<input type="text" id="title" name="title" value="<?= $title ?>" placeholder="Заголовок" class="wp100 required">
				</div>

			</div>
			<div class="dv2">

				<div class="column12 grid-12">
					<textarea name="content" id="content" class="wp100" style="height:350px"><?= $content ?></textarea>
				</div>

			</div>

			<div class="row">

				<div class="column12 grid-3">

					<select name="idcat" id="idcat" class="wp93">
						<OPTION value="">--Выбор раздела--</OPTION>
						<?php
						$result = $db -> getAll( "SELECT * FROM {$sqlname}kb WHERE subid = '0' and identity = '$identity' ORDER BY title" );
						foreach ( $result as $data ) {

							$s = ($data['idcat'] == $idcat) ? "selected" : "";

							print '<OPTION value="'.$data['idcat'].'" '.$s.'>'.$data['title'].'</OPTION>';

							$res = $db -> getAll( "SELECT * FROM {$sqlname}kb WHERE subid = '".$data['idcat']."' and identity = '$identity'" );
							foreach ( $res as $da ) {

								$s = ($da['idcat'] == $idcat) ? "selected" : "";

								print '<OPTION value="'.$da['idcat'].'" '.$s.'>&nbsp;&nbsp;-  '.$da['title'].'</OPTION>';

							}

						}
						?>
					</select>

				</div>
				<div class="column12 grid-3">

					<input type="text" name="newcat" id="newcat" class="wp93" placeholder="Новый раздел" value=""/>

				</div>
				<div class="column12 grid-6">

					<input type="text" name="keywords" id="keywords" class="wp100" autocomplete="off" placeholder="Тэги" value="<?= $keywords ?>"/>

				</div>

			</div>

		</div>

		<hr>

		<div class="button--pane block text-right">

			<div class="inline pt10 pr20">

				<div class="block">

					<div class="checkbox mt5 text-left">
						<label>
							<input name="active" type="checkbox" value="no" <?php if ( $active == 'no' )
								print 'checked'; ?> />
							<span class="custom-checkbox"><i class="icon-ok"></i></span>
							&nbsp;<b class="red">Черновик</b>
						</label>
					</div>

				</div>

			</div>
			<div class="div-right inline">

				<A href="javascript:void(0)" onclick="saveForm()" class="button">Сохранить</A>&nbsp;
				<A href="javascript:void(0)" onclick="DClose2()" class="button">Отмена</A>

			</div>

		</div>
	</FORM>
	<?php
}

if ( $action == "view" ) {

	$id = (int)$_REQUEST['id'];

	$knowledgebase = new Knowledgebase();
	$kb = $knowledgebase -> info($id);

	$title    = $kb["title"];
	$content  = $kb["content"];
	$idcat    = $kb["idcat"];
	$keywords = $kb["keywords"];

	?>
	<DIV class="zagolovok"><?= $title ?></DIV>
	<div id="articles" style="overflow-y: auto;">
		<?= $content ?>
	</div>

	<script type="text/javascript">
		$(function () {

			var h = $('#dialog').height();

			$('#dialog').css('width', '80%').css('height', '90vh').center();
			$('#articles').css('height', h - 70 + 'px');

		});
	</script>

	<?php
	exit();
}
if ( $action == "viewshort" ) {

	$id = (int)$_REQUEST['id'];

	$knowledgebase = new Knowledgebase();
	$kb = $knowledgebase -> info($id);

	$change = ($kb['author'] == $iduser1 || $isadmin == 'on') ? 'yes' : '';
	$urls = $kb['urls'];

	$pin = !$kb['ispin'] ? "" : '<i class="icon-star red" title="Закреплено"></i><span class="hidden-iphone">Закреплено&nbsp;</span>';

	?>

	<DIV class="kbaction p10 hidden">

		<?php if ( $isMobile ) { ?>
			<div class="gray inline">
				<a href="javascript:void(0)" onclick="$('.ui-layout-east').removeClass('open'); $('#contentdiv').find('tr').removeClass('current');" title=""><i class="icon-cancel-circled"></i> Закрыть</a>
			</div>
		<?php } ?>

		<div class="pull-left">
			<?= $pin ?>
			<span class="hidden-iphone"><i class="icon-clock green"></i><?= get_sfdate( $kb['date'] ) ?></span>
			<i class="icon-user-1 blue"></i><?= $kb['authorName'] ?>
		</div>

		<div class="inline hidden-iphone">

			<div class="flex-container button--group">

				<div class="flex-string">
					<a href="javascript:void(0)" onclick="editKb('<?= $id ?>','open');" title="В новом окне" class="button graybtn m0"><i class="icon-print green"></i> Открыть</a>
				</div>
				<?php if ( $change == 'yes' ) { ?>
					<div class="flex-string">
						<a href="javascript:void(0)" onclick="editKb('<?= $id ?>','edit');" title="Изменить" class="button graybtn m0"><i class="icon-pencil blue"></i> Редактировать</a>
					</div>
					<div class="flex-string">
						<a href="javascript:void(0)" onclick="cf=confirm('Вы действительно хотите удалить запись?');if (cf)editKb('<?= $id ?>','delete');" title="Удалить" class="button graybtn m0"><i class="icon-cancel-circled red"></i> Удалить</a>
					</div>
				<?php } ?>

			</div>

		</div>

	</DIV>

	<h2 class="blue fs-16"><?= $kb['title'] ?></h2>

	<div id="public" class="mb10 pt20 mp0">
		<?= $kb['content'] ?>
		<?php
		if ( !empty( $urls ) ) {

			$text = '';

			foreach ( $urls as $url ) {

				$text .= '<li><a href="'.$url['url'].'" target="_blank">'.$url['title'].'</a></li>';

			}

			print '
				<div class="infodiv mt20">
					<h3 class="togglerbox hand" data-id="urls"><i class="icon-globe"></i>Ссылки из статьи: <i class="icon-angle-down" id="mapic"></i></h3>
					<div class="hidden" id="urls">
						<ul>
							'.$text.'
						</ul>
					</div>
				</div>
			';

		}
		?>
	</div>

	<div style="height: 90px"></div>
	<?php
	exit();
}

if ( $action == "cat.edit" ) {

	$idcat = (int)$_REQUEST['id'];

	$knowledgebase = new Knowledgebase();
	$result = $knowledgebase -> categoryInfo($idcat);
	$title  = $result["title"];
	$idcat  = $result["id"];
	$subid  = $result["subid"];
	?>
	<div class="zagolovok">Добавить/Изменить раздел</div>
	<FORM action="/modules/knowledgebase/core.knowledgebase.php" method="post" enctype="multipart/form-data" name="form" id="form">
		<INPUT type="hidden" name="action" id="action" value="cat.edit">
		<INPUT type="hidden" name="idcat" id="idcat" value="<?= $idcat ?>">

		<div class="flex-container float p10 ha">

			<div class="flex-string title w140">
				Новое название:
			</div>
			<div class="flex-string float pl10">
				<INPUT name="title" type="text" class="wp100" id="title" value="<?= $title ?>">
			</div>

		</div>

		<div class="flex-container float p10 ha">

			<div class="flex-string title w140">
				Главный раздел:
			</div>
			<div class="flex-string float pl10">
				<select name="subid" id="subid" class="wp100">
					<OPTION value="">--Выбор--</OPTION>
					<?php
					$result = $db -> getAll( "SELECT * FROM {$sqlname}kb WHERE subid = '0' and idcat != '$idcat' and identity = '$identity' ORDER BY title" );
					foreach ( $result as $data ) {

						print '<OPTION value="'.$data['idcat'].'" '.($data['idcat'] == $subid ? "selected" : "").'>'.$data['title'].'</OPTION>';

					}
					?>
				</select>
			</div>

		</div>

		<hr>

		<div class="button--pane text-right">

			<A href="javascript:void(0)" onclick="$('#form').submit()" class="button">Сохранить</A>&nbsp;
			<A href="javascript:void(0)" onclick="editKb('','cat.list');" class="button">Отмена</A>

		</div>
	</FORM>
	<?php
}
if ( $action == 'cat.list' ) {
	?>
	<div class="zagolovok">Редактор разделов</div>

	<div id="formtabs" style="max-height:70vh; overflow:auto" class="border--bottom">

		<?php
		$knowledgebase = new Knowledgebase();
		$list = $knowledgebase -> categorylist(0);
		foreach ($list as $item){

			print '
			<div class="flex-container float p10 ha">
				'.($item['level'] == 0 ? '' : '<div class="flex-string w20"></div>').'
				<div class="flex-string float">
					'.($item['level'] == 0 ? '<i class="icon-folder-open blue"></i>' : '<i class="icon-folder gray2"></i>').'&nbsp;<B>'.$item['title'].'</B>&nbsp;[ <b class="green" title="Число записей">'.$item['count'].'</b> ]
				</div>
				<div class="flex-string w50">
					<A href="javascript:void(0)" onclick="editKb(\''.$item['id'].'\',\'cat.edit\')"><i class="icon-pencil green" title="Редактировать"></i></A>
				</div>
				<div class="flex-string w50">
					<A href="javascript:void(0)" onclick="cf=confirm(\'Вы действительно хотите удалить запись?\');if (cf)editKb(\''.$item['id'].'\',\'cat.delete\')"><i class="icon-cancel-circled red" title="Удалить"></i></A>
				</div>
			</div>';

		}
		?>

	</div>

	<hr>

	<div class="button--pane text-right">

		<A href="javascript:void(0)" onclick="editKb('','cat.edit')" class="button">Добавить</A>&nbsp;
		<A href="javascript:void(0)" onclick="DClose()" class="button">Отмена</A>

	</div>

	<script>
		$('#dialog').css('width', '600px');
	</script>
	<?php
	exit();
}
if ( $action == "tags" ) {

	$list = $knowledgebase -> taglist(NULL, true);

	foreach ( $list as $data ) {

		print '<div class="tags" data-tag="'.$data['name'].'">'.$data['name'].'</div>';

	}

	exit();

}

//api,crm,sip,внедрение,обучение,,отчет,разработка,руководителю,телефон
//api,crm,внедрение,обучение,отчет,разработка,руководителю
?>
<script>

	var editor2;
	var action = $('#action').val();

	$(function () {

		$('#dialog').css({'width': '90%'}).center();

		if (action === 'edit') {

			$('#dialog').css({'height': '80vh'}).center();
			createEditor2();

		}
		if (action === 'cat.edit') {
			$('#dialog').css({'width': '600px'}).center();
		}

		$("#keywords").autocomplete("/modules/knowledgebase/core.knowledgebase.php?action=taglist", {
			autofill: true,
			minChars: 0,
			cacheLength: 5,
			maxItemsToShow: 20,
			selectFirst: true,
			multiple: true,
			delay: 10,
			matchSubset: 1
		});

		$('#form').ajaxForm({
			dataType: 'json',
			beforeSubmit: function () {

				var $out = $('#message');
				var em = checkRequired();

				if (em === false) return false;

				$('#dialog').css('display', 'none');
				$('#dialog_container').css('display', 'none');

				if (editor2) removeEditor2();

				$out.empty().fadeTo(1, 1).css('display', 'block').append('<div id=loader><img src=/assets/images/loader.gif> Загрузка данных. Пожалуйста подождите...</div>');

				return true;

			},
			success: function (data) {

				var id = parseInt( $('#lmenu #idcat').val() );

				$('#message').fadeTo(1, 1).css('display', 'block').html(data.mes);
				setTimeout(function () {
					$('#message').fadeTo(1000, 0);
				}, 20000);

				$('#resultdiv').empty();

				if (action === 'cat.edit') {

					$('.ifolder').load('/modules/knowledgebase/core.knowledgebase.php?action=catlist&id=' + id, function () {
						$('.ifolder a [data-id=' + id + ']').addClass('fol_it');
					});

					configpage();

					$("#lmenu").find('.nano').nanoScroller();

				}
				else {

                    $current = data.id;
					configpage();
					loadtags();
					removeEditor2();

					//$('.messagelist').trigger('click');

				}

			}
		});

		$('.close')
			.on('click', function () {
				removeEditor2();
			});

	});


	function saveForm() {

		CKEDITOR.instances['content'].updateElement();

		$('#form').trigger('submit');

	}

	function createEditor2() {

		var vh = $('.dialog').actual('height') - $('.zagolovok').actual('height') - $('.button--pane').actual('height') - $('.dv1').actual('height');

		if ($(window).width() < 1000) {
			vh = vh - 30;
		}

		editor2 = CKEDITOR.replace('content', {
			height: vh + 'px',
			width: '100%',
			extraPlugins: 'image2,textselection,base64image,codemirror,oembed,widget,autolink',
			filebrowserUploadUrl: '/modules/ckuploader/upload.php?type=kb',
			filebrowserImageBrowseUrl: '/modules/ckuploader/browse.php?type=kb',
			filebrowserBrowseUrl: '/modules/ckuploader/browse.php?type=kb',
			toolbar: [
				['Format', 'FontSize', 'Bold', 'Italic', 'Underline', 'Strike', 'Subscript', 'Superscript', '-', 'NumberedList', 'BulletedList', '-', 'Link', 'Unlink'],
				['JustifyLeft', 'JustifyCenter', 'JustifyRight', 'JustifyBlock'],
				['TextColor', 'BGColor', '-', 'Undo', 'Redo', '-', 'Maximize', '-', 'Find', 'Replace', 'SelectAll'],
				['PasteText', 'PasteFromWord', 'Image', 'oembed', 'HorizontalRule'],
				['Blockquote', 'Outdent', 'Indent'],
				['CopyFormatting', 'RemoveFormat'],
				['-', 'Source']
			]
		});

		CKEDITOR.on("instanceReady", function (event) {

			vh = vh - $('.cke_top').actual('height') - $('.cke_bottom').actual('outerHeight') - 120;
			$('.cke_contents').height(vh + 'px');

			$('#dialog').center();

		});

	}

	function removeEditor2() {

		var html = $('#cke_editor_content').html();

		if (editor) {
			$('#content').val(html);
			editor2.destroy();
			editor2 = null;
		}

		$('#contentdiv .nano').css('height', '100%');

		return true;
	}

	function DClose2() {

		$('#dialog').css('display', 'none').css('width', '500px');
		$('#resultdiv').empty();
		$('#dialog_container').css('display', 'none');

		removeEditor2();

	}

</script>