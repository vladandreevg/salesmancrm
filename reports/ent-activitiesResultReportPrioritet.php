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

$action    = $_REQUEST[ 'action' ];
$da1       = $_REQUEST[ 'da1' ];
$da2       = $_REQUEST[ 'da2' ];

$roles     = (array)$_REQUEST[ 'roles' ];
$user_list = (array)$_REQUEST[ 'user_list' ];
$tips      = (array)$_REQUEST[ 'tips' ];

$thisfile = basename( $_SERVER[ 'PHP_SELF' ] );

if ( !empty( $tips ) ) {//сохраним параметры, если они есть

	$settings = [
		"user_list" => $user_list,
		"tips"      => $tips,
		"roles"     => $roles
	];
	$file        = $rootpath.'/cash/report_'.$thisfile.'_'.$iduser1.'.txt';

	file_put_contents( $file, json_encode_cyr( $settings ) );

}
else {//в противном случае загружаем сохраненные

	$file = $rootpath.'/cash/report_'.$thisfile.'_'.$iduser1.'.txt';

	if ( file_exists( $file ) ) {

		$rSet  = json_decode( file_get_contents( $file ), true );

		$user_list = (array)$rSet['user_list'];
		$tips      = (array)$rSet['tips'];
		$roles     = (array)$rSet['roles'];
	}

}

$sort  = '';
$sort2 = '';

$color = [
	'#F7C1BB',
	'#F29E95',
	'#ED786B',
	'#E74B3B',
	'#E74B3B'
];

//массив выбранных пользователей
$user_list = (!empty( $user_list )) ? $user_list : (array)get_people( $iduser1, "yes" );
if ( !empty( $user_list ) ) {
	$sort .= " iduser IN (".yimplode( ",", $user_list ).") AND ";
}

//роли сотрудников
if ( !empty( $roless ) ) {
	$sort .= "tip IN (".yimplode( ",", $roles, "'" ).") and ";
}

//выбранные типы активности
if ( !empty( $tips ) ) {
	$s = "id IN (".yimplode( ",", $tips, "'" ).") and  ";
}
else {

	$tips = $db -> getCol( "SELECT id FROM ".$sqlname."activities WHERE id > 0 and identity = '$identity'" );
	$s = "id IN (".yimplode( ",", $tips, "'" ).") and  ";

}

$cpath = $tips;

