<?php
/* ============================ */
/*         SalesMan CRM         */
/* ============================ */
/* (C) 2020 Vladislav Andreev   */
/*       SalesMan Project       */
/*        www.isaler.ru         */
/*        ver. 2020.x           */

/* ============================ */

use Salesman\Client;
use Salesman\Guides;

error_reporting( E_ERROR );
ini_set( 'display_errors', 1 );
header( "Pragma: no-cache" );

$rootpath = realpath( __DIR__.'/../' );

include $rootpath."/inc/config.php";
include $rootpath."/inc/dbconnector.php";
include $rootpath."/inc/auth.php";
include $rootpath."/inc/func.php";
include $rootpath."/inc/settings.php";
include $rootpath."/inc/language/".$language.".php";

//print_r($_REQUEST);

$action  = $_REQUEST['action'];
$da1     = $_REQUEST['da1'];
$da2     = $_REQUEST['da2'];
$dp1     = $_REQUEST['dp1'];
$dp2     = $_REQUEST['dp2'];
$da      = $_REQUEST['da'];
$act     = $_REQUEST['act'];
$per     = $_REQUEST['per'];
$dperiod = $_REQUEST['dperiod'];
$close   = $_REQUEST['close'];

$mc = [];

if ( !$per ) {
	$per = 'nedelya';
}

$user_list   = (array)$_REQUEST['user_list'];
$field       = (array)$_REQUEST['field'];
$field_query = (array)$_REQUEST['field_query'];
$steps       = (array)$_REQUEST['step'];

$fields = array_combine( $field, $field_query );

//массив выбранных пользователей
$user_list = (!empty( $user_list )) ? $user_list : (array)get_people( $iduser1, "yes" );
if ( !empty( $user_list ) ) {
	$sort .= " {$sqlname}dogovor.iduser IN (".yimplode( ",", $user_list ).") AND ";
}

//составляем запрос по параметрам сделок
$ar = [
	'sid',
	'close'
];
foreach ( $fields as $i => $field ) {

	if ( !in_array( $field, $ar ) && !in_array( $field, [
			'close',
			'mcid'
		] ) ) {
		$sort .= " deal.".$field." = '".$field_query[ $i ]."' AND ";
	}
	elseif ( $field == 'close' ) {
		$sort .= $field_query[ $i ] != 'yes' ? " COALESCE(deal.{$field}, 'no') != 'yes' AND " : " COALESCE(deal.{$field}, 'no') == 'yes' AND ";
	}
	elseif ( $field == 'mcid' ) {
		$mc = $field_query[ $i ];
	}

}

$mycomps    = Guides ::myComps();
$directions = Guides ::Direction();
$tips       = Guides ::DealType();
$steplist   = Guides ::Step();

// учитываем закрытые успешные сделки
$closed = $close == 'yes' ? "OR (".$sqlname."dogovor.close = 'yes' AND ".$sqlname."dogovor.kol_fact > 0)" : "";

$sort .= !empty( $mc ) ? $sqlname."dogovor.mcid IN (".yimplode( ",", $mc ).") AND " : "";
$sort .= !empty( $steps ) ? "( (".$sqlname."dogovor.idcategory IN (".yimplode( ",", $steps ).") AND close != 'yes') $closed) AND " : "";
$sort .= !empty( $da1 ) ? $sqlname."dogovor.datum BETWEEN '$da1' AND '$da2' AND " : "";
$sort .= !empty( $dp1 ) ? $sqlname."dogovor.datum_plan BETWEEN '$dp1' AND '$dp2' AND " : "";

if ( empty( $steps ) ) {

	if ( $close == 'yes' ) {

		$sort .= " (".$sqlname."dogovor.close = 'yes' AND ".$sqlname."dogovor.kol_fact > 0) AND ";

	}
	else {

		$sort .= " (".$sqlname."dogovor.close != 'yes' OR (".$sqlname."dogovor.close = 'yes' AND ".$sqlname."dogovor.kol_fact > 0)) AND ";

	}

}

//print $sort;

$i = 0;

$kolSum = $margaSum = $summaCredit = $summaCreditMarga = 0;

