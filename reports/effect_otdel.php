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

$da1 = $_REQUEST['da1'];
$da2 = $_REQUEST['da2'];
$da  = $_REQUEST['da'];
$act = $_REQUEST['act'];
$per = $_REQUEST['per'];
if ( !$per )
	$per = 'nedelya';

//Создание массивов данных
$i      = 0;
$result = $db -> getAll( "SELECT * FROM ".$sqlname."user WHERE tip='Руководитель отдела' and identity = '$identity'" );
foreach ( $result as $data_array ) {

	$manpro = $data_array['title'];
	$sort   = get_people( $data_array['iduser'] );

	$otdel = $db -> getOne( "SELECT title FROM ".$sqlname."otdel_cat WHERE idcategory='".$data_array['otdel']."' and identity = '$identity'" );

	$hist = $db -> getOne( "SELECT COUNT(*) as count FROM ".$sqlname."history WHERE did<1 and datum between '".$da1." 00:00:01' and '".$da2." 23:59:59' ".$sort." and identity = '$identity'" );

	$hist_d = $db -> getOne( "SELECT COUNT(*) as count FROM ".$sqlname."history WHERE did>0 and datum between '".$da1." 00:00:01' and '".$da2." 23:59:59' ".$sort." and identity = '$identity'" );

	$result4 = $db -> getRow( "SELECT COUNT(*) as count, SUM(kol) as kol FROM ".$sqlname."dogovor WHERE datum between '".$da1."' and '".$da2."' ".$sort." and identity = '$identity'" );
	$new_d   = $result4['count'];
	$kol     = $result4['kol'];

	$result5 = $db -> getRow( "SELECT COUNT(*) as count, SUM(kol) as kol FROM ".$sqlname."dogovor WHERE datum_izm between '".$da1."' and '".$da2."' ".$sort." and identity = '$identity'" );
	$izm_d   = $result5['count'];
	$kol_i   = $result5['kol'];

	$result6 = $db -> getRow( "SELECT COUNT(*) as count, SUM(kol_fact) as kol FROM ".$sqlname."dogovor WHERE close='yes' and datum_close between '".$da1."' and '".$da2."' ".$sort." and identity = '$identity'" );
	$cl_d    = $result6['count'];
	$kol_c   = $result6['kol'];

	$effect[ $i ] = [
		"otdel"         => $otdel,
		"manager"       => $manpro,
		"activ_client"  => $hist,
		"activ_dogovor" => $hist_d,
		"new_dogs"      => $new_d,
		"kol_new"       => $kol,
		"izm_dogs"      => $izm_d,
		"kol_izm"       => $kol_i,
		"close_dogs"    => $cl_d,
		"kol_close"     => $kol_c
	];

	$all_hist   = $all_hist + $hist;
	$all_hist_d = $all_hist_d + $hist_d;
	$all_new_d  = $all_new_d + $new_d;
	$all_izm_d  = $all_izm_d + $izm_d;
	$all_cl_d   = $all_cl_d + $cl_d;
	$all_kol    = $all_kol + pre_format( $kol );
	$all_kol_i  = $all_kol_i + pre_format( $kol_i );
	$all_kol_c  = $all_kol_c + pre_format( $kol_c );

	$i      = $i + 1;
	$hist   = 0;
	$hist_d = 0;
	$new_d  = 0;
	$izm_d  = 0;
	$cl_d   = 0;
	$kol    = 0;
	$kol_i  = 0;
	$kol_c  = 0;
}
?>

<div class="zagolovok_rep fs-12" align="center">
	<h1>Эффективность отделов</h1>
	<b>за период &nbsp;<?= format_date_rus( $da1 ) ?> &divide; <?= format_date_rus( $da2 ) ?></b>
</div>

<hr>

<table width="98%" border="0" cellpadding="2" cellspacing="0" id="bborder">
	<thead>
	<TR height="40">
		<th width="30" align="center">&nbsp;</th>
		<th align="center"><B>Руководитель &#8250; Отдел</B></th>
		<th width="40" align="center"><i class="icon-building blue"></i></th>
		<th width="40" align="center"><i class="icon-briefcase-1 broun"></i></th>
		<th width="100" align="center"><B>Сделок новых</B></th>
		<th width="100" align="center"><b>Сумма, <?= $valuta ?></b></th>
		<th width="100" align="center"><B>Сделок измен.</B></th>
		<th width="100" align="center"><b>Сумма, <?= $valuta ?></b></th>
		<th width="100" align="center"><b>Сделок закр.</b></th>
		<th width="100" align="center"><b>Сумма, <?= $valuta ?></b></th>
	</tr>
	</thead>
	<tbody>
	<?php
	for ( $j = 0; $j < $i; $j++ ) {
		?>
		<TR height="40" class="ha">
			<TD align="center"># <?= $j + 1 ?></TD>
			<TD>
				<DIV class="ellipsis1 fs-12" title="<?= $effect[ $j ]['manager'] ?>">
					<b><?= $effect[ $j ]['otdel'] ?></b></DIV>
				<div class="fs-09 gray2 mt5">Руководитель: <b><?= $effect[ $j ]['manager'] ?></b></div>
			</TD>
			<TD align="center">
				<DIV title="<?= $effect[ $j ]['activ_client'] ?>"><?= $effect[ $j ]['activ_client'] ?></DIV>
			</TD>
			<TD align="center">
				<DIV title="<?= $effect[ $j ]['activ_dogovor'] ?>"><?= $effect[ $j ]['activ_dogovor'] ?></DIV>
			</TD>
			<TD align="center">
				<DIV title="<?= $effect[ $j ]['new_dogs'] ?>"><?= $effect[ $j ]['new_dogs'] ?></DIV>
			</TD>
			<TD align="right" nowrap>
				<DIV title="<?= $effect[ $j ]['kol_new'] ?>"><?= num_format( $effect[ $j ]['kol_new'] ) ?></DIV>
			</TD>
			<TD align="center">
				<DIV title="<?= $effect[ $j ]['izm_dogs'] ?>"><?= $effect[ $j ]['izm_dogs'] ?></DIV>
			</TD>
			<TD align="right" nowrap>
				<DIV title="<?= $effect[ $j ]['kol_izm'] ?>"><?= num_format( $effect[ $j ]['kol_izm'] ) ?></DIV>
			</TD>
			<TD align="center">
				<DIV title="<?= $effect[ $j ]['close_dogs'] ?>"><?= $effect[ $j ]['close_dogs'] ?></DIV>
			</TD>
			<TD align="right" nowrap>
				<DIV title="<?= $effect[ $j ]['kol_close'] ?>"><?= num_format( $effect[ $j ]['kol_close'] ) ?></DIV>
			</TD>
		</TR>
		<?php
	}
	?>
	<TR bgcolor="#FC9" height="40">
		<TD align="center">&nbsp;</TD>
		<TD align="center"><B>ИТОГО</B></TD>
		<TD align="center"><B><?= $all_hist ?></B></TD>
		<TD align="center"><B><?= $all_hist_d ?></B></TD>
		<TD align="center"><B><?= $all_new_d ?></B></TD>
		<TD align="right" nowrap><B><?= num_format( $all_kol ) ?></B></TD>
		<TD align="center"><B><?= $all_izm_d ?></B></TD>
		<TD align="right" nowrap><B><?= num_format( $all_kol_i ) ?></B></TD>
		<TD align="center"><B><?= $all_cl_d ?></B></TD>
		<TD align="right" nowrap><B><?= num_format( $all_kol_c ) ?></B></TD>
	</TR>
	</TBODY>
</TABLE>
<div style="height:90px"></div>