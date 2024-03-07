<?php
/**
 * @license  http://isaler.ru/
 * @author   Vladislav Andreev, http://iandreyev.ru/
 * @charset  UTF-8
 * @version  6.4
 */

set_time_limit( 0 );
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

function dateFormat( $date_orig, $format = 'excel' ) {

	$date_new = '';

	if ( $format == 'excel' ) {

		if ( $date_orig != '0000-00-00' && $date_orig != '' && $date_orig != NULL ) {
			$date_new = $date_orig;
		}

	}
	elseif ( $format == 'date' ) {

		if ( $date_orig && $date_orig != '0000-00-00' ) {

			$date_new = explode( "-", $date_orig );
			$date_new = $date_new[ 1 ]."-".$date_new[ 2 ]."-".$date_new[ 0 ];

		}

	}

	return $date_new;

}

function num2excelExt( $string, $s = 2 ) {

	$string = str_replace( ",", ".", $string );
	$string = str_replace( " ", "", $string );

	return number_format( $string, $s, '.', '' );
}

$action = $_REQUEST[ 'action' ];
$da1    = $_REQUEST[ 'da1' ];
$da2    = $_REQUEST[ 'da2' ];

$user_list = (array)$_REQUEST[ 'user_list' ];

$thisfile = basename( $_SERVER[ 'PHP_SELF' ] );

$sort = '';

//массив выбранных пользователей
if ( !empty($user_list) ) {
	$sort .= " and ".$sqlname."history.iduser IN (".implode( ",", $user_list ).")";
}
else {
	$sort .= str_replace( "iduser", $sqlname."history.iduser", get_people( $iduser1 ) );
}

if ( $da1 != '' ) {
	$sort .= " and (".$sqlname."history.datum BETWEEN '".$da1." 00:00:01' and '".$da2." 23:59:59')";
}

$rez = [];

if ( $action == 'export' ) {
	$format = 'excel';
}
else {
	$format = 'date';
}


//перебираем сделки
$q  = "
	SELECT
		".$sqlname."history.datum,
		".$sqlname."history.tip,
		".$sqlname."history.des as content,
		".$sqlname."history.iduser,
		".$sqlname."history.clid,
		".$sqlname."history.did,
		substring(".$sqlname."history.des, 0, 250) as des,
		".$sqlname."user.title as user,
		".$sqlname."clientcat.title as client,
		".$sqlname."dogovor.title as dogovor
	FROM ".$sqlname."history
		LEFT JOIN ".$sqlname."user ON ".$sqlname."history.iduser = ".$sqlname."user.iduser
		LEFT JOIN ".$sqlname."clientcat ON ".$sqlname."history.clid = ".$sqlname."clientcat.clid
		LEFT JOIN ".$sqlname."dogovor ON ".$sqlname."history.did = ".$sqlname."dogovor.idcategory
	WHERE
		".$sqlname."history.cid > 0
		$sort and
		".$sqlname."history.identity = '$identity'
		ORDER BY ".$sqlname."history.datum
";
$re = $db -> query( $q );
while ( $da = $db -> fetch( $re ) ) {

	if ( dateFormat( $da[ 'datum' ], $format ) != '' ) {

		$rez[] = [
			"datum"   => dateFormat( $da[ 'datum' ], $format ),
			"year"    => get_year( $da[ 'datum' ] ),
			"tip"     => $da[ 'tip' ],
			"content" => untag( $da[ 'content' ] ),
			"des"     => $da[ 'des' ],
			"clid"    => $da[ 'clid' ],
			"client"  => $da[ 'client' ],
			"did"     => $da[ 'did' ],
			"dogovor" => $da[ 'client' ],
			"user"    => $da[ 'user' ]
		];

	}

}

//print_r($rez);
//exit();

