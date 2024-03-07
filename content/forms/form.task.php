<?php
/* ============================ */
/*         SalesMan CRM         */
/* ============================ */
/* (C) 2018 Vladislav Andreev   */
/*       SalesMan Project       */
/*        www.isaler.ru         */
/*        ver. 2018.x           */

/* ============================ */

use Salesman\Elements;
use Salesman\Todo;

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

$action = $_REQUEST['action'];
$datum  = $_REQUEST['datum'] != 'undefined' ? $_REQUEST['datum'] : '';

$clid = (int)$_REQUEST['clid'];
$pid  = (int)$_REQUEST['pid'];
$did  = (int)$_REQUEST['did'];
$tid  = (int)$_REQUEST['tid'];

$sort = get_people( $iduser1 );

//настройки пользователя
$ress         = $db -> getOne( "select usersettings from ".$sqlname."user where iduser='".$iduser1."' and identity = '$identity'" );
$usersettings = json_decode( $ress, true );

//доп.напстройки
$customSettings = customSettings( 'settingsMore' );
$timecheck      = ($customSettings['timecheck'] == 'yes') ? 'true' : 'false';

$thistime = date( 'H:00', mktime( date( 'H' ) + 3, date( 'i' ), date( 's' ), date( 'm' ), date( 'd' ), date( 'Y' ) ) + ($tzone) * 3600 );

$y  = date( 'y' );
$m  = date( 'm' );
$nd = date( 'd' );
$st = mktime( 0, 0, 0, $m + 1, 0, $y ); //сформировали дату для дальнейшей обработки - первый день месяца $m года $y
$dd = date( "t", mktime( date( 'H' ), date( 'i' ), date( 's' ), date( 'm' ) + 1, date( 'd' ), date( 'Y' ) ) + $tm * 3600 ); //получили Стоимость дней в месяце
$d1 = date( "Y-m-d", mktime( date( 'H' ), date( 'i' ), date( 's' ), date( 'm' ), '01', date( 'Y' ) ) + $tm * 3600 );
$d2 = date( "Y-m-d", mktime( date( 'H' ), date( 'i' ), date( 's' ), date( 'm' ), $dd, date( 'Y' ) ) + $tm * 3600 );

// перенаправляем на новую форму
if ( $action == 'add' )
	$action = 'edit';

