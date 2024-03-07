<?php
/* ============================ */
/*         SalesMan CRM         */
/* ============================ */
/* (C) 2016 Vladislav Andreev   */
/*       SalesMan Project       */
/*        www.isaler.ru         */
/*        ver. 2017.x           */
/* ============================ */
/* Developer: Iskopaeva Liliya  */

error_reporting( E_ERROR );
ini_set( 'display_errors', 1 );
header( "Pragma: no-cache" );

$rootpath = dirname(__DIR__);

include $rootpath."/inc/config.php";
include $rootpath."/inc/dbconnector.php";
include $rootpath."/inc/auth.php";
include $rootpath."/inc/func.php";
include $rootpath."/inc/settings.php";
include $rootpath."/inc/language/".$language.".php";

function dateFormat($date_orig, $format = 'excel') {

	$date_new = '';

	if ( $format == 'excel' ) {

		if ( $date_orig != '0000-00-00' and $date_orig != '' and $date_orig != NULL ) {
			/*
			$dstart = $date_orig;
			$dend = '1970-01-01';
			$date_new = intval((date_to_unix($dstart) - date_to_unix($dend))/86400)+25570;
			*/
			$date_new = $date_orig;
		}
		else $date_new = '';

	}
	elseif ( $format == 'date' ) {

		if ( $date_orig && $date_orig != '0000-00-00' ) {

			$date_new = explode( "-", $date_orig );
			$date_new = $date_new[1].".".$date_new[2].".".$date_new[0];

		}
		else $date_new = '';

	}
	elseif ( $date_orig != '0000-00-00' || $date_orig == '' )
		$date_new = '';

	return $date_new;
}

function num2excelExt($string, $s = 2) {

	if ( !$string )
		$string = 0;

	$string = str_replace( ",", ".", $string );
	$string = str_replace( " ", " ", $string );

	$string = number_format( $string, $s, '.', ' ' );

	return $string;
}

function date2mounthyear($date) {
	$date = yexplode( "-", $date );

	return $date[0]."-".$date[1];
}

function date2array($date) {
	$date = yexplode( "-", $date );

	return [
		$date[0],
		$date[1],
		$date[2]
	];
}

$action    = $_REQUEST['action'];
$subaction = $_REQUEST['subaction'];
$da1       = $_REQUEST['da1'];
$da2       = $_REQUEST['da2'];
$period    = $_REQUEST['period'];

$clientTip       = (array)$_REQUEST['clientTip'];
$clientTerritory = (array)$_REQUEST['clientTerritory'];
$clientPath      = (array)$_REQUEST['clientPath'];
$direction       = (array)$_REQUEST['direction'];
$stepid          = $_REQUEST['step'];
$userlist        = (array)$_REQUEST['user_list'];
$field           = (array)$_REQUEST['field'];
$field_query     = (array)$_REQUEST['field_query'];

$period = ($period == '') ? getPeriod( 'month' ) : getPeriod( $period );

$da1 = ($da1 != '') ? $da1 : $period[0];
$da2 = ($da2 != '') ? $da2 : $period[1];

$sort     = '';
$dirs     = [];
$dirc     = [];
$dataset1 = [];
$order    = [];

$color = [
	'#AD1457',
	'#FF8A65',
	'#F9A825',
	'#2E7D32',
	'#0277BD',
	'#3F51B5',
	'#6A1B9A',
	'#546E7A',
	'#78909C',
	'#00695C',
	'#9E9D24'
];

$thisfile = basename( $_SERVER['PHP_SELF'] );

//массив выбранных пользователей
$userlist = ($userlist[0] == '') ? get_people( $iduser1, "yes" ) : $userlist;

if ( empty( $userlist ) )
	$sort .= " and {$sqlname}dogovor.iduser IN (".implode( ",", get_people( $iduser1, "yes" ) ).")";
else                  $sort .= " and {$sqlname}dogovor.iduser IN (".implode( ",", $userlist ).")";

