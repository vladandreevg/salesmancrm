<?php
/* ============================ */
/*         SalesMan CRM         */
/* ============================ */
/* (C) 2019 Vladislav Andreev   */
/*       SalesMan Project       */
/*         salesman.pro         */
/*          ver. 2019.х         */
/* ============================ */
/*   Developer: Ivan Drachyov   */

use Salesman\CorpUniver;
use Salesman\Elements;
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

$mdcset      = $db -> getRow( "SELECT * FROM ".$sqlname."modules WHERE mpath = 'corpuniver' and identity = '$identity'" );
$mdcsettings = json_decode( $mdcset[ 'content' ], true );

$icoNMaterial = [
	"video"    => "icon-video",
	"audio"    => "icon-volume-up",
	"image"    => "icon-picture",
	"resource" => "icon-link-1",
	"text"     => "icon-doc-text",
	"file"     => "icon-attach-1"
];

$action = $_REQUEST[ 'action' ];

// Информация о курсе. Правая панель
if ( $action == "courceView" ) {

	$id = $_REQUEST[ 'id' ];

	$categories = '';

	$Course = CorpUniver ::info( $id )[ 'data' ];

	$idcat = $Course[ "cat" ];
	$cat   = $db -> getOne( "SELECT title FROM ".$sqlname."corpuniver_course_cat WHERE id = '$idcat' AND identity = '$identity'" );

	$files = ( $Course[ 'fid' ] != '' ) ? '<div id="filelist"></div>
		<div class="fs-09 gray ml5 em">При прохождении курса слушатели могут просматривать и скачивать файлы</div>' : '<div class="bad">Файлы отсутствуют</div>';


	$last_change = '<i class="icon-calendar-inv blue"></i> '.datetimeru2datetime( $Course[ 'date_edit' ] ).'<span class="noBold">, '.current_user( $Course[ 'editor' ] ).'</span>';

	// Признак начала курса
	$way = CorpUniver ::infoWayCource( ["idcourse" => $id] );

	// Прогресс выполнения
	$progress = CorpUniver ::progressCource( $id );

	print '
	<div class="body" style="height:calc(100vh - 60px)">

		<div class="m0 p0 pb20 mt10 blue fs-14 Bold">
			<span class="gray">Курс: </span><span class="blue fs-12 uppercase">'.$Course[ 'name' ].'</span>
		</div>
			
		<div class="bgwhite fcontainer flex-vertical p0 border--bottom box--child fs-10">
		
			<div class="flex-container p10">
	
				<div class="flex-string wp100 uppercase fs-09 Bold gray2 mb10">Дата создания</div>
				<div class="flex-string wp100 relativ blue">
					<i class="icon-calendar-1 green"></i>
					'.format_date_rus( $Course[ 'date_create' ] ).'
				</div>
	
			</div>
			<div class="flex-container p10">
	
				<div class="flex-string wp100 uppercase fs-09 Bold gray2">Последнее изенение</div>
				<div class="flex-string wp100 mt10">
					'.$last_change.'
				</div>
	
			</div>
			<div class="flex-container p10">
	
				<div class="flex-string wp100 uppercase fs-09 Bold gray2 mb10">Автор</div>
				<div class="flex-string wp100 relativ">
					<i class="icon-user-1 blue"></i>
					'.current_user( $Course[ 'author' ] ).'
				</div>
	
			</div>
			<div class="flex-container p10">
	
				<div class="flex-string wp100 uppercase fs-09 Bold gray2 mb10">Категория</div>
				<div class="flex-string wp100 blue">
					<i class="icon-folder-open orange"></i>'.$cat.'</div>
					
			</div>
			<div class="flex-container p10">
	
				<div class="flex-string wp100 uppercase fs-09 Bold gray2">Описание</div>
				<div class="flex-string wp100 mt10 noBold text-wrap">
					'.$Course[ 'des' ].'
				</div>
	
			</div>
			
		</div>
		
		<div class="divider mt10 mb10">Прохождение</div>
		
		<div class="bgwhite fcontainer flex-vertical p0 border--bottom box--child fs-10">
		
			<div class="flex-container p10">
	
				<div class="flex-string wp100 uppercase fs-09 Bold gray2 mb10">Начало прохождения</div>
				<div class="flex-string wp100">
					'.( $way[ 'isStart' ] ? '<i class="icon-calendar-1 green"></i> '.get_sfdate( $way[ 'datum' ] ) : "<i class='icon-location red'></i> Не начат" ).'
				</div>
	
			</div>
			<div class="flex-container p10">
	
				<div class="flex-string wp100 uppercase fs-09 Bold gray2 mb10">Завершение прохождения</div>
				<div class="flex-string wp100">
					'.( $way[ 'isEnd' ] ? '<i class="icon-calendar-1 green"></i> '.get_sfdate( $way[ 'datum_end' ] ) : "<i class='icon-flag-1 red'></i> Не закончен" ).'
				</div>
	
			</div>
			<div class="flex-container p10">
	
				<div class="flex-string wp100 uppercase fs-09 Bold gray2 mb10">Текущий прогресс</div>
				<div class="flex-string wp100">
					
					<div class="fs-14 blue">'.round( $progress[ 'progress' ] * 100, 1 ).'%</div>
					
				</div>
	
			</div>
			
		</div>
		
		<div id="files" class="pt10 pb10">
			<div id="divider" class="div-center"><b><i class="icon-attach-1 fs-16"></i>Прикрепленные файлы</b></div>
			'.$files.'
		</div>
			
	</div>
	
	<div class="footer pl10" style="height:60px">
		<a href="javascript:void(0)" onclick="closeInfo()" class="button bcancel">Закрыть</a>
	</div>
	';

	exit();

}

