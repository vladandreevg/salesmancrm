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

$action   = $_REQUEST['action'];
$edefault = [];

//доп.поля формы
$re = $db -> query( "select * from {$sqlname}field where fld_tip IN ('dogovor') and fld_on='yes' and (fld_name LIKE '%input%') and identity = '$identity' order by fld_order" );
while ($da = $db -> fetch( $re )) {

	$edefault[ $da['fld_name'] ] = $da['fld_title'];

}

if ( $action == "delete" ) {

	$step = $_REQUEST['step'];

	$params = json_decode( $db -> getOne( "SELECT params FROM {$sqlname}customsettings WHERE tip = 'dfieldsstep' and identity = '$identity'" ), true );

	//print_r($params);

	unset( $params[ $step ] );

	//print_r($params);

	$id = (int)$db -> getOne( "select id from {$sqlname}customsettings where tip='dfieldsstep' and identity = '$identity'" );

	if ( $id > 0 ) {
		$db -> query("UPDATE {$sqlname}customsettings SET ?u WHERE tip = 'dfieldsstep' and identity = '$identity'", [
			"datum"  => current_datumtime(),
			"params" => json_encode($params)
		]);
	}
	else {
		$db -> query("INSERT INTO {$sqlname}customsettings SET ?u", [
			"tip"      => "dfieldsstep",
			"params"   => json_encode($params),
			"identity" => $identity
		]);
	}

	print "Запись обновлена";

	exit();

}

if ( $action == "edit.do" ) {

	$id = $_REQUEST['id'];

	$data = $_REQUEST;

	//print_r($_REQUEST);

	$nparams = [];

	$params = json_decode( $db -> getOne( "SELECT params FROM {$sqlname}customsettings WHERE tip = 'dfieldsstep' and identity = '$identity'" ), true );

	$inputs = [];
	foreach ( $data['input'] as $input => $val ) {

		$inputs[ $input ] = $data['required'][ $input ];

	}

	$params[ 's'.$data['step'] ] = [
		"step"   => $data['step'],
		"inputs" => $inputs,
	];

	$id = (int)$db -> getOne( "select id from {$sqlname}customsettings where tip='dfieldsstep' and identity = '$identity'" );

	if ( $id > 0 ) {
		$db -> query("UPDATE {$sqlname}customsettings SET ?u WHERE tip = 'dfieldsstep' and identity = '$identity'", [
			"datum"  => current_datumtime(),
			"params" => json_encode($params)
		]);
	}
	else {
		$db -> query("INSERT INTO {$sqlname}customsettings SET ?u", [
			"tip"      => "dfieldsstep",
			"params"   => json_encode($params),
			"identity" => $identity
		]);
	}

	print "Запись обновлена";

	exit();

}

