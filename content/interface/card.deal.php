<?php
/* ============================ */
/*         SalesMan CRM         */
/* ============================ */
/* (C) 2016 Vladislav Andreev   */
/*       SalesMan Project       */
/*        www.isaler.ru         */
/*        ver. 2019.x           */

/* ============================ */

use Salesman\DealAnketa;

global $rootpath;

error_reporting(E_ERROR);

$did    = (int)$_REQUEST['did'];
$action = $_REQUEST['action'];

require_once $rootpath."/inc/head.card.php";

$lang = $GLOBALS['lang'];

//Найдем тип сделки, которая является Сервисной
$isper = (isServices($did)) ? 'yes' : 'no';

$result = $db -> getRow("SELECT * FROM ".$sqlname."dogovor WHERE did = '$did' and identity = '$identity'");
$close  = $result["close"];
//$calculate = $result["calculate"];
$clid  = (int)$result["clid"];
$pid   = $result["pid"];
$title = $result["title"];
//$isdog_num = $result["dog_num"];
//$isakt_num = $result["akt_num"];
$iduser = (int)$result["iduser"];

if ($isper == 'yes') {
	$icn = '<i class="icon-arrows-cw blue list"></i>';
}


// Проверка на доступность редактирования
$isAccess = (get_accesse(0, 0, (int)$did) == "yes") || $isadmin == 'on';

// выполняем фильтры
$tabs = $hooks -> apply_filters("card_tab", $tabs, [
	"type" => "deal",
	"did"  => $did
]);

//print $ac_import[18];
?>

