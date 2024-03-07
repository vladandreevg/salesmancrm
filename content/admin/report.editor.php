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

$on = $_COOKIE['reportView'] == 'thumbnail';

//составим массив используемых отчетов
$result = $db -> getAll( "SELECT * FROM {$sqlname}reports WHERE identity = '$identity' order by title" );
foreach ( $result as $data_array ) {
	$reps[] = $data_array['file'];
}

//Подключаем или загружаем базу отчетов
$rURL = "https://salesman.pro/docs/reports/";

//файл кэша
$cash = $rootpath."/cash/reports.json";

if ( !file_exists( $rootpath."/cash/reports.json" ) ) {

	a1:

	/*$reportList = str_replace( [
		"  ",
		"\t",
		"\n",
		"\r"
	], "", file_get_contents( $rURL."reportDB.json" ) );
	$reportBase = json_decode( $reportList, true );*/

	$reportList = str_replace( [
		"  ",
		"\t",
		"\n",
		"\r"
	], "", sendRequestStream( $rURL."reportDB.json" ) );
	$reportBase = json_decode( (string)$reportList, true );

	//запишем в файл
	file_put_contents( $cash, $reportList );

	//$file = fopen($cash, "w");
	//fputs($file, $reportList);
	//fclose($file);

	//$reportList = json_encode($reportBase);

}
else {

	$time = filemtime( $cash );
	$diff = datetimetoday( unix_to_datetime( $time ) );

	if ( abs( $diff ) > 10 ) {
		goto a1;
	}

	$reportList = str_replace( [
		"  ",
		"\t",
		"\n",
		"\r"
	], "", file_get_contents( $cash ) );
	$reportBase = json_decode( (string)$reportList, true );

	//$reportList = json_encode($reportBase);

}

//print_r($reportBase);

if ( $action == "edit.on" ) {

	$rid = (int)$_REQUEST['rid'];

	$param['title']    = $_REQUEST['title'];
	$param['file']     = $_REQUEST['url'];
	$param['ron']      = $_REQUEST['ron'];
	$param['category'] = $_REQUEST['category'];
	$param['roles']    = yimplode( ",", (array)$_REQUEST['role'] );
	$param['users']    = yimplode( ",", (array)$_REQUEST['users'] );

	if ( $rid > 0 ) {

		try {

			$db -> query( "UPDATE {$sqlname}reports SET ?u where rid = '$rid' and identity = '$identity'", arrayNullClean( $param ) );
			print "Запись сохранена";

		}
		catch ( Exception $e ) {
			echo $e -> getMessage();
		}

	}
	else {

		try {

			$param['identity'] = $identity;

			$db -> query( "INSERT INTO {$sqlname}reports SET ?u", arrayNullClean( $param ) );
			print "Запись сохранена";

		}
		catch ( Exception $e ) {
			echo $e -> getMessage();
		}

	}

	exit();
}
if ( $action == "onoff" ) {

	$rid = $_REQUEST['rid'];

	$ron = $db -> getOne( "SELECT ron FROM {$sqlname}reports WHERE rid = '$rid' and identity = '$identity'" );

	$ron = ($ron == 'yes') ? "no" : "yes";

	try {

		$db -> query( "UPDATE {$sqlname}reports SET ron = '$ron' where rid = '$rid' and identity = '$identity'" );
		print "Запись сохранена";

	}
	catch ( Exception $e ) {
		echo $e -> getMessage();
	}

	exit();
}
if ( $action == "delete" ) {

	$rid = $_REQUEST['rid'];

	try {

		$db -> query( "delete from {$sqlname}reports where rid = '$rid' and identity = '$identity'" );
		print "Отчет удален";

	}
	catch ( Exception $e ) {
		echo $e -> getMessage();
	}

	exit();

}

