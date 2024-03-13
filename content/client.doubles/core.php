<?php
/* ============================ */
/*         SalesMan CRM         */
/* ============================ */
/* (C) 2016 Vladislav Andreev   */
/*       SalesMan Project       */
/*        www.isaler.ru         */
/*        ver. 2017.6           */
/* ============================ */

use Salesman\Client;
use Salesman\Elements;
use Salesman\Person;

set_time_limit( 0 );

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

//настройки поиска дублей
$doublesFile = $rootpath."/cash/".$fpath."settings.checkdoubles.json";
if ( file_exists( $doublesFile ) ) {

	$dblSettings = json_decode( file_get_contents( $doublesFile ), true );

}
else {

	$dbl         = $db -> getOne( "SELECT params FROM {$sqlname}customsettings WHERE tip = 'doubles' AND identity = '$identity'" );
	$dblSettings = json_decode( (string)$dbl, true );

	$file = fopen( $doublesFile, "w" );
	fwrite( $file, $dbl );
	fclose( $file );

}

$action = $_REQUEST['action'];

$status = [
	"yes" => "Обработан",
	"no"  => "Не обработан",
	"ign" => "Игнорирован"
];

$tips = [
	"phone" => "телефону",
	"email" => "email",
	"recv"  => "реквизитам",
	"title" => "имени"
];

$tipa = [
	"phone" => "icon-phone",
	"email" => "icon-mail-alt",
	"recv"  => "icon-doc",
	"title" => "icon-user"
];

$keys = [
	"inn" => "ИНН",
	"kpp" => "КПП",
	"phone" => "Тел.",
	"email" => "Email"
];

//вывод списка дублей
if ( $action == 'list' ) {

	$page           = $_REQUEST['page'];
	$filter         = $_REQUEST['filterDouble'];
	$lines_per_page = 100;

	$clients = [];

	$sort = (!empty( $filter )) ? $sqlname."doubles.tip = '$filter' and" : "";

	$q1 = "
	SELECT
		COUNT(*)
	FROM {$sqlname}doubles
	WHERE 
		-- {$sqlname}doubles.tip = 'client' and 
		$sort
		{$sqlname}doubles.identity = '$identity' 
	";

	$all_lines = $db -> getOne( $q1 );

	$q = "
	SELECT
		{$sqlname}doubles.id as id,
		{$sqlname}doubles.datum as datum,
		IF({$sqlname}doubles.tip = 'client', {$sqlname}doubles.idmain, 0) as clid,
		IF({$sqlname}doubles.tip = 'person', {$sqlname}doubles.idmain, 0) as pid,
		{$sqlname}doubles.tip as tip,
		{$sqlname}doubles.status as status,
		{$sqlname}doubles.list as list
	FROM {$sqlname}doubles
	WHERE 
		-- {$sqlname}doubles.tip = 'client' and 
		$sort
		{$sqlname}doubles.identity = '$identity' 
	";

	if ( $page > ceil( $all_lines / $lines_per_page ) ) {
		$page = 1;
	}

	if ( empty( $page ) || $page <= 0 ) {
		$page = 1;
	}
	else {
		$page = (int)$page;
	}
	$page_for_query = $page - 1;
	$lpos           = $page_for_query * $lines_per_page;

	$count_pages = ceil( $all_lines / $lines_per_page );

	if ( $count_pages < 1 ) {
		$count_pages = 1;
	}

	$limit = "LIMIT $lpos,$lines_per_page";

	$q      .= " ORDER BY FIELD({$sqlname}doubles.status, 'no', 'yes', 'ign'), {$sqlname}doubles.datum DESC $limit";
	$result = $db -> query( $q );
	while ($da = $db -> fetch( $result )) {

		$l = (array)json_decode( (string)$da['list'], true );

		$doubles = [];
		foreach ( $l['clid'] as $id ) {

			$doubles[] = [
				"id"     => (int)$id,
				"client" => current_client( (int)$id )
			];

		}
		foreach ( $l['pid'] as $id ) {

			$doubles[] = [
				"id"     => (int)$id,
				"person" => current_person( (int)$id )
			];

		}

		$s = [];

		if ( !empty( $l['title'] ) ) {
			$s[] = '<i class="icon-user broun"></i>';
		}
		if ( !empty( $l['phone'] ) ) {
			$s[] = '<i class="icon-phone blue"></i>';
		}
		if ( !empty( $l['email'] ) ) {
			$s[] = '<i class="icon-mail-alt green"></i>';
		}
		if ( !empty( $l['recv'] ) ) {
			$s[] = '<i class="icon-doc-inv red"></i>';
		}

		$color = ($da['status'] == 'yes') ? 'greenbg-sub' : '';

		if ( ($da['status'] == 'ign') ) {
			$color = 'graybg-sub';
		}

		if ( $da['clid'] > 0 ) {

			$user = current_user( getClientData( $da['clid'], 'iduser' ) );

		}
		elseif ( $da['pid'] > 0 ) {

			$user = current_user( getPersonData( $da['pid'], 'iduser' ) );

		}

		$clients[] = [
			"id"      => (int)$da['id'],
			"datum"   => str_replace( "  ", "<br>", get_sfdate( $da['datum'] ) ),
			"clid"    => ($da['clid'] > 0) ? $da['clid'] : '',
			"client"  => ($da['clid'] > 0) ? current_client( $da['clid'] ) : '',
			"pid"     => ($da['pid'] > 0) ? $da['pid'] : '',
			"person"  => ($da['pid'] > 0) ? current_person( $da['pid'] ) : '',
			"tip"     => ($da['tip'] == 'client') ? 'Клиент' : 'Контакт',
			"icon"    => ($da['tip'] == 'client') ? 'icon-building broun' : 'icon-user-1 blue',
			"color"   => $color,
			"status"  => ($da['status']) ? strtr( $da['status'], $status ) : "Не обработан",
			"user"    => $user,
			"why"     => yimplode( " ", (array)$s ),
			"doubles" => $doubles
		];

	}

	$data = [
		"client"  => $clients,
		"page"    => $page,
		"pageall" => $count_pages,
		"count"   => $all_lines
	];

	$list = json_encode_cyr( $data );

	print $list;

	exit();

}

