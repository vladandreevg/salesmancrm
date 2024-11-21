<?php
/* ============================ */
/*         SalesMan CRM         */
/* ============================ */
/* (C) 2016 Vladislav Andreev   */
/*       SalesMan Project       */
/*        www.isaler.ru         */
/*        ver. 2016.20          */

/* ============================ */

use Salesman\Price;
use Salesman\Storage;

error_reporting(E_ERROR);
header("Pragma: no-cache");

$rootpath = dirname(__DIR__, 2);

include $rootpath."/inc/config.php";
include $rootpath."/inc/dbconnector.php";
include $rootpath."/inc/auth.php";
include $rootpath."/inc/func.php";
include $rootpath."/inc/settings.php";
include $rootpath."/inc/language/".$language.".php";

$thisfile = basename(__FILE__);

//require_once "mcfunc.php";

$n_id   = $_REQUEST['n_id'];
$action = $_REQUEST['action'];

//настройки модуля
$settings            = $db -> getOne("SELECT settings FROM {$sqlname}modcatalog_set WHERE identity = '$identity'");
$settings            = json_decode($settings, true);
$settings['mcSklad'] = 'yes';

if ($settings['mcSkladPoz'] != "yes") {
	$pozzi = " and status != 'out'";
}

$id = $db -> getOne("select id from {$sqlname}modcatalog where prid='".$n_id."' and identity = '$identity'");

//---названия полей прайса. start---//
$dname  = [];
$fields = [];
$result = $db -> getAll("SELECT * FROM {$sqlname}field WHERE fld_tip='price' and identity = '$identity' ORDER BY fld_order");
foreach ($result as $data) {

	$dname[$data['fld_name']] = $data['fld_title'];
	$dvar[$data['fld_name']]  = $data['fld_var'];
	$don[]                    = $data['fld_name'];

	if($data['fld_name'] != 'price_in' && $data['fld_on'] == 'yes') {

		$fields[] = [
			"field" => $data['fld_name'],
			"title" => $data['fld_title'],
			"value" => $data['fld_var'],
		];

	}

}
//---названия полей прайса. end---//

$status = [
	'0' => 'Создана',
	'1' => 'В работе',
	'2' => 'Выполнена',
	'3' => 'Отменена'
];
$colors = [
	'0' => 'broun',
	'1' => 'blue',
	'2' => 'green',
	'3' => 'Отменена'
];

