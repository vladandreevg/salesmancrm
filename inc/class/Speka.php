<?php
/* ============================ */
/*         SalesMan CRM         */
/* ============================ */
/* (C) 2016 Vladislav Andreev   */
/*       SalesMan Project       */
/*        www.isaler.ru         */
/*        ver. 2018.x           */
/* ============================ */

namespace Salesman;

use event;

/**
 * Класс для работы со спецификацией
 *
 * Class Speka
 *
 * @package     Salesman
 * @author      Vladislav Andreev <v@salesman.pro>
 * @version     1.0 (06/09/2019)
 */
class Speka {

	/**
	 * Абсолютный путь
	 *
	 * @var string
	 */
	public $rootpath;

	/**
	 * Различные параметры, в основном из GLOBALS
	 *
	 * @var mixed
	 */
	public $identity, $iduser1, $sqlname, $db, $fpath, $opts, $skey, $ivc, $tmzone;

	/**
	 * Передача различных параметров
	 *
	 * @var array
	 */
	public $params = [];

	/**
	 * Работает только с объектом
	 * Подключает необходимые файлы, задает первоначальные параметры
	 */
	public function __construct() {

		$rootpath = dirname(__DIR__, 2);

		require_once $rootpath."/inc/config.php";
		require_once $rootpath."/inc/dbconnector.php";
		require_once $rootpath."/inc/func.php";
		require_once $rootpath."/vendor/autoload.php";

		$params = $this -> params;

		$this -> rootpath = dirname(__DIR__, 2);
		$this -> identity = ( $params['identity'] > 0 ) ? $params['identity'] : $GLOBALS['identity'];
		$this -> iduser1  = $GLOBALS['iduser1'];
		$this -> sqlname  = $GLOBALS['sqlname'];
		$this -> db       = $GLOBALS['db'];
		$this -> fpath    = $GLOBALS['fpath'];
		$this -> opts     = $GLOBALS['opts'];
		$this -> skey     = ( $this -> skey != '' ) ? $this -> skey : $GLOBALS['skey'];
		$this -> ivc      = ( $this -> ivc != '' ) ? $this -> ivc : $GLOBALS['ivc'];
		$this -> tmzone   = $GLOBALS['tmzone'];

		// тут почему-то не срабатывает
		if (!empty($params)) {
			foreach ($params as $key => $val) {
				$this ->{$key} = $val;
			}
		}

		date_default_timezone_set($this -> tmzone);

	}