//просмотр дублей
if ( $action == 'view' ) {

	$id = $_REQUEST['id'];

	$clients = [];

	$q = "
	SELECT
		{$sqlname}doubles.id as id,
		{$sqlname}doubles.datum as datum,
		{$sqlname}doubles.datumdo as datumdo,
		IF({$sqlname}doubles.tip = 'client', {$sqlname}doubles.idmain, 0) as clid,
		IF({$sqlname}doubles.tip = 'person', {$sqlname}doubles.idmain, 0) as pid,
		{$sqlname}doubles.tip as tip,
		{$sqlname}doubles.status as status,
		{$sqlname}doubles.list as list,
		{$sqlname}doubles.des,
		{$sqlname}doubles.iduser
	FROM {$sqlname}doubles
	WHERE 
		{$sqlname}doubles.id = '$id' and 
		{$sqlname}doubles.identity = '$identity' 
	";

	$da = $db -> getRow( $q );

	$l = json_decode( $da['list'], true );

	$doubles = [];
	foreach ( $l['clid'] as $ida ) {

		$doubles[] = [
			"id"     => $ida,
			"client" => current_client( $ida )
		];

	}
	foreach ( $l['pid'] as $ida ) {

		$doubles[] = [
			"id"     => $ida,
			"person" => current_person( $ida )
		];

	}

	if ( $da['clid'] > 0 ) {

		$user = current_user( getClientData( $da['clid'], 'iduser' ) );

	}
	elseif ( $da['pid'] > 0 ) {

		$user = current_user( getPersonData( $da['pid'], 'iduser' ) );

	}

	$client = [
		"id"      => $da['id'],
		"datum"   => get_sfdate( $da['datum'] ),
		"datumdo" => get_sfdate( $da['datumdo'] ),
		"clid"    => ($da['clid'] > 0) ? $da['clid'] : '',
		"client"  => ($da['clid'] > 0) ? current_client( $da['clid'] ) : '',
		"pid"     => ($da['pid'] > 0) ? $da['pid'] : '',
		"person"  => ($da['pid'] > 0) ? current_person( $da['pid'] ) : '',
		"tip"     => ($da['tip'] == 'client') ? 'Клиент' : 'Контакт',
		"icon"    => ($da['tip'] == 'client') ? 'icon-building broun' : 'icon-user-1 blue',
		"color"   => ($da['status'] == 'yes') ? 'green' : 'red',
		"status"  => ($da['status']) ? strtr( $da['status'], $status ) : "Не обработан",
		"user"    => $user,
		"doubles" => $doubles,
		"des"     => $da['des'],
		"iduser"  => current_user( $da['iduser'] )
	];

	unset( $l['clid'], $l['pid'], $l['id'] );

	//print_r($dblSettings);

	?>
	<DIV class="zagolovok"><B>Найденный дубль</B></DIV>
	<div id="formtabse" style="overflow-y: auto; max-height: 90vh; overflow-x: hidden">

		<div class="row">

			<div class="column12 grid-3 right-text gray2">Дата проверки:</div>
			<div class="column12 grid-9"><?= $client['datum'] ?></div>

		</div>

		<div class="row">

			<div class="column12 grid-3 right-text gray2">Статус:</div>
			<div class="column12 grid-9 <?= $client['color'] ?> Bold"><?= $client['status'] ?></div>

		</div>

		<div class="row">

			<div class="column12 grid-3 right-text gray2">Проверяемый:</div>
			<div class="column12 grid-9">
				<?php if ( (int)$client['clid'] > 0 ) { ?>
					[CLID <?= $client['clid'] ?>]
					<a href="javascript:void(0)" onclick="openClient('<?= $client['clid'] ?>')" title="Открыть карточку"><i class="<?= $client['icon'] ?>"></i><?= $client['client'] ?>
					</a>
				<?php } ?>
				<?php if ( (int)$client['pid'] > 0 ) { ?>
					[PID <?= $client['pid'] ?>]
					<a href="javascript:void(0)" onclick="openPerson('<?= $client['pid'] ?>')" title="Открыть карточку"><i class="<?= $client['icon'] ?>"></i><?= $client['person'] ?>
					</a>
					<?php
					$cl = (int)getPersonData( $client['pid'], 'clid' );
					if ( $cl > 0 ) {

						print '&nbsp;[<a href="javascript:void(0)" onclick="openClient(\''.$cl.'\')" title="Открыть карточку"><i class="icon-building"></i>'.current_client( $cl ).'</a>]';

					}
					?>
				<?php } ?>
			</div>

		</div>

		<div class="row">

			<div class="column12 grid-3 right-text gray2">Ответственный:</div>
			<div class="column12 grid-9">
				<i class="icon-user-1 blue"></i><?= $client['user'] ?>
			</div>

		</div>

		<?php if ( $da['status'] != 'no' ) { ?>
			<div class="row mb10">

				<div class="column12 grid-12">
					<div id="divider" class="red" align="center">
						<b class="blue">Обработка</b>
					</div>
				</div>

			</div>

			<div class="row">

				<div class="column12 grid-3 right-text gray2">Дата обработки:</div>
				<div class="column12 grid-9"><i class="icon-clock green"></i><?= $client['datumdo'] ?></div>

			</div>

			<div class="row">

				<div class="column12 grid-3 right-text gray2">Выполнил:</div>
				<div class="column12 grid-9"><i class="icon-user-1 blue"></i><?= $client['iduser'] ?></div>

			</div>

			<div class="row mb10">

				<div class="column12 grid-12">
					<div id="divider" class="red" align="center">
						<b class="blue">Коментарий</b>
					</div>
				</div>

			</div>

			<div class="viewdiv">
				<?= nl2br( $da['des'] ) ?>
			</div>

		<?php } ?>

		<?php if ( $da['status'] != 'yes' ) { ?>

			<div class="row mb10">

				<div class="column12 grid-12">
					<div id="divider" class="red" align="center">
						<b class="blue">Совпадения по</b>
					</div>
				</div>

			</div>

			<?php
			foreach ( $l as $item => $value ) {

				$s = '';

				foreach ( $value as $ida => $val ) {

					//$v = (is_array( $val )) ? implode( ", ", (array)$val ) : $val;

					if(is_array( $val )){

						$xv = [];
						foreach ($val as $t => $x){
							$xv[] = $keys[$t].": ".$x;
						}

						$v = yimplode("<br>", $xv);

					}
					else{
						$v = $val;
					}

					if ( $da['tip'] == 'client' ) {
						$s .= '
							<div class="flex-container box--child">
								<div class="flex-string wp20">'.$v.'</div>
								<div class="flex-string wp5"> => </div>
								<div class="flex-string wp65">[CLID '.$ida.'] <a href="javascript:void(0)" onclick="openClient(\''.$ida.'\')" title="Открыть карточку"><i class="'.$client['icon'].'"></i>'.current_client( $ida ).'</a></div>
							</div>
						';
					}
					elseif ( $da['tip'] == 'person' ) {

						$cl = (int)getPersonData( $ida, 'clid' );
						if ( $cl > 0 ) {

							$d = '&nbsp;[<a href="javascript:void(0)" onclick="openClient(\''.$cl.'\')" title="Открыть карточку"><i class="icon-building"></i>'.current_client( $cl ).'</a>]';

						}

						$s .= '
							<div class="flex-container box--child">
								<div class="flex-string wp20">'.$v.'</div>
								<div class="flex-string wp5"> => </div>
								<div class="flex-string wp65">[PID '.$ida.'] <a href="javascript:void(0)" onclick="openPerson(\''.$ida.'\')" title="Открыть карточку"><i class="'.$client['icon'].'"></i>'.current_person( $ida ).'</a>'.$d.'</div>
							</div>
						';
					}

				}

				print '
				<div class="row">
	
					<div class="column12 grid-1 right-text gray2" title="'.strtr( $item, $tips ).'"><i class="'.strtr( $item, $tipa ).'"></i></div>
					<div class="column12 grid-11">
						'.$s.'
					</div>
		
				</div>
				';

			}
			?>

		<?php } ?>

	</div>

	<hr class="mt20">

	<div class="button--pane text-right">
		<?php if ( $da['status'] == 'no' && in_array( $iduser1, (array)$dblSettings['Coordinator'] ) ) { ?>
			<A href="javascript:void(0)" onclick="doubleModule.merge('<?= $id ?>')" class="button greenbtn"><i class="icon-docs"></i>Слить дубли</A>&nbsp;
			<A href="javascript:void(0)" onclick="doubleModule.ignore('<?= $id ?>')" class="button redbtn"><i class="icon-block"></i>Игнорировать</A>&nbsp;
		<?php } ?>
		<A href="javascript:void(0)" onclick="DClose()" class="button">Закрыть</A>

	</div>
	<script>

		$(function () {

			$('#dialog').css('width', '703px');
			$('#dialog').center();

		});

	</script>
	<?php

	exit();

}

