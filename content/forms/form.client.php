<?php
/* ============================ */
/*         SalesMan CRM         */
/* ============================ */
/* (C) 2016 Vladislav Andreev   */
/*       SalesMan Project       */
/*        www.isaler.ru         */
/*        ver. 2019.x           */
/* ============================ */

error_reporting(E_ERROR);
header("Pragma: no-cache");

use Salesman\Elements;

/**
 * Эксперимент с форматированием номеров телефонов.
 * Пока не работает реакция на события, надо их привязать к событиям плагина
 */

$rootpath = dirname( __DIR__, 2 );

include $rootpath."/inc/config.php";
include $rootpath."/inc/dbconnector.php";
include $rootpath."/inc/auth.php";
include $rootpath."/inc/func.php";
include $rootpath."/inc/settings.php";
include $rootpath."/inc/language/".$language.".php";

$thisfile = basename(__FILE__);

global $userRights;

$clid   = (int)$_REQUEST['clid'];
$pid    = (int)$_REQUEST['pid'];
$did    = (int)$_REQUEST['did'];
$action = $_REQUEST['action'];

$format_tel = yexplode(",", (string)$format_tel, 0);

$format_phone    = $GLOBALS['format_phone'];
$relTitleDefault = $GLOBALS['relTitleDefault'];
$loyalDefault    = $GLOBALS['loyalDefault'];
$actDefault      = $GLOBALS['actDefault'];
$pathDefault     = $GLOBALS['pathDefault'];

//время для напоминания + 2 часа от текущего
$thistime = date('H:00', mktime((int)date('H') + 3, (int)date('i'), (int)date('s'), (int)date('m'), (int)date('d'), (int)date('Y')) + ((int)$tzone) * 3600);

//доп.напстройки
$customSettings = (array)customSettings('settingsMore');
$timecheck      = ($customSettings['timecheck'] == 'yes') ? 'true' : 'false';

// перенаправляем на новую форму
if ($action == 'add') {
	$action = 'edit';
}

