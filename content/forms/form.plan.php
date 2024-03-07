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
include $rootpath."/inc/language/".$language.".php";

$thisfile = basename( __FILE__ );

$dname  = [];
$result = $db -> query( "SELECT * FROM ".$sqlname."field WHERE fld_tip='dogovor' AND fld_on='yes' and identity = '$identity' ORDER BY fld_order" );
while ($data = $db -> fetch( $result )) {
	$dname[ $data['fld_name'] ] = $data['fld_title'];
}

function prenum($num) {
	return $num = str_replace( ".", ",", $num );
}

function prep($string) {
	$string = str_replace( "|", "", $string );
	$string = trim( $string );

	return $string;
}

$year   = $_REQUEST['year'];
$iduser = (int)$_REQUEST['id'];
$action = $_REQUEST['action'];

if ( $year == "" ) {
	$year = date( 'Y' );
}

if ( $action == 'edit' ) {

	$result = $db -> query( "SELECT * FROM ".$sqlname."plan WHERE iduser='".$iduser."' and year='".$year."' and identity = '$identity'" );
	while ($data_array = $db -> fetch( $result )) {
		$kol_plan[ $data_array['mon'] ] = str_replace( ".", ",", $data_array['kol_plan'] );
		$marga[ $data_array['mon'] ]    = str_replace( ".", ",", $data_array['marga'] );
	}
	?>
	<DIV class="zagolovok">План сотрудника:&nbsp;<B style="color:#000"><?= current_user( $iduser ) ?></B> на <?= $year ?> год</DIV>
	<FORM method="post" action="/content/core/core.plan.php" enctype="multipart/form-data" name="planform" id="planform">
		<INPUT name="iduser" id="iduser" type="hidden" value="<?= $iduser ?>">
		<INPUT name="action" id="action" type="hidden" value="edit">
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

		<div class="text-right button--pane">
			<A href="javascript:void(0)" onclick="$('#planform').trigger('submit')" class="button">Сохранить</A>&nbsp;
			<A href="javascript:void(0)" onclick="DClose();" class="button">Отмена</A>
		</DIV>
	</FORM>
	<?php
}