$da = $db -> getAll( "SELECT * FROM {$sqlname}dogovor WHERE did > 0 AND $sort identity = '$identity' ORDER BY datum_plan" );
foreach ( $da as $data ) {

	$color  = $scolor = '';
	$dolya2 = 0;

	$client = ($data['clid'] > 0) ? current_client( $data['clid'] ) : current_person( $data['pid'] );

	$datum     = format_date_rus( $data['datum'] );//сдесь дата создания сделки
	$datum_min = $data['datum_plan']; //задаем начальную минимальную дату как плановую дату сделки

	//Сформируем сумму оплаченных счетов
	$summa = $db -> getOne( "SELECT SUM(summa_credit) FROM {$sqlname}credit WHERE did = '".$data['did']."' and do = 'on' and identity = '$identity'" );

	if ( $data['close'] == 'yes' ) {

		$data['kol'] = $data['kol_fact'];

	}

	// расчитаем прибыльность
	$dolya = ($data['kol'] > 0) ? $data['marga'] / $data['kol'] : 0;

	$kolSum           += $data['kol'];
	$margaSum         += $data['marga'];
	$summaCredit      += $summa;
	$summaCreditMarga += $summa * $dolya;

	$dolya2 = $data['kol'] > 0 ? round( ($summa / $data['kol']) * 100, 1 ) : 0;

	if ( $dolya2 > 0 && $summa == $data['kol'] )
		$color = 'greenbg-sub';

	elseif ( $dolya2 > 0 && $summa < $data['kol'] )
		$color = 'yellowbg-sub';

	//elseif( $dolya == 0 )
	//$color = '';

	//Последнее движение по сделке
	$md      = $db -> getOne( "SELECT MAX(datum) as datum FROM {$sqlname}steplog WHERE did = '".$data['did']."' and identity = '$identity'" );
	$stepDay = ($md != '') ? abs( round( diffDate2( $md ) ) ) : abs( round( diffDate2( $data['datum'] ) ) );

	$step = current_dogstepname( $data['idcategory'] );

	//цвет этапа
	if ( is_between( $step, 0, 20 ) )
		$scolor = ' gray';
	elseif ( is_between( $step, 20, 60 ) )
		$scolor = ' green';
	elseif ( is_between( $step, 60, 90 ) )
		$scolor = ' blue';
	elseif ( $step >= 90 )
		$scolor = ' red';

	$clientInfo = Client ::info( $data['clid'] );

	$dogs[] = [
		"did"        => $data['did'],
		"step"       => current_dogstep( $data['did'] ),
		"stepDay"    => $stepDay,
		"datum"      => $data['datum'],
		"datum_plan" => $data['datum_plan'],
		"direction"  => $directions[ $data['direction'] ],
		"tip"        => $tips[ $data['tip'] ],
		"client"     => $client,
		"clid"       => $data['clid'],
		"pid"        => $data['pid'],
		"title"      => $data['title'],
		"category"   => $clientInfo['client']['category'],
		"kol"        => $data['kol'],
		"marga"      => $data['marga'],
		"payment"    => $summa,
		"dolya"      => round( $dolya * 100, 1 ),
		"dolya2"     => $data['kol'] > 0 ? round( ($summa / $data['kol']) * 100, 1 ) : 0,
		"manager"    => current_user( $data['iduser'] ),
		"color"      => $color,
		"scolor"     => $scolor,
		"close"      => $data['close']
	];

	$i++;

}

//exit();

if ( $action == "export" ) {

	$list = [];

	foreach ( $dogs as $dog ) {

		$list[] = [
			"date"        => $dog['datum']." 12:00:00",
			"dateplan"    => $dog['datum_plan']." 12:00:00",
			"client"      => $dog['client'],
			"category"    => $dog['category'],
			"deal"        => $dog['title'],
			"step"        => $dog['step'] / 100,
			"summa"       => $dog['kol'],
			"marga"       => $dog['marga'],
			"credit"      => $dog['payment'] + 0,
			"dolya"       => $dog['dolya2'] / 100,
			"creditmarga" => $dog['marga'] * ($dog['dolya2'] / 100),
			"user"        => $dog['manager'],
			"direction"   => $dog['direction'],
			"tip"         => $dog['tip'],
		];

	}

	//print_r($list);
	//exit();

	$data = ["list" => $list];

	$templateFile = 'templates/bobTemp.xlsx';
	$outputFile   = 'exportDealsBob.xlsx';

	$TBS = new clsTinyButStrong; // new instance of TBS
	$TBS -> PlugIn( TBS_INSTALL, OPENTBS_PLUGIN ); // load the OpenTBS plugin

	$TBS -> SetOption( noerr, true );
	$TBS -> LoadTemplate( $templateFile, OPENTBS_ALREADY_UTF8 );

	$TBS -> MergeBlock( 'list', $data['list'] );
	$TBS -> Show( OPENTBS_DOWNLOAD, $outputFile );

	exit();

}

