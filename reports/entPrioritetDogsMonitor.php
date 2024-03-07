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

function dateFormat($date_orig, $format = 'excel') {

	$date_new = '';

	if ( $format == 'excel' ) {

		if ( $date_orig != '0000-00-00' && $date_orig != '' && $date_orig != NULL ) {
			$date_new = $date_orig;
		}

	}
	elseif ( $format == 'date' ) {

		if ( $date_orig && $date_orig != '0000-00-00' ) {

			$date_new = explode( "-", $date_orig );
			$date_new = $date_new[1].".".$date_new[2].".".$date_new[0];

		}

	}

	return $date_new;

}

function num2excelExt($string, $s = 2): string {

	$string = str_replace( ",", ".", $string );
	$string = str_replace( " ", "", $string );

	return number_format( $string, $s, '.', '' );

}

$action = $_REQUEST['action'];
$da1    = $_REQUEST['da1'];
$da2    = $_REQUEST['da2'];

$user_list   = (array)$_REQUEST['user_list'];
$field       = (array)$_REQUEST['field'];
$field_query = (array)$_REQUEST['field_query'];

$sort = '';

//массив выбранных пользователей
if ( !empty( $user_list ) ) {
	$sort .= " deal.iduser IN (".yimplode( ",", $user_list ).") AND ";
}
else {
	$sort .= " deal.iduser IN (".yimplode( ",", (array)get_people( $iduser1, 'yes' ) ).") AND ";
}

//составляем запрос по параметрам сделок
$ar = [];
foreach ( $fields as $i => $field ) {

	if ( !in_array( $field, $ar ) && $field != '' ) {
		$sort .= " deal.".$field." = '".$field_query[ $i ]."' AND ";
	}
	elseif($field == 'close'){
		$sort .= $field_query[ $i ] != 'yes' ? " COALESCE(deal.{$field}, 'no') != 'yes' AND " : " COALESCE(deal.{$field}, 'no') == 'yes' AND ";
	}

}

$kolSum = $kolMarg = 0;

if ( $action == 'export' ) {
	$format = 'excel';
}
else {
	$format = 'date';
}

$q = "
SELECT
	deal.did as did,
	deal.title as dogovor,
	deal.datum as dcreate,
	deal.datum_plan as dplan,
	deal.datum_close as dclose,
	deal.idcategory as idstep,
	deal.tip as tip,
	deal.clid as clid,
	deal.pid as pid,
	deal.kol as kol,
	deal.marga as marga,
	deal.kol_fact as kolf,
	deal.close as close,
	deal.iduser as iduser,
	deal.adres as adres,
	deal.content as content,
	us.title as user,
	cc.title as client,
	dc.title as step,
	dc.content as steptitle,
	dt.title as tips,
	ds.title as dstatus
FROM ".$sqlname."dogovor `deal`
	LEFT JOIN ".$sqlname."user `us` ON deal.iduser = us.iduser
	LEFT JOIN ".$sqlname."clientcat `cc` ON deal.clid = cc.clid
	LEFT JOIN ".$sqlname."dogcategory `dc` ON deal.idcategory = dc.idcategory
	LEFT JOIN ".$sqlname."dogtips `dt` ON deal.tip = dt.tid
	LEFT JOIN ".$sqlname."dogstatus `ds` ON deal.sid = ds.sid
	$ht
WHERE
	deal.datum BETWEEN '$da1 00:00:01' and '$da2 23:59:59' and
	COALESCE(deal.close, 'no') != 'yes' and
	$sort
	deal.identity = '$identity'
ORDER BY deal.datum_close";

