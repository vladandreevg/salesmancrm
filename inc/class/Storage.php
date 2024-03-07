<?php
/* ============================ */
/*         SalesMan CRM         */
/* ============================ */
/* (C) 2016 Vladislav Andreev   */
/*       SalesMan Project       */
/*        www.isaler.ru         */
/*        ver. 2017.6           */
/* ============================ */

namespace Salesman;

use Exception;
use FtpClient\FtpClient;
use FtpClient\FtpException;
use SafeMySQL;
use Spreadsheet_Excel_Reader;

/**
 * Класс и функции для работы модуля Каталог-склад
 */

/**
 * Класс для управления складом
 *
 * Class Storage
 *
 * @package     Salesman
 * @author      Vladislav Andreev <v@salesman.pro>
 * @version     1.0 (06/09/2019)
 */
class Storage {

	/**
	 * Различные параметры, в основном из GLOBALS
	 * @var mixed
	 */
	public $identity, $iduser1, $sqlname, $db, $fpath, $opts, $skey, $ivc, $tmzone;

	/**
	 * Передача различных параметров
	 * @var array
	 */
	public $params = [];

	/**
	 * @var false|string
	 */
	private $rootpath;

	/**
	 * Akt constructor.
	 */
	public function __construct() {

		$rootpath = dirname( __DIR__, 2 );

		require_once $rootpath."/inc/config.php";
		require_once $rootpath."/inc/dbconnector.php";
		require_once $rootpath."/inc/func.php";

		$this -> rootpath = $rootpath;
		$this -> identity = $GLOBALS['identity'];
		$this -> iduser1  = $GLOBALS['iduser1'];
		$this -> sqlname  = $GLOBALS['sqlname'];
		$this -> fpath    = $GLOBALS['fpath'];
		$this -> opts     = $GLOBALS['opts'];
		$this -> tmzone   = $GLOBALS['tmzone'];
		$this -> db       = new SafeMySQL( $this -> opts );

	}

	/**
	 * Массив доп.полей
	 * @return array
	 */
	public static function getFields(): array {

		$rootpath = dirname( __DIR__, 2 );

		include_once $rootpath."/inc/config.php";
		include_once $rootpath."/inc/dbconnector.php";
		include_once $rootpath."/inc/func.php";

		global $db, $sqlname, $identity;

		$data = [];

		$result = $db -> query( "SELECT * FROM {$sqlname}modcatalog_fieldcat WHERE identity = '$identity' ORDER by ord" );
		while ( $xdata = $db -> fetch( $result ) ) {

			if( $xdata['tip'] == 'divider' ){
				continue;
			}

			$data[$xdata['id']] = [
				"field" => $xdata['pole'],
				"name"  => $xdata['name'],
				"tip"   => $xdata['tip'],
				"width" => $xdata['pwidth'],
			];

		}

		return $data;

	}

	/**
	 * Информация о позиции
	 * @param $id
	 * @param array $params (identity, iduser)
	 * @return array (prixe, sklad)
	 */
	public static function info($id, array $params = []): array {

		$rootpath = dirname( __DIR__, 2 );

		include_once $rootpath."/inc/config.php";
		include_once $rootpath."/inc/dbconnector.php";
		include_once $rootpath."/inc/func.php";

		$identity = ($params['identity'] > 0) ? $params['identity'] : $GLOBALS['identity'];
		$sqlname  = $GLOBALS['sqlname'];
		$db       = $GLOBALS['db'];
		$fpath    = $GLOBALS['fpath'];

		$data = [];

		$settings = self ::settings( $identity );

		$pozzi = ($settings['mcSkladPoz'] != "yes") ? " and status != 'out'" : "";

		$res = $db -> getRow( "select * from {$sqlname}price where n_id = '$id' and identity = '$identity'" );

		$data['price']['prid']         = $res["n_id"];
		$data['price']['artikul']      = $res["artikul"];
		$data['price']['title']        = clean( $res["title"] );
		$data['price']['description']  = $res["descr"];
		$data['price']['descr']        = $res["descr"];
		$data['price']['datum']        = $res["datum"];
		$data['price']['price_in']     = $res["price_in"];
		$data['price']['price_1']      = $res["price_1"];
		$data['price']['price_2']      = $res["price_2"];
		$data['price']['price_3']      = $res["price_3"];
		$data['price']['price_4']      = $res["price_4"];
		$data['price']['price_5']      = $res["price_5"];
		$data['price']['edizm']        = $res["edizm"];
		$data['price']['folder']       = $res["pr_cat"];
		$data['price']['categoryID']   = $res["pr_cat"];
		$data['price']['categoryName'] = $db -> getOne( "SELECT title FROM {$sqlname}price_cat WHERE idcategory = '".$res["pr_cat"]."' AND identity = '$identity'" );
		$data['price']['nds']          = $res["nds"];
		$data['price']['category'] = $data['price']['categoryName'];


		$res                      = $db -> getRow( "SELECT * FROM {$sqlname}modcatalog WHERE prid = '$id' AND identity = '$identity'" );
		$data['sklad']['content'] = $res["content"];
		$data['sklad']['status']  = $res["status"];
		$data['sklad']['file']    = $res["files"];
		$data['sklad']['id']      = $res["id"];

		$files = json_decode( $res["files"], true );
		foreach ( $files as $file ) {

			$data['sklad']['images'][] = [
				"name" => $file['name'],
				"file" => $rootpath."/files/".$fpath."modcatalog/".$file['file']
			];

		}

		$data['sklad']['countSklad'] = (float)$db -> getOne( "SELECT SUM(kol) as kol FROM {$sqlname}modcatalog_skladpoz WHERE status != 'out' AND prid = '$id' $pozzi AND identity = '$identity'" );
		$data['sklad']['countReserve'] = (float)$db -> getOne( "SELECT SUM(kol) as kol FROM {$sqlname}modcatalog_reserv WHERE prid = '$id' AND identity = '$identity'" );
		$data['sklad']['countZayavka'] = (float)$db -> getOne( "SELECT SUM(kol) as kol FROM {$sqlname}modcatalog_zayavkapoz WHERE prid = '$id' AND idz NOT IN (select idz from {$sqlname}modcatalog_zayavka where status IN (2, 3) AND identity = '$identity') AND identity = '$identity'" );

		//$prid = $res["n_id"];

		// доп.поля
		$fields = self ::getFields();
		foreach ( $fields as $fieldid => $field ) {

			if( $xdata['tip'] == 'divider' ){
				continue;
			}

			$value = $db -> getOne( "SELECT value FROM {$sqlname}modcatalog_field WHERE n_id = '$id' and pfid = '$fieldid' and identity = '$identity'" );

			if(!empty($value)) {
			
				$data['sklad']['fields'][$xdata['pole']] = [
					"field" => $xdata['pole'],
					"name"  => $xdata['name'],
					"value" => $value,
				];
			
			}

		}

		return $data;

	}

	/**
	 * Данные по складу по его ID
	 * @param $id
	 * @param string $field - поле для вывода
	 * @return array|string
	 */
	public static function getSklad($id, string $field = '') {

		$rootpath = dirname( __DIR__, 2 );

		require_once $rootpath."/inc/config.php";
		require_once $rootpath."/inc/dbconnector.php";
		include_once $rootpath."/inc/func.php";

		$identity = $GLOBALS['identity'];
		$sqlname  = $GLOBALS['sqlname'];
		$db       = $GLOBALS['db'];

		$data = [];

		$res               = $db -> getRow( "select * from {$sqlname}modcatalog_sklad where id = '$id' and identity = '$identity'" );
		$data['title']     = clean( $res["title"] );
		$data['mcid']      = $res["mcid"];
		$data['isDefault'] = $res["isDefault"];

		$res                  = $db -> getRow( "select * from {$sqlname}mycomps where id = '$data[mcid]' and identity = '$identity'" );
		$data['compUrName']   = $res['name_ur'];
		$data['compShotName'] = $res['name_shot'];
		$data['compUrAddr']   = $res['address_yur'];
		$data['compFacAddr']  = $res['address_post'];

		if ( !empty( $field ) )
			$data = $data[ $field ];

		return $data;

	}

	/**
	 * Вывод списка складов
	 * @param int $mcid
	 * @return array
	 */
	public static function getSkladList(int $mcid = 0): array {

		$rootpath = dirname( __DIR__, 2 );

		require_once $rootpath."/inc/config.php";
		require_once $rootpath."/inc/dbconnector.php";
		require_once $rootpath."/inc/func.php";

		$identity = $GLOBALS['identity'];
		$sqlname  = $GLOBALS['sqlname'];
		$db       = $GLOBALS['db'];

		$sort = ($mcid > 0) ? "mcid = '$mcid' and" : "";

		return $db -> getIndCol( "id", "SELECT title, id FROM {$sqlname}modcatalog_sklad WHERE $sort identity = '$identity'" );

	}

	/**
	 * Проверка сделки на комплектность
	 * @param int   $did
	 * @param array $params
	 * @return array
	 */
	public static function dealcomplete(int $did, array $params = []): array {

		$rootpath = dirname( __DIR__, 2 );

		require_once $rootpath."/inc/config.php";
		require_once $rootpath."/inc/dbconnector.php";
		require_once $rootpath."/inc/func.php";

		$identity = ($params['identity'] > 0) ? $params['identity'] : $GLOBALS['identity'];
		$sqlname  = $GLOBALS['sqlname'];
		$db       = $GLOBALS['db'];

		//только позиции из прайса
		$countSpeca = $db -> getOne( "SELECT SUM(kol) FROM {$sqlname}speca WHERE did = '$did' and prid > 0 and identity = '$identity'" );

		//количество позиций уже размещенных в заявках
		$countZayavka = (float)$db -> getOne( "SELECT SUM(kol) FROM {$sqlname}modcatalog_zayavkapoz WHERE idz IN (SELECT id FROM {$sqlname}modcatalog_zayavka WHERE did = '$did' and status != '2' and identity = '$identity') and identity = '$identity'" );

		//количество позиций уже размещенных в заявках
		$countOrder = (float)$db -> getOne( "SELECT SUM(kol) FROM {$sqlname}modcatalog_aktpoz WHERE ida IN (SELECT id FROM {$sqlname}modcatalog_akt WHERE did = '$did' and tip = 'outcome' and identity = '$identity') and identity = '$identity'" );

		//количество позиций уже размещенных в заявках
		$countOrderIn = (float)$db -> getOne( "SELECT SUM(kol) FROM {$sqlname}modcatalog_aktpoz WHERE ida IN (SELECT id FROM {$sqlname}modcatalog_akt WHERE did = '$did' and tip = 'income' and identity = '$identity') and identity = '$identity'" );

		//кол-во позиций в резерве под сделку
		$countReserve = (float)$db -> getOne( "SELECT SUM(kol) FROM {$sqlname}modcatalog_reserv WHERE did = '$did' and identity = '$identity'" );

		//число позиций, не в заявках и не в ордерах
		$count = $countSpeca - ($countOrder + $countZayavka + $countReserve);

		return [
			"count"   => $count,
			"speka"   => $countSpeca,
			"zayavka" => $countZayavka,
			"reserve" => $countReserve,
			"order"   => $countOrder,
			"orderin" => $countOrderIn
		];

	}