//Информация о товаре
if ($action == "getInfo") {

	$state  = [
		'0' => 'Нет в наличии',
		'1' => 'Можно купить',
		'2' => 'Приобретен',
		'3' => 'В наличии',
		'4' => 'Нет свободных'
	];
	$colors = [
		'0' => 'gray',
		'1' => 'broun',
		'2' => 'blue',
		'3' => 'green',
		'4' => 'red'
	];

	$price = Price::info($n_id)['data'];

	$result  = $db -> getRow("select * from {$sqlname}modcatalog where prid='".$n_id."' and identity = '$identity'");
	$content = htmlspecialchars_decode($result["content"]);
	$status  = $result["status"];
	$kol     = $result["kol"];
	$id      = $result["id"];
	$file    = $result["files"];
	$sklad   = $result["sklad"];

	$sum = $db -> getOne("SELECT SUM(summa) as sum FROM {$sqlname}modcatalog_dop WHERE prid = '".$n_id."' and identity = '$identity'");

	$kol_res = $db -> getOne("select SUM(kol) as kol from {$sqlname}modcatalog_reserv where prid='".$n_id."' and identity = '$identity'");

	$kol_zay = $db -> getOne("select SUM(kol) as kol from {$sqlname}modcatalog_zayavkapoz where prid='".$n_id."' and idz NOT IN (select idz from {$sqlname}modcatalog_zayavka where status IN (2, 3) and identity = '$identity') and identity = '$identity'");

	if ($kol == '') {
		$kol = '?';
	}
	?>
	<div class="viewdiv1 bgwhite">
		<table id="noborder">
			<tr>
				<td width="100" height="25" align="right">Обновлен:</td>
				<td><?=modifyDatetime($price['datum'], ["format" => "d.m.Y H:i"])?></td>
			</tr>
			<?php
			if ($settings['mcArtikul'] == 'yes') { ?>
				<tr>
					<td width="100" height="25" align="right">Артикул:</td>
					<td><?php print ( !empty($price['artikul']) ) ? '<b>'.$price['artikul'].'</b>' : "--//--" ?>
					</td>
				</tr>
			<?php
			} ?>
			<tr>
				<td height="25" align="right"><?= $dname['price_1'] ?>:</td>
				<td>
					<b class="green"><?= num_format($price['price_1']) ?></b> <?= $valuta ?>&nbsp;за&nbsp;<b><?= $price['edizm'] ?></b>&nbsp;, в т.ч.
					<b>НДС </b><?= num_format($price['nds']) ?>&nbsp;%<a href="javascript:void(0)" onclick="doLoad('/modules/modcatalog/form.modcatalog.php?n_id=<?= $n_id ?>&action=editone&tip=price');" title="Изменить" class="dright gray"><i class="icon-pencil"></i></a>
				</td>
			</tr>
			<?php
			if ($show_marga == 'yes' && $otherSettings['marga']) { ?>
				<tr>
					<td height="25" align="right"><?= $dname['price_in'] ?>:</td>
					<td><b class="red"><?= num_format($price['price_in']) ?></b> <?= $valuta ?></td>
				</tr>
			<?php
			} ?>
		</table>
	</div>
	<hr>
	<div class="viewdiv1 bgwhite">
		<table id="noborder">
			<tr>
				<td align="right" width="100" valign="top">Уровни цен:</td>
				<td>
					<div id="tagbox">
						<?php
						foreach ($fields as $field) {

							if ($field['field'] !== 'price_1') {
								print '
								<div class="tags">
									'.$field['title'].':&nbsp;<b class="red">'.num_format( $price[$field['field']] ).'</b> '.$valuta.'
								</div>
								';
							}

						}
						?>
					</div>
				</td>
			</tr>
			<?php
			$files = json_decode($file, true);

			if (!empty($files)) {
				?>
				<tr>
					<td colspan="2">
						<hr>
						<div>
							<?php
							foreach ($files as $file) {
								?>
								<div class="tumbs" style="background: url(<?= "/files/".$fpath.'modcatalog/'.$file['file'] ?>) top no-repeat; background-size:cover;">
									<a href="<?= "/files/".$fpath.'modcatalog/'.$file['file'] ?>" target="blank" title="В новом окне"><i class="icon-search gray icon-3x"></i></a>
								</div>
							<?php
							} ?>
						</div>
					</td>
				</tr>
			<?php
			} ?>
		</table>
	</div>
	<hr>
	<div style="display:inline-block; width:100%;">
		<DIV id="formtabs" style="border:0; background: none;">
			<UL style="background: none;">
				<LI><A href="#tab-form-1">Описание</A></LI>
				<LI><A href="#tab-form-2">Доп.информация</A></LI>
			</UL>
			<div id="tab-form-1" style="max-height: 450px; overflow-y: auto; overflow-x:hidden;">

				<div class="divider mt10 mb10">Описание</div>
				<div class="infodiv bgwhite text-wrap">

					<?= nl2br($price['descr']) ?>

				</div>

				<div class="divider mt10 mb10">Расширенное описание</div>
				<div class="infodiv bgwhite text-wrap">

					<?= nl2br($content) ?>

				</div>

			</div>
			<div id="tab-form-2" style="max-height: 450px; overflow-y: auto; overflow-x:hidden;">

				<div class="viewdiv bgwhite">

					<?php
					$result = $db -> query("SELECT * FROM {$sqlname}modcatalog_fieldcat WHERE identity = '$identity' ORDER by ord");
					while ($data = $db -> fetch($result)) {

						$val = [];

						$resultp = $db -> getOne("SELECT value FROM {$sqlname}modcatalog_field WHERE n_id = '".$n_id."' and pfid = '".$data['id']."' and identity = '$identity'");
						$values  = yexplode(";", $resultp);

						foreach ($values as $value) {

							$val[] = ( $value != '' ) ? $value : '';

						}

						$width = $data['pwidth'] - 1;
						$val   = $cval = implode("; ", $val);

						if ($data['tip'] != 'divider' && $val != '') {

							print '
							<div class="flex-container">
							
								<div class="flex-string label">'.$data['name'].':</div>
								<div class="flex-string infodiv bgwhite text-wrap">'.$val.'</div>
								
							</div>
							';

						}
						if ($data['tip'] == 'divider' && $cval != '') {
							print '<div class="divider mt10 mb10">'.$data['name'].'</div>';
						}

					}
					?>

				</div>

			</div>

		</div>

	</div>
	<script>
		$(function () {
			$('#formtabs').tabs();
			resizeImages();
		});
	</script>
	<?php
	exit();
}

