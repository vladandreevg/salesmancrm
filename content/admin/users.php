<?php
/* ============================ */
/*         SalesMan CRM         */
/* ============================ */
/* (C) 2016 Vladislav Andreev   */
/*       SalesMan Project       */
/*        www.isaler.ru         */
/*        ver. 2017.x           */
/* ============================ */


use Salesman\User;

error_reporting(E_ERROR);
header("Pragma: no-cache");

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

if (empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) != 'xmlhttprequest') {

	print '<div class="bad text-center"><br>Доступ запрещен.<br>Обратитесь к администратору.<br><br></div>';
	exit();

}

$helper = json_decode(file_get_contents($rootpath.'/cash/helper.json'), true);

if (isset($action) && $action == "delete") {

	$db -> query("delete from ".$sqlname."user where iduser = '$iduser' and identity = '$identity'");

}

$users = User ::userOrgChart();

function drowChart($list = []): string {

	$html     = '';
	$iduser1  = $GLOBALS['iduser1'];
	$db       = $GLOBALS['db'];
	$sqlname  = $GLOBALS['sqlname'];
	$identity = $GLOBALS['identity'];

	foreach ($list as $item) {

		$dd = 10;
		
		$avatar = ($item['avatar']) ? "/cash/avatars/".$item['avatar'] : "/assets/images/noavatar.png";
		//$dd     = ($item['adate'] != '0000-00-00' && $item['adate'] != null) ? abs(diffDate2($item['adate'])) : 10;

		$otdel = $db -> getRow("SELECT title, uid FROM ".$sqlname."otdel_cat WHERE idcategory = '".$item['otdel']."' and identity = '$identity'");

		$html .= '
		<li data-id="'.$item['id'].'">
			
			<div class="user-container relativ">
			
				<div class="" style="height: 100%;">
				
					<div class="avatar--mini pull-left mr10" style="background: url('.$avatar.'); background-size:cover;"></div>
					
				</div>
				
				<div>
			
					<div class="pull-right">
					
						'.($item['active'] == 'yes' && stripos(texttosmall($item['tip']), 'руководитель') !== false ? '<A href="javascript:void(0)" onclick="doLoad(\'content/admin/usereditor.php?action=edit&otdel='.$item['otdel'].'&ruk='.$item['id'].'\');" title="Добавить подчиненного"><i class="icon-plus-circled green"></i></A>' : '').'
						'.($item['active'] == 'yes' ? '&nbsp;<A href="javascript:void(0)" onclick="doLoad(\'content/admin/usereditor.php?action=edit&iduser='.$item['id'].'\');" title="Редактировать"><i class="icon-pencil blue"></i></A>' : '').'
						'.($item['tip'] != 'Руководитель организации' ? '&nbsp;<A href="javascript:void(0)" onclick="doLoad(\'content/admin/usereditor.php?action=edit&iduser='.$item['id'].'&clone=yes\');" title="Клонировать с правами"><i class="icon-paste green"></i></A>' : '').'
						'.( User ::canDeleted( $item[ 'id']) == true ? '&nbsp;<A href="javascript:void(0)" onclick="cf=confirm(\'Вы действительно хотите удалить запись?\');if (cf)refresh(\'contentdiv\',\'content/admin/users.php?iduser='.$item[ 'id'].'&action=delete\');" title="Удалить"><i class="icon-cancel-circled red" title="Удалить"></i></A>' : '<i class="icon-cancel-circled gray" title="Удаление не возможно. У сотрудника есть записи"></i>').'
						
					</div>
					
					<div class="hand" onclick="viewUser(\''.$item['id'].'\')">
				
						<div class="Bold uppercase mb5">'.$item['name'].''.($item['isadmin'] == 'on' ? '<sup><i class="icon-star broun" title="Есть права Админа"></i></sup>' : '').'</div>
						<div class="block fs-09 flh-11">Роль: <b>'.$item['tip'].'</b></div>
						'.($item['post'] != '' ? '<div class="block fs-09 flh-11">Должность: <b>'.$item['post'].'</b></div>' : '').'
						'.($otdel['title'] != '' ? '<div class="block fs-09 flh-11">Отдел: <b>'.$otdel['uid'].' '.$otdel['title'].'</b></div>' : '').'
					
					</div>
					
					<div class="block bottom1 pt10 fs-07 pr5">
						'.($dd >= 3 && $item['id'] != $iduser1 ? '<a href="javascript:void(0)" onclick="deActivate(\''.$item['id'].'\')" title="Повторная активация возможна через 3 дня" class="gray1"> Блокировать <i class="icon-lock red"></i></a>' : '').'
						'.($item['id'] == $iduser1 ? '<div title="Себя нельзя блокировать :)" class="gray"> Блокировать <i class="icon-lock gray2"></i></div>' : '').'
					</div>
					
				</div>
				
				<div class=""></div>
				
			</div>
			
			'.(!empty($item['users']) ? '<ul>'.drowChart($item['users']).'</ul>' : '').'
		
		</li>
		';

	}

	return $html;

}

