<?php
/* ============================ */
/*         SalesMan CRM         */
/* ============================ */
/* (C) 2016 Vladislav Andreev   */
/*       SalesMan Project       */
/*        www.isaler.ru         */
/*        ver. 2017.x           */
/* ============================ */
?>
<?php
error_reporting( E_ERROR );

header( "Pragma: no-cache" );

$rootpath = realpath( __DIR__.'/../../' );

include $rootpath."/inc/config.php";
include $rootpath."/inc/dbconnector.php";
include $rootpath."/inc/auth.php";
include $rootpath."/inc/func.php";
include $rootpath."/inc/settings.php";
include $rootpath."/inc/language/".$language.".php";

$thisfile = basename( __FILE__ );

$pid    = (int)$_REQUEST['pid'];
$action = $_REQUEST['action'];

if ( $acs_prava != 'on' && get_accesse( (int)$clid, (int)$pid, (int)$did ) != 'yes' ) {

	print '<div class="zagolovok">Запрет просмотра</div>
	<div class="warning">
		<span><i class="icon-attention red icon-5x pull-left"></i></span>
		<b class="red uppercase">Внимание:</b><br><br>
		К сожалению Вы не можете просматривать данную информацию<br>У Вас отсутствует разрешение.<br>
	</div>';

	exit;

}

$json = get_person_info( $pid );
$data = json_decode( $json, true );

$client = current_client( (int)$data['clid'] );
$social = [];
if ( $data['social'] != ';;;;;;;' ) {
	$social = explode( ";", $data['social'] );
}

$result = $db -> getRow( "SELECT title, color FROM ".$sqlname."loyal_cat WHERE idcategory='".$data['loyalty']."' and identity = '$identity'" );
if ( $data['loyalty'] != '' ) {

	$loyalty = $result["title"];
	$lcolor  = $result["color"];

}
?>
<DIV class="zagolovok"><?= $data['person'] ?></DIV>

