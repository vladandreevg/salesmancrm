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

$rootpath = realpath( __DIR__.'/../' );

include $rootpath."/inc/config.php";
include $rootpath."/inc/dbconnector.php";
include $rootpath."/inc/auth.php";
include $rootpath."/inc/func.php";
include $rootpath."/inc/settings.php";
include $rootpath."/inc/language/".$language.".php";

$action = $_REQUEST[ 'action' ];
$da1    = $_REQUEST[ 'da1' ];
$da2    = $_REQUEST[ 'da2' ];

$user_list = (array)$_REQUEST[ 'user_list' ];
$period    = $_REQUEST[ 'period' ];

$tip    = (array)$_REQUEST[ 'tip' ];
$sklad  = (array)$_REQUEST[ 'sklad' ];
$status = (array)$_REQUEST[ 'status' ];

$period = ( $period == '' ) ? getPeriod( 'month' ) : getPeriod( $period );
$da1    = ( $da1 != '' ) ? $da1 : $period[ 0 ];
$da2    = ( $da2 != '' ) ? $da2 : $period[ 1 ];

if ( empty( $user_list ) ) {
	$user_list = (array)get_people( $iduser1, "yes" );
}

$sort = '';

$itogo = $list = $users = [];

$statuses = [
	'yes' => 'Проведен',
	'no'  => 'Не проведен'
];

//фильтр выбранных пользователей
//if (count($user_list) > 0) $sort .= " and ".$sqlname."dogovor.iduser in (".implode(",", $user_list).")";

//фильтр по типу ордера
if ( !empty( $tip ) ) {
	$sort .= " and ".$sqlname."modcatalog_akt.tip IN (".yimplode( ",", $tip, "'" ).")";
}

//фильтр по складу
if ( !empty( $sklad ) ) {
	$sort .= " and ".$sqlname."modcatalog_akt.sklad IN (".yimplode( ",", $sklad ).")";
}

//фильтр по статусу
if ( !empty( $status ) ) {
	$sort .= " and ".$sqlname."modcatalog_akt.isdo IN ('".yimplode( ",", $status, "'" )."')";
}

//Этапы, соответствующие 60,80,90,100
//$steps = $db -> getCol("SELECT idcategory FROM ".$sqlname."dogcategory WHERE CAST(title AS UNSIGNED) >= '60' and identity = '$identity' ORDER BY title");

$q = "
	SELECT
		".$sqlname."modcatalog_aktpoz.id as id,
		".$sqlname."modcatalog_aktpoz.prid as prid,
		".$sqlname."modcatalog_aktpoz.kol as kol,
		".$sqlname."modcatalog_aktpoz.ida as ida,
		".$sqlname."modcatalog_akt.tip as tip,
		".$sqlname."modcatalog_akt.sklad as sklad,
		".$sqlname."modcatalog_akt.number as number,
		".$sqlname."modcatalog_akt.clid as clid,
		".$sqlname."modcatalog_akt.did as did,
		".$sqlname."modcatalog_akt.isdo as do,
		DATE_FORMAT(".$sqlname."modcatalog_akt.datum, '%Y-%m-%d') as datum
	FROM ".$sqlname."modcatalog_aktpoz
		LEFT JOIN ".$sqlname."modcatalog_akt ON ".$sqlname."modcatalog_akt.id = ".$sqlname."modcatalog_aktpoz.ida
	WHERE
		".$sqlname."modcatalog_akt.datum BETWEEN '$da1 00:00:00' and '$da2 23:59:59' and
		".$sqlname."modcatalog_akt.isdo = 'yes' and 
		".$sqlname."modcatalog_akt.identity = '$identity'
		$sort
	ORDER by ".$sqlname."modcatalog_akt.datum
";

