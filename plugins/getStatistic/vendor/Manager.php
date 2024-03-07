<?php
/* ============================ */
/*         SalesMan CRM         */
/* ============================ */
/* (C) 2018 Vladislav Andreev   */
/*      SalesMan Project        */
/*        www.isaler.ru         */
/*          ver. 2018.x         */

/* ============================ */

class Manager {

	public static function BotInfo($id = 0, $name = '') {

		$rootpath = dirname( __DIR__, 3 );

		require_once $rootpath."/inc/config.php";
		require_once $rootpath."/inc/dbconnector.php";

		$sqlname = $GLOBALS['sqlname'];
		$db      = $GLOBALS['db'];

		$bot = [];

		if ( $id != '' ) {
			$bot = $db -> getRow( "select * from ".$sqlname."sendstatistic_bots WHERE botid = '$id'" );
		}
		elseif ( $name != '' ) {
			$bot = $db -> getRow( "select * from ".$sqlname."sendstatistic_bots WHERE name = '$name'" );
		}

		if(!empty($bot)) {

			for ( $i = 0; $i < 20; $i++ ) {
				unset( $bot[ $i ] );
			}
		}

		return $bot;

	}

	public function BotSave($id, $params = []): string {

		$rootpath = dirname( __DIR__, 3 );

		require_once $rootpath."/inc/config.php";
		require_once $rootpath."/inc/dbconnector.php";
		require_once $rootpath."/inc/func.php";

		$identity = $GLOBALS['identity'];
		$sqlname  = $GLOBALS['sqlname'];
		$db       = $GLOBALS['db'];

		if ( (int)$id == 0 ) {

			$params['identity'] = $identity;

			try {

				$db -> query( "INSERT INTO ".$sqlname."sendstatistic_bots SET ?u", $params );
				$mes = 'Готово';

			}
			catch ( Exception $e ) {

				$mes = 'Ошибка'.$e -> getMessage().' в строке '.$e -> getCode();

			}

		}
		else {

			try {

				$db -> query( "UPDATE ".$sqlname."sendstatistic_bots SET ?u WHERE id = '$id'", $params );
				$mes = 'Готово';

			}
			catch ( Exception $e ) {

				$mes = 'Ошибка'.$e -> getMessage().' в строке '.$e -> getCode();

			}

		}

		return $mes;

	}

	public function BotDelete($id): array {

		$rootpath = dirname( __DIR__, 3 );

		require_once $rootpath."/inc/config.php";
		require_once $rootpath."/inc/dbconnector.php";
		require_once $rootpath."/inc/func.php";

		$sqlname = $GLOBALS['sqlname'];
		$db      = $GLOBALS['db'];
		$fpath   = $GLOBALS['fpath'];

		$mes = [];

		$file     = '../../data/'.$fpath.'settings.json';
		$settings = json_decode( file_get_contents( $file ), true );

		$bot = self ::BotInfo( $id );

		switch ($bot['tip']) {

			case 'viber':

				require_once $rootpath."/plugins/sendstatistic/vendor/viber-bot-api/Viber.php";

				$viber = new \Viber( $settings['token'] );
				$mes[] = $viber -> deleteWebhook();

			break;

		}

		try {

			$db -> query( "DELETE FROM ".$sqlname."sendstatistic_bots WHERE id = '$id'" );
			$mes[] = 'Готово';

		}
		catch ( Exception $e ) {

			$mes[] = 'Ошибка'.$e -> getMessage().' в строке '.$e -> getCode();

		}

		return $mes;

	}

