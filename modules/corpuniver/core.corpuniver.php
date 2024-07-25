<?php
/* ============================ */
/*         SalesMan CRM         */
/* ============================ */
/*  (C) 2019 Ivan Drachyov      */
/*       SalesMan Project       */
/*        www.isaler.ru         */
/*        ver. 2019.x           */

/* ============================ */

use andreskrey\Readability\Configuration;
use andreskrey\Readability\Readability;
use Salesman\CorpUniver;
use Salesman\Document;
use Salesman\Upload;

error_reporting( E_ERROR );

header( "Pragma: no-cache" );

$rootpath = dirname(__DIR__, 2);

include $rootpath."/inc/config.php";
include $rootpath."/inc/dbconnector.php";
include $rootpath."/inc/auth.php";
include $rootpath."/inc/func.php";
include $rootpath."/inc/settings.php";
include $rootpath."/inc/language/".$language.".php";

$thisfile = basename( __FILE__ );

$action = $_REQUEST['action'];

$mdcset      = $db -> getRow( "SELECT * FROM ".$sqlname."modules WHERE mpath = 'corpuniver' and identity = '$identity'" );
$mdcsettings = json_decode( $mdcset['content'], true );

// Изменение курса/лекции/материала/задания
if ( $action == 'edit.item' ) {
   // echo 'asda';



	$type    = $_REQUEST['type'];
	$id      = (int)$_REQUEST['id'];
	$name    = $_REQUEST['name'];
	$lec     = $_REQUEST['lec'];
	$course  = $_REQUEST['course'];
	$source  = $_REQUEST['source'];
	$content = htmlspecialchars( $_REQUEST['content'] );

	$print = '';
	$fid = [];

	//загружаем файлы
	$upload = Upload ::upload();


	//print_r($upload);

	foreach ( $upload['data'] as $file ) {

		$ext     = getExtention( $file['name'] );
		$oldfile = $file['name'];

		// конвертируем в PDF
		if ( in_array( $ext, [
			"docx",
			"doc",
			"rtf",
			"pptx",
			"ppt"
		] ) ) {

			$rez    = new Document();
			$data   = $rez -> doc2PDF( 0, [
				"file" => $file['name']
			] );
			$rezult = $rez -> rezult;

			//print_r($rezult);

			if ( file_exists( $rootpath."/files/$fpath".$data ) ) {

				// подменяем значения на новые
				$file['title'] = str_replace( $ext, "pdf", $file['name'] );
				$file['name']  = $data;
				$file['size']  = filesize( $rootpath."/files/$fpath".$data );
				$file['type']  = get_mimetype( $data );

				unlink( $rootpath."/files/$fpath".$oldfile );

			}

		}

		$arg = [
			'ftitle'   => $file['title'],
			'fname'    => $file['name'],
			'ftype'    => $file['type'],
			'fver'     => '1',
			'iduser'   => $iduser1,
			'folder'   => $folder,
			"size"     => $file['size'],
			"datum"    => current_datumtime(),
			'identity' => $identity
		];

		$fid[] = Upload ::edit( 0, $arg );

	}

    if($_REQUEST['localFiles']){
        $fid[] = $_REQUEST['localFiles'][0];
    }

    //var_dump($fid);

	$fileo = ($_REQUEST['fid_old'] != '') ? yexplode( ",", $_REQUEST['fid_old'] ) : [];

	$fidn = (!empty( $fid )) ? array_merge_recursive( $fileo, $fid ) : $fileo;

    //var_dump($fidn);

	$fidItem = yimplode( ",", $fidn );

	// Добавление/редактирование курса
	if ( $type == 'Course' ) {

		$catId = '';

		$params['name'] = $name;
		$params['cat']  = (int)$_REQUEST['cat'];
		$params['des']  = $content;
		$params['fid']  = $fidItem;

		if ( $_REQUEST['catNew'] != '' ) {

			$db -> query( "INSERT INTO ".$sqlname."corpuniver_course_cat SET title='".$_REQUEST['catNew']."', identity='$identity'" );

			$params['cat'] = $db -> insertId();

		}

		$response = CorpUniver ::edit( $id, $params );

	}

	// Добавление/редактирование лекции
	elseif ( $type == 'Lecture' ) {

		$response = CorpUniver ::editLecture( $id, $name, $course );

	}

	// Добавление/редактирование материала
	elseif ( $type == 'Material' ) {

		$params['name']     = trim( $name );
		$params['lecture']  = $lec;
		$params['text']     = trim( $content );
		$params['fid']      = $fidItem;
		$params['source']   = $source;
		$params['type']     = $_REQUEST['tip'];
		$params['identity'] = $identity;

        //var_dump($params);
        //var_dump($_REQUEST['tip']);

        if($_REQUEST['tip'] === 'localfile'){
            $params['type'] = 'file';
        }
        //var_dump($id);
        //exit;


		$response = CorpUniver ::editMaterial( $id, $params );

	}

	// Добавление/редактирование задания
	elseif ( $type == 'Task' ) {

		$cat    = $_REQUEST['cat'];
		$idtask = $_REQUEST['idtask'];

		$params['name']    = $name;
		$params['lecture'] = $lec;
		$params['type']    = $cat;
		//$params['fid']	  = $fidItem;
		$params['identity'] = $identity;

		$Task = CorpUniver ::editTask( $idtask, $params );

		//print_r($Task);

		if ( $cat == 'question' ) {

			if ( $idtask > 0 ) {

				$qid = CorpUniver ::infoQuestion( 0, $idtask )['id'];

				$db -> query( "UPDATE ".$sqlname."corpuniver_answers SET text = '".$_REQUEST['answer']."' WHERE question = '$qid' and identity = '$identity'" );
				$db -> query( "UPDATE ".$sqlname."corpuniver_questions SET text = '".$_REQUEST['question']."' WHERE id = '$qid' and identity = '$identity'" );

			}
			else {

				$db -> query( "INSERT INTO ".$sqlname."corpuniver_questions SET ?u", [
					"task"     => $Task['data'],
					"text"     => $_REQUEST['question'],
					"identity" => $identity
				] );
				$qid = $db -> insertId();

				$db -> query( "INSERT INTO ".$sqlname."corpuniver_answers SET ?u", [
					"question" => $qid,
					"text"     => $_REQUEST['answer'],
					"status"   => '1',
					"identity" => $identity
				] );

			}

		}

		$response = $Task;

	}

	// Добавление/редактирование вопроса и ответов
	elseif ( $type == 'question' ) {

		$idtask = $_REQUEST['idtask'];

		$params['text']    = untag( $_REQUEST['text'] );
		$params['answers'] = $_REQUEST['answer'];
		$params['right']   = $_REQUEST['right'];
		$params['idtask']  = $idtask;

		$response = CorpUniver ::editQuestion( $id, $params );

		$response['idtask'] = $idtask;

	}

	print json_encode_cyr( $response );

	exit();

}

