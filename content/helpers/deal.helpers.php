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
use Salesman\Elements;

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

$action = $_REQUEST['action'];
$did    = (int)$_REQUEST['did'];
$clid   = (int)$_REQUEST['clid'];

//Данные для редактирования полей сделки
$fldtip  = $_REQUEST['fldtip']; //тип элемента
$fldvals = $_REQUEST['fldvals']; //поле таблицы

//для добавления поля
$fldnewName = $_REQUEST['newfield'];
$fldnew     = $_REQUEST['field'];

//===============
$dname  = [];
$result = $db -> query( "SELECT * FROM ".$sqlname."field WHERE fld_tip='dogovor' AND fld_on='yes' and identity = '$identity' ORDER BY fld_order" );
while ($data = $db -> fetch( $result )) {
	$dname[$data['fld_name']] = $data['fld_title'];
}

//Найдем тип сделки, которая является Сервисной
$tid = $db -> getCol( "SELECT tid FROM ".$sqlname."dogtips WHERE (title LIKE '%месячный%' or title LIKE '%сервис%' or title LIKE '%абонент%') and identity = '$identity'" );

$tip = $db -> getOne( "SELECT tip FROM ".$sqlname."dogovor WHERE did='".$did."' and identity = '$identity'" );

//проверим, если договор является Сервисным
if (in_array( $tip, (array)$tid )) {
	$isper = 'yes';
}

//восстановим поля формы
$result = $db -> query( "select * from ".$sqlname."field where fld_tip='dogovor' and fld_on='yes' and identity = '$identity'" );
while ($data = $db -> fetch( $result )) {
	$fields[]                    = $data['fld_name'];
	$fName[$data['fld_name']]    = $data['fld_title'];
	$required[$data['fld_name']] = $data['required'];
}
//===============

if ($action == 'dostup') {
	
	$result = $db -> query( "SELECT * FROM ".$sqlname."dostup WHERE did = '".$did."' and identity = '$identity'" );
	while ($data = $db -> fetch( $result )) {
		
		$s = '';
		
		if ($data['subscribe'] == 'on') {
			$s = '<i class="icon-mail sup red" title="Подписан на уведомления"></i>';
		}
		
		print '<div class="inline mr5 mb5 p10 bluebg-sub"><i class="icon-user-1 blue">'.$s.'</i>&nbsp;&nbsp;'.current_user( $data['iduser'] ).'</div>';
		
	}
	if ($db -> affectedRows() < 1) {
		print '<div class="p5 gray2">Доступ не предоставлялся</div>';
	}
	
	exit();
	
}

if ($action == 'get.list') {
	
	$q = trim( $_GET["q"] );
	
	if (!$q) {
		exit();
	}
	
	$qq = yexplode( " ", (string)$q );
	foreach ($qq as $g) {
		$word .= "'%".$g."%' ";
	}
	
	$word = trim( $word );
	$word = str_replace( " ", " or ", $word );
	
	$result = $db -> query( "select DISTINCT title from ".$sqlname."dogovor WHERE title LIKE $word and identity = '$identity' ORDER by title" );
	while ($data = $db -> fetch( $result )) {
		echo $data['title']."\n";
	}
	
	exit();
	
}

if ($action == 'get.persons') {//getPersons
	
	if (in_array( 'pid_list', (array)$fields )) {
		?>
		<div class="divider"><b>Присоединить Контакты</b></div>
		<select name="pid_list[]" multiple="multiple" class="multiselect" id="pid_list" style="width: 50%;">
			<?php
			$result = $db -> query( "SELECT * FROM ".$sqlname."personcat where clid='".$clid."' and identity = '$identity'" );
			while ($data = $db -> fetch( $result )) {
				?>
				<option value="<?= $data['pid'] ?>"><?= $data['person'] ?></option>
			<?php } ?>
		</select>
		<script>
			$(function () {
				$(".multiselect").multiselect({sortable: true, searchable: true});
				$(".connected-list").css('height', "200px");
			});
		</script>
		<?php
	}
	
	exit();
	
}

