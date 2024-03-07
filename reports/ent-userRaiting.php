<?php
/* ============================ */
/* (C) 2018 Vladislav Andreev   */
/*       SalesMan Project       */
/*        www.isaler.ru         */
/*        ver. 2018.x           */
/* ============================ */

error_reporting(E_ERROR);
header("Pragma: no-cache");

$rootpath = realpath( __DIR__.'/../' );

include $rootpath."/inc/config.php";
include $rootpath."/inc/dbconnector.php";
include $rootpath."/inc/auth.php";
include $rootpath."/inc/func.php";
include $rootpath."/inc/settings.php";
include $rootpath."/inc/language/".$language.".php";

$action = $_REQUEST['action'];
$roles  = (array)$_REQUEST['roles'];
$year   = (int)$_REQUEST['year'];
$mon    = (int)$_REQUEST['mon'];
$view   = $_REQUEST['view'];//передается для виджета (скрывает заголовок)

$sd = ($roles == 'yes') ? " and tip = 'Менеджер продаж'" : '';

$color = [
	'#d62728',
	'#1f77b4',
	'#aec7e8',
	'#ff7f0e',
	'#ffbb78',
	'#2ca02c',
	'#98df8a',
	'#ff9896',
	'#9467bd',
	'#c5b0d5',
	'#8c564b',
	'#c49c94',
	'#e377c2',
	'#f7b6d2',
	'#7f7f7f',
	'#c7c7c7',
	'#bcbd22',
	'#dbdb8d',
	'#17becf',
	'#9edae5',
	'#393b79',
	'#5254a3',
	'#6b6ecf',
	'#9c9ede',
	'#637939',
	'#8ca252',
	'#b5cf6b',
	'#cedb9c',
	'#8c6d31',
	'#bd9e39',
	'#e7ba52',
	'#e7cb94',
	'#843c39',
	'#ad494a',
	'#d6616b',
	'#e7969c',
	'#7b4173',
	'#a55194',
	'#ce6dbd',
	'#de9ed6',
	'#FF0F00',
	'#000099',
	'#006600',
	'#CC6600',
	'#666699',
	'#990099',
	'#999900',
	'#0066CC',
	'#FF6600',
	'#996666',
	'#FF0033',
	'#0099FF',
	'#663300',
	'#666600',
	'#FF00CC',
	'#9900FF',
	'#FFCC00',
	'#003366',
	'#333333',
	'#99FF00'
];

$dsort = '';

if ($otherSettings['planByClosed']) {
	$dsort .= " and close = 'yes'";
}

if (!isset($year)) {
	$year = (int)date( 'Y' );
}
if (!isset($mon)) {
	$mon = (int)date( 'm' );
}

$mon = str_pad($mon, 2, "0", STR_PAD_LEFT);