if ( $action == 'view' ) {

	$user = $_REQUEST[ 'user' ];
	$tip  = $_REQUEST[ 'tip' ];

	if ( !in_array( $tip, [
		'client',
		'person'
	] ) ) {

		$res = $db -> query( "SELECT * FROM ".$sqlname."activities WHERE id = '$tip' and identity = '$identity'" );
		$t   = $db -> fetchnorm( $res, 0, 'title' );

		print '<br><div class="zagolovok_rep">Активности с типом <b class="blue">'.$t.'</b> по сотруднику <b class="blue">'.current_user( $user ).'</b>:</div><hr>';
		print '
		<TABLE width="98.5%" cellpadding="5" cellspacing="0">
		<thead>
		<TR class="header_contaner" height="30">
			<td width="100" align="center"><b>Дата</b></td>
			<td align="center"><b>Содержание</b></td>
			<td width="300" align="center"><b>Клиент / Сделка</b></td>
		</TR>
		</thead>
		';

		$r = $db -> getAll( "SELECT * FROM ".$sqlname."history WHERE cid > 0 and (datum BETWEEN '".$da1." 00:00:01' and '".$da2." 23:59:59') and iduser = '".$user."' and tip = '".$t."' and identity = '$identity' order by datum DESC" );
		foreach ( $r as $da ) {

			$string = '';

			if ( $da[ 'clid' ] > 0 ) {
				$string .= '<div class="ellipsis hand" onclick="viewClient(\''.$da['clid'].'\')" title="Просмотр"><i class="icon-building broun"></i>'.current_client( $da['clid'] ).'</div>';
			}

			if ( $da[ 'pid' ] != '' ) {
				$pids = yexplode( ",", (string)$da[ 'pid' ] );
				foreach ($pids as $pid) {
					$string .= '<br><div class="ellipsis"><i class="icon-user-1 broun"></i>'.current_person( $pid ).'</div>';
				}
			}

			if ( $da[ 'did' ] > 0 ) {
				$string .= '<br><div class="ellipsis hand" onclick="viewDogovor(\''.$da['did'].'\')" title="Просмотр"><i class="icon-briefcase blue"></i>'.current_dogovor( $da['did'] ).'</div>';
			}

			print '
				<tr class="ha" height="30">
					<TD>'.$da[ 'datum' ].'</TD>
					<TD class="hview hand" data-cid="'.$da[ 'cid' ].'" title="Просмотр">'.$da[ 'des' ].'</TD>
					<TD>'.$string.'</TD>
				</tr>';

		}
		if ( count( $r ) == 0 ) {
			print '<tr height="30"><td colspan="3">Данных не обнаружено</td></tr>';
		}

		print '</TABLE>';

	}
	if ( in_array( $tip, [
		'client',
		'person'
	] ) ) {

		if ( $tip == 'client' ) {
			$s  = 'клиенты';
			$s0 = 'Клиент';
			$q  = "SELECT * FROM ".$sqlname."clientcat WHERE clid > 0 and (date_create BETWEEN '".$da1." 00:00:01' and '".$da2." 23:59:59') and creator = '".$user."' and identity = '$identity' order by date_create DESC";
		}
		if ( $tip == 'person' ) {
			$s  = 'контакты';
			$s0 = 'Контакт';
			$q  = "SELECT * FROM ".$sqlname."personcat WHERE pid > 0 and (date_create BETWEEN '".$da1." 00:00:01' and '".$da2." 23:59:59') and creator = '".$user."' and identity = '$identity' order by date_create DESC";
		}

		print '<br><div class="zagolovok_rep">Новые '.$s.', добавленные сотрудником <b class="blue">'.current_user( $user ).'</b>:</div><hr>';
		print '
		<TABLE width="98.5%" cellpadding="5" cellspacing="0">
		<thead>
		<TR class="header_contaner" height="30">
			<td class="yw250" align="center"><b>'.$s0.'</b></td>
			<td class="yw120" align="center"><b>Дата</b></td>
			<td class="yw120" align="center"><b>Автор</b></td>
			<td class="yw120" align="center"><b>Ответственный</b></td>
			<td></td>
		</TR>
		</thead>
		';

		$r = $db -> getAll( $q );
		foreach ( $r as $da ) {

			$string = '';

			if ( $tip == 'client' && $da[ 'clid' ] > 0 ) $string .= '<div class="ellipsis hand" onclick="viewClient(\''.$da[ 'clid' ].'\')" title="Просмотр"><i class="icon-building broun"></i>'.current_client( $da[ 'clid' ] ).'</div>';

			if ( $tip == 'person' && $da[ 'pid' ] > 0 ) $string .= '<div class="ellipsis hand" onclick="viewPerson(\''.$da[ 'pid' ].'\')><i class="icon-user-1 broun"></i>'.current_person( $da[ 'pid' ] ).'</div>';

			print
				'<tr class="ha" height="30">
					<TD>'.$string.'</TD>
					<TD>'.$da[ 'date_create' ].'</TD>
					<TD>'.current_user( $da[ 'creator' ] ).'</TD>
					<TD>'.current_user( $da[ 'iduser' ] ).'</TD>
					<TD></TD>
				</tr>';

		}
		if ( count( $r ) == 0 ) print '<tr height="30"><td colspan="5">Данных не обнаружено</td></tr>';

		print '</TABLE>';

	}

	exit();
}
if ( $action == 'viewcalls' ) {
	$user = $_REQUEST[ 'user' ];
	?>
	<br>
	<div class="zagolovok_rep">Исходящие звонки по сотруднику <b class="blue"><?= current_user( $user ) ?></b>:</div>
	<hr>
	<TABLE width="100%" cellpadding="5" cellspacing="0" border="0" class="list_header" height="30">
		<thead>
		<TR class="header_contaner">
			<TH width="60" align="center"><b>Дата</b></TH>
			<TH width="130" align="center"><b>Источник</b></TH>
			<TH width="130" align="center"><b>Назначение</b></TH>
			<TH width="40" align="center"><b><i class="icon-clock" title="Продолжительность, мин."></i></b></TH>
			<TH width="50" align="center"><b>Файл</b></TH>
			<TH width="120" align="left" class="hidden-netbook">
				<div class="ellipsis"><B>Сотрудник</B></div>
			</TH>
			<TH align="left">
				<div class="ellipsis"><B>Клиент/Персона</B></div>
			</TH>
		</TR>
		</thead>
		<TBODY>
		<?php
		$query  = "
			SELECT
				".$sqlname."callhistory.id as id,
				".$sqlname."callhistory.src as src,
				".$sqlname."callhistory.dst as dst,
				".$sqlname."callhistory.did as did,
				".$sqlname."callhistory.sec as sec,
				".$sqlname."callhistory.iduser as iduser,
				".$sqlname."callhistory.file as file,
				".$sqlname."callhistory.datum as datum,
				".$sqlname."clientcat.clid as clid,
				".$sqlname."clientcat.title as title,
				".$sqlname."personcat.pid as pid,
				".$sqlname."personcat.person as person,
				".$sqlname."user.title as user
			FROM ".$sqlname."callhistory
				LEFT JOIN ".$sqlname."clientcat ON ".$sqlname."clientcat.clid = ".$sqlname."callhistory.clid
				LEFT JOIN ".$sqlname."personcat ON ".$sqlname."personcat.pid = ".$sqlname."callhistory.pid
				LEFT JOIN ".$sqlname."user ON ".$sqlname."callhistory.iduser = ".$sqlname."user.iduser
			WHERE
				".$sqlname."callhistory.id>0 and
				".$sqlname."callhistory.iduser = '$user' and
				".$sqlname."callhistory.direct = 'outcome' and
				".$sqlname."callhistory.res = 'ANSWERED' and
				(".$sqlname."callhistory.datum BETWEEN '".$da1." 00:00:01' and '".$da2." 23:59:59') and
				".$sqlname."callhistory.identity = '$identity'
			ORDER BY ".$sqlname."callhistory.datum DESC";
		$result = $db -> query( $query );

		while ( $data = $db -> fetch( $result ) ) {

			$manpro = $data[ 'user' ];

			if ( $data[ 'clid' ] > 0 ) $client = $data[ 'title' ];
			if ( $data[ 'pid' ] > 0 ) $person = $data[ 'person' ];

			if ( $data[ 'sec' ] > 0 ) {
				$min = (int)($data['sec'] / 60); //число минут
				$sec = $data[ 'sec' ] - $min * 60; //число секунд

				if ( $sec < 10 ) $sec = '0'.$sec;
				if ( strlen( $sec ) > 2 ) $sec = substr( $data[ 'sec' ], 0, -1 );

				$dur = gmdate( "i:s", $data[ 'sec' ] );

				//$dur = $min.':'.$sec;

				if ( $data[ 'file' ] != '' ) $play = '<a href="#" onClick="doLoad(\'api/asterisk/play.php?id='.$data[ 'id' ].'\')" title="Прослушать запись"><i class="icon-volume-up blue"></i></a>';
				else $play = '<i class="icon-volume-up gray" title="Разговор не записан"></i>';
			}
			else {
				$dur  = '-';
				$play = '<i class="icon-volume-up gray" title="Разговор не записан"></i>';
			}
			?>
			<TR height="35" class="ha">
				<TD width="60" align="center">
					<div class="smalltxt"><?= str_replace( ",", "", get_sfdate( $data[ 'datum' ] ) ) ?></div>
				</TD>
				<TD width="130" class="phone"><?= formatPhoneUrl2( $data[ 'src' ] ) ?></TD>
				<TD width="130" class="phone"><?= formatPhoneUrl2( $data[ 'dst' ] ) ?></TD>
				<TD width="40" align="right"><?= $dur ?></TD>
				<TD width="50" align="center"><?= $play ?></TD>
				<TD width="120" class="hidden-netbook">
					<span class="ellipsis" title="<?= $manpro ?>"><?= $manpro ?></span></TD>
				<TD>
					<?php
					if ( $data[ 'clid' ] > 0 ) {
						print '<span class="ellipsis" title="'.$client.'"><A href="#" onClick="openClient(\''.$data[ 'clid' ].'\')"><i class="icon-commerical-building blue"></i></A>|&nbsp;<a onClick="viewClient(\''.$data[ 'clid' ].'\')" href="#">'.$client.'</a></span>';
					}
					if ( $data[ 'pid' ] > 0 ) {
						print '<br><span class="ellipsis" title="'.$person.'"><A href="#" onClick="openPerson(\''.$data[ 'pid' ].'\')"><i class="icon-user-1 green"></i></A>|&nbsp;<A href="#" onClick="viewPerson(\''.$data[ 'pid' ].'\')">'.$person.'</A></span>';
					}
					if ( $data[ 'clid' ] < 1 and $data[ 'pid' ] < 1 ) {
						print '<a href="#" onclick="expressClient(\''.$data[ 'src' ].'\')"><i class="icon-plus-circled red"></i>Добавить</a>';
					}
					?>
				</TD>
			</TR>
			<?php
		}
		?>
		</TBODY>
	</TABLE>
	<?php
	exit();
}

