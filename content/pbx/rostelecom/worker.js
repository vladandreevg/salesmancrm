var siptip = $('#siptip').val();
$.Mustache.load('/content/pbx/template.mustache');

getLastCalls();
inpeers();

//для всех интеграций
function getLastCalls(){

	var url = '/content/pbx/rostelecom/peers.php?action=lastcolls';
	var sms = '';

	if($('#lastcolls').is('div')) {

		$.getJSON(url, function (data) {

			if(in_array('smsSender', $pluginEnambled)) sms = '1';

			$('#lastcolls').empty().mustache('lastcollsTpl', {'list':data,'sms':sms});

		});

	}

}

function synchist(){

	var url = '/content/pbx/rostelecom/cdr.php';
	$.post(url);
	
}

function inpeers(){

	//признак того, что отправлен запрос и еще не получен
	var isWork = localStorage.getItem("rostelecomWork");
	var oldtime = parseInt(localStorage.getItem("rostelecomWorkTime"));
	var thistime = Date.now();
	
	var adelta = thistime - oldtime;
	var atime = 5000;
	
	//console.log('delta='+adelta);

	if(isWork !== 'yes' || adelta > atime){

		localStorage.setItem("rostelecomWork", 'yes');
		localStorage.setItem("rostelecomWorkTime", Date.now());

		$.getJSON('/content/pbx/rostelecom/peers.php?action=getIncoming', function(data){

			$('#inpeers').empty().mustache('templateTpl', data);

			//если есть звонки или разговоры, то поднимаем блок звонков
			if(data.inpeers.length > 0 || data.peers.length > 0) CallWShow();
			//в противном случае снимаем принудительное скрытие окна
			else {

				localStorage.setItem("callerNotAutoShow", '');

			}


		})
			.done( function(){

				localStorage.setItem("rostelecomWork", '');

			});

	}

}

if(siptip === 'rostelecom'){
	Visibility.every(2000,5000, inpeers);
	//период запросов для входящих соединений 1- при активном окне, 2 - при окне в фоне только для астериска
}

//Visibility.every(6000,11000, peers);
//период запросов для получения статуса линий 1- при активном окне, 2 - при окне в фоне

Visibility.every(300000, synchist);
//период запроса на синхронизацию истории звонков

Visibility.every(60000, getLastCalls);
//период запросов на получение истории последних звонков