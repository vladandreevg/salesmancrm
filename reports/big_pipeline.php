<?php
error_reporting( E_ERROR );
ini_set( 'display_errors', 1 );
header( "Pragma: no-cache" );

$rootpath = realpath( __DIR__.'/../' );

include $rootpath."/inc/config.php";
include $rootpath."/inc/dbconnector.php";
include $rootpath."/inc/auth.php";
include $rootpath."/inc/func.php";
include $rootpath."/inc/settings.php";
include $rootpath."/inc/language/".$language.".php";

$action = $_REQUEST['action'];
$da1    = $_REQUEST['da1'];
$da2    = $_REQUEST['da2'];
$da     = $_REQUEST['da'];
$act    = $_REQUEST['act'];
$per    = $_REQUEST['per'];

if ( !$per )
	$per = 'nedelya';

$user_list    = (array)$_REQUEST['user_list'];
$clients_list = (array)$_REQUEST['clients_list'];
$persons_list = (array)$_REQUEST['persons_list'];
$field        = (array)$_REQUEST['field'];
$field_query  = (array)$_REQUEST['field_query'];

$da1_array = explode( "-", $da1 );
$da2_array = explode( "-", $da2 );

//текущий год
$year = date( 'Y', mktime( 0, 0, 0, $da1_array[1], 1, $da1_array[0] ) );
//месяц.год начала
$dstart  = date( 'Y-m', mktime( 0, 0, 0, $da1_array[1], 1, $da1_array[0] ) );
$dstarta = date( 'm.Y', mktime( 0, 0, 0, $da1_array[1], 1, $da1_array[0] ) );
//месяц начальный
$ds = date( 'm', mktime( 0, 0, 0, $da1_array[1], 1, $da1_array[0] ) );
//количество дней в начальном месяце
$dd = date( "t", mktime( date( 'H' ), date( 'i' ), date( 's' ), $da2_array[1], 1, $da2_array[0] ) );
//месяц.год конца
$dend  = date( 'Y-m', mktime( 0, 0, 0, $da2_array[1], $dd + 1, $da2_array[0] ) );
$denda = date( 'm.Y', mktime( 0, 0, 0, $da2_array[1], $dd + 1, $da2_array[0] ) );
//месяц конечный
$de = date( 'm', mktime( 0, 0, 0, $da2_array[1], $dd, $da2_array[0] ) );
//текущий день
$d2 = strftime( '%Y-%m-%d', mktime( 1, 0, 0, date( 'm' ), date( 'd' ), date( 'Y' ) ) );

$user_list = (!empty( $user_list )) ? $user_list : (array)get_people( $iduser1, "yes" );
$users = implode( ",", $user_list );

$sort = $sort.$sort1.$sort2;

//вывод текущего номера картала
function current_quartal($date_orig) {
	$new  = explode( "-", $date_orig );
	$mon  = $new[1]; //это текущий месяц
	$year = $new[0];
	$q1   = [
		1,
		2,
		3
	];
	$q2   = [
		4,
		5,
		6
	];
	$q3   = [
		7,
		8,
		9
	];
	$q4   = [
		10,
		11,
		12
	];
	if ( in_array( $mon, $q1 ) )
		$quartal = 1;
	if ( in_array( $mon, $q2 ) )
		$quartal = 2;
	if ( in_array( $mon, $q3 ) )
		$quartal = 3;
	if ( in_array( $mon, $q4 ) )
		$quartal = 4;

	return $quartal;
}

//вывод дат для квартала
function get_quartal($quartal, $year) {
	if ( $quartal == 1 ) {
		$q11 = $year.'-01-01';
		$q12 = $year.'-03-31';
	}
	if ( $quartal == 2 ) {
		$q11 = $year.'-04-01';
		$q12 = $year.'-06-30';
	}
	if ( $quartal == 3 ) {
		$q11 = $year.'-07-01';
		$q12 = $year.'-09-30';
	}
	if ( $quartal == 4 ) {
		$q11 = $year.'-10-01';
		$q12 = $year.'-12-31';
	}
	$dates = $q11." ".$q12;

	return $dates;
}

//квартал текущий
$q = current_quartal( $da1 );

$qq = explode( " ", get_quartal( $q, $year ) );
$q3 = $qq[0];
$q4 = $qq[1];

