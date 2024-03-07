<?php
/* ============================ */
/*         SalesMan CRM         */
/* ============================ */
/* (C) 2016 Vladislav Andreev   */
/*       SalesMan Project       */
/*        www.isaler.ru         */
/*        ver. 2017.x           */

/* ============================ */

use Salesman\Upload;

error_reporting(E_ERROR);

header("Pragma: no-cache");

$rootpath = dirname(__DIR__, 2);

include $rootpath."/inc/config.php";
include $rootpath."/inc/dbconnector.php";
include $rootpath."/inc/auth.php";
include $rootpath."/inc/func.php";
include $rootpath."/inc/settings.php";
include $rootpath."/inc/language/".$language.".php";

$thisfile = basename(__FILE__);

global $userRights;

if ($acs_files != 'on') {

	print "<div class=\"bad\" align=\"center\"><br>Доступ запрещен.<br>Обратитесь к администратору.<br /><br /></div>";
	exit();

}

$clid = (int)$_REQUEST['clid'];
$pid  = (int)$_REQUEST['pid'];
$did  = (int)$_REQUEST['did'];

$fileSort = $_COOKIE['fileSort'];

$x = Upload ::cardFiles([
	"clid"     => $clid,
	"pid"      => $pid,
	"did"      => $did,
	"fileSort" => $fileSort
]);

//print_r($x);

$list = $x['list'];

$f = '';

//сортируем массив документов по давности
( $fileSort != 'desc' ) ? krsort($list) : ksort($list);

$ssort = ( $fileSort == 'desc' ) ? '' : 'desc';
$icon  = ( $fileSort == 'desc' ) ? 'icon-sort-alt-down' : 'icon-sort-alt-up';

foreach ($list as $fid => $file) {

	$s  = '';
	$fd = '';
	$fh = '';

	if (isViewable($file['name'])) {
		$s .= '<A href="javascript:void(0)" onclick="fileDownload(\''.$fid.'\',\'\',\'\')"><i class="icon-eye broun" title="Просмотр"></i></A>&nbsp;';
	}

	$s .= '<A href="javascript:void(0)" onclick="fileDownload(\''.$fid.'\',\'\',\'yes\')"><i class="icon-download blue" title="Скачать"></i></A>&nbsp;';

	if (get_accesse((int)$clid, (int)$pid, (int)$did) == "yes") {

		$s .= '<A href="javascript:void(0)" onclick="editUpload(\''.$fid.'\',\'edit\');"><i class="icon-pencil green" title="Изменить"></i></A>&nbsp;';

		if ($userRights['delete']) {
			$s .= '<A href="javascript:void(0)" onclick="cf=confirm(\'Вы действительно хотите удалить файл?\');if (cf)editUpload(\''.$fid.'\',\'delete\');"><i class="icon-cancel-circled red" title="Удалить"></i></A>';
		}

	}

	if ($file['size'] == 0) {
		$fd = 'nofind';
		$fh = 'disabled';
	}

	$f .= '
		<div class="ha flex-container bgwhite box--child p10 mb5 box-shadow focused">
		
			<div class="flex-string wp60">
			
				<div class="fs-12">
					<div class="inline hi60 w20 hidden-iphone"><input type="checkbox" id="fid[]" name="fid[]" value="'.$fid.'" '.$fh.'></div>
					<A href="javascript:void(0)" onclick="editUpload(\''.$fid.'\',\'info\');"><span class="ellipsis '.$fd.'">'.$file['icon'].'&nbsp;<b>'.$file['name'].'</b></span></A>
				</div>
				
				<div class="fs-09 mt10">
					<span title="'.$file['folder'].'" class="gray"><i class="icon-folder"></i>&nbsp;'.$file['folder'].'</span>
					<span class="gray">&nbsp;<i class="icon-clock"></i><b>'.$file['date'].'</b></span>
					'.( !empty($file['version']) ? '<span class="gray">&nbsp;<i class="icon-info-1"></i><B>'.$file['version'].'</B></span>' : '' ).'
					'.( $file['did'] > 0 ? '<span class="gray">&nbsp;<i class="icon-briefcase-1"></i><b>'.current_dogovor($file['did']).'</b></span>' : '' ).'
				</div>
				
			</div>
			<div class="flex-string wp20 nowrap">
				<div><B>'.num_format($file['size']).'</B>&nbsp;kb&nbsp;</div>
			</div>
			<div class="flex-string wp20 right-text nowrap mob-pull-right">'.$s.'&nbsp;</div>
			
		</div>
	';

}

if ($x['total'] == 0) {
	$f = '<div class="fcontainer fs-09 gray mp10">Файлы отсутствуют</div>';
}

?>


<div class="inline pull-left Bold <?= ( $all == 0 ? 'hidden' : '' ) ?>" style="position:absolute; top:-30px">

	<a href="javascript:void(0)" onclick="setCookie('fileSort', '<?= $ssort ?>', {expires:31536000}); settab('6')" class="gray" title="Изменить сортировку"><i class="<?= $icon ?> broun"></i> Сортировка</a>

</div>
<div class="fcontainer1 mt10">

	<FORM id="filesForm">

		<DIV style="max-height:80vh; overflow:auto !important" id="fileList"><?= $f ?></DIV>

	</FORM>

</div>

<script>

	var fcount;

	$(function () {

		fcount = $('#fid\\[\\]:enabled').length;

		if (parseInt(fcount) > 0)
			$('.zip').removeClass('hidden');
		else
			$('.zip').addClass('hidden');

		$('.zip span').html(' ( ' + fcount + ' )');

	});

	$('#tab6 input:checkbox').bind('click', function () {

		var countt = $('#tab6 input:checkbox:checked').length;

		if (countt === 0) countt = fcount;

		$('.zip span').html(' ( ' + countt + ' )');

	});

	function getZip() {

		var url = "modules/upload/core.upload.php?action=zip&card=" + $('#card').val() + "&clid=" + $('#clid').val() + "&pid=" + $('#pid').val() + "&did=" + $('#did').val() + "&" + $('#filesForm').serialize();

		window.open(url);

	}
</script>