// удаление лекции/материала/задания
if ( $action == 'delete' ) {

	$id   = (int)$_REQUEST['id'];
	$type = (int)$_REQUEST['type'];

	if ( $id > 0 ) {

		if ( $type == 'course' ) {

			$response = CorpUniver ::delete( $id );

		}
		elseif ( $type == 'lecture' ) {

			$response = CorpUniver ::deleteLecture( $id );

		}
		elseif ( $type == 'material' ) {

			$response = CorpUniver ::deleteMaterial( $id );

		}
		elseif ( $type == 'Task' ) {

			$response = CorpUniver ::deleteTask( $id );

		}
		elseif ( $type == 'question' ) {

			$response = CorpUniver ::deleteQuestion( $id );

		}

	}
	else {

		$response['result']        = 'Error';
		$response['error']['code'] = '405';
		$response['error']['text'] = "Отсутствуют параметры - id";

	}

	print json_encode_cyr( $response );

}

// Проверка результатов решения задания
if ( $action == 'verification.task' ) {

	$id   = $_REQUEST['id'];
	$type = $_REQUEST['type'];

	if ( $id > 0 ) {

		// Тип - вопрос
		if ( $type == 'question' ) {

			$question = $_REQUEST['question'];
			$answer   = $_REQUEST['answer'];

			// Берем правильный ответ из БД
			$ans = $db -> getOne( "SELECT text FROM ".$sqlname."corpuniver_answers WHERE question = '$question' AND status = '1' AND identity = '$identity'" );

			if ( $ans == $answer ) {

				$response['result']['text']    = 'Правильный ответ!';
				$response['result']['percent'] = 100;

			}
			else {

				$response['result']['text']    = 'Ответ неверный!';
				$response['result']['percent'] = 0;

			}

			// записываем ответ
			$arg = [
				"type"   => "quest",
				"parent" => $question,
				"answer" => $answer
			];
			CorpUniver ::addAnswer( $arg );


		}
		// Тест
		else {

			$questions = $_REQUEST['question'];

			$corr = 0;
			$all  = count( $questions );

			foreach ( $questions as $q ) {

				$ansUser = $_REQUEST[ 'answer-'.$q ];

				$ans = $db -> getOne( "SELECT text FROM ".$sqlname."corpuniver_answers WHERE question = '$q' AND status = '1' AND identity = '$identity'" );

				// записываем ответ
				$arg = [
					"type"   => "test",
					"parent" => $q,
					"answer" => $ansUser
				];
				CorpUniver ::addAnswer( $arg );

				if ( $ans == $ansUser )
					++$corr;

			}

			$response['result']['text']    = 'Правильных ответов: '.$corr.' из '.$all;
			$response['result']['percent'] = $corr / $all * 100;

		}

	}
	else {

		$response['result']        = 'Error';
		$response['error']['code'] = '405';
		$response['error']['text'] = "Отсутствуют параметры - id задания";

	}

	print json_encode_cyr( $response );

}