// Окно просмотра
if ( $action == "courceViewDialog" ) {

	$id = $_REQUEST[ 'id' ];

	$categories = '';

	$Course = CorpUniver ::info( $id )[ 'data' ];

	$idcat = $Course[ "cat" ];
	$cat   = $db -> getOne( "SELECT title FROM ".$sqlname."corpuniver_course_cat WHERE id = '$idcat' AND identity = '$identity'" );

	$files = ( $Course[ 'fid' ] != '' ) ? '<div class="fs-09 gray ml5 em">При прохождении курса слушатели могут просматривать и скачивать файлы</div>' : '<div class="bad">Файлы отсутствуют</div>';

	$last_change = '<i class="icon-calendar-inv blue"></i> '.datetimeru2datetime( $Course[ 'date_edit' ] ).'<span class="noBold">, '.current_user( $Course[ 'editor' ] ).'</span>';


	// Признак начала курса
	$way = CorpUniver ::infoWayCource( ["idcourse" => $id] );

	// Прогресс выполнения
	$progress = CorpUniver ::progressCource( $id );

	?>
	<DIV class="zagolovok">Сведения о курсе</DIV>
	<div id="formtabs" style="overflow-y: auto; overflow-x: hidden; max-height: 70vh" class="p5">

		<div class="m0 p0 pb20 mt10 blue fs-14 Bold">
			<span class="gray">Курс: </span>
			<span class="blue fs-12 uppercase"><?= $Course[ 'name' ] ?></span>
		</div>

		<div class="bgwhite fcontainer flex-vertical p0 border--bottom box--child fs-10">

			<div class="flex-container p10">

				<div class="flex-string wp100 uppercase fs-09 Bold gray2 mb10">Дата создания</div>
				<div class="flex-string wp100 relativ blue">
					<i class="icon-calendar-1 green"></i>
					<?= format_date_rus( $Course[ 'date_create' ] ) ?>
				</div>

			</div>

			<div class="flex-container p10">

				<div class="flex-string wp100 uppercase fs-09 Bold gray2">Последнее изенение</div>
				<div class="flex-string wp100 mt10">
					<?= $last_change ?>
				</div>

			</div>

			<div class="flex-container p10">

				<div class="flex-string wp100 uppercase fs-09 Bold gray2 mb10">Автор</div>
				<div class="flex-string wp100 relativ">
					<i class="icon-user-1 blue"></i>
					<?= current_user( $Course[ 'author' ] ) ?>
				</div>

			</div>

			<div class="flex-container p10">

				<div class="flex-string wp100 uppercase fs-09 Bold gray2 mb10">Категория</div>
				<div class="flex-string wp100 blue">
					<i class="icon-folder-open orange"></i><?= $cat ?>
				</div>
			</div>

			<div class="flex-container p10">

				<div class="flex-string wp100 uppercase fs-09 Bold gray2">Описание</div>
				<div class="flex-string wp100 mt10 noBold text-wrap">
					<?= $Course[ 'des' ] ?>
				</div>

			</div>

		</div>

		<div class="divider mt10 mb10">Прохождение</div>

		<div class="bgwhite fcontainer flex-vertical p0 border--bottom box--child fs-10">

			<div class="flex-container p10">

				<div class="flex-string wp100 uppercase fs-09 Bold gray2 mb10">Начало прохождения</div>
				<div class="flex-string wp100">
					<?= ( $way[ 'isStart' ] ? '<i class="icon-calendar-1 green"></i> '.get_sfdate( $way[ 'datum' ] ) : "<i class='icon-location red'></i> Не начат" ) ?>
				</div>

			</div>
			<div class="flex-container p10">

				<div class="flex-string wp100 uppercase fs-09 Bold gray2 mb10">Завершение прохождения</div>
				<div class="flex-string wp100">
					<?= ( $way[ 'isEnd' ] ? '<i class="icon-calendar-1 green"></i> '.get_sfdate( $way[ 'datum_end' ] ) : "<i class='icon-flag-1 red'></i> Не закончен" ) ?>
				</div>

			</div>
			<div class="flex-container p10">

				<div class="flex-string wp100 uppercase fs-09 Bold gray2 mb10">Текущий прогресс</div>
				<div class="flex-string wp100">

					<div class="fs-14 blue"><?= round( $progress[ 'progress' ] * 100, 1 ) ?>%</div>

				</div>

			</div>

		</div>

		<div id="files" class="pt10 pb10">

			<div id="divider" class="div-center"><b><i class="icon-attach-1 fs-16"></i>Прикрепленные файлы</b></div>
			<div id="filelist"></div>
			<?= $files ?>

		</div>

	</div>

	<div class="button--pane text-right">

		<a href="javascript:void(0)" onclick="DClose()" class="button bcancel">Закрыть</a>

	</div>
	<script>

		$('#dialog').find('#filelist').load('modules/corpuniver/core.corpuniver.php?action=files&type=Course&id=' + <?=$id?>).append('<img src="/assets/images/loading.svg">')
			.complete(function () {

				$('#dialog').center();

			});

	</script>
	<?php

	exit();

}

