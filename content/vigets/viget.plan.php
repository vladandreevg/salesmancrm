<?php
/* ============================ */
/*         SalesMan CRM         */
/* ============================ */
/* (C) 2016 Vladislav Andreev   */
/*       SalesMan Project       */
/*        www.isaler.ru         */
/*        ver. 2016.20          */
/* ============================ */

error_reporting( E_ERROR );
header( "Pragma: no-cache" );

$rootpath = dirname( __DIR__, 2 );

include $rootpath."/inc/config.php";
include $rootpath."/inc/dbconnector.php";
include $rootpath."/inc/auth.php";
include $rootpath."/inc/func.php";
include $rootpath."/inc/settings.php";
include $rootpath."/inc/language/".$language.".php";

$thisfile = basename( __FILE__ );

global $userRights;

$sort  = ($userRights['individualplan']) ? " and iduser = '".$iduser1."'" : get_people( $iduser1 );
$dsort = ( $otherSettings[ 'planByClosed']) ? " and close = 'yes'" : "";

$y  = date( 'Y' );
$m  = date( 'm' );
$nd = date( 'd' );

$dd = date( "t", mktime( date( 'H' ), date( 'i' ), date( 's' ), $m, 1, $y ) + $tzone * 3600 ); //кол-во дней в текущем месяце

$dm_start = $y."-".$m."-01";
$dm_end   = $y."-".$m."-".$dd;

$kol_plan   = 0;
$marga_plan = 0;

$color[0] = [
	"#E74C3C",
	"#F1C40F",
	"#1ABC9C"
];
//цвета первого круга - красный-желтый-зеленый

$color[1] = [
	"#C0392B",
	"#F1C40F",
	"#16A085"
];
//цвета второго круга - красный-желтый-зеленый

//Расчет плановых показателей для заданного пользователя
$res        = $db -> getRow( "SELECT SUM(kol_plan) as kol, SUM(marga) as marga FROM ".$sqlname."plan WHERE mon='$m' and year='$y' and iduser = '$iduser1' and identity = '$identity'" );
$kol_plan   = $res['kol'];
$marga_plan = $res['marga'];

//расчет плана на текущий день
$planOborot = round( ($kol_plan / $dd) * $nd );
$planMarga  = round( ($marga_plan / $dd) * $nd );

$kol = $marga = 0;

//Расчет выполнения плана по заданному пользователю
//если рассрочка не включена, то считаем закрытые сделки
if ( !$otherSettings[ 'credit'] ) {

	//$result = $db -> getRow( "SELECT SUM(kol_fact) as kol, SUM(marga) as marga FROM {$sqlname}dogovor WHERE datum_close between '$dm_start 00:00:01' and '$dm_end 23:59:59' and close='yes' $sort and identity = '$identity'" );
	$result = $db -> getRow( "SELECT SUM(kol_fact) as kol, SUM(marga) as marga FROM {$sqlname}dogovor WHERE DATE_FORMAT({$sqlname}dogovor.datum_close, '%Y-%c') = '$y-$m' and close='yes' $sort and identity = '$identity'" );
	//print $db -> lastQuery();
	$kol    = $result['kol'];
	$marga  = $result['marga'];

}

