<?php
/* ============================ */
/*         SalesMan CRM         */
/* ============================ */
/* (C) 2018 Vladislav Andreev   */
/*       SalesMan Project       */
/*        www.isaler.ru         */
/*        ver. 2019.x           */

/* ============================ */

use Salesman\Metrics;

error_reporting( E_ERROR );
header( "Pragma: no-cache" );

$rootpath = dirname( __DIR__, 2 );

include $rootpath."/inc/config.php";
include $rootpath."/inc/dbconnector.php";
include $rootpath."/inc/auth.php";
include $rootpath."/inc/func.php";
include $rootpath."/inc/settings.php";
include $rootpath."/inc/language/".$language.".php";

$thisfile = basename( __FILE__ );

function prenum($num) {
	return str_replace( ".", ",", $num );
}

$year   = (int)$_REQUEST['year'];
$iduser = (int)$_REQUEST['iduser'];
$action = $_REQUEST['action'];

if ( $year == "" ) {
	$year = date( 'Y' );
}

/**
 * Редактирование плана по сотруднику
 */
if ( $action === 'edit.plan' ) {
	?>
	<DIV class="zagolovok">План сотрудника <B><?= current_user( $iduser ) ?></B> на <?= $year ?> год</DIV>
	<FORM method="post" action="/modules/metrics/core.metrics.php" enctype="multipart/form-data" name="planform" id="planform">
		<INPUT name="action" id="action" type="hidden" value="edit.plan">
		<INPUT name="iduser[]" id="iduser[]" type="hidden" value="<?= $iduser ?>">
		<INPUT name="year" id="year" type="hidden" value="<?= $year ?>">

		<div id="formtabs" class="relativ" style="overflow-y: auto; max-height: 70vh">

			<div class="flex-container box--child p5 pt10 pb10 fs12 graybg Bold tableHead sticked--top no-border">

				<div class="flex-string wp15 text-right">Период&nbsp;&nbsp;</div>
				<div class="flex-string wp40 text-center">Оборот</div>
				<div class="flex-string wp40 text-center">Маржа</div>
				<div class="flex-string wp5">&nbsp;</div>

			</div>

			<?php
			//План по продажам
			$m = 0;
			while ($m++ < 12) {

				$r = $db -> getRow( "SELECT SUM(kol_plan) as summa, SUM(marga) as marga FROM ".$sqlname."plan WHERE iduser = '$iduser' AND year = '$year' AND mon = '$m' AND identity = '$identity'" );

				$s = ($m == 1) ? '&nbsp;<i class="icon-down-big blue fs-09 dcreate hand" title="Заполнить всё"></i>' : '';


				print '
				<div class="flex-container box--child p5 border-bottom1">
			
					<div class="flex-string wp15 text-right Bold fs-12 pt7">'.ru_mon( $m ).':&nbsp;&nbsp;</div>
					<div class="flex-string wp40">
						<INPUT name="plan['.$m.'][summa]" type="text" id="plan['.$m.'][summa]" value="'.($r['summa'] + 0).'" class="wp90 isumma">
					</div>
					<div class="flex-string wp40">
						<INPUT name="plan['.$m.'][marga]" type="text" id="plan['.$m.'][marga]" value="'.($r['marga'] + 0).'" class="wp90 imarga">
					</div>
					<div class="flex-string wp5">'.$s.'</div>
			
				</div>
				';

			}
			?>

		</div>

		<hr>

		<div class="flex-container box--child p5 pt10 pb10 fs12 graybg fxd mb5">

			<div class="flex-string wp30 text-right Bold fs-12 pt7">также для:&nbsp;&nbsp;</div>
			<div class="flex-string wp70">

				<div class="ydropDown w400 m0" data-id="Coordinator">
					<span class="hidden1"></span>
					<span class="ydropCount">0 выбрано</span><i class="icon-angle-down pull-aright"></i>
					<div class="yselectBox top Coordinator" data-id="Coordinator">
						<?php
						$users = get_people( $iduser1, 'yes', 'yes' );
						$sort  = (count( $users ) > 0 && $isadmin != 'on') ? " and iduser IN (".implode( ",", $users ).")" : "";
						$res   = $db -> getAll( "SELECT * FROM ".$sqlname."user WHERE secrty = 'yes' $sort and acs_plan = 'on' and iduser != '$iduser' and identity = '$identity' ORDER BY title" );
						foreach ( $res as $data ) {

							print '
								<div class="ydropString ellipsis">
									<label>
										<input class="taskss" name="iduser[]" type="checkbox" id="iduser[]" value="'.$data['iduser'].'">&nbsp;'.$data['title'].'&nbsp;
										[ <span class="em gray2">'.$data['tip'].'</span> ]
									</label>
								</div>
							';

						}
						?>
					</div>
				</div>
				<div class="fs-09 em gray2">Установить такой же план выбранным сотрудникам</div>

			</div>

		</div>

		<hr>

		<DIV class="button--pane text-right">
			<A href="javascript:void(0)" onclick="$('#planform').trigger('submit')" class="button">Сохранить</A>&nbsp;
			<A href="javascript:void(0)" onclick="DClose();" class="button">Отмена</A>
		</DIV>
	</FORM>
	<?php
}

