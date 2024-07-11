<?php
/* ============================ */
/*         SalesMan CRM         */
/* ============================ */
/* (C) 2022 Vladislav Andreev   */
/*       SalesMan Project       */
/*        www.isaler.ru         */
/*        ver. 2022.x           */
/* ============================ */

use Cronman\Cronman;
use Salesman\Guides;
use Salesman\User;

set_time_limit( 0 );
error_reporting( E_ERROR );

$rootpath = dirname(__DIR__, 2);

require_once $rootpath."/inc/config.php";
require_once $rootpath."/inc/dbconnector.php";

require_once $rootpath."/inc/auth.php";

require_once $rootpath."/inc/func.php";
require_once $rootpath."/inc/settings.php";
require_once $rootpath."/inc/language/".$language.".php";
require_once $rootpath."/plugins/cronManager/php/autoload.php";

$thisfile = basename( __FILE__ );

$action = $_REQUEST['action'];

createDir($rootpath."/cash/export");

if ( $action == 'setTask' ) {

	$params            = $_REQUEST;
	$params['UserID']  = $iduser1;
	$params['created'] = str_replace( " ", "T", current_datumtime() );
	$params['url']     = 'https://'.$_SERVER["HTTP_HOST"];
	//$params['url']    = 'https://dc-b2b.takemycall.ru';

	unset( $params['action'] );

	$task = new Cronman();
	$task -> setTask( 0, [
		"uid"    => time(),
		"name"   => "Экспорт сделок++",
		"bin"    => "php",
		"script" => $rootpath."/plugins/dealsExportExtended/cronjob.php",
		"task"   => json_encode_cyr( $params ),
		"active" => "on",
		"parent" => "once"
	] );

	$response = [
		"result"  => "ok",
		"error"   => "",
		"message" => "Задание добавлено в очередь. По выполнении вам придет уведомление (колокольчик рядом с аватаркой)",
		"params"  => $params
	];

	print json_encode_cyr( $response );
	exit();

}

$fields = [];

/*поля*/
$fieldsNames = [
	'did'          => 'Deal ID',
	'uid'          => 'Deal UID',
	'clientUID'    => 'Client UID',
	'title'        => 'Название',
	'idcategory'   => 'Этап',
	'stepDate'     => 'Дата смены этапа',
	'datum_plan'   => 'Дата план.',
	'kol'          => 'Сумма план.',
	'marga'        => 'Маржа',
	'direction'    => 'Направление',
	'datum'        => 'Дата создания',
	'datum_izm'    => 'Дата изменения',
	'datum_start'  => 'Период старт',
	'datum_end'    => 'Период финиш',
	'close'        => 'Закрыта',
	'datum_close'  => 'Дата закрытия',
	'status_close' => 'Статус закрытия',
	'des_fact'     => 'Комментарий закрытия',
	'kol_fact'     => 'Сумма факт',
	'clid'         => 'Клиент',
	'date_create'  => 'Дата добавления Клиента',
	'iduser'       => 'Куратор',
	'autor'        => 'Автор',
	'path'         => 'Канал продаж'
];

$res = $db -> query( "SELECT * FROM {$sqlname}field WHERE fld_tip='dogovor' and fld_on='yes' and fld_name NOT IN ('kol_fact','money','pid_list','oborot','period','des','kol','marga') and identity = '$identity'" );
while ($do = $db -> fetch( $res )) {

	if ( $do['fld_name'] == 'marg' )
		$do['fld_name'] = 'marga';

	$fieldsNames[ $do['fld_name'] ] = $do['fld_title'];
	$fields[]                       = $do['fld_name'];

}
$fieldsClientNames = $db -> getIndCol( "fld_name", "SELECT fld_name, fld_title FROM ".$sqlname."field WHERE fld_tip = 'client' AND fld_on = 'yes' AND identity = '$identity'" );

?>
<style>
	label.field {
		background    : var(--white);
		/*margin-right: 1px;*/
		margin-bottom : 1px;
		padding       : 5px;
		border        : 1px dashed var(--gray-superlite);
		border-radius : 3px;
		box-sizing    : border-box !important;
	}