//если включена рассрочка, то считаем по оплатам
if ( $otherSettings[ 'credit'] ) {

	if ( !$otherSettings[ 'planByClosed'] ) {
		$result = $db -> getAll( "SELECT * FROM ${sqlname}credit WHERE do='on' and invoice_date between '$dm_start' and '$dm_end' and did IN (SELECT did FROM ${sqlname}dogovor WHERE did > 0 $sort) OR (".str_replace( "and", "", $sort )." and invoice_date between '$dm_start' and '$dm_end') and identity = '$identity' ORDER by did" );
	}

	if ( $otherSettings[ 'planByClosed'] ) {
		$result = $db -> getAll( "
			SELECT 
				{$sqlname}credit.crid,
				{$sqlname}credit.do,
				{$sqlname}credit.did,
				{$sqlname}credit.clid,
				{$sqlname}credit.pid,
				{$sqlname}credit.invoice,
				{$sqlname}credit.invoice_date,
				{$sqlname}credit.summa_credit,
				{$sqlname}credit.iduser,
				{$sqlname}dogovor.title as deal,
				{$sqlname}dogovor.kol,
				{$sqlname}dogovor.marga,
				{$sqlname}dogovor.iduser as diduser,
				{$sqlname}dogovor.close,
				{$sqlname}dogovor.datum_close,
				{$sqlname}clientcat.title as client
			FROM {$sqlname}credit
				LEFT JOIN {$sqlname}dogovor ON {$sqlname}credit.did = {$sqlname}dogovor.did
				LEFT JOIN {$sqlname}clientcat ON {$sqlname}credit.clid = {$sqlname}clientcat.clid
			WHERE
				{$sqlname}credit.do = 'on' and
				COALESCE({$sqlname}dogovor.close, 'no') = 'yes' and
				DATE_FORMAT({$sqlname}dogovor.datum_close, '%Y-%m') = '".date( 'Y' )."-".date( 'm' )."' and 
				{$sqlname}credit.iduser IN (".implode( ",", get_people( $iduser1, "yes" ) ).") and 
				{$sqlname}credit.identity = '$identity'
			ORDER by {$sqlname}credit.invoice_date" );
	}

	//print $db -> lastQuery();

	foreach ( $result as $data ) {

		//расчет процента размера платежа от суммы сделки
		$kolp = $db -> getOne( "SELECT kol FROM ${sqlname}dogovor WHERE did = '$data[did]' and identity = '$identity'" );

		$margap = $db -> getOne( "SELECT marga FROM ${sqlname}dogovor WHERE did = '$data[did]' and identity = '$identity' ORDER by did" );

		$dolya = ($kolp > 0) ? $data['summa_credit'] / $kolp : 0;//% оплаченной суммы от суммы по договору
		$m     = ($kolp > 0) ? $data['summa_credit'] / $kolp : 0;

		$kol   += pre_format( $data['summa_credit'] );
		$marga += $margap * $dolya;

	}

}

//$planOborot = $planOborot == 0 ? $kol : $planOborot;
//$planMarga  = $planMarga == 0 ? $marga : $planMarga;

if($kol_plan == 0){
	$kol_plan = $planOborot = $kol;
}
if($marga_plan == 0){
	$marga_plan = $planMarga = $marga;
}

//$kol = 0.7 * $planOborot;
//$marga = $planMarga * 0.92;

//формируем цветовую схему
if ( $kol > $planOborot * 0.9 ) {

	$color1 = $color[0][2];
	$color2 = $color[1][2];

	$class1 = "bggreen";

}
elseif ( $kol < $planOborot * 0.7 ) {

	$color1 = $color[0][0];
	$color2 = $color[1][0];

	$class1 = "bgred";

}
else {

	$color1 = $color[0][1];
	$color2 = $color[1][1];

	$class1 = "bgyellow";

}

if ( $marga > $planMarga * 0.9 ) {

	$color3 = $color[0][2];
	$color4 = $color[1][2];

	$class2 = "bggreen";

}
elseif ( $marga < $planMarga * 0.7 ) {

	$color3 = $color[0][0];
	$color4 = $color[1][0];

	$class2 = "bgred";

}
else {

	$color3 = $color[0][1];
	$color4 = $color[1][1];

	$class2 = "bgyellow";

}

//Расчет процента выполнения
if ( $kol_plan > 0 ) {
	$proc_kol = num_format( $kol / $kol_plan * 100 );
}
else {
	$proc_kol = 0;
}
if ( $marga_plan > 0 ) {
	$proc_marga = num_format( $marga / $marga_plan * 100 );
}
else {
	$proc_marga = 0;
}

$kol   = num_format( $kol );
$marga = num_format( $marga );

$kol_plan   = num_format( $kol_plan );
$marga_plan = num_format( $marga_plan );

$ww = $show_marga == 'yes' && $otherSettings['marga'] ? 49 : 100;
?>
<style>
	#outer {
		background    : #FFFFFF;
		border-radius : 5px;
		color         : #000;
	}

	#div1, #div2 {
		box-sizing : border-box;
	}

	/*первый оборот*/
	.arc, .arc2 {
		stroke-weight : 0.1;
	}

	.bgred .arc {
		fill : #E74C3C;
	}
	.bgred .arc2 {
		fill : #C0392B;
	}
	.bgyellow .arc {
		fill : #F1C40F;
	}
	.bgyellow .arc2 {
		fill : #F1C40F;
	}
	.bggreen .arc {
		fill : #1ABC9C;
	}
	.bggreen .arc2 {
		fill : #16A085;
	}

	.radial {
		border-radius : 3px;
		background    : #FFFFFF;
		color         : #000;

	}
	.background {
		fill         : #FFFFFF;
		fill-opacity : 0.01;
	}
	.component {
		fill : #e1e1e1;
	}
	.component .label {
		text-anchor : middle;
		fill        : #000000;
		display     : none;
	}

	.label {
		text-anchor : middle;
	}

	.radial-svg {
		display : block;
		margin  : 0 auto;
	}

	.afoot {
		display               : grid;
		grid-template-columns : 1fr 1fr;
	}
</style>

<div id="chartdiv" style="position: relative;">

	<div class="flex-container box--child nopad" style="flex-wrap: nowrap !important;">

		<div class="flex-string6 table nopad div-center wp50">

			<div class="mb5 text-center no--overflow">

				<div id="div1" class="<?= $class1 ?> text-center"></div>

			</div>

		</div>

		<?php if ( $show_marga == 'yes' && $otherSettings[ 'marga'] ) { ?>
			<div class="flex-string6 table nopad div-center wp50">

				<div class="mb5 text-center no--overflow">

					<div id="div2" class="<?= $class2 ?> text-center"></div>

				</div>

			</div>
		<?php } ?>

	</div>

