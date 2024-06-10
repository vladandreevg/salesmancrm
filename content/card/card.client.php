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
ini_set( 'display_errors', 1 );
header( "Pragma: no-cache" );

$rootpath = dirname(__DIR__, 2);

include $rootpath."/inc/config.php";
include $rootpath."/inc/dbconnector.php";
include $rootpath."/inc/auth.php";
include $rootpath."/inc/func.php";
include $rootpath."/inc/settings.php";
include $rootpath."/inc/language/".$language.".php";

$thisfile = basename( __FILE__ );

global $userRights;

$clid = (int)$_REQUEST['clid'];

//массив данных по клиенту
$client = get_client_info( $clid, "yes" );

//print_r($client);

$creator = ((int)$client['creatorID'] > 0) ? $client['creator'] : "Не определено";
$editor  = ((int)$client['editorID'] > 0) ? $client['editor'] : "Не определено";

$date_create = ($client['date_create'] != '') ? $client['date_create'] : "??";
$date_edit   = ($client['date_edit'] != '') ? $client['date_edit'] : "??";

$user = yexplode( ';', (string)$client['dostup'] );

//Массив уровней цен
$priceF = $db -> getAll( "SELECT fld_name as name, fld_title as title FROM {$sqlname}field WHERE fld_tip = 'price' and fld_on = 'yes' and identity = '$identity' ORDER BY fld_name" );
foreach ( $priceF as $p ) {

	$priceFields[ $p['name'] ] = $p['title'];

}

// Проверка на доступность редактирования
$isAccess = get_accesse( (int)$clid ) == "yes" || $isadmin == 'on';

