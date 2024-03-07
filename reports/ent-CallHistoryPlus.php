<?php
/* ============================ */
/*         SalesMan CRM         */
/* ============================ */
/* (C) 2016 Vladislav Andreev   */
/*       SalesMan Project       */
/*        www.isaler.ru         */
/*        ver. 2017.x           */
/* ============================ */
/* Developer: Iskopaeva Liliya  */

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

$thisfile = basename( $_SERVER['PHP_SELF'] );
$page     = $_REQUEST['page'];
$zvonok   = [];
$nalBaz   = (array)$_REQUEST['nalBaz'];
$Visov    = (array)$_REQUEST['Visov'];
$resVisov = (array)$_REQUEST['resVisov'];
$userlist = (array)$_REQUEST['user_list'];
$d1       = $_REQUEST['da1'];
$d2       = $_REQUEST['da2'];

//фильтр по наличию в базе телефона
if ( in_array( "no", $nalBaz ) ) {
	$sort .= $sqlname."callhistory.pid = 0 and ";
}
if ( in_array( "yes", $nalBaz ) ) {
	$sort .= $sqlname."callhistory.pid > 0 and ";
}
//фильтр по направлению вызова
if ( count( $Visov ) > 0 ) {
	$sort .= $sqlname."callhistory.direct IN (".yimplode( ",", $Visov, "'" ).") and ";
}
//фильтр по результатам вызова
if ( count( $resVisov ) > 0 ) {
	if ( in_array( "NOANSWER", $resVisov ) ) {
		$sort .= $sqlname."callhistory.res IN ('NO ANSWER','".yimplode( ",", $resVisov, "'" )."') and ";
	}
	else {
		$sort .= $sqlname."callhistory.res IN (".yimplode( ",", $resVisov, "'" ).") and ";
	}
}
//фильтр выбранных пользователей
if ( count( $userlist ) > 0 ) {
	$sort .= $sqlname."callhistory.iduser IN (".yimplode( ",", $userlist, "'" ).") and ";
}
//фильтр по дате
if ( $d1 != '' ) {
	$day = $sqlname."callhistory.datum BETWEEN '".$d1." 00:00:00' and '".$d2." 23:59:59' and";
}

$di      = [
	'income',
	'outcome'
];
$di2     = [
	'income'  => 'Входящий',
	'outcome' => 'Исходящий'
	/*, 'inner' => 'Внутренний'*/
];
$rezult  = [
	'ANSWERED'   => '<i class="icon-ok-circled green" title="Отвечен"></i>',
	'NOANSWER'   => '<i class="icon-minus-circled red" title="Не отвечен"></i>',
	'NO ANSWER'  => '<i class="icon-minus-circled red" title="Не отвечен"></i>',
	'TRANSFER'   => '<i class="icon-forward-1 gray2" title="Переадресация"></i>',
	'BREAKED'    => '<i class="icon-off red" title="Прервано"></i>',
	'BUSY'       => '<i class="icon-block-1 broun" title="Занято"></i>',
	'CONGESTION' => '<i class="icon-help red" title="Перегрузка канала"></i>',
	'FAILED'     => '<i class="icon-cancel-squared red" title="Ошибка соединения"></i>'
];
$rezult2 = [
	'ANSWERED'   => 'Отвечен',
	'NOANSWER'   => 'Не отвечен',
	'TRANSFER'   => 'Переадресация',
	'BREAKED'    => 'Прервано',
	'BUSY'       => 'Занято',
	'CONGESTION' => 'Перегрузка канала',
	'FAILED'     => 'Ошибка соединения'
];
$colors  = [
	'ANSWERED'  => 'green',
	'NO ANSWER' => 'red',
	'BUSY'      => 'broun'
];
$directt = [
	'inner'   => '<i class="icon-arrows-cw smalltxt broun" title="Внутренний"></i>',
	'income'  => '<i class="icon-down-big smalltxt green" title="Входящий"></i>',
	'outcome' => '<i class="icon-up-big smalltxt blue" title="Исходящий"></i>'
];

//кол-во номеров
$kolNom = $db -> getRow( "SELECT count(DISTINCT phone) as count FROM ".$sqlname."callhistory WHERE direct in (".yimplode( ",", $di, "'" ).") and $day $sort identity ='$identity'" );


//постраничная реализация
$lines_per_page = 100;
if ( !isset( $page ) || empty( $page ) || $page <= 0 ) {
	$page = 1;
}
else {
	$page = (int)$page;
}
$page_for_query = $page - 1;
$lpos           = $page_for_query * $lines_per_page;
$count_pages    = ceil( $kolNom['count'] / $lines_per_page );
if ( $count_pages < 1 ) {
	$count_pages = 1;
}