// смена порядка вывода
if ( $action == 'order.edit' ) {

	$type = $_REQUEST['type'];
	$arr  = yexplode( ',', $_REQUEST['arr'] );

	if ( $type == 'Lecture' ) {

		// Изменяем порядок для лекций
		foreach ( $arr as $i => $num ) {

			$db -> query( "UPDATE ".$sqlname."corpuniver_lecture SET ord = '$i' WHERE id = '$num' and identity = '$identity'" );

		}

	}
	elseif ( $type == 'Material' ) {

		// Изменяем порядок для материалов
		foreach ( $arr as $i => $num ) {

			$db -> query( "UPDATE ".$sqlname."corpuniver_material SET ord = '$i' WHERE id = '$num' and identity = '$identity'" );

		}

	}
	elseif ( $type == 'Task' ) {

		// Изменяем порядок для заданий
		foreach ( $arr as $i => $num ) {

			$db -> query( "UPDATE ".$sqlname."corpuniver_task SET ord = '$i' WHERE id = '$num' and identity = '$identity'" );

		}

	}
	elseif ( $type == 'Question' ) {

		// Изменяем порядок для вопросов
		foreach ( $arr as $i => $num ) {

			$db -> query( "UPDATE ".$sqlname."corpuniver_questions SET ord = '$i' WHERE id = '$num' and identity = '$identity'" );

		}

	}

	print 'Обновлено';

}

