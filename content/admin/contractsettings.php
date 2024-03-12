<?php
/* ============================ */
/*         SalesMan CRM         */
/* ============================ */
/* (C) 2016 Vladislav Andreev   */
/*       SalesMan Project       */
/*        www.isaler.ru         */
/*        ver. 2019.x           */
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

//Редактирование категории
if ( $action == "save" ) {

	$good    = 0;
	$bad     = 0;
	$message = '';

	//Сохраним настройки для генератора номеров счетов и номеров договоров
	$contract_format = $_POST['contract_format'];
	$contract_num    = $_POST['contract_num'];

	$db -> query( "UPDATE ".$sqlname."settings SET ?u WHERE id = '$identity'", [
		'contract_format' => $contract_format,
		'contract_num'    => $contract_num,
	] );

	unlink( $rootpath."/cash/".$fpath."settings.all.json" );
	$good++;

	print "Готово. ".$message;

	exit();
}

if ( empty( $_SERVER['HTTP_X_REQUESTED_WITH'] ) && strtolower( $_SERVER['HTTP_X_REQUESTED_WITH'] ) != 'xmlhttprequest' ) {

	print '<div class="bad" align="center"><br>Доступ запрещен.<br>Обратитесь к администратору.<br><br></div>';
	exit();

}

if ( $action == "" ) {
	?>
	<h2>&nbsp;Раздел: "Номера документов"</h2>

	<?php
	if(!$otherSettings['contract']){

		print '<div class="warning mb10">Ведение Договоров <b>отключено</b> (см. Общие настройки / Дополнения к сделкам)</div>';

	}
	?>

	<FORM action="/content/admin/<?php echo $thisfile; ?>" method="post" enctype="multipart/form-data" name="set" id="set">
	<INPUT type="hidden" name="action" id="action" value="save">

	<h2 class="blue mt20 mb20 pl5">Генератор номеров</h2>

	<div class="flex-container mt20 box--child ha p10 border-bottom">

		<div class="flex-string wp20 right-text fs-12 gray2 pt10">Формат номера договора:</div>
		<div class="flex-string wp80 pl10">

			<div class="ul--group">
				<ul>
					<li onclick="addItem('contract_format','cnum');"><span>Номер</span></li>
					<li onclick="addItem('contract_format','DD');"><span>День</span></li>
					<li onclick="addItem('contract_format','MM');"><span>Месяц</span></li>
					<li onclick="addItem('contract_format','YY');"><span>Год (2 цифры)</span></li>
					<li onclick="addItem('contract_format','YYYY');"><span>Год (4 цифры)</span></li>
				</ul>
			</div>

			<input name="contract_format" type="text" id="contract_format" size="50" value="<?= $contract_format ?>">
			<div class="smalltxt black"> При пустом поле &quot;Формат номера&quot; счетчик и генератор будет отключен.</div>

		</div>

	</div>

	<div class="flex-container box--child ha p10 border-bottom">

		<div class="flex-string wp20 right-text fs-12 gray2 pt5">Счетчик договоров:</div>
		<div class="flex-string wp80 pl10">

			<input name="contract_num" type="text" id="contract_num" size="10" value="<?= $contract_num ?>"/>
			<div class="smalltxt black">Укажите номер последнего договора.<b> Если договоров не было укажите 0 (ноль)</b>.
			</div>

		</div>

	</div>

	<div class="button--group1 box--child" style="position: fixed; bottom: 40px; left: 380px; z-index: 100;">

		<a href="javascript:void(0)" onclick="$('#set').trigger('submit')" class="button bluebtn box-shadow" title="Добавить"><i class="icon-ok-circled"></i>Сохранить</a>

	</div>

	<div class="pagerefresh refresh--icon admn red" onclick="$('#set').trigger('submit')" title="Сохранить"><i class="icon-ok-circled"></i></div>
	<div class="pagerefresh refresh--icon admn orange" onclick="openlink('https://salesman.pro/docs/12')" title="Документация"><i class="icon-help"></i></div>

	<div class="space-100">&nbsp;</div>

	<script>

		var editorr;

		$(function () {

			$('#set').ajaxForm({
				beforeSubmit: function () {
					var $out = $('#message');
					$out.empty();
					$out.css('display', 'block').append('<div id=loader><img src=/assets/images/loader.gif> Загрузка данных. Пожалуйста подождите...</div>');
					return true;
				},
				success: function (data) {

					//$("#contentdiv").load('content/admin/<?php echo $thisfile; ?>');

					razdel(hash);

					$('#message').fadeTo(1, 1).css('display', 'block').html(data);
					setTimeout(function () {
						$('#message').fadeTo(1000, 0);
					}, 20000);

				}
			});

			$('#dialog').center();

		});

		$('.tagsmenu li').on('click', function () {

			var $t = $('b', this).html();
			//addTagInEditor('invoice_suffix', $t);

			if (!in_array($t, ['{{speka}}', '{{tovar}}', '{{usluga}}', '{{material}}'])) addTagInEditor('invoice_suffix', $t);
			else {

				var s1, s2, itog;

				switch ($t) {

					case "{{speka}}":

						s1 = "{{#speka}}";
						s2 = "{{/speka}}";
						itog = "{{ItogMaterial}}";

						break;
					case "{{tovar}}":

						s1 = "{{#tovar}}";
						s2 = "{{/tovar}}";
						itog = "{{ItogTovar}}";

						break;
					case "{{usluga}}":

						s1 = "{{#usluga}}";
						s2 = "{{/usluga}}";
						itog = "{{ItogUsluga}}";

						break;
					case "{{material}}":

						s1 = "{{#material}}";
						s2 = "{{/material}}";
						itog = "{{ItogMaterial}}";

						break;

				}

				$t = s1 + '{{Number}}. {{#Artikul}}[{{Artikul}}]  {{/Artikul}}<b>{{Title}}</b><em>{{Comments}}</em>, {{Kol}} {{Edizm}}, {{Price}} ({{nalogTitle}}: {{Nalog}}) - {{Summa}}<br>' + s2 + '<br><br>Итого: <b>' + itog + '</b><br>';

				addTagInEditor('invoice_suffix', $t);

			}

		});

		function addItem(txtar, myitem) {
			//alert (html);
			var textt = $('#' + txtar).val();
			$('#' + txtar).val(textt + '{' + myitem + '}');
		}

	</script>
	<?php
}