?>
<STYLE type="text/css">
	<!--
	.raiting {
		width              : 170px;
		/*height:280px;*/
		display            : inline-block;
		padding            : 10px;
		border             : 0 dotted #ddd;
		box-sizing         : border-box;
		-moz-box-sizing    : border-box;
		-webkit-box-sizing : border-box;
	}

	.raiting:hover {
		-moz-box-shadow    : 0 0 5px #999;
		-webkit-box-shadow : 0 0 5px #999;
		box-shadow         : 0 0 5px #999;
	}

	.progressbar-completed,
	.status {
		height     : 0.4em;
		box-sizing : border-box;
	}

	.progressbar-completed div {
		display : inline;
	}

	#swindow .avatarbig,
	#clientlist .avatarbig {
		width                 : 50px;
		height                : 50px;
		margin                : 0 auto;
		border                : 6px solid #E74B3B;
		border-radius         : 200px;
		-webkit-border-radius : 200px;
		-moz-border-radius    : 200px;
	}
	#swindow .avatarbig--inner,
	#clientlist .avatarbig--inner {
		width                 : 48px;
		height                : 48px;
		margin                : 0 auto;
		border                : 2px solid #FFF;
		padding-top           : -2px;
		border-radius         : 200px;
		-webkit-border-radius : 200px;
		-moz-border-radius    : 200px;
	}
	#swindow .avatar--mini,
	#clientlist .avatar--mini {
		width                 : 50px;
		height                : 50px;
		border                : 5px solid #CCC;
		border-radius         : 200px;
		-webkit-border-radius : 200px;
		-moz-border-radius    : 200px;
	}
	#swindow .candidate,
	#clientlist .candidate {
		border : 6px solid #349C5A;
	}
	#swindow .loozer,
	#clientlist .loozer {
		border : 6px solid #DDD;
	}
	#swindow .raiting-mini,
	#clientlist .raiting-mini {
		width              : 190px !important;
		display            : inline-block;
		padding            : 5px;
		border             : 0 dotted #ddd;
		box-sizing         : border-box;
		-moz-box-sizing    : border-box;
		-webkit-box-sizing : border-box;
	}

	.flex-container.main {
		align-content   : stretch !important;
		flex-wrap       : wrap !important;
		justify-content : center;
	}
	.flex-container.main > .flex-string {
		min-width          : 200px !important;
		flex-grow          : inherit !important;
		box-sizing         : border-box;
		-moz-box-sizing    : border-box;
		-webkit-box-sizing : border-box;
	}
	.flex-container.last {
		align-content   : stretch !important;
		flex-wrap       : wrap !important;
		justify-content : center;
		min-width       : 300px !important;
	}
	.flex-container.last > .flex-string {
		flex-grow          : inherit !important;
		padding            : 5px;
		box-sizing         : border-box;
		-moz-box-sizing    : border-box;
		-webkit-box-sizing : border-box;
	}

	.dwinner {
		border-top    : 5px dotted rgba(231, 75, 59, 0.3);
		margin-bottom : 5px;
	}
	.dcandidate {
		border-top    : 5px dotted rgba(21, 157, 130, 0.3);
		margin-bottom : 5px;
	}
	.dloozer {
		border-top    : 5px dotted #DDD;
		margin-bottom : 5px;
	}

	.progressbar {
		width                 : 100%;
		border                : #CCC 0 dotted;
		-moz-border-radius    : 1px;
		-webkit-border-radius : 1px;
		border-radius         : 1px;
		background            : rgba(250, 250, 250, 1);
		position              : relative;
	}
	.progressbar-completed {
		height       : 2.0em;
		line-height  : 2.0em;
		margin-left  : 0;
		padding-left : 0;
	}
	.progressbar-text {
		position : absolute;
		right    : 10px;
		top      : 5px;
	}
	.progressbar-head {
		position : absolute;
		left     : 10px;
		top      : 5px;
	}

	.progress-gray2 {
		background-image : -webkit-gradient(linear, 0.00% 50.00%, 100.00% 50.00%, color-stop(0%, rgba(207, 216, 220, 1)), color-stop(91.71%, rgba(207, 216, 220, 1.00)));
		background-image : -webkit-linear-gradient(0deg, rgba(207, 216, 220, 1.00) 0%, rgba(207, 216, 220, 1.00) 91.71%);
		background-image : linear-gradient(90deg, rgba(207, 216, 220, 1.00) 0%, rgba(207, 216, 220, 1.00) 91.71%);
	}
	.progress-green {
		background-image : -webkit-gradient(linear, 0.00% 50.00%, 100.00% 50.00%, color-stop(0%, rgba(0, 150, 136, 1)), color-stop(100%, rgba(0, 150, 136, 1.00)));
		background-image : -webkit-linear-gradient(0deg, rgba(0, 150, 136, 1) 0%, rgba(0, 150, 136, 1.00) 100%);
		background-image : linear-gradient(90deg, rgba(0, 150, 136, 1) 0%, rgba(0, 150, 136, 1.00) 100%);
	}
	.progress-green2 {
		background-image : -webkit-gradient(linear, 0.00% 50.00%, 100.00% 50.00%, color-stop(0%, rgba(26, 188, 156, 1)), color-stop(100%, rgba(26, 188, 156, 1.00)));
		background-image : -webkit-linear-gradient(0deg, rgba(26, 188, 156, 1) 0%, rgba(26, 188, 156, 1.00) 100%);
		background-image : linear-gradient(90deg, rgba(26, 188, 156, 1) 0%, rgba(26, 188, 156, 1.00) 100%);
	}
	.progress-blue {
		background-image : -webkit-gradient(linear, 0.00% 50.00%, 100.00% 50.00%, color-stop(0%, rgba(33, 150, 243, 1)), color-stop(100%, rgba(33, 150, 243, 1.00)));
		background-image : -webkit-linear-gradient(0deg, rgba(33, 150, 243, 1) 0%, rgba(33, 150, 243, 1.00) 100%);
		background-image : linear-gradient(90deg, rgba(33, 150, 243, 1) 0%, rgba(33, 150, 243, 1.00) 100%);
	}
	.progress-blue2 {
		background-image : -webkit-gradient(linear, 0.00% 50.00%, 100.00% 50.00%, color-stop(0%, rgba(100, 181, 246, 1)), color-stop(100%, rgba(100, 181, 246, 1.00)));
		background-image : -webkit-linear-gradient(0deg, rgba(100, 181, 246, 1) 0%, rgba(100, 181, 246, 1.00) 100%);
		background-image : linear-gradient(90deg, rgba(100, 181, 246, 1) 0%, rgba(100, 181, 246, 1.00) 100%);
	}

	.graybg22 {
		background : rgba(245, 245, 245, 1);
	}

	.ryear, .rmon {
		border     : 1px solid rgba(207, 216, 220, 1) !important;
		background : rgba(207, 216, 220, .3) !important;
	}
	.ryear.active,
	.rmon.active {
		border     : 1px solid rgba(231, 75, 59, 1.3) !important;
		background : rgba(231, 75, 59, 0.3) !important;
	}

	#tagbox .tags {
		color : #222 !important;
	}

	-->
