<?php
/* ============================ */
/*         SalesMan CRM         */
/* ============================ */
/* (C) 2016 Vladislav Andreev   */
/*       SalesMan Project       */
/*        www.isaler.ru         */
/*        ver. 2017.x           */
/* ============================ */

error_reporting(E_ERROR);
header("Pragma: no-cache");

$rootpath = dirname(__DIR__, 2);

include $rootpath."/inc/config.php";
include $rootpath."/inc/dbconnector.php";
include $rootpath."/inc/auth.php";
include $rootpath."/inc/func.php";
include $rootpath."/inc/settings.php";

$thisfile = basename(__FILE__);

$action = $_REQUEST['action'];

if ($action == 'view') {

	$id = (int)$_REQUEST['id'];

	$event = $db -> getRow("SELECT * FROM ".$sqlname."webhooklog WHERE id = '$id' and identity = '$identity'");

	$answr = htmlspecialchars($event['response']);

	//print_r($answr);
	?>
	<div class="zagolovok">Просмотр</div>

	<div id="formtabse" style="overflow-y: auto; max-height: 80vh; overflow-x: hidden" class="p5 bgwhite1 graybg-sub pr10">

		<div class="bgwhite fcontainer flex-vertical p0 border--bottom box--child fs-10 mb10">

			<div class="flex-container p10">

				<div class="flex-string wp100 uppercase fs-07 Bold gray2 mb10">Дата</div>
				<div class="flex-string wp100 relativ fs-10 Bold"><?= get_sfdate($event['datum']) ?></div>

			</div>

			<div class="flex-container p10">

				<div class="flex-string wp100 uppercase fs-07 Bold gray2 mb10">Событие</div>
				<div class="flex-string wp100 relativ fs-10 Bold"><?= $event['event'] ?></div>

			</div>

			<div class="flex-container p10">

				<div class="flex-string wp100 uppercase fs-07 Bold gray2 mb10">Отправлены данные</div>
				<div class="flex-string wp100 relativ fs-10 Bold">
					<code><?= array2string(json_decode($event['query'], true), "<br>", "&nbsp;&nbsp;&nbsp;") ?></code>
				</div>

			</div>

			<div class="flex-container p10">

				<div class="flex-string wp100 uppercase fs-07 Bold gray2 mb10">Ответ</div>
				<div class="flex-string wp100 relativ fs-10 Bold">
					<code><?= str_replace([
							"\\r",
							"\\n",
							"\\t"
						], "", $answr) ?></code>
				</div>

			</div>

		</div>

	</div>

	<hr>

	<div class="pull-aright button--pane">

		&nbsp;<a href="javascript:void(0)" onclick="deleteHookLog(<?= $id ?>)" class="button">Удалить</a> &nbsp;<a href="javascript:void(0)" onclick="DClose()" class="button">Закрыть</a>

	</div>

	<script>

		$(function () {

			if ($(window).width() > 990) {

				$('#dialog').css({'width': '600px'});

			}
			else {

				$('#dialog').css('width', '80%');

			}

			$('#dialog').center();

		});

	</script>
	<?php

	exit();

}

if ($action == 'clear') {

	$db -> query("DELETE FROM {$sqlname}webhooklog WHERE datum < NOW()");

	print "Готово";

}

if ($action == 'delete') {

	$id = $_REQUEST['id'];

	$db -> query("DELETE FROM {$sqlname}webhooklog WHERE id = '$id'");

	print "Готово";

}

