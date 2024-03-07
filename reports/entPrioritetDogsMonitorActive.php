<?php
/**
 * @license  http://isaler.ru/
 * @author   Vladislav Andreev, http://iandreyev.ru/
 * @charset  UTF-8
 * @version  6.4
 */
?>
<?php
set_time_limit( 0 );
error_reporting( 0 );
header( "Pragma: no-cache" );

include "../inc/config.php";
include "../inc/dbconnector.php";
include "../inc/auth.php";
include "../inc/settings.php";
include "../inc/func.php";

function dateFormat( $date_orig, $format = 'excel' ) {

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
			$date_new = $date_new[ 1 ].".".$date_new[ 2 ].".".$date_new[ 0 ];

		}
		else $date_new = '';

	}
	elseif ( $date_orig != '0000-00-00' || $date_orig == '' ) $date_new = '';

	return $date_new;
}

function num2excelExt( $string, $s = 2 ) {

	$string = str_replace( ",", ".", $string );
	$string = str_replace( " ", "", $string );

	$string = number_format( $string, $s, '.', '' );

	return $string;
}

function yimplodeExt( $divider, $array ) {

	$string = '';

	if ( is_array( $array ) ) {

		for ( $i = 0; $i < count( $array ); $i++ ) {

			if ( trim( $array[ $i ] ) != '' ) {

				if ( $i == 0 ) $string .= trim( $array[ $i ] );
				else $string .= $divider.trim( $array[ $i ] );

			}

		}
	}
	else $string = $array;

	return $string;
}

$action = $_REQUEST[ 'action' ];
$da1    = $_REQUEST[ 'da1' ];
$da2    = $_REQUEST[ 'da2' ];

$user_list = $_REQUEST[ 'user_list' ];

$field       = $_REQUEST[ 'field' ];
$field_query = $_REQUEST[ 'field_query' ];

$sort = '';

//массив выбранных пользователей
if ( $user_list[ 0 ] != '' ) $sort .= " and ".$sqlname."dogovor.iduser IN (".implode( ",", $user_list ).")";
else $sort .= str_replace( "iduser", $sqlname."dogovor.iduser", get_people( $iduser1 ) );

$kolSum = $kolMarg = 0;

if ( $action == 'export' ) $format = 'excel';
else $format = 'date';

$q = "
SELECT
	".$sqlname."dogovor.did as did,
	".$sqlname."dogovor.title as dogovor,
	".$sqlname."dogovor.datum as dcreate,
	".$sqlname."dogovor.datum_plan as dplan,
	".$sqlname."dogovor.datum_close as dclose,
	".$sqlname."dogovor.idcategory as idstep,
	".$sqlname."dogovor.tip as tip,
	".$sqlname."dogovor.clid as clid,
	".$sqlname."dogovor.pid as pid,
	".$sqlname."dogovor.kol as kol,
	".$sqlname."dogovor.marga as marga,
	".$sqlname."dogovor.kol_fact as kolf,
	".$sqlname."dogovor.close as close,
	".$sqlname."dogovor.iduser as iduser,
	".$sqlname."dogovor.adres as adres,
	".$sqlname."dogovor.content as content,
	".$sqlname."personcat.person as person,
	".$sqlname."user.title as user,
	".$sqlname."clientcat.title as client,
	".$sqlname."dogcategory.title as step,
	".$sqlname."dogcategory.content as steptitle,
	".$sqlname."dogtips.title as tips,
	".$sqlname."dogstatus.title as dstatus
FROM ".$sqlname."dogovor
	LEFT JOIN ".$sqlname."user ON ".$sqlname."dogovor.iduser = ".$sqlname."user.iduser
	LEFT JOIN ".$sqlname."personcat ON ".$sqlname."dogovor.pid = ".$sqlname."personcat.pid
	LEFT JOIN ".$sqlname."clientcat ON ".$sqlname."dogovor.clid = ".$sqlname."clientcat.clid
	LEFT JOIN ".$sqlname."dogcategory ON ".$sqlname."dogovor.idcategory = ".$sqlname."dogcategory.idcategory
	LEFT JOIN ".$sqlname."dogtips ON ".$sqlname."dogovor.tip = ".$sqlname."dogtips.tid
	LEFT JOIN ".$sqlname."dogstatus ON ".$sqlname."dogovor.sid = ".$sqlname."dogstatus.sid
	$ht