//фильтр по типам клиентов
if ( !empty( $clientTip ) )
	$sort .= " and {$sqlname}clientcat.type IN (".yimplode( ",", $clientTip, "'" ).")";

//фильтр по территории
if ( !empty( $clientTerritory ) )
	$sort .= " and {$sqlname}clientcat.territory IN (".yimplode( ",", $clientTerritory ).")";

//фильтр по источнику
if ( !empty( $clientPath ) )
	$sort .= " and {$sqlname}clientcat.clientpath IN (".yimplode( ",", $clientPath ).")";

//фильтр по направлениям
if ( !empty( $direction ) )
	$sort .= " and {$sqlname}dogovor.direction IN (".yimplode( ",", $direction ).")";


//составляем запрос по параметрам сделок
$ar = ['sid'];
foreach ( $field as $i => $fi )
	if ( !in_array( $fi, $ar ) && $fi != '' )
		$sort .= " and {$sqlname}dogovor.".$fi." = '".$field_query[ $i ]."'";

foreach ( $fields as $i => $field ) {

	if ( !in_array( $field, $ar ) && !in_array( $field, [
			'close',
			'mcid'
		] ) ) {
		$sort .= " AND {$sqlname}dogovor".$field." = '".$field_query[ $i ]."'";
	}
	elseif ( $field == 'close' ) {
		$sort .= $field_query[ $i ] != 'yes' ? " AND COALESCE({$sqlname}dogovor.{$field}, 'no') != 'yes'" : " AND COALESCE({$sqlname}dogovor.{$field}, 'no') == 'yes' ";
	}
	elseif ( $field == 'mcid' ) {
		$mc = $field_query[ $i ];
	}

}

$format = ($action == 'export') ? 'excel' : 'date';


$dogs = $total = $sdogs = [];

if ( $action == 'view' ) {

	$resultFilter = $_REQUEST['result'];
	$user         = $_REQUEST['user'];

	if ( !empty( $user ) )
		$sort .= " and {$sqlname}user.title = '$user'";
	$sort .= ($subaction == 'step') ? '' : ($resultFilter == 'good' ? " and {$sqlname}dogovor.kol_fact > 0" : " and {$sqlname}dogovor.kol_fact = 0");

}

$query = "
	SELECT 
		{$sqlname}dogovor.did,
		{$sqlname}dogovor.title as deal,
		{$sqlname}dogovor.datum as dstart,
		{$sqlname}dogovor.datum_close as dend,
		{$sqlname}dogovor.close as close,
		DATEDIFF({$sqlname}dogovor.datum, {$sqlname}dogovor.datum_close) as duration,
		{$sqlname}dogovor.kol as psumma,
		{$sqlname}dogovor.kol_fact as summa,
		{$sqlname}dogovor.marga as marga,
		{$sqlname}dogovor.clid as clid,
		{$sqlname}dogcategory.title as step,
		{$sqlname}clientcat.title as client,
		{$sqlname}user.title as user,
		{$sqlname}direction.title as direction,
		{$sqlname}dogstatus.title as dstatus
	FROM {$sqlname}dogovor 
		LEFT JOIN {$sqlname}clientcat ON {$sqlname}clientcat.clid = {$sqlname}dogovor.clid 
		LEFT JOIN {$sqlname}clientpath ON {$sqlname}clientpath.id = {$sqlname}clientcat.clientpath 
		LEFT JOIN {$sqlname}territory_cat ON {$sqlname}territory_cat.idcategory = {$sqlname}clientcat.territory
		LEFT JOIN {$sqlname}user ON {$sqlname}user.iduser = {$sqlname}dogovor.iduser
		LEFT JOIN {$sqlname}dogcategory ON {$sqlname}dogcategory.idcategory = {$sqlname}dogovor.idcategory
		LEFT JOIN {$sqlname}direction ON {$sqlname}dogovor.direction = {$sqlname}direction.id
		LEFT JOIN {$sqlname}dogstatus ON {$sqlname}dogovor.sid = {$sqlname}dogstatus.sid
	WHERE 
		{$sqlname}dogovor.did > 0 and 
		{$sqlname}dogovor.datum_close between '".$da1." 00:00:00' and '".$da2." 23:59:59' and
		{$sqlname}dogovor.identity = '$identity'
		$sort
	ORDER BY duration DESC
