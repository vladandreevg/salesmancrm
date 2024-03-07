<?php
/* ============================ */
/*         SalesMan CRM         */
/* ============================ */
/* (C) 2016 Vladislav Andreev   */
/*       SalesMan Project       */
/*        www.isaler.ru         */
/*        ver. 2017.x           */
/* ============================ */

error_reporting(0);

header("Pragma: no-cache");

$rootpath = dirname(__DIR__, 2);

include $rootpath."/inc/config.php";
include $rootpath."/inc/dbconnector.php";
include $rootpath."/inc/auth.php";
include $rootpath."/inc/func.php";
include $rootpath."/inc/settings.php";
include $rootpath."/inc/language/".$language.".php";

$thisfile = basename( __FILE__ );

$did    = (int)$_REQUEST['did'];
$id     = (int)$_REQUEST['id'];
$action = untag($_REQUEST['action']);

if ($action == 'view') {
	?>
	<DIV class="zagolovok">Контрольные точки</DIV>
	<table id="zebra">
		<thead>
		<tr>
			<th width="200" align="center"></th>
			<th width="100" align="center">Дата план.</th>
			<th width="100" align="center">Дата факт.</th>
			<th width="100" align="center"><b>Сделано?</b></th>
		</tr>
		</thead>
		<?php
		$i      = 0;
		$result = $db -> query("SELECT * FROM {$sqlname}complect_cat WHERE identity = '$identity' ORDER BY corder");
		while ($dataa = $db -> fetch($result)) {

			$complect = $db -> getRow("SELECT * FROM {$sqlname}complect WHERE ccid = '".$dataa['ccid']."' and did = '".$did."' and identity = '$identity' ORDER BY id");

			if ($complect['data_fact'] == '0000-00-00') $complect['data_fact'] = '-';

			if ($complect['id'] != '') {

				$do = ($complect['doit'] == 'yes') ? '<span class="green">да</span>' : '<span class="red">нет</span>';

				$dati_p = date_to_unix($complect['data_plan']);
				$dati_n = date_to_unix(current_datum());
				$dati_f = date_to_unix($complect['data_fact']);

				$day = round(($dati_p - $dati_n) / 86400);

				if ($day <= 0 && $complect['doit'] != 'yes') $day = '<b class="red">нет → '.$day.'</b> дн.';
				if ($day > 0 && $complect['doit'] != 'yes') $day = '<b class="green">нет → '.$day.'</b> дн.';
				if ($complect['doit'] == 'yes') $day = '<b class="green">да</b>';

				print
					'<tr height="30">
						<td class="cherta"><b>'.$dataa['title'].'</b></td>
						<td class="cherta" align="center">'.format_date_rus($complect['data_plan']).'</td>
						<td class="cherta" align="center">'.format_date_rus($complect['data_fact']).'</td>
						<td class="cherta" align="center">'.$day.'</a></td>
					</tr>';

				$i++;

			}

		}
		?>
	</table>
	<?php
}

