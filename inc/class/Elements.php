<?php
/* ============================ */
/*         SalesMan CRM         */
/* ============================ */
/* (C) 2016 Vladislav Andreev   */
/*       SalesMan Project       */
/*        www.isaler.ru         */
/*        ver. 2019.x           */
/* ============================ */

namespace Salesman;

/**
 * Класс для вывода селекторов в интерфейс
 *
 * Class Elements
 *
 * @package Salesman
 * @author      Vladislav Andreev <v@salesman.pro>
 * @version     1.1 (06/09/2019)
 *
 * Example
 *
 * ```php
 * $element = new \Salesman\Elements();
 * $select = $element -> UsersSelect("userlist", ["class" => "w300", "multiple" => true, "sel" => [1,22,23]]);
 * ```
 */
class Elements {

	//public $element;

	/**
	 * Названия существующих ролей
	 * @var array
	 */
	public static $roles = [
		"Руководитель организации",
		"Руководитель с доступом",
		"Руководитель подразделения",
		"Руководитель отдела",
		"Менеджер продаж",
		"Поддержка продаж",
		"Администратор"
	];

	/**
	 * Выбор пользователя - Select
	 *
	 * @param $name      - id/name элемента в форме
	 * @param array $opt - опции
	 *      - class = string|array, css-классы, применяемые к элементу select
	 *      - haveplan = boolean, фильтр пользователей имеющих план
	 *      - active = boolean, фильтр пользователей по активности
	 *      - users = array, фильтр по указанным сотрудникам
	 *      - sel = integer|array, id-элемента, который должен быть выбран (-1 отменяет выбор), array в случае если multiple = true
	 *      - jsact = string, js-событие onchange
	 *      - exclude = string|array - id пользователей, исключенные из набора
	 *      - self = boolean, добавляет "--Назначить себе--"
	 *      - noempty = boolean, не позволяет добавить пустой пункт
	 *      - multiple = boolean, преобразует в мультиселект
	 *
	 * @return string
	 *
	 * Example
	 *
	 * ```php
	 * $element = new \Salesman\Elements();
	 * $select = $element -> UsersSelect("userlist", ["class" => "w300", "multiple" => true, "sel" => [1,22,23]]);
	 * ```
	 */
	public function UsersSelect($name, array $opt = []): string {

		$rootpath = dirname( __DIR__ );

		require_once $rootpath."/config.php";
		require_once $rootpath."/dbconnector.php";
		require_once $rootpath."/func.php";

		$sqlname  = $GLOBALS['sqlname'];
		$identity = $GLOBALS['identity'];
		$iduser1  = $GLOBALS['iduser1'];
		$userlim  = $GLOBALS['userlim'];
		$db       = $GLOBALS['db'];

		$sort = '';
		$act = '';

		if (!$opt['class']) {
			$opt['class'] = 'yw200';
		}

		if(!isset($opt['multiple'])) {
			$opt['multiple'] = false;
		}

		if (is_array($opt['class'])) {
			$opt['class'] = yimplode( " ", $opt['class'] );
		}

		if (!empty($opt['exclude'])) {

			if (is_array($opt['exclude'])) {
				$opt['exclude'] = yimplode( ",", $opt['exclude'] );
			}

			$sort .= " iduser NOT IN (".$opt['exclude'].") and ";

		}

		//показывать только тех, кто имеет план продаж
		if ( $opt['haveplan'] ) {
			$sort .= " acs_plan = 'on' and ";
		}

		if ( $opt['active'] ) {
			$sort .= " secrty = 'yes' and ";
		}


		if ( $opt['multiple'] ) {
			$act .= " multiple";
		}


		if (is_array($opt['users']) && !empty($opt['users'])) {
			$sort .= " iduser IN (".yimplode( ",", (array)$opt['users'] ).") and ";
		}

		if (!$opt['multiple']) {

			$opt['sel'] = ($opt['sel'] != '') ? $opt['sel'] : $iduser1;

			if ($opt['sel'] == '-1') {
				$opt['sel'] = '';
			}

		}

		$act .= ($opt['jsact'] != '') ? 'onchange="'.$opt['jsact'].'"' : '';

		$el = [];

		$result = $db -> getAll("SELECT iduser, title, secrty FROM {$sqlname}user WHERE secrty = 'yes' AND $sort identity = '$identity' ORDER by title $userlim");
		foreach ($result as $data) {

			if ($data['secrty'] == 'yes') {

				if (!$opt['multiple']) {
					$el['active'][] = '<option value="'.$data['iduser'].'" class="'.($data['iduser'] == $iduser1 ? "greenbg white" : "").'" '.($data['iduser'] == $opt['sel'] && $opt['sel'] > 0 ? "selected" : "").'>'.$data['title'].($data['iduser'] == $iduser1 ? ' [ Я ]' : '').'</option>';
				}

				else {
					$el['active'][] = '<option value="'.$data['iduser'].'" class="'.($data['iduser'] == $iduser1 ? "greenbg white" : "").'" '.(in_array( $data['iduser'], $opt['sel'] ) ? "selected" : "").'>'.$data['title'].($data['iduser'] == $iduser1 ? ' [ Я ]' : '').'</option>';
				}

			}

			else {
				$el['inactive'][] = '<option value="'.$data['iduser'].'" class="graybg-lite">'.$data['title'].'</option>';
			}

		}

		$result = $db -> getAll("SELECT iduser, title, secrty FROM {$sqlname}user WHERE secrty != 'yes' AND $sort identity = '$identity' ORDER by title");
		foreach ($result as $data) {

			if ($data['secrty'] == 'yes') {

				if (!$opt['multiple']) {
					$el['active'][] = '<option value="'.$data['iduser'].'" class="'.($data['iduser'] == $iduser1 ? "greenbg white" : "").'" '.($data['iduser'] == $opt['sel'] && $opt['sel'] > 0 ? "selected" : "").'>'.$data['title'].($data['iduser'] == $iduser1 ? ' [ Я ]' : '').'</option>';
				}

				else {
					$el['active'][] = '<option value="'.$data['iduser'].'" class="'.($data['iduser'] == $iduser1 ? "greenbg white" : "").'" '.(in_array( $data['iduser'], (array)$opt['sel'] ) ? "selected" : "").'>'.$data['title'].($data['iduser'] == $iduser1 ? ' [ Я ]' : '').'</option>';
				}

			}

			else {
				$el['inactive'][] = '<option value="'.$data['iduser'].'" class="graybg-lite">'.$data['title'].'</option>';
			}

		}

		$a  = '
		<optgroup label="Активные">
		'.implode("", (array)$el['active']).'
		</optgroup>
		';

		$na = !$opt['active'] ? '
		<optgroup label="Не активные">
		'.implode("", (array)$el['inactive']).'
		</optgroup>
		' : '';

		$self = ($opt['self'] && $opt['hasempty']) ? '<option value="">--Назначить себе--</option>' : '<option value="">--выбор--</option>';

		if ($opt['noempty'] || $opt['multiple']) {
			$self = '';
		}

		return '
		<select name="'.$name.'" id="'.$name.'" class="'.$opt['class'].'" '.$act.'>
			'.$self.'
			'.$a.'
			'.$na.'
		</select>
		';

	}