$tipa[ 'client' ] = 'Новых клиентов';
$tipa[ 'person' ] = 'Новых контактов';

//перебираем сотруников
$re = $db -> getAll( "SELECT * FROM ".$sqlname."user WHERE iduser > 0 and $sort identity = '$identity' ORDER BY title" );
foreach ( $re as $da ) {

	$users[ $da[ 'iduser' ] ] = $da[ 'title' ];

	//перебираем типы активности
	$res = $db -> query( "SELECT * FROM ".$sqlname."activities WHERE id > 0 and $s identity = '$identity'" );
	while ( $data = $db -> fetch( $res ) ) {

		$rez[ $da[ 'iduser' ] ][ $data[ 'id' ] ] = $db -> getOne( "SELECT COUNT(*) as count FROM ".$sqlname."history WHERE cid > 0 and (datum BETWEEN '".$da1." 00:00:01' and '".$da2." 23:59:59') and iduser = '".$da[ 'iduser' ]."' and tip = '".$data[ 'title' ]."' and identity = '$identity'" );

		$tipa[ $data[ 'id' ] ] = $data[ 'title' ];

	}

	$rez[ $da[ 'iduser' ] ][ 'client' ] = $db -> getOne( "SELECT COUNT(*) as count FROM ".$sqlname."clientcat WHERE clid > 0 and (date_create BETWEEN '".$da1." 00:00:01' and '".$da2." 23:59:59') and creator = '".$da[ 'iduser' ]."' and identity = '$identity'" );

	$rez[ $da[ 'iduser' ] ][ 'person' ] = $db -> getOne( "SELECT COUNT(*) as count FROM ".$sqlname."personcat WHERE pid > 0 and (date_create BETWEEN '".$da1." 00:00:01' and '".$da2." 23:59:59') and creator = '".$da[ 'iduser' ]."' and identity = '$identity'" );

	if ( $sip_active == 'yes' ) $hist[ $da[ 'iduser' ] ] = $db -> getOne( "SELECT COUNT(*) as count FROM ".$sqlname."callhistory WHERE id > 0 and direct = 'outcome' and res = 'ANSWERED' and (datum BETWEEN '".$da1." 00:00:01' and '".$da2." 23:59:59') and iduser = '".$da[ 'iduser' ]."' and identity = '$identity'" );

}