// Массив содержимого курса ( для Шаблонизатора )
if ( $action == "courseConstructor" ) {

	$id = $_REQUEST[ 'id' ];

	$list = CorpUniver ::courseConstructor($id);

	print json_encode_cyr( ["list" => $list] );

	exit();

}

// Начало изучения курса ( отрисовка основного окна )
if ( $action == 'startCource' ) {

	$id = $_REQUEST[ 'id' ];

	$cource   = CorpUniver ::info( $id );
	$lections = CorpUniver ::listLections( $id )[ 'data' ];

	// Признак начала курса
	$way = CorpUniver ::infoWayCource( ["idcourse" => $id] );

	if ( !$way[ 'isStart' ] )
		CorpUniver ::startWayCource( [
			"idcourse" => $id,
			"start"    => true
		] );
	?>

	<div class="flex-container box--child" style="">

		<div class="hidden" id="course_id"><?= $id ?></div>
		<div class="hidden" id="slideN">0</div>
		<div class="hidden" id="slideMax">0</div>

		<input type="hidden" id="currentCource" value="<?= $id ?>">

		<div class="flex-string wp20 graybg-sub" style="overflow: hidden">

			<div class="infodiv" id="ii">

				<div class="">
					<a href="javascript:void(0)" onclick="editCourse('<?= $id ?>','viewdialog');" title="Инфо"><i class="icon-info-1 green"></i> Сведения о курсе</a>
				</div>

			</div>

			<div class="alist" style="overflow-x: hidden; overflow-y: auto;min-height: calc(100vh - 55px)">

				<div id="accordion"></div>

			</div>

		</div>
		<div class="flex-string wp80 slide p5">

			<div class="content-slide" style="height: calc(100vh - 130px); overflow-y: auto !important; overflow-x: hidden; border:1px solid #B5B8C8"></div>

			<div class="inline nav-btns p5">

				<div class="flex-container box--child text-center button--group1">

					<div class="flex-string hidden">

						<a href="javascript:void(0)" onclick="toSlide('prev');" title="Пред. слайд" class="button graybtn m0" id="prev">
							<i class="icon-left-open"></i><span class="hidden-iphone"> Предыдущий</span>
						</a>

					</div>

					<div class="flex-string" data-type="nextButton">

						<a href="javascript:void(0)" onclick="toSlide('next');" title="След. слайд" class="button rounded-r10 greenbtn wp50 p15 m0" id="next">
							<i class="icon-ok-circled"></i><span class="hidden-iphone"> Пройдено </span>
						</a>

					</div>

					<div class="flex-string hidden" data-type="totaskButton">

						<a href="javascript:void(0)" onclick="toTest()" title="Зафиксировать" class="button orangebtn m0" id="totest">
							<i class="icon-tasks"></i><span class="hidden-iphone"> Зафиксировать результат теста</span>
						</a>

					</div>

				</div>

			</div>

		</div>

	</div>

	<script>

		var hh = $('#ii').height() + 80;
		var currentCource = $('#currentCource').val();
		var slideMax = 0;
		var currentSlide = 0;
		var currentMaterial = 0;
		var currentTask = 0;
		var currentLecture = 0;
		var currentSlideType = 'material';

		$('.alist').css({"min-height": "calc(100vh - " + hh + "px)"});

		$.Mustache.load('/modules/corpuniver/tpl.corpuniver.html');

		$(document).ready(function () {

			materialList();
			constructSpace();

		});

		// Переход по материалам/заданиям
		$('a.menu').click(function () {

			var num = $(this).data('slide');

			toSlide(num);

		});

		window.onhashchange = function () {

			var hash = window.location.hash.substring(1);

			//toSlide(parseInt(hash));

			if ($('.lpToggler').hasClass('open') && isMobile)
				$('.lpToggler').trigger('click');

		};

		function constructSpace() {

			var hw = $('.alist').width();
			var ht = ($('.listHead').is(':visible')) ? $('.listHead').actual('outerHeight') : 0;
			var hh = $('.alist').actual('innerHeight');
			var hah = $('#accordion').find('h3:first-child').actual('outerHeight');
			var count = $('#accordion').find('h3').length;
			var mh = hh - count * hah - 10;
			var ch = hh - ht - 0;

			$('.alist').find('.tableHeader').css({"width": hw + "px", "top": ht + 'px', "left": "0px"});
			$('#clist').css({"height": ch + "px"});

			$('#accordion').css({"height": hh + "px", "max-height": hh + "px"});
			$('#accordion').find('.ui-accordion-content').css({"height": mh + "px", "max-height": mh + "px"});

		}

		function materialList(loadSlide) {

			if (loadSlide === 'list')
				$('#accordion').accordion("destroy");

			//console.log(currentSlide);

			fetch("/modules/corpuniver/view.corpuniver.php?action=courseList&id=" + currentCource)
				.then(response => response.json())
				.then(data => {

					//console.log(data);

					if (data.error)
						Swal.fire({
							title: 'Ошибка',
							text: data.error,
							type: 'error',
							showCancelButton: true
						});

					else {

						$('#accordion').empty().mustache('courseListTpl', data);

						//console.log(data);
						//console.log(data.currentSlideType);
						//console.log(data.currentTask);

						if (loadSlide !== 'list') {

							currentMaterial = data.current;
							slideMax = data.maxSlide;

							currentSlide = data.currentSlide;
							currentSlideType = data.currentSlideType;
							currentLecture = data.currentLecture;

						}

						if (data.currentSlideType === 'task' && data.currentTask > 0) {

							currentTask = data.currentTask;

							$('div[data-type="nextButton"]').addClass('hidden');
							$('div[data-type="totaskButton"]').removeClass('hidden');

						}
						else if(data.currentSlideType === 'material'){

							$('div[data-type="totaskButton"]').addClass('hidden');
							$('div[data-type="nextButton"]').removeClass('hidden');

						}


					}

				})
				.then(function () {

					toSlide(currentSlide);

					$('#accordion').accordion({
						heightStyle: true,
						collapsible: true
					});

				})
				.catch(error => {

					//console.log(error);

					Swal.fire({
						title: 'Ошибка',
						text: error,
						type: 'error',
						showCancelButton: true
					});

				});

		}

		/**
		 * Вывод слайда курса
		 */
		function toSlide(num) {

			if (num === 'next' && currentSlide < slideMax) {

				//меняем статус прохождения
				fetch("/modules/corpuniver/core.corpuniver.php?action=changeMaterialStatus&idmaterial=" + currentMaterial + "&idlecture=" + currentLecture + "&idtask=" + currentTask)
					.then(response => response.json())
					.then(data => {

						//console.log(data);

						if (data.error)
							Swal.fire({
								title: 'Ошибка',
								text: data.error,
								type: 'error',
								showCancelButton: true
							});

						else {

							currentSlide += 1;

						}

					})
					.then(function () {

						materialList('list');

					})
					.then(function () {

						configpage();

					})
					.catch(error => {

						//console.log(error);

						Swal.fire({
							title: 'Ошибка',
							text: error,
							type: 'error',
							showCancelButton: true
						});

					});

			}
			else if (num === 'prev' && currentSlide > 0)
				currentSlide -= 1;

			else currentSlide = (num >= 0) ? num : currentSlide;

			var $elm = $('a#slide[data-slide="' + currentSlide + '"');
			var type = $elm.data('type');
			var id = $elm.data('id');
			var lection = $elm.parent('#matlec').data('lec');

			$('#currentMaterial').val(id);
			currentMaterial = id;

			//console.log(currentSlide);
			//console.log(slideMax);

			if (currentSlide < slideMax) {

				$('#accordion').accordion({active: lection});

				window.location.hash = '#' + currentSlide;

				$('a#slide').removeClass('current');
				$elm.addClass('current');

				$('#slideN').html(currentSlide);

				$.get('/modules/corpuniver/view.corpuniver.php?action=slide&id=' + id + '&type=' + type, function (data) {

					$('.content-slide').html(data);

				});

			}
			else {

				fetch("/modules/corpuniver/core.corpuniver.php?action=changeMaterialStatus&idcourse=" + currentCource)
					.then(response => response.json())
					.then(data => {

						//console.log(data);

						if (data.error)
							Swal.fire({
								title: 'Ошибка',
								text: data.error,
								type: 'error',
								showCancelButton: true
							});

						else {

							Swal.fire({
								title: 'Курс пройден',
								text: 'Поздравляем!',
								type: 'info',
								showCancelButton: false,
								showCloseButton: true,
								confirmButtonColor: '#3085D6',
								confirmButtonText: 'Ok'
							})
								.then((result) => {

									if (result.value) {

										$('.closer').trigger('click');

									}

								});

						}

					})
					.catch(error => {

						//console.log(error);

						Swal.fire({
							title: 'Ошибка',
							text: error,
							type: 'error',
							showCancelButton: true
						});

					});

			}

		}

		/**
		 * Отметка задания выполненным
		 */
		function toTest() {

			let em = checkRequiredMod('.content-slide');
			let type = $('#type').val();
			let str = $('#FormQuest').serialize();
			let url = $('#FormQuest').attr('action');
			let islast = $('#islast').val();

			if (em > 0 && type === 'question') {

				const msg = Swal.mixin({
					toast: true,
					position: 'center',
					showConfirmButton: false,
					background: "var(--red-dark)",
					timer: 3000
				});

				msg.fire(
					'<span class="white fs-11">Внимание!</span><br>',
					'<span class="white">Ответьте на вопрос!</span>',
					'warning'
				);

				return false;

			}

			if (em > 0 && type === 'test') {

				const msg = Swal.mixin({
					toast: true,
					position: 'center',
					showConfirmButton: false,
					background: "var(--red-dark)",
					timer: 3000
				});

				msg.fire(
					'<span class="white fs-11">Внимание!</span><br>',
					'<span class="white">Нужно ответить на все вопросы!</span>',
					'warning'
				);

				return false;

			}

			const msg = Swal.mixin({
				toast: true,
				position: 'bottom-center',
				showConfirmButton: false,
				timer: 3000
			});

			fetch(url+'?'+str)
				.then(response => response.json())
				.then(data => {

					//console.log(data);

					if (data.result !== 'Error') {

						let res = (data.result.percent > 50) ? 'success' : 'warning';

						msg.fire(
							'',
							data.result.text,
							res
						);

					}
					else {

						msg.fire(
							data.error.text,
							'',
							'warning'
						);

					}

					//меняем статус прохождения
					fetch("/modules/corpuniver/core.corpuniver.php?action=changeMaterialStatus&idtask=" + currentTask + "&idlecture=" + currentLecture)
						.then(response => response.json())
						.then(data => {

							//console.log(data);

							if (data.error)
								Swal.fire({
									title: 'Ошибка',
									text: data.error,
									type: 'error',
									showCancelButton: true
								});

							else {

								currentSlide += 1;

							}

						})
						.then(function () {

							// последнее задание
							if(islast === 'yes'){

								$('div[data-type="totaskButton"]').addClass('hidden');
								$('div[data-type="nextButton"]').removeClass('hidden');

								toSlide(currentSlide);

								Swal.fire({
									title: 'Пройдена лекция!',
									text: "Вы прошли все задания лекции",
									type: 'success',
									showCancelButton: false
								});

							}

							materialList('list');

						})
						.then(function () {

							configpage();

						})
						.catch(error => {

							//console.log(error);

							Swal.fire({
								title: 'Ошибка',
								text: error,
								type: 'error',
								showCancelButton: true
							});

						});

				})
				.catch(error => {

					//console.log(error);

				});

			return true;

		}

	</script>

	<?php

}

