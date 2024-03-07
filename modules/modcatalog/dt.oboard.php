<?php
/* ============================ */
/*         SalesMan CRM         */
/* ============================ */
/* (C) 2016 Vladislav Andreev   */
/*       SalesMan Project       */
/*        www.isaler.ru         */
/*        ver. 2019.2           */
/* ============================ */

error_reporting( 0 );
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
	.mcViget0 {
		position              : relative;
		height                : 230px;
		border                : 1px dotted #ccc;
		background            : #FFF;
		margin-top            : 5px;
		margin-right          : 5px;
		padding               : 5px;
		display               : inline-table;
		float                 : left;
		-moz-border-radius    : 1px;
		-webkit-border-radius : 1px;
		border-radius         : 1px;
		width                 : calc(33.3% - 5px);
		box-sizing            : border-box;
		-moz-box-sizing       : border-box;
		-webkit-box-sizing    : border-box;
	}
	.mcViget0:hover {
		-moz-box-shadow    : 0 0 5px #999;
		-webkit-box-shadow : 0 0 5px #999;
		box-shadow         : 0 0 5px #999;
	}
	.mcPic {
		width       : 30%;
		height      : 100px;
		float       : right;
		margin-left : 10px;
		border      : 2px solid #ddd;
		padding     : 2px;
		cursor      : zoom-in;
	}
	.mcPrice2 {
		font-size   : 1.1em;
		line-height : 1.0em;
		position    : absolute;
		bottom      : 10px;
		right       : 10px;
		text-align  : right;
	}
	.mcTxt {
		/*font-size:1.0em;*/
		line-height   : 1.2em;
		margin-bottom : 10px;
		/*border:1px dotted red;*/
	}
	.mcDescr {
		font-size       : 0.90em;
		display         : blok;
		max-height      : 65px;
		overflow-y      : hidden;
		/*margin-top:10px;*/
		-moz-hyphens    : auto;
		-webkit-hyphens : auto;
		-ms-hyphens     : auto;
	}
	.mcReservList3 {
		padding-top : 10px;
		max-height  : 75px;
		overflow    : hidden;
	}
	.mcMore2 {
		position : absolute;
		bottom   : 5px;
		left     : 10px;
	}
	.mcSklad2 {
		font-size   : 1.0em;
		line-height : 1.2em;
		margin-top  : 10px;
	}
	.mcSklad2 .mcReservList {
		font-size  : 0.9em;
		margin-top : 10px;
	}
	.mcSklad2 div {
		line-height : 1.15em;
	}
	.mcBottom {
		position : absolute;
		bottom   : 5px;
		width    : 100%;
	}
	.mcBig a {
		font-size   : 1.5em;
		line-height : 1.1em;
		display     : block;
		color       : #3F9843;
	}
	.mcInfo {
		display      : block;
		line-height  : 1.3em;
		font-size    : 1.3em;
		margin-top   : 30px;
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
		color        : #3F9843;
		font-weight  : 400;
		font-size    : 0.70em;
		float        : right;
		padding-left : 20px;
	}
	.mcBgreen {
		border-color : #3F9843;
	}
	-->
</STYLE>
<?php
$status = [
	'0' => 'Актуально',
	'1' => 'Закрыто'
];
$colors = [
	'0' => 'green',
	'1' => 'gray'
];