/**
 * Редактирование показателя KPI для сотрудника
 */
if ( $action === 'edit.kpi' ) {

	$id = (int)$_REQUEST['id'];
	//$iduser = (int)$_REQUEST[ 'iduser' ];
	$kpi = [];

	$list = Metrics ::getKPIs();

	if ( $id > 0 ) {

		$kpi = Metrics ::getUserKPI( ["id" => $id] );

	}
	else {

		$kpi['year']  = $year;
		$kpi['period'] = 'month';
		$kpi['value'] = 0;

	}

	?>
	<DIV class="zagolovok">Персональный показатель</DIV>
	<FORM method="post" action="/modules/metrics/core.metrics.php" enctype="multipart/form-data" name="planform" id="planform">
		<INPUT name="action" id="action" type="hidden" value="edit.kpi">
		<INPUT name="id" id="id" type="hidden" value="<?= $id ?>">
		<INPUT name="iduser" id="iduser" type="hidden" value="<?= $iduser ?>">

		<div id="formtabs" class="relativ" style="overflow-y: auto; max-height: 70vh">

			<div class="flex-container mb10 mt20 box--child hidden">

				<div class="flex-string wp20 gray2 fs-12 pt7 right-text">Показатель:</div>
				<div class="flex-string wp80 pl10">
					<select name="kpi1" id="kpi1" class="required wp97">
						<?php
						foreach ( $list as $value ) {

							$s = ((int)$value['id'] == (int)$kpi['kpi']) ? "selected" : "";
							print '<OPTION '.$s.' value="'.$value['id'].'">'.$value['title'].' ['.$value['tipTitle'].']</OPTION>';

						}
						?>
					</select>
				</div>

			</div>

			<div class="flex-container mb10 mt20 box--child">

				<div class="flex-string wp20 gray2 fs-12 pt10 right-text">Показатель:</div>
				<div class="flex-string wp80 pl10">

					<div class="ydropDown like-input wp97 req">

						<span title="Показатель"><i class="icon-article-alt black"></i></span>
						<span class="ydropText"></span>
						<i class="icon-angle-down pull-aright arrow"></i>

						<div class="yselectBox" style="max-height: 250px; width:100%">
							<?php
							foreach ( $list as $value ) {

								print '
								<div class="ydropString yRadio">
									<label class="wp95">
										<input type="radio" name="kpi" id="kpi" data-title="'.$value['title'].'" value="'.$value['id'].'" class="hidden1" '.($value['id'] == $kpi['kpi'] ? 'checked' : '').'>
										'.$value['title'].'
										<div class="pull-aright blue">'.$value['tipTitle'].'</div>
									</label>
								</div>
								';

							}
							?>

						</div>

					</div>

				</div>

			</div>

			<div class="flex-container mb10 mt20 box--child">

				<div class="flex-string wp20 gray2 fs-12 pt7 right-text">Год:</div>
				<div class="flex-string wp80 pl10">
					<input name="year" type="text" id="year" class="wp97 required" value="<?= $kpi['year'] ?>" placeholder="Укажите год">
				</div>

			</div>

			<div class="flex-container mb10 mt20 box--child">

				<div class="flex-string wp20 gray2 fs-12 pt7 right-text">Период:</div>
				<div class="flex-string wp80 pl10 values">
					<select name="period" id="period" class="required wp97">
						<OPTION value="day" <?php if ( $kpi['period'] == 'day' )
							print 'selected' ?>>в День
						</OPTION>
						<OPTION value="week" <?php if ( $kpi['period'] == 'week' )
							print 'selected' ?>>в Неделю
						</OPTION>
						<OPTION value="month" <?php if ( $kpi['period'] == 'month' )
							print 'selected' ?>>в Месяц
						</OPTION>
						<OPTION value="quartal" <?php if ( $kpi['period'] == 'quartal' )
							print 'selected' ?>>в Квартал
						</OPTION>
						<OPTION value="year" <?php if ( $kpi['period'] == 'year' )
							print 'selected' ?>>в Год
						</OPTION>
					</select>
					<div class="smalltxt mt2 gray2 em">Укажите период достижения показателя, для расчетов</div>
				</div>

			</div>

			<div class="flex-container mb10 mt20 box--child">

				<div class="flex-string wp20 gray2 fs-12 pt7 right-text">Целевое значение:</div>
				<div class="flex-string wp80 pl10 values">
					<input name="val" type="text" id="val" class="wp97 required" value="<?= $kpi['value'] ?>" placeholder="Укажите значение">
					<div class="smalltxt mt2 gray2 em">Укажите целевое значение показателя, для расчетов</div>
				</div>

			</div>

			<div class="flex-container mb10 mt20 box--child">

				<div class="flex-string wp20 gray2 fs-12 pt7 right-text"></div>
				<div class="flex-string wp80 pl10 values">

					<div class="checkbox mt5">
						<label>
							<input name="isPersonal" type="checkbox" id="isPersonal" value="1" <?php if ( $kpi['isPersonal'] == '1' )
								print "checked"; ?>>
							<span class="custom-checkbox"><i class="icon-ok"></i></span>&nbsp;Персональный расчет&nbsp;<i class="icon-info-circled blue" title="Включите, чтобы при расчете учитывать только записи сотрудника. Без учета данных подчиненных"></i>
						</label>
					</div>

				</div>

			</div>

		</div>

		<hr>

		<div class="flex-container box--child p5 pt10 pb10 fs12 graybg fxd mb5">

			<div class="flex-string wp30 text-right Bold fs-12 pt7">также для:&nbsp;&nbsp;</div>
			<div class="flex-string wp70">

				<div class="ydropDown w400 m0" data-id="Coordinator">
					<span class="hidden1"></span>
					<span class="ydropCount">0 выбрано</span><i class="icon-angle-down pull-aright"></i>
					<div class="yselectBox top Coordinator" data-id="Coordinator">
						<?php
						$users = get_people( $iduser1, 'yes', 'yes' );
						$sort  = (!empty( $users ) && $isadmin != 'on') ? " and iduser IN (".implode( ",", $users ).")" : "";
						$res   = $db -> getAll( "SELECT * FROM ".$sqlname."user WHERE secrty = 'yes' $sort and acs_plan = 'on' and iduser != '$iduser' and identity = '$identity' ORDER BY title" );
						foreach ( $res as $data ) {

							print '
								<div class="ydropString ellipsis">
									<label>
										<input class="taskss" name="users[]" type="checkbox" id="users[]" value="'.$data['iduser'].'">&nbsp;'.$data['title'].'&nbsp;
										[ <span class="em gray2">'.$data['tip'].'</span> ]
									</label>
								</div>
							';

						}
						?>
					</div>
				</div>
				<div class="fs-09 em gray2">Установить такой же план выбранным сотрудникам</div>

			</div>

		</div>

		<hr>

		<div class="pull-aright button--pane">

			<A href="javascript:void(0)" onclick="$('#planform').trigger('submit')" class="button">Сохранить</A>&nbsp;
			<A href="javascript:void(0)" onclick="DClose();" class="button">Отмена</A>

		</DIV>
	</FORM>
	<?php

}

