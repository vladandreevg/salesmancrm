<?php
/* ============================ */
/*         SalesMan CRM         */
/* ============================ */
/* (C) 2016 Vladislav Andreev   */
/*       SalesMan Project       */
/*        www.isaler.ru         */
/*        ver. 2019.2           */
/* ============================ */

error_reporting( E_ERROR );
header( "Pragma: no-cache" );

//error_reporting( E_ALL );
//ini_set('display_errors', 1);

$rootpath = dirname(__DIR__, 2);

include $rootpath."/inc/config.php";
include $rootpath."/inc/dbconnector.php";
include $rootpath."/inc/auth.php";
include $rootpath."/inc/func.php";
include $rootpath."/inc/settings.php";
include $rootpath."/inc/language/".$language.".php";

$thisfile = basename( __FILE__ );

global $userRights;

$tm = $tzone;

$calendarn = $cpointn = $eventn = $nexta = [];

$y = (int)$_GET['y'];
if ( $y == '' ) $y = date( "Y", mktime( date( 'H' ), date( 'i' ), date( 's' ), date( 'm' ), date( 'd' ), date( 'Y' ) ) + $tzone * 3600 );
$m = (int)$_GET['m'];
if ( $m == '' ) $m = date( "m", mktime( date( 'H' ), date( 'i' ), date( 's' ), date( 'm' ), date( 'd' ), date( 'Y' ) ) + $tzone * 3600 );

$dd = date( "t", mktime( date( 'H' ), date( 'i' ), date( 's' ), $m, 1, $y ) + $tzone * 3600 ); //кол-во дней в текущем месяце
$d1 = date( "Y-m-d", mktime( date( 'H' ), date( 'i' ), date( 's' ), $m, '01', $y ) + $tzone * 3600 );
$d2 = date( "Y-m-d", mktime( date( 'H' ), date( 'i' ), date( 's' ), $m, $dd, $y ) + $tzone * 3600 );

//возможность перехода по месяцам и годам
if ( $y < 2000 ) $y += 2000;

#составим массив событий календаря
$result = $db -> query( "SELECT * FROM {$sqlname}tasks WHERE iduser = '$iduser1' and datum BETWEEN '$d1' and '$d2' and active != 'no' and identity = '$identity' ORDER BY datum, totime, FIELD(day, 'yes', null) DESC" );
while ( $data = $db -> fetch( $result ) ) {

	$drg     = '';
	$tooltip = '&#013;';
	$author  = '';

	$color = $db -> getOne( "SELECT color FROM {$sqlname}activities WHERE title='".$data[ 'tip' ]."' and identity = '$identity'" );
	if ( $color == "" ) $color = "transparent";

	if ( $data[ 'autor' ] > 0 ) {

		if ( $data['author'] == $iduser1 )
			$author = '<i class="icon-user-1 blue" title="Назначено мной"></i>';

		elseif ( $data['author'] != $iduser1 )
			$author = '<i class="icon-user-1 red" title="Назначено мне"></i>';

	}

	//if ($data['author'] == 0 or $data['author'] == $iduser1) $drg = 'todocal';
	//else $drg = '';

	$hours = difftime( $data[ 'created' ] );

	//mod

	if ( $data[ 'autor' ] == 0 || $data[ 'autor' ] == $iduser1 ) {

		if ( $hours <= $hoursControlTime ) {
			$drg = 'todocal';
		}
		elseif ( $userRights['changetask'] ) {
			$drg = 'todocal';
		}
		else {
			$drg = '';
		}

	}

	if ( $drg == '' )
		$tooltip .= 'Назначил: '.current_user( $data[ 'autor' ] ).'&#013;';

	if ( $data[ 'readonly' ] == 'yes' ) {

		if ( $data[ 'autor' ] == $data[ 'iduser' ] || $data[ 'author' ] == 0 || $data[ 'autor' ] == $iduser1 )
			$drg = 'todocal';

		if ( $data[ 'autor' ] != $data[ 'iduser' ] && $data[ 'author' ] == 0 ) {
			$drg     = '';
			$tooltip .= 'Только чтение';
		}

	}

	//--mod
	if ( $data[ 'day' ] != 'yes' ) {

		$calendar[ (int)getDay($data['datum']) ][] = [
			"day"     => getDay($data['datum']),
			"time"    => getTime((string)$data['totime']),
			"title"   => $data['title'],
			"tip"     => get_ticon($data['tip']),
			"color"   => $color,
			"autor"   => $author,
			"tid"     => $data['tid'],
			"iduser"  => $data['iduser'],
			"auth"    => $data['autor'],
			"tooltip" => $tooltip,
			"type"    => 'event',
			"drg"     => $drg
		];

	}
	else {

		$event[ (int)getDay($data['datum']) ][] = [
			"day"     => getDay($data['datum']),
			"type"    => 'event',
			"age"     => '<i class="icon-flag fs-09" title="Весь день"></i>',
			"time"    => "",
			"title"   => $data['title'],
			"tip"     => get_ticon($data['tip']),
			"color"   => $color,
			"author"  => $author,
			"tid"     => $data['tid'],
			"allday"  => $data['day'] == 'yes' ? 1 : null,
			"drg"     => $drg,
			"iduser"  => '',
			"pid"     => '',
			"clid"    => '',
			"comment" => ''
		];

	}

}