if ($action == '') {

	$string = '';

	$cstep = current_dogstep($did);//текущий этап сделки
	$pstep = current_dogstepname(prev_step(current_dogstepid($did)));

	$resultct = $db -> query("SELECT * FROM {$sqlname}complect_cat WHERE identity = '$identity' ORDER BY corder");
	while ($dataa = $db -> fetch($resultct)) {

		$complects = $db -> getAll("SELECT * FROM {$sqlname}complect WHERE ccid = '".$dataa['ccid']."' and did = '$did' and identity = '$identity' ORDER BY id");
		foreach($complects as $complect) {

			$btn = '';
			$actn = 'doit';

			if ($complect['data_fact'] == '0000-00-00') {
				$complect['data_fact'] = '-';
			}

			$do = ($complect['doit'] == 'yes') ? '<span class="green">да</span>' : '<span class="red">нет</span>';

			$day = (int)diffDate2( $complect[ 'data_plan' ] );

			if ($day > 0) {
				$day = '+'.$day;
			}
			
			if ($complect['doit'] == 'yes') {
				$day = '<i class="icon-ok green"></i>';
			}
			else if ($day <= 0) {
				$day = '<b class="red">'.$day.' дн.</b>';
			}
			else {
				$day = '<b class="blue">'.$day.' дн.</b>';
			}

			//этап сделки, связанный с текущей КТ
			$nextstep = current_dogstepname($dataa['dstep']);
			$prevstep = prev_step((int)$dataa['dstep']);

			//printf("%s: %s<br>", (int)$dataa['dstep'], $prevstep);
			//$btn .= $prevstep;

			$dt = (int)$dataa['dstep'] > 0 ? "Доступно с этапа сделки ".current_dogstepname(prev_step($dataa['dstep']))."%." : "";

			$tt = get_cpaccesse($dataa['ccid']) == 'yes' || (int)$complect['iduser'] == (int)$iduser1 ? '' : "У Вас нет доступа";
			$actn = (int)$dataa['dstep'] > 0 ? "doitmore" : "doit";

			if ((int)$complect['iduser'] == $iduser1 || $tipuser == 'Поддержка продаж' || get_accesse(0, 0, (int)$dataa['did']) == "yes") {

				if ($complect['doit'] != 'yes') {

					$ss = (current_dogstepname((int)$dataa['dstep']) != '') ? '<br>Этап сделки будет переведен на '.current_dogstepname((int)$dataa['dstep']).'%.' : '';

					$btn .= ( current_dogstepname($prevstep) <= $cstep || (int)$dataa['dstep'] == 0) && (get_cpaccesse((int)$dataa['ccid']) == 'yes' || $complect['iduser'] == $iduser1) ? '<a href="javascript:void(0)" onclick="editCPoint(\''.$complect['id'].'\',\''.$actn.'\',\''.$did.'\');" title="Отметить выполнение" data-title="Отметить выполнение.<br>Будет указана текущая дата. '.$ss.'"><i class="icon-attention broun"></i></a>&nbsp;&nbsp;' : '<a href="javascript:void(0)" class="list" title="'.$dt.' '.$tt.'"><i class="icon-attention gray"></i></a>&nbsp;&nbsp;';

					$btn .= get_cpaccesse($dataa['ccid']) == 'yes' ? '<a href="javascript:void(0)" onclick="editCPoint(\''.$complect['id'].'\',\'edit\',\''.$did.'\');" title="Изменить"><i class="icon-pencil blue"></i></a>&nbsp;&nbsp;<a href="javascript:void(0)" onclick="cf=confirm(\'Вы действительно хотите удалить запись?\');if (cf)editCPoint(\''.$complect['id'].'\',\'delete\',\''.$did.'\');" title="Удалить"><i class="icon-cancel-circled2 red"></i></a>' : '<a href="javascript:void(0)" title="Изменить. '.$tt.'"><i class="icon-pencil gray"></i></a>&nbsp;&nbsp;<a href="javascript:void(0)" title="Удалить. '.$tt.'"><i class="icon-cancel-circled2 gray"></i></a>';

				}
				else {

					$btn .= (get_cpaccesse($dataa['ccid']) == 'yes') ? '<a href="javascript:void(0)" onclick="editCPoint(\''.$complect['id'].'\',\'undoit\',\''.$did.'\')" title="Восстановить" data-title="Отменить выполнение.<br>Этап сделки изменен не будет."><i class="icon-ccw broun"></i></a>&nbsp;&nbsp;' : '<a href="javascript:void(0)" title="Восстановить. '.$tt.'"><i class="icon-ccw gray"></i></a>&nbsp;&nbsp;';

					$btn .= '<a href="javascript:void(0)" title="Выполнено. Изменение не возможно" class="list"><i class="icon-pencil gray"></i></a>&nbsp;&nbsp;<a href="javascript:void(0)" title="Выполнено. Удаление не целесообразно" class="list"><i class="icon-cancel-circled2 gray"></i></a>';

				}

			}

			$string .= '
				<tr class="ha">
					<td>
						<div class="Bold fs-11">
							'.$dataa['title'].'
							'.((int)$dataa['dstep'] > 0 ? '&nbsp;<i class="icon-info-circled blue list" title="Связанный этап сделки - '.current_dogstepname($dataa['dstep']).'% '.current_dogstepcontent($dataa['dstep']).'"></i><sup class="blue">'.current_dogstepname($dataa['dstep']).'%'.'</sup>' : '').'
						</div>
						<div class="gs-09 gray2 em mt5">Контролёр: '.current_user($complect['iduser']).'</div>
					</td>
					<td class="text-center">'.format_date_rus($complect['data_plan']).'</td>
					<td class="text-center">'.format_date_rus($complect['data_fact']).'</td>
					<td class="text-center">'.$day.'</td>
					<td class="text-center">'.$btn.'</td>
				</tr>';

		}

	}


	?>
	<div class="fcontainer1">
		<table class="bgwhite">
			<thead>
			<tr class="header_contaner">
				<th class="text-center">Контрольная точка</th>
				<th class="w80 text-center">Дата план.</th>
				<th class="w100 text-center">Дата факт.</th>
				<th class="w80 text-center">Срок</th>
				<th class="w100 text-center"><b>Действия</b></th>
			</tr>
			</thead>
			<?php
			print ($string != '') ? $string : '<tr><td colspan="5" class="gray fs-09 p10">Не определены</td></tr>';
			?>
		</table>
	</div>
	<?php
}