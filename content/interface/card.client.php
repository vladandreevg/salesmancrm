<?php
/* ============================ */
/*         SalesMan CRM         */
/* ============================ */
/* (C) 2016 Vladislav Andreev   */
/*       SalesMan Project       */
/*        www.isaler.ru         */
/*        ver. 2019.x           */
/* ============================ */

global $rootpath;

error_reporting( E_ERROR );

$clid = (int)$_REQUEST['clid'];
$action = $_REQUEST['action'];

require_once $rootpath."/inc/head.card.php";

$result = $db -> getRow( "select iduser, pid, type from ".$sqlname."clientcat WHERE clid = '$clid' and identity = '$identity'" );
$iduser = (int)$result["iduser"];
$pid    = (int)$result["pid"];
$ctype  = $result["type"];

$isdog_num = $db -> getOne( "SELECT deid FROM ".$sqlname."contract WHERE clid = '$clid' and identity = '$identity'" );

$haveAccesse = get_accesse( $clid ) == "yes";

if ( !isset( $title ) ) {

	print '
		<LINK rel="stylesheet" type="text/css" href="/assets/css/app.css">
		<LINK rel="stylesheet" href="/assets/css/fontello.css">
		<SCRIPT type="text/javascript" src="/assets/js/jquery/jquery-3.4.1.min.js"></SCRIPT>
		<SCRIPT type="text/javascript" src="/assets/js/jquery/jquery-migrate-3.0.0.min.js"></SCRIPT>
		<SCRIPT type="text/javascript" src="/assets/js/app.js"></SCRIPT>
		<div class="warning text-left" style="width:300px">
			<span><i class="icon-attention red icon-5x pull-left"></i></span>
			<b class="red uppercase">'.$lang['all']['Success'].':</b><br><br>
			'.$lang['msg']['CantFind'].'<br>
		</div>
		<script type="text/javascript">
			$(".warning").center();
		</script>
	';

	exit();

}

switch ($ctype) {
	case 'concurent':
		$clientName = $lang['agents']['Concurent'][0];
	break;
	case 'contractor':
		$clientName = $lang['agents']['Contractor'][0];
	break;
	case 'partner':
		$clientName = $lang['agents']['Partner'][0];
	break;
	default:
		$clientName = $lang['face']['ClientName'][0];
	break;
}

// выполняем фильтры
$tabs = $hooks -> apply_filters( "card_tab", $tabs, [
	"type" => $ctype,
	"clid" => $clid
] );