//форма слияния дублей
if ( $action == 'merge' ) {

	$id = (int)$_REQUEST['id'];

	$q = "
	SELECT
		{$sqlname}doubles.id as id,
		{$sqlname}doubles.datum as datum,
		{$sqlname}doubles.idmain,
		IF({$sqlname}doubles.tip = 'client', {$sqlname}doubles.idmain, 0) as clid,
		IF({$sqlname}doubles.tip = 'person', {$sqlname}doubles.idmain, 0) as pid,
		{$sqlname}doubles.tip as tip,
		{$sqlname}doubles.status as status,
		{$sqlname}doubles.list as list
	FROM {$sqlname}doubles
	WHERE 
		{$sqlname}doubles.id = '$id'
	";

	$da = $db -> getRow( $q );

	//print_r($da);

	$l = json_decode( (string)$da['list'], true );

	//print_r($l);

	$a['phone'] = $l['phone'];
	$a['email'] = $l['email'];

	$clids = (array)$l['clid'];
	$pids  = (array)$l['pid'];

	if ( (int)$da['clid'] > 0 ) {

		$clids[] = (int)$da['clid'];
		$min     = arrayMin( $clids );

	}
	if ( (int)$da['pid'] > 0 ) {

		$pids[] = (int)$da['pid'];
		$min    = arrayMin( $pids );

	}


	?>
	<DIV class="zagolovok"><B>Слияние дублей</B></DIV>
	<FORM action="/content/client.doubles/core.php" method="post" enctype="multipart/form-data" name="doubleForm" id="doubleForm" autocomplete="off">
		<INPUT type="hidden" id="action" name="action" value="merge.on">
		<INPUT type="hidden" id="id" name="id" value="<?= $id ?>">
		<INPUT type="hidden" id="tip" name="tip" value="<?= $da['tip'] ?>">
		<INPUT type="hidden" id="ignored" name="ignored" value="">

		<div id="formtabse" style="overflow-y: auto; max-height: 80vh; overflow-x: hidden">

			<div class="row mb10">

				<div class="column12 grid-12">
					<div id="divider" class="red" align="center">
						<b class="blue">Выбор основной записи</b>
					</div>
				</div>

			</div>

			<div class="row">

				<?php
				foreach ( $clids as $clid ) {

					$s = ($min -> min == $clid) ? 'checked' : '';
					$d = ($min -> min == $clid) ? '[<b class="green">Добавлен&nbsp;первым</b>]' : '[<b class="gray2">Добавлен&nbsp;позже</b>]';
					if ( $da['clid'] == $clid ) {
						$d .= '[<b class="red">Проверяемый</b>]';
					}

					?>
					<div class="column12 grid-12 infodiv mb10 p10" data-id="<?= $clid ?>">

						<INPUT type="hidden" id="doid[]" name="doid[]" value="<?= $clid ?>">

						<div class="pull-aright">

							<a href="javascript:void(0)" onclick="openClient('<?= $clid ?>')" title="Открыть карточку"><i class="icon-attach-1"></i></a>

							<?php if ( (int)$da['clid'] != $clid ) { ?>
								<div class="hand doubleDelete mt10" title="Исключить из слияния">
									<i class="icon-cancel-circled red"></i></div>
							<?php } ?>

						</div>

						<div class="pr15 ml5 wp90 mb5">
							<div class="radio">
								<label>
									<input name="main" type="radio" id="main" <?= $s ?> value="<?= $clid ?>"/>
									<span class="custom-radio success1"><i class="icon-radio-check"></i></span>
									<span class="title fs-10 Bold"><?= current_client( $clid ) ?><br><div class="pl20 fs-07"><?= $d ?></div></span>
								</label>
							</div>
						</div>

						<div class="flex-container box--child">

							<div class="flex-string wp20 gray2 right-text">CLID:</div>
							<div class="flex-string wp80 pl10"><?= $clid ?></div>

						</div>

						<div class="flex-container box--child">

							<div class="flex-string wp20 gray2 right-text">Дата создания:</div>
							<div class="flex-string wp80 pl10"><?= get_sfdate( getClientData( $clid, 'date_create' ) ) ?></div>

						</div>

						<div class="flex-container box--child">

							<div class="flex-string wp20 gray2 right-text">Ответственный:</div>
							<div class="flex-string wp80 pl10"><?= current_user( getClientData( $clid, 'iduser' ) ) ?></div>

						</div>

						<?php if ( (int)$da['idmain'] != $clid ) { ?>

							<div class="flex-container box--child">

								<div class="flex-string wp20 gray2 right-text">Похожие данные:</div>
								<div class="flex-string wp80 pl10">
									<?php
									$h = [];
									foreach ( $a as $it => $va ) {

										foreach ( $va as $i => $v ) {

											if ( $i == $clid ) {

												$h[] = (is_array( $v )) ? '<i class="'.strtr( $it, $tipa ).'"></i>'.implode( ",", $v ) : '<i class="'.strtr( $it, $tipa ).'"></i>'.$v;

											}

										}

									}

									print implode( ";", $h );
									?>
								</div>

							</div>
						<?php } ?>

					</div>
				<?php }
				foreach ( $pids as $pid ) {

					$s = ($min -> min == $pid) ? 'checked' : '';
					$d = ($min -> min == $pid) ? '[<b class="green">Добавлен&nbsp;первым</b>]' : '[<b class="gray2">Добавлен&nbsp;позже</b>]';
					
					if ( (int)$da['pid'] == $pid ) {
						$d .= '[<b class="red">Проверяемый</b>]';
					}

					?>
					<div class="column12 grid-12 infodiv mb10 p10" data-id="<?= $pid ?>">

						<INPUT type="hidden" id="doid[]" name="doid[]" value="<?= $pid ?>">

						<div class="pull-aright">

							<a href="javascript:void(0)" onclick="openPerson('<?= $pid ?>')" title="Открыть карточку"><i class="icon-attach-1"></i></a>

							<?php if ( (int)$da['pid'] != $pid ) { ?>
								<div class="hand doubleDelete mt10" title="Исключить из слияния">
									<i class="icon-cancel-circled red"></i></div>
							<?php } ?>

						</div>

						<div class="pr15 ml5 wp90 mb5">
							<div class="radio">
								<label>
									<input name="main" type="radio" id="main" <?= $s ?> value="<?= $pid ?>"/>
									<span class="custom-radio success1"><i class="icon-radio-check"></i></span>
									<span class="title fs-10 Bold"><?= current_person( $pid ) ?><br><div class="pl20 fs-07"><?= $d ?></div></span>
								</label>
							</div>
						</div>

						<div class="flex-container box--child">

							<div class="flex-string wp20 gray2 right-text">PID:</div>
							<div class="flex-string wp80 pl10"><?= $pid ?></div>

						</div>

						<div class="flex-container box--child">

							<div class="flex-string wp20 gray2 right-text">Дата создания:</div>
							<div class="flex-string wp80 pl10"><?= get_sfdate( getPersonData( $pid, 'date_create' ) ) ?></div>

						</div>

						<div class="flex-container box--child">

							<div class="flex-string wp20 gray2 right-text">Ответственный:</div>
							<div class="flex-string wp80 pl10"><?= current_user( getPersonData( $pid, 'iduser' ) ) ?></div>

						</div>

						<?php
						$cl = (int)getPersonData( $pid, 'clid' );
						if ( $cl > 0 ) {
							?>

							<div class="flex-container box--child">

								<div class="flex-string wp20 gray2 right-text">Клиент:</div>
								<div class="flex-string wp80 pl10">
									<a href="javascript:void(0)" onclick="openClient('<?= $cl ?>)" title="Открыть карточку"><i class="icon-building"></i><?= current_client( $cl ) ?>
									</a></div>

							</div>

							<?php
						}
						?>

						<?php if ( (int)$da['idmain'] != $pid ) { ?>

							<div class="flex-container box--child">

								<div class="flex-string wp20 gray2 right-text">Похожие данные:</div>
								<div class="flex-string wp80 pl10">
									<?php
									$h = [];
									foreach ( $a as $it => $va ) {

										foreach ( $va as $i => $v ) {

											if ( (int)$i == $pid ) {

												$h[] = (is_array( $v )) ? '<i class="'.strtr( $it, $tipa ).'"></i>'.implode( ",", $v ) : '<i class="'.strtr( $it, $tipa ).'"></i>'.$v;

											}

										}

									}

									print implode( ";", $h );
									?>
								</div>

							</div>
						<?php } ?>

					</div>
				<?php }
				?>

			</div>

			<div class="row mb10">

				<div class="column12 grid-12">
					<div id="divider" class="red" align="center">
						<b class="blue">Выбор действий</b>
					</div>
				</div>

			</div>

			<div class="row">

				<div class="column12 grid-2 right-text gray2 flh-09">Новый ответственый</div>
				<div class="column12 grid-10">

					<?php
					$element = new Elements();
					print $element -> UsersSelect( 'more[newuser]', [
						"class" => "wp97",
						"users" => get_people( $iduser1, "yes" ),
						"sel"   => "-1"
					] );
					?>

				</div>

			</div>

			<?php
			if ( $da['tip'] == 'client' ) {
				?>
				<div class="row">

					<div class="column12 grid-2 right-text gray2"></div>
					<div class="column12 grid-10">
						<div class="checkbox">

							<label>
								<input name="more[trash]" type="checkbox" value="yes"/>
								<span class="custom-checkbox"><i class="icon-ok"></i></span>
								&nbsp;Поместить сливаемые записи в корзину
								<i class="icon-info-circled blue" title="В противном случае записи будут удалены"></i>
							</label>
						</div>

					</div>

				</div>
			<?php } ?>

			<div class="row">

				<div class="column12 grid-2 right-text gray2"></div>
				<div class="column12 grid-10">

					<div class="checkbox">
						<label>
							<input name="more[merge]" type="checkbox" checked="checked" value="yes"/>
							<span class="custom-checkbox"><i class="icon-ok"></i></span>
							&nbsp;Слить данные по возможности (телефоны, email)
							<i class="icon-info-circled blue" title="В противном случае оставим только данные основной записи"></i>
						</label>
					</div>

				</div>

			</div>

			<div class="row">

				<div class="column12 grid-2 right-text gray2"></div>
				<div class="column12 grid-10">
					<div class="checkbox">

						<label>
							<input name="more[log]" type="checkbox" checked="checked" value="yes"/>
							<span class="custom-checkbox"><i class="icon-ok"></i></span>
							&nbsp;Записать данные сливаемых записей в лог
							<i class="icon-info-circled blue" title="В противном случае в лог добавим только факт слияния"></i>
						</label>
					</div>

				</div>

			</div>

			<div class="row">

				<div class="column12 grid-2 right-text gray2"></div>
				<div class="column12 grid-10">
					<div class="checkbox">

						<label>
							<input name="more[notify]" type="checkbox" value="yes"/>
							<span class="custom-checkbox"><i class="icon-ok"></i></span>
							&nbsp;Уведомить Ответственных за сливаемые записи по email
						</label>
					</div>

				</div>

			</div>

			<div class="row">

				<div class="column12 grid-2 right-text gray2"></div>
				<div class="column12 grid-10">
					<div class="checkbox">

						<label>
							<input name="more[ignoredClear]" type="checkbox" value="yes"/>
							<span class="custom-checkbox"><i class="icon-ok"></i></span>
							&nbsp;Удалить совпадающие данные из игнорируемых записей
						</label>
					</div>

				</div>

			</div>

			<div class="row mb10">

				<div class="column12 grid-12">
					<div id="divider" class="red" align="center">
						<b class="blue">Комментарий</b>
					</div>
				</div>

			</div>

			<div class="row">

				<div class="column12 grid-12">

					<textarea id="more[des]" name="more[des]" class="wp100" rows="4"></textarea>

				</div>

			</div>

		</div>

		<hr class="mt10">

		<div align="right" class="button--pane">

			<A href="javascript:void(0)" onclick="$('#doubleForm').submit()" class="button greenbtn"><i class="icon-ok"></i>Выполнить</A>&nbsp;
			<A href="javascript:void(0)" onclick="DClose()" class="button">Закрыть</A>

		</div>
	</FORM>

	<script>

		var $ignored = [];

		$(function () {

			$('#dialog').css('width', '703px').center();

		});

		$(document).on('click', '.doubleDelete', function () {

			var $element = $(this).closest('.infodiv');
			var $id = $(this).closest('.infodiv').data('id');

			$ignored.push($id);

			var $ign = $ignored.join();

			$('#ignored').val($ign);

			$element.remove();

		});

		$('#doubleForm').ajaxForm({
			dataType: 'json',
			beforeSubmit: function () {

				var $out = $('#message');
				var em = 0;

				if ($('#theme').val() == '') $('#theme').removeClass('required');

				$(".required").removeClass("empty").css({"color": "inherit", "background": "#FFF"});
				$(".required").each(function () {

					if ($(this).val() === '') {
						$(this).addClass("empty").css({"color": "red", "background": "#FF8080"});
						em = em + 1;
					}

				});

				$out.empty();

				if (em > 0) {

					alert("Не заполнены обязательные поля\n\rОни выделены цветом");
					return false;

				}

				if (em === 0) {

					$out.fadeTo(1, 1).css('display', 'block').append('<div id="loader"><img src="/assets/images/loader.gif"> Выполняю...</div>');

					$('#dialog').css('display', 'none');
					$('#dialog_container').css('display', 'none');

					return true;

				}

			},
			success: function (data) {

				$('#dialog_container').css('display', 'none');
				$('#dialog').css('display', 'none');

				$('#message').fadeTo(1, 1).css('display', 'block').html('Результат: ' + data.result);
				setTimeout(function () {
					$('#message').fadeTo(1000, 0);
				}, 20000);

				if (typeof DoublesPageRender === 'function') DoublesPageRender();

				//todo: предусмотреть реакцию, если в качестве дубля выбрана другая запись - надо перегрузить окно в этой записи

			}
		});

	</script>
	<?php

	exit();
}

