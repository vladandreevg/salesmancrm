<?php
/* ============================ */
/*         SalesMan CRM         */
/* ============================ */
/* (C) 2016 Vladislav Andreev   */
/*       Salesman Project       */
/*        www.isaler.ru         */
/*        ver. 2019.x           */
/* ============================ */

/*
 * Совершение исходящего звонка через телефонию
 * Для правильного отображения ссылки на прослушивание записанного разговора в "Истории звонков" необходимо сделать настройки в файле api/asterisk/play.php
 */

error_reporting( E_ERROR );

$rootpath = dirname( __DIR__, 3 );

include $rootpath."/inc/config.php";
include $rootpath."/inc/dbconnector.php";
include $rootpath."/inc/auth.php";
include $rootpath."/inc/func.php";
include $rootpath."/inc/settings.php";

$thisfile = basename( __FILE__ );

require_once dirname( __DIR__)."/onlinepbx/sipparams.php";
require_once dirname( __DIR__)."/onlinepbx/mfunc.php";
require_once dirname( __DIR__)."/onlinepbx/lib/onpbx_http_api.php";

$action = $_REQUEST['action'];
$clid   = $_REQUEST['clid'];
$pid    = $_REQUEST['pid'];
$phone  = preparePhone( $_REQUEST['phone'] );

//проверим наличие буферной таблицы и создадим её, если нет
$da = $db -> getOne( "SELECT COUNT(*) as count FROM INFORMATION_SCHEMA.STATISTICS WHERE table_schema = '$database' and TABLE_NAME = '{$sqlname}onlinepbx_log'" );
if ( $da == 0 ) {

	$db -> query( "CREATE TABLE `{$sqlname}onlinepbx_log` (
			`id` INT(20) NOT NULL AUTO_INCREMENT,
			`callid` varchar(255) NOT NULL COMMENT 'Идентификатор звонка', 
			`datum` timestamp  NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP, 
			`extension` varchar(10) NOT NULL COMMENT 'Внутренний номер сотрудника', 
			`phone` varchar(16) NOT NULL COMMENT 'Номер абонента', 
			`status` varchar(255) NOT NULL COMMENT 'Статус звонка', 
			`type` varchar(255) NOT NULL COMMENT 'Тип записи (исходящий или входящий)', 
			`clid` INT(20) NOT NULL COMMENT 'Из базы клиент (clientcat.clid)', 
			`pid` INT(20) NOT NULL COMMENT 'Из базы контакт (personcat.pid)', 
			`identity` int(30) DEFAULT '1' NOT NULL COMMENT 'идентификатор аккаунта (id записи в таблице settings)', 
			PRIMARY KEY (`id`), 
			UNIQUE INDEX `id` (`id`),
			INDEX `extension` (`extension`)
			) 
			COMMENT='Лог отправленных уведомлений' COLLATE='utf8_general_ci'" );

}

//параметры сотрудника
$res       = $db -> getRow( "SELECT * FROM {$sqlname}user WHERE iduser = '$iduser1' and identity = '$identity'" );
$title     = $res["title"];
$extension = $res["phone_in"];//внутренний номер абонента