</STYLE>

<div class="relativ mt20 mb20 wp95 text-center">

	<h1 class="uppercase fs-14 m0 mb10">Сделки в работе</h1>

	<div class="gray2">
		<span class="hidden">за период &nbsp;<?= format_date_rus( $da1 ) ?> &divide; <?= format_date_rus( $da2 ) ?></span>
		<span class="Bold">[ <a href="javascript:void(0)" onclick="Export()" title="Выгрузить в Excel" class="blue">Выгрузить в Excel</a> ]</span>
	</div>

	<div class="pull-right hand noprint" style="top:0;">

		<form id="customForm" name="customForm">

			<div class="pop nothide" id="params">

				<div class="gray2 mr20 fs-14"><i class="icon-list-nested gray2"></i></div>
				<div class="popmenu-top cursor-default" style="right:5px">

					<div class="popcontent w3001 box--child" style="right: 0;">

						<div class="flex-vertical border--bottom">

							<div class="flex-container p10 ha">
								<div class="flex-string">Оборот по сделкам</div>
								<div class="flex-string fs-14 noBold"><b><?= num_format( $kolSum ) ?></b> <?= $valuta ?>
								</div>
							</div>

							<div class="flex-container p10 ha">
								<div class="flex-string">Маржа по сделкам</div>
								<div class="flex-string fs-14 noBold">
									<b><?= num_format( $margaSum ) ?></b> <?= $valuta ?></div>
							</div>

							<div class="flex-container p10 ha">
								<div class="flex-string">Оплаты</div>
								<div class="flex-string fs-14 noBold green">
									<b><?= num_format( $summaCredit ) ?></b> <?= $valuta ?></div>
							</div>

							<div class="flex-container p10 ha">
								<div class="flex-string">Оплаты ( маржа )</div>
								<div class="flex-string fs-14 noBold green">
									<b><?= num_format( $summaCreditMarga ) ?></b> <?= $valuta ?></div>
							</div>

							<div class="flex-container p10 ha">
								<div class="flex-string">Оборот ( план )</div>
								<div class="flex-string fs-14 noBold blue">
									<b><?= num_format( $kolSum - $summaCredit ) ?></b> <?= $valuta ?></div>
							</div>

							<div class="flex-container p10 ha">
								<div class="flex-string">Маржа ( план )</div>
								<div class="flex-string fs-14 noBold blue">
									<b><?= num_format( $margaSum - $summaCreditMarga ) ?></b> <?= $valuta ?></div>
							</div>

						</div>

					</div>

				</div>

			</div>

		</form>

	</div>