if ($action == 'get.personsplus') {
	
	$payer = (int)$_REQUEST['payer'];
	$plist = yexplode( ",", (string)$_REQUEST['plist'] );
	
	$str = [];
	
	if ($payer > 0)
		$str[] = $payer;
	if ($clid > 0)
		$str[] = $clid;
	
	$str = yimplode( ",", $str );
	
	$s = '';
	
	if ($str != '') {
		
		$result = $db -> query( "SELECT * FROM ".$sqlname."personcat where clid IN ($str) and identity = '$identity'" );
		while ($data = $db -> fetch( $result )) {
			
			$d = (in_array( $data['pid'], (array)$plist )) ? "selected" : "";
			$f = ($data['clid'] == $clid) ? " [заказчик]" : " [плательщик]";
			
			$s .= '<option value="'.$data['pid'].'" '.$d.'>'.$data['person'].$f.'</option>';
			
		}
		
	}
	
	print $s;
	
	exit();
	
}

if ($action == 'get.contracts') {
	
	$pid = (int)$_REQUEST['pid'];
	
	$idtype = $db -> getOne( "SELECT id FROM ".$sqlname."contract_type WHERE type = 'get_dogovor' and identity = '$identity'" );
	
	if ($clid > 0)
		$query = "SELECT * FROM ".$sqlname."contract WHERE clid='".$clid."' and idtype = '".$idtype."' and identity = '$identity'";
	if ($pid > 0)
		$query = "SELECT * FROM ".$sqlname."contract WHERE pid='".$pid."' and idtype = '".$idtype."' and identity = '$identity'";
	
	print '<option value="none">--б/н--</option>';
	
	$result = $db -> query( $query );
	while ($data = $db -> fetch( $result )) {
		print '<option value="'.$data['deid'].'">'.$data['number'].'</option>';
	}
	
	exit();
	
}

if ($action == 'get.course') {
	
	$idcurrency = $_REQUEST['idcurrency'];
	
	$list = Currency ::currencyLog( $idcurrency, 10 );
	
	print json_encode_cyr( ["list" => $list] );
	
	exit();
	
}

if ($action == "doglist") {
	
	$q      = untag( texttosmall( $_REQUEST["q"] ) );
	$clid   = (int)untag( $_REQUEST["clid"] );
	$closed = $_REQUEST['closed'];
	
	if ($closed == 'yes')
		$d = " and close = 'yes'";
	
	if ($closed == 'no')
		$d = " and close != 'yes'";
	
	if ($clid < 1)
		$result = $db -> query( "SELECT LOWER(title) as title2, title, did, clid, pid, iduser FROM ".$sqlname."dogovor WHERE (title LIKE '%$q%' or did = '".$q."' or uid LIKE '%".$q."%' or clid IN (SELECT clid FROM ".$sqlname."clientcat WHERE title LIKE '%$q%' and identity = '$identity')) $d and identity = '$identity'" );
	else $result = $db -> query( "SELECT title, did, clid, pid, iduser FROM ".$sqlname."dogovor WHERE clid = '$clid' and close != 'yes' $d and identity = '$identity'" );
	
	while ($data = $db -> fetch( $result )) {
		
		if ($data['pid'] < 1)
			$data['pid'] = getClientData( $data['clid'], 'pid' );
		
		print $data['title']."|".$data['did']."|".$data['clid']."|".current_client( $data['clid'] )."|".$data['pid']."|".current_user( $data['iduser'] )."|".current_person( $data['pid'] )."\n";
	}
	
	exit();
}

if ($action == "getNalog") {
	
	$rs    = $_REQUEST['rs'];
	$mcid  = $_REQUEST['mcid'];
	$summa = $_REQUEST['summa'];
	
	$snalog = 0;
	
	if ($mcid > 0 && $rs < 1) {
		
		$result = $db -> getRow( "SELECT * FROM ".$sqlname."mycomps_recv WHERE cid = '".$mcid."' and isDefault = 'yes' and identity = '$identity'" );
		$rs     = $result["id"];
		$snalog = $result["ndsDefault"];
		
	}
	elseif ($rs > 0) {
		
		$result = $db -> getRow( "SELECT * FROM ".$sqlname."mycomps_recv WHERE id = '".$rs."' and identity = '$identity'" );
		$mcid   = $result["cid"];
		$snalog = $result["ndsDefault"];
		
	}
	
	$nalog = getNalog( $summa, $snalog, $ndsRaschet );
	
	print json_encode_cyr( $nalog );
	
	exit();
}

