<?php
/* ============================ */
/*         SalesMan CRM         */
/* ============================ */
/* (C) 2018 Vladislav Andreev   */
/*       SalesMan Project       */
/*        www.isaler.ru         */
/*        ver. 2019.x           */

/* ============================ */

use Salesman\Client;
use Salesman\Currency;

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

global $userRights;

$clid   = (int)$_REQUEST['clid'];
$pid    = (int)$_REQUEST['pid'];
$did    = (int)$_REQUEST['did'];
$action = untag( $_REQUEST['action'] );

//Найдем тип сделки, которая является Сервисной
if ( isServices( $did ) ) {
	$isper = 'yes';
}

//массив данных по клиенту
$data = get_dog_info( $did, "yes" );

//print_r($data);

$user  = current_user( $data['iduser'] );
$autor = current_user( $data['autor'] );

if ( $data['clid'] > 0 ) {
	$client = current_client($data['clid']);
}
if ( $data['clid'] < 1 && $data['pid'] > 0 ) {
	$client = current_person($data['pid']);
}

if ( $data['idcategory'] > 0 ) {

	$dogstatus  = current_dogstepname( $data['idcategory'] );
	$dogcontent = current_dogstepcontent( $data['idcategory'] );

}

$cotract = $db -> getRow( "select number, datum_start from {$sqlname}contract where deid='".$data['dog_num']."' and identity = '$identity'" );

$mycomp = $db -> getOne( "select name_shot from {$sqlname}mycomps where id='".$data['mcid']."' and identity = '$identity'" );

if ( $data['close'] == 'yes' ) {

	$statusclose = $db -> getRow( "SELECT title, content FROM {$sqlname}dogstatus WHERE sid='".$data['sid']."' and identity = '$identity'" );
	$concurent   = current_client( $data['coid'] );

}

// Проверка на доступность редактирования
$isAccess = (get_accesse( 0, 0, (int)$did ) == "yes" && $data['close'] != 'yes') || $isadmin == 'on';

$direction = $db -> getOne( "SELECT title FROM {$sqlname}direction WHERE id = '".$data['direction']."' and identity = '$identity'" );