	/**
	 * Вывод объединенной спецификации с объединением одинаковых позиций в одну ( количество суммируется )
	 * @param        $did
	 * @param string $filter
	 * @return array
	 */
	public static function totalSpeka($did, string $filter = ''): array {

		$rootpath = dirname( __DIR__, 2 );

		require_once $rootpath."/inc/config.php";
		require_once $rootpath."/inc/dbconnector.php";

		$sqlname  = $GLOBALS['sqlname'];
		$identity = $GLOBALS['identity'];
		$db       = $GLOBALS['db'];

		$specaPre = [];

		$result = $db -> getAll( "
			SELECT
				{$sqlname}speca.prid,
				{$sqlname}speca.title,
				{$sqlname}speca.kol,
				{$sqlname}speca.price,
				{$sqlname}speca.price_in,
				{$sqlname}price.pr_cat as folder
			FROM {$sqlname}speca
				LEFT JOIN {$sqlname}price ON {$sqlname}speca.prid = {$sqlname}price.n_id
				LEFT JOIN {$sqlname}price_cat ON {$sqlname}price.pr_cat = {$sqlname}price_cat.idcategory
			WHERE
				{$sqlname}speca.did = '$did' and
				{$sqlname}speca.prid > 0
				$filter
				and {$sqlname}speca.identity = '$identity'
		" );
		//print $db -> lastQuery();
		foreach ( $result as $data ) {

			// если такой позиции нет то добавляем
			if ( empty( $specaPre[ $data['prid'] ] ) )
				$specaPre[ $data['prid'] ] = [
					"prid"     => $data['prid'],
					"title"    => $data['title'],
					"kol"      => $data['kol'],
					"price"    => $data['price'],
					"price_in" => $data['price_in'],
					"folder"   => $data['folder']
				];

			// если есть, то добавляем количество
			else
				$specaPre[ $data['prid'] ]['kol'] += $data['kol'];

		}

		return array_values( $specaPre );

	}

	/**
	 * Настройки модуля
	 * @param $identity
	 * @return mixed
	 */
	private static function settings($identity) {

		$rootpath = dirname( __DIR__, 2 );

		require_once $rootpath."/inc/config.php";
		require_once $rootpath."/inc/dbconnector.php";

		$sqlname = $GLOBALS['sqlname'];
		$db      = $GLOBALS['db'];

		$settings            = $db -> getOne( "SELECT settings FROM {$sqlname}modcatalog_set WHERE identity = '$identity'" );
		$settings            = json_decode( $settings, true );
		$settings['mcSklad'] = 'yes';

		return $settings;

	}

	/**
	 * Добавление/изменение позиции прайса и склада
	 * @param array $params
	 *          identity
	 *          iduser
	 *          n_id, artikul, title, descr, content, price_in, price_1, price_2, price_3, price_4, price_5,
	 *          edizm, nds, category
	 *          idz - id заявки на приобретение, если создается на её основе
	 *          ido - id предложения, если создается на её основе
	 * @return array
	 * @throws Exception
	 */
	public function edit(array $params = []): array {

		$rootpath = $this -> rootpath;
		$sqlname  = $this -> sqlname;
		$iduser1  = ($params['iduser'] > 0) ? $params['iduser'] : $this -> iduser1;
		$identity = ($params['identity'] > 0) ? $params['identity'] : $this -> identity;
		$db       = $this -> db;
		$fpath    = $this -> fpath;

		unset( $params['action'] );

		//настройки модуля
		//$settings = self::settings($identity);

		$params['prid']     = (int)$params['prid'] > 0 ? : (int)$params['n_id'];
		$params['content']  = htmlspecialchars( $params['content'] );
		$params['price_in'] = (float)pre_format( $params['price_in'] );
		$params['price_1']  = (float)pre_format( $params['price_1'] );
		$params['price_2']  = (float)pre_format( $params['price_2'] );
		$params['price_3']  = (float)pre_format( $params['price_3'] );
		$params['price_4']  = (float)pre_format( $params['price_4'] );
		$params['price_5']  = (float)pre_format( $params['price_5'] );
		$params['pr_cat']   = (int)$params['category'];
		$params['nds']      = pre_format( $params['nds'] );

		$errors = $fileuploaded = $oldparams = [];

		//для существующей позиции прайса
		if ( $params['prid'] > 0 ) {

			$res = $db -> getRow( "select * from {$sqlname}price where n_id='".$params['prid']."' and identity = '$identity'" );

			$oldparams['artikul']  = $res["artikul"];
			$oldparams['descr']    = $res["descr"];
			$oldparams['title']    = clean( $res["title"] );
			$oldparams['price_in'] = pre_format( (float)$res["price_in"] );
			$oldparams['price_1']  = pre_format( (float)$res["price_1"] );
			$oldparams['price_2']  = pre_format( (float)$res["price_2"] );
			$oldparams['price_3']  = pre_format( (float)$res["price_3"] );
			$oldparams['price_4']  = pre_format( (float)$res["price_4"] );
			$oldparams['price_5']  = pre_format( (float)$res["price_5"] );
			$oldparams['edizm']    = $res["edizm"];
			$oldparams['pr_cat']   = $res["pr_cat"];
			$oldparams['nds']      = pre_format( $res["nds"] );
			$oldparams['prid']     = $params['prid'];

			$db -> query( "UPDATE {$sqlname}price SET ?u WHERE n_id = '$params[prid]' and identity = '$identity'", [
				"artikul"  => $params['artikul'],
				"title"    => $params['title'],
				"descr"    => $params['descr'],
				"price_in" => (float)$params['price_in'],
				"price_1"  => (float)$params['price_1'],
				"price_2"  => (float)$params['price_2'],
				"price_3"  => (float)$params['price_3'],
				"price_4"  => (float)$params['price_4'],
				"price_5"  => (float)$params['price_5'],
				"edizm"    => $params['edizm'],
				"pr_cat"   => (int)$params['pr_cat'],
				"nds"      => $params['nds']
			] );

			//print $db -> lastQuery();

		}
		else {

			$db -> query( "INSERT INTO {$sqlname}price SET ?u", [
				"artikul"  => $params['artikul'],
				"title"    => $params['title'],
				"descr"    => $params['descr'],
				"price_in" => (float)$params['price_in'],
				"price_1"  => (float)$params['price_1'],
				"price_2"  => (float)$params['price_2'],
				"price_3"  => (float)$params['price_3'],
				"price_4"  => (float)$params['price_4'],
				"price_5"  => (float)$params['price_5'],
				"edizm"    => $params['edizm'],
				"pr_cat"   => (int)$params['pr_cat'],
				"nds"      => (float)$params['nds'],
				"identity" => $identity
			] );

			$params['prid'] = $db -> insertId();

		}

		//если записи для текущей позиции прайса нет таблице каталога, то добавим её
		$id = (int)$db -> getOne( "SELECT id FROM {$sqlname}modcatalog where prid='".$params['prid']."' and identity = '$identity'" );
		if ( $id < 1 ) {

			$db -> query( "INSERT INTO {$sqlname}modcatalog SET ?u", [
				"prid"     => $params['prid'],
				"datum"    => current_datum(),
				"identity" => $identity
			] );
			$id = $db -> insertId();

		}

		//---Загрузка файлов---///

		if ( !empty( $_FILES['file'] ) ) {

			$uploaddir = $rootpath.'/files/'.$fpath.'modcatalog/';

			createDir( $rootpath.'/files/'.$fpath.'modcatalog' );

			$file_ext_allow = [
				"png",
				"jpg",
				"jpeg",
				"gif"
			];
			$maxupload      = str_replace( [
				'M',
				'm'
			], '', @ini_get( 'upload_max_filesize' ) );

			for ( $i = 0, $iMax = count( $_FILES['file']['name'] ); $i < $iMax; $i++ ) {

				if ( filesize( $_FILES['file']['tmp_name'][ $i ] ) > 0 ) {

					$ftitle     = basename( $_FILES['file']['name'][ $i ] );
					$tim        = time() + $i;
					$fname      = $tim.".".getExtention( $ftitle );
					$uploadfile = $uploaddir.$fname;

					$cur_ext = texttosmall( getExtention( $ftitle ) );

					if ( in_array( $cur_ext, $file_ext_allow ) ) {

						if ( (filesize( $_FILES['file']['tmp_name'][ $i ] ) / 1000000) > $maxupload ) {

							$errors[] = 'Ошибка файла '.$ftitle.' - Превышает допустимые размеры!';

						}
						elseif ( move_uploaded_file( $_FILES['file']['tmp_name'][ $i ], $uploadfile ) ) {

								$fileuploaded[] = [
									"name" => $ftitle,
									"file" => $fname
								];

						}
						else $errors[] = 'Ошибка: '.$_FILES['file']['error'][ $i ];

					}
					else $errors[] = 'Ошибка: '.$ftitle.' - тип не поддерживается';

				}

			}

		}

		$fcount = count( $fileuploaded );

		//---заполнение каталога---//

		//массив файлов, которые уже прикреплены
		$files = json_decode( (string)$db -> getOne( "SELECT files FROM {$sqlname}modcatalog where id='$id' and identity = '$identity'" ), true );

		//---сохраним данные каталога

		$files = (!empty( $files )) ? array_merge( $files, $fileuploaded ) : $fileuploaded;
		$files = json_encode_cyr( $files );

		//если позиция создается из Предложения, то свяжем их
		if ( $params['ido'] > 0 ) {

			$db -> query( "update {$sqlname}modcatalog_offer set prid='".$params['prid']."' WHERE id='".$params['ido']."' and identity = '$identity'" );

		}

		$sparams = [];

		if ( (int)$params['idz'] > 0 ) {

			$sparams['idz'] = (int)$params['idz'];

			//для заявок на поиск - делаем заявку выполненной, т.к. она преобразована в позицию склада
			$db -> query( "UPDATE {$sqlname}modcatalog_zayavka SET ?u WHERE id = '".$params['idz']."' and identity = '$identity'", [
				"status"    => '2',
				"datum_end" => current_datumtime()
			] );

		}

		$sparams['content'] = $params['content'];
		$sparams['files']   = $files;
		$sparams['iduser']  = $iduser1;

		$db -> query( "UPDATE {$sqlname}modcatalog SET ?u WHERE id='".$id."' and identity = '$identity'", $sparams );

		//---сохраним доп.поля

		$fields  = $params['field'];
		$afields = [];

		foreach ( $fields as $input => $value ) {

			//подготовим значение
			$afields[ $input ] = (is_array( $value )) ? implode( ";", $value ) : $value;

		}

		$result = $db -> query( "SELECT * FROM {$sqlname}modcatalog_fieldcat WHERE tip != 'divider' and identity = '$identity'" );
		while ($data = $db -> fetch( $result )) {

			//Проверим существование текущего поля в базе
			$ef = (int)$db -> getOne( "SELECT id FROM {$sqlname}modcatalog_field WHERE n_id = '".$params['prid']."' and pfid = '$data[id]' and identity = '$identity'" );

			//если значение заполнено
			if ( $afields[ $data['pole'] ] != '' ) {

				if ( $ef == 0 )
					$db -> query( "INSERT INTO {$sqlname}modcatalog_field SET ?u", [
						"pfid"     => $data['id'],
						"n_id"     => $params['prid'],
						"value"    => $afields[ $data['pole'] ],
						"identity" => $identity
					] );

				else $db -> query( "UPDATE {$sqlname}modcatalog_field SET ?u WHERE id = '".$ef."'", ["value" => $afields[ $data['pole'] ]] );

			}
			elseif ( $ef > 0 )
				$db -> query( "UPDATE {$sqlname}modcatalog_field SET ?u WHERE id = '".$ef."'", ["value" => ""] );

		}

		//загрузим по ftp
		$ftp['result'] = 'ok';

		if ( $fcount > 0 ) {

			$ftp = $this -> FtpUpload( $params['prid'], json_decode( $files, true ) );
			if ( $ftp['result'] != 'ok' )
				$errors[] = implode( "<br>", $ftp['error'] );

		}

		//if($settings['mcAutoStatus'] == 'yes') mcCheckStatus($params['n_id']);

		$log = $this -> logger( 'catalog', $identity, $iduser1, $params, $oldparams );

		if ( $log != '' )
			$errors[] = $log;

		return [
			'result' => "Сделано",
			"error"  => $errors
		];

	}

	/**
	 * Удаление позиции каталога/прайса
	 * @param array $params
	 * @return array
	 */
	public function delete(array $params = []): array {

		$rootpath = $this -> rootpath;
		$sqlname  = $this -> sqlname;
		$identity = ($params['identity'] > 0) ? $params['identity'] : $this -> identity;
		$db       = $this -> db;
		$fpath    = $this -> fpath;
		$uploaddir = $rootpath.'/files/'.$fpath.'modcatalog/';

		unset( $params['action'] );

		$prid = $params['n_id'];

		//позиция прайса
		$db -> query( "delete from {$sqlname}price where n_id = '$prid' and identity = '$identity'" );

		//файлы
		$files = json_decode( $db -> getOne( "SELECT files FROM {$sqlname}modcatalog where prid='".$params['prid']."' and identity = '$identity'" ), true );

		foreach ( $files as $file ) {

			unlink( $uploaddir.$file['file'] );

		}

		//всё остальное
		$db -> query( "delete from {$sqlname}modcatalog where prid = '$prid' and identity = '$identity'" );
		$db -> query( "delete from {$sqlname}modcatalog_skladpoz where prid = '$prid' and identity = '$identity'" );
		$db -> query( "delete from {$sqlname}modcatalog_aktpoz where prid = '$prid' and identity = '$identity'" );
		$db -> query( "delete from {$sqlname}modcatalog_reserv where prid = '$prid' and identity = '$identity'" );
		$db -> query( "delete from {$sqlname}modcatalog_zayavkapoz where prid = '$prid' and identity = '$identity'" );

		return ['result' => "Сделано"];

	}

	/**
	 * Удаление позиции со склада
	 * @param array $params
	 * - id записи
	 * @return array
	 */
	public function deletepoz(array $params = []): array {

		$sqlname  = $this -> sqlname;
		$identity = ($params['identity'] > 0) ? $params['identity'] : $this -> identity;
		$db       = $this -> db;

		unset( $params['action'] );

		$id = $params['id'];

		//позиция прайса
		$db -> query( "delete from {$sqlname}modcatalog_skladpoz where id = '$id' and identity = '$identity'" );

		return ['result' => "Сделано"];

	}

	/**
	 * Функция изменения розничной цены на позицию
	 * @param array $params
	 *          identity
	 *          iduser
	 *          n_id, price_1
	 * @return array
	 */
	public function editprice(array $params = []): array {

		$sqlname  = $this -> sqlname;
		$iduser1  = ($params['iduser'] > 0) ? $params['iduser'] : $this -> iduser1;
		$identity = ($params['identity'] > 0) ? $params['identity'] : $this -> identity;
		$db       = $this -> db;

		unset( $params['action'] );

		$params['prid']     = ($params['prid']) ? : $params['n_id'];
		$params['price_1']  = pre_format( $params['price_1'] );
		$params['price_in'] = pre_format( $params['price_in'] );

		$errors = $oldparams = [];

		//для существующей позиции прайса
		if ( $params['prid'] > 0 ) {

			$res = $db -> getRow( "select * from {$sqlname}price where n_id = '".$params['prid']."' and identity = '$identity'" );

			$oldparams['price_1']  = pre_format( $res["price_1"] );
			$oldparams['prid']     = $params['prid'];
			$oldparams['price_in'] = ($params['price_in'] > 0) ? $params["price_in"] : $res['price_in'];

			$db -> query( "UPDATE {$sqlname}price SET ?u WHERE n_id = '$params[prid]' and identity = '$identity'", [
				"price_1"  => ($params['price_1'] > 0) ? $params['price_1'] : $res["price_1"],
				"price_in" => $params['price_in']
			] );

		}

		$this -> logger( 'catalog', $identity, $iduser1, $params, $oldparams );

		return [
			'result' => "Сделано",
			"error"  => $errors
		];

	}

	/**
	 * Редактирование позиции на складе
	 * @param array $params
	 * @return array
	 */
	public function editone(array $params = []): array {

		$sqlname  = $this -> sqlname;
		$identity = ($params['identity'] > 0) ? $params['identity'] : $this -> identity;
		$db       = $this -> db;

		unset( $params['action'], $params['identity'], $params['iduser1'] );

		//для существующей позиции прайса
		if ( $params['id'] > 0 ) {

			$id = $params['id'];
			unset( $params['id'] );

			$param = $db -> filterArray( $params, [
				"prid",
				"serial",
				"summa",
				"date_in",
				"date_out",
				"date_create",
				"date_period",
				"did",
				"kol"
			] );
			$db -> query( "UPDATE {$sqlname}modcatalog_skladpoz SET ?u WHERE id = '".$id."' and identity = '$identity'", $param );

		}

		return ['result' => "Сделано"];

	}

	/**
	 * Работа с ордерами
	 * @param array $params
	 *          id - id записи акта
	 *          identity
	 *          iduser
	 *
	 *          массив серийных номеров
	 *          serial = [
	 *                  prid = [serial, date_create, date_period]
	 *              ]
	 * @return array
	 */
	public function editakt(array $params = []): array {

		$rootpath = $this -> rootpath;
		$sqlname  = $this -> sqlname;
		$iduser1  = ($params['iduser'] > 0) ? $params['iduser'] : $this -> iduser1;
		$identity = ($params['identity'] > 0) ? $params['identity'] : $this -> identity;
		$db       = $this -> db;
		$fpath    = $this -> fpath;

		$settings = self ::settings( $identity );

		//id ордера
		$id = (int)$params['id'];

		$spekas = $params['speka'];

		$order['clid'] = ($params['clid'] > 0) ? (int)$params['clid'] : 0;
		$order['did']  = ($params['did'] > 0) ? (int)$params['did'] : 0;

		//id поставщика
		$order['posid'] = ($params['posid'] > 0) ? (int)$params['posid'] : 0;
		$order['man1']  = $params['man1'] ?? 'na';
		$order['man2']  = $params['man2'] ?? 'na';
		$order['isdo']  = ($params['isdo'] == 'yes') ? $params['isdo'] : 'no';
		$order['sklad'] = $params['sklad'];

		//тип ордера приходный (income) / расходный (outcome)
		$order['tip'] = $params['tip'];

		//id заявки для приходных ордеров
		$order['idz'] = (int)$params['idz'];

		if ( $order['idz'] < 1 )
			$order['did'] = (int)$params['adid'];

		//переданные серийники для поштучного учета
		//$serial = $params['serial'];

		$order['datum'] = current_datumtime();

		//стоит ли резервировать, если ордер не будет проведен
		//todo: ОТЛОЖЕНО пока не будет запроса от клиентво
		$doReserv = 'no';

		$order['cFactura'] = $params['cFactura'] != '' ? $params['cFactura'] : NULL;
		$order['cDate']    = ($params['cDate'] != '') ? $params['cDate'] : NULL;

		$errors = $speka = [];
		$file   = '';

		//Если список загружается из файла
		if ( $_FILES['file']['name'] != '' ) {

			//require_once $rootpath.'/opensource/excel_reader/excel_reader2.php';

			//загружаем из файла
			$ext = texttosmall( getExtention( $_FILES['file']['name'] ) );
			if ( $ext != 'xls' ) {

				$errors[] = 'Ошибка при загрузке - Недопустимый формат файла. Допускаются только файлы в формате XLS';

			}
			else {

				$file = $rootpath.'/files/'.$fpath.translit( str_replace( " ", "", basename( $_FILES['file']['name'] ) ) );

				//Сначала загрузим файл на сервер
				if ( !move_uploaded_file( $_FILES['file']['tmp_name'], $file ) ) {

					$errors[] = 'Ошибка при загрузке файла - '.$_FILES['file']['error'];

				}

			}

			if ( file_exists( $file ) ) {

				$datas = new Spreadsheet_Excel_Reader();
				$datas -> setOutputEncoding( 'UTF-8' );
				$datas -> read( $file );
				$data1 = $datas -> dumptoarray();//получили двумерный массив с данными

				unset( $data1[0] );

				foreach ( $data1 as $d ) {

					$prid = $db -> getOne( "SELECT n_id FROM {$sqlname}price WHERE artikul = '".$d[4]."' and identity = '$identity'" );

					if ( $prid > 0 && $d[1] != '' )
						$speka[] = [
							"prid"     => $prid,
							"title"    => $d[1],
							"kol"      => $d[2],
							"price_in" => pre_format( $d[3] )
						];

				}

			}

		}

		//Если передается массив
		if ( !empty( $spekas ) ) {

			foreach ( $spekas as $value ) {

				//если позиция есть в прайсе
				if ( $value['prid'] > 0 && $value['speca_title'] != '' )
					$speka[] = [
						"idp"      => $value['idp'],
						"prid"     => $value['prid'],
						"title"    => $value['speca_title'],
						"kol"      => pre_format( $value['speca_kol'] ),
						"price_in" => pre_format( $value['speca_price'] ),
						"serial"   => $value['serial']
					];

			}

		}

		$good = $del = $upd = 0;
		$err  = $mes = $newpoz = [];
		$msg  = '';

		if ( $order['idz'] > 0 ) {

			$zayavka        = $db -> getRow( "SELECT conid, did FROM {$sqlname}modcatalog_zayavka WHERE id = '$order[idz]' and identity = '$identity'" );
			$order['posid'] = (int)$zayavka['conid'];
			$order['did']   = (int)$zayavka['did'];

		}

		//для нового ордера
		if ( $id < 1 ) {

			try {

				$order['identity'] = $identity;

				$db -> query( "INSERT INTO {$sqlname}modcatalog_akt SET ?u", $order );
				$id = $db -> insertId();

				$mes[] = "Ордер добавлен";

				foreach ( $speka as $value ) {

					try {

						$value['ida']      = $id;
						$value['identity'] = $identity;

						//серийные номера
						$serials = $value['serial'];

						unset( $value['idp'], $value['title'], $value['serial'] );

						$db -> query( "INSERT INTO {$sqlname}modcatalog_aktpoz SET ?u", $value );

						//пройдем серийные номера и закрепим их за сделокой/ордером
						foreach ( $serials as $seria ) {

							$db -> query( "UPDATE {$sqlname}modcatalog_skladpoz SET ?u WHERE id = '$seria'", [
								"did"       => $order['did'],
								"order_out" => $id
							] );

						}

						$good++;

					}
					catch ( Exception $e ) {

						$err[] = 'Ошибка'.$e -> getMessage().' в строке '.$e -> getCode();

					}

				}

			}
			catch ( Exception $e ) {

				$err[] = 'Ошибка'.$e -> getMessage().' в строке '.$e -> getCode();

			}

		}

		//обновление ордера
		else {

			try {

				$norder = $db -> filterArray( $order, [
					"did",
					"man1",
					"man2",
					"clid",
					"posid",
					"sklad",
					"idz",
					"cFactura",
					"cDate"
				] );

				$db -> query( "UPDATE {$sqlname}modcatalog_akt SET ?u WHERE id = '$id'", $norder );

				$mes[] = "Ордер обновлен";

			}
			catch ( Exception $e ) {

				$err[] = 'Ошибка'.$e -> getMessage().' в строке '.$e -> getCode();

			}

			foreach ( $speka as $value ) {

				//серийные номера
				$serials = $value['serial'];
				unset( $value['serial'] );

				//обновляем позиции
				if ( (int)$value['idp'] > 0 ) {//если позиция уже была обновляем

					$idp = (int)$value['idp'];

					unset( $value['idp'], $value['title'] );

					try {

						$db -> query( "UPDATE {$sqlname}modcatalog_aktpoz SET ?u WHERE id = '$idp'", $value );
						$upd++;

					}
					catch ( Exception $e ) {

						$err[] = 'Ошибка'.$e -> getMessage().' в строке '.$e -> getCode();

					}

					$newpoz[] = $idp;


				}
				//добавляем новые
				elseif ( (int)$value['idp'] == 0 ) {

					try {

						$value['ida']      = $id;
						$value['identity'] = $identity;

						unset( $value['idp'], $value['title'] );

						$db -> query( "INSERT INTO {$sqlname}modcatalog_aktpoz SET ?u", $value );
						$good++;

						$newpoz[] = $db -> insertId();

					}
					catch ( Exception $e ) {

						$err[] = 'Ошибка'.$e -> getMessage().' в строке '.$e -> getCode();

					}

				}

				//пройдем серийные номера и закрепим их за сделокой/ордером
				foreach ( $serials as $seria ) {

					$db -> query( "UPDATE {$sqlname}modcatalog_skladpoz SET ?u WHERE id = '$seria'", [
						"did"         => $order['did'],
						"idorder_out" => $id
					] );

					//а также поставим в резерв
					///???

				}

				//открепим ранее выбранные серийники, которых сейчас нет
				if ( !empty( $serials ) && $order['tip'] == 'income' )
					$db -> query( "UPDATE {$sqlname}modcatalog_skladpoz SET idorder_in  = '0', did = '0' WHERE idorder_in = '$id' and prid = '$value[prid]' and did = '$order[did]' and id NOT IN (".implode( ",", $serials ).") and identity = '$identity'" );

				if ( !empty( $serials ) && $order['tip'] == 'outcome' )
					$db -> query( "UPDATE {$sqlname}modcatalog_skladpoz SET idorder_out  = '0', did = '0' WHERE idorder_out = '$id' and prid = '$value[prid]' and did = '$order[did]' and id NOT IN (".implode( ",", $serials ).") and identity = '$identity'" );

			}

			//составим массив имеющихся позиций
			$pozitions = $db -> getCol( "SELECT id FROM {$sqlname}modcatalog_aktpoz WHERE ida = '$id' and identity = '$identity'" );

			//убираем удаленные позиции
			foreach ( $pozitions as $value ) {

				if ( !in_array( $value, $newpoz ) ) {

					try {

						$db -> query( "delete from {$sqlname}modcatalog_aktpoz where id = '$value' and identity = '$identity'" );
						$del++;

					}
					catch ( Exception $e ) {

						$err[] = 'Ошибка'.$e -> getMessage().' в строке '.$e -> getCode();

					}

				}

			}

		}

		//действия, если ордер проведен
		if ( $order['isdo'] == 'yes' ) {

			if ( $order['tip'] == 'income' ) {

				//здесь проходим позиции акта в сочетании с каталогом и добавляем количество из позиции
				$result = $db -> getAll( "SELECT * FROM {$sqlname}modcatalog_aktpoz where ida = '$id' and identity = '$identity'" );
				foreach ( $result as $data ) {

					$oldparam = $newparam = [];

					$oldparam['prid'] = (int)$data['prid'];

					//количество позиций на складе
					$oldparam['kol'] = $db -> getOne( "SELECT SUM(kol) as kol FROM {$sqlname}modcatalog_skladpoz where sklad = '$order[sklad]' and prid = '".$data['prid']."' and identity = '$identity'" );

					$newparam['prid'] = (int)$data['prid'];
					$newparam['kol']  = $oldparam['kol'] + $data['kol'];

					//добавим позиции на склад
					//поштучный учет по складам
					if ( $settings['mcSkladPoz'] == 'yes' ) {

						for ( $i = 0; $i < $data['kol']; $i++ ) {

							$list = [
								"prid"       => $data['prid'],
								"sklad"      => $order['sklad'],
								"status"     => 'in',
								"date_in"    => current_datum(),
								"kol"        => 1,
								"did"        => $order['did'],
								"idorder_in" => $id,
								"identity"   => $identity
							];

							//если передаются серийники
							if ( isset( $params['serial'] ) ) {

								foreach ( $params['serial']['prid'] as $pos ) {

									$list['serial']      = $pos['serial'];
									$list['date_create'] = $pos['date_create'];
									$list['date_period'] = $pos['date_period'];

								}

							}

							$db -> query( "INSERT INTO {$sqlname}modcatalog_skladpoz SET ?u", $list );

						}

					}
					//учет по позициям по складам
					else {

						//выясним количество позиций товара на конкретном складе
						$cSklad = $db -> getRow( "SELECT COUNT(*) as count, SUM(kol) as kol FROM {$sqlname}modcatalog_skladpoz where prid = '$data[prid]' and sklad = '$order[sklad]' and identity = '$identity'" );

						//print $db -> lastQuery() . "\n";
						//print $cSklad['count'] ."\n";
						//print $cSklad['kol'] ."\n";

						//если позиция уже есть на складе - обновляем её с добавлением количества
						if ( (int)$cSklad['count'] > 0 ) {

							$newkol = $cSklad['kol'] + $data['kol'];

							$db -> query( "UPDATE {$sqlname}modcatalog_skladpoz SET ?u WHERE prid = '".$data['prid']."' and sklad = '$order[sklad]' and identity = '$identity'", [
								"kol"     => $newkol,
								"date_in" => current_datum()
							] );

						}
						//если такой позиции нет - добавим
						else {

							$db -> query( "INSERT INTO {$sqlname}modcatalog_skladpoz SET ?u", [
								"prid"     => $data['prid'],
								"sklad"    => $order['sklad'],
								"status"   => 'in',
								"date_in"  => current_datum(),
								"kol"      => $data['kol'],
								"identity" => $identity
							] );

						}

					}

					//автоматически зарезервируем позиции по сделке
					if ( $settings['mcAutoWork'] == 'yes' && $order['did'] > 0 )
						$msg = $this -> SyncPoz( "no", [
							"prid" => $data['prid'],
							"ida"  => $id,
							"idz"  => $order['idz'],
							"did"  => $order['did'],
							"tip"  => $order['tip']
						] );

					//если настроено, то обновляем прайс
					if ( $settings['mcAutoPricein'] == "yes" ) {

						$priceParam = [
							"prid"     => $data['prid'],
							"price_in" => $data['price_in']
						];
						$this -> editprice( $priceParam );

					}

					$this -> logger( "catalog", $identity, $iduser1, $newparam, $oldparam );

				}

				$mes[] = 'Ордер проведен';

				//отметим заявку выполненной, если все позиции отгружены
				//не требуется, т.к. создание ордера доступно только для выполненных заявок

			}
			elseif ( $order['tip'] == 'outcome' ) {

				//проходим позиции акта и проводим его
				$result = $db -> getAll( "SELECT * FROM {$sqlname}modcatalog_aktpoz where ida = '$id' and identity = '$identity'" );
				foreach ( $result as $data ) {

					//если поштучный учет не включен
					//что делаем?
					// Если по этой сделке по этой позиции есть резерв, то его надо удалить + списать позиции со склада
					if ( $settings['mcSkladPoz'] != "yes" ) {

						//проверяем количество позиций на складе
						$kolSklad = $db -> getOne( "SELECT SUM(kol) as kol FROM {$sqlname}modcatalog_skladpoz where sklad = '$order[sklad]' and prid = '".$data['prid']."' and identity = '$identity'" );

						//проверяем наличие позиции на резерве под сделку
						$kolReserve = $db -> getOne( "SELECT SUM(kol) as kol FROM {$sqlname}modcatalog_reserv where sklad = '$order[sklad]' and did = '$order[did]' and prid = '".$data['prid']."' and identity = '$identity'" );

						//проверяем наличие позиции на резерве под другие сделки
						//$kolReserveOther = $db -> getOne( "SELECT SUM(kol) as kol FROM {$sqlname}modcatalog_reserv where sklad = '$order[sklad]' and did != '$order[did]' and prid = '".$data['prid']."' and identity = '$identity'" );

						//если позиции есть в резерве под эту сделку, то списываем их
						if ( $kolReserve > 0 ) {

							//на сколько резерв удовлетворяет количеству в акте
							$kolDelta = $kolReserve - $data['kol'];

							//позиция полностью закрыта ордером
							//значит резерв убираем, а со склада списываем указанное количество
							if ( $kolDelta == 0 ) {

								$db -> query( "DELETE FROM {$sqlname}modcatalog_reserv WHERE sklad = '$order[sklad]' and did = '$order[did]' and prid = '".$data['prid']."' and identity = '$identity'" );

							}
							//если весь резерв не выбран (например, если ордер частичный), то обновим остаток
							elseif ( $kolDelta > 0 ) {

								$db -> query( "UPDATE {$sqlname}modcatalog_reserv SET ?u WHERE prid = '".$data['prid']."' and sklad = '$order[sklad]' and identity = '$identity'", ["kol" => $kolDelta] );

							}
							//kolDelta не может быть меньше нуля - проверяем при создании акта

						}

						$kolOstatok = $kolSklad - $data['kol'];

						//обновляем количество товара на складе
						$db -> query( "UPDATE {$sqlname}modcatalog_skladpoz SET ?u WHERE prid = '".$data['prid']."' and sklad = '$order[sklad]' and identity = '$identity'", [
							"kol"      => $kolOstatok,
							"date_out" => current_datum()
						] );

						//если настроено, то обновляем прайс
						/*if ($settings['mcAutoPricein'] == "yes") {

							$priceParam = array(
								"prid"     => $data['prid'],
								"price_in" => $data['price_in']
							);
							$this -> editprice($priceParam);

						}*/

					}
					else {

						//проверяем количество позиций на складе
						$kolSklad = $db -> getOne( "SELECT SUM(kol) as kol FROM {$sqlname}modcatalog_skladpoz where sklad = '$order[sklad]' and prid = '".$data['prid']."' and status = 'in' and identity = '$identity'" );

						//проверяем наличие позиции на резерве под сделку
						$kolReserve = $db -> getOne( "SELECT SUM(kol) as kol FROM {$sqlname}modcatalog_reserv where sklad = '$order[sklad]' and did = '$order[did]' and prid = '".$data['prid']."' and identity = '$identity'" );

						//проверяем наличие позиции на резерве под другие сделки
						$kolReserveOther = $db -> getOne( "SELECT SUM(kol) as kol FROM {$sqlname}modcatalog_reserv where sklad = '$order[sklad]' and did != '$order[did]' and prid = '".$data['prid']."' and identity = '$identity'" );

						//здесь обрабатываем перечень серийников с привязкой к ордеру

						$kolSkladFree = $kolSklad - $kolReserveOther;

						//количество на складе под сделку
						$kolSkladByDeal = $db -> getOne( "SELECT SUM(kol) FROM {$sqlname}modcatalog_skladpoz where sklad = '$order[sklad]' and prid = '".$data['prid']."' and did = '$order[did]' and idorder_out = '$id' and identity = '$identity'" );

						//стоимость позиции по спецификации
						$summa = $db -> getOne( "SELECT price FROM {$sqlname}speca WHERE prid = '".$data['prid']."' and did = '$order[did]' and identity = '$identity'" );

						// если делаем без спецификации, то смотрим в прайсе
						if($summa == ''){

							$summa = $db -> getOne( "SELECT price_1 FROM {$sqlname}price WHERE n_id = '".$data['prid']."' and identity = '$identity'" );

						}

						$rezDiff = $kolReserve - $data['kol'];

						//если серийники не передаются
						if ( !empty( $params['serial'][ $data['prid'] ] ) ) {

							//если под сделку есть позиции и они соответствуют ордеру, то спишем их
							if ( $kolSkladByDeal > 0 && $kolSkladByDeal == $data['kol'] ) {

								$i = 0;

								$rs = $db -> getAll( "SELECT * FROM {$sqlname}modcatalog_skladpoz where sklad = '$order[sklad]' and prid = '".$data['prid']."' and did = '$order[did]' and status = 'in' and identity = '$identity'" );
								while ($i < $data['kol']) {

									$db -> query( "UPDATE {$sqlname}modcatalog_skladpoz SET ?u WHERE id = '".$rs[ $i ]['id']."' and identity = '$identity'", [
										"status"   => 'out',
										"date_out" => current_datum(),
										"summa"    => pre_format($summa)
									] );

									$i++;

								}

							}
							//если под сделку нет позиции, но на складе есть свободные, то привязываем их к ордеру и сделке
							if ( $kolSkladFree >= $data['kol'] && $kolSkladByDeal == 0 ) {

								$i  = 0;
								$rs = $db -> getAll( "SELECT * FROM {$sqlname}modcatalog_skladpoz where sklad = '$order[sklad]' and prid = '$data[prid]' and did = '0' and status = 'in' and identity = '$identity'" );

								while ($i < $data['kol']) {

									$db -> query( "UPDATE {$sqlname}modcatalog_skladpoz SET ?u WHERE id = '".$rs[$i]['id']."' and identity = '$identity'", [
										"status"      => 'out',
										"did"         => $order['did'],
										"idorder_out" => $id,
										"date_out"    => current_datum(),
										"summa"       => pre_format($summa)
									] );

									$i++;

								}

							}

						}

						//если серийники передаются и количество позиций соответствует заданному
						elseif ( count( $params['serial'][ $data['prid'] ] ) >= $data['kol'] ) {

							$i = 0;
							foreach ( $params['serial'][ $data['prid'] ] as $value ) {

								//количество должно быть не больше указанного в акте
								if ( $i < $data['kol'] ) {

									$db -> query( "UPDATE {$sqlname}modcatalog_skladpoz SET ?u WHERE id = '".$value."' and identity = '$identity'", [
										"status"      => 'out',
										"did"         => $order['did'],
										"idorder_out" => $id,
										"date_out"    => current_datum(),
										"summa"       => pre_format($summa)
									] );

								}

								$i++;

							}

						}

						//если серийники передаются и количество позиций меньше заданного
						else {

							foreach ( $params['serial'][ $data['prid'] ] as $value ) {

								$db -> query( "UPDATE {$sqlname}modcatalog_skladpoz SET ?u WHERE id = '".$value."' and identity = '$identity'", [
									"status"      => 'out',
									"did"         => $order['did'],
									"idorder_out" => $id,
									"date_out"    => current_datum(),
									"summa"       => pre_format($summa)
								] );

							}

							//меняем количество в ордере
							$db -> query( "UPDATE {$sqlname}modcatalog_aktpoz SET ?u WHERE id = '".$id."' and identity = '$identity'", ["kol" => count( $params['serial'][ $data['prid'] ] )] );

						}

						//удаляем резерв

						//если количество в резерве равно или меньше списываемого, то удаляем резерв
						if ( $kolReserve > 0 && $rezDiff <= 0 ) {

							$db -> query( "DELETE FROM {$sqlname}modcatalog_reserv WHERE sklad = '$order[sklad]' and did = '$order[did]' and prid = '$data[prid]' and identity = '$identity'" );

						}
						else {

							$db -> query( "UPDATE {$sqlname}modcatalog_reserv SET ?u WHERE prid = '$data[prid]' and sklad = '$order[sklad]' and identity = '$identity'", ["kol" => $rezDiff] );

						}

						//если настроено, то обновляем прайс
						/*if ($settings['mcAutoPricein'] == "yes") {

							$priceParam = array(
								"prid"     => $data['prid'],
								"price_in" => $data['price_in']
							);
							$this -> editprice($priceParam);

						}*/

					}

				}

				$mes[] = 'Ордер проведен';

			}

			//найдем количество всех актов такого типа и присвоим ордеру акт
			$number = $db -> getOne( "SELECT max(number + 0) FROM {$sqlname}modcatalog_akt where tip = '$order[tip]' and isdo = 'yes' and identity = '$identity'" );

			$number++;

			$db -> query( "UPDATE {$sqlname}modcatalog_akt SET ?u WHERE id = '".$id."' and identity = '$identity'", [
				"number" => $number,
				"isdo"   => 'yes',
				"datum"  => current_datumtime()
			] );

		}

		//todo: на будущее - если не проведен, то может резервировать под сделку??
		if ( $order['tip'] == 'outcome' && $doReserv == 'yes' ) {

			//проходим позиции акта и проводим его
			$result = $db -> getAll( "SELECT * FROM {$sqlname}modcatalog_aktpoz where ida = '$id' and identity = '$identity'" );
			foreach ( $result as $data ) {

				//проверяем количество позиций на складе
				$kolSklad = $db -> getOne( "SELECT kol FROM {$sqlname}modcatalog_skladpoz where sklad = '$order[sklad]' and prid = '".$data['prid']."' and identity = '$identity'" );

				//проверяем наличие позиции на резерве под сделку
				$kolReserve = $db -> getOne( "SELECT kol FROM {$sqlname}modcatalog_reserv where sklad = '$order[sklad]' and did = '$order[did]' and prid = '".$data['prid']."' and identity = '$identity'" );

				//проверяем наличие позиции на резерве под другие сделки
				$kolReserveOther = $db -> getOne( "SELECT kol FROM {$sqlname}modcatalog_reserv where sklad = '$order[sklad]' and did != '$order[did]' and prid = '".$data['prid']."' and identity = '$identity'" );


				//если позиции есть в резерве под эту сделку, то списываем их
				if ( $kolReserve == 0 && $kolSklad > 0 && $kolSklad > $data['kol'] ) {

					//свободные позиции на складе
					$kolSkladFree = $kolSklad - $kolReserveOther;

					//на сколько склад удовлетворяет количеству в акте
					$kolDelta = $kolSkladFree - $data['kol'];

					//позиция полностью будет зарезервирована
					if ( $kolDelta >= 0 ) {

						$db -> query( "INSERT INTO {$sqlname}modcatalog_reserv SET u?", [
							'prid'     => (int)$data['prid'],
							'sklad'    => (int)$order['sklad'],
							'status'   => 'reserved',
							'datum'    => current_datumtime(),
							'kol'      => (float)$data['kol'],
							'did'      => (int)$data['did'],
							'ida'      => (int)$data['ida'],
							'idz'      => (int)$data['idz'],
							'identity' => $identity
						] );

					}

				}

			}

			$mes[] = 'Товар зарезервирован под сделку';

		}

		$result = ( empty( $err ) ) ? "Сделано. Ошибок нет" : "Сделано. Есть ошибки";

		$counts = [];

		if ( $good > 0 )
			$counts['add'] = $good;

		if ( $upd > 0 )
			$counts['update'] = $upd;

		if ( $del > 0 )
			$counts['delete'] = $del;

		//передаем параметр для поштучного учета и вызова формы ввода серийников для каждой позиции
		$doit = ($settings['mcSkladPoz'] == 'yes' && $order['tip'] == 'income') ? $order['isdo'] : '';

		return [
			"id"        => $id,
			"result"    => $result,
			"counts"    => $counts,
			"message"   => $mes,
			"msg"       => $msg,
			"doit"      => $doit,
			"error"     => $err,
			"uploaderr" => $errors
		];

	}

	/**
	 * Перемещение м/у складами
	 * @param array $params
	 * @return array
	 */
	public function move(array $params = []): array {

		$sqlname  = $this -> sqlname;
		$iduser1  = ($params['iduser'] > 0) ? $params['iduser'] : $this -> iduser1;
		$identity = ($params['identity'] > 0) ? $params['identity'] : $this -> identity;
		$db       = $this -> db;

		$settings = self ::settings( $identity );

		$move['skladfrom'] = $params['skladfrom'];
		$move['skladto']   = $params['skladto'];
		$move['iduser']    = $iduser1;
		$move['identity']  = $identity;

		$prid = $params['prid'];
		$idp  = $params['idp'];
		$kol  = $params['kol'];

		//print_r($params);
		//exit();

		//группа перемещений (для лога)
		$db -> query( "INSERT INTO {$sqlname}modcatalog_skladmove SET ?u", $move );
		$id = $db -> insertId();

		foreach ( $idp as $i => $value ) {

			//для поштучного учета всегда 1
			if ( $kol[ $i ] == "" )
				$kol[ $i ] = 1;

			//перемещаемые позиции (для лога)
			$db -> query( "insert into {$sqlname}modcatalog_skladmovepoz (id,idm,idp,prid,kol,identity) values (null, '$id', '$value', '$prid[$i]', '$kol[$i]', '$identity')" );

			//если поштучный учет, то просто меняем склад
			if ( $settings['mcSkladPoz'] == "yes" )
				$db -> query( "UPDATE {$sqlname}modcatalog_skladpoz SET sklad = '$move[skladto]' WHERE id = '".$value."' and identity = '$identity'" );

			//если простой учет, то нужно списать со старого и добавить на новый
			else {

				//количество на складе выбытия
				$kolSkladFrom = $db -> getOne( "SELECT kol FROM {$sqlname}modcatalog_skladpoz WHERE sklad = '$move[skladfrom]' and id = '".$value."' and identity = '$identity'" );

				//остаток на складе выбытия
				$newKolfrom = ($kolSkladFrom < $kol[ $i ]) ? $kolSkladFrom : $kolSkladFrom - $kol[ $i ];

				//количество на складе прихода
				$kolSkladTo = $db -> getOne( "SELECT kol FROM {$sqlname}modcatalog_skladpoz WHERE sklad = '$move[skladto]' and prid = '".$prid[ $i ]."' and identity = '$identity'" );
				$idSkladTo  = $db -> getOne( "SELECT id FROM {$sqlname}modcatalog_skladpoz WHERE sklad = '$move[skladto]' and prid = '".$prid[ $i ]."' and identity = '$identity'" );

				//если такой позиции не записано
				if ( $kolSkladTo == '' ) {

					$db -> query( "INSERT INTO {$sqlname}modcatalog_skladpoz SET ?u", [
						"prid"     => $prid[ $i ],
						"sklad"    => $move['skladto'],
						"date_in"  => current_datum(),
						"status"   => "in",
						"identity" => $identity
					] );
					$idSkladTo = $db -> insertID();

					$kolSkladTo = 0;

				}

				//остаток на складе прихода
				$newKolTo = $kolSkladTo + $kol[ $i ];

				//кол-во на старом складе
				$db -> query( "update {$sqlname}modcatalog_skladpoz set kol = '$newKolfrom' WHERE id = '".$value."' and sklad = '$move[skladfrom]' and identity = '$identity'" );

				//кол-во на новом складе
				$db -> query( "update {$sqlname}modcatalog_skladpoz set kol = '$newKolTo' WHERE id = '".$idSkladTo."' and sklad = '$move[skladto]' and identity = '$identity'" );

			}

		}

		return ['result' => "Сделано"];

	}

	/**
	 * Работа с серийными номерами
	 * @param array $params
	 * @return bool
	 */
	public function serials(array $params = []): bool {

		$sqlname  = $this -> sqlname;
		$db       = $this -> db;

		$serial = $params['serial'];

		foreach ( $serial as $id => $value ) {

			unset( $value['prid'] );

			if ( $id > 0 )
				$db -> query( "UPDATE {$sqlname}modcatalog_skladpoz SET ?u WHERE id = '$id'", $value );

		}

		return true;

	}

	/**
	 * Работа с заявками
	 * @param array $params
	 * @return array
	 */
	public function editzayavka(array $params = []): array {

		$rootpath = dirname( __DIR__, 2 );

		require_once $rootpath."/inc/config.php";
		require_once $rootpath."/inc/dbconnector.php";
		require_once $rootpath."/inc/func.php";

		$identity = ($params['identity'] > 0) ? $params['identity'] : $GLOBALS['identity'];
		$sqlname  = $GLOBALS['sqlname'];
		$db       = $GLOBALS['db'];

		$id = $params['id'];

		//спецификация
		$speka = $params['speka'];

		$zayavka['did']            = (int)$params['did'];
		$zayavka['iduser']         = (int)$params['iduser'];
		$zayavka['content']        = $params['content'];
		$zayavka['datum_priority'] = $params['datum_priority'];
		$zayavka['datum']          = current_datumtime();
		$zayavka['conid']          = (int)$params['conid'];
		$zayavka['isHight']        = ($params['isHight'] != '') ? $params['isHight'] : "no";
		$zayavka['cInvoice']       = $params['cInvoice'];
		$zayavka['cDate']          = ($params['cDate'] != '') ? $params['cDate'] : NULL;
		$zayavka['cSumma']         = pre_format( $params['cSumma'] );

		//Заявка на поиск
		$czayavka['zTitle']       = $params['zTitle'];
		$czayavka['zGod']         = $params['zGod'];
		$czayavka['zProbeg']      = $params['zProbeg'];
		$czayavka['zPriceStart']  = $params['zPriceStart'];
		$czayavka['zPriceEnd']    = $params['zPriceEnd'];
		$czayavka['zNDS']         = $params['zNDS'];
		$czayavka['zAnswer']      = $params['zAnswer'];
		$czayavka['zCoordinator'] = $params['zCoordinator'];

		$zayavka['des'] = json_encode_cyr( $czayavka );

		$err  = [];

		//для новой заявки
		if ( $id < 1 ) {

			//номер последней заявки
			$zayavka['number'] = $db -> getOne( "SELECT max(CAST(number AS UNSIGNED)) FROM {$sqlname}modcatalog_zayavka WHERE identity = '".$identity."'" ) + 1;

			try {

				$zayavka['identity'] = $identity;

				$db -> query( "INSERT INTO {$sqlname}modcatalog_zayavka SET ?u", $zayavka );
				$id = $db -> insertId();

				foreach ( $speka as $value ) {

					//если позиция есть в прайсе
					if ( $value['prid'] > 0 ) {

						try {

							unset( $value['idp'] );

							$value['idz']      = $id;
							$value['identity'] = $identity;

							$db -> query( "INSERT INTO {$sqlname}modcatalog_zayavkapoz SET ?u", $value );

						}
						catch ( Exception $e ) {

							$err[] = 'Ошибка'.$e -> getMessage().' в строке '.$e -> getCode();

						}

					}

				}

				//отправитм уведомление
				$this -> eNotify( $id, 'new' );

			}
			catch ( Exception $e ) {

				$err[] = 'Ошибка'.$e -> getMessage().' в строке '.$e -> getCode();

			}

		}

		//изменение заявки
		else {

			try {

				$data = $db -> filterArray( $zayavka, [
					"did",
					"content",
					"des",
					"iduser",
					"isHight",
					"datum_priority",
					"cInvoice",
					"cDate",
					"cSumma",
					"conid"
				] );

				$db -> query( "UPDATE {$sqlname}modcatalog_zayavka SET ?u WHERE id = '".$id."' and identity = '$identity'", $data );

				//список позиций в заявке
				$idpExists = $db -> getCol( "SELECT id FROM {$sqlname}modcatalog_zayavkapoz WHERE idz = '$id' and identity = '$identity' ORDER BY prid" );

				$idps = [];

				foreach ( $speka as $idp => $value ) {

					if ( $value['prid'] > 0 ) {

						if ( $value['idp'] < 1 ) {//если позиции нет в заявке - добавляем

							try {

								unset( $value['idp'] );

								$value['idz']      = $id;
								$value['identity'] = $identity;

								$db -> query( "INSERT INTO {$sqlname}modcatalog_zayavkapoz SET ?u", $value );
								$rez['insert'][] = $db -> insertId();

							}
							catch ( Exception $e ) {

								$err[] = 'Ошибка'.$e -> getMessage().' в строке '.$e -> getCode();

							}

						}
						//если позиция есть в заявке - обновляем
						//если позиция была, то обновляем её
						elseif ( in_array( $idp, $idpExists ) ) {

								try {
									$db -> query( "UPDATE {$sqlname}modcatalog_zayavkapoz SET ?u WHERE id = '$value[idp]'", [
										"prid" => $value['prid'],
										"kol"  => $value['speca_kol']
									] );
									$rez['update'][] = $value['idp'];

								}
								catch ( Exception $e ) {

									$err[] = 'Ошибка'.$e -> getMessage().' в строке '.$e -> getCode();

								}

							}
						//если позиция новая, то добавляем её
						else {

							try {

								unset( $value['idp'] );

								$value['idz']      = $id;
								$value['identity'] = $identity;

								$db -> query( "INSERT INTO {$sqlname}modcatalog_zayavkapoz SET ?u", $value );
								$rez['insert'][] = $idp;

							}
							catch ( Exception $e ) {

								$err[] = 'Ошибка'.$e -> getMessage().' в строке '.$e -> getCode();

							}

						}

					}

					$idps[] = $idp;

				}

				//ищем удаленные позиции и убираем их
				foreach ( $idpExists as $idpOld ) {

					if ( !in_array( $idpOld, $idps ) ) {

						$db -> getCol( "DELETE FROM {$sqlname}modcatalog_zayavkapoz WHERE id = '$idpOld'" );
						$rez['delete'][] = $idpOld;

					}

				}

			}
			catch ( Exception $e ) {

				$err[] = 'Ошибка'.$e -> getMessage().' в строке '.$e -> getCode();

			}

		}

		//резервирование позиций по сделке
		$sync = ($params['did'] > 0) ? $this -> SyncPoz( "no", [
			"did" => $params['did'],
			"idz" => $id
		] ) : '';

		$result = (!empty( $err )) ? "Выполнено с ошибками" : "Выполнено";

		return [
			"result" => $result,
			"error"  => $err,
			"sync"   => $sync,
			"rez"    => $rez
		];

	}

	/**
	 * Изменение статуса заявки
	 * @param array $params
	 * @return array
	 */
	public function editzstatus(array $params = []): array {

		$sqlname  = $this -> sqlname;
		$identity = ($params['identity'] > 0) ? $params['identity'] : $this -> identity;
		$db       = $this -> db;

		$settings = self ::settings( $identity );

		$id = $params['id'];

		$zayavka['status'] = $params['status'];

		$zayavka['cInvoice']  = $params['cInvoice'];
		$zayavka['cDate']     = !isset($params['cDate']) ? $params['cDate'] : current_datum();
		$zayavka['cSumma']    = pre_format( $params['cSumma'] );
		$zayavka['bid']       = (int)$params['bid'];
		$zayavka['conid']     = (int)$params['conid'];
		$zayavka['sotrudnik'] = $params['sotrudnik'];

		$err = [];

		$des      = $db -> getOne( "SELECT des FROM {$sqlname}modcatalog_zayavka WHERE id = '$id' and identity = '$identity'" );
		$czayavka = json_decode( $des, true );

		$czayavka['zAnswer']      = $params['zAnswer'];
		$czayavka['zCoordinator'] = $params['sotrudnik'];

		$zayavka['des'] = json_encode_cyr( $czayavka );

		$re         = $db -> getRow( "SELECT did, status, providerid FROM {$sqlname}modcatalog_zayavka WHERE id = '$id' and identity = '$identity'" );
		$old_status = $re['status'];
		$did        = (int)$re['did'];
		$providerid = (int)$re['providerid'];

		if ( $zayavka['status'] == 1 && $old_status == 0 )
			$zayavka['datum_start'] = current_datumtime();

		if ( $zayavka['status'] == 2 )
			$zayavka['datum_end'] = current_datumtime();

		try {

			$db -> query( "UPDATE {$sqlname}modcatalog_zayavka SET ?u WHERE id = '$id' and identity = '$identity'", $zayavka );

			//расходы добавляем, если включено в настройках
			if ( $zayavka['status'] == 2 && $settings['mcAutoProvider'] == 'yes' ) {

				//добавим расход в бюджет и пересчитаем суммы по сделке
				if ( $providerid == 0 ) {

					try {

						$db -> query( "INSERT INTO {$sqlname}dogprovider SET ?u", [
							"conid"    => (int)$zayavka['conid'],
							"partid"   => (int)$zayavka['partid'],
							"did"      => $did,
							"summa"    => $zayavka['cSumma'],
							"status"   => 0,
							"bid"   => 0,
							"identity" => $identity
						] );
						$providerid = $db -> insertId();

						//свяжем с расходом в связях
						$db -> query( "UPDATE {$sqlname}modcatalog_zayavka SET ?u WHERE id = '".$id."' and identity = '$identity'", ["providerid" => $providerid] );

						if ( $did > 0 ) {

							addProviderRashod( $did, $zayavka['cSumma'] );

						}

					}
					catch ( Exception $e ) {

						$err[] = 'Ошибка'.$e -> getMessage().' в строке '.$e -> getCode();

					}

				}
				if ( $providerid > 0 ) {

					try {

						$db -> query( "UPDATE {$sqlname}dogprovider SET ?u WHERE id = '$providerid' and identity = '$identity'", ["summa" => $zayavka['cSumma']] );

						if ( $did > 0 ) {

							addProviderRashod( $did, $zayavka['cSumma'] );

						}

					}
					catch ( Exception $e ) {

						$err[] = 'Ошибка'.$e -> getMessage().' в строке '.$e -> getCode();

					}

				}

			}

			//отправитм уведомление
			$this -> eNotify( $id, 'status' );

		}
		catch ( Exception $e ) {

			$err[] = 'Ошибка'.$e -> getMessage().' в строке '.$e -> getCode();

		}

		$result = (!empty( $err )) ? "Выполнено с ошибками" : "Выполнено";

		return [
			"result" => $result,
			"error"  => $err
		];

	}

	/**
	 * Редактор предложений
	 * @param array $params
	 * @return array
	 */
	public function editoffer(array $params = []): array {

		$rootpath = $this -> rootpath;
		$sqlname  = $this -> sqlname;
		$identity = ($params['identity'] > 0) ? $params['identity'] : $this -> identity;
		$db       = $this -> db;
		$fpath    = $this -> fpath;

		$err = $file = [];

		$id                 = $params['id'];
		$zayavka['iduser']  = $params['iduser'];
		$zayavka['content'] = $params['content'];
		$zayavka['status']  = $params['status'];

		$czayavka['zTitle']  = $params['zTitle'];
		$czayavka['zGod']    = $params['zGod'];
		$czayavka['zProbeg'] = $params['zProbeg'];
		$czayavka['zPrice']  = $params['zPrice'];
		$czayavka['zNDS']    = $params['zNDS'];

		$uploaddir = $rootpath.'/files/'.$fpath.'modcatalog/';

		if ( $_FILES['file']['name'] != '' ) {

			$cur_ext = texttosmall( getExtention($_FILES['file']['name']) );

			if ( !in_array( $cur_ext, [
				"png",
				"jpeg",
				"jpg",
				"gif"
			] ) ) {

				$err[] = 'Ошибка - Недопустимый формат файла. Только PNG, JPEG, JPG, GIF';

			}
			else {

				$ftitle     = basename( $_FILES['file']['name'] );
				$tim        = time();
				$fname      = $tim.".".getExtention($ftitle);
				$uploadfile = $uploaddir.$fname;

				//Сначала загрузим файл на сервер
				if ( move_uploaded_file( $_FILES['file']['tmp_name'], $uploadfile ) ) {

					$file = [
						"name" => $ftitle,
						"file" => $fname
					];

				}
				else {

					$err[] = 'Ошибка - '.$_FILES['file']['error'];

				}
			}
		}

		$oldz = [];

		if ( $id > 0 )
			$oldz = json_decode( $db -> getOne( "SELECT des FROM {$sqlname}modcatalog_offer where id = ".$id." and identity = '$identity'" ), true );

		if ( !empty( $file ) ) {

			$czayavka['zFile'] = $file;
			unlink( $uploaddir.$oldz['zFile']['file'] );

		}
		else $czayavka['zFile'] = $oldz['zFile'];

		$zayavka['des'] = json_encode_cyr( $czayavka );

		//для новой заявки
		if ( $id < 1 ) {

			$zayavka['datum']    = current_datumtime();
			$zayavka['identity'] = $identity;

			try {

				$db -> query( "INSERT INTO {$sqlname}modcatalog_offer SET ?u", $zayavka );
				$id = $db -> insertId();

			}
			catch ( Exception $e ) {

				$err[] = 'Ошибка'.$e -> getMessage().' в строке '.$e -> getCode();

			}

		}

		//изменение заявки
		else {

			try {

				$db -> query( "UPDATE {$sqlname}modcatalog_offer SET ?u WHERE id = '".$id."' and identity = '$identity'", $zayavka );

			}
			catch ( Exception $e ) {

				$err[] = 'Ошибка'.$e -> getMessage().' в строке '.$e -> getCode();

			}

		}

		$result = (!empty( $err )) ? "Выполнено с ошибками" : "Выполнено";

		return [
			"id"     => $id,
			"result" => $result,
			"error"  => $err
		];

	}

	/**
	 * удаление резерва
	 * @param array $params
	 *    integer id - идентификатор записи резерва
	 * @return array
	 */
	public function removereserve(array $params = []): array {

		$db       = $this -> db;
		$sqlname  = $this -> sqlname;
		$identity = ($params['identity'] > 0) ? $params['identity'] : $this -> identity;

		$err = [];

		$id = $params['id'];

		try {

			//открепляем привязку позиций склада от сделки

			//данные по текущему резерву
			$reserve = $db -> getRow( "SELECT * FROM {$sqlname}modcatalog_reserv where id = '$id' and identity = '$identity'" );

			//проходим складские позиции, привязанные к сделке
			$rs = $db -> getAll( "SELECT * FROM {$sqlname}modcatalog_skladpoz where sklad = '$reserve[sklad]' and prid = '$reserve[prid]' and did = '$reserve[did]' and status = 'in' and identity = '$identity'" );

			$i = 0;
			foreach ( $rs as $da ) {

				//снимаем только указанное в резерве количество (если резерва было 2)
				//хотя врят ли под сделку будет 2 записи резерва
				if ( $i < $rs['kol'] )
					$db -> query( "update {$sqlname}modcatalog_skladpoz set did = '' WHERE id = '$da[id]' and identity = '$identity'" );

				$i++;

			}

			//удаляем резерв
			$db -> query( "delete from {$sqlname}modcatalog_reserv WHERE id = '$id' and identity = '$identity'" );

		}
		catch ( Exception $e ) {

			$err[] = 'Ошибка'.$e -> getMessage().' в строке '.$e -> getCode();

		}

		$result = (!empty( $err )) ? "Выполнено с ошибками" : "Выполнено";

		return [
			"result" => $result,
			"error"  => $err
		];

	}

	/**
	 * Удаление заявки
	 * @param array $params
	 * @return array
	 */
	public function removezayavka(array $params = []): array {

		$sqlname  = $this -> sqlname;
		$identity = ($params['identity'] > 0) ? $params['identity'] : $this -> identity;
		$db       = $this -> db;

		$err = [];

		$id = $params['id'];

		try {

			$db -> query( "delete from {$sqlname}modcatalog_zayavka WHERE id = '$id' and identity = '$identity'" );
			$db -> query( "delete from {$sqlname}modcatalog_zayavkapoz WHERE idz = '$id' and identity = '$identity'" );

		}
		catch ( Exception $e ) {

			$err[] = 'Ошибка'.$e -> getMessage().' в строке '.$e -> getCode();

		}

		$result = (!empty( $err )) ? "Выполнено с ошибками" : "Выполнено";

		return [
			"result" => $result,
			"error"  => $err
		];

	}

	/**
	 * Удаление ордера
	 * @param array $params
	 * @return array
	 */
	public function removeorder(array $params = []): array {

		$sqlname  = $this -> sqlname;
		$identity = ($params['identity'] > 0) ? $params['identity'] : $this -> identity;
		$db       = $this -> db;

		$err = [];

		$id = $params['id'];

		// инфа по ордеру
		$order = $db -> getRow( "SELECT * FROM {$sqlname}modcatalog_akt WHERE id = '$id' and identity = '$identity'" );

		// для не проведенных ордеров
		if ( $order['isdo'] != 'yes' ) {

			try {

				if ( $order['tip'] == 'income' )
					$db -> query( "UPDATE {$sqlname}modcatalog_skladpoz SET idorder_in  = '0' WHERE idorder_in = '$id' and identity = '$identity'" );

				elseif ( $order['tip'] == 'outcome' )
					$db -> query( "UPDATE {$sqlname}modcatalog_skladpoz SET idorder_out = '0' WHERE idorder_out = '$id' and identity = '$identity'" );

				$db -> query( "delete from {$sqlname}modcatalog_akt WHERE id = '$id' and identity = '$identity'" );
				$db -> query( "delete from {$sqlname}modcatalog_aktpoz WHERE ida = '$id' and identity = '$identity'" );

			}
			catch ( Exception $e ) {

				$err[] = 'Ошибка'.$e -> getMessage().' в строке '.$e -> getCode();

			}

		}
		// для проведенных ордеров
		else {

			$sklad = $order['sklad'];

			//$zayavka = $db -> getRow( "SELECT * FROM {$sqlname}modcatalog_zayavka WHERE id = '$order[idz]' and identity = '$identity'" );

			// обходим позиции ордера
			// наша задача вернуть их на склад
			$r = $db -> query( "SELECT * FROM {$sqlname}modcatalog_aktpoz WHERE ida = '$id' and identity = '$identity'" );
			while ($d = $db -> fetch( $r )) {

				$prid = $d['prid'];
				$kol  = $d['kol'];

				// количество на складе
				$skladpoz = $db -> getRow( "SELECT id, kol FROM {$sqlname}modcatalog_skladpoz WHERE prid = '$prid' AND sklad = '$sklad'" );

				// новое количество
				if ( $order['tip'] == 'income' )
					$newkol = $skladpoz['kol'] - $kol;

				else
					$newkol = $skladpoz['kol'] + $kol;

				$db -> query( "UPDATE {$sqlname}modcatalog_skladpoz SET kol  = '$newkol' WHERE id = '$skladpoz[id]'" );

				// удаляем позицию из заявки
				if ( $order['idz'] > 0 )
					$db -> query( "delete from {$sqlname}modcatalog_zayavkapoz WHERE idz = '$order[idz]' and prid = '$prid'" );

				// удаляем позицию ордера
				$db -> query( "delete from {$sqlname}modcatalog_aktpoz WHERE id = '$d[id]'" );

				// добавляем в резерв
				if ( $order['tip'] == 'outcome' )
					$db -> query( "INSERT INTO {$sqlname}modcatalog_reserv SET ?u", [
						"did"      => $order['did'],
						"prid"     => $d['prid'],
						"kol"      => $d['kol'],
						"status"   => 'reserved',
						"identity" => $identity
					] );

			}

			// удаляем ордер
			$db -> query( "delete from {$sqlname}modcatalog_akt WHERE id = '$id' and identity = '$identity'" );
			$db -> query( "delete from {$sqlname}modcatalog_zayavka WHERE id = '$order[idz]'" );

		}

		$result = (!empty( $err )) ? "Выполнено с ошибками" : "Выполнено";

		return [
			"result" => $result,
			"error"  => $err
		];

	}

	/**
	 * Обработка поступлений на склад и резервирование как по ордеру, так и авторезервирование
	 * @param string $print
	 * @param        $params , м.б. пустым (для авторезерва)
	 *                       ida   - id ордера,
	 *                       did   - id сделки,
	 *                       prid  - id позиции,
	 *                       idz   - id заявки,
	 *                       sklad - id склада (на перспективу, для выбора во время смены этапа)
	 * @return string
	 */
	public function SyncPoz(string $print = 'no', array $params = []): string {

		$sqlname  = $this -> sqlname;
		$iduser1  = ($params['iduser'] > 0) ? $params['iduser'] : $this -> iduser1;
		$identity = ($params['identity'] > 0) ? $params['identity'] : $this -> identity;
		$db       = $this -> db;

		$settings = self ::settings( $identity );

		//if ( $settings['mcSkladPoz'] != "yes" )
		//	$pozzi = " and status != 'out'";

		$rezz  = '';
		$debag = false;

		$good = 0;
		$zay  = 0;

		//склад, на который делаем приход
		$params['sklad'] = ($params['ida'] > 0) ? $db -> getOne( "SELECT sklad FROM {$sqlname}modcatalog_akt WHERE id = '".$params['ida']."' and identity = '$identity'" ) : 0;

		if ( $params['ida'] > 0 ) {

			//если обрабатывается акт по сделке, то позиции ставим в резерв
			//принимаем параметры ida, did
			if( $params['did'] > 0 ) {

				$params['kol']      = $db -> getOne( "SELECT kol FROM {$sqlname}modcatalog_aktpoz WHERE prid = '".$params['prid']."' and ida = '".$params['ida']."' and identity = '$identity'" );
				$params['status']   = 'reserved';
				$params['datum']    = current_datumtime();
				$params['identity'] = $identity;

				$data = $db -> filterArray( $params, [
					'prid',
					'sklad',
					'status',
					'datum',
					'kol',
					'did',
					'ida',
					'idz',
					'identity'
				] );

				$db -> query( "INSERT INTO {$sqlname}modcatalog_reserv SET ?u", $data );

				$good++;

			}
			//если обрабатывается акт не по сделке, то делаем резервирование по всем сделкам, имеющим такие позиции в не проведенных заявках
			//принимаем параметры ida
			else{

				//Количество позиции по ордеру
				$kolOrder = $db -> getOne( "SELECT kol FROM {$sqlname}modcatalog_aktpoz where prid = '".$params['prid']."' and ida = '".$params['ida']."' and identity = '$identity'" );

				//компания к которой прикреплен склад, чтобы отфильтровать сделки
				$mcid = ($params['sklad'] > 0) ? $db -> getOne( "SELECT mcid FROM {$sqlname}modcatalog_sklad WHERE id = '$params[sklad]' and  identity = '$identity' ORDER BY title" ) : 0;

				$kolOstatok = $kolOrder;

				if ( $mcid > 0 ) {

					//запрос выделяет сделки, по которым есть не проведенные заявки
					$q = "
						SELECT
							{$sqlname}speca.prid,
							{$sqlname}speca.kol,
							{$sqlname}speca.did,
							{$sqlname}dogovor.mcid
						FROM {$sqlname}speca
							LEFT JOIN {$sqlname}dogovor ON {$sqlname}dogovor.did = {$sqlname}speca.did
							LEFT JOIN {$sqlname}dogcategory ON {$sqlname}dogovor.idcategory = {$sqlname}dogcategory.idcategory
						WHERE
							{$sqlname}speca.prid = '".$params['prid']."' and
							{$sqlname}dogovor.mcid = '$mcid' and
							{$sqlname}dogcategory.title >= ".$settings['mcStepPers']." and
							{$sqlname}speca.identity = '$identity'
							GROUP BY {$sqlname}dogovor.did
					";

					$res = $db -> getAll( $q );
					foreach ( $res as $data ) {

						//проверим позиции в заявках со статусом 0 (создана), 1 (в работе), и если они там есть работаем с ними
						//ставим позиции в резерв и уменьшаем количество в заявках
						//цель: не резервировать позиции в сделках, по которым уже есть заявки
						$q1 = "
						SELECT
							did
						FROM {$sqlname}modcatalog_zayavkapoz
							LEFT JOIN {$sqlname}modcatalog_zayavka ON {$sqlname}modcatalog_zayavka.id = {$sqlname}modcatalog_zayavkapoz.idz
						WHERE
							{$sqlname}modcatalog_zayavkapoz.prid = '".$params['prid']."' and
							{$sqlname}modcatalog_zayavka.status IN (0, 1) and
							{$sqlname}modcatalog_zayavkapoz.identity = '$identity'
					";

						//сделки, подходящие под условия:
						$dids = $db -> getCol( $q1 );

						if ( $kolOstatok > 0 && !in_array( $data['did'], $dids ) ) {

							//вычисляем разницу м/у количеством в сделке и в приходе
							//если значение отрицательное, то значит позиций пришло больше, чем в сделке и будем делать резерв на всё количество
							$params['kol'] = ($kolOstatok >= $data['kol']) ? $data['kol'] : $data['kol'] - $kolOstatok;

							$kolOstatok -= $params['kol'];

							$params['prid']     = $data['prid'];
							$params['status']   = 'reserved';
							$params['datum']    = current_datumtime();
							$params['identity'] = $identity;

							$da = $db -> filterArray( $params, [
								'prid',
								'sklad',
								'status',
								'datum',
								'kol',
								'did',
								'identity'
							] );

							$db -> query( "INSERT INTO {$sqlname}modcatalog_reserv SET ?u", $da );

							$good++;

						}


					}

				}

			}

		}
		elseif( $params['did'] > 0 ) {

				//если обрабатывается авторезерв по сделке без заявки, то резервируем нужное количество, либо создаем заявку
				//принимаем параметры did, sklad
				if ( (int)$params['idz'] == 0 ) {

					//компания к которой прикреплен склад, чтобы отфильтровать сделки
					$deal = get_dog_info( $params['did'], "yes" );
					$mcid = $deal['mcid'];

					$list = [];

					$complete = $this -> CompleteStatus( $params['did'] );

					//авторезерв делаем строго при переходе сделки на заданный этап
					if ( $deal['idcategory'] == $settings['mcStep'] && $complete['delta'] > 0 ) {

						$params['sklad'] = ($params['sklad'] < 1) ? $db -> getOne( "SELECT id FROM {$sqlname}modcatalog_sklad WHERE mcid = '".$mcid."' and isDefault = 'yes' and identity = '$identity'" ) : $params['sklad'];

						//если склад задан, то идем дальше, т.к. для резерва нужен склад
						if ( $params['sklad'] > 0 ) {

							$speca = $db -> getAll( "SELECT * FROM {$sqlname}speca WHERE did = '".$params['did']."' and identity = '$identity'" );
							foreach ( $speca as $data ) {

								//количество на складе
								$kolSklad = (float)$db -> getOne( "SELECT SUM(kol) as kol FROM {$sqlname}modcatalog_skladpoz WHERE prid = '".$data['prid']."' and sklad = '".$params['sklad']."' and identity = '$identity'" );

								//количество уже зарезервированных под эту сделку
								$kolReserve = (float)$db -> getOne( "SELECT SUM(kol) as kol FROM {$sqlname}modcatalog_reserv WHERE prid = '".$data['prid']."' and did = '".$params['did']."' and sklad = '".$params['sklad']."' and identity = '$identity'" );

								//количество уже зарезервированных под другие сделки
								$kolReserveOther = (float)$db -> getOne( "SELECT SUM(kol) as kol FROM {$sqlname}modcatalog_reserv WHERE prid = '".$data['prid']."' and did != '".$params['did']."' and sklad = '".$params['sklad']."' and identity = '$identity'" );

								//что осталось зарезервировать
								$kolToReserve = $kolSklad - $kolReserveOther - $kolReserve;

								//если есть, что резервировать
								if ( $kolToReserve > 0 ) {

									$params['kol']      = $data['kol'];
									$params['prid']     = $data['prid'];
									$params['status']   = 'reserved';
									$params['datum']    = current_datumtime();
									$params['identity'] = $identity;

									$da = $db -> filterArray( $params, [
										'prid',
										'sklad',
										'status',
										'datum',
										'kol',
										'did',
										'identity'
									] );

									$db -> query( "INSERT INTO {$sqlname}modcatalog_reserv SET ?u", $da );
									$good++;

								}
								//если нет, то собираем массив для создания заявки
								else {

									$list[] = [
										"prid" => $data['prid'],
										"kol"  => $data['kol'],
									];

								}

							}

							//создаем заявку
							if ( !empty( $list ) ) {

								$number = $db -> getOne( "SELECT max(number + 0) FROM `{$sqlname}modcatalog_zayavka` WHERE identity = '".$identity."'" );
								$number++;

								$content = 'Заявка размещена автоматически в связи со сменой этапа сделки';

								$db -> query( "INSERT INTO {$sqlname}modcatalog_zayavka SET ?u", [
									"number"   => $number,
									"did"      => $params['did'],
									"datum"    => current_datumtime(),
									"iduser"   => $iduser1,
									"content"  => $content,
									"identity" => $identity
								] );
								$zay++;

								$id = $db -> insertId();

								foreach ( $list as $da ) {

									$db -> query( "INSERT INTO {$sqlname}modcatalog_zayavkapoz SET ?u", [
										"idz"      => $id,
										"prid"     => $da['prid'],
										"kol"      => $da['kol'],
										"identity" => $identity
									] );

								}

								//отправитм уведомление
								$this -> eNotify( $id, 'new' );

							}

						}

					}

				}

				//если обрабатывается авторезерв по заявке, то резервируем нужное количество, либо создаем заявку
				//принимаем параметры did, sklad
				else {

					//в этом случае создавать новые заявки не надо!!!!!!!!

					//склад берем из заявки
					$params['sklad'] = $db -> getOne( "SELECT id FROM {$sqlname}modcatalog_zayavka WHERE id = '".$params['idz']."' and identity = '$identity'" );

					//если склад задан, то идем дальше, т.к. для резерва нужен склад
					if ( $params['sklad'] > 0 ) {

						$speca = $db -> getAll( "SELECT * FROM {$sqlname}modcatalog_zayavkapoz WHERE idz = '".$params['idz']."' and identity = '$identity'" );
						foreach ( $speca as $data ) {

							//количество на складе
							$kolSklad = $db -> getOne( "SELECT SUM(kol) as kol FROM {$sqlname}modcatalog_skladpoz WHERE prid = '".$data['prid']."' and sklad = '".$params['sklad']."' and identity = '$identity'" );

							//количество уже зарезервированных под эту сделку
							$kolReserve = $db -> getOne( "SELECT SUM(kol) as kol FROM {$sqlname}modcatalog_reserv WHERE prid = '".$data['prid']."' and did = '".$params['did']."' and sklad = '".$params['sklad']."' and identity = '$identity'" );

							//количество уже зарезервированных под другие сделки
							$kolReserveOther = $db -> getOne( "SELECT SUM(kol) as kol FROM {$sqlname}modcatalog_reserv WHERE prid = '".$data['prid']."' and did != '".$params['did']."' and sklad = '".$params['sklad']."' and identity = '$identity'" );

							//что осталось зарезервировать
							$kolToReserve = $kolSklad - $kolReserveOther - $kolReserve;

							//если есть, что резервировать
							if ( $kolToReserve > 0 ) {

								$params['kol']      = $data['kol'];
								$params['prid']     = $data['prid'];
								$params['status']   = 'reserved';
								$params['datum']    = current_datumtime();
								$params['identity'] = $identity;

								$da = $db -> filterArray( $params, [
									'prid',
									'sklad',
									'status',
									'datum',
									'kol',
									'did',
									'identity'
								] );

								$db -> query( "INSERT INTO {$sqlname}modcatalog_reserv SET ?u", $da );
								$good++;

							}

						}

					}

				}

			}
		//если производтся синхронизация не по сделке, подходит для авторезерва
		//не принимаем параметры
		else{

			//проходим все сделки не в заявках
			//$res = $db -> getAll("SELECT * FROM {$sqlname}dogovor where did NOT IN (SELECT did FROM {$sqlname}modcatalog_zayavka where id > 0 and did > 0 and identity = '$identity') and identity = '$identity'");

			//найдем все этапы сделок >= $settings['mcStep']
			//$stepsGood = $db -> getCol("SELECT idcategory FROM {$sqlname}dogcategory where CAST(title AS UNSIGNED) >= '".$settings['mcStep']."' and identity = '$identity'");

			$list = [];

			$res = $db -> getAll( "SELECT * FROM {$sqlname}dogovor where idcategory IN (SELECT idcategory FROM {$sqlname}dogcategory where CAST(title AS UNSIGNED) >= '".$settings['mcStepPers']."' and identity = '$identity') and identity = '$identity'" );
			foreach ( $res as $da ) {

				//если сделка закрыта с факт.прибылью == 0, то удаляем резервы под ней
				if ( $da['close'] == "yes" && $da['kol_fact'] == 0 ) {

					$db -> query( "DELETE FROM {$sqlname}modcatalog_reserv WHERE did = '".$da['did']."' and identity = '$identity'" );

				}
				elseif ( $da['close'] != "yes" ) {

					if ( $settings['mcStep'] >= $da['idcategory'] ) {

						$params['sklad'] = $db -> getOne( "SELECT id FROM {$sqlname}modcatalog_sklad WHERE mcid = '".$da['mcid']."' and isDefault = 'yes' and identity = '$identity'" );

						//если склад задан, то идем дальше, т.к. для резерва нужен склад
						if ( $params['sklad'] > 0 ) {

							//Старое (не использовать)
							//$list = array();

							$speca = $db -> getAll( "SELECT * FROM {$sqlname}speca WHERE did = '".$da['did']."' and prid > 0 and identity = '$identity'" );
							foreach ( $speca as $data ) {

								// проверяем категорию - включена ли она для Каталога
								// если нет, то идем дальше
								$cat = $db -> getOne( "SELECT pr_cat FROM {$sqlname}price WHERE n_id = '".$data['prid']."'" );
								if ( !in_array( $cat, $settings['mcPriceCat'] ) )
									goto rext;

								// количество на складе
								$kolSklad = (float)$db -> getOne( "SELECT SUM(kol) as kol FROM {$sqlname}modcatalog_skladpoz WHERE prid = '".$data['prid']."' and sklad = '".$params['sklad']."' and identity = '$identity'" );

								// количество уже зарезервированных под эту сделку
								$kolReserved = (float)$db -> getOne( "SELECT SUM(kol) as kol FROM {$sqlname}modcatalog_reserv WHERE prid = '".$data['prid']."' and did = '".$da['did']."' and sklad = '".$params['sklad']."' and identity = '$identity'" );

								// количество уже зарезервированных под другие сделки
								$kolReserveOther = (float)$db -> getOne( "SELECT SUM(kol) as kol FROM {$sqlname}modcatalog_reserv WHERE prid = '".$data['prid']."' and did != '".$da['did']."' and sklad = '".$params['sklad']."' and identity = '$identity'" );

								// что осталось зарезервировать по сделке
								$kolToReserve = $data['kol'] - $kolReserved;

								// свободно на складе
								$kolFreeOnSklad = $kolSklad - $kolReserveOther - $kolReserved;

								// останется свободным после резерва
								//$kolToReserveS = $kolFreeOnSklad - $kolToReserve;

								// сколько сможем зарезервировать
								$toReserve = 0;

								if ( $kolFreeOnSklad > $kolToReserve && $kolFreeOnSklad > 0 )
									$toReserve = $kolToReserve;
								elseif ( $kolFreeOnSklad < $kolToReserve && $kolFreeOnSklad > 0 )
									$toReserve = $kolToReserve - $kolFreeOnSklad;

								// сколько надо заказать
								$existInZayavka = (float)$db -> getOne( "SELECT SUM(kol) FROM {$sqlname}modcatalog_zayavkapoz WHERE prid = '$data[prid]' and idz IN (SELECT id FROM {$sqlname}modcatalog_zayavka WHERE did = '$da[did]') and identity = '".$identity."'" );
								$toZayavka      = $kolToReserve - $toReserve - $existInZayavka;

								// массив для обработки
								// использован для отладки
								if ( $debag ) {

									print array2string( [
											"did"            => $da['did'],
											"prid"           => $data['prid'],
											"title"          => $db -> getOne( "SELECT title FROM {$sqlname}price WHERE n_id = '".$data['prid']."'" ),
											"kol"            => $data['kol'],
											"kolSklad"       => $kolSklad,
											"kolFreeOnSklad" => $kolFreeOnSklad,
											"kolToReserve"   => $kolToReserve,
											"sklad"          => $db -> getOne( "SELECT title FROM {$sqlname}modcatalog_sklad WHERE id = '".$params['sklad']."'" ),
											"reserved"       => $kolReserved,
											"toReserve"      => $toReserve,
											"existInZayavka" => $existInZayavka,
											"toZayavka"      => $toZayavka
										] )."\n\n";

								}

								// если есть, что резервировать
								if ( $toReserve > 0 && !$debag ) {

									$params['kol']      = $toReserve;
									$params['prid']     = $data['prid'];
									$params['did']      = $da['did'];
									$params['status']   = 'reserved';
									$params['datum']    = current_datumtime();
									$params['identity'] = $identity;

									$r = $db -> filterArray( $params, [
										'prid',
										'sklad',
										'status',
										'datum',
										'kol',
										'did',
										'identity'
									] );

									$db -> query( "INSERT INTO {$sqlname}modcatalog_reserv SET ?u", $r );
									$good++;

								}

								//если позиция уже есть в заявке и зарезервирована, то удалим позицию из заявки
								if ( $toZayavka < 1 && $toZayavka == $existInZayavka && ($toZayavka - $kolReserved) > 0 && !$debag ) {

									if ( $toZayavka == $kolReserved ) {

										$db -> query( "DELETE FROM {$sqlname}modcatalog_zayavkapoz WHERE prid = '$data[prid]' and idz IN (SELECT id FROM {$sqlname}modcatalog_zayavka WHERE did = '$da[did]') and identity = '$identity'" );

									}
									else {

										$db -> query( "UPDATE {$sqlname}modcatalog_zayavkapoz SET kol = '".($toZayavka - $kolReserved)."' WHERE prid = '$data[prid]' and idz IN (SELECT id FROM {$sqlname}modcatalog_zayavka WHERE did = '$da[did]') and identity = '$identity'" );

									}

								}

								//если нет, то собираем массив для создания заявки. Старое (не использовать)
								/*
								if ($toZayavka > 0) {

									//Есть ли заявка с такой позицией под эту сделку?
									$zexist = $db -> getOne("SELECT SUM(kol) FROM {$sqlname}modcatalog_zayavkapoz WHERE prid = '$data[prid]' and idz IN (SELECT id FROM {$sqlname}modcatalog_zayavka WHERE did = '$da[did]') and identity = '".$identity."'") + 0;

									$nkol = $toZayavka - $zexist;

									if ($nkol > 0) $list[] = array(
										"prid" => $data['prid'],
										"kol"  => $nkol,
									);

								}
								*/

								// если есть что размещать в заявках
								// то добавляем в массив
								if ( $toZayavka > 0 ) {

									$list[ $da['did'] ][ $data['prid'] ] = $toZayavka;

								}

								rext:

							}

							if ( !empty( $list[ $da['did'] ] ) && $debag ) {

								print array2string( $list[ $da['did'] ] );

							}

						}

					}

				}

			}

			//обрабатываем массив позиций для создания заявок
			if ( !empty( $list ) && !$debag ) {

				foreach ( $list as $did => $item ) {

					$number = $db -> getOne( "SELECT max(number + 0) FROM {$sqlname}modcatalog_zayavka WHERE identity = '$identity'" );
					$number++;

					$content = 'Заявка размещена в результате запуска массового функции Авторезерва';

					$db -> query( "INSERT INTO {$sqlname}modcatalog_zayavka SET ?u", [
						"number"   => $number,
						"did"      => $did,
						"datum"    => current_datumtime(),
						"iduser"   => $iduser1,
						"content"  => $content,
						"identity" => $identity
					] );
					$zay++;

					$id = $db -> insertId();

					foreach ( $item as $prid => $kol ) {

						$db -> query( "INSERT INTO {$sqlname}modcatalog_zayavkapoz SET ?u", [
							"idz"      => $id,
							"prid"     => $prid,
							"kol"      => $kol,
							"identity" => $identity
						] );

					}

				}

			}

			//пройдем заявки и удалим пустые
			$res = $db -> query( "SELECT * FROM {$sqlname}modcatalog_zayavka WHERE identity = '$identity'" );
			while ($da = $db -> fetch( $res )) {

				$count = (float)$db -> getOne( "SELECT SUM(kol) as kol FROM {$sqlname}modcatalog_zayavkapoz WHERE idz = '".$da['id']."' and identity = '$identity'" );
				if ( $count == 0 ) {

					$db -> query( "DELETE FROM {$sqlname}modcatalog_zayavka WHERE id = '".$da['id']."'" );

				}

			}

		}

		if ( $print == 'yes' ) {

			$rezz .= '<DIV class="zagolovok">Результат обработки</DIV>';
			$rezz .= "Поставлено в резерв <b>".$good."</b> позиций.<br>Добавлено <b>".$zay."</b> заявок.";
			$rezz .= '<div class="text-right"><A href="javascript:void(0)" onClick="DClose()" class="button">Отмена</A></div>';

		}
		else {

			$rezz = "Поставлено в резерв ".$good." позиций. <br>Добавлено ".$zay." заявок.";

		}

		return $rezz;

	}

	/**
	 * Автоматическое резервирование и создание заявок при смене этапа сделки
	 * инициируется в классе Deal (func.php)
	 *
	 * @param string $print
	 * @param int    $did
	 * @param        $params - identity, iduser1,
	 * @return string
	 */
	public function SyncReserv(string $print = 'no', int $did = 0, array $params = []) {

		$sqlname  = $this -> sqlname;
		$iduser1  = ($params['iduser'] > 0) ? $params['iduser'] : $this -> iduser1;
		$identity = ($params['identity'] > 0) ? $params['identity'] : $this -> identity;
		$db       = $this -> db;

		$settings = self ::settings( $identity );

		$good      = $zay = $zaypoz = $updz = 0;
		$rezz      = '';

		//если действие не по конкретной сделке
		if ( $did < 1 ) {

			//пройдемся по договорам, которые удовлетворяют условиям по этапу
			$dids = $db -> getCol( "SELECT did FROM {$sqlname}dogovor WHERE did > 0 and idcategory IN (SELECT idcategory FROM {$sqlname}dogcategory WHERE title >= '".$settings['mcStepPers']."' and identity = '$identity') and close != 'yes' and identity = '$identity'" );

		}
		else $dids[] = $did;

		foreach($dids as $didd) {

			$resc      = $db -> getAll( "SELECT iduser, close, kol_fact FROM {$sqlname}dogovor WHERE did = '$didd' and identity = '$identity'" );
			$dclose    = $resc["close"];
			$dkol_fact = $resc["kol_fact"];

			//если сделка закрыта с проигрышем, то удаляем резерв
			if ( $dclose == 'yes' && $dkol_fact == 0 ) {

				//проходим резервы по сделке
				$res = $db -> getAll( "SELECT * FROM {$sqlname}modcatalog_reserv WHERE did = '$didd' and identity = '$identity'" );
				foreach ( $res as $data ) {

					//получаем склад
					//$sklad = $db -> getOne("SELECT * FROM {$sqlname}modcatalog_zayavka WHERE id = '".$data['idz']."' and identity = '$identity'");
					$sklad = $data['idz'];

					$db -> query( "DELETE FROM {$sqlname}modcatalog_reserv WHERE prid = '".$data['prid']."' and did = '$didd' and sklad = '$sklad' and identity = '$identity'" );

				}

			}
			//если сделка открыта
			elseif ( $dclose != 'yes' ) {

				$ida = $db -> getOne( "SELECT id FROM {$sqlname}modcatalog_akt WHERE did = '$didd' and tip = 'outcome' and identity = '$identity'" );

				if ( $ida < 1 ) {//если сделки нет в реализации

					$res = $db -> getAll( "SELECT * FROM {$sqlname}speca WHERE did = '$didd' and identity = '$identity'" );
					foreach ( $res as $data ) {

						if ( $data['prid'] > 0 ) {//только прайсовые позиции

							//проверяем наличие позиции на складе
							$kol_sclad = $db -> getOne( "SELECT kol FROM {$sqlname}modcatalog WHERE prid = '".$data['prid']."' and identity = '$identity'" );

							//останется на складе после резерва
							$delta = $kol_sclad - $data['kol'];

							//проверяем наличие позиции в резерве по этой сделке
							$kol_reserv = $db -> getOne( "SELECT kol FROM {$sqlname}modcatalog_reserv WHERE prid = '".$data['prid']."' and did = '$didd' and identity = '$identity'" );

							//если такой позиции еще нет в резерве, то резервируем
							if ( $kol_reserv < 1 ) {

								if ( $delta >= 0 && $kol_sclad > 0 ) {//если на складе есть нужное нам кол-во, то резервируем его

									$db -> query( "INSERT INTO {$sqlname}modcatalog_reserv SET ?u", [
										"did"      => $didd,
										"prid"     => $data['prid'],
										"kol"      => $data['kol'],
										"status"   => 'reserved',
										"identity" => $identity
									] );

									$good += $data['kol'];

								}
								if ( $delta < 0 ) {

									//если на складе меньше позиций чем надо, то резервируем сколько есть + создаем заявку на оставшееся кол-во
									if ( $kol_sclad > 0 ) {

										$db -> query( "INSERT INTO {$sqlname}modcatalog_reserv SET ?u", [
											"did"      => $didd,
											"prid"     => $data['prid'],
											"kol"      => $kol_sclad,
											"status"   => 'reserved',
											"identity" => $identity
										] );

										$good += $kol_sclad;

										$delta = $data['kol'] - $kol_sclad;//определяем дельту как разницу между необходимым и тем, что зарезервировали

									}

									//проверяем наличие заявки
									$idz = $db -> getOne( "SELECT id FROM {$sqlname}modcatalog_zayavka WHERE did = '$didd' and identity = '$identity'" );

									//если заявки еще нет, то создаем
									if ( (int)$idz == 0) {

										//последний номер заявки
										$number = $db -> getOne( "SELECT max(number + 1) as number FROM {$sqlname}modcatalog_zayavka WHERE identity = '$identity'" );

										$db -> query( "INSERT INTO {$sqlname}modcatalog_zayavka SET ?u", [
											"number"   => $number,
											"did"      => $didd,
											"datum"    => current_datumtime(),
											"iduser"   => $iduser1,
											"identity" => $identity
										] );

										$idz = $db -> insertId();
										$zay++;

										//отправитм уведомление
										$this -> eNotify( $idz, 'new' );

									}
									else {//если уже есть, то добавляем позицию

										//проверим наличие такой позиции в резерве и его кол-во
										$countz = $db -> getOne( "SELECT SUM(kol) as count FROM {$sqlname}modcatalog_zayavkapoz WHERE idz = '".$idz."' and prid = '".$data['prid']."' and identity = '$identity'" );

										if ( $countz < 1 ) {//если такой позиции еще нет

											$idzp = $db -> getOne( "SELECT id FROM {$sqlname}modcatalog_zayavkapoz WHERE idz = '$idz' and prid = '".$data['prid']."'  and identity = '$identity'" );

											if ( $idzp < 1 ) {

												//добавим позицию в заявку
												$db -> query( "INSERT INTO {$sqlname}modcatalog_zayavkapoz SET ?u", [
													"idz"      => $idz,
													"prid"     => $data['prid'],
													"kol"      => abs( $delta ),
													"identity" => $identity
												] );

												$zaypoz += abs( $delta );

											}

										}

									}

								}

							}
							elseif ( $kol_reserv > $data['kol'] ) {

								//обновим резерв по позиции
								$db -> query( "update {$sqlname}modcatalog_reserv set kol = '".$data['kol']."' WHERE prid = '".$data['prid']."' and did = '$didd' and identity = '$identity'" );

							}

							//проверяем количества в резерве
							$kolRez = $db -> getOne( "SELECT kol FROM {$sqlname}modcatalog_reserv WHERE prid = '".$data['prid']."' and did = '$didd' and identity = '$identity' " );

							//найдем заявку по этой сделке
							$idz = $db -> getOne( "SELECT id FROM {$sqlname}modcatalog_zayavka WHERE status != '2' and did = '$didd' and identity = '$identity' " );

							//узнаем количество по спецификации
							$kolSpeca = $data['kol'];

							//проверяем количества в заявках
							$re     = $db -> getRow( "SELECT id,kol FROM {$sqlname}modcatalog_zayavkapoz WHERE prid = '".$data['prid']."' and idz = '".$idz."' and identity = '$identity' " );
							$kolZay = $re['kol'];
							$idpz   = $re['id'];

							//сколько д.б. в заявках с учетом резерва
							$kolNeed = $kolSpeca - $kolRez;

							//делаем, если в заявках есть такие позиции
							//если в заявках больше, чем надо
							if ( ($kolZay > 0) && $kolZay > $kolNeed ) {

								if ( $kolNeed > 0 ) {

									$db -> query( "update {$sqlname}modcatalog_zayavkapoz set kol = '$kolNeed' WHERE id = '".$idpz."' and identity = '$identity'" );

								}
								else {

									$db -> query( "delete from {$sqlname}modcatalog_zayavkapoz WHERE id = '".$idpz."' and identity = '$identity'" );

								}

								$updz++;

							}

						}

					}

				}

			}

		}

		if ( $print == 'yes' ) {

			$rezz .= '<DIV class="zagolovok">Результат обработки</DIV>';
			$rezz .= "<div>Поставлено в резерв <b>".$good."</b> позиций.<br>Создано <b>".$zay."</b> заявок на <b>".$zaypoz."</b> позиций.</div>";
			$rezz .= "<div>Удалены позиции из заявок - <b>".$updz."</b> позиций (они есть в резерве).</div>";
			$rezz .= '<hr><div class="text-right"><A href="#" onClick="DClose()" class="button">Закрыть</A></div>';

		}
		else $rezz = true;

		return $rezz;

	}

	/**
	 * Вспомогательная функция. Считает сколько позиций сделки закрыто заявками
	 *
	 * @param        $did
	 * @param        $params - identity, iduser1,
	 * @return array
	 */
	public function CompleteStatus($did, array $params = []): array {

		$sqlname  = $this -> sqlname;
		$identity = ($params['identity'] > 0) ? $params['identity'] : $this -> identity;
		$db       = $this -> db;

		$kolAll    = 0;
		$kolResAll = 0;
		$kolZakAll = 0;

		if ( $did > 0 ) {

			//общее количество позиций по спеке
			$kolAll = $db -> getOne( "SELECT SUM(kol) as kol FROM {$sqlname}speca WHERE did = '$did' and prid > 0 and identity = '$identity'" );

			$result = $db -> getAll( "SELECT * FROM {$sqlname}speca WHERE did = '$did' and prid > 0 and identity = '$identity'" );
			foreach ( $result as $data ) {

				//Зарезервировано под сделку
				$kolResAll += $db -> getOne( "SELECT SUM(kol) as kol FROM {$sqlname}modcatalog_reserv WHERE prid = '".$data['prid']."' and did = '$did' and identity = '$identity'" );

				//смотрим, сколько уже заказано под эту сделку
				$q = "
					SELECT
						SUM({$sqlname}modcatalog_zayavkapoz.kol) as kol
					FROM {$sqlname}modcatalog_zayavkapoz
					LEFT JOIN {$sqlname}modcatalog_zayavka ON {$sqlname}modcatalog_zayavka.id = {$sqlname}modcatalog_zayavkapoz.idz
					WHERE
						{$sqlname}modcatalog_zayavkapoz.prid = '".$data['prid']."' and
						{$sqlname}modcatalog_zayavka.did = '$did' and
						{$sqlname}modcatalog_zayavkapoz.identity = '$identity'
					";

				$kolZakAll += $db -> getOne( $q );

			}

		}

		$delta = $kolAll - $kolResAll - $kolZakAll;

		return [
			"kolAll"    => $kolAll,
			"kolResAll" => $kolResAll,
			"kolZakAll" => $kolZakAll,
			"delta"     => $delta
		];

	}

	/**
	 * Отправка уведомлений о заявке
	 *
	 * @param       $id
	 * @param       $tip
	 * @param array $params
	 * @return array
	 */
	public function eNotify($id, $tip, array $params = []): array {

		$sqlname  = $this -> sqlname;
		$iduser1  = ($params['iduser'] > 0) ? $params['iduser'] : $this -> iduser1;
		$identity = ($params['identity'] > 0) ? $params['identity'] : $this -> identity;
		$db       = $this -> db;

		$settings = self ::settings( $identity );

		$status = [
			'0' => 'Создана',
			'1' => 'В работе',
			'2' => 'Выполнена',
			'3' => 'Отменена'
		];

		$mailer   = [];
		$users    = [];
		$response = [];

		$z = $db -> getRow( "SELECT * FROM {$sqlname}modcatalog_zayavka WHERE id = '$id' and identity = '$identity'" );

		$from               = $db -> getRow( "SELECT email, title FROM {$sqlname}user WHERE iduser = '$z[iduser]' and identity = '$identity'" );
		$mailer['from']     = $from['email'];
		$mailer['fromname'] = $from['title'];

		switch ($tip) {

			case "new":

				$u = [];

				$r = $db -> query( "SELECT iduser, email, title FROM {$sqlname}user WHERE (iduser IN (".implode( ",", $settings['mcCoordinator'] ).") and iduser != '$z[iduser]') and identity = '$identity'" );
				while ($user = $db -> fetch( $r )) {

					$users[] = $user['iduser'];

					$u[] = [
						"name"  => $user['title'],
						"email" => $user['email']
					];

				}

				$mailer['to']     = $u[0]['email'];
				$mailer['toname'] = $u[0]['name'];

				unset( $u[0] );
				$u = array_values( $u );

				$mailer['theme'] = 'Склад. Новая заявка №'.$z['number'];
				$mailer['html']  = 'Размещена новая заявка №'.$z['number'].'.<br>Автор заявки: '.$from['title'].'<hr>CRM / Модуль Склад';
				$mailer['files'] = [];
				$mailer['cc']    = $u;

			break;
			case "status":

				$users[] = $z['iduser'];

				$user = $db -> getRow( "SELECT email, title FROM {$sqlname}user WHERE iduser = '$z[iduser]' and identity = '$identity'" );

				$mailer['to']     = $user['email'];
				$mailer['toname'] = $user['title'];
				$mailer['theme']  = 'Склад. Изменен статус заявки №'.$z['number'];
				$mailer['html']   = 'Статус заявки №'.$z['number'].' - '.strtr( $z['status'], $status ).'.<br>Автор заявки: '.$user['title'].'<hr>CRM / Модуль Склад';
				$mailer['files']  = [];
				$mailer['cc']     = [];

			break;

		}

		$users = array_unique( $users );

		$arg        = [
			"tip"     => "catalog",
			"title"   => $mailer['theme'],
			"content" => $mailer['html'],
			"users"   => $users,
			"notice"  => 'yes'
		];
		$response[] = Notify ::fire( "sklad", $iduser1, $arg );

		//$response = mailer( $mailer['to'], $mailer['toname'], $mailer['from'], $mailer['fromname'], $mailer['theme'], $mailer['html'], $mailer['files'], $mailer['cc'] );

		return $response;

	}

	/**
	 * Логгирование измененных данных
	 *
	 * @param       $tip
	 * @param int   $identity
	 * @param int   $iduser
	 * @param array $param
	 * @param array $oldparams
	 * @return string
	 */
	private function logger($tip, int $identity = 1, int $iduser = 0, array $param = [], array $oldparams = []): string {

		$sqlname  = $this -> sqlname;
		$iduser  = ($iduser > 0) ? $iduser : $this -> iduser1;
		$db       = $this -> db;

		$old = (!empty( $oldparams )) ? $oldparams : [];
		$new = (!empty( $param )) ? $param : [];

		$diff = array_diff_ext( $param, $oldparams );

		$logerror = '';

		$param['dopzid'] = $param['dopzid'] ?? "0";

		if ( !empty( $diff )) {

			try {

				$db -> query( "INSERT INTO {$sqlname}modcatalog_log SET ?u", [
					"tip"      => $tip,
					"dopzid"   => $param['dopzid'],
					"prid"     => $param['prid'],
					"datum"    => current_datumtime(),
					"new"      => json_encode_cyr( $new ),
					"old"      => json_encode_cyr( $old ),
					"iduser"   => $iduser,
					"identity" => $identity
				] );

			}
			catch ( Exception $e ) {

				$logerror = 'Ошибка'.$e -> getMessage().' в строке '.$e -> getCode();

			}

		}
		else $logerror = 'Не обнаружено изменений<br>';

		return $logerror;

	}

	/**
	 * Вспомогательная функция. Загрузка картинок на ftp-сервер
	 *
	 * @param int   $prid
	 * @param array $files
	 * @return array
	 * @throws Exception
	 */
	public function FtpUpload(int $prid = 0, array $files = []): array {

		$rootpath = $this -> rootpath;
		$sqlname  = $this -> sqlname;
		$db       = $this -> db;

		$result   = 'error';
		$ftperror = $message = [];

		$identity = $db -> getOne( "SELECT identity FROM {$sqlname}modcatalog WHERE prid = '$prid'" );

		if ( $prid > 0 && $identity > 0 ) {

			$fpath = (file_exists( $rootpath.'/cash/'.$identity )) ? $identity."/" : "";

			$ftpsettings = $db -> getOne( "SELECT ftp FROM {$sqlname}modcatalog_set WHERE identity = '$identity'" );

			$ftpparams = json_decode( $ftpsettings, true );

			if ( $ftpparams['mcFtpServer'] != '' ) {

				$dpath = $ftpparams['mcFtpPath'];
				$spath = $rootpath.'/files/'.$fpath.'modcatalog';

				try {

					$ftp = new FtpClient;
					$ftp -> connect( $ftpparams['mcFtpServer'] );
					$ftp -> login( $ftpparams['mcFtpUser'], $ftpparams['mcFtpPass'] );

					foreach ($files as $file) {

						if ( $ftp -> put( $dpath."/".$file['file'], $spath."/".$file['file'], FTP_BINARY ) )
							$message[] = "Файл ".$file['file']." загружен<br>";

					}

					$ftp->close();

				}
				catch ( FtpException $e ) {

					$ftperror[] = 'Error: '.$e -> getMessage();

				}

				/*
				$ftp = ftp_connect ( $ftpparams['mcFtpServer'] );
				$login = ftp_login($ftp, $ftpparams['mcFtpUser'], $ftpparams['mcFtpPass']);

				if ($login){

					foreach ($files as $file) {

						if ( ftp_put( $ftp, $dpath."/".$file['file'], $spath."/".$file['file'], FTP_BINARY ) ) {

							print "Файл ".$file['file']." загружен<br>";

						}

					}

				}
				else{

					$ftperror[] = 'Ошибка авторизации на FTP';

				}

				ftp_close($ftp);
				*/

				if ( empty( $ftperror ) )
					$result = 'ok';

			}
			else {

				//если сервер не настроен, то просто возвращаем OK
				$result = 'ok';

			}

		}

		return [
			"result" => $result,
			"message" => $message,
			"error"  => $ftperror
		];

	}

	/**
	 * Автоматическое резервирование и создание заявок при смене этапа сделки
	 * инициируется в классе Deal (func.php)
	 *
	 * @param string $print
	 * @param int    $did
	 * @return string
	 */
	public function mcSyncReserv(string $print = 'no', int $did = 0): string {

		$sqlname  = $this -> sqlname;
		$identity = $this -> identity;
		$db       = $this -> db;

		$rezz     = '';

		$settings            = $db -> getOne( "SELECT settings FROM {$sqlname}modcatalog_set WHERE identity = '$identity'" );
		$settings            = json_decode( $settings, true );
		$settings['mcSklad'] = 'yes';

		$good      = $zay = $zaypoz = $updz = 0;
		$doZayavka = $err = [];

		//если действие не по конкретной сделке
		if ( $did < 1 ) {

			//пройдемся по договорам, которые удовлетворяют условиям по этапу
			$dids = $db -> getCol( "SELECT did FROM {$sqlname}dogovor WHERE did > 0 and idcategory IN (SELECT idcategory FROM {$sqlname}dogcategory WHERE title >= '".$settings['mcStepPers']."' and identity = '$identity') and close != 'yes' and identity = '$identity'" );

		}
		else $dids[] = $did;

		foreach ( $dids as $didd ) {

			$resc      = $db -> getAll( "SELECT iduser, close, kol_fact FROM {$sqlname}dogovor WHERE did = '$didd' and identity = '$identity'" );
			$iduser    = $resc["iduser"];
			$dclose    = $resc["close"];
			$dkol_fact = $resc["kol_fact"];

			//если сделка закрыта с проигрышем, то удаляем резерв
			if ( $dclose == 'yes' && $dkol_fact == 0 ) {

				//проходим резервы по сделке
				$res = $db -> getAll( "SELECT * FROM {$sqlname}modcatalog_reserv WHERE did = '$didd' and identity = '$identity'" );
				foreach ( $res as $data ) {

					//получаем склад
					//$sklad = $db -> getOne("SELECT * FROM {$sqlname}modcatalog_zayavka WHERE id = '".$data['idz']."' and identity = '$identity'");
					$sklad = $data['idz'];

					//текущее кол-во товара на указанном складе складе
					//$kol_sclad = $db -> getOne("SELECT kol FROM {$sqlname}modcatalog_skladpoz WHERE sklad = '$sklad' and prid = '".$data['prid']."' and identity = '$identity'");

					//количество резерва на складе
					//$kol_reserv = $db -> getOne("SELECT kol FROM {$sqlname}modcatalog_reserv WHERE prid = '".$data['prid']."' and did = '".$dids[$i]."' and sklad = '$sklad' and identity = '$identity'");

					//$newkol_sclad = floatval($kol_sclad) + floatval($kol_reserv);

					$db -> query( "delete from {$sqlname}modcatalog_reserv WHERE prid = '".$data['prid']."' and did = '$didd' and sklad = '$sklad' and identity = '$identity'" );

					//$db -> query("update {$sqlname}modcatalog set kol = '".$newkol_sclad."' WHERE prid = '".$data['prid']."' and identity = '$identity'");

				}

			}
			//если сделка открыта
			elseif ( $dclose != 'yes' ) {

				$ida = $db -> getOne( "SELECT id FROM {$sqlname}modcatalog_akt WHERE did = '$didd' and tip = 'outcome' and identity = '$identity'" );

				if ( $ida < 1 ) {//если сделки нет в реализации

					$res = $db -> getAll( "SELECT * FROM {$sqlname}speca WHERE did = '$didd' and identity = '$identity'" );
					foreach ( $res as $data ) {

						if ( $data['prid'] > 0 ) {//только прайсовые позиции

							//проверяем наличие позиции на складе
							$kol_sclad = $db -> getOne( "SELECT kol FROM {$sqlname}modcatalog WHERE prid = '".$data['prid']."' and identity = '$identity'" );

							//останется на складе после резерва
							$delta = $kol_sclad - $data['kol'];

							//проверяем наличие позиции в резерве по этой сделке
							$kol_reserv = $db -> getOne( "SELECT kol FROM {$sqlname}modcatalog_reserv WHERE prid = '".$data['prid']."' and did = '$didd' and identity = '$identity'" );

							//если такой позиции еще нет в резерве, то резервируем
							if ( $kol_reserv < 1 ) {

								if ( $delta >= 0 && $kol_sclad > 0 ) {//если на складе есть нужное нам кол-во, то резервируем его

									$kolRez = $data['kol'];

									$db -> query( "insert into {$sqlname}modcatalog_reserv (id,did,prid,kol,status,identity) values(null,'$didd','".$data['prid']."','".$data['kol']."','reserved','$identity')" );
									$idr  = $db -> insertId();
									$good += $data['kol'];

									//$kol_s = $delta;
									//if($delta == 0) $st = ", status = '0'";

									//$db -> query("update {$sqlname}modcatalog set kol = '".$kol_s."' ".$st." WHERE prid = '".$data['prid']."' and identity = '$identity'");

								}
								if ( $delta < 0 ) {

									//если на складе меньше позиций чем надо, то резервируем сколько есть + создаем заявку на оставшееся кол-во
									if ( $kol_sclad > 0 ) {

										$kolRez = $kol_sclad;

										$db -> query( "insert into {$sqlname}modcatalog_reserv (id,did,prid,kol,status,identity) values(null,'$didd','".$data['prid']."','".$kol_sclad."','reserved','$identity')" );

										$idr  = $db -> insertId();
										$good += $kol_sclad;

										$delta = $data['kol'] - $kol_sclad;//определяем дельту как разницу между необходимым и тем, что зарезервировали

										//mysql_query("update {$sqlname}modcatalog set kol = '0', status = '0' WHERE prid = '".$data['prid']."' and identity = '$identity'");

									}

									//проверяем наличие заявки
									$idz = $db -> getOne( "SELECT id FROM {$sqlname}modcatalog_zayavka WHERE did = '$didd' and identity = '$identity'" );

									//если заявки еще нет, то создаем
									if ( $idz < 1 ) {

										//последний номер заявки
										$number = $db -> getOne( "SELECT max(number + 0) as number FROM {$sqlname}modcatalog_zayavka WHERE did = '$didd' and identity = '$identity'" );

										$number++;

										$db -> query( "insert into {$sqlname}modcatalog_zayavka (id,number, did,datum,iduser,identity) values(null,'$number', '$didd','".current_datumtime()."','".$iduser."','$identity')" );
										$idz = $db -> insertId();
										$zay++;

									}
									if ( $idz > 0 ) {//если уже есть, то добавляем позицию

										//проверим наличие такой позиции в резерве и его кол-во
										$countz = $db -> getOne( "SELECT SUM(kol) as count FROM {$sqlname}modcatalog_zayavkapoz WHERE idz = '".$idz."' and prid = '".$data['prid']."' and identity = '$identity'" );

										if ( $countz < 1 ) {//если такой позиции еще нет

											$idzp = $db -> getOne( "SELECT id FROM {$sqlname}modcatalog_zayavkapoz WHERE idz = '$idz' and prid = '".$data['prid']."' and did = '$didd' and identity = '$identity'" );

											if ( $idzp < 1 ) {

												//добавим позицию в заявку
												$db -> query( "insert into {$sqlname}modcatalog_zayavkapoz (id,idz,prid,kol,identity) values(null,'".$idz."','".$data['prid']."','".abs( $delta )."','$identity')" );

												$zaypoz += abs( $delta );

											}

										}

									}

									$kol_s = 0;

								}

								//mcCheckStatus($data['prid']);
							}
							else {

								if ( $kol_reserv > $data['kol'] ) {

									$delta     = $kol_reserv - $data['kol'];//считаем лишнее в резерве
									$newReserv = $data['kol'];

									//обновим резерв по позиции
									$db -> query( "update {$sqlname}modcatalog_reserv set kol = '".$newReserv."' WHERE prid = '".$data['prid']."' and did = '$didd' and identity = '$identity'" );

									//добавим освободившиеся позиции в каталог
									//$newkol_sclad = $kol_sclad + $delta;

									//$db -> query("update {$sqlname}modcatalog set kol = '".$newkol_sclad."' WHERE prid = '".$data['prid']."' and identity = '$identity'");

								}

							}

							//проверяем количества в резерве
							$kolRez = $db -> getOne( "SELECT kol FROM {$sqlname}modcatalog_reserv WHERE prid = '".$data['prid']."' and did = '$didd' and identity = '$identity' " );

							//найдем заявку по этой сделке
							$idz = $db -> getOne( "SELECT id FROM {$sqlname}modcatalog_zayavka WHERE status != '2' and did = '$didd' and identity = '$identity' " );

							//узнаем количество по спецификации
							$kolSpeca = $data['kol'];

							//проверяем количества в заявках
							$re     = $db -> getRow( "SELECT id,kol FROM {$sqlname}modcatalog_zayavkapoz WHERE prid = '".$data['prid']."' and idz = '".$idz."' and identity = '$identity' " );
							$kolZay = $re['kol'];
							$idpz   = $re['id'];

							//сколько д.б. в заявках с учетом резерва
							$kolNeed = $kolSpeca - $kolRez;

							//print $dids[$i].": prid = ".$data['prid'].". Надо - ".$kolNeed." : Спека - ".$kolSpeca." : Резерв - ".$kolRez." : Заявки - ".$kolZay."<br>";

							//делаем, если в заявках есть такие позиции
							if ( $kolZay > 0 ) {

								//если в заявках больше, чем надо
								if ( $kolZay > $kolNeed ) {

									if ( $kolNeed > 0 )
										$db -> query( "update {$sqlname}modcatalog_zayavkapoz set kol = '$kolNeed' WHERE id = '".$idpz."' and identity = '$identity'" );
									else {

										$db -> query( "delete from {$sqlname}modcatalog_zayavkapoz WHERE id = '".$idpz."' and identity = '$identity'" );

									}

									$updz++;

								}
							}

						}
					}
				}

			}

		}

		if ( $print == 'yes' ) {

			$rezz .= '<DIV class="zagolovok">Результат обработки</DIV>';
			$rezz .= "<div>Поставлено в резерв <b>".$good."</b> позиций.<br>Создано <b>".$zay."</b> заявок на <b>".$zaypoz."</b> позиций.</div>";
			$rezz .= "<div>Удалены позиции из заявок - <b>".$updz."</b> позиций (они есть в резерве).</div>";
			$rezz .= '<hr><div class="button--pane text-right"><A href="#" onClick="DClose()" class="button">Закрыть</A></div>';

		}

		return $rezz;

	}

	/**
	 * Обработка поступлений на склад и резервирование как по ордеру, так и авторезервирование
	 *
	 * @param string $print
	 * @param array  $params
	 *      ida   - id ордера,
	 *      did   - id сделки,
	 *      prid  - id позиции,
	 *      idz   - id заявки,
	 *      sklad - id склада (на перспективу, для выбора во время смены этапа)
	 * @return string
	 */
	public function mcSyncPoz(string $print = 'no', array $params = []): string {

		$sqlname  = $this -> sqlname;
		$iduser1  = ($params['iduser'] > 0) ? $params['iduser'] : $this -> iduser1;
		$identity = ($params['identity'] > 0) ? $params['identity'] : $this -> identity;
		$db       = $this -> db;

		$settings            = $db -> getOne( "SELECT settings FROM {$sqlname}modcatalog_set WHERE identity = '$identity'" );
		$settings            = json_decode( $settings, true );
		$settings['mcSklad'] = 'yes';

		if ( $settings['mcSkladPoz'] != "yes" )
			$pozzi = " and status != 'out'";

		$rezz = '';

		$good  = 0;
		$upd   = 0;
		$zay   = 0;
		$err   = [];
		$sklad = 0;

		//склад, на который делаем приход
		if ( (int)$params['ida'] > 0 ) {

			$sklad = $db -> getOne( "SELECT sklad FROM {$sqlname}modcatalog_akt WHERE id = '$params[ida]' and identity = '$identity'" );

			//если обрабатывается акт по сделке, то позиции ставим в резерв
			//принимаем параметры ida, did
			if ( (int)$params['did'] > 0 ) {

				$data = $db -> getRow( "SELECT * FROM {$sqlname}modcatalog_aktpoz WHERE prid = '".$params['prid']."' and ida = '".$params['ida']."' and identity = '$identity'" );

				//$db -> query( "INSERT INTO {$sqlname}modcatalog_reserv (id, prid, sklad, status, datum, kol, did, ida, idz, identity) VALUES (NULL, '".$params['prid']."', '".$sklad."', 'reserved', '".current_datumtime()."', '".$data['kol']."', '".$params['did']."', '".$params['ida']."', '".$params['idz']."', '".$identity."')" );

				$db -> query( "INSERT INTO {$sqlname}modcatalog_reserv SET ?u", [
					'prid'     => $params['prid'],
					'sklad'    => $sklad,
					'status'   => 'reserved',
					'datum'    => current_datumtime(),
					'kol'      => $data['kol'],
					'did'      => $params['did'],
					'ida'      => $params['ida'],
					'idz'      => $params['idz'],
					'identity' => $identity
				] );

				$good++;

			}

			//если обрабатывается акт не по сделке, то делаем резервирование по всем сделкам, имеющим такие позиции в не проведенных заявках
			//принимаем параметры ida
			else {

				//Количество позиции по ордеру
				$kolOrder = $db -> getOne( "SELECT kol FROM {$sqlname}modcatalog_aktpoz where prid = '".$params['prid']."' and ida = '".$params['ida']."' and identity = '$identity'" );

				//компания к которой прикреплен склад, чтобы отфильтровать сделки
				$mcid = (int)$db -> getOne( "SELECT mcid FROM {$sqlname}modcatalog_sklad WHERE id = '$sklad' and  identity = '$identity' ORDER BY title" );

				$kolOstatok = $kolOrder;

				if ( $mcid > 0 ) {

					//запрос выделяет сделки, по которым есть не проведенные заявки
					$q = "
					SELECT
						{$sqlname}speca.prid,
						{$sqlname}speca.kol,
						{$sqlname}speca.did,
						{$sqlname}dogovor.mcid
					FROM {$sqlname}speca
						LEFT JOIN {$sqlname}dogovor ON {$sqlname}dogovor.did = {$sqlname}speca.did
						LEFT JOIN {$sqlname}dogcategory ON {$sqlname}dogovor.idcategory = {$sqlname}dogcategory.idcategory
					WHERE
						{$sqlname}speca.prid = '".$params['prid']."' and
						{$sqlname}dogovor.mcid = '$mcid' and
						{$sqlname}dogcategory.title >= ".$settings['mcStepPers']." and
						{$sqlname}speca.identity = '$identity'
						GROUP BY {$sqlname}dogovor.did
					";

					$res = $db -> getAll( $q );
					foreach ( $res as $data ) {

						//проверим позиции в заявках со статусом 0 (создана), 1 (в работе), и если они там есть работаем с ними
						//ставим позиции в резерв и уменьшаем количество в заявках
						//цель: не резервировать позиции в сделках, по которым уже есть заявки
						$q1 = "
						SELECT
							did
						FROM {$sqlname}modcatalog_zayavkapoz
							LEFT JOIN {$sqlname}modcatalog_zayavka ON {$sqlname}modcatalog_zayavka.id = {$sqlname}modcatalog_zayavkapoz.idz
						WHERE
							{$sqlname}modcatalog_zayavkapoz.prid = '".$params['prid']."' and
							{$sqlname}modcatalog_zayavka.status IN (0, 1) and
							{$sqlname}modcatalog_zayavkapoz.identity = '$identity'
						";

						//сделки, подходящие под условия:
						$dids = $db -> getCol( $q1 );

						if ( $kolOstatok > 0 && !in_array( $data['did'], (array)$dids ) ) {

							//вычисляем разницу м/у количеством в сделке и в приходе
							//если значение отрицательное, то значит позиций пришло больше, чем в сделке и будем делать резерв на всё количество
							if ( $kolOstatok >= $data['kol'] )
								$kol = $data['kol'];
							else
								$kol = $data['kol'] - $kolOstatok;

							$kolOstatok -= $kol;

							//$db -> query( "INSERT INTO {$sqlname}modcatalog_reserv (id, prid, sklad, status, datum, kol, did, identity) VALUES (NULL, '".$data['prid']."', '".$sklad."', 'reserved', '".current_datumtime()."', '".$data['kol']."', '".$params['did']."', '".$identity."')" );

							$db -> query( "INSERT INTO {$sqlname}modcatalog_reserv SET ?u", [
								'prid'     => $params['prid'],
								'sklad'    => $sklad,
								'status'   => 'reserved',
								'datum'    => current_datumtime(),
								'kol'      => $data['kol'],
								'did'      => $params['did'],
								'identity' => $identity
							] );

							$good++;

						}


					}

				}

			}

		}
		elseif ( (int)$params['ida'] == 0 ) {

			if ( $params['did'] > 0 ) {

				//если обрабатывается авторезерв по сделке без заявки, то резервируем нужное количество, либо создаем заявку
				//принимаем параметры did, sklad
				if ( $params['idz'] < 1 ) {

					//компания к которой прикреплен склад, чтобы отфильтровать сделки
					$deal = get_dog_info( $params['did'], "yes" );
					$mcid = $deal['mcid'];

					$list = [];

					//комплектность сделки
					$complete = $this -> mcCompleteStatus( $params['did'] );

					//авторезерв делаем строго при переходе сделки на заданный этап
					if ( $deal['idcategory'] == $settings['mcStep'] && $complete['delta'] > 0 ) {

						if ( $params['sklad'] < 1 )
							$sklad = $db -> getOne( "SELECT id FROM {$sqlname}modcatalog_sklad WHERE mcid = '$mcid' and isDefault = 'yes' and identity = '$identity'" );
						else $sklad = $params['sklad'];

						//если склад задан, то идем дальше, т.к. для резерва нужен склад
						if ( $sklad > 0 ) {

							$speca = $db -> getAll( "SELECT * FROM {$sqlname}speca WHERE did = '$params[did]' and identity = '$identity'" );
							foreach ( $speca as $data ) {

								//количество на складе
								$kolSklad = (float)$db -> getOne( "SELECT SUM(kol) as kol FROM {$sqlname}modcatalog_skladpoz WHERE prid = '$data[prid]' and sklad = '$sklad' and identity = '$identity'" );

								//количество уже зарезервированных под эту сделку
								$kolReserve = (float)$db -> getOne( "SELECT SUM(kol) as kol FROM {$sqlname}modcatalog_reserv WHERE prid = '$data[prid]' and did = '$params[did]' and sklad = '$sklad' and identity = '$identity'" );

								//количество уже зарезервированных под другие сделки
								$kolReserveOther = (float)$db -> getOne( "SELECT SUM(kol) as kol FROM {$sqlname}modcatalog_reserv WHERE prid = '$data[prid]' and did != '$params[did]' and sklad = '$sklad' and identity = '$identity'" );

								//что осталось зарезервировать
								$kolToReserve = $kolSklad - $kolReserveOther - $kolReserve;

								//если есть, что резервировать
								if ( $kolToReserve > 0 ) {

									//$db -> query( "INSERT INTO {$sqlname}modcatalog_reserv (id, prid, sklad, status, datum, kol, did, identity) VALUES (NULL, '".$data['prid']."', '".$sklad."', 'reserved', '".current_datumtime()."', '".$data['kol']."', '".$params['did']."', '".$identity."')" );

									$db -> query( "INSERT INTO {$sqlname}modcatalog_reserv SET ?u", [
										'prid'     => $params['prid'],
										'sklad'    => $sklad,
										'status'   => 'reserved',
										'datum'    => current_datumtime(),
										'kol'      => $data['kol'],
										'did'      => $params['did'],
										'identity' => $identity
									] );

									$good++;

								}
								//если нет, то собираем массив для создания заявки
								else {

									$list[] = [
										"prid" => $data['prid'],
										"kol"  => $data['kol'],
									];

								}

							}

							//создаем заявку
							if ( !empty( $list )) {

								$number = $db -> getOne( "SELECT max(number + 0) FROM `{$sqlname}modcatalog_zayavka` WHERE identity = '".$identity."'" );
								$number++;

								$content = 'Заявка размещена автоматически в связи со сменой этапа сделки';

								//$db -> query( "insert into {$sqlname}modcatalog_zayavka (id,number,did,datum,iduser,content,identity) values (null, '$number', '".$params['did']."', '".current_datumtime()."', '".$iduser1."', '".$content."', '$identity')" );

								$db -> query( "INSERT INTO {$sqlname}modcatalog_zayavka SET ?u", [
									'number'   => $number,
									'did'      => $params['did'],
									'datum'    => current_datumtime(),
									'iduser'   => $iduser1,
									'content'  => $content,
									'identity' => $identity
								] );

								$zay++;

								$id = $db -> insertId();

								foreach ( $list as $da ) {

									//$db -> query( "insert into {$sqlname}modcatalog_zayavkapoz (id,idz,prid,kol,identity) values (null, '".$id."', '".$da['prid']."','".$da['kol']."','$identity')" );

									$db -> query( "INSERT INTO {$sqlname}modcatalog_zayavkapoz SET ?u", [
										'idz'      => $id,
										'prid'     => $da['prid'],
										'kol'      => $da['kol'],
										'identity' => $identity
									] );

								}

							}

						}

					}

				}

				//если обрабатывается авторезерв по заявке, то резервируем нужное количество, либо создаем заявку
				//принимаем параметры did, sklad
				elseif ( $params['idz'] > 0 ) {

					//в этом случае создавать новые заявки не надо!!!!!!!!

					//склад берем из заявки
					$sklad = $db -> getOne( "SELECT id FROM {$sqlname}modcatalog_zayavka WHERE id = '".$params['idz']."' and identity = '$identity'" );

					//если склад задан, то идем дальше, т.к. для резерва нужен склад
					if ( $sklad > 0 ) {

						$speca = $db -> getAll( "SELECT * FROM {$sqlname}modcatalog_zayavkapoz WHERE idz = '".$params['idz']."' and identity = '$identity'" );
						foreach ( $speca as $data ) {

							//количество на складе
							$kolSklad = $db -> getOne( "SELECT SUM(kol) as kol FROM {$sqlname}modcatalog_skladpoz WHERE prid = '".$data['prid']."' and sklad = '".$sklad."' and identity = '$identity'" );

							//количество уже зарезервированных под эту сделку
							$kolReserve = $db -> getOne( "SELECT SUM(kol) as kol FROM {$sqlname}modcatalog_reserv WHERE prid = '".$data['prid']."' and did = '".$params['did']."' and sklad = '".$sklad."' and identity = '$identity'" );

							//количество уже зарезервированных под другие сделки
							$kolReserveOther = $db -> getOne( "SELECT SUM(kol) as kol FROM {$sqlname}modcatalog_reserv WHERE prid = '".$data['prid']."' and did != '".$params['did']."' and sklad = '".$sklad."' and identity = '$identity'" );

							//что осталось зарезервировать
							$kolToReserve = $kolSklad - $kolReserveOther - $kolReserve;

							//если есть, что резервировать
							if ( $kolToReserve > 0 ) {

								//$db -> query( "INSERT INTO {$sqlname}modcatalog_reserv (id, prid, sklad, status, datum, kol, did, identity) VALUES (NULL, '".$data['prid']."', '".$sklad."', 'reserved', '".current_datumtime()."', '".$data['kol']."', '".$params['did']."', '".$identity."')" );

								$db -> query( "INSERT INTO {$sqlname}modcatalog_reserv SET ?u", [
									'prid'     => $params['prid'],
									'sklad'    => $sklad,
									'status'   => 'reserved',
									'datum'    => current_datumtime(),
									'kol'      => $data['kol'],
									'did'      => $params['did'],
									'identity' => $identity
								] );

								$good++;

							}

						}

					}

				}

			}

			//если производтся синхронизация не по сделке, подходит для авторезерва
			//не принимаем параметры
			elseif ( (int)$params['did'] == 0 ) {

				//проходим все сделки не в заявках
				//$res = $db -> getAll("SELECT * FROM {$sqlname}dogovor where did NOT IN (SELECT did FROM {$sqlname}modcatalog_zayavka where id > 0 and did > 0 and identity = '$identity') and identity = '$identity'");

				//найдем все этапы сделок >= $settings['mcStep']
				$stepsGood = $db -> getCol( "SELECT idcategory FROM {$sqlname}dogcategory where CAST(title AS UNSIGNED) >= '".$settings['mcStep']."' and identity = '$identity'" );

				$res = $db -> getAll( "SELECT * FROM {$sqlname}dogovor where idcategory IN (".implode( ",", $stepsGood ).") and identity = '$identity'" );
				foreach ( $res as $da ) {

					//если сделка закрыта с факт.прибылью == 0, то удаляем резервы под ней
					if ( $da['close'] == "yes" && $da['kol_fact'] == 0 ) {

						$db -> query( "delete from {$sqlname}modcatalog_reserv WHERE did = '".$da['did']."' and identity = '$identity'" );

					}
					elseif ( $da['close'] != "yes" ) {

						if ( $settings['mcStep'] >= $da['idcategory'] ) {

							$sklad = (int)$db -> getOne( "SELECT id FROM {$sqlname}modcatalog_sklad WHERE mcid = '".$da['mcid']."' and isDefault = 'yes' and identity = '$identity'" );

							//если склад задан, то идем дальше, т.к. для резерва нужен склад
							if ( $sklad > 0 ) {

								$list = [];

								$speca = $db -> getAll( "SELECT * FROM {$sqlname}speca WHERE did = '".$da['did']."' and identity = '$identity'" );
								foreach ( $speca as $data ) {

									//количество на складе
									$kolSklad = $db -> getOne( "SELECT SUM(kol) as kol FROM {$sqlname}modcatalog_skladpoz WHERE prid = '".$data['prid']."' and sklad = '".$sklad."' and identity = '$identity'" );

									//количество уже зарезервированных под эту сделку
									$kolReserve = $db -> getOne( "SELECT SUM(kol) as kol FROM {$sqlname}modcatalog_reserv WHERE prid = '".$data['prid']."' and did = '".$da['did']."' and sklad = '".$sklad."' and identity = '$identity'" );

									//количество уже зарезервированных под другие сделки
									$kolReserveOther = $db -> getOne( "SELECT SUM(kol) as kol FROM {$sqlname}modcatalog_reserv WHERE prid = '".$data['prid']."' and did != '".$da['did']."' and sklad = '".$sklad."' and identity = '$identity'" );

									//что осталось зарезервировать
									$kolToReserve = $kolSklad - $kolReserveOther - $kolReserve;

									//если есть, что резервировать
									if ( $kolToReserve > 0 ) {

										//$db -> query( "INSERT INTO {$sqlname}modcatalog_reserv (id, prid, sklad, status, datum, kol, did, identity) VALUES (NULL, '".$data['prid']."', '".$sklad."', 'reserved', '".current_datumtime()."', '".$data['kol']."', '".$da['did']."', '".$identity."')" );

										$db -> query( "INSERT INTO {$sqlname}modcatalog_reserv SET ?u", [
											'prid'     => $data['prid'],
											'sklad'    => $sklad,
											'status'   => 'reserved',
											'datum'    => current_datumtime(),
											'kol'      => $data['kol'],
											'did'      => $da['did'],
											'identity' => $identity
										] );

										$good++;

									}
									//если нет, то собираем массив для создания заявки
									else {

										$list[] = [
											"prid" => $data['prid'],
											"kol"  => $data['kol'],
										];

									}

								}
								//создаем заявку
								if ( !empty( $list ) ) {

									$number = $db -> getOne( "SELECT max(number + 0) FROM `{$sqlname}modcatalog_zayavka` WHERE identity = '".$identity."'" );
									$number++;

									$content = 'Заявка размещена автоматически в связи со сменой этапа сделки';

									//$db -> query( "insert into {$sqlname}modcatalog_zayavka (id,number,did,datum,iduser,content,identity) values (null, '$number', '".$da['did']."', '".current_datumtime()."', '".$iduser1."', '".$content."', '$identity')" );

									$db -> query( "INSERT INTO {$sqlname}modcatalog_zayavka SET ?u", [
										'number'   => $number,
										'did'      => $da['did'],
										'datum'    => current_datumtime(),
										'iduser'   => $iduser1,
										'content'  => $content,
										'identity' => $identity
									] );

									$zay++;

									$id = $db -> insertId();

									foreach ( $list as $d ) {

										//$db -> query( "insert into {$sqlname}modcatalog_zayavkapoz (id,idz,prid,kol,identity) values (null, '".$id."', '".$d['prid']."','".$d['kol']."','$identity')" );

										$db -> query( "INSERT INTO {$sqlname}modcatalog_zayavkapoz SET ?u", [
											'idz'      => $id,
											'prid'     => $d['prid'],
											'kol'      => $d['kol'],
											'identity' => $identity
										] );

									}

								}

							}

						}

					}

				}

			}

		}

		if ( $print == 'yes' ) {

			$rezz .= '<DIV class="zagolovok">Результат обработки</DIV>';
			$rezz .= "Поставлено в резерв <b>".$good."</b> позиций.<br>Добавлено <b>".$zay."</b> заявок.";
			$rezz .= '<div class="text-right"><A href="#" onClick="DClose()" class="button">Отмена</A></div>';

			return $rezz;

		}

		return "Поставлено в резерв ".$good." позиций. <br>Добавлено ".$zay." заявок.";

	}

	/**
	 * Вспомогательная функция. Считает сколько позиций сделки закрыто заявками
	 *
	 * @param $did
	 * @return array
	 */
	public function mcCompleteStatus($did): array {

		$sqlname  = $this -> sqlname;
		$identity = $this -> identity;
		$db       = $this -> db;

		$kolAll    = 0;
		$kolResAll = 0;
		$kolZakAll = 0;

		$settings = $db -> getOne( "SELECT settings FROM {$sqlname}modcatalog_set WHERE identity = '$identity'" );
		$settings = json_decode( $settings, true );

		$apx = (!empty( $settings['mcPriceCat'] )) ? "{$sqlname}price.pr_cat IN (".implode( ",", $settings['mcPriceCat'] ).") and" : "";

		if ( $did > 0 ) {

			//общее количество позиций по спеке
			$kolAll = $db -> getOne( "
			SELECT
				SUM({$sqlname}speca.kol)
			FROM {$sqlname}speca
			LEFT JOIN {$sqlname}price ON {$sqlname}speca.prid = {$sqlname}price.n_id
			WHERE
				{$sqlname}speca.did = '$did' and
				{$sqlname}speca.prid > 0 and
				$apx
				{$sqlname}speca.identity = '$identity'
			" );

			$result = $db -> getAll( "SELECT * FROM {$sqlname}speca WHERE did = '$did' and prid > 0 and identity = '$identity'" );
			foreach ( $result as $data ) {

				//Зарезервировано под сделку
				$kolResAll += $db -> getOne( "SELECT SUM(kol) as kol FROM {$sqlname}modcatalog_reserv WHERE prid = '".$data['prid']."' and did = '$did' and identity = '$identity'" );

				//смотрим, сколько уже заказано под эту сделку
				$q = "
				SELECT
					SUM({$sqlname}modcatalog_zayavkapoz.kol) as kol
				FROM {$sqlname}modcatalog_zayavkapoz
					LEFT JOIN {$sqlname}modcatalog_zayavka ON {$sqlname}modcatalog_zayavka.id = {$sqlname}modcatalog_zayavkapoz.idz
				WHERE
					{$sqlname}modcatalog_zayavkapoz.prid = '".$data['prid']."' and
					{$sqlname}modcatalog_zayavka.did = '$did' and
					{$sqlname}modcatalog_zayavkapoz.identity = '$identity'
				";

				$kolZakAll += $db -> getOne( $q );

			}

		}

		$delta = $kolAll - $kolResAll - $kolZakAll;

		return [
			"kolAll"    => $kolAll,
			"kolResAll" => $kolResAll,
			"kolZakAll" => $kolZakAll,
			"delta"     => $delta
		];

	}

	/**
	 * Вспомогательная функция. Загрузка картинок на ftp-сервер
	 *
	 * @param int   $prid
	 * @param array $files
	 * @return string
	 * @throws Exception
	 */
	public function mcFtpUpload(int $prid = 0, array $files = []): string {

		$rootpath = dirname( __DIR__, 2 );

		require_once $rootpath."/inc/config.php";
		require_once $rootpath."/inc/dbconnector.php";
		require_once $rootpath."/inc/func.php";
		require_once $rootpath."/inc/settings.php";

		$identity = $GLOBALS['identity'];
		$fpath    = $GLOBALS['fpath'];
		$sqlname  = $GLOBALS['sqlname'];
		$db       = $GLOBALS['db'];

		$rez = '';

		if ( $prid > 0 ) {

			$err = [];

			$ftpsettings = $db -> getOne( "SELECT ftp FROM {$sqlname}modcatalog_set WHERE identity = '$identity'" );

			$ftpparams = json_decode( $ftpsettings, true );

			if ( $ftpparams['mcFtpServer'] != '' ) {

				$dpath = $ftpparams['mcFtpPath'];
				$spath = $rootpath."/files/".$fpath."modcatalog/";

				try {

					$ftp = new FtpClient;
					$ftp -> connect( $ftpparams['mcFtpServer'] );
					$ftp -> login( $ftpparams['mcFtpUser'], $ftpparams['mcFtpPass'] );

					foreach ($files as $file) {

						if ( $ftp -> put( $dpath."/".$file['file'], $spath."/".$file['file'], FTP_BINARY ) )
							print "Файл ".$file['file']." загружен<br>";

					}

					$ftp->close();

				}
				catch ( FtpException $e ) {

					$ftperror[] = 'Error: '.$e -> getMessage();

				}

				if ( $err )
					$rez = "Ошибка: ".implode( ", ", $err )."<br>";
				else $rez = 'Файлы успешно загружены на сервер FTP<br>';

			}
			else $rez = "Параметры FTP-сервера не заданы";

		}
		else $rez = "Не задана позиция";

		return $rez;

	}

}