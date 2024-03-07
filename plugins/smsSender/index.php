<?php
/* ============================ */
/* (C) 2015 Vladislav Andreev   */
/*        100crm Project        */
/*        www.100crm.ru         */
/*           ver. 8.30          */
/* ============================ */

set_time_limit( 0 );

error_reporting( E_ERROR );

$rootpath = dirname( __DIR__, 2 );
$ypath    = $rootpath."/plugins/smsSender/";

include $rootpath."/inc/config.php";
include $rootpath."/inc/dbconnector.php";
include $rootpath."/inc/auth.php";
include $rootpath."/inc/func.php";
include $rootpath."/inc/settings.php";

$action = $_REQUEST['action'];

$identity = $GLOBALS['identity'];
$iduser1  = (int)$GLOBALS['iduser1'];

$fpath = '';

if ( $isCloud ) {

	//создаем папки хранения файлов
	createDir( "data/".$identity );

	$fpath = $identity.'/';

}

$scheme = $_SERVER['HTTP_SCHEME'] ?? (((isset( $_SERVER['HTTPS'] ) && $_SERVER['HTTPS'] != 'off') || 443 == $_SERVER['SERVER_PORT']) ? 'https://' : 'http://');

function sendSMS($action, $type, $params = []): array {

	require_once "../_core/core.php";

	$r     = [];
	$fpath = $GLOBALS['fpath'];
	$ypath = $GLOBALS['ypath'];

	switch ($type) {

		case "sms.ru":

			$answer = [
				"-1"  => "Сообщение не найдено.",
				"100" => "Сообщение находится в очереди",
				"101" => "Сообщение передается оператору",
				"102" => "Сообщение отправлено (в пути)",
				"103" => "Сообщение доставлено",
				"104" => "Не может быть доставлено: время жизни истекло",
				"105" => "Не может быть доставлено: удалено оператором",
				"106" => "Не может быть доставлено: сбой в телефоне",
				"107" => "Не может быть доставлено: неизвестная причина",
				"108" => "Не может быть доставлено: отклонено",
				"200" => "Неправильный api_id",
				"201" => "Не хватает средств на лицевом счету",
				"202" => "Неправильно указан получатель",
				"203" => "Нет текста сообщения",
				"204" => "Имя отправителя не согласовано с администрацией",
				"205" => "Сообщение слишком длинное (превышает 8 СМС)",
				"206" => "Будет превышен или уже превышен дневной лимит на отправку сообщений",
				"207" => "На этот номер (или один из номеров) нельзя отправлять сообщения, либо указано более 100 номеров в списке получателей",
				"208" => "Параметр time указан неправильно",
				"209" => "Вы добавили этот номер (или один из номеров) в стоп-лист",
				"210" => "Используется GET, где необходимо использовать POST",
				"211" => "Метод не найден",
				"212" => "Текст сообщения необходимо передать в кодировке UTF-8 (вы передали в другой кодировке)",
				"220" => "Сервис временно недоступен, попробуйте чуть позже.",
				"230" => "Сообщение не принято к отправке, так как на один номер в день нельзя отправлять более 60 сообщений.",
				"300" => "Неправильный token (возможно истек срок действия, либо ваш IP изменился)",
				"301" => "Неправильный пароль, либо пользователь не найден",
				"302" => "Пользователь авторизован, но аккаунт не подтвержден (пользователь не ввел код, присланный в регистрационной смс)"
			];

			define( 'BASEURL', 'https://sms.ru/' );

			$result  = '';
			$balance = '';
			$uid     = 0;
			$cost    = 0;
			$count   = 0;
			$apikey  = $params['apikey'];

			$settings = json_decode( file_get_contents( $ypath.'data/'.$fpath.'settings.json' ), true );

			if ( !$apikey )
				$apikey = $settings['apikey'];

			$data = [
				"api_id" => $apikey
			];

			switch ($action) {
				//отправка
				case "send":

					$data['to']   = $params['phone'];
					$data['text'] = $params['content'];

					$response = sendRequest( BASEURL."sms/send", $data );

					[
						$code,
						$uid
					] = explode( "\n", $response );

					$result = strtr( $code, $answer );

				break;
				//получение статуса
				case "status":

					$data['id'] = $params['uid'];

					$response = sendRequest( BASEURL."sms/status", $data );

					[
						$code,
						$balance
					] = explode( "\n", $response );

					$result = strtr( $code, $answer );

				break;
				//получение стоимости
				case "cost":

					$data['to']   = $params['phone'];
					$data['text'] = $params['content'];

					//print_r($data);

					$response = sendRequest( BASEURL."sms/cost", $data );

					[
						$code,
						$cost,
						$count
					] = explode( "\n", $response );

					$result = strtr( $code, $answer );

				break;
				//баланс
				case "balance":

					$response = sendRequest( BASEURL."my/balance", $data );

					[
						$code,
						$balance
					] = explode( "\n", $response );

					$result = strtr( $code, $answer );

				break;
			}

			$r = [
				"result"  => $result,
				"balance" => $balance,
				"uid"     => $uid,
				"cost"    => $cost,
				"count"   => $count,
				"apikey"  => $apikey
			];

		break;
		case "smsaero.ru":

			define( 'BASEURL', 'https://gate.smsaero.ru/' );

			$login     = $params['login'];
			$password  = $params['password'];
			$signature = $params['signature'];

			$answer = [
				"accepted"                                                       => "Сообщение принято сервисом",
				"empty field. reject"                                            => "Не все обязательные поля заполнены",
				"incorrect user or password"                                     => "Ошибка авторизации",
				"no credits"                                                     => "Недостаточно средств на балансе",
				"incorrect sender name. reject"                                  => "Неверная (незарегистрированная) подпись отправителя",
				"incorrect destination adress. reject"                           => "Неверно задан номер телефона (формат 71234567890)",
				"incorrect date. reject"                                         => "Неправильный формат даты",
				"in blacklist. reject"                                           => "Телефон находится в черном списке. Внимание! Данные номера исключаются из рассылки при использование типа отправки sendtogroup",
				"incorrect language in '...' use the cyrillic or roman alphabet" => "в слове '...' одновременно используются символы из кириллицы и латиницы"
			];

			$settings = json_decode( file_get_contents( $ypath.'data/'.$fpath.'settings.json' ), true );

			if ( !$login )
				$login = $settings['login'];
			if ( !$password )
				$password = $settings['password'];
			if ( !$signature )
				$signature = $settings['signature'];

			$data = [
				"user"     => $login,
				"password" => md5( $password ),
				"from"     => $signature,
				"answer"   => "json",
				"type"     => "3",
			];

			switch ($action) {
				//отправка
				case "send":

					$data['to']   = $params['phone'];
					$data['text'] = $params['content'];

				break;
				//получение статуса
				case "status":

					$data['id'] = $params['uid'];

				break;
				//получение стоимости. не поддерживается сервисом
				case "cost":

					$data['to']   = $params['phone'];
					$data['text'] = $params['content'];

					$response = sendRequest( BASEURL."balance/", $data );

				break;
				//баланс
				case "balance":

				break;
			}

			$response = file_get_contents( BASEURL.$action."/?".http_build_query( $data ) );

			$result = json_decode( $response, true );

			//print_r($result);

			$r = [
				"result"  => strtr( $result['result'], $answer ),
				"balance" => $result['balance'],
				"uid"     => $result['id'],
				"text"    => $result['reason']
			];

		break;

	}

	return $r;

}