if ( $action == 'edit' ) {

	$id = $_REQUEST['id'];

	if ( !$id ) {
		$id = 0;
	}//'s1';

	$params = json_decode( $db -> getOne( "SELECT params FROM {$sqlname}customsettings WHERE tip = 'dfieldsstep' and identity = '$identity'" ), true );

	//$params = $test;
	//print_r($params);

	$steps = $inputs = $directions = $tips = [];

	foreach ( $params as $k => $param ) {

		if ( $k != $id ) {

			$steps[]      = $param['step'];
			$directions[] = $param['direction'];
			$tips[]       = $param['tip'];

			$u = array_keys( $param['inputs'] );
			foreach ($u as $item){
				$inputs[] = $item;
			}

		}

	}

	?>
	<div class="zagolovok"><b>Изменить / Добавить</b></div>
	<FORM action="/content/admin/<?php echo $thisfile; ?>" method="POST" name="form" id="form">
		<input name="action" type="hidden" value="edit.do" id="action"/>
		<input name="id" type="hidden" value="<?= $id ?>" id="<?= $id ?>"/>

		<DIV style="overflow-y:auto; overflow-x:hidden" id="formtabse" class="box--child p10">

			<div class="flex-container box--child mt10 mb10">

				<div class="flex-string wp20 gray2 fs-12 pt7 right-text">Этап сделки:</div>
				<div class="flex-string wp80 pl10">
					<?php
					$res = $db -> query( "SELECT * FROM {$sqlname}dogcategory WHERE identity = '$identity' ORDER BY title" );
					?>
					<select name="step" id="step" class="required wp95">
						<option value="">--выбор--</option>
						<?php
						while ($data = $db -> fetch( $res )) {

							$s = ($data['title'] == $params[ $id ]['step']) ? "selected" : "";
							$d = (in_array( $data['title'], $steps )) ? "disabled" : "";

							print '<option value="'.$data['title'].'" '.$s.' '.$d.'>'.$data['title'].'% - '.$data['content'].'</option>';

						}

						$s1 = ('close' == $params[ $id ]['step']) ? "selected" : "";
						$d1 = (in_array( 'close', $steps )) ? "disabled" : "";
						?>
						<option value="close" <?= $s1.' '.$d1 ?>>Закрытие сделки</option>
					</select>&nbsp;
				</div>

			</div>

			<hr>

			<div class="flex-container box--child">

				<div class="flex-string wp100 mb20">

					<div class="viewdiv mb10">
						Укажите поля, которые должны быть заполнены для перехода на указанный этап сделки
					</div>

					<table id="rowtable">
						<thead>
						<tr class="header_contaner" height="40">
							<TH width="350">Поле сделки</TH>
							<TH width="100">Обязательное</TH>
							<TH></TH>
						</tr>
						</thead>
						<?php
						//print_r($edefault);
						//ksort($edefault);
						foreach ( $edefault as $input => $title ) {

							$s1 = (in_array( $input, array_keys( $params[ $id ]['inputs'] ) )) ? 'checked' : '';
							$s2 = $params[ $id ]['inputs'][$input] == 'required' ? 'checked' : '';
							$d = (in_array( $input, $inputs )) ? 'disabled' : '';
							$g = (in_array( $input, $inputs )) ? 'grayb' : '';
							$c = (in_array( $input, $inputs )) ? 'secondary' : '';

							print '
							<tr class="'.$g.' ha" height="40" id="'.$input.'">
								<td width="350" class="enable--select">
								<div class="checkbox ml10">
									<label>
										<input name="input['.$input.']" type="checkbox" id="input['.$input.']" '.$s1.' '.$d.' value="'.$input.'" class="check" />
										<span class="custom-checkbox mt10 '.$c.'"><i class="icon-ok"></i></span>
										<span class="title ml10"><b>'.strtr( $input, $fieldsNames['dogovor'] ).'</b> ['.$input.']</span>
									</label>
								</div>
								</td>
								<td width="100">
								<div class="checkbox ml10">
									<label>
										<input name="required['.$input.']" type="checkbox" id="required['.$input.']" '.$s2.' '.$d.' value="required">
										<span class="custom-checkbox mt10 '.$c.'"><i class="icon-ok"></i></span>
										<span class="title ml10">да</span>
									</label>
								</div>
								</td>
								<td align="center"></td>
							</tr>';

						}
						?>
					</table>

				</div>

			</div>

		</DIV>

		<hr>

		<div class="button--pane text-right">
			<A href="javascript:void(0)" onclick="$('#form').trigger('submit')" class="button">Сохранить</A>&nbsp;
			<A href="javascript:void(0)" onclick="DClose()" class="button">Отмена</A>
		</div>

	</FORM>
	<script>

		var h = $('#dialog_container').actual('height') * 0.8;
		var h2 = h - $('.zagolovok').actual('outerHeight') - $('.button--pane').actual('outerHeight') - 10;

		$('#dialog').css({'width': '800px'});
		$('#formtabse').css({'max-height': h2 + 'px'});

		$(function () {

			$(".multiselect").multiselect({sortable: true, searchable: false});
			$(".connected-list").css({'max-height': '150px', 'height': '150px'});
			$('#dialog').center();

		});

		$('.check').on('click', function () {

			if ($(this).prop('checked') && $(this).prop('disabled') !== true) {

				//$(this).closest('tr').find('input').not(this).prop('disabled', false);
				//$(this).closest('tr').removeClass('grayb');

			}
			else if ($(this).prop('disabled') !== true) {

				//$(this).closest('tr').find('input').not(this).prop('disabled', true);
				//$(this).closest('tr').addClass('grayb');

			}

			if ($(this).prop('disabled')) $(this).prop('checked', false);

		});

		$('#form').ajaxForm({

			beforeSubmit: function () {

				var $out = $('#message');
				var em = checkRequired();

				if (em === false) return false;

				$('#dialog').css('display', 'none');
				$('#dialog_container').css('display', 'none');

				$out.fadeTo(1, 1).css('display', 'block').append('<div id=loader><img src=/assets/images/loader.gif> Загрузка данных. Пожалуйста подождите...</div>');

			},
			success: function (data) {

				razdel('dealfieldsforstep');

				DClose();

				$('#message').fadeTo(1, 1).css('display', 'block').html(data);
				setTimeout(function () {
					$('#message').fadeTo(1000, 0);
				}, 20000);

			}

		});

	</script>
	<?php

}

