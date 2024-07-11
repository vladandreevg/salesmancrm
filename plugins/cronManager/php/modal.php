<?php
/* ============================ */
/*         SalesMan CRM         */
/* ============================ */
/* (C) 2019 Vladislav Andreev   */
/*       SalesMan Project       */
/*        www.isaler.ru         */
/*        ver. 2019.x           */
/* ============================ */

$rootpath = dirname( __DIR__, 3 );
$ypath    = $rootpath."/plugins/cronManager";

error_reporting( E_ERROR );

require_once $rootpath."/inc/config.php";
require_once $rootpath."/inc/dbconnector.php";
require_once $rootpath."/inc/auth_main.php";
require_once $rootpath."/inc/settings.php";
require_once $rootpath."/inc/func.php";

require_once $ypath."/php/autoload.php";
require_once $ypath."/vendor/autoload.php";

$action = $_REQUEST[ 'action' ];

$cron = new Cronman\Cronman();

$api_key = $db -> getOne( "SELECT api_key FROM ".$sqlname."settings WHERE id = '$identity'" );

$xphp = getPhpInfo();
$xbin = $xphp['bin'];

if(PHP_OS_FAMILY != 'Linux'){
	$xbin = 'php';
}

$scripts = [
	"totalCleaner"                => [
		"name"   => "Тотальная очистка [ PHP ]",
		"bin"    => $xbin,
		"script" => "{{DIR}}/cron/cronTotalCleaner.php",
		"parent" => "everyweek",
		"period" => [
			"i" => 0,
			"h" => 3,
			"w" => ["saturday"]
		]
	],
	"backup"                => [
		"name"   => "Бэкап БД [ PHP ]",
		"bin"    => $xbin,
		"script" => "{{DIR}}/cron/backup.php",
		"parent" => "everyday",
		"period" => [
			"i" => 0,
			"h" => 2
		]
	],
	"backup-wget"           => [
		"name"   => "Бэкап БД [ WGET ]",
		"bin"    => "/usr/bin/wget",
		"script" => "-O - -q -t 1 --no-check-certificate ".( ( isset( $_SERVER[ 'HTTPS' ] ) && $_SERVER[ 'HTTPS' ] != 'off' ) || 443 == $_SERVER[ 'SERVER_PORT' ] ? 'https://' : 'http://' ).$_SERVER[ 'HTTP_HOST' ]."/cron/backup.php",
		"parent" => "everyday",
		"period" => [
			"i" => 0,
			"h" => 2
		]
	],
	"tasksToday"            => [
		"name"   => "Дела на день [ PHP ]",
		"bin"    => $xbin,
		"script" => "{{DIR}}/cron/tasksToday.php",
		"parent" => "everyday",
		"period" => [
			"i" => 0,
			"h" => 8
		]
	],
	"tasksToday-wget"       => [
		"name"   => "Дела на день [ WGET ]",
		"bin"    => "/usr/bin/wget",
		"script" => "-O - -q -t 1 --no-check-certificate ".( ( isset( $_SERVER[ 'HTTPS' ] ) && $_SERVER[ 'HTTPS' ] != 'off' ) || 443 == $_SERVER[ 'SERVER_PORT' ] ? 'https://' : 'http://' ).$_SERVER[ 'HTTP_HOST' ]."/cron/backup.php",
		"parent" => "everyday",
		"period" => [
			"i" => 0,
			"h" => 8
		]
	],
	"cronYmailChecker"      => [
		"name"   => "Проверка почты сотрудников [ PHP ]",
		"bin"    => $xbin,
		"script" => "{{DIR}}/cron/cronMailerChecker.php",
		"parent" => "everyminutes",
		"period" => [
			"i" => 5
		]
	],
	"cronYmailChecker-wget" => [
		"name"   => "Проверка почты сотрудников [ WGET ]",
		"bin"    => "/usr/bin/wget",
		"script" => "-O - -q -t 1 --no-check-certificate ".( ( isset( $_SERVER[ 'HTTPS' ] ) && $_SERVER[ 'HTTPS' ] != 'off' ) || 443 == $_SERVER[ 'SERVER_PORT' ] ? 'https://' : 'http://' ).$_SERVER[ 'HTTP_HOST' ]."/cron/cronMailerChecker.php",
		"parent" => "everyminutes",
		"period" => [
			"i" => 5
		]
	],
	"cronLeadsChecker"      => [
		"name"   => "Сборщик заявок [ PHP ]",
		"bin"    => $xbin,
		"script" => "{{DIR}}/cron/cronLeadsChecker.php ".$_SERVER[ 'HTTP_HOST' ]." ".$_SERVER[ 'SERVER_PORT' ]." ".( ( isset( $_SERVER[ 'HTTPS' ] ) && $_SERVER[ 'HTTPS' ] != 'off' ) || 443 == $_SERVER[ 'SERVER_PORT' ] ? 'on' : 'off' ),
		"parent" => "everyminutes",
		"period" => [
			"i" => 5
		]
	],
	"cronLeadsChecker-wget" => [
		"name"   => "Сборщик заявок [ WGET ]",
		"bin"    => "/usr/bin/wget",
		"script" => "-O - -q -t 1 --no-check-certificate ".( ( isset( $_SERVER[ 'HTTPS' ] ) && $_SERVER[ 'HTTPS' ] != 'off' ) || 443 == $_SERVER[ 'SERVER_PORT' ] ? 'https://' : 'http://' ).$_SERVER[ 'HTTP_HOST' ]."/cron/cronLeadsChecker.php",
		"parent" => "everyminutes",
		"period" => [
			"i" => 5
		]
	],
	"cdr-asterisk"          => [
		"name"   => "История звонков [ Asterisk ]",
		"bin"    => "/usr/bin/wget",
		"script" => "-O - -q -t 1 --no-check-certificate ".( ( isset( $_SERVER[ 'HTTPS' ] ) && $_SERVER[ 'HTTPS' ] != 'off' ) || 443 == $_SERVER[ 'SERVER_PORT' ] ? 'https://' : 'http://' ).$_SERVER[ 'HTTP_HOST' ]."/content/pbx/asterisk/cdr.php?apkey=".$api_key."&hours=1",
		"parent" => "everyminutes",
		"period" => [
			"i" => 5
		]
	],
	"cdr-gravitel"          => [
		"name"   => "История звонков [ Gravitel PBX ]",
		"bin"    => "/usr/bin/wget",
		"script" => "-O - -q -t 1 --no-check-certificate ".( ( isset( $_SERVER[ 'HTTPS' ] ) && $_SERVER[ 'HTTPS' ] != 'off' ) || 443 == $_SERVER[ 'SERVER_PORT' ] ? 'https://' : 'http://' ).$_SERVER[ 'HTTP_HOST' ]."/content/pbx/gravitel/cdr.php?apkey=".$api_key."&hours=1",
		"parent" => "everyminutes",
		"period" => [
			"i" => 5
		]
	],
	"cdr-mango"             => [
		"name"   => "История звонков [ Mango PBX ]",
		"bin"    => "/usr/bin/wget",
		"script" => "-O - -q -t 1 --no-check-certificate ".( ( isset( $_SERVER[ 'HTTPS' ] ) && $_SERVER[ 'HTTPS' ] != 'off' ) || 443 == $_SERVER[ 'SERVER_PORT' ] ? 'https://' : 'http://' ).$_SERVER[ 'HTTP_HOST' ]."/content/pbx/mango/cdr.php?apkey=".$api_key."&hours=1",
		"parent" => "everyminutes",
		"period" => [
			"i" => 5
		]
	],
	"cdr-onlinepbx"         => [
		"name"   => "История звонков [ OnlinePBX ]",
		"bin"    => "/usr/bin/wget",
		"script" => "-O - -q -t 1 --no-check-certificate ".( ( isset( $_SERVER[ 'HTTPS' ] ) && $_SERVER[ 'HTTPS' ] != 'off' ) || 443 == $_SERVER[ 'SERVER_PORT' ] ? 'https://' : 'http://' ).$_SERVER[ 'HTTP_HOST' ]."/content/pbx/onlinepbx/cdr.php?apkey=".$api_key."&hours=1",
		"parent" => "everyminutes",
		"period" => [
			"i" => 5
		]
	],
	"cdr-rostelecom"        => [
		"name"   => "История звонков [ OnlinePBX ]",
		"bin"    => "/usr/bin/wget",
		"script" => "-O - -q -t 1 --no-check-certificate ".( ( isset( $_SERVER[ 'HTTPS' ] ) && $_SERVER[ 'HTTPS' ] != 'off' ) || 443 == $_SERVER[ 'SERVER_PORT' ] ? 'https://' : 'http://' ).$_SERVER[ 'HTTP_HOST' ]."/content/pbx/rostelecom/cdr.php?apkey=".$api_key."&hours=1",
		"parent" => "everyminutes",
		"period" => [
			"i" => 5
		]
	],
	"cdr-telfin"            => [
		"name"   => "История звонков [ Telfin PBX ]",
		"bin"    => "/usr/bin/wget",
		"script" => "-O - -q -t 1 --no-check-certificate ".( ( isset( $_SERVER[ 'HTTPS' ] ) && $_SERVER[ 'HTTPS' ] != 'off' ) || 443 == $_SERVER[ 'SERVER_PORT' ] ? 'https://' : 'http://' ).$_SERVER[ 'HTTP_HOST' ]."/content/pbx/telfin/cdr.php?apkey=".$api_key."&hours=1",
		"parent" => "everyminutes",
		"period" => [
			"i" => 5
		]
	],
	"cdr-yandextel"         => [
		"name"   => "История звонков [ Yandex PBX ]",
		"bin"    => "/usr/bin/wget",
		"script" => "-O - -q -t 1 --no-check-certificate ".( ( isset( $_SERVER[ 'HTTPS' ] ) && $_SERVER[ 'HTTPS' ] != 'off' ) || 443 == $_SERVER[ 'SERVER_PORT' ] ? 'https://' : 'http://' ).$_SERVER[ 'HTTP_HOST' ]."/content/pbx/yandextel/cdr.php?apkey=".$api_key."&hours=1",
		"parent" => "everyminutes",
		"period" => [
			"i" => 5
		]
	],
	"cdr-zadarma"           => [
		"name"   => "История звонков [ Zadarma PBX ]",
		"bin"    => "/usr/bin/wget",
		"script" => "-O - -q -t 1 --no-check-certificate ".( ( isset( $_SERVER[ 'HTTPS' ] ) && $_SERVER[ 'HTTPS' ] != 'off' ) || 443 == $_SERVER[ 'SERVER_PORT' ] ? 'https://' : 'http://' ).$_SERVER[ 'HTTP_HOST' ]."/content/pbx/zadarma/cdr.php?apkey=".$api_key."&hours=1",
		"parent" => "everyminutes",
		"period" => [
			"i" => 5
		]
	],
	"cronDealUnFreeze"      => [
		"name"   => "Разморозка сделок [ PHP ]",
		"bin"    => $xbin,
		"script" => "{{DIR}}/cron/cronDealUnFreeze.php",
		"parent" => "everyday",
		"period" => [
			"i" => 0,
			"h" => 9,
		]
	],
];

