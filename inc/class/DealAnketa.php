<?php
/* ============================ */
/*         SalesMan CRM         */
/* ============================ */
/* (C) 2018 Vladislav Andreev   */
/*       SalesMan Project       */
/*        www.isaler.ru         */
/*        ver. 2018.x           */
/* ============================ */

namespace Salesman;

use Exception;

/**
 * Класс для работы с анкетами по сделке
 * Class DealAnketa
 *
 * @package     Salesman
 * @author      Vladislav Andreev <v@salesman.pro>
 * @version     1.0 (06/09/2019)
 */
class DealAnketa {

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
	 * Работает только с объектом.
	 * Подключает необходимые файлы, задает первоначальные параметры
	 * Currency constructor.
	 */
	public function __construct() {

		$rootpath = dirname( __DIR__, 2 );

		require_once $rootpath."/inc/config.php";
		require_once $rootpath."/inc/dbconnector.php";
		require_once $rootpath."/inc/func.php";

		$params = $this -> params;

		$this -> rootpath = dirname( __DIR__, 2 );
		$this -> identity = ($params['identity'] > 0) ? $params['identity'] : $GLOBALS['identity'];
		$this -> iduser1  = $GLOBALS['iduser1'];
		$this -> sqlname  = $GLOBALS['sqlname'];
		$this -> db       = $GLOBALS['db'];
		$this -> fpath    = $GLOBALS['fpath'];
		$this -> opts     = $GLOBALS['opts'];
		$this -> tmzone   = $GLOBALS['tmzone'];

		// тут почему-то не срабатывает
		if ( !empty( $params ) ) {
			foreach ( $params as $key => $val ) {
				$this ->{$key} = $val;
			}
		}

		date_default_timezone_set( $this -> tmzone );

	}

	/**
	 * Редактирование анкеты
	 *
	 * @param array $params
	 * @return bool
	 */
	public function edit(array $params = []): bool {

		$sqlname  = $this -> sqlname;
		$db       = $this -> db;
		$identity = $this -> identity;

		$ida    = (int)$params['ida'];
		$clid   = (int)$params['clid'];
		$did    = (int)$params['did'];
		$fields = (array)$params['field'];

		//определяем массив значений
		$cfield = [];

		//массив полей
		foreach ( $fields as $pole => $value ) {

			$value = (is_array( $value )) ? yimplode( ";", $value ) : $value;

			//найдем id поля в базе
			$id = (int)$db -> getOne( "SELECT id FROM {$sqlname}deal_anketa_base WHERE pole = '$pole' AND ida = '$ida' AND identity = '$identity'" );

			//сформируем массив передаваемых значений имя профиля -> значение
			$cfield[ $id ] = [
				"id"    => $id,
				"name"  => $pole,
				"value" => $value
			];

		}

		//print_r($cfield);

		$result = $db -> getAll( "SELECT * FROM {$sqlname}deal_anketa_base WHERE ida = '$ida' AND identity = '$identity'" );
		foreach ( $result as $fieldbase ) {

			//Проверим существование текущего поля в базе
			$aid = (int)$db -> getOne( "SELECT id FROM {$sqlname}deal_anketa WHERE clid = '$clid' AND did = '$did' AND idbase = '$fieldbase[id]' AND ida = '$ida' AND identity = '$identity'" ) + 0;

			//print $cfield[ $fieldbase['id'] ]['value']."\n";

			//если значение заполнено
			if ( $cfield[ $fieldbase['id'] ]['value'] != '' ) {

				//Добавляем, если записи поля не найдено
				if ( $aid == 0 ) {

					$db -> query( "INSERT INTO {$sqlname}deal_anketa SET ?u", [
						'idbase'   => $fieldbase['id'],
						'ida'      => $ida,
						'clid'     => $clid,
						'did'      => $did,
						'value'    => untag( $cfield[ $fieldbase['id'] ]['value'] ),
						'identity' => $identity
					] );

				}
				//или устанавливаем новое значение
				else {
					$db -> query( "UPDATE {$sqlname}deal_anketa SET ?u WHERE id = '$aid'", ['value' => $cfield[ $fieldbase['id'] ]['value']] );
				}

			}
			//если не заполнено, то очищаем
			elseif ( $aid > 0 ) {
				$this -> clear( $aid );
			}

		}

		return true;

	}