	/**
	 * Клиент
	 */

	/**
	 * Выбор канала продаж - Select
	 *
	 * @param $name      - id/name элемента в форме
	 * @param array $opt - опции
	 *              - class = string|array, css-классы, применяемые к элементу select
	 *              - sel = integer, id-элемента, который должен быть выбран (-1 отменяет выбор)
	 *              - noempty = boolean, не позволяет добавить пустой пункт
	 *              - multiple = boolean, преобразует в мультиселект
	 *              - data - data-атрибуты, передаваемые строкой
	 *
	 * @return string
	 */
	public function ClientpathSelect($name, array $opt = []): string {

		$rootpath = dirname( __DIR__ );

		require_once $rootpath."/config.php";
		require_once $rootpath."/dbconnector.php";
		require_once $rootpath."/func.php";

		$sqlname     = $GLOBALS['sqlname'];
		$identity    = $GLOBALS['identity'];
		$db          = $GLOBALS['db'];
		$pathDefault = $GLOBALS['pathDefault'];

		$act = '';

		//print_r($opt);

		if (!$opt['class']) {
			$opt['class'] = 'yw200';
		}

		else if (is_array($opt['class'])) {
			$opt['class'] = yimplode( " ", (array)$opt['class'] );
		}

		$opt['sel'] = ($opt['sel'] != '' || $opt['sel'] == '0') ? $opt['sel'] : $pathDefault;

		if ( !$opt['multiple'] && $opt['sel'] == '-1' ) {
			$opt['sel'] = '';
		}

		if ( $opt['multiple'] ) {
			$act .= " multiple";
		}

		$str = '';

		$result = $db -> getAll("SELECT id, name FROM {$sqlname}clientpath WHERE identity = '$identity' ORDER by name");
		foreach ($result as $data) {

			if (!$opt['multiple']) {
				$str .= '<option value="'.$data['id'].'" '.($data['id'] == $opt['sel'] ? "selected" : "").'>'.$data['name'].'</option>';
			}

			else {
				$str .= '<option value="'.$data['id'].'" class="" '.(in_array( $data['id'], (array)$opt['sel'] ) ? "selected" : "").'>'.$data['name'].'</option>';
			}

		}

		return '
		<select name="'.$name.'" id="'.$name.'" class="'.$opt['class'].'" '.$opt['data'].' '.$act.'>
			'.(isset($opt['noempty']) ? '' : '<option value="">--выбор--</option>').'
			'.$str.'
		</select>';

	}

	/**
	 * Выбор типа отношений - Select
	 *
	 * @param $name      - id/name элемента в форме
	 * @param array $opt - опции
	 *              - class = string|array, css-классы, применяемые к элементу select
	 *              - sel = string, значение элемента, который должен быть выбран (-1 отменяет выбор)
	 *              - noempty = boolean, не позволяет добавить пустой пункт
	 *              - multiple = boolean, преобразует в мультиселект
	 *              - data - data-атрибуты, передаваемые строкой
	 *
	 * @return string
	 */
	public function RelationSelect($name, array $opt = []): string {

		$rootpath = dirname( __DIR__ );

		require_once $rootpath."/config.php";
		require_once $rootpath."/dbconnector.php";
		require_once $rootpath."/func.php";

		$sqlname    = $GLOBALS['sqlname'];
		$identity   = $GLOBALS['identity'];
		$db         = $GLOBALS['db'];
		$relDefault = $GLOBALS['relDefault'];

		$act = '';

		if (!$opt['class']) {
			$opt['class'] = 'yw200';
		}

		else if (is_array($opt['class'])) {
			$opt['class'] = yimplode( " ", $opt['class'] );
		}

		$opt['sel'] = ($opt['sel'] != '' || $opt['sel'] == '0') ? $opt['sel'] : $relDefault;

		if (!$opt['multiple']) {

			if ( $opt['sel'] == '-1' ) {
				$opt['sel'] = '';
			}

		}

		if ( $opt['multiple'] ) {
			$act .= " multiple";
		}

		$str = '';

		$result = $db -> getAll("SELECT id, title FROM {$sqlname}relations WHERE identity = '$identity' ORDER by title");
		foreach ($result as $data) {

			if (!$opt['multiple']) {
				$str .= '<option value="'.$data['title'].'" data-id="'.$data['id'].'" '.($data['title'] == $opt['sel'] ? "selected" : "").'>'.$data['title'].'</option>';
			}

			else {
				$str .= '<option value="'.$data['id'].'" class="" '.(in_array( $data['id'], (array)$opt['sel'] ) ? "selected" : "").'>'.$data['title'].'</option>';
			}

		}

		return '
		<select name="'.$name.'" id="'.$name.'" class="'.$opt['class'].'" '.$opt['data'].' '.$act.'>
			'.(isset($opt['noempty']) ? '' : '<option value="">--выбор--</option>').'
			'.$str.'
		</select>';

	}

	/**
	 * Выбор территории - Select
	 *
	 * @param $name      - id/name элемента в форме
	 * @param array $opt - опции
	 *              - class = string|array, css-классы, применяемые к элементу select
	 *              - sel = integer, id-элемента, который должен быть выбран (-1 отменяет выбор)
	 *              - exclude = string|array, исключенные территории
	 *              - data = string - доп.признаки для элемента
	 *              - noempty = boolean, не позволяет добавить пустой пункт
	 *              - multiple = boolean, преобразует в мультиселект
	 *
	 * @return string
	 */
	public function TerritorySelect($name, array $opt = []): string {

		$rootpath = dirname( __DIR__ );

		require_once $rootpath."/config.php";
		require_once $rootpath."/dbconnector.php";
		require_once $rootpath."/func.php";

		$sqlname  = $GLOBALS['sqlname'];
		$identity = $GLOBALS['identity'];
		$db       = $GLOBALS['db'];

		$sort = '';
		$act = '';

		if (!$opt['class']) {
			$opt['class'] = 'yw200';
		}

		else if (is_array($opt['class'])) {
			$opt['class'] = yimplode( " ", (array)$opt['class'] );
		}

		if (!$opt['multiple']) {

			if ( $opt['sel'] == '-1' ) {
				$opt['sel'] = '';
			}

		}


		if ( $opt['multiple'] ) {
			$act .= " multiple";
		}


		if (isset($opt['exclude'])) {

			if (!is_array($opt['exclude'])) {
				$sort .= " and  idcategory != '".$opt['exclude']."'";
			}

			else {
				$sort .= " and idcategory NOT IN '".yimplode( ",", (array)$opt['exclude'], "'" )."'";
			}

		}

		$str = '';

		$result = $db -> getAll("SELECT idcategory, title FROM {$sqlname}territory_cat WHERE identity = '$identity' $sort ORDER by title");
		foreach ($result as $data) {

			//$str .= '<option value="'.$data['idcategory'].'" '.(isset($opt['sel']) && $data['idcategory'] == $opt['sel'] ? "selected" : "").'>'.$data['title'].'</option>';

			if (!$opt['multiple']) {
				$str .= '<option value="'.$data['idcategory'].'" '.(isset( $opt['sel'] ) && $data['idcategory'] == $opt['sel'] ? "selected" : "").'>'.$data['title'].'</option>';
			}

			else {
				$str .= '<option value="'.$data['idcategory'].'" class="" '.(in_array( $data['idcategory'], (array)$opt['sel'] ) ? "selected" : "").'>'.$data['title'].'</option>';
			}

		}

		return '
		<select name="'.$name.'" id="'.$name.'" class="'.$opt['class'].'" '.$opt['data'].' '.$act.'>
			'.(isset($opt['noempty']) ? '' : '<option value="">--выбор--</option>').'
			'.$str.'
		</select>';

	}

