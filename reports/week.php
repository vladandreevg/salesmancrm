<?php
/**
 * @license  http://isaler.ru/
 * @author   Vladislav Andreev, http://iandreyev.ru/
 * @charset  UTF-8
 * @version  6.4
 */

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

$action = $_REQUEST['action'];
$da1    = $_REQUEST['da1'];
$da2    = $_REQUEST['da2'];
$da     = $_REQUEST['da'];
$act    = $_REQUEST['act'];
$per    = $_REQUEST['per'];

if ( !$per )
	$per = 'nedelya';

$user_list   = (array)$_REQUEST['user_list'];
$fields       = (array)$_REQUEST['field'];
$field_query = (array)$_REQUEST['field_query'];

$tips = $_REQUEST['tips'];

$tips = (!empty( $tips )) ? $tips : $db -> getCol( "SELECT title FROM ".$sqlname."activities WHERE id > 0 and filter IN ('activ','all') and identity = '$identity'" );

$sort   = '';
$kolSum = 0;

function getDateCustom($date): string {

	$d = yexplode( " ", (string)$date );

	return format_date_rus( $d[0] )." ".getTime( $d[1] );

}

$colors = $db -> getIndCol( "title", "SELECT color, LOWER(title) as title FROM ".$sqlname."activities WHERE identity = '$identity'" );

//массив выбранных пользователей
$sort .= (!empty( $user_list )) ? "cc.iduser IN (".yimplode( ",", $user_list ).") and " : "cc.iduser IN (".yimplode( ",", (array)get_people( $iduser1, "yes" ) ).") and ";

$sort .= (!empty( $tips )) ? "hs.tip IN(".yimplode( ",", $tips, "'" ).") and " : "";

$i = 0;

$q = "
	SELECT 
		cc.clid as clid,
		cc.title as client,
		cc.iduser as iduser,
		cc.date_create as dcreate,
		us.title as user,
		hs.cid as cid,
		hs.datum as datum,
		hs.tip as tip,
		hs.des as content,
		hs.did as did,
		hs.iduser as huser
	FROM ".$sqlname."clientcat `cc`
		LEFT JOIN ".$sqlname."user `us` ON cc.iduser = us.iduser
		LEFT JOIN ".$sqlname."history `hs` ON cc.clid = hs.clid
	WHERE 
		hs.datum BETWEEN '".$da1." 00:00:00' and '".$da2." 23:59:59' and 
		$sort
		hs.identity = '$identity' and
		cc.identity = '$identity' 
	GROUP BY hs.cid
	ORDER BY hs.datum DESC";

$da = $db -> getAll( $q );

$dogs = [];

foreach ( $da as $data ) {

	$dfact = '';
	$prim  = '';
	$color = '';

	//сводная строка по сделке
	if ( empty( $dogs[ $data['clid'] ] ) ) {

		$dogs[ $data['clid'] ] = [
			"dcreate" => get_date( $data['dcreate'] ),
			"dogovor" => current_dogovor( $data['did'] ),
			"did"     => $data['did'],
			"client"  => $data['client'],
			"user"    => $data['user'],
			"datum"   => getDateCustom( $data['datum'] )
		];

	}

	//записи активностей по сделке
	$list[ $data['clid'] ][] = [
		"cid"     => $data['cid'],
		"datum"   => getDateCustom( $data['datum'] ),
		"tip"     => $data['tip'],
		"content" => $data['content'],
		"dogovor" => current_dogovor( $data['did'] ),
		"did"     => $data['did'],
		"user"    => current_user( $data['huser'] )
	];

}

if ( $action == 'export' ) {

	$elist = [];

	$header = [
		"Менеджер",
		"Клиент",
		"Сделка",
		"Дата",
		"Активность",
		"Содержание"
	];

	//строка заголовков
	$elist[] = $header;

	foreach ( $list as $clid => $history ) {

		foreach ( $history as $item ) {

			$elist[] = [
				$item['user'],
				current_client( $clid ),
				$item['dogovor'],
				$item['datum'],
				$item['tip'],
				$item['content']
			];

		}

	}

	//require_once "../opensource/class/php-excel.class.php";

	$from = [
		":",
		" "
	];
	$to   = [
		"-",
		"_"
	];

	Shuchkin\SimpleXLSXGen::fromArray( $otchet )->downloadAs(str_replace( $from, $to, current_datumtime() ).'-activitiesByClients.xlsx');

	exit();

}
?>

