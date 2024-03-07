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

$users  = (array)$_REQUEST['user_list'];
$fields      = (array)$_REQUEST['field'];
$field_query = (array)$_REQUEST['field_query'];

$sort .= (!empty( $users )) ? "iduser IN (".yimplode( ",", $users ).") and " : "iduser IN (".yimplode( ",", (array)get_people( $iduser1, "yes" ) ).") and ";

//составляем запрос по параметрам сделок
$ar = [
	'sid',
	'close'
];
$dsort = '';
foreach ( $fields as $i => $field ) {

	if ( !in_array( $field, $ar ) && $field != 'close' ) {
		$dsort .= " deal.".$field." = '".$field_query[ $i ]."' AND ";
	}
	elseif ( $field == 'close' ) {
		$dsort .= $field_query[ $i ] != 'yes' ? " COALESCE(deal.{$field}, 'no') != 'yes' AND " : " COALESCE(deal.{$field}, 'no') == 'yes' AND ";
	}

}

$str     = [];
$sumUser = [];

$res = $db -> getAll( "SELECT * FROM ".$sqlname."user WHERE iduser > 0 and $sort identity = '$identity' ORDER BY title" );
foreach ( $res as $data ) {

	$user[ $data['iduser'] ] = $data['title'];

	$ress = $db -> getAll( "SELECT * FROM ".$sqlname."dogcategory WHERE identity = '$identity' ORDER BY title" );
	foreach ( $ress as $dataa ) {

		$step[ $dataa['idcategory'] ] = [
			"step" => $dataa['title'],
			"name" => $dataa['content']
		];

		$resss = $db -> getAll( "
			SELECT * 
			FROM ".$sqlname."dogovor `deal`
			WHERE 
				(
					(deal.close != 'yes' and deal.datum_plan between '".$da1." 00:00:00' and '".$da2." 23:59:59') or 
					(deal.close = 'yes' and deal.datum_close between '".$da1." 00:00:00' and '".$da2." 23:59:59')
				) and 
				deal.idcategory = '".$dataa['idcategory']."' and 
				deal.iduser = '".$data['iduser']."' and
				$dsort 
				deal.identity = '$identity'
			" );
		foreach ( $resss as $da ) {

			if ( $da['close'] != "yes" ) {
				$summa = $da['kol'];
			}
			else {
				$summa = $da['kol_fact'];
			}

			$prov = pre_format( getProviderSum( $da['did'] ) );

			if ( $prov > 0 )
				$itog = $summa - $prov;
			else $itog = 0;

			$str[ $data['iduser'] ][ $dataa['idcategory'] ][ $da['did'] ] = [
				"title"   => $da['title'],
				"client"  => current_client( $da['clid'] ),
				"dplan"   => $da['datum_plan'],
				"dclose"  => $da['datum_close'],
				"isClose" => $da['close'],
				"summa"   => $summa,
				"rashod"  => $prov,
				"itog"    => $itog
			];

			$sumUser[ $data['iduser'] ]['summa']  = $sumUser[ $data['iduser'] ]['summa'] + $summa;
			$sumUser[ $data['iduser'] ]['rashod'] = $sumUser[ $data['iduser'] ]['rashod'] + $prov;
			$sumUser[ $data['iduser'] ]['itog']   = $sumUser[ $data['iduser'] ]['itog'] + $itog;

		}

	}

}

//print_r($step);
//print array2string($str,"<br>","&nbsp;");

//exit();
?>
<STYLE type="text/css">
	<!--
	#salestepss {
		width : 96%;
	}
	#salestepss .pheader {
		display     : block;
		border      : 0 solid #79b7e7;
		background  : #78909C;
		font-weight : bold;
		height      : 30px;
		line-height : 30px;
		color       : #fff;
		font-size   : 12px;
	}
	#salestepss .pstring {
		background  : #CFD8DC;
		font-weight : bold;
		overflow    : hidden !important;
	}
	#salestepss .stringg:hover {
		background : #FF6;
	}
	#salestepss .stringg {
		border-bottom : 1px dotted #78909C;
		overflow      : hidden !important;
		box-sizing    : border-box;
	}
	#salestepss .column_0 {
		width       : 100%;
		height      : 25px;
		line-height : 25px;
		overflow    : hidden !important;
	}
	#salestepss .column_1 {
		width       : 50%;
		display     : inline-block;
		height      : 30px;
		line-height : 30px;
		float       : left;
		overflow    : hidden !important;
		box-sizing  : border-box;
	}
	#salestepss .column_2 {
		width       : 13%;
		display     : inline-block;
		height      : 30px;
		line-height : 30px;
		float       : left;
		overflow    : hidden !important;
		box-sizing  : border-box;
	}
	#salestepss .column_3 {
		width       : 13%;
		display     : inline-block;
		height      : 30px;
		line-height : 30px;
		float       : left;
		overflow    : hidden !important;
		box-sizing  : border-box;
	}
	#salestepss .column_4 {
		width       : 13%;
		display     : inline-block;
		height      : 30px;
		line-height : 30px;
		float       : left;
		overflow    : hidden !important;
		box-sizing  : border-box;
	}
	#salestepss .column_5 {
		width       : 11%;
		display     : inline-block;
		height      : 30px;
		line-height : 30px;
		float       : left;
		overflow    : hidden !important;
		box-sizing  : border-box;
	}
	#salestepss .user {
		background : #FFF9C4; /*#FFD7D7;*/
		cursor     : pointer;
	}
	#salestepss .cur {
		cursor : pointer;
	}
	#salestepss .sb {
		font-size   : 1em;
		font-weight : bold;
		background  : #E6E6FA;
	}
	-->
