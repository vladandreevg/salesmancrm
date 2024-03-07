<?php
/* ============================ */
/*         SalesMan CRM         */
/* ============================ */
/* (C) 2018 Vladislav Andreev   */
/*       SalesMan Project       */
/*        www.isaler.ru         */
/*        ver. 2018.x           */

/* ============================ */

use Salesman\DealAnketa;

error_reporting(E_ERROR);
header("Pragma: no-cache");

$rootpath = dirname(__DIR__, 2);

include $rootpath."/inc/config.php";
include $rootpath."/inc/dbconnector.php";
include $rootpath."/inc/auth.php";
include $rootpath."/inc/settings.php";
include $rootpath."/inc/func.php";
include $rootpath."/inc/language/".$language.".php";

//require "../../inc/class/DealAnketa.php";

$ida    = (int)$_REQUEST['ida'];
$did    = (int)$_REQUEST['did'];
$action = $_REQUEST['action'];

$anketa = new DealAnketa();

$clid = (int)getDogData($did, 'clid');

$stringTip = [
	'input',
	'inputlist',
	'text',
	'number',
	'datum',
	'datetime'
];

/**
 * редактирование анкеты
 */
if ($action == 'anketa.edit.on') {

	$params = $_REQUEST;

	//print_r($params);
	//exit();

	$result = $anketa -> edit($params);

	print json_encode_cyr([
		"result" => "Готово",
		"ida"    => $params['ida']
	]);

	exit();
}

/**
 * удаление поля
 */
if ($action == 'anketa.delete') {

	$id = $_REQUEST['id'];

	$ida = $anketa -> clear($id);

	print json_encode_cyr([
		"ida"  => $ida,
		"text" => "Профиль обновлен"
	]);

	exit();

}

/**
 * Вывод списка доступных анкет для меню добавления
 */
if ($action == 'anketa.lists') {

	$list = $anketa -> anketalist();

	print json_encode_cyr($list);

	exit();

}

/**
 * Список анкет по сделке
 */
if ($action == 'anketa.list') {

	//анкеты по сделке
	$list = $anketa -> anketadeallist($did);

	//print_r($list);
	//print_r(array_keys($list));

	//все анкеты
	$listall = $anketa -> anketalist();

	//print_r($listall);

	//print json_encode_cyr($list);


	$str = '';
	foreach ($listall as $a) {

		if (!array_key_exists($a['id'], $list) || empty($list)) {
			$str .= '
			<div class="item ha hand w200" data-type="anketa" data-id="'.$a['id'].'">
				<i class="icon-doc-inv-alt blue"></i>&nbsp;'.$a['title'].'
			</div>
			';
		}

	}

	?>
	<div id="anketaMore" class="ftabs" data-id="container">

		<DIV class="batton-edit pt10 pb15 zindex-10">

			<!--Меню добавления анкеты-->
			<div class="relativ <?= ( $str == '' ? 'hidden' : '' ) ?>">

				<a href="javascript:void(0)" title="Добавить анкету" class="tagsmenuToggler"><i class="icon-angle-down broun" id="mapii"></i> Добавить анкету&nbsp;<i class="icon-plus-circled-1 blue"></i></a>

				<div class="tagsmenu toright hidden w2002">

					<div class="items noBold fs-09">
						<?= $str ?>
					</div>

				</div>

			</div>

		</DIV>

		<!--Вкладки с анкетами-->
		<?php
		if (count($list) > 0) {
			?>
			<div id="ytabs">

				<ul class="gray flex-container blue">

					<?php
					foreach ($list as $id => $a) {

						print '<li class="flex-string" data-link="anketa'.$id.'" data-id="'.$id.'">'.$a.'</li>';

					}
					?>

				</ul>

			</div>
			<div id="container" class="fcontainer1 pt10">

				<?php
				foreach ($list as $id => $a) {

					print '

					<div class="anketa'.$id.' cbox">
					
						<DIV id="anketa'.$id.'" class="relativ">
						
							<DIV class="block1 mt10 mb20 text-right">
								<a href="javascript:void(0)" onClick="$anketa.reload(\''.$id.'\')" class="black"><i class="icon-arrows-cw broun"></i>'.$lang['all']['Refresh'].'</a>&nbsp;&nbsp;
								<a href="javascript:void(0)" onClick="$anketa.edit(\''.$id.'\');" class="black"><i class="icon-pencil gred"></i>'.$lang['all']['Edit'].'</a>&nbsp;&nbsp;
								<a href="javascript:void(0)" onClick="$anketa.print(\''.$id.'\',\''.$did.'\');" class="black"><i class="icon-print broun"></i>'.$lang['all']['Print'].'</a>&nbsp;&nbsp;
							</DIV>
							
							<div class="anketa-data mt10" data-id="'.$id.'"></div>
							
						</DIV>

					</div>
					';

				}
				?>

			</div>
			<?php
		}
		else {
			print '<div class="fcontainer mp10">Анкет пока нет</div>';
		}
		?>

	</div>
	<script>

		var did = $('#did').val();

		$(function () {

			$('#tab5').find('.ftabs').each(function () {

				$(this).find('li').removeClass('active');
				$(this).find('li:first-child').addClass('active');

				$(this).find('.cbox').addClass('hidden');
				$(this).find('.cbox:first-child').removeClass('hidden');

			});

			if (ida > 0) $('li[data-link="anketa' + ida + '"]').trigger('click');

		});

		$('div.anketa-data').each(function () {

			var id = $(this).data('id');

			$(this).load('/content/deal.anketa/card.php?action=anketa.anketa&ida=' + id + '&did=' + did);

		});

		$('div[data-type="anketa"]').unbind('click');
		$('div[data-type="anketa"]').bind('click', function () {

			var id = $(this).data('id');

			$anketa.edit(id);

		});

	</script>
	<?php

	exit();

}