<style>
	<!--
	.dimple-custom-axis-line {
		stroke       : black !important;
		stroke-width : 1.1;
	}

	.dimple-custom-axis-label {
		font-family : Arial, serif !important;
		font-size   : 11px !important;
		font-weight : 500;
	}

	.dimple-custom-gridline {
		stroke-width     : 1;
		stroke-dasharray : 5;
		fill             : none;
		stroke           : #CFD8DC !important;
	}

	.td--main {
		height : 45px;
		cursor : pointer;
	}

	.color1 {
		background : rgba(255, 236, 179, .9);
	}

	.color2 {
		background : rgba(255, 249, 196, .9);
	}

	.td--main:hover {
		background : rgba(197, 225, 165, 1);
	}

	.td--sub {

	}

	.gray--3 {
		color : #37474F;
	}

	@media print {
		.fixAddBotButton {
			display : none;
		}
	}

	-->
</style>

<div class="relativ mt20 mb20 wp95 text-center">

	<h1 class="uppercase fs-14 m0 mb10">Активности по клиентам</h1>

	<div class="gray2">за период &nbsp;<?= format_date_rus( $da1 ) ?> &divide; <?= format_date_rus( $da2 ) ?>
		<span class="hidden1 Bold">[ <a href="javascript:void(0)" onclick="Export()" title="Выгрузить в Excel для Roistat" class="blue">Excel</a> ]</span>
	</div>

</div>

<hr>

<table class="noborder">
	<tr>
		<td class="wp25">
			<div class="ydropDown margtop5">
				<span>Только Активности</span><span class="ydropCount"><?= count( $tips ) ?> выбрано</span><i class="icon-angle-down pull-aright"></i>
				<div class="yselectBox">
					<div class="right-text">
						<div class="ySelectAll w0 inline" title="Выделить всё"><i class="icon-plus-circled"></i>Всё
						</div>
						<div class="yunSelect w0 inline" title="Снять выделение"><i class="icon-minus-circled"></i>Ничего
						</div>
					</div>
					<?php
					$result = $db -> query( "SELECT * FROM ".$sqlname."activities WHERE filter IN ('activ','all') and identity = '$identity' ORDER BY title" );
					while ($data = $db -> fetch( $result )) {

						print
							'<div class="ydropString ellipsis">
							<label>
								<input class="taskss" name="tips[]" type="checkbox" id="tips[]" value="'.$data['title'].'" '.(in_array( $data['title'], $tips ) ? 'checked' : '').'>
								<span class="bullet-mini" style="background: '.$data['color'].'"></span>&nbsp;'.$data['title'].'
							</label>
						</div>';

					}
					?>
					<div class="ydropString ellipsis">
						<label>
							<input class="taskss" name="tips[]" type="checkbox" id="tips[]" value="СобытиеCRM" <?php if ( in_array( 'СобытиеCRM', $tips ) )
								print 'checked'; ?>>
							<span class="bullet-mini" style="background: #9E9E9E"></span>&nbsp;СобытиеCRM
						</label>
					</div>
					<div class="ydropString ellipsis">
						<label>
							<input class="taskss" name="tips[]" type="checkbox" id="tips[]" value="ЛогCRM" <?php if ( in_array( 'ЛогCRM', $tips ) )
								print 'checked'; ?>>
							<span class="bullet-mini" style="background: #607D8B"></span>&nbsp;ЛогCRM
						</label>
					</div>
				</div>
			</div>
		</td>
		<td class="wp25"></td>
		<td class="wp25"></td>
		<td></td>
	</tr>
</table>

<hr>