/**
 * Редактирование базового показателя KPI
 */
if ( $action === 'edit.kpiBase' ) {

	$id = (int)$_REQUEST['id'];

	$kpi = [];

	$list = Metrics ::MetricList();

	if ( $id > 0 ) {

		$kpi       = $db -> getRow( "SELECT * FROM ".$sqlname."kpibase WHERE id = '$id'" );
		$values    = (array)yexplode( ",", (string)$kpi['values'] );
		$subvalues = (array)yexplode( ",", (string)$kpi['subvalues'] );

		$items = Metrics ::getElements( $kpi['tip'] );

		if ( !in_array( $kpi['tip'], [
			"productCount",
			"productSumma"
		] ) ) {

			$subitems = Metrics ::MetricSubList( $kpi['tip'] );

		}
		else {

			$subitems = $db -> getIndCol( "n_id", "SELECT title, n_id FROM ".$sqlname."price WHERE n_id IN ($kpi[subvalues])" );

		}

		//print_r($subitems);

	}
	else {

		$id    = 0;
		$keys  = array_keys( $list );
		$items = Metrics ::getElements( $keys['0'] );

		$kpi['tip'] = $keys['0'];

	}

	?>
	<DIV class="zagolovok">Базовый показатель</DIV>
	<FORM method="post" action="/modules/metrics/core.metrics.php" enctype="multipart/form-data" name="planform" id="planform">
		<INPUT name="action" id="action" type="hidden" value="edit.kpiBase">
		<INPUT name="id" id="id" type="hidden" value="<?= $id ?>">

		<div id="formtabs" class="relativ" style="overflow-y: auto; max-height: 70vh">

			<div class="flex-container mb10 mt20 box--child">

				<div class="flex-string wp20 gray2 fs-12 pt7 right-text">Тип показателя:</div>
				<div class="flex-string wp80 pl10">
					<select name="tip" id="tip" class="required wp97">
						<?php
						foreach ( $list as $item => $value ) {

							$s = ($item == $kpi['tip']) ? "selected" : "";
							print '<OPTION '.$s.' value="'.$item.'">'.$value.'</OPTION>';

						}
						?>
					</select>
				</div>

			</div>

			<div class="flex-container mb10 mt20 box--child">

				<div class="flex-string wp20 gray2 fs-12 pt7 right-text">Название:</div>
				<div class="flex-string wp80 pl10">
					<input name="title" type="text" id="title" class="wp97 required" value="<?= $kpi['title'] ?>" placeholder="Например: Показатель А">
					<div class="smalltxt mt2 gray2 em">Назовите показатель для удобства</div>
				</div>

			</div>

			<div class="flex-container mb10 mt20 box--child">

				<div class="flex-string wp20 gray2 fs-12 pt7 right-text">Значения:</div>
				<div class="flex-string wp80 pl10 values">
					<div class="warning hidden vars">Варианты значений не поддерживаются</div>
					<select name="values[]" id="values[]" multiple class="wp97 required multiselect">
						<?php
						foreach ( $items as $id => $item ) {

							$s = (in_array( $id, (array)$values )) ? 'selected' : '';

							print '<option value="'.$id.'" '.$s.'>'.$item.'</option>';

						}
						?>
					</select>
					<div class="smalltxt mt2 gray2 em">Укажите варианты, для расчетов</div>
				</div>

			</div>

			<div class="flex-container mb10 mt20 box--child <?= (empty( $subitems ) ? 'hidden' : '') ?>" data-id="subvars">

				<div class="flex-string wp20 gray2 fs-12 pt7 right-text">Доп.значения:</div>
				<div class="flex-string wp80 pl10 values">
					<div class="prices <?= (!in_array( $kpi['tip'], [
						"productCount",
						"productSumma"
					] ) ? 'hidden' : '') ?>">
						<input type="text" id="price" class="wp97" placeholder="Начните вводить название продукта">
					</div>
					<div class="warning hidden subvars">Варианты значений не поддерживаются</div>
					<select name="subvalues[]" id="subvalues[]" multiple class="wp97 multiselect">
						<?php
						foreach ( $subitems as $id => $item ) {

							$s = (in_array( $id, (array)$subvalues )) ? 'selected' : '';

							print '<option value="'.$id.'" '.$s.'>'.$item.'</option>';

						}
						?>
					</select>
					<div class="smalltxt mt2 gray2 em">Укажите варианты</div>
				</div>

			</div>

		</div>

		<hr>

		<div class="pull-aright button--pane">

			<A href="javascript:void(0)" onclick="$('#planform').trigger('submit')" class="button">Сохранить</A>&nbsp;
			<A href="javascript:void(0)" onclick="DClose();" class="button">Отмена</A>

		</DIV>
	</FORM>
	<?php

}

