<?php
/* ============================ */
/*         SalesMan CRM         */
/* ============================ */
/* (C) 2016 Vladislav Andreev   */
/*       SalesMan Project       */
/*        www.isaler.ru         */
/*        ver. 2019.x           */

/* ============================ */

use Salesman\Currency;
use Salesman\Deal;
use Salesman\Elements;
use Salesman\Speka;

error_reporting( E_ERROR );
//ini_set('display_errors', 1);
header( "Pragma: no-cache" );

$rootpath = dirname( __DIR__, 2 );

include $rootpath."/inc/config.php";
include $rootpath."/inc/dbconnector.php";
include $rootpath."/inc/auth.php";
include $rootpath."/inc/func.php";
include $rootpath."/inc/settings.php";
include $rootpath."/inc/language/".$language.".php";

$thisfile = basename( __FILE__ );

global $userRights;

$action = $_REQUEST['action'];
$did    = (int)$_REQUEST['did'];

$mcDefault = customSettings( "mcDefault");

// перенаправляем на новую форму
if ( in_array( $action, [
	'add',
	'edit'
] ) ) {
	$action = 'edit.new';
}

// перенаправляем на новую форму
if ( $action == 'credit.add' ) {
	$action = 'credit.edit';
}

//загружаем все возможные цепочки и конвертируем в JSON
$mFunnel = json_encode_cyr( getMultiStepList() );

//текущий этап сделки
$currentStep = 0;

//Типы позиций спеки
$types = [
	"Товар",
	"Услуга",
	"Материал"
];

$ndsRaschet = $GLOBALS['ndsRaschet'];

//выбранные контакты по сделке
$plist = '';