";

$rez = $db -> query( $query );
while ($da = $db -> fetch( $rez )) {

	$result   = ($da['close'] == 'yes' ? ($da['summa'] > 0 ? "good" : "bad") : 'open');
	$duration = diffDate2( $da['dend'], $da['dstart'] );

	$dogs[ $da['user'] ][ $result ][] = [
		"did"       => $da['did'],
		"deal"      => $da['deal'],
		"close"     => $da['close'],
		"step"      => $da['step'],
		"dstart"    => $da['dstart'],
		"dend"      => $da['dend'],
		"summa"     => $da['summa'],
		"psumma"    => $da['psumma'],
		"marga"     => $da['marga'],
		"clid"      => $da['clid'],
		"client"    => $da['client'],
		"direction" => $da['direction'],
		"dstatus"   => $da['dstatus'],
		"duration"  => abs( $da['duration'] )
	];

}


/**
 * Расчет длительности достижения сделок указанного этапа
 * Здесь учитываем все сделки, созданные в указанный период
 */
if ( $stepid > 0 ) {

	//$sdogs = [];

	if ( $subaction == 'step' )
		$dogs = [];
	$stepname = current_dogstepname( $stepid );

	$query = "
		SELECT 
			{$sqlname}dogovor.did,
			{$sqlname}dogovor.title as deal,
			{$sqlname}dogovor.datum as dstart,
			{$sqlname}dogovor.datum_close as dend,
			{$sqlname}dogovor.close as close,
			DATEDIFF({$sqlname}dogovor.datum, {$sqlname}dogovor.datum_close) as duration,
			{$sqlname}dogovor.kol as psumma,
			{$sqlname}dogovor.kol_fact as summa,
			{$sqlname}dogovor.marga as marga,
			{$sqlname}dogovor.clid as clid,
			{$sqlname}dogcategory.title as step,
			{$sqlname}clientcat.title as client,
			{$sqlname}user.title as user,
			{$sqlname}direction.title as direction,
			{$sqlname}dogstatus.title as dstatus
		FROM {$sqlname}dogovor 
			LEFT JOIN {$sqlname}clientcat ON {$sqlname}clientcat.clid = {$sqlname}dogovor.clid 
			LEFT JOIN {$sqlname}clientpath ON {$sqlname}clientpath.id = {$sqlname}clientcat.clientpath 
			LEFT JOIN {$sqlname}territory_cat ON {$sqlname}territory_cat.idcategory = {$sqlname}clientcat.territory
			LEFT JOIN {$sqlname}user ON {$sqlname}user.iduser = {$sqlname}dogovor.iduser
			LEFT JOIN {$sqlname}dogcategory ON {$sqlname}dogcategory.idcategory = {$sqlname}dogovor.idcategory
			LEFT JOIN {$sqlname}direction ON {$sqlname}dogovor.direction = {$sqlname}direction.id
			LEFT JOIN {$sqlname}dogstatus ON {$sqlname}dogovor.sid = {$sqlname}dogstatus.sid
		WHERE 
			{$sqlname}dogovor.did > 0 and 
			-- {$sqlname}dogovor.close != 'yes' and 
			{$sqlname}dogovor.datum between '".$da1." 00:00:00' and '".$da2." 23:59:59' and
			{$sqlname}dogovor.identity = '$identity'
			$sort
		ORDER BY did DESC
	";

	$rez = $db -> query( $query );
	while ($da = $db -> fetch( $rez )) {

		//дата последнего изменения этапов по этой сделке
		$maxdatum = $db -> getOne( "SELECT MAX(datum) FROM {$sqlname}steplog WHERE did = '".$da['did']."' AND step = '$stepid'" );

		//print $da['did']." : ".$maxdatum."<br>";

		if ( $maxdatum != '' ) {

			$maxdatum = $db -> getOne( "SELECT MAX(datum) FROM {$sqlname}steplog WHERE did = '".$da['did']."'" );

		}

		//дата перехода на выбранные этап (+ защита от учета сделок с откатом этапа)
		$datum = $db -> getOne( "SELECT datum FROM {$sqlname}steplog WHERE did = '".$da['did']."' AND step = '$stepid' ORDER BY datum DESC LIMIT 1" );

		//если текущий этап сделки меньше выбранного, то исключаем из набора (+ защита от учета сделок с откатом этапа)
		if ( $da['step'] < $stepname )
			$datum = '';

		if ( $datum != '' ) {

			$datum = get_smdate( $datum );

			$result    = ($da['close'] == 'yes') ? ($da['summa'] > 0 ? "good" : "bad") : 'open';
			$sduration = diffDate2( $datum, $da['dstart'] );

			$sdogs[ $da['user'] ][] = abs( $sduration );

			if ( $subaction == 'step' )
				$dogs[ $da['user'] ][ $result ][] = [
					"did"       => $da['did'],
					"deal"      => $da['deal'],
					"close"     => $da['close'],
					"step"      => $da['step'],
					"dstart"    => $da['dstart'],
					"dend"      => $datum,
					"summa"     => $da['summa'],
					"psumma"    => $da['psumma'],
					"marga"     => $da['marga'],
					"clid"      => $da['clid'],
					"client"    => $da['client'],
					"direction" => $da['direction'],
					"dstatus"   => $da['dstatus'],
					"duration"  => abs( $sduration )
				];

		}

	}

}