//print_r($tabs);
?>
<div class="fixx">

	<DIV id="head">

		<DIV id="ctitle">

			<input type="hidden" name="isCard" id="isCard" value="yes">
			<input type="hidden" name="card" id="card" value="client">
			<input type="hidden" name="clid" id="clid" value="<?= $clid ?>">

			<span data-step="1" data-intro="<h1>Тип и Название клиента.</h1>" data-position="bottom-middle-aligned">

				<span class="back2menu">
					<a href="/" title="Рабочий стол"><i class="icon-home"></i></a>
				</span>

				<span class="hidden-iphone">
					<b class="blue"><?= $clientName ?>:&nbsp;</b>
				</span>

				<span class="elipsis Bold">
					<span class="visible-iphone"><i class="icon-building"></i></span>
					<?= $title ?>
				</span>

			</span>

			<DIV id="close"><?= $lang['all']['Close'] ?></DIV>

		</DIV>

	</DIV>

	<DIV id="dtabs" data-step="2" data-intro="<h1>Вкладки по разделам</h1>" data-position="bottom-middle-aligned" class="table">

		<i class="icon-menu menu--card visible-iphone"></i>

		<UL class="disable--select">
			<LI class="current" id="tb0" onclick="settab('0')">
				<A href="#0">
					<span class="hidden-normal" title="<?= $lang['all']['Info'] ?>"><i class="icon-info-circled fs-09"></i></span>
					<?= $lang['all']['Info'] ?>
				</A>
			</LI>
			<LI class="hidden-normal" id="tb1" onclick="settab('1')">
				<A href="#1">
					<span class="hidden-normal" title="Дела & Активность"><i class="icon-calendar-inv fs-09"></i></span>
					Дела & Активность
				</A>
			</LI>
			<LI id="tb2" onclick="settab('2')">
				<A href="#2">
					<span class="hidden-normal" title="<?= $lang['face']['ContactsName'][0] ?>"><i class="icon-users-1 fs-09"></i></span>
					<span class="hidden-netbook"><?= $lang['face']['ContactsName'][0] ?></span>
				</A>
			</LI>
			<LI id="tb15" onclick="settab('15')" class="">
				<A href="#15">
					<span class="hidden-normal" title="<?= $lang['docs']['Doc'][1] ?>"><i class="icon-doc-text-inv fs-09"></i></span>
					<span class="hidden-netbook"><?= $lang['docs']['Doc'][1] ?></span>
				</A>
			</LI>
			<LI id="tbcbudjet" onclick="settab('cbudjet')" class="<?= (in_array( $ctype, [
				'contractor',
				'partner'
			] ) ? "" : "hidden for-remove") ?>">
				<A href="#cbudjet">
					<span class="hidden-normal" title="<?= $lang['finance']['Budjet'] ?>"><i class="icon-rouble fs-09"></i></span>
					<span class="hidden-netbook"><?= $lang['finance']['Budjet'] ?></span>
				</A>
			</LI>
			<LI id="tb12" onclick="settab('12')" class="<?= ($otherSettings['comment'] ? "" : "hidden for-remove") ?>">
				<A href="#12">
					<span class="hidden-normal" title="<?= $lang['face']['Comments'] ?>"><i class="icon-chat fs-09"></i></span>
					<span class="hidden-netbook"><?= $lang['face']['Comments'] ?></span>
				</A>
			</LI>
			<LI id="tb9" onclick="settab('9')" class="<?= ($otherSettings['profile'] ? "" : "hidden for-remove") ?>">
				<A href="#9">
					<span class="hidden-normal" title="<?= $lang['face']['Profile'] ?>"><i class="icon-book fs-09"></i></span>
					<span class="hidden-netbook"><?= $lang['face']['Profile'] ?></span>
				</A>
			</LI>
			<LI id="tb4" onclick="settab('4')" class="<?= ($ctype != 'concurent' ? "" : "hidden for-remove") ?>">
				<A href="#4">
					<span class="hidden-normal" title="<?= $lang['face']['DealsName'][0] ?>"><i class="icon-briefcase-1 fs-09"></i></span>
					<span class="hidden-netbook"><?= $lang['face']['DealsName'][0] ?></span>
				</A>
			</LI>
			<LI id="tbcdeals" onclick="settab('cdeals')" class="<?= ($ctype == 'concurent' ? "" : "hidden for-remove") ?>">
				<A href="#cdeals">
					<span class="hidden-normal" title="<?= $lang['face']['DealsName'][0] ?>"><i class="icon-briefcase fs-09"></i></span>
					<span class="hidden-netbook"><?= $lang['face']['DealsName'][0] ?></span>
				</A>
			</LI>
			<LI id="tb6" onclick="settab('6')" class="<?= ($acs_files == 'on' ? "" : "hidden for-remove") ?>">
				<A href="#6">
					<span class="hidden-normal hidden-notebook-normal" title="<?= $lang['face']['Files'] ?>"><i class="icon-floppy fs-09"></i></span>
					<span class="hidden-netbook hidden-notebook"><?= $lang['face']['Files'] ?></span>
				</A>
			</LI>
			<?php
			// добавляем вкладки из фильтров
			foreach ( $tabs as $tab ) {

				print '
				<LI id="tb'.$tab['name'].'" onclick="settab(\''.$tab['name'].'\')" class="'.$tab['class'].'">
					<A href="#'.$tab['name'].'">
						<span class="hidden-normal" title="'.$tab['title'].'"><i class="'.$tab['icon'].' fs-09"></i></span>
						<span class="hidden-netbook">'.$tab['title'].'</span>
					</A>
				</LI>
				';

			}
			?>

		</UL>
		<ul class="right hidden-iphone">
			<li id="tb600" class="<?= ($ymEnable ? "" : "hidden for-remove") ?> green right1">
				<a href="javascript:void(0)" onclick="$mailer.composeCard('<?= $clid ?>','<?= $pid ?>','');" title="<?= $lang['ymail']['ComposeMail'] ?>">
					<i class="icon-mail fs-07"></i>&nbsp;<span class="visible-iphone">Написать</span>
				</a>
			</li>
			<li id="tbmail" onclick="settab('mail')" class="<?= ($ymEnable ? "" : "hidden for-remove") ?> right1">
				<A href="#mail">
					<i class="icon-exchange fs-07"></i>&nbsp;<span class="hidden-netbook"><?= $lang['ymail']['Correspondence'] ?></span>
				</A>
			</li>
		</ul>

	</DIV>

</div>

<DIV class="fixbg"></DIV>