$periodStart = str_replace( "/", "-", $_REQUEST['periodStart'] );
$periodEnd   = str_replace( "/", "-", $_REQUEST['periodEnd'] );

if ( !$periodStart ) {

	$period = getPeriod( 'month' );

	$periodStart = $period[0];
	$periodEnd   = $period[1];

}

$access = [];

//загружаем настройки доступа
$file     = $ypath.'data/'.$fpath.'access.json';
$settings = json_decode( file_get_contents( $ypath.'data/'.$fpath.'settings.json' ), true );

//если настройки произведены, то загружаем их
if ( file_exists( $file ) && $action != 'access.do' ) {

	$access = json_decode( file_get_contents( $file ), true );

}
else {

	$access = [(int)$db -> getCol( "SELECT iduser FROM {$sqlname}user WHERE isadmin = 'on' and secrty = 'yes' and identity = '$identity' ORDER BY title" )];

}

//отправка сообщений
if ( $action == 'sms.do' ) {

	$param['content'] = $_REQUEST['content'];
	$param['phone']   = $_REQUEST['phone'];
	$clid = (int)$_REQUEST['clid'];
	$pid  = (int)$_REQUEST['pid'];

	$rez = sendSMS( 'send', $settings['type'], $param );
	
	if($clid == 0 && $pid == 0) {
		
		//определяем клиента/контакт
		$r = getxCallerID( $param['phone'] );
		
		$clid = $r['clid'];
		$pid  = $r['pid'];
		
	}

	//$db -> query( "INSERT INTO {$sqlname}logsms (id, uid, datum, phone, clid, pid, iduser, content, status, identity) VALUES (null, '".$rez['uid']."', '".current_datumtime()."', '".$param['phone']."', '".$clid."', '".$pid."', '".$iduser1."', '".$param['content']."', '0', '$identity')" );

	$db -> query( "INSERT INTO {$sqlname}logsms SET ?u", [
		"uid"      => $rez['uid'],
		"datum"    => current_datumtime(),
		"phone"    => $param['phone'],
		"clid"     => (int)$clid,
		"pid"      => (int)$pid,
		"iduser"   => (int)$iduser1,
		"content"  => $param['content'],
		"status"   => 0,
		"identity" => $identity
	] );

	//$db -> query( "INSERT INTO {$sqlname}history (cid,datum,iduser,clid,pid,des,tip,identity) VALUES (NULL, '".current_datumtime()."', '".$iduser1."', '".$clid."', '".$pid."', '".$param['content']."', 'СМС', '".$identity."')" );

	$db -> query( "INSERT INTO {$sqlname}history SET ?u", [
		"datum"    => current_datumtime(),
		"iduser"   => $iduser1,
		"clid"     => (int)$clid,
		"pid"      => (int)$pid,
		"des"      => $param['content'],
		"tip"      => 'СМС',
		"identity" => $identity
	] );

	print json_encode_cyr( $rez );

	exit();

}
if ( $action == 'sms.cost' ) {

	$param['content'] = $_REQUEST['content'];
	$param['phone']   = $_REQUEST['phone'];

	$rez = sendSMS( 'cost', $settings['type'], $param );

	print json_encode_cyr( $rez );

	exit();

}
if ( $action == 'sms.status' ) {

	$param['uid'] = $_REQUEST['uid'];

	$rez = sendSMS( 'status', $settings['type'], $param );

	print json_encode_cyr( $rez );

	exit();

}
if ( $action == 'sms.balance' ) {

	if ( $_REQUEST['tip'] == '' ) {

		$_REQUEST['tip']       = $settings['type'];
		$_REQUEST['apikey']    = $settings['apikey'];
		$_REQUEST['login']     = $settings['login'];
		$_REQUEST['password']  = $settings['password'];
		$_REQUEST['signature'] = $settings['signature'];

	}

	$rez = sendSMS( 'balance', $_REQUEST['tip'], [
		"apikey"    => $_REQUEST['apikey'],
		"login"     => $_REQUEST['login'],
		"password"  => $_REQUEST['password'],
		"signature" => $_REQUEST['signature']
	] );

	print json_encode_cyr( $rez );

	exit();

}