$plugins = $db -> getCol( "SELECT name FROM ".$sqlname."plugins WHERE active = 'on' AND identity = '$identity'" );

if(in_array('backup2yadisk', $plugins))
	$scripts['yd'] = [
		"name"   => "Бэкап на Яндекс Диск [ PHP ]",
		"bin"    => $xbin,
		"script" => "{{DIR}}/plugins/backup2yadisk/app.php yes",
		"parent" => "everyhour",
		"period" => [
			"h" => 1
		]
	];

// настройка логики
if ( $action == "settings.save" ) {

	$params = $_REQUEST;

	unset( $params[ 'action' ] );

	customSettings( 'cronManager', 'put', ["params" => $params] );

	print json_encode_cyr( [
		"status"  => "ok",
		"message" => "Сохранено"
	] );

	exit();

}

//настройки бота
if ( $action == 'task.get' ) {

	$list = $cron -> getTaskList();

	print json_encode_cyr( ["list" => !empty( $list ) ? $list : NULL] );

	exit();

}
if ( $action == 'task.delete' ) {

	$id = $_REQUEST[ 'id' ];

	$mes = $cron -> deleteTask( $id );

	print $mes['message'];

	exit();

}

if ( $action == 'log.get' ) {

	$id = $_REQUEST[ 'id' ];
	$list = $cron -> getLog( $id );

	print json_encode_cyr( ["list" => $list] );

	exit();

}
if ( $action == "log.info" ) {

	$id  = $_REQUEST[ 'id' ];
	$res = $cron -> getTask( $id );

	//print_r( $res );

	?>
	<DIV class="zagolovok"><B>Информация</B></DIV>
	<form action="php/modal.php" method="post" enctype="multipart/form-data" name="Form" id="Form">
		<input name="id" type="hidden" id="id" value="<?= $id ?>">

		<div id="formtabs" style="overflow-y: auto; overflow-x: hidden" class="p5">

			<div class="logrezult pad10"></div>

		</div>

		<hr>

		<div class="button--pane">

			<div class="pull-aright">

				<A href="javascript:void(0)" onClick="new DClose()" class="button">Закрыть</A>

			</div>

		</div>

	</form>

	<!--шаблон блока для валюты-->
	<div id="logTpl" type="x-tmpl-mustache" class="hidden">
		{{#list}}
		<div class="flex-container float border-bottom">
			<div class="flex-string w120 p10">
				<div class="fs-11 Bold">{{{diff}}} назад</div>
				<div class="fs-09 blue">{{datumru}}</div>
			</div>
			<div class="flex-string float p10">{{{response}}}</div>
		</div>
		{{/list}}
		{{^list}}
		<div class="warning m0 mt5 dotted">Записей нет. Видимо задание еще не выполнялось.</div>
		{{/list}}
	</div>

	<script>

		var hh = $('#dialog_container').actual('height') * 0.8;
		var hh2 = hh - $('.zagolovok').actual('outerHeight') - $('.button--pane').actual('outerHeight');

		if (!isMobile) {

			$('#dialog').css({'width': '600px'});
			$('#formtabs').css({'max-height': hh2 + 'px'});

		}
		else {

			var h2 = $(window).height() - $('.zagolovok').actual('outerHeight') - $('.button--pane').actual('outerHeight') - 120;

			$('#dialog').css({'width': '100vw'});
			$('#formtabs').css({'max-height': h2 + 'px', 'height': h2 + 'px'});

		}

		$('.logrezult').append('<div id="loader"><img src="/assets/images/loading.gif"> Загрузка данных...</div>');

		var id = $('#id').val();
		var template = $('#logTpl').html();

		//console.log(template);

		Mustache.parse(template);

		$.getJSON('php/modal.php?action=log.get&id=' + id, function (data) {

			var rendered = Mustache.render(template, data);

			//console.log( rendered );

			$('.logrezult').html(rendered);

		})
			.done(function(){

				$('#dialog').center();

			});

	</script>
	<?php

	exit();
}

if ( $action == 'task.edit.do' ) {

	$id = $_REQUEST[ 'id' ];

	$data = $_REQUEST;

	$result = $cron -> setTask( $id, $data );

	if ( $result > 0 ) {
		$result = [
			"status"  => 'ok',
			"message" => 'Сохранено'
		];
	}
	else {
		$result = [
			"status"  => 'error',
			"message" => 'Возникла какая-то ошибка'
		];
	}

	print json_encode_cyr( $result );

	exit();

}
if ( $action == "task.edit.form" ) {

	$id   = $_REQUEST[ 'id' ] + 0;
	$task = [];

	if ( $id > 0 ) {
		$task = $cron -> getTask($id);
	}
	else {

		$task[ 'parent' ] = 'everyminutes';

	}

	//print_r($task)

	?>
	<DIV class="zagolovok"><B>Редактировать задание</B></DIV>
	<form action="php/modal.php" method="post" enctype="multipart/form-data" name="Form" id="Form">
		<input type="hidden" id="action" name="action" value="task.edit.do">
		<input name="id" type="hidden" id="id" value="<?= $id ?>">

		<div id="formtabs" class="p5" style="max-height: 70vh; overflow-y: auto; overflow-x:hidden;">

			<div class="divider">Стандартные задания</div>

			<div class="row">

				<div class="column12 grid-12">

					<span class="label">Системные скрипты</span>
					<select id="scripts" class="wp100">
						<option value="" class="redbg-sub">-- не использовать --</option>
						<?php
						foreach ( $scripts as $script => $params ) {

							print '<option value="'.$script.'">'.$params[ 'name' ].'</option>';

						}
						?>
					</select>

				</div>

			</div>

			<div class="divider">Установка задания</div>

			<div class="row">

				<div class="column12 grid-12">

					<span class="label">Название</span>
					<input type="text" name="name" id="name" class="wp100 required" value="<?= $task[ 'name' ] ?>">

				</div>

			</div>

			<div class="row" data-field="noexpert">

				<div class="column12 grid-12">

					<span class="label">Исполняемая программа</span>
					<input type="text" name="bin" id="bin" class="wp100 required" value="<?= $task[ 'bin' ] ?>" list="bins">
					<datalist id="bins">
						<option value="/usr/bin/wget">/usr/bin/wget</option>
						<option value="/usr/bin/curl">/usr/bin/curl</option>
						<option value="php">php (версия по-умолчанию)</option>
						<option value="/opt/php72/bin/php">/opt/php72/bin/php</option>
						<option value="/opt/php72/sbin/php-fpm">/opt/php72/sbin/php-fpm</option>
						<option value="<?=$xbin?>"><?=$xbin?></option>
					</datalist>

				</div>

			</div>

			<div class="row" data-field="noexpert">

				<div class="column12 grid-12">

					<span class="label">Исполняемая команда</span>
					<textarea name="script" id="script" class="wp100 required"><?= $task[ 'script' ] ?></textarea>

				</div>

			</div>

			<div class="divider">Периодичность</div>

			<div class="row">

				<div class="column12 grid-12">

					<span class="label">Выполнять</span>
					<select name="parent" id="parent" class="wp100" data-value="<?= $task[ 'parent' ] ?>">
						<option value="once">Разово</option>
						<?php
						foreach ( $cron::PERIODS as $key => $value ) {

							print '<option value="'.$key.'">'.$value.'</option>';

						}
						?>
					</select>

				</div>

			</div>

			<div class="detales p5 box--child">

				<div class="detale" data-period="minute">

					<span class="label">Минуты</span>
					<input type="number" name="period[i]" id="period[i]" class="wp100" value="<?= $task[ 'period' ][ 'i' ] ?>" step="5" max="59" min="0">

				</div>
				<div class="detale" data-period="hour">

					<span class="label">Часы</span>
					<input type="number" name="period[h]" id="period[h]" class="wp100" value="<?= $task[ 'period' ][ 'h' ] ?>" step="1" max="23" min="0">

				</div>
				<div class="detale" data-period="day">

					<span class="label">Число</span>
					<input type="number" name="period[d]" id="period[d]" class="wp100" value="<?= $task[ 'period' ][ 'd' ] ?>" step="1" max="31" min="1">

				</div>
				<div class="detale" data-period="week">

					<span class="label">День недели</span>

					<div class="weeks">
						<div class="checkbox like-input">
							<label class="mt5 noBold">
								<input name="period[w][]" type="checkbox" id="period[w][]" value="monday" <?php echo( in_array( "monday", (array)$task[ 'period' ][ 'w' ] ) ? "checked" : "" ) ?>>
								<span class="custom-checkbox"><i class="icon-ok"></i></span>
								Пн
							</label>
						</div>
						<div class="checkbox like-input">
							<label class="mt5 noBold">
								<input name="period[w][]" type="checkbox" id="period[w][]" value="tuesday" <?php echo( in_array( "tuesday", (array)$task[ 'period' ][ 'w' ] ) ? "checked" : "" ) ?>>
								<span class="custom-checkbox"><i class="icon-ok"></i></span>
								Вт
							</label>
						</div>
						<div class="checkbox like-input">
							<label class="mt5 noBold">
								<input name="period[w][]" type="checkbox" id="period[w][]" value="wednesday" <?php echo( in_array( "wednesday", (array)$task[ 'period' ][ 'w' ] ) ? "checked" : "" ) ?>>
								<span class="custom-checkbox"><i class="icon-ok"></i></span>
								Ср
							</label>
						</div>
						<div class="checkbox like-input">
							<label class="mt5 noBold">
								<input name="period[w][]" type="checkbox" id="period[w][]" value="thursday" <?php echo( in_array( "thursday", (array)$task[ 'period' ][ 'w' ] ) ? "checked" : "" ) ?>>
								<span class="custom-checkbox"><i class="icon-ok"></i></span>
								Чт
							</label>
						</div>
						<div class="checkbox like-input">
							<label class="mt5 noBold">
								<input name="period[w][]" type="checkbox" id="period[w][]" value="friday" <?php echo( in_array( "friday", (array)$task[ 'period' ][ 'w' ] ) ? "checked" : "" ) ?>>
								<span class="custom-checkbox"><i class="icon-ok"></i></span>
								Пт
							</label>
						</div>
						<div class="checkbox like-input">
							<label class="mt5 noBold">
								<input name="period[w][]" type="checkbox" id="period[w][]" value="saturday" <?php echo( in_array( "saturday", (array)$task[ 'period' ][ 'w' ] ) ? "checked" : "" ) ?>>
								<span class="custom-checkbox"><i class="icon-ok"></i></span>
								Сб
							</label>
						</div>
						<div class="checkbox like-input">
							<label class="mt5 noBold">
								<input name="period[w][]" type="checkbox" id="period[w][]" value="sunday" <?php echo( in_array( "sunday", (array)$task[ 'period' ][ 'w' ] ) ? "checked" : "" ) ?>>
								<span class="custom-checkbox"><i class="icon-ok"></i></span>
								Вс
							</label>
						</div>
					</div>

				</div>
				<div class="detale" data-period="month">

					<span class="label">Месяц</span>
					<input type="number" name="period[m]" id="period[m]" class="wp100" value="<?= $task[ 'period' ][ 'm' ] ?>" step="1" max="12" min="1">

				</div>
				<div class="detale" data-period="expert">

					<span class="label">Строка периодичности</span>
					<input type="text" name="period[cmd]" id="period[cmd]" class="wp100" value="<?= $task[ 'period' ][ 'cmd' ] ?>">

				</div>

			</div>

			<div class="space-20"></div>

		</div>

		<hr>

		<div class="button--pane">

			<div class="pt10 pull-left pl10">

				<div class="checkbox">
					<label class="">
						<input name="active" type="checkbox" id="active" value="on" <?php echo( $task[ 'active' ] == 'on' ? "checked" : "" ) ?>>
						<span class="custom-checkbox"><i class="icon-ok"></i></span>
						Активен
					</label>
				</div>

			</div>

			<div class="pull-aright">

				<A href="javascript:void(0)" onclick="saveForm()" class="button">Сохранить</A>&nbsp;
				<A href="javascript:void(0)" onclick="new DClose()" class="button">Отмена</A>

			</div>

		</div>

	</form>

	<script>

		var $scripts = JSON.parse('<?php echo json_encode_cyr( $scripts );?>');

		var hh = $('#dialog_container').actual('height') * 0.8;
		var hh2 = hh - $('.zagolovok').actual('outerHeight') - $('.button--pane').actual('outerHeight');

		if (!isMobile) {

			$('#dialog').css({'width': '600px'});
			$('#formtabs').css({'max-height': hh2 + 'px'});

		}
		else {

			var h2 = $(window).height() - $('.zagolovok').actual('outerHeight') - $('.button--pane').actual('outerHeight') - 80;

			$('#dialog').css({'width': '100vw'});
			$('#formtabs').css({'max-height': h2 + 'px', 'height': h2 + 'px'});

		}

		//console.log($scripts);

		$(function () {

			$('#parent').val($('#parent').data('value')).trigger('change');

			$('#bin').flexdatalist({
				//data: people,
				minLength: 0,
				cache: false,
				maxShownResults: 100,
				noResultsText: 'Не найдено для "{keyword}"'
			});

			$('#dialog').center();

		});

		$(document).off('change', '#scripts');
		$(document).on('change', '#scripts', function () {

			let script = $(this).val();

			$('.detale').addClass('hidden');

			if (script !== '') {

				//console.log($scripts[script]['period']['w'].length);

				$('#name').val($scripts[script]['name']);
				$('#bin').val($scripts[script]['bin']);
				$('#script').val($scripts[script]['script']);
				$('#parent').val($scripts[script]['parent']).trigger('change');

				$('#period\\[i\\]').val($scripts[script]['period']['i']);
				$('#period\\[h\\]').val($scripts[script]['period']['h']);
				$('#period\\[d\\]').val($scripts[script]['period']['d']);

				if($scripts[script]['period']['w'].length > 0) {

					for(var i in $scripts[script]['period']['w']) {

						$('input[value="'+ $scripts[script]['period']['w'][i] +'"]').prop('checked', true);

					}

				}

			}
			else {

				$('#name').val('');
				$('#bin').val('');
				$('#script').val('');
				$('#parent').val('everyminutes').trigger('change');

				$('#period\\[i\\]').val('');
				$('#period\\[h\\]').val('');
				$('#period\\[d\\]').val('');

			}

			$('#dialog').center();

		});

		$(document).off('change', '#parent');
		$(document).on('change', '#parent', function () {

			let period = $(this).val();

			$('.detale').addClass('hidden');
			//$('#bin').addClass('required');
			//$('#script').addClass('required');

			$('.row[data-field="noexpert"]').removeClass('hidden');

			switch (period) {
				case "everyminutes":

					$('.detale[data-period="minute"]').removeClass('hidden');

					break;
				case "everyhour":

					$('.detale[data-period="hour"]').removeClass('hidden');

					break;
				case "everyday":

					$('.detale[data-period="minute"]').removeClass('hidden');
					$('.detale[data-period="hour"]').removeClass('hidden');

					break;
				case "everyweek":

					$('.detale[data-period="minute"]').removeClass('hidden');
					$('.detale[data-period="hour"]').removeClass('hidden');
					$('.detale[data-period="week"]').removeClass('hidden');

					break;
				case "everymonth":

					$('.detale[data-period="minute"]').removeClass('hidden');
					$('.detale[data-period="hour"]').removeClass('hidden');
					$('.detale[data-period="day"]').removeClass('hidden');

					break;
				case "everyyear":

					$('.detale[data-period="minute"]').removeClass('hidden');
					$('.detale[data-period="hour"]').removeClass('hidden');
					$('.detale[data-period="day"]').removeClass('hidden');
					$('.detale[data-period="month"]').removeClass('hidden');

					break;
				case "expert":

					//$('.row[data-field="noexpert"]').addClass('hidden');

					//$('#bin').removeClass('required');
					//$('#script').removeClass('required');

					$('.detale[data-period="expert"]').removeClass('hidden');

					break;
			}

			$('#dialog').center();

		});

		function saveForm() {

			var str = $('#Form').serialize();
			var url = $('#Form').attr("action");

			$('#dialog_container').css('display', 'none');

			$.post(url, str, function (data) {

				if (data.status === 'ok') {

					Swal.fire({
						imageUrl: '/assets/images/success.svg',
						imageWidth: 50,
						imageHeight: 50,
						//position: 'bottom-end',
						html: '' + data.message + '',
						icon: 'info',
						showConfirmButton: false,
						timer: 3500
					});

				}
				else {

					Swal.fire({
						imageUrl: '/assets/images/error.svg',
						imageWidth: 50,
						imageHeight: 50,
						html: '' + data.message + '',
						icon: 'info',
						showConfirmButton: false,
						timer: 3500
					});

				}

				$app.loadTasks();

				new DClose();

			}, 'json');
		}

	</script>
	<?php

	exit();

}