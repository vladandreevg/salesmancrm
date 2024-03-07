<?php
/* ============================ */
/*         SalesMan CRM         */
/* ============================ */
/* (C) 2016 Vladislav Andreev   */
/*       SalesMan Project       */
/*        www.isaler.ru         */
/*        ver. 2017.x           */

/* ============================ */

use Salesman\Currency;

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

$c = new Currency();

if ( $action == "edit.on" ) {

	$params = $_REQUEST;

	$c -> edit( $id, $params );

	print "Сделано";

	exit();

}

if ( $action == "exchange.on" ) {

	$courses = $_REQUEST['course'];

	foreach ( $courses as $id => $course ) {

		if ( $course != '' ) {

			$c -> edit( $id, [
				"id"     => $id,
				"course" => $course
			] );

		}

	}

	print "Сделано";

	exit();

}

if ( $action == "delete.on" ){

	$r = $c -> delete($id);

	print ($r['result'] == 'successe') ? 'Успешно' : $r['error']['text'];
	exit();

}

if ( empty( $_SERVER['HTTP_X_REQUESTED_WITH'] ) && strtolower( $_SERVER['HTTP_X_REQUESTED_WITH'] ) != 'xmlhttprequest' ) {

	print '<div class="bad text-center"><br>Доступ запрещен.<br>Обратитесь к администратору.<br><br></div>';
	exit();

}

if ( $action == 'edit' ) {

	$currency = [];

	if ( $id > 0 ) {

		$currency = $c -> currencyInfo( $id );

	}

	?>
	<div class="zagolovok">Редактирование валюты</div>
	<FORM action="content/admin/<?php echo $thisfile; ?>" method="post" enctype="multipart/form-data" name="Form" id="Form">
		<INPUT type="hidden" name="action" id="action" value="edit.on">
		<INPUT type="hidden" name="id" id="id" value="<?= $id ?>">

		<div id="formtabse" class="flex-vertical box--child wp100 p10" style="max-height: 80vh;">

			<div class="flex-container wp100 border--bottom mb20">

				<div class="flex-string wp100 uppercase fs-07 Bold gray2 mb5">Название</div>
				<div class="flex-string wp100 relativ fs-12 Bold blue">

					<input type="text" id="name" name="name" value="<?= $currency['name'] ?>" class="wp95">

				</div>

			</div>
			<div class="flex-container wp100 border--bottom mb20">

				<div class="flex-string wp100 uppercase fs-07 Bold blue mb5">Выбор знака</div>
				<div class="flex-string wp100 relativ fs-12 Bold blue">

					<select id="code" name="code" class="w180">
						<option value="">-- не выбрано --</option>
						<?php
						foreach ( Currency::HTMLCODE as $key => $code ) {

							print '<option value="'.$key.'" '.($currency['code'] == $key ? 'selected' : '').'>'.$key.'  &rArr;  '.$code.'</option>';

						}
						?>
					</select>

				</div>

			</div>

			<div class="flex-container wp100 border--bottom mb20">

				<div class="flex-string wp100 uppercase fs-07 Bold blue mb5">Отображать как</div>
				<div class="flex-string wp100 relativ fs-12 Bold blue">

					<input type="text" id="view" name="view" value="<?= $currency['view'] ?>" class="wp95">

					<div class="fs-07 gray2">Оставить пустым, если нужно отображать знаком. Можно указать
						<a href="https://www.rabotayvinter.net/html/simvoly_html/1_znaki_valjut.php" target="_blank" title="Знаки валют">HTML-версию</a> знака
					</div>

				</div>

			</div>

			<div class="flex-container wp100 border--bottom mb10">

				<div class="flex-string wp100 uppercase fs-07 Bold blue mb5">Текущий курс</div>
				<div class="flex-string wp100 relativ fs-12 Bold blue">

					<input type="text" id="course" name="course" value="<?= $currency['course'] ?>" class="wp95">

				</div>

			</div>

		</div>

		<hr>

		<div class="button--pane text-right">

			<A href="javascript:void(0)" onclick="$('#Form').trigger('submit')" class="button">Сохранить</A>&nbsp;
			<A href="javascript:void(0)" onclick="DClose()" class="button">Отмена</A>

		</div>

	</FORM>

	<script>

		$(function () {

			$('#dialog').center();

			$('#Form').ajaxForm({
				beforeSubmit: function () {

					var $out = $('#message');

					$out.empty().css('display', 'block').append('<div id="loader"><img src="/assets/images/loader.gif"> Пожалуйста подождите...</div>');

					return true;

				},
				success: function (data) {

					$('#message').fadeTo(1, 1).css('display', 'block').html(data);

					setTimeout(function () {
						$('#message').fadeTo(1000, 0);
					}, 20000);

					razdel('currency');

					DClose();

				}
			});

		});

	</script>
	<?php
	exit();
}

