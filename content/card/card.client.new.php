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

include $rootpath."/inc/language/".$language.".php";

$clid = $_REQUEST[ 'clid' ];

//массив данных по клиенту
$client = get_client_info( $clid, "yes" );

$creator = ( $client[ 'creator' ] != '' ) ? $client[ 'creator' ] : "Не определено";
$editor  = ( $client[ 'editor' ] != '' ) ? $client[ 'editor' ] : "";

$date_create = ( $client[ 'date_create' ] != '' ) ? $client[ 'date_create' ] : "??";
$date_edit   = ( $client[ 'date_edit' ] != '' ) ? $client[ 'date_edit' ] : "??";

$user = yexplode( ';', $client[ 'dostup' ] );

//Массив уровней цен
$priceF = $db -> getAll( "SELECT fld_name as name, fld_title as title FROM ".$sqlname."field WHERE fld_tip = 'price' and fld_on = 'yes' and identity = '$identity' ORDER BY fld_name" );
foreach ( $priceF as $p ) {

	$priceFields[ $p[ 'name' ] ] = $p[ 'title' ];

}

$trash = 'В корзине:';

if ( $client[ 'trash' ] == "no" ) {

	if ( $ac_import[ 20 ] != 'on' ) $trash .= '<A href="javascript:void(0)" title="Этот Клиент Активен. Удалить в корзину?" onclick="cf=confirm(\'Вы действительно удалить Клиента в корзину?\');if (cf)trashClient(\''.$client[ 'clid' ].'\',\'trash\');"><i class="icon-trash gray"></i></A>';
	else $trash .= '<A href="javascript:void(0)" title="Этот Клиент Активен. Вы не можете управлять этим"><i class="icon-trash gray"></i></A>';

}
else {

	if ( $ac_import[ 20 ] != 'on' ) $trash .= '<A href="javascript:void(0)" onclick="cf=confirm(\'Вы действительно хотите извлечь Клиента из Корзины?\');if (cf)trashClient(\''.$client[ 'clid' ].'\',\'untrash\');"><i class="icon-trash blue"></i></A>';
	else $trash .= '<A href="javascript:void(0)" title="Этот Клиент в корзине. Вы не можете управлять этим"><i class="icon-trash blue"></i></A>';

}

print 'Свободный:';

if ( $client[ 'iduser' ] > 0 ) {

	if ( $ac_import[ 20 ] != 'on' ) $trash .= '&nbsp;&nbsp;<A href="javascript:void(0)" title="Этот Клиент имеет Ответственного. Сделать Свободным?" onclick="cf=confirm(\'Вы действительно хотите Сделать Свободным?\');if (cf)trashClient(\''.$client[ 'clid' ].'\',\'cold\');"><i class="icon-user-1 gray" title="Этот Клиент имеет Ответственного. Сделать Свободным?"></i></A>';
	else $trash .= '&nbsp;&nbsp;<A href="javascript:void(0)" title="Этот Клиент имеет Ответственного. Вы не можете управлять этим"><i class="icon-user-1 gray"></i></A>';

}
else {

	$trash .= '&nbsp;&nbsp;<A href="javascript:void(0)" onclick="cf=confirm(\'Вы действительно хотите назначить Клиента на себя?\');if (cf)trashClient(\''.$client[ 'clid' ].'\',\'uncold\');"><i class="icon-user-1 blue" title="Это Свободный Клиент. Назначить на себя?"></i></A>';

}

$trash .= 'Ключевой:';

if ( $client[ 'fav' ] != 'yes' ) {

	$trash .= '&nbsp;&nbsp;<A href="javascript:void(0)" onclick="trashClient(\''.$client[ 'clid' ].'\',\'add_fav\');"><i class="icon-star-empty gray" title="Отметить Ключевым клиентом"></i></A>';
}
else {

	$trash .= '&nbsp;&nbsp;<A href="javascript:void(0)" onclick="trashClient(\''.$client[ 'clid' ].'\',\'del_fav\');"><i class="icon-star red" title="Снять отметку Ключевого клиента"></i></A>';
}

if ( $outClientUrl != '' && $client[ 'uid' ] != '' ) {

	$outClientUrl = str_replace( "{uid}", $client[ 'uid' ], $outClientUrl );
	$outClientUrl = str_replace( "{login}", current_userlogin( $iduser1 ), $outClientUrl );
	$outClientUrl = '<span class="button pull-aright"><a href="'.$outClientUrl.'" target="_blank" title="Переход в ИС"><i class="icon-forward"></i></a></span>';

}