	/**
	 * Удаление поля анкеты
	 *
	 * @param $id
	 */
	public function delete($id): void {

		$sqlname  = $this -> sqlname;
		$db       = $this -> db;
		$identity = $this -> identity;

		$db -> query( "DELETE FROM {$sqlname}deal_anketa WHERE id = '$id' AND identity = '$identity'" );

	}

	/**
	 * Очистка поля анкеты
	 *
	 * @param $id
	 * @return int
	 */
	public function clear($id): int {

		$sqlname  = $this -> sqlname;
		$db       = $this -> db;
		$identity = $this -> identity;

		$db -> query( "UPDATE {$sqlname}deal_anketa SET value = '' WHERE id = '$id' and identity = '$identity'" );

		return (int)$db -> getOne( "SELECT ida FROM {$sqlname}deal_anketa WHERE id = '$id' AND identity = '$identity'" );

	}

	/**
	 * Информация по анкете
	 *
	 * @param $id
	 * @return mixed
	 */
	public function anketainfo($id) {

		$sqlname  = $this -> sqlname;
		$db       = $this -> db;
		$identity = $this -> identity;

		$field_types = db_columns_types( "{$sqlname}deal_anketa_list" );

		$anketa = $db -> getRow( "SELECT * FROM {$sqlname}deal_anketa_list WHERE id = '$id' AND identity = '$identity'" );

		foreach ( $anketa as $k => $v ) {

			if ( is_numeric( $k ) ) {
				unset( $anketa[ $k ] );
			}
			elseif($field_types[ $k ] == "int"){

				$anketa[ $k ] = (int)$v;

			}
			elseif(in_array($field_types[ $k ], ["float","double"])){

				$anketa[ $k ] = (float)$v;

			}
			else {

				$anketa[ $k ] = $v;

			}

		}

		return $anketa;

	}

	/**
	 * Список анкет
	 *
	 * @return array
	 */
	public function anketalist(): array {

		$sqlname  = $this -> sqlname;
		$db       = $this -> db;
		$identity = $this -> identity;

		$field_types = db_columns_types( "{$sqlname}deal_anketa_list" );

		$anketa = $db -> getAll( "SELECT * FROM {$sqlname}deal_anketa_list WHERE identity = '$identity' ORDER by id" );

		foreach ( $anketa as $key => $value ) {

			foreach ( $value as $k => $v ) {

				/*if ( is_numeric( $k ) ) {
					unset( $anketa[ $key ][ $k ] );
				}*/

				if ( is_numeric( $k ) ) {
					unset( $anketa[ $key ][ $k ] );
				}
				elseif($field_types[ $k ] == "int"){

					$anketa[ $key ][ $k ] = (int)$v;

				}
				elseif(in_array($field_types[ $k ], ["float","double"])){

					$anketa[ $key ][ $k ] = (float)$v;

				}
				else {

					$anketa[ $key ][ $k ] = $v;

				}

			}

		}

		return $anketa;

	}

	/**
	 * Список анкет по сделке
	 *
	 * @param $did
	 * @return array
	 */
	public function anketadeallist($did): array {

		$sqlname  = $this -> sqlname;
		$db       = $this -> db;
		$identity = $this -> identity;

		$list = [];

		//находим уникальные id анкет, которые есть в этой сделке
		$ida = $db -> getCol( "SELECT DISTINCT ida FROM {$sqlname}deal_anketa WHERE did = '$did' AND identity = '$identity'" );

		//если анкеты есть, то составим их список
		if ( !empty( $ida ) ) {

			$list = $db -> getIndCol( "id", "SELECT title,id FROM {$sqlname}deal_anketa_list WHERE id IN (".yimplode( ",", $ida ).") AND identity = '$identity' ORDER by id" );

		}

		return $list;

	}

