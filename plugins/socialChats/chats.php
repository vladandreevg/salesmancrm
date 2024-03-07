<?php
/* ============================ */
/*         SalesMan CRM         */
/* ============================ */
/* (C) 2019 Vladislav Andreev   */
/*       SalesMan Project       */
/*        www.isaler.ru         */
/*        ver. 2019.x           */
/* ============================ */

error_reporting( E_ERROR );
//ini_set('display_errors', 1);

$rootpath = realpath( __DIR__.'/../../' );

include $rootpath."/inc/config.php";
include $rootpath."/inc/dbconnector.php";
include $rootpath."/inc/auth_main.php";
include $rootpath."/inc/settings.php";
include $rootpath."/inc/func.php";

require_once "php/autoload.php";

$about = json_decode( str_replace( [
	"  ",
	"\t",
	"\n",
	"\r"
], "", file_get_contents( "plugin.json" ) ), true );

use Chats\Chats;
use Chats\Comet;

$chat = new Chats();

//$channelNames = Chats::CHANNELS;

// параметры comet-сервера
$comet  = new Comet($iduser1);
$cometSettings = $comet -> getSettings();

if($cometSettings['dev_id'] != '') {

	// регистрация юзера
	$r = $comet -> setUser();

}

$periodStart = str_replace("/", "-", $_REQUEST['periodStart']);
$periodEnd   = str_replace("/", "-", $_REQUEST['periodEnd']);

if (!$periodStart) {

	$period = getPeriod('month');

	$periodStart = $period[0];
	$periodEnd   = $period[1];

}

?>

<!DOCTYPE html>
<html lang="ru">
<head>
	<meta charset="utf-8">
	<title><?php echo $about[ 'name' ] ?></title>
	<meta content="text/html; charset=utf-8" http-equiv="content-type">
	<meta http-equiv="X-UA-Compatible" content="IE=edge">
	<meta name="viewport" content="user-scalable=no, initial-scale=1.0, maximum-scale=1.0"/>
	<meta name="apple-mobile-web-app-capable" content="yes"/>
	<meta name="apple-mobile-web-app-status-bar-style" content="default">

	<link type="text/css" rel="stylesheet" href="/assets/css/app.css">
	<link type="text/css" rel="stylesheet" href="/assets/css/fontello.css">
	<link type="text/css" rel="stylesheet" href="assets/css/chats.css?v=0.7">
	<link type="text/css" rel="stylesheet" href="/assets/css/ui.jquery.css">

	<script type="text/javascript" src="/assets/js/jquery/jquery-3.4.1.min.js"></script>
	<script type="text/javascript" src="/assets/js/jquery/jquery-migrate-3.0.0.min.js"></script>
	<script type="text/javascript" src="/assets/js/jquery/jquery-ui.min.js?v=2019.4"></script>
	<script type="text/javascript" src="/assets/js/jquery/jquery.scrollTo.js"></script>
	<script type="text/javascript" src="/assets/js/jquery/jquery.form.js"></script>
	<script type="text/javascript" src="/assets/js/jquery/jquery.autocomplete.js"></script>
	<script type="text/javascript" src="/assets/js/jquery/jquery.meio.mask.min.js"></script>

	<script type="text/javascript" src="/assets/js/moment.js/moment.min.js"></script>
	<script type="text/javascript" src="/assets/js/visibility.js/visibility.min.js"></script>
	<script type="text/javascript" src="/assets/js/mustache/mustache.js"></script>
	<script type="text/javascript" src="/assets/js/mustache/jquery.mustache.js"></script>
	<script type="text/javascript" src="/assets/js/favico.js/favico-0.3.10.min.js"></script>

	<script>
		const formatPhone = '<?php echo $format_phone?>';
		const cometUserKey = '<?php echo $cometSettings['user_key']?>';
		const cometUserID = '<?php echo $cometSettings['user_id']?>';
		const cometDevID = '<?php echo $cometSettings['dev_id']?>';
		const cometChannel = '<?php echo $cometSettings['channel']?>';
	</script>

	<!--красивые алерты-->
	<script type="text/javascript" src="/assets/js/sweet-alert2/sweetalert2.min.js"></script>
	<link type="text/css" rel="stylesheet" href="/assets/js/sweet-alert2/sweetalert2.min.css">

	<script type="text/javascript" src="/assets/js/daterangepicker/jquery.daterangepicker.js"></script>
	<link type="text/css" rel="stylesheet" href="/assets/js/daterangepicker/daterangepicker.css">

	<script type="text/javascript" src="assets/js/CometServerApi.js"></script>
	<script type="text/javascript" src="assets/js/chats.js?v=0.7"></script>