//Находим именнинников в компании;
$result = $db -> query( "SELECT * FROM {$sqlname}user WHERE (DATE_FORMAT(bday, '%c') = '".$m."' or DATE_FORMAT(CompStart, '%n') = '".$m."') and secrty = 'yes' and identity = '$identity' ORDER BY bday" );
while ( $data = $db -> fetch( $result ) ) {

	if ( $data[ 'bday' ] != '0000-00-00' && $data[ 'bday' ] != '' ) {

		$age = $y - get_year( $data[ 'bday' ] );
		$by  = explode( "-", $data[ 'bday' ] );
		$by1 = $by[ 0 ] + $age;
		$by  = $by1."-".$by[ 1 ]."-".$by[ 2 ];

		$event[ (int)getDay( $by ) ][] = [
			"day"    => getDay( $by ),
			"age"    => $age." ".getMorph( $age ),
			"time"   => "",
			"title"  => $data[ 'title' ],
			"tip"    => '<i class="icon-calendar-1" title="День Рождения сотрудника"></i>',
			"color"  => "#d62728",
			"author" => '',
			"tid"    => '',
			"iduser" => $data[ 'iduser' ]
		];

	}

	if ( $data[ 'CompStart' ] != '0000-00-00' && $data[ 'CompStart' ] != '' ) {

		$age = $y - get_year( $data[ 'CompStart' ] );
		$by  = explode( "-", $data[ 'CompStart' ] );
		$by1 = $by[ 0 ] + $age;
		$by  = $by1."-".$by[ 1 ]."-".$by[ 2 ];

		$event[ (int)getDay( $by ) ][] = [
			"day"    => getDay( $by ),
			"age"    => $age." ".getMorph( $age ),
			"time"   => "",
			"title"  => $data[ 'title' ],
			"tip"    => '<i class="icon-calendar-1" title="Стаж в компании"></i>',
			"color"  => "#1f77b4",
			"author" => '',
			"tid"    => '',
			"iduser" => $data[ 'iduser' ],
			"pid"    => '',
			"clid"   => ''
		];

	}

}

#добавим события из Клиентов, Контактов и Сотрудников

//Клиенты. Сначала найдем поля, содержащие даты
$result_k = $db -> query( "select * from {$sqlname}field where fld_tip='person' and fld_on='yes' and fld_temp = 'datum' and identity = '$identity' order by fld_order" );
while ( $data_array_k = $db -> fetch( $result_k ) ) {

	$field[] = $data_array_k[ 'fld_name' ];
	$fname[] = $data_array_k[ 'fld_title' ];

}

//данные по Контактам
for ( $g = 0, $gMax = count( $field ); $g < $gMax; $g++ ) {

	if($field[ $g ] == ''){
		continue;
	}

	$result = $db -> query( "SELECT * FROM {$sqlname}personcat WHERE DATE_FORMAT(".$field[ $g ].", '%c') = '".$m."' and identity = '$identity' ORDER BY ".$field[ $g ] );
	while ( $data = $db -> fetch( $result ) ) {

		if ( $data[ $field[ $g ] ] != '0000-00-00' && $data[ $field[ $g ] ] != '' ) {

			$age = $y - get_year( $data[ $field[ $g ] ] );
			$by  = explode( "-", $data[ $field[ $g ] ] );
			$by1 = $by[ 0 ] + $age;
			$by  = $by1."-".$by[ 1 ]."-".$by[ 2 ];

			if($by != '') {

				$event[ (int)getDay($by) ][] = [
					"day"    => getDay($by),
					"age"    => $age." ".getMorph($age),
					"time"   => "",
					"title"  => $data['person'],
					"tip"    => '<i class="icon-calendar-1" title="'.$fname[ $g ].'"></i>',
					"color"  => "#ff7f0e",
					"author" => '',
					"tid"    => '',
					"iduser" => '',
					"pid"    => $data['pid'],
					"clid"   => ''
				];

			}

		}

		$i++;

	}

}

