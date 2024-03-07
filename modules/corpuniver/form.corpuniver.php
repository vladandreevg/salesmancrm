<?php
/* ============================ */
/*         SalesMan CRM         */
/* ============================ */
/*  (C) 2019 Ivan Drachyov      */
/*       SalesMan Project       */
/*        www.isaler.ru         */
/*        ver. 2019.x           */
/* ============================ */

use Salesman\CorpUniver;
use Salesman\Elements;use Salesman\Upload;

error_reporting(E_ERROR);

header("Pragma: no-cache");
header('Content-Type: text/html; charset=utf-8');

$rootpath = dirname(__DIR__, 2);

include $rootpath."/inc/config.php";
include $rootpath."/inc/dbconnector.php";
include $rootpath."/inc/auth.php";
include $rootpath."/inc/func.php";
include $rootpath."/inc/settings.php";
include $rootpath."/inc/language/".$language.".php";

$thisfile = basename( __FILE__ );

$action = $_REQUEST['action'];

$mdcset = $db -> getRow("SELECT * FROM ".$sqlname."modules WHERE mpath = 'corpuniver' and identity = '$identity'");
$mdcsettings = json_decode($mdcset['content'], true);

$icoNMaterial = [
	"video"    => "icon-video",
	"audio"    => "icon-volume-up",
	"image"    => "icon-picture",
	"resource" => "icon-link-1",
	"text"     => "icon-doc-text",
	"file"     => "icon-attach-1"
];

// Окно редактирования курса
if ($action == "edit") {

	$id = (int)$_REQUEST['id'];

	$title = ($id > 0) ? 'Редактирование курса' : 'Создание курса';

	$categories = '';

	$result = CorpUniver::info($id)['data'];
	$idcat = $result["cat"];
	$cat = $db->getOne("SELECT title FROM ".$sqlname."corpuniver_course_cat WHERE id = '$idcat' AND identity = '$identity'");

	$list_cats = $db->getAll("SELECT * FROM ".$sqlname."corpuniver_course_cat WHERE identity = '$identity'");

	$files = ($result['fid'] != '') ? '<div class="infodiv" id="filelist"></div>' : '<div class="attention">Файлы отсутствуют</div>';

	foreach ($list_cats as $c) {

		$categories .= '<option '.($c['id'] == $idcat ? 'selected' : '').' value="'.$c['id'].'">'.$c['title'].'</option>';

	}

	?>
		<FORM action="/modules/corpuniver/core.corpuniver.php" method="post" enctype="multipart/form-data" name="Form" id="Form">
		<INPUT type="hidden" name="action" id="action" value="edit.item">
		<INPUT type="hidden" name="id" id="id" value="<?=$id?>">
		<INPUT type="hidden" name="type" id="type" value="Course">
		
			<div class="body" style="height:calc(100vh - 60px)">
			
				<div class="m0 p0 pb20 mt10 blue fs-16 Bold uppercase"><?=$title?></div>

				<div class="flex-container mt20 gray2 pl5">

					<div class="flex-string wp100 mb5 uppercase "><b>Название:</b></div>
					<div class="flex-string wp100">

						<input type="text" id="name" name="name" class="wp99 required" placeholder="Введите название" value="<?=$result['name']?>">
						<span class="idel mt5" style="height:10px;" >
							<i title="Очистить" onclick="$('#name').val('');" class="icon-block red hand mr10"></i>
						</span>

					</div>

				</div>
				<div class="flex-container gray2 mt10 pl5">

					<div class="flex-string wp100 mb5 uppercase "><b>Категория:</b></div>
					<div class="flex-string">

						<span class="select">
							<select name="cat" id="cat" class="wp30 required" onchange=""><?=$categories?></select>
						</span>
						<a href="javascript:void(0)" onclick="addCat()" class="middle pb10 pl10 fs-11" id="newCatCoursebtn"><i class="icon-plus green"></i>Добавить новую</a>

					</div>

				</div>
				<div class="flex-container gray2 mt10 pl5 hidden" id="newCatCourse">

					<div class="flex-string wp90 mb5 uppercase"><b>Новая категория:</b></div>
					<div class="flex-string wp5">

						<a href="javascript:void(0)" onclick="hideAddCat()" class="underline red">убрать</a>

					</div>
					<div class="flex-string mb5">

						<input type="text" id="catNew" name="catNew" class="wp99" placeholder="Введите название категории">
						<span class="idel mt5" style="height:10px;">
							<i title="Очистить" onclick="$('#catNew').val('');" class="icon-block red hand mr10"></i>
						</span >
					</div>

				</div>

				<div class="gray2 mt10 pl5 uppercase Bold"><b>Описание:</b></div>

				<div class="mt5 pl5">
					<textarea name="content" id="content" class="wp99" placeholder="Введите описание курса" style="height:200px"><?=$result['des']?></textarea>
				</div>

				<div id="files" class="pt10 pb10">

					<div id="divider" class="div-center"><b><i class="icon-attach-1 fs-16"></i>Прикрепленные файлы</b></div>

					<?=$files?>

					<DIV id="uploads">
						<div id="file-1" class="filebox margbot0 margtop5" style="width:99.5%">
							<input name="file[]" type="file" class="file" id="file[]" multiple onchange="addfile();">
							<div class="delfilebox hand pt0" onclick="deleteFilebox('file-1')" title="Очистить">
								<i class="icon-cancel-circled red"></i></div>
						</div>
					</DIV>
					<div class="fs-09 gray ml5 mt5 em">При прохождении курса слушатели могут просматривать и скачивать файлы</div>

				</div>
			</div>

			<div class="footer pl10" style="height:60px">
				<a href="javascript:void(0)" onclick="$('#Form').submit()" class="button">Сохранить</a>
				<a href="javascript:void(0)" onclick="closeInfo()" class="button bcancel">Закрыть</a>
			</div>
		</FORM>
		<script>

			$('#Form').ajaxForm({
				dataType:"json",
				beforeSubmit: function () {

					var name = $('#name').val();

					if (name === '') {

						$('#name').css({"color": "#222", "background": "#FFE3D7"});

						const msg = Swal.mixin({
								toast: true,
								position: 'top-end',
								showConfirmButton: false,
								timer: 3000
							});

							msg.fire(
								'Вы не указали название!',
								'',
								'warning'
							);

						return false;

					}

					return true;

				},
				success: function (data) {

					const msg = Swal.mixin({
								toast: true,
								position: 'top-end',
								showConfirmButton: false,
								timer: 3000
							});

					if(data !== 'Error'){
							msg.fire(
								data.result,
								'',
								'success'
							);
					}

					closeInfo();

					configpage();

					$('.ifolder').load('modules/corpuniver/core.corpuniver.php?action=catlist');

				}
			});

		</script>
	<?php

	exit();
}

