<?php
/* ============================ */
/*         SalesMan CRM         */
/* ============================ */
/* (C) 2016 Vladislav Andreev   */
/*       SalesMan Project       */
/*        www.isaler.ru         */
/*        ver. 2017.x           */
/* ============================ */

error_reporting( E_ERROR );
header( "Pragma: no-cache" );

$rootpath = dirname(__DIR__, 2);

include $rootpath."/inc/config.php";
include $rootpath."/inc/dbconnector.php";
include $rootpath."/inc/auth.php";
include $rootpath."/inc/func.php";
include $rootpath."/inc/settings.php";
include $rootpath."/inc/language/".$language.".php";

$thisfile = basename( __FILE__ );

$dname  = [];
$result = $db -> query( "SELECT * FROM ".$sqlname."field WHERE fld_tip='dogovor' AND fld_on='yes' and identity = '$identity' ORDER BY fld_order" );
while ($data = $db -> fetch( $result )) {
	$dname[ $data['fld_name'] ] = $data['fld_title'];
}

$year = $_REQUEST['year'];
if ( $year == "" )
	$year = date( 'Y' );

$iduser     = $_REQUEST['iduser'];
$users      = $_REQUEST['user'];
$action     = $_REQUEST['action'];
$view       = $_REQUEST['view'];
$onlyactive = $_REQUEST['onlyactive'];

if ( $view == '' )
	$changeview = 'org';
if ( $view == 'org' )
	$changeview = '';

$ss = ($onlyactive == 'yes') ? " and secrty = 'yes'" : "";

if ( !empty( $users ) && $view == 'org' )
	$ss .= " and iduser IN (".implode( ",", $users ).")";

$y1 = $year + 1;
$y2 = $year - 1;

$width = $_COOKIE['width'];

if ( $width > 1500 ) {
	$w1 = 150;
	$w2 = ($width * 0.7 - $w1 - 60) / 12;
}
else {
	$w1 = 120;
	$w2 = 60;
}

//Данные сотрудника
$u     = $db -> getRow( "SELECT * FROM {$sqlname}user WHERE iduser = '$iduser1' AND identity = '$identity'" );
$prava = (array)yexplode( ";", (string)$user['acs_import'] );