WHERE
	".$sqlname."dogovor.datum BETWEEN '".$da1." 00:00:01' and '".$da2." 23:59:59' and
	".$sqlname."dogovor.close != 'yes' and
	".$sqlname."dogovor.identity = '$identity'
	$sort
ORDER BY ".$sqlname."dogovor.datum_close";

$result = $db -> getAll( $q );
foreach ( $result as $data ) {

	$history = '';
	$color   = '';

	//Сформируем записи активностей
	$h = [];

	$resh = $db -> getAll( "select * from ".$sqlname."history WHERE did='".$data[ 'did' ]."' and tip NOT IN ('СобытиеCRM','ЛогCRM') and identity = '$identity' ORDER BY cid DESC LIMIT 3" );
	foreach ( $resh as $da ) {

		if ( $da[ 'datum' ] != '0000-00-00 00:00:00' and $action != 'export' ) {

			$h[] = str_replace( ";", ",", '<strong>'.get_sfdate( $da[ 'datum' ] ).'</strong>: '.$da[ 'tip' ].', '.$da[ 'des' ].' <br>' );

		}
		if ( $da[ 'datum' ] != '0000-00-00 00:00:00' and $action == 'export' ) {

			$des = str_replace( ";", ",", str_replace( "<br>", "\r", $da[ 'des' ] ) );
			$h[] = get_sfdate( $da[ 'datum' ] ).": ".$da[ 'tip' ]."\r".$des."\r";

		}

	}

	$history = yimplodeExt( "", $h );

	if ( $data[ 'close' ] == 'yes' ) {
		$dfact = format_date_rus( $data[ 'dclose' ] );
		$icon  = '<i class="icon-lock red"></i>';
		$kolf  = num_format( $data[ 'kolf' ], 2 );
	}
	else {
		$dfact = '';
		$icon  = '<i class="icon-briefcase blue"></i>';
		$kolf  = '';
	}

	//цветовая схема
	if ( $data[ 'close' ] == 'yes' and $data[ 'kolf' ] > 0 ) $color = 'greenbg-sub';
	if ( $data[ 'close' ] == 'yes' and $data[ 'kolf' ] == 0 ) $color = 'redbg-sub';

	//Здоровье сделки. конец.
	$dogs[] = [
		"datum"   => dateFormat( $data[ 'dcreate' ], $format ),
		"did"     => $data[ 'did' ],
		"dogovor" => $data[ 'dogovor' ],
		"tip"     => $data[ 'tips' ],
		"step"    => $data[ 'step' ],
		"dplan"   => dateFormat( $data[ 'dplan' ], $format ),
		"dfact"   => dateFormat( $dfact, $format ),
		"client"  => $data[ 'client' ],
		"clid"    => $data[ 'clid' ],
		"kol"     => $data[ 'kol' ],
		"kolf"    => $kolf,
		"marga"   => $data[ 'marga' ],
		"zakup"   => $data[ 'kol' ] - $data[ 'marga' ],
		"user"    => $data[ 'user' ],
		"close"   => $data[ 'close' ],
		"content" => $data[ 'content' ],
		"color"   => $color,
		"icon"    => $icon,
		"history" => $history
	];

	$kolSum += $data[ 'kol' ];
	$kolMarg += $data[ 'marga' ];

}

if ( $action == 'export' ) {

	//include_once '../opensource/tbs_us/tbs_class.php';
	//include_once '../opensource/tbs_us/plugins/tbs_plugin_opentbs.php';

	$templateFile = 'templates/prioritetTemp.xlsx';
	$outputFile   = 'exportDealsPrioritet.xlsx';

	$TBS = new clsTinyButStrong; // new instance of TBS
	$TBS -> PlugIn( TBS_INSTALL, OPENTBS_PLUGIN ); // load the OpenTBS plugin
	//$TBS->Plugin(OPENTBS_DEBUG_XML_SHOW);
	$TBS -> SetOption( noerr, true );
	$TBS -> LoadTemplate( $templateFile, OPENTBS_ALREADY_UTF8 );

	$TBS -> MergeBlock( 'list', $dogs );
	$TBS -> Show( OPENTBS_DOWNLOAD, $outputFile );

	exit();

}