//расчет плана на год
//Найдем босса, который управляет предприятием
$iduser = $db -> getOne( "SELECT * FROM ".$sqlname."user WHERE tip='Руководитель организации' and identity = '$identity'" );

$data = $db -> getRow( "SELECT SUM(kol_plan) as kol, SUM(marga) as marga FROM ".$sqlname."plan WHERE year='".$year."' and iduser IN (".$users.") and identity = '$identity'" );

$kol_plan  = $data['kol'];
$marg_plan = $data['marga'];

//расчет результатов за год
$data      = $db -> getRow( "SELECT SUM(kol_fact) as kol, SUM(marga) as marga FROM ".$sqlname."dogovor WHERE date_format(datum_close, '%Y-%m-%d') >= '".$year."-01-01' and date_format(datum_close, '%Y-%m-%d') <= '".$year."-12-31' and iduser IN (".$users.") and close='yes' and identity = '$identity'" );
$kol_fact  = $data['kol'];
$marg_fact = $data['marga'];

//расчет плана и результатов на период
$data        = $db -> getRow( "SELECT SUM(kol_plan) as kol, SUM(marga) as marga FROM ".$sqlname."plan WHERE year='".$year."' and mon >= '".date( 'm', date_to_unix( $q3 ) )."' and mon <= '".date( 'm', date_to_unix( $q4 ) )."' and iduser IN (".$users.") and identity = '$identity'" );
$kol_plan_q  = $data['kol'];
$marg_plan_q = $data['marga'];

$data        = $db -> getRow( "SELECT SUM(kol_fact) as kol, SUM(marga) as marga FROM ".$sqlname."dogovor WHERE date_format(datum_close, '%Y-%m-%d') >= '".$q3."' and date_format(datum_close, '%Y-%m-%d') <= '".$q4."' and iduser IN (".$users.") and close='yes' and identity = '$identity'" );
$kol_fact_q  = $data['kol'];
$marg_fact_q = $data['marga'];

//Процент выполнения
$proc_plan   = $kol_fact / $kol_plan * 100;
$proc_marg   = $marg_fact / $marg_plan * 100;
$proc_plan_q = $kol_fact_q / $kol_plan_q * 100;
$proc_marg_q = $marg_fact_q / $marg_plan_q * 100;

//квартал следующий
$q2 = current_quartal( $da1 ) + 1;
//if($q!=1) { $q = $q - 1; $yy = date('Y');}
//else { $q = 4; $yy = date('Y') - 1;}

$qq = explode( " ", get_quartal( $q2, $year ) );
//$q3 = $qq[0];
$q4 = $qq[1];

//массив стадий сделок
$i      = 0;
$result = $db -> getAll( "SELECT * FROM ".$sqlname."dogcategory WHERE identity = '$identity' ORDER BY title" );
foreach ( $result as $data_array ) {

	$dogstep[ $i ] = [
		"id"   => $data_array['idcategory'],
		"name" => $data_array['title'],
		"des"  => $data_array['content']
	];

	//массив сделок
	$resultm = $db -> query( "SELECT * FROM ".$sqlname."dogovor WHERE date_format(datum_plan, '%Y-%m-%d') >= '".$q3."' and date_format(datum_plan, '%Y-%m-%d') <= '".$q4."' and idcategory = '".$data_array['idcategory']."' and close!='yes' and iduser IN (".$users.") and identity = '$identity' ORDER BY iduser" );

	$j = 0;
	$k = 0;

	while ($data = $db -> fetch( $resultm )) {

		$step = current_dogstepname( $data['did'] );

		if ( $data['datum_plan'] >= current_datum() ) {

			$dogs[ $i ][ $j ] = [
				"did"    => $data['did'],
				"clid"   => $data['clid'],
				"pid"    => $data['pid'],
				"step"   => $step,
				"title"  => $data['title'],
				"iduser" => $data['iduser'],
				"kolp"   => $data['kol'],
				"kolf"   => $data['kol_fact'],
				"marga"  => $data['marga'],
				"datum"  => $data['datum_plan'],
				"type"   => current_dogtype( (int)$data['tip'] )
			];
			$j++;

		}
		else {

			$dogs_old[ $i ][ $k ] = [
				"did"    => $data['did'],
				"clid"   => $data['clid'],
				"pid"    => $data['pid'],
				"step"   => $step,
				"title"  => $data['title'],
				"iduser" => $data['iduser'],
				"kolp"   => $data['kol'],
				"kolf"   => $data['kol_fact'],
				"marga"  => $data['marga'],
				"datum"  => $data['datum_plan'],
				"type"   => current_dogtype( (int)$data['tip'] )
			];
			$k++;

		}
	}
	$i++;
}