// вывод прикрепленных файлов
if ( $action == 'files' ) {

	$type = $_REQUEST['type'];
	$id   = $_REQUEST['id'];
	$view = $_REQUEST['view'];

	$change = '';

	if ( $type == 'Course' ) {

		$cdata  = CorpUniver ::info( $id )['data'];
		$fidd   = $cdata['fid'];
		$change = ($cdata['author'] == $iduser1 || $isadmin == 'on' || (in_array( $iduser1, $mdcsettings['Editor'] ) && !in_array( $iduser1, $mdcsettings['EditorMy'] ))) ? 'yes' : '';

	}
	elseif ( $type == 'Material' )
		$fidd = CorpUniver ::infoMaterial( $id )['data']['fid'];

	$fids = yexplode( ",", $fidd );

	if ( !empty( $fids ) ) {

		if ( $view == 'yes' )
			print '<DIV class="zagolovok">Курс: Прикрепленные файлы</DIV>';

		foreach ( $fids as $fid ) {

			$result2 = $db -> getRow( "select * from ".$sqlname."file WHERE fid = '$fid' and identity = '$identity'" );
			$ftitle  = $result2["ftitle"];
			$fname   = $result2["fname"];

			$result2['size'] = filesize( "../../files/".$fpath.$result2['fname'] ) / 1000;

			if ( $type == 'Course' ) {

				if ( $result2['size'] == 0 ) {

					$fd = 'nofind';
					$fh = 'disabled';

				}

				print '
				<div class="ha flex-container bgwhite box--child p10 mb5 box-shadow focused">
				
					<div class="flex-string wp70">
					
						<div class="fs-12">
					
							<A href="javascript:void(0)" onClick="editUpload(\''.$fid.'\',\'info\');"><span class="ellipsis '.$fd.'">'.$result2['icon'].'&nbsp;<b>'.$ftitle.'</b></span></A>
							
						</div>
						
						<div class="fs-09 mt10">
						
							<span class="gray">&nbsp;<i class="icon-clock"></i><b>'.datetimeru2datetime( $result2['datum'] ).'</b></span>
							<span class="gray">&nbsp;<B>'.num_format( $result2['size'] ).'</B>&nbsp;kb&nbsp;</span>
							
						</div>
						
					</div>
					<div class="flex-string wp30 nowrap text-right">
					
						<A href="javascript:void(0)" onClick="fileDownload(\''.$fid.'\',\'\',\'\')"><i class="icon-eye broun" title="Просмотр"></i></A>&nbsp;
						<A href="javascript:void(0)" onClick="fileDownload(\''.$fid.'\',\'\',\'yes\')"><i class="icon-download blue" title="Скачать"></i></A>&nbsp;'.($change == 'yes' ? '
						<A href="javascript:void(0)" onClick="editUpload(\''.$fid.'\',\'edit\');"><i class="icon-pencil green" title="Изменить"></i></A>&nbsp;
						<A href="javascript:void(0)" onClick="delFile(\''.$id.'\', \''.$fid.'\', \''.$type.'\')" title="Удалить"><i class="icon-cancel-circled red"></i></A>' : '').'
						
					</div>
				</div>';

			}
			else {

				print '
				<div class="viewdiv flex-string">
					'.get_icon2( $ftitle ).'&nbsp;'.$ftitle.'&nbsp;<A href="javascript:void(0)" onClick="delFile(\''.$id.'\', \''.$fid.'\', \''.$type.'\')" title="Удалить"><i class="icon-cancel-circled red"></i></A>&nbsp;
				</div>';

			}

		}

		print '<input name="fid_old" id="fid_old" type="hidden" value="'.yimplode( ",", $fids ).'">';

	}

	exit();

}

if ( $action == "deleteFile" ) {

	$fid  = $_REQUEST['fid'];
	$type = $_REQUEST['type'];
	$id   = $_REQUEST['id'];

	$fid_old = '';

	$fname = $db -> getOne( "SELECT fname FROM ".$sqlname."file WHERE fid='".$fid."' AND identity = '$identity'" );

	@unlink( $rootpath."/files/".$fpath.$fname );

	//удалим запись о файле
	$db -> query( "DELETE FROM ".$sqlname."file WHERE fid = '".$fid."' AND identity = '$identity'" );

	//составим массив файлов в записи
	if ( $type == 'Course' )
		$fid_old = CorpUniver ::info( $id )['data']['fid'];

	if ( $type == 'Material' )
		$fid_old = CorpUniver ::infoMaterial( $id )['data']['fid'];

	//если есть файлы, то преобразуем в массив
	if ( $fid_old != '' )
		$fidd = yexplode( ",", $fid_old );

	//если файлов нет, то создадим пустой
	else $fidd = [];

	$fidd2 = [];

	// Формируем новый список файлов
	foreach ( $fidd as $key => $item ) {

		if ( $item != $fid )
			$fidd2[] = $item;

	}

	$fid_new = yimplode( ",", $fidd2 );

	if ( $type == 'Course' )
		$db -> query( "UPDATE ".$sqlname."corpuniver_course SET fid = '".$fid_new."' WHERE id = '".$id."' AND identity = '$identity'" );

	if ( $type == 'Material' )
		$db -> query( "UPDATE ".$sqlname."corpuniver_material SET fid = '".$fid_new."' WHERE id = '".$id."' AND identity = '$identity'" );

	print "<script>$('#filelist').load('modules/corpuniver/core.corpuniver.php?action=files&type=$type&id=$id').append('<img src=\"/assets/images/loading.svg\">');</script>";

}

