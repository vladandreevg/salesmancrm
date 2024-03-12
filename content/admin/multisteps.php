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

$action = $_REQUEST['action'];
$id     = (int)$_REQUEST['id'];

if ( $action == "delete.do" ) {

	$db -> query( "DELETE FROM {$sqlname}multisteps WHERE id = '".$id."' AND identity = '$identity'" );
	print "Сделано";

	exit();

}
if ( $action == "set.do" ) {

	//существующие цепочки
	$threads = $db -> getCol( "SELECT CONCAT(direction, ':', tip) as thread FROM {$sqlname}multisteps WHERE identity = '$identity'" );

	//существующие этапы
	$astep = [];

	$r = $db -> query( "SELECT * FROM {$sqlname}dogcategory WHERE identity = '$identity' ORDER BY CAST(title AS UNSIGNED)" );
	while ($d = $db -> fetch( $r )) {

		$astep[ $d['idcategory'] ] = '2';

	}

	$isdefault = array_keys( $astep );

	$count = 0;

	$res = $db -> query( "SELECT * FROM {$sqlname}direction WHERE identity = '$identity'" );
	while ($data = $db -> fetch( $res )) {

		$r = $db -> query( "SELECT * FROM {$sqlname}dogtips WHERE identity = '$identity'" );
		while ($da = $db -> fetch( $r )) {

			//если такой цепочки нет, то создаем
			if ( !in_array( $data['id'].':'.$da['tid'], $threads ) ) {

				$list = [];

				$list['title'] = $data['title']."/".$da['title'];

				$list['direction'] = $data['id'];
				$list['tip']       = $da['tid'];

				$list['isdefault'] = $isdefault[0];
				$list['identity']  = $identity;

				$list['steps'] = json_encode( $astep );

				$db -> query( "INSERT INTO {$sqlname}multisteps SET ?u", $list );

				$count++;

			}

		}

	}

	print "Сделано. Создано ".$count." цепочек";

	exit();

}
if ( $action == "edit.do" ) {

	$data['title'] = $_REQUEST['title'];

	$thread            = explode( ":", $_REQUEST['thread'] );
	$data['direction'] = $thread[0];
	$data['tip']       = $thread[1];

	$data['isdefault'] = $_REQUEST['isdefault'];
	$data['identity']  = $identity;

	$steps  = $_REQUEST['steps'];
	$length = $_REQUEST['length'];

	$astep = [];

	foreach ( $steps as $k => $v ) {

		$astep[ $k ] = $length[ $k ];

	}

	$data['steps'] = json_encode( $astep );

	//print_r($data);
	//exit();

	if ( $id < 1 ) {

		$db -> query( "INSERT INTO {$sqlname}multisteps SET ?u", $data );
		print "Сделано";

	}
	else {

		unset( $data['identity'] );

		$db -> query( "UPDATE {$sqlname}multisteps SET ?u where id = '$id'", $data );
		print "Сделано";

	}

	exit();
}