?>
	<DIV class="fcontainer relativ bgwhite transparent1 no-border1 p0">

		<div class="fs-09 gray2 hidden">

			<div>Автор:&nbsp;<b><?= $creator ?></b>, <?= $date_create ?></div>
			<?php if ( $client['editor'] != "" ) { ?>
				<div>Редактор:&nbsp;<b><?= $editor ?></b>, <?= $date_edit ?></div><?php } ?>
			<hr>

		</div>

		<?php
		if ( get_accesse( (int)$clid ) == "yes" || $isadmin == 'on' /*&& $tipuser != 'Поддержка продаж'*/ ) {
			?>
			<DIV class="text-right mb15 p15">
				<?php

				print 'В корзине: ';

				if ( $client['trash'] == "no" ) {

					if ( !$userRights['nouserchange'] ) {
						print '<A href="javascript:void(0)" title="Этот Клиент Активен. Удалить в корзину?" onclick="cf=confirm(\'Вы действительно удалить Клиента в корзину?\');if (cf)trashClient(\''.$client['clid'].'\',\'trash\');"><i class="icon-trash gray"></i></A>&nbsp;&nbsp;';
					}
					else {
						print '<A href="javascript:void(0)" title="Этот Клиент Активен. Вы не можете управлять этим"><i class="icon-trash gray"></i></A>&nbsp;&nbsp;';
					}

				}
				else {

					if ( !$userRights['nouserchange'] ) {
						print '<A href="javascript:void(0)" onclick="cf=confirm(\'Вы действительно хотите извлечь Клиента из Корзины?\');if (cf)trashClient(\''.$client['clid'].'\',\'untrash\');"><i class="icon-trash blue"></i></A>&nbsp;&nbsp;';
					}
					else {
						print '<A href="javascript:void(0)" title="Этот Клиент в корзине. Вы не можете управлять этим"><i class="icon-trash blue"></i></A>&nbsp;&nbsp;';
					}

				}

				print 'Свободный: ';

				if ( $client['iduser'] > 0 ) {

					if ( !$userRights['nouserchange'] ) {
						print '<A href="javascript:void(0)" title="Этот Клиент имеет Ответственного. Сделать Свободным?" onclick="cf=confirm(\'Вы действительно хотите Сделать Свободным?\');if (cf)trashClient(\''.$client['clid'].'\',\'cold\');"><i class="icon-user-1 gray" title="Этот Клиент имеет Ответственного. Сделать Свободным?"></i></A>&nbsp;&nbsp;';
					}
					else {
						print '<A href="javascript:void(0)" title="Этот Клиент имеет Ответственного. Вы не можете управлять этим"><i class="icon-user-1 gray"></i></A>&nbsp;&nbsp;';
					}

				}
				else {

					/*if($ac_import[20] != 'on')*/
					print '&nbsp;&nbsp;<A href="javascript:void(0)" onclick="cf=confirm(\'Вы действительно хотите назначить Клиента на себя?\');if (cf)trashClient(\''.$client['clid'].'\',\'uncold\');"><i class="icon-user-1 blue" title="Это Свободный Клиент. Назначить на себя?"></i></A>&nbsp;&nbsp;';
					//else print '&nbsp;&nbsp;<A href="javascript:void(0)" title="Это Свободный Клиент. Вы не можете управлять этим"><i class="icon-user-1 blue"></i></A>';

				}

				print 'Ключевой: ';

				if ( $client['fav'] != 'yes' ) {

					print '<A href="javascript:void(0)" onclick="trashClient(\''.$client['clid'].'\',\'add_fav\');"><i class="icon-star-empty gray" title="Отметить Ключевым клиентом"></i></A>';
				}
				else {

					print '<A href="javascript:void(0)" onclick="trashClient(\''.$client['clid'].'\',\'del_fav\');"><i class="icon-star red" title="Снять отметку Ключевого клиента"></i></A>';
				}
				?>
			</DIV>
			<?php
		}
		else {

			print '<div class="warning m0 mb10 div-center"><i class="icon-attention red"></i>&nbsp;У вас нет доступа к редактированию записи</div>';

		}
		?>

		<div class="divider">Информация</div>

		<div id="cInfo" class="bgwhite flex-vertical p0 border--bottom box--child">

			<?php if ( $client['uid'] != '' ) { ?>
				<div class="flex-container p10">

					<div class="flex-string wp25 gray2">UID</div>
					<div class="flex-string wp75">
						<b class="Bold"><?= $client['uid'] ?></b>
						<?php
						if ( $outClientUrl != '' && $client['uid'] != '' ) {

							$outClientUrl = str_replace( "{uid}", $client['uid'], $outClientUrl );
							$outClientUrl = str_replace( "{login}", current_userlogin( $iduser1 ), $outClientUrl );
							print '<span class="button pull-aright"><a href="'.$outClientUrl.'" target="_blank" title="Переход в ИС"><i class="icon-forward"></i></a></span>';

						}
						?>
					</div>

				</div>
				<?php
			}
			if ( stripos( $tipuser, 'Руководитель' ) !== false && $clid > 0 ) {
				?>
				<div class="flex-container p10">

					<div class="flex-string wp25 gray2">ID записи</div>
					<div class="flex-string wp75">
						<b class="Bold"><?= $client['clid'] ?></b>
					</div>

				</div>
				<?php
			}
			?>
			<div class="flex-container p10">

				<div class="flex-string wp25 gray2">Автор</div>
				<div class="flex-string wp75 relativ">
					<div class="Bold"><?= $creator ?></div>
					<div class="pull-right noBold fs-09 blue"><?= $date_create ?></div>
				</div>

			</div>
			<?php if ( $client['editor'] != '' ) { ?>
				<div class="flex-container p10">

					<div class="flex-string wp25 gray2">Редактор</div>
					<div class="flex-string wp75 relativ">
						<div class="Bold"><?= $editor ?></div>
						<div class="pull-right noBold fs-09 blue"><?= $date_edit ?></div>
					</div>

				</div>
			<?php } ?>
			<div class="flex-container p10">

				<div class="flex-string wp25 gray2"><?= $fieldsNames['client']['iduser'] ?></div>
				<div class="flex-string wp75">
					<B class="red"><?= current_user( $client['iduser'] ); ?></B>&nbsp;
					<?php
					if ( get_accesse( (int)$clid ) == 'yes' && !$userRights['nouserchange'] ) {

						?>
						<a href="javascript:void(0)" onclick="editClient('<?= $clid ?>','change.user');" title="Изменить ответственного" class="dright gray blue"><i class="icon-pencil blue"></i></a>
						<?php

					}
					?>
				</div>

			</div>
			<div class="flex-container p10" id="field-type">

				<div class="flex-string wp25 gray2">Тип записи</div>
				<div class="flex-string wp75" id="type">
					<b class="broun"><?= strtr( $client['type'], $client_types ) ?></b>
				</div>

			</div>
			<?php
			//привязанный уровень прайса
			if ( $otherSettings['price'] ) {
				?>
				<div class="flex-container p10">

					<div class="flex-string wp25 gray2">Уровень цен</div>
					<div class="flex-string wp75">
						<b class="Bold"><?= strtr( $client['priceLevel'], $fieldsNames['price'] ) ?></b>
						<?php
						if ( $userRights['client']['edit'] && get_accesse( (int)$clid ) == "yes" ) {
							?>
							<a href="javascript:void(0)" onclick="editClient('<?= $client['clid'] ?>','change.priceLevel');" title="Изменить" class="dright gray blue"><i class="icon-pencil blue"></i></a>
						<?php } ?>
					</div>

				</div>
				<?php
			}
			?>

		</div>

		<div class="divider mt15 mb15">Детали</div>

		<div id="cMain" class="bgwhite flex-vertical border--bottom p0 box--child">

			<?php
			$head_print = 0;
			$doche      = $db -> getAll( "SELECT clid, title FROM {$sqlname}clientcat WHERE head_clid='$clid' and identity = '$identity'" );
			//print_r($doche);

			$re = $db -> query( "select fld_name, fld_title, fld_temp, fld_stat from {$sqlname}field where fld_tip='client' and fld_on='yes' and identity = '$identity' order by fld_order" );
			while ($da = $db -> fetch( $re )) {

				$field = '';

				if ( $da['fld_name'] == 'iduser' ) {

					//перенесено в блок выше

				}
				elseif ( $da['fld_name'] == 'idcategory' && $client['category'] > 0 ) { ?>
					<div class="flex-container p10" id="field-<?= $da['fld_name'] ?>">

						<div class="flex-string wp25 gray2"><?= $da['fld_title'] ?></div>
						<div class="flex-string wp75" id="<?= $da['fld_name'] ?>">
							<?= $client['category'] ?>
							<?php if ( $isAccess ) { ?>
								<a href="javascript:void(0)" onclick="edit_field('client','<?= $da['fld_name'] ?>','select','<?= $clid ?>')" title="Изменить отрасль" class="dright gray"><i class="icon-pencil blue"></i></a>
							<?php } ?>
						</div>

					</div>
					<?php
				}
				elseif ( $da['fld_name'] == 'clientpath' && $client['clientpath'] != '' ) { ?>
					<div class="flex-container p10" id="field-<?= $da['fld_name'] ?>">

						<div class="flex-string wp25 gray2"><?= $da['fld_title'] ?></div>
						<div class="flex-string wp75" id="<?= $da['fld_name'] ?>">
							<?= $client['clientpath'] ?>
							<?php if ( $isAccess ) { ?>
								<a href="javascript:void(0)" onclick="edit_field('client','<?= $da['fld_name'] ?>','select','<?= $clid ?>')" title="Изменить отрасль" class="dright gray"><i class="icon-pencil blue"></i></a>
							<?php } ?>
						</div>

					</div>
					<?php
				}
				elseif ( $da['fld_name'] == 'head_clid' ) { ?>
					<?php
					if ( (int)$client['head_clid'] > 0 ) {
						?>
						<div class="flex-container p10">

							<div class="flex-string wp25 gray2"><?= $da['fld_title'] ?></div>
							<div class="flex-string wp75">
								<A href="javascript:void(0)" onclick="openClient('<?= $client['head_clid'] ?>');" title="Открыть карточку"><i class="icon-commerical-building blue"></i><?= $client['head'] ?>
								</A>
							</div>

						</div>
					<?php } ?>
					<?php
					if ( !empty( $doche ) && $head_print == 0 ) {
						?>
						<div class="flex-container p10">

							<div class="flex-string wp25 gray2">Доч. организации</div>
							<div class="flex-string wp75 tagbox">
								<?php
								foreach ( $doche as $do ) {
									?>
									<A href="javascript:void(0)" onclick="openClient('<?= $do['clid'] ?>');" title="Открыть карточку" class="tags"><i class="icon-building"></i><?= $do['title'] ?>
									</A>
								<?php } ?>
							</div>

						</div>
						<?php
						//исключим многоразовый вывод с помощью счетчика.
						$head_print++;

					}

				}
				elseif ( $da['fld_name'] == 'address' && $client['address'] != "" ) { ?>
					<div class="flex-container p10" id="field-<?= $da['fld_name'] ?>">

						<div class="flex-string wp25 gray2"><?= $da['fld_title'] ?></div>
						<div class="flex-string wp75 text-wrap" id="<?= $da['fld_name'] ?>">

							<?php if ( $isAccess ) { ?>
								<a href="javascript:void(0)" onclick="edit_field('client','<?= $da['fld_name'] ?>','adres','<?= $clid ?>')" title="Изменить" class="dright gray"><i class="icon-pencil blue"></i></a>
							<?php } ?>
							<i class="icon-location blue"></i>&nbsp;<a href="https://maps.google.ru/maps?hl=ru&tab=wl&q=<?= $client['address'] ?>" target="_blank"><?= $client['address'] ?></a>

						</div>

					</div>
					<?php
				}
				elseif ( $da['fld_name'] == 'territory' && $client['territory'] > 0 ) { ?>
					<div class="flex-container p10" id="field-<?= $da['fld_name'] ?>">

						<div class="flex-string wp25 gray2"><?= $da['fld_title'] ?></div>
						<div class="flex-string wp75 text-wrap" id="<?= $da['fld_name'] ?>">

							<?php if ( $isAccess ) { ?>
								<a href="javascript:void(0)" onclick="edit_field('client','<?= $da['fld_name'] ?>','select','<?= $clid ?>')" title="Изменить территорию" class="dright gray"><i class="icon-pencil blue"></i></a>
							<?php } ?>
							<b><?= $client['territoryname'] ?></b>

						</div>

					</div>
					<?php
				}
				elseif ( $da['fld_name'] == 'phone' && $client['phone'] != "" ) {

					$phone_list = [];
					$phones     = yexplode(",", str_replace(";", ",", str_replace(" ", "", $client['phone'])));
					foreach ($phones as $phone) {

						//$phone = ( $isAccess && $userSettings['hideAllContacts'] != 'yes' ) ? $phone : hidePhone($phone);

						$phone_list[] = ( $isAccess && $userSettings['hideAllContacts'] != 'yes' ) ? '<span class="phonec phonenumber '.( is_mobile($phone) ? 'ismob' : '' ).'" data-pid="" data-clid="'.$clid.'" data-phone="'.prepareMobPhone($phone).'">'.formatPhoneUrl($phone, $clid).'</span>' : '<span class="phonec phonenumber">'.hidePhone($phone).'</span>';

					}
					$phone = implode("", $phone_list);
					?>
					<div class="flex-container p10">

						<div class="flex-string wp25 gray2"><?= $da['fld_title'] ?></div>
						<div class="flex-string wp75 xpmt">
							<?= $phone ?>
						</div>

					</div>
					<?php

				}
				elseif ( $da['fld_name'] == 'fax' && $client['fax'] != "" ) {

					$phone_list = [];
					$fax        = yexplode( ",", str_replace( ";", ",", str_replace( " ", "", $client['fax'] ) ) );
					foreach ( $fax as $phone ) {

						$phone_list[] = ( $isAccess && $userSettings['hideAllContacts'] != 'yes' ) ? '<span class="phonec phonenumber '.(is_mobile( $phone ) ? 'ismob' : '').'" data-pid="" data-clid="'.$clid.'" data-phone="'.prepareMobPhone( $phone ).'">'.formatPhoneUrl( $phone, $clid ).'</span>' : '<span class="phonec phonenumber">'.hidePhone($phone).'</span>';

					}
					$fax = implode( "", $phone_list );
					?>
					<div class="flex-container p10" id="field-<?= $da['fld_name'] ?>">

						<div class="flex-string wp25 gray2"><?= $da['fld_title'] ?></div>
						<div class="flex-string wp75 xpmt" id="<?= $da['fld_name'] ?>">
							<?= $fax ?>
						</div>

					</div>
					<?php

				}
				elseif ( $da['fld_name'] == 'site_url' && $client['site_url'] != "" ) { ?>
					<div class="flex-container p10" id="field-<?= $da['fld_name'] ?>">

						<div class="flex-string wp25 gray2"><?= $da['fld_title'] ?></div>
						<div class="flex-string wp75 text-wrap" id="<?= $da['fld_name'] ?>">
							<?= link_it( $client['site_url'] ) ?>
							<?php if ( $isAccess ) { ?>
								<a href="javascript:void(0)" onclick="edit_field('client','<?= $da['fld_name'] ?>','text','<?= $clid ?>')" title="Изменить сайт" class="dright gray"><i class="icon-pencil blue"></i></a>
							<?php } ?>
						</div>

					</div>
					<?php
				}
				elseif ( $da['fld_name'] == 'mail_url' && $client['mail_url'] != "" ) { ?>
					<div class="flex-container p10" id="field-<?= $da['fld_name'] ?>">

						<div class="flex-string wp25 gray2"><?= $da['fld_title'] ?></div>
						<div class="flex-string wp75" id="<?= $da['fld_name'] ?>">
							<?php
							$emails = explode( ",", str_replace( ";", ",", $client['mail_url'] ) );
							foreach ( $emails as $email ) {

								$apx = $ymEnable ? '&nbsp;(<A href="javascript:void(0)" onclick="$mailer.composeCard(\''.$clid.'\',\'\',\''.trim( $email ).'\');" title="Написать сообщение"><i class="icon-mail blue"></i></A>)&nbsp;' : "";

								print (( $isAccess && $userSettings['hideAllContacts'] != 'yes' ) ? link_it( $email ) : hideEmail($email)).$apx;

							}
							?>
						</div>

					</div>
					<?php
				}
				elseif ( $da['fld_name'] == 'pid' && ($client['pid'] != "") && ($client['pid'] != '0') ) { ?>
					<div class="flex-container p10" id="field-<?= $da['fld_name'] ?>">

						<div class="flex-string wp25 gray2"><?= $da['fld_title'] ?></div>
						<div class="flex-string wp75" id="<?= $da['fld_name'] ?>">
							<?php if ( $isAccess ) { ?>
								<A href="javascript:void(0)" onclick="viewPerson('<?= $client['pid'] ?>')" title="Просмотр"><i class="icon-user-1 blue"></i></A>
								<a href="javascript:void(0)" onclick="edit_field('client','<?= $da['fld_name'] ?>','select','<?= $clid ?>')" title="Изменить основной контакт" class="dright gray"><i class="icon-pencil blue"></i></a>
								<?= current_person( $client['pid'] ) ?>
							<?php } ?>
						</div>

					</div>
					<?php
				}
				elseif ( $da['fld_name'] == 'scheme' && $client['scheme'] != "" ) { ?>
					<div class="flex-container p10" id="field-<?= $da['fld_name'] ?>">

						<div class="flex-string wp25 gray2"><?= $da['fld_title'] ?></div>
						<div class="flex-string wp75" id="<?= $da['fld_name'] ?>">
							<?php if ( $isAccess ) { ?>
								<a href="javascript:void(0)" onclick="edit_field('client','<?= $da['fld_name'] ?>','textarea','<?= $clid ?>')" title="Изменить схему принятия решений" class="dright gray"><i class="icon-pencil blue"></i></a>
							<?php } ?>
							<?= nl2br( $client['scheme'] ) ?>
						</div>

					</div>
					<?php
				}
				elseif ( $da['fld_name'] == 'tip_cmr' && $client['tip_cmr'] != "" ) { ?>
					<div class="flex-container p10" id="field-<?= $da['fld_name'] ?>">

						<div class="flex-string wp25 gray2"><?= $da['fld_title'] ?></div>
						<div class="flex-string wp75" id="<?= $da['fld_name'] ?>">
							<b><?= $client['tip_cmr'] ?></b>
							<?php if ( $isAccess ) { ?>
								<a href="javascript:void(0)" onclick="edit_field('client','<?= $da['fld_name'] ?>','select','<?= $clid ?>')" title="Изменить тип отношений" class="dright gray"><i class="icon-pencil blue"></i></a>
							<?php } ?>
						</div>

					</div>
					<?php
				}
				elseif ( $client[ $da['fld_name'] ] != '' && $da['fld_name'] == 'des' && $userRights['client']['edit'] ) {
					?>
					<div class="flex-container p10 relativ1" id="field-<?= $da['fld_name'] ?>">

						<div class="flex-string wp25 gray2"><?= $da['fld_title'] ?></div>
						<div class="flex-string wp75" id="<?= $da['fld_name'] ?>">
							<div class="noBold fs-09 noscroll text-wrap" style="overflow-y: auto; max-height: 300px;"><?= link_it( nl2br( $client['des'] ) ) ?></div>
						</div>
						<?php if ( $isAccess ) { ?>
							<div class="pull-aright wp100 text-right">
								<a href="javascript:void(0)" onclick="editClient('<?= $clid ?>','change.desсription')" class="gray blue" title="Добавить примечание"><i class="icon-plus"></i></a>
								<a href="javascript:void(0)" onclick="edit_field('client','<?= $da['fld_name'] ?>','textarea','<?= $clid ?>')" title="Изменить описание" class="dright gray"><i class="icon-pencil blue"></i></a>
							</div>
						<?php } ?>

					</div>
					<?php
				}
				elseif ( $da['fld_stat'] != 'yes' ) {

					if ( $client[ $da['fld_name'] ] != '' && $da['fld_temp'] != 'textarea' ) {

						if ( $da['fld_temp'] == "datum" ) {

							//if ($isAccess) $field .= '<a href="javascript:void(0)" onclick="edit_field(\'client\',\''.$da['fld_name'].'\',\''.$da['fld_temp'].'\',\''.$clid.'\')" title="Изменить дату" class="dright gray"><i class="icon-pencil blue"></i></a>';
							$field .= '<b class="green">'.format_date_rus_name( $client[ $da['fld_name'] ] ).'</b>';

						}

						elseif ( $da['fld_temp'] == "adres" ) {

							//if ($isAccess) $field .= '<a href="javascript:void(0)" onclick="edit_field(\'client\',\''.$da['fld_name'].'\',\''.$da['fld_temp'].'\',\''.$clid.'\')" title="Изменить адрес" class="dright gray"><i class="icon-pencil blue"></i></a>';
							$field .= '<i class="icon-location blue"></i>&nbsp;<a href="http://maps.google.ru/maps?hl=ru&tab=wl&q='.$client[ $da['fld_name'] ].'" target="_blank">'.$client[ $da['fld_name'] ].'</a>';

						}
						else {

							$field .= $client[ $da['fld_name'] ];

						}
						?>
						<div class="flex-container p10" id="field-<?= $da['fld_name'] ?>">

							<div class="flex-string wp25 gray2"><?= $da['fld_title'] ?></div>
							<div class="flex-string wp75 text-wrap" id="<?= $da['fld_name'] ?>" style="max-height:250px">
								<?= ($isAccess && $da['fld_temp'] != 'hidden' ? '<a href="javascript:void(0)" onclick="edit_field(\'client\',\''.$da['fld_name'].'\',\''.$da['fld_temp'].'\',\''.$clid.'\')" title="Изменить данные" class="dright gray"><i class="icon-pencil blue"></i></a>' : '') ?>
								<div class="fs-09 text-wrap"><?= nl2br( $field ) ?></div>
							</div>

						</div>
						<?php
					}
					elseif ( $client[ $da['fld_name'] ] != '' && $da['fld_temp'] == 'textarea' ) {

						$field .= $client[ $da['fld_name'] ];
						?>
						<div class="flex-container p10" id="field-<?= $da['fld_name'] ?>">

							<div class="flex-string wp25 gray2"><?= $da['fld_title'] ?></div>
							<div class="flex-string wp75 text-wrap" id="<?= $da['fld_name'] ?>">
								<?= ($isAccess ? '<a href="javascript:void(0)" onclick="edit_field(\'client\',\''.$da['fld_name'].'\',\'textarea\',\''.$clid.'\')" title="Изменить данные" class="dright gray"><i class="icon-pencil blue"></i></a>' : '') ?>
								<div class="noBold fs-09 text-wrap"><?= link_it( nl2br( $field ) ) ?></div>
							</div>

						</div>
						<?php

					}

				}

			}
			if ( $isAccess ) { ?>
				<div class="flex-container p10" id="field-append">

					<div class="flex-string1 wp100" id="append">

						<div class="fcontainer other-btn text-center" onclick="edit_field('client','append','select','<?= $clid ?>')" title="Добавить поле">
							<i class="icon-plus"></i>Добавить поле
						</div>

					</div>

				</div>
			<?php } ?>

		</div>

	</DIV>
