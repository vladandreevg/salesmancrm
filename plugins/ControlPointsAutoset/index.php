<?php
/* ============================ */
/*         SalesMan CRM         */
/* ============================ */
/* (C) 2022 Vladislav Andreev   */
/*       SalesMan Project       */
/*        www.isaler.ru         */
/*        ver. 2021.x           */

/* ============================ */

use Salesman\ControlPoints;
use Salesman\Elements;
use Salesman\Guides;

error_reporting(E_ERROR);
//ini_set('display_errors', 1);

$rootpath = realpath(__DIR__.'/../../');

include $rootpath."/inc/config.php";
include $rootpath."/inc/dbconnector.php";
include $rootpath."/inc/auth_main.php";
include $rootpath."/inc/settings.php";
include $rootpath."/inc/func.php";
include $rootpath."/inc/language/".$language.".php";

$access = $db -> getCol("SELECT iduser FROM ".$sqlname."user WHERE isadmin = 'on' and secrty = 'yes' and identity = '$identity' ORDER BY title");

if (!in_array($iduser1, $access)) {

	print '
		<TITLE>'.$lang['all']['Notification'].' - '.$productInfo['name'].'</TITLE>
		<LINK rel="stylesheet" type="text/css" href="assets/css/app.css">
		<LINK rel="stylesheet" href="assets/css/fontello.css">
		<SCRIPT type="text/javascript" src="assets/js/jquery/jquery-3.1.1.min.js"></SCRIPT>
		<SCRIPT type="text/javascript" src="assets/js/jquery/jquery-migrate-3.0.0.min.js"></SCRIPT>
		<SCRIPT type="text/javascript" src="assets/js/app.js"></SCRIPT>
		<div id="dialoge">
			<div class="warning p20" align="left" style="width:600px">
				<span><i class="icon-attention red icon-5x pull-left"></i></span>
				<b class="red uppercase">'.$lang['all']['Attention'].':</b><br><br>
				'.$lang['msg']['CantFindOrNotAccess'].'<br>
			</div>
			<div>
				<DIV id="ctitle" class="wp100 text-center" style="position:initial;">
					<DIV id="close" onClick="window.close();" class="p10 pl20 pr20 hand button redbtn" style="position:initial;"><i class="icon-cancel-circled red"></i>Закрыть</DIV>
				</DIV>
			</div>
		</div>
		
		<script type="text/javascript">
			$("#dialoge").center();
		</script>
		';

	exit();

}

$action = $_REQUEST['action'];

if ($action == 'edit.form') {

	$id   = (int)$_REQUEST['id'];
	$d = [];

	if ($id > 0) {
		$d = $db -> getRow("SELECT iduser, days FROM {$sqlname}complect_auto WHERE cpid = '$id'");
	}

	?>
	<DIV class="zagolovok"><B>Редактировать</B></DIV>
	<form action="index.php" method="post" enctype="multipart/form-data" name="Form" id="Form">
		<input type="hidden" id="action" name="action" value="edit.do">
		<input name="id" type="hidden" id="id" value="<?= $id ?>">

		<div id="formtabs" class="p5" style="max-height: 70vh; overflow-y: auto; overflow-x:hidden;">

			<div class="row">

				<div class="column12 grid-12">

					<span class="label">Длительность</span>
					<input type="number" name="days" id="days" class="wp100 required" value="<?= (int)$d['days'] ?>">

				</div>

			</div>

			<div class="row">

				<div class="column12 grid-12">

					<span class="label">Ответственный</span>
					<?php
					print (new Salesman\Elements) -> UsersSelect('iduser', [
						"active" => true,
						"class"  => "wp97",
						"sel"    => (int)$d['iduser'] > 0 ? (int)$d['iduser'] : -1
					]);
					?>

				</div>

			</div>

		</div>

		<hr>

		<div class="button--pane pull-aright">

			<A href="javascript:void(0)" onClick="saveForm()" class="button">Сохранить</A>&nbsp;
			<A href="javascript:void(0)" onClick="new DClose()" class="button">Отмена</A>

		</div>

	</form>

	<script>

		$(function () {

			$('#dialog').center();

		});

		function saveForm() {

			var str = $('#Form').serialize();
			var url = $('#Form').attr("action");

			$('#dialog_container').css('display', 'none');

			$.post(url, str, function (data) {

				if (data.status === 'ok') {

					Swal.fire({
						imageUrl: '/assets/images/success.svg',
						imageWidth: 50,
						imageHeight: 50,
						//position: 'bottom-end',
						html: '' + data.message + '',
						icon: 'info',
						showConfirmButton: false,
						timer: 3500
					});

				} else {

					Swal.fire({
						imageUrl: '/assets/images/error.svg',
						imageWidth: 50,
						imageHeight: 50,
						html: '' + data.message + '',
						icon: 'info',
						showConfirmButton: false,
						timer: 3500
					});

				}

				$app.list();

				new DClose();

			}, 'json');

		}

	</script>
	<?php

	exit();

}
if ($action == 'edit.do') {

	$id     = $_REQUEST['id'];
	$days   = (int)$_REQUEST['days'];
	$iduser = (int)$_REQUEST['iduser'];

	$xid = $db -> getOne("SELECT id FROM {$sqlname}complect_auto WHERE cpid = '$id'");

	if ((int)$xid > 0) {

		$db -> query("UPDATE {$sqlname}complect_auto SET ?u WHERE id = '$xid'", [
			"days"   => $days > 0 ? $days : NULL,
			"iduser" => $iduser > 0 ? $iduser : NULL
		]);

		$result = [
			"status"  => 'ok',
			"message" => 'Сохранено'
		];

	}
	else {

		$db -> query("INSERT INTO {$sqlname}complect_auto SET ?u", [
			"cpid"     => $id,
			"days"   => $days > 0 ? $days : NULL,
			"iduser" => $iduser > 0 ? $iduser : NULL,
			"identity" => $identity
		]);

		$result = [
			"status"  => 'ok',
			"message" => 'Обновлено'
		];

	}

	print json_encode_cyr($result);

	exit();

}

