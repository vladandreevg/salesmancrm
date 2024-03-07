<?php
/* ============================ */
/*         SalesMan CRM         */
/* ============================ */
/* (C) 2016 Vladislav Andreev   */
/*       SalesMan Project       */
/*        www.isaler.ru         */
/*        ver. 2017.x           */
/* ============================ */

use Salesman\Project;

error_reporting(E_ERROR);

global $rootpath;
require_once $rootpath."/inc/head.card.php";
flush();

$comid  = $_REQUEST['comid'];
$action = $_REQUEST['action'];

//Обеспечиваем доступ только для приглашенных пользователей
$users = $db -> getCol("select iduser from ".$sqlname."comments_subscribe WHERE idcomment = '$comid' and identity = '$identity' ORDER BY id");

$resultt   = $db -> getRow("select * from ".$sqlname."comments where id = '$comid' and identity = '$identity'");
$clid      = (int)$resultt["clid"];
$pid       = (int)$resultt["pid"];
$did       = (int)$resultt["did"];
$idproject = (int)$resultt["project"];
$iduser    = (int)$resultt["iduser"];
$datum     = $resultt["datum"];
$isClose   = $resultt["isClose"];
$dateClose = $resultt["dateClose"];


if ($isClose == 'yes') {

	$s    = 'Активировать';
	$t    = '&nbsp;<i class="icon-lock red" title="Закрыто"></i><sup class="fs-05">'.get_sfdate($dateClose).'</sup>';
	$icon = 'icon-lock-open green';

}
else {

	$s    = 'Закрыть';
	$t    = '';
	$icon = 'icon-lock red';

}

$accsess = in_array($iduser1, $users) || $iduser1 == $iduser || $isadmin == 'on';

$items = [];

if ($idproject > 0) {

	//require_once "./inc/class/Project.php";

	$project = Project::info($idproject);

	$tip = "Проект";
	$url = '<a href="javascript:void(0)" onclick="openProject(\''.$idproject.'\')" class="black"><i class="icon-buffer green"></i>&nbsp;'.$project['project']['name'].'</a>&nbsp;';

	if($clid == 0) {
		$clid = $project['project']['clid'];
	}

	if($did == 0) {
		$did = $project['project']['did'];
	}

	$items[] = [
		"tip" => $tip,
		"url" => $url
	];

}
if ($clid > 0) {

	$tip = "Клиент";
	$url = '<a href="javascript:void(0)" onclick="openClient(\''.$clid.'\')" class="black"><i class="icon-building broun"></i>&nbsp;'.current_client($clid).'</a>';

	$items[] = [
		"tip" => $tip,
		"url" => $url
	];

}
if ($pid > 0) {

	$tip = "Контакт";
	$url = '<a href="javascript:void(0)" onclick="openPerson(\''.$pid.'\')" class="black"><i class="icon-user-1 blue"></i>&nbsp;'.current_person($pid).'</a>';

	$items[] = [
		"tip" => $tip,
		"url" => $url
	];

}
if ($did > 0) {

	$tip = "Сделка";
	$url = '<a href="javascript:void(0)" onclick="openDogovor(\''.$did.'\')" class="black"><i class="icon-briefcase fiolet"></i>&nbsp;'.current_dogovor($did).'</a>';

	$items[] = [
		"tip" => $tip,
		"url" => $url
	];

}

$btn = '';

if ($iduser1 == $iduser && $isClose != 'yes') {
	$btn .= '
	<div onclick="editComment(\''.$comid.'\',\'edit\', \'0\');" class="flex-container p10 ha">
		<div class="flex-string wp20"><i class="icon-pencil broun"></i></div>
		<div class="flex-string wp80">Изменить</div>
	</div>';
}
elseif ($iduser1 == $iduser && $isClose == 'yes') {
	$btn .= '
	<div class="flex-container p10 ha">
		<div class="flex-string wp20"><i class="icon-pencil gray2"></i></div>
		<div class="flex-string wp80 gray">Изменить. Не доступно - обсуждение закрыто</div>
	</div>';
}

if ( $accsess && $isClose != 'yes') {
	$btn .= '
	<div onclick="editComment(\''.$comid.'\',\'subscribe.user\');" title="Пригласить коллег" class="flex-container p10 ha">
		<div class="flex-string wp20"><i class="icon-users-1 blue"></i></div>
		<div class="flex-string wp80">Пригласить коллег</div>
	</div>';
}
elseif ( $accsess && $isClose == 'yes') {
	$btn .= '
	<div title="Пригласить коллег" class="flex-container p10 ha">
		<div class="flex-string wp20"><i class="icon-users-1 gray2"></i></div>
		<div class="flex-string wp80 gray">Пригласить коллег. Не доступно - обсуждение закрыто</div>
	</div>';
}

if ($isadmin == 'on' || $iduser == $iduser1) {
	$btn .= '
	<div onclick="cf=confirm(\'Вы действительно хотите выполнить?\');if (cf)editComment(\''.$comid.'\', \'close\', \'\')" class="flex-container p10 ha">
		<div class="flex-string wp20"><i class="'.$icon.'"></i></div>
		<div class="flex-string wp80">'.$s.'</div>
	</div>';
}

