<?php
/* ============================ */

/*         SalesMan CRM         */
/* ============================ */
/* (C) 2018 Vladislav Andreev   */
/*       SalesMan Project       */
/*       www.salesman.pro       */
/*        ver. 2018.x           */
/* ============================ */

error_reporting( E_ERROR );
header( "Pragma: no-cache" );

$rootpath = dirname( __DIR__, 2 );

include $rootpath."/inc/config.php";
include $rootpath."/inc/dbconnector.php";
include $rootpath."/inc/auth.php";
include $rootpath."/inc/func.php";
include $rootpath."/inc/settings.php";

$thisfile = basename( __FILE__ );

$action = $_REQUEST['action'];

if ($action == "edit.on") {
	
	$s = $_REQUEST;

	$value = [
		"key"    => rij_crypt( $s['key'], $skey, $ivc ),
		"secret" => rij_crypt( $s['secret'], $skey, $ivc )
	];

	$id = $db -> getOne( "SELECT id FROM {$sqlname}customsettings WHERE tip = '$tip' AND identity = '$identity'" ) + 0;
	if ($id > 0) {

		$db -> query( "UPDATE {$sqlname}customsettings SET ?u WHERE id = '$id' and identity = '$identity'", ["params" => json_encode( $value )] );

	}
	else {

		$db -> query( "INSERT INTO {$sqlname}customsettings SET ?u", [
			"tip"      => $tip,
			"params"   => json_encode( $value ),
			"identity" => $identity
		] );

	}
	
	print "Выполнено";
	
	unlink( $rootpath."/cash/".$fpath."settings.all.json" );
	
	exit();
	
}

if ($action == "edit") {
	
	$tip = $_REQUEST['tip'];
	
	$result = $db -> getRow( "SELECT * FROM {$sqlname}customsettings WHERE tip = '$tip' and identity = '$identity'" );
	$id     = $result["id"];
	$params = json_decode( $result["params"], true );
	
	$key    = rij_decrypt( $params["key"], $skey, $ivc );
	$secret = rij_decrypt( $params["secret"], $skey, $ivc );
	
	if ($tip == 'dadata') {
		$title = "Dadata.ru";
	}
	
	?>
	<div class="zagolovok">Параметры <?= $title ?></div>

	<form method="post" action="/content/admin/<?php echo $thisfile; ?>" enctype="multipart/form-data" name="form" id="form" autocomplete="off">
		<input name="action" type="hidden" value="edit.on">
		<input name="id" type="hidden" value="<?= $id ?>">

		<DIV id="formtabs" class="box--child" style="max-height:80vh; overflow-x: hidden; overflow-y: auto !important;">

			<div class="flex-container box--child mt10 mb10">

				<div class="flex-string wp20 gray2 fs-12 pt7 right-text">Ключ:</div>
				<div class="flex-string wp80 pl10">
					<input name="<?= $tip ?>[key]" id="<?= $tip ?>[key]" class="wp97" value="<?= $key ?>">
				</div>

			</div>

			<div class="flex-container box--child mt10 mb10">

				<div class="flex-string wp20 gray2 fs-12 pt7 right-text">Подпись:</div>
				<div class="flex-string wp80 pl10">
					<input name="<?= $tip ?>[secret]" id="<?= $tip ?>[secret]" class="wp97" value="<?= $secret ?>">
				</div>

			</div>

		</DIV>

		<hr>

		<div align="right">

			<a href="javascript:void(0)" onclick="$('#form').trigger('submit')" class="button">Выполнить</a>&nbsp;
			<a href="javascript:void(0)" onClick="DClose()" class="button"><span>Отмена</span></a>

		</div>
	</form>

	<script>
		$(function () {

			$('#dialog').css('width', '600px');

			$('#form').ajaxForm({
				beforeSubmit: function () {

					var $out = $('#message');
					var em = checkRequired();

					if (!em)
						return false;

					$('#dialog').css('display', 'none');
					$('#dialog_container').css('display', 'none');

					$out.css('display', 'block').append('<div id=loader><img src=/assets/images/loader.gif> Загрузка данных. Пожалуйста подождите...</div>');

					return true;

				},
				success: function (data) {

					$('#contentdiv').load('/content/admin/<?php echo $thisfile; ?>');
					$('#message').fadeTo(1, 1).css('display', 'block').html(data);

					setTimeout(function () { $('#message').fadeTo(1000, 0); }, 20000);

					DClose();

				}
			});

		});
	</script>
	<?php
	
	exit();
	
}

if ($action == "") {
	
	?>
	<h2 class="blue mt20 mb20 pl5">Интеграция с различными сервисами</h2>

	<div class="space-20"></div>

	<div class="flex-container mt20 box--child">

		<div class="flex-string wp20 right-text fs-12 black Bold">Dadata.ru:</div>
		<div class="flex-string wp60 pl10">

			<div class="infodiv">
				Сервис Dadata предоставляет доступ к подсказкам при вводе адреса.<br>
				Сервис требует авторизации с использованием API KEY, который можно получить в личном кабинете после регистрации в сервисе.<br><br>

				<a href="https://dadata.ru/?from=https://salesman.pro" title="" class="blue">https://dadata.ru/</a>
			</div>

		</div>
		<div class="flex-string wp20 pl10">
			
			<?php
			print '<a href="javascript:void(0)" onclick="doLoad(\'/content/admin/'.$thisfile.'?tip=dadata&action=edit\');" class="button"><i class="icon-edit"></i>Настроить</a>';
			?>

		</div>

	</div>

	<hr>
	<?php
	
}