//print_r($sdogs);
//print_r($dogs);

if ( $action == 'view' ) {

	?>
	<hr>

	<div align="center">

		<h2 class="blue uppercase">Учтенные данные</h2>

	</div>

	<TABLE border="0" cellpadding="5" cellspacing="0" class="wp99" id="zebra">
		<THEAD>
		<TR height="40">
			<th width="30"></th>
			<th><b>Сделка</b></th>
			<th width="80"><b>Дата старт.</b></th>
			<th width="80"><b>Дата финиш.</b></th>
			<th width="60"><b>Длит-ть, дн.</b></th>
			<th width="100"><b>Сумма, <?= $valuta ?></b></th>
			<th width="60"><b>Этап</b></th>
			<th width="100"><b>Направление</b></th>
			<th width="100"><b>Результат. Причина</b></th>
			<th width="60"><b>Результат</b></th>
		</TR>
		</THEAD>
		<TBODY>
		<?php
		foreach ( $dogs as $user => $results ) {

			$nums   = 1;
			$clList = '';
			$summa  = 0;

			print '
			<tr class="fioletbg-sub title" height="40" data-user="'.$user.'">
				<td colspan="10" class="Bold fs-12">'.$user.'</td>
			</tr>
			';

			foreach ( $results as $result => $deals ) {

				foreach ( $deals as $i => $deal ) {

					$pcolor = ' progress-gray';

					if ( $deal['step'] >= 20 and $deal['step'] < 60 )
						$pcolor = ' progress-green';
					elseif ( $deal['step'] >= 60 and $deal['step'] < 90 )
						$pcolor = ' progress-blue';
					elseif ( $deal['step'] >= 90 and $deal['step'] <= 100 )
						$pcolor = ' progress-red';

					$iresult = ($result == 'good') ? '<i class="icon-ok green"></i>' : '<i class="icon-cancel-circled red"></i>';
					$icolor  = ($result == 'good') ? 'green' : 'red';

					$deal['summa']   = ($deal['close'] == 'yes') ? $deal['summa'] : $deal['psumma'];
					$deal['dstatus'] = ($deal['close'] == 'yes') ? $deal['dstatus'] : 'Открыта';
					if ( $deal['close'] != 'yes' ) {

						$icolor  = 'blue';
						$iresult = '';

					}

					$clList .= '
						<TR height="35" class="ha client" data-user="'.$user.'" data-result="'.$result.'">
							<TD width="30" align="right">#'.$nums.'&nbsp;</TD>
							<TD>
								<div class="ellipsis fs-11 Bold" title="'.$deal['deal'].'"><a href="javascript:void(0);" onclick="viewDogovor(\''.$deal['did'].'\')" title="Карточка"><i class="icon-briefcase-1 blue"></i>&nbsp;'.$deal['deal'].'</a></div><br>
								<div class="ellipsis fs-10" title="'.$deal['client'].'"><a href="javascript:void(0);" onclick="viewClient(\''.$deal['clid'].'\')" title="Карточка" class="gray"><i class="icon-building broun"></i>&nbsp;'.$deal['client'].'</a></div>
							</TD>
							<TD align="center">'.get_date( $deal['dstart'] ).'</TD>
							<TD align="center">'.get_date( $deal['dend'] ).'</TD>
							<TD align="right">'.number_format( ceil( $deal['duration'] ), 0, "", "` " ).'</TD>
							<TD align="right"><div title="'.num_format( $deal['summa'] ).'">'.num_format( $deal['summa'] ).'</div></TD>
							<TD>
								<DIV class="progressbarr m5 ml10 wp80">
								'.$deal['step'].'%<DIV id="test" class="progressbar-completed '.$pcolor.'" style="width:'.$deal['step'].'%;" title="'.$deal['step'].'%"></DIV>
								</DIV>
							</TD>
							<TD>'.$deal['direction'].'</TD>
							<TD class="'.$icolor.'">'.$deal['dstatus'].'</TD>
							<TD align="center">'.$iresult.'</TD>
						</TR>';

					$nums++;

				}

			}

			print $clList;

		}
		?>
		</TBODY>
	</TABLE>
	<?php

	exit();

}
?>

