var siptip = $('#siptip').val();

$.Mustache.load('/content/pbx/template.mustache');

getLastCalls();
newinpeers();

//для всех интеграций
function getLastCalls(){

	var url = '/content/pbx/asterisk/peers.php?action=lastcolls';
	var sms = '';

	if($('#lastcolls').is('div')) {

		$.getJSON(url, function (data) {

			// Интеграция с плагином SMSsender
			if(in_array('smsSender', $pluginEnambled))
				sms = '1';

			//datas = {'list':data,'sms':sms};

			$('#lastcolls').empty().mustache('lastcollsTpl', {'list':data,'sms':sms});

		});

	}

}

/**
 * Синхронизация CDR
 */
function synchist(){

	var url;

	if(siptip === 'asterisk')
		url = '/content/pbx/asterisk/cdr.php';
	else
		url = '/content/pbx/asterisk/callto.php?action=gethistory';

	$.post(url);

}

/**
 * Запрашивает список операторов и состояние линий
 * Не используется
 */
function peers(){

	$("#peers").load("/content/pbx/asterisk/peers.php?action=getPeers");

}

/**
 * Проверка входящих звонков и разговоров
 */
function newinpeers(){

	//признак того, что отправлен запрос и еще не получен
	var isWork = localStorage.getItem("asteriskWork");
	var oldtime = parseInt(localStorage.getItem("asteriskWorkTime"));
	var thistime = Date.now();

	// время, прошедшее с последней проверки
	var adelta = thistime - oldtime;
	// максимальное время, через которое запрос выполняется в любом случае
	var atime = 10000;
	
	//console.log('delta='+adelta);

	/**
	 * Выполняем запрос только в том случае, если в данный момент не выполняется точно такой же запрос
	 * Это связано с возможно более долгим ответом от сервера Asterisk
	 * Или выполняем, если с момента последней проверки прошло более 10 сек. (atime)
	 */
	if(isWork !== 'yes' || adelta > atime){

		localStorage.setItem("asteriskWork", 'yes');
		localStorage.setItem("asteriskWorkTime", Date.now());

		$.getJSON('/content/pbx/asterisk/peers.php?action=getIncoming', function(data){

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

			localStorage.setItem("asteriskWork", '');

		});

	}

}

if(siptip === 'asterisk'){

	//период запросов для входящих соединений 1- при активном окне, 2 - при окне в фоне только для астериска
	Visibility.every(2000,5000, newinpeers);

}

//период запросов для получения статуса линий 1- при активном окне, 2 - при окне в фоне
//Visibility.every(6000,11000, peers);

//период запроса на синхронизаци истории звонков
Visibility.every(300000, synchist);

//период запросов на получение истории последних звонков
Visibility.every(60000, getLastCalls);