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

$rootpath = dirname(__DIR__, 2);

include $rootpath."/inc/config.php";
include $rootpath."/inc/dbconnector.php";
include $rootpath."/inc/auth.php";
include $rootpath."/inc/func.php";
include $rootpath."/inc/settings.php";

$thisfile = basename( __FILE__ );

$action = $_REQUEST['action'];
$iduser = (int)$_REQUEST['iduser'];
$word   = $_REQUEST['word'];
$roles  = (array)$_REQUEST['roles'];
$otdels = (array)$_REQUEST['otdels'];
$rukov  = $_REQUEST['rukov'];
$utitle = $_REQUEST['utitle'];

//проверим таблицу на наличие поля
$result = $db -> getRow( "select * from {$sqlname}user where identity = '$identity' LIMIT 1" );
$adate  = $result["adate"];

/*
if ( $adate == '' ) {

	$adate = date( "Y-m-d", mktime( 1, 0, 0, (int)date( 'm' ), (int)date( 'd' ) - 7, (int)date( 'Y' ) ) );

	$field = $db -> getRow( "SHOW COLUMNS FROM {$sqlname}user LIKE 'adate'" );
	if ( $field['Field'] == '' ) {
		$db -> query("ALTER TABLE {$sqlname}user ADD `adate` DATE NOT NULL AFTER `sole`");
	}

	$db -> query( "update {$sqlname}user set adate = '$adate' where secrty = 'no'" );

}
*/

function candelete($id, $db): array {
	
	global $rootpath;

	require $rootpath."/inc/config.php";

	$identity = $GLOBALS['identity'];
	$sqlname  = $GLOBALS['sqlname'];

	$responce = [];

	//print "select COUNT(*) as count from {$sqlname}clientcat where iduser='".$id."' and identity = '$identity'";
	$all = 0;

	//проверим наличие клиентов
	$result             = (int)$db -> getOne( "select COUNT(*) as count from {$sqlname}clientcat where iduser='".$id."' and identity = '$identity'" );
	$all                += $result;
	$responce['client'] = $result;

	//проверим наличие персон
	$result             = (int)$db -> getOne( "select COUNT(*) as count from {$sqlname}personcat where iduser='".$id."' and identity = '$identity'" );
	$all                += $result;
	$responce['person'] = $result;

	//проверим наличие сделок
	$result           = (int)$db -> getOne( "select COUNT(*) as count from {$sqlname}dogovor where iduser='".$id."' and identity = '$identity'" );
	$all              += $result;
	$responce['deal'] = $result;

	//проверим наличие подчиненных
	$result               = $db -> getOne( "select COUNT(*) as count from {$sqlname}user where mid='".$id."' and identity = '$identity'" );
	$all                  += $result;
	$responce['subusers'] = $result;

	$responce['all'] = $all;

	return $responce;

}

if ( empty( $_SERVER['HTTP_X_REQUESTED_WITH'] ) && strtolower( $_SERVER['HTTP_X_REQUESTED_WITH'] ) != 'xmlhttprequest' ) {

	print '<div class="warning text-center">Доступ запрещен.<br>Обратитесь к администратору.</div>';
	exit();

}
?>
<h2>&nbsp;Раздел: "Сотрудники" [таблица]</h2>

<?php
if ( isset( $action ) && $action == "delete" ) {
	$db -> query( "delete from {$sqlname}user where iduser = '$iduser' and identity = '$identity'" );
}

$sort = '';

if ( !empty( $roles ) ) {
	$sort .= " and tip IN (".yimplode(",", $roles, "'").")";
}
if ( !empty( $otdels ) ) {
	$sort .= " and otdel IN (".implode(",", $otdels).")";
}
if ( !empty( $rukov ) ) {
	$sort .= " and (mid IN (".implode(",", $rukov).") or iduser IN (".implode(",", $rukov)."))";
}
if ( $utitle != '' ) {
	$sort .= " and title LIKE '%$utitle%'";
}

define('SORT', $sort);