//Сделки, в которых участвует
if ($action == "getDogs") {

	$tbl  = '';
	$tbl2 = '';

	$result  = $db -> getRow("SELECT * FROM {$sqlname}price WHERE n_id = '$n_id' and identity = '$identity'");
	$artikul = $result["artikul"];
	$title   = $result["title"];

	$result = $db -> getAll("SELECT * FROM {$sqlname}speca WHERE (prid = '$n_id' or title = '$title') and identity = '$identity' ORDER BY spid");
	foreach ($result as $data) {

		$client = '';

		$json  = get_dog_info($data['did']);
		$datas = json_decode($json, true);

		$step = current_dogstep($data['did']);

		$step = ( $step ) ? current_dogstep($data['did']).'%' : '';

		if ($datas['clid'] > 0) {

			$client = '<br><div class="ellipsis fs-09 mt5"><A href="javascript:void(0)" onclick="openClient(\''.$datas['clid'].'\')" class="gray"><i class="icon-building broun"></i>'.current_client($datas['clid']).'</A></div>';

		}
		elseif ($datas['pid'] > 0) {

			$client = '<br><div class="ellipsis fs-09 mt5"><A href="javascript:void(0)" onclick="openPerson(\''.$datas['pid'].'\')" class="gray"><i class="icon-user-1 broun"></i>'.current_person($datas['pid']).'</A></div>';

		}

		if ($datas['close'] != 'yes') {

			$tbl .= '
			<tr class="th40 ha">
				<td class="w60">'.$step.'</td>
				<td class="nopad">
					<span class="ellipsis fs-11"><a href="javascript:void(0)" title="Быстрый просмотр: '.$datas['title'].'" onclick="openDogovor(\''.$datas['did'].'\')"><B>'.$datas['title'].'</B></a></span>
					'.$client.'
				</td>
				<td class="w120 text-right"><b>'.num_format($data['kol']).'</b>&nbsp;'.$data['edizm'].'</td>
				<td class="w100"><span class="ellipsis">'.current_user($datas['iduser']).'</span></td>
			</tr>
			';

		}
		else {

			$resultc        = $db -> getRow("SELECT * FROM {$sqlname}dogstatus WHERE sid='".$datas['sid']."' and identity = '$identity'");
			$status         = $resultc["title"];
			$status_content = $resultc["content"];

			if ($datas['kol_fact'] > 0) {
				$rez = '<b class="green">'.$status.'</b>';
			}
			else {
				$rez = '<b class="red">'.$status.'</b>';
			}

			$tbl2 .= '
			<tr class="th40 ha">
				<td class="w60"><b>'.$step.'</b></td>
				<td>
					<span class="ellipsis"><a href="javascript:void(0)" title="Быстрый просмотр: '.$datas['title'].'" onclick="openDogovor(\''.$datas['did'].'\')"><B>'.$datas['title'].'</B></a></span>
				</td>
				<td class="w120"><span class="ellipsis" title="'.untag($rez).'">'.$rez.'</span></td>
				<td class="w80 text-right"><b>'.num_format($data['kol']).'</b>&nbsp;'.$data['edizm'].'</td>
				<td class="w20"><span class="ellipsis1">'.$url2.'</span></td>
				<td class="w100"><span class="ellipsis">'.current_user($datas['iduser']).'</span></td>
			</tr>
			';

		}

	}

	if ($tbl == '') {
		$tbl .= '
		<tr class="th40">
			<td class="gray">В сделках не участвует</td>
		</tr>
		';
	}

	if ($tbl2 == '') {
		$tbl2 .= '
		<tr class="th40">
			<td class="gray">В сделках не участвует</td>
		</tr>
		';
	}

	print '
	<fieldset class="fcontainer">
	<legend><b>Активные сделки</b></legend>
	
		<table id="bborder" class="">
		'.$tbl.'
		</table>
		
	</fieldset>
	';

	print '
	<fieldset class="fcontainer">
	<legend><i class="icon-lock red"></i><b>Закрытые сделки</b></legend>
	
		<div>
		<table id="bborder" class="top nopad">
		'.$tbl2.'
		</table>
		</div>
		
	</fieldset>
	';

	exit();
}

