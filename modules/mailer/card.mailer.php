<?php
/* ============================ */
/* (C) 2016 Vladislav Andreev   */
/*       SalesMan Project       */
/*        www.isaler.ru         */
/*        ver. 2017.x           */
/* ============================ */
?>
<div class="msglist"></div>
<script>

	var stickwidth = $("#tabmail").width();

	$.Mustache.load('/modules/mailer/tpl/interface.mustache');

	$(function () {

		mailerLoad();

	});

	$('.fdownload').bind('click', function () {

		var uid = $(this).data('uid');
		var mid = $(this).data('mid');

		$(this).append('<img src="/assets/images/loading.gif" width="12">');

		$.get('/modules/mailer/core.mailer.php?uid=' + uid + '&mid=' + mid + '&action=getAttachments', function (data) {

			if (data.length > 0 || !data) {

			}
			else {
				alert('Не могу загрузить файлы. Возможно письмо удалено с сервера!');
			}
			settab('mail');

			return true;

		}, 'json')
			.done(function () {
			});
	});

	$(document).off('change', '#ympage');
	$(document).on('change', '#ympage', function(){

		var page = $(this).val();
		mailerLoad(page);

	});

	function mailerLoad(page) {

		if (!page)
			page = 1;

		var did  = $('#did').val();
		var clid = $('#clid').val();
		var pid  = $('#pid').val();

		if(clid === undefined)
			clid = 0;

		if(pid === undefined)
			pid = 0;

		if(did === undefined)
			did = 0;

		var url = '/modules/mailer/list.mailer.php?card=true&pid='+pid+'&clid='+clid+'&did='+did+'&page=' + page;
		var pg = '';
		var mpage, pageall, select;

		$('.msglist').append('<div id="loader" class="pad10"><img src="/assets/images/loading.svg"></div>');

		$.getJSON(url, function (data) {

			$('.msglist').empty().mustache('card', data);

			mpage = data.page;
			pageall = data.pageall;

			pg = 'Стр. ' + mpage + ' из ' + pageall + '&nbsp;';

			if (pageall > 1) {

				var prev = mpage - 1;
				var next = mpage + 1;

				for(var i = 1;i <= pageall; i++){

					select += '<option value="'+i+'" '+(i == page ? 'selected' : '')+'>&nbsp;&nbsp;'+ i +'&nbsp;&nbsp;</option>';

				}

				if(mpage > 1)
					pg += '<div onclick="mailerLoad(\''+ prev+ '\')" data-page="'+ prev+ '"><</div>&nbsp;';

				pg += '&nbsp;<span class="select inline pull-left"><select id="ympage" name="ympage">'+select+'</select></span>&nbsp;';

				if(mpage < pageall)
					pg += '<div onclick="mailerLoad(\''+ next+ '\')" data-page="'+ next+ '">></div>';

				$('.msglist').find('#pages').removeClass('hidden').html(pg);

			}
			else
				$('.msglist').find('#pages').addClass('hidden');

		})
			.done(function () {

				$mailer.formatQuoteCard();
				$mailer.previewImageCard();

				$('.msglist').find("#pages").css({"bottom": "0px", "position": "fixed", "width": stickwidth + "px"});

			});

	}

</script>