<DIV id="telo">

	<DIV class="leftcol" id="tab-0" data-id="info">

		<div id="fixblock" style="display: table; width: 100%;" data-step="3" data-intro="<h1>Блок Информации.</h1>В блоке выводится базовая информация о Клиенте" data-position="right">

			<fieldset class="relativ">

				<legend>
					<?= $lang['all']['Info'] ?>&nbsp;<a href="<?= $productInfo['site'] ?>/docs/43" target="blank" title="Как это работает?"><i class="icon-help-circled"></i></a>
				</legend>

				<div id="data-append" class="pull-left mt10"></div>

				<?php
				$iduser = (int)getClientData( $clid, "iduser" );
				if ( $haveAccesse || $isadmin == 'on' /* && $tipuser != 'Поддержка продаж'*/ ) {
					?>
					<DIV class="batton-edit pt10 pb10">

						<div class="inline">

							<?php
							if ( $userRights['client']['edit'] || $isadmin == 'on' ) {

								print '<a href="javascript:void(0)" onclick="editClient(\''.$clid.'\',\'edit\');" title="Изменить"><i class="icon-pencil broun"></i>&nbsp;'.$lang['all']['Edit'].'</a>&nbsp;&nbsp;';

							}
							?>

						</div>

						<div class="inline relativ" data-step="4" data-intro="<h1>Действия.</h1>Позволяют совершать манипуляции с записью" data-position="right">

							<a href="javascript:void(0)" title="<?= $lang['all']['Actions'] ?>" class="tagsmenuToggler"><i class="icon-angle-down broun" id="mapii"></i><?= $lang['all']['Actions'] ?>&nbsp;</a>

							<div class="tagsmenu left hidden">

								<div class="items noBold fs-09">

									<?php if ( $haveAccesse && $userSettings['historyAddBlock'] != 'yes' ) { ?>
										<div onclick="addHistory('','<?= $clid ?>');" class="item ha hand">
											<i class="icon-clock blue"></i>&nbsp;<?= $lang['all']['Add'] ?> <?= $lang['all']['Activity'] ?>
										</div>
									<?php } ?>

									<div onclick="addTask('', '<?= $clid ?>');" class="item ha hand">
										<i class="icon-calendar-inv green"></i>&nbsp;<?= $lang['all']['Add'] ?> <?= $lang['face']['TodoName'][0] ?>
									</div>

									<!--
									<div onclick="window.open('print.php?clid=<?= $clid ?>',this.target,'width=650,height=500,'+'location=no,toolbar=no,menubar=yes,status=no,resizeable=yes,scrollbars=yes')" title="<?= $lang['all']['Print'] ?>" class="item ha hand">
										<i class="icon-print blue"></i>&nbsp;<?= $lang['all']['Print'] ?></div>
									-->

									<div onclick="settab('0')" class="item ha hand" title="<?= $lang['all']['Refresh'] ?>">
										<i class=" icon-arrows-cw blue"></i>&nbsp;<?= $lang['all']['Refresh'] ?></div>

									<?php
									/**
									 * Редактирование клиента
									 */
									if ( $userRights['client']['edit'] || $isadmin == 'on' ) {

										print '<div onclick="editClient(\''.$clid.'\',\'edit\');" class="item ha hand" title="'.$lang['all']['Actions'].'"><i class="icon-pencil broun"></i>&nbsp;'.$lang['all']['Edit'].'</div>';

									}

									/**
									 * Смена ответственного
									 */
									if ( $haveAccesse && !$userRights['nouserchange'] ) {

										print '<div onclick="editClient(\''.$clid.'\',\'change.user\');" title="'.$lang['all']['UserChange'].'" class="item ha hand"><i class="icon-user-1 blue"></i>&nbsp;'.$lang['all']['UserChange'].'</div>';

									}

									/**
									 * Удаление клиента
									 */
									if ( $clid > 0 && $userRights['client']['delete'] && $tipuser != 'Поддержка продаж' ) {

										print '
										<div onclick="cf=confirm(\''.$lang['msg']['RealyDeleteThis'].'\');if (cf)deleteCCD(\'client\',\''.$clid.'\',\'card\')" title="'.$lang['all']['Delete'].'" class="item ha hand"><i class="icon-trash red"></i>&nbsp;'.$lang['all']['Delete'].'
										</div>';

									}
									?>

								</div>

							</div>

						</div>

					</DIV>
				<?php } ?>

				<DIV id="tab0"></DIV>

			</fieldset>

			<fieldset>

				<legend>Группы&nbsp;<a href="<?= $productInfo['site'] ?>/docs/50" target="blank" title="Как это работает?"><i class="icon-help-circled"></i></a></legend>

				<?php
				if ( $userRights['group'] ) {
					?>
					<div class="batton-edit mb10">
						<a href="javascript:void(0)" onclick="editGroup('','addtoGroup');">&nbsp;<i class="icon-plus-circled broun"></i><?= $lang['all']['Add'] ?></a>
					</div>
				<?php } ?>

				<DIV id="tabgroup" class="mb10"></DIV>

			</fieldset>

			<fieldset>

				<legend>Доступ к карточке</legend>

				<?php
				$json = get_client_info( $clid );
				$data = json_decode( $json, true );
				$user = yexplode( ';', (string)$data['dostup'] );

				if ( $haveAccesse && $tipuser != 'Поддержка продаж' && !in_array( $iduser1, $user ) ) {
					?>
					<div class="abs mb10">

						<DIV class="batton-edit">
							<a href="javascript:void(0)" onclick="editClient('<?= $clid ?>','change.dostup');"><i class="icon-pencil broun"></i><?= $lang['all']['Edit'] ?></a>
						</DIV>

					</div>
					<?php
				}
				?>
				<DIV id="carddostup" class="p5 mb5"></DIV>

			</fieldset>

		</div>

	</DIV>

	<DIV class="rightcol" id="tab-1" data-id="tasks">

		<?php
		$more = 0;
		if ( $isEntry == 'on' ) {
			$more++;
		}
		if ( $otherSettings['saledProduct'] ) {
			$more++;
		}
		if ( $otherSettings['potential'] ) {
			$more++;
		}
		if ( $otherSettings['credit'] ) {
			$more++;
		}

		if ( $more > 0 ) {
			?>

			<fieldset>

				<?php
				if ( $more > 1 ) {
					$legend = $lang['face']['More'];
				}
				else {
					if ( $isEntry == 'on' )
						$legend = $lang['all']['Entry'][1];
					if ( $otherSettings['saledProduct'] )
						$legend = $lang['all']['SaleHistory'];
					if ( $otherSettings['potential'] )
						$legend = $lang['all']['Potential'];
					if ( $otherSettings['credit'] )
						$legend = $lang['docs']['AddedInvoices'];
				}
				?>

				<legend><?= $legend ?></legend>

				<div id="clientMore" class="ftabs" data-id="container">

					<?php
					if ( $more > 1 ) {
						?>
						<div id="ytabs">

							<ul class="gray flex-container blue">

								<?php if ( $isEntry == 'on' ) { ?>
									<li class="flex-string" data-link="entry"><?= $lang['all']['Entry'][1] ?>
										<sup class="bullet red">0</sup></li>
								<?php } ?>
								<?php if ( $otherSettings['credit'] ) { ?>
									<li class="flex-string" data-link="invoices"><?= $lang['docs']['AddedInvoices'] ?>
										<sup class="bullet red">0</sup></li>
								<?php } ?>
								<?php if ( $otherSettings['saledProduct'] ) { ?>
									<li class="flex-string" data-link="products"><?= $lang['all']['SaleHistory'] ?>
										<sup class="bullet red">0</sup></li>
								<?php } ?>
								<?php if ( $otherSettings['potential'] ) { ?>
									<li class="flex-string" data-link="potencial"><?= $lang['all']['Potential'] ?>
										<sup class="bullet red">0</sup></li>
								<?php } ?>
							</ul>

						</div>
						<?php
					}
					?>
					<div id="container" class="fcontainer1">
						<?php if ( $isEntry == 'on' ) { ?>
							<div class="entry cbox hidden">
								<DIV class="batton-edit mt15 mb15">
									<a href="javascript:void(0)" onclick="getEntry()"><i class="icon-arrows-cw broun"></i><?= $lang['all']['Refresh'] ?></a>&nbsp;&nbsp;
									<a href="javascript:void(0)" onclick="editEntry('','edit','')"><i class="icon-plus-circled green"></i><?= $lang['all']['Add'] ?></a>
								</DIV>
								<DIV id="tabEntry" class="graybg-lite" style="overflow-y: auto; overflow-x: hidden; max-height: 300px"></DIV>
							</div>
						<?php } ?>
						<?php if ( $otherSettings['credit'] ) { ?>
							<div class="invoices cbox hidden">
								<DIV id="tabInvoices" style="overflow-y: auto; overflow-x: hidden; max-height: 300px"></DIV>
							</div>
						<?php } ?>
						<?php if ( $otherSettings['saledProduct'] ) { ?>
							<div class="products cbox hidden">
								<DIV id="tabProducts" style="overflow-y: auto; overflow-x: hidden; max-height: 300px"></DIV>
							</div>
						<?php } ?>
						<?php if ( $otherSettings['potential'] ) { ?>
							<div class="potencial cbox hidden">
								<DIV class="batton-edit mt15 mb15">
									<a href="javascript:void(0)" onclick="$('#tab11').load('/content/card/card.capacity.php?clid=<?= $clid ?>').append('<img src=/assets/images/loading.svg>')"><i class="icon-arrows-cw broun"></i><?= $lang['all']['Refresh'] ?></a>&nbsp;&nbsp;
									<a href="javascript:void(0)" onclick="doLoad('/content/card/card.capacity.php?action=add&clid=<?= $clid ?>')"><i class="icon-plus-circled green"></i><?= $lang['all']['Add'] ?></a>
								</DIV>
								<DIV id="tab11" style="overflow-y: auto; overflow-x: hidden; max-height: 300px"></DIV>
							</div>
						<?php } ?>
					</div>
				</div>

			</fieldset>

		<?php } ?>

		<fieldset data-step="5" data-intro="<h1>Напоминания.</h1>Здесь выводятся все напоминания по данному Клиенту/Контакту. Также можно <?= $lang['all']['Add'] ?> новое напоминание" data-position="left">

			<legend><i class="icon-calendar-1"></i><?= $lang['face']['TodosName'][0] ?></legend>

			<DIV class="table batton-edit">

				<a href="javascript:void(0)" onclick="cardload()"><i class="icon-arrows-cw broun"></i><?= $lang['all']['Refresh'] ?></a>&nbsp;&nbsp;
				<a href="javascript:void(0)" onclick="addTask('', '<?= $clid ?>');"><i class="icon-plus-circled green"></i><?= $lang['all']['Add'] ?></a>

			</DIV>
			<DIV id="tab10" class="fcontainer1 bgwhite mt10"></DIV>

		</fieldset>

		<fieldset data-step="6" data-intro="<h1>История активностей.</h1>Выводится История активностей по данной карточке" data-position="left">

			<legend><i class="icon-clock"></i><?= $lang['all']['Activity'] ?></legend>

			<div id="historyMore" class="ftabs" data-id="container">

				<div id="ytabs">

					<ul class="gray flex-container blue">

						<li class="flex-string" data-link="history"><?= $lang['face']['ActsName'][0] ?></li>
						<?php if ( $sip_active == 'yes' ) { ?>
							<li class="flex-string" data-link="calls"><?= $lang['face']['Calls'] ?></li>
						<?php } ?>
						<li class="flex-string" data-link="log" onclick="cardloadlog()"><?= $lang['all']['Logs'] ?></li>

					</ul>

				</div>
				<div id="container" class="fcontainer1 pt10">

					<div class="history cbox">

						<div class="relativ">

							<div class="ydropDown w200 fs-10" style="z-index:1;">

								<?php
								$tiphistory = yexplode( ",", (string)$_COOKIE['tiphistory'] );
								$atype = $db -> getAll( "SELECT id, title, color FROM ".$sqlname."activities WHERE identity = '$identity' ORDER BY title" );
								?>

								<span><i class="icon-filter blue"></i> Фильтр</span>
								<span class="ydropCount"><?= count( $tiphistory ) ?> выбрано</span>
								<i class="icon-angle-down pull-aright"></i>

								<div class="action hidden">Применить</div>

								<div class="yselectBox" style="max-height: 300px;">

									<div class="yunSelect noaction"><i class="icon-cancel-circled2"></i>Снять выделение
									</div>

									<?php
									foreach ( $atype as $data ) {

										$s = (in_array( $data['id'], $tiphistory )) ? "checked" : "";
										?>
										<div class="ydropString ellipsis">
											<label class="wp100">
												<input class="taskss" type="checkbox" name="tiphistory[]" id="tiphistory[]" value="<?= $data['id'] ?>" <?= $s ?>>
												<div class="bullet-mini" style="background: <?= $data['color'] ?>"></div>&nbsp;<?= $data['title'] ?>
											</label>
										</div>
										<?php
									}
									?>

								</div>

							</div>

							<?php
							if ( $haveAccesse ) {
								?>
								<DIV class="table pull-right Bold wp100 p5 text-right">
									<a href="javascript:void(0)" onclick="cardloadHist()"><i class="icon-arrows-cw broun"></i><?= $lang['all']['Refresh'] ?></a>&nbsp;&nbsp;
									<?php if ( $userSettings['historyAddBlock'] != 'yes' ) { ?>
										<a href="javascript:void(0)" onclick="addHistory('','<?= $clid ?>');"><i class="icon-plus-circled gred"></i><?= $lang['all']['Add'] ?></a>
									<?php } ?>
								</DIV>
								<?php
							}
							?>

						</div>

						<DIV id="history"></DIV>

					</div>

					<?php if ( $sip_active == 'yes' ) { ?>
						<div class="calls cbox bgwhite">
							<DIV id="callhistory"></DIV>
						</div>
					<?php } ?>

					<div class="log cbox">

						<?php
						if ( $haveAccesse ) {
							$nolog = $_COOKIE['nolog'];
							?>
							<DIV class="batton-edit">
								<a href="javascript:void(0)" onclick="cardloadlog()"><i class="icon-arrows-cw broun"></i><?= $lang['all']['Refresh'] ?></a>
							</DIV><br>
						<?php } ?>
						<DIV id="log"></DIV>

					</div>
				</div>

			</div>

		</fieldset>

	</DIV>
	<DIV class="rightcol hidden" id="tab-cbudjet" data-id="cbudjet">

		<fieldset data-step="81" data-intro="<h1>Вкладка <b>Документы.</b></h1>Здесь хранятся различные Документы - договоры, КП и пр." data-position="left">
			<legend><?= $lang['finance']['Budjet'] ?></legend>
			<DIV id="tabcbudjet" style="overflow-y: auto; max-height: 80vh;"></DIV>
		</fieldset>

	</DIV>
	<DIV class="rightcol hidden" id="tab-15" data-id="docs">

		<fieldset data-step="8" data-intro="<h1>Вкладка <b>Документы.</b></h1>Здесь хранятся различные Документы - договоры, КП и пр." data-position="left">
			<legend>
				<?= $lang['docs']['Doc'][1] ?>&nbsp;<a href="<?= $productInfo['site'] ?>/docs/52" target="blank" title="Как это работает?"><i class="icon-help-circled"></i></a>
			</legend>
			<?php
			if ( $haveAccesse ) {
				?>
				<DIV class="batton-edit pt10 pb10">

					<div class="inline">
						<a href="javascript:void(0)" onclick="settab('15')"><i class="icon-arrows-cw broun"></i><?= $lang['all']['Refresh'] ?></a>&nbsp;&nbsp;
					</div>
					<div class="inline relativ">

						<a href="javascript:void(0)" onclick="$cardsf.docMenu();" class="tagsmenuToggler" title="<?= $lang['all']['Add'] ?>"><i class="icon-plus-circled green"></i><?= $lang['all']['Add'] ?>&nbsp;<i class="icon-angle-down broun" id="mapi"></i></a>

						<div class="tagsmenu toright hidden">

							<div class="items noBold fs-09" data-id="doctypes"></div>

						</div>

					</div>

				</DIV>
			<?php
			}
			else{
				print '<DIV class="batton-edit pt10 pb10">&nbsp;</DIV>';
			}
			?>

			<DIV id="tab15" class="relativ"></DIV>

		</fieldset>

	</DIV>
	<DIV class="rightcol hidden" id="tab-2" data-id="contacts">

		<fieldset data-step="7" data-intro="<h1>Вкладка <b>Список контактов.</b></h1>Здесь выводится список контактов, прикрепленных к карточке" data-position="left">
			<legend><?= $lang['face']['ContactsName'][0] ?></legend>
			<?php
			if ( $haveAccesse && $userRights['person']['create'] ) { ?>
				<DIV class="batton-edit">
					<a href="javascript:void(0)" onclick="addPerson('<?= $clid ?>');"><i class="icon-plus-circled green"></i><?= $lang['all']['Add'] ?></a>
				</DIV>
				<br/>
			<?php
			}
			?>
			<DIV id="tab2"></DIV>
		</fieldset>

	</DIV>
	<DIV class="rightcol hidden" id="tab-9" data-id="profile">

		<fieldset>
			<legend>Профиль</legend>
			<br/>
			<DIV class="pull-left block">
				<a href="<?= $productInfo['site'] ?>/docs/49" target="blank" title="Документация: Знакомство с Профилем"><i class="icon-help-circled-1"></i>Что такое Профиль?</a>
			</DIV>
			<DIV class="batton-edit pull-aright">
				<?php if ( $haveAccesse ) { ?>
					<a href="javascript:void(0)" onclick="settab('9')"><i class="icon-arrows-cw broun"></i><?= $lang['all']['Refresh'] ?></a>&nbsp;&nbsp;
					<a href="javascript:void(0)" onclick="doLoad('/content/card/card.profile.php?action=edit&clid=<?= $clid ?>')"><i class="icon-pencil green"></i><?= $lang['all']['Edit'] ?></a>
				<?php } ?>
			</DIV>
			<br>
			<DIV id="tab9"></DIV>
		</fieldset>

	</DIV>
	<DIV class="rightcol hidden" id="tab-4" data-id="deals">

		<fieldset data-step="9" data-intro="<h1>Вкладка <b>Сделки.</b></h1>Здесь фиксируются все продажи в данного клиента." data-position="left">
			<legend><?= $lang['face']['DealsName'][0] ?>&nbsp;<a href="<?= $productInfo['site'] ?>/docs/67" target="blank" title="Как это работает?"><i class="icon-help-circled"></i></a></legend>
			<?php
			if ( $haveAccesse && $userRights['deal']['create'] ) {
			?>
			<DIV class="batton-edit">
				<a href="javascript:void(0)" onclick="settab('4')"><i class="icon-arrows-cw broun"></i><?= $lang['all']['Refresh'] ?></a>&nbsp;&nbsp;
				<a href="javascript:void(0)" onclick="editDogovor('<?= $clid ?>','add');"><i class="icon-plus-circled green"></i><?= $lang['all']['Add'] ?></a>
			</DIV><br/>
			<?php } ?>
			<DIV id="tab4"></DIV>
		</fieldset>

	</DIV>
	<DIV class="rightcol hidden" id="tab-cdeals" data-id="cdeals">

		<fieldset>
			<legend><?= $lang['face']['DealsName'][0] ?></legend>
			<DIV id="tabcdeals"></DIV>
		</fieldset>

	</DIV>
	<?php if ( $otherSettings['comment'] ) { ?>
		<DIV class="rightcol hidden" id="tab-12" data-id="comments">

			<fieldset>
				<legend>
					<?= $lang['face']['Comments'] ?>&nbsp;<a href="<?= $productInfo['site'] ?>/docs/51" target="blank" title="Как это работает?"><i class="icon-help-circled"></i></a>
				</legend>
				<DIV class="batton-edit">
					<a href="javascript:void(0)" onclick="settab('12')"><i class="icon-arrows-cw broun"></i><?= $lang['all']['Refresh'] ?></a>&nbsp;&nbsp;
					<a href="javascript:void(0)" onclick="editComment('', 'add', '');"><i class="icon-plus-circled green"></i><?= $lang['all']['New'][2] ?></a>
				</DIV>
				<br/>
				<DIV id="tab12" class="mp10"></DIV>
			</fieldset>

		</DIV>
	<?php } ?>
	<DIV class="rightcol hidden " id="tab-6" data-id="files">

		<fieldset class="relativ" data-step="10" data-intro="<h1>Вкладка <b>Файлы.</b></h1>Вы можете прикреплять к карточке любые документы и хранить их" data-position="left">

			<legend>Файлы</legend>

			<?php if ( $haveAccesse ) { ?>
				<div class="batton-edit fs-09">

					<a href="javascript:void(0)" onclick="getZip()" title="" class="button orangebtn hidden zip"><i class="icon-file-archive"></i>Скачать ZIP<span></span></a>&nbsp;
					<a href="javascript:void(0)" onclick="editUpload('','add')" class="button"><i class="icon-plus-circled"></i>Загрузить</a>

				</div>
			<?php } ?>

			<DIV id="tab6" class="relativ mt20"></DIV>

		</fieldset>

	</DIV>
	<DIV class="rightcol hidden" id="tab-mail" data-id="mail">

		<DIV id="tabmail" class="p5"></DIV>

	</DIV>
	<?php
	// добавляем вкладки из фильтров
	foreach ( $tabs as $tab ) {

		print '
			<DIV class="rightcol hidden" id="tab-'.$tab['name'].'" data-id="'.$tab['name'].'">

				<fieldset>
					<legend>'.$tab['title'].'</legend>
					<DIV class="batton-edit">
						<a href="javascript:void(0)" onclick="settab(\''.$tab['name'].'\')"><i class="icon-arrows-cw broun"></i>'.$lang['all']['Refresh'].'</a>
					</DIV>
					<DIV id="tab'.$tab['name'].'" class="mt10 mp10"></DIV>
				</fieldset>
		
			</DIV>
		';

	}
	?>

	<DIV style="height:50px; display:inline-block; width:99%;"></DIV>

