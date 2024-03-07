<?php
/* ============================ */

/*         SalesMan CRM         */
/* ============================ */
/* (C) 2016 Vladislav Andreev   */
/*       SalesMan Project       */
/*        www.isaler.ru         */
/*        ver. 2019.x           */
/* ============================ */

error_reporting( E_ERROR );
header( "Pragma: no-cache" );

$rootpath = dirname( __DIR__, 2 );

require_once $rootpath."/inc/config.php";
require_once $rootpath."/inc/dbconnector.php";
require_once $rootpath."/inc/auth.php";
require_once $rootpath."/inc/func.php";
require_once $rootpath."/inc/settings.php";
require_once $rootpath."/inc/language/".$language.".php";

$thisfile = basename( __FILE__ );

$xtype = [
	"client"     => "Клиент",
	"partner"    => "Партнер",
	"contractor" => "Поставщик",
	"concurent"  => "Конкурент",
];

$action = $_REQUEST['action'];

if ($action == 'save') {
	
	$name  = $_POST['name'];
	$width = $_POST['width'];
	$on    = $_POST['on'];
	$tip   = $_POST['tip'];
	$subtip   = $_POST['subtip'];

	$keys = array_keys( $name );
	
	if ($tip == 'clients') {

		$pole = [
			"date_create",
			"title",
			"pid",
			"tip_cmr",
			"idcategory",
			"iduser",
			"phone",
			"mail",
			"site",
			"territory",
			"clientpath",
			"last_dog",
			"last_history",
			"last_history_descr"
		];

		if(in_array($subtip, ['partner','contractor','concurent'])){
			$tip = $subtip."s";
		}

	}
	
	if ($tip == 'persons') {

		$pole = [
			"person",
			"ptitle",
			"tel",
			"mob",
			"mail",
			"loyalty",
			"role",
			"iduser",
			"des",
			"client",
			"last_history",
			"last_history_descr"
		];

	}
	
	if ($tip == 'dogs') {

		$pole = [
			"datum",
			"datum_plan",
			"title",
			"tip",
			"kol",
			"marg",
			"status",
			"iduser",
			"direction",
			"client",
			"adres",
			"dogs",
			"last_history",
			"last_history_descr"
		];

	}
	
	//формируем массив
	foreach ($keys as $key) {
		
		if ($on[$key] != 'yes') {
			$on[$key] = '';
		}
		
		$json[$key] = [
			"name"  => $name[$key],
			"width" => $width[$key],
			"on"    => $on[$key]
		];
		
	}
	
	file_put_contents( $rootpath."/cash/{$tip}_columns_{$iduser1}.txt", json_encode_cyr( $json ) );
	
	print "Сделано";
	
	exit();
	
}

if ($action == 'restore') {
	
	$tip = $_REQUEST['tip'];
	
	$file = $rootpath."/cash/{$tip}s_columns_{$iduser1}.txt";
	
	if (file_exists( $file )) {
		unlink($file);
	}
	
	print 'ok';
	
}

