<?php
/* ============================ */
/*         SalesMan CRM         */
/* ============================ */
/* (C) 2019 Vladislav Andreev   */
/*       SalesMan Project       */
/*        www.isaler.ru         */
/*        ver. 2019.x           */
/* ============================ */

use Chats\Comet;

$rootpath = realpath( __DIR__.'/../../' );
$ypath    = realpath( __DIR__.'/../../' )."/plugins/socialChats/";

error_reporting( E_ERROR );

require_once $rootpath."/inc/config.php";
require_once $rootpath."/inc/dbconnector.php";
require_once $rootpath."/inc/auth_main.php";
require_once $rootpath."/inc/settings.php";
require_once $rootpath."/inc/func.php";

require_once "php/autoload.php";

$about = json_decode( str_replace( [
	"  ",
	"\t",
	"\n",
	"\r"
], "", file_get_contents( "plugin.json" ) ), true );

$settings = customSettings("socialChatsSettings", "get");
if(empty($settings)){

	$settings = [
		"autoSaveAs" => "person",
		"autoClose"  => "24"
	];

}

// параметры comet-сервера
$comet  = new Comet($iduser1);
$cometSettings = $comet -> getSettings();

?>
<!DOCTYPE html>
<html lang="ru">
<head>
	<meta charset="utf-8">
	<title><?php echo $about['package']." - ".$about['name'] ?></title>

	<link type="text/css" rel="stylesheet" href="/assets/css/app.css">
	<link type="text/css" rel="stylesheet" href="/assets/css/fontello.css">
	<link type="text/css" rel="stylesheet" href="assets/css/app.css?v=0.7">

	<script type="text/javascript" src="/assets/js/jquery/jquery-3.4.1.min.js"></script>
	<script type="text/javascript" src="/assets/js/jquery/jquery-migrate-3.0.0.min.js"></script>
	<script type="text/javascript" src="/assets/js/jquery/jquery-ui.min.js?v=2019.4"></script>

	<script type="text/javascript" src="/assets/js/mustache/mustache.js"></script>
	<script type="text/javascript" src="/assets/js/mustache/jquery.mustache.js"></script>

	<!--красивые алерты-->
	<script type="text/javascript" src="/assets/js/sweet-alert2/sweetalert2.min.js"></script>
	<link type="text/css" rel="stylesheet" href="/assets/js/sweet-alert2/sweetalert2.min.css">

	<script type="text/javascript" src="assets/js/app.js?v=0.7"></script>
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

<div id="helper"><i class="icon-help-circled"></i></div>