// Изменение лекции/материала. Старое
if ($action == 'edit.item') {

	$id = $_REQUEST['id'];
	$type = $_REQUEST['type'];
	$lec = $_REQUEST['lec'];
	$course = $_REQUEST['course'];

	$content = '';

	if ($type == 'Lecture') {

		$title = 'Название лекции';

		if ($id > 0)
			{$item = CorpUniver::infoLecture($id)['data'];}

	}
	elseif ($type == 'Material') {

		$title = 'Название учебного материала';
		$source = '';

		$hideRes = $hideFid = 'hidden';

		$item['type'] = 'file';

		if ($id > 0)
			{$item = CorpUniver::infoMaterial($id)['data'];}

		// ссылка на сторонний ресурс
		if ($item['source'] != '') {

			$page_content = file_get_contents($item['source']);
			preg_match_all("|<title>(.*)</title>|sUSi", $page_content, $titles);
			$source = $titles[1][0] . '<A href="javascript:void(0)" onClick="delResMat();" title="Удалить"><i class="icon-cancel red"></i></A>';
			$hideResbtn = 'hidden';

		}

		if ($item['source'] != '') {

			$checkRes = 'checked';
			$hideRes = '';

		}
		else {

			$checkFid = 'checked';
			$hideFid = '';

		}

		$content = '
		<div class="pt10" id="checkcontainer">
		
			<div class="flex-container box--child">
			
				<div class="flex-string wp30 infodiv bgwhite radio mt5 ml5">
				
					<label>
						<input name="tip" type="radio" id="tip" value="file" class="tip" onClick="addMat()" '.($item['type'] == 'file' ? 'checked' : '').'>
						<span class="custom-radio" style="top:0"><i class="icon-radio-check"></i></span>
						<span class="title Bold">Файл</span>
						<input name="file[]" type="file" class="file hidden" id="file[]" multiple onchange="getFileMaterial();">
					</label>
					
				</div>
				
				<div class="flex-string wp30 infodiv bgwhite radio mt5 ml5">
				
					<label>
					<input name="tip" type="radio" id="tip" value="resource" class="tip" onClick="addMat()" '.($item['type'] == 'resource' ? 'checked' : '').'>
						<span class="custom-radio" style="top:0"><i class="icon-radio-check"></i></span>
						<span class="title Bold pt10">Сторонний ресурс</span>
						<input name="source" type="text" class="hidden" id="source" value="'.$item['source'].'">
					</label>
					
				</div>
				
				<div class="flex-string wp30 infodiv bgwhite radio mt5 ml5">
				
					<label>
						<input name="tip" type="radio" id="tip" value="text" class="tip" onClick="addMat()"  '.($item['type'] == 'text' ? 'checked' : '').'>
						<span class="custom-radio" style="top:0"><i class="icon-radio-check"></i></span>
						<span class="title Bold">Свой текст</span>
						
					</label>
					
				</div>
			
			</div>
			
			<span id="filesMat" class="infodiv fs-08 mt5 '.$hideFid.'">
			
				<A href="javascript:void(0)" id="addFilebtn" onClick="$(\'.file\').click();" class="button">выбрать файл</A>
				<span id="filelist" class="flex-container pl20 hidden"></span>
				
			</span>
			
			<span id="addRes" class="infodiv fs-08 mt5 '.$hideRes.'">
			
				<A href="javascript:void(0)" onClick="getLinkMaterial()" id="addResbtn" class="button '.$hideResbtn.'">указать ссылку на источник</A>
				<span id="resource" class="pt5 ml5 blue Bold">'.$source.'</span>
				
			</span>
			
			<div class="hidden pt10 pb10" id="addText">
				
				<div class="flex-container float infodiv">
				
					<div class="flex-string w120 fs-09 gray2 pt7 Bold">Загрузить из url:</div>
					<div class="flex-string float fs-09">
						<input id="fromurl" class="wp99">
					</div>
					<div class="flex-string w60">
						<a href="javascript:void(0)" onclick="parceURL()" class="button dotted greenbtn" title="Загрузить"><i class="icon-check"></i></a>
					</div>
					
				</div>
				
				<hr>
				
				<TEXTAREA name="content" id="content">'.htmlspecialchars_decode($item['text']).'</TEXTAREA>
				
			</div>
			
		</div>';

	}

	// Поле ввода
	$Element = new Elements();
	$name = $Element::InputText("name", $item['name'], [
		"class" => "wp100",
		"other" => 'placeholder="Введите название"'
	]);

	?>

	<FORM method="post" action="/modules/corpuniver/core.corpuniver.php" enctype="multipart/form-data" name="editItem" id="editItem">
		<input name="id" type="hidden" id="id" value="<?=$id?>">
		<input name="type" type="hidden" id="type" value="<?=$type?>">
		<input name="lec" type="hidden" id="lec" value="<?=$lec?>">
		<input type="hidden" id="action" name="action" value="edit.item">
		<input type="hidden" id="course" name="course" value="<?=$course?>">

		<div id="pole">
			<div class="uppercase Bold fs-07 gray"><?=$title?></div>
			<?=$name?>
			<?=$content?>
		</div>

		<div class="text-right button--pane">
			<A href="javascript:void(0)" id="saveitem" onClick="btn_submit()" class="button bluebtn m0 mr10 ptb5">Сохранить</A>
			<A href="javascript:void(0)" class="button cancelbtn m0 ptb5" onClick="edit_close()">Отмена</A>
		</div>

	</FORM>

	<script>

		var tip = '<?=$item['type']?>';
		var course = $('#course').val();

		if(tip === 'text')
			addMat();

		$('#editItem').ajaxForm({
			dataType:"json",
			beforeSubmit: function () {

				var name = $('#name').val();

				if (name === '') {

					$('#name').css({"color": "#222", "background": "#FFE3D7"});

					Swal.fire({
						title: 'Ошибка',
						text: "Вы не указали название!",
						type: 'error',
						showCancelButton: false,
						confirmButtonColor: '#32CD32',
					});

					return false;

				}

				return true;

			},
			success: function (data) {
				
				const msg = Swal.mixin({
							toast: true,
							position: 'top-end',
							showConfirmButton: false,
							timer: 3000
						});

				msg.fire(
					data.result,
					'',
					'success'
				);

				editCourse(course, 'viewshort');

			},
			error: function(er){

				console.log(er);

			}

		});

		function parceURL(){

			let url = $('#fromurl').val();

			fetch("modules/corpuniver/core.corpuniver.php?action=parceURL&url=" + url)
				.then(response => response.json())
					.then(data => {

						//console.log(data);

						if(data.error)
							Swal.fire({
								title: 'Ошибка',
								text: data.error,
								type: 'error',
								showCancelButton: true
							});

						else{

							$('#name').val(data.title);

							let oEditor = CKEDITOR.instances.content;
							oEditor.insertHtml(data.content);

						}

					})
						.catch(error => {

							console.log(error);

							Swal.fire({
								title: 'Ошибка',
								text: error,
								type: 'error',
								showCancelButton: true
							});

						});

		}

	</script>

<?php
}