<div class="fixx">

	<DIV id="head">

		<DIV id="ctitle">

			<input type="hidden" name="isCard" id="isCard" value="yes">
			<input type="hidden" name="card" id="card" value="dogovor">
			<input type="hidden" name="did" id="did" value="<?= $did ?>">

			<span data-step="1" data-intro="<h1>Тип и Название клиента.</h1>" data-position="bottom-middle-aligned">

				<span class="back2menu">
					<a href="/" title="<?= $lang['all']['Desktop'] ?>"><i class="icon-home"></i></a>
				</span>

				<span class="hidden-iphone">
					<?= $icn ?><b class="blue"><?= $lang['face']['DealName'][0] ?>:</b>&nbsp;
				</span>

				<span class="elipsis Bold">
					<span class="visible-iphone"><i class="icon-briefcase-1"></i></span>
					<?= $title ?>
				</span>

				<?php if ($close == "yes") { ?>
					&nbsp;(<i class="hidden-iphone icon-lock red "></i>
					<B class="hidden-iphone red "><?= $lang['face']['DealName'][0] ?> закрыта</B>)
				<?php } ?>

			</span>
			<DIV id="close" onclick="window.close();"><?= $lang['all']['Close'] ?></DIV>

		</DIV>

	</DIV>

	<DIV id="dtabs" class="box--child" data-step="2" data-intro="<h1>Вкладки по разделам</h1>" data-position="bottom-middle-aligned">

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
			<LI id="tb15" onclick="settab('15')" class="">
				<A href="#15">
					<span class="hidden-normal" title="<?= $lang['docs']['Doc'][1] ?>"><i class="icon-doc-text-inv fs-09"></i></span>
					<span class="hidden-netbook"><?= $lang['docs']['Doc'][1] ?></span>
				</A>
			</LI>
			<LI id="tb7" onclick="settab('7')" class="<?= ($otherSettings['credit'] || $otherSettings['price'] ? "" : "hidden for-remove") ?>">
				<A href="#7">
					<span class="hidden-normal" title="<?= $lang['face']['InvoicesAndSpecification'] ?>"><i class="icon-rouble fs-09"></i>&<i class="icon-list"></i></span>
					<span class="hidden-netbook"><?= $lang['face']['InvoicesAndSpecification'] ?></span>
				</A>
			</LI>
			<LI id="tb12" onclick="settab('12')" class="<?= ($otherSettings['comment'] ? "" : "hidden for-remove") ?>">
				<A href="#12">
					<span class="hidden-normal hidden-notebook-normal" title="<?= $lang['face']['Comments'] ?>"><i class="icon-chat fs-09"></i></span>
					<span class="hidden-netbook hidden-notebook"><?= $lang['face']['Comments'] ?></span>
				</A>
			</LI>
			<LI id="tb13" onclick="settab('13')" class="<?= (( ($otherSettings['partner'] || $otherSettings['contractor']) && ($userSettings['dostup']['partner'] == 'on' || $userSettings['dostup']['contractor'] == 'on')) ? "" : "hidden for-remove") ?>">
				<A href="#13">
					<span class="hidden-normal hidden-notebook-normal" title="<?= $lang['face']['Agents'] ?>"><i class="icon-share fs-09"></i></span>
					<span class="hidden-netbook hidden-notebook"><?= $lang['face']['Agents'] ?></span>
				</A>
			</LI>
			<LI id="tb6" onclick="settab('6')" class="<?= ($acs_files == 'on' ? "" : "hidden for-remove") ?>">
				<A href="#6">
					<span class="hidden-normal hidden-notebook-normal" title="<?= $lang['face']['Files'] ?>"><i class="icon-floppy fs-09"></i></span>
					<span class="hidden-netbook hidden-notebook"><?= $lang['face']['Files'] ?></span>
				</A>
			</LI>
			<?php
			$list = (new DealAnketa()) -> anketalist();
			if (!empty($list)) {
				?>
				<LI id="tb5" onclick="settab('5')" title="Анкеты по сделкам">
					<A href="#5">
						<span class="large" title="Анкеты"><i class="icon-doc-inv-alt fs-09"></i></span>
						<span class="hidden visible-iphone">Анкеты</span>
					</A>
				</LI>
			<?php } ?>
			<?php
			$result = $db -> query("SELECT * FROM ".$sqlname."modules WHERE active = 'on' and identity = '$identity' ORDER by id");
			while ($data = $db -> fetch($result)) {

				if (file_exists("modules/".$data['mpath']."/cardblock.php")) {

					print '
					<LI id="tb'.$data['mpath'].'" onclick="settab(\''.$data['mpath'].'\')" title="'.$data['title'].'">
						<A href="#'.$data['mpath'].'">
							<span class=""><i class="'.$data['icon'].' fs-09"></i></span>
							<span class="hidden-normal">'.$data['title'].'</span>
						</A>
					</LI>
					';

				}
			}
			?>
			<?php
			// добавляем вкладки из фильтров
			foreach ($tabs as $tab) {

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
			<li id="tb600" class="<?= ($ymEnable == true ? "" : "hidden for-remove") ?> green right">
				<a href="javascript:void(0)" onclick="$mailer.composeCard('<?= $clid ?>','<?= $pid ?>','');" title="<?= $lang['ymail']['ComposeMail'] ?>">
					<i class="icon-mail fs-07"></i>&nbsp;<span class="visible-iphone">Написать</span>
				</a>
			</li>
			<li id="tbmail" onclick="settab('mail')" class="<?= ($ymEnable ? "" : "hidden for-remove") ?> right">
				<a href="#mail" title="">
					<i class="icon-exchange fs-07"></i>&nbsp;<span class="hidden-netbook"><?= $lang['ymail']['Correspondence'] ?></span>
				</a>
			</li>
		</ul>

	</DIV>

</div>

<DIV class="fixbg"></DIV>

<DIV id="telo">

	<DIV class="leftcol" id="tab-0" data-id="info" data-step="3" data-intro="<h1>Блок Информации.</h1>В блоке выводится базовая информация о Сделке" data-position="right">

		<div>

			<fieldset>

				<legend>
					<?= $lang['all']['Info'] ?>&nbsp;<a href="https://salesman.pro/docs/67" target="blank" title="Как это работает?"><i class="icon-help-circled"></i></a>
				</legend>

				<?php
				if ($isAccess || $isadmin == 'on') {
					?>
					<DIV class="batton-edit pt10 pb10">

						<?php
						if ( ($close != "yes" && $userRights['deal']['create'] && $tipuser != ' Поддержка продаж') || $isadmin == 'on' || ($close == "yes" && $userRights['deal']['editclosed']) ) {

							print '<a href="javascript:void(0)" onclick="editDogovor(\''.$did.'\',\'edit\');"><i class="icon-pencil broun"></i>&nbsp;'.$lang['all']['Edit'].'</a>&nbsp;&nbsp;&nbsp;';

						}
						?>
						<div class="inline relativ" data-step="4" data-intro="<b>Действия.</b><br>Позволяют совершать манипуляции с записью" data-position="right">

							<a href="javascript:void(0)" class="tagsmenuToggler" title="<?= $lang['all']['Actions'] ?>"><i class="icon-angle-down broun" id="mapi"></i><b class="blue"><?= $lang['all']['Actions'] ?></b></a>&nbsp;

							<div class="tagsmenu left hidden">

								<div class="items noBold fs-09">

									<div onclick="settab('0')" class="item ha hand">
										<i class="icon-arrows-cw broun"></i>&nbsp;<?= $lang['all']['Refresh'] ?>
									</div>

									<?php if ($isAccess == "yes" && $userSettings['historyAddBlock'] != 'yes') { ?>
										<div onclick="addHistory('','','','<?= $did ?>');" class="item ha hand">
											<i class="icon-clock blue"></i>&nbsp;<?= $lang['all']['Add'] ?> <?= $lang['all']['Activity'] ?>
										</div>
									<?php } ?>

									<div onclick="addTask('', '','','<?= $did ?>');" class="item ha hand">
										<i class="icon-calendar-inv green"></i>&nbsp;<?= $lang['all']['Add'] ?> <?= $lang['face']['TodoName'][0] ?>
									</div>

									<?php
									if ($tipuser != 'Поддержка продаж') {

										/**
										 * Восстановление сделки
										 */
										if ( $close == 'yes' && ($isadmin == 'on' || $userRights['deal']['restore']) ) {

											print '<div onclick="cf=confirm(\'Вы действительно хотите Восстановить Сделку?\');if(cf) editDogovor(\''.$did.'\',\'restore\')" title="Восстановить Сделку" class="item ha hand"><i class="icon-ccw broun"></i>&nbsp;Восстановить</div>';

										}

										/**
										 * Клонирование сделки
										 */
										if ( ($userRights['deal']['edit'] && $userRights['deal']['create']) || $isadmin == 'on') {

											print '<div onclick="cloneDogovor(\''.$did.'\');" title="Создать новую на основе текущей" class="item ha hand"><i class="icon-paste green"></i>&nbsp;Клонировать</div>';

										}

										/**
										 * Редактирование
										 */
										if (($userRights['deal']['edit'] && $close != "yes") || $isadmin == 'on' || ($close == "yes" && $userRights['deal']['editclosed'])) {

											print '<div onclick="editDogovor(\''.$did.'\',\'edit\');" class="item ha hand"><i class="icon-pencil broun"></i>&nbsp;'.$lang['all']['Edit'].'</div>';

										}

										/**
										 * Смена пользователя
										 */
										if (($isAccess /*&& $tipuser != 'Поддержка продаж'*/ && $close != 'yes' && !$userRights['nouserchange'])) {

											print '<div onclick="editDogovor(\''.$did.'\',\'change.user\');" title="'.$lang['all']['UserChange'].'" class="item ha hand"><i class="icon-user-1 blue"></i>&nbsp;'.$lang['all']['UserChange'].'</div>';

										}

									}

									if ($close != 'yes' /*&& $tipuser != 'Поддержка продаж'*/ && ($userRights['deal']['create'] || $isadmin == 'on')) {

										print '
										<div onclick="editDogovor(\''.$did.'\',\'close\');" title="'.$lang['all']['Close'].' '.$lang['face']['DealName'][3].'" class="item ha hand"><i class="icon-box red"></i>&nbsp;'.$lang['all']['Close'].' '.$lang['face']['DealName'][3].'</div>
										';


										if ($userRights['deal']['delete'] || $isadmin == 'on') {

											print '
											<div onclick="cf=confirm(\''.$lang['msg']['RealyDeleteThis'].'\'); if (cf)deleteCCD(\'deal\',\''.$did.'\',\'card\')" title="'.$lang['all']['Delete'].' '.$lang['face']['DealName'][3].'" class="item ha hand"><i class="icon-trash red"></i>&nbsp;'.$lang['all']['Delete'].'</div>
											';

										}

									}
									?>

								</div>

							</div>

						</div>

					</DIV>
					<?php
				}
				else {
					print '<div class="warning div-center"><i class="icon-attention red"></i>&nbsp;У вас нет доступа к редактированию записи</div>';
				}
				?>

				<DIV id="tab0"></DIV>

			</fieldset>

		</div>

		<fieldset>

			<legend>Доступ к карточке</legend>

			<?php
			if (($isAccess && $tipuser != 'Поддержка продаж') || $isadmin == 'on') {

				if (!in_array($iduser1, (array)$user) || $isAccess || $isadmin == 'on') {

					print '
					<DIV class="batton-edit broun">
						<a href="javascript:void(0)" onclick="editDogovor(\''.$did.'\',\'change.dostup\');"><i class="icon-pencil broun"></i>'.$lang['all']['Edit'].'</a>
					</DIV>
					<br>';

				}

			}
			?>

			<DIV id="tabd"></DIV>
			<br>

		</fieldset>

	</DIV>

	<DIV class="rightcol" id="tab-1" data-id="tasks">

		<?php if ($complect_on == 'yes') { ?>
			<fieldset>
				<legend>Контрольные точки<a href="https://salesman.pro/docs/70" target="blank" title="Как это работает?"><i class="icon-help-circled"></i></a></legend>
				<?php
				if (( ($close != 'yes' && $isAccess) || $tipuser == 'Поддержка продаж') || $isadmin == 'on') {
					?>
					<DIV class="batton-edit">
						<a href="javascript:void(0)" onclick="configpage()"><i class="icon-arrows-cw broun"></i>Обновить</a>&nbsp;&nbsp;&nbsp;&nbsp;<a href="javascript:void(0)" onclick="editCPoint('','add','<?= $did ?>');"><i class="icon-plus-circled green"></i>Добавить</a>
					</DIV><br/>
				<?php } ?>
				<div id="complect"></div>
			</fieldset>
		<?php } ?>

		<fieldset data-step="5" data-intro="<h1>Напоминания.</h1>Здесь выводятся все напоминания по данному Сделке. Также можно добавить новое напоминание" data-position="left">
			<legend>Напоминания</legend>
			<DIV class="batton-edit">
				<a href="javascript:void(0)" onclick="cardload()"><i class="icon-arrows-cw broun"></i>Обновить</a>&nbsp;&nbsp;&nbsp;&nbsp;
				<a href="javascript:void(0)" onclick="addTask('', '<?= $clid ?>','<?= $pid ?>','<?= $did ?>');"><i class="icon-plus-circled green"></i>Добавить</a>
			</DIV>
			<br>
			<DIV id="tab10" class="fcontainer1"></DIV>
		</fieldset>
		<fieldset data-step="6" data-intro="<h1>История активностей.</h1>Выводится История активностей по данной карточке" data-position="left">

			<legend><?= $lang['all']['History'] ?></legend>

			<div id="historyMore" class="ftabs" data-id="container">

				<div id="ytabs">

					<ul class="gray flex-container blue">

						<li class="flex-string" data-link="history"><?= $lang['face']['ActsName'][0] ?></li>
						<li class="flex-string" data-link="log" onclick="cardloadlog()"><?= $lang['all']['Logs'] ?></li>

					</ul>

				</div>
				<div id="container" class="fcontainer1 pt10">

					<div class="history cbox">

						<div class="relativ">

							<div class="ydropDown w200 fs-10" style="z-index:1;">

								<?php
								$tiphistory = yexplode(",", $_COOKIE['tiphistory']);
								$atype = $db -> getAll("SELECT id, title, color FROM ".$sqlname."activities WHERE identity = '$identity' ORDER BY title");
								?>

								<span><i class="icon-filter blue"></i> Фильтр</span>
								<span class="ydropCount"><?= count($tiphistory) ?> выбрано</span>
								<i class="icon-angle-down pull-aright"></i>

								<div class="action hidden">Применить</div>

								<div class="yselectBox" style="max-height: 300px;">

									<div class="yunSelect noaction"><i class="icon-cancel-circled2"></i>Снять выделение
									</div>

									<?php
									foreach ($atype as $data) {

										$s = (in_array($data['id'], $tiphistory)) ? "checked" : "";
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
							if ($isAccess) {
								?>
								<DIV class="pull-right Bold">
								<a href="javascript:void(0)" onclick="cardloadHist()"><i class="icon-arrows-cw broun"></i><?= $lang['all']['Refresh'] ?></a>&nbsp;&nbsp;
								<?php if ($userSettings['historyAddBlock'] != 'yes') { ?>
									<a href="javascript:void(0)" onclick="addHistory('','<?= $clid ?>', '<?= $pid ?>', '<?= $did ?>');"><i class="icon-plus-circled gred"></i><?= $lang['all']['Add'] ?></a>
								<?php } ?>
								</DIV>
							<?php } ?>

						</div>

						<DIV id="history"></DIV>

					</div>

					<div class="log cbox">

						<?php
						if ($isAccess) {
							?>
							<DIV class="batton-edit">
								<a href="javascript:void(0)" onclick="cardloadlog()"><i class="icon-arrows-cw broun"></i><?= $lang['all']['Refresh'] ?></a>
							</DIV>
							<br/>
						<?php } ?>
						<DIV id="log"></DIV>

					</div>
				</div>
			</div>

		</fieldset>

	</DIV>
	<DIV class="rightcol hidden" id="tab-6" data-id="files">
		<fieldset data-step="10" data-intro="<h1>Вкладка <b>Файлы.</b></h1>Вы можете прикреплять к карточке любые документы и хранить их" data-position="left">
			<legend>Файлы</legend>
			<?php
			if ($isAccess) {
				?>
				<DIV class="batton-edit inline text-right wp100">

					<div id="orangebutton" class="inline hidden zip">
						<a href="javascript:void(0)" onclick="getZip()" title="" class="button">Скачать ZIP<span></span></a>
					</div>
					<a href="javascript:void(0)" onclick="editUpload('','add')" class="button"><i class="icon-plus-circled"></i>Загрузить</a>

				</DIV>
			<?php } ?>
			<DIV id="tab6" class="relativ"></DIV>
		</fieldset>
	</DIV>
	<DIV class="rightcol hidden mp5" id="tab-7" data-id="invoices">

		<?php if ($otherSettings['credit']) { ?>
		<fieldset data-step="12" data-intro="<h1><b>Счета</b></h1>Здесь вы можете выставить Счет клиенту. Здесь же фиксируется оплата" data-position="left">
			<legend>Счета и оплаты&nbsp;<a href="https://salesman.pro/docs/74" target="blank" title="Как выставлять счета?"><i class="icon-help-circled"></i></a>
			</legend>
			<?php
			if (($close != 'yes' && $isAccess /*&& $acs_credit == 'on'*/) || $isadmin == 'on') {
				?>
				<DIV class="batton-edit">
					<a href="javascript:void(0)" onclick="editClient('<?= $clid ?>','change.recvisites');"><i class="icon-town-hall broun"></i>Реквизиты</a>&nbsp;&nbsp;&nbsp;
					<a href="javascript:void(0)" onclick="$('#credit_<?= $did ?>').load('/content/card/card.credit.php?did=<?= $did ?>');"><i class="icon-arrows-cw broun"></i>Обновить</a>&nbsp;&nbsp;&nbsp;
					<a href="javascript:void(0)" onclick="editCredit('<?= $did ?>','credit.add');" class="button-credit-add"><i class="icon-plus-circled green"></i>Добавить Счет</a>
					<?php
					if ($isper == 'yes') { ?>
						&nbsp;|&nbsp;
						<a href="javascript:void(0)" onclick="editAkt('add','','','<?= $did ?>')"><i class="icon-plus-circled green"></i>Добавить Счет+Акт</a>
						<?php
					}
					?>
				</DIV><br>
			<?php } ?>
			<div id="credit_<?= $did ?>"></div>

		</fieldset>
		<?php } ?>
		<fieldset data-step="11" data-intro="<h1><b>Спецификация.</b></h1>Здесь вы можете составить список продуктов для выставления счета" data-position="left">

			<legend>Спецификация</legend>
			<DIV id="tab7"></DIV>

		</fieldset>

		<?php
		if ($isCatalog == 'on' && !$isMobile) {
			?>
			<fieldset>
				<legend>Склад</legend>
				<DIV id="tab_reserv"></DIV>
				<DIV id="tab_zayavka"></DIV>
				<DIV id="tab_order"></DIV>
			</fieldset>
			<?php
		}

		?>
	</DIV>
	<DIV class="rightcol hidden" id="tab-12" data-id="comments">
		<fieldset>
			<legend>Обсуждение&nbsp;<a href="https://salesman.pro/docs/51" target="blank" title="Как это работает?"><i class="icon-help-circled"></i></a></legend>
			<DIV class="batton-edit">
				<a href="javascript:void(0)" onclick="settab('12')"><i class="icon-arrows-cw broun"></i>Обновить</a>&nbsp;&nbsp;&nbsp;&nbsp;<a href="javascript:void(0)" onclick="editComment('','add','');"><i class="icon-plus-circled green"></i>Добавить</a>
			</DIV>
			<DIV id="tab12" class="mt10 mp10"></DIV>
		</fieldset>
	</DIV>
	<DIV class="rightcol hidden" id="tab-13" data-id="contractors">
		<fieldset>
			<legend>
				Поставщики/Партнеры&nbsp;<a href="https://salesman.pro/docs/73" target="blank" title="Как это работает?"><i class="icon-help-circled"></i></a>
			</legend>
			<DIV id="tab13"></DIV>
		</fieldset>
	</DIV>
	<DIV class="rightcol hidden" id="tab-15" data-id="docs">

		<fieldset data-step="8" data-intro="<h1>Вкладка <b>Документы.</b></h1>Здесь хранятся различные Документы - договоры, КП и пр." data-position="left">
			<legend>
				Документы&nbsp;<a href="https://salesman.pro/docs/52" target="blank" title="Как это работает?"><i class="icon-help-circled"></i></a>
			</legend>
			<?php
			if ($isAccess || $isadmin == 'on') {
				?>
				<DIV class="batton-edit pt10 pb10">

					<div style="display: inline-block;">
						<a href="javascript:void(0)" onclick="editClient('<?= $clid ?>', 'change.recvisites');"><i class="icon-town-hall broun"></i>Реквизиты</a>&nbsp;&nbsp;&nbsp;<a href="javascript:void(0)" onclick="settab('15');"><i class="icon-arrows-cw broun"></i>Обновить</a>&nbsp;&nbsp;&nbsp;&nbsp;
					</div>
					<div class="inline relativ">

						<a href="javascript:void(0)" onclick="$cardsf.docMenu();" class="tagsmenuToggler" title="Добавить"><i class="icon-plus-circled green"></i>Добавить<i class="icon-angle-down" id="mapi"></i>&nbsp;</a>

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
	<DIV class="rightcol hidden" id="tab-mail" data-id="mail">
		<DIV id="tabmail" class="paddtop10"></DIV>
	</DIV>

	<?php
	/**
	 * Блок подключения модулей
	 */
	$result = $db -> query("SELECT * FROM ".$sqlname."modules WHERE active = 'on' and identity = '$identity' ORDER by id");
	while ($data = $db -> fetch($result)) {

		if (file_exists("modules/".$data['mpath']."/cardblock.php")) {

			print '
			<DIV class="rightcol" id="tab-'.$data['mpath'].'" data-id="'.$data['mpath'].'">
				<DIV id="tab'.$data['mpath'].'"></DIV>
			</DIV>
			';

		}

	}
	?>

	<?php
	//require "./inc/class/DealAnketa.php";
	$anketa  = new DealAnketa();
	$listall = $anketa -> anketalist();
	if (!empty($listall)) {
		?>
		<DIV class="rightcol hidden" id="tab-5" data-id="anketa">

			<fieldset>
				<legend>Анкеты</legend>
				<DIV id="tab5"></DIV>
			</fieldset>

		</DIV>
	<?php } ?>
	<?php
	// добавляем вкладки из фильтров
	foreach ($tabs as $tab) {

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
				<a href="javascript:void(0)" onclick="PersonAdd({'clid':'<?= $clid ?>','did':'<?= $did ?>'})" title="Добавить контакт" class="green"><i class="icon-user-1"><i class="sup icon-plus-circled"></i></i></a>
			</li>
			<li>
				<a href="javascript:void(0)" onclick="addTask('', '<?= $clid ?>', '', <?= $did ?>)" title="Добавить напоминание" class="red"><i class="icon-calendar-inv"></i></a>
			</li>
			<li>
				<a href="javascript:void(0)" onclick="addHistory('', '<?= $clid ?>', '', <?= $did ?>)" title="Добавить активность">
					<i class="icon-clock"></i>
				</a>
			</li>
		</ul>

	</div>

</div>

<div id="startinto">

	<div class="relativ">

		<div class="showintro" title="Запустить гид для знакомства с CRM">
			<span><i class="icon-help-circled-1"></i></span>Знакомство
		</div>
		<div id="hideintro" title="Больше не показывать гид"><i class="icon-cancel-circled"></i></div>

	</div>

</div>

<script>

	//устанавливаем переменную
	//что мы в карточке
	isCard = true;
	tipCard = 'deal';

	//признак того, что открыт фрейм
	isFrame = <?=($_REQUEST['face']) ? 'true' : 'false';?>;

	$(function () {

		var id = window.location.hash.replace('#', '');
		var intro = getCookie('intro');

		if (id !== '')
			settab(id, true);

		if (intro === 'hid')
			$('#startinto').hide();

		$('.ftabs').each(function () {

			$(this).find('li').removeClass('active');
			$(this).find('li:first-child').addClass('active');

			$(this).find('.cbox').addClass('hidden');
			$(this).find('.cbox:first-child').removeClass('hidden');

		});

		configpage();
		cardload();
		$cardsf.getDostup();

		if ($('#tab12').is('div') && parseInt(id) === 12)
			Visibility.every(30000, mm);

		if ($('#tab_reserv').is('div'))
			getCatalog();

		$('.for-remove').remove();

	});

	$('.close').on('click', function () {

		$('#dialog').css('display', 'none').css('width', '500px');
		$('#resultdiv').empty();
		$('#dialog_container').css('display', 'none');

		if (editor)
			removeEditor();

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
						/*$('html, body').animate({
							scrollTop: $("#carddostup").offset().top
						}, 200);*/
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
						settab('7');
						$(targetElement).show();
						break;
					case "12":
						settab('7');
						//$(targetElement).show();
						break;
					case "13":
						break;
				}

			})
	});

	$(document).on('click', '#ytabs li', function () {

		var link = $(this).data('link');
		var id = $(this).closest('.ftabs').attr('id');

		$('#' + id + ' li').removeClass('active');
		$(this).addClass('active');

		$('#' + id + ' .cbox').addClass('hidden');
		$('#' + id + ' .' + link).removeClass('hidden');

		//igetFiles("docs");

	});
	$(document).on('click', '.action', function () {

		$('#hpage').val('1');

		$cardsf.getHistory();

	});

	function configpage() {

		settab("0", false);

		if ($("#complect").is('div'))
			$("#complect").load("/content/card/card.controlpoint.php?did=<?=$did?>").append('<img src="/assets/images/loading.svg">');

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

	function mm() {

		settab(12, false);

	}

	function settab(id, show) {

		var url = '';
		var el = $('#tab-' + id);

		if( show === undefined ){
			show = true
		}

		switch (id) {

			case "":
			case "0":

				url = '/content/card/card.deal.php?did=<?=$did?>';

				$cardsf.getDostup();

				break;
			case "5":

				url = '/content/deal.anketa/card.php?action=anketa.list&did=<?=$did?>';

				break;
			case "6":

				url = '/content/card/card.files.php?did=<?=$did?>';

				break;
			case "7":

				url = '/content/card/card.speca.php?did=<?=$did?>';

				if ($('#credit_<?=$did?>').is('div'))
					$('#credit_<?=$did?>').load('/content/card/card.credit.php?did=<?=$did?>');

				break;
			case "12":

				url = '/modules/comments/card.comments.php?action=theme.extern&did=<?=$did?>';

				break;
			case "13":

				url = '/content/card/card.provider.php?did=<?=$did?>';

				break;
			case "15":

				url = '/content/card/card.contracts.php?did=<?=$did?>';

				break;
			case "mail":

				url = '/modules/mailer/card.mailer.php?did=<?=$did?>';

				break;
		<?php
			$result = $db -> query("SELECT * FROM ".$sqlname."modules WHERE active = 'on' and identity = '$identity' ORDER by id");
			while ($data = $db -> fetch($result)) {

				if (file_exists("modules/".$data['mpath']."/cardblock.php")) {

					print 'case "'.$data['mpath'].'": url = \'/modules/'.$data['mpath'].'/cardblock.php?did='.$did.'\'; break;';

				}
			}
			?>
		<?php
			foreach ($tabs as $tab) {

				print '
				case "'.$tab['name'].'":
					url = \''.$tab['url'].'?did='.$did.'\';
				break;
				';

			}
			?>

		}

		if (id === 0 || id === "0" || id === 'undefined') {

			$('.ftabs').each(function () {

				$(this).find('li').removeClass('active');
				$(this).find('li:first-child').addClass('active');

				$(this).find('.cbox').addClass('hidden');
				$(this).find('.cbox:first-child').removeClass('hidden');

			});

		}

		if (show) {
			$('#dtabs li').removeClass('current');
		}

		if (!isMobile) {

			if (show) {

				$('.rightcol').addClass('hidden');

				if (parseInt(id) === 0) {

					$('#tab-1').removeClass('hidden');
					$('#tb0').addClass('current');

				} else {

					el.removeClass('hidden');
					$('#tb' + id).addClass('current');

				}

			}

		}
		else {

			if (el.hasClass('leftcol')) {

				$('.rightcol').addClass('hidden');
				$('.leftcol').removeClass('hidden');

			} else {

				$('.leftcol').addClass('hidden');
				$('.rightcol').addClass('hidden');

				el.removeClass('hidden');

			}

			$('#tb' + id).addClass('current');

		}

		$('#tab' + id).load(url).append('<div id="loader"><img src="/assets/images/loading.svg"> Загрузка. Пожалуйста подождите...</div>')
			.ajaxComplete(function () {

				if (isMobile) {

					$('#tab' + id).find('table').rtResponsiveTables({'id': 'table-tab' + id});

				}

				if (typeof cardCallback === 'function') {

					cardCallback();

				}

				CardLoad.fire({
					etype: 'dealCard'
				});

			});

	}

	function getCatalog() {

		$('#tab_reserv').load('/modules/modcatalog/card.modcatalog.php?action=getReserv&did=<?=$did?>').append('<img src="/assets/images/loading.svg">');
		$('#tab_zayavka').load('/modules/modcatalog/card.modcatalog.php?action=getZayavka&did=<?=$did?>').append('<img src="/assets/images/loading.svg">');
		$('#tab_order').load('/modules/modcatalog/card.modcatalog.php?action=getOrder&did=<?=$did?>').append('<img src="/assets/images/loading.svg">');

	}

	function removeReserve(id) {

		var url = '/modules/modcatalog/core.modcatalog.php?id=' + id + '&action=removereserv';
		$('#message').css('display', 'block').append('<div id=loader><img src=/assets/images/loader.gif> Загрузка данных. Пожалуйста подождите...</div>');

		$.get(url, function (data) {

			$('#tab_reserv').load('/modules/modcatalog/card.modcatalog.php?action=getReserv&did=<?=$did?>').append('<img src="/assets/images/loading.svg">');

			$('#message').fadeTo(1, 1).css('display', 'block').html(data);
			setTimeout(function () {
				$('#message').fadeTo(1000, 0);
			}, 20000);

		});

	}

</script>
</BODY>
</HTML>