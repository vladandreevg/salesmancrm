<?php
/* ============================ */
/* (C) 2016 Vladislav Andreev   */
/*       SalesMan Project       */
/*        www.isaler.ru         */
/*        ver. 2017.x           */
/* ============================ */
?>
<?php
error_reporting(E_ERROR);
header("Pragma: no-cache");

$rootpath = realpath( __DIR__.'/../../' );

include $rootpath."/inc/config.php";
include $rootpath."/inc/dbconnector.php";
include $rootpath."/inc/auth.php";
include $rootpath."/inc/func.php";
include $rootpath."/inc/settings.php";
include $rootpath."/inc/language/".$language.".php";

$thisfile = basename( __FILE__ );

$mid = $_REQUEST['mid'];
?>
<!DOCTYPE html>
<html lang="ru">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
<title>Рассыльщик SalesMan CRM</title>
<LINK rel="stylesheet" href="/assets/css/app.css">
<LINK rel="stylesheet" href="/assets/css/app.card.css">
<link rel="stylesheet" href="/assets/css/fontello.css">
<script src="/assets/js/jquery/jquery-3.4.1.min.js"></script>
<script src="/assets/js/jquery/jquery-migrate-3.0.0.min.js"></script>
</head>
<body>
<div id="contentdiv">
	<div class="zagolovok left Bold">Отправка сообщений</div>
	<div class="center-text blue">В этом окне отображается ход отправки сообщений из рассылки.</div>
	<div id="attention" class="div-center pad10 miditxt center-text"><span class="pull-left"><i class="icon-attention-1 red icon-2x"></i></span><b class="red">На закрывайте окно до выполнения.</b></div>
	<div class="div-center"><p><progress class="ph_meter" value="0.0" max="1.0">0.0</progress></p></div>
	<div id="rez" class="pad10 div-center"></div>
</div>
<script type="text/javascript">

document.cookie = "mailer=";

workit();
iWork = setInterval(workit, 2000);

function workit(){

	var isWork = getCookie('mailer');
	//alert(isWork);
	if(isWork != 'work') sendMessageList();

}
function sendMessageList(){

	var url = 'core.maillist.php?action=startMailing&mid=<?=$mid?>';

	document.cookie = "mailer=work";
	$.get(url, function(data){

		if(data.result == 'resume') $('#rez').html('Всего получателей: <b>'+data.all + '</b>, Отправлено: '+data.sent);
		else {
			$('#rez').html('<div class="blue miditxt Bold"><i class="icon-ok-circled"></i> Выполнено. Работа завершена.</div>');
			$('#attention').hide();
			clearInterval(iWork);
			window.opener.configpage();
		}

		var perc = parseFloat(data.meter)*100;
		$('.ph_meter').val(data.meter).html(perc);

	},"json")
	.complete(function() {
		document.cookie = "mailer=";
	});
}

function getCookie(name) {
	var cookie = " " + document.cookie;
	var search = " " + name + "=";
	var setStr = null;
	var offset = 0;
	var end = 0;
	if (cookie.length > 0) {
		offset = cookie.indexOf(search);
		if (offset != -1) {
			offset += search.length;
			end = cookie.indexOf(";", offset)
			if (end == -1) {
				end = cookie.length;
			}
			setStr = unescape(cookie.substring(offset, end));
		}
	}
	return (setStr);
}
</script>
</body>
</html>