//слияние дублей. действие
if ( $action == 'merge.on' ) {

	$id                = (int)$_REQUEST['id'];
	$params['main']    = $_REQUEST['main'];
	$params['list']    = $_REQUEST['doid'];
	$params['more']    = $_REQUEST['more'];
	$params['ignored'] = $_REQUEST['ignored'];
	$tip               = $_REQUEST['tip'];

	if ( $tip == 'client' ) {

		$client = new Client();
		$result = $client -> mergeDouble( $id, $params );

		print json_encode_cyr( ["result" => $result] );

	}
	elseif ( $tip == 'person' ) {

		$client = new Person();
		$result = $client -> mergeDouble( $id, $params );

		print json_encode_cyr( ["result" => $result] );

	}

	exit();

}

//игнорирование дубля
if ( $action == 'ignore' ) {

	$id = (int)$_REQUEST['id'];

	$q = "
	SELECT
		{$sqlname}doubles.id as id,
		{$sqlname}doubles.datum as datum,
		{$sqlname}doubles.idmain,
		IF({$sqlname}doubles.tip = 'client', {$sqlname}doubles.idmain, 0) as clid,
		IF({$sqlname}doubles.tip = 'person', {$sqlname}doubles.idmain, 0) as pid,
		{$sqlname}doubles.tip as tip,
		{$sqlname}doubles.status as status,
		{$sqlname}doubles.list as list
	FROM {$sqlname}doubles
	WHERE 
		{$sqlname}doubles.id = '$id'
	";

	$da = $db -> getRow( $q );

	//print_r($da);

	$l = json_decode( (string)$da['list'], true );

	//print_r($l);

	$a['phone'] = $l['phone'];
	$a['email'] = $l['email'];

	$clids = (array)$l['clid'];
	$pids  = (array)$l['pid'];

	if ( (int)$da['clid'] > 0 ) {
		$clids[] = (int)$da['clid'];
	}

	if ( (int)$da['pid'] > 0 ) {
		$pids[] = (int)$da['pid'];
	}

	$min = min( $clids );

	?>
	<DIV class="zagolovok"><B>Игнорировать дубли</B></DIV>
	<FORM action="/content/client.doubles/core.php" method="post" enctype="multipart/form-data" name="doubleForm" id="doubleForm" autocomplete="off">
		<INPUT type="hidden" id="action" name="action" value="ignore.on">
		<INPUT type="hidden" id="id" name="id" value="<?= $id ?>">

		<div id="formtabse" style="overflow-y: auto; max-height: 80vh; overflow-x: hidden">

			<div class="row mb10">

				<div class="column12 grid-12">
					<div id="divider" class="red" align="center">
						<b class="blue">Информация</b>
					</div>
				</div>

			</div>

			<div class="row">

				<?php
				foreach ( $clids as $clid ) {

					$s = ($min -> min == $clid) ? 'checked' : '';
					$d = ($min -> min == $clid) ? '[<b class="green">Добавлен&nbsp;первым</b>]' : '[<b class="gray2">Добавлен&nbsp;позже</b>]';
					
					if ( (int)$da['clid'] == $clid ) {
						$d .= '[<b class="red">Проверяемый</b>]';
					}

					?>
					<div class="column12 grid-12 infodiv mb10 p10" data-id="<?= $clid ?>">

						<INPUT type="hidden" id="clid[]" name="clid[]" value="<?= $clid ?>">

						<div class="pull-aright">

							<a href="javascript:void(0)" onclick="openClient('<?= $clid ?>')" title="Открыть карточку"><i class="icon-attach-1"></i></a>

						</div>

						<div class="pr15 ml5 wp90 mb5">
							<div class="title fs-10 Bold">
								<?= current_client( $clid ) ?><br>
								<div class="fs-07"><?= $d ?></div>
							</div>
						</div>

						<div class="flex-container box--child">

							<div class="flex-string wp20 gray2 right-text">CLID:</div>
							<div class="flex-string wp80 pl10"><?= $clid ?></div>

						</div>

						<div class="flex-container box--child">

							<div class="flex-string wp20 gray2 right-text">Дата создания:</div>
							<div class="flex-string wp80 pl10"><?= get_sfdate( getClientData( $clid, 'date_create' ) ) ?></div>

						</div>

						<div class="flex-container box--child">

							<div class="flex-string wp20 gray2 right-text">Ответственный:</div>
							<div class="flex-string wp80 pl10"><?= current_user( getClientData( $clid, 'iduser' ) ) ?></div>

						</div>

						<?php if ( (int)$da['idmain'] != $clid ) { ?>

							<div class="flex-container box--child">

								<div class="flex-string wp20 gray2 right-text">Похожие данные:</div>
								<div class="flex-string wp80 pl10">
									<?php
									$h = [];
									foreach ( $a as $it => $va ) {

										foreach ( $va as $i => $v ) {

											if ( $i == $clid ) {

												$h[] = (is_array( $v )) ? '<i class="'.strtr( $it, $tipa ).'"></i>'.implode( ",", $v ) : '<i class="'.strtr( $it, $tipa ).'"></i>'.$v;

											}

										}

									}

									print implode( ";", $h );
									?>
								</div>

							</div>
						<?php } ?>

					</div>
				<?php }
				foreach ( $pids as $pid ) {

					$s = ($min -> min == $pid) ? 'checked' : '';
					$d = ($min -> min == $pid) ? '[<b class="green">Добавлен&nbsp;первым</b>]' : '[<b class="gray2">Добавлен&nbsp;позже</b>]';
					
					if ( (int)$da['pid'] == $pid ) {
						$d .= '[<b class="red">Проверяемый</b>]';
					}

					?>
					<div class="column12 grid-12 infodiv mb10 p10" data-id="<?= $pid ?>">

						<INPUT type="hidden" id="doid[]" name="doid[]" value="<?= $pid ?>">

						<div class="pull-aright">

							<a href="javascript:void(0)" onclick="openPerson('<?= $pid ?>')" title="Открыть карточку"><i class="icon-attach-1"></i></a>

						</div>

						<div class="pr15 ml5 wp90 mb5">
							<div class="radio">
								<div class="title fs-10 Bold"><?= current_person( $pid ) ?>
									<div class="fs-07"><?= $d ?></div>
								</div>
							</div>
						</div>

						<div class="flex-container box--child">

							<div class="flex-string wp20 gray2 right-text">PID:</div>
							<div class="flex-string wp80 pl10"><?= $pid ?></div>

						</div>

						<div class="flex-container box--child">

							<div class="flex-string wp20 gray2 right-text">Дата создания:</div>
							<div class="flex-string wp80 pl10"><?= get_sfdate( getPersonData( $pid, 'date_create' ) ) ?></div>

						</div>

						<div class="flex-container box--child">

							<div class="flex-string wp20 gray2 right-text">Ответственный:</div>
							<div class="flex-string wp80 pl10"><?= current_user( getPersonData( $pid, 'iduser' ) ) ?></div>

						</div>

						<?php
						$cl = (int)getPersonData( $pid, 'clid' );
						if ( $cl > 0 ) {
							?>

							<div class="flex-container box--child">

								<div class="flex-string wp20 gray2 right-text">Клиент:</div>
								<div class="flex-string wp80 pl10">
									<a href="javascript:void(0)" onclick="openClient('<?= $cl ?>)" title="Открыть карточку">
										<i class="icon-building"></i><?= current_client( $cl ) ?>
									</a>
								</div>

							</div>

							<?php
						}
						?>

						<?php if ( (int)$da['idmain'] != $pid ) { ?>

							<div class="flex-container box--child">

								<div class="flex-string wp20 gray2 right-text">Похожие данные:</div>
								<div class="flex-string wp80 pl10">
									<?php
									$h = [];
									foreach ( $a as $it => $va ) {

										foreach ( $va as $i => $v ) {

											if ( $i == $pid ) {

												$h[] = (is_array( $v )) ? '<i class="'.strtr( $it, $tipa ).'"></i>'.implode( ",", $v ) : '<i class="'.strtr( $it, $tipa ).'"></i>'.$v;

											}

										}

									}

									print implode( ";", $h );
									?>
								</div>

							</div>
						<?php } ?>

					</div>
				<?php }
				?>

			</div>

			<div class="row mb10">

				<div class="column12 grid-12">
					<div id="divider" class="red" align="center">
						<b class="blue">Комментарий</b>
					</div>
				</div>

			</div>

			<div class="row">

				<div class="column12 grid-12">

					<textarea id="more[des]" name="more[des]" class="wp100" rows="4"></textarea>

				</div>

			</div>

		</div>

		<div class="viewdiv">Это действие изменит статус найденных дублей на обработанный без выполнения каких-либо действий над найденными записями.</div>

		<hr class="mt10">

		<div align="right" class="button--pane">

			<A href="javascript:void(0)" onclick="$('#doubleForm').trigger('submit')" class="button greenbtn"><i class="icon-ok"></i>Выполнить</A>&nbsp;
			<A href="javascript:void(0)" onclick="DClose()" class="button">Закрыть</A>

		</div>
	</FORM>

	<script>

		$(function () {

			$('#dialog').css('width', '703px');
			$('#dialog').center();

		});

		$('#doubleForm').ajaxForm({
			dataType: 'json',
			beforeSubmit: function () {

				var $out = $('#message');
				var em = 0;

				if ($('#theme').val() == '') $('#theme').removeClass('required');

				$(".required").removeClass("empty").css({"color": "inherit", "background": "#FFF"});
				$(".required").each(function () {

					if ($(this).val() === '') {
						$(this).addClass("empty").css({"color": "red", "background": "#FF8080"});
						em = em + 1;
					}

				});

				$out.empty();

				if (em > 0) {

					alert("Не заполнены обязательные поля\n\rОни выделены цветом");
					return false;

				}

				if (em === 0) {

					$out.fadeTo(1, 1).css('display', 'block').append('<div id="loader"><img src="/assets/images/loader.gif"> Выполняю...</div>');

					$('#dialog').css('display', 'none');
					$('#dialog_container').css('display', 'none');

					return true;

				}

			},
			success: function (data) {

				$('#dialog_container').css('display', 'none');
				$('#dialog').css('display', 'none');

				$('#message').fadeTo(1, 1).css('display', 'block').html('Результат: ' + data.result);
				setTimeout(function () {
					$('#message').fadeTo(1000, 0);
				}, 20000);

				if (typeof DoublesPageRender === 'function') DoublesPageRender();

			}
		});

	</script>
	<?php

	exit();

}