?>
<div class="zagolovok_rep" align="center">
	<h1>Оценка эффективности работы</h1>
	за <b class="red"><?= $q ?> квартал <?= $year ?> года</b>
</div>

<div style="margin:0 auto; width:90%"><b>По обороту</b></div>

<table width="90%" border="0" align="center" cellpadding="2" cellspacing="0" id="tborder" style="border-top:1px solid #ccc; border-right:1px solid #ccc;">
	<thead style="border: 2px solid #000">
	<tr>
		<td width="50%" height="30" colspan="2" align="center"><b>Годовой план</b></td>
		<td colspan="2" width="50%" align="center"><b>План на период</b></td>
	</tr>
	<tr>
		<td width="25%" align="center"><b>План</b></td>
		<td align="center"><b>Результат</b></td>
		<td width="25%" align="center"><b>План</b></td>
		<td align="center"><b>Результат</b></td>
	</tr>
	</thead>
	<tr>
		<td align="center"><?= num_format( $kol_plan ) ?></td>
		<td align="center"><?= num_format( $kol_fact ) ?></td>
		<td align="center"><?= num_format( $kol_plan_q ) ?></td>
		<td align="center"><?= num_format( $kol_fact_q ) ?></td>
	</tr>
	<tr>
		<td align="right"><b>% выполнения:</b></td>
		<td align="center" bgcolor="#FFCC33" style="border: 2px solid #000">
			<b class="red"><?= num_format( $proc_plan ) ?>%</b></td>
		<td align="right"><b>% выполнения:</b></td>
		<td align="center" bgcolor="#FFCC33" style="border: 2px solid #000">
			<b class="red"><?= num_format( $proc_plan_q ) ?>%</b></td>
	</tr>
</table><br/><br/>
<div style="margin:0 auto; width:90%"><b>По марже</b></div>
<table width="90%" border="0" align="center" cellpadding="2" cellspacing="0" id="tborder" style="border-top:1px solid #ccc; border-right:1px solid #ccc;">
	<thead style="border: 2px solid #000">
	<tr>
		<td width="50%" height="30" colspan="2" align="center"><b>Годовой план</b></td>
		<td colspan="2" width="50%" align="center"><b>План на период</b></td>
	</tr>
	<tr>
		<td width="25%" align="center"><b>План</b></td>
		<td align="center"><b>Результат</b></td>
		<td width="25%" align="center"><b>План</b></td>
		<td align="center"><b>Результат</b></td>
	</tr>
	</thead>
	<tr class="ha">
		<td align="center"><?= num_format( $marg_plan ) ?></td>
		<td align="center"><?= num_format( $marg_fact ) ?></td>
		<td align="center"><?= num_format( $marg_plan_q ) ?></td>
		<td align="center"><?= num_format( $marg_fact_q ) ?></td>
	</tr>
	<tr>
		<td align="right"><b>% выполнения:</b></td>
		<td align="center" bgcolor="#FFCC33" style="border: 2px solid #000">
			<b class="red"><?= num_format( $proc_marg ) ?>%</b></td>
		<td align="right"><b>% выполнения:</b></td>
		<td align="center" bgcolor="#FFCC33" style="border: 2px solid #000">
			<b class="red"><?= num_format( $proc_marg_q ) ?>%</b></td>
	</tr>
</table><br/><br/>
<div class="zagolovok_rep" align="center">
	<b>Оценка прогноза продаж </b>за период&nbsp;с&nbsp;<b class="red"><?= format_date_rus( $q3 ) ?></b>&nbsp;по&nbsp;<b class="red"><?= format_date_rus( $q4 ) ?></b>:
