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
ini_set('display_errors', 1);

header( "Pragma: no-cache" );

global $hooks;

const SMPLUGIN = true;

$rootpath = dirname( __DIR__, 2 );

include $rootpath."/inc/config.php";
include $rootpath."/inc/dbconnector.php";
include $rootpath."/inc/auth.php";
include $rootpath."/inc/func.php";
include $rootpath."/inc/settings.php";

$thisfile = basename( __FILE__ );

$pluginList       = json_decode( str_replace( [
	"  ",
	"\t",
	"\n",
	"\r"
], "", file_get_contents( $rootpath."/plugins/map.json" ) ), true );
$pluginListCastom = (file_exists( $rootpath."/plugins/map.castom.json" )) ? json_decode( str_replace( [
	"  ",
	"\t",
	"\n",
	"\r"
], "", file_get_contents( $rootpath."/plugins/map.castom.json" ) ), true ) : [];

$pluginList = array_merge( $pluginList, $pluginListCastom );

$action = $_REQUEST['action'];

if ( $action == "deactivate" ) {

	$id = $_REQUEST['id'];
	$folder = $_REQUEST['name'];

	// подключаем хук плагина
	/*if ( !empty( $folder ) && file_exists( $rootpath."/plugins/$folder/".strtolower( $folder ).".php" ) ) {
		require_once $rootpath."/plugins/$folder/".strtolower( $folder ).".php";
	}*/

	$db -> query( "UPDATE ".$sqlname."plugins SET ?u WHERE id = '$id' and identity = '$identity'", [
		'active' => 'off',
		'datum'  => current_datumtime()
	] );

	// выполняем хук деактивации
	$hooks -> do_action( 'plugin_deactivate', ["name" => $folder] );

	/**
	 * массив подключенных плагинов
	 * далее используется для публикации в глобальной переменной для js
	 * в файле inc/header.php
	 */
	$pluginEnabled = [];

	$plugin = $db -> query( "SELECT * FROM ".$sqlname."plugins WHERE active = 'on' and identity = '$identity' ORDER by name" );
	while ($data = $db -> fetch( $plugin )) {

		$pluginEnabled[] = $data['name'];

	}

	file_put_contents( $rootpath."/cash/{$fpath}plugins.json", json_encode( $pluginEnabled ) );

	print "Плагин деактивирован";

	exit();

}
if ( $action == "activate" ) {

	$id     = $_REQUEST['id'];
	$folder = $_REQUEST['name'];
	$name = strtolower($_REQUEST['name']);

	//print $rootpath."/plugins/{$folder}/{$name}.php";

	// подключаем хук плагина
	if ( !empty( $folder ) && file_exists( $rootpath."/plugins/{$folder}/{$name}.php" ) ) {
		require_once $rootpath."/plugins/{$folder}/{$name}.php";
	}

	$pluginAbout['version'] = '1.0';
	if ( file_exists( $rootpath."/plugins/{$folder}/plugin.json" ) ) {

		$pluginAbout = json_decode( str_replace( [
			"  ",
			"\t",
			"\n",
			"\r"
		], "", file_get_contents( $rootpath."/plugins/{$folder}/plugin.json" ) ), true );

		//print_r($pluginAbout);

	}

	$db -> query( "UPDATE ".$sqlname."plugins SET ?u WHERE id = '$id' and identity = '$identity'", [
		'active'  => 'on',
		'version' => $pluginAbout['version'],
		'datum'   => current_datumtime()
	] );

	// выполняем хук активации
	$hooks -> do_action( 'plugin_activate', ["name" => $_REQUEST['name']] );

	/**
	 * массив подключенных плагинов
	 * далее используется для публикации в глобальной переменной для js
	 * в файле inc/header.php
	 */
	$pluginEnabled = [];

	$plugin = $db -> query( "SELECT * FROM ".$sqlname."plugins WHERE active = 'on' and identity = '$identity' ORDER by name" );
	while ($data = $db -> fetch( $plugin )) {

		$pluginEnabled[] = $data['name'];

	}

	file_put_contents( $rootpath."/cash/{$fpath}plugins.json", json_encode( $pluginEnabled ) );

	print "Плагин активирован";

	exit();

}
if ( $action == "uninstall" ) {

	$id     = $_REQUEST['id'];
	$folder = $_REQUEST['name'];

	define( 'SM_UNINSTALL_PLUGIN', true );

	// подключаем хук плагина
	if ( !empty( $folder ) && file_exists( $rootpath."/plugins/$folder/uninstall.php" ) ) {
		require_once $rootpath."/plugins/$folder/uninstall.php";
	}

	// выполняем хук деинсталляции
	$hooks -> do_action( 'plugin_uninstall', ["name" => $_REQUEST['name']] );

	$db -> query( "DELETE FROM ".$sqlname."plugins WHERE id = '$id' and identity = '$identity'" );

	/**
	 * массив подключенных плагинов
	 * далее используется для публикации в глобальной переменной для js
	 * в файле inc/header.php
	 */
	$pluginEnabled = [];

	$plugin = $db -> query( "SELECT * FROM ".$sqlname."plugins WHERE active = 'on' and identity = '$identity' ORDER by name" );
	while ($data = $db -> fetch( $plugin )) {

		$pluginEnabled[] = $data['name'];

	}

	file_put_contents( $rootpath."/cash/{$fpath}plugins.json", json_encode( $pluginEnabled ) );

	print "Плагин удален";

	exit();

}
if ( $action == "update" ) {

	$id     = $_REQUEST['id'];
	$folder = $_REQUEST['name'];

	// подключаем хук плагина
	// отключено, т.к. они уже подключены
	/*if ( !empty( $folder ) && file_exists( $rootpath."/plugins/$folder/".strtolower( $folder ).".php" ) ) {
		include_once $rootpath."/plugins/$folder/".strtolower( $folder ).".php";
	}*/

	if ( file_exists( $rootpath."/plugins/$folder/plugin.json" ) ) {

		$pluginAbout = json_decode( str_replace( [
			"  ",
			"\t",
			"\n",
			"\r"
		], "", file_get_contents( $rootpath."/plugins/{$folder}/plugin.json" ) ), true );

	}

	$db -> query( "UPDATE ".$sqlname."plugins SET ?u WHERE id = '$id' and identity = '$identity'", [
		'version' => $pluginAbout['version'],
		'datum'   => current_datumtime()
	] );

	// выполняем хук активации
	$hooks -> do_action( 'plugin_update', ["name" => $folder] );

	print "Плагин обновлен";

}