if ( $action == "edit.new" ) {

	if ( $did == 0 ) {

		$kol          = 0;
		$marg         = 0;
		$specadiv     = 'hidden'; //по умолчанию спецификация скрыта
		$calculate    = 'no';
		$isstartspeca = '';

		$deal   = $speca = [];
		$client = '';

		$clid = (int)$_REQUEST['clid'];
		$pid  = (int)$_REQUEST['pid'];

		//если сделка создается на основе обращения
		$ide = (int)$_REQUEST['ide'];
		if ( $ide > 0 ) {

			$res     = $db -> getRow( "SELECT * FROM {$sqlname}entry WHERE ide = '$ide' and identity = '$identity'" );
			$clid    = (int)$res["clid"];
			$pid     = (int)$res["pid"];
			$content = $res["content"];

			$deal['priceLevel'] = ($clid > 0) ? getClientData( $clid, "priceLevel" ) : 'price_1';

			$deal['content'] = strtr( $content, [
				"<br>" => "",
				"\t"   => ""
			] );

			$resp = $db -> query( "SELECT * FROM {$sqlname}entry_poz WHERE ide = '$ide' and identity = '$identity'" );
			while ($datap = $db -> fetch( $resp )) {

				$ress     = $db -> getRow( "SELECT * FROM {$sqlname}price WHERE n_id = '".$datap['prid']."' and identity = '$identity'" );
				$price_in = $ress["price_in"];
				$price    = $ress[ $deal['priceLevel'] ];
				$edizm    = $ress["edizm"];

				if ( $datap['price'] > 0 ) {
					$price = $datap['price'];
				}

				$speca[] = [
					"artikul"  => $datap['artikul'],
					"prid"     => (int)$datap['prid'],
					"tip"      => $datap['tip'],
					"title"    => $datap['title'],
					"kol"      => $datap['kol'],
					"price"    => num_format( $price ),
					"price_in" => num_format( $price_in ),
					"edizm"    => $edizm
				];

			}

		}

		//если создаем дубликат сделки
		$odid = (int)$_REQUEST['odid'];
		if ( $odid > 0 ) {

			$deal = get_dog_info( $odid, 'yes' );

			//print_r($deal);

			$clid            = (int)$deal['clid'];
			$pid             = (int)$deal['pid'];
			$mcDefault       = (int)$deal['mcid'];
			$deal['content'] = strtr( $deal['content'], [
				"<br>" => "",
				"\t"   => ""
			] );

			$resp = $db -> query( "SELECT * FROM {$sqlname}speca WHERE did = '$odid' and tip != '2' and identity = '$identity' ORDER BY spid" );
			while ($datap = $db -> fetch( $resp )) {

				$speca[] = [
					"artikul"  => $datap['artikul'],
					"prid"     => (int)$datap['prid'],
					"tip"      => $datap['tip'],
					"title"    => $datap['title'],
					"kol"      => $datap['kol'],
					"dop"      => $datap['dop'],
					"price"    => num_format( $datap['price'] ),
					"price_in" => num_format( $datap['price_in'] ),
					"nds"      => $datap['nds'],
					"edizm"    => $datap['edizm']
				];

			}

			//print_r($speca);

			if ( $deal['datum_start'] != '0000-00-00' ) {
				$datum_start = $deal['datum_start'];
			}
			if ( $deal['datum_end'] != '0000-00-00' ) {
				$datum_end = $deal['datum_end'];
			}

			$tipDefault = $deal['tip'];// = $odata['tip'];
			$dirDefault = $deal['direction'];// = $odata['direction'];
			$pids       = yexplode( ";", (string)$deal['pid_list'] );

		}

		//формируем спецификацию
		$tbody = $sphtml = '';

		//если есть спека, то выведем её
		if ( !empty( $speca ) ){

			$ss  = ($show_marga != 'yes') ? "hidden" : "";
			$dop = ($otherSettings['dop']) ? '<th class="text-center">'.$otherSettings['dopName'].'</th>' : '';

			foreach ( $speca as $item ) {

				$dopp = ($otherSettings['dop']) ? '<td class="text-center"><input name="speca_dop[]" type="text" class="required wp80" id="speca_dop[]" value="'.$item['dop'].'"></td>' : '';

				foreach ( $types as $j => $type ) {

					$tipes .= '<option value="'.$j.'" '.($j == (int)$item['tip'] ? 'selected' : '').'>'.$type.'</option>';

					$j++;

				}

				$tbody .= '
				<tr class="tstring th45">
					<td><span><input name="speca_prid[]" id="speca_prid[]" type="hidden" value="'.$item['prid'].'"><input name="speca_nds[]" id="speca_nds[]" type="hidden" value="'.$item['nds'].'"><input name="speca_title[]" type="text" id="speca_title[]" value="'.$item['title'].'" class="wp100"></span></td>
					<td class="text-center">
						<select name="speca_tip[]" id="speca_tip[]" class="wp90 p0 pt2 pb2">
						'.$tipes.'
						</select>
					</td>
					<td class="text-center"><span><input name="speca_kol[]" type="text" min="1" id="speca_kol[]" onkeyup="GetSum();" value="'.num_format( $item['kol'] ).'" class="wp80"/></span></td>
					'.$dopp.'
					<td class="text-center"><span><input name="speca_ediz[]" type="text" id="speca_ediz[]" value="'.$item['edizm'].'" class="required wp80"/></span></td>
					<td><span><input name="speca_summa[]" type="text" id="speca_summa[]" onkeyup="GetSum();" value="'.$item['price'].'" size="15" class="wp95"></span></td>
					<td class="'.$ss.'"><span><input name="price_in[]" type="text" id="price_in[]" onkeyup="GetSum();" value="'.$item['price_in'].'" size="15" class="wp95"/></span></td>
					<td class="text-right"><div class="removeSpecaString hand"><i class="icon-cancel-circled red" title="Удалить"></i><span class="visible-iphone">Удалить</span></td>
				</tr>
				';

				$specadiv     = '';
				$calculate    = 'yes';
				$chspecac     = 'checked';
				$isstartspeca = 'yes';

			}

			$sphtml = '
			<table id="tbspeca">
			<thead>
			<tr>
				<th class="text-center">Продукт</th>
				<th class="w100 text-center">Тип</th>
				<th class="w80 text-center">Кол-во</th>
				'.$dop.'
				<th class="w80 text-center">Ед.изм.</th>
				<th class="w100 text-center">Цена, <b>'.$valuta.'</b></th>
				<th class="w100 text-center" class="'.$ss.'">Цена вход., <b>'.$valuta.'</b></th>
				<th class="w30 text-center"></th>
			</tr>
			</thead>
			'.$tbody.'
			</table>
			<br>
			<div class="text-right"><a href="javascript:void(0)" onclick="addSpecaString()" class="button greenbtn dotted">Добавить продукт</a></div>
			';

		}

		//если спеки нет, то подготовим пустую таблицу
		if ( $sphtml == '' ) {

			$dop  = ($otherSettings['dop']) ? '<th class="w60 text-center">'.$otherSettings['dopName'].'</th>' : '';
			$dopp = ($otherSettings['dop']) ? '<td class="text-center"><input name="speca_dop[]" type="text" id="speca_dop[]" value="" class="wp80"/></td>' : '';
			$cc   = ($show_marga != 'yes') ? 'hidden' : '';

			$sphtml = '
			<table id="tbspeca">
			<thead>
			<tr>
				<th class="text-center">Продукт</th>
				<th class="w100 text-center">Тип</th>
				<th class="w80 text-center">Кол-во</th>
				'.$dop.'
				<th class="w80 text-center">Ед.изм.</th>
				<th class="w100 text-center">Цена, <b>'.$valuta.'</b></th>
				<th class="w100 text-center" class="'.$cc.'">Цена вход., <b>'.$valuta.'</b></th>
				<th class="w30 text-center"></th>
			</tr>
			</thead>
			<tbody>
			<tr class="tstring th45">
				<td><span><input name="speca_artikul[]" id="speca_artikul[]" type="hidden" value="" /><input name="speca_prid[]" id="speca_prid[]" type="hidden" value="" /><input name="speca_title[]" type="text" id="speca_title[]" value="" class="wp100"/></span></td>
				<td class="text-center">
					<select name="speca_tip[]" id="speca_tip[]" class="wp90">
						<option value="0">Товар</option>
						<option value="1">Услуга</option>
						<option value="2">Материал</option>
					</select>
				</td>
				<td class="text-center"><span><input name="speca_kol[]" type="text"  id="speca_kol[]" value="1,00" class="wp80"/></span></td>
				'.$dopp.'
				<td class="text-center"><span><input name="speca_ediz[]" type="text" id="speca_ediz[]" value="" class="wp80"/></span></td>
				<td><span><input name="speca_summa[]" type="text" class="wp95" id="speca_summa[]" value="" size="15"/><input name="speca_nds[]" type="hidden" id="speca_nds[]" value="" /></span></td>
				<td class="'.$cc.'"><span><input name="price_in[]" type="text" class="wp95" id="price_in[]" value="" size="15"/></span></td>
				<td class="text-center"><div class="removeSpecaString hand"><i class="icon-cancel-circled red" title="Удалить"></i><span class="visible-iphone">Удалить</span></div></td>
			</tr>
			</tbody>
			</table>
			<br>
			<div class="text-right"><a href="javascript:void(0)" onclick="addSpecaString()" class="button greenbtn dotted">Добавить продукт</a></div>
			';

		}

		$deal['datum_plan'] = current_datum( -$perDay );

		$chspeca = ($calculate == 'yes') ? '' : 'hidden';

		$payer = $clid;

		if ( $clid > 0 ) {
			$client = current_client( $clid );
		}
		elseif ( $pid > 0 ) {
			$client = current_person( $pid );
		}

		$deal['title'] = str_replace( "{ClientName}", $client, generate_num( 'namedogovor' ) );

		if ( $deal['title'] == '' ) {
			$deal['title'] = 'Новая сделка';
		}

		$dNum = generate_num( 'dogovor' );
		if ( $dNum ) {
			$dnum = '<span class="smalltxt green">Номер '.$lang['face']['DealName'][1].': <b>'.$dNum.'</b> (предварительно)</span>';
		}

		//Посмотрим номер последней заявки в базе
		$nzayavka = $db -> getOne( "SELECT MAX(zayavka) FROM {$sqlname}dogovor WHERE zayavka > 0 and identity = '$identity'" );

		//если номер заявки = auto, то добавим расчетную
		if ( $deal['zayavka'] == 'auto' || $GLOBALS['zayavka'] == '' ) {
			$deal['zayavka'] = $nzayavka;
		}

		//print (int)$mcDefault;

		$deal['mcid']      = (int)$mcDefault;
		$deal['direction'] = (int)$dirDefault;
		$deal['tip']       = (int)$tipDefault;

		/**
		 * Найдем mcid в последних сделках
		 */
		$mcid = $clid > 0 ? (int)$db -> getOne( "SELECT mcid FROM {$sqlname}dogovor WHERE clid = '$clid' ORDER BY datum DESC LIMIT 1" ) : 0;
		if ( $mcid > 0 ) {
			$deal['mcid'] = $mcid;
		}

		if ( $otherSettings['dealStepDefault'] != '' && $deal['idcategory'] < 1 ) {
			$firstStep = $otherSettings['dealStepDefault'];
		}
		elseif ( $deal['idcategory'] > 0 ) {
			$firstStep = $deal['idcategory'];
		}
		else {
			$firstStep = $dfs;
		}

		$data['idcategory'] = $firstStep;

	}
	else {

		$kol       = 0;
		$marg      = 0;
		$specadiv  = 'hidden'; //по умолчанию спецификация скрыта
		$calculate = 'no';

		$deal   = $speca = [];
		$client = '';

		$deal  = $db -> getRow( "select * from {$sqlname}dogovor where did = '$did' and identity = '$identity'" );
		$clid  = (int)$deal['clid'];
		$pid   = (int)$deal['pid'];
		$close = $deal['close'];

		if ( (int)$deal['payer'] < 1 ) {
			$deal['payer'] = $clid;
		}

		if ( $deal['datum_start'] == '0000-00-00' ) {
			$deal['datum_start'] = '';
		}
		if ( $deal['datum_end'] == '0000-00-00' ) {
			$deal['datum_end'] = '';
		}

		$margp = (float)pre_format( $deal['kol'] ) != 0 ? num_format( (pre_format( $deal['marga'] ) / (float)pre_format( $deal['kol'] )) * 100 ) : (float)pre_format( $deal['kol'] );

		if ( $clid > 0 ) {
			$client = current_client( $clid );
		}
		elseif ( $pid > 0 ) {
			$client = current_person( $pid );
		}

		if ( $clid > 0 ) {
			$ss = "payer = '".$clid."' and ";
		}
		elseif ( $pid > 0 ) {
			$ss = "pid = '".$pid."' and ";
		}
		else {
			$ss = '';
		}

		$dog_numm = $db -> getOne( "SELECT number FROM {$sqlname}contract WHERE $ss identity = '$identity'" );

		$currentStep = current_dogstepname( $did );

		$deal['pid_list'] = yexplode( ";", (string)$deal['pid_list'] );

	}

	?>
	<div class="zagolovok"><?= $lang['all']['Edit'] ?> <?= $lang['face']['DealName'][3] ?></div>

	<form action="/content/core/core.deals.php" method="post" enctype="multipart/form-data" name="dealForm" id="dealForm" autocomplete="off">
		<input name="did" type="hidden" id="did" value="<?= $did ?>">
		<input type="hidden" id="action" name="action" value="deal.edit">
		<input name="datum" type="hidden" id="datum" value="<?= $deal['datum'] ?>">
		<input name="iduser" type="hidden" id="iduser" value="<?= $deal['iduser'] ?>">
		<input name="plist" type="hidden" id="plist" value="<?= $plist ?>">
		<input name="close" type="hidden" id="close" value="<?= $close ?>">
		<input name="ide" type="hidden" id="ide" value="<?= $ide ?>">
		<input name="odid" type="hidden" id="odid" value="<?= $odid ?>">
		<input name="isstartspeca" type="hidden" id="isstartspeca" value="<?= $isstartspeca ?>">

		<DIV id="formtabs" class="box--child" style="max-height:80vh; overflow-x: hidden; overflow-y: auto !important;">

			<?php
			$hooks -> do_action( "deal_form_before", $_REQUEST );
			?>

			<?php if ( $did == 0 ) { ?>
				<div class="flex-container box--child mt10 mb10">

					<div class="flex-string wp20 gray2 fs-12 pt7 right-text"><?= $fieldsNames['dogovor']['iduser'] ?>:</div>
					<div class="flex-string wp80 pl10">
						<?php
						$users = new Elements();
						print $users -> UsersSelect( "iduser", [
							"class"  => [
								"wp95",
								$fieldsRequire['dogovor']['iduser']
							],
							"active" => true,
							"jsact"  => "setUser()",
							"sel"    => $iduser1
						] );
						?>
					</div>

				</div>
			<?php } ?>

			<div id="divider" class="mt20 mb20"><b class="blue">Общая информация</b></div>

			<div class="flex-container mb10">

				<div class="flex-string wp20 gray2 fs-12 pt7 right-text">Название:</div>
				<div class="flex-string wp80 pl10">
					<input name="title_dog" type="text" class="required wp95" id="title_dog" value="<?= $deal['title'] ?>">
					<div><?= $dnum ?></div>
				</div>

			</div>

			<?php
			if ( !$otherSettings['dealByContact'] ) {
				?>
				<div class="flex-container float mb10">

					<div class="flex-string wp20 gray2 fs-12 pt7 right-text">Тип заказчика:</div>
					<div class="flex-string wp20 pl10">

						<select name="cld" id="cld" onchange="fncld();" class="wp97">
							<option value="org" <?= ($clid > 0 ? 'selected' : '') ?>>Клиент</option>
							<option value="psn" <?= ($clid < 1 && $pid > 0 ? 'selected' : '') ?>>Контакт</option>
						</select>

					</div>

				</div>
				<?php
			}
			?>

			<div class="flex-container float mb10">

				<div class="flex-string wp20 gray2 fs-12 pt7 right-text">Заказчик:</div>

				<div class="flex-string float pl10" id="clientselector">

					<div class="iclient <?= ($pid > 0 && $clid < 1 ? 'hidden' : '') ?>">
						<input name="client" type="text" id="client" class="<?= ($pid < 1 ? 'required' : '') ?> wp95" value="<?= $client ?>" placeholder="Начните вводить название. Например: Сэйлзмэн"><input type="hidden" id="clid" name="clid" value="<?= $clid ?>">
					</div>
					<?php if ( !$otherSettings['dealByContact'] ) { ?>
						<div class="iperson <?= ($clid > 0 || ($clid < 1 && $pid < 1) ? 'hidden' : '') ?>">
							<input name="person" type="text" class="<?= ($clid < 1 && $pid > 0 ? 'required' : '') ?> wp95" id="person" value="<?= $client ?>" placeholder="Начните вводить ФИО. Например: Иванов">
							<input type="hidden" id="pid" name="pid" value="<?= $pid ?>">
						</div>
					<?php } ?>
					<div class="fs-09 em gray">
						Выбирайте из существующих
						<?= ($otherSettings['addClientWDeal'] ? "или введите Название нового клиента ".(!$otherSettings['dealByContact'] ? "или ФИО нового контакта" : "") : "") ?>
					</div>

				</div>

			</div>

			<div class="flex-container mb10">

				<div class="flex-string wp20 gray2 fs-12 pt7 right-text"><?= $fieldsNames['dogovor']['payer'] ?>:</div>
				<div class="flex-string wp80 pl10">
					<div id="org" class="relativ">
						<INPUT type="hidden" id="payer" name="payer" value="<?= $deal['payer'] ?>" onchange="$('#payer').trigger('change')">
						<INPUT id="lst_payer" type="text" class="wp95 <?= $fieldsRequire['dogovor']['payer'] ?>" value="<?= current_client( $deal['payer'] ) ?>" readonly onclick="get_orgspisok('lst_payer','org','/content/helpers/client.helpers.php?action=get_orgselector','payer')" placeholder="Нажмите для выбора">
						<div class="idel pr20 mr20">
							<i title="Очистить" onclick="$('input#payer').val(0); $('#lst_payer').val('');" class="icon-block red hand mr10"></i>
						</div>
					</div>
					<div class="fs-09 em gray">Оставьте поле пустым, если Плательщик = Заказчик.</div>
				</div>

			</div>

			<?php if ( in_array( 'zayavka', (array)$fieldsOn['dogovor'] ) ) { ?>
				<div class="flex-container mb10">

					<div class="flex-string wp20 gray2 fs-12 pt7 right-text"><?= $fieldsNames['dogovor']['zayavka'] ?>:</div>
					<div class="flex-string wp80 pl10">
						<input name="zayavka" type="text" id="zayavka" class="wp95 <?= $fieldsRequire['dogovor']['zayavka'] ?>" value="<?= $deal['zayavka'] ?>" title="Поставьте “авто“ чтобы добавить автоматически">
					</div>

				</div>
				<div class="flex-container mb10">

					<div class="flex-string wp20 gray2 fs-12 pt7 right-text">Основание:</div>
					<div class="flex-string wp80 pl10">
						<input name="ztitle" type="text" id="ztitle" class="wp95" value="<?= $deal['ztitle'] ?>">
					</div>

				</div>
			<?php } ?>

			<?php
			$result = $db -> query( "SELECT * FROM {$sqlname}mycomps WHERE (SELECT COUNT(cid) FROM {$sqlname}mycomps_recv WHERE cid = {$sqlname}mycomps.id AND bloc != 'yes') > 0 AND identity = '$identity' ORDER BY name_shot" );
			$kol    = $db -> affectedRows();
			$hidd   = ($kol > 1) ? "" : 'hidden';

			//если вдруг компания не указана, то покажем
			if ( (int)$deal['mcid'] == 0 && $did > 0 )
				$hidd = '';
			?>
			<div class="flex-container mb10 <?= $hidd ?>">

				<div class="flex-string wp20 gray2 fs-12 pt7 right-text"><?= $fieldsNames['dogovor']['mcid'] ?>:</div>
				<div class="flex-string wp80 pl10">

					<select name="mcid" id="mcid" class="<?= $fieldsRequire['dogovor']['mcid'] ?> wp95" title="Укажите, от какой Вашей компании совершается сделка">
						<?php
						while ($data = $db -> fetch( $result )) {

							$s = ($data['id'] == $deal['mcid'] || $kol == 1) ? "selected" : "";
							print '<option value="'.$data['id'].'" '.$s.'>'.$data['name_shot'].'</option>';

						}
						?>
					</select>

				</div>

			</div>

			<div id="divider" class="mt20 mb20"><b>Детали</b></div>

			<div class="flex-container mb10">

				<div class="flex-string wp20 gray2 fs-12 pt7 right-text"><?= $fieldsNames['dogovor']['datum_plan'] ?>:</div>
				<div class="flex-string wp80 pl10">
					<input name="datum_plan" type="date" class="required inputdate wp30" id="datum_plan" value="<?= $deal['datum_plan'] ?>" maxlength="10" placeholder="Дата реализации" autocomplete="off"/>
				</div>

			</div>

			<?php
			$hidd = (in_array( 'period', (array)$fieldsOn['dogovor'] )) ? "" : 'hidden';
			?>
			<div class="flex-container mb10 <?= $hidd ?>">

				<div class="flex-string wp20 gray2 fs-12 pt7 right-text"><?= $fieldsNames['dogovor']['period'] ?>:</div>
				<div class="flex-string wp80 pl10">
					<input name="datum_start" type="date" id="datum_start" value="<?= $deal['datum_start'] ?>" size="10" maxlength="10" autocomplete="off" class="inputdate"/>&nbsp;до&nbsp;<input name="datum_end" type="date" id="datum_end" value="<?= $deal['datum_end'] ?>" size="10" maxlength="10" autocomplete="off" class="inputdate">
				</div>

			</div>

			<?php
			$idtype = $db -> getOne( "SELECT id FROM {$sqlname}contract_type WHERE type = 'get_dogovor' and identity = '$identity'" );

			if ( $pid > 0 )
				$ss = "pid = '".$pid."' and";
			else          $ss = "clid = '".$clid."' and";

			$result = $db -> query( "SELECT * FROM {$sqlname}contract WHERE (clid > 0 or pid > 0) and $ss idtype = '".$idtype."' and identity = '$identity'" );

			$hidd = ($db -> affectedRows( $result ) == 0) ? 'hidden' : '';
			?>
			<div class="flex-container mb10 <?= $hidd ?>">

				<div class="flex-string wp20 gray2 fs-12 pt7 right-text"><?= $fieldsNames['dogovor']['dog_num'] ?>:</div>
				<div class="flex-string wp80 pl10">
					<select name="dog_num" id="dog_num" class="wp95">
						<option value="">--без договора--</option>
						<?php
						while ($data = $db -> fetch( $result )) {

							$s = ($data['deid'] == $deal['dog_num']) ? "selected" : "";
							print '<option value="'.$data['deid'].'" '.$s.'>'.$data['number'].'</option>';

						}
						?>
					</select>
				</div>

			</div>

			<?php
			$res  = $db -> query( "SELECT * FROM {$sqlname}direction WHERE identity = '$identity' ORDER BY title" );
			$kol  = $db -> affectedRows( $res );
			$hidd = ($kol > 1) ? "" : 'hidden';
			?>
			<div class="flex-container mb10 <?= $hidd ?>">

				<div class="flex-string wp20 gray2 fs-12 pt7 right-text"><?= $fieldsNames['dogovor']['direction'] ?>:</div>
				<div class="flex-string wp80 pl10">
					<select name="direction" id="direction" class="required wp95">
						<?php
						while ($data = $db -> fetch( $res )) {

							$s = ($data['id'] == $deal['direction']) ? "selected" : "";
							print '<option value="'.$data['id'].'" '.$s.'>'.$data['title'].'</option>';

						}
						?>
					</select>&nbsp;
				</div>

			</div>

			<?php
			$res  = $db -> query( "SELECT * FROM {$sqlname}dogtips WHERE identity = '$identity' ORDER BY title" );
			$kol  = $db -> affectedRows( $res );
			$hidd = ($kol > 1) ? "" : 'hidden';
			?>
			<div class="flex-container mb10 <?= $hidd ?>">

				<div class="flex-string wp20 gray2 fs-12 pt7 right-text"><?= $fieldsNames['dogovor']['tip'] ?>:</div>
				<div class="flex-string wp80 pl10">
					<select name="tip" id="tip" class="required wp95">
						<?php
						while ($data = $db -> fetch( $res )) {

							$s = ($data['tid'] == $deal['tip']) ? "selected" : "";
							print '<option value="'.$data['tid'].'" '.$s.'>'.$data['title'].'</option>';

						}
						?>
					</select>
				</div>

			</div>

			<div class="flex-container mb10">

				<div class="flex-string wp20 gray2 fs-12 pt7 right-text"><?= $fieldsNames['dogovor']['idcategory'] ?>:</div>
				<div class="flex-string wp80 pl10">
					<select name="idcategory" id="idcategory" class="required wp95" <?= ($did > 0 ? 'disabled' : '') ?>>
						<option value="">--Выбор--</option>
						<?php
						$dfs = $db -> getOne( "SELECT idcategory FROM {$sqlname}dogcategory WHERE title = '20' and identity = '$identity' ORDER BY title" );

						$result = $db -> query( "SELECT * FROM {$sqlname}dogcategory WHERE identity = '$identity' ORDER BY title" );
						while ($data = $db -> fetch( $result )) {

							if ( $otherSettings['dealStepDefault'] != '' && $deal['idcategory'] < 1 )
								$firstStep = $otherSettings['dealStepDefault'];
							elseif ( $deal['idcategory'] > 0 )
								$firstStep = $deal['idcategory'];
							else $firstStep = $dfs;

							$s = ($data['idcategory'] == $firstStep) ? 'selected' : '';
							print '<option value="'.$data['idcategory'].'" '.$s.'>'.$data['title'].'%-'.$data['content'].'</option>';

						}
						?>
					</select>
				</div>

			</div>

			<?php if ( in_array( 'adres', (array)$fieldsOn['dogovor'], true ) ) { ?>
				<div class="flex-container mb10">

					<div class="flex-string wp20 gray2 fs-12 pt7 right-text"><?= $fieldsNames['dogovor']['adres'] ?>:</div>
					<div class="flex-string wp80 pl10 relativ">
						<input id="adres" name="adres" type="text" class="<?= $fieldsRequire['dogovor']['adres'] ?> wp95" value="<?= $deal['adres'] ?>" data-type="address">
					</div>

				</div>
			<?php } ?>

			<?php
			$res = $db -> query( "select * from {$sqlname}field where fld_tip='dogovor' and fld_name LIKE '%input%' and fld_on='yes' and identity = '$identity' order by fld_order" );
			while ($da = $db -> fetch( $res )) {

				$fieldData = $deal[ $da['fld_name'] ];

				if ( $da['fld_temp'] == "--Обычное--" ) {
					?>
					<div class="flex-container mb10">

						<div class="flex-string wp20 gray2 fs-12 pt7 right-text"><?= $da['fld_title'] ?>:</div>
						<div class="flex-string wp80 pl10 relativ">
							<input id="<?= $da['fld_name'] ?>" name="<?= $da['fld_name'] ?>" type="text" class="<?= $da['fld_required'] ?> wp95" value="<?= $fieldData ?>"/>
						</div>

					</div>
					<?php
				}
				elseif ( $da['fld_temp'] == "hidden" ) {
					?>
					<input id="<?= $da['fld_name'] ?>" name="<?= $da['fld_name'] ?>" type="hidden" value="<?= $fieldData ?>">
					<?php
				}
				elseif ( $da['fld_temp'] == "adres" ) {
					?>
					<div class="flex-container mb10">

						<div class="flex-string wp20 gray2 fs-12 pt7 right-text"><?= $da['fld_title'] ?>:</div>
						<div class="flex-string wp80 pl10 relativ">
							<input id="<?= $da['fld_name'] ?>" name="<?= $da['fld_name'] ?>" type="text" class="<?= $da['fld_required'] ?> wp95" value="<?= $fieldData ?>" data-type="address">
						</div>

					</div>
					<?php
				}
				elseif ( $da['fld_temp'] == "textarea" ) {

					if ( $fieldData == '' )
						$fieldData = $da['fld_var'];
					?>
					<div class="flex-container mb10">

						<div class="flex-string wp20 gray2 fs-12 pt7 right-text"><?= $da['fld_title'] ?>:</div>
						<div class="flex-string wp80 pl10 relativ">
							<textarea name="<?= $da['fld_name'] ?>" id="<?= $da['fld_name'] ?>" class="<?= $da['fld_required'] ?> wp95" rows="4" placeholder="<?= $da['fld_title'] ?>"><?= str_replace( "<br>", "\n", $fieldData ) ?></textarea>
						</div>

					</div>
					<?php
				}
				elseif ( $da['fld_temp'] == "select" ) {

					$vars = explode( ",", (string)$da['fld_var'] );
					?>
					<div class="flex-container mb10">

						<div class="flex-string wp20 gray2 fs-12 pt7 right-text"><?= $da['fld_title'] ?>:</div>
						<div class="flex-string wp80 pl10 relativ">
							<select name="<?= $da['fld_name'] ?>" class="<?= $da['fld_required'] ?> wp95" id="<?= $da['fld_name'] ?>">
								<option value="">--Выбор--</option>
								<?php
								foreach ($vars as $var) {

									$s = ($var == $fieldData) ? 'selected' : '';
									print '<option value="'.$var.'" '.$s.'>'.$var.'</option>';

								}
								?>
							</select>
						</div>

					</div>
					<?php
				}
				elseif ( $da['fld_temp'] == "multiselect" ) {

					$vars = explode( ",", (string)$da['fld_var'] );
					?>
					<div id="divider"><b><?= $da['fld_title'] ?></b></div>
					<div class="flex-container mb10">

						<div class="flex-string wp100 pl10 relativ <?= ($da['fld_required'] == 'required' ? 'multireq' : '') ?>">
							<select name="<?= $da['fld_name'] ?>[]" multiple="multiple" class="multiselect" id="<?= $da['fld_name'] ?>[]">
								<?php
								foreach ($vars as $var) {

									$s = (in_array( $var, (array)yexplode( ",", (string)$fieldData ) )) ? 'selected' : '';
									print '<option value="'.$var.'" '.$s.'>'.$var.'</option>';

								}
								?>
							</select>
						</div>

					</div>
					<hr>
					<?php
				}
				elseif ( $da['fld_temp'] == "radio" ) {

					$vars = explode( ",", (string)$da['fld_var'] );
					?>
					<div class="flex-container mb10 <?= ($da['fld_required'] == 'required' ? 'req' : '') ?>">

						<div class="flex-string wp20 gray2 fs-12 pt7 right-text"><?= $da['fld_title'] ?>:</div>
						<div class="flex-string wp80 relativ pl10">

							<div class="flex-container box--child wp95--5">
								<?php
								foreach ($vars as $var) {

									$s = ($var == $fieldData) ? $s = 'checked' : '';
									?>
									<div class="flex-string p10 mr5 mb5 flx-basis-20 viewdiv bgwhite inset">

										<div class="radio">
											<label>
												<input name="<?= $da['fld_name'] ?>" type="radio" id="<?= $da['fld_name'] ?>" <?= $s ?> value="<?= $var ?>"/>
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
												<input name="<?= $da['fld_name'] ?>" type="radio" id="<?= $da['fld_name'] ?>" <?= ($fieldData == '' ? 'checked' : '') ?> value="">
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
				elseif ( $da['fld_temp'] == "inputlist" ) {

					$vars = $da['fld_var'];
					?>
					<div class="flex-container mb10">

						<div class="flex-string wp20 gray2 fs-12 pt7 right-text"><?= $da['fld_title'] ?>:</div>
						<div class="flex-string wp80 pl10 relativ">
							<input type="text" name="<?= $da['fld_name'] ?>" id="<?= $da['fld_name'] ?>" class="<?= $da['fld_required'] ?> wp95" value="<?= $fieldData ?>" placeholder="<?= $da['fld_title'] ?>"/>
							<div class="smalltxt blue"><em>Двойной клик мышкой для показа вариантов</em></div>
							<script>
								var str = '<?=$vars?>';
								var data = str.split(',');
								$("#<?=$da['fld_name']?>").autocomplete(data, {
									autoFill: true,
									minLength: 0,
									minChars: 0,
									cacheLength: 5,
									max: 30,
									//selectFirst: true,
									multiple: false,
									delay: 0,
									matchSubset: 2
								});
							</script>
						</div>

					</div>
					<?php
				}
				elseif ( $da['fld_temp'] == "datum" ) {
					?>
					<div class="flex-container mb10">

						<div class="flex-string wp20 gray2 fs-12 pt7 right-text"><?= $da['fld_title'] ?>:</div>
						<div class="flex-string wp80 pl10 relativ">
							<INPUT name="<?= $da['fld_name'] ?>" type="text" id="<?= $da['fld_name'] ?>" class="inputdate <?= $da['fld_required'] ?> wp30" value="<?= $fieldData ?>" autocomplete="off" placeholder="<?= $da['fld_title'] ?>">
						</div>

					</div>
					<?php
				}
				elseif ( $da['fld_temp'] == "datetime" ) {
					?>
					<div class="flex-container mb10">

						<div class="flex-string wp20 gray2 fs-12 pt7 right-text"><?= $da['fld_title'] ?>:</div>
						<div class="flex-string wp80 pl10 relativ">
							<INPUT name="<?= $da['fld_name'] ?>" type="text" id="<?= $da['fld_name'] ?>" class="inputdatetime <?= $da['fld_required'] ?> wp30" value="<?= $fieldData ?>" autocomplete="off" placeholder="<?= $da['fld_title'] ?>">
						</div>

					</div>
					<?php
				}

			}
			?>

			<div id="divider" class="mt20 mb20"><b>Стоимость и Прибыль</b></div>

			<?php
			//if ( $did == 0 ) {

			$list      = [];
			$currencys = (new Currency()) -> currencyList();
			foreach ( $currencys as $item ) {

				$icon = ($item['code'] != '') ? $item['code'] : $item['view'];

				$list[] = [
					"id"    => $item['id'],
					"title" => $item['name'].' [ '.$icon.' ]'
				];

			}

			if ( !empty( $list ) ) {
				?>
				<div class="flex-container mb10">

					<div class="flex-string wp20 gray2 fs-12 pt7 right-text">Валюта:</div>
					<div class="flex-string wp80 pl10 relativ">

						<?php

						print Elements ::Select( 'idcurrency', $list, [
							"class"      => 'wp95',
							"emptyValue" => 0,
							"emptyText"  => "Системная",
							'sel'        => $deal['idcurrency'],
							'nowrapper'  => true
						] );
						?>

					</div>

				</div>
				<div class="flex-container mb10">

					<div class="flex-string wp20 gray2 fs-12 pt7 right-text">Курс валюты:</div>
					<div class="flex-string wp80 pl10 relativ">

						<?php
						$list = [];

						if ( $deal['idcurrency'] > 0 ) {
							$courses = Currency ::currencyLog( $deal['idcurrency'], 10 );
							foreach ( $courses as $item ) {

								$list[] = [
									"id"    => $item['id'],
									"title" => $item['course'].' [ '.$item['date'].' ]'
								];

							}
						}
						print Elements ::Select( 'idcourse', $list, [
							"class"      => 'wp95',
							"emptyValue" => 0,
							"emptyText"  => "Системная",
							"sel"        => $deal['idcourse'],
							'nowrapper'  => true
						] );
						?>

					</div>

				</div>
				<?php

			}

			//}
			?>

			<div class="flex-container mb10">

				<div class="flex-string wp20 gray2 fs-12 pt7 right-text"><?= $fieldsNames['dogovor']['kol'] ?>:</div>
				<div class="flex-string wp80 pl10 relativ">
					<input name="kol" type="text" class="required yw140" id="kol" <?php if ( $calculate == 'yes' )
						print 'disabled' ?> onkeyup="CheckMarg();" value="<?= num_format( $deal['kol'] ) ?>"/>&nbsp;<?= $valuta ?>
				</div>

			</div>

			<?php
			$hidd = ($show_marga == 'yes' && $otherSettings['marga']) ? "" : "hidden";
			?>
			<div class="flex-container mb10 <?= $hidd ?>">

				<div class="flex-string wp20 gray2 fs-12 pt7 right-text"><?= $fieldsNames['dogovor']['marg'] ?>:</div>
				<div class="flex-string wp80 pl10 relativ">

					<input name="marg" type="text" class="required yw140" id="marg" <?php if ( $calculate == 'yes' )
						print 'disabled' ?> value="<?= num_format( $deal['marga'] ) ?>"/>&nbsp;<?= $valuta ?>&nbsp;или&nbsp;<input name="margp" type="text" class="w100" id="margp" onkeyup="CheckMarg()" value="<?= $margp ?>" <?php if ( $calculate == 'yes' )
						print 'disabled' ?>/>&nbsp;%

				</div>

			</div>

			<div class="flex-container mb10">

				<div class="flex-string wp20 gray2 fs-12 pt7 right-text"></div>
				<div class="flex-string wp80 pl10 relativ">

					<?php if ( $did > 0 ) { ?>
						<?php
						if ( $otherSettings['price'] ) {

							if ( current_dogstepname( $deal['idcategory'] ) < 80 || $deal['calculate'] !== 'yes' ) {

								$ssc = ($deal['calculate'] == 'yes') ? "checked" : "";

								print '<div class="checkbox">
									<label for="calculate">
										<input name="calculate" type="checkbox" id="calculate" value="yes" '.$ssc.' onchange="startSpeca()"/>
										<span class="custom-checkbox"><i class="icon-ok"></i></span>
										&nbsp;Расчет по спецификации
										&nbsp;<i class="icon-info-circled blue" title="Включает возможность создавать спецификацию. Данные берутся на основе спецификации."></i>
									</label>
								</div>';

							}
							else {

								$ssc = ($deal['calculate'] == 'yes') ? "checked" : "";

								print '
									<div class="warning inline m0 p10">Этап сделки больше или равен 80%. Управление спецификацией не доступно.</div>
									<span class="hidden"><input name="calculate" type="checkbox" id="calculate" value="yes" '.$ssc.'></span>
								';

							}

						}
						?>
						<div id="chspeca" class="checkbox hidden">
							<?php if ( $otherSettings['price'] ) { ?>
								<label for="createcpeca">
									<input name="createcpeca" type="checkbox" id="createcpeca" value="yes" <?= $chspecac ?> onchange="showSpeca()"/>
									<span class="custom-checkbox"><i class="icon-ok"></i></span>
									&nbsp;Составить спецификацию сейчас&nbsp;<i class="icon-info-circled blue" title="Включает форму добавления позиций."></i>
								</label>
							<?php } ?>
						</div>
					<?php } ?>
					<?php if ( $did == 0 ) { ?>
						<?php if ( $otherSettings['price'] ) { ?>
							<div class="checkbox">
								<label for="calculate">
									<input name="calculate" type="checkbox" id="calculate" value="yes" <?php if ( $calculate == 'yes' )
										print 'checked' ?> <?php if ( $dogstatus > 80 )
										print "disabled"; ?> onchange="startSpeca()"/>
									<span class="custom-checkbox"><i class="icon-ok"></i></span>
									&nbsp;Расчет по спецификации
									&nbsp;<i class="icon-info-circled blue hidden-iphone" title="Включает возможность создавать спецификацию. Данные берутся на основе спецификации."></i>
								</label>
							</div>
						<?php } ?>
						<div id="chspeca" class="<?= $chspeca ?> checkbox mt10">
							<?php if ( $otherSettings['price'] ) { ?>
								<label for="createcpeca">
									<input name="createcpeca" type="checkbox" id="createcpeca" value="yes" <?= $chspecac ?> onchange="showSpeca()"/>
									<span class="custom-checkbox"><i class="icon-ok"></i></span>
									&nbsp;Составить спецификацию сейчас&nbsp;<i class="icon-info-circled blue hidden-iphone" title="Включает форму добавления позиций."></i>
								</label>
							<?php } ?>
						</div>
					<?php } ?>

				</div>

			</div>

			<div class="flex-container mb10 <?= $specadiv ?>">

				<div id="divider" class="wp100 mt20 mb20"><b class="blue">Спецификация</b></div>
				<div id="specaloader" class="wp100 mr20"><?= $sphtml ?></div>

			</div>

			<?php if ( in_array( 'content', (array)$fieldsOn['dogovor'] ) ) { ?>
				<div class="flex-container mb10">

					<div id="divider" class="wp100 mt20 mb20">
						<b><?= $fieldsNames['dogovor']['content'] ?></b></div>
					<div class="flex-string wp100 pl10 relativ pl20 pr20">
						<textarea name="content" rows="5" class="content wp100" id="content"><?= $deal['content'] ?></textarea>
					</div>

				</div>
			<?php } ?>

			<div id="persons">
				<?php
				if ( in_array( 'pid_list', $fieldsOn['dogovor'] ) ) {
					?>
					<div class="flex-container mb10">

						<div id="divider" class="wp100 mt20 mb20"><b>Присоединить Контакты</b></div>
						<div class="flex-string wp100 pl10 relativ">

							<select name="pid_list[]" multiple="multiple" class="multiselect" id="pid_list">
								<?php

								if ( $clid > 0 ) {

									$result = $db -> query( "SELECT pid, clid, person FROM {$sqlname}personcat WHERE (clid = '$clid' ".($deal['payer'] > 0 ? " or clid = '$deal[payer]'" : "").") and identity = '$identity'" );
									while ($data = $db -> fetch( $result )) {

										$s = ($data['pid'] == $deal['pid'] || in_array( $data['pid'], (array)$deal['pid_list'] )) ? "selected" : '';

										print '<option value="'.$data['pid'].'" '.$s.'>'.$data['person'].($data['clid'] == $clid ? " [заказчик]" : " [плательщик]").'</option>';

									}

								}
								?>
							</select>

						</div>

					</div>
					<?php
				}
				?>
			</div>

			<?php if ( $otherSettings['concurent'] ) { ?>

				<div id="divider" class="mt20 mb20"><b>Конкуренты</b></div>
				<div class="flex-container mb10">
					<div class="flex-string wp100 pl10 relativ">

						<select name="coid1[]" multiple="multiple" class="multiselect" id="coid1[]">
							<?php
							$result = $db -> query( "SELECT * FROM {$sqlname}clientcat WHERE type = 'concurent' and identity = '$identity'" );
							while ($data = $db -> fetch( $result )) {

								$s = ((in_array( $data['clid'], (array)yexplode( ";", (string)$deal['coid1'] ) ))) ? "selected" : '';
								print '<option value="'.$data['clid'].'" '.$s.'>'.$data['title'].'</option>';

							}
							?>
						</select>

					</div>

				</div>

			<?php } ?>

		</div>

		<hr>

		<div class="button--pane text-right">

			<a href="javascript:void(0)" onclick="$('#dealForm').trigger('submit')" class="button">Сохранить</a>&nbsp;
			<a href="javascript:void(0)" onclick="DClose()" class="button">Отмена</a>

		</div>

	</form>

	<!--шаблон блока для валюты-->
	<div id="courseTpl" type="x-tmpl-mustache" class="hidden">
		{{#list}}
		<option value="{{id}}">{{course}} [ {{date}} ]</option>
		{{/list}}
	</div>
	<?php

	$hooks -> do_action( "deal_form_after", $_REQUEST );

}

if ( $action == "close" ) {

	//проверка сделки перед закрытием
	$msg = [];

	$icount = $db -> getOne( "SELECT COUNT(crid) AS count FROM {$sqlname}credit WHERE did='".$did."'" );

	if ( $icount == 0 && ($otherSettings['credit'] || $otherSettings['planByClosed']) )
		$wrn = '<div class="warning div-center"><span><i class="icon-attention red icon-3x pull-left"></i></span><b class="red">Внимание!</b> <b>Вы хотите закрыть '.$lang['face']['DealName'][0].', по которому(ой) нет графика платежей!</b> Это приведет к тому, что суммы, указанные в '.$lang['face']['DealName'][4].' не будут учитаны в показателях выполнения плана. Рекомендуем зафиксировать оплату на вкладке "Счета и спецификация"</div>';

	//проверим контрольные точки
	if ( $complect_on == 'yes' ) {

		$ccount = $db -> getOne( "SELECT COUNT(id) as count FROM {$sqlname}complect WHERE did = '".$did."' and doit !='yes' and identity = '$identity' ORDER BY id" );

		if ( $ccount > 0 ) {

			$msg[] = '<li>Имеются открытые (невыполненные) контрольные точки в количестве " '.$ccount.' " штук.</li>';

		}

	}
	if ( !$userRights['deal']['close'] && $isadmin != 'on' ) {

		$msg[] = '<li>У вас нет прав на действие - Закрытие сделок</li>';

	}

	if ( !empty( $msg ) ) {

		print '
		<div class="zagolovok">Ошибка!</div>
		<h2 class="red">Выполнение действия невозможно.</h2>
		<div class="p10">
			<b>Причина:</b>
			<ul>'.implode( "", (array)$msg ).'</ul>
		</div>';

		exit();

	}

	$kol   = getDogData( $did, "kol" );
	$marga = getDogData( $did, "marga" );

	$payments = $db -> getOne( "SELECT COUNT(crid) FROM {$sqlname}credit WHERE do='on' AND did='$did' AND identity='$identity' GROUP BY did" );

	if ( $payments == 0 ) {
		$status = ( current_dogstep($did) < 90 ) ? 'lose' : '';
	}
	else {
		$status = 'win';
	}

	$winAnswers  = '';
	$loseAnswers = '';

	// Формируем возможные варианты комментариев для успешных сделок
	$winAnswer = $db -> getCol( "SELECT des_fact FROM {$sqlname}dogovor WHERE close='yes' AND des_fact!='' AND sid IN (SELECT sid FROM {$sqlname}dogstatus WHERE sid>0 AND result_close='win' AND identity='$identity') AND identity='$identity' GROUP BY des_fact ORDER BY datum_close DESC LIMIT 5" );

	foreach ( $winAnswer as $da ) {

		$winAnswers .= '<div class="p5 pt101 pb101 ha hand ellipsis border-bottom block" onclick="$(\'#des_fact\').val(\''.$da.'\')"><span class="bullet-mini greenbg"></span>&nbsp;&nbsp;'.$da.'</div>';

	}

	// Формируем возможные варианты комментариев для плохих сделок
	$loseAnswer = $db -> getCol( "SELECT des_fact FROM {$sqlname}dogovor WHERE close='yes' AND des_fact!='' AND sid IN (SELECT sid FROM {$sqlname}dogstatus WHERE sid>0 AND result_close='lose' AND identity='$identity') AND identity='$identity' GROUP BY des_fact ORDER BY datum_close DESC LIMIT 5 " );

	foreach ( $loseAnswer as $da ) {

		$loseAnswers .= '<div class="p5 pt101 pb101 ha hand ellipsis border-bottom block" onclick="$(\'#des_fact\').val(\''.$da.'\')"><span class="bullet-mini redbg"></span>&nbsp;&nbsp;'.$da.'</div>';

	}

	$sel = '';
	$l   = 0;
	
	// новая дата напоминания
	$thistime = modifyDatetime(NULL, ["format" => "Y-m-d 11:00", "modify" => "+14 days"]);

	?>
	<div class="zagolovok">Закрытие <?= $lang['face']['DealName'][1] ?></div>
	<form method="post" action="/content/core/core.deals.php" enctype="multipart/form-data" name="dealForm" id="dealForm" autocomplete="off">
		<input name="did" type="hidden" id="did" value="<?= $did ?>">
		<input type="hidden" name="action" id="action" value="deal.change.close">

		<DIV id="formtabs" style="max-height:80vh; overflow-x: hidden; overflow-y:auto !important">

			<?php
			$hooks -> do_action( "deal_form_close_before", $_REQUEST );
			?>

			<div class="flex-container mb10 mt20 box--child">

				<div class="flex-string wp20 gray2 fs-12 pt7 right-text">Дата:</div>
				<div class="flex-string wp80 pl10 relativ">
					<input name="datum" type="text" class="inputdate wp97" id="datum" value="<?= current_datum() ?>">
				</div>

			</div>

			<div class="flex-container mb10 mt20 box--child">

				<div class="flex-string wp20 gray2 fs-12 pt7 right-text">Результат:</div>
				<div class="flex-string wp80 pl10 relativ">

					<select name="sid" id="sid" class="required wp97" onchange="getResSel()">
						<option value="" selected>--Выбор--</option>
						<?php
						$res = $db -> query( "SELECT * FROM {$sqlname}dogstatus WHERE identity = '$identity' ORDER BY result_close DESC" );
						while ($data = $db -> fetch( $res )) {

							$sel = '';

							if ( $status == $data['result_close'] && $status != '' && $l == 0 ) {

								$sel = 'selected';
								$l   = 1;

							}
							?>
							<option value="<?= $data['sid'] ?>" data-id="<?= $data['result_close'] ?>" data-content="<?= $data['content'] ?>" title="<?= $data['content'] ?>" <?= $sel ?> class="<?= ($data['result_close'] == 'win' ? 'greenbg-sub green' : 'redbg-sub red') ?>"><?= $data['title'] ?></option>
						<?php } ?>
					</select>

				</div>

			</div>

			<?php if ( $otherSettings['concurent'] ) { ?>
				<hr>
				<div class="flex-container mb10 mt20 box--child">

					<div class="flex-string wp20 gray2 fs-12 pt7 right-text">Конкурент:</div>
					<div class="flex-string wp80 pl10 relativ">
						<select name="coid" id="coid" class="wp97">
							<option value="" title="">--Выбор--</option>
							<?php
							$res = $db -> query( "SELECT * FROM {$sqlname}clientcat WHERE type = 'concurent' and identity = '$identity' ORDER BY title" );
							while ($data = $db -> fetch( $res )) {
								?>
								<option value="<?= $data['clid'] ?>" title="<?= $data['title'] ?>" style="width:97%" onclick="$('#kol_fact').val('0,00');  $('#marga').val('0,00');"><?= $data['title'] ?></option>
							<?php } ?>
						</select>
						<div class="gray2 em fs-09">Если выиграл конкурент</div>
					</div>

				</div>

				<div id="sum_conc" class="flex-container mb10 mt20 box--child hidden">

					<div class="flex-string wp20 gray2 fs-12 pt7 right-text">Цена:</div>
					<div class="flex-string wp80 pl10 relativ">
						<input name="co_kol" type="text" class="w160" id="co_kol" value="">&nbsp;<?= $valuta ?>
					</div>

				</div>
				<hr>
			<?php } ?>

			<div class="flex-container mb10 mt20 box--child">

				<div class="flex-string wp20 gray2 fs-12 pt10 right-text">Комментарий:</div>
				<div class="flex-string wp80 pl10 relativ">
					<?php if ( $status != '' )
						$text = ($status == 'win') ? "Победа: имеются оплаченные счета" : "Проигрыш: оплат не найдено, этап сделки ниже 90%" ?>
					<div class="tagsmenuToggler hand pull-aright mr20 mb5 text-right" style="margin-top: -10px">
						Возможные варианты
						<div class="tagsmenu top hidden text-left" style="right: 20px">
							<div class="blok" id="answers">
								<?php
								if ( $status != '' ) {
									if ( $status == 'win' ) {
										$hide1 = '';
										$hide2 = 'hidden';
									}
									else {
										$hide1 = 'hidden';
										$hide2 = '';
									}
								}
								else {
									$hide1 = $hide2 = 'hidden';
								}
								?>
								<div class="<?= $hide1 ?>" id="winAnswers"><?= $winAnswers ?></div>
								<div class="<?= $hide2 ?>" id="loseAnswers"><?= $loseAnswers ?></div>
							</div>
						</div>
					</div>
					<textarea name="des_fact" rows="2" id="des_fact" class="wp97"><?= $text ?></textarea>
					<div class="idel mt10 mr10" style="height:20px">
						<i title="Очистить" onclick="$('#des_fact').val('');" class="icon-block red hand mr10"></i>
					</div>

				</div>

			</div>

			<div id="stepfields" class="infodiv"></div>

			<div class="flex-container mb10 mt20 box--child">

				<div class="flex-string wp20 gray2 fs-12 pt7 right-text"><?= $fieldsNames['dogovor']['kol_fact'] ?>:</div>
				<div class="flex-string wp80 pl10 relativ">
					<input name="kol_fact" type="text" class="required w160" id="kol_fact" value="<?= num_format( $kol ) ?>">&nbsp;<?= $valuta ?>
				</div>

			</div>

			<?php if ( $show_marga == 'yes' && $otherSettings['marga'] ) { ?>
				<div class="flex-container mb10 mt20 box--child">

					<div class="flex-string wp20 gray2 fs-12 pt7 right-text"><?= $fieldsNames['dogovor']['marg'] ?> (факт):</div>
					<div class="flex-string wp80 pl10 relativ">
						<input name="marga" type="text" class="required w160" id="marga" value="<?= num_format( $marga ) ?>">&nbsp;<?= $valuta ?>
					</div>

				</div>
			<?php } ?>

			<div class="flex-container mb10 mt20 box--child infodiv dotted bgwhite">

				<div class="flex-string wp20 gray2 fs-12 pt7 right-text"></div>
				<div class="flex-string wp80 pl10 relativ">

					<div class="checkbox">

						<label>
							<input name="closetask" type="checkbox" id="closetask" value="yes">
							<span class="custom-checkbox"><i class="icon-ok"></i></span>
							Закрыть активные напоминания?
						</label>

					</div>

					<div class="fs-09 gray-dark mt10">Закрываются только Ваши напоминания и напоминания ответственного за сделку (если это не вы)</div>

				</div>

			</div>

			<?= $wrn ?>
			
			<div id="todoBoxExpress" class="mb20">
				
				<div class="flex-container mt20 mb20">
					
					<div class="flex-string wp100">
						<div id="divider" class="red text-center">
							<b class="blue">Добавить напоминание</b>
						</div>
					</div>
				
				</div>
				
				<div class="flex-container box--child mt10">
					
					<div class="flex-string wp20 gray2 fs-12 pt7 right-text">Тема:</div>
					<div class="flex-string wp80 pl10">
						<INPUT name="todo[theme]" id="todo[theme]" type="text" value="<?= $title ?>" placeholder="Укажите тему напоминания" class="wp95">
						<div class="em gray2 fs-09">Например: <b>Узнать как дела</b></div>
					</div>
				
				</div>
				
				<hr>
				
				<div class="flex-container box--child mt10">
					
					<div class="flex-string wp20 gray2 fs-12 pt7 right-text">К исполнению:</div>
					<div class="flex-string wp80 pl10 relativ">
						
						<input name="todo[datumtime]" type="text" class="inputdatetime required1" id="todo[datumtime]" value="<?= $thistime ?>" onclick="$('.datumTasksView').empty().hide()" onchange="getDateTasksNew('todo\\[datumtime\\]')" autocomplete="off">
						
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
								<input type="checkbox" name="todo[alert]" id="todo[alert]" value="yes" <?php if ($alert == 'no' || $usersettings['taskAlarm'] == 'yes') {
									print "checked";
								} ?>>
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
						print $element -> UsersSelect("todo[touser]", [
								"class"   => ['wp95'],
								"active"  => true,
								"sel"     => $iduser1,
								"noempty" => true
						]);
						?>
					
					</div>
				
				</div>
				
				<div class="flex-container box--child mt10">
					
					<div class="flex-string wp20 gray2 fs-12 pt7 right-text">Тип напоминания:</div>
					<div class="flex-string wp80 pl10">
						
						<select name="todo[tip]" id="todo[tip]" class="wp95 required1" data-change="activities" data-id="todo[des]">
							<?php
							$res = $db -> getAll("SELECT * FROM {$sqlname}activities WHERE filter IN ('all','task') and identity = '$identity' ORDER by aorder");
							foreach ($res as $data) {
								
								//$s = ( $data[ 'id' ] == $actDefault ) ? "selected" : "";
								//print '<option value="'.$data[ 'title' ].'" '.$s.' style="color:'.$data[ 'color' ].'">'.$data[ 'title' ].'</option>';
								
								print '<option value="'.$data['title'].'" '.($data['id'] == $actDefault ? "selected" : "").' style="color:'.$data['color'].'" data-color="'.$data['color'].'" data-icon="'.get_ticon($data['title'], '', true).'">'.$data['title'].'</option>';
								
							}
							?>
						</select>
					
					</div>
				
				</div>
				
				<div class="flex-container box--child mt10">
					
					<div class="flex-string wp20 gray2 fs-12 pt7 right-text">Срочность:</div>
					<div class="flex-string wp80 pl10">
						
						<div class="like-input wp95">
							
							<div id="psdiv" class="speed">
								
								<input type="hidden" id="todo[speed]" name="todo[speed]" value="0" data-id="speed">
								<div class="but black w100 text-center" id="sp1" title="Не срочно" onclick="setPS('speed','1')">
									<i class="icon-down-big"></i>&nbsp;Не срочно
								</div>
								<div class="but black active w100 text-center" id="sp0" title="Обычно" onclick="setPS('speed','0')">
									<i class="icon-check-empty"></i>&nbsp;Обычно
								</div>
								<div class="but black w100 text-center" id="sp2" title="Срочно" onclick="setPS('speed','2')">
									<i class="icon-up-big"></i>&nbsp;Срочно
								</div>
							
							</div>
						
						</div>
					
					</div>
				
				</div>
				
				<div class="flex-container box--child mt10">
					
					<div class="flex-string wp20 gray2 fs-12 pt7 right-text">Важность:</div>
					<div class="flex-string wp80 pl10">
						
						<div class="like-input wp95">
							
							<div id="psdiv" class="priority">
								
								<input type="hidden" id="todo[priority]" name="todo[priority]" value="0" data-id="priority">
								<div class="but black w100 text-center" id="pr1" title="Не важно" onclick="setPS('priority','1')">
									<i class="icon-down-big"></i>&nbsp;Не важно
								</div>
								<div class="but black active w100 text-center" id="pr0" title="Обычно" onclick="setPS('priority','0')">
									<i class="icon-check-empty"></i>&nbsp;Обычно
								</div>
								<div class="but black w100 text-center" id="pr2" title="Важно" onclick="setPS('priority','2')">
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
						
						<textarea name="todo[des]" id="todo[des]" rows="4" class="required1 wp95 pr20" style="height:120px;" placeholder="Здесь можно указать детали напоминания - что именно надо сделать?"><?= $des ?></textarea>
						
						<!--<div id="tagbox" class="gray1 fs-09 mt5" data-id="todo[des]" data-tip="todotip"></div>-->
					
					</div>
				
				</div>
				
				<div class="space-50"></div>
			
			</div>

		</DIV>

		<div class="button--pane text-right">

			<a href="javascript:void(0)" onclick="$('#dealForm').trigger('submit')" class="button">Выполнить</a>&nbsp;
			<a href="javascript:void(0)" onclick="DClose()" class="button">Отмена</a>

		</div>

	</form>

	<script>

		$.get('/content/helpers/deal.helpers.php?action=getStepFields&did=' + $('#did').val() + '&step=close', function (data) {

			if (!data)
				$('#stepfields').addClass('hidden');
			else {

				$('#stepfields').removeClass('hidden');
				$('#stepfields').html(data);

			}

		}).done(function () {
			$('#dialog').center();
		});

		$(function () {

			getResSel();
			
			getDateTasksNew('todo\\[datumtime\\]');
			
			$('#todo\\[des\\]').autoHeight(120);
			
			//$(document).find('select[data-change="activities"]').trigger('change')
			
			$("#todo\\[theme\\]").autocomplete("/content/core/core.tasks.php?action=theme", {
				autoFill: false,
				minChars: 0,
				cacheLength: 1,
				max: 100,
				selectFirst: false,
				multiple: false,
				delay: 500,
				matchSubset: 3,
				matchContains: true
			});

			/*
			$('.ydropDown[data-change="activities"]').each(function () {
	
				var $el = $(this).data('selected');
				var $tip = $(this).data('id');
				$('#tagbox[data-tip="' + $tip + '"]').empty().load('/content/core/core.tasks.php?action=itags&tip=' + urlEncodeData($el));
	
			});
			
			$(document).on('change', 'select[data-change="activities"]', function () {
				var $el = $(this).data('id');
				$('#tagbox[data-id="' + $el + '"]').empty().load('/content/core/core.tasks.php?action=itags&tip=' + urlEncodeData($('option:selected', this).val()));
			});
			*/

		});

		$('#coid').off('change');
		$('#coid').on('change', function () {

			var value = $(this).val();

			if (value !== '') {

				$('#kol_fact').val('0,00');
				$('#marga').val('0,00');
				$('#sum_conc').removeClass('hidden');

			}
			else {

				$('#kol_fact').val('<?= num_format( $kol ) ?>');
				$('#marga').val('<?= num_format( $marga ) ?>');
				$('#sum_conc').addClass('hidden');

			}

			$('#dialog').center();

		});

	</script>
	<?php

	$hooks -> do_action( "deal_form_close_after", $_REQUEST );

}

// массовые действия со сделками
if ( $action == "mass" ) {

	$id  = $_REQUEST['ch'];
	$ids = implode( ",", (array)$id );
	$kol = $_REQUEST['count'];
	?>
	<div class="zagolovok"><b>Групповое действие</b></div>

	<form action="/content/core/core.deals.php" id="dealForm" name="dealForm" method="post" enctype="multipart/form-data">
		<input name="ids" id="ids" type="hidden" value="<?= $ids ?>">
		<input name="action" id="action" type="hidden" value="deal.mass">

		<DIV id="formtabs" class="box--child" style="overflow-y: auto; max-height:80vh;">

			<div class="infodiv mb10">
				<b class="red">Важная инфрмация:</b>
				<ul>
					<li class="Bold blue">При нажатой клавише Ctrl можно мышкой выбрать нужные записи</li>
					<li>Отмена групповых действий не возможна</li>
					<li>Действия будут применены только для записей, к которым у вас есть доступ</li>
					<li>Ограничение для действия составляет 1000 записей</li>
				</ul>
			</div>

			<!--Старая реализация-->
			<div class="flex-container box--child mt10 mb10 hidden">

				<div class="flex-string wp20 gray2 fs-12 pt7 right-text">Действие:</div>
				<div class="flex-string wp80 pl10">
					<select name="doAction1" id="doAction1" class="wp97" onchange="showd()">
						<option value="">--выбор действия--</option>
						<?php
						if ( !$userRights['nouserchange'] ) {

							print '<option value="userChange" selected>Смена ответственного</option>';

						}
						?>
						<option value="stepChange">Смена этапа</option>
						<option value="datumChange">Смена плановой даты</option>
						<option value="dostupChange">Предоставить доступ сотруднику</option>
					</select>
				</div>

			</div>

			<!--Новая реализация-->
			<div class="flex-container mb10">

				<!--<div class="flex-string wp20 gray2 fs-12 pt7 right-text">Действие:</div>-->
				<div class="flex-string wp100 pl10">

					<div class="flex-container box--child wp95--5">

						<?php
						if ( !$userRights['nouserchange'] ) {
							?>
							<div class="flex-string p10 mr5 mb5 flx-basis-30 viewdiv bgwhite inset bluebg-sub" data-type="check">

								<div class="radio">
									<label>
										<span class="hidden">
											<input name="doAction" type="radio" id="doAction" value="userChange" checked onchange="showd()">
											<span class="custom-radio success1"><i class="icon-radio-check"></i></span>
										</span>
										<span class="title"><i class="icon-user-1 blue"></i>&nbsp;Ответственный</span>
									</label>
								</div>

							</div>
						<?php } ?>

						<div class="flex-string p10 mr5 mb5 flx-3 viewdiv bgwhite inset" data-type="check">

							<div class="radio">
								<label>
									<span class="hidden">
										<input name="doAction" type="radio" id="doAction" value="dostupChange" onchange="showd()">
										<span class="custom-radio success1"><i class="icon-radio-check"></i></span>
									</span>
									<span class="title"><i class="icon-lock-open green"></i>&nbsp;Предоставить Доступ</span>
								</label>
							</div>

						</div>

						<div class="flex-string p10 mr5 mb5 flx-3 viewdiv bgwhite inset" data-type="check">

							<div class="radio">
								<label>
									<span class="hidden">
										<input name="doAction" type="radio" id="doAction" value="dostupDelete" onchange="showd()">
										<span class="custom-radio success1"><i class="icon-radio-check"></i></span>
									</span>
									<span class="title"><i class="icon-lock red"></i>&nbsp;Удалить Доступ</span>
								</label>
							</div>

						</div>

						<div class="flex-string p10 mr5 mb5 flx-3 viewdiv bgwhite inset" data-type="check">

							<div class="radio">
								<label>
									<span class="hidden">
										<input name="doAction" type="radio" id="doAction" value="stepChange" onchange="showd()">
										<span class="custom-radio success1"><i class="icon-radio-check"></i></span>
									</span>
									<span class="title"><i class="icon-forward-1 broun"></i>&nbsp;Этап</span>
								</label>
							</div>

						</div>

						<div class="flex-string p10 mr5 mb5 flx-3 viewdiv bgwhite inset" data-type="check">

							<div class="radio">
								<label>
									<span class="hidden">
										<input name="doAction" type="radio" id="doAction" value="datumChange" onchange="showd()">
										<span class="custom-radio success1"><i class="icon-radio-check"></i></span>
									</span>
									<span class="title"><i class="icon-calendar-1 green"></i>&nbsp;Плановая&nbsp;дата</span>
								</label>
							</div>

						</div>

					</div>

				</div>

			</div>

			<div id="divider">Опции</div>

			<div class="flex-container box--child mt10 mb10" id="userdiv">

				<div class="flex-string wp20 gray2 fs-12 pt7 right-text">Новый:</div>
				<div class="flex-string wp80 pl10">
					<?php
					$element = new Elements();
					$exclude = ($isadmin == 'on' || stripos( $tipuser, 'Руководитель' ) !== false) ? 0 : $iduser1;
					print $element -> UsersSelect( "newuser", [
						"class"   => "wp95",
						"active"  => true,
						"sel"     => "-1",
						"exclude" => $exclude
					] );
					?>
				</div>

			</div>
			<div class="flex-container box--child mt10 mb10 hidden" id="dostupdiv">

				<div class="flex-string wp20 gray2 fs-12 pt7 right-text">Доступ для:</div>
				<div class="flex-string wp80 pl10">
					<?php
					$users = new Elements();
					print $users -> UsersSelect( "duser", [
						"class"   => "wp95",
						"active"  => true,
						"sel"     => "",
						//"exclude" => $iduser1
					] );
					?>
				</div>

			</div>
			<div class="flex-container box--child mt10 mb10 hidden" id="datdiv">

				<div class="flex-string wp20 gray2 fs-12 pt7 right-text">Плановая дата:</div>
				<div class="flex-string wp80 pl10">

					<input name="datum_plan" type="text" class="required inputdate w140" id="datum_plan" placeholder="Дата реализации">

				</div>

			</div>
			<div class="flex-container box--child mt10 mb10 hidden" id="stepdiv">

				<div class="flex-string wp20 gray2 fs-12 pt7 right-text">Этап:</div>
				<div class="flex-string wp80 pl10">

					<select name="nstep" id="nstep" class="wp95">
						<?php
						$result = $db -> query( "SELECT * FROM {$sqlname}dogcategory WHERE identity = '$identity' ORDER BY title" );
						while ($dat = $db -> fetch( $result )) {
							?>
							<option value="<?= $dat['idcategory'] ?>"><?= $dat['title']."% - ".$dat['content'] ?></option>
						<?php } ?>
					</select>
					<div class="viewdiv mt5">
						<?php
						//<b class="red">Важно:</b> В случае отсутствия указанного этапа в Воронке конкретной сделки будет принят ближайший меньший существующий этап
						?>
						<b class="red">Важно:</b> Действие будет применено только в случае если в Воронке для конкретной сделки имеется указанный этап
					</div>

				</div>

			</div>

			<div class="flex-container mb10 pt15 warning bgwhite">

				<div class="flex-string wp20 gray2 fs-12 right-text">Выполнить для:</div>
				<div class="flex-string wp80 pl10">

					<div class="flex-container">

						<div class="flex-string wp40 pl10">
							<div class="radio">
								<label>
									<input name="isSelect" id="isSelect" value="doSelected" type="radio" <?php if ( $kol > 0 )
										print "checked"; ?>>
									<span class="custom-radio success1"><i class="icon-radio-check"></i></span>
									<span class="title">Выбранного (<b class="blue"><?= $kol ?></b>)</span>
								</label>
							</div>
						</div>
						<div class="flex-string wp40 pl10">
							<div class="radio" title="Действие возможно для 500 записей максимум">
								<label>
									<input name="isSelect" id="isSelect" value="doAll" type="radio" <?php if ( $kol == 0 )
										print "checked"; ?>>
									<span class="custom-radio success1"><i class="icon-radio-check"></i></span>
									<span class="title">Всех записей (<b class="blue"><span id="counts"></span></b> из <span id="alls"></span>)</span>
								</label>
							</div>
						</div>

					</div>

				</div>

			</div>

			<div class="flex-container mb10" id="appendix">

				<div class="flex-string wp20 gray2 fs-12 pt7 right-text">также:</div>
				<div class="flex-string wp80 pl10">

					<div class="infodiv bgwhite wp95">

						<div class="checkbox mb10">
							<label>
								<input name="credit_send" id="credit_send" value="yes" type="checkbox">
								<span class="custom-checkbox"><i class="icon-ok"></i></span>
								&nbsp;Включить Активные счета (предыдущего Ответственного)
							</label>
						</div>

						<div class="checkbox">
							<label>
								<input name="todo_send" id="todo_send" value="yes" type="checkbox">
								<span class="custom-checkbox"><i class="icon-ok"></i></span>
								&nbsp;Включить Напоминания (предыдущего Ответственного)
							</label>
						</div>

					</div>

				</div>

			</div>
			<div class="flex-container box--child mt10 mb10 hidden" id="reazon">

				<div class="flex-string wp20 gray2 fs-12 pt7 right-text">Причина:</div>
				<div class="flex-string wp80 pl10">
					<textarea name="reazon" id="reazon" class="wp95"></textarea>
				</div>

			</div>

		</div>

		<hr>

		<div class="text-right button--pane">

			<a href="javascript:void(0)" onclick="massSubmit()" class="button">Сохранить</a>&nbsp;
			<a href="javascript:void(0)" onclick="DClose()" class="button">Отмена</a>

		</div>
	</form>
	<script>
		$('input[type="radio"]')
			.off('change')
			.on('change', function (){

				var xprop = $(this).prop('checked');

				$('div[data-type="check"]').removeClass('bluebg-sub')

				if(xprop){
					$(this).closest('div[data-type="check"').addClass('bluebg-sub')
				}

			})
	</script>
	<?php
}

if ( $action == "change.dostup" ) {

	//текущий ответственный
	$iduser = getDogData( $did, 'iduser' );

	//список пользователей, которые имеют доступ
	$dostup = $db -> getCol( "SELECT iduser FROM {$sqlname}dostup WHERE did = '".$did."' and identity = '$identity'" );
	?>
	<DIV class="zagolovok"><B>Доступ к <?= $lang['face']['DealName'][4] ?></B></DIV>
	<form action="/content/core/core.deals.php" id="dealForm" name="dealForm" method="post" enctype="multipart/form-data">
		<input type="hidden" id="action" name="action" value="deal.change.dostup">
		<input name="did" type="hidden" id="did" value="<?= $did ?>">

		<div class="row ha bluebg-sub">
			<div class="column grid-1 div-center"></div>
			<div class="column grid-6 blue"></div>
			<div class="column grid-3 em div-center">Уведомления</div>
		</div>

		<div style="overflow-y:auto;max-height:60vh">
			<?php
			$i = 0;

			$result = $db -> query( "SELECT * FROM {$sqlname}user where identity = '$identity' ORDER by field(secrty, 'yes', 'no'), title" );
			while ($data = $db -> fetch( $result )) {

				$subscribe = $db -> getOne( "SELECT subscribe FROM {$sqlname}dostup WHERE did = '".$did."' and iduser = '".$data['iduser']."' and identity = '$identity'" );

				$g = ($subscribe == 'on') ? "checked" : '';
				$t = ($data['secrty'] != 'yes') ? '<b class="red">N/a:</b> ' : '';
				$s = (in_array( $data['iduser'], (array)$dostup )) ? "checked" : '';

				?>
				<div class="row ha bgwhite mb2">
					<div class="column grid-7 blue">
						<label class="pt5 pb5">
							<input type="checkbox" name="user[<?= $i ?>]" id="user[<?= $i ?>]" <?= $s ?> value="<?= $data['iduser'] ?>"/>&nbsp;&nbsp;<?= $t.$data['title'] ?>
						</label>
					</div>
					<div class="column grid-3 em div-center">
						<label class="pt5 pb5">
							<input type="checkbox" name="notify[<?= $i ?>]" id="notify[<?= $i ?>]" <?= $g ?> value="on"/>&nbsp;Да
						</label>
					</div>
				</div>
				<?php
				$i++;
			}
			?>
		</div>

		<div class="pad10 bgwhite em fs-09">

			<span class="red">* Пользователи без доступа получать уведомления об изменениях в Сделке не будут</span>

		</div>

		<hr>

		<div class="text-right button--pane">

			<A href="javascript:void(0)" onclick="$('#dealForm').trigger('submit')" class="button">Сохранить</A>&nbsp;
			<A href="javascript:void(0)" onclick="DClose()" class="button">Отмена</A>

		</div>
	</form>
	<?php
}
if ( $action == "change.user" ) {

	$iduser = getDogData( $did, 'iduser' );

	$reazonReq = (!$otherSettings['changeUserComment']) ? 'class="required"' : '';

	?>
	<DIV class="zagolovok"><B>Изменить Ответственного</B></DIV>

	<form action="/content/core/core.deals.php" method="post" enctype="multipart/form-data" name="dealForm" id="dealForm">
		<input type="hidden" id="action" name="action" value="deal.change.user">
		<input name="did" type="hidden" id="did" value="<?= $did ?>">
		<input name="olduser" type="hidden" id="olduser" value="<?= $iduser ?>">

		<DIV id="formtabs" class="box--child" style="overflow-y: auto; max-height:80vh;">

			<div class="flex-container mb10 mt20">

				<div class="flex-string wp20 gray2 fs-12 pt7 right-text">Сотрудник:</div>
				<div class="flex-string wp80 pl10">
					<?php
					$users = new Elements();
					print $users -> UsersSelect( "newuser", [
						"class"   => "wp97 required",
						"active"  => true,
						"sel"     => "-1",
						"exclude" => $iduser
					] );
					?>
				</div>

			</div>

			<hr>

			<div class="flex-container mb10">

				<div class="flex-string wp20 gray2 fs-12 pt10 right-text">Опции:</div>
				<div class="flex-string box--child wp80 pl10">

					<div class="flex-container wp99">

						<div class="flex-string checkbox inline viewdiv mb5 mr10">
							<label>
								<input type="checkbox" name="client_send" id="client_send" value="yes" checked>
								<span class="custom-checkbox"><i class="icon-ok"></i></span>
								<span class="pl10">передать Клиента</span>
							</label>
						</div>

						<div class="flex-string checkbox inline viewdiv mb5 mr10">
							<label>
								<input type="checkbox" name="person_send" id="person_send" value="yes" checked>
								<span class="custom-checkbox"><i class="icon-ok"></i></span>
								<span class="pl10">передать Контакты</span>
							</label>
						</div>

						<div class="flex-string checkbox inline viewdiv mb5 mr10">
							<label>
								<input type="checkbox" name="todo_send" id="todo_send" value="yes">
								<span class="custom-checkbox"><i class="icon-ok"></i></span>
								<span class="pl10">передать Напоминания (предыдущего Ответственного)</span>
							</label>
						</div>

					</div>

				</div>

			</div>

			<div class="flex-container">

				<div class="flex-string wp20 gray2 fs-12 pt7 right-text">Причина передачи:</div>
				<div class="flex-string wp80 pl10">
					<textarea id="reason" name="reason" rows="3" class="wp97 <?= $reazonReq ?>"></textarea>
				</div>

			</div>

		</div>

		<hr>

		<div class="text-right button--pane">

			<A href="javascript:void(0)" onclick="$('#dealForm').trigger('submit')" class="button">Сохранить</A>&nbsp;
			<A href="javascript:void(0)" onclick="DClose()" class="button">Отмена</A>

		</div>

	</FORM>
	<?php
}

if ( $action == "change.datum_plan" ) {

	$datum = getDogData( $did, 'datum_plan' );

	?>
	<DIV class="zagolovok"><B>Изменить Плановую дату</B></DIV>

	<form action="/content/core/core.deals.php" method="post" enctype="multipart/form-data" name="dealForm" id="dealForm">
		<input type="hidden" id="action" name="action" value="deal.change.dplan">
		<input name="did" type="hidden" id="did" value="<?= $did ?>">

		<DIV id="formtabs" class="box--child" style="overflow-y: auto; max-height:80vh;">

			<div class="flex-container mb10 mt20">

				<div class="flex-string wp20 gray2 fs-12 pt7 right-text">Плановая дата:</div>
				<div class="flex-string wp80 pl10">
					<?php
					print Elements::Date( "newdate", $datum, ["class" => "wp97 required"] );
					?>
				</div>

			</div>

			<hr>

			<div class="flex-container">

				<div class="flex-string wp20 gray2 fs-12 pt7 right-text">Причина изменения:</div>
				<div class="flex-string wp80 pl10">
					<textarea id="reason" name="reason" rows="3" class="wp97 required"></textarea>
				</div>

			</div>

		</div>

		<hr>

		<div class="text-right button--pane">

			<A href="javascript:void(0)" onclick="$('#dealForm').trigger('submit')" class="button">Сохранить</A>&nbsp;
			<A href="javascript:void(0)" onclick="DClose()" class="button">Отмена</A>

		</div>

	</FORM>
	<?php
}
if ( $action == "change.step" ) {

	$did     = $_REQUEST['did'];
	$newStep = $_REQUEST['newstep'];
	$next    = $_REQUEST['next'];

	$mFunnel = getMultiStepList( ["did" => $did] );
	$ss      = !empty( (array)$mFunnel['steps'] ) ? " and idcategory IN (".implode( ",", array_keys( (array)$mFunnel['steps'] ) ).")" : "";

	$comment = '';

	//если для сделки нет воронки, то берем общую воронку
	if ( empty( (array)$mFunnel['steps'] ) ) {

		//находим текущий статус и его значение
		$currentStep = getDogData( $did, "idcategory" );

		$oldStep = getPrevNextStep( $currentStep );

		//если новый этап не задан, но расчитаем следующий
		$newStep = ($newStep < 1) ? getPrevNextStep( $currentStep, 'next' ) : getPrevNextStep( $newStep );

		if ( (int)$oldStep['title'] > (int)$newStep['title'] ) {
			$cpDo = 'yes';
		}

	}
	else {

		$currentStep = (int)$mFunnel['current']['id'];
		$oldStep     = $mFunnel['current'];

		//если новый этап есть в воронке для этой сделки
		if ( array_key_exists( $newStep, (array)$mFunnel['steps'] ) ) {

			//если новый этап не задан, но расчитаем следующий
			$newStep = ($newStep < 1) ? $mFunnel['next'] : getPrevNextStep( $newStep );

		}
		else {

			$newStep = getPrevNextStep( $newStep );
			$comment = 'Выбранный этап - '.$newStep['title'].'% ('.$newStep['content'].') отсутствует в воронке для выбранной сделки.';
			$newStep = $mFunnel['next'];

		}

		if ( (int)$oldStep['title'] > (int)$newStep['title'] ) {
			$cpDo = 'yes';
		}

	}

	$reazonReq = (!$otherSettings['changeDealComment']) ? 'required' : '';

	?>
	<div class="zagolovok">Изменение этапа</div>

	<div class="infodiv">
		<b><?= $lang['face']['DealName'][0] ?>:</b>&nbsp;
		<a href="javascript:void(0)" onclick="openDogovor('<?= $did ?>')"><i class="icon-briefcase-1 broun"></i><?= getDogData( $did, 'title' ) ?></a>
	</div>

	<hr>

	<form action="/content/core/core.deals.php" method="post" enctype="multipart/form-data" name="dealForm" id="dealForm">
		<input name="action" id="action" type="hidden" value="deal.change.step"/>
		<input name="did" id="did" type="hidden" value="<?= $did ?>"/>
		<input name="next" id="next" type="hidden" value="<?= $next ?>"/>

		<div class="box--child" style="max-height:70vh; overflow-x: hidden; overflow-y:auto !important">

			<?php
			$hooks -> do_action( "deal_form_changestep_before", $_REQUEST );
			?>

			<div class="flex-container mb10">

				<div class="flex-string wp20 gray2 fs-12 pt7 right-text">Текущий этап:</div>
				<div class="flex-string wp80 pl10 fs-12 pt7">
					<?= "<b>".$oldStep['title']."%</b> - ".$oldStep['content'] ?>
				</div>

			</div>

			<hr>

			<div class="flex-container mb10 mt10">

				<div class="flex-string wp20 gray2 fs-12 pt7 right-text">Новый этап:</div>
				<div class="flex-string wp80 pl10">
					<select name="idcategory" id="idcategory" class="required wp95">
						<option value="">--Выбор--</option>
						<?php
						$result = $db -> query( "SELECT * FROM {$sqlname}dogcategory WHERE identity = '$identity' $ss ORDER BY CAST(title as SIGNED)" );
						while ($data = $db -> fetch( $result )) {

							$s = ($data['title'] == $newStep['title'] || $data['title'] == $newStep) ? 'selected' : '';
							$d = ($data['idcategory'] == $currentStep) ? 'disabled' : '';

							print '<option value="'.$data['idcategory'].'" '.$s.' '.$d.'>'.$data['title']."%-".$data['content'].'</option>';

						}
						?>
					</select>
				</div>

				<?php
				if ( $comment != '' ) {
					print '<div class="warning fs-09 wp97"><b class="red">Внимание:</b> '.$comment.'</div>';
				}
				?>

			</div>

			<hr>

			<div class="flex-container mb10">

				<div class="flex-string wp20 gray2 fs-12 right-text">Комментарий:</div>
				<div class="flex-string wp80 pl10">
					<textarea name="description" rows="4" id="description" class="<?= $reazonReq ?> wp95"></textarea>
				</div>

			</div>

			<div id="stepfields" class="viewdiv hidden m10">1</div>

			<?php
			//найдем категорию Кт. соответствующие текущему этапу
			$res     = $db -> getRow( "SELECT * FROM {$sqlname}complect_cat WHERE dstep = '".$idcategory."' and identity = '$identity'" );
			$ccid    = (int)$res["ccid"];
			$cctitle = $res["title"];

			//найдем КТ, которая есть в этой сделке
			$res  = $db -> getRow( "SELECT * FROM {$sqlname}complect WHERE ccid = '".$ccid."' and did = '".$_REQUEST['did']."' and identity = '$identity' ORDER BY id" );
			$cpid = (int)$res["id"];

			if ( $cpid > 0 ) {
				?>
				<div class="flex-container mb10">

					<div class="flex-string wp97 pl10">
						<div class="infodiv wp97">Связанная контрольная точка "<b class="red"><?= $cctitle ?></b>" будет отмечена выполненной, если новый этап выше старого.
						</div>
						<input name="cpid" id="cpid" type="hidden" value="<?= $cpid ?>"/>

					</div>

				</div>
			<?php } ?>

		</div>

		<hr>

		<div class="text-right button--pane">

			<a href="javascript:void(0)" onclick="$('#dealForm').trigger('submit')" class="button">Сохранить</a>&nbsp;
			<a href="javascript:void(0)" onclick="DClose()" class="button">Отмена</a>

		</div>

	</form>
	<?php

	$hooks -> do_action( "deal_form_changestep_after", $_REQUEST );

}
if ( $action == "change.period" ) {

	//следующий период
	$p = getPeriodDeal( (int)$did );

	$dstart = getDogData( (int)$did, 'datum_start' );
	$dend   = getDogData( (int)$did, 'datum_end' );
	?>
	<DIV class="zagolovok"><B>Период <?= $lang['face']['DealName'][1] ?></B></DIV>
	<FORM method="post" action="/content/core/core.deals.php" enctype="multipart/form-data" name="dealForm" id="dealForm">
		<input name="action" id="action" type="hidden" value="deal.change.period">
		<input name="did" id="did" type="hidden" value="<?= $did ?>">

		<div class="box--child">

			<div class="space-20"></div>

			<div class="flex-container mb10" id="dperiod">

				<div class="flex-string wp10 gray2 fs-12"></div>

				<div class="flex-string wp40">

					<div class="gray fs-07">Начало периода</div>
					<input type="text" name="dstart" class="dstart required inputdate wp95" id="dstart" value="<?= $dstart ?>">

				</div>
				<div class="flex-string wp40">

					<div class="gray fs-07">Конец периода</div>
					<input type="text" name="dend" class="dend required inputdate wp95" id="dend" value="<?= $dend ?>">

				</div>

				<div class="flex-string wp10 gray2 fs-12"></div>

				<div class="flex-string wp100 text-center mt10">

					<a href="javascript:void(0)" onclick="setNextPeriod()" class="button dotted greenbtn m0">Следующий период</a>

				</div>

			</div>

			<hr>

			<div class="flex-container mb10">

				<div class="flex-string wp100 text-center">

					<select name="period" id="period" data-goal="dperiod" data-action="period">
						<option selected="selected">-выбор-</option>

						<option disabled="disabled">---------------------------</option>

						<option data-period="calendarweek">Неделя текущая</option>
						<option data-period="calendarweeknext">Неделя следующая</option>

						<option data-period="month">Месяц текущий</option>
						<option data-period="monthnext">Месяц следующий</option>

						<option data-period="quart">Квартал текущий</option>
						<option data-period="quartnext">Квартал следующий</option>

						<option data-period="year">Год</option>
					</select>
					<div class="gray fs-07">Быстрый выбор</div>

				</div>

			</div>

		</div>

		<hr>

		<DIV class="button--pane text-right">

			<A href="javascript:void(0)" onclick="$('#dealForm').trigger('submit')" class="button">Сохранить</A>&nbsp;
			<A href="javascript:void(0)" onclick="DClose();" class="button">Отмена</A>

		</DIV>
	</FORM>
	<script>

		function setNextPeriod() {

			$('#dstart').val('<?= $p[0] ?>');
			$('#dend').val('<?= $p[1] ?>');

		}

	</script>
	<?php
}
if ( $action == "change.freeze" ) {

	?>
	<DIV class="zagolovok">Укажите дату разморозки</DIV>
	<FORM method="post" action="/content/core/core.deals.php" enctype="multipart/form-data" name="dealForm" id="dealForm">
		<input name="action" id="action" type="hidden" value="deal.freeze">
		<input name="did" id="did" type="hidden" value="<?= $did ?>">

		<div class="box--child">

			<div class="space-20"></div>

			<div class="flex-container mb10" id="dperiod">

				<div class="flex-string wp100 text-center">

					<input type="text" name="date" class="required inputdate w200" id="dstart" value="">

				</div>

			</div>

		</div>

		<hr>

		<DIV class="button--pane text-right">

			<A href="javascript:void(0)" onclick="$('#dealForm').trigger('submit')" class="button">Сохранить</A>&nbsp;
			<A href="javascript:void(0)" onclick="DClose();" class="button">Отмена</A>

		</DIV>
	</FORM>
	<script>
	</script>
	<?php
}

if ( $action == "append.contract" ) {


	$idtype = $db -> getOne( "SELECT id FROM {$sqlname}contract_type where type = 'get_dogovor' and identity = '$identity'" );

	$clid = (int)getDogData( $did, 'clid' );
	$pid  = (int)getDogData( $did, 'pid' );
	?>
	<div class="zagolovok">Привязка договора</div>
	<form method="post" action="/content/core/core.deals.php" enctype="multipart/form-data" name="dealForm" id="dealForm" autocomplete="off">
		<input name="action" id="action" type="hidden" value="deal.append.contract"/>
		<input name="did" id="did" type="hidden" value="<?= $did ?>"/>

		<div class="box--child">

			<div class="flex-container mb10">

				<div class="flex-string wp20 gray2 fs-12 pt7 right-text">Договор:</div>
				<div class="flex-string wp80 pl10">
					<?php
					if ( $clid > 0 ) {
						$ss = "clid = '".$clid."' and ";
					}
					elseif ( $pid > 0 ) {
						$ss = "pid = '".$pid."' and";
					}
					else {
						$ss = '';
					}

					$result = $db -> query( "SELECT * FROM {$sqlname}contract WHERE ".$ss." idtype = '".$idtype."' and identity = '$identity'" );
					if ( $db -> affectedRows() > 0 ) {
						?>
						<select name="deid" id="deid" class="wp95">
							<option value="">--б/н--</option>
							<?php
							while ($data = $db -> fetch( $result )) {

								print '<option value="'.$data['deid'].'">'.$data['number'].'</option>';

							}
							?>
						</select>
						<?php
					}
					else print 'С данным клиентом нет договоров.&nbsp;';
					?>
				</div>

			</div>

		</div>

		<hr>

		<DIV class="button--pane text-right">

			<?php if ( $db -> numRows( $result ) > 0 ) { ?>
				<a href="javascript:void(0)" onclick="$('#dealForm').trigger('submit')" class="button">Сохранить</a>&nbsp;
			<?php } ?>
			<a href="javascript:void(0)" onclick="DClose()" class="button">Отмена</a>

		</div>

	</form>
	<?php
}

if ( $action == "credit.edit" ) {

	$crid = (int)$_REQUEST['crid'];

	$spekaSumNotDo = 0;
	$spekaSumDo    = 0;
	$ndsCredit     = 0;

	if ( $crid > 0 ) {

		$credit           = $db -> getRow( "SELECT * FROM {$sqlname}credit WHERE crid = '$crid' and identity = '$identity'" );
		$credit["suffix"] = htmlspecialchars_decode( $credit["suffix"] );

		$did = (int)$credit['did'];

		$deal = Deal::info($did);

		$isper = (isServices( (int)$credit["did"] )) ? 'yes' : '';

		if ( $credit['template'] == '' ) {
			$credit['templatefile'] = 'invoice.tpl';
		}

		$nalogScheme = getNalogScheme( (int)$credit['rs'] );
		//$Speka       = getSpekaData($credit["did"]);

		$ndsDefault = $nalogScheme['nalog'];

		//$ndsDefault = $db -> getOne( "SELECT ndsDefault FROM {$sqlname}mycomps_recv WHERE cid = '".$credit['mcid']."' and isDefault='yes' and identity = '$identity'");


		if ( $Speka['summaInvoice'] == 0 ) {
			$nds_proc = $nalogScheme['nalog'];
		}
		elseif ( (float)$credit['nds_credit'] <= 0 && (float)$nalogScheme['nalog'] > 0 ) {
			$nds_proc = $nalogScheme['nalog'];
		}
		else {
			$nds_proc = 0;
		}

		$nds_credit = $credit['nds_credit'];
		$nds_proc   = $ndsDefault;

		if ( (float)$credit['nds_credit'] == 0 ) {
			$nds_proc = 0;
		}


		$result         = $db -> getRow( "SELECT mcid, calculate FROM {$sqlname}dogovor WHERE did = '$did' and identity = '$identity'" );
		$credit['mcid'] = (int)$result["mcid"];
		//$calculate      = $result["calculate"];

		//$ns    = ($ndsRaschet == 'yes') ? 'Налог (ндс)' : 'в т.ч. налог (ндс)';

		//Проверим НДС у данной компании
		$ndsDefault = $db -> getOne( "SELECT ndsDefault FROM {$sqlname}mycomps_recv WHERE cid = '".$credit['mcid']."' and isDefault='yes' and identity = '$identity'" );

		$ns        = ($ndsRaschet == 'yes') ? ' налог (НДС)' : ' в т.ч. налог (НДС)';
		$ndsa      = getNalog( $spekaSumNotDo, $ndsDefault, $ndsRaschet );
		$ndsCredit = $ndsa['nalog'];

		/*
		 * счета, кроме текущего
		 */
		$spekaSumDo = (float)$db -> getOne( "SELECT SUM(summa_credit) as summa FROM {$sqlname}credit WHERE did = '$credit[did]' and crid != '$crid' and do = 'on' and identity = '$identity'" );

		/**
		 * НДС по спецификации
		 */
		$spekaData = Speka ::getNalog( (int)$credit["did"] );
		//print_r($spekaData);

		/*
		 * процент налога от суммы спецификации
		 * он может отличаться в смешанных счетах (есть позиции с ндс и без)
		 */
		$nalogPercent = $spekaData['summa'] > 0 ? $spekaData['nalog'] / $spekaData['summa'] : 0;

		/*
		 * налог, который должен быть с учетом уже выставленных счетов
		 */
		$nalogNotDo = ($spekaData['summa'] - $spekaSumDo) * $nalogPercent;

		$nalogPercent = $nds_proc;

	}
	else {

		$crid = 0;

		$deal = Deal::info($did);

		$nalogScheme = getNalogScheme( 0, (int)$deal['mcid'] );

		$credit['mcid'] = (int)$deal["mcid"];

		$user  = getDogData( (int)$did, 'iduser' );
		$isper = (isServices( (int)$did )) ? 'yes' : '';

		$invoiceNumberFormat = $db -> getOne( "SELECT iformat FROM {$sqlname}settings WHERE id = '$identity'" );
		$contractNumber      = $db -> getOne( "SELECT number FROM {$sqlname}contract WHERE deid = '$deal[dog_num]' and identity = '$identity'" );

		$suffix = file_get_contents( $rootpath.'/cash/'.$fpath.'templates/invoice_suffix.htm' );

		// для обычных сделок считаем остаток не оплаченной суммы
		if ( $isper != 'yes' ) {

			$spekaSumDo    = $db -> getOne( "SELECT SUM(summa_credit) FROM {$sqlname}credit WHERE did = '$did' and identity = '$identity'" );
			$spekaSumNotDo = pre_format( $deal['kol'] ) - pre_format( $spekaSumDo );

		}
		// для сервисных сделок сумма счета равна сумме спецификации
		else {

			$result = $db -> query( "SELECT kol, price, dop FROM {$sqlname}speca WHERE did = '$did' and identity = '$identity'" );
			while ($data = $db -> fetch( $result )) {

				$spekaSumNotDo += pre_format( $data['kol'] ) * pre_format( $data['price'] ) * pre_format( $data['dop'] );

			}

		}

		$credit['invoice_chek'] = $contractNumber;
		$credit['datum']        = current_datumtime();
		$credit['datum_credit'] = current_datum( -5 );
		$credit['summa_credit'] = $spekaSumNotDo;
		$credit['rs']           = $db -> getOne( "SELECT id FROM {$sqlname}mycomps_recv WHERE cid = '$deal[mcid]' and isDefault = 'yes' and identity = '$identity'" );

		//Проверим НДС у данной компании
		$ndsDefault = $db -> getOne( "SELECT ndsDefault FROM {$sqlname}mycomps_recv WHERE cid = '".$deal['mcid']."' and isDefault='yes' and identity = '$identity'" );

		$ns        = ($ndsRaschet == 'yes') ? ' налог (НДС)' : ' в т.ч. налог (НДС)';
		$ndsa      = getNalog( $spekaSumNotDo, (float)$ndsDefault, $ndsRaschet );
		$ndsCredit = $ndsa['nalog'];


		if ( $spekaSumNotDo == 0 ) {

			print '
				<div class="zagolovok">Добавить счет</div>
				
				<div class="div-center miditxt pad10">
					<b class="red">Внимание!</b><br>
					Сумма имеющихся счетов или стоимость сделки не предусматривают создание дополнительного счета.
				</div>
				
				<hr>
				
				<div class="text-right">
					<a href="javascript:void(0)" onclick="DClose()" class="button"><span>Закрыть</span></a>
				</div>
			';

			exit();

		}

		//$ns = ($ndsRaschet == 'yes' ? 'Налог' : 'в т.ч. налог');

		/**
		 * НДС по спецификации
		 */
		$spekaData = Speka ::getNalog( (int)$did );
		//print_r($spekaData);

		/*
		 * процент налога от суммы спецификации
		 * он может отличаться в смешанных счетах (есть позиции с ндс и без)
		 */
		$nalogPercent = $spekaData['summa'] > 0 ? $spekaData['nalog'] / $spekaData['summa'] : 0;

		/*
		 * налог, который должен быть с учетом уже выставленных счетов
		 */
		$nalogNotDo = ($spekaData['summa'] - $spekaSumDo) * $nalogPercent;

		$credit['template']     = ($isper == 'yes') ? $otherSettings['invoiceTempService'] : $otherSettings['invoiceTemp'];
		$credit['tip']          = 'Счет-договор';
		$credit['templatefile'] = ($isper == 'yes') ? $otherSettings['invoiceTempService'] : $otherSettings['invoiceTemp'];

	}

	$disabledNalog = ($spekaData['nalog'] < $ndsCredit) ? "disabled" : "";

	?>
	<div class="zagolovok">Изменить счет</div>
	<FORM method="post" action="/content/core/core.deals.php" enctype="multipart/form-data" name="dealForm" id="dealForm">
		<input type="hidden" id="action" name="action" value="credit.edit">
		<input type="hidden" id="crid" name="crid" value="<?= $_REQUEST['crid'] ?>">
		<input type="hidden" id="did" name="did" value="<?= $did ?>">

		<div id="formtabs" class="flex-container wp100 box--child" style="max-height:80vh; overflow-y:auto !important; overflow-x:hidden">

			<?php
			$hooks -> do_action( "invoice_form_before", $_REQUEST );
			?>

			<div class="flex-string wp50 nopad border-box">

				<?php
				if ( $credit['do'] == 'on' ) {
					print '
					<div class="flex-container redbg-sub pad10">
						<div class="text-center fs-12">
							<b class="red">Внимание!</b> Вы редактируете проведенный счет
						</div>
					</div>
					<hr>
					';
				}
				?>

				<?php
				if ( $isper != 'yes' && $crid == 0 ) {
					?>
					<div class="flex-container bluebg-sub p10 wp97">

						<div class="flex-string wp20 fs-12 pt7 gray2 right-text">
							<span class="middle">Новый этап:</span>
						</div>
						<div class="flex-string wp80 pl10">
							<?php
							$mFunnel = getMultiStepList( ["did" => $did] );
							$ss      = (!empty( (array)$mFunnel['steps'] ) ) ? " and idcategory IN (".implode( ",", array_keys( (array)$mFunnel['steps'] ) ).")" : "";
							if ( empty( (array)$mFunnel['steps'] ) ) {

								$oldStep = getDogData( $did, 'idcategory' );
								$step    = getPrevNextStep( $oldStep, 'next' );

							}
							else {

								$oldStep = $mFunnel['current']['id'];
								$step    = $mFunnel['next'];

							}
							?>
							<select name="newstep" id="newstep" class="wp95">
								<option value="<?= $oldStep ?>">--Не менять--</option>
								<?php
								$result = $db -> query( "SELECT * FROM {$sqlname}dogcategory WHERE identity = '$identity' $ss ORDER BY title" );
								while ($data = $db -> fetch( $result )) {

									$s = ($data['title'] == $newStep['title']) ? 'selected' : '';
									$d = ($data['idcategory'] == $oldStep) ? 'disabled' : '';

									print '<option value="'.$data['idcategory'].'" '.$s.' '.$d.'>'.$data['title'].'%-'.$data['content'].'</option>';

								}
								?>
							</select>

						</div>

					</div>
				<?php }
				?>

				<?php
				if ( $crid == 0 ) {
					?>
					<div id="stepfields" class="viewdiv1 hidden mt10 mb20 p10"></div>
				<?php }
				?>

				<?php
				if ( $isper == 'yes' ) {//указываем данные для смены периода, ПОКА отключено

					$p = getPeriodDeal( (int)$did );

					if ( $otherSettings['changeDealPeriod'] == 'invoice' ) {

						$ch = 'checked';
						$hh = '';
						$hg = '';

					}
					else {

						$ch = '';
						$hh = 'hidden';
						$hg = 'hidden';

					}

					?>
					<div class="divider mt10 mb10 <?= $hg ?>">Период сделки</div>

					<div class="bluebg-sub pt10 <?= $hg ?>">

						<div class="flex-container pt10 pb10">

							<div class="flex-string wp20 pt7 fs-12 gray2 right-text">&nbsp;</div>
							<div class="flex-string wp80 checkbox">
								<label>
									<input type="checkbox" name="changePeriod" id="changePeriod" <?= $ch ?> value="yes" onclick="$('#per').toggleClass('hidden'); $('#dialog').center()"/>
									<span class="custom-checkbox"><i class="icon-ok"></i></span>
									Изменить период сделки
								</label>
							</div>

						</div>

						<div class="flex-container pt10 pb10" id="per">

							<div class="flex-string wp20 pt7">&nbsp;</div>
							<div class="flex-string wp80 flex-container pb10">

								<div class="flex-string wp50">
									<input type="text" id="dstart" name="dstart" class="required inputdate wp90" value="<?= $p[0] ?>">
									<label for="dstart" class="em gray2 fs-09">Начало периода</label>
								</div>
								<div class="flex-string wp50">
									<input type="text" id="dend" name="dend" class="required inputdate wp90" value="<?= $p[1] ?>">
									<label for="dend" class="em gray2 fs-09">Конец периода</label>
								</div>

							</div>

						</div>

					</div>
					<?php
				}
				?>

				<?php
				$list = [];

				if ( $deal['idcurrency'] > 0 ) {

					$currency = (new Currency) -> currencyInfo( $deal['idcurrency'] );

					$courses = Currency ::currencyLog( $deal['idcurrency'], 10 );
					foreach ( $courses as $item ) {

						$list[] = [
							"id"    => $item['id'],
							"title" => $item['course'].' [ '.$item['date'].' ]'
						];

					}

				}

				if ( !empty( $list ) ) {
					?>
					<div class="flex-container p10">

						<div class="flex-string wp20 gray2 fs-12 pt7 right-text">Курс ( <?php echo $currency['symbol'] ?> ):</div>
						<div class="flex-string wp80 pl10 relativ">

							<?php
							print Elements ::Select( 'dogovor[idcourse]', $list, [
								"class"     => 'wp95',
								'req'       => '0',
								//"emptyValue" => 0,
								//"emptyText"  => "Не использовать (1:1)",
								"sel"       => $deal['idcourse'],
								'nowrapper' => true
							] );
							?>

						</div>

					</div>
					<?php

				}
				?>

				<div class="flex-container p10">

					<div class="flex-string wp20 fs-12 gray2 pt7 right-text">Куратор:</div>
					<div class="flex-string wp80 pl10">
						<?php
						$users = new Elements();
						print $users -> UsersSelect( "user", [
							"class"  => "wp95 required",
							"active" => true,
							"sel"    => $credit['iduser']
						] );
						?>
					</div>

				</div>

				<div class="flex-container box--child p10 float">

					<div class="flex-string wp20 fs-12 gray2 pt7 right-text">№ счета:</div>
					<div class="flex-string float pl10">
						<input name="invoice" type="text" class="sum wp95" id="invoice" value="<?= $credit['invoice'] ?>">
					</div>
					<?php if ( $invoiceNumberFormat != '' && $crid == 0 ) { ?>
						<div class="flex-string wp35 checkbox pt10 pl10">
							<label title="Номер счета будет присвоен автоматически">
								<input name="igen" id="igen" type="checkbox" value="yes" checked>
								<span class="custom-checkbox"><i class="icon-ok"></i></span>
								авто. (№ <b class="blue"><?= generate_num( 'invoice' ) ?></b>)
							</label>
						</div>
					<?php } ?>

				</div>

				<div class="flex-container p10">

					<div class="flex-string wp20 fs-12 gray2 pt7 right-text"><span class="middle">Дата счета:</span>
					</div>
					<div class="flex-string wp80 pl10">
						<input name="datum" class="required inputdate yw120" type="date" id="datum" value="<?= get_smdate( $credit['datum'] ) ?>">
					</div>

				</div>

				<?php if ( $otherSettings['price'] && $otherSettings['printInvoice'] ) { ?>
					<div class="flex-container p10">

						<div class="flex-string wp20 fs-12 gray2 pt7 right-text">
							<span class="middle">Тип платежа:</span></div>
						<div class="flex-string wp80 pl10">
							<select name="tip" id="tip" class="required wp95">
								<option value="">--выбор--</option>
								<option value="Предварительная оплата" <?php if ( $credit['tip'] == 'Предварительная оплата' )
									print 'selected' ?>>Предварительная оплата
								</option>
								<option value="Окончательная оплата" <?php if ( $credit['tip'] == 'Окончательная оплата' )
									print 'selected' ?>>Окончательная оплата
								</option>
								<option value="По спецификации" <?php if ( $credit['tip'] == 'По спецификации' )
									print 'selected' ?>>По спецификации
								</option>
								<option value="По договору" <?php if ( $credit['tip'] == 'По договору' )
									print 'selected' ?>>По договору
								</option>
								<option value="Счет-договор" <?php if ( $credit['tip'] == 'Счет-договор' )
									print 'selected' ?>>Счет-договор
								</option>
							</select>
						</div>

					</div>
				<?php } ?>

				<div class="flex-container p10">

					<div class="flex-string wp20 fs-12 gray2 pt7 right-text">Шаблон:</div>
					<div class="flex-string wp80 pl10">
						<select name="template" id="template" class="required wp95">
							<?php
							$ires = $db -> query( "SELECT * FROM {$sqlname}contract_temp WHERE typeid IN (SELECT id FROM {$sqlname}contract_type WHERE type IN ('invoice') AND identity = '$identity') AND identity = '$identity' ORDER by title" );
							while ($data = $db -> fetch( $ires )) {

								print '<option value="'.$data['id'].'" '.($credit['template'] == $data['id'] || $credit['templatefile'] == $data['file'] ? 'selected' : '').'>'.$data['title'].'</option>';

							}
							?>
						</select>
					</div>

				</div>

				<div class="flex-container p10">

					<div class="flex-string wp20 fs-12 gray2 pt7 right-text"><span class="middle">Дата план.:</span>
					</div>
					<div class="flex-string wp80 pl10">
						<input name="datum_credit" class="required inputdate yw120" type="date" id="datum_credit" value="<?= $credit['datum_credit'] ?>" size="10">&nbsp;<span class="blue pl10">Для контроля оплаты</span>
					</div>

				</div>

				<div class="flex-container p10">

					<div class="flex-string wp20 fs-12 gray2 pt7 right-text"><span class="middle">Сумма счета:</span>
					</div>
					<div class="flex-string wp80 pl10">
						<input name="summa_credit" type="text" class="required yw120" id="summa_credit" value="<?= num_format( $credit['summa_credit'] ) ?>" onKeyUp="GetNns()"><span class="pl5 fs-12 gray2"><?= $valuta ?></span>
					</div>

				</div>

				<div class="flex-container p10">

					<div class="flex-string wp20 fs-12 gray2 pt7 right-text">
						<span class="middle"><?= ($ndsRaschet == 'yes' ? 'Налог' : 'в т.ч. налог') ?>:</span>
					</div>
					<div class="flex-string wp80 pl10 relativ">

						<input name="nds_credit" type="text" class="required yw80" id="nds_credit" value="<?= num_format( $nalogNotDo ) ?>"><span class="pl5 fs-12 gray2"><?= $valuta ?></span>
						<label for="textfield" class="fs-12 gray2 pl5 inline">или </label>
						<input type="text" name="nds_proc" id="nds_proc" value="<?= $nalogScheme['nalog'] ?>" size="3" onKeyUp="GetNns()" <?= $disabledNalog ?>><span class="pl5 fs-12 gray2">%</span>

						<div class="tagsmenuToggler hand mr15 pull-aright" data-id="fhelper">
							<span class="fs-10 blue"><i class="icon-help-circled"></i></span>
							<div class="tagsmenu top fly right hidden" id="fhelper" style="right: 10px">
								<div class="blok p10 w250 fs-09">
									Как посчитан налог:<br>
									<div class="pl5">
										* Налог расчитан в соответствие со спецификацией и ранее оплаченных счетов<br>
										* Также учтена ставка налога для текущей компании<br>
										* Поле отключается, если сумму налога невозможно посчитать в %% от суммы счета
									</div>
								</div>
							</div>
						</div>

					</div>

				</div>

				<?php if ( $credit['invoice_chek'] != '' ) { ?>
					<div class="flex-container p10">

						<div class="flex-string wp20 fs-12 gray2 pt7 right-text"><span class="middle">Договор:</span>
						</div>
						<div class="flex-string wp80 pl10">
							<input name="invoice_chek" type="text" class="sum wp90" id="invoice_chek" value="<?= $credit['invoice_chek'] ?>">
						</div>

					</div>
				<?php } ?>

				<div class="flex-container p10">

					<div class="flex-string wp20 fs-12 gray2 pt7 right-text">
						<span class="middle">Расч.счет:</span>
					</div>
					<div class="flex-string wp80 pl10">
						<select name="rs" id="rs" class="required wp95">
							<option value="">--выбор--</option>
							<?php
							$x = !empty($userRights['dostup']['rc']) ? " (SELECT COUNT(*) FROM {$sqlname}mycomps_recv WHERE cid = mc.id AND id IN (".yimplode(",", $userRights['dostup']['rc']).") ) > 0 AND " : "";
							$result = $db -> query( "
								SELECT
									mc.id,
									mc.name_shot
								FROM {$sqlname}mycomps `mc`
								WHERE 
								    mc.id = '".$deal['mcid']."' AND 
								    $x
								    mc.identity = '$identity' 
								ORDER BY mc.name_shot
							" );
							while ($data = $db -> fetch( $result )) {
								?>
								<optgroup label="<?= $data['name_shot'] ?>">
									<?php
									$z = !empty($userRights['dostup']['rc']) ? " id IN (".yimplode(",", $userRights['dostup']['rc']).") AND " : "";
									$res = $db -> query( "SELECT * FROM {$sqlname}mycomps_recv WHERE cid = '".$data['id']."' AND $z identity = '$identity' ORDER BY title" );
									while ($da = $db -> fetch( $res )) {

										//если включен модуль бюджет, сотрудник имеет доступ к нему
										$ostatok = $userRights['budjet'] ? ': '.num_format( $da['ostatok'] ).' '.$valuta : '';

										$s = ($credit['rs'] == $da['id']) ? 'selected' : '';

										print '<option value="'.$da['id'].'" '.$s.' data-mcid="'.$data['id'].'">'.$da['title'].$ostatok.'</option>';

									}
									?>
								</optgroup>
							<?php } ?>
						</select>
					</div>

				</div>

				<?php
				$signers = getSigner( 0, (int)$credit['mcid'] );
				if ( !empty( $signers ) ) {
					?>
					<div class="flex-container p10">

						<div class="flex-string wp20 fs-12 gray2 pt7 right-text"><span class="middle">Подписант:</span>
						</div>
						<div class="flex-string wp80 pl10">
							<select name="signer" id="signer" class="wp95">
								<option value="">--выбор--</option>
								<?php
								foreach ( $signers as $xsigners ) {

									foreach ( $xsigners as $xsigner ) {

										print '<option value="'.$xsigner['id'].'" '.($credit['signer'] == $xsigner['id'] ? 'selected' : '').'>'.$xsigner['signature'].': '.$xsigner['status'].'</option>';

									}

								}
								?>
							</select>
						</div>

					</div>
				<?php } ?>

				<div class="space-100"></div>

			</div>
			<div class="flex-string wp50 nopad pr10">

				<div class="sticked--top block wp100">

					<div class="mb5 Bold table p5 wp100">
						Суффикс счета
						<div class="pull-aright">
							<a href="javascript:void(0)" title="Вставить из шаблона" onclick="addSuffix()"><b class="broun"><i class="icon-doc-text"></i>Из шаблона</b></a>&nbsp;&nbsp;
							<a href="javascript:void(0)" title="Действия" class="tagsmenuToggler"><b class="blue">Вставить тэг</b>&nbsp;<i class="icon-angle-down" id="mapii"></i></a>
							<div class="tagsmenu hidden">
								<ul>
									<li title="Ответственный. ФИО"><b class="broun">{{UserName}}</b></li>
									<li title="Ответственный. Должность"><b class="broun">{{UserStatus}}</b></li>
									<li title="Ответственный. Телефон"><b class="broun">{{UserPhone}}</b></li>
									<li title="Ответственный. Мобильный"><b class="broun">{{UserMob}}</b></li>
									<li title="Ответственный. Email"><b class="broun">{{UserEmail}}</b></li>

									<li title="Юридическое название нашей компании"><b class="red">{{compUrName}}</b>
									</li>
									<li title="Краткое юр. название нашей компании"><b class="red">{{compShotName}}</b>
									</li>
									<li title="Наш юр.адрес"><b class="red">{{compUrAddr}}</b></li>
									<li title="Наш почтовый адрес"><b class="red">{{compFacAddr}}</b></li>
									<li title="ИНН нашей компании"><b class="red">{{compInn}}</b></li>
									<li title="КПП нашей компании"><b class="red">{{compKpp}}</b></li>
									<li title="ОГРН нашей компании"><b class="red">{{compOgrn}}</b></li>
									<li title="ОКПО нашей компании"><b class="red">{{compOkpo}}</b></li>
									<li title="Наш банк"><b class="red">{{compBankName}}</b></li>
									<li title="БИК нашего банка"><b class="red">{{compBankBik}}</b></li>
									<li title="наш Расчетный счет"><b class="red">{{compBankRs}}</b></li>
									<li title="Корр.счет нашего банка"><b class="red">{{compBankKs}}</b></li>
									<li title="ФИО руководителя (В контексте «в лице кого»)">
										<b class="red">{{compDirName}}</b></li>
									<li title="Должность руководителя (Директор, Генеральный директор)">
										<b class="red">{{compDirStatus}}</b></li>
									<li title="Должность руководителя (краткая, Петров И.И.)">
										<b class="red">{{compDirSignature}}</b></li>
									<li title="На основании чего действует руководитель (Устава, Доверенности..)">
										<b class="red">{{compDirOsnovanie}}</b></li>
									<li title="Название Бренда"><b class="red">{{compBrand}}</b></li>
									<li title="Сайт Бренда"><b class="red">{{compSite}}</b></li>
									<li title="Email Бренда"><b class="red">{{compMail}}</b></li>
									<li title="Телефон Бренда"><b class="red">{{compPhone}}</b></li>

									<li title="Название Клиента (Как отображается в CRM)">
										<b class="blue">{{castName}}</b>
									</li>
									<li title="Юридическое название Клиента (из реквизитов)">
										<b class="blue">{{castUrName}}</b></li>
									<li title="ИНН Клиента (из реквизитов)"><b class="blue">{{castInn}}</b></li>
									<li title="КПП Клиента (из реквизитов)"><b class="blue">{{castKpp}}</b></li>
									<li title="Банк Клиента (из реквизитов)"><b class="blue">{{castBank}}</b></li>
									<li title="Кор.счет Клиента (из реквизитов)"><b class="blue">{{castBankKs}}</b></li>
									<li title="Расч.счет Клиента (из реквизитов)"><b class="blue">{{castBankRs}}</b>
									</li>
									<li title="БИК банка Клиента (из реквизитов)"><b class="blue">{{castBankBik}}</b>
									</li>
									<li title="ОКПО Клиента (из реквизитов)"><b class="blue">{{castOkpo}}</b></li>
									<li title="ОГРН Клиента (из реквизитов)"><b class="blue">{{castOgrn}}</b></li>
									<li title="ФИО руководителя Клиента, в родительном падеже (в лице кого) - Иванова Ивана Ивановича (из реквизитов)">
										<b class="blue">{{castDirName}}</b></li>
									<li title="ФИО руководителя Клиента, например Иванов И.И. (из реквизитов)">
										<b class="blue">{{castDirSignature}}</b></li>
									<li title="Должность руководителя Клиента, в род.падеже, например: Директора (из реквизитов)">
										<b class="blue">{{castDirStatus}}</b></li>
									<li title="Должность руководителя Клиента, например: Директор (из реквизитов)">
										<b class="blue">{{castDirStatusSig}}</b></li>
									<li title="Основание прав Руководителя Клиента, в родительном падеже - Устава, Доверенности №ХХХ от ХХ.ХХ.ХХХХ г. (из реквизитов)">
										<b class="blue">{{castDirOsnovanie}}</b></li>
									<li title="Юр.адрес Клиента (из реквизитов)"><b class="blue">{{castUrAddr}}</b></li>
									<li title="Фактич.адрес Клиента (из реквизитов)"><b class="blue">{{castFacAddr}}</b>
									</li>

									<li title="Заказчик. Название (Как отображается в CRM)">
										<b class="blue">{{castomerFtitle}}</b></li>
									<li title="Заказчик. Адрес"><b class="blue">{{castomerFaddress}}</b></li>
									<li title="Заказчик. Телефон"><b class="blue">{{castomerFphone}}</b></li>
									<li title="Заказчик. Факс"><b class="blue">{{castomerFfax}}</b></li>
									<li title="Заказчик. Email"><b class="blue">{{castomerFmail_url}}</b></li>
									<li title="Заказчик. Сайт"><b class="blue">{{castomerFsite_url}}</b></li>

									<?php
									$re = $db -> getAll( "select * from {$sqlname}field where fld_tip='client' and fld_on='yes' and fld_name LIKE '%input%' and identity = '".$identity."' order by fld_order" );
									foreach ( $re as $d ) {

										print '<li title="Заказчик. '.$d['fld_title'].'"><b class="blue">{{castomerF'.$d['fld_name'].'}}</b></li>';

									}
									?>
									<li title="Номер счета (из сделки)"><b class="green">{{Invoice}}</b></li>
									<li title="Дата счета (в формате: 29 февраля 2014 года)">
										<b class="green">{{InvoiceDate}}</b></li>
									<li title="Дата счета (в формате: 29.02.2014)">
										<b class="green">{{InvoiceDateShort}}</b>
									</li>
									<li title="Дата оплаты плановая (в формате: 29 февраля 2014 года)">
										<b class="green">{{InvoiceDatePlan}}</b></li>
									<li title="Дата оплаты плановая (в формате: 29.02.2014)">
										<b class="green">{{InvoiceDatePlanShort}}</b></li>
									<!--<li title="Номер акта (из сделки)"><b class="green">{{AktNumber}}</b></li>-->
									<!--<li title="Дата акта (из сделки)"><b class="green">{{AktDate}}</b></li>-->
									<li title="Сумма прописью (сумма сделки)">
										<b class="green">{{InvoiceSummaPropis}}</b>
									</li>
									<li title="Общая сумма сделки (из сделки)"><b class="green">{{InvoiceSumma}}</b>
									</li>
									<li title="Сумма позиций счета (из счета). При налоге 'сверху' не включает налог">
										<b class="green">{{ItogSumma}}</b></li>
									<!--<li title="Сумма НДС (из сделки)"><b class="green">{{summa_nds}}</b></li>-->
									<li title="Сумма НДС (из сделки)"><b class="green">{{nalogSumma}}</b></li>
									<li title="Название налога (например, в т.ч. НДС)">
										<b class="green">{{nalogName}}</b>
									</li>
									<li title="Название налога (например, НДС)"><b class="green">{{nalogTitle}}</b></li>
									<li title="Номер договора (из сделки)"><b class="green">{{ContractNumber}}</b></li>
									<li title="Дата договора (из сделки)"><b class="green">{{ContractDate}}</b></li>
									<li title="Название сделки"><b class="green">{{dealFtitle}}</b></li>
									<li title="Сумма сделки"><b class="green">{{dealFsumma}}</b></li>
									<li title="Маржа сделки"><b class="green">{{dealFmarga}}</b></li>
									<?php
									$res = $db -> getAll( "select * from {$sqlname}field where fld_tip='dogovor' and fld_name LIKE '%input%' and fld_on='yes' and identity = '".$GLOBALS['identity']."' order by fld_order" );
									foreach ( $res as $data ) {

										print '<li title="'.$data['fld_title'].'"><b class="green">{{dealF'.$data['fld_name'].'}}</b></li>';

									}
									?>
									<li title="Период. Начало (из сделки)"><b class="green">{{dealFperiodStart}}</b>
									</li>
									<li title="Период. Конец (из сделки)"><b class="green">{{dealFperiodEnd}}</b></li>
								</ul>
							</div>
						</div>
					</div>
					<div class="wp100">
						<textarea name="suffix" id="suffix" style="height:400px"><?= $credit["suffix"] ?></textarea>
					</div>

				</div>

			</div>

		</div>

		<hr>

		<div class="button--pane pull-aright">

			<a href="javascript:void(0)" onclick="SaveInvoice()" class="button">Сохранить</a>&nbsp;
			<a href="javascript:void(0)" onclick="DClose(); remEditor();" class="button">Отмена</a>

		</div>
	</form>
	<script>

		$(function () {

			$('#newstep').trigger('change');

		});

		$('#newstep').on('change', function () {

			if ($('#stepfields').is('div')) {

				$.get('/content/helpers/deal.helpers.php?action=getStepFields&did=' + $('#did').val() + '&idcategory=' + $('option:selected', this).val(), function (data) {

					$('#stepfields').html(data);

					if (!data)
						$('#stepfields').addClass('hidden');
					else
						$('#stepfields').removeClass('hidden');

					$('.inputdatetime').each(function () {

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
							closeText: '<i class="icon-ok-circled"></i>',
							dateFormat: 'yy-mm-dd',
							firstDay: 1,
							dayNamesMin: ['Вс', 'Пн', 'Вт', 'Ср', 'Чт', 'Пт', 'Сб'],
							monthNamesShort: ['Январь', 'Февраль', 'Март', 'Апрель', 'Май', 'Июнь', 'Июль', 'Август', 'Сентябрь', 'Октябрь', 'Ноябрь', 'Декабрь'],
							changeMonth: true,
							changeYear: true,
							yearRange: '1940:2030',
							minDate: new Date(1940, 1 - 1, 1)
						});

					});
					$('.inputdate').each(function () {

						if (!isMobile) $(this).datepicker({
							dateFormat: 'yy-mm-dd',
							numberOfMonths: 2,
							firstDay: 1,
							dayNamesMin: ['Вс', 'Пн', 'Вт', 'Ср', 'Чт', 'Пт', 'Сб'],
							monthNamesShort: ['Январь', 'Февраль', 'Март', 'Апрель', 'Май', 'Июнь', 'Июль', 'Август', 'Сентябрь', 'Октябрь', 'Ноябрь', 'Декабрь'],
							changeMonth: true,
							changeYear: true,
							yearRange: '1940:2030',
							minDate: new Date(1940, 1 - 1, 1),
							showButtonPanel: true,
							currentText: 'Сегодня',
							closeText: 'Готово'
						});

					});

					$('input[data-type="address"]').each(function () {

						$(this).suggestions({
							token: $dadata,
							type: "ADDRESS",
							count: 5,
							formatResult: formatResult,
							formatSelected: formatSelected,
							onSelect: function (suggestion) {

								//console.log(suggestion);

							},
							addon: "clear",
							geoLocation: true
						});

					});

					if (!isMobile) {

						$(".multiselect").multiselect({sortable: true, searchable: true});
						$(".connected-list").css('max-height', "200px");

					}

				})
					.done(function () {
						$('#dialog').center();
					});

			}

		});

	</script>
	<?php

	$hooks -> do_action( "invoice_form_after", $_REQUEST );

}
if ( $action == "credit.express" ) {

	$deal = Deal::info($did);

	$isper = (isServices( (int)$did )) ? 'yes' : '';

	//Проверим НДС у данной компании
	$ndsDefault = $db -> getOne( "SELECT ndsDefault FROM {$sqlname}mycomps_recv WHERE cid = '".$deal['mcid']."' and isDefault='yes' and identity = '$identity'" );

	$csum = $db -> getOne( "SELECT SUM(summa_credit) FROM {$sqlname}credit WHERE did='".$did."' and identity = '$identity' ORDER by crid" );

	$ckol = pre_format( $deal['kol'] ) - pre_format( $csum );

	$dog_numm = $db -> getOne( "SELECT number FROM {$sqlname}contract WHERE deid='".$deal['dog_num']."' and identity = '$identity'" );

	$tip        = 'По спецификации';
	$ns         = ($ndsRaschet == 'yes') ? ' налог' : ' в т.ч. налог';
	$ndsa       = getNalog( $ckol, $ndsDefault, $ndsRaschet );
	$nds_credit = $ndsa['nalog'];

	if ( $ckol == 0 ) {

		print '
		<div class="zagolovok">Добавить счет</div>
		<div class="text-center">
			<b class="red">Внимание!</b><br>
			Сумма имеющихся счетов или стоимость сделки не предусматривают создание дополнительного счета.
		</div>
		<hr>
		<div class="text-right">
			<a href="javascript:void(0)" onclick="DClose()" class="button">Закрыть</a>
		</div>
		';

		exit();
	}

	/**
	 * НДС по спецификации
	 */
	$spekaData = Speka ::getNalog( $did );
	//print_r($spekaData);

	/*
	 * процент налога от суммы спецификации
	 * он может отличаться в смешанных счетах (есть позиции с ндс и без)
	 */
	$nalogPercent = $spekaData['summa'] > 0 ? $spekaData['nalog'] / $spekaData['summa'] : 0;

	/*
	 * налог, который должен быть с учетом уже выставленных счетов
	 */
	$nalogNotDo = ($spekaData['summa'] - $csum) * $nalogPercent;

	$currency   = [];
	$course     = 1;
	$valutaOrig = $valuta;
	$ckolOrig   = $ckol;

	$template = $otherSettings['aktTemp'];

	?>
	<div class="zagolovok">Внесение оплаты</div>
	<FORM method="post" action="/content/core/core.deals.php" enctype="multipart/form-data" name="dealForm" id="dealForm">
		<input name="did" type="hidden" id="did" value="<?= $did ?>">
		<input type="hidden" id="action" name="action" value="credit.express">

		<div id="formtabs" class="wp100 box--child" style="max-height:80vh; overflow-y:auto !important; overflow-x:hidden">

			<?php
			$hooks -> do_action( "invoice_form_express_before", $_REQUEST );
			?>

			<div class="flex-container box--child mt10">

				<div class="flex-string wp20 fs-12 gray2 pt7 right-text">№ счета:</div>
				<div class="flex-string wp40 pl10">
					<input name="invoice" type="text" class="sum wp100" id="invoice" value="">
				</div>
				<div class="flex-string wp40 checkbox pt10 pl10">
					<label title="Номер счета будет присвоен автоматически">
						<input name="igen" id="igen" type="checkbox" value="yes"/>
						<span class="custom-checkbox"><i class="icon-ok"></i></span>
						авто. (№ <b class="blue"><?= generate_num( 'invoice' ) ?></b>)
					</label>
				</div>

			</div>

			<div class="flex-container box--child mt10">

				<div class="flex-string wp20 gray2 fs-12 pt7 right-text">Куратор:</div>
				<div class="flex-string wp80 pl10 norelativ">
					<?php
					$users = new Elements();
					print $users -> UsersSelect( "user", [
						"class"  => "wp95 required",
						"active" => true,
						"sel"    => $deal['iduser']
					] );
					?>
				</div>

			</div>

			<div class="flex-container box--child mt10">

				<div class="flex-string wp20 gray2 fs-12 pt7 right-text">Дата платежа:</div>
				<div class="flex-string wp80 pl10 norelativ">
					<input name="datum" class="required inputdate wp30" type="date" id="datum" value="<?= current_datum() ?>" size="10">
				</div>

			</div>

			<?php if ( $otherSettings['price'] && $otherSettings['printInvoice'] ) { ?>
				<div class="flex-container box--child mt10">

					<div class="flex-string wp20 gray2 fs-12 pt7 right-text">Тип платежа:</div>
					<div class="flex-string wp80 pl10 norelativ">
						<select name="tip" id="tip" class="required wp95">
							<option value="">--выбор--</option>
							<option value="Предварительная оплата" <?php if ( $deal['tip'] == 'Предварительная оплата' )
								print 'selected' ?>>Предварительная оплата
							</option>
							<option value="Окончательная оплата" <?php if ( $deal['tip'] == 'Окончательная оплата' )
								print 'selected' ?>>Окончательная оплата
							</option>
							<option value="По спецификации" <?php if ( $deal['tip'] == 'По спецификации' )
								print 'selected' ?>>По спецификации
							</option>
							<option value="По договору" <?php if ( $deal['tip'] == 'По договору' )
								print 'selected' ?>>По договору
							</option>
							<option value="Счет-договор" selected>Счет-договор</option>
						</select>
					</div>

				</div>
			<?php } ?>

			<div class="flex-container box--child mt10">

				<div class="flex-string wp20 fs-12 gray2 pt7 right-text">Шаблон:</div>
				<div class="flex-string wp80 pl10">
					<select name="template" id="template" class="required wp95">
						<?php
						$ires = $db -> query( "SELECT * FROM {$sqlname}contract_temp WHERE typeid IN (SELECT id FROM {$sqlname}contract_type WHERE type IN ('invoice') AND identity = '$identity') AND identity = '$identity' ORDER by title" );
						while ($data = $db -> fetch( $ires )) {

							print '<option value="'.$data['id'].'" '.($credit['template'] == $data['file'] ? 'selected' : '').'>'.$data['title'].'</option>';

						}
						?>
					</select>
				</div>

			</div>

			<?php
			// Пока закроем, т.к. есть проблемы с конвертацией в связи с округлением
			if ( (int)$deal['idcurrency'] > 0 ) {

				$currency = (new Currency) -> currencyInfo( $deal['idcurrency'] );

				$ckol2       = Currency ::currencyConvert( $ckol, $deal['idcourse'], false, false );
				$nalogNotDo2 = Currency ::currencyConvert( $nalogNotDo, $deal['idcourse'], false, false );

				$course  = (new Currency) -> courseInfo( $deal['idcourse'] )['course'];
				$valuta2 = $currency['symbol'];

				?>

				<div class="flex-container box--child mt10 infodiv p0 pt10 pb10">

					<div class="flex-string wp20 gray2 fs-12 pt7 right-text">В валюте:</div>
					<div class="flex-string wp80 pl10 norelativ">

						<div class="like-input wp95 pt10 pb10 fs-11">
							<div class="inline Blue">Сумма: <b><?= num_format( $ckol2 ) ?></b> <?= $valuta2 ?>,</div>
							<div class="inline"><?= $ns ?>: <b><?= num_format( $nalogNotDo2 ) ?></b> <?= $valuta2 ?></div>
						</div>

					</div>


				</div>
			<?php } ?>

			<div class="flex-container box--child mt10">

				<div class="flex-string wp20 gray2 fs-12 pt7 right-text">Сумма оплаты, <?= $valuta ?>:</div>
				<div class="flex-string wp80 pl10 norelativ">

					<input name="summa_credit" type="text" class="required w160" id="summa_credit" onKeyUp="GetNns()" value="<?= num_format( $ckol ) ?>" size="15">&nbsp;

					<div class="inline w250 checkbox pt10 pl10">
						<label title="Номер счета будет присвоен автоматически">
							<input name="createDelta" id="createDelta" type="checkbox" value="yes"/>
							<span class="custom-checkbox"><i class="icon-ok"></i></span>
							Добавить счет на разницу
						</label>
					</div>

				</div>


			</div>

			<div class="flex-container box--child mt10">

				<div class="flex-string wp20 gray2 fs-12 pt7 right-text"><?= $ns ?>, <?= $valuta ?>:</div>
				<div class="flex-string wp30 pl10 norelativ">
					<input name="nds_credit" type="text" class="required w160" id="nds_credit" value="<?= num_format( $nalogNotDo ) ?>" size="15">&nbsp;
				</div>
				<div class="flex-string wp50 pl10 relativ">

					<div class="gray2 fs-12 pt7 inline">Налог:</div>
					<input type="text" name="nds_proc" id="nds_proc" value="<?= $ndsDefault ?>" class="w80" onKeyUp="GetNns()"> %

					<div class="tagsmenuToggler hand pt7 mr15 pull-aright" data-id="fhelper">
						<span class="fs-10 blue"><i class="icon-help-circled"></i> Подсказка</span>
						<div class="tagsmenu top fly right hidden" id="fhelper" style="right: 10px">
							<div class="blok p10 w250 fs-09">
								Как посчитан налог:<br>
								<div class="pl5">
									* Налог расчитан в соответствие со спецификацией и ранее оплаченных счетов<br>
									* Также учтена ставка налога для текущей компании<br>
									* Поле отключается, если сумму налога невозможно посчитать в %% от суммы счета
								</div>
							</div>
						</div>
					</div>


				</div>

			</div>

			<?php if ( $deal['dog_num'] > 0 ) { ?>
				<div class="flex-container box--child mt10">

					<div class="flex-string wp20 gray2 fs-12 pt7 right-text">№ договора:</div>
					<div class="flex-string wp80 pl10 norelativ">
						<input name="invoice_chek" type="text" class="sum wp95" id="invoice_chek" value="<?= $dog_numm ?>" size="12">
					</div>

				</div>
			<?php } ?>

			<div class="flex-container box--child mt10 mb10">

				<div class="flex-string wp20 gray2 fs-12 pt7 right-text">Расч.счет:</div>
				<div class="flex-string wp80 pl10 norelativ">
					<select name="rs" id="rs" class="required wp95">
						<option value="">--выбор--</option>
						<?php
						$x = !empty($userRights['dostup']['rc']) ? " (SELECT COUNT(*) FROM {$sqlname}mycomps_recv WHERE cid = mc.id AND id IN (".yimplode(",", $userRights['dostup']['rc']).") ) > 0 AND " : "";
						$result = $db -> query( "SELECT * FROM {$sqlname}mycomps `mc` WHERE mc.id = '".$deal['mcid']."' AND $x mc.identity = '$identity' ORDER BY mc.name_shot" );
						while ($data = $db -> fetch( $result )) {
							?>
							<optgroup label="<?= $data['name_shot'] ?>">
								<?php
								$z = !empty($userRights['dostup']['rc']) ? " id IN (".yimplode(",", $userRights['dostup']['rc']).") AND " : "";
								$res = $db -> query( "SELECT * FROM {$sqlname}mycomps_recv WHERE cid = '".$data['id']."' AND $z identity = '$identity' ORDER BY title" );
								while ($da = $db -> fetch( $res )) {

									$ostatok = $userRights['budjet'] ? ': '.num_format( $da['ostatok'] ).' '.$valutaOrig : '';
									$s       = ($da['isDefault'] == 'yes') ? 'selected' : '';

									print '<option value="'.$da['id'].'" '.$s.'>'.$da['title'].$ostatok.'</option>';

								}
								?>
							</optgroup>
						<?php } ?>
					</select>
				</div>

			</div>

			<?php
			$signers = getSigner( 0, (int)$deal['mcid'] );
			if ( !empty( $signers ) ) {
				?>
				<div class="flex-container box--child mt10 mb10">

					<div class="flex-string wp20 fs-12 gray2 pt7 right-text"><span class="middle">Подписант:</span>
					</div>
					<div class="flex-string wp80 pl10">
						<select name="signer" id="signer" class="wp95">
							<option value="">--выбор--</option>
							<?php
							foreach ( $signers as $xsigners ) {

								foreach ( $xsigners as $xsigner ) {

									print '<option value="'.$xsigner['id'].'" '.($credit['signer'] == $xsigner['id'] ? 'selected' : '').'>'.$xsigner['signature'].': '.$xsigner['status'].'</option>';

								}

							}
							?>
						</select>
					</div>

				</div>
			<?php } ?>

			<?php
			if ( $isper != 'yes' ) {

				$idtype  = $db -> getOne( "SELECT id FROM {$sqlname}contract_type WHERE type = 'get_akt' and identity = '$identity'" );
				$akt_num = generate_num( "akt" );

				$cStep       = getPrevNextStep( getDogData( $did, 'idcategory' ) );
				$stepApprove = current_dogstepname( $GLOBALS['akt_step'] );

				?>
				<hr class="m0">
				<div class="flex-container bluebg-sub box--child pt10 pb10">

					<div class="flex-string wp20 gray2 fs-12 pt7 right-text"><span class="middle">Новый этап:</span>
					</div>
					<div class="flex-string wp80 pl10 norelativ">
						<?php
						$mFunnel = getMultiStepList( ["did" => $did] );
						$ss      = !empty( (array)$mFunnel['steps'] )  ? " and idcategory IN (".implode( ",", array_keys( (array)$mFunnel['steps'] ) ).")" : "";
						if ( empty( (array)$mFunnel['steps'] ) ) {

							$oldStep = getDogData( $did, 'idcategory' );
							$newStep = getPrevNextStep( $oldStep, 'next' );

						}
						else {

							$oldStep = $mFunnel['current']['id'];
							$newStep = $mFunnel['next'];

						}
						?>
						<select name="newstep" id="newstep" class="wp95">
							<option value="<?= $oldStep ?>">--Не менять--</option>
							<?php
							$result = $db -> query( "SELECT * FROM {$sqlname}dogcategory WHERE identity = '$identity' $ss ORDER BY title" );
							while ($data = $db -> fetch( $result )) {

								$s = ($data['title'] == $newStep['title']) ? 'selected' : '';
								$d = ($data['idcategory'] == $oldStep) ? 'disabled' : '';

								print '<option value="'.$data['idcategory'].'" '.$s.' '.$d.' data-step="'.$data['title'].'">'.$data['title'].'%-'.$data['content'].'</option>';

							}
							?>
						</select>
					</div>

				</div>

				<div class="hidden" data-block="aktblock" data-currentstep="<?= $cStep['title'] ?>" data-approvedstep="<?= $stepApprove ?>">

					<div class="divider mt20 mb20">Создать акт</div>

					<div class="flex-container box--child mt10">

						<div class="flex-string wp20"></div>
						<div class="flex-string wp80 pl10">

							<label for="createAkt" class="switch">
								<input type="checkbox" name="createAkt" id="createAkt" value="yes">
								<span class="slider empty"></span>
							</label>
							<label class="inline" for="createAkt"><span class="text Bold gray2 ml10">Создать акт</span></label>

						</div>

					</div>

					<div class="space-10"></div>

					<div class="flex-container box--child mt10 hidden" data-block="akt">

						<div class="flex-string wp20 gray2 fs-12 pt7 right-text">Номер акта:</div>
						<div class="flex-string wp80 pl10 norelativ">
							<INPUT name="akt[num]" type="text" id="akt[num]" class="w120" value="<?= $akt_num ?>">&nbsp;
							<label title="Номер акта будет присвоен автоматически" class="inline"><input name="akt[igen]" id="akt[igen]" type="checkbox" value="yes" checked="checked">&nbsp;авто.</label>
							<br>
							<span class="gray2 fs-07">(Предварительный). Будет присвоен после сохранения.</span>
						</div>

					</div>

					<div class="flex-container box--child mt20 hidden" data-block="akt">

						<div class="flex-string wp20 gray2 fs-12 pt7 right-text">Шаблон:</div>
						<div class="flex-string wp80 pl10 norelativ">

							<select name="akt[temp]" id="akt[temp]" class="required wp95">
								<?php
								$ires = $db -> query( "SELECT * FROM {$sqlname}contract_temp WHERE typeid IN (SELECT id FROM {$sqlname}contract_type WHERE type IN ('get_akt') AND identity = '$identity') AND identity = '$identity' ORDER by title" );
								while ($data = $db -> fetch( $ires )) {

									print '<option value="'.$data['file'].'" '.($template == $data['file'] ? 'selected' : '').'>'.$data['title'].'</option>';

								}
								?>
							</select>

						</div>

					</div>

					<?php
					/**
					 * Статус документа
					 */
					$statuses = $db -> getAll( "SELECT * FROM {$sqlname}contract_status WHERE FIND_IN_SET('$idtype', REPLACE({$sqlname}contract_status.tip, ';',',')) > 0 AND identity = '$identity' ORDER by ord" );
					if ( !empty( $statuses ) ) {
						?>
						<div class="flex-container mb10 mt20 box--child hidden" data-block="akt">

							<div class="flex-string wp20 gray2 fs-12 pt7 right-text">Статус документа:</div>
							<div class="flex-string wp80 pl10 relativ">

								<select name="akt[status]" id="akt[status]" class="wp95">
									<?php
									foreach ( $statuses as $da ) {

										print '<option value="'.$da['id'].'" data-color="'.$da['color'].'" style="color:'.$da['color'].'">'.$da['title'].'</option>';

									}
									?>
								</select>

							</div>

						</div>
						<?php
					}

					?>

				</div>

				<?php
			}
			?>

			<div id="stepfields" class="viewdiv1 hidden1 p0 mt20 mb20"></div>

		</div>

		<hr>

		<div class="text-right button--pane">

			<a href="javascript:void(0)" onclick="$('#dealForm').trigger('submit')" class="button">Сохранить</a>&nbsp;
			<a href="javascript:void(0)" onclick="DClose()" class="button">Отмена</a>

		</div>

	</form>

	<script>

		var $course = parseFloat(<?=$course?>);
		var $aelm = $('div[data-block="aktblock"]');
		var $currentStep = $aelm.data('currentstep');
		var $approvedStep = $aelm.data('approvedstep');

		$(function () {

			$('#newstep').trigger('change');

		});

		$('#newstep').on('change', function () {

			var $newStepID = parseInt($('option:selected', this).val());
			var $newStep = $('option:selected', this).data('step');

			if ($currentStep >= $approvedStep || $newStep >= $approvedStep)
				$aelm.removeClass('hidden');

			else
				$aelm.addClass('hidden');

			if ($('#stepfields').is('div')) {

				$.get('/content/helpers/deal.helpers.php?action=getStepFields&did=' + $('#did').val() + '&idcategory=' + $newStepID, function (data) {

					$('#stepfields').html(data);

					if (!data) $('#stepfields').addClass('hidden');
					else $('#stepfields').removeClass('hidden');

					$('.inputdatetime').each(function () {

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
							closeText: '<i class="icon-ok-circled"></i>',
							dateFormat: 'yy-mm-dd',
							firstDay: 1,
							dayNamesMin: ['Вс', 'Пн', 'Вт', 'Ср', 'Чт', 'Пт', 'Сб'],
							monthNamesShort: ['Январь', 'Февраль', 'Март', 'Апрель', 'Май', 'Июнь', 'Июль', 'Август', 'Сентябрь', 'Октябрь', 'Ноябрь', 'Декабрь'],
							changeMonth: true,
							changeYear: true,
							yearRange: '1940:2030',
							minDate: new Date(1940, 1 - 1, 1)
						});

					});
					$('.inputdate').each(function () {

						if (!isMobile) $(this).datepicker({
							dateFormat: 'yy-mm-dd',
							numberOfMonths: 2,
							firstDay: 1,
							dayNamesMin: ['Вс', 'Пн', 'Вт', 'Ср', 'Чт', 'Пт', 'Сб'],
							monthNamesShort: ['Январь', 'Февраль', 'Март', 'Апрель', 'Май', 'Июнь', 'Июль', 'Август', 'Сентябрь', 'Октябрь', 'Ноябрь', 'Декабрь'],
							changeMonth: true,
							changeYear: true,
							yearRange: '1940:2030',
							minDate: new Date(1940, 1 - 1, 1),
							showButtonPanel: true,
							currentText: 'Сегодня',
							closeText: 'Готово'
						});

					});

					$('input[data-type="address"]').each(function () {

						$(this).suggestions({
							token: $dadata,
							type: "ADDRESS",
							count: 5,
							formatResult: formatResult,
							formatSelected: formatSelected,
							onSelect: function (suggestion) {

								console.log(suggestion);

							},
							addon: "clear",
							geoLocation: true
						});

					});

					if (!isMobile) {

						$(".multiselect").multiselect({sortable: true, searchable: true});
						$(".connected-list").css('max-height', "200px");

					}

				}).done(function () {
					$('#dialog').center();
				});

			}

		});

		$('#createAkt').on('change', function () {

			$('div[data-block="akt"]').toggleClass('hidden');
			$('#dialog').center();

		});

	</script>
	<?php

	$hooks -> do_action( "invoice_form_express_after", $_REQUEST );

}
if ( $action == "credit.doit" ) {

	$crid = $_REQUEST['crid'];

	$credit         = $db -> getRow( "SELECT * FROM {$sqlname}credit WHERE crid='".$_REQUEST['crid']."' and identity = '$identity'" );
	$credit['mcid'] = (int)$db -> getOne( "SELECT mcid FROM {$sqlname}dogovor WHERE did='".$credit['did']."' and identity = '$identity'" );

	$datum_credit = current_datum();
	$isper        = (isServices( (int)$credit['did'] )) ? 'yes' : '';

	$template = $otherSettings['aktTemp'];

	$deal = Deal::info($credit['did']);

	?>
	<div class="zagolovok">Отметка о поступлении платежа</div>
	<form method="post" action="/content/core/core.deals.php" enctype="multipart/form-data" name="dealForm" id="dealForm">
		<input name="action" id="action" type="hidden" value="credit.doit">
		<input name="crid" id="crid" type="hidden" value="<?= $crid ?>">
		<input name="did" id="did" type="hidden" value="<?= $credit['did'] ?>">

		<div id="formtabs" class="box--child wp100" style="max-height: 70vh; overflow-y:auto !important; overflow-x:hidden">

			<?php
			$hooks -> do_action( "invoice_form_do_before", $_REQUEST );
			?>

			<div class="flex-container box--child mt10">

				<div class="flex-string wp20 gray2 fs-12 pt7 right-text">Дата:</div>
				<div class="flex-string wp80 pl10 norelativ">
					<input name="datum_credit" type="text" id="datum_credit" class="w120" readonly value="<?= current_datum() ?>">
				</div>

			</div>

			<div class="flex-container box--child mt10">

				<div class="flex-string wp20 gray2 fs-12 pt7 right-text">Номер счета:</div>
				<div class="flex-string wp80 pl10 norelativ">
					<input type="text" name="invoice" class="required w120" id="invoice" value="<?= $credit['invoice'] ?>">
				</div>

			</div>

			<div class="flex-container box--child mt10">

				<div class="flex-string wp20 gray2 fs-12 pt7 right-text">Расч.счет:</div>
				<div class="flex-string wp80 pl10 norelativ">
					<select name="rs" id="rs" class="required wp95">
						<option value="">--выбор--</option>
						<?php
						$x = !empty($userRights['dostup']['rc']) ? " (SELECT COUNT(*) FROM {$sqlname}mycomps_recv WHERE cid = mc.id AND id IN (".yimplode(",", $userRights['dostup']['rc']).") ) > 0 AND " : "";
						$result = $db -> query( "SELECT * FROM {$sqlname}mycomps `mc` WHERE mc.id = '".$credit['mcid']."' and $x mc.identity = '$identity' ORDER BY mc.name_shot" );
						while ($data = $db -> fetch( $result )) {
							?>
							<optgroup label="<?= $data['name_shot'] ?>">
								<?php
								$z = !empty($userRights['dostup']['rc']) ? " id IN (".yimplode(",", $userRights['dostup']['rc']).") AND " : "";
								$res = $db -> query( "SELECT * FROM {$sqlname}mycomps_recv WHERE cid = '".$data['id']."' and $z identity = '$identity' ORDER BY title" );
								while ($da = $db -> fetch( $res )) {

									$ostatok = $userRights['budjet'] ? ': '.num_format( $da['ostatok'] ).' '.$valuta : '';
									$s       = ($da['id'] == $credit['rs']) ? 'selected' : '';

									print '<option value="'.$da['id'].'" '.$s.'>'.$da['title'].$ostatok.'</option>';

								}
								?>
							</optgroup>
						<?php } ?>
					</select>
				</div>

			</div>

			<div class="flex-container box--child mt10">

				<div class="flex-string wp20 gray2 fs-12 pt7 right-text">Дата платежки:</div>
				<div class="flex-string wp80 pl10 norelativ">
					<input type="date" name="date_do" class="required inputdate w120" id="date_do" value="<?= current_datum() ?>">
				</div>

			</div>

			<div class="flex-container box--child mt10">

				<div class="flex-string wp20 gray2 fs-12 pt7 right-text">Номер договора:</div>
				<div class="flex-string wp80 pl10 norelativ">
					<input type="text" name="invoice_chek" id="invoice_chek" value="<?= $credit['invoice_chek'] ?>" class="wp95">
				</div>

			</div>

			<div class="flex-container box--child mt10">

				<div class="flex-string wp20 gray2 fs-12 pt7 right-text">Сумма оплаты:</div>
				<div class="flex-string wp80 pl10 norelativ">
					<input type="text" name="payment_now" class="required w120" id="payment_now" value="<?= num_format( $credit['summa_credit'] ) ?>">
				</div>

			</div>

			<?php
			//указываем данные для смены периода
			if ( $isper == 'yes' ) {

				$p = getPeriodDeal( (int)$credit['did'] );

				if ( $otherSettings['changeDealPeriod'] == 'invoicedo' ) {

					$ch = 'checked';
					$hh = '';
					$hg = '';

				}
				else {

					$ch = '';
					$hh = 'hidden';
					$hg = 'hidden';

				}

				?>
				<div class="divider mt10 mb10">Период сделки</div>

				<div class="flex-container box--child mt10 <?= $hg ?>">

					<div class="flex-string wp20 gray2 fs-12 pt7 right-text"></div>
					<div class="flex-string wp80 pl10 norelativ">

						<label><input type="checkbox" name="changePeriod" id="changePeriod" <?= $ch ?> value="yes" onclick="$('#per').toggleClass('hidden'); $('#dialog').center()">Изменить период сделки</label>

					</div>

				</div>

				<div class="flex-container box--child mt10 <?= $hh ?>" id="per">

					<div class="flex-string wp20 gray2 fs-12 pt7 right-text"></div>
					<div class="flex-string wp80 pl10 norelativ">

						<div class="wp40 inline">
							<input type="date" name="dstart" class="required inputdate" id="dstart" value="<?= $p[0] ?>">
							<div class="fs-10 gray2 em">Начало периода</div>
						</div>
						<div class="wp40 inline">
							<input type="date" name="dend" class="required inputdate" id="dend" value="<?= $p[1] ?>">
							<div class="fs-10 gray2 em">Конец периода</div>
						</div>

					</div>

				</div>
				<?php
			}
			?>

			<?php
			if ( $isper != 'yes' ) {

				$idtype  = $db -> getOne( "SELECT id FROM {$sqlname}contract_type WHERE type = 'get_akt' and identity = '$identity'" );
				$akt_num = generate_num( "akt" );

				$cStep       = getPrevNextStep( getDogData( $credit['did'], 'idcategory' ) );
				$stepApprove = current_dogstepname( $GLOBALS['akt_step'] );

				$types = $db -> getIndCol( "id", "SELECT title, id FROM {$sqlname}contract_type WHERE type NOT IN ('invoice','get_dogovor','get_akt','get_aktper') AND identity = '$identity' ORDER by title" );

				?>
				<div class="flex-container box--child mt10">

					<div class="flex-string wp20 gray2 fs-12 pt7 right-text">Новый этап:</div>
					<div class="flex-string wp80 pl10 norelativ">
						<?php
						$mFunnel = getMultiStepList( ["did" => $credit['did']] );
						$ss      = !empty( (array)$mFunnel['steps'] ) ? " and idcategory IN (".implode( ",", array_keys( (array)$mFunnel['steps'] ) ).")" : "";

						//print_r($mFunnel);

						if ( empty( (array)$mFunnel['steps'] ) ) {

							$oldStep = getDogData( $credit['did'], 'idcategory' );
							$newStep = getPrevNextStep( $oldStep, 'next' );

						}
						else {

							$oldStep = $mFunnel['current']['id'];
							$newStep = $mFunnel['next'];

							//print_r($newStep);

						}

						//print $oldStep."\n";


						?>
						<select name="newstep" id="newstep" class="wp95">
							<option value="<?= $oldStep ?>">--Не менять--</option>
							<?php
							$result = $db -> query( "SELECT * FROM {$sqlname}dogcategory WHERE identity = '$identity' $ss ORDER BY title" );
							while ($data = $db -> fetch( $result )) {

								$s = ($data['title'] == $newStep['title']) ? 'selected' : '';
								$d = ($data['idcategory'] == $oldStep) ? 'disabled' : '';

								print '<option value="'.$data['idcategory'].'" '.$s.' '.$d.' data-step="'.$data['title'].'">'.$data['title'].'%-'.$data['content'].'</option>';

							}
							?>
						</select>
					</div>

				</div>

				<div class="hidden" data-block="aktblock" data-currentstep="<?= $cStep['title'] ?>" data-approvedstep="<?= $stepApprove ?>">

					<!--блок создания акта-->
					<div class="divider mt20 mb20">Создать акт</div>

					<div class="flex-container box--child mt10 pt10 pb10">

						<div class="flex-string wp20"></div>
						<div class="flex-string wp80 pl10">

							<label for="createAkt" class="switch">
								<input type="checkbox" name="createAkt" id="createAkt" value="yes">
								<span class="slider empty"></span>
							</label>
							<label class="inline" for="createAkt"><span class="text Bold gray2 ml10">Создать акт</span></label>

						</div>

					</div>

					<div class="space-10"></div>

					<div class="flex-container box--child mt10 hidden" data-block="akt">

						<div class="flex-string wp20 gray2 fs-12 pt7 right-text">Номер акта:</div>
						<div class="flex-string wp80 pl10 norelativ">
							<INPUT name="akt[num]" type="text" id="akt[num]" class="w120" value="<?= $akt_num ?>">&nbsp;
							<label title="Номер акта будет присвоен автоматически" class="inline"><input name="akt[igen]" id="akt[igen]" type="checkbox" value="yes" checked="checked">&nbsp;авто.</label>
							<br>
							<span class="gray2 fs-07">(Предварительный). Будет присвоен после сохранения.</span>
						</div>

					</div>

					<div class="flex-container box--child mt20 hidden" data-block="akt">

						<div class="flex-string wp20 gray2 fs-12 pt7 right-text">Шаблон:</div>
						<div class="flex-string wp80 pl10 norelativ">

							<select name="akt[temp]" id="akt[temp]" class="required wp95">
								<?php
								$ires = $db -> query( "SELECT * FROM {$sqlname}contract_temp WHERE typeid IN (SELECT id FROM {$sqlname}contract_type WHERE type IN ('get_akt') AND identity = '$identity') AND identity = '$identity' ORDER by title" );
								while ($data = $db -> fetch( $ires )) {

									print '<option value="'.$data['file'].'" '.($template == $data['file'] ? 'selected' : '').'>'.$data['title'].'</option>';

								}
								?>
							</select>

						</div>

					</div>

					<?php
					/**
					 * Статус документа
					 */
					$statuses = $db -> getAll( "SELECT * FROM {$sqlname}contract_status WHERE FIND_IN_SET('$idtype', REPLACE({$sqlname}contract_status.tip, ';',',')) > 0 AND identity = '$identity' ORDER by ord" );
					if ( !empty( $statuses ) ) {
						?>
						<div class="flex-container mb10 mt20 box--child hidden" data-block="akt">

							<div class="flex-string wp20 gray2 fs-12 pt7 right-text">Статус документа:</div>
							<div class="flex-string wp80 pl10 relativ">

								<select name="akt[status]" id="akt[status]" class="wp95">
									<?php
									foreach ( $statuses as $da ) {

										print '<option value="'.$da['id'].'" data-color="'.$da['color'].'" style="color:'.$da['color'].'">'.$da['title'].'</option>';

									}
									?>
								</select>

							</div>

						</div>
						<?php
					}

					?>

				</div>

				<div class="mt20 <?= (!empty( $types ) ? "" : "hidden") ?>" data-block="docblock">

					<div class="divider">Сгенерировать документ</div>

					<div class="flex-container box--child mt10 pt10 pb10">

						<div class="flex-string wp20"></div>
						<div class="flex-string wp80 pl10">

							<label for="createDoc" class="switch">
								<input type="checkbox" name="createDoc" id="createDoc" value="yes">
								<span class="slider empty"></span>
							</label>
							<label class="inline" for="createDoc"><span class="text Bold gray2 ml10">Создать документ</span></label>

						</div>

					</div>

					<div class="flex-container box--child mt10 pt10 pb10 hidden" data-block="doctip">

						<div class="flex-string wp20 gray2 fs-12 pt7 right-text">Тип документа</div>
						<div class="flex-string wp80 pl10">

							<select name="doc[idtype]" id="doc[idtype]" class="wp97">
								<?php
								foreach ( $types as $id => $name ) {

									print '<option value="'.$id.'" '.(texttosmall( $name ) == 'счет-фактура' ? 'selected' : '').'>'.$name.'</option>';

								}
								?>
							</select>

						</div>

					</div>

					<div class="hidden" data-block="doc"></div>

				</div>

				<?php

			}
			?>

			<div class="space-10"></div>

			<div id="stepfields" class="viewdiv1 hidden p0 mt20 mb20 border-bottom"></div>

		</div>

		<div class="infodiv wp100 div-center mt5 p10 fs-12 Bold">

			Сумма по графику:&nbsp;<span class="red"><?= num_format( $credit['summa_credit'] ) ?> <?= $valuta ?></span>
			<input type="hidden" name="summa_credit" id="summa_credit" value="<?= $credit['summa_credit'] ?>">

		</div>

		<hr>

		<div class="button--pane pull-aright">

			<a href="javascript:void(0)" onclick="$('#dealForm').trigger('submit')" class="button">Сохранить</a>&nbsp;
			<a href="javascript:void(0)" onclick="DClose()" class="button">Отмена</a>

		</div>

	</form>

	<script>

		var $aelm = $('div[data-block="aktblock"]');
		var $currentStep = $aelm.data('currentstep');
		var $approvedStep = $aelm.data('approvedstep');
		var $did = $('#did').val();

		$(function () {

			$('#newstep').trigger('change');

		});

		$('#newstep').on('change', function () {

			var $newStepID = parseInt($('option:selected', this).val());
			var $newStep = $('option:selected', this).data('step');

			if ($currentStep >= $approvedStep || $newStep >= $approvedStep)
				$aelm.removeClass('hidden');

			else
				$aelm.addClass('hidden');

			if ($('#stepfields').is('div')) {

				$.get('/content/helpers/deal.helpers.php?action=getStepFields&did=' + $did + '&idcategory=' + $newStepID, function (data) {

					$('#stepfields').html(data);

					if (!data) $('#stepfields').addClass('hidden');
					else $('#stepfields').removeClass('hidden');

					$('.inputdatetime').each(function () {

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
							closeText: '<i class="icon-ok-circled"></i>',
							dateFormat: 'yy-mm-dd',
							firstDay: 1,
							dayNamesMin: ['Вс', 'Пн', 'Вт', 'Ср', 'Чт', 'Пт', 'Сб'],
							monthNamesShort: ['Январь', 'Февраль', 'Март', 'Апрель', 'Май', 'Июнь', 'Июль', 'Август', 'Сентябрь', 'Октябрь', 'Ноябрь', 'Декабрь'],
							changeMonth: true,
							changeYear: true,
							yearRange: '1940:2030',
							minDate: new Date(1940, 1 - 1, 1)
						});

					});
					$('.inputdate').each(function () {

						if (!isMobile) $(this).datepicker({
							dateFormat: 'yy-mm-dd',
							numberOfMonths: 2,
							firstDay: 1,
							dayNamesMin: ['Вс', 'Пн', 'Вт', 'Ср', 'Чт', 'Пт', 'Сб'],
							monthNamesShort: ['Январь', 'Февраль', 'Март', 'Апрель', 'Май', 'Июнь', 'Июль', 'Август', 'Сентябрь', 'Октябрь', 'Ноябрь', 'Декабрь'],
							changeMonth: true,
							changeYear: true,
							yearRange: '1940:2030',
							minDate: new Date(1940, 1 - 1, 1),
							showButtonPanel: true,
							currentText: 'Сегодня',
							closeText: 'Готово'
						});

					});

					$('input[data-type="address"]').each(function () {

						$(this).suggestions({
							token: $dadata,
							type: "ADDRESS",
							count: 5,
							formatResult: formatResult,
							formatSelected: formatSelected,
							onSelect: function (suggestion) {

								console.log(suggestion);

							},
							addon: "clear",
							geoLocation: true
						});

					});

					$(".multiselect").multiselect({sortable: true, searchable: true});
					$(".connected-list").css('max-height', "200px");

				})
					.done(function () {

						$('#dialog').center();

					});

			}

		});

		$('#doc\\[idtype\\]').on('change', function () {

			var $idtype = $('option:selected', this).val();

			$.get('/modules/contract/form.contract.php?action=contract.add.extended&did=' + $did + '&idtype=' + $idtype, function (content) {

				if (content !== '') {

					$('div[data-block="doc"]').html(content);
					$('div[data-block="docblock"]').removeClass('hidden');

				}

			})
				.done(function () {

					$('#dialog').center();

				});

		});

		$('#createAkt').off('change').on('change', function () {

			$('div[data-block="akt"]').toggleClass('hidden');
			$('#dialog').center();

		});

		$('#createDoc').off('change').on('change', function () {

			$('div[data-block="doc"]').toggleClass('hidden');
			$('div[data-block="doctip"]').toggleClass('hidden');
			$('#dialog').center();

			$('#doc\\[idtype\\]').trigger('change');

		});


	</script>
	<?php

	$hooks -> do_action( "invoice_form_do_after", $_REQUEST );

}