if ($action == 'paymentView') {

	$year = (int)$_REQUEST['year'];
	$mon  = (int)$_REQUEST['mon'];
	$user = (int)$_REQUEST['iduser'];

	if ($user == 0) {
		$user = $iduser1;
	}

	$sort = ($ac_import[19] == 'on' ? $sqlname."dogovor.iduser = '$user' AND " : $sqlname."dogovor.iduser IN (".yimplode(",", (array)get_people($user, "yes")).") AND ");

	$summa = $summa_marg = 0;
	?>
	<div class="zagolovok">Данные по запросу</div>
	<?php
	$i = 0;

	//если выполнение считаем только по закрытым сделкам
	if (!$otherSettings['credit']) {
		?>
		<div id="formtabse" style="overflow: auto;">

			<table id="zebra">
				<thead class="sticked--top">
				<tr class="header_contaner">
					<TH class="w20 text-center"></TH>
					<TH class="w80 text-center">Дата факт.</TH>
					<TH class="text-center"><?= $lang['face']['DealName'][0] ?> / Клиент</TH>
					<TH class="w130 text-center">Сумма, <?= $valuta ?></TH>
					<?php if ($show_marga == 'yes' && $otherSettings['marga']) { ?>
						<TH class="w130 text-center">Маржа, <?= $valuta ?></TH>
					<?php } ?>
					<TH class="w80 text-center">Ответств.</TH>
				</tr>
				</thead>
				<?php
				$q = "
					SELECT
						*
					FROM
						".$sqlname."dogovor
					WHERE
						".$sqlname."dogovor.did > 0 and
						DATE_FORMAT(".$sqlname."dogovor.datum_close, '%y-%m') = '$year-$mon' and
						".$sqlname."dogovor.kol_fact > 0 and
						".$sqlname."dogovor.close = 'yes' and
						".($ac_import[19] == 'on' ? $sqlname."dogovor.iduser = '$user' AND " : $sqlname."dogovor.iduser IN (".yimplode(",", (array)get_people($user, "yes")).") AND ")."
						identity = '$identity'
				";

				$result = $db -> getAll($q);
				foreach ($result as $data) {

					$i++;

					if ((int)$data['clid'] > 0) {
						$client = '<i class="icon-building broun"></i><a href="javascript:void(0)" onclick="openClient('.$data['clid'].')" title="Карточка: '.current_client( $data['clid'] ).'">'.current_client( $data['clid'] ).'</b></a>';
					}
					elseif ((int)$data['pid'] > 0) {
						$client = '<i class="icon-user-1 blue"></i><a href="javascript:void(0)" onclick="openPerson(\''.$data['pid'].'\')" title="Карточка: '.current_person( $data['pid'] ).'">'.current_person( $data['pid'] ).'</b></a>';
					}

					?>
					<tr class="ha th35">
						<td class="text-center"><?= $i ?></td>
						<td class="text-center smalltxt"><?= format_date_rus($data['datum_close']) ?></td>
						<td>
							<span class="ellipsis" title="<?= $data['title'] ?>"><a href="javascript:void(0)" onclick="openDogovor('<?= $data['did'] ?>')" title="Карточка: <?= current_dogovor($data['did']) ?>"><i class="icon-briefcase blue"></i><?= current_dogovor($data['did']) ?></a></span>
							<br>
							<div class="ellipsis paddtop5"><?= $client ?></div>
						</td>
						<td class="text-right">
							<span title="<?= num_format($data['kol']) ?>"><?= num_format($data['kol']) ?> <?= $valuta ?></span>
						</td>
						<?php if ($show_marga == 'yes' && $otherSettings['marga']) { ?>
							<td class="text-right">
								<span title="<?= num_format($data['marg']) ?>"><?= num_format($data['marg']) ?> <?= $valuta ?></span>
							</td>
						<?php } ?>
						<td><span class="ellipsis"><?= current_user($data['iduser']) ?></span></td>
					</tr>
					<?php
					$summa      += $data['kol'];
					$summa_marg += $data['marg'];
				}
				?>
				<tr class="itog th35">
					<td></td>
					<td class="text-right"><strong>Итого:</strong></td>
					<td class="text-right"><strong><?= $i ?></strong></td>
					<td class="text-right">
						<span title="<?= num_format($summa) ?>"><strong><?= num_format($summa) ?></strong> <?= $valuta ?></span>
					</td>
					<?php if ($show_marga == 'yes' && $otherSettings['marga']) { ?>
						<td class="text-right nowrap">
							<span title="<?= num_format($summa_marg) ?>"><strong><?= num_format($summa_marg) ?></strong> <?= $valuta ?></span>
						</td>
					<?php } ?>
					<td></td>
				</tr>
			</table>

		</div>
		<?php
	}

	//если включена рассрочка, то считаем по оплатам
	if ($otherSettings['credit']) {
		?>
		<div id="formtabse" style="overflow: auto;">

			<table id="zebra">
				<thead class="sticked--top">
				<tr class="header-container">
					<TH class="w10 text-center"></TH>
					<TH class="w60 text-center">Дата оплаты.</TH>
					<TH class="w60 text-center">№ счета</TH>
					<TH class="w150 text-center"><?= $lang['face']['DealName'][0] ?> / Клиент</TH>
					<TH class="w120 text-center">Оплачено, <?= $valuta ?></TH>
					<?php if ($show_marga == 'yes' && $otherSettings['marga']) { ?>
						<TH class="w120 text-center">Маржа, <?= $valuta ?></TH>
					<?php } ?>
					<TH class="w80 text-center">Ответств.</TH>
				</tr>
				</thead>
				<?php

				if (!$otherSettings['planByClosed']) {
					$result = $db -> getAll( "SELECT * FROM ".$sqlname."credit WHERE do = 'on' AND DATE_FORMAT(invoice_date, '%Y-%m') = '$year-$mon' AND ".($ac_import[19] == 'on' ? $sqlname."credit.iduser = '$user' AND " : $sqlname."credit.iduser IN (".yimplode( ",", (array)get_people( $user, "yes" ) ).") AND ")." identity = '$identity' ORDER BY invoice_date DESC" );
				}
				else {
					$result = $db -> getAll( "SELECT * FROM ".$sqlname."credit WHERE do = 'on' AND (SELECT did FROM ".$sqlname."dogovor WHERE did > 0 AND close = 'yes' AND DATE_FORMAT(datum_close, '%Y-%m') = '$year-$mon' AND identity = '$identity') > 0 AND ".($ac_import[19] == 'on' ? $sqlname."dogovor.iduser = '$user' AND " : $sqlname."dogovor.iduser IN (".yimplode( ",", (array)get_people( $user, "yes" ) ).") AND ")." identity = '$identity' ORDER BY invoice_date" );
				}

				foreach ($result as $data) {

					$i++;

					if ((int)$data['clid'] > 0) {
						$client = '<i class="icon-building broun"></i><a href="javascript:void(0)" onclick="openClient('.$data['clid'].')" title="Карточка: '.current_client( $data['clid'] ).'">'.current_client( $data['clid'] ).'</b></a>';
					}
					elseif ((int)$data['pid'] > 0) {
						$client = '<i class="icon-user-1 blue"></i><a href="javascript:void(0)" onclick="openPerson(\''.$data['pid'].'\')" title="Карточка: '.current_person( $data['pid'] ).'">'.current_person( $data['pid'] ).'</b></a>';
					}

					//найдем долю оплаченного счета
					$res    = $db -> getRow("SELECT * FROM ".$sqlname."dogovor WHERE did = '".$data['did']."' and identity = '$identity'");
					$kol    = $res["kol"];
					$marga  = $res["marga"];
					$iduser = $res["iduser"];
					$close  = $res["close"];

					$dolya = ($kol > 0) ? $data['summa_credit'] / $kol : 0;

					$marga_i = $marga * $dolya;

					$ic = ($close == 'yes') ? '<i class="icon-lock green"></i>' : '<i class="icon-briefcase blue"></i>';
					?>
					<tr class="ha th35">
						<td class="text-center"><?= $i ?></td>
						<td class="text-center smalltxt"><?= format_date_rus($data['invoice_date']) ?></td>
						<td class="text-center"><?= $data['invoice'] ?></td>
						<td>
							<div class="ellipsis">
								<a href="javascript:void(0)" onclick="openDogovor('<?= $data['did'] ?>')" title="Карточка: <?= current_dogovor($data['did']) ?>"><?= $ic ?><?= current_dogovor($data['did']) ?></a>
							</div>
							<br><span class="ellipsis paddtop5" title="Карточка клиента"><?= $client ?></span>
						</td>
						<td class="text-right">
							<span title="<?= num_format($data['summa_credit']) ?>"><?= num_format($data['summa_credit']) ?> <?= $valuta ?></span>
						</td>
						<?php if ($show_marga == 'yes' && $otherSettings['marga']) { ?>
							<td class="text-right">
								<span title="<?= num_format($marga_i) ?>"><?= num_format($marga_i) ?> <?= $valuta ?></span>
							</td>
						<?php } ?>
						<td>
							<span class="ellipsis"><a href="javascript:void(0)" onclick="viewUser('<?= $data['iduser'] ?>');"><?= current_user($iduser) ?></a></span>
						</td>
					</tr>
					<?php
					$summa      += $data['summa_credit'];
					$summa_marg += $marga_i;
				}
				?>
				<tr class="itog th35">
					<td class="text-center"></td>
					<td class="text-right"><strong>Итого:</strong></td>
					<td></td>
					<td class="text-right"><strong><?= $i ?></strong></td>
					<td class="text-right">
						<span title="<?= num_format($summa) ?>"><strong><?= num_format($summa) ?></strong> <?= $valuta ?></span>
					</td>
					<?php if ($show_marga == 'yes' && $otherSettings['marga']) { ?>
						<td class="text-right">
							<span title="<?= num_format($summa_marg) ?>"><strong><?= num_format($summa_marg) ?></strong> <?= $valuta ?></span>
						</td>
					<?php } ?>
					<td></td>
				</tr>
			</table>
		</div>
		<?php
		$marga_i = 0;
	}

	?>
	<script>

		var hh = $('#dialog_container').actual('height') * 0.85;
		var hh2 = hh - $('.zagolovok').actual('outerHeight') - 70;

		if ($(window).width() > 990) {
			$('#dialog').css({'width': '800px'});
		}
		if ($(window).width() > 1200) {
			$('#dialog').css({'width': '950px'});
		}
		else {
			$('#dialog').css('width', '90vw');
		}

		$('#formtabse').css('max-height', hh2);

		$(function () {

			$('#dialog').center();

		});

	</script>
	<?php

	exit();

}
if ($action == 'planView') {

	$year = $_REQUEST['year'];
	$mon  = $_REQUEST['mon'];
	$user = $_REQUEST['iduser'];

	if (!$user) $user = $iduser1;

	$sort = ($ac_import[19] == 'on' ? " AND ".$sqlname."dogovor.iduser = '$user'" : " AND ".$sqlname."dogovor.iduser IN (".yimplode(",", get_people($user, "yes")).") ");

	$summa = $summa_marg = 0;
	?>
	<div class="zagolovok">Данные по запросу</div>
	<?php
	$i = 0;
	//если выполнение считаем только по закрытым сделкам
	if (!$otherSettings['credit']) {
		?>
		<div id="formtabse" style="overflow: auto;">

			<table id="zebra" class="bgwhite">
				<thead class="sticked--top">
				<tr class="header_contaner">
					<TH class="w20 text-center"></TH>
					<TH class="w80 text-center">Дата факт.</TH>
					<TH class="text-center"><?= $lang['face']['DealName'][0] ?> / Клиент</TH>
					<TH class="w130 text-center">Сумма, <?= $valuta ?></TH>
					<?php if ($show_marga == 'yes' && $otherSettings['marga']) { ?>
						<TH class="w130 text-center">Маржа, <?= $valuta ?></TH>
					<?php } ?>
					<TH class="w80 text-center">Ответств.</TH>
				</tr>
				</thead>
				<?php
				$q = "
				SELECT
					*
				FROM
					".$sqlname."dogovor
				WHERE
					".$sqlname."dogovor.did > 0 and
					DATE_FORMAT(".$sqlname."dogovor.datum_close, '%y-%m') = '$year-$mon' and
					".$sqlname."dogovor.kol_fact > 0 and
					".$sqlname."dogovor.close = 'yes' $sort and
					".$sqlname."dogovor.identity = '$identity'
				";

				$result = $db -> getAll($q);
				foreach ($result as $data) {

					$i++;

					if ($data['clid'] > 0)
						$client = '<i class="icon-building broun"></i><a href="javascript:void(0)" onclick="openClient('.$data['clid'].')" title="Карточка: '.current_client($data['clid']).'">'.current_client($data['clid']).'</b></a>';

					elseif ($data['pid'] > 0)
						$client = '<i class="icon-user-1 blue"></i><a href="javascript:void(0)" onclick="openPerson(\''.$data['pid'].'\')" title="Карточка: '.current_person($data['pid']).'">'.current_person($data['pid']).'</b></a>';

					?>
					<tr class="ha th35">
						<td class="text-center"><?= $i ?></td>
						<td class="text-left smalltxt"><?= format_date_rus($data['datum_close']) ?></td>
						<td>
							<span class="ellipsis" title="<?= $data['title'] ?>"><a href="javascript:void(0)" onclick="openDogovor('<?= $data['did'] ?>')" title="Карточка: <?= current_dogovor($data['did']) ?>"><i class="icon-briefcase blue"></i><?= current_dogovor($data['did']) ?></a></span>
							<br>
							<div class="ellipsis pt5"><?= $client ?></div>
						</td>
						<td class="text-right">
							<span title="<?= num_format($data['kol']) ?>"><?= num_format($data['kol']) ?> <?= $valuta ?></span>
						</td>
						<?php if ($show_marga == 'yes' && $otherSettings['marga']) { ?>
							<td class="text-right">
								<span title="<?= num_format($data['marg']) ?>"><?= num_format($data['marg']) ?> <?= $valuta ?></span>
							</td>
						<?php } ?>
						<td><span class="ellipsis"><?= current_user($data['iduser']) ?></span></td>
					</tr>
					<?php
					$summa      += $data['kol'];
					$summa_marg += $data['marg'];
				}
				?>
				<tr class="itog th35">
					<td></td>
					<td class="text-right"><strong>Итого:</strong></td>
					<td class="text-right"><strong><?= $i ?></strong></td>
					<td class="text-right">
						<span title="<?= num_format($summa) ?>"><strong><?= num_format($summa) ?></strong> <?= $valuta ?></span>
					</td>
					<?php if ($show_marga == 'yes' && $otherSettings['marga']) { ?>
						<td class="text-right nowrap">
							<span title="<?= num_format($summa_marg) ?>"><strong><?= num_format($summa_marg) ?></strong> <?= $valuta ?></span>
						</td>
					<?php } ?>
					<td></td>
				</tr>
			</table>

		</div>
		<?php
	}

	//если включена рассрочка, то считаем по оплатам
	if ($otherSettings['credit']) {
		?>
		<div id="formtabse" style="overflow: auto;">

			<table id="zebra" class="bgwhite">
				<thead class="sticked--top">
				<tr class="header-container">
					<TH class="w10 text-center"></TH>
					<TH class="w60 text-left">Дата оплаты.</TH>
					<TH class="w60 text-left">№ счета</TH>
					<TH class="w150 text-center"><?= $lang['face']['DealName'][0] ?> / Клиент</TH>
					<TH class="w120 text-right">Оплачено, <?= $valuta ?></TH>
					<?php if ($show_marga == 'yes' && $otherSettings['marga']) { ?>
						<TH class="w120 text-right">Маржа, <?= $valuta ?></TH>
					<?php } ?>
					<TH class="w80 text-center">Ответств.</TH>
				</tr>
				</thead>
				<?php

				//если расчет планов по оплатам
				if (!$otherSettings['planByClosed'])
					$q = "
						SELECT *
						FROM ".$sqlname."credit
						WHERE
							".$sqlname."credit.do = 'on' AND
							DATE_FORMAT(".$sqlname."credit.invoice_date, '%Y-%m') = '$year-$mon' AND 
							".($ac_import[19] == 'on' ? " ".$sqlname."credit.iduser = '$user' AND " : " ".$sqlname."credit.iduser IN (".yimplode(",", get_people($user, "yes")).") AND ")."
							".$sqlname."credit.identity = '$identity'
						ORDER by ".$sqlname."credit.invoice_date DESC
					";

				//расчет планов по закрытым сделкам
				else
					$q = "
						SELECT *
						FROM ".$sqlname."credit
						WHERE
							".$sqlname."credit.do = 'on' AND
							(SELECT COUNT(close) FROM ".$sqlname."dogovor WHERE ".$sqlname."dogovor.did = ".$sqlname."credit.did AND DATE_FORMAT(".$sqlname."dogovor.datum_close, '%Y-%m') = '$year-$mon' AND ".$sqlname."dogovor.identity = '$identity') > 0 AND
							".($ac_import[19] == 'on' ? " ".$sqlname."credit.iduser = '$user' AND" : " ".$sqlname."credit.iduser IN (".yimplode(",", get_people($user, "yes")).") AND ")."
							".$sqlname."credit.identity = '$identity'
						ORDER by ".$sqlname."credit.invoice_date
					";

				$result = $db -> getAll($q);
				foreach ($result as $data) {

					$i++;

					if ($data['clid'] > 0)
						$client = '<i class="icon-building broun"></i><a href="javascript:void(0)" onclick="openClient('.$data['clid'].')" title="Карточка: '.current_client($data['clid']).'">'.current_client($data['clid']).'</b></a>';

					if ($data['pid'] > 0)
						$client = '<i class="icon-user-1 blue"></i><a href="javascript:void(0)" onclick="openPerson(\''.$data['pid'].'\')" title="Карточка: '.current_person($data['pid']).'">'.current_person($data['pid']).'</b></a>';

					//найдем долю оплаченного счета
					$res    = $db -> getRow("SELECT * FROM ".$sqlname."dogovor WHERE did = '".$data['did']."' and identity = '$identity'");
					$kol    = $res["kol"];
					$marga  = $res["marga"];
					$iduser = $res["iduser"];
					$close  = $res["close"];

					$dolya   = ($kol > 0) ? $data['summa_credit'] / $kol : 0;
					$marga_i = $marga * $dolya;

					$ic = ($close == 'yes') ? '<i class="icon-lock green"></i>' : '<i class="icon-briefcase blue"></i>';
					?>
					<tr class="ha th35">
						<td class="text-center"><?= $i ?></td>
						<td class="text-left smalltxt"><?= format_date_rus($data['invoice_date']) ?></td>
						<td class="text-left"><?= $data['invoice'] ?></td>
						<td>
							<div class="ellipsis">
								<a href="javascript:void(0)" onclick="openDogovor('<?= $data['did'] ?>')" title="Карточка: <?= current_dogovor($data['did']) ?>"><?= $ic ?><?= current_dogovor($data['did']) ?></a>
							</div>
							<br><span class="ellipsis paddtop5" title="Карточка клиента"><?= $client ?></span>
						</td>
						<td class="text-right">
							<span title="<?= num_format($data['summa_credit']) ?>"><?= num_format($data['summa_credit']) ?> <?= $valuta ?></span>
						</td>
						<?php if ($show_marga == 'yes' && $otherSettings['marga']) { ?>
							<td class="text-right">
								<span title="<?= num_format($marga_i) ?>"><?= num_format($marga_i) ?> <?= $valuta ?></span>
							</td>
						<?php } ?>
						<td>
							<span class="ellipsis"><a href="javascript:void(0)" onclick="viewUser('<?= $data['iduser'] ?>');"><?= current_user($iduser) ?></a></span>
						</td>
					</tr>
					<?php
					$summa      += $data['summa_credit'];
					$summa_marg += $marga_i;
				}
				?>
				<tr class="itog th35">
					<td class="text-left"></td>
					<td class="text-right"><b>Итого:</b></td>
					<td></td>
					<td class="text-right"><b><?= $i ?></b></td>
					<td class="text-right">
						<span title="<?= num_format($summa) ?>"><b><?= num_format($summa) ?></b> <?= $valuta ?></span>
					</td>
					<?php if ($show_marga == 'yes' && $otherSettings['marga']) { ?>
						<td class="text-right">
							<span title="<?= num_format($summa_marg) ?>"><b><?= num_format($summa_marg) ?></b> <?= $valuta ?></span>
						</td>
					<?php } ?>
					<td></td>
				</tr>
			</table>

		</div>
		<?php
		$marga_i = 0;
	}

	?>
	<script>

		var hh = $('#dialog_container').actual('height') * 0.85;
		var hh2 = hh - $('.zagolovok').actual('outerHeight') - 70;

		if ($(window).width() > 990) {
			$('#dialog').css({'width': '800px'});
		}
		if ($(window).width() > 1200) {
			$('#dialog').css({'width': '950px'});
		}
		else {
			$('#dialog').css('width', '90vw');
		}

		$('#formtabse').css('max-height', hh2);

		$(function () {

			$('#dialog').center();

		});

	</script>
	<?php

	exit();

}

