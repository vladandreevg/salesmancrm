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

$action = $_REQUEST['action'];
$datum  = $_REQUEST['datum'];
$cid    = (int)$_REQUEST['cid'];

if ( $cid == 0 ) {

	$sort = get_people( $iduser1 );

	$result = $db -> query( "select * from ".$sqlname."history WHERE iduser<>'' ".$sort." and tip NOT IN ('СобытиеCRM','ЛогCRM') and identity = '$identity' ORDER BY datum DESC LIMIT 30" );
	?>
	<TABLE id="bborder">
		<?php
		while ($data_array = $db -> fetch( $result )) {

			$user     = current_user( $data_array['iduser'] );
			$client   = current_client( (int)$data_array['clid'] );
			$a_person = (int)$data_array['pid'];
			$des      = str_replace( "\n", "<br>", $data_array['des'] );

			if ( $a_person != '' ) {
				$pers   = explode( ";", $a_person );
				$col    = count( $pers );
				$person = $pers[0] > 0 ? current_person( $pers[0] ) : "";
			}

			$color = $db -> getOne( "SELECT color FROM ".$sqlname."activities WHERE title='".$data_array['tip']."' and identity = '$identity'" );
			if ( $color == "" ) {
				$color = "gray";
			}
			?>
			<TR class="ha th30">
				<TD width="80" align="left" title="<?= $data_array['tip'] ?>" style="color:<?= $color ?>"><?= get_ticon( $data_array['tip'] ) ?>
					<b class="smalltxt">- <?= diffDateTime( $data_array['datum'] ) ?></b>
				</TD>
				<TD>
					<div class="ellipsis" title="<?= untag( str_replace( "<br>", "\n", $des ) ) ?>">
						<a href="javascript:void(0)" onclick="doLoad('/content/vigets/history.php?cid=<?= $data_array['cid'] ?>&action=view')"><?= link_it( trim( $des ) ) ?></a>
					</div>
					<?php if ( (int)$data_array['clid'] > 0 ) { ?>
						<br>
						<div class="ellipsis">
							<a href="javascript:void(0)" onclick="openClient(<?=$data_array['clid']?>)" class="smalltxt">
								<i class="icon-building broun" title="<?= $client ?>"></i><b><?= $client ?></b>
							</a>
						</div>
					<?php } ?>
					<?php if ( (int)$data_array['did'] > 0 ) { ?>
						<br>
						<div class="ellipsis">
							<a href="javascript:void(0)" onclick="openDogovor(<?=$data_array['did']?>)" class="smalltxt">
								<i class="icon-briefcase broun" title="<?= current_dogovor( $data_array['did'] ) ?>"></i><b><?= current_dogovor( $data_array['did'] ) ?></b>
							</a>
						</div>
					<?php } ?>
				</TD>
				<TD width="100">
					<span class="ellipsis" title="<?= $user ?>"><?= $user ?></span>
				</TD>
				<TD width="30" align="right" nowrap>
					<?php if ( (int)$data_array['pid'] > 0 && $col = 1 ) { ?>
						<a href="javascript:void(0)" onclick="openPerson('<?=$data_array['pid']?>')"><i class="icon-user-1 broun" title="<?= $person ?>"></i></a>
					<?php } ?>
				</TD>
			</TR>
		<?php } ?>
	</TABLE>
	<?php

	exit();
}
else {

	$html = $files = '';

	//Найдем задачу, на которую сделана активность
	$result = $db -> getRow( "SELECT * FROM ".$sqlname."tasks WHERE cid = '$cid' and identity = '$identity'" );
	$tid    = (int)$result["tid"];
	$tip    = $result["tip"];

	$data = $db -> getRow( "select * from ".$sqlname."history WHERE cid = '$cid' and identity = '$identity'" );

	$color = $db -> getOne( "SELECT color FROM ".$sqlname."activities WHERE title = '$data[tip]' and identity = '$identity'" );
	if ( $color == "" ) {
		$color = "gray";
	}

	if ( $data['datum'] != '' ) {

		$html .= '
		<div class="flex-container box--child mt10 mb15">
			<div class="flex-string wp20 gray2 fs-12 right-text">Дата:</div>
			<div class="flex-string wp80 fs-12 pl10">'.get_sdate( $data['datum'] ).'</div>
		</div>
		';

	}
	if ( $data['tip'] != '' ) {

		$html .= '
		<div class="flex-container box--child mt10 mb15">
			<div class="flex-string wp20 gray2 fs-12 right-text">Тип:</div>
			<div class="flex-string wp80 fs-12 pl10"><span style="color:'.$color.'">'.get_ticon( $data['tip'] ).' '.$data['tip'].'</span></div>
		</div>
		';

	}
	if ( (int)$data['iduser'] > 0 ) {

		$html .= '
		<div class="flex-container box--child mt10 mb15">
			<div class="flex-string wp20 gray2 fs-12 right-text">Ответственный:</div>
			<div class="flex-string wp80 fs-12 pl10">'.current_user( (int)$data['iduser'] ).'</div>
		</div>
		';

	}
	if ( (int)$data['did'] > 0 ) {

		$html .= '
		<div class="flex-container box--child mt10 mb15">
			<div class="flex-string wp20 gray2 fs-12 right-text">Сделка:</div>
			<div class="flex-string wp80 fs-12 pl10"><A href="javascript:void(0)" onClick="openDogovor(\''.$data['did'].'\')"><i class="icon-briefcase broun"></i>&nbsp;'.current_dogovor( (int)$data['did'] ).'</a></div>
		</div>
		';

	}
	if ( (int)$data['clid'] > 0 ) {

		$html .= '
		<div class="flex-container box--child mt10 mb15">
			<div class="flex-string wp20 gray2 fs-12 right-text">Клиент:</div>
			<div class="flex-string wp80 fs-12 pl10"><A href="javascript:void(0)" onClick="openClient(\''.$data['clid'].'\')"><i class="icon-building broun"></i>&nbsp;'.current_client( (int)$data['clid'] ).'</a></div>
		</div>
		';

	}
	if ( $data['pid'] != '' ) {

		$person = '';
		$pers   = yexplode( ";", $data['pid'] );

		$plist = [];
		foreach ( $pers as $p ) {

			if((int)$p > 0) {

				$plist[] = '<div class="inline"><a href="javascript:void(0)" onclick="openPerson(\''.$p.'\')" title="В новом окне"><i class="icon-user-1 broun"></i>'.current_person( $p ).'</a></div>';

			}

		}

		if ( !empty( $plist ) ) {
			$html .= '
				<div class="flex-container box--child mt10 mb15">
					<div class="flex-string wp20 gray2 fs-12 right-text">Контакты:</div>
					<div class="flex-string wp80 fs-12 pl10">'.yimplode( "; ", $plist ).'</div>
				</div>
			';
		}

	}
	if ( $data['des'] != '' ) {

		$html .= '
		<hr>
		<div class="flex-container box--child mt10 mb15">
			<div class="flex-string wp20 gray2 fs-12 right-text">Описание:</div>
			<div class="flex-string wp80 fs-11 flh-12 pl10"><div class="viewdib bgwhite p10">'.link_it( nl2br( $data['des'] ) ).'</div></div>
		</div>
		';

	}

	$fids = yexplode( ";", $data['fid'] );

	foreach ( $fids as $fid ) {

		$result = $db -> getRow( "select * from ".$sqlname."file WHERE fid = '$fid' and identity = '$identity'" );
		$ftitle = $result["ftitle"];
		$fname  = $result["fname"];

		$files .= '
		<div class="p5 ellipsis1">
			'.(isViewable( $fname ) ? '<A href="javascript:void(0)" onClick="fileDownload(\''.$fid.'\',\'\',\'\')"><i class="icon-eye broun" title="Просмотр"></i></A>&nbsp;' : '').'
			'.get_icon2( $ftitle ).'&nbsp;<A href="javascript:void(0)" onclick="fileDownload(\''.$fid.'\')" title="Скачать"><B>'.$ftitle.'</B></A>&nbsp;['.num_format( filesize( $rootpath."/files/".$fpath.$fname ) / 1000 ).' kb.]
		</div>
		';

	}
	?>
	<DIV class="zagolovok"><B>Просмотр активности</B></DIV>

	<div id="formtabs" class="box--child" style="max-height: 70vh; overflow-y:auto !important; overflow-x:hidden">

		<?= $html ?>

		<?php
		if ( !empty( $files ) ) {
			print '
			<hr>
			<div class="flex-container box--child mt10 mb15">
				<div class="flex-string wp20 gray2 fs-12 right-text">Файлы:</div>
				<div class="flex-string wp80 fs-11 flh-12 pl10"><div class="infodiv">'.$files.'</div></div>
			</div>
			';
		}
		?>

	</div>

	<hr>

	<div class="button--pane text-right">
		<?php
		if ( $tid > 0 ) {
			print '<A href="javascript:void(0)" onclick="viewTask(\''.$tid.'\');" class="button">Посмотреть напоминание</a>';
		}
		?>
		<a href="javascript:void(0)" onclick="DClose();" class="button">Закрыть</a>

	</div>

	<script>
		if (!isMobile) {

			if ($(window).width() > 990) $('#dialog').css({'width': '800px'});
			else $('#dialog').css('width', '90vw');

			$('#formtabs').css('max-height', hh2);

		}
		else {

			var h2 = $(window).height() - $('.zagolovok').actual('outerHeight') - $('.button--pane').actual('outerHeight') - 30;
			$('#formtabs').css({'max-height': h2 + 'px', 'height': h2 + 'px'});

		}
	</script>
	<?php
	exit();
}
?>