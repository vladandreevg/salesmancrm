<?php
/* ============================ */
/*         SalesMan CRM         */
/* ============================ */
/* (C) 2016 Vladislav Andreev   */
/*       SalesMan Project       */
/*        www.isaler.ru         */
/*        ver. 2017.x           */
/* ============================ */

use Salesman\Elements;

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

//require_once "../../inc/class/Elements.php";

$clid   = (int)$_REQUEST[ 'clid' ];
$pid    = (int)$_REQUEST[ 'pid' ];
$did    = (int)$_REQUEST[ 'did' ];
$action = $_REQUEST[ 'action' ];
$cid    = (int)$_REQUEST[ 'cid' ];

// перенаправляем на новую форму
if ( $action == 'add' )
	$action = 'edit';

$ress         = $db -> getOne( "select usersettings from ".$sqlname."user where iduser='".$iduser1."' and identity = '$identity'" );
$usersettings = json_decode( $ress, true );

$now = date( 'H:i', mktime( date( 'H' ), date( 'i' ), date( 's' ), date( 'm' ), date( 'd' ), date( 'Y' ) ) + ( $tzone ) * 3600 );

$thistime = ( date( 'H' ) > 20 ) ? current_datum( -1 )." 09:00" : current_datum()." ".$now;

if ( $action == "edit" ) {

	if ( $cid > 0 ) {

		$res       = $db -> getRow( "select * from ".$sqlname."history where cid = '$cid' and identity = '$identity'" );
		$datum     = $res[ "datum" ];
		$des       = $res[ "des" ];
		$tip       = $res[ "tip" ];
		$pid_array = $res[ "pid" ];
		$did       = (int)$res[ "did" ];
		$clid      = (int)$res[ "clid" ];

		$pids = yexplode( ";", (string)$pid_array );

		if ( $did <= 0 ) {

			$result = $db -> getRow( "select * from ".$sqlname."history where cid = '$cid' and identity = '$identity'" );
			$clid   = (int)$result[ "clid" ];
			$pid    = (int)$result[ "pid" ];
			$client = current_client( $clid );
			$person = current_person( $pid );

		}
		if ( $did > 0 ) {

			$result = $db -> getRow( "SELECT * FROM ".$sqlname."dogovor WHERE did = '$did' and identity = '$identity'" );
			$dog    = $result[ "title" ];
			$clid   = (int)$result[ "clid" ];
			$pid    = (int)$result[ "pid" ];

			$client = current_client( $clid );

			if ( $pid > 0 ) {

				$resultp = $db -> getRow( "SELECT * FROM ".$sqlname."personcat WHERE pid = '$pid' and identity = '$identity'" );
				$person  = $result[ "person" ];
				$clid    = (int)$result[ "clid" ];

			}

		}

	}
	else {

		$datum = current_datum().' '.$now;
		$npid  = $pid;
		$tip   = $actTitleDefault;//$GLOBALS['actDefault'];

		if ( $clid > 0 ) {

			$resulth = $db -> getRow( "SELECT * FROM ".$sqlname."clientcat WHERE clid = '$clid' and identity = '$identity'" );
			$client  = $resulth[ "title" ];
			$npid    = (int)$resulth[ "pid" ];
			$person  = current_person( $pid );

		}
		if ( $pid > 0 ) {

			$resulth = $db -> getRow( "SELECT * FROM ".$sqlname."personcat WHERE pid = '$pid' and identity = '$identity'" );
			$clid    = (int)$resulth[ "clid" ];
			$npid    = (int)$resulth[ "pid" ];
			$person  = $resulth[ "person" ];
			$client  = current_client( $clid );

		}
		if ( $did > 0 ) {

			$resultd = $db -> getRow( "SELECT * FROM ".$sqlname."dogovor WHERE did = '$did' and identity = '$identity'" );
			$dog     = $resultd[ "title" ];
			$clid    = (int)$resultd[ "clid" ];
			$npid    = (int)$resultd[ "pid" ];

			$client = current_client( $clid );
			$person = current_person( $pid );

		}

	}

	?>
	<DIV class="zagolovok">Изменение Активности</DIV>
	<FORM method="post" action="/content/core/core.history.php" enctype="multipart/form-data" name="sForm" id="sForm">
		<INPUT name="cid" type="hidden" id="cid" value="<?= $cid ?>">
		<INPUT type="hidden" name="action" value="edit">

		<?php
		$hooks -> do_action( "history_form_before", $_REQUEST );
		?>

		<div id="place"></div>

		<div id="flyitbox"></div>

		<DIV id="formtabs" style="overflow-y: auto; overflow-x: hidden" class="pad5">

			<div id="tab-form-1">

				<div class="row">

					<div class="column12 grid-12">
						<div id="divider" class="div-center"><b>Результат активности</b></div>
					</div>

				</div>

				<div class="row mb10">

					<div class="column12 grid-1 right-text fs-12 gray2 pt10">Дата:</div>
					<div class="column12 grid-2">
						<INPUT name="datum" type="text" class="required inputdatetime" id="datum" value="<?= $datum ?>" readonly>
					</div>

					<div class="column12 grid-1 right-text fs-12 gray2 pt10">Тип:</div>
					<div class="column12 grid-4">

						<select name="tip" id="tip" class="required wp100" data-change="activities" data-id="des">
							<?php
							$res = $db -> getAll( "SELECT * FROM ".$sqlname."activities WHERE filter IN ('all','activ') and identity = '$identity' ORDER by title" );
							foreach ( $res as $data ) {

								print '<option value="'.$data[ 'title' ].'" '.( texttosmall( $tip ) == texttosmall( $data[ 'title' ] ) ? "selected" : "" ).' style="color:'.$data[ 'color' ].'" data-color="'.$data[ 'color' ].'" data-icon="'.get_ticon($data[ 'title' ], '', true).'">'.$data[ 'title' ].'</option>';

							}
							?>
						</select>

					</div>

				</div>

				<hr>

				<div class="row">

					<div class="column12 grid-12">
						<TEXTAREA name="des" rows="4" class="required wp100" id="des" style="height:120px;" placeholder="Результат"><?= $des ?></TEXTAREA>
						<div id="tagbox" class="gray1 fs-09 mt5" data-id="des" data-tip="tip"><br>Начните с выбора
							<strong class="errorfont">типа активности</strong></div>
					</div>

				</div>
				<!--
				<hr>

				<div class="pb5 em gray2">Добавить быстрый тег:</div>
				<div id="tagbox" class="gray"><br />Начните с выбора <strong class="errorfont">типа активности</strong></div>
				-->

			</div>
			<div id="tab-form-2">

				<div class="row">

					<div class="column12 grid-12">
						<div id="divider" class="div-center"><b>Клиент, Контакты</b></div>
					</div>

				</div>

				<div class="infodiv">

					<span class="relativ cleared flex-container wp100">
						<span class="flex-string wp5 pt5 hidden-ipad"><i class="icon-building blue"></i></span>
						<span class="relativ cleared flex-string wp95">
							<input name="client" type="text" class="wp100" id="client" value="<?= $client ?>" placeholder="Начните вводить название. Например: Сэйлзмэн">
							<INPUT type="hidden" id="clid" name="clid" value="<?= $clid ?>">
							<span class="idel clearinputs pr10" title="Очистить"><i class="icon-block-1 red"></i></span>
						</span>
					</span>

				</div>

				<select name="pid[]" id="pid[]" multiple="multiple" class="multiselect" style="width: 50%;">
					<?php
					if ( $clid > 0 ) {

						$res = $db -> getAll( "SELECT * FROM ".$sqlname."personcat WHERE clid = '$clid' and identity = '$identity' ORDER BY person" );
						foreach ( $res as $data ) {

							$s = ( in_array( $data[ 'pid' ], (array)$pids ) ) ? "selected" : "";

							print '<OPTION value="'.$data[ 'pid' ].'" '.$s.'>'.$data[ 'person' ].'</OPTION>';

							unset( $pids[ array_search( $data[ 'pid' ], (array)$pids ) ] );

						}

					}
					else {

						$res = $db -> getAll( "SELECT * FROM ".$sqlname."personcat WHERE pid = '$pid' and identity = '$identity' ORDER BY person" );
						foreach ( $res as $data ) {

							print '<OPTION value="'.$data[ 'pid' ].'" '.( in_array( $data[ 'pid' ], (array)$pids ) ? "selected" : "" ).'>'.$data[ 'person' ].'</OPTION>';

							unset( $pids[ array_search( $data[ 'pid' ], (array)$pids ) ] );

						}

					}
					?>
				</select>

			</div>
			<div id="tab-form-5">

				<div class="row">

					<div class="column12 grid-12">
						<div id="divider" class="div-center"><b>Контакты прочие</b></div>
					</div>

				</div>

				<div class="column12 grid-12">

					<div class="flex-container">

						<div class="flex-string wp100">

							<div id="prsn" class="relativ">

								<div class="pid relativ">

									<input type="hidden" id="pids" name="pids" value="">
									<INPUT id="lst_spisokp" placeholder="Нажмите сюда для выбора" type="text" value="" readonly onclick="get_orgspisok('prsn','place','/content/helpers/person.helpers.php?action=get_personselector&put=yes','pids','lst_spisokp')" class="wp97">

								</div>

							</div>

						</div>

						<div class="flex-string wp100 mt5">

							<div id="pid_list">
								<?php
								foreach ( $pids as $pid ) {

									$person = current_person( (int)$pid );

									print '
									<div class="pid_box" data-id="person_'.$pid.'" title="'.$person.'">
										<INPUT type="hidden" name="pid[]" id="pid[]" value="'.$pid.'">
										<div class="el"><div class="del" onclick="delItem(\'person_'.$pid.'\')"><i class="icon-cancel-circled-1"></i></div>'.$person.'</div>
									</div>';

								}
								?>
							</div>

						</div>

					</div>

				</div>

			</div>
			<div id="tab-form-4">

				<div class="row">

					<div class="column12 grid-12">
						<div id="divider" class="div-center"><b>Файлы</b></div>
					</div>

				</div>

				<?php
				include $rootpath."/content/ajax/check_disk.php";
				if ( $diskLimit > 0 ) {

					print '
						<div class="infodiv text-center">
							<b>Ипользование диска:</b> Лимит: <b>'.$diskUsage[ 'total' ].'</b> Мб, Занято: <b class="red">'.$diskUsage[ 'current' ].'</b> Mb ( <b>'.$diskUsage[ 'percent' ].'</b> % )
						</div>
						';

				}
				?>

				<div id="filelist" class="flex-container"></div>

				<hr>

				<?php

				if ( $diskLimit == 0 || $diskUsage[ 'percent' ] < 100 ) {
					print
						'<DIV id="uploads">
							<div id="file-1" class="filebox">
								<input name="file[]" type="file" class="file" id="file[]" onchange="addfile();">
								<div class="delfilebox hand" onclick="deleteFilebox(\'file-1\')" title="Очистить">
									<i class="icon-cancel-circled red"></i></div>
							</div>
						</DIV>';
				}
				else {
					print '<div class="warning text-center"><b class="red">Превышен лимит использования диска</b></div>';
				}
				?>

			</div>
			<div id="tab-form-3" class="<?php echo ( $cid > 0 ) ? "hidden" : ""; ?>">

				<div class="row">

					<div class="column12 grid-12">
						<div id="divider" class="div-center"><b>Выполнить далее</b></div>
					</div>

				</div>

				<?php
				$tcount = getOldTaskCount( (int)$iduser1 );

				if ( (int)$otherSettings[ 'taskControl'] > 0 && (int)$tcount >= (int)$otherSettings[ 'taskControl'] ) {

					print '<div class="warning"><b class="red">Включен режим контроля выполненения дел.</b><br>У вас '.$tcount.' не выполненных дел - вы не можете создавать новые напоминания и добавлять Клиентов и Контакты, пока не закроете старые напоминания.</div>';

				}
				else {
					?>

					<div id="todoBoxExpress" class="mb20">

						<div class="flex-container box--child mt10">

							<div class="flex-string wp20 gray2 fs-12 pt7 right-text">Тема:</div>
							<div class="flex-string wp80 pl10">
								<INPUT name="todo[theme]" id="todo[theme]" type="text" value="<?= $title ?>" placeholder="Укажите тему напоминания" class="required wp95">
								<div class="em gray2 fs-09">Например: <b>Договориться о встрече</b></div>
							</div>

						</div>

						<hr>

						<div class="flex-container box--child mt10">

							<div class="flex-string wp20 gray2 fs-12 pt7 right-text">К исполнению:</div>
							<div class="flex-string wp80 pl10 relativ">

								<input name="todo[datumtime]" type="text" class="inputdatetime required" id="todo[datumtime]" value="<?= $thistime ?>" onclick="$('.datumTasksView').empty().hide()" onchange="getDateTasksNew('todo\\[datumtime\\]')" autocomplete="off">

								<div class="datumTasks hand tagsmenuToggler p10">
									Число дел: <span class="taskcount Bold">0</span>
									<div class="tagsmenu left hidden">
										<div class="blok"></div>
									</div>
								</div>
								<div class="datumTasksView" onblur="$('.datumTasksView').hide()"></div>

							</div>

						</div>

						<div class="flex-container box--child mt10 infodiv bgwhite" data-block="options">

							<div class="flex-string wp20 gray2 fs-12 pt7 right-text">Опции:</div>
							<div class="flex-string wp80 pt7 pl10 fs-11">

								<!--Пока не реализовано. Сложности с выводом таких напоминаний-->
								<div class="mb10 pl10">

									<label for="todo[day]" class="switch">
										<input type="checkbox" name="todo[day]" id="todo[day]" value="yes">
										<span class="slider empty"></span>
									</label>
									<span class="">&nbsp;Весь день&nbsp;<i class="icon-info-circled blue" title="Включите, чтобы напоминание не было привязано к времени"></i></span>

								</div>

								<div class="mb10 pl10">

									<label for="todo[readonly]" class="switch">
										<input type="checkbox" name="todo[readonly]" id="todo[readonly]" value="yes">
										<span class="slider empty"></span>
									</label>
									<span class="">&nbsp;Только чтение&nbsp;<i class="icon-info-circled blue" title="Включите, чтобы не ставить отметку о выполнении"></i></span>

								</div>

								<div class="mb10 pl10">

									<label for="todo[alert]" class="switch">
										<input type="checkbox" name="todo[alert]" id="todo[alert]" value="yes" <?php if ( $alert == 'no' || $usersettings[ 'taskAlarm' ] == 'yes' ) print "checked"; ?>>
										<span class="slider empty"></span>
									</label>
									<span class="">&nbsp;Напоминать&nbsp;<i class="icon-info-circled blue" title="Если включено, то будет показано всплывающее окно"></i></span>

								</div>

							</div>

						</div>

						<div class="flex-container box--child mt10">

							<div class="flex-string wp20 gray2 fs-12 pt7 right-text">Тип напоминания:</div>
							<div class="flex-string wp80 pl10">

								<select name="todo[tip]" id="todo[tip]" class="wp95 required" data-change="activities" data-id="todo[des]">
									<?php
									$res = $db -> getAll( "SELECT * FROM ".$sqlname."activities WHERE filter IN ('all','task') and identity = '$identity' ORDER by aorder" );
									foreach ( $res as $data ) {

										print '<option value="'.$data[ 'title' ].'" '.( $data[ 'id' ] == $actDefault ? "selected" : "" ).' style="color:'.$data[ 'color' ].'" data-color="'.$data[ 'color' ].'" data-icon="'.get_ticon($data[ 'title' ], '', true).'">'.$data[ 'title' ].'</option>';

									}
									?>
								</select>

							</div>

						</div>

						<div class="flex-container box--child mt10">

							<div class="flex-string wp20 gray2 fs-12 pt7 right-text">Исполнитель</div>
							<div class="flex-string wp80 pl10">

								<?php
								$element = new Elements();
								print $element -> UsersSelect( "todo[touser]", [
									"class"   => ['wp95'],
									"active"  => true,
									"sel"     => $iduser1,
									"noempty" => true
								] );
								?>

							</div>

						</div>

						<div class="flex-container box--child mt10">

							<div class="flex-string wp20 gray2 fs-12 pt7 right-text">Срочность:</div>
							<div class="flex-string wp80 pl10">

								<div class="like-input wp95">

									<div id="psdiv" class="speed">

										<input type="hidden" id="todo[speed]" name="todo[speed]" value="0" data-id="speed">
										<div class="but black w100 text-center" id="sp1" title="Не срочно" onClick="setPS('speed','1')">
											<i class="icon-down-big"></i>&nbsp;Не срочно
										</div>
										<div class="but black active w100 text-center" id="sp0" title="Обычно" onClick="setPS('speed','0')">
											<i class="icon-check-empty"></i>&nbsp;Обычно
										</div>
										<div class="but black w100 text-center" id="sp2" title="Срочно" onClick="setPS('speed','2')">
											<i class="icon-up-big"></i>&nbsp;Срочно
										</div>

									</div>

								</div>

							</div>

						</div>

						<div class="flex-container box--child mt10">

							<div class="flex-string wp20 gray2 fs-12 pt7 right-text">Важность:</div>
							<div class="flex-string wp80 pl10">

								<div class="like-input wp95">

									<div id="psdiv" class="priority">

										<input type="hidden" id="todo[priority]" name="todo[priority]" value="0" data-id="priority">
										<div class="but black w100 text-center" id="pr1" title="Не важно" onClick="setPS('priority','1')">
											<i class="icon-down-big"></i>&nbsp;Не важно
										</div>
										<div class="but black active w100 text-center" id="pr0" title="Обычно" onClick="setPS('priority','0')">
											<i class="icon-check-empty"></i>&nbsp;Обычно
										</div>
										<div class="but black w100 text-center" id="pr2" title="Важно" onClick="setPS('priority','2')">
											<i class="icon-up-big"></i>&nbsp;Важно
										</div>

									</div>

								</div>

							</div>

						</div>

						<hr>

						<div class="flex-container box--child mt10">

							<div class="flex-string wp20 gray2 fs-12 pt7 right-text">Агенда:</div>
							<div class="flex-string wp80 pl10 relativ">
								<a href="javascript:void(0)" onclick="copydes();" title="скопировать из активности" class="blue pull-right mr30 mt5"><i class="icon-docs"></i></a>
								<textarea name="todo[des]" id="todo[des]" rows="4" class="required1 wp95 pr20" style="height:120px;" placeholder="Здесь можно указать детали напоминания - что именно надо сделать?"><?= $des ?></textarea>
								<div id="tagbox" class="gray1 fs-09 mt5" data-id="todo[des]" data-tip="tips"></div>
							</div>

						</div>

					</div>

				<?php } ?>

			</div>

		</DIV>

		<div class="row p5 mt5" data-id="deals">

			<span class="adddeal hand hidden" title="Привязать к <?= $lang[ 'face' ][ 'DealName' ][ '2' ] ?>" style="padding-left: 10px;"><i class="icon-plus-circled blue"></i> <?= $lang[ 'face' ][ 'DealName' ][ 0 ] ?></span>

			<div class="hidden1 deal div-info flex-container wp97">
				<span class="flex-string wp5 pt5 hidden-ipad"><i class="icon-briefcase-1 blue"></i></span>
				<span class="relativ cleared flex-string wp95">
					<INPUT name="did" type="hidden" id="did" value="<?= $did ?>">
					<INPUT name="dtitle" id="dtitle" type="text" placeholder="Выбор <?= $lang[ 'face' ][ 'DealName' ][ '1' ] ?>" value="<?= current_dogovor( $did ) ?>" class="wp100">
					<span class="idel clearinputs pr10" title="Очистить"><i class="icon-block-1 red"></i></span>
				</span>
			</div>

		</div>

		<hr>

		<div class="pull-aright button--pane">

			<A href="javascript:void(0)" onclick="$('#sForm').trigger('submit')" class="button">Сохранить</A>&nbsp;
			<A href="javascript:void(0)" onclick="DClose()" class="button">Отмена</A>

		</div>
	</FORM>
	<?php

	$hooks -> do_action( "history_form_after", $_REQUEST );

}
?>
<script type="text/javascript" src="/assets/js/smSelect.js"></script>
<script>

	var formType = 'history';

	includeJS('/assets/js/timepickeraddon/jquery-ui-timepicker-addon.js');

	var hh = $('#dialog_container').actual('height') * 0.85;
	var hh2 = hh - $('.zagolovok').actual('outerHeight') - $('.button--pane').actual('outerHeight') - 70;
	var hh3 = ($('div[data-id="deals"]').is('div')) ? $('div[data-id="deals"]').actual('outerHeight') : 0;

	var action = $('#action').val();
	var origDateTime = $('#todo\\[datumtime\\]').val();
	var origTip = $('#todo\\[tip\\] option:selected').text();

	if (!isMobile) {

		$('#tip').smSelect({
			text: "",
			width: "p97",
			icon: "",
			class: "p51 like-input",
			fly : true,
			id: "tip"
		});

		$('#todo\\[tip\\]').smSelect({
			text: "",
			width: "p95",
			height: "300px",
			icon: "",
			class: "p51 like-input",
			fly : true,
			id: "tips"
		});

		if ($(window).width() > 990) $('#dialog').css({'width': '800px'});
		else $('#dialog').css('width', '90vw');

		$('#formtabs').css('max-height', hh2);
		$(".multiselect").multiselect({sortable: true, searchable: true});

	}
	else {

		var h2 = $(window).height() - $('.zagolovok').actual('outerHeight') - $('.button--pane').actual('outerHeight') - hh3 - 40;

		$('#formtabs').css({'max-height': h2 + 'px', 'height': h2 + 'px'});
		$(".multiselect").addClass('wp100 h0');

	}

	var did = parseInt($('#did').val());
	var cid = parseInt($('#cid').val());
	var clid = parseInt($('#clid').val());
	var tip = $('#tip option:selected').val();

	$("#client").unautocomplete();
	$("#dtitle").unautocomplete();

	$(function () {

		getDateTasksNew('todo\\[datumtime\\]');

		if (did > 0) {

			$('.adddeal').hide();
			$('.deal').removeClass('hidden');

		}
		if (cid > 0) {

			$('#filelist').load('/content/card/fileview.php?cid=' + cid);

		}

		$('#des').autoHeight(250).focus();

		$("#client").autocomplete('/content/helpers/client.helpers.php?action=clientlist', {
			autofill: false,
			minChars: 2,
			cacheLength: 20,
			max: 20,
			selectFirst: false,
			multiple: false,
			delay: 500,
			matchSubset: 1,
			formatItem: function (data, j, n, value) {
				return '<div onclick="selItem(\'client\',\'' + data[1] + '\')">' + data[0] + '&nbsp;[<span class="red">' + data[2] + '</span>]</div>';
			},
			formatResult: function (data) {
				return data[0];
			}
		})
			.result(function (value, data) {

				selItem('client', data[1]);
				clid = data[1];

				$("#dtitle")
					.unautocomplete()
					.autocomplete("/content/helpers/deal.helpers.php?action=doglist&clid=" + clid, {
						autoFill: true,
						minChars: 2,
						cacheLength: 40,
						max: 30,
						selectFirst: false,
						multiple: false,
						delay: 500,
						matchSubset: 1,
						formatItem: function (data, i, n, value) {
							return '<div id="selitemid-' + data[1] + '" data-clid="' + data[1] + '">' + data[0] + '&nbsp;<span class="pull-aright">[<span class="broun">' + data[5] + '</span>]</span><div class="blue smalltext">' + data[3] + '</div></div>';
						},
						formatResult: function (data) {
							return data[0];
						}
					});

			});

		$("#rol").autocomplete("/content/helpers/person.helpers.php?action=get.role", {
			autoFill: true,
			minChars: 3,
			cacheLength: 30,
			max: 30,
			selectFirst: true,
			multiple: true,
			multipleSeparator: "; ",
			delay: 500
		});
		$("#ptitle").autocomplete("/content/helpers/person.helpers.php?action=get.status", {
			autofill: true,
			minChars: 3,
			cacheLength: 1,
			maxItemsToShow: 20,
			selectFirst: false,
			multiple: false,
			delay: 500,
			matchSubset: 1
		});

		$("#todo\\[theme\\]").autocomplete("/content/core/core.tasks.php?action=theme", {
			autoFill: false,
			minChars: 0,
			cacheLength: 1,
			max: 100,
			selectFirst: false,
			multiple: false,
			delay: 500,
			matchSubset: 3,
			matchContains: true
		});

		$("#dtitle").autocomplete("/content/helpers/deal.helpers.php?action=doglist&clid=" + clid, {
			autoFill: true,
			minChars: 2,
			cacheLength: 40,
			max: 30,
			selectFirst: false,
			multiple: false,
			delay: 500,
			matchSubset: 1,
			formatItem: function (data, i, n, value) {
				return '<div id="selitemid-' + data[1] + '" data-clid="' + data[1] + '">' + data[0] + '&nbsp;<span class="pull-aright">[<span class="broun">' + data[5] + '</span>]</span><div class="blue smalltext">' + data[3] + '</div></div>';
			},
			formatResult: function (data) {
				return data[0];
			}
		})
			.result(function (value, data) {

				$('#did').val(data[1]);
				$('#clid').val(data[2]);
				$('#client').val(data[3]);

				selItem("client", data[2], "");

			});

		//$('#kol').setMask({mask: '<?=$format_dogs?>', type: 'reverse'});

		if (!isMobile) {

			$("#datum_task").datepicker({
				dateFormat: 'yy-mm-dd',
				firstDay: 1,
				dayNamesMin: ['Вс', 'Пн', 'Вт', 'Ср', 'Чт', 'Пт', 'Сб'],
				monthNamesShort: ['Январь', 'Февраль', 'Март', 'Апрель', 'Май', 'Июнь', 'Июль', 'Август', 'Сентябрь', 'Октябрь', 'Ноябрь', 'Декабрь'],
				changeMonth: true,
				changeYear: true,
				yearRange: "1940:2020",
				minDate: new Date(1940, 1 - 1, 1)
			});
			$('#totime_task').ptTimeSelect();

			$('.inputdatetime').each(function () {

				var date = new Date();
				var id = $(this).attr('id');

				$(this).datetimepicker({
					timeInput: false,
					timeFormat: 'HH:mm',
					oneLine: false,
					showSecond: false,
					showMillisec: false,
					showButtonPanel: true,
					timeOnlyTitle: 'Выберите время',
					timeText: 'Время',
					hourText: 'Часы',
					minuteText: 'Минуты',
					secondText: 'Секунды',
					millisecText: 'Миллисекунды',
					timezoneText: 'Часовой пояс',
					currentText: 'Текущее',
					stepMinute: 1,
					closeText: '<i class="icon-ok-circled"></i>',
					dateFormat: 'yy-mm-dd',
					firstDay: 1,
					dayNamesMin: ['Вс', 'Пн', 'Вт', 'Ср', 'Чт', 'Пт', 'Сб'],
					monthNamesShort: ['Январь', 'Февраль', 'Март', 'Апрель', 'Май', 'Июнь', 'Июль', 'Август', 'Сентябрь', 'Октябрь', 'Ноябрь', 'Декабрь'],
					changeMonth: true,
					changeYear: true,
					yearRange: (date.getFullYear() - 5) + ':' + (date.getFullYear() + 2),
					minDate: (id !== 'datum') ? new Date(date.getFullYear(), date.getMonth(), date.getDate(), (date.getHours() - 1), date.getMinutes()) : ''
				});

			});

		}

		$('#dialog').center();

		ShowModal.fire({
			etype: 'historyForm',
			action: action
		});

	});

	/**
	 * Управление тэгами
	 */
	$('select[data-change="activities"]').each(function () {

		var $el = $(this).data('id');
		$('#tagbox[data-id="' + $el + '"]').empty().load('/content/core/core.tasks.php?action=itags&tip=' + urlEncodeData($('option:selected', this).val()));

	});
	$('.ydropDown[data-change="activities"]').each(function () {

		var $el = $(this).data('selected');
		var $tip = $(this).data('id');
		$('#tagbox[data-tip="' + $tip + '"]').empty().load('/content/core/core.tasks.php?action=itags&tip=' + urlEncodeData( $el ));

	});

	$(document).on('change', 'select[data-change="activities"]', function () {

		var $el = $(this).data('id');
		$('#tagbox[data-id="' + $el + '"]').empty().load('/content/core/core.tasks.php?action=itags&tip=' + urlEncodeData($('option:selected', this).val()));

	});

	$(document).off('change', 'input[data-change="activities"]');
	$(document).on('change', 'input[data-change="activities"]', function () {

		var $el = $(this).data('id');
		var $tip = $(this).val();

		$('#tagbox[data-tip="' + $el + '"]').empty().load('/content/core/core.tasks.php?action=itags&tip=' + urlEncodeData( $tip ));

	});

	$(document).off('click', '.tags');
	$(document).on('click', '.tags', function () {

		var $tag = $(this).text();
		var $el = $(this).closest('#tagbox').data('id');

		insTextAtCursor($el, $tag + '; ');

	});

	$('#sForm').ajaxForm({
		beforeSubmit: function () {

			if ($('#todo\\[theme\\]').val() == '')
				$('#todo\\[theme\\]').removeClass('required');

			var $out = $('#message');
			var em = checkRequired();

			if (em === false) return false;

			$('#dialog').css('display', 'none');
			$('#dialog_container').css('display', 'none');

			$out.empty().css('display', 'block').append('<div id=loader><img src=/assets/images/loader.gif> Загрузка данных. Пожалуйста подождите...</div>');

			return true;

		},
		success: function (data) {

			if (isCard) {
				cardloadHist();
				$cardsf.getTasks();
			}
			else if (typeof configpage === 'function') configpage();

			$('#message').fadeTo(1, 1).css('display', 'block').html(data);
			setTimeout(function () {
				$('#message').fadeTo(1000, 0);
			}, 20000);

		}
	});

	function checkTask() {

		if (action === "add" && $('#todo\\[theme\\]').val() !== '') {

			if (origDateTime === $('#todo\\[datumtime\\]').val() || origTip === $('#todo\\[tip\\] option:selected').text()) {

				Swal.fire(
					{
						title: 'Вы ничего не забыли?',
						text: "Не изменена Дата и/или Тип напоминания!",
						type: 'question',
						showCancelButton: true,
						confirmButtonText: 'Продолжить',
						cancelButtonText: 'Упс, реально забыл',
						customClass: {
							confirmButton: 'button greenbtn',
							cancelButton: 'button redbtn'
						},
					}
				).then((result) => {

					if (result.value)
						$('#sForm').trigger('submit');

				});

			}
			else $('#sForm').trigger('submit');

		}
		else $('#sForm').trigger('submit');

	}

	function selItem(tip, id, title) {

		if (tip === 'client') {

			$("#clid").val(id);

			var url = '/content/helpers/client.helpers.php?action=personlist&clid=' + id;
			$.getJSON(url, function (data) {

				var plist = '';
				var s = '';

				var selectedPersons = $('#pid\\[\\]').val() || [];

				for (var i in data) {

					//подгружаем список контактов клиента в select
					if (in_array(data[i].pid, selectedPersons)) s = 'selected';
					else s = '';

					plist = plist + '<option value="' + data[i].pid + '" ' + s + '>' + data[i].person + '</option>';

				}

				$('#pid\\[\\]').empty().append(plist).multiselect('destroy').multiselect({
					sortable: true,
					searchable: true
				});

			})
				.done(function () {

					if (!isMobile) $(".multiselect").multiselect('refresh');

				});
		}
		if (tip === 'person') {

			$("#pid_list").append('<div class="pid_box" id="person_' + id + '" title="' + title + '"><INPUT type="hidden" name="pid[]" id="pid[]" value="' + id + '"><div class="el"><div class="del" onclick="delItem(\'' + id + '\')"><i class="icon-cancel-circled-1"></i></div>' + title + '</div></div>');

		}

	}

	function copydes() {

		var tt = $('#des').val();
		$('#todo\\[des\\]').text(tt);

	}

	function gettags(selector) {

		if (!selector) selector = 'tiphist';
		var tip = urlEncodeData($('#' + selector + ' option:selected').val());

		$('#tagbox').load('/content/core/core.tasks.php?action=tags&tip=' + tip);

	}

	function tagit(id) {

		var html = $('#tag_' + id).html();
		insTextAtCursor('des', html + '; ');

	}

	function addfile() {

		var kol = $('.filebox').size();
		var i = kol + 1;
		var htmltr = '<div id="file-' + i + '" class="filebox"><input name="file[]" type="file" class="file" id="file[]" onchange="addfile();"><div class="delfilebox hand" onclick="deleteFilebox(\'file-' + i + '\')" title="Очистить"><i class="icon-cancel-circled red"></i></div></div>';

		$('#uploads').append(htmltr);
		$('#dialog').center();

	}

	/**
	 * @deprecated
	 */
	function addItem() {

		var pid = $('input[name=lid]:checked').val();
		var title = $('#txt' + pid).data('person');
		var client = $('#txt' + pid).data('client');

		$('#orgspisok').remove();

		if (pid > 0) {

			var html = '<div class="pid_box" data-id="person_' + pid + '" title="' + title + ' [' + client + ']" style="max-height:100px; overflow:auto;"><INPUT type="hidden" name="pid[]" id="pid[]" value="' + pid + '"><div class="el transparent"><div class="del" onclick="delItem(\'person_' + pid + '\')"><i class="icon-cancel-circled red"></i></div>' + title + '</div></div>';
			$('#pid_list').append(html);

		}

	}

	function delItem(id) {

		$('.pid_box[data-id="' + id + '"]').remove();

	}

</script>