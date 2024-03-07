<?php
/* ============================ */
/*         SalesMan CRM         */
/* ============================ */
/* (C) 2018 Vladislav Andreev   */
/*       SalesMan Project       */
/*        www.isaler.ru         */
/*        ver. 2018.x           */
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

$action = $_REQUEST['action'];

//создадим таблицу, если надо
$da = $db -> getOne( "SELECT COUNT(*) as count FROM INFORMATION_SCHEMA.STATISTICS WHERE table_schema = '$database' and TABLE_NAME = '{$sqlname}doubles'" );
if ( $da == 0 ) {

	$db -> query( "CREATE TABLE {$sqlname}doubles (
			`id` INT(20) NOT NULL AUTO_INCREMENT,
			`datum` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'дата добавления',
			`tip` TEXT NULL DEFAULT NULL COMMENT 'типы дубля',
			`idmain` INT(10) NULL DEFAULT NULL COMMENT 'id проверяемой записи',
			`list` VARCHAR(500) NULL COMMENT 'json-массив найденных дублей',
			`ids` VARCHAR(100) NULL DEFAULT NULL COMMENT 'список всех id, упомятутых в list',
			`status` VARCHAR(3) NULL DEFAULT 'no' COMMENT 'статус',
			`datumdo` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'дата обработки',
			`des` TEXT NULL COMMENT 'комментарий',
			`iduser` VARCHAR(10) NULL DEFAULT NULL COMMENT 'сотрудник, который выполнил действие',
			`identity` INT(20) NOT NULL DEFAULT '1',
			PRIMARY KEY (`id`),
			INDEX `filter` (`id`, `tip`(10), `idmain`, `ids`)
		)
		COMMENT='Лог поиска дублей'
		ENGINE=InnoDB
	" );

}