/**
 * Вывод конкретной анкеты
 */
if ($action == 'anketa.anketa') {

	$anketa = new DealAnketa();
	$print  = $anketa ::anketaprint($ida, $did);

	print $print;

	exit();

}

/**
 * Просмотр анкеты в модальном окне
 */
if ($action == 'anketa.dialog') {

	$ianketa = $anketa -> anketainfo($ida);
	?>
	<DIV class="zagolovok">Анкета "<?= $ianketa['title'] ?>"</DIV>

	<DIV id="formtabs" style="overflow-y: auto; overflow-x: hidden">

		<?php
		$anketa = new DealAnketa();
		$print  = $anketa -> anketaprint($ida, $did);

		print $print;
		?>

	</DIV>

	<hr>

	<div class="button--pane pull-aright">

		<A href="javascript:void(0)" onClick="DClose()" class="button">Закрыть</A>

	</div>
	<?php
}

/**
 * Распечатка анкеты
 */
if ($action == 'anketa.print') {

	//require_once "../../opensource/Mustache/Autoloader.php";

	//загружаем массив тэгов
	$tags = getNewTag(0, $did);

	//print_r($tags);

	//загружаем шаблон
	$template = file_get_contents($rootpath."/content/deal.anketa/deal.anketa.mustache");

	//готовим анкету
	$tags['anketa'] = DealAnketa ::anketaprint($ida, $did);

	//загружаем шаблонизатор
	Mustache_Autoloader ::register();

	//рендерим шаблон
	$m     = new Mustache_Engine();
	$forma = $m -> render($template, $tags);

	print $forma;

	exit();

}

/**
 * Распечатка базовой анкеты для заполнения на бумаге
 */
if ($action == 'anketa.baseprint') {

	//загружаем массив тэгов
	$tags = getNewTag(0, $did);

	//print_r($tags);

	//загружаем шаблон
	$template = file_get_contents($rootpath."/content/deal.anketa/deal.anketa.mustache");

	//готовим анкету
	$tags['anketa'] = DealAnketa ::anketaform($ida, 0, true);

	$anketa = new DealAnketa();
	$a      = $anketa -> anketainfo($ida);

	$tags['anketaTitle'] = $a['title'];

	//загружаем шаблонизатор
	Mustache_Autoloader ::register();

	//рендерим шаблон
	$m     = new Mustache_Engine();
	$forma = $m -> render($template, $tags);

	print $forma;

	exit();

}

/**
 * Форма редактирования анкеты
 */