// Сторонний ресурс
if ( $action == 'resource' ) {

	$url = $_REQUEST['url'];

	$img = '';
	$xssBlock = false;

	$youtube = [
		'youtube',
		'youtu.be'
	];

	$vk = [
		'vk.com',
		'vk.cc'
	];

	if ( arrayFindInSet( $url, $youtube ) ) {

		if ( arrayFindInSet( $url, ['youtube'] ) ) {

			$id = yexplode( 'watch?v=', $url, 1 );

		}
		else {

			$id = yexplode( 'youtu.be/', $url, 1 );

		}

		$img = '//img.youtube.com/vi/'.$id.'/maxresdefault.jpg';
		$dom = $html = file_get_contents( $url );
		$dom = new DomDocument();
		$dom -> loadHTML( '<?xml version="1.0" encoding="UTF-8"?>'.$html );
		$title = $dom -> getElementById( 'eow-title' );
		$name  = $title -> nodeValue;

		$res = ($name != '') ? 'Видео найдено' : 'Успешно';

		$url = 'https://www.youtube.com/embed/'.$id;

		$type = 'video';

	}
	elseif ( strpos( $url, 'vimeo.com' ) ) {

		$id = yexplode( 'vimeo.com/', $url )[1];

		$page_content = file_get_contents( $url );
		preg_match_all( "|<title>(.*)</title>|sUSi", $page_content, $titles );
		$name = $titles[1][0];

		$curl = curl_init( $url );
		curl_setopt( $curl, CURLOPT_USERAGENT, 'IE20' );
		curl_setopt( $curl, CURLOPT_HEADER, 0 );
		curl_setopt( $curl, CURLOPT_RETURNTRANSFER, 1 );
		curl_setopt( $curl, CURLOPT_SSL_VERIFYPEER, 0 );
		$out = curl_exec( $curl );
		curl_close( $curl );

		preg_match_all( '#meta name="twitter:image" content="(.*?)"#is', $out, $matches_image );
		$img = $matches_image[1][0];

		$res = ($name != '') ? 'Успешно' : 'Видео не найдено';

		$url = 'https://player.vimeo.com/video/'.$id.'?color=ffffff';

		$type = 'video';

	}
	/*elseif (arrayFindInSet($url, $vk)) {

		$id = yexplode('video-', $url)[1];

		$oid = yexplode('_', $id)[0];
		$id  = yexplode('_', $id)[1];

		$page_content = file_get_contents($url);
		preg_match_all("|<title>(.*)</title>|sUSi", $page_content, $titles);
		$name = $titles[1][0];

		$res = ($name != '') ? 'Видео найдено ' : 'Видео не найдено';

		$url = "//vk.com/video_ext.php?oid=-".$oid."&id=".$id;
	}*/
	else {

		/*
		$page_content = file_get_contents( $url );
		preg_match_all( "|<title>(.*)</title>|sUSi", $page_content, $titles );
		$name = $titles[1][0];
		*/

		// заменяем ссылки на изображения на нужный адрес
		// в данном случае на корень сайта
		$site    = parse_url( $url );
		$newhost = $site['scheme']."://".$site['host'];

		$headers = get_headers( $url, 1 );
		$html    = file_get_contents( $url );

		if(strtolower($headers['X-Frame-Options']) == "sameorigin")
			$xssBlock = true;

		//print_r($headers);

		$configuration = new Configuration( [
			// фиксирует относительные ссылки типа /img.png
			"FixRelativeURLs" => true,
			// на что менять
			"OriginalURL"     => $newhost
		] );

		$readability = new Readability( $configuration );

		try {

			$readability -> parse( $html );

			$name   = $readability -> getTitle();
			$images = $readability -> getImages();

			if ( !empty( $images ) )
				$img = $images[0];

		}
		catch ( andreskrey\Readability\ParseException $e ) {

			$error = sprintf( 'Error processing text: %s', $e -> getMessage() );

		}

		if ( $name != '' ) {

			$res  = 'Найдена страница';
			$type = 'link';

		}
		else {

			$res  = 'Error';
			$name = 'Страница не найдена или некорректна';

		}

	}

	if ( $img == '' ) {

		// Получение скриншота страницы сайта
		if ( filter_var( $url, FILTER_VALIDATE_URL ) ) {

			// вызов Google PageSpeed Insights API
			$api_data = file_get_contents( "https://www.googleapis.com/pagespeedonline/v2/runPagespeed?url=$url&screenshot=true" );

			// расшифровка json данных
			$api_data = json_decode( $api_data, true );

			// данные снимка
			$screenshot = $api_data['screenshot']['data'];
			$screenshot = str_replace( [
				'_',
				'-'
			], [
				'/',
				'+'
			], $screenshot );

			// отобразить изображение
			$img = "<img src=\"data:image/jpeg;base64,".$screenshot."\" />";

		}

	}

	$response = [
		'title'   => $name,
		'preview' => $img,
		'result'  => $res,
		'type'    => $type,
		'src'     => $url,
		'error'   => $error,
		'xssBlock' => $xssBlock
	];

	print json_encode_cyr( $response );

}

