<?php
/* ============================ */
/*         SalesMan CRM         */
/* ============================ */
/* (C) 2018 Vladislav Andreev   */
/*       SalesMan Project       */
/*        salesman.pro          */
/*        ver. 2018.x           */
/* ============================ */

use Salesman\Guides;

error_reporting( E_ERROR );
ini_set( 'display_errors', 1 );
header( "Pragma: no-cache" );

$action = $_REQUEST['action'];

if ( $action == 'setpparam' ) {
	setcookie( "onlymyp", $_REQUEST['onlymyp'], time() + 31536000 );
	setcookie( "pipeline-tip", $_REQUEST['tip'], time() + 31536000 );
	setcookie( "pipeline", implode( ",", (array)$_REQUEST['user'] ), time() + 31536000 );
	$action = '';

	exit();
}

$onlymyp   = $_COOKIE['onlymyp'];

$rootpath = dirname(__DIR__, 2);

include $rootpath."/inc/config.php";
include $rootpath."/inc/dbconnector.php";
include $rootpath."/inc/auth.php";
include $rootpath."/inc/func.php";
include $rootpath."/inc/settings.php";
include $rootpath."/inc/language/".$language.".php";

$thisfile = basename( __FILE__ );

$direction = $tip = $user = [];

if ( $_COOKIE['pipeline'] ) {
	$user = yexplode(",", (string)$_COOKIE['pipeline']);
}
if ( $_COOKIE['pipeline-tip'] ) {
	$tip = yexplode(",", (string)$_COOKIE['pipeline-tip']);
}
if ( $_COOKIE['pipeline-direction'] ) {
	$direction = yexplode(",", (string)$_COOKIE['pipeline-direction']);
}

//загружаем все возможные цепочки и конвертируем в JSON
$mFunnel   = getMultiStepList();
$modFunnel = $dirAll = $tipAll = [];

foreach ( $mFunnel as $ddirection => $dtips ) {

	$dirAll[] = $ddirection;

	foreach ( $dtips as $dtip => $items ) {

		$tipAll[] = $dtip;

		$modFunnel[ $ddirection ][ $dtip ] = array_keys( $items['steps'] );

	}

}