// Список лекций по курсу ( для Шаблонизатора )
if ( $action == 'courseList' ) {

	$id = $_REQUEST[ 'id' ];

	$list = CorpUniver::courseList($id);

	print json_encode_cyr( $list );

}

// Слайд курса
if ( $action == 'slide' ) {

	$id   = $_REQUEST[ 'id' ];
	$type = $_REQUEST[ 'type' ];
	$preview = $_REQUEST[ 'preview' ];

	if ( $type == 'material' ) {

		$mat = CorpUniver ::infoMaterial( $id )[ 'data' ];

		if($preview != 'yes') {

			// признак начала изучения лекции
			$way = CorpUniver ::infoWayCource( [
				"idcourse"  => $mat[ 'course' ],
				"idlecture" => $mat[ 'lecture' ]
			] );

			if ( !$way[ 'isStart' ] )
				CorpUniver ::startWayCource( [
					"idcourse"  => $mat[ 'course' ],
					"idlecture" => $mat[ 'lecture' ],
					"start"     => true
				] );


			// Признак начала изучения материала
			$way = CorpUniver ::infoWayCource( [
				"idcourse"   => $mat[ 'cours' ],
				"idlecture"  => $mat[ 'lecture' ],
				"idmaterial" => $id
			] );

			if ( !$way[ 'isStart' ] )
				CorpUniver ::startWayCource( [
					"idcourse"   => $mat[ 'course' ],
					"idlecture"  => $mat[ 'lecture' ],
					"idmaterial" => $id,
					"start"      => true
				] );

		}

		if ( $mat[ 'text' ] != '' )
			print '<div class="p10 fs-12 flh-12 text-wrap">'.htmlspecialchars_decode( $mat[ 'text' ] ).'</div>';

		if ( $mat[ 'source' ] != ''){

			$url = parse_url($mat[ 'source' ]);

			if(in_array(str_replace("www.","",$url['host']), CorpUniver::VIDEOSITE))
				print '<embed width="100%" height="99.5%" class="pt5" src="'.$mat[ 'source' ].'" frameborder="0" allow="accelerometer; autoplay; encrypted-media; gyroscope; picture-in-picture" allowfullscreen>';

			else
				print '<iframe src="'.$mat[ 'source' ].'" width="100%" height="99.5%" class="pt5"></iframe>';

		}


		$fids = yexplode( ",", $mat[ 'fid' ] );
		foreach ( $fids as $fid ) {

			$file = Upload::info($fid);

			if(in_array($file['ext'],['pdf','png','jpeg','jpg','gif'])) {
				print '<embed width="100%" height="99.5%" class="pl5 pt5" style="background:black" src="/files/'.$fpath.$file['file'].'" frameborder="0" allow="accelerometer; autoplay; encrypted-media; gyroscope; picture-in-picture" allowfullscreen>';
			}

			if(in_array($file['ext'],['avi','mp4','mpeg','ogv','webm'])) {
				print '<video src="/files/'.$fpath.$file['file'].'" type="'.$file['mime'].'" controls width="100%" height="99.5%"></video>';
			}

		}

	}
	elseif ( $type == 'task' ) {

		$task = CorpUniver ::infoTask( $id )[ 'data' ];

		if($preview != 'yes') {

			// Признак начала изучения материала
			$way = CorpUniver ::infoWayCource( [
				"idcourse"  => $task[ 'cours' ],
				"idlecture" => $task[ 'lecture' ],
				"idtask"    => $id
			] );

			if ( !$way[ 'isStart' ] )
				CorpUniver ::startWayCource( [
					"idcourse"  => $task[ 'course' ],
					"idlecture" => $task[ 'lecture' ],
					"idtask"    => $id,
					"start"     => true
				] );

		}

		?>
		<FORM action="/modules/corpuniver/core.corpuniver.php" method="post" enctype="multipart/form-data" name="FormQuest" id="FormQuest">
			<INPUT type="hidden" name="action" id="action" value="verification.task">
			<INPUT type="hidden" name="id" id="id" value="<?= $id ?>">
			<INPUT type="hidden" name="type" id="type" value="<?= $task[ 'type' ] ?>">
			<INPUT type="hidden" name="islast" id="islast" value="<?= ($task[ 'isLast' ] ? "yes" : "no") ?>">

			<div class="p20">

				<div class="mb20 pt10 Bold fs-14 uppercase"><?= $task[ 'name' ] ?></div>

				<?php
				if ( $task[ 'type' ] == 'question' ) {

					$quest = $db -> getRow( "SELECT id, text FROM ".$sqlname."corpuniver_questions WHERE task = '$id' and identity = '$identity'" );

					?>
					<div class="pt10 fs-11 flh-11 text-wrap"><?= htmlspecialchars_decode( $quest[ 'text' ] ) ?></div>

					<input type="hidden" id="question" name="question" value="<?= $quest[ 'id' ] ?>">
					<div class="infodiv mt15 p20 fs-11">

						<div class="Bold gray2 fs-09 mb10">Ответ</div>
						<textarea id="answer" name="answer" type="text" class="fs-12 required wp100" rows="5"></textarea>
						<div class="fs-07">Предполагается развернутый ответ на поставленный вопрос</div>

					</div>
					<?php

				}
				else {

					$questions = CorpUniver ::listQuestions( $id )[ 'data' ];
					$print     = '';

					foreach ( $questions as $i => $q ) {

						$ans     = [];
						$answers = CorpUniver ::infoQuestion( $q[ 'id' ] )[ 'answers' ];

						foreach ( $answers as $a )
							$ans[] = $a[ 'text' ];

						$print .= '
						<div class="mb20 infodiv">
						
							<div class="pt10 fs-11 flh-12">'.$q[ 'text' ].'</div>
							<input type="hidden" id="question[]" name="question[]" value="'.$q[ 'id' ].'">
							<div class="mt15 p0 fs-11 req">
								'.Elements ::Radio( 'answer-'.$q[ 'id' ], $ans, ["sel" => -1, "mainclass" => "mb5 mt5", "radioclass" => "infodiv inset1 bgwhite p10 rounded-r10", "empty" => false] ).'
							</div>
							
						</div>
						';

					}

					print $print;

				}
				?>

				<div class="mt15 pl30 fs-12 hidden" id="resultat"></div>

				<!--Скрываем кнопку проверки результатов. Результаты выведем отдельно-->
				<!--
				<a href="javascript:void(0)" id="checkBtn" class="button greenbtn mt20" onclick="$('#FormQuest').submit()" title="Проверить результаты">
					<i class="icon-ok"></i> Проверить результат
				</a>
				-->

			</div>

		</FORM>
		<script>

			$('#FormQuest').ajaxForm({
				dataType: 'json',
				beforeSubmit: function () {

					var em = checkRequiredMod('.content-slide');
					var type = $('#type').val();

					if (em > 0 && type === 'question') {

						/*
						 const msg = Swal.mixin({
						 toast: true,
						 position: 'top-end',
						 showConfirmButton: false,
						 timer: 3000
						 });

						 msg.fire(
						 'Выполните все задания!',
						 '',
						 'warning'
						 );
						 */

						Swal.fire({
							title: "Ошибка",
							text: "Ответьте на вопрос!",
							type: "warning"
						});

						return false;

					}

					if (em > 0 && type === 'test') {

						/*
						 const msg = Swal.mixin({
						 toast: true,
						 position: 'top-end',
						 showConfirmButton: false,
						 timer: 3000
						 });

						 msg.fire(
						 'Ответьте на все вопросы!',
						 '',
						 'warning'
						 );
						 */

						Swal.fire({
							title: "Ошибка",
							text: "Нужно ответить на все вопросы!",
							type: "warning"
						});

						return false;

					}

					return true;

				},
				success: function (data) {

					/*const msg = Swal.mixin({
					 toast: true,
					 position: 'top-end',
					 showConfirmButton: false,
					 timer: 3000
					 });*/

					if (data.result !== 'Error') {

						let res = (data.result.percent > 50) ? 'success' : 'warning';

						/*msg.fire(
						 '',
						 data.result.text,
						 res
						 );*/

						$('#resultat').empty().removeClass('hidden').addClass(res).append('<span class="Bold fs-13">Результат: </span>' + data.result.text);

					}
					else {

						msg.fire(
							data.error.text,
							'',
							'warning'
						);

					}

					//materialList('list');

					//$('input').prop('disabled', true);
					//$('#checkBtn').addClass('hidden');

				}

			});

		</script>

		<?php

	}

}