//игнорирование дубля. действие
if ( $action == 'ignore.on' ) {

	$id             = (int)$_REQUEST['id'];
	$params['list'] = $_REQUEST['clid'];
	$params['more'] = $_REQUEST['more'];

	$client = new Client();
	$result = $client -> ignoreDouble( $id, $params );

	print json_encode_cyr( ["result" => $result] );

	exit();

}

//диалог ручной проверки на дубли. не используется
if ( $action == 'checkDouble' ) {

	$pid  = (int)$_REQUEST['pid'];
	$clid = (int)$_REQUEST['clid'];

	?>
	<DIV class="zagolovok"><B>Поиск дублей</B></DIV>
	<FORM action="/content/client.doubles/core.php" method="post" enctype="multipart/form-data" name="doubleForm" id="doubleForm" autocomplete="off">
		<INPUT type="hidden" id="action" name="action" value="checkDouble.on">

		<div id="formtabse" style="overflow-y: auto; max-height: 80vh; overflow-x: hidden">

			<div class="row">

				<div class="column12 grid-2 right-text gray2"></div>
				<div class="column12 grid-10">

					<div class="checkbox">
						<label>
							<input name="more[merge]" type="checkbox" checked="checked" value="yes"/>
							<span class="custom-checkbox"><i class="icon-ok"></i></span>
							&nbsp;Слить данные по возможности (телефоны, email)
							<i class="icon-info-circled blue" title="В противном случае оставим только данные основной записи"></i>
						</label>
					</div>

				</div>

			</div>

			<div class="row">

				<div class="column12 grid-2 right-text gray2"></div>
				<div class="column12 grid-10">
					<div class="checkbox">

						<label>
							<input name="more[log]" type="checkbox" checked="checked" value="yes"/>
							<span class="custom-checkbox"><i class="icon-ok"></i></span>
							&nbsp;Записать данные сливаемых записей в лог
							<i class="icon-info-circled blue" title="В противном случае в лог добавим только факт слияния"></i>
						</label>
					</div>

				</div>

			</div>

			<div class="row">

				<div class="column12 grid-2 right-text gray2"></div>
				<div class="column12 grid-10">
					<div class="checkbox">

						<label>
							<input name="more[notify]" type="checkbox" value="yes"/>
							<span class="custom-checkbox"><i class="icon-ok"></i></span>
							&nbsp;Уведомить Ответственных за сливаемые записи по email
						</label>
					</div>

				</div>

			</div>

			<div class="row mb10">

				<div class="column12 grid-12">
					<div id="divider" class="red" align="center">
						<b class="blue">Комментарий</b>
					</div>
				</div>

			</div>

			<div class="row">

				<div class="column12 grid-12">

					<textarea id="more[des]" name="more[des]" class="wp100" rows="4"></textarea>

				</div>

			</div>

		</div>

		<hr class="mt10">

		<div align="right" class="button--pane">

			<A href="javascript:void(0)" onclick="$('#doubleForm').trigger('submit')" class="button greenbtn"><i class="icon-ok"></i>Выполнить</A>&nbsp;
			<A href="javascript:void(0)" onclick="DClose()" class="button">Закрыть</A>

		</div>
	</FORM>

	<script>

		$(function () {

			$('#dialog').css('width', '703px').center();

		});

		$('#doubleForm').ajaxForm({
			dataType: 'json',
			beforeSubmit: function () {

				var $out = $('#message');
				var em = 0;

				if ($('#theme').val() == '') $('#theme').removeClass('required');

				$(".required").removeClass("empty").css({"color": "inherit", "background": "#FFF"});
				$(".required").each(function () {

					if ($(this).val() === '') {
						$(this).addClass("empty").css({"color": "red", "background": "#FF8080"});
						em = em + 1;
					}

				});

				$out.empty();

				if (em > 0) {

					alert("Не заполнены обязательные поля\n\rОни выделены цветом");
					return false;

				}

				if (em === 0) {

					$out.fadeTo(1, 1).css('display', 'block').append('<div id="loader"><img src="/assets/images/loader.gif"> Выполняю...</div>');

					$('#dialog').css('display', 'none');
					$('#dialog_container').css('display', 'none');

					return true;

				}

			},
			success: function (data) {

				$('#dialog_container').css('display', 'none');
				$('#dialog').css('display', 'none');

				$('#message').fadeTo(1, 1).css('display', 'block').html('Результат: ' + data.result);
				setTimeout(function () {
					$('#message').fadeTo(1000, 0);
				}, 20000);

				if (parseInt(data) > 0) doLoad('/content/client.doubles/core.php?action=view&id=' + data);
				else Swal.fire('Отлично', 'Дубли не найдены', 'success');

			}
		});

	</script>
	<?php

	exit();

}

