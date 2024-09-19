<?php
/* ============================ */
/*         SalesMan CRM         */
/* ============================ */
/* (C) 2019 Vladislav Andreev   */
/*       SalesMan Project       */
/*        www.isaler.ru         */
/*        ver. 2019.x           */
/* ============================ */

global $rootpath, $hooks;

// If this file is called directly, abort.
//use Chats\Chats;

if ( defined( 'SMPLUGIN' ) ) {

	$hooks -> add_action( 'plugin_activate', 'activate_socialchats' );
	$hooks -> add_action( 'plugin_deactivate', 'deactivate_socialchats' );
	$hooks -> add_action( 'plugin_update', 'update_socialchats' );

	/**
	 * Активация плагина
	 *
	 * @param array $argv
	 */
	function activate_socialchats(array $argv = []) {

		$isCloud  = $GLOBALS['isCloud'];
		$identity = $GLOBALS['identity'];
		$database = $GLOBALS['database'];
		$sqlname  = $GLOBALS['sqlname'];
		$db       = $GLOBALS['db'];
		$rootpath = $GLOBALS['rootpath'];

		$ypath = $rootpath."/plugins/socialChats";
		$spath = $ypath."/settings/";

		$mes = [];

		createDir($rootpath."/files/chatcash");
		createDir($rootpath."/files/chatcash/files");

		//если таблицы нет, то создаем её
		$da = $db -> getOne( "SELECT COUNT(*) as count FROM INFORMATION_SCHEMA.STATISTICS WHERE table_schema = '$database' and TABLE_NAME = '{$sqlname}chats_channels'" );
		if ( $da == 0 ) {

			try {

				$db -> query( "
					CREATE TABLE {$sqlname}chats_channels (
						`id` INT(20) NOT NULL AUTO_INCREMENT,
						`datum` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
						`name` VARCHAR(200) NULL DEFAULT NULL COMMENT 'название канала',
						`type` VARCHAR(100) NULL DEFAULT NULL COMMENT 'тип канала (провайдер)',
						`channel_id` VARCHAR(255) NULL DEFAULT NULL COMMENT 'внешний идентификатор',
						`token` VARCHAR(255) NULL DEFAULT NULL COMMENT 'ключ  канала',
						`settings` TEXT NULL COMMENT 'настройки (json)',
						`active` CHAR(5) NULL DEFAULT 'on' COMMENT 'активность канала',
						`identity` INT(20) NULL DEFAULT '1' COMMENT 'идентификатор',
						PRIMARY KEY (`id`),
						INDEX `name_channel_id` (`name`, `channel_id`)
					)
					COMMENT='Каналы, провайдеры'
					COLLATE 'utf8_general_ci'
					ENGINE=InnoDB
				" );

			}
			catch ( Exception $e ) {

				$mes[] = 'Ошибка'.$e -> getMessage().' в строке '.$e -> getCode();

			}

		}

		$da = $db -> getOne( "SELECT COUNT(*) as count FROM INFORMATION_SCHEMA.STATISTICS WHERE table_schema = '$database' and TABLE_NAME = '{$sqlname}chats_chat'" );
		if ( $da == 0 ) {

			try {

				$db -> query( "
					CREATE TABLE `{$sqlname}chats_chat` (
						`id` INT(20) NOT NULL AUTO_INCREMENT,
						`channel_id` VARCHAR(200) NULL DEFAULT NULL COMMENT 'id канала',
						`chat_id` VARCHAR(200) NULL DEFAULT NULL COMMENT 'id чата у провайдера (чат с конкретным посетителем)',
						`client_id` VARCHAR(200) NULL DEFAULT NULL COMMENT 'id посетителя',
						`client_firstname` VARCHAR(255) NULL DEFAULT NULL COMMENT 'имя посетителя',
						`client_lastname` VARCHAR(255) NULL DEFAULT NULL COMMENT 'фамилия посетителя',
						`client_avatar` VARCHAR(255) NULL DEFAULT NULL COMMENT 'аватар посетителя',
						`users` VARCHAR(255) NULL DEFAULT NULL COMMENT 'id сотрудников в чате',
						`clid` INT(20) NULL DEFAULT NULL COMMENT 'id клиента',
						`pid` INT(20) NULL DEFAULT NULL COMMENT 'id контакта',
						`status` CHAR(10) NULL DEFAULT 'free' COMMENT 'статус чата',
						`type` CHAR(10) NULL DEFAULT NULL COMMENT 'тип чата, для мультиканальных чатов',
						`phone` CHAR(15) NULL DEFAULT NULL COMMENT 'номер телефона',
						`email` CHAR(255) NULL DEFAULT NULL COMMENT 'email',
						`identity` INT(20) NULL DEFAULT '1' COMMENT 'идентификатор',
						PRIMARY KEY (`id`),
						INDEX `channel_id_chat_id_users` (`channel_id`, `chat_id`, `identity`),
						INDEX `clid_pid` (`clid`, `pid`)
					)
					COMMENT='Чаты'
					COLLATE 'utf8_general_ci'
					ENGINE=InnoDB
				" );

			}
			catch ( Exception $e ) {

				$mes[] = 'Ошибка'.$e -> getMessage().' в строке '.$e -> getCode();

			}

		}

		$da = $db -> getOne( "SELECT COUNT(*) as count FROM INFORMATION_SCHEMA.STATISTICS WHERE table_schema = '$database' and TABLE_NAME = '{$sqlname}chats_dialogs'" );
		if ( $da == 0 ) {

			try {

				$db -> query( "
					CREATE TABLE `{$sqlname}chats_dialogs` (
						`id` INT(20) NOT NULL AUTO_INCREMENT,
						`chat_id` VARCHAR(100) NULL DEFAULT NULL COMMENT 'id чата',
						`message_id` VARCHAR(100) NULL DEFAULT NULL COMMENT 'id сообщения',
						`direction` CHAR(5) NULL DEFAULT NULL COMMENT 'направление сообщения',
						`status` CHAR(10) NULL DEFAULT NULL COMMENT 'статус отправки',
						`content` TEXT NULL COMMENT 'текст сообщения',
						`readability` TEXT NULL COMMENT 'просмотр ссылок',
						`attachment` TEXT NULL COMMENT 'вложения',
						`server_timestamp` DATETIME NULL DEFAULT NULL COMMENT 'время сообщения на сервере провайдера',
						`datum` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'время сообщения в crm',
						`iduser` INT(20) NULL DEFAULT NULL COMMENT 'id пользователя, написавшего сообщения',
						`identity` INT(20) NULL DEFAULT '1' COMMENT 'идентификатор',
						PRIMARY KEY (`id`),
						INDEX `chat_id` (`chat_id`) USING BTREE,
						INDEX `direction` (`direction`) USING BTREE,
						INDEX `status` (`status`) USING BTREE,
						INDEX `iduser` (`iduser`) USING BTREE,
						INDEX `datum` (`datum`) USING BTREE,
						INDEX `content` (`content`(100)) USING BTREE
					)
					COMMENT='Диалоги по чатам'
					COLLATE 'utf8_general_ci'
					ENGINE=InnoDB
				" );

			}
			catch ( Exception $e ) {

				$mes[] = 'Ошибка'.$e -> getMessage().' в строке '.$e -> getCode();

			}

		}

		$da = $db -> getOne( "SELECT COUNT(*) as count FROM INFORMATION_SCHEMA.STATISTICS WHERE table_schema = '$database' and TABLE_NAME = '{$sqlname}chats_logs'" );
		if ( $da == 0 ) {

			try {

				$db -> query( "
					CREATE TABLE `{$sqlname}chats_logs` (
						`id` INT(20) NOT NULL AUTO_INCREMENT,
						`datum` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'время записи',
						`chat_id` VARCHAR(255) NULL COMMENT 'id чата' COLLATE 'utf8_general_ci',
						`event` CHAR(10) NULL COMMENT 'тип события' COLLATE 'utf8_general_ci',
						`oldvalue` VARCHAR(300) NULL COMMENT 'старое значение' COLLATE 'utf8_general_ci',
						`newvalue` VARCHAR(300) NULL COMMENT 'новое значение' COLLATE 'utf8_general_ci',
						`iduser` INT(20) NULL COMMENT 'автор события',
						`identity` INT(20) NULL DEFAULT 1,
						PRIMARY KEY (`id`) USING BTREE,
						INDEX `chat_id` (`chat_id`) USING BTREE
					)
					COMMENT='Логи событий диалогов'
					COLLATE='utf8_general_ci'
					ENGINE=InnoDB
				" );

			}
			catch ( Exception $e ) {

				$mes[] = 'Ошибка'.$e -> getMessage().' в строке '.$e -> getCode();

			}

		}

		// добавим доступы
		$da = customSettings( 'socialChats' );
		if ( empty( $da ) ) {

			try {

				$users = $db -> getCol( "SELECT iduser FROM ".$sqlname."user WHERE iduser > 0 and isadmin = 'on' and secrty = 'yes' and identity = '$identity'" );

				customSettings( 'socialChats', 'put', ["params" => $users] );

				file_put_contents( $spath."access.json", json_encode( $users ) );

			}
			catch ( Exception $e ) {

				$mes[] = 'Ошибка'.$e -> getMessage().' в строке '.$e -> getCode();

			}

		}

		$argv = array_merge( $mes, $argv );

		file_put_contents( $rootpath."/cash/actions.log", json_encode_cyr( $argv ) );

	}

	/**
	 * Деактивация плагина
	 *
	 * @param array $argv
	 */
	function deactivate_socialchats(array $argv = []) {

		$rootpath = $GLOBALS['rootpath'];

		file_put_contents( $rootpath."/cash/actions.log", json_encode_cyr( $argv ) );

	}

	/**
	 * Обновление плагина
	 *
	 * @param array $argv
	 */
	function update_socialchats(array $argv = []) {

		global $database, $sqlname, $db;

		$count = $db -> getOne( "SELECT DISTINCT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS WHERE TABLE_SCHEMA = '$database' and TABLE_NAME = '{$sqlname}chats_logs' and INDEX_NAME = 'chat_id'" );
		if ( $count == 0 ) {

			$db -> query("
			ALTER TABLE `{$sqlname}chats_dialogs`
				ADD INDEX `chat_id` (`chat_id`),
				ADD INDEX `direction` (`direction`),
				ADD INDEX `status` (`status`),
				ADD INDEX `iduser` (`iduser`),
				ADD INDEX `datum` (`datum`),
				ADD INDEX `content` (`content`(100))
			");

		}

		$count = $db -> getOne( "SELECT DISTINCT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS WHERE TABLE_SCHEMA = '$database' and TABLE_NAME = '{$sqlname}chats_logs' and INDEX_NAME = 'chat_id'" );
		if ( $count == 0 ) {

			$db -> query("ALTER TABLE `{$sqlname}chats_logs` ADD INDEX `chat_id` (`chat_id`)");

		}

	}

}

require_once $rootpath."/plugins/socialChats/php/Class/Chats.php";

$chatUsers = (new Chats\Chats) -> getOperators();

global $iduser1;

if( in_array( $iduser1, $chatUsers ) ) {

	// добавляем таб в карточку Клиента, Контакта
	$hooks -> add_filter( "card_tab", "schat_card_tab" );
	function schat_card_tab($tab = [], $params = []){

		if($params['clid'] > 0){

			$tab[] = [
				"name"  => "schat",
				"title" => "Диалоги",
				"class" => "",
				"icon"  => "icon-chat",
				"url"   => "/plugins/socialChats/php/card.php"
			];

		}
		if($params['pid'] > 0){

			$tab[] = [
				"name"  => "schat",
				"title" => "Диалоги",
				"class" => "",
				"icon"  => "icon-chat",
				"url"   => "/plugins/socialChats/php/card.php"
			];

		}

		return $tab;

	}

	$hooks -> add_action( 'main__js', 'js_main_socialchats' );
	$hooks -> add_action( 'main__css', 'css_main_socialchats' );

	$hooks -> add_action( 'card__js', 'js_card_socialchats' );
	$hooks -> add_action( 'card__css', 'css_card_socialchats' );

	$hooks -> add_action( 'main__body', 'body_socialchats' );

	function js_main_socialchats() {

		global $rootpath, $iduser1;

		require_once $rootpath."/plugins/socialChats/php/autoload.php";

		// параметры comet-сервера
		$comet  = new Chats\Comet($iduser1);
		$cometSettings = $comet -> getSettings();

		if($cometSettings['dev_id'] != '') {

			// регистрация юзера
			$comet -> setUser();

		}

		print "<script src=\"/assets/js/favico.js/favico-0.3.10.min.js\"></script>\n";

		print "
		<script>
			const cometUserKey = '".$cometSettings['user_key']."';
			const cometUserID = '".$cometSettings['user_id']."';
			const cometDevID = '".$cometSettings['dev_id']."';
			const cometChannel = '".$cometSettings['channel']."';
			
			let faviconChat = new Favico({
				type : 'rectangle',
				animation: 'slide',
				bgColor : '#1565C0',
			});
		</script>
		";

		print "<script src=\"/plugins/socialChats/assets/js/chats.init.js\"></script>\n";
		print "<script src=\"/plugins/socialChats/assets/js/CometServerApi.js\"></script>\n";

	}

	function css_main_socialchats() {

		print "<link rel=\"stylesheet\" href=\"/plugins/socialChats/assets/css/chats.init.css\">\n";

	}

	function js_card_socialchats() {

		// Добавляем возможность отправки сообщений

	}

	function css_card_socialchats() {

		print "<link rel=\"stylesheet\" type=\"text/css\" href=\"/plugins/socialChats/assets/css/chats.card.css\">\n";

	}

	/**
	 * Добавляем фрейм
	 */
	function body_socialchats() {

		print '
		<div class="chatframe--container">
			<iframe id="chatframe"></iframe>
			<div class="chatframe--close" title="Закрыть"><i class="icon-cancel"></i></div>
			<div class="chatframe--url" title="Открыть в новом окне"><i class="icon-popup"></i></div>
		</div>
		';

	}

}