//Контакты. Сначала найдем поля, содержащие даты
$fname    = [];
$field    = [];
$result_c = $db -> query( "select fld_name, fld_title from {$sqlname}field where fld_tip='client' and fld_on='yes' and fld_temp = 'datum' and identity = '$identity' order by fld_order" );
while ( $data_array_c = $db -> fetch( $result_c ) ) {

	$field[] = $data_array_c[ 'fld_name' ];
	$fname[] = $data_array_c[ 'fld_title' ];

}

//print "SELECT * FROM {$sqlname}clientcat WHERE DATE_FORMAT(input2, '%n') = '1' and identity = '$identity'";

for ( $g = 0, $gMax = count( $field ); $g < $gMax; $g++ ) {

	$result = $db -> query( "SELECT * FROM {$sqlname}clientcat WHERE DATE_FORMAT(".$field[ $g ].", '%c') = '".$m."' and identity = '$identity' ORDER BY ".$field[ $g ] );
	while ( $data = $db -> fetch( $result ) ) {

		if ( $data[ $field[ $g ] ] != '' ) {

			$age = $y - get_year( $data[ $field[ $g ] ] );
			$by  = explode( "-", $data[ $field[ $g ] ] );
			$by1 = $by[ 0 ] + $age;
			$by  = $by1."-".$by[ 1 ]."-".$by[ 2 ];

			$event[ (int)getDay( $by ) ][] = [
				"day"    => getDay( $by ),
				"age"    => $age." ".getMorph( $age ),
				"time"   => "",
				"title"  => $data[ 'title' ],
				"tip"    => '<i class="icon-calendar-1" title="'.$fname[ $g ].'"></i>',
				"color"  => "#2ca02c",
				"author" => '',
				"tid"    => '',
				"iduser" => '',
				"pid"    => '',
				"clid"   => $data[ 'clid' ]
			];

		}

		$i++;

	}

}

//Составим события по КТ
$q  = "
	SELECT
		{$sqlname}complect.data_plan as dplan,
		{$sqlname}complect.did as did,
		{$sqlname}complect.iduser as iduser,
		{$sqlname}complect_cat.title as title
	FROM {$sqlname}complect
	LEFT JOIN {$sqlname}complect_cat ON {$sqlname}complect.ccid = {$sqlname}complect_cat.ccid
	WHERE
		{$sqlname}complect.doit != 'yes' and
		{$sqlname}complect.iduser IN (".implode( ",", get_people( $iduser1, "yes" ) ).") and
		DATE_FORMAT({$sqlname}complect.data_plan, '%Y-%c') = '".$y."-".$m."' and
		{$sqlname}complect.identity = '$identity'";
$re = $db -> query( $q );
while ( $da = $db -> fetch( $re ) ) {

	if ( datestoday( $da[ 'dplan' ] ) < 0 ) $color = 'red';
	elseif ( datestoday( $da[ 'dplan' ] ) == 0 ) $color = 'broun';
	else $color = 'blue';

	$cpoint[ (int)getDay( $da['dplan'] ) ][] = [
		"day"    => getDay( $da[ 'dplan' ] ),
		"title"  => $da[ 'title' ],
		"tip"    => '<i class="icon-check '.$color.'"></i>',
		"iduser" => $da[ 'iduser' ],
		"cp"     => "1",
		"did"    => $da[ 'did' ]
	];

}

//массив для недельного календаря
$dayofmonth = $dd; // Вычисляем число дней в текущем месяце
$day_count  = 1; // Счётчик для дней месяца
$datum_max  = strftime( '%Y-%m-%d', mktime( 1, 0, 0, $m, $dayofmonth, $y ) );
$fweek      = strftime( '%w', mktime( 1, 0, 0, $m, 1, $y ) );

if ( $fweek == 0 ) $fweek = 7;

$maxDay = $fweek + $dayofmonth;

// 1. Первая неделя
$num = 0;
for ( $i = 0; $i < 7; $i++ ) {

	$dayofweek = date( 'w', mktime( 1, 0, 0, $m, $day_count, $y ) + $tm * 3600 );// Вычисляем номер дня недели для числа
	--$dayofweek;// Приводим к числа к формату 1 - понедельник, ..., 6 - суббота

	if ( $dayofweek == -1 )
		$dayofweek = 6;
	if ( $dayofweek == $i ) {
		// Если дни недели совпадают, массив $week числами месяца
		$week[ $num ][ $i ] = $day_count;
		$day_count++;
	}
	else
		$week[ $num ][ $i ] = "";


}
// 2. Последующие недели месяца
while ( true ) {
	$num++;
	for ( $i = 0; $i < 7; $i++ ) {
		$week[ $num ][ $i ] = $day_count;
		$day_count++;
		if ( $day_count > $dayofmonth ) break;// Если достигли конца недели - выходим из цикла
	}
	if ( $day_count > $dayofmonth ) break;// Если достигли конца месяца - выходим из цикла
}

