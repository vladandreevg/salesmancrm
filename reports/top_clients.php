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

$da1 = $_REQUEST['da1'];
$da2 = $_REQUEST['da2'];
$da  = $_REQUEST['da'];
$top = $_REQUEST['top'];

if ( $top == '' ) {
	$top = 10;
}

$act = $_REQUEST['act'];
$per = $_REQUEST['per'];

if ( !$per ) {
	$per = 'nedelya';
}

$user_list    = (array)$_REQUEST['user_list'];
$clients_list = (array)$_REQUEST['clients_list'];
$persons_list = (array)$_REQUEST['persons_list'];
$fields       = (array)$_REQUEST['field'];
$field_query  = (array)$_REQUEST['field_query'];

$sort = $clist = $plist = '';

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
		$sort .= $field_query[ $i ] != 'yes' ? " COALESCE(deal.{$field}, 'no') != 'yes' AND " : " COALESCE(deal.{$field}, 'no') == 'yes' AND ";
	}
	elseif ( $field == 'mcid' ) {
		$mc = $field_query[ $i ];
	}

}

//Создание массивов данных
$i = 0;

//новый запрос
$query = "
	SELECT * 
	FROM ".$sqlname."clientcat 
	WHERE 
		clid > 0 and 
		(SELECT COUNT(*) FROM ".$sqlname."dogovor WHERE clid > 0 and kol_fact > 0 and datum_close BETWEEN '$da1' and '$da2' and identity = '$identity') > 0 and 
		identity = '$identity'
	";

//старый запрос
$old = "SELECT * FROM ".$sqlname."clientcat WHERE clid > 0 and identity = '$identity'";

//проходим все организации
$effect = [];
$result = $db -> getAll( $query );
foreach ( $result as $data ) {

	//прходим все сделки с данной организацией
	$q     = "
		SELECT 
		    COUNT(deal.did) as count, 
		    SUM(deal.kol_fact) as summa, 
		    SUM(deal.marga) as marga 
		FROM ".$sqlname."dogovor `deal`
		WHERE 
			deal.clid = '".$data['clid']."' and 
			COALESCE(deal.close, 'no') = 'yes' and 
			deal.datum_close between '$da1' and '$da2' and 
			$sort
			deal.identity = '$identity'
		";
	$r     = $db -> getRow( $q );
	$count = $r["count"];
	$summa = $r["summa"];
	$marga = $r["marga"];

	if ( $summa > 0 ) {

		$effect[] = [
			"title"  => $data['title'],
			"clid"   => $data['clid'],
			"count"  => $count,
			"summa"  => $summa,
			"marga"  => $marga,
			"iduser" => $data['iduser'],
			"user"   => current_user( $data['iduser'] ),
		];

	}

}

//сортируем массив по новым или закрытым сделкам в зависимости от настроек
function cmp($a, $b): bool {
	return $b['summa'] > $a['summa'];
}

usort( $effect, 'cmp' );

$total = arraysum( $effect, 'summa' );
?>
<STYLE type="text/css">
	<!--
	.progressbar-completed,
	.status {
		height     : 0.4em;
		box-sizing : border-box;
	}

	.progressbar-completed div {
		display : inline;
	}

	.progressbar {
		width                 : 100%;
		border                : #CCC 0 dotted;
		-moz-border-radius    : 1px;
		-webkit-border-radius : 1px;
		border-radius         : 1px;
		background            : rgba(250, 250, 250, 1);
		position              : relative;
	}
	.progressbar-completed {
		height       : 4.0em;
		line-height  : 4.0em;
		margin-left  : 0;
		padding-left : 0;
	}
	.progressbar-text {
		position : absolute;
		right    : 10px;
		top      : 5px;
	}
	.progressbar-head {
		position : absolute;
		left     : 10px;
		top      : 5px;
	}

	.progress-gray {
		background-image : -webkit-gradient(linear, 0.00% 50.00%, 100.00% 50.00%, color-stop(0%, rgba(227, 216, 220, 1)), color-stop(91.71%, rgba(207, 216, 220, 1.00)));
		background-image : -webkit-linear-gradient(0deg, rgba(227, 216, 220, 1.00) 0%, rgba(207, 216, 220, 1.00) 91.71%);
		background-image : linear-gradient(90deg, rgba(227, 216, 220, 1.00) 0%, rgba(207, 216, 220, 1.00) 91.71%);
	}

	.graybg22 {
		background : rgba(245, 245, 245, 1);
	}

	-->
</STYLE>

<div class="relativ mt20 mb20 wp95 text-center">
	<h1 class="uppercase fs-14 m0 mb10">Топ клиентов</h1>
	<div class="blue">за период&nbsp;с&nbsp;<?= format_date_rus( $da1 ) ?>&nbsp;по&nbsp;<?= format_date_rus( $da2 ) ?></div>
</div>

<hr>

<div class="infodiv noprint mb10">
	&nbsp;Топ: <input name="top" type="number" min="1" id="top" value="<?= $top ?>" size="3" maxlength="3" class="w100">
</div>

<table id="zebra">
	<thead class="sticked--top">
	<TR class="header_contaner text-center">
		<th class="w40">&nbsp;</th>
		<th><B>Клиент</B></th>
		<th class="w100"><b>Количество</b></th>
		<th class="w120" nowrap="nowrap"><b>Сумма, <?= $valuta ?></b></th>
		<th class="w120"><b>Маржа, <?= $valuta ?></b></th>
		<th class="w100"></th>
	</TR>
	</thead>
	<TBODY>
	<?php
	$j   = 0;
	if(!empty($effect)){
		$max = arrayMax( $effect, 'summa' );
		while ($j < $top) {

			$wk = $max -> max > 0 ? $effect[ $j ]['summa'] / $max -> max * 100 : 0;

			print '
		<TR class="ha bordered">
			<TD class="text-center"># '.($j + 1).'</TD>
			<TD>
				<DIV class="progressbar wp100 graybg22">
					<div class="progressbar-head wp100">
						
						<DIV class="ellipsis Bold fs-12" title="'.$effect[ $j ]['title'].'">
							<A href="javascript:void(0)" onclick="openClient(\''.$effect[ $j ]['clid'].'\')"><i class="icon-building broun"></i> '.$effect[ $j ]['title'].'</a>
						</DIV>
						<DIV class="blue mt5 fs-09">
							<i class="icon-user-1"></i> '.$effect[ $j ]['user'].'
						</DIV>
						
					</div>
					<DIV id="test" class="progressbar-completed progress-gray" style="width:'.$wk.'%;"></DIV>
				</DIV>
			</TD>
			<TD class="text-center">
				<DIV title="'.$effect[ $j ]['count'].'">'.$effect[ $j ]['count'].'</DIV>
			</TD>
			<TD class="text-right" class="Bold fs-11">
				<DIV title="'.num_format( $effect[ $j ]['summa'] ).'">'.num_format( $effect[ $j ]['summa'] ).'</DIV>
			</TD>
			<TD class="text-right" class="Bold fs-11">
				<DIV title="'.num_format( $effect[ $j ]['marga'] ).'">'.num_format( $effect[ $j ]['marga'] ).'</DIV>
			</TD>
			<TD></TD>
		</TR>
		';

			$j++;

		}
	}
	?>
	</TBODY>
</TABLE>

<div style="height: 90px"></div>