<?php
/* ============================ */
/* (C) 2016 Vladislav Andreev   */
/*      SalesMan Project        */
/*        www.isaler.ru         */
/*         ver. 2016.20         */
/* ============================ */

set_time_limit(0);

error_reporting(E_ERROR);

$rootpath = realpath( __DIR__.'/../../' );
$ypath    = $rootpath."/plugins/saleScript/";

include $rootpath."/inc/config.php";
include $rootpath."/inc/dbconnector.php";
include $rootpath."/inc/auth.php";
include $rootpath."/inc/func.php";
include $rootpath."/inc/settings.php";

$action = $_REQUEST['action'];

$identity = $GLOBALS['identity'];
$iduser1  = $GLOBALS['iduser1'];

$fpath = '';

if ($isCloud == true) {

	//создаем папки хранения файлов
	if (!file_exists("data/".$identity)) {

		mkdir("data/".$identity, 0777);
		chmod("data/".$identity, 0777);

	}

	$fpath = $identity.'/';

}

//загружаем настройки доступа
$file = $ypath.'data/'.$fpath.'settings.json';

//если настройки произведены, то загружаем их
if (file_exists($file) && $action != 'settings.do') {

	$settings = json_decode(file_get_contents($file), true);
	$access   = $settings['access'];
	$forms    = $settings['forms'];
	$forusers = $settings['forusers'];

}

if (empty($access))
	$access = $db -> getCol("SELECT iduser FROM {$sqlname}user WHERE isadmin = 'on' and secrty = 'yes' and identity = '$identity' ORDER BY title");

if (empty($forms))
	$forms = [
		"editEntry",
		"expressClient",
		"workitLead"
	];