$modFunnel = json_encode_cyr( $modFunnel );
$dirAll    = json_encode_cyr( $dirAll );
$tipAll    = json_encode_cyr( array_unique( $tipAll ) );
?>
<div style="">

	<div class="inline miditxt pt5">&nbsp;&nbsp;<b>Pipeline. <?=$lang['pipeline']['SalesBySteps']?></b></div>

	<div class="tagsmenuToggler hand relativ inline" data-id="fhelper">
		<span class="fs-10 blue" title="<?=$lang['pipeline']['ColorScheme']?>"><i class="icon-help-circled"></i></span>
		<div class="tagsmenu fly left hidden" id="fhelper" style="left:-10px; top: 100%">
			<div class="blok p10 w200 fs-09">

				<div class="flex-container border-bottom1 fs-09 mt5">
					<div class="flex-string wp10">
						<div class="colordiv redbg-dark"></div>
					</div>
					<div class="flex-string wp90">
						<div class="Bold fs-11 ellipsis">&nbsp;!!! <?=$lang['pipeline']['ExpiredMore15day']?></div>
					</div>
				</div>

				<div class="flex-container border-bottom1 fs-09 mt5">
					<div class="flex-string wp10">
						<div class="colordiv redbg"></div>
					</div>
					<div class="flex-string wp90">
						<div class="Bold fs-11 ellipsis">&nbsp;<?=$lang['pipeline']['ExpiredLess15day']?></div>
					</div>
				</div>

				<div class="flex-container border-bottom1 fs-09 mt5">
					<div class="flex-string wp10">
						<div class="colordiv greenbg"></div>
					</div>
					<div class="flex-string wp90">
						<div class="Bold fs-11 ellipsis">&nbsp;<?=$lang['pipeline']['More15dayLess']?></div>
					</div>
				</div>

				<div class="flex-container border-bottom1 fs-09 mt5">
					<div class="flex-string wp10">
						<div class="colordiv greenbg-dark"></div>
					</div>
					<div class="flex-string wp90">
						<div class="Bold fs-11 ellipsis">&nbsp;<?=$lang['pipeline']['IsTime']?></div>
					</div>
				</div>

				<div class="flex-container border-bottom1 fs-09 mt5">
					<div class="flex-string wp10">
						<div class="colordiv bluebg"></div>
					</div>
					<div class="flex-string wp90">
						<div class="Bold fs-11 ellipsis">&nbsp;<?=$lang['pipeline']['More30day']?></div>
					</div>
				</div>

			</div>
		</div>
	</div>

	<div class="ydropGroup pull-aright mt5" style="margin-right: 130px;">

		<div class="ydropDown pipeline w0 dWidth">

			<?php
			$dir = $db -> getAll( "SELECT id, title FROM ".$sqlname."direction WHERE identity = '$identity' ORDER BY title" );
			?>

			<span title="<?= $fieldsNames['dogovor']['direction'] ?>"><i class="icon-address black"></i></span>
			<span class="ydropCount"><?= count( $direction ) ?> <?php echo $lang[ 'all' ][ 'Selected' ]?></span>
			<i class="icon-angle-down pull-aright"></i>

			<div class="yselectBox" style="max-height: 300px;">

				<div class="right-text">
					<div class="ySelectAll w0 inline" title="Выделить всё"><i class="icon-plus-circled"></i><?php echo $lang[ 'all' ][ 'Alls' ]; ?></div>
					<div class="yunSelect w0 inline" title="Снять выделение"><i class="icon-minus-circled"></i><?php echo $lang[ 'all' ][ 'Nothing' ]; ?></div>
				</div>

				<?php
				foreach ( $dir as $data ) {

					$s = (in_array( $data['id'], $direction )) ? "checked" : "";
					?>
					<div class="ydropString ellipsis">
						<label class="wp100">
							<input class="taskss" type="checkbox" name="ppl-direction[]" id="ppl-direction[]" value="<?= $data['id'] ?>" <?= $s ?>>&nbsp;<?= $data['title'] ?>
						</label>
					</div>
				<?php } ?>

			</div>
		</div>
		<div class="ydropDown pipeline w0 dWidth">

			<?php
			$dir = $db -> getAll( "SELECT tid, title FROM ".$sqlname."dogtips WHERE identity = '$identity' ORDER BY title" );
			?>

			<span title="<?= $fieldsNames['dogovor']['tip'] ?>"><i class="icon-folder-1"></i></span>
			<span class="ydropCount"><?= count( $tip ) ?> <?php echo $lang[ 'all' ][ 'Selected' ]?></span>
			<i class="icon-angle-down pull-aright"></i>

			<div class="yselectBox" style="max-height: 300px;">

				<div class="right-text">
					<div class="ySelectAll w0 inline" title="Выделить всё"><i class="icon-plus-circled"></i><?php echo $lang[ 'all' ][ 'Alls' ]; ?></div>
					<div class="yunSelect w0 inline" title="Снять выделение"><i class="icon-minus-circled"></i><?php echo $lang[ 'all' ][ 'Nothing' ]; ?>
					</div>
				</div>

				<?php
				foreach ( $dir as $data ) {

					$s = (in_array( $data['tid'], $tip )) ? "checked" : "";
					?>
					<div class="ydropString ellipsis">
						<label>
							<input class="taskss" type="checkbox" name="ppl-tip[]" id="ppl-tip[]" value="<?= $data['tid'] ?>" <?= $s ?>>&nbsp;<?= $data['title'] ?>
						</label>
					</div>
				<?php } ?>

			</div>
		</div>
		<?php if ( count( $user ) > 0 && $onlymyp != 'yes' ) { ?>
			<div id="chuser" class="ydropDown pipeline w0 dWidth">
				<?php
				$users = $db -> getAll( "SELECT iduser, title FROM ".$sqlname."user WHERE iduser IN (".implode( ",", $user ).") and identity = '$identity' ORDER BY title" );
				?>
				<span><i class="icon-users-1 black"></i></span>
				<span class="ydropCount"><?= count( $user ) ?> <?php echo $lang[ 'all' ][ 'Selected' ]?></span>
				<i class="icon-angle-down pull-aright"></i>

				<div class="yselectBox" style="max-height: 300px;">

					<div class="right-text">
						<div class="ySelectAll w0 inline" title="Выделить всё"><i class="icon-plus-circled"></i><?php echo $lang[ 'all' ][ 'Alls' ]; ?></div>
						<div class="yunSelect w0 inline" title="Снять выделение"><i class="icon-minus-circled"></i><?php echo $lang[ 'all' ][ 'Nothing' ]; ?></div>
					</div>

					<?php
					foreach ( $users as $data ) {

						$s   = (in_array( $data['iduser'], $user )) ? "checked" : "";
						$clr = ($data['iduser'] == $iduser1) ? "green" : "";
						?>
						<div class="ydropString ellipsis w160 ">
							<label class="<?= $clr ?>">
								<input class="taskss" type="checkbox" name="ppl-user[]" id="ppl-user[]" value="<?= $data['iduser'] ?>" <?= $s ?>>&nbsp;<?= $data['title'] ?>
							</label>
						</div>
					<?php } ?>

				</div>
			</div>
		<?php } ?>
		<div class="ydropDown pipeline w100" data-id="sort">

			<span title="Сортировать по"><i class="icon-sort-alt-down black"></i></span>
			<span class="ydropText">Дата план.</span>
			<i class="icon-angle-down pull-aright arrow"></i>

			<div class="yselectBox" style="max-height: 350px;">
				<div class="ydropString yRadio">
					<label>
						<input type="radio" name="sortby" id="sortby" data-title="<?php echo $lang[ 'all' ][ 'DatePlan' ]; ?>" value="datum" class="hidden">&nbsp;<?php echo $lang[ 'all' ][ 'DatePlan' ]; ?>
					</label>
				</div>
				<div class="ydropString yRadio">
					<label>
						<input type="radio" name="sortby" id="sortby" data-title="<?php echo $lang[ 'pipeline' ][ 'StepChange' ]; ?>" value="day" class="hidden">&nbsp;<?php echo $lang[ 'pipeline' ][ 'StepChange' ]; ?>
					</label>
				</div>
				<div class="ydropString yRadio">
					<label>
						<input type="radio" name="sortby" id="sortby" data-title="<?php echo $lang[ 'all' ][ 'Summa' ]; ?>" value="summa" class="hidden">&nbsp;<?php echo $lang[ 'all' ][ 'Summa' ]; ?>
					</label>
				</div>
			</div>

		</div>

	</div>

