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
<div class="counters bottom disable--select" data-step="41" data-intro="<h1>Панель индикаторов и быстрого поиска</h1>" data-position="right">

	<ul>
		<?php

		// Получаем параметры поиска
		$paramsSearch = $_COOKIE[ 'paramsSearch' ] != '' ? json_decode( $_COOKIE[ 'paramsSearch' ] ) : [];

		if ( $script !== 'index.php' && $script !== 'desktop.php' && $settingsMore['dealHealthOn'] == 'yes' ) {
			?>
			<li class="pop visible-min-h590" data-id="health" title="<?= $lang[ 'face' ][ 'DealsHealth' ] ?>">
				<div class="pops" data-id="health" onclick="getHealthModal()">
					<i class="icon-medkit"></i><span id="counthealth" class="bullet"><b>??</b></span>
				</div>
			</li>
		<?php } ?>
		<?php
		if ( $script !== 'calendar.php' && $script !== 'desktop.php' ) {
			?>
			<li class="lpop donthidee hidden-ipad" data-id="todo">

				<div class="pops" data-id="todo"><i class="icon-calendar-1"></i></div>

				<div class="popmenu nothide w300">

					<div class="left-triangle-before"></div>
					<div class="left-triangle-after"></div>

					<div class="pophead">
						Напоминания
						<div class="popcloser" title="Скрыть"><i class="icon-cancel-circled"></i></div>
					</div>

					<?php
					include dirname(__DIR__, 2)."/content/leftnav/todo-popup.php";
					flush();
					?>

				</div>

			</li>
		<?php
		}

		// Получаем список закрепленных статей Базы Знаний
		//$articles = $db -> getAll( "SELECT * FROM ".$sqlname."knowledgebase WHERE pin = 'yes' and identity = '$identity' ORDER BY pindate" );

		?>
		<li class="lpop donthidee" data-id="search" data-step="42" data-intro="<h1>Быстрый и универсальный поиск</h1>" data-position="right">

			<div class="pops clearevents" data-id="search"><i class="icon-search"></i></div>

			<div class="popmenu w600 nothide disable--select" style="height: calc(100vh - 60px);">

				<div class="left-triangle-before"></div>
				<div class="left-triangle-after"></div>

				<div class="pophead">

					<?= $lang[ 'msg' ][ 'SearchPanelTitle' ] ?>

					<div class="popcloser" title="Скрыть"><i class="icon-cancel-circled"></i></div>

					<div class="tagsmenuToggler hand relativ inline pull-aright" data-id="fhelper">
						<span><i class="icon-help-circled orange"></i></span>
						<div class="tagsmenu fly1 right" id="fhelper" style="right:0; top: 100%">
							<div class="blok p10 w350 fs-09 black noBold">

								<div class="Bold blue">Поиск осуществляется по следующим полям:</div>
								<?php
								$fc = [];
								if ( $fieldsNames[ 'client' ][ 'title' ] != '' ) $fc[] = $fieldsNames[ 'client' ][ 'title' ];
								if ( $fieldsNames[ 'client' ][ 'des' ] != '' ) $fc[] = $fieldsNames[ 'client' ][ 'des' ];
								if ( $fieldsNames[ 'client' ][ 'address' ] != '' ) $fc[] = $fieldsNames[ 'client' ][ 'address' ];
								if ( $fieldsNames[ 'client' ][ 'recv' ] != '' ) $fc[] = $fieldsNames[ 'client' ][ 'recv' ];
								if ( $fieldsNames[ 'client' ][ 'phone' ] != '' ) $fc[] = $fieldsNames[ 'client' ][ 'phone' ];
								if ( $fieldsNames[ 'client' ][ 'mail_url' ] != '' ) $fc[] = $fieldsNames[ 'client' ][ 'mail_url' ];
								if ( $fieldsNames[ 'client' ][ 'site_url' ] != '' ) $fc[] = $fieldsNames[ 'client' ][ 'site_url' ];
								?>

								<b><?= $lang[ 'all' ][ 'Search' ] ?> <?= $lang[ 'face' ][ 'ClientName' ][ 1 ] ?>:</b> <?= implode( ", ", $fc ) ?>
								<br>

								<?php
								$fp = [];
								if ( $fieldsNames[ 'person' ][ 'person' ] != '' ) $fp[] = $fieldsNames[ 'person' ][ 'person' ];
								if ( $fieldsNames[ 'person' ][ 'tel' ] != '' ) $fp[] = $fieldsNames[ 'person' ][ 'tel' ];
								if ( $fieldsNames[ 'person' ][ 'mob' ] != '' ) $fp[] = $fieldsNames[ 'person' ][ 'mob' ];
								if ( $fieldsNames[ 'person' ][ 'mail' ] != '' ) $fp[] = $fieldsNames[ 'person' ][ 'mail' ];
								?>

								<b><?= $lang[ 'all' ][ 'Search' ] ?><?= $lang[ 'face' ][ 'ContactName' ][ 1 ] ?>:</b> <?= implode( ", ", $fp ) ?>
								<br>

								<?php
								$fd = [];
								if ( $fieldsNames[ 'dogovor' ][ 'title' ] != '' ) $fd[] = $fieldsNames[ 'dogovor' ][ 'title' ];
								if ( $fieldsNames[ 'dogovor' ][ 'content' ] != '' ) $fd[] = $fieldsNames[ 'dogovor' ][ 'content' ];
								if ( $fieldsNames[ 'dogovor' ][ 'adres' ] != '' ) $fd[] = $fieldsNames[ 'dogovor' ][ 'adres' ];
								?>

								<b><?= $lang[ 'all' ][ 'Search' ] ?> <?= $lang[ 'face' ][ 'DealName' ][ 1 ] ?>:</b> <?= $lang[ 'all' ][ 'Name' ] ?>, <?= implode( ", ", $fd ) ?>
								<hr>
								<div class="Bold"><?= $lang[ 'msg' ][ 'SearchEnter' ] ?></div>
								<div>Используйте <i class="icon-cog-1"></i> Параметры</div><hr>

								<div class="red">Параметры поиска сохраняются только на текущем устройстве в хранилище браузера</div>
								<hr>

								<div>Параметр "<b>Строгий поиск</b>" задаёт поиск слов строго в заданном порядке. Например: поиск "Владислав Андреев" не включит в результат запись с названием "Андреев Владислав".</div>

							</div>
						</div>
					</div>

				</div>

				<form id="searchForm" name="searchForm" method="post" enctype="multipart/form-data">
					<div class="popblock flex-container viewdiv wp100" data-block="unisearch">

						<div class="flex-string wp80 relativ cleared">

							<input type="text" name="unisearch" id="unisearch" placeholder="Поиск: введите поисковой запрос" autocomplete="off" class="wp99 cleareventss enable--select" data-id="searchinput">
							<span class="idel red clearinputs pt5 mr15"><i class="icon-block-1" title="Очистить"></i></span>

						</div>
						<div class="flex-string wp20 visible-iphone">

							<a href="javascript:void(0)" onclick="uniSearchPop();" class="button m0"><i class="icon-search white wp100"></i></a>

						</div>
						<div class="flex-string wp20">

							<div class="ydropDown dWidth" id="paramsSearch">

								<span class="hidden-netbook gray2">Параметры</span>
								<span class="hidden-normal"><i class="icon-cog-1"></i></span>
								<i class="icon-angle-down pull-aright"></i>

								<div class="yselectBox" style="max-height: 300px;">

									<div class="right-text">
										<div class="ySelectAll w0 inline" title="Выделить всё">
											<i class="icon-plus-circled"></i>Всё
										</div>
										<div class="yunSelect w0 inline" title="Снять выделение">
											<i class="icon-minus-circled"></i>Ничего
										</div>
									</div>

									<div class="ydropString ellipsis">
										<label>
											<input class="taskss clearevents" type="checkbox" name="sch[]" id="sch[]" value="title" <?php if ( in_array( 'title', $paramsSearch ) ) print 'checked' ?>>&nbsp;по Названию, ФИО
										</label>
									</div>
									<div class="ydropString ellipsis">
										<label>
											<input class="taskss clearevents" type="checkbox" name="sch[]" id="sch[]" value="content" <?php if ( in_array( 'content', $paramsSearch ) ) print 'checked' ?>>&nbsp;по описанию
										</label>
									</div>
									<div class="ydropString ellipsis">
										<label>
											<input class="taskss clearevents" type="checkbox" name="sch[]" id="sch[]" value="adress" <?php if ( in_array( 'adress', $paramsSearch ) ) print 'checked' ?>>&nbsp;по адресу
										</label>
									</div>
									<div class="ydropString ellipsis">
										<label>
											<input class="taskss clearevents" type="checkbox" name="sch[]" id="sch[]" value="phone" <?php if ( in_array( 'phone', $paramsSearch ) ) print 'checked' ?>>&nbsp;по телефону
										</label>
									</div>
									<div class="ydropString ellipsis">
										<label>
											<input class="taskss clearevents" type="checkbox" name="sch[]" id="sch[]" value="email" <?php if ( in_array( 'email', $paramsSearch ) ) print 'checked' ?>>&nbsp;по email, сайту
										</label>
									</div>
									<div class="ydropString ellipsis">
										<label>
											<input class="taskss clearevents" type="checkbox" name="sch[]" id="sch[]" value="recv" <?php if ( in_array( 'recv', $paramsSearch, true ) ) print 'checked' ?>>&nbsp;по реквизитам (Клиент)
										</label>
									</div>

								</div>

							</div>

						</div>

						<div class="flex-string mt5 wp100">
							<label>
								<input class="taskss clearevents" type="checkbox" name="strong" id="strong" value="yes" <?php if ( in_array( 'yes', $paramsSearch ) ) print 'checked' ?>>&nbsp;<span class="Bold" title="Поиск осуществляется по полному словосочетанию">Строгий поиск</span>
							</label>
						</div>

					</div>
				</form>

				<div class="popbody">

					<div class="popcontent">

						<div class="viewdiv gray2 hidden">

							<?php
							$fc = [];
							if ( $fieldsNames[ 'client' ][ 'title' ] != '' ) $fc[] = $fieldsNames[ 'client' ][ 'title' ];
							if ( $fieldsNames[ 'client' ][ 'des' ] != '' ) $fc[] = $fieldsNames[ 'client' ][ 'des' ];
							if ( $fieldsNames[ 'client' ][ 'address' ] != '' ) $fc[] = $fieldsNames[ 'client' ][ 'address' ];
							if ( $fieldsNames[ 'client' ][ 'recv' ] != '' ) $fc[] = $fieldsNames[ 'client' ][ 'recv' ];
							if ( $fieldsNames[ 'client' ][ 'phone' ] != '' ) $fc[] = $fieldsNames[ 'client' ][ 'phone' ];
							if ( $fieldsNames[ 'client' ][ 'mail_url' ] != '' ) $fc[] = $fieldsNames[ 'client' ][ 'mail_url' ];
							if ( $fieldsNames[ 'client' ][ 'site_url' ] != '' ) $fc[] = $fieldsNames[ 'client' ][ 'site_url' ];
							?>

							<b><?= $lang[ 'all' ][ 'Search' ] ?> <?= $lang[ 'face' ][ 'ClientName' ][ 1 ] ?>:</b> <?= implode( ", ", $fc ) ?>
							<br>

							<?php
							$fp = [];
							if ( $fieldsNames[ 'person' ][ 'person' ] != '' ) $fp[] = $fieldsNames[ 'person' ][ 'person' ];
							if ( $fieldsNames[ 'person' ][ 'tel' ] != '' ) $fp[] = $fieldsNames[ 'person' ][ 'tel' ];
							if ( $fieldsNames[ 'person' ][ 'mob' ] != '' ) $fp[] = $fieldsNames[ 'person' ][ 'mob' ];
							if ( $fieldsNames[ 'person' ][ 'mail' ] != '' ) $fp[] = $fieldsNames[ 'person' ][ 'mail' ];
							?>

							<b><?= $lang[ 'all' ][ 'Search' ] ?><?= $lang[ 'face' ][ 'ContactName' ][ 1 ] ?>:</b> <?= implode( ", ", $fp ) ?>
							<br>

							<?php
							$fd = [];
							if ( $fieldsNames[ 'dogovor' ][ 'title' ] != '' ) $fd[] = $fieldsNames[ 'dogovor' ][ 'title' ];
							if ( $fieldsNames[ 'dogovor' ][ 'content' ] != '' ) $fd[] = $fieldsNames[ 'dogovor' ][ 'content' ];
							if ( $fieldsNames[ 'dogovor' ][ 'adres' ] != '' ) $fd[] = $fieldsNames[ 'dogovor' ][ 'adres' ];
							?>

							<b><?= $lang[ 'all' ][ 'Search' ] ?> <?= $lang[ 'face' ][ 'DealName' ][ 1 ] ?>:</b> <?= $lang[ 'all' ][ 'Name' ] ?>, <?= implode( ", ", $fd ) ?>

							<hr>
							<b><?= $lang[ 'msg' ][ 'SearchEnter' ] ?></b>, Используйте
							<i class="icon-cog-1"></i> Параметры

						</div>

						<div class="pad5 div-center1 fs-11" id="searchResult"></div>

					</div>

				</div>

			</div>

		</li>
		<?php
		//print $script;
		if ( $otherSettings[ 'comment'] && $script !== 'comments' ) {
			?>
			<li class="pop donthidee hidden-ipad" onclick="$('#commlist').load('modules/comments/card.comments.php?action=listpanel');" data-id="comments">

				<div class="pops" data-id="comments">
					<i class="icon-chat"></i><span id="commnum" class="bullet blue Bold">??</span>
				</div>

				<div class="popmenu yw350">

					<div class="left-triangle-before"></div>
					<div class="left-triangle-after"></div>

					<div class="pophead">
						Ответы в обсуждениях
						<span class="link">
						<a href="/comments" title="Перейти к обсуждениям" target="blank"><i class="icon-chat pull-aright"></i></a>
					</span>
					</div>
					<div class="popbody">
						<div class="popcontent" id="commlist">
							<div class="pad5 div-center"><?= $lang[ 'msg' ][ 'LoadingShort' ] ?></div>
						</div>
					</div>

				</div>

			</li>
			<?php
		}
		if ( $ymEnable && file_exists( "modules/mailer" ) ) {
			?>
			<li class="pop donthidee hidden-ipad" data-id="ymail">

				<div class="pops" data-id="ymail" onclick="$mailer.check()">
					<i class="icon-mail-alt" id="mailIndicator"></i><span id="countEmail" class="bullet green">??</span>
				</div>

				<div class="popmenu yw350">

					<div class="left-triangle-before"></div>
					<div class="left-triangle-after"></div>

					<div class="pophead">
						<?= $lang[ 'ymail' ][ 'Module' ] ?> (<?= $lang[ 'all' ][ 'Last' ][ 1 ] ?> 20)

						<span class="link pull-aright">
						<a href="javascript:void(0)" onclick="$mailer.get('yes');" class="" title="<?= $lang[ 'ymail' ][ 'GetIncomMail' ] ?>"><i class="icon-arrows-cw"></i></a>&nbsp;
						<a href="/mailer#inbox" title="<?= $lang[ 'ymail' ][ 'AllMail' ] ?>"><i class="icon-mail"></i></a>&nbsp;
						<a href="javascript:void(0)" onclick="$mailer.compose()" title="<?= $lang[ 'ymail' ][ 'ComposeMail' ] ?>"><i class="icon-plus-circled"></i></a>
					</span>

					</div>

					<div class="popbody">
						<div class="popcontent" style="border:0">
							<div id="mails"></div>
						</div>
					</div>

				</div>

			</li>
			<?php
		}
		if ( $modLeadActive == 'on' ) {

			$mleadset      = $db -> getRow( "SELECT * FROM ".$sqlname."modules WHERE mpath = 'leads' and identity = '$identity'" );
			$mleadsettings = json_decode( $mleadset[ 'content' ], true );

			if ( $iduser1 == $mleadsettings[ 'leadСoordinator' ] || in_array( $iduser1, $mleadsettings[ 'leadOperator' ], true ) ) {
				?>
				<li class="pop donthidee" onclick="$('#leadlist').load('/content/vigets/notify.leads.php?action=get_leads');" data-id="leads">

					<div class="pops" data-id="leads">
						<i class="icon-sort-alt-down"></i><span id="leadnum" class="bullet">??</span></div>

					<div class="popmenu yw350">

						<div class="left-triangle-before"></div>
						<div class="left-triangle-after"></div>

						<div class="pophead">

							<div class="popcloser visible-iphone" title="Скрыть"><i class="icon-cancel-circled"></i>
							</div>

							Заявки
							<span class="link hidden-iphone">
								<a href="/leads" title="Перейти к заявкам"><i class="icon-sort-alt-down pull-aright"></i></a>
							</span>

						</div>
						<div class="popbody">
							<div id="leadlist">
								<div class="pad5 div-center"><?= $lang[ 'msg' ][ 'LoadingShort' ] ?></div>
							</div>
						</div>
					</div>

				</li>
				<?php
			}
		}
		?>
		<li class="pop donthidee visible-min-h590" onclick="$('#contcredit').load('/content/vigets/notify.php?action=get_credit');" data-id="credit">

			<div class="pops" data-id="credit"><i class="icon-dollar"></i><span id="kolcredit" class="bullet">??</span>
			</div>

			<div class="popmenu yw350">

				<div class="left-triangle-before"></div>
				<div class="left-triangle-after"></div>

				<div class="pophead">

					<div class="popcloser visible-iphone" title="Скрыть"><i class="icon-cancel-circled"></i></div>

					Платежи от клиентов
					<span class="link hidden-iphone">
						<a href="/contract#payment" title="Перейти к счетам"><i class="icon-rouble pull-aright"></i></a>
					</span>

				</div>
				<div class="popbody">
					<div id="contcredit">
						<div class="pad5 div-center"><?= $lang[ 'msg' ][ 'LoadingShort' ] ?></div>
					</div>
				</div>

			</div>

		</li>
		<li class="pop donthidee visible-min-h590" onclick="$('#cont').load('/content/vigets/notify.php?action=get_notifi');" data-id="deals">

			<div class="pops" data-id="deals"><i class="icon-briefcase"></i><span id="kolnot" class="bullet">??</span>
			</div>

			<div class="popmenu yw350">

				<div class="left-triangle-before"></div>
				<div class="left-triangle-after"></div>

				<div class="pophead">

					<div class="popcloser visible-iphone" title="Скрыть"><i class="icon-cancel-circled"></i></div>

					Внимание к сделкам

					<span class="link hidden-iphone">
						<a href="/deals" title="Перейти к сделкам"><i class="icon-briefcase pull-aright"></i></a>
					</span>

				</div>
				<div class="popbody">
					<div id="cont">
						<div class="pad5 div-center"><?= $lang[ 'msg' ][ 'LoadingShort' ] ?></div>
					</div>
				</div>

			</div>

		</li>
	</ul>

</div>