<style>
	<!--

	tfoot {
		height      : 40px;
		background  : rgba(207, 216, 220, 1);
		font-weight : 700;
		font-size   : 1.4rem;
	}

	@media print {
		.fixAddBotButton {
			display : none;
		}
	}

	table.borderer thead tr th,
	table.borderer tr,
	table.borderer td {
		border-left   : 1px dotted #ccc !important;
		border-bottom : 1px dotted #ccc !important;
		padding       : 2px 3px 2px 3px;
		height        : 30px;
	}

	table.borderer thead th:last-child {
		border-right : 1px dotted #ccc !important;
	}

	table.borderer thead tr:first-child th {
		border-top : 1px dotted #ccc !important;
	}

	table.borderer td:last-child {
		border-right : 1px dotted #ccc !important;
	}

	table.borderer td.scount span,
	table.borderer td.count span {
		padding-bottom : 2px;
		border-bottom  : 1px dashed #222 !important;
	}

	table.borderer td.scount:hover,
	table.borderer td.count:hover {
		background : rgba(197, 225, 165, 1);
		cursor     : pointer;
	}

	table.borderer thead {
		border : 1px dotted #ccc !important;
	}

	table.borderer thead td,
	table.borderer thead th {
		/*background : #E5F0F9;*/
		color : #222;
	}

	table.borderer thead th {
		border-bottom : 1px dotted #666 !important;
	}

	table.borderer thead {
		border : 1px dotted #222 !important;
	}

	-->
</style>

<div class="relativ mt20 mb20 wp95" align="center">
	<h1 class="uppercase fs-14 m0 mb10">Анализ продолжительности сделок</h1>
	<div class="gray2">закрытых за период&nbsp;с&nbsp;<?= format_date_rus( $da1 ) ?>&nbsp;по&nbsp;<?= format_date_rus( $da2 ) ?></div>