//приходит ссылка
if ( $action == 'inicialize' ) {

	$rez = getxCallerID( (string)$phone );

	$callerID = $rez['callerID'];

	if ( (int)$pid > 0 ) {

		$callerID   = current_person( $pid );
		$rez['pid'] = (int)$pid;
		$clid       = $rez['clid'] = (int)getPersonData( $pid, 'clid' );

	}
	elseif ( (int)$clid > 0 ) {

		$callerID    = current_client( (int)$clid );
		$rez['clid'] = (int)$clid;

	}

	//найдем данные клиента по полученным $clientID и $personID
	$client = ($callerID) ? : 'Неизвестный';

	if ( (int)$rez['pid'] > 0 ) {
		$person = current_person( (int)$rez['pid'] );
	}
	if ( (int)$rez['clid'] > 0 ) {
		$title = current_client( (int)$rez['clid'] );
	}

	?>
	<div id="caller-header" class="zag paddbott10 white">
		<b>Звонок на номер:</b> <i class="icon-phone white"></i><?= $phone ?>
	</div>

	<div class="paddbott100 white">
		<?php if ( $rez['clid'] > 0 ) { ?>
			<div class="carda">
				<a href="javascript:void(0)" onclick="openClient('<?= $rez['clid'] ?>')" title="Карточка клиента">
					<i class="icon-building blue"></i>&nbsp;<?= $title ?>
				</a>
			</div>
		<?php } ?>
		<?php if ( $rez['pid'] > 0 ) { ?>
			<div class="carda">
				<a href="javascript:void(0)" onclick="openPerson('<?= $rez['pid'] ?>')" title="Карточка контакта">
					<i class="icon-user-1 blue"></i>&nbsp;<?= $person ?>
				</a>
			</div>
		<?php } ?>
	</div>

	<hr>

	<div id="rezult" class="p10 relativ">Набор номера...<br></div>

	<hr>

	<div class="text-center btn small paddbott10">
		<a href="javascript:void(0)" onclick="addHistory('','<?= $rez['clid'] ?>','<?= $rez['pid'] ?>')" title="Добавить активность" class="button greenbtn"><i class="icon-clock"></i>&nbsp;Активность</a>
		<?php if ( $setEntry['enShowButtonCall'] == 'yes' && $isEntry == 'on' ) { ?>
			<a href="javascript:void(0)" onClick="editEntry('','edit','<?= $phone ?>');" title="Добавить обращение" class="button redbtn">&nbsp;&nbsp;<i class="icon-plus-circled"></i>&nbsp;&nbsp;Обращение</a>
		<?php } ?>
	</div>

	<script>
		$('#rezult').load('content/pbx/onlinepbx/callto.php?action=Originate&phone=<?=$phone?>&clid=<?=$rez['clid']?>&pid=<?=$rez['pid']?>').append('<img src="/assets/images/loading.gif">');
	</script>


	<?php

	exit();

}

//осуществление вызова исходящего
if ( $action == 'Originate' ) {

	$id = (int)$db -> getOne( "SELECT id FROM  {$sqlname}onlinepbx_log WHERE type = 'out' and extension = '$extension' and identity = '$identity'" ) + 0;

	//чистим лог при out нового звонка
	if ( $id != 0 )
		$db -> query( "DELETE FROM {$sqlname}onlinepbx_log WHERE id = '$id'" );

	$phone  = $extension;
	$phone2 = $_REQUEST['phone'];
	$clid   = (int)$_REQUEST['clid'];
	$pid    = (int)$_REQUEST['pid'];


	$apar = [
		"api_salt" => $api_user,
		"api_key"  => $api_secret,
		"from"     => $extension,
		"to"       => $phone2,
		"clid"     => $clid,
		"pid"      => $pid
	];

	print '<b><div class="state green"></div></b>';

	$callID = doMethod( 'call', $apar );

	?>
	<SCRIPT type="text/javascript">

		// Проверяем авторизацию в OnlinePBX
		<?php
		if($callID['status']){
		?>

		if (!isCard) setTimeout(function () {
			$('#callto').empty();
		}, 10000);

		var url = 'content/pbx/onlinepbx/callto.php?action=State';

		$.post(url, function (data) {

			$('.state').html(data.message);

			if (data.state === 'disconnected') {

				$('.state').removeClass('green').addClass('red');

			}

		}, "json");

		<?php
		}
		else {
		?>

		$('.state').html("Вы не авторизованы в ВАТС OnlinePBX!").removeClass('green').addClass('red');

	</SCRIPT>
	<?php
}
	exit();

}