if ( $action == "sms.compose" ) {

	$clid  = (int)$_REQUEST['clid'];
	$phone = $_REQUEST['phone'];
	?>
	<DIV class="zagolovok"><B>Отправка СМС:</B></DIV>
	<form action="index.php" method="post" enctype="multipart/form-data" name="Form" id="Form">
		<input type="hidden" id="action" name="action" value="sms.do">
		<input name="clid" type="hidden" id="clid" value="<?= $clid ?>">

		<div class="row">

			<div class="column grid-7">
				<textarea name="content" id="content" style="width: 99%; height: 300px; max-height: 300px; font-size: 1.2em"></textarea>
			</div>
			<div class="column grid-3">
				<div>
					<span class="fs-10">Номер:</span>
					<input type="text" name="phone" id="phone" style="width:95%" value='<?= $phone ?>'/>
				</div>
				<hr>
				<div>
					<span class="fs-10">Шаблон:</span>
					<div class="select">
						<select name="tpl" id="tpl" style="width:99%">
							<option>--Выбор--</option>
							<?php
							$da = $db -> getAll( "select * from {$sqlname}logsms_tpl WHERE identity = '$identity'" );
							foreach ( $da as $data ) {
								print '<option value="'.$data['id'].'">'.$data['name'].'</option>';
							}
							?>
						</select>
					</div>
				</div>
				<hr>
				<div class="hidden">
					<div><a href="javascript:void(0)" onclick="costSMS()" title="">Узнать стоимость</a></div>
					<div class="cost"></div>
					<hr>
				</div>
				<div class="balance blue"></div>
			</div>

		</div>

		<hr>

		<div align="right">
			<div class="wordcount pull-left paddleft10 gray">0</div>
			<A href="javascript:void(0)" onClick="sendSMS()" class="button">Отправить</A>&nbsp;
			<A href="javascript:void(0)" onClick="DClose()" class="button">Отмена</A>
		</div>
	</form>

	<script>

		$(function () {

			$('#dialog').css('width', '800px');
			balanceSMS();

			$("#phone").autocomplete({
				dropdownWidth: 'auto',
				appendMethod: 'replace',
				replaceAccentsForRemote: true,
				limit: 10,
				valid: function () {
					return true;
				},
				source: [
					function (q, add) {
						$.getJSON("index.php?tip=ext&action=get.client&q=" + q, function (resp) {
							add(resp.data)
						})
					}
				]
			});

			$(window).bind('change keyup', '#content', function () {
				countTXT();
			});

		});

		function countTXT() {
			var text = $('#content').val();
			var count = text.length;

			$('.wordcount').html('Число символов: ' + count);
		}

	</script>
	<?php

	exit();
}
if ( $action == "sms.compose.ext" ) {

	if ( $settings['type'] == '' && $settings['apikey'] == '' && $settings['login'] == '' ) {
		print '
			<div class="attention" align="left">
				<span><i class="icon-cancel-circled broun icon-5x pull-left"></i></span>
				<b class="broun uppercase">Предупреждение:</b>
				<br><br>
				Плагин не настроен. Обратитесь к своему администратору CRM.<br><br><br>
			</div>
			';
		exit();
	}

	$clid  = (int)$_REQUEST['clid'];
	$pid   = (int)$_REQUEST['pid'];
	$phone = $_REQUEST['phone'];

	?>
	<DIV class="zagolovok"><B>Отправка СМС:</B></DIV>
	<form action="/plugins/smsSender/index.php" method="post" enctype="multipart/form-data" name="Form" id="Form">
		<input type="hidden" id="action" name="action" value="sms.do">
		<input name="clid" type="hidden" id="clid" value="<?= $clid ?>">
		<input name="pid" type="hidden" id="pid" value="<?= $pid ?>">

		<div class="row">

			<div class="column grid-7">
				<textarea name="content" id="content" style="width: 99%; height: 300px; max-height: 300px; font-size: 1.2em"></textarea>
			</div>
			<div class="column grid-3">
				<div>
					<span class="fs-10">Номер:</span>
					<input type="text" name="phone" id="phone" style="width:95%" value='<?= $phone ?>'/>
				</div>
				<hr>
				<div>
					<span class="fs-10">Шаблон:</span>
					<div class="select">
						<select name="tpl" id="tpl" style="width:99%">
							<option>--Выбор--</option>
							<?php
							$da = $db -> getAll( "select * from {$sqlname}logsms_tpl WHERE identity = '$identity'" );
							foreach ( $da as $data ) {
								print '<option value="'.$data['id'].'">'.$data['name'].'</option>';
							}
							?>
						</select>
					</div>
				</div>
				<hr>
				<div class="hidden">
					<div><a href="javascript:void(0)" onclick="costSMS()" title="">Узнать стоимость</a></div>
					<div class="cost"></div>
				</div>
				<hr>
				<div class="balance blue"></div>
			</div>

		</div>

		<hr>

		<div align="right">
			<div class="wordcount pull-left paddleft10 gray">0</div>
			<A href="javascript:void(0)" onclick="sendSMS()" class="button">Отправить</A>&nbsp;
			<A href="javascript:void(0)" onclick="DClose()" class="button">Отмена</A>
		</div>
	</form>
	<script>

		$('#dialog').css('width', '800px');
		balanceSMS();

		$("#phone").autocomplete('plugins/smsSender/index.php?action=get.client',
			{
				autofill: false,
				minChars: 2,
				cacheLength: 2,
				maxItemsToShow: 10,
				selectFirst: false,
				multiple: false,
				delay: 10,
				matchSubset: 1,
				formatItem: function (data, j, n, value) {
					return '<div>' + data[0] + '&nbsp;[<span class="red">' + data[2] + '</span>]</div>';
				},
				formatResult: function (data) {
					return data[0];
				}
			}
		);

		$(document).on('change', '#content', function () {
			countTXT();
		});

		$('#tpl').on('change', function () {

			var id = $('#tpl option:selected').val();

			$.get('plugins/smsSender/index.php?action=tpl.get&id=' + id, function (data) {
				$('#content').val(data).trigger('change');
			});

		});

		function sendSMS() {

			var str = $('#Form').serialize();

			$('#dialog_container').css('display', 'none');
			$('#message').empty().fadeTo(1, 1).css('display', 'block').append('<div id="loader"><img src="/assets/images/loader.gif"> Отправляю...</div>');

			$.post("plugins/smsSender/index.php", str, function (data) {

				$('#message').fadeTo(1, 1).css('display', 'block').html('Результат: ' + data.result);
				setTimeout(function () {
					$('#message').fadeTo(1000, 0);
				}, 20000);

				DClose();

			}, 'json');
		}

		function balanceSMS() {

			var str = 'action=sms.balance';

			$('.balance').empty().append('<div id="loader"><img src="/assets/images/loading.gif"></div>');

			$.post("plugins/smsSender/index.php", str, function (data) {

				$('.balance').html('Баланс: <b>' + data.balance + '</b> руб.');

			}, 'json');
		}

		function costSMS() {

			if ($('#phone').val() == '') alert('Не указано получатель');
			else if ($('#content').val() == '') alert('Пустое сообщение');
			else {

				var str = 'action=sms.cost&phone=' + $('#phone').val() + '&content=' + $('#content').val();

				$('.cost').empty().append('<div id="loader"><img src="/assets/images/loading.gif"></div>');

				$.post("plugins/smsSender/index.php", str, function (data) {

					$('.cost').html('Стоимость: <b class="green">' + data.cost + '</b> руб.;<br>Сообщений: <b class="green">' + data.count + '</b> <i class="icon-info-circled gray" title="' + data.result + '"></i>');

				}, 'json');

			}
		}

		function countTXT() {
			var text = $('#content').val();
			var count = text.length;

			$('.wordcount').html('Число символов: ' + count);
		}

	</script>
	<?php

	exit();
}

