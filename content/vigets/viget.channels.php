<?php
/* ============================ */
/*         SalesMan CRM         */
/* ============================ */
/* (C) 2016 Vladislav Andreev   */
/*       SalesMan Project       */
/*        www.isaler.ru         */
/*        ver. 2017.x           */
/* ============================ */

error_reporting( E_ERROR );
header( "Pragma: no-cache" );

$rootpath = dirname( __DIR__, 2 );

include $rootpath."/inc/config.php";
include $rootpath."/inc/dbconnector.php";
include $rootpath."/inc/auth.php";
include $rootpath."/inc/func.php";
include $rootpath."/inc/settings.php";
include $rootpath."/inc/language/".$language.".php";

$thisfile = basename( __FILE__ );

$Interval = $_COOKIE['channelsInterval'];

$setperiod = (strlen( $Interval ) > 1 && $Interval != 'undefined') ? $Interval : 'month';

$period = getPeriod( $setperiod );

$da1 = $period[0];
$da2 = $period[1];

//$da1 = '01-01-2017';

//формируем запрос
$queryArray = getFilterQuery( 'client', $param = [
	'iduser'     => $iduser,
	'iduser1'    => $iduser1,
	'filter'     => 'otdel',
	'filterplus' => "and {$sqlname}clientcat.date_create between '$da1' and '$da2'",
	'type'       => 'client',
	'fields'     => [
		'clid',
		'title',
		'creator',
		'clientpath'
	]
] );

$list = [];

//формируем массив
$query  = $queryArray['query'];
$result = $db -> query( $query );
while ($data = $db -> fetch( $result )) {

	if ( $data['clientpath'] == '' )
		$data['clientpath'] = 'Не указано';

	$list[ $data['clientpath'] ]++;

}

arsort( $list );
?>

<style>

	#channels ul.group {
		position              : absolute;
		z-index               : 1;
		top                   : calc(100% - 40px);
		right                 : 10px;
		display               : table;
		list-style            : none;
		background            : rgba(245, 245, 245, 0.3);
		border                : 1px solid #CCD1D9;
		margin-top            : 5px;
		padding               : 0;
		font-size             : 0.9em;
		border-radius         : 4px;
		-moz-border-radius    : 4px;
		-webkit-border-radius : 4px;
	}

	#channels ul.group > li {
		margin       : 0 !important;
		padding      : 5px 10px !important;
		display      : table-cell;
		text-align   : center;
		cursor       : pointer;
		border-right : 1px solid #CCD1D9;
		box-sizing   : border-box !important;
	}

	#channels ul.group > li:last-child {
		border-right : 0;
	}

	#channels ul.group > li:hover,
	#channels ul.group > li.active {
		color        : #fff;
		background   : #C0392B;
		border-color : #C0392B !important;
	}

</style>

<div class="flex-container box--child" style="justify-content: space-between;">

	<div class="flex-string flx-2">&nbsp;</div>

</div>
<?php
//формируем таблицу
foreach ( $list as $path => $count ) {

	if ( $count > 0 )
		print '
	<div class="flex-container box--child ha border-bottom" style="justify-content: space-between;">

		<div class="flex-string wp100 p5 visible-iphone fs-12 Bold">Канал</div>
		<div class="flex-string wp80 flx-2 p5">'.$path.'</div>
		<div class="flex-string wp20 flx-2 p5">'.$count.'</div>

	</div>
	';

}
if ( empty( $list ) ) {
	print '
	<div class="flex-container box--child ha border-bottom" style="justify-content: space-between;">

		<div class="flex-string wp100 flx-2 p5">
		Данные отсутствуют
		</div>

	</div>
	';
}
?>
<div class="flex-container">

	<div class="flex-string flx-2 p10">&nbsp;</div>

</div>

<div class="pull-aright">
	<ul class="group">
		<li data-id="today">Сегодня</li>
		<li data-id="calendarweek">Неделя</li>
		<li data-id="month">Месяц</li>
		<li data-id="quart">Квартал</li>
	</ul>
</div>

<script>

	$('#channels').find('ul.group').find('li[data-id="<?=$setperiod?>"]').addClass('active');

	$('#channels').find('li').bind('click', function () {

		var id = $(this).data('id');

		setCookie('channelsInterval', id, {"expires": 1000000});

		$('#channels').load('/content/vigets/viget.channels.php');

	});

</script>