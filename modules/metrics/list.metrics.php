<?php
/* ============================ */
/*         SalesMan CRM         */
/* ============================ */
/* (C) 2018 Vladislav Andreev   */
/*       SalesMan Project       */
/*        www.isaler.ru         */
/*        ver. 2018.x           */

/* ============================ */

use Salesman\Metrics;

error_reporting( E_ERROR );
header( "Pragma: no-cache" );

$rootpath = dirname(__DIR__, 2);

include $rootpath."/inc/config.php";
include $rootpath."/inc/dbconnector.php";
include $rootpath."/inc/auth.php";
include $rootpath."/inc/func.php";
include $rootpath."/inc/settings.php";
include $rootpath."/inc/language/".$language.".php";

$thisfile = basename( __FILE__ );

$year       = (!empty( $_REQUEST['year'] )) ? (int)$_REQUEST['year'] : (int)date( 'Y' );
$iduser     = (int)$_REQUEST['iduser'];
$action     = $_REQUEST['action'];
$onlyactive = $_REQUEST['onlyactive'];

$userSettings = $GLOBALS['userSettings'];

//единицы измерения
$edizms = Metrics ::metricEdizm();

/**
 * Список пользователей
 */
if ( $action == 'users' ) {

	$roles = $_REQUEST['role'];

	$onlyactive = $onlyactive == 'yes';
	$sort       = '';

	$users = get_people( $iduser1, 'yes', $onlyactive );

	//print_r($users);

	if ( !empty( $users ) && $isadmin != 'on' )
		$sort .= " and iduser IN (".implode( ",", (array)$users ).")";
	if ( !empty( $roles ) )
		$sort .= " and tip IN (".yimplode( ",", (array)$roles, "'" ).")";
	if ( $onlyactive )
		$sort .= " and secrty = 'yes'";

	$result = $db -> query( "SELECT * FROM {$sqlname}user WHERE identity = '$identity' $sort ORDER BY field(secrty, 'yes', 'no'), mid, tip" );

	while ($data = $db -> fetch( $result )) {

		$prava = (array)yexplode( ";", (string)$data['acs_import'] );

		$list[] = [
			"iduser" => (int)$data['iduser'],
			"title"  => $data['title'],
			"tip"    => $data['tip'],
			"status" => $data['user_post'],
			"avatar" => ($data['avatar']) ? "cash/avatars/".$data['avatar'] : "/assets/images/noavatar.png",
			"boss"   => current_user( $data['mid'] ),
			"active" => $data['secrty'] == 'yes'
		];

	}

}

/**
 * Данные по сотруднику
 */
if ( $action == 'user' ) {

	$user = $plan = $havekpi = [];

	//Данные сотрудника
	$u     = $db -> getRow( "SELECT * FROM {$sqlname}user WHERE iduser = '$iduser' AND identity = '$identity'" );
	$prava = (array)yexplode( ";", (string)$user['acs_import'] );

	$user = [
		[
			"id"    => "title",
			"name"  => "Имя",
			"value" => $u['title'],
			"class" => "Bold blue"
		],
		[
			"id"    => "role",
			"name"  => "Роль в системе",
			"value" => $u['tip'],
			"class" => "Bold"
		],
		[
			"id"    => "CompStart",
			"name"  => "Дата приема",
			"value" => format_date_rus_name( $u['CompStart'] ),
			"class" => "Bold"
		],
		[
			"id"    => "boss",
			"name"  => "Руководитель",
			"value" => ($u['mid'] > 0) ? current_user( $u['mid'] ) : false,
			"class" => "Bold"
		]
	];

	$f = new Metrics();

	$total = [];

	//План по продажам
	$m = 0;
	while ($m++ < 12) {

		$fact = $f -> getPlanDo( $iduser, $year, $m );

		$r = $db -> getRow( "SELECT SUM(kol_plan) as summa, SUM(marga) as marga FROM {$sqlname}plan WHERE iduser = '$iduser' AND year = '$year' AND mon = '$m' AND identity = '$identity'" );

		$plan[] = [
			"month"  => ru_mon( $m ),
			"summa"  => num_format( (float)$r['summa'] ),
			"fsumma" => num_format( (float)$fact['summa'] ),
			"marga"  => num_format( (float)$r['marga'] ),
			"fmarga" => num_format( (float)$fact['marga'] )
		];

		$total['summa']  += $r['summa'];
		$total['fsumma'] += $fact['summa'];
		$total['marga']  += $r['marga'];
		$total['fmarga'] += $fact['marga'];

		unset( $fact );

	}

	$total['summa']  = num_format( (float)$total['summa'] );
	$total['fsumma'] = num_format( (float)$total['fsumma'] );
	$total['marga']  = num_format( (float)$total['marga'] );
	$total['fmarga'] = num_format( (float)$total['fmarga'] );

	//показатели KPI
	$havekpi = Metrics ::getUserKPI( [
		"iduser"   => $iduser,
		"year"     => $year,
		"as_money" => true
	] );

	$list = [
		"iduser"   => $iduser,
		"name"     => $u['title'],
		"user"     => $user,
		"haveplan" => $u['acs_plan'] == 'on',
		"edit"     => $u['acs_plan'] == 'on' && (str_contains($tipuser, 'Руководитель') || $isadmin == 'on'),
		"plan"     => $plan,
		"total"    => $total,
		"year"     => $year,
		"avatar"   => ($u['avatar']) ? "/cash/avatars/".$u['avatar'] : "/assets/images/noavatar.png",
		"havekpi"  => (!empty( $havekpi )) ? ["kpi" => $havekpi] : false,
		"editkpi"  => $isadmin == 'on' || $userSettings['kpiEditor'] == 'yes',
	];


}