function getUserCatalogg($id = NULL, $level = 0, $res = []) {

	global $rootpath;

	include $rootpath."/inc/config.php";
	include $rootpath."/inc/dbconnector.php";

	$identity = $GLOBALS['identity'];
	$sort     = SORT;
	$sqlname  = $GLOBALS['sqlname'];
	$db       = $GLOBALS['db'];

	global $res;

	if ( !$id ) {
		$sort .= " and (mid = '0')";
	}
	else {
		$sort .= " and mid = '".$id."'";
	}

	$re = $db -> getAll( "SELECT iduser, mid, title, tip, secrty, adate, otdel, avatar FROM {$sqlname}user WHERE iduser > 0 $sort and identity = '$identity' ORDER BY mid, title" );
	foreach ( $re as $da ) {

		$dd   = 10;
		$atip = 'off';
		$act2 = '<i class="icon-lock-open blue" title="Активен. Блокировать"></i>';

		$edit = '<A href="javascript:void(0)" onclick="doLoad(\'/content/admin/usereditor.php?action=edit&iduser='.$da['iduser'].'\');" title="Редактировать" class="gray"><i class="icon-pencil"></i></A>';

		if ( $da['secrty'] != 'yes' ) {

			$atip = 'on';
			$act2 = '<i class="icon-lock red" title="Блокирован. Активировать"></i>';

		}

		/*if ( $da['adate'] != '0000-00-00' ) {
			$dd = abs(diffDate2($da['adate']));
		}

		if ( $dd >= 3 || $da['adate'] == NULL ) {
			$icon = '<a href="javascript:void(0)" onclick="deActivate(\''.$da['iduser'].'\',\''.$atip.'\')">'.$act2.'</a>';
		}
		else {

			$icon = ($da['secrty'] == 'yes') ? '<i class="icon-lock-open gray2" title="Активен. Действие не доступно 3 дня. Прошло - '.$dd.' дней"></i>' : '<i class="icon-lock gray2" title="Блокирован. Действие не доступно 3 дня. Прошло - '.$dd.' дней"></i>';

		}*/

		$icon = '<a href="javascript:void(0)" onclick="deActivate(\''.$da['iduser'].'\',\''.$atip.'\')">'.$act2.'</a>';

		$candelete = candelete( (int)$da['iduser'], $db );

		$reso  = $db -> getRow( "SELECT * FROM {$sqlname}otdel_cat WHERE idcategory='".$da['otdel']."' and identity = '$identity'" );
		$otdel = $reso["title"];
		$uid   = $reso["uid"];

		if ( $uid != '' ) {
			$otdel = '<b>'.$uid.'</b>. '.$otdel;
		}

		$otdel = ($otdel != '') ? '<br><DIV class="ellipsis blue fs-07 mt10 pl20 hidden-iphone noBold" title="'.$otdel.'">&nbsp;'.$otdel.'</DIV>' : '<br><DIV class="ellipsis gray fs-07 mt10 pl20 hidden-iphone noBold">&nbsp;--Не указан--</DIV>';

		$res[] = [
			"id"        => $da["iduser"],
			"title"     => $da["title"],
			"level"     => $level,
			"secrty"    => $da['secrty'],
			"tip"       => $da['tip'],
			"icon"      => $icon,
			"candelete" => $candelete,
			"otdel"     => $otdel,
			"edit"      => $edit,
			"mid"       => $da['mid'],
			"avatar"    => $da['avatar']
		];

		if ( $da['iduser'] > 0 ) {

			$level++;
			getUserCatalogg( $da['iduser'], $level );
			$level--;

		}

	}

	return $res;

}

$uC = getUserCatalogg( 0 );

?>

