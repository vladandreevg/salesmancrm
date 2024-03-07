<?php
/**
 * @license  http://isaler.ru/
 * @author   Vladislav Andreev, http://iandreyev.ru/
 * @charset  UTF-8
 * @version  6.4
 */
?>
<?php
error_reporting( 0 );
header( "Pragma: no-cache" );

$rootpath = realpath( __DIR__.'/../../' );

include $rootpath."/inc/config.php";
include $rootpath."/inc/dbconnector.php";
include $rootpath."/inc/auth.php";
include $rootpath."/inc/func.php";
include $rootpath."/inc/settings.php";
include $rootpath."/developer/events.php";
include $rootpath."/inc/language/".$language.".php";

$thisfile = basename( __FILE__ );

$action = $_REQUEST['action'];
$id     = $_REQUEST['id'];

if ( $action == "settings_do" ) {

	$params['enShowButtonLeft'] = $_REQUEST['enShowButtonLeft'];
	$params['enShowButtonCall'] = $_REQUEST['enShowButtonCall'];

	$settings = json_encode_cyr( $params );

	try {

		$db -> query( "update ".$sqlname."modules set content = '".$settings."' WHERE mpath = 'entry' and identity = '$identity'" );
		print "Сделано";

	}
	catch ( Exception $e ) {
		echo $e -> getMessage();
	}

	unlink( $rootpath."/cash/".$fpath."settings.all.json" );

	exit();
}
if ( $action == "" ) {
	?>
	<FORM action="/modules/entry/settings.entry.php" method="post" enctype="multipart/form-data" name="set" id="set">
		<INPUT type="hidden" name="action" id="action" value="settings_do">
		<TABLE id="bborder">
			<thead>
			<TR height="35">
				<th colspan="2" class="header_contaner" align="left"><b class="blue">Настройка модуля</b></th>
			</TR>
			</thead>
			<tr>
				<td width="170" align="right" valign="top">
					<div class="fs-12 gray2">Кнопка в левой панели:</div>
				</td>
				<td>
					<label><input type="checkbox" name="enShowButtonLeft" id="enShowButtonLeft" value="yes" <?php if ( $setEntry['enShowButtonLeft'] == 'yes' )
							print "checked"; ?>>&nbsp;Вкл.</label><br>
					<div class="smalltxt blue">Включает кнопку добавления Обращения на левой панели: Рабочего стола, списков Клиентов, Контактов, Сделок, в Календаре</div>
				</td>
			</tr>
			<tr>
				<td width="170" align="right" valign="top">
					<div class="fs-12 gray2">Кнопка при звонках:</div>
				</td>
				<td>
					<label><input type="checkbox" name="enShowButtonCall" id="enShowButtonCall" value="yes" <?php if ( $setEntry['enShowButtonCall'] == 'yes' )
							print "checked"; ?>>&nbsp;Вкл.</label><br>
					<div class="smalltxt blue">Включает кнопку добавления Обращения на левой панели: Рабочего стола, списков Клиентов, Контактов, Сделок, в Календаре</div>
				</td>
			</tr>
			<tr class="hidden">
				<td width="170" align="right" valign="top">
					<div class="fs-12 gray2 pt7">Срок обработки:</div>
				</td>
				<td>
					<input type="text" name="enTimeControl" id="enTimeControl" value="<?= $setEntry['enTimeControl'] ?>" class="w100">&nbsp;<div class="fs-12 gray2 pt7 inline pl10">дней</div>
					<br>
					<div class="smalltxt blue">Включает контроль сроков обработки обращения. Если не указано - отключено</div>
					<div class="viewdiv">Модуль учитывает абсолютное время - без учета не рабочего времени и выходных</div>
				</td>
			</tr>
		</TABLE>

		<br><br>

		<div class="button--group1 box--child" style="position: fixed; bottom: 40px; left: 380px; z-index: 100;">
			<a href="javascript:void(0)" class="button" onClick="$('#set').submit()"><span>Сохранить</span></a>
		</DIV>

		<div class="pagerefresh refresh--icon admn orange" onclick="openlink('https://salesman.pro/docs/37')" title="Документация"><i class="icon-help"></i></div>

		<div class="space-100"></div>

	</FORM>
	<script type="text/javascript">
		$(document).ready(function () {
			$('#set').ajaxForm({
				beforeSubmit: function () {
					var $out = $('#message');
					$out.empty();
					$out.css('display', 'block').append('<div id=loader><img src=/assets/images/loader.gif> Загрузка данных. Пожалуйста подождите...</div>');
					return true;
				},
				success: function (data) {
					$('#contentdiv').load('modules/entry/settings.entry.php').append('<img src="/assets/images/loading.gif">');
					$('#message').fadeTo(1, 1).css('display', 'block').html(data);
					setTimeout(function () {
						$('#message').fadeTo(1000, 0);
					}, 20000);
				}
			});
		});
	</script>
	<?php
}
?>