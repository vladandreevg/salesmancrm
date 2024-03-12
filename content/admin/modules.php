<?php
/* ============================ */
/*         SalesMan CRM         */
/* ============================ */
/* (C) 2016 Vladislav Andreev   */
/*       SalesMan Project       */
/*        www.isaler.ru         */
/*        ver. 2017.x           */
/* ============================ */

error_reporting(E_ERROR);
ini_set( 'display_errors', 1 );
header("Pragma: no-cache");

$rootpath = dirname(__DIR__, 2);

include $rootpath."/inc/config.php";
include $rootpath."/inc/dbconnector.php";
include $rootpath."/inc/auth.php";
include $rootpath."/inc/func.php";
include $rootpath."/inc/settings.php";

$thisfile = basename( __FILE__ );

/**
 * Если ячейки нет, то добавляем
 */
$field = $db -> getRow("SHOW COLUMNS FROM {$sqlname}modules LIKE 'activateDate'");
if ($field['Field'] == '') {
	$db -> query( "ALTER TABLE {$sqlname}modules ADD COLUMN `activateDate` DATETIME NULL DEFAULT NULL AFTER `active`" );
}

$action = $_REQUEST['action'];

$modulesPay  = [
	"moddaycontrol" => [
		"name"        => "Узлы и приборы учета",
		"image"       => "https://salesman.pro/docs.img/docs/ppu_main.png",
		"url"         => "https://salesman.pro/docs/113",
		"description" => "Модуль предназначен для компаний, которые занимаются продажей и обслуживанием каких-либо объектов (оборудование, здания и пр.) и им необходимо контролировать сроки проведения различных проверок/поверок данного оборудования.",
		"pay"         => true
	],
	"modworkplan"   => [
		"name"        => "План работ",
		"image"       => "https://salesman.pro/docs.img/docs/mwp_gantt2.png",
		"url"         => "https://salesman.pro/docs/114",
		"description" => "Модуль позволяет вести учет выполнения работ по заказчикам с указанием исполнителей работ, формирование ежедневных заданий по бригадам/исполнителям, а также вести расчет з/пл исполнителей.",
		"pay"         => true
	],
	"callcenter"    => [
		"name"        => "Центр исходящих звонков",
		"image"       => "https://salesman.pro/docs.img/docs/cc_main_task_with_comment_2.png",
		"url"         => "https://salesman.pro/docs/112",
		"description" => "Модуль «ЦИЗ» предназначен для работы в отделе телемаркетинга или в небольшом колл-центре и позволяет назначать операторам задания для исходящих звонков, а также контролировать их выполнение.",
		"pay"         => true
	],
	"soiskatel"     => [
		"name"        => "Соискатель",
		"image"       => "https://salesman.pro/docs.img/docs/soiskatel_vacancy_soiskatel_list.png",
		"url"         => "https://salesman.pro/docs/126",
		"description" => "Модуль предназначен для облегчения и систематизации работы с вакансиями компании и подбора персонала в небольших компаниях.",
		"pay"         => true
	],
	"projects"      => [
		"name"        => "Проекты",
		"image"       => "https://salesman.pro/docs.img/docs/prj_gantt.png",
		"url"         => "https://salesman.pro/docs/144",
		"description" => "Модуль позволяет управлять проектами (в т.ч. по сделкам), связанными задачами с назначением ответственных и контролем сроков выполнения.",
		"pay"         => true
	],
	"corpuniver"      => [
		"name"        => "Корпоративный университет",
		"image"       => "https://salesman.pro/docs.img/docs/cu_course.png",
		"url"         => "https://salesman.pro/docs/149",
		"description" => "Модуль позволяет организовать обучение и проверку знаний сотрудников компании.",
		"pay"         => false
	],
];
$modulesCustom = json_decode(file_get_contents($rootpath."/cash/map.modules.json"), true);

$modulesBase = array_merge($modulesPay, $modulesCustom);
$modulesExist = [];

//file_put_contents("../cash/map.modules.json", json_encode_cyr($modulesBase));