// Загрузка содержимого по URL
if ( $action == 'parceURL' ) {

	$url = $_REQUEST['url'];

	// заменяем ссылки на изображения на нужный адрес
	// в данном случае на корень сайта
	$site    = parse_url( $url );
	$newhost = $site['scheme']."://".$site['host'];

	$rezult = [];

	$configuration = new Configuration( [
		// фиксирует относительные ссылки типа /img.png
		"FixRelativeURLs" => true,
		// на что менять
		"OriginalURL"     => $newhost
	] );

	$readability = new Readability( $configuration );

	$html = file_get_contents( $url );

	try {

		$readability -> parse( $html );

		$rezult['title']   = $readability -> getTitle();
		$rezult['content'] = $readability -> getContent();
		$rezult['image']   = $readability -> getImage();
		$rezult['images']  = $readability -> getImages();

	}
	catch ( andreskrey\Readability\ParseException $e ) {

		$rezult['error'] = sprintf( 'Error processing text: %s', $e -> getMessage() );

	}

	print json_encode_cyr( $rezult );

}

// Добавление/изменение раздела
if ( $action == "cat.edit" ) {

	$params = [
		"subid" => $_REQUEST['subid'],
		"title" => $_REQUEST['title']
	];

	$category = CorpUniver ::editCategory( $_REQUEST['id'], $params );

	$print = ($category['result'] !== 'Error') ? $category['result'] : $category['error']['text'];

	print $print;

	exit();

}

// Удаление категории
if ( $action == "cat.delete" ) {

	$category = CorpUniver ::deleteCategory( $_REQUEST['id'] );

	$print = ($category['result'] !== 'Error') ? $category['result'] : $category['error']['text'];

	print $print;

	exit();

}

// Вывод списка разделов
if ( $action == "catlist" ) {

	$idcat = $_REQUEST['id'];

	$ss = ($idcat == '') ? 'fol_it' : 'fol';

	print '<a href="javascript:void(0)" data-id="" data-title="" class="'.$ss.'"><i class="icon-folder blue"></i>&nbsp;[все]</a>';

	$result = $db -> getAll( "SELECT * FROM ".$sqlname."corpuniver_course_cat WHERE subid = '0' and identity = '$identity' ORDER by title" );
	foreach ( $result as $data ) {

		$ss = ($data['id'] == $idcat) ? 'fol_it' : 'fol';

		print '<a href="javascript:void(0)" class="'.$ss.' mt5" data-id="'.$data['id'].'" data-title="'.$data['title'].'"><span class="ellipsis"><i class="icon-folder blue"></i>&nbsp;'.$data['title'].'</span></a>';

		$res = $db -> getAll( "SELECT * FROM ".$sqlname."corpuniver_course_cat WHERE subid = '".$data['id']."' and identity = $identity ORDER by title" );
		foreach ( $res as $da ) {

			$ss = ($da['id'] == $idcat) ? 'fol_it' : 'fol';

			print '<a href="javascript:void(0)" class="fol mt5" data-id="'.$da['id'].'" data-title="'.$da['title'].'"><span class="ellipsis pl20"><div class="strelka w5 mr10"></div><i class="icon-folder gray2"></i>&nbsp;'.$da['title'].'</span></a>';

		}

	}

	exit();

}