/**
 * Импорт списка планов по продажам из Excel
 */
if ( $action === 'import.plan' ) {
	?>
	<div class="zagolovok">Импорт планов на <b class="red"><?= $_REQUEST['year'] ?></b> год</div>
	<FORM method="post" action="/content/core/core.plan.php" enctype="multipart/form-data" name="planform" id="planform">
		<input name="action" id="action" type="hidden" value="import.plan"/>
		<input name="year" id="year" type="hidden" value="<?= $year ?>"/>

		<div class="pad10"><input name="file" type="file" class="file wp100" id="file"></div>
		<div class="infodiv">

			<div class="blue Bold">Инструкция</div>
			<ul>
				<li>Перед загрузкой подготовьте данные</li>
				<li>Обязательно сохраните структуру файла, полученного экспортом (с сохранением всех колонок и шапки таблицы)</li>
				<li>Все показатели импортируютка "Как есть", без дополнительных расчетов</li>
				<li>Поддерживаются файлы с расширением CSV, XLS или XLSX</li>
			</ul>

			<hr>

			<div class="button--pane text-right">

				<a href="javascript:void(0)" onclick="Next()" class="button graybtn next">Импорт</a>&nbsp;
				<a href="javascript:void(0)" onclick="DClose()" class="button">Отмена</a>

			</div>
	</form>
	<?php
}