/**
 * статистика курса
 */
if ( $action == "courseStat" ){

	$id = $_REQUEST['id'];

	// пользователи, начавшие тестирование
	$users = $db -> getCol("SELECT iduser FROM ".$sqlname."corpuniver_coursebyusers WHERE idcourse = '$id' AND idlecture = '0' AND idmaterial = '0' AND idtask = '0' AND identity = '$identity'");

	$list = [];

	foreach ($users as $iduser){

		$list[$iduser] = CorpUniver ::courseConstructor($id, $iduser);

	}

	//print array2string( $list, "<br>", str_repeat( "&nbsp;", 5 ) );

	$head = CorpUniver ::courseConstructor($id, 0);
	$str = '';
	?>
	<div class="zagolovok">Статистика прохождения курса</div>
	<div id="formtabs" style="overflow-y: auto; overflow-x: hidden; min-height: 400px; max-height: 80vh" class="p5 bgwhite graybg-sub">

		<table class="space-1">
			<thead class="sticked--top noshadow bgwhite">
			<tr>
				<th rowspan="2">Сотрудник</th>
				<?php
				foreach ($head['lection'] as $num => $lection){

					if(($lection['materialCount'] + $lection['taskCount']) > 0) {

						print '
						<th colspan="'.($lection['materialCount'] + $lection['taskCount']).'" class="'.($num % 2 == 0 ? 'greenbg' : '').'">'.$lection['name'].'</th>';

						foreach ( $lection['material'] as $material )
							$str .= '
							<th title="'.trim($material['name']).'" class="top0">'.($material['num'] + 1).' <i class="'.$material['icon'].'"></i></th>';

						foreach ( $lection['task'] as $task )
							$str .= '
							<th title="'.trim($task['name']).'" class="top0">'.($task['num'] + 1).' <i class="'.$task['icon'].'"></i></th>';

					}

				}
				?>
			</tr>
			<tr><?=$str?></tr>
			</thead>
			<tbody class="graybg-sub">
			<?php
			foreach ($list as $iduser => $item){

				$str = '';

				foreach ($item['lection'] as $lection) {

					foreach ( $lection['material'] as $material ) {

						$icon = '';

						if($material['isStart'])
							$icon = '<i class="icon-location blue"></i>';

						if($material['isEnd'])
							$icon = '<i class="icon-ok-circled green"></i>';

						$str .= '
						<td title="'.$material['text'].'" class="text-center">'.$icon.'</td>';

					}

					foreach ( $lection['task'] as $task ) {

						$title = $task['title'];

						if($task['isStart'])
							$icon = '<i class="icon-location blue"></i>';

						if($task['isEnd'])
							$icon = '<i class="icon-ok-circled green"></i>';

						if($task['rezult']){

							$icon = '';

							foreach($task['rezult'] as $r) {

								$title = "Вопрос:\n".untag($r[ 'query' ])."\n\n";
								$title .= "Ответ сотрудника: ".$r[ 'answer' ]."\n";
								$title .= "Верный ответ: ".$r[ 'answerGood' ]."\n\n";
								$title .= "Результат: ".$r[ 'title' ];

								$icon  .= '<i class="'.$r[ 'icon' ].'" title="'.$title.'"></i>';

							}

						}

						$str .= '
						<td class="text-center">'.$icon.'</td>';

					}

				}

				print '
				<tr class="ha th40">
					<td>
						<i class="icon-user-1 blue"></i>'.current_user($iduser).'
					</td>
					'.$str.'
				</tr>';

			}
			?>
			</tbody>
		</table>

	</div>

	<script>

		$('#dialog').css({'width':'90vw'});

	</script>
<?php

}