// Изменение лекции/материала. Новое - в модальном окне
if ($action == 'edit.item.dialog') {

	$id     = (int)$_REQUEST['id'];
	$type   = $_REQUEST['type'];
	$lec    = (int)$_REQUEST['lec'];
	$course = (int)$_REQUEST['course'];

	$content = '';

	if ($type == 'Lecture') {

		$title = 'Название лекции';

		if ($id > 0)
			{$item = CorpUniver::infoLecture($id)['data'];}

	}
	elseif ($type == 'Material') {

		$title = 'Название учебного материала';
		$source = '';
		$file = [];

		$hideRes = $hideFid = 'hidden';

		$item['type'] = 'file';

		if ($id > 0){
			$item = CorpUniver::infoMaterial($id)['data'];
		}

		// ссылка на сторонний ресурс
		if ($item['source'] != '') {

			$page_content = file_get_contents($item['source']);
			preg_match_all("|<title>(.*)</title>|sUSi", $page_content, $titles);
			$source = $titles[1][0] . '<A href="javascript:void(0)" onClick="delResMat();" title="Удалить"><i class="icon-cancel red"></i></A>';
			$hideResbtn = 'hidden';

		}

		if ($item['source'] != '') {

			$checkRes = 'checked';
			$hideRes = '';

		}
		else {

			$checkFid = 'checked';
			$hideFid = '';

		}

		if($item['fid'] > 0){

			$file = Upload::info($item['fid']);

			//print_r($file);

		}

		$content = '
		<div class="pt10" id="checkcontainer">
		
			<div class="flex-container box--child">
			
				<div class="flex-string wp30 infodiv bgwhite radio mt5 ml5">
				
					<label>
						<input name="tip" type="radio" id="tip" value="file" class="tip" onClick="addMat()" '.($item['type'] == 'file' ? 'checked' : '').'>
						<span class="custom-radio"><i class="icon-radio-check"></i></span>
						<span class="title Bold">Файл</span>
						<input name="file[]" type="file" class="file hidden" id="file[]" multiple onchange="getFileMaterial();">
					</label>
					
				</div>
				
				<div class="flex-string wp30 infodiv bgwhite radio mt5 ml5">
				
					<label>
					<input name="tip" type="radio" id="tip" value="resource" class="tip" onClick="addMat()" '.($item['type'] == 'resource' ? 'checked' : '').'>
						<span class="custom-radio"><i class="icon-radio-check"></i></span>
						<span class="title Bold pt10">Сторонний ресурс</span>
						<input name="source" type="text" class="hidden" id="source" value="'.$item['source'].'">
					</label>
					
				</div>
				
				<div class="flex-string wp30 infodiv bgwhite radio mt5 ml5">
				
					<label>
						<input name="tip" type="radio" id="tip" value="text" class="tip" onClick="addMat()"  '.($item['type'] == 'text' ? 'checked' : '').'>
						<span class="custom-radio"><i class="icon-radio-check"></i></span>
						<span class="title Bold">Свой текст</span>
						
					</label>
					
				</div>
			
			</div>
			
			<span id="filesMat" class="infodiv fs-08 mt5 '.$hideFid.'">
				
				<A href="javascript:void(0)" id="addFilebtn" onClick="$(\'.file\').click();" class="button '.($item['fid'] > 0 ? 'hidden' : '').'">выбрать файл</A>
				
				<span id="filelist" class="flex-container '.($item['fid'] > 0 ? '' : 'hidden').'">
				
					<div class="viewdiv flex-string" id="newFile">
						<i class="'.$file['icon'].'"></i>'.$file['title'].'<A href="javascript:void(0)" onClick="delFileMat();" title="Удалить"><i class="icon-cancel red"></i></A>
					</div>
					
				</span>
			
				<div class="loadr hidden"><img src="/assets/images/loading.svg"></div>
				<div class="attention mt10">
					<p>После загрузки файлы типов "docx", "doc", "rtf", "pptx", "ppt" будут конвертированы в PDF, если ОС поддерживает такую конвертацию.</p>
					<p>Максимальный размер файла: '.$maxupload.' Mb</p>
					<p>Разрешенные типы файлов: '.$ext_allow.'</p>
				</div>
				
			</span>
			
			<span id="addRes" class="infodiv fs-08 mt5 '.$hideRes.'">
			
				<A href="javascript:void(0)" onClick="getLinkMaterial()" id="addResbtn" class="button '.$hideResbtn.'">указать ссылку на источник</A>
				<span id="resource" class="pt5 ml5 blue Bold">'.$source.'</span>
				
			</span>
			
			<div id="addText" class="hidden pt10 pb10">
				
				<div class="flex-container float infodiv">
				
					<div class="flex-string float fs-09">
						<input id="fromurl" class="wp99" placeholder="Загрузить из url">
					</div>
					<div class="flex-string w60">
						<a href="javascript:void(0)" onclick="parceURL()" class="button dotted greenbtn m0 p5" title="Загрузить"><i class="icon-ok-circled"></i></a>
					</div>
					
				</div>
				
				<hr>
				
				<TEXTAREA name="content" id="content">'.htmlspecialchars_decode($item['text']).'</TEXTAREA>
				
			</div>
			
		</div>';

	}

	// Поле ввода
	$Element = new Elements();
	$name = $Element::InputText("name", $item['name'], [
		"class" => "wp100",
		"other" => 'placeholder="Введите название"'
	]);

	?>

	<div class="zagolovok">Добавление/Редактирование</div>
	<FORM method="post" action="/modules/corpuniver/core.corpuniver.php" enctype="multipart/form-data" name="editItem" id="editItem">
		<input name="id" type="hidden" id="id" value="<?=$id?>">
		<input name="type" type="hidden" id="type" value="<?=$type?>">
		<input name="lec" type="hidden" id="lec" value="<?=$lec?>">
		<input type="hidden" id="action" name="action" value="edit.item">
		<input type="hidden" id="course" name="course" value="<?=$course?>">

		<div id="pole" class="p5" style="overflow-y: auto; overflow-x: hidden; max-height:70vh">

			<div class="flex-container box--child mt10">

				<div class="flex-string wp100 uppercase Bold fs-07 gray"><?=$title?></div>
				<div class="flex-string wp100 relativ">

					<input type="text" name="name" id="name" class="wp100" value="<?=$item['name']?>" placeholder="Введите название">
					<div class="idel hand">
						<i title="Очистить" onclick="$('#name').val('');" class="icon-block red mr10"></i>
					</div>

				</div>

			</div>

			<?=$content?>

			<div class="space-40"></div>

		</div>

		<div class="text-right button--pane">

			<A href="javascript:void(0)" id="saveitem" onClick="btn_submit()" class="button bluebtn">Сохранить</A>
			<A href="javascript:void(0)" class="button cancelbtn" onClick="DClose()">Отмена</A>

		</div>

	</FORM>

	<script>

		var tip = '<?=$item['type']?>';
		var id = parseInt('<?=$id?>');

		if(tip === 'text')
			addMat();

		if($('#content').is('textarea'))
			$('#dialog').css('width', '800px').center();

		if(id > 0 && tip !== 'file')
			$('#filelist').removeClass('hidden').load('modules/corpuniver/core.corpuniver.php?action=files&type=' + tip + '&id=' + id);

		$('#editItem').ajaxForm({
			dataType:"json",
			beforeSubmit: function () {

				var name = $('#name').val();

				$('.loadr').removeClass('hidden');

				if (name === '') {

					$('#name').css({"color": "#222", "background": "#FFE3D7"});

					Swal.fire({
						title: 'Ошибка',
						text: "Вы не указали название!",
						type: 'error',
						showCancelButton: false,
						confirmButtonColor: '#32CD32',
					});

					$('.loadr').addClass('hidden');

					return false;

				}

				return true;

			},
			success: function (data) {

				var course = $('#course').val();

				const msg = Swal.mixin({
							toast: true,
							position: 'top-end',
							showConfirmButton: false,
							timer: 3000
						});

				DClose();

				msg.fire(
					data.result,
					'',
					'success'
				);

				editCourse(course, 'viewshort');

				$('.loadr').addClass('hidden');

			},
			error: function(er){

				console.log(er);

			}

		});

		function parceURL(){

			let url = $('#fromurl').val();

			fetch("modules/corpuniver/core.corpuniver.php?action=parceURL&url=" + url)
				.then(response => response.json())
					.then(data => {

						//console.log(data);

						if(data.error)
							Swal.fire({
								title: 'Ошибка',
								text: data.error,
								type: 'error',
								showCancelButton: true
							});

						else{

							$('#name').val(data.title);

							let oEditor = CKEDITOR.instances.content;
							oEditor.insertHtml(data.content);

						}

					})
						.catch(error => {

							console.log(error);

							Swal.fire({
								title: 'Ошибка',
								text: error,
								type: 'error',
								showCancelButton: true
							});

						});

		}

	</script>

<?php

}