	/**
	 * Добавление/Изменение позиции спецификации
	 *
	 * @param       $id
	 * @param array $params - массив с параметрами
	 *                      - int id|spid - id записи спецификации
	 *                      - int did - id сделки
	 *                      - int prid|n_id - id позиции в прайсе
	 *                      - string artikul - артикул
	 *                      - string title - название позиции
	 *                      - int tip - тип позиции (0/1/2) - товар/услуга/материал
	 *                      - float price_in - закупочная цена
	 *                      - float price - цена продажи
	 *                      - string edizm - ед.измерения
	 *                      - float nds - ставка налога
	 *                      - float dop - доп.множитель
	 *                      - float kol - количество
	 *                      - string comments - комментарий для позиции
	 *                      - bool event (true|false) - отправлять событие или нет, false
	 *
	 * @return mixed
	 */
	public function edit($id, array $params = []): array {

		global $hooks;

		$rootpath = $this -> rootpath;

		$sqlname  = $this -> sqlname;
		$db       = $this -> db;
		$identity = $this -> identity;

		$iduser1 = ( $params['iduser1'] > 0 ) ? $params['iduser'] : $this -> iduser1;
		$other   = $GLOBALS['other'];
		$fpath   = $GLOBALS['fpath'];

		$otherSettings = json_decode(file_get_contents($rootpath."/cash/".$fpath."otherSettings.json"), true);

		$post = $params;

		$did     = (int)$params['did'];
		$artikul = $params['artikul'];
		$prid    = (int)$params['prid'];

		if (!isset($params['prid']) && isset($params['n_id'])) {
			$params['prid'] = (int)$params['n_id'];
		}

		$kol      = pre_format($params['kol']);
		$price    = pre_format($params['price']);
		$dop      = ( !$otherSettings['dop'] ) ? 1 : pre_format($params['dop']);
		$price_in = pre_format($params['price_in']);//это входная цена, пользователь может её определить вручную

		//print $params[ 'price_in' ]." : ".$price_in."\n";

		$title   = untag($params['title']);
		$tip     = $params['tip'];
		$edizm   = $params['edizm'];
		$nds     = $params['nds'] + 0;
		$comment = $params['comments'] ?? $params['comment'];

		$params['event'] = $params['event'] ?? false;

		if ($id == 0 && (int)$params['spid'] > 0) {
			$id = (int)$params['spid'];
		}

		//print_r($params);

		if ($id == 0) {
			$params = $hooks -> apply_filters("speka_addfilter", $params);
		}
		else {
			$params = $hooks -> apply_filters("speka_editfilter", $params);
		}

		$message = [];

		//поля, которые есть в таблице
		$allowed = [
			'prid',
			'did',
			'artikul',
			'title',
			'tip',
			'price_in',
			'price',
			'edizm',
			'kol',
			'nds',
			'dop',
			'comments',
			'identity'
		];

		$show_marga = $db -> getOne("SELECT show_marga FROM {$sqlname}user WHERE iduser = '$iduser1' and identity = '$identity'");

		//$params[ 'title' ] = ( !$params[ 'title' ] ) ? untag( $params[ 'title' ] ) : untag( $params[ 'title' ] );

		if ($params['title'] != '') {

			//если позиция прайсовая
			if ($prid > 0) {

				$result  = $db -> getRow("SELECT artikul, edizm, price_in FROM {$sqlname}price WHERE n_id = '$prid' and identity = '$identity'");
				$artikul = $result["artikul"];
				$edizm   = $result["edizm"];

				//если пользователю НЕ разрешена работа с маржой, то возьмем её из прайса
				if ($show_marga != 'yes') {
					$price_in = $result["price_in"];
				}

			}
			elseif ($artikul != '') {

				$prid = (int)$db -> getOne("SELECT n_id FROM {$sqlname}price WHERE artikul = '$artikul' and identity = '$identity'");

				if ($prid > 0) {

					$result = $db -> getRow("SELECT edizm, price_in FROM {$sqlname}price WHERE n_id = '$prid' and identity = '$identity'");
					$edizm  = $result["edizm"];

					//если пользователю НЕ разрешена работа с маржой, то возьмем её из прайса
					if ($show_marga != 'yes') {
						$price_in = $result["price_in"];
					}

				}

			}

			//новая позиция
			if ($id == 0) {

				$deal = getDogData($did, 'title');

				if ($did > 0 && $deal != '') {

					$arg = [
						'prid'     => (int)$prid,
						'did'      => $did,
						'artikul'  => $artikul,
						'title'    => $title,
						'tip'      => ( $tip != '' ) ? $tip : 0,
						'price'    => pre_format($price),
						'price_in' => pre_format($price_in),
						'kol'      => $kol,
						'edizm'    => $edizm,
						'nds'      => $nds,
						'dop'      => $dop,
						'comments' => ( $comment == '' ) ? " " : $comment,
						'identity' => $identity
					];

					//очищаем от мусора и случайных элементов
					$arg = $db -> filterArray($arg, $allowed);

					$db -> query("INSERT INTO {$sqlname}speca SET ?u", $arg);
					$id = $db -> insertId();

					$arg['spid'] = $id;

					if ($hooks) {
						$hooks -> do_action("speka_add", $post, $arg);
					}

					$message[] = "Добавлена позиция";

				}
				elseif ($did == 0) {

					$response['result']        = 'Error';
					$response['error']['code'] = '403';
					$response['error']['text'] = "Отсутствуют параметры - id сделки (did)";

					goto ext;

				}
				elseif ($deal == '') {

					$response['result']        = 'Error';
					$response['error']['code'] = '403';
					$response['error']['text'] = "Сделка не найдена";

					goto ext;

				}

			}
			//редактирование позиции
			else {

				$spid = (int)$db -> getOne("SELECT spid FROM {$sqlname}speca WHERE spid = '$id' and identity = '$identity'");
				$did  = (int)$db -> getOne("SELECT did FROM {$sqlname}speca WHERE spid = '$id' and identity = '$identity'");

				if ($spid > 0) {

					$arg = [
						'prid'     => $prid,
						'artikul'  => $artikul,
						'title'    => $title,
						'tip'      => max((int)$tip, 0),
						'price'    => $price,
						'price_in' => $price_in,
						'kol'      => $kol,
						'edizm'    => $edizm,
						'nds'      => $nds,
						'dop'      => $dop,
						'comments' => ( $comment == '' ) ? " " : $comment
					];

					//очищаем от мусора и случайных элементов
					$arg = $db -> filterArray($arg, $allowed);

					$db -> query("UPDATE {$sqlname}speca SET ?u WHERE spid = '$id' and identity = '$identity'", $arg);

					if ($hooks) {
						$hooks -> do_action("speka_edit", $post, $arg);
					}

					$message[] = "Обновлена позиция";

				}
				else {

					$response['result']        = 'Error';
					$response['error']['code'] = '403';
					$response['error']['text'] = "Сделка не найдена";

					goto ext;

				}

			}

			reCalculate($did);

			if ($params['event']) {
				event ::fire('deal.edit', $args = [
					"did"     => $did,
					"autor"   => $iduser1,
					"comment" => "Изменена спецификация"
				]);
			}

			$response['result'] = 'Успешно';
			$response['data']   = $did;
			$response['spid']   = (int)$id;
			$response['text']   = $message;

		}
		else {

			$response['result']        = 'Error';
			$response['error']['code'] = '406';
			$response['error']['text'] = "Отсутствуют параметры - Название позиции";

		}

		ext:

		return $response;

	}