//Формируем массив данных для шаблона
$Client = [
	"clid"        => $clid,
	"creator"     => $creator,
	"date_create" => $date_create,
	"editor"      => $editor,
	"date_edit"   => $date_edit,
	"accesse"     => ( get_accesse( (int)$clid ) == "yes" && $tipuser != 'Поддержка продаж' ) ? "yes" : "",
	"trash"       => $trash,
	"uid"         => $client[ 'uid' ],
	"ID"          => ( stripos( $tipuser, 'Руководитель' ) !== false ) ? $clid : '',
	"link"        => $outClientUrl

];
?>
	<DIV class="fcontainer relativ bgwhite">

		<div class="fs-09 gray2">
			<div>Автор:&nbsp;<b><?= $creator ?></b>, <?= $date_create ?></div>
			<?php if ( $client[ 'editor' ] > 0 ) { ?>
				<div>Редактор:&nbsp;<b><?= $editor ?></b>, <?= $date_edit ?></div><?php } ?>
		</div>

		<hr>

		<?php
		if ( get_accesse( (int)$clid ) == "yes" and $tipuser != 'Поддержка продаж' ) {
			?>
			<DIV class="text-right">
				<?php

				print 'В корзине:';

				if ( $client[ 'trash' ] == "no" ) {

					if ( $ac_import[ 20 ] != 'on' ) print '<A href="javascript:void(0)" title="Этот Клиент Активен. Удалить в корзину?" onclick="cf=confirm(\'Вы действительно удалить Клиента в корзину?\');if (cf)trashClient(\''.$client[ 'clid' ].'\',\'trash\');"><i class="icon-trash gray"></i></A>';
					else print '<A href="javascript:void(0)" title="Этот Клиент Активен. Вы не можете управлять этим"><i class="icon-trash gray"></i></A>';

				}
				else {

					if ( $ac_import[ 20 ] != 'on' ) print '<A href="javascript:void(0)" onclick="cf=confirm(\'Вы действительно хотите извлечь Клиента из Корзины?\');if (cf)trashClient(\''.$client[ 'clid' ].'\',\'untrash\');"><i class="icon-trash blue"></i></A>';
					else print '<A href="javascript:void(0)" title="Этот Клиент в корзине. Вы не можете управлять этим"><i class="icon-trash blue"></i></A>';

				}

				print 'Свободный:';

				if ( $client[ 'iduser' ] > 0 ) {

					if ( $ac_import[ 20 ] != 'on' ) print '&nbsp;&nbsp;<A href="javascript:void(0)" title="Этот Клиент имеет Ответственного. Сделать Свободным?" onclick="cf=confirm(\'Вы действительно хотите Сделать Свободным?\');if (cf)trashClient(\''.$client[ 'clid' ].'\',\'cold\');"><i class="icon-user-1 gray" title="Этот Клиент имеет Ответственного. Сделать Свободным?"></i></A>';
					else print '&nbsp;&nbsp;<A href="javascript:void(0)" title="Этот Клиент имеет Ответственного. Вы не можете управлять этим"><i class="icon-user-1 gray"></i></A>';

				}
				else {

					/*if($ac_import[20] != 'on')*/
					print '&nbsp;&nbsp;<A href="javascript:void(0)" onclick="cf=confirm(\'Вы действительно хотите назначить Клиента на себя?\');if (cf)trashClient(\''.$client[ 'clid' ].'\',\'uncold\');"><i class="icon-user-1 blue" title="Это Свободный Клиент. Назначить на себя?"></i></A>';
					//else print '&nbsp;&nbsp;<A href="javascript:void(0)" title="Это Свободный Клиент. Вы не можете управлять этим"><i class="icon-user-1 blue"></i></A>';

				}

				print 'Ключевой:';

				if ( $client[ 'fav' ] != 'yes' ) {

					print '&nbsp;&nbsp;<A href="javascript:void(0)" onclick="trashClient(\''.$client[ 'clid' ].'\',\'add_fav\');"><i class="icon-star-empty gray" title="Отметить Ключевым клиентом"></i></A>';
				}
				else {

					print '&nbsp;&nbsp;<A href="javascript:void(0)" onclick="trashClient(\''.$client[ 'clid' ].'\',\'del_fav\');"><i class="icon-star red" title="Снять отметку Ключевого клиента"></i></A>';
				}
				?>
			</DIV>
			<?php
		}
		else {

			print '<div class="warning div-center"><i class="icon-attention red"></i>&nbsp;У вас нет доступа к редактированию записи</div>';

		}
		?>

		<fieldset id="cInfo">

			<legend><b>Общее</b></legend>

			<TABLE width="100%" border="0" cellspacing="2" cellpadding="2">
				<?php if ( $client[ 'uid' ] != '' ) { ?>
					<TR>
						<TD width="25%" nowrap>
							<DIV class="ellipsis" title="UID">&nbsp;UID:</DIV>
						</TD>
						<TD>
							<DIV class="text-content_rev">
								<b class="Bold"><?= $client[ 'uid' ] ?></b>
								<?php
								if ( $outClientUrl != '' && $client[ 'uid' ] != '' ) {

									$outClientUrl = str_replace( "{uid}", $client[ 'uid' ], $outClientUrl );
									$outClientUrl = str_replace( "{login}", current_userlogin( $iduser1 ), $outClientUrl );
									print '<span class="button pull-aright"><a href="'.$outClientUrl.'" target="_blank" title="Переход в ИС"><i class="icon-forward"></i></a></span>';

								}
								?>
							</DIV>
						</TD>
					</TR>
					<?php
				}
				if ( stripos( $tipuser, 'Руководитель' ) !== false && $clid > 0 ) {
					?>
					<TR>
						<TD width="25%" nowrap>
							<DIV class="ellipsis" title="ID записи">&nbsp;ID записи:</DIV>
						</TD>
						<TD>
							<DIV class="text-content_rev"><b class="Bold"><?= $client[ 'clid' ] ?></b></DIV>
						</TD>
					</TR>
					<?php
				}
				?>
				<TR>
					<TD width="25%" nowrap>
						<DIV class="ellipsis em gray2" title="Тип записи">&nbsp;Тип записи:</DIV>
					</TD>
					<TD>
						<DIV class="text-content_rev">
							<b class="broun"><?= strtr( $client[ 'type' ], $client_types ) ?></b></DIV>
					</TD>
				</TR>
				<?php
				//привязанный уровень прайса
				if ( $other[ 3 ] == 'yes' ) {
					?>
					<TR>
						<TD width="25%" nowrap>
							<DIV class="ellipsis em gray2">&nbsp;Уровень цен:</DIV>
						</TD>
						<TD>
							<DIV class="text-content_rev">
								<b class="Bold"><?= strtr( $client[ 'priceLevel' ], $fieldsNames[ 'price' ] ) ?></b>
								<?php
								if ( get_accesse( (int)$clid ) == "yes" ) {
									?>
									<a href="javascript:void(0)" onclick="editClient('<?= $client[ 'clid' ] ?>','change.priceLevel');" title="Изменить" class="dright gray blue"><i class="icon-pencil blue"></i></a>
								<?php } ?>
							</DIV>
						</TD>
					</TR>
					<?php
				}
				?>
			</TABLE>

		</fieldset>

		<fieldset id="cMain">

			<legend><b>Основное</b></legend>

			<TABLE>
				<?php
				$head_print = 0;
				$re         = $db -> query( "select fld_name, fld_title, fld_temp, fld_stat from ".$sqlname."field where fld_tip='client' and fld_on='yes' and identity = '$identity' order by fld_order" );
				while ( $da = $db -> fetch( $re, MYSQLI_ASSOC ) ) {

					if ( $da[ 'fld_name' ] == 'iduser' ) {
						?>
						<TR>
							<TD width="25%" nowrap>
								<DIV class="ellipsis em gray2" title="<?= $da[ 'fld_title' ] ?>">&nbsp;<?= $da[ 'fld_title' ] ?>:</DIV>
							</TD>
							<TD>
								<DIV class="text-content_rev">
									<B class="red"><?= current_user( $client[ 'iduser' ] ); ?></B>&nbsp;
									<?php
									if ( get_accesse( (int)$clid ) == 'yes' and $ac_import[ 20 ] != 'on' ) {

										if ( $tipuser != 'Поддержка продаж' ) {
											?>
											<a href="javascript:void(0)" onclick="editClient('<?= $clid ?>','change.user');" title="Изменить ответственного" class="dright gray blue"><i class="icon-pencil blue"></i></a>
											<?php
										}

									}
									?>
								</DIV>
							</TD>
						</TR>
						<?php
					}
					elseif ( $da[ 'fld_name' ] == 'idcategory' ) { ?>
						<TR>
							<TD width="25%" nowrap>
								<DIV class="ellipsis em gray2" title="<?= $da[ 'fld_title' ] ?>">&nbsp;<?= $da[ 'fld_title' ] ?>:</DIV>
							</TD>
							<TD>
								<DIV class="text-content_rev"><?= $client[ 'category' ] ?>&nbsp;</DIV>
							</TD>
						</TR>
						<?php
					}
					elseif ( $da[ 'fld_name' ] == 'clientpath' and $client[ 'clientpath' ] != '' ) { ?>
						<TR>
							<TD width="25%" nowrap>
								<DIV class="ellipsis em gray2" title="<?= $da[ 'fld_title' ] ?>">&nbsp;<?= $da[ 'fld_title' ] ?>:</DIV>
							</TD>
							<TD>
								<DIV class="text-content_rev"><?= $client[ 'clientpath' ] ?>&nbsp;</DIV>
							</TD>
						</TR>
						<?php
					}
					elseif ( $da[ 'fld_name' ] == 'head_clid' and $client[ 'head_clid' ] > 0 ) { ?>
						<TR>
							<TD width="25%" nowrap>
								<DIV class="ellipsis em gray2" title="<?= $da[ 'fld_title' ] ?>">&nbsp;<?= $da[ 'fld_title' ] ?>:</DIV>
							</TD>
							<TD>
								<DIV class="text-content_rev">
									<A href="javascript:void(0)" onclick="openClient('<?= $client[ 'head_clid' ] ?>');" title="Открыть карточку"><i class="icon-building blue"></i><?= $client[ 'head' ] ?>
									</A></DIV>
							</TD>
						</TR>
						<?php
						$doche = $db -> getAll( "select clid, title from ".$sqlname."clientcat where head_clid='".$client[ 'clid' ]."' and identity = '$identity'" );
						if ( !empty( $doche ) && (int)$head_print == 0 ) {
							?>
							<TR>
								<TD width="25%" nowrap>
									<DIV class="ellipsis em gray2" title="<?= $da[ 'fld_title' ] ?>">&nbsp;Доч. организации:</DIV>
								</TD>
								<TD>
									<DIV class="text-content_rev">
										<?php
										foreach ( $doche as $do ) {
											?>
											<A href="javascript:void(0)" onclick="openClient('<?= $do[ 'clid' ] ?>');" title="Открыть карточку"><i class="icon-commerical-building"></i><?= $do[ 'title' ] ?>
											</A>;
										<?php } ?>
									</DIV>
								</TD>
							</TR>
							<?php
							//исключим многоразовый вывод с помощью счетчика.
							$head_print++;
						}
					}
					elseif ( $da[ 'fld_name' ] == 'address' and $client[ 'address' ] != "" ) { ?>
						<TR>
							<TD width="25%" nowrap>
								<DIV class="ellipsis em gray2" title="<?= $da[ 'fld_title' ] ?>">&nbsp;<?= $da[ 'fld_title' ] ?>:</DIV>
							</TD>
							<TD>
								<DIV class="text-content_rev">
									<i class="icon-location blue"></i>&nbsp;<a href="http://maps.google.ru/maps?hl=ru&tab=wl&q=<?= $client[ 'address' ] ?>" target="_blank"><?= $client[ 'address' ] ?></a>
								</DIV>
							</TD>
						</TR>
						<?php
					}
					elseif ( $da[ 'fld_name' ] == 'territory' and $client[ 'territory' ] > 0 ) { ?>
						<TR>
							<TD width="25%" nowrap>
								<DIV class="ellipsis em gray2" title="<?= $da[ 'fld_title' ] ?>">&nbsp;<?= $da[ 'fld_title' ] ?>:</DIV>
							</TD>
							<TD>
								<DIV class="text-content_rev"><b><?= $client[ 'territoryname' ] ?></b></DIV>
							</TD>
						</TR>
					<?php }
					elseif ( $da[ 'fld_name' ] == 'phone' && $client[ 'phone' ] != "" ) {
						$phone_list = [];
						$phone      = yexplode( ",", (string)str_replace( ";", ",", str_replace( " ", "", $client[ 'phone' ] ) ) );
						for ( $p = 0; $p < count( $phone ); $p++ ) {

							if ( substr( prepareMobPhone( $phone[ $p ] ), 1, 1 ) == '9' ) $ismob = 'ismob';
							else $ismob = '';

							$phone_list[] = '<span class="phonenumber '.$ismob.'" data-pid="" data-clid="'.$clid.'" data-phone="'.prepareMobPhone( $phone[ $p ] ).'">'.formatPhoneUrl( $phone[ $p ], $clid ).'</span>';

						}
						$phone = implode( ", ", $phone_list );
						?>
						<TR>
							<TD width="25%" nowrap>
								<DIV class="ellipsis em gray2" title="<?= $da[ 'fld_title' ] ?>">&nbsp;<?= $da[ 'fld_title' ] ?>:</DIV>
							</TD>
							<TD>
								<DIV class="text-content_rev"><?= $phone ?></DIV>
							</TD>
						</TR>
					<?php }
					elseif ( $da[ 'fld_name' ] == 'fax' && $client[ 'fax' ] != "" ) {
						$phone_list = [];
						$fax        = yexplode( ",", (string)str_replace( ";", ",", str_replace( " ", "", $client[ 'fax' ] ) ) );
						for ( $p = 0; $p < count( $fax ); $p++ ) {

							if ( substr( prepareMobPhone( $fax[ $p ] ), 1, 1 ) == '9' ) $ismob = 'ismob';
							else $ismob = '';

							$phone_list[] = '<span class="phonenumber '.$ismob.'" data-pid="" data-clid="'.$clid.'" data-phone="'.prepareMobPhone( $fax[ $p ] ).'">'.formatPhoneUrl( $fax[ $p ], $clid ).'</span>';

						}
						$fax = implode( ", ", $phone_list );
						?>
						<TR>
							<TD width="25%" nowrap>
								<DIV class="ellipsis em gray2" title="<?= $da[ 'fld_title' ] ?>">&nbsp;<?= $da[ 'fld_title' ] ?>:</DIV>
							</TD>
							<TD>
								<DIV class="text-content_rev"><?= $fax ?></DIV>
							</TD>
						</TR>
						<?php
					}
					elseif ( $da[ 'fld_name' ] == 'site_url' and $client[ 'site_url' ] != "" ) { ?>
						<TR>
							<TD width="25%" nowrap>
								<DIV class="ellipsis em gray2" title="<?= $da[ 'fld_title' ] ?>">&nbsp;<?= $da[ 'fld_title' ] ?>:</DIV>
							</TD>
							<TD>
								<DIV class="text-content_rev"><?= link_it( $client[ 'site_url' ] ) ?></DIV>
							</TD>
						</TR>
					<?php }
					elseif ( $da[ 'fld_name' ] == 'mail_url' and $client[ 'mail_url' ] != "" ) { ?>
						<TR>
							<TD width="25%" nowrap>
								<DIV class="ellipsis em gray2" title="<?= $da[ 'fld_title' ] ?>">&nbsp;<?= $da[ 'fld_title' ] ?>:</DIV>
							</TD>
							<TD>
								<DIV class="text-content_rev">
									<?php
									$email = explode( ",", (string)str_replace( ";", ",", $client[ 'mail_url' ] ) );
									for ( $j = 0; $j < count( $email ); $j++ ) {
										if ( $ymEnable ) $apx = '&nbsp;(<A href="javascript:void(0)" onclick="$mailer.composeCard(\''.$clid.'\',\'\',\''.trim( $email[ $j ] ).'\');" title="Написать сообщение"><i class="icon-mail blue"></i></A>)&nbsp;';
										else $apx = '';
										print link_it( $email[ $j ] )."".$apx;
									}
									?>
								</DIV>
							</TD>
						</TR>
					<?php }
					elseif ( $da[ 'fld_name' ] == 'pid' and $client[ 'pid' ] != "" ) { ?>
						<TR>
							<TD width="25%" nowrap>
								<DIV class="ellipsis em gray2" title="<?= $da[ 'fld_title' ] ?>">&nbsp;<?= $da[ 'fld_title' ] ?>:</DIV>
							</TD>
							<TD>
								<DIV class="text-content_rev">
									<A href="javascript:void(0)" onclick="viewPerson('<?= $client[ 'pid' ] ?>')" title="Просмотр"><i class="icon-user-1 blue"></i><?= current_person( $client[ 'pid' ] ) ?>
									</A></DIV>
							</TD>
						</TR>
					<?php }
					elseif ( $da[ 'fld_name' ] == 'scheme' and $client[ 'scheme' ] != "" ) { ?>
						<TR>
							<TD width="25%" nowrap>
								<DIV class="ellipsis em gray2" title="<?= $da[ 'fld_title' ] ?>">&nbsp;<?= $da[ 'fld_title' ] ?>:</DIV>
							</TD>
							<TD>
								<DIV class="text-content_rev"><?= nl2br( $client[ 'scheme' ] ) ?></DIV>
							</TD>
						</TR>
					<?php }
					elseif ( $da[ 'fld_name' ] == 'tip_cmr' and $client[ 'tip_cmr' ] != "" ) { ?>
						<TR>
							<TD width="25%" nowrap>
								<DIV class="ellipsis em gray2" title="<?= $da[ 'fld_title' ] ?>">&nbsp;<?= $da[ 'fld_title' ] ?>:</DIV>
							</TD>
							<TD>
								<DIV class="text-content_rev"><b><?= $client[ 'tip_cmr' ] ?></b></DIV>
							</TD>
						</TR>
						<?php
					}
					elseif ( $da[ 'fld_name' ] == 'des' ) {
						?>
						<TR>
							<TD colspan="2">
								<DIV class="em gray2 mt10" title="<?= $da[ 'fld_title' ] ?>">&nbsp;<?= $da[ 'fld_title' ] ?>:</DIV>
								<DIV class="text-content_rev mt5" style="max-height:300px; overflow:auto !important;">
									<?= nl2br( $client[ 'des' ] ) ?>
									<hr>
									<div class="margtop5 right-text">
										<A href="javascript:void(0)" onclick="editClient('<?= $clid ?>','change.desсription')" class="sbutton">Добавить примечание</A>
									</div>
								</DIV>
							</TD>
						</TR>
						<?php
					}
					elseif ( $da[ 'fld_stat' ] != 'yes' ) {

						if ( $client[ $da[ 'fld_name' ] ] != '' && $da[ 'fld_temp' ] != 'textarea' ) {

							if ( $da[ 'fld_temp' ] == "datum" ) $field = '<b class="green">'.format_date_rus_name( $client[ $da[ 'fld_name' ] ] ).'</b>';
							else if ( $da[ 'fld_temp' ] == "adres" ) $field = '<i class="icon-location blue"></i>&nbsp;<a href="http://maps.google.ru/maps?hl=ru&tab=wl&q='.$client[ $da[ 'fld_name' ] ].'" target="_blank">'.$client[ $da[ 'fld_name' ] ].'</a>';
							else $field = $client[ $da[ 'fld_name' ] ];
							?>
							<TR>
								<TD width="25%" nowrap>
									<DIV class="ellipsis em gray2" title="<?= $da[ 'fld_title' ] ?>">&nbsp;<?= $da[ 'fld_title' ] ?>:</DIV>
								</TD>
								<TD>
									<DIV class="text-content_rev" style="max-height:250px"><?= str_replace( "\n", "<br>", $field ) ?></DIV>
								</TD>
							</TR>
							<?php
						}
						elseif ( $client[ $da[ 'fld_name' ] ] != '' && $da[ 'fld_temp' ] == 'textarea' ) {

							$field = $client[ $da[ 'fld_name' ] ];
							?>
							<tr>
								<td colspan="2">
									<DIV class="ellipsis em gray2"><?= $da[ 'fld_title' ] ?>:&nbsp;</DIV>
									<div class="text-content_rev" style="min-height:30px; max-height:300px; overflow:auto !important;"><?= nl2br( $field ) ?>&nbsp;</div>
								</td>
							</tr>
							<?php
						}
					}

				}
				?>
			</TABLE>

		</fieldset>

	</DIV>