if ( $action == "edit" ) {

	$clone = $_REQUEST['clone'];

	$threads = $db -> getCol( "SELECT CONCAT(direction, ':', tip) as thread FROM {$sqlname}multisteps WHERE identity = '$identity'" );

	$multistep = $db -> getRow( "SELECT * FROM {$sqlname}multisteps WHERE id = '$id' AND identity = '$identity'" );
	$steps     = json_decode( $multistep['steps'], true );

	$dcurrent = '';

	if ( $clone == 'yes' ) {

		$id                 = 0;
		$multistep['title'] = '';

	}

	?>
	<div class="zagolovok"><b>Изменить / Добавить</b></div>

	<FORM method="post" action="/content/admin/<?php echo $thisfile; ?>" name="form" id="form">
		<INPUT name="action" id="action" type="hidden" value="edit.do">
		<INPUT name="id" id="id" type="hidden" value="<?= $id ?>">

		<DIV style="overflow-y:auto; overflow-x:hidden" id="formtabse" class="box--child p10">

			<div class="flex-container box--child mt10">

				<div class="flex-string wp20 gray2 fs-12 pt7 right-text">Название цепочки:</div>
				<div class="flex-string wp80 pl10">
					<INPUT name="title" type="text" class="required wp95" id="title" value="<?= $multistep['title'] ?>">
				</div>

			</div>

			<div class="flex-container box--child mt10 hidden">

				<div class="flex-string wp20 gray2 fs-12 pt7 right-text">Направление:</div>
				<div class="flex-string wp80 pl10">
					<?php
					$res = $db -> query( "SELECT * FROM {$sqlname}direction WHERE identity = '$identity'" );
					?>
					<select name="direction1" id="direction1" class="required wp95">
						<?php
						while ($data = $db -> fetch( $res )) {

							$s = ($data['id'] == $multistep['direction']) ? "selected" : "";
							print '<option value="'.$data['id'].'" '.$s.'>'.$data['title'].'</option>';

						}
						?>
					</select>&nbsp;
				</div>

			</div>

			<div class="flex-container box--child mt10 hidden">

				<div class="flex-string wp20 gray2 fs-12 pt7 right-text">Тип сделки:</div>
				<div class="flex-string wp80 pl10">
					<?php
					$res = $db -> query( "SELECT * FROM {$sqlname}dogtips WHERE identity = '$identity'" );
					?>
					<select name="tip1" id="tip1" class="required wp95">
						<?php
						while ($data = $db -> fetch( $res )) {

							$s = ($data['tid'] == $multistep['tip']) ? "selected" : "";
							print '<option value="'.$data['tid'].'" '.$s.'>'.$data['title'].'</option>';

						}
						?>
					</select>
				</div>

			</div>

			<div class="flex-container box--child mt10">

				<div class="flex-string wp20 gray2 fs-12 pt7 right-text">Применимость:</div>
				<div class="flex-string wp80 pl10">
					<?php
					$res = $db -> query( "SELECT * FROM {$sqlname}direction WHERE identity = '$identity'" );
					$dir = [];
					?>
					<select name="thread" id="thread" class="required wp95">
						<option value="">--</option>
						<?php
						while ($data = $db -> fetch( $res )) {

							print '<optgroup label="Направление: '.$data['title'].'" data-id="'.$data['id'].'">';

							$r = $db -> query( "SELECT * FROM {$sqlname}dogtips WHERE identity = '$identity'" );
							while ($da = $db -> fetch( $r )) {

								$dir[ $data['id'] ] = $data['title'];

								if ( $data['id'] == $multistep['direction'] )
									$dcurrent = 'для направления: "'.$data['title'].'"';

								$s = ($data['id'] == $multistep['direction'] && $da['tid'] == $multistep['tip']) ? "selected" : "";
								$d = (in_array( $data['id'].':'.$da['tid'], $threads ) && $s == '') ? "disabled" : "";

								print '<option value="'.$data['id'].':'.$da['tid'].'" '.$s.' '.$d.'>Тип: '.$da['title'].'</option>';

							}

							print '</optgroup>';

						}

						$dir = json_encode_cyr( $dir );
						?>
					</select>&nbsp;
				</div>

			</div>

			<div id="divider" class="mt20 mb20"><b class="blue">Цепочка этапов
					<span id="dir" class="red"><?= $dcurrent ?></span></b></div>

			<div class="flex-container box--child">

				<div class="flex-string wp100 pl10 mb20">

					<div class="viewdiv mb10">Надо выбрать нужные этапы и расставить их по порядку. Каждому этапу можно присвоить продолжительность для контроля</div>

					<table id="rowtable">
						<thead>
						<tr class="nodrag disable--select">
							<td width="100" align="center">Этап</td>
							<td>Описание этапа</td>
							<td width="140">По умолчанию</td>
							<td width="120" align="center">Длительность, дн.</td>
						</tr>
						</thead>
						<tbody>
						<?php
						$a = !empty($steps) ? array_reverse( array_keys( $steps ) ) : [];

						$order = (!empty( $a )) ? 'field(idcategory, '.implode( ",", $a ).') DESC' : 'title';

						$result = $db -> query( "SELECT * FROM {$sqlname}dogcategory WHERE identity = '$identity' ORDER BY ".$order );
						while ($data = $db -> fetch( $result )) {

							$s = !empty($steps) && (array_key_exists($data['idcategory'], $steps)) ? 'checked' : '';
							$f = ($data['idcategory'] == $multistep['isdefault']) ? 'checked' : '';
							$d = !empty($steps) && (array_key_exists($data['idcategory'], $steps)) ? '' : 'disabled';
							$g = !empty($steps) && (array_key_exists($data['idcategory'], $steps)) ? '' : 'grayb';

							if ( $steps[ $data['idcategory'] ] < 1 ) {
								$steps[$data['idcategory']] = 5;
							}

							print '
							<tr class="'.$g.' disable--select">
								<td width="100" class="enable--select">
									<div class="checkbox ml10">
										<label>
											<input name="steps['.$data['idcategory'].']" type="checkbox" id="steps['.$data['idcategory'].']" '.$s.' value="'.$data['idcategory'].'" class="check" />
											<span class="custom-checkbox mt10"><i class="icon-ok"></i></span>
											<span class="title ml10">'.$data['title'].'%</span>
										</label>
									</div>
								</td>
								<td><div class="ellipsis clearevents">'.$data['content'].'</div></td>
								<td width="140">
									<div class="radio">
										<label>
											<input name="isdefault" type="radio" id="isdefault" '.$d.' '.$f.' value="'.$data['idcategory'].'" />
											<span class="custom-radio mt10"><i class="icon-radio-check"></i></span>
											<span class="title">да</span>
										</label>
									</div>
								</td>
								<td width="120">
									<INPUT name="length['.$data['idcategory'].']" type="number" id="length['.$data['idcategory'].']" class="wp70" value="'.$steps[ $data['idcategory'] ].'" min="1" '.$d.' autocomplete="off">
								</td>
							</tr>
							';

						}
						?>

						</tbody>
					</table>

				</div>

			</div>

		</DIV>

		<hr>

		<div class="button--pane text-right">

			<A href="javascript:void(0)" onclick="$('#form').trigger('submit')" class="button">Сохранить</A>&nbsp;
			<A href="javascript:void(0)" onclick="DClose();" class="button">Отмена</A>

		</div>

	</form>

	<script>

		var h = $('#dialog_container').actual('height') * 0.8;
		var h2 = h - $('.zagolovok').actual('outerHeight') - $('.button--pane').actual('outerHeight') - 10;
		var dir = JSON.parse('<?=$dir?>');

		$('#dialog').css({'width': '800px'});
		$('#formtabse').css({'max-height': h2 + 'px'});

		$(function () {

			$(".multiselect").multiselect({sortable: true, searchable: false});
			$(".connected-list").css({'max-height': '300px', 'height': '300px'});

		});

		$('.check').bind('click', function () {

			if ($(this).prop('checked')) {

				$(this).closest('tr').find('input').not(this).prop('disabled', false);
				$(this).closest('tr').removeClass('grayb');

			}
			else {

				$(this).closest('tr').find('input').not(this).prop('disabled', true);
				$(this).closest('tr').addClass('grayb');

			}

		});

		$('#thread').on('change', function () {

			var id = $(this).find('option:selected').closest('optgroup').data('id');
			var d = dir[id];

			$('#dir').html('для направления: "' + d + '"');

		});

		$("#rowtable").tableDnD({
			onDragClass: "tableDrag",
			onDrop: function (table, row) {
			}
		});

		$('#form').ajaxForm({

			beforeSubmit: function () {

				var $out = $('#message');
				var em = checkRequired();

				if (em === false) return false;

				$('#dialog').css('display', 'none');
				$('#dialog_container').css('display', 'none');

				$out.fadeTo(1, 1).css('display', 'block').append('<div id=loader><img src=/assets/images/loader.gif> Загрузка данных. Пожалуйста подождите...</div>');

				return true;

			},
			success: function (data) {

				//$('#contentdiv').load('content/admin/<?php echo $thisfile; ?>');
				razdel(hash);

				$('#message').fadeTo(1, 1).css('display', 'block').html(data);
				setTimeout(function () {
					$('#message').fadeTo(1000, 0);
				}, 20000);

				DClose();
			}

		});

	</script>
	<?php

	exit();
}