	/**
	 * Выбор отрасли - Select
	 *
	 * @param $name      - id/name элемента в форме
	 * @param array $opt - опции
	 *
	 *              - class = string|array, css-классы, применяемые к элементу select
	 *              - tip = string|array, тип клиента: client, contractor, partner, concurent
	 *              - exclude = string|array, исключенный тип клиента: client, contractor, partner, concurent
	 *              - sel = integer, id-элемента, который должен быть выбран (-1 отменяет выбор)
	 *              - data = string - доп.признаки для элемента
	 *              - noempty = boolean, не позволяет добавить пустой пункт
	 *              - multiple = boolean, преобразует в мультиселект
	 *
	 * @return string
	 */
	public function IndustrySelect($name, array $opt = []): string {

		$rootpath = dirname( __DIR__ );

		require_once $rootpath."/config.php";
		require_once $rootpath."/dbconnector.php";
		require_once $rootpath."/func.php";

		$sqlname  = $GLOBALS['sqlname'];
		$identity = $GLOBALS['identity'];
		$db       = $GLOBALS['db'];

		$sort = '';
		$act = '';

		if (!$opt['class']) {
			$opt['class'] = 'yw200';
		}

		else if (is_array($opt['class'])) {
			$opt['class'] = yimplode( " ", $opt['class'] );
		}

		$opt['tip'] = ($opt['tip'] != '' || $opt['tip'] == '0') ? $opt['tip'] : 'client';

		if (isset($opt['tip'])) {

			if (!is_array($opt['tip'])) {

				if ( $opt['tip'] != 'other' ) {
					$sort .= " and  tip = '".$opt['tip']."'";
				}

				elseif ( $opt['tip'] == 'other' ) {
					$sort .= " and  tip != 'client'";
				}

			}
			else {
				$sort .= " and tip IN '".yimplode( ",", (array)$opt['tip'], "'" )."'";
			}

		}

		if (isset($opt['exclude'])) {

			if (!is_array($opt['exclude'])) {
				$sort .= " and  tip != '".$opt['exclude']."'";
			}

			else {
				$sort .= " and tip NOT IN '".yimplode( ",", (array)$opt['exclude'], "'" )."'";
			}

		}

		if ( !$opt['multiple'] && $opt['sel'] == '-1' ) {
			$opt['sel'] = '';
		}

		if ( $opt['multiple'] ) {
			$act .= " multiple";
		}

		$str = '';

		$result = $db -> getAll("SELECT idcategory, title FROM {$sqlname}category WHERE identity = '$identity' $sort ORDER by title");
		foreach ($result as $data) {

			//$s = (isset($opt['sel']) && $data['idcategory'] == (int)$opt['sel']) ? "selected" : "";
			//$element .= '<option value="'.$data['idcategory'].'" '.$s.'>'.$data['title'].'</option>';

			if (!$opt['multiple']) {
				$str .= '<option value="'.$data['idcategory'].'" '.(isset( $opt['sel'] ) && $data['idcategory'] == $opt['sel'] ? "selected" : "").'>'.$data['title'].'</option>';
			}

			else {
				$str .= '<option value="'.$data['idcategory'].'" class="" '.(in_array( $data['idcategory'], (array)$opt['sel'] ) ? "selected" : "").'>'.$data['title'].'</option>';
			}

		}

		return '
		<select name="'.$name.'" id="'.$name.'" class="'.$opt['class'].'" '.$opt['data'].' '.$act.'>
			'.(isset($opt['noempty']) ? '' : '<option value="">--выбор--</option>').'
			'.$str.'
		</select>';

	}

	/**
	 * Контакт
	 */

	/**
	 * Выбор типа отношений - Select
	 *
	 * @param $name      - id/name элемента в форме
	 * @param array $opt - опции
	 *              - class = string|array, css-классы, применяемые к элементу select
	 *              - sel = string, значение элемента, который должен быть выбран (-1 отменяет выбор)
	 *              - noempty = boolean, не позволяет добавить пустой пункт
	 *              - multiple = boolean, преобразует в мультиселект
	 *              - data = string - доп.признаки для элемента
	 *
	 * @return string
	 */
	public function LoyaltySelect($name, array $opt = []): string {

		$rootpath = dirname( __DIR__ );

		require_once $rootpath."/config.php";
		require_once $rootpath."/dbconnector.php";
		require_once $rootpath."/func.php";

		$sqlname      = $GLOBALS['sqlname'];
		$identity     = $GLOBALS['identity'];
		$db           = $GLOBALS['db'];
		$loyalDefault = $GLOBALS['loyalDefault'];

		$act = $str = '';

		if (!$opt['class']) {
			$opt['class'] = 'yw200';
		}

		else if (is_array($opt['class'])) {
			$opt['class'] = yimplode( " ", $opt['class'] );
		}

		$opt['sel'] = ($opt['sel'] != '' || $opt['sel'] == '0') ? $opt['sel'] : $loyalDefault;

		if (!$opt['multiple']) {

			if ( $opt['sel'] == '-1' ) {
				$opt['sel'] = '';
			}

		}

		if ( $opt['multiple'] ) {
			$act .= " multiple";
		}


		$result = $db -> getAll("SELECT idcategory, title FROM {$sqlname}loyal_cat WHERE identity = '$identity' ORDER by title");
		foreach ($result as $data) {

			//$s = ($data['idcategory'] == $opt['sel']) ? "selected" : "";
			//$str .= '<option value="'.$data['idcategory'].'" '.$s.' data-id="'.$data['id'].'">'.$data['title'].'</option>';

			if (!$opt['multiple']) {
				$str .= '<option value="'.$data['idcategory'].'" '.(isset( $opt['sel'] ) && $data['idcategory'] == $opt['sel'] ? "selected" : "").'>'.$data['title'].'</option>';
			}

			else {
				$str .= '<option value="'.$data['idcategory'].'" class="" '.(in_array( $data['idcategory'], (array)$opt['sel'] ) ? "selected" : "").'>'.$data['title'].'</option>';
			}

		}

		return '
		<select name="'.$name.'" id="'.$name.'" class="'.$opt['class'].'" '.$opt['data'].' '.$act.'>
			'.(isset($opt['noempty']) ? '' : '<option value="">--выбор--</option>').'
			'.$str.'
		</select>';

	}