//устаревшее
if ($action == "getDopz") {

	$tbl = '';

	$result = $db -> getAll("SELECT * FROM {$sqlname}modcatalog_dop WHERE prid = '".$n_id."' and identity = '$identity' ORDER BY datum");
	foreach ($result as $data) {

		$resultb = $db -> getRow("SELECT do, id FROM {$sqlname}budjet WHERE id = '".$data['bid']."' and identity = '$identity'");
		$do      = $resultb['do'];
		$bid     = $resultb['id'];

		if ($bid > 0) {
			if ($do == 'on') {
				$status = '<i class="icon-ok green list" title="Расход проведен"></i>';
			}
			else {
				$status = '<i class="icon-clock blue list" title="Расход занесен в бюджет, но не проведен"></i>';
			}
		}
		else {
			$status = '<i class="icon-attention gray list" title="Расход пока не запланирован"></i>';
		}

		$tbl .= '
		<tr class="th40 ha">
			<td class="w80"><b class="smalltxt">'.format_date_rus($data['datum']).'</b></td>
			<td><B>'.$data['content'].'</B></td>
			<td class="w120 text-right"><b>'.num_format($data['summa']).'</b>&nbsp;'.$valuta.'</td>
			<td class="120"><span class="ellipsis">'.current_user($data['iduser']).'</span></td>
			<td class="60 text-right">
				<a href="javascript:void(0)" onclick="doLoad(\'modules/modcatalog/form.modcatalog.php?action=editdop&id='.$data['id'].'\');" title="Редактировать"><i class="icon-pencil blue"></i></a>&nbsp;
				<span class="gray">|</span>&nbsp;
				<a href="javascript:void(0)" onclick="cf=confirm(\'Вы действительно хотите удалить запись?\');if (cf){reLoad(\'dopzat\', \'modules/modcatalog/card.php?action=deletedop&id='.$data['id'].'&n_id='.$n_id.'\')}" title="Удалить позицию"><i class="icon-cancel red"></i></a>
			</td>
		</tr>
		';

	}

	if ($tbl == '') {
		$tbl .= '
		<tr class="th40">
			<td class="gray">Дополнительных затрат нет</td>
		</tr>
		';
	}

	print '
	<fieldset class="fcontainer">
		<legend><b>Дополнительные затраты</b></legend>
		
		<div class="viewdiv bgwhite">
			<table id="bborder">
			'.$tbl.'
			</table>
		</div>
		
	</fieldset>
	';

	exit();
}