if ( $action == "install.do" ) {

	$id     = $_REQUEST['id'];
	$folder = $_REQUEST['plugin'];

	$data['name']     = $_REQUEST['plugin'];
	$data['active']   = 'on';
	$data['identity'] = $identity;
	$data['datum']    = current_datumtime();

	$data['version'] = '1.0';
	if ( file_exists( $rootpath."/plugins/{$folder}/plugin.json" ) ) {

		$pluginAbout     = json_decode( str_replace( [
			"  ",
			"\t",
			"\n",
			"\r"
		], "", file_get_contents( $rootpath."/plugins/{$folder}/plugin.json" ) ), true );
		$data['version'] = $pluginAbout['version'];

	}

	$db -> query( "INSERT INTO ".$sqlname."plugins SET ?u", $data );

	// подключаем хук плагина
	if ( isset( $data['name'] ) && file_exists( $rootpath."/plugins/$folder/".strtolower( $data['name'] ).".php" ) ) {
		require_once $rootpath."/plugins/$folder/".strtolower( $data['name'] ).".php";
	}

	// выполняем хук активации
	$hooks -> do_action( 'plugin_activate', ["name" => $data['name']] );

	/**
	 * массив подключенных плагинов
	 * далее используется для публикации в глобальной переменной для js
	 * в файле inc/header.php
	 */
	$pluginEnabled = [];

	$plugin = $db -> query( "SELECT * FROM ".$sqlname."plugins WHERE active = 'on' and identity = '$identity' ORDER by name" );
	while ($data = $db -> fetch( $plugin )) {

		$pluginEnabled[] = $data['name'];

	}

	file_put_contents( $rootpath."/cash/".$fpath."plugins.json", json_encode( $pluginEnabled ) );

	print json_encode_cyr( ["result" => "Сделано"] );

	exit();

}
if ( $action == "install" ) {

	$id = $_REQUEST['id'];

	$pluginsExist = $db -> getCol( "SELECT name FROM ".$sqlname."plugins where identity = '$identity'" );

	// пройдем папку с плагинами
	clearstatcache();

	$folders = scandir( $rootpath."/plugins", 1 );

	$pluginList = [];
	foreach ( $folders as $folder ) {

		if ( !in_array( $folder, [
				".",
				".."
			] ) && file_exists( $rootpath."/plugins/{$folder}/plugin.json" ) && !in_array( $folder, $pluginsExist, true ) ) {

			$pluginAbout = json_decode( str_replace( [
				"  ",
				"\t",
				"\n",
				"\r"
			], "", file_get_contents( $rootpath."/plugins/{$folder}/plugin.json" ) ), true );

			//print_r($pluginAbout);

			//$pluginList[ $pluginAbout['package'] ] = ["name" => $pluginAbout['name']];

			$pluginList[ $pluginAbout['name'] ] = [
				"name" => $pluginAbout['name'],
				"package" => $pluginAbout['package']
			];

		}

	}

	ksort($pluginList);

	?>
	<div class="zagolovok">Добавить Плагин</div>

	<FORM action="/content/admin/<?php echo $thisfile; ?>" method="POST" name="form" id="form">
		<input name="action" id="action" type="hidden" value="install.do"/>

		<div class="row">

			<div class="column12 grid-2 fs-12 pt10 right-text gray2">Плагин:</div>
			<div class="column12 grid-10">
				<select name="plugin" id="plugin" class="wp97">
					<option value="">--выбор--</option>
					<?php
					foreach ( $pluginList as $plugin => $item ) {

						if (
							!in_array( $item['package'], $pluginsExist ) &&
							file_exists( $rootpath."/plugins/".$item['package'] ) &&
							$item['name'] != ''
						) {
							print '<option value="'.$item['package'].'">'.$item['name'].'</option>';
						}

					}
					?>
				</select>
			</div>

		</div>

		<div class="viewdiv">

			<p>Необходимость подключения выбранного плагина к Webhook смотрите в Справке к плагину.</p>

			<?php
			if ( $isCloud ) {
				?>
				<p>После первого подключения плагина вы сможете протестировать его работу в течение 14 календарных дней. По истечению этого срока со счета вашего аккаунта будут списаны средства в виде арендной платы в размере 100 руб. (кроме SMS-плагинов) Списание производится ежемесячно и единовременно</p>
			<?php } ?>

		</div>

	</FORM>

	<hr>

	<div class="button--pane text-right">

		<A href="javascript:void(0)" onclick="$('#form').trigger('submit')" class="button">Сохранить</A>&nbsp;
		<A href="javascript:void(0)" onclick="DClose()" class="button">Отмена</A>

	</div>

	<script>

		$('#dialog').css({'width': '600px'});

		$('#form').ajaxForm({

			dataType: 'json',
			beforeSubmit: function () {

				var $out = $('#message');
				var em = checkRequired();

				if (em === false) return false;

				$out.fadeTo(10, 1).empty();

				$('#dialog').css('display', 'none');
				$('#dialog_container').css('display', 'none');
				$out.css('display', 'block').append('<div id=loader><img src=/assets/images/loader.gif> Загрузка данных. Пожалуйста подождите...</div>');

				return true;

			},
			success: function (data) {

				$('#contentdiv').load('/content/admin/<?php echo $thisfile; ?>');

				$('#message').fadeTo(1, 0);

				Swal.fire({
					icon: 'info',
					imageUrl: '/assets/images/success.svg',
					imageWidth: 50,
					imageHeight: 50,
					title: 'Вроде получилось',
					html: '' + data.result + '',
					showConfirmButton: false,
					timer: 2500
				});

				DClose();

			}

		});

	</script>
	<?php

	exit();

}