// Редактирование задания
if ($action == "edit.task") {

	$print = '';
	$test = '';

	$idtask = $_REQUEST['id'];
	$lec = $_REQUEST['lec'];
	$task = [];

	if ($idtask > 0){
		$task = CorpUniver::infoTask($idtask)['data'];
	}

	$change = ($da['author'] == $iduser1 || $isadmin == 'on' || (in_array($iduser1, $mdcsettings['Editor']) && !in_array($iduser1, $mdcsettings['EditorMy']))) ? 'yes' : '';

	if ($idtask <= 0){

		$block = '
		<div class="flex-container gray2 mt20 pl5" id="typeTask">
			<div class="flex-string wp100 mb5 uppercase "><b>Тип задания:</b></div>
			<div class="flex-string">
				<span class="select">
					<select name="cat" id="cat" class="wp30 required">
					<option value="question">Вопрос</option>
					<option value="test">Тест</option>
				</select>
				</span>
			</div>
		</div>';

	}
	else{
		$block = '<INPUT type="hidden" name="cat" id="cat" value="' . $task['type'] . '">';
	}

	// Поля для задания типа "Вопрос"
	if ($task['type'] != 'test') {

		$question = CorpUniver::infoQuestion(0, $idtask);

		$ans = $question['answers'][0];

		$test = '
		<div class="type-question">
		
			<div class="gray2 mt10 pl5 uppercase Bold"><b>Текст вопроса:</b></div>
			<div class="mt5 pl5">
			
				<textarea name="question" id="question" class="wp99 required" placeholder="Введите текст вопроса" style="height:200px">' . $question['text'] . '</textarea>
				
			</div>
			
			<div class="gray2 pl5 mt10 uppercase Bold"><b>Ответ:</b></div>
			<div class="mt5 pl5">
				<input name="answer" id="answer" class="wp99 required" placeholder="Введите ответ" value="' . $ans['text'] . '">
			</div>
				
		</div>';

	}
?>

	<FORM action="/modules/corpuniver/core.corpuniver.php" method="post" enctype="multipart/form-data" name="Form" id="Form">
		<INPUT type="hidden" name="action" id="action" value="edit.item">
		<INPUT type="hidden" name="lec" id="lec" value="<?=$lec?>">
		<INPUT type="hidden" name="type" id="type" value="Task">
		<INPUT type="hidden" name="idtask" id="idtask" value="<?=$idtask?>">

		<div class="body" style="height:calc(100vh - 60px)">

			<div class="m0 p0 mt10 mb10 blue fs-16 Bold uppercase">редактирование задания</div>

			<?=$block?>

			<div class="mt10 p5 qButton">

				<div class="flex-container mt20 mb20 gray2 pl5 tskTitle">

					<div class="flex-string wp100 mb5 uppercase "><b>Название:</b></div>
					<div class="flex-string wp100 relativ">
						<input type="text" id="name" name="name" class="wp99 required" placeholder="Новое задание" value="<?=$task['name']?>">
						<span class="idel" style="height:10px;">
							<i title="Очистить" onclick="$('#name').val('');" class="icon-block red hand m0 mr10"></i>
						</span>
					</div>

				</div>

				<div data-type="Quest" data-id="0" data-task="<?=$idtask?>" class="button greenbtn editQuest type-test <?=($task['type'] != 'test' ? 'hidden' : '')?>">
					<i class="icon-plus whie"></i> Добавить вопрос
				</div>

			</div>

			<ul class="Questions mt20"></ul>

			<?=$test?>

			<div class="greenbg-sub p10 hidden" id="test"></div>

		</div>
	
		<div class="footer pl10" style="height:60px">

			<a href="javascript:void(0)" onclick="$('#Form').trigger('submit')" class="button">Сохранить задание</a>
			<a href="javascript:void(0)" onclick="closeInfo()" class="button bcancel">Закрыть</a>

		</div>

	</FORM>

	<script>

		var id = $('#id').val();
		var cat = $('#cat').val();
		var idtask = parseInt(<?=$idtask?>);
		
		$(function(){

			if (cat === 'test')
				$('.Questions').empty().load('modules/corpuniver/form.corpuniver.php?action=list.questions&idtask=' + idtask);

		});

		$(document).off('click', '.editQuest');
		$(document).on('click', '.editQuest', function(){

			//var task = parseInt($(this).data('task'));
			let id = parseInt($(this).data('id'));
			let name = $('#name').val();

			if(name === ''){

				const msg = Swal.mixin({
							toast: true,
							position: 'center',
							showConfirmButton: false,
							background: "var(--red-dark)",
							timer: 3000
						});

					msg.fire(
						'<span class="white fs-11">Внимание!</span><br>',
						'<span class="white">Укажите название!</span>',
						'warning'
					);

					return false;

			}
			else{

				if(idtask === 0){

					let lec = parseInt($('#lec').val());

					$.getJSON('modules/corpuniver/core.corpuniver.php?action=edit.item&type=Task&cat=test&idtask=' + idtask + '&lec=' + lec + '&name=' + name, function(data) {

						idtask = data.data;

						$('#idtask').val(data.data);

					})
					.done(function(){

						$.get('modules/corpuniver/form.corpuniver.php?action=question&idtask=' + idtask, function(html) {

							$('#test').empty().append(html).removeClass('hidden');
							$('.type-question').remove();
							$('#typeTask').addClass('hidden');

						});

					});

				}
				else{

					$.post('modules/corpuniver/form.corpuniver.php?action=question&id=' + id + '&idtask=' + idtask, function (data) {

						$('#test').empty().append(data).removeClass('hidden');
						$('.type-question').remove();
						$('#typeTask').addClass('hidden');

					});

				}

				$('.qButton').addClass('hidden');
				// эта инструкция не работает. хз почему
				$('div.footer').addClass('hidden');

			}

		});

		$('#Form').ajaxForm({
			dataType:"json",
			beforeSubmit: function () {

				var em = 0;

				$('.required').css({"color": "inherit", "background": "#FFF"});
				$('.required').each(function(){

					if($(this).val() === ''){

						$(this).addClass("empty").css({"color": "#222", "background": "#FFE3D7"});
						em++;

					}

				});

				if(em > 0) {

					const msg = Swal.mixin({
							toast: true,
							position: 'top-end',
							showConfirmButton: false,
							timer: 3000
						});

						msg.fire(
							'Заполните поля!',
							'',
							'warning'
						);

					return false;

				}

				return true;

			},
			success: function (data) {

				let task = $('#task').val();

				const msg = Swal.mixin({
							toast: true,
							position: 'top-end',
							showConfirmButton: false,
							timer: 3000
						});

				if(data !== 'Error'){

					msg.fire(
						data.result,
						'',
						'success'
					);

				}

				if (cat === 'test')
					$('.Questions').empty().load('modules/corpuniver/form.corpuniver.php?action=list.questions&id=' + task);

				closeInfo();

				configpage();

				$('.ifolder').load('modules/corpuniver/core.corpuniver.php?action=catlist');

			}
		});

	</script>

<?php

	exit();

}