if ( $action == "edit" ) {

	if ( $tid > 0 ) {

		$task = Todo ::info( $tid );

		$iduser = $task['iduser'];

		$tids = yimplode( ",", $task['child'] );

		//print_r($task);

	}
	else {

		$messageid    = (int)$_REQUEST['messageid'];
		$task['deal'] = '';

		/*if( $pid > 0 ){
			$task['pids'][] = $pid;
		}*/

		if ( $messageid > 0 ) {

			$des         = $db -> getOne( "SELECT content FROM ".$sqlname."ymail_messages WHERE id = '$messageid' and identity = '$identity'" );
			$task['des'] = str_replace( "&nbsp;", "", html2text( htmlspecialchars_decode( $des ) ) );

			$result         = $db -> getRow( "SELECT * FROM ".$sqlname."ymail_messagesrec WHERE mid = '$messageid' and identity = '$identity'" );
			$task['clid']   = (int)$result['clid'];
			$task['pids'][] = (int)$result['pid'];


		}

		$task['datum']  = current_datum();
		$task['totime'] = $thistime;//strftime('%H:%n', mktime(date('H') + 1, date('i'), date('s'), date('m'), date('d'), date('Y')) + $tzone * 3600);

		if ( $_REQUEST['date'] ) {

			$t = yexplode( " ", (string)$_REQUEST['date'], 1 );

			$task['datum']  = datetime2date( $_REQUEST['date'] );
			$task['totime'] = getTime( (string)$t );

		}
		elseif ( $_REQUEST['datum'] ) {

			if ( (int)date( 'H' ) > 20 ) {
				$task['totime'] = "09:00";
			}

			if ( (int)date( 'H' ) < 9 ) {
				$task['totime'] = "09:00";
			}

			$task['datum'] = $_REQUEST['datum'] != 'undefined' ? $_REQUEST['datum'] : current_datum();

		}
		elseif ( (int)date( 'H' ) > 20 ) {

			$task['datum']  = current_datum( -1 );
			$task['totime'] = "09:00";

		}

		if ( $did > 0 ) {

			$task['did']  = $did;
			$task['deal'] = getDogData( $did, 'title' );
			$task['pid']  = yexplode( ";", getDogData( $did, 'pid_list' ));

			$_REQUEST['tip'] = 'Задача';

		}

		if ( $clid > 0 ) {

			$task['clid']   = $clid;
			$task['client'] = getClientData( $clid, 'title' );

			if ( empty( $task['pid'] ) && $pid == 0 ) {

				$p = getClientData( $clid, 'pid' );

				if ( (int)$p > 0 ) {
					$task['pid'][] = $p;
				}

			}

		}

		if ( $pid > 0 && empty( $task['pid'] ) ) {
			$task['pid'][] = $pid;
		}

		$tip         = ($_REQUEST['tip'] > 0) ? $_REQUEST['tip'] : $GLOBALS['actDefault'];
		$task['tip'] = $db -> getOne( "SELECT title FROM ".$sqlname."activities WHERE id = '$tip' and identity = '$identity'" );

		$task['alert']    = ($usersettings['taskAlarm'] == 'yes') ? "yes" : "no";
		$task['priority'] = 0;
		$task['speed']    = 0;

		$iduser = $iduser1;

	}
	?>
	<DIV class="zagolovok">Добавить/Изменить напоминание</DIV>
	<?php
	$tcount = getOldTaskCount( (int)$iduser1 );
	if ( (int)$otherSettings['taskControl'] > 0 && (int)$tcount >= (int)$otherSettings['taskControl'] && (int)$tid == 0 ) {

		print '<div class="warning"><b class="red">Включен режим контроля выполненения дел.</b><br>У вас '.$tcount.' не выполненных дел - вы не можете создавать новые Напоминания, добавлять Клиентов и Контакты, пока не закроете старые.</div>';
		exit();

	}

	// загружаем фильтры
	$filter = $hooks -> apply_filters( "task_form_userfilter", $task );

	//print_r($task[ 'users' ]);
	//print_r($hooks);
	//print_r($filter);

	// только если есть изменения
	if ( !empty( $filter['users'] ) && !empty( array_diff( $task, $filter ) ) ) {

		// список сотрудников
		$users = $db -> getAll( "SELECT iduser, title FROM ".$sqlname."user WHERE ".(!empty( $filter['users'] ) ? "iduser IN (".yimplode( ",", $filter['users'] ).") AND" : "secrty = 'yes' AND")." identity = '$identity' ORDER by title" );

		// выбранные сотрудники
		$task['users'] = $filter['selected'];

	}
	else {

		$users = $db -> getAll( "SELECT iduser, title FROM ".$sqlname."user WHERE secrty = 'yes' AND identity = '$identity' ORDER by title" );

	}

	?>
	<FORM action="/content/core/core.tasks.php" method="post" enctype="multipart/form-data" name="taskform" id="taskform">
		<INPUT name="tid" type="hidden" id="tid" value="<?= $tid ?>">
		<INPUT type="hidden" name="action" value="edit" id="action">
		<INPUT type="hidden" name="resdiv" value="task" id="resdiv">
		<INPUT name="tids" type="hidden" id="tids" value="<?= $tids ?>">
		<INPUT name="autor" type="hidden" id="autor" value="<?= $task['autor'] ?>">

		<div id="flyitbox"></div>
		<div id="formtabs" style="max-height: 80vh; overflow-y: auto; overflow-x: hidden" class="p5">

			<?php
			$hooks -> do_action( "task_form_before", $_REQUEST );
			?>

			<div class="flex-container box--child mt10" data-block="theme">

				<div class="flex-string wp20 gray2 fs-12 pt7 right-text">Тема:</div>
				<div class="flex-string wp80 pl10">
					<INPUT name="title" id="title" type="text" value="<?= $task['title'] ?>" placeholder="Укажите тему напоминания" class="required wp97">
					<div class="em gray2 fs-09">Например: <b>Договориться о встрече</b></div>
				</div>

			</div>

			<hr>

			<div class="flex-container box--child mt10" data-block="datumtime">

				<div class="flex-string wp20 gray2 fs-12 pt7 right-text">К исполнению:</div>
				<div class="flex-string wp80 pl10 relativ">

					<input name="datumtime" type="text" class="inputdatetime required" id="datumtime" value="<?= $task['datum']." ".$task['totime'] ?>" onclick="$('.datumTasksView').empty().hide()" onchange="getDateTasksNew('datumtime')" autocomplete="off">

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

						<label for="day" class="switch">
							<input type="checkbox" name="day" id="day" value="yes" <?php if ( $task['day'] == 'yes' )
								print "checked"; ?>>
							<span class="slider empty"></span>
						</label>
						<label for="day" class="inline">&nbsp;Весь день&nbsp;<i class="icon-info-circled blue" title="Включите, чтобы напоминание не было привязано к времени"></i></label>

					</div>

					<div class="mb10 pl10">

						<label for="readonly" class="switch">
							<input type="checkbox" name="readonly" id="readonly" value="yes" <?php if ( $task['readonly'] == 'yes' )
								print "checked"; ?>>
							<span class="slider empty"></span>
						</label>
						<label for="readonly" class="inline">&nbsp;Только чтение&nbsp;<i class="icon-info-circled blue" title="Включите, чтобы не ставить отметку о выполнении"></i></label>

					</div>

					<div class="mb10 pl10">

						<label for="alert" class="switch">
							<input type="checkbox" name="alert" id="alert" value="yes" <?php if ( $task['alert'] != 'no' )
								print "checked"; ?>>
							<span class="slider empty"></span>
						</label>
						<label for="alert" class="inline">&nbsp;Напоминать&nbsp;<i class="icon-info-circled blue" title="Если включено, то будет показано всплывающее окно"></i></label>

					</div>

				</div>

			</div>

			<div class="flex-container box--child mt10" data-block="tip">

				<div class="flex-string wp20 gray2 fs-12 pt7 right-text">Тип напоминания:</div>
				<div class="flex-string wp80 pl10">

					<select name="tip" id="tip" class="wp97 required" data-change="activities" data-id="des">
						<?php
						$res = $db -> getAll( "SELECT * FROM ".$sqlname."activities WHERE filter IN ('all','task') and identity = '$identity' ORDER by aorder" );
						foreach ( $res as $data ) {

							print '<option value="'.$data['title'].'" '.($data['title'] == $task['tip'] ? "selected" : "").' style="color:'.$data['color'].'" data-color="'.$data['color'].'" data-icon="'.get_ticon( $data['title'], '', true ).'">'.$data['title'].'</option>';

						}
						?>
					</select>

				</div>

			</div>

			<div class="flex-container box--child mt10" data-block="speed">

				<div class="flex-string wp20 gray2 fs-12 pt7 right-text">Срочность:</div>
				<div class="flex-string wp80 pl10">

					<div class="like-input wp97">

						<div id="psdiv" class="speed">

							<input type="hidden" id="speed" name="speed" value='<?= $task['speed'] ?>'>
							<div class="but black <?php if ( $task['speed'] == "1" )
								print "active" ?> w100 text-center" id="sp1" title="Не срочно" onClick="setPS('speed','1')">
								<i class="icon-down-big"></i>&nbsp;Не срочно
							</div>
							<div class="but black <?php if ( $task['speed'] == "0" )
								print "active" ?> w100 text-center" id="sp0" title="Обычно" onClick="setPS('speed','0')">
								<i class="icon-check-empty"></i>&nbsp;Обычно
							</div>
							<div class="but black <?php if ( $task['speed'] == "2" )
								print "active" ?> w100 text-center" id="sp2" title="Срочно" onClick="setPS('speed','2')">
								<i class="icon-up-big"></i>&nbsp;Срочно
							</div>

						</div>

					</div>

				</div>

			</div>

			<div class="flex-container box--child mt10" data-block="priority">

				<div class="flex-string wp20 gray2 fs-12 pt7 right-text">Важность:</div>
				<div class="flex-string wp80 pl10">

					<div class="like-input wp97">

						<div id="psdiv" class="priority">

							<input type="hidden" id="priority" name="priority" value='<?= $task['priority'] ?>'>
							<div class="but black <?php if ( $task['priority'] == "1" )
								print "active" ?> w100 text-center" id="pr1" title="Не важно" onClick="setPS('priority','1')">
								<i class="icon-down-big"></i>&nbsp;Не важно
							</div>
							<div class="but black <?php if ( $task['priority'] == "0" )
								print "active" ?> w100 text-center" id="pr0" title="Обычно" onClick="setPS('priority','0')">
								<i class="icon-check-empty"></i>&nbsp;Обычно
							</div>
							<div class="but black <?php if ( $task['priority'] == "2" )
								print "active" ?> w100  text-center" id="pr2" title="Важно" onClick="setPS('priority','2')">
								<i class="icon-up-big"></i>&nbsp;Важно
							</div>

						</div>

					</div>

				</div>

			</div>

			<hr>

			<div class="flex-container box--child mt10" data-block="agenda">

				<div class="flex-string wp20 gray2 fs-12 pt7 right-text">Агенда:</div>
				<div class="flex-string wp80 pl10">
					<textarea name="des" rows="4" class="required1 wp97" id="des" style="height:120px;" placeholder="Здесь можно указать детали напоминания - что именно надо сделать?"><?= $task['des'] ?></textarea>
					<div id="tagbox" class="gray1 fs-09 mt5" data-id="des" data-tip="tip">
						<br>Начните с выбора <strong class="errorfont">типа активности</strong>
					</div>
				</div>

			</div>

			<div data-block="client">

				<div class="flex-container box--child mt10" data-block="client-divider">

					<div class="flex-string wp100">
						<div id="divider" class="div-center"><b>Клиент, Контакты</b></div>
					</div>

				</div>

				<div class="flex-container box--child mt10">

					<div class="flex-string wp20 gray2 fs-12 pt7 right-text">Выбор Клиента:</div>
					<div class="flex-string wp80 pl10">
						<INPUT name="client" type="text" class="wp97" id="client" value="<?= $task['client'] ?>" placeholder="Начните вводить название. Например: Сэйлзмэн"><INPUT type="hidden" id="clid" name="clid" value="<?= $task['clid'] ?>">
					</div>

				</div>

				<div class="flex-container box--child mt10">

					<div class="flex-string wp20 gray2 fs-12 pt7 right-text">Добавить Контакт:</div>
					<div class="flex-string wp80 pl10" id="prsn">

						<div class="pid">
							<input type="hidden" id="pidd" name="pidd" value="">
							<INPUT id="lst_spisokp" placeholder="Нажмите сюда для выбора" type="text" class="wp97" value="<?= $task['person'] ?>" readonly onclick="get_orgspisok('lst_spisokp','prsn','content/helpers/person.helpers.php?action=get_personselector&clid='+$('#clid').val()+'&put=yes','pidd','yes')">

						</div>

					</div>

				</div>

				<div class="flex-container box--child mt10">

					<div class="flex-string wp20 gray2 fs-12 pt7 right-text"></div>
					<div class="flex-string wp80 pl10">

						<div id="pid_list" class="like-input p5 wp97 flex-container">
							<?php
							foreach ( $task['pid'] as $pid ) {

								if ( is_int( intval( $pid ) ) )
									print '
									<div class="infodiv h0 fs-10 flh-12" id="person_'.$pid.'" title="'.current_person( $pid ).'">
										<INPUT type="hidden" name="pid[]" id="pid[]" value="'.$pid.'">
										<div class="el">
											<div class="del"><i class="icon-cancel-circled"></i></div>'.current_person( $pid ).'
										</div>
									</div>
								';

							}
							?>
							&nbsp;
						</div>
					</div>

				</div>

			</div>

			<div class="flex-container box--child" data-block="users">

				<div class="flex-string wp100">
					<div id="divider" class="div-center"><b>Исполнители</b></div>
				</div>

				<div class="flex-string wp100">

					<SELECT name="users[]" id="users[]" multiple="multiple" class="multiselect" data-id="userSelect">
						<?php
						foreach ( $users as $user ) {

							print '<OPTION value="'.$user['iduser'].'" '.(in_array( $user['iduser'], (array)$task['users'] ) || $user['iduser'] == $iduser ? "selected" : "").'>'.$user['title'].'</OPTION>';

						}
						?>
					</SELECT>

				</div>

			</div>

		</div>

		<div class="row" data-id="deals" data-block="deals">

			<span class="adddeal hand hidden" title="Привязать к <?= $lang['face']['DealName']['1'] ?>"><i class="icon-plus-circled blue"></i> <?= $lang['face']['DealName']['0'] ?></span>

			<div class="hidden1 deal div-info flex-container wp97">

				<span class="flex-string wp5 pt5 hidden-iphone"><i class="icon-briefcase-1 blue"></i></span>
				<span class="relativ cleared flex-string wp95">
					<INPUT name="did" type="hidden" id="did" value="<?= $task['did'] ?>">
					<INPUT name="dtitle" id="dtitle" type="text" placeholder="Выбор <?= $lang['face']['DealName']['1'] ?>" value="<?= $task['deal'] ?>" class="wp100">
					<span class="idel clearinputs pr10" title="Очистить"><i class="icon-block-1 red"></i></span>
				</span>

			</div>

		</div>

		<hr>

		<div class="button--pane">

			<div class="pull-aright">

				<A href="javascript:void(0)" id="sender" onclick="$('#taskform').trigger('submit')" class="button">Сохранить</A>&nbsp;
				<A href="javascript:void(0)" onclick="DClose()" class="button">Отмена</A>

			</div>

		</div>

	</FORM>
	<?php

	$hooks -> do_action( "task_form_after", $task );

}