if ($action == 'client') {

	$hash = $_REQUEST['hash'];

	if(!in_array($hash, ['partner','contractor','concurent'])){
		$hash = "client";
	}
	
	$poledop = [];
	
	//считаем текущие настройки
	$file = $rootpath."/cash/{$hash}s_columns_{$iduser1}.txt";
	
	$file1 = file_exists( $file ) ? $file : $rootpath.'/cash/columns_default_client.json';
	
	$fc = json_decode( file_get_contents( $file1 ), true );
	
	$pole = $dpole = [];
	foreach ($fc as $key => $value) {
		$pole[]  = $key;
		$dpole[] = "'".$key."'";
	}

	//доп фильтр по типу записи
	$s = " AND (fld_sub IS NULL OR fld_sub = '$hash')";

	$xf = $db -> getIndCol( "fld_name", "SELECT fld_name, fld_sub FROM {$sqlname}field WHERE fld_tip = 'client' $s and identity = '$identity'" );

	//print_r($xf);
	?>
	<DIV class="zagolovok">Настройка колонок <?=$xtype[$hash]?></DIV>
	<FORM action="/content/helpers/column.editor.php" method="post" enctype="multipart/form-data" name="Form" id="Form">
		<INPUT type="hidden" name="action" id="action" value="save">
		<INPUT name="tip" type="hidden" id="tip" value="clients">
		<INPUT name="subtip" type="hidden" id="subtip" value="<?=$hash?>">

		<div style="max-height:80vh; overflow:auto">
			
			<table class="rowtable middle" id="table">
				<thead class="sticked--top disable--select">
				<tr class="header_contaner noDrag th30">
					<th class="w350"><b>Название</b></th>
					<th class="w80 text-center"><b>Ширина</b></th>
					<th class="w80 text-center">&nbsp;</th>
				</tr>
				</thead>
				<tbody>
				<?php
				$title_name      = $db -> getOne( "select fld_title from {$sqlname}field where fld_tip='client' and fld_name='title' and identity = '$identity'" );
				$pid_name        = $db -> getOne( "select fld_title from {$sqlname}field where fld_tip='client' and fld_name='pid' and identity = '$identity'" );
				$tip_cmr_name    = $db -> getOne( "select fld_title from {$sqlname}field where fld_tip='client' and fld_name='tip_cmr' and identity = '$identity'" );
				$cat_name        = $db -> getOne( "select fld_title from {$sqlname}field where fld_tip='client' and fld_name='idcategory' and identity = '$identity'" );
				$user_name       = $db -> getOne( "select fld_title from {$sqlname}field where fld_tip='client' and fld_name='iduser' and identity = '$identity'" );
				$phone_name      = $db -> getOne( "select fld_title from {$sqlname}field where fld_tip='client' and fld_name='phone' and identity = '$identity'" );
				$mail_name       = $db -> getOne( "select fld_title from {$sqlname}field where fld_tip='client' and fld_name='mail_url' and identity = '$identity'" );
				$site_name       = $db -> getOne( "select fld_title from {$sqlname}field where fld_tip='client' and fld_name='site_url' and identity = '$identity'" );
				$territory_name  = $db -> getOne( "select fld_title from {$sqlname}field where fld_tip='client' and fld_name='territory' and identity = '$identity'" );
				$clientpath_name = $db -> getOne( "select fld_title from {$sqlname}field where fld_tip='client' and fld_name='clientpath' and identity = '$identity'" );
				$address_name    = $db -> getOne( "select fld_title from {$sqlname}field where fld_tip='client' and fld_name='address' and identity = '$identity'" );
				
				$pole_name = $xpole = [
					"date_create"        => "Создание",
					"pid"                => $pid_name,
					"address"            => $address_name,
					"site"               => $site_name,
					"mail"               => $mail_name,
					"last_dog"           => "Дней с последней сделки",
					"last_history"       => "Последняя активность",
					"last_history_descr" => "Описание активности",
					"zakaz_kol"          => "Число заказов",
					"zakaz_sum"          => "Сумма заказов"
				];

				if(in_array($hash, ['partner','contractor','concurent'])){
					unset($xpole["last_dog"], $xpole["zakaz_kol"], $xpole["zakaz_sum"]);
					unset($pole["last_dog"], $pole["zakaz_kol"], $pole["zakaz_sum"]);
				}

				$roarray = [
					'tip_cmr',
					'act',
					'last_dog'
				];

				//отключенные поля
				$pole_exclude = ['last_hist'];
				
				//массив имен полей
				$re = $db -> query( "SELECT fld_title,fld_name,fld_on FROM {$sqlname}field WHERE fld_tip = 'client' $s and identity = '$identity'" );
				while ($data = $db -> fetch( $re )) {
					
					if ($data['fld_on'] == 'yes') {
						$pole_name[$data['fld_name']] = $data['fld_title'];
					}
					else {
						$pole_exclude[] = $data['fld_name'];
					}
					
				}
				
				//доп.поля, которые еще не включены выше
				$re = $db -> query( "SELECT fld_title,fld_name FROM {$sqlname}field WHERE fld_tip = 'client' $s and (fld_name LIKE '%input%' or fld_name IN (".implode( ",", $dpole ).")) and fld_on = 'yes' and identity = '$identity'" );
				while ($data = $db -> fetch( $re )) {
					
					//исключим из массима poledop все поля, которые уже есть в массиве pole_name
					if (!in_array( $data['fld_name'], $pole)) {
						$poledop[$data['fld_name']] = $data['fld_title'];
					}
					
				}
				
				if (!in_array( 'last_history_descr', $pole)) {
					$poledop['last_history_descr'] = 'Описание активности';
				}

				if (!in_array( 'last_history', $pole)) {
					$poledop['last_history'] = 'Последняя активность';
				}
				
				if (!in_array( 'zakaz_kol', $pole)) {
					$poledop['zakaz_kol'] = 'Число заказов';
				}
				
				if (!in_array( 'zakaz_sum', $pole)) {
					$poledop['zakaz_sum'] = 'Сумма заказов';
				}

				//print_r($xpole);
				
				foreach ($pole as $field) {

					//print "$field\n";
					
					$ro = in_array( $field, $roarray) ? 'readonly' : '';
					
					if ($field != 'act' && !in_array( $field, $pole_exclude)) {

						// отсекаем не типичные для данного типа записи поля
						if(!array_key_exists($field, $xf) && !array_key_exists($field, $xpole)){
							//print $field."\n";
							continue;
						}
						
						if ( (int)$fc[$field]['width'] == 0 && $field != 'title') {
							$fc[$field]['width'] = '100';
						}

						$yf = strtr( $field, $pole_name );
						?>
						<tr class="noDrag th30" data-id="<?= $field ?>">
							<td class="w350 Bold fs-11">
								<div class="drag-handler"></div>&nbsp;<input name="name[<?= $field ?>]" type="hidden" id="name[<?= $field ?>]" value="<?= $yf ?>"><?= strtr( $field, $pole_name ) ?>
							</td>
							<td class="80">
								<input name="width[<?= $field ?>]" type="number" min="0" step="5" id="width[<?= $field ?>]" value="<?= str_replace( "px", "", $fc[$field]['width'] ) ?>" <?= $ro ?> class="wp90 width">
							</td>
							<td class="80">
								<label for="on[<?= $field ?>]" class="switch">
									<input type="checkbox" name="on[<?= $field ?>]" id="on[<?= $field ?>]" value="yes" <?php if ($fc[$field]['on'] == 'yes') print "checked" ?>>
									<span class="slider"></span>
								</label>
							</td>
						</tr>
						<?php
					}

				}
				
				//доп.поля
				foreach ($poledop as $key => $val) {

					?>
					<tr class="noDrag th30" data-id="<?= $key ?>">
						<td class="w350 Bold fs-11">
							<div class="drag-handler"></div>&nbsp;<input name="name[<?= $key ?>]" type="hidden" id="name[<?= $key ?>]" value="<?= $val ?>"><?= $val ?>
						</td>
						<td class="80">
							<input name="width[<?= $key ?>]" type="number" min="0" step="5" id="width[<?= $key ?>]" value="100" class="wp90"/>
						</td>
						<td class="80">
							<label for="on[<?= $key ?>]" class="switch">
								<input type="checkbox" name="on[<?= $key ?>]" id="on[<?= $key ?>]" value="yes">
								<span class="slider"></span>
							</label>
						</td>
					</tr>
					<?php
				}
				?>
				</tbody>
			</table>
		
		</div>
		
		<hr>
		
		<DIV class="button--pane text-right">
			
			<div class="pull-left">
				<A href="javascript:void(0)" onclick="RestoreColumn()" class="redbtn button"><i class="icon-cancel-squared"></i>Сброс</A>
			</div>
			
			<A href="javascript:void(0)" onclick="$('#Form').submit()" class="button">Сохранить</A>&nbsp;
			<A href="javascript:void(0)" onclick="DClose()" class="button">Отмена</A>
		
		</DIV>
	
	</FORM>
	<?php
}