/**
 * Просмотр выполнения KPI
 */
if ( $action == 'user.kpido' ) {

	$id     = (int)$_REQUEST['id'];
	$year   = (int)$_REQUEST['year'];
	$month  = (int)$_REQUEST['month'];
	$datum  = $_REQUEST['datum'];
	$export = $_REQUEST['export'];

	if ( isset( $datum ) ) {

		$year  = get_year( $datum );
		$month = getMonth( $datum );

	}

	//список месяцев для period = day
	$months = [];

	$kpiDo = [];
	$year  = (!isset( $year ) || $year == '') ? date( 'Y' ) : $year;
	$month = (!isset( $month ) || $month == '') ? date( 'm' ) : $month;
	$count = 0;

	$userKPI = Metrics ::getUserKPI( ["id" => $id] );
	$kpi     = (int)$userKPI['kpi'];

	//print_r($userKPI);
	//$year  = '2018';
	//$month = '01';

	$KPI = new Metrics();

	// сезонные коэффициенты к плану
	$kpiSeason = $KPI -> getSeason( (int)$year );

	//формируем данные факических показателей
	switch ($userKPI['period']) {

		//перебираем дни указанного месяца
		case 'day':

			$count = (int)date( "t", mktime( 1, 0, 0, (int)$month, 1, (int)$year ) );
			$d1    = strftime( '%Y-%m-%d', mktime( 1, 0, 0, (int)$month, 1, (int)$year ) );
			$d2    = strftime( '%Y-%m-%d', mktime( 1, 0, 0, (int)$month, $count, (int)$year ) );

			// применяем сезонный коэффициент
			$userKPI['value'] *= pre_format( $kpiSeason[ $month ] );

			for ( $day = 1; $day <= $count; $day++ ) {

				$d = strftime( '%Y-%m-%d', mktime( 1, 0, 0, (int)$month, (int)$day, (int)$year ) );

				$calc    = $KPI -> calculateKPI( $iduser, $kpi, '', $d );
				$percent = $kpiSeason[ $month ] > 0 ? round( 100 * ($calc / $userKPI['value']), 2 ) : 0;

				$kpiDo[] = [
					"period"  => format_date_rus( $d ),
					"value"   => num_format( $calc ),
					"percent" => $percent,
					"bgcolor" => ($d == current_datum()) ? "orangebg-sub" : "",
					"edizm"   => $edizms[ $userKPI['tip'] ]
				];

			}

			for ( $m = 1; $m <= 12; $m++ ) {

				$months[] = [
					"name"     => $m,
					"title"    => $lang['face']['MounthName'][ $m - 1 ],
					"selected" => ($m == (int)$month) ? 'selected' : false,
					"edizm"    => $edizms[ $userKPI['tip'] ]
				];

			}

		break;
		case 'week':

			//первый день месяца
			$firstDay = $year."-".$month."-01";

			// последний день месяца
			$lastDay = $year."-".$month."-".date( "t", date_to_unix( $firstDay ) );

			// номер недели начала месяца
			$count1 = (int)date( 'W', strtotime( $firstDay ) );
			// номер недели конца месяца
			$count2 = (int)date( 'W', strtotime( $lastDay ) );

			//число недель в месяце
			$count = $count2 - $count1;

			//начальное значение текущего дня
			$currentUnix = date_to_unix( $firstDay );

			for ( $week = 1; $week <= $count; $week++ ) {

				// применяем сезонный коэффициент
				//$userKPI[ 'value' ] *= pre_format($kpiSeason[ $month ]);
				$userKPI['value'] = pre_format( $userKPI['value'] ) * $kpiSeason[ (int)$month ];

				//текущая дата (не всегда месяц начинается в Понедельник)
				$d1 = date( 'Y-m-d', $currentUnix );
				//дата воскресенья
				$d2 = date( 'Y-m-d', strtotime( "next Sunday", $currentUnix ) );

				//если дата больше, чем последний день месяца, то устанавливаем как последний день месяца
				if ( getDay( $d2 ) > date( "t", $firstDay ) ) {
					$d2 = $year."-".$month."-".date( "t", $firstDay );
				}

				//следующий понедельник, для следующей итерации
				$currentUnix = strtotime( "next Monday", $currentUnix );

				/**
				 * Алгоритм не верный, работает только если месяц начинается в понедельник
				 */
				/*
				$d1 = date('Y-m-d', $week * 7 * 86400 + strtotime('1/1/'.$year) - date('w', strtotime('1/1/'.$year)) * 86400 + 86400);
				$d2 = date('Y-m-d', ($week + 1) * 7 * 86400 + strtotime('1/1/'.$year) - date('w', strtotime('1/1/'.$year)) * 86400);
				print $week.": ".$d1." - ".$d2."\n";
				*/

				//print_r($kpi);

				$calc    = $KPI -> calculateKPI( $iduser, $kpi, '', [
					$d1,
					$d2
				] );
				$percent = $userKPI['value'] > 0 ? round( 100 * ($calc / $userKPI['value']), 2 ) : 0;

				$kpiDo[] = [
					"period"      => $lang['period']['week']." #".$week,
					"periodDates" => format_date_rus( $d1 )." - ".format_date_rus( $d2 ),
					"value"       => num_format( $calc ),
					"percent"     => $percent,
					"bgcolor"     => ($week == $count) ? "orangebg-sub" : "",
					"edizm"       => $edizms[ $userKPI['tip'] ]
				];

			}

		break;
		case 'month':

			for ( $month = 1; $month <= 12; $month++ ) {

				// применяем сезонный коэффициент
				$userKPI['value'] *= (float)pre_format( $kpiSeason[ $month ] );

				$dsf = (int)date( "t", mktime( 1, 0, 0, (int)$month, 1, (int)$year ) );
				$d1  = strftime( '%Y-%m-%d', mktime( 1, 0, 0, (int)$month, 1, (int)$year ) );
				$d2  = strftime( '%Y-%m-%d', mktime( 1, 0, 0, (int)$month, $dsf, (int)$year ) );

				$calc    = $KPI -> calculateKPI( $iduser, $kpi, '', [
					$d1,
					$d2
				] );
				$percent = $userKPI['value'] > 0 ? round( 100 * ($calc / $userKPI['value']), 2 ) : 0;

				$kpiDo[] = [
					"period"  => $lang['face']['MounthName'][ $month - 1 ],
					"value"   => num_format( $calc ),
					"percent" => $percent,
					"bgcolor" => (date( 'm' ) == $month) ? "orangebg-sub" : "",
					"edizm"   => $edizms[ $userKPI['tip'] ]
				];

			}


		break;
		case 'quartal':

			for ( $quartal = 1; $quartal <= 4; $quartal++ ) {

				if ( $quartal == 1 ) {
					$q1 = $year.'-01-01';
					$q2 = $year.'-03-31';
				}
				if ( $quartal == 2 ) {
					$q1 = $year.'-04-01';
					$q2 = $year.'-06-30';
				}
				if ( $quartal == 3 ) {
					$q1 = $year.'-07-01';
					$q2 = $year.'-09-30';
				}
				if ( $quartal == 4 ) {
					$q1 = $year.'-10-01';
					$q2 = $year.'-12-31';
				}

				//$period = getPeriod($period);
				$d1 = $q1." 00:00:00";
				$d2 = $q2." 23:59:59";

				$calc    = $KPI -> calculateKPI( $iduser, $kpi, '', [
					$d1,
					$d2
				] );
				$percent = round( 100 * ($calc / $userKPI['value']), 2 );

				$kpiDo[] = [
					"period"  => $lang['period']['quartal']." #".$quartal,
					"value"   => num_format( $calc ),
					"percent" => $percent,
					"bgcolor" => ($quartal == (int)(((int)date( 'm' ) + 2) / 3)) ? "orangebg-sub" : "",
					"edizm"   => $edizms[ $userKPI['tip'] ]
				];

				//print intval(((int)date('m') + 2)/3)."\n";

			}

		break;
		case 'year':

			$period = getPeriod( 'year' );
			$d1     = "$year-01-01 00:00:00";
			$d2     = "$year-12-31 23:59:59";

			$calc    = $KPI -> calculateKPI( $iduser, $kpi, '', [
				$d1,
				$d2
			] );
			$percent = round( 100 * ($calc / $userKPI['value']), 2 );

			$kpiDo[] = [
				"period"  => $year." ".$lang['period']['year'],
				"value"   => num_format( $calc ),
				"percent" => $percent,
				"bgcolor" => ($year == (int)get_year( $d1 )) ? "orangebg-sub" : "",
				"edizm"   => $edizms[ $userKPI['tip'] ]
			];

		break;

	}

	// print_r($kpiDo);

	/**
	 * Обрабатываем запрос на экспорт данных
	 */
	if ( $export == 'yes' ) {

		$otchet = [
			[
				"Сотрудник",
				current_user( $iduser )
			],
			[
				"Параметр KPI",
				$userKPI['kpititle']
			],
			[
				"Год",
				$year
			],
			[
				"Месяц",
				$lang['face']['MounthName'][ $month - 1 ]
			],
			[
				"План",
				$userKPI['value']." ".$userKPI['edizm']." в ".$userKPI['periodname']
			],
			[
				"Период",
				"Значение",
				"Выполнение",
				"Ед.измерения"
			],
			[]
		];

		//print_r($kpiDo);
		//print_r($_REQUEST);

		foreach ( $kpiDo as $item ) {

			$otchet[] = [
				$item["period"],
				$item["value"],
				$item["percent"]."%",
				$item["edizm"],
			];

		}

		$xls = new Excel_XML( 'UTF-8', true, 'Data' );
		$xls -> addArray( $otchet );
		$xls -> generateXML( 'exportKPI-'.translit( current_user( $iduser ) ) );

		exit();

	}

	$list[] = [
		"id"          => $id,
		"iduser"      => $iduser,
		"kpiTitle"    => $userKPI['kpititle'],
		"periodName"  => $userKPI['periodname'],
		"kpiDo"       => $kpiDo,
		"count"       => $count,
		"monthSelect" => (!empty( $months )) ? 1 : 0,
		"months"      => $months,
		"monthsSel"   => $month
	];


}