</div>

<div class="noprint">

	<hr>

	<div class="pad5 mt20 gray2 Bold">Фильтры по клиентам:</div>
	<!--дополнителный фильтры-->
	<table width="100%" border="0" cellspacing="0" cellpadding="5" class="noborder">
		<tr>
			<td width="25%">
				<div class="ydropDown">
					<span>По Типу клиента</span><span class="ydropCount"><?= count( $clientTip ) ?> выбрано</span><i class="icon-angle-down pull-aright"></i>
					<div class="yselectBox" style="max-height: 300px;">
						<div class="yunSelect"><i class="icon-cancel-circled2"></i>Снять выделение</div>

						<div class="ydropString ellipsis">
							<label>
								<input class="taskss" name="clientTip[]" type="checkbox" id="clientTip[]" value="client" <?php if ( in_array( "client", $clientTip ) )
									print 'checked'; ?>>&nbsp;Клиент: юр.лицо
							</label>
						</div>

						<div class="ydropString ellipsis">
							<label>
								<input class="taskss" name="clientTip[]" type="checkbox" id="clientTip[]" value="person" <?php if ( in_array( "person", $clientTip ) )
									print 'checked'; ?>>&nbsp;Клиент: физ.лицо
							</label>
						</div>

						<div class="ydropString ellipsis">
							<label>
								<input class="taskss" name="clientTip[]" type="checkbox" id="clientTip[]" value="partner" <?php if ( in_array( "partner", $clientTip ) )
									print 'checked'; ?>>&nbsp;Партнер
							</label>
						</div>

						<div class="ydropString ellipsis">
							<label>
								<input class="taskss" name="clientTip[]" type="checkbox" id="clientTip[]" value="contractor" <?php if ( in_array( "contractor", $clientTip ) )
									print 'checked'; ?>>&nbsp;Поставщик
							</label>
						</div>

						<div class="ydropString ellipsis">
							<label>
								<input class="taskss" name="clientTip[]" type="checkbox" id="clientTip[]" value="concurent" <?php if ( in_array( "concurent", $clientTip ) )
									print 'checked'; ?>>&nbsp;Конкурент
							</label>
						</div>

					</div>
				</div>
			</td>
			<td width="25%">
				<div class="ydropDown">
					<span>По Направлению</span><span class="ydropCount"><?= count( $direction ) ?> выбрано</span><i class="icon-angle-down pull-aright"></i>
					<div class="yselectBox" style="max-height: 300px;">
						<div class="yunSelect"><i class="icon-cancel-circled2"></i>Снять выделение</div>
						<div class="ydropString ellipsis">
							<label>
								<input class="taskss" name="direction[]" type="checkbox" id="direction[]" value="0" <?php if ( in_array( "0", $direction ) )
									print 'checked'; ?>>&nbsp;Не указано
							</label>
						</div>
						<?php
						$result = $db -> getAll( "SELECT * FROM {$sqlname}direction WHERE identity = '$identity' ORDER BY title" );
						foreach ( $result as $data ) {
							?>
							<div class="ydropString ellipsis">
								<label>
									<input class="taskss" name="direction[]" type="checkbox" id="direction[]" value="<?= $data['id'] ?>" <?php if ( in_array( $data['id'], $direction ) )
										print 'checked'; ?>>&nbsp;<?= $data['title'] ?>
								</label>
							</div>
						<?php } ?>
					</div>
				</div>
			</td>
			<td width="25%">
				<div class="ydropDown">

					<?php
					require_once "../inc/class/Guides.php";
					$steps = \Salesman\Guides ::Step();
					?>

					<span class="Bold">до Этапа</span>
					<span class="ydropText"><?= ($stepid > 0 ? $steps[ $stepid ] : 'Этап не выбран') ?></span>
					<i class="icon-angle-down pull-aright arrow"></i>

					<div class="yselectBox" style="max-height: 350px;">

						<div class="ydropString yRadio">
							<label><input type="radio" name="step" id="step" data-title="Этап не выбран" value="0" <?= ($step == 0 ? 'checked' : '') ?> class="hidden">&nbsp;Этап не выбран</label>
						</div>
						<?php
						foreach ( $steps as $step => $value ) {

							print '
							<div class="ydropString yRadio">
								<label><input type="radio" name="step" id="step" data-title="'.$value.'" value="'.$step.'" '.($step == $stepid ? 'checked' : '').' class="hidden">&nbsp;'.$value.'</label>
							</div>
							';

						}
						?>

					</div>

				</div>
			</td>
			<td width="25%">
				<div class="tagsmenuToggler hand relativ" data-id="fhelper">
					<span class="fs-12 blue"><i class="icon-help-circled"></i></span>
					<div class="tagsmenu fly right1 hidden" id="fhelper" style="right:90%; top: 100%">
						<div class="blok p10 w350 fs-09 flh-11">
							Если указан этап, то в колонке "Среднее до этапа" будет отображена средняя продолжительность сделок до достижения этого этапа.<br><br>
							При этом учитываются все <b>Активные</b> сделки, если в "Логе по этапам" есть об этом запись<br><br>
							<b>Если записи по этапу не найдено</b>, а сделка находится на более высоком этапе, то берется дата перехода на более высокий этап<br><br>
							<b>В других случаях Этап не учитывается.</b>
						</div>
					</div>
				</div>
			</td>
		</tr>
	</table>