$max = max( max( array_values( $rez ) ) );
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
	-->
</STYLE>

<br>

<table class="noborder">
	<tr>
		<td class="wp25">
			<div class="ydropDown">
				<span>Только Активности</span><span class="ydropCount"><?= count( $cpath ) ?> выбрано</span><i class="icon-angle-down pull-aright"></i>
				<div class="yselectBox">
					<div class="right-text">
						<div class="ySelectAll w0 inline" title="Выделить всё"><i class="icon-plus-circled"></i>Всё
						</div>
						<div class="yunSelect w0 inline" title="Снять выделение"><i class="icon-minus-circled"></i>Ничего
						</div>
					</div>
					<?php
					$result = $db -> query( "SELECT * FROM ".$sqlname."activities WHERE identity = '$identity' ORDER BY title" );
					while ( $data = $db -> fetch( $result ) ) {
						?>
						<div class="ydropString ellipsis">
							<label>
								<input class="taskss" name="tips[]" type="checkbox" id="tips[]" value="<?= $data[ 'id' ] ?>" <?php if ( in_array( $data[ 'id' ], $cpath ) ) print 'checked'; ?>>
								<div class="bullet-mini" style="background: <?= $data[ 'color' ] ?>"></div>&nbsp;<?= $data[ 'title' ] ?>
							</label>
						</div>
						<?php
					}
					?>
				</div>
			</div>
		</td>
		<td class="wp25">
			<div class="ydropDown">
				<span>Только Роли</span><span class="ydropCount"><?= count( $roles ) ?> выбрано</span><i class="icon-angle-down pull-aright"></i>
				<div class="yselectBox">
					<div class="right-text">
						<div class="ySelectAll w0 inline" title="Выделить всё"><i class="icon-plus-circled"></i>Всё
						</div>
						<div class="yunSelect w0 inline" title="Снять выделение"><i class="icon-minus-circled"></i>Ничего
						</div>
					</div>
					<div class="ydropString ellipsis">
						<label>
							<input class="taskss" name="roles[]" type="checkbox" id="roles[]" value="0" <?php if ( in_array( '', $roles ) ) print 'checked'; ?>>&nbsp;Не указано
						</label>
					</div>
					<div class="ydropString ellipsis">
						<label>
							<input class="taskss" name="roles[]" type="checkbox" id="roles[]" value="Руководитель организации" <?php if ( in_array( "Руководитель организации", $roles ) ) print 'checked'; ?>>&nbsp;Руководитель организации
						</label>
					</div>
					<div class="ydropString ellipsis">
						<label>
							<input class="taskss" name="roles[]" type="checkbox" id="roles[]" value="Руководитель с доступом" <?php if ( in_array( "Руководитель с доступом", $roles ) ) print 'checked'; ?>>&nbsp;Руководитель с доступом
						</label>
					</div>
					<div class="ydropString ellipsis">
						<label>
							<input class="taskss" name="roles[]" type="checkbox" id="roles[]" value="Руководитель подразделения" <?php if ( in_array( "Руководитель подразделения", $roles ) ) print 'checked'; ?>>&nbsp;Руководитель подразделения
						</label>
					</div>
					<div class="ydropString ellipsis">
						<label>
							<input class="taskss" name="roles[]" type="checkbox" id="roles[]" value="Руководитель отдела" <?php if ( in_array( "Руководитель отдела", $roles ) ) print 'checked'; ?>>&nbsp;Руководитель отдела
						</label>
					</div>
					<div class="ydropString ellipsis">
						<label>
							<input class="taskss" name="roles[]" type="checkbox" id="roles[]" value="Менеджер продаж" <?php if ( in_array( "Менеджер продаж", $roles ) ) print 'checked'; ?>>&nbsp;Менеджер продаж
						</label>
					</div>
					<div class="ydropString ellipsis">
						<label>
							<input class="taskss" name="roles[]" type="checkbox" id="roles[]" value="Специалист" <?php if ( in_array( "Специалист", $roles ) ) print 'checked'; ?>>&nbsp;Специалист
						</label>
					</div>
					<div class="ydropString ellipsis">
						<label>
							<input class="taskss" name="roles[]" type="checkbox" id="roles[]" value="Администратор" <?php if ( in_array( "Администратор", $roles ) ) print 'checked'; ?>>&nbsp;Администратор
						</label>
					</div>
				</div>
			</div>
		</td>
		<td class="wp25"></td>
		<td></td>
	</tr>