if ($action == "save") {

	$err = [];

	$result = $db -> getAll("SELECT * FROM {$sqlname}modules WHERE identity = '$identity' ORDER by id");
	foreach ($result as $data) {

		try {
			$db -> query("update {$sqlname}modules set active = '".$_REQUEST[ $data['mpath'] ]."' WHERE mpath = '".$data['mpath']."' and identity = '$identity'");

		}
		catch (Exception $e) {
			$err[] = $e -> getMessage();
		}

	}

	if (count($err) > 0) {
		print "Выполнено с ошибками.<br>Ошибки: ".implode("<br>", $err);
	}
	else {
		print "Выполнено. Обновите окно";
	}

	unlink($rootpath."/cash/".$fpath."settings.all.json");

	exit();

}
if ($action == "onoff") {

	$id = (int)$_REQUEST['id'];

	$ron = $db -> getRow("SELECT active, activateDate, mpath FROM {$sqlname}modules WHERE id = '$id' and identity = '$identity'");

	$on = ($ron['active'] == 'on') ? "off" : "on";

	$do = ($on == 'on') ? "Активирован" : "Отключен";

	try {

		if (($isCloud && (diffDate( $ron['activateDate']) == 0 || diffDate( $ron['activateDate']) > 14 || $ron['active'] == 'off')) || !$isCloud || !$modulesBase[ $ron['mpath'] ]['pay'] ) {

			$db -> query("UPDATE {$sqlname}modules SET ?u WHERE id = '$id' and identity = '$identity'", [
				'active'       => $on,
				'activateDate' => current_datumtime()
			]);

			if($on == 'on'){

				// подключаем хук вручную, т.к. в системе его еще нет
				//include $rootpath."/modules/soiskatel/hook_soiskatel.php";

				$hooks -> do_action( "module_activate", $ron['mpath'] );

			}
			else{

				$hooks -> do_action( "module_deactivate", $ron['mpath'] );

			}

			print "Модуль ".$do;

		}
		else {

			$diff = 14 - diffDate($ron['activateDate']);
			print "Модуль не может быть отключен в течение $diff ".getMorph($diff, 'day');

		}

	}
	catch (Exception $e) {
		echo $e -> getMessage();
	}

	unlink($rootpath."/cash/".$fpath."settings.all.json");
	exit();

}