</DIV>

<div class="options">

	<i class="icon-plus-circled-1"></i>

	<div class="box">

		<ul>
			<li>
				<a href="javascript:void(0)" onclick="editDogovor('<?= $clid ?>', 'add')" title="Добавить Сделку" class="blue"><i class="icon-briefcase-1"></i></a>
			</li>
			<li>
				<a href="javascript:void(0)" onclick="PersonAdd({'clid':'<?= $clid ?>','did':'<?= $did ?>'})" title="Добавить контакт" class="green"><i class="icon-user-1"><i class="sup icon-plus-circled"></i></i></a>
			</li>
			<li>
				<a href="javascript:void(0)" onclick="addTask('', '<?= $clid ?>', '', <?= $did ?>)" title="Добавить напоминание" class="red"><i class="icon-calendar-1"></i></a>
			</li>
			<li>
				<a href="javascript:void(0)" onclick="addHistory('', '<?= $clid ?>', '', <?= $did ?>)" title="Добавить активность"><i class="icon-clock"></i></a>
			</li>
		</ul>

	</div>

</div>

<div id="startinto" class="<?= (!$_COOKIE['intro'] ? '' : 'hidden') ?>">

	<div class="relativ">

		<div class="showintro" title="Запустить гид для знакомства с CRM">
			<span><i class="icon-help-circled-1"></i></span>Знакомство
		</div>
		<div id="hideintro" title="Больше не показывать гид"><i class="icon-cancel-circled"></i></div>

	</div>