$kfact = $mfact = $kperc = $mperc = $dataa = [];

$days = (int)date( "t", mktime( 1, 0, 0, $mon, 1, $year ) );

$result = $db -> getAll("
	SELECT 
		* 
	FROM ".$sqlname."user 
	WHERE 
		iduser > 0 AND 
		acs_plan = 'on' AND 
		(secrty = 'yes' OR DATE_FORMAT(CompEnd, '%Y-%m') >= '$year-$mon') AND 
		iduser IN (SELECT iduser FROM ".$sqlname."plan WHERE year = '$year' AND mon = '$mon' AND iduser IN (".yimplode(",", get_people($iduser1, "yes")).") $sd AND identity = '$identity') AND 
		identity = '$identity' 
	ORDER BY title
");
foreach ($result as $data) {

	$ac_importt[ $data['iduser'] ] = explode(";", $data['acs_import']);

	$did_str                    = $dids = [];
	$kolfact                    = 0;
	$marfact                    = 0;
	$didss                      = '';
	$avatarr[ $data['iduser'] ] = "images/noavatar.png";

	if ($data['avatar']) $avatarr[ $data['iduser'] ] = "./cash/avatars/".$data['avatar'];

	$users[ $data['iduser'] ]['iduser'] = $data['iduser'];
	$users[ $data['iduser'] ]['tip']    = $data['tip'];

	//плановые показатели для текущего сотрудника
	$result1                  = $db -> getRow("SELECT SUM(kol_plan) as kol, SUM(marga) as marga FROM ".$sqlname."plan WHERE year = '$year' AND mon = '$mon' AND iduser = '".$data['iduser']."' AND identity = '$identity'");
	$kplan[ $data['iduser'] ] = $result1['kol'];
	$mplan[ $data['iduser'] ] = $result1['marga'];

	//список сделок сотрудника, с учетом подчиненных (Индивидуальный план или нет)
	if ($ac_importt[ $data['iduser'] ][19] != 'on') {
		$sub = get_people( $data['iduser'] );
	}
	else {
		$sub = " and iduser = '".$data['iduser']."'";
	}

	/**
	 * фактические показатели
	 */

	$dolya = [];

	// по оплаченным счетам
	if (!$otherSettings['planByClosed']) {
		$result3 = $db -> getAll( "SELECT * FROM ".$sqlname."credit WHERE do = 'on' AND DATE_FORMAT(invoice_date, '%Y-%m') = '$year-$mon' $sub AND identity = '$identity' ORDER by did" );
	}

	// по оплаченным счетам в закрытых сделках
	if ($otherSettings['planByClosed']) {
		$result3 = $db -> getAll( "SELECT * FROM ".$sqlname."credit WHERE do = 'on' $sub AND (SELECT did FROM ".$sqlname."dogovor WHERE did = ".$sqlname."credit.did AND DATE_FORMAT(datum_close, '%Y-%m') = '$year-$mon' AND close = 'yes' AND identity = '$identity') > 0 AND identity = '$identity' ORDER BY did" );
	}

	foreach ($result3 as $data3) {

		//расчет процента размера платежа от суммы сделки
		$result4 = $db -> getRow("SELECT kol, marga FROM ".$sqlname."dogovor WHERE did = '".$data3['did']."' and identity = '$identity'");
		$kolfact = pre_format($result4["kol"]);//сумма всей сделки
		$marfact = pre_format($result4["marga"]);//сумма всей сделки

		$dolya = ($kolfact > 0) ? $data3['summa_credit'] / $kolfact : 0;//% оплаченной суммы от суммы по договору

		$kfact[ $data['iduser'] ] += $data3['summa_credit'];
		$mfact[ $data['iduser'] ] += $marfact * $dolya;

	}

	$kperc[ $data['iduser'] ] = ($kplan[ $data['iduser'] ] > 0) ? $kfact[ $data['iduser'] ] / $kplan[ $data['iduser'] ] * 100 : 0;
	$mperc[ $data['iduser'] ] = ($mplan[ $data['iduser'] ] > 0) ? $mfact[ $data['iduser'] ] / $mplan[ $data['iduser'] ] * 100 : 0;

	/**
	 * Суммы за всё время
	 */
	// по оплаченным счетам
	if (!$otherSettings['planByClosed']) {
		$res = $db -> getAll( "SELECT * FROM ".$sqlname."credit WHERE do = 'on' $sub AND identity = '$identity' ORDER BY did" );
	}
	// по оплаченным счетам в закрытых сделках
	if ($otherSettings['planByClosed']) {
		$res = $db -> getAll( "SELECT * FROM ".$sqlname."credit WHERE do = 'on' $sub and (SELECT did FROM ".$sqlname."dogovor WHERE did = ".$sqlname."credit.did AND close = 'yes' AND identity = '$identity') > 0 AND identity = '$identity' ORDER BY did" );
	}

	$kolall = $margaall = 0;

	foreach ($res as $da) {

		//расчет процента размера платежа от суммы сделки
		$res4     = $db -> getRow("SELECT kol, marga FROM ".$sqlname."dogovor WHERE did = '".$da['did']."' and identity = '$identity'");
		$kolall   += pre_format($res4["kol"]);//сумма всей сделки
		$margaall += pre_format($res4["marga"]);//сумма всей сделки

	}

	if ($kplan[ $data['iduser'] ] > 0 || $mplan[ $data['iduser'] ] > 0) {
		$dataa[ $data['iduser'] ] = [
			"iduser"   => $data['iduser'],
			"avatar"   => $avatarr[ $data['iduser'] ],
			"kol"      => $kfact[ $data['iduser'] ],
			"kperc"    => round( $kperc[ $data['iduser'] ], 2 ),
			"marga"    => $mfact[ $data['iduser'] ],
			"mperc"    => round( $mperc[ $data['iduser'] ], 2 ),
			"kolall"   => $kolall,
			"margaall" => $margaall
		];
	}

}

//print_r($dataa);

//print_r($mplan);
//print_r($mfact);

function cmp($a, $b) { return $b['mperc'] + $b['kperc'] > $a['mperc'] + $a['kperc']; }

usort($dataa, 'cmp');

?>
<STYLE type="text/css">
	<!--
	.raiting {
		width              : 170px;
		/*height:280px;*/
		display            : inline-block;
		padding            : 10px;
		border             : 0 dotted #ddd;
		box-sizing         : border-box;
		-moz-box-sizing    : border-box;
		-webkit-box-sizing : border-box;
	}

	.raiting:hover {
		-moz-box-shadow    : 0 0 5px #999;
		-webkit-box-shadow : 0 0 5px #999;
		box-shadow         : 0 0 5px #999;
	}

	.progressbar-completed,
	.status {
		height     : 0.4em;
		box-sizing : border-box;
	}

	.progressbar-completed div {
		display : inline;
	}

	#swindow .avatarbig,
	#clientlist .avatarbig {
		width                 : 50px;
		height                : 50px;
		margin                : 0 auto;
		border                : 6px solid #E74B3B;
		border-radius         : 200px;
		-webkit-border-radius : 200px;
		-moz-border-radius    : 200px;
	}
	#swindow .avatarbig--inner,
	#clientlist .avatarbig--inner {
		width                 : 48px;
		height                : 48px;
		margin                : 0 auto;
		border                : 2px solid #FFF;
		padding-top           : -2px;
		border-radius         : 200px;
		-webkit-border-radius : 200px;
		-moz-border-radius    : 200px;
	}
	#swindow .avatar--mini,
	#clientlist .avatar--mini {
		width                 : 50px;
		height                : 50px;
		border                : 5px solid #CCC;
		border-radius         : 200px;
		-webkit-border-radius : 200px;
		-moz-border-radius    : 200px;
	}
	#swindow .candidate,
	#clientlist .candidate {
		border : 6px solid #349C5A;
	}
	#swindow .loozer,
	#clientlist .loozer {
		border : 6px solid #DDD;
	}
	#swindow .raiting-mini,
	#clientlist .raiting-mini {
		width              : 190px !important;
		display            : inline-block;
		padding            : 5px;
		border             : 0 dotted #ddd;
		box-sizing         : border-box;
		-moz-box-sizing    : border-box;
		-webkit-box-sizing : border-box;
	}

	.flex-container.main {
		align-content   : stretch !important;
		flex-wrap       : wrap !important;
		justify-content : center;
	}
	.flex-container.main > .flex-string {
		min-width          : 200px !important;
		flex-grow          : inherit !important;
		box-sizing         : border-box;
		-moz-box-sizing    : border-box;
		-webkit-box-sizing : border-box;
	}
	.flex-container.last {
		align-content   : stretch !important;
		flex-wrap       : wrap !important;
		justify-content : center;
		min-width       : 300px !important;
	}
	.flex-container.last > .flex-string {
		flex-grow          : inherit !important;
		padding            : 5px;
		box-sizing         : border-box;
		-moz-box-sizing    : border-box;
		-webkit-box-sizing : border-box;
	}

	.dwinner {
		border-top    : 5px dotted rgba(231, 75, 59, 0.3);
		margin-bottom : 5px;
	}
	.dcandidate {
		border-top    : 5px dotted rgba(21, 157, 130, 0.3);
		margin-bottom : 5px;
	}
	.dloozer {
		border-top    : 5px dotted #DDD;
		margin-bottom : 5px;
	}

	.progressbar {
		width                 : 100%;
		border                : #CCC 0 dotted;
		-moz-border-radius    : 1px;
		-webkit-border-radius : 1px;
		border-radius         : 1px;
		background            : rgba(250, 250, 250, 1);
		position              : relative;
	}
	.progressbar-completed {
		height       : 2.0em;
		line-height       : 2.0em;
		margin-left  : 0;
		padding-left : 0;
	}
	.progressbar-text {
		position : absolute;
		right    : 10px;
		top      : 5px;
	}
	.progressbar-head {
		position : absolute;
		left     : 10px;
		top      : 5px;
	}

	.progress-gray2 {
		background-image : -webkit-gradient(linear, 0.00% 50.00%, 100.00% 50.00%, color-stop(0%, rgba(207, 216, 220, 1)), color-stop(91.71%, rgba(207, 216, 220, 1.00)));
		background-image : -webkit-linear-gradient(0deg, rgba(207, 216, 220, 1.00) 0%, rgba(207, 216, 220, 1.00) 91.71%);
		background-image : linear-gradient(90deg, rgba(207, 216, 220, 1.00) 0%, rgba(207, 216, 220, 1.00) 91.71%);
	}
	.progress-green {
		background-image : -webkit-gradient(linear, 0.00% 50.00%, 100.00% 50.00%, color-stop(0%, rgba(0, 150, 136, 1)), color-stop(100%, rgba(0, 150, 136, 1.00)));
		background-image : -webkit-linear-gradient(0deg, rgba(0, 150, 136, 1) 0%, rgba(0, 150, 136, 1.00) 100%);
		background-image : linear-gradient(90deg, rgba(0, 150, 136, 1) 0%, rgba(0, 150, 136, 1.00) 100%);
	}
	.progress-green2 {
		background-image : -webkit-gradient(linear, 0.00% 50.00%, 100.00% 50.00%, color-stop(0%, rgba(26, 188, 156, 1)), color-stop(100%, rgba(26, 188, 156, 1.00)));
		background-image : -webkit-linear-gradient(0deg, rgba(26, 188, 156, 1) 0%, rgba(26, 188, 156, 1.00) 100%);
		background-image : linear-gradient(90deg, rgba(26, 188, 156, 1) 0%, rgba(26, 188, 156, 1.00) 100%);
	}
	.progress-blue {
		background-image : -webkit-gradient(linear, 0.00% 50.00%, 100.00% 50.00%, color-stop(0%, rgba(33, 150, 243, 1)), color-stop(100%, rgba(33, 150, 243, 1.00)));
		background-image : -webkit-linear-gradient(0deg, rgba(33, 150, 243, 1) 0%, rgba(33, 150, 243, 1.00) 100%);
		background-image : linear-gradient(90deg, rgba(33, 150, 243, 1) 0%, rgba(33, 150, 243, 1.00) 100%);
	}
	.progress-blue2 {
		background-image : -webkit-gradient(linear, 0.00% 50.00%, 100.00% 50.00%, color-stop(0%, rgba(100, 181, 246, 1)), color-stop(100%, rgba(100, 181, 246, 1.00)));
		background-image : -webkit-linear-gradient(0deg, rgba(100, 181, 246, 1) 0%, rgba(100, 181, 246, 1.00) 100%);
		background-image : linear-gradient(90deg, rgba(100, 181, 246, 1) 0%, rgba(100, 181, 246, 1.00) 100%);
	}

	.graybg22 {
		background : rgba(245, 245, 245, 1);
	}

	.ryear, .rmon {
		border     : 1px solid rgba(207, 216, 220, 1) !important;
		background : rgba(207, 216, 220, .3) !important;
	}
	.ryear.active,
	.rmon.active {
		border     : 1px solid rgba(231, 75, 59, 1.3) !important;
		background : rgba(231, 75, 59, 0.3) !important;
	}

	#tagbox .tags{
		color: #222 !important;
	}

	-->
