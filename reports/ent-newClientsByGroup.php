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

if ( !$per )
	$per = 'nedelya';

$users     = (array)$_REQUEST['user_list'];
$grouplist = (array)$_REQUEST['groups'];

$sort = '';


//выбранные типы активности
if ( count( $grouplist ) > 0 ) {

	$s    = (in_array( '0', $grouplist )) ? " or gl.gid is null" : "";
	$sort .= " (gr.id IN (".implode( ",", $grouplist ).") $s) and ";

}
//или все
else {
	$sort        .= " (gr.id > 0 or gl.gid is null) and ";
	//$grouplist[] = "0";
}
$cpath = $grouplist;

$list = $groups = $users = $stat = [];

//Создание массивов данных
//foreach($user_list as $i => $user){

$q = "
SELECT 
	cc.clid,
	cc.title,
	cc.date_create as dcreate,
	DATE_FORMAT(cc.date_create, '%Y-%m-%d') as dacreate,
	cc.creator,
	us.title as user,
	gl.gid,
	DATE_FORMAT(gl.datum, '%Y-%m-%d') as dagroup,
	gr.name as ggroups
FROM ".$sqlname."clientcat `cc`
	LEFT JOIN ".$sqlname."user `us` ON cc.creator = us.iduser
	LEFT JOIN ".$sqlname."grouplist `gl` ON gl.clid = cc.clid
	LEFT JOIN ".$sqlname."group `gr` ON gr.id = gl.gid
WHERE 
	(gl.datum between '".$da1." 00:00:01' and '".$da2." 23:59:59' or 
	cc.date_create between '".$da1." 00:00:01' and '".$da2." 23:59:59') and 
	$sort
	cc.identity = '$identity'
ORDER BY gl.gid, cc.date_create
";

$result = $db -> query( $q );
while ($data = $db -> fetch( $result )) {

	if ( $data['gid'] == NULL ) {
		$data['gid']    = 0;
		$data['ggroups'] = '<span class="red">Вне групп</span>';
	}
	if ( $data['creator'] == NULL || $data['creator'] == "0" ) {
		$data['creator'] = 0;
		$data['user']    = 'Без ответственного';
	}

	//формируем справочники групп и пользователей
	$groups[ $data['gid'] ]    = $data['ggroups'];
	$users[ $data['creator'] ] = $data['user'];

	//if($data['dagroup'] == '' && $data['dagroup'] == null) $data['dacreate'] = $data['dagroup'];

	$list[ $data['creator'] ][ $data['gid'] ][] = [
		"clid"    => $data['clid'],
		"client"  => $data['title'],
		"dcreate" => $data['dacreate'],
		"dgroup"  => $data['dagroup']
	];

	$stat[ $data['dacreate'] ]++;

}

//}

//print_r($list);

$dday = $newClientsCount = [];
$i    = 0;

ksort( $stat );

foreach ( $stat as $day => $adds ) {

	$newClientsCount[] = $adds;
	$dday[]            = $i.": '".get_date( $day )."'";
	$sday[]            = "'".$day."'";

	$i++;

}

$newClients = yimplode( ",", $newClientsCount );
$days       = yimplode( ",", $dday );
$sdays      = yimplode( ",", $sday );


//$diffMonth = diffDate($da1, $da2) / 30;
$countDay = count( $dday );

if ( $countDay > 120 )
	$chartWidth = 5;
elseif ( $countDay > 99 )
	$chartWidth = 8;
elseif ( $countDay > 80 )
	$chartWidth = 10;
elseif ( $countDay > 60 )
	$chartWidth = 15;
elseif ( $countDay > 40 )
	$chartWidth = 15;
elseif ( $countDay > 30 )
	$chartWidth = 25;
else $chartWidth = 30;
?>

<br>
<div class="zagolovok_rep div-center">
	<span class="fs-12">Новые клиенты по группам</span><br>
	<span class="gray2 em fs-07 noBold pt5">за период&nbsp;с&nbsp;<?= format_date_rus( $da1 ) ?>&nbsp;по&nbsp;<?= format_date_rus( $da2 ) ?></span>