if ($action == "getFunnel") {
	
	$direction = (int)preg_replace( "/[^0-9]/", "", $_REQUEST['direction'] ) + 0;
	$tip       = (int)preg_replace( "/[^0-9]/", "", $_REQUEST['tip'] ) + 0;
	
	$steps = getMultiStepList( [
		"direction" => $direction,
		"tip"       => $tip
	] );
	
	print json_encode_cyr( $steps );
	
	exit();
	
}

/**
 * Вывод полей, которые необходимо заполнить на данном этапе сделки
 */
if ($action == "getStepFields") {
	
	$step   = $_REQUEST['step'];
	$idstep = $_REQUEST['idcategory'];
	$full   = $_REQUEST['full'] ?? false;
	//$did    = $_REQUEST[ 'did' ];
	
	//загрузим этапы для данной сделки
	$multisteps = getMultiStepList( ["did" => $did] );
	$multisteps = array_keys( $multisteps['steps'] );
	
	//print_r($multisteps);
	//print "<br>";
	
	if (!$step) {
		
		$step = $db -> getOne( "SELECT title FROM ".$sqlname."dogcategory WHERE idcategory = '$idstep' and identity = '$identity'" );
		
	}
	
	$fparams = json_decode( $db -> getOne( "SELECT params FROM ".$sqlname."customsettings WHERE tip = 'dfieldsstep' and identity = '$identity'" ), true );
	
	$ainputs = [];
	$a       = [];
	
	//для всех этапов, кроме закрытия
	if ($step != 'close') {
		
		//print_r($fparams);
		
		foreach ($fparams as $astep => $param) {
			
			$astep   = substr( $astep, 1 );
			$astepID = $db -> getOne( "SELECT idcategory FROM ".$sqlname."dogcategory WHERE title = '$astep' and identity = '$identity'" );
			
			//print $astepID."<br>";
			
			if ($astep <= $step && in_array( $astepID, (array)$multisteps )) {
				
				//$ainputs = array_merge( $ainputs, $param['inputs'] );
				
				foreach ($param['inputs'] as $k => $inpt) {
					
					$ainputs[$k] = $inpt;
					
				}
				
			}
			
		}
		
	}
	else {
		
		$ainputs = $fparams[$step]['inputs'];
		
	}
	
	//print_r($ainputs);
	
	$step = "s".$step;
	
	if (!empty( $ainputs )) {
		
		/*$inputs = array_map( function ($element) {
			return "'$element'";
		}, array_keys( $fparams[$step]['inputs'] ) );*/
		
		$inputs = array_map( static function ($element) {
			return "'$element'";
		}, array_keys( $ainputs ) );
		
		//print_r($inputs);
		
		$deal = get_dog_info( $did, "yes" );
		
		$string = '';
		
		$res = $db -> getAll( "select * from ".$sqlname."field where fld_tip='dogovor' and fld_name IN (".implode( ",", $inputs ).") and fld_on='yes' and identity = '$identity' order by fld_order" );
		foreach ($res as $da) {
			
			if ($da['fld_temp'] == "--Обычное--") {
				
				if ($deal[$da['fld_name']] == '' || $full)
					$string .= '<div class="flex-container mb10 mt20 box--child">

					<div class="flex-string wp20 gray2 fs-12 pt7 right-text">'.$da['fld_title'].':</div>
					<div class="flex-string wp80 pl10 relativ">
						<input type="text" name="dogovor['.$da['fld_name'].']" id="dogovor['.$da['fld_name'].']" class="wp97 '.$ainputs[$da['fld_name']].'" value="'.$deal[$da['fld_name']].'" placeholder="'.$da['fld_title'].'">
					</div>

				</div>';
				
			}
			elseif ($da['fld_temp'] == "adres") {
				
				if ($deal[$da['fld_name']] == '' || $full)
					$string .= '<div class="flex-container mb10 mt20 box--child">

					<div class="flex-string wp20 gray2 fs-12 pt7 right-text">'.$da['fld_title'].':</div>
					<div class="flex-string wp80 pl10 relativ">
						<input type="text" name="dogovor['.$da['fld_name'].']" id="dogovor['.$da['fld_name'].']" class="wp97 yaddress '.$ainputs[$da['fld_name']].'" value="'.$deal[$da['fld_name']].'" placeholder="'.$da['fld_title'].'" data-type="address">
					</div>

				</div>';
				
			}
			elseif ($da['fld_temp'] == "hidden") {
				?>
				<input id="<?= $da['fld_name'] ?>" name="<?= $da['fld_name'] ?>" type="hidden" value="<?= $fieldData ?>">
				<?php
			}
			elseif ($da['fld_temp'] == "textarea") {
				
				$fieldData = $deal[$da['fld_name']] != '' ? $deal[$da['fld_name']] : $da['fld_var'];
				$isDefault = strcasecmp( $da['fld_var'], $deal[$da['fld_name']] ) == 0;
				
				if ($deal[$da['fld_name']] == '' || $isDefault || $full)
					$string .= '<div class="flex-container mb10 mt20 box--child">

					<div class="flex-string wp20 gray2 fs-12 pt7 right-text">'.$da['fld_title'].':</div>
					<div class="flex-string wp80 pl10 relativ">
						<textarea name="dogovor['.$da['fld_name'].']" id="dogovor['.$da['fld_name'].']" class="wp97 '.$ainputs[$da['fld_name']].'" style="height: 150px;" placeholder="'.$da['fld_title'].'">'.str_replace( "<br>", "\n", $fieldData ).'</textarea>
					</div>

				</div>';
				
			}
			elseif ($da['fld_temp'] == "select") {
				
				$vars = explode( ",", $da['fld_var'] );
				
				$su = '';
				
				foreach ($vars as $var) {
					
					$s  = ($var == $deal[$da['fld_name']]) ? 'selected' : '';
					$su .= '<option value="'.$var.'" '.$s.'>'.$var.'</option>';
					
				}
				
				if ($deal[$da['fld_name']] == '' || $full)
					$string .= '<div class="flex-container mb10 mt20 box--child">

					<div class="flex-string wp20 gray2 fs-12 pt7 right-text">'.$da['fld_title'].':</div>
					<div class="flex-string wp80 pl10 relativ">
						<select name="dogovor['.$da['fld_name'].']" id="dogovor['.$da['fld_name'].']" class="wp97 '.$ainputs[$da['fld_name']].'">
							<option value="">--Выбор--</option>
							'.$su.'
						</select>
					</div>

				</div>';
				
			}
			elseif ($da['fld_temp'] == "multiselect") {
				
				$vars = explode( ",", $da['fld_var'] );
				
				$su = '';
				
				foreach ($vars as $var) {
					
					$s  = (in_array( $var, explode( ",", $deal[$da['fld_name']] ) )) ? 'selected' : '';
					$su .= '<option value="'.$var.'" '.$s.'>'.$var.'</option>';
					
				}
				
				if ($deal[$da['fld_name']] == '' || $full)
					$string .= '<div class="divider text-center">'.$da['fld_title'].'</div>

				<div class="flex-container mb10 mt20 box--child '.($ainputs[$da['fld_name']] == 'required' ? 'multireq' : '').'">

					<div class="flex-string wp100 pl10">
						<select name="dogovor['.$da['fld_name'].'][]" id="dogovor['.$da['fld_name'].'][]" multiple="multiple" class="multiselect '.$ainputs[$da['fld_name']].'">
							'.$su.'
						</select>
					</div>

				</div>

				<hr>';
				
			}
			elseif ($da['fld_temp'] == "inputlist") {
				
				$vars = $da['fld_var'];
				
				if ($deal[$da['fld_name']] == '' || $full)
					$string .= '<div class="flex-container mb10 mt20 box--child">

					<div class="flex-string wp20 gray2 fs-12 pt7 right-text">'.$da['fld_title'].':</div>
					<div class="flex-string wp80 pl10 relativ">
						<input type="text" name="dogovor['.$da['fld_name'].']" id="dogovor['.$da['fld_name'].']" class="wp97 '.$ainputs[$da['fld_name']].'" value="'.$deal[$da['fld_name']].'" placeholder="'.$da['fld_title'].'">
						<div class="smalltxt blue"><em>Двойной клик мышкой для показа вариантов</em></div>
						<script>
							var str = \''.$vars.'\';
							var data = str.split(\',\');
							$("#dogovor\\\['.$da['fld_name'].'\\\]").autocomplete(data, {autofill: true, minLength:0, minChars: 0, cacheLength: 5, maxItemsToShow:20, selectFirst: true, multiple: false,  delay: 0, matchSubset: 2});
						</script>
					</div>

				</div>';
				
			}
			elseif ($da['fld_temp'] == "radio") {
				
				$vars = explode( ",", $da['fld_var'] );
				
				$su = '';
				
				foreach ($vars as $var) {
					
					$s1 = ($var == $deal[$da['fld_name']]) ? 'checked' : '';
					
					$su .= '
					<div class="flex-string p10 mr5 mb5 flx-basis-20 viewdiv bgwhite inset">
						<div class="radio nowrap">
							<label class="nowrap">
								<input name="dogovor['.$da['fld_name'].']" type="radio" id="dogovor['.$da['fld_name'].']" '.$s1.' value="'.$var.'">
								<span class="custom-radio success1"><i class="icon-radio-check"></i></span>
								<span class="title">'.$var.'</span>
							</label>
						</div>
					</div>';
					
				}
				
				if ($deal[$da['fld_name']] == '' || $full)
					$string .= '
				<div class="flex-container mb10 mt20 box--child '.($ainputs[$da['fld_name']] == 'required' ? 'req' : '').'">

					<div class="flex-string wp20 gray2 fs-12 pt7 right-text">'.$da['fld_title'].':</div>
					<div class="flex-string wp80 pl10 relativ">
					
						<div class="flex-container box--child wp95--5">
							'.$su.'
							'.($ainputs[$da['fld_name']] != 'required' ? '<div class="flex-string p10 mr5 mb5 flx-basis-20 viewdiv bgwhite inset">
							
								<div class="radio">
									<label>
										<input name="'.$da['fld_name'].'" type="radio" id="'.$da['fld_name'].'" '.($deal[$da['fld_name']] == '' ? 'checked' : '').' value="">
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
				
				if ($deal[$da['fld_name']] == '' || $full)
					$string .= '<div class="flex-container mb10 mt20 box--child">

					<div class="flex-string wp20 gray2 fs-12 pt7 right-text">'.$da['fld_title'].':</div>
					<div class="flex-string wp80 pl10 relativ">
						<INPUT name="dogovor['.$da['fld_name'].']" type="text" id="dogovor['.$da['fld_name'].']" class="datum inputdate w140 '.$ainputs[$da['fld_name']].'" value="'.$deal[$da['fld_name']].'" autocomplete="off">
					</div>

				</div>';
				
			}
			elseif ($da['fld_temp'] == "datetime") {
				
				if ($deal[$da['fld_name']] == '' || $full)
					$string .= '
					<div class="flex-container mb10 mt20 box--child">
	
						<div class="flex-string wp20 gray2 fs-12 pt7 right-text">'.$da['fld_title'].':</div>
						<div class="flex-string wp80 pl10 relativ">
							<INPUT name="dogovor['.$da['fld_name'].']" type="text" id="dogovor['.$da['fld_name'].']" class="inputdatetime '.$ainputs[$da['fld_name']].' w140" value="'.$deal[$da['fld_name']].'" autocomplete="off" placeholder="'.$da['fld_title'].'">
						</div>
	
					</div>';
				
			}
			
		}
		
		if ($string != '') {
			
			print '
				<div class="divider mt20 mb20"><b class="red">Требуется заполнить следующие поля сделки</b></div>
				'.$string.'
				<div class="divider mt20 mb20">Требуется заполнить предыдущие поля сделки</div>
			';
			
		}
		
	}
	
	exit();
	
}

// Формирование блоков для индивидуального редактирования полей карточки сделки
if ($action == 'getFieldElement') {
	
	$deal = get_dog_info( $did, "yes" );
	
	$string = '';
	
	$systemFields = [
		'zayavka',
		'ztitle',
		'mcid',
		'iduser',
		'datum_plan',
		'period',
		'idcategory',
		'dog_num',
		'money',
		'pid_list',
		'payer',
		'kol',
		'kol_fact',
		'marg',
		'oborot'
	];
	
	$a = yimplode( ',', $systemFields, '"' );
	
	if ($fldvals != 'append') {
		$datas = $db -> getRow( "select * from ".$sqlname."field WHERE fld_tip='dogovor' AND fld_name='$fldvals' AND fld_on='yes' AND fld_temp != 'hidden' AND identity = '$identity'" );
	}
	else {
		$datas = $db -> getAll( "select * from ".$sqlname."field WHERE fld_tip='dogovor' AND fld_on='yes' AND fld_name NOT IN ($a) AND fld_temp != 'hidden' AND identity = '$identity'" );
	}
	
	if ($fldnew == "new") {
		
		$fieldType = $db -> getRow( "select * from ".$sqlname."field WHERE fld_tip='dogovor' AND fld_name='$fldnewName' AND identity = '$identity'" );
		
		if ($fieldType['fld_temp'] == '' || $fieldType['fld_temp'] == '--Обычное--') {
			$fieldType['fld_temp'] = 'text';
		}
		
		elseif ($fieldType['fld_temp'] == 'inputlist') {
			$fieldType['fld_temp'] = 'select';
		}
		
		if ($fieldType['fld_name'] == 'adres') {
			$fieldType['fld_temp'] = 'adres';
		}
		
		$fieldArray = [
			"name"  => $fieldType['fld_name'],
			"type"  => $fieldType['fld_temp'],
			"param" => "deal",
			"id"    => $did
		];
		
		print json_encode( $fieldArray );
		
	}
	else {
		
		if ($fldtip == "adres") {
			
			$Element = new Elements();
			$string  = $Element ::Adres( "value", $deal[$datas['fld_name']], [
				"class" => "wp100 yaddress",
				"other" => 'placeholder="Введите адрес"'
			] );
			
		}
		elseif ($fldtip == "textarea") {
			
			$text = str_replace( "<br>", "\n", $deal[$datas['fld_name']] );
			
			$Element = new Elements();
			$string  = $Element ::TextArea( "value", $text );
			
		}
		elseif ($fldtip == "select") { //список выбора
			
			$string  = '';
			$s       = '';
			$namefld = '';
			
			if ($fldvals == 'tip') { //тип сделки
				
				$res = $db -> query( "SELECT * FROM ".$sqlname."dogtips WHERE identity = '$identity' ORDER BY title" );
				$kol = $db -> affectedRows( $res );
				
				$values = [];
				
				foreach ($res as $data) {
					
					$values[] = [
						"id"    => $data['tid'],
						"title" => $data['title']
					];
					
					$req = 'yes';
					
				}
			}
			elseif ($fldvals == 'direction') { //направление сделки
				
				$res = $db -> query( "SELECT * FROM ".$sqlname."direction WHERE identity = '$identity' ORDER BY title" );
				$kol = $db -> affectedRows( $res );
				
				$values = [];
				
				foreach ($res as $data) {
					
					$values[] = [
						"id"    => $data['id'],
						"title" => $data['title']
					];
					
					$req = 'yes';
					
				}
			}
			elseif ($fldvals == "append") {
				
				foreach ($datas as $d) {
					
					if ($deal[$d['fld_name']] == '') {
						
						$values[] = [
							"id"    => $d['fld_name'],
							"title" => $d['fld_title']
						];
					}
					
				}
				
				$namefld = 'newfield';
				$other   = 'data-type="deal"';
				
			}
			else {
				
				$val = yexplode( ",", (string)$datas['fld_var'] );
				$kol = count( $val );
				
				foreach ($val as $data) {
					
					$values[] = [
						"id"    => $data,
						"title" => $data
					];
					
				}
			}
			
			if ($namefld == '')
				$namefld = "value";
			
			$Element = new Elements();
			$string  = $Element ::Select( $namefld, $values, [
				"sel"   => $deal[$datas['fld_name']],
				"req"   => $req,
				"other" => $other
			] );
			
		}
		elseif ($fldtip == "multiselect") {//множественный выбор
			
			$data = [];
			
			if ($fldvals == "coid1") { // для конкурентов
				
				$res = $db -> query( "SELECT * FROM ".$sqlname."clientcat WHERE type='concurent' AND identity = '$identity'" );
				$kol = $db -> affectedRows( $res );
				
				$data = [];
				$k    = 0;
				
				foreach ($res as $v) {
					
					$data[] = [
						"id"    => $v['clid'],
						"title" => $v['title']
					];
					
				}
				
				$val = yexplode( ";", (string)$deal[$fldvals] );
				
			}
			else {
				
				$vars = explode( ",", $datas['fld_var'] );
				
				foreach ($vars as $v) {
					
					$data[] = [
						"id"    => $v,
						"title" => $v
					];
					
				}
				
				$val = yexplode( ",", (string)$deal[$datas['fld_name']] );
				
			}
			
			$Element = new Elements();
			$string  = $Element ::MultiSelect( "value", $data, [
				"sel"  => $val,
				"req"  => 'yes',
				"func" => 'saveField(\'deal\')'
			] );
			
		}
		elseif ($fldtip == "radio") {
			
			$vars = explode( ",", $datas['fld_var'] );
			
			$Element = new Elements();
			$string  = $Element ::Radio( "value", $vars, ["sel" => $deal[$datas['fld_name']]] );
			
		}
		elseif ($fldtip == "datum") {
			
			$Element = new Elements();
			$string  = $Element ::Date( "value", $deal[$datas['fld_name']], [
				"class" => "inputdate required wp100",
				"other" => 'autocomplete="off" placeholder="'.$datas['fld_title'].'"'
			] );
			
		}
		elseif ($fldtip == "datetime") {
			
			$Element = new Elements();
			$string  = $Element ::DateTime( "value", $deal[$datas['fld_name']], [
				"class" => "inputdatetime required wp100",
				"other" => 'autocomplete="off" placeholder="'.$datas['fld_title'].'"'
			] );
			
		}
		else {
			
			$Element = new Elements();
			$string  = $Element ::InputText( "value", $deal[$datas['fld_name']], [
				"class" => "wp100",
				"other" => 'placeholder="'.$datas['fld_title'].'"'
			] );
			
		}
		
		if ($string != '') {
			print $string;
		}
		
	}
	
	exit();
	
}

if ($action == 'columnOrderSave') {
	
	$fields = $_REQUEST;
	unset( $fields['action'] );
	
	$fields = array_flip( $fields );
	ksort( $fields );
	$fields = array_values( $fields );
	
	$names = [
		'dcreate'            => 'datum',
		'dplan'              => 'datum_plan',
		'user'               => 'iduser',
		'history'            => 'last_history',
		'last_history_descr' => 'last_history_descr',
		'credit'             => 'credit'
	];
	
	//Загрузка настроек колонок для текущего пользователя
	$f = $rootpath.'/cash/dogs_columns_'.$iduser1.'.txt';
	
	$file = (file_exists( $f )) ? $f : $rootpath.'/cash/dogs_columns_default.txt';
	
	$currentColumns = json_decode( file_get_contents( $file ), true );
	
	//формируем данные новых колонок ( активных )
	$columns = $exists = [];
	foreach ($fields as $field) {
		
		$key = (array_key_exists( $field, $names )) ? strtr( $field, $names ) : $field;
		
		$columns[$key] = [
			"name"  => $currentColumns[$key]['name'],
			"width" => $currentColumns[$key]['width'],
			"on"    => "yes"
		];
		
		$exists[] = $key;
		
	}
	
	//проходим все оставшиеся колонки (не активные) и добавляем в конец массива
	foreach ($currentColumns as $column => $value) {
		
		if (!in_array( $column, (array)$exists ))
			$columns[$column] = [
				"name"  => $value['name'],
				"width" => $value['width'],
				"on"    => ""
			];
		
	}
	
	file_put_contents( $f, json_encode_cyr( $columns ) );
	
	print "Сохранено";
	
	exit();
	
}