	public static function UserInfo($userid = 0, $id = 0): array {

		$rootpath = dirname( __DIR__, 3 );

		require_once $rootpath."/inc/config.php";
		require_once $rootpath."/inc/dbconnector.php";
		require_once $rootpath."/inc/func.php";

		$identity = $GLOBALS['identity'];
		$sqlname  = $GLOBALS['sqlname'];
		$db       = $GLOBALS['db'];

		$user = [];

		if ( $userid != '' ) {
			$r = $db -> getRow( "SELECT * FROM ".$sqlname."sendstatistic_users WHERE userid = '$userid' AND identity = '$identity'" );
		}
		elseif ( $id > 0 ) {
			$r = $db -> getRow( "SELECT * FROM ".$sqlname."sendstatistic_users WHERE id = '$id' AND identity = '$identity'" );
		}

		if ( !empty( $r ) ) {

			$crmuser = $db -> getRow( "SELECT * FROM ".$sqlname."user WHERE iduser = '$r[iduser]' AND identity = '$identity'" );

			$user = [
				"id"       => (int)$r['id'],
				"iduser"   => (int)$r['iduser'],
				"botid"    => (int)$r['botid'],
				"user"     => current_user( $r['iduser'], "yes" ),
				"userid"   => $r['userid'],
				"username" => $r['username'],
				"datum"    => $r['datum'],
				"isunlock" => $crmuser['secrty'] == 'yes',
				"active"   => $r['active'] == 1,
				"identity" => $r['identity']
			];

		}
		else {
			$user['id'] = 0;
		}

		return $user;

	}

	public function UserSave($id, $params = []): string {

		$rootpath = dirname( __DIR__, 3 );

		require_once $rootpath."/inc/config.php";
		require_once $rootpath."/inc/dbconnector.php";
		require_once $rootpath."/inc/func.php";

		$identity = $GLOBALS['identity'];
		$sqlname  = $GLOBALS['sqlname'];
		$db       = $GLOBALS['db'];

		$data['botid']    = $params['botid'];
		$data['iduser']   = trim( $params['iduser'] );
		$data['userid']   = trim( $params['userid'] );
		$data['username'] = str_replace( "@", "", trim( $params['username'] ) );
		$data['datum']    = current_datumtime();
		$data['active']   = 1;

		if ( (int)$id == 0 ) {

			$data['identity'] = $identity;

			try {
				$db -> query( "INSERT INTO ".$sqlname."sendstatistic_users SET ?u", $data );
				$mes = 'Готово';
			}
			catch ( Exception $e ) {

				$mes = 'Ошибка'.$e -> getMessage().' в строке '.$e -> getCode();

			}

		}
		else {

			try {
				$db -> query( "UPDATE ".$sqlname."sendstatistic_users SET ?u WHERE id = '$id'", $data );
				$mes = 'Готово';
			}
			catch ( Exception $e ) {

				$mes = 'Ошибка'.$e -> getMessage().' в строке '.$e -> getCode();

			}

		}

		return $mes;

	}

	public function UserActiveChange($id): string {

		$rootpath = dirname( __DIR__, 3 );

		require_once $rootpath."/inc/config.php";
		require_once $rootpath."/inc/dbconnector.php";
		require_once $rootpath."/inc/func.php";

		$sqlname = $GLOBALS['sqlname'];
		$db      = $GLOBALS['db'];

		$user = self ::UserInfo( 0, $id );

		$status = ($user['active']) ? '0' : '1';

		$db -> query( "UPDATE ".$sqlname."sendstatistic_users SET ?u WHERE id = '$id'", ["active" => $status] );

		return 'Готово';

	}

	public function UserDelete($id): string {

		$rootpath = dirname( __DIR__, 3 );

		require_once $rootpath."/inc/config.php";
		require_once $rootpath."/inc/dbconnector.php";
		require_once $rootpath."/inc/func.php";

		$sqlname = $GLOBALS['sqlname'];
		$db      = $GLOBALS['db'];

		try {

			$db -> query( "DELETE FROM ".$sqlname."sendstatistic_users WHERE id = '$id'" );
			$mes = 'Готово';

		}
		catch ( Exception $e ) {

			$mes = 'Ошибка'.$e -> getMessage().' в строке '.$e -> getCode();

		}

		return $mes;

	}

}