</table>

<br>

<div class="zagolovok_rep">
	<b>Активности по сотрудникам за период с <?= format_date_rus( $da1 ) ?>&nbsp;по&nbsp;<?= format_date_rus( $da2 ) ?></b>:
</div>

<hr>

<TABLE>
	<thead>
	<TR class="header_contaner">
		<th class="w100 text-center"><b>Сотрудник</b></th>
		<?php
		if ( $sip_active == 'yes' ) {
			?>
			<th class="w100 text-center hist blue"><b>Исх.звонки</b></th>
		<?php } ?>
		<?php
		foreach ( $tipa as $key => $val ) {
			?>
			<th class="w100 text-center"><b><?= $val ?></b></th>
		<?php } ?>
		<th class="w100 text-center itog green"><b>Итог</b></th>
	</TR>
	</thead>
	<?php
	foreach ( $users as $user => $utitle ) {
		?>
		<TR class="ha th35">
			<TD><?= $utitle ?></TD>
			<?php
			if ( $sip_active == 'yes' ) {
				if ( $hist[ $user ] > 0 ) $cls = 'hand hmore Bold';
				else $cls = '';
				?>
				<TD data-user="<?= $user ?>" class="hist text-center <?= $cls ?>"><?= $hist[ $user ] ?></TD>
			<?php } ?>
			<?php
			foreach ( $tipa as $key => $val ) {

				$row = round( $rez[ $user ][ $key ] / $max * 100, 1 );

				$class  = "";
				$tcolor = "#000";

				if ( $row == 0 ) $rcolor = "";
				elseif ( $row < 20 and $row > 0 ) $rcolor = 'more hand color1 color';
				elseif ( $row < 40 and $row > 20 ) $rcolor = 'more hand color2 color';
				elseif ( $row < 60 and $row > 40 ) $rcolor = 'more hand color3 color Bold';
				elseif ( $row < 80 and $row > 60 ) $rcolor = 'more hand color4 color Bold';
				elseif ( $row <= 100 and $row > 80 ) $rcolor = 'more hand color5 color Bold';
				elseif ( $row = 100 ) {
					$rcolor = 'more hand color6 color';
					$class  = "Bold";
				}
				?>
				<TD data-user="<?= $user ?>" data-tip="<?= $key ?>" class="<?= $rcolor ?> text-center trans"><?= $rez[ $user ][ $key ] ?></TD>
			<?php } ?>
			<TD class="Bold itog text-center"><?= array_sum( $rez[ $user ] ) ?></TD>
		</TR>
	<?php } ?>