if ($action == "") {

	$string = '';
	?>

	<style>
		.good {
			background-color : rgba(200, 230, 201, 0.5);
			border           : 1px solid rgba(200, 230, 201, 0.8);
			padding          : 10px;
			color            : #222;
			border-radius    : 5px;
		}

		.thumb {
			height : 150px;
		}

		.text {
			overflow-y : auto;
			max-height : 100px;
			height     : 100px;
		}
	</style>

	<?php
	if ( $isCloud ) {
		?>

		<div class="warning p10 fs-11">
			<h2 class="red fs-12 mt5">Внимание!</h2>
			Деактивация некоторых модулей возможна либо <b>в день активации</b>, либо
			<b>через 14 дней</b> после активации!
		</div>

	<?php }
	?>

	<FORM action="/content/admin/<?php echo $thisfile; ?>" method="post" enctype="multipart/form-data" name="set" id="set">
		<INPUT type="hidden" name="action" id="action" value="save">

		<?php
		$string1 = $string2 = '';

		$result = $db -> getAll("SELECT * FROM {$sqlname}modules WHERE identity = '$identity' ORDER by title");
		foreach ($result as $data) {

			$modulesExist[] = $data['mpath'];
			$mName          = $data['mpath'];

			$cclass   = ($data['active'] == 'on') ? 'good' : 'viewdiv';
			$bclass   = ($data['active'] == 'on') ? 'green' : 'red';
			$btitle   = ($data['active'] == 'on') ? "Активен" : "Отключен";
			$btnclass = ($data['active'] == 'on') ? "green" : "red";
			$btnstate = ($data['active'] == 'on') ? "Откл." : "Вкл.";
			$btnact   = ($data['active'] == 'on') ? "Отключен" : "Включен";

			$img = ($modulesBase[ $mName ]['image'] != '') ? $modulesBase[ $mName ]['image'] : 'http://via.placeholder.com/300x150/fff/ccc';

			$text = ($modulesBase[ $mName ]['description'] != '') ? $modulesBase[ $mName ]['description'] : 'нет описания';

			$help = ($modulesBase[ $mName ]['url'] != '') ? '<a href="'.$modulesBase[ $mName ]['url'].'" target="blank" title="Перейти к описанию"><b>Подробнее</b></a>' : '';

			$diff = (14 - diffDate($data['activateDate']));

			$btnOn = (($isCloud && (diffDate( $data['activateDate']) == 0 || diffDate( $data['activateDate']) > 14 || $data['active'] == 'off')) || !$isCloud || !$modulesBase[ $mName ]['pay']) ? '<A href="javascript:void(0)" onclick="onoff(\''.$data['id'].'\');" class="gray1 pull-aright Bold" title="Вкл./Откл."><i class="icon-ok-circled '.$btnclass.'"></i> '.$btnstate.'</A>&nbsp;&nbsp;' : '<A href="javascript:void(0)" class="pull-aright Bold gray" title="Модуль может быть '.$btnact.' через '.$diff.' '.getMorph($diff, 'day').'"><i class="icon-help-circled-1"></i> Не доступно</A>';

			$price = '';
			if ( $isCloud && $modulesBase[ $mName ]['pay'] ) {

				$price = $db -> getOne("SELECT price FROM {$sqlname}payments_price WHERE type = 'module' AND name = '$data[mpath]'");
				$price = '<div class="fs-14 Bold mt10 mb10">'.$price.' '.$valuta.'/сут.</div>';

			}
			else {
				$price = '<div class="fs-14 mt10 mb10">&nbsp;</div>';
			}

			$str = '
				<div class="flex-string flx-basis-33 '.$cclass.' box-shadow mr5 mb5 relativ"  title="'.$data['title'].'">
					<div class="ellipsis" title="'.$btitle.'">
						<span class="fs-12 Bold"><i class="icon-cog '.$bclass.'"></i>'.$data['title'].'</span>
					</div>
					<div class="thumb mt10" style="background-image: url('.$img.'); background-size: cover;"></div>
					<div class="text fs-09 noBold mt10">'.$text.'</div>
					'.$price.'
					<div class="fs-09 mt10 mb5">
						'.$help.'
						'.$btnOn.'
					</div>
				</div>
			';

			if ($data['active'] == 'on') $string1 .= $str;
			else $string2 .= $str;

		}

		$string = $string1.$string2;

		foreach ($modulesBase as $module => $item) {

			if (!in_array($module, $modulesExist) && file_exists($rootpath."/modules/".$module)) {

				$img = ($item['image'] != '') ? $item['image'] : 'http://via.placeholder.com/300x150/fff/ccc';

				$help = ($modulesBase[ $module ]['url'] != '') ? '<a href="'.$modulesBase[ $module ]['url'].'" target="blank" title="Перейти к описанию"><b>Подробнее</b></a>' : '';

				$price = '';
				if ($isCloud == true && $modulesBase[ $module ]['pay'] == true) {

					$price = $db -> getOne("SELECT price FROM {$sqlname}payments_price WHERE type = 'module' AND name = '$module'");

					$price = '<div class="fs-14 Bold mt10 mb10">'.$price.' '.$valuta.'/сут.</div>';

				}
				else $price = '<div class="fs-14 Bold mt10 mb10">&nbsp;</div>';

				$string .= '
					<div class="flex-string flx-basis-33 graybg box-shadow mr5 mb5 p10 relativ"  title="'.$item['name'].'">
						<div class="ellipsis">
							<span class="fs-12 Bold"><i class="icon-cog gray2"></i>'.$item['name'].'</span>
						</div>
						<div class="thumb mt10" style="background-image: url('.$img.'); background-size: cover;"></div>
						<div class="text fs-09 noBold mt10">'.$item['description'].'</div>
						<div class="fs-09 mt10 mb5">
							'.$price.'
							'.$help.'
							<A href="javascript:void(0)" onclick="install(\''.$module.'\');" class="Bold pull-aright"><i class="icon-cog-alt red"></i> Установить</A>&nbsp;&nbsp;
						</div>
					</div>
				';

			}

		}
		?>

		<div class="flex-container box--child mt10"><?= $string ?></div>

		<DIV class="text-center hidden">

			<a href="javascript:void(0)" class="button" onclick="$('#set').trigger('submit')">Сохранить</a>

		</DIV>

		<div class="space-100"></div>

	</FORM>

	<script>

		$(function () {

			$('#set').ajaxForm({
				beforeSubmit: function () {
					var $out = $('#message');
					$out.empty();
					$out.css('display', 'block').append('<div id=loader><img src=/assets/images/loader.gif> Загрузка данных. Пожалуйста подождите...</div>');
					return true;
				},
				success: function (data) {

					$('#contentdiv').load('/content/admin/<?php echo $thisfile; ?>').append('<img src="/assets/images/loading.gif">');
					$('#message').fadeTo(1, 1).css('display', 'block').html(data);

					setTimeout(function () {
						$('#message').fadeTo(1000, 0);
						location.reload();
					}, 3000);

				}
			});

		});

		function onoff(id) {

			$('#message').css('display', 'block').append('<div id="loader" class="loader"><img src=/assets/images/loader.gif> Выполняю...</div>');
			$.post('/content/admin/<?php echo $thisfile; ?>?id=' + id + '&action=onoff', function (data) {

				$('#contentdiv').load('content/admin/<?php echo $thisfile; ?>');
				$('#message').fadeTo(1, 1).css('display', 'block').html(data);

				setTimeout(function () {
					$('#message').fadeTo(1000, 0);
					location.reload();
				}, 3000);

			})

		}

		function install(module) {

			$('#message').css('display', 'block').append('<div id="loader" class="loader"><img src=/assets/images/loader.gif> Выполняю...</div>');
			$.post('/modules/' + module + '/install.php', function (data) {

				$('#contentdiv').load('/content/admin/<?php echo $thisfile; ?>');
				$('#message').fadeTo(1, 1).css('display', 'block').html(data);

				setTimeout(function () {
					$('#message').fadeTo(1000, 0);
					location.reload();
				}, 3000);

			})

		}

	</script>
	<?php
}
?>