<form name="uform" id="uform" action="/content/admin/users.table.php" method="post">

	<table class="noborder hidden-iphone">
		<tr>
			<td class="wp25">
				<div class="ydropDown selects">
					<span>Роли</span><span class="ydropCount"><?= count( (array)$roles ) ?> выбрано</span><i class="icon-angle-down pull-aright"></i>
					<div class="yselectBox" style="max-height: 300px;">
						<div class="yunSelect"><i class="icon-cancel-circled2"></i>Снять выделение</div>
						<div class="ydropString ellipsis">
							<label>
								<input class="taskss" name="roles[]" type="checkbox" id="roles[]" value="0" <?php if ( in_array( '', (array)$roles ) )
									print 'checked'; ?>>&nbsp;Не указано
							</label>
						</div>
						<div class="ydropString ellipsis">
							<label>
								<input class="taskss" name="roles[]" type="checkbox" id="roles[]" value="Руководитель организации" <?php if ( in_array( "Руководитель организации", (array)$roles ) )
									print 'checked'; ?>>&nbsp;Руководитель организации
							</label>
						</div>
						<div class="ydropString ellipsis">
							<label>
								<input class="taskss" name="roles[]" type="checkbox" id="roles[]" value="Руководитель с доступом" <?php if ( in_array( "Руководитель с доступом", (array)$roles ) )
									print 'checked'; ?>>&nbsp;Руководитель с доступом
							</label>
						</div>
						<div class="ydropString ellipsis">
							<label>
								<input class="taskss" name="roles[]" type="checkbox" id="roles[]" value="Руководитель подразделения" <?php if ( in_array( "Руководитель подразделения", (array)$roles ) )
									print 'checked'; ?>>&nbsp;Руководитель подразделения
							</label>
						</div>
						<div class="ydropString ellipsis">
							<label>
								<input class="taskss" name="roles[]" type="checkbox" id="roles[]" value="Руководитель отдела" <?php if ( in_array( "Руководитель отдела", (array)$roles ) )
									print 'checked'; ?>>&nbsp;Руководитель отдела
							</label>
						</div>
						<div class="ydropString ellipsis">
							<label>
								<input class="taskss" name="roles[]" type="checkbox" id="roles[]" value="Менеджер продаж" <?php if ( in_array( "Менеджер продаж", (array)$roles ) )
									print 'checked'; ?>>&nbsp;Менеджер продаж
							</label>
						</div>
						<div class="ydropString ellipsis">
							<label>
								<input class="taskss" name="roles[]" type="checkbox" id="roles[]" value="Специалист" <?php if ( in_array( "Специалист", (array)$roles ) )
									print 'checked'; ?>>&nbsp;Специалист
							</label>
						</div>
						<div class="ydropString ellipsis">
							<label>
								<input class="taskss" name="roles[]" type="checkbox" id="roles[]" value="Администратор" <?php if ( in_array( "Администратор", (array)$roles ) )
									print 'checked'; ?>>&nbsp;Администратор
							</label>
						</div>
					</div>
				</div>
			</td>
			<td class="wp25">
				<div class="ydropDown selects">
					<span>Отдел</span><span class="ydropCount"><?= count( (array)$otdels ) ?> выбрано</span><i class="icon-angle-down pull-aright"></i>
					<div class="yselectBox" style="max-height: 300px;">
						<div class="yunSelect"><i class="icon-cancel-circled2"></i>Снять выделение</div>
						<?php
						$result = $db -> getAll( "SELECT * FROM {$sqlname}otdel_cat WHERE identity = '$identity' ORDER BY title" );
						foreach ( $result as $data ) {
							?>
							<div class="ydropString ellipsis">
								<label>
									<input class="taskss" name="otdels[]" type="checkbox" id="otdels[]" value="<?= $data['idcategory'] ?>" <?php if ( in_array( $data['idcategory'], (array)$otdels ) ) print 'checked'; ?>>&nbsp;<?= $data['title'] ?>
								</label>
							</div>
						<?php } ?>
					</div>
				</div>
			</td>
			<td class="wp30">
				<div class="ydropDown selects">
					<span>Руководитель</span><span class="ydropCount"><?= count( (array)$rukov ) ?> выбрано</span><i class="icon-angle-down pull-aright"></i>
					<div class="yselectBox" style="max-height: 300px;">
						<div class="yunSelect"><i class="icon-cancel-circled2"></i>Снять выделение</div>
						<?php
						$result = $db -> getAll( "SELECT * FROM {$sqlname}user WHERE tip LIKE '%Руководител%' and identity = '$identity' ORDER BY title" );
						foreach ( $result as $data ) {
							?>
							<div class="ydropString ellipsis">
								<label>
									<input class="taskss" name="rukov[]" type="checkbox" id="rukov[]" value="<?= $data['iduser'] ?>" <?php if ( in_array( $data['iduser'], (array)$rukov ) )
										print 'checked'; ?>>&nbsp;<?= $data['title'] ?>
								</label>
							</div>
						<?php } ?>
					</div>
				</div>
			</td>
			<td>
				<input type="text" name="utitle" id="utitle" placeholder="Имя сотрудника" onkeydown="if(event.keyCode==13){ getUsersList(); return false }" class="wp70" value="<?= $utitle ?>">
				<a href="javascript:void(0)" onclick="getUsersList()" class="button orangebtn p5">Ок</a>
			</td>
		</tr>
	</table>