// Вывод вопросов теста
if ($action == 'list.questions') {

	$idtask = $_REQUEST['idtask'];

	$questions = [];
	$author = 0;

	if ($idtask > 0){

		$author = CorpUniver::infoTask($idtask)['data']['author'];
		// вопросы задания
		$questions = CorpUniver::listQuestions($idtask)['data'];

	}

	$change = ($da['author'] == $iduser1 || $isadmin == 'on' || (in_array($iduser1, $mdcsettings['Editor']) && !in_array($iduser1, $mdcsettings['EditorMy']))) ? 'yes' : '';


	foreach ($questions as $num => $q) {
	?>
	<li id="Question" data-id="<?=$q['id']?>" data-num="<?=($num + 1)?>" data-t="Question">

		<div href="javascript:void(0)" id="editQuestion-<?=$q['id']?>" class="quest" style="margin-left: 4em">
			&nbsp;<?=$q['text']?>
			<?php
			if ($change == 'yes'){
			?>
			<span class="actions">

				<a href="javascript:void(0)" data-type="Question" class="up-quest gray yellow mpr0" title="Вверх">
					<i class="icon-up-big yellow"></i>
				</a>
				<a href="javascript:void(0)" data-type="Question" class="down-quest gray yellow" title="Вниз">
					<i class="icon-down-big yellow"></i>
				</a>
				<a href="javascript:void(0)" data-type="Quest" data-id="<?=$q['id']?>" data-task="<?=$idtask?>" title="Изменить" class="gray blue editQuest">
					<i class="icon-pencil blue"></i>
				</a>
				<a href="javascript:void(0)" data-type="Quest" data-id="<?=$q['id']?>" data-task="<?=$idtask?>" title="Удалить" class="gray red deleteQuest">
					<i class="icon-cancel-circled"></i>
				</a>

			</span>
			<?php } ?>
		</div>

	</li>
	<?php } ?>

	<div class="hidden" id="countQuest-<?=$lec['id']?>"><?=count($questions)?></div>

	<script>

		/**
		* Изменение порядка вывода блоков
		 */

		$(document).off('click',".down-quest");
		$(document).on('click',".down-quest", function () {

			var arr = [];
			var num = 0;
			var type = $(this).data('type');
			var count = $('li[data-t="Question"]').length;
			var pdiv = $(this).closest('li');

			if (pdiv.data('num') < count) {

				pdiv.next().fadeTo(1, 0.3).fadeTo(1500, 1);
				pdiv.fadeTo(1, 0.3).fadeTo(1500, 1);

				pdiv.insertAfter(pdiv.next());

				$('ul.Questions').find('li').each(function () {

					var id = $(this).data('id');

					//console.log(id);

					$(this).data('num', num);

					num++;

					arr.push(id);

				});

				orderChange(type, arr);

			}
			return false;

		});

		$(document).off('click',".up-quest");
		$(document).on('click',".up-quest",function () {

			var arr = [];
			var num = 0;
			var type = $(this).data('type');
			var pdiv = $(this).closest('li');

			if (pdiv.data('num') > 1) {

				pdiv.prev().fadeTo(1, 0.3).fadeTo(1500, 1);
				pdiv.fadeTo(1, 0.3).fadeTo(1500, 1);

				pdiv.insertBefore(pdiv.prev());

				$('ul.Questions').find('li').each(function () {

					$(this).data('num', num);
					num++;

					var id = $(this).data('id');
					arr.push(id);

				});

				orderChange(type, arr);

			}
			return false;

		});

		$('.editQuest').off().on('click',function(){

			var task = parseInt($(this).data('task'));
			var id = parseInt($(this).data('id'));

			$('.Questions').addClass('hidden');
			$('.qButton').addClass('hidden');

			$.post('modules/corpuniver/form.corpuniver.php?action=question&id=' + id + '&idtask=' + idtask, function (data) {

				$('#test').empty().append(data).removeClass('hidden');
				$('.type-question').remove();
				$('#typeTask').addClass('hidden');

			});

		});

		$('.deleteQuest')
			.off()
			.on('click',function(){

				var id = parseInt($(this).data('id'));
				var task = parseInt($(this).data('task'));

				$.getJSON('modules/corpuniver/core.corpuniver.php?action=delete&id=' + id + '&type=question', function (data) {

					const msg = Swal.mixin({
						toast: true,
						position: 'top-end',
						showConfirmButton: false,
						timer: 3000
					});

					if(data.result!=='Error'){

						msg.fire(
							'Результат: ',
							data.result,
							'success'
						);

					}
					else{

						msg.fire(
							'Ошибка: ',
							data.error.text,
							'warning'
						);

					}

				});

				$('.Questions').empty().load('modules/corpuniver/form.corpuniver.php?action=list.questions&idtask=' + idtask);

			});

	</script>

<?php
	exit();
}