if ( $action == 'export' ) {

	$data = ["list" => $rez];
	
	require_once $rootpath."/vendor/tinybutstrong/opentbs/tbs_plugin_opentbs.php";

	$templateFile = 'templates/exportHistoryTemp.xlsx';
	$outputFile   = 'exportHistoryDoka.xlsx';

	$TBS = new clsTinyButStrong(); // new instance of TBS
	$TBS -> PlugIn( TBS_INSTALL, OPENTBS_PLUGIN ); // load the OpenTBS plugin

	$TBS -> SetOption( 'noerr', true );
	$TBS -> LoadTemplate( $templateFile, OPENTBS_ALREADY_UTF8 );

	$TBS -> MergeBlock( 'list', $data[ 'list' ] );
	$TBS -> Show( OPENTBS_DOWNLOAD, $outputFile );

	exit();

}
if ( !$action ) {
	?>
	<STYLE type="text/css">
		<!--
		.color1 {
			background : rgba(181, 59, 46, 0.4);
			color      : #222;
		}
		.color2 {
			background : rgba(181, 59, 46, 0.6);
			color      : #222;
		}
		.color3 {
			background : rgba(181, 59, 46, 0.8);
			color      : #222;
		}
		.color4 {
			background : rgba(181, 59, 46, 1.0);
			color      : #FFF;
		}
		.color5 {
			background : rgba(181, 59, 46, 1.2);
			color      : #FFF;
		}
		.color6 {
			background : rgba(181, 59, 46, 1.4);
			color      : #FFF;
		}
		.itog {
			background : rgba(46, 204, 113, 0.3);
		}
		.hist {
			background : rgba(41, 128, 185, 0.3);
		}

		.color:hover, .color:active {
			background         : #F1C40F;
			color              : #222;
			font-weight        : 700;
			font-size          : 1.4em;
			transition         : all 400ms ease;
			-webkit-transition : all 400ms ease;
			-moz-transition    : all 400ms ease;
		}
		.itog:hover, .itog:active {
			background         : rgba(46, 204, 113, 1.0);;
			color              : #FFF;
			font-weight        : 700;
			font-size          : 1.4em;
			transition         : all 400ms ease;
			-webkit-transition : all 400ms ease;
			-moz-transition    : all 400ms ease;
		}
		.hist:hover, .hist:active {
			background         : rgba(41, 128, 185, 1.0);
			color              : #FFF;
			font-weight        : 700;
			font-size          : 1.4em;
			transition         : all 400ms ease;
			-webkit-transition : all 400ms ease;
			-moz-transition    : all 400ms ease;
		}
		table.borderer thead tr th,
		table.borderer tr,
		table.borderer td {
			border-left   : 1px dotted #ccc !important;
			border-bottom : 1px dotted #ccc !important;
			padding       : 2px 3px 2px 3px;
			height        : 30px;
		}

		table.borderer thead th:last-child {
			border-right : 1px dotted #ccc !important;
		}

		table.borderer thead tr:first-child th {
			border-top : 1px dotted #ccc !important;
		}

		table.borderer td:last-child {
			border-right : 1px dotted #ccc !important;
		}

		table.borderer td.count:hover {
			background : rgba(197, 225, 165, 1);
			cursor     : pointer;
		}

		table.borderer thead {
			border : 1px dotted #ccc !important;
		}

		table.borderer thead td,
		table.borderer thead th {
			/*background : #E5F0F9;*/
			color : #222;
		}

		table.borderer thead th {
			border-bottom : 1px dotted #666 !important;
		}

		table.borderer thead {
			border : 1px dotted #222 !important;
		}
		-->
	</STYLE>

	<br>

	<div class="zagolovok_rep" align="center">
		<b>Сводный отчет</b>
	</div>

	<hr>
	<div style="width: 98.5%; overflow: auto" id="datatable">

		<a href="javascript:void(0)" onclick="exportHistory()" class="button" title="">Получить отчет</a>

	</div>

	<script src="/assets/js/tableHeadFixer/tableHeadFixer.js"></script>
	<script>

		$(function () {

			$("#zebra").tableHeadFixer({
				'head': true,
				'foot': false,
				'z-index': 12000,
				'left': 2
			}).css('z-index', '100');

			//$("#zebra").find('th:nth-child(1)').css('z-index', '110');
			//$("#zebra").find('th:nth-child(2)').css('z-index', '110');
			$("#zebra").find('td:nth-child(1)').css('z-index', '110');
			$("#zebra").find('td:nth-child(2)').css('z-index', '110');

		});

		function exportHistory() {

			var str = $('#selectreport').serialize();
			window.open('reports/' + $('#report option:selected').val() + '?action=export&' + str);

		}
	</script>

<?php } ?>