$result = $db -> getAll( "SELECT * FROM ".$sqlname."modcatalog_offer where status != '1' and identity = '$identity' ORDER BY datum DESC, FIELD(`status`, 0,1,2)" );
foreach ( $result as $data ) {

	$des = '';
	$bg  = '';

	$zayavka = json_decode( $data[ 'des' ], true );

	$des = $data[ 'content' ];

	if ( abs( diffDate2( $data[ 'datum' ] ) ) < 1 )
		$bg = '#FFFFE1';

	if ( $zayavka[ 'zFile' ][ 'file' ] != '' )
		$fl = 'style="background: url(\'content/helpers/get.file.php?file=modcatalog/'.$zayavka[ 'zFile' ][ 'file' ].'\') top no-repeat; background-size:cover;" onclick="window.open(\'content/helpers/get.file.php?file=modcatalog/'.$zayavka[ 'zFile' ][ 'file' ].'\')" title="Просмотр" class="list"';
	else
		$fl = 'style="background: url(\'./modules/modcatalog/images/noimage.png\') top no-repeat; background-size:cover;"';

	$users = json_decode( $data[ 'users' ], true );
	$likes = count( $users );

	if ( $likes > 0 ) $likes = "+".$likes;

	?>
	<div class="mcViget0 zay2 flex-string" style="background:<?= $bg ?>">

		<div title="<?= $zayavka[ 'zTitle' ] ?>" class="mcTitle2">

			<div onClick="doLoad('modules/modcatalog/form.modcatalog.php?action=viewzayavka&id=<?= $data[ 'id' ] ?>');" class="Bold blue hand">
				<?= $zayavka[ 'zTitle' ] ?>
			</div>
			<div class="fs-07 gray2"><?= get_sfdate( $data[ 'datum' ] ) ?></div>

		</div>
		<div class="mcSklad2">

			<?php if ( $settings[ 'mcOfferName1' ] != '' ) { ?>
				<div>
					<?= $settings[ 'mcOfferName1' ] ?>:
					<b><?= intval( $zayavka[ 'zGod' ] ) ?></b>, <?= $settings[ 'mcOfferName2' ] ?>:
					<b><?= intval( $zayavka[ 'zProbeg' ] ) ?></b><br>
					Добавил: <b><?= current_user( $data[ 'iduser' ] ) ?></b><br>
				</div>
			<?php } ?>
			<div>
				<b>Цена:</b>
				<b class="red"><?= str_replace( ",00", "", num_format( $zayavka[ 'zPrice' ] ) ) ?></b> <?= $valuta ?>
			</div>
			<div class="mcPic" <?= $fl ?>></div>
			<div class="mcReservList3"><?= $data[ 'content' ] ?></div>

		</div>
		<div class="mcMore2">

			<a href="javascript:void(0)" onClick="doLoad('modules/modcatalog/form.modcatalog.php?action=viewoffer&id=<?= $data[ 'id' ] ?>');" class="fbutton">Информация</a>
			<?php if ( $data[ 'status' ] != '2' and ( $data[ 'iduser' ] == $iduser1 or in_array( $iduser1, $settings[ 'mcCoordinator' ] ) ) ) { ?>
				<A href="javascript:void(0)" onClick="doLoad('modules/modcatalog/form.modcatalog.php?id=<?= $data[ 'id' ] ?>&action=editoffer');" class="fbutton red" title="Изменить"><i class="icon-pencil fs-09"></i></A>
			<?php } ?>
			<?php if ( in_array( $iduser1, $settings[ 'mcCoordinator' ] ) ) { ?>
				<A href="javascript:void(0)" onClick="doLoad('modules/modcatalog/form.modcatalog.php?ido=<?= $data[ 'id' ] ?>&action=edit');" class="fbutton green" title="В каталог">В каталог</A>
			<?php } ?>
			<?php if ( !in_array( $iduser1, $users ) ) { ?>
				<span id="button" style="font-size:0.95em;"><A href="javascript:void(0)" onClick="likeoffer('<?= $data[ 'id' ] ?>');" class="fbutton" title="Голосовать"><i class="icon-thumbs-up"></i></A></span>
				<span style="font-size:0.95em;" class="green" title="Голосов"><i class="icon-thumbs-up"></i>&nbsp;<b>Интерес:</b> <?= $likes ?></span>
			<?php } ?>
			<?php if ( in_array( $iduser1, $users ) ) { ?>
				<span style="font-size:0.95em;" class="green" title="Вы проголосовали"><i class="icon-thumbs-up"></i>&nbsp;<b>Интерес:</b> <?= $likes ?></span>
			<?php } ?>

		</div>

	</div>
<?php } ?>

<div style="height:65px;"></div>

<script type="text/javascript">

	oboardResize();

	$(".nano").nanoScroller();

	function oboardResize() {

		var w = $('#last').innerWidth();
		var elem = $('.zay2');
		var v = 0;
		var hi = 0;
		var wi = 0;

		if (w >= 1000) {

			v = w / 3 - 15;
			hi = v * 0.2;
			wi = hi * 1.4;

			//elem.width(v + 'px');
			elem.find('.mcPic').height(hi + 'px').width(wi + 'px');

		}
		else if (w < 1200 && w >= 700) {
			v = w / 2 - 12;
			hi = v * 0.2;
			wi = hi * 1.4;

			//elem.width(v+'px');
			elem.find('.mcPic').height(hi + 'px').width(wi + 'px');
		}
		else {

			//elem.width('99%');

		}
	}

	function likeoffer(id) {

		url = 'modules/modcatalog/core.modcatalog.php?action=likeoffer&id=' + id;
		$.post(url, function (data) {

			$('.catalog--oboard').load('modules/modcatalog/dt.oboard.php').append('<img src="/assets/images/loading.gif">');

			$('#message').fadeTo(1, 1).css('display', 'block').html(data);
			setTimeout(function () {
				$('#message').fadeTo(1000, 0);
			}, 20000);

			return true;
		});

	}
</script>