// Добавление вопроса теста
if ($action == 'question'){

	$id = $_REQUEST['id'];
	$idtask = $_REQUEST['idtask'];

	$ans = [];

	if($id > 0){

		$question = CorpUniver ::infoQuestion($id);
		$ans = $question['answers'];

	}

	?>
	<FORM action="/modules/corpuniver/core.corpuniver.php" method="post" enctype="multipart/form-data" name="editQuest" id="editQuest">
		<INPUT type="hidden" name="action" id="action" value="edit.item">
		<INPUT type="hidden" name="id" id="id" value="<?=$id?>">
		<INPUT type="hidden" name="type" id="type" value="question">
		<INPUT type="hidden" name="idtask" id="idtask" value="<?=$idtask?>">

		<div class="attention">Укажите один из правильных ответов</div>

		<div class="gray2 mt10 pl5 uppercase Bold"><b>Текст вопроса</b></div>

		<div class="mt5 pl5">

			<textarea name="text" id="text" class="wp99 required" placeholder="Введите текст вопроса" style="height:200px"><?=$question['text']?></textarea>

		</div>

		<div class="gray2 pl5 mt10 uppercase Bold"><b>Варианты ответа:</b></div>
		<div class="m5" id="answers">

			<?php
			if(count($ans) <= 0){
				$ans[] = ["status" => 1,'',''];
			}
			foreach ($ans as $a){
				$check = ($a['status'] == 1) ? 'checked' : '';
			?>
				<div class="radio mt5 wp100">

					<div class="flex-container float">

						<div class="flex-string w40">
							<label style="display: unset">
								<input name="right" type="radio" id="right" value="<?=$a['text']?>" <?=$check?>>
								<span class="custom-radio mt10"><i class="icon-radio-check"></i></span>
							</label>
						</div>
						<div class="flex-string float relativ">

							<input name="answer[]" id="answer[]" type="text" class="wp100 required" placeholder="Введите ответ" value="<?=$a['text']?>">
							<a href="javascript:void(0)" title="Удалить" class="idel red delAnswer"><i class="icon-cancel-circled"></i></a>

						</div>

					</div>

				</div>

			<?php } ?>

		</div>
		<div class="text-center pt10">

			<a href="javascript:void(0)" title="Добавить вариант ответа" class="gray blue addAnswer">
				<i class="icon-plus"></i> Добавить вариант
			</a>

		</div>

		<div class="text-right mt20">

			<a href="javascript:void(0)" onclick="preSubmitQuest()" class="button greenbtn">Сохранить</a>
			<a href="javascript:void(0)" onclick="cancelBtnQuest()" class="button bcancel">Отмена</a>

		</div>

	</FORM>
	<script>

		$('#answers').find('div.radio:last').find('input[type="text"]').focus();

		$('#editQuest').ajaxForm({
			dataType:"json",
			beforeSubmit: function () {

				//var em = 0;
				let em = checkRequiredMod('.content-slide');

				if(em > 0) {

					Swal.fire({
						title: "Внимание!",
						text: "Заполните поля",
						type: "warning"
					});

					return false;

				}

				if( !$("input:radio").is(":checked") ){

					const msg = Swal.mixin({
							toast: true,
							position: 'center',
							showConfirmButton: false,
							background: "var(--red-dark)",
							timer: 3000
						});

					msg.fire(
						'<span class="white fs-11">Внимание!</span><br>',
						'<span class="white">Выберите правильный вариант ответа!</span>',
						'warning'
					);

					return false;

				}

				return true;

			},
			success: function (data) {

				const msg = Swal.mixin({
						toast: true,
						position: 'top-end',
						showConfirmButton: false,
						timer: 3000
					});

				if(data !== 'Error'){

					msg.fire(
						data.result,
						'',
						'success'
					);

				}

				//var task = $('#dialog').find('#task').val();

				$('#test').empty().addClass('hidden');

				$('.qButton').removeClass('hidden');
				$('#subwindow').find('.footer').removeClass('hidden');

				$('.Questions').empty().load('modules/corpuniver/form.corpuniver.php?action=list.questions&idtask='+idtask).removeClass('hidden');

				configpage();

			},
			error: function(request, status, error) {

				console.log(error);

			}
		});

		$('input').on('focusout', '.answer', function(){

			var a = $(this).val();
			$(this).siblings('label').find('#right').val(a);

		});

		$(document).on('click', '.delAnswer', function(){

			var count = $('#answers').find('.radio').length;

			if(count > 1){

				$(this).closest('div.radio').remove();

			}
			else{

				$(this).closest('div.radio').find('input[type="radio"]').prop('checked', false);
				$(this).closest('div.radio').find('input[type="text"]').val('');

			}

		});

		// делаем, чтобы вернуться к списку вопросов
		// при отмене, иначе получаем пустоту
		function cancelBtnQuest(){

			$('.qButton').removeClass('hidden');
			$('#subwindow').find('.footer').removeClass('hidden');

			$('.Questions').empty().load('modules/corpuniver/form.corpuniver.php?action=list.questions&idtask='+idtask).removeClass('hidden');

			$('#test').empty().addClass('hidden');

		}

		// промежуточная функция
		// нужна для передачи правильного варианта
		// иначе, при создании нового вопроса
		// это значение пустое
		function preSubmitQuest(){

			let $elmnt = $('#answers');
			let right;

			$elmnt.find('input[type="radio"]').each(function(){

				if( $(this).prop('checked') ){

					right = $(this).closest('div.radio').find('input[type="text"]').val();

					$(this).val(right);

				}

			});

			$('#editQuest').trigger('submit');

		}

	</script>
	<?php
}

