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

if ( $acs_analitics != 'on' ) {
	print "<div class=\"bad\" align=\"center\"><br />Доступ запрещен.<br />Обратитесь к администратору.<br /><br /></div>";
	exit;
}

$da1 = $_REQUEST['da1'];
$da2 = $_REQUEST['da2'];
$da  = $_REQUEST['da'];
$act = $_REQUEST['act'];
$per = $_REQUEST['per'];

$sort = '';

if ( !$per )
	$per = 'nedelya';

$user_list   = (array)$_REQUEST['user_list'];
if ( !empty( $user_list ) ) {
	$sort .= " tsk.iduser IN (".yimplode( ",", $user_list ).") AND ";
}
else {
	$sort .= " tsk.iduser IN (".yimplode( ",", (array)get_people( $iduser1, 'yes' ) ).") AND ";
}

if ( $_REQUEST['action'] == "get_csv" ) {

	$otchet = ["Дата:Время;Тип;Заголовок;Агенда;Результат;Ответственный;Клиент;Ссылка"];
	$query  = "SELECT * FROM ".$sqlname."tasks WHERE datum between '".$da1." 00:00:01' and '".$da2." 23:59:59' and active='no' ".$sort." and identity = '$identity' ORDER BY datum DESC";

	$result = $db -> getAll( $query );
	foreach ( $result as $data ) {

		if ( $data['clid'] > 0 )
			$client = current_client( $data['clid'] );
		elseif ( $data['pid'] > 0 )
			$client = current_person( $data['pid'] );
		else $client = "";
		if ( $data['cid'] > 0 ) {
			$resultt  = $db -> query( "SELECT * FROM ".$sqlname."history WHERE cid='".$data['cid']."' and identity = '$identity'" );
			$desr     = clean( str_replace( ";", ",", $db -> fetchnorm( $resultt, 0, "des" ) ) );
			$datumr   = get_sdate( $db -> fetchnorm( $resultt, 0, "datum" ) );
			$rezultat = $datumr.' : '.$desr;
		}
		//создаем массив строк отчета
		$client = "";
		$url    = "";
		if ( $data['clid'] > 0 ) {
			$client = current_client( $data['clid'] );
			$url    = "http://".$_SERVER['HTTP_HOST']."/card.client.php?clid=".$data['clid'];
		}
		if ( $data['pid'] > 0 ) {
			$client = current_person( $data['pid'] );
			$url    = "http://".$_SERVER['HTTP_HOST']."/card.person.php?pid=".$data['pid'];
		}

		$otchet[] = get_sdate( $data['datum']." ".$data['totime'] ).";".str_replace( ";", ",", $data['tip'] ).";".str_replace( ";", ",", $data['title'] ).";".str_replace( ";", ",", $data['des'] ).";".$rezultat.";".str_replace( ";", ",", current_user( $data['iduser'] ) ).";".$client.";".$url;

	}
	//создаем файл csv
	$filename = 'export_rezultat.csv';
	$handle   = fopen( "../files/".$filename, 'w' );
	for ( $g = 0; $g < count( $otchet ); $g++ ) {
		$otchet[ $g ] = iconv( "UTF-8", "CP1251", $otchet[ $g ] );
		fwrite( $handle, "$otchet[$g]\n" );
	}
	fclose( $handle );
	header( 'Content-type: application/csv' );
	header( 'Content-Disposition: attachment; filename="'.$filename.'"' );

	readfile( "../files/".$filename );
	unlink( "../files/".$filename );
	exit();
}
?>
<div class="zagolovok_rep text-center">
	<h1>Результаты выполнения дел</h1>
	<div class="fs-09 gray">
		за период&nbsp;с&nbsp;<?= $da1 ?>&nbsp;по&nbsp;<?= $da2 ?> (<a href="javascript:void(0)" onclick="generate_csv()">Скачать CSV</a>)
	</div>
</div>

<hr>

<TABLE id="zebra" class="top">
	<thead class="sticked--top">
	<TR class="header_contaner text-center">
		<th class="w100"><b>Дата</b></th>
		<th class="w120"><b>Тип</b></th>
		<th class="w250"><B>Агенда</B></th>
		<th><b>Результат</b></th>
		<th class="w160"><B>Ответственный</B></th>
		<th class="w250"><B>Клиент</B></th>
	</TR>
	</thead>
	<?php
	$result = $db -> getAll( "
		SELECT * 
		FROM ".$sqlname."tasks `tsk` 
		WHERE 
			tsk.datum between '$da1 00:00:01' and '$da2 23:59:59' and 
			tsk.active='no' and 
			$sort 
			tsk.identity = '$identity' 
		ORDER BY datum DESC" );
	foreach ( $result as $data ) {

		$manpro = $db -> getOne( "SELECT title FROM ".$sqlname."user WHERE iduser='".$data['iduser']."' and identity = '$identity'" );

		if ( $data['clid'] > 0 )
			$client = current_client( $data['clid'] );
		if ( $data['pid'] > 0 )
			$person = current_person( $data['pid'] );
		if ( $data['cid'] > 0 ) {

			$resultt = $db -> getRow( "SELECT des, datum FROM ".$sqlname."history WHERE cid='".$data['cid']."' and identity = '$identity'" );
			$desr    = $resultt["des"];
			$datumr  = $resultt["datum"];

			$rezultat = '<div class="ellipsis" title="'.$datumr.': '.$desr.'"><b>'.$datumr.'</b> &rarr; '.$desr.'</b></div>';

		}
		?>
		<TR class="ha bordered">
			<TD><b><?= get_sdate( $data['datum'].' '.$data['totime'] ) ?></b></TD>
			<TD nowrap="nowrap"><?= $data['tip'] ?></TD>
			<TD><?= get_priority( 'priority', $data['priority'] ).get_priority( 'speed', $data['speed'] ) ?>&nbsp;<A href="javascript:void(0)" onclick="viewTask('<?= $data['tid'] ?>');"><strong><?= $data['title'] ?></strong></A><br/><?= mb_strimwidth( $data['des'], 0, 1000, "..>" ) ?>
			</TD>
			<TD><?= $rezultat ?></TD>
			<TD><div class="ellipsis"><?= $manpro ?></div></TD>
			<TD>
				<?php if ( $data['clid'] > 0 ) { ?>
					<div class="ellipsis" title="<?= $client ?>">
						<a onclick="viewClient('<?= $data['clid'] ?>&action=view')" href="javascript:void(0)"><i class="icon-building broun"></i>&nbsp;<b><?= $client ?></b></a>
					</div>
				<?php } ?>
				<?php if ( $data['pid'] > 0 ) { ?>
					<br>
					<div class="ellipsis" title="<?= $person ?>">
						<A href="javascript:void(0)" onclick="viewPerson('<?= $data['pid'] ?>')"><i class="icon-user-1 blue"></i>&nbsp;<?= $person ?></A>
					</div>
				<?php } ?>
			</TD>
		</TR>
		<?php
		$rezultat = '';
	} ?>
</TABLE>
<div style="height: 90px;"></div>