if ( $action == 'readme' ){

	$text = file_get_contents($rootpath."/plugins/".$_REQUEST['name']."/readme.md");
	$about = json_decode( str_replace( [
		"  ",
		"\t",
		"\n",
		"\r"
	], "", file_get_contents( $rootpath."/plugins/".$_REQUEST['name']."/plugin.json" ) ), true );

	$Parsedown = new Parsedown();
	$html = $Parsedown -> text( $text );

	$html = str_replace( [
		"{{package}}",
		"{{version}}",
		"{{versiondate}}",
	], [
		$about['package'],
		$about['version'],
		$about['versiondate'],
	], $html );
	?>
	<style>
        .readme blockquote{
            font-size: 0.9em;
            padding: 5px 10px;
            background-color: var(--gray-superlite);
            border: 1px dashed var(--red);
            border-left: 3px solid var(--red);
            margin: 0;
        }
	</style>
	<div class="zagolovok">Readme</div>
	<DIV id="formtabs" class="p20 bgwhite readme" style="overflow-y: auto; overflow-x:hidden; max-height:80vh;">

		<?php print $html; ?>

	</DIV>

	<hr>

	<div class="button--pane text-right">

		<a href="javascript:void(0)" onclick="DClose()" class="button">Закрыть</a>

	</div>
	<script>

		$(function () {
			$('#dialog').css('width', '70vw').center();
		});

	</script>
	<?php
	exit();
}