if ( $action == "doit" ) {

	$old = $_REQUEST['old'];

	$result   = $db -> getRow( "SELECT * FROM ".$sqlname."tasks WHERE tid = '$tid' and identity = '$identity'" );
	$datum    = $result["datum"];
	$totime   = substr( $result["totime"], 0, 5 );
	$title    = $result["title"];
	$des      = $result["des"];
	$tip      = $result["tip"];
	$clid     = $result["clid"];
	$did      = $result["did"];
	$pids     = yexplode( ";", (string)$result["pid"] );
	$iduser   = $result["iduser"];
	$client   = current_client( $clid );
	$priority = $result["priority"];
	$speed    = $result["speed"];
	$readonly = $result["readonly"];
	$autor    = $result["autor"];
	$alert    = $result["alert"];

	$users = [];
	$res   = $db -> getAll( "SELECT iduser, tid FROM ".$sqlname."tasks WHERE maintid='".$tid."' and identity = '$identity'" );
	foreach ( $res as $data ) {

		$users[] = $data['iduser'];//выбранные сотрудники
		$tids[]  = $data['tid'];//связанные id напоминаний ? зачем, пока не знаю

	}

	$now = date( 'H:i', mktime( date( 'H' ), date( 'i' ), date( 's' ), date( 'm' ), date( 'd' ), date( 'Y' ) ) + ($tzone) * 3600 );

	if ( date( 'H' ) > 20 )
		$thistime = current_datum( -1 )." 09:00";

	else
		$thistime = current_datum()." ".$thistime;

	?>
	<FORM action="/content/core/core.tasks.php" method="post" enctype="multipart/form-data" name="taskform" id="taskform">
		<INPUT name="action" id="action" type="hidden" value="doit">
		<INPUT name="tid" id="tid" type="hidden" value="<?= $tid ?>">
		<INPUT name="resdiv" id="resdiv" type="hidden" value="task">
		<INPUT name="did" id="did" type="hidden" value="<?= $did ?>">

		<DIV class="zagolovok">Результат выполнения</DIV>

		<?php
		$hooks -> do_action( "task_form_doit_before", $_REQUEST );
		?>

		<div id="flyitbox"></div>
		<div id="formtabs" style="overflow-y: auto; overflow-x: hidden" class="p5">

			<div class="togglerbox hand pt5 pb5" data-id="oldtask">

				Просмотр напоминания <i class="icon-angle-down blue" id="mapic" title="Детали. Показать/Скрыть"></i>

			</div>

			<div id="oldtask" class="hidden viewdiv"></div>

			<div id="rezt">

				<div id="tab-form-1">

					<div class="flex-container box--child mt10">

						<div class="flex-string wp100">
							<div id="divider" class="div-center"><b>Результат активности</b></div>
						</div>

					</div>

					<div class="flex-container box--child mt10" data-block="datumtime">

						<div class="flex-string wp20 gray2 fs-12 pt7 right-text">Дата:</div>
						<div class="flex-string wp80 pl10">
							<INPUT name="datumdo" type="text" class="required inputdatetime" id="datumdo" value="<?= current_datum().' '.$now ?>" readonly>
						</div>

					</div>

					<div class="flex-container box--child mt10" data-block="tip">

						<div class="flex-string wp20 gray2 fs-12 pt7 right-text">Тип:</div>
						<div class="flex-string wp80 pl10">
							<select name="oldtip" id="oldtip" class="required wp97" data-change="activities" data-id="rezultat">
								<?php
								$res = $db -> getAll( "SELECT id, title, color FROM ".$sqlname."activities WHERE filter IN ('all','activ') and identity = '$identity' ORDER by aorder" );
								foreach ( $res as $data ) {

									print '<option value="'.$data['title'].'" '.($data['title'] == $tip ? "selected" : "").' style="color:'.$data['color'].'" data-color="'.$data['color'].'" data-icon="'.get_ticon( $data['title'], '', true ).'">'.$data['title'].'</option>';

								}
								?>
							</select>
						</div>

					</div>

					<hr>

					<div class="flex-container box--child mt10" data-block="status">

						<div class="flex-string wp20 gray2 fs-12 pt7 right-text">Статус:</div>
						<div class="flex-string wp80 pl10">

							<div class="flex-container box--child wp97">

								<div class="flex-string p10 mr5 mb5 flx-basis-20 viewdiv bgwhite inset">

									<div class="radio">
										<label>
											<i class="icon-ok green pull-aright"></i>
											<input name="status" type="radio" id="status" value="1" checked>
											<span class="custom-radio success"><i class="icon-radio-check"></i></span>
											<span class="title">Успешно</span>
										</label>
									</div>

								</div>
								<div class="flex-string p10 mb5 flx-basis-20 viewdiv bgwhite inset">

									<div class="radio">
										<label>
											<i class="icon-block red pull-aright"></i>
											<input name="status" type="radio" id="status" value="2">
											<span class="custom-radio alert"><i class="icon-radio-check"></i></span>
											<span class="title">Не успешно</span>
										</label>
									</div>

								</div>

							</div>

						</div>

					</div>

					<div class="flex-container box--child mt10" data-block="rezult">

						<div class="flex-string wp20 gray2 fs-12 pt7 right-text">Результат:</div>
						<div class="flex-string wp80 pl10">

							<textarea name="rezultat" rows="4" class="required wp97" id="rezultat" style="height:120px;" placeholder="Напишите, какой результат достигнут. Для быстрых результатов используйте тэги ниже."></textarea>
							<div id="tagbox" class="gray1 fs-09 mt5" data-id="rezultat" data-tip="oldtip"></div>

						</div>

					</div>

				</div>
				<div id="tab-form-4" data-block="files">

					<div class="flex-container">

						<div class="flex-string wp100">
							<div id="divider" class="div-center"><b>Файлы</b></div>
						</div>

					</div>

					<?php
					include $rootpath."/content/ajax/check_disk.php";
					if ( $diskLimit > 0 ) {

						print '
						<div class="infodiv text-center">
							<b>Ипользование диска:</b> Лимит: <b>'.$diskUsage['total'].'</b> Мб, Занято: <b class="red">'.$diskUsage['current'].'</b> Mb ( <b>'.$diskUsage['percent'].'</b> % )
						</div>
						';

					}
					?>

					<?php
					if ( $diskLimit == 0 || $diskUsage['percent'] < 100 ) {
						print
							'<DIV id="uploads">
							<div id="file-1" class="filebox">
								<input name="file[]" type="file" class="file" id="file[]" onchange="addfile();">
								<div class="delfilebox hand" onclick="deleteFilebox(\'file-1\')" title="Очистить">
									<i class="icon-cancel-circled red"></i></div>
							</div>
						</DIV>';
					}
					else print '<div class="warning text-center"><b class="red">Превышен лимит использования диска</b></div>';
					?>

				</div>

			</div>

			<div id="adtask" style="display:none">

				<div class="flex-container box--child mt10">

					<div class="flex-string wp20 gray2 fs-12 pt7 right-text">Тема:</div>
					<div class="flex-string wp80 pl10">
						<INPUT name="title" id="title" type="text" value="<?= $title ?>" placeholder="Укажите тему напоминания" class="wp97">
						<div class="em gray2 fs-09">Например: <b>Договориться о встрече</b></div>
					</div>

				</div>

				<hr>

				<div class="flex-container box--child mt10">

					<div class="flex-string wp20 gray2 fs-12 pt7 right-text">К исполнению:</div>
					<div class="flex-string wp80 pl10 relativ">

						<input name="datumtime" type="text" class="inputdatetime" id="datumtime" value="<?= $thistime ?>" onclick="$('.datumTasksView').empty().hide()" onchange="getDateTasksNew('datumtime')" autocomplete="off">

						<div class="datumTasks hand tagsmenuToggler p10">
							Число дел: <span class="taskcount Bold">0</span>
							<div class="tagsmenu left hidden">
								<div class="blok"></div>
							</div>
						</div>
						<div class="datumTasksView" onblur="$('.datumTasksView').hide()"></div>

					</div>

				</div>

				<div class="flex-container box--child mt10 infodiv bgwhite">

					<div class="flex-string wp20 gray2 fs-12 pt7 right-text">Опции:</div>
					<div class="flex-string wp80 pt7 pl10 fs-11">

						<!--Пока не реализовано. Сложности с выводом таких напоминаний-->
						<div class="mb10 pl10">

							<label for="day" class="switch">
								<input type="checkbox" name="day" id="day" value="yes">
								<span class="slider empty"></span>
							</label>
							<span class="">&nbsp;Весь день&nbsp;<i class="icon-info-circled blue" title="Включите, чтобы напоминание не было привязано к времени"></i></span>

						</div>

						<div class="mb10 pl10">

							<label for="readonly" class="switch">
								<input type="checkbox" name="readonly" id="readonly" value="yes">
								<span class="slider empty"></span>
							</label>
							<span class="">&nbsp;Только чтение&nbsp;<i class="icon-info-circled blue" title="Включите, чтобы не ставить отметку о выполнении"></i></span>

						</div>

						<div class="mb10 pl10">

							<label for="alert" class="switch">
								<input type="checkbox" name="alert" id="alert" value="yes" <?php if ( $alert == 'no' || $usersettings['taskAlarm'] == 'yes' )
									print "checked"; ?>>
								<span class="slider empty"></span>
							</label>
							<span class="">&nbsp;Напоминать&nbsp;<i class="icon-info-circled blue" title="Если включено, то будет показано всплывающее окно"></i></span>

						</div>

					</div>

				</div>

				<div class="flex-container box--child mt10">

					<div class="flex-string wp20 gray2 fs-12 pt7 right-text">Тип напоминания:</div>
					<div class="flex-string wp80 pl10">

						<select name="tip" id="tip" class="wp97" data-change="activities" data-id="des">
							<?php
							$res = $db -> getAll( "SELECT * FROM ".$sqlname."activities WHERE filter IN ('all','task') and identity = '$identity' ORDER by aorder" );
							foreach ( $res as $data ) {

								print '<option value="'.$data['title'].'" '.($data['title'] == $tip ? "selected" : "").' style="color:'.$data['color'].'" data-color="'.$data['color'].'" data-icon="'.get_ticon( $data['title'], '', true ).'">'.$data['title'].'</option>';

							}
							?>
						</select>

					</div>

				</div>

				<div class="flex-container box--child mt10">

					<div class="flex-string wp20 gray2 fs-12 pt7 right-text">Срочность:</div>
					<div class="flex-string wp80 pl10">

						<div class="like-input wp97">

							<div id="psdiv" class="speed">

								<input type="hidden" id="speed" name="speed" value="0">
								<div class="but black <?php /*if ($speed=="1") print "active"*/ ?> w100 text-center" id="sp1" title="Не срочно" onClick="setPS('speed','1')">
									<i class="icon-down-big"></i>&nbsp;Не срочно
								</div>
								<div class="but black active <?php /*if ($speed=="0") print "active"*/ ?> w100 text-center" id="sp0" title="Обычно" onClick="setPS('speed','0')">
									<i class="icon-check-empty"></i>&nbsp;Обычно
								</div>
								<div class="but black <?php /*if ($speed=="2") print "active"*/ ?> w100 text-center" id="sp2" title="Срочно" onClick="setPS('speed','2')">
									<i class="icon-up-big"></i>&nbsp;Срочно
								</div>

							</div>

						</div>

					</div>

				</div>

				<div class="flex-container box--child mt10">

					<div class="flex-string wp20 gray2 fs-12 pt7 right-text">Важность:</div>
					<div class="flex-string wp80 pl10">

						<div class="like-input wp97">

							<div id="psdiv" class="priority">

								<input type="hidden" id="priority" name="priority" value="0">
								<div class="but black <?php /*if ($priority=="1") print "active"*/ ?> w100 text-center" id="pr1" title="Не важно" onClick="setPS('priority','1')">
									<i class="icon-down-big"></i>&nbsp;Не важно
								</div>
								<div class="but black active <?php /*if ($priority=="0") print "active"*/ ?> w100 text-center" id="pr0" title="Обычно" onClick="setPS('priority','0')">
									<i class="icon-check-empty"></i>&nbsp;Обычно
								</div>
								<div class="but black <?php /*if ($priority=="2") print "active"*/ ?> w100  text-center" id="pr2" title="Важно" onClick="setPS('priority','2')">
									<i class="icon-up-big"></i>&nbsp;Важно
								</div>

							</div>

						</div>

					</div>

				</div>

				<hr>

				<div class="flex-container box--child mt10">

					<div class="flex-string wp20 gray2 fs-12 pt7 right-text">Агенда:</div>
					<div class="flex-string wp80 pl10">
						<textarea name="des" rows="4" class="wp97" id="des" style="height:120px;" placeholder="Здесь можно указать детали напоминания - что именно надо сделать?"><?= $des ?></textarea>
						<div id="tagbox" class="gray1 fs-09 mt5" data-id="des" data-tip="tip">
							<br>Начните с выбора <strong class="errorfont">типа активности</strong>
						</div>
					</div>

				</div>

				<div class="flex-container box--child mt10">

					<div class="flex-string wp100">
						<div id="divider" class="div-center"><b>Клиент, Контакты</b></div>
					</div>

				</div>

				<div class="flex-container box--child mt10">

					<div class="flex-string wp20 gray2 fs-12 pt7 right-text">Выбор Клиента:</div>
					<div class="flex-string wp80 pl10">
						<INPUT name="client" type="text" class="wp97" id="client" value="<?= $client ?>" placeholder="Начните вводить название. Например: Сэйлзмэн"><INPUT type="hidden" id="clid" name="clid" value="<?= $clid ?>">
					</div>

				</div>

				<div class="flex-container box--child mt10">

					<div class="flex-string wp20 gray2 fs-12 pt7 right-text">Добавить Контакт:</div>
					<div class="flex-string wp80 pl10" id="prsn">

						<div class="pid">
							<input type="hidden" id="pidd" name="pidd" value="">
							<INPUT id="lst_spisokp" placeholder="Нажмите сюда для выбора" type="text" class="wp97" value="<?= $person ?>" readonly onclick="get_orgspisok('lst_spisokp','prsn','content/helpers/person.helpers.php?action=get_personselector&clid='+$('#clid').val()+'&put=yes','pidd','yes')">

						</div>
					</div>

				</div>

				<div class="flex-container box--child mt10">

					<div class="flex-string wp20 gray2 fs-12 pt7 right-text"></div>
					<div class="flex-string wp80 pl10">

						<div id="pid_list" class="like-input p5 wp97 flex-container">
							<?php
							foreach ( $pids as $pid ) {

								if ( is_int( (int)$pid ) )
									print '
								<div class="infodiv h0 fs-10 flh-12" id="person_'.$pid.'" title="'.current_person( $pid ).'">
									<INPUT type="hidden" name="pid[]" id="pid[]" value="'.$pid.'">
									<div class="el">
										<div class="del"><i class="icon-cancel-circled"></i></div>'.current_person( $pid ).'
									</div>
								</div>
							';

							}
							?>
							&nbsp;
						</div>
					</div>

				</div>

				<div class="flex-container box--child">

					<div class="flex-string wp100">
						<div id="divider" class="div-center"><b>Исполнители</b></div>
					</div>

					<div class="flex-string wp100">

						<SELECT name="users[]" id="users[]" multiple="multiple" class="multiselect">
							<?php
							$result = $db -> getAll( "SELECT iduser, title FROM ".$sqlname."user where secrty='yes' and identity = '$identity' ORDER by title" );
							foreach ( $result as $data ) {

								$ss = (in_array( $data['iduser'], (array)$users ) || $data['iduser'] == $iduser) ? $ss = " selected" : "";

								print '<OPTION value="'.$data['iduser'].'" '.$ss.'>'.$data['title'].'</OPTION>';

							}
							?>
						</SELECT>

					</div>

				</div>

			</div>

		</div>

		<div class="row" data-id="deals" data-block="deals">

			<span class="adddeal hand hidden" title="Привязать к <?= $lang['face']['DealName']['1'] ?>"><i class="icon-plus-circled blue"></i> <?= $lang['face']['DealName']['0'] ?></span>

			<div class="hidden1 deal div-info flex-container wp97">
				<span class="flex-string wp5 pt5"><i class="icon-briefcase-1 blue"></i></span>
				<span class="relativ cleared flex-string wp95">
					<INPUT name="did" type="hidden" id="did" value="<?= $did ?>">
					<INPUT name="dtitle" id="dtitle" type="text" placeholder="Выбор <?= $lang['face']['DealName']['1'] ?>" value="<?= current_dogovor( $did ) ?>" class="wp100">
					<span class="idel clearinputs pr10" title="Очистить"><i class="icon-block-1 red"></i></span>
				</span>
			</div>

		</div>

		<hr>

		<div class="button--pane">

			<div class="pt10 pull-left pl10" data-block="addnew">

				<div class="checkbox mt5">
					<label>
						<input name="redo" type="checkbox" id="redo" value="yes" onClick="reddo()">
						<span class="custom-checkbox"><i class="icon-ok"></i></span>
						&nbsp;Создать новое, на основе текущего
					</label>
				</div>

			</div>

			<div class="pull-aright">

				<A href="javascript:void(0)" id="sender" onclick="$('#taskform').trigger('submit')" class="button">Сохранить</A>&nbsp;
				<A href="javascript:void(0)" onclick="DClose()" class="button">Отмена</A>

			</div>

		</div>

	</FORM>
	<?php
	$hooks -> do_action( "task_form_doit_after", $_REQUEST );

}