</div>

<div class="flex-container infodiv dotted wp100">

	<div class="flex-string wp30">

		<div class="Bold uppercase fs-07 gray2 mb2">Этап</div>
		<div class="ydropDown selects wp97 fs-09">

			<span class="ydropCount"><?= count( $steps ) ?> выбрано</span><i class="icon-angle-down pull-aright"></i>
			<div class="yselectBox">
				<div class="right-text">
					<div class="ySelectAll w0 inline" title="Выделить всё"><i class="icon-plus-circled"></i>Всё</div>
					<div class="yunSelect w0 inline" title="Снять выделение"><i class="icon-minus-circled"></i>Ничего
					</div>
				</div>
				<?php
				foreach ( $steplist as $id => $item ) {

					print '
					<div class="ydropString ellipsis">
						<label>
							<input name="step[]" type="checkbox" id="step[]" value="'.$id.'" '.(in_array( $id, $steps ) ? "checked" : "").'>
							<span>'.$item.'</span>
						</label>
					</div>
					';

				}
				?>
				<div class="ydropString ellipsis">
					<label>
						<input name="close" type="checkbox" id="close" value="yes" <?= ($close == 'yes' ? "checked" : "") ?>>
						<span class="green Bold">Закрытые успешные</span>
					</label>
				</div>
			</div>

		</div>

	</div>
	<div class="flex-string wp70">

		<div class="Bold uppercase fs-07 gray2 mb2">По плановой дате</div>
		<div class="row" id="spperiod">

			<div class="inline w160">

				<INPUT name="dp1" type="text" id="dp1" value="<?= $dp1 ?>" class="inputdate dstart wp100 fs-10">

			</div>
			<div class="inline w20 text-center pt10 flh-20">&divide;</div>
			<div class="inline w160">

				<INPUT name="dp2" type="text" id="dp2" value="<?= $dp2 ?>" class="inputdate dend wp100 fs-10">

			</div>

			<div class="inline pl10">

				<span class="select bgwhite">
					<select name="dperiod" id="dperiod" class="w140 p5 pt7 clean bgwhite" data-goal="spperiod" data-action="period">
						<option>-за всё время-</option>
						<option value="today" <?= ($dperiod == 'today' ? 'selected' : '') ?> data-period="today">Сегодня</option>
						<option value="tomorrow" <?= ($dperiod == 'tomorrow' ? 'selected' : '') ?> data-period="tomorrow">Завтра</option>

						<option value="month" <?= ($dperiod == 'month' ? 'selected' : '') ?> data-period="month">Месяц текущий</option>
						<option value="monthnext" <?= ($dperiod == 'monthnext' ? 'selected' : '') ?> data-period="monthnext">Месяц следующий</option>

						<option value="quart" <?= ($dperiod == 'quart' ? 'selected' : '') ?> data-period="quart">Квартал текущий</option>
						<option value="quartnext" <?= ($dperiod == 'quartnext' ? 'selected' : '') ?> data-period="quartnext">Квартал следующий</option>

						<option value="year" <?= ($dperiod == 'year' ? 'selected' : '') ?> data-period="year">Год</option>
						<option value="yearnext" <?= ($dperiod == 'yearnext' ? 'selected' : '') ?> data-period="yearnext">Год следующий</option>
					</select>
				</span>

			</div>

		</div>

	</div>

</div>

<hr>