if ( $action == 'exchange' ) {

	$list = $c -> currencyList();

	//print_r($list);
	?>
	<div class="zagolovok">Обновление курсов</div>
	<FORM action="content/admin/<?php echo $thisfile; ?>" method="post" enctype="multipart/form-data" name="Form" id="Form">
		<INPUT type="hidden" name="action" id="action" value="exchange.on">

		<div id="formtabse" class="box--child wp100" style="max-height:80vh; overflow-y:auto !important; overflow-x:hidden">

			<?php
			foreach ( $list as $item ) {

				print '
				<div class="flex-container float box--child p10 border-bottom relativ ha fs-10 mt5 mb5" data-id="'.$item['id'].'">
			
					<div class="flex-string w100" title="'.$item['name'].'">
						<div class="button dotted rounded bluebtn text-center w90">'.$item['symbol'].'</div>
					</div>
					<div class="flex-string float">
						
						<input id="course['.$item['id'].']" name="course['.$item['id'].']" class="wp95">
						<div class="fs-07 gray2">'.$item['name'].'</div>
						
					</div>
			
				</div>
				';

			}
			?>

		</div>

		<hr>

		<div class="button--pane text-right">

			<A href="javascript:void(0)" onclick="$('#Form').trigger('submit')" class="button">Сохранить</A>&nbsp;
			<A href="javascript:void(0)" onclick="DClose()" class="button">Отмена</A>

		</div>

	</FORM>

	<script>

		$(function () {

			$('#dialog').center();

			$('#Form').ajaxForm({
				beforeSubmit: function () {

					var $out = $('#message');

					$out.empty().css('display', 'block').append('<div id="loader"><img src="/assets/images/loader.gif"> Пожалуйста подождите...</div>');

					return true;

				},
				success: function (data) {

					$('#message').fadeTo(1, 1).css('display', 'block').html(data);

					setTimeout(function () {
						$('#message').fadeTo(1000, 0);
					}, 20000);

					razdel('currency');

					DClose();

				}
			});

		});

	</script>
	<?php
	exit();

}

if ( $action == 'log' ) {

	$currency = [];

	if ( $id > 0 ) {

		$currency = Currency ::currencyLog( $id );

	}

	?>
	<div class="zagolovok">Лог изменения валюты</div>

	<div id="formtabse" class="box--child wp100" style="max-height: 80vh;">

		<div class="flex-container float box--child fs-11 graybg no-border Bold hidden-iphone sticked--top">

			<div class="flex-string w120 p10">Дата</div>
			<div class="flex-string w30 p10"></div>
			<div class="flex-string w120 p10">Курс</div>
			<div class="flex-string float p10">Изменил</div>

		</div>
		<?php
		foreach ( $currency as $item ) {

			print '
				<div class="flex-container float box--child border-bottom relativ ha fs-10 mt5 mb5">
			
					<div class="flex-string w120 p10">
						'.get_sfdate( $item['datum'] ).'
					</div>
					<div class="flex-string w30 p10">
						<b class="'.$item['color'].'">'.$item['icon'].'</b> 
					</div>
					<div class="flex-string w120 p10">
						<div class="fs-10 Bold">'.num_format( $item['course'], 4 ).'</div>
					</div>
					<div class="flex-string p10 float">
						<i class="icon-user-1 blue"></i> '.current_user( (int)$item['iduser'] ).'
					</div>
			
				</div>
				';

		}
		?>

	</div>
	<?php
	exit();
}