if ($action == 'list') {

	$list  = (new ControlPoints()) -> points();
	$steps = Guides ::Steps();

	$addons = $db -> getIndCol("cpid", "SELECT cpid, days FROM {$sqlname}complect_auto");
	$xaddons = $db -> getIndCol("cpid", "SELECT cpid, iduser FROM {$sqlname}complect_auto");

	$lists = [];

	foreach ($list as $id => $item) {

		$lists[] = [
			"id"        => $id,
			"title"     => $item['title'],
			"step"      => (int)$item['step'] > 0 ? (int)$item['step'] : null,
			"steptitle" => (int)$item['step'] > 0 ? $steps[ $item['step'] ]['title'].'%' : null,
			"user"      => (int)$xaddons[$id] > 0 ? current_user((int)$xaddons[$id], "yes") : null,
			"days"      => $addons[ $id ]
		];

	}

	print json_encode_cyr(["list" => $lists]);
	exit();

}

$about = json_decode(str_replace([
	"  ",
	"\t",
	"\n",
	"\r"
], "", file_get_contents("plugin.json")), true);

?>
<!DOCTYPE html>
<html lang="ru">
<head>
	<meta charset="utf-8">
	<title>Автоматические Контрольные точки</title>
	<link rel="stylesheet" href="/assets/css/app.css">
	<link rel="stylesheet" href="/assets/css/fontello.css">

	<!--красивые алерты-->
	<script type="text/javascript" src="/assets/js/sweet-alert2/sweetalert2.min.js"></script>
	<link type="text/css" rel="stylesheet" href="/assets/js/sweet-alert2/sweetalert2.min.css">

	<style>
		body {
			height : calc(100vh);
			/*overflow-y : auto;*/
		}

		.xbody {
			width                 : 100vw;
			margin                : 0 auto;
			display               : grid;
			grid-template-columns : 60% 40%;
			grid-template-rows    : 100vh;
		}

		.left--pane {
			height      : 100vh;
			overflow-y  : auto;
			width       : 90%;
			margin      : 0 auto;
			padding-top : 20px;
		}

		.right--pane {
			height        : 100vh;
			overflow-y    : auto;
			background    : var(--white);
			padding-right : 20px;
			padding-left  : 20px;
		}

		.button.small.dotted {
			padding : 2px 5px !important;
		}

		.gray a.button {
			border : 1px dotted var(--gray-litedarkblue) !important;
		}

		.gray:hover a > i {
			color : inherit;
		}

		#help h1 {
			font-size : 2em;
		}

		#help h2 {
			font-size : 1.6em;
		}

		#help h3 {
			color     : var(--blue);
			font-size : 1.4em;
		}

		table th {
			font-size     : 0.9rem;
			background    : #E6E9ED;
			border-bottom : 1px dotted #CCC;
		}

		table tbody td {
			border-bottom : 1px dotted #CCC;
			font-size     : 1.05em;
		}

		#help blockquote {
			border        : 1px solid #CCC;
			border-left   : 3px solid rgba(255, 193, 7, 1);
			padding-left  : 50px;
			padding-right : 10px;
			margin-left   : 0;
			border-radius : 5px;
			background    : #FFF;
			position      : relative;
			display       : block;
			width         : 89%;
		}

		#help blockquote:before {
			font-family : "fontello";
			font-size   : 2em;
			position    : absolute;
			top         : 20px;
			left        : 10px;
			color       : #DDD;
			content     : "\e8b8";
		}

		#help blockquote ul {
			margin-left  : 5px;
			padding-left : 10px;
			list-style   : circle;
		}

		#help blockquote li {
			margin-left : 5px;
			padding     : 0 !important;
		}

		#help blockquote > h3,
		#help blockquote > h2 {
			color          : var(--red);
			margin-top     : 15px;
			margin-bottom  : -5px;
			text-transform : uppercase;
		}

		.tagbox .tag {
			/*border: 1px dotted #79B7E7;
			background: #D0E5F5;*/
			padding: 5px;
			display: inline-block;
			overflow: hidden !important;
			margin-bottom: 2px;
			margin-right: 2px;
			-moz-border-radius: 1px;
			-webkit-border-radius: 1px;
			border-radius: 5px;
			cursor: pointer;
		}

	</style>

