/* ============================ */
/*         SalesMan CRM         */
/* ============================ */
/* (C) 2019 Vladislav Andreev   */
/*       SalesMan Project       */
/*        www.isaler.ru         */
/*        ver. 2019.x           */
/* ============================ */

/**
 * базовый адрес для файлов плагина
 * @type {string}
 */
const chatsbaseURL = "/plugins/socialChats";

$(document).ready(function () {

	chatsFunc.setIndicator();

	if(cometDevID !== '') {

		cometApi.start({dev_id: cometDevID, user_id: cometUserID, user_key: cometUserKey, node: "app.comet-server.ru"});

		// подписка на канал. Сообщения всем операторам, если чат не назначен
		cometApi.subscription(cometChannel, function (msg) {

			console.log(msg);

			let chatid = msg.data.chatid;
			let text = msg.data.text;
			let tip = msg.data.tip;

			// в противном случае сообщаем о сообщении в другом чате
			if (tip === 'newchat') {

				chatNotifyMe({
					title: "Новый диалог",
					content: text,
					chatid: chatid
				});

			}
			else if (tip === 'newmessage') {

				chatNotifyMe({
					title: "Новое сообщение в диалоге",
					content: text,
					chatid: chatid
				});

			}
			else {

				chatNotifyMe({
					title: "Уведомление",
					content: text,
					chatid: chatid
				});

			}

			chatsFunc.count();

		});

		// подписка на сообщения
		cometApi.subscription("msg", function (msg) {

			let chatid = msg.data.chatid;
			let text = msg.data.text;
			let tip = msg.data.tip;

			// в противном случае сообщаем о сообщении в другом чате
			if (tip === 'newmessage') {

				chatNotifyMe({
					title: "Новое сообщение в диалоге",
					content: text,
					chatid: chatid
				});

			}
			else {

				chatNotifyMe({
					title: "Уведомление",
					content: text,
					chatid: chatid
				});

			}

			chatsFunc.count();

		});

	}

	chatsFunc.count();

	chatNotifyCheck();

});

Visibility.change(function (e, state) {

	// показываем уведомление на вкладке, только если она не активна
	if(state === 'visible') {

		setTimeout(function () {
			faviconChat.reset();
		},500);

	}

});

// как только вкладка становится активной
// скрываем уведомление
if ( !Visibility.hidden() ) {

	faviconChat.reset();

}

/**
 * Показывем фрейм чата
 */
$(document).on("click", ".chats--icon", function () {

	chatsFunc.show();

});
$(document).on("click", '.pop[data-id="chat"]', function () {

	chatsFunc.show();

});

/**
 * Закрываем фрейм чата
 */
$(document).off("click", ".chatframe--close");
$(document).on("click", ".chatframe--close", function (e) {

	parent.$(parent.document).trigger('chatframeClose');

	e.preventDefault();
	e.stopPropagation();

});

/**
 * Открываем чат в отдельном окне
 */
$(document).on('click', '.chatframe--url', function (e) {

	var url = e.currentTarget.dataset.url;

	window.open('/plugins/socialChats/chats.php');

	parent.$(parent.document).trigger('chatframeClose');

});

/**
 * Закрываем окно чата во фрейме
 */
$(document).off("chatframeClose");
$(document).on("chatframeClose", function (e) {

	$('.chatframe--container').css({"left": "110vw"});
	$('iframe#chatframe').attr('src', '');
	$('.chatframe--url').data('url', '');

	e.preventDefault();
	e.stopPropagation();

	return false;

});

var chatsFunc = {
	/**
	 * Установка индикатора
	 */
	setIndicator: function () {

		var html;

		if(!isMobile) {

			html = '' +
				'<div class="plugin--icon chats--icon">' +
				'   <img src="' + chatsbaseURL + '/assets/images/chat.png" width="20">' +
				'   <div class="chats--bullet">0</div>' +
				'</div>';

			$('.plugin--panel').append(html);

		}
		else{

			html = '' +
				'<li class="pop" data-id="chat">' +
				'   <div class="pops"><img src="' + chatsbaseURL + '/assets/images/chat.png" width="20"><span class="bullet chats--bullet">0</span></div>' +
				'</li>';

			//console.log(html);

			$('li[data-id="search"]').closest('ul').prepend(html);

		}

	},
	/**
	 * Загружаем панель чатов во фрейме
	 */
	show: function(){

		$('#chatframe').attr('src', chatsbaseURL+'/chats.php');
		$('.chatframe--container').css({"left": "0"});

		faviconChat.reset();

	},
	/**
	 * Получаем количество не прочитанных чатов
	 * @returns {Promise<void>}
	 */
	count: async function(){

		await fetch(chatsbaseURL + "/php/chats.php?action=browserNotify")
			.then(response => response.text())
			.then(viewData => {

				if ( Visibility.hidden() ) {

					if(viewData > 0)
						faviconChat.badge(viewData);

				}

				$('.chats--bullet').html(viewData);

			});

	}
};

function chatNotifyMe(data) {

	if (Notification.permission === 'granted') {

		if (("Notification" in window)) {

			if (Notification.permission === "granted") {
				var notification = new Notification(data.title, {
					lang: 'ru-RU',
					body: data.content,
					icon: chatsbaseURL+'/assets/images/chat.png',
					tag: 'chat'
				});
			}
			// В противном случае, мы должны спросить у пользователя разрешение
			else if (Notification.permission === 'default') {

				Notification.requestPermission(function (permission) {

					// Не зависимо от ответа, сохраняем его в настройках
					if (!('permission' in Notification)) {
						Notification.permission = permission;
					}
					// Если разрешение получено, то создадим уведомление
					if (permission === "granted") {
						var notification = new Notification(data.title, {
							lang: 'ru-RU',
							body: data.content,
							icon: chatsbaseURL +'/assets/images/chat.png',
							tag: data.chatid
						});
					}

				});
			}

			else return true;

			notification.onshow = function () {

				var wpmupsnd = new Audio(chatsbaseURL+"/assets/audio/call-leave.ogg");
				wpmupsnd.volume = 0.6;
				wpmupsnd.play();

			};
			notification.onclick = function () {

				chatsFunc.show();

			};

		}
		else
			return true;

	}
	else {

		Swal.fire({
			imageUrl: chatsbaseURL +'/assets/images/chat.png',
			position: 'bottom-end',
			background: "var(--blue)",
			title: '<div class="white fs-11">' + data.title + '</div>',
			html: '<div class="white">' + data.content + '</div>',
			showConfirmButton: false,
			timer: 10500
		});

		//$('<audio src="assets/audio/new-notification.ogg" type="audio/ogg" id="chatAudio" autoplay="autoplay"><source src="assets/audio/new-notification.ogg" type="audio/ogg"><source src="assets/audio/new-notification.mp3" type="audio/mpeg"></audio>').appendTo('body');

		//$('#chatAudio')[0].play();

	}

}

function chatNotifyCheck() {

	if (("Notification" in window)) {

		if (Notification.permission === 'default') {

			Notification.requestPermission(function (permission) {

				// Не зависимо от ответа, сохраняем его в настройках
				if (!('permission' in Notification)) {
					Notification.permission = permission;
				}

			});

		}
		else return true;

	}
	else return true;

}