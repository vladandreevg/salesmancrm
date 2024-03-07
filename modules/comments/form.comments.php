<?php
/* ============================ */
/*         SalesMan CRM         */
/* ============================ */
/* (C) 2016 Vladislav Andreev   */
/*       SalesMan Project       */
/*        www.isaler.ru         */
/*        ver. 2017.x           */
/* ============================ */

use Salesman\Comments;
use Salesman\Project;

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

$clid    = (int)$_REQUEST['clid'];
$pid     = (int)$_REQUEST['pid'];
$did     = (int)$_REQUEST['did'];
$project = (int)$_REQUEST['project'];

$action     = $_REQUEST['action'];
$id         = (int)$_REQUEST['id'];
$hideEditor = $_REQUEST["hideEditor"];

$modules = getModules();

if ( $action == "add" ) {

	$idparent = (int)$_REQUEST["idparent"];

	$mid = ($idparent < 1) ? 0 : 1;

	$title = $db -> getOne( "select title from ".$sqlname."comments WHERE id = '$idparent' and identity = '$identity'" );

	$req = ($hideEditor == 'yes') ? "required" : "";

	$isProject = false;

	if ( $did > 0 ) {

		$clid = getDogData( $did, 'clid' );

	}

	if ( array_key_exists( 'projects', $modules ) ) {

		$isProject = true;

		$prj = $project > 0 ? Salesman\Project ::info( $project ) : [];

		if ( !empty( $prj['project'] ) ) {

			$did  = $prj['project']['did'];
			$clid = $prj['project']['clid'];

		}

	}

	?>
	<DIV class="zagolovok"><B><?= (!$idparent ? 'Начать обсуждение' : 'Ответить') ?></B></DIV>
	<FORM method="post" action="/modules/comments/core.comments.php" enctype="multipart/form-data" name="sForm" id="sForm">
		<INPUT name="action" id="action" type="hidden" value="edit">
		<INPUT name="clid" id="clid" type="hidden" value="<?= $clid ?>">
		<INPUT name="pid" id="pid" type="hidden" value="<?= $pid ?>">
		<INPUT name="did" id="did" type="hidden" value="<?= $did ?>">
		<INPUT name="project" id="project" type="hidden" value="<?= $project ?>">
		<input name="idparent" id="idparent" type="hidden" value="<?= $idparent ?>">
		<input name="mid" id="mid" type="hidden" value="<?= $mid ?>">

		<DIV id="formtabs" class="box--child mt20" style="max-height:80vh; overflow-x: hidden; overflow-y: auto !important;">

			<div id="tab-form-1">

				<?php
				if ( !$idparent ) {
					?>
					<div class="ctitle mb10">
						<input name="title" id="title" type="text" placeholder="Тема обсуждения" class="required wp100" value="Новая тема"/>
					</div>
					<?php
				}
				?>
				<div class="ccontent">
					<textarea name="content" class="required <?= $req ?> wp100" id="content"></textarea>
				</div>

			</div>

			<div id="divider" class="red">
				<b class="blue">Файлы</b>
			</div>

			<div id="tab-form-2">

				<?php
				include $rootpath."/content/ajax/check_disk.php";
				if ( $diskLimit > 0 ) {

					$fl = '<b>Ипользование диска:</b> Лимит: <b>'.$diskUsage['total'].'</b> Мб, Занято: <b class="red">'.$diskUsage['current'].'</b> Mb ( <b>'.$diskUsage['percent'].'</b> % )<br>';

					if ( $maxupload == '' )
						$maxupload = str_replace( [
							'M',
							'm'
						], '', @ini_get( 'upload_max_filesize' ) );

				}
				?>

				<DIV id="uploads" class="mb10">

					<?php if ( $diskLimit == 0 || $diskUsage['percent'] < 100 ) { ?>

						<div id="file-1" class="filebox wp99">
							<input name="file[]" type="file" class="file wp100" id="file[]" onchange="addfile();" multiple>
							<div class="delfilebox hand" onclick="deleteFilebox('file-1')" title="Очистить">
								<i class="icon-cancel-circled red"></i>
							</div>
						</div>

						<?php
					}
					else print '<div class="warning wp97 text-center"><b class="red">Превышен лимит использования диска</b></div>';
					?>

				</DIV>

				<div class="fs-09 wp100 pl10 pr10 mb15">
					<b class="red">Информация:</b> максимальный размер файла = <?= $maxupload ?>mb; <?= $fl ?>
				</div>

			</div>

			<?php
			//если это новое Обсуждение
			if ( $mid == 0 ) {
				?>
				<div id="divider" class="mt15">
					<b class="blue">Привязать обсуждение</b>
				</div>

				<div class="client div-info flex-container wp100 mt10">

				<span class="flex-string wp5 pt5 hidden-ipad">
					<i class="icon-building-filled blue"></i>
				</span>
					<span class="relativ cleared flex-string wp95">
					<input name="client" type="text" class="wp97" id="client" value="<?= current_client( $clid ) ?>" placeholder="Начните вводить название. Например: Сэйлзмэн">
					<span class="idel clearinputs pr20 mr5" title="Очистить"><i class="icon-block-1 red"></i></span>
				</span>

				</div>

				<div class="deal div-info flex-container wp100">

				<span class="flex-string wp5 pt5 hidden-ipad">
					<i class="icon-briefcase-1 blue"></i>
				</span>
					<span class="relativ cleared flex-string wp95">
					<input name="dogovor" id="dogovor" type="text" placeholder="Выбор <?= $lang['face']['DealName']['1'] ?>" value="<?= current_dogovor( $did ) ?>" class="wp97">
					<span class="idel clearinputs pr20 mr5" title="Очистить"><i class="icon-block-1 red"></i></span>
				</span>

				</div>

				<?php if ( $isProject ) { ?>
					<div class="project div-info flex-container wp100">

				<span class="flex-string wp5 pt5 hidden-ipad">
					<i class="icon-buffer blue"></i>
				</span>
						<span class="relativ cleared flex-string wp95">
					<input name="prj" type="text" class="wp97" id="prj" value="<?= $prj['project']['name'] ?>" placeholder="Начните вводить название существующего проекта">
					<span class="idel clearinputs pr20 mr5" title="Очистить"><i class="icon-block-1 red"></i></span>
				</span>

					</div>
				<?php } ?>

				<div id="divider">
					<b class="blue">Пригласить участников</b>
				</div>

				<div id="tab-form-3">

					<SELECT name="users[]" id="users[]" multiple="multiple" class="multiselect" style="width:50%">
						<?php
						$result = $db -> getAll( "SELECT iduser, title FROM ".$sqlname."user where secrty='yes' and iduser != '$iduser1' and identity = '$identity' ORDER by title" );
						foreach ( $result as $data ) {
							?>
							<OPTION value="<?= $data['iduser'] ?>"><?= $data['title'] ?></OPTION>
						<?php } ?>
					</SELECT>

				</div>
			<?php } ?>

		</DIV>

		<hr>

		<div class="button--pane text-right wp100">

			<A href="javascript:void(0)" onclick="addTheme()" class="button">Добавить</A>&nbsp;
			<A href="javascript:void(0)" onclick="DClose2()" class="button">Отмена</A>

		</div>

	</FORM>
	<?php
}