<DIV id="formtabs" class="box--child" style="max-height:80vh; overflow-x: hidden; overflow-y: auto !important;">

	<TABLE id="noborder" class="top">
		<TR class="th40">
			<td width="70" rowspan="16" align="center" valign="top" nowrap="nowrap" class="header2">
				<i class="icon-user-1 icon-5x broun"></i>
				<?php
				if ( $data['loyalty'] != "" ) {
					print '<br><div style="background-color:'.$lcolor.'; height: 25px; line-height:25px">&nbsp;'.$loyalty.'&nbsp;</div>';
				}
				?>
			</td>
			<TD width="100" nowrap>
				<DIV class="fnameCold">&nbsp;Должность:&nbsp;</DIV>
			</TD>
			<TD><span class="fpoleCold Bold"><?= $data['ptitle'] ?>&nbsp;</span></TD>
		</TR>
		<?php if ( $data['clid'] > 0 ) { ?>
			<TR>
				<TD width="130" nowrap>
					<DIV class="fnameCold">&nbsp;Клиент:&nbsp;</DIV>
				</TD>
				<TD>
					<span class="fpoleCold Bold"><A href="javascript:void(0)" onclick="openClient('<?= $data['clid'] ?>')" title="Открыть карточку"><?= current_client( $data['clid'] ) ?>&nbsp;<i class="icon-building broun"></i></A></span>
				</TD>
			</TR>
		<?php } ?>
		<?php if ( $data['tel'] != "" ) {

			$phone_list = [];
			$phones      = yexplode( ",", str_replace( ";", ",", str_replace( " ", "", $data['tel'] ) ) );
			foreach ($phones as $phone) {

				$ismob        = isPhoneMobile( $phone ) ? 'ismob' : '';
				$phone_list[] = '<span class="phonec phonenumber '.$ismob.'" data-pid="'.$pid.'" data-clid="'.$data['clid'].'" data-phone="'.prepareMobPhone( $phone ).'">'.formatPhoneUrl( $phone, $data['clid'], $pid ).'</span>';

			}
			$phone = implode( ", ", $phone_list );
			?>
			<TR height="25">
				<TD width="100" nowrap>
					<DIV class="fnameCold">&nbsp;Телефон:&nbsp;</DIV>
				</TD>
				<TD><span class="fpoleCold Bold"><?= $phone ?>&nbsp;</span></TD>
			</TR>
		<?php } ?>
		<?php if ( $data['mob'] != "" ) {
			$phone_list = [];
			$phones       = yexplode( ",", str_replace( ";", ",", str_replace( " ", "", $data['mob'] ) ) );
			foreach ($phones as $phone) {

				$ismob        = isPhoneMobile( $phone ) ? 'ismob' : '';
				$phone_list[] = '<span class="phonec phonenumber '.$ismob.'" data-pid="'.$pid.'" data-clid="'.$data['clid'].'" data-phone="'.prepareMobPhone( $phone ).'">'.formatPhoneUrl( $phone, $data['clid'], $pid ).'</span>';

			}
			$mob = implode( ", ", $phone_list );
			?>
			<TR height="25">
				<TD width="100" nowrap>
					<DIV class="fnameCold">&nbsp;Мобильный:&nbsp;</DIV>
				</TD>
				<TD><span class="fpoleCold Bold"><?= $mob ?>&nbsp;</span></TD>
			</TR>
		<?php } ?>
		<?php if ( $data['fax'] != "" ) {
			$phone_list = [];
			$phones     = yexplode( ",", str_replace( ";", ",", str_replace( " ", "", $data['fax'] ) ) );
			foreach ($phones as $phone) {

				$ismob        = isPhoneMobile( $phone ) ? 'ismob' : '';
				$phone_list[] = '<span class="phonec phonenumber '.$ismob.'" data-pid="'.$pid.'" data-clid="'.$data['clid'].'" data-phone="'.prepareMobPhone( $phone ).'">'.formatPhoneUrl( $phone, $data['clid'], $pid ).'</span>';

			}
			$fax = implode( ", ", $phone_list );
			?>
			<TR height="25">
				<TD width="100" nowrap>
					<DIV class="fnameCold">&nbsp;Факс:&nbsp;</DIV>
				</TD>
				<TD><span class="fpoleCold Bold"><?= $fax ?>&nbsp;</span></TD>
			</TR>
		<?php } ?>
		<?php if ( $data['mail'] != "" ) {
			$emails = explode( ",", str_replace( ";", ",", $data['mail'] ) );
			?>
			<TR height="25">
				<TD width="100" nowrap>
					<DIV class="fnameCold">&nbsp;Email:&nbsp;</DIV>
				</TD>
				<TD>
		<span class="fpoleCold Bold">
		<?php
		foreach ($emails as $email) {
			$apx = $ymEnable ? '&nbsp;(<A href="javascript:void(0)" onClick="$mailer.composeCard(\''.$clid.'\',\''.$pid.'\',\''.trim( $email[ $j ] ).'\');" title="Написать сообщение"><i class="icon-mail blue"></i></A>)&nbsp;' : '';
			print link_it( $email ).$apx;
		}
		?>
		</span>
				</TD>
			</TR>
		<?php } ?>
		<?php if ( $data['rol'] != "" ) { ?>
			<TR height="25">
				<TD width="100" nowrap>
					<DIV class="fnameCold">&nbsp;Роль:&nbsp;</DIV>
				</TD>
				<TD><span class="fpoleCold Bold"><?= $data['rol'] ?>&nbsp;</span></TD>
			</TR>
		<?php } ?>
		<?php
		if ( $data['loyalty'] != "" ) { ?>
			<?php
		}

		//выведем дату рождения
		//Сначала найдем поля, содержащие даты
		$fields = $db -> getIndCol( "fld_name", "
			SELECT fld_name, fld_title
			FROM ".$sqlname."field 
			WHERE 
				fld_tip = 'person' AND 
				fld_on = 'yes' AND 
				fld_temp = 'datum' AND 
				identity = '$identity' 
			ORDER BY fld_order" );

		//данные по персонам
		foreach ($fields as $fname => $ftitle) {

			$result = $db -> query( "SELECT * FROM ".$sqlname."personcat WHERE pid = '".$pid."' and identity = '$identity' ORDER BY $fname" );
			while ($data = $db -> fetch( $result )) {

				if ( $data[ $fname ] != '' ) {

					print '
					<tr height="25">
						<td nowrap="nowrap"><div class="fnameCold">&nbsp;'.$ftitle.':</div></td>
						<td><span class="fpoleCold Bold green"><i class="icon-gift red"></i>'.format_date_rus_name( $data[ $fname ] ).'г.&nbsp;</span></td>
					</tr>';

				}

			}

		}
		?>
	</TABLE>

</div>

<div class="button--pane text-right">

	<a href="javascript:void(0)" onclick="openPerson('<?= $pid ?>')" class="button bluebtn"><i class="icon-user-1"></i> Карточка</a>

</div>

<?php
$hooks -> do_action( "person_view", $_REQUEST );
?>

<script>

	$(function () {

		$('#dialog').css('width', '602px').center();

		ShowModal.fire({
			etype: 'personView'
		});

	});

	$('.phonec div').removeClass('ellipsis');

</script>
