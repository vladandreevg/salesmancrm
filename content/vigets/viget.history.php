<?php
/* ============================ */
/*         SalesMan CRM         */
/* ============================ */
/* (C) 2018 Vladislav Andreev   */
/*       SalesMan Project       */
/*        www.isaler.ru         */
/*        ver. 2018.x           */
/* ============================ */

error_reporting( E_ERROR );
header( "Pragma: no-cache" );

$rootpath = dirname( __DIR__, 2 );

include $rootpath."/inc/config.php";
include $rootpath."/inc/dbconnector.php";
include $rootpath."/inc/auth.php";
include $rootpath."/inc/func.php";
include $rootpath."/inc/settings.php";
include $rootpath."/inc/language/".$language.".php";

$thisfile = basename( __FILE__ );

$action = $_REQUEST['action'];

$d1 = current_datum( 7 );
$d2 = current_datum();

if ( $action == '' ) {

	$q = "
	SELECT
		DISTINCT({$sqlname}history.cid) as cid,
		{$sqlname}history.datum as datum,
		{$sqlname}history.clid as clid,
		{$sqlname}history.pid as pid,
		{$sqlname}history.did as did,
		{$sqlname}history.iduser as iduser,
		SUBSTRING({$sqlname}history.des, 1, 101) as des,
		{$sqlname}history.tip as tip
	FROM {$sqlname}history
	WHERE
		{$sqlname}history.iduser > 0 and
		{$sqlname}history.iduser IN (".yimplode( ",", get_people( $iduser1, "yes" ) ).") and
		{$sqlname}history.tip NOT IN ('СобытиеCRM','ЛогCRM') and
		{$sqlname}history.identity = '$identity'
		GROUP BY {$sqlname}history.cid
		ORDER BY {$sqlname}history.datum DESC LIMIT 30
	";

	$result = $db -> query( $q );
	?>
	<TABLE id="bborder" class="top">
		<thead class="hidden">
		<tr>
			<th class="w60">Тип</th>
			<th>Содержание</th>
			<th class="w80">Автор</th>
		</tr>
		</thead>
		<tbody>
		<?php
		while ($da = $db -> fetch( $result )) {

			$person = '';
			$client = '';

			$user   = current_user( $da['iduser'] );
			$client = current_client( $da['clid'] );

			$des = str_replace( "\n", " ", $da['des'] );
			$des = mb_substr( clean( $des ), 0, 101, 'utf-8' );

			$pers = yexplode( ";", (string)$da['pid'], 0 );
			if ( $pers > 0 )
				$person = current_person( (int)$pers );

			$color = $db -> getOne( "SELECT color FROM {$sqlname}activities WHERE title = '$da[tip]' AND identity = '$identity'" );
			if ( $color == "" )
				$color = "gray";

			?>
			<TR class="ha th30">
				<TD class="w60 text-center" title="<?= $da['tip'] ?>">
					<span style="color:<?= $color ?>"><?= get_ticon( $da['tip'] ) ?></span>
					<div class="smalltxt"><?= diffDateTime( $da['datum'] ) ?></div>
				</TD>
				<TD>
					<div class="ellipsis margbot5" title="<?= untag( str_replace( "<br>", "\n", $des ) ) ?>">
						<a href="javascript:void(0)" onClick="viewHistory('<?= $da['cid'] ?>')"><?= link_it( trim( $des ) ) ?></a>
					</div>
					<?php if ( $da['clid'] > 0 ) { ?>
						<br>
						<div class="ellipsis">
							<a href="javascript:void(0)" onclick="openClient('<?= $da['clid'] ?>')" class="smalltxt"><i class="icon-building broun" title="<?= $client ?>"></i><b><?= $client ?></b></a>
						</div>
					<?php } ?>
					<?php if ( $person != '' ) { ?><br>
						<div class="ellipsis smalltxt">
						<a href="javascript:void(0)" onclick="openPerson('<?= $da['pid'] ?>')"><i class="icon-user-1 blue" title=""></i><?= $person ?>
						</a></div><?php } ?>
					<?php if ( $da['did'] > 0 ) { ?>
						<br>
						<div class="ellipsis">
							<a href="javascript:void(0)" onclick="openDogovor('<?= $da['did'] ?>')" class="smalltxt"><i class="icon-briefcase broun" title="<?= $da['dogovor'] ?>"></i><b><?= current_dogovor( $da['did'] ) ?></b></a>
						</div>
					<?php } ?>
					<br>
				</TD>
				<td class="w80">
					<span class="ellipsis smalltxt1" title="<?= $user ?>"><i class="icon-user-1 blue"></i><?= $user ?></span>
				</td>
			</TR>
			<?php
		}
		?>
		</tbody>
	</TABLE>

	<?php
}