if ($action == "express") {

	$rid       = (int)$_REQUEST['rid'];
	$messageid = (int)$_REQUEST['messageid'];
	$phone     = $_REQUEST['phone'];

	if ($phone == 'undefined') {
		$phone = '';
	}

	$req = "red";
	$rq  = '<b title="Обязательное поле" class="redd">*</b>';

	//подгрузим данные из письма
	if ($messageid > 0) {

		$result  = $db -> getRow("SELECT * FROM {$sqlname}ymail_messages WHERE id = '$messageid' and identity = '$identity'");
		$datum   = $result['datum'];
		$theme   = $result['theme'];
		$email   = $result['from'];
		$name    = $result['fromname'];
		$content = $result['content'];
		$did     = (int)$result['did'];

		$content = html2text($content);
		$content = $theme."\n\r".$content;

		$data = html2data($content);

		//print_r($data);

		if ($data['phone'] != '') {

			$phones = preparePhoneSmart($data['phone'], false, true);

			$phone = $mob = [];

			foreach ($phones as $tel) {

				if (is_mobile($tel)) {
					$mob[] = $tel;
				}
				else {
					$phone[] = $tel;
				}

			}

			$tel   = $phone[0];
			$mob   = yimplode(",", $mob);
			$phone = yimplode(",", $phone);

		}

		$email2 = ($data['email'] != '') ? $data['email'] : "";
		$site_url = ($data['site'] != '') ? $data['site'] : "";

		if ($email != '') {

			$e = explode(", ", (string)$email2);

			if (!in_array($email, (array)$e)) {
				$e[] = $email;
			}

			$e = array_unique($e);

			$email = implode(", ", $e);

		}
		else {
			$email = $email2;
		}

		$result = $db -> getRow("SELECT * FROM {$sqlname}ymail_messages WHERE id = '$messageid' and identity = '$identity'");
		$clid   = (int)$result['clid'];
		$pid    = (int)$result['pid'];

	}

	//подгрузим данные из письма
	if ($rid > 0) {

		$result    = $db -> getRow("SELECT * FROM {$sqlname}ymail_messagesrec WHERE id = '$rid' and identity = '$identity'");
		$messageid = (int)$result['mid'];
		$tip       = $result['tip'];
		$email     = $result['email'];
		$name      = $result['name'];

		if ($name == '0') {
			$name = '';
		}

		$result  = $db -> getRow("SELECT * FROM {$sqlname}ymail_messages WHERE id = '$messageid' and identity = '$identity'");
		$datum   = $result['datum'];
		$theme   = $result['theme'];
		$content = $result['content'];

		$content = html2text($content);
		$content = $theme."\n\r".$content;

	}

	//если при добавлении указан телефон, то находим канал
	if ($phone) {

		$tel = $phone;

		$destination = $db -> getOne("SELECT did FROM {$sqlname}callhistory WHERE phone = '".preparePhone($phone)."' AND identity = '".$identity."' ORDER BY datum LIMIT 1");
		if ($destination > 0) {
			$pathDefault = getClientpath( '', '', $destination );
		}

		//todo: сделать проверку номера перед добавлением и если есть, то привязать звонки и предложить перейти к карточке клиента
		/*
		$caller = json_decode(getCallerID($phone, true, $tr, true), true);

		if ($caller['clid'] > 0) $clid = $caller['clid'];
		if ($caller['pid'] > 0) $pid = $caller['pid'];
		*/

	}

	//настройки пользователя
	$usersettings = json_decode((string)$db -> getOne("select usersettings from {$sqlname}user where iduser='".$iduser1."' and identity = '$identity'"), true);

	$thistime = date('H') > 20 ? current_datum(-1)." 09:00" : current_datum()." ".$thistime;

	?>
	<DIV class="zagolovok"><B>Добавить Экспресс</B></DIV>
	<FORM action="/content/core/core.client.php" method="post" enctype="multipart/form-data" name="form" id="form">
		<INPUT type="hidden" id="action" name="action" value="client.express">
		<INPUT type="hidden" id="tip" name="tip" value="person">
		<INPUT type="hidden" id="messageid" name="messageid" value="<?= $messageid ?>">
		<INPUT type="hidden" id="rid" name="rid" value="<?= $rid ?>">
		<INPUT type="hidden" id="income" name="income" value="<?= $phone ?>">

		<?php
		//если активирована опция контроля количества дел
		$tcount = getOldTaskCount((int)$iduser1);

		if ((int)$otherSettings['taskControl'] > 0 && $otherSettings['taskControlClientAdd'] && (int)$tcount >= (int)$otherSettings['taskControl']) {

			print '<div class="warning"><b class="red">Включен режим контроля выполненения дел.</b><br>У вас '.$tcount.' не выполненных дел - вы не можете создавать новые напоминания и добавлять Клиентов и Контакты, пока не закроете старые напоминания.</div>';
			exit();

		}
		?>

		<?php
		//загружаем настройки экспресс-формы
		$efields = json_decode($db -> getOne("select params from {$sqlname}customsettings where tip='eform' and identity = '$identity'"), true);
		$string  = $stringMore = [];

		$xf = $db -> getIndCol( "fld_name", "SELECT fld_name, fld_sub FROM {$sqlname}field WHERE fld_tip = 'client' and identity = '$identity'" );

		foreach ($efields as $tip => $fields) {

			foreach ($fields as $input => $param) {

				if ($param['active'] != 'yes') {
					goto b;
				}

				$param['requered'] = ($param['requered'] == 'yes') ? "required" : "";

				$s = $x = '';

				if ($input == 'title' && $fieldsNames['client']['title'] != '') {

					$s = '
					<div class="flex-container mb10" data-block="client-'.$input.'">
						<div class="flex-string wp20 gray2 fs-12 pt7 right-text">'.$fieldsNames['client']['title'].':</div>
						<div class="flex-string wp80 pl10" id="org">
							<INPUT name="client[title]" type="text" data-class="'.$param['requered'].'" class="wp93 '.$param['requered'].'" id="client[title]" value="" placeholder="Например: Сэйлзмэн, ООО">
							<INPUT type="hidden" id="client[clid]" name="client[clid]" value="">
						</div>
					</div>';

				}
				elseif ($input == 'head_clid' && $fieldsNames['client']['head_clid'] != '') {

					$s = '
					<div class="flex-container box--child mt10 mb10" data-block="client-'.$input.'">

						<div class="flex-string wp20 gray2 fs-12 pt7 right-text">'.$fieldsNames['client']['head_clid'].':</div>
						<div class="flex-string wp80 pl10">
						
							<div class="relativ wp93" id="head_clid">
								<INPUT type="hidden" id="client[head_clid]" name="client[head_clid]" value="">
								<INPUT id="lst_spisok" type="text" data-class="'.$param['requered'].'" class="wp100 '.$param['requered'].'" value="" readonly onclick="_.debounce(get_orgspisok(\'lst_spisok\',\'head_clid\',\'/content/helpers/client.helpers.php?action=get_orgselector\',\'client\\[head_clid\\]\'), 500)" placeholder="Нажмите для выбора">
								<span class="idel"><i title="Очистить" onclick="$(\'input#client\\[head_clid\\]\').val(\'\'); $(\'#lst_spisok\').val(\'\');" class="icon-block red hand"></i></span>
							</div>
							<div class="fs-10 gray2 em">Укажите головную организацию</div>
							
						</div>
	
					</div>';

				}
				elseif ($input == 'phone' && $fieldsNames['client']['phone'] != '') {

					$su = '';

					if ($format_phone != '') {

						if ($phone != '') {

							$phones = yexplode(",", (string)$phone);
							foreach ($phones as $i => $xphone) {

								if ($i == (count((array)$phones) - 1)) {
									$adder = '<span class="adder hand" title="" data-block="phoneBlock" data-main="vphone" data-mask="'.$format_phone.'"><i class="icon-plus-circled green"></i></span>';
								}
								else {
									$adder = '';
								}

								$su .= '<div class="phoneBlock paddbott5 relativv">
								<INPUT name="client[phone][]" type="text" data-class="'.$param['requered'].'" class="phone w250 '.$param['requered'].'" id="client[phone][]" value="'.$xphone.'" placeholder="Формат: '.$format_tel.'" data-id="vphone" data-action="valphone" data-type="client.helpers" autocomplete="off">
								<span class="remover hand" data-parent="vphone"><i class="icon-minus-circled red"></i></span>'.$adder.'
							</div>';

							}
						}
						else {

							$su = '<div class="phoneBlock paddbott5 relativv">
							<INPUT name="client[phone][]" type="text" data-class="'.$param['requered'].'" class="phone w250 '.$param['requered'].'" id="client[phone][]" value="'.$client['phone'].'" placeholder="Формат: '.$format_tel.'" data-id="vphone" data-action="valphone" data-type="client.helpers" autocomplete="off">
							<span class="remover hand" data-parent="vphone"><i class="icon-minus-circled red"></i></span>
							<span class="adder hand" title="" data-block="phoneBlock" data-main="vphone" data-mask="'.$format_phone.'"><i class="icon-plus-circled green"></i></span>
						</div>';

						}
					}
					else {

						$su = '<div class="phoneBlock paddbott5 relativv">
						<INPUT name="client[phone]" type="text" data-class="'.$param['requered'].'" class="phone wp93 '.$param['requered'].'" id="client[phone]" value="'.$phone.'" placeholder="Формат: '.$format_tel.'" data-id="vphone" data-action="valphone" data-type="client.helpers" autocomplete="off">
						<div class="em blue smalltxt">Используйте <b>запятую</b> в качестве разделителя</div>
					</div>';

					}


					$s = '<div class="flex-container mb10" data-block="client-'.$input.'">
					<div class="flex-string wp20 gray2 fs-12 pt7 right-text">'.$fieldsNames['client']['phone'].':</div>
					<div class="flex-string wp80 pl10">
						<div id="vphone">'.$su.'</div>
					</div>
				</div>';

				}
				elseif ($input == 'fax' && $fieldsNames['client']['fax'] != '') {

					$su = '';

					if ($format_phone != '') {

						if ($client['fax'] != '') {

							$phones = yexplode(",", (string)$client['fax']);
							foreach ($phones as $i => $xphone) {

								if ($i == (count((array)$phones) - 1)) {
									$adder = '<span class="adder hand" title="" data-block="phoneBlock" data-main="vfax" data-mask="'.$format_phone.'"><i class="icon-plus-circled green"></i></span>';
								}
								else {
									$adder = '';
								}

								$su .= '<div class="phoneBlock paddbott5 relativv">
								<INPUT name="client[fax][]" type="text" data-class="'.$param['requered'].'" class="phone w250 '.$param['requered'].'" id="client[fax][]" value="'.$xphone.'" placeholder="Формат: '.$format_tel.'" data-id="vfax" data-action="valphone" data-type="client.helpers" autocomplete="off">
								<span class="remover hand" data-parent="vfax"><i class="icon-minus-circled red"></i></span>'.$adder.'
							</div>';

							}

						}
						else {

							$su = '<div class="phoneBlock paddbott5 relativv">
							<INPUT name="client[fax][]" type="text" data-class="'.$param['requered'].'" class="phone w250 '.$param['requered'].'" id="client[fax][]" value="'.$client['fax'].'" placeholder="Формат: '.$format_tel.'" data-id="vfax" data-action="valphone" data-type="client.helpers" autocomplete="off">
							<span class="remover hand" data-parent="vfax"><i class="icon-minus-circled red"></i></span>
							<span class="adder hand" title="" data-block="phoneBlock" data-main="vfax" data-mask="'.$format_phone.'"><i class="icon-plus-circled green"></i></span>
						</div>';

						}
					}
					else {

						$su = '<div class="phoneBlock paddbott5 relativv">
						<INPUT name="client[fax]" type="text" data-class="'.$param['requered'].'" class="phone wp93 '.$param['requered'].'" id="client[fax]" value="'.$client['fax'].'" placeholder="Формат: '.$format_tel.'" data-id="vfax" data-action="valphone" data-type="client.helpers" autocomplete="off">
						<div class="em blue smalltxt">Используйте <b>запятую</b> в качестве разделителя</div>
					</div>';

					}

					$s = '<div class="flex-container mb10" data-block="client-'.$input.'">
					<div class="flex-string wp20 gray2 fs-12 pt7 right-text">'.$fieldsNames['client']['fax'].':</div>
					<div class="flex-string wp80 pl10">
						<div id="vfax">'.$su.'</div>
					</div>
				</div>';

				}
				elseif ($input == 'mail_url' && $fieldsNames['client']['mail_url']) {

					$s = '
					<div class="flex-container mb10" data-block="client-'.$input.'">
						<div class="flex-string wp20 gray2 fs-12 pt7 right-text">'.$fieldsNames['client']['mail_url'].':</div>
						<div class="flex-string wp80 pl10" id="vmaile">
							<INPUT name="client[mail_url]" type="text" data-class="'.$param['requered'].'" class="wp93 '.$param['requered'].' validate" id="client[mail_url]" value="" onMouseOut="$(\'#ospisok\').remove();" onblur="$(\'#ospisok\').remove();" autocomplete="off" data-url="/content/helpers/client.helpers.php" data-action="valmail">
						</div>
					</div>';

				}
				elseif ($input == 'clientpath' && $fieldsNames['client']['clientpath']) {

					$su = $sub = '';

					$pathDefault = ($client['clientpath'] > 0) ? $client['clientpath'] : $pathDefault;

					$element = new Salesman\Elements();
					$su      = $element -> ClientpathSelect('client[clientpath]', [
						"class" => [
							"wp93",
							$param['requered']
						],
						"sel"   => $pathDefault,
						"data"  => 'data-class="'.$param['requered'].'"'
					]);

					if (!$otherSettings['guidesEdit']) {
						$sub = '<span class="hidden-iphone del">&nbsp;<a href="javascript:void(0)" onclick="add_sprav(\'clientpath\',\'client\\\[clientpath\\\]\')" title="Добавить"><i class="icon-plus-circled blue"></i></a></span>';
					}

					$s = '
					<div class="flex-container mb10" data-block="client-'.$input.'" data-xtype="'.$xf[$input].'">
						<div class="flex-string wp20 gray2 fs-12 pt7 right-text">'.$fieldsNames['client']['clientpath'].':</div>
						<div class="flex-string wp80 pl10 relativ">
							'.$su.'
							'.$sub.'
						</div>
					</div>';

				}
				elseif ($input == 'tip_cmr' && $fieldsNames['client']['tip_cmr']) {

					$su = '';

					$relDefault = ($client['tip_cmr'] > 0) ? $client['tip_cmr'] : $relTitleDefault;

					$element = new Salesman\Elements();
					$su      = $element -> RelationSelect('client[tip_cmr]', [
						"class" => [
							"wp93",
							$param['requered']
						],
						"sel"   => $relDefault,
						"data"  => 'data-class="'.$param['requered'].'"'
					]);

					$s = '
					<div class="flex-container mb10" data-block="client-'.$input.'" data-xtype="'.$xf[$input].'">
						<div class="flex-string wp20 gray2 fs-12 pt7 right-text">'.$fieldsNames['client']['tip_cmr'].':</div>
						<div class="flex-string wp80 pl10">
							'.$su.'
						</div>
					</div>';

				}
				elseif ($input == 'idcategory' && $fieldsNames['client']['idcategory']) {

					$su = $sub = '';

					$element = new Elements();
					$su      = $element -> IndustrySelect('client[idcategory]', [
						"class" => [
							"wp93",
							$param['requered']
						],
						"sel"   => $client['idcategory'],
						"tip"   => $tt,
						"data"  => 'data-class="'.$param['requered'].'"'
					]);

					if (!$otherSettings['guidesEdit']) {
						$sub = '<span class="hidden-iphone">&nbsp;<a href="javascript:void(0)" onclick="add_sprav(\'category\',\'client\\\[idcategory\\\]\')" title="Добавить"><i class="icon-plus-circled blue"></i></a>&nbsp;</span>';
					}

					$s = '
					<div class="flex-container mb10" data-block="client-'.$input.'">
						<div class="flex-string wp20 gray2 fs-12 pt7 right-text">'.$fieldsNames['client']['idcategory'].':</div>
						<div class="flex-string wp80 pl10 relativ">
							'.$su.'
							'.$sub.'
						</div>
					</div>';

				}
				elseif ($input == 'des' && $fieldsNames['client']['des']) {

					$s = '
					<div class="flex-container mb10" data-block="client-'.$input.'">
						<div class="flex-string wp20 gray2 fs-12 pt7 right-text">'.$fieldsNames['client']['des'].':</div>
						<div class="flex-string wp80 pl10">
							<textarea name="client[des]" rows="2" id="client[des]" class="wp93 '.$param['requered'].'" data-class="'.$param['requered'].'"></textarea>
						</div>
					</div>';

				}
				elseif ($input == 'territory' && $fieldsNames['client']['territory']) {

					$su = $sub = '';

					$element = new Elements();
					$su      = $element -> TerritorySelect('client[territory]', [
						"class" => [
							"wp93",
							$param['requered']
						],
						"sel"   => $territory,
						"data"  => 'data-class="'.$param['requered'].'"'
					]);

					if (!$otherSettings['guidesEdit']) {
						$sub = '<span class="hidden-iphone">&nbsp;<a href="javascript:void(0)" onclick="add_sprav(\'territory\',\'client\\\[territory\\\]\')" title="Добавить"><i class="icon-plus-circled blue"></i></a></span>';
					}

					$s = '
					<div class="flex-container mb10" data-block="client-'.$input.'" data-xtype="'.$xf[$input].'">
						<div class="flex-string wp20 gray2 fs-12 pt7 right-text">'.$fieldsNames['client']['territory'].':</div>
						<div class="flex-string wp80 pl10 relativ">
							'.$su.'
							'.$sub.'
						</div>
					</div>';

				}
				elseif ($input == 'address' && $fieldsNames['client']['address']) {

					$s = '
					<div class="flex-container mb10" data-block="client-'.$input.'" data-xtype="'.$xf[$input].'">
						<div class="flex-string wp20 gray2 fs-12 pt7 right-text">'.$fieldsNames['client']['address'].':</div>
						<div class="flex-string wp80 pl10">
							<div class="relativ"><INPUT name="client[address]" id="client[address]" class="wp93 '.$param['requered'].'" value="" data-class="'.$param['requered'].'" data-type="address"></div>
						</div>
					</div>';

				}
				elseif ($input == 'site_url' && $fieldsNames['client']['site_url']) {

					$s = '
					<div class="flex-container mb10" data-block="client-'.$input.'">
	
						<div class="flex-string wp20 gray2 fs-12 pt7 right-text">'.$fieldsNames['client']['site_url'].':</div>
						<div class="flex-string wp80 pl10" id="surl">
							<INPUT name="client[site_url]" type="text" id="client[site_url]" class="wp93 '.$param['requered'].' validate" value="'.$site_url.'" onMouseOut="$(\'#ospisok\').remove();" onblur="$(\'#ospisok\').remove();" autocomplete="off" data-class="'.$param['requered'].'" data-url="/content/helpers/client.helpers.php" data-action="valsite">
						</div>
	
					</div>';

				}
				elseif ($input == 'person' && $fieldsNames['person']['person'] != '') {

					$s = '
					<div class="flex-container mb10" data-block="person-'.$input.'">
	
						<div class="flex-string wp20 gray2 fs-12 pt7 right-text">'.$fieldsNames['person']['person'].':</div>
						<div class="flex-string wp80 pl10" id="pers">
							<input name="person[person]" type="text" id="person[person]" class="wp93 '.$param['requered'].'" value="'.$name.'" autocomplete="off" onkeyup="validate(\'person[person]\', \'pers\', \'/content/helpers/person.helpers.php\', \'validate\');" onblur="$(\'#ospisok\').remove();" placeholder="Начните с Фамилии. Например: Иванов Семен Петрович" data-class="'.$param['requered'].'">
						</div>
	
					</div>';

				}
				elseif ($input == 'ptitle' && $fieldsNames['person']['ptitle'] != '') {

					$fld_var = $db -> getOne("SELECT fld_var FROM {$sqlname}field WHERE fld_name = '$input' and identity = '$identity'");
					$vars = str_replace(" \n", ",", $fld_var);

					$x = '<div class="smalltxt">Например: Генеральный директор</div>';
					$dx = !empty($vars) ? '' : 'suggestion';

					if( !empty($vars) ){

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
					<div class="flex-container mb10" data-block="person-'.$input.'">
	
						<div class="flex-string wp20 gray2 fs-12 pt7 right-text">'.$fieldsNames['person']['ptitle'].':</div>
						<div class="flex-string wp80 pl10">
							<input name="person[ptitle]" type="text" id="person[ptitle]" class="wp93 '.$param['requered'].' ptitle '.$dx.'" value="" placeholder="Например: Генеральный директор" data-class="'.$param['requered'].'">
							'.$x.'
						</div>
	
					</div>';

				}
				elseif ($input == 'tel' && $fieldsNames['person']['tel'] != '') {

					$su = $sub = '';

					if ($format_phone != '') {

						if ($tel != '') {

							$phones = yexplode(",", (string)$tel);

							foreach ($phones as $xphone) {

								if ($i == (count((array)$phones) - 1)) {
									$adder = '<span class="adder hand" title="" data-block="phoneBlock" data-main="vtel" data-mask="'.$format_phone.'"><i class="icon-plus-circled green"></i></span>';
								}
								else {
									$adder = '';
								}

								$su .= '
								<div class="phoneBlock paddbott5 relativv">
									<INPUT name="person[tel][]" type="text" class="phone w250 '.$param['requered'].'" id="person[tel][]" alt="phone" autocomplete="off" value="'.$xphone.'" placeholder="Формат: '.$format_tel.'" data-id="vtel" data-action="valphone" data-type="person.helpers" data-class="'.$param['requered'].'">
									<span class="remover hand" data-parent="vtel"><i class="icon-minus-circled red"></i></span>'.$adder.'
								</div>';

							}
						}
						else {

							$su = '
							<div class="phoneBlock paddbott5 relativv">
								<INPUT name="person[tel][]" type="text" class="phone w250 '.$param['requered'].'" id="person[tel][]" alt="phone" autocomplete="off" value="'.$person['tel'].'" placeholder="Формат: '.$format_tel.'" data-id="vtel" data-action="valphone" data-type="person.helpers" data-class="'.$param['requered'].'">
								<span class="remover hand" data-parent="vtel"><i class="icon-minus-circled red"></i></span>
								<span class="adder hand" title="" data-block="phoneBlock" data-main="vtel" data-mask="'.$format_phone.'"><i class="icon-plus-circled green"></i></span>
							</div>';

						}

					}
					else {

						$su = '
						<div class="phoneBlock paddbott5 relativv">
							<INPUT name="person[tel]" type="text" class="phone wp93 '.$param['requered'].'" id="person[tel]" alt="phone" autocomplete="off" value="'.$tel.'" placeholder="Формат: '.$format_tel.'" data-id="vtel" data-action="valphone" data-type="person.helpers" data-class="'.$param['requered'].'">
							<div class="em blue smalltxt">Используйте <b>запятую</b> в качестве разделителя</div>
						</div>';

					}

					$s = '
					<div class="flex-container mb10" data-block="person-'.$input.'">
	
						<div class="flex-string wp20 gray2 fs-12 pt7 right-text">'.$fieldsNames['person']['tel'].':</div>
						<div class="flex-string wp80 pl10" id="vtel">
							'.$su.'
						</div>
	
					</div>';

				}
				elseif ($input == 'mob' && $fieldsNames['person']['mob'] != '') {

					$su = $sub = '';

					if ($format_phone != '') {

						if ($mob != '') {

							$phones = yexplode(",", (string)$mob);
							foreach ($phones as $i => $xphone) {

								if ($i == (count((array)$phones) - 1)) {
									$adder = '<span class="adder hand" title="" data-block="phoneBlock" data-main="vmob" data-mask="'.$format_phone.'"><i class="icon-plus-circled green"></i></span>';
								}
								else {
									$adder = '';
								}

								$su .= '
								<div class="phoneBlock paddbott5 relativv">
									<INPUT name="person[mob][]" type="text" class="phone w250 '.$param['requered'].'" id="person[mob][]" alt="phone" autocomplete="off" value="'.$xphone.'" placeholder="Формат: '.$format_tel.'" data-id="vmob" data-action="valphone" data-type="person.helpers" data-class="'.$param['requered'].'">
									<span class="remover hand" data-parent="vmob"><i class="icon-minus-circled red"></i></span>'.$adder.'
								</div>';

							}

						}
						else {

							$su = '
							<div class="phoneBlock paddbott5 relativv">
								<INPUT name="person[mob][]" type="text" class="phone w250 '.$param['requered'].'" id="person[mob][]" alt="phone" autocomplete="off" value="'.$person['mob'].'" placeholder="Формат: '.$format_tel.'" data-id="vmob" data-action="valphone" data-type="person.helpers" data-class="'.$param['requered'].'">
								<span class="remover hand" data-parent="vmob"><i class="icon-minus-circled red"></i></span>
								<span class="adder hand" title="" data-block="phoneBlock" data-main="vmob" data-mask="'.$format_phone.'"><i class="icon-plus-circled green"></i></span>
							</div>';

						}

					}
					else {

						$su = '
						<div class="phoneBlock paddbott5 relativv">
							<INPUT name="person[mob]" type="text" class="phone wp93 '.$param['requered'].'" id="person[mob]" alt="phone" autocomplete="off" value="'.$mob.'" placeholder="Формат: '.$format_tel.'" data-id="vmob" data-action="valphone" data-type="person.helpers" data-class="'.$param['requered'].'">
							<div class="em blue smalltxt">Используйте <b>запятую</b> в качестве разделителя</div>
						</div>';

					}

					$s = '
					<div class="flex-container mb10" data-block="person-'.$input.'">
	
						<div class="flex-string wp20 gray2 fs-12 pt7 right-text">'.$fieldsNames['person']['mob'].':</div>
						<div class="flex-string wp80 pl10" id="vmob">
							'.$su.'
						</div>
	
					</div>';

				}
				elseif ($input == 'mail' && $fieldsNames['person']['mail'] != '') {

					$s = '
					<div class="flex-container mb10" data-block="person-'.$input.'">
	
						<div class="flex-string wp20 gray2 fs-12 pt7 right-text">'.$fieldsNames['person']['mail'].':</div>
						<div class="flex-string wp80 pl10" id="vmail1">
							<INPUT name="person[mail]" type="text" class="wp93 '.$param['requered'].' validate" id="person[mail]" autocomplete="off" onmouseout="$(\'#ospisok\').remove();" value="'.$email.'" data-class="'.$param['requered'].'" data-url="/content/helpers/person.helpers.php" data-action="valmail">
						</div>
	
					</div>';

				}
				elseif ($input == 'loyalty' && $fieldsNames['person']['loyalty']) {

					$element = new Elements();
					$su      = $element -> LoyaltySelect('person[loyalty]', [
						"class" => [
							"wp93",
							$param['requered']
						],
						"sel"   => $loyalDefault,
						"data"  => 'data-class="'.$param['requered'].'"'
					]);

					$s = '
					<div class="flex-container mb10" data-block="person-'.$input.'">
	
						<div class="flex-string wp20 gray2 fs-12 pt7 right-text">'.$fieldsNames['person']['loyalty'].':</div>
						<div class="flex-string wp80 pl10">
							'.$su.'
						</div>
	
					</div>';

				}
				elseif ($input == 'rol' && $fieldsNames['person']['rol']) {

					$s = '
					<div class="flex-container mb10" data-block="person-'.$input.'">
	
						<div class="flex-string wp20 gray2 fs-12 pt7 right-text">'.$fieldsNames['person']['rol'].':</div>
						<div class="flex-string wp80 pl10">
							<input name="person[rol]" type="text" id="person[rol]" class="wp93 ac_input '.$param['requered'].'" value="" placeholder="Например: Принимает решение">
							<div class="smalltxt mt2 gray2 em">Например: Принимает решение</div>
						</div>
	
					</div>';

				}
				elseif (stripos($input, 'input') !== false) {

					$re = $db -> query("select * from {$sqlname}field where fld_tip='$tip' and fld_on='yes' and fld_name = '$input' and identity = '$identity' order by fld_order");
					while ($da = $db -> fetch($re)) {

						if ($da['fld_temp'] == "textarea") {

							$s = '
							<div class="flex-container mb10" data-block="'.$tip.'-'.$da['fld_name'].'" data-xtype="'.$da['fld_sub'].'">
	
								<div class="column12 grid-12">
									<div id="divider" class="red"><b>'.$da['fld_title'].'</b></div>
								</div>
	
							</div>
							<div class="flex-container mb10" data-block="'.$tip.'-'.$da['fld_name'].'" data-xtype="'.$da['fld_sub'].'">
	
								<div class="flex-string wp100 pl10">
									<textarea name="'.$tip.'['.$da['fld_name'].']" rows="4" class="pad3 wp97 '.$param['requered'].'" id="'.$tip.'['.$da['fld_name'].']" data-class="'.$param['requered'].'"></textarea><hr>
								</div>
	
							</div>';

						}
						elseif ($da['fld_temp'] == "--Обычное--") {

							$s = '
							<div class="flex-container mb10" data-block="'.$tip.'-'.$da['fld_name'].'" data-xtype="'.$da['fld_sub'].'">
	
								<div class="flex-string wp20 gray2 fs-12 pt7 right-text">'.$da['fld_title'].':</div>
								<div class="flex-string wp80 pl10">
									<INPUT name="'.$tip.'['.$da['fld_name'].']" type="text" id="'.$tip.'['.$da['fld_name'].']" class="wp93 '.$param['requered'].'" value="" autocomplete="off" data-class="'.$param['requered'].'">
								</div>
	
							</div>';

						}
						elseif ($da['fld_temp'] == "adres") {

							$s = '
							<div class="flex-container mb10" data-block="'.$tip.'-'.$da['fld_name'].'" data-xtype="'.$da['fld_sub'].'">
	
								<div class="flex-string wp20 gray2 fs-12 pt7 right-text">'.$da['fld_title'].':</div>
								<div class="flex-string wp80 pl10">
									<div class="relativ"><INPUT name="'.$tip.'['.$da['fld_name'].']" type="text" id="'.$tip.'['.$da['fld_name'].']" class="wp93 '.$param['requered'].'" value="" autocomplete="off" data-class="'.$param['requered'].'" data-type="address"></div>
								</div>
	
							</div>';

						}
						elseif ($da['fld_temp'] == "hidden" ) {
							$s = '<input id="'.$tip.'['.$da['fld_name'].']" name="'.$tip.'['.$da['fld_name'].']" type="hidden" value="">';
						}
						elseif ($da['fld_temp'] == "select") {

							$vars  = yexplode(",", (string)$da['fld_var']);
							$datas = [];
							foreach ($vars as $var) {
								$datas[] = [
									"id"    => $var,
									"title" => $var
								];
							}

							$su = Elements ::Select($tip.'['.$da['fld_name'].']', $datas, [
								"class"     => [
									"wp93",
									$param['requered']
								],
								"nowrapper" => true,
								"sel"       => $person[ $da['fld_name'] ],
								"data"      => 'data-class="'.$param['requered'].'"'
							]);

							$s = '
							<div class="flex-container mb10" data-block="'.$tip.'-'.$da['fld_name'].'" data-xtype="'.$da['fld_sub'].'">
	
								<div class="flex-string wp20 gray2 fs-12 pt7 right-text">'.$da['fld_title'].':</div>
								<div class="flex-string wp80 pl10">
									'.$su.'
								</div>
	
							</div>';

						}
						elseif ($da['fld_temp'] == "multiselect") {

							$vars = explode(",", (string)$da['fld_var']);
							$su   = '';

							foreach ($vars as $var) {

								$su .= '<option value="'.$var.'">'.$var.'</option>';

							}


							$datas = [];
							foreach ($vars as $var) {

								$datas[] = [
									"id"    => $var,
									"title" => $var
								];

							}
							$su = Elements ::Select($tip.'['.$da['fld_name'].'][]', $datas, [
								"class"        => [
									"wp95",
									$param['requered']
								],
								"nowrapper"    => true,
								"multiple"     => true,
								"multipleInit" => false
							]);

							$s = '
							<div class="flex-container mb10" data-block="'.$tip.'-'.$da['fld_name'].'" data-xtype="'.$da['fld_sub'].'">
	
								<div class="column12 grid-12">
									<div id="divider" class="red"><b>'.$da['fld_title'].'</b></div>
								</div>
	
							</div>
							<div class="flex-container mb10 '.($da['fld_required'] == 'required' ? 'multireq' : '').'" data-block="'.$tip.'-'.$da['fld_name'].'" data-xtype="'.$da['fld_sub'].'">
	
								<div class="flex-string wp100 pl10">
								
									'.$su.'
									<hr>
									
								</div>
	
							</div>';

						}
						elseif ($da['fld_temp'] == "inputlist") {

							$vars = $da['fld_var'];

							$s = '
							<div class="flex-container mb10" data-block="'.$tip.'-'.$da['fld_name'].'" data-xtype="'.$da['fld_sub'].'">
	
								<div class="flex-string wp20 gray2 fs-12 pt7 right-text">'.$da['fld_title'].':</div>
								<div class="flex-string wp80 pl10">
									<input type="text" name="'.$tip.'['.$da['fld_name'].']" id="'.$tip.'['.$da['fld_name'].']" class="wp93 '.$param['requered'].'" value="" placeholder="'.$da['fld_title'].'" data-class="'.$param['requered'].'">
									<div class="smalltxt blue"><em>Двойной клик мышкой для показа вариантов</em></div>
									<script>
									
										var str = \''.$vars.'\';
										//console.log(str);
										var data = str.split(\',\');
										$("#'.$tip.'\\\['.$da['fld_name'].'\\\]").autocomplete(data, {autoFill: true, minLength:0, minChars: 0, cacheLength: 5, max:100, selectFirst: false, multiple: false,  delay: 0, matchSubset: 2});
										
									</script>
								</div>
	
							</div>';

						}
						elseif ($da['fld_temp'] == "radio") {

							$vars = explode(",", (string)$da['fld_var']);
							$su   = '';

							foreach ($vars as $var) {

								$s1 = ($var == $input[ $i ]) ? 'checked' : '';

								$su .= '
								<div class="flex-string p10 mr5 mb5 flx-basis-20 viewdiv bgwhite inset" data-block="'.$tip.'-'.$da['fld_name'].'" data-xtype="'.$da['fld_sub'].'">
									<div class="radio">
										<label>
											<input name="'.$tip.'['.$da['fld_name'].']" type="radio" id="'.$tip.'['.$da['fld_name'].']" '.$s1.' value="'.$var.'" />
											<span class="custom-radio success1"><i class="icon-radio-check"></i></span>
											<span class="title">'.$var.'</span>
										</label>
									</div>
								</div>';

							}

							$s = '
							<div class="flex-container mb20 mt10 '.($da['fld_required'] == 'required' ? 'req' : '').'" data-block="'.$tip.'-'.$da['fld_name'].'" data-xtype="'.$da['fld_sub'].'">
	
								<div class="flex-string wp20 gray2 fs-12 pt7 right-text">'.$da['fld_title'].':</div>
								<div class="flex-string wp80 pl10">
								
									<div class="flex-container box--child wp93--5">
										'.$su.'
										'.($da['fld_required'] != 'required' ? '
										<div class="flex-string p10 mr5 mb5 flx-basis-20 viewdiv bgwhite inset">

											<div class="radio">
												<label>
													<input name="'.$da['fld_name'].'" type="radio" id="'.$da['fld_name'].'" checked value="">
													<span class="custom-radio secondary"><i class="icon-radio-check"></i></span>
													<span class="title gray">Не выбрано</span>
												</label>
											</div>
		
										</div>' : '').'
										
									</div>
									
								</div>
	
							</div>';

						}
						elseif ($da['fld_temp'] == "datum") {

							$s = '
							<div class="flex-container mb10" data-block="'.$tip.'-'.$da['fld_name'].'" data-xtype="'.$da['fld_sub'].'">
	
								<div class="flex-string wp20 gray2 fs-12 pt7 right-text">'.$da['fld_title'].':</div>
								<div class="flex-string wp80 pl10">
									<INPUT name="'.$tip.'['.$da['fld_name'].']" type="text" id="'.$tip.'['.$da['fld_name'].']" class="datum wp30 '.$param['requered'].'" autocomplete="off" data-class="'.$param['requered'].'">
								</div>
	
							</div>';

						}

					}

				}

				if ($param['more'] != 'yes') {
					$string[ $tip ] .= $s;
				}
				else {
					$stringMore[ $tip ] .= $s;
				}

				b:

			}

		}

		?>

		<div id="flyitbox"></div>
		<DIV style="overflow-y:auto !important; overflow-x:hidden" id="formtabs">

			<?php
			$hooks -> do_action("client_form_express_before", $_REQUEST);
			?>

			<div class="flex-container box--child mt10">

				<div class="flex-string wp20 gray2 fs-12 pt7 right-text"><?= $fieldsNames['client']['iduser'] ?>:</div>
				<div class="flex-string wp80 pl10">
					<?php
					$element = new Elements();
					print $element -> UsersSelect("iduser", [
						"class"  => [
							$fieldsRequire['client']['iduser'],
							'wp93'
						],
						"active" => true,
						"jsact"  => "setUser()",
						"sel"    => $iduser1
					]);
					?>
				</div>

			</div>

			<div id="clientBoxExpress" class="fxmain box--child">

				<div class="flex-container mt20 mb20">

					<div class="flex-string wp100">
						<div id="divider" class="red text-center">
							<b class="blue">Клиент</b><i class="icon-info-circled blue" title="Вы можете выбрать существующего клиента. Для этого начните набирать его название и выберите из найденных"></i>
						</div>
					</div>

				</div>
				<div class="flex-container mb10">

					<div class="flex-string wp20 gray2 fs-12 pt7 right-text">Тип:</div>
					<div class="flex-string wp80 pl10">
						<SELECT name="client[type]" id="client[type]" data-class="required" class="required wp93 typeselect" onchange="getOtrasli()">
							<OPTION value="client" <?php if (!$otherSettings['clientIsPerson']) { print "selected"; } ?>><?=$lang['agenttypes']['client']?></OPTION>
							<OPTION value="person" <?php if ($otherSettings['clientIsPerson']) { print "selected"; } ?>><?=$lang['agenttypes']['person']?></OPTION>
							<OPTION value="partner"><?=$lang['agenttypes']['partner']?></OPTION>
							<OPTION value="contractor" <?php if ($_REQUEST['tip'] == "other") { print "selected"; } ?>><?=$lang['agenttypes']['contractor']?></OPTION>
							<OPTION value="concurent"><?=$lang['agenttypes']['concurent']?></OPTION>
						</SELECT>
					</div>

				</div>

				<?= $string['client']; ?>

				<?php if ($stringMore['client'] != '') { ?>
					<div class="togglerbox smalltxt gray2 hand mb20 text-center" data-id="fullFilter" title="Показать/скрыть доп.фильтры">

						ещё поля...&nbsp;<i class="icon-angle-down" id="mapic"></i>

					</div>

					<div id="fullFilter" class="hidden box--child">

						<?= $stringMore['client'] ?>

					</div>
				<?php } ?>

			</div>

			<?php
			if (!$otherSettings['hideContactFromExpress']) {
				?>
				<div id="contactBoxExpress" class="box--child <?php echo(!empty((array)$string['person']) ? "" : "hidden"); ?>">

					<div class="flex-container mt20 mb20">

						<div class="flex-string wp100">
							<div id="divider" class="red text-center">
								<b class="blue">Контакт</b>
							</div>
						</div>

					</div>

					<?= $string['person'] ?>

					<div class="flex-container mb10 mt20">

						<div class="flex-string wp20 gray2 fs-12 pt7 right-text"></div>
						<div class="flex-string wp80 pl10">
							<div class="checkbox">
								<label>
									<input name="mperson" type="checkbox" checked="checked" value="yes"/>
									<span class="custom-checkbox"><i class="icon-ok"></i></span>
									&nbsp;Установить основным контактом
									&nbsp;<i class="icon-info-circled blue" title="Если Контакт закреплен за Клиентом, то он станет основным контактом и будет показываться в карточке Клиента."></i>
								</label>
							</div>
						</div>

					</div>

					<?php if ($stringMore['person'] != '') { ?>
						<div class="togglerbox smalltxt gray2 hand mb20 text-center" data-id="fullFilterP" title="Показать/скрыть доп.фильтры">

							ещё поля...&nbsp;<i class="icon-angle-down" id="mapic"></i>

						</div>

						<div id="fullFilterP" class="hidden box--child">

							<?= $stringMore['person'] ?>

						</div>
					<?php } ?>

				</div>
			<?php } ?>

			<?php
			if ($otherSettings['addDealForExpress']) {

				$title_dog = str_replace("{ClientName}", $client['title'], generate_num('namedogovor'));
				$dNum      = generate_num('dogovor');

				if ($dNum) {
					$dnum = '<span class="smalltxt green">Номер '.$lang['face']['DealName'][1].': <b>'.$dNum.'</b> (предварительно)</span>';
				}

				$datum_plan = current_datum(-$perDay);

				$fieldDeal = $fieldsNames['dogovor'];
				?>
				<div id="dogblock">

					<div class="flex-container mt20 mb20">

						<div class="flex-string wp100">
							<div id="divider" class="red text-center">
								<b class="blue">Сделка</b>
							</div>
						</div>

					</div>

					<div class="flex-container mb10 mt20 box--child">

						<div class="flex-string wp20 gray2 fs-12 pt7 right-text">Название сделки:</div>
						<div class="flex-string wp80 pl10 relativ">
							<input type="text" name="dogovor[title]" id="dogovor[title]" value="<?= $title_dog ?>" placeholder="Название" class="wp93" data-req="required">
							<div class="idel paddright15">
								<i title="Очистить" onclick="$('input#dogovor\\[title\\]').val('');" class="icon-block red hand"></i>
							</div>
							<div class="smalltxt gray2"><?= $lang['face']['DealName'][3] ?>. <?= $dnum ?></div>
						</div>

					</div>

					<div class="flex-container mb10 mt20 box--child">

						<div class="flex-string wp20 gray2 fs-12 pt7 right-text">Плановая дата:</div>
						<div class="flex-string wp80 pl10 relativ">
							<input name="dogovor[datum_plan]" type="date" id="dogovor[datum_plan]" class="wp30 datum" value="<?= $datum_plan ?>" maxlength="10" placeholder="Дата реализации" autocomplete="off" data-req="required">
						</div>

					</div>

					<div class="flex-container mb10 mt20 box--child">

						<div class="flex-string wp20 gray2 fs-12 pt7 right-text"><?= $fieldDeal['direction'] ?>:</div>
						<div class="flex-string wp80 pl10 relativ">
							<select name="dogovor[direction]" id="dogovor[direction]" class="wp93" data-req="<?= $fieldsRequire['dogovor']['direction'] ?>">
								<?php
								$resulttip = $db -> getAll("SELECT * FROM {$sqlname}direction WHERE identity = '$identity' ORDER BY title");
								foreach ($resulttip as $data) {

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
								$resulttip = $db -> getAll("SELECT * FROM {$sqlname}dogtips WHERE identity = '$identity' ORDER BY title");
								foreach ($resulttip as $data) {

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
							$dfs     = $db -> getOne("SELECT idcategory FROM {$sqlname}dogcategory WHERE title = '".$otherSettings['dealStepDefault']."' and identity = '$identity' ORDER BY title");
							$resultt = $db -> getAll("SELECT * FROM {$sqlname}dogcategory WHERE identity = '$identity' ORDER BY title");
							?>
							<select name="dogovor[idcategory]" id="dogovor[idcategory]" class="wp93" data-req="<?= $fieldsRequire['dogovor']['idcategory'] ?>">
								<?php
								foreach ($resultt as $data) {
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
								$result = $db -> query("SELECT * FROM {$sqlname}mycomps WHERE identity = '$identity' ORDER BY name_shot");
								while ($data = $db -> fetch($result)) {

									$s = ($data['id'] == $mcDefault) ? "selected" : "";
									print '<option value="'.$data['id'].'" '.$s.'>'.$data['name_shot'].'</option>';

								}
								?>
							</select>
						</div>

					</div>

					<?php
					if ($fieldDeal['adres']) {
						?>
						<div class="flex-container mb10 mt20 box--child">

							<div class="flex-string wp20 gray2 fs-12 pt7 right-text"><?= $fieldDeal['adres'] ?>:</div>
							<div class="flex-string wp80 pl10 relativ">
								<input name="dogovor[adres]" type="text" id="dogovor[adres]" class="wp93" value="" placeholder="<?= $fieldDeal['adres'] ?>" autocomplete="on" data-req="<?= $fieldsRequire['dogovor']['adres'] ?>" data-type="address">
							</div>

						</div>

					<?php } ?>

					<div class="flex-container mb10 mt20 box--child">

						<div class="flex-string wp20 gray2 fs-12 pt7 right-text"><?= $fieldsNames['dogovor']['kol'] ?>:</div>
						<div class="flex-string wp80 pl10 relativ">
							<input name="dogovor[kol]" type="text" class="required yw140" id="dogovor[kol]" onkeyup="CheckMarg();" value="0"/>&nbsp;<?= $valuta ?>
						</div>

					</div>

					<?php
					$hidd = ($show_marga == 'yes' && $otherSettings['marga']) ? "" : "hidden";
					?>
					<div class="flex-container mb10 mt20 box--child <?= $hidd ?>">

						<div class="flex-string wp20 gray2 fs-12 pt7 right-text"><?= $fieldsNames['dogovor']['marg'] ?>:</div>
						<div class="flex-string wp80 pl10 relativ">
							<input name="dogovor[marg]" type="text" class="required yw140" id="dogovor[marg]" value="0"/>&nbsp;<?= $valuta ?>
						</div>

					</div>

					<div class="flex-container mb10 mt20 box--child">

						<div class="flex-string wp20 gray2 fs-12 pt7 right-text"><?= $fieldDeal['content'] ?>:</div>
						<div class="flex-string wp80 pl10 relativ">
							<textarea name="dogovor[content]" id="dogovor[content]" style="height: 100px;" class="wp93" data-req="<?= $fieldsRequire['dogovor']['content'] ?>"><?= trim($content) ?></textarea>
						</div>

					</div>

					<?php
					$res = $db -> getAll("select * from {$sqlname}field where fld_tip='dogovor' and fld_name LIKE '%input%' and fld_on='yes' and identity = '$identity' order by fld_order");

					if (!empty($res)) {
						?>

						<div class="togglerbox smalltxt gray2 hand mb20 wp100 text-center" data-id="dfullFilter" title="Показать/скрыть доп.фильтры">

							ещё поля...&nbsp;<i class="icon-angle-down" id="mapic"></i>

						</div>

						<div id="dfullFilter" class="hidden box--child">

							<?php
							foreach ($res as $da) {

								if ($da['fld_temp'] == "--Обычное--") {
									?>

									<div class="flex-container mb10 mt20 box--child">

										<div class="flex-string wp20 gray2 fs-12 pt7 right-text"><?= $da['fld_title'] ?>:</div>
										<div class="flex-string wp80 pl10 relativ">
											<input type="text" name="dogovor[<?= $da['fld_name'] ?>]" id="dogovor[<?= $da['fld_name'] ?>]" class="wp93" value="" placeholder="<?= $da['fld_title'] ?>" data-req="<?= $da['fld_required'] ?>">
										</div>

									</div>

									<?php
								}
								elseif ($da['fld_temp'] == "adres") {
									?>

									<div class="flex-container mb10 mt20 box--child">

										<div class="flex-string wp20 gray2 fs-12 pt7 right-text"><?= $da['fld_title'] ?>:</div>
										<div class="flex-string wp80 pl10 relativ">
											<input type="text" name="dogovor[<?= $da['fld_name'] ?>]" id="dogovor[<?= $da['fld_name'] ?>]" class="wp93 yaddress" value="" placeholder="<?= $da['fld_title'] ?>" data-req="<?= $da['fld_required'] ?>" data-type="address">
										</div>

									</div>

									<?php
								}
								elseif ($da['fld_temp'] == "textarea") {

									$fieldData = $da['fld_var'];
									?>

									<div class="flex-container mb10 mt20 box--child">

										<div class="flex-string wp20 gray2 fs-12 pt7 right-text"><?= $da['fld_title'] ?>:</div>
										<div class="flex-string wp80 pl10 relativ">
											<textarea name="dogovor[<?= $da['fld_name'] ?>]" id="dogovor[<?= $da['fld_name'] ?>]" class="wp93" style="height: 150px;" placeholder="<?= $da['fld_title'] ?>" data-req="<?= $da['fld_required'] ?>"><?= str_replace("<br>", "\n", $fieldData) ?></textarea>
										</div>

									</div>

									<?php
								}
								elseif ($da['fld_temp'] == "select") {

									$vars = explode(",", $da['fld_var']);
									?>

									<div class="flex-container mb10 mt20 box--child">

										<div class="flex-string wp20 gray2 fs-12 pt7 right-text"><?= $da['fld_title'] ?>:</div>
										<div class="flex-string wp80 pl10 relativ">
											<select name="dogovor[<?= $da['fld_name'] ?>]" id="dogovor[<?= $da['fld_name'] ?>]" class="wp93" data-req="<?= $da['fld_required'] ?>">
												<option value="">--Выбор--</option>
												<?php
												foreach ($vars as $var) {
													?>
													<option value="<?= $var ?>"><?= $var ?></option>
												<?php } ?>
											</select>
										</div>

									</div>

									<?php
								}
								elseif ($da['fld_temp'] == "multiselect") {

									$vars = explode(",", $da['fld_var']);
									?>

									<div id="divider" class="text-center"><b><?= $da['fld_title'] ?></b></div>

									<div class="flex-container mb10 mt20 box--child" data-req="<?= ($da['fld_required'] == 'required' ? 'multireq' : '') ?>">

										<div class="flex-string wp100 pl10">
											<select name="dogovor[<?= $da['fld_name'] ?>][]" id="dogovor[<?= $da['fld_name'] ?>][]" multiple="multiple" class="multiselect" style="width: 98.5%;">
												<?php
												foreach ($vars as $var) {
													?>
													<option value="<?= $var ?>"><?= $var ?></option>
												<?php } ?>
											</select>
										</div>

									</div>

									<hr>
									<?php
								}
								elseif ($da['fld_temp'] == "inputlist") {

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
								elseif ($da['fld_temp'] == "radio") {

									$vars = explode(",", $da['fld_var']);
									?>

									<div class="flex-container mb10 mt20 box--child" data-req="<?= ($da['fld_required'] == 'required' ? 'req' : '') ?>">

										<div class="flex-string wp20 gray2 fs-12 pt7 right-text"><?= $da['fld_title'] ?>:</div>
										<div class="flex-string wp80 pl10 relativ">

											<div class="flex-container box--child wp93--5">

												<?php
												foreach ($vars as $var) {
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
												<?php if ($da['fld_required'] != 'required') { ?>
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
								elseif ($da['fld_temp'] == "datum") {
									?>

									<div class="flex-container mb10 mt20 box--child">

										<div class="flex-string wp20 gray2 fs-12 pt7 right-text"><?= $da['fld_title'] ?>:</div>
										<div class="flex-string wp80 pl10 relativ">
											<INPUT name="dogovor[<?= $da['fld_name'] ?>]" type="text" id="dogovor[<?= $da['fld_name'] ?>]" class="datum wp30" data-req="<?= $da['fld_required'] ?>" value="<?= $dogovor[ $da['fld_name'] ] ?>" autocomplete="off">
										</div>

									</div>
									<?php
								}
								elseif ($da['fld_temp'] == "datetime") {
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

					<?php } ?>

				</div>
			<?php } ?>

			<div id="historyBoxExpress">

				<div class="flex-container mt20 mb20">

					<div class="flex-string wp100">
						<div id="divider" class="red text-center">
							<b class="blue">Добавить активность</b>
						</div>
					</div>

				</div>

				<div class="flex-container box--child mt10">

					<div class="flex-string wp20 gray2 fs-12 pt7 right-text">Тип активности:</div>
					<div class="flex-string wp80 pl10">

						<select name="tiphist" id="tiphist" class="required wp93" data-change="activities" data-id="deshist">
							<?php
							$actDefaulte = ($messageid > 0) ? getTipHistory('Вх.почта') : $actDefault;

							$result = $db -> query("SELECT * FROM {$sqlname}activities WHERE filter IN ('all','activ') and identity = '$identity' ORDER by aorder");
							while ($data = $db -> fetch($result)) {

								//$s = ( $data[ 'id' ] == $actDefaulte ) ? "selected" : "";
								//print '<OPTION '.$s.' value="'.$data[ 'title' ].'" style="color:'.$data[ 'color' ].'">'.$data[ 'title' ].'</OPTION>';

								print '<option value="'.$data['title'].'" '.($data['id'] == $actDefault ? "selected" : "").' style="color:'.$data['color'].'" data-color="'.$data['color'].'" data-icon="'.get_ticon($data['title'], '', true).'">'.$data['title'].'</option>';

							}
							?>
						</select>

					</div>

				</div>

				<div class="flex-container box--child mt10">

					<div class="flex-string wp20 gray2 fs-12 pt7 right-text">Описание активности:</div>
					<div class="flex-string wp80 pl10">

						<textarea name="deshist" rows="5" class="wp93" id="deshist"><?= $content ?></textarea>
						<div id="tagbox" class="gray1 fs-09 mt5" data-id="deshist" data-tip="tiphist">
							<br/>Начните с выбора
							<strong class="errorfont">типа активности</strong></div>

					</div>

				</div>

			</div>

			<?php
			$tcount = getOldTaskCount((int)$iduser1);
			if ((int)$otherSettings['taskControl'] > 0 && (int)$tcount >= (int)$otherSettings['taskControl']) {

				print '<div class="warning"><b class="red">Включен режим контроля выполненения дел.</b><br>У вас '.$tcount.' не выполненных дел - вы не можете создавать новые напоминания, пока не закроете старые.</div>';

			}
			else {
				?>
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

							<select name="todo[tip]" id="todo[tip]" class="wp95 required" data-change="activities" data-id="todo[des]">
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

							<a href="javascript:void(0)" onclick="copydes();" title="скопировать из активности" class="blue pull-right mr30 mt5"><i class="icon-docs"></i></a>

							<textarea name="todo[des]" id="todo[des]" rows="4" class="required1 wp95 pr20" style="height:120px;" placeholder="Здесь можно указать детали напоминания - что именно надо сделать?"><?= $des ?></textarea>

							<div id="tagbox" class="gray1 fs-09 mt5" data-id="todo[des]" data-tip="todotip"></div>

						</div>

					</div>

				</div>
			<?php }
			?>

		</DIV>

		<hr>

		<div class="text-right button--pane">

			<A href="javascript:void(0)" onclick="checkTask()" class="button">Добавить</A>&nbsp;
			<A href="javascript:void(0)" onclick="DClose()" class="button">Отмена</A>

		</div>

	</FORM>

	<script type="text/javascript" src="/assets/js/smSelect.js"></script>
	<script type="text/javascript" src="/assets/js/app.form.js"></script>
	<script>

		//тип клиента по умолчанию
		var isClientCompany = '<?=(!$otherSettings['clientIsPerson'] ? "yes" : "no")?>';
		var formatPhone = '<?=$format_phone?>';
		var $timecheck = <?=$timecheck?>;

		/*формируем окно. старт*/
		var dwidth = $(document).width();
		var dialogWidth;
		var dialogHeight;

		var origDateTime = $('#todo\\[datumtime\\]').val();
		var origTip = $('#todo\\[tip\\] option:selected').text();

		if (!isMobile) {

			if (dwidth < 945) {
				dialogWidth = '90%';
				dialogHeight = '95vh';
			} else {
				dialogWidth = '80%';
			}

			var defActivitie = $('#todo\\[tip\\] option:selected').html();
			var hh = $('#dialog_container').actual('height') * 0.9;
			var hh2 = hh - $('.zagolovok').actual('outerHeight') - $('.button--pane').actual('outerHeight') - 20;

			if ($(window).width() > 990) {

				$('#dialog').css({'width': '800px'});
				$('#formtabs').css({'max-height': hh2});

			} else {

				$('#dialog').css('width', '80%');
				$('#formtabs').css('max-height', hh2);

			}

			$(".multiselect").multiselect({sortable: true, searchable: true});

			$('#tiphist').smSelect({
				text: "",
				width: "p93",
				height: "300px",
				icon: "",
				class: "p51 like-input",
				fly: true,
				id: "tiphist"
			});

			$('#todo\\[tip\\]').smSelect({
				text: "",
				width: "p95",
				height: "250px",
				icon: "",
				class: "p51 like-input",
				fly: true,
				id: "todotip"
			});

		}
		else {

			var h2 = $(window).height() - $('.zagolovok').actual('outerHeight') - $('.button--pane').actual('outerHeight') - 30;
			$('#formtabs').css({'max-height': h2 + 'px', 'height': h2 + 'px'});

			$(".multiselect").addClass('wp97 h0');

		}

		/*формируем окно. финиш*/

		$(function () {

			$('.typeselect').trigger('change')

			//меняем блоки местами
			//if(isClientCompany != 'yes') $('#contactBoxExpress').insertAfter('#clientBoxExpress');
			//else $('#clientBoxExpress').insertAfter('#contactBoxExpress');

			getDateTasksNew('todo\\[datumtime\\]');

			$('#todo\\[des\\]').autoHeight(120);
			$('#deshist').autoHeight(120);

			$('.datum').each(function () {

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
			if (!isMobile) $('.inputdatetime').each(function () {

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

			$("#person\\[rol\\]").autocomplete("/content/helpers/person.helpers.php?action=get.role", {
				autoFill: false,
				minChars: 3,
				cacheLength: 30,
				max: 20,
				//selectFirst: true,
				multiple: true,
				multipleSeparator: "; ",
				delay: 500
			});

			if( $("#person\\[ptitle\\]").hasClass('suggestion') ) {
				$("#person\\[ptitle\\]").autocomplete("/content/helpers/person.helpers.php?action=get.status", {
					autofill: true,
					minChars: 3,
					cacheLength: 1,
					maxItemsToShow: 20,
					selectFirst: false,
					multiple: false,
					delay: 500,
					matchSubset: 1
				});
			}

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

			if (!isMobile) $("#datum_task").datepicker({
				dateFormat: 'yy-mm-dd',
				firstDay: 1,
				dayNamesMin: ['Вс', 'Пн', 'Вт', 'Ср', 'Чт', 'Пт', 'Сб'],
				monthNamesShort: ['Январь', 'Февраль', 'Март', 'Апрель', 'Май', 'Июнь', 'Июль', 'Август', 'Сентябрь', 'Октябрь', 'Ноябрь', 'Декабрь'],
				changeMonth: true,
				changeYear: true
			});
			if (!isMobile) $('#totime_task').ptTimeSelect();

			$("#person\\[person\\]").trigger('focus');

			$("#client\\[title\\]")
				.autocomplete('/content/helpers/client.helpers.php?action=clientlist', {
					autofill: false,
					minChars: 2,
					cacheLength: 2,
					max: 20,
					selectFirst: false,
					multiple: false,
					delay: 500,
					matchSubset: 1,
					formatItem: function (data) {
						return '<div onclick="selItem(\'client\',\'' + data[1] + '\')">' + data[0] + '&nbsp;[<span class="red">' + data[2] + '</span>]</div>';
					},
					formatResult: function (data) {
						return data[0];
					}
				})
				.result(function (value, data) {
					selItem('client', data[1])
				});


			//Формат номеров телефонов
			if (formatPhone !== '')
				reloadMasks();

			$('#dialog').center();

			doLoadCallback('expressClient');

			ShowModal.fire({
				etype: 'expressClient'
			});

		});

		$('.typeselect')
			.off('change')
			.on('change', function(){

				let tip = $(this).val();

				if( tip === 'person' ){
					tip = 'client';
				}

				$('.fxmain').find('.flex-container').each(function(){

					var xtype = $(this).data('xtype');

					$(this).removeClass('hidden');

					if(xtype !== '' && xtype !== undefined) {

						if (xtype !== tip) {
							$(this).addClass('hidden');
						}
						else if (xtype === tip) {
							$(this).removeClass('hidden');
						}

					}

				});

			})

		/**
		 * Управление тэгами
		 */
		$('select[data-change="activities"]').each(function () {

			var $el = $(this).data('id');
			$('#tagbox[data-id="' + $el + '"]').empty().load('/content/core/core.tasks.php?action=itags&tip=' + urlEncodeData($('option:selected', this).val()));

		});

		$(document).on('change', 'select[data-change="activities"]', function () {
			var $el = $(this).data('id');
			$('#tagbox[data-id="' + $el + '"]').empty().load('/content/core/core.tasks.php?action=itags&tip=' + urlEncodeData($('option:selected', this).val()));
		});

		$('.ydropDown[data-change="activities"]').each(function () {

			var $el = $(this).data('selected');
			var $tip = $(this).data('id');
			$('#tagbox[data-tip="' + $tip + '"]').empty().load('/content/core/core.tasks.php?action=itags&tip=' + urlEncodeData($el));

		});

		$(document).on('change', '#client\\[type\\]', function () {

			var type = $(this).val();

			if (type === 'person') {
				$('#client\\[title\\]').attr('placeholder', 'Например: Иванов Иван');
			} else {
				$('#client\\[title\\]').attr('placeholder', 'Например: Сейлзмен, ООО');
			}

		})

		$(document)
			.off('change', 'input[data-change="activities"]')
			.on('change', 'input[data-change="activities"]', function () {

				var $el = $(this).data('id');
				var $tip = $(this).val();

				//console.log($el);

				$('#tagbox[data-tip="' + $el + '"]').empty().load('/content/core/core.tasks.php?action=itags&tip=' + urlEncodeData($tip));

			});

		$(document).off('click', '.tags');
		$(document).on('click', '.tags', function () {
			var $tag = $(this).text();
			var $el = $(this).closest('#tagbox').data('id');
			insTextAtCursor($el, $tag + '; ');
		});

		$('.togglerbox').on('click', function () {

			var id = $(this).data('id');

			$('#' + id).find('.datum').each(function () {

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

		});

		$('#form').ajaxForm({
			dataType: 'json',
			beforeSubmit: function () {

				var $out = $('#message');
				var em = checkRequired();

				if (em === false) return false;

				$out.empty().fadeTo(1, 1).css('display', 'block').append('<div id="loader"><img src="/assets/images/loader.gif"> Выполняю...</div>');

				$('#dialog').css('display', 'none');
				$('#dialog_container').css('display', 'none');

				return true;

			},
			success: function (data) {

				//var isExpress = $('.expressbuttons').is('div')

				$('#dialog_container').css('display', 'none');
				$('#dialog').css('display', 'none');

				/*if ($('#dogblock').is('div') && $('#dogovor\\[title\\]').val() !== '') {

					window.open('card.deal?did=' + data.did);

				}*/

				if (data.clid > 0 && data.did === 0) {
					//window.open('card.client?clid=' + data.clid);
					openClient(data.clid)
				}
				else if (data.did > 0) {
					//window.location = 'card.client?clid=' + data.clid;
					openDogovor(data.did)
				}

				$('#message').fadeTo(1, 1).css('display', 'block').html('Результат: ' + data.message + '<br>Ошибки: ' + data.error);
				setTimeout(function () {
					$('#message').fadeTo(1000, 0);
				}, 20000);

				if (typeof configpage == 'function')
					configpage();

				<?php if($messageid > 0){?>
				if (typeof loadMes == 'function')
					loadMes('<?=$messageid?>');
				<?php } ?>

				if ($('#todo\\[theme\\]').val() !== '') {

					if (isCard)
						cardload();
					else
						changeMounth();

				}

				if ($display === 'desktop' && $space === 'clients') {

					$desktop.clients();

				}

			}

		});

		function checkTask() {

			if ($('#todo\\[theme\\]').val() !== '' && $timecheck) {

				if (origDateTime === $('#todo\\[datumtime\\]').val() || origTip === $('#todo\\[tip\\] option:selected').text()) {

					Swal.fire({
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
					}).then((result) => {

						if (result.value) {

							$('#form').trigger('submit');

						}

					});

				} else $('#form').trigger('submit');

			} else $('#form').trigger('submit');

		}

		function gettags(selector) {

			if (!selector) selector = 'tiphist';
			var tip = urlEncodeData($('#' + selector + ' option:selected').val());
			$('#tagbox').load('/content/core/core.tasks.php?action=tags&tip=' + tip);

		}

		function tagit(id) {

			var html = $('#tag_' + id).html();
			insTextAtCursor('deshist', html + '; ');

		}

		function copydes() {

			var tt = $('#deshist').val();

			$('#todo\\[des\\]').val(tt);

		}

		function selItem(tip, id) {

			var url = '';
			var reqphone = '';
			var reqfax = '';

			if (tip === 'client') {

				url = '/content/helpers/client.helpers.php?action=clientinfo&clid=' + id;

				if ($('#vphone').find('.phone:first-child').hasClass('required')) reqphone = 'required';
				if ($('#vfax').find('.phone:first-child').hasClass('required')) reqfax = 'required';

				$("#client\\[clid\\]").val(id);

				$.getJSON(url, function (data) {

					$("#client\\[mail_url\\]").val(data.mail_url);
					$("#client\\[site_url\\]").val(data.site_url);
					$("#client\\[head_clid\\]").val(data.head_clid);
					$("#lst_spisok").val(data.head);
					$("#client\\[address\\]").val(data.address);
					$("#client\\[territory\\]").find('[value="' + data.territory + '"]').prop("selected", true);
					$("#client\\[idcategory\\]").find('[value="' + data.idcategory + '"]').prop("selected", true);
					$("#client\\[clientpath\\]").find('[value="' + data.clientpath2 + '"]').prop("selected", true);
					$("#client\\[tip_cmr\\]").find('[value="' + data.tip_cmr + '"]').prop("selected", true);

					var phone = (data.phone) ? data.phone.replace('+', '').split(',') : [];
					var fax = (data.fax) ? data.fax.replace('+', '').split(',') : [];
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

					//пройдем доп.поля
					for (var key in data) {

						if (key.indexOf("input") >= 0) {

							var element = $('#client\\[' + key + '\\]');

							if (element.is('input[type="text"]')) {

								element.val(data[key]);

							} else if (element.is('select') && !element.hasClass('multiselect')) {

								element.find('[value="' + data[key] + '"]').prop("selected", true);

							} else if ($('#client\\[' + key + '\\]\\[\\]').hasClass('multiselect')) {

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

							} else if (element.is('input[type="radio"]')) {

								$('#client\\[' + key + '\\][value="' + data[key] + '"]').prop("checked", true);

							}

						}

					}

					<?php
					//Формат номеров телефонов
					if($format_phone){
					?>
					reloadMasks();
					<?php } ?>

				});

			}
			if (tip === 'person') {

				url = '/content/helpers/client.helpers.php?action=clientinfo&pid=' + id;

				$("#person\\[pid\\]").val(id);

				$.getJSON(url, function (data) {

					if ($("#person\\[ptitle\\]").val() === '') $("#person\\[ptitle\\]").val(data.ptitle);
					if ($("#person\\[tel\\]").val() === '') $("#person\\[tel\\]").val(data.tel);
					if ($("#person\\[mob\\]").val() === '') $("#person\\[mob\\]").val(data.mob);
					if ($("#person\\[mail\\]").val() === '') $("#person\\[mail\\]").val(data.mail);

					var url2 = '/content/helpers/client.helpers.php?action=clientinfo&clid=' + data.clid;

					$.getJSON(url2, function (data2) {

						$("#client\\[title\\]").val(data2.title);
						$("#client\\[mail_url\\]").val(data2.mail_url);
						$("#client\\[address\\]").val(data2.address);
						$("#client\\[territory\\] [value='" + data2.territory + "']").attr("selected", "selected");
						$("#client\\[idcategory\\] [value='" + data2.idcategory + "']").attr("selected", "selected");
						$("#client\\[clientpath\\] :contains('" + data2.clientpath + "')").attr("selected", "selected");

						var tel = (data2.phone) ? data2.phone.replace('+', '').split(',') : [];
						var mob = (data2.mob) ? data2.mob.replace('+', '').split(',') : [];
						var string = '';

						$('#vphone').find('.phoneBlock').not(':last').remove();
						$('#vtel').find('.phoneBlock').not(':last').remove();
						$('#vmob').find('.phoneBlock').not(':last').remove();

						for (var i in tel) {

							if (tel[i] != '') string += '' +
								'<div class="phoneBlock paddbott5 relativ">' +
								'   <INPUT name="person[tel][]" type="text" class="phone w250" id="person[tel][]" value="' + tel[i] + '" placeholder="Формат: <?=$format_tel?>" data-id="vtel" data-action="valphone" data-type="person.helpers" autocomplete="off"> <span class="remover hand" data-parent="vtel"><i class="icon-minus-circled red"></i></span>' +
								'</div>';

						}

						$('#vtel').prepend(string);

						for (var i in mob) {

							if (mob[i] != '') string += '' +
								'<div class="phoneBlock paddbott5 relativ">' +
								'   <INPUT name="person[mob][]" type="text" class="phone w250" id="person[mob][]" value="' + mob[i] + '" placeholder="Формат: <?=$format_tel?>" data-id="vmob" data-action="valphone" data-type="person.helpers" autocomplete="off"> <span class="remover hand" data-parent="vmob"><i class="icon-minus-circled red"></i></span>' +
								'</div>';

						}

						$('#vmob').prepend(string);

						//пройдем доп.поля
						for (var key in data) {

							if (key.indexOf("input") >= 0) {

								var element = $('#client\\[' + key + '\\]');

								if (element.is('input[type="text"]')) {

									element.val(data[key]);

								} else if (element.is('select') && !element.hasClass('multiselect')) {

									element.find('[value="' + data[key] + '"]').prop("selected", true);

								} else if ($('#client\\[' + key + '\\]\\[\\]').hasClass('multiselect')) {

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

										element.find('[value="' + ch.trim() + '"]').prop("checked", true);

									}

								} else if (element.is('input[type="radio"]')) {

									$('#client\\[' + key + '\\][value="' + data[key] + '"]').prop("checked", true);

								}

							}

						}

						<?php
						//Формат номеров телефонов
						if($format_phone){
						?>
						reloadMasks();
						<?php } ?>

					});

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

			var $eel = $('#' + formelement);
			var $espisok = $('#ospisok');

			if ($eel.val().length >= 3) {

				atop = $eel.position().top + 30;
				aleft = $eel.position().left - 5;
				awidth = $eel.width();
				title = urlEncodeData($eel.val());

				if ($espisok.is('div') === false) {

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


					$espisok.empty().append('<div class="header fs-12"><b>Похожие записи (возможные дубли):</b></div><div class="zbody">' + string + '</div>').css('display', 'block');

				}, "json");


				return false;
			}

		}

		function reloadMasks() {

			//Формат номеров телефонов
			if (formatPhone !== '') {

				$('.phone').each(function () {

					$(this).phoneFormater(formatPhone);

				});

			}

		}

	</script>
	<?php
	$hooks -> do_action("client_form_express_after", $_REQUEST);

	exit();

}

// объединенная форма
if ($action == "edit") {

	if ($clid > 0) {

		$client = $db -> getRow("select * from {$sqlname}clientcat where clid = '$clid' and identity = '$identity'");

		//print_r($client);

		$recv = explode(";", $client["recv"]);

		$recv_on = $db -> getOne("select fld_on from {$sqlname}field where fld_name='recv' and identity = '$identity'");

		//переберем все поля и найдем с именем ИНН и КПП
		$kol  = 0;
		$resk = $db -> query("select * from {$sqlname}field where fld_tip='client' and fld_on='yes' and identity = '$identity' order by fld_order");
		while ($dak = $db -> fetch($resk)) {

			if ($dak['fld_title'] == 'ИНН') {
				$kol++;
				print '<INPUT type="hidden" name="innp" id="innp" value="'.$client[ $dak['fld_name'] ].'">';
			}
			if ($dak['fld_title'] == 'КПП') {
				$kol++;
				print '<INPUT type="hidden" name="kppp" id="kppp" value="'.$client[ $dak['fld_name'] ].'">';
			}

		}

	}
	else {

		$clid = 0;

		$tip       = $_REQUEST['tip'];
		$rid       = $_REQUEST['rid'];
		$messageid = $_REQUEST['messageid'];

		$client = [];

		//подгрузим данные из письма
		if ($messageid > 0) {

			$result             = $db -> getRow("SELECT * FROM {$sqlname}ymail_messages WHERE id = '$messageid' and identity = '$identity'");
			$datum              = $result['datum'];
			$theme              = $result['theme'];
			$client['mail_url'] = $result['fromm'];
			$client['title']    = $result['fromname'];
			$content            = $result['content'];

			$content       = html2text($content);
			$client['des'] = $theme."\n\r".$content;

			$data = html2data($content);

			//print_r($data);

			if ($data['phone'] != '') {
				$client['phone'] = $data['phone'];
			}
			if ($data['email'] != '') {
				$email2 = $data['email'];
			}
			if ($data['site'] != '') {
				$client['site_url'] = $data['site'];
			}

			if ($email2 != '') {

				$e = yexplode(",", (string)$email2);

				if (!in_array($client['mail_url'], (array)$e)) {
					$e[] = $client['mail_url'];
				}

				$e = array_unique($e);

				$client['mail_url'] = implode(", ", $e);

			}
			//else $client['mail_url'] = $email2;

		}

		//подгрузим данные из письма
		elseif ($rid > 0) {

			$result             = $db -> getRow("SELECT * FROM {$sqlname}ymail_messagesrec WHERE id = '$rid' and identity = '$identity'");
			$messageid          = $result['mid'];
			$tip                = $result['tip'];
			$client['mail_url'] = $result['email'];
			$client['title']    = $result['name'];

			if ($client['title'] == '0') {
				$client['title'] = '';
			}

			$result  = $db -> getRow("SELECT * FROM {$sqlname}ymail_messages WHERE id = '$mid' and identity = '$identity'");
			$datum   = $result['datum'];
			$theme   = $result['theme'];
			$content = $result['content'];

			$content       = html2text($content);
			$client['des'] = $theme."\n\r".$content;

		}

		$client['type'] = $otherSettings['clientIsPerson'] ? "person" : "client";

	}

	$xf = $db -> getIndCol( "fld_name", "SELECT fld_name, fld_sub FROM {$sqlname}field WHERE fld_tip = 'client' and identity = '$identity'" );

	?>
	<DIV class="zagolovok">Информации о Контрагенте</DIV>
	<?php
	if ( $clid == 0) {

		$tcount = getOldTaskCount((int)$iduser1);
		if ((int)$otherSettings['taskControl'] > 0 && (int)$otherSettings['taskControlClientAdd'] && (int)$tcount >= (int)$otherSettings['taskControl']) {

			print '<div class="warning"><b class="red">Включен режим контроля выполненения дел.</b><br>У вас '.$tcount.' не выполненных дел - вы не можете создавать новые напоминания и добавлять Клиентов и Контакты, пока не закроете старые напоминания.</div>';
			exit();

		}

	}
	?>
	<FORM action="/content/core/core.client.php" method="post" enctype="multipart/form-data" name="clientForm" id="clientForm">
		<INPUT type="hidden" name="action" id="action" value="client.edit">
		<INPUT type="hidden" name="clid" id="clid" value="<?= $clid ?>">

		<DIV id="formtabs" class="fxmain box--child pt20" style="overflow-x: hidden; overflow-y: auto !important;">

			<?php
			$hooks -> do_action("client_form_before", $_REQUEST);

			$i    = 0;
			$resk = $db -> query("select * from {$sqlname}field where fld_tip = 'client' and fld_on='yes' and identity = '$identity' order by fld_order");
			while ($dak = $db -> fetch($resk)) {

				if ($dak['fld_name'] == 'title') {
					?>
					<div class="flex-container mt10" data-block="type">

						<div class="flex-string wp20 gray2 fs-12 pt7 right-text">Тип:</div>
						<div class="flex-string wp80 pl10">
							<SELECT name="type" id="type" class="required typeselect" onchange="getOtrasli('<?=$client['idcategory']?>')">
								<OPTION value="client" <?php if ($client['type'] == 'client') { print "selected"; } ?>><?=$lang['agenttypes']['client']?></OPTION>
								<OPTION value="person" <?php if ($client['type'] == 'person') { print "selected"; } ?>><?=$lang['agenttypes']['person']?></OPTION>
								<OPTION value="concurent" <?php if ($client['type'] == 'concurent') { print "selected"; } ?>><?=$lang['agenttypes']['concurent']?></OPTION>
								<OPTION value="contractor" <?php if ($client['type'] == 'contractor') { print "selected"; } ?>><?=$lang['agenttypes']['contractor']?></OPTION>
								<OPTION value="partner" <?php if ($client['type'] == 'partner') { print "selected"; } ?>><?=$lang['agenttypes']['partner']?></OPTION>
							</SELECT>
						</div>

					</div>
					<div class="flex-container mt10" id="org" data-block="<?php echo $dak['fld_name']; ?>">
						<div class="flex-string wp20 gray2 fs-12 pt7 right-text"><?= $fieldsNames['client']['title'] ?>:</div>
						<div class="flex-string wp80 pl10 norelativ">
							<INPUT name="<?= $dak['fld_name'] ?>" type="text" class="<?= $dak['fld_required'] ?> wp95 validate" id="<?= $dak['fld_name'] ?>" value="<?= untag($client['title']) ?>" autocomplete="off" onMouseOut="$('#ospisok').remove();" onblur="$('#ospisok').remove();" placeholder="Название, Орг.форма" data-url="/content/helpers/client.helpers.php" data-action="validate">
							<div class="fs-09 gray em placeholder"><b>Например:</b> Сэйлзмэн, ООО</div>
						</div>
					</div>
					<?php
				}
				elseif ($dak['fld_name'] == 'iduser') {

					if ($clid == 0) {
						?>
						<div class="flex-container box--child mt10" data-block="<?php echo $dak['fld_name']; ?>">

							<div class="flex-string wp20 gray2 fs-12 pt7 right-text"><?= $dak['fld_title'] ?>:</div>
							<div class="flex-string wp80 pl10">
								<?php
								$element = new Elements();
								print $element -> UsersSelect($dak['fld_name'], [
									"class"  => [
										"wp95",
										$dak['fld_required']
									],
									"active" => true,
									"jsact"  => "setUser()",
									"sel"    => $iduser1
								]);
								?>
							</div>

						</div>
						<?php
					}
					else {

						print '<INPUT type="hidden" name="iduser" id="iduser" value="'.$client['iduser'].'">';

					}

				}
				elseif ($dak['fld_name'] == 'idcategory') {

					switch ($client['type']) {

						case "client":
						case "person":
							$tt = "client";
						break;
						case "contractor":
							$tt = "contractor";
						break;
						case "partner":
							$tt = "partner";
						break;
						case "concurent":
							$tt = "concurent";
						break;

					}

					//$tt = ($client['type'] != 'other') ? "and tip = 'client'" : "and tip != 'client'";

					?>
					<div class="flex-container box--child mt10" data-block="<?php echo $dak['fld_name']; ?>">
						<div class="flex-string wp20 gray2 fs-12 pt7 right-text"><?= $fieldsNames['client'][ $dak['fld_name'] ] ?>:</div>
						<div class="flex-string wp80 pl10 relativ">

							<?php
							$element = new Elements();
							print $su = $element -> IndustrySelect($dak['fld_name'], [
								"class" => [
									"wp95",
									$dak['fld_required']
								],
								"sel"   => $client['idcategory'],
								"tip"   => $tt,
								"data"  => 'data-class="'.$dak['fld_required'].'"'
							]);
							?>
							<?php if (!$otherSettings['guidesEdit']) { ?>
								<span class="hidden-ipad"><a href="javascript:void(0)" onclick="add_sprav('category','<?= $dak['fld_name'] ?>')" title="Добавить"><i class="icon-plus-circled blue"></i></a></span>
							<?php } ?>

						</div>
					</div>
					<?php
				}
				elseif ($dak['fld_name'] == 'clientpath') { ?>

					<div class="flex-container mt10" data-block="<?php echo $dak['fld_name']; ?>" data-xtype="<?=$xf[$dak['fld_name']]?>">
						<div class="flex-string wp20 gray2 fs-12 pt7 right-text"><?= $fieldsNames['client'][ $dak['fld_name'] ] ?>:</div>
						<div class="flex-string wp80 pl10 relativ">

							<?php
							$element = new Elements();
							print $su = $element -> ClientpathSelect($dak['fld_name'], [
								"class" => [
									"wp95",
									$dak['fld_required']
								],
								"sel"   => $client['clientpath'],
								"data"  => 'data-class="'.$dak['fld_required'].'"'
							]);
							?>
							<?php if (!$otherSettings['guidesEdit']) { ?>
								<span class="hidden-ipad"><a href="javascript:void(0)" onclick="add_sprav('clientpath','<?= $dak['fld_name'] ?>')" title="Добавить"><i class="icon-plus-circled blue"></i></a></span>
							<?php } ?>

						</div>
					</div>
					<?php
				}
				elseif ($dak['fld_name'] == 'head_clid') {
					?>
					<div class="flex-container box--child mt10" data-block="<?php echo $dak['fld_name']; ?>">

						<div class="flex-string wp20 gray2 fs-12 pt7 right-text"><?= $fieldsNames['client'][ $dak['fld_name'] ] ?>:</div>
						<div class="flex-string wp80 pl10 relativ" id="head_clid">
							<INPUT type="hidden" id="head_clid" name="head_clid" value="<?= $client['head_clid'] ?>">
							<INPUT id="lst_spisok" type="text" class="<?= $dak['fld_required'] ?> wp95" value="<?= current_client($client['head_clid']) ?>" readonly onclick="_.debounce(get_orgspisok('lst_spisok','head_clid','/content/helpers/client.helpers.php?action=get_orgselector','<?= $dak['fld_name'] ?>'), 500)" placeholder="Нажмите для выбора">
							<span class="idel"><i title="Очистить" onclick="$('input#head_clid').val(0); $('#lst_spisok').val('');" class="icon-block red" style="cursor:pointer"></i></span>
							<div class="smalltxt">Укажите головную организацию</div>
						</div>

					</div>
					<?php
				}
				elseif ($dak['fld_name'] == 'pid') {

					if ($clid > 0) {
						?>
						<div class="flex-container box--child mt10" data-block="<?php echo $dak['fld_name']; ?>">

							<div class="flex-string wp20 gray2 fs-12 pt7 right-text"><?= $dak['fld_title'] ?>:</div>
							<div class="flex-string wp80 pl10">

								<?php
								$datas = [];
								$r     = $db -> getAll("SELECT pid, ptitle, person FROM {$sqlname}personcat WHERE clid = '$clid' and clid > 0 and identity = '$identity' ORDER BY ptitle");
								foreach ($r as $item) {
									$datas[] = [
										"id"    => $item['pid'],
										"title" => $item['person']." [ ".$item['ptitle']." ]"
									];
								}

								print $su = Elements ::Select($dak['fld_name'], $datas, [
									"class"      => [
										"wp95",
										$dak['fld_required']
									],
									"emptyValue" => 0,
									"nowrapper"  => true,
									"sel"        => $client['pid'],
									"data"       => 'data-class="'.$dak['fld_required'].'"'
								]);
								?>

							</div>

						</div>
						<?php
					}

				}
				elseif ($dak['fld_name'] == 'address') { ?>
					<div class="flex-container box--child mt10" data-block="<?php echo $dak['fld_name']; ?>" data-xtype="<?=$xf[$dak['fld_name']]?>">

						<div class="flex-string wp20 gray2 fs-12 pt7 right-text"><?= $fieldsNames['client'][ $dak['fld_name'] ] ?>:</div>
						<div class="flex-string wp80 pl10 relativ">
							<INPUT name="<?= $dak['fld_name'] ?>" class="<?= $dak['fld_required'] ?> wp95" id="<?= $dak['fld_name'] ?>" value="<?= $client['address'] ?>" data-type="address">
						</div>

					</div>
					<?php
				}
				elseif ($dak['fld_name'] == 'phone') { ?>
					<div class="flex-container box--child mt10" id="vphone1" data-block="<?php echo $dak['fld_name']; ?>">

						<div class="flex-string wp20 gray2 fs-12 pt7 right-text"><?= $fieldsNames['client'][ $dak['fld_name'] ] ?>:</div>
						<div class="flex-string wp80 pl10 norelativ">
							<div id="v<?= $dak['fld_name'] ?>">
								<?php
								if ($format_phone != '') {

									if ($client['phone'] != '') {

										$phones = yexplode(",", (string)$client['phone']);
										foreach ($phones as $i => $xphone) {

											$adder = ($i == (count($phones) - 1)) ? '<span class="adder hand" title="" data-block="phoneBlock" data-main="v'.$dak['fld_name'].'" data-mask="'.$format_phone.'"><i class="icon-plus-circled green"></i></span>' : '';

											?>
											<div class="phoneBlock paddbott5 relativv">
												<INPUT name="<?= $dak['fld_name'] ?>[]" type="text" class="phone w250 <?= $dak['fld_required'] ?>" id="<?= $dak['fld_name'] ?>[]" value="<?= $xphone ?>" placeholder="Формат: <?= $format_tel ?>" data-id="v<?= $dak['fld_name'] ?>" data-action="valphone" data-type="client.helpers" autocomplete="off">
												<span class="remover hand" data-parent="v<?= $dak['fld_name'] ?>"><i class="icon-minus-circled red"></i></span><?= $adder ?>
											</div>
											<?php

										}

									}
									else {
										?>
										<div class="phoneBlock paddbott5 relativv">
											<INPUT name="<?= $dak['fld_name'] ?>[]" type="text" class="phone w250 <?= $dak['fld_required'] ?>" id="<?= $dak['fld_name'] ?>[]" value="<?= $phone ?>" placeholder="Формат: <?= $format_tel ?>" data-id="v<?= $dak['fld_name'] ?>" data-action="valphone" data-type="client.helpers" autocomplete="off">
											<span class="remover hand" data-parent="v<?= $dak['fld_name'] ?>"><i class="icon-minus-circled red"></i></span>
											<span class="adder hand" title="" data-block="phoneBlock" data-main="vphone" data-mask="<?= $format_phone ?>"><i class="icon-plus-circled green"></i></span>
										</div>
										<?php
									}
								}
								else {
									?>
									<div class="phoneBlock paddbott5 relativv">
										<INPUT name="<?= $dak['fld_name'] ?>" type="text" class="phone <?= $dak['fld_required'] ?>" style="width:98%" id="<?= $dak['fld_name'] ?>" value="<?= $client['phone'] ?>" placeholder="Формат: <?= $format_tel ?>" data-id="v<?= $dak['fld_name'] ?>" data-action="valphone" data-type="client.helpers" autocomplete="off">
										<div class="em blue smalltxt">Используйте <b>запятую</b> в качестве разделителя
										</div>
									</div>
									<?php
								}
								?>
							</div>
						</div>

					</div>
					<?php
				}
				elseif ($dak['fld_name'] == 'fax') { ?>
					<div class="flex-container box--child mt10" data-block="<?php echo $dak['fld_name']; ?>">

						<div class="flex-string wp20 gray2 fs-12 pt7 right-text"><?= $fieldsNames['client'][ $dak['fld_name'] ] ?>:</div>
						<div class="flex-string wp80 pl10 norelativ">
							<div id="v<?= $dak['fld_name'] ?>">
								<?php
								if ($format_phone != '') {

									if ($client['fax'] != '') {

										$phones = yexplode(",", (string)$client['fax']);
										foreach ($phones as $i => $xphone) {

											$adder = ($i == (count($phones) - 1)) ? '<span class="adder hand" title="" data-block="phoneBlock" data-main="v'.$dak['fld_name'].'" data-mask="'.$format_phone.'"><i class="icon-plus-circled green"></i></span>' : '';

											?>
											<div class="phoneBlock paddbott5 relativv">
												<INPUT name="<?= $dak['fld_name'] ?>[]" type="text" class="phone w250 <?= $dak['fld_required'] ?>" id="<?= $dak['fld_name'] ?>[]" value="<?= $xphone ?>" placeholder="Формат: <?= $format_tel ?>" data-id="v<?= $dak['fld_name'] ?>" data-action="valphone" data-type="client.helpers" autocomplete="off">
												<span class="remover hand" data-parent="v<?= $dak['fld_name'] ?>"><i class="icon-minus-circled red"></i></span><?= $adder ?>
											</div>
											<?php
										}
									}
									else {
										?>
										<div class="phoneBlock paddbott5 relativv">
											<INPUT name="<?= $dak['fld_name'] ?>[]" type="text" class="phone w250 <?= $dak['fld_required'] ?>" id="<?= $dak['fld_name'] ?>[]" value="<?= $phone ?>" placeholder="Формат: <?= $format_tel ?>" data-id="v<?= $dak['fld_name'] ?>" data-action="valphone" data-type="client.helpers" autocomplete="off">
											<span class="remover hand" data-parent="v<?= $dak['fld_name'] ?>"><i class="icon-minus-circled red"></i></span>
											<span class="adder hand" title="" data-block="phoneBlock" data-main="v<?= $dak['fld_name'] ?>" data-mask="<?= $format_phone ?>"><i class="icon-plus-circled green"></i></span>
										</div>
										<?php
									}
								}
								else {
									?>
									<div class="phoneBlock paddbott5 relativv">
										<INPUT name="<?= $dak['fld_name'] ?>" type="text" class="phone <?= $dak['fld_required'] ?>" style="width:98%" id="<?= $dak['fld_name'] ?>" value="<?= $client['fax'] ?>" placeholder="Формат: <?= $format_tel ?>" data-id="v<?= $dak['fld_name'] ?>" data-action="valphone" data-type="client.helpers" autocomplete="off">
										<div class="em blue smalltxt">Используйте
											<b>запятую</b> в качестве разделителя
										</div>
									</div>
									<?php
								}
								?>
							</div>
						</div>

					</div>
					<?php
				}
				elseif ($dak['fld_name'] == 'site_url') { ?>
					<div class="flex-container box--child mt10" id="surl" data-block="<?php echo $dak['fld_name']; ?>">

						<div class="flex-string wp20 gray2 fs-12 pt7 right-text"><?= $fieldsNames['client'][ $dak['fld_name'] ] ?>:</div>
						<div class="flex-string wp80 pl10 norelativ">
							<INPUT name="<?= $dak['fld_name'] ?>" type="text" class="<?= $dak['fld_required'] ?> wp95 validate" id="<?= $dak['fld_name'] ?>" value="<?= $client['site_url'] ?>" onMouseOut="$('#ospisok').remove();" onblur="$('#ospisok').remove();" data-url="/content/helpers/client.helpers.php" data-action="valsite">
						</div>

					</div>
					<?php
				}
				elseif ($dak['fld_name'] == 'mail_url') {
					?>
					<div class="flex-container box--child mt10" id="vmail" data-block="<?php echo $dak['fld_name']; ?>">

						<div class="flex-string wp20 gray2 fs-12 pt7 right-text"><?= $fieldsNames['client'][ $dak['fld_name'] ] ?>:</div>
						<div class="flex-string wp80 pl10 norelativ">
							<INPUT name="<?= $dak['fld_name'] ?>" type="text" class="<?= $dak['fld_required'] ?> wp95 validate" id="<?= $dak['fld_name'] ?>" value="<?= $client['mail_url'] ?>" onMouseOut="$('#ospisok').remove();" onblur="$('#ospisok').remove();" data-url="/content/helpers/client.helpers.php" data-action="valmail">
						</div>

					</div>
					<?php
				}
				elseif ($dak['fld_name'] == 'territory') { ?>
					<div class="flex-container box--child mt10" data-block="<?php echo $dak['fld_name']; ?>" data-xtype="<?=$xf[$dak['fld_name']]?>">

						<div class="flex-string wp20 gray2 fs-12 pt7 right-text"><?= $fieldsNames['client'][ $dak['fld_name'] ] ?>:</div>
						<div class="flex-string wp80 pl10 relativ">

							<?php
							$element = new Elements();
							print $su = $element -> TerritorySelect($dak['fld_name'], [
								"class" => [
									"wp95",
									$dak['fld_required']
								],
								"sel"   => $client['territory'],
								"data"  => 'data-class="'.$dak['fld_required'].'"'
							]);
							?>
							<?php if (!$otherSettings['guidesEdit']) { ?>
								<span class="hidden-ipad"><a href="javascript:void(0)" onclick="add_sprav('territory','<?= $dak['fld_name'] ?>')" title="Добавить"><i class="icon-plus-circled blue"></i></a></span>
							<?php } ?>

						</div>

					</div>
					<?php
				}
				elseif ($dak['fld_name'] == 'des') {
					?>
					<div id="divider"><?= $dak['fld_title'] ?></div>

					<div class="flex-container box--child mt10" data-block="<?php echo $dak['fld_name']; ?>">

						<div class="flex-string wp95 relativ div-center">
							<textarea name="<?= $dak['fld_name'] ?>" rows="4" class="<?= $dak['fld_required'] ?> wp95 p5" id="<?= $dak['fld_name'] ?>"><?= $client['des'] ?></textarea>
						</div>

					</div>
					<hr>
					<?php
				}
				elseif ($dak['fld_name'] == 'scheme') {
					?>
					<div id="divider"><?= $dak['fld_title'] ?></div>

					<div class="flex-container box--child mt10" data-block="<?php echo $dak['fld_name']; ?>">

						<div class="flex-string wp80 relativ div-center">
							<textarea name="<?= $dak['fld_name'] ?>" rows="5" class="<?= $dak['fld_required'] ?> wp95 p5" id="<?= $dak['fld_name'] ?>" placeholder="Как принимают решение? Например: проводят тендеры"><?= $client['scheme'] ?></textarea>
						</div>

					</div>
					<hr>
					<?php
				}
				elseif ($dak['fld_name'] == 'tip_cmr') { ?>
					<div class="flex-container mt10" data-block="<?php echo $dak['fld_name']; ?>" data-xtype="<?=$xf[$dak['fld_name']]?>">

						<div class="flex-string wp20 gray2 fs-12 pt7 right-text"><?= $fieldsNames['client'][ $dak['fld_name'] ] ?>:</div>
						<div class="flex-string wp80 pl10">

							<?php
							$relDefault = ($client['tip_cmr'] != '') ? $client['tip_cmr'] : $relTitleDefault;

							$element = new Elements();
							print $su = $element -> RelationSelect($dak['fld_name'], [
								"class" => [
									"wp95",
									$dak['fld_required']
								],
								"sel"   => $relDefault,
								"data"  => 'data-class="'.$dak['fld_required'].'"'
							]);
							?>

						</div>

					</div>
					<?php
				}
				elseif ($dak['fld_name'] != 'recv' || stripos($dak['fld_name'], 'input') !== false) {

					if ($dak['fld_temp'] == "textarea") {
						?>
						<div id="divider"><?= $dak['fld_title'] ?></div>

						<div class="flex-container box--child mt10 pl20 pr20" data-block="<?php echo $dak['fld_name']; ?>" data-xtype="<?php echo $dak['fld_sub']; ?>">

							<div class="flex-string wp100 relativ div-xenter">
								<textarea name="<?= $dak['fld_name'] ?>" rows="4" class="<?= $dak['fld_required'] ?> p5 wp95" id="<?= $dak['fld_name'] ?>"><?= $client[ $dak['fld_name'] ] ?></textarea>
							</div>

						</div>
						<hr>
						<?php
					}
					elseif ($dak['fld_temp'] == "--Обычное--" || $dak['fld_temp'] == "") {

						//$isinnkpp = '';
						//$isinnkpp = ( texttosmall( $fieldsNames[ 'client' ][ $dak[ 'fld_name' ] ] ) == 'инн' ) ? "isinn" : "";
						$isinnkpp = (texttosmall($fieldsNames['client'][ $dak['fld_name'] ]) == 'кпп') ? "iskpp" : $isinnkpp;
						?>
						<div class="flex-container box--child mt10" data-block="<?php echo $dak['fld_name']; ?>" data-xtype="<?php echo $dak['fld_sub']; ?>">

							<div class="flex-string wp20 gray2 fs-12 pt7 right-text"><?= $fieldsNames['client'][ $dak['fld_name'] ] ?>:</div>
							<div class="flex-string wp80 pl10 relativ">
								<INPUT name="<?= $dak['fld_name'] ?>" class="<?= $dak['fld_required'] ?> <?= $isinnkpp ?> wp95" id="<?= $dak['fld_name'] ?>" value="<?= $client[ $dak['fld_name'] ] ?>" autocomplete="off">
							</div>

						</div>
						<?php
					}
					elseif ( $dak[ 'fld_temp' ] == "hidden" ) {
						?>
						<input id="<?= $dak[ 'fld_name' ] ?>" name="<?= $dak[ 'fld_name' ] ?>" type="hidden" value="<?= $client[ $dak['fld_name'] ] ?>">
						<?php
					}
					elseif ($dak['fld_temp'] == "adres") {
						?>
						<div class="flex-container box--child mt10" data-block="<?php echo $dak['fld_name']; ?>" data-xtype="<?php echo $dak['fld_sub']; ?>">

							<div class="flex-string wp20 gray2 fs-12 pt7 right-text"><?= $fieldsNames['client'][ $dak['fld_name'] ] ?>:</div>
							<div class="flex-string wp80 pl10 relativ">
								<INPUT name="<?= $dak['fld_name'] ?>" class="<?= $dak['fld_required'] ?> wp95" id="<?= $dak['fld_name'] ?>" value="<?= $client[ $dak['fld_name'] ] ?>" data-type="address">
							</div>

						</div>
						<?php
					}
					elseif ($dak['fld_temp'] == "select") {
						$vars = yexplode(",", (string)$dak['fld_var']);
						?>
						<div class="flex-container box--child mt10" data-block="<?php echo $dak['fld_name']; ?>" data-xtype="<?php echo $dak['fld_sub']; ?>">

							<div class="flex-string wp20 gray2 fs-12 pt7 right-text"><?= $fieldsNames['client'][ $dak['fld_name'] ] ?>:</div>
							<div class="flex-string wp80 pl10 relativ">

								<?php
								$datas = [];
								foreach ($vars as $var) {
									$datas[] = [
										"id"    => $var,
										"title" => $var
									];
								}
								print $su = Elements ::Select($dak['fld_name'], $datas, [
									"class"     => [
										"wp95",
										$dak['fld_required']
									],
									"nowrapper" => true,
									"sel"       => $client[ $dak['fld_name'] ],
									"data"      => 'data-class="'.$dak['fld_required'].'"'
								]);
								?>

							</div>

						</div>
						<?php
					}
					elseif ($dak['fld_temp'] == "multiselect") {

						$vars = explode(",", $dak['fld_var']);
						?>
						<div id="divider"><b><?= $dak['fld_title'] ?></b></div>

						<div class="flex-container box--child mt10 <?= ($dak['fld_required'] == 'required' ? 'multireq' : '') ?>" data-block="<?php echo $dak['fld_name']; ?>" data-xtype="<?php echo $dak['fld_sub']; ?>">

							<div class="flex-string wp100 relativ">

								<?php
								$datas = [];
								foreach ($vars as $var) {
									$datas[] = [
										"id"    => $var,
										"title" => $var
									];
								}
								print $su = Elements ::Select($dak['fld_name']."[]", $datas, [
									"class"        => [
										"wp95"
									],
									"nowrapper"    => true,
									"multiple"     => true,
									"multipleInit" => false,
									"sel"          => yexplode(",", (string)$client[ $dak['fld_name'] ])
								]);
								?>

							</div>

						</div>
						<hr>
						<?php
					}
					elseif ($dak['fld_temp'] == "inputlist") {

						$vars = $dak['fld_var'];
						?>
						<div class="flex-container box--child mt10" data-block="<?php echo $dak['fld_name']; ?>" data-xtype="<?php echo $dak['fld_sub']; ?>">

							<div class="flex-string wp20 gray2 fs-12 pt7 right-text"><?= $fieldsNames['client'][ $dak['fld_name'] ] ?>:</div>
							<div class="flex-string wp80 pl10 relativ">
								<input type="text" name="<?= $dak['fld_name'] ?>" id="<?= $dak['fld_name'] ?>" class="<?= $dak['fld_required'] ?> wp95" value="<?= $client[ $dak['fld_name'] ] ?>" placeholder="<?= $dak['fld_title'] ?>"/>
								<div class="fs-09 em blue"><em>Двойной клик мышкой для показа вариантов</em></div>
								<script>
									var str = '<?=$vars?>';
									var data = str.split(',');
									$("#<?=$dak['fld_name']?>").autocomplete(data, {
										autoFill: true,
										minLength: 0,
										minChars: 0,
										cacheLength: 5,
										max: 100,
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
					elseif ($dak['fld_temp'] == "radio") {

						$vars = explode(",", $dak['fld_var']);
						?>
						<div class="flex-container box--child mt20 mb20 <?= ($dak['fld_required'] == 'required' ? 'req' : '') ?>" data-block="<?php echo $dak['fld_name']; ?>" data-xtype="<?php echo $dak['fld_sub']; ?>">

							<div class="flex-string wp20 gray2 fs-12 pt7 right-text"><?= $fieldsNames['client'][ $dak['fld_name'] ] ?>:</div>
							<div class="flex-string wp80 pl10 relativ">

								<div class="flex-container box--child wp95--5">

									<?php
									foreach ($vars as $var) {

										if ($var == $client[ $dak['fld_name'] ]) {
											$s = 'checked';
										}
										else {
											$s = '';
										}
										?>
										<div class="flex-string p10 mr5 mb5 flx-basis-20 viewdiv bgwhite inset">
											<div class="radio">
												<label>
													<input name="<?= $dak['fld_name'] ?>" type="radio" id="<?= $dak['fld_name'] ?>" <?= $s ?> value="<?= $var ?>"/>
													<span class="custom-radio success1"><i class="icon-radio-check"></i></span>
													<span class="title"><?= $var ?></span>
												</label>
											</div>
										</div>
									<?php } ?>
									<?php if ($dak['fld_required'] != 'required') { ?>
										<div class="flex-string p10 mr5 mb5 flx-basis-20 viewdiv bgwhite inset">

											<div class="radio">
												<label>
													<input name="<?= $dak['fld_name'] ?>" type="radio" id="<?= $dak['fld_name'] ?>" <?= ($client[ $dak['fld_name'] ] == '' ? 'checked' : '') ?> value="">
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
					elseif ($dak['fld_temp'] == "datum") {
						?>
						<div class="flex-container box--child mt10" data-block="<?php echo $dak['fld_name']; ?>" data-xtype="<?php echo $dak['fld_sub']; ?>">

							<div class="flex-string wp20 gray2 fs-12 pt7 right-text"><?= $fieldsNames['client'][ $dak['fld_name'] ] ?>:</div>
							<div class="flex-string wp80 pl10 relativ">
								<INPUT name="<?= $dak['fld_name'] ?>" type="text" id="<?= $dak['fld_name'] ?>" class="<?= $dak['fld_required'] ?> wp30 inputdate" value="<?= $client[ $dak['fld_name'] ] ?>" autocomplete="off">
							</div>

						</div>
						<?php
					}
					$i++;

				}

			}
			?>

		</DIV>

		<hr>

		<div class="pull-left ml20 mt0" id="validated"></div>

		<div class="text-right button--pane">

			<A href="javascript:void(0)" onclick="validateINN()" class="button">Сохранить</A>&nbsp;
			<A href="javascript:void(0)" onclick="DClose()" class="button">Отмена</A>

		</div>
	</FORM>
	<?php

	$hooks -> do_action("client_form_after", $_REQUEST);

}

if ($action == "mass") {

	$id  = (array)$_REQUEST['ch'];
	$kol = $_REQUEST['count'];
	$ids = implode(",", $id);
	?>
	<div class="zagolovok"><b>Групповое действие</b></div>
	<form action="/content/core/core.client.php" id="clientForm" name="clientForm" method="post" enctype="multipart/form-data">
		<input name="ids" id="ids" type="hidden" value="<?= $ids ?>">
		<input name="goal" id="goal" type="hidden" value="client">
		<input name="action" id="action" type="hidden" value="client.mass">

		<DIV id="formtabs" class="box--child" style="overflow-y: auto; overflow-x:hidden; max-height:80vh;">

			<div class="infodiv mb10">
				<b class="red">Важная инфрмация:</b>
				<ul>
					<li class="Bold blue">При нажатой клавише Ctrl можно мышкой выбрать нужные записи</li>
					<li>Отмена групповых действий не возможна</li>
					<li>Действия будут применены только для записей, к которым у вас есть доступ</li>
					<li>Ограничение для действия составляет 1000 записей</li>
				</ul>
			</div>

			<div class="fmain1 box--child">

				<!--Старая реализация-->
				<div class="flex-container mb10 hidden">

					<div class="flex-string wp20 gray2 fs-12 pt7 right-text">Действие:</div>
					<div class="flex-string wp80 pl10">

						<select name="doAction1" id="doAction1" onchange="showd()">
							<option value="">--выбор действия--</option>
							<?php
							if ($_REQUEST['matip'] != 'contragent' && !$userRights['nouserchange']) {

								print '<option value="userChange" selected>Смена ответственного</option>';

							}
							?>
							<option value="dostupChange">Предоставить доступ сотруднику</option>
							<option value="terChange">Установить территорию</option>
							<option value="cmrChange">Установить тип отношений</option>
							<?php
							if ($userRights['group']) {

								print '<option value="groupChange">Добавить в группу</option>';

							}
							if ($_REQUEST['matip'] != 'contragent') {

								print '<option value="clientTrash">Удалить в корзину</option>';

							}
							if ($isadmin == 'on' || $tipuser == 'Администратор' || ($userRights['groupactions'] && $userRights['delete'])) {

								print '<option value="clientDelete">Удалить навсегда</option>';

							}
							?>
						</select>

					</div>

				</div>

				<!--Новая реализация-->
				<div class="flex-container mb10">

					<!--<div class="flex-string wp20 gray2 fs-12 pt7 right-text">Действие:</div>-->
					<div class="flex-string wp100 pl10">

						<div class="flex-container box--child wp95--5">

							<?php
							if ($_REQUEST['matip'] != 'contragent' && !$userRights['nouserchange']) {
								?>
								<div class="flex-string p10 mr5 mb5 flx-3 viewdiv bgwhite inset bluebg-sub" data-type="check">

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
											<input name="doAction" type="radio" id="doAction" value="terChange" onchange="showd()">
											<span class="custom-radio success1"><i class="icon-radio-check"></i></span>
										</span>
										<span class="title"><i class="icon-globe broun"></i>&nbsp;Территория</span>
									</label>
								</div>

							</div>

							<div class="flex-string p10 mr5 mb5 flx-3 viewdiv bgwhite inset" data-type="check">

								<div class="radio">
									<label>
										<span class="hidden">
											<input name="doAction" type="radio" id="doAction" value="cmrChange" onchange="showd()">
											<span class="custom-radio success1"><i class="icon-radio-check"></i></span>
										</span>
										<span class="title"><i class="icon-handshake-o green"></i>&nbsp;Тип&nbsp;отношений</span>
									</label>
								</div>

							</div>

							<?php
							if ($userRights['group']) {
								?>
								<div class="flex-string p10 mr5 mb5 flx-3 viewdiv bgwhite inset" data-type="check">

									<div class="radio">
										<label>
											<span class="hidden">
												<input name="doAction" type="radio" id="doAction" value="groupChange" onchange="showd()">
												<span class="custom-radio success1"><i class="icon-radio-check"></i></span>
											</span>
											<span class="title"><i class="icon-sitemap fiolet"></i>&nbsp;В&nbsp;группу</span>
										</label>
									</div>

								</div>
							<?php } ?>

							<?php
							if ($_REQUEST['matip'] != 'contragent') {
								?>
								<div class="flex-string p10 mr5 mb5 flx-3 viewdiv bgwhite inset" data-type="check">

									<div class="radio">
										<label>
											<span class="hidden">
												<input name="doAction" type="radio" id="doAction" value="clientTrash" onchange="showd()">
												<span class="custom-radio success1"><i class="icon-radio-check"></i></span>
											</span>
											<span class="title"><i class="icon-trash orange"></i>&nbsp;В&nbsp;корзину</span>
										</label>
									</div>

								</div>
							<?php } ?>

							<?php
							if ($isadmin == 'on' || $tipuser == 'Администратор' || ($userRights['groupactions'] && $userRights['delete'])) {
								?>
								<div class="flex-string p10 mr5 mb5 flx-3 viewdiv bgwhite inset" data-type="check">

									<div class="radio">
										<label>
											<span class="hidden">
												<input name="doAction" type="radio" id="doAction" value="clientDelete" onchange="showd()">
												<span class="custom-radio success1"><i class="icon-radio-check"></i></span>
											</span>
											<span class="title"><i class="icon-cancel-circled red"></i>&nbsp;Удалить&nbsp;навсегда</span>
										</label>
									</div>

								</div>
							<?php } ?>

						</div>

					</div>

				</div>

				<div id="divider">Опции</div>

				<div class="flex-container mb10" id="userdiv">

					<div class="flex-string wp20 fs-12 pt7 right-text blue Bold">Новый:</div>
					<div class="flex-string wp80 pl10">
						<?php
						$element = new Elements();
						$exclude = ($isadmin == 'on' || stripos($tipuser, 'Руководитель') !== false) ? 0 : $iduser1;
						print $element -> UsersSelect("newuser", [
							"class"   => "wp95",
							"active"  => true,
							"sel"     => "-1",
							"exclude" => $exclude
						]);
						?>
					</div>

				</div>
				<div class="flex-container mb10 hidden" id="dostupdiv">

					<div class="flex-string wp20 fs-12 pt7 right-text blue Bold">Сотрудник:</div>
					<div class="flex-string wp80 pl10">
						<?php
						$element = new Elements();
						print $element -> UsersSelect("duser", [
							"class"   => "wp95",
							"active"  => true,
							"sel"     => "-1",
							//"exclude" => $iduser1
						]);
						?>
					</div>

				</div>
				<div class="flex-container mb10 hidden" id="terrdiv">

					<div class="flex-string wp20 fs-12 pt7 right-text blue Bold">Территория:</div>
					<div class="flex-string wp80 pl10">
						<select name="nterritory" id="nterritory" class="wp95">
							<?php
							$result = $db -> query("SELECT * FROM {$sqlname}territory_cat WHERE identity = '$identity' ORDER by title");
							while ($data = $db -> fetch($result)) {

								print '<option value="'.$data['idcategory'].'">'.$data['title'].'</option>';

							}
							?>
						</select>
					</div>

				</div>
				<div class="flex-container mb10 hidden" id="cmrdiv">

					<div class="flex-string wp20 fs-12 pt7 right-text blue Bold">Тип отношений:</div>
					<div class="flex-string wp80 pl10">
						<select name="tipcmr" id="tipcmr" class="wp95">
							<?php
							$result = $db -> query("SELECT * FROM {$sqlname}relations WHERE identity = '$identity' ORDER by title");
							while ($data = $db -> fetch($result)) {

								print '<option value="'.$data['title'].'">'.$data['title'].'</option>';

							}
							?>
						</select>
					</div>

				</div>
				<div class="flex-container mb10 hidden" id="grpt">

					<div class="flex-string wp20 fs-12 pt7 right-text blue Bold">Группа:</div>
					<div class="flex-string wp80 pl10">

						<select name="newgid" id="newgid" class="wp95">
							<option value="">--выбор--</option>
							<?php
							print '<optgroup label="Группа CRM"></optgroup>';

							$result = $db -> query("SELECT * FROM {$sqlname}group WHERE COALESCE(service, '') = '' and identity = '$identity'");
							while ($data_array = $db -> fetch($result)) {
								print '<option value="'.$data_array['id'].'">&nbsp;&nbsp;'.$data_array['name'].'</option>';
							}

							$result = $db -> query("SELECT * FROM {$sqlname}services WHERE user_key != '' and tip = 'mail' and identity = '$identity'");
							while ($data = $db -> fetch($result)) {

								print '<optgroup label="'.$data['name'].'"></optgroup>';

								$re = $db -> query("SELECT * FROM {$sqlname}group WHERE service = '".$data['name']."' and identity = '$identity'");
								while ($da = $db -> fetch($re)) {
									print '<option value="'.$da['id'].'">&nbsp;&nbsp;'.$da['name'].'</option>';
								}

							}
							?>
						</select>
						<div class="infodiv bgwhite wp95 mt10">Позиции будут добавлены в выбранную группу. Если запись относится к сервису рассылок, то подписчик будет добавлен в список на стороне сервиса.</div>

					</div>

				</div>

				<div class="flex-container mb10 pt15 warning bgwhite">

					<div class="flex-string wp20 gray2 fs-12 right-text">Выполнить для:</div>
					<div class="flex-string wp40 pl10">
						<div class="radio">
							<label>
								<input name="isSelect" id="isSelect" value="doSelected" type="radio" <?php if ($kol > 0) {
									print "checked";
								} ?>>
								<span class="custom-radio success1"><i class="icon-radio-check"></i></span>
								<span class="title">Выбранного (<b class="blue"><?= $kol ?></b>)</span>
							</label>
						</div>
					</div>
					<div class="flex-string wp40 pl10">
						<div class="radio" title="Действие возможно для 500 записей максимум">
							<label>
								<input name="isSelect" id="isSelect" value="doAll" type="radio" <?php if ($kol == 0) {
									print "checked";
								} ?>>
								<span class="custom-radio success1"><i class="icon-radio-check"></i></span>
								<span class="title">Всех записей (<b class="blue"><span id="counts"></span></b> из <span id="alls"></span>)</span>
							</label>
						</div>
					</div>

				</div>

				<div class="flex-container mb10" id="appendix">

					<div class="flex-string wp20 gray2 fs-12 pt7 right-text">также:</div>
					<div class="flex-string wp80 pl10">

						<div class="infodiv bgwhite wp95">

							<div class="checkbox mb10">
								<label>
									<input name="person_send" id="person_send" value="yes" type="checkbox">
									<span class="custom-checkbox"><i class="icon-ok"></i></span>
									&nbsp;Включить Контакты
								</label>
							</div>

							<div class="checkbox mb10">
								<label>
									<input name="dog_send" id="dog_send" value="yes" type="checkbox">
									<span class="custom-checkbox"><i class="icon-ok"></i></span>
									&nbsp;Включить Сделки
								</label>
							</div>

							<div class="checkbox mb10">
								<label>
									<input name="credit_send" id="credit_send" value="yes" type="checkbox">
									<span class="custom-checkbox"><i class="icon-ok"></i></span>
									&nbsp;Включить Активные счета
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

				<div class="flex-container mb10" id="reazon">

					<div class="flex-string wp20 gray2 fs-12 pt7 right-text">Причина:</div>
					<div class="flex-string wp80 pl10">
						<textarea name="reazon" id="reazon" class="wp95"></textarea>
					</div>

				</div>
				<div class="flex-container mb10 hidden" id="dltt">

					<div class="flex-string wp100 pl10">

						<div class="infodiv"><b>Удаление клиентов со Сделками не поддерживается</b>.
							<b class="red">Отмена действия не возможна.</b><br><br>
							При включении Контактов будут удалены все связанные с Клиентом контакты. Также будут удалены связанные с Клиентом записи:<br>
							<ul>
								<li>Истории активностей</li>
								<li>Напоминания</li>
								<li>Файлы</li>
							</ul>
						</div>

					</div>

				</div>
				<div class="flex-container mb10 hidden" id="garbage">

					<div class="flex-string wp100 pl10">

						<div class="infodiv">
							<b class="red">Перемещение Клиента в корзину повлечет к откреплению Ответственных от выбранных Клиентов</b>. К таким записям будут иметь доступ все сотрудники.
						</div>

					</div>

				</div>

			</div>

		</div>

		<hr>

		<div class="button--pane text-right">

			<a href="javascript:void(0)" onclick="massSubmit()" class="button">Выполнить</a>&nbsp;
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

if ($action == "change.recvisites") {

	$json = get_client_recv($clid);
	$data = json_decode($json, true);
	$did  = (int)$_GET['did'];

	$file = $rootpath.'/cash/'.$fpath.'requisites.json';

	if (file_exists($file)) {
		$file     = file_get_contents($file);
		$recvName = json_decode($file, true);
	}
	else {
		$cfile     = file_get_contents($file);
		$recvName = json_decode($cfile, true);
	}

	$castUrName = ($data['castUrName'] == '') ? $data['castName'] : $data['castUrName'];
	?>
	<DIV class="zagolovok"><B>Реквизиты Клиента</B></DIV>
	<FORM action="/content/core/core.client.php" method="post" enctype="multipart/form-data" name="clientForm" id="clientForm" autocomplete="off">
		<INPUT type="hidden" name="action" id="action" value="client.change.recvisites">
		<INPUT type="hidden" name="clid" id="clid" value="<?= $clid ?>">
		<INPUT type="hidden" name="did" id="did" value="<?= $did ?>">
		<INPUT type="hidden" name="type" id="type" value="<?= $data['castType'] ?>">

		<DIV id="formtabs" style="overflow-y: auto; max-height:80vh;">

			<?php
			if ($data['castType'] != 'person') {
				?>

				<div class="box--child">

					<div class="flex-container mb10 mt20">

						<div class="flex-string wp20 pt7 gray2 fs-12 right-text">Юр. название:</div>
						<div class="flex-string wp80 pl10">
							<input name="recv[castUrName]" type="text" id="recv[castUrName]" class="wp95" value='<?= $castUrName ?>'/>
							<div class="fs-09 gray em">Название, которое можно использовать для документов</div>
						</div>

					</div>
					<div class="flex-container mb10">

						<div class="flex-string wp20 pt7 gray2 fs-12 right-text">Краткое название:</div>
						<div class="flex-string wp80 pl10">
							<input name="recv[castUrNameShort]" type="text" id="recv[castUrNameShort]" class="wp95" value='<?= $data['castUrNameShort'] ?>'>
							<div class="fs-09 gray em">Название, с краткой орг.формой</div>
						</div>

					</div>

					<div class="flex-container mb10">

						<div class="flex-string wp20 pt7 gray2 fs-12 right-text"><?= $recvName['recvInn'] ?>:</div>
						<div class="flex-string wp80 pl10">
							<input name="recv[castInn]" type="text" id="recv[castInn]" value="<?= $data['castInn'] ?>" maxlength="<?= $recvName['recvInnCount'] ?>" data-type="inn" class="wp95">
							<?php
							print ($dadataKey != '' ? '<div class="Bold blue wp95">Начните с ИНН (поиск с Dadata.ru)</div>' : '<div>Рекомендуем подключить сервис <a href="https://dadata.ru/suggestions/?from=SalesMan.pro" target="_blank" title="DaData.ru">DaData.ru</a></div>');
							?>

						</div>

					</div>
					<div class="flex-container mb10">

						<div class="flex-string wp20 pt7 gray2 fs-12 right-text"><?= $recvName['recvKpp'] ?>:</div>
						<div class="flex-string wp80 pl10">
							<input name="recv[castKpp]" type="text" id="recv[castKpp]" value="<?= $data['castKpp'] ?>" maxlength="<?= $recvName['recvKppCount'] ?>" class="wp95">
						</div>

					</div>
					<div class="flex-container mb10">

						<div class="flex-string wp20 gray2 fs-12 right-text"><?= $recvName['recvOkpo'] ?>/<?= $recvName['recvOgrn'] ?>:</div>
						<div class="flex-string wp80 pl10">
							<input name="recv[castOkpo]" type="text" id="recv[castOkpo]" value="<?= $data['castOkpo'] ?>" maxlength="<?= $recvName['recvOkpoCount'] ?>"/>&nbsp;/&nbsp;<input name="recv[castOgrn]" type="text" id="recv[castOgrn]" value="<?= $data['castOgrn'] ?>" maxlength="<?= $recvName['recvOgrnCount'] ?>"/>
						</div>

					</div>
					<div class="flex-container mb10">

						<div class="flex-string wp20 pt7 gray2 fs-12 right-text"><?= $recvName['recvBankName'] ?>:</div>
						<div class="flex-string wp80 pl10">
							<input name="recv[castBank]" type="text" id="recv[castBank]" class="wp95" value='<?= $data['castBank'] ?>'/>
						</div>

					</div>
					<div class="flex-container mb10">

						<div class="flex-string wp20 pt7 gray2 fs-12 right-text"><?= $recvName['recvBankBik'] ?>:</div>
						<div class="flex-string wp80 pl10">
							<input name="recv[castBankBik]" type="text" id="recv[castBankBik]" maxlength="<?= $recvName['recvBankBikCount'] ?>" value="<?= $data['castBankBik'] ?>"/><a href="javascript:void(0)" onclick="getBik()" title="Получить данные банка по БИК"><i class="icon-download"></i></a><span id="limit" class="red"></span>
						</div>

					</div>
					<div class="flex-container mb10">

						<div class="flex-string wp20 pt7 gray2 fs-12 right-text"><?= $recvName['recvBankRs'] ?>:</div>
						<div class="flex-string wp80 pl10">
							<input name="recv[castBankRs]" type="text" id="recv[castBankRs]" class="wp95" value="<?= $data['castBankRs'] ?>"/>
						</div>

					</div>
					<div class="flex-container mb10">

						<div class="flex-string wp20 pt7 gray2 fs-12 right-text"><?= $recvName['recvBankKs'] ?>:</div>
						<div class="flex-string wp80 pl10">
							<input name="recv[castBankKs]" type="text" id="recv[castBankKs]" class="wp95" value="<?= $data['castBankKs'] ?>"/>
						</div>

					</div>
					<div class="flex-container mb10">

						<div class="flex-string wp20 pt7 gray2 fs-12 right-text">Руководитель:</div>
						<div class="flex-string wp80 pl10">
							<input name="recv[castDirName]" type="text" id="recv[castDirName]" class="wp95" value="<?= $data['castDirName'] ?>" data-type="name">
							<div class="fs-09 gray em">В родительном падеже (в лице кого) - Иванова Ивана Ивановича</div>
						</div>

					</div>
					<div class="flex-container mb10">

						<div class="flex-string wp20 gray2 fs-12 right-text">Руководитель (подпись):</div>
						<div class="flex-string wp80 pl10">
							<input name="recv[castDirSignature]" type="text" id="recv[castDirSignature]" class="wp95" value="<?= $data['castDirSignature'] ?>" data-type="name">
							<div class="fs-09 gray em">Например: Иванов И.И.</div>
						</div>

					</div>
					<div class="flex-container mb10">

						<div class="flex-string wp20 pt7 gray2 fs-12 right-text">Должность:</div>
						<div class="flex-string wp80 pl10">
							<input name="recv[castDirStatus]" type="text" id="recv[castDirStatus]" class="wp95" value="<?= $data['castDirStatus'] ?>"/>
							<div class="fs-09 gray em">В родительном падеже - Директора</div>
						</div>

					</div>
					<div class="flex-container mb10">

						<div class="flex-string wp20 pt7 gray2 fs-12 right-text">Должность (подпись):</div>
						<div class="flex-string wp80 pl10">
							<input name="recv[castDirStatusSig]" type="text" id="recv[castDirStatusSig]" class="wp95" value="<?= $data['castDirStatusSig'] ?>"/>
							<div class="fs-09 gray em">Например: Директор</div>
						</div>

					</div>
					<div class="flex-container mb10">

						<div class="flex-string wp20 gray2 fs-12 right-text">Действует на основании:</div>
						<div class="flex-string wp80 pl10">
							<input name="recv[castDirOsnovanie]" type="text" id="recv[castDirOsnovanie]" class="wp95" value="<?= $data['castDirOsnovanie'] ?>"/>
							<div class="fs-09 gray em">В родительном падеже - Устава, Доверенности №ХХХ от ХХ.ХХ.ХХХХ г.</div>
						</div>

					</div>
					<div class="flex-container mb10">

						<div class="flex-string wp20 pt7 gray2 fs-12 right-text">Юр.адрес:</div>
						<div class="flex-string wp80 pl10">
							<input name="recv[castUrAddr]" type="text" id="recv[castUrAddr]" class="wp95" value="<?= $data['castUrAddr'] ?>" data-type="address">
						</div>

					</div>

				</div>

				<?php
			}
			else {
				?>

				<div class="box--child">

					<div class="flex-container mb10 mt20">
						<div class="flex-string wp20 pt7 gray2 fs-12 right-text">ФИО (полностью):</div>
						<div class="flex-string wp80 pl10">
							<input name="recv[castUrName]" type="text" id="recv[castUrName]" class="wp95" value='<?= $castUrName ?>' data-type="name">
							<div class="fs-09 gray em">ФИО, которое можно использовать для документов</div>
						</div>
					</div>
					<div class="flex-container mb10">

						<div class="flex-string wp20 pt7 gray2 fs-12 right-text">ФИО (краткое):</div>
						<div class="flex-string wp80 pl10">
							<input name="recv[castUrNameShort]" type="text" id="recv[castUrNameShort]" class="wp95" value="<?= $data['castUrNameShort'] ?>">
							<div class="fs-09 gray em">ФИО, с кратким написанием</div>
						</div>

					</div>

					<div class="flex-container mb10">
						<div class="flex-string wp20 pt7 gray2 fs-12 right-text">Паспорт:</div>
						<div class="flex-string wp80 pl10">

							<div class="inline">
								<input name="recv[castInn]" type="text" id="recv[castInn]" value="<?= $data['castInn'] ?>" maxlength="10" class="w60" placeholder="Серия"/><br>
								<div class="fs-09 gray em">Серия</div>
							</div>

							<div class="inline">
								<input name="recv[castKpp]" type="text" id="recv[castKpp]" value="<?= $data['castKpp'] ?>" maxlength="15" class="w80" placeholder="Номер"/><br>
								<div class="fs-09 gray em">Номер</div>
							</div>

							<div class="inline">
								<input name="recv[castDirStatus]" type="text" id="recv[castDirStatus]" value="<?= $data['castDirStatus'] ?>" class="w100" placeholder="Дата выдачи"/><br>
								<div class="fs-09 gray em">Дата выдачи</div>
							</div>

							<div class="inline">
								<input name="recv[castDirOsnovanie]" type="text" id="recv[castDirOsnovanie]" value="<?= $data['castDirOsnovanie'] ?>" class="w100" placeholder="Действителен до"/><br>
								<div class="fs-09 gray em">Действителен до</div>
							</div>
						</div>
					</div>

					<div class="flex-container mb10">
						<div class="flex-string wp20 pt7 gray2 fs-12 right-text">Кем выдан:</div>
						<div class="flex-string wp80 pl10">
							<textarea name="recv[castDirStatusSig]" id="recv[castDirStatusSig]" class="wp95"><?= $data['castDirStatusSig'] ?></textarea>
						</div>
					</div>

					<div class="flex-container mb10">
						<div class="flex-string wp20 pt7 gray2 fs-12 right-text">Дата рождения:</div>
						<div class="flex-string wp80 pl10">
							<input name="recv[castBank]" type="text" id="recv[castBank]" value="<?= $data['castBank'] ?>" maxlength="10">
						</div>
					</div>

					<div class="flex-container mb10">
						<div class="flex-string wp20 pt7 gray2 fs-12 right-text">Место рождения:</div>
						<div class="flex-string wp80 pl10">
							<textarea name="recv[castBankKs]" id="recv[castBankKs]" class="wp95"><?= $data['castBankKs'] ?></textarea>
						</div>
					</div>

					<div class="flex-container mb10">
						<div class="flex-string wp20 pt7 gray2 fs-12 right-text">Прописка (Страна):</div>
						<div class="flex-string wp80 pl10">
							<input name="recv[castBankRs]" type="text" id="recv[castBankRs]" class="wp95" value='<?= $data['castBankRs'] ?>'/>
						</div>
					</div>

					<div class="flex-container mb10">
						<div class="flex-string wp20 pt7 gray2 fs-12 right-text">Прописка (Область):</div>
						<div class="flex-string wp80 pl10">
							<input name="recv[castBankBik]" type="text" id="recv[castBankBik]" class="wp95" value="<?= $data['castBankBik'] ?>"/>
						</div>
					</div>

					<div class="flex-container mb10">
						<div class="flex-string wp20 pt7 gray2 fs-12 right-text">Прописка (Индекс):</div>
						<div class="flex-string wp80 pl10">
							<input name="recv[castOkpo]" type="text" id="recv[castOkpo]" class="wp20" value="<?= $data['castOkpo'] ?>"/>
						</div>
					</div>

					<div class="flex-container mb10">
						<div class="flex-string wp20 pt7 gray2 fs-12 right-text">Прописка (Город):</div>
						<div class="flex-string wp80 pl10">
							<input name="recv[castOgrn]" type="text" id="recv[castOgrn]" class="wp95" value="<?= $data['castOgrn'] ?>"/>
						</div>
					</div>

					<div class="flex-container mb10">
						<div class="flex-string wp20 pt7 gray2 fs-12 right-text">Прописка:</div>
						<div class="flex-string wp80 pl10">
							<input name="recv[castDirName]" type="text" id="recv[castDirName]" class="wp95" value="<?= $data['castDirName'] ?>">
							<div class="fs-09 gray em">Улица, дом, квартира</div>
						</div>
					</div>

					<div class="flex-container mb10">
						<div class="flex-string wp20 pt7 gray2 fs-12 right-text">ФИО Сотрудника:</div>
						<div class="flex-string wp80 pl10">
							<input name="recv[castDirSignature]" type="text" id="recv[castDirSignature]" class="wp95" value="<?= $data['castDirSignature'] ?>">
							<div class="fs-09 gray em">Например: Иванов И.И.</div>
						</div>
					</div>

				</div>

			<?php } ?>

		</DIV>

		<hr>

		<div class="button--pane text-right">

			<A href="javascript:void(0)" onclick="$('#clientForm').trigger('submit')" class="button">Сохранить</A>&nbsp;
			<A href="javascript:void(0)" onclick="DClose()" class="button">Отмена</A>

		</div>
	</FORM>
	<?php
}
if ($action == "change.dostup") {

	$iduser = (int)$db -> getOne("select iduser from {$sqlname}clientcat where clid='".$clid."' and identity = '$identity'");

	//список пользователей, которые имеют доступ
	$dostup = $db -> getCol("SELECT iduser FROM {$sqlname}dostup WHERE clid = '".$clid."' and identity = '$identity'");
	?>
	<DIV class="zagolovok"><B>Доступ к карточке Клиента</B></DIV>
	<form action="/content/core/core.client.php" method="post" enctype="multipart/form-data" name="clientForm" id="clientForm">
		<input type="hidden" id="action" name="action" value="client.change.dostup">
		<input name="clid" type="hidden" id="clid" value="<?= $clid ?>">

		<div class="flex-container box--child" style="overflow-y:auto;max-height:60vh">

			<?php
			$i = 0;

			$result = $db -> query("SELECT * FROM {$sqlname}user where identity = '$identity' ORDER by field(secrty, 'yes', 'no'), title");
			while ($data = $db -> fetch($result)) {

				$t = ($data['secrty'] != 'yes') ? '<b class="red">N/a:</b> ' : '';
				$s = (in_array($data['iduser'], (array)$dostup)) ? "checked" : '';

				?>
				<div class="flex-string wp50 p10 ha">
					<label class="pl10 Bold blue">
						<input type="checkbox" name="userlist[<?= $i ?>]" id="userlist[<?= $i ?>]" <?= $s ?> value="<?= $data['iduser'] ?>"/>&nbsp;&nbsp;<?= $t.$data['title'] ?>
					</label>
				</div>
				<?php
				$i++;
			}
			?>
		</div>

		<hr>

		<div class="button--pane text-right">

			<A href="javascript:void(0)" onclick="$('#clientForm').trigger('submit')" class="button">Сохранить</A>&nbsp;
			<A href="javascript:void(0)" onclick="DClose()" class="button">Отмена</A>

		</div>
	</form>
	<?php
}
if ($action == "change.desсription") {
	?>
	<DIV class="zagolovok"><B>Добавить новое примечание:</B></DIV>
	<form action="/content/core/core.client.php" method="post" enctype="multipart/form-data" name="clientForm" id="clientForm">
		<input type="hidden" id="action" name="action" value="client.change.description">
		<input name="clid" type="hidden" id="clid" value="<?= $clid ?>">
		<input name="pid" type="hidden" id="pid" value="<?= $pid ?>">
		<input name="did" type="hidden" id="did" value="<?= $did ?>">

		<TEXTAREA name="des" rows="4" class="required wp100" id="des"></TEXTAREA>

		<hr>

		<div class="button--pane text-rightright">
			<A href="javascript:void(0)" onclick="$('#clientForm').trigger('submit')" class="button">Сохранить</A>&nbsp;
			<A href="javascript:void(0)" onclick="DClose()" class="button">Отмена</A>
		</div>
	</form>
	<?php
}
if ($action == "change.user") {

	if ($clid > 0) {
		$iduser = getClientData( $clid, 'iduser' );
	}
	elseif ($pid > 0) {
		$iduser = getPersonData( $pid, 'iduser' );
	}

	$reazonReq = ($otherSettings['changeUserComment']) ? 'required' : '';
	?>
	<DIV class="zagolovok"><B>Изменить Ответственного</B></DIV>
	<form action="/content/core/core.client.php" method="post" enctype="multipart/form-data" name="clientForm" id="clientForm">
		<input type="hidden" id="action" name="action" value="client.change.user">
		<input name="clid" type="hidden" id="clid" value="<?= $clid ?>">
		<input name="pid" type="hidden" id="pid" value="<?= $pid ?>">

		<DIV id="formtabs" class="box--child" style="overflow-y: auto; max-height:80vh;">

			<div class="flex-container mb10 mt20">

				<div class="flex-string wp20 gray2 fs-12 pt7 right-text">Ответственный:</div>
				<div class="flex-string wp80 pl10">
					<?php
					//$exclude = ($iduser1 != $iduser) ? 0 : $iduser1;
					$element = new Elements();
					print $element -> UsersSelect("newuser", [
						"class"   => "required wp97",
						"active"  => true,
						"sel"     => "-1",
						"exclude" => $iduser
					]);
					?>
				</div>

			</div>

			<hr>

			<div class="flex-container mb10">

				<div class="flex-string wp20 gray2 fs-12 pt10 right-text">Опции:</div>
				<div class="flex-string box--child wp80 pl10">

					<div class="flex-container wp99">

						<?php
						if ($clid > 0) {
							?>
							<div class="flex-string checkbox inline viewdiv mb5 mr10">
								<label>
									<input type="checkbox" name="person_send" id="person_send" value="yes" checked>
									<span class="custom-checkbox"><i class="icon-ok"></i></span>
									<span class="pl10">передать Контакты</span>
								</label>
							</div>
							<?php
						}
						?>

						<div class="flex-string checkbox inline viewdiv mb5 mr10">
							<label>
								<input type="checkbox" name="dog_send" id="dog_send" value="yes" checked>
								<span class="custom-checkbox"><i class="icon-ok"></i></span>
								<span class="pl10">передать Сделки</span>
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

		<div class="button--pane text-right">
			<A href="javascript:void(0)" onclick="$('#clientForm').trigger('submit')" class="button">Сохранить</A>&nbsp;
			<A href="javascript:void(0)" onclick="DClose()" class="button">Отмена</A>
		</div>
	</FORM>
	<?php
}
if ($action == "change.priceLevel") {

	$current = getClientData($clid, "priceLevel");
	?>
	<DIV class="zagolovok"><B>Изменить Уровень цен:</B></DIV>
	<form action="/content/core/core.client.php" method="post" enctype="multipart/form-data" name="clientForm" id="clientForm">
		<input type="hidden" id="action" name="action" value="client.change.priceLevel">
		<input name="clid" type="hidden" id="clid" value="<?= $clid ?>">
		<input name="oldLevel" type="hidden" id="oldLevel" value="<?= $current ?>">

		<div class="p10 box--child">

			<div class="flex-container mb10">

				<div class="flex-string wp20 pt7 gray2 fs-12 right-text">Новый уровень:</div>
				<div class="flex-string wp80 pl10">
					<SELECT name="priceLevel" id="priceLevel" class="required">
						<?php
						foreach ($fieldsNames['price'] as $name => $title) {

							if ($name == $current) {
								$s = 'selected';
							}
							else {
								$s = '';
							}

							print '<OPTION value="'.$name.'" '.$s.'>'.$title.'</OPTION>';

						}
						?>
					</SELECT>
				</div>

			</div>

			<hr>

			<div class="flex-container">

				<div class="flex-string wp20 gray2 fs-12 right-text">Причина изменения:</div>
				<div class="flex-string wp80 pl10">
					<textarea name="reason" rows="3" class="wp100" id="reason"></textarea>
				</div>

			</div>

		</div>

		<hr>

		<div class="button--pane text-right">
			<A href="javascript:void(0)" onclick="$('#clientForm').trigger('submit')" class="button">Сохранить</A>&nbsp;
			<A href="javascript:void(0)" onclick="DClose()" class="button">Отмена</A>
		</div>
	</FORM>
	<?php
}
if ($action == "change.relation") {

	$tip = getClientData($clid, 'tip_cmr');
	?>
	<DIV class="zagolovok"><B>Изменить Тип отношений:</B></DIV>
	<FORM action="/content/core/core.client.php" method="post" enctype="multipart/form-data" name="clientForm" id="clientForm" autocomplete="off">
		<input type="hidden" id="action" name="action" value="client.change.relation">
		<input name="clid" type="hidden" id="clid" value="<?= $clid ?>">

		<div class="p10 box--child">

			<div class="flex-container mb10">

				<div class="flex-string wp20 pt7 gray2 fs-12 right-text">Новый тип:</div>
				<div class="flex-string wp80 pl10">
					<select id="tip_cmr" name="tip_cmr" class="required" style="width: 99%;">
						<option value="">--Выбор--</option>
						<?php
						$result = $db -> query("SELECT * FROM {$sqlname}relations WHERE identity = '$identity' ORDER by title");
						while ($data = $db -> fetch($result)) {
							?>
							<option <?php if ($tip == $data['title']) { print "disabled"; } ?> value="<?= $data['title'] ?>"><?= $data['title'] ?><?php if ($tip == $data['title']) { print "(текущий)"; } ?></option>
							<?php
						}
						?>
					</select>
				</div>

			</div>

			<hr>

			<div class="flex-container">

				<div class="flex-string wp20 gray2 fs-12 right-text">Причина изменения:</div>
				<div class="flex-string wp80 pl10">
					<textarea name="reason" rows="3" class="wp100" id="reason"></textarea>
				</div>

			</div>

		</div>

		<hr>

		<div class="button--pane text-right">
			<A href="javascript:void(0)" onclick="$('#clientForm').trigger('submit')" class="button">Сохранить</A>&nbsp;
			<A href="javascript:void(0)" onclick="DClose()" class="button">Отмена</A>
		</div>
	</FORM>
	<?php
}

?>
<script type="text/javascript" src="/assets/js/app.form.js"></script>
<script>

	var action = $('#action').val();
	var formatPhone = '<?=$format_phone?>';
	var isAdmin = '<?=$isadmin?>';
	var alls, counts;

	if (!isMobile) {

		//исходная ширина окна
		$('#dialog').css('width', '800px');

		//специализированная ширина для действий
		if (in_array(action, ['client.add', 'client.edit'])) {

			var dwidth = $(document).width();
			var dialogWidth;
			var dialogHeight;

			if (dwidth < 945) {
				dialogWidth = '90%';
				dialogHeight = '95vh';
			} else {
				dialogWidth = '80%';
			}

			var hh = $('#dialog_container').actual('height') * 0.9;
			var hh2 = hh - $('.zagolovok').actual('outerHeight') - $('.button--pane').actual('outerHeight') - 20;

			$('.fmain').css({'width': '100%'});

			if (dwidth < 945) {
				$('#dialog').css({'width': dialogWidth, 'height': dialogHeight});
				$('#formtabs').css({'height': 'unset', 'max-height': hh2 + 30});
			} else {
				$('#formtabs').css({'height': 'unset', 'max-height': hh2});
			}

			if (in_array(action, ['client.change.user', 'client.change.relation', 'client.change.desсription', 'client.change.dostup', 'client.change.priceLevel'])) {
				$('#dialog').css('width', '600px');
			}

		}

		$(".multiselect").multiselect({sortable: true, searchable: true});

	} else {

		var h2 = $(window).height() - $('.zagolovok').actual('outerHeight') - $('.button--pane').actual('outerHeight') - 50;
		$('#formtabs').css({'max-height': h2 + 'px', 'height': h2 + 'px'});

		$(".multiselect").addClass('wp95 h0');

	}

	$(document)
		.off('change', '.typeselect')
		.on('change', '.typeselect', function(){

		let tip = $(this).val();

		if( tip === 'person' ){
			tip = 'client';
		}

		$('.fxmain').find('.flex-container').each(function(){

			var xtype = $(this).data('xtype');

			$(this).removeClass('hidden');

			if(xtype !== '' && xtype !== undefined) {

				if (xtype !== tip) {
					$(this).addClass('hidden');
				}
				else if (xtype === tip) {
					$(this).removeClass('hidden');
				}

			}

		});

	})

	if ($('#allSelected').is('input')) {

		alls = parseInt($('#allSelected').val());
		counts = alls;

		if (alls > 1000)
			counts = 1000;

		$('#alls').html(alls);
		$('#counts').html(counts);

	}

	$(function () {

		$('.typeselect').trigger('change')

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

		$(document).on('change', '#type', function () {

			var type = $(this).val();

			if (type === 'person') {
				$('#title').attr('placeholder', 'Например: Иванов Иван');
				$('.placeholder').html('<b>Например:</b> Иванов Иван');
			} else {
				$('#title').attr('placeholder', 'Например: Сейлзмен, ООО');
				$('.placeholder').html('<b>Например:</b> Сейлзмен, ООО');
			}

		})

		//Формат номеров телефонов
		if (formatPhone !== '') reloadMasks();

		if ($("#title").is('input')) $("#title").trigger('focus');

		if ($('#type').is('input')) {

			var type = $('#type').val();

			if (type !== 'person' && type !== 'udefined') {

				$("#recv\\[castDirStatusSig\\]").autocomplete("/content/helpers/client.helpers.php?action=recvisites&tip=appointment&char=0", {
					autofill: true,
					minChars: 0,
					cacheLength: 0,
					maxItemsToShow: 20,
					selectFirst: false,
					multiple: false,
					delay: 10,
					matchSubset: 1
				});
				$("#recv\\[castDirStatus\\]").autocomplete("/content/helpers/client.helpers.php?action=recvisites&tip=appointment&char=1", {
					autofill: true,
					minChars: 0,
					cacheLength: 0,
					maxItemsToShow: 20,
					selectFirst: false,
					multiple: false,
					delay: 10,
					matchSubset: 1
				});
				$("#recv\\[castDirOsnovanie\\]").autocomplete("/content/helpers/client.helpers.php?action=recvisites&tip=osnovanie&char=0", {
					autofill: true,
					minChars: 0,
					cacheLength: 0,
					maxItemsToShow: 20,
					selectFirst: false,
					multiple: false,
					delay: 10,
					matchSubset: 1
				});

			} else {

				$("#recv\\[castBank\\]").datepicker({
					dateFormat: 'dd.mm.yy',
					firstDay: 1,
					dayNamesMin: ['Вс', 'Пн', 'Вт', 'Ср', 'Чт', 'Пт', 'Сб'],
					monthNamesShort: ['Январь', 'Февраль', 'Март', 'Апрель', 'Май', 'Июнь', 'Июль', 'Август', 'Сентябрь', 'Октябрь', 'Ноябрь', 'Декабрь'],
					changeMonth: true,
					changeYear: true,
					yearRange: '1930:2030'
				});
				$("#recv\\[castDirStatus\\]").datepicker({
					dateFormat: 'dd.mm.yy',
					firstDay: 1,
					dayNamesMin: ['Вс', 'Пн', 'Вт', 'Ср', 'Чт', 'Пт', 'Сб'],
					monthNamesShort: ['Январь', 'Февраль', 'Март', 'Апрель', 'Май', 'Июнь', 'Июль', 'Август', 'Сентябрь', 'Октябрь', 'Ноябрь', 'Декабрь'],
					changeMonth: true,
					changeYear: true,
					yearRange: '1930:2030'
				});
				$("#recv\\[castDirOsnovanie\\]").datepicker({
					dateFormat: 'dd.mm.yy',
					firstDay: 1,
					dayNamesMin: ['Вс', 'Пн', 'Вт', 'Ср', 'Чт', 'Пт', 'Сб'],
					monthNamesShort: ['Январь', 'Февраль', 'Март', 'Апрель', 'Май', 'Июнь', 'Июль', 'Август', 'Сентябрь', 'Октябрь', 'Ноябрь', 'Декабрь'],
					changeMonth: true,
					changeYear: true,
					yearRange: '1930:2030'
				});

			}

		}

		if (!isMobile) {

			$("#datum_task").datepicker({
				dateFormat: 'yy-mm-dd',
				firstDay: 1,
				dayNamesMin: ['Вс', 'Пн', 'Вт', 'Ср', 'Чт', 'Пт', 'Сб'],
				monthNamesShort: ['Январь', 'Февраль', 'Март', 'Апрель', 'Май', 'Июнь', 'Июль', 'Август', 'Сентябрь', 'Октябрь', 'Ноябрь', 'Декабрь'],
				changeMonth: true,
				changeYear: true,
				yearRange: "1940:2020",
				minDate: new Date(1940, 1 - 1, 1)
			});
			$('#totime_task').ptTimeSelect();

		}

		$('#dialog').center();

		//getOtrasli();

		doLoadCallback('clientForm');

		ShowModal.fire({
			etype: 'clientForm',
			action: action
		});

	});

	$('#clientForm').ajaxForm({
		dataType: 'json',
		beforeSubmit: function () {

			var $out = $('#message');
			var em = checkRequired();

			if (em === false) return false;

			$out.empty().fadeTo(1, 1).css('display', 'block').append('<div id="loader"><img src="/assets/images/loader.gif"> Загрузка данных. Пожалуйста подождите...</div>');

			$('#dialog').css('display', 'none');
			$('#dialog_container').css('display', 'none');

			return true;

		},
		success: function (data) {

			var errors;
			var clid = parseInt($('#clid').val());

			$('#dialog_container').css('display', 'none');
			$('#dialog').css('display', 'none');

			if (data.clid > 0 && clid === 0) {
				window.open('card.client?clid=' + data.clid);
			}

			errors = (data.error && data.error !== 'undefined' && data.error !== '') ? '<br>Note: ' + data.error : '';

			$('#message').fadeTo(1, 1).css('display', 'block').html('Результат: ' + data.result + errors);
			setTimeout(function () {
				$('#message').fadeTo(1000, 0);
			}, 20000);

			<?php //мы в карточке ?>
			if (isCard) {

				var card = $('#card').val();
				var did = $('#ctitle #did').val();

				//clid = $('#ctitle #clid').val();

				if (card === 'client') {

					settab('0', false);
					cardload();

				}
				if (card === 'dogovor') {

					$('#credit_' + did).load('/content/card/card.credit.php?did=' + did);
					cardload();

				}

				if ($('tab15').is('div'))
					settab('15', false);

				if (action === 'client.change.relation')
					$cardsf.getDostup();

			}

			<?php //смена типа отношений ?>
			if (action === 'client.change.dostup') {

				$cardsf.getDostup();

			} else {

				//if ($('#clients').is('div'))
				//$('#clients').load('/content/desktop/clients.php').append('<img src="/assets/images/loading.gif">');

				if ($display === 'desktop') {
					$desktop.clients();
				}

				<?php //мы в списке ?>
				if (typeof configpage == 'function')
					configpage();

				if (typeof loadMes == 'function')
					loadMes();

			}

		}
	});

	function reloadMasks() {

		//Формат номеров телефонов
		if (formatPhone !== '') {

			$('.phone').each(function () {

				$(this).phoneFormater(formatPhone);

			});

		}

	}

	/**
	 * @deprecated Вывод существующих Организации/Персоны
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
		var $elm = $('#' + formelement);

		if ($elm.val().length >= 3) {

			atop = $elm.position().top + 30;
			aleft = $elm.position().left - 5;
			awidth = $elm.width();
			title = urlEncodeData($elm.val());

			if ($('#ospisok').is('div') === false) {

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


				$('#ospisok').empty().css("left", aleft + "px").css("top", atop + "px").css('width', awidth).append('<div class="header fs-12"><b>Похожие записи (возможные дубли):</b></div><div class="zbody">' + string + '</div>').css('display', 'block');

			}, "json");


			return false;
		}

	}

	function validateINN() {

		var clid = $('#clid').val();

		$('#message').empty().fadeTo(1, 1).css('display', 'block').append('<div id=loader><img src=/assets/images/loader.gif> Загрузка данных. Пожалуйста подождите...</div>');

		if ($('input.isinn').is('input') && $('input.iskpp').is('input')) {

			var inn = $('input.isinn').val();
			var kpp = $('input.iskpp').val();
			var inn_pole = $('input.isinn').attr('id');
			var kpp_pole = $('input.iskpp').attr('id');

			$.get('/content/helpers/client.helpers.php?action=valinn&clidd=' + clid + '&inn_pole=' + inn_pole + '&kpp_pole=' + kpp_pole + '&inn=' + inn + '&kpp=' + kpp, function (data) {

				if (data && data.clid !== '') {

					$('input.isinn').css({color: "#FFF", background: "#FF8080"});
					$('input.iskpp').css({color: "#FFF", background: "#FF8080"});

					$('#message').fadeTo(1000, 0);
					alert('Организация с такими ИНН/КПП есть в базе:\n' + data.title);

					return false;

				} else {

					$('#clientForm').trigger('submit');
					DClose();

				}

			}, "json");

		} else $('#clientForm').trigger('submit');

	}

	function gettags() {

		var tip = urlEncodeData($('#tip option:selected').val());
		$('#tagbox').load('/content/core/core.tasks.php?action=tags&tip=' + tip);

	}

	function tagit(id) {

		var html = $('#tag_' + id).html();
		insTextAtCursor('des', html + '; ');

	}

	// переключатель для групповых действий
	function showd() {

		var cel = $('#doAction:checked').val();
		var u = 0;

		if (cel === 'userChange') {

			$('#userdiv').removeClass('hidden');
			$('#dostupdiv').addClass('hidden');
			$('#terrdiv').addClass('hidden');
			$('#cmrdiv').addClass('hidden');
			$('#appendix').removeClass('hidden');
			$('#reazon').removeClass('hidden');
			$('#grpt').addClass('hidden');
			$('#garbage').addClass('hidden');

		} else if (cel === 'dostupChange' || cel === 'dostupDelete') {

			$('#dostupdiv').removeClass('hidden');
			$('#userdiv').addClass('hidden');
			$('#terrdiv').addClass('hidden');
			$('#cmrdiv').addClass('hidden');
			$('#appendix').addClass('hidden');
			$('#reazon').addClass('hidden');
			$('#grpt').addClass('hidden');
			$('#garbage').addClass('hidden');

			if (cel === 'dostupDelete') {

				u = $('#dostup').val();

				$('#duser').val(u);

			} else {
				$('#duser').val(0);
			}

			if (isAdmin === 'on') {

				$('#counts').html(alls);

			}

		} else if (cel === 'terChange') {

			$('#terrdiv').removeClass('hidden');
			$('#userdiv').addClass('hidden');
			$('#dostupdiv').addClass('hidden');
			$('#cmrdiv').addClass('hidden');
			$('#appendix').addClass('hidden');
			$('#reazon').addClass('hidden');
			$('#grpt').addClass('hidden');
			$('#garbage').addClass('hidden');

			if (isAdmin === 'on') {

				$('#counts').html(alls);

			}

		} else if (cel === 'cmrChange') {

			$('#cmrdiv').removeClass('hidden');
			$('#userdiv').addClass('hidden');
			$('#dostupdiv').addClass('hidden');
			$('#terrdiv').addClass('hidden');
			$('#appendix').addClass('hidden');
			$('#reazon').addClass('hidden');
			$('#grpt').addClass('hidden');
			$('#garbage').addClass('hidden');

			if (isAdmin === 'on') {

				$('#counts').html(alls);

			}

		} else if (cel === 'groupChange') {

			$('#grpt').removeClass('hidden');
			$('#cmrdiv').addClass('hidden');
			$('#userdiv').addClass('hidden');
			$('#dostupdiv').addClass('hidden');
			$('#terrdiv').addClass('hidden');
			$('#appendix').addClass('hidden');
			$('#reazon').addClass('hidden');
			$('#garbage').addClass('hidden');

			if (isAdmin === 'on') {

				if (alls > 1000)
					counts = 1000;

				$('#counts').html(counts);

			}

		} else if (cel === 'clientDelete') {

			$('#userdiv').addClass('hidden');
			$('#dostupdiv').addClass('hidden');
			$('#terrdiv').addClass('hidden');
			$('#cmrdiv').addClass('hidden');
			$('#appendix').removeClass('hidden');
			$('#dltt').removeClass('hidden');
			$('#reazon').addClass('hidden');
			$('#grpt').addClass('hidden');
			$('#garbage').addClass('hidden');

			if (isAdmin === 'on') {

				if (alls > 1000)
					counts = 1000;

				$('#counts').html(counts);

			}

		} else if (cel === 'clientTrash') {

			$('#userdiv').addClass('hidden');
			$('#dostupdiv').addClass('hidden');
			$('#terrdiv').addClass('hidden');
			$('#cmrdiv').addClass('hidden');
			$('#appendix').addClass('hidden');
			$('#dltt').addClass('hidden');
			$('#reazon').removeClass('hidden');
			$('#grpt').addClass('hidden');
			$('#garbage').removeClass('hidden');

			if (isAdmin === 'on') {

				if (alls > 1000)
					counts = 1000;

				$('#counts').html(counts);

			}

		} else {

			$('#userdiv').addClass('hidden');
			$('#dostupdiv').addClass('hidden');
			$('#terrdiv').addClass('hidden');
			$('#cmrdiv').addClass('hidden');
			$('#appendix').addClass('hidden');
			$('#reazon').addClass('hidden');
			$('#grpt').addClass('hidden');
			$('#dltt').addClass('hidden');
			$('#garbage').addClass('hidden');

			if (isAdmin === 'on') {

				if (alls > 1000)
					counts = 1000;

				$('#counts').html(counts);

			}

		}

		$('#dialog').center();

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

			var str = $('#clientForm').serialize() + '&' + $('#pageform').serialize();
			var url = "/content/core/core.client.php";

			$('#message').empty().fadeTo(1, 1).css('display', 'block').append('<div id="loader"><img src="/assets/images/loader.gif"> Загрузка данных...</div>');

			$.post(url, str, function (data) {

				$('#resultdiv').empty();

				configpage();

				$('#message').fadeTo(1, 1).css('display', 'block').html(data.result);
				setTimeout(function () {
					$('#message').fadeTo(1000, 0);
				}, 20000);

			}, 'json')
				.fail(function (xhr, status, error) {

					$('#message').fadeTo(1, 1).css('display', 'block').html(status);
					//console.log(status)

				});
		}

	}

</script>