</form>

<TABLE id="zebra">
	<THEAD class="hidden-iphone sticked--top">
	<TR class="th40">
		<TH class=""><b>Имя пользователя</b></TH>
		<TH class="w120"></TH>
		<TH class="w200"><b>Роль, Руководитель</b></TH>
		<TH class="w50"></TH>
	</TR>
	</THEAD>
	<TBODY>
	<?php
	$userall = [];
	foreach ($uC as $xuC) {

		$margin = ($xuC['level'] - 1) * 40;

		$img    = ($xuC['level'] > 0) ? '<div class="pull-left hidden-iphone">&nbsp;</div>' : '';
		$avatar = ($xuC['avatar']) ? "/cash/avatars/".$xuC['avatar'] : "/assets/images/noavatar.png";

		$userall[] = $xuC['id'];

		?>
		<TR class="th40 ha border-bottom">
			<TD>
				<div style="padding-left: <?= $margin ?>px" class="nopad">
					<?= $img ?>
					<div class="avatar--mini pull-left mr10" style="background: url(<?= $avatar ?>); background-size:cover;">&nbsp;</div>
					<div onclick="viewUser('<?= $xuC['id'] ?>');" class="Bold hand relativ">

						<div class="pull-right hidden-iphone hidden"><?= $xuC['edit'] ?></div>
						<div class="fs-12 flh-11">
							<span class="gray2">ID <?= $xuC['id'] ?>:</span>
							<span class="<?= ($xuC['secrty'] != 'yes' ? "gray" : "") ?>">
								<?= $xuC['title'] ?>
							</span>
							<?= $xuC['otdel'] ?>
						</div>

					</div>
					<DIV class="visible-iphone" title="<?= $xuC['tip'] ?>"><?= $xuC['tip'] ?></DIV>
				</div>
			</TD>
			<TD class="text-left">
				<div class="inline pr5"><?= $xuC['edit'] ?></div>
				<?php
				if ( $iduser1 != $xuC['id'] ) {
					print $xuC['icon'];
				}
				else {
					print '<i class="icon-lock-open gray" title="Себя нельзя блокировать"></i>';
				}
				?>
				<div class="inline pr5"><?= ($xuC['tip'] != 'Руководитель организации' ? '&nbsp;<A href="javascript:void(0)" onclick="doLoad(\'/content/admin/usereditor.php?action=edit&iduser='.$xuC['id'].'&clone=yes\');" title="Клонировать с правами"><i class="icon-paste green"></i></A>' : '') ?></div>
			</TD>
			<TD class="hidden-iphone">
				<DIV class="ellipsis Bold" title="<?= $xuC['tip'] ?>"><?= $xuC['tip'] ?></DIV><br>
				<DIV class="ellipsis"><?= current_user( $xuC['mid'] ) ?></DIV>
			</TD>
			<TD class="text-center hidden-iphone">
				<?php
				if ( $xuC['candelete']['all'] < 1 && $iduser1 != $xuC['id'] ) {
					print '<A href="javascript:void(0)" onclick="cf=confirm(\'Вы действительно хотите удалить запись?\');if (cf)refresh(\'contentdiv\',\'/content/admin/users.php?iduser='.$xuC['id'].'&action=delete\');" title="Удалить"><i class="icon-cancel-circled red"></i></A>';
				}
				else {
					print '<i class="icon-cancel-circled gray" title="Удаление не возможно. У сотрудника есть записи: Клиент - '.$xuC['candelete']['client'].', Контакты - '.$xuC['candelete']['person'].', Сделки - '.$xuC['candelete']['deal'].', Подчиненные - '.$xuC['candelete']['subusers'].'"></i>';
				}
				?>
			</TD>
		</TR>
		<?php
	}

	//выводим пользователей, выпавших из списка
	if ( !empty( $userall ) ) {

		$re = $db -> getAll( "SELECT iduser, mid, title, tip, secrty, adate, otdel, avatar FROM {$sqlname}user WHERE iduser NOT IN (".implode( ",", $userall ).") and identity = '$identity' ORDER BY mid, title" );
		foreach ( $re as $da ) {

			$edit = '';
			$dd   = 10;
			$atip = 'off';
			$act2 = '<i class="icon-lock-open blue" title="Активен. Блокировать"></i>';

			$edit = '<A href="javascript:void(0)" onclick="doLoad(\'/content/admin/usereditor.php?action=izm&iduser='.$da['iduser'].'\');" title="Редактировать" class="gray"><i class="icon-pencil"></i></A>';

			if ( $da['secrty'] != 'yes' ) {

				$atip = 'on';
				$act2 = '<i class="icon-lock red" title="Блокирован. Активировать"></i>';

			}

			if ( $da['adate'] != '0000-00-00' && $da['adate'] != NULL ) {
				$dd = abs(diffDate2($da['adate']));
			}


			if ( $dd >= 3 ) {
				$icon = '<a href="javascript:void(0)" onclick="deActivate(\''.$da['iduser'].'\',\''.$atip.'\')">'.$act2.'</a>';
			}
			else {
				$icon = ( $da['secrty'] == 'yes' ) ? '<i class="icon-lock-open gray2" title="Активен. Действие не доступно 3 дня. Прошло - '.$dd.' дней"></i>' : '<i class="icon-lock gray2" title="Блокирован. Действие не доступно 3 дня. Прошло - '.$dd.' дней"></i>';
			}


			$candelete = candelete( (int)$da['iduser'], $db );

			$reso  = $db -> getRow( "SELECT * FROM {$sqlname}otdel_cat WHERE idcategory='".$da['otdel']."' and identity = '$identity'" );
			$otdel = $reso["title"];
			$uid   = $reso["uid"];

			if ( $uid != '' ) {
				$otdel = '<b>'.$uid.'</b>. '.$otdel;
			}

			$otdel = ($otdel != '') ? '<br><DIV class="ellipsis blue fs-09 pt5 pl20" title="'.$otdel.'">&nbsp;'.$otdel.'</DIV>' : '<br><DIV class="ellipsis gray fs-09 pt5 pl20">&nbsp;--Не указан--</DIV>';

			$res[] = [
				"id"        => $da["iduser"],
				"title"     => $da["title"],
				"secrty"    => $da['secrty'],
				"tip"       => $da['tip'],
				"icon"      => $icon,
				"candelete" => $candelete,
				"otdel"     => $otdel,
				"edit"      => $edit,
				"mid"       => $da['mid'],
				"avatar"    => $da['avatar']
			];

			$avatar = ($da['avatar']) ? "/cash/avatars/".$da['avatar'] : "/assets/images/noavatar.png";

			$us = current_user( $da['mid'] );

			$iuser = ($us == 'Не определен' && $da['tip'] != 'Руководитель организации') ? '<b class="red">Не указан</b>' : $us;

			?>
			<TR class="ha graybg-sub">
				<TD>
					<div>
						<div class="avatar--mini pull-left mr10" style="background: url(<?= $avatar ?>); background-size:cover;"></div>
						<DIV class="ellipsis" title="<?= $da['title'] ?>">
							<div onclick="viewUser('<?= $da['iduser'] ?>');" class="fs-11 Bold hand">
								<span class="gray2">ID <?= $da['iduser'] ?>:</span>
								<span class="<?= ($da['secrty'] != 'yes' ? "gray" : "blue") ?>">
									<?= $da['title'] ?>
								</span>
							</div>
						</DIV>
						<div class="pull-aright hidden"><?= $edit ?></div>
						<?= $otdel ?>
					</div>
				</TD>
				<TD class="text-center">
					<div class="inline pr5"><?= $edit ?></div>
					<?php
					if ( $iduser1 != $da['iduser'] ) {
						print $icon;
					}
					else {
						print '<i class="icon-lock-open gray" title="Себя нельзя блокировать"></i>';
					}
					?>
					<div class="inline pr5"><?= ($da['tip'] != 'Руководитель организации' ? '&nbsp;<A href="javascript:void(0)" onclick="doLoad(\'/content/admin/usereditor.php?action=edit&iduser='.$da['iduser'].'&clone=yes\');" title="Клонировать с правами"><i class="icon-paste green"></i></A>' : '') ?></div>
				</TD>
				<TD>
					<DIV class="ellipsis" title="<?= $da['tip'] ?>"><?= $da['tip'] ?></DIV><br>
					<DIV class="ellipsis"><?= $iuser ?></DIV>
				</TD>
				<TD class="text-center">
					<?php
					if ( $candelete['all'] < 1 && $iduser1 != $da['iduser'] ) {
						print '<A href="javascript:void(0)" onclick="cf=confirm(\'Вы действительно хотите удалить запись?\');if (cf)refresh(\'contentdiv\',\'/content/admin/users.php?iduser='.$da['iduser'].'&action=delete\');" title="Удалить"><i class="icon-cancel-circled red"></i></A>';
					}
					else {
						print '<i class="icon-cancel-circled gray" title="Удаление не возможно. У сотрудника есть записи: Клиент - '.$candelete['client'].', Контакты - '.$candelete['person'].', Сделки - '.$candelete['deal'].', Подчиненные - '.$candelete['subusers'].'"></i>';
					}
					?>
				</TD>
			</TR>
			<?php

		}

	}
	?>
	</tbody>