if ( $action == 'edit.old' ) {

	$result = $db -> query( "SELECT * FROM ".$sqlname."plan WHERE iduser='".$iduser."' and year='".$year."' and identity = '$identity'" );
	while ($data_array = $db -> fetch( $result )) {
		$kol_plan[ $data_array['mon'] ] = str_replace( ".", ",", $data_array['kol_plan'] );
		$marga[ $data_array['mon'] ]    = str_replace( ".", ",", $data_array['marga'] );
	}
	?>
	<DIV class="zagolovok">План сотрудника:&nbsp;<B style="color:#000"><?= current_user( $iduser ) ?></B> на <?= $year ?> год</DIV>
	<FORM method="post" action="/content/core/core.plan.php" enctype="multipart/form-data" name="planform" id="planform">
		<INPUT name="iduser" id="iduser" type="hidden" value="<?= $iduser ?>">
		<INPUT name="action" id="action" type="hidden" value="edit">
		<INPUT name="year" id="year" type="hidden" value="<?= $year ?>">

		<TABLE id="zebra">
			<thead class="sticked--top">
			<TR>
				<TH width="55" align="center"></TH>
				<TH align="center"><?= $dname['oborot'] ?></TH>
				<TH width="" align="center"><?= $dname['marg'] ?></TH>
				<TH width="55" align="center"></TH>
				<TH align="center"><?= $dname['oborot'] ?></TH>
				<TH width="" align="center"><?= $dname['marg'] ?></TH>
			</TR>
			</thead>
			<TR height="25">
				<TD align="right"><B>Янв.</B></TD>
				<TD align="center">
					<INPUT name="kol_plan_1" type="text" id="kol_plan_1" style="width: 95%;" value="<?= $kol_plan[1] ?>">
				</TD>
				<TD align="center">
					<INPUT name="marga_1" type="text" id="marga_1" style="width: 95%;" value="<?= $marga[1] ?>"></TD>
				<TD align="right"><B>Июл.</B></TD>
				<TD align="center">
					<INPUT name="kol_plan_7" type="text" id="kol_plan_7" style="width: 95%;" value="<?= $kol_plan[7] ?>">
				</TD>
				<TD align="center">
					<INPUT name="marga_7" type="text" id="marga_7" style="width: 95%;" value="<?= $marga[7] ?>"></TD>
			</TR>
			<TR height="25">
				<TD align="right"><B>Фев.</B></TD>
				<TD align="center">
					<INPUT name="kol_plan_2" type="text" id="kol_plan_2" style="width: 95%;" value="<?= $kol_plan[2] ?>">
				</TD>
				<TD align="center">
					<INPUT name="marga_2" type="text" id="marga_2" style="width: 95%;" value="<?= $marga[2] ?>"></TD>
				<TD align="right"><B>Авг.</B></TD>
				<TD align="center">
					<INPUT name="kol_plan_8" type="text" id="kol_plan_8" style="width: 95%;" value="<?= $kol_plan[8] ?>">
				</TD>
				<TD align="center">
					<INPUT name="marga_8" type="text" id="marga_8" style="width: 95%;" value="<?= $marga[8] ?>">
				</TD>
			</TR>
			<TR height="25">
				<TD align="right"><B>Мар.</B></TD>
				<TD align="center">
					<INPUT name="kol_plan_3" type="text" id="kol_plan_3" style="width: 95%;" value="<?= $kol_plan[3] ?>">
				</TD>
				<TD align="center">
					<INPUT name="marga_3" type="text" id="marga_3" style="width: 95%;" value="<?= $marga[3] ?>"></TD>
				<TD align="right"><B>Сен.</B></TD>
				<TD align="center">
					<INPUT name="kol_plan_9" type="text" id="kol_plan_9" style="width: 95%;" value="<?= $kol_plan[9] ?>">
				</TD>
				<TD align="center">
					<INPUT name="marga_9" type="text" id="marga_9" style="width: 95%;" value="<?= $marga[9] ?>"></TD>
			</TR>
			<TR height="25">
				<TD align="right"><B>Апр.</B></TD>
				<TD align="center">
					<INPUT name="kol_plan_4" type="text" id="kol_plan_4" style="width: 95%;" value="<?= $kol_plan[4] ?>">
				</TD>
				<TD align="center">
					<INPUT name="marga_4" type="text" id="marga_4" style="width: 95%;" value="<?= $marga[4] ?>"></TD>
				<TD align="right"><B>Окт.</B></TD>
				<TD align="center">
					<INPUT name="kol_plan_10" type="text" id="kol_plan_10" style="width: 95%;" value="<?= $kol_plan[10] ?>">
				</TD>
				<TD align="center">
					<INPUT name="marga_10" type="text" id="marga_10" style="width: 95%;" value="<?= $marga[10] ?>"></TD>
			</TR>
			<TR height="25">
				<TD align="right"><B>Май</B></TD>
				<TD align="center">
					<INPUT name="kol_plan_5" type="text" id="kol_plan_5" style="width: 95%;" value="<?= $kol_plan[5] ?>">
				</TD>
				<TD align="center">
					<INPUT name="marga_5" type="text" id="marga_5" style="width: 95%;" value="<?= $marga[5] ?>"></TD>
				<TD align="right"><B>Ноя.</B></TD>
				<TD align="center">
					<INPUT name="kol_plan_11" type="text" id="kol_plan_11" style="width: 95%;" value="<?= $kol_plan[11] ?>">
				</TD>
				<TD align="center">
					<INPUT name="marga_11" type="text" id="marga_11" style="width: 95%;" value="<?= $marga[11] ?>"></TD>
			</TR>
			<TR height="25">
				<TD align="right"><B>Июн.</B></TD>
				<TD align="center">
					<INPUT name="kol_plan_6" type="text" id="kol_plan_6" style="width: 95%;" value="<?= $kol_plan[6] ?>">
				</TD>
				<TD align="center">
					<INPUT name="marga_6" type="text" id="marga_6" style="width: 95%;" value="<?= $marga[6] ?>"></TD>
				<TD align="right"><B>Дек.</B></TD>
				<TD align="center">
					<INPUT name="kol_plan_12" type="text" id="kol_plan_12" style="width: 95%;" value="<?= $kol_plan[12] ?>">
				</TD>
				<TD align="center">
					<INPUT name="marga_12" type="text" id="marga_12" style="width: 95%;" value="<?= $marga[12] ?>"></TD>
			</TR>
		</TABLE>
		<hr>
		<div class="text-right button--pane">
			<A href="javascript:void(0)" onclick="$('#planform').trigger('submit')" class="button">Сохранить</A>&nbsp;
			<A href="javascript:void(0)" onclick="DClose();" class="button">Отмена</A>
		</DIV>
	</FORM>
	<?php
}

if ( $action == 'import' ) {
	?>
	<div class="zagolovok">Импорт планов на <b class="red"><?= $_REQUEST['year'] ?></b> год</div>
	<FORM method="post" action="/content/core/core.plan.php" enctype="multipart/form-data" name="planform" id="planform">
		<input name="action" id="action" type="hidden" value="import"/>
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
		</div>

		<hr>

		<div class="text-right button--pane">

			<a href="javascript:void(0)" onclick="Next()" class="button graybtn next">Импорт</a>&nbsp;
			<a href="javascript:void(0)" onclick="DClose()" class="button">Отмена</a>

		</div>
	</form>
	<?php
}
?>
<script>

	$('#dialog').css('width', '800px');

	$(function () {


		$('#planform').ajaxForm({
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

				$('#message').fadeTo(1, 1).css('display', 'block').html(data);
				setTimeout(function () {
					$('#message').fadeTo(1000, 0);
				}, 20000);

				configpage();
				DClose();

			},
			error: function (){

				Swal.fire({
					title: "Ошибка: Ошибка загрузки данных! Пересохраните файл в Excel и попробуйте снова!",
					type: "error"
				});

				$('#message').fadeTo(1, 0);

			}
		});

		$('#dialog').center();

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



	$('.dcreate')
		.off('click')
		.on('click', function () {

			var summa = $('#plan\\[1\\]\\[summa\\]').val();
			var marga = $('#plan\\[1\\]\\[marga\\]').val();

			$('.isumma').val(summa);
			$('.imarga').val(marga);

		});

</script>