//Карточка. Блок Контакты
$(document).ajaxComplete(function () {

	smssender_addSMS();

});

$(function() {

	ShowModal.subscribe(function () {

		smssender_addSMS();

	});

	CardLoad.subscribe(function () {

		//smssender_addSMS();

	});

});

function smssender_addSMS() {

	//var phones = [];

	if (in_array('smsSender', $pluginEnambled)) {

		$('.phonenumber').each(function () {

			var phone = $(this).data('phone');
			var count = $(this).find('.sms').length;
			var clid = $(this).data('clid');
			var pid = $(this).data('pid');

			if ($(this).hasClass('ismob') && count === 0) {

				$(this).append('<a href="javascript:void(0)" class="sms" onclick="doLoad(\'plugins/smsSender/index.php?phone=' + phone + '&clid=' + clid + '&pid=' + pid + '&action=sms.compose.ext\');" title="Отправить СМС" class="blue"><i class="icon-paper-plane red"></i></a>');

				//phones.push(phone);

			}

		});

	}

	//console.log(phones);

}