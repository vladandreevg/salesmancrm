<?php

/* ============================ */
/*         SalesMan CRM         */
/* ============================ */
/* (C) 2018 Vladislav Andreev   */
/*   Developer: Ivan Drachyov   */
/*       SalesMan Project       */
/*        www.isaler.ru         */
/*        ver. 2018.x           */
/* ============================ */

/**
 * Рассылка суточной статистики. В работе. Пока какая-то хуйня получается
 */

error_reporting( E_ERROR );

$ypath = realpath( __DIR__.'/../' );

include $ypath."/inc/config.php";
include $ypath."/inc/dbconnector.php";
include $ypath."/inc/settings.php";
include $ypath."/inc/func.php";
//include $ypath."/inc/class/Statistic.php";

if ( empty( $productInfo ) )
	$productInfo = [
		"name"    => "SalesMan CRM",
		"site"    => "http://isaler.ru",
		"crmurl"  => "",
		"email"   => "info@isaler.ru",
		"support" => "support@isaler.ru",
		"info"    => "info@isaler.ru"
	];

//Определяем период отчета
// до 12:00 - отчет за пред. день, после 12 за сегодня
$day = ( date( 'H' ) > 11 ) ? 'today' : 'yestoday';
$raz = ( $day == 'today' ) ? 0 : 1;

$today = date( "Y-m-d", mktime( 0, 0, 0, date( 'm' ), date( 'd' ) - $raz, date( 'Y' ) ) + $tm * 3600 );

if ( $productInfo[ 'crmurl' ] == '' )
	$productInfo[ 'crmurl' ] = isset( $_SERVER[ 'HTTP_SCHEME' ] ) ? $_SERVER[ 'HTTP_SCHEME' ] : ( ( ( isset( $_SERVER[ 'HTTPS' ] ) && $_SERVER[ 'HTTPS' ] != 'off' ) || 443 == $_SERVER[ 'SERVER_PORT' ] ) ? 'https://' : 'http://' ).$_SERVER[ "HTTP_HOST" ];

if ( $productInfo[ 'info' ] == '' )
	$productInfo[ 'info' ] = 'info@'.$_SERVER[ "HTTP_HOST" ];

if ( $productInfo[ 'phone' ] == '' )
	$productInfo[ 'phone' ] = '+7(922)3289466';