</div>
<div class="filterdiv inline">

	<div class="togglerbox hand zagolovok div-center" id="dtpCalFilter" data-id="CalFilter2">
		<span class="blue"><i class="icon-filter"></i><b><?php echo $lang[ 'all' ][ 'Filter' ]; ?></b>&nbsp;<span><i class="icon-angle-down" id="mapic"></i></span>
	</div>

	<div id="CalFilter2" class="p10 hidden">

		<form enctype="multipart/form-data" name="filterpipelineform" id="filterpipelineform">
			<INPUT type="hidden" name="action" value="setpparam" id="action">

			<div style="display: block; margin-top:20px;" class="box--child">

				<?php
				//require_once "../../inc/class/Guides.php";
				$users = Guides ::Users( [
					"users" => get_people( $iduser1, "yes" ),
					"exold" => true
				] );

				$actives = $inactives = '';

				foreach ( $users as $tip => $iuser ) {

					foreach ( $iuser as $iduser => $title ) {

						$clr = ($iduser == $iduser1) ? "green" : "";

						switch ($tip) {

							case "active":

								$actives .= '
								<div class="flex-string wp25 p5 '.$clr.'" style="flex-grow: unset;">
									<label class="ellipsis">
										<input name="user[]" type="checkbox" id="user[]" data-tip="actives" value="'.$iduser.'" '.(in_array( $iduser, $user ) || count( $user ) == 0 ? 'checked' : '').' '.($iduser == $iduser1 ? 'data-im="yes"' : '').'>&nbsp;<i class="icon-user-1 blue"></i><B>'.$title.'</B>
									</label>
								</div>
								';

							break;

							case "inactive":

								$inactives .= '
								<div class="flex-string wp25 p5 gray" style="flex-grow: unset;">
									<label class="ellipsis" title="Не активен">
										<input name="user[]" type="checkbox" id="user[]" data-tip="inactives" value="'.$iduser.'" '.(in_array( $iduser, $user ) || count( $user ) == 0 ? "checked" : "").'>&nbsp;<i class="icon-user-1 gray2"></i><B>'.$title.'</B>
									</label>
								</div>
								';

							break;

						}

					}

				}
				?>
				<div class="flex-container" style="flex-wrap: nowrap; flex-flow: column wrap;">
					<?= $actives ?>
					<?= $inactives ?>
				</div>

			</div>

			<div class="infodiv flex-container bgwhite block left-text mt10 p5">

				<div class="flex-string wp80 flex-container inline border-box pt5" style="flex-wrap: nowrap; flex-flow: column wrap;">

					<div class="flex-string w160 p5 Bold" style="flex-grow: unset;">
						<label><input name="onlymyp" id="onlymyp" type="checkbox" <?php if ( $onlymyp == 'yes' )
								print "checked" ?> value="yes">&nbsp;<?php echo $lang[ 'pipeline' ][ 'OnlyMy' ]; ?>&nbsp;<i class="icon-info-circled blue fs-09 info" title="Показывать только свои сделки. Остальные фильтры не работают"></i></label>
					</div>

					<?php
					if ( $inactives != '' ) {
						?>
						<div class="flex-string w160 p5 blue Bold" style="flex-grow: unset;">
							<label><input name="alls" id="alls" type="checkbox" value="yes">&nbsp;<?php echo $lang[ 'all' ][ 'All' ]; ?></label>
						</div>
					<?php } ?>

					<div class="flex-string w160 p5 green Bold" style="flex-grow: unset;">
						<label><input name="actives" id="actives" type="checkbox" value="yes">&nbsp;<?php echo $lang[ 'pipeline' ][ 'AllActive' ]; ?></label>
					</div>

				</div>

				<div class="flex-string wp20 border-box text-right inline">

					<a href="javascript:void(0)" onclick="doFilterP()" class="button fs-09 m0"><?php echo $lang[ 'pipeline' ][ 'ApplyFilter' ]; ?></a>

				</div>

			</div>

		</form>

	</div>

