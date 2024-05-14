<?php
/* ============================ */
/*         SalesMan CRM         */
/* ============================ */
/* (C) 2019 Vladislav Andreev   */
/*       SalesMan Project       */
/*        www.isaler.ru         */
/*        ver. 2019.x           */

/* ============================ */

use Chats\Chats;

$rootpath = dirname( __DIR__, 3 );
$ypath    = $rootpath."/plugins/socialChats";

error_reporting( E_ERROR );

require_once $rootpath."/inc/config.php";
require_once $rootpath."/inc/dbconnector.php";
require_once $rootpath."/inc/auth_main.php";
require_once $rootpath."/inc/settings.php";
require_once $rootpath."/inc/func.php";

require_once $ypath."/php/autoload.php";
require_once $ypath."/vendor/autoload.php";

$action = $_REQUEST['action'];

$chat = new Chats();

$list = Chats::channelsProvider();

$channels = [];
foreach ($list as $provider){

	$channels[$provider['title']] = $provider['messenger'];

}

//print_r($channels);

//$channels = $chat ::CHANNELS;

if ( $action == 'operator.get' ) {

	$u = $chat -> getOperatorsFull();
	print json_encode_cyr( array_values( $u ) );

	exit();

}
if ( $action == 'operator.delete' ) {

	$iduser = (int)$_REQUEST['iduser'];

	$u = $chat -> getOperators();
	$u = array_values( arraydel( $u, $iduser ) );

	$chat -> setOperators( $u );

	print 'Выполнено';

	exit();

}

if ( $action == 'operator.edit.do' ) {

	$users = array_values( (array)$_REQUEST['users'] );
	$chat -> setOperators( $users );

	print 'Выполнено';

	exit();

}
if ( $action == 'operator.edit.form' ) {

	$access = $chat -> getOperators();

	?>
	<DIV class="zagolovok"><B>Настройка</B></DIV>

	<div class="infodiv bgwhite">Доступ к настройкам плагина имеют только администраторы. Укажите сотрудников, которые будут общаться в чате</div>

	<form action="php/modal.php" method="post" enctype="multipart/form-data" name="Form" id="Form">
		<input type="hidden" id="action" name="action" value="operator.edit.do">

		<div class="divider mb20 mt20">Настройки доступа</div>

		<div class="row" style="overflow-y: auto; max-height: 350px">
			<?php
			$da = $db -> getAll( "SELECT * FROM ".$sqlname."user WHERE secrty = 'yes' and identity = '$identity' ORDER BY title" );
			foreach ( $da as $data ) {
				?>
				<label for="u<?= $data['iduser'] ?>" style="display: inline-block; width: 50%; box-sizing: border-box; float: left; padding-left: 20px">
					<div class="column grid-1">
						<input name="users[<?= $data['iduser'] ?>]" type="checkbox" id="u<?= $data['iduser'] ?>" value="<?= $data['iduser'] ?>" <?php if ( in_array( $data['iduser'], $access ) )
							print 'checked'; ?>>
					</div>
					<div class="column grid-9">
						<?= $data['title'] ?>
					</div>
				</label>
				<?php
			}
			?>
		</div>

		<hr>

		<div class="text-right">
			<A href="javascript:void(0)" onclick="saveAccess()" class="button">Сохранить</A>&nbsp;
			<A href="javascript:void(0)" onclick="DClose()" class="button">Отмена</A>
		</div>

	</form>
	<script>

		$('#dialog').css('width', '700px');

		function saveAccess() {

			var str = $('#Form').serialize();
			var url = $('#Form').attr("action");

			$('#dialog_container').css('display', 'none');

			$.post(url, str, function (data) {

				Swal.fire({
					imageUrl: 'assets/images/success.svg',
					imageWidth: 50,
					imageHeight: 50,
					html: '' + data + '',
					icon: 'info',
					showConfirmButton: false,
					timer: 3500
				});

				//yNotifyMe("CRM. Результат," + data + ",signal.png");

				$app.loadOperators();

				DClose();

			});
		}

	</script>
	<?php

	exit();

}

// настройка логики
if ( $action == "settings.save" ) {

	$params = $_REQUEST;

	unset( $params['action'] );

	customSettings( 'socialChatsSettings', 'put', ["params" => $params] );

	print json_encode_cyr( [
		"status"  => "ok",
		"message" => "Сохранено"
	] );

	exit();

}