	/**
	 * Добавление массива позиций в спецификацию
	 *
	 * @param int $did - id сделки
	 * @param array $speka - массив позиций, где
	 *                   - int prid|n_id - id позиции в прайсе
	 *                   - string artikul - артикул
	 *                   - string title - название позиции
	 *                   - int tip - тип позиции
	 *                   - float price_in - закупочная цена
	 *                   - float price - цена продажи
	 *                   - string edizm - ед.измерения
	 *                   - float nds - ставка налога
	 *                   - float dop - доп.множитель
	 *                   - string comments - комментарий для позиции
	 * @param     $event (true|false) - отправлять событие или нет, false
	 *
	 * @return mixed
	 */
	public function mass(int $did = 0, array $speka = [], bool $event = false): array {

		$rootpath = $this -> rootpath;

		$sqlname  = $this -> sqlname;
		$db       = $this -> db;
		$identity = $this -> identity;

		$message = [];

		$deal = getDogData($did, 'title');

		if ($did == 0) {

			$response['result']        = 'Error';
			$response['error']['code'] = '403';
			$response['error']['text'] = "Отсутствуют параметры - id сделки (did)";

			goto ext;

		}
		elseif ($deal == '') {

			$response['result']        = 'Error';
			$response['error']['code'] = '403';
			$response['error']['text'] = "Сделка не найдена";

			goto ext;

		}

		if (!empty($speka)) {

			$err = 0;

			foreach ($speka as $item) {

				//найдем данные по позиции в прайсе
				if ($item['title'] != '' && $item['artikul'] != '') {

					$resp = $db -> getRow("SELECT * FROM {$sqlname}price WHERE n_id > 0 and (n_id = '$item[prid]' or artikul = '$item[artikul]' or title = '$item[title]') and identity = '$identity'");

					$item['artikul']  = $item["artikul"] != '' ? $item["artikul"] : $resp["artikul"];
					$item['prid']     = $item['prid'] > 0 ? $item['prid'] : $resp["n_id"];
					$item['title']    = ( $resp['title'] == '' ) ? $item['title'] : $resp["title"];
					$item['tip']      = max((int)$item['tip'], 0);
					$item['edizm']    = ( $item['edizm'] != '' ) ? $item['edizm'] : $resp["edizm"];
					$item['price']    = ( $item['price'] > 0 ) ? $item['price'] : (float)$resp["price_1"];
					$item['price_in'] = $item['price_in'] > 0 ? $item['price_in'] : (float)$resp["price_in"];
					$item['nds']      = $item['nds'] > 0 ? $item["nds"] : (float)$resp['nds'];
					$item['comments'] = $item["comments"];
					$item['dop']      = $item["dop"];

				}

				$item['nds']   += 0;
				$item['did']   = $did;
				$item['event'] = $event;

				//print_r($item);

				$rez = $this -> edit(0, $item);

				if ($rez['result'] == 'Error') {

					$err++;
					$message[] = $rez['error']['text'];

				}

			}

			if ($err > 0) {

				$response['result']        = 'Error';
				$response['error']['code'] = '406';
				$response['error']['text'] = yimplode("; ", $message);

			}
			else {

				$response['result'] = 'Успешно';
				$response['data']   = $did;
				$response['text']   = "Добавлены позиции в спецификацию. Ошибок $err";

			}

		}
		else {

			$response['result']        = 'Error';
			$response['error']['code'] = '406';
			$response['error']['text'] = "Отсутствуют параметры - Массив позиций";

		}

		ext:

		return $response;

	}