</div>

<div class="pagep"></div>

<script type="text/javascript">

	var sstop = 0;
	var $pipelineBlock = $('.pagep');

	var mFunnel = JSON.parse('<?=$modFunnel?>');
	var dirAll = JSON.parse('<?=$dirAll?>');
	var tipAll = JSON.parse('<?=$tipAll?>');

	if (Object.keys(mFunnel).length > 0) {


	}

	//console.log( $('#sortby').val() );

	$(function () {

		$.Mustache.load('/content/tpl/dt.pipeline.mustache');

		$pipelineBlock.append('<div class="contentloader"><img src="/assets/images/Services.svg" width="50px" height="50px"></div>');

		var cdheight = $pipelineBlock.closest('.nano').actual('height');
		var cdwidth = $('#last').actual('width');

		$('.contentloader').height(cdheight).width(cdwidth);

		var str = 'onlymyp=<?=$onlymyp?>&user=<?=$_COOKIE['pipeline']?>';

		var defSort = getCookie('pipelineSort');
		var defVal = $('.ydropDown[data-id="sort"]').find('input[value="' + defSort + '"]').data('title');
		$('.ydropText').html(defVal);

		$.getJSON('/content/desktop/pipeline.php', str, function (viewData) {

			viewData.language = $language;

			$pipelineBlock.empty().mustache('pipelineTpl', viewData).animate({scrollTop: 0}, 200);
			//ppSort(defSort);

		})
			.done(function () {

				sstop = $('#salesteps').offset().top;

				//console.log(sstop);

				stepsHeight();

				$('#pipelineSteps').hide();

				$pipelineBlock.find('div.step').draggable({
					containment: '.pipeline',
					helper: 'clone',
					revert: false,
					zIndex: 100,
					start: function (event, ui) {

						var wi = $('.step:first').actual('outerWidth') - 5;

						ui.helper.css({"width": wi + "px"});

					},
					drag: function (event, ui) {

						//ui.helper.addClass('bluebg');

					},
					stop: function (event, ui) {

						//ui.helper.removeClass('bluebg');

					}
				});
				$pipelineBlock.find('div.stepBlock').droppable({
					tolerance: "pointer",
					over: function (event, ui) {//если фигура над клеткой- выделяем её границей
						$(this).addClass('orangebg-sub');
					},
					out: function (event, ui) {//если фигура ушла- снимаем границу
						$(this).removeClass('orangebg-sub');
					},

					drop: function (event, ui) {//если бросили фигуру в клетку
						//$(this).append(ui.draggable);//перемещаем фигуру в нового предка
						$(this).removeClass('orangebg-sub');//убираем выделение

						var oldstep = $(ui.draggable).data('oldstep');
						var newstep = $(this).data('step');
						var did = $(ui.draggable).data('did');

						if (oldstep !== newstep)
							doLoad('/content/forms/form.deal.php?did=' + did + '&newstep=' + newstep + '&action=change.step&next=no');
					}
				});

				filterSet();

				if (!isMobile)
					$(".nano").nanoScroller();

				$('.pagep').closest('.nano').find('.contentloader').remove();

				// pipelineStepsPos();

				$('#sortby').trigger('click');

			});

		$('.pipeline').find('.nano-content').on('scroll', function () {

			var ntop = $('#salesteps').offset().top;

			//console.log( sstop + ' : ' + ntop );

			if (ntop < sstop) {
				$('#pipelineSteps').show();
			} else {
				$('#pipelineSteps').hide();
			}

		});

		/*Для старого интерфейса*/
		/*$('.pipeline').on('scroll', function () {

			var ntop = $('#salesteps').offset().top;

			//console.log( sstop + ' : ' + ntop );

			if (ntop < sstop) {
				$('#pipelineSteps').show();
			} else {
				$('#pipelineSteps').hide();
			}

		});*/

	});

	$('#ppl-tip\\[\\]')
		.off('click')
		.on('click', function () {

			var tip = [];

			$('#ppl-tip\\[\\]:checked').each(function () {

				tip.push($(this).val());

			});

			tip = tip.join();

			setCookie('pipeline-tip', tip, {expires: 0});
			filterSet();

			//console.log(tip);

		});

	$('#ppl-direction\\[\\]')
		.off('click')
		.on('click', function () {

			var direction = [];

			$('#ppl-direction\\[\\]:checked').each(function () {

				direction.push($(this).val());

			});

			direction = direction.join();

			setCookie('pipeline-direction', direction, {expires: 0});
			filterSet();

			//console.log(direction);

		});

	$('#ppl-user\\[\\]')
		.off('click')
		.on('click', function () {

			var user = [];

			$('#ppl-user\\[\\]:checked').each(function () {

				user.push($(this).val());

			});

			user = user.join();

			setCookie('pipeline-user', user, {expires: 0});
			filterSet();

			//console.log(direction);

		});

	$(document).on('click', '#sortby', function () {

		var sort = $(this).val();

		$(this).prop('checked', true);

		setCookie('pipelineSort', sort, {expires: 365 * 24 * 60 * 60});

		ppSort(sort);

	});

	$('#alls')
		.off('click')
		.on('click', function () {

			$('#CalFilter2').find('#user\\[\\]').prop('checked', true);
			$(this).prop('checked', false);
			$('#onlymyp').prop('checked', false);

		});

	$('#actives')
		.off('click')
		.on('click', function () {

			$('#CalFilter2').find('#user\\[\\]').prop('checked', false);
			$('#CalFilter2').find('#user\\[\\][data-tip="actives"]').prop('checked', true);
			$(this).prop('checked', false);
			$('#onlymyp').prop('checked', false);

		});

	$('#onlymyp')
		.off('click')
		.on('click', function () {

			if (!$('#onlymyp').prop('checked')) {

				//return false;

			} else {

				$('#CalFilter2').find('#user\\[\\]').prop('checked', false);
				$('#CalFilter2').find('#user\\[\\][data-im="yes"]').prop('checked', true);

			}

		});

	function filterSet() {

		var element = $('#salesteps');

		var tip = [];
		var user = [];
		var direction = [];

		$('#ppl-tip\\[\\]:checked').each(function () {

			tip.push($(this).val());

		});
		$('#ppl-direction\\[\\]:checked').each(function () {

			direction.push($(this).val());

		});

		if ($('#chuser').is('div')) {

			$('#ppl-user\\[\\]:checked').each(function () {

				user.push($(this).val());

			});

		}

		element.find('.step').removeClass('hidden').each(function () {

			if (tip.length > 0) {

				if (!in_array($(this).data('tip'), tip)) $(this).addClass('hidden');

			}
			if (direction.length > 0) {

				if (!in_array($(this).data('direction'), direction)) $(this).addClass('hidden');

			}
			if (user.length > 0) {

				if (!in_array($(this).data('user'), user)) $(this).addClass('hidden');

			}

		});

		element.find('.steps').each(function () {

			var summa = 0;
			var step = $(this).data('step');

			$(this).find('.step').not('.hidden').each(function () {

				var sum = parseFloat($(this).data('summa').replace(/ /g, '').replace(/,/g, '.'));

				summa = summa + sum;

			});

			$(this).find('.stepsumma').html(setNumFormat(summa.toFixed(2), ',', ' ').replace('.', ','));

		});

		//попытка фильтровать этапы для выбранных воронок

		var stepUses = [];
		var stepUsesNew = [];

		//если направления или типы не выбраны
		if (direction.length < 1) direction = dirAll;
		if (tip.length < 1) tip = tipAll;

		for (var i in direction) {

			for (var j in tip) {

				stepUses = stepUses.concat(mFunnel[direction[i]][tip[j]]);

			}

		}

		//оставляем только уникальные значения
		for (var k in stepUses) {

			if (!in_array(stepUses[k], stepUsesNew)) stepUsesNew.push(stepUses[k]);

		}

		//console.log(stepUsesNew);

		//скрываем не нужные этапы
		element.find('.steps').each(function () {

			var step = parseInt($(this).data('step'));
			var width = 99 / parseInt(stepUsesNew.length);

			if (!in_array(step, stepUsesNew)) {

				$(this).addClass('hidden');
				$('#pipelineSteps').find('.steps[data-step="' + step + '"]').addClass('hidden');

			} else {

				//$(this).removeClass('hidden').css("width", width + "%");
				$('#pipelineSteps').find('.steps[data-step="' + step + '"]').removeClass('hidden')//.css("width", width + "%");

			}

		});

	}

	function filterSetD() {

		var direction = $('#ppl-direction option:selected').val();

		$('#salesteps .step').each(function () {

			if (direction !== '0') {

				if ($(this).data('tip') !== direction)
					$(this).addClass('hidden');
				else
					$(this).removeClass('hidden');

			}
			else $(this).removeClass('hidden');

		});

	}

	function doFilterP() {

		var url = '/content/desktop/dt.pipeline.php';
		var str = $('#filterpipelineform').serialize();

		$.get(url, str, function () {

			if (typeof razdel == 'function') {
				$('#pipeline').empty();
				razdel('pipeline');
			}

			$('#tabs-5').load(url);

			if (!isMobile)
				$(".nano").nanoScroller();

		});

	}

	function stepsHeight() {

		var maxH = 0;

		$('.stepBlock').each(function () {
			var thisH = $(this).actual('height');
			if (thisH > maxH) maxH = thisH;
		});

		$('.stepBlock').height(maxH);
	}

	function pipelineStepsPos() {

		var pw = $('#salesteps').actual('outerWidth');
		var pr = $('#salesteps').offset();

		$('#pipelineSteps').css({'width': pw + "px", 'left': pr.left - 5 + "px"}).hide();

	}

	function ppSort(ord) {

		var $element = $('#salesteps');
		var order = (ord) ? ord : 'day';

		//console.log(ord);

		$element.find('.steps').each(function () {

			var $fields, $container, sorted, index;

			$container = $(this);
			$fields = $(".step", $container);

			if (ord === 'datum') {

				$fields.sort(function (a, b) {

					var a1, a2, b1, b2;

					a1 = a.getAttribute('data-datum').split('.');
					b1 = b.getAttribute('data-datum').split('.');

					//console.log(a1 + ' : ' + b1);

					a2 = (new Date(parseInt(a1[2]), parseInt(a1[1]), parseInt(a1[0]))).getTime();
					b2 = (new Date(parseInt(b1[2]), parseInt(b1[1]), parseInt(b1[0]))).getTime();

					return (a2 < b2) ? -1 : (a2 < b2) ? 1 : 0;

				}).appendTo($fields.parent());

			}
			else if (ord === 'summa') {

				//$fields.parent().css({"border","1px dotted red"});

				$fields.sort(function (a, b) {

					var a1, b1;

					a1 = parseFloat(a.getAttribute('data-summa').replace(/ /g, '').replace(/,/g, '.'));
					b1 = parseFloat(b.getAttribute('data-summa').replace(/ /g, '').replace(/,/g, '.'));

					//console.log(a1 + ':' + b1);

					return (a1 > b1) ? -1 : (a1 < b1) ? 1 : 0;

					//return a1 - b1;
					//return a1 < b1;

				}).appendTo($fields.parent());

			}
			else {

				$fields.sort(function (a, b) {

					var a2, b2;

					a2 = parseInt(a.getAttribute('data-' + order));
					b2 = parseInt(b.getAttribute('data-' + order));

					//return parseInt(a.getAttribute('data-' + order)) < parseInt(b.getAttribute('data-' + order));

					return (a2 > b2) ? -1 : (a2 < b2) ? 1 : 0;

				}).appendTo($fields.parent());

			}

		});

	}

</script>