if ( empty( $_SERVER['HTTP_X_REQUESTED_WITH'] ) && strtolower( $_SERVER['HTTP_X_REQUESTED_WITH'] ) != 'xmlhttprequest' ) {

	print '<div class="warning text-center"><br>Доступ запрещен.<br>Обратитесь к администратору.<br><br></div>';

	exit();

}

?>
<h2>&nbsp;Раздел: "МультиВоронка"</h2>

<div class="viewdiv mb15">
	В этом разделе можно задать персональную цепочку этапов сделок для каждого Направления/Типа сделки.<br>Либо использовать функцию
	<a href="javascript:void(0)" onclick="setSteps()" title="" class="button">Создать все</a> для автоматического создания всех возможных цепочек.
</div>

<table id="catlist">
	<thead class="hidden-iphone sticked--top">
	<tr class="th40">
		<th class="w250 text-left">Название</th>
		<th class="text-left">Этапы</th>
		<th class="w120 text-left">Длительность, дн.</th>
		<th class="w160"></th>
	</tr>
	</thead>
	<?php
	$result = $db -> query( "SELECT * FROM {$sqlname}multisteps WHERE identity = '$identity' ORDER by title" );
	while ($data = $db -> fetch( $result )) {

		$steplist = json_decode( $data['steps'], true );
		$steps    = array_keys( $steplist );
		$s        = [];

		foreach ( $steps as $step ) {

			if ( $step != $data['isdefault'] )
				$s[] = current_dogstepname( $step ).'%';
			else $s[] = '<b class="red">'.current_dogstepname( $step ).'%</b>';

		}

		$length = array_sum( json_decode( $data['steps'], true ) );
		?>
		<tr class="ha th40">
			<td>
				<div class="fs-12 pl10 mb10 mt5 ellipsis"><b><?= $data['title'] ?></b></div>
				<div class="fs-09 gray2">
					<i class="icon-direction gray"></i><?= current_direction( (int)$data['direction'] ) ?>,
					<i class="icon-briefcase-1 gray"></i><?= current_dogtype( (int)$data['tip'] ) ?></div>
			</td>
			<td>
				<div class="fs-12"><?= implode( ", ", $s ) ?></div>
			</td>
			<td>
				<div class="fs-12"><?= $length ?></div>
			</td>
			<td>
				<a href="javascript:void(0)" onclick="doLoad('/content/admin/<?php echo $thisfile; ?>?action=edit&id=<?= $data['id'] ?>');" title="Редактировать" class="button dotted bluebtn"><i class="icon-pencil"></i></a>
				<a href="javascript:void(0)" onclick="doLoad('/content/admin/<?php echo $thisfile; ?>?action=edit&id=<?= $data['id'] ?>&clone=yes');" title="Клонировать" class="button dotted greenbtn"><i class="icon-buffer"></i></a>
				<a href="javascript:void(0)" onclick="cf=confirm('Вы действительно хотите удалить запись?');if (cf)deleteStep('<?= $data['id'] ?>')" title="Удалить" class="button dotted redbtn"><i class="icon-cancel-circled-1"></i></a>
			</td>
		</tr>
	<?php } ?>