//статусы при исходящем берется из onlinepbx_log. Данные попадают туда из events.php
if ( $action == 'State' ) {

	$result = [];

	$status = $db -> getRow( "SELECT * FROM  {$sqlname}onlinepbx_log WHERE type = 'out' and extension = '$extension' and identity = '$identity'" );

	$result['state'] = '';

	//отловим событие, когда установлено соединение
	switch ($status['comment']) {
		case 'UNSPECIFIED':
			$result['state']   = 'disconnected';
			$result['message'] = 'Неизвестная ошибка';
		break;
		case 'UNALLOCATED_NUMBER':
			$result['state']   = 'disconnected';
			$result['message'] = 'Несуществующий номер';
		break;
		case 'NO_ROUTE_TRANSIT_NET':
			$result['state']   = 'disconnected';
			$result['message'] = 'Нет транзитного маршрута';
		break;
		case 'DESTINATION_OUT_OF_ORDER':
		case 'NO_ROUTE_DESTINATION':
			$result['state']   = 'disconnected';
			$result['message'] = 'Нет заданного маршрута';
		break;
		case 'CHANNEL_UNACCEPTABLE':
			$result['state']   = 'disconnected';
			$result['message'] = 'Отказ не принят';
		break;
		case 'RESPONSE_TO_STATUS_ENQUIRY':
		case 'PRE_EMPTED':
		case 'BEARERCAPABILITY_NOTAUTH':
		case 'BEARERCAPABILITY_NOTAVAIL':
		case 'CALL_AWARDED_DELIVERED':
			$result['state']   = 'disconnected';
			$result['message'] = $status;
		break;
		case 'NORMAL_CLEARING':
			$result['state']   = 'connected';
			$result['message'] = "Ожидайте звонка";
		break;
		case 'USER_BUSY':
			$result['state']   = 'disconnected';
			$result['message'] = 'Абонент занят';
		break;
		case 'NO_USER_RESPONSE':
			$result['state']   = 'disconnected';
			$result['message'] = 'Абонент не ответил';
		break;
		case 'NO_ANSWER':
			$result['state']   = 'disconnected';
			$result['message'] = 'Нет ответа';
		break;
		case 'SUBSCRIBER_ABSENT':
			$result['state']   = 'disconnected';
			$result['message'] = 'Абонент не в сети';
		break;
		case 'CALL_REJECTED':
			$result['state']   = 'disconnected';
			$result['message'] = 'Вызов отклонен';
		break;
		case 'NUMBER_CHANGED':
			$result['state']   = 'disconnected';
			$result['message'] = 'Номер изменился';
		break;
		case 'REDIRECTION_TO_NEW_DESTINATION':
			$result['state']   = 'disconnected';
			$result['message'] = 'Вызов переадресован';
		break;
		case 'EXCHANGE_ROUTING_ERROR':
			$result['state']   = 'disconnected';
			$result['message'] = 'Ошибка оператора';
		break;
		case 'INVALID_NUMBER_FORMAT':
			$result['state']   = 'disconnected';
			$result['message'] = 'Ошибка в номере';
		break;
		case 'FACILITY_REJECTED':
			$result['state']   = 'disconnected';
			$result['message'] = 'Услуга недоступна';
		break;
		case 'NORMAL_CIRCUIT_CONGESTION':
		case 'NORMAL_UNSPECIFIED':
			$result['state']   = 'disconnected';
			$result['message'] = 'Нет канала связи';
		break;
		case 'NETWORK_OUT_OF_ORDER':
			$result['state']   = 'disconnected';
			$result['message'] = 'Сеть недоступна';
		break;
		case 'NORMAL_TEMPORARY_FAILURE':
			$result['state']   = 'disconnected';
			$result['message'] = 'Временная ошибка';
		break;
		case 'SWITCH_CONGESTION':
			$result['state']   = 'disconnected';
			$result['message'] = 'Компьютерная сеть перегружена';
		break;
		case 'ACCESS_INFO_DISCARDED':
			$result['state']   = 'disconnected';
			$result['message'] = 'Отказ в обслуживании';
		break;
		case 'REQUESTED_CHAN_UNAVAIL':
			$result['state']   = 'disconnected';
			$result['message'] = 'Канал связи недоступен';
		break;
		case 'FACILITY_NOT_SUBSCRIBED':
			$result['state']   = 'disconnected';
			$result['message'] = 'Нет доступа к услуги';
		break;
		case 'OUTGOING_CALL_BARRED':
			$result['state']   = 'disconnected';
			$result['message'] = 'Исходящий вызов запрещен';
		break;
		case 'INCOMING_CALL_BARRED':
			$result['state']   = 'disconnected';
			$result['message'] = 'Входящий вызов запрещен';
		break;
		case 'SERVICE_UNAVAILABLE':
			$result['state']   = 'disconnected';
			$result['message'] = 'Сервис недоступен';
		break;
		case 'BEARERCAPABILITY_NOTIMPL':
			$result['state']   = 'disconnected';
			$result['message'] = 'Плохое интернет соединение';
		break;
		case 'CHAN_NOT_IMPLEMENTED':
			$result['state']   = 'disconnected';
			$result['message'] = 'Данный тип связи не поддерживается';
		break;
		case 'FACILITY_NOT_IMPLEMENTED':
			$result['state']   = 'disconnected';
			$result['message'] = 'Данная услуга не поддерживается';
		break;
		case 'SERVICE_NOT_IMPLEMENTED':
			$result['state']   = 'disconnected';
			$result['message'] = 'Сервис не реализован';
		break;
		case 'INVALID_CALL_REFERENCE':
			$result['state']   = 'disconnected';
			$result['message'] = 'Ошибка в ссылке звонка';
		break;
		case 'INCOMPATIBLE_DESTINATION':
			$result['state']   = 'disconnected';
			$result['message'] = 'Несовместимое назначение';
		break;
		case 'INVALID_MSG_UNSPECIFIED':
			$result['state']   = 'disconnected';
			$result['message'] = 'Ошибка сообщения';
		break;
		case 'IE_NONEXIST':
		case 'WRONG_CALL_STATE':
		case 'MANDATORY_IE_LENGTH_ERROR':
		case 'PROTOCOL_ERROR':
		case 'INVALID_IE_CONTENTS':
		case 'MANDATORY_IE_MISSING':
			$result['state']   = 'disconnected';
			$result['message'] = 'Устройство не соответствует стандартам';
		break;
		case 'MESSAGE_TYPE_NONEXIST':
			$result['state']   = 'disconnected';
			$result['message'] = 'Тип сообщения отсутствует';
		break;
		case 'WRONG_MESSAGE':
			$result['state']   = 'disconnected';
			$result['message'] = 'Неверное сообщение';
		break;
		case 'RECOVERY_ON_TIMER_EXPIRE':
			$result['state']   = 'disconnected';
			$result['message'] = 'Время истекло';
		break;
		case 'INTERWORKING':
			$result['state']   = 'disconnected';
			$result['message'] = 'Неустойчивое взаимодействие';
		break;
		case 'ORIGINATOR_CANCEL':
			$result['state']   = 'disconnected';
			$result['message'] = 'Вызов отменен';
		break;
		case 'CRASH':
			$result['state']   = 'disconnected';
			$result['message'] = 'Случилось страшное';
		break;
		case 'SYSTEM_SHUTDOWN':
			$result['state']   = 'disconnected';
			$result['message'] = 'Потерпите минуту сервер перезагружается';
		break;
		case 'LOSE_RACE':
			$result['state']   = 'disconnected';
			$result['message'] = 'Обрыв линии связи';
		break;
		case 'MANAGER_REQUEST':
			$result['state']   = 'disconnected';
			$result['message'] = 'Завершен через API';
		break;
		case 'BLIND_TRANSFER':
			$result['state']   = 'disconnected';
			$result['message'] = 'Без условный перевод';
		break;
		case 'ATTENDED_TRANSFER':
			$result['state']   = 'disconnected';
			$result['message'] = 'Условный перевод';
		break;
		case 'ALLOTTED_TIMEOUT':
			$result['state']   = 'disconnected';
			$result['message'] = 'Выделенный таймаут';
		break;
		case 'USER_CHALLENGE':
			$result['state']   = 'disconnected';
			$result['message'] = 'У абонента проблемы';
		break;
		case 'MEDIA_TIMEOUT':
			$result['state']   = 'disconnected';
			$result['message'] = 'Кончилась музыка';
		break;
		case 'PICKED_OFF':
			$result['state']   = 'disconnected';
			$result['message'] = 'Перехвачен';
		break;
		case 'USER_NOT_REGISTERED':
			$result['state']   = 'disconnected';
			$result['message'] = 'Абонент не зарегистрирован';
		break;
		case 'PROGRESS_TIMEOUT':
			$result['state']   = 'disconnected';
			$result['message'] = 'Время ожидания вышло';
		break;
		default:
			$result['state']   = 'connected';
			$result['message'] = 'Ожидайте звонка';
		break;
	}

	$result['callid'] = $status['callid'];

	print json_encode_cyr( $result );

	exit();

}