</div>

<hr>

<table class="wp99 borderer" id="table">
	<thead>
	<tr height="40">
		<th rowspan="2">Сотрудник</th>
		<th colspan="3" class="greenbg-sub">Успешные</th>
		<th colspan="3" class="redbg-sub">Проигранные</th>
		<?php
		if ( $stepid > 0 ) {
			?>
			<th rowspan="2">Среднее до этапа, дн.</th>
		<?php } ?>
	</tr>
	<tr height="40">
		<th class="greenbg-sub">Кол-во</th>
		<th class="greenbg-sub">Длительность<br>средняя, дн.</th>
		<th class="greenbg-sub">Сумма факт., <?= $valuta ?></th>
		<th class="redbg-sub">Кол-во</th>
		<th class="redbg-sub">Длительность<br>средняя, дн.</th>
		<th class="redbg-sub">Сумма план., <?= $valuta ?></th>
	</tr>
	</thead>
	<tbody>
	<?php
	$itog = [];
	foreach ( $dogs as $user => $list ) {

		/**
		 * Расчеты по успешным сделкам
		 */

		$goodCount    = count( $list['good'] );//кол-во сделок
		$goodDuration = arraysum( $list['good'], 'duration' );//продолжительность суммарная
		$good         = ceil( $goodDuration / $goodCount );//средняя продолжительность
		$goodSumma    = arraysum( $list['good'], 'summa' );

		/**
		 * Расчеты по проигранным сделкам
		 */

		$badCount    = count( (array)$list['bad'] );
		$badDuration = (float)arraysum((array)$list['bad'], 'duration');
		$bad         = $badCount > 0 ? ceil( $badDuration / $badCount ) : 0;
		$badSumma    = (float)arraysum((array)$list['bad'], 'psumma');

		/**
		 * Считаем итоги
		 */
		$itog['goodCount']    += $goodCount;
		$itog['goodDuration'] += $goodDuration;
		$itog['goodSumma']    += $goodSumma;
		$itog['badCount']     += $badCount;
		$itog['badDuration']  += $badDuration;
		$itog['badSumma']     += $badSumma;

		/**
		 * Считаем продолжительность сделки до этапа
		 */
		$dd     = count( (array)$sdogs[ $user ] ) > 0 ? round( array_sum( (array)$sdogs[ $user ] ) / count( (array)$sdogs[ $user ] ), 1 ) : 0;
		$dmin   = !empty((array)$sdogs[ $user ]) ? round( min( (array)$sdogs[ $user ] ), 1 ) : 0;
		$dmax   = !empty((array)$sdogs[ $user ]) ? round( max( (array)$sdogs[ $user ] ), 1 ) : 0;
		$dcount = !empty((array)$sdogs[ $user ]) ? round( count( (array)$sdogs[ $user ] ), 1 ) : 0;

		print '
		<tr class="title" data-user="'.$user.'">
			<td class="Bold fs-12">&nbsp;'.$user.'</td>
			<td align="center" data-result="good" class="count"><span>&nbsp;'.$goodCount.'</span></td>
			<td align="center">&nbsp;'.$good.'</td>
			<td align="right">&nbsp;'.num_format( $goodSumma ).'</td>
			<td align="center" data-result="bad" class="count"><span>&nbsp;'.$badCount.'</span></td>
			<td align="center">&nbsp;'.$bad.'</td>
			<td align="right">&nbsp;'.num_format( $badSumma ).'</td>
			'.($stepid > 0 ? '<td align="center" data-result="middle" class="scount relativ">
				<span>&nbsp;'.$dd.' </span><sup class="bullet bluebg-sub fs-07 p2" title="Число сделок">'.$dcount.'</sup>&nbsp;&nbsp; [ min = '.$dmin.', max = '.$dmax.' ]
			</td>' : '').'
		</tr>
		';

	}
	?>
	</tbody>
	<tfoot>
	<tr height="40" class="graybg">
		<td class="Bold fs-11">&nbsp;Итого:</td>
		<td align="center"><?= $itog['goodCount'] ?></td>
		<td align="center"><?= ($itog['goodCount'] > 0 ? ceil( $itog['goodDuration'] / $itog['goodCount'] ) : 0) ?></td>
		<td align="right"><?= num_format( $itog['goodSumma'] ) ?></td>
		<td align="center"><?= $itog['badCount'] ?></td>
		<td align="center"><?= ($itog['badCount'] > 0 ? ceil( $itog['badDuration'] / $itog['badCount'] ) : 0) ?></td>
		<td align="right"><?= num_format( $itog['badSumma'] ) ?></td>
		<?php
		if ( $stepid > 0 ) {
			?>
			<td align="center"></td>
		<?php } ?>
	</tr>
	</tfoot>