//телефоны без повторов
$da = $db -> getAll( "SELECT phone FROM ".$sqlname."callhistory WHERE $day $sort identity ='".$identity."' GROUP BY phone LIMIT $lpos,$lines_per_page" );

//формирование данных
foreach ( $da as $data ) {

	//подгруппа по номеру
	$query = "
	SELECT
		".$sqlname."callhistory.id as id,
		".$sqlname."callhistory.datum as datum,
		".$sqlname."callhistory.src as src,
		".$sqlname."callhistory.dst as dst,
		".$sqlname."callhistory.did as did,
		".$sqlname."callhistory.direct as direct,
		".$sqlname."callhistory.res as res,
		".$sqlname."callhistory.sec as sec,
		".$sqlname."callhistory.file as file,
		".$sqlname."callhistory.uid as uid,
		".$sqlname."callhistory.iduser as iduser,
		".$sqlname."callhistory.clid as clid,
		".$sqlname."callhistory.pid as pid,
		".$sqlname."callhistory.phone as phone,
		".$sqlname."clientcat.title as client,
		".$sqlname."personcat.person as person,
		".$sqlname."user.title as user
	FROM ".$sqlname."callhistory
		LEFT JOIN ".$sqlname."user ON ".$sqlname."callhistory.iduser = ".$sqlname."user.iduser
		LEFT JOIN ".$sqlname."personcat ON ".$sqlname."callhistory.pid = ".$sqlname."personcat.pid
		LEFT JOIN ".$sqlname."clientcat ON ".$sqlname."callhistory.clid = ".$sqlname."clientcat.clid
	WHERE
		".$sqlname."callhistory.direct in (".yimplode( ",", $di, "'" ).") and
		".$sqlname."callhistory.phone='".$data['phone']."' and 
		$day
		$sort
		".$sqlname."callhistory.identity = '$identity' 
		ORDER BY ".$sqlname."callhistory.datum DESC
	";

	$result = $db -> query( $query );
	while ($da = $db -> fetch( $result )) {

		if ( $da['sec'] > 0 ) {
			$min = (int)($da['sec'] / 60); //число минут
			$sec = $da['sec'] - $min * 60; //число секунд

			if ( $sec < 10 )
				$sec = '0'.$sec;
			if ( strlen( $sec ) > 2 )
				$sec = substr( $da['sec'], 0, -1 );

			$dur = gmdate( "i:s", $da['sec'] );

			if ( $da['file'] != '' )
				$play = '<a href="javascript:void(0)" onClick="doLoad(\'content/pbx/play.php?id='.$da['id'].'\')" title="Прослушать запись"><i class="icon-volume-up blue"></i></a>';
			else $play = '<i class="icon-volume-up gray" title="Разговор не записан"></i>';
		}
		else {
			$dur  = '-';
			$play = '<i class="icon-volume-up gray" title="Разговор не записан"></i>';
		}

		if ( $da['direct'] == 'income' )
			$phone = $da['src'];
		if ( $da['direct'] == 'outcome' )
			$phone = $da['dst'];

		$clientpath = current_clientpathbyid( getClientpath( '', '', $da['did'] ) );

		if ( $da['did'] != '' )
			$did = ($clientpath != '') ? $clientpath : $da['did'];
		else $did = '<span class="gray">Линия не определена</span>';

		if ( $da['clid'] < 1 && $da['pid'] < 1 )
			$add = '1';
		else $add = '';

		$zvonok[ $data['phone'] ][ $da['id'] ] = [
			'datum'  => str_replace( ",", "", get_sfdate( $da['datum'] ) ),
			'direct' => strtr( $da['direct'], $directt ),
			'color'  => strtr( $da['res'], $colors ),
			'rezult' => strtr( $da['res'], $rezult ),
			'src'    => formatPhoneUrl2( $da['src'], $da['clid'], $da['pid'] ),
			'dst'    => formatPhoneUrl2( $da['dst'], $da['clid'], $da['pid'] ),
			'phone'  => $phone,
			'dur'    => $dur,
			'play'   => $play,
			'did'    => $did,
			'clid'   => $da['clid'],
			'client' => $da['client'],
			'pid'    => $da['pid'],
			'person' => $da['person'],
			'user'   => $da['user'],
			'add'    => $add
		];
	}
}

?>
<div class="zagolovok_rep" align="center">
	<h2>История звонков +</h2>
</div>
<div class="noprint">

	<div class="black Bold"><h3>Всего номеров: <?= $kolNom['count']; ?></h3></div>
	<div class="pad5 mt20 gray2 Bold">Фильтры:</div>
	<table class="noborder">
		<tr>
			<td width="23%">
				<div class="ydropDown">
					<span>Наличие телефона в базе</span><span class="ydropCount"><?= count( $nalBaz ) ?> выбрано</span><i class="icon-angle-down pull-aright"></i>
					<div class="yselectBox" style="max-height: 300px;">
						<div class="yunSelect"><i class="icon-cancel-circled2"></i>Снять выделение</div>
						<div class="ydropString ellipsis">
							<label>
								<input class="taskss" name="nalBaz[]" type="checkbox" id="nalBaz[]" value="yes" <?php if ( in_array( "yes", $nalBaz ) )
									print 'checked'; ?>>&nbsp;Есть в базе
							</label>
						</div>
						<div class="ydropString ellipsis">
							<label>
								<input class="taskss" name="nalBaz[]" type="checkbox" id="nalBaz[]" value="no" <?php if ( in_array( "no", $nalBaz ) )
									print 'checked'; ?>>&nbsp;Нет в базе
							</label>
						</div>
					</div>
				</div>
			</td>
			<td width="21%">
				<div class="ydropDown">
					<span>Направление вызова</span><span class="ydropCount"><? if ( !isset( $Visov ) )
							print "2";
						else print count( $Visov ) ?> выбрано</span><i class="icon-angle-down pull-aright"></i>
					<div class="yselectBox" style="max-height: 300px;">
						<div class="yunSelect"><i class="icon-cancel-circled2"></i>Снять выделение</div>
						<?php
						foreach ( $di2 as $key => $val ) {


							?>
							<div class="ydropString ellipsis">
								<label>
									<input class="taskss" name="Visov[]" type="checkbox" id="Visov[]" value="<?= $key; ?>" <?php if ( in_array( $key, $Visov ) )
										print 'checked';
									if ( !isset( $Visov ) )
										print 'checked'; ?>>&nbsp;<?= $val ?>
								</label>
							</div>
						<?php } ?>
					</div>
				</div>
			</td>
			<td width="19%">
				<div class="ydropDown">
					<span>Результат вызова</span><span class="ydropCount"><?= count( $resVisov ) ?> выбрано</span><i class="icon-angle-down pull-aright"></i>
					<div class="yselectBox" style="max-height: 300px;">
						<div class="yunSelect"><i class="icon-cancel-circled2"></i>Снять выделение</div>
						<?php
						foreach ( $rezult2 as $key => $val ) {
							?>
							<div class="ydropString ellipsis">
								<label>
									<input class="taskss" name="resVisov[]" type="checkbox" id="resVisov[]" value="<?= $key; ?>" <?php if ( in_array( $key, $resVisov ) )
										print 'checked'; ?>>&nbsp;<?= $val ?>
								</label>
							</div>
						<?php } ?>
					</div>
				</div>
			</td>
			<td width="37%"></td>
		</tr>
	</table>
</div>
<hr>
<table class="list_header">
	<thead>
	<tr class="header_contaner">
		<TH align="center" class="yw80"><b>Дата</b></TH>
		<TH align="center" class="yw40"><b></b></TH>
		<TH align="center" class="yw40"></TH>
		<TH align="center" class="hidden yw120"><b>Наш номер</b></TH>
		<TH align="center" class="yw160"><b>Источник</b></TH>
		<TH align="center" class="yw160"><b>Назначение</b></TH>
		<TH align="center" class="yw60"><b><i class="icon-clock" title="Продолжительность, мин."></i></b></TH>
		<TH align="center" class="yw60"><b>Файл</b></TH>
		<TH align="left" class="hidden-netbook yw100">
			<div class="ellipsis"><B>Сотрудник</B></div>
		</TH>
		<TH align="left" class="yw250">
			<div class="ellipsis"><B>Клиент/Персона</B></div>
		</TH>
	</tr>
	</thead>
	<tbody>
	<?
	foreach ( $zvonok as $key => $val ) {
		if ( $key != '' ) {
			?>
			<tr class="bluebg-sub">
				<td colspan="10">
					<div class="Bold p10 fs-12"><i class="icon-phone blue"></i> <?= $key ?></div>
				</td>
			</tr>
			<? foreach ( $val as $key2 => $val2 ) { ?>
				<tr class="ha" height="40">
					<td align="center">
						<div class="smalltxt"><?= $val2['datum'] ?></div>
					</td>
					<td align="center"><?= $val2['direct'] ?></td>
					<td align="center"><b class="<?= $val2['color'] ?>"><?= $val2['rezult'] ?></b></td>
					<td class="hidden"><?= $val2['did'] ?></td>
					<td>
						<div class="ellipsis"><?= $val2['src'] ?></div>
						<br>
						<div title="Номер линии" class="smalltxt ellipsis"><?= $val2['did'] ?></div>
					</td>
					<td>
						<div class="ellipsis"><?= $val2['dst'] ?></div>
					</td>
					<td align="right"><?= $val2['dur'] ?></td>
					<td align="center"><?= $val2['play'] ?></td>
					<td><SPAN class="ellipsis" title="<?= $val2['user'] ?>"><?= $val2['user'] ?></SPAN></td>
					<td>
						<?php
						if ( $val2['client'] != '' ) {
							print '<span class="ellipsis" title='.$val2['client'].'"><a href="javascript:void(0)" onClick="openClient(\''.$val2['clid'].'\')"><i class="icon-building blue"></i>'.$val2['client'].'</a></span>';
						}
						if ( $val2['person'] != '' ) {
							print '<br><span class="ellipsis paddtop5" title="<'.$val2['person'].'"><a href="javascript:void(0)" onClick="openPerson(\''.$val2['pid'].'\')"><i class="icon-user-1 green"></i>'.$val2['person'].'</a></span>';
						}
						if ( $val2['add'] == 1 ) {
							print '<a href="javascript:void(0)" onclick="expressClient(\''.$val2['phone'].'\')" class="fbutton" title="'.$val2['phone'].'"><i class="icon-plus-circled"><i class="icon-paper-plane sup"></i></i>&nbsp;&nbsp;Экспресс</a>&nbsp;<a href="javascript:void(0)" onclick="editEntry(\'\',\'edit\',\''.$val2['phone'].'\')" class="fbutton red" title="'.$val2['phone'].'"><i class="icon-phone-squared"><i class="icon-plus-circled sup"></i></i>&nbsp;&nbsp;Обращение</a>';
						}
						?>
					</td>
				</tr>
				<?php
			}
		}
	}
	?>
	</tbody>
</TABLE>
<div class="pagecontainer">
	<div class="page pbottom mainbg" id="pagediv">
		<?php
		$prev = $page - 1;
		$next = $page + 1;
		if ( ($page == 1) and ($count_pages != 1) ) {
			print 'Стр. '.$page.' из '.$count_pages.'&nbsp;<a href="javascript:void(0)" onClick="change_page(\''.$next.'\')" title="Следующая"><i class="icon-angle-right"></i></a>&nbsp;&nbsp;<a href="javascript:void(0)" onClick="change_page(\''.$count_pages.'\')" title="Последняя"><i class="icon-angle-double-right"></i></a>&nbsp';
		}
		else {
			if ( $count_pages == 1 ) {
				print 'Стр. '.$page.' из '.$count_pages;
			}
			else {
				if ( $page == $count_pages ) {
					print 'Стр. '.$page.' из '.$count_pages.'&nbsp;<a href="javascript:void(0)" onClick="change_page(\'1\')" alt="Начало" title="Начало"><i class="icon-angle-double-left"></i></a>&nbsp;&nbsp;<a href="javascript:void(0)" onClick="change_page(\''.$prev.'\')" title="Предыдущая"><i class="icon-angle-left"></i></a>&nbsp';
				}
				else {
					print '&nbsp;<a href="javascript:void(0)" onClick="change_page(\'1\')" title="Начало"><i class="icon-angle-double-left"></i></a>&nbsp;&nbsp;<a href="javascript:void(0)" onClick="change_page(\''.$prev.'\')" title="Предыдущая"><i class="icon-angle-left"></i></a>&nbsp;Стр. '.$page.' из '.$count_pages.'&nbsp;<a href="javascript:void(0)" onClick="change_page(\''.$next.'\')" title="Следующая"><i class="icon-angle-right"></i></a>&nbsp;&nbsp;<a href="javascript:void(0)" onClick="change_page(\''.$count_pages.'\')" title="Последняя"><i class="icon-angle-double-right"></i></a>&nbsp';
				}
			}
		}
		?>
	</div>
</div>
<div style="height:45px;"></div>
<script>
	function change_page(page) {
		var str = $('#selectreport').serialize();
		var url = 'reports/<?=$thisfile?>';
		str = str + '&page=' + page;
		$.get(url, str, function (data) {
			$('#contentdiv').html(data);
		});
	}
</script>