//настройки подключения к сервису
if ($action == 'settings.do') {

	$login    = $_REQUEST['UserID'];
	$password = $_REQUEST['APIkey'];
	$scripts  = $_REQUEST['script'];
	$preusers = $_REQUEST['preusers'];
	$forusers = $_REQUEST['forusers'];
	$forms    = $_REQUEST['forms'];

	$list = [];

	foreach ($scripts as $script) {

		$s = yexplode(":", $script);

		$list[] = [
			"id"   => $s[0],
			"name" => $s[1]
		];

	}

	$params = json_encode_cyr([
		"UserID"   => $login,
		"APIkey"   => $password,
		"scripts"  => $list,
		"access"   => $preusers,
		"forusers" => $forusers,
		"forms"    => $forms
	]);

	$f    = $ypath.'data/'.$fpath.'settings.json';
	$file = fopen($f, "w");

	if (!$file)
		$rez = 'Не могу открыть файл';

	else
		$rez = (fputs($file, $params) === false) ? 'Ошибка записи' : 'Записано';

	fclose($file);

	print $rez;

	exit();

}
if ($action == "settings") {

	?>
	<DIV class="zagolovok"><B>Настройка плагина</B></DIV>
	<form action="index.php" method="post" enctype="multipart/form-data" name="Form" id="Form">
		<input type="hidden" id="action" name="action" value="settings.do">

		<div id="formtabs" style="overflow-y: auto; overflow-x: hidden; max-height: 80vh" class="p5">

			<div class="divider mt10 mb10">Подключение к HyperScript</div>

			<div class="flex-container box--child pl10 pr10">

				<div class="flex-string wp100 fs-07 uppercase Bold blue">User ID</div>
				<div class="flex-string wp100">
					<input type="text" id="UserID" name="UserID" value="<?= $settings['UserID'] ?>" class="wp100">
				</div>

			</div>

			<div class="flex-container box--child pl10 pr10 mt5">

				<div class="flex-string wp100 fs-07 uppercase Bold blue">API key</div>
				<div class="flex-string wp100">
					<input type="text" id="APIkey" name="APIkey" value="<?= $settings['APIkey'] ?>" class="wp100">
				</div>

			</div>

			<div class="flex-container box--child pl10 pr10 smallbtn">

				<a href="javascript:void(0)" onclick="getHSlogin()" class="button" title="">Получить ключ</a>
				<a href="javascript:void(0)" onclick="getHSF()" class="button greenbtn" title="">Получить список</a>

			</div>

			<div class="flex-container box--child pl10 pr10">

				<div class="flex-string wp100 hidden viewdiv" id="hss"></div>
				<div class="flex-string wp100 hidden viewdiv" id="hsf">

					<div class="divider mb20"><b class="blue">Пройдите авторизацию</b></div>

					<iframe id="hyperscript" name="hsiframe" src="https://hyper-script.ru/integration/test_api_key" frameborder="0" width="100%" height="200px"></iframe>

				</div>

			</div>


			<div class="divider mt10 mb10">Формы для плагина</div>

			<div class="flex-container box--child pl10 pr10">

				<div class="flex-string wp100 infodiv mb20">Укажите формы, при вызове которых будет срабатывать плагин</div>

				<label class="flex-container wp100 box--child mb10">

					<div class="flex-string wp5">

						<div class="checkbox">
							<label class="">
								<input name="forms[]" type="checkbox" id="forms[]" value="expressClient" <?php if (in_array('expressClient', $forms)) print 'checked'; ?>>
								<span class="custom-checkbox"><i class="icon-ok"></i></span>
							</label>
						</div>

					</div>
					<div class="flex-string wp95 pl10">Экспресс-форма</div>

				</label>
				<label class="flex-container wp100 box--child mb10">

					<div class="flex-string wp5">

						<div class="checkbox">
							<label class="">
								<input name="forms[]" type="checkbox" id="forms[]" value="editEntry" <?php if (in_array('editEntry', $forms)) print 'checked'; ?>>
								<span class="custom-checkbox"><i class="icon-ok"></i></span>
							</label>
						</div>

					</div>
					<div class="flex-string wp95 pl10">Форма Обращения</div>

				</label>
				<label class="flex-container wp100 box--child mb10">

					<div class="flex-string wp5">

						<div class="checkbox">
							<label class="">
								<input name="forms[]" type="checkbox" id="forms[]" value="workitLead" <?php if (in_array('workitLead', $forms)) print 'checked'; ?>>
								<span class="custom-checkbox"><i class="icon-ok"></i></span>
							</label>
						</div>

					</div>
					<div class="flex-string wp95 pl10">Форма обработки Заявки</div>

				</label>

			</div>


			<div class="divider mt10 mb20">Срабатывать для пользователей</div>

			<div class="infodiv mb20">Только для этих пользователей будет показан блок со скриптом разговора</div>

			<div class="flex-container box--child pl10 pr10">

				<?php
				$da = $db -> getAll("SELECT * FROM {$sqlname}user WHERE secrty = 'yes' and identity = '$identity' ORDER BY title");
				foreach ($da as $data) {

					print
						'<label class="flex-string flx-basis-50 mb10 hand">

						<div class="flex-container">

							<div class="flex-string wp10">

								<div class="checkbox">
									<label class="">
										<input name="forusers[]" type="checkbox" id="forusers[]" value="'.$data['iduser'].'" '.(in_array($data['iduser'], $forusers) ? 'checked' : '').'>
										<span class="custom-checkbox"><i class="icon-ok"></i></span>
									</label>
								</div>

							</div>
							<div class="flex-string wp90 '.($data['isadmin'] == 'on' ? 'Bold' : '').'">
								'.$data['title'].' '.($data['isadmin'] == 'on' ? '<i class="icon-star orange"></i>' : '').'
							</div>

						</div>

					</label>';

				}
				?>

			</div>


			<div class="divider mt10 mb20">Доступы пользователей к настройкам плагина</div>

			<div class="flex-container box--child pl10 pr10">

				<?php
				$da = $db -> getAll("SELECT * FROM {$sqlname}user WHERE secrty = 'yes' and identity = '$identity' ORDER BY title");
				foreach ($da as $data) {

					print
						'<label class="flex-string flx-basis-50 mb10 hand">

						<div class="flex-container">

							<div class="flex-string wp10">

								<div class="checkbox">
									<label class="">
										<input name="preusers[]" type="checkbox" id="preusers[]" value="'.$data['iduser'].'" '.(in_array($data['iduser'], $access) ? 'checked' : '').'>
										<span class="custom-checkbox"><i class="icon-ok"></i></span>
									</label>
								</div>

							</div>
							<div class="flex-string wp90 '.($data['isadmin'] == 'on' ? 'Bold' : '').'">
								'.$data['title'].' '.($data['isadmin'] == 'on' ? '<i class="icon-star orange"></i>' : '').'
							</div>

						</div>

					</label>';

				}
				?>

			</div>

		</div>

		<hr>

		<div class="button--pane text-right">

			<A href="javascript:void(0)" onClick="saveSettings()" class="button">Сохранить</A>&nbsp;
			<A href="javascript:void(0)" onClick="DClose()" class="button">Отмена</A>

		</div>

	</form>
	<script src="/plugins/saleScript/js/hscript.js"></script>
	<script>

		$('#dialog').css('width', '700px');

		function getHSlogin() {

			var hyperscript = new window.hyperscript();
			var hparams = {
				'user': $('#UserID').val()
			};

			$('#hsf').removeClass('hidden');
			$('#dialog').center();

			hyperscript.sendMessage('showAuthForm', hparams, function (data) {

				console.log(data);

				$('#APIkey').val(data.key);
				$('#hsf').addClass('hidden');

			});

		}

		function getHSF() {

			var hyperscript = new window.hyperscript();

			var p = {
				'user': $('#UserID').val(),
				'key': $('#APIkey').val()
			};

			hyperscript.sendMessage('getUserScripts', p, function (obj) {

				if (obj.status !== "error") {

					var string = '';
					var list = obj.data;

					if (list.length === 0)
						Swal.fire({
							title: "Ответ сервиса",
							text: "Нет доступных скриптов",
							type: "warning"
						});

					else {

						for (var i in list) {

							string += '<div class="p5 pl10"><label><input type="checkbox" name="script[]" value="' + list[i].id + ':' + list[i].name + '" checked>' + list[i].id + ': ' + list[i].name + '</label></div>';

						}

						$('#hss').removeClass('hidden').append('<div>' + string + '</div>');

					}

				} else {

					Swal.fire({
						title: "Ответ сервиса",
						text: "Ошибка: " + obj.msg,
						type: "error"
					});

				}

			});

		}

	</script>
	<?php

	exit();
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>

	<meta charset="utf-8">
	<title>SaleScript - скрипты продаж</title>
	<link rel="stylesheet" href="/assets/css/app.css">
	<link rel="stylesheet" href="/assets/css/app.card.css">
	<link rel="stylesheet" href="/assets/css/fontello.css">
	<link rel="stylesheet" href="css/app.css">

	<script type="text/javascript" src="/assets/js/jquery/jquery-3.4.1.min.js"></script>
	<script type="text/javascript" src="/assets/js/jquery/jquery-migrate-3.0.0.min.js"></script>
	<script type="text/javascript" src="/assets/js/jquery/jquery-ui.min.js"></script>

	<!--красивые алерты-->
	<script type="text/javascript" src="/assets/js/sweet-alert2/sweetalert2.min.js"></script>
	<link type="text/css" rel="stylesheet" href="/assets/js/sweet-alert2/sweetalert2.min.css">

</head>
<body>

<div id="dialog_container" class="dialog_container">
	<div class="dialog-preloader">
		<img src="/assets/images/rings.svg" width="128">
	</div>
	<div class="dialog" id="dialog">
		<div class="close" title="Закрыть или нажмите ^ESC"><i class="icon-cancel"></i></div>
		<div id="resultdiv"></div>
	</div>
</div>

<div class="fixx">
	<DIV id="head">
		<DIV id="ctitle">
			<b>SaleScript - скрипты продаж</b>
			<DIV id="close" onClick="window.close();">Закрыть</DIV>
		</DIV>
	</DIV>
	<DIV id="dtabs">
		<UL>
			<LI class="ytab current" id="tb1" data-id="1"><A href="#1">Справка</A></LI>
			<LI class="ytab"><A href="javascript:void(0)" onclick="setSettings()">Настройка</A></LI>
		</UL>
	</DIV>
</div>

<DIV class="fixbg"></DIV>

<DIV id="telo">

	<div id="tab-1" class="tabbody">

		<?php
		if (is_writable('data') != true) {
			print '
			<div class="warning margbot10">
				<p><b class="red">Внимание! Ошибка</b> - отсутствуют права на запись для папки хранения настроек доступа"<b>data</b>".</p>
			</div>';
		}
		?>

		<fieldset class="pad10" style="overflow: auto; height: 450px">

			<legend>Справка по плагину</legend>

			<div class="infodiv margbot10">

				<pre id="copyright">
##################################################
#                                                #
#  Плагин разработан для SalesMan CRM v.2019.x   #
#  Разработчик: Владислав Андреев                #
#  Контакты:                                     #
#     - Сайт:  http://isaler.ru                  #
#     - Email: vladislav@isaler.ru               #
#     - Скайп: andreev.v.g                       #
#                                                #
##################################################
				</pre>

				<hr>

				<div class="margbot10 text fs-11">

					<div class="text-wrap">
						<?php
						//include_once "../../opensource/parsedown-master/Parsedown.php";

						$Parsedown = new Parsedown();
						print $help = $Parsedown -> text(file_get_contents("readme.md"));
						?>
					</div>

				</div>

			</div>

		</fieldset>

	</div>

</DIV>

<hr>

<div class="gray center-text">Сделано для SalesMan CRM</div>

<script src="js/app.js"></script>

</body>
</html>