//настройки бота
if ( $action == 'channel.check' ) {

	$type           = $_REQUEST['type']."Provider";
	$param['token'] = $_REQUEST['token'];

	//print $_REQUEST['type'];
	//print_r(array_keys($channels));

	if ( array_key_exists( $_REQUEST[ 'type' ], $channels ) ) {

		$type = "Chats\\".$type;

		$provider = new $type();
		$result   = $provider -> check( $_REQUEST );

	}

	print json_encode_cyr( $result );

	exit();

}
if ( $action == 'channel.get' ) {

	$channels = $chat -> getChannels();

	print json_encode_cyr( $channels );

	exit();

}
if ( $action == 'channel.delete' ) {

	$id = (int)$_REQUEST['id'];

	$mes = $chat -> deleteChannels( $id );

	print yimplode( "\n", $mes );

	exit();

}

if ( $action == 'channel.edit.do' ) {

	$id = (int)$_REQUEST['id'];

	$settings = $_REQUEST;

	unset( $settings[ 'id' ], $settings[ 'action' ] );

	$data['type']       = $_REQUEST['type'];
	$data['name']       = trim( $_REQUEST['name'] );
	$data['channel_id'] = trim( $_REQUEST['channel_id'] );
	$data['token']      = trim( $_REQUEST['token'] );
	$data['active']     = $_REQUEST['active'];
	$data['datum']      = current_datumtime();
	$data['settings']   = $settings;

	$result = $chat -> setChannels( $id, $data );

	print json_encode_cyr( $result );

	exit();

}
if ( $action == "channel.fields.form" ) {

	$id   = $_REQUEST['id'];
	$type = $_REQUEST['type'];

	$fields = '';

	if ( file_exists( "Class/Providers/{$type}Provider.php" ) ) {

		$ptype  = "Chats\\{$type}Provider";
		$fields = $ptype ::settingsForm( $id );

	}

	print $fields;

}
if ( $action == "channel.edit.form" ) {

	$id      = (int)$_REQUEST['id'];
	$channel = [];

	if ( $id > 0 )
		$channel = Chats ::channelsInfo( $id );

	$channelExists = $db -> getCol( "SELECT type FROM ".$sqlname."chats_channels WHERE identity = '$identity'" );

	?>
	<DIV class="zagolovok"><B>Редактировать канал</B></DIV>
	<form action="php/modal.php" method="post" enctype="multipart/form-data" name="Form" id="Form">
		<input type="hidden" id="action" name="action" value="channel.edit.do">
		<input name="id" type="hidden" id="id" value="<?= $id ?>">

		<div id="formtabse" style="max-height: 80vh; overflow-y: auto; overflow-x:hidden;">

			<div class="row">

				<div class="column grid-10 relative">
					<span class="label">Канал:</span>
					<span class="select">
						<select id="type" name="type" class="wp100 required">
							<option value="">--Укажите тип канала--</option>
							<?php
							$auth = 'bot';
							foreach ( $channels as $type => $name ) {

								$authType = [];

								if ( file_exists( "Class/Providers/{$type}Provider.php" ) ) {

									$authType = true;

								}

								$s = ($type == $channel['type']) ? "selected" : "";

								$t = (!$authType) ? "disabled" : "";

								print '<option value="'.$type.'" '.$s.' '.$t.'>'.$name.'</option>';

							}
							?>
						</select>
					</span>
				</div>

			</div>

			<div class="row">

				<div class="column grid-10 relative">

					<div class="checkbox">
						<label class="pb5">
							<input name="active" type="checkbox" id="active" value="on" <?php echo($channel['active'] == 'on' ? "checked" : "") ?>>
							<span class="custom-checkbox"><i class="icon-ok"></i></span>
							Активен
						</label>
					</div>

				</div>

			</div>

			<div class="row" data-id="fields"></div>

		</div>

		<hr>

		<div class="text-right">

			<a href="javascript:void(0)" onclick="getToken('blank.html')" class="button greenbtn pull-aright hidden" data-id="oauth"><i class="icon-ok-circled"></i>Получить ключ</a>

			<A href="javascript:void(0)" onclick="saveForm()" class="button">Сохранить</A>&nbsp;
			<A href="javascript:void(0)" onclick="DClose()" class="button">Отмена</A>

		</div>

	</form>

	<script>

		$(function () {

			$('#dialog').css('width', '500px');
			$('#type').trigger('change');

		});

		$('#type').on('change', function () {

			var id = <?php echo $id ?>;
			var type = $('option:selected', this).val();
			var url = $('#Form').attr("action");

			$.post(url, 'action=channel.fields.form&id=' + id + '&type=' + type, function (data) {

				$('.row[data-id="fields"]').html(data);

			})
				.done(function () {

					$('#dialog').center();

				});

		});

		function checkConnection() {

			$('.rezult').append('<div id="loader"><img src="/assets/images/loading.svg"></div>');

			var str = 'action=channel.check&type=' + $('#type').val() + '&token=' + $('#token').val();
			var url = $('#Form').attr("action");

			$.post(url, str, function (data) {

				if (data.ok === true) {

					$('.rezult').html('Ответ: <b>Соединение установлено</b>');
					$('#name').val(data.result.username);
					$('#channel_id').val(data.result.id);

				}
				else $('.rezult').html('Ошибка: <b>' + data.message + '</b>');

			}, 'json');

		}

		function saveForm() {

			var str = $('#Form').serialize();
			var url = $('#Form').attr("action");

			$('#dialog_container').css('display', 'none');

			$.post(url, str, function (data) {

				if (data.status === 'ok') {

					Swal.fire({
						imageUrl: 'assets/images/success.svg',
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
						imageUrl: 'assets/images/error.svg',
						imageWidth: 50,
						imageHeight: 50,
						html: '' + data.message + '',
						icon: 'info',
						showConfirmButton: false,
						timer: 3500
					});

				}

				$app.loadChannels();

				DClose();

				$(document).off('change keyup', '#channel_id');
				$('#type').off('change');

			}, 'json');
		}

	</script>
	<?php

	exit();

}