</table>

<div id="datas" class="mt10"></div>

<div style="height:80px"></div>

<script>

	$(function () {

	});

	$(document).off('click', '.count');
	$(document).on('click', '.count', function () {

		var user = $(this).closest('tr').data('user');
		var result = $(this).data('result');
		var str = $('#selectreport').serialize();
		var custom = $('#customForm').serialize();
		var element = $('#datas');

		element.empty().append('<div id="loaderr" class="loaderr"><img src="/assets/images/loading.svg" width="40"></div>');

		$.get('reports/ent-DealDurationAnalyse.php?action=view&user=' + user + '&result=' + result + '&' + str + '&' + custom, function (data) {

			element.html(data);

		});

		var $top = element.offset();
		var ttop = $top.top - 150;

		$("#clientlist.nano").nanoScroller({scrollTop: ttop});

		$(".nano").nanoScroller();

	});

	$(document).off('click', '.scount');
	$(document).on('click', '.scount', function () {

		var user = $(this).closest('tr').data('user');
		var str = $('#selectreport').serialize();
		var custom = $('#customForm').serialize();
		var element = $('#datas');

		element.empty().append('<div id="loaderr" class="loaderr"><img src="/assets/images/loading.svg" width="40"></div>');

		$.get('reports/ent-DealDurationAnalyse.php?action=view&subaction=step&user=' + user + '&' + str + '&' + custom, function (data) {

			element.html(data);

		});

		var $top = element.offset();
		var ttop = $top.top - 150;

		$("#clientlist.nano").nanoScroller({scrollTop: ttop});

		$(".nano").nanoScroller();

	});

</script>