//Логи
if ($action == "getLogs") {

	$tbl = '';

	$page           = $_REQUEST['page'];
	$lines_per_page = 10;

	$allowa = [
		"datum"    => "Дата",
		"content"  => "Описание",
		"summa"    => "Сумма",
		"artikul"  => "Артикул",
		"kol"      => "Количество",
		"title"    => "Название",
		"price_in" => $dname['price_in'],
		"price_1"  => $dname['price_1'],
		"price_2"  => $dname['price_2'],
		"price_3"  => $dname['price_3'],
		"price_4"  => $dname['price_4'],
		"price_5"  => $dname['price_5'],
		"edizm"    => "Ед.изм.",
		"nds"      => "НДС",
		"status"   => "Статус",
		"descr"    => "Комментарий"
	];

	$allowa1 = [
		"datum",
		"content",
		"summa",
		"artikul",
		"kol",
		"title",
		"price_in",
		"price_1",
		"price_2",
		"price_3",
		"price_4",
		"price_5",
		"edizm",
		"nds",
		"status",
		"descr"
	];

	$status = [
		'0' => 'Нет данных',
		'1' => 'Заказан',
		'2' => 'Приобретен',
		'3' => 'В наличии',
		'4' => 'Продан'
	];

	$tips = [
		"dop"     => "Доп.затраты",
		"catalog" => "Позиция",
		"status"  => "Статус",
		"kol"     => "Количество",
		"price"   => "Розничная цена"
	];

	$query = "SELECT * FROM {$sqlname}modcatalog_log WHERE prid = '".$n_id."' and identity = '$identity' ORDER BY datum DESC";

	$result    = $db -> getAll($query);
	$all_lines = count($result);
	if (!isset($page) or empty($page) or $page <= 0) {
		$page = 1;
	}
	else {
		$page = (int)$page;
	}

	$page_for_query = $page - 1;
	$lpos           = $page_for_query * $lines_per_page;
	$count_pages    = ceil($all_lines / $lines_per_page);

	$query .= " LIMIT $lpos,$lines_per_page";

	$result = $db -> getAll($query);
	foreach ($result as $data) {

		$log = [];
		$old = [];

		$new = json_decode($data['new'], true);

		if ($data['old'] != '') {

			$old = json_decode($data['old'], true);
			$tip = '<i class="icon-arrows-cw blue"></i>';

		}
		else {
			$tip = '<i class="icon-plus green"></i>';
		}

		$gid     = $new['dopzid'];
		$content = $db -> getOne("SELECT content FROM {$sqlname}modcatalog_dop WHERE id = '".$gid."' and identity = '$identity' ORDER BY datum");

		foreach ($new as $key => $value) {

			if (!$old[$key]) {
				$old[$key] = 'нет';
			}

			if (in_array($key, $allowa1) && $old[$key] != 'нет' && $value != $old[$key]) {

				if ($key != 'status') {
					$log[] = $tip.''.$allowa[$key].' : '.$old[$key].' -> <b>'.htmlspecialchars_decode($value).'</b>';
				}
				//else $log[] = $tip.''.strtr($key,$allowa).' : '.strtr($old[$key],$status).' -> <b>'.strtr($value,$status).'</b>';

			}
			elseif (in_array($key, $allowa1) && $old[$key] == 'нет') {

				if ($key != 'status') {
					$log[] = $tip.''.$allowa[$key].' = <b>'.htmlspecialchars_decode($value).'</b>';
				}
				//else $log[] = $tip.''.strtr($key,$allowa).' = <b>'.strtr($value,$status).'</b>';

			}

		}

		$tip = strtr($data['tip'], $tips);

		$log = implode(", ", $log);

		if ($content) {
			$content = ' ['.$content.']';
		}

		$tbl .= '
		<tr class="ha th40">
			<td class="w60"><span>'.get_hist($data['datum']).'</span></td>
			<td><div class="fs-10 Bold mb5">'.$tip.'</div>'.$content.'<br>'.$log.'<br></td>
			<td class="w120"><span class="ellipsis">'.current_user($data['iduser']).'</span></td>
		</tr>
		';

	}

	if ($tbl == '') {
		$tbl .= '
		<tr class="th40">
			<td class="gray">События отсутствуют</td>
		</tr>
		';
	}

	print '
	<div class="viewdiv bgwhite">
	
		<table id="bborder" class="top nopad">
		'.$tbl.'
		</table>
		
	</div>
	'.$appendix;

	if ($count_pages > 1) {

		for ($i = 1; $i <= $count_pages; $i++) {

			if ($page == $i && $i != 1) {
				$pg .= '<div class="active">'.$i.'</div>';
			}
			elseif ($page == $i && $i == 1) {
				$pg .= '<div class="active">1</div>';
			}
			elseif ($i == 1 && $page != $i) {
				$pg .= '<div onclick="logs(\''.$i.'\')" data-page="'.$i.'">'.$i.'</div>';
			}
			elseif ($i != 1 && $page != $i) {
				$pg .= '<div onclick="logs(\''.$i.'\')" data-page="'.$i.'">'.$i.'</div>';
			}
		}

	}

	//print $page;

	if ($count_pages > 1) {
		print '
		<br>
		<div class="viewdiv bgwhite" id="pages">
		'.$pg.'
		</div>
		';
	}

	if ($tbl != '') {

		print '<hr><i class="smalltxt">Измененный параметр (Старое значение -> Новое значение)</i>';

	}

	exit();

}