if ($isadmin == 'on' || $iduser == $iduser1) {
	$btn .= '
	<div onclick="cf=confirm(\'Вы действительно хотите удалить запись?\');if (cf)editComment(\''.$comid.'\', \'delete.card\', \'\')" class="flex-container p10 ha">
		<div class="flex-string wp20"><i class="icon-trash red"></i></div>
		<div class="flex-string wp80">Удалить</div>
	</div>';
} ?>

<style>
	body {
		margin-top : 4.5em;
		height     : calc(100vh - 4.5em);
	}
</style>

<div class="fixx">

	<DIV id="head">
		<DIV id="ctitle">
			<div class="back2menu"><a href="/" title="Рабочий стол"><i class="icon-home"></i></a></div>
			<span class="blue">Обсуждение:</span>&nbsp;<b><span class="elipsis"><?= $title ?></span></b><?= $t ?>
			<input type="hidden" name="isCard" id="isCard" value="yes">
			<input type="hidden" name="isComment" id="isComment" value="yes">
			<DIV id="close" onclick="window.close();">Закрыть</DIV>
		</DIV>
	</DIV>

</div>

<DIV id="telo">

	<DIV class="leftcol" id="tab-1">

		<fieldset>
			<legend>Информация</legend>
			<DIV id="obj" class="mb10 fs-11">

				<?php
				foreach ($items as $item){

					print '
					<DIV class="flex-container box--child mt10">
						<div class="flex-string wp15 gray2 text-right pr10 Bold">'.$item['tip'].':</div>
						<div class="flex-string wp85 Bold">'.$item['url'].'</div>
					</DIV>
					';

				}
				?>

				<DIV class="flex-container box--child mt10">
					<div class="flex-string wp15 gray2 text-right pr10 Bold">Автор:</div>
					<div class="flex-string wp85 Bold"><i class="icon-user-1 blue"></i>&nbsp;<?= current_user($iduser) ?></div>
				</DIV>

				<DIV class="flex-container box--child mt10">
					<div class="flex-string wp15 gray2 text-right pr10 Bold">Начата:</div>
					<div class="flex-string wp85 Bold"><i class="icon-calendar-1 blue"></i>&nbsp;<?= get_sfdate3($datum) ?></div>
				</DIV>

			</DIV>
		</fieldset>

		<fieldset>

			<legend>Тема обсуждения</legend>

			<div class="text-right mr10">
				<DIV class="hand relativ tagsmenuToggler">
					<div class="Bold blue" data-id="fhelper"><i class="icon-angle-down broun"></i>Действия</div>
					<div class="tagsmenu right hidden" id="fhelper" style="right:0; top: 100%">
						<div class="blok border--bottom w200 text-left"><?=$btn?></div>
					</div>
				</DIV>
			</div>

			<DIV id="theme" class="mt10" data-block="theme"></DIV>

		</fieldset>

	</DIV>

	<DIV class="rightcol" id="tab-2">
		<fieldset class="bgwhite">
			<DIV class="batton-edit">
				<?php
				if ( $accsess && $isClose != 'yes') print '<a href="javascript:void(0)" onclick="editComment(\'\', \'add\', \''.$comid.'\');" title="Ответить"><i class="icon-chat-empty broun"></i>&nbsp;&nbsp;Ответить</a>&nbsp;&nbsp;';
				?>
				<a href="javascript:void(0)" onclick="settab('')"><i class="icon-arrows-cw broun"></i>&nbsp;Обновить</a>
			</DIV>
			<br>
			<legend>Беседы</legend>
			<DIV id="comments"></DIV>
		</fieldset>
	</DIV>

</DIV>

<DIV class="space-40"></DIV>

<script>

	//устанавливаем переменную
	//что мы в карточке
	isCard = true;

	//признак того, что открыт фрейм
	isFrame = <?=($_REQUEST['face']) ? 'true' : 'false'?>;

	$(function () {

		settab();

	});

	function mm() {
		$('#comments').load('/modules/comments/card.comments.php?comid=<?=$comid?>&action=comment.list');
	}

	Visibility.every(60000, mm);

	function settab() {

		$('div[data-block="theme"]').load("/modules/comments/card.comments.php?action=theme.card&comid=<?=$comid?>").append('<img src="/assets/images/loading.svg">')
			.ajaxComplete(function () {

				if (typeof cardCallback === 'function') {
					cardCallback();
				}

				CardLoad.fire({
					etype: 'commentCardTheme'
				});

			});

		$('#comments').load("/modules/comments/card.comments.php?action=comment.list&comid=<?=$comid?>").append('<img src="/assets/images/loading.svg">')
			.ajaxComplete(function () {

				if (typeof cardCallback === 'function') {
					cardCallback();
				}

				CardLoad.fire({
					etype: 'commentCardComments'
				});

			});

	}
</script>
</body>
</html>