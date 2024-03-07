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
$action = $_REQUEST['action'];
$showall = $_REQUEST['showall'];
$product = $_REQUEST['product'];

if($product != ''){

	$q = "
		SELECT
			".$sqlname."speca.did as did,
			".$sqlname."dogovor.title as dogovor,
			".$sqlname."dogovor.close as close,
			".$sqlname."dogovor.iduser as iduser,
			".$sqlname."dogovor.datum_plan as dplan,
			".$sqlname."dogovor.datum_close as dclose,
			".$sqlname."user.title as user
		FROM ".$sqlname."speca
			LEFT JOIN ".$sqlname."dogovor ON ".$sqlname."dogovor.did = ".$sqlname."speca.did
			LEFT JOIN ".$sqlname."user ON ".$sqlname."dogovor.iduser = ".$sqlname."user.iduser
		WHERE
			".$sqlname."dogovor.clid = '$clid' and
			".$sqlname."speca.title = '$product' and
			".$sqlname."speca.identity = '$identity'
		ORDER BY ".$sqlname."dogovor.datum_close, ".$sqlname."dogovor.datum_plan";

	$res = $db -> query($q);
	while ($da = $db -> fetch($res)){

		$list[] =array("did" => $da['did'], "dogovor" => $da['dogovor'], "close" => $da['close'], "dplan" => format_date_rus($da['dplan']), "dclose" => format_date_rus($da['dclose']), "user" => $da['user']);

	}

	print json_encode_cyr($list);

	exit();
}

if($showall == 'yes') $sort = '';
else $sort = $sqlname."dogovor.kol_fact > 0 and";

$q = "
	SELECT
		".$sqlname."speca.title as product,
		".$sqlname."speca.artikul,
		SUM(".$sqlname."speca.price) as price,
		SUM(".$sqlname."speca.kol) as kol,
		COUNT(".$sqlname."speca.did) as count,
		".$sqlname."speca.edizm
	FROM ".$sqlname."speca
		LEFT JOIN ".$sqlname."dogovor ON ".$sqlname."dogovor.did = ".$sqlname."speca.did
	WHERE
		".$sqlname."dogovor.clid = '$clid' and
		$sort
		".$sqlname."speca.identity = '$identity'
	GROUP BY ".$sqlname."speca.title
	ORDER BY ".$sqlname."speca.spid";

$res = $db -> query($q);
while ($da = $db -> fetch($res)){

	if ($da['artikul'] == '') $da['artikul'] = '???';
?>
	<div class="row halight hand pad3 bgwhite border-box">
		<div class="column grid-1 w30">
			<i class="icon-angle-down angle gray"></i>
		</div>
		<div class="products column grid-5" data-title="<?=$da['product']?>">
			<b class="blue"><?=$da['product']?></b><br>
			<div class="gray fs-09 em">Артикул: <?=$da['artikul']?></div>
		</div>
		<div class="column grid-1" title="Число сделок с продуктом"><i class="icon-briefcase-1 gray"></i><?=$da['count']?></div>
		<div class="column grid-1"><?=$da['kol']?> <?=$da['edizm']?></div>
		<div class="column grid-2"><?=num_format($da['price'])?></div>

		<div class="dealwproduct hidden full"></div>
	</div>
	<hr class="marg0">
<?php
}
if($db -> affectedRows($res) == 0) print '<div class="row pad5 gray bgwhite border-box">Ничего нет</div>';
?>
<script>
	$('.products').bind('click', function(){

		var $divProduct = $(this).closest('div.row').find('.dealwproduct');

		if($divProduct.hasClass('hidden')) {

			$divProduct.toggleClass('hidden').append('<img width="12" src="/assets/images/loading.gif">');

			var product = $(this).data('title');
			var str = "clid=<?=$clid?>&product=" + urlEncodeData(product);
			var deals = '';

			$.get("content/card/card.products.php", str, function(data) {

				for(var i in data) {

					deals = deals +
						'<div class="ha full greenbg-sub" onclick="viewDogovor(\''+data[i].did+'\');">' +
						'   <div class="column grid-1 w30">&nbsp;</div>' +
						'   <div class="column grid-6"><i class="icon-briefcase-1 broun"></i>'+ data[i].dogovor +'</div>' +
						'   <div class="column grid-3 ellipsis">'+ data[i].user +'</div>' +
						'</div>';

				}

				$divProduct.empty().append(deals);

			},'json');

		}
		else {

			$divProduct.toggleClass('hidden').empty();

		}
	});
</script>
