<?php
/* ============================ */
/*         SalesMan CRM         */
/* ============================ */
/* (C) 2016 Vladislav Andreev   */
/*       SalesMan Project       */
/*        www.isaler.ru         */
/*        ver. 2017.x           */
/* ============================ */
?>
<?php
error_reporting(E_ERROR);

header("Pragma: no-cache");

$rootpath = realpath( __DIR__.'/../../' );

include $rootpath."/inc/config.php";
include $rootpath."/inc/dbconnector.php";
include $rootpath."/inc/auth.php";
include $rootpath."/inc/func.php";
include $rootpath."/inc/settings.php";
include $rootpath."/inc/language/".$language.".php";

$thisfile = basename( __FILE__ );

$clid = (int)$_REQUEST['clid'];
$action = untag($_REQUEST['action']);

$q = "
	SELECT
		".$sqlname."speca.did as did,
		".$sqlname."speca.title as product,
		".$sqlname."speca.artikul,
		".$sqlname."speca.price,
		".$sqlname."speca.price_in,
		".$sqlname."speca.nds,
		".$sqlname."speca.dop,
		".$sqlname."speca.kol,
		".$sqlname."speca.edizm,
		".$sqlname."dogovor.title as dogovor,
		".$sqlname."dogovor.close as close,
		".$sqlname."dogovor.iduser as iduser,
		".$sqlname."dogovor.datum_plan as dplan,
		".$sqlname."dogovor.datum_close as dclose
	FROM ".$sqlname."speca
		LEFT JOIN ".$sqlname."dogovor ON ".$sqlname."dogovor.did = ".$sqlname."speca.did
	WHERE
		".$sqlname."dogovor.clid = '$clid' and
		".$sqlname."speca.identity = '$identity'
	ORDER BY ".$sqlname."dogovor.datum_close, ".$sqlname."dogovor.datum_plan";

$res = $db -> query($q);// or print mysql_error();
while ($da = $db -> fetch($res)){

	$ndsa = getNalog($da['price'], $da['nds'], $ndsRaschet);
	$ndsaIn = getNalog($da['price_in'], $da['nds'], $ndsRaschet);

	if($ndsRaschet != 'yes') {

		$summa = pre_format($da['kol']) * pre_format($da['price']) * pre_format($da['dop']);

		$num = $num + pre_format($da['kol']);
		$summaIn = pre_format($da['price_in']) * pre_format($da['kol']) * pre_format($da['dop']);

	}
	else{

		$summa = pre_format($da['kol']) * pre_format($da['price']) * pre_format($da['dop']);

		$num = $num + pre_format($da['kol']);
		$summaIn = pre_format($da['price_in']) * pre_format($da['kol']) * pre_format($da['dop']);

		$ndsTotal = $ndsTotal + $ndsa['nalog'] * pre_format($da['kol']) * pre_format($da['dop']);

	}

	if ($da['artikul']=='') $da['artikul'] = '???';
?>
	<div class="row ha">
		<div class="column grid-6">
			<?=$da['product']?>
			<div>
				<a href="javascript:void(0)" onclick="viewDogovor('<?=$da['did']?>')" title=""><b><?=$da['dogovor']?></b></a><br>
				<?=$da['dclose']?>
			</div>
		</div>
		<div class="column grid-2"><?=$da['kol']?> <?=$da['edizm']?></div>
		<div class="column grid-2"><?=num_format($da['price'])?></div>
	</div>
<?php
}
?>