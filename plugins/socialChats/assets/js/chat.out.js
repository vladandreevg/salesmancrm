/* ============================ */
/*         SalesMan CRM         */
/* ============================ */
/* (C) 2019 Vladislav Andreev   */
/*       SalesMan Project       */
/*        www.isaler.ru         */
/*        ver. 2019.x           */
/* ============================ */

// Скрипт, который будет показывать иконки каналов на нашем сайте

(function() {

// Localize jQuery variable
	var jQuery;

	var $interval;

	//console.log(window.jQuery);

	/******** Load jQuery if not present *********/
	if (window.jQuery === undefined || window.jQuery.fn.jquery !== '3.4.1') {

		var script_tag = document.createElement('script');
		script_tag.setAttribute("type","text/javascript");
		script_tag.setAttribute("src", "https://code.jquery.com/jquery-3.4.1.min.js");

		if (script_tag.readyState) {
			script_tag.onreadystatechange = function () { // For old versions of IE
				if (this.readyState == 'complete' || this.readyState == 'loaded') {
					scriptLoadHandler();
				}
			};
		}
		else { // Other browsers
			script_tag.onload = scriptLoadHandler;
		}

		// Try to find the head, otherwise default to the documentElement
		(document.getElementsByTagName("head")[0] || document.documentElement).appendChild(script_tag);

	}
	else {
		// The jQuery version on the window is the one we want to use
		jQuery = window.jQuery;
		main();
	}

	/******** Called once jQuery has loaded ******/
	function scriptLoadHandler() {
		// Restore $ and window.jQuery to their previous values and store the
		// new jQuery in our local jQuery variable
		jQuery = window.jQuery.noConflict(true);
		// Call our main function
		main();

		console.log('scriptLoadHandler');

	}

	/******** Our main function ********/
	function main() {

		includeCSS("//"+ salesman.host + "/plugins/socialChats/assets/css/chats.out.css");

		jQuery(document).ready(async function() {

			await fetch("//"+ salesman.host + "/plugins/socialChats/php/extend.php?identity="+salesman.identity+"&apkey="+salesman.apkey)
				.then(response => response.json())
				.then(viewData => {

					//console.log(viewData);

					if(viewData.icons.length > 0) {

						var string = '';

						for (var i in viewData.icons) {

							string += '<li class="chat-icon chat-icon-' + viewData.icons[i].icon + '" title="Открыть"><a href="' + viewData.icons[i].uri + '" target="_blank"></a></li>';

						}

						//console.log(string);

						jQuery('body').append('' +
							'<style>' +
							':root {' +
							'   --pulse : '+ viewData.wiget.shadow +';' +
							'}' +
							'.chat-main.chat-icon-chat {' +
							'   background-color          : '+ viewData.wiget.color +';' +
							'   box-shadow                : 0 0 0 '+ viewData.wiget.shadow +';' +
							'}' +
							'ul.chat-block {' +
							'   bottom     : '+ viewData.wiget.bottom +'px;' +
							'   right      : '+ viewData.wiget.right +'px;' +
							'}' +
							'</style>' +
							'<ul class="chat-block">' +
							'   <li class="chat-container">' +
							'       <div class="chat-sub"><div class="chat-main chat-icon-chat"></div></div>' +
							'       <ul class="chat-icons">' + string + '</ul>' +
							'   </li>' +
							'</ul>');

						jQuery('.chat-main').off('click');
						jQuery('.chat-main').on('click', function () {

							jQuery('.chat-container').toggleClass('open');
							jQuery('.chat-main').toggleClass('chat-icon-chat chat-icon-cancel');

							if (jQuery('.chat-container').hasClass('open'))
								clearInterval($interval);
							else
								startShake();

						});

						jQuery(document).mouseup(function (e) {

							var div = jQuery(".chat-sub");
							if (!div.is(e.target) && div.has(e.target).length === 0) {
								jQuery('.chat-container').removeClass('open');
								jQuery('.chat-main').removeClass('chat-icon-cancel').addClass('chat-icon-chat');
							}
							startShake();

						});

						startShake();

					}

				});

		});

	}

	function startShake(){

		clearInterval($interval);
		$interval = setInterval( function() {

			jQuery('.chat-sub').addClass('chat-shake');

			setTimeout(function () {
				jQuery('.chat-sub').removeClass('chat-shake');
			}, 5000);

		}, 10000);

	}

	function includeCSS(file) {

		var a = document.createElement("link");
		a.rel = "stylesheet";
		a.href = file;
		document.getElementsByTagName("head")[0].appendChild(a)

	}

})(); // We call our anonymous function immediately

console.log(salesman);