if (!$action) {

	$page = $_GET['page'];

	$lines_per_page = 50; //Стоимость записей на страницу

	$query     = "SELECT * FROM ".$sqlname."webhooklog WHERE id > 0 and identity = '$identity' ORDER BY datum DESC";
	$result    = $db -> query($query);
	$all_lines = $db -> numRows($result);
	if (!isset($page) or empty($page) or $page <= 0) {
		$page = 1;
	}
	else {
		$page = (int)$page;
	}
	$page_for_query = $page - 1;
	$lpos           = $page_for_query * $lines_per_page;

	$query .= " LIMIT $lpos,$lines_per_page";

	$result      = $db -> query($query);
	$count_pages = ceil($all_lines / $lines_per_page);
	?>

	<div style="position:fixed; top:60px; right:10px; z-index:100000">

		<a href="javascript:void(0)" onclick="clearHookLog()" class="sbutton">Очистить</a>

	</div>

	<TABLE>
		<thead class="hidden-iphone sticked--top">
		<TR class="th40">
			<TH class="w100">Дата</TH>
			<TH class="w160">Тип события</TH>
			<TH class="wp40">Запрос</TH>
			<TH>Ответ</TH>
		</TR>
		</thead>
		<TBODY>
		<?php
		while ($data = $db -> fetch($result)) {
			?>
			<TR class="th40 ha hand" onclick="doLoad('/content/admin/<?php
			echo $thisfile; ?>?action=view&id=<?= $data['id'] ?>')">
				<TD title="<?= get_sfdate($data['datum']) ?>"><?= get_sfdate($data['datum']) ?></TD>
				<TD title="<?= $data['event'] ?>" title="Просмотр"><?= $data['event'] ?></TD>
				<TD>
					<div class="ellipsis"><?= $data['query'] ?></div>
				</TD>
				<TD>
					<div class="text-wrap"><?= html2text($data['response']) ?></div>
				</TD>
			</TR>
			<?php
		}
		?>
		</TBODY>
	</TABLE>

	<div id="pagecontainer" class="pagediv">
		<div class="page mainbg" id="pagediv">
			<?php
			if ($count_pages == 0) {
				$count_pages = 1;
			}
			print " Стр.".$page." из ".$count_pages."&nbsp;";
			if ($count_pages > 1) {

				for ($g = 1; $g <= $count_pages; $g++) {
					if ($page == $g and $g == 1) { ?>
						&nbsp;<a href="javascript:void(0)" onclick="changepagepay('<?= ( $g + 1 ) ?>')" title="Следующая"><i class="icon-angle-right"></i></a>&nbsp;&nbsp;
						<a href="javascript:void(0)" onclick="changepagepay('<?= $count_pages ?>')" title="Последняя"><i class="icon-angle-double-right"></i></a>&nbsp;
						<?php
					}
					if ($page == $g and $g == 2) {
						?>
						&nbsp;						<a href="javascript:void(0)" onclick="changepagepay('1')" title="Начало"><i class="icon-angle-double-left"></i></a>&nbsp;
						<?php
						if ($count_pages > 2) { ?>
							&nbsp;
							<a href="javascript:void(0)" onclick="changepagepay('<?= ( $g + 1 ) ?>')" title="Следующая"><i class="icon-angle-right"></i></a>&nbsp;&nbsp;
							<a href="javascript:void(0)" onclick="changepagepay('<?= $count_pages ?>')" title="Последняя"><i class="icon-angle-double-right"></i></a>&nbsp;
							<?php
						}
					}
					if ($page == $g and $g > 2) {
						?>
						&nbsp;
						<a href="javascript:void(0)" onclick="changepagepay('1')" title="Начало"><i class="icon-angle-double-left"></i></a>&nbsp;&nbsp;
						<a href="javascript:void(0)" onclick="changepagepay('<?= ( $g - 1 ) ?>')" title="Предыдущая"><i class="icon-angle-left"></i></a>&nbsp;
						<?php
						if ($g < $count_pages) { ?>
							&nbsp;<a href="javascript:void(0)" onclick="changepagepay('<?= ( $g + 1 ) ?>')" title="Следующая"><i class="icon-angle-right"></i></a>&nbsp;&nbsp;
							<a href="javascript:void(0)" onclick="changepagepay('<?= $count_pages ?>')" title="Последняя"><i class="icon-angle-double-right"></i></a>&nbsp;
							<?php
						}
					}
				}
			}
			?>
		</div>
	</div>

	<div class="space-40"></div>

	<SCRIPT>

		function changepagepay(num) {
			$('#contentdiv').load('/content/admin/<?php echo $thisfile; ?>?page=' + num + '&filter=' + $('#filter').val());
		}

		function clearHookLog() {

			Swal.fire({
					title: 'Вы уверены?',
					text: "Будут удалены все записи",
					type: 'warning',
					showCancelButton: true,
					confirmButtonColor: '#3085d6',
					cancelButtonColor: '#d33',
					confirmButtonText: 'Да, выполнить',
					cancelButtonText: 'Отменить',
					confirmButtonClass: 'greenbtn',
					cancelButtonClass: 'redbtn'
				},
				function () {

				}
			).then((result) => {

				if (result.value) {

					$.get("/content/admin/<?php echo $thisfile; ?>?action=clear", function () {

						razdel('webhooklog');

					});

				}

			});

		}

		function deleteHookLog(id) {

			$.get("/content/admin/<?php echo $thisfile; ?>?action=delete&id=" + id, function () {

				DClose();
				razdel('webhooklog');

			});

		}

	</SCRIPT>

<?php
}