</table>

<div class="button--group1 box--child" style="position: fixed; bottom: 40px; left: 380px; z-index: 100;">

	<a href="javascript:void(0)" onclick="doLoad('content/admin/<?php echo $thisfile; ?>?action=edit');" class="button bluebtn box-shadow" title="Добавить"><i class="icon-plus-circled"></i>Добавить</a>

</div>

<div class="pagerefresh refresh--icon admn red" onclick="doLoad('/content/admin/<?php echo $thisfile; ?>?action=edit');" title="Добавить"><i class="icon-plus-circled"></i></div>
<div class="pagerefresh refresh--icon admn orange" onclick="openlink('https://salesman.pro/docs/119')" title="Документация"><i class="icon-help"></i></div>

<div class="space-100"></div>


<script>

	function deleteStep(id) {

		$('#message').fadeTo(1, 1).css('display', 'block').append('<div id="loader"><img src="/assets/images/loader.gif"> Выполняю. Пожалуйста подождите...</div>');

		$.ajax({
			type: "GET",
			url: '/content/admin/<?php echo $thisfile; ?>?action=delete.do&id=' + id,
			success: function (viewData) {

				$('#message').fadeTo(1, 1).css('display', 'block').html(viewData);
				setTimeout(function () {
					$('#message').fadeTo(1000, 0);
				}, 20000);

				//$('#contentdiv').load('content/admin/<?php echo $thisfile; ?>');
				razdel(hash);

			}
		});

	}

	function setSteps() {

		$('#message').fadeTo(1, 1).css('display', 'block').append('<div id="loader"><img src="/assets/images/loader.gif"> Выполняю. Пожалуйста подождите...</div>');

		$.ajax({
			type: "GET",
			url: '/content/admin/<?php echo $thisfile; ?>?action=set.do',
			success: function (viewData) {

				$('#message').fadeTo(1, 1).css('display', 'block').html(viewData);
				setTimeout(function () {
					$('#message').fadeTo(1000, 0);
				}, 20000);

				//$('#contentdiv').load('content/admin/<?php echo $thisfile; ?>');
				razdel(hash);

			}
		});

	}

</script>