if ( $action == 'view' ) {

	$cid = $_REQUEST['cid'];

	$client = $dog = $person = '';

	//Найдем задачу, на которую сделана активность
	$tid     = $db -> getOne( "SELECT tid FROM {$sqlname}tasks WHERE cid='".$cid."' and identity = '$identity'" );
	$history = $db -> getRow( "select * from {$sqlname}history WHERE cid='".$cid."' and identity = '$identity'" );

	if ( $history['did'] > 0 ) {

		$dog = 'Сделка:&nbsp;<b><A href="javascript:void(0)" onClick="viewDogovor(\''.$history['did'].'\')">'.current_dogovor( $history['did'] ).'</a></b>&nbsp;&nbsp;<A href="javascript:void(0)" onclick="openDogovor(\''.$history['did'].'\')"><i class="icon-briefcase broun"></i></A><br />';

	}

	if ( $history['clid'] > 0 ) {

		$client = 'Клиент:&nbsp;<b><A href="javascript:void(0)" onClick="viewClient(\''.$history['clid'].'\')">'.current_client( $history['clid'] ).'</a></b>&nbsp;&nbsp;<A href="javascript:void(0)" onclick="openClient(\''.$history['clid'].'\')"><i class="icon-building broun"></i></A><br />';

	}

	if ( $history['pid'] > 0 ) {

		$pers = explode( ";", $history['pid'] );

		foreach ($pers as $per) {

			$person .= 'Персона:&nbsp;<b><A href="javascript:void(0)" onClick="viewPerson(\''.$per.'\')">'.current_person( $per ).'</a></b>&nbsp;&nbsp;<A href="javascript:void(0)" onclick="openPerson(\''.$per.'\')"><i class="icon-user-1 broun"></i></A><br>';
		}

	}

	$des = link_it( str_replace( "\n", "<br>", $history['des'] ) );

	?>
	<DIV class="zagolovok"><B>Просмотр активности</B></DIV>

	<hr>

	<TABLE id="noborder">
		<TR>
			<TD width="20" align="center" valign="top" nowrap><?= get_ticon( $history['tip'] ) ?></TD>
			<TD>
				<DIV style="max-height: 250px; height:250px; overflow: auto">
					Добавил:&nbsp;<B><?= current_user( $history['iduser'] ) ?></B>&nbsp;
					<i class="green"><?= get_sdate( $history['datum'] ) ?></i>
					<?php if ( $dog != '' or $person != '' or $history['clid'] > 0 )
						print "<hr>"; ?>
					<?= $dog ?>
					<?= $client ?>
					<?= $person ?>
					<hr>
					<?= link_it( str_replace( "\n", "<br>", $history['des'] ) ) ?>
				</DIV>
			</TD>
		</TR>
	</TABLE>

	<?php
	if ( $tid ) {
		?>
		<hr>
		<div align="right">
			<A href="javascript:void(0)" onClick="viewTask('<?= $tid ?>');" class="button">Посмотреть напоминание</a>
		</div>
	<?php } ?>
	<script>
		$('#dialog').width('600px');
	</script>
	<?php
}