if ($action == 'person') {
	
	//считаем текущие настройки
	$file = $rootpath.'/cash/persons_columns_'.$iduser1.'.txt';
	
	if (file_exists( $file )) {
		$file1 = $file;
	}
	else {
		$file1 = $rootpath.'/cash/columns_default_person.json';
	}
	
	$fc = json_decode( file_get_contents( $file1 ), true );
	
	$pole    = [
		"person",
		"ptitle",
		"tel",
		"mob",
		"mail",
		"loyalty",
		"role",
		"iduser",
		"client",
		"last_history",
		"date_create"
	];
	$roarray = [
		'loyalty',
		'act',
		'last_dog'
	];
	
	if (in_array( $pole[$i], (array)$roarray )) {
		$ro = 'readonly';
	}
	else {
		$ro = '';
	}
	
	?>
	<DIV class="zagolovok">Настройка колонок</DIV>
	<FORM action="/content/helpers/column.editor.php" method="post" enctype="multipart/form-data" name="Form" id="Form">
		<INPUT type="hidden" name="action" id="action" value="save">
		<INPUT name="tip" type="hidden" id="tip" value="persons">
		
		<div style="max-height:80vh; overflow:auto">
			
			<table class="rowtable middle" id="table">
				<thead class="sticked--top">
				<tr class="header_contaner noDrag th30">
					<th class="w350"><b>Название</b></th>
					<th class="w80 text-center"><b>Ширина</b></th>
					<th class="w80 text-center">&nbsp;</th>
				</tr>
				</thead>
				<tbody>
				<?php
				$title_name   = $db -> getOne( "select fld_title from {$sqlname}field where fld_tip='person' and fld_name='person' and identity = '$identity'" );
				$pid_name     = $db -> getOne( "select fld_title from {$sqlname}field where fld_tip='person' and fld_name='ptitle' and identity = '$identity'" );
				$phone_name   = $db -> getOne( "select fld_title from {$sqlname}field where fld_tip='person' and fld_name='tel' and identity = '$identity'" );
				$mob_name     = $db -> getOne( "select fld_title from {$sqlname}field where fld_tip='person' and fld_name='mob' and identity = '$identity'" );
				$mail_name    = $db -> getOne( "select fld_title from {$sqlname}field where fld_tip='person' and fld_name='mail' and identity = '$identity'" );
				$loyalty_name = $db -> getOne( "select fld_title from {$sqlname}field where fld_tip='person' and fld_name='loyalty' and identity = '$identity'" );
				$rol_name     = $db -> getOne( "select fld_title from {$sqlname}field where fld_tip='person' and fld_name='rol' and identity = '$identity'" );
				$cat_name     = $db -> getOne( "select fld_title from {$sqlname}field where fld_tip='person' and fld_name='mob' and identity = '$identity'" );
				$user_name    = $db -> getOne( "select fld_title from {$sqlname}field where fld_tip='person' and fld_name='iduser' and identity = '$identity'" );
				$clid_name    = $db -> getOne( "select fld_title from {$sqlname}field where fld_tip='person' and fld_name='clid' and identity = '$identity'" );
				
				$pole_name = [
					"date_create"        => "Создан",
					"person"             => $title_name,
					"ptitle"             => $pid_name,
					"tel"                => $phone_name,
					"mob"                => $mob_name,
					"loyalty"            => $loyalty_name,
					"mail"               => $mail_name,
					"iduser"             => $user_name,
					//"client"             => $client_name,
					"role"               => $rol_name,
					"client"             => $clid_name,
					"last_dog"           => "Дней с последней сделки",
					"act"                => "Действия",
					"last_history"       => "Последняя активность",
					"last_history_descr" => "Описание активности"
				];
				
				$pole = [
					"date_create",
					"person",
					"ptitle",
					"tel",
					"mob",
					"mail",
					"loyalty",
					"role",
					"iduser",
					"client",
					"last_history",
					"last_history_descr"
				];
				
				for ($i = 0, $iMax = count( $pole ); $i < $iMax; $i++) {
					
					if ($fc[$pole[$i]]['width'] < 1 && $pole[$i] != 'person')
						$fc[$pole[$i]]['width'] = '100';
					?>
					<tr class="noDrag">
						<td class="w350 Bold fs-11">
							<div class="drag-handler"></div>&nbsp;<input name="name[<?= $pole[$i] ?>]" type="hidden" id="name[<?= $pole[$i] ?>]" value="<?= strtr( $pole[$i], $pole_name ) ?>"><?= strtr( $pole[$i], $pole_name ) ?>
						</td>
						<td class="w80">
							<input name="width[<?= $pole[$i] ?>]" type="text" id="width[<?= $pole[$i] ?>]" value="<?= str_replace( "px", "", $fc[$pole[$i]]['width'] ) ?>" <?= $ro ?> style="width:90%"/>
						</td>
						<td class="w80">
							<label for="on[<?= $pole[$i] ?>]" class="switch">
								<input type="checkbox" name="on[<?= $pole[$i] ?>]" id="on[<?= $pole[$i] ?>]" value="yes" <?php if ($fc[$pole[$i]]['on'] == 'yes')
									print "checked" ?>>
								<span class="slider"></span>
							</label>
						</td>
					</tr>
				<?php } ?>
				</tbody>
			</table>
		
		</div>
		
		<hr>
		
		<DIV class="button--pane text-right">
			
			<div class="pull-left">
				<A href="javascript:void(0)" onclick="RestoreColumn()" class="redbtn button"><i class="icon-cancel-squared"></i>Сброс</A>
			</div>
			
			<A href="javascript:void(0)" onclick="$('#Form').submit()" class="button">Сохранить</A>&nbsp;
			<A href="javascript:void(0)" onclick="DClose()" class="button">Отмена</A>
		
		</DIV>
	
	</FORM>
	<?php
}
if ($action == 'deal') {
	
	//считаем текущие настройки
	$file = $rootpath.'/cash/dogs_columns_'.$iduser1.'.txt';
	
	if (file_exists( $file )) {
		$file1 = $file;
	}
	else {
		$file1 = $rootpath.'/cash/columns_default_deal.json';
	}
	
	$fc   = json_decode( file_get_contents( $file1 ), true );
	$pole = [];
	
	foreach ($fc as $key => $value) {
		$pole[] = $key;
	}
	
	//print_r($pole);
	
	$dname  = [];
	$result = $db -> query( "SELECT * FROM {$sqlname}field WHERE fld_tip = 'dogovor' AND fld_on = 'yes' and identity = '$identity' ORDER BY fld_order" );
	while ($data = $db -> fetch( $result )) {
		
		$dname[$data['fld_name']] = $data['fld_title'];
		
	}
	
	//print_r($dname);
	
	//$poledop[ 'mcid' ] = 'Компания';
	
	//доп.поля, которые еще не включены выше
	$re = $db -> query( "select fld_title,fld_name from {$sqlname}field where fld_tip='dogovor' and (fld_name LIKE '%input%') and fld_on = 'yes' and identity = '$identity'" );
	while ($data = $db -> fetch( $re )) {
		
		//исключим из массима poledop все поля, которые уже есть в массиве pole_name
		if (!in_array( $data['fld_name'], (array)$pole )) {
			$poledop[$data['fld_name']] = $data['fld_title'];
		}
		
	}
	
	if (!in_array( 'credit', (array)$pole )) {
		$poledop['credit'] = 'Оплаты';
	}
	
	if (!in_array( 'last_history_descr', (array)$pole )) {
		$poledop['last_history_descr'] = 'Описание активности';
	}
	
	?>
	<DIV class="zagolovok">Настройка колонок</DIV>
	<FORM action="/content/helpers/column.editor.php" method="post" enctype="multipart/form-data" name="Form" id="Form">
		<INPUT type="hidden" name="action" id="action" value="save">
		<INPUT name="tip" type="hidden" id="tip" value="dogs">
		
		<div id="formtabs" style="max-height:80vh; overflow:auto">
			
			<table class="rowtable middle" id="table">
				<thead class="sticked--top">
				<tr class="header_contaner noDrag th30">
					<th class="w350"><b>Название</b></th>
					<th class="w80 text-center"><b>Ширина</b></th>
					<th class="w80 text-center">&nbsp;</th>
				</tr>
				</thead>
				<tbody>
				<?php
				//$pole = array("datum","datum_plan","title","tip","kol","marg","status","iduser","direction","client","adres","last_history");
				$roarray = [
					'status',
					'act',
					'last_dog'
				];
				for ($i = 0, $iMax = count( $pole ); $i < $iMax; $i++) {
					
					$ro = (in_array( $pole[$i], (array)$roarray )) ? 'readonly' : '';
					
					if ($pole[$i] == 'marga') {
						
						$pole[$i]               = 'marg';
						$fc[$pole[$i]]['width'] = $pole['marga']['width'];
						
					}
					
					if ($dname[$pole[$i]] == '') {
						
						$dname[$pole[$i]] = $fc[$pole[$i]]['name'];
						
					}
					
					if ($dname[$pole[$i]] != '') {
						
						if ($fc[$pole[$i]]['width'] < 1 && $pole[$i] != 'title')
							$fc[$pole[$i]]['width'] = '100';
						?>
						<tr class="noDrag">
							<td class="w350 Bold fs-11">
								<div class="drag-handler"></div>&nbsp;<input name="name[<?= $pole[$i] ?>]" type="hidden" id="name[<?= $pole[$i] ?>]" <?= $ro ?> value="<?= $dname[$pole[$i]] ?>"><?= $dname[$pole[$i]] ?>
							</td>
							<td class="w80">
								<input name="width[<?= $pole[$i] ?>]" type="number" step="5" id="width[<?= $pole[$i] ?>]" value="<?= str_replace( "px", "", $fc[$pole[$i]]['width'] ) ?>" <?= $ro ?> style="width:90%"/>
							</td>
							<td class="w80">
								<label for="on[<?= $pole[$i] ?>]" class="switch">
									<input type="checkbox" name="on[<?= $pole[$i] ?>]" id="on[<?= $pole[$i] ?>]" value="yes" <?php if ($fc[$pole[$i]]['on'] == 'yes')
										print "checked" ?>>
									<span class="slider"></span>
								</label>
							</td>
						</tr>
						<?php
					}
					
				}
				
				//доп.поля
				foreach ($poledop as $key => $val) {
					?>
					<tr class="noDrag th30">
						<td class="w350 Bold fs-11">
							<div class="drag-handler"></div>&nbsp;<input name="name[<?= $key ?>]" type="hidden" id="name[<?= $key ?>]" value="<?= $val ?>"><?= $val ?>
						</td>
						<td class="w80">
							<input name="width[<?= $key ?>]" type="number" step="5" id="width[<?= $key ?>]" value="100" style="width:90%"/>
						</td>
						<td class="w80">
							<label for="on[<?= $key ?>]" class="switch">
								<input type="checkbox" name="on[<?= $key ?>]" id="on[<?= $key ?>]" value="yes">
								<span class="slider"></span>
							</label>
						</td>
					</tr>
					<?php
				}
				?>
				</tbody>
			</table>
		</div>
		
		<DIV class="button--pane text-right">
			
			<div class="pull-left">
				<A href="javascript:void(0)" onclick="RestoreColumn()" class="redbtn button"><i class="icon-cancel-squared"></i>Сброс</A>
			</div>
			
			<A href="javascript:void(0)" onclick="$('#Form').submit()" class="button">Сохранить</A>&nbsp;
			<A href="javascript:void(0)" onclick="DClose()" class="button">Отмена</A>
		
		</DIV>
	</FORM>
	<?php
}
?>
<script>
	$(function () {

		$('#dialog').css('width', '800px');

		$('#Form').ajaxForm({
			beforeSubmit: function () {

				var $out = $('#message');
				var em = checkRequired();

				if (em === false) return false;

				$out.css('display', 'block').append('<div id=loader><img src=/assets/images/loader.gif> Загрузка данных. Пожалуйста подождите...</div>');
				return true;

			},
			success: function (data) {

				$('#message').fadeTo(1, 1).css('display', 'block').html(data);
				setTimeout(function () {
					$('#message').fadeTo(1000, 0);
				}, 20000);

				configpage();

				DClose();
			}
		});

		$('#dialog').center();


	});

	$("#table").tableDnD({
		indentArtifact: '<div class="drag-handler"></div>',
		onDragClass: "tableDrag",
		onDrop: function (table, row) {
		}
	});

	function RestoreColumn() {

		let tip = $('#tip').val();

		fetch("/content/helpers/column.editor.php?action=restore&tip=" + tip)
			.then(response => response.text())
			.then(function () {

				DClose();
				configpage();

			})
			.catch(error => {

				//console.log(error);

				Swal.fire({
					title: 'Ошибка',
					text: error,
					type: 'error',
					showCancelButton: true
				});

			});

	}

</script>