$template = '
	<!DOCTYPE html>
	<html lang="ru">
	<head>
		<title>Дела на сегодня</title>
		<meta content="text/html; charset=utf-8" http-equiv="content-type">
		<meta http-equiv="X-UA-Compatible" content="IE=edge">
		<meta name="viewport" content="user-scalable=no, initial-scale=1.0, maximum-scale=1.0"/>
		<meta name="apple-mobile-web-app-capable" content="yes"/>
		<meta name="apple-mobile-web-app-status-bar-style" content="default">
		<style type="text/css">
			<!--
			body {
				color:#000;
				font-size: 14px;
				font-family: arial, tahoma;
				background:#eee;
				padding:0;
				margin:0;
			}
			div{
				margin:0;
				display:block;
			}
			hr{
				width:100%;
				border:0 none;
				border-top: #ccc 1px dotted;
				padding:0; 
				height:1px;
				clear:both;
			}
			.green { 
				color: #349C5A;
			}
			.blue, .blue a, a { 
				color:#00548C;
			}
			.head{
				background:transparent;
				padding:5px;
				margin:10px auto;
				height:50px;
				border:0 solid #DFDFDF;
			}
			.blok{
				font-size: 14px;
				color: #000;
				border:1px solid #DFDFDF;
				line-height: 18px;
				margin:10px auto;
				/*padding: 10px 10px;*/
				background:#FFF;
				padding-bottom: 20px;
			
			}
			.todo{
				float:left;
				color: #000;
				padding:5px 5px;
			}
			.logo img{
				height: 40px;
			}
			.red{
				color : red;
			}
			.green{
				color : green;
			}
			.border-red{
				border-left: 2px solid red;
			}
			.border-gray{
				border-left: 2px solid gray;
			}
			.border-blue{
				border-left: 2px solid blue;
			}
			sup{
				font-size: 0.7rem;
				font-weight: 700;
			}
			.fs-07{
				font-size: 0.7rem;
				font-weight: 700;
				line-height: 1.0rem;
			}
			.fs-07 a{ 
				display: block;
				margin-top: 5px;
				font-size: 0.8rem;
				color:blue;
				text-decoration: none;
			}
			.fs-09{
				font-size: 0.9rem;
				font-weight: 700;
				line-height: 1.0rem;
			}
			.fs-12{
				font-size: 0.9rem;
				font-weight: 700;
				line-height: 1.3rem;
			}
			.title{
				font-size: 0.8em;
				text-transform: uppercase;
				color: gray;
			}
			.infodiv {
				border        : 1px dashed #BFCFFF;
				background    : #E5F0F9;
				font-size     : 1.0em;
				padding       : 10px;
				display       : block;
				border-radius : 3px;
			}
			.p5 {
				padding : 5px !important;
			}
			.p10 {
				padding : 10px !important;
			}
			.pl10 {
				padding-left : 10px !important;
			}
			.pr10 {
				padding-right : 10px !important;
			}
			.mb10 {
				margin-bottom: 10px !important;
			}
			
			@media (max-width: 989px) {
				.head{
					width:auto;
					margin:5px;
				}
				.todo{
					font-size: 16px;
				}
				.logo img{
					height: 30px;
					margin-top: -0px;
				}
				.blok{
					width:auto;
					margin:5px;
				}
			}
			-->
		</style>
	</head>
	<body>
	<DIV style="width:98%; max-width:600px; margin: 0 auto">
	
		<div class="head" align="left">
			<div class="todo" style="display:table; width:100%;">
				
				<img src="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAADAAAAAwCAYAAABXAvmHAAAAAXNSR0IArs4c6QAAAARnQU1BAACxjwv8YQUAAAAJcEhZcwAADsMAAA7DAcdvqGQAAAIySURBVGhD7ZlNS2pRFIb9IX0NnTWoc6YXFIKS/ls/okkZ3gocnkY2CyMKlI4QdO/w1r2pNT2dba+x03e53LK57uA88GCs9lrrPepBqVJBQYE7pxeXtZ8Xrd8jk9Yeys74muPMadL6lS/PRiaXjyg742uOM59LIcrO+JpDedvZXh1WopNhNeoPq3FmO7l48vfzqs+J+oNq3OjvbJURaz4Q/ml6YJz1K9OLTY2dnaXbnOj59Ue8gng6H888GxRnd5vlqcWmxs7O0n1OVEc8nfzwgA+Js2RjbWpxsrFOz87SeU4lekE8HToAmsVnJ+efS8/q5wtfgOscxNNhzWPNy9ys7Y+Wm6XN3drCbyHXOYinw5rHmhvNLDLPltH8vOhN7DoH8XRYcwging5rDkHE02HNIYh4OtfdXhaiiKfDmkMQ8XRYcwging5rDkHE02HNtgeHjS/6rksing5rtpUW+6pLIp4Oa7aVFvuqSyKeDmu2lRb7qksins6fvy9ZiCKeDmsOQcTTYc224+/wY7X65FcC7bwk4umwZltpsVQvLgAing5rtpUWS/XgLmBZIp4Oaw5BxNNhHyK20geQr7ok4umwZltpsa+6JOLpsGZbabGvuiTi6bBmW2mxr7ok4umw5hBEPB3WHIKIp9Pu9gZswFLt9Ob/4267kzbokCXa7vSOEE/nqvtQbnfTZzZoGeZZnm7u7+f/B4fhNk1X8+a6eekmB/43O+m//PHYOXxBQcF3oVR6B/LlQSoOgy8bAAAAAElFTkSuQmCC" style="float: left; margin-right: 10px;">
				<div style="display: inline-block; padding-top: 5px;">Дела на сегодня<br><b>{{datum}}</b></div>
				
			</div>
		</div>
	
		{{#tasks}}
		<div class="blok {{border}}">

			<div style="color:black; font-size:12px; margin-top: 5px;">
			
				<div class="p5 pl10" style="margin-bottom:0; font-size:16px">
					<b class="blue">{{time}}</b>&nbsp;{{{priority}}}&nbsp;<b>{{title}}</b>&nbsp;
					<sup style="float:right">{{tip}}</sup>
				</div>
				
				{{#agenda}}
				<hr>
				<div class="pl10 pr10">
					<b class="title">Агенда:</b>
					<div class="infodiv">{{{agenda}}}</div>
				</div>
				{{/agenda}}
				
				{{#iscard}}
					<hr>
					<div class="p5 pl10">
						
						{{#client}}
						<b class="title">Клиент</b>
						<div class="fs-12"><a href="{{crmurl}}/card.client.php?clid={{id}}" target="_blank">{{title}}</a></div>
						{{/client}}
						
					</div>
					
					{{#isperson}}
					<hr>
					<div class="p5 pl10">
					
						<b class="title">Контакты:</b>
						
						{{#person}}
						<div class="fs-12 mb10">
							<a href="{{crmurl}}/card.person.php?pid={{id}}" target="_blank">{{title}}</a>
							{{#url}}
							<div class="fs-07 pl10">{{{url}}}</div>
							{{/url}}
						</div>
						{{/person}}
						
					</div>
					{{/isperson}}
						
					{{#deal}}
					<hr>
					<div class="p5 pl10">
						<b class="title">Сделка</b>
						<div class="fs-12"><a href="{{crmurl}}/card.deal.php?did={{id}}" target="_blank">{{title}}</a></div>
					</div>
					{{/deal}}
					
				{{/iscard}}
				
				{{#autor}}
				<hr>
				<div class="p10" align="right">
					Назначил: <b style="color:red">{{autor}}</b>
				</div>
				{{/autor}}
				
			</div>
			
		</div>
		{{/tasks}}
	
		<div style="font-size:10px; margin-top:10px; padding: 10px 10px; margin-bottom: 10px;" align="right">
			<div>SalesMan CRM Team</div>
		</div>
	
	</DIV>
	</body>
	</html>
';

//перебирем всех активных руководителей
$boss = $result = $db -> getAll( "SELECT iduser, email, title, identity FROM ".$sqlname."user WHERE secrty = 'yes' AND tip LIKE 'Руководитель%' ORDER BY iduser" );
foreach ( $result as $data ) {

	$html  = '';
	$pokaz = [];

	$Stat = Salesman\Statistic ::all( $day, ["user" => $data[ 'iduser' ]] );

	$StatToday = [
		[
			"title" => "Новых клиентов",
			"value" => $Stat[ 'details' ][ 'clients' ],
			"url"   => "/clients.php#search"
		],
		[
			"title" => "Новых клиентов на внедрение",
			"value" => $db -> getOne( "SELECT COUNT(*) FROM ".$sqlname."clientcat WHERE DATE(date_create) = '$today' ORDER BY iduser" ),
			"url"   => "/clients.php#search"
		],
		[
			"title" => "Новых сделок",
			"value" => $Stat[ 'details' ][ 'deals' ][ 'new' ][ 'count' ],
			"url"   => "/deals.php#alldealsday"
		],
		[
			"title" => "Новых сделок на сумму",
			"value" => num_format( $Stat[ 'details' ][ 'deals' ][ 'new' ][ 'sum' ] ).' '.$valuta,
			"url"   => "/deals.php#alldealsday"
		],
		[
			"title" => "Закрыто сделок",
			"value" => $Stat[ 'details' ][ 'deals' ][ 'close' ][ 'count' ],
			"url"   => "/deals.php#close"
		],
		[
			"title" => "Закрыто сделок на сумму",
			"value" => num_format( $Stat[ 'details' ][ 'deals' ][ 'close' ][ 'sum' ] ).' '.$valuta,
			"url"   => "/deals.php#close"
		],
		[
			"title" => "Выставлено счетов на сумму",
			"value" => num_format( $Stat[ 'details' ][ 'invoices' ][ 'sum' ] ).' '.$valuta,
			"url"   => "/contracts.php#payment"
		],
		[
			"title" => "Оплачено счетов на сумму",
			"value" => num_format( $Stat[ 'details' ][ 'payments' ][ 'sum' ] ).' '.$valuta,
			"url"   => "/contracts.php#payment"
		]
	];

	$managers = $db -> getAll( "SELECT iduser,kol_plan,marga FROM ".$sqlname."plan WHERE iduser = '$data[iduser]' AND year = '".date( 'Y' )."' AND mon = '".date( 'm' )."' AND identity = '$identity' ORDER BY iduser" );

	$sumKol = $sumMarga = $sumKMonth = $sumMMonth = $planMargaOtdel = $planKolOtdel = 0;
	$kplan  = $kolfact = $margafact = $mplan = $kfactMonth = $mfactMonth = 0;

	// Для сделок, по которым есть оплаты

	//За сегодня
	$invoices = $db -> getAll( "SELECT * FROM ".$sqlname."credit WHERE do = 'on' AND invoice_date = '$today' AND iduser IN (".yimplode( ",", get_people( $data[ 'iduser' ], "yes", true ) ).") AND identity = '$identity' ORDER BY did" );
	foreach ( $invoices as $invoice ) {

		//расчет процента размера платежа от суммы сделки
		$deal = $db -> getRow( "SELECT kol,marga FROM ".$sqlname."dogovor WHERE did = '".$invoice[ 'did' ]."' AND identity = '$identity'" );

		//сумма всей сделки
		$kfact = pre_format( $deal[ "kol" ] );
		//сумма маржи сделки
		$mfact = pre_format( $deal[ "marga" ] );

		//% оплаченной суммы от суммы по договору
		$dolya = ( $kfact > 0 ) ? $invoice[ 'summa_credit' ] / $kfact : 0;

		// Фактическая оплата и маржа
		$kfact = $invoice[ 'summa_credit' ];
		$mfact = $mfact * $dolya;

		$kolfact   += $kfact;
		$margafact += $mfact;

	}

	//За месяц текущий
	$invoices = $db -> getAll( "SELECT * FROM ".$sqlname."credit WHERE do = 'on' AND MONTH(invoice_date) = '".date( 'm' )."' AND YEAR(invoice_date) = '".date( 'Y' )."' AND iduser IN (".yimplode( ",", get_people( $data[ 'iduser' ], "yes", true ) ).") AND identity = '$identity' ORDER BY did" );
	foreach ( $invoices as $invoice ) {

		//расчет процента размера платежа от суммы сделки
		$deal = $db -> getRow( "SELECT kol,marga FROM ".$sqlname."dogovor WHERE did = '".$invoice[ 'did' ]."' AND identity = '$identity'" );

		//сумма всей сделки
		$kfact = pre_format( $deal[ "kol" ] );
		//сумма маржи сделки
		$mfact = pre_format( $deal[ "marga" ] );

		//% оплаченной суммы от суммы по договору
		$dolya = ( $kfact > 0 ) ? $invoice[ 'summa_credit' ] / $kfact : 0;

		// Фактическая оплата и маржа
		$kfact = $invoice[ 'summa_credit' ];
		$mfact = $mfact * $dolya;

		$kfactMonth += $kfact;
		$mfactMonth += $mfact;

	}

	$sumKol    += $kolfact;
	$sumMarga  += $margafact;
	$sumKMonth += $kfactMonth;
	$sumMMonth += $mfactMonth;

	$dataa[ $data[ 'iduser' ] ] = [
		"marga"      => $margafact,
		"kol"        => $kolfact,
		"margaMonth" => $mfactMonth,
		"kolMonth"   => $kfactMonth,
		"mplan"      => $managers[ 'marga' ],
		"kplan"      => $managers[ 'kol_plan' ],
		"iduser"     => $data[ 'iduser' ]
	];

	rsort( $dataa );

	$string = '';

	foreach ( $dataa as $da ) {

		$kpers = ( $da[ 'kplan' ] > 0 ) ? round( $da[ 'kolMonth' ] / $da[ 'kplan' ] * 100, 2 ) : 0;
		$mpers = ( $da[ 'mplan' ] > 0 ) ? round( $da[ 'margaMonth' ] / $da[ 'mplan' ] * 100, 2 ) : 0;

		$string .= '
			<tr style="height: 3vh;">
				<td>'.current_user( $da[ 'iduser' ], "yes" ).'</td>
				<td style="text-align: right;">'.num_format( $da[ 'kol' ] + 0 ).' '.$valuta.'</td>
				<td style="text-align: center;">'.num_format( $kpers ).' %</td>
				<td style="text-align: right;">'.num_format( $da[ 'marga' ] + 0 ).' '.$valuta.'</td>
				<td style="text-align: center;">'.num_format( $mpers ).' %</td>
			</tr>
		';

	}

	//$string .= '<tr style="text-align: right;height: 4vh; background: lightyellow"><td><b>По подразделению: </b></td><td>'.num_format($sumKol).$valuta.'</td><td style="text-align: center;">'.num_format($sumKMonth / $planKolOtdel * 100).' %</td><td>'.num_format($sumMarga).$valuta.'</td><td style="text-align: center;">'.num_format($sumMMonth / $planMargaOtdel * 100).' %</td></tr>';

	$html .= '
		<div style="width: 99%; margin: 0; background: slategray; color:white; float:left;padding:5px 5px;font-size: 14px;">
			Выполнение плана сотрудниками
		</div>
		<table style="width: 97%; margin: 0 0 0.7vh 0.7vw; border-collapse: collapse; border: 1px">
			<thead>
			<tr style="height: 3vh">
				<th style="width: 50%;" rowspan="2"></th>
				<th style="width: 25%;" colspan="2">Оборот</th>
				<th colspan="2">Маржа</th>
			</tr>
			<tr>
				<th>за сегодня</th>
				<th>всего</th>
				<th>за сегодня</th>
				<th>всего</th>
			</tr>
			</thead>
			'.$string.'
		</table>
	';

	//Формируем письмо для отправки
	$from     = 'no-replay@'.$_SERVER[ 'HTTP_HOST' ];
	$fromname = 'Статистика '.$productInfo[ 'name' ];
	$subject  = 'Статистика за '.format_date_rus( $today ).' [CRM]';

	$message = '
	<html>
		<div style="color:black;font-size: 14px;font-family: arial, tahoma;background:#DCDCDC;padding:0;margin:0;">
			<DIV style="width:50vw; margin: 0 auto;">
			
				<table align="left" style="width:100%;background:rgba(240,50,50, 0.8);padding:5px;margin:10px auto;height:60px;font-size: 16px;border:0 solid #DFDFDF;">
					<tr style="float:left;color: #000;display:table;width:100%;">
						<td>
							<div style="padding:0 0 0 5px"><a href="'.$productInfo[ 'crmurl' ].'" style="color:white; text-decoration:none;">'.$data[ 'title' ].'</a>
							</div>
							<br>
							<div style="padding: 0 0 0 5px"><b>Отчет за <span style="color:white">'.format_date_rus_name( $today ).'</span></b></div>
						</td>
						<td style="text-align: right"><a href="'.$productInfo[ 'crmurl' ].'"><img src="'.$productInfo[ 'crmurl' ].'/images/logo-white.png" style="height: 30px"></a></td>
					</tr>
				</table>
				
				<div style="float:left;color: #000;width:100%;">
					'.$html.'
				</div>
				<div style="font-size:12px; margin-top:20px; padding: 10px 10px; margin-bottom: 10px;" align="right">
					<div>© '.$productInfo[ 'name' ].' Team</div>
				</div>
				
			</DIV>
		</div>
	</html>
	';

	//mailer( $data[ 'email' ], $data[ 'title' ], $from, $fromname, $subject, $message );
	mailto( [$data[ 'email' ], $data[ 'title' ], $from, $fromname, $subject, $message] );

	$tomail = '';
	$html   = '';

}


exit();