?>
<STYLE type="text/css">

	.knopka {
		position : fixed;
		top      : 100px;
		right    : 10px;
		padding  : 5px;
		z-index  : 1000;
	}

	.tree {
		position   : relative;
		margin-top : 10px;
		padding    : 10px;
	}
	.tree ul {
		padding-left : 5px;
		list-style   : none;
	}
	.tree ul li {
		position           : relative;
		padding-top        : 5px;
		/*padding-bottom     : 5px;*/
		padding-left       : 40px;
		white-space        : nowrap;
		-webkit-box-sizing : border-box;
		-moz-box-sizing    : border-box;
		box-sizing         : border-box;
		display            : table;
	}
	.tree ul li:before {
		position      : absolute;
		top           : 25px;
		left          : 0;
		width         : 40px;
		height        : 1px;
		margin        : auto;
		content       : '';
		border-bottom : 2px solid var(--gray-sub);
	}
	.tree ul li:after {
		position    : absolute;
		top         : -9px;
		bottom      : 0;
		left        : 0;
		width       : 1px;
		height      : calc(100% + 35px);
		content     : '';
		border-left : 2px solid var(--gray-sub);
	}
	.tree ul li:last-child:after {
		height : 35px;
	}

	/*первый элемент*/
	.tree > ul > li:before {
		width : 0 !important;
	}
	.tree > ul > li:after {
		height : 0 !important;
	}

	.tree ul a {
		cursor : pointer;
	}
	.tree ul a:hover {
		text-decoration : none;
	}
	.tree .user-container {
		display       : grid;
		grid-template-columns: 60px auto 20px;
		justify-items: stretch;
		max-width     : 400px;
		width     : 330px;
		padding: 10px 10px 10px 15px;
		margin-bottom : 10px;
		background    : var(--gray);
		border        : 0;
		border-radius : 5px;
		color         : var(--gray-darkblue);
		box-shadow    : rgba(0, 0, 0, 0.5) 0 1px 2px;
	}
	.tree ul .avatar--mini{
		border: 0;
		width: 50px;
		height: 50px;
		-moz-box-shadow:    inset 0 0 10px #000000;
		-webkit-box-shadow: inset 0 0 10px #000000;
		box-shadow:         inset 0 0 10px #000000;
	}
	.tree ul .user-container {
		background : #FFE3D7;
	}
	.tree ul .avatar--mini {
		border-color : #E74B3B;
	}
	.tree ul ul .user-container {
		background : #B6EFCE;
	}
	.tree ul ul .avatar--mini {
		border-color : #159D82;
	}
	.tree ul ul ul .user-container {
		background : #CBE4F5;
	}
	.tree ul ul ul .avatar--mini {
		border-color : #1C6697;
	}
	.tree ul ul ul ul .user-container {
		background : #FF6;
	}
	.tree ul ul ul ul .avatar--mini {
		border-color : #FC0;
	}

	-->
</STYLE>

<DIV style="position:fixed;top:65px;right:15px; z-index:1001">
	<a href="javascript:void(0)" onclick="help('<?= $helper['users'] ?>')"><i class="icon-help-circled blue"></i><b class="blue">Помощь / Сотрудники</b></a>
</DIV>

<h2>&nbsp;Раздел: "Сотрудники"</h2>
<hr>

<div class="infodiv">
	<b>Показаны только активные сотрудники.</b> Всех, включая не активных, можно посмотреть в разделе "<a href="#users.table" title="Перейти в раздел">Сотрудники (таблица)</a>".
</div>