if ( $action == "edit" ) {

	$hideEditor = 'no';

	$r        = Comments ::info( $id );
	$idparent = (int)$r['idparent'];
	$content  = $r['content'];
	$title    = $r['title'];
	$fid      = $r['fid'];
	$clid     = (int)$r['clid'];
	$did      = (int)$r['did'];
	$project  = (int)$r['project'];

	$isProject = false;

	if ( array_key_exists( 'projects', $modules ) && $idparent == 0 ) {

		$isProject = true;

		$prj = $project > 0 ? Project ::info( $project ) : [];

		if ( !empty( $prj['project'] ) ) {

			$did  = $prj['project']['did'];
			$clid = $prj['project']['clid'];

		}

	}

	?>
	<DIV class="zagolovok"><B>Изменение</B></DIV>

	<FORM method="post" action="/modules/comments/core.comments.php" enctype="multipart/form-data" name="sForm" id="sForm">
		<INPUT type="hidden" name="id" id="id" value="<?= $id ?>">
		<INPUT type="hidden" name="idparent" id="idparent" value="<?= $idparent ?>">
		<INPUT type="hidden" name="action" id="action" value="edit">

		<DIV id="formtabs" class="box--child mt20 p5" style="max-height:70vh; overflow-x: hidden; overflow-y: auto !important;">

			<div id="tab-form-1">

				<?php if ( $idparent == 0 ) { ?>
					<div class="ctitle mb10">
						<input name="title" id="title" type="text" class="required wp99" value="<?= $title ?>">
					</div>
				<?php } ?>

				<div class="ccontent">
					<TEXTAREA name="content" class="wp100" id="content"><?= $content ?></TEXTAREA>
				</div>

			</div>

			<?php if ( $idparent == 0 ) { ?>
				<div id="divider" class="mt15">
					<b class="blue">Привязать обсуждение</b>
				</div>

				<div class="client div-info flex-container wp100 mt10">

				<span class="flex-string wp5 pt5 hidden-ipad">
					<i class="icon-building-filled blue"></i>
				</span>
					<span class="relativ cleared flex-string wp95">
					<input name="client" type="text" class="wp97" id="client" value="<?= current_client( $clid ) ?>" placeholder="Начните вводить название. Например: Сэйлзмэн">
					<INPUT name="clid" id="clid" type="hidden" value="<?= $clid ?>">
					<span class="idel clearinputs pr20 mr5" title="Очистить"><i class="icon-block-1 red"></i></span>
				</span>

				</div>

				<div class="deal div-info flex-container wp100">

				<span class="flex-string wp5 pt5 hidden-ipad">
					<i class="icon-briefcase-1 blue"></i>
				</span>
					<span class="relativ cleared flex-string wp95">
					<input name="dogovor" id="dogovor" type="text" placeholder="Выбор <?= $lang['face']['DealName']['1'] ?>" value="<?= current_dogovor( $did ) ?>" class="wp97">
					<INPUT name="did" id="did" type="hidden" value="<?= $did ?>">
					<span class="idel clearinputs pr20 mr5" title="Очистить"><i class="icon-block-1 red"></i></span>
				</span>

				</div>

				<?php if ( $isProject ) { ?>
					<div class="project div-info flex-container wp100">

				<span class="flex-string wp5 pt5 hidden-ipad">
					<i class="icon-buffer blue"></i>
				</span>
						<span class="relativ cleared flex-string wp95">
					<input name="prj" type="text" class="wp97" id="prj" value="<?= $prj['project']['name'] ?>" placeholder="Начните вводить название существующего проекта">
					<INPUT name="project" id="project" type="hidden" value="<?= $project ?>">
					<span class="idel clearinputs pr20 mr5" title="Очистить"><i class="icon-block-1 red"></i></span>
				</span>

					</div>
				<?php } ?>
			<?php } ?>

			<div id="divider" class="red">Файлы</div>

			<div id="tab-form-2">

				<?php
				include $rootpath."/content/ajax/check_disk.php";
				if ( $diskLimit > 0 ) {

					$fl = '<b>Ипользование диска:</b> Лимит: <b>'.$diskUsage['total'].'</b> Мб, Занято: <b class="red">'.$diskUsage['current'].'</b> Mb ( <b>'.$diskUsage['percent'].'</b> % )';

					if ( $maxupload == '' )
						$maxupload = str_replace( [
							'M',
							'm'
						], '', @ini_get( 'upload_max_filesize' ) );

				}
				?>

				<?php if ( $fid != '' ) { ?>
					<div id="filelist" class="mt10"></div>
				<?php } ?>

				<DIV id="uploads" class="pb10">

					<div id="filelist" class="wp100"></div>

					<?php if ( $diskLimit == 0 || $diskUsage['percent'] < 100 ) { ?>

						<div id="file-1" class="filebox wp99">
							<input name="file[]" type="file" class="file wp100" id="file[]" onchange="addfile();" multiple>
							<div class="delfilebox hand" onclick="deleteFilebox('file-1')" title="Очистить">
								<i class="icon-cancel-circled red"></i>
							</div>
						</div>

						<div class="pt5 fs-09 wp100">
							<b class="red">Информация:</b> максимальный размер файла = <?= $maxupload ?>mb; <?= $fl ?>
						</div>

						<?php
					}
					else
						print '<div class="warning text-center wp98"><b class="red">Превышен лимит использования диска</b></div>';
					?>

				</DIV>

			</div>

		</DIV>

		<hr>
		<div class="button--pane text-right wp100">

			<A href="javascript:void(0)" onclick="saveForm()" class="button">Сохранить</A>&nbsp;
			<A href="javascript:void(0)" onclick="DClose2()" class="button">Отмена</A>

		</div>

	</FORM>
	<?php

	if ( $idparent == 0 ) {
		$idparent = $id;
	}//если это основная тема

}