</head>
<body>

<div id="dialog_container" class="dialog_container">

	<div class="dialog-preloader">
		<img src="/assets/images/rings.svg" width="128">
	</div>
	<div class="dialog text-left" id="dialog">
		<div class="close" title="Закрыть или нажмите ^ESC"><i class="icon-cancel"></i></div>
		<div id="resultdiv"></div>
	</div>

</div>

<div class="xbody">

	<div class="left--pane">

		<div class="flex-container float wp100 mb20">

			<div class="flex-string w80">
				<img src="data:image/svg+xml;base64,<?php echo $about['iconSVGinBase64'] ?>" class="icon" width="50" height="50">
			</div>
			<div class="flex-string float">
				<div class="fs-20 flh-11 Bold"><?php echo $about['name'] ?></div>
				<div class="pl10"><?php echo $about['package'] ?></div>
			</div>

		</div>

		<h2><i class="icon-monitor blue"></i> Контрольные точки</h2>

		<div class="space-0"></div>

		<div class="p0 mt10 mb10" data-id="tasks">

			<div class="flex-container box--child p10 fs-09 Bold gray2 float bluebg-sub shadow sticked--top" data-id="{{id}}">

				<div class="flex-string float">Название Контрольной точки</div>
				<div class="flex-string w120 text-center hidden-ipad">Этап сделки</div>
				<div class="flex-string w120 text-center hidden-ipad">Длительность, дн.</div>
				<div class="flex-string w80 hidden-ipad"></div>

			</div>

			<div class="trow"></div>

		</div>

	</div>
	<div class="right--pane">

		<div style="overflow-wrap: normal;word-wrap: break-word;word-break: normal;line-break: strict;-webkit-hyphens: auto; -moz-hyphens: auto; hyphens: auto; width: 98%; box-sizing: border-box;">

			<?php
			$html      = file_get_contents("readme.md");
			$Parsedown = new Parsedown();

			$help = $Parsedown -> text($html);

			$help = str_replace("{{package}}", $about['package'], $help);
			$help = str_replace("{{version}}", $about['version'], $help);
			$help = str_replace("{{versiondate}}", $about['versiondate'], $help);

			print $help;
			?>

			<div class="space-50"></div>

		</div>

	</div>

	<!--шаблон блока-->
	<div id="template" type="x-tmpl-mustache" class="hidden">

		{{#list}}
		<div class="ha bgwhite border-bottom">
			<div class="flex-container box--child p5 float" data-id="{{id}}">

				<div class="flex-string float p5">
					<div class="Bold fs-12">{{title}}</div>
					<div class="tagbox mt5">
						{{#user}}
						<div class="tag greenbg-sub dotted fs-09 mt5">Ответственный: <b>{{user}}</b></div>
						{{/user}}
						{{#step}}
						<div class="tag bluebg-sub dotted fs-09 mt5">Связано с этапом</div>
						{{/step}}
					</div>
				</div>
				<div class="flex-string w120 p5 text-center">{{steptitle}}</div>
				<div class="flex-string w120 p5 text-center">{{days}}</div>
				<div class="flex-string w80 fs-09 text-right p5">
					<a href="javascript:void(0)" onclick="$app.edit({{id}})" class="button small bluebtn dotted" title="Редактировать"><i class="icon-pencil"></i></a>
				</div>

			</div>
		</div>
		{{/list}}

	</div>

</div>

<script src="/assets/js/jquery/jquery-3.4.1.min.js"></script>
<script src="/assets/js/jquery/jquery-migrate-3.0.0.min.js"></script>
<script src="/assets/js/jquery/jquery-ui.min.js?v=2019.4"></script>
<script src="/assets/js/moment.min.js"></script>
<script src="/assets/js/app.extended.js"></script>

<script type="text/javascript" src="/assets/js/mustache/mustache.js"></script>
<script type="text/javascript" src="/assets/js/mustache/jquery.mustache.js"></script>

<script>

	$(function () {

		$app.list();

	});

	$app = {
		edit: function (id) {
			doLoad("?action=edit.form&id=" + id);
		},
		list: function () {

			$.getJSON('index.php?action=list', function (data) {

				let template = $('#template').html();
				Mustache.parse(template);// optional, speeds up future uses

				let rendered = Mustache.render(template, data);
				$('.trow').empty().append(rendered);

			});

		}
	}
</script>

</body>
</html>