</STYLE>

<div class="relativ mt20 mb20 wp95 <?=$view?> text-center mb20">

	<h1 class="uppercase fs-14 m0 mb10">Рейтинг сотрудников</h1>

	<div class="blue">по выполнению планов за <b><?= ru_mon($mon)." ".$year ?></b></div>

	<div class="pull-right hand noprint" style="top:20px;">

		<form id="customForm" name="customForm">

			<div class="pop nothide" id="params">

				<div class="gray2 mr20 fs-12"><i class="icon-cog-1 gray2"></i></div>
				<div class="popmenu-top cursor-default" style="right:5px">

					<div class="popcontent info1 w300 pad10 fs-09 box--child" style="right: 0;">

						<div class="flex-container">

							<div class="flex-string wp20 text-right fs-14 pt5 pr10">Год:</div>
							<div class="flex-string wp80">

								<div class="flex-container" id="tagbox">

									<input type="hidden" id="year" name="year" value="<?= $year ?>">

									<?php
									$y = date('Y');
									while ($y > date('Y') - 4) {

										$s = ($y == $year) ? "active" : "";

										print '
											<div class="flex-string p5 fs-14 ryear tags text-center '.$s.'" data-year="'.$y.'">
											'.$y.'
											</div>
											';

										$y--;

									}
									?>

								</div>

							</div>

						</div>
						<hr>
						<div class="flex-container">

							<div class="flex-string wp20 text-right fs-14 pt5 pr10">Месяц:</div>
							<div class="flex-string wp80">

								<div class="flex-container" id="tagbox">

									<input type="hidden" id="mon" name="mon" value="<?= (int)$mon ?>">

									<?php
									$y = date('Y');
									for ($i = 1; $i <= 12; $i++) {

										$s = ($i == (int)$mon) ? "active" : "";

										print '
											<div class="flex-string p5 fs-14 rmon tags text-center '.$s.'" data-mon="'.$i.'">
											'.ru_mon($i).'
											</div>
											';

									}
									?>

								</div>

							</div>

						</div>
						<hr>
						<div class="flex-container mt10 mb10">

							<div class="flex-string wp20"></div>
							<div class="flex-string wp80">

								<div class="checkbox fs-14 clearevent">
									<label for="roles">
										<input name="roles" type="checkbox" id="roles" value="yes" <?php if ($roles == 'yes') print 'checked' ?>>
										<span class="custom-checkbox"><i class="icon-ok"></i></span>
										&nbsp;Только менеджеры
									</label>
								</div>

							</div>

						</div>
						<hr>
						<div class="flex-container mt20">

							<div class="flex-string wp20"></div>
							<div class="flex-string wp80">

								<a href="javascript:void(0)" onclick="generateReport()" class="button fs-11" title="Применить"><i class="icon-ok"></i>Применить</a>

							</div>

						</div>

					</div>

				</div>

			</div>

		</form>

	</div>

