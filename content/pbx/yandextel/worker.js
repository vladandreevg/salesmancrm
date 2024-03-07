/*функция в yandextel.js отправляет по событию в файл event.php данные cmd=events с параметрами str
Для каждого API ключа установлено индивидуальное ограничение на вызов методов API, которое составляет 2500 вызовов в сутки. Limit Exceeded
*/
//переменная для данных полученных из базы кей и добномер
var extention;

$.Mustache.load('/content/pbx/template.mustache');
inpeers();
getLastCalls();

//WebPhone SDK - это JavaScript библиотека, которую необходимо подключить к вашему проекту (внешней системе).
includeJS('https://api.yandex.mightycall.ru/api/v3/sdk/mightycall.webphone.sdk.js');

//подключение к телефнонии с данными (вызов идет в sip_editor.php $action == 'check'  $tip == 'yandextel')
function Connect(data) {
//console.log(data);
	var mcConfig = JSON.parse (data);
	//console.log(mcConfig);
	MightyCallWebPhone.ApplyConfig(mcConfig);
	MightyCallWebPhone.Phone.Init();

//для панели управления - проверить соединение
	setTimeout(function () {
		var status = MightyCallWebPhone.Phone.Status();
		//console.log(status);
		if (status == "ready")
			$('#sipress').html('<b class="green">Соединение установлено</b>');
		else $('#sipress').html('<b class="red">Ошибка</b>');
	}, 20000);
}

//Полученте кей и добномер из базы данных
$.getJSON('/content/pbx/yandextel/sipparams.php?action=js', function(data){
	extention = data.dobnumer;
	//console.log(data);
});

$(document).ready(function() {

	//подписываемся чтобы работали данные функции
	MightyCallWebPhone.Phone.OnCallIncoming.subscribe(webPhoneOnCallIncoming);
	MightyCallWebPhone.Phone.OnCallStarted.subscribe(webPhoneOnCallStarted);
	MightyCallWebPhone.Phone.OnCallCompleted.subscribe(webPhoneOnCallCompleted);
	/*MightyCallWebPhone.Phone.OnHangUp.subscribe(webPhoneOnHangUp);
	MightyCallWebPhone.Phone.OnAccept.subscribe(webPhoneOnAccept);*/
	MightyCallWebPhone.Phone.OnError.subscribe(webPhoneOnError);
	MightyCallWebPhone.Phone.OnCallOutgoing.subscribe(webPhoneOnCallOutgoing);

});

//Обработчик события вызывается в момент ПОЛУЧЕНИЯ в WebPhone ВХОДЯЩЕГО ЗВОНКА.
	function webPhoneOnCallIncoming(callInfo) {
		var status = MightyCallWebPhone.Phone.Status();
		console.log(status);
		MightyCallWebPhone.Phone.Focus();
		console.log('Идет входящий звонок.');
		console.log(callInfo);
		var str = 'phone=' + callInfo.From + '&extension=' + extention + '&number_to=' + callInfo.To/*+'&callid='+callInfo.eventid*/ + '&type=in&status=' + callInfo.info.state;
		var url = '/content/pbx/yandextel/events.php?cmd=event';
		$.getJSON(url, str, function (data) {
		});
	}

/*НЕ ПРАВИЛЬНО РАБОТАЕТ ПОСКОЛЬКУ ОДНОВРЕМЕННО СРАБАТЫВАЕТ ПРИ ВХЯОДЯЩЕМ webPhoneOnCallStarted
//Обработчик события вызывается в момент когда оператор ПРИНИМАЕТ ВХОДЯЩИЙ ЗВОНОК через WebPhone.
function webPhoneOnAccept() {
	console.log('Идет входящий разговор.');
	var str = 'cmd2=call&extension='+100+'&type=in&status=CONNECTED';
	var url = '../../api/yandextel/events.php?cmd=event';
	$.getJSON(url, str, function(data){
	});
}
*/

/*НЕ ПРАВИЛЬНО РАБОТАЕТ ПОСКОЛЬКУ ОДНОВРЕМЕННО СРАБАТЫВАЕТ ПРИ ВХЯОДЯЩЕМ webPhoneOnCallCompleted (Данная функция срабатывает всегда когда нажимается кнопка "Завершить звонок" В  WebPhone и при входящем и при исходящем) Получается нельзя нажимать эту кнопку при исходящем

//Обработчик события вызывается в момент когда оператор ЗАВЕРШАЕТ ВХОДЯЩИЙ ЗВОНОК через WebPhone.
function webPhoneOnHangUp() {
	console.log('Завершен входящий звонок');
	var url = '../../api/yandextel/events.php?cmd=history&type=in';
	$.getJSON(url, function(data){});
}
*/

//Обработчик события вызывается в момент СОВЕРШЕНИЕ ИСХОДЯЩЕГО ЗВОНКА через WebPhone.(callInfo.info.state=OUTGOING)НЕ СРАБАТЫВАЕТ ПРИ ВХОДЯЩЕМ ЭТО ПРАВИЛЬНО
function webPhoneOnCallOutgoing(callInfo) {
	console.log('Вывывается исходящий номер.');
	console.log(callInfo);
	var str = 'phone='+callInfo.info.To+'&extension='+extention+'&number_to='+callInfo.info.From/*+'&callid='+callInfo.eventid*/+'&type=out&status='+ callInfo.info.info.state;
	var url = '/content/pbx/yandextel/events.php?cmd=event';
	if(callInfo.info.state = 'OUTGOING') {
		$.getJSON(url, str, function (data) {
		});
	}

	$('#rezult').html('<b class="green">Ожидайте звонка</b>');

	var status = MightyCallWebPhone.Phone.Status();
	if(status ='call_outgoing') $('#rezult').html('<b class="red">Вызываемый абонент не взял трубку</b>');

}