// Генерируем js-код для сайта
if ( $action == 'getJsCode.do' ) {

	$params = $_REQUEST;

	unset( $params['action'] );
	unset( $params['content'] );

	$rgb = hexToRgb( $params['color'] );

	$params['shadow'] = "rgba(".$rgb['r'].",".$rgb['g'].",".$rgb['b'].",0.5)";

	customSettings( 'socialChatsWiget', 'put', ["params" => $params] );

	print 'ok';

	exit();

}
if ( $action == 'getJsCode' ) {

	$wiget = customSettings( "socialChatsWiget", "get" );

	if ( empty( $wiget ) ) {

		$wiget = [
			'key'    => md5( $identity.$_SERVER["HTTP_HOST"] ),
			'bottom' => 50,
			'right'  => 50,
			'color'  => '#0D47A1'
		];

	}

	if($wiget['key'] == '') {
		$wiget['key'] = md5( $identity.$_SERVER["HTTP_HOST"] );
	}

	?>
	<DIV class="zagolovok"><B>Код для сайта</B></DIV>
	<form action="php/modal.php" method="post" enctype="multipart/form-data" name="Form" id="Form">
		<input type="hidden" id="action" name="action" value="getJsCode.do">

		<div class="infodiv p10 mb5">Скопируйте данный код и установите на сайт перед закрывающим тегом <b>body</b>
		</div>

		<div id="formtabse" class="flex-container" style="max-height: 70vh; overflow-y: auto; overflow-x: hidden">

			<div class="flex-string wp100 pl10 pr10">

				<div class="flex-container0 box--child flex-vertical">

					<div class="divider mt10 mb10">Позиция</div>

					<div class="flex-container box--child wp50 mt10 cleared">

						<div class="flex-string wp100 uppercase fs-07 Bold pl5">Снизу</div>
						<div class="flex-string wp100 relativ">

							<input type="number" id="pozbottom" name="bottom" value="<?php echo $wiget['bottom'] ?>" class="wp90" placeholder="Снизу" step="10" min="20"> px

						</div>

					</div>
					<div class="flex-container box--child wp50 mt10 cleared">

						<div class="flex-string wp100 uppercase fs-07 Bold pl5">Справа</div>
						<div class="flex-string wp100 relativ">

							<input type="number" id="pozright" name="right" value="<?php echo $wiget['right'] ?>" class="wp90" placeholder="Снизу" step="10" min="20"> px

						</div>

					</div>

				</div>

			</div>

			<div class="flex-string wp100 pl10 pr10">

				<div class="flex-container box--child flex-vertical">

					<div class="divider mt10 mb10">Цвет виджета</div>

					<div class="flex-container box--child wp100 mt10 cleared">

						<div class="flex-string wp100 uppercase fs-07 Bold pl5 hidden">Цвет виджета</div>
						<div class="flex-string wp100 relativ">

							<input type="color" id="color" name="color" value="<?php echo $wiget['color'] ?>" class="wp100 p0">

						</div>

					</div>

				</div>

			</div>

			<div class="divider mt20 mb10">Код</div>

			<div class="flex-string wp100">
				<div id="coder"></div>
				<textarea id="content" name="content" class="hidden"><!-- Start -- SaleaManCRM Chat Widget -->
<script type="text/javascript">

   var salesman = {
	   identity: '<?php echo $identity?>',
	   host: '<?php echo $_SERVER["HTTP_HOST"]?>',
	   apkey: '<?php echo $wiget['key']?>'
   };

   (function (d) {
	   var s = d.createElement('script');
	   s.type = 'text/javascript';
	   s.id = 'crmScript';
	   s.async = true;
	   s.src = '//<?php echo $_SERVER["HTTP_HOST"]?>/plugins/socialChats/assets/js/chat.out.js';

	   (document.getElementsByTagName("head")[0] || document.documentElement).appendChild(s);

   })(document);

</script>
<!-- End -- SaleaManCRM Chat Widget --></textarea>
			</div>

		</div>

	</form>

	<hr>

	<div class="text-right">

		<A href="javascript:void(0)" onclick="saveForm()" class="button">Сохранить</A>&nbsp;
		<A href="javascript:void(0)" onclick="DClose()" class="button">Отмена</A>

	</div>

	<link type="text/css" rel="stylesheet" href="/assets/js/codemirror/lib/codemirror.css">
	<link type="text/css" rel="stylesheet" href="/assets/js/codemirror/theme/idea.css">
	<link type="text/css" rel="stylesheet" href="/assets/js/codemirror/addon/fold/foldgutter.css">
	<link type="text/css" rel="stylesheet" href="/assets/js/codemirror/addon/lint/lint.css">

	<script src="/assets/js/codemirror/lib/codemirror.js"></script>
	<script src="/assets/js/codemirror/addon/fold/foldcode.js"></script>
	<script src="/assets/js/codemirror/addon/fold/foldgutter.js"></script>
	<script src="/assets/js/codemirror/addon/fold/brace-fold.js"></script>
	<script src="/assets/js/codemirror/addon/fold/xml-fold.js"></script>
	<script src="/assets/js/codemirror/addon/fold/indent-fold.js"></script>
	<script src="/assets/js/codemirror/addon/fold/comment-fold.js"></script>
	<script src="/assets/js/codemirror/addon/lint/lint.js"></script>
	<script src="/assets/js/codemirror/addon/lint/css-lint.js"></script>
	<script src="/assets/js/codemirror/addon/lint/html-lint.js"></script>
	<script src="/assets/js/codemirror/addon/selection/active-line.js"></script>
	<script src="/assets/js/codemirror/addon/edit/closetag.js"></script>
	<script src="/assets/js/codemirror/addon/edit/matchtags.js"></script>
	<script src="/assets/js/codemirror/mode/htmlmixed/htmlmixed.js"></script>
	<script src="/assets/js/codemirror/mode/xml/xml.js"></script>
	<script src="/assets/js/codemirror/mode/css/css.js"></script>
	<script>

		var editorCodeMirror;

		$(function () {

			editorCodeMirror = CodeMirror(document.getElementById("coder"), {
				value: $('#content').text(),
				mode: "htmlmixed",
				lineNumbers: true,
				lineWrapping: true,
				smartIndent: true,
				tabSize: 2,
				indentWithTabs: true,
				theme: 'idea',
				foldGutter: true,
				gutters: ["CodeMirror-linenumbers", "CodeMirror-foldgutter"],
				lint: false,
				styleActiveLine: true,
				styleActiveSelected: true,
				autoCloseTags: true,
				matchTags: {bothTags: true}
			});

			$("#dialog").css("width", "600px").center();

		});

		function saveForm() {

			var str = $('#Form').serialize();
			var url = $('#Form').attr("action");

			$('#dialog_container').css('display', 'none');

			$.post(url, str, function (data) {

				if (data === 'ok') {

					Swal.fire({
						imageUrl: 'assets/images/success.svg',
						imageWidth: 50,
						imageHeight: 50,
						//position: 'bottom-end',
						html: "Успешно",
						icon: 'info',
						showConfirmButton: false,
						timer: 3500
					});

				}
				else {

					Swal.fire({
						imageUrl: 'assets/images/error.svg',
						imageWidth: 50,
						imageHeight: 50,
						html: "Упс, какая-то ошибка",
						icon: 'info',
						showConfirmButton: false,
						timer: 3500
					});

				}

				DClose();

			});
		}

	</script>

	<?php

	exit();

}

// Получаем иконки каналов
if ( $action == 'getChannelUrls' ) {

	$icons = [];

	$list = $chat -> getChannels();

	foreach ( $list as $item ) {

		if ( $item['active'] ) {
			$icons[] = [
				"icon" => strtolower( $item['otype'] ),
				"uri"  => $item['uri']
			];
		}

	}

	print json_encode_cyr( $icons );

	exit();

}