</TABLE>
<div id="detail" class="paddtop10 paddbott10"></div>

<div class="pad10">
	<div class="infodiv">Отчет учитывает только параметры "Период", "Сотрудник", "Тип активности", "Роль".<br>После первого применения фильтр сохраняется во временном файле для текущего пользователя.
	</div>
</div>

<div style="height:60px"></div>

<script>
	$('.more').click(function () {
		var tip = $(this).data('tip');
		var user = $(this).data('user');
		var str = $('#selectreport').serialize();
		var url = './reports/<?=$thisfile?>?action=view&tip=' + tip + '&user=' + user + '&' + str;

		$('#detail').empty().append('<div id="loader" class="loader"><img src=/assets/images/loading.gif> Вычисление...</div>');

		$.get(url, function (data) {
			$('#detail').html(data);
		})
			.complete(function () {
				$(".nano").nanoScroller({scrollTo: $('#detail')});
			});

	});
	$('.hmore').click(function () {
		var user = $(this).data('user');
		var str = $('#selectreport').serialize();
		var url = './reports/<?=$thisfile?>?action=viewcalls&user=' + user + '&' + str;

		$('#detail').empty().append('<div id="loader" class="loader"><img src=/assets/images/loading.gif> Вычисление...</div>');

		$.get(url, function (data) {
			$('#detail').html(data);
		})
			.complete(function () {
				$(".nano").nanoScroller({scrollTo: $('#detail')});
			});
	});
	$('.hview').live('click', function () {
		var cid = $(this).data('cid');
		viewHistory(cid);
	});
</script>