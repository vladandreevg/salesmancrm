<?php
/* ============================ */
/*         SalesMan CRM         */
/* ============================ */
/* (C) 2016 Vladislav Andreev   */
/*       SalesMan Project       */
/*        www.isaler.ru         */
/*        ver. 2017.x           */
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

$tid    = (int)$_REQUEST['tid'];
$datum  = $_REQUEST['datum'];
$action = $_REQUEST['action'];
$clid   = (int)$_REQUEST['clid'];
$did    = (int)$_REQUEST['did'];

if ( $clid == 0 && $did == 0 ) {

	if ( $action ) {

		//просмотр конкретного напоминания
		if ( $tid > 0 && $datum == '' ) {

			$result     = $db -> getRow( "SELECT * FROM ".$sqlname."tasks WHERE tid = '$tid' and identity = '$identity'" );
			$clid       = (int)$result["clid"];
			$did        = (int)$result["did"];
			$maintid    = $result["maintid"];
			$title      = $result["title"];
			$des        = nl2br( $result["des"] );
			$tip        = $result["tip"];
			$iduser     = (int)$result["iduser"];
			$autor      = $result["autor"];
			$datum      = $result["datum"];
			$priority   = $result["priority"];
			$speed      = $result["speed"];
			$pid        = yexplode( ";", (string)$result["pid"] );
			$totime     = $result["totime"];
			$day        = $result["day"];
			$status     = ((int)$result['status'] == 1) ? "Успешно" : "Не успешно";
			$statusIcon = ((int)$result['status'] == 1) ? '<i class="icon-ok green"></i>' : '<i class="icon-block red"></i>';

			$color = $db -> getOne( "SELECT color FROM ".$sqlname."activities WHERE title = '$tip' and identity = '$identity'" );
			if ( $color == "" ) {
				$color = "gray";
			}

			$dati1   = explode( "-", $datum );
			$dati1   = $dati1[2].".".$dati1[1].".".$dati1[0];
			$totime1 = yexplode( ":", $totime );
			$totime1 = $totime1[0].":".$totime1[1];

			$client = current_client( $clid );

			$tipa = get_activtip( $tip );

			$string = '';

			//Найдем связанные напоминания
			$users   = [];
			$resultt = $db -> query( "SELECT * FROM ".$sqlname."tasks WHERE maintid='".$tid."' and identity = '$identity'" );
			while ($data = $db -> fetch( $resultt )) {

				$users[] = '<span><i class="icon-user-1 blue"></i>'.current_user( $data['iduser'] ).' </span>';

			}

			//найдем напоминания с другими участниками
			$usert = [];
			if ( (int)$maintid > 0 ) {

				$resultt = $db -> query( "SELECT * FROM ".$sqlname."tasks WHERE maintid = '$maintid' and identity = '$identity'" );
				while ($data = $db -> fetch( $resultt )) {
					$usert[] = '<span><i class="icon-user-1 blue"></i>'.current_user( $data['iduser'] ).' </span>';
				}

			}

			if ( $des != '' ) {
				$string .= '<div class="viewdiv mb10 bgwhite p10" data-block="des">'.link_it( $des ).'</div>';
			}


			if ( $client != "" ) {
				$string .= '
				<div class="mb5 flex-container box--child" data-block="client">
					<div class="flex-string wp20">Клиент:</div>
					<div class="flex-string wp80">'.getAppendixClient( $tip, $clid ).'</div>
				</div>
				';
			}

			if ( $did > 0 ) {
				$string .= '
				<div class="mb5 flex-container box--child" data-block="deal">
					<div class="flex-string wp20">Сделка:</div>
					<div class="flex-string wp80"><A href="javascript:void(0)" onclick="openDogovor(\''.$did.'\')" title="Открыть в новом окне"><i class="icon-briefcase blue"></i>&nbsp;'.current_dogovor( $did ).'</A></div>
				</div>
				';
			}


			$plist = [];
			foreach ( $pid as $p ) {
				if ( (int)$p > 0 ) {
					$plist[] = '
					<div class="inline"><a href="javascript:void(0)" onclick="openPerson(\''.$p.'\')" title="В новом окне"><i class="icon-user-1 broun"></i>'.current_person( (int)$p ).'</a></div>
					';
				}
			}


			if ( !empty( $plist ) ) {
				$string .= '
				<div class="mb5 flex-container box--child" data-block="person">
					<div class="flex-string wp20">Контакты:</div>
					<div class="flex-string wp80">'.yimplode( "; ", $plist ).'</div>
				</div>
				';
			}

			if ( empty( $users ) ) {

				$s = ($autor > 0 && $autor != $iduser) ? ', <span class="blue" title="Назначил"><i class="icon-user-add blue"></i>'.current_user( $autor ).'</span>' : '';

				$string .= '
				<div class="mb5 flex-container box--child" data-block="users">
					<div class="flex-string wp20">Ответственный:</div>
					<div class="flex-string wp80">'.current_user( $iduser ).$s.'</div>
				</div>
				';

			}
			else {
				$string .= '
				<div class="mb5 flex-container box--child">
					<div class="flex-string wp20">Инициатор:</div>
					<div class="flex-string wp80"><i class="icon-user-1 blue"></i>'.current_user( $iduser ).'</div>
				</div>
				<div class="mb5 flex-container box--child">
					<div class="flex-string wp20">Участники:</div>
					<div class="flex-string wp80"> '.yimplode( "", $users ).'</div>
				</div>
				';
			}

			if ( !empty( $usert ) ) {
				$string .= '
				<div class="mb5 flex-container box--child">
					<div class="flex-string wp20">Участники:</div>
					<div class="flex-string wp80"> '.yimplode( "", $usert ).'</div>
				</div>
				';
			}

			$time = ($day == 'yes') ? '<i class="icon-flag" title="Весь день"></i>' : getTime( (string)$totime );
			?>

			<div class="Bold fs-12 mb10" data-block="datumtime">
				<b style="color:<?= $color ?>"><?= get_ticon( $tip ) ?></b>&nbsp;<B class="shad black"><?= $time ?></B>&nbsp;|&nbsp;<?= get_priority( 'priority', $priority ).get_priority( 'speed', $speed ) ?>
				<?= $title ?>
			</div>

			<div id="formtabs">

				<?php
				if($_REQUEST['button'] != 'yes') {
					$hooks -> do_action( "task_view", $tid );
				}
				?>

				<?= $string ?>

			</div>

			<?php

			exit();

		}

		/*Список напоминаний на дату*/
		if ( $datum != '' && $tid == 0 ) {
			?>
			<DIV class="zagolovok">
				<span class="smalltext"><B>Список дел</B> на <b class="bezh"><?= format_date_rus_name( $datum ) ?></b></span>
				<?php
				if ( $_REQUEST['zag'] != '' ) {
					?>
					<div style="float: right;">
						<a href="javascript:void(0)" onclick="$('.datumTasksView').empty().hide()"><i class="icon-cancel-circled smalltxt white"></i></a>
					</div>
				<?php } ?>
			</DIV>

			<div style="max-height:70vh; overflow:auto !important">

				<TABLE id="bborder" class="">
					<?php
					$resultt = $db -> query( "SELECT * FROM ".$sqlname."tasks WHERE iduser = '$iduser1' and datum = '$datum' and identity = '$identity' ORDER BY datum, totime" );
					while ($data = $db -> fetch( $resultt )) {

						$pids = yexplode( ";", $data['pid'] );

						$tipa = get_activtip( $data['tip'] );

						$color = $db -> getOne( "SELECT color FROM ".$sqlname."activities WHERE title = '".$data['tip']."' and identity = '$identity'" );
						if ( $color == "" ) {
							$color = "transparent";
						}

						//Найдем связанные напоминания
						$users = [];
						$res   = $db -> query( "SELECT * FROM ".$sqlname."tasks WHERE maintid = '".$data['tid']."' and identity = '$identity'" );
						while ($da = $db -> fetch( $res )) {
							$users[] = '<span><i class="icon-user-1 blue"></i>'.current_user( (int)$da['iduser'] ).' </span>';
						}

						$diff  = diffDate2( (string)$data['datum'] );
						$hours = difftime( (string)$data['created'] );

						$time = ($data['day'] == 'yes') ? '<i class="icon-flag" title="Весь день"></i>' : getTime( (string)$data['totime'] );

						?>
						<TR class="ha">
							<TD width="80" align="center" valign="top">
								<?php
								if ( $data['active'] == "yes" ) {

									if ( $diff == 0 )
										print '<span class="fs-09 greenbg p5">'.$time.'&nbsp;<i class="icon-ok white" title="Порядок"></i></span>';

									elseif ( $diff < 0 )
										print '<span class="fs-09 redbg p5">'.$time.'&nbsp;<i class="icon-attention white" title="! Не выполнено"></i></span>';

									elseif ( $diff > 0 )
										print '<span class="fs-09 bluebg p5">'.$time.'&nbsp;<i class="icon-ok white" title="Порядок"></i></span>';

								}
								else
									print '<span class="fs-09 bluebg-dark"><del>'.$time.'</del>&nbsp;<i class="icon-ok white" title="Порядок"></i></span>';
								?>
							</TD>
							<TD align="left" valign="top">

								<div title="<?= $data['title'] ?>" class="mb10"><?= get_priority( 'priority', $data['priority'] ).get_priority( 'speed', $data['speed'] ) ?>

									<div class="Bold fs-12 inline">
										<span style="color:<?= $color ?>"><?= get_ticon( $data['tip'] ) ?></span>&nbsp;<?= $data['title'] ?>
									</div>

								</div>

								<?php

								if ( $data['des'] != '' )
									print '<div class="viewdiv mb10 bgwhite p10">'.link_it( $data['des'] ).'</div>';

								if ( $data['did'] > 0 )
									print '
										<div class="mb5 flex-container box--child">
											<div class="flex-string wp20">Сделка:</div>
											<div class="flex-string wp80"><A href="javascript:void(0)" onclick="openDogovor(\''.$data['did'].'\')" title="Открыть в новом окне"><i class="icon-briefcase blue"></i>&nbsp;'.current_dogovor( $data['did'] ).'</A></div>
										</div>
									';

								if ( $data['clid'] > 0 )
									print '
										<div class="mb5 flex-container box--child">
											<div class="flex-string wp20">Клиент:</div>
											<div class="flex-string wp80">'.getAppendixClient( $data['tip'], $data['clid'] ).'</div>
										</div>
									';


								$plist = [];
								foreach ( $pids as $pid ) {

									if ( $pid > 0 )
										$plist[] = '<div class="inline"><a href="javascript:void(0)" onclick="openPerson(\''.$pid.'\')" title="В новом окне"><i class="icon-user-1 broun"></i>'.current_person( $pid ).'</a></div>';

								}

								if ( !empty( $plist ) )
									print '
									<div class="mb5 flex-container box--child">
										<div class="flex-string wp20">Контакты:</div>
										<div class="flex-string wp80">'.yimplode( "; ", $plist ).'</div>
									</div>';

								if ( empty( $users ) )
									print '
									<div class="mb5 flex-container box--child">
										<div class="flex-string wp20">Ответственный:</div>
										<div class="flex-string wp80"><i class="icon-user blue"></i> '.current_user( $data['iduser'] ).($data['autor'] > 0 && $data['autor'] != $data['iduser'] ? ', <span class="blue" title="Назначил"><i class="icon-user-add blue"></i>'.current_user( $data['autor'] ).'</span>' : '').'</div>
									</div>
									';

								elseif ( !empty( $users ) )
									print '
									<div class="mb5 flex-container box--child">
										<div class="flex-string wp20">Инициатор:</div>
										<div class="flex-string wp80"><i class="icon-user-1 blue"></i>'.current_user( $data['iduser'] ).'</div>
									</div>
									<div class="mb5 flex-container box--child">
										<div class="flex-string wp20">Участники:</div>
										<div class="flex-string wp80"> '.yimplode( "; ", $users ).'</div>
									</div>
									';


								?>
								<div>
									<div class="pull-aright smalltxt">
										<?php
										if ( $data['readonly'] != 'yes' )
											$data['readonly'] = '';
										if ( $data['autor'] == $data['iduser'] && $data['readonly'] == 'yes' )
											$data['readonly'] = '';
										if ( $data['autor'] != $data['iduser'] && $data['readonly'] == 'yes' )
											$data['readonly'] = 'yes';
										if ( $data['autor'] == $iduser1 && $data['readonly'] == 'yes' )
											$data['readonly'] = '';

										if ( ($data['autor'] == $iduser1 || $data['autor'] == 0) || $data['readonly'] != 'yes' ) {

											if($userSettings['taskCheckBlock'] == 'yes' && $data[ 'iduser' ] != $iduser1) {
												print "";
											}
											else {
												print '<A href="javascript:void(0)" onClick="editTask(\''.$data['tid'].'\',\'doit\');" title="Активно, пометить как сделанное"><i class="icon-ok blue"></i>Сделано</A>';
											}

											if ( $hours <= 1 )
												print '<A href="javascript:void(0)" onClick="editTask(\''.$data['tid'].'\',\'edit\');" title="Изменить"><i class="icon-pencil broun"></i>Изменить</A>';

										}
										else print '<i class="icon-lock red" title="Только чтение"></i>';
										?>
									</div>
								</div>
							</TD>
						</TR>
						<?php
					}
					?>
				</TABLE>

			</div>
			<script>
				$(function () {

					$('#dialog').css('width', '803px').center();

				});

				$('#dialog').center();
			</script>
			<?php
		}

	}
	if ( !$action ) {

		$string = '';

		$result   = $db -> getRow( "SELECT * FROM ".$sqlname."tasks WHERE tid = '$tid' and identity = '$identity'" );
		$clid     = (int)$result["clid"];
		$did      = (int)$result["did"];
		$title    = $result["title"];
		$des      = link_it( $result["des"] );
		$tip      = $result["tip"];
		$maintid  = $result["maintid"];
		$iduser   = (int)$result["iduser"];
		$autor    = (int)$result["autor"];
		$datum    = $result["datum"];
		$totime   = $result["totime"];
		$created  = $result["created"];
		$pids     = yexplode( ";", $result["pid"] );
		$cid      = $result["cid"];
		$priority = $result["priority"];
		$speed    = $result["speed"];
		$readonly = $result["readonly"];
		$day      = $result["day"];
		//$status     = ($result['status'] == 1) ? "Успешно" : "Не успешно";
		//$statusIcon = ($result['status'] == 1) ? '<i class="icon-ok green"></i>' : '<i class="icon-block red"></i>';

		if ( $result['status'] == 1 ) {
			$status = '<i class="icon-ok green" title="Успешно"></i>&nbsp;Успешно';
		}

		elseif ( $result['status'] == 2 ) {
			$status = '<i class="icon-block red" title="Не успешно"></i>&nbsp;Не успешно';
		}

		else {
			$status = '<i class="icon-ok green" title="Не определено"></i>&nbsp;Не определено';
		}


		$hours = difftime( $created );

		//Найдем связанные напоминания
		$users   = [];
		$resultt = $db -> query( "SELECT * FROM ".$sqlname."tasks WHERE maintid = '$tid' and identity = '$identity'" );
		while ($data = $db -> fetch( $resultt )) {
			$users[] = '<span class="tags"><i class="icon-user-1 blue"></i>'.current_user( $data['iduser'] ).' </span>';
		}

		//найдем напоминания с другими участниками
		$usert = [];
		if ( $maintid > 0 ) {

			$resultt = $db -> query( "SELECT * FROM ".$sqlname."tasks WHERE maintid = '$maintid' and identity = '$identity'" );
			while ($data = $db -> fetch( $resultt )) {
				$usert[] = '<span class="tags"><i class="icon-user-1 blue"></i>'.current_user( $data['iduser'] ).' </span>';
			}

		}

		$color = $db -> getOne( "SELECT color FROM ".$sqlname."activities WHERE title = '$tip' and identity = '$identity'" );
		if ( $color == "" ) {
			$color = "#bbb";
		}

		if ( empty( $users ) ) {

			$string .= '
				<div class="mb15 flex-container box--child" data-block="user">
					<div class="flex-string wp20 gray2 fs-12 text-right">Ответственный:</div>
					<div class="flex-string wp80 pl10 fs-12 Bold black"><i class="icon-user-1 blue"></i>'.current_user( (int)$iduser ).'</div>
				</div>
				';

			$string .= ($autor > 0 && $autor != $iduser) ? '
				<div class="mb15 flex-container box--child" data-block="author">
					<div class="flex-string wp20 gray2 fs-12 text-right">Назначил:</div>
					<div class="flex-string wp80 pl10 fs-12 blue"><i class="icon-user-add"></i>'.current_user( (int)$autor ).'</div>
				</div>
				' : '';

		}
		else {

			$string .= '
				<div class="mb15 flex-container box--child" data-block="author">
					<div class="flex-string wp20 gray2 fs-12 text-right">Инициатор:</div>
					<div class="flex-string wp80 pl10"><i class="icon-user-1 blue"></i>'.current_user( $autor ).'</div>
				</div>
				<div class="mb15 flex-container box--child" data-block="users">
					<div class="flex-string wp20 gray2 fs-12 pt7 text-right">Участники:</div>
					<div class="flex-string wp80 pl10">'.yimplode( "", $users ).'</div>
				</div>
				';

		}

		if ( !empty( $usert ) ) {
			$string .= '
				<div class="mb15 flex-container box--child" data-block="users">
					<div class="flex-string wp20 gray2 pt7 fs-12 text-right">Участники:</div>
					<div class="flex-string wp80 pl10"> '.yimplode( "", $usert ).'</div>
				</div>
				';
		}


		if ( $did > 0 ) {
			$string .= '
				<div class="mb15 flex-container box--child" data-block="deal">
					<div class="flex-string wp20 gray2 fs-12 text-right">Сделка:</div>
					<div class="flex-string wp80 pl10 fs-12"><A href="javascript:void(0)" onclick="openDogovor(\''.$did.'\')" title="Открыть в новом окне"><i class="icon-briefcase blue"></i>&nbsp;'.current_dogovor( $did ).'</A></div>
				</div>
				';
		}


		if ( $clid > 0 ) {
			$string .= '
				<div class="mb15 flex-container box--child" data-block="client">
					<div class="flex-string wp20 gray2 fs-12 text-right">Клиент:</div>
					<div class="flex-string wp80 pl10 fs-12">'.getAppendixClient( $tip, $clid ).'</div>
				</div>
				';
		}


		$tipa = get_activtip( $tip );

		$plist = [];
		foreach ( $pids as $pid ) {

			if($pid == 0){
				continue;
			}

			$s = '';

			if ( $tipa == 'phone' ) {
				$s = getPersonWPhone( $pid );
			}
			elseif ( $tipa == 'email' ) {
				$s = getPersonWMail( $pid );
			}
			else {
				$s = '<a href="javascript:void(0)" onclick="openPerson(\''.$pid.'\')" title="В новом окне"><i class="icon-user-1 broun"></i>'.current_person( $pid ).'</a>';
			}

			$plist[] = '<div class="inline">'.$s.'</div>';

		}

		if ( !empty( $plist ) ) {
			$string .= '
				<div class="mb10 flex-container box--child" data-block="person">
					<div class="flex-string wp20 gray2 fs-12 text-right">Контакты:</div>
					<div class="flex-string wp80 pl10 fs-12">'.yimplode( "; ", $plist ).'</div>
				</div>
				';
		}

		if ( $des != '' ) {
			$string .= '<div class="tipp infodiv mb10 bgwhite p10" data-block="des">'.link_it( nl2br( $des ) ).'</div>';
		}

		$time = ($day == 'yes') ? '<i class="icon-flag green" title="Весь день"></i> Весь день' : getTime( (string)$totime );
		?>
		<DIV class="zagolovok">
			<?= $time ?> <?= get_ticon( $tip ) ?>
			<span class="hidden-iphone"> <?= get_priority2( 'priority', $priority ).get_priority2( 'speed', $speed ) ?></span><?= $title ?>
			<sup class="fs-07 hidden-iphone"><?= format_date_rus( $datum ) ?></sup>
		</DIV>

		<div id="formtabs" class="box--child" style="max-height: 70vh; overflow-y:auto !important; overflow-x:hidden">

			<div class="mt10 mb10 flex-container box--child" data-block="datumtime">
				<div class="flex-string wp20 gray2 fs-12 text-right">Выполнить:</div>
				<div class="flex-string wp80 pl10 fs-12">
					<i class="icon-clock blue"></i><?= $time ?>, <?= format_date_rus( $datum ) ?></div>
			</div>

			<div class="mb10 flex-container box--child" data-block="tip">
				<div class="flex-string wp20 gray2 fs-12 text-right">Тип:</div>
				<div class="flex-string wp80 pl10 fs-12" style="color:<?= $color ?>"><?= get_ticon( $tip ).' '.$tip ?></div>
			</div>

			<?php

			print $string;

			if($_REQUEST['button'] != 'yes') {
				$hooks -> do_action( "task_view", $tid );
			}

			//Данные по истории
			if ( (int)$cid > 0 ) {

				$resulth = $db -> getRow( "SELECT * FROM ".$sqlname."history WHERE cid='".$cid."' and identity = '$identity'" );
				$datumr  = get_sfdate( $resulth["datum"] );
				$desr    = $resulth["des"];
				$fids    = $resulth["fid"];

				?>
				<div class="divider mt10">Результат выполнения</div>
				<div class="infodiv">
					<?php
					print '
					<div>Выполнено со статусом <b>'.$status.'</b></div>
					<div><b>'.$datumr.'</b></div>
					<div class="infodiv bgwhite mt10">'.link_it( $desr ).'</div>
					';

					$fids = yexplode( ";", $fids );
					if ( !empty( $fidss ) ) {

						print "<hr>";

						foreach ( $fids as $fid ) {

							$r      = $db -> getRow( "select * from ".$sqlname."file WHERE fid = '$fid' and identity = '$identity'" );
							$ftitle = $r["ftitle"];
							$fname  = $r["fname"];

							print '<div class="fileboxx"><A href="javascript:void(0)" onclick="fileDownload(\''.$fid.'\')">'.get_icon2( $ftitle ).'&nbsp;<B>'.$ftitle.'</B></A></div>';

						}

					}
					?>
				</div>
				<?php
			}
			?>

		</div>

		<?php
		if ( $_REQUEST['button'] != 'yes' && (int)$cid == 0 ) {
			?>
			<hr>
			<div class="text-right button--pane">

				<?php
				$change = '';

				if ( $autor == $iduser1 || $autor == 0 )
					$readonly = '';

				if ( $autor == $iduser || $autor == 0 || $autor == $iduser1 ) {

					if ( $hours <= $hoursControlTime ) {
						$change = 'yes';
					}
					elseif ( $userRights['changetask'] ) {
						$change = 'yes';
					}

				}

				if ( $readonly != 'yes' ) {

					if($userSettings['taskCheckBlock'] == 'yes' && $data[ 'iduser' ] != $iduser1) {
						print "";
					}
					else {
						print '<a href="javascript:void(0)" onClick="editTask(\''.$tid.'\',\'doit\');" class="button greenbtn"><i class="icon-ok"></i>Сделано</a>';
					}

					if ( $change == 'yes' )
						print '<a href="javascript:void(0)" onClick="editTask(\''.$tid.'\',\'edit\');" class="button bluebtn"><i class="icon-pencil"></i>Изменить</a>';


				}
				else
					print '<a href="javascript:void(0)" class="button graybtn"><i class="icon-lock red"></i>Только чтение</a>';

				?>

			</div>
			<?php
		}
		?>
		<script>

			$(function () {

				if (!isMobile) {

					$('#dialog').css('width', '702px').center();

				}
				else {

					var h2 = $(window).height() - $('.zagolovok').actual('outerHeight') - $('.button--pane').actual('outerHeight') - 30;

					$('#formtabs').css({'max-height': h2 + 'px', 'height': h2 + 'px'});

				}

			});
		</script>
		<?php

	}

}
else {
	?>
	<DIV class="zagolovok">
		<?php
		if ( $did > 0 )
			print 'Список дел по сделке: <B>'.current_dogovor( $did ).'</B>';
		if ( $clid > 0 )
			print 'Список дел по клиенту: <B>'.current_client( $clid ).'</B>';
		?>
	</DIV>

	<div id="formtabss" style="max-height:70vh; overflow:auto !important">

		<TABLE id="bborder">
			<?php
			if ( $did > 0 ) {
				$dd  = "did = '".$did."'";
				$url = '<a href="javascript:void(0)" onclick="openDogovor(\''.$did.'\')" class="button pull-aright"><i class="icon-briefcase-1"></i>Карточка сделки &rarr;</a>';
			}
			if ( $clid > 0 ) {
				$dd  = "clid = '".$clid."'";
				$url = '<a href="javascript:void(0)" onclick="openClient(\''.$clid.'\')" class="button pull-aright"><i class="icon-building"></i>Карточка клиента &rarr;</a>';
			}

			$resultt = $db -> query( "SELECT * FROM ".$sqlname."tasks WHERE ".$dd." and active = 'yes' and identity = '$identity' ORDER BY datum, totime" );
			while ($data = $db -> fetch( $resultt )) {

				$pids = yexplode( ";", $data['pid'] );

				$tipa = get_activtip( $data['tip'] );

				$color = $db -> getOne( "SELECT color FROM ".$sqlname."activities WHERE title = '".$data['tip']."' and identity = '$identity'" );
				if ( $color == "" ) {
					$color = "transparent";
				}

				//Найдем связанные напоминания
				$users = [];
				$res   = $db -> query( "SELECT * FROM ".$sqlname."tasks WHERE maintid = '".$data['tid']."' and identity = '$identity'" );
				while ($da = $db -> fetch( $res )) {
					$users[] = '<span><i class="icon-user-1 blue"></i>'.current_user( $da['iduser'] ).' </span>';
				}

				$diff = diffDate2( $data['datum'] );

				$hours = difftime( $data['created'] );

				?>
				<TR class="ha">
					<TD width="10%" align="center" valign="top">
						<?php
						if ( $diff == 0 && $data['active'] == "yes" )
							print '<span class="task_ind smallind rd">'.getTime( (string)$data['totime'] ).'</span>&nbsp;<i class="icon-ok white" title="Порядок"></i>';
						elseif ( $diff < 0 && $data['active'] == "yes" )
							print '<span class="task_ind smallind grn">'.getTime( (string)$data['totime'] ).'&nbsp;<i class="icon-attention white" title="! Не выполнено"></i></span>';
						elseif ( $diff < 0 )
							print '<span class="task_ind smallind bl">'.getTime( (string)$data['totime'] ).'&nbsp;<i class="icon-ok white" title="Порядок"></i></span>';
						elseif ( $data['active'] == "no" )
							print '<span class="task_ind smallind bl"><del>'.getTime( (string)$data['totime'] ).'</del>&nbsp;<i class="icon-ok white" title="Порядок"></i></span>';
						?>
					</TD>
					<TD align="left" valign="top">

						<div title="<?= $data['title'] ?>" class="mb10"><?= get_priority( 'priority', $data['priority'] ).get_priority( 'speed', $data['speed'] ) ?>

							<div class="Bold fs-12 inline">
								<span style="color:<?= $color ?>"><?= get_ticon( $data['tip'] ) ?></span>&nbsp;<?= $data['title'] ?>
							</div>

						</div>

						<?php

						if ( $data['des'] != '' )
							print '<div class="viewdiv mb10 bgwhite p10">'.link_it( $data['des'] ).'</div>';

						if ( $data['did'] > 0 ) {

							print '
								<div class="mb5 flex-container box--child">
									<div class="flex-string wp20">Сделка:</div>
									<div class="flex-string wp80"><A href="javascript:void(0)" onclick="openDogovor(\''.$data['did'].'\')" title="Открыть в новом окне"><i class="icon-briefcase blue"></i>&nbsp;'.current_dogovor( $data['did'] ).'</A></div>
								</div>
							';

						}
						if ( $data['clid'] > 0 ) {

							print '
								<div class="mb5 flex-container box--child">
									<div class="flex-string wp20">Клиент:</div>
									<div class="flex-string wp80">'.getAppendixClient( $data['tip'], $data['clid'] ).'</div>
								</div>
							';

						}

						$plist = [];
						foreach ( $pids as $pid ) {

							$plist[] = '<div class="inline"><a href="javascript:void(0)" onclick="openPerson(\''.$pid.'\')" title="В новом окне"><i class="icon-user-1 broun"></i>'.current_person( $pid ).'</a></div>';

						}

						if ( !empty( $plist ) )

							print '
								<div class="mb5 flex-container box--child">
									<div class="flex-string wp20">Контакты:</div>
									<div class="flex-string wp80">'.yimplode( "; ", $plist ).'</div>
								</div>
							';

						if ( empty( $users ) ) {

							$s = ($data['autor'] > 0 && $data['autor'] != $data['iduser']) ? ', <span class="blue" title="Назначил"><i class="icon-user-add blue"></i>'.current_user( $data['autor'] ).'</span>' : '';

							print '
							<div class="mb5 flex-container box--child">
								<div class="flex-string wp20">Ответственный:</div>
								<div class="flex-string wp80">'.current_user( $data['iduser'] ).$s.'</div>
							</div>
							';

						}
						elseif ( !empty( $users ) ) {

							print '
							<div class="mb5 flex-container box--child">
								<div class="flex-string wp20">Инициатор:</div>
								<div class="flex-string wp80"><i class="icon-user-1 blue"></i>'.current_user( $data['iduser'] ).'</div>
							</div>
							<div class="mb5 flex-container box--child">
								<div class="flex-string wp20">Участники:</div>
								<div class="flex-string wp80"> '.yimplode( "; ", $users ).'</div>
							</div>
							';

						}
						?>
					</TD>
				</TR>
				<?php

			}
			?>
		</TABLE>

	</div>

	<hr>

	<div class="button--pane">

		<div class="text-left pull-left hidden">

			<?php
			if ( $data['readonly'] != 'yes' )
				$data['readonly'] = '';
			if ( $data['autor'] == $data['iduser'] && $data['readonly'] == 'yes' )
				$data['readonly'] = '';
			if ( $data['autor'] != $data['iduser'] && $data['readonly'] == 'yes' )
				$data['readonly'] = 'yes';
			if ( $data['autor'] == $iduser1 && $data['readonly'] == 'yes' )
				$data['readonly'] = '';

			if ( ($data['autor'] == $iduser1 || $data['autor'] == 0) || $data['readonly'] != 'yes' ) {

				print '<A href="javascript:void(0)" onClick="editTask(\''.$data['tid'].'\',\'doit\');" class="button greenbtn" title="Активно, пометить как сделанное"><i class="icon-ok"></i>Сделано</A>';
				if ( $hours <= 1 )
					print '<A href="javascript:void(0)" onClick="editTask(\''.$data['tid'].'\',\'edit\');" class="button bluebtn" title="Изменить"><i class="icon-pencil broun"></i>Изменить</A>';

			}
			else print '<a href="javascript:void(0)" class="button redbtn"><i class="icon-lock" title="Только чтение"></i></a>';
			?>

		</div>

		<div class="pull-aright inline"><?= $url ?></div>

	</div>

	<script>
		$(function () {

			if (!isMobile) {

				$('#dialog').css('width', '803px').center();

			}
			else {

				var h2 = $(window).height() - $('.zagolovok').actual('outerHeight') - $('.button--pane').actual('outerHeight') - 30;
				$('#formtabs').css({'max-height': h2 + 'px', 'height': h2 + 'px'});

			}

		});
	</script>
	<?php
}
?>