</table>

<div class="space-80"></div>

<script>

	if (isMobile) $('#zebra').addClass('rowtable');

	function deActivate(id, tip) {

		if (tip === 'off') cf = confirm('Вы хотите ДЕАКТИВИРОВАТЬ сотрудника!\n\nЭто действие закроет возможность доступа сотрудника в систему.\nВажно! Повторная активация возможна только через 3 дня. Продолжить?');

		if (tip === 'on') cf = confirm('Вы хотите АКТИВИРОВАТЬ сотрудника.\n\nЭто действие откроет доступ сотрудника в систему.\n Продолжить?');

		if (cf) {

			$.post('/content/admin/usereditor.php?action=activate&iduser=' + id, function (data) {

				DClose();
				getUsersList();

				$('#message').fadeTo(1, 1).css('display', 'block').html(data);

				setTimeout(function () {
					$('#message').fadeTo(1000, 0);
				}, 20000);

			});

		}

	}

	function getUsersList() {

		var str = $('#uform').serialize();
		$('#contentdiv').empty().load("/content/admin/users.table.php?" + str).append('<div id="loader" class="loader"><img src="/assets/images/loading.gif"> Вычисление...</div>');

	}

	function doLoadPost(url, action, iduser) {

		var str = "action=" + action + "&iduser=" + iduser;

		$('#dialog_container').css('height', $(window).height());
		$('#dialog').css('width', '500px');
		$('#resultdiv').empty().append('<div id="loader" class="loader"><img src="/assets/images/loading.svg"> Загрузка данных...</div>');

		$.post(url, str, function (data) {
			$('#resultdiv').empty().html(data)
				.ajaxComplete(function () {
					$('#dialog').center();
				});
		});

		$('#dialog_container').css('display', 'block');
		$('#dialog').css('display', 'block');

		return false;
	}
</script>