</style>
<DIV class="zagolovok">Экспорт данных по сделкам</DIV>
<form action="/plugins/dealsExportExtended/" method="post" enctype="multipart/form-data" name="uploadForm" id="uploadForm">
	<input type="hidden" name="action" value="setTask">

	<div id="formtabs" style="max-height: 70vh; overflow-y: auto; overflow-x: hidden" class="p5">

		<div class="flex-container box--child">

			<div class="flex-string wp95">

				<div class="Bold uppercase fs-07 gray2 mt5">Использовать выборки</div>
				<select name="list" id="list" class="wp100">
					<optgroup label="Стандартные представления">
						<option value="my" selected="selected">Мои Сделки</option>
						<option value="otdel">Сделки Подчиненных</option>
						<?php if ( $tipuser != "Менеджер продаж" or $ac_import[5] == 'on' ) { ?>
							<option value="all">Все Активные <?= $lang['face']['DealsName']['0'] ?></option>
							<option value="alldeals">Все <?= $lang['face']['DealsName']['0'] ?></option>
							<option value="alldealsday">Все <?= $lang['face']['DealsName']['0'] ?>. За сегодня</option>
							<option value="alldealsweek">Все <?= $lang['face']['DealsName']['0'] ?>. За текущую неделю</option>
							<option value="alldealsmounth">Все <?= $lang['face']['DealsName']['0'] ?>. За текущий месяц</option>
						<?php } ?>
						<option value="close">Закрытые <?= $lang['face']['DealsName']['0'] ?></option>
						<option value="closedealsday">Закрытые <?= $lang['face']['DealsName']['0'] ?>. За сегодня</option>
						<option value="closedealsweek">Закрытые <?= $lang['face']['DealsName']['0'] ?>. За текущую неделю</option>
						<option value="closedealsmounth">Закрытые <?= $lang['face']['DealsName']['0'] ?>. За текущий месяц</option>
					</optgroup>
					<optgroup label="Пользовательские представления">
						<?php
						$result = $db -> query( "SELECT * FROM {$sqlname}search WHERE tip = 'dog' and (iduser = '$iduser1' or share = 'yes') and identity = '$identity' order by sorder" );
						while ($data = $db -> fetch( $result )) {
							print '<option value="search:'.$data['seid'].'">'.$data['title'].'</option>';
						}
						?>
					</optgroup>
				</select>
				<div class="fs-07 gray2">Выборку можно создать в разделе "Сделки"</div>

			</div>
			<div class="flex-string wp5">

				<div class="Bold uppercase fs-07 gray2 mt10">&nbsp;</div>
				<div class="tagsmenuToggler hand relativ mt5" data-id="fhelper">
					<span class="fs-14 blue mt5"><i class="icon-help-circled"></i></span>
					<div class="tagsmenu fly1 right hidden" id="fhelper" style="right:0; top: 100%">
						<div class="blok1 w350 fs-09">
							<ul>
								<li>Ознакомьтесь с Документацией на модуль [&nbsp;<a href="https://salesman.pro/docs/54" target="_blank" title="Перейти в Документацию">Справка</a>&nbsp;]</li>
								<li>Вы можете использовать <b>поисковые выборки</b> для большей гибкости [&nbsp;<a href="https://salesman.pro/docs/45#searcheditor" target="_blank" title="Перейти в Документацию">Справка</a>&nbsp;]</li>
								<li>Если экспорт идет в формате CSV, то данные необходимо <b>Импортировать</b> в Excel - Вкладка "Данные" / Из текста</li>
								<li>Чем больше информации экспортируется, тем дольше времени занимает этот процесс!</li>
								<li>Для <b class="red">исключения полей</b> укажите их в блоке "Исключить" - они не будут выведены в файле экспорта</li>
								<li>Возможна загрузка только данных контакта, присоединенного к сдекле. Если контактов несколько, то выбирается первый</li>
							</ul>

						</div>
					</div>
				</div>

			</div>

		</div>

		<div class="divider mt10 mb10"><i class="icon-plus-circled green"></i> Включить</div>

		<div class="flex-container">

			<div class="flex-string">

				<div class="flex-container box--child">
					<label class="flex-string wp50 field"><input name="include[]" type="checkbox" value="lasthist">&nbsp;Дата активности (сделка)</label>
					<label class="flex-string wp50 field"><input name="include[]" type="checkbox" value="nexttask">&nbsp;Дата след.напоминания (сделка)</label>
					<label class="flex-string wp50 field"><input name="include[]" type="checkbox" value="historyone">&nbsp;1 активность (сделка)</label>
					<label class="flex-string wp50 field"><input name="include[]" type="checkbox" value="history">&nbsp;3 активности (сделка)</label>
					<label class="flex-string wp50 field"><input name="include[]" type="checkbox" value="speca">&nbsp;Спецификацию (сделка)</label>
					<label class="flex-string wp50 field"><input name="include[]" type="checkbox" value="person">&nbsp;Контакт (тел. + email)</label>
					<label class="flex-string wp50 field"><input name="include[]" type="checkbox" value="tipcmr">&nbsp;Тип отношений (клиент)</label>
					<label class="flex-string wp50 field"><input name="include[]" type="checkbox" value="clientcategory">&nbsp;Отрасль (клиент)</label>
					<label class="flex-string wp50 field"><input name="include[]" type="checkbox" value="territory">&nbsp;Территорию (клиент)</label>
					<?php
					// добавим кастомные поля клиента
					$cfields = $db -> getAll( "SELECT fld_title,fld_name FROM {$sqlname}field WHERE fld_tip = 'client' AND fld_on = 'yes' AND fld_name LIKE 'input%' AND identity = '$identity'" );
					foreach ( $cfields as $cfield ) {

						print '<label class="flex-string wp50 field"><input name="include[client][]" type="checkbox" value="'.$cfield['fld_name'].'">&nbsp;'.$cfield['fld_title'].' (клиент)</label>';

					}
					?>
				</div>

			</div>

		</div>

		<div class="divider mt10 mb10"><i class="icon-minus-circled red"></i> Исключить</div>

		<div class="flex-container">

			<div class="flex-string">

				<div class="flex-container">
					<?php
					$exclude_array = [
						'did',
						'title',
						'clid',
						'pid'
					];
					foreach ( $fieldsNames as $k => $v ) {

						if ( !in_array( $k, $exclude_array ) ) {
							print '<label class="flex-string wp50 field"><input name="exclude[]" type="checkbox" value="'.$k.'">&nbsp;'.$v.'</label>';
						}

					}
					?>
				</div>

			</div>

		</div>

		<div class="space-50"></div>

	</div>

	<hr>

	<div class="button--pane text-right">

		<A href="javascript:void(0)" onclick="$('#uploadForm').submit()" class="button">Выполнить</A>
		<A href="javascript:void(0)" onclick="DClose()" class="button">Закрыть</A>

	</div>

</form>

<script>
	$(function () {

		$('#dialog').css('width', '600px').center();

		$('#uploadForm').ajaxForm({
			dataType: 'json',
			beforeSubmit: function () {

				var em = checkRequired();

				if (em === false) return false;

				$('#dialog').css('display', 'none');
				$('#dialog_container').css('display', 'none');

				return true;

			},
			success: function (data) {

				Swal.fire({
					icon: 'info',
					position: 'bottom-end',
					background: "var(--blue)",
					title: '<div class="white fs-11">Отлично!</div>',
					html: '<div class="white">' + data.message + '</div>',
					showConfirmButton: false,
					timer: 3500
				});

			},
			statusCode: {
				404: function () {
					DClose();
					Swal.fire({
						title: "Ошибка 404: Страница не найдена!",
						type: "warning"
					});
				},
				500: function () {
					DClose();
					Swal.fire({
						title: "Ошибка 500: Ошибка сервера!",
						type: "error"
					});
				}
			}

		})

	});

</script>