</div>

<script>

	//устанавливаем переменную, что мы в карточке
	isCard = true;
	tipCard = 'client';

	var clid = parseInt('<?=$clid?>');

	//признак того, что открыт фрейм
	isFrame = <?=($_REQUEST['face']) ? 'true' : 'false';?>;

	$(function () {

		var id = window.location.hash.replace('#', '');
		var intro = getCookie('intro');

		if (id)
			settab(id);

		if (intro === 'hid')
			$('#startinto').hide();

		$('.ftabs').each(function () {

			$(this).find('li').removeClass('active');
			$(this).find('li:first-child').addClass('active');

			$(this).find('.cbox').addClass('hidden');
			$(this).find('.cbox:first-child').removeClass('hidden');

		});

		configpage();
		$cardsf.getTasks();

		$('#cardtabs').tabs();

		if ($('#tab12').is('div') && parseInt(id) === 12)
			Visibility.every(30000, mm);

		$('.for-remove').remove();

	});

	$(".showintro").on('click', function () {

		var intro = introJs();

		$('html, body').animate({
			scrollTop: 0
		}, 200);

		settab('0');

		intro.setOptions({
			'nextLabel': 'Дальше',
			'prevLabel': 'Вернуть',
			'skipLabel': 'Пропустить',
			'doneLabel': 'Я понял',
			'showStepNumbers': false,
			'scrollToElement': false
		});
		intro.start().goToStep(3)
			.onbeforechange(function (targetElement) {

				switch ($(targetElement).attr("data-step")) {
					case "1":
					case "2":
					case "3":
						break;
					case "4":
						$(targetElement).find(".popmenu-top").show();
						break;
					case "5":
						settab('0');
						break;
					case "6":
						settab('0');
						$("#subpan3").show();
						$(targetElement).show();
						break;
					case "7":
						settab('2');
						$(targetElement).show();
						break;
					case "8":
						settab('15');
						$(targetElement).show();
						break;
					case "9":
						settab('4');
						$(targetElement).show();
						break;
					case "10":
						settab('6');
						$(targetElement).show();
						break;
					case "11":
						$(targetElement).show();
						break;
				}
			})
	});

	$('.close').on('click', function () {

		DClose();
		if (editor) removeEditor();

	});

	$(document).on('click', '#ytabs li', function () {

		var link = $(this).data('link');
		var id = $(this).closest('.ftabs').attr('id');

		$('#' + id + ' li').removeClass('active');
		$(this).addClass('active');

		$('#' + id + ' .cbox').addClass('hidden');
		$('#' + id + ' .' + link).removeClass('hidden');

		if (link === 'calls')
			$cardsf.getCalls();

	});
	$(document).on('click', '.action', function () {

		$('#hpage').val('1');
		cardloadHist('1');

	});

	function configpage() {

		settab("0", false);

		if ($("#tabgroup").is('div'))
			$('#tabgroup').load("/content/card/card.group.php?clid=<?=$clid?>").append('<img src="/assets/images/loading.svg">');

		$cardsf.getDostup();

		if ($("#tab11").is('div'))
			$("#tab11").load('/content/card/card.capacity.php?clid=<?=$clid?>').append('<img src="/assets/images/loading.svg">');

		if ($("#tabInvoices").is('div'))
			getInvoices();

		if ($("#tabEntry").is('div'))
			getEntry();

		if ($("#tabProducts").is('div'))
			getProducts();

		if ($('#callhistory').is('div'))
			$cardsf.getCalls();

	}

	function settab(id, show = true) {

		var url = '';
		var el = $('#tab-' + id);

		if( show === undefined ){
			show = true
		}

		switch (id) {
			case "":
			case "0":
				url = '/content/card/card.client.php?clid=<?=$clid?>';
				break;
			case "2":
				url = '/content/card/card.persons.php?clid=<?=$clid?>';
				break;
			case "4":
				url = '/content/card/card.deals.php?clid=<?=$clid?>';
				break;
			case "6":
				url = '/content/card/card.files.php?clid=<?=$clid?>';
				break;
			case "9":
				url = '/content/card/card.profile.php?clid=<?=$clid?>';
				break;
			case "12":
				url = '/modules/comments/card.comments.php?action=theme.extern&clid=<?=$clid?>';
				break;
			case "15":
				url = '/content/card/card.contracts.php?clid=<?=$clid?>';
				break;
			case "mail":
				url = '/modules/mailer/card.mailer.php?clid=<?=$clid?>';
				break;
			case "11":
				url = '/content/card/card.capacity.php?clid=<?=$clid?>';
				break;
			case "cbudjet":
				url = '/content/card/card.cbudjet.php?clid=<?=$clid?>';
				break;
			case "cdeals":
				url = '/content/card/card.cdeals.php?clid=<?=$clid?>';
				break;
		<?php
			foreach ( $tabs as $tab ) {

				print '
				case "'.$tab['name'].'":
					url = \''.$tab['url'].'?clid='.$clid.'\';
				break;
				';

			}
			?>
		}

		if (id === 0 || id === 0 || id === 'undefined') {

			$('.ftabs').each(function () {

				$(this).find('li').removeClass('active');
				$(this).find('li:first-child').addClass('active');

				$(this).find('.cbox').addClass('hidden');
				$(this).find('.cbox:first-child').removeClass('hidden');

			});

		}

		if (show)
			$('#dtabs li').removeClass('current');

		if (!isMobile) {

			if (show) {

				$('.rightcol').addClass('hidden');

				if (parseInt(id) === 0) {

					$('#tab-1').removeClass('hidden');
					$('#tb0').addClass('current');

				}
				else {

					el.removeClass('hidden');
					$('#tb' + id).addClass('current');

				}

			}

		}
		else {

			if (el.hasClass('leftcol')) {

				$('.rightcol').addClass('hidden');
				$('.leftcol').removeClass('hidden');

			}
			else {

				$('.leftcol').addClass('hidden');
				$('.rightcol').addClass('hidden');

				el.removeClass('hidden');

			}

			$('#tb' + id).addClass('current');

		}

		$('#tab' + id).load(url).append('<div id="loader"><img src="/assets/images/loading.svg"> Загрузка данных. Пожалуйста подождите...</div>')
			.ajaxComplete(function () {

				if (isMobile) {

					$('#tab' + id).find('table').rtResponsiveTables();

				}

				if (typeof cardCallback === 'function') {
					cardCallback();
				}

				CardLoad.fire({
					etype: 'clientCard'
				});

			});

	}

	function getEntry() {

		var $el = $('#tabEntry');
		var count = 0;

		$el.append('<img src="/assets/images/loading.svg">').load('/modules/entry/card.entry.php?clid=<?=$clid?>', function () {

			$el.find('.viewdiv').find('div.row.new').each(function () {

				count++;

			});

			$('li[data-link="entry"]').find('sup').html(count);

		});

	}

	function getInvoices() {

		var $el = $('#tabInvoices');
		var count = 0;

		$el.append('<img src="/assets/images/loading.svg">').load('/content/card/card.invoices.php?clid=<?=$clid?>', function () {

			$el.find('div[data-id="invoice"]').each(function () {

				count++;

			});

			$('li[data-link="invoices"]').find('sup').html(count);

		});

	}

	function getProducts() {

		var $el = $('#tabProducts');
		var count = 0;

		$el.append('<img src="/assets/images/loading.svg">').load('/content/card/card.products.php?clid=<?=$clid?>', function () {

			$el.find('div.row.halight').each(function () {

				count++;

			});

			$('li[data-link="products"]').find('sup').html(count);

		});

	}

	function cardload(page) {

		$cardsf.getTasks(page);

	}

	function cardloadlog(page) {

		$cardsf.getLog(page);

	}

	function cardloadHist(page) {

		$cardsf.getHistory(page);

	}

	function callsload(page) {

		$cardsf.getCalls(page);

	}

	function callsload2() {

		$cardsf.getCalls();

	}

	function mm() {

		settab('12', false);

	}
</script>
</BODY>
</HTML>