	/**
	 * Удаление позиции спецификации
	 *
	 * @param       $id
	 * @param array $params - массив с параметрами
	 *                      - bool event (true|false) - отправлять событие или нет, false
	 *
	 * @return mixed
	 */
	public function delete($id, array $params = []): array {

		global $hooks;

		$rootpath = $this -> rootpath;

		$sqlname  = $this -> sqlname;
		$db       = $this -> db;
		$identity = $this -> identity;

		$iduser1 = ( $params['iduser1'] > 0 ) ? $params['iduser'] : $this -> iduser1;

		$did = (int)$params['did'];

		$params['event'] = $params['event'] ?? false;

		if ($did == 0) {
			$did = $db -> getOne("SELECT did FROM {$sqlname}speca WHERE spid = '$id'");
		}

		$message = [];

		if ($id > 0) {

			if ($hooks) {
				$hooks -> do_action("speka_delete", $id);
			}

			$db -> query("DELETE FROM {$sqlname}speca WHERE spid = '$id' and identity = '$identity'");

			$message[] = reCalculate($did);

			if ($params['event']) {
				event ::fire('deal.edit', $args = [
					"did"     => $did,
					"autor"   => $iduser1,
					"comment" => "Изменена спецификация - удалена позиция"
				]);
			}

			$response['result'] = 'Успешно';
			$response['data']   = $did;
			$response['spid']   = $id;
			$response['text']   = $message;

		}
		else {

			$response['result']        = 'Error';
			$response['error']['code'] = '406';
			$response['error']['text'] = "Отсутствуют параметры - ID позиции";

		}

		return $response;

	}

	/**
	 * Выдает информацию по спеке
	 *
	 * @param $did
	 * @return array - массив с результатом
	 *      - nalog - сумма НДС
	 *      - summa - сумма спеки
	 *      - zakup - закупочная стоимость
	 *
	 */
	public static function getNalog($did): array {

		$rootpath = dirname(__DIR__, 2);

		require_once $rootpath."/inc/config.php";
		require_once $rootpath."/inc/dbconnector.php";
		require_once $rootpath."/inc/func.php";

		$identity = $GLOBALS['identity'];
		$sqlname  = $GLOBALS['sqlname'];
		$db       = $GLOBALS['db'];
		$fpath    = $GLOBALS['fpath'];

		$otherSettings = json_decode(file_get_contents($rootpath."/cash/".$fpath."otherSettings.json"), true);

		//$other = $db -> getOne("SELECT other FROM {$sqlname}settings WHERE id = '$identity'");
		//$other = explode(";", $other);

		$mcid = (int)getDogData((int)$did, 'mcid');

		$nalogScheme = getNalogScheme(0, $mcid);

		$ndsRaschet = $otherSettings['ndsInOut'];

		$num          = 0;
		$ndsTotal     = 0;
		$summaInTotal = 0;
		$summaTotal   = 0;

		$sp = $db -> query("SELECT * FROM {$sqlname}speca WHERE did = '$did' AND tip!='2' and identity = '$identity' ORDER BY spid");
		while ($da = $db -> fetch($sp)) {

			//если у компании Налог = 0, то она его не платит
			if ($da['nds'] > 0 && $nalogScheme['nalog'] == 0) {
				$da['nds'] = 0;
			}

			$ndsa = getNalog($da['price'], $da['nds'], $ndsRaschet);

			if ($ndsRaschet != 'yes') {

				$summa = pre_format($da['kol']) * pre_format($da['price']) * pre_format($da['dop']);

				$num     += pre_format($da['kol']);
				$summaIn = pre_format($da['price_in']) * pre_format($da['kol']) * pre_format($da['dop']);

				//расчет итогов
				$ndsTotal += $ndsa['nalog'] * pre_format($da['kol']) * pre_format($da['dop']);

			}
			else {

				$summa = pre_format($da['kol']) * pre_format($da['price']) * pre_format($da['dop']);

				$num     += pre_format($da['kol']);
				$summaIn = pre_format($da['price_in']) * pre_format($da['kol']) * pre_format($da['dop']);

				//расчет итогов
				$ndsTotal += $ndsa['nalog'] * pre_format($da['kol']) * pre_format($da['dop']);

			}

			$summaInTotal += $summaIn;
			$summaTotal   += $summa;

		}

		return [
			"nalog" => $ndsTotal,
			"summa" => $summaTotal,
			"zakup" => $summaInTotal,
		];

	}