</div><BR>
<table width="90%" border="0" align="center" cellpadding="2" cellspacing="0" id="tborder" class="border" style="border-top:1px solid #ccc; border-right:1px solid #ccc;">
	<thead style="border: 2px solid #000">
	<tr>
		<td width="20%" height="30" align="center"><b>Текущий этап</b></td>
		<td width="10%" align="center"><b>Кол-во</b></td>
		<td width="15%" align="center"><b>Сумма, <?= $valuta ?></b></td>
		<td width="15%" align="center"><b>Вес, <?= $valuta ?></b></td>
		<td align="center"><b>Примечание</b></td>
	</tr>
	</thead>
	<tr>
		<td colspan="5" class="redbg white">
			<b>Просроченные сделки</b> [Дата реализации раньше <?= format_date_rus( current_datum() ); ?>]
		</td>
	</tr>
	<?php
	$k_all = 0;
	for ( $i = 0; $i < count( (array)$dogstep ); $i++ ) {

		//посчитаем сумму сделок по этапу
		for ( $j = 0; $j < count( (array)$dogs_old[ $i ] ); $j++ ) {

			$koll     = $koll + pre_format( $dogs_old[ $i ][ $j ]['kolp'] );
			$k_all    = $k_all + 1;
			$koll_all = $koll_all + $koll;
		}

		//if($koll>0){
		$kol_ves      = ($dogstep[ $i ]['name'] * $koll) / 100;
		$koll_ves_all = $koll_ves_all + $kol_ves;
		if ( $koll == 0 )
			$cl = "gray";
		else $cl = "";
		?>
		<tr class="ha <?= $cl ?>">
			<td nowrap="nowrap"><b><?= $dogstep[ $i ]['name'] ?>%</b> - <?= $dogstep[ $i ]['des'] ?></td>
			<td align="center"><?= count( (array)$dogs_old[ $i ] ) ?></td>
			<td align="right" nowrap="nowrap"><?= num_format( $koll ) ?>&nbsp;&nbsp;</td>
			<td align="right" nowrap="nowrap"><?= num_format( $kol_ves ) ?>&nbsp;&nbsp;</td>
			<td align="center">&nbsp;</td>
		</tr>
		<?php
		//}
		$koll    = 0;
		$kol_ves = 0;
	}
	?>
	<tr>
		<td colspan="5" class="bluebg white"><b>Сделки в работе</b></td>
	</tr>
	<?php
	//print_r($dogs);
	for ( $i = 0; $i < count( (array)$dogstep ); $i++ ) {
		//посчитаем сумму сделок по этапу
		for ( $j = 0; $j < count( (array)$dogs[ $i ] ); $j++ ) {
			$koll     = $koll + pre_format( $dogs[ $i ][ $j ]['kolp'] );
			$k_all    = $k_all + 1;
			$koll_all = $koll_all + $koll;
		}

		//if($koll>0){
		$kol_ves      = ($dogstep[ $i ]['name'] * $koll) / 100;
		$koll_ves_all = $koll_ves_all + $kol_ves;
		if ( $koll == 0 )
			$cl = "gray";
		else $cl = "";
		?>
		<tr class="ha <?= $cl ?>">
			<td nowrap="nowrap"><b><?= $dogstep[ $i ]['name'] ?>%</b> - <?= $dogstep[ $i ]['des'] ?></td>
			<td align="center"><?= count( (array)$dogs[ $i ] ) ?></td>
			<td align="right" nowrap="nowrap"><?= num_format( $koll ) ?>&nbsp;&nbsp;</td>
			<td align="right" nowrap="nowrap"><?= num_format( $kol_ves ) ?>&nbsp;&nbsp;</td>
			<td align="center">&nbsp;</td>
		</tr>
		<?php
		//}
		$koll    = 0;
		$kol_ves = 0;
	}
	?>
	<tr>
		<td align="right"><b>Всего:</b></td>
		<td bgcolor="#FFCC33" style="border: 2px solid #000" align="center"><b><?= $k_all ?></b></td>
		<td align="right"><b><?= num_format( $koll_all ) ?></b>&nbsp;&nbsp;</td>
		<td bgcolor="#FFCC33" style="border: 2px solid #000" align="right"><b><?= num_format( $koll_ves_all ) ?></b>&nbsp;&nbsp;
		</td>
		<td>&nbsp;</td>
	</tr>
</table>
<div style="height:90px"></div>