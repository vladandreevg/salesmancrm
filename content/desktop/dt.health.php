<?php
/* ============================ */
/*         SalesMan CRM         */
/* ============================ */
/* (C) 2016 Vladislav Andreev   */
/*       SalesMan Project       */
/*        www.isaler.ru         */
/*        ver. 2017.x           */
/* ============================ */


use Salesman\Deal;

header('Access-Control-Allow-Origin: *');
header("Pragma: no-cache");

error_reporting(E_ERROR);

$rootpath = dirname( __DIR__, 2 );

include $rootpath."/inc/config.php";
include $rootpath."/inc/dbconnector.php";
include $rootpath."/inc/auth.php";

$thisfile = basename( __FILE__ );

$action = $_REQUEST['action'];
$did    = $_REQUEST['did'];
$view   = $_REQUEST['view'];

if (!isset($iduser1)) $iduser1 = $_REQUEST['iduser1'];
if (!isset($identity)) $identity = $_REQUEST['identity'];

include $rootpath."/inc/func.php";
include $rootpath."/inc/settings.php";
include $rootpath."/inc/language/".$language.".php";

$complect_on = $complect_on ?? $_REQUEST['complect_on'];

// этап заморозки
$stepInHold = customSettings('stepInHold');

//загружаем все возможные цепочки и конвертируем в JSON
$mFunnel = getMultiStepList();

//print_r($mFunnel);

$dogs = [];