if ( $action == "" ) {

	$list = (new Currency()) -> currencyList();
	?>

	<h2>&nbsp;Раздел: &quot;Настройка валют"</h2>

	<div class="infodiv">

		<h3>Важно</h3>

		<p>Основная валюта учета задается в разделе "Общие настройки / Настройки под бизнес". У вас настроено -
			<span class="Bold blue button bluebtn dotted bgwhite ptb5lr15"><?= $valuta ?></span>. В этом разделе вы можете задать прочие валюты, в которых необходимо выставлять счета.
		</p>
		<p>Валюта, которую нужно будет использовать в Счетах, Документах, Актах, задается непосредственно в Сделке.</p>

	</div>

	<div class="flex-container float box--child p10 fs-11 graybg no-border Bold hidden-iphone sticked--top">

		<div class="flex-string w60">Знак</div>
		<div class="flex-string wp20">Название</div>
		<div class="flex-string wp10">Текст</div>
		<div class="flex-string wp15">Курс</div>
		<div class="flex-string wp10">Дата</div>
		<div class="flex-string float"></div>
		<div class="flex-string w140"></div>
		<div class="flex-string w10"></div>

	</div>
	<?php
	foreach ( $list as $item ) {

		$log  = $date = [];
		$logs = array_reverse(Currency ::currencyLog( $item['id'] ));
		foreach ( $logs as $l ) {

			$log[]  = $l['course'];
			$date[] = get_sfdate3( $l['datum'] );

		}



		$currencyCountExplore = $db -> getOne("SELECT COUNT(*) FROM ".$sqlname."dogovor WHERE idcurrency = '$item[id]'");

		print '
			<div class="flex-container float box--child p10 border-bottom relativ ha fs-10 mt5 mb5" data-id="'.$item['id'].'">
		
				<div class="flex-string w60">
					'.($item['code'] != '' ? '<div class="button dotted rounded bluebtn text-center">'.$item['code'].'</div>' : '<div class="button dotted rounded text-center">--</div>').'
				</div>
				<div class="flex-string wp20 nopad middle viewlog hand">
					<span class="fs-12 Bold pt5 ellipsis"><div class="bullet fs-07 redbg" title="Используется в сделках">'.$currencyCountExplore.'</div>  '.$item['name'].'</span>
				</div>
				<div class="flex-string wp10 pt10">
					'.($item['view'] != '' ? '<div class="Bold blue">'.$item['view'].'</div>' : '--').'
				</div>
				<div class="flex-string wp15 pt10">
					<div class="fs-12 Bold">'.num_format( $item['course'], 4 ).'</div>
				</div>
				<div class="flex-string wp10 pt10">
					<div>'.format_date_rus( $item['datum'] ).'</div>
				</div>
				<div class="flex-string float hidden-iphone bar" data-id="'.$item['id'].'" data-date="'.implode( ",", $date ).'">
				
					<div class="bar-'.$item['id'].' block wp90">'.implode( ",", $log ).'</div>
					
				</div>
				<div class="flex-string w140">
					
					<A href="javascript:void(0)" onclick="doLoad(\'content/admin/'.$thisfile.'?action=edit&id='.$item['id'].'\');" class="button dotted bluebtn m0"><i class="icon-pencil"></i></A>
				
					<A href="javascript:void(0)" onclick="cf=confirm(\'Вы действительно хотите удалить запись?\');if (cf)deleteCurrency(\''.$item['id'].'\');" class="button dotted redbtn m0"><i class="icon-cancel-circled"></i></A>
					
				</div>
				<div class="flex-string w10"></div>
		
			</div>
			';

	}
	?>

	<div class="button--group1 box--child" style="position: fixed; bottom: 40px; left: 380px; z-index: 100;">

		<a href="javascript:void(0)" onclick="doLoad('content/admin/<?php echo $thisfile; ?>?action=edit');" class="button bluebtn box-shadow" title="Добавить"><i class="icon-plus-circled"></i>Добавить</a>

		<a href="javascript:void(0)" onclick="doLoad('content/admin/<?php echo $thisfile; ?>?action=exchange');" class="button greenbtn box-shadow" title="Обновить курсы"><i class="icon-publish"></i>Обновить курсы</a>

	</div>

	<div class="pagerefresh refresh--icon admn red" onclick="doLoad('content/admin/<?php echo $thisfile; ?>?action=edit');" title="Добавить"><i class="icon-plus-circled"></i></div>
	<div class="pagerefresh refresh--icon admn orange" onclick="openlink('https://salesman.pro/docs/153')" title="Документация"><i class="icon-help"></i></div>

	<div class="space-100"></div>

	<script>

		$('.viewlog').trigger('click', function () {

			let id = $(this).closest('.flex-container').data('id');
			doLoad('content/admin/<?php echo $thisfile; ?>?action=log&id=' + id);

		});

		includeJS("/assets/js/jquery.sparkline.min.js");

		$('.bar').each(function () {

			let id = $(this).data('id');
			let days = $(this).data('date').split(',');

			$(".bar-" + id).sparkline('html', {
				type: 'line',
				lineColor: '#2980B9',
				width: '95%',
				tooltipFormat: 'Добавлено: {{offset:levels}} - {{y}}',
				tooltipValueLookups: {
					levels: days
				}
			});

		});

		function deleteCurrency(id) {

			$.get('content/admin/<?php echo $thisfile; ?>?action=delete.on&id='+id, function(data){

				$('#message').fadeTo(1, 1).css('display', 'block').html(data);

				setTimeout(function () {
					$('#message').fadeTo(1000, 0);
				}, 20000);

				razdel('currency');

			});

		}

	</script>

	<?php

}