	/**
	 * Возвращает спецификацию по сделке в массиве
	 *
	 * @param     $did
	 * @param int $rs
	 *
	 * @return array
	 * @category Core
	 * @package  Func
	 */
	public function getSpekaData($did, int $rs = 0): array {

		$rootpath = $this -> rootpath;

		$sqlname  = $this -> sqlname;
		$db       = $this -> db;
		$identity = $this -> identity;

		$ndsRaschet = $GLOBALS['ndsRaschet'];
		$fpath      = $GLOBALS['fpath'];

		if (empty($ndsRaschet)) {
			$ndsRaschet = 'no';
		}

		$otherSettings = json_decode(file_get_contents($rootpath."/cash/".$fpath."otherSettings.json"), true);

		if ($rs == 0) {

			$mcid        = (int)$db -> getOne("SELECT mcid FROM {$sqlname}dogovor WHERE did = '$did' and identity = '$identity'");
			$nalogScheme = getNalogScheme(0, $mcid);

		}
		else {
			$nalogScheme = getNalogScheme($rs);
		}

		$summaInvoice = 0;
		$summaItog    = $ItogTovar = $ItogUsluga = 0;
		$summaNalog   = 0;
		$summaZakup   = $ZakupTovar = $ZakupUsluga = $ZakupMaterial = $NalogMaterial = 0;
		$NalogTovar   = $NalogUsluga = $ItogMaterial = 0;
		$i            = 1;
		$pozition     = $tovar = $usluga = $material = [];

		$result = $db -> query("SELECT * FROM {$sqlname}speca WHERE did = '$did' AND identity = '$identity' ORDER BY spid");
		while ($data = $db -> fetch($result)) {

			$s = '';

			if ((int)$data['prid'] > 0) {
				$s = " and n_id='".$data['prid']."'";
			}
			elseif ($data['artikul'] != '') {
				$s = " and artikul='".$data['artikul']."'";
			}

			$priceIn = $db -> getOne("SELECT price_in FROM {$sqlname}price WHERE n_id > 0 $s and identity = '$identity'");

			if ($data['tip'] != '2') {

				//если у компании Налог = 0, то она его не платит
				if ($data['nds'] > 0 && $nalogScheme['nalog'] == 0) {
					$data['nds'] = 0;
				}
				//elseif ($data['nds'] == 0) $data['nds'] = 0;

				//стоимость позиций спецификации (без учета материалов)
				$summaPoz = pre_format($data['kol']) * pre_format($data['price']) * pre_format($data['dop']);

				$summaZakup = pre_format($data['kol']) * pre_format($data['price_in']) * pre_format($data['dop']);
				$summaItog  += $summaPoz;

				$ndsa = getNalog($summaPoz, $data['nds'], (string)$ndsRaschet);     //НДС на все количество
				$ndsi = getNalog($data['price'], $data['nds'], (string)$ndsRaschet);//НДС на 1 ед.изм.

				$summaNalog += $ndsa['nalog'];
				$ndsPoz     = $ndsi['nalog'];

				if ($ndsRaschet == 'yes') {
					$summaInvoice += $summaPoz + $ndsa['nalog'];
				}
				else {
					$summaInvoice += $summaPoz;
				}

				$dop   = ( $otherSettings['dop'] ) ? $data['dop'] : '';
				$nalog = ( $nalogScheme['nalog'] != 0 ) ? $ndsPoz : '';

				$pozition[$data['spid']] = [
					"num"        => $i,
					"prid"       => (int)$data['prid'],
					"artikul"    => $data['artikul'],
					"title"      => $data['title'],
					"tip"        => $data['tip'],
					"comments"   => $data['comments'],
					"kol"        => (float)$data['kol'],
					"dop"        => $dop,
					"edizm"      => $data['edizm'],
					"price"      => (float)$data['price'],
					"price_in"   => (float)$data['price_in'],
					"nds"        => (float)$nalog,
					"summa"      => (float)$summaPoz,
					"summaZakup" => (float)$summaZakup,
					"inPrice"    => (float)$priceIn,
					"spid"       => (int)$data['spid']
				];

				if ($data['tip'] == '0') {

					//если у компании Налог = 0, то она его не платит
					if ($data['nds'] > 0 && $nalogScheme['nalog'] == 0) {
						$data['nds'] = 0;
					}
					//elseif ($data['nds'] == 0) $data['nds'] = 0;

					//стоимость товаров (всего количества)
					$summaPoz = pre_format($data['kol']) * pre_format($data['price']) * pre_format($data['dop']);

					$ZakupTovar = $data['kol'] * $data['price_in'] * $data['dop'];
					$ItogTovar  += $summaPoz;

					$ndsa = getNalog($summaPoz, $data['nds'], $ndsRaschet);     //НДС на все количество
					$ndsi = getNalog($data['price'], $data['nds'], $ndsRaschet);//НДС на 1 ед.изм.

					$NalogTovar += $ndsa['nalog'];
					$ndsPoz     = $ndsi['nalog'];

					if ($otherSettings['dop']) {
						$dop = $data['dop'];
					}
					else {
						$dop = '';
					}

					if ($nalogScheme['nalog'] != 0) {
						$nalog = $ndsPoz;
					}
					else {
						$nalog = '';
					}

					$tovar[] = [
						"num"        => $i,
						"prid"       => (int)$data['prid'],
						"artikul"    => $data['artikul'],
						"title"      => $data['title'],
						"tip"        => $data['tip'],
						"comments"   => $data['comments'],
						"kol"        => (float)$data['kol'],
						"dop"        => $dop,
						"edizm"      => $data['edizm'],
						"price"      => (float)$data['price'],
						"price_in"   => (float)$data['price_in'],
						"nds"        => (float)$nalog,
						"summa"      => (float)$summaPoz,
						"summaZakup" => (float)$ZakupTovar,
						"inPrice"    => (float)$priceIn,
						"spid"       => (int)$data['spid']
					];

				}
				else {

					//если у компании Налог = 0, то она его не платит
					if ($data['nds'] > 0 && $nalogScheme['nalog'] == 0) {
						$data['nds'] = 0;
					}
					//elseif ($data['nds'] == 0) $data['nds'] = 0;

					//стоимость услуг (всего количества)
					$summaPoz = pre_format($data['kol']) * pre_format($data['price']) * pre_format($data['dop']);

					$ZakupUsluga = $data['kol'] * $data['price_in'] * $data['dop'];
					$ItogUsluga  += $summaPoz;

					$ndsa = getNalog($summaPoz, $data['nds'], $ndsRaschet);     //НДС на все количество
					$ndsi = getNalog($data['price'], $data['nds'], $ndsRaschet);//НДС на 1 ед.изм.

					$NalogUsluga += $ndsa['nalog'];
					$ndsPoz      = $ndsi['nalog'];

					if ($otherSettings['dop']) {
						$dop = $data['dop'];
					}
					else {
						$dop = '';
					}

					if ($nalogScheme['nalog'] != 0) {
						$nalog = $ndsPoz;
					}
					else {
						$nalog = '';
					}

					$usluga[] = [
						"num"        => $i,
						"prid"       => (int)$data['prid'],
						"artikul"    => $data['artikul'],
						"title"      => $data['title'],
						"tip"        => $data['tip'],
						"comments"   => $data['comments'],
						"kol"        => (float)$data['kol'],
						"dop"        => $dop,
						"edizm"      => $data['edizm'],
						"price"      => (float)$data['price'],
						"price_in"   => (float)$data['price_in'],
						"nds"        => (float)$nalog,
						"summa"      => (float)$summaPoz,
						"summaZakup" => (float)$ZakupUsluga,
						"inPrice"    => (float)$priceIn,
						"spid"       => (int)$data['spid']
					];

				}

			}
			else {

				//если у компании Налог = 0, то она его не платит
				if ($data['nds'] > 0 && $nalogScheme['nalog'] == 0) {
					$data['nds'] = 0;
				}
				//elseif ($data['nds'] == 0) $data['nds'] = 0;

				//стоимость материалов (всего количества)
				$summaPoz = pre_format($data['kol']) * pre_format($data['price']) * pre_format($data['dop']);

				$ZakupMaterial = $data['kol'] * $data['price_in'] * $data['dop'];
				$ItogMaterial  += $summaPoz;

				$ndsa = getNalog($summaPoz, $data['nds'], $ndsRaschet);     //НДС на все количество
				$ndsi = getNalog($data['price'], $data['nds'], $ndsRaschet);//НДС на 1 ед.изм.

				$NalogMaterial += $ndsa['nalog'];
				$ndsPoz        = $ndsi['nalog'];

				$dop   = ( $otherSettings['dop'] ) ? $data['dop'] : '';
				$nalog = ( $nalogScheme['nalog'] != 0 ) ? $ndsPoz : '';

				$material[] = [
					"num"        => $i,
					"prid"       => (int)$data['prid'],
					"artikul"    => $data['artikul'],
					"title"      => $data['title'],
					"tip"        => $data['tip'],
					"comments"   => $data['comments'],
					"kol"        => (float)$data['kol'],
					"dop"        => $dop,
					"edizm"      => (float)$data['edizm'],
					"price"      => (float)$data['price'],
					"price_in"   => (float)$data['price_in'],
					"nds"        => (float)$nalog,
					"summa"      => (float)$summaPoz,
					"summaZakup" => (float)$ZakupMaterial,
					"inPrice"    => (float)$priceIn,
					"spid"       => (int)$data['spid']
				];

			}

			$i++;

		}

		return [
			"pozition"     => $pozition,
			"tovar"        => $tovar,
			"usluga"       => $usluga,
			"material"     => $material,
			"summaInvoice" => $summaInvoice,
			"summaItog"    => $summaItog,
			"summaNalog"   => $summaNalog,
			"itogTovar"    => $ItogTovar,
			"nalogTovar"   => $NalogTovar,
			"itogUsluga"   => $ItogUsluga,
			"nalogUsluga"  => $NalogUsluga,
			"itogMaterial" => $ItogMaterial
		];

	}