if ($action == '') {

	$users = get_people($iduser1, "yes");

	$s = !empty($users) ? $sqlname."dogovor.iduser IN (".implode(",", (array)$users).") and" : "";

	if($stepInHold['step'] > 0 && $stepInHold['input'] != ''){

		$s .= " ({$sqlname}dogovor.idcategory != '$stepInHold[step]' OR ({$sqlname}dogovor.idcategory = '$stepInHold[step]' AND DATE({$sqlname}dogovor.".$stepInHold['input'].") <= DATE(NOW()) )) AND ";

	}

	$q = "
		SELECT
			{$sqlname}dogovor.did as did,
			{$sqlname}dogovor.title as title,
			{$sqlname}dogovor.datum as dcreate,
			{$sqlname}dogovor.datum_plan as dplan,
			{$sqlname}dogovor.datum_close as dclose,
			{$sqlname}dogovor.clid as clid,
			{$sqlname}dogovor.kol as kol,
			{$sqlname}dogovor.marga as marga,
			{$sqlname}dogovor.close as close,
			{$sqlname}dogovor.iduser as iduser,
			{$sqlname}dogovor.idcategory,
			{$sqlname}dogovor.direction,
			{$sqlname}dogovor.tip,
			{$sqlname}dogovor.iduser as iduser,
			{$sqlname}user.title as user,
			{$sqlname}clientcat.title as client,
			{$sqlname}dogcategory.title as step,
			{$sqlname}dogcategory.content as steptitle
		FROM {$sqlname}dogovor
			LEFT JOIN {$sqlname}user ON {$sqlname}dogovor.iduser = {$sqlname}user.iduser
			LEFT JOIN {$sqlname}clientcat ON {$sqlname}dogovor.clid = {$sqlname}clientcat.clid
			LEFT JOIN {$sqlname}personcat ON {$sqlname}dogovor.pid = {$sqlname}personcat.pid
			LEFT JOIN {$sqlname}dogcategory ON {$sqlname}dogovor.idcategory = {$sqlname}dogcategory.idcategory
		WHERE
			{$sqlname}dogovor.did > 0 and
			COALESCE({$sqlname}dogovor.close, 'no') != 'yes' and
			$s
			{$sqlname}dogovor.identity = '$identity'
		ORDER BY {$sqlname}dogovor.datum_plan";

	$result = $db -> query($q);
	while ($data = $db -> fetch($result)) {

		$summa     = $day = 0;
		$color     = '';
		$datum_min = $datum_min2 = $datum_min3 = $data['dplan'];
		$health    = 1;
		$taskcount = 2;
		$cp['task'] = '';

		$cp['plan'] = (diffDate2($data['dplan']) < 1) ? '<i class="icon-calendar-2 red list" title="Плановая дата"></i>' : '<i class="icon-calendar-2 green list" title="Плановая дата"></i>';

		$client = ($data['clid'] > 0) ? $data['client'] : $data['person'];

		if ($complect_on == 'yes') {

			//Сформируем здоровье сделки относительно плановой даты
			$datum_min2 = $db -> getOne("SELECT MIN(data_plan) FROM {$sqlname}complect WHERE did = '".$data['did']."' and doit != 'yes' and identity = '$identity'");

			$cp['complect'] = (diffDate2($datum_min2) < 1 && $datum_min2 != '0000-00-00' && $datum_min2 != null) ? '<i class="icon-check red list" title="Контрольные точки"></i>' : '<i class="icon-check green list" title="Контрольные точки"></i>';

			if ($datum_min2 == '0000-00-00' || is_null($datum_min2)) {
				$cp['complect'] = '<i class="icon-check-empty gray list" title="Контрольные точки отсутствуют"></i>';
			}

		}

		if ($datum_min2 != '0000-00-00' && !is_null($datum_min2) && $datum_min2 < $datum_min) {
			$datum_min = $datum_min2;
		}

		//Сформируем сумму оплаченных счетов
		$summa = (float)$db -> getOne("SELECT SUM(summa_credit) as summa FROM {$sqlname}credit WHERE did = '".$data['did']."' and do = 'on' and identity = '$identity'");

		$icount = (int)$db -> getOne("SELECT count(*) as count FROM {$sqlname}credit WHERE did = '".$data['did']."' and identity = '$identity'");

		$xcount = (int)$db -> getOne("SELECT count(*) as count FROM {$sqlname}credit WHERE did = '".$data['did']."' and do != 'on' and identity = '$identity'");

		$res = $db -> getRow("SELECT MIN(datum_credit) as datum_credit, MIN(invoice_date) as invoice_date FROM {$sqlname}credit WHERE did = '".$data['did']."' and do != 'on' and datum_credit != '0000-00-00' and identity = '$identity'");

		if ($res['invoice_date'] != '0000-00-00' && $res['datum_credit'] != null) {
			$datum_min3 = $res['datum_credit'];
		}
		elseif ($res['invoice_date'] != '0000-00-00' && $res['invoice_date'] != null) {
			$datum_min3 = $res['invoice_date'];
		}

		if ($datum_min3 != '0000-00-00' && $datum_min3 != null && $datum_min3 < $datum_min && $xcount > 0) {
			$datum_min = $datum_min3;
		}

		$cp['invoice'] = (diffDate2($datum_min3) < 1 && $xcount > 0) ? '<i class="icon-rouble red list" title="Выставленные счета"></i>' : '<i class="icon-rouble green list" title="Выставленные счета"></i>';

		if ($icount == 0) {
			$cp['invoice'] = '<i class="icon-rouble gray list" title="Выставленные счета отсутствуют"></i>';
		}

		$day = round(diffDate2($datum_min), 0);

		if ( $otherSettings[ 'taskControlInHealth']) {

			$taskcount = (int)$db -> getOne("SELECT COUNT(*) FROM {$sqlname}tasks WHERE did = '".$data['did']."' and active = 'yes' and identity = '$identity'");
			$att = ($taskcount == 0) ? '<i class="icon-calendar-2 red list fs-07" title="По сделке нет напоминаний"></i>&nbsp;' : '';
			$cp['task'] = ($taskcount == 0) ? '<i class="icon-calendar-1 red list" title="Нет напоминаний"></i>' : '<i class="icon-calendar-1 green list" title="Есть напоминания"></i>';

		}

		//if ( diffDate($data['dplan']) < 0 || $taskcount == 0) {
		if ( diffDate(current_datum(), $data['dplan'] ) < 0 || $taskcount == 0) {

			$color  = "redbg-dark";
			$health = 0;

		}
		elseif ($day < 0) {
			$color  = "redbg-dark";
			$health = 0;
		}
		elseif ( $day <= 7 ) {
			$color  = "greenbg";
			//$health = 1;
			$day    += 100;
		}
		else {
			$color  = 'bluebg';
			$health = 2;
			$day    += 200;
		}

		//для запроса на число не здоровых сделок считаем только не здоровые
		$g = 0;
		if ($view == 'count' && $health == 0) {
			$g = 1;
		}
		elseif ($view != 'count') {
			$g = 1;
		}

		//количество дней на этап
		$dayCount = (int)$mFunnel[ $data['direction'] ][ $data['tip'] ]['steps'][ $data['idcategory'] ];

		//если активен контроль по дате изменения этапа
		if( $otherSettings[ 'stepControlInHealth']) {

			$lastStepChange = $db -> getOne("SELECT MAX(datum) as datum FROM {$sqlname}steplog WHERE did = '$data[did]'");

			if ($lastStepChange == '') $lastStepChange = $db -> getOne("SELECT datum_izm as datum FROM {$sqlname}dogovor WHERE did = '$data[did]'");
			if ($lastStepChange == '') $lastStepChange = $db -> getOne("SELECT datum as datum FROM {$sqlname}dogovor WHERE did = '$data[did]'");

			//количество дней с последнего изменения этапа
			$stepDay = abs(round(diffDate2($lastStepChange)));

			//print $data['did'].": ".$lastStepChange.": ".$stepDay."\n";

		}
		else {

			$stepDay = $dayCount - 1;

		}

		$icon = '<i class="icon-briefcase broun" title="Открыть в новом окне"></i>';

		// для замороженных сделок
		if($data['idcategory'] == $stepInHold['step']){
			$icon = '<i class="icon-snowflake-o bluemint" title="Открыть в новом окне"></i>';
		}

		//Здоровье сделки. конец.
		if ( ($g > 0 && $color == 'redbg-dark') || ($stepDay > $dayCount)) {

			$dogs[] = [
				"health"     => $health,
				"did"        => $data['did'],
				"datum"      => $data['dcreate'],
				"datum_plan" => $data['dplan'],
				"datum_min"  => $datum_min,
				"step"       => $data['step'],
				"steptitle"  => $data['steptitle'],
				"client"     => $client,
				"clid"       => $data['clid'],
				"deal"       => $data['title'],
				"kol"        => num_format( $data['kol'] ),
				"summa"      => num_format( $summa ),
				"iduser"     => $data['iduser'],
				"user"       => $data['user'],
				"day"        => $day,
				"color"      => $color,
				"icon"       => $icon,
				"task"       => $att,
				"cp"         => $cp,
				"dayCount"   => $dayCount,
				"stepDay"    => $stepDay,
				"stepChange" => ($dayCount < $stepDay) ? '<i class="icon-filter red" title="Нет движения по этапу. Последнее движение '.$stepDay.' дней назад [ Регламент = '.$dayCount.' дн. ]"></i>' : '<i class="icon-filter green" title="Последнее движение '.$stepDay.' дней назад [ Регламент = '.$dayCount.' дн. ]"></i>',
				"diff" => diffDate(current_datum(), $data['dplan'] )
			];

		}

		$datum_min = 0;

	}

	//print_r($otherSettings);

	function cmp($a, $b): bool { return $b['day'] < $a['day']; }

	usort($dogs, 'cmp');

	//Для отображения счетчика
	if ($view == 'count') {

		print count( $dogs );
		exit();

	}

	$users = [];
	$us = $db -> getAll("SELECT iduser, title FROM {$sqlname}user WHERE iduser IN (".yimplode(",", get_people($iduser1, "yes")).") and identity = '$identity' ORDER BY title");
	foreach ($us as $data){

		$users[] = [
			"iduser" => $data['iduser'],
			"title" => $data['title'],
			"checked" => (in_array($data['iduser'], (array)$user)) ? "checked" : "",
			"current" => ($data['iduser'] == $iduser1) ? true : NULL
		];

	}

	//для вывода списка. новое
	if ( $_REQUEST['modal'] != 'true' ) {

		$lists = [
			"list"  => $dogs,
			"users" => $users,
		];

		print json_encode_cyr($lists);

		exit();

	}

	//для вывода списка. старое
	if ( $_REQUEST['modal'] != 'true' && $_REQUEST['old'] == 'true') {
		?>

		<TABLE id="zebraTable">
			<thead class="sticked--top">
			<?php if($_COOKIE['width'] > 700){ ?>
			<tr class="hidden-iphone bgwhite th45">
				<th colspan="11" class="text-left relativ">

					<div class="fs-14 Bold black"><i class="icon-medkit red"></i> Здоровье сделок</div>
					<div class="pull-right pr10 zindex-20">

						<div id="chuser" class="ydropDown w200 p5 fs-11">
							<?php
							$users = $db -> getAll("SELECT iduser, title FROM {$sqlname}user WHERE iduser IN (".yimplode(",", get_people($iduser1, "yes")).") and identity = '$identity' ORDER BY title");
							?>
							<span><i class="icon-users-1 black"></i></span>
							<span class="ydropCount">0 выбрано</span>
							<i class="icon-angle-down pull-aright"></i>
							<div class="yselectBox zindex-20" style="max-height: 300px; top: calc(100% + 55px)" data-func="healthFilter">

								<div class="right-text">
									<div class="ySelectAll w0 inline" title="Выделить всё"><i class="icon-plus-circled"></i>Все</div>
									<div class="yunSelect w0 inline" title="Снять выделение"><i class="icon-minus-circled"></i>Очистить</div>
								</div>

								<?php
								foreach ($users as $data){

									$s = (in_array($data['iduser'], (array)$user)) ? "checked" : "";
									$clr = ($data['iduser'] == $iduser1) ? "green" : "";
									?>
									<div class="ydropString ellipsis w160">
										<label class="<?=$clr?>">
											<input class="taskss" type="checkbox" name="hl-user[]" id="hl-user[]" value="<?=$data['iduser']?>" <?=$s?>>&nbsp;<?=$data['title']?>
										</label>
									</div>
								<?php } ?>

							</div>
						</div>

					</div>

				</th>
			</tr>
			<?php } ?>
			<TR class="header_contaner fs-091 hidden-iphone th45">
				<th class="w20 text-center hidden-iphone">#</th>
				<th class="w60 text-center">Создана</th>
				<th class="w60 text-center">Дата план.</th>
				<th class="w80 text-center">Этап</th>
				<th class="w40"></th>
				<th class="text-center">Сделка</th>
				<th class="w150">Причины диагноза?</th>
				<th class="w120">Ответственный</th>
				<th class="w100 text-right hidden-ipad">Сумма</th>
				<th class="w100 text-right hidden-ipad">Оплачено</th>
				<th class="w60 text-right hidden">Дни</th>
				<th class="w10"></th>
			</TR>
			</thead>
			<tbody>
			<?php
			foreach ($dogs as $i => $dog) {
			?>
			<TR class="ha <?= $dog['color1'] ?> filtered th55" data-user="<?=$dog['iduser']?>">
				<TD class="text-center hidden-iphone"><?= ($i + 1) ?></TD>
				<TD class="text-center">
					<span class="fs-09"><?= format_date_rus($dog['datum']) ?></span></TD>
				<TD class="text-center">
					<span class="fs-09"><?= format_date_rus($dog['datum_plan']) ?></span></TD>
				<TD class="text-center" title="<?= $dog['steptitle'] ?>"><div><?= $dog['step'] ?>%</div></TD>
				<TD class="text-center hidden-iphone">
					<A href="javascript:void(0)" onclick="openDogovor('<?= $dog['did'] ?>')" title="Открыть в новом окне"><?= $deal['icon'] ?></A>
				</TD>
				<TD>
					<div class="ellipsis fs-11 Bold hand" title="Быстрый просмотр: <?= $dog['title'] ?>" onClick="viewDogovor('<?= $dog['did'] ?>')">
						<span class="bullet-mini hidden-iphone <?= $dog['color'] ?>"></span><span class="hidden-iphone">&nbsp;&nbsp;</span><?= $dog['title'] ?>
					</div>
					<br>
					<div class="ellipsis gray2 fs-10" title="<?= $dog['client'] ?>"><?= $dog['client'] ?></div>
				</TD>
				<TD onClick="doLoad('content/lists/dt.health.php?did=<?= $dog['did'] ?>&action=view')" class="hand">
					<div>
					<?= $dog['cp']['plan']."&nbsp;&nbsp;".$dog['cp']['complect']."&nbsp;&nbsp;".$dog['cp']['invoice']."&nbsp;&nbsp;".$dog['cp']['task'] ?>
					<?php

					print ($dog['dayCount'] < $dog['stepDay']) ? '<i class="icon-filter red" title="Нет движения по этапу. Последнее движение '.$dog['stepDay'].' дней назад [ Регламент = '.$dog['dayCount'].' дн. ]"></i>' : '<i class="icon-filter green" title="Последнее движение '.$dog['stepDay'].' дней назад [ Регламент = '.$dog['dayCount'].' дн. ]"></i>';

					?>
					</div>
				</TD>
				<TD class="hidden-ipad1">
					<div class="ellipsis"><?= $dog['user'] ?></div>
				</TD>
				<TD class="text-right hidden-ipad1"><div><?= num_format($dog['kol']) ?></div></TD>
				<TD class="text-right hidden-ipad1"><div><?= num_format($dog['summa']) ?></div></TD>
				<TD class="text-right hidden"><div><?= $dog['day'] ?></div></TD>
				<TD></TD>
			</TR>
			<?php } ?>
			</tbody>
		</TABLE>
		<script>

			$( function() {

				$('#hl-user\\[\\]')
					.off('click')
					.on('click', function(){
						healthFilter();
					});

			});

			function healthFilter(){

				var husers = [];

				$('#hl-user\\[\\]:checked').each(function(){

					husers.push($(this).val());

				});

				$('tr.filtered').each(function(){

					if(!in_array($(this).data('user'), husers) && husers.length > 0)
						$(this).addClass('hidden');

					else
						$(this).removeClass('hidden');

				});

			}

		</script>
		<?php

	}

	//для вывода модального окна
	if ( $_REQUEST['modal'] == 'true' ) {
		?>

		<DIV class="zagolovok">Здоровье сделок</DIV>

		<div class="tableHeader" style="position:absolute; width: 100%"></div>
		<div id="formtabs" class="relativ" style="overflow-y:auto !important; overflow-x:hidden">

			<TABLE id="helthTable">
				<thead class="sticked--top">
				<TR class="header_contaner fs-09 th30">
					<th class="w30 text-center">#</th>
					<th class="w60 text-center">Создана</th>
					<th class="w60 text-center">Дата план.</th>
					<th class="w80 text-center">Этап</th>
					<th class="w40"></th>
					<th class="text-center">Сделка</th>
					<th class="w150 text-left">Причины диагноза?</th>
					<th class="w120">Ответственный</th>
					<th class="w100 text-right hidden-ipad">Сумма</th>
					<th class="w100 text-right hidden-ipad">Оплачено</th>
					<th class="w60 text-right hidden">Дни</th>
					<th class="w10"></th>
				</TR>
				</thead>
				<tbody>
				<?php
				foreach($dogs as $i => $deal) {
					?>
					<TR class="ha <?= $deal['color1'] ?> th55">
						<TD class="text-center">
							<span class="fs-09"><?= $i + 1 ?></span>
						</TD>
						<TD class="text-center">
							<span class="fs-09"><?= format_date_rus($deal['datum']) ?></span>
						</TD>
						<TD class="text-center">
							<span class="fs-09"><?= format_date_rus($deal['datum_plan']) ?></span></TD>
						<TD class="text-center" title="<?= $deal['steptitle'] ?>"><?= $deal['step'] ?>%</TD>
						<TD class="text-center">
							<A href="javascript:void(0)" onclick="openDogovor('<?= $deal['did'] ?>')" title="Открыть в новом окне"><?= $deal['icon'] ?></A>
						</TD>
						<TD>
							<div class="ellipsis fs-11 Bold hand" title="Быстрый просмотр: <?= $deal['title'] ?>" onClick="viewDogovor('<?= $deal['did'] ?>')">
								<span class="bullet-mini <?= $deal['color'] ?>">&nbsp;</span>&nbsp;<?= $deal['deal'] ?>
							</div>
							<br>
							<div class="ellipsis gray2 fs-10" title="<?= $deal['client'] ?>"><?= $deal['client'] ?></div>
						</TD>
						<TD onclick="doLoad('/content/desktop/dt.health.php?did=<?= $deal['did'] ?>&action=view')" class="hand">
							<?= $deal['cp']['plan']."&nbsp;&nbsp;".$deal['cp']['complect']."&nbsp;&nbsp;".$deal['cp']['invoice']."&nbsp;&nbsp;".$deal['cp']['task'] ?>
							<?php echo $deal['stepChange']; ?>
						</TD>
						<TD class="hidden-ipad">
							<div class="ellipsis"><?= $deal['user'] ?></div>
						</TD>
						<TD class="text-right hidden-ipad"><?= $deal['kol'] ?></TD>
						<TD class="text-right hidden-ipad"><?= $deal['summa'] ?></TD>
						<TD class="text-right hidden"><?= $deal['day'] ?></TD>
						<TD></TD>
					</TR>
				<?php } ?>
				</tbody>
			</TABLE>

		</div>
		<!--<script src="js/tableHeadFixer.js"></script>-->
		<script>

			if(!isMobile) {

				$('#dialog').css({'width': '80vw'});
				$('#formtabs').css({'min-height': '60vh', 'max-height': '80vh'});

			}
			else{

				var h2 = $(window).height() - $('.zagolovok').actual('outerHeight') - 30;
				$('#formtabs').css({'max-height': h2 + 'px','height': h2 + 'px'});

			}

			/*if (!isMobile) $("#helthTable").tableHeadFixer({'z-index': 12000});*/

			if (isMobile)
				$('#dialog').find('#helthTable').rtResponsiveTables({id:'dtable-helth'});

			$('#formtabs').find('.ellipsis').css({"position": "inherit"});
			$('#formtabs').find('i').css({"position": "inherit"});

		</script>
		<?php

	}

	exit();

}