<?php
$recv_on = $db -> getOne( "select fld_on from {$sqlname}field where fld_name='recv' and identity = '$identity'" );

if ( $recv_on == 'yes' ) {

	if ( file_exists( $rootpath.'/cash/'.$fpath.'requisites.json' ) ) {
		$file     = file_get_contents( $rootpath.'/cash/'.$fpath.'requisites.json' );
	}
	else {
		$file     = file_get_contents( $rootpath.'/cash/requisites.json' );
	}
	$recvName = json_decode( $file, true );

	?>
	<DIV id="detail_<?= $client['clid'] ?>" class="block">

		<fieldset class="fcontainer1 p0 bgwhite">

			<legend><?= $lang['all']['Details'] ?></legend>
			<?php
			if ( $userRights['client']['edit'] && get_accesse( (int)$clid ) == "yes" ) {
				?>
				<DIV class="batton-edit pr10">
					<a href="javascript:void(0)" onclick="editClient('<?= $clid ?>','change.recvisites');"><i class="icon-pencil broun"></i><?= $lang['all']['Edit'] ?>
					</a>
				</DIV>
				<?php
			}

			if ( $client['type'] != 'person' ) {

				$json = get_client_recv( $clid );
				$recv = json_decode( $json, true );

				$castUrName = $recv['castUrName'] == '' ? $recv['castName'] : $recv['castUrName'];

				if ( !empty( $recv ) ) {
					?>
					<div class="togglerbox hand mb10 ml5 fs-09 blue" data-id="recv" title="Детали. Показать/Скрыть">
						<i class="icon-angle-down" id="mapic" title="<?= $lang['all']['More'] ?> <?= $lang['all']['Show'] ?>/<?= $lang['all']['Hide'] ?>"></i>&nbsp;<?= $lang['all']['Show'] ?>/<?= $lang['all']['Hide'] ?>
					</div>
					<div id="recv" class="hidden flex-vertical fs-09 border--bottom box--child">

						<div class="flex-container p10">

							<div class="flex-string wp25 gray2">Юр. Название</div>
							<div class="flex-string wp75">
								<?= ($castUrName != '' ? $castUrName : '--') ?>
							</div>

						</div>
						<div class="flex-container p10">

							<div class="flex-string wp25 gray2">Юр. Название (кратко)</div>
							<div class="flex-string wp75">
								<?= ($recv['castUrNameShort'] != '' ? $recv['castUrNameShort'] : '--') ?>
							</div>

						</div>
						<div class="flex-container p10">

							<div class="flex-string wp25 gray2">Юр. Адрес</div>
							<div class="flex-string wp75">
								<?= ($recv['castUrAddr'] != '' ? $recv['castUrAddr'] : '--') ?>
							</div>

						</div>

						<div class="flex-container wp50 p10">

							<div class="flex-string wp25 gray2"><?= $recvName['recvInn'] ?></div>
							<div class="flex-string wp75">
								<?= ($recv['castInn'] != '' ? $recv['castInn'] : '--') ?>
							</div>

						</div>
						<div class="flex-container wp50 p10">

							<div class="flex-string wp25 gray2"><?= $recvName['recvKpp'] ?></div>
							<div class="flex-string wp75">
								<?= ($recv['castKpp'] != '' ? $recv['castKpp'] : '--') ?>
							</div>

						</div>

						<div class="flex-container wp50 p10">

							<div class="flex-string gray2"><?= $recvName['recvOkpo'] ?></div>
							<div class="flex-string">
								<?= ($recv['castOkpo'] != '' ? $recv['castOkpo'] : '--') ?>
							</div>

						</div>
						<div class="flex-container wp50 p10">

							<div class="flex-string gray2"><?= $recvName['recvOgrn'] ?></div>
							<div class="flex-string">
								<?= ($recv['castOgrn'] != '' ? $recv['castOgrn'] : '--') ?>
							</div>

						</div>

						<div class="flex-container p10">

							<div class="flex-string wp25 gray2"><?= $recvName['recvBankName'] ?></div>
							<div class="flex-string wp75">
								<?= ($recv['castBank'] != '' ? $recv['castBank'] : '--') ?>
							</div>

						</div>
						<div class="flex-container p10">

							<div class="flex-string wp25 gray2"><?= $recvName['recvBankBik'] ?></div>
							<div class="flex-string wp75">
								<?= ($recv['castBankBik'] != '' ? $recv['castBankBik'] : '--') ?>
							</div>

						</div>
						<div class="flex-container p10">

							<div class="flex-string wp25 gray2"><?= $recvName['recvBankKs'] ?></div>
							<div class="flex-string wp75">
								<?= ($recv['castBankKs'] != '' ? $recv['castBankKs'] : '--') ?>
							</div>

						</div>
						<div class="flex-container p10">

							<div class="flex-string wp25 gray2"><?= $recvName['recvBankRs'] ?></div>
							<div class="flex-string wp75">
								<?= ($recv['castBankRs'] != '' ? $recv['castBankRs'] : '--') ?>
							</div>

						</div>
						<div class="flex-container p10">

							<div class="flex-string wp25 gray2">Руководитель</div>
							<div class="flex-string wp75">
								<?= ($recv['castDirName'] != '' ? $recv['castDirName'] : '--') ?>
							</div>

						</div>
						<div class="flex-container p10">

							<div class="flex-string wp25 gray2">Руководитель (подпись)</div>
							<div class="flex-string wp75">
								<?= ($recv['castDirSignature'] != '' ? $recv['castDirSignature'] : '--') ?>
							</div>

						</div>
						<div class="flex-container p10">

							<div class="flex-string wp25 gray2">Должность</div>
							<div class="flex-string wp75">
								<?= ($recv['castDirStatus'] != '' ? $recv['castDirStatus'] : '--') ?>
							</div>

						</div>
						<div class="flex-container p10">

							<div class="flex-string wp25 gray2">Должность (подпись)</div>
							<div class="flex-string wp75">
								<?= ($recv['castDirStatusSig'] != '' ? $recv['castDirStatusSig'] : '--') ?>
							</div>

						</div>
						<div class="flex-container p10">

							<div class="flex-string wp25 gray2">Действует на основании</div>
							<div class="flex-string wp75">
								<?= ($recv['castDirOsnovanie'] != '' ? $recv['castDirOsnovanie'] : '--') ?>
							</div>

						</div>
					</div>
					<?php
				}
				else {
					print 'Реквизиты не заполнены';
				}

			}

			if ( $client['type'] == 'person' ) {

				$json = get_client_recv( $clid );
				$recv = json_decode( $json, true );

				$castUrName = $recv['castUrName'] == '' ? $recv['castName'] : $recv['castUrName'];

				if ( !empty( $recv ) ) {
					?>
					<div class="togglerbox hand pb10 blue" data-id="recvp" title="Детали. Показать/Скрыть">
						<i class="icon-angle-down" id="mapic" title="<?= $lang['all']['More'] ?> <?= $lang['all']['Show'] ?>/<?= $lang['all']['Hide'] ?>"></i>&nbsp;<?= $lang['all']['Show'] ?>/<?= $lang['all']['Hide'] ?>
					</div>
					<div id="recvp" class="hidden flex-vertical">

						<div class="flex-container p10">

							<div class="flex-string wp25 gray2">ФИО (полностью)</div>
							<div class="flex-string wp75">
								<?= $castUrName ?>
							</div>

						</div>
						<div class="flex-container p10">

							<div class="flex-string wp25 gray2">Паспорт</div>
							<div class="flex-string wp75">
								Серия <b><?= $recv['castInn'] ?></b> №
								<b><?= $recv['castKpp'] ?></b> от
								<b><?= format_date_rus_name( $recv['castDirStatus'] ) ?></b>
							</div>

						</div>
						<?php if ( $recv['castDirOsnovanie'] != '' ) { ?>
							<div class="flex-container p10">

								<div class="flex-string wp25 gray2">Действителен до</div>
								<div class="flex-string wp75">
									<?= format_date_rus_name( $recv['castDirOsnovanie'] ) ?>
								</div>

							</div>
						<?php } ?>
						<div class="flex-container p10">

							<div class="flex-string wp25 gray2">Кем выдан</div>
							<div class="flex-string wp75">
								<?= $recv['castDirStatusSig'] ?>
							</div>

						</div>
						<div class="flex-container p10">

							<div class="flex-string wp25 gray2">Дата рождения</div>
							<div class="flex-string wp75">
								<?= $recv['castBank'] ?>
							</div>

						</div>
						<div class="flex-container p10">

							<div class="flex-string wp25 gray2">Место рождения</div>
							<div class="flex-string wp75">
								<?= $recv['castBankKs'] ?>
							</div>

						</div>
						<div class="flex-container p10">

							<div class="flex-string wp25 gray2">Прописка (Страна)</div>
							<div class="flex-string wp75">
								<?= $recv['castBankRs'] ?>
							</div>

						</div>
						<div class="flex-container p10">

							<div class="flex-string wp25 gray2">Прописка (Область)</div>
							<div class="flex-string wp75">
								<?= $recv['castBankBik'] ?>
							</div>

						</div>
						<div class="flex-container p10">

							<div class="flex-string wp25 gray2">Прописка (Индекс)</div>
							<div class="flex-string wp75">
								<?= $recv['castOkpo'] ?>
							</div>

						</div>
						<div class="flex-container p10">

							<div class="flex-string wp25 gray2">Прописка (Город)</div>
							<div class="flex-string wp75">
								<?= $recv['castOgrn'] ?>
							</div>

						</div>
						<div class="flex-container p10">

							<div class="flex-string wp25 gray2">Прописка (Улица, дом, квартира)</div>
							<div class="flex-string wp75">
								<?= $recv['castDirName'] ?>
							</div>

						</div>
						<div class="flex-container p10">

							<div class="flex-string wp25 gray2">ФИО Сотрудника</div>
							<div class="flex-string wp75">
								<?= $recv['castDirSignature'] ?>
							</div>

						</div>
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