if ( $action == '' ) {

	$list        = [];
	$updateCount = 0;
	$updateName  = [];
	$i           = 1;
	$result      = $db -> getAll( "SELECT * FROM ".$sqlname."plugins WHERE identity = '$identity' ORDER BY name" );

	//print_r($result);

	foreach ( $result as $data ) {

		$pluginAbout = [];
		$actions = $about = $package = '';

		$img = ($pluginList[ $data['name'] ]['icon'] != '') ? '<div class="mt10 mr10 mb10 fs-20 pl10"><i class="'.$pluginList[ $data['name'] ]['icon'].' gray2"></i></div>' : '<img src="data:image/svg+xml;base64,PD94bWwgdmVyc2lvbj0iMS4wIiBlbmNvZGluZz0iaXNvLTg4NTktMSI/Pg0KPCEtLSBHZW5lcmF0b3I6IEFkb2JlIElsbHVzdHJhdG9yIDE5LjAuMCwgU1ZHIEV4cG9ydCBQbHVnLUluIC4gU1ZHIFZlcnNpb246IDYuMDAgQnVpbGQgMCkgIC0tPg0KPHN2ZyB2ZXJzaW9uPSIxLjEiIGlkPSJMYXllcl8xIiB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHhtbG5zOnhsaW5rPSJodHRwOi8vd3d3LnczLm9yZy8xOTk5L3hsaW5rIiB4PSIwcHgiIHk9IjBweCINCgkgdmlld0JveD0iMCAwIDUwMy42MDcgNTAzLjYwNyIgc3R5bGU9ImVuYWJsZS1iYWNrZ3JvdW5kOm5ldyAwIDAgNTAzLjYwNyA1MDMuNjA3OyIgeG1sOnNwYWNlPSJwcmVzZXJ2ZSI+DQo8ZyB0cmFuc2Zvcm09InRyYW5zbGF0ZSgxIDEpIj4NCgk8Zz4NCgkJPHBhdGggc3R5bGU9ImZpbGw6I0ZGREQwOTsiIGQ9Ik00MjcuMDY2LDExNi41MDhoMzMuNTc0VjI0LjE4YzAtOS4yMzMtNy41NTQtMTYuNzg3LTE2Ljc4Ny0xNi43ODcNCgkJCWMtOS4yMzMsMC0xNi43ODcsNy41NTQtMTYuNzg3LDE2Ljc4N1YxMTYuNTA4eiIvPg0KCQk8cGF0aCBzdHlsZT0iZmlsbDojRkZERDA5OyIgZD0iTTMwOS41NTcsNDE4LjY3MkwzMDkuNTU3LDQxOC42NzJjMCwxOC40NjYsMTUuMTA4LDMzLjU3NCwzMy41NzQsMzMuNTc0aDMzLjU3NA0KCQkJYzE4LjQ2NiwwLDMzLjU3NC0xNS4xMDgsMzMuNTc0LTMzLjU3NHYtMjEuODIzYzAtNDAuMjg5LDE1LjEwOC03OC44OTgsNDEuOTY3LTEwOC4yNzVsMCwwDQoJCQljMjYuODU5LTI5LjM3Nyw0MS45NjctNjcuOTg3LDQxLjk2Ny0xMDguMjc1di02My43OWgtMjY4LjU5djYzLjc5YzAsNDAuMjg5LDE1LjEwOCw3OC44OTgsNDEuOTY3LDEwOC4yNzUNCgkJCXM0MS45NjcsNjguODI2LDQxLjk2NywxMDguMjc1VjQxOC42NzJ6Ii8+DQoJPC9nPg0KCTxwYXRoIHN0eWxlPSJmaWxsOiNGRDk4MDg7IiBkPSJNNDY5LjAzMywxMTYuNTA4djYzLjc5YzAsNDAuMjg5LTE1LjEwOCw3OC44OTgtNDEuOTY3LDEwOC4yNzVsMCwwDQoJCWMtMjYuODU5LDI5LjM3Ny00MS45NjcsNjcuOTg3LTQxLjk2NywxMDguMjc1djIxLjgyM2MwLDE4LjQ2Ni0xNS4xMDgsMzMuNTc0LTMzLjU3NCwzMy41NzRoMjUuMTgNCgkJYzE4LjQ2NiwwLDMzLjU3NC0xNS4xMDgsMzMuNTc0LTMzLjU3NHYtMjEuODIzYzAtNDAuMjg5LDE1LjEwOC03OC44OTgsNDEuOTY3LTEwOC4yNzVsMCwwDQoJCWMyNi44NTktMjkuMzc3LDQxLjk2Ny02Ny45ODcsNDEuOTY3LTEwOC4yNzV2LTYzLjc5SDQ2OS4wMzN6Ii8+DQoJPHBhdGggc3R5bGU9ImZpbGw6I0ZGREQwOTsiIGQ9Ik0yOTIuNzcsMTE2LjUwOGgtMzMuNTc0VjI0LjE4YzAtOS4yMzMsNy41NTQtMTYuNzg3LDE2Ljc4Ny0xNi43ODdzMTYuNzg3LDcuNTU0LDE2Ljc4NywxNi43ODcNCgkJVjExNi41MDh6Ii8+DQoJPHBhdGggc3R5bGU9ImZpbGw6I0ZDQzMwOTsiIGQ9Ik0yNjcuNTksMjg4LjU3NGMtMjQuMzQxLTI2Ljg1OS0zOC42MS02MC40MzMtNDEuMTI4LTk2LjUyNUg0OS4zNjENCgkJYy0yMy41MDIsMC00MS45NjcsMTguNDY2LTQxLjk2Nyw0MS45Njd2MjE4LjIyOWMwLDIzLjUwMiwxOC40NjYsNDEuOTY3LDQxLjk2Nyw0MS45NjdIMjY3LjU5DQoJCWMyMy41MDIsMCw0MS45NjctMTguNDY2LDQxLjk2Ny00MS45Njd2LTMzLjU3NHYtMjEuODIzQzMwOS41NTcsMzU3LjQsMjk0LjQ0OSwzMTcuOTUxLDI2Ny41OSwyODguNTc0Ii8+DQoJPHBhdGggc3R5bGU9ImZpbGw6I0ZGREQwOTsiIGQ9Ik0yNjcuNTksMjg4LjU3NGMtMTYuNzg3LTE4LjQ2Ni0yOC41MzgtMzkuNDQ5LTM1LjI1Mi02Mi45NTFINjEuOTUxDQoJCWMtMTEuNzUxLDAtMjAuOTg0LDkuMjMzLTIwLjk4NCwyMC45ODR2MTkzLjA0OWMwLDExLjc1MSw5LjIzMywyMC45ODQsMjAuOTg0LDIwLjk4NEgyNTVjMTEuNzUxLDAsMjAuOTg0LTkuMjMzLDIwLjk4NC0yMC45ODQNCgkJdi0xNDEuMDFDMjczLjQ2NiwyOTUuMjg5LDI3MC45NDgsMjkxLjkzMSwyNjcuNTksMjg4LjU3NCIvPg0KCTxwYXRoIHN0eWxlPSJmaWxsOiNGRkZGRkY7IiBkPSJNNDkuMzYxLDE5Mi4wNDloMTYuNzg3Yy0yMy41MDIsMC00MS45NjcsMTguNDY2LTQxLjk2Nyw0MS45Njd2MjE4LjIyOQ0KCQljMCwyMy41MDIsMTguNDY2LDQxLjk2Nyw0MS45NjcsNDEuOTY3SDQ5LjM2MWMtMjMuNTAyLDAtNDEuOTY3LTE4LjQ2Ni00MS45NjctNDEuOTY3VjIzNC4wMTYNCgkJQzcuMzkzLDIxMC41MTUsMjYuNjk4LDE5Mi4wNDksNDkuMzYxLDE5Mi4wNDkiLz4NCgk8cGF0aCBzdHlsZT0iZmlsbDojRkNDMzA5OyIgZD0iTTIzNC4wMTYsMzQzLjEzMWMwLTQxLjk2Ny0zMy41NzQtNzUuNTQxLTc1LjU0MS03NS41NDFzLTc1LjU0MSwzMy41NzQtNzUuNTQxLDc1LjU0MQ0KCQlzMzMuNTc0LDc1LjU0MSw3NS41NDEsNzUuNTQxUzIzNC4wMTYsMzg1LjA5OCwyMzQuMDE2LDM0My4xMzEiLz4NCgk8cGF0aCBzdHlsZT0iZmlsbDojRkQ5ODA4OyIgZD0iTTE1OC40NzUsMjY3LjU5Yy00LjE5NywwLTguMzkzLDAuODM5LTEyLjU5LDAuODM5YzM2LjA5Miw1Ljg3NSw2Mi45NTEsMzYuOTMxLDYyLjk1MSw3NC43MDINCgkJcy0yNi44NTksNjcuOTg3LTYyLjk1MSw3NC43MDJjNC4xOTcsMC44MzksOC4zOTMsMC44MzksMTIuNTksMC44MzljNDEuOTY3LDAsNzUuNTQxLTMzLjU3NCw3NS41NDEtNzUuNTQxDQoJCVMyMDAuNDQzLDI2Ny41OSwxNTguNDc1LDI2Ny41OSIvPg0KCTxwYXRoIGQ9Ik0xNTguNDc1LDQyNy4wNjZjLTQ2LjE2NCwwLTgzLjkzNC0zNy43Ny04My45MzQtODMuOTM0czM3Ljc3LTgzLjkzNCw4My45MzQtODMuOTM0czgzLjkzNCwzNy43Nyw4My45MzQsODMuOTM0DQoJCVMyMDQuNjM5LDQyNy4wNjYsMTU4LjQ3NSw0MjcuMDY2eiBNMTU4LjQ3NSwyNzUuOTg0Yy0zNi45MzEsMC02Ny4xNDgsMzAuMjE2LTY3LjE0OCw2Ny4xNDhzMzAuMjE2LDY3LjE0OCw2Ny4xNDgsNjcuMTQ4DQoJCXM2Ny4xNDgtMzAuMjE2LDY3LjE0OC02Ny4xNDhTMTk1LjQwNywyNzUuOTg0LDE1OC40NzUsMjc1Ljk4NHoiLz4NCgk8cGF0aCBkPSJNMTUwLjA4MiwzNDMuMTMxYzAtOS4yMzMtNy41NTQtMTYuNzg3LTE2Ljc4Ny0xNi43ODdzLTE2Ljc4Nyw3LjU1NC0xNi43ODcsMTYuNzg3YzAsOS4yMzMsNy41NTQsMTYuNzg3LDE2Ljc4NywxNi43ODcNCgkJUzE1MC4wODIsMzUyLjM2NCwxNTAuMDgyLDM0My4xMzEiLz4NCgk8cGF0aCBkPSJNMjAwLjQ0MywzNDMuMTMxYzAtOS4yMzMtNy41NTQtMTYuNzg3LTE2Ljc4Ny0xNi43ODdzLTE2Ljc4Nyw3LjU1NC0xNi43ODcsMTYuNzg3YzAsOS4yMzMsNy41NTQsMTYuNzg3LDE2Ljc4NywxNi43ODcNCgkJUzIwMC40NDMsMzUyLjM2NCwyMDAuNDQzLDM0My4xMzEiLz4NCgk8cGF0aCBkPSJNMTU4LjQ3NSwyOTIuNzdjLTUuMDM2LDAtOC4zOTMtMy4zNTctOC4zOTMtOC4zOTNWMjY3LjU5YzAtNS4wMzYsMy4zNTctOC4zOTMsOC4zOTMtOC4zOTNzOC4zOTMsMy4zNTcsOC4zOTMsOC4zOTN2MTYuNzg3DQoJCUMxNjYuODY5LDI4OS40MTMsMTYzLjUxMSwyOTIuNzcsMTU4LjQ3NSwyOTIuNzd6Ii8+DQoJPHBhdGggZD0iTTE1OC40NzUsNDI3LjA2NmMtNS4wMzYsMC04LjM5My0zLjM1Ny04LjM5My04LjM5M3YtMTYuNzg3YzAtNS4wMzYsMy4zNTctOC4zOTMsOC4zOTMtOC4zOTNzOC4zOTMsMy4zNTcsOC4zOTMsOC4zOTMNCgkJdjE2Ljc4N0MxNjYuODY5LDQyMy43MDgsMTYzLjUxMSw0MjcuMDY2LDE1OC40NzUsNDI3LjA2NnoiLz4NCgk8cGF0aCBkPSJNMjkyLjc3LDEyNC45MDJoLTMzLjU3NGMtNS4wMzYsMC04LjM5My0zLjM1Ny04LjM5My04LjM5M1YyNC4xOGMwLTE0LjI2OSwxMC45MTEtMjUuMTgsMjUuMTgtMjUuMTgNCgkJczI1LjE4LDEwLjkxMSwyNS4xOCwyNS4xOHY5Mi4zMjhDMzAxLjE2NCwxMjEuNTQ0LDI5Ny44MDcsMTI0LjkwMiwyOTIuNzcsMTI0LjkwMnogTTI2Ny41OSwxMDguMTE1aDE2Ljc4N1YyNC4xOA0KCQljMC01LjAzNi0zLjM1Ny04LjM5My04LjM5My04LjM5M2MtNS4wMzYsMC04LjM5MywzLjM1Ny04LjM5Myw4LjM5M1YxMDguMTE1eiIvPg0KCTxwYXRoIGlkPSJTVkdDbGVhbmVySWRfMCIgZD0iTTM3Ni43MDUsNDYwLjYzOWgtMzMuNTc0Yy0yMy41MDIsMC00MS45NjctMTguNDY2LTQxLjk2Ny00MS45Njd2LTIxLjgyMw0KCQljMC0zNy43NzEtMTQuMjY5LTc0LjcwMi0zOS40NDktMTAzLjIzOWMtMjguNTM4LTMxLjA1Ni00NC40ODUtNzIuMTg0LTQ0LjQ4NS0xMTQuMTUxdi02Mi45NTFjMC01LjAzNiwzLjM1Ny04LjM5Myw4LjM5My04LjM5Mw0KCQloMjY4LjU5YzUuMDM2LDAsOC4zOTMsMy4zNTcsOC4zOTMsOC4zOTN2NjMuNzljMCw0MS45NjctMTUuOTQ4LDgzLjA5NS00NC40ODUsMTE0LjE1MWMtMjYuMDIsMjguNTM4LTM5LjQ0OSw2NC42My0zOS40NDksMTAzLjIzOQ0KCQl2MjAuOTg0QzQxOC42NzIsNDQyLjE3NCw0MDAuMjA3LDQ2MC42MzksMzc2LjcwNSw0NjAuNjM5eiBNMjM0LjAxNiwxMjQuOTAydjU1LjM5N2MwLDM3Ljc3LDE0LjI2OSw3NC43MDIsMzkuNDQ5LDEwMy4yMzkNCgkJYzI4LjUzOCwzMS4wNTYsNDQuNDg1LDcyLjE4NCw0NC40ODUsMTE0LjE1MXYyMC45ODRjMCwxNC4yNjksMTAuOTExLDI1LjE4LDI1LjE4LDI1LjE4aDMzLjU3NGMxNC4yNjksMCwyNS4xOC0xMC45MTEsMjUuMTgtMjUuMTgNCgkJdi0yMS44MjNjMC00MS45NjcsMTUuOTQ4LTgzLjA5NSw0NC40ODUtMTE0LjE1MWMyNi4wMi0yOC41MzgsMzkuNDQ5LTY0LjYzLDM5LjQ0OS0xMDMuMjM5di01NC41NTdIMjM0LjAxNnoiLz4NCgk8cGF0aCBkPSJNMzU5LjkxOCw1MDIuNjA3Yy01LjAzNiwwLTguMzkzLTMuMzU3LTguMzkzLTguMzkzdi00MS45NjdjMC01LjAzNiwzLjM1Ny04LjM5Myw4LjM5My04LjM5Mw0KCQljNS4wMzYsMCw4LjM5MywzLjM1Nyw4LjM5Myw4LjM5M3Y0MS45NjdDMzY4LjMxMSw0OTkuMjQ5LDM2NC45NTQsNTAyLjYwNywzNTkuOTE4LDUwMi42MDd6Ii8+DQoJPHBhdGggZD0iTTQ2MC42MzksMTI0LjkwMmgtMzMuNTc0Yy01LjAzNiwwLTguMzkzLTMuMzU3LTguMzkzLTguMzkzVjI0LjE4YzAtMTQuMjY5LDEwLjkxMS0yNS4xOCwyNS4xOC0yNS4xOA0KCQlzMjUuMTgsMTAuOTExLDI1LjE4LDI1LjE4djkyLjMyOEM0NjkuMDMzLDEyMS41NDQsNDY1LjY3NSwxMjQuOTAyLDQ2MC42MzksMTI0LjkwMnogTTQzNS40NTksMTA4LjExNWgxNi43ODdWMjQuMTgNCgkJYzAtNS4wMzYtMy4zNTctOC4zOTMtOC4zOTMtOC4zOTNjLTUuMDM2LDAtOC4zOTMsMy4zNTctOC4zOTMsOC4zOTNWMTA4LjExNXoiLz4NCgk8cGF0aCBkPSJNNDE4LjY3MiwyMDAuNDQzSDMwMS4xNjRjLTUuMDM2LDAtOC4zOTMtMy4zNTctOC4zOTMtOC4zOTNjMC01LjAzNiwzLjM1Ny04LjM5Myw4LjM5My04LjM5M2gxMTcuNTA4DQoJCWM1LjAzNiwwLDguMzkzLDMuMzU3LDguMzkzLDguMzkzQzQyNy4wNjYsMTk3LjA4NSw0MjMuNzA4LDIwMC40NDMsNDE4LjY3MiwyMDAuNDQzeiIvPg0KCTxwYXRoIGQ9Ik00MTguNjcyLDIzNC4wMTZIMzAxLjE2NGMtNS4wMzYsMC04LjM5My0zLjM1Ny04LjM5My04LjM5M3MzLjM1Ny04LjM5Myw4LjM5My04LjM5M2gxMTcuNTA4DQoJCWM1LjAzNiwwLDguMzkzLDMuMzU3LDguMzkzLDguMzkzUzQyMy43MDgsMjM0LjAxNiw0MTguNjcyLDIzNC4wMTZ6Ii8+DQoJPGc+DQoJCTxwYXRoIGlkPSJTVkdDbGVhbmVySWRfMF8xXyIgZD0iTTM3Ni43MDUsNDYwLjYzOWgtMzMuNTc0Yy0yMy41MDIsMC00MS45NjctMTguNDY2LTQxLjk2Ny00MS45Njd2LTIxLjgyMw0KCQkJYzAtMzcuNzcxLTE0LjI2OS03NC43MDItMzkuNDQ5LTEwMy4yMzljLTI4LjUzOC0zMS4wNTYtNDQuNDg1LTcyLjE4NC00NC40ODUtMTE0LjE1MXYtNjIuOTUxYzAtNS4wMzYsMy4zNTctOC4zOTMsOC4zOTMtOC4zOTMNCgkJCWgyNjguNTljNS4wMzYsMCw4LjM5MywzLjM1Nyw4LjM5Myw4LjM5M3Y2My43OWMwLDQxLjk2Ny0xNS45NDgsODMuMDk1LTQ0LjQ4NSwxMTQuMTUxYy0yNi4wMiwyOC41MzgtMzkuNDQ5LDY0LjYzLTM5LjQ0OSwxMDMuMjM5DQoJCQl2MjAuOTg0QzQxOC42NzIsNDQyLjE3NCw0MDAuMjA3LDQ2MC42MzksMzc2LjcwNSw0NjAuNjM5eiBNMjM0LjAxNiwxMjQuOTAydjU1LjM5N2MwLDM3Ljc3LDE0LjI2OSw3NC43MDIsMzkuNDQ5LDEwMy4yMzkNCgkJCWMyOC41MzgsMzEuMDU2LDQ0LjQ4NSw3Mi4xODQsNDQuNDg1LDExNC4xNTF2MjAuOTg0YzAsMTQuMjY5LDEwLjkxMSwyNS4xOCwyNS4xOCwyNS4xOGgzMy41NzRjMTQuMjY5LDAsMjUuMTgtMTAuOTExLDI1LjE4LTI1LjE4DQoJCQl2LTIxLjgyM2MwLTQxLjk2NywxNS45NDgtODMuMDk1LDQ0LjQ4NS0xMTQuMTUxYzI2LjAyLTI4LjUzOCwzOS40NDktNjQuNjMsMzkuNDQ5LTEwMy4yMzl2LTU0LjU1N0gyMzQuMDE2eiIvPg0KCTwvZz4NCgk8cGF0aCBkPSJNMjY3LjU5LDUwMi42MDdINDkuMzYxQzIxLjY2Miw1MDIuNjA3LTEsNDc5Ljk0NC0xLDQ1Mi4yNDZWMjM0LjAxNmMwLTI3LjY5OCwyMi42NjItNTAuMzYxLDUwLjM2MS01MC4zNjFoMTc3LjEwMg0KCQljNC4xOTcsMCw4LjM5MywzLjM1Nyw4LjM5Myw3LjU1NGMyLjUxOCwzMy41NzQsMTYuNzg3LDY2LjMwOCwzOS40NDksOTEuNDg5bDAsMGMyOC41MzgsMzEuMDU2LDQ0LjQ4NSw3Mi4xODQsNDQuNDg1LDExNC4xNTENCgkJdjU1LjM5N0MzMTcuOTUxLDQ3OS45NDQsMjk1LjI4OSw1MDIuNjA3LDI2Ny41OSw1MDIuNjA3eiBNNDkuMzYxLDIwMC40NDNjLTE4LjQ2NiwwLTMzLjU3NCwxNS4xMDgtMzMuNTc0LDMzLjU3NHYyMTguMjI5DQoJCWMwLDE4LjQ2NiwxNS4xMDgsMzMuNTc0LDMzLjU3NCwzMy41NzRIMjY3LjU5YzE4LjQ2NiwwLDMzLjU3NC0xNS4xMDgsMzMuNTc0LTMzLjU3NHYtNTUuMzk3DQoJCWMwLTM3Ljc3MS0xNC4yNjktNzQuNzAyLTM5LjQ0OS0xMDMuMjM5bDAsMGMtMjMuNTAyLTI2LjAyLTM4LjYxLTU4Ljc1NC00Mi44MDctOTQuMDA3SDQ5LjM2MVYyMDAuNDQzeiIvPg0KCTxwYXRoIGQ9Ik0yNTUsNDY5LjAzM0g2MS45NTFjLTE1Ljk0OCwwLTI5LjM3Ny0xMy40My0yOS4zNzctMjkuMzc3VjI0Ni42MDdjMC0xNS45NDgsMTMuNDMtMjkuMzc3LDI5LjM3Ny0yOS4zNzdoMTcwLjM4Nw0KCQljMy4zNTcsMCw2LjcxNSwyLjUxOCw4LjM5Myw1Ljg3NWM2LjcxNSwyMS44MjMsMTguNDY2LDQyLjgwNywzMy41NzQsNTkuNTkzbDAsMGMzLjM1NywzLjM1Nyw1Ljg3NSw2LjcxNSw5LjIzMywxMC45MTENCgkJYzAuODM5LDEuNjc5LDEuNjc5LDMuMzU3LDEuNjc5LDUuMDM2djE0MS4wMUMyODQuMzc3LDQ1NS42MDMsMjcwLjk0OCw0NjkuMDMzLDI1NSw0NjkuMDMzeiBNNjEuOTUxLDIzNC4wMTYNCgkJYy02LjcxNSwwLTEyLjU5LDUuODc1LTEyLjU5LDEyLjU5djE5My4wNDljMCw2LjcxNSw1Ljg3NSwxMi41OSwxMi41OSwxMi41OUgyNTVjNi43MTUsMCwxMi41OS01Ljg3NSwxMi41OS0xMi41OVYzMDIuMDAzDQoJCWMtMS42NzktMi41MTgtNC4xOTctNS4wMzYtNS44NzUtNy41NTRjLTE1Ljk0OC0xNy42MjYtMjcuNjk4LTM3Ljc3LTM1LjI1Mi02MC40MzNMNjEuOTUxLDIzNC4wMTZMNjEuOTUxLDIzNC4wMTZ6Ii8+DQo8L2c+DQo8Zz4NCjwvZz4NCjxnPg0KPC9nPg0KPGc+DQo8L2c+DQo8Zz4NCjwvZz4NCjxnPg0KPC9nPg0KPGc+DQo8L2c+DQo8Zz4NCjwvZz4NCjxnPg0KPC9nPg0KPGc+DQo8L2c+DQo8Zz4NCjwvZz4NCjxnPg0KPC9nPg0KPGc+DQo8L2c+DQo8Zz4NCjwvZz4NCjxnPg0KPC9nPg0KPGc+DQo8L2c+DQo8L3N2Zz4NCg==" class="mt10 icon" width="30" height="30">';

		$color = ($data['active'] == 'on') ? 'blue' : 'gray';
		$a     = ($data['active'] == 'on') ? '' : 'idisabled';

		$doit = ($isCloud == true && (diffDate( $data['datum'] ) == 0 || diffDate( $data['datum'] ) > 14 || $data['active'] == 'off')) || $isCloud == false;
		$diff = (14 - diffDate( $data['datum'] ));

		if ( file_exists( $rootpath."/plugins/".$data['name']."/plugin.json" ) ) {

			$pluginAbout = json_decode( str_replace( [
				"  ",
				"\t",
				"\n",
				"\r"
			], "", file_get_contents( $rootpath."/plugins/".$data['name']."/plugin.json" ) ), true );

			$pluginList[ $data['name'] ]['name']       = $pluginAbout['name'];
			$pluginList[ $data['name'] ]['content']    = $pluginAbout['description'];
			$pluginList[ $data['name'] ]['package']    = $pluginAbout['package'];
			$pluginList[ $data['name'] ]['interfaice'] = $pluginAbout['menu'] ? "yes" : "";

			$img = ($pluginAbout['iconSVGinBase64'] != '') ? '<img src="data:image/svg+xml;base64,'.$pluginAbout['iconSVGinBase64'].'" class="mt10 icon '.$color.'" width="30" height="30">' : '<div class="mt10 mr10 mb10 fs-20 pl10"><i class="'.$pluginAbout['icon'].'"></i></div>';

			$about .= 'Версия: '.$pluginAbout['version'].'&nbsp;|&nbsp;';
			$about .= '<a href="'.$pluginAbout['authorurl'].'" target="_blank"><i class="icon-globe"></i> '.$pluginAbout['author'].'</a>&nbsp;|&nbsp;';
			$about .= '<a href="'.$pluginAbout['link'].'" target="_blank"><i class="icon-link-1"></i> Описание</a>&nbsp;|&nbsp;';
			$about .= (file_exists( $rootpath."/plugins/".$data['name']."/readme.md" )) ? '<a href="javascript:void(0)" onclick="readMe(\''.$data['name'].'\')" class="blue"><i class="icon-book"></i> Readme</a>&nbsp;|&nbsp;' : "";

		}

		if ( is_null( $data['version'] ) && $pluginAbout['version'] != '' ) {

			$db -> query( "UPDATE ".$sqlname."plugins SET ?u WHERE id = '$data[id]' and identity = '$identity'", [
				'version' => $pluginAbout['version']
			] );

			$data['version'] = $pluginAbout['version'];

		}

		if ( $data['active'] == 'on' ) {

			if ( $doit ) {

				if ( $pluginList[ $data['name'] ]['interfaice'] == "yes" ) {

					$actions .= '<A href="javascript:void(0)" onclick="openPlugin(\'plugins/'.$data['name'].'\')" class="blue" title="Настроить">Настроить</A>&nbsp;|&nbsp;';

				}

				$actions .= '<A href="javascript:void(0)" onclick="actPlugin(\''.$data['id'].'\',\'deactivate\', \''.$data['name'].'\')" class="blue" title="Деактивировать">Деактивировать</A>';

			}

			else {
				$actions .= '<A href="javascript:void(0)" class="gray2" title="Плагин может быть деактивирован через '.$diff.' '.getMorph( $diff, 'day' ).'">Деактивировать</A>';
			}

		}
		else {

			$actions .= '<A href="javascript:void(0)" onclick="actPlugin(\''.$data['id'].'\',\'activate\', \''.$data['name'].'\')" class="blue" title="Активировать">Активировать</A>';

			$actions .= '&nbsp;|&nbsp;<A href="javascript:void(0)" onclick="uninstallPlugin(\''.$data['id'].'\',\''.$data['name'].'\')" class="red" title="Удалить">Удалить</A>';

		}

		if ( $data['version'] != $pluginAbout['version'] && $pluginAbout['version'] != '' ) {

			$actions .= '<div class="mt5"><A href="javascript:void(0)" onclick="actPlugin(\''.$data['id'].'\',\'update\', \''.$data['name'].'\')" class="button greenbtn ptb5lr15" title="Обновить"><i class="icon-refresh"></i>Обновить</A></div>Версия в базе: '.$data['version'];

			$updateCount++;
			$updateName[] = $pluginAbout['name'];

		}

		$about .= '<span title="Дата активации"><i class="icon-calendar-inv"></i> '.get_date( $data['datum'] ).'</span>';

		$list[ $pluginList[ $data['name'] ]['name'] ] = [
			"active"  => $a,
			"img"     => $img,
			"name"    => $pluginList[ $data['name'] ]['name'],
			"color"   => $color,
			"actions" => $actions,
			"content" => $pluginList[ $data['name'] ]['content'],
			"package" => $pluginList[ $data['name'] ]['package'],
			"about"   => $about
		];

	}

	ksort( $list );

	?>
	<style>

		.idisabled .icon {
			filter : grayscale(5);
		}
		.idisabled:hover .icon {
			filter : unset;
		}

	</style>
	<h2>&nbsp;Раздел: "Плагины"</h2>

	<div class="viewdiv">

		<p>В этом разделе вы можете подключить имеющиеся плагины</p>
		<p>Плагины <b>НЕ входят в состав SalesMan CRM</b>, поставляются отдельно и их работоспособность не гарантируется в каждом конкретном случае. Список плагинов доступен
			<a href="https://salesman.pro/plugins/" title="SalesMan. Плагины" target="_blank" class="blue">на сайте</a> и в
			<a href="https://salesman.pro/api2/plugins" title="Документации" target="_blank">Документации</a></p>
		<p>После добавления/удаления и активации/деактивации плагина требуется обновить окно браузера</p>

		<?php
		if ( $updateCount > 0 ) {
			print '<div class="success">Следует обновить <b>'.$updateCount.'</b> '.getMorph2( $updateCount, [
					'плагин',
					'плагина',
					'плагинов'
				] ).' - '.yimplode( ", ", $updateName ).'</div>';
		}
		?>

	</div>

	<?php
	if ( $isCloud ) {

		print '<div class="warning p10 fs-11">
			<h2 class="red fs-12 mt5">Внимание!</h2>
			Деактивация некоторых плагинов возможна либо <b>в день активации</b>, либо
			<b>через 14 дней</b> после активации!
		</div>';

	}
	?>

	<hr>

	<TABLE id="catlist" class="top">
		<thead class="hidden-iphone sticked--top">
		<TR class="th40">
			<th class="w60"></th>
			<th class="w300 text-left"><b>Название</b></th>
			<th class="text-left"><b>Описание</b></th>
		</TR>
		</thead>
		<tbody>
		<?php
		foreach ( $list as $data ) {
			?>
			<TR class="ha th45 <?php echo $data['active']; ?>">
				<TD class="text-center">
					<div><?php echo $data['img'] ?></div>
				</TD>
				<TD>
					<div class="Bold <?php echo $data['color'] ?> fs-12 mt10"><?php echo $data['name'] ?></div>
					<?php if ( $data['package'] != '' ) {
						print '<div class="gray2 mt5">'.$data['package'].'</div>';
					} ?>
					<div class="mt10 fs-09 mb10 gray"><?php echo $data['actions']; ?></div>
				</TD>
				<TD>
					<div class="fs-10 mt10 mb5"><?php echo $data['content'] ?></div>
					<div class="mt10 fs-09 mb10"><?php echo $data['about']; ?></div>
				</TD>
			</TR>
			<?php
		}
		?>
		</tbody>
	</TABLE>

	<div style="height: 90px">&nbsp;</div>

	<DIV class="fixAddBotButton" onclick="doLoad('/content/admin/<?php echo $thisfile; ?>?action=install');">
		<i class="icon-plus"></i>Добавить
	</div>

	<script>

		function actPlugin(id, action, name) {

			$('#message').empty().fadeTo(1, 1).css('display', 'block').append('<div id="loader"><img src="/assets/images/loader.gif"> Выполняю...</div>');

			$.get("/content/admin/<?php echo $thisfile; ?>?action=" + action + "&id=" + id + "&name=" + name, function (data) {

				$('#contentdiv').load('/content/admin/<?php echo $thisfile; ?>');

				$('#message').empty().fadeTo(1, 0);//.css('display', 'block').html('Результат: ' + data);

				Swal.fire({
					imageUrl: '/assets/images/congratulation.svg',
					imageWidth: 50,
					imageHeight: 50,
					position: 'bottom-end',
					//background: "var(--blue)",
					//title: '<div class="white fs-11">Вроде получилось</div>',
					html: '' + data + '',
					icon: 'info',
					showConfirmButton: false,
					timer: 3500
				});

			});

		}

		function readMe(name){

			doLoad("/content/admin/<?php echo $thisfile; ?>?action=readme&name=" + name);

		}

		function uninstallPlugin(id, name) {

			Swal.fire({
					title: 'Вы уверены?',
					text: "Будут удалены все данные, собранные плагином и его настройки!",
					type: 'warning',
					showCancelButton: true,
					confirmButtonColor: '#3085D6',
					cancelButtonColor: '#D33',
					confirmButtonText: 'Да, выполнить',
					cancelButtonText: 'Отменить',
					confirmButtonClass: 'greenbtn',
					cancelButtonClass: 'redbtn'
				}
			).then((result) => {

				if (result.value) {

					$('#message').empty().fadeTo(1, 1).css('display', 'block').append('<div id="loader"><img src="/assets/images/loader.gif"> Выполняю...</div>');

					$.get("/content/admin/<?php echo $thisfile; ?>?action=uninstall&id=" + id + "&name=" + name, function (data) {

						$('#contentdiv').load('/content/admin/<?php echo $thisfile; ?>');

						$('#message').empty().fadeTo(1, 0);

						Swal.fire({
							imageUrl: '/assets/images/congratulation.svg',
							imageWidth: 50,
							imageHeight: 50,
							position: 'bottom-end',
							html: '' + data + '',
							icon: 'info',
							showConfirmButton: false,
							timer: 3500
						});

					});

				}

			});

		}

	</script>
<?php } ?>