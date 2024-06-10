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

use Salesman\Deal;

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

$did    = $_REQUEST['did'];
$action = $_REQUEST['action'];

// Проверка на доступность редактирования
$isAccess = get_accesse(0, 0, $did) == "yes" || $isadmin == 'on';

if ( $acs_prava != 'on' && !$isAccess ) {
	print '<div class="zagolovok">Запрет просмотра</div>
	<div class="warning">
		<span><i class="icon-attention red icon-5x pull-left"></i></span>
		<b class="red uppercase">Внимание:</b><br><br>
		К сожалению Вы не можете просматривать данную информацию<br>У Вас отсутствует разрешение.<br>
	</div>';
	exit;
}

$deal = Deal ::info( $did );

//print_r($deal);

//оплаченные счета
$creditDo = 0;
foreach ( $deal['invoice'] as $i => $invoice ) {

	if ( $invoice['do'] == 'on' ) {
		$creditDo += $invoice['summa'];
	}

}
$сreditCount = count( (array)$deal['invoice'] );

//входящий лид
$lead = $db -> getOne( "SELECT id FROM ".$sqlname."leads WHERE did='".$did."' and identity = '$identity'" );

$lica = explode( ";", (string)$deal['pid_list'] );
$col  = count( $lica );

$stepDay = abs( round( diffDate2( $db -> getOne( "SELECT MAX(datum) as datum FROM ".$sqlname."steplog WHERE did='".$did."'" ) ) ) );

$dogstatus  = $deal['step']['steptitle'];
$dogcontent = $deal['step']['stepname'];

?>
<DIV class="zagolovok">
	<?= $lang['face']['DealName'][0] ?>: <?= $deal['title'] ?>&nbsp;
	<?php if ( $deal['close']['close'] == 'yes' ) { ?><i class="icon-lock red" title="Закрыта"></i><?php } ?>
	<span class="blue smalltxt">[ Cоздана: <?= get_date( $deal['date_create'] ) ?> ]</span>
</DIV>