?>
<TABLE id="list_header" class="salesplan">
	<thead class="sticked--top">
	<TR class="header_contaner">
		<TH class="w<?= $w1 ?> text-left" id="firstt">
			<DIV class="ellipsis">Сотрудник</DIV>
		</TH>
		<?php for ( $m = 1; $m <= 12; $m++ ) { ?>
			<TH class="mounth text-left">
				<DIV class="ellipsis"><b><?= ru_month( $m ) ?>.</b></DIV>
			</TH>
		<?php } ?>
		<TH class="w60">&nbsp;</TH>
	</TR>
	</thead>
	<tbody>
	<!--//-->
	<?php
	if ( $view == '' ) {

		$result = $db -> query( "SELECT * FROM ".$sqlname."user WHERE tip='Руководитель организации' and identity = '$identity' $ss ORDER BY bid, mid" );
		while ($data = $db -> fetch( $result )) {

			if ( $data['tip'] == 'Руководитель организации' )
				$otdel = 'План по компании';
			if ( $data['tip'] == 'Руководитель подразделения' )
				$otdel = 'План по подразделению';
			if ( $data['tip'] == 'Руководитель отдела' )
				$otdel = 'План по отделу';
			if ( $data['tip'] == 'Менеджер продаж' )
				$otdel = 'План по сотруднику';

			$userlist = str_replace( ';', ',', get_userlist( $data['iduser'] ) );

			$coloor2 = 'redbg-sub';
			$cls     = '';

			if ( $data['secrty'] == 'no' ) {
				$cls     = '<i class="icon-lock red" title="Доступ закрыт"></i>';
				$coloor2 = 'graybg-dark';
			}

			$edit = $u['acs_plan'] == 'on' && (str_contains($tipuser, 'Руководитель') || $isadmin == 'on') && in_array($data['iduser'], get_people($iduser1, 'yes'));
			?>
			<tr class="th40 redbg-dark">
				<td colspan="14"><span><b><?= $otdel ?></b></span></td>
			</tr>
			<TR class="th40 ha <?= $coloor2 ?>">
				<TD class="w<?= $w1 ?>">
					<DIV title="<?= $data['tip'] ?>" class="ellipsis">
						<a href="javascript:void(0)" onclick="viewUser('<?= $data['iduser'] ?>');"><?= $cls ?><?= $data['title'] ?></a>
					</DIV>
				</TD>
				<?php
				for ( $m = 1; $m <= 12; $m++ ) {

					$dat = $year."-".$m."-01";

					$result2        = $db -> getRow( "SELECT SUM(replace(replace(kol_plan,',',''),' ','')) as kol_all, SUM(replace(replace(marga,',',''),' ','')) as marga_all FROM ".$sqlname."plan WHERE iduser = '".$data['iduser']."' and year='".$year."' and mon='".$m."' and identity = '$identity'" );
					$kol_plan[ $m ] = num_format( $result2["kol_all"] );
					$marga[ $m ]    = num_format( $result2["marga_all"] );

					?>
					<TD class="mounth">
						<DIV class="ellipsis black" title="<?= $dname['oborot'] ?>: <?= $kol_plan[ $m ] ?>"><?= $kol_plan[ $m ] ?></DIV>
						<br>
						<DIV class="ellipsis blue" title="<?= $dname['marg'] ?>: <?= $marga[ $m ] ?>"><?= $marga[ $m ] ?></DIV>
					</TD>
				<?php } ?>
				<TD class="w60">
					<?php
					if($edit){
					?>
					<A href="javascript:void(0)" onclick="editPlan('<?= $data['iduser'] ?>','edit');" title="Редактировать"><i class="icon-pencil broun"></i></A>
					<?php } ?>
				</TD>
			</TR>
			<!--/Подразделение/-->
			<?php
			$result_1 = $db -> query( "SELECT * FROM ".$sqlname."user WHERE mid='".$data['iduser']."' and tip!='Поддержка продаж' $ss and acs_plan = 'on' and identity = '$identity' ORDER BY bid, mid" );
			while ($data0 = $db -> fetch( $result_1 )) {

				if ( $data0['tip'] == 'Руководитель организации' ) {
					$otdel   = 'План по компании';
					$coloor  = 'redbg';
					$coloor2 = 'bgwhite';
				}
				if ( $data0['tip'] == 'Руководитель подразделения' ) {
					$otdel   = 'План по подразделению';
					$coloor  = 'bluebg';
					$coloor2 = 'graybg-sub';
				}
				if ( $data0['tip'] == 'Руководитель отдела' ) {
					$otdel   = 'План по отделу';
					$coloor  = 'greenbg';
					$coloor2 = 'greenbg-sub';
				}
				if ( $data0['tip'] == 'Менеджер продаж' ) {
					$otdel   = 'План по сотруднику';
					$coloor  = 'orangebg-dark';
					$coloor2 = 'bgwhite';
				}

				$userlist = str_replace( ';', ',', get_userlist( $data0['iduser'] ) );
				$cls0     = '';

				if ( $data0['secrty'] == 'no' ) {
					$cls0    = '<i class="icon-lock red" title="Доступ закрыт"></i>';
					$coloor2 = 'graybg-dark';
				}

				$edit = $u['acs_plan'] == 'on' && (str_contains($tipuser, 'Руководитель') || $isadmin == 'on') && in_array($data0['iduser'], get_people($iduser1, 'yes'));
				?>
				<tr class="<?= $coloor ?> th40">
					<td colspan="14">
						<span><b><?= $otdel ?></b>, <?= $data0['tip'] ?> <b><?= $data0['title'] ?></b></span>
					</td>
				</tr>
				<TR class="<?= $coloor2 ?> th40">
					<TD class="w<?= $w1 ?>">
						<DIV title="<?= $data0['tip'] ?>" class="ellipsis">
							<a href="javascript:void(0)" onclick="viewUser('<?= $data0['iduser'] ?>');"><?= $cls0 ?><?= $data0['title'] ?></a>
						</DIV>
					</TD>
					<?php
					for ( $m = 1; $m <= 12; $m++ ) {

						$dat = $year."-".$m."-01";

						$result2        = $db -> getRow( "SELECT SUM(replace(replace(kol_plan,',',''),' ','')) as kol_all, SUM(replace(replace(marga,',',''),' ','')) as marga_all FROM ".$sqlname."plan WHERE iduser = '".$data0['iduser']."' and year='".$year."' and mon='".$m."' and identity = '$identity'" );
						$kol_plan[ $m ] = num_format( $result2["kol_all"] );
						$marga[ $m ]    = num_format( $result2["marga_all"] );
						?>
						<TD class="mounth">
							<DIV class="ellipsis black" title="<?= $dname['oborot'] ?>: <?= $kol_plan[ $m ] ?>"><?= $kol_plan[ $m ] ?></DIV>
							<br/>
							<DIV class="ellipsis blue" title="<?= $dname['marg'] ?>: <?= $marga[ $m ] ?>"><?= $marga[ $m ] ?></DIV>
						</TD>
					<?php } ?>
					<TD class="w60">
						<?php
						if($edit){
						?>
						<A href="javascript:void(0)" onclick="editPlan('<?= $data0['iduser'] ?>','edit');" title="Редактировать"><i class="icon-pencil broun"></i></A>
						<?php } ?>
					</TD>
				</TR>
				<!--/Отдел/-->
				<?php
				$result_2 = $db -> query( "SELECT * FROM ".$sqlname."user WHERE mid='".$data0['iduser']."' and tip!='Поддержка продаж' $ss and acs_plan = 'on' and identity = '$identity' ORDER BY bid, mid" );
				while ($data2 = $db -> fetch( $result_2 )) {

					if ( $data2['tip'] == 'Руководитель организации' ) {
						$otdel   = 'План по компании';
						$coloor  = 'redbg';
						$coloor2 = 'bgwhite';
					}
					if ( $data2['tip'] == 'Руководитель подразделения' ) {
						$otdel   = 'План по подразделению';
						$coloor  = 'bluebg';
						$coloor2 = 'graybg-sub';
					}
					if ( $data2['tip'] == 'Руководитель отдела' ) {
						$otdel   = 'План по отделу';
						$coloor  = 'greenbg';
						$coloor2 = 'greenbg-sub';
					}
					if ( $data2['tip'] == 'Менеджер продаж' ) {
						$otdel   = 'План по сотруднику';
						$coloor  = 'orangebg-dark';
						$coloor2 = 'bgwhite';
					}

					$userlist2 = str_replace( ';', ',', get_userlist( $data2['iduser'] ) );
					$coloor2   = 'bgwhite';

					if ( $data2['secrty'] == 'no' ) {
						$cls2    = '<i class="icon-lock red" title="Доступ закрыт"></i>';
						$coloor2 = 'graybg-dark';
					}

					$edit = $u['acs_plan'] == 'on' && (str_contains($tipuser, 'Руководитель') || $isadmin == 'on') && in_array($data2['iduser'], get_people($iduser1, 'yes'));
					?>
					<tr class="th40 <?= $coloor ?>">
						<td colspan="14">
							<span><b><?= $otdel ?></b>, <?= $data2['tip'] ?> <b><?= $data2['title'] ?></b></span>
						</td>
					</tr>
					<TR class="th40 <?= $coloor2 ?>">
						<TD class="w<?= $w1 ?>">
							<DIV title="<?= $data2['tip'] ?>" class="ellipsis">
								<a href="javascript:void(0)" onclick="viewUser('<?= $data2['iduser'] ?>');"><?= $cls2 ?><?= $data2['title'] ?></a>
							</DIV>
						</TD>
						<?php
						for ( $m = 1; $m <= 12; $m++ ) {

							$dat = $year."-".$m."-01";

							$result2        = $db -> getRow( "SELECT SUM(replace(replace(kol_plan,',',''),' ','')) as kol_all, SUM(replace(replace(marga,',',''),' ','')) as marga_all FROM ".$sqlname."plan WHERE iduser = '".$data2['iduser']."' and year='".$year."' and mon='".$m."' and identity = '$identity'" );
							$kol_plan[ $m ] = num_format( $result2["kol_all"] );
							$marga[ $m ]    = num_format( $result2["marga_all"] );
							?>
							<TD class="mounth">
								<DIV class="ellipsis black" title="<?= $dname['oborot'] ?>: <?= $kol_plan[ $m ] ?>"><?= $kol_plan[ $m ] ?></DIV>
								<br/>
								<DIV class="ellipsis blue" title="<?= $dname['marg'] ?>: <?= $marga[ $m ] ?>"><?= $marga[ $m ] ?></DIV>
							</TD>
						<?php } ?>
						<TD class="w60">
							<?php
							if($edit){
							?>
							<A href="javascript:void(0)" onclick="editPlan('<?= $data2['iduser'] ?>','edit');" title="Редактировать"><i class="icon-pencil broun"></i></A>
							<?php } ?>
						</TD>
					</TR>
					<!--/Сотрудники отдела/-->
					<?php
					$result_3 = $db -> query( "SELECT * FROM ".$sqlname."user WHERE tip='Менеджер продаж' and mid='".$data2['iduser']."' $ss and acs_plan = 'on' and identity = '$identity' ORDER BY bid, mid" );
					while ($data3 = $db -> fetch( $result_3 )) {

						$userlist3 = str_replace( ';', ',', get_userlist( $data3['iduser'] ) );

						$cls3 = '';
						$bg3  = 'bgwhite';

						if ( $data3['secrty'] == 'no' ) {
							$cls3 = '<i class="icon-lock red" title="Доступ закрыт"></i>';
							$bg3  = 'graybg-dark';
						}

						$edit = $u['acs_plan'] == 'on' && (str_contains($tipuser, 'Руководитель') || $isadmin == 'on') && in_array($data3['iduser'], get_people($iduser1, 'yes'));
						?>
						<TR class="<?= $bg3 ?> th40">
							<TD class="w<?= $w1 ?>">
								<DIV title="<?= $data3['tip'] ?>" class="ellipsis">
									<a href="javascript:void(0)" onclick="viewUser('<?= $data3['iduser'] ?>');">
										<div class="strelka w20 mr10"></div>&nbsp;<?= $cls3 ?><?= $data3['title'] ?>
									</a>
								</DIV>
							</TD>
							<?php
							for ( $m = 1; $m <= 12; $m++ ) {
								$dat            = $year."-".$m."-01";
								$result2        = $db -> getRow( "SELECT SUM(replace(replace(kol_plan,',',''),' ','')) as kol_all, SUM(replace(replace(marga,',',''),' ','')) as marga_all FROM ".$sqlname."plan WHERE iduser = '".$data3['iduser']."' and year='".$year."' and mon='".$m."' and identity = '$identity'" );
								$kol_plan[ $m ] = num_format( $result2["kol_all"] );
								$marga[ $m ]    = num_format( $result2["marga_all"] );
								?>
								<TD class="mounth">
									<DIV class="ellipsis black" title="<?= $dname['oborot'] ?>: <?= $kol_plan[ $m ] ?>"><?= $kol_plan[ $m ] ?></DIV>
									<br/>
									<DIV class="ellipsis blue" title="<?= $dname['marg'] ?>: <?= $marga[ $m ] ?>"><?= $marga[ $m ] ?></DIV>
								</TD>
							<?php } ?>
							<TD class="w60">
								<?php
								if($edit){
								?>
								<A href="javascript:void(0)" onclick="editPlan('<?= $data3['iduser'] ?>','edit');" title="Редактировать"><i class="icon-pencil broun"></i></A>
								<?php } ?>
							</TD>
						</TR>
					<?php }
					?>
					<!--/Сотрудники отдела/-->
				<?php } ?>
				<!--/Отдел/-->
			<?php } ?>
			<!--/Подразделение/-->
		<?php } ?>
		<?php
	}
	?>
	<!--//-->
	<?php

	if ( $view == 'org' ) {

		$sort   = get_people( $iduser1 );
		$result = $db -> query( "SELECT * FROM ".$sqlname."user WHERE iduser>0 $sort and tip!='Поддержка продаж' $ss and acs_plan = 'on' and identity = '$identity' ORDER BY title" );
		while ($data = $db -> fetch( $result )) {

			if ( $data['tip'] == 'Руководитель организации' )
				$coloor2 = 'redbg';

			if ( $data['tip'] == 'Руководитель подразделения' )
				$coloor2 = 'bluebg';

			if ( $data['tip'] == 'Руководитель отдела' )
				$coloor2 = 'greenbg';

			if ( $data['tip'] == 'Менеджер продаж' )
				$coloor2 = 'orangebg-dark';

			$edit = $u['acs_plan'] == 'on' && (str_contains($tipuser, 'Руководитель') || $isadmin == 'on') && in_array($data['iduser'], get_people($iduser1, 'yes'));
			?>
			<TR class="th40 ha">
				<TD class="w<?= $w1 ?>">
					<DIV title="<?= $data['tip'] ?>" class="ellipsis">
						<a href="javascript:void(0)" onclick="viewUser('<?= $data['iduser'] ?>');"><b><?= $data['title'] ?></b></a>
					</DIV>
					<br>
					<DIV title="<?= $data['tip'] ?>" class="ellipsis"><?= $data['tip'] ?></DIV>
				</TD>
				<?php
				for ( $m = 1; $m <= 12; $m++ ) {

					$dat = $year."-".$m."-01";

					$result2  = $db -> getRow( "SELECT * FROM ".$sqlname."plan WHERE iduser = '".$data['iduser']."' and year = '$year' and mon = '$m' and identity = '$identity'" );
					$kol_plan = num_format( $result2["kol_plan"] );
					$marga    = num_format( pre_format( $result2["marga"] ) );
					?>
					<TD class="mounth">
						<DIV class="ellipsis black" title="<?= $dname['oborot'] ?>: <?= $kol_plan ?>"><?= $kol_plan ?></DIV>
						<br>
						<DIV class="ellipsis blue" title="<?= $dname['marg'] ?>: <?= $marga ?>"><?= $marga ?></DIV>
					</TD>
				<?php } ?>
				<TD class="w60">
					<?php
					if($edit){
					?>
					<A href="javascript:void(0)" onclick="editPlan('<?= $data['iduser'] ?>','edit');" title="Редактировать"><i class="icon-pencil broun"></i></A>
					<?php } ?>
				</TD>
			</TR>
			<?php
		}
	}
	?>
	</tbody>
</TABLE>
<div class="space-80"></div>