	/**
	 * Сделка
	 */

	/**
	 * Выбор направления - Select
	 *
	 * @param $name      - id/name элемента в форме
	 * @param array $opt - опции
	 *              - class = string|array, css-классы, применяемые к элементу select
	 *              - sel = integer|array, id-элемента, который должен быть выбран (-1 отменяет выбор), array в случае если multiple = true
	 *              - multiple = boolean, преобразует в мультиселект
	 *              - noempty = boolean, не позволяет добавить пустой пункт
	 *              - data - data-атрибуты, передаваемые строкой
	 *
	 * Example:
	 * ```php
	 * $element = new \Salesman\Elements();
	 * $select = $element -> DirectionSelect("direction", ["class" => "w300", "multiple" => true, "sel" => [1,22,23]]);
	 * ```
	 *
	 * @return string
	 */
	public function DirectionSelect($name, array $opt = []): string {

		$rootpath = dirname( __DIR__ );

		require_once $rootpath."/config.php";
		require_once $rootpath."/dbconnector.php";
		require_once $rootpath."/func.php";

		$sqlname    = $GLOBALS['sqlname'];
		$identity   = $GLOBALS['identity'];
		$db         = $GLOBALS['db'];
		$dirDefault = $GLOBALS['dirDefault'];

		$sort = '';
		$act = '';
		$items = [];

		if (!$opt['class']) {
			$opt['class'] = 'yw200';
		}

		elseif (is_array($opt['class'])) {
			$opt['class'] = yimplode( " ", $opt['class'] );
		}

		if (!$opt['multiple']) {

			$opt['sel'] = ($opt['sel'] != '' || $opt['sel'] == '0') ? $opt['sel'] : $dirDefault;

			if ($opt['sel'] == '-1') {
				$opt['sel'] = '';
			}

		}

		if ( $opt['multiple'] ) {
			$act .= " multiple";
		}


		$result = $db -> getAll("SELECT id, title FROM {$sqlname}direction WHERE identity = '$identity' $sort ORDER by title");
		foreach ($result as $data) {

			if (!$opt['multiple']) {
				$s = (isset( $opt['sel'] ) && $data['id'] == $opt['sel'] ? "selected" : "");
			}
			else {
				$s = (isset( $opt['sel'] ) && in_array( $data['id'], (array)$opt['sel'] ) ? "selected" : "");
			}

			$items[] = '<option value="'.$data['id'].'" '.$s.'>'.$data['title'].'</option>';

		}

		$self = '<option value="">--выбор--</option>';

		if ($opt['noempty'] || $opt['multiple']) {
			$self = '';
		}

		return '
			<select name="'.$name.'" id="'.$name.'" class="'.$opt['class'].'" '.$opt['data'].' '.$act.'>
			'.$self.'
			'.yimplode("", $items).'
			</select>
		';

	}

	/**
	 * Выбор типа сделки - Select
	 *
	 * @param $name      - id/name элемента в форме
	 * @param array $opt - опции
	 *              - class = string|array, css-классы, применяемые к элементу select
	 *              - sel = integer|array, id-элемента, который должен быть выбран (-1 отменяет выбор), array в случае если multiple = true
	 *              - multiple = boolean, преобразует в мультиселект
	 *              - noempty = boolean, не позволяет добавить пустой пункт
	 *              - data - data-атрибуты, передаваемые строкой
	 *
	 * @return string
	 */
	public function DealTypeSelect($name, array $opt = []): string {

		$rootpath = dirname( __DIR__ );

		require_once $rootpath."/config.php";
		require_once $rootpath."/dbconnector.php";
		require_once $rootpath."/func.php";

		$sqlname    = $GLOBALS['sqlname'];
		$identity   = $GLOBALS['identity'];
		$db         = $GLOBALS['db'];
		$tipDefault = $GLOBALS['tipDefault'];

		$sort = '';
		$act = '';
		$items = [];

		if (!$opt['class']) {
			$opt['class'] = 'yw200';
		}
		else if (is_array($opt['class'])) {
			$opt['class'] = yimplode( " ", $opt['class'] );
		}

		if (!$opt['multiple']) {

			$opt['sel'] = ($opt['sel'] != '' || $opt['sel'] == '0') ? $opt['sel'] : $tipDefault;

			if ($opt['sel'] == '-1') {
				$opt['sel'] = '';
			}

		}

		if ( $opt['multiple'] ) {
			$act .= " multiple";
		}


		$result = $db -> getAll("SELECT tid, title FROM {$sqlname}dogtips WHERE identity = '$identity' $sort ORDER by title");
		foreach ($result as $data) {

			if (!$opt['multiple']) {
				$s = (isset( $opt['sel'] ) && $data['tid'] == $opt['sel'] ? "selected" : "");
			}
			else {
				$s = (isset( $opt['sel'] ) && in_array( $data['tid'], $opt['sel'] ) ? "selected" : "");
			}

			$items[] = '<option value="'.$data['tid'].'" '.$s.'>'.$data['title'].'</option>';

		}

		$self = '<option value="">--выбор--</option>';

		if ($opt['noempty'] || $opt['multiple']) {
			$self = '';
		}

		return '
			<select name="'.$name.'" id="'.$name.'" class="'.$opt['class'].'" '.$opt['data'].' '.$act.'>
			'.$self.'
			'.yimplode("", $items).'
			</select>
		';

	}

