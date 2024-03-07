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

$status = [
	'0' => 'Создана',
	'1' => 'В работе',
	'2' => 'Выполнена'
];
$colors = [
	'0' => 'broun',
	'1' => 'blue',
	'2' => 'green'
];

if ( in_array( $iduser1, $settings[ 'mcCoordinator' ] ) ) $sort = '';
else $sort = get_people( $iduser1 );

$list = [];

$result = $db -> getAll( "SELECT * FROM ".$sqlname."modcatalog_zayavka where (status < '2' or (status = '2' and datum > '".current_datum( 7 )."')) ".$sort." and identity = '$identity' ORDER BY FIELD(`status`, 0,1,2), datum DESC" );
foreach ( $result as $data ) {

	$zayavka = json_decode( $data[ 'des' ], true );

	if ( $zayavka[ 'zTitle' ] != '' ) {

		$tip   = '<span class="red">Новая позиция</span>';
		$title = $zayavka[ 'zTitle' ];

	}
	else {

		$tip   = '<span class="green">Из каталога</span>';
		$title = current_dogovor( $data[ 'did' ] );

	}

	$des = $data[ 'content' ];

	if ( $data[ 'isHight' ] == 'yes' and $data[ 'status' ] != 2 )
		$hi = '<i class="icon-attention red smalltxt" title="Срочно"></i> Срочно';

	else
		$hi = '<i class="icon-ok blue smalltxt" title="Нормально"></i> Нормально';

	if ( $data[ 'sotrudnik' ] ) $sotr = '<b class="broun">'.current_user( $data[ 'sotrudnik' ] ).'</b>';
	else $sotr = ' -- ';

	if ( $data[ 'status' ] == 2 )
		$datum = format_date_rus( get_smdate( $data[ 'datum_end' ] ) );

	elseif ( $data[ 'status' ] == 1 )
		$datum = format_date_rus( get_smdate( $data[ 'datum_start' ] ) );

	else
		$datum = format_date_rus( get_smdate( $data[ 'datum' ] ) );

	$list[ $data[ 'status' ] ][] = [
		"id"      => $data[ 'id' ],
		"datum"   => $datum,
		"number"  => $data[ 'number' ],
		"tip"     => $tip,
		"content" => $des,
		"hi"      => $hi,
		"worker"  => $sotr,
		"user"    => current_user( $data[ 'iduser' ] ),
		"did"     => $data[ 'did' ],
		"dogovor" => current_dogovor( $data[ 'did' ] ),
	];

}

//print_r($list);

?>
<style type="text/css">
	<!--
	.zmcViget {
		width                 : 100%;
		position              : relative;
		border                : 1px dotted #ccc;
		background            : #FFF;
		padding               : 5px;
		margin-bottom         : 5px;
		display               : inline-block;
		float                 : left;
		-moz-border-radius    : 3px;
		-webkit-border-radius : 3px;
		border-radius         : 3px;
		box-sizing            : border-box;
	}
	.zmcViget:hover {
		-moz-box-shadow    : 0 0 2px #999;
		-webkit-box-shadow : 0 0 2px #999;
		box-shadow         : 0 0 2px #999;
	}
	.zmcViget .zhead {
		padding       : 5px;
		margin        : -5px -5px 0 -5px;
		background    : #eee;
		border-bottom : 1px solid #ccc;
	}
	-->
</style>

<div class="relativ">

	<div class="canban three canban--header sticked--top" style="top: 50px">

		<div class="canban--item no-border">

			<div class="div--header broun"><i class="icon-clock"></i>&nbsp;Новая</div>
			<div class="p10 fs-09 gray2 center-text">Все заявки не в работе</div>

		</div>
		<div class="canban--item no-border">

			<div class="div--header blue"><i class="icon-tools"></i>&nbsp;В работе</div>
			<div class="p10 fs-09 gray2 center-text">Все заявки в работе</div>

		</div>
		<div class="canban--item no-border">

			<div class="div--header green"><i class="icon-ok"></i>&nbsp;Выполнена</div>
			<div class="p10 fs-09 gray2 center-text">Выполненные за последние 7 дней заявки</div>

		</div>

	</div>

	<div class="canban three canban--body box--child">

		<?php
		foreach ( $list as $status => $item ) {
			?>
			<div class="canban--column pl5 pr5">

				<?php
				if ( $status == 0 ) $pre = "Создана: ";
				elseif ( $status == 1 ) $pre = "В работе с: ";
				else $pre = "Выполнена: ";

				foreach ( $item as $k => $val ) {
					?>
					<div class="canban--item zmcViget">

						<div class="zhead">
							<span class="fs-12 Bold">№ <?= $val[ 'number' ] ?></span><sup><?= $val[ 'tip' ] ?></sup>
						</div>

						<div class="p5">

							<div class="pull-right fs-09"><?= $val[ 'hi' ] ?>&nbsp;</div>
							<div class="pb5">Автор: <b><?= $val[ 'user' ] ?></b></div>
							<div class="pb5">Исполнитель: <b><?= $val[ 'worker' ] ?></b></div>
							<?php
							if ( $val[ 'did' ] > 0 )
								print '<hr><span class="ellipsis"><a href="javascript:void(0)" onclick="openDogovor(\''.$val[ 'did' ].'\',\'7\');"><i class="icon-briefcase-1 blue"></i> '.$val[ 'dogovor' ].'</a></span>';

							else
								print '<span class="gray"><i class="icon-briefcase-1"></i> Без сделки</span><br>';
							?>

						</div>

						<div class="pull-aright mt5">

							<span class="em fs-09 gray2"><?= $pre ?><?= $val[ 'datum' ] ?></span>&nbsp;
							<a href="javascript:void(0)" onClick="doLoad('modules/modcatalog/form.modcatalog.php?id=<?= $val[ 'id' ] ?>&action=viewzayavka');" title="Просмотр" class="fbutton fs-07"><i class="icon-info"></i></a>

						</div>

					</div>
					<?php
				}
				?>

			</div>
		<?php } ?>

	</div>

</div>

<div class="space-40"></div>

<script>

	$(".nano").nanoScroller();

</script>