if ( $action == "credit.suffix" ) {

	print $suffixTemp = file_get_contents( $rootpath.'/cash/'.$fpath.'templates/invoice_suffix.htm' );
	exit();

}

if ( $action == "invoice.print" ) {

	$crid = (int)$_REQUEST['crid'];

	$invoice = $db -> getRow( "SELECT did, invoice FROM {$sqlname}credit WHERE crid = '$crid' and identity = '$identity'" );
	$file    = $rootpath."/files/".$fpath."invoice_".$invoice.".pdf";

	?>
	<div class="zagolovok">Печать счета</div>
	<FORM method="post" action="/content/helpers/get.doc.php" enctype="multipart/form-data" name="invoiceForm" id="invoiceForm" target="_blank">
		<input name="action" id="action" type="hidden" value="invoice.print">
		<input type="hidden" id="crid" name="crid" value="<?= $crid ?>">
		<input type="hidden" id="did" name="did" value="<?= $invoice['did'] ?>">

		<div id="formtabs" class="box--child wp100">

			<div class="flex-container box--child mt10">

				<div class="flex-string wp20 gray2 fs-12 right-text">Вывести:</div>
				<div class="flex-string wp80 pl10 pt2">

					<label class="paddright15 inline"><input type="radio" name="tip" id="tip" value="print" title="На экран" checked="checked">&nbsp;На экран</label>
					<label class="inline"><input type="radio" name="tip" id="tip" value="pdf" title="На экран">&nbsp;в PDF-файл</label>

				</div>

			</div>
			<div class="flex-container box--child mt10 hidden" id="pdf">

				<div class="flex-string wp20 gray2 fs-12 pt7 right-text"></div>
				<div class="flex-string wp80 pl10">
					<label class="margtop10 Bold blue"><input name="download" id="download" type="checkbox" value="yes" checked="checked"/>&nbsp;Скачать</label>
				</div>

			</div>
			<div class="flex-container box--child mt10">

				<div class="flex-string wp20 gray2 fs-12 pt7 right-text"></div>
				<div class="flex-string wp80 pl10">
					<label class="margtop10"><input name="nosignat" id="nosignat" type="checkbox" value="yes">&nbsp;без штампа</label>
				</div>

			</div>

		</div>

		<?php
		if ( file_exists( $file ) ) {

			$link = "/content/helpers/get.file.php?file=invoice_".$invoice.".pdf";

			?>
			<div class="text-center viewdiv">
				Файл PDF счета уже есть в системе. <br>Вы можете его получить по этой
				<a href="<?= $link ?>" class="red" target="blank"><b>ссылке</b></a> или <b>сгенерировать новый</b>.
			</div>
		<?php } ?>

		<hr>

		<div class="text-right button--pane">

			<a href="javascript:void(0)" onclick="printInvoice();" class="button">Получить</a>&nbsp;
			<a href="javascript:void(0)" onclick="DClose()" class="button">Отмена</a>

		</div>

	</form>
	<script>
		$('input[type="radio"]').off('change').on('change', function () {

			if ($(this).val() === 'pdf') {
				$('#pdf').removeClass('hidden');
			}
			else {
				$('#pdf').addClass('hidden');
			}

		});

		function printInvoice() {

			var str = $('#invoiceForm').serialize();

			window.open('/content/helpers/get.doc.php?' + str);

			DClose();

		}

	</script>
	<?php
}
if ( $action == "invoice.mail" ) {

	$crid = $_REQUEST['crid'];
	?>
	<div class="zagolovok">Отправка документа</div>
	<?php

	//Проверим на подключенный SMTP-сервер
	$active = $db -> getOne( "select active from {$sqlname}smtp WHERE identity = '$identity' and tip = 'send'" );

	if ( $isCloud == 'yes' && $active != 'yes' ) {

		print '<div class="warning">Отправка возможно только при настроенном SMTP-сервере. Настроить его можно в разделе:<br>"Панель управления" / "Интеграция" / Почтовый сервер</div>';

	}
	else {

		$result = $db -> getRow( "SELECT clid, did FROM {$sqlname}credit WHERE crid = '$crid' and identity = '$identity'" );
		$clid   = $result["clid"];
		$did    = $result["did"];

		$result = $db -> getRow( "select * from {$sqlname}user where iduser = '$iduser1' and identity = '$identity'" );
		$mMail  = $result["email"];
		$mName  = $result["title"];
		$mPhone = $result["phone"];

		$content = '
Приветствую, {{person}}

Отправляю Вам Счет на оплату.

Спасибо за внимание.
С уважением,
'.$mName.'
Тел.: '.$mPhone.'
Email.: '.$mMail.'
==============================
'.$company;

		$email  = [];
		$emails = [];
		?>
		<FORM method="post" action="/content/core/core.deals.php" enctype="multipart/form-data" name="dealForm" id="dealForm">
			<input name="action" id="action" type="hidden" value="invoice.mail"/>
			<input name="crid" type="hidden" value="<?= $crid ?>"/>
			<input name="did" type="hidden" value="<?= $did ?>"/>

			<div class="flex-container" style="max-height: 70vh; overflow-y: auto; overflow-x: hidden;">

				<div class="flex-string wp70" style="max-height: 70vh; width:69%; overflow-y: auto; overflow-x: hidden; float:left">

					<div class="row pt5">

						<div class="column12 grid-12 border-box pt0">

							<div class="gray2 fs-09">Тема сообщения</div>
							<input type="text" name="theme" class="required wp100" id="theme" value="Счет на оплату">

						</div>

					</div>
					<div class="row">

						<div class="column12 grid-12 border-box">

							<textarea name="content" class="wp100" id="content" style="min-height: 250px; height: 30vh;"><?= $content ?></textarea>

						</div>

					</div>

				</div>
				<div class="flex-string wp30">

					<div class="row">

						<div class="column12 grid-12 border-box">
							<?php
							$count = 0;

							$pids = yexplode( ";", (string)getDogData( $did, 'pid_list' ) );

							$result = $db -> query( "SELECT * FROM {$sqlname}personcat WHERE clid = '$clid' and mail != '' and identity = '$identity'" );
							while ($data = $db -> fetch( $result )) {

								$s = "";
								if ( in_array( $data['pid'], (array)$pids ) ) {
									$s = "checked";
									$count++;
								}

								$emails[] = '<label><input type="checkbox" name="email[]" id="email[]" class="email" value="pid:'.$data['pid'].'" '.$s.'>&nbsp;'.$data['person'].'</label>';

							}

							$mail_url = yexplode( ",", str_replace( ";", ",", str_replace( " ", "", (string)getClientData( $clid, 'mail_url' ) ) ), 0 );

							if ( $mail_url != '' ) {

								array_unshift( $emails, '<label><input type="checkbox" name="email[]" id="email[]" class="email" checked="checked" value="clid:'.$clid.'">&nbsp;'.current_client( $clid ).'</label>' );
								$count++;
							}

							if ( empty( $emails ) ) {

								print '<div class="warning m0">Внимание! Не найдено ни одного Email. <b class="red">Отправка невозможна!</b></div>';
								exit();

							}

							?>

							<div class="ydropDown border">
								<span>Получатели</span>
								<span class="ydropCount"><?= $count ?> выбрано</span><i class="icon-angle-down pull-aright"></i>
								<div class="yselectBox" style="max-height: 350px;">
									<?php
									foreach ( $emails as $email ) {

										echo '<div class="ydropString ellipsis">'.$email.'</div>';

									}
									?>
								</div>
							</div>

						</div>

					</div>

					<div class="p5">

						<div class="gray2 fs-07">Вложения</div>

						<div id="attach" class="infodiv wp100">

							<span id="loader"><img src="/assets/images/loading.gif" width="12"> Получаю файл...</span>

						</div>

					</div>

				</div>

			</div>

			<hr>

			<div class="text-right button--pane">

				<span class="hidden sender"><a href="javascript:void(0)" onclick="$('#dealForm').trigger('submit')" class="button">Отправить</a>&nbsp;</span>
				<a href="javascript:void(0)" onclick="DClose()" class="button">Отмена</a>

			</div>

		</form>
		<script>

			var count = $('.email').length;

			$.get('/content/core/core.deals.php?action=invoice.link&crid=<?=$crid?>', function (data) {

				if (data.file) {

					$('#attach').html('<div><a href="/content/helpers/get.file.php?file=' + data.file + '" target="blank" title="Скачать"><i class="icon-attach-1"></i>' + data.name + '</a></div>');

					if (data.file !== '' && data.file !== 'undefined' && count > 0) $('.sender').removeClass('hidden');

				}

				if (data.payorder) {

					$('#attach').append('<div><a href="/content/helpers/get.file.php?file=' + data.payorder + '" target="blank" title="Скачать"><i class="icon-attach-1"></i>' + data.payorderName + '</a></div>');

				}

			}, 'json');
		</script>
		<?php
	}

}