//Наличие по складам
if ($action == "getSklad") {

	$prid  = $_REQUEST['n_id'];
	$sklad = [];
	?>
	<table id="bborder">
		<thead>
		<tr>
			<th class="text-left">&nbsp;Тип</th>
			<th class="w120 text-left">Кол-во</th>
			<th class="w20 text-left"></th>
		</tr>
		</thead>
		<tr class="bgbluelight th45">
			<td colspan="3" class="Bold uppercase">По складам</td>
		</tr>
		<?php
		$i   = 0;
		$res = $db -> getAll("SELECT * FROM {$sqlname}modcatalog_sklad WHERE identity = '$identity'");
		foreach ($res as $data) {

			$count = $db -> getOne("SELECT SUM(kol) as kol FROM {$sqlname}modcatalog_skladpoz WHERE sklad = '".$data['id']."' and prid = '".$prid."' and identity = '$identity'") + 0;

			$sklad[$data['id']] = $data['title'];

			if ($count > 0) {

				print '
				<tr class="th40 ha">
					<td>'.$data['title'].'</td>
					<td colspan="2">'.$count.'</td>
				</tr>';

				$i++;

			}

		}
		if ($i == 0) {

			print '
			<tr class="th40">
				<td colspan="3" class="gray2">Не найдено</td>
			</tr>';

		}
		?>
		<tr class="bgbluelight th45">
			<td colspan="3" class="Bold uppercase">В заявках (актуальные)</td>
		</tr>
		<?php
		$i   = 0;
		$res = $db -> getAll("SELECT * FROM {$sqlname}modcatalog_zayavka WHERE identity = '$identity' and status NOT IN (2, 3)");
		foreach ($res as $data) {

			$count = $db -> getOne("SELECT SUM(kol) as kol FROM {$sqlname}modcatalog_zayavkapoz WHERE idz = '".$data['id']."' and prid = '".$prid."' and identity = '$identity'") + 0;

			if ($count > 0) {

				print '
				<tr class="th45 ha hand" onclick="doLoad(\'modules/modcatalog/form.modcatalog.php?id='.$data['id'].'&action=viewzayavka\');" title="Просмотр">
					<td>
						Заявка №'.$data['number'].', Статус: <b class="'.strtr($data['status'], $colors).'">'.strtr($data['status'], $status).'</b>
					</td>
					<td colspan="2">'.$count.'</td>
				</tr>';

				$i++;

			}

		}
		if ($i == 0) {

			print '
			<tr class="th40">
				<td colspan="2" class="gray2">Не найдено</td>
			</tr>';

		}
		?>
		<tr class="bgbluelight th45">
			<td colspan="3" class="Bold uppercase">В резерве</td>
		</tr>
		<?php
		$i   = 0;
		$res = $db -> getAll("SELECT * FROM {$sqlname}modcatalog_reserv WHERE prid = '".$prid."' and identity = '$identity'");
		foreach ($res as $data) {

			if ($data['kol'] > 0) {

				$za = $db -> getRow("SELECT * FROM {$sqlname}modcatalog_zayavka WHERE id = '".$data['idz']."' and identity = '$identity'");

				$act = ( in_array($iduser1, $settings['mcCoordinator']) && $za['status'] < 2 ) ? '<a href="javascript:void(0)" onclick="cf=confirm(\'Вы действительно хотите удалить из Резерва?\');if (cf)removeReserve(\''.$data['id'].'\');" title="Удалить"><i class="icon-cancel-circled red"></i></a>' : "";

				print '
				<tr class="th40 ha">
					<td>
						<div '.( !empty($za) ? 'onclick="doLoad(\'modules/modcatalog/form.modcatalog.php?id='.$data['idz'].'&action=viewzayavka\');" title="Просмотр"' : "" ).' class="'.( !empty($za) ? 'hand' : '' ).' fs-11">
							Склад: <b>'.strtr($data['sklad'], $sklad).'</b>'.( !empty($za) ? ', Заявка №'.$za['number'].', Статус: <b class="'.strtr($za['status'], $colors).'">'.strtr($za['status'], $status).'</b>' : "" ).'
						</div>
						'.( $data['did'] > 0 ? '<div class="mt5 fs-09"><a href="javascript:void(0)" onclick="openDogovor(\''.$data['did'].'\',\'7\')" title="Карточка">'.current_dogovor($data['did']).'</a></div>' : '' ).'
					</td>
					<td>'.$data['kol'].'</td>
					<td>
						'.( in_array($iduser1, $settings['mcCoordinator']) && $za['status'] < 2 ? '<a href="javascript:void(0)" onclick="cf=confirm(\'Вы действительно хотите удалить из Резерва?\');if (cf)removeReserve(\''.$data['id'].'\');" title="Удалить"><i class="icon-cancel-circled red"></i></a>' : '' ).'
					</td>
				</tr>
				';

				$i++;

			}

		}
		if ($i == 0) {

			print '
				<tr class="th40">
					<td colspan="3" class="gray2">Не найдено</td>
				</tr>
			';

		}
		?>
	</table>
	<?php

}

