<?php
/* ============================ */
/*         SalesMan CRM         */
/* ============================ */
/* (C) 2016 Vladislav Andreev   */
/*       SalesMan Project       */
/*        www.isaler.ru         */
/*        ver. 2017.x           */
/* ============================ */

error_reporting(E_ERROR);
header("Pragma: no-cache");

$rootpath = dirname(__DIR__, 2);

require_once $rootpath."/inc/config.php";
require_once $rootpath."/inc/dbconnector.php";
require_once $rootpath."/inc/auth.php";
require_once $rootpath."/inc/func.php";
require_once $rootpath."/inc/settings.php";
require_once $rootpath."/inc/language/".$language.".php";

$thisfile = basename(__FILE__);

$action = $_REQUEST['action'];
$clid   = (int)$_REQUEST['clid'];
$pid    = (int)$_REQUEST['pid'];

if ($action == '') {

	print '<OPTION value="">--Выбор--</OPTION>';

	if ($clid > 0) {
		$sort = " clid='".$clid."' and ";
	}
	elseif ($pid > 0) {
		$sort = " pid='".$pid."' and ";
	}

	$res = $db -> getAll("SELECT * FROM ".$sqlname."personcat WHERE $sort identity = '$identity' ORDER BY person");
	foreach ($res as $data) {

		$s = ($data['pid'] == $pid) ? "selected" : "";
		print '<OPTION value="'.$data['pid'].'" '.$s.' class="wp97">'.$data['person'].'</OPTION>';

	}

	exit();
}
if ($action == 'get.status') {

	$tip = $_REQUEST["tip"];
	$q   = mb_strtolower($_REQUEST["q"], 'utf-8');

	//if ($q == '') print 'error';

	$result = $db -> getAll("SELECT DISTINCT LOWER(ptitle), ptitle FROM ".$sqlname."personcat WHERE ptitle LIKE '%".$q."%' and identity = '$identity'");
	foreach ($result as $data) {

		echo $data['ptitle']."\n";

	}

	exit();
}
if ($action == 'get.role') {

	$tip = $_REQUEST["tip"];
	$q   = mb_strtolower($_REQUEST["q"], 'utf-8');

	//if ($q == '') print 'error';

	//print "SELECT DISTINCT rol FROM ".$sqlname."personcat WHERE rol LIKE '%".$q."%' and identity = '$identity'";

	$result = $db -> getCol("SELECT DISTINCT rol FROM ".$sqlname."personcat WHERE rol LIKE '%".$q."%' and identity = '$identity'");
	foreach ($result as $data) {

		echo $data."\n";

	}

	exit();
}