// todo: объединить
if ( $action == "controlpoint.add" ) {

	$did = $_REQUEST['did'];

	/**
	 * Расчет даты для каждого этапа
	 */
	$dates     = [];
	$icount    = 0;
	$dateStart = getDogData( $did, 'datum' );
	$lastStep  = get_smdate( $db -> getOne( "SELECT max(datum) FROM {$sqlname}steplog WHERE did = '$did' and identity = '$identity'" ) );
	$msFunnel  = getMultiStepList( [
		"direction" => getDogData( $did, 'direction' ),
		"tip"       => getDogData( $did, 'tip' )
	] );

	$lastStep = ($lastStep == '0000-00-00' && $lastStep == '') ? $dateStart : $lastStep;

	$dateStart = (diffDate2( $lastStep ) < -7 || $lastStep == '') ? current_datum() : $lastStep;
	$title     = (diffDate2( $lastStep ) < -7 || $lastStep == '') ? "Начальная дата - сегодня" : "Начальная дата - дата изменения этапа сделки";

	foreach ( $msFunnel['steps'] as $step => $count ) {

		$icount += $count;

		$dates[ $step ] = addDateRange( $dateStart, $icount );

	}

	//Доступы сотрудников к КТ

	?>
	<div class="zagolovok">Добавить Контрольную точку</div>
	<FORM method="post" action="/content/core/core.deals.php" enctype="multipart/form-data" name="dealForm" id="dealForm">
		<input name="action" id="action" type="hidden" value="controlpoint.add"/>
		<input name="did" id="did" type="hidden" value="<?= $did ?>"/>

		<div id="formtabs" class="box--child wp100" style="overflow-y:auto !important; overflow-x:hidden">

			<div class="flex-container box--child mt10">

				<div class="flex-string wp20 gray2 fs-12 pt7 right-text">Тип точки:</div>
				<div class="flex-string wp80 pl10 norelativ">
					<select id="ccid" name="ccid" class="required wp97">
						<option>--выбор--</option>
						<?php
						$e = '';
						//найдем имеющиеся по сделке КТ
						$exclude = $db -> getCol( "SELECT ccid FROM {$sqlname}complect WHERE did = '$did' and identity = '$identity'" );
						if ( !empty( (array)$exclude ) ) {
							$e = " and ccid NOT IN (".implode( ",", (array)$exclude ).")";
						}

						//ограничим КТ по воронке
						$mFunnel = getMultiStepList( ["did" => $did] );

						//print_r($mFunnel);

						$e .= !empty( (array)$mFunnel['steps'] ) ? " and (dstep IN (".implode( ",", array_keys( (array)$mFunnel['steps'] ) ).") OR dstep = '')" : "";

						//print "SELECT * FROM {$sqlname}complect_cat WHERE ccid > 0 $e and identity = '$identity' ORDER BY corder";

						$resultc = $db -> query( "SELECT * FROM {$sqlname}complect_cat WHERE ccid > 0 $e and identity = '$identity' ORDER BY corder" );
						while ($data = $db -> fetch( $resultc )) {

							$ds = ($data['dstep']) ? ", <b>".current_dogstepname( $data['dstep'] )."</b>%" : '';
							$rr = $tt = "";

							if ( get_cpaccesse( $data['ccid'] ) == 'no' ) {
								$rr = "disabled";
								$tt = " / Нет доступа";
							}

							print '<option '.$rr.' value="'.$data['ccid'].'" data-date="'.$dates[ $data['dstep'] ].'">'.$data['title'].$ds.$tt.'</option>';

						}
						?>
					</select>
				</div>

			</div>

			<div class="flex-container box--child mt10">

				<div class="flex-string wp20 gray2 fs-12 pt7 right-text">Контролёр:</div>
				<div class="flex-string wp80 pl10 norelativ">
					<?php
					$users = new Elements();
					print $users -> UsersSelect( "iduser", [
						"class"  => "wp97",
						"active" => true
					] );
					?>
				</div>

			</div>

			<div class="flex-container box--child mt10">

				<div class="flex-string wp20 gray2 fs-12 pt7 right-text">Дата план:</div>
				<div class="flex-string wp80 pl10 norelativ">
					<INPUT name="datum" type="date" class="required inputdate w120" id="datum" value="" readonly>
					<i class="icon-help-circled blue" title="<?= $title ?>"></i>
				</div>

			</div>

		</div>

		<hr>

		<div class="button-pane text-right">

			<a href="javascript:void(0)" onclick="$('#dealForm').trigger('submit')" class="button">Сохранить</a>&nbsp;
			<a href="javascript:void(0)" onclick="DClose()" class="button">Отмена</a>

		</div>
	</form>

	<script>

		$('#ccid').off('change').on('change', function () {

			var date = $('option:selected', this).data('date');
			$('#datum').val(date);

		});

	</script>
	<?php
}
if ( $action == "controlpoint.edit" ) {

	$did = $_REQUEST['did'];
	$id  = $_REQUEST['id'];
	$ds  = '';

	$cpoint = $db -> getRow( "SELECT * FROM {$sqlname}complect WHERE did = '$did' and id = '$id' and identity = '$identity'" );

	$result = $db -> getRow( "SELECT * FROM {$sqlname}complect_cat WHERE ccid = '$cpoint[ccid]' and identity = '$identity'" );
	$title  = $result["title"];
	$dstep  = $result["dstep"];

	if ( $dstep )
		$ds = ", <b>".current_dogstepname( $dstep )."</b>%";

	?>
	<div class="zagolovok">Изменить Контрольную точку</div>
	<FORM method="post" action="/content/core/core.deals.php" enctype="multipart/form-data" name="dealForm" id="dealForm">
		<input name="action" id="action" type="hidden" value="controlpoint.edit"/>
		<input name="did" id="did" type="hidden" value="<?= $did ?>"/>
		<input name="id" id="id" type="hidden" value="<?= $id ?>"/>

		<div id="formtabs" class="box--child wp100" style="overflow-y:auto !important; overflow-x:hidden">

			<div class="flex-container box--child mt10 mb20">

				<div class="flex-string wp20 gray2 fs-12 right-text">Тип точки:</div>
				<div class="flex-string wp80 pl10 fs-12">

					<?= $title.$ds ?>

				</div>

			</div>

			<div class="flex-container box--child mt10">

				<div class="flex-string wp20 gray2 fs-12 pt7 right-text">Контролёр:</div>
				<div class="flex-string wp80 pl10 norelativ">
					<?php
					$users = new Elements();
					print $users -> UsersSelect( "iduser", [
						"class" => "wp97",
						"sel"   => $cpoint['iduser']
					] );
					?>
				</div>

			</div>

			<div class="flex-container box--child mt20">

				<div class="flex-string wp20 gray2 fs-12 pt7 right-text">Дата план:</div>
				<div class="flex-string wp80 pl10 norelativ">

					<INPUT name="datum" type="date" class="required inputdate w120" id="datum" value="<?= $cpoint["data_plan"] ?>" readonly>

				</div>

			</div>

		</div>

		<hr>

		<div class="button-pane text-right">

			<a href="javascript:void(0)" onclick="$('#dealForm').trigger('submit')" class="button">Сохранить</a>&nbsp;
			<a href="javascript:void(0)" onclick="DClose()" class="button">Отмена</a>

		</div>
	</form>
	<?php
}