if ( $m < 12 ) {
	$n  = $m + 1;
	$yn = $y;
}
else {
	$n  = 1;
	$yn = $y + 1;
}

if ( $n < 10 ) $n = '0'.$n;

$datum_next = $yn.'-'.$n.'-01';

$last  = 0;
$x     = 0;
$days  = 1;
$weeks = 0;

for ( $i = 0, $iMax = count( $week ); $i < $iMax; $i++ ) {

	$weeks++;

	for ( $j = 0; $j < 7; $j++ ) {

		// Если имеем дело с субботой и воскресенья подсвечиваем их
		if ( $j == 5 || $j == 6 ) $bg = 'bgray';
		else $bg = '';

		if ( $week[ $i ][ $j ] < 10 ) $d1 = "0".$week[ $i ][ $j ];
		else $d1 = $week[ $i ][ $j ];

		$d = $week[ $i ][ $j ];

		$datum = strftime( '%Y-%m-%d', mktime( 1, 0, 0, (int)$m, (int)$d, (int)$y ) );

		//if($x <= $dayofmonth){
		if ( $datum <= $datum_max && $x < $maxDay - 1 ) {

			$datum = strftime( '%Y-%m-%d', mktime( 1, 0, 0, (int)$m, (int)$d, (int)$y ) );

			//$datum = $y."-".$m."-".$d1;

			if ( diffDate2( $datum ) == 0 ) $bg = 'today';
			if ( diffDate2( $datum ) >= 0 ) {
				$scope = ' adtaskb';
				$add   = 'yes';
			}
			else $scope = '';

			if ( count( (array)$calendar[ $week[ $i ][ $j ] ] ) > 0 ) $list = 'yes'; //если есть дела на текущий день

			$datas[ $x ]               = [
				"day"   => $x,
				"date"  => $d,
				"datum" => $datum,
				"add"   => $add,
				"bg"    => $bg,
				"list"  => $list,
				"scope" => $scope
			];
			$datas[ $x ][ 'event' ]    = $event[ $d ];
			$datas[ $x ][ 'calendar' ] = $calendar[ $d ];
			$datas[ $x ][ 'cpoint' ]   = $cpoint[ $d ];

			$last = $j;

			$days++;

			$x++;

		}

	}

}

//счтаем еще 2 недели следующего месяца
$nextm = ( 6 - $last ) + 14;
$mn    = $m + 1;
$x     = 0;

$d1 = strftime( '%Y-%m-%d', mktime( 1, 0, 0, $m + 1, 1, (int)$y ) );
$d2 = strftime( '%Y-%m-%d', mktime( 1, 0, 0, $m + 1, (int)$nextm, (int)$y ) );

