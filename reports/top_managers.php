<?php
/**
 * @license  http://isaler.ru/
 * @author   Vladislav Andreev, http://iandreyev.ru/
 * @charset  UTF-8
 * @version  6.4
 */
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

$da1 = $_REQUEST[ 'da1' ];
$da2 = $_REQUEST[ 'da2' ];
$da  = $_REQUEST[ 'da' ];
$top = $_REQUEST[ 'top' ];

if ( !$top ) {
	$top = 10;
}

$act = $_REQUEST[ 'act' ];
$per = $_REQUEST[ 'per' ];

if ( !$per ) {
	$per = 'nedelya';
}

$user_list    = (array)$_REQUEST['user_list'];
$clients_list = (array)$_REQUEST['clients_list'];
$persons_list = (array)$_REQUEST['persons_list'];
$fields       = (array)$_REQUEST['field'];
$field_query  = (array)$_REQUEST['field_query'];

$user_list = (!empty( $user_list )) ? $user_list : (array)get_people( $iduser1, "yes" );

if ( !empty($clients_list) && !empty($persons_list) ) {
	$sort .= "(deal.clid IN (".yimplode( ",", $clients_list).") OR deal.pid IN (".yimplode( ",", $persons_list ).")) AND ";
}
elseif ( !empty($clients_list) ) {
	$sort .= "deal.clid IN (".yimplode( ",", $clients_list).") AND ";
}
elseif ( !empty($persons_list) ) {
	$sort .= "deal.pid IN (".yimplode( ",", $persons_list ).") AND ";
}

//составляем запрос по параметрам сделок
$ar = [
	'con_id',
	'partner',
	'sid'
];
foreach ( $fields as $i => $field ) {

	if ( !in_array( $field, $ar ) && !in_array( $field, [
			'close',
			'mcid'
		] ) ) {
		$sort .= " deal.{$field} = '".$field_query[ $i ]."' AND ";
	}
	elseif ( $field == 'close' ) {
		$sort .= $query[ $i ] != 'yes' ? " COALESCE(deal.{$field}, 'no') != 'yes' AND " : " COALESCE(deal.{$field}, 'no') == 'yes' AND ";
	}
	elseif ( $field == 'mcid' ) {
		$mc = $field_query[ $i ];
	}

}

//print_r($user_list);

//Создание массивов данных
foreach ($user_list as $user) {

	$result = $db -> getRow( "
		SELECT 
		    SUM(deal.kol_fact) as kol, 
		    COUNT(deal.did) as dogs 
		FROM ".$sqlname."dogovor `deal`
		WHERE 
			deal.close = 'yes' and 
			deal.datum_close between '".$da1."' and '".$da2."' and 
			deal.iduser = '$user' and  
			$sort
			deal.identity = '$identity'
		" );

	$data[] = [
		"manager" => current_user( $user ),
		"dogs"    => (int)$result[ 'dogs' ],
		"kol"     => (float)$result[ 'kol' ]
	];

}

//сумма всех элементов
$summa = arraysum( $data, "kol" );

function cmp( $a, $b ) { return $b[ 'kol' ] > $a[ 'kol' ]; }

usort( $data, 'cmp' );

//print_r($data);
?>
<div style="display:inline-block; width:200px;" class="noprint">&nbsp;Топ:
	<input name="top" type="text" id="top" value="<?= $top ?>" size="3" maxlength="3" style="text-align:center"/> сотрудников
</div>

<div class="zagolovok_rep text-center">
	<b>Топ сотрудников за период&nbsp;с&nbsp;<?= format_date_rus( $da1 ) ?>&nbsp;по&nbsp;<?= format_date_rus( $da2 ) ?></b>:<BR><BR>
</div>

<hr>

<table id="zebra">
	<thead class="sticked--top">
	<TR class="header_contaner">
		<th class="w40 text-center">&nbsp;</th>
		<th class="text-center"><b>Сотрудник</b></th>
		<th class="w100 text-center"><b>Сделок</b></th>
		<th class="w120 text-center"><b>Сумма, <?= $valuta ?></b></th>
	</TR>
	</THEAD>
	<TBODY>
	<?php
	foreach ($data as $j => $row) {
		?>
		<TR class="ha bordered">
			<TD class="text-center"># <?= $j + 1 ?></TD>
			<TD>
				<DIV class="ellipsis" title="<?= $row[ 'manager' ] ?>"><?= $row[ 'manager' ] ?></DIV>
			</TD>
			<TD class="text-center"><?= $row[ 'dogs' ] ?></TD>
			<TD class="text-right" nowrap><?= num_format( $row[ 'kol' ] ) ?></TD>
		</TR>
		<?php
	}
	?>
	<TR bgcolor="#FC9">
		<TD class="text-center">&nbsp;</TD>
		<TD class="text-right"><B>ИТОГО:</B></TD>
		<TD class="text-center"><B><?= $all_cl_d ?></B></TD>
		<TD class="text-right" nowrap><B><?= num_format( $summa ) ?></B></TD>
	</TR>
	</TBODY>
</TABLE>
<hr>
<div class="infodiv">
	<span><i class="icon-info-circled blue icon-3x pull-left"></i></span>
	<b class="blue uppercase">Помощь</b><br><br>
	Данный отчет учитывает только закрытые сделки и производит расчет по закрытым сделкам.
</div>
<div style="height:90px"><div>