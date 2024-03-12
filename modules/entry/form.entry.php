<?php
/* ============================ */
/*         SalesMan CRM         */
/* ============================ */
/* (C) 2016 Vladislav Andreev   */
/*       SalesMan Project       */
/*        www.isaler.ru         */
/*        ver. 2016.20          */

/* ============================ */

use Salesman\Elements;

error_reporting( E_ERROR );
ini_set( 'display_errors', 1 );
header( "Pragma: no-cache" );

$rootpath = dirname(__DIR__, 2);

include $rootpath."/inc/config.php";
include $rootpath."/inc/dbconnector.php";
include $rootpath."/inc/auth.php";
include $rootpath."/inc/func.php";
include $rootpath."/inc/settings.php";
include $rootpath."/developer/events.php";
include $rootpath."/inc/language/".$language.".php";

$thisfile = basename( __FILE__ );

$action = $_REQUEST['action'];
$phone  = $_REQUEST['phone'];
$ide    = (int)$_REQUEST['id'];
$iduser = (int)$_REQUEST['iduser'];
$clid   = (int)$_REQUEST['clid'];

$status = [
	'0' => 'Новое',
	'1' => 'Обработано',
	'2' => 'Отменено'
];
$colors = [
	'0' => 'broun',
	'1' => 'green',
	'2' => 'gray'
];

$ress         = $db -> getOne( "select usersettings from {$sqlname}user where iduser='".$iduser1."' and identity = '$identity'" );
$usersettings = json_decode( $ress, true );

$thistime = date( 'G:00', mktime( date( 'H' ) + 2, date( 'i' ), date( 's' ), date( 'm' ), date( 'd' ), date( 'Y' ) ) + ($tzone) * 3600 );

if ( date( 'H' ) > 20 ) {
	$thistime = current_datum(-1)." 09:00";
}
else {
	$thistime = current_datum()." ".$thistime;
}

if ( !$datum ) {
	$datum = current_datum();
}

/**
 * Названия полей для клиента
 */
$fieldClient = $fieldsNames['client'];
$fieldPerson = $fieldsNames['person'];
$fieldDeal   = $fieldsNames['dogovor'];

//загружаем все возможные цепочки и конвертируем в JSON
$mFunnel = json_encode_cyr( getMultiStepList() );

//доп.напстройки
$customSettings = customSettings( 'settingsMore' );
$timecheck      = ($customSettings['timecheck'] == 'yes') ? 'true' : 'false';