#составим массив событий календаря
$result = $db -> query( "SELECT * FROM {$sqlname}tasks WHERE iduser = '$iduser1' and datum BETWEEN '$d1' and '$d2' and active != 'no' and identity = '$identity' ORDER BY datum, totime, FIELD(day, 'yes', null) DESC" );
//print $db -> lastQuery();
while ( $data = $db -> fetch( $result ) ) {

	$drg     = '';
	$tooltip = '&#013;';

	$color = $db -> getOne( "SELECT color FROM {$sqlname}activities WHERE title='".$data[ 'tip' ]."' and identity = '$identity'" );
	if ( $color == "" ) $color = "transparent";

	if ( $data[ 'autor' ] > 0 && $data[ 'author' ] == $iduser1 ) $author = '<i class="icon-user-1 blue" title="Назначено мной"></i>';
	elseif ( $data[ 'autor' ] > 0 && $data[ 'author' ] != $iduser1 ) $author = '<i class="icon-user-1 red" title="Назначено мне"></i>';
	else $author = '';

	//if ($data['author'] == 0 or $data['author'] == $iduser1) $drg = 'todocal';
	//else $drg = '';

	//mod

	if ( $data[ 'autor' ] == 0 || $data[ 'autor' ] == $iduser1 ) {

		if ( $hours <= $hoursControlTime ) {
			$drg = 'todocal';
		}
		elseif ( $userRights['changetask'] ) {
			$drg = 'todocal';
		}
		else {
			$drg = '';
		}

	}

	if ( $drg == '' ) {
		$tooltip .= 'Назначил: '.current_user($data['autor']).'&#013;';
	}

	if ( $data[ 'readonly' ] == 'yes' ) {

		if ( $data[ 'autor' ] == $data[ 'iduser' ] || $data[ 'author' ] == 0 || $data[ 'autor' ] == $iduser1 ) {
			$drg = 'todocal';
		}
		if ( $data[ 'autor' ] != $data[ 'iduser' ] && $data[ 'author' ] == 0 ) {
			$drg     = '';
			$tooltip .= 'Только чтение';
		}

	}

	//--mod

	if ( $data[ 'day' ] != 'yes' ) {

		$calendarn[ (int)getDay($data['datum']) ][] = [
			"day"    => getDay($data['datum']),
			"time"   => getTime((string)$data['totime']),
			"title"  => $data['title'],
			"tip"    => get_ticon($data['tip']),
			"color"  => $color,
			"autor"  => $author,
			"tid"    => $data['tid'],
			"iduser" => $data['iduser'],
			"auth"   => $data['autor'],
			"type"   => 'event',
			"drg"    => $drg
		];

	}
	else {

		$eventn[ (int)getDay($data['datum']) ][] = [
			"day"     => getDay($data['datum']),
			"type"    => 'event',
			"age"     => '<i class="icon-flag fs-09" title="Весь день"></i>',
			"time"    => "",
			"title"   => $data['title'],
			"tip"     => get_ticon($data['tip']),
			"color"   => $color,
			"author"  => $author,
			"tid"     => $data['tid'],
			"allday"  => $data['day'] == 'yes' ? 1 : null,
			"iduser"  => '',
			"pid"     => '',
			"clid"    => '',
			"comment" => ''
		];

	}

}

//Составим события по КТ
$q  = "
	SELECT
		{$sqlname}complect.data_plan as dplan,
		{$sqlname}complect.did as did,
		{$sqlname}complect.iduser as iduser,
		{$sqlname}complect_cat.title as title
	FROM {$sqlname}complect
	LEFT JOIN {$sqlname}complect_cat ON {$sqlname}complect.ccid = {$sqlname}complect_cat.ccid
	WHERE
		{$sqlname}complect.doit != 'yes' and
		{$sqlname}complect.iduser IN (".implode( ",", get_people( $iduser1, "yes" ) ).") and
		{$sqlname}complect.data_plan BETWEEN '".$d1."' and '".$d2."' and
		{$sqlname}complect.identity = '$identity'";
$re = $db -> query( $q );
while ( $da = $db -> fetch( $re ) ) {

	if($da[ 'data_plan' ] !== '') {

		if (datestoday($da['dplan']) < 0) $color = 'red';
		elseif (datestoday($da['dplan']) == 0) $color = 'broun';
		else $color = 'blue';

		$cpointn[ (int)getDay($da['dplan']) ][] = [
			"day"    => getDay($da['dplan']),
			"title"  => $da['title'],
			"tip"    => '<i class="icon-check '.$color.'"></i>',
			"iduser" => $da['iduser'],
			"cp"     => "1",
			"did"    => $da['did']
		];

	}

}

for ( $j = 1; $j <= $nextm; $j++ ) {

	$datum = strftime( '%Y-%m-%d', mktime( 0, 0, 0, $m + 1, $j, (int)$y ) );
	$fweek = strftime( '%w', mktime( 1, 0, 0, $m + 1, $j, (int)$y ) );

	if ( count( (array)$calendarn[ $j ] ) > 0 ) $list = 'yes';
	else $list = '';

	if ( $fweek == 0 || $fweek == 6 ) $bg = 'bgray';
	else $bg = 'next';

	$nexta[ $x ]               = [
		"day"   => $x,
		"date"  => $j." ".ru_month( $m + 1 ),
		"datum" => $datum,
		"add"   => "yes",
		"list"  => $list,
		"bg"    => $bg,
		"scope" => " adtaskb",
		"fweek" => $fweek
	];
	$nexta[ $x ][ 'calendar' ] = $calendarn[ $j ];
	$nexta[ $x ][ 'cpoint' ]   = $cpointn[ $j ];
	$nexta[ $x ][ 'event' ]    = $eventn[ $j ];

	$days++;

	$x++;

}

$data = [
	"calendar"   => $datas,
	"next"       => $nexta,
	"weeks"      => $weeks,
	"dayofmonth" => $dayofmonth,
	"maxDay"     => $maxDay,
	"fweek"      => $fweek
];

//print_r($data);
print json_encode_cyr( $data );