if ($action == 'get_plist') {

	if ($clid > 0) {
		$p = " clid='".$clid."' and";
	}
	else {
		$p = " iduser='".$iduser1."' and";
	}

	print '<OPTION value="">--Выбор--</OPTION>';

	$res = $db -> getAll("SELECT * FROM ".$sqlname."personcat WHERE ".$p." identity = '$identity' ORDER BY person");
	foreach ($res as $data) {

		$s = ($data['pid'] == $pid) ? "selected" : "";
		print '<OPTION value="'.$data['pid'].'" '.$s.' class="wp97">'.$data['person'].'</OPTION>';

	}

	exit();
}
if ($action == 'gper') {

	if ($pid) {
		$cl = current_person( $pid );
	}
	?>
	<INPUT type="hidden" id="pid" name="pid" value="<?= $pid ?>">
	<INPUT id="lst_spisok" type="text" class="required" placeholder="Нажмите, чтобы выбрать" style="width: 97%;" readonly onclick="get_orgspisok('lst_spisok','clientselector','/content/ajax/personlist.php?action=get_personselectordog','pid','yes')" value="<?= $cl ?>">
	<?php

	exit();

}
if ($action == 'get_personselector') {

	$word     = str_replace(" ", "%", $_REQUEST['word']);
	$pname    = $_REQUEST['pname'];
	$felement = $_REQUEST['felement'];

	$s = '';

	if ($clid > 0 && $word == '') {
		$s .= "and pc.clid = '".$clid."'";
	}
	if ($word != '') {
		$s .= " and pc.person LIKE '%".$word."%'";
	}

	$result = $db -> getAll("
		SELECT 
			pc.pid, 
			pc.person, 
			pc.ptitle, 
			pc.clid, 
			cc.title
			FROM {$sqlname}personcat `pc`
				LEFT JOIN {$sqlname}clientcat `cc` ON cc.clid = pc.clid
			WHERE pc.pid > 0 $s and pc.identity = '$identity' 
			ORDER BY pc.person
		");
	foreach ($result as $data) {

		$ss = ($data['pid'] == $pid) ? "checked" : "";

		print '
		<div class="radio personselector" data-pid="'.$data['pid'].'" data-person="'.$data['person'].'" data-client="'.$data['title'].'" data-clid="'.$data['clid'].'">
			<label>
				<input name="lid" id="lid" type="hidden" value="'.$data['pid'].'" onclick="addItem()" '.$ss.'>
				<!--<span class="custom-radio success1"><i class="icon-radio-check"></i></span>-->
				<span data-person="'.$data['person'].'" data-client="'.$data['title'].'" data-clid="'.$data['clid'].'">
					<span class="Bold fs-11" id="txt'.$data['pid'].'">'.$data['person'].'</span>
					'.($clid > 0 ? '<span class="fs-09 gray-dark"> ['.$data['ptitle'].']</span>' : '<span class="fs-09 gray-dark">'.$data['title'].'</span>').'
				</span>
			</label>
			<div class="gray2 fs-09 mt5"><i class="icon-building"></i>'.$data['title'].'</div>
		</div>';

	}

	if (empty($result)) {
		print '<b class="red">! В базе ничего не найдено</b>';
	}

	exit();

}
if ($action == 'get_personselectordog') {

	$word     = str_replace(" ", "%", $_REQUEST['word']);
	$pname    = $_REQUEST['pname'];
	$felement = $_REQUEST['felement'];

	$s = '';

	if ($clid > 0 && $word == '') {
		$s .= "and clid = '".$clid."'";
	}
	if ($word != '') {
		$s .= " and person LIKE '%".$word."%'";
	}

	$result = $db -> getAll("SELECT * FROM ".$sqlname."personcat WHERE pid!='0' ".$s." and identity = '$identity' ORDER BY person");
	foreach ($result as $data) {

		$d = ($clid > 0) ? 'onclick="addItem()"' : "onclick=\"spisok_select('".$pname."','".$felement."')\"";
		$s = ($data['pid'] == $pid) ? "checked" : "";
		?>
		<div class="radio pl5">
			<LABEL>
				<input name="lid" id="lid" type="radio" value="<?= $data['pid'] ?>" <?= $d ?> <?= $s ?>/>&nbsp;
				<span id="txt<?= $data['pid'] ?>" style="width:50%" class="ellipsis"><?= $data['person'] ?></span><span id="user" class="red user" style="float: right"><?= current_user($data['iduser']); ?></span>
			</LABEL>
		</div>
		<?php
	}

	exit();
}
if ($action == 'get_clients') {

	$person  = $_REQUEST['person'];
	$tel     = $_REQUEST['tel'];
	$loyalty = $_REQUEST['loyalty'];
	$rol     = $_REQUEST['rol'];
	$fax     = $_REQUEST['fax'];
	$iduser  = $_REQUEST['iduser'];
	$filter  = $_REQUEST['plist'];

	$clientpath  = $_REQUEST['clientpath'];
	$person_list = $_REQUEST['person_list'];

	$fplus = [];
	if ($tel != '') {
		$fplus[] = " and ".$sqlname."personcat.tel LIKE '%".$tel."%'";
	}
	if ($fax != '') {
		$fplus[] = " and ".$sqlname."personcat.fax LIKE '%".$fax."%'";
	}
	if ($rol != '') {
		$fplus[] = " and ".$sqlname."personcat.rol LIKE '%".$rol."%'";
	}
	if ($iduser != '') {
		$fplus[] = " and ".$sqlname."personcat.iduser='".$iduser."'";
	}
	if ($loyalty != '') {
		$fplus[] = " and ".$sqlname."personcat.loyalty='".$loyalty."'";
	}
	if ($_REQUEST['report'] != 'yes') {
		$fplus[] = " and ".$sqlname."personcat.mail != ''";
	}
	if (count($person_list) > 0) {
		$fplus[] = " and ".$sqlname."personcat.pid NOT IN (".yimplode( ",", $person_list ).")";
	}

	$query = getFilterQuery('person', [
		'iduser'     => $iduser,
		'filter'     => $filter,
		'clientpath' => $clientpath,
		'loyalty'    => $loyalty,
		'fields'     => [
			"person",
			"ptitle"
		],
		'filterplus' => implode("", $fplus)
	], false);

	$result = $db -> getAll($query." ORDER BY person");
	foreach ($result as $data) {

		print '<OPTION value="'.$data['pid'].'">'.$data['person'].'</OPTION>';

	}

	exit();

}

if ($action == 'validate') {

	$word   = texttosmall(cleanTotal($_REQUEST['title']));
	$word   = str_replace([
		"(",
		")",
		",",
		"+"
	], " ", $word);

	$type   = $_REQUEST['type'];
	$sort   = '';
	$string = '';
	$list   = [];
	$words  = yexplode(" ", $word);

	$w = [];
	foreach ($words as $k => $v) {

		if (mb_strlen(trim($v), 'utf-8') > 3) {
			$w[] = $v;
		}

	}
	$words = $w;

	if (count($words) == 0 || mb_strlen(trim($words[0]), 'utf-8') <= 3) {

		$string .= '<div class="red">Продолжайте ввод данных</div>';
		goto lbl1;

	}

	if ($word != '' && count($words) > 1) {

		$regexp = [];

		asort($words);

		foreach ($words as $word) {

			if ($word != ' ') {
				$regexp[] = '('.$word.')+';
			}

		}

		$sort .= " and LOWER(person) REGEXP '".implode("(.*)?", $regexp)."'";

		$regexp = [];

		if (count($words) > 1) {

			rsort($words);

			foreach ($words as $word) {

				if ($word != ' ') {
					$regexp[] = '('.$word.')+';
				}

			}

		}

		$sort .= " or LOWER(person) REGEXP '".implode("(.*)?", $regexp)."'";

	}
	else {
		$sort = " and person LIKE '%".$words[0]."%'";
	}

	$query = "SELECT * FROM ".$sqlname."personcat WHERE pid > 0 $sort and identity = '$identity' ORDER BY person LIMIT 5";

	$result = $db -> getAll($query);
	$num    = count($result);
	foreach ($result as $data) {

		$data['tel']  = ($data['mob'] != '') ? $data['tel'].", ".$data['mob'] : $data['tel'];
		$data['mail'] = ($data['mail'] != '') ? ", ".$data['mail'] : "";

		if (get_accesse(0, (int)$data['pid']) != 'yes' && $acs_prava != 'on') {
			$data['tel'] = yimplode( ",", hidePhone( $data['tel'] ) );
		}

		if (get_accesse(0, (int)$data['pid']) != 'yes' && $acs_prava != 'on') {
			$data['mail'] = yimplode( ",", hideEmail( $data['mail'] ) );
		}

		if ($type != 'json') {
			$string .= '
			<div class="row p2">
			
				<div class="column12 grid-8">
					<div class="ellipsis fs-11">'.$data['person'].'</div>
					<div class="em fs-09 gray2">'.$data['tel'].'</div>
				</div>
				
				<div class="column12 grid-4 blue">'.current_user( $data['iduser'] ).'</div>
				
			</div>
			<hr>
			';
		}

		else {
			$list[] = [
				"name"  => $data['person'],
				"tel"   => $data['tel'],
				"email" => $data['mail'],
				"user"  => current_user( $data['iduser'] )
			];
		}

	}

	if ($num < 1 && $type != 'json') {
		$string .= '<div class="green">Ура! Дубликатов нет. Можно добавить</div>';
	}

	lbl1:

	if ($type != 'json') {
		print '
		<div class="header fs-12"><b>Похожие записи (возможные дубли):</b></div>
		<div>'.$string.'</div>
		';
	}

	else {
		print json_encode_cyr( $list );
	}

	exit();

}
if ($action == 'valphone') {

	$word   = texttosmall($_REQUEST['title']);
	$word   = str_replace([
		"(",
		")",
		","
	], " ", $word);
	$type   = $_REQUEST['type'];
	$sort   = '';
	$string = '';
	$pcount = $ccount = 0;
	$list   = [];

	$phones = str_replace( [
		"(",
		"+",
		")",
		"-",
		" "
	], "", $word );
	$phones = yexplode(",", $phones);
	$count  = count($phones) - 1;

	if ($phones[ $count ] != '' && strlen($phones[ $count ]) > 3) {

		$str = substr($phones[ $count ], 1);

		$sortp = " and (replace(replace(replace(replace(replace(tel, '+', ''), '(', ''), ')', ''), ' ', ''), '-', '') LIKE '%".$str."%' or replace(replace(replace(replace(replace(fax, '+', ''), '(', ''), ')', ''), ' ', ''), '-', '') LIKE '%".$str."%' or replace(replace(replace(replace(replace(mob, '+', ''), '(', ''), ')', ''), ' ', ''), '-', '') LIKE '%".$str."%')";

		$result = $db -> getAll("SELECT * FROM ".$sqlname."personcat WHERE pid > 0 $sortp and identity = '$identity' ORDER BY person");
		$pcount = count($result);
		foreach ($result as $data) {

			$tel = '';

			if ($data['tel']) {
				$tel = $data['tel'];
			}

			elseif ($data['mob']) {
				$tel = $data['mob'];
			}

			$tels = yexplode(",", $tel);

			foreach ($tels as $tel) {

				if (stripos(prepareMobPhone($tel), $str) !== false) {

					$data['tel']  = ($data['mob'] != '') ? $tel.", ".$data['mob'] : $tel;
					$data['mail'] = ($data['mail'] != '') ? ", ".$data['mail'] : "";

					if (get_accesse(0, (int)$data['pid']) != 'yes' && $acs_prava != 'on') {
						$data['tel'] = yimplode( ",", hidePhone( $data['tel'] ) );
					}

					if (get_accesse(0, (int)$data['pid']) != 'yes' && $acs_prava != 'on') {
						$data['mail'] = yimplode( ",", hideEmail( $data['mail'] ) );
					}

					if ($type != 'json') {
						$string .= '
						<div class="row p2">
		
							<div class="column12 grid-8">
								<div class="ellipsis fs-11">'.$tel.'</div>
								<div class="em fs-09 gray2">'.$data['person'].'</div>
							</div>
							<div class="column12 grid-4 blue">'.current_user( $data['iduser'] ).'</div>
							
						</div>
						<hr>
						';
					}
					else {
						$list[] = [
							"name"  => $data['person'],
							"tel"   => $data['tel'],
							"email" => $data['mail'],
							"user"  => current_user( $data['iduser'] )
						];
					}

				}

			}

		}

		$sortp = " and (replace(replace(replace(replace(replace(phone, '+', ''), '(', ''), ')', ''), ' ', ''), '-', '') LIKE '%$str%' or replace(replace(replace(replace(replace(fax, '+', ''), '(', ''), ')', ''), ' ', ''), '-', '') LIKE '%$str%')";

		$result = $db -> getAll("SELECT * FROM ".$sqlname."clientcat WHERE clid > 0 $sortp and identity = '$identity' ORDER BY title");
		$ccount = count($result);
		foreach ($result as $data) {

			if ($data['phone']) {
				$tel = $data['phone'];
			}

			elseif ($data['fax']) {
				$tel = $data['fax'];
			}

			$tels = yexplode(",", $tel);

			foreach ($tels as $tel){

				if (stripos(prepareMobPhone($tel), $str) !== false) {

					$data['tel']  = ($tel != '') ? $tel : "";
					$data['mail'] = ($data['mail_url'] != '') ? ", ".$data['mail_url'] : "";

					if (get_accesse((int)$data['clid']) != 'yes' && $acs_prava != 'on') {
						$data['tel'] = yimplode( ",", hidePhone( $data['tel'] ) );
					}

					if (get_accesse((int)$data['clid']) != 'yes' && $acs_prava != 'on') {
						$data['mail'] = yimplode( ",", hideEmail( $data['mail'] ) );
					}

					if ($type != 'json') {
						$string .= '
						<div class="row p2">
		
							<div class="column12 grid-8">
								<div class="ellipsis fs-11">'.$tel.'</div>
								<div class="em fs-09 gray2">'.$data['title'].'</div>
							</div>
							<div class="column12 grid-4 blue">'.current_user( $data['iduser'] ).'</div>
							
						</div>
						<hr>
						';
					}

					else {
						$list[] = [
							"name"  => $data['title'],
							"tel"   => $data['tel'],
							"email" => $data['mail'],
							"user"  => current_user( $data['iduser'] )
						];
					}

				}
			}
		}

	}
	else {
		$string .= '<div class="red">Продолжайте набор</div>';
	}

	$num = $pcount + $ccount;

	if ($num < 1 && $type != 'json') {
		$string .= '<div class="green">Ура! Дубликатов нет. Можно добавить</div>';
	}

	if ($type != 'json') {
		print '
			<div class="header fs-12"><b>Похожие записи (возможные дубли):</b></div>
			<div>'.$string.'</div>
		';
	}

	else {
		print json_encode_cyr( $list );
	}

	exit();

}
if ($action == 'valmail') {

	$word   = texttosmall($_REQUEST['title']);
	$type   = $_REQUEST['type'];
	$sort   = '';
	$string = '';
	$pcount = $ccount = 0;
	$list   = [];

	$imail = str_replace(" ", "", $word);
	$imail = explode(",", $imail);
	$count = count($imail) - 1;

	if ($imail[ $count ] != '' && strlen($imail[ $count ]) > 3) {

		$sortp .= " and replace(replace(replace(replace(replace(mail, '+', ''), '(', ''), ')', ''), ' ', ''), '-', '') LIKE '%".$imail[ $count ]."%' ";

		$result = $db -> getAll("SELECT * FROM ".$sqlname."personcat WHERE pid > 0 $sortp and identity = '$identity' ORDER BY person");
		$num    = count($result);

		foreach ($result as $data) {

			$data['tel']  = ($data['mob'] != '') ? $data['tel'].", ".$data['mob'] : $data['tel'];
			$data['mail'] = ($data['mail'] != '') ? ", ".$data['mail'] : "";

			if (get_accesse(0, (int)$data['pid']) != 'yes' && $acs_prava != 'on') {
				$data['tel'] = yimplode( ",", hidePhone( $data['tel'] ) );
			}

			if (get_accesse(0, (int)$data['pid']) != 'yes' && $acs_prava != 'on') {
				$data['mail'] = yimplode( ",", hideEmail( $data['mail'] ) );
			}

			if ($type != 'json') {
				$string .= '
				<div class="row p2">

					<div class="column12 grid-8">
						<div class="ellipsis fs-11">'.$data['mail'].'</div>
						<div class="em fs-09 gray2">'.$data['person'].'</div>
					</div>
					<div class="column12 grid-4 blue">'.current_user( $data['iduser'] ).'</div>
					
				</div>
				<hr>
				';
			}
			else {
				$list[] = [
					"name"  => $data['person'],
					"tel"   => $data['tel'],
					"email" => $data['mail'],
					"user"  => current_user( $data['iduser'] )
				];
			}

		}

		if ($num < 1 && $type != 'json') {
			$string .= '<div class="green">Ура! Дубликатов нет. Можно добавить</div>';
		}

	}
	else {
		$string .= '<div class="red">Продолжайте набор</div>';
	}

	if ($type != 'json') {
		print '
			<div class="header fs-12"><b>Похожие записи (возможные дубли):</b></div>
			<div>'.$string.'</div>
		';
	}
	else {
		print json_encode_cyr( $list );
	}

	exit();

}