/**
 * Список KPI
 */
if ( $action == 'kpis' ) {

	$list = Metrics ::getKPIs();

}

/**
 * Инфо по базовому KPI
 */
if ( $action == 'kpi' ) {

	$id = (int)$_REQUEST['id'];

	//данные текущего базового показателя
	$kpi = $db -> getRow( "SELECT * FROM {$sqlname}kpibase WHERE id = '$id' AND identity = '$identity' ORDER BY title" );

	//названия базовых типов показателей
	$base = Metrics ::MetricList();

	$items = Metrics ::getElements( $kpi['tip'] );

	//возможные имена значений
	if ( !in_array( $kpi['tip'], [
		"productCount",
		"productSumma"
	] ) ) {

		$subitems = Metrics ::MetricSubList( $kpi['tip'] );

	}
	else {

		$subitems = (!empty( $kpi['subvalues'] )) ? $db -> getIndCol( "n_id", "SELECT title, n_id FROM {$sqlname}price WHERE n_id IN ($kpi[subvalues])" ) : [];

	}

	//имена вариантов
	$vals   = [];
	$values = (array)yexplode( ",", (string)$kpi['values'] );

	foreach ( $values as $key => $value )
		$vals[] = ["name" => strtr( $value, $items )];

	//имена вариантов
	$subvals   = [];
	$subvalues = (array)yexplode( ",", (string)$kpi['subvalues'] );

	//print_r($subitems);

	foreach ( $subvalues as $key => $value )
		$subvals[] = ["name" => strtr( $value, $subitems )];

	//имена пользователей
	$usrs  = [];
	$users = Metrics ::getKPIUsers( (int)$id );

	foreach ( $users as $key => $value )
		$usrs[] = ["name" => current_user( $value )];

	$list = [
		"id"        => (int)$kpi['id'],
		"title"     => $kpi['title'],
		"tip"       => $kpi['tip'],
		"tipname"   => strtr( $kpi['tip'], $base ),
		"values"    => $vals,
		"subvalues" => $subvals,
		"users"     => $usrs
	];

}


$lists = ["list" => $list];

print json_encode_cyr( $lists );

exit();