if ( $action == "mass" ) {

	//print_r($_REQUEST);

	$ids = $_REQUEST['ids'];
	$kol = $_REQUEST['count'];
	?>
	<div class="zagolovok"><b>Групповое действие</b></div>
	<form action="/content/core/core.tasks.php" id="taskform" name="taskform" method="post" enctype="multipart/form-data">
		<input name="ids" id="ids" type="hidden" value="<?= $ids ?>">
		<input name="action" id="action" type="hidden" value="mass.edit">

		<DIV id="formtabs" class="box--child" style="overflow-y: auto; overflow-x:hidden; max-height:80vh;">

			<div class="infodiv mb10">
				<b class="red">Важная инфрмация:</b>
				<ul>
					<li>Отмена групповых действий не возможна</li>
					<li>Действия будут применены только для записей, к которым у вас есть доступ</li>
				</ul>
			</div>

			<div class="fmain1 box--child">

				<div id="divider">Опции</div>

				<div class="flex-container box--child mt10 mb10" id="datdiv">

					<div class="flex-string wp40 gray2 fs-12 pt7 right-text">Плановая дата:</div>
					<div class="flex-string wp60 pl10">

						<input name="datum" type="text" class="required inputdate w140" id="datum" placeholder="Дата реализации">

					</div>

				</div>

			</div>

		</div>

		<hr>

		<div class="button--pane text-right">

			<a href="javascript:void(0)" onclick="$('#taskform').trigger('submit')" class="button">Выполнить</a>&nbsp;
			<a href="javascript:void(0)" onclick="DClose()" class="button">Отмена</a>

		</div>

	</form>
	<?php
}