<DIV id="cardview">

	<div class="flex-container">

		<div class="flex-string wp10 text-center pt10">

			<i class="icon-briefcase-1 icon-5x broun mt20"></i>

		</div>
		<div class="flex-string wp90">

			<div class="row wp100">

				<div class="column12 grid-3 gray2 fs-12 text-right"><?= $fieldsNames['dogovor']['iduser'] ?>:</div>
				<div class="column12 grid-9 fs-12">
					<b class="red"><?= $deal['user'] ?></b> [ Автор:&nbsp;<?= $deal['autorName'] ?> ]
				</div>

			</div>
			<div class="row wp100">

				<div class="column12 grid-3 gray2 fs-12 text-right"><?= $fieldsNames['dogovor']['datum_plan'] ?>:</div>
				<div class="column12 grid-9 fs-12"><?= format_date_rus( $deal['datum_plan'] ) ?></div>

			</div>

			<?php if ( (int)$deal['client']['clid'] > 0 ) { ?>
				<div class="row wp100">

					<div class="column12 grid-3 gray2 fs-12 text-right">Заказчик:</div>
					<div class="column12 grid-9 fs-12">
						<i class="icon-building broun"></i>&nbsp;<A href="javascript:void(0)" onclick="openClient('<?= $deal['client']['clid'] ?>')" title="Открыть"><?= $deal['client']['title'] ?></A>
					</div>

				</div>
				<div class="row wp100 hidden">

					<div class="column12 grid-3 gray2 fs-12 text-right">Плательщик:</div>
					<div class="column12 grid-9 fs-12">
						<i class="icon-building broun"></i>&nbsp;<A href="javascript:void(0)" onclick="openClient('<?= $deal['payer']['clid'] ?>')" title="Открыть карточку"><?= $deal['payer']['title'] ?></A>
					</div>

				</div>
			<?php } ?>

			<?php if ( (int)$deal['client']['clid'] < 1 && (int)$deal['person']['pid'][0] > 0 ) { ?>
				<div class="row wp100">

					<div class="column12 grid-3 gray2 fs-12 text-right">Клиент:</div>
					<div class="column12 grid-9 fs-12">
						<i class="icon-user-1 broun"></i>&nbsp;<A href="javascript:void(0)" onclick="openPerson('<?= $deal['person']['pid'] ?>')"><B class="blue"><?= $deal['person']['title'] ?></B>&nbsp;</A>
					</div>

				</div>
			<?php } ?>

			<?php if ( (int)$lead > 0 ) { ?>
				<div class="row wp100">

					<div class="column12 grid-3 gray2 fs-12 text-right">Входящий Лид:</div>
					<div class="column12 grid-9 fs-12">
						<b>№<?= $lead ?></b> -
						<a href="javascript:void(0)" onClick="editLead('<?= $lead ?>','view');" title="Просмотр">просмотр</a>
					</div>

				</div>
			<?php } ?>

			<?php if ( get_date( $deal['datum_start'] ) != '' ) { ?>
				<div class="row wp100">

					<div class="column12 grid-3 gray2 fs-12 text-right"><?= $fieldsNames['dogovor']['period'] ?>:</div>
					<div class="column12 grid-9 fs-12">
						<?= get_date( $deal['datum_start'] )." - ".get_date( $deal['datum_end'] ) ?>
					</div>

				</div>
			<?php } ?>

			<?php if ( (float)$deal['kol'] > 0 ) { ?>
				<div class="row wp100">

					<div class="column12 grid-3 gray2 fs-12 text-right">:</div>
					<div class="column12 grid-9 fs-12">

						<b class="blue"><?= num_format( $deal['kol'] ) ?></b> <?= $valuta ?>&nbsp;
						<?php if ( $deal['marga'] > 0 && $show_marga == 'yes' && $otherSettings['marga'] ) { ?>
							[ <b>Прибыль:</b>&nbsp;
							<b class="blue"><?= num_format( $deal['marga'] ) ?></b> <?= $valuta ?> ]
						<?php } ?>

					</div>

				</div>
			<?php } ?>

			<?php if ( $deal['close']['close'] == 'yes' ) { ?>
				<div class="row wp100">

					<div class="column12 grid-3 gray2 fs-12 text-right"><?= $fieldsNames['dogovor']['kol_fact'] ?>:</div>
					<div class="column12 grid-9 fs-12">

						<b class="blue"><?= num_format( $deal['close']['summa'] ) ?></b> <?= $valuta ?>&nbsp;
						Дата закрытия:&nbsp;<b class="red"><?= get_date( $deal['close']['date'] ) ?></b>

					</div>

				</div>
			<?php } ?>

			<?php if ( $сreditCount > 0 ) { ?>
				<div class="row wp100">

					<div class="column12 grid-3 gray2 fs-12 text-right">Платежи (счета):</div>
					<div class="column12 grid-9 fs-12">

						Выставлено счетов: <B class="blue"><?= $сreditCount ?></B>&nbsp;[ Оплачено
						<b><?= num_format( $creditDo ) ?></b> <?= $valuta ?> ]

					</div>

				</div>
			<?php } ?>

			<?php
			$deal['direction'] = ($deal['direction'] == 0) ? $dirDefault : $deal['direction'];
			$deal['tip']       = ($deal['tip'] == 0) ? $tipDefault : $deal['tip'];

			$mFunnel       = getMultiStepList( [
				"direction" => $deal['direction'],
				"tip"       => $deal['tip']
			] );
			$currentStepID = $deal['step']['stepid'];
			$isClose       = $deal['close']['close'];
			$steps         = '';
			$w             = !empty($mFunnel['steps']) ? 100 / (count( $mFunnel['steps'] )) - 0.5 : 0;

			//print_r($mFunnel);

			if ( !empty( $mFunnel['steps'] ) ) {
				?>
				<div class="row wp100">

					<div class="column12 grid-3 gray2 fs-12 text-right">Воронка:</div>
					<div class="column12 grid-9 fs-12 flex-container">

						<?php
						print '<b>'.$mFunnel['funnel'].'</b>';

						$stepres      = $db -> getAll( "SELECT * FROM ".$sqlname."steplog WHERE did = '$did' ORDER BY datum " );
						$currStepDate = $db -> getOne( "SELECT datum FROM ".$sqlname."steplog WHERE did = '$did' AND step = '$currentStepID'" );

						$currStep = current_dogstepname( $currentStepID );

						if ( $currStep < 20 ) {
							$currStepColor = 'gray';
						}
						elseif ( $currStep >= 20 and $currStep < 60 ) {
							$currStepColor = 'green';
						}
						elseif ( $currStep >= 60 and $currStep < 90 ) {
							$currStepColor = 'blue';
						}
						elseif ( $currStep >= 90 and $currStep <= 100 ) {
							$currStepColor = 'red';
						}
						?>

					</div>
				</div>
				<div class="row wp100">

					<div class="column12 grid-3 gray2 fs-12 text-right">Этап:</div>
					<div class="column12 grid-9">
						<div class="flex-container box--child wp95">

							<div class="flex-string wp100 relativ">

								<!--Лог изменения этапов-->
								<div class="pull-aright hidden-iphone">
									<div class="tagsmenuToggler hand mr15 relativ" data-id="fhelper">
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
								</div>

								<div class="<?= $currStepColor ?> currentStep1 fs-12">
									<?= $currStep ?>%
									<div class="inline <?= $currStepColor ?>"><?= current_dogstepcontent( $currentStepID ) ?>
									</div>
								</div>
								<?php if ( $currStepDate ) { ?>
									<div class="gray pt5">с <?= get_sfdate( $currStepDate ) ?></div>
								<?php } ?>

							</div>

						</div>
					</div>

				</div>
				<?php
			}
			if ( !array_key_exists( $currentStepID, (array)$mFunnel['steps'] ) ) {

				print '<div class="viewdiv"><b class="red">Внимание:</b> текущий этап сделки выпал из воронки. Текущий этап - <b>'.$dogstatus.'%</b> - '.$dogcontent.'</div>';

			}
			?>

			<?php if ( $deal['close']['close'] != 'yes' ) { ?>
				<div class="row wp100 hidden">

					<div class="column12 grid-3 gray2 fs-12 text-right">Последний этап:</div>
					<div class="column12 grid-9 fs-12">

						<?php
						if ( $dogstatus < 20 ) {
							$progressbg = ' progress-gray';
							$prcolor    = 'black';
						}
						elseif ( $dogstatus >= 20 and $dogstatus < 60 ) {
							$progressbg = ' progress-green';
							$prcolor    = 'white';
						}
						elseif ( $dogstatus >= 60 and $dogstatus < 90 ) {
							$progressbg = ' progress-blue';
							$prcolor    = 'white';
						}
						elseif ( $dogstatus >= 90 and $dogstatus <= 100 ) {
							$progressbg = ' progress-red';
							$prcolor    = 'white';
						}
						?>

						<DIV class="progressbar">
							<b><?= $dogstatus ?>%</b>
							<sup class="gray" title="Статус изменен <?= $stepDay ?> дн. назад"><?= $stepDay ?> д.&nbsp;&nbsp;(<?= $dogcontent ?>)</sup>
							<DIV id="test" class="progressbar-completed <?= $progressbg ?>" style="width:<?= $dogstatus ?>%; border: 1px solid #ddd" title="<?= $dogstatus." - ".$dogcontent ?>">
								<DIV class="status <?= $prcolor ?>"></DIV>
							</DIV>
						</DIV>

					</div>

				</div>
			<?php } ?>

			<?php if ( $deal['close']['close'] == 'yes' ) { ?>
				<div class="row wp100">

					<div class="column12 grid-3 gray2 fs-12 text-right">Статус закрытия:</div>
					<div class="column12 grid-9">

						<div class="blue Bold fs-12"><?= $deal['close']['status'] ?></div>
						<div class="gray2 fs-10 em"><?= $deal['close']['statustext'] ?></div>

					</div>

				</div>
			<?php } ?>

		</div>

	</div>

