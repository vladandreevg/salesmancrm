<?php
/* ============================ */
/* (C) 2016 Vladislav Andreev   */
/*       SalesMan Project       */
/*        www.isaler.ru         */
/*        ver. 2019.x           */
/* ============================ */

global $rootpath;

error_reporting(E_ERROR);

$pid    = $_REQUEST['pid'];
$action = $_REQUEST['action'];

require_once $rootpath."/inc/head.card.php";

$result = $db -> getRow("select iduser, clid from ".$sqlname."personcat WHERE pid = '$pid' and identity = '$identity'");
$iduser = $result["iduser"];
$clid   = $result["clid"];

// выполняем фильтры
$tabs = $hooks -> apply_filters( "card_tab", $tabs, ["type" => "contact", "pid" => $pid] );

$haveAccesse = get_accesse(0, (int)$pid) == "yes";
?>
<div class="fixx">

	<DIV id="head">

		<DIV id="ctitle">

			<input type="hidden" name="isCard" id="isCard" value="yes">
			<input type="hidden" name="card" id="card" value="person">
			<input type="hidden" name="pid" id="pid" value="<?= $pid ?>">

			<span data-step="1" data-intro="<h1>Тип и Название клиента.</h1>" data-position="bottom-middle-aligned">

				<span class="back2menu">
					<a href="/" title="Рабочий стол"><i class="icon-home"></i></a>
				</span>

				<span class="hidden-iphone">
					<b class="blue"><?= $lang['face']['ContactName'][0] ?>:</b>&nbsp;
				</span>

				<span class="elipsis Bold">
					<span class="visible-iphone"><i class="icon-user-1"></i></span>
					<?= $title ?>
				</span>

			</span>

			<DIV id="close" onclick="window.close();"><?= $lang['all']['Close'] ?></DIV>

		</DIV>

	</DIV>

	<DIV id="dtabs" data-step="2" data-intro="<h1>Вкладки по разделам</h1>" data-position="bottom-middle-aligned" class="table">

		<i class="icon-menu menu--card visible-iphone"></i>

		<UL>
			<LI class="current" id="tb0" onclick="settab('0')"><A href="#0"><?= $lang['all']['Info'] ?></A></LI>
			<LI class="hidden-normal" id="tb1" onclick="settab('1')"><A href="#1">Дела & Активность</A></LI>
			<?php
			/*if($otherSettings['dealByContact']){
			?>
			<LI id="tb4" onclick="settab('4')"><A href="#4">Сделки</A></LI>
			<?php
			}*/
			?>
			<LI id="tb12" onclick="settab('12')" class="<?= ( $otherSettings[ 'comment'] ? "" : "hidden for-remove") ?>">
				<A href="#12"><?= $lang['face']['Comments'] ?></A></LI>
			<LI id="tb6" onclick="settab('6')" class="<?= ($acs_files == 'on' ? "" : "hidden for-remove") ?>">
				<A href="#6"><?= $lang['face']['Files'] ?></A></LI>
			<?php
			// добавляем вкладки из фильтров
			foreach($tabs as $tab){

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
					<i class="icon-mail" style="font-size: 75%;"></i>&nbsp;<span class="visible-iphone">Написать</span>
				</a>
			</li>
			<li id="tbmail" onclick="settab('mail')" class="<?= ($ymEnable == true ? "" : "hidden for-remove") ?> right">
				<A href="#mail">
					<i class="icon-exchange" style="font-size: 0.5em;"></i>&nbsp;<span class="hidden-netbook"><?= $lang['ymail']['Correspondence'] ?></span>
				</A>
			</li>
		</ul>

	</DIV>

</div>

<DIV class="fixbg"></DIV>

<DIV id="telo">

	<DIV class="leftcol" id="tab-0" data-id="info">

		<fieldset data-step="3" data-intro="<h1>Блок Информации.</h1>В блоке выводится базовая информация о Контакте" data-position="right">

			<legend><?= $lang['all']['Info'] ?></legend>

			<div id="data-append" class="pull-left mt10"></div>

			<DIV class="batton-edit pt10 pb10">
				<?php
				if ( ($haveAccesse && $tipuser != 'Поддержка продаж' && ($userRights['person']['edit'] || $isadmin == 'on')) || (get_accesse((int)$clid) == "yes" && $tipuser == 'Поддержка продаж' && $da['iduser'] == $iduser1)) {
					?>
					<a href="javascript:void(0)" onclick="editPerson('<?= $pid ?>','edit');"><i class="icon-pencil broun"></i>&nbsp;Изменить</a>&nbsp;&nbsp;
				<?php } ?>
				<div class="inline relativ" data-step="4" data-intro="<h1>Действия.</h1>Позволяют совершать манипуляции с записью" data-position="right">
					<a href="javascript:void(0)" title="<?= $lang['all']['Actions'] ?>" class="tagsmenuToggler">
						<i class="icon-angle-down broun" id="mapi"></i><b class="blue"><?= $lang['all']['Actions'] ?></b>
					</a>
					<div class="tagsmenu left hidden">

						<div class="items noBold fs-09">

							<div onclick="window.open('/print.php?pid=<?= $pid ?>',this.target,'width=650,height=500,'+'location=no,toolbar=no,menubar=yes,status=no,resizeable=yes,scrollbars=yes')" title="Для печати" class="item ha hand hidden">
								<i class="icon-print blue"></i>&nbsp;Распечатать
							</div>
							<?php
							if ($haveAccesse && $tipuser != 'Поддержка продаж' && ($userRights['person']['edit'] || $isadmin == 'on')) {

								print '
								<div onclick="settab(\'0\')" class="item ha hand"><i class="icon-arrows-cw blue"></i>&nbsp;'.$lang['all']['Refresh'].'</div>
								<div onclick="editPerson(\''.$pid.'\',\'edit\');" class="item ha hand"><i class="icon-pencil broun"></i>&nbsp;'.$lang['all']['Edit'].'</div>
								';

							}
							if ($haveAccesse && $tipuser != 'Поддержка продаж' && $userRights['nouserchange']) {

								print '
								<div onclick="editPerson(\''.$pid.'\',\'change.user\');" title="'.$lang['all']['UserChange'].'" class="item ha hand"><i class="icon-user-1 blue"></i>&nbsp;'.$lang['all']['UserChange'].'</div>
								';

							}
							if (($haveAccesse && $userRights['delete'] && $tipuser != 'Поддержка продаж' && $userRights['person']['delete']) || $isadmin == 'on') {

								print '<div onclick="cf=confirm(\''.$lang['msg']['RealyDeleteThis'].'\');if (cf)deleteCCD(\'person\',\''.$pid.'\',\'card\')" title="'.$lang['all']['Delete'].'" class="item ha hand"><i class="icon-trash red"></i>&nbsp;'.$lang['all']['Delete'].'</div>';

							}
							?>

						</div>

					</div>

				</div>

			</DIV>

			<DIV id="tab0"></DIV>

		</fieldset>

		<fieldset>

			<legend>Группы</legend>

			<div class="batton-edit">
				<?php if ($userRights['group']) { ?>
					<a href="javascript:void(0)" onclick="editGroup('','addtoGroup');">&nbsp;<i class="icon-plus-circled broun"></i>Добавить</a>
				<?php } ?>
			</div>

			<DIV id="tabgroup"></DIV>

		</fieldset>

	</DIV>

	<DIV class="rightcol" id="tab-1" data-id="tasks">

		<fieldset data-step="5" data-intro="<h1>Напоминания.</h1>Здесь выводятся все напоминания по данному Клиенту/Контакту. Также можно добавить напоминание" data-position="left">
			<legend>Напоминания</legend>
			<DIV class="batton-edit">
				<a href="javascript:void(0)" onclick="cardload()">Обновить</a>&nbsp;&nbsp;|&nbsp;&nbsp;
				<a href="javascript:void(0)" onclick="addTask('', '<?= $clid ?>','<?= $pid ?>');">Добавить</a>
			</DIV>
			<br/>
			<DIV id="tab10" class="fcontainer" style="background:#FFF"></DIV>
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
				<div id="container" class="fcontainer1 paddtop10">

					<div class="history cbox">

						<div class="relativ">

							<div class="ydropDown w200 fs-12" style="z-index:1;">

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
							if ($haveAccesse) {
								?>
								<DIV class="pull-right Bold">
									<a href="javascript:void(0)" onclick="cardloadHist()"><i class="icon-arrows-cw broun"></i><?= $lang['all']['Refresh'] ?></a>&nbsp;&nbsp;
									<?php if($userSettings['historyAddBlock'] != 'yes'){ ?>
									<a href="javascript:void(0)" onclick="addHistory('','<?= $clid ?>', '<?= $pid ?>', '');"><i class="icon-plus-circled gred"></i><?= $lang['all']['Add'] ?></a>
									<?php } ?>
								</DIV>
							<?php } ?>

						</div>

						<DIV id="history"></DIV>

					</div>

					<div class="log cbox">

						<?php
						if ($haveAccesse) {
							?>
							<DIV class="batton-edit">
								<a href="javascript:void(0)" onclick="cardloadlog()"><i class="icon-arrows-cw broun"></i><?= $lang['all']['Refresh'] ?></a>
							</DIV><br/>
						<?php } ?>
						<DIV id="log"></DIV>

					</div>
				</div>
			</div>

		</fieldset>

	</DIV>

	<DIV class="rightcol hidden" id="tab-4" data-id="deals">

		<?php if ( $otherSettings[ 'dealByContact']) { ?>
			<fieldset>
				<legend>Сделки</legend>
				<?php
				if ($haveAccesse && $userRights['deal']['create']) { ?>
				<DIV class="batton-edit">
					<a href="javascript:void(0)" onclick="settab('4')"><i class="icon-arrows-cw"></i>Обновить</a>&nbsp;&nbsp;|&nbsp;&nbsp;<a href="javascript:void(0)" onclick="editDogovor('add','<?= $clid ?>');">Добавить</a>
				</DIV><br/>
				<?php } ?>
				<DIV id="tab4"></DIV>
			</fieldset>
		<?php } ?>

	</DIV>

	<?php if ( $otherSettings[ 'comment']) { ?>
	<DIV class="rightcol hidden" id="tab-12" data-id="comments">

		<fieldset>
			<legend>Обсуждение</legend>
			<DIV class="batton-edit"><a href="javascript:void(0)" onclick="settab('12')">Обновить</a>&nbsp;&nbsp;|&nbsp;&nbsp;<a href="javascript:void(0)" onclick="editComment('','add','');">Добавить</a>
			</DIV>
			<br/>
			<DIV id="tab12"></DIV>
		</fieldset>

	</DIV>
	<?php } ?>

	<DIV class="rightcol hidden" id="tab-6" data-id="files">

		<fieldset data-step="10" data-intro="Вкладка <b>Файлы.</b><br>Вы можете прикреплять к карточке любые документы и хранить их" data-position="left">

			<legend>Файлы</legend>

			<?php
			if ($haveAccesse) {
				?>
				<DIV class="batton-edit fs-09">
					<a href="javascript:void(0)" onclick="getZip()" class="button orangebtn hidden zip">Скачать ZIP<span></span></a>
					<a href="javascript:void(0)" onclick="editUpload('','add')" class="button"><i class="icon-plus-circled"></i>Загрузить</a>
				</DIV>
			<?php } ?>

			<DIV id="tab6" class="relativ"></DIV>

		</fieldset>

	</DIV>

	<DIV class="rightcol hidden" id="tab-mail">
		<DIV id="tabmail" class="paddtop10"></DIV>
	</DIV>
	<?php
	// добавляем вкладки из фильтров
	foreach($tabs as $tab){

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


<div id="startinto">

	<div class="relativ">

		<div class="showintro" title="Запустить гид для знакомства с CRM">
			<span><i class="icon-help-circled-1"></i></span>Знакомство
		</div>
		<div id="hideintro" title="Больше не показывать гид"><i class="icon-cancel-circled"></i></div>

	</div>

</div>


<script>

	var id = window.location.hash.replace('#', '');
	var intro = getCookie('intro');

	//устанавливаем переменную
	//что мы в карточке
	isCard = true;
	tipCard = 'person';

	//признак того, что открыт фрейм
	isFrame = <?=($_REQUEST['face']) ? 'true' : 'false';?>;

	$(function () {

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

		if ($('#tab12').is('div') && parseInt(id) === 12)
			Visibility.every(30000, mm);

		$('.for-remove').remove();


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

	function cardloadlog(page) {

		$cardsf.getLog(page);

	}

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
		intro.start()
			.onbeforechange(function (targetElement) {

				switch ($(targetElement).attr("data-step")) {
					case "1":
					case "2":
					case "3":
						settab('0');
						break;
					case "4":
						settab('0');
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
						$(targetElement).show();
						break;
				}
			})
	});

	function configpage() {

		$("#tab0").load("/content/card/card.person.php?pid=<?=$pid?>").append('<img src="/assets/images/loading.svg">');

		if ($("#tabgroup").is('div'))
			$('#tabgroup').load("/content/card/card.group.php?pid=<?=$pid?>").append('<img src="/assets/images/loading.svg">');

		if ($('#callhistory').is('div'))
			$cardsf.getCalls();

	}

	function settab(id) {

		var url = '';

		switch (id) {
			case "0":
				url = '/content/card/card.person.php?pid=<?=$pid?>';
				break;
			case "4":
				url = '/content/card/card.deals.php?clid=<?=$clid?>&pid=<?=$pid?>';
				break;
			case "6":
				url = '/content/card/card.files.php?pid=<?=$pid?>';
				break;
			case "12":
				url = '/modules/comments/card.comments.php?action=theme.extern&pid=<?=$pid?>';
				break;
			case "mail":
				url = '/modules/mailer/card.mailer.php?pid=<?=$pid?>';
				break;
			<?php
			foreach($tabs as $tab){

				print '
				case "'.$tab['name'].'":
					url = \''.$tab['url'].'?pid='.$pid.'\';
				break;
				';

			}
			?>
		}

		if (id === 0) {

			$('.ftabs').each(function () {

				$(this).find('li').removeClass('active');
				$(this).find('li:first-child').addClass('active');

				$(this).find('.cbox').addClass('hidden');
				$(this).find('.cbox:first-child').removeClass('hidden');

			});

		}

		$('#dtabs li').removeClass('current');

		if (!isMobile) {

			$('.rightcol').addClass('hidden');

			if (id === 0) {

				$('#tab-1').removeClass('hidden');
				$('#tb0').addClass('current');

			} else {

				$('#tab-' + id).removeClass('hidden');
				$('#tb' + id).addClass('current');

			}

		}
		else {

			if ($('#tab-' + id).hasClass('leftcol')) {

				$('.rightcol').addClass('hidden');
				$('.leftcol').removeClass('hidden');

			}
			else {

				$('.leftcol').addClass('hidden');
				$('.rightcol').addClass('hidden');

				$('#tab-' + id).removeClass('hidden');

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
					etype: 'personCard'
				});

			});

	}

	function cardload(page) {

		$cardsf.getTasks(page);

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