if ( $action == "save" ) {

	$id = (int)$_REQUEST['id'];

	unset($_REQUEST['id'], $_REQUEST['action']);

	$params = $_REQUEST;

	if ( $id > 0 ) {
		$db -> query("UPDATE {$sqlname}customsettings SET ?u WHERE tip = 'doubles' and identity = '$identity'", [
			"datum"  => current_datumtime(),
			"params" => json_encode($params)
		]);
	}
	else {
		$db -> query("INSERT INTO {$sqlname}customsettings SET ?u", [
			"tip"      => "doubles",
			"params"   => json_encode($params),
			"identity" => $identity
		]);
	}

	unlink( $rootpath."/cash/".$fpath."settings.checkdoubles.json" );

	print "Готово";

	exit();

}
if ( $action == '' ) {

	$set         = $db -> getRow( "SELECT id, params FROM {$sqlname}customsettings WHERE tip = 'doubles' AND identity = '$identity'" );
	$dblID       = $set['id'];
	$dblSettings = json_decode( (string)$set['params'], true );

	?>
	<h2 class="mb20">&nbsp;Раздел: "Поиск дублей"</h2>

	<div class="infodiv">

		<p>Функция поиска дублей позволяет находить дубли записей Клиентов и Контактов в ручном или автоматическом режиме.</p>
		<p>Поиск осуществляется по следующим параметрам:</p>
		<ul>
			<li>Телефон, Факс - Клиент, Контакт</li>
			<li>Мобильный - Контакт</li>
			<li>ФИО - Контакт</li>
			<li>Email - Клиент, Контакт</li>
			<li>Реквизиты (ИНН + КПП) - Клиент. Юр.лицо</li>
			<li>Реквизиты (Паспорт Серия + Номер) - Клиент. Физ.лицо</li>
		</ul>

		<b class="red">Важно:</b> Функция производит параллельную проверку, т.е. проверяет дубли Клиента в записях Клиентов, дубли контактов в записях Контактов
	</div>

	<FORM action="content/admin/<?php echo $thisfile; ?>" method="post" enctype="multipart/form-data" name="form" id="form">
		<INPUT type="hidden" name="action" id="action" value="save">
		<INPUT type="hidden" name="id" id="id" value="<?= $dblID ?>">

		<div class="flex-container mt20 mb20 pl10">

			<div class="flex-string">
				<b class="blue miditxt">Настройки функции:</b>
			</div>

		</div>

		<div class="flex-container box--child mt20">

			<div class="flex-string wp15 right-text fs-12 gray2"></div>
			<div class="flex-string wp85 pl10 Bold red">
				<label><input id="active" name="active" type="checkbox" value="yes" <?php if ( $dblSettings['active'] == 'yes' )
						print 'checked' ?> />&nbsp;Активировать поиск дублей&nbsp;</label>
			</div>

		</div>

		<div class="flex-container box--child mt10">

			<div class="flex-string wp15 right-text fs-12 gray2"></div>
			<div class="flex-string wp85 pl10">

				<label class="inline">
					<input id="autoSearch" name="autoSearch" type="checkbox" value="yes" <?php if ( $dblSettings['autoSearch'] == 'yes' )
						print 'checked' ?> />&nbsp;Включить автопоиск&nbsp;
				</label>
				<div class="tagsmenuToggler hand mr15 inline relativ" data-id="fhelper">
					<span class="fs-11 blue"><i class="icon-help-circled"></i></span>
					<div class="tagsmenu right hidden" id="fhelper" style="right:-20px; top: 100%">
						<div class="blok p10 w250">
							Каждый раз после Добавления/Редактирования записи Клиента/Контакта будет проводиться проверка на дубли в фоновом режиме
						</div>
					</div>
				</div>

			</div>

		</div>

		<div class="flex-container mt20 mb20 pl10">

			<div class="flex-string">
				<b class="blue miditxt">Поиск по данным:</b>
			</div>

		</div>

		<div class="flex-container box--child mt20">

			<div class="flex-string wp15 right-text fs-12 gray2"></div>
			<div class="flex-string wp85 pl10">
				<label class="inline"><input id="field[]" name="field[]" type="checkbox" value="phone" <?php if ( in_array( 'phone', $dblSettings['field'] ) )
						print 'checked' ?> />&nbsp;<b>По Телефону, Факсу</b> [ клиенты ]&nbsp;</label>
			</div>

		</div>

		<div class="flex-container box--child mt10">

			<div class="flex-string wp15 right-text fs-12 gray2"></div>
			<div class="flex-string wp85 pl10">
				<label class="inline"><input id="field[]" name="field[]" type="checkbox" value="mail_url" <?php if ( in_array( 'mail_url', $dblSettings['field'] ) )
						print 'checked' ?> />&nbsp;<b>По Email</b> [ клиенты ]&nbsp;</label>
			</div>

		</div>

		<div class="flex-container box--child mt10">

			<div class="flex-string wp15 right-text fs-12 gray2"></div>
			<div class="flex-string wp85 pl10">
				<label class="inline"><input id="field[]" name="field[]" type="checkbox" value="recv" <?php if ( in_array( 'recv', $dblSettings['field'] ) )
						print 'checked' ?> />&nbsp;<b>По ИНН + КПП</b> [ клиенты ]&nbsp;</label>
			</div>

		</div>

		<div class="flex-container box--child mt20">

			<div class="flex-string wp15 right-text fs-12 gray2"></div>
			<div class="flex-string wp85 pl10">
				<label class="inline"><input id="field[]" name="field[]" type="checkbox" value="person" <?php if ( in_array( 'person', $dblSettings['field'] ) )
						print 'checked' ?> />&nbsp;<b>По ФИО</b> [ контакты ]&nbsp;</label>
			</div>

		</div>

		<div class="flex-container box--child mt10">

			<div class="flex-string wp15 right-text fs-12 gray2"></div>
			<div class="flex-string wp85 pl10">
				<label class="inline"><input id="field[]" name="field[]" type="checkbox" value="tel" <?php if ( in_array( 'tel', $dblSettings['field'] ) )
						print 'checked' ?> />&nbsp;<b>По Телефону, Мобильному</b> [ контакты ]&nbsp;</label>
			</div>

		</div>

		<div class="flex-container box--child mt10">

			<div class="flex-string wp15 right-text fs-12 gray2"></div>
			<div class="flex-string wp85 pl10">
				<label class="inline"><input id="field[]" name="field[]" type="checkbox" value="mail" <?php if ( in_array( 'mail', $dblSettings['field'] ) )
						print 'checked' ?> />&nbsp;<b>По Email</b> [ контакты ]&nbsp;</label>
			</div>

		</div>

		<div class="flex-container mt20 mb20 pl10">

			<div class="flex-string">
				<b class="blue miditxt">Доступ к функции:</b>
			</div>

		</div>

		<div class="flex-container box--child">

			<div class="flex-string wp15 right-text fs-12 gray2 pt7">Координаторы:</div>
			<div class="flex-string wp85 pl10">

				<div class="ydropDown w300 m0" data-id="Coordinator">
					<span class="hidden">Координаторы</span>
					<span class="ydropCount"><?= count( (array)$dblSettings['Coordinator'] ) ?> выбрано</span><i class="icon-angle-down pull-aright"></i>
					<div class="yselectBox top Coordinator" data-id="Coordinator">
						<?php
						$res = $db -> getAll( "SELECT * FROM {$sqlname}user WHERE secrty = 'yes' and identity = '$identity' ORDER BY title" );
						foreach ( $res as $data ) {

							$s = (in_array( $data['iduser'], (array)$dblSettings['Coordinator'] )) ? 'checked' : '';
							?>
							<div class="ydropString ellipsis">
								<label>
									<input class="taskss" name="Coordinator[]" type="checkbox" id="Coordinator[]" value="<?= $data['iduser'] ?>" <?= $s ?>>&nbsp;<?= $data['title'] ?>
								</label>
							</div>
						<?php } ?>
					</div>
				</div>
				<div class="fs-09 em gray2">Сотрудники, которые смогут обрабатывать дубли</div>

			</div>

		</div>

		<div class="flex-container box--child mt10">

			<div class="flex-string wp15 right-text fs-12 gray2 pt7">Просмотр:</div>
			<div class="flex-string wp85 pl10">

				<div class="ydropDown w300 m0" data-id="Operator">
					<span class="hidden">Просмотр</span>
					<span class="ydropCount"><?= count( (array)$dblSettings['Operator'] ) ?> выбрано</span><i class="icon-angle-down pull-aright"></i>
					<div class="yselectBox top Operator" data-id="Operator" style="">
						<?php
						$res = $db -> getAll( "SELECT * FROM {$sqlname}user WHERE secrty = 'yes' and identity = '$identity' ORDER BY title" );
						foreach ( $res as $data ) {

							$s = (in_array( $data['iduser'], (array)$dblSettings['Operator'] )) ? 'checked' : '';
							?>
							<div class="ydropString ellipsis">
								<label>
									<input class="taskss" name="Operator[]" type="checkbox" id="Operator[]" value="<?= $data['iduser'] ?>" <?= $s ?>>&nbsp;<?= $data['title'] ?>
								</label>
							</div>
						<?php } ?>
					</div>
				</div>
				<div class="fs-09 em gray2">Сотрудники, которые смогут просматривать дубли</div>

			</div>

		</div>

		<div class="flex-container mt20 mb20 pl10">

			<div class="flex-string">
				<b class="blue miditxt">Уведомления:</b>
			</div>

		</div>

		<div class="flex-container box--child">

			<div class="flex-string wp15 right-text fs-12 gray2"></div>
			<div class="flex-string wp85 pl10">
				<label class="inline">
					<input id="CoordinatorNotify" name="CoordinatorNotify" type="checkbox" value="yes" <?php if ( $dblSettings['CoordinatorNotify'] == 'yes' )
						print 'checked' ?> />&nbsp;Уведомлять Координатора о найденных дублях&nbsp;
				</label>

				<div class="tagsmenuToggler hand mr15 inline relativ" data-id="fhelper">
					<span class="fs-11 blue"><i class="icon-help-circled"></i></span>
					<div class="tagsmenu top hidden" id="fhelper" style="right:-20px;">
						<div class="blok p10 w350">
							Уведомления отправляются по Email.<br>
							- При отключенной функции уведомления отправляться не будут<br>
							- При массовом поиске дублей уведомления не отправляются
						</div>
					</div>
				</div>
			</div>

		</div>

		<div class="flex-container box--child mt10">

			<div class="flex-string wp15 right-text fs-12 gray2"></div>
			<div class="flex-string wp85 pl10">
				<label class="inline">
					<input id="UserNotify" name="UserNotify" type="checkbox" value="yes" <?php if ( $dblSettings['UserNotify'] == 'yes' )
						print 'checked' ?> />&nbsp;Уведомлять Сотрудников&nbsp;
				</label>

				<div class="tagsmenuToggler hand mr15 inline relativ" data-id="fhelper">
					<span class="fs-11 blue"><i class="icon-help-circled"></i></span>
					<div class="tagsmenu top hidden" id="fhelper" style="right:-20px;">
						<div class="blok p10 w250">
							Сотрудники, записи которых будут слиты, будут оповещены об этом по Email. В уведомлении также будет указан новый ответственный, если он назначен.
						</div>
					</div>
				</div>
			</div>

		</div>

		<hr class="mt20">

		<div class="button--group1 box--child" style="position: fixed; bottom: 40px; left: 380px; z-index: 100;">

			<a href="javascript:void(0)" class="button bluebtn box-shadow" onclick="$('#form').trigger('submit')">Сохранить</a>

		</DIV>

	</FORM>

	<div class="pagerefresh refresh--icon admn red" onclick="$('#form').trigger('submit')" title="Сохранить"><i class="icon-ok"></i></div>
	<div class="pagerefresh refresh--icon admn orange" onclick="openlink('https://salesman.pro/docs/137')" title="Документация"><i class="icon-help"></i></div>

	<div class="space-100"></div>

	<script>

		$('#dialog').css('width', '802px');

		$(function () {

			var $out = $('#message');

			//$(".multiselect").multiselect({sortable: true, searchable: true});
			//$(".connected-list").css('height', "150px");

			$('#form').ajaxForm({
				beforeSubmit: function () {

					var $out = $('#message');
					var em = checkRequired();

					if (em === false) return false;

					$('#dialog').css('display', 'none');
					$('#dialog_container').css('display', 'none');

					$out.css('display', 'block').append('<div id=loader><img src=/assets/images/loader.gif> Загрузка данных. Пожалуйста подождите...</div>');
					return true;

				},
				success: function (data) {

					$('#dialog').css('display', 'none').css('width', '500px');
					$('#dialog_container').css('display', 'none');

					//$("#contentdiv").load('content/admin/<?php echo $thisfile; ?>');
					razdel(hash);
					$('#resultdiv').empty();

					$out.fadeTo(1, 1).css('display', 'block').html(data);
					setTimeout(function () {
						$out.fadeTo(1000, 0);
					}, 20000);

				}
			});

			$('#dialog').center();

		});

	</script>

	<?php
}