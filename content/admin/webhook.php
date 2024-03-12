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

$rootpath = dirname(__DIR__, 2);

include $rootpath."/inc/config.php";
include $rootpath."/inc/dbconnector.php";
include $rootpath."/inc/auth.php";
include $rootpath."/inc/func.php";
include $rootpath."/inc/settings.php";

$thisfile = basename( __FILE__ );

$events = [
	"client.import"      => "Импорт клиентов",
	"client.expressadd"  => "Добавлен клиент,Контакт. Экспресс-форма",
	"client.add"         => "Добавлен клиент",
	"client.edit"        => "Изменен клиент",
	"client.delete"      => "Удален клиент",
	"client.change.recv" => "Изменены реквизиты клиента",
	"client.change.user" => "Изменен ответственный клиента",
	"person.add"         => "Добавлен контакт",
	"person.edit"        => "Изменен контакт",
	"person.delete"      => "Удален контакт",
	"person.change.user" => "Изменен ответственный контакта",
	"deal.import"        => "Импорт сделок",
	"deal.add"           => "Добавлена сделка",
	"deal.edit"          => "Изменена сделка",
	"deal.delete"        => "Удалена сделка",
	"deal.change.user"   => "Изменен ответственный за сделку",
	"deal.change.step"   => "Изменен этап сделки",
	"deal.close"         => "Закрыта сделка",
	"invoice.add"        => "Добавлен счет",
	"invoice.edit"       => "Изменен счет",
	"invoice.doit"       => "Оплачен счет",
	"invoice.expressadd" => "Внесена оплата по сделке",
	"task.add"           => "Напоминание добавлено",
	"task.edit"          => "Напоминание изменено",
	"task.doit"          => "Напоминание выполнено",
	"history.add"        => "Добавлена активность",
	"history.edit"       => "Изменена активность",
	"lead.add"           => "Добавлена заявка",
	"lead.setuser"       => "Назначен ответственный по заявке",
	"lead.do"            => "Обработана заявка",
	"entry.add"          => "Добавлено обращение",
	"entry.status"       => "Обработано обращение",
	"contract.add"       => "Добавлен документ",
	"contract.edit"      => "Изменен документ",
	"contract.delete"    => "Удален документ",
	"akt.add"            => "Добавлен акт",
	"akt.edit"           => "Изменен акт",
	"akt.delete"         => "Удален акт",
];

ksort($events);

//установка
$da = $db -> getCol( "SELECT COUNT(*) as count FROM INFORMATION_SCHEMA.STATISTICS WHERE table_schema = '$database' and TABLE_NAME = '{$sqlname}webhook'" );
if ( $da[ 0 ] == 0 ) {

	$db -> query( "CREATE TABLE `{$sqlname}webhook` (`id` INT(20) NOT NULL AUTO_INCREMENT,`title` VARCHAR(255) NULL DEFAULT 'event',`event` VARCHAR(255) NULL DEFAULT NULL,`url` TINYTEXT NULL,`identity` INT(20) NOT NULL DEFAULT '1',PRIMARY KEY (`id`)) COLLATE ='utf8_general_ci' ENGINE=InnoDB" );

}

$da = $db -> getCol( "SELECT COUNT(*) as count FROM INFORMATION_SCHEMA.STATISTICS WHERE table_schema = '$database' and TABLE_NAME = '{$sqlname}webhooklog'" );
if ( $da[ 0 ] == 0 ) {

	$db -> query( "CREATE TABLE `{$sqlname}webhooklog` (`id` INT(30) NOT NULL AUTO_INCREMENT,`datum` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP, `event` VARCHAR(50) NOT NULL, `query` TEXT NOT NULL,`response` TEXT NOT NULL,`identity` INT(20) NOT NULL DEFAULT '1',PRIMARY KEY (`id`)) COLLATE='utf8_general_ci' ENGINE=InnoDB" );

}

$action = $_REQUEST[ 'action' ];