if ( $action == "changeMaterialStatus" ) {

	$id        = $_REQUEST['idmaterial'];
	$idlecture = $_REQUEST['idlecture'];
	$idtask    = $_REQUEST['idtask'];
	$idcourse  = $_REQUEST['idcourse'];

	$haveTask = false;

	if ( !$idtask ) {

		$material = CorpUniver ::infoMaterial( $id );

		$r = CorpUniver ::startWayCource( [
			"idcourse"   => $material['data']['course'],
			"idlecture"  => $material['data']['lecture'],
			"idmaterial" => $material['data']['id'],
			"end"        => true
		] );

		// если есть следующий материал, то активируем его
		if ( $material['data']['next'] > 0 )
			CorpUniver ::startWayCource( [
				"idcourse"   => $material['data']['course'],
				"idlecture"  => $material['data']['lecture'],
				"idmaterial" => $material['data']['next'],
				"start"      => true
			] );

		// в противном случае начинаем задачи
		elseif ( !$material['data']['next'] ) {

			$task = $db -> getRow( "
			SELECT 
				*
			FROM {$sqlname}corpuniver_task
			WHERE 
				{$sqlname}corpuniver_task.lecture = '$idlecture'
			LIMIT 1
		" );

			// если задачи найдены, то начинаем их
			if ( $task['id'] > 0 ) {

				CorpUniver ::startWayCource( [
					"idcourse"  => $material['data']['course'],
					"idlecture" => $idlecture,
					"idtask"    => $task['id'],
					"start"     => true
				] );

				$haveTask = true;

			}

		}

		// или заканчиваем лекцию и начинаем новую
		if ( !$material['data']['next'] && !$haveTask ) {

			CorpUniver ::startWayCource( [
				"idcourse"  => $material['data']['course'],
				"idlecture" => $material['data']['lecture'],
				"end"       => true
			] );

			$lectures = $db -> getCol( "SELECT id FROM {$sqlname}corpuniver_lecture WHERE course = '$material[data][course]' ORDER BY ord" );

			$nextLecture = arrayNext( $material['data']['lecture'], $lectures );

			if ( !$nextLecture )
				CorpUniver ::startWayCource( [
					"idcourse"  => $material['data']['course'],
					"idlecture" => $nextLecture,
					"start"     => true
				] );

		}

	}
	elseif( !$idcourse ) {

		$task = CorpUniver ::infoTask( $idtask );

		$r = CorpUniver ::startWayCource( [
			"idcourse"  => $task['data']['course'],
			"idlecture" => $task['data']['lecture'],
			"idtask"    => $idtask,
			"end"       => true
		] );

		// если есть следующий материал, то активируем его
		if ( $task['data']['next'] > 0 )
			CorpUniver ::startWayCource( [
				"idcourse"  => $task['data']['course'],
				"idlecture" => $task['data']['lecture'],
				"idtask"    => $task['data']['next'],
				"start"     => true
			] );

		else
			CorpUniver ::startWayCource( [
				"idcourse"  => $task['data']['course'],
				"idlecture" => $task['data']['lecture'],
				"end"       => true
			] );

		$lectures = $db -> getCol( "SELECT id FROM {$sqlname}corpuniver_lecture WHERE course = '$task[data][course]' ORDER BY ord" );

		$nextLecture = arrayNext( $task['data']['lecture'], $lectures );

		if ( !$nextLecture )
			CorpUniver ::startWayCource( [
				"idcourse"  => $task['data']['course'],
				"idlecture" => $nextLecture,
				"start"     => true
			] );

	}
	elseif( $idcourse ) {

		$r = CorpUniver ::startWayCource( [
			"idcourse"  => $idcourse,
			"end"       => true
		] );

	}

	if ( $r )
		$rez['data'] = true;
	else
		$rez['error'] = true;

	$rez['info'] = $material;

	print json_encode_cyr( $rez );

}