if ( $action == "edit" ) {

	$rid = $_REQUEST['rid'];

	if ( $rid > 0 ) {

		$result   = $db -> getRow( "select * from {$sqlname}reports where rid='".$rid."' and identity = '$identity'" );
		$title    = $result["title"];
		$url      = $result["file"];
		$ron      = $result["ron"];
		$category = $result["category"];
		$role     = (array)yexplode( ",", (string)$result["roles"] );
		$users    = (array)yexplode( ",", (string)$result["users"] );

	}

	$reportExist = $db -> getCol( "select file from {$sqlname}reports where file != '$url' and identity = '$identity'" );

	?>
	<DIV class="zagolovok">Добавить/Изменить Отчет</DIV>

	<FORM action="/content/admin/<?php echo $thisfile; ?>" method="post" enctype="multipart/form-data" name="rForm" id="rForm">
		<input name="action" id="action" type="hidden" value="edit.on">
		<input name="rid" id="rid" type="hidden" value="<?= $rid ?>">

		<DIV id="formtabs" class="box--child" style="max-height:80vh; overflow-x: hidden; overflow-y: auto !important;">

			<div class="flex-container box--child mt10 mb10">

				<div class="flex-string wp20 gray2 fs-12 pt7 right-text">Название отчета:</div>
				<div class="flex-string wp80 pl10">
					<input name="title" id="title" class="required wp97" value="<?= $title ?>">
				</div>

			</div>

			<div class="flex-container box--child mt10 mb10">

				<div class="flex-string wp20 gray2 fs-12 pt7 right-text">Файл отчета:</div>
				<div class="flex-string wp80 pl10">

					<select name="url" id="url" class="wp97 required" onchange="$('#infoR').removeClass('hidden')">
						<option value="">--Выбор--</option>
						<?php
						$handle = opendir( $rootpath."/reports" );
						while (($file = readdir( $handle )) !== false) {

							if ( $file != '.' && $file != '..' && strpos( $file, 'chart' ) === false && strpos( $file, 'fieldselect' ) === false && strpos( $file, 'php' ) && !in_array( $file, $reportExist ) ) {

								$s    = ($url == $file) ? "selected" : "";
								$name = yexplode( ".", $file, 0 );

								$reportName = (isset( $reportBase[ $name ] )) ? $reportBase[ $name ]['name'].' [ '.$file.' ]' : $file;
								$rname      = (isset( $reportBase[ $name ] )) ? $name : '';
								$color      = (isset( $reportBase[ $name ] )) ? 'bluebg-sub blue' : '';

								print '<option value="'.$file.'" '.$s.' data-name="'.$rname.'" data-cat="'.$reportBase[ $name ]['cat'].'" data-title="'.$reportBase[ $name ]['name'].'" class="'.$color.'">'.$reportName.'</option>';


							}

						}
						closedir( $handle );
						?>
					</select>
					<div id="viewlink" class="mt5 hidden"></div>
				</div>

			</div>

			<div class="flex-container box--child mt15 mb10">

				<div class="flex-string wp20 gray2 fs-12 pt7 right-text">Категория:</div>
				<div class="flex-string wp80 pl10">
					<select name="category" id="category" class="required">
						<option value="">--Выбор--</option>
						<option value="Планирование" <?php if ( $category == "Планирование" )
							print "selected" ?>>Планирование
						</option>
						<option value="Активности" <?php if ( $category == "Активности" )
							print "selected" ?>>Активности
						</option>
						<option value="Продажи" <?php if ( $category == "Продажи" )
							print "selected" ?>>Продажи
						</option>
						<option value="Эффективность" <?php if ( $category == "Эффективность" )
							print "selected" ?>>Эффективность
						</option>
						<option value="Рейтинг" <?php if ( $category == "Рейтинг" )
							print "selected" ?>>Рейтинг
						</option>
						<option value="Связи" <?php if ( $category == "Связи" )
							print "selected" ?>>Связи
						</option>
					</select>
				</div>

			</div>

			<hr>

			<div class="flex-container box--child mt10 mb10">

				<div class="flex-string wp20 gray2 fs-12 right-text"></div>
				<div class="flex-string wp80 pl20 Bold green">
					<label><input name="ron" type="checkbox" id="ron" value="yes" <?php if ( $ron == 'yes' )
							print 'checked="checked"' ?>> Включить</label>
				</div>

			</div>

			<hr>

			<div class="flex-container box--child mt10 mb10">

				<div class="flex-string wp20 gray2 fs-12 pt7 right-text">Доступ для ролей:</div>
				<div class="flex-string wp80 pl10">
					<div style="padding:2px 10px; display:inline-block; width:45%" class="nowrap">
						<label><input name="role[]" type="checkbox" id="role[]" value="Руководитель организации" <?php if ( in_array( 'Руководитель организации', (array)$role ) )
								print "checked" ?> />&nbsp;Руководитель организации</label>
					</div>
					<div style="padding:2px 10px; display:inline-block; width:45%" class="nowrap">
						<label><input name="role[]" type="checkbox" id="role[]" value="Руководитель с доступом" <?php if ( in_array( 'Руководитель с доступом', (array)$role ) )
								print "checked" ?> />&nbsp;Руководитель с доступом</label>
					</div>
					<div style="padding:2px 10px; display:inline-block; width:45%" class="nowrap">
						<label><input name="role[]" type="checkbox" id="role[]" value="Руководитель подразделения" <?php if ( in_array( 'Руководитель подразделения', (array)$role ) )
								print "checked" ?> />&nbsp;Руководитель подразделения</label>
					</div>
					<div style="padding:2px 10px; display:inline-block; width:45%" class="nowrap">
						<label><input name="role[]" type="checkbox" id="role[]" value="Руководитель отдела" <?php if ( in_array( 'Руководитель отдела', (array)$role ) )
								print "checked" ?> />&nbsp;Руководитель отдела</label>
					</div>
					<div style="padding:2px 10px; display:inline-block; width:45%" class="nowrap">
						<label><input name="role[]" type="checkbox" id="role[]" value="Менеджер продаж" <?php if ( in_array( 'Менеджер продаж', (array)$role ) )
								print "checked" ?> />&nbsp;Менеджер продаж</label>
					</div>
					<div style="padding:2px 10px; display:inline-block; width:45%" class="nowrap">
						<label><input name="role[]" type="checkbox" id="role[]" value="Поддержка продаж" <?php if ( in_array( 'Поддержка продаж', (array)$role ) )
								print "checked" ?> />&nbsp;Поддержка продаж</label>
					</div>
					<div style="padding:2px 10px; display:inline-block; width:45%" class="nowrap">
						<label><input name="role[]" type="checkbox" id="role[]" value="Администратор" <?php if ( in_array( 'Администратор', (array)$role ) )
								print "checked" ?> />&nbsp;Администратор</label>
					</div>
				</div>

			</div>

			<div class="flex-container box--child mt10 mb10">

				<div class="flex-string wp20 gray2 fs-12 pt7 right-text">или<br>Доступ для сотрудников:</div>
				<div class="flex-string wp80 pl10">
					<SELECT name="users[]" id="users[]" multiple="multiple" class="multiselect" style="width:50%">
						<?php
						$result = $db -> getAll( "SELECT * FROM {$sqlname}user where secrty='yes' and identity = '$identity' ORDER by title" );
						foreach ( $result as $data ) {
							?>
							<OPTION value="<?= $data['iduser'] ?>" <?php if ( in_array( $data['iduser'], (array)$users ) )
								print "selected" ?>><?= $data['title'] ?></OPTION>
						<?php } ?>
					</SELECT>
				</div>

			</div>

		</DIV>

		<hr>

		<div class="button--pane text-right">

			<a href="javascript:void(0)" onclick="$('#rForm').trigger('submit')" class="button">Сохранить</a>&nbsp;
			<A href="javascript:void(0)" onclick="DClose()" class="button">Отмена</A>

		</div>
	</FORM>

	<SCRIPT>

		$(function () {

			$('#dialog').css('width', '800px');

			$(".multiselect").multiselect({sortable: true, searchable: true});
			$(".connected-list").css('height', "150px");

			$('#rForm').ajaxForm({
				beforeSubmit: function () {

					var $out = $('#message');
					var em = checkRequired();

					if (!em)
						return false;

					$('#dialog').css('display', 'none');
					$('#dialog_container').css('display', 'none');

					$out.css('display', 'block').append('<div id=loader><img src=/assets/images/loader.gif> Загрузка данных. Пожалуйста подождите...</div>');

					return true;

				},
				success: function (data) {

					//$("#contentdiv").load('content/admin/<?php echo $thisfile; ?>');
					razdel(hash);

					$('#message').fadeTo(1, 1).css('display', 'block').html(data);
					setTimeout(function () {
						$('#message').fadeTo(1000, 0);
					}, 20000);

					$('#subwindow').removeClass('open');

					DClose();

				}
			});

			$('#dialog').center();

		});

	</SCRIPT>
	<?php
	exit();
}