//ручная проверка на дубли
if ( $action == 'checkDouble.on' ) {

	$pid  = (int)$_REQUEST['pid'];
	$clid = (int)$_REQUEST['clid'];
	$tip  = $_REQUEST['tip'];

	$count = 0;

	if ( $pid > 0 || $tip == 'person' ) {

		//проверка конктретного контакта
		if ( $pid > 0 ) {

			$person = new Person();
			$double = $person -> checkDouble( $pid );
			$id     = $double -> doubleid;

			print json_encode_cyr( [
				"id"   => $id,
				"type" => "one"
			] );

		}
		//проверка всей базы
		else {

			$sort = '';

			//pid последней проверенной записи
			$last = (int)$db -> getOne( "SELECT MAX(idmain) FROM {$sqlname}doubles WHERE tip = 'person' AND identity = '$identity'" ) + 0;
			if ( $last > 0 ) {

				$sort = " pid > '$last' AND ";

			}

			$c = $a = 0;

			$person = new Person();

			$res = $db -> query( "SELECT pid FROM {$sqlname}personcat WHERE $sort identity = '$identity' ORDER BY pid" );
			While ($da = $db -> fetch( $res )) {

				$double = $person -> checkDouble( $da['pid'], [
					"noNotify" => true,
					"multi"    => true
				] );

				if ( $double -> doubleid > 0 ) {
					$count++;
				}

				if ( $c == 500 ) {

					$c = 0;
					sleep( 2 );

				}

				$c++;
				$a++;

				$person = NULL;

			}

			//print $db -> lastQuery();

			print json_encode_cyr( [
				"count" => $count,
				"type"  => "list",
				"total" => $a
			] );

		}

	}
	elseif ( $clid > 0 || $tip == 'client' ) {

		$client = new Client();

		//проверка конктретного Клиента
		if ( $clid > 0 ) {

			$double = $client -> checkDouble( $clid );
			$id     = $double -> doubleid;

			print json_encode_cyr( [
				"id"   => $id,
				"type" => "one"
			] );

		}
		//проверка всей базы
		else {

			$sort = '';

			//pid последней проверенной записи
			$last = (int)$db -> getOne( "SELECT MAX(idmain) FROM {$sqlname}doubles WHERE tip = 'client' AND identity = '$identity'" ) + 0;
			if ( $last > 0 ) {

				$sort = " clid > '$last' AND ";

			}

			$c = $a = 0;

			$res = (array)$db -> getCol( "SELECT clid FROM {$sqlname}clientcat WHERE $sort identity = '$identity' ORDER BY clid" );
			foreach ( $res as $id ) {

				$double = $client -> checkDouble( $id, [
					"noNotify" => true,
					"multi"    => true
				] );
				if ( $double -> doubleid > 0 ) {
					$count++;
				}

				if ( $c == 500 ) {

					$c = 0;
					sleep( 2 );

				}

				$c++;
				$a++;

			}

			print json_encode_cyr( [
				"count" => $count,
				"type"  => "list",
				"total" => $a
			] );

		}

	}

	exit();

}