</head>
<body>

<div id="dialog_container" class="dialog_container">
	<div class="dialog-preloader">
		<img src="/assets/images/rings.svg" width="128">
	</div>
	<div class="dialog" id="dialog">
		<div class="close" title="Закрыть или нажмите ^ESC"><i class="icon-cancel"></i></div>
		<div id="resultdiv"></div>
	</div>
</div>

<div class="chat-layout">

	<div class="navigation">

		<div class="header">

			<div id="avatar" class="avatar">

				<div class="avatar--image" style="background : url(data:image/svg+xml;base64,<?php echo $about[ 'iconSVGinBase64' ] ?>) no-repeat;">
					<div class="avatar--img"></div>
				</div>
				<div class="avatar--txt pt5 hidden">
					<div class="avatar--name fs-12 Bold"><?= $about[ 'name' ] ?></div>
				</div>

			</div>

		</div>
		<div class="tabs disable--select">

			<UL>
				<LI class="ytab hidden" id="tb0" data-id="dashboard">
					<A href="#dashboard"><span class="icn"><i class="icon-gauge"></i></span><span class="text">Dashboard</span></A>
				</LI>
				<LI class="ytab current" id="tb0" data-id="chats">
					<A href="#chats"><span class="icn"><i class="icon-chat-1"></i></span><span class="text">Диалоги</span></A>
					<div class="new--chats hidden"></div>
				</LI>
				<LI class="ytab" id="tb4" data-id="statistics">
					<A href="#statistics"><span class="icn"><i class="icon-chart-line"></i></span><span class="text">Статистика</span></A>
				</LI>
				<LI class="ytab hidden" id="tb5" data-id="settings">
					<A href="#settings"><span class="icn"><i class="icon-cog"></i></span><span class="text">Настройка</span></A>
				</LI>
				<LI class="ytab hidden" id="tb3" data-id="exit">
					<A href="javascript:void(0)"><span class="icn"><i class="icon-off"></i></span><span class="text">Закрыть</span></A>
				</LI>
			</UL>

		</div>

	</div>

	<div class="lists hidden" data-id="dashboard">

		<div class="topper" data-id="tab-dashboard"></div>
		<div class="mainblock" data-id="tab-dashboard"></div>

	</div>
	<div class="lists" data-id="chats">

		<div class="leftblock">

			<div class="topper" data-id="tab-chats">

				<form id="filterForm" name="filterForm">

					<input type="hidden" id="page" name="page" value="1">
					<input type="hidden" id="shownew" name="shownew" value="0">

					<ul class="text-center spaced">
						<li data-id="filter" title="Фильтры" class="popblock">
							<a href="javascript:void(0)">
								<i class="icon-search"></i>
							</a>
							<span class="flyitbox" data-id="users"></span>
							<div class="popblock-menu width-unset w6001 not-hide">
								<div class="popblock-items">

									<div class="gray-blue Bold p10 uppercase text-center">Параметры поиска</div>
									<div class="gcontainer">

										<div class="gstring pl10 pr10">

											<div class="divider pt10 pb10">По каналу</div>

											<?php
											$channels = $chat -> getChannels();
											foreach ( $channels as $k => $channel ) {

												print '
												<div class="checkbox popblock-item" title="'.$channel[ 'name' ].'">
													<label for="channel['.$k.']" class="wp100 pl10 mt5 flex-container float">
														<input class="taskss" name="channel[]" type="checkbox" id="channel['.$k.']" value="'.$channel[ 'channel_id' ].'">
														
														<span class="custom-checkbox"><i class="icon-ok"></i></span>
														<span class="flex-string float pl10 text-wrap">
															<span class="block Bold fs-11">'.$channel[ 'name' ].'</span>
															<span class="block fs-09 gray">'.$channel[ 'type' ].'</span>
														</span>
														
													</label>
												</div>
												';

											}
											?>

										</div>
										<div class="gstring divider-vertical"></div>
										<div class="gstring pl10 pr10">

											<div class="divider pt10 pb10">По имени посетителя</div>

											<div class="no-border">

												<input type="text" name="name" id="name" class="wp100 p5 pl10 pr10" value="<?= $filters[ 'name' ] ?>" placeholder="" autocomplete="on">

											</div>

											<div class="divider pt10 pb10">По дате</div>

											<div class="no-border flex-container box--child" id="speriod">

												<div class="flex-string wp50 pr5">
													<input type="text" name="d1" id="d1" class="dstart inputdate wp100 p5 pl10 pr10" value="<?= $filters[ 'd1' ] ?>" placeholder="" autocomplete="on" data-type="date">
												</div>
												<div class="flex-string wp50 pl5">
													<input type="text" name="d2" id="d2" class="dend inputdate wp100 p5 pl10 pr10" value="<?= $filters[ 'd2' ] ?>" placeholder="" autocomplete="on" data-type="date">
												</div>
												<div class="flex-string wp100">

													<select id="period" data-goal="speriod" data-action="period" class="wp100">
														<option selected="selected">-за всё время-</option>
														<option data-period="today">Сегодня</option>
														<option data-period="yestoday">Вчера</option>

														<option data-period="calendarweekprev">Неделя прошлая</option>
														<option data-period="calendarweek">Неделя текущая</option>

														<option data-period="monthprev">Месяц прошлый</option>
														<option data-period="month">Месяц текущий</option>

														<option data-period="quartprev">Квартал прошлый</option>
														<option data-period="quart">Квартал текущий</option>

														<option data-period="year">Год</option>
													</select>

												</div>

											</div>

											<div class="divider pt10 pb10">По статусу</div>

											<div class="no-border flex-container box--child" id="speriod">

												<div class="flex-string wp100">

													<div class="checkbox popblock-item" title="Архивные">
														<label for="status[0]" class="wp100 pl10 mt5 flex-container float">
															<input class="taskss" name="status[]" type="checkbox" id="status[0]" value="archive">

															<span class="custom-checkbox"><i class="icon-ok"></i></span>
															<span class="flex-string float pl10 text-wrap">
															<span class="block Bold fs-11">Архивные</span>
														</span>

														</label>
													</div>
													<div class="checkbox popblock-item hidden" title="Заблокированные">
														<label for="status[1]" class="wp100 pl10 mt5 flex-container float">
															<input class="taskss" name="status[]" type="checkbox" id="status[1]" value="blocked">

															<span class="custom-checkbox"><i class="icon-ok"></i></span>
															<span class="flex-string float pl10 text-wrap">
																<span class="block Bold fs-11">Заблокированные</span>
															</span>

														</label>
													</div>

												</div>

											</div>

										</div>

									</div>
									<hr>
									<div class="text-right pr10 pl10">
										<a href="javascript:void(0)" class="button w120 orangebtn pull-left mb10 border-box m0" data-tip="filter" data-action="clear">Очистить</a>
										<a href="javascript:void(0)" class="button w120 mb10 border-box m0" data-tip="filter" data-action="do">Применить</a>
										<a href="javascript:void(0)" class="button w120 graybtn mb10 border-box m0" data-tip="filter" data-action="cancel">Закрыть</a>
									</div>

								</div>
							</div>
						</li>
						<li data-id="sort" title="Сортировка" class="popblock">
							<a href="javascript:void(0)">
								<i class="icon-sort-alt-down"></i>
							</a>
							<div class="popblock-menu w300">
								<div class="popblock-items">

									<div class="gray-blue Bold p10 uppercase text-center">Параметры</div>

									<div>

										<div class="divider pt10 pb10">Сортировка</div>

										<div class="radio popblock-item">
											<label class="wp100 p5">
												<input name="sort" type="radio" id="sort" value="lastdate" <?= ( $filters[ 'sort' ] == 'lastdate' || !$filters[ 'sort' ] ? 'checked' : '' ) ?>>
												<span class="custom-radio"><i class="icon-radio-check"></i></span>
												<span class="title">По дате</span>
											</label>
											<span class="pull-aright mt5 mr5 hidden"><i class="icon-sort-alt-up" data-for="datum"></i></span>
										</div>

										<div class="radio popblock-item">
											<label class="wp100 p5">
												<input name="sort" type="radio" id="sort" value="client_firstname" <?= ( $filters[ 'sort' ] == 'client_firstname' ? 'checked' : '' ) ?>>
												<span class="custom-radio"><i class="icon-radio-check"></i></span>
												<span class="title">По имени</span>
											</label>
											<span class="pull-aright mt5 mr5 hidden"><i class="icon-sort-alt-up" data-for="name"></i></span>
										</div>

										<div class="radio popblock-item no-border">
											<label class="wp100 p5">
												<input name="sort" type="radio" id="sort" value="type" <?= ( $filters[ 'sort' ] == 'type' ? 'checked' : '' ) ?>>
												<span class="custom-radio"><i class="icon-radio-check"></i></span>
												<span class="title">По каналу</span>
											</label>
											<span class="pull-aright mt5 mr5 hidden"><i class="icon-sort-alt-up clearevents" data-for="dstatus"></i></span>
										</div>

									</div>
									<div>

										<div class="divider pt10 pb10">Порядок сортировки</div>

										<div class="radio popblock-item">
											<label class="wp100 p5">
												<input name="order" type="radio" id="order" value="asc" <?= ( $filters[ 'order' ] == 'asc' ? 'checked' : '' ) ?>>
												<span class="custom-radio"><i class="icon-radio-check"></i></span>
												<span class="title">По возрастанию <i class="icon-sort-alt-down pull-right" data-for="order"></i></span>
											</label>
										</div>

										<div class="radio popblock-item no-border">
											<label class="wp100 p5">
												<input name="order" type="radio" id="order" value="desc" <?= ( $filters[ 'order' ] == 'desc' || !$filters[ 'order' ] ? 'checked' : '' ) ?>>
												<span class="custom-radio"><i class="icon-radio-check"></i></span>
												<span class="title">По убыванию <i class="icon-sort-alt-up pull-right" data-for="order"></i></span>
											</label>
										</div>

									</div>

									<hr>

									<div class="text-center">
										<a href="javascript:void(0)" onclick="$mainFunc.chats();" class="button wp90 mb10 border-box m0">Применить</a>
									</div>

								</div>
							</div>
						</li>
						<li data-id="newchats" title="Новые диалоги" class="relativ">
							<a href="javascript:void(0)">
								<i class="icon-chat-empty"></i>
							</a>
							<div class="new--chats hidden"></div>
						</li>
						<li data-id="reload" title="Обновить">
							<a href="javascript:void(0)">
								<i class="icon-arrows-ccw"></i>
							</a>
						</li>
					</ul>

				</form>

			</div>
			<div class="mainblock" data-id="tab-chats"></div>
			<div class="footter pagediv hidden1" data-id="tab-chats"></div>

		</div>
		<div class="messageslist">

			<div class="topper">

				<div class="chatUsers"></div>

				<ul class="wtext w0 text-left razdel" data-id="tab-chats">
					<li data-id="complete" class="popblock" title="Действия">
						<a href="javascript:void(0)">
							<div class="poprounder"><i class="icon-ok"></i></div>
							<span class="text">Действия</span>
						</a>
						<div class="popblock-menu w160">
							<div class="popblock-items">
								<div class="popblock-item p10 border-bottom nowrap" onclick="$mainFunc.invite()">
									<i class="icon-user-1 red"></i>&nbsp;Пригласить коллегу
								</div>
								<div class="popblock-item p10 border-bottom nowrap" onclick="$mainFunc.transfer()">
									<i class="icon-user-1 blue"></i>&nbsp;Передать
								</div>
								<div class="popblock-item p10 border-bottom nowrap" onclick="$mainFunc.close()">
									<i class="icon-ok green"></i>&nbsp;Завершить диалог
								</div>
								<div class="popblock-item p10 border-bottom nowrap hidden">
									<i class="icon-block-1 red"></i>&nbsp;Заблокировать
								</div>
								<div class="popblock-item p10 border-bottom nowrap hidden">
									<i class="icon-cancel-circled red"></i>&nbsp;Выйти из диалога
								</div>
								<div class="divider"></div>
								<div class="popblock-item p10 border-bottom nowrap" onclick="$mainFunc.chatDelete()">
									<i class="icon-trash-1 red"></i>&nbsp;Удалить диалог
								</div>
							</div>
						</div>
					</li>
				</ul>

				<div class="fullavatar" data-action="contactShow"></div>

				<div class="dialog-closer">
					<i class="icon-cancel"></i>
				</div>

			</div>

			<div class="dialogs"></div>

			<form action="php/chats.php" method="post" enctype="multipart/form-data" name="sendForm" id="sendForm">
				<input type="hidden" id="action" name="action" value="send">
				<div class="filebox wp100 hidden">

					<div class="eupload relativ">
						<input name="file[]" id="file[]" type="file" class="file wp100" multiple>
						<div class="idel hand delbox" title="Удалить">
							<i class="icon-cancel-circled red"></i>
						</div>
					</div>

				</div>
				<div class="messagetext">

					<div class="attachment p10 hand" id="addFile">
						<i class="icon-attach-1 fs-20 gray2"></i>
					</div>
					<div class="keyboard p10">

						<textarea name="text" id="message" placeholder="Написать сообщение"></textarea>

						<div class="send">
							<i class="icon-paper-plane fs-20 blue"></i>
						</div>

					</div>
					<div class="infodiv hidden p5 fs-09 description" style="overflow: auto;"></div>

				</div>
			</form>

		</div>
		<div class="contact">

			<div class="fullavatar"></div>
			<div class="contactinfo"></div>
			<div class="close" data-action="contactHide"><i class="icon-cancel"></i></div>
			<div class="refresh" title="Запросить с сервера"><i class="icon-arrows-ccw green"></i></div>

		</div>

	</div>
	<div class="lists hidden" data-id="statistics">

		<div class="topper" data-id="tab-statistics">

			<form id="statForm" name="statForm">

				<div class="flex-container float no-border1 mt10 box--child">

					<div class="flex-string wp50 ml10">

						<div class="inline period periodblock pl10" id="periods">

							<i class="icon-calendar-1 fs-11"></i>
							<input id="periodStart" name="periodStart" type="text" value="<?= $periodStart ?>" class="dateinput dstart">
							&divide;
							<input id="periodEnd" name="periodEnd" type="text" value="<?= $periodEnd ?>" class="dateinput dend">

						</div>
						<div class="inline presets popblock">

							<a href="javascript:void(0)"><i class="icon-ellipsis-vert"></i></a>

							<div class="popblock-menu w0">

								<div class="popblock-items" data-action="period" data-goal="periods">
									<div class="popblock-item p10 pr20 border-bottom nowrap" data-period="today">Сегодня</div>
									<div class="popblock-item p10 pr20 border-bottom nowrap" data-period="yestoday">Вчера</div>
									<div class="popblock-item p10 pr20 border-bottom nowrap" data-period="calendarweek">Неделя.&nbsp;Текущая</div>
									<div class="popblock-item p10 pr20 border-bottom nowrap" data-period="calendarweekprev">Неделя.&nbsp;Прошлая</div>
									<div class="popblock-item p10 pr20 border-bottom nowrap" data-period="month">Месяц.&nbsp;Текущий</div>
									<div class="popblock-item p10 pr20 border-bottom nowrap" data-period="prevmonth">Месяц.&nbsp;Прошлый</div>
									<div class="popblock-item p10 pr20 border-bottom nowrap" data-period="quart">Квартал.&nbsp;Текущий</div>
									<div class="popblock-item p10 pr20 border-bottom nowrap" data-period="prevquart">Квартал.&nbsp;Прошлый</div>
									<div class="popblock-item p10 pr20 border-bottom nowrap" data-period="year">Год</div>
								</div>

							</div>

						</div>
						<div class="inline buttonblock">
							<a href="javascript:void(0)" onclick="$mainFunc.reports()" class="m0 button greenbtn link">Показать</a>&nbsp;
						</div>

					</div>

					<div class="flex-string float pr15">

						<ul class="stext w0 pull-aright">
							<li data-file="channels" class="reports active" title="Каналы">
								<a href="javascript:void(0)">
									<div class="poprounder"><i class="icon-list-nested"></i></div><span class="text">Каналы</span>
								</a>
							</li>
							<li data-file="dialogs" class="reports" title="Диалоги">
								<a href="javascript:void(0)">
									<div class="poprounder"><i class="icon-chat-1"></i></div><span class="text">Диалоги</span>
								</a>
							</li>
							<li data-file="operators" class="reports" title="Операторы">
								<a href="javascript:void(0)" class="pr20">
									<div class="poprounder"><i class="icon-users-1"></i></div><span class="text">Операторы</span>
								</a>
							</li>
						</ul>

					</div>

				</div>

			</form>

		</div>
		<div class="mainblock p10" data-id="tab-statistics"></div>

	</div>

</div>

<!--шаблон блока для файлов-->
<div id="filetemplate" class="hidden">

	<div class="eupload relativ">
		<input name="file[]" id="file[]" type="file" class="file wp100" multiple>
		<div class="idel hand delbox" title="Удалить">
			<i class="icon-cancel-circled red"></i>
		</div>
	</div>

</div>

<audio src="assets/audio/new-notification.ogg" type="audio/ogg" id="chatAudio">
	<source src="assets/audio/new-notification.ogg" type="audio/ogg">
	<source src="assets/audio/new-notification.mp3" type="audio/mpeg">
</audio>

</body>
</html>