?>
<br/>

<div class="zagolovok_rep">
	<b>Сделки на период&nbsp;с&nbsp;<?= format_date_rus( $da1 ) ?>&nbsp;по&nbsp;<?= format_date_rus( $da2 ) ?></b> (<a href="javascript:void(0)" onClick="exportDeal()" style="color:blue">Экспорт в Excel</a>):
</div>

<hr>

<TABLE class="top">
	<thead class="sticked--top">
	<TR class="header_contaner">
		<th class="w20"><b>#</b></th>
		<th class="w70"><b>Дата<br>создан.</b></th>
		<th class="w70"><b>Дата<br>план.</b></th>
		<th><b>Заказчик</b>/<b>Сделка</b></th>
		<th class="w160"><b>Тип</b></th>
		<th class="w80"><b>Ответств.</b></th>
		<th class="w120"><b>Сумма, <?= $valuta ?></b></th>
		<th class="w120"><b>Маржа, <?= $valuta ?></b></th>
		<th class="w70"><b>Этап сделки</b></th>
		<th class="w250"><b>Описание</b></th>
	</TR>
	</thead>
	<?php
	for ( $i = 0, $iMax = count( $dogs ); $i < $iMax; $i++ ) {
		?>
		<TR class="ha <?= $dogs[ $i ][ 'color' ] ?> th40">
			<TD class="text-right"><?= $i + 1 ?>.</TD>
			<TD class="text-center"><span class=""><?= $dogs[ $i ][ 'datum' ] ?></span></TD>
			<TD class="text-center"><span class=""><?= $dogs[ $i ][ 'dplan' ] ?></span></TD>
			<TD>
				<div class="ellipsis fs-11 Bold">
					<A href="javascript:void(0)" onclick="openDogovor('<?= $dogs[ $i ][ 'did' ] ?>')" title="Открыть в новом окне" class="black"><?= $dogs[ $i ][ 'icon' ] ?>&nbsp;<?= $dogs[ $i ][ 'dogovor' ] ?></A>
				</div>
				<br>
				<div class="ellipsis">
					<A href="javascript:void(0)" onclick="openClient('<?= $dogs[ $i ][ 'clid' ] ?>')"><i class="icon-building broun"></i></A>&nbsp;<?= $dogs[ $i ][ 'client' ] ?>
				</div>
			</TD>
			<TD>
				<div class="ellipsis"><?= $dogs[ $i ][ 'tip' ] ?></div>
			</TD>
			<TD>
				<div class="ellipsis"><?= $dogs[ $i ][ 'user' ] ?></div>
			</TD>
			<TD class="text-right">
				<?= num_format( $dogs[ $i ][ 'kol' ] ) ?>
				<div class="gray2 fs-09" title="Сумма закрытия"><?= $dogs[ $i ][ 'kolf' ] ?></div>
			</TD>
			<TD class="text-right"><?= num_format( $dogs[ $i ][ 'marga' ] ) ?></TD>
			<TD class="text-right"><?= $dogs[ $i ][ 'step' ] ?>%</TD>
			<TD>
				<div class="ellipsis1"><?= $dogs[ $i ][ 'content' ] ?></div>
			</TD>
		</TR>
	<?php } ?>
	<tfoot class="sticked--bottom">
	<TR class="th40 orangebg">
		<td></td>
		<td></td>
		<td></td>
		<td></td>
		<td></td>
		<td></td>
		<td class="text-right"><b><?= num_format( $kolSum ) ?></b></td>
		<td class="text-right"><b><?= num_format( $kolMarg ) ?></b></td>
		<td></td>
		<td></td>
	</TR>
	</tfoot>
</TABLE>

<div style="height:80px"></div>

<script>
	function exportDeal() {

		var str = $('#selectreport').serialize();
		window.open('reports/' + $('#report option:selected').val() + '?action=export&' + str);
	}
</script>