</div>

<div class="row">
	<div class="column12 grid-3 pl10">
		<div class="ydropDown">
			<span>Выбор Групп</span><span class="ydropCount"><?= count( $cpath ) ?> выбрано</span><i class="icon-angle-down pull-aright"></i>
			<div class="yselectBox" style="height: 300px;">
				<div class="yunSelect"><i class="icon-cancel-circled2"></i>Снять выделение</div>
				<div class="ydropString ellipsis">
					<label class="red">
						<input class="taskss" name="groups[]" type="checkbox" id="groups[]" value="0" <?php if ( in_array( '0', $cpath ) )
							print 'checked'; ?>>&nbsp;Вне групп
					</label>
				</div>
				<?php
				$result = $db -> query( "SELECT * FROM ".$sqlname."group WHERE identity = '$identity' ORDER BY name" );
				while ($data = $db -> fetch( $result )) {
					?>
					<div class="ydropString ellipsis">
						<label>
							<input class="taskss" name="groups[]" type="checkbox" id="groups[]" value="<?= $data['id'] ?>" <?php if ( in_array( $data['id'], $cpath ) )
								print 'checked'; ?>>&nbsp;<?= $data['name'] ?>
						</label>
					</div>
					<?php
				}
				?>
			</div>
		</div>
	</div>
	<div class="column12 grid-9"></div>
</div>

<hr>

<div id="bars" class="div-center margbot10 margtop10"><?= $newClients ?></div>

<table width="99%" border="0" cellpadding="5" cellspacing="0" id="zebra">
	<thead>
	<TR height="40" class="header_contaner">
		<th width="30"></th>
		<th width="350"><B>Сотрудник</B></th>
		<th width="100" align="right"><b>Дата (в базе)</b></th>
		<th width="100" align="right"><b>Дата (в группе)</b></th>
		<th width="100" align="right"><b>Кол-во</b></th>
		<th></th>
	</TR>
	</THEAD>
	<TBODY>
	<?php
	$uList    = '';
	$uCount   = 0;
	$allCount = 0;
	foreach ( $list as $user => $val ) {

		$num     = 1;
		$grList  = '';
		$grCount = 0;
		$uCount  = 0;
		foreach ( $val as $group => $clients ) {


			$nums    = 1;
			$clList  = '';
			$grCount += count( $clients );
			foreach ( $clients as $i => $client ) {

				$clList .= '
				<TR height="35" class="ha client hidden" data-key="'.$user.'-'.$group.'" data-date="'.$client['dcreate'].'">
					<TD width="30" align="right">#'.$nums.'&nbsp;</TD>
					<TD>
						<div class="ellipsis"><a href="#" onclick="openClient(\''.$client['clid'].'\')" title="Карточка"><i class="icon-building broun"></i>&nbsp;'.$client['client'].'</a></div>
					</TD>
					<TD align="right">'.get_date( $client['dcreate'] ).'</TD>
					<TD align="right"><div class="blue em">'.get_date( $client['dgroup'] ).'</div></TD>
					<TD></TD>
					<TD></TD>
				</TR>';

				$nums++;

			}

			$numbers1 = array_map( function($details) {
				return $details['dcreate'];
			}, $clients );
			$numbers2 = array_map( function($details) {
				return $details['dgroup'];
			}, $clients );

			$max1 = min( $numbers1 );
			$max2 = min( $numbers2 );

			$grList .= '
			<TR height="35" class="ha group bluebg-sub hand hidden1" data-user="'.$user.'" data-key="'.$user.'-'.$group.'">
				<TD align="left"></TD>
				<TD><i class="icon-plus-circled blue"></i>&nbsp;<b>'.strtr( $group, $groups ).'</b></TD>
				<TD align="right" title="Мин. дата">'.get_date( $max1 ).'</TD>
				<TD align="right" title="Мин. дата">'.get_date( $max2 ).'</TD>
				<TD align="center" class="Bold blue">'.$grCount.'</TD>
				<TD></TD>
			</TR>
			';

			$grList .= $clList;

			$num++;

			$uCount += $grCount;

			$grCount = 0;

		}

		$allCount += $uCount;

		print '
		<TR height="40" class="ha user greenbg-sub" data-user="'.$user.'">
			<TD colspan="2">
				<DIV title="'.strtr( $user, $users ).'" class="Bold fs-11"><i class="icon-plus-circled green us hidden"></i>&nbsp;'.strtr( $user, $users ).'</DIV>
			</TD>
			<TD></TD>
			<TD></TD>
			<TD align="center"><span class="green Bold fs-11">'.$uCount.'</span></TD>
			<TD></TD>
		</TR>';

		print $grList;

	}
	?>
	</TBODY>
	<TFOOT>
	<TR height="40" class="orangebg-sub" style="background: #ccc">
		<TD align="center">&nbsp;</TD>
		<TD align="left"><B>ИТОГО</B></TD>
		<TD></TD>
		<TD></TD>
		<TD align="center"><B><?= $allCount ?></B></TD>
		<TD></TD>
	</TR>
	</TFOOT>