//Обработчик события вызывается в момент НАЧАЛО РАЗГОВОРА через WebPhone. (callInfo.info.state=CONNECTED)
function webPhoneOnCallStarted(callInfo) {
	console.log(callInfo);
	console.log('Идет разговор.');
	//var str = 'cmd2=call&extension='+100+'&type=out&status='+ callInfo.info.info.state;
	var str = 'cmd2=call&extension='+extention+'&status='+ callInfo.info.state;
	var url = '/content/pbx/yandextel/events.php?cmd=event';
	$.getJSON(url, str, function(data){
	});

	$('#rezult').html('<b class="green">Идет разговор</b>');
	/*
	//скроем окна (Последние звонки, Входящие звонки и разговоры)
	$('#lastcolls').hide();
	$('#peers').hide();
	$('#inpeers').hide();*/
}

//Обработчик события вызывается в момент ЗАВЕРШЕНИЕ РАЗГОВОРА через WebPhone.(callInfo.info.state=IDLE)
function webPhoneOnCallCompleted(callInfo) {
	console.log('Завершение разговора');
	console.log(callInfo);
	//var url = '../../api/yandextel/events.php?cmd=history&type=out';
	var url = '/content/pbx/yandextel/events.php?cmd=history';
	//установлен таймаут поскольку если зразу же запрашивать он присылает предпоследний звонок (10 секунд)
	setTimeout(function () {
		$.getJSON(url, function (data) {
		});
	}, 10000);
	var status=callInfo.info.state;

	if(status='IDLE') $('#rezult').html('<b class="red">Звонок завершен</b>');


}

//Обработчик события вызывается в случае возникновения непредвиденной ошибки.
function webPhoneOnError(errorMessage) {
	console.log('Произошла ошибка в WebPhone: ' + errorMessage);
}
/*
//Обработчик события вызывается в момент завершения разговора через WebPhone.
function webPhoneOnCallCompleted(callInfo) {
	console.log(callInfo);
	var str = 'phone='+callInfo.info.From+'&extension='+100+'&number_to='+callInfo.info.To+'&callid='+callInfo.eventid+'&type=in&status='+ callInfo.info.info.state;
	var url = '../../api/yandextel/events.php?cmd=event';
	$.getJSON(url, str, function(data){
	});
}*/



/*МОЖЕТ ПРИГОДИТСЯ ДЛЯ ВХОДЯЩИХ
//Обработчик события вызывается в момент НАЧАЛО РАЗГОВОРА через WebPhone.
function webPhoneOnCallStarted(callInfo) {
	//console.log(callInfo);
	var str = 'cmd2=call&extension='+100+'&type=in&status='+ callInfo.info.info.state;
	var url = '../../api/yandextel/events.php?cmd=event';
	$.getJSON(url, str, function(data){
	});
}

//Обработчик события вызывается в момент ЗАВЕРШЕНИЕ РАЗГОВОРА через WebPhone.
function webPhoneOnCallCompleted(callInfo) {
	console.log('Завершение разговора');
	var url = '../../api/yandextel/events.php?cmd=history&type=in';
	$.getJSON(url, function(data){});
}
*/


//CallWShow();


//последние звонки
function getLastCalls(){

	var url = '/content/pbx/yandextel/peers.php?action=lastcolls';
	var sms = '';

	if($('#lastcolls').is('div')) {

		$.getJSON(url, function (data) {

			if(in_array('smsSender', $pluginEnambled)) sms = '1';

			$('#lastcolls').empty().mustache('lastcollsTpl', {'list':data,'sms':sms});

		});

	}

}

//для истории звноков
function synchist(){
	url = '/content/pbx/yandextel/cdr.php';
	$.post(url);
	//configpage();
}

//мониторинг звонков
function inpeers(){

	//признак того, что отправлен запрос и еще не получен
	var isWork = localStorage.getItem("yandextelWork");
	var oldtime = parseInt(localStorage.getItem("yandextelWorkTime"));
	var thistime = Date.now();

	var adelta = thistime - oldtime;
	var atime = 5000;

	//console.log('delta='+adelta);

	if(isWork != 'yes' || adelta > atime){

		localStorage.setItem("yandextelWork", 'yes');
		localStorage.setItem("yandextelWorkTime", Date.now());

		$.getJSON('/content/pbx/yandextel/peers.php?action=getIncoming', function(data){

			$('#inpeers').empty().mustache('templateTpl', data);

			//если есть звонки или разговоры, то поднимаем блок звонков
			if(data.inpeers.length > 0 || data.peers.length > 0) {
				CallWShow();
			}
			//в противном случае снимаем принудительное скрытие окна
			else {

				localStorage.setItem("callerNotAutoShow", '');

			}


		})
			.done( function(){

				localStorage.setItem("gravitelWork", '');

			});

	}

}

//провнрка на события по телефонии
Visibility.every(6000,5000, inpeers);
//период запросов для входящих соединений 1- при активном окне, 2 - при окне в фоне только для астериска

Visibility.every(540000, synchist);//раз в 9 минут
//период запроса на синхронизацию истории звонков

Visibility.every(60000, getLastCalls);
//период запросов на получение истории последних звонков