	/**
	 * Выбор этапа сделки - Select
	 *
	 * @param $name      - id/name элемента в форме
	 * @param array $opt - опции
	 *              - class = string|array, css-классы, применяемые к элементу select
	 *              - sel = integer, id-элемента, который должен быть выбран (-1 отменяет выбор)
	 *              - noempty = boolean, не позволяет добавить пустой пункт
	 *              - nodefault = boolean, если не нужно подставлять дефолтное значение
	 *              - nameAsId = boolean, если в качестве id записи нужно ставить числовое значение
	 *              - data - data-атрибуты, передаваемые строкой
	 *
	 * @return string
	 */
	public function StepSelect($name, array $opt = []): string {

		$rootpath = dirname( __DIR__ );

		require_once $rootpath."/config.php";
		require_once $rootpath."/dbconnector.php";
		require_once $rootpath."/func.php";

		$sqlname     = $GLOBALS['sqlname'];
		$identity    = $GLOBALS['identity'];
		$db          = $GLOBALS['db'];
		$stepDefault = $GLOBALS['stepDefault'];

		$sort = '';
		$act = '';
		$items = [];

		//print_r($opt);

		if (!$opt['class']) {
			$opt['class'] = 'yw200';
		}

		else if (is_array($opt['class'])) {
			$opt['class'] = yimplode( " ", $opt['class'] );
		}

		if(!is_array($opt['sel'])) {

			$opt['sel'] = ($opt['sel'] != '' || $opt['sel'] == '0') ? $opt['sel'] : $stepDefault;

			if ( $opt['sel'] == '-1' || $opt['nodefault'] ) {
				$opt['sel'] = '';
			}

		}

		//print $opt['sel'];

		$result = $db -> getAll("SELECT idcategory, title, content FROM {$sqlname}dogcategory WHERE identity = '$identity' $sort ORDER by title");
		foreach ($result as $data) {

			if (!$opt['multiple']) {

				if ( !$opt['nameAsId'] ) {
					$items[] = '<option value="'.$data['idcategory'].'" '.(isset( $opt['sel'] ) && $data['idcategory'] == $opt['sel'] ? "selected" : "").'>'.$data['title'].'% - '.$data['content'].'</option>';
				}

				else {
					$items[] = '<option value="'.$data['title'].'" '.(isset( $opt['sel'] ) && $data['title'] == $opt['sel'] ? "selected" : "").'>'.$data['title'].'% - '.$data['content'].'</option>';
				}

			}
			else{

				if ( !$opt['nameAsId'] ) {
					$items[] = '<option value="'.$data['idcategory'].'" '.(isset( $opt['sel'] ) && in_array( $data['idcategory'], (array)$opt['sel'] ) ? "selected" : "").'>'.$data['title'].'% - '.$data['content'].'</option>';
				}

				else {
					$items[] = '<option value="'.$data['title'].'" '.(isset( $opt['sel'] ) && in_array( $data['title'], (array)$opt['sel'] ) ? "selected" : "").'>'.$data['title'].'% - '.$data['content'].'</option>';
				}

			}

		}

		$self = '<option value="">--выбор--</option>';

		if ($opt['noempty'] || $opt['multiple']) {
			$self = '';
		}

		return '
			<select name="'.$name.'" id="'.$name.'" class="'.$opt['class'].'" '.$opt['data'].' '.$act.'>
			'.$self.'
			'.yimplode("", $items).'
			</select>
		';

	}

	/**
	 * Выбор этапа сделки из мультиворонки - Select
	 *
	 * @param $name      - id/name элемента в форме
	 * @param array $opt - опции
	 *              - direction = integer, id Направления
	 *              - tip = integer, id Типа сделки
	 *              - class = string|array, css-классы, применяемые к элементу select
	 *              - sel = integer, id-элемента, который должен быть выбран (-1 отменяет выбор)
	 *              - noempty = boolean, не позволяет добавить пустой пункт
	 *              - data - data-атрибуты, передаваемые строкой
	 *
	 * @return string
	 */
	public function StepSelectFromFunnel($name, array $opt = []): string {

		$rootpath = dirname( __DIR__ );

		require_once $rootpath."/config.php";
		require_once $rootpath."/dbconnector.php";
		require_once $rootpath."/func.php";

		$act = '';
		$items = [];

		$direction = $opt['direction'];
		$tip       = $opt['tip'];

		$steps = getMultiStepList([
			"direction" => $direction,
			"tip"       => $tip
		]);

		$stepDefault = $steps['nsteps']['default'];

		if (!$opt['class']) {
			$opt['class'] = 'yw200';
		}
		else if (is_array($opt['class'])) {
			$opt['class'] = yimplode( " ", $opt['class'] );
		}

		$opt['sel'] = ($opt['sel'] != '' || $opt['sel'] == '0') ? $opt['sel'] : $stepDefault;

		if ($opt['sel'] == '-1') {
			$opt['sel'] = '';
		}

		foreach ((array)$steps['nsteps'] as $step){

			$s = (isset($opt['sel']) && $step['id'] == $opt['sel']) ? "selected" : "";

			$items[] = '<option value="'.$step['id'].'" '.$s.'>'.$step['name'].'% - '.$step['content'].'</option>';

		}

		$self = '<option value="">--выбор--</option>';

		if ($opt['noempty'] || $opt['multiple']) {
			$self = '';
		}

		return '
			<select name="'.$name.'" id="'.$name.'" class="'.$opt['class'].'" '.$opt['data'].' '.$act.'>
			'.$self.'
			'.yimplode("", $items).'
			</select>
		';

	}

	/**
	 * Выбор статуса закрытия сделки - Select
	 *
	 * @param $name      - id/name элемента в форме
	 * @param array $opt - опции
	 *              - class = string|array, css-классы, применяемые к элементу select
	 *              - sel = integer, id-элемента, который должен быть выбран
	 *              - other = string, атрибуты input
	 *              - data - data-атрибуты, передаваемые строкой
	 *              - multiple = boolean, преобразует в мультиселект
	 *
	 * @return string
	 */
	public function CloseStatusSelect($name, array $opt = []): string {

		$rootpath = dirname( __DIR__ );

		require_once $rootpath."/config.php";
		require_once $rootpath."/dbconnector.php";
		require_once $rootpath."/func.php";

		$sqlname  = $GLOBALS['sqlname'];
		$identity = $GLOBALS['identity'];
		$db       = $GLOBALS['db'];

		$sort = '';
		$act = '';
		$items = [];

		if (!$opt['class']) {
			$opt['class'] = 'yw200';
		}
		elseif (is_array($opt['class'])) {
			$opt['class'] = yimplode( " ", (array)$opt['class'] );
		}

		$result = $db -> getAll("SELECT sid, title, result_close FROM {$sqlname}dogstatus WHERE identity = '$identity' $sort ORDER by title");
		foreach ($result as $data) {

			if (!$opt['multiple']) {
				$items[] = '<option value="'.$data['sid'].'" '.(isset( $opt['sel'] ) && $data['sid'] == $opt['sel'] ? "selected" : "").' data-id="'.$data['result_close'].'" data-content="'.$data['content'].'">'.$data['title'].'</option>';
			}
			else {
				$items[] = '<option value="'.$data['sid'].'" '.(isset( $opt['sel'] ) && in_array( $data['sid'], (array)$opt['sel'] ) ? "selected" : "").' data-id="'.$data['result_close'].'" data-content="'.$data['content'].'">'.$data['title'].'</option>';
			}

		}

		return '
			<select name="'.$name.'" id="'.$name.'" class="'.$opt['class'].'" '.$opt['data'].' '.$act.' '.$opt['other'].'>
			'.($opt['noempty'] || $opt['multiple'] ? '' : '<option value="">--выбор--</option>').'
			'.yimplode("", $items).'
			</select>
		';

	}