if ( $action == 'view' ) {

	$entry = $db -> getRow( "SELECT * FROM {$sqlname}entry WHERE ide = '$ide' and identity = '$identity'" );

	if ( file_exists( $rootpath.'/modules/modcatalog' ) ) {
		$showcat = true;
	}
	?>
	<DIV class="zagolovok">Просмотр обращения</DIV>

	<div id="formtabs" class="box--child" style="overflow-y: auto; overflow-x: hidden">

		<div class="flex-container mb10 mt10">

			<div class="flex-string wp20 fs-12 right-text gray2">Дата:</div>
			<div class="flex-string wp80 fs-12 pl10"><?= get_sfdate( $entry['datum'] ) ?></div>

		</div>
		<div class="flex-container mb10">

			<div class="flex-string wp20 fs-12 right-text gray2">Автор:</div>
			<div class="flex-string wp80 fs-12 pl10"><?= current_user( $entry['autor'] ) ?></div>

		</div>
		<div class="flex-container mb10">

			<div class="flex-string wp20 fs-12 right-text gray2">Ответственный:</div>
			<div class="flex-string wp80 fs-12 pl10"><?= current_user( $entry['iduser'] ) ?></div>

		</div>

		<div class="flex-container mt20 mb10 greenbg-sub">

			<div class="flex-string wp20 fs-12 right-text gray2">Статус:</div>
			<div class="flex-string wp80 fs-12 Bold pl10 <?= strtr( $entry['status'], $colors ) ?>"><?= strtr( $entry['status'], $status ) ?></div>

		</div>

		<div class="flex-container mb10 greenbg-sub">

			<div class="flex-string wp20 fs-12 right-text gray2">Комментарий:</div>
			<div class="flex-string wp80 fs-12 pl10">
				<?= nl2br( $entry['content'] ) ?>
			</div>

		</div>

		<div class="flex-container mt20 mb10">

			<div class="flex-string wp20 fs-12 right-text gray2">Клиент:</div>
			<div class="flex-string wp80 fs-12 pl10">
				<i class="icon-commerical-building broun"></i><a href="javascript:void(0)" onclick="openClient('<?= $entry['clid'] ?>')" title="Просмотр"><?= current_client( $entry['clid'] ) ?></a>
			</div>

		</div>
		<?php if ( $entry['pid'] > 0 ) { ?>
			<div class="flex-container mb10">

				<div class="flex-string wp20 fs-12 right-text gray2">Контакт:</div>
				<div class="flex-string wp80 fs-12 pl10">
					<i class="icon-user-1 blue"></i><a href="javascript:void(0)" onclick="openPerson('<?= $entry['pid'] ?>')" title="Карточка"><?= current_person( $entry['pid'] ) ?></a>
				</div>

			</div>
		<?php } ?>
		<?php if ( $entry['did'] > 0 ) { ?>
			<div class="flex-container mb10">

				<div class="flex-string wp20 fs-12 right-text gray2">Сделка:</div>
				<div class="flex-string wp80 fs-12 pl10">
					<i class="icon-briefcase-1 blue"></i><a href="javascript:void(0)" onclick="openDogovor('<?= $entry['did'] ?>')" title="Карточка"><?= current_dogovor( $entry['did'] ) ?></a>
				</div>

			</div>
		<?php } ?>

		<!--<hr class="flex-container mb20">-->

		<?php
		//if($ide < 1){
		$result = $db -> getAll( "SELECT * FROM {$sqlname}entry_poz WHERE ide = '$ide' and identity = '$identity'" );
		$all    = count( $result );
		if ( $all > 0 ) {
			?>

			<div id="divider"><b>Позиции запроса</b></div>

			<table id="bborder">
				<thead>
				<tr>
					<th>Наименование</th>
					<th>Количество</th>
				</tr>
				</thead>
				<?php
				foreach ( $result as $data ) {

					print '
					<tr height="35" class="ha">
						<td><div class="fs-11">'.($showcat == true ? '<A href="card.modcatalog.php?n_id='.$data['prid'].'" title="Карточка" target="_blank"><i class="icon-archive broun"></i>&nbsp;'.$data['title'].'</A>' : $data['title']).'</div></td>
						<td width="160" align="center"><b>'.$data['kol'].'</b></td>
					</tr>';

				}
				?>
			</table>

			<?php
		}
		//}
		?>

	</div>

	<hr>

	<div class="button--pane pull-aright">

		<?php if ( $entry['did'] < 1 ) { ?>

			<div id="cancelbutton"><A href="javascript:void(0)" onClick="DClose()" class="button">Отмена</A></div>
			<span id="submitbutton" class="hidden1"><A href="javascript:void(0)" onClick="editDogovor('<?= $ide ?>','fromentry');" class="button">Преобразовать в <?= $lang['face']['DealName'][3] ?></A>&nbsp;</span>

		<?php } ?>

		<?php if ( $entry['did'] > 0 ) { ?>
			<div class="text-right">
				<A href="javascript:void(0)" onclick="openDogovor('<?= $entry['did'] ?>')" class="button">Перейти к <?= $lang['face']['DealName'][2] ?></a>
			</div>
		<?php } ?>

	</div>

	<script>

		if (!isMobile) {

			var hh = $('#dialog_container').actual('height') * 0.95;
			var hh2 = hh - $('.zagolovok').actual('outerHeight') - $('.button--pane').actual('outerHeight') - $('.client').actual('outerHeight') - $('.person').actual('outerHeight') - 50;

			if ($(window).width() > 990) {

				$('#dialog').css({'width': '700px'});
				$('#formtabse').css({'max-height': hh2});
			}
			else {
				$('#dialog').css('width', '80%');
				$('#formtabse').css('max-height', hh2);
			}

		}
		else {

			var h2 = $(window).height() - $('.zagolovok').actual('outerHeight') - $('.button--pane').actual('outerHeight') - 30;
			$('#formtabse').css({'max-height': h2 + 'px', 'height': h2 + 'px'});

			$(".multiselect").addClass('wp97 h0');

			if (isMobile) $('table').rtResponsiveTables();

		}

	</script>
	<?php
	exit();
}
if ( $action == 'edit' ) {

	$pid    = 0;
	$client = $person = [];
	//$client['phone'] = $phone;

	if ( $iduser == 0 ) {
		$iduser = $iduser1;
	}

	if ( $ide > 0 ) {

		$result  = $db -> getRow( "SELECT * FROM {$sqlname}entry WHERE ide = '$ide' and identity = '$identity'" );
		$clid    = (int)$result['clid'];
		$pid     = (int)$result['pid'];
		$did     = (int)$result['did'];
		$iduser  = (int)$result['iduser'];
		$datum   = $result['datum'];
		$content = $result['content'];

	}

	if ( $phone ) {

		$tel = $phone;

		$caller = getxCallerID( $phone, true );

		if ( (int)$caller['clid'] > 0 ) {
			$clid = (int)$caller['clid'];
		}
		if ( (int)$caller['pid'] > 0 ) {
			$pid = (int)$caller['pid'];
		}

		$destination = $db -> getOne( "SELECT did FROM {$sqlname}callhistory WHERE phone = '".preparePhone( $phone )."' and identity = '".$identity."' ORDER BY datum LIMIT 1" );
		if ( $destination > 0 ) {
			$pathDefault = getClientpath( '', '', $destination );
		}

	}

	if ( $pid > 0 ) {

		$person = get_person_info( $pid, "yes" );

	}
	if ( $clid > 0 ) {

		$client = get_client_info( $clid, "yes" );

	}

	//print_r($client);

	if ( $pid > 0 ) {
		$client['phone'] = yimplode( ",", (array)preparePhoneSmart( $phone.",".$client['phone'].",".$person['tel'], false, true ) );
	}
	else {
		$client['phone'] = yimplode( ",", (array)preparePhoneSmart( $phone.",".$client['phone'], false, true ) );
	}

	$person['tel'] = yimplode( ",", preparePhoneSmart( $phone.",".$person['tel'], false, true ) );

	//Найдем в сделках тип по умолчанию или содержащий ключ "Вход"
	$tid = (int)$db -> getOne( "SELECT tid FROM {$sqlname}dogtips WHERE LCASE(title) LIKE '%вход%' or LCASE(title) LIKE '%интерес%' and identity = '$identity' ORDER BY title" );

	$datum_plan = current_datum( -$perDay );

	$title_dog = str_replace( "{ClientName}", $client['title'], generate_num( 'namedogovor' ) );
	$dNum      = generate_num( 'dogovor' );
	if ( $dNum ) {
		$dnum = '<span class="smalltxt green">Номер '.$lang['face']['DealName'][1].': <b>'.$dNum.'</b> (предварительно)</span>';
	}


	//загружаем настройки экспресс-формы
	$efields = json_decode( $db -> getOne( "select params from {$sqlname}customsettings where tip='eform' and identity = '$identity'" ), true );
	$string  = $stringMore = [];

	foreach ( $efields as $tip => $fields ) {

		foreach ( $fields as $input => $param ) {

			if ( $param['active'] != 'yes' ) {
				goto b;
			}

			$param['requered'] = ($param['requered'] == 'yes') ? "required" : "";

			$s = '';

			if ( $input == 'title' && $fieldsNames['client']['title'] != '' ) {

				$s = '
				<div class="flex-container mb10">
					<div class="flex-string wp20 gray2 fs-12 pt7 right-text">'.$fieldsNames['client']['title'].':</div>
					<div class="flex-string wp80 pl10" id="org">
						<INPUT name="client[title]" type="text" class="wp93 '.$param['requered'].'" id="client[title]" value="'.$client['title'].'" placeholder="Например: Сэйлзмэн, ООО">
						<INPUT type="hidden" id="client[clid]" name="client[clid]" value="'.$client['clid'].'">
					</div>
				</div>';

			}
			elseif ( $input == 'head_clid' && $fieldsNames['client']['head_clid'] != '' ) {

				$s = '
				<div class="flex-container box--child mt10 mb10">
	
					<div class="flex-string wp20 gray2 fs-12 pt7 right-text">'.$fieldsNames['client']['head_clid'].':</div>
					<div class="flex-string wp80 pl10">
					
						<div class="relativ wp93" id="head_clid">
							<INPUT type="hidden" id="client[head_clid]" name="client[head_clid]" value="'.$client['head_clid'].'">
							<INPUT id="lst_spisok" type="text" class="wp100 '.$param['requered'].'" value="'.$client['head'].'" readonly onclick="_.debounce(get_orgspisok(\'lst_spisok\',\'head_clid\',\'content/helpers/client.helpers.php?action=get_orgselector\',\'client\\[head_clid\\]\'), 500)" placeholder="Нажмите для выбора"> 
							<span class="idel"><i title="Очистить" onClick="$(\'#client\\[head_clid\\]\').val(\'\'); $(\'#lst_spisok\').val(\'\');" class="icon-block red hand"></i></span>
						</div>
						<div class="fs-10 gray2 em">Укажите головную организацию</div>
					</div>
	
				</div>';

			}
			elseif ( $input == 'phone' && $fieldsNames['client']['phone'] != '' ) {

				$su = '';

				if ( $format_phone != '' ) {

					if ( $client['phone'] != '' ) {

						$phonep = yexplode( ",", (string)$client['phone'] );
						for ( $i = 0, $iMax = count( $phonep ); $i < $iMax; $i++ ) {

							if ( $i == (count( $phonep ) - 1) ) {
								$adder = '<span class="adder hand" title="" data-block="phoneBlock" data-main="vphone" data-mask="'.$format_phone.'"><i class="icon-plus-circled green"></i></span>';
							}
							else {
								$adder = '';
							}

							$su .= '<div class="phoneBlock paddbott5 relativv">
							<INPUT name="client[phone][]" type="text" class="phone w250 '.$param['requered'].'" id="client[phone][]" value="'.trim( $phonep[ $i ] ).'" placeholder="Формат: '.$format_tel.'" data-id="vphone" data-action="valphone" data-type="client.helpers" autocomplete="off">
							<span class="remover hand" data-parent="vphone"><i class="icon-minus-circled red"></i></span>'.$adder.'
						</div>';

						}
					}
					else {

						$su = '<div class="phoneBlock paddbott5 relativv">
						<INPUT name="client[phone][]" type="text" class="phone w250 '.$param['requered'].'" id="client[phone][]" value="'.trim( $client['phone'] ).'" placeholder="Формат: '.$format_tel.'" data-id="vphone" data-action="valphone" data-type="client.helpers" autocomplete="off">
						<span class="remover hand" data-parent="vphone"><i class="icon-minus-circled red"></i></span>
						<span class="adder hand" title="" data-block="phoneBlock" data-main="vphone" data-mask="'.$format_phone.'"><i class="icon-plus-circled green"></i></span>
					</div>';

					}
				}
				else {

					$su = '<div class="phoneBlock paddbott5 relativv">
					<INPUT name="client[phone]" type="text" class="phone wp93 '.$param['requered'].'" id="client[phone]" value="'.trim( $client['phone'] ).'" placeholder="Формат: '.$format_tel.'" data-id="vphone" data-action="valphone" data-type="client.helpers" autocomplete="off">
					<div class="em blue smalltxt">Используйте <b>запятую</b> в качестве разделителя</div>
				</div>';

				}


				$s = '<div class="flex-container mb10">
				<div class="flex-string wp20 gray2 fs-12 pt7 right-text">'.$fieldsNames['client']['phone'].':</div>
				<div class="flex-string wp80 pl10">
					<div id="vphone">'.$su.'</div>
				</div>
			</div>';

			}
			elseif ( $input == 'fax' && $fieldsNames['client']['fax'] != '' ) {

				$su = '';

				if ( $format_phone != '' ) {

					if ( $phone != '' ) {

						$phonep = yexplode( ",", (string)$client['fax'] );
						for ( $i = 0, $iMax = count( $phonep ); $i < $iMax; $i++ ) {

							if ( $i == (count( $phonep ) - 1) ) {
								$adder = '<span class="adder hand" title="" data-block="phoneBlock" data-main="vfax" data-mask="'.$format_phone.'"><i class="icon-plus-circled green"></i></span>';
							}
							else {
								$adder = '';
							}

							$su .= '<div class="phoneBlock paddbott5 relativv">
							<INPUT name="client[fax][]" type="text" class="phone w250 '.$param['requered'].'" id="client[fax][]" value="'.trim( $phonep[ $i ] ).'" placeholder="Формат: '.$format_tel.'" data-id="vfax" data-action="valphone" data-type="client.helpers" autocomplete="off">
							<span class="remover hand" data-parent="vfax"><i class="icon-minus-circled red"></i></span>'.$adder.'
						</div>';

						}
					}
					else {

						$su = '<div class="phoneBlock paddbott5 relativv">
						<INPUT name="client[fax][]" type="text" class="phone w250 '.$param['requered'].'" id="client[fax][]" value="'.trim( $client['fax'] ).'" placeholder="Формат: '.$format_tel.'" data-id="vfax" data-action="valphone" data-type="client.helpers" autocomplete="off">
						<span class="remover hand" data-parent="vfax"><i class="icon-minus-circled red"></i></span>
						<span class="adder hand" title="" data-block="phoneBlock" data-main="vfax" data-mask="'.$format_phone.'"><i class="icon-plus-circled green"></i></span>
					</div>';

					}
				}
				else {

					$su = '<div class="phoneBlock paddbott5 relativv">
					<INPUT name="client[fax]" type="text" class="phone wp93 '.$param['requered'].'" id="client[fax]" value="'.trim( $client['fax'] ).'" placeholder="Формат: '.$format_tel.'" data-id="vfax" data-action="valphone" data-type="client.helpers" autocomplete="off">
					<div class="em blue smalltxt">Используйте <b>запятую</b> в качестве разделителя</div>
				</div>';

				}


				$s = '<div class="flex-container mb10">
				<div class="flex-string wp20 gray2 fs-12 pt7 right-text">'.$fieldsNames['client']['fax'].':</div>
				<div class="flex-string wp80 pl10">
					<div id="vfax">'.$su.'</div>
				</div>
			</div>';

			}
			elseif ( $input == 'mail_url' && $fieldsNames['client']['mail_url'] ) {

				$s = '
				<div class="flex-container mb10">
					<div class="flex-string wp20 gray2 fs-12 pt7 right-text">'.$fieldsNames['client']['mail_url'].':</div>
					<div class="flex-string wp80 pl10" id="vmaile">
						<INPUT name="client[mail_url]" type="text" class="wp93 '.$param['requered'].' validate" id="client[mail_url]" value="'.$client['mail_url'].'" onMouseOut="$(\'#ospisok\').remove();" onblur="$(\'#ospisok\').remove();" autocomplete="off" data-url="content/helpers/client.helpers.php" data-action="valmail">
					</div>
				</div>';

			}
			elseif ( $input == 'clientpath' && $fieldsNames['client']['clientpath'] ) {

				$su = $sub = '';

				$pathDefault = ($client['clientpath'] > 0) ? $client['clientpath'] : $pathDefault;

				$element = new Elements();
				$su      = $element -> ClientpathSelect( 'client[clientpath]', [
					"class" => [
						"wp93",
						$param['requered']
					],
					"sel"   => $pathDefault,
					"data"  => 'data-class="'.$param['requered'].'"'
				] );

				if ( !$otherSettings['guidesEdit'] ) {
					$sub = '<a href="javascript:void(0)" onclick="add_sprav(\'clientpath\',\'client\\\[clientpath\\\]\')" title="Добавить" class="hidden-iphone"><i class="icon-plus-circled blue"></i></a>';
				}

				$s = '<div class="flex-container mb10">
				<div class="flex-string wp20 gray2 fs-12 pt7 right-text">'.$fieldsNames['client']['clientpath'].':</div>
				<div class="flex-string wp80 pl10 relativ">
					'.$su.'
					'.$sub.'
				</div>
			</div>';

			}
			elseif ( $input == 'tip_cmr' && $fieldsNames['client']['tip_cmr'] ) {

				$su = '';

				$relDefault = ($client['tip_cmr'] != '') ? $client['tip_cmr'] : $relTitleDefault;

				$element = new Elements();
				$su      = $element -> RelationSelect( 'client[tip_cmr]', [
					"class" => [
						"wp93",
						$param['requered']
					],
					"sel"   => $relDefault,
					"data"  => 'data-class="'.$param['requered'].'"'
				] );

				$s = '<div class="flex-container mb10">
				<div class="flex-string wp20 gray2 fs-12 pt7 right-text">'.$fieldsNames['client']['tip_cmr'].':</div>
				<div class="flex-string wp80 pl10">
					'.$su.'
				</div>
			</div>';

			}
			elseif ( $input == 'idcategory' && $fieldsNames['client']['idcategory'] ) {

				$su = $sub = '';

				//print_r(array("class" => array("wp93", $param['requered']), "sel" => $client['idcategory'], "tip" => $tt, "data" => 'data-class="'.$param['requered'].'"'));

				$element = new Elements();
				$su      = $element -> IndustrySelect( 'client[idcategory]', [
					"class" => [
						"wp93",
						$param['requered']
					],
					"sel"   => $client['idcategory'],
					"tip"   => $tt,
					"data"  => 'data-class="'.$param['requered'].'"'
				] );

				if ( !$otherSettings['guidesEdit'] ) {
					$sub = '<a href="javascript:void(0)" onclick="add_sprav(\'category\',\'client\\\[idcategory\\\]\')" title="Добавить" class="hidden-iphone"><i class="icon-plus-circled blue"></i></a>&nbsp;';
				}

				$s = '<div class="flex-container mb10">
				<div class="flex-string wp20 gray2 fs-12 pt7 right-text">'.$fieldsNames['client']['idcategory'].':</div>
				<div class="flex-string wp80 pl10 relativ">
					'.$su.'
					'.$sub.'
				</div>
			</div>';

			}
			elseif ( $input == 'des' && $fieldsNames['client']['des'] ) {

				$s = '<div class="flex-container mb10">

				<div class="flex-string wp20 gray2 fs-12 pt7 right-text">'.$fieldsNames['client']['des'].':</div>
				<div class="flex-string wp80 pl10">
					<textarea name="client[des]" rows="2" id="client[des]" class="wp93 '.$param['requered'].'">'.$client['des'].'</textarea>
				</div>

			</div>';

			}
			elseif ( $input == 'territory' && $fieldsNames['client']['territory'] ) {

				$su = $sub = '';

				$element = new Elements();
				$su      = $element -> TerritorySelect( 'client[territory]', [
					"class" => [
						"wp93",
						$param['requered']
					],
					"sel"   => (int)$territory,
					"data"  => 'data-class="'.$param['requered'].'"'
				] );

				if ( !$otherSettings['guidesEdit'] ) {
					$sub = '<a href="javascript:void(0)" onclick="add_sprav(\'territory\',\'client\\\[territory\\\]\')" title="Добавить" class="hidden-iphone"><i class="icon-plus-circled blue"></i></a>';
				}

				$s = '<div class="flex-container mb10">

				<div class="flex-string wp20 gray2 fs-12 pt7 right-text">'.$fieldsNames['client']['territory'].':</div>
				<div class="flex-string wp80 pl10 relativ">
					'.$su.'
					'.$sub.'
				</div>

			</div>';

			}
			elseif ( $input == 'address' && $fieldsNames['client']['address'] ) {

				$s = '<div class="flex-container mb10">

				<div class="flex-string wp20 gray2 fs-12 pt7 right-text">'.$fieldsNames['client']['address'].':</div>
				<div class="flex-string wp80 pl10">
					<div class="relativ"><INPUT name="client[address]" id="client[address]" class="wp93 '.$param['requered'].'" value="'.$client['address'].'" data-type="address"></div>
				</div>

			</div>';

			}
			elseif ( $input == 'site_url' && $fieldsNames['client']['site_url'] ) {

				$s = '<div class="flex-container mb10">

				<div class="flex-string wp20 gray2 fs-12 pt7 right-text">'.$fieldsNames['client']['site_url'].':</div>
				<div class="flex-string wp80 pl10" id="surl">
					<INPUT name="client[site_url]" type="text" id="client[site_url]" class="wp93 '.$param['requered'].' validate" value="'.$client['site_url'].'" onMouseOut="$(\'#ospisok\').remove();" onblur="$(\'#ospisok\').remove();" autocomplete="off" data-url="content/helpers/client.helpers.php" data-action="valsite">
				</div>

			</div>';

			}
			elseif ( $input == 'person' && $fieldsNames['person']['person'] != '' ) {

				$s = '<div class="flex-container mb10">

				<div class="flex-string wp20 gray2 fs-12 pt7 right-text">'.$fieldsNames['person']['person'].':</div>
				<div class="flex-string wp80 pl10" id="pers">
					<input name="person[person]" type="text" id="person[person]" class="wp93 '.$param['requered'].'" value="'.$person['person'].'" autocomplete="off" placeholder="Начните с Фамилии. Например: Иванов Семен Петрович">
					<INPUT type="hidden" id="person[pid]" name="person[pid]" value="'.$person['pid'].'">
				</div>

			</div>';

			}
			elseif ( $input == 'ptitle' && $fieldsNames['person']['ptitle'] != '' ) {

				$fld_var = $db -> getOne("SELECT fld_var FROM {$sqlname}field WHERE fld_name = '$input' and identity = '$identity'");
				$vars = str_replace(" \n", ",", $fld_var);

				$x = '<div class="smalltxt">Например: Генеральный директор</div>';
				$dx = !empty($vars) ? '' : 'suggestion';

				if( !empty($vars) ) {

					$x = '
					<div class="fs-09 em blue"><em>Двойной клик мышкой для показа вариантов</em></div>
					<script>
						var str = "'.$vars.'";
						var data = str.split(",");
						$(".ptitle").autocomplete(data, {
							autofill: true,
							minLength: 0,
							minChars: 0,
							cacheLength: 5,
							maxItemsToShow: 20,
							selectFirst: true,
							multiple: false,
							delay: 0,
							matchSubset: 2
						})
					</script>';

				}

				$s = '
				<div class="flex-container mb10">
	
					<div class="flex-string wp20 gray2 fs-12 pt7 right-text">'.$fieldsNames['person']['ptitle'].':</div>
					<div class="flex-string wp80 pl10">
						<input name="person[ptitle]" type="text" id="person[ptitle]" class="wp93 '.$param['requered'].' ptitle '.$dx.'" value="'.$person['ptitle'].'" placeholder="Например: Генеральный директор">
						'.$x.'
					</div>
	
				</div>';

			}
			elseif ( $input == 'tel' && $fieldsNames['person']['tel'] != '' ) {

				$su = $sub = '';

				if ( $format_phone != '' ) {
					if ( $person['tel'] != '' ) {
						$phonep = yexplode( ",", $person['tel'] );
						for ( $i = 0, $iMax = count( $phonep ); $i < $iMax; $i++ ) {

							if ( $i == (count( $phonep ) - 1) )
								$adder = '<span class="adder hand" title="" data-block="phoneBlock" data-main="vtel" data-mask="'.$format_phone.'"><i class="icon-plus-circled green"></i></span>';
							else $adder = '';

							$su .= '<div class="phoneBlock paddbott5 relativv">
							<INPUT name="person[tel][]" type="text" class="phone w250 '.$param['requered'].'" id="person[tel][]" alt="phone" autocomplete="off" value="'.trim( $phonep[ $i ] ).'" placeholder="Формат: '.$format_tel.'" data-id="vtel" data-action="valphone" data-type="person.helpers">
							<span class="remover hand" data-parent="vtel"><i class="icon-minus-circled red"></i></span>'.$adder.'
						</div>';

						}
					}
					else {

						$su = '<div class="phoneBlock paddbott5 relativv">
						<INPUT name="person[tel][]" type="text" class="phone w250 '.$param['requered'].'" id="person[tel][]" alt="phone" autocomplete="off" value="'.trim( $person['tel'] ).'" placeholder="Формат: '.$format_tel.'" data-id="vtel" data-action="valphone" data-type="person.helpers">
						<span class="remover hand" data-parent="vtel"><i class="icon-minus-circled red"></i></span>
						<span class="adder hand" title="" data-block="phoneBlock" data-main="vtel" data-mask="'.$format_phone.'"><i class="icon-plus-circled green"></i></span>
					</div>';

					}
				}
				else {

					$su = '<div class="phoneBlock paddbott5 relativv">
					<INPUT name="person[tel]" type="text" class="phone wp93 '.$param['requered'].'" id="person[tel]" alt="phone" autocomplete="off" value="'.trim( $person['tel'] ).'" placeholder="Формат: '.$format_tel.'" data-id="vtel" data-action="valphone" data-type="person.helpers">
					<div class="em blue smalltxt">Используйте <b>запятую</b> в качестве разделителя</div>
				</div>';

				}

				$s = '<div class="flex-container mb10">

				<div class="flex-string wp20 gray2 fs-12 pt7 right-text">'.$fieldsNames['person']['tel'].':</div>
				<div class="flex-string wp80 pl10" id="vtel">
					'.$su.'
				</div>

			</div>';

			}
			elseif ( $input == 'mob' && $fieldsNames['person']['mob'] != '' ) {

				$su = $sub = '';

				if ( $format_phone != '' ) {
					if ( $person['mob'] != '' ) {
						$phonep = yexplode( ",", $person['mob'] );
						for ( $i = 0, $iMax = count( $phonep ); $i < $iMax; $i++ ) {

							if ( $i == (count( $phonep ) - 1) )
								$adder = '<span class="adder hand" title="" data-block="phoneBlock" data-main="vmob" data-mask="'.$format_phone.'"><i class="icon-plus-circled green"></i></span>';
							else $adder = '';

							$su .= '<div class="phoneBlock paddbott5 relativv">
							<INPUT name="person[mob][]" type="text" class="phone w250 '.$param['requered'].'" id="person[mob][]" alt="phone" autocomplete="off" value="'.trim( $phonep[ $i ] ).'" placeholder="Формат: '.$format_tel.'" data-id="vmob" data-action="valphone" data-type="person.helpers">
							<span class="remover hand" data-parent="vmob"><i class="icon-minus-circled red"></i></span>'.$adder.'
						</div>';

						}
					}
					else {

						$su = '<div class="phoneBlock paddbott5 relativv">
						<INPUT name="person[mob][]" type="text" class="phone w250 '.$param['requered'].'" id="person[mob][]" alt="phone" autocomplete="off" value="'.trim( $person['mob'] ).'" placeholder="Формат: '.$format_tel.'" data-id="vmob" data-action="valphone" data-type="person.helpers">
						<span class="remover hand" data-parent="vmob"><i class="icon-minus-circled red"></i></span>
						<span class="adder hand" title="" data-block="phoneBlock" data-main="vmob" data-mask="'.$format_phone.'"><i class="icon-plus-circled green"></i></span>
					</div>';

					}
				}
				else {

					$su = '<div class="phoneBlock paddbott5 relativv">
					<INPUT name="person[mob]" type="text" class="phone wp93 '.$param['requered'].'" id="person[mob]" alt="phone" autocomplete="off" value="'.trim( $person['mob'] ).'" placeholder="Формат: '.$format_tel.'" data-id="vmob" data-action="valphone" data-type="person.helpers">
					<div class="em blue smalltxt">Используйте <b>запятую</b> в качестве разделителя</div>
				</div>';

				}

				$s = '<div class="flex-container mb10">

				<div class="flex-string wp20 gray2 fs-12 pt7 right-text">'.$fieldsNames['person']['mob'].':</div>
				<div class="flex-string wp80 pl10" id="vmob">
					'.$su.'
				</div>

			</div>';

			}
			elseif ( $input == 'mail' && $fieldsNames['person']['mail'] != '' ) {

				$s = '<div class="flex-container mb10">

				<div class="flex-string wp20 gray2 fs-12 pt7 right-text">'.$fieldsNames['person']['mail'].':</div>
				<div class="flex-string wp80 pl10" id="vmail1">
					<INPUT name="person[mail]" type="text" class="wp93 '.$param['requered'].'" id="person[mail]" autocomplete="off" onMouseOut="$(\'#ospisok\').remove();" value="'.$person['mail'].'" data-url="content/helpers/person.helpers.php" data-action="valmail">
				</div>

			</div>';

			}
			elseif ( $input == 'loyalty' && $fieldsNames['person']['loyalty'] ) {

				$su = '';

				$result = $db -> query( "SELECT * FROM {$sqlname}loyal_cat WHERE identity = '$identity'" );
				while ($data = $db -> fetch( $result )) {

					$loyalDefault = ($person['loyalty'] > 0) ? $person['loyalty'] : $loyalDefault;

					$s1 = ($data['idcategory'] == $loyalDefault) ? "selected" : "";
					$su .= '<OPTION value="'.$data['idcategory'].'" '.$s1.'>'.$data['title'].'</OPTION>';

				}

				$s = '<div class="flex-container mb10">

				<div class="flex-string wp20 gray2 fs-12 pt7 right-text">'.$fieldsNames['person']['loyalty'].':</div>
				<div class="flex-string wp80 pl10">
					<select name="person[loyalty]" id="person[loyalty]" class="wp93 '.$param['requered'].'">
						<OPTION value="">--Выбор--</OPTION>
						'.$su.'
					</select>
				</div>

			</div>';

			}
			elseif ( $input == 'rol' && $fieldsNames['person']['rol'] ) {

				$s = '<div class="flex-container mb10">

					<div class="flex-string wp20 gray2 fs-12 pt7 right-text">'.$fieldsNames['person']['rol'].':</div>
					<div class="flex-string wp80 pl10">
						<input name="person[rol]" type="text" id="person[rol]" class="wp93 ac_input '.$param['requered'].'" value="" placeholder="Например: Принимает решение">
						<div class="smalltxt mt2 gray2 em">Например: Принимает решение</div>
					</div>

				</div>';

			}
			elseif ( stripos( $input, 'input' ) !== false ) {

				$re = $db -> query( "select * from {$sqlname}field where fld_tip='$tip' and fld_on='yes' and fld_name = '$input' and identity = '$identity' order by fld_order" );
				while ($da = $db -> fetch( $re )) {

					if ( $da['fld_temp'] == "textarea" ) {

						$s = '<div class="flex-container mb10">

							<div class="column12 grid-12">
								<div id="divider" class="red fs-09" align="center"><b>'.$da['fld_title'].'</b></div>
							</div>
	
						</div>
						<div class="flex-container mb10">
	
							<div class="flex-string wp100 pl10">
								<textarea name="'.$tip.'['.$da['fld_name'].']" rows="4" class="pad3 wp97 '.$param['requered'].'" id="'.$tip.'['.$da['fld_name'].']" data-req="'.$param['requered'].'">'.${$tip}[ $da['fld_name'] ].'</textarea><hr>
							</div>
	
						</div>';

					}
					elseif ( $da['fld_temp'] == "--Обычное--" ) {

						$s = '<div class="flex-container mb10">

						<div class="flex-string wp20 gray2 fs-12 pt7 right-text">'.$da['fld_title'].':</div>
						<div class="flex-string wp80 pl10">
							<INPUT name="'.$tip.'['.$da['fld_name'].']" type="text" id="'.$tip.'['.$da['fld_name'].']" class="wp93 '.$param['requered'].'" value="'.${$tip}[ $da['fld_name'] ].'" autocomplete="off" data-req="'.$param['requered'].'">
						</div>

					</div>';

					}
					elseif ( $da['fld_temp'] == "adres" ) {

						$s = '<div class="flex-container mb10">

						<div class="flex-string wp20 gray2 fs-12 pt7 right-text">'.$da['fld_title'].':</div>
						<div class="flex-string wp80 pl10">
							<div class="relativ"><INPUT name="'.$tip.'['.$da['fld_name'].']" type="text" id="'.$tip.'['.$da['fld_name'].']" class="wp93 '.$param['requered'].'" value="'.${$tip}[ $da['fld_name'] ].'" autocomplete="off" data-type="address" data-req="'.$param['requered'].'"></div>
						</div>

					</div>';

					}
					elseif ( $da['fld_temp'] == "select" ) {

						$vars = explode( ",", $da['fld_var'] );

						$su = '';

						foreach ($vars as $var) {

							$s  = ($var == ${$tip}[ $da['fld_name'] ]) ? 'selected' : '';
							$su .= '<option value="'.$var.'" '.$s.'>'.$var.'</option>';

						}

						$s = '<div class="flex-container mb10">

						<div class="flex-string wp20 gray2 fs-12 pt7 right-text">'.$da['fld_title'].':</div>
						<div class="flex-string wp80 pl10">
							<select name="'.$tip.'['.$da['fld_name'].']" class="wp93 '.$tip.'['.$param['requered'].']" id="'.$tip.'['.$da['fld_name'].']" data-req="'.$param['requered'].'">
								<option value="">--Выбор--</option>
								'.$su.'
							</select>
						</div>

					</div>';

					}
					elseif ( $da['fld_temp'] == "multiselect" ) {

						$vars = explode( ",", $da['fld_var'] );
						$sel  = explode( ", ", ${$tip}[ $da['fld_name'] ] );
						$su   = '';

						foreach ($vars as $var) {

							$s  = (in_array( $var, $sel )) ? 'selected' : '';
							$su .= '<option value="'.$var.'" '.$s.'>'.$var.'</option>';

						}

						$s = '<div class="flex-container mb10">

							<div class="column12 grid-12">
								<div id="divider" class="red fs-09" align="center"><b>'.$da['fld_title'].'</b></div>
							</div>
	
						</div>
						<div class="flex-container mb10 '.($da['fld_required'] == 'required' ? 'multireq' : '').'" data-reqq="'.($da['fld_required'] == 'required' ? 'multireq' : '').'">
	
							<div class="flex-string wp100 pl10">
								<select name="'.$tip.'['.$da['fld_name'].'][]" multiple="multiple" class="multiselect" id="'.$tip.'['.$da['fld_name'].'][]" data-req="'.$param['requered'].'">
									'.$su.'
								</select>
								<hr>
							</div>
	
						</div>';

					}
					elseif ( $da['fld_temp'] == "inputlist" ) {

						$vars = $da['fld_var'];

						$s = '<div class="flex-container mb10">

						<div class="flex-string wp20 gray2 fs-12 pt7 right-text">'.$da['fld_title'].':</div>
						<div class="flex-string wp80 pl10">
							<input type="text" name="'.$tip.'['.$da['fld_name'].']" id="'.$tip.'['.$da['fld_name'].']" class="wp93 '.$param['requered'].'" value="'.${$tip}[ $da['fld_name'] ].'" placeholder="'.$da['fld_title'].'" data-req="'.$param['requered'].'">
							<div class="smalltxt blue"><em>Двойной клик мышкой для показа вариантов</em></div>
							<script>
								var str = \''.$vars.'\';
								var data = str.split(\',\');
								$("#'.$tip.'\\\['.$da['fld_name'].'\\\]").autocomplete(data, {autoFill: true, minLength:0, minChars: 0, cacheLength: 5, max:50, selectFirst: true, multiple: false,  delay: 0, matchSubset: 2});
							</script>
						</div>

					</div>';

					}
					elseif ( $da['fld_temp'] == "radio" ) {

						$vars = explode( ",", $da['fld_var'] );
						$su   = '';

						foreach ($vars as $var) {

							$s1 = ($var == ${$tip}[ $da['fld_name'] ]) ? 'checked' : '';

							$su .= '
							<div class="flex-string p10 mr5 mb5 flx-basis-20 viewdiv bgwhite inset">
								<div class="radio">
									<label>
										<input name="'.$tip.'['.$da['fld_name'].']" type="radio" id="'.$tip.'['.$da['fld_name'].']" '.$s1.' value="'.$var.'" />
										<span class="custom-radio success1"><i class="icon-radio-check"></i></span>
										<span class="title">'.$var.'</span>
									</label>
								</div>
							</div>';

						}


						$s = '<div class="flex-container mb20 mt10">

							<div class="flex-string wp20 gray2 fs-12 pt7 right-text">'.$da['fld_title'].':</div>
							<div class="flex-string wp80 pl10 '.($da['fld_required'] == 'required' ? 'req' : '').'" data-req="'.($da['fld_required'] == 'required' ? 'req' : '').'">
							
								<div class="flex-container box--child wp93--5">
									'.$su.'
									'.($da['fld_required'] != 'required' ? '<div class="flex-string p10 mr5 mb5 flx-basis-20 viewdiv bgwhite inset">
									
											<div class="radio">
												<label>
													<input name="'.$da['fld_name'].'" type="radio" id="'.$da['fld_name'].'" '.(${$tip}[ $da['fld_name'] ] == '' ? 'checked' : '').' value="">
													<span class="custom-radio secondary"><i class="icon-radio-check"></i></span>
													<span class="title gray">Не выбрано</span>
												</label>
											</div>
										
										</div>' : '').'
								</div>
								
							</div>
	
						</div>';

					}
					elseif ( $da['fld_temp'] == "datum" ) {

						$s = '<div class="flex-container mb10">

						<div class="flex-string wp20 gray2 fs-12 pt7 right-text">'.$da['fld_title'].':</div>
						<div class="flex-string wp80 pl10">
							<INPUT name="'.$tip.'['.$da['fld_name'].']" type="text" id="'.$tip.'['.$da['fld_name'].']" class="datum wp30 '.$param['requered'].'" autocomplete="off" value="'.${$tip}[ $da['fld_name'] ].'">
						</div>

					</div>';

					}

				}

			}

			if ( $param['more'] != 'yes' ) {
				$string[$tip] .= $s;
			}
			else {
				$stringMore[$tip] .= $s;
			}

			b:

		}

	}

	?>

	<DIV class="zagolovok"><B>Добавить обращение</B></DIV>

	<?php
	$tcount = getOldTaskCount( (int)$iduser1 );
	if ( (int)$otherSettings['taskControl'] > 0 && (int)$otherSettings['taskControlClientAdd'] && (int)$tcount >= (int)$otherSettings['taskControl'] ) {

		print '<div class="warning"><b class="red">Включен режим контроля выполненения дел.</b><br>У вас '.$tcount.' не выполненных дел - вы не можете создавать новые напоминания и добавлять Клиентов и Контакты, пока не закроете старые напоминания.</div>';
		exit();

	}

	$req = "red";
	$rq  = '<b title="Обязательное поле" class="redd">*</b>';
	?>

	<FORM action="/modules/entry/core.entry.php" method="post" enctype="multipart/form-data" name="Form" id="Form">
		<INPUT type="hidden" id="action" name="action" value="edit">
		<INPUT type="hidden" id="ide" name="ide" value="<?= $ide ?>">
		<INPUT type="hidden" id="income" name="income" value="<?= $phone ?>">

		<div id="flyitbox"></div>
		<DIV id="formtabs" style="overflow-x: hidden; overflow-y:auto !important">

			<?php
			$hooks -> do_action( "entry_form_before", $_REQUEST );
			?>

			<div class="flex-container mt20 mb10 box--child">

				<div class="flex-string wp20 gray2 fs-12 pt7 right-text"><?= $fieldClient['iduser'] ?>:</div>
				<div class="flex-string wp80 pl10">
					<?php
					$element = new Elements();
					print $element -> UsersSelect( "iduser", [
						"class"  => [$fieldsRequire['client']['iduser']],
						"active" => true,
						"jsact"  => "setUser()",
						"sel"    => $iduser
					] );
					?>
				</div>

			</div>

			<?php //Клиент
			?>

			<div id="clientBoxEntry" class="fmainn box--child">

				<div class="flex-container mb10">

					<div class="flex-string wp100">
						<div id="divider" class="red">
							<b>Клиент</b><i class="icon-info-circled blue" title="Вы можете выбрать существующего клиента. Для этого начните набирать его название и выберите из найденных"></i>
						</div>
					</div>

				</div>
				<div class="flex-container mb10">

					<div class="flex-string wp20 gray2 fs-12 pt7 right-text">Тип:</div>
					<div class="flex-string wp80 pl10">
						<SELECT name="client[type]" id="client[type]" class="required typeselect" onchange="getOtrasli()">
							<OPTION value="client" <?php if ( !$otherSettings['clientIsPerson'] || $client['type'] == 'client' )
								print "selected" ?>>Клиент. Юр.лицо</OPTION>
							<OPTION value="person" <?php if ( $otherSettings['clientIsPerson'] || $client['type'] == 'person' )
								print "selected" ?>>Клиент. Физ.лицо</OPTION>
							<!--<OPTION value="partner">Партнер</OPTION>
							<OPTION value="contractor" <?php /*if ( $_REQUEST['tip'] == "other" )
								print "selected" */?>>Поставщик</OPTION>
							<OPTION value="concurent">Конкурент</OPTION>-->
						</SELECT>
					</div>

				</div>
				<div class="flex-container mb10">

					<div class="flex-string wp20 gray2 fs-12 pt7 right-text"></div>
					<div class="flex-string wp80 pl10">

					</div>

				</div>

				<?= $string['client'] ?>

				<?php if ( $stringMore['client'] != '' ) { ?>
					<div align="center" class="togglerbox smalltxt gray2 hand mb20" data-id="fullFilter" title="Показать/скрыть доп.фильтры">

						ещё поля...&nbsp;<i class="icon-angle-down" id="mapic"></i>

					</div>

					<div id="fullFilter" class="hidden box--child">

						<?= $stringMore['client'] ?>

					</div>
				<?php } ?>

			</div>

			<?php //Клиент
			?>

			<?php //Контакт
			?>

			<?php
			if ( !$otherSettings['hideContactFromExpress'] ) {
				?>
				<div id="contactBoxEntry" class="box--child <?php echo(!empty( $string['person'] ) ? "" : "hidden"); ?>">

					<div class="flex-container mb10">

						<div class="column12 grid-12">
							<div id="divider" class="red"><b>Контакт</b></div>
						</div>

					</div>

					<?= $string['person'] ?>

					<div class="flex-container mb10 mt20">

						<div class="flex-string wp20 gray2 fs-12 pt7 right-text"></div>
						<div class="flex-string wp80 pl10">
							<div class="checkbox">
								<label>
									<input name="mperson" type="checkbox" checked value="yes">
									<span class="custom-checkbox"><i class="icon-ok"></i></span>
									<span class="hidden-iphone">
										&nbsp;Установить основным контактом&nbsp;<i class="icon-info-circled blue" title="Если Контакт закреплен за Клиентом, то он станет основным контактом и будет показываться в карточке Клиента."></i>
									</span>
									<span class="visible-iphone">Основной контакт</span>
								</label>
							</div>
						</div>

					</div>

					<?php if ( $stringMore['person'] != '' ) { ?>
						<div align="center" class="togglerbox smalltxt gray2 hand mb20" data-id="fullFilterP" title="Показать/скрыть доп.фильтры">

							ещё поля...&nbsp;<i class="icon-angle-down" id="mapic"></i>

						</div>

						<div id="fullFilterP" class="hidden box--child">

							<?= $stringMore['person'] ?>

						</div>
					<?php } ?>

				</div>
			<?php } ?>

			<?php //Контакт?>

			<?php //Активность?>

			<?php if ( $ide < 1 ) { ?>
				<div id="divider"><b>Активность</b></div>

				<div class="flex-container mb10 mt20 box--child">

					<div class="flex-string wp20 gray2 fs-12 pt7 right-text">Тип активности:</div>
					<div class="flex-string wp80 pl10">
						<select name="tiphist" id="tiphist" class="required" data-change="activities" data-id="content">
							<?php
							$result = $db -> getAll( "SELECT * FROM {$sqlname}activities WHERE filter IN ('all','activ') and identity = '$identity' ORDER by aorder" );
							foreach ( $result as $data ) {

								//$s = ($data['id'] == $actDefault) ? "selected" : "";
								//print '<OPTION '.$s.' value="'.$data['title'].'" style="color:'.$data['color'].'">'.$data['title'].'</OPTION>';

								print '<option value="'.$data['title'].'" '.($data['id'] == $actDefault ? "selected" : "").' style="color:'.$data['color'].'" data-color="'.$data['color'].'" data-icon="'.get_ticon( $data['title'], '', true ).'">'.$data['title'].'</option>';

							}
							?>
						</select>&nbsp;<i class="icon-info-circled blue" title="В описание активности и обращения будет добавлен комментарий"></i>
					</div>

				</div>

				<div class="flex-container mb10 mt20 box--child">

					<div class="flex-string wp20 gray2 fs-12 pt7 right-text">Комментарий:</div>
					<div class="flex-string wp80 pl10">
						<textarea name="content" id="content" style="height:120px;" class="wp93"><?= $content ?></textarea>
						<div id="tagbox" class="gray1 fs-09 mt5" data-id="content" data-tip="tip"><br/>Начните с выбора
							<strong class="errorfont">типа активности</strong></div>
					</div>

				</div>

			<?php } ?>
			<?php //Активность?>

			<?php //Спецификация?>
			<?php if ( $did < 1 ) { ?>

				<div id="spekaBoxEntry" class="mt20" data-block="speca">

					<div id="divider"><b>Позиции запроса</b></div>

					<div id="speca">

						<table id="tbspeca" class="transparent">
							<thead>
							<tr>
								<th align="center">Продукт из каталога</th>
								<th width="100" align="center">Кол-во</th>
								<th width="150" align="center">Цена</th>
								<th width="30" align="center"></th>
							</tr>
							</thead>
							<tbody>
							<?php if ( !$ide ) { ?>
								<tr id="pr_0">
									<td>
										<input name="prid[]" id="prid[]" type="hidden" value=""><input name="idp[]" id="idp[]" type="hidden" value=""><input name="speca_title[]" type="text" id="speca_title[]" value="" style="width:98%" class="requered" placeholder="Начните вводить наименование"/>
									</td>
									<td align="center">
										<input name="speca_kol[]" id="speca_kol[]" type="text" value="1,00" style="width:70%" class="requered"/>
									</td>
									<td align="center">
										<input name="speca_price[]" id="speca_price[]" type="text" style="width:70%">
									</td>
									<td align="right">
										<a href="javascript:void(0)" onclick="prTRremove(0);"><i class="icon-cancel-circled red" title="Удалить"></i><span class="visible-iphone">Удалить</span></a>
									</td>
								</tr>
								<?php
								$co = 0;
							}
							else {

								$i      = 0;
								$result = $db -> getAll( "SELECT * FROM {$sqlname}entry_poz WHERE ide = '$ide' and identity = '$identity'" );
								foreach ( $result as $data ) {
									print '
									<tr id="pr_'.$i.'">
										<td><input name="prid[]" id="prid[]" type="hidden" value="'.$data['prid'].'"><input name="idp[]" id="idp[]" type="hidden" value="'.$data['idp'].'"><input name="speca_title[]" type="text" id="speca_title[]" value="'.$data['title'].'" style="width:98%" class="requered"/></td>
										<td align="center"><input name="speca_kol[]" id="speca_kol[]" type="text" value="'.num_format( $data['kol'] ).'" style="width:70%" class="requered"/></td>
										<td align="center"><input name="speca_price[]" id="speca_price[]" type="text" value="'.num_format( $data['price'] ).'" style="width:70%"/></td>
										<td align="right"><a href="javascript:void(0)" onclick="prTRremove('.$i.');"><i class="icon-cancel-circled red" title="Удалить"></i><span class="visible-iphone">Удалить</span></a></td>
									</tr>';
									$i++;
								}
								$co = $i;
							}
							?>
							</tbody>
						</table>
						<input type="hidden" name="spcount" id="spcount" value="<?= $co ?>">
						<br>
						<div align="right">
							<a href="javascript:void(0)" onclick="prTRclone2()" class="button"><span>Добавить поле</span></a>
						</div>

					</div>

				</div>
				<?php //Спецификация ?>

				<?php //Сделка ?>
				<?php if ( $ide < 1 ) { ?>
					<div id="divider" class="red mt20" align="center" data-block="deal">
						<b class="red">Создать <?= $lang['face']['DealName'][3] ?></b>
					</div>

					<div class="flex-container mb10 mt20 box--child" data-block="deal">

						<div class="flex-string wp20 gray2 fs-12 pt7 right-text"></div>
						<div class="flex-string wp80 pl10">

							<div class="checkbox mt10 mb20">
								<label>
									<input name="dodog" type="checkbox" id="dodog" value="yes">
									<span class="custom-checkbox"><i class="icon-ok"></i></span>
									&nbsp;Создать <?= $lang['face']['DealName'][3] ?>
								</label>
							</div>

						</div>

					</div>

					<div id="dogblock" class="hidden" data-block="deal">

						<div class="flex-container mb10 mt20 box--child">

							<div class="flex-string wp20 gray2 fs-12 pt7 right-text"><?= $lang['face']['DealName'][0] ?>:</div>
							<div class="flex-string wp80 pl10 relativ">
								<input type="text" name="dogovor[title]" id="dogovor[title]" value="<?= $title_dog ?>" placeholder="Название" class="wp93" data-req="required">
								<div class="idel paddright15">
									<i title="Очистить" onClick="$('#dogovor\\[title\\]').val('');" class="icon-block red hand"></i>
								</div>
								<div class="smalltxt gray2"><?= $lang['face']['DealName'][3] ?>. <?= $dnum ?></div>
							</div>

						</div>

						<div class="flex-container mb10 mt20 box--child">

							<div class="flex-string wp20 gray2 fs-12 pt7 right-text"><?= $fieldsNames['dogovor']['datum_plan'] ?>:</div>
							<div class="flex-string wp80 pl10 relativ">
								<input name="dogovor[datum_plan]" type="date" id="dogovor[datum_plan]" class="wp30" value="<?= $datum_plan ?>" maxlength="10" placeholder="Дата реализации" autocomplete="off" data-req="required">
							</div>

						</div>

						<div class="flex-container mb10 mt20 box--child">

							<div class="flex-string wp20 gray2 fs-12 pt7 right-text"><?= $fieldDeal['direction'] ?>:</div>
							<div class="flex-string wp80 pl10 relativ">
								<select name="dogovor[direction]" id="dogovor[direction]" class="wp93" data-req="<?= $fieldsRequire['dogovor']['direction'] ?>">
									<?php
									$resulttip = $db -> getAll( "SELECT * FROM {$sqlname}direction WHERE identity = '$identity' ORDER BY title" );
									foreach ( $resulttip as $data ) {

										$s = ($data['id'] == $dirDefault || $data['id'] == $direction) ? "selected" : "";
										print '<OPTION '.$s.' value="'.$data['id'].'">'.$data['title'].'</OPTION>';

									}
									?>
								</select>
							</div>

						</div>

						<div class="flex-container mb10 mt20 box--child">

							<div class="flex-string wp20 gray2 fs-12 pt7 right-text"><?= $fieldDeal['tip'] ?>:</div>
							<div class="flex-string wp80 pl10 relativ">
								<select name="dogovor[tip]" id="dogovor[tip]" class="wp93" data-req="<?= $fieldsRequire['dogovor']['tip'] ?>">
									<?php
									$resulttip = $db -> getAll( "SELECT * FROM {$sqlname}dogtips WHERE identity = '$identity' ORDER BY title" );
									foreach ( $resulttip as $data ) {

										$s = ($data['tid'] == $tipDefault) ? "selected" : "";
										print '<OPTION '.$s.' value="'.$data['tid'].'">'.$data['title'].'</OPTION>';

									}
									?>
								</select>
							</div>

						</div>

						<div class="flex-container mb10 mt20 box--child">

							<div class="flex-string wp20 gray2 fs-12 pt7 right-text"><?= $fieldDeal['idcategory'] ?>:</div>
							<div class="flex-string wp80 pl10 relativ">
								<?php
								$dfs     = $db -> getOne( "SELECT idcategory FROM {$sqlname}dogcategory WHERE title = '".$otherSettings['dealStepDefault']."' and identity = '$identity' ORDER BY title" );
								$resultt = $db -> getAll( "SELECT * FROM {$sqlname}dogcategory WHERE identity = '$identity' ORDER BY title" );
								?>
								<select name="dogovor[idcategory]" id="dogovor[idcategory]" class="wp93" data-req="<?= $fieldsRequire['dogovor']['idcategory'] ?>">
									<?php
									foreach ( $resultt as $data ) {
										$firstStep = ($otherSettings['dealStepDefault'] != '') ? $otherSettings['dealStepDefault'] : $dfs;
										$s         = ($data['idcategory'] == $firstStep) ? 'selected' : '';
										echo '<option value="'.$data['idcategory'].'" '.$s.'>'.$data['title'].'% - '.$data['content'].'</option>';
									}
									?>
								</select>
							</div>

						</div>

						<div class="flex-container mb10 mt20 box--child">

							<div class="flex-string wp20 gray2 fs-12 pt7 right-text"><?= $fieldsNames['dogovor']['mcid'] ?>:</div>
							<div class="flex-string wp80 pl10 relativ">
								<select name="dogovor[mcid]" id="dogovor[mcid]" class="<?= $fieldsRequire['dogovor']['mcid'] ?> wp93" title="Укажите, от какой Вашей компании совершается сделка">
									<?php
									$result = $db -> query( "SELECT * FROM {$sqlname}mycomps WHERE identity = '$identity' ORDER BY name_shot" );
									while ($data = $db -> fetch( $result )) {

										$s = ($data['id'] == $mcDefault) ? "selected" : "";
										print '<option value="'.$data['id'].'" '.$s.'>'.$data['name_shot'].'</option>';

									}
									?>
								</select>
							</div>

						</div>

						<?php
						if ( $fieldDeal['adres'] ) {
							?>
							<div class="flex-container mb10 mt20 box--child">

								<div class="flex-string wp20 gray2 fs-12 pt7 right-text"><?= $fieldDeal['adres'] ?>:</div>
								<div class="flex-string wp80 pl10 relativ">
									<input name="dogovor[adres]" type="text" id="dogovor[adres]" class="wp93" value="" placeholder="<?= $fieldDeal['adres'] ?>" autocomplete="on" data-req="<?= $fieldsRequire['dogovor']['adres'] ?>" data-type="address">
								</div>

							</div>

						<?php } ?>

						<div class="flex-container mb10 mt20 box--child">

							<div class="flex-string wp20 gray2 fs-12 pt7 right-text"><?= $fieldDeal['content'] ?>:</div>
							<div class="flex-string wp80 pl10 relativ">
								<textarea name="dogovor[content]" id="dogovor[content]" style="height: 100px;" class="wp93" data-req="<?= $fieldsRequire['dogovor']['content'] ?>"><?= trim( $content ) ?></textarea>
							</div>

						</div>

						<?php
						$res = $db -> getAll( "select * from {$sqlname}field where fld_tip='dogovor' and fld_name LIKE '%input%' and fld_on='yes' and identity = '$identity' order by fld_order" );
						foreach ( $res as $da ) {

							if ( $da['fld_temp'] == "--Обычное--" ) {
								?>

								<div class="flex-container mb10 mt20 box--child">

									<div class="flex-string wp20 gray2 fs-12 pt7 right-text"><?= $da['fld_title'] ?>:</div>
									<div class="flex-string wp80 pl10 relativ">
										<input type="text" name="dogovor[<?= $da['fld_name'] ?>]" id="dogovor[<?= $da['fld_name'] ?>]" class="wp93" value="" placeholder="<?= $da['fld_title'] ?>" data-req="<?= $da['fld_required'] ?>">
									</div>

								</div>

								<?php
							}
							elseif ( $da['fld_temp'] == "adres" ) {
								?>

								<div class="flex-container mb10 mt20 box--child">

									<div class="flex-string wp20 gray2 fs-12 pt7 right-text"><?= $da['fld_title'] ?>:</div>
									<div class="flex-string wp80 pl10 relativ">
										<input type="text" name="dogovor[<?= $da['fld_name'] ?>]" id="dogovor[<?= $da['fld_name'] ?>]" class="wp93 yaddress" value="" placeholder="<?= $da['fld_title'] ?>" data-req="<?= $da['fld_required'] ?>" data-type="address">
									</div>

								</div>

								<?php
							}
							elseif ( $da['fld_temp'] == "textarea" ) {

								$fieldData = $da['fld_var'];
								?>

								<div class="flex-container mb10 mt20 box--child">

									<div class="flex-string wp20 gray2 fs-12 pt7 right-text"><?= $da['fld_title'] ?>:</div>
									<div class="flex-string wp80 pl10 relativ">
										<textarea name="dogovor[<?= $da['fld_name'] ?>]" id="dogovor[<?= $da['fld_name'] ?>]" class="wp93" style="height: 150px;" placeholder="<?= $da['fld_title'] ?>" data-req="<?= $da['fld_required'] ?>"><?= str_replace( "<br>", "\n", $fieldData ) ?></textarea>
									</div>

								</div>

								<?php
							}
							elseif ( $da['fld_temp'] == "select" ) {

								$vars = explode( ",", $da['fld_var'] );
								?>

								<div class="flex-container mb10 mt20 box--child">

									<div class="flex-string wp20 gray2 fs-12 pt7 right-text"><?= $da['fld_title'] ?>:</div>
									<div class="flex-string wp80 pl10 relativ">
										<select name="dogovor[<?= $da['fld_name'] ?>]" id="dogovor[<?= $da['fld_name'] ?>]" class="wp93" data-req="<?= $da['fld_required'] ?>">
											<option value="">--Выбор--</option>
											<?php
											foreach ( $vars as $var ) {
												?>
												<option value="<?= $var ?>"><?= $var ?></option>
											<?php } ?>
										</select>
									</div>

								</div>

								<?php
							}
							elseif ( $da['fld_temp'] == "multiselect" ) {

								$vars = explode( ",", $da['fld_var'] );
								?>

								<div id="divider" align="center"><b><?= $da['fld_title'] ?></b></div>

								<div class="flex-container mb10 mt20 box--child" data-req="<?= ($da['fld_required'] == 'required' ? 'multireq' : '') ?>">

									<div class="flex-string wp100 pl10">
										<select name="dogovor[<?= $da['fld_name'] ?>][]" id="dogovor[<?= $da['fld_name'] ?>][]" multiple="multiple" class="multiselect" style="width: 98.5%;">
											<?php
											foreach ( $vars as $var ) {
												?>
												<option value="<?= $var ?>"><?= $var ?></option>
											<?php } ?>
										</select>
									</div>

								</div>

								<hr>
								<?php
							}
							elseif ( $da['fld_temp'] == "inputlist" ) {

								$vars = $da['fld_var'];
								?>

								<div class="flex-container mb10 mt20 box--child">

									<div class="flex-string wp20 gray2 fs-12 pt7 right-text"><?= $da['fld_title'] ?>:</div>
									<div class="flex-string wp80 pl10 relativ">
										<input type="text" name="dogovor[<?= $da['fld_name'] ?>]" id="dogovor[<?= $da['fld_name'] ?>]" class="wp93" value="<?= $fieldData ?>" placeholder="<?= $da['fld_title'] ?>" data-req="<?= $da['fld_required'] ?>"/>
										<div class="smalltxt blue"><em>Двойной клик мышкой для показа вариантов</em>
										</div>
										<script>
											var str = '<?=$vars?>';
											var data = str.split(',');
											$("#dogovor\\[<?=$da['fld_name']?>\\]").autocomplete(data, {
												autofFll: true,
												minLength: 0,
												minChars: 0,
												cacheLength: 5,
												max: 50,
												selectFirst: true,
												multiple: false,
												delay: 0,
												matchSubset: 2
											});
										</script>
									</div>

								</div>
								<?php
							}
							elseif ( $da['fld_temp'] == "radio" ) {

								$vars = explode( ",", $da['fld_var'] );
								?>

								<div class="flex-container mb10 mt20 box--child" data-req="<?= ($da['fld_required'] == 'required' ? 'req' : '') ?>">

									<div class="flex-string wp20 gray2 fs-12 pt7 right-text"><?= $da['fld_title'] ?>:</div>
									<div class="flex-string wp80 pl10 relativ">

										<div class="flex-container box--child wp93--5">

											<?php
											foreach ( $vars as $var ) {
												?>
												<div class="flex-string p10 mr5 mb5 flx-basis-20 viewdiv bgwhite inset">

													<div class="radio">
														<label>
															<input name="dogovor[<?= $da['fld_name'] ?>]" type="radio" id="dogovor[<?= $da['fld_name'] ?>]" <?= $s ?> value="<?= $var ?>"/>
															<span class="custom-radio success1"><i class="icon-radio-check"></i></span>
															<span class="title"><?= $var ?></span>
														</label>
													</div>

												</div>
											<?php } ?>
											<?php if ( $da['fld_required'] != 'required' ) { ?>
												<div class="flex-string p10 mr5 mb5 flx-basis-20 viewdiv bgwhite inset">

													<div class="radio">
														<label>
															<input name="<?= $da['fld_name'] ?>" type="radio" id="<?= $da['fld_name'] ?>" checked value="">
															<span class="custom-radio secondary"><i class="icon-radio-check"></i></span>
															<span class="title gray">Не выбрано</span>
														</label>
													</div>

												</div>
											<?php } ?>

										</div>

									</div>

								</div>
								<?php
							}
							elseif ( $da['fld_temp'] == "datum" ) {
								?>

								<div class="flex-container mb10 mt20 box--child">

									<div class="flex-string wp20 gray2 fs-12 pt7 right-text"><?= $da['fld_title'] ?>:</div>
									<div class="flex-string wp80 pl10 relativ">
										<INPUT name="dogovor[<?= $da['fld_name'] ?>]" type="text" id="dogovor[<?= $da['fld_name'] ?>]" class="datum wp30" data-req="<?= $da['fld_required'] ?>" value="<?= $dogovor[ $da['fld_name'] ] ?>" autocomplete="off">
									</div>

								</div>
								<?php
							}
							elseif ( $da['fld_temp'] == "datetime" ) {
								?>

								<div class="flex-container mb10 mt20 box--child">

									<div class="flex-string wp20 gray2 fs-12 pt7 right-text"><?= $da['fld_title'] ?>:</div>
									<div class="flex-string wp80 pl10 relativ">
										<INPUT name="dogovor[<?= $da['fld_name'] ?>]" type="date" id="dogovor[<?= $da['fld_name'] ?>]" class="inputdatetime" style="width: 30%;" value="<?= $dogovor[ $da['fld_name'] ] ?>" autocomplete="off" placeholder="<?= $da['fld_title'] ?>" data-req="<?= $da['fld_required'] ?>">
									</div>

								</div>
								<?php
							}

						}
						?>

					</div>
					<?php //Сделка ?>
				<?php } ?>

			<?php } ?>

			<?php if ( $ide < 1 ) { ?>

				<?php //Напоминание ?>

				<div class="flex-container mt20 mb20">

					<div class="flex-string wp100">
						<div id="divider" class="red">
							<b class="blue">Добавить напоминание</b>
						</div>
					</div>

				</div>

				<?php
				$tcount = getOldTaskCount( (int)$iduser1 );
				if ( (int)$otherSettings['taskControl'] > 0 && (int)$tcount >= (int)$otherSettings['taskControl'] ) {

					print '<div class="warning"><b class="red">Включен режим контроля выполненения дел.</b><br>У вас '.$tcount.' не выполненных дел - вы не можете создавать новые напоминания, пока не закроете старые.</div>';

				}
				else {
					?>
					<div id="todoBoxExpress">

						<div class="flex-container box--child mt10">

							<div class="flex-string wp20 gray2 fs-12 pt7 right-text">Тема:</div>
							<div class="flex-string wp80 pl10">
								<INPUT name="todo[theme]" id="todo[theme]" type="text" value="<?= $title ?>" placeholder="Укажите тему напоминания" class="wp97">
								<div class="em gray2 fs-09">Например: <b>Договориться о встрече</b></div>
							</div>

						</div>

						<hr>

						<div class="flex-container box--child mt10">

							<div class="flex-string wp20 gray2 fs-12 pt7 right-text">К исполнению:</div>
							<div class="flex-string wp80 pl10 relativ">

								<input name="todo[datumtime]" type="text" class="inputdatetime required" id="todo[datumtime]" value="<?= $thistime ?>" onclick="$('.datumTasksView').empty().hide()" onchange="getDateTasksNew('todo\\[datumtime\\]')" autocomplete="off">

								<div class="datumTasks hand tagsmenuToggler p10">
									Число дел: <span class="taskcount Bold">0</span>
									<div class="tagsmenu left hidden">
										<div class="blok"></div>
									</div>
								</div>
								<div class="datumTasksView" onblur="$('.datumTasksView').hide()"></div>

							</div>

						</div>

						<div class="flex-container box--child mt10 infodiv bgwhite">

							<div class="flex-string wp20 gray2 fs-12 pt7 right-text">Опции:</div>
							<div class="flex-string wp80 pt7 pl10 fs-11">

								<div class="mb10 pl10">

									<label for="todo[day]" class="switch">
										<input type="checkbox" name="todo[day]" id="todo[day]" value="yes">
										<span class="slider empty"></span>
									</label>
									<label for="todo[day]" class="inline">&nbsp;Весь день&nbsp;<i class="icon-info-circled blue" title="Включите, чтобы напоминание не было привязано к времени"></i></label>

								</div>

								<div class="mb10 pl10">

									<label for="todo[readonly]" class="switch">
										<input type="checkbox" name="todo[readonly]" id="todo[readonly]" value="yes">
										<span class="slider empty"></span>
									</label>
									<label for="todo[readonly]" class="inline">&nbsp;Только чтение&nbsp;<i class="icon-info-circled blue" title="Включите, чтобы не ставить отметку о выполнении"></i></label>

								</div>

								<div class="mb10 pl10">

									<label for="todo[alert]" class="switch">
										<input type="checkbox" name="todo[alert]" id="todo[alert]" value="yes" <?php if ( $alert == 'no' || $usersettings['taskAlarm'] == 'yes' )
											print "checked"; ?>>
										<span class="slider empty"></span>
									</label>
									<label for="todo[alert]" class="inline">&nbsp;Напоминать&nbsp;<i class="icon-info-circled blue" title="Если включено, то будет показано всплывающее окно"></i></label>

								</div>

							</div>

						</div>

						<div class="flex-container box--child mt10">

							<div class="flex-string wp20 gray2 fs-12 pt7 right-text">Исполнитель</div>
							<div class="flex-string wp80 pl10">

								<?php
								$element = new Elements();
								print $element -> UsersSelect( "todo[touser]", [
									"class"   => ['wp97'],
									"active"  => true,
									"sel"     => $iduser1,
									"noempty" => true
								] );
								?>

							</div>

						</div>

						<div class="flex-container box--child mt10">

							<div class="flex-string wp20 gray2 fs-12 pt7 right-text">Тип напоминания:</div>
							<div class="flex-string wp80 pl10">

								<select name="todo[tip]" id="todo[tip]" class="wp97 required" data-change="activities" data-id="todo[des]">
									<?php
									$res = $db -> getAll( "SELECT * FROM {$sqlname}activities WHERE filter IN ('all','task') and identity = '$identity' ORDER by aorder" );
									foreach ( $res as $data ) {

										//$s = ($data['id'] == $actDefault) ? "selected" : "";
										//print '<option value="'.$data['title'].'" '.$s.' style="color:'.$data['color'].'">'.$data['title'].'</option>';

										print '<option value="'.$data['title'].'" '.($data['id'] == $actDefault ? "selected" : "").' style="color:'.$data['color'].'" data-color="'.$data['color'].'" data-icon="'.get_ticon( $data['title'], '', true ).'">'.$data['title'].'</option>';

									}
									?>
								</select>

							</div>

						</div>

						<div class="flex-container box--child mt10">

							<div class="flex-string wp20 gray2 fs-12 pt7 right-text">Срочность:</div>
							<div class="flex-string wp80 pl10">

								<div class="like-input wp97">

									<div id="psdiv" class="speed">

										<input type="hidden" id="todo[speed]" name="todo[speed]" value="0" data-id="speed">
										<div class="but black w100 text-center" id="sp1" title="Не срочно" onClick="setPS('speed','1')">
											<i class="icon-down-big"></i>&nbsp;Не срочно
										</div>
										<div class="but black active w100 text-center" id="sp0" title="Обычно" onClick="setPS('speed','0')">
											<i class="icon-check-empty"></i>&nbsp;Обычно
										</div>
										<div class="but black w100 text-center" id="sp2" title="Срочно" onClick="setPS('speed','2')">
											<i class="icon-up-big"></i>&nbsp;Срочно
										</div>

									</div>

								</div>

							</div>

						</div>

						<div class="flex-container box--child mt10">

							<div class="flex-string wp20 gray2 fs-12 pt7 right-text">Важность:</div>
							<div class="flex-string wp80 pl10">

								<div class="like-input wp97">

									<div id="psdiv" class="priority">

										<input type="hidden" id="todo[priority]" name="todo[priority]" value="0" data-id="priority">
										<div class="but black w100 text-center" id="pr1" title="Не важно" onClick="setPS('priority','1')">
											<i class="icon-down-big"></i>&nbsp;Не важно
										</div>
										<div class="but black active w100 text-center" id="pr0" title="Обычно" onClick="setPS('priority','0')">
											<i class="icon-check-empty"></i>&nbsp;Обычно
										</div>
										<div class="but black w100 text-center" id="pr2" title="Важно" onClick="setPS('priority','2')">
											<i class="icon-up-big"></i>&nbsp;Важно
										</div>

									</div>

								</div>

							</div>

						</div>

						<hr>

						<div class="flex-container box--child mt10 mb20">

							<div class="flex-string wp20 gray2 fs-12 pt7 right-text">Агенда:</div>
							<div class="flex-string wp80 pl10 relativ">
								<a href="javascript:void(0)" onClick="copydes();" title="скопировать из активности" class="blue pull-right mr20 mt5"><i class="icon-docs"></i></a>
								<textarea name="todo[des]" id="todo[des]" rows="4" class="required1 wp97 pr20" style="height:120px;" placeholder="Здесь можно указать детали напоминания - что именно надо сделать?"><?= $des ?></textarea>
								<div id="tagbox" class="gray1 fs-09 mt5" data-id="todo[des]" data-tip="tips"></div>
							</div>

						</div>

					</div>
				<?php } ?>

				<?php //Напоминание ?>

			<?php } ?>

		</DIV>

		<hr>

		<div class="button--pane text-right">

			<A href="javascript:void(0)" onClick="checkTask()" class="button">Сохранить</A>&nbsp;
			<A href="javascript:void(0)" onClick="DClose()" class="button">Отмена</A>

		</div>

	</FORM>
	<?php
	$hooks -> do_action( "entry_form_after", $_REQUEST );

}
if ( $action == "status" ) {

	$id = $_REQUEST['id'];

	$res = $db -> getRow( "SELECT * FROM {$sqlname}entry WHERE ide = '$id' and identity = '$identity'" );

	$t = getDateTimeArray( current_datumtime() );

	$datum_do = $t['Y']."-".$t['m'].'-'.$t['d'].' '.$t['H'].':'.$t['i'];

	?>
	<DIV class="zagolovok">Изменение статуса</DIV>
	<FORM action="/modules/entry/core.entry.php" method="post" enctype="multipart/form-data" name="Form" id="Form">
		<INPUT type="hidden" name="action" id="action" value="status">
		<INPUT type="hidden" name="id" id="id" value="<?= $id ?>">

		<div id="formtabs" class="box--child" style="overflow-y: auto; overflow-x: hidden">

			<div class="flex-container box--child mt10 mb10">

				<div class="flex-string wp20 gray2 fs-12 pt7 right-text">Cтатус:</div>
				<div class="flex-string wp80 pl10 relativ">
					<select name="status" id="status" class="required wp97">
						<option value="none">--Выбор--</option>
						<option disabled value="0">Новый</option>
						<option disabled value="1">Выполнен</option>
						<option disabled="disabled">-------------------</option>
						<option selected value="2">Отменен</option>
					</select>
					<div class="mmt-10 mblock">&nbsp;Текущий:
						<b class="<?= strtr( $res['status'], $colors ) ?>"><?= strtr( $res['status'], $status ) ?></b>
					</div>
				</div>

			</div>

			<hr>

			<div class="flex-container box--child greenbg-sub">

				<div class="flex-string wp20 gray2 fs-12 pt7 right-text">Дата.Факт:</div>
				<div class="flex-string wp80 pl10 relativ">
					<input type="text" name="datum_do" id="datum_do" value="<?= $datum_do ?>" class="yw160 inputdatetime">
				</div>

			</div>

			<div class="flex-container box--child mb10 greenbg-sub">

				<div class="flex-string wp20 gray2 fs-12 pt7 right-text">Комментарий:</div>
				<div class="flex-string wp80 pl10 relativ">
					<textarea name="content" id="content" class="wp97"><?= $res['content'] ?></textarea>
				</div>

			</div>

		</div>

		<hr>

		<div class="pull-aright button--pane">

			<A href="javascript:void(0)" onClick="editDogovor('<?= $ide ?>','fromentry');" class="button orangebtn">Преобразовать в <?= $lang['face']['DealName'][3] ?></A>&nbsp;или&nbsp;&nbsp;

			<A href="javascript:void(0)" onClick="$('#Form').submit()" class="button redbtn">Закрыть обращение</A>&nbsp;
			<A href="javascript:void(0)" onClick="DClose()" class="button">Отмена</A>

		</div>
	</FORM>
	<?php

}
?>
<script type="text/javascript" src="/assets/js/smSelect.js"></script>
<script type="text/javascript" src="/assets/js/app.form.js"></script>
<script>

	var origphone = '<?=$phone?>';

	var action = $('#action').val();
	var formatPhone = '<?=$format_phone?>';
	var $timecheck = <?=$timecheck?>;

	var origDateTime = $('#todo\\[datumtime\\]').val();
	var origTip = $('#todo\\[tip\\] option:selected').text();

	if (!isMobile) {

		var hh = $('#dialog_container').actual('height') * 0.9;
		var hh2 = hh - $('.zagolovok').actual('outerHeight') - $('.button--pane').actual('outerHeight') - 30;

		$("#person\\[person\\]").focus();

		if ($(window).width() > 990) $('#formtabs').css({'height': 'unset', 'max-height': hh2 + "px"});
		else $('#formtabs').css({'height': 'unset', 'max-height': hh2 + "px"});

		$('#dialog').css('width', '800px');
		$(".multiselect").multiselect({sortable: true, searchable: true});

		$('#tiphist').smSelect({
			text: "",
			width: "p93",
			height: "300px",
			icon: "",
			class: "p51 like-input inline",
			fly: true,
			id: "tip"
		});

		$('#todo\\[tip\\]').smSelect({
			text: "",
			width: "p97",
			height: "250px",
			icon: "",
			class: "p51 like-input",
			fly: true,
			id: "tips"
		});

	}
	else {

		var h2 = $(window).height() - $('.zagolovok').actual('outerHeight') - $('.button--pane').actual('outerHeight') - 30;
		$('#formtabs').css({'max-height': h2 + 'px', 'height': h2 + 'px'});

		$(".multiselect").addClass('wp97 h0');

		if (isMobile) $('table').rtResponsiveTables();

	}

	if (in_array(action, ['edit'])) {

		var mFunnel = JSON.parse('<?=$mFunnel?>');
		if (Object.keys(mFunnel).length > 0) {

			$('#dogovor\\[tip\\]').bind('change', function () {

				var tip = $('#dogovor\\[tip\\] option:selected').val();
				var direction = $('#dogovor\\[direction\\] option:selected').val();

				//console.log(direction);

				if (parseInt(direction) > 0) {

					var steps = mFunnel[direction][tip]['nsteps'];
					var def = mFunnel[direction][tip]['default'];
					var str = '';
					var $s;

					for (var i in steps) {

						$s = (steps[i].id == def) ? "selected" : "";

						str += '<option value="' + steps[i].id + '" ' + $s + '>' + steps[i].name + '% - ' + steps[i].content + '</option>';

					}

					//console.log(str);

					$('#dogovor\\[idcategory\\]').html(str);

				}

			});

			$('#dogovor\\[direction\\]').bind('change', function () {

				$('#dogovor\\[tip\\]').trigger('change');

			});

			$('#dogovor\\[tip\\]').trigger('change');

		}

	}

	$(function () {

		//getOtrasli();

		<?php
		//меняем блоки Клиент / Контакт в запвисимости от того с кем работает оператор
		/*if($otherSettings['clientIsPerson']){
			print "$('#contactBoxEntry').insertAfter('#clientBoxEntry');";
		}
		else print "$('#clientBoxEntry').insertAfter('#contactBoxEntry');";*/
		?>
		$('#clientBoxEntry').insertAfter('#contactBoxEntry');

		//Формат номеров телефонов
		if (formatPhone !== '') reloadMasks();

		if (!isMobile) {

			$('.inputdatetime').each(function () {

				var date = new Date();

				$(this).datetimepicker({
					timeInput: false,
					timeFormat: 'HH:mm',
					oneLine: true,
					showSecond: false,
					showMillisec: false,
					showButtonPanel: true,
					timeOnlyTitle: 'Выберите время',
					timeText: 'Время',
					hourText: 'Часы',
					minuteText: 'Минуты',
					secondText: 'Секунды',
					millisecText: 'Миллисекунды',
					timezoneText: 'Часовой пояс',
					currentText: 'Текущее',
					stepMinute: 5,
					closeText: '<i class="icon-ok-circled"></i>',
					dateFormat: 'yy-mm-dd',
					firstDay: 1,
					dayNamesMin: ['Вс', 'Пн', 'Вт', 'Ср', 'Чт', 'Пт', 'Сб'],
					monthNamesShort: ['Январь', 'Февраль', 'Март', 'Апрель', 'Май', 'Июнь', 'Июль', 'Август', 'Сентябрь', 'Октябрь', 'Ноябрь', 'Декабрь'],
					changeMonth: true,
					changeYear: true,
					yearRange: date.getFullYear() + ':' + (date.getFullYear() + 5),
					minDate: new Date(date.getFullYear(), date.getMonth(), date.getDate())
				});

			});
			$('.datum').datepicker({
				dateFormat: "yy-mm-dd",
				firstDay: 1,
				changeMonth: true,
				changeYear: true,
				numberOfMonths: 2
			});
			$("#dogovor\\[datum\\]").datepicker({
				dateFormat: "yy-mm-dd",
				firstDay: 1,
				changeMonth: true,
				changeYear: true,
				numberOfMonths: 2
			});
			$("#dogovor\\[datum_plan\\]").datepicker({
				dateFormat: "yy-mm-dd",
				firstDay: 1,
				changeMonth: true,
				changeYear: true,
				numberOfMonths: 2
			});
			$("#datum_task").datepicker({
				dateFormat: 'yy-mm-dd',
				firstDay: 1,
				dayNamesMin: ['Вс', 'Пн', 'Вт', 'Ср', 'Чт', 'Пт', 'Сб'],
				monthNamesShort: ['Январь', 'Февраль', 'Март', 'Апрель', 'Май', 'Июнь', 'Июль', 'Август', 'Сентябрь', 'Октябрь', 'Ноябрь', 'Декабрь'],
				changeMonth: true,
				changeYear: true
			});
			$('#totime_task').ptTimeSelect();

		}

		$("#dogovor\\[title\\]").autocomplete("/content/card/deal.helpers.php?action=get.list", {
			autofill: true,
			minChars: 3,
			cacheLength: 1,
			maxItemsToShow: 20,
			selectFirst: false,
			multiple: false,
			delay: 10,
			matchSubset: 1
		});
		$('#dogovor\\[kol\\]').setMask({mask: '<?=$format_dogs?>', type: 'reverse'});
		$("#person\\[rol\\]").autocomplete("/content/helpers/person.helpers.php?action=get.role", {
			autofill: false,
			minChars: 3,
			cacheLength: 1,
			maxItemsToShow: 20,
			selectFirst: true,
			multiple: true,
			multipleSeparator: "; ",
			delay: 10
		});

		if( $("#person\\[ptitle\\]").hasClass('suggestion') ) {
			$("#person\\[ptitle\\]").autocomplete("/content/helpers/person.helpers.php?action=get.status", {
				autofill: true,
				minChars: 3,
				cacheLength: 1,
				maxItemsToShow: 20,
				selectFirst: false,
				multiple: false,
				delay: 10,
				matchSubset: 1
			});
		}

		$("#todo\\[theme\\]").autocomplete("/content/core/core.tasks.php?action=theme", {
			autoFill: false,
			minChars: 3,
			cacheLength: 1,
			max: 100,
			selectFirst: false,
			multiple: false,
			delay: 10,
			matchSubset: 3,
			matchContains: true
		});

		$("#pr_0 #speca_title\\[\\]").autocomplete("/content/helpers/price.helpers.php?clid=" + $('#clid').val(), {
			autofill: true,
			minChars: 2,
			cacheLength: 1,
			maxItemsToShow: 100,
			max: 100,
			selectFirst: false,
			multiple: false,
			delay: 100,
			matchSubset: 1,
			formatItem: function (data, j, n, value) {

				var str = '';

				if (parseFloat(data[8]) != '') str = '<div class="gray">На складах: ' + data[7] + ', в т.ч. в резерве ' + data[9] + ' </div>';

				return '<div onclick="selItem2(\'0\',\'' + data[1] + '\')"><b>' + data[5] + ':</b> ' + data[0] + str + '</div>';
			},
			formatResult: function (data) {
				return data[0];
			}
		});
		$("#pr_0 #speca_title\\[\\]").result(function (value, data) {
			selItem2('0', data[1])
		});

		<?php
		$i = 0;
		$result = $db -> query( "SELECT * FROM {$sqlname}entry_poz WHERE ide = '$ide' and identity = '$identity'" );
		while($data = $db -> fetch( $result )) {
		?>
		$("#pr_<?=$i?> #speca_title\\[\\]").autocomplete("/content/helpers/price.helpers.php?clid=" + $('#clid').val(), {
			autofill: true,
			minChars: 2,
			cacheLength: 1,
			maxItemsToShow: 100,
			max: 100,
			selectFirst: false,
			multiple: false,
			delay: 10,
			matchSubset: 1,
			formatItem: function (data, j, n, value) {
				return '<div onclick="selItem2(\'<?=$i?>\',\'' + data[1] + '\')">' + data[0] + '</div>';
			},
			formatResult: function (data) {
				return data[0];
			}
		});
		$("#pr_<?=$i?> #speca_title\\[\\]").result(function (value, data) {
			selItem2('<?=$i?>', data[1])
		});
		<?php
		$i++;
		}
		?>
		$('.ac_results').css('width', '200px');

		$("#client\\[title\\]").autocomplete("/content/helpers/client.helpers.php?action=clientlist", {
			autofill: true,
			minChars: 2,
			cacheLength: 2,
			maxItemsToShow: 10,
			selectFirst: false,
			multiple: false,
			delay: 10,
			matchSubset: 1,
			formatItem: function (data, i, n, value) {
				return '<div id="selitemid-' + data[1] + '" data-clid="' + data[1] + '">' + data[0] + '&nbsp;[<span class="red">' + data[2] + '</span>]</div>';
			},
			formatResult: function (data) {
				return data[0];
			}
		});
		$("#client\\[title\\]").result(function (value, data) {
			selItem('client', data[1])
		});

		$("#person\\[person\\]").autocomplete("/content/helpers/client.helpers.php?action=contactlist&clid=" + $("#client\\[clid\\]").val(), {
			autofill: true,
			minChars: 2,
			cacheLength: 2,
			maxItemsToShow: 10,
			selectFirst: false,
			multiple: false,
			delay: 10,
			matchSubset: 1,
			formatItem: function (data, i, n, value) {
				return '<div class="relativ">' + data[0] + '&nbsp;<div class="pull-aright">[<span class="broun">' + data[2] + '</span>]</div><br><div class="blue smalltxt">' + data[3] + '</div></div>';
			},
			formatResult: function (data) {
				return data[0];
			}
		});
		$("#person\\[person\\]").result(function (value, data) {
			selItem('person', data[1])
		});

		getDateTasksNew('todo\\[datumtime\\]');

		$('#Form').ajaxForm({
			dataType: 'json',
			beforeSubmit: function () {

				//если сделка не создается, то все обязательные поля делаем не обязательными
				if ($('#dodog').prop('checked') && $('#dogovor').val() !== '') {

					$('#dogblock').find('[data-req="required"]').addClass('required');

				}
				else $('#dogblock').find('[data-req="required"]').removeClass('required');

				var $out = $('#message');
				var em = checkRequired();

				if (em === false) return false;

				$out.empty().fadeTo(1, 1).css('display', 'block').append('<div id="loader"><img src="/assets/images/loader.gif"> Загрузка данных. Пожалуйста подождите...</div>');

				$('#dialog').css('display', 'none');
				$('#dialog_container').css('display', 'none');

				return true;

			},
			success: function (data) {

				var iscard = '';
				var er = '';
				var ide = parseInt($('#ide').val());

				if ($('#isCard').is('input')) iscard = $('#isCard').val();

				$('#dialog_container').css('display', 'none');
				$('#dialog').css('display', 'none');

				if (data.err !== '') er = '<br>Ошибки: ' + data.err;

				//открое карточку только если это новое обращение
				//а не редактирование
				if (ide === 0) {

					if (data.clid !== '' && iscard !== 'yes' && !$('.expressbuttons').is('div')) window.open('card.client?clid=' + data.clid);
					if (data.clid !== '' && $('.expressbuttons').is('div')) window.location = 'card.client?clid=' + data.clid;

				}

				$('#message').fadeTo(1, 1).css('display', 'block').html('Результат: ' + data.mess + er);
				setTimeout(function () {
					$('#message').fadeTo(1000, 0);
				}, 20000);

				if (typeof configpage == 'function') configpage();

				if ($("#todo\\[theme\\]").val() !== '') {

					if (typeof cardload == 'function') cardload();
					if (typeof changeMounth == 'function') changeMounth();

				}
			}
		});

		$('#dialog').center();

		if (action === 'edit') doLoadCallback('editEntry');
		else if (action === 'status') doLoadCallback('statusEntry');

		if (action === 'edit') ShowModal.fire({
			etype: 'editEntry',
			action: action
		});
		if (action === 'status') ShowModal.fire({
			etype: 'statusEntry',
			action: action
		});

	});

	/**
	 * Управление тэгами
	 */
	$('select[data-change="activities"]').each(function () {

		var $el = $(this).data('id');
		$('#tagbox[data-id="' + $el + '"]').empty().load('/content/core/core.tasks.php?action=itags&tip=' + urlEncodeData($('option:selected', this).val()));

	});
	$('.ydropDown[data-change="activities"]').each(function () {

		var $el = $(this).data('selected');
		var $tip = $(this).data('id');
		$('#tagbox[data-tip="' + $tip + '"]').empty().load('/content/core/core.tasks.php?action=itags&tip=' + urlEncodeData($el));

	});

	$(document).on('change', 'select[data-change="activities"]', function () {
		var $el = $(this).data('id');
		$('#tagbox[data-id="' + $el + '"]').empty().load('/content/core/core.tasks.php?action=itags&tip=' + urlEncodeData($('option:selected', this).val()));
	});

	$(document).off('change', 'input[data-change="activities"]');
	$(document).on('change', 'input[data-change="activities"]', function () {

		var $el = $(this).data('id');
		var $tip = $(this).val();

		$('#tagbox[data-tip="' + $el + '"]').empty().load('/content/core/core.tasks.php?action=itags&tip=' + urlEncodeData($tip));

	});

	$(document).off('click', '.tags');
	$(document).on('click', '.tags', function () {
		var $tag = $(this).text();
		var $el = $(this).closest('#tagbox').data('id');
		insTextAtCursor($el, $tag + '; ');
	});

	$('#dodog').bind('click', function () {

		$('#dogblock').toggleClass('hidden');

		//если сделка не создается, то все обязательные поля делаем не обязательными
		if ($('#dodog').prop('checked')) {

			$('#dogblock').find('[data-req="required"]').addClass('required');
			$('#dogblock').find('[data-req="multireq"]').addClass('multireq');
			$('#dogblock').find('[data-req="req"]').addClass('req');

		}
		else {

			$('#dogblock').find('[data-req="required"]').removeClass('required');
			$('#dogblock').find('[data-req="multireq"]').removeClass('multireq');
			$('#dogblock').find('[data-req="req"]').removeClass('req');

		}

	});

	function checkTask() {

		if ($('#todo\\[theme\\]').val() !== '' && action === 'edit' && parseInt($('#ide').val()) === 0 && $timecheck) {

			if (origDateTime === $('#todo\\[datumtime\\]').val() || origTip === $('#todo\\[tip\\] option:selected').text()) {

				Swal.fire(
					{
						title: 'Вы ничего не забыли?',
						text: "Не изменена Дата и/или Тип напоминания!",
						type: 'question',
						showCancelButton: true,
						confirmButtonText: 'Продолжить',
						cancelButtonText: 'Упс, реально забыл',
						customClass: {
							confirmButton: 'button greenbtn',
							cancelButton: 'button redbtn'
						},
					}
				).then((result) => {

					if (result.value) {

						$('#Form').submit();

					}

				});

			}
			else $('#Form').submit();

		}
		else $('#Form').submit();

	}

	function reloadMasks() {

		//Формат номеров телефонов
		if (formatPhone !== '') {

			$('.phone').each(function () {

				$(this).phoneFormater(formatPhone);

			});

		}

	}

	function selItem(tip, id) {

		var cphone = $("#client\\[phone\\]").val();
		var pphone = $("#person\\[tel\\]").val();
		var $clid = $("#client\\[clid\\]").val();

		if (tip === 'client') {

			var url = '/content/helpers/client.helpers.php?action=clientinfo&clid=' + id;
			var reqphone = '';
			var reqfax = '';

			$("#client\\[clid\\]").val(id);

			if ($('#vphone').find('.phone:first-child').hasClass('required')) reqphone = 'required';
			if ($('#vfax').find('.phone:first-child').hasClass('required')) reqfax = 'required';

			$.getJSON(url, function (data) {

				if (formatPhone === '') {

					if (cphone === '') $("#client\\[phone\\]").val(data.phone);
					else $("#client\\[phone\\]").val(cphone + ', ' + data.phone);

				}

				if (formatPhone !== '') {

					var phone = data.phone.replace('+', '').split(",");
					var fax = data.fax.replace('+', '').split(',');
					var string = '';
					var stringf = '';

					$('#vphone').find('.phoneBlock').not(':last').remove();
					$('#vfax').find('.phoneBlock').not(':last').remove();
					$('#vtel').find('.phoneBlock').not(':last').remove();

					for (var i in phone) {

						if (phone[i] !== '') string += '' +
							'<div class="phoneBlock paddbott5 relativ">' +
							'   <INPUT name="client[phone][]" type="text" class="phone w250 ' + reqphone + '" id="client[phone][]" value="' + phone[i] + '" placeholder="Формат: <?=$format_tel?>" data-id="vphone" data-action="valphone" data-type="client.helpers" autocomplete="off"> <span class="remover hand" data-parent="vphone"><i class="icon-minus-circled red"></i></span>' +
							'</div>';

					}

					$('#vphone').prepend(string);

					if (string !== '') $('#vphone').find('.phone:last').removeClass('required');

					for (var i in fax) {

						if (fax[i] !== '') stringf += '' +
							'<div class="phoneBlock paddbott5 relativ">' +
							'   <INPUT name="client[fax][]" type="text" class="phone w250 ' + reqfax + '" id="client[fax][]" value="' + fax[i] + '" placeholder="Формат: <?=$format_tel?>" data-id="vfax" data-action="valphone" data-type="client.helpers" autocomplete="off"> <span class="remover hand" data-parent="vfax"><i class="icon-minus-circled red"></i></span>' +
							'</div>';

					}

					$('#vfax').prepend(stringf);

					if (stringf !== '') $('#vfax').find('.phone:last').removeClass('required');

					//Формат номеров телефонов
					reloadMasks();

				}

				$("#client\\[mail_url\\]").val(data.mail_url);
				$("#client\\[site_url\\]").val(data.site_url);
				$("#client\\[head_clid\\]").val(data.head_clid);
				$("#lst_spisok").val(data.head);
				$("#client\\[address\\]").val(data.address);
				$("#client\\[territory\\]").find('[value="' + data.territory + '"]').prop("selected", true);
				$("#client\\[idcategory\\]").find('[value="' + data.idcategory + '"]').prop("selected", true);
				$("#client\\[clientpath\\]").find('[value="' + data.clientpath2 + '"]').prop("selected", true);
				$("#client\\[tip_cmr\\]").find('[value="' + data.tip_cmr + '"]').prop("selected", true);

				//пройдем доп.поля
				for (var key in data) {

					if (key.indexOf("input") >= 0) {

						//console.log(key);

						var element = $('#client\\[' + key + '\\]');

						if (element.is('input[type="text"]')) {

							element.val(data[key]);

						}
						else if (element.is('select') && !element.hasClass('multiselect')) {

							element.find('[value="' + data[key] + '"]').prop("selected", true);

						}
						else if ($('#client\\[' + key + '\\]\\[\\]').hasClass('multiselect')) {

							var chm = data[key].split(',');

							for (var h in chm) {

								$('#client\\[' + key + '\\]\\[\\] option[value="' + chm[h].trim() + '"]').prop("selected", true);

							}

							$('#client\\[' + key + '\\]\\[\\]').multiselect('destroy').multiselect({
								sortable: true,
								searchable: true
							});
							$(".connected-list").css('height', "200px");

						}
						//такого типа нет, но вдруг :)
						else if (element.is('input[type="checkbox"]')) {

							var ch = data[key].split(',');

							for (var h in ch) {

								element.find('[value="' + ch[h].trim() + '"]').prop("checked", true);

							}

						}
						else if (element.is('input[type="radio"]')) {

							$('#client\\[' + key + '\\][value="' + data[key] + '"]').prop("checked", true);

						}

					}

				}

			});
		}
		if (tip === 'person') {

			url = '/content/helpers/client.helpers.php?action=clientinfo&pid=' + id;
			$("#person\\[pid\\]").val(id);

			var reqphone2 = '';
			var reqfax2 = '';
			var reqtel = '';
			var reqmob = '';
			var string = '';
			var stringf = '';

			if ($('#vphone').find('.phone:first-child').hasClass('required')) reqphone2 = 'required';
			if ($('#vfax').find('.phone:first-child').hasClass('required')) reqfax2 = 'required';

			$.getJSON(url, function (data) {

				if (data.clid != '' && $clid == '') {

					var url2 = '/content/helpers/client.helpers.php?action=clientinfo&clid=' + data.clid;

					$.getJSON(url2, function (data2) {

						if (formatPhone === '') {

							$("#client\\[phone\\]").val(data2.phone);

						}

						if (formatPhone !== '') {

							var phone = (data2.phone) ? data2.phone.replace('+', '').split(", ") : [];
							var fax = (data2.fax) ? data2.fax.replace('+', '').split(", ") : [];

							for (var i in phone) {

								if (phone[i] !== '') string += '' +
									'<div class="phoneBlock paddbott5 relativ">' +
									'   <INPUT name="client[phone][]" type="text" class="phone w250 ' + reqphone2 + '" id="client[phone][]" value="' + phone[i] + '" placeholder="Формат: <?=$format_tel?>" data-id="vphone" data-action="valphone" data-type="client.helpers" autocomplete="off"> <span class="remover hand" data-parent="vphone"><i class="icon-minus-circled red"></i></span>' +
									'</div>';

							}

							$('#vphone').prepend(string);

							if (string !== '') $('#vphone').find('.phone:last').removeClass('required');

							for (var i in fax) {

								if (fax[i] !== '') stringf += '' +
									'<div class="phoneBlock paddbott5 relativ">' +
									'   <INPUT name="client[fax][]" type="text" class="phone w250 ' + reqfax2 + '" id="client[fax][]" value="' + fax[i] + '" placeholder="Формат: <?=$format_tel?>" data-id="vfax" data-action="valphone" data-type="client.helpers" autocomplete="off"> <span class="remover hand" data-parent="vfax"><i class="icon-minus-circled red"></i></span>' +
									'</div>';

							}

							$('#vfax').prepend(stringf);

							if (stringf !== '') $('#vfax').find('.phone:last').removeClass('required');

							//Формат номеров телефонов
							reloadMasks();

						}

						$("#client\\[clid\\]").val(data2.clid);
						$("#client\\[title\\]").val(data2.title);
						$("#client\\[head_clid\\]").val(data2.head_clid);
						$("#lst_spisok").val(data2.head);
						$("#client\\[mail_url\\]").val(data2.mail_url);
						$("#client\\[address\\]").val(data2.address);
						$("#client\\[territory\\]").find('[value="' + data2.territory + '"]').prop("selected", true);
						$("#client\\[idcategory\\]").find('[value="' + data2.idcategory + '"]').prop("selected", true);
						$("#client\\[clientpath\\]").find('[value="' + data2.clientpath2 + '"]').prop("selected", true);
						$("#client\\[tip_cmr\\]").find('[value="' + data2.tip_cmr + '"]').prop("selected", true);

						//пройдем доп.поля
						for (var key in data2) {

							if (key.indexOf("input") >= 0) {

								//console.log(key);

								var element = $('#client\\[' + key + '\\]');

								if (element.is('input[type="text"]')) {

									element.val(data2[key]);

								}
								else if (element.is('select') && !element.hasClass('multiselect')) {

									element.find('[value="' + data2[key] + '"]').prop("selected", true);

								}
								else if ($('#client\\[' + key + '\\]\\[\\]').hasClass('multiselect')) {

									var chm = data2[key].split(',');

									for (var h in chm) {

										$('#client\\[' + key + '\\]\\[\\] option[value="' + chm[h].trim() + '"]').prop("selected", true);

									}

									$('#client\\[' + key + '\\]\\[\\]').multiselect('destroy').multiselect({
										sortable: true,
										searchable: true
									});
									$(".connected-list").css('height', "200px");

								}
								//такого типа нет, но вдруг :)
								else if (element.is('input[type="checkbox"]')) {

									var ch = data2[key].split(',');

									for (var h in ch) {

										element.find('[value="' + ch[h].trim() + '"]').prop("checked", true);

									}

								}
								else if (element.is('input[type="radio"]')) {

									$('#client\\[' + key + '\\][value="' + data2[key] + '"]').prop("checked", true);

								}

							}

						}

					})
						.complete(function () {

							if (formatPhone !== '') {

								/*$('#resultdiv').find('.phone').each(function () {

									if ($(this).val().replace(/\D+/g, "").length === 12) {
										$(this).setMask({
											mask: '99 (999) 999-9999'
										});
									}
									else {
										$(this).setMask({
											mask: formatPhone
										});
									}

								});*/

							}

						});

				}

				if ($("#person\\[ptitle\\]").val() == '') $("#person\\[ptitle\\]").val(data.ptitle);
				if ($("#person\\[mail\\]").val() == '') $("#person\\[mail\\]").val(data.mail);

				if (formatPhone === '') {

					$('#vtel').find('.phoneBlock').not(':last').remove();
					$('#vmob').find('.phoneBlock').not(':last').remove();

					if (pphone == '') $("#person\\[tel\\]").val(data.tel);
					else $("#person\\[tel\\]").val(pphone + ', ' + data.tel);

					if (pphone == '') $("#person\\[mob\\]").val(data.mob);
					else $("#person\\[mob\\]").val(pphone + ', ' + data.mob);

				}

				if (formatPhone !== '') {

					if ($('#vtel').find('.phone:first-child').hasClass('required')) reqtel = 'required';
					if ($('#vmob').find('.phone:first-child').hasClass('required')) reqmob = 'required';

					var tel = (data.tel) ? data.tel.replace('+', '').split(",") : [];
					var mob = (data.mob) ? data.mob.replace('+', '').split(",") : [];
					var stringt = '';
					var stringm = '';

					for (var i in tel) {

						if (tel[i] !== '') stringt += '<div class="phoneBlock paddbott5 relativ">' +
							'<INPUT name="person[tel][]" type="text" class="phone w250 ' + reqtel + '" id="person[tel][]" alt="phone" autocomplete="off" value="' + tel[i] + '" placeholder="Формат: <?=$format_tel?>" data-id="vtel" data-action="valphone" data-type="person.helpers">&nbsp;' +
							'<span class="remover hand" data-parent="vtel"><i class="icon-minus-circled red"></i></span>' +
							'</div>';
					}

					$('#vtel').prepend(stringt);

					if (stringt !== '') $('#vtel').find('.phone:last').removeClass('required');

					for (var i in mob) {

						if (mob[i] !== '') stringm += '' +
							'<div class="phoneBlock paddbott5 relativ">' +
							'<INPUT name="person[mob][]" type="text" class="phone w250 ' + reqmob + '" id="person[mob][]" alt="phone" autocomplete="off" value="' + mob[i] + '" placeholder="Формат: <?=$format_tel?>" data-id="vtel" data-action="valphone" data-type="person.helpers">&nbsp;' +
							'<span class="remover hand" data-parent="vtel"><i class="icon-minus-circled red"></i></span>' +
							'</div>';
					}

					$('#vmob').prepend(stringm);

					if (stringm !== '') $('#vmob').find('.phone:last').removeClass('required');

					//Формат номеров телефонов
					reloadMasks();

				}

			})
				.complete(function () {

					if (formatPhone !== '') {

						/*$('#resultdiv').find('.phone').each(function () {

							if ($(this).val().replace(/\D+/g, "").length === 12) {
								$(this).setMask({
									mask: '99 (999) 999-9999'
								});
							}
							else {
								$(this).setMask({
									mask: formatPhone
								});
							}

						});*/

					}

				});
		}

	}

	/**
	 * @deprecated
	 * @param formelement
	 * @param divname
	 * @param url
	 * @param action
	 * @returns {boolean}
	 */
	function validate(formelement, divname, url, action) {

		var awidth;
		var title;
		var atop;
		var aleft;

		formelement = formelement.replace("[", "\\[").replace("]", "\\]");

		if ($('#' + formelement).val().length >= 3) {

			atop = $('#' + formelement).position().top + 30;
			aleft = $('#' + formelement).position().left - 5;
			awidth = $('#' + formelement).width();
			title = urlEncodeData($('#' + formelement).val());

			if ($('#ospisok').is('div') == false) {

				$('#dialog').append('<div id="ospisok"></div>');
				$('#ospisok').css({
					"left": aleft + "px",
					"top": atop + "px",
					"width": awidth + "px",
					"display": "block"
				}).append('<div id="loader"><img src="/assets/images/loading.gif"> Загрузка данных...</div>');

			}

			$.get(url + '?type=json&action=' + action + '&title=' + title, function (data) {

				var string = '';

				for (var i in data) {

					string = string +
						'<div class="row">' +
						'   <div class="column12 grid-8">' +
						'       <div class="ellipsis fs-11">' + data[i].name + '</div>' +
						'       <div class="em fs-09 gray2">' + data[i].tel + (data[i].tel !== '' && data[i].email !== '' ? ', ' : '') + data[i].email + '</div>' +
						'   </div>' +
						'   <div class="column12 grid-4 blue">' + data[i].user + '</div>' +
						'</div>' +
						'<hr>';

				}

				if (data.length === 0) string = '<div class="zbody green pad5">Ура! Дубликатов нет. Можно добавить</div>';


				$('#ospisok').empty().append('<div class="header fs-12"><b>Похожие записи (возможные дубли):</b></div><div class="zbody">' + string + '</div>').css('display', 'block');

			}, "json");


			return false;
		}

	}

	function validatein() {
		if ($('#client\\[title\\]').val() == '' && $('#person\\[person\\]').val() == '') {
			alert('Должно быть заполнено "Название организации" или "Ф.И.О. персоны"');
			return false;
		}
		else {
			$('#form').submit();
			DClose();
		}
	}

	function prTRclone2() {

		var i = parseInt($("#spcount").val()) + 1;

		var trhtml = '<tr id="pr_' + i + '"><td><input name="prid[]" id="prid[]" type="hidden" value=""><input name="idp[]" id="idp[]" type="hidden" value=""><input name="speca_title[]" type="text" id="speca_title[]" value="" style="width:98%" class="requered" placeholder="Начните вводить наименование"/></td><td align="center"><input name="speca_kol[]" type="text" id="speca_kol[]" value="1,00" style="width:70%"></td><td align="center"><input name="speca_price[]" type="text" id="speca_price[]" style="width:70%"></td><td align="center"><a href="javascript:void(0)" onclick="prTRremove(' + i + ');"><i class="icon-cancel-circled red" title="Удалить"></i><span class="visible-iphone">Удалить</span></a></td></tr>';

		$('#tbspeca').append(trhtml);
		$('#spcount').val(i);

		$("#pr_" + i + " #speca_title\\[\\]").autocomplete("/content/helpers/price.helpers.php",
			{
				autofill: true,
				minChars: 2,
				cacheLength: 1,
				maxItemsToShow: 20,
				selectFirst: false,
				multiple: false,
				delay: 10,
				matchSubset: 1,
				formatItem: function (data, j, n, value) {
					return '<div onclick="selItem2(\'' + i + '\',\'' + data[1] + '\',\'' + data[6] + '\')">' + data[0] + '</div>';
				},
				formatResult: function (data) {
					return data[0];
				}
			}
		);
		$("#pr_" + i + " #speca_title\\[\\]").result(function (value, data) {
			selItem2(i, data[1], data[6])
		});

	}

	function selItem2(i, price, prid) {
		$("#pr_" + i + " #speca_price\\[\\]").val(price);
		$("#pr_" + i + " #prid\\[\\]").val(prid);
	}

	function addPositionSpeca() {

		var i = parseInt($("#spcount").val()) + 1;

		trhtml = '<tr id="pr_' + i + '"><td><input name="prid[]" id="prid[]" type="hidden" value=""><input name="idp[]" id="idp[]" type="hidden" value=""><input name="speca_title[]" type="text" id="speca_title[]" value="' + $('#title').val() + '" style="width:98%"></td><td align="center"><input name="speca_kol[]" type="text" id="speca_kol[]" value="' + $('#kol').val() + '" style="width:70%"></td><input name="speca_price[]" type="text" id="speca_price[]" value="' + $('#price_1').val() + '" style="width:70%"></td><td align="center"><a href="javascript:void(0)" onclick="prTRremove(' + i + ');"><i class="icon-cancel-circled red" title="Удалить"></i><span class="visible-iphone">Удалить</span></a></td></tr>';
		$('#tbspeca').append(trhtml);
		$('#spcount').val(i);

		$('#swindow').hide().empty();

	}

	function prTRremove(id) {
		$('#tbspeca #pr_' + id).remove();
	}

	function setUser() {

		var id = $('#iduser option:selected').val();
		$('#todo\\[touser\\]').val(id);

	}

	function gettags() {
		var tip = urlEncodeData($('#tiphist option:selected').val());
		$('#tagbox').load('/content/core/core.tasks.php?action=tags&tip=' + tip);
	}

	function tagit(id) {
		var html = $('#tag_' + id).html();
		insTextAtCursor('content', html + '; ');
	}

	function copydes() {

		var tt = $('#content').val();
		$('#todo\\[des\\]').val(tt);

	}

</script>