$da = $db -> getAll( $q );
foreach ( $da as $data ) {

	$data[ 'tip' ] = ( $data[ 'tip' ] == "income" ) ? '<span class="green">Приходный</span>' : '<span class="red">Расходный</span>';

	$data[ 'title' ] = $db -> getOne( "SELECT title FROM ".$sqlname."price WHERE n_id = '$data[prid]' AND identity = '$identity'" );

	$data[ 'sklad' ] = $db -> getOne( "select title from ".$sqlname."modcatalog_sklad where id = '$data[sklad]' and identity = '$identity'" );

	$list[ $data[ 'datum' ] ][] = [
		"id"      => $data[ 'id' ],
		"prid"    => $data[ 'prid' ],
		"title"   => $data[ 'title' ],
		"kol"     => $data[ 'kol' ],
		"ida"     => $data[ 'ida' ],
		"number"  => $data[ 'number' ],
		"tip"     => $data[ 'tip' ],
		"sklad"   => $data[ 'sklad' ],
		"clid"    => $data[ 'clid' ],
		"client"  => current_client( $data[ 'clid' ] ),
		"did"     => $data[ 'did' ],
		"dogovor" => current_dogovor( $data[ 'did' ] )
	];

}

if ( $action == 'export' ) {

	$otchet[] = explode( ";", "№ п.п.;Наименование;Кол-во;Номер акта;Дата акта;Тип;Склад;Клиент;Сделка" );

	$i = 1;

	foreach ( $list as $datum => $item ) {

		foreach ( $item as $value ) {

			$otchet[] = [
				$i,
				$value[ 'title' ],
				$value[ 'kol' ],
				$value[ 'number' ],
				$datum,
				untag3( $value[ 'tip' ] ),
				$value[ 'sklad' ],
				$value[ 'client' ],
				$value[ 'dogovor' ]
			];

			$i++;

		}

	}

	Shuchkin\SimpleXLSXGen ::fromArray( $otchet ) -> downloadAs( 'export_orderpoz.xlsx' );

	exit();

}
?>
<style>
	.td--main {
		height : 45px;
	}

	.td--main:hover {
		background : rgba(197, 225, 165, 1);
	}
</style>

<div class="zagolovok_rep fs-12" align="center">

	<h1 class="fs-20">Каталог-склад. Движение по ордерам</h1>
	<b>за период &nbsp;<?= format_date_rus( $da1 ) ?> &divide; <?= format_date_rus( $da2 ) ?></b>
	<span class="sw--hide">(<a href="javascript:void(0)" onclick="toExcel()" class="blue"><i class="icon-file-excel"></i> Экспорт</a>)</span>

</div>

<div class="noprint">

	<hr>

	<!--дополнителный фильтры-->
	<table class="noborder">
		<tr>
			<td width="20%">

				<div class="ydropDown">

					<span>По Типу</span><span class="ydropCount"><?= count( $tip ) ?> выбрано</span><i class="icon-angle-down pull-aright"></i>
					<div class="yselectBox" style="max-height: 300px;">
						<div class="yunSelect"><i class="icon-cancel-circled2"></i>Снять выделение</div>

						<div class="ydropString ellipsis">
							<label>
								<input class="taskss" name="tip[]" type="checkbox" id="tip[]" value="income" <?php if ( in_array( "income", $tip ) ) print 'checked'; ?>>&nbsp;Приходный
							</label>
						</div>

						<div class="ydropString ellipsis">
							<label>
								<input class="taskss" name="tip[]" type="checkbox" id="tip[]" value="outcome" <?php if ( in_array( "outcome", $tip ) ) print 'checked'; ?>>&nbsp;Расходный
							</label>
						</div>

					</div>

				</div>

			</td>
			<td width="20%">

				<div class="ydropDown">

					<span>По Складу</span><span class="ydropCount"><?= count( $sklad ) ?> выбрано</span><i class="icon-angle-down pull-aright"></i>
					<div class="yselectBox" style="max-height: 300px;">
						<div class="yunSelect"><i class="icon-cancel-circled2"></i>Снять выделение</div>
						<?php
						$result = $db -> getAll( "SELECT id, title FROM ".$sqlname."modcatalog_sklad WHERE identity = '$identity' ORDER BY title" );
						foreach ( $result as $data ) {

							print '
					<div class="ydropString ellipsis">
						<label>
							<input class="taskss" name="sklad[]" type="checkbox" id="sklad[]" value="'.$data[ 'id' ].'" '.( in_array( $data[ 'id' ], $sklad ) ? 'checked' : '' ).'>&nbsp;'.$data[ 'title' ].'
						</label>
					</div>';

						}
						?>
					</div>

				</div>

			</td>
			<td width="20%" class="">
				<A href="javascript:void(0)" onClick="generate()" class="button">Сформировать</A>
			</td>
			<td width="20%"></td>
			<td width="20%"></td>
		</tr>
	</table>