<?php
$recv_on = $db -> getOne( "select fld_on from ".$sqlname."field where fld_name='recv' and identity = '$identity'" );

if ( $recv_on == 'yes' ) {

	if ( file_exists( $rootpath.'/cash/'.$fpath.'requisites.json' ) ) {
		$file     = file_get_contents( $rootpath.'/cash/'.$fpath.'requisites.json' );
		$recvName = json_decode( $file, true );
	}
	else {
		$file     = file_get_contents( $rootpath.'/cash/requisites.json' );
		$recvName = json_decode( $file, true );
	}

	?>
	<DIV id="detail_<?= $client[ 'clid' ] ?>" style="display:block">

		<fieldset class="fcontainer">

			<legend><b><?= $lang[ 'all' ][ 'Details' ] ?></b></legend>
			<?php
			if ( get_accesse( (int)$clid ) == "yes" ) {
				?>
				<DIV align="right" class="batton-edit">
					<a href="javascript:void(0)" onclick="editClient('<?= $clid ?>','change.recvisites');"><?= $lang[ 'all' ][ 'Edit' ] ?></a>
				</DIV>
				<?php
			}

			if ( $client[ 'type' ] != 'person' ) {

				$json = get_client_recv( $clid );
				$recv = json_decode( $json, true );

				if ( $recv[ 'castUrName' ] == '' ) $castUrName = $recv[ 'castName' ];
				else $castUrName = $recv[ 'castUrName' ];

				if ( count( $recv ) > 0 ) {
					?>
					<div class="togglerbox hand paddbott10 blue" data-id="recv" title="Детали. Показать/Скрыть">
						<i class="icon-angle-down" id="mapic" title="<?= $lang[ 'all' ][ 'More' ] ?> <?= $lang[ 'all' ][ 'Show' ] ?>/<?= $lang[ 'all' ][ 'Hide' ] ?>"></i>&nbsp;<?= $lang[ 'all' ][ 'Show' ] ?>/<?= $lang[ 'all' ][ 'Hide' ] ?>
					</div>
					<div id="recv" class="hidden">
						<TABLE width="100%" border="0" cellspacing="2" cellpadding="2">
							<TR>
								<TD width="30%" nowrap>
									<DIV class="em gray2 ellipsis">Юр. Название:</DIV>
								</TD>
								<TD>
									<DIV class="text-content_rev"><?= $castUrName ?>&nbsp;</DIV>
								</TD>
							</TR>
							<TR>
								<TD width="30%" nowrap>
									<DIV class="em gray2 ellipsis">Юр. Адрес:</DIV>
								</TD>
								<TD>
									<DIV class="text-content_rev"><?= $recv[ 'castUrAddr' ] ?>&nbsp;</DIV>
								</TD>
							</TR>
							<TR>
								<TD width="30%" nowrap>
									<DIV class="em gray2 ellipsis"><?= $recvName[ 'recvInn' ] ?>/<?= $recvName[ 'recvKpp' ] ?>:</DIV>
								</TD>
								<TD>
									<DIV class="text-content_rev"><?= $recv[ 'castInn' ] ?>&nbsp;/&nbsp;<?= $recv[ 'castKpp' ] ?></DIV>
								</TD>
							</TR>
							<TR>
								<TD width="30%" nowrap>
									<DIV class="em gray2 ellipsis"><?= $recvName[ 'recvOkpo' ] ?>/<?= $recvName[ 'recvOgrn' ] ?>:</DIV>
								</TD>
								<TD>
									<DIV class="text-content_rev"><?= $recv[ 'castOkpo' ] ?>&nbsp;/&nbsp;<?= $recv[ 'castOgrn' ] ?></DIV>
								</TD>
							</TR>
							<TR>
								<TD width="30%" nowrap>
									<DIV class="em gray2 ellipsis"><?= $recvName[ 'recvBankName' ] ?>:</DIV>
								</TD>
								<TD>
									<DIV class="text-content_rev"><?= $recv[ 'castBank' ] ?>&nbsp;</DIV>
								</TD>
							</TR>
							<TR>
								<TD width="30%" nowrap>
									<DIV class="em gray2 ellipsis"><?= $recvName[ 'recvBankBik' ] ?>:</DIV>
								</TD>
								<TD>
									<DIV class="text-content_rev"><?= $recv[ 'castBankBik' ] ?>&nbsp;</DIV>
								</TD>
							</TR>
							<TR>
								<TD width="30%" nowrap>
									<DIV class="em gray2 ellipsis"><?= $recvName[ 'recvBankKs' ] ?>:</DIV>
								</TD>
								<TD>
									<DIV class="text-content_rev"><?= $recv[ 'castBankKs' ] ?>&nbsp;</DIV>
								</TD>
							</TR>
							<TR>
								<TD width="30%" nowrap>
									<DIV class="em gray2 ellipsis"><?= $recvName[ 'recvBankRs' ] ?>:</DIV>
								</TD>
								<TD>
									<DIV class="text-content_rev"><?= $recv[ 'castBankRs' ] ?>&nbsp;</DIV>
								</TD>
							</TR>
							<TR>
								<TD width="30%" nowrap>
									<DIV class="em gray2 ellipsis">Руководитель:</DIV>
								</TD>
								<TD>
									<DIV class="text-content_rev"><?= $recv[ 'castDirName' ] ?>&nbsp;</DIV>
								</TD>
							</TR>
							<TR>
								<TD width="30%" nowrap>
									<DIV class="em gray2 ellipsis">Руководитель (подпись):</DIV>
								</TD>
								<TD>
									<DIV class="text-content_rev"><?= $recv[ 'castDirSignature' ] ?>&nbsp;</DIV>
								</TD>
							</TR>
							<TR>
								<TD width="30%" nowrap>
									<DIV class="em gray2 ellipsis">Должность:</DIV>
								</TD>
								<TD>
									<DIV class="text-content_rev"><?= $recv[ 'castDirStatus' ] ?>&nbsp;</DIV>
								</TD>
							</TR>
							<TR>
								<TD width="30%" nowrap>
									<DIV class="em gray2 ellipsis">Должность (подпись):</DIV>
								</TD>
								<TD>
									<DIV class="text-content_rev"><?= $recv[ 'castDirStatusSig' ] ?>&nbsp;</DIV>
								</TD>
							</TR>
							<TR>
								<TD width="30%" nowrap>
									<DIV class="em gray2 ellipsis">Действует на основании:</DIV>
								</TD>
								<TD>
									<DIV class="text-content_rev"><?= $recv[ 'castDirOsnovanie' ] ?>&nbsp;</DIV>
								</TD>
							</TR>
						</TABLE>
					</div>
					<?php
				}
				else print 'Реквизиты не заполнены';

			}

			if ( $client[ 'type' ] == 'person' ) {

				$json = get_client_recv( $clid );
				$recv = json_decode( $json, true );

				if ( $recv[ 'castUrName' ] == '' ) $castUrName = $recv[ 'castName' ];
				else $castUrName = $recv[ 'castUrName' ];

				if ( count( $recv ) > 0 ) {
					?>
					<div class="togglerbox hand paddbott10 blue" data-id="recvp" title="Детали. Показать/Скрыть">
						<i class="icon-angle-down" id="mapic" title="<?= $lang[ 'all' ][ 'More' ] ?> <?= $lang[ 'all' ][ 'Show' ] ?>/<?= $lang[ 'all' ][ 'Hide' ] ?>"></i>&nbsp;<?= $lang[ 'all' ][ 'Show' ] ?>/<?= $lang[ 'all' ][ 'Hide' ] ?>
					</div>
					<div id="recvp" class="hidden">
						<TABLE width="100%" border="0" cellspacing="2" cellpadding="2">
							<TR>
								<TD width="30%" nowrap>
									<DIV class="em gray2 ellipsis">ФИО (полностью):</DIV>
								</TD>
								<TD>
									<DIV class="text-content_rev"><?= $castUrName ?>&nbsp;</DIV>
								</TD>
							</TR>
							<TR>
								<TD width="30%" nowrap>
									<DIV class="em gray2 ellipsis">Паспорт :</DIV>
								</TD>
								<TD>
									<DIV class="text-content_rev">Серия <b><?= $recv[ 'castInn' ] ?></b> №
										<b><?= $recv[ 'castKpp' ] ?></b> от
										<b><?= format_date_rus_name( $recv[ 'castDirStatus' ] ) ?></b></DIV>
								</TD>
							</TR>
							<?php if ( $recv[ 'castDirOsnovanie' ] != '' ) { ?>
								<TR>
									<TD width="30%" nowrap>
										<DIV class="em gray2 ellipsis">Действителен до :</DIV>
									</TD>
									<TD>
										<DIV class="text-content_rev">
											<b><?= format_date_rus_name( $recv[ 'castDirOsnovanie' ] ) ?></b></DIV>
									</TD>
								</TR>
							<?php } ?>
							<TR>
								<TD width="30%" nowrap>
									<DIV class="em gray2 ellipsis">Кем выдан:</DIV>
								</TD>
								<TD>
									<DIV class="text-content_rev"><?= $recv[ 'castDirStatusSig' ] ?>&nbsp;</DIV>
								</TD>
							</TR>
							<TR>
								<TD width="30%" nowrap>
									<DIV class="em gray2 ellipsis">Дата рождения:</DIV>
								</TD>
								<TD>
									<DIV class="text-content_rev"><?= $recv[ 'castBank' ] ?>&nbsp;</DIV>
								</TD>
							</TR>
							<TR>
								<TD width="30%" nowrap>
									<DIV class="em gray2 ellipsis">Место рождения:</DIV>
								</TD>
								<TD>
									<DIV class="text-content_rev"><?= $recv[ 'castBankKs' ] ?>&nbsp;</DIV>
								</TD>
							</TR>
							<TR>
								<TD width="30%" nowrap>
									<DIV class="em gray2 ellipsis">Прописка (Страна):</DIV>
								</TD>
								<TD>
									<DIV class="text-content_rev"><?= $recv[ 'castBankRs' ] ?>&nbsp;</DIV>
								</TD>
							</TR>
							<TR>
								<TD width="30%" nowrap>
									<DIV class="em gray2 ellipsis">Прописка (Область):</DIV>
								</TD>
								<TD>
									<DIV class="text-content_rev"><?= $recv[ 'castBankBik' ] ?>&nbsp;</DIV>
								</TD>
							</TR>
							<TR>
								<TD width="30%" nowrap>
									<DIV class="em gray2 ellipsis">Прописка (Индекс):</DIV>
								</TD>
								<TD>
									<DIV class="text-content_rev"><?= $recv[ 'castOkpo' ] ?>&nbsp;
								</TD>
							</TR>
							<TR>
								<TD width="30%" nowrap>
									<DIV class="em gray2 ellipsis">Прописка (Город):</DIV>
								</TD>
								<TD>
									<DIV class="text-content_rev"><?= $recv[ 'castOgrn' ] ?>&nbsp;</DIV>
								</TD>
							</TR>
							<TR>
								<TD width="30%" nowrap>
									<DIV class="em gray2 ellipsis">Прописка (Улица, дом, квартира):</DIV>
								</TD>
								<TD>
									<DIV class="text-content_rev"><?= $recv[ 'castDirName' ] ?>&nbsp;</DIV>
								</TD>
							</TR>
							<TR>
								<TD width="30%" nowrap>
									<DIV class="em gray2 ellipsis">ФИО Сотрудника:</DIV>
								</TD>
								<TD>
									<DIV class="text-content_rev"><?= $recv[ 'castDirSignature' ] ?>&nbsp;</DIV>
								</TD>
							</TR>
						</TABLE>
					</div>
					<?php
				}
				else print 'Реквизиты не заполнены';

			}
			?>
			<br>
		</fieldset>

	</DIV>
	<?php
}
?>