?>
<DIV class="fcontainer relativ bgwhite p0">

	<div class="not--mob fs-12">

		<div class="flex-container box--child">

			<div class="flex-string wp50 mwp50 flh-13">
				<div class="inline Bold"><?= $fieldsNames['dogovor']['datum_plan'] ?></div>
				<div class="inline">
					<i class="icon-calendar-1 blue"></i><b class="blue"><?= get_date( $data['datum_plan'] ) ?></b>
					<?php
					if ( $isAccess && $data['close'] != 'yes' ) {
						?>
						<a href="javascript:void(0)" onclick="editDogovor('<?= $data['did'] ?>','change.datum_plan');" title="Изменить плановую дату" class="fs-09 gray"><i class="icon-pencil blue"></i></a>
						<?php
					}
					?>
				</div>
			</div>

			<?php
			if ( $data['close'] == 'yes' ) {

				print '
				<div class="flex-string wp50 mwp50 flh-13 text-right gray2">
				
					<div class="inline Bold">Закрыта</div> 
					<div class="inline"><i class="icon-calendar-1 green"></i><b class="green">'.get_date( $data['datum_close'] ).'</b></div>
				
				</div>
				';

			}
			if ( $isAccess && $data['close'] != 'yes' ) {
				?>
				<div class="flex-string wp50 text-right">

					<?php
					$fi = $db -> getRow( "SHOW COLUMNS FROM {$sqlname}dogovor LIKE 'isFrozen'" );
					if ( $otherSettings['dateFieldForFreeze'] != '' && $fi['Field'] != '' ) {

						$freezIcon = "bluemint";
						$freezText = "Разморозить";

						if ( !$data['isFrozen'] ) {

							$freezIcon = "green-lite";
							$freezText = "Заморозить";

							?>
							<div onclick="editDogovor('<?= $did ?>','change.freeze');" class="inline transparent hand text-center hidden-iphone w50" tooltip-type="primary" tooltip="<b><?= $freezText ?></b>" tooltip-position="top">
								<i class="icon-snowflake-o <?= $freezIcon ?> fs-16 tooltips" tooltip-type="primary" tooltip="<b><?= $freezText ?></b>" tooltip-position="top"></i>
							</div>
							<?php

						}
						else {

							?>
							<div onclick="editDogovor('<?= $did ?>','change.unfreeze');" class="inline transparent hand text-center hidden-iphone w100" tooltip-type="primary" tooltip="<b><?= $freezText ?></b>" tooltip-position="top">
								<i class="icon-snowflake-o <?= $freezIcon ?> fs-16 tooltips" tooltip-type="primary" tooltip="<b><?= $freezText ?></b>" tooltip-position="top"></i>
								<span class="bluemint fs-07 block"><?= ($data[ $otherSettings['dateFieldForFreeze'] ] != '' ? modifyDatetime( $data[ $otherSettings['dateFieldForFreeze'] ], ["format" => 'd.m.y'] ) : "") ?></span>
							</div>
							<?php

						}
					}
					?>

					<div onclick="editDogovor('<?= $did ?>','close');" class="inline transparent hand green text-right hidden-iphone w50" tooltip-type="success" tooltip="<b>Закрыть</b>" tooltip-position="top">
						<i class="icon-lock-open fs-16 tooltips" tooltip-type="success" tooltip="<b>Закрыть</b>" tooltip-position="top"></i>
					</div>
					<span class="visible-iphone">
					<a href="javascript:void(0)" onclick="editDogovor('<?= $did ?>','close');" class=" button greenbtn text-right w100"><i class="icon-lock-open"></i> Закрыть</a>
				</span>

				</div>
				<?php
			}
			?>
			<div class="flex-string mt20 fs-07 gray2">
				Открыто: <b><?= get_date( $data['datum'] ) ?></b> &nbsp;/&nbsp;Автор:
				<b class="Bold blue"><?= current_user( $data['autor'] ) ?></b>
			</div>
		</div>

	</div>

	<div class="divider mt10">Этап и Воронка по сделке</div>

	<div>

		<?php

		$mFunnel = getMultiStepList( [
			"did"   => $did,
			"steps" => true
		] );

		$stepInHold = customSettings( 'stepInHold' );

		$ss = !empty( $mFunnel ) ? " and idcategory IN (".implode( ",", array_keys( $mFunnel ) ).")" : "";
		$ord = !empty( $mFunnel ) ? " FIELD(idcategory, ".implode( ",", array_keys( $mFunnel ) ).")" : "title";

		$res = $db -> getAll( "SELECT * FROM {$sqlname}dogcategory WHERE identity = '$identity' $ss ORDER BY $ord" );

		$w = 100 / (count( $res ) + 1) - 0.1;

		$steps    = [];
		$substeps = '';

		foreach ( $res as $da ) {

			$stepDayID = $db -> getOne( "SELECT datum FROM {$sqlname}steplog WHERE did='".$did."' and step = '".$da['idcategory']."'" );
			$stepDay   = diffDateTime( $stepDayID );

			if ( $stepDay == '0' ) {
				$stepDay = '';
			}

			if ( $da['title'] < 20 ) {
				$color = 'gray';
			}
			elseif ( is_between((int)$da['title'], 20, 60 ) ) {
				$color = 'green';
			}
			elseif ( is_between((int)$da['title'], 60, 90 ) ) {
				$color = 'blue';
			}
			elseif ( is_between((int)$da['title'], 90, 100 ) ) {
				$color = 'red';
			}

			$bg = 'progress-'.$color;

			if ( $da['idcategory'] == $data['idcategory'] ) {

				$s = 'current';
				$t = 'Текущий этап';
				$a = 'tooltip-type="primary"';

				$currStep      = $da['title'];
				$currStepTitle = $da['content'];
				$currStepDate  = datetimeru2datetime( $stepDayID );
				$currStepColor = $color;

			}
			else {

				$s = '';
				$t = ($data['close'] != 'yes') ? 'Перейти на этап' : 'Смена этапа не доступна - Текущий статус '.$lang['face']['DealName'][1].': <b>закрыт</b>';
				$a = 'tooltip-type="gray"';

			}

			$g = ($stepDayID > 0) ? '<br>Последнее изменение <b>'.$stepDay.'</b> назад' : '';

			$steps[] = [
				"day"     => $stepDay,
				"stpDeal" => $da['idcategory'],
				"step"    => $da['title'],
				"title"   => $da['content'],
				"color"   => $color,
				"cur"     => $s,
				"txt"     => $t,
				"type"    => $a,
				"change"  => $g
			];

		}

		if ( !empty( $mFunnel ) && !array_key_exists($data['idcategory'], $mFunnel)) {

			print '<div class="warning mb10"><b class="red">Внимание:</b> текущий этап сделки выпал из воронки. Текущий этап - <b>'.$dogstatus.'%</b> - '.$dogcontent.'</div>';

		}

		$stepres = $db -> getAll( "SELECT * FROM {$sqlname}steplog WHERE did = '$did' ORDER BY datum" );
		?>

		<div class="flex-container not--mob box--child">

			<!--Лог изменения этапов-->
			<div class="flex-string wp90 border-box text-right hidden-iphone">
				<?php
				if ( !empty( $stepres ) ) {
					?>
					<div class="tagsmenuToggler hand relativ inline" data-id="fhelper">
						<span class="fs-07 blue"><i class="icon-help-circled"></i> Лог</span>
						<div class="tagsmenu fly1 right hidden" id="fhelper" style="right:0; top: 100%">
							<div class="blok p10 w350 fs-09">
								<?php
								foreach ( $stepres as $stp ) {

									print '
									<div class="flex-container box--child mt5 p5 text-left infodiv">
										<div class="flex-string wp25">'.get_sfdate( $stp['datum'] ).'</div>
										<div class="flex-string wp75">
											<div class="Bold fs-11">'.current_dogstepname( $stp['step'] ).'%</div>
											<div class="gray2 fs-09 em">'.current_dogstepcontent( $stp['step'] ).'</div>
										</div>
									</div>
									';

								}
								?>
							</div>
						</div>
					</div>
				<?php } ?>
			</div>

		</div>
		<div class="flex-container float box--child">

			<div class="flex-string w120 border-box pl10 hand stepDeal">

				<div class="tagsmenuToggler hand relativ" data-id="fhelper">

					<a href="javascript:void(0)" onclick="$(body).animate({scrollTop: $('.stepDeal').offset().top - parseInt( $('.fixx').actual('height') + 50 ) }, 500)" class="<?= $currStepColor ?> currentStep">
						<?= $currStep ?>%
					</a>

					<?php
					if ( $isAccess && ( ( $userRights['deal']['edit'] && $close != "yes" ) || $isadmin == 'on' || ($close == "yes" && $userRights['deal']['editclosed'] ) ) ) {
						?>
						<div class="tagsmenu fly1 left hidden" id="fhelper">
							<div class="blok w400 fs-09 p0 pt10 pb10" style="max-width:70vw; max-height: 50vh;">

								<table style="width:400px">
									<?php
									foreach ( $steps as $key => $stp ) {

										$s   = ($stp['step'] <= $currStep) ? $stp['color']."bg-dark" : 'graybg-sub';
										$cls = ($stp['step'] == $currStep) ? 'orangebg-sub' : '';
										$pos = ($key > 1) ? 'top' : 'bottom';

										print '
											<tr class="stepline text-left ha tooltips1 '.$cls.'" tooltip="<b>'.$stp['txt'].'</b><hr> '.$stp['title'].$stp['change'].'" tooltip-position="'.$pos.'" onclick="editDogovor(\''.$did.'\',\'change.step\',\''.$stp['stpDeal'].'\')" '.$stp['type'].'>
												<td class="fs-09 no-border center pl5 wp15">'.$stp['day'].'</td>
												<td class="step1 p0 '.$s.' w5"></td>
												<td class="Bold fs-12 no-border pl10 text-center '.$stp['color'].' wp20">'.$stp['step'].'%</td>
												<td class="fs-10 flh-10 right no-border pl10 pr10">
													'.$stp['title'].'
													'.($stp['stpDeal'] == $stepInHold['step'] ? '&nbsp;<i class="icon-snowflake-o bluemint"></i>' : '').'
													<!--'.($data['isFrozen'] ? '&nbsp;<i class="icon-snowflake-o bluemint"></i>' : '').'-->
												</td>
											</tr>';

									}
									?>
								</table>

							</div>
						</div>
						<?php
					}
					?>

				</div>

			</div>
			<div class="flex-string float currentStepTitle p0">
				<div class="<?= $currStepColor ?> fs-12"><?= $currStepTitle.($data['idcategory'] == $stepInHold['step'] ? '&nbsp;<i class="icon-snowflake-o bluemint"></i>' : '') ?></div>
				<?php if ( $currStepDate ) { ?>
					<div class="gray pt5">с <?= $currStepDate ?></div>
				<?php } ?>
			</div>

		</div>

	</div>

	<?php
	$lead = $db -> getOne( "SELECT id FROM {$sqlname}leads WHERE did = '$did' and identity = '$identity'" );
	if ( $lead > 0 ) {

		print '
		<div class="infodiv m5 mt20">
			Создано по входящему интересу <b>№ '.$lead.'</b> -
			<A href="javascript:void(0)" onclick="editLead(\''.$lead.'\',\'view\');" title="Просмотр" class="sbutton">просмотр</a>
		</div>';

	}

	$entry = $db -> getOne( "SELECT ide FROM {$sqlname}entry WHERE did = '$did' and identity = '$identity'" );
	if ( $entry > 0 ) {

		print
			'<div class="attention m5 mt20">
			Создано по Обращению <b>№ '.$entry.'</b> -
			<A href="javascript:void(0)" onclick="editEntry(\''.$entry.'\',\'view\');" title="Просмотр" class="sbutton">просмотр</a>
		</div>';

	}
	?>

	<?php
	if ( $isper == 'yes' ) {

		if ( $data['datum_start'] == '0000-00-00' ) {

			print '
			<div class="warning m0 p10 red Bold center-text uppercase">
				Не заполнен период. <a href="javascript:void(0)" onclick="editDogovor(\''.$did.'\',\'change.period\')" class="button redbtn">Заполнить</a>
			</div>
			';

		}
		else {

			$idt = (diffDate2( $data['datum_end'], current_datum() ) < 0) ? 'red' : 'blue';

			$days = diffDate2( $data['datum_end'] );

			print '
			<hr class="mt10 p0">
			<DIV class="p10 mb10" data-step="13" data-intro="<h1>Этапы.</h1>Текущий этап и действия по смене этапа" data-position="left">
				
				<a href="javascript:void(0)" onclick="editDogovor(\''.$did.'\',\'change.period\')" class="gray pull-aright"><i class="icon-pencil"></i></a>
				
				<div class="uppercase fs-07 gray2">Период '.$lang['face']['DealName'][1].'</div> 
				
				<div class="fs-12 mt10 '.$idt.'">
					<b>'.format_date_rus( $data['datum_start'] ).'&nbsp;-&nbsp;'.format_date_rus( $data['datum_end'] ).'</b> [ осталось <b>'.$days.'</b> '.getMorph( $days, 'day' ).' ]
				</div>
				
			</DIV>
			';

		}
		?>

		<div class="success m5 flex-container text-left">

			<div class="flex-string wp25"><i class="icon-arrows-cw green icon-5x pull-left"></i>
			</div>
			<div class="flex-string wp75">

				<div class="green uppercase Bold mb10">Данная сделка является Сервисной</div>

				<div class="Bold">Это значит, что по данной сделке предусмотрено периодическое выставление счетов и актов.</div>
				<div class="mt5">Закрытие данной сделки может быть связано только с завершением оказания периодических услуг.</div>

			</div>

		</div>

	<?php } ?>

	<?php if ( $data['close'] == "yes" ) { ?>

		<div class="divider mt20 mb15 uppercase"><b class="red">Результат</b></div>

		<div class="flex-vertical not--mob p0 border--bottom">

			<div class="flex-container p10">

				<div class="flex-string wp25 gray2"><?= $fieldsNames['dogovor']['kol_fact'] ?></div>
				<div class="flex-string wp75">
					<b class="red fs-12"><?= num_format( $data['kol_fact'] ) ?></b>&nbsp;<?= $valuta ?>
				</div>

			</div>
			<div class="flex-container p10">

				<div class="flex-string wp25 gray2">Дата факт.</div>
				<div class="flex-string wp75"><b><?= format_date_rus_name( $data['datum_close'] ) ?></b></div>

			</div>
			<div class="flex-container p10">

				<div class="flex-string wp25 gray2">Статус</div>
				<div class="flex-string wp75">
					<div class="fs-11 red Bold"><?= $statusclose['title'] ?></div>
					<div class="fs-09 noBold gray2"><?= $statusclose['content'] ?></div>
				</div>

			</div>
			<?php
			if ( $data['co_kol'] > 0 && $concurent != '' ) { ?>
				<div class="flex-container p10">

					<div class="flex-string wp25 gray2">Выиграл конкурент</div>
					<div class="flex-string wp75">
						<div class="Bold fs-12">
							<a href="javascript:void(0)" onclick="viewClient('<?= $data['coid'] ?>')"><i class="icon-flag red"></i><?= $concurent ?>
							</a>
						</div>
						<div class="fs-09">
							Цена конкурента:
							<b class="red"><?= num_format( $data['co_kol'] ) ?></b> <?= $valuta ?>
						</div>
					</div>

				</div>
				<?php
			}
			if ( $data['des_fact'] != 'null' & $data['des_fact'] > '' ) { ?>
				<div class="flex-container p10">

					<div class="flex-string wp25 gray2">Комментарий</div>
					<div class="flex-string wp75"><?= $data['des_fact'] ?></div>

				</div>
			<?php } ?>

		</div>

	<?php } ?>

	<div class="divider mt20 mb15 uppercase"><b class="">Основное</b></div>

	<div class="flex-vertical not--mob p0 border--bottom">

		<?php
		if ( $data['uid'] != '' ) {
			?>
			<div class="flex-container p10">

				<div class="flex-string wp25 gray2">UID</div>
				<div class="flex-string wp75">
					<b class="Bold"><?= $data['uid'] ?></b>
					<?php
					if ( $outDealUrl != '' && $data['uid'] != '' ) {

						$outDealUrl = str_replace( "{uid}", $data['uid'], $outDealUrl );
						$outDealUrl = str_replace( "{login}", current_userlogin( $iduser1 ), $outDealUrl );
						print '<span class="button pull-aright"><a href="'.$outDealUrl.'" target="_blank" title="Переход в ИС"><i class="icon-forward"></i></a></span>';

					}
					?>
				</div>

			</div>
			<?php
		}
		if ( (stripos( $tipuser, 'Руководитель' ) !== false && $data['did'] != '') || $isadmin == 'on' ) {
			?>
			<div class="flex-container p10">

				<div class="flex-string wp25 gray2">ID записи</div>
				<div class="flex-string wp75">
					<b class="Bold"><?= $data['did'] ?></b>
				</div>

			</div>
			<?php
		}
		if ( $data['pid'] > 0 && !$data['clid'] ) {
			?>
			<div class="flex-container p10">

				<div class="flex-string wp100 gray2 mb5 fs-07 uppercase">Заказчик</div>
				<div class="flex-string wp100">
					<a href="javascript:void(0)" onclick="openPerson('<?= $data['pid'] ?>')" title="Открыть карточку" class="dright"><i class="icon-link-1 blue"></i></a>
					<?php if ( get_accesse( 0, (int)$data['pid'] ) == "yes" && $tipuser != 'Поддержка продаж' ) { ?>
						<a href="javascript:void(0)" onclick="editPerson('<?= $data['pid'] ?>','edit')" title="Редактировать" class="dright gray"><i class="icon-pencil blue"></i></a>
					<?php } ?>
					<div class="togglerbox hand inline Bold blue" data-id="clientContact"><?= current_person( $data['pid'] ) ?>&nbsp;<i class="icon-angle-down" id="mapic"></i></div>&nbsp;
					<a href="javascript:void(0)" onclick="viewPerson('<?= $data['pid'] ?>')" title="Просмотр"><i class="icon-info-circled blue"></i></a>
				</div>
				<div class="flex-string wp100 hidden" id="clientContact" data-block="person" data-id="<?= $data['pid'] ?>">

					<?php
					if($isAccess && $userSettings['hideAllContacts'] != 'yes'){

					$info = get_person_info( $data['pid'], 'yes' );

					$phone_list = [];
					$phone1     = yexplode( ",", str_replace( ";", ",", $info['tel'] ) );
					$phone2     = yexplode( ",", str_replace( ";", ",", $info['mob'] ) );

					$phones = array_merge( $phone1, $phone2 );
					foreach ( $phones as $phone ) {

						$phone_list[] = '<div class="phonec phonenumber '.(is_mobile( $phone ) ? 'ismob' : '').'" data-phone="'.prepareMobPhone( $phone ).'">'.formatPhoneUrl( $phone, $data['clid'], $data['pid'] ).'</div>';

					}

					$email_list = [];
					$emails     = yexplode( ",", str_replace( ";", ",", $info['mail'] ) );
					foreach ( $emails as $email ) {

						$apx = $ymEnable ? '&nbsp;(<A href="javascript:void(0)" onclick="$mailer.composeCard(\''.$data['clid'].'\',\'\',\''.trim( $email ).'\');" title="Написать сообщение"><i class="icon-mail blue"></i></A>)&nbsp;' : "";

						$email_list[] = link_it( $email ).$apx;

					}

					$phone = yimplode( ", ", $phone_list );
					$email = yimplode( ",", $email_list );
					?>
					<ul class="table">
						<?php if ( trim( $phone ) != '' ) { ?>
							<li class="xpmt">
								<div class="fs-09 gray">Телефоны</div>
								<div class="phoneblock"><?= $phone ?></div>
							</li>
						<?php } ?>
						<?php if ( trim( $email ) != '' ) { ?>
							<li>
								<div class="fs-09 gray">Email</div>
								<span><?= $email ?></span>
							</li>
						<?php } ?>
					</ul>
					<?php } ?>

				</div>

			</div>

			<?php
		}
		if ( $data['clid'] > 0 ) { ?>
			<div class="flex-container p10">

				<div class="flex-string wp100 gray2 mb5 fs-07 uppercase">Заказчик</div>
				<div class="flex-string wp100">
					<a href="javascript:void(0)" onclick="openClient('<?= $data['clid'] ?>')" title="Открыть карточку" class="dright"><i class="icon-link-1 blue"></i></a>
					<?php
					if ( get_accesse( (int)$data['clid'] ) == "yes" && $tipuser != 'Поддержка продаж' ) {
						?>
						<a href="javascript:void(0)" onclick="editClient('<?= $data['clid'] ?>','edit')" title="Редактировать" class="dright gray"><i class="icon-pencil blue"></i>&nbsp;&nbsp;</a>
					<?php } ?>
					<div class="togglerbox hand inline Bold blue" data-id="clientContact">
						<?= $client ?>&nbsp;<i class="icon-angle-down" id="mapic"></i>
					</div>&nbsp;
					<a href="javascript:void(0)" onclick="viewClient('<?= $data['clid'] ?>')" title="Просмотр"><i class="icon-info-circled blue"></i></a>
				</div>
				<div class="flex-string wp100 hidden" id="clientContact" data-block="client" data-id="<?= $data['clid'] ?>">

					<?php
					if($isAccess && $userSettings['hideAllContacts'] != 'yes'){

					$info = Salesman\Client ::info( (int)$data['clid'] );

					$phone_list = [];
					$phones     = yexplode( ",", str_replace( ";", ",", str_replace( " ", "", $info['client']['phone'] ) ) );

					foreach ( $phones as $phone ) {
						$phone_list[] = '<div class="phonec phonenumber '.(is_mobile( $phone ) ? 'ismob' : '').'" data-pid="" data-clid="'.$data['clid'].'" data-phone="'.prepareMobPhone( $phone ).'">'.formatPhoneUrl( $phone, $data['clid'], $data['pid'] ).'</div>';
					}

					$phone = yimplode( " ", $phone_list );

					$email_list = [];
					$emails     = yexplode( " ", str_replace( ";", ",", $info['client']['mail_url'] ) );

					foreach ( $emails as $email ) {

						$apx = $ymEnable ? '&nbsp;(<A href="javascript:void(0)" onclick="$mailer.composeCard(\''.$data['clid'].'\',\'\',\''.trim( $email ).'\');" title="Написать сообщение"><i class="icon-mail blue"></i></A>)&nbsp;' : "";

						$email_list[] = link_it( $email ).$apx;

					}

					$email = yimplode( "", $email_list );
					?>
					<ul class="table">
						<?php if ( trim( $phone ) != '' ) { ?>
							<li class="xpmt">
								<div class="fs-09 gray">Телефоны</div>
								<div class="phoneblock"><?= $phone ?></div>
							</li>
						<?php } ?>
						<?php if ( trim( $email ) != '' ) { ?>
							<li>
								<div class="fs-09 gray">Email</div>
								<span><?= $email ?></span>
							</li>
						<?php } ?>
						<?php if ( trim( $info['client']['address'] ) != '' ) { ?>
							<li>
								<div>Адрес:</div>
								<span><i class="icon-location blue"></i>&nbsp;<a href="http://maps.google.ru/maps?hl=ru&tab=wl&q=<?= $info['client']['address'] ?>" target="_blank"><?= $info['client']['address'] ?></a></span>
							</li>
						<?php } ?>
					</ul>
					<?php } ?>

				</div>

			</div>
			<?php
		}
		if ( $data['payer'] > 0 ) { ?>
			<div class="flex-container p10">

				<div class="flex-string wp100 gray2 mb5 fs-07 uppercase">Плательщик</div>
				<div class="flex-string wp100">

					<a href="javascript:void(0)" onclick="openClient('<?= $data['payer'] ?>')" title="Открыть карточку" class="dright"><i class="icon-link-1 blue"></i></a>
					<?php
					if ( get_accesse( (int)$data['payer'] ) == "yes" && $tipuser != 'Поддержка продаж' ) {
						?>
						<a href="javascript:void(0)" onclick="editClient('<?= $data['payer'] ?>','edit')" title="Редактировать" class="dright gray">
							<i class="icon-pencil blue"></i>&nbsp;&nbsp;
						</a>
					<?php } ?>
					<div class="togglerbox hand inline Bold blue" data-id="payerContact"><?= current_client( $data['payer'] ) ?>&nbsp;<i class="icon-angle-down" id="mapic"></i>
					</div>&nbsp;
					<a href="javascript:void(0)" onclick="viewClient('<?= $data['payer'] ?>')" title="Просмотр"><i class="icon-info-circled blue"></i></a>

				</div>
				<div class="flex-string wp100 hidden" id="payerContact" data-block="payer" data-id="<?= $data['payer'] ?>">

					<?php
					if($isAccess && $userSettings['hideAllContacts'] != 'yes'){

					$info = Client ::info( (int)$data['payer'] );

					$phone_list = [];
					$phones     = yexplode( ",", (string)str_replace( ";", ",", str_replace( " ", "", $info['client']['phone'] ) ) );
					foreach ( $phones as $phone ) {

						$phone_list[] = '<div class="phonec phonenumber '.(is_mobile( $phone ) ? 'ismob' : '').'" data-pid="" data-clid="'.$data['payer'].'" data-phone="'.prepareMobPhone( $phone ).'">'.formatPhoneUrl( $phone, $data['payer'], $data['pid'] ).'</div>';

					}
					$phone = yimplode( " ", $phone_list );

					$email_list = [];
					$emails     = yexplode( ",", str_replace( ";", ",", $info['client']['mail_url'] ) );
					foreach ( $emails as $email ) {

						$apx = $ymEnable ? '&nbsp;(<A href="javascript:void(0)" onclick="$mailer.composeCard(\''.$data['payer'].'\',\'\',\''.trim( $email ).'\');" title="Написать сообщение"><i class="icon-mail blue"></i></A>)&nbsp;' : "";

						$email_list[] = link_it( $email ).$apx;

					}
					$email = yimplode( "", $email_list );
					?>
					<ul class="table">
						<?php if ( trim( $phone ) != '' ) { ?>
							<li class="xpmt">
								<div class="fs-09 gray">Телефоны</div>
								<div class="phoneblock"><?= $phone ?></div>
							</li>
						<?php } ?>
						<?php if ( trim( $email ) != '' ) { ?>
							<li>
								<div class="fs-09 gray">Email</div>
								<span><?= $email ?></span>
							</li>
						<?php } ?>
						<?php if ( trim( $info['client']['address'] ) != '' ) { ?>
							<li>
							<div>Адрес:</div>
							<span><i class="icon-location blue"></i>&nbsp;<a href="//maps.google.ru/maps?hl=ru&tab=wl&q=<?= $info['client']['address'] ?>" target="_blank"><?= $info['client']['address'] ?></a></span>
							</li><?php } ?>
					</ul>
					<?php } ?>

				</div>

			</div>
			<?php
		}
		if ( count( $data['pid_list'] ) > 0 && in_array( 'pid_list', $fieldsOn['dogovor'] ) ) { ?>
			<div class="flex-container p10">

				<div class="flex-string wp100 gray2 mb5 fs-07 uppercase">Контакты</div>

				<?php
				$pids = $data['pid_list'];

				foreach ( $pids as $pid ) {

					$person = $db -> getRow( "SELECT person, ptitle FROM {$sqlname}personcat WHERE pid = '$pid' and identity = '$identity'" );

					?>
					<div class="flex-string wp100 mt10">

						<a href="javascript:void(0)" onclick="openPerson('<?= $pid ?>')" title="Открыть карточку" class="dright"><i class="icon-link-1 blue"></i></a>
						<a href="javascript:void(0)" onclick="viewPerson('<?= $pid ?>')" class="dright" title="Просмотр"><i class="icon-info-circled blue"></i>&nbsp;&nbsp;</a>
						<?php
						if ( get_accesse( 0, (int)$pid ) == "yes" && $tipuser != 'Поддержка продаж' ) {
							?>
							<a href="javascript:void(0)" onclick="editPerson('<?= $pid ?>','edit')" title="Редактировать" class="dright gray"><i class="icon-pencil blue"></i>&nbsp;&nbsp;</a>
						<?php } ?>
						<div class="togglerbox hand inline Bold blue" data-id="personContacts<?= $pid ?>">

							<?= $person['person'] ?>&nbsp;<i class="icon-angle-down" id="mapic"></i>
							<div class="fs-09 gray2 noBold"><?= $person['ptitle'] ?></div>

						</div>
					</div>
					<div class="flex-string wp100 hidden" id="personContacts<?= $pid ?>" data-block="person" data-id="<?= $pid ?>">
						<?php
						if($isAccess && $userSettings['hideAllContacts'] != 'yes'){


						$info = get_person_info( (int)$pid, 'yes' );

						$phone_list = [];
						$phones     = yexplode( ",", (string)str_replace( ";", ",", str_replace( " ", "", $info['tel'] ) ) );
						foreach ( $phones as $phone ) {

							$phone_list[] = '<div class="phonec phonenumber '.(is_mobile( $phone ) ? 'ismob' : '').'" data-pid="'.$pid.'" data-clid="'.$data['clid'].'" data-phone="'.prepareMobPhone( $phone ).'">'.formatPhoneUrl( $phone, (int)$data['clid'], (int)$pid ).'</div>';

						}

						$phones = yexplode( ",", str_replace( ";", ",", str_replace( " ", "", $info['mob'] ) ) );
						foreach ( $phones as $phone ) {

							$phone_list[] = '<div class="phonec phonenumber '.(is_mobile( $phone ) ? 'ismob' : '').'" data-pid="'.$pid.'" data-clid="'.$data['clid'].'" data-phone="'.prepareMobPhone( $phone ).'">'.formatPhoneUrl( $phone, $data['clid'], $pid ).'</div>';

						}

						$phone = implode( " ", $phone_list );

						$email_list = [];
						$emails     = yexplode( ",", str_replace( ";", ",", $info['mail'] ) );
						foreach ( $emails as $email ) {

							$email_list[] = link_it( $email ).($ymEnable ? '&nbsp;(<A href="javascript:void(0)" onclick="$mailer.composeCard(\''.$data['payer'].'\',\'\',\''.trim( $email ).'\');" title="Написать сообщение"><i class="icon-mail blue"></i></A>)&nbsp;' : '');

						}
						?>
						<ul class="table">
							<?php if ( count( $phone_list ) > 0 ) { ?>
								<li class="xpmt">
									<div class="fs-09 gray">Телефоны</div>
									<div class="phoneblock"><?= $phone ?></div>
								</li>
							<?php } ?>
							<?php if ( count( $email_list ) > 0 ) { ?>
								<li>
									<div class="fs-09 gray">Email</div>
									<span><?= implode( ", ", $email_list ) ?></span>
								</li>
							<?php } ?>
						</ul>
						<?php } ?>
					</div>
				<?php } ?>

			</div>
			<?php
		}
		//привязанный уровень прайса
		if ( $otherSettings['price'] && $data['clid'] > 0 ) {

			$priceLevel = getClientData( $data['clid'], "priceLevel" );
			?>
			<div class="flex-container p10">

				<div class="flex-string wp25 gray2">Уровень цен</div>
				<div class="flex-string wp75">
					<b class="Bold"><?= strtr( $priceLevel, $fieldsNames['price'] ) ?></b>
					<?php
					if ( $userRights['client']['edit'] && get_accesse( (int)$data['clid'] ) == "yes" ) {
					?>
					<a href="javascript:void(0)" onclick="editClient('<?= $data['clid'] ?>','change.priceLevel');" title="Изменить" class="dright gray blue"><i class="icon-pencil blue"></i></a>&nbsp;
					<?php } ?>
				</div>

			</div>
			<?php
		}
		?>
		<div class="flex-container p10">

			<div class="flex-string wp25 gray2"><?= $fieldsNames['dogovor']['iduser'] ?></div>
			<div class="flex-string wp75">
				<b><?= $user ?></b>&nbsp;
				<?php
				if ( ($isAccess && !$userRights['nouserchange']) || $isadmin == 'on' ) {
					?>
					<a href="javascript:void(0)" onclick="editDogovor('<?= $data['did'] ?>','change.user');" title="Изменить ответственного" class="dright gray"><i class="icon-pencil blue"></i></a>
				<?php } ?>&nbsp;
			</div>

		</div>

		<div class="flex-container p10">

			<div class="flex-string wp25 gray2"><?= $fieldsNames['dogovor']['kol'] ?></div>
			<div class="flex-string wp75">
				<b class="blue fs-12"><?= num_format( $data['kol'] ) ?></b>&nbsp;<?= $valuta ?>
			</div>

		</div>

		<?php
		$prov = getProviderSum( $did );
		$spec = getSpecaSum( $did );

		if ( $prov > 0 || ($spec > 0 && $isper != 'yes') ) {
			?>
			<div class="flex-container p10">

				<div class="flex-string wp25 gray2">Расходы</div>
				<div class="flex-string wp75 pt10">

					<?php if ( $prov > 0 ) { ?>
						<DIV class="fs-09 Bold gray2" title="Расходы (Поставщики и Партнеры)">Расходы (поставщики + партнер):</DIV>
						<DIV class="mb10 pl10 pt5">
							<b class="blue fs-12"><?= num_format( $prov ) ?></b> <?= $valuta ?>&nbsp;
						</DIV>
					<?php } ?>
					<?php if ( $spec > 0 && $isper != 'yes' ) { ?>
						<DIV class="fs-09 Bold gray2" title="Расходы по спецификации">Расходы по спецификации:</DIV>
						<DIV class="mb10 pl10 pt5">
							<b class="blue fs-12"><?= num_format( $spec ) ?></b> <?= $valuta ?>&nbsp;
						</DIV>
					<?php } ?>

				</div>

			</div>
			<?php
		}
		if ( $show_marga == 'yes' && $otherSettings['marga'] == 'yes' ) {

			if ( $data['kol_fact'] <= 0 ) {
				$mg = ( $data['kol'] > 0 ) ? ( $data['marga'] / $data['kol'] ) * 100 : 0;
			}
			else {
				$mg = ( $data['marga'] / $data['kol_fact'] ) * 100;
			}

			?>
			<div class="flex-container p10">

				<div class="flex-string wp25 gray2"><?= $fieldsNames['dogovor']['marg'] ?></div>
				<div class="flex-string wp75 relativ">
					<b class="blue fs-12"><?= num_format( $data['marga'] ) ?></b>&nbsp;<?= $valuta ?>
					<div class="pull-aright fs-12" title="Прибыльность"><b><?= num_format( $mg ); ?></b>%</div>
				</div>

			</div>
		<?php } ?>

		<?php
		if ( (int)$data['idcurrency'] > 0 ) {

			$currency = (new Salesman\Currency) -> currencyInfo( $data['idcurrency'] );
			$course   = (new Salesman\Currency) -> courseInfo( $data['idcourse'] );

			?>
			<div class="flex-container p10">

				<div class="flex-string wp25 gray2">Курс валюты</div>
				<div class="flex-string wp75">
					<div class="fs-14"><?php echo $course['course'] ?></div>
				</div>

			</div>
			<div class="flex-container p10">

				<div class="flex-string wp25 gray2">В валюте - <?php echo $currency['name'] ?></div>
				<div class="flex-string wp75">

					<div class="">
						<span class="fs-14 flh-14"><?php echo $currency['symbol']; ?><?php echo num_format( Currency ::currencyConvert( $data['kol'], $data['idcourse'], false ) ) ?></span> [ <?php echo $fieldsNames['dogovor']['kol']; ?> ]
					</div>
					<div class="">
						<span class="fs-14 flh-14 blue"><?php echo $currency['symbol']; ?><?php echo num_format( Currency ::currencyConvert( $data['marga'], $data['idcourse'], false ) ) ?></span> [ <?= $fieldsNames['dogovor']['marg']; ?> ]
					</div>

				</div>

			</div>
		<?php } ?>

	</div>

	<div class="divider blue mt20 mb10 uppercase"><b>Детальная информация</b></div>

	<div class="flex-vertical not--mob p0 border--bottom">

		<?php if ( $autor ) { ?>
			<div class="flex-container p10">

				<div class="flex-string wp25 gray2">Автор</div>
				<div class="flex-string wp75">
					<b><?= $autor ?></b>
				</div>

			</div>
		<?php } ?>
		<div class="flex-container p10">

			<div class="flex-string wp25 gray2"><?= $fieldsNames['dogovor']['mcid'] ?></div>
			<div class="flex-string wp75">
				<b><?= $mycomp ?></b>
			</div>

		</div>
		<?php
		if ( in_array( 'zayavka', $fieldsOn['dogovor'] ) && $data['zayavka'] != '' ) {
			?>
			<div class="flex-container p10">

				<div class="flex-string wp25 gray2"><?= $fieldsNames['dogovor']['zayavka'] ?></div>
				<div class="flex-string wp75">
					<b>№<?= $data['zayavka'] ?></b> на основании
					<b><?= $data['ztitle'] ?></b>
				</div>

			</div>
			<?php
		}
		if ( $isper != 'yes' && $data['datum_start'] != '0000-00-00' && in_array( 'period', $fieldsOn['dogovor'] ) ) {
			?>
			<div class="flex-container p10">

				<div class="flex-string wp25 gray2"><?= $fieldsNames['dogovor']['period'] ?></div>
				<div class="flex-string wp75">
					<b><?= format_date_rus( $data['datum_start'] )." - ".format_date_rus( $data['datum_end'] ) ?></b>
				</div>

			</div>
			<?php
		}
		if ( $data['adres'] != '' && in_array( 'adres', $fieldsOn['dogovor'] ) ) { ?>
			<div class="flex-container p10" id="field-adres">

				<div class="flex-string wp25 gray2"><?= $fieldsNames['dogovor']['adres'] ?></div>
				<div class="flex-string wp75" id="adres">
					<i class="icon-location blue"></i>&nbsp;<a href="http://maps.google.ru/maps?hl=ru&tab=wl&q=<?= $data['adres'] ?>" target="_blank"><?= $data['adres'] ?></a>
					<?php if ( $isAccess ) { ?>
						<a href="javascript:void(0)" onclick="edit_field('deal','adres','adres','<?= $did ?>')" title="Изменить адрес" class="dright gray">
							<i class="icon-pencil blue"></i></a>
					<?php } ?>
				</div>

			</div>
			<?php
		}

		$res = $db -> query( "select * from {$sqlname}field where fld_tip='dogovor' and fld_name LIKE '%input%' and fld_on='yes' and identity = '$identity' order by fld_order" );
		while ($da = $db -> fetch( $res )) {

			$field = '';

			if ( $data[ $da['fld_name'] ] != '' && $da['fld_temp'] != 'textarea' ) {

				if ( $da['fld_temp'] == "datum" ) {

					if ( $isAccess && $userRights['deal']['edit'] ) {
						$field .= '<a href="javascript:void(0)" onclick="edit_field(\'deal\',\''.$da['fld_name'].'\',\''.$da['fld_temp'].'\',\''.$did.'\')" title="Изменить дату" class="dright gray"><i class="icon-pencil blue"></i></a>';
					}
					$field .= '<b class="green">'.format_date_rus_name( $data[ $da['fld_name'] ] ).'</b>';

				}
				elseif ( $da['fld_temp'] == "datetime" ) {

					if ( $isAccess && $userRights['deal']['edit'] ) {
						$field = '<a href="javascript:void(0)" onclick="edit_field(\'deal\',\''.$da['fld_name'].'\',\''.$da['fld_temp'].'\',\''.$did.'\')" title="Изменить дату/время" class="dright gray"><i class="icon-pencil blue"></i></a>';
					}
					$field .= datetimeru2datetime( $data[ $da['fld_name'] ] );

				}
				elseif ( $da['fld_temp'] == "adres" ) {

					if ( $isAccess && $userRights['deal']['edit'] ) {
						$field .= '<a href="javascript:void(0)" onclick="edit_field(\'deal\',\''.$da['fld_name'].'\',\''.$da['fld_temp'].'\',\''.$did.'\')" title="Изменить адрес" class="dright gray"><i class="icon-pencil blue"></i></a>';
					}
					$field .= '<i class="icon-location blue"></i><a href="http://maps.google.ru/maps?hl=ru&tab=wl&q='.$data[ $da['fld_name'] ].'" target="_blank">'.$data[ $da['fld_name'] ].'</a>';

				}
				elseif ( $da['fld_temp'] == "inputlist" ) {

					if ( $isAccess && $userRights['deal']['edit'] ) {
						$field .= '<a href="javascript:void(0)" onclick="edit_field(\'deal\',\''.$da['fld_name'].'\',\'select\',\''.$did.'\')" title="Изменить" class="dright gray"><i class="icon-pencil blue"></i></a>';
					}
					$field .= $data[ $da['fld_name'] ];

				}
				else {

					if ( $isAccess && $userRights['deal']['edit'] ) {
						$field .= '<a href="javascript:void(0)" onclick="edit_field(\'deal\',\''.$da['fld_name'].'\',\''.$da['fld_temp'].'\',\''.$did.'\')" title="Изменить" class="dright gray"><i class="icon-pencil blue"></i></a>';
					}
					$field .= $data[ $da['fld_name'] ];

				}
				?>
				<div class="flex-container p10" id="field-<?= $da['fld_name'] ?>">

					<div class="flex-string wp25 gray2"><?= $da['fld_title'] ?></div>
					<div class="flex-string wp75 text-wrap" id="<?= $da['fld_name'] ?>" style="max-height:250px">
						<?= nl2br( $field ) ?>
					</div>

				</div>
				<?php

			}
			elseif ( $data[ $da['fld_name'] ] != '' && $da['fld_temp'] == 'textarea' ) {

				if ( $isAccess && $userRights['deal']['edit'] ) {
					$field .= '<a href="javascript:void(0)" onclick="edit_field(\'deal\',\''.$da['fld_name'].'\',\'textarea\',\''.$did.'\')" title="Изменить данные" class="dright gray"><i class="icon-pencil blue"></i></a>';
				}

				$field .= $data[ $da['fld_name'] ];

				?>
				<div class="flex-container p10" id="field-<?= $da['fld_name'] ?>">

					<div class="flex-string wp25 gray2"><?= $da['fld_title'] ?></div>
					<div class="flex-string wp75" id="<?= $da['fld_name'] ?>">
						<div class="noBold fs-09 text-wrap"><?= link_it( nl2br( $field ) ) ?></div>
					</div>

				</div>
				<?php

			}

		}
		if ( in_array( 'content', $fieldsOn['dogovor'] ) && $data['content'] != '' ) { ?>
			<div class="flex-container p10" id="field-content">

				<div class="flex-string wp25 gray2"><?= $fieldsNames['dogovor']['content'] ?></div>
				<div class="flex-string wp75" id="content">
					<?php if ( $isAccess && $userRights['deal']['edit'] ) { ?>
						<a href="javascript:void(0)" onclick="edit_field('deal','content','textarea','<?= $did ?>')" title="Изменить описание" class="dright gray"><i class="icon-pencil blue"></i></a>
					<?php } ?>
					<div class="noBold fs-09 text-wrap"><?= link_it( $data['content'] ) ?></div>
				</div>

			</div>
			<?php
		}
		if ( $data['tip'] != '' && in_array( 'tip', $fieldsOn['dogovor'] ) ) { ?>
			<div class="flex-container p10" id="field-tip">

				<div class="flex-string wp25 gray2"><?= $fieldsNames['dogovor']['tip'] ?></div>
				<div class="flex-string wp75" id="tip">
					<?php if ( $isAccess && $userRights['deal']['edit'] ) { ?>
						<a href="javascript:void(0)" onclick="edit_field('deal','tip','select','<?= $did ?>')" title="Изменить тип сделки" class="dright gray"><i class="icon-pencil blue"></i></a>
					<?php } ?>
					<?= current_dogtype( (int)$data['tip'] ) ?>
				</div>

			</div>
			<?php
		}
		if ( $data['direction'] != '' && in_array( 'direction', $fieldsOn['dogovor'] ) ) { ?>
			<div class="flex-container p10" id="field-direction">

				<div class="flex-string wp25 gray2"><?= $fieldsNames['dogovor']['direction'] ?></div>
				<div class="flex-string wp75" id="direction">
					<?php if ( $isAccess && $userRights['deal']['edit'] ) { ?>
						<a href="javascript:void(0)" onclick="edit_field('deal','direction','select','<?= $did ?>')" title="Изменить направление" class="dright gray"><i class="icon-pencil blue"></i></a>
					<?php } ?>
					<?= $direction ?>
				</div>

			</div>
			<?php
		}
		if ( $data['coid1'] != '' && $otherSettings['concurent'] ) {

			print '<div class="divider blue mt20 mb10 uppercase"><b>Конкуренты</b></div>';

			$con = yexplode( ";", $data['coid1'] );
			$col = count( $con );
			for ( $i = 0; $i < $col; $i++ ) {
				?>
				<div class="flex-container p10">

					<div class="flex-string wp25 gray2">Конкурент <?= $i + 1 ?></div>
					<div class="flex-string wp75">
						<a href="javascript:void(0)" onclick="viewClient('<?= $con[ $i ] ?>');" title="Открыть карточку"><?= current_client( $con[ $i ] ) ?></a>&nbsp;<a href="javascript:void(0)" onclick="openClient('<?= $con[ $i ] ?>')" title="Открыть карточку"><i class="icon-commerical-building broun"></i></a>
					</div>

				</div>
				<?php
			}

		}
		if ( $isAccess && $userRights['deal']['edit'] ) {
			?>
			<div class="flex-container p10" id="field-coid1">

				<div class="flex-string wp25 gray2"></div>
				<div class="flex-string" id="coid1">
					<a href="javascript:void(0)" onclick="edit_field('deal','coid1','multiselect','<?= $did ?>')" title="Добавить конкурента" class="dright blue"><i class="icon-plus-circled blue"></i>Конкурент</a>
				</div>

			</div>
			<?php
		}
		if ( $isAccess && $userRights['deal']['edit'] ) {
			?>
			<div class="flex-container p10" id="field-append">

				<div class="flex-string1 wp100" id="append">

					<div class="fcontainer other-btn" align="center" onclick="edit_field('deal','append','select','<?= $did ?>')" title="Добавить поле">
						<i class="icon-plus"></i>Добавить поле
					</div>

				</div>

			</div>
			<?php
		}
		?>

	</div>

