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

$rootpath = realpath( __DIR__.'/../../' );

include $rootpath."/inc/config.php";
include $rootpath."/inc/dbconnector.php";
include $rootpath."/inc/auth.php";
include $rootpath."/inc/func.php";
include $rootpath."/inc/settings.php";
include $rootpath."/inc/language/".$language.".php";

$thisfile = basename( __FILE__ );

$settings              = $db -> getOne( "SELECT settings FROM ".$sqlname."modcatalog_set WHERE identity = '$identity'" );
$settings              = json_decode( $settings, true );
$settings[ 'mcSklad' ] = 'yes';
?>
<STYLE type="text/css">
	<!--
	.zmsViget {
		width                 : calc(33.3% - 5px);
		position              : relative;
		border                : 1px dotted #ccc;
		background            : #FFF;
		padding               : 5px;
		margin-bottom         : 5px;
		margin-left           : 3px;
		margin-right          : 2px;
		display               : inline-block;
		float                 : left;
		-moz-border-radius    : 3px;
		-webkit-border-radius : 3px;
		border-radius         : 3px;
		box-sizing            : border-box;
		height                : 155px;
	}
	.zmsViget:hover {
		-moz-box-shadow    : 0 0 2px #999;
		-webkit-box-shadow : 0 0 2px #999;
		box-shadow         : 0 0 2px #999;
	}
	.zmsViget .zhead {
		width         : 100%;
		padding       : 10px 5px;
		margin        : -5px -5px 0 -5px;
		background    : #eee;
		border-bottom : 1px solid #ccc;
	}
	.mcPic {
		width       : 20%;
		height      : 50px;
		float       : right;
		margin-left : 10px;
		border      : 2px solid #ddd;
		padding     : 2px;
		cursor      : zoom-in;
	}
	.mcPrice {
		position   : absolute;
		bottom     : 5px;
		margin-top : 5px !important;
	}
	.mcDescr {
		max-height      : 85px;
		overflow-y      : hidden;
		-moz-hyphens    : auto;
		-webkit-hyphens : auto;
		-ms-hyphens     : auto;
	}
	.mcMore {
		position : absolute;
		bottom   : 5px;
		left     : 10px;
	}
	.mcSklad {
		margin-top : 10px;
	}
	.mcSklad div {
		line-height : 1.05em;
	}
	.mcBottom {
		position : absolute;
		bottom   : 5px;
		width    : 100%;
	}
	.mcBig a {
		display : block;
		color   : rgba(0, 150, 136, 1.0);
	}
	.mcInfo {
		display      : block;
		padding-left : 10px;
	}
	.mcInfo span {
		float       : right;
		font-weight : 700;
		color       : #507192;
	}
	.mcInfo span.mcToday {
		display      : inline-block;
		width        : 90px;
		color        : rgba(0, 150, 136, 1.0);
		font-weight  : 400;
		float        : right;
		padding-left : 20px;
	}
	.mcBgreen {
		border : 1px double rgba(0, 150, 136, 1.0);
	}
	.mcBgreen .column {
		height  : 1.2em !important;
		padding : 2px;
	}
	-->
</STYLE>
<?php
$kol_pred = $db -> getOne( "SELECT COUNT(*) as kol FROM ".$sqlname."modcatalog_offer where status = '0' and identity = '$identity'" ) + 0;

$kol_pred_today = $db -> getOne( "SELECT COUNT(*) as kol FROM ".$sqlname."modcatalog_offer where status = '0' and DATE_FORMAT(datum, '%Y-%m-%d') = '".current_datum()."' and identity = '$identity'" );

$kol_sklad = $db -> getOne( "SELECT SUM(kol) as kol FROM ".$sqlname."modcatalog where kol > 0 and status = '3' and identity = '$identity'" );

$kol_sklad_today = $db -> getOne( "SELECT SUM(kol) as kol FROM ".$sqlname."modcatalog_aktpoz where ida IN (SELECT id FROM ".$sqlname."modcatalog_akt where tip = 'income' and DATE_FORMAT(datum, '%Y-%m-%d') = '".current_datum()."' and isdo = 'yes' and identity = '$identity') and identity = '$identity'" ) + 0;

$kol_zay = $db -> getOne( "SELECT COUNT(*) as count FROM ".$sqlname."modcatalog_zayavka where id > 0 and status != '2' and identity = '$identity'" );

$kol_zay_today = $db -> getOne( "SELECT COUNT(*) as count FROM ".$sqlname."modcatalog_zayavka where DATE_FORMAT(datum, '%Y-%m-%d') = '".current_datum()."' and identity = '$identity'" ) + 0;

