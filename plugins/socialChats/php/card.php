<?php
/* ============================ */
/*         SalesMan CRM         */
/* ============================ */
/* (C) 2019 Vladislav Andreev   */
/*       SalesMan Project       */
/*        www.isaler.ru         */
/*        ver. 2019.x           */

/* ============================ */

use Chats\Chats;

$rootpath = realpath( __DIR__.'/../../../' );
$ypath    = realpath( __DIR__.'/../../../' )."/plugins/socialChats";

error_reporting( E_ERROR );

require_once $rootpath."/inc/config.php";
require_once $rootpath."/inc/dbconnector.php";
require_once $rootpath."/inc/auth_main.php";
require_once $rootpath."/inc/settings.php";
require_once $rootpath."/inc/func.php";

require_once $ypath."/php/autoload.php";
require_once $ypath."/vendor/autoload.php";

$action = $_REQUEST[ 'action' ];
$clid   = $_REQUEST[ 'clid' ];
$pid    = $_REQUEST[ 'pid' ];

$icons = $channel = [];
$whatsapp = '';

$chat = new Chats();

$users = $chat -> getOperatorsFull();
//$icons = Chats ::channelsIcon();

$channels = $chat -> getChannels();
foreach ($channels as $ch){

	$icons[$ch['channel_id']] = $ch['icon'];
	$channel[$ch['channel_id']] = $ch['messenger'];

	if($ch['messenger'] == 'Whatsapp'){

		$whatsapp = $ch['channel_id'];

	}

}

//print_r($icons);