<div class="block">

	<TABLE>
		<thead class="sticked--top">
		<TR class="th35">
			<th class="w20 text-center"></th>
			<th class="w20 text-center"><b>#</b></th>
			<th class="w120 text-center"><b>Дата<br>создан.</b></th>
			<th class="w120 text-center"><b>Дата<br>активности.</b></th>
			<th class="text-center"><b>Заказчик</b></th>
			<th class="w120 text-center"><b>Ответств.</b></th>
		</TR>
		</thead>
		<tbody>
		<?php
		$num = 1;
		foreach ( $dogs as $key => $val ) {

			$color = ($num & 1) ? 'color1' : 'color2';

			print '
			<tr class="datetoggle td--main th40 '.$color.'" data-key="'.$key.'">
				<td class="text-right"><i class="icon-plus-circled gray2"></i></td>
				<td class="text-right"><b>'.$num.'.</b></td>
				<td>'.$val['dcreate'].'</td>
				<td>'.$val['datum'].'</td>
				<td>
					<div class="ellipsis Bold">
						<A href="javascript:void(0)" onclick="openClient(\''.$key.'\')"><i class="icon-building broun"></i>&nbsp;'.$val['client'].'</A>
					</div>
					'.($v['did'] > 0 ? '<br><div class="ellipsis fs-09 mt5"><A href="javascript:void(0)" onclick="openDogovor(\''.$v['did'].'\')" class="gray" title="Открыть в новом окне"><i class="icon-briefcase blue"></i>'.$v['dogovor'].'</A></div>' : '').'
				</td>
				<td><div class="ellipsis"><i class="icon-user-1 blue"></i>&nbsp;'.$val['user'].'</div></td>
			</tr>
			';

			$number = 1;

			foreach ( $list[ $key ] as $k => $v ) {

				print
					'<tr class="ha hidden sub gray--3 th40" data-date="'.$key.'">
					<TD></TD>
					<TD class="text-right">'.$number.'.</TD>
					<TD>
						<div class="ellipsis">
							<span style="color:'.strtr( mb_strtolower( $v['tip'] ), $colors ).'">'.get_ticon( $v['tip'] ).'</span>'.$v['tip'].'
						</div>
					</TD>
					<TD>'.$v['datum'].'</TD>
					<TD title="'.html2text( str_replace( "<br>", "\n", $v['content'] ) ).'">
						<div class="dot-ellipsis hand" onclick="viewHistory(\''.$v['cid'].'\')">'.str_replace( "\n", "<br>", $v['content'] ).'</div>
					</TD>
					<TD><div class="ellipsis"><i class="icon-user-1 gray2"></i>&nbsp;'.$v['user'].'</div></TD>
				</tr>';

				$number++;

			}

			$num++;

		}
		?>
		</tbody>
	</TABLE>
</div>

<div class="space-100"></div>

<DIV class="fixAddBotButton" style="left:auto; right: 50px" onclick="ToggleAll()" data-state="collapse">
	<i class="icon-plus"></i> <span>Развернуть всё</span>
</div>

<script src="/assets/js/jquery.liTextLength.js"></script>
<script>

	$(".dot-ellipsis").liTextLength({
		length: 200,
		afterLength: '...',
		fullText: false
	});

	$('.datetoggle').on('click', function () {

		var key = $(this).data('key');

		$('tr.sub').not('[data-date="' + key + '"]').addClass('hidden');
		$('tr.sub[data-date="' + key + '"]').toggleClass('hidden');

		$(this).find('i:first').toggleClass('icon-plus-circled icon-minus-circled');

	});

	function Toggle(date) {

		$('tr.sub').addClass('hidden');
		$('tr[data-date="' + date + '"]').toggleClass('hidden show');

	}

	function ToggleAll() {

		var state = $('.fixAddBotButton').data('state');

		if (state == 'collapse') {

			$('.fixAddBotButton').data('state', 'expand');
			$('.fixAddBotButton').find('span').html('Свернуть всё');
			$('.fixAddBotButton').find('i:first').removeClass('icon-plus').addClass('icon-minus');
			$('tr.sub').removeClass('hidden');

		}
		if (state == 'expand') {

			$('.fixAddBotButton').data('state', 'collapse');
			$('.fixAddBotButton').find('span').html('Развернуть всё');
			$('.fixAddBotButton').find('i:first').addClass('icon-plus').removeClass('icon-minus');
			$('tr.sub').addClass('hidden');

		}

	}

	function Export() {

		var str = $('#selectreport').serialize();
		window.open('reports/' + $('#report option:selected').val() + '?action=export&' + str);

	}

</script>