if ( $action == "delete" ) {

	$id    = $_REQUEST[ 'id' ];
	$multi = $_REQUEST[ 'multi' ];

	if ( empty( $multi ) ) {

		$db -> query( "DELETE FROM {$sqlname}webhook WHERE id = '$id' and identity = '$identity'" );

	}
	else {

		//print_r($multi);
		$db -> query( "DELETE FROM {$sqlname}webhook WHERE id IN (".yimplode( ",", $multi ).") and identity = '$identity'" );

	}

	print "Сделано";

	exit();

}
if ( $action == "edit_do" ) {

	$id = $_REQUEST[ 'id' ];

	$data[ 'title' ]    = $_REQUEST[ 'title' ];
	$data[ 'event' ]    = $_REQUEST[ 'event' ];
	$data[ 'url' ]      = $_REQUEST[ 'url' ];
	$data[ 'identity' ] = $identity;

	if ( $id > 0 ) {

		$db -> query( "UPDATE {$sqlname}webhook SET ?u WHERE id = '$id'", $data );

		print '{"result":"Сделано","error":""}';

	}
	else {

		$db -> query( "INSERT INTO {$sqlname}webhook SET ?u", $data );

		print '{"result":"Сделано","error":""}';

	}

	exit();

}
if ( $action == "edit" ) {

	$id = $_REQUEST[ 'id' ];

	$hook = $db -> getRow( "SELECT * FROM {$sqlname}webhook where id='".$id."' and identity = '$identity'" );

	?>
	<div class="zagolovok">Изменить / Добавить Webhook</div>

	<FORM action="/content/admin/<?php echo $thisfile; ?>" method="POST" name="form" id="form">
		<input name="action" id="action" type="hidden" value="edit_do"/>
		<input name="id" type="hidden" value="<?= $id ?>" id="<?= $id ?>"/>

		<div class="row">

			<div class="column12 grid-3 fs-12 pt10 right-text gray2">Название:</div>
			<div class="column12 grid-9 fpole relativ">
				<input type="text" name="title" id="title" value="<?= $hook[ 'title' ] ?>" class="wp97">
			</div>

			<div class="column12 grid-3 fs-12 pt10 right-text gray2">Событие:</div>
			<div class="column12 grid-9">
				<select name="event" id="event" class="wp97">
					<option value="">--выбор--</option>
					<?php
					foreach ( $events as $event => $title ) {

						$s = ( $hook[ 'event' ] == $event ) ? "selected" : "";

						print '<option value="'.$event.'" '.$s.' data-title="'.$title.'">'.$event.': '.$title.'</option>';

					}
					?>
				</select>
			</div>

			<div class="column12 grid-3 fs-12 pt10 right-text gray2">Адрес URL:</div>
			<div class="column12 grid-9">
				<textarea name="url" id="url" class="wp97" rows="3"><?= $hook[ 'url' ] ?></textarea>
				<div class="fs-09 gray2">Используйте тэг {HOME} для вызова скриптов, находящихся в пределах CRM</div>
			</div>

		</div>

	</FORM>

	<hr>

	<div class="button--pane text-right">

		<A href="javascript:void(0)" onclick="$('#form').trigger('submit')" class="button">Сохранить</A>&nbsp;
		<A href="javascript:void(0)" onclick="DClose()" class="button">Отмена</A>

	</div>

	<script>

		$('#dialog').css({'width': '600px'});

		$('#form').ajaxForm({

			dataType: 'json',
			beforeSubmit: function () {

				var $out = $('#message');
				var em = checkRequired();

				if (!em)
					return false;

				$out.fadeTo(10, 1).empty();

				$('#dialog').css('display', 'none');
				$('#dialog_container').css('display', 'none');
				$out.css('display', 'block').append('<div id=loader><img src=/assets/images/loader.gif> Загрузка данных. Пожалуйста подождите...</div>');

				return true;

			},
			success: function (data) {

				$('#contentdiv').load('/content/admin/webhook.php');
				$('#message').fadeTo(1, 1).css('display', 'block').html(data.result);

				setTimeout(function () {
					$('#message').fadeTo(1000, 0);
				}, 20000);
				DClose();

			}

		});

		$('#event').bind('change', function () {

			if ($('#title').val() != null) $('#title').val($(':selected', this).data('title'));

		});

	</script>
	<?php

	exit();

}