</STYLE>
<div class="zagolovok_rep">Pipeline. Сделки по Сотрудникам</div>
<hr>
<div id="salestepss" class="margtop20">
	<div class="pheader sticked--top text-center">
		<div class="column_1">Сотрудник / Этап / Сделка</div>
		<div class="column_2">Сумма, <?= $valuta ?></div>
		<div class="column_3">Расходы, <?= $valuta ?></div>
		<div class="column_4">Итого, <?= $valuta ?></div>
		<div class="column_5 paddright5">%</div>
	</div>
	<?php
	foreach ( $str as $key => $value ) {

		$procent = ($sumUser[ $key ]['itog'] / $sumUser[ $key ]['summa']) * 100;

		print '
		<div class="stringg pstring">
			<div class="column_1 paddleft5">'.$user[ $key ].'</div>
			<div class="column_2 text-right">'.num_format( $sumUser[ $key ]['summa'] ).'</div>
			<div class="column_3 text-right">'.num_format( $sumUser[ $key ]['rashod'] ).'</div>
			<div class="column_4 text-right">'.num_format( $sumUser[ $key ]['itog'] ).'</div>
			<div class="column_5 paddright5 text-right">'.num_format( $procent ).' %</div>
		</div>
		';
		foreach ( $value as $key2 => $value2 ) {

			$numbers = array_map( function($details) {
				return $details['summa'];
			}, $value2 );
			$sum     = array_sum( $numbers );

			$numbers = array_map( function($details) {
				return $details['rashod'];
			}, $value2 );
			$rashod  = array_sum( $numbers );

			$numbers = array_map( function($details) {
				return $details['itog'];
			}, $value2 );
			$itog    = array_sum( $numbers );

			$procent = $sum > 0 ? ($itog / $sum) * 100 : 0;

			print '
				<div class="stringg user stepname" data-id="'.$key.$key2.'">
					<div class="column_1 paddleft10"><b>'.$step[ $key2 ]['step'].'%</b> - '.$step[ $key2 ]['name'].'&nbsp;<i class="icon-angle-down"></i></div>
					<div class="column_2 text-right"><b>'.num_format( $sum ).'</b></div>
					<div class="column_3 text-right"><b>'.num_format( $rashod ).'</b></div>
					<div class="column_4 text-right"><b>'.num_format( $itog ).'</b></div>
					<div class="column_5 paddright5 text-right"><b>'.num_format( $procent ).'</b> %</div>
				</div>
				<div class="hidden deals" id="'.$key.$key2.'">
			';
			foreach ( $value2 as $key3 => $value3 ) {

				if ( $value3['isClose'] == 'yes' ) {
					$icon  = '<i class="icon-lock red"></i>';
					$datum = $value3['dclose'];
				}
				else {
					$icon  = '<i class="icon-briefcase-1 broun"></i>';
					$datum = $value3['dplan'];
				}

				$procent = $value3['summa'] > 0 ? ($value3['itog'] / $value3['summa']) * 100 : 0;

				print '
					<div class="stringg cur">
						<div class="column_1 paddleft20">
							<a href="javascript:void(0)" onclick="viewDogovor(\''.$key3.'\')" title="Просмотр">'.$icon.'&nbsp;'.$value3['title'].'</a><sup class="blue">&nbsp;'.format_date_rus( $datum ).'</sup><br>
							<em>'.$value3['client'].'</em>
						</div>
						<div class="column_2 text-right">'.num_format( $value3['summa'] ).'</div>
						<div class="column_3 text-right">'.num_format( $value3['rashod'] ).'</div>
						<div class="column_4 text-right">'.num_format( $value3['itog'] ).'</div>
						<div class="column_5 paddright5 text-right">'.num_format( $procent ).' %</div>
						<div class="column_0 paddleft20 hidden">
							<em><i class="icon-building blue"></i>&nbsp;'.$value3['client'].'</em>
						</div>
					</div>
				';
			}

			print '</div>';
		}
	}
	?>
</div>
<div class="pt10">
	<a href="javascript:void(0)" onclick="openAll()" class="button">Раскрыть/Свернуть все</a>
</div>
<hr>
<div class="infodiv">
	Вы можете использовать параметры: Период, Сотрудники, Сделки. <br><br>
	<b class="red">Важно:</b>
	<ul>
		<li>В отчете выводятся <b>Активные</b> (по плановой дате) и <b>Закрытые</b> (по дате закрытия) сделки</li>
		<li>При расчете итогового параметра учитывается наличие "Расходов" по сделке (расходы, учтенные с помощью вкладки "Связи" в карточке сделки). Если расходы по сделке не указаны, то "Итог" всегда равен 0.</li>
	</ul>
</div>
<div style="height: 90px;"></div>
<script>

	$('.cur').on('click', function () {
		$(this).find('.column_0').toggleClass('hidden');
	});

	$(document).on('click', '.stepname', function () {

		var id = $(this).data('id');
		$('#' + id).toggleClass('hidden');
		$(this).find('i').toggleClass('icon-angle-down icon-angle-up');

	});

	function openAll() {
		$('.deals').toggleClass('hidden');
	}

</script>