if ( $action == "get.client" ) {

	$tip = $_REQUEST['tip'];
	$q   = texttosmall( $_REQUEST["q"] );

	$phone = substr( str_replace( [
		"+",
		"(",
		")",
		"-",
		" "
	], "", $q ), 1 );

	$query = "SELECT LOWER(title) as title2, title, clid, iduser, phone, fax FROM {$sqlname}clientcat WHERE (title LIKE '%".$q."%' or (replace(replace(replace(replace(replace(phone, '+', ''), '(', ''), ')', ''), ' ', ''), '-', '') LIKE '%".$phone."%' or replace(replace(replace(replace(replace(fax, '+', ''), '(', ''), ')', ''), ' ', ''), '-', '') LIKE '%".$phone."%')) and identity = '$identity'";

	$da = $db -> getAll( $query );

	$list = [];

	if ( $tip != 'ext' ) {

		foreach ( $da as $data ) {

			$phone1 = yexplode( ",", $data['phone'] );
			$phone2 = yexplode( ",", $data['fax'] );

			$phones = array_unique( array_merge( $phone1, $phone2 ) );

			foreach ($phones as $phone) {

				$x = prepareMobPhone( $phone );
				$y = prepareMobPhone( $q );

				if (
					stripos( substr( $x, 1 ), substr( prepareMobPhone( $q ), 1 ) ) !== false &&
					str_split($x)[1] == '9'
				) {
					print prepareMobPhone( $phone )."|".$data['title']."|".current_user( $data['iduser'] )."\n";
				}

			}

		}

	}
	else {

		foreach ( $da as $data ) {

			$phone1 = yexplode( ",", $data['phone'] );
			$phone2 = yexplode( ",", $data['fax'] );

			$phones = array_unique( array_merge( $phone1, $phone2 ) );

			foreach ($phones as $phone) {

				$x = prepareMobPhone( $phone );
				$y = prepareMobPhone( $q );

				if ( 
					stripos( substr( prepareMobPhone( $phone ), 1 ), substr( prepareMobPhone( $q ), 1 ) ) !== false &&
					str_split($x)[1] == '9'
				) {
					$list[] = prepareMobPhone( $phone );
				}
			}

		}

		print json_encode_cyr( ["data" => $list] );

	}

	exit();
}