if ( $action == "controlpoint.doitmore" ) {

	$did = $_REQUEST['did'];
	$id  = $_REQUEST['id'];
	$ds  = '';

	$cpoint = $db -> getRow( "SELECT * FROM {$sqlname}complect WHERE did = '$did' and id = '$id' and identity = '$identity'" );

	$result = $db -> getRow( "SELECT * FROM {$sqlname}complect_cat WHERE ccid = '$cpoint[ccid]' and identity = '$identity'" );
	$title  = $result["title"];
	$dstep  = $result["dstep"];

	if ( $dstep )
		$ds = ", <b>".current_dogstepname( $dstep )."</b>%";

	?>
	<div class="zagolovok">Выполнить Контрольную точку</div>
	<FORM method="post" action="/content/core/core.deals.php" enctype="multipart/form-data" name="dealForm" id="dealForm">
		<input name="action" id="action" type="hidden" value="controlpoint.doit"/>
		<input name="did" id="did" type="hidden" value="<?= $did ?>"/>
		<input name="id" id="id" type="hidden" value="<?= $id ?>"/>

		<div id="formtabs" class="box--child wp100">

			<div class="flex-container box--child mt10 mb20">

				<div class="flex-string wp20 gray2 fs-12 right-text">Тип точки:</div>
				<div class="flex-string wp80 pl10 fs-12">

					<?= $title.$ds ?>

				</div>

			</div>

			<div class="flex-container box--child mt20">

				<div class="flex-string wp20 gray2 fs-12 pt7 right-text">Дата выполнения:</div>
				<div class="flex-string wp80 pl10 norelativ">

					<INPUT name="datum" type="date" class="required inputdate w120" id="datum" value="<?= current_datum() ?>" readonly>

				</div>

			</div>

			<div id="stepfields" class="viewdiv hidden m10" style="overflow-y:auto !important; overflow-x:hidden; max-height: 60vh">1</div>

		</div>

		<hr>

		<div class="button-pane text-right">

			<a href="javascript:void(0)" onclick="$('#dealForm').trigger('submit')" class="button">Сохранить</a>&nbsp;
			<a href="javascript:void(0)" onclick="DClose()" class="button">Отмена</a>

		</div>
		<script>
			$(function () {
				getStepFields('<?=$dstep?>');
			})
		</script>
	</form>
	<?php

}

