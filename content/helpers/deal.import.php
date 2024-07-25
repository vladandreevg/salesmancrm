<?php
/* ============================ */
/*         SalesMan CRM         */
/* ============================ */
/* (C) 2016 Vladislav Andreev   */
/*       SalesMan Project       */
/*        www.isaler.ru         */
/*        ver. 2017.x           */
/* ============================ */

ini_set('memory_limit', '-1');

error_reporting(E_ERROR);

$rootpath = dirname(__DIR__, 2);

require_once $rootpath."/inc/config.php";
require_once $rootpath."/inc/dbconnector.php";
require_once $rootpath."/inc/auth.php";
require_once $rootpath."/inc/func.php";
require_once $rootpath."/inc/settings.php";
require_once $rootpath."/developer/events.php";
require_once $rootpath."/inc/language/".$language.".php";

$thisfile = basename(__FILE__);


$helper = json_decode(file_get_contents($rootpath.'/cash/helper.json'), true);

$action = $_REQUEST['action'];

if ($action == "discard") {

	$url = $rootpath.'/files/'.$fpath.$_COOKIE['url'];
	setcookie("url", '');
	unlink($url);

}
if ($action == "import") {
	?>
	<form action="/content/helpers/deal.import.php" method="post" enctype="multipart/form-data" name="Form" id="Form">
		<input type="hidden" name="action" id="action" value="upload">
		<DIV class="zagolovok">Импорт сделок в базу. Шаг 1.</DIV>

		<TABLE>
			<TR>
				<TD width="150" align="right"><B>Из файла:</B></TD>
				<TD><input name="file" type="file" class="file wp100" id="file"></TD>
			</TR>
		</TABLE>
		<div class="infodiv div-center">

			<b>Импортируйте сделки из других систем</b> в CRM. Вы можете использовать файлы в формате XLSX, XLS, CSV.
			<br/>Посмотреть
			<a href="/developer/example/deals.xls" target="_blank" style="color:red">пример файла</a> или
			<a href="<?= $productInfo['site'] ?>/docs/47" target="blank"><i class="icon-help-circled blue"></i><b class="blue">пошаговую инструкцию</b></a>.
			<hr>
			<iframe width="640" height="360" src="https://www.youtube.com/embed/quFtEe8Ihh8" frameborder="0" allowfullscreen></iframe>

		</div>

		<hr>

		<div class="button--pane text-center">
			<A href="javascript:void(0)" onclick="Next()" class="button graybtn next">Далее...</A>
			<A href="javascript:void(0)" onclick="Discard()" class="button">Закрыть</A>
		</div>
	</FORM>
	<?php
}
if ($action == "upload") {

	//проверяем расширение файла. Оно д.б. только csv
	$cur_ext = texttosmall(getExtention($_FILES['file']['name']));

	if (
		!in_array($cur_ext, [
			'csv',
			'xls',
			'xlsx'
		])
	) {
		print 'Ошибка при загрузке файла <b>"'.basename($_FILES['file']['name']).'"</b>!<br />
		<b class="yelw">Ошибка:</b> Недопустимый формат файла. <br>Допускаются только файлы в формате <b>CSV</b> или <b>XLS</b>';
		exit;
	}
	else {
		$url = $rootpath.'/files/'.$fpath.'import'.$iduser1.time().".".$cur_ext;
		//Сначала загрузим файл на сервер
		if (move_uploaded_file($_FILES['file']['tmp_name'], $url)) {
			setcookie("durl", 'import'.$iduser1.time().".".$cur_ext, time() + 86400);
			print 'Файл загружен';
			exit;
		}
		else {
			print 'Ошибка при загрузке файла <b>"'.$_FILES['file']['name'].'"</b> - '.$_FILES['file']['error'].'<br />';
			exit;
		}
	}
}
if ($action == "select") {

	$result = $db -> query("select * from {$sqlname}field where fld_tip='client' and fld_on='yes' and identity = '$identity' ORDER BY fld_order");
	while ($data = $db -> fetch($result)) {
		$fieldClient[$data['fld_name']] = $data['fld_title'];
	}

	$url = $rootpath.'/files/'.$fpath.$_COOKIE['durl'];

	$xdata = parceExcel($url, 0);

	$data = [];
	$x    = 0;
	while ($x < 3) {
		$data[] = $xdata[$x];
		$x++;
	}

	//выводим поля для выбора и ассоциации с данными
	if (file_exists($rootpath.'/cash/'.$fpath.'requisites.json')) {
		$file     = file_get_contents($rootpath.'/cash/'.$fpath.'requisites.json');
	}
	else {
		$file     = file_get_contents($rootpath.'/cash/requisites.json');
	}
	$recvName = json_decode($file, true);
	?>
	<DIV class="zagolovok">Импорт сделок в базу. Шаг 2.</DIV>
	<form action="/content/helpers/deal.import.php" method="post" enctype="multipart/form-data" name="Form2" id="Form2">
		<input type="hidden" name="action" id="action" value="import_on">

		<div id="formtabs">

			<div class="flex-container box--child">

				<div class="flex-string wp50 p5">

					<div class="fs-07 gray uppercase Bold">Новых клиентов Назначить:</div>
					<select name="new_user" id="new_user" style="width:100%;">
						<option selected="selected" value="<?= $iduser1 ?>">На себя</option>
						<option value="0">В холодные организации</option>
						<optgroup label="Сотруднику"></optgroup>
						<?php
						$result = $db -> query("SELECT * FROM {$sqlname}user WHERE identity = '$identity' ORDER by title ".$userlim);
						while ($data_array = $db -> fetch($result)) {
							print '<option value="'.$data_array['iduser'].'">'.$data_array['title'].'</option>';
						}
						?>
					</select>

				</div>
				<div class="flex-string wp50 p5">

					<div class="fs-07 gray uppercase Bold">Тип клиента (для новых):</div>
					<SELECT name="ctype" id="ctype" style="width:100%;">
						<OPTION value="client" <?php
						if (!$otherSettings['clientIsPerson']) print "selected" ?>>Клиент. Юр.лицо
						</OPTION>
						<OPTION value="person" <?php
						if ($otherSettings['clientIsPerson']) print "selected" ?>>Клиент. Физ.лицо
						</OPTION>
					</SELECT>

				</div>

			</div>

		</div>

		<hr>

		<table id="zebra">
			<thead>
			<tr class="header_contaner noDrag">
				<TH width="200" height="35" align="left" class="nodrop">Название поля в БД</TH>
				<TH width="250" height="35" align="left" class="nodrop">Название поля из файла</TH>
				<TH align="left" class="nodrop">Образец из файла</TH>
			</tr>
			</thead>
		</table>
		<DIV class="bgwhite" style="height:43vh; overflow:auto">

			<table id="zebra">
				<?php
				foreach ($data[0] as $i => $item) {
					?>
					<tr class="ha">
						<td width="200">
							<select id="field[]" name="field[]" class="required" style="width:100%">
								<option value="">--Выбор--</option>
								<optgroup label="Клиент">
									<option value="client:uid" <?php
									if ($item == 'UID') {
										print "selected";
									} ?>>Клиент: UID
									</option>
									<option value="client:title" <?php
									if ($item == $fieldClient['title']) {
										print "selected";
									} ?>>Клиент: <?= $fieldClient['title'] ?></option>
									<?php
									if ($fieldClient['phone']) { ?>
										<option value="client:phone" <?php
										if ($item == $fieldClient['phone']) {
											print "selected";
										} ?>>Клиент: <?= $fieldClient['phone'] ?></option>
									<?php
									} ?>
									<?php
									if ($fieldClient['fax']) { ?>
										<option value="client:fax" <?php
										if ($item == $fieldClient['fax']) {
											print "selected";
										} ?>>Клиент: <?= $fieldClient['fax'] ?></option>
									<?php
									} ?>
									<?php
									if ($fieldClient['mail_url']) { ?>
										<option value="client:mail_url" <?php
										if ($item == $fieldClient['mail_url']) {
											print "selected";
										} ?>>Клиент: <?= $fieldClient['mail_url'] ?></option>
									<?php
									} ?>
								</optgroup>
								<optgroup label="Сделка">
									<option value="deal:uid" <?php
									if ($item == 'UID') {
										print "selected";
									} ?>>Сделка: UID
									</option>
									<option value="deal:title" <?php
									if ($item == 'Название сделки') {
										print "selected";
									} ?>>Сделка: Название сделки
									</option>
									<option value="dop:dateCreate">Сделка: Дата создания</option>
									<option value="dop:datePlan">Сделка: Дата плановая</option>
									<option value="dop:step" <?php
									if ($item == 'Этап') {
										print "selected";
									} ?>>Сделка: Этап
									</option>
									<option value="dop:summa" <?php
									if ($item == 'Сумма') {
										print "selected";
									} ?>>Сделка: Сумма
									</option>
									<option value="dop:dateClose">Сделка: Дата закрытия</option>
									<option value="dop:isClose">Сделка: Статус активна/закрыта</option>
									<option value="dop:statusClose" <?php
									if ($item == 'Статус закрытия') {
										print "selected";
									} ?>>Сделка: Статус закрытия
									</option>
									<?php
									$result = $db -> query("select * from {$sqlname}field where fld_on='yes' and fld_tip='dogovor' and fld_name NOT IN ('zayavka','ztitle','mcid','iduser','period','money','pid_list','payer','oborot','idcategory','kol','datum_plan') and identity = '$identity' order by fld_tip, fld_order");
									while ($data_array = $db -> fetch($result)) {

										if ($data[0][$i] == $data_array['fld_title']) {
											$s3 = " selected";
										}
										else {
											$s3 = '';
										}

										print '<option value="deal:'.$data_array['fld_name'].'" '.$s3.'>Сделка: '.$data_array['fld_title'].'</option>';
									}
									?>
								</optgroup>
								<optgroup label="Активности">
									<option value="history:datum">Активности: Дата</option>
									<option value="history:tip">Активности: Тип</option>
									<option value="history:des">Активности: Содержание</option>
								</optgroup>
							</select>
						</td>
						<td width="250"><b><?= $data[0][$i] ?></b></td>
						<td>
							<div class="ellipsis"><?= $data[1][$i] ?></div>
						</td>
					</tr>
				<?php
				} ?>
			</table>

		</DIV>

	</FORM>

	<hr>

	<div align="center" class="success pad5">
		<p>Теперь Вам необходимо ассоциировать загруженные данные с БД системы. Подробнее в
			<a href="https://isaler.ru/docs/index.php?id=47" target="blank">Документации</a></p>
		<p>
			<b>Важно:</b> Допускается импортировать не более 5000 записей за один раз. Привязка к существующим записям клиентов осуществляется по полям "UID" (при наличии) или "<?= $fieldClient['title'] ?>", затем, при наличии, по полям "<?= $fieldClient['phone'] ?>", "<?= $fieldClient['fax'] ?>", "Email"
		</p>
	</div>

	<hr>

	<DIV class="button--pane text-right">

		<A href="javascript:void(0)" onclick="$('#Form2').trigger('submit')" class="button">Импортировать</A>
		<A href="javascript:void(0)" onclick="Discard()" class="button">Отмена</A>

	</DIV>
	<?php
}
if ($action == "import_on") {

	$url   = $rootpath.'/files/'.$fpath.$_COOKIE['durl'];//файл для расшифровки
	$fields = $_REQUEST['field'];                         //порядок полей

	$new_user   = $_REQUEST['new_user'];
	$clientpath = $_REQUEST['clientpath'];
	$ctype      = $_REQUEST['ctype'];

	$trash = ( $new_user == '0' ) ? "yes" : "no";

	$date_create = current_datumtime();

	$cc = 0;
	$dd = 0;
	$z  = 0;

	$stepsID  = $stepsTitle = [];
	$statusID = $statusTitle = [];
	$tipID    = $tipTitle = [];
	$dirID    = $dirTitle = [];

	$names  = [];
	$indexs = [];

	/*
	 * Справочник этапов
	 */
	$result = $db -> query("SELECT * FROM {$sqlname}dogcategory WHERE identity = '$identity' ORDER BY title");
	while ($data_array = $db -> fetch($result)) {

		$stepsID[]    = $data_array['idcategory'];
		$stepsTitle[] = $data_array['content'];

	}

	/*
	 * Справочник статусов закрытия
	 */
	$result = $db -> query("SELECT * FROM {$sqlname}dogstatus WHERE identity = '$identity' ORDER BY title");
	while ($data_array = $db -> fetch($result)) {

		$statusID[]    = $data_array['sid'];
		$statusTitle[] = $data_array['title'];

	}

	/*
	 * Справочник типов сделок
	 */
	$result = $db -> query("SELECT * FROM {$sqlname}dogtips WHERE identity = '$identity' ORDER BY title");
	while ($data_array = $db -> fetch($result)) {

		$tipID[]    = $data_array['tid'];
		$tipTitle[] = $data_array['title'];

	}

	/*
	 * Справочник направлений
	 */
	$result = $db -> query("SELECT * FROM {$sqlname}direction WHERE identity = '$identity' ORDER BY title");
	while ($data_array = $db -> fetch($result)) {

		$dirID[]    = $data_array['id'];
		$dirTitle[] = $data_array['title'];

	}

	//составим массивы ассоциации данных по типам. $i - это номер колонки из таблицы.
	foreach ($fields as $i => $field) {

		if (strpos($field, 'client') !== false) {

			$c = str_replace("client:", "", $field);

			if ($c == 'title') {

				$cc++; //индикатор наличия организации
				$clx = $i;

			}
			if ($c == 'uid') {

				$cc++; //индикатор наличия организации
				$clu = $i;

			}
			if ($c == 'phone') {

				$clt = $i;

			}
			if ($c == 'fax') {

				$clf = $i;

			}
			if ($c == 'mail_url') {

				$clm = $i;

			}

			//массив данных по клиенту
			$indexs['client'][]  = $i;//массив ключ поля -> номер столбца
			$names['client'][$i] = $c;//массив номер столбца -> индекс поля

		}
		if (strpos($field, 'deal') !== false) {

			$c = str_replace("deal:", "", $field);
			if (
				!in_array($c, [
					'tip',
					'direction'
				])
			) {

				if ($c == 'title') {
					$dd++; //индикатор наличия сделки
					$dlx = $i;
				}
				if ($c == 'uid') {
					$dlu = $i;
				}
				//массив данных по сделке
				$indexs['deal'][]  = $i;
				$names['deal'][$i] = $c;//массив номер столбца -> индекс поля
			}

		}
		if (strpos($field, 'tip') !== false) {

			$indexs['dop']['tip'] = $i;//массив ключ поля -> номер столбца

		}
		if (strpos($field, 'direction') !== false) {

			$indexs['dop']['direction'] = $i;//массив ключ поля -> номер столбца

		}
		if (strpos($field, 'summa') !== false) {

			$indexs['dop']['summa'] = $i;//массив ключ поля -> номер столбца

		}
		if (strpos($field, 'isClose') !== false) {

			//массив ключ поля -> номер столбца
			$indexs['dop']['isClose'] = $i;

		}
		if (strpos($field, 'dateCreate') !== false) {

			$indexs['dop']['date_create'] = $i;//массив ключ поля -> номер столбца

		}
		if (strpos($field, 'datePlan') !== false) {

			$indexs['dop']['date_plan'] = $i;//массив ключ поля -> номер столбца

		}
		if (strpos($field, 'step') !== false) {

			$indexs['dop']['step'] = $i;//массив ключ поля -> номер столбца

		}
		if (strpos($field, 'dateClose') !== false) {

			$indexs['dop']['date_close'] = $i;//массив ключ поля -> номер столбца

		}
		if (strpos($field, 'statusClose') !== false) {

			$indexs['dop']['status_close'] = $i;//массив ключ поля -> номер столбца

		}
		if (strpos($field, 'history') !== false) {

			$c                     = str_replace("history:", "", $field);
			$indexs['history'][$c] = $i;//массив ключ поля -> номер столбца

		}

	}

	$data = [];

	//считываем данные из файла в массив
	$cur_ext = texttosmall(getExtention($_COOKIE['durl']));

	$maxImport = 5001;

	$datas = [];
	$x    = 0;
	while ($x < $maxImport) {
		$datas[] = $xdata[$x];
		$x++;
	}

	$good  = 0;
	$good2 = 0;
	$good3 = 0;
	$err   = 0;
	$err2  = 0;
	$err3  = 0;

	$clids = [];
	$dids  = [];

	//импортируем данные из файла
	foreach ($datas as $i => $data) {

		$clid = 0;
		$did  = 0;

		$date_create = current_datumtime();
		$idtip       = $tipDefault;
		$iddir       = $dirDefault;
		$summa       = 0;
		$idstep      = 0;
		$idstatus    = 0;
		$isClose     = 'no';
		$summaFact   = 0;
		$date_plan   = current_datumtime();
		$date_close  = '';

		$dogovor = [];
		$client  = [];

		$castName = $data[$indexs['client']['title']];

		//обработаем сумму и установим статус Активна/Закрыта на основании этой суммы
		if ($data[$indexs['dop']['summa']] != '') {

			$summa = pre_format($data[$indexs['dop']['summa']]);

		}

		//обработаем этапы
		$st = $data[$indexs['dop']['step']];
		if ($st != '') {

			//сопоставляем id этапа, если нет создаем.
			if (in_array($st, $stepsTitle)) {

				$idstep = $stepsID[array_search($st, $stepsTitle)];

			}
			else {

				$db -> query("insert into {$sqlname}dogcategory (`idcategory`, `title`, `content`, `identity`) values(null, '0', '$st','$identity')");
				$idstep = $db -> insertId();

				$stepsID[]    = $idstep;
				$stepsTitle[] = $st;

			}

		}

		//обработаем статусы закрытия
		$sc = $data[$indexs['dop']['status_close']];
		if ($data[$indexs['dop']['isClose']] == 'yes' && $sc != '') {

			$isClose   = 'yes';
			$summaFact = $summa;

			//сопоставляем id статуса текущего, если нет создаем.
			if (in_array($sc, $statusTitle)) {

				//если такое название уже сужествует, то сопоставляем id
				$idstatus = $statusID[array_search($sc, $statusTitle)];

			}
			else {

				$db -> query("insert into {$sqlname}dogstatus (sid, title, content, identity) values(null, '$sc', '$sc', '$identity')");

				$idstatus = $db -> insertId();

				$statusID[]    = $idstatus;
				$statusTitle[] = $sc;

			}

		}

		//обработаем типы сделок
		$stip = $data[$indexs['dop']['tip']];
		if ($data[$indexs['dop']['tip']] != '' && $stip != '') {

			//сопоставляем id статуса текущего, если нет создаем.
			if (in_array($stip, $tipTitle)) {

				$idtip = $tipID[array_search($stip, $tipTitle)];

			}
			else {

				$db -> query("insert into {$sqlname}dogtips (tid, title, identity) values(null, '$stip', '$identity')");

				$idtip = $db -> insertId();

				$tipID[]    = $idtip[$i];
				$tipTitle[] = $stip;

			}

		}

		//обработаем направления
		$sdir = $data[$indexs['dop']['direction']];
		if ( $data[$indexs['dop']['direction']] != '' && $sdir != '') {

			//сопоставляем id статуса текущего, если нет создаем.
			if (in_array($sdir, $dirTitle)) {

				$iddir = $dirID[array_search($sdir, $dirTitle)];

			}
			else {

				$db -> query("insert into {$sqlname}direction (id, title, identity) values(null, '".$sdir."', '$identity')");

				$iddir = $db -> insertId();

				$dirID[]    = $iddir;
				$dirTitle[] = $sdir;

			}

		}

		//обработаем дату создания
		if ($data[$indexs['dop']['date_create']] != '') {
			$date_create = $data[$indexs['dop']['date_create']];
		}

		//обработаем дату план
		if ($data[$indexs['dop']['date_plan']] != '') {

			$date_plan = $data[$indexs['dop']['date_plan']];

		}

		//обработаем дату закрытия
		if ($data[$indexs['dop']['date_close']] != '') {

			$date_close = $data[$indexs['dop']['date_close']];

		}

		//если в данных есть клиент, то попробуем найти его clid или добавить нового
		if ($cc > 0 && ( $data[$clx] != '' || $data[$clu] != '' )) {

			//поищем клиента в базе
			$qr = '';

			//проверка на наличие по uid или названию
			if ($data[$clu] == '') {
				$qr .= " and title='".clientFormatTitle(untag(enc_detect($data[$clx])))."'";
			}
			else {
				$qr .= " and uid='".$data[$clu]."'";
			}

			//проверка на наличие по email
			if (untag($data[$clm]) != '') {
				$qr .= "and mail_url LIKE '%".untag(enc_detect($data[$clm]))."%'";
			}

			//проверка на наличие по телефонам
			if (untag($data[$clt]) != '' && untag($data[$clf]) == '') {
				$qr .= "and phone LIKE '%".untag(enc_detect($data[$clt]))."%'";
			}

			elseif (untag($data[$clt]) == '' && untag($data[$clf]) != '') {
				$qr .= "and fax LIKE '%".untag(enc_detect($data[$clf]))."%'";
			}

			elseif (untag($data[$clt]) != '' && untag($data[$clf]) != '') {
				$qr .= "and (phone LIKE '%".untag(enc_detect($data[$clt]))."%' or fax LIKE '%".untag(enc_detect($data[$clf]))."%')";
			}

			$clid = (int)$db -> getOne("select clid from {$sqlname}clientcat where clid > 0 $qr and identity = '$identity'");

			//если клиент не найден, то добавим его
			if ($clid < 1) {

				$client = [
					"iduser"      => $new_user,
					"creator"     => $iduser1,
					"idcategory"  => $idcat,
					"date_create" => $date_create,
					"trash"       => $trash,
					"type"        => $ctype,
					"identity"    => $identity
				];

				foreach ($indexs['client'] as $k => $v) {
					$client[$names['client'][$v]] = ( $k == $clx ) ? clientFormatTitle($data[$v]) : $data[$v];
				}

				try {

					$db -> query("INSERT INTO {$sqlname}clientcat SET ?u", arrayNullClean($client));
					$good++;

					$clid    = $db -> insertId();
					$clids[] = $clid;

				}
				catch (Exception $e) {

					$err++;
					$error[] = 'Ошибка'.$e -> getMessage().' в строке '.$e -> getCode().'<br>';

				}

			}

		}

		//добавим сделку, если она есть в данных
		if ($clid > 0 && $dd > 0 && $data[$dlx] != '') {

			$did = $db -> getOne("select did from {$sqlname}dogovor where uid = '".$data[$dlu]."' and identity = '$identity'") + 0;

			if ($dlu > 0 && $did > 0) {
				continue;
			}

			$dogovor = [
				"clid"        => $clid,
				"payer"       => $clid,
				"iduser"      => $new_user,
				"autor"       => $iduser1,
				"datum"       => $date_create,
				"datum_plan"  => $date_plan,
				"datum_close" => $date_close,
				"idcategory"  => $idstep,
				"tip"         => $idtip,
				"direction"   => $iddir,
				"kol"         => $summa,
				"kol_fact"    => $summaFact,
				"sid"         => $idstatus,
				"close"       => $isClose,
				"identity"    => $identity
			];

			foreach ($indexs['deal'] as $k => $v) {

				$dogovor[$names['deal'][$v]] = $data[$v];

			}

			try {

				$db -> query("INSERT INTO {$sqlname}dogovor SET ?u", arrayNullClean($dogovor));
				$good2++;
				$did    = $db -> insertId();
				$dids[] = $did;

			}
			catch (Exception $e) {

				$err2++;

			}

		}

		//добавим запись в историю активности
		if (!empty($indexs['history'])) {

			try {

				addHistorty([
					"clid"     => $clid,
					"pid"      => $pid,
					"did"      => $did,
					"datum"    => $data[$indexs['history']['datum']] == '' ? current_datumtime() : $data[$indexs['history']['datum']]." 12:00:00",
					"tip"      => $data[$indexs['history']['tip']],
					"des"      => $data[$indexs['history']['des']],
					"iduser"   => $iduser1,
					"identity" => $identity
				]);

				$good3++;

			}
			catch (Exception $e) {

				$err3++;

			}

		}

	}

	unlink($url);

	$mesg = '';
	if ($err == 0) {
		$mesg .= "Список клиентов импортирован успешно.<br> Импортировано <strong>".$good."</strong> записей.<br> Ошибок: нет<br>";
	}
	else {
		$mesg .= "Список клиентов импортирован с ошибками.<br> Импортировано <strong>".$good."</strong> позиций.<br> Ошибок: ".$err."<br>";
	}

	if ($err2 == 0) {
		$mesg .= "Список сделок импортирован успешно.<br> Импортировано <strong>".$good2."</strong> записей.<br> Ошибок: нет<br>";
	}
	else {
		$mesg .= "Список сделок импортирован с ошибками.<br> Импортировано <strong>".$good2."</strong> позиций.<br> Ошибок: ".$err2;
	}

	if ($err3 == 0) {
		$mesg .= "Список активностей импортирован успешно.<br> Импортировано <strong>".$good3."</strong> записей.<br> Ошибок: нет<br>";
	}
	else {
		$mesg .= "Список активностей импортирован с ошибками.<br> Импортировано <strong>".$good3."</strong> позиций.<br> Ошибок: ".$err3;
	}

	logger('6', 'Импорт клиентов и сделок', $iduser1);

	print $mesg;

	event ::fire('deal.import', $args = [
		"clids" => $clids,
		"dids"  => $dids,
		"autor" => $iduser1,
		"user"  => $iduser
	]);

	exit();

}
?>
<script>

	$('#dialog').css('width', '800px');

	$(function () {

		$('#resultdiv').find('select').each(function () {

			$(this).wrap("<span class='select'></span>");

		});

		$('#dialog').center();

	});

	$('#Form').ajaxForm({
		beforeSubmit: function () {
			var $out = $('#message');
			var em = 0;

			$(".required").removeClass("empty").css({"color": "inherit", "background": "#FFF"});
			$(".required").each(function () {

				if ($(this).val() === '') {
					$(this).addClass("empty").css({"color": "red", "background": "#FF8080"});
					em = em + 1;
				}

			});

			$out.empty();

			if (em > 0) {

				alert("Не заполнены обязательные поля\n\rОни выделены цветом");
				return false;

			}
			if (em === 0) {
				$out.css('display', 'block').append('<div id="loader"><img src="/assets/images/loader.gif"> Загрузка данных. Пожалуйста подождите...</div>');
				$('#dialog').removeClass('dtransition');

				return true;
			}
		},
		success: function (data) {

			$('#message').fadeTo(1, 1).css('display', 'block').html(data);
			setTimeout(function () {
				$('#message').fadeTo(1000, 0);
			}, 20000);

			$('#resultdiv').empty().append('<DIV class="zagolovok">Импорт сделок в базу. Читаю данные.</DIV><div class="contentloader margtop20 margbot20"><img src="/assets/images/Services.svg" width="50px" height="50px"></div>');

			if (data === 'Файл загружен') {

				$.get('/content/helpers/deal.import.php?action=select', function (resp) {

					if (resp !== '')
						$('#resultdiv').html(resp);

					else
						$('#resultdiv').html('<DIV class="zagolovok">Ошибка.</DIV><div class="contentloader margtop20 margbot20">Файл содержит слишком большое количество записей. Попробуйте загрузить не более 5000 строк</div>');


				});

			}
			else return false;
		},
		complete: function () {
			$('#dialog').addClass('dtransition');
		}
	});
	$('#Form2').ajaxForm({
		beforeSubmit: function () {
			var $out = $('#message');
			var ef = $("#field\\[\\]").filter('[value=""]').size();
			var ff = $("#field\\[\\]").size();
			var emp = (ff - ef);
			$out.empty();
			if (emp === 0) {
				$("#field\\[\\]").filter('[value=""]').css({color: "#FFF", background: "#FF8080"});
				alert("Не сопоставлено ни одного поля");
				return false;
			}
			else {
				$out.fadeTo(1, 1).css('display', 'block').append('<div id=loader><img src=/assets/images/loader.gif> Загрузка данных. Пожалуйста подождите...</div>');
				$('#dialog_container').css('display', 'none');
				$('#dialog').css('display', 'none');
				$('#dialog').removeClass('dtransition');
				return true;
			}
		},
		success: function (data) {

			if (typeof configpage === 'function') {
				configpage();
			}

			$('#dialog_container').css('display', 'none');
			$('#dialog').css('display', 'none');

			$('#message').fadeTo(1, 1).css('display', 'block').html(data);
			setTimeout(function () {
				$('#message').fadeTo(1000, 0);
			}, 20000);
		},
		complete: function () {
			$('#dialog').addClass('dtransition');
		}
	});

	$(document).on('change', '#file', function () {

		//console.log(this.files);

		var ext = this.value.split(".");
		var elength = ext.length;
		var carrentExt = ext[elength - 1].toLowerCase();

		if (in_array(carrentExt, ['csv', 'xls', 'xlsx']))
			$('.next').removeClass('graybtn');

		else {

			Swal.fire('Только в формате CSV, XLS, XLSX', '', 'warning');
			$('#file').val('');
			$('.next').addClass('graybtn');

		}

	});

	function Next() {

		if (!$('.next').hasClass('graybtn'))
			$('#Form').trigger('submit');

		else
			Swal.fire('Внимание', 'Вы забыли выбрать файл для загрузки', 'info');

	}

	function Discard() {

		var url = '/content/helpers/deal.import.php?action=discard';
		var str = '';

		$.post(url, str, function () {

			$('#dialog').css('display', 'none');
			$('#resultdiv').empty();
			$('#dialog_container').css('display', 'none');

			return false;

		});
	}
</script>