if ( $action == "subscribe.user" ) {

	$idparent = (int)$_REQUEST['id'];

	//найдем уже участвующих в обсуждении
	$user = '';

	$users = $db -> getCol( "SELECT iduser FROM ".$sqlname."comments_subscribe WHERE idcomment = '$idparent' and identity = '$identity'" );

	//array_push($users, $iduser1);
	?>
	<DIV class="zagolovok"><B>Пригласить коллег</B></DIV>
	<FORM method="post" action="/modules/comments/core.comments.php" enctype="multipart/form-data" name="sForm" id="sForm">
		<INPUT type="hidden" id="id" name="id" value="<?= $idparent ?>">
		<INPUT type="hidden" id="action" name="action" value="subscribe.user">
		<SELECT name="users[]" id="users[]" multiple="multiple" class="multiselect" style="width:50%">
			<?php
			$users = !empty($users) ? implode( ",", $users ) : $iduser1;
			$result = $db -> getAll( "SELECT * FROM ".$sqlname."user WHERE secrty = 'yes' and iduser NOT IN (".$users.") and identity = '$identity' ORDER by title" );
			foreach ( $result as $data ) {
			?>
				<OPTION value="<?= $data['iduser'] ?>"><?= $data['title'] ?></OPTION>
			<?php
			}
			?>
		</SELECT>
		<hr>
		<div class="button--pane text-right">
			<A href="javascript:void(0)" onclick="$('#sForm').submit()" class="button">Сохранить</A>&nbsp;
			<A href="javascript:void(0)" onclick="DClose()" class="button">Отмена</A>
		</div>
	</FORM>
	<?php
}
?>
<script>

	var editor2;
	var $elm = $('#dialog').find('#content');
	var $hideEditor = '<?=$hideEditor?>';
	var $action = $('#action').val();
	var $clid = '<?=$clid?>';
	var $did = '<?=$did?>';

	if (!isMobile) {

		$('#dialog').css('width', '800px');

		if (in_array(action, ['add', 'edit'])) {

			var dwidth = $(document).width();
			var dialogWidth;
			var dialogHeight;

			if (dwidth < 945) {
				dialogWidth = '90%';
				dialogHeight = '95vh';
			}
			else {
				dialogWidth = '80%';
			}

			var hh = $('#dialog_container').actual('height') * 0.8;
			var hh2 = hh - $('.zagolovok').actual('outerHeight') - $('.button--pane').actual('outerHeight') - 20;

			if (dwidth < 945) {
				$('#dialog').css({'width': dialogWidth, 'height': dialogHeight});
				$('.fmain').css({'height': 'unset', 'max-height': hh2 + 30});
			}
			else {
				$('.fmain').css({'max-height': hh2});
			}

		}

		$(".multiselect").multiselect({sortable: true, searchable: true});

	}
	else {

		var h2 = $(window).height() - $('.zagolovok').actual('outerHeight') - $('.button--pane').actual('outerHeight') - 50;
		$('#formtabs').css({'max-height': h2 + 'px', 'height': h2 + 'px'});

		$(".multiselect").addClass('wp97 h0');

	}

	$(function () {

		$(".connected-list").css('height', "300px");

		if ($hideEditor !== 'yes') {

			if ($elm.is('textarea')) {

				createEditor2();
				$elm.removeClass('required');

			}
			else
				$elm.css("height", "250px");

		}

		if ($('#filelist').is('div')) $('#filelist').load('/modules/comments/fileview.php?id=' + $('#id').val());

		$('#dialog').center();

	});

	//сделано так, чтобы хватался параметр $clid из формы иначе берет 0
	$(document)
		.off('click', '#dogovor')
		.on('click', '#dogovor', function () {

			$("#dogovor").autocomplete("/content/helpers/deal.helpers.php?action=doglist", {
				autofill: true,
				minChars: 2,
				cacheLength: 2,
				max: 30,
				selectFirst: false,
				multiple: false,
				delay: 10,
				matchSubset: 1,
				extraParams: {clid: $clid},
				formatItem: function (data, i, n, value) {
					return '<div id="selitemid-' + data[1] + '" data-clid="' + data[1] + '">' + data[0] + '&nbsp;<span class="pull-aright">[<span class="broun">' + data[5] + '</span>]</span><div class="blue smalltext">' + data[3] + '</div></div>';
				},
				formatResult: function (data) {
					return data[0];
				}
			});
			$("#dogovor").result(function (value, data) {

				$('#did').val(data[1]);
				$('#clid').val(data[2]);
				$('#client').val(data[3]);

				$clid = data[2];
				$did = data[1];

				if (data[4] !== '')
					$("#pid_list").append('<div class="infodiv h0 fs-10 flh-12" title="' + data[6] + '"><INPUT type="hidden" name="pid[]" id="pid[]" value="' + data[4] + '"><div class="el"><div class="del"><i class="icon-cancel-circled"></i></div>' + data[6] + '</div></div>');

			});

		});

	$("#client")
		.autocomplete('/content/helpers/client.helpers.php?action=clientlist&strong=yes', {
			autofill: false,
			minChars: 2,
			cacheLength: 1,
			max: 30,
			selectFirst: false,
			multiple: false,
			delay: 500,
			matchSubset: 1,
			formatItem: function (data, j, n, value) {
				return '<div onclick="selItem(\'client\',\'' + data[1] + '\')">' + data[0] + '&nbsp;[<span class="red">' + data[2] + '</span>]</div>';
			},
			formatResult: function (data) {
				return data[0];
			}
		})
		.result(function (value, data) {

			$('#clid').val(data[1]);
			$clid = data[1];

		});

	$('#prj')
		.autocomplete("/modules/projects/core.projects.php?action=getProject", {
			autofill: true,
			minChars: 0,
			cacheLength: 2,
			maxItemsToShow: 10,
			selectFirst: false,
			multiple: false,
			delay: 500,
			matchSubset: 1,
			formatItem: function (data, j, n, value) {
				return '<div>' + data[1] + '</div>';
			},
			formatResult: function (data) {

				return data[1];

			}
		})
		.result(function (value, data) {

			$('#prj').val(data[1]);
			$('#project').val(data[0]);

			$('#clid').val(data[2]);
			$('#client').val(data[3]);

			$('#did').val(data[4]);
			$('#dogovor').val(data[5]);

		});

	function addTheme() {

		var $users = $('#users\\[\\] option:selected').length;
		var $idparent = parseInt($('#idparent').val());

		CKEDITOR.instances['content'].updateElement();

		if ($idparent === 0 && $users === 0) {

			Swal.fire({
					title: 'Вы ничего не забыли?',
					text: "Не указаны участники обсуждения!\nКонечно, это можно будет сделать позже!",
					type: 'warning',
					showCancelButton: true,
					confirmButtonColor: '#3085D6',
					cancelButtonColor: '#D33',
					confirmButtonText: 'Да, выполнить',
					cancelButtonText: 'Упс, реально забыл',
					confirmButtonClass: 'greenbtn',
					cancelButtonClass: 'redbtn'
				},
				function () {
					$('#sForm').trigger('submit')
				}
			).then((result) => {

				if (result.value) {

					$('#sForm').trigger('submit')

				}

			});

		}
		else
			$('#sForm').trigger('submit')

	}

	$('#sForm').ajaxForm({
		dataType: 'json',
		beforeSubmit: function () {

			var $el = $('#content');

			if ($action !== 'subscribe.user') {

				if (!editor && $el.val() === '' && $el.hasClass('required')) {

					Swal.fire("Не заполнено поле комментария", "", 'warning');
					return false;

				}
				else if ($('#idparent').val() === '' && $('#title').val() === '') {

					Swal.fire("Не заполнена тема обсуждения", '', 'warning');
					return false;

				}

			}

			$('#dialog').css('display', 'none');
			$('#dialog_container').css('display', 'none');

			if (editor2) remEditor();

			$('#message').fadeTo(10, 1).empty().css('display', 'block').append('<div id="loader"><img src="/assets/images/loader.gif"> Загрузка данных. Пожалуйста подождите...</div>');

		},
		success: function (data) {

			$('#message').fadeTo(1, 1).css('display', 'block').html(data.mes);
			setTimeout(function () {
				$('#message').fadeTo(1000, 0);
			}, 5000);

			var ida = data.id;

			if ($('#tar').is('input')) {

				//editComment(ida, 'viewshort', '');

			}
			//else if($('#isCard').val() == 'yes') settab('12');
			//else configpage();

			if (typeof settab == 'function') {
				settab('12');
			}
			if (typeof configpage == 'function') {
				configpage();
			}

			if (editor2) remEditor();
		}

	});

	function saveForm() {

		CKEDITOR.instances['content'].updateElement();

		$('#sForm').trigger('submit')

	}

	function createEditor2() {

		editor2 = CKEDITOR.replace('content',
			{
				width: '99.50%',
				height: '200px',
				extraPlugins: 'image2,textselection,base64image,codemirror,oembed,widget,autolink',
				filebrowserUploadUrl: '/modules/ckuploader/upload.php?type=kb',
				filebrowserImageBrowseUrl: '/modules/ckuploader/browse.php?type=kb',
				filebrowserBrowseUrl: '/modules/ckuploader/browse.php?type=kb',
				toolbar:
					[
						['Bold', 'Italic', 'Underline', 'Strike', '-', 'NumberedList', 'BulletedList', '-', 'Link', 'Unlink'],
						['-', 'Undo', 'Redo', '-', 'PasteText', 'PasteFromWord', 'Image', 'HorizontalRule', 'RemoveFormat'],
						['JustifyLeft', 'JustifyCenter', 'JustifyRight']
					]
			});

		CKEDITOR.on("instanceReady", function (event) {

			$('#dialog').center();

		});

	}

	function remEditor() {

		//var html = $('#cke_editor_content').html();

		if (editor2) {

			CKEDITOR.instances['content'].updateElement();
			//$('#content').val(html);
			editor2.destroy();
			editor2 = null;

		}

		return true;

	}

	function addfile() {

		var kol = $('.filebox').size();
		var i = kol + 1;
		var htmltr = '<div id="file-' + i + '" class="filebox wp99"><input name="file[]" type="file" class="file" id="file[]" onchange="addfile();" multiple><div class="delfilebox hand" onclick="deleteFilebox(\'file-' + i + '\')" title="Очистить"><i class="icon-cancel-circled red"></i></div></div>';

		$('#uploads').append(htmltr);
		$('#dialog').center();

	}

	function DClose2() {

		remEditor();
		DClose();

	}

</script>