//проверим, проводилась ли проверка записи на дубли
if ( $action == 'isDouble' ) {

	$id  = (int)$_REQUEST['id'];
	$tip = $_REQUEST['tip'];

	$main = 0;
	$sec  = 'no';
	$z = [];

	if ( in_array( $iduser1, (array)$dblSettings['Coordinator'] ) || in_array( $iduser1, (array)$dblSettings['Operator'] ) ) {

		$q    = "
		SELECT
			id, idmain, ids, tip
		FROM {$sqlname}doubles
		WHERE 
			({$sqlname}doubles.idmain = '$id' or FIND_IN_SET('$id', {$sqlname}doubles.ids) > 0) and 
			{$sqlname}doubles.tip = '$tip' and 
			{$sqlname}doubles.status = 'no' and 
			{$sqlname}doubles.identity = '$identity' 
		";
		$xmain = $db -> getRow( $q );

		// проверим смежные записи на существование
		if( !empty($xmain['ids']) ){

			$x = yexplode(",", $xmain['ids']);
			foreach ($x as $xid){

				// исключаем из массива проверяемую запись
				if($xid == $id){
					continue;
				}

				if($xmain['tip'] == 'client'){
					$zx = (int)$db -> getOne("SELECT clid FROM {$sqlname}clientcat WHERE clid = '$xid'");
					if($zx > 0){
						$z[] = $zx;
					}
				}
				else{
					$zx = (int)$db -> getOne("SELECT pid FROM {$sqlname}personcat WHERE pid = '$xid'");
					if($zx > 0){
						$z[] = $zx;
					}
				}

			}

		}

		//print_r($xmain);
		//print $db -> lastQuery();

		// получим информацию о клиенте
		$client = get_client_info((int)$xmain['idmain'], "yes");

		//print_r($client);

		// если название есть, то клиент существует
		if( (int)$client['clid'] > 0 && !empty($z)) {
			$main = (int)$xmain['id'];
			$sec  = 'yes';
		}

	}

	print json_encode( [
		"id"  => $main,
		"sec" => $sec,
		"z"   => $z
	] );

	exit();

}

//вывод блока в карточке Клиента и/или Контакта
//реализовано в project.core.new.js
if ( $action == 'card' ) {

	$pid  = (int)$_REQUEST['pid'];
	$clid = (int)$_REQUEST['clid'];


	exit();

}