// Окно редактирования категорий
if ($action == 'cat.list') {
	?>
	<div class="zagolovok">Редактор разделов</div>
	<div id="formtabs" style="max-height:70vh; overflow:auto" class="border--bottom">
	<?php
	$result = $db -> getAll("SELECT * FROM ".$sqlname."corpuniver_course_cat WHERE subid = '0' and identity = '$identity' ORDER BY title");
	foreach ($result as $datat) {

		$all = $db -> getOne("SELECT COUNT(*) FROM ".$sqlname."corpuniver_course WHERE cat = '".$datat['id']."' and identity = '$identity'");

		print '
			<div class="flex-container float p10 ha">
				<div class="flex-string float">
					<i class="icon-folder-open blue"></i>&nbsp;<B>'.$datat['title'].'</B>&nbsp;[ <b class="green" title="Число записей">'.$all.'</b> ]
				</div>
				<div class="flex-string w50">
					<A href="javascript:void(0)" onClick="editCourse(\''.$datat['id'].'\',\'cat.edit\')"><i class="icon-pencil green" title="Редактировать"></i></A>
				</div>
				<div class="flex-string w50">
					<A href="javascript:void(0)" onClick="cf=confirm(\'Вы действительно хотите удалить запись?\');if (cf)editCourse(\''.$datat['id'].'\',\'cat.delete\')"><i class="icon-cancel-circled red" title="Удалить"></i></A>
				</div>
			</div>';

		$res = $db -> getAll("SELECT * FROM ".$sqlname."corpuniver_course_cat WHERE subid = '".$datat['id']."' and identity = '$identity' ORDER BY title");
		foreach ($res as $data) {

			$all = $db -> getOne("SELECT COUNT(*) FROM ".$sqlname."corpuniver_course WHERE cat='".$data['id']."' and identity = '$identity'");

			print '
				<div class="flex-container float p10 ha bgwhite">
					<div class="flex-string w20"></div>
					<div class="flex-string float">
						<i class="icon-folder gray2"></i>&nbsp;<B>'.$data['title'].'</B>&nbsp;[ <b class="blue" title="Число записей">'.$all.'</b> ]
					</div>
					<div class="flex-string w50">
						<A href="javascript:void(0)" onClick="editCourse(\''.$data['id'].'\',\'cat.edit\')"><i class="icon-pencil green" title="Редактировать"></i></A>
					</div>
					<div class="flex-string w50">
						<A href="javascript:void(0)" onClick="cf=confirm(\'Вы действительно хотите удалить запись?\');if (cf)editCourse(\''.$data['id'].'\',\'cat.delete\')"><i class="icon-cancel-circled red" title="Удалить"></i></A>
					</div>
				</div>';

		}

	}

	?>
	<div class="button--pane text-right">

		<A href="javascript:void(0)" onclick="editCourse('','cat.edit')" class="button">Добавить</A>&nbsp;
		<A href="javascript:void(0)" onclick="DClose()" class="button">Отмена</A>

	</div>
	<?php
	exit();

}