/**
 * Редактор сезонных коэффициентов
 */
if ( $action === 'edit.season' ) {

	$rate = (new Metrics()) -> getSeason( $year );
	?>
	<DIV class="zagolovok">Сезонные коэффициенты на <?= $year ?> год</DIV>
	<FORM method="post" action="/modules/metrics/core.metrics.php" enctype="multipart/form-data" name="planform" id="planform">
		<INPUT name="action" id="action" type="hidden" value="edit.season">
		<INPUT name="year" id="year" type="hidden" value="<?= $year ?>">

		<div id="formtabs" class="relativ" style="overflow-y: auto; max-height: 70vh">

			<div class="month-horizontal box--child">
				<?php
				//План по продажам
				$m = 0;
				while ($m++ < 12) {

					print '
				<div class="month-horizontal-block">
					<div class="Bold fs-09 gray mb2">'.ru_mon( $m ).'</div>
					<div class="">
						<INPUT type="number" id="season['.$m.']" name="season['.$m.']" value="'.($rate[ $m ] > 0 ? $rate[ $m ] : 1).'" min="0" step="0.1" class="wp100 isumma">
					</div>
				</div>
				';

				}
				?>
			</div>

			<div class="attention mt10 mb10">Коэффициенты не применяются к Квартальным и Годовым показателям</div>

		</div>

		<hr>

		<DIV class="button--pane text-right">
			<A href="javascript:void(0)" onclick="$('#planform').trigger('submit')" class="button">Сохранить</A>&nbsp;
			<A href="javascript:void(0)" onclick="DClose();" class="button">Отмена</A>
		</DIV>
	</FORM>
	<?php
}
?>
<script>

	var action = $('#action').val();
	var $cat = '';

	if (!isMobile) {

		var hh = $('#dialog_container').actual('height') * 0.9;
		var hh2 = hh - $('.zagolovok').actual('outerHeight') - $('.button--pane').actual('outerHeight') - 30;

		if ($(window).width() > 990) $('#formtabs').css({'height': 'unset', 'max-height': hh2 + "px"});
		else $('#formtabs').css({'height': 'unset', 'max-height': hh2 + "px"});

		$('#dialog').css('width', '800px');

		$(".multiselect").each(function () {

			$(this).multiselect({sortable: true, searchable: true});

		});
		$(".connected-list").css('height', "200px");

	}
	else {

		var h2 = $(window).height() - $('.zagolovok').actual('outerHeight') - $('.button--pane').actual('outerHeight') - 30;
		$('#formtabs').css({'max-height': h2 + 'px', 'height': h2 + 'px'});

		$(".multiselect").addClass('wp97 h0');

		if (isMobile) $('table').rtResponsiveTables();

	}

	$(function () {

		yDropSelectSetText();

		var $fixed = $('.fxd');
		var w = $fixed.width();
		var poz = $fixed.offset();

		$('.fixedHeader').css({"width": w + "px", "top": poz.top, "left": poz.left});

		$('#planform').ajaxForm({
			dataType: 'json',
			beforeSubmit: function () {

				var $out = $('#message');
				var em = checkRequired();

				if (em === false) return false;

				$out.empty().fadeTo(1, 1).css('display', 'block').append('<div id="loader"><img src="/assets/images/loader.gif"> Выполняю...</div>');

				$('#dialog').css('display', 'none');
				$('#dialog_container').css('display', 'none');

				return true;

			},
			success: function (data) {

				$('#message').fadeTo(1, 1).css('display', 'block').html(data.result);
				setTimeout(function () {
					$('#message').fadeTo(1000, 0);
				}, 20000);

				if (in_array(action, ['edit.plan', 'edit.kpi'])) $('.userblock[data-id="<?=$iduser?>"]').trigger('click');
				else if (action === 'edit.kpiBase') {

					if (typeof configpage === 'function') configpage();

				}

				DClose();

			}
		});

		$('#dialog').center();

	});

	$(document).off('change', '#values\\[\\]');
	$(document).on('change', '#values\\[\\]', function () {

		$cat = $(this).val().toString();

	});

	$(document).off('click', '#price');
	$(document).on('click', '#price', function () {

		var $el1 = $('#values\\[\\]');
		var $el2 = $("#price");

		$cat = $el1.val().toString();

		$el2.unautocomplete().flushCache();
		$el2.autocomplete("content/helpers/price.helpers.php?cat=" + $cat, {
			autofill: false,
			minChars: 3,
			cacheLength: 0,
			maxItemsToShow: 100,
			max: 100,
			selectFirst: false,
			multiple: false,
			delay: 10,
			matchSubset: 1,
			formatItem: function (data, j, n, value) {
				return '<div"><b>' + data[5] + ':</b> ' + data[0] + '</div>';
			},
			formatResult: function (data) {
				return data[0];
			},
			extraParams: {pr_cat: $el1.val().toString()}
		});
		$el2.result(function (value, data) {

			var str = '<option value="' + data[6] + '" selected>' + data[0] + '</option>';

			if (!isMobile) {

				$('#subvalues\\[\\]').multiselect('destroy').append(str).multiselect({
					sortable: true,
					searchable: true
				});
				$(".connected-list").css('height', "200px");

			}
			else $('#subvalues\\[\\]').append(str);

			$el2.val('');

		});

	});

	$('.dcreate')
		.off('click')
		.on('click', function () {

		var summa = $('#plan\\[1\\]\\[summa\\]').val();
		var marga = $('#plan\\[1\\]\\[marga\\]').val();

		$('.isumma').val(summa);
		$('.imarga').val(marga);

	});

	$('#tip').bind('change', function () {

		var tip = $(this).val();

		$.getJSON("modules/metrics/core.metrics.php?action=get.KPIvariants&tip=" + tip, function (data) {

			var $items = data.items;
			var $subitems = data.sub;
			var $el1 = $('#values\\[\\]');
			var $el2 = $('#subvalues\\[\\]');

			if ($items.length !== 0) {

				$('.vars').addClass('hidden');

				var str = '';

				for (var i in $items) str += '<option value="' + i + '">' + $items[i] + '</option>';

				$el1.empty().html(str);

				if (!isMobile) {

					$el1.multiselect('destroy').multiselect({
						sortable: true,
						searchable: true
					});
					$(".connected-list").css('height', "200px");

				}

				$('.prices').addClass('hidden');

			}
			else {

				$('.vars').addClass('hidden');
				$('.prices').addClass('hidden');

				$('#dialog').center();

			}

			if (in_array(tip, ['productCount', 'productSumma'])) {

				$('div[data-id="subvars"]').removeClass('hidden');
				$('.prices').removeClass('hidden');

				$el2.empty().multiselect('destroy').multiselect({
					sortable: true,
					searchable: true
				});
				$(".connected-list").css('height', "200px");

				$('#dialog').center();

			}
			else {

				if ($subitems.length !== 0) {

					$('.subvars').addClass('hidden');

					str = '';

					for (var i in $subitems) str += '<option value="' + i + '">' + $subitems[i] + '</option>';

					$el2.empty().html(str);

					if (!isMobile) {

						$el2.multiselect('destroy').multiselect({
							sortable: true,
							searchable: true
						});
						$(".connected-list").css('height', "200px");

					}

					$('div[data-id="subvars"]').removeClass('hidden');

					$('#dialog').center();

				}
				else {

					$('.subvars').removeClass('hidden');
					$('div[data-id="subvars"]').addClass('hidden');

				}

				$('.prices').addClass('hidden');

			}


		});

		return false;

	});

	$(document).on('change', '#file', function () {

		//console.log(this.files);

		var ext = this.value.split(".");
		var elength = ext.length;
		var carrentExt = ext[elength - 1].toLowerCase();

		if (in_array(carrentExt, ['csv', 'xls', 'xlsx']))
			$('.next').removeClass('graybtn');

		else {

			Swal.fire('Только в формате CSV, XLS, XLSX', '', 'warning');
			$('#file').val('');
			$('.next').addClass('graybtn');

		}

	});

	function Next() {

		if (!$('.next').hasClass('graybtn'))
			$('#planform').trigger('submit');

		else
			Swal.fire('Внимание', 'Вы забыли выбрать файл для загрузки', 'warning');

	}

</script>