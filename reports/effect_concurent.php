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
$top = $_REQUEST['top'];

if ($top == '') {
	$top = 10;
}

$act  = $_REQUEST['act'];
$per  = $_REQUEST['per'];
$coid = $_REQUEST['coid'];

if (!$per) {
	$per = 'nedelya';
}

?>

<div class="relativ mt20 mb20 wp95" align="center">
	<h1 class="uppercase fs-14 m0 mb10">Cделки, выигранные конкурентами</h1>
	<div class="blue">за период&nbsp;с&nbsp;<?= format_date_rus($da1) ?>&nbsp;по&nbsp;<?= format_date_rus($da2) ?></div>
</div>

<hr>

<div class="infodiv">

	&nbsp;<B>Конкурент:</B>&nbsp;

	<div class="select inline">
		<SELECT name="coid" id="coid" class="w250">
			<OPTION value="">Все</OPTION>
			<?php
			$types = $db -> getAll("SELECT clid, title FROM ".$sqlname."clientcat WHERE type = 'concurent' and identity = '$identity' ORDER BY title");
			foreach ($types as $type) {
				?>
				<OPTION <?php if ($coid == $type['clid']) print "selected"; ?> value="<?= $type['clid'] ?>"><?= $type['title'] ?></OPTION>
			<?php } ?>
		</SELECT>
	</div>

</div>

<hr>

<table id="zebra">
	<thead>
	<TR height="50">
		<th width="30" align="center"><B>№ п/п</B></th>
		<th width="200" align="center">Конкурент</th>
		<th width="250" align="center"><B>Заказчик</B></th>
		<th align="center"><B>Сделка</B></th>
		<th width="80" align="center"><B>Дата<BR>закрытия</B></th>
		<th width="100" align="center"><B>Наша цена, <?= $valuta ?></B></th>
		<th width="100" align="center"><B>Цена, <?= $valuta ?></B></th>
		<th width="100" align="center"></th>
	</TR>
	</thead>
	<?php
	$s = '';
	if ($coid > 0) {
		$s = "coid = ".$coid." and";
	}

	$i       = 1;
	$kol_all = 0;

	$resultm = $db -> getAll("SELECT * FROM ".$sqlname."dogovor WHERE ".$s." coid > 0 and datum_close between '$da1' and '$da2' and identity = '$identity' order by coid");
	foreach ($resultm as $data) {

		$dati1  = format_date_rus($data['datum_close']);
		$manpro = current_user($data['iduser']);
		$titlec = current_client($data['coid']);

		$client = ((int)$data['clid'] > 0) ? current_client($data['clid']) : current_person($data['pid']);

		$kol = num_format($data['kol']);

		$kol_ras = $db -> getOne("SELECT co_kol FROM ".$sqlname."dogovor WHERE did = ".$data['did']." and datum_close between '$da1' and '$da2' and identity = '$identity'");

		$kol_all     += pre_format( $kol );
		$kol_all_ras += pre_format( $kol_ras );
		$kol_ras     = num_format($kol_ras);
		?>
		<TR height="50" class="ha bordered">
			<TD align="center"><?= $i ?></TD>
			<TD>
				<DIV class="ellipsis">
					<A href="javascript:void(0);" onClick="openClient('<?= $data['coid'] ?>');" title="Открыть карточку"><?= $titlec ?></a>
				</DIV>
			</TD>
			<TD>
				<DIV class="ellipsis">
					<?php if ($data['clid'] > 0) { ?>
						<A href="javascript:void(0)" onclick="openClient('<?= $data['clid'] ?>')"><b><?= $client ?></b></a>
					<?php } ?>
					<?php if ($data['pid'] > 0) { ?>
						<A href="javascript:void(0)" onclick="openPerson('<?= $data['pid'] ?>')"><b><?= $client ?></b></a>
					<?php } ?>
				</DIV>
			</TD>
			<TD>
				<div class="ellipsis">
					<a href="javascript:void(0);" onclick="openDogovor('<?= $data['did'] ?>')"><?= $data['title'] ?></a>
				</div>
			</TD>
			<TD align="center"><?= $dati1 ?></TD>
			<TD align="right"><?= $kol ?></TD>
			<TD align="right"><?= $kol_ras ?></TD>
			<TD></TD>
		</TR>
		<?php
		$i++;
	}
	$kol_all     = num_format($kol_all);
	$kol_all_ras = num_format($kol_all_ras);
	//}
	?>
	<TR bgcolor="#FC9" height="50">
		<TD colspan="5" align="right" nowrap><B>ВСЕГО:</B></TD>
		<TD align="right" nowrap><B>&nbsp;<?= $kol_all ?></B></TD>
		<TD align="right" nowrap><B><?= $kol_all_ras ?></B></TD>
		<TD></TD>
	</TR>
</TABLE>