if ( $action == "" ) {

	$list = [];

	$result = $db -> query( "SELECT * FROM {$sqlname}reports WHERE identity = '$identity' order by Field(ron, 'yes', 'no'), category, title" );
	while ($data = $db -> fetch( $result )) {

		$list[ $data['category'] ][] = [
			"id"    => $data['rid'],
			"title" => $data['title'],
			"file"  => $data['file'],
			"on"    => $data['ron'],
			"roles" => $data['roles'],
			"users" => $data['users']
		];

	}

	if ( $on ) {
		?>

		<style>
			.good {
				background-color : rgba(200, 230, 201, 0.5);
				border           : 1px solid rgba(200, 230, 201, 0.8);
				padding          : 10px;
				color            : #222;
				border-radius    : 5px;
			}

			.thumb {
				height : 150px;
			}
		</style>

		<!--<DIV style="float:right; position:fixed;top:60px;right:5px; z-index:1001">
			<A href="javascript:void(0)" onclick="doLoad('admin/report_editor.php?action=edit');" class="button">Добавить</A>
		</DIV>-->

		<DIV class="zagolovok_rep fs-12 pb20 pl10">

			<h2 class="p0 m0">Управление отчетами</h2>

		</DIV>

		<div class="noBold gray2 p10 bluebg-sub sticked--top">

			<div class="wp100">

				<div class="Bold mb10">Категории</div>

				<div class="wp100">

					<label class="mr10"><input name="category[]" type="radio" id="category[]" value="all" checked/>&nbsp;Все</label>
					<label class="mr10"><input name="category[]" type="radio" id="category[]" value="Планирование"/>&nbsp;Планирование</label>
					<label class="mr10"><input name="category[]" type="radio" id="category[]" value="Активности"/>&nbsp;Активности</label>
					<label class="mr10"><input name="category[]" type="radio" id="category[]" value="Продажи"/>&nbsp;Продажи</label>
					<label class="mr10"><input name="category[]" type="radio" id="category[]" value="Эффективность"/>&nbsp;Эффективность</label>
					<label class="mr10"><input name="category[]" type="radio" id="category[]" value="Рейтинг"/>&nbsp;Рейтинг</label>
					<label class="mr10"><input name="category[]" type="radio" id="category[]" value="Связи"/>&nbsp;Связи</label>

				</div>

				<div class="Bold mt10">Поиск отчета</div>

				<div class="wp100 border-box mb5">

					<input type="text" id="searcher" class="search wp99" placeholder="Поиск отчета">

				</div>

			</div>

		</div>

		<div class="space-20"></div>

		<?php
		$tbody = '';


		foreach ( $list as $category => $reports ) {

			$stringOn = $stringOff = '';

			foreach ( $reports as $k => $report ) {

				$roles   = $users = $string = '';
				$comment = [];

				$cclass   = ($report['on'] == 'yes') ? 'good' : 'viewdiv';
				$bclass   = ($report['on'] == 'yes') ? 'greenbg-dark' : 'redbg-sublite';
				$btitle   = ($report['on'] == 'yes') ? "Активен" : "Отключен";
				$btnclass = ($report['on'] == 'yes') ? "green" : "red";
				$btnstate = ($report['on'] == 'yes') ? "Откл." : "Вкл.";

				if ( $report['roles'] != '' ) {

					$comment[] = $roles = "<span title=\"Роли:\n     ".str_replace( ",", "\n     ", $report['roles'] ).'">Роли ('.count( yexplode( ",", (string)$report['roles'] ) ).')</span>';

				}

				$xusers = yexplode( ",", (string)$report['users'] );
				if ( !empty( $xusers ) ) {

					$str = [];

					foreach ( $xusers as $user ) {
						$str[] = '     '.current_user( $user, "yes" );
					}

					$comment[] = $users = "<span title=\"Сотрудники:\n".implode( "\n", $str ).'">Сотрудники ('.count( $str ).')</span>';

				}

				$comments = (count( $comment ) > 0) ? implode( ", ", $comment ) : 'для всех';
				$name     = yexplode( ".", $report['file'], 0 );
				$img      = ($reportBase[ $name ]['img'] == '') ? 'https://via.placeholder.com/300x150/fff/ccc' : $rURL.'images/'.$reportBase[ $name ]['img'];
				$url      = ($reportBase[ $name ]['img'] == '') ? '' : 'onclick="reportInfo(\''.$name.'\')"';
				$text     = ($reportBase[ $name ]['des'] == '') ? '<span class="gray">Нет описания. Видимо это какой-то специализированный отчет :)</span>' : htmlspecialchars_decode( $reportBase[ $name ]['des'] );

				$string = '
					<div class="flex-string flx-basis-33 '.$cclass.' box-shadow mr5 mb5 relativ treports-string"  title="'.$report['title'].'">
						<div class="ellipsis ttext">
							<div class="bullet '.$bclass.'" title="'.$btitle.'"></div>
							<span class="fs-12 Bold">'.$report['title'].'</span>
						</div>
						<div class="gray2 fs-09 pl101 mt10" title="'.$report['file'].'">
							<div class="ellipsis ttext">Файл: '.$report['file'].'</div>
						</div>
						<div class="thumb mt10 '.($url != '' ? 'hand' : '').'" style="background-image: url('.$img.'); background-size: cover;" '.$url.'></div>
						<div class="text fs-09 noBold mt10" style="overflow-y: auto; max-height: 160px; height: 120px;">'.$text.'</div>
						<div class="mt10 mb10 fs-09 gray2">
							Доступы: '.$comments.'&nbsp;
						</div>
						<div class="fs-09 mb5 pull-aright">
							<A href="javascript:void(0)" onclick="onoff(\''.$report['id'].'\');" class="gray1" title="Вкл./Откл."><i class="icon-ok-circled '.$btnclass.'"></i> '.$btnstate.'</A>&nbsp;&nbsp;
							<A href="javascript:void(0)" onclick="doLoad(\'content/admin/'.$thisfile.'?rid='.$report['id'].'&action=edit\');" class="gray1" title="Изменить"><i class="icon-pencil blue"></i> Ред.</A>&nbsp;&nbsp;
							<A href="javascript:void(0)" onclick="cf=confirm(\'Вы действительно хотите удалить запись?\');if (cf)delete_rep(\''.$report['id'].'\');" class="gray1" title="Удалить"><i class="icon-cancel-circled-1 red"></i> Удалить</A>
						</div>
					</div>
				';

				if ( $report['on'] == 'yes' )
					$stringOn .= $string;
				else
					$stringOff .= $string;

			}

			$tbody .= '
			<div class="fs-14 Bold mt10 mb20 reports blue" data-category="'.$category.'">Раздел: '.$category.'</div>
			<div class="flex-container box--child mt10 reports" data-category="'.$category.'">'.$stringOn.$stringOff.'</div>
			';

		}

		print '<div id="treports">'.$tbody.'</div>';

	}
	else {
		?>
		<div class="wp100 infodiv p10 mb5">

			<input type="text" id="searcher" class="search wp99" placeholder="Поиск отчета">

		</div>

		<TABLE id="zebrat">
			<thead class="hidden-iphone sticked--top">
			<tr class="th40">
				<TH class="w100">Категория</TH>
				<TH class="w20"></TH>
				<TH class="w350">Название</TH>
				<TH class="w70">Действие</TH>
				<TH>Доступы к отчету</TH>
			</tr>
			</thead>
			<tbody>
			<?php
			foreach ( $list as $category => $data ) {

				foreach ( $data as $report ) {

					$btnclass = ($report['on'] == 'yes') ? "green" : "red";
					$btnstate = ($report['on'] == 'yes') ? "Откл." : "Вкл.";

					$bg    = (file_exists( $rootpath."/reports/".$report['file'] )) ? "" : "redbg-sublite";
					$color = (file_exists( $rootpath."/reports/".$report['file'] )) ? "" : "red";
					$title = (file_exists( $rootpath."/reports/".$report['file'] )) ? "" : "Файл отчета отсутствует";

					$name = yexplode( ".", $report['file'], 0 );

					$url  = (!empty( $reportBase[ $name ] )) ? 'onclick="reportInfo(\''.$name.'\')" class="hand blue" title="Информация об Отчете"' : '';
					$icon = (!empty( $reportBase[ $name ] )) ? '<i class="icon-info-circled blue"></i>' : '';
					?>
					<TR class="th40 ha <?= $bg ?> <?= $color ?>" title="<?= $title ?>">
						<TD class="w100"><?= $category ?></TD>
						<TD class="w20">
							<div class="bullet-mini <?= ($report['on'] == 'yes') ? 'greenbg-dark' : 'redbg-dark'; ?>" title="<?= ($report['on'] == 'yes') ? 'Активен' : 'Отключен'; ?>"></div>
						</TD>
						<TD class="w350" <?= $url ?>>
							<div class="ellipsis fs-11" title="<?= $report['title'] ?>"><?= $icon ?>
								<B><?= $report['title'] ?></B></div>
							<br>
							<div class="ellipsis mt5 em gray2" title="<?= $report['file'] ?>"><?= $report['file'] ?></div>
						</TD>
						<TD class="w70 text-center" nowrap>
							<A href="javascript:void(0)" onclick="onoff('<?= $report['id'] ?>');" class="gray1" title="Вкл./Откл."><i class="icon-ok-circled <?= $btnclass ?>"></i> <?= $btnstate ?>
							</A>&nbsp;&nbsp;
							<A href="javascript:void(0)" onclick="doLoad('content/admin/<?php echo $thisfile; ?>?rid=<?= $report['id'] ?>&action=edit');" title="Изменить"><i class="icon-pencil blue"></i></A>&nbsp;&nbsp;
							<A href="javascript:void(0)" onclick="cf=confirm('Вы действительно хотите удалить запись?');if (cf)delete_rep('<?= $report['id'] ?>');" title="Удалить"><i class="icon-cancel-circled-1 red"></i></A>
						</TD>
						<TD>
							<div>
								<?php
								if ( $report['roles'] != '' ) {

									print '<span class="gray2">Роли:</span> '.str_replace( ",", ", ", $report['roles'] );

								}
								?>
							</div>
							<div class="mt10">
								<?php
								$xusers = yexplode( ",", (string)$report['users'] );

								if ( count( $xusers ) > 0 ) {

									$str = [];

									foreach ( $xusers as $user ) {

										$str[] = '<i class="icon-user-1 blue"></i>'.current_user( $user, "yes" );

									}

									print '<span class="gray2">Сотрудники:</span> '.implode( ", ", (array)$str );

								}
								?>
							</div>
						</TD>
					</TR>
					<?php
				}
			}
			?>
			</tbody>
		</TABLE>
		<?php
	}
	?>

	<DIV style="float:right; position:fixed;top:58px;right:10px; z-index:1001">

		<div class="inline p5">Вид:</div>
		<div class="inline p5 ha <?= ($on == true ? 'hand view gray' : 'graybg-sub blue') ?>" title="В виде списка" data-tip="list">
			<i class="icon-article"></i></div>
		<div class="inline p5 ha <?= ($on == true ? 'graybg-sub blue' : 'hand view gray') ?>" title="В виде миниатюр" data-tip="thumbnail">
			<i class="icon-th-large"></i></div>

	</DIV>

	<div class="pagerefresh refresh--icon admn red" onclick="doLoad('content/admin/<?php echo $thisfile; ?>?action=edit');" title="Добавить">
		<i class="icon-plus-circled"></i></div>
	<div class="pagerefresh refresh--icon admn orange" onclick="openlink('https://salesman.pro/docs/17')" title="Документация">
		<i class="icon-help"></i></div>

	<div class="button--group1 box--child" style="position: fixed; bottom: 40px; left: 380px; z-index: 100;">

		<a href="javascript:void(0)" onclick="doLoad('content/admin/<?php echo $thisfile; ?>?action=edit');" class="button bluebtn box-shadow" title="Добавить"><i class="icon-plus-circled"></i>Добавить</a>

	</div>

	<div class="space-100"></div>

	<script>

		$(function () {

			$("#searcher").on("keyup", function () {

				var value = $(this).val().toLowerCase();

				$("#zebrat tbody tr").filter(function () {

					$(this).toggle($(this).find('td:nth-child(3)').text().toLowerCase().indexOf(value) > -1)

				});

				$(".treports-string").filter(function () {

					$(this).toggle($(this).find('div.ttext').text().toLowerCase().indexOf(value) > -1)

				});

			});

		});

		var $base = [<?=$reportList?>];
		var $baseURL = '<?=$rURL?>';

		$('#category\\[\\]').on('click', function () {

			var element = $('.reports');
			var id = $(this).val();

			element.removeClass('hidden').each(function () {

				if (id !== 'all' && $(this).data('category') !== id) $(this).addClass('hidden');

			});

			$(".nano").nanoScroller();

		});

		function delete_rep(id) {

			$('#message').css('display', 'block').append('<div id="loader" class="loader"><img src=/assets/images/loader.gif> Вычисление...</div>');
			$.post('content/admin/<?php echo $thisfile; ?>?rid=' + id + '&action=delete', function (data) {

				//$('#contentdiv').load('admin/report.editor.php');
				razdel(hash);

				$('#message').fadeTo(1, 1).css('display', 'block').html(data);
				setTimeout(function () {
					$('#message').fadeTo(1000, 0);
				}, 20000);

			})

		}

		function onoff(id) {

			$('#message').css('display', 'block').append('<div id="loader" class="loader"><img src=/assets/images/loader.gif> Вычисление...</div>');
			$.post('content/admin/<?php echo $thisfile; ?>?rid=' + id + '&action=onoff', function (data) {

				//$('#contentdiv').load('content/admin/<?php echo $thisfile; ?>');
				razdel(hash);

				$('#message').fadeTo(1, 1).css('display', 'block').html(data);
				setTimeout(function () {
					$('#message').fadeTo(1000, 0);
				}, 20000);

			})

		}

		function reportInfo($text) {

			if ($text == null || $text === '') $text = $('#url').val();

			var $el = $('#subwindow');
			var $content;
			var $rep = $base[0][$text];

			$content = '' +
				'<h1 class="m0 p0 mb10">' + $rep.name + '</h1>' +
				'<div class="em gray2 mb20"><b>Файл:</b> ' + $rep.file + '</div>' +
				'<div class="infodiv">' +
				'   <div class="Bold mb10">Описание:</div>' +
				'   <div class="flh-12">' + decodeHtml($rep.des) + '</div>' +
				'</div>' +
				'<div class="viewdiv hand imgView"><img src="' + $baseURL + '/images/' + $rep.img + '" class="wp100"></div>';

			$el.empty().css('z-index', '50').addClass('open').append('<div class="body" style="height:calc(100vh - 60px)">' + $content + '</div><div class="footer pl10" style="height:60px"><a href="javascript:void(0)" onclick="closeInfo()" class="button">Закрыть</a></div>');

		}

		function decodeHtml(str) {
			var map = {
				'&amp;': '&',
				'&lt;': '<',
				'&gt;': '>',
				'&quot;': '"',
				'&#039;': "'"
			};
			return str.replace(/&amp;|&lt;|&gt;|&quot;|&#039;/g, function (m) {
				return map[m];
			});
		}

		$(document).off('click', '.view');
		$(document).on('click', '.view', function () {

			var tip = $(this).data('tip');

			setCookie('reportView', tip, {expires: 0});
			razdel('report.editor');

		});

		$(document).off('change', '#url');
		$(document).on('change', '#url', function () {

			var $el = $('#url option:selected');

			var $name = $el.data('name');
			var $cat = $el.data('cat');
			var $title = $el.data('title');

			//console.log("name=" + $name);

			if ($title !== '') $('#title').val($title);
			if ($cat !== '') $('#category').val($cat);
			if ($name !== '') {

				var link = '<a onclick="reportInfo(\'' + $name + '\')" class="blue mt5"><i class="icon-help-circled-1 blue"></i>Информация об отчете</a>';
				$('#viewlink').html(link).removeClass('hidden');

			}
			else {

				$('#category').val('');
				$('#title').val('');
				$('#viewlink').empty().addClass('hidden');

			}

		});

		$(document).off('click', '.imgView');
		$(document).on('click', '.imgView', function () {

			var img = $(this).find('img');
			var src = img.attr('src'); // Достаем из этого изображения путь до картинки

			$("#subwindow").append('' +
				'<div class="popup">' + //Добавляем в тело документа разметку всплывающего окна
				'   <div class="popup_bg"></div>' + // Блок, который будет служить фоном затемненным
				'   <img src="' + src + '" class="popup_img hand" onclick="$(\'.popup\').remove()">' + // Само увеличенное фото
				'</div>');

			$(".popup").fadeIn(400); // выводим изображение

			$(".popup_bg").on('click', function () {    // Событие клика на затемненный фон

				$(".popup").fadeOut(400);    // убираем всплывающее окно

				setTimeout(function () {    // Выставляем таймер
					$(".popup").remove(); // Удаляем разметку всплывающего окна
				}, 800);

				reportInfo();

			});

		});

		function closeInfo() {

			$('#subwindow').removeClass('open');

		}

	</script>
	<style>

		.popup {

			position   : fixed;
			height     : 100vh;
			width      : 100vw;
			top        : 0;
			left       : 0;
			display    : none;
			text-align : center;

		}

		.popup_bg {

			background : rgba(0, 0, 0, 0.4);
			position   : absolute;
			z-index    : 70;
			height     : 100%;
			width      : 100%;

		}

		.popup_img {

			position   : relative;
			margin-top : 3%;
			z-index    : 100;
			max-height : 90vh;
			max-width  : 90vw;

		}

	</style>
	<?php
}