<div class="chat-main box--child">

	<div class="" style="max-height: 100vh; overflow-y: auto">

		<div class="chat-first">

			<div class="flex-container float w400">

				<div class="flex-string w80">
					<img src="data:image/svg+xml;base64,<?php echo $about['iconSVGinBase64'] ?>" class="icon" width="50" height="50">
				</div>
				<div class="flex-string float">
					<div class="fs-20 flh-11 Bold"><?php echo $about['name'] ?></div>
					<div class="pl10"><?php echo $about['package'] ?></div>
				</div>

			</div>

			<h2><i class="icon-tools green"></i> Настройки логики</h2>

			<div>

				<form action="php/modal.php" method="post" enctype="multipart/form-data" name="settingsForm" id="settingsForm">
					<input type="hidden" id="action" name="action" value="settings.save">

					<div class="p0 mb10 grid" data-id="settings">

						<div class="flex-container box--child infodiv bgwhite pl20">

							<div class="flex-string wp100 fs-09 gray Bold">
								Действие с новым посетителем
							</div>
							<div class="flex-string wp100 mt10">

								<div class="flex-container">

									<div class="flex-string wp80 fs-10">
										Автоматически добавлять в базу
									</div>
									<div class="flex-string wp20">

										<label class="switch">
											<input name="autoSave" type="checkbox" id="autoSave" value="on" <?php echo ($settings['autoSave'] ? "checked" : "") ?>>
											<span class="slider"></span>
										</label>

									</div>

								</div>

							</div>

						</div>

						<div class="flex-container box--child infodiv bgwhite pl20">

							<div class="flex-string wp100 fs-09 gray Bold">
								Распределение диалогов
							</div>
							<div class="flex-string wp100 mt10">

								<div class="flex-container">

									<div class="flex-string wp80 fs-10">
										Автоматически распределять
									</div>
									<div class="flex-string wp20">

										<label class="switch">
											<input name="autoUser" type="checkbox" id="autoUser" value="on" <?php echo ($settings['autoUser'] ? "checked" : "") ?>>
											<span class="slider"></span>
										</label>

									</div>

								</div>

							</div>

						</div>

						<div class="flex-container box--child infodiv bgwhite pl20">

							<div class="flex-string wp100 fs-09 gray Bold">
								Какую запись добавлять
							</div>
							<div class="flex-string wp100 mt10 relative">

								<div class="radio wp45 border-box inline">
									<label class="pb5">
										<input name="autoSaveAs" type="radio" id="autoSaveAs" value="client" <?php echo ($settings['autoSaveAs'] == "client" ? "checked" : "") ?>>
										<span class="custom-radio success"><i class="icon-radio-check"></i></span>
										как Клиента
									</label>
								</div>

								<div class="radio wp45 border-box inline">
									<label class="pb5">
										<input name="autoSaveAs" type="radio" id="autoSaveAs" value="person" <?php echo ($settings['autoSaveAs'] == "person" ? "checked" : "") ?>>
										<span class="custom-radio success1"><i class="icon-radio-check"></i></span>
										как Контакт
									</label>
								</div>

							</div>

						</div>

						<div class="flex-container box--child infodiv bgwhite pl20">

							<div class="flex-string wp100 fs-09 gray Bold">
								Автозакрытие диалогов через
							</div>
							<div class="flex-string wp100 mt10">

								<div class="radio wp30 border-box inline">
									<label class="pb5">
										<input name="autoClose" type="radio" id="autoClose" value="24" <?php echo ($settings['autoClose'] == "24" ? "checked" : "") ?>>
										<span class="custom-radio success"><i class="icon-radio-check"></i></span>
										24 часа
									</label>
								</div>

								<div class="radio wp30 border-box inline">
									<label class="pb5">
										<input name="autoClose" type="radio" id="autoClose" value="48" <?php echo ($settings['autoClose'] == "48" ? "checked" : "") ?>>
										<span class="custom-radio success"><i class="icon-radio-check"></i></span>
										48 часов
									</label>
								</div>

								<div class="radio wp30 border-box inline">
									<label class="pb5">
										<input name="autoClose" type="radio" id="autoClose" value="0"  <?php echo ($settings['autoClose'] == "0" ? "checked" : "") ?>>
										<span class="custom-radio success"><i class="icon-radio-check"></i></span>
										вручную
									</label>
								</div>

							</div>

						</div>

						<div class="flex-container box--child infodiv bgwhite" style="grid-column: 1/3;">

							<div class="flex-string wp100 fs-09 gray Bold pl10">
								Автоответы
							</div>
							<div class="flex-string wp100 mt10">

								<div class="row">

									<div class="column grid-5 relative">

										<div class="flex-container">

											<div class="flex-string wp80 fs-10">
												О назначении сотрудника
											</div>
											<div class="flex-string wp20">

												<label class="switch">
													<input name="answers[notifyUserSet]" type="checkbox" id="answers[notifyUserSet]" value="on" <?php echo ($settings['answers']['notifyUserSet'] ? "checked" : "") ?>>
													<span class="slider"></span>
												</label>

											</div>

										</div>

									</div>

									<div class="column grid-5 relative">

										<div class="flex-container">

											<div class="flex-string wp80 fs-10">
												О добавлении сотрудника в диалог
											</div>
											<div class="flex-string wp20">

												<label class="switch">
													<input name="answers[notifyUserAppend]" type="checkbox" id="answers[notifyUserAppend]" value="on" <?php echo ($settings['answers']['notifyUserAppend'] ? "checked" : "") ?>>
													<span class="slider"></span>
												</label>

											</div>

										</div>

									</div>

								</div>

								<hr>

								<div class="row">

									<div class="column grid-5 relative">
										<span class="label">Первое обращение</span>
										<textarea type="text" name="answers[first]" id="answers[first]" rows="5" class="wp100"><?= $settings['answers']['first'] ?></textarea>
									</div>

									<div class="column grid-5 relative">
										<span class="label">Операторы Offline</span>
										<textarea type="text" name="answers[offline]" id="answers[offline]" rows="5" class="wp100"><?= $settings['answers']['offline'] ?></textarea>
									</div>

								</div>

							</div>

						</div>

						<div class="flex-container box--child infodiv bgwhite" style="grid-column: 1/3;">

							<div class="flex-string wp100 fs-09 gray Bold pl10">
								Подключение к Comet-server.ru <a href="https://comet-server.ru" target="_blank"><i class="icon-attach-1 gray2"></i></a>
							</div>
							<div class="flex-string wp100 mt10">

								<div class="row">

									<div class="column grid-2 relative">
										<span class="label">Dev ID</span>
										<input type="text" name="dev_id" id="dev_id" class="wp100" value="<?= $settings['dev_id'] ?>">
									</div>

									<div class="column grid-8 relative">
										<span class="label">Dev KEY</span>
										<input type="text" name="dev_key" id="dev_key" class="wp100" value="<?= $settings['dev_key'] ?>">
									</div>

								</div>

							</div>

						</div>

					</div>

				</form>

				<A href="javascript:void(0)" onClick="saveSettings()" class="button greenbtn dotted">Сохранить</A>&nbsp;

			</div>

			<h2><i class="icon-chat blue"></i> Подключенные каналы</h2>

			<div class="p0 mb10 grid" data-id="channels"></div>

			<a href="javascript:void(0)" onclick="$app.editChannel(0)" class="button bluebtn dotted"><i class="icon-plus-circled"></i> Добавить канал</a>
			<a href="javascript:void(0)" onclick="$app.getJsCode(0)" class="button bluebtn dotted"><i class="icon-file-code"></i> Код для сайта</a>

			<h2><i class="icon-users-1 red"></i> Операторы</h2>

			<div class="p0 mb10 grid" data-id="users"></div>

			<a href="javascript:void(0)" onclick="$app.editUser()" class="button redbtn dotted"><i class="icon-user-1"></i> Добавить оператора</a>

		</div>

		<div class="space-100"></div>
		<div class="gray center-text mt20">Сделано для SalesMan CRM</div>

	</div>
	<div class="graybg-lite pl20 pr20 relativ" id="help" style="max-height: 100vh; overflow-y: auto">

		<div id="helpcloser"><i class="icon-cancel"></i></div>

		<div style="overflow-wrap: normal;word-wrap: break-word;word-break: normal;line-break: strict;-webkit-hyphens: auto; -moz-hyphens: auto; hyphens: auto; width: 98%; box-sizing: border-box;">

			<?php
			$html = file_get_contents("readme.md");
			$Parsedown = new Parsedown();
			print $help = $Parsedown -> text($html);
			?>

			<div class="space-50"></div>

		</div>

	</div>

</div>

</body>
</html>