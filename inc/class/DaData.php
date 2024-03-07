<?php
/* ============================ */
/*         DocCollector         */
/* ============================ */
/* (C) 2018 Vladislav Andreev   */
/*      SalesMan Project        */
/*        www.isaler.ru         */
/*        ver. 2018.x           */
/* ============================ */

namespace Salesman;

use SafeMySQL;
use stdClass;

/**
 * Класс для получения данных из Dadata
 * https://dadata.ru/api/find-party/
 *
 * @package Salesman
 */
class DaData {

	/**
	 * Различные параметры, в основном из GLOBALS
	 *
	 * @var mixed
	 */
	public $identity, $iduser1, $sqlname, $db, $fpath, $opts, $tmzone, $rootpath, $Language, $url;

	/**
	 * Передача различных параметров
	 *
	 * @var array
	 */
	public $params = [];

	/**
	 * Языковой массив
	 *
	 * @var array
	 */
	public $language = [];

	/**
	 * Настройки логики
	 *
	 * @var array
	 */
	public $settings = [];

	/**
	 * Расширенный ответ
	 *
	 * @var array
	 */
	public $response = [];

	/**
	 * Фильтры для списка чатов
	 *
	 * @var array
	 */
	public $filters = [];
	public $error;

	public $dadataKey, $dadataToken, $geourl;

	/**
	 * Работает только с объектом
	 * Подключает необходимые файлы, задает первоначальные параметры
	 * Chats constructor
	 */
	public function __construct() {

		$rootpath = dirname( __DIR__, 2 );

		require_once $rootpath."/inc/config.php";
		require_once $rootpath."/inc/dbconnector.php";
		require_once $rootpath."/inc/func.php";

		$params = $this -> params;

		$this -> rootpath = $rootpath;
		$this -> identity = $GLOBALS['identity'];
		$this -> sqlname  = $GLOBALS['sqlname'];
		$this -> fpath    = $GLOBALS['fpath'];
		$this -> opts     = $GLOBALS['opts'];
		$this -> tmzone   = $GLOBALS['tmzone'];

		$skey   = $GLOBALS['skey'];
		$ivc   = $GLOBALS['ivc'];

		$this -> url    = "https://suggestions.dadata.ru/suggestions/api/4_1/rs/findById/party";
		$this -> geourl = "https://cleaner.dadata.ru/api/v1/clean/address";

		$set = customSettings('dadata');

		$this -> dadataKey   = rij_decrypt($set['key'], $skey, $ivc);
		$this -> dadataToken = rij_decrypt($set['secret'], $skey, $ivc);

		$this -> db = new SafeMySQL($this -> opts);

		// тут почему-то не срабатывает
		if (!empty($params)) {
			foreach ($params as $key => $val) {
				$this ->{$key} = $val;
			}
		}

		date_default_timezone_set($this -> tmzone);

	}

	/**
	 * Получение реквизитов по ИНН, КПП
	 *
	 * @param string|null $inn
	 * @param string|null $kpp
	 * @return bool|object|stdClass
	 */
	public function getContragent(string $inn = NULL, string $kpp = NULL) {

		$dadataKey = $this -> dadataKey;

		$data   = [
			"query" => trim($inn),
			"kpp"   => trim($kpp)
		];
		$header = [
			"Authorization" => "Token $dadataKey"
		];

		$result = SendRequestCurl($this -> url, $data, $header);

		$this -> response = $result;

		return json_decode($result -> response, true);

	}

	/**
	 * Возвращает Гео-координаты адреса
	 * @param $address
	 * @return array
	 */
	public function getGeoByAddress($address): array {

		$dadataKey   = $this -> dadataKey;
		$dadataToken = $this -> dadataToken;

		$data   = [
			trim($address)
		];
		$header = [
			"Authorization" => "Token $dadataKey",
			"X-Secret"      => $dadataToken
		];

		$result = SendRequestCurl($this -> geourl, $data, $header);
		$res    = json_decode($result -> response, true);

		$this -> response = $result;

		//print_r($result);
		//print_r($res);

		if ($res[0]['city'] == '') {

			$res[0]['city'] = $res[0]['area'] == "" ? trim(str_replace("г", "", $res[0]['region_with_type'])) : $res[0]['area'];

		}

		return [
			"address"  => $res[0]['result'],
			"city"     => $res[0]['city'],
			"lat"      => $res[0]['geo_lat'],
			"lan"      => $res[0]['geo_lon'],
			"metro"    => $res[0]['metro'][0]['name'] != '' ? $res[0]['metro'][0]['name']." ( линия ".$res[0]['metro'][0]['line']." )" : null,
			"timezone" => substr($res[0]['timezone'], 3),
		];

	}

}