	/**
	 * Выбор расчетного счета - Select
	 *
	 * @param $name      - id/name элемента в форме
	 * @param array $opt - опции
	 *              - mcid = integer, id компании
	 *              - class = string|array, css-классы, применяемые к элементу select
	 *              - sel = integer, id-элемента, который должен быть выбран (-1 отменяет выбор)
	 *              - noempty = boolean, не позволяет добавить пустой пункт
	 *              - data - data-атрибуты, передаваемые строкой
	 *
	 * @return string
	 */
	public function rsSelect($name, array $opt = []): string {

		$rootpath = dirname( __DIR__ );

		require_once $rootpath."/config.php";
		require_once $rootpath."/dbconnector.php";
		require_once $rootpath."/func.php";

		$sqlname     = $GLOBALS['sqlname'];
		$identity    = $GLOBALS['identity'];
		$db          = $GLOBALS['db'];

		$sort = '';
		$act = '';
		$items = [];

		if (!$opt['class']) {
			$opt['class'] = 'yw200';
		}
		elseif (is_array($opt['class'])) {
			$opt['class'] = yimplode( " ", $opt['class'] );
		}

		$opt['sel'] = ($opt['sel'] != '' || $opt['sel'] == '0') ? $opt['sel'] : "";

		if ($opt['sel'] == '-1') {
			$opt['sel'] = '';
		}

		if(isset($opt['mcid'])) {
			$sort = " and id = '$opt[mcid]'";
		}

		$result = $db -> getAll("SELECT * FROM {$sqlname}mycomps WHERE identity = '$identity' $sort ORDER by name_shot");
		foreach ($result as $data) {

			if (!$opt['multiple']) {
				$items[] = '<optgroup label="'.$data['name_shot'].'">';
			}

			$xsort = isset($opt['active']) ? " and bloc != 'yes'" : "";

			$res = $db -> query("SELECT * FROM {$sqlname}mycomps_recv WHERE cid = '$data[id]' $xsort and identity = '$identity' ORDER BY title");
			while ($da = $db -> fetch($res)) {

				if (!$opt['multiple']) {
					$s = (isset($opt['sel']) && $da['id'] == $opt['sel']) || ($opt['sel'] == '' && $da['isDefault'] == 'yes') ? "selected" : "";
					$items[] = '<option value="'.$da['id'].'" '.$s.'>'.$da['title'].'</option>';
				}
				else {
					$s = isset( $opt['sel'] ) && in_array( $da['id'], (array)$opt['sel'] ) ? "selected" : "";
					$items[] = '<option value="'.$da['id'].'" '.$s.'>'.$da['title'].' ['.$data['name_shot'].']'.'</option>';
				}


			}

		}

		$self = '<option value="">--выбор--</option>';

		if ($opt['noempty'] || $opt['multiple']) {
			$self = '';
		}

		if ( $opt['multiple'] ) {
			$act .= " multiple";
		}

		return '
			<select name="'.$name.'" id="'.$name.'" class="'.$opt['class'].'" '.$opt['data'].' '.$act.'>
			'.$self.'
			'.yimplode("", $items).'
			</select>
		';

	}

	/**
	 * Вывод компаний
	 *
	 * @param       $name
	 * @param array $opt - опции
	 *              - mcid = integer, id компании
	 *              - class = string|array, css-классы, применяемые к элементу select
	 *              - sel = integer, id-элемента, который должен быть выбран (-1 отменяет выбор)
	 *              - noempty = boolean, не позволяет добавить пустой пункт
	 *              - data - data-атрибуты, передаваемые строкой
	 * @return string
	 */
	public function mycompSelect($name, array $opt = []): string {

		$rootpath = dirname( __DIR__ );

		require_once $rootpath."/config.php";
		require_once $rootpath."/dbconnector.php";
		require_once $rootpath."/func.php";

		$sqlname     = $GLOBALS['sqlname'];
		$identity    = $GLOBALS['identity'];
		$db          = $GLOBALS['db'];

		$sort = '';
		$act = '';
		$items = [];

		if (!$opt['class']) {
			$opt['class'] = 'yw200';
		}
		elseif (is_array($opt['class'])) {
			$opt['class'] = yimplode( " ", $opt['class'] );
		}

		$opt['sel'] = ($opt['sel'] != '' || $opt['sel'] == '0') ? $opt['sel'] : "";

		if ($opt['sel'] == '-1') {
			$opt['sel'] = '';
		}

		if(isset($opt['mcid'])) {
			$sort = " and id = '$opt[mcid]'";
		}

		$result = $db -> getAll("SELECT * FROM {$sqlname}mycomps WHERE identity = '$identity' $sort ORDER by name_shot");
		foreach ($result as $data) {

			$s = ( isset($opt['sel']) && $data['id'] == $opt['sel'] ) ? "selected" : "";

			$items[] = '<option value="'.$data['id'].'" '.$s.'>'.$data['name_shot'].'</option>';

		}

		$self = '<option value="">--выбор--</option>';

		if ($opt['noempty'] || $opt['multiple']) {
			$self = '';
		}

		$act .= ($opt['jsact'] != '') ? 'onchange="'.$opt['jsact'].'"' : '';

		return '
			<select name="'.$name.'" id="'.$name.'" class="'.$opt['class'].'" '.$act.'>
			'.$self.'
			'.yimplode("", $items).'
			</select>
		';

	}

	/**
	 * Формирование html-элемента "Список"
	 *
	 * @param $name       - id/name элемента в форме
	 * @param array $data - данные для формирования блока
	 *              - id - значение элемента
	 *              - title - наименование элемента
	 * @param array $opt  - опции
	 *              - class = string|array, css-классы, применяемые к элементу select
	 *              - sel = integer, id-элемента, который должен быть выбран
	 *              - req = yes|'', признак обязательного выбора значения
	 *              - other = string, атрибуты input
	 *              - multiple = boolean, добавляет возможность выбора нескольких вариантов
	 *              - emptyValue - значение не выбранного элемента (пусто)
	 *              - emptyText - текст не выбранного элемента (--Выбор--)
	 *
	 * @return string
	 */
	public static function Select($name, array $data = [], array $opt = []): string {

		$str = '';
		$multiple = '';
		$class = '';

		if (!$opt['class']) {
			$class = 'wp100';
		}

		elseif (is_array($opt['class'])) {
			$class = yimplode( " ", $opt['class'] );
		}

		else {
			$class = $opt['class'];
		}

		if(isset($opt['sel']) && !is_array($opt['sel'])) {
			$opt['sel'] = yexplode( ",", str_replace( ";", ",", $opt['sel'] ) );
		}


		$sel = (!$opt['sel']) ? "selected" : '';

		$emptyText = $opt['emptyText'] ?? '--Выбор--';

		if ($opt['req'] == '' && !isset($opt['multiple'])) {
			$str .= '<option value="'.$opt['emptyValue'].'" '.$sel.'>'.$emptyText.'</option>';
		}

		if(!empty($data)) {
			foreach ( $data as $d ) {

				$str .= '<option value="'.$d['id'].'" '.(in_array( $d['id'], (array)$opt['sel'] ) ? "selected" : "").'>'.$d['title'].'</option>';

			}
		}

		$propeties = (is_array($opt['other'])) ? yimplode(" ", $opt['other']) : $opt['other'];

		if(isset($opt['multiple']) && $opt['multiple'] ) {

			$multiple = 'multiple';
			$class .= ' multiselect';

		}

		if(!isset($opt['nowrapper'])) {
			$element = '
			<span class="select">
				<select name="'.$name.'" id="'.$name.'" class="'.$class.'" '.$multiple.' '.$propeties.'>'.$str.'</select>
			</span>';
		}
		else {
			$element = '<select name="'.$name.'" id="'.$name.'" class="'.$class.'" '.$multiple.' '.$propeties.'>'.$str.'</select>';
		}

		if(isset($opt['multiple']) && $opt['multiple'] && $opt['multipleInit'] ) {
			$element .= '
			<script>
				$("#'.$name.'").multiselect({sortable: true, searchable: true});
			</script>
			';
		}

		return $element;

	}