$template = '
	{{#list}}
		<div class="answer" data-messageid="{{message_id}}" data-id="{{id}}">

			<div class="userpic">

				<div class="avatar--mini" style="{{#avatar}}background-image: url(\'{{avatar}}\'); background-size:cover; {{/avatar}} background-color: {{background}};" title="{{name}}"></div>

			</div>
			<div class="dialogmessage">

					<span class="dialogmessage--message {{boxbg}}">

						<div class="Bold fs-12 {{color}} mr20 pr10">{{name}}</div>

						{{#content}}
							<div class="fs-11 flh-12 mt10 inline text-wrap1">{{{content}}}</div>
						{{/content}}

						{{#attachment}}
							{{#document}}{{/document}}
							{{#image}}{{/image}}
							{{#video}}{{/video}}
							{{#ptt}}{{/ptt}}
							{{#audio}}{{/audio}}
						{{/attachment}}

						<div class="time">{{date}}</div>

						<div class="deletemessage" title="Удалить"><i class="icon-cancel-circled"></i></div>

						{{#hasAttachment}}
							<div class="attachments mt10 infodiv p0 bgwhite">
								{{#attachment}}

									{{#doc}}
										<div class="p5 text-wrap"><a href="{{url}}" target="_blank"><i class="{{icon}}"></i>{{title}}</a></div>
									{{/doc}}
									{{#photo}}
										<div class="p5 text-wrap"><a href="{{url}}" target="_blank"><i class="{{icon}}"></i>{{title}}</a></div>
									{{/photo}}

								{{/attachment}}
							</div>
						{{/hasAttachment}}

						{{#readability}}
							<div class="readability">
								{{#content}}
									<div class="ra--text">
										<div class="Bold fs-11 mb10">{{readability.title}}</div>
										<div class="fs-09 mb10">{{readability.content}}</div>
										<div class="Bold gray2">{{readability.url}}</div>
									</div>
								{{/content}}
							</div>
						{{/readability}}

					</span>

			</div>

		</div>
	{{/list}}
	{{^total}}
	<div class="attention bgwhite">диалог не начат</div>
	{{/total}}

	{{#chat.isnew}}
	<div class="divider">Данный диалог не имеет куратора</div>
	<div class="mt20 text-center">
		<a href="javascript:void(0)" onclick="$mainFunc.chatSetUser()" class="button greenbtn">Взять в работу</a>
	</div>
	{{/chat.isnew}}
';

// получение списка сообщений
if ( $action == 'messages' ) {

	//print_r($_REQUEST);

	$page = $_REQUEST[ 'page' ];
	//$clid = $_REQUEST[ 'clid' ];
	//$pid  = $_REQUEST[ 'pid' ];
	$sort = '';
	$dateText = '';
	$current  = current_datum();

	$list = [];

	if ( $pid > 0 )
		$sort .= " pid = '$pid' AND";

	elseif ( $clid > 0 )
		$sort .= " clid = '$clid' AND";

	else
		goto ext;

	$query = "
		SELECT 
			*,
			(SELECT channel_id FROM {$sqlname}chats_chat WHERE {$sqlname}chats_chat.chat_id = {$sqlname}chats_dialogs.chat_id) as channel_id
		FROM {$sqlname}chats_dialogs 
		WHERE 
			chat_id IN (SELECT chat_id FROM {$sqlname}chats_chat WHERE id > 0 AND $sort identity = '$identity') 
		ORDER BY id DESC";

	$result = $db -> query( $query );
	$total  = $db -> numRows( $result );

	$page = ( !isset( $page ) || empty( $page ) || $page <= 0 ) ? 1 : (int)$page;

	$lines_per_page = 30;

	$page_for_query = $page - 1;
	$lpos           = $page_for_query * $lines_per_page;
	$count_pages    = ceil( $total / $lines_per_page );

	$query .= " LIMIT $lpos,$lines_per_page";

	$data = $db -> getAll( $query );
	foreach ( $data as $da ) {

		$echat = $chat -> chatInfoShort( 0, $da[ 'chat_id' ] );

		$dateDivider = NULL;

		$avatar = ( $da[ 'direction' ] == 'in' ) ? $echat[ 'avatar' ] : $users[ $da[ 'iduser' ] ][ 'avatar' ];
		$color  = ( $da[ 'direction' ] == 'in' ) ? "blue" : "green";
		$name   = ( $da[ 'direction' ] == 'in' ) ? $echat[ 'firstname' ].' '.$echat[ 'lastname' ] : $users[ $da[ 'iduser' ] ][ 'title' ];
		$direct = ( $da[ 'direction' ] == 'in' ) ? 'icon-reply' : 'icon-forward-1';

		$date = get_smdate( $da[ 'datum' ] );

		$text = htmlspecialchars_decode( nl2br( $da[ 'content' ] ) );

		$readability = $da[ 'readability' ] != '' ? json_decode( $da[ 'readability' ], true ) : NULL;
		$attachent   = $da[ 'attachment' ] != NULL ? json_decode( $da[ 'attachment' ], true ) : NULL;

		$date = get_smdate( $da[ 'datum' ] );

		if ( diffDate2( $date, $current ) < 0 && $dateText != $date ) {

			$dateText    = $date;
			$dateDivider = format_date_rus_name( $dateText );

		}
		elseif ( diffDate2( $date, $current ) == 0 && $dateText != $date ) {

			$dateText    = $date;
			$dateDivider = 'Сегодня';

		}

		$list[] = [
			'id'            => $da[ 'id' ],
			'message_id'    => $da[ 'message_id' ],
			'chat_id'       => $da[ 'chat_id' ],
			'datum'         => $date,
			'direction'     => $da[ 'direction' ],
			'status'        => $da[ 'status' ],
			'avatar'        => $avatar,
			'color'         => $color,
			'background'    => $echat[ 'color' ],
			'iduser'        => $da[ 'iduser' ],
			'name'          => $name,
			'content'       => link_it( $text ),
			'date'          => getTime( (string)$da[ 'datum' ] ),
			'dateDivider'   => $dateDivider,
			'boxbg'         => $da[ 'direction' ] == 'out' ? 'user' : '',
			'readability'   => ( $readability[ 'title' ] != '' || $readability[ 'image' ] != '' ) ? $readability : NULL,
			'attachment'    => $attachent,
			'hasAttachment' => $attachent != NULL ? true : NULL,
			"diricon"       => $direct,
			"icon"          => $icons[$da['channel_id']]
		];

	}

	ext:

	$datalist = [
		"list"  => $list,
		"page"  => $page,
		"total" => $count_pages
	];

	print json_encode_cyr( $datalist );

	exit();

}

// первоначальная загрузка в карточку
if ( $action == '' ) {

	// все мобильные номера карточки
	$phones = getMobileFromCard($clid, $pid, true);

	if($pid > 0) {

		$chats = $db -> getAll( "SELECT chat_id, channel_id, CONCAT(client_firstname, ' ', client_lastname) as name, pid, phone, type FROM {$sqlname}chats_chat WHERE pid = '$pid'" );

	}
	elseif($clid > 0){

		$pids = $db -> getCol("SELECT pid FROM {$sqlname}personcat WHERE clid = '$clid'");

		if(!empty($pids))
			$chats = $db -> getAll( "SELECT chat_id, channel_id, CONCAT(client_firstname, ' ', client_lastname) as name, pid, phone, type FROM {$sqlname}chats_chat WHERE clid = '$clid' OR pid IN (".yimplode(",", $pids).")" );
		else
			$chats = $db -> getAll( "SELECT chat_id, channel_id, CONCAT(client_firstname, ' ', client_lastname) as name, pid, phone, type FROM {$sqlname}chats_chat WHERE clid = '$clid'" );

	}

	if($whatsapp != '') {

		// смотрим, какие чаты уже есть в массиве
		$chatsID = [];
		foreach ($chats as $chat){

			$chatsID[] = $chat['chat_id'];

		}

		foreach ($phones as $person) {

			// если чата нет в массиве, то добавляем
			if( !in_array( $person[ 'phone' ]."@c.us", $chatsID, true ) )
				$chats[] = [
					"chat_id"    => $person[ 'phone' ]."@c.us",
					"channel_id" => $whatsapp,
					"name"       => $person[ 'title' ],
					"pid"        => $person[ 'pid' ],
					"phone"      => $person[ 'phone' ],
					"type"       => "Whatsapp"
				];

		}

	}

	//print_r($channel);

	?>
	<?php
	if( !empty($chats) ){
	?>
	<div class="chat--form">
		<form action="/plugins/socialChats/php/chats.php" method="post" enctype="multipart/form-data" name="sendForm" id="sendForm">
			<input type="hidden" id="action" name="action" value="send">
			<input type="hidden" id="chat_id" name="chat_id" value="<?php echo $chats[0]['chat_id']?>">
			<input type="hidden" id="channel_id" name="channel_id" value="<?php echo $chats[0]['channel_id']?>">
			<input type="hidden" id="pid" name="pid" value="<?php echo $chats[0]['pid']?>">
			<input type="hidden" id="clid" name="clid" value="<?php echo $clid?>">
			<input type="hidden" id="phone" name="phone" value="<?php echo $chats[0]['phone']?>">
			<input type="hidden" id="type" name="type" value="<?php echo $chats[0]['type']?>">
			<div class="filebox wp100 hidden">

				<div class="eupload relative">
					<input name="file[]" id="file[]" type="file" onchange="addefile();" class="file wp100" multiple>
					<div class="idel hand delbox" title="Удалить">
						<i class="icon-cancel-circled red"></i>
					</div>
				</div>

			</div>

			<div class="channel-select wp100">
				<div class="tags">Выбор канала:</div>
				<?php
				foreach ($chats as $i => $chat){

					print '
					<div class="chat--tag '.($i == 0 ? 'active' : '').'" data-chat="'.$chat['chat_id'].'" data-phone="'.$chat['phone'].'" data-pid="'.$chat['pid'].'" data-channel="'.$chat['channel_id'].'" title="'.$chat['chat_id'].'">
						<img src="/plugins/socialChats/assets/images/'.$icons[$chat['channel_id']].'" width="12" style="vertical-align:middle">&nbsp;'.$chat['name'].'
					</div>
					';

				}
				?>
			</div>
			<div class="messagetext wp100">

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
	<?php } ?>
	<div class="chat--dialogs"></div>
	<div id="chatpages" class="viewdiv relativ">
		<div class="chat--pages"></div>
	</div>

	<div class="to--plugin" onclick="openlink('/plugins/socialChats/chats.php')" title="Перейти к чатам">
		<i class="icon-chat-1"></i>
	</div>


	<!--шаблон блока для файлов-->
	<div id="filetemplate" class="hidden">

		<div class="eupload relative">
			<input name="file[]" id="file[]" type="file" onchange="addefile();" class="file wp100" multiple>
			<div class="idel hand delbox" title="Удалить">
				<i class="icon-cancel-circled red"></i>
			</div>
		</div>

	</div>

	<script>

		var page = 1;
		var fList = [];
		var phones = [];

		$.Mustache.load('/plugins/socialChats/assets/tpl/chat.mustache');

		$(document).ready(function () {

			chatgetMessages().then(function () {

				var wh = $(window).height() - $('.fixx').height() - $('.chat--form').height() - 160;

				$('.chat--dialogs').css({"height": wh + "px"});

			});

			/*findPhones().then(
				function(){
					console.log(phones);
				}
			);*/

		});

		async function chatgetMessages() {

			$('.chat--pages').html('<img src="/assets/images/loading.svg">');

			fetch("/plugins/socialChats/php/card.php?action=messages&page=" + page + "&clid=<?php echo $clid ?>&pid=<?php echo $pid ?>")
				.then(response => response.json())
				.then(viewData => {

					$('.chat--dialogs').empty().mustache('cardTpl', viewData);

					let pageall = viewData.total;
					let prev = page - 1;
					let next = page + 1;
					let pg = '';

					if (pageall > 1) {

						pg = '<div class="text-left" id="pages" style="z-index: 1">' +
							(page > 1 ? '<div onclick="chatChangePage(1)" data-page="1"><<</div>&nbsp;<div onclick="chatChangePage(\'' + prev + '\')" data-page="' + prev + '"><</div>&nbsp;' : '') +
							'Страница: <span class="Bold">' + page + '</span> из ' + pageall + '&nbsp;' +
							(page < pageall ? '<div onclick="chatChangePage(\'' + next + '\')" data-page="' + next + '">></div><div onclick="chatChangePage(\'' + pageall + '\')" data-page="' + pageall + '">>></div>' : '') +
							'</div>';

					}

					$('.chat--pages').empty().html( pg );

				})
				.then(r => {
					$('body').scrollTop(0);
				})
				.catch(error => {
					console.log(error);
				});

			$('#sendForm').ajaxForm({
				dataType: 'json',
				beforeSubmit: function () {

					//$('.chat--dialogs').find('.space-100').prepend('<div class="notify"><i class="icon-mail-alt"></i> Отправляю сообщение...</div><div class="space-100"></div>');
					return true;

				},
				success: function (data) {

					if (data.result !== 'ok') {

						Swal.fire({
							imageUrl: '/assets/images/error.svg',
							imageWidth: 50,
							imageHeight: 50,
							html: '' + data.result + '',
							icon: 'info',
							showConfirmButton: false,
							timer: 3500
						});

					}

					// Очищаем файлы
					var fhtml = $('#filetemplate').html();
					$('.filebox').empty().append(fhtml).addClass('hidden');
					$('.description').empty().addClass('hidden');

					$('#message').text('');
					$('.messagetext').find('#message').val('');

					chatgetMessages();

				}
			});

		}

		function chatChangePage(npage) {

			page = parseInt(npage);

			chatgetMessages();

		}

		$(document).off('click', '.chat--tag');
		$(document).on('click', '.chat--tag', function(){

			var chat_id = $(this).data('chat');
			var channel_id = $(this).data('channel');
			var pid = $(this).data('pid');
			var phone = $(this).data('phone');
			var type = $(this).data('type');

			$('#chat_id').val(chat_id);
			$('#channel_id').val(channel_id);
			$('#pid').val(pid);
			$('#phone').val(phone);
			$('#type').val(type);

			$('.chat--tag').removeClass('active');
			$(this).addClass('active');

		});

		// добавляем файлы для загрузки
		$(document).on('change', '#file\\[\\]', function () {

			var string = '';
			var size, color;
			var ext;
			var i = 0;
			var extention = ['doc', 'docx', 'xls', 'xlsx', 'zip', 'pptx', 'ppt', 'csv', 'pdf', 'png', 'jpeg', 'jpg', 'gif', 'txt'];

			//fList = [];

			$('.file').each(function () {

				var substring = '';

				for (var x = 0; x < this.files.length; x++) {

					size = this.files[x].size / 1024 / 1024;
					ext = this.files[x].name.split(".");

					var elength = ext.length;
					var carrentExt = ext[elength - 1].toLowerCase();

					color = (parseInt(size) > parseInt(10)) ? 'red' : 'green';
					color = (in_array(carrentExt, extention)) ? color : 'red';

					substring += '<div class="p5">' + '[ <b class="' + color + '">' + carrentExt + '</b> ] ' + this.files[x].name + ' <span class="' + color + '">[' + setNumFormat(size.toFixed(2)) + ' Mb]</span></div>';

				}

				if (substring !== '')
					string += '<div class="p10 infodiv bgwhite relative sfile" style="word-break: break-all" data-file="' + x + '" data-index="' + i + '"><div class="pull-aright hand pt5 fdel"><i class="icon-cancel-squared red"></i></div>' + substring + '</div>';

				i++;

				fList.push(this.files);

			});

			$('.description').empty().append('<div class="pt5 pb5">' + string + '</div> <div class="fs-10"><b class="red">Красным</b> выделены файлы, которые не будут загружены поскольку либо превышают допустимый размер, либо не соответствуют формату</div>').removeClass('hidden');

		});

		// имитация клика на поле выбора файла кнопкой
		$(document).off('click', '#addFile');
		$(document).on('click', '#addFile', function () {

			var $elm = $('.filebox').find('.eupload:last').find('input[type="file"]');

			if (!$('#message').prop('disabled')) {
				//$('.eupload:last-of-type').find('#file\\[\\]').click();
				$elm.click();
			}

		});

		// удаляем файлы для загрузки
		$(document).off('click', '.fdel');
		$(document).on('click', '.fdel', function () {

			var currentIndex = $(this).parent(".infodiv").data('index');
			var count = $('.eupload').length;

			if (count > 1)
				$('.eupload:eq(' + currentIndex + ')').remove();
			else
				$('.eupload:eq(' + currentIndex + ')').find('#file\\[\\]').val('');

			$(this).parent(".infodiv").remove();

			if ($('.sfile').size() === 0)
				$('.description').empty().addClass('hidden');

		});

		/**
		 * отправка сообщения
		 */
		$(document).off('click', '.send');
		$(document).on('click', '.send', function () {

			if (!$('#message').prop('disabled'))
				sendmessage();

			else {

				Swal.fire({
					imageUrl: '/assets/images/error.svg',
					imageWidth: 50,
					imageHeight: 50,
					html: 'Сначала надо принять диалог',
					icon: 'info',
					showConfirmButton: false,
					timer: 3500
				});

			}

		});

		/*Отправка*/
		var sendmessage = function () {

			Swal.fire({
				icon: 'info',
				imageUrl: '/assets/images/signal.png',
				position: 'bottom-end',
				background: "var(--blue)",
				title: '<div class="white fs-11">Отправляю..</div>',
				showConfirmButton: false,
				timer: 1500
			});

			$('#sendForm').submit();

		}

		/*Поиск мобильных. Отключено, т.к. требуется загрузка вкладки Контакты*/
		var findPhones = async function(){

			$('.phonenumber.ismob').each(function () {

				var phone = $(this).data('phone');

				if (!in_array(phone, phones)) {
					phones.push(phone);
				}

			});

		}

	</script>
	<?php

}

