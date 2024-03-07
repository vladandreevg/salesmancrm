<?php
/* ============================ */
/* (C) 2016 Vladislav Andreev   */
/*       SalesMan Project       */
/*        www.isaler.ru         */
/*        ver. 2017.x           */
/* ============================ */
?>
<?php
error_reporting( 0 );
header( "Pragma: no-cache" );

$rootpath = realpath( __DIR__.'/../../' );

include $rootpath."/inc/config.php";
include $rootpath."/inc/dbconnector.php";
include $rootpath."/inc/auth.php";
include $rootpath."/inc/func.php";
include $rootpath."/inc/settings.php";
include $rootpath."/inc/language/".$language.".php";

$fid    = $_REQUEST[ 'fid' ];
$action = $_REQUEST[ 'action' ];

$result     = $db -> getRow( "select * from ".$sqlname."file where fid='".$fid."' and identity = '$identity'" );
$ftitle     = $result[ "ftitle" ];
$fname      = $result[ "fname" ];
$fver       = $result[ "fver" ];
$idcategory = $result[ "folder" ];
$ftag       = $result[ "ftag" ];
$clid       = $result[ "clid" ];
$pid        = $result[ "pid" ];
$did        = $result[ "did" ];
$tskid      = $result[ "tskid" ];
$coid       = $result[ "coid" ];
$iduser     = $result[ "iduser" ];

$size = num_format( filesize( $rootpath."/files/".$fpath.$fname ) / 1000 );
//$icon = get_icon($ftitle);

$result2 = $db -> getRow( "SELECT * FROM ".$sqlname."file_cat WHERE idcategory='".$idcategory."' and identity = '$identity'" );
$folder  = $result2[ "title" ];
$shared  = $result2[ "shared" ];

$img = '<i class="icon-folder broun"></i>';
$url = "";
if ( $clid > 0 ) {

	$roditel  = current_client( $clid );
	$url      = "card.client.php?clid=".$clid."#6";
	$url_load = "viewClient('".$clid."')";
	$img      = '<i class="icon-commerical-building blue"></i>';
	$type     = "Организация";

}
if ( $pid > 0 ) {

	$roditel  = current_person( $pid );
	$url      = "card.person.php?pid=".$pid."#6";
	$url_load = "viewPerson('".$pid."')";
	$img      = '<i class="icon-user-1 blue"></i>';
	$type     = "Персона";

}
if ( $did > 0 ) {

	$roditel  = current_dogovor( $did );
	$url      = "card.deal.php?did=".$did."#6";
	$url_load = "viewDogovor('".$did."')";
	$img      = '<i class="icon-briefcase broun"></i>';
	$type     = "Сделка";

}
?>
<DIV class="zagolovok">Информация о файле</DIV>
<table width="99%" border="0" cellspacing="2" cellpadding="2" id="bborder">
	<tr>
		<td width="70" rowspan="17" align="center" valign="top" nowrap="nowrap" class="header2">
			<span class="standard"><i class="icon-folder icon-5x broun"></i></span></td>
		<td width="110" nowrap="nowrap">&nbsp;Ответственный:&nbsp;</td>
		<td>
			<?php
			$usertitle = current_user( $iduser );
			if ( $iduser == 0 ) $usertitle = 'Не определено';
			?>
			<b style="color:#F00"><?= $usertitle; ?></b></td>
	</tr>
	<?php
	if ( $ftitle != '' ) { ?>
		<tr>
			<td width="110" valign="top" nowrap="nowrap">&nbsp;Название:&nbsp;</td>
			<td><?= get_icon2( $ftitle ) ?>&nbsp;<b><?= $ftitle ?></b>&nbsp;</td>
		</tr>
		<tr>
			<td valign="top" nowrap="nowrap">&nbsp;Описание:</td>
			<td><?= $ftag ?>&nbsp;</td>
		</tr>
	<?php } ?>
	<?php if ( $idcategory != "" ) { ?>
		<tr>
			<td width="110" nowrap="nowrap">&nbsp;Папка:&nbsp;</td>
			<td><i class="icon-folder blue"></i><?= $folder ?><?php if ( $shared == 'yes' ) print " - Общая папка"; ?>
			</td>
		</tr>
	<? } ?>
	<?php if ( $size != '' ) { ?>
		<tr>
			<td width="110" nowrap="nowrap">&nbsp;Размер:&nbsp;</td>
			<td><b><?= $size ?></b>&nbsp;kb</td>
		</tr>
	<? } ?>
	<?php if ( $roditel != "" ) { ?>
		<tr>
			<td width="110" nowrap="nowrap">&nbsp;Родитель:&nbsp;</td>
			<td>
				<?php if ( $url != "" ){ ?>
				<B><A href="<?= $url ?>" target="_blank" title="Открыть в новом окне"><?= $img ?></A>
					<?php } else { ?><?= $img ?><?php } ?>&nbsp;&nbsp;</B>&nbsp;<A href="#" onClick="<?= $url_load ?>"><?= $roditel ?></A>
			</td>
		</tr>
	<? } ?>
</table>