</div>

<div class="data mt20">

	<div class="flex-container mt10 mb20 wp95 gray2">

		<div class="flex-string wp10"></div>
		<div class="flex-string wp70 cherta pb5">

			<div class="wp100 m0">
				За текущий период
			</div>

		</div>
		<div class="flex-string wp20 text-right cherta pb5">

			<div class="wp100 m0">
				За всё время
			</div>

		</div>

	</div>

	<?php
	foreach ($dataa as $iduser => $data) {

		$p  = $data['kperc'] + $data['mperc'];
		$wk = $data['kperc'];
		$wm = $data['mperc'];
		$pk = 'progress-gray';
		$pm = 'progress-gray2';
		$a  = '';

		if ($data['kperc'] > 100) {

			$pk = 'progress-green';
			$wk = '100%';

		}
		elseif (is_between($data['kperc'], 70, 100)) {

			$pk = 'progress-blue';

		}

		if ($data['mperc'] > 100) {

			$pm = 'progress-green2';
			$wm = '100%';

		}
		elseif (is_between($data['mperc'], 70, 100)) {

			$pm = 'progress-blue2';

		}

		if (is_between($p, 0, 70)) $a = 'loozer';
		elseif ($p >= 140) $a = 'candidate';

		print '
		<div class="flex-container mb10 ha pt10 pb10 wp95">
		
			<div class="flex-container wp100 mb5">
		
				<div class="flex-string wp10 uppercase Bold"></div>
				<div class="flex-string wp90 uppercase Bold gray">
					'.current_user($data['iduser'], "yes").'
				</div>
			
			</div>
		
			<div class="flex-string wp10">
				<div class="avatarbig '.$a.'" style="background: url('.$data['avatar'].'); background-size:cover;" title="'.current_user($data['iduser'], 'yes').'"></div>
			</div>
			<div class="flex-string wp70 hand" onclick="doLoad(\'reports/ent-userRaiting.php?action=planView&iduser='.$data['iduser'].'&mon='.$mon.'&year='.$year.'\')">
			
				<div class="wp100 m0" title="Оборот">
					<DIV class="progressbar wp100 graybg22">
						<div class="progressbar-text '.($data['kperc'] > 90 ? 'white' : '').'">'.$data['kperc'].'%</div>
						<div class="progressbar-head '.($data['kperc'] > 70 ? 'white' : '').'"><b>'.num_format($data['kol']).'</b> из '.num_format($kplan[ $data['iduser'] ]).'</div>
						<DIV id="test" class="progressbar-completed '.$pk.'" style="width:'.$wk.'%;"></DIV>
					</DIV>
				</div>
				
				<div class="wp100 m0" title="Маржа">
					<DIV class="progressbar wp100">
						<div class="progressbar-text '.($data['mperc'] > 90 ? 'white' : '').'">'.$data['mperc'].'%</div>
						<div class="progressbar-head '.($data['mperc'] > 70 ? 'white' : '').'"><b>'.num_format($data['marga']).'</b> из '.num_format($mplan[ $data['iduser'] ]).'</div>
						<DIV id="test" class="progressbar-completed '.$pm.'" style="width:'.$wm.'%;"></DIV>
					</DIV>
				</div>
				
			</div>
			<div class="flex-string wp20 text-right">
			
				<div class="wp100 m0 progressbar bluebg-sub" title="Оборот за всё время">
					<div class="progressbar-completed pr5">'.num_format($data['kolall']).'</div>
				</div>
				
				<div class="wp100 m0 progressbar bluebg-sub" title="Маржа за всё время">
					<div class="progressbar-completed pr5">'.num_format($data['margaall']).'</div>
				</div>
				
			</div>
			
		</div>
		';

	}
	?>

</div>

<div style="height:60px; display:block"></div>

<script>

	$('.ryear').on('click', function () {

		var y = $(this).data('year');
		$('#year').val(y);
		$('.ryear').not(this).removeClass('active');
		$(this).addClass('active');

	});
	$('.rmon').on('click', function () {

		var m = $(this).data('mon');
		$('#mon').val(m);
		$('.rmon').not(this).removeClass('active');
		$(this).addClass('active');

	});
	$('.checkbox label').on('click', function () {

		$('.popmenu-top').show();

	});

</script>