	/**
	 * Структура базовой анкеты
	 *
	 * @param $ida
	 * @return array
	 */
	public static function anketabase($ida): array {

		$rootpath = dirname( __DIR__, 2 );

		require_once $rootpath."/inc/config.php";
		require_once $rootpath."/inc/dbconnector.php";
		require_once $rootpath."/inc/func.php";

		$sqlname  = $GLOBALS['sqlname'];
		$identity = $GLOBALS['identity'];
		$db       = $GLOBALS['db'];

		$anketa = [];

		$valIsArray = [
			'select',
			'checkbox',
			'radio',
			'multiselect',
			'inputlist'
		];

		//выбираем все элементы, не включенные в блоки
		//элементы в блоках будем добавлять внутри
		$r = $db -> query( "SELECT * FROM {$sqlname}deal_anketa_base WHERE ida = '$ida' AND block = '0' AND identity = '$identity' ORDER BY ord" );
		while ($d = $db -> fetch( $r )) {

			$block = [];

			//если это блок, то найдем все поля, включенные в этот блок
			if ( $d['tip'] == 'divider' ) {

				//поля, объединенные блоком
				$re = $db -> getAll( "SELECT * FROM {$sqlname}deal_anketa_base WHERE ida = '$ida' AND block > 0 AND block = '$d[id]' AND identity = '$identity' ORDER BY ord" );
				foreach ( $re as $da ) {

					$block[ $da['pole'] ] = [
						"id"    => (int)$da['id'],
						"ida"   => (int)$da['ida'],
						"name"  => $da['name'],
						"tip"   => $da['tip'],
						"value" => (in_array( $da['tip'], $valIsArray )) ? yexplode( ";", (string)$da['value'] ) : $da['value'],
						"order" => (int)$da['ord'],
					];

				}

			}

			$anketa[ $d['pole'] ] = [
				"id"    => (int)$d['id'],
				"ida"   => (int)$d['ida'],
				"name"  => $d['name'],
				"tip"   => $d['tip'],
				"value" => (in_array( $d['tip'], $valIsArray )) ? yexplode( ";", (string)$d['value'] ) : $d['value'],
				"block" => $block,
				"order" => (int)$d['ord'],
			];


		}

		return $anketa;

	}