</DIV>

<script>

	/*tooltips*/
	$('.tooltips').append("<span></span>");
	$('.tooltips:not([tooltip-position])').attr('tooltip-position', 'bottom');
	$(".tooltips").off('mouseenter');
	$(".tooltips").on('mouseenter', function () {
		$(this).find('span').empty().append($(this).attr('tooltip'));
	});
	/*tooltips*/

	// сортировка номеров телефонов
	// отключено. проблема - номера выводятся перемешанные
	// чтобы реализовать надо разделять типы номеров (телефон, факс, мобильный) на блоки отдельные
	/*
	$('.phoneblock').each(function (){

		var counts = $(this).find('.phonec').length

		$(this).find('.phonec').addClass('disable--select')

		if( counts > 1 ) {

			$(this).sortable({
				cursor: 'move',
				placeholder: "xitem-placeholder",
				update: function (event, ui) {

					var rids = []
					var xrid
					var type = $(this).closest('.flex-string').data('block')
					var id = $(this).closest('.flex-string').data('id')


					$(this).find('.phonec').each(function () {
						rids.push($(this).data('phone'))
					})

					xrid = rids.toString()

					//console.log(type, id)
					//console.log(xrid)

					var params = {
						"id": id,
						"type": type,
						"data": xrid
					}

					$.post('/content/helpers/helpers.php?action=sortphones', params, function(data){
						//console.log(data)
					});

				}

			})

		}

	})
	*/


</script>
