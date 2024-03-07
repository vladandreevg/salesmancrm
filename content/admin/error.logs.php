<?php
/* ============================ */
/*         SalesMan CRM         */
/* ============================ */
/* (C) 2016 Vladislav Andreev   */
/*       SalesMan Project       */
/*        www.isaler.ru         */
/*        ver. 2017.x           */
/* ============================ */

error_reporting( 0 );
header( "Pragma: no-cache" );

if ( empty( $_SERVER['HTTP_X_REQUESTED_WITH'] ) && strtolower( $_SERVER['HTTP_X_REQUESTED_WITH'] ) != 'xmlhttprequest' ) {
	print '<div class="bad" align="center"><br>Доступ запрещен.<br>Обратитесь к администратору.<br><br></div>';
	exit();
}

$rootpath = dirname(__DIR__, 2);

include $rootpath."/inc/config.php";
include $rootpath."/inc/dbconnector.php";
include $rootpath."/inc/auth.php";
include $rootpath."/inc/func.php";
include $rootpath."/inc/settings.php";

$thisfile = basename( __FILE__ );

$handle = @fopen( $rootpath."/cash/salesman_error.log", 'rb' );

fseek( $handle, -261000, SEEK_END );

$i     = $j = 0;
$error = '';
$list  = [];

if ( $handle ) {

	while (($buffer = fgets( $handle, 60000 )) !== false) {

		if ( $buffer[0] != "[" ) {

			$list[ $j ] .= str_replace( "\t", str_repeat( "&nbsp;", 4 ), $buffer );

		}
		else {

			$j++;
			$list[ $j ] = ($buffer != "\r\n") ? $buffer : "";

		}

		/*
		print $j . ":\n";
		print "buffer:\n" . $buffer . "\n";
		print "list:\n" . $list[$j] . "\n";
		print "------------------------------\n";
		*/

	}

	if ( !feof( $handle ) ) {

		$error = "Error: unexpected fgets() fail\n";

	}

	fclose( $handle );

	//print_r($list);
	//exit();

	//arsort( $list );

}

$log = [];
foreach ( $list as $data ) {

	$t = strtotime( $d[0] );

	$data = str_replace( "\n\r", "\n", $data );

	preg_match( "/\[(.+?)\]/", $data, $dates );
	$date = ($dates[0] != '') ? $dates[1] : "";

	$d         = yexplode( " ", $date );
	$log[ $t ] = [
		"datum"    => $t,
		"date"     => date( "d.m.Y", strtotime( $d[0] ) ),
		"time"     => $d[1],
		"timezone" => $d[2],
		"text"     => str_replace( "[$date] ", "", nl2br( highlighter( "Stack trace:", highlighter( "Fatal error:", $data, "red Bold" ), "blue Bold" ) ) )
	];

}

arsort( $log );
//print_r( $log );
?>

<div style="position:fixed; top:60px; right:10px; z-index:100000">

	<a href="/cash/salesman_error.log" target="_blank" class="sbutton">Смотреть в файле</a>

</div>

<TABLE id="zebra" class="mt10 enable--select top">
	<TBODY>
	<?php
	foreach ( $log as $data ) {

		$txt = str_replace(
			"[$data[text] ",
			"",
			nl2br( highlighter( "Stack trace:", highlighter( "Fatal error:", $data['text'], "red Bold" ), "blue Bold" ) )
		);
		?>
		<TR class="th40">
			<TD class="w160">
				<div><?= "<b>".$data['date']."</b> ".$data['time'] ?></div>
				<div class="fs-07 blue"><?= $data['timezone'] ?></div>
			</TD>
			<TD class="fs-10 flh-10 text-wrap">
				<?= str_replace(
					"Full query: ",
					"<br><br><b>Full query:</b><br>",
					$txt
				) ?>
			</TD>
		</TR>
		<?php
	}
	?>
	</TBODY>
</TABLE>

<SCRIPT>
	$("#zebra tr:nth-child(even)").addClass("even");
</SCRIPT>