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
if ( !empty( $user_list ) ) {
	$sort .= " deal.iduser IN (".yimplode( ",", $user_list ).") AND ";
}

if ( !empty( $clients_list ) && !empty( $persons_list ) ) {
	$sort .= "(deal.clid IN (".yimplode( ",", $clients_list ).") OR deal.pid IN (".yimplode( ",", $persons_list ).")) AND ";
}
elseif ( !empty( $clients_list ) ) {
	$sort .= "deal.clid IN (".yimplode( ",", $clients_list ).") AND ";
}
elseif ( !empty( $persons_list ) ) {
	$sort .= "deal.pid IN (".yimplode( ",", $persons_list ).") AND ";
}

//составляем запрос по параметрам сделок
$ar = [
	'close',
	'idcategory'
];
foreach ( $fields as $i => $field ) {

	if ( !in_array( $field, $ar ) && !in_array( $field, [
			'close',
			'mcid'
		] ) ) {
		$sort .= " deal.".$field." = '".$field_query[ $i ]."' AND ";
	}
	elseif ( $field == 'close' ) {
		$sort .= $field_query[ $i ] != 'yes' ? " COALESCE(deal.{$field}, 'no') != 'yes' AND " : " COALESCE(deal.{$field}, 'no') == 'yes' AND ";
	}
	elseif ( $field == 'mcid' ) {
		$mc = $field_query[ $i ];
	}

}

$list = $toplist = [];

$res = $db -> getAll( "SELECT * FROM ".$sqlname."clientcat WHERE type = 'contractor' and identity = '$identity' ORDER by title" );
foreach ( $res as $data ) {

	$string = '';
	$result = $db -> getAll( "SELECT * FROM ".$sqlname."dogprovider WHERE conid = '".$data['clid']."' and identity = '$identity'" );
	foreach ( $result as $datad ) {

		//if($datad['did'] < 1) goto ex;

		//получим данные по сделке
		$dataar = get_dog_info( (int)$datad['did'], 'yes' );

		if ( $datad['summa'] == 0 ) {
			continue;
		}

		$list[ $datad['conid'] ][] = [
			"clid"   => $dataar['clid'],
			"client" => current_client( $dataar['clid'] ),
			"pid"    => $dataar['pid'],
			"person" => current_person( $dataar['pid'] ),
			"did"    => $dataar['did'],
			"deal"   => $dataar['title'],
			"step"   => current_dogstepname( $dataar['idcategory'] ),
			"date"   => format_date( $dataar['datum_plan'] ),
			"summa"  => $datad['summa'],
			"user"   => current_user( $dataar['iduser'] )
		];

		$toplist[ $datad['conid'] ] += $datad['summa'];

		$dataar = [];
		$i++;

		ex:

	}

}

function cmp($a, $b): bool {

	return $b['summa'] > $a['summa'];

}

uksort( $list, 'cmp' );
arsort( $toplist );

$summaTotal = 0;
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
		height       : 2.0em;
		line-height  : 2.0em;
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
		top      : 0;
	}
	.flh-20 {
		line-height : 2.0em;
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
	<h1 class="uppercase fs-14 m0 mb10">Поставщики по сделкам</h1>
	<div class="blue">за период&nbsp;с&nbsp;<?= format_date_rus( $da1 ) ?>&nbsp;по&nbsp;<?= format_date_rus( $da2 ) ?></div>
</div>

<hr>

<table id="zebra">
	<thead class="sticked--top">
	<TR height="30" class="header_contaner">
		<th width="60" align="center"><B>№ п/п</B></th>
		<th align="center"><B>Сделка</B></th>
		<th width="350" align="center"><B>Заказчик</B></th>
		<th width="100" align="left"><b>Статус сделки</b></th>
		<th width="100" align="center"><B>Дата</B></th>
		<th width="150" align="center"><B>Стоимость, <?= $valuta ?></B></th>
		<th width="100"></th>
	</TR>
	</thead>
	<?php
	$i = 1;
	foreach ( $toplist as $provider => $summa ) {

		if ( !empty( $list[ $provider ] ) ) {

			$max   = arrayMax( $toplist ) -> max;
			$wk    = arraysum( $list[ $provider ], 'summa' ) / $max * 100;
			$total = arraysum( $list[ $provider ], 'summa' ) / array_sum( $toplist ) * 100;

			print '
			<TR height="2.0em" class="graybg fs-12">
				<TD align="center">&nbsp;</TD>
				<TD colspan="4">
					
					<DIV class="progressbar wp100 graybg22">
						<div class="progressbar-head wp100 flh-20">
							
							<a href="javascript:void(0)" onClick="openClient(\''.$provider.'\')" class="Bold uppercase flh-20" title="Открыть карточку"><i class="icon-building blue"></i> '.current_client( $provider ).'</a> [ '.round( $total, 2 ).'% ]
							
						</div>
						<DIV id="test" class="progressbar-completed progress-gray" style="width:'.$wk.'%;"></DIV>
					</DIV>
					
				</TD>
				<TD align="right"><b>'.num_format( arraysum( $list[ $provider ], 'summa' ) ).'</b> '.$valuta.'</TD>
				<TD></TD>
			</TR>';

			uksort( $list[ $provider ], 'cmp' );

			foreach ( $list[ $provider ] as $value ) {

				print '
				<TR height="40" class="ha">
					<TD align="center">'.$i.'</TD>
					<TD>
						<div class="ellipsis Bold">
							'.($value['did'] > 0 ? '<a href="javascript:void(0)" onClick="viewDogovor(\''.$value['did'].'\')"><i class="icon-briefcase-1 blue"></i> <span class="fs-12">'.$value['deal'].'</span></a></div>' : '<div class="gray">Без сделки</div>').'
						</div><br>
						<div class="ellipsis mt5 gray2 fs-09">
							'.($value['user'] != '' ? '<i class="icon-user-1 broun"></i> '.$value['user'] : '').'
						</div>
					</TD>
					<TD>
						<div class="ellipsis Bold">
							'.($value['clid'] > 0 ? '<a href="javascript:void(0)" onClick="viewClient(\''.$value['clid'].'\')"><i class="icon-building broun"></i> '.$value['client'].'</a>' : '').'
							'.($value['clid'] == 0 && $value['pid'] > 0 ? '<a href="javascript:void(0)" onClick="viewPerson(\''.$value['pid'].'\')"><i class="icon-user-1 blue"></i> '.$value['person'].'</a>' : '').'
						</div>
					</TD>
					<TD align="left">'.($value['step'] != '' ? $value['step'].' %' : '--').'</TD>
					<TD align="center">'.$value['date'].'</TD>
					<TD align="right">'.num_format( $value['summa'] ).'</TD>
					<TD></TD>
				</TR>
				';

				$summaTotal += $value['summa'];

				$i++;

			}

		}

	}
	?>
	<TR height="40" bgcolor="#FC9">
		<TD align="right">&nbsp;</TD>
		<TD align="right">&nbsp;</TD>
		<TD align="right">&nbsp;</TD>
		<TD align="right">&nbsp;</TD>
		<TD align="right"><b>ВСЕГО:</b></TD>
		<TD align="right"><B>&nbsp;<?= num_format( $summaTotal ) ?></B></TD>
		<TD align="right">&nbsp;</TD>
	</TR>
</TABLE>

<div style="height:90px">
	<div>