/**
 * статистика курса
 */
if ( $action == "courseStatUser" ){

	$id = $_REQUEST['id'];

	$list = CorpUniver ::courseConstructor($id, $iduser1);

	$str = '';
	?>
	<div class="zagolovok">Статистика прохождения курса</div>
	<div id="formtabs" style="overflow-y: auto; overflow-x: hidden; min-height: 400px; max-height: 80vh" class="p5 bgwhite graybg-sub pr10">

		<?php
		// обходим лекции
		foreach ($list['lection'] as $lec){

			if($lec['materialCount'] > 0) {

				$material = $tasks = '';

				foreach ( $lec['material'] as $mat ) {

					$material .= '
					<div class="flex-container p10">
		
						<div class="flex-string wp100 uppercase fs-07 Bold gray2 mb10">Материал</div>
						<div class="flex-string wp100 relativ fs-12 Bold">
						
							<b><i class="'.$mat['icon'].'"></i>&nbsp;'.$mat['name'].'</b>
							
							<div class="fs-07 gray mt10">
							
								<span class="black Bold fs-12">[ '.diffDateTime( $mat['do']['datum'], $mat['do']['datum_end'] ).' ]</span>
								&nbsp<i class="icon-location blue"></i>&nbsp;'.get_sfdate( $mat['do']['datum'] ).' - <i class="icon-flag-1 green"></i>&nbsp;'.get_sfdate( $mat['do']['datum_end'] ).'
								
							</div>
							
						</div>
		
					</div>
					';

				}

				foreach ( $lec['task'] as $task ) {

					$reztxt = '';

					if($task['rezult']){

						foreach($task['rezult'] as $r) {

							$reztxt .= '
							<div class="infodiv bgwhite fs-09 mb5">
								<b>Вопрос:</b><br>'.untag($r[ 'query' ]).'<br><br>
								<b>Ответ сотрудника:</b> '.$r[ 'answer' ].'<br>
								<b>Результат:</b> <i class="'.$r[ 'icon' ].'"></i>&nbsp;'.$r[ 'title' ].'
							</div>
							';

						}

					}

					$tasks .= '
					<div class="flex-container p10 graybg-sub mb5">
		
						<div class="flex-string wp100 uppercase fs-07 Bold gray2 mb10">Задание</div>
						<div class="flex-string wp100 relativ fs-12 Bold">
						
							<b><i class="'.$task['icon'].'"></i>&nbsp;'.$task['name'].'</b>
							
							<div class="fs-07 gray mt10 pl10">
							
								<span class="black Bold fs-12">[ '.diffDateTime( $task['do']['datum'], $task['do']['datum_end'] ).' ]</span>
								&nbsp<i class="icon-location blue"></i>&nbsp;'.get_sfdate( $task['do']['datum'] ).' - <i class="icon-flag-1 green"></i>&nbsp;'.get_sfdate( $task['do']['datum_end'] ).'
								
							</div>
							
							<div class="fs-09 mt10">'.$reztxt.'</div>
							
						</div>
		
					</div>
					';

				}

				print '
				<div class="bgwhite fcontainer flex-vertical p0 border--bottom box--child fs-10 mb10">
	
					<div class="flex-container p10 bluebg-sub sticked--top">
		
						<div class="flex-string wp100 uppercase fs-07 Bold gray2 mb10">Лекция</div>
						<div class="flex-string wp100 relativ fs-12 Bold blue">
							<b>'.$lec['name'].'</b>
						</div>
		
					</div>
					'.$material.'
					'.$tasks.'
				
				</div>
				';

			}

		}
		?>

	</div>

	<script>

		$('#dialog').css({'width':'600px'});

	</script>
	<?php

}