if ($action == 'view') {
	?>

	<DIV class="zagolovok">Параметры контроля сделки</DIV>

	<div class="viewdiv mb10 fs-12">
		<i class="icon-briefcase-1 blue"></i>&nbsp;<?= getDogData($did, 'title') ?>
	</div>

	<div class="p10 bgwhite" style="overflow-x:hidden; overflow-y: auto; max-height: calc(80vh - 80px)">

		<?php
		/**
		 * По кол-ву дней с последней смены этапа
		 */
		$deal = Deal::info($did);

		$dayCount = $mFunnel[$deal['direction']][$deal['tip']]['steps'][$deal['step']['stepid']];

		$lastStepChange = $db -> getOne("SELECT MAX(datum) as datum FROM {$sqlname}steplog WHERE did = '$did'");

		if($lastStepChange == '')
			$lastStepChange = $db -> getOne("SELECT datum_izm as datum FROM {$sqlname}dogovor WHERE did = '$did'");

		if($lastStepChange == '')
			$lastStepChange = $db -> getOne("SELECT datum as datum FROM {$sqlname}dogovor WHERE did = '$did'");

		//количество дней с последнего изменения этапа
		$stepDay = abs(round(diffDate2( $lastStepChange )));

		if ($stepDay <= $dayCount) {

			print '
					<div class="fs-12 pt5 pb10 Bold">Движение по воронке: норма = '.$dayCount.' дн.</div>
					<div class="success marg0"><i class="icon-ok-circled green"></i>Изменено <b>'.$stepDay.'</b> дн. назад</div>
				';

		}
		else {

			print '
					<div class="fs-12 pt5 pb10 Bold">Движение по воронке: норма = '.$dayCount.' дн.</div>
					<div class="warn marg0"><i class="icon-attention-1 red"></i>Изменено <b class="red">'.$stepDay.'</b> дн. назад</div>
				';

		}

		/**
		 * По плановой дате сделки
		 */
		$datum_plan = $db -> getOne("SELECT datum_plan FROM {$sqlname}dogovor WHERE did = '$did' and identity = '$identity'");
		$delta_date = datestoday($datum_plan);
		if ($delta_date > 0) {

			print '
				<div class="fs-12 mt10 pt5 pb10 Bold">По плановой дате: <b>'.format_date_rus_name($datum_plan).'</b></div>
				<div class="success marg0">Осталось <b>'.$delta_date.'</b> дн.</div>
			';

		}
		if ($delta_date <= 0) {

			print '
				<div class="fs-12 mt10 pt5 pb10 Bold">По плановой дате: <b>'.format_date_rus_name($datum_plan).'</b></div>
				<div class="warn marg0"><i class="icon-attention-1 red"></i>Просрочено <b class="red">'.$delta_date.'</b> дн.</div>
			';

		}

		/**
		 * По наличию напоминаний
		 */
		$task = $db -> getOne("SELECT COUNT(*) FROM {$sqlname}tasks WHERE did = '$did' and identity = '$identity'") + 0;
		if ( $otherSettings[ 'taskControlInHealth']) {

			if ($task > 0) {

				print '
					<div class="fs-12 mt10 pt5 pb10 Bold">Наличие напоминаний</div>
					<div class="success marg0"><i class="icon-ok-circled green"></i>По сделке есть напоминания</div>
				';

			}
			if ($task == 0) {

				print '
					<div class="fs-12 mt10 pt5 pb10 Bold">Наличие напоминаний</div>
					<div class="warn marg0"><i class="icon-attention-1 red"></i>По сделке нет напоминаний</div>
				';

			}

		}

		/**
		 * По оплатам
		 */
		$credit = $db -> getAll("SELECT * FROM {$sqlname}credit WHERE did = '$did' and identity = '$identity' ORDER by crid");
		if (!empty($credit)) {
			?>
			<div class="fs-12 mt10 pt5 pb10 Bold">По платежам</div>
			<table id="zebra">
				<thead>
				<tr>
					<th width="40" align="center"><b>№</b></th>
					<th width="130" align="center"><b>Сумма, <?= $valuta ?></b></th>
					<th width="100" align="center"><b>Дата платежа</b></th>
					<th width="120" align="center">№ счета</th>
					<th width="120" align="center">№ договора</th>
					<th width="120" align="center">Дата платежки</th>
					<th width="30" align="center"><b>Оплата</b></th>
					<th width="60" align="center"><b>Дней</b></th>
				</tr>
				</thead>
				<?php
				$num = 1;
				foreach ($credit as $data) {

					$do = ($data['do'] == 'on') ? '<b class="green">да</b>' : '<b class="red">нет</b>';

					$day = round(diffDate2($data['datum_credit']));

					if ($day < 0) $day = '<b class="red">'.$day.'</b>';
					else $day = '<b class="green">'.$day.'</b>';

					?>
					<tr class="ha" height="40">
						<td align="center"><?= $num ?></td>
						<td align="right"><?= num_format($data['summa_credit']) ?></td>
						<td align="right"><?= format_date_rus($data['datum_credit']) ?></td>
						<td align="right"><?= $data['invoice'] ?></td>
						<td align="right"><?= $data['invoice_chek'] ?></td>
						<td align="right"><?php if ($data['invoice_date'] != "0000-00-00") print format_date_rus_name($data['invoice_date']) ?></td>
						<td align="center"><b><?= $do ?></b></td>
						<td align="center">
							<?php
							if ($data['do'] != "on") print $day."&nbsp;дн.";
							?>
						</td>
					</tr>
					<?php
					$num++;

				}
				?>
			</table>
			<?php
		}

		/**
		 * По контрольным точкам
		 */
		if ($complect_on == 'yes' and $tarif != 'Base') {

			$resu = $db -> getAll("SELECT * FROM {$sqlname}complect WHERE did = '".$did."' and identity = '$identity'");
			if (!empty($resu)) {

				?>
				<div class="fs-12 mt10 pt5 pb10 Bold">По контрольным точкам</div>
				<table id="zebra">
					<thead>
					<tr>
						<th width="200" align="center"></th>
						<th width="100" align="center">Дата план.</th>
						<th width="100" align="center">Дата факт.</th>
						<th width="100" align="center"><b>Сделано?</b></th>
					</tr>
					</thead>
					<?php
					$i = 0;

					$resultct = $db -> getAll("SELECT * FROM {$sqlname}complect_cat WHERE identity = '$identity' ORDER BY title");
					foreach ($resultct as $dataa) {

						$resultcc  = $db -> getRow("SELECT * FROM {$sqlname}complect WHERE ccid = '".$dataa['ccid']."' and did = '".$did."' and identity = '$identity' ORDER BY id");
						$id        = $resultcc["id"];
						$ccid      = $resultcc["ccid"];
						$data_plan = $resultcc["data_plan"];
						$data_fact = $resultcc["data_fact"];
						$doit      = $resultcc["doit"];

						if ($data_fact == '0000-00-00') $data_fact = '-';

						if ($data_plan != '') {

							$do  = ($doit == 'yes') ? '<b class="green">да</b>' : '<b class="red">нет</b>';
							$day = round(diffDate2($data_plan));

							if ($day <= 0 && $doit != 'yes') $day = '<b class="red">нет</b> [ '.$day.' дн. ]';
							if ($day > 0 && $doit != 'yes') $day = '<b class="green">нет</b> [ '.$day.' дн. ]';

							if ($doit == 'yes') $day = '<b class="green">да</b>';
							?>
							<tr class="ha" height="40">
								<td><?= $dataa['title'] ?></td>
								<td align="center"><?= format_date_rus($data_plan) ?></td>
								<td align="center"><?= format_date_rus($data_fact) ?></td>
								<td align="center"><?= $day ?></td>
							</tr>
							<?php

						}

					}
					?>
				</table>
				<?php
			}

		}
		?>

	</div>

	<hr>

	<div class="button--pane text-right">

		<a href="javascript:void(0)" onclick="openDogovor('<?= $did ?>')" class="button"><i class="icon-briefcase"></i>Карточка сделки</a>

	</div>

	<script>

		$('#dialog').css('width', '800px').center();

	</script>
	<?php
}