if ( $action == '' ) {

	$params = json_decode( $db -> getOne( "SELECT params FROM {$sqlname}customsettings WHERE tip = 'dfieldsstep' and identity = '$identity'" ), true );

	?>

	<h2>&nbsp;Раздел: "Настройка обязательных полей по этапам"</h2>

	<div class="infodiv">
		Настройте дополнительные поля сделок, которые должны быть заполнены для перехода на указанный этап. При этом:
		<ul>
			<li>Пользователю будет предложено дозаполнить только не указанные ранее данные</li>
			<li>Будет предложено дозаполнить также те данные, которые не были заполнены на предыдущих этапах</li>
			<li>При закрытии сделки будет предложено заполнить только поля, настроенные на Закрытие сделки</li>
		</ul>
	</div>

	<DIV class="mt15">

		<FORM action="content/admin/<?php echo $thisfile; ?>" method="post" enctype="multipart/form-data" name="setForm" id="setForm">
			<INPUT type="hidden" name="action" id="action" value="edit.on">

			<table>
				<thead class="hidden-iphone sticked--top">
				<tr class="th40">
					<TH class="w130 nodrop">Этап</TH>
					<TH class="w350 nodrop">Поля</TH>
					<TH class="nodrop"></TH>
				</tr>
				</thead>
				<?php
				foreach ( $params as $id => $param ) {

					$inputs = [];

					foreach ( $param['inputs'] as $input => $required ) {

						$inputs[] = strtr( $input, $edefault );

					}

					$param['step'] = ($param['step'] != 'close') ? $param['step'].'%' : 'Закрытие';
					?>
					<tr class="ha th40" id="<?= $id ?>">
						<td class="Bold"><?= $param['step'] ?></td>
						<td>
						<span class="fs-12 Bold">
							<?= implode( ", ", $inputs ) ?>
						</span>
						</td>
						<td>

							<a href="javascript:void(0)" class="button dotted bluebtn" onclick="doLoad('content/admin/<?php echo $thisfile; ?>?action=edit&id=<?= $id ?>');"><i class="icon-pencil"></i> Редактировать</a>&nbsp;

							<a href="javascript:void(0)" onclick="cf=confirm('Вы действительно хотите удалить набор?');if (cf) fdelete('<?= $id ?>');" class="button dotted redbtn"><i class="icon-cancel-circled-1"></i> Удалить</a>

						</td>
					</tr>
					<?php
				}
				?>

			</table>

		</FORM>

	</DIV>

	<div class="button--group1 box--child" style="position: fixed; bottom: 40px; left: 380px; z-index: 100;">

		<a href="javascript:void(0)" onclick="doLoad('content/admin/<?php echo $thisfile; ?>?action=edit');" class="button bluebtn box-shadow" title="Добавить"><i class="icon-plus-circled"></i>Добавить</a>

	</div>

	<div class="pagerefresh refresh--icon admn red" onclick="doLoad('content/admin/<?php echo $thisfile; ?>?action=edit');" title="Добавить"><i class="icon-plus-circled"></i></div>
	<div class="pagerefresh refresh--icon admn orange" onclick="openlink('https://salesman.pro/docs/123')" title="Документация"><i class="icon-help"></i></div>

	<div class="space-100"></div>

	<script>

		function fdelete(step) {

			$.get('content/admin/<?php echo $thisfile; ?>?action=delete&step=' + step, function (data) {

				$('#message').fadeTo(1, 1).css('display', 'block').html(data);

				setTimeout(function () {
					$('#message').fadeTo(1000, 0);
				}, 20000);

				razdel('dealfieldsforstep');

			});

		}

	</script>
	<?php
}