// todo: объединить
if ( $action == 'provider.add' ) {

	$tip = $_REQUEST['tip'];
	$did = $_REQUEST['did'];

	?>
	<DIV class="zagolovok">Добавить</DIV>
	<FORM method="post" action="/content/core/core.deals.php" enctype="multipart/form-data" name="dealForm" id="dealForm">
		<input name="action" id="action" type="hidden" value="provider.add"/>
		<input name="did" id="did" type="hidden" value="<?= $did ?>"/>

		<div id="place"></div>
		<div id="formtabs" class="box--child wp100 pt20">

			<?php
			if ( $tip == 'contractor' ) {

				//Число поставщиков
				$count = $db -> getOne( "SELECT COUNT(clid) FROM {$sqlname}clientcat WHERE type = 'contractor' and identity = '$identity'" );

				if ( $count < 50 ) {
					?>

					<div class="flex-container box--child mt10 mb10">

						<div class="flex-string wp20 gray2 fs-12 pt7 right-text">Поставщик:</div>
						<div class="flex-string wp80 pl10">

							<select name="conid" id="conid" class="required wp97">
								<option value="" selected>--Выбор--</option>
								<?php
								$result = $db -> query( "SELECT * FROM {$sqlname}clientcat WHERE type = 'contractor' and identity = '$identity'" );
								while ($data = $db -> fetch( $result )) {

									//$ss = ($data['clid'] == $conid) ? "selected" : "";
									print '<option value="'.$data['clid'].'">'.$data['title'].'</option>';

								}
								?>
							</select>

						</div>

					</div>

					<?php
				}
				else {
					?>
					<div class="flex-container mt10 mb10">

						<div class="flex-string wp20 gray2 fs-12 pt7 right-text">Поставщик:</div>
						<div class="flex-string wp80 pl10">

							<div id="org" class="relativ">

								<INPUT type="hidden" id="conid" name="conid" value="<?= $deal['conid'] ?>">
								<div class="relativ">
									<INPUT id="lst_conid" type="text" class="wp97 required" value="<?= current_client( $deal['conid'] ) ?>" readonly onclick="get_orgspisok('org','place','/content/helpers/client.helpers.php?action=get_orgselector&type=<?= $tip ?>','conid','lst_conid')" placeholder="Нажмите для выбора">
									<div class="idel pr20 mr10">
										<i title="Очистить" onclick="$('input#conid').val(''); $('#lst_conid').val('');" class="icon-block red hand"></i>
									</div>
								</div>

							</div>

						</div>

					</div>
					<?php
				}

			}
			if ( $tip == 'partner' ) {

				//Число партнеров
				$count = $db -> getOne( "SELECT COUNT(clid) FROM {$sqlname}clientcat WHERE type = 'contractor' and identity = '$identity'" );

				if ( $count < 50 ) {
					?>

					<div class="flex-container box--child mt10">

						<div class="flex-string wp20 gray2 fs-12 pt7 right-text">Партнер:</div>
						<div class="flex-string wp80 pl10">

							<select name="partid" id="partid" class="required wp95">
								<option value="" selected>--Выбор--</option>
								<?php
								$result = $db -> query( "SELECT * FROM {$sqlname}clientcat WHERE type = 'partner' and identity = '$identity'" );
								while ($data = $db -> fetch( $result )) {

									//$ss = ($data['clid'] == $partid) ? "selected" : "";
									print '<option value="'.$data['clid'].'">'.$data['title'].'</option>';

								}
								?>
							</select>

						</div>

					</div>
					<?php
				}
				else {
					?>
					<div class="flex-container mt10 mb10">

						<div class="flex-string wp20 gray2 fs-12 pt7 right-text">Поставщик:</div>
						<div class="flex-string wp80 pl10">

							<div id="org" class="relativ">

								<INPUT type="hidden" id="partid" name="partid" value="<?= $deal['partid'] ?>">
								<div class="relativ">
									<INPUT id="lst_partid" type="text" class="wp97 required" value="<?= current_client( $deal['partid'] ) ?>" readonly onclick="get_orgspisok('org','place','/content/helpers/client.helpers.php?action=get_orgselector&type=<?= $tip ?>','partid','lst_partid')" placeholder="Нажмите для выбора">
									<div class="idel pr20 mr10">
										<i title="Очистить" onclick="$('input#partid').val(''); $('#lst_partid').val('');" class="icon-block red hand"></i>
									</div>
								</div>

							</div>

						</div>

					</div>
					<?php
				}
			}
			?>

			<div class="flex-container box--child mt10">

				<div class="flex-string wp20 gray2 fs-12 pt7 right-text">Сумма:</div>
				<div class="flex-string wp80 pl10 norelativ">

					<input name="summa" id="summa" type="text" class="required" value="<?= $summa ?>"/>&nbsp;<?= $valuta ?>

				</div>

			</div>

			<div class="flex-container box--child mt10">

				<div class="flex-string wp20 gray2 fs-12 pt7 right-text"></div>
				<div class="flex-string wp80 pl10">

					<div class="checkbox mt5">
						<label>
							<input name="recal" type="checkbox" id="recal" value="0" checked>
							<span class="custom-checkbox"><i class="icon-ok"></i></span>
							&nbsp;Вычитать из прибыли
						</label>
					</div>

				</div>

			</div>

		</div>

		<hr>

		<DIV class="button--pane text-right">

			<A href="javascript:void(0)" onclick="$('#dealForm').trigger('submit')" class="button">Сохранить</A>&nbsp;
			<A href="javascript:void(0)" onclick="DClose();" class="button">Отмена</A>

		</DIV>
	</FORM>
	<?php
}
if ( $action == 'provider.edit' ) {

	$id  = $_REQUEST['id'];
	$tip = $_REQUEST['tip'];

	$res = $db -> getRow( "SELECT * FROM {$sqlname}dogprovider WHERE id = '$id' and identity = '$identity'" );

	?>
	<DIV class="zagolovok">Изменить</DIV>
	<FORM method="post" action="/content/core/core.deals.php" enctype="multipart/form-data" name="dealForm" id="dealForm">
		<input name="action" id="action" type="hidden" value="provider.edit"/>
		<input name="did" id="did" type="hidden" value="<?= $did ?>"/>
		<input name="id" id="id" type="hidden" value="<?= $id ?>"/>

		<div id="formtabs" class="box--child wp100 pt20">

			<?php
			if ( $tip == 'contractor' ) {
				?>
				<div class="flex-container box--child mt10 mb10">

					<div class="flex-string wp20 gray2 fs-12 right-text">Поставщик:</div>
					<div class="flex-string wp80 pl10">

						<select name="conid" id="conid" class="required wp95">
							<option value="" selected>--Выбор--</option>
							<?php
							$result = $db -> query( "SELECT * FROM {$sqlname}clientcat WHERE type = 'contractor' and identity = '$identity'" );
							while ($data = $db -> fetch( $result )) {

								$ss = ($data['clid'] == $res['conid']) ? "selected" : "";
								print '<option value="'.$data['clid'].'" '.$ss.'>'.$data['title'].'</option>';

							}
							?>
						</select>

					</div>

				</div>

				<?php
			}
			if ( $tip == 'partner' ) {
				?>

				<div class="flex-container box--child mt10">

					<div class="flex-string wp20 gray2 fs-12 pt7 right-text">Партнер:</div>
					<div class="flex-string wp80 pl10">

						<select name="partid" id="partid" class="required wp95">
							<option value="" selected>--Выбор--</option>
							<?php
							$result = $db -> query( "SELECT * FROM {$sqlname}clientcat WHERE type = 'partner' and identity = '$identity'" );
							while ($data = $db -> fetch( $result )) {

								$ss = ($data['clid'] == $res['partid']) ? "selected" : "";
								print '<option value="'.$data['clid'].'" '.$ss.'>'.$data['title'].'</option>';

							}
							?>
						</select>

					</div>

				</div>
				<?php
			}
			?>

			<div class="flex-container box--child mt10">

				<div class="flex-string wp20 gray2 fs-12 pt7 right-text">Сумма:</div>
				<div class="flex-string wp80 pl10 norelativ">

					<input name="summa" id="summa" type="text" class="required" value="<?= num_format( $res['summa'] ) ?>"/>&nbsp;<?= $valuta ?>

				</div>

			</div>

			<div class="flex-container box--child mt10">

				<div class="flex-string wp20 gray2 fs-12 pt7 right-text"></div>
				<div class="flex-string wp80 pl10">

					<div class="checkbox mt5">
						<label>
							<input name="recal" type="checkbox" id="recal" value="0" <?= ((int)$res['recal'] == 0 ? 'checked' : '') ?>>
							<span class="custom-checkbox"><i class="icon-ok"></i></span>
							&nbsp;Вычитать из прибыли
						</label>
					</div>

				</div>

			</div>

		</div>

		<hr>

		<DIV class="button--pane text-right">

			<A href="javascript:void(0)" onclick="$('#dealForm').trigger('submit')" class="button">Сохранить</A>&nbsp;
			<A href="javascript:void(0)" onclick="DClose();" class="button">Отмена</A>

		</DIV>
	</FORM>
	<?php
}
?>