</div>

<div class="block mt10">

	<TABLE width="100%" cellpadding="5" cellspacing="0">
		<thead>
		<TR height="35">
			<th width="20" align="center"><b></b></th>
			<th width="20" align="center"><b>#</b></th>
			<th width="350" align="center"><b>Наименование</b></th>
			<th width="100" align="center"><b>Кол-во</b></th>
			<th width="80" align="center"><b>Акт</b></th>
			<th width="100" align="center"><b>Тип</b></th>
			<th width="160" align="center"><b>Склад</b></th>
			<th align="center"><b>Сделка</b>/<b>Заказчик</b></th>
		</TR>
		</thead>
		<tbody>
		<?php
		$n = 1;
		foreach ( $list as $key => $val ) {

			print '
			<tr class="td--main bluebg-sub" data-key='.$n.'>
				<td class="openprj" colspan="9">
					<i class="icon-plus-circled"></i>
					<div class="Bold blue fs-11 inline">Дата: '.format_date_rus( $key ).' г.</div>
				</td>
			</tr>
			';

			$num = 1;
			foreach ( $val as $key2 => $val2 ) {

				$icon = ( getDogData( $val2[ 'did' ], 'close' ) == 'yes' ) ? 'icon-lock red' : 'icon-briefcase-1 blue';

				print '
					<tr class="datetoggle td--main hidden sub" data-date='.$n.' >
						<td></td>
						<td align="left">'.$num.'</td>
						<td align="left">'.$val2[ 'title' ].'</td>
						<td align="center">'.$val2[ 'kol' ].'</td>
						<td align="right" class="hand">
							<a href="javascript:void(0)" onclick="doLoad(\'modules/modcatalog/form.modcatalog.php?id='.$val2[ 'ida' ].'&action=viewakt\');" title="Просмотр" class=""><i class="icon-eye green pull-left"></i>#'.$val2[ 'number' ].'</a>
						</td>
						<td align="center"><b>'.$val2[ 'tip' ].'</b></td>
						<td align="left">'.$val2[ 'sklad' ].'</td>
						<td>
							'.( $val2[ 'did' ] > 0 ? '<div class="ellipsis"><A href="javascript:void(0)" onclick="openDogovor(\''.$val2[ 'did' ].'\')" title="Открыть в новом окне"><i class="'.$icon.'"></i>&nbsp;'.$val2[ 'dogovor' ].'</A></div>' : '' ).'
							'.( $val2[ 'clid' ] > 0 ? '<br><div class="ellipsis"><A href="javascript:void(0)" onclick="openClient(\''.$val2[ 'clid' ].'\')"><i class="icon-building broun"></i>&nbsp;'.$val2[ 'client' ].'</A></div>' : '' ).'
						</td>
					</tr>
				';

				$num++;

			}

			$n++;

		}
		?>
		</tbody>
	</TABLE>

</div>

<div class="space-100"></div>

<DIV class="fixAddBotButton" style="left:auto; right: 50px" onclick="ToggleAll()" data-state="collapse">
	<i class="icon-plus"></i> <span>Развернуть всё</span>
</div>

<script>

	$('.openprj').on('click', function () {

		var key = $(this).closest('tr').data('key');

		$('tr.datetoggle[data-date="' + key + '"]').toggleClass('hidden');
		$(this).find('i').toggleClass('icon-plus-circled icon-minus-circled');

	});

	function ToggleAll() {

		var state = $('.fixAddBotButton').data('state');

		if (state === 'collapse') {

			$('.fixAddBotButton').data('state', 'expand');
			$('.fixAddBotButton').find('span').html('Свернуть всё');
			$('.fixAddBotButton').find('i').removeClass('icon-plus').addClass('icon-minus');
			$('.datetoggle').removeClass('hidden');
			$('.openprj').find('i').toggleClass('icon-plus-circled icon-minus-circled');

		}
		if (state === 'expand') {

			$('.fixAddBotButton').data('state', 'collapse');
			$('.fixAddBotButton').find('span').html('Развернуть всё');
			$('.fixAddBotButton').find('i').addClass('icon-plus').removeClass('icon-minus');
			$('.datetoggle').addClass('hidden');
			$('.openprj').find('i').toggleClass('icon-plus-circled icon-minus-circled');

		}

	}
</script>