if ( $action == '' ) {

	$whuk = $db -> getIndCol( "id", "SELECT title, id FROM {$sqlname}webhook WHERE identity = '$identity' GROUP BY title" );

	$wlist  = [];
	$result = $db -> getAll( "SELECT * FROM {$sqlname}webhook WHERE identity = '$identity' ORDER BY title" );
	foreach ( $result as $data ) {

		$wlist[ $data[ 'title' ] ][] = [
			"id"    => $data[ 'id' ],
			"event" => $data[ 'event' ],
			"url"   => $data[ 'url' ],
		];

	}

	$pliginList       = json_decode( file_get_contents( $rootpath."/plugins/map.json" ), true );
	$pliginListCastom = ( file_exists( $rootpath."/plugins/map.castom.json" ) ) ? json_decode( file_get_contents( $rootpath."/plugins/map.castom.json" ), true ) : [];

	$pliginList = array_merge( $pliginList, $pliginListCastom );

	?>
	<h2>&nbsp;Раздел: "WebHook"</h2>

	<div class="infodiv mt20">

		<p>В этом разделе вы можете настроить отправку данных во внешние системы или в указанные скрипты при наступлении конкретных событий в CRM</p>
		<div class="infodiv bgwhite">

			<div class="flex-container">

				<div class="flex-string wp50">

					Фильтр по Названию:&nbsp;
					<span class="select">
					<select id="whname" class="w250">
						<option data-id="0">Все</option>
						<?php
						foreach ( $whuk as $id => $name ) {

							print '<option data-id="'.$name.'">'.$name.'</option>';

						}
						?>
					</select>
					</span>

				</div>
				<div class="flex-string wp50">

					Фильтр по Событию:&nbsp;
					<span class="select">
					<select id="whevent" class="w250">
						<option data-id="0">Все</option>
						<?php
						foreach ( $events as $event => $name ) {

							print '<option data-id="'.$event.'">'.$event.'</option>';

						}
						?>
					</select>
					</span>

				</div>

			</div>



		</div>

	</div>

	<form id="list">

		<TABLE id="catlist" class="top">
			<thead class="hidden-iphone sticked--top">
			<TR class="th40">
				<th class="w50 text-center"></th>
				<th class="w250"><b>Событие</b></th>
				<th><b>URL</b></th>
				<th class="w50 text-center"></th>
			</TR>
			</thead>
			<tbody>
			<?php
			foreach ( $wlist as $name => $items ) {

				$plg = $pliginList[ $name ][ 'name' ];

				print '
				<tr class="th50 graybg-lite">
					<td class="text-center hand" data-action="CheckChild" data-id="'.$name.'" title="Выделить все из блока">
						<i class="icon-th"></i>
					</td>
					<td colspan="3">
						<div class="fs-12 Bold gray2">
						'.( $name != '' ? ( $plg != '' ? '<span class="blue">'.$plg.'</span> ['.$name.']' : $name ) : 'Без имени' ).'
						</div>
					</td>
				</tr>
				';

				foreach ( $items as $k => $item ) {

					print '
					<TR class="ha th45" data-name="'.$name.'" data-event="'.$item[ 'event' ].'">
						<TD class="text-center">
							<label class="block p5">
								<input type="checkbox" onclick="chbCheck()" class="mm" name="multi[]" id="multi[]" value="'.$item[ 'id' ].'">
							</label>
						</TD>
						<TD>
							<A href="javascript:void(0)" onclick="doLoad(\'/content/admin/'.$thisfile.'?id='.$item[ 'id' ].'&action=edit\')" class="gray" title="Редактировать">
							<span class="block flh-30">
								<span class="pull-aright">
									<i class="icon-pencil"></i>
								</span>
								<div class="black fs-12 Bold">'.$item[ 'event' ].'</div>
								<div class="fs-09 gray2 inline">'.$name.'</div>
							</span>
							</A>
						</TD>
						<TD>'.$item[ 'url' ].'</TD>
						<TD class="text-center">
							<A href="javascript:void(0)" onclick="delHook(\''.$item[ 'id' ].'\')"><i class="icon-cancel-circled red"></i></A>
						</TD>
					</TR>
					';

				}

			}
			?>
			</tbody>
		</TABLE>

	</form>

	<div class="infodiv mt20">

		<p>
			Webhook входят в состав SalesMan RestAPI. Документация доступна
			<a href="<?= $productInfo[ 'site' ] ?>/api2/" title="SalesMan RestAPI. Документация" target="_blank" class="blue">на сайте</a>
		</p>
		<p>Для обращения к скриптам, расположенным в пределах CRM используйте тэг {HOME}. Например: {HOME}/developer/my/event.php</p>

	</div>

	<div class="space-100"></div>

	<div class="button--group1 box--child" style="position: fixed; bottom: 40px; left: 380px; z-index: 100;">

		<a href="javascript:void(0)" onclick="doLoad('/content/admin/<?php echo $thisfile; ?>?action=edit');" class="button bluebtn box-shadow" title="Добавить"><i class="icon-plus-circled"></i>Добавить</a>
		<a href="javascript:void(0)" onclick="preDel()" class="button redbtn box-shadow hidden amultidel" title="Удалить"><i class="icon-minus-circled"></i>Удалить выбранное</a>
		<a href="javascript:void(0)" onclick="clearCheck()" class="button greenbtn box-shadow hidden amultidel" title="Снять выделение"><i class="icon-th"></i>Снять выделение</a>

	</div>

	<script>

		$('td[data-action="CheckChild"]').on('click', function () {

			var name = $(this).data('id');

			if (!$(this).hasClass('green')) {

				$(this).addClass('green');
				$('tr[data-name="' + name + '"]').find('input:checkbox').prop('checked', true);

			}
			else {

				$(this).removeClass('green');
				$('tr[data-name="' + name + '"]').find('input:checkbox').prop('checked', false);

			}

			chbCheck();

		});

		$('#whname').on('change', function () {

			var id = $('option:selected', this).data('id');

			if (id !== 0) {

				$('#catlist').find('tbody tr').not('[data-name="' + id + '"]').addClass('hidden');
				$('#catlist').find('tbody tr[data-name="' + id + '"]').removeClass('hidden');

			}
			else
				$('#catlist').find('tbody tr').removeClass('hidden');

			clearCheck();

		});
		$('#whevent').on('change', function () {

			var id = $('option:selected', this).data('id');

			if (id !== 0) {

				$('#catlist').find('tbody tr').not('[data-event="' + id + '"]').addClass('hidden');
				$('#catlist').find('tbody tr[data-event="' + id + '"]').removeClass('hidden');

			}
			else
				$('#catlist').find('tbody tr').removeClass('hidden');

			clearCheck();

		});

		function delHook(id) {

			$('#message').fadeTo(1, 1).css('display', 'block').append('<div id="loader"><img src="/assets/images/loader.gif"> Выполняю...</div>');

			$.get("/content/admin/<?php echo $thisfile; ?>?action=delete&id=" + id, function (data) {

				$('#contentdiv').load('/content/admin/<?php echo $thisfile; ?>');

				$('#message').fadeTo(1, 1).css('display', 'block').html('Результат: ' + data);
				setTimeout(function () {
					$('#message').fadeTo(1000, 0);
				}, 20000);

			});

		}

		function chbCheckChild(name) {

			$('tr[data-name="' + name + '"]').find('input:checkbox').prop('checked', true);

		}

		function chbCheck() {

			var col = $('#catlist input:checkbox:checked').length;

			if (col > 0) $('.amultidel').removeClass('hidden');
			else $('.amultidel').addClass('hidden');

		}

		function clearCheck() {

			$('#catlist input:checkbox:checked').prop('checked', false);
			$('.amultidel').addClass('hidden');

			$('td[data-action="CheckChild"]').removeClass('green');

		}

		function preDel() {

			Swal.fire({
					title: 'Вы уверены?',
					text: "Записи будут удалены безвозвратно",
					type: 'question',
					showCancelButton: true,
					confirmButtonColor: '#3085D6',
					cancelButtonColor: '#D33',
					confirmButtonText: 'Да, выполнить',
					cancelButtonText: 'Отменить',
					confirmButtonClass: 'greenbtn',
					cancelButtonClass: 'redbtn'
				},
				function () {
					multidel();
				}
			).then((result) => {

				if (result.value) {

					multidel();

				}

			});

		}

		function multidel() {

			var str = $('#list').serialize();

			$.get('/content/admin/<?php echo $thisfile; ?>?action=delete&' + str, function (data) {

				$('#contentdiv').load('/content/admin/<?php echo $thisfile; ?>');

				$('#message').fadeTo(1, 1).css('display', 'block').html('Результат: ' + data);
				setTimeout(function () {
					$('#message').fadeTo(1000, 0);
				}, 20000);

			});

		}

	</script>
	<?php
}