	/**
	 * Данные для вывода спецификации в карточке сделки
	 * @param int $did
	 * @param string $type - тип позиций (pozition = осн.спецификация, material - материалы)
	 * @return array
	 */
	public function card(int $did = 0, string $type = 'pozition'): array {

		$sqlname  = $this -> sqlname;
		$db       = $this -> db;
		$identity = $this -> identity;

		$valuta  = $GLOBALS['valuta'];
		$isadmin = $GLOBALS['isadmin'];

		global $isCatalog, $otherSettings, $ndsRaschet, $show_marga;

		$deal = Deal ::info($did);

		$invoicees   = (int)$db -> getOne("SELECT COUNT(*) FROM {$sqlname}credit WHERE did = '$did' and identity = '$identity'");
		$speka       = ( new Speka() ) -> getSpekaData($did);
		$nalogScheme = getNalogScheme(0, (int)$deal['mcid']);

		$dallow = 0;
		if ($deal['close']['close'] != 'yes') {

			if ($deal['step']['steptitle'] < 80 && $invoicees > 0) {
				$dallow = 1;
			}
			elseif ($deal['step']['steptitle'] <= 80) {
				$dallow = 2;
			}
			elseif ($invoicees > 0) {
				$dallow = 3;
			}

		}
		if ($invoicees > 0) {
			++$dallow;
		}

		$message = $rows = [];

		if ($deal['close']['close'] == 'yes') {
			$message[] = 'Сделка закрыта. Составление спецификации <b>не целесообразно</b>!';
		}
		if ($dallow == 1) {
			$message[] = 'Изменение спецификации <b>не целесообразно</b>, т.к. составлен график оплаты';
		}
		if ($dallow == 3) {
			$message[] = 'Статус договора более 80%. Составление спецификации <b>не целесообразно</b>!';
		}

		$complect = round(Akt ::getAktComplect($did), 0);
		if ($complect > 0 && $deal['calculate'] == 'yes') {
			$message[] = "<b>Актами закрыто $complect% позиций спецификации</b>";
		}

		$daccesse = get_accesse(0, 0, $did);

		$nalogComment = '(с учетом налога)';
		if ($ndsRaschet == 'yes' && $nalogScheme['nalog'] > 0) {
			$nalogComment = '(без учета налога)';
		}
		elseif ($nalogScheme['nalog'] == 0) {
			$nalogComment = '(налогом не облагается)';
		}

		$i            = 1;
		$err          = 0;
		$totalCount   = 0;
		$ndsTotal     = 0;
		$summaInTotal = 0;
		$summaTotal   = 0;

		foreach ($speka[$type] as $da) {

			$s   = '';
			$msg = [];

			$tip = 'Товар';

			if ((int)$da['tip'] == 1) {
				$tip = 'Услуга';
			}
			elseif ((int)$da['tip'] == 2) {
				$tip = 'Материал';
			}

			if ((int)$da['prid'] > 0) {
				$s = " and n_id = '".$da['prid']."'";
			}
			elseif ($da['artikul'] != '' && $da['artikul'] != 'undefined') {
				$s = " and artikul = '".$da['artikul']."'";
			}

			$pia = $db -> getRow("SELECT n_id, price_in FROM {$sqlname}price WHERE n_id > 0 $s and identity = '$identity'");

			//print "SELECT n_id, price_in FROM {$sqlname}price WHERE n_id > 0 $s and identity = '$identity'\n";

			$summaTotal   += (float)$da['summa'];
			$summaInTotal += (float)$da['summaZakup'];
			$ndsTotal     += (float)$da['nds'];

			if ((int)$da['prid'] > 0) {

				if ((int)$pia['n_id'] == 0) {
					$msg = [
						"title" => 'Позиция удалена из прайса',
						"html"  => '<i class="icon-attention red list fs-07" title="Позиция удалена из прайса"></i>'
					];
				}
				elseif ((float)$da['price_in'] != (float)$pia['price_in']) {

					$delta = pre_format((float)$pia['price_in']) - pre_format($da['price_in']);
					$t     = 'Закупочная цена по прайсу отличается на '.( $delta < 0 ? "" : "+" ).num_format($delta).' '.$valuta;

					$msg = [
						"title" => $t,
						"html"  => '<i class="icon-attention red list fs-07" title="'.$t.'"></i>'
					];

				}
				else {
					$msg = [
						"title" => 'Закуп в порядке',
						"html"  => '<i class="icon-ok green list fs-07" title="Закуп в порядке"></i>'
					];
				}

			}
			else{

				$msg = [
					"title" => 'Не прайсовая позиция',
					"html"  => '<i class="icon-help-circled broun fs-07" title="Не прайсовая позиция"></i>'
				];

			}

			$da['artikul'] = ( $da['artikul'] != '' && $da['artikul'] != 'undefined' ) ? $da['artikul'] : '???';

			// поищем позиции в актах
			$deid = $db -> getCol("SELECT DISTINCT(deid) FROM {$sqlname}contract_poz WHERE did = '$did' AND spid = '$da[spid]'");
			$akts = ( !empty($deid) ) ? yimplode(", ", $db -> getCol("SELECT number FROM {$sqlname}contract WHERE deid IN (".yimplode(",", $deid).")")) : "";

			$rows[] = [
				"number"      => $i,
				"spid"        => $da['spid'],
				"artikul"     => $da['artikul'],
				"title"       => $da['title'],
				"view"        => (int)$da['prid'] > 0 ? true : '',
				"comment"     => $da['comments'] != '' ? $da['comments'] : '',
				"edizm"       => $da['edizm'],
				"kol"         => $da['kol'],
				"kolf"        => num_format($da['kol']),
				"dop"         => $otherSettings['dop'] ? $da['dop'] : '',
				"dopf"        => $otherSettings['dop'] ? num_format($da['dop']) : '',
				"price"       => $da['price'],
				"pricef"      => num_format($da['price']),
				"summa"       => $da['summa'],
				"summaf"      => num_format($da['summa']),
				"prid"        => $da['prid'],
				"isPrice"     => (int)$da['prid'] > 0 ? true : NULL,
				"price_in"    => $show_marga == 'yes' && $otherSettings['marga'] ? $da['price_in'] : '',
				"price_inf"   => $show_marga == 'yes' && $otherSettings['marga'] ? num_format($da['price_in']) : '',
				"summaZakup"  => $da['summaZakup'],
				"summaZakupf" => num_format($da['summaZakup']),
				"tip"         => $tip,
				"msg"         => $show_marga == 'yes' && $otherSettings['marga'] ? $msg : '',
				"akts"        => $akts,
				"edit"        => ( $dallow == 2 || $isadmin == 'on' ) && ( $daccesse == 'yes' || $isadmin == 'on' ) ? true : NULL
			];

			$i++;
			$totalCount += $da['kol'];

		}

		$marga = $summaTotal - $summaInTotal;

		return [
			"did"          => $did,
			"calculate"    => $deal['calculate'] == 'yes' ? true : NULL,
			"close"        => $deal['calculate'] == 'yes' ? true : NULL,
			"dop"          => $otherSettings['dop'],
			"dopName"      => $otherSettings['dopName'],
			"accesse"      => $daccesse == 'yes' ? true : NULL,
			"message"      => !empty($message) ? $message : NULL,
			"messagestring" => !empty($message) ? yimplode("<br>", $message) : NULL,
			"rights"       => [
				"add" => $deal['close'] != 'yes' && ( $daccesse == 'yes' || $isadmin == 'on' ),
			],
			"nalogComment" => $nalogComment,
			"speca"        => $rows,
			"haveSpeca"    => !empty($rows) ? true : NULL,
			"isCatalog"    => $isCatalog == 'on' ? true : NULL,
			"isPositions"  => $type == 'pozition' ? true : NULL,
			"totalSumma"   => $summaTotal,
			"totalSummaF"  => num_format($summaTotal),
			"totalCount"   => $totalCount,
			"totalCountF"  => num_format($totalCount),
			"totalMarga"   => $marga,
			"totalMargaF"  => num_format($marga),
			"totalZakup"   => $summaInTotal,
			"totalZakupF"  => num_format($summaInTotal),
			"totalNalog"   => $speka['summaNalog'],
			"totalNalogF"  => num_format($speka['summaNalog']),
			"showMarga"    => ( $show_marga == 'yes' && $otherSettings['marga'] ) ? true : NULL,
			"isSpeka"      => $type == 'pozition' ? true : NULL,
			"value"        => $summaTotal > 0 ? num_format($marga / $summaTotal * 100) : 0,
			"valuta"       => $valuta
		];

	}

}