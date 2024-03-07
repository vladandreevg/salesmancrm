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
if (!$per) $per = 'nedelya';
?>

<div class="zagolovok_rep" align="center">
	<h2>Эффективность сотрудников</h2>
	<div class="fs-10">за период&nbsp;с&nbsp;<?= format_date_rus($da1) ?>&nbsp;по&nbsp;<?= format_date_rus($da2) ?></div>
</div>

<div class="pad10">

	<table width="100%" border="0" align="center" cellpadding="5" cellspacing="0" id="zebra">
		<thead class="sticked--top">
		<TR class="">
			<th align="center"><B>Сотрудник</B></th>
			<th width="80" align="center"><B>Сделок<BR>новых</B></th>
			<th width="100" align="center"><b>Сумма, <?= $valuta ?></b></th>
			<th width="80" align="center"><B>Сделок<BR>измен.</B></th>
			<th width="100" align="center"><b>Сумма, <?= $valuta ?></b></th>
			<th width="80" align="center"><b>Сделок<BR>закр.</b></th>
			<th width="100" align="center"><b>Сумма, <?= $valuta ?></b></th>
		</TR>
		</THEAD>
		<TBODY>
		<?php
		$all_hist   = 0;
		$all_hist_d = 0;
		$all_new_d  = 0;
		$all_izm_d  = 0;
		$all_cl_d   = 0;
		$all_kol    = 0;
		$all_kol_i  = 0;
		$all_kol_c  = 0;

		$a = "";

		$result = $db -> getAll("SELECT * FROM ".$sqlname."user WHERE identity = '$identity' ORDER BY title");
		foreach ($result as $data_array) {

			$manpro = $data_array['title'];

			/*$hist = $db->getOne("SELECT COUNT(*) as count FROM ".$sqlname."clientcat WHERE datum between '".$da1."' and '".$da2."' and iduser='".$data_array['iduser']."' and identity = '$identity'");

			$hist_d = $db->getOne("SELECT COUNT(*) as count FROM ".$sqlname."dogshist WHERE datum between '".$da1."' and '".$da2."' and iduser='".$data_array['iduser']."' WHERE identity = '$identity'");*/

			$result4 = $db -> getRow("SELECT COUNT(*) as count, SUM(kol) as summa FROM ".$sqlname."dogovor WHERE datum between '".$da1."' and '".$da2."' and iduser='".$data_array['iduser']."' and identity = '$identity'");
			$new_d   = $result4['count'];
			$kol     = $result4['summa'];

			$result5 = $db -> getRow("SELECT COUNT(*) as count, SUM(kol) as summa FROM ".$sqlname."dogovor WHERE datum_izm between '".$da1."' and '".$da2."' and iduser='".$data_array['iduser']."' and identity = '$identity'");
			$izm_d   = $result5['count'];
			$kol_i   = $result5['summa'];

			$result6 = $db -> getRow("SELECT COUNT(*) as count, SUM(kol_fact) as summa FROM ".$sqlname."dogovor WHERE datum_close between '".$da1."' and '".$da2."' and iduser='".$data_array['iduser']."' and identity = '$identity'");
			$cl_d    = $result6['count'];
			$kol_c   = $result6['summa'];
			?>
			<TR height="30" class="ha bordered">

				<TD>
					<DIV class="ellipsis" title="<?= $manpro ?>"><?= $manpro ?></DIV>
				</TD>
				<TD align="center">
					<DIV title="<?= $new_d ?>"><?= $new_d ?></DIV>
				</TD>
				<TD align="right" nowrap>
					<DIV title="<?= $kol ?>"><?= num_format($kol) ?></DIV>
				</TD>
				<TD align="center">
					<DIV title="<?= $izm_d ?>"><?= $izm_d ?></DIV>
				</TD>
				<TD align="right" nowrap>
					<DIV title="<?= $kol_i ?>"><?= num_format($kol_i) ?></DIV>
				</TD>
				<TD align="center">
					<DIV title="<?= $cl_d ?>"><?= $cl_d ?></DIV>
				</TD>
				<TD align="right" nowrap>
					<DIV title="<?= $kol_c ?>"><?= num_format($kol_c) ?></DIV>
				</TD>

			</TR>
			<?php
			/*$all_hist = $all_hist + $hist;
			$all_hist_d = $all_hist_d + $hist_d;*/
			$all_new_d = $all_new_d + $new_d;
			$all_izm_d = $all_izm_d + $izm_d;
			$all_cl_d  = $all_cl_d + $cl_d;
			$all_kol   = $all_kol + pre_format($kol);
			$all_kol_i = $all_kol_i + pre_format($kol_i);
			$all_kol_c = $all_kol_c + pre_format($kol_c);

			$hist   = 0;
			$hist_d = 0;
			$new_d  = 0;
			$izm_d  = 0;
			$cl_d   = 0;
			$kol    = 0;
			$kol_i  = 0;
			$kol_c  = 0;
		}

		$all_kol   = num_format($all_kol);
		$all_kol_i = num_format($all_kol_i);
		$all_kol_c = num_format($all_kol_c);
		?>
		<TR height="30" bgcolor="#FC9">
			<TD align="center"><B>ИТОГО</B></TD>
			<TD align="center"><B><?= $all_new_d ?></B></TD>
			<TD align="right" nowrap><B><?= $all_kol ?></B></TD>
			<TD align="center"><B><?= $all_izm_d ?></B></TD>
			<TD align="right" nowrap><B><?= $all_kol_i ?></B></TD>
			<TD align="center"><B><?= $all_cl_d ?></B></TD>
			<TD align="right" nowrap><B><?= $all_kol_c ?></B></TD>
		</TR>
		</TBODY>
	</TABLE>

</div>

<div style="height:60px"></div>