$result = $db -> getAll( $q );
foreach ( $result as $data ) {

	$history = '';
	$color   = '';

	//Сформируем записи активностей
	$h = [];

	$resh = $db -> getAll( "select * from ".$sqlname."history WHERE did='".$data['did']."' and tip NOT IN ('СобытиеCRM','ЛогCRM') and identity = '$identity' ORDER BY cid DESC LIMIT 3" );
	foreach ( $resh as $da ) {

		if ( $da['datum'] != '0000-00-00 00:00:00' && $action != 'export' ) {

			$h[] = str_replace( ";", ",", '<strong>'.get_sfdate( $da['datum'] ).'</strong>: '.$da['tip'].', '.$da['des'].' <br>' );

		}
		if ( $da['datum'] != '0000-00-00 00:00:00' && $action == 'export' ) {

			$des = str_replace( [
				"<br>",
				";"
			], [
				"\r",
				","
			], $da['des'] );
			$h[] = get_sfdate( $da['datum'] ).": ".$da['tip']."\r".$des."\r";

		}

	}

	$history = yimplode( "", $h );

	if ( $data['close'] == 'yes' ) {
		$dfact = format_date_rus( $data['dclose'] );
		$icon  = '<i class="icon-lock red"></i>';
		$kolf  = num_format( $data['kolf'], 2 );
	}
	else {
		$dfact = '';
		$icon  = '<i class="icon-briefcase blue"></i>';
		$kolf  = '';
	}

	//цветовая схема
	if ( $data['close'] == 'yes' && $data['kolf'] > 0 ) {
		$color = 'greenbg-sub';
	}

	if ( $data['close'] == 'yes' && $data['kolf'] == 0 ) {
		$color = 'redbg-sub';
	}

	//Здоровье сделки. конец.
	$dogs[] = [
		"datum"   => dateFormat( $data['dcreate'], $format ),
		"did"     => $data['did'],
		"dogovor" => $data['dogovor'],
		"tip"     => $data['tips'],
		"step"    => $data['step'],
		"dplan"   => dateFormat( $data['dplan'], $format ),
		"dfact"   => dateFormat( $dfact, $format ),
		"client"  => $data['client'],
		"clid"    => $data['clid'],
		"kol"     => $data['kol'],
		"kolf"    => $kolf,
		"marga"   => $data['marga'],
		"zakup"   => $data['kol'] - $data['marga'],
		"user"    => $data['user'],
		"close"   => $data['close'],
		"content" => $data['content'],
		"color"   => $color,
		"icon"    => $icon,
		"history" => $history
	];

	$kolSum  += $data['kol'];
	$kolMarg += $data['marga'];

}

if ( $action == 'export' ) {

	$templateFile = 'templates/prioritetTemp.xlsx';
	$outputFile   = 'exportDealsPrioritet.xlsx';

	$TBS = new clsTinyButStrong; // new instance of TBS
	$TBS -> PlugIn( TBS_INSTALL, OPENTBS_PLUGIN ); // load the OpenTBS plugin
	//$TBS->Plugin(OPENTBS_DEBUG_XML_SHOW);
	$TBS -> SetOption( 'noerr', true );
	$TBS -> LoadTemplate( $templateFile, OPENTBS_ALREADY_UTF8 );

	$TBS -> MergeBlock( 'list', $dogs );
	$TBS -> Show( OPENTBS_DOWNLOAD, $outputFile );

	exit();

}

?>

<div class="zagolovok_rep text-center">
	<h1 class="m0 p0 fs-14 mb10">Сделки на период</h1>
	<b>&nbsp;с&nbsp;<?= format_date_rus( $da1 ) ?>&nbsp;по&nbsp;<?= format_date_rus( $da2 ) ?></b> (<a href="javascript:void(0)" onClick="exportDeal()" style="color:blue">Экспорт в Excel</a>):
</div>

<hr>

<TABLE class="top">
	<thead class="sticked--top">
	<TR class="header_contaner">
		<th class="w20">#</th>
		<th class="w70">Дата<br>создан.</th>
		<th class="w70">Дата<br>план.</th>
		<th>Заказчик/Сделка</th>
		<th class="w160">Тип</th>
		<th class="w80">Ответств.</th>
		<th class="w120">Сумма, <?= $valuta ?></th>
		<th class="w120">Маржа, <?= $valuta ?></th>
		<th class="w70">Этап</th>
		<th class="w250">Описание</th>
	</TR>
	</thead>
	<?php
	foreach ($dogs as $i => $row) {
		?>
		<TR class="ha <?= $row['color'] ?> th35">
			<TD class="text-right"><?= $i + 1 ?>.</TD>
			<TD class="text-center"><span class=""><?= $row['datum'] ?></span></TD>
			<TD class="text-center"><span class=""><?= $row['dplan'] ?></span></TD>
			<TD>
				<div class="ellipsis fs-11 Bold">
					<A href="javascript:void(0)" onclick="openDogovor('<?= $row['did'] ?>')" title="Открыть в новом окне" class="black"><?= $row['icon'] ?>&nbsp;<?= $row['dogovor'] ?></A>
				</div>
				<br>
				<div class="ellipsis">
					<A href="javascript:void(0)" onclick="openClient('<?= $row['clid'] ?>')"><i class="icon-building broun"></i></A>&nbsp;<?= $row['client'] ?>
				</div>
			</TD>
			<TD>
				<div class="ellipsis"><?= $row['tip'] ?></div>
			</TD>
			<TD>
				<div class="ellipsis"><?= $row['user'] ?></div>
			</TD>
			<TD class="text-right">
				<?= num_format( $row['kol'] ) ?>
				<div class="gray2 fs-09" title="Сумма закрытия"><?= $row['kolf'] ?></div>
			</TD>
			<TD class="text-right"><?= num_format( $row['marga'] ) ?></TD>
			<TD class="text-right"><?= $row['step'] ?>%</TD>
			<TD>
				<div class="ellipsis1"><?= $row['content'] ?></div>
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