//настройка шаблонов сообщений
if ( $action == 'tpl.do' ) {

	$id = (int)$_REQUEST['id'];

	$name    = $_REQUEST['name'];
	$content = $_REQUEST['content'];
	$date    = current_datumtime();

	if ( $id < 1 ) {

		try {
			$db -> query( "INSERT INTO {$sqlname}logsms_tpl (id, name, content, identity) VALUES (null, '$name', '$content', '$identity')" );
			$mes = 'Готово';
		}
		catch ( Exception $e ) {

			$mes = 'Ошибка'.$e -> getMessage().' в строке '.$e -> getCode();

		}

	}
	else {

		try {
			$db -> query( "UPDATE {$sqlname}logsms_tpl SET name = '$name', content = '$content', datum = '$date' WHERE id = '$id'" );
			$mes = 'Готово';
		}
		catch ( Exception $e ) {

			$mes = 'Ошибка'.$e -> getMessage().' в строке '.$e -> getCode();

		}

	}

	print $mes;

	exit();
}
if ( $action == 'tpl.delete' ) {

	$id = (int)$_REQUEST['id'];

	try {
		$db -> query( "DELETE FROM {$sqlname}logsms_tpl WHERE id = '$id'" );
		$mes = 'Готово';
	}
	catch ( Exception $e ) {

		$mes = 'Ошибка'.$e -> getMessage().' в строке '.$e -> getCode();

	}

	print $mes;

	exit();
}
if ( $action == 'tpl.get' ) {

	$id = (int)$_REQUEST['id'];

	if ( $id > 0 ) {

		$tpl = $db -> getOne( "select content from {$sqlname}logsms_tpl WHERE id = '$id'" );

	}

	print $tpl;

	exit();

}
if ( $action == "tpl" ) {

	$id = (int)$_REQUEST['id'];

	if ( $id > 0 ) {

		$tpl = $db -> getRow( "select * from {$sqlname}logsms_tpl WHERE id = '$id'" );

	}

	//print_r($tpl);

	?>
	<DIV class="zagolovok"><B>Редактор шаблона</B></DIV>
	<form action="index.php" method="post" enctype="multipart/form-data" name="Form" id="Form">
		<input type="hidden" id="action" name="action" value="tpl.do">
		<input name="id" type="hidden" id="id" value="<?= $id ?>">

		<div class="row">


			<div class="column grid-10">
				<span class="fs-10">Название:</span>
				<input type="text" name="name" id="name" style="width:95%" value="<?= $tpl['name'] ?>"/>
			</div>
			<div class="column grid-10">
				<textarea name="content" id="content" style="width: 99%; height: 200px; max-height: 200px; font-size: 1.2em"><?= $tpl['content'] ?></textarea>
			</div>

		</div>

		<hr>

		<div align="right">
			<A href="javascript:void(0)" onclick="saveTpl()" class="button">Сохранить</A>&nbsp;
			<A href="javascript:void(0)" onclick="DClose()" class="button">Отмена</A>
		</div>
	</form>

	<script>

		$('#dialog').css('width', '700px');

	</script>
	<?php

	exit();
}

