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
$id     = $_REQUEST['id'];

if ( $maxupload == '' ) {
	$maxupload = str_replace([
		'M',
		'm'
	], '', @ini_get('upload_max_filesize'));
}

$uploaddir = $rootpath.'/cash/'.$fpath.'templates/';
$ext_allow = [
	"docx",
	"xlsx"
];

if ( $action == "delete" ) {

	$result = $db -> getRow( "SELECT * FROM {$sqlname}contract_temp where id = '$id' and identity = '$identity'" );
	$file   = $result["file"];

	$db -> query( "delete from {$sqlname}contract_temp where id = '$id' and identity = '$identity'" );
	$action = false;

	unlink( $uploaddir.$file );

	if($_REQUEST['face'] == 'other'){

		print "Сделано";
		exit();

	}

}

if ( $action == "edit.on" ) {

	$title   = $_REQUEST['title'];
	$typeid  = $_REQUEST['typeid'];
	$oldfile = '';

	if ( $id > 0 ) {

		$result = $db -> getRow( "SELECT file FROM {$sqlname}contract_temp where id = '$id' and identity = '$identity'" );
		$file   = $oldfile = $result["file"];

	}

	if ( filesize( $_FILES['file']['tmp_name'] ) > 0 ) {

		if ( $oldfile != '' )
			unlink( $uploaddir.$oldfile );

		$file       = str_replace( [" ", "-"], "_", texttosmall( translit( basename( $_FILES[ 'file' ][ 'name' ] ) ) ) );
		$uploadfile = $uploaddir.$file;

		$ext = texttosmall( getExtention($file) );

		if ( !in_array( $ext, $ext_allow, true ) )
			$message = 'Ошибка: поддерживаются только файлы docx, xlsx';


		if ( in_array( $ext, $ext_allow, true ) ) {

			if ( (filesize( $_FILES['file']['tmp_name'] ) / 1000000) > $maxupload )
				$message = 'Ошибка при загрузке файла '.$file.' - Превышает допустимые размеры!';

			else {

				if ( move_uploaded_file( $_FILES['file']['tmp_name'], $uploadfile ) )
					$message = 'Файл шаблона успешно загружен.';

				else
					$message = 'Ошибка: '.$_FILES['file']['error'];

			}

		}

	}

	if ( $type == 'get_dogovor' || $type == 'get_akt' )
		$num = $format = '';

	if ( $id > 0 ) {

		$db -> query( "UPDATE {$sqlname}contract_temp SET ?u WHERE id = '$id' and identity = '$identity'", [
			'title'  => $title,
			'typeid' => $typeid,
			'file'   => $file
		] );

		print $message.'<br>Сделано';

	}
	else {

		$db -> query( "INSERT INTO {$sqlname}contract_temp SET ?u", [
			"typeid"   => $typeid,
			"title"    => $title,
			"file"     => $file,
			"identity" => $identity
		] );

		print 'Сделано';

	}

}

if ( $action == "edit" ) {

	if ( $id > 0 ) {

		$result = $db -> getRow( "SELECT * FROM {$sqlname}contract_temp where id = '$id' and identity = '$identity'" );
		$title  = $result["title"];
		$typeid = $result["typeid"];
		$file   = $result["file"];

	}

	if((int)$_REQUEST['idtype'] > 0){
		$typeid = (int)$_REQUEST['idtype'];
	}

	if ( !is_writable( $uploaddir ) ) {
		print '
		<div class="warning wp100">
			<span><i class="icon-attention red icon-5x pull-left"></i></span>
			<b class="red uppercase">Внимание:</b><br><br>Ошибка: папка недоступна для записи.<br>Установите права на папку "<b>'.$uploaddir.'</b>" , равные 0777 (xrw-xrw-xrw)!.
		</div>
		';
		exit;
	}
	?>
	<form method="post" action="/content/admin/<?php echo $thisfile; ?>" enctype="multipart/form-data" name="form" id="form" autocomplete="off">
		<input name="id" type="hidden" id="id" value="<?= $id ?>">
		<input type="hidden" id="action" name="action" value="edit.on">
		<div class="zagolovok">Редактирование</div>

		<div class="flex-container pl10 pr10">

			<div class="flex-string wp100 label fs-07">Название шаблона</div>
			<div class="flex-string wp100">

				<INPUT name="title" type="text" class="required wp100" id="title" value="<?= $title ?>">

			</div>

		</div>
		<div class="flex-container pl10 pr10">

			<div class="flex-string wp100 label fs-07">Прикрепить к документу</div>
			<div class="flex-string wp100">

				<select name="typeid" id="typeid" class="required wp100">
					<option value="">--выбор--</option>
					<?php
					$result = $db -> getAll( "SELECT * FROM {$sqlname}contract_type WHERE COALESCE(type, '') NOT IN ('get_akt','get_aktper') and identity = '$identity' ORDER BY title" );
					foreach ( $result as $data ) {
						$s = ($data['id'] == $typeid) ? 'selected' : '';
						print '<option value="'.$data['id'].'" '.$s.'>'.$data['title'].'</option>';
					}
					?>
				</select>

			</div>

		</div>
		<div class="flex-container pl10 pr10">

			<div class="flex-string wp100 label fs-07">Файл шаблона</div>
			<div class="flex-string wp100" id="uploads">

				<input name="file" type="file" id="file" class="wp100">
				<div class="smalltxt">Только в формате docx, xlsx</div>

			</div>

		</div>

		<hr>

		<div class="text-right">

			<A href="javascript:void(0)" onclick="$('#form').trigger('submit')" class="button">Сохранить</A>&nbsp;
			<A href="javascript:void(0)" onclick="DClose()" class="button">Отмена</A>

		</div>
	</FORM>
	<?php
}