if ($action == 'anketa.edit') {

	$ianketa = $anketa -> anketainfo($ida);

	?>
	<DIV class="zagolovok"><B>Анкета "<?= $ianketa['title'] ?>"</B></DIV>

	<FORM action="content/deal.anketa/card.php" method="post" enctype="multipart/form-data" name="Form" id="Form">
		<INPUT type="hidden" name="action" id="action" value="anketa.edit.on">
		<INPUT type="hidden" name="clid" id="clid" value="<?= $clid ?>">
		<INPUT type="hidden" name="did" id="did" value="<?= $did ?>">
		<INPUT type="hidden" name="ida" id="ida" value="<?= $ida ?>">

		<DIV id="formtabs" class="box--child wp100 table--newface" style="max-height:80vh; overflow-y: auto; overflow-x: hidden">

			<div class="viewdiv"><?= $ianketa['content'] ?></div>

			<?php
			$anketa = new DealAnketa();
			$forma  = $anketa -> anketaform($ida, $did);

			print $forma;
			?>

			<div class="space-30"></div>

		</DIV>

		<hr>

		<div class="button--pane pull-aright">

			<A href="javascript:void(0)" onClick="$('#Form').trigger('submit');" class="button">Сохранить</A>&nbsp;
			<A href="javascript:void(0)" onClick="DClose()" class="button">Отмена</A>

		</div>
	</FORM>
	<?php
}
?>
<script>

	var ida = 0;

	var hh = $('#dialog_container').actual('height') * 0.90;
	var hh2 = hh - $('.zagolovok').actual('outerHeight') - $('.button--pane').actual('outerHeight') - 30;

	if ($(window).width() > 990) $('#dialog').css({'width': '800px'});
	else $('#dialog').css('width', '90vw');

	$('#formtabs').css('max-height', hh2);

	$(function () {

		$('#dialog').find('textarea').each(function () {

			$(this).autoHeight(200);

		});

		$('#dialog').center();

	});

	if (!isMobile) {

		$('.inputdate').each(function () {

			$(this).datepicker({
				dateFormat: 'yy-mm-dd',
				numberOfMonths: 2,
				firstDay: 1,
				dayNamesMin: ['Вс', 'Пн', 'Вт', 'Ср', 'Чт', 'Пт', 'Сб'],
				monthNamesShort: ['Январь', 'Февраль', 'Март', 'Апрель', 'Май', 'Июнь', 'Июль', 'Август', 'Сентябрь', 'Октябрь', 'Ноябрь', 'Декабрь'],
				changeMonth: true,
				changeYear: true,
				yearRange: '1940:2030',
				minDate: new Date(1940, 1 - 1, 1),
				showButtonPanel: true,
				currentText: 'Сегодня',
				closeText: 'Готово'
			});

		});
		$('.inputdatetime').each(function () {

			$(this).datetimepicker({
				timeInput: false,
				timeFormat: 'HH:mm',
				oneLine: true,
				showSecond: false,
				showMillisec: false,
				showButtonPanel: true,
				timeOnlyTitle: 'Выберите время',
				timeText: 'Время',
				hourText: 'Часы',
				minuteText: 'Минуты',
				secondText: 'Секунды',
				millisecText: 'Миллисекунды',
				timezoneText: 'Часовой пояс',
				currentText: 'Текущее',
				closeText: '<i class="icon-ok-circled"></i>',
				dateFormat: 'yy-mm-dd',
				firstDay: 1,
				dayNamesMin: ['Вс', 'Пн', 'Вт', 'Ср', 'Чт', 'Пт', 'Сб'],
				monthNamesShort: ['Январь', 'Февраль', 'Март', 'Апрель', 'Май', 'Июнь', 'Июль', 'Август', 'Сентябрь', 'Октябрь', 'Ноябрь', 'Декабрь'],
				changeMonth: true,
				changeYear: true,
				yearRange: '1940:2030',
				minDate: new Date(1940, 1 - 1, 1)
			});

		});

	}
	else {

		$('input.inputdate').each(function () {
			this.setAttribute('type', 'date');
		});
		$('input.inputdatetime').each(function () {
			this.setAttribute('type', 'datetime-local');
		});

	}

	$('#Form').ajaxForm({
		dataType: 'json',
		async: false,
		beforeSubmit: function () {

			var $out = $('#message');
			var em = checkRequired();

			$('input[data-var="new"]').each(function () {

				var $v = $(this).val();

				if ($v === '') $(this).remove();

			});

			if (em === false) return false;

			$('#dialog').css('display', 'none');
			$('#dialog_container').css('display', 'none');

			$out.empty().fadeTo(1, 1).css('display', 'block').append('<div id="loader"><img src="/assets/images/loader.gif"> Загрузка данных. Пожалуйста подождите...</div>');

			return true;

		},
		success: function (data) {

			$('#dialog').css('display', 'none');
			$('#resultdiv').empty();
			$('#dialog_container').css('display', 'none');
			$('#dialog').css('width', '500px');

			ida = data.ida;

			if (isCard) settab('5');

			$('#message').fadeTo(1, 1).css('display', 'block').html(data.result);
			setTimeout(function () {
				$('#message').fadeTo(1000, 0);
			}, 20000);

		}
	});

</script>