</div>

<hr>

<DIV id="more" class="ftabs" data-id="ccontainer" style="border:0; background: none;">

	<div id="ytabs">

		<ul class="gray flex-container blue">

			<li class="flex-string" data-link="bperson">Контакты</li>
			<li class="flex-string" data-link="bcomment"><?= $fieldsNames['dogovor']['content'] ?></li>
			<li class="flex-string" data-link="bcredit">Счета</li>
			<li class="flex-string" data-link="bhistory">История</li>
			<li class="flex-string" data-link="btodo">Напоминания</li>

		</ul>

	</div>
	<div id="ccontainer" style="overflow-y: auto; overflow-x: hidden; max-height: 250px">

		<div class="bperson  cbox pt10" style="height: 200px">

			<?php
			$res = $deal['person'];

			if ( empty( $res ) ) {
				print '<div class="row pad10">Нет контактов</div>';
			}

			foreach ( $res as $da ) {
				?>
				<div class="row ha">
					<div class="column grid-4">
						<b class="fs-12"><?= $da['title'] ?></b><br>
						<em class="gray2"><?= $da['post'] ?></em>
					</div>
					<div class="column grid-6">
						<?php
						if ( $da['phone'] != "" ) {

							$phone_list = [];
							$phones      = yexplode( ",", str_replace( ";", ",", str_replace( " ", "", $da['phone'] ) ) );
							foreach ($phones as $phone) {

								$ismob        = isPhoneMobile( $phone ) ? 'ismob' : '';
								$phone_list[] = ( $isAccess && $userSettings['hideAllContacts'] != 'yes' ) ? '<span class="phonec phonenumber '.$ismob.'" data-pid="'.$da['pid'].'" data-clid="'.$da['clid'].'" data-phone="'.prepareMobPhone( $phone ).'">'.formatPhoneUrl( $phone, $deal['client']['clid'], $da['pid'] ).'</span>' : '<span class="phonec phonenumber">'.hidePhone($phone).'</span>';

							}
							$phone = implode( ", ", $phone_list );
							print '<div>'.$phone.'</div>';

						}
						if ( $da['fax'] != "" ) {

							$phone_list = [];
							$phones        = yexplode( ",", str_replace( ";", ",", str_replace( " ", "", $da['fax'] ) ) );
							foreach ($phones as $phone) {

								$ismob        = isPhoneMobile( $phone ) ? 'ismob' : '';
								$phone_list[] = ( $isAccess && $userSettings['hideAllContacts'] != 'yes' ) ? '<span class="phonec phonenumber '.$ismob.'" data-pid="'.$da['pid'].'" data-clid="'.$da['clid'].'" data-phone="'.prepareMobPhone( $phone ).'">'.formatPhoneUrl( $phone, $deal['client']['clid'], $da['pid'] ).'</span>' : '<span class="phonec phonenumber">'.hidePhone($phone).'</span>';

							}
							$fax = implode( ", ", $phone_list );
							print '<div>'.$fax.'</div>';

						}
						if ( $da['mob'] != "" ) {

							$phone_list = [];
							$phones       = yexplode( ",", str_replace( ";", ",", str_replace( " ", "", $da['mob'] ) ) );
							foreach ($phones as $phone) {

								$ismob        = isPhoneMobile( $phone ) ? 'ismob' : '';
								$phone_list[] = ( $isAccess && $userSettings['hideAllContacts'] != 'yes' ) ? '<span class="phonec phonenumber '.$ismob.'" data-pid="'.$da['pid'].'" data-clid="'.$da['clid'].'" data-phone="'.prepareMobPhone( $phone ).'">'.formatPhoneUrl( $phone, $deal['client']['clid'], $da['pid'] ).'</span>' : '<span class="phonec phonenumber">'.hidePhone($phone).'</span>';

							}
							$mob = implode( ", ", $phone_list );
							print "<div>".$mob."</div>";

						}
						if ( $da['email'] != "" ) {

							$emails = yexplode( ",", $da['email'] );
							foreach ($email as $xemail) {

								$apx = $ymEnable && $xemail != '' ? '&nbsp;(<A href="javascript:void(0)" onclick="$mailer.composeCard(\'\',\''.$da['pid'].'\',\''.trim( $xemail ).'\');" title="Написать сообщение"><i class="icon-mail blue"></i></A>)&nbsp;' : '';
								//print '<div>'.link_it( $email ).$apx.'</div>';
								print (( $isAccess && $userSettings['hideAllContacts'] != 'yes' ) ? link_it( $xemail ) : hideEmail($xemail)).$apx;

							}

						}
						?>
					</div>
					<hr class="marg0 pad0">
				</div>
				<?php
			}
			?>

		</div>
		<div class="bcomment cbox pt10" style="height: 200px">

			<div class="row pad10 fs-11 flh-12"><?= nl2br( $deal['content'] ) ?></div>

		</div>
		<div class="bhistory cbox pt10" style="height: 200px">

			<?php
			$res = $db -> getAll( "select * from ".$sqlname."history WHERE did='".$did."' and identity = '$identity' ORDER BY datum DESC LIMIT 5" );

			if ( empty( $res ) ) {
				print '<div class="row pad10">Нет записей</div>';
			}

			foreach ( $res as $da ) {
				?>
				<div class="row ha pad5">

					<div class="column grid-1">
						<b><?= get_sfdate( $da['datum'] ) ?></b>
					</div>
					<div class="column grid-8">
						<div class="gray2 em fs-07"><?= $da['tip'] ?></div>
						<div class="fs-10"><?= nl2br( mb_substr( clean( $da['des'] ), 0, 101, 'utf-8' ) ) ?></div>
					</div>

				</div>
				<hr>
			<?php } ?>

		</div>
		<div class="bcredit  cbox pt10" style="height: 200px">

			<?php
			if ( empty( $deal['invoice'] ) ) {
				print '<div class="row pad10">Нет записей</div>';
			}

			foreach ( $deal['invoice'] as $i => $invoice ) {

				$day = round( diffDate2( $invoice['datum_credit'] ) );

				if ( $day < 0 && $invoice['do'] != "on" ) {
					$day = '<div class="fs-12"><b class="red">'.$day.'</b> дн.</div><span class="fs-09">Просрочено</span>';
				}
				elseif ( $day >= 0 && $invoice['do'] != "on" ) {
					$day = '<div class="fs-12"><b class="green">+'.$day.' дн</b>.</div><span class="fs-09">Ожидается</span>';
				}
				else {
					$day = '';
				}

				if ( $invoice['do'] != "on" ) {
					$doo = '<div class="fs-12"><b class="red">'.get_date( $invoice['datum_credit'] ).'</b></div><div><span class="fs-09">Ожидаемая дата</span></div>';
				}
				if ( $invoice['do'] == "on" ) {
					$doo = '<div class="fs-12"><b class="green">Оплачен</b></div><div><span class="fs-09">'.format_date_rus( $invoice['invoice_date'] ).'</span></div>';
				}

				print '
				<div class="row">
					<div class="column grid-2">
						<div class="fs-12"><b>№ '.$invoice['invoice'].'</b></div>
						<div class="fs-09 gray2">от '.get_date( $invoice['datum_credit'] ).'</div>
					</div>
					<div class="column grid-3">
						'.$day.'
					</div>
					<div class="column grid-3">
						'.$doo.'
					</div>
					<div class="column grid-2 fs-12"><b>'.num_format( $invoice['summa'] ).'</b> '.$valuta.'</div>
				</div>
				<hr class="pad0 marg0">
				';

			}
			?>

		</div>
		<div class="btodo    cbox pt10" style="height: 200px">

			<?php

			$res = $db -> getAll( "select * from ".$sqlname."tasks WHERE did='".$did."' and active = 'yes' and identity = '$identity' ORDER BY datum, totime DESC limit 2" );

			if ( empty( $res ) ) {
				print '
				<div class="row pad10">
					<a href="javascript:void(0)" onclick="addTask(\'\', \''.$deal['client']['clid'].'\',\''.$deal['person'][0]['pid'].'\',\''.$did.'\')" class="button">Добавить<i class="icon-calendar-inv"></i></a>
				</div>
				';
			}

			foreach ( $res as $da ) {
				?>
				<div class="row ha pad5">

					<div class="column grid-1">
						<div class="fs-11 Bold"><?= getTime( (string)$da['totime'] ) ?></div>
						<div class="fs-10 gray2"><?= get_date( (string)$da['datum'] ) ?></div>
					</div>
					<div class="column grid-8">
						<div class="fs-12 Bold"><?= $da['title'] ?></div>
						<div class="fs-09 em gray2"><?= $da['tip'] ?></div>
						<div><?= nl2br( mb_substr( clean( $da['des'] ), 0, 101, 'utf-8' ) ) ?></div>
					</div>

				</div>
				<hr>
			<?php } ?>

		</div>

	</div>