<TABLE id="zebra" class="top">
	<thead class="sticked--top">
	<TR>
		<th class="w20">#</th>
		<th class="w80">Дата создан.</th>
		<th class="w80">Дата план.</th>
		<th class="w60">Этап</th>
		<th class="min100">Сделка/ Заказчик</th>
		<th class="w120">Направление</th>
		<th class="w100">Тип</th>
		<th class="w100">Оборот, <?= $valuta ?></th>
		<th class="w100">Маржа, <?= $valuta ?></th>
		<th class="w100">Оплачено, <?= $valuta ?></th>
		<th class="w80">Ответств.</th>
	</TR>
	</thead>
	<?php
	foreach ( $dogs as $i => $dog ) {

		$icn = $dog['close'] == 'yes' ? 'icon-lock red' : 'icon-briefcase blue';
		?>
		<TR class="ha <?= $dog['color'] ?> th40">
			<TD><?= $i + 1 ?>.</TD>
			<TD><?= format_date_rus( $dog['datum'] ) ?></TD>
			<TD><?= format_date_rus( $dog['datum_plan'] ) ?></TD>
			<TD class="<?= $dog['scolor'] ?> Bold"><?= $dog['step'] ?>%</TD>
			<TD>
				<div class="ellipsis Bold fs-12">
					<A href="javascript:void(0)" onclick="openDogovor('<?= $dog['did'] ?>')" title="Открыть в новом окне"><i class="<?= $icn ?>"></i>&nbsp;<?= current_dogovor( $dog['did'] ) ?>
					</A>
				</div>
				<br>
				<div class="ellipsis mt5">
					<?php if ( $dog['clid'] > 0 ) { ?>
						<A href="javascript:void(0)" onclick="openClient('<?= $dog['clid'] ?>')" class="gray"><i class="icon-building broun"></i>&nbsp;<?= current_client( $dog['clid'] ) ?>
						</A>
					<?php } else { ?>
						<A href="javascript:void(0)" onclick="openPerson('<?= $dog['pid'] ?>')" class="gray"><i class="icon-user-1 broun"></i>&nbsp;<?= current_person( $dog['pid'] ) ?>
						</A>
					<?php } ?>
				</div>
			</TD>
			<TD title="<?= $dog['direction'] ?>">
				<div class="ellipsis"><?= $dog['direction'] ?></div>
			</TD>
			<TD title="<?= $dog['tip'] ?>">
				<div class="ellipsis"><?= $dog['tip'] ?></div>
			</TD>
			<TD class="text-right"><?= num_format( $dog['kol'] ) ?></TD>
			<TD class="text-right"><?= num_format( $dog['marga'] ) ?></TD>
			<TD class="text-right">
				<div class="Bold"><?= num_format( $dog['payment'] ) ?></div>
				<div class="fs-07 gray2"><?= $dog['dolya2'] ?>%</div>
			</TD>
			<TD>
				<div class="ellipsis"><?= $dog['manager'] ?></div>
			</TD>
		</TR>
	<?php } ?>
</TABLE>

<div class="space-100"></div>

<script src="/assets/js/jquery.liTextLength.js"></script>
<script>

	let $dperiod = '<?=$dperiod?>';

	period.yearnext = [moment().add(1, 'year').startOf('year').format('YYYY-MM-DD'), moment().add(1, 'year').endOf('year').format('YYYY-MM-DD')];

	$('.checkbox label').bind('click', function () {

		$('.popmenu-top').show();

	});

	$(".dot-ellipsis").liTextLength({
		length: 100,
		afterLength: '...',
		fullText: true
	});

	$('.inputdate').each(function () {

		if (!isMobile)
			$(this).datepicker({
				dateFormat: 'yy-mm-dd',
				numberOfMonths: 2,
				firstDay: 1,
				dayNamesMin: ['Вс', 'Пн', 'Вт', 'Ср', 'Чт', 'Пт', 'Сб'],
				monthNamesShort: ['Январь', 'Февраль', 'Март', 'Апрель', 'Май', 'Июнь', 'Июль', 'Август', 'Сентябрь', 'Октябрь', 'Ноябрь', 'Декабрь'],
				changeMonth: true,
				changeYear: true,
				yearRange: '1940:2030',
				minDate: new Date(1940, 1 - 1, 1),
				showButtonPanel: true,
				currentText: 'Сегодня',
				closeText: 'Готово'
			});

	});

	function Export() {

		var str = $('#selectreport').serialize();
		var custom = $('#customForm').serialize();

		window.open('/reports/<?=$thisfile?>?action=export&' + str + '&' + custom);

	}

</script>