//для вывода модального окна
if ( $_REQUEST['modal'] == 'true' ) {

	if ( !in_array( $iduser1, (array)$dblSettings['Coordinator'] ) ) {

		print '<div class="warning">Нет доступа</div>';
		exit();

	}
	?>

	<style>
		#dblbottom {
			position : fixed;
			bottom   : 10px;
			width    : calc(100vw - 20px);
		}
	</style>

	<form id="doublesForm">

		<div class="p10 viewdiv flex-container" id="dbltop">

			<?php
			//для облака ручной поиск отключаем из-за непредсказуемо высокой нагрузки
			if ( !$isCloud ) {
				?>
				<div class="tagsmenuToggler hand relativ mt5" data-id="fhelper">

					<span class="fs-11 blue"><i class="icon-ellipsis gray"></i></span>
					<div class="tagsmenu fly hidden left" id="fhelper" style="left:-10px; top: 100%">
						<ul class="w300 fs-09">
							<li onclick="doubleModule.check('0','client')" title="Запустить для Клиентов">
								<i class="icon-building broun"></i> Запустить для Клиентов
							</li>
							<li onclick="doubleModule.check('0','person')" title="Запустить для Контактов">
								<i class="icon-users-1 blue"></i> Запустить для Контактов
							</li>
						</ul>
					</div>

				</div>
			<?php } ?>

			<div class="ydropDown inline p0 m0 ml20 transparent w140">

				<i class="icon-filter blue fs-11"></i>
				<span>Фильтр:</span>
				<span class="ydropText fs-10">Все</span>
				<i class="icon-angle-down pull-aright arrow"></i>
				<div class="yselectBox wi120 disable--select" style="max-height: 350px;">
					<div class="ydropString yRadio ellipsis">
						<label>
							<input type="radio" name="filterDouble" id="filterDouble" data-title="Все" value="" checked class="hidden"><i class="icon-star fs-10 red"></i>&nbsp;Все
						</label>
					</div>
					<div class="ydropString yRadio ellipsis">
						<label>
							<input type="radio" name="filterDouble" id="filterDouble" data-title="Клиенты" value="client" class="hidden"><i class="icon-building fs-10 broun"></i>&nbsp;Клиенты
						</label>
					</div>
					<div class="ydropString yRadio ellipsis">
						<label>
							<input type="radio" name="filterDouble" id="filterDouble" data-title="Контакты" value="person" class="hidden"><i class="icon-users-1 fs-10 blue"></i>&nbsp;Контакты
						</label>
					</div>
				</div>

			</div>

			<div id="reload" class="flex-string right-text">

				<a href="javascript:void(0)" onclick="DoublesPageRender()" class="gray blue" title="Обновить"><i class="icon-arrows-cw"></i></a>

			</div>

		</div>

	</form>

	<div id="dblview"></div>

	<div class="p10 viewdiv flex-container" id="dblbottom">

		<div id="pagination" class="flex-string"></div>
		<div id="reload" class="flex-string right-text">

			<a href="javascript:void(0)" onclick="DoublesPageRender()" class="button1" title="Обновить"><i class="icon-arrows-cw"></i> Обновить</a>

		</div>

	</div>

	<!--<script src="../../js/tableHeadFixer.js"></script>-->
	<script>

		var $delement = $('#dblview');
		var $page = 1;

		$.Mustache.load('/content/client.doubles/tpl.mustache');

		$('.footer').addClass('hidden');

		$(document).off('change', '#filterDouble');

		$(function () {

			$('#swindow').find('.body').css({"height": "calc(100% - 60px)"});
			DoublesPageRender();

		});

		function DoublesPageRender() {

			var url = '/content/client.doubles/core.php?action=list&page=' + $page;
			var height = $('#swindow').find('.body').innerHeight() - $('#dbltop').outerHeight() - $('#dblbottom').outerHeight() - 20;
			var str = $('#doublesForm').serialize();

			$delement.empty().append('<img src="/assets/images/Services.svg" width="50px" height="50px">');

			$.getJSON(url, str, function (viewData) {

				$delement.empty().mustache('listTpl', viewData);
				$delement.css({'height': height + 'px'});
				//$delement.find("#zebraTable").tableHeadFixer({'z-index': 12000});
				$delement.find('.ellipsis').css({"position": "inherit"});
				$delement.find('i').css({"position": "inherit"});

				var page = viewData.page;
				var pageall = viewData.pageall;

				var pg = 'Стр. ' + page + ' из ' + pageall;

				if (pageall > 1) {

					var prev = page - 1;
					var next = page + 1;

					if (page === 1) pg = '&nbsp;<a href="javascript:void(0)" title="Начало"><i class="icon-angle-double-left gray"></i></a>&nbsp;&nbsp;<a href="javascript:void(0)" title="Предыдущая"><i class="icon-angle-left gray"></i></a>&nbsp;' + pg + '&nbsp;<a href="javascript:void(0)" onclick="DoublesPageChange(\'' + next + '\')" title="Следующая"><i class="icon-angle-right"></i></a>&nbsp;&nbsp;<a href="javascript:void(0)" onclick="DoublesPageChange(\'' + pageall + '\')" title="Последняя"><i class="icon-angle-double-right"></i></a>&nbsp;';
					else if (page === pageall) pg = '&nbsp;<a href="javascript:void(0)" onclick="DoublesPageChange(\'1\')" title="Начало"><i class="icon-angle-double-left"></i></a>&nbsp;&nbsp;<a href="javascript:void(0)" onclick="DoublesPageChange(\'' + prev + '\')" title="Предыдущая"><i class="icon-angle-left"></i></a>&nbsp;' + pg + '&nbsp;<a href="javascript:void(0)" title="Следующая"><i class="icon-angle-right gray"></i></a>&nbsp;&nbsp;<a href="javascript:void(0)" title="Последняя"><i class="icon-angle-double-right gray"></i></a>&nbsp;';
					else pg = '&nbsp;<a href="javascript:void(0)" onclick="DoublesPageChange(\'1\')" title="Начало"><i class="icon-angle-double-left"></i></a>&nbsp;&nbsp;<a href="javascript:void(0)" onclick="DoublesPageChange(\'' + prev + '\')" title="Предыдущая"><i class="icon-angle-left"></i></a>&nbsp;' + pg + '&nbsp;<a href="javascript:void(0)" onclick="DoublesPageChange(\'' + next + '\')" title="Следующая"><i class="icon-angle-right"></i></a>&nbsp;&nbsp;<a href="javascript:void(0)" onclick="DoublesPageChange(\'' + pageall + '\')" title="Последняя"><i class="icon-angle-double-right"></i></a>&nbsp;';

				}

				$('#pagination').html(pg);

			});

		}

		function DoublesPageChange(page) {

			$page = page;
			DoublesPageRender();

		}

		$(document).on('click', '.closer', function () {

			$('#swindow').find('.body').css({"height": ""});

		});
		$(document).on('change', '#filterDouble', function () {

			DoublesPageRender();

		});

	</script>
	<?php

}