// Изменение категории
if ($action == "cat.edit") {

	$idcat = $_REQUEST['id'];

	$result = $db -> getRow("SELECT * FROM ".$sqlname."corpuniver_course_cat where id='".$idcat."' and identity = '$identity'");
	$title  = $result["title"];
	$idcat  = $result["id"];
	$subid  = $result["subid"];
	?>
	<div class="zagolovok">Добавить/Изменить раздел</div>
	<FORM action="/modules/corpuniver/core.corpuniver.php" method="post" enctype="multipart/form-data" name="form" id="form">
		<INPUT type="hidden" name="action" id="action" value="cat.edit">
		<INPUT type="hidden" name="id" id="id" value="<?= $idcat ?>">

		<div class="flex-container float p10 ha">

			<div class="flex-string title w140">
				Новое название:
			</div>
			<div class="flex-string float pl10">
				<INPUT name="title" type="text" class="wp100" id="title" value="<?= $title ?>" placeholder="Введите название">
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
					$result = $db -> getAll("SELECT * FROM ".$sqlname."corpuniver_course_cat WHERE subid = '0' and id != '$idcat' and identity = '$identity' ORDER BY title");
					foreach ($result as $data) {

						print '<OPTION value="'.$data['id'].'" '.($data['id'] == $subid ? "selected" : "").'>'.$data['title'].'</OPTION>';

					}
					?>
				</select>
			</div>

		</div>

		<hr>

		<div class="button--pane text-right">

			<A href="javascript:void(0)" onclick="$('#form').trigger('submit')" class="button">Сохранить</A>&nbsp;
			<A href="javascript:void(0)" onclick="editCourse('','cat.list');" class="button">Отмена</A>

		</div>
	</FORM>

	<script>
		$('#form').ajaxForm({
			beforeSubmit: function () {

				var em = checkRequired();

				if (em === false) return false;

				$('#dialog').css('display', 'none');
				$('#dialog_container').css('display', 'none');

				return true;

			},
			success: function (data) {

				const msg = Swal.mixin({
					toast: true,
					position: 'top-end',
					showConfirmButton: false,
					timer: 3000
				});

					msg.fire(
						data,
						'',
						'success'
					);

				doLoad('modules/corpuniver/form.corpuniver.php?action=cat.list');

				$('.ifolder').load('modules/corpuniver/core.corpuniver.php?action=catlist');

			}
		});

	</script>
	<?php
}