	/**
	 * Формирование поля для вывода
	 *
	 * @param              $tip
	 * @param              $name
	 * @param string|array $value
	 * @param string       $data
	 * @param string       $width
	 * @return string
	 */
	public static function field($tip, $name, $value = '', string $data = '', string $width = 'wp97'): string {

		$newvalue = '';

		$tips = [
			"input"    => "",
			"datum"    => "inputdate",
			"datetime" => "inputdatetime"
		];

		switch ($tip) {

			case 'input':
			case 'datum':
			case 'datetime':

				$newvalue = '<input name="field['.$name.']" id="field['.$name.']" type="text" class="'.$width.' '.strtr( $tip, $tips ).'" value="'.$data.'" autocomplete="off">';

			break;
			case 'text':

				$newvalue = '<textarea name="field['.$name.']" id="field['.$name.']" rows="3" class="'.$width.'">'.$data.'</textarea>';

			break;
			case 'number':

				$newvalue = '<input name="field['.$name.']" id="field['.$name.']" type="number" class="'.$width.'" value="'.$data.'" autocomplete="off">';

			break;
			case 'inputlist':

				$value = (is_array( $value )) ? yimplode( ";", $value ) : $value;

				$newvalue = '
				<INPUT name="field['.$name.']" id="field['.$name.']" value="'.$data.'" type="text" class="'.$width.'" autocomplete="off">
				<div class="smalltxt blue"><em>Двойной клик мышкой для показа вариантов</em></div>
				<script>
					var str = \''.$value.'\';
					var data = str.split(\';\');
					$("#field\\\['.$name.'\\\]").autocomplete(data, {
						autoFill: true,
						minLength: 0,
						minChars: 0,
						cacheLength: 5,
						max: 50,
						selectFirst: true,
						multiple: false,
						delay: 0,
						matchSubset: 0
					});
				</script>
				';

			break;
			case 'select':

				$variant = (is_array( $value )) ? $value : yexplode( ";", $value );
				$v       = '';
				$more    = 0;
				$v1      = '';

				foreach ( $variant as $row ) {

					if ( $row != '???' )
						$v .= '<option value="'.$row.'" '.($data == $row ? "selected" : "").'>'.$row.'</option>';
					else $more = 1;

				}

				//ранее введенные свои варианты
				if ( !in_array( $data, $variant, true ) ) {

					$v .= '<option value="'.$data.'" selected>'.$data.'</option>';

				}

				if ( $more != 0 ) {
					$v1 = '
					<div class="mt5 wp99 block">
						<input name="field['.$name.']" id="field['.$name.']" type="text" class="wp99 bluebg-sub" data-type="new'.$name.'" data-var="new" onkeydown="$(\'select[data-type=zero'.$name.']\').val(\'\');" value="" autocomplete="on" placeholder="Свой вариант">
					</div>
					';
				}

				$newvalue = '
					<SELECT name="field['.$name.']" id="field['.$name.']" class="'.$width.'" data-type="zero'.$name.'">
						<option value="">--выбор--</option>
						'.$v.'
					</SELECT>
					'.$v1.'
				';

			break;
			case 'checkbox':

				$variant = (is_array( $value )) ? $value : yexplode( ";", (string)$value );
				$xdata    = (array)yexplode( ";", $data );
				$v       = '';
				$more    = 0;

				foreach ( $variant as $row ) {

					if ( $row != '???' ) {
						$v .= '
							<div class="flex-string checkbox flx-basis-201 inline viewdiv mb5 mr10 inset bgwhite">
								<label>
									<input type="checkbox" name="field['.$name.'][]" id="field['.$name.'][]" value="'.$row.'" '.(in_array( $row, $xdata ) ? 'checked' : '').'>
									<span class="custom-checkbox"><i class="icon-ok"></i></span>
									<span class="pl10 text-wrap">'.str_replace( " ", "&nbsp;", $row ).'</span>
								</label>
							</div>
						';
					}
					else {
						$more = 1;
					}

				}

				//ранее введенные свои варианты
				foreach ( $xdata as $d ) {

					if ( !in_array( $d, (array)$variant ) ) {

						$v .= '
							<div class="flex-string checkbox flx-basis-201 inline viewdiv mb5 mr10 inset bluebg-sub" title="Свой вариант введенный ранее">
								<label>
									<input type="checkbox" name="field['.$name.'][]" id="field['.$name.'][]" value="'.$d.'" checked>
									<span class="custom-checkbox"><i class="icon-ok"></i></span>
									<span class="pl10 text-wrap">'.str_replace( " ", "&nbsp;", $d ).'</span>
								</label>
							</div>
						';

					}

				}

				if ( $more != 0 ) {
					$v .= '
					<div class="mt5 wp99 block">
						<input name="field['.$name.'][]" id="field['.$name.'][]" type="text" class="wp99 bluebg-sub" value="" data-var="new" autocomplete="off" placeholder="Свой вариант">
					</div>
					';
				}

				$newvalue = '<div class="flex-container box--child">'.$v.'</div>';

			break;
			case 'radio':

				$variant = (is_array( $value )) ? $value : yexplode( ";", (string)$value );
				$data    = yexplode( ";", $data );
				$v       = '';
				$more    = 0;

				foreach ( $variant as $row ) {

					if ( $row != '???' )
						$v .= '
							<div class="flex-string radio flx-basis-201 inline viewdiv mb5 mr10 inset bgwhite">
								<label>
									<input type="radio" name="field['.$name.'][]" id="field['.$name.'][]" value="'.$row.'" onclick="$(\'input[data-type=new'.$name.']\').val(\'\');" '.(in_array( $row, $data ) ? 'checked' : '').'>
									<span class="custom-radio"><i class="icon-radio-check"></i></span>
									<span class="title pl10 text-wrap">'.str_replace( " ", "&nbsp;", $row ).'</span>
								</label>
							</div>
						';
					else $more = 1;

				}

				//ранее введенные свои варианты
				foreach ( $data as $d ) {

					if ( !in_array( $d, (array)$variant ) ) {

						$v .= '
							<div class="flex-string radio flx-basis-201 inline viewdiv mb5 mr10 inset bluebg-sub" title="Свой вариант введенный ранее">
								<label>
									<input type="radio" name="field['.$name.'][]" id="field['.$name.'][]" value="'.$d.'" onclick="$(\'input[data-type=new'.$name.']\').val(\'\');" checked>
									<span class="custom-radio"><i class="icon-radio-check"></i></span>
									<span class="title pl10 text-wrap">'.str_replace( " ", "&nbsp;", $d ).'</span>
								</label>
							</div>
						';

					}

				}

				//Вариант для сброса выбранного
				$v .= '
					<div class="flex-string radio flx-basis-20 inline viewdiv mb5 mr10 inset bgwhite">
						<label>
							<input type="radio" name="field['.$name.'][]" id="field['.$name.'][]" value="" data-type="zero'.$name.'" '.(empty( $data ) ? 'checked' : '').'>
							<span class="custom-radio secondary"><i class="icon-radio-check"></i></span>
							<span class="title pl10 gray">Не выбрано</span>
						</label>
					</div>
				';

				if ( $more != 0 ) {
					$v .= '
					<div class="mt5 wp99 block">
						<input name="field['.$name.'][]" id="field['.$name.'][]" type="text" class="wp99 bluebg-sub" data-type="new'.$name.'" data-var="new" onkeydown="$(\'input[data-type=zero'.$name.']\').prop(\'checked\', true);" value="" autocomplete="on" placeholder="Свой вариант">
					</div>
					';
				}

				$newvalue = '<div class="flex-container box--child">'.$v.'</div>';

			break;

		}

		return $newvalue;

	}

	/**
	 * получение сформированной анкеты
	 *
	 * @param int  $id
	 * @param int  $did
	 * @param bool $action
	 * @return string
	 * @throws Exception
	 */
	public static function anketaprint(int $id, int $did, bool $action = true): string {

		$rootpath = dirname( __DIR__, 2 );

		require_once $rootpath."/inc/config.php";
		require_once $rootpath."/inc/dbconnector.php";
		require_once $rootpath."/inc/func.php";

		$sqlname  = $GLOBALS['sqlname'];
		$identity = $GLOBALS['identity'];
		$db       = $GLOBALS['db'];

		$list = self ::anketabase( $id );

		$stringTip = [
			'input',
			'inputlist',
			'text',
			'number',
			'datum',
			'datetime'
		];

		$str = '';

		foreach ( $list as $pole => $fields ) {

			$data = $db -> getRow( "SELECT id, value FROM {$sqlname}deal_anketa WHERE did = '$did' AND idbase = '$fields[id]' AND ida = '$id' AND identity = '$identity'" );

			$values = [];

			if ( $fields['tip'] != 'divider' ) {

				if ( !in_array( $fields['tip'], $stringTip, true ) ) {

					$values = (array)yexplode( ";", (string)$data['value'] );

				}
				else {

					$values[] = $data['value'];

				}

				$vali = [];
				foreach ( $values as $v ) {
					if ( $v != '' ) {
						$vali[] = '<div class="tag">'.link_it( nl2br( $v ) ).'</div>';
					}
				}

				$val = (!empty( $vali )) ? yimplode( ", ", $vali ) : '<div class="tag gray noBold">--не заполнен--</div>';

				$str .= '
				<div class="flex-string table--newface wp100 mb15">
				
					<div class="fname pb3 fs-10 Bold">'.$fields['name'].':</div>
					<div class="relativ ablock p10 bgwhite hy">
						'.$val.'
						'.($action ? '<a href="javascript:void(0)" class="idel gray blue flh-10" onClick="$anketa.fieldClear(\''.$data['id'].'\')" title="Очистить"><i class="icon-cancel-circled blue"></i></a>' : '').'
					</div>
					
				</div>';

			}
			else {

				$string = '';

				foreach ( $fields['block'] as $ipole => $item ) {

					$values = [];

					$data = $db -> getRow( "SELECT id, value FROM {$sqlname}deal_anketa WHERE did = '$did' AND idbase = '$item[id]' AND ida = '$id' AND identity = '$identity'" );

					if ( !in_array( $item['tip'], $stringTip, true ) ) {

						$values = (array)yexplode( ";", (string)$data['value'] );

					}
					else {

						if ( $item['tip'] == 'datum' )
							$values[] = get_date( $data['value'] );

						elseif ( $item['tip'] == 'datetime' )
							$values[] = get_sfdate( $data['value'] );

						else
							$values[] = $data['value'];

					}

					$vali = [];
					foreach ( $values as $v ) {
						if ( $v != '' ) {
							$vali[] = '<div class="tag">'.link_it( nl2br( $v ) ).'</div>';
						}
					}

					$val = (!empty( $vali )) ? yimplode( ", ", $vali ) : '<div class="tag gray noBold">--не заполнен--</div>';

					$string .= '
					<div class="flex-string wp100 mb15 table--newface">
					
						<div class="fname Bold pb3 fs-10">'.$item['name'].':</div>
						<div class="relativ ablock p10 hy">
							'.$val.'
							'.($action ? '<a href="javascript:void(0)" class="idel gray blue flh-10" onClick="$anketa.fieldClear(\''.$data['id'].'\')" title="Очистить"><i class="icon-cancel-circled blue"></i></a>' : '').'
						</div>
						
					</div>';

				}

				$str .= '
				<div class="p10 border--bottom wp100 bgwhite viewdiv mb10">
					<div class="fs-09 gray pb10">'.$fields['name'].'</div>
					<div>'.$string.'</div>
				</div>
				';

			}

		}

		return '
		<DIV id="danketa">
			<DIV class="flex-container wp100">'.$str.'</DIV>
		</DIV>';

	}

	/**
	 * получение сформированной формы анкеты
	 *
	 * @param      $id
	 * @param int  $did
	 * @param bool $forpaper
	 * @return string
	 */
	public static function anketaform($id, int $did = 0, bool $forpaper = false): string {

		$rootpath = dirname( __DIR__, 2 );

		require_once $rootpath."/inc/config.php";
		require_once $rootpath."/inc/dbconnector.php";
		require_once $rootpath."/inc/func.php";

		$sqlname  = $GLOBALS['sqlname'];
		$identity = $GLOBALS['identity'];
		$db       = $GLOBALS['db'];

		$list = self ::anketabase( $id );

		$forma = '';

		foreach ( $list as $pole => $fields ) {

			$fp = '';

			$data = ($did > 0) ? $db -> getOne( "SELECT value FROM {$sqlname}deal_anketa WHERE did = '$did' AND idbase = '$fields[id]' AND ida = '$id' AND identity = '$identity'" ) : '';

			if ( $forpaper && in_array( $fields['tip'], [
					"select",
					"inputlist"
				] ) ) {

				$fields['tip'] = 'radio';

			}
			elseif ( $forpaper && in_array( $fields['tip'], [
					"input",
					"text",
					"datum",
					"datetime",
					"number"
				] ) ) {

				$fp = 'viewdiv';

			}

			//if( !empty($fields[ 'value' ]) ) {

			$field = self ::field( $fields['tip'], $pole, $fields['value'], $data, 'wp100' );

			if ( $fields['tip'] != 'divider' ) {

				$forma .= '
					<div class="flex-container box--child ablock bgwhite1 p10 wp97">
					
						<div class="flex-string wp100 mb5 gray2 fs-12 Bold">'.$fields['name'].':</div>
						<div class="flex-string wp100 norelativ '.$fp.'">'.$field.'</div>
						
					</div>';

			}
			else {

				$string = '';

				foreach ( $fields['block'] as $ipole => $item ) {

					$fp = '';

					$data = ($did > 0) ? $db -> getOne( "SELECT value FROM {$sqlname}deal_anketa WHERE did = '$did' AND idbase = '$item[id]' AND ida = '$id' AND identity = '$identity'" ) : '';

					if ( $forpaper && in_array( $item['tip'], [
							"select",
							"inputlist"
						] ) ) {

						$item['tip'] = 'radio';

					}
					elseif ( $forpaper && in_array( $item['tip'], [
							"input",
							"text",
							"datum",
							"datetime",
							"number"
						] ) ) {

						$fp = 'viewdiv';

					}

					$field = self ::field( $item['tip'], $ipole, $item['value'], $data, 'wp99' );

					$string .= '
						<div class="flex-container box--child border-box ablock pt10 pb10 wp100">
					
							<div class="flex-string wp100 pl10 mb10 gray2 fs-12 Bold">'.$item['name'].':</div>
							<div class="flex-string wp100 pl10 norelativ '.$fp.'">'.$field.'</div>
							
						</div>
						';

				}

				$forma .= '
					<div class="p10 border--bottom bgwhite viewdiv">
						<div class="fs-09 gray pb10">'.$fields['name'].'</div>
						<div class="">'.$string.'</div>
					</div>
					';

			}

			//}

		}

		return $forma;

	}

}