</div>

<div class="afoot">

	<div class="oborot">

		<div class="mb10 div-center">

			<a href="javascript:void(0)" onclick="showPlan()" title="Текущее выполнение. Показать данные" class="list"><b class="bigtxt" style="color:<?= $color2 ?>"><?= num_format( $kol ) ?>&nbsp;<?= $valuta ?></b></a>

		</div>

		<div class="table div-center">

			<div class="tooltips p3 wp100 text-center" tooltip="<blue>Расчетный план продаж на текущий день</blue><hr>(План продаж в день * Кол-во прошедших дней месяца)" tooltip-position="top">

				<div class="inline wp25 border-box mwp100 nopad pl5 text-right gray2"><?= $fieldsNames['dogovor']['oborot'] ?>&nbsp;</div>
				<div class="inline wp70 border-box mwp100 nopad pl5 text-right">
					<b class="underline broun"><?= num_format( $planOborot ) ?></b>&nbsp;<?= $valuta ?> ( Р )
				</div>

			</div>

			<div title="План на месяц" class="p3 wp100 text-center">

				<div class="inline wp25 border-box mwp100 nopad pl5 text-right"></div>
				<div class="inline wp70 border-box mwp100 nopad pl5 text-right">
					<b><?= $kol_plan ?></b>&nbsp;<?= $valuta ?> ( П )
				</div>

			</div>

		</div>

	</div>
	<div class="marga">

		<?php if ( $show_marga == 'yes' && $otherSettings[ 'marga'] ) { ?>
			<div class="table mb10 div-center">

				<a href="javascript:void(0)" onclick="showPlan()" title="Текущее выполнение. Показать данные" class="list"><b class="bigtxt" style="color:<?= $color4 ?>"><?= num_format( $marga ) ?>&nbsp;<?= $valuta ?></b></a>

			</div>

			<div class="table div-center">

				<div class="tooltips p3 text-center" tooltip="<blue>Расчетный план продаж на текущий день</blue><hr>(План продаж в день * Кол-во прошедших дней месяца)" tooltip-position="top">

					<div class="inline wp25 border-box mwp100 nopad pl5 text-right gray2"><?= $fieldsNames['dogovor']['marg'] ?>&nbsp;</div>
					<div class="inline wp70 border-box mwp100 nopad pl5 text-right">
						<b class="underline broun"><?= num_format( $planMarga ) ?></b>&nbsp;<?= $valuta ?> ( Р )
					</div>

				</div>

				<div title="План на месяц" class="p3 text-center">

					<div class="inline wp25 border-box mwp100 nopad pl5 text-right"></div>
					<div class="inline wp70 border-box mwp100 nopad pl5 text-right">
						<b><?= $marga_plan ?></b>&nbsp;<?= $valuta ?> ( П )
					</div>

				</div>

			</div>
		<?php } ?>

	</div>

</div>

<script src="/assets/js/radialProgress.js"></script>
<script>

	var $elmnt = $('#analitic');

	/*tooltips*/
	$elmnt.closest('.viget').find('.tooltips').append("<span></span>");
	$elmnt.closest('.viget').find('.tooltips:not([tooltip-position])').attr('tooltip-position', 'bottom');
	$elmnt.closest('.viget').find('.tooltips').mouseenter(function () {
		$(this).find('span').empty().append($(this).attr('tooltip'));
	});
	/*tooltips*/

	//if(isMobile) $('#chartdiv').css({'height':'inherit'});

	var div1 = d3.select(document.getElementById('div1'));
	<?php if ($show_marga == 'yes' && $otherSettings[ 'marga']){?>
	var div2 = d3.select(document.getElementById('div2'));
	<?php } ?>

	start();

	function showPlan() {
		doLoad('/content/vigets/viget.dataview.php?action=planView');
	}

	function deselect() {
		div1.attr("class", "radial");
		<?php if ($show_marga == 'yes' && $otherSettings[ 'marga']){?>
		div2.attr("class", "radial");
		<?php } ?>
	}

	function start() {

		var diameter = $elmnt.find('#chartdiv').width() / 2 - 5;

		if (diameter >= 160) {

			diameter = 160;
			//$('.radial-svg').css({'width': diameter + 'px', 'height': diameter + 'px'});

		}
		else if (diameter < 160) {

			diameter = diameter - 5;
			//$('.radial-svg').css({'width': diameter + 'px', 'height': diameter + 'px'});

		}

		var rp1 = radialProgress(document.getElementById('div1'))
			.label("<?=$kol_plan?>")
			.onClick(showPlan)
			.diameter(diameter)
			.value(<?=$proc_kol?>)
			.render();

		<?php if ($show_marga == 'yes' && $otherSettings[ 'marga']){?>
		var rp2 = radialProgress(document.getElementById('div2'))
			.label("<?=$marga_plan?>")
			.onClick(showPlan)
			.diameter(diameter)
			.value(<?=$proc_marga?>)
			.render();
		<?php }?>
	}
</script>