<script type="text/javascript" src="/assets/js/app.form.js"></script>
<script>

	includeJS('/assets/js/timepickeraddon/jquery-ui-timepicker-addon.js');

	var isAdmin = '<?=$isadmin?>';

	var clid = parseInt($('#clid').val());
	var did = parseInt($('#did').val());

	var action = $('#action').val();
	var dwidth = $(document).width();
	var dialogWidth;
	var dialogHeight;

	var $nalogPercent = parseFloat(<?=($nalogPercent + 0)?>);
	var $nds_credit = parseFloat(<?=($ndsCredit + 0)?>);
	var $nalogSpeka = parseFloat(<?=($spekaData['nalog'] + 0)?>);

	var $kol_fact = $('#kol_fact').val();
	var $marga = $('#marga').val();

	if ($nalogSpeka < $nds_credit)
		$('#nds_proc').prop('disabled', true);

	var alls, counts;

	if (!isMobile) {

		if (dwidth < 945) {
			dialogWidth = '90%';
			dialogHeight = '95vh';
		}
		else
			dialogWidth = '70%';


		if (in_array(action, ['credit.add', 'credit.edit'])) {

			var hhh = ($(window).height() * 0.7);
			var hhh2 = hhh - $('.zagolovok').actual('outerHeight') - $('.button--pane').actual('outerHeight') - 10;

			dialogWidth = '80%';

			$('#formtabs').find('.table').css({'max-height': hhh + 'px'});

			$('#dialog').css({'width': dialogWidth});

			createEditor();

		}
		else if (in_array(action, ['deal.add', 'deal.edit'])) {

			var hh = $('#dialog_container').actual('height') * 0.9;
			var hh2 = hh - $('.zagolovok').actual('outerHeight') - $('.button--pane').actual('outerHeight') - 10;

			$('#dialog').css({'width': dialogWidth});

			if (dwidth < 945) {

				$('#dialog').css({'height': dialogHeight});
				$('#formtabs').css({'height': 'unset', 'max-height': hh2 + 30});

			}
			else if (dwidth > 1500) {

				$('#dialog').css({'width': '60vw', 'height': hh}).center();
				$('#formtabs').css({'height': 'unset', 'max-height': hh2});

			}
			else {

				$('#dialog').css({'width': dialogWidth, 'height': hh}).center();
				$('#formtabs').css({'height': 'unset', 'max-height': hh2});

			}

		}
		else if (in_array(action, ['deal.mass'])) {

			$('#dialog').css({'width': '800px'});

		}
		else if (in_array(action, ['invoice.print', 'akt.print', 'akt.per.print', 'deal.change.period'])) {

			$('#dialog').css({'width': '600px'});

		}
		else if (in_array(action, ['controlpoint.doit', 'deal.freeze'])) {

			$('#dialog').css({'width': '600px'});

		}
		else {

			if ($(window).width() > 990)
				$('#dialog').css('width', '892px');

			else
				$('#dialog').css('width', '80%');

		}

		if (in_array(action, ['deal.change.datum_plan', 'deal.change.user', 'deal.change.close', 'deal.change.dostup', 'deal.change.step', 'credit.express', 'credit.doit', 'append.contract', 'provider.add', 'provider.edit', 'controlpoint.add', 'controlpoint.edit'/*, 'invoice.print'*/])) {

			if (dwidth < 945)
				dialogWidth = '90%';

			else
				dialogWidth = '800px';

			$('#dialog').css('width', dialogWidth);

		}

		$(".multiselect").multiselect({sortable: true, searchable: true});
		$(".connected-list").css('max-height', "200px");

	}
	else {

		var h2 = $(window).height() - $('.zagolovok').actual('outerHeight') - $('.button--pane').actual('outerHeight') - 30;
		$('#formtabs').css({'max-height': h2 + 'px', 'height': h2 + 'px'});

		$(".multiselect").addClass('wp97 h0');

		$('#dialog').find('table').rtResponsiveTables();

		if (in_array(action, ['credit.add', 'credit.edit'/*, 'credit.express'*/])) {

			createEditor();

		}

	}

	$(function () {

		$('#title_dog').trigger('focus');

		if ($('#isstartspeca').val() !== '') {

			$('#specadiv').removeClass('hidden');

		}

		$('#idcategory').trigger('change');

		if ($('#allSelected').is('input')) {

			alls = parseInt($('#allSelected').val());
			counts = alls;

			if (alls > 500)
				counts = 500;

			$('#alls').html(alls);
			$('#counts').html(counts);

		}

		if ($("#title_dog").is('input')) {

			$("#title_dog").autocomplete('/content/helpers/deal.helpers.php?action=get.list', {
				autofill: true,
				minChars: 3,
				cacheLength: 10,
				maxItemsToShow: 20,
				selectFirst: false,
				multiple: false,
				delay: 500,
				matchSubset: 1
			});

		}

		if (!isMobile) {

			$('.inputdate').each(function () {

				$(this).datepicker({
					dateFormat: 'yy-mm-dd',
					numberOfMonths: 2,
					firstDay: 1,
					dayNamesMin: ['Вс', 'Пн', 'Вт', 'Ср', 'Чт', 'Пт', 'Сб'],
					monthNamesShort: ['Январь', 'Февраль', 'Март', 'Апрель', 'Май', 'Июнь', 'Июль', 'Август', 'Сентябрь', 'Октябрь', 'Ноябрь', 'Декабрь'],
					changeMonth: true,
					changeYear: true,
					yearRange: '1940:2030',
					minDate: new Date(1940, 1 - 1, 1),
					showButtonPanel: true,
					currentText: 'Сегодня',
					closeText: 'Готово'
				});

			});

		}

		$('.inputdatetime').each(function () {

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
				closeText: '<i class="icon-ok-circled"></i>',
				dateFormat: 'yy-mm-dd',
				firstDay: 1,
				dayNamesMin: ['Вс', 'Пн', 'Вт', 'Ср', 'Чт', 'Пт', 'Сб'],
				monthNamesShort: ['Январь', 'Февраль', 'Март', 'Апрель', 'Май', 'Июнь', 'Июль', 'Август', 'Сентябрь', 'Октябрь', 'Ноябрь', 'Декабрь'],
				changeMonth: true,
				changeYear: true,
				yearRange: '1940:2030',
				minDate: new Date(1940, 1 - 1, 1)
			});

		});

		$("#client").autocomplete('/content/helpers/client.helpers.php?action=clientlist', {
			autofill: false,
			minChars: 2,
			cacheLength: 2,
			maxItemsToShow: 10,
			selectFirst: false,
			multiple: false,
			delay: 500,
			matchSubset: 1,
			formatItem: function (data, j, n, value) {
				return '<div>' + data[0] + '&nbsp;[<span class="red">' + data[2] + '</span>]</div>';
			},
			formatResult: function (data) {
				return data[0];
			}
		})
			.result(function (value, data) {
				selItem('client', data[1]);
				$('#clid').val(data[1]);
			});

		$("#person").autocomplete("/content/helpers/client.helpers.php?action=contactlist", {
			autofill: true,
			minChars: 2,
			cacheLength: 2,
			maxItemsToShow: 10,
			selectFirst: false,
			multiple: false,
			delay: 500,
			matchSubset: 1,
			formatItem: function (data, i, n, value) {
				return '<div onclick="selItem(\'person\',\'' + data[1] + '\')">' + data[0] + '&nbsp;[<span class="red">' + data[2] + '</span>]</div>';
			},
			formatResult: function (data) {
				return data[0];
			}
		})
			.result(function (value, data) {
				selItem('person', data[1]);
			});

		$("#speca_title\\[\\]").autocomplete("/content/helpers/price.helpers.php?clid=" + $('#clid').val(), {
			autofill: true,
			minChars: 2,
			cacheLength: 1,
			maxItemsToShow: 100,
			max: 100,
			selectFirst: false,
			multiple: false,
			delay: 500,
			matchSubset: 1,
			formatItem: function (data, j, n, value) {

				var str = '';

				if (parseFloat(data[8]) != 0) str = '<div class="gray">На складах: ' + data[7] + ', в т.ч. в резерве ' + data[9] + ' </div>';

				return '<div"><b>' + data[5] + ':</b> ' + data[0] + str + '</div>';

			},
			formatResult: function (data) {
				return data[0];
			}
		})
			.result(function (value, data) {

				$(this).closest('tr.tstring').find('#speca_summa\\[\\]').val(data[1]);
				$(this).closest('tr.tstring').find('#price_in\\[\\]').val(data[2]);
				$(this).closest('tr.tstring').find('#speca_ediz\\[\\]').val(data[3]);
				$(this).closest('tr.tstring').find('#speca_nds\\[\\]').val(data[4]);
				$(this).closest('tr.tstring').find('#speca_artikul\\[\\]').val(data[5]);
				$(this).closest('tr.tstring').find('#speca_prid\\[\\]').val(data[6]);

			});

		if (action === 'deal.edit' && did > 0) {
			startSpeca();
		}

		$('#dialog').center();

		$('#dealForm').ajaxForm({
			dataType: 'json',
			beforeSubmit: function () {

				var stopp = 'no';
				var $out = $('#message');
				var em = checkRequired();

				if (em === false)
					return false;

				<?php if(!$otherSettings['addClientWDeal']){?>
				if (in_array(action, ['deal.add', 'deal.edit'])) {

					if ($('#clid').val() === '' && $('#pid').val() === '') {

						stopp = 'yes';
						Swal.fire('Внимание', "Необходимо выбрать Клиента из существующих", 'info');

						return false;

					}
					else stopp = 'no';

				}
				<?php } ?>

				if (stopp === 'no') {

					$('#dialog').css('display', 'none');
					$('#dialog_container').css('display', 'none');

					//для формы редактирования счета
					if (editor && in_array(action, ['credit.add', 'credit.edit']))
						remEditor();

					$out.empty().fadeTo(1, 1).css('display', 'block').append('<div id="loader"><img src="/assets/images/loader.gif"> Выполняю...</div>');

					return true;

				}

			},
			success: function (data) {

				var errors = '';
				var did = $('#ctitle').find('#did').val();
				var next = $('#next').val();//no - отмена открытия карточки сделки для смены этапа
				var isset = [];//массив хранит id-табов, которые уже были перезагружены, чтобы предотвратить повторную загрузку
				var card = $('#card').val();
				var odid = parseInt($('#odid').val());// есть, если мы клонируем сделку

				$('#dialog_container').css('display', 'none');
				$('#dialog').css('display', 'none');

				if (data.error !== 'undefined' && data.error !== null && data.error) {
					errors = '<br>Note: ' + data.error;
				}


				$('#message').fadeTo(1, 1).css('display', 'block').html('Результат: ' + data.result + errors);
				setTimeout(function () {
					$('#message').fadeTo(1000, 0);
				}, 10000);

				<?php //мы в карточке ?>

				if (!isCard && data.did && card !== 'dogovor' && odid === 0 && next !== 'no') {
					openDogovor(data.did);
					//window.open('card.deal?did=' + data.did);
				}

				if (isCard) {

					if (action === 'deal.add' || action === 'deal.edit') {

						if ($('tab15').is('div')) {

							settab('15', false);
							isset.push(15);

						}

						if (typeof settab === 'function' && !in_array(0, isset)) {

							settab('0', false);
							isset.push(0);

						}

						if (typeof settab === 'function' && card === 'client' && !in_array(4, isset))
							settab('4', false);

						if (typeof cardload === 'function')
							cardload('1');

					}
					if (action === 'deal.change.user' || action === 'deal.change.period') {

						if (typeof cardload === 'function')
							cardload();

						if (typeof settab === 'function' && !in_array(0, isset)) {

							settab('0', false);
							isset.push(0);

						}

					}
					if (action === 'deal.change.dostup' || action === 'deal.change.priceLevel') {

						if (typeof settab === 'function' && !in_array(0, isset)) {

							settab('0', false);
							isset.push(0);

						}

					}
					if (action === 'deal.change.close') {

						window.location.href = 'card.deal?did=' + did;

					}
					if (action === 'deal.change.step') {

						if ($('#complect').is('div'))
							$('#complect').load('/content/card/card.controlpoint.php?did=' + did);

						if (typeof settab === 'function' && !in_array(0, isset)) {

							settab('0', false);
							isset.push(0);

						}

						if (typeof cardload === 'function')
							cardload();

						if (typeof getCatalog === 'function')
							getCatalog();

					}
					if (in_array(action, ['credit.add', 'credit.edit', 'credit.express', 'credit.delete', 'credit.doit', 'credit.undoit'])) {

						if (typeof settab === 'function' && !in_array(0, isset)) {

							settab('0', false);
							isset.push(0);

						}

						setTimeout(function () {

							if (typeof settab === 'function' && !in_array(7, isset)) {

								settab('7', false);
								isset.push(7);

							}

						}, 1000);

					}
					if (action === 'append.contract') {

						if (typeof settab === 'function' && !in_array(15, isset)) {

							settab('15', false);
							isset.push(15);

						}

						if (typeof settab === 'function' && !in_array(0, isset)) {

							settab('0', false);
							isset.push(0);

						}

					}
					if (action === 'provider.add' || action === 'provider.edit') {

						//if (typeof settab == 'function') settab('0');

						if (typeof settab === 'function')
							settab('13', true);

						if (typeof configpage === 'function')
							configpage();

					}
					if (['controlpoint.add', 'controlpoint.edit', 'controlpoint.doit'].includes(action)) {

						if (typeof configpage === 'function')
							configpage();

						if (typeof cardload === 'function')
							cardload();

					}
					if (action === 'invoice.mail') {

						if (typeof cardload === 'function')
							cardload();

					}
					if (in_array(action, ['akt.add', 'akt.edit', 'akt.per.add', 'akt.per.edit'])) {

						if (typeof settab === 'function')
							settab('15', false);

						if (typeof cardload === 'function')
							cardload();

					}
					if (action === 'deal.freeze') {
						window.location.href = 'card.deal?did=' + did;
					}

					if (card === 'client' && !in_array(0, isset)) {
						settab('0', false);
					}

					if (card === 'dogovor' && !in_array(7, isset)) {

						$('#credit_' + did).load('/content/card/card.credit.php?did=' + did);

						$.get('/content/card/card.deal.php?did=' + did, function (data) {

							$('#tab0').html(data);

							if (typeof cardCallback === 'function') {
								cardCallback();
							}

						});

					}

				}

				//для клонирования сделок
				if ($('#odid').is('input') && odid > 0) {
					window.location = 'card.deal?did=' + data.did;
					//openDogovor(data.did);
				}

				if ($('#closetask').prop('checked') && action === 'deal.change.close') {
					cardload();
				}

				if ($('#tiplist').is('input'))
					configpage();

				if ($display === 'desktop') {

					$desktop.deals();
					$desktop.pipeline();

				}

			}
		});

		if (isMobile) {
			$('#dialog').find('table').rtResponsiveTables();
		}

		doLoadCallback('dealForm');

		ShowModal.fire({
			etype: 'dealForm',
			action: action
		});

	});

	if (in_array(action, ['deal.add', 'deal.edit']) && did < 1) {

		var mFunnel = JSON.parse('<?=$mFunnel?>');
		//var currentStep = <?=$currentStep?>;

		if (Object.keys(mFunnel).length > 0) {

			$(document).off('change', '#tip');
			$(document).on('change', '#tip', function () {

				var tip = $('option:selected', this).val();
				var direction = $('#direction option:selected').val();

				if (parseInt(direction) > 0) {

					var steps = mFunnel[direction][tip]['nsteps'];
					var def = mFunnel[direction][tip]['default'];
					var str = '';
					var $s;

					for (var i in steps) {

						$s = (steps[i].id == def) ? "selected" : "";

						str += '<option value="' + steps[i].id + '" ' + $s + '>' + steps[i].name + '% - ' + steps[i].content + '</option>';

					}

					$('#idcategory').html(str);

				}

			});

			$(document).off('change', '#direction');
			$(document).on('change', '#direction', function () {

				$('#tip').trigger('change');

			});

			$('#tip').trigger('change');

		}

	}

	$(document).off('change', '#period');
	$(document).on('change', '#period', function () {

		$('#dstart').val($('option:selected', this).data('da1'));
		$('#dend').val($('option:selected', this).data('da2'));

	});

	$(document).off('change', '#idcategory');
	$(document).on('change', '#idcategory', function () {

		if ($('#stepfields').is('div') && action !== 'change.close') {

			$.get('/content/helpers/deal.helpers.php?action=getStepFields&did=' + $('#did').val() + '&idcategory=' + $('option:selected', this).val(), function (data) {

				if (!data || data == '') $('#stepfields').addClass('hidden');
				else {

					$('#stepfields').removeClass('hidden').html(data);

					$('.inputdatetime').each(function () {

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
							closeText: '<i class="icon-ok-circled"></i>',
							dateFormat: 'yy-mm-dd',
							firstDay: 1,
							dayNamesMin: ['Вс', 'Пн', 'Вт', 'Ср', 'Чт', 'Пт', 'Сб'],
							monthNamesShort: ['Январь', 'Февраль', 'Март', 'Апрель', 'Май', 'Июнь', 'Июль', 'Август', 'Сентябрь', 'Октябрь', 'Ноябрь', 'Декабрь'],
							changeMonth: true,
							changeYear: true,
							yearRange: '1940:2030',
							minDate: new Date(1940, 1 - 1, 1)
						});

					});
					$('.inputdate').each(function () {

						if (!isMobile) $(this).datepicker({
							dateFormat: 'yy-mm-dd',
							numberOfMonths: 2,
							firstDay: 1,
							dayNamesMin: ['Вс', 'Пн', 'Вт', 'Ср', 'Чт', 'Пт', 'Сб'],
							monthNamesShort: ['Январь', 'Февраль', 'Март', 'Апрель', 'Май', 'Июнь', 'Июль', 'Август', 'Сентябрь', 'Октябрь', 'Ноябрь', 'Декабрь'],
							changeMonth: true,
							changeYear: true,
							yearRange: '1940:2030',
							minDate: new Date(1940, 1 - 1, 1),
							showButtonPanel: true,
							currentText: 'Сегодня',
							closeText: 'Готово'
						});

					});

					if (!isMobile) {

						$(".multiselect").multiselect({sortable: true, searchable: true});
						$(".connected-list").css('max-height', "200px");

					}

					$('input[data-type="address"]').each(function () {

						$(this).suggestions({
							token: $dadata,
							type: "ADDRESS",
							count: 5,
							formatResult: formatResult,
							formatSelected: formatSelected,
							onSelect: function (suggestion) {

								console.log(suggestion);

							},
							addon: "clear",
							geoLocation: true
						});

					});

				}

			}).done(function () {

				$('#dialog').center();

			});

		}

	});

	$(document).off('change', '#idcurrency');
	$(document).on('change', '#idcurrency', function () {

		let idcurrency = parseInt($(this).val());

		if (idcurrency > 0) {

			$.getJSON('/content/helpers/deal.helpers.php?action=get.course&idcurrency=' + idcurrency, function (data) {

				let template = $('#courseTpl').html();
				Mustache.parse(template);// optional, speeds up future uses

				let rendered = Mustache.render(template, data);
				$('#idcourse').empty().append(rendered);

			});

		}

	});

	$(document).off('click', '.removeSpecaString');
	$(document).on('click', '.removeSpecaString', function () {

		var count = $('#tbspeca tbody tr').length;
		if (count > 1) $(this).closest('tr.tstring').remove();

	});

	$(document).off('click', '.tagsmenu li');
	$(document).on('click', '.tagsmenu li', function () {

		var t = $('b', this).html();

		if ($('#suffix').is('textarea')) addTagInEditor('suffix', t);
		if ($('#des').is('textarea')) addTagInEditor('des', t);

	});

	$(document).off('click', '.close');
	$(document).on('click', '.close', function () {

		remEditor();
		DClose();

	});

	function getStepFields(stepid) {

		$('#stepfields').removeClass('hidden').append('<img src="/assets/images/loading.svg"> проверка полей сделки..');

		$.get('/content/helpers/deal.helpers.php?action=getStepFields&did=' + $('#did').val() + '&idcategory=' + stepid + '&full=1', function (data) {

			if (!data || data == '') {
				$('#stepfields').removeClass('hidden').html('<div class="success">Заполнение полей не требуется на данном этапе</div>');
			}
			else {

				$('#dialog').css({'width': '800px'});

				$('#stepfields').removeClass('hidden').html(data);

				$('.inputdatetime').each(function () {

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
						closeText: '<i class="icon-ok-circled"></i>',
						dateFormat: 'yy-mm-dd',
						firstDay: 1,
						dayNamesMin: ['Вс', 'Пн', 'Вт', 'Ср', 'Чт', 'Пт', 'Сб'],
						monthNamesShort: ['Январь', 'Февраль', 'Март', 'Апрель', 'Май', 'Июнь', 'Июль', 'Август', 'Сентябрь', 'Октябрь', 'Ноябрь', 'Декабрь'],
						changeMonth: true,
						changeYear: true,
						yearRange: '1940:2030',
						minDate: new Date(1940, 1 - 1, 1)
					});

				});
				$('.inputdate').each(function () {

					if (!isMobile) $(this).datepicker({
						dateFormat: 'yy-mm-dd',
						numberOfMonths: 2,
						firstDay: 1,
						dayNamesMin: ['Вс', 'Пн', 'Вт', 'Ср', 'Чт', 'Пт', 'Сб'],
						monthNamesShort: ['Январь', 'Февраль', 'Март', 'Апрель', 'Май', 'Июнь', 'Июль', 'Август', 'Сентябрь', 'Октябрь', 'Ноябрь', 'Декабрь'],
						changeMonth: true,
						changeYear: true,
						yearRange: '1940:2030',
						minDate: new Date(1940, 1 - 1, 1),
						showButtonPanel: true,
						currentText: 'Сегодня',
						closeText: 'Готово'
					});

				});

				if (!isMobile) {

					$(".multiselect").multiselect({sortable: true, searchable: true});
					$(".connected-list").css('max-height', "200px");

				}

				$('input[data-type="address"]').each(function () {

					$(this).suggestions({
						token: $dadata,
						type: "ADDRESS",
						count: 5,
						formatResult: formatResult,
						formatSelected: formatSelected,
						onSelect: function (suggestion) {

							//console.log(suggestion);

						},
						addon: "clear",
						geoLocation: true
					});

				});

			}

		}).done(function () {

			$('#dialog').center();

		});

	}

	function SaveInvoice() {

		remEditor();
		$('#dealForm').trigger('submit');

	}

	<?php //--add, edit ?>
	function addSpecaString() {

		//var str = $('#spekaTpl').html();
		//$('#specaloader tbody').append(str);

		var str = $('#specaloader tbody tr:first').html();

		$('#specaloader tbody').append('<tr class="tstring">' + str + '</tr>');
		$('#specaloader tbody tr:last').find('input').val('');
		$('#specaloader tbody tr:last').find('input#speca_kol\\[\\]').val('1,00');

		$("#speca_title\\[\\]").autocomplete("/content/helpers/price.helpers.php?clid=" + clid, {
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
				return '<div">' + data[0] + '</div>';
			},
			formatResult: function (data) {
				return data[0];
			}
		});
		$("#speca_title\\[\\]").result(function (value, data) {

			$(this).closest('tr.tstring').find('#speca_summa\\[\\]').val(data[1]);
			$(this).closest('tr.tstring').find('#price_in\\[\\]').val(data[2]);
			$(this).closest('tr.tstring').find('#speca_ediz\\[\\]').val(data[3]);
			$(this).closest('tr.tstring').find('#speca_nds\\[\\]').val(data[4]);
			$(this).closest('tr.tstring').find('#speca_artikul\\[\\]').val(data[5]);
			$(this).closest('tr.tstring').find('#speca_prid\\[\\]').val(data[6]);

		});

	}

	function showSpeca() {

		$('#specaloader').closest('div.flex-container').toggleClass('hidden');

	}

	function startSpeca() {

		//console.log($('#calculate').prop('checked'));

		if ($('#calculate').prop('checked') === true) {

			$('#kol').prop('disabled', true);
			$('#marg').prop('disabled', true);
			$('#margp').prop('disabled', true);
			$('#kol').removeClass('required');
			$('#marg').removeClass('required');
			//$('#calculate').prop('checked', false);

			//if (action !== 'edit')
			if (did === 0)
				$('#chspeca').toggleClass('hidden');

		}
		else {

			$('#kol').prop('disabled', false);
			$('#marg').prop('disabled', false);
			$('#margp').prop('disabled', false);
			$('#kol').addClass('required');
			$('#marg').addClass('required');
			//$('#calculate').prop('checked', true);

			//if (action !== 'edit')
			if (did === 0)
				$('#chspeca').toggleClass('hidden');

		}

	}
	<?php //--add, edit ?>

	<?php //--close ?>
	function getResSel() {

		var str = $('#sid option:selected').data('id');

		if (str === 'lose') {

			$('#kol_fact').val('0,00');
			$('#marga').val('0,00');
			$('#loseAnswers').removeClass('hidden');
			$('#winAnswers').addClass('hidden');

		}
		else if (str === 'win') {

			$('#kol_fact').val($kol_fact);
			$('#marga').val($marga);
			$('#winAnswers').removeClass('hidden');
			$('#loseAnswers').addClass('hidden');

		}

		$('#des_fact').val($('#sid :selected').data('content'));

	}

	<?php //--close ?>

	function GetCalc() {

		if ($('#calculate:checked').length == 1) {

			$('#kol').attr('disabled', true);
			$('#marg').attr('disabled', true);
			$('#margp').attr('disabled', true);
			$('#kol').removeClass('required');
			$('#marg').removeClass('required');

		}
		else {

			$('#kol').attr('disabled', false);
			$('#marg').attr('disabled', false);
			$('#margp').attr('disabled', false);
			$('#kol').addClass('required');
			$('#marg').addClass('required');

		}
	}

	function CheckSum() {

		var sum = $('#kol').val().replace(/ /g, '').replace(/,/g, '.');//Значение поля Сумма сделки
		if (sum === '') sum = 0;
		var kol_sum = $('#credit tr').size() - 1;//Подсчет количества элементов содержащих сумму
		var sum_2 = 0;
		for (var j = 0; j < kol_sum; j++) {
			sum_1 = $('#summa_credit\\[' + j + '\\]').val().replace(/ /g, '').replace(/,/g, '.');
			sum_2 += parseFloat(sum_1);
		}
		delta = sum - sum_2;
		delta = delta.toFixed(2);
		if (delta < 0) delta = 'Перебор. Уменьшите сумму на ' + numFormat(delta) + ' <?=$valuta?>';
		else if (delta == 0) delta = 'Вся сумма расписана';
		else delta = numFormat(delta) + ' <?=$valuta?>';
		$('#check_sum').html(delta);
	}

	function CheckMarg() {
		var pers = $('#margp').val().replace(/ /g, '').replace(/,/g, '.');//Значение поля маржа в %
		var kol = $('#kol').val().replace(/ /g, '').replace(/,/g, '.');//Значение поля маржа в %
		var marg = (kol * pers) / 100;
		m = numFormat(marg, ',', '');
		$('#marg').val(m);
	}

	function fncld() {

		if ($('#cld option:selected').val() == 'org') {

			$('.iclient').removeClass('hidden');
			$('.iperson').addClass('hidden');
			$('#pid').val('');
			$('#person').val('').removeClass('required');
			$('#client').addClass('required');

			$('#lst_payer').removeClass('required');

		}
		if ($('#cld option:selected').val() == 'psn') {

			$('.iclient').addClass('hidden');
			$('.iperson').removeClass('hidden');
			$('#clid').val('');
			$('#client').val('').removeClass('required');
			$('#person').addClass('required');

			$('#lst_payer').addClass('required');

		}
	}

	function selItem(tip, id) {

		if (tip === 'client') {

			var payer = $('#payer').val();
			var plist = '<?=$plist?>';

			$.get('/content/helpers/deal.helpers.php?action=get.personsplus&clid=' + id + '&payer=' + payer + '&plist=' + plist, function (data) {

				$('#pid_list').empty().html(data);

				if (!isMobile) {

					$('#persons').find(".multiselect").multiselect('destroy').multiselect({
						sortable: true,
						searchable: true
					});
					$(".connected-list").css('height', "200px");

				}

			});

		}
		if (tip === 'person') {

			$("#pid").val(id);

		}

	}

	function numFormat(n, d, s) {
		if (arguments.length === 2) {
			s = "`";
		}
		if (arguments.length == 1) {
			s = "`";
			d = ",";
		}
		n = n.toString();
		a = n.split(d);
		x = a[0];
		y = a[1];
		z = "";
		if (typeof (x) != "undefined") {
			for (i = x.length - 1; i >= 0; i--)
				z += x.charAt(i);
			z = z.replace(/(\d{3})/g, "$1" + s);
			if (z.slice(-s.length) == s)
				z = z.slice(0, -s.length);
			x = "";
			for (i = z.length - 1; i >= 0; i--)
				x += z.charAt(i);
			if (typeof (y) != "undefined" && y.length > 0)
				x += d + y;
		}
		return x;
	}

	<?php //для массовой передачи клиентов ?>
	function showd() {

		var cel = $('#doAction:checked').val();
		var u = 0;

		$('#counts').html(500);

		switch (cel) {

			case "userChange":

				$('#userdiv').removeClass('hidden');
				$('#dostupdiv').addClass('hidden');
				$('#stepdiv').addClass('hidden');
				$('#reazon').removeClass('hidden');
				$('#datdiv').addClass('hidden');
				$('#appendix').removeClass('hidden');

				break;
			case "dostupChange":
			case "dostupDelete":

				$('#dostupdiv').removeClass('hidden');
				$('#userdiv').addClass('hidden');
				$('#stepdiv').addClass('hidden');
				$('#reazon').addClass('hidden');
				$('#datdiv').addClass('hidden');
				$('#appendix').addClass('hidden');

				if (cel === 'dostupDelete') {

					u = $('#dostup').val();

					$('#duser').val(u);

				}
				else {
					$('#duser').val(0);
				}

				if (isAdmin === 'on') {

					$('#counts').html(alls);

				}

				break;
			case "stepChange":

				$('#stepdiv').removeClass('hidden');
				$('#userdiv').addClass('hidden');
				$('#dostupdiv').addClass('hidden');
				$('#reazon').removeClass('hidden');
				$('#datdiv').addClass('hidden');
				$('#appendix').addClass('hidden');

				break;
			case "datumChange":

				$('#stepdiv').addClass('hidden');
				$('#userdiv').addClass('hidden');
				$('#dostupdiv').addClass('hidden');
				$('#reazon').removeClass('hidden');
				$('#datdiv').removeClass('hidden');
				$('#appendix').addClass('hidden');

				break;
			default:

				$('#userdiv').addClass('hidden');
				$('#dostupdiv').addClass('hidden');
				$('#stepdiv').addClass('hidden');
				$('#reazon').addClass('hidden');
				$('#datdiv').addClass('hidden');
				$('#appendix').addClass('hidden');

				break;
		}

		$('#dialog').center();

	}

	<?php //для счетов ?>
	function GetNns() {

		var summa = $('#summa_credit').val().replace(/ /g, '').replace(/,/g, '.');//Значение поля маржа в %
		var pers = $('#nds_proc').val().replace(/ /g, '').replace(/,/g, '.');//Значение поля маржа в %

		if ($nalogPercent > 0) m = summa * $nalogPercent;
		else m = summa * (1 - 1 / (1 + pers / 100));

		m = m.toFixed(2);
		m = numFormat(m, ',', ' ').replace('.', ',');

		$('#nds_credit').val(m);

	}

	<?php //выставление счетов?>
	function createEditor() {

		var html = $('#dialog #suffix').val();
		var hh = ($(window).height() * 0.7 - 105) + 'px';//'300px';

		//if ($(document).width() < 945) hh = '270px';

		editor = CKEDITOR.replace('suffix',
			{
				height: hh,
				toolbar: [
					['Bold', 'Italic', 'Underline', '-', 'NumberedList', 'BulletedList', '-', 'Link', 'Unlink'],
					['-', 'Undo', 'Redo', '-', 'PasteText', 'PasteFromWord', 'HorizontalRule'],
					['JustifyLeft', 'JustifyCenter', 'JustifyRight']
				]
			});
		$('.cke_bottom').addClass('hidden');
		$(".nano").nanoScroller();

		CKEDITOR.on("instanceReady", function () {

			$('#dialog').center();

		});

	}

	function remEditor() {
		var html = $('#cke_editor_suffix').html();
		if (editor) {
			$('#suffix').val(html);
			editor.destroy();
			editor = null;
		}
		return true;
	}

	function addSuffix() {

		$.get('/content/forms/form.deal.php?action=credit.suffix', function (data) {

			var oEditor = CKEDITOR.instances.suffix;
			oEditor.insertHtml(data);

		});
	}

	function massSubmit() {

		var empty = $(".required").removeClass("empty").filter('[value=""]').addClass("empty");
		if (empty.size()) {
			empty.css({color: "#FFF", background: "#FF8080"});
			alert("Не заполнены обязательные поля\n\rОни выделены цветом");
		}
		if (!empty.size()) {
			$('#dialog').css('display', 'none');
			$('#dialog_container').css('display', 'none');

			var str = $('#dealForm').serialize() + '&' + $('#pageform').serialize();
			var url = "/content/core/core.deals.php";

			$('#message').empty().fadeTo(1, 1).css('display', 'block').append('<div id=loader><img src="/assets/images/loader.gif"> Загрузка данных...</div>');

			$.post(url, str, function (data) {

				$('#resultdiv').empty();

				configpage();

				$('#message').fadeTo(1, 1).css('display', 'block').html(data.result);
				setTimeout(function () {
					$('#message').fadeTo(1000, 0);
				}, 20000);

			}, 'json')
				.fail(function (xhr, status, error) {

					//console.log(status)
					//console.log(error)

					$('#message').fadeTo(1, 1).css('display', 'block').html(status);

				});

		}

	}

</script>