?>

<script src="/assets/js/smSelect.js"></script>
<script src="/assets/js/app.form.js"></script>
<script>

	var action = $('#action').val();
	var formType = 'task';

	if (parseInt($('#tid').val()) === 0)
		action = 'add';

	var origDateTime = $('#datumtime').val();
	var origTip = $('#tip option:selected').text();
	var $timecheck = <?=$timecheck?>;

	var hh = $('#dialog_container').actual('height') * 0.8;
	var hh3 = ($('div[data-id="deals"]').is('div')) ? $('div[data-id="deals"]').actual('outerHeight') : 0;
	var hh2 = hh - $('.zagolovok').actual('outerHeight') - $('.button--pane').actual('outerHeight') - hh3;

	if (!isMobile) {

		$('#tip').smSelect({
			id: "tip",
			text: "",
			width: "p97",
			height: "300px",
			fly: true,
			icon: "",
			class: "p51 like-input"
		});

		$('#oldtip').smSelect({
			id: "oldtip",
			text: "",
			width: "p97",
			height: "300px",
			fly: true,
			icon: "",
			class: "p51 like-input"
		});

		if (action !== "mass.edit") {

			if ($(window).width() > 990)
				$('#dialog').css({'width': '800px'});
			else
				$('#dialog').css('width', '90vw');

		}

		$('#formtabs').css({'max-height': hh2 + 'px'});
		$(".connected-list").css('height', "160px");
		$(".multiselect").multiselect({sortable: true, searchable: true});

	}
	else {

		var h2 = $(window).height() - $('.zagolovok').actual('outerHeight') - $('.button--pane').actual('outerHeight') - hh3 - 120;
		$('#formtabs').css({'max-height': h2 + 'px', 'height': h2 + 'px'});
		$(".multiselect").addClass('wp97 h0');

	}

	$(function () {

		$('#title').trigger('focus');

		if (action !== "mass.edit") {
			getDateTasksNew('datumtime');
		}

		$('#des').autoHeight(250);
		$('#rezultat').autoHeight(250);

		if (action === "doit") {

			var tid = $('#tid').val();
			var oldtip = $('#oldtip').val();
			var url = 'content/core/core.tasks.php?action=tags&tip=' + urlEncodeData(oldtip);

			$('#rezultat').trigger('focus');

			$('#tagbox').load(url);
			$('#oldtask').load('content/view/task.view.php?tid=' + tid + '&action=view&button=yes');

		}

		if (!isMobile) {

			$("#datum").datepicker({
				dateFormat: 'yy-mm-dd',
				firstDay: 1,
				dayNamesMin: ['Вс', 'Пн', 'Вт', 'Ср', 'Чт', 'Пт', 'Сб'],
				monthNamesShort: ['Январь', 'Февраль', 'Март', 'Апрель', 'Май', 'Июнь', 'Июль', 'Август', 'Сентябрь', 'Октябрь', 'Ноябрь', 'Декабрь'],
				changeMonth: true,
				changeYear: true,
				numberOfMonths: 2,
				showButtonPanel: true,
				currentText: 'Сегодня',
				closeText: 'Готово'
			});
			$('#totime').ptTimeSelect();

		}

		$("#title").autocomplete("content/core/core.tasks.php?action=theme", {
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

		$(document).off('click', '#dtitle');
		$(document).on('click', '#dtitle', function () {

			$("#dtitle").autocomplete("content/helpers/deal.helpers.php?action=doglist", {
				autofill: true,
				minChars: 2,
				cacheLength: 10,
				max: 30,
				selectFirst: false,
				multiple: false,
				delay: 500,
				matchSubset: 1,
				extraParams: {clid: $('#clid').val()},
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

					if (data[4] !== '')
						$("#pid_list").append('<div class="infodiv h0 fs-10 flh-12" title="' + data[6] + '"><INPUT type="hidden" name="pid[]" id="pid[]" value="' + data[4] + '"><div class="el"><div class="del"><i class="icon-cancel-circled"></i></div>' + data[6] + '</div></div>');

				});

		});

		$("#client").autocomplete('content/helpers/client.helpers.php?action=clientlist&strong=yes', {
			autofill: false,
			minChars: 2,
			cacheLength: 10,
			max: 30,
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
			});

		$("#person").autocomplete("content/helpers/client.helpers.php?action=personlist", {
			autofill: true,
			minChars: 2,
			cacheLength: 10,
			max: 20,
			selectFirst: false,
			multiple: false,
			delay: 500,
			matchSubset: 1,
			extraParams: {clid: $('#clid').val()},
			formatItem: function (data, i, n, value) {
				return '<div onclick="selItem(\'person\',\'' + data[1] + '\')">' + data[0] + '&nbsp;[<span class="red">' + data[2] + '</span>]</div>';
			},
			formatResult: function (data) {
				return data[0];
			}
		})
			.result(function (value, data) {
				selItem('person', data[1], data[0]);
				$("#person").val();
			});

		if (parseInt($('#did').val()) > 0) {

			$('.adddeal').hide();
			$('.deal').removeClass('hidden');

		}
		if (parseInt($('#clid').val()) > 0 && action === 'add') {
			selItem('client', $('#clid').val());
		}

		if (!isMobile) {
			$('.inputdatetime').each(function () {

				var date = new Date();
				var mindate = new Date(date.getFullYear(), date.getMonth(), date.getDate());

				if (action === 'doit')
					mindate = new Date(moment().subtract(2, 'week'));

				$(this).datetimepicker({
					timeInput: false,
					timeFormat: 'HH:mm',
					oneLine: true,
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
					stepMinute: 5,
					closeText: '<i class="icon-ok-circled"></i>',
					dateFormat: 'yy-mm-dd',
					firstDay: 1,
					dayNamesMin: ['Вс', 'Пн', 'Вт', 'Ср', 'Чт', 'Пт', 'Сб'],
					monthNamesShort: ['Январь', 'Февраль', 'Март', 'Апрель', 'Май', 'Июнь', 'Июль', 'Август', 'Сентябрь', 'Октябрь', 'Ноябрь', 'Декабрь'],
					changeMonth: true,
					changeYear: true,
					yearRange: date.getFullYear() + ':' + (date.getFullYear() + 5),
					minDate: mindate
				});

			});
		}

		$('#dialog').center();

		/**
		 * Управление тэгами
		 */
		setTimeout(function () {
			$('.ydropDown[data-change="activities"]').each(function () {

				var $el = $(this).data('selected');
				var $tip = $(this).data('id');

				$('#tagbox[data-tip="' + $tip + '"]').empty().load('content/core/core.tasks.php?action=itags&tip=' + urlEncodeData($el));

				//console.log($tip);
				//console.log($el);

			});
		}, 100);

		$('select[data-change="activities"]').each(function () {

			var $el = $(this).data('id');
			$('#tagbox[data-id="' + $el + '"]').empty().load('content/core/core.tasks.php?action=itags&tip=' + urlEncodeData($('option:selected', this).val()));

		});

		$(document).off('change', 'input[data-change="activities"]');
		$(document).on('change', 'input[data-change="activities"]', function () {

			var $el = $(this).data('id');
			var $tip = $(this).val();

			$('#tagbox[data-tip="' + $el + '"]').empty().load('content/core/core.tasks.php?action=itags&tip=' + urlEncodeData($tip));

		});

		$('#day').trigger('change');

		ShowModal.fire({
			etype: 'taskForm',
			action: action
		});

	});

	$('textarea').on('change', function () {

		$(this).text().replace(/((\r?\n)\s*\r?\n)+/g, '$2');

	});

	$('#taskform').ajaxForm({
		beforeSubmit: function () {

			var $out = $('#message');
			var em = checkRequired();

			if (!em)
				return false;

			if (action === "doit") {

				if ($('#rezultat').val() === '') {

					$('#rezultat').css({"color": "#222", "background": "#FFE3D7"});
					Swal.fire("Вы не внесли Результат выполнения", "", "warning");

					return false;

				}

			}


			$('#dialog').css('display', 'none');
			$('#dialog_container').css('display', 'none');

			$out.empty().fadeTo(1, 1).css('display', 'block').append('<div id="loader"><img src="/assets/images/loader.gif"> Загрузка данных. Пожалуйста подождите...</div>');

			return true;

		},
		success: function (data) {

			$('#dialog').css('display', 'none');
			$('#dialog_container').css('display', 'none');

			$('#message').fadeTo(1, 1).css('display', 'block').html(data);
			setTimeout(function () {
				$('#message').fadeTo(1000, 0);
			}, 20000);

			if ($display === 'calendar')
				configpage();

			else if (isCard)
				$cardsf.getTasks();

			else if ($display === 'desktop') {

				razdel($space);
				$desktop.calendar('');

			}
			else if ($display === 'todo') {

				configpage()

			}
			else if (swindow)
				getWeekCalendar();

			else
				changeMounth();

			talarm();

		}
	});

	function checkTask() {

		if (in_array(action, ["add", "doit"]) && $('#title').val() !== '' && $timecheck) {

			if (origDateTime === $('#datumtime').val() || origTip === $('#tip option:selected').text()) {

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

					if (result.value) {

						$('#taskform').trigger('submit');

					}

				});

			}
			else $('#taskform').trigger('submit');

		}
		else $('#taskform').trigger('submit');

	}

	$('#person').on('mouseleave', function () {
		$('#person').val('');
	});

	$('#day')
		.off('change')
		.on('change', function () {

			var state = $(this).prop('checked');
			var datum = $('#datumtime').val();
			var date = datum.split(" ");

			if (!state)
				$('#datumtime').val(datum);
			else
				$('#datumtime').val(date[0] + ' 00:00');

			//console.log(state);

		});

	$(document).off('change', 'select[data-change="activities"]');
	$(document).on('change', 'select[data-change="activities"]', function () {

		var $el = $(this).data('id');
		$('#tagbox[data-tip="' + $el + '"]').empty().load('content/core/core.tasks.php?action=itags&tip=' + urlEncodeData($('option:selected', this).val()));

	});

	/*
	$(document).off('select', '#oldtip');
	$(document).on('select', '#oldtip', function () {

		var $el = $(this).attr('id');
		var $tip = $(this).val();
		$('#tagbox[data-id="' + $el + '"]').empty().load('content/core/core.tasks.php?action=itags&tip=' + urlEncodeData($tip));

	});

	$(document).off('select', '#tip');
	$(document).on('select', '#tip', function () {

		var $el = $(this).attr('id');
		var $tip = $(this).val();
		$('#tagbox[data-id="' + $el + '"]').empty().load('content/core/core.tasks.php?action=itags&tip=' + urlEncodeData($tip));

	});
	*/

	$(document).off('click', '.tags');
	$(document).on('click', '.tags', function () {

		var $tag = $(this).text();
		var $el = $(this).closest('#tagbox').data('id');
		insTextAtCursor($el, $tag + '; ');

	});

	$('#dialog').on('click', '.del', function () {

		$(this).closest('.infodiv').remove();

	});

	function selItem(tip, id, title) {

		if (tip === 'client') {

			$("#clid").val(id);

			// если напоминание ставится для конкретного контакта
			var pid = parseInt('<?=$pid?>');

			if (action === 'add' && pid === 0) {

				$.get('content/helpers/client.helpers.php?action=get.maincontact&clid=' + id, function (data) {

					if (data.pid > 0) {
						$("#pid_list").html('<div class="infodiv h0 fs-10 flh-12" id="person_' + data.pid + '" title="' + data.contact + '"><INPUT type="hidden" name="pid[]" id="pid[]" value="' + data.pid + '"><div class="el"><div class="del" onclick="delItem(\'' + id + '\')"><i class="icon-cancel-circled"></i></div>' + data.contact + ' [' + data.ptitle + ']</div></div>');
					}

				}, 'json');

			}

		}
		else if (tip === 'person') {

			$("#pid_list").append('<div class="infodiv h0 fs-10 flh-12" id="person_' + id + '" title="' + title + '"><INPUT type="hidden" name="pid[]" id="pid[]" value="' + id + '"><div class="el"><div class="del" onclick="delItem(\'' + id + '\')"></div>' + title + '</div></div>');

		}

	}

	function get_pClient() {

		var clid = $('#clid').val();
		if (clid > 0) $('#pidd\\[\\]').load('content/helpers/person.helpers.php?action=get_plist&clid=' + clid);

	}

	/**
	 * @deprecated
	 */
	function addItem() {

		var pid = $('input[name=lid]:checked').val();
		var title = $('#txt' + pid).html();

		$('#orgspisok').remove();

		if (pid > 0) {

			var html = '<div class="infodiv h0 p3 mr5 mb5 fs-10 flh-12" title="' + title + '"><input type="hidden" name="pid[]" id="pid[]" value="' + pid + '"><div class="el"><div class="del"><i class="icon-cancel-circled"></i></div>' + title + '</div></div>';

			if ($('#pid_list').html() !== '') {
				$('#pid_list').prepend(html);
			}
			else {
				$('#pid_list').html(html);
			}

		}

	}

	function reddo() {

		var che = $('#redo').prop('checked');

		if (che === true) {

			$('#adtask #title').addClass('required');
			$('#adtask #datum').addClass('required');
			$('#adtask #totime').addClass('required');
			$('#adtask #datumtime').addClass('required');
			$('#adtask #tip').addClass('required');
			$('#adtask #rezultat').addClass('required');
			$('#old_task').hide();
			$('#rezt').hide();
			$('#uploads').hide();
			$('#boxtd').hide();
			$('#adtask').show('normal', function () {

				$('#dialog').center();

			});
			$('#hidealert').show();

		}
		else {

			$('#adtask #title').removeClass('required');
			$('#adtask #datum').removeClass('required');
			$('#adtask #totime').removeClass('required');
			$('#adtask #datumtime').removeClass('required');
			$('#adtask #tip').removeClass('required');
			$('#adtask #rezultat').addClass('required');
			//$('#old_task').show();
			$('#rezt').show();
			$('#uploads').show();
			$('#boxtd').show();
			$('#adtask').hide('normal', function () {
				$('#dialog').center();
			});
			$('#hidealert').hide();

		}

		copydes();

	}

	function delItem(id) {

		$('#person_' + id).remove();
		$('#dialog').center();

	}

	function addfile() {

		var kol = $('.filebox').size();
		var i = kol + 1;
		var htmltr = '<div id="file-' + i + '" class="filebox"><input name="file[]" type="file" class="file" id="file[]" onchange="addfile();"><div class="delfilebox hand" onclick="deleteFilebox(\'file-' + i + '\')" title="Очистить"><i class="icon-cancel-circled red"></i></div></div>';

		$('#uploads').append(htmltr);
		$('#dialog').center();

	}

	function copydes() {

		var tt = $('#rezultat').val();
		$('#des').val(tt);

	}

</script>