//Движение по ордерам
if ($action == "getDrive") {

	$prid           = (int)$_REQUEST['n_id'];
	$page           = (int)$_REQUEST['page'];
	$lists          = [];
	$lines_per_page = 30;

	$price = Storage ::info($prid);

	$query = "
		SELECT 
			{$sqlname}modcatalog_aktpoz.id,
			{$sqlname}modcatalog_aktpoz.kol,
			{$sqlname}modcatalog_akt.id as ida,
			{$sqlname}modcatalog_akt.datum,
			{$sqlname}modcatalog_akt.tip,
			{$sqlname}modcatalog_akt.number,
			{$sqlname}modcatalog_akt.isdo,
			{$sqlname}modcatalog_akt.sklad,
			{$sqlname}modcatalog_akt.idz,
			{$sqlname}modcatalog_akt.did
		FROM {$sqlname}modcatalog_aktpoz 
			LEFT JOIN {$sqlname}modcatalog_akt ON {$sqlname}modcatalog_aktpoz.ida = {$sqlname}modcatalog_akt.id
		WHERE 
			{$sqlname}modcatalog_aktpoz.id > 0 AND 
			{$sqlname}modcatalog_aktpoz.prid = '$prid' AND
			{$sqlname}modcatalog_aktpoz.identity = '$identity'
		ORDER BY {$sqlname}modcatalog_akt.datum DESC
	";

	$res       = $db -> query($query);
	$all_lines = $db -> affectedRows($res);

	if (empty($page) || $page <= 0) {
		$page = 1;
	}
	else {
		$page = (int)$page;
	}

	$page_for_query = $page - 1;
	$lpos           = $page_for_query * $lines_per_page;
	$count_pages    = ceil($all_lines / $lines_per_page);

	$query .= " LIMIT $lpos,$lines_per_page";
	$res   = $db -> query($query);

	while ($data = $db -> fetch($res)) {

		$data['tip'] = ( $data['tip'] == 'income' ) ? '<i class="icon-down-big green pull-left" title="Приходный"></i>' : '<i class="icon-up-big red pull-left" title="Расходный"></i>';

		$data['do']      = ( $data['isdo'] == 'yes' ) ? '<span class="green">Проведен</span>' : '';
		$data['color']   = ( $data['isdo'] == 'yes' ) ? '' : 'gray2';
		$data['bgcolor'] = ( $data['isdo'] == 'yes' ) ? '' : 'graybg-sub';

		$lists[] = [
			"id"      => $data['id'],
			"akt"     => $data['ida'],
			"number"  => $data['number'],
			"datum"   => $data['datum'],
			"tip"     => $data['tip'],
			"kol"     => $data['kol'],
			"do"      => $data['do'],
			"sklad"   => Storage ::getSklad($data['sklad'], 'title'),
			"color"   => $data['color'],
			"bgcolor" => $data['bgcolor'],
			"idz"     => $data['idz'],
			"did"     => $data['did'],
			"edizm"   => $price['price']['edizm'],
			"dogovor" => current_dogovor($data['did']),
		];

	}

	foreach ($lists as $item) {

		print '
		<div class="flex-container box--child p5 pt10 pb10 ha border-bottom '.$item['color'].' '.$item['bgcolor'].'">
			<div class="flex-string wp20 hand" onclick="doLoad(\'modules/modcatalog/form.modcatalog.php?id='.$item['akt'].'&action=viewakt\');" title="Просмотр Ордера">
				'.( $item['number'] > 0 ? '<b>№ '.$item['number'].'</b> от '.get_sfdate2($item['datum']) : '--' ).'
			</div>
			<div class="flex-string wp5">'.$item['tip'].'</div>
			<div class="flex-string wp10">'.$item['kol'].' '.$item['edizm'].'</div>
			<div class="flex-string wp20" title="'.$item['sklad'].'">
				<div class="ellipsis">'.$item['sklad'].'</div>
			</div>
			<div class="flex-string wp45">
				<div class="ellipsis">
					'.( $item['did'] > 0 ? '<a href="javascript:void(0)" onclick="openDogovor(\''.$item['did'].'\')" title="Открыть"><i class="icon-briefcase-1 blue"></i>'.$item['dogovor'].'</a>' : '' ).'
				</div>
			</div>
		</div>
		';

	}

	if ($count_pages > 1) {

		for ($i = 1; $i <= $count_pages; $i++) {

			$s      = ( $i == $page ) ? 'selected' : '';
			$select .= '<option value="'.$i.'" '.$s.'>&nbsp;&nbsp;'.$i.'&nbsp;&nbsp;</option>';

		}

		$j = $page + 1;
		$k = $page - 1;

		print '
		<div class="viewdiv bgwhite" id="pages">
			'.( $page > 1 ? 'Страница: <div onclick="drive(\''.$k.'\')" data-page="'.$k.'"><</div>&nbsp;' : '' ).'
			<span class="select inline">&nbsp;<select id="hpage" name="hpage" onchange="drive()">'.$select.'</select>&nbsp;</span>
			'.( $page < $count_pages ? '<div onclick="drive(\''.$j.'\')" data-page="'.$j.'"> > </div>' : '' ).'
		</div>
		';

	}

}