$kol_sklad_today = "+ ".$kol_sklad_today." сегодня";
$kol_pred_today  = "+ ".$kol_pred_today." сегодня";
$kol_zay_today   = "+ ".$kol_zay_today." сегодня";
?>
<div class="zmsViget mcBgreen">

	<div title="Отдел закупа" class="zhead">
		<a href="/sklad.php#zayavka"><b>Отдел закупа</b></a>
	</div>
	<div class="fs-10 row pt10">

		<div class="column grid-5 right-text">Предложений:</div>
		<div class="column grid-5 pl10">
			<b><?= $kol_pred ?></b><sup class="fs-07 blue"><?= $kol_pred_today ?></sup>
		</div>

		<div class="column grid-5 right-text">Требуется:</div>
		<div class="column grid-5 pl10">
			<b><?= $kol_zay ?></b><sup class="fs-07 blue"><?= $kol_zay_today ?></sup>
		</div>

		<div class="column grid-5 right-text">В наличии:</div>
		<div class="column grid-5 pl10">
			<b><?= $kol_sklad ?></b><sup class="fs-07 blue"><?= $kol_sklad_today ?></sup>
		</div>
	</div>
	<div class="mcBottom div-center">
		<?php if ( in_array( $iduser1, $settings[ 'mcCoordinator' ] ) ) { ?>
			<a href="javascript:void(0)" onClick="doLoad('modules/modcatalog/summary.php');" class="fbutton green" title="Сводный отчет"><i class="icon-chart-line"></i></a>
		<?php } ?>
		<?php if ( in_array( $iduser1, $settings[ 'mcCoordinator' ] ) ) { ?>
			<a href="javascript:void(0)" onClick="doLoad('modules/modcatalog/form.modcatalog.php?action=editoffer');" class="fbutton red" title="Добавить предложение"><i class="icon-plus-circled"></i> Добавить предложение</a>
		<?php } ?>
		<?php if ( in_array( $iduser1, $settings[ 'mcSpecialist' ] ) ) { ?>
			<a href="javascript:void(0)" onClick="doLoad('modules/modcatalog/form.modcatalog.php?action=editzayavka&tip=cold');" class="fbutton" title="Добавить заявку на поиск">Заявка на поиск</a>
		<?php } ?>
	</div>

</div>
<?php
$status = [
	'0' => 'Нет в наличии',
	'1' => 'Заказан',
	'2' => 'Приобретен',
	'3' => 'В наличии',
	'4' => 'Нет свободных'
];
$colors = [
	'0' => 'gray',
	'1' => 'broun',
	'2' => 'blue',
	'3' => 'green',
	'4' => 'red'
];

$q = "
	SELECT 
		".$sqlname."modcatalog.id,
		".$sqlname."modcatalog.prid,
		SUM(".$sqlname."modcatalog_skladpoz.kol) as kol,
		".$sqlname."modcatalog.files,
		".$sqlname."price.title as title,
		".$sqlname."price_cat.title as category,
		".$sqlname."price.price_1 as price_1,
		".$sqlname."price.edizm as edizm,
		substring(".$sqlname."price.descr, 1, 100) as descr
	FROM ".$sqlname."modcatalog
		LEFT JOIN ".$sqlname."price ON ".$sqlname."modcatalog.prid = ".$sqlname."price.n_id
		LEFT JOIN ".$sqlname."price_cat ON ".$sqlname."price.pr_cat = ".$sqlname."price_cat.idcategory
		LEFT JOIN ".$sqlname."modcatalog_skladpoz ON ".$sqlname."modcatalog_skladpoz.prid = ".$sqlname."modcatalog.prid
	WHERE 
		".$sqlname."modcatalog.id > 0 and
		".$sqlname."modcatalog.identity = '$identity'
	GROUP BY ".$sqlname."modcatalog_skladpoz.prid HAVING kol > 0
	";

$result = $db -> getAll( $q );

foreach ( $result as $data ) {

	$fl = '';

	$kol_res = $db -> getOne( "select SUM(kol) from ".$sqlname."modcatalog_reserv where prid='".$data[ 'prid' ]."' and identity = '$identity'" ) + 0;

	$files = json_decode( $data[ 'files' ], true );

	if ( $files[ 0 ][ 'file' ] != '' )
		$fl = '<div class="mcPic" style="background: url(\'content/helpers/get.file.php?file=modcatalog/'.$files[ 0 ][ 'file' ].'\') top no-repeat; background-size:cover;" onclick="window.open(\'content/helpers/get.file.php?file=modcatalog/'.$files[ 0 ][ 'file' ].'\')" title="Просмотр" class="list"></div>';

	?>
	<div class="zmsViget kat">

		<div title="<?= $data[ 'title' ] ?>" class="zhead ellipsis">
			<a href="javascript:void(0)" onClick="doLoad('modules/modcatalog/form.modcatalog.php?action=view&n_id=<?= $data[ 'prid' ] ?>');"><b class="blue"><?= $data[ 'title' ] ?></b></a><br>
		</div>
		<div class="smalltxt gray"><?= $data[ 'category' ] ?></div>
		<div class="fs-10">
			<?= $fl ?>
			<div class="mcDescr"><?= $data[ 'descr' ] ?></div>
			<div class="mcSklad">
				<div><b>В наличии:</b> <b class="blue"><?= $data[ 'kol' ] + 0 ?></b>&nbsp;( в т.ч. в резерве:
					<u class="green"><b><?= $kol_res ?></b></u> )
				</div>
			</div>
			<div class="mcMore hidden">
				<a href="javascript:void(0)" onClick="doLoad('modules/modcatalog/form.modcatalog.php?action=view&n_id=<?= $data[ 'prid' ] ?>');" class="fbutton"><i class="icon-info"></i></a>
			</div>
			<div class="mcPrice">
				Цена:
				<b class="blue"><?= str_replace( ",00", "", num_format( $data[ 'price_1' ] ) ) ?></b> <?= $valuta ?>
			</div>
		</div>

	</div>
<?php } ?>

<div style="height:65px;"></div>

<script>

	$(".nano").nanoScroller();

</script>