if ( !$action ) {
	?>
	<h2>&nbsp;Раздел: "Шаблоны документов"</h2>

	<?php
	if(!$otherSettings['contract']){

		print '<div class="warning mb10">Ведение Договоров <b>отключено</b> (см. Общие настройки / Дополнения к сделкам)</div>';

	}
	?>

	<?php
	if ( !is_writable( $uploaddir ) ) {

		print '
		<div class="warning wp100">
			<span><i class="icon-attention red icon-5x pull-left"></i></span>
			<b class="red uppercase">Внимание:</b><br><br>Ошибка: папка недоступна для записи.<br>Установите права на папку "<b>'.$uploaddir.'</b>" , равные 0777 (xrw-xrw-xrw)!.
		</div>
		';

		exit();

	}

	$typea = $db -> getIndCol( "id", "SELECT title, id FROM {$sqlname}contract_type WHERE identity = '$identity' ORDER by title" );

	//шаблоны документов, кроме счетов
	$list   = [];
	$result = $db -> getAll( "SELECT * FROM {$sqlname}contract_temp WHERE typeid NOT IN (SELECT id FROM {$sqlname}contract_type WHERE COALESCE(type, '') IN ('invoice','get_akt','get_aktper') AND identity = '$identity') AND identity = '$identity' ORDER by title" );
	foreach ( $result as $data ) {

		$list[ $data['typeid'] ][] = [
			"id"    => $data['id'],
			"title" => $data['title'],
			"file"  => $data['file'],
		];

	}
	?>

	<div class="flex-container box--child p10 fs-11 graybg no-border Bold hidden-iphone sticked--top">

		<div class="flex-string wp65">Название шаблона</div>
		<div class="flex-string wp30 hidden-iphone">Файл</div>
		<div class="flex-string wp5 hidden-iphone"></div>

	</div>

	<?php
	foreach ( $list as $type => $credits ) {

		print '
			<div class="flex-container box--child p10 border-bottom bluebg-sub">
		
				<div class="flex-string wp100 fs-12 blue Bold">
					'.strtr( $type, $typea ).'
				</div>
		
			</div>
		';

		foreach ( $credits as $credit ) {

			print '
			<div class="flex-container box--child p10 border-bottom relativ ha">
		
				<div class="flex-string wp65 nopad middle">
				
					<span class="fs-12 Bold gray-dark">ID '.$credit['id'].':</span>&nbsp;<span class="fs-10 Bold pt10">'.$credit['title'].'</span>&nbsp;
					<A href="javascript:void(0)" onclick="doLoad(\'/content/admin/'.$thisfile.'?action=edit&id='.$credit['id'].'\');" class="gray"><i class="icon-pencil"></i></A>
					
				</div>
				<div class="flex-string wp30">
					<div>'.$credit['file'].' <a href="/cash/'.$fpath.'templates/'.$credit['file'].'" title="Загрузить"><i class="icon-attach-1 blue"></i></a></div>
					<div class="smalltxt blue">
						'.num_format( round( filesize( $uploaddir.$credit['file'] ) / 1024, 2 ) ).'&nbsp;Kb / '.date( "d-m-Y", filemtime( $uploaddir.$credit['file'] ) ).'
					</div>
				</div>
				<div class="flex-string wp5 hidden-iphone">
					<A href="javascript:void(0)" onclick="cf=confirm(\'Вы действительно хотите удалить запись?\');if (cf)refresh(\'contentdiv\',\'/content/admin/'.$thisfile.'?id='.$credit['id'].'&action=delete\');"><i class="icon-cancel-circled red"></i></A>
				</div>
		
			</div>
			';

		}

	}
	?>

	<div class="button--group1 box--child" style="position: fixed; bottom: 40px; left: 380px; z-index: 100;">

		<a href="javascript:void(0)" onclick="doLoad('/content/admin/<?php echo $thisfile; ?>?action=edit');" class="button bluebtn box-shadow" title="Добавить"><i class="icon-plus-circled"></i>Добавить</a>

	</div>

	<div class="pagerefresh refresh--icon admn green" onclick="doLoad('/content/admin/<?php echo $thisfile; ?>?action=edit');" title="Добавить"><i class="icon-plus-circled"></i></div>
	<div class="pagerefresh refresh--icon admn orange" onclick="openlink('https://salesman.pro/docs/52')" title="Документация"><i class="icon-help"></i></div>

	<div class="space-100"></div>
	<?php
}
?>
<script>

	if (!isMobile) $('#dialog').css('width', '500px');

	$(function () {

		$(".multiselect").multiselect({sortable: true, searchable: true});
		$(".connected-list").css('height', "100px");

		$('#form').ajaxForm({
			beforeSubmit: function () {

				var em = checkRequired();

				if (em === false) return false;

				$('#dialog').css('display', 'none').css('width', '500px');
				$('#dialog_container').css('display', 'none');

				$('#message').css('display', 'block').append('<div id=loader><img src=/assets/images/loader.gif> Загрузка данных...</div>');

				return true;

			},
			success: function (data) {

				//$("#contentdiv").load('content/admin/<?php echo $thisfile; ?>');
				razdel(hash);

				$('#resultdiv').empty();

				$('#message').fadeTo(1, 1).css('display', 'block').html('Результат: ' + data);

			}
		});

		$('#dialog').center();

	});

	function addItem(txtar, myitem) {

		var textt = $('#' + txtar).val();
		$('#' + txtar).val(textt + '{' + myitem + '}');

	}

	function changeVis() {

		var tip = $('#type option:selected').val();

		if (tip === 'get_dogovor' || tip === 'get_akt') {

			$('.contract').addClass('hidden');
			$('#dialog').center();

		}
		else {

			$('.contract').removeClass('hidden');
			$('#dialog').center();

		}

	}

</script>