<DIV class="mbody" id="mbody">

	<div class="knopka">

		<div class="relativ" id="tagsmenu">

			<?php if ($_REQUEST['t'] == '') { ?>
				<a href="javascript:void(0)" onclick="doLoad('content/admin/usereditor.php?action=edit');" class="button"><i class="icon-plus-circled"></i> Добавить</a>
				<!--<a href="javascript:void(0)" onclick="doLoad('admin/usereditor.php?action=edit');" class="button"><i class="icon-plus-circled"></i> Добавить +</a>-->
				<!--<a href="admin/users.php?t=print" target="_blank" class="button">Распечатать</a>-->
				<a href="javascript:void(0)" class="button tagsmenuToggler"><i class="icon-help"></i> Типы Ролей&nbsp;<i class="icon-angle-down" id="mapic"></i></a>

				<div class="hidden tagsmenu fs-10 bgwhite p10 noprint" style="max-height: 70vh; overflow-y: auto; z-index: 1002; top:30px; border: 1px solid #CCC">

					<div class="mb10">
						<b>РУКОВОДИТЕЛЬ ОРГАНИЗАЦИИ</b><br>
						отображается информация со всех отделов компании. Кроме того ему доступны напоминания подчиненных, а так же модуль Аналитика, в котором выводится сводная информация по всей организации.
					</div>
					<div class="mb10">
						<b>РУКОВОДИТЕЛЬ с доступом</b><br>
						отображается информация со всех отделов компании. Кроме того ему доступны напоминания подчиненных, а так же модуль Аналитика, в котором выводится сводная информация по организации. При этом у сотрудника с такой ролью отсутствуют права на редактирование информации, не принадлежащей ему.
					</div>
					<div class="mb10">
						<b>РУКОВОДИТЕЛЬ ПОДРАЗДЕЛЕНИЯ</b><br>
						имеет в подчинении руководителей отделов и их сотрудников. Соответственно имеет доступ к информации сотрудников его подразделения.
					</div>
					<div class="mb10">
						<b>РУКОВОДИТЕЛЬ ОТДЕЛА</b><br>
						имеет доступ к информации по клиентам и договорам Клиентов, закрепленных за ним и сотрудниками его отдела. Кроме того ему доступны напоминания подчиненных, а так же модуль Аналитика, в котором выводится сводная информация по его отделу;
					</div>
					<div class="mb10">
						<b>МЕНЕДЖЕР</b><br>
						имеет доступ к информации только по клиентам и договорам Клиентов, закрепленных за ним. Так же он может редактировать только информацию, по которой он является Ответственным;
					</div>
					<div class="mb10">
						<b>ПОДДЕРЖКА ПРОДАЖ</b><br>
						осуществляет поддержку менеджеров по продажам. Видит Организации, Персоны, Сделки всех сотрудников (в т.ч. Руководителя организации) без возможности редактирования; Может строить отчеты по всем сотрудникам (если есть доступ в Аналитику); Может добавлять активности; Может ставить себе и всем пользователям напоминания; Может формировать спецификацию; Не может изменять комплектность сделок;
					</div>
					<div class="mb10">
						<b>АДМИНИСТРАТОР</b><br>
						имеет доступ в раздел настройки системы, имеет логин «admin». Также любому сотруднику могут быть назначены права Администратора с предоставлением доступа в Панель управления.
					</div>
					<div class="mb10">
						<b>СПЕЦИАЛИСТ</b><br>
						Сотрудник, которому предоставлен доступ только к Календарю и Напоминаниям.
					</div>

				</div>
			<?php } ?>

		</div>

	</div>

	<div class="tree">

		<ul>
			<?= drowChart($users) ?>
		</ul>

	</div>

</DIV>

<div class="pagerefresh refresh--icon admn green" onclick="doLoad('content/admin/usereditor.php?action=edit');" title="Добавить"><i class="icon-plus-circled"></i></div>
<div class="pagerefresh refresh--icon admn orange" onclick="openlink('https://salesman.pro/docs/16')" title="Документация"><i class="icon-help"></i></div>

<div class="space-100"></div>

<script>

	$(".nano").nanoScroller();

	function deActivate(id) {

		var cf = confirm('Вы хотите изменить статус сотрудника!\n\nЭто действие изменит возможность доступа сотрудника в систему.\nВажно! Повторная активация возможна только через 3 дня. Продолжить?');

		if (cf) {
			$.post('content/admin/usereditor.php?action=activate&iduser=' + id, function (data) {

				DClose();

				$('#contentdiv').load('content/admin/users.php');
				$('#message').fadeTo(1, 1).css('display', 'block').html(data);

				setTimeout(function () {
					$('#message').fadeTo(1000, 0);
				}, 20000);

			});
		}
	}

	function getUsersList() {

		$('#contentdiv').empty().load("content/admin/users.php").append('<div id="loader" class="loader"><img src="/assets/images/loading.gif"> Вычисление...</div>');

	}

</script>