//настройка доступа
if ( $action == 'access.do' ) {

	$preusers = $_REQUEST['preusers'];

	$params = json_encode_cyr( $preusers );

	$f    = $ypath.'data/'.$fpath.'access.json';
	$file = fopen( $f, 'wb' );

	if ( !$file ) {
		$rez = 'Не могу открыть файл';
	}
	else {

		$rez = fwrite( $file, $params ) === false ? 'Ошибка записи' : 'Записано';

		fclose( $file );

	}

	print $rez;

	exit();

}
if ( $action == "access" ) {
	?>
	<DIV class="zagolovok"><B>Доступы пользователей:</B></DIV>
	<form action="index.php" method="post" enctype="multipart/form-data" name="Form" id="Form">
		<input type="hidden" id="action" name="action" value="access.do">

		<div class="row" style="overflow-y: auto; max-height: 350px">
			<?php
			$da = $db -> getAll( "SELECT * FROM {$sqlname}user WHERE secrty = 'yes' and identity = '$identity' ORDER BY title" );
			foreach ( $da as $data ) {
				?>
				<label style="display: inline-block; width: 50%; box-sizing: border-box; float: left; padding-left: 20px">
					<div class="column grid-1">
						<input name="preusers[]" type="checkbox" id="preusers[]" value="<?= $data['iduser'] ?>" <?php if ( in_array( $data['iduser'], $access ) )
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

		<div align="right">
			<A href="javascript:void(0)" onclick="saveAccess()" class="button">Сохранить</A>&nbsp;
			<A href="javascript:void(0)" onclick="DClose()" class="button">Отмена</A>
		</div>
	</form>
	<script>

		$('#dialog').css('width', '700px');

		function saveAccess() {

			var str = $('#Form').serialize();

			$('#dialog_container').css('display', 'none');

			$.post("index.php", str, function (data) {

				yNotifyMe("CRM. Результат," + data + ",signal.png");

				DClose();

			});
		}
	</script>
	<?php

	exit();
}

//настройки подключения к сервису
if ( $action == 'settings.do' ) {

	$apikey    = $_REQUEST['apikey'];
	$login     = $_REQUEST['login'];
	$password  = $_REQUEST['password'];
	$signature = $_REQUEST['signature'];
	$type      = $_REQUEST['type'];

	$params = json_encode_cyr( [
		"type"      => $type,
		"apikey"    => $apikey,
		"login"     => $login,
		"password"  => $password,
		"signature" => $signature
	] );

	$f    = $ypath.'data/'.$fpath.'settings.json';
	$file = fopen( $f, "w" );

	if ( !$file ) {
		$rez = 'Не могу открыть файл';
	}
	else {

		$rez = fwrite( $file, $params ) === false ? 'Ошибка записи' : 'Записано';

		fclose( $file );

	}

	print $rez;

	exit();

}
if ( $action == "settings" ) {
	?>
	<DIV class="zagolovok"><B>Подключение к сервису SMS:</B></DIV>
	<form action="index.php" method="post" enctype="multipart/form-data" name="Form" id="Form">
		<input type="hidden" id="action" name="action" value="settings.do">

		<div class="row">

			<div class="column grid-2 right-text ">
				<label for="apikey" class="paddtop5">СМС-шлюз:</label>
			</div>
			<div class="column grid-8">
				<span class="select">
				<select name="type" id="type" class="wp95">
					<option value="sms.ru" <?php if ( $settings['type'] == 'sms.ru' )
						print "selected"; ?> data-type="smsru">SMS.ru</option>
					<option value="smsaero.ru" <?php if ( $settings['type'] == 'smsaero.ru' )
						print "selected"; ?> data-type="smsaeroru">SMSaero.ru</option>
				</select>
				</span>
			</div>

		</div>

		<div class="smstype smsru <?php if ( $settings['type'] != 'sms.ru' )
			print "hidden"; ?>">

			<hr>

			<div class="row" style="overflow-y: auto; max-height: 350px">

				<div class="column grid-2 right-text ">
					<label for="apikey" class="paddtop5">Ключ API:</label>
				</div>
				<div class="column grid-8">
					<input id="apikey" name="apikey" value="<?= $settings['apikey'] ?>" style="width: 98%">
				</div>

			</div>

		</div>
		<div class="smstype smsaeroru <?php if ( $settings['type'] != 'smsaero.ru' )
			print "hidden"; ?>">

			<hr>

			<div class="row" style="overflow-y: auto; max-height: 350px">

				<div class="column grid-2 right-text ">
					<label for="login" class="paddtop5">Логин:</label>
				</div>
				<div class="column grid-8">
					<input id="login" name="login" value="<?= $settings['login'] ?>" style="width: 98%">
				</div>

			</div>
			<div class="row" style="overflow-y: auto; max-height: 350px">

				<div class="column grid-2 right-text ">
					<label for="password" class="paddtop5">Пароль:</label>
				</div>
				<div class="column grid-8">
					<input id="password" name="password" value="<?= $settings['password'] ?>" style="width: 98%">
				</div>

			</div>
			<div class="row" style="overflow-y: auto; max-height: 350px">

				<div class="column grid-2 right-text ">
					<label for="signature" class="paddtop5">Подпись:</label>
				</div>
				<div class="column grid-8">
					<input id="signature" name="signature" value="<?= $settings['signature'] ?>" style="width: 98%">
					<span class="em blue fs-09">Подписи для СМС, задаются в личном кабинете сервиса</span>
				</div>

			</div>

		</div>

		<hr>

		<div class="div-center">

			<span id="orangebutton"><a href="//salesman.sms.ru" target="_blank" title="Получить ключ" class="button">Получить ключ</a></span>
			<a href="javascript:void(0)" onclick="checkBalance()" title="Проверить" class="button">Проверить</a>

		</div>

		<div class="balance pad10 div-center"></div>

		<hr>

		<div align="right">
			<A href="javascript:void(0)" onclick="saveSettings()" class="button">Сохранить</A>&nbsp;
			<A href="javascript:void(0)" onclick="DClose()" class="button">Отмена</A>
		</div>
	</form>
	<script src="js/app.js"></script>
	<script>

		var type;

		$('#dialog').css('width', '500px');

		$('#type').on("change", function () {

			type = $("#type option:selected").data('type');

			$('.smstype').addClass('hidden');
			$('.' + type).removeClass('hidden');

			$('#dialog').center();

		});

		function checkBalance() {

			$('.balance').append('<div id="loader"><img src="/assets/images/loading.gif"></div>');

			var str;

			if (type === 'smsru') str = 'tip=' + $("#type option:selected").val() + '&action=sms.balance&apikey=' + $('#apikey').val();
			else str = 'tip=' + $("#type option:selected").val() + '&action=sms.balance&login=' + $('#login').val() + '&password=' + $('#password').val() + '&signature=' + $('#signature').val();

			$.post("index.php", str, function (data) {

				$('.balance').html('Баланс: <b>' + data.balance + '</b> руб. Соединение установлено.');

			}, 'json');

		}

	</script>
	<?php

	exit();
}

//вывод лога
if ( $action == 'loaddata' ) {

	$list = [];

	$q = "
		SELECT
			{$sqlname}logsms.id as id,
			{$sqlname}logsms.datum as datum,
			{$sqlname}logsms.iduser as iduser,
			{$sqlname}logsms.phone as phone,
			{$sqlname}logsms.content as content,
			{$sqlname}logsms.status as status,
			{$sqlname}logsms.clid as clid,
			{$sqlname}logsms.pid as pid,
			{$sqlname}clientcat.title as client,
			{$sqlname}personcat.person as person,
			{$sqlname}user.title as user
		FROM {$sqlname}logsms
			LEFT JOIN {$sqlname}clientcat ON {$sqlname}clientcat.clid = {$sqlname}logsms.clid
			LEFT JOIN {$sqlname}personcat ON {$sqlname}personcat.pid = {$sqlname}logsms.pid
			LEFT JOIN {$sqlname}user ON {$sqlname}user.iduser = {$sqlname}logsms.iduser
		WHERE
			{$sqlname}logsms.datum BETWEEN '$periodStart 00:00:00' and '$periodEnd 23:59:59' and
			{$sqlname}logsms.identity = '$identity'
		ORDER BY {$sqlname}logsms.datum DESC";

	$data = $db -> getAll( $q );
	foreach ( $data as $da ) {

		$list[] = [
			"datum"   => get_sfdate( $da['datum'] ),
			"phone"   => $da['phone'],
			"content" => $da['content'],
			"status"  => $da['status'],
			"clid"    => (int)$da['clid'],
			"client"  => $da['client'],
			"pid"     => (int)$da['pid'],
			"person"  => $da['person'],
			"user"    => $da['user'],
		];

	}

	$data = ["list" => $list];

	print $result = json_encode_cyr( $data );

	exit();
}
if ( $action == 'loadtpl' ) {

	$data = $db -> getAll( "SELECT * FROM {$sqlname}logsms_tpl WHERE identity = '$identity'" );
	foreach ( $data as $da ) {

		$tpl[] = [
			"id"      => (int)$da['id'],
			"date"    => $da['datum'],
			"name"    => $da['name'],
			"content" => $da['content'],
		];

	}

	print json_encode_cyr( $tpl );

	exit();
}

if ( !in_array( $iduser1, $access ) && $isadmin != 'on' ) {

	print '
			<TITLE>Предупреждение - CRM</TITLE>
			<LINK rel="stylesheet" type="text/css" href="/assets/css/app.css">
			<LINK rel="stylesheet" href="/assets/css/fontello.css">
			<div class="warning" align="left" style="width:600px; margin:0 auto;">
				<span><i class="icon-attention red icon-5x pull-left"></i></span>
				<b class="red uppercase">Предупреждение:</b>
				<br><br>
				У вас нет доступа<br><br><br>
			</div>
			';
	exit();

}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
	<meta charset="utf-8">
	<title>Отправка СМС-сообщений</title>
	<link rel="stylesheet" href="/assets/css/app.css">
	<link rel="stylesheet" href="/assets/css/app.card.css">
	<link rel="stylesheet" href="/assets/css/fontello.css">
	<link rel="stylesheet" href="./plugins/tablesorter/theme.default.css">
	<link rel="stylesheet" href="./plugins/daterangepicker/daterangepicker.css">
	<link rel="stylesheet" href="./plugins/periodpicker/jquery.periodpicker.min.css">
	<link rel="stylesheet" href="./plugins/autocomplete/jquery.autocomplete.css">
	<link rel="stylesheet" href="css/app.css">

	<!--красивые алерты-->
	<script type="text/javascript" src="/assets/js/sweet-alert2/sweetalert2.min.js"></script>
	<link type="text/css" rel="stylesheet" href="/assets/js/sweet-alert2/sweetalert2.min.css">

</head>
<body>

<div id="dialog_container" class="dialog_container">
	<div class="dialog-preloader">
		<img src="/assets/images/rings.svg" border="0" width="128">
	</div>
	<div class="dialog" id="dialog" align="left">
		<div class="close" title="Закрыть или нажмите ^ESC"><i class="icon-cancel"></i></div>
		<div id="resultdiv"></div>
	</div>
</div>

<div class="fixx">
	<DIV id="head">
		<DIV id="ctitle">
			<b>Отправка СМС-сообщений</b>
			<DIV id="close" onClick="window.close();">Закрыть</DIV>
		</DIV>
	</DIV>
	<DIV id="dtabs">
		<UL>
			<LI class="ytab current" id="tb0" data-id="0"><A href="#0">Отправки</A></LI>
			<LI class="ytab" id="tb2" data-id="2"><A href="#2">Шаблоны</A></LI>
			<LI class="ytab"><A href="javascript:void(0)" onclick="setSettings()">Настройка</A></LI>
			<LI class="ytab" id="tb1" data-id="1" style="float:right"><A href="#1">Справка</A></LI>
			<LI class="ytab"><A href="javascript:void(0)" onclick="setAccess()">Доступы</A></LI>
		</UL>
	</DIV>
</div>

<DIV class="fixbg"></DIV>

<DIV id="telo">

	<?php
	if ( is_writable( 'data' ) != true ) {
		print '
	<div class="warning margbot10">
		<p><b class="red">Внимание! Ошибка</b> - отсутствуют права на запись для папки хранения настроек доступа"<b>data</b>".</p>
	</div>';
	}
	?>

	<div id="tab-0" class="tabbody">

		<fieldset class="pad10 notoverflow">

			<legend>Отправленные за период</legend>

			<div class="infodiv margbot10">
				<div class="inline pull-aright1">
					Период отправки:
					<div class="inline period">
						<i class="icon-calendar-1"></i>
						<input id="periodStart" name="periodStart" type="text" value="<?= $periodStart ?>">
						&divide;
						<input id="periodEnd" name="periodEnd" type="text" value="<?= $periodEnd ?>">
					</div>
				</div>
				<span id="greenbutton" class="noprint div-center">
					<a href="javascript:void(0)" onclick="loadData()" class="marg0 button">Показать</a>&nbsp;
				</span>
				<span id="orangebutton" class="noprint pull-aright">
					<a href="javascript:void(0)" onclick="doLoad('?action=sms.compose')" class="marg0 button"><i class="icon-paper-plane"></i>Написать</a>&nbsp;
				</span>
			</div>

			<div class="wrapper">

				<table width="100%" border="0" cellspacing="0" cellpadding="4" class="bgwhite tablesorter" id="dataTable" align="center">
					<thead>
					<tr>
						<th width="20" class="{ filter: false }">№</th>
						<th width="90">Дата</th>
						<th width="">Клиент</th>
						<th width="150">Номер</th>
						<th width="300">Содержимое</th>
						<th width="50">Отправитель</th>
						<th width="90">Статус</th>
					</tr>
					</thead>
					<tbody></tbody>
				</table>

			</div>

		</fieldset>

	</div>

	<div id="tab-1" class="tabbody hidden">

		<fieldset class="pad10" style="overflow: auto; height: 450px">

			<legend>Справка по плагину</legend>

			<div class="margbot10">
				
				<?php
				$html = file_get_contents("readme.md");
				$Parsedown = new Parsedown();
				$help = $Parsedown -> text($html);
				
				$help = str_replace( [
					"{{package}}",
					"{{version}}",
					"{{versiondate}}"
				], [
					$about['package'],
					$about['version'],
					$about['versiondate']
				], $help );
				
				print $help;
				?>

				<!--<pre id="copyright">
##################################################
#                                                #
#   Плагин разработан для SalesMan CRM v.2016    #
#   Разработчик: Владислав Андреев               #
#   Контакты:                                    #
#     - Сайт:  http://isaler.ru                  #
#     - Email: vladislav@isaler.ru               #
#     - Скайп: andreev.v.g                       #
#                                                #
##################################################
				</pre>

				<hr>

				<div class="margbot10">
					<h2>Возможности</h2>

					<ul>
						<li>Отправка СМС из карточки Клиента/Контакта/Сделки</li>
						<li>Отправка СМС из окна просмотра Клиента/Контакта</li>
						<li>Отправка СМС из POPUP-окна разговоров</li>
						<li>Ведение лога отправленных сообщений</li>
						<li>Добавление содержания сообщения в карточку Клиента/Контакта - История активностей с типом "СМС"</li>
					</ul>

					<h3 class="red">Важно</h3>

					<ul>
						<li>Скрипт работает только с корректно введенными мобильными номерами</li>
						<li>Скрипт определяет мобильный номер по 2-й (второй) цифре номера, равной "9"</li>
					</ul>

					<hr>

					<h2>Доступы</h2>

					<p>По умолчанию доступ к настройкам приложения имеют ВСЕ администраторы. Для ограничения доступа конкретным сотрудникам необходимо провести настройки в разделе "Доступы".</p>

					<hr>

					<h2>Настройка подключения</h2>

					<p>Плагин отправляет СМС-сообщения через внешний СМС-шлюз. Поддерживаются сервисы:</p>

					<ul>
						<li><a href="//salesman.sms.ru" target="blank" title="SMS.ru">SMS.ru</a>.</li>
						<li>
							<a href="//smsaero.ru/?agent=fAvELn5OPzSV#registration" target="blank" title="SMSaero.ru">SMSaero.ru</a>.
						</li>
					</ul>

					<p>Подключение:</p>

					<ul>
						<li>Зарегистрируйтесь в сервисе</li>
						<li>Получите необходимые данные для подключения</li>
						<li>Укажите его в разделе "Настройка"</li>
						<li>Сохраните</li>
					</ul>

					<p class="hidden">
						Для актуализации статуса сообщения необходимо указать в настройках скрипт
						<a href="//salesman.sms.ru/?panel=apps&subpanel=cb" target="blank" title="SMS.ru">Callback</a> - для получения уведомлений от сервиса.
					</p>

					<p class="infodiv bgwhite hidden">Адрес скрипта Callback:
						<b><?php /*= $productInfo['crmurl'].'/plugins/smsSender/callback.php' */?></b></p>

					<hr>

					<h2>Шаблоны</h2>

					<ul>
						<li>Шаблоны можно добавить на вкладке "Шаблоны"</li>
						<li>Поддерживается неограниченное количество шаблонов</li>
					</ul>

				</div>-->

			</div>

		</fieldset>

	</div>

	<div id="tab-2" class="tabbody hidden">

		<fieldset class="pad10" style="height: 450px">

			<legend>Шаблоны</legend>

			<div class="infodiv">
				<span id="orangebutton">
					<a href="javascript:void(0)" onclick="doLoad('?action=tpl')" class="marg0 button"><i class="icon-plus-circled"></i>Новый шаблон</a>&nbsp;
				</span>
			</div>

			<div class="margbot10">

				<div class="wrapper2">

					<table width="100%" border="0" cellspacing="0" cellpadding="4" class="bborder bgwhite" id="tplTable" align="center">
						<thead>
						<tr>
							<th width="20">№</th>
							<th width="200">Название</th>
							<th width="">Содержание</th>
							<th width="120">Дата добавления</th>
							<th width="180" class="{sorter: 'false'}">Действие</th>
						</tr>
						</thead>
						<tbody></tbody>

					</table>

				</div>

			</div>

		</fieldset>

	</div>

</DIV>

<div class="h10"></div>

<hr>

<div class="h40 gray center-text">Сделано для SalesMan CRM</div>

<script src="/assets/js/jquery/jquery-3.4.1.min.js"></script>
<script src="/assets/js/jquery/jquery-migrate-3.0.0.min.js"></script>
<script src="/assets/js/jquery/jquery-ui.min.js?v=2019.4"></script>
<script src="/assets/js/moment.js/moment.min.js"></script>
<script src="js/app.js"></script>

<script src="plugins/tablesorter/jquery.tablesorter.js"></script>
<script src="plugins/tablesorter/jquery.tablesorter.widgets.js"></script>
<script src="plugins/tablesorter/widgets/widget-cssStickyHeaders.min.js"></script>

<script src="plugins/daterangepicker/jquery.daterangepicker.js"></script>
<script src="plugins/periodpicker/jquery.periodpicker.full.min.js"></script>

<script src="plugins/autocomplete/jquery.autocomplete.js"></script>
<script>

	$(function () {

		var fh = $(window).height() - 210;
		var fh2 = $(window).height() - 310;

		$('fieldset:not(.notoverflow)').height(fh);
		$('.wrapper').height(fh2);

		$('.period').dateRangePicker({
			separator: ' &divide; ',
			getValue: function () {
				if ($('#periodStart').val() && $('#periodEnd').val())
					return $('#periodStart').val() + '  &divide;  ' + $('#periodEnd').val();
				else
					return '';
			},
			setValue: function (s, s1, s2) {
				$('#periodStart').val(s1);
				$('#periodEnd').val(s2);
			}
		});

		$("#dataTable").tablesorter({

			widthFixed: true,
			widgets: ['cssStickyHeaders'],

			widgetOptions: {
				cssStickyHeaders_attachTo: '.wrapper',
				cssStickyHeaders_addCaption: true
			}

		});

		$("#tplTable").tablesorter({

			widthFixed: true,
			widgets: ['cssStickyHeaders'],

			widgetOptions: {
				cssStickyHeaders_attachTo: '.wrapper2',
				cssStickyHeaders_addCaption: true
			}

		});

		loadData();

	});

</script>
</body>
</html>