</DIV>

<hr>

<div class="button--pane text-right">

	<a href="javascript:void(0)" onclick="openDogovor('<?= $did ?>')" class="button"><i class="icon-briefcase-1"></i>Карточка <?= $lang['face']['DealName'][1] ?>
	</a>

</div>

<?php
$hooks -> do_action( "deal_view", $_REQUEST );
?>

<script>
	$(function () {

		var dwidth = $(document).width();
		var dialogWidth;
		var dialogHeight;

		var hh = $('#dialog_container').actual('height') * 0.9;
		var hh2 = hh - $('.zagolovok').actual('outerHeight') - $('.button--pane').actual('outerHeight') - 20 - $('.ftabs').actual('outerHeight');

		if (dwidth < 945) {
			dialogWidth = '90%';
			dialogHeight = '95vh';
			//$('#dialog').css({'height':dialogHeight});
			//$('#cardview').css({'height':'unset','max-height':hh2 + 30});
		}
		else {
			dialogWidth = '800px';
			//$('#dialog').css({'width':dialogWidth,'height':hh}).center();
			//$('#cardview').css({'overflow-y':'auto', 'height':'unset', 'max-height':hh2});
		}

		$('#dialog').css('width', dialogWidth);

		$('.ftabs').each(function () {

			$(this).find('li').removeClass('active');
			$(this).find('li:first-child').addClass('active');

			$(this).find('.cbox').addClass('hidden');
			$(this).find('.cbox:first-child').removeClass('hidden');

		});

		$('#dialog').center();

		ShowModal.fire({
			etype: 'dealView'
		});

	});

	$('#ytabs li').on('click', function () {

		var link = $(this).data('link');
		var id = $(this).closest('.ftabs').attr('id');

		$('#' + id + ' li').removeClass('active');
		$(this).addClass('active');

		$('#' + id + ' .cbox').addClass('hidden');
		$('#' + id + ' .' + link).removeClass('hidden');

	});
</script>