</TABLE>

<div style="height: 80px;"></div>

<script src="/assets/js/jquery.sparkline.min.js"></script>
<script>

	$(document).ready(function () {

		var range_map = $.range_map({
			'1:5': '#2980B9',
			'6:10': '#16A085',
			'10:20': '#FFC107',
			'20:': '#F44336'
		});

		var days = [<?=$sdays?>];

		$("#bars").sparkline('html', {
			type: 'bar',
			lineColor: '#2980B9',
			width: '95%',
			height: '70px',
			barWidth: <?=$chartWidth?>,
			colorMap: range_map,
			tooltipFormat: 'Добавлено: {{offset:levels}} - {{value}} шт.',
			tooltipValueLookups: {
				levels: {<?=$days?>}
			}
		});

		$('#bars').bind('sparklineClick', function (ev) {

			var sparkline = ev.sparklines[0];
			var region = sparkline.getCurrentRegionFields();
			var day = days[region[0].offset];

			$('.client').addClass('hidden');
			$('.group').addClass('hidden');

			$('.client[data-date="' + day + '"]').each(function () {

				var key = $(this).data('key');

				$(this).removeClass('hidden');
				$('.group[data-key="' + key + '"]').removeClass('hidden');

			});

		});

	});

	/*$('.user').bind('click', function(){

	 var key = $(this).data('user');
	 var status;

	 if($(this).find('i.us').hasClass('icon-plus-circled')) {
	 status = 'opened';

	 }
	 else {
	 status = 'closed';
	 }

	 $('tr.client').addClass('hidden');

	 $('tr.group').not('[data-user="'+key+'"]').addClass('hidden');
	 $('tr.group[data-user="'+key+'"]').toggleClass('hidden');

	 $('tr.user').not('[data-user="'+key+'"]').find('i').addClass('icon-plus-circled').removeClass('icon-minus-circled');

	 $(this).find('i').toggleClass('icon-plus-circled icon-minus-circled');

	 if(status == 'opened') $('tr.group[data-user="'+key+'"]').find('i').addClass('icon-plus-circled').removeClass('icon-minus-circled');

	 });*/
	$('.group').bind('click', function () {

		var key = $(this).data('key');

		$('tr.client').not('[data-key="' + key + '"]').addClass('hidden');
		$('tr.client[data-key="' + key + '"]').toggleClass('hidden');

		$('tr.group').not(this).find('i').addClass('icon-plus-circled').removeClass('icon-minus-circled');

		//$('tr.group[data-key="'+key+'"]').find('i').toggleClass('icon-minus-circled icon-plus-circled');
		$(this).find('i').toggleClass('icon-plus-circled icon-minus-circled');

	});

</script>