	/**
	 * Формирование html-элемента "Список множественного выбора"
	 *
	 * @param $name       - id/name элемента в форме
	 * @param array $data - данные для формирования блока
	 *              - id - значение элемента
	 *              - title - наименование элемента
	 * @param array $opt  - опции
	 *              - class = string|array, css-классы, применяемые к элементу input
	 *              - sel = array, массив выбранных элементов
	 *              - func = функция отправки значений списка, если нужна встроенная кнопка
	 *              - other = string, атрибуты input
	 *
	 * @return string
	 */
	public static function MultiSelect($name, array $data = [], array $opt = []): string {

		$str = '';

		if (!$opt['class']) {
			$opt['class'] = 'taskss';
		}

		elseif (is_array($opt['class'])) {
			$opt['class'] = yimplode( " ", $opt['class'] );
		}

		$func = ($opt['func']) ? '<div class="yDoit action button hidden" onClick="'.$opt['func'].'">Применить</div>' : '';

		if(!empty($data)) {
			foreach ( $data as $key => $value ) {

				if ( is_array( $value ) ) {
					$str .= '
					<div class="ydropString ellipsis">
						<label>
							<input class="taskss '.$opt['class'].'" name="'.$name.'[]" type="checkbox" id="'.$name.'[]" value="'.$value['id'].'" '.(in_array( $value['id'], (array)$opt['sel'] ) ? "checked" : "").' '.$opt['other'].'>&nbsp;'.$value['title'].'
						</label>
					</div>';
				}

				else {
					$str .= '
					<div class="ydropString ellipsis">
						<label>
							<input class="taskss '.$opt['class'].'" name="'.$name.'[]" type="checkbox" id="'.$name.'[]" value="'.$key.'" '.(in_array( $key, $opt['sel'] ) ? "checked" : "").' '.$opt['other'].'>&nbsp;'.$value.'
						</label>
					</div>';
				}

			}
		}

		return '
		<div class="ydropDown" data-id="fields" >
			<span class="ydropCount">'.count((array)$opt['sel']).' выбрано</span>
			<i class="icon-angle-down pull-aright"></i>'
			.$func.
			'<div class="yselectBox fields" data-id="roles" style="max-height: 50vh; z-index: 1">
				<div class="right-text">
					<div class="ySelectAll w0 inline" title="Выделить всё"><i class="icon-plus-circled"></i>Всё</div>
					<div class="yunSelect w0 inline" title="Снять выделение"><i class="icon-minus-circled"></i>Ничего</div>
				</div>
				'.$str.'
			</div>
		</div>';

	}

	/**
	 * Формирование html-элемента "Переключатели"
	 *
	 * @param $name       - id/name элемента в форме
	 * @param array $data - данные для формирования блока
	 *              - id - значение элемента
	 *              - title - наименование элемента
	 * @param array $opt  - опции
	 *              - class = string|array, css-классы, применяемые к элементу input
	 *              - sel = integer, id выбранного элемента
	 *              - other = string, атрибуты input
	 *
	 * @return string
	 */
	public static function Radio($name, array $data = [], array $opt = []): string {

		$su = '';

		if (!$opt['class']) {
			$opt['class'] = 'wp100';
		}

		elseif (is_array($opt['class'])) {
			$opt['class'] = yimplode( " ", $opt['class'] );
		}

		if (is_array($opt['subclass'])) {
			$opt['subclass'] = yimplode( " ", $opt['subclass'] );
		}

		if (!isset($opt['sel'])) {
			$opt['sel'] = $data[0]['id'];
		}

		foreach($data as $item) {
			$su .= '
			<div class="inline pr15 ml5 mt5 '.$opt['radioclass'].'">
				<div class="radio">
					<label>
						<input name="'.$name.'" type="radio" id="'.$name.'" '.($item == $opt['sel'] ? 'checked' : '').' value="'.$item.'" class="'.$opt['class'].'" '.$opt['other'].'>
						<span class="custom-radio success1"><i class="icon-radio-check"></i></span>
						<span class="title">'.$item.'</span>
					</label>
				</div>
			</div>';
		}

		if($opt['empty']) {
			$su .= '
			<div class="inline pr15 ml5 mt5 '.$opt['radioclass'].' graybg-sub">
				<div class="radio">
					<label>
						<input name="'.$name.'" type="radio" id="'.$name.'" '.(($opt['sel'] == -1) ? 'checked' : '').' value="" class="'.$opt['class'].'" '.$opt['other'].'>
						<span class="custom-radio secondary"><i class="icon-radio-check"></i></span>
						<span class="title gray">Не выбрано</span>
					</label>
				</div>
			</div>';
		}

		return '
		<div class="flex-container mb10  box--child '.$opt['mainclass'].'">
			<div class="flex-string wp80 pl10 relativ">
				'.$su.'
			</div>
		</div>';

	}

	/**
	 * Формирование адресного поля
	 *
	 * @param $name             - id/name элемента в форме
	 * @param string|null $text - текущий текст
	 * @param array $opt        - опции
	 *              - class = string|array, css-классы, применяемые к элементу textarea
	 *              - other = string, атрибуты input
	 *
	 * @return string
	 */
	public static function Adres($name, string $text = NULL, array $opt = []): string {

		return '
		<input type="text" name="'.$name.'" id="'.$name.'" class="'.$opt['class'].'" value="'.$text.'" '.$opt['other'].' data-type="address">
		<script>
			$(\'input[data-type="address"]\').suggestions({
				token: $dadata,
				type: "ADDRESS",
				count: 5,
				formatResult: formatResult,
				formatSelected: formatSelected,
				onSelect: function (suggestion) {
					//console.log(suggestion);
				},
				addon: "clear",
				geoLocation: true
			});
		</script>
		';

	}

	/**
	 * Формирование поля даты с подгружающимся календарем
	 *
	 * @param $name             - id/name элемента в форме
	 * @param string|null $text - исходная дата
	 * @param array $opt        - опции
	 *              - class = string|array, css-классы, применяемые к элементу textarea
	 *              - other = string, атрибуты input
	 *
	 * @return string
	 */
	public static function Date($name, string $text = NULL, array $opt = []): string {

		return '
		<input type="text" name="'.$name.'" id="'.$name.'" class="'.$opt['class'].'" value="'.$text.'" '.$opt['other'].'>
		<script type="text/javascript">
			$(function () {
				$("#'.$name.'").datepicker({
					dateFormat: \'yy-mm-dd\',
					firstDay: 1,
					dayNamesMin: [\'Вс\', \'Пн\', \'Вт\', \'Ср\', \'Чт\', \'Пт\', \'Сб\'],
					monthNamesShort: [\'Январь\', \'Февраль\', \'Март\', \'Апрель\', \'Май\', \'Июнь\', \'Июль\', \'Август\', \'Сентябрь\', \'Октябрь\', \'Ноябрь\', \'Декабрь\'],
					changeMonth: true,
					changeYear: true
				});
			});
		</script>';

	}

	/**
	 * Формирование поля Дата/Время с подгружающимся календарем и часами
	 *
	 * @param $name             - id/name элемента в форме
	 * @param string|null $text - исходная дата
	 * @param array $opt        - опции
	 *              - class = string|array, css-классы, применяемые к элементу textarea
	 *              - other = string, атрибуты input
	 *
	 * @return string
	 */
	public static function DateTime($name, string $text = NULL, array $opt = []): string {

		$element = '
		<input type="text" name="'.$name.'" id="'.$name.'" class="'.$opt['class'].'" value="'.$text.'" '.$opt['other'].'>
		<script type="text/javascript">
		
			$(function () {
					
				var date = new Date();
					
				$("#'.$name.'").datetimepicker({
					timeInput: false,
					timeFormat: \'HH:mm\',
					oneLine: true,
					showSecond: false,
					showMillisec: false,
					showButtonPanel: true,
					timeOnlyTitle: \'Выберите время\',
					timeText: \'Время\',
					hourText: \'Часы\',
					minuteText: \'Минуты\',
					secondText: \'Секунды\',
					millisecText: \'Миллисекунды\',
					timezoneText: \'Часовой пояс\',
					currentText: \'Текущее\',
					closeText: \'<i class="icon-ok-circled"></i>\',
					dateFormat: \'yy-mm-dd\',
					firstDay: 1,
					dayNamesMin: [\'Вс\', \'Пн\', \'Вт\', \'Ср\', \'Чт\', \'Пт\', \'Сб\'],
					monthNamesShort: [\'Январь\', \'Февраль\', \'Март\', \'Апрель\', \'Май\', \'Июнь\', \'Июль\', \'Август\', \'Сентябрь\', \'Октябрь\', \'Ноябрь\', \'Декабрь\'],
					changeMonth: true,
					changeYear: true,
					//yearRange: (date.getFullYear() - 50) + \':\' + (date.getFullYear() + 5),
					minDate: new Date(date.getFullYear(), date.getMonth(), date.getDate(), (date.getHours() - 1))
				});
			});
			
		</script>';

		return $element;

	}

	/**
	 * Формирование html-элемента "Текстовое поле"
	 *
	 * @param $name             - id/name элемента в форме
	 * @param string|null $text - текущий текст
	 * @param array $opt        - опции
	 *              - class = string|array, css-классы, применяемые к элементу textarea
	 *              - other = string, атрибуты input
	 *
	 * @return string
	 */
	public static function InputText($name, string $text = NULL, array $opt = []): string {

		$element = '
		<input type="text" name="'.$name.'" id="'.$name.'" class="'.$opt['class'].'" value="'.$text.'" '.$opt['other'].'>
		<div class="idel mt5" style="height:20px">
			<i title="Очистить" onclick="$(\'#'.$name.'\').val(\'\');" class="icon-block red hand mr10"></i>
		</div>';

		return $element;

	}

	/**
	 * Формирование html-элемента "Многострочное текстовое поле"
	 *
	 * @param $name             - id/name элемента в форме
	 * @param string|null $text - текущий текст
	 * @param array $opt        - опции
	 *              - class = string|array, css-классы, применяемые к элементу textarea
	 *              - other = string, дополнительные атрибуты
	 *
	 * @return string
	 */
	public static function TextArea($name, string $text = NULL, array $opt = []): string {

		if (!$opt['class']) {
			$opt['class'] = 'wp100';
		}
		elseif (is_array($opt['class'])) {
			$opt['class'] = yimplode( " ", (array)$opt['class'] );
		}

		return '
		<textarea name="'.$name.'" id="'.$name.'" class="'.$opt['class'].'" style="height: 150px;" placeholder="Начните вводить текст" '.$opt['other'].'>'.$text.'</textarea>
		';

	}

	/**
	 * Напоминание, Активность
	 */

	/**
	 * Список активностей
	 *
	 * @param $name
	 * @param array $tip (task, history, all)
	 * @param array $opt - массив активностей (если нужно)
	 *
	 * @return string
	 */
	public static function TaskTypesSelect($name, array $tip = ["task", "all"], array $opt = []): string {

		$rootpath = dirname( __DIR__ );

		require_once $rootpath."/config.php";
		require_once $rootpath."/dbconnector.php";
		require_once $rootpath."/func.php";

		$sqlname  = $GLOBALS['sqlname'];
		$identity = $GLOBALS['identity'];
		$db       = $GLOBALS['db'];

		$str = '';

		if (!$opt['class']) {
			$opt['class'] = 'wp100';
		}

		elseif (is_array($opt['class'])) {
			$opt['class'] = yimplode( " ", $opt['class'] );
		}


		if ($opt['req'] == '') {
			$str .= '<option value="" '.(!$opt['sel'] ? "selected" : '').'>--Выбор--</option>';
		}

		$res = $db -> getAll("SELECT * FROM {$sqlname}activities WHERE filter IN (".yimplode(",", $tip, "'").") and identity = '$identity' ORDER by aorder");
		foreach ($res as $data) {

			$str .= '<option value="'.$data['title'].'" style="color:'.$data['color'].'" '.($data['title'] == $opt['sel'] ? "selected" : "").'>'.$data['title'].'</option>';

		}

		$element = '
		<select name="'.$name.'" id="'.$name.'" class="'.$opt['class'].' required" data-change="activities" data-id="des">
			'.$str.'
		</select>
		';

		return $element;

	}

	/**
	 * Массив активностей
	 *
	 * @param array $tip (task, history, all)
	 *
	 * @return array
	 */
	public static function TaskTypes(array $tip = ["task", "all"]): array {

		$rootpath = dirname( __DIR__ );

		require_once $rootpath."/config.php";
		require_once $rootpath."/dbconnector.php";
		require_once $rootpath."/func.php";

		$sqlname  = $GLOBALS['sqlname'];
		$identity = $GLOBALS['identity'];
		$db       = $GLOBALS['db'];

		$element = [];

		$res = $db -> getAll("SELECT * FROM {$sqlname}activities WHERE filter IN (".yimplode(",", $tip, "'").") and identity = '$identity' ORDER by aorder");
		foreach ($res as $data) {

			$element[ $data['id'] ] = [
				"title" => $data['title'],
				"color" => $data['color'],
				"icon"  => get_ticon($data['tip'])
			];

		}

		return $element;

	}

}