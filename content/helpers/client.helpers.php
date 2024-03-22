<?php
/* ============================ */
/*         SalesMan CRM         */
/* ============================ */
/* (C) 2016 Vladislav Andreev   */
/*       SalesMan Project       */
/*        www.isaler.ru         */
/*        ver. 2019.x           */
/* ============================ */

use Salesman\Elements;

error_reporting( E_ERROR );
header( "Pragma: no-cache" );

$rootpath = dirname(__DIR__, 2);

require_once $rootpath."/inc/config.php";
require_once $rootpath."/inc/dbconnector.php";
require_once $rootpath."/inc/auth.php";
require_once $rootpath."/inc/func.php";
require_once $rootpath."/inc/settings.php";
require_once $rootpath."/inc/language/".$language.".php";

$thisfile = basename( __FILE__ );

$action = $_REQUEST['action'];
$clid   = (int)$_REQUEST['clid'];

//Данные для редактирования полей клиента
$fldtip  = $_REQUEST['fldtip']; //тип элемента
$fldvals = $_REQUEST['fldvals']; //поле таблицы

//для добавления поля
$fldnewName = $_REQUEST['newfield'];
$fldnew     = $_REQUEST['field'];

$exp = [
	'ооо',
	'зао',
	'оао',
	'ип'
];

if ( $action == 'dostup' ) {

	$result = $db -> getAll( "SELECT * FROM {$sqlname}dostup WHERE clid = '".$clid."' and identity = '$identity'" );
	foreach ( $result as $data ) {

		$s = ($data['subscribe'] == 'on') ? '<i class="icon-mail sup blue"></i>' : "";

		print '<div class="inline mr5 mb5 p10 bluebg-sub"><i class="icon-user-1 blue"></i>&nbsp;'.current_user( $data['iduser'] ).$s.'</div>';

	}

	if ( count( $result ) == 0 ) {
		print '<div class="p5 gray2">Доступ не предоставлялся</div>';
	}

	exit();

}
if ( $action == 'validateclient' ) {

	$bad = $bad2 = 0;
	$msg = '';

	//проверим наличие сделки
	$bad1 = $db -> getOne( "SELECT COUNT(*) FROM {$sqlname}dogovor WHERE clid = '$clid' and identity = '$identity' ORDER by did" ) + 0;
	if ( $bad1 > 0 ) {
		$msg .= 'Имеются сделки в количестве '.$bad1.' штук.';
	}

	//проверим контрольные точки
	$bad2 = $db -> getOne( "SELECT COUNT(*) FROM {$sqlname}person WHERE clid = '$clid' and identity = '$identity' ORDER BY pid" ) + 0;
	if ( $bad2 > 0 ) {
		$msg .= ' Имеются связанные Персоны в количестве '.$bad2.' штук.';
	}

	$bad = $bad1 + $bad2;

	print $msg;

	exit();

}
if ( $action == "recvisites" ) {

	$tip  = $_REQUEST['tip'];
	$char = $_REQUEST['char'];

	$list = [];
	$file = file_get_contents( $rootpath.'/cash/spravochnik.json' );
	$fc   = json_decode( $file, true );

	foreach ( $fc[ $tip ] as $key => $value ) {

		$dc = yexplode( ",", (string)$value );
		print $dc[ $char ]."\n";

	}

	exit();

}
if ( $action == "personlist" ) {

	$clid     = $_REQUEST['clid'];
	$response = [];

	$result = $db -> getAll( "SELECT pid, person, iduser, clid FROM {$sqlname}personcat WHERE clid = '$clid' and identity = '$identity'" );
	foreach ( $result as $data ) {
		$response[] = [
			"pid"    => $data['pid'],
			"person" => $data['person'],
			"iduser" => $data['iduser'],
			"clid"   => $data['clid']
		];
	}

	print json_encode_cyr( $response );

	exit();
}
if ( $action == 'getBIK' ) {

	//$str = (isset( $htmlwebkey ) && $htmlwebkey != '') ? '&api_key='.$htmlwebkey : '';
	//$url = 'http://htmlweb.ru/service/api.php?bic='.$_REQUEST['bik'].$str.'&from=crm&charset=utf-8&json';

	$res = [];
	$url = 'https://www.bik-info.ru/api.html?type=json&bik='.prepareMobPhone($_REQUEST['bik']);

	$response = sendRequestStream($url);
	$res = json_decode( $response, true );

	//$res = json_decode( file_get_contents($url), true );

	$res['name'] = str_replace( "&quot;", "\"", $res['name'] );

	print $res = json_encode( $res );

	exit();

}
if ( $action == 'getOtrasli' ) {

	$tip = $_REQUEST['tip'];
	$selected = (int)$_REQUEST['selected'];
	$tp  = [
		"client",
		"person"
	];

	if ( $tip == 'client' || $tip == 'person' ) {
		$tt = "and tip = 'client'";
	}
	else {
		$tt = "and tip = '$tip'";
	}

	print '<OPTION value="">--Выбор--</OPTION>';

	$result = $db -> getAll( "SELECT * FROM {$sqlname}category WHERE idcategory > 0 ".$tt." and identity = '$identity' ORDER BY title" );
	foreach ( $result as $data ) {

		print '<OPTION value="'.$data['idcategory'].'" '.($selected > 0 && $selected == $data['idcategory'] ? 'selected' : '').'>'.$data['title'].'</OPTION>';

	}

	exit();
}

if ( $action == 'get.pole' ) {

	if ( $clid ) {

		$client = current_client( $clid );
		print '<INPUT type="hidden" id="clid" name="clid" value="'.$clid.'"><INPUT id="lst_spisok" type="text" class="required" placeholder="Нажмите, чтобы выбрать" style="width: 97%;" readonly onclick="get_orgspisok(\'lst_spisok\',\'clientselector\',\'/content/helpers/client.helpers.php?action=get_orgselector\',\'clid\')" value="'.$client.'">';

	}
	exit();
}
if ( $action == 'get.maincontact' ) {

	$pid    = getClientData( $clid, 'pid' );
	$person = get_person_info( $pid, 'yes' );

	print json_encode_cyr( [
		"pid"     => $pid,
		"contact" => $person['person'],
		"ptitle"  => $person['ptitle']
	] );

	exit();
}
if ( $action == 'get_orgselector' ) {

	$word     = str_replace( " ", "%", untag( $_GET['word'] ) );
	$pname    = $_GET['pname'];
	$felement = $_GET['felement'];
	$clid     = $_GET['clid'];
	$type     = $_GET['type'];

	$s = '';

	if ( $word != '' ) {
		$s .= " and title LIKE '%$word%'";
	}
	if ( isset( $type ) ) {
		$s .= " and type = '$type'";
	}

	$result = $db -> getAll( "SELECT * FROM {$sqlname}clientcat WHERE trash != 'yes' $s and identity = '$identity' ORDER BY title" );
	foreach ( $result as $data ) {

		$s = ($data['clid'] == $clid) ? "checked" : "";

		print '
		<div class="radio">
			<label>
				<input name="lid" id="lid" type="radio" value="'.$data['clid'].'" onclick="spisok_select(\''.$pname.'\',\''.$felement.'\')" '.$s.'>&nbsp;
				<span class="custom-radio success1"><i class="icon-radio-check"></i></span>
				<span id="txt'.$data['clid'].'" class="ellipsis" title="'.$data['title'].'">'.$data['title'].'</span>
				<span id="user" class="blue user" style="float: right">'.current_user( $data['iduser'] ).'</span>
			</label>
		</div>';

	}

	if ( count( $result ) == 0 ) {
		print '<b class="red">! В базе ничего не найдено</b>';
	}

	exit();

}
if ( $action == 'get_clients' ) {

	$title      = str_replace( " ", "%", untag( $_REQUEST['title'] ) );
	$phone      = $_REQUEST['phone'];
	$fax        = $_REQUEST['fax'];
	$site_url   = $_REQUEST['site_url'];
	$address    = $_REQUEST['address'];
	$idcategory = $_REQUEST['category'];
	$iduser     = $_REQUEST['iduser'];
	$datum_1    = $_REQUEST['datum_1'];
	$datum_2    = $_REQUEST['datum_2'];

	$client_list = (array)$_REQUEST['client_list'];

	$filter     = $_REQUEST['filter'];
	$clientpath = $_REQUEST['clientpath'];
	$territory  = $_REQUEST['territory'];
	$tip_cmr    = $_REQUEST['tip_cmr'];

	$fplus = [];

	if ( $datum_1 != '' && $datum_2 != '' ) {
		$fplus[] = " and {$sqlname}clientcat.date_create between '$datum_1' and '$datum_2'";
	}

	if ( $site_url == 'yes' ) {
		$fplus[] = " and {$sqlname}clientcat.site_url != ''";
	}

	if ( $site_url == 'no' ) {
		$fplus[] = " and {$sqlname}clientcat.site_url = ''";
	}

	if ( !empty( $client_list ) ) {
		$fplus[] = " and {$sqlname}clientcat.clid NOT IN (".yimplode( ",", $client_list ).")";
	}

	if ( $_REQUEST['report'] != 'yes' ) {
		$fplus[] = " and {$sqlname}clientcat.mail_url != ''";
	}

	if ( $phone != '' ) {
		$fplus[] = " and {$sqlname}clientcat.phone LIKE '%$phone%'";
	}

	if ( $fax != '' ) {
		$fplus[] = " and {$sqlname}clientcat.fax LIKE '%$fax%'";
	}

	if ( $address != '' ) {
		$fplus[] = " and {$sqlname}clientcat.address LIKE '%$address%'";
	}

	if ( $_REQUEST['report'] == 'yes' ) {
		$filter = 'otdel';
	}

	$query = getFilterQuery( 'client', [
		'iduser'     => $iduser,
		'filter'     => $filter,
		'idcategory' => $idcategory,
		'clientpath' => $clientpath,
		'territory'  => $territory,
		'tip_cmr'    => $tip_cmr,
		'filterplus' => implode( "", $fplus )
	], false );

	$option = '';

	$result = $db -> getAll( $query." ORDER BY title" );
	foreach ( $result as $data ) {

		$option .= '<option value="'.$data['clid'].'" '.($data['clid'] == $clid ? "selected" : "").'>'.$data['title'].'</option>';

	}

	print $option;

	exit();

}

if ( $action == "clientlist" ) {

	$word = mb_strtolower( $_REQUEST["q"], 'utf-8' );
	$word = str_replace( [
		"(",
		")",
		",",
		"+"
	], " ", untag( $word ) );

	$strong = $_REQUEST['strong'];

	if ( $word == '' ) {
		print 'error';
	}

	$sort = '';

	if ( $_REQUEST["tip"] == "contragent" ) {
		$sort .= " and type IN ('partner','contractor')";
	}
	elseif( !empty($_REQUEST['xtip']) && $_REQUEST['xtip'] != 'undefined' ){
		$sort .= " and type = '".$_REQUEST['xtip']."'";
	}

	//для данного поиска требуется строгое соответствие словосочетанию
	$q = str_replace( " ", "%", ($word) );

	$result = $db -> getAll( "SELECT title, clid, iduser, type FROM {$sqlname}clientcat WHERE title LIKE '%$q%' $sort and identity = '$identity'" );
	//print $db -> lastQuery();
	foreach ( $result as $data ) {

		print $data['title']."|".$data['clid']."|".current_user( $data['iduser'] )."|".$data['type']."|".$client_types[$data['type']]."\n";

	}

	exit();
}
if ( $action == "contactlist" ) {

	$clid = ($_REQUEST['client']['clid'] > 0) ? $_REQUEST['client']['clid'] : $_REQUEST['clid'];
	$word = texttosmall( $_REQUEST['q'] );
	$word = str_replace( [
		"(",
		")",
		","
	], " ", $word );

	$sort = '';

	if ( $clid > 0 ) {
		$sort .= " and clid = '$clid'";
	}

	$words = yexplode( " ", (string)$word );

	$w = [];
	foreach ( $words as $k => $v ) {
		if ( mb_strlen( trim( $v ), 'utf-8' ) > 2 && !in_array( $v, (array)$exp ) ) {
			$w[] = $v;
		}
	}

	$words = $w;

	if ( count( $words ) == 0 or mb_strlen( trim( $words[0] ), 'utf-8' ) <= 2 ) {
		goto lbl2;
	}


	if ( $word != '' && count( $words ) > 1 ) {

		$regexp = [];

		asort( $words );
		foreach ( $words AS $word ) {
			if ( $word != ' ' ) {
				$regexp[] = '('.$word.')+';
			}
		}


		$sort .= " and LOWER(person) REGEXP '".implode( "(.*)?", $regexp )."'";

		//$regexp = array();

		if ( count( $words ) > 1 ) {

			rsort( $words );

			foreach ( $words AS $word ) {
				if ( $word != ' ' ) {
					$regexp[] = '('.$word.')+';
				}
			}

		}

		$sort .= " or LOWER(person) REGEXP '".implode( "(.*)?", $regexp )."'";

	}
	else {
		$sort = " and person LIKE '%".$words[0]."%'";
	}

	if ( $clid > 0 ) {
		$sort = " and clid = '$clid'";
	}

	//print "SELECT LOWER(person) as person2, person, pid, iduser, clid FROM {$sqlname}personcat WHERE pid > 0 $sort and identity = '$identity' LIMIT 10";

	$result = $db -> getAll( "SELECT LOWER(person) as person2, person, pid, iduser, clid FROM {$sqlname}personcat WHERE pid > 0 $sort and identity = '$identity' LIMIT 10" );
	foreach ( $result as $data ) {

		print $data['person']."|".$data['pid']."|".current_user( $data['iduser'] )."|".current_client( $data['clid'] )."\n";

	}

	lbl2:

	exit();
}

if ( $action == "clientinfo" ) {

	$pid  = $_REQUEST['pid'];
	$clid = $_REQUEST['clid'];

	if ( $pid > 0 ) {
		print $client = get_person_info( $pid );
	}

	if ( $clid < 1 ) {
		$clid = $client['clid'];
	}

	if ( $clid > 0 ) {
		print $client = get_client_info( $clid );
	}

	exit();
}
if ( $action == "clientinfomore" ) {

	$pid  = $_REQUEST['pid'];
	$clid = $_REQUEST['clid'];

	$client = [];
	$person = [];

	if ( $pid > 0 ) {
		$person = get_person_info( $pid, 'yes' );
	}

	if ( $clid < 1 ) {
		$clid = $person['clid'];
	}

	if ( $clid > 0 ) {
		$client = get_client_info( $clid, 'yes' );
	}

	print json_encode_cyr( [
		"clid"    => $clid,
		"client"  => $client,
		"pid"     => $pid,
		"contact" => $person
	] );

	exit();
}

if ( $action == 'validate' ) {

	$word = untag( texttosmall( $_REQUEST['title'] ) );
	$word = str_replace( [
		"(",
		")",
		","
	], " ", $word );

	$type   = $_REQUEST['type'];
	$sort   = '';
	$string = '';
	$list   = [];

	$words = yexplode( " ", (string)$word );

	$w = [];
	foreach ( $words as $k => $v ) {
		if ( mb_strlen( trim( $v ), 'utf-8' ) > 2 && !in_array( $v, (array)$exp ) ) {
			$w[] = $v;
		}
	}

	$words = $w;

	if ( count( $words ) == 0 || mb_strlen( trim( $words[0] ), 'utf-8' ) <= 2 ) {

		$string .= '<div class="red">Продолжайте ввод данных</div>';
		goto lbl3;

	}

	if ( $word != '' && count( $words ) > 1 ) {

		$regexp = [];

		asort( $words );

		foreach ( $words AS $word ) {
			if ( !in_array( $word, (array)$exp ) && $word != ' ' ) {
				$regexp[] = '('.$word.')+';
			}
		}


		$sort .= " and LOWER(title) REGEXP '".implode( "(.*)?", $regexp )."'";

		$regexp = [];

		if ( count( $words ) > 1 ) {

			rsort( $words );

			foreach ( $words AS $word ) {
				if ( $word != ' ' ) {
					$regexp[] = '('.$word.')+';
				}
			}


		}

		$sort .= " or LOWER(title) REGEXP '".implode( "(.*)?", $regexp )."'";

	}
	else {
		$sort = " and title LIKE '%".$words[0]."%'";
	}

	$result = $db -> getAll( "SELECT clid, title, iduser, phone, fax, mail_url FROM {$sqlname}clientcat WHERE clid > 0 $sort and clid != '$clid' and identity = '$identity' ORDER BY title LIMIT 10" );
	$num    = count( $result );

	//print $db -> lastQuery();

	foreach ( $result as $data ) {

		$data['phone']    = ($data['fax'] != '') ? $data['phone'].", ".$data['fax'] : $data['phone'];
		$data['mail_url'] = ($data['mail_url'] != '') ? ", ".$data['mail_url'] : "";

		if ( get_accesse( (int)$data['clid'] ) != 'yes' && $acs_prava != 'on' ) {
			$data['phone'] = yimplode( ", ", hidePhone( $data['phone'] ) );
		}

		if ( get_accesse( (int)$data['clid'] ) != 'yes' && $acs_prava != 'on' ) {
			$data['mail_url'] = yimplode( ", ", hideEmail( $data['mail_url'] ) );
		}

		if ( $type != 'json' ) {
			$string .= '
			<div class="row p2">
			
				<div class="column12 grid-8">
					<div class="ellipsis fs-11">'.$data['title'].'</div>
					<div class="em fs-09 gray2">'.$data['tel'].'</div>
				</div>
				<div class="column12 grid-4 blue">'.current_user( $data['iduser'] ).'</div>
				
			</div>
			<hr>
			';
		}

		else {
			$list[] = [
				"name"  => $data['title'],
				"tel"   => $data['phone'],
				"email" => $data['mail_url'],
				"user"  => current_user( $data['iduser'] )
			];
		}

	}

	if ( $num < 1 && $type != 'json' ) {
		$string .= '<div class="green">Ура! Дубликатов нет. Можно добавить</div>';
	}

	lbl3:

	if ( $type != 'json' ) {
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
if ( $action == 'valphone' ) {

	$word = texttosmall( $_REQUEST['title'] );
	$word = str_replace( [
		"(",
		")",
		","
	], " ", $word );

	$type   = $_REQUEST['type'];
	$sort   = '';
	$string = '';
	$pcount = $ccount = 0;
	$list   = [];

	$phones = str_replace( [
		"+",
		"(",
		")",
		"-",
		" "
	], "", $word );
	$phones = yexplode( ",", (string)$phones );
	$count  = count( $phones ) - 1;

	if ( $phones[ $count ] != '' && strlen( $phones[ $count ] ) > 3 ) {

		$str = substr( $phones[ $count ], 1 );

		$sortp = " and (replace(replace(replace(replace(replace(phone, '+', ''), '(', ''), ')', ''), ' ', ''), '-', '') LIKE '%".$str."%' or replace(replace(replace(replace(replace(fax, '+', ''), '(', ''), ')', ''), ' ', ''), '-', '') LIKE '%".$str."%')";

		$result = $db -> getAll( "SELECT * FROM {$sqlname}clientcat WHERE clid > 0 $sortp and identity = '$identity' ORDER BY title" );
		$ccount = count( $result );
		foreach ( $result as $data ) {

			$tel = '';

			if ( $data['phone'] ) {
				$tel = $data['phone'];
			}
			elseif ( $data['fax'] ) {
				$tel = $data['fax'];
			}

			$tel = yexplode( ",", (string)$tel );

			for ( $i = 0, $iMax = count( $tel ); $i < $iMax; $i++ ) {

				if ( stripos( prepareMobPhone( $tel[ $i ] ), $str ) !== false ) {

					$data['phone']    = ($data['fax'] != '') ? $tel[ $i ].", ".$data['fax'] : $tel[ $i ];
					$data['mail_url'] = ($data['mail_url'] != '') ? $data['mail_url'] : "";

					if ( get_accesse( (int)$data['clid'] ) != 'yes' && $acs_prava != 'on' ) {
						$data['phone'] = yimplode( ", ", hidePhone( $data['phone'] ) );
					}

					if ( get_accesse( (int)$data['clid'] ) != 'yes' && $acs_prava != 'on' ) {
						$data['mail_url'] = yimplode( ", ", hideEmail( $data['mail_url'] ) );
					}

					if ( $type != 'json' ) {
						$string .= '
						<div class="row p2">
		
							<div class="column12 grid-8">
								<div class="ellipsis fs-11">'.$tel[ $i ].'</div>
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
							"tel"   => $data['phone'],
							"email" => $data['mail_url'],
							"user"  => current_user( $data['iduser'] )
						];
					}

				}

			}
		}

		$sortp = " and (replace(replace(replace(replace(replace(tel, '+', ''), '(', ''), ')', ''), ' ', ''), '-', '') LIKE '%$str%' or replace(replace(replace(replace(replace(fax, '+', ''), '(', ''), ')', ''), ' ', ''), '-', '') LIKE '%$str%' or replace(replace(replace(replace(replace(mob, '+', ''), '(', ''), ')', ''), ' ', ''), '-', '') LIKE '%$str%')";

		$result = $db -> getAll( "SELECT * FROM {$sqlname}personcat WHERE pid > 0 $sortp and identity = '$identity' ORDER BY person" );
		$pcount = count( $result );
		foreach ( $result as $data ) {

			if ( $data['tel'] ) {
				$tel = $data['tel'];
			}
			elseif ( $data['mob'] ) {
				$tel = $data['mob'];
			}

			$tel = yexplode( ",", (string)$tel );

			for ( $i = 0, $iMax = count( $tel ); $i < $iMax; $i++ ) {

				if ( stripos( prepareMobPhone( $tel[ $i ] ), $str ) !== false ) {

					$data['tel']  = ($data['mob'] != '') ? $tel[ $i ].", ".$data['mob'] : $tel[ $i ];
					$data['mail'] = ($data['mail'] != '') ? $data['mail'] : "";

					if ( get_accesse( 0, (int)$data['pid'] ) != 'yes' && $acs_prava != 'on' ) {
						$data['tel'] = yimplode( ",", hidePhone( $data['tel'] ) );
					}

					if ( get_accesse( 0, (int)$data['pid'] ) != 'yes' && $acs_prava != 'on' ) {
						$data['mail'] = yimplode( ",", hideEmail( $data['mail'] ) );
					}

					if ( $type != 'json' ) {
						$string .= '
						<div class="row p2">
		
							<div class="column12 grid-8">
								<div class="ellipsis fs-11">'.$tel[ $i ].'</div>
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

	}
	else {
		$string .= '<div class="red">Продолжайте набор</div>';
	}

	$num = $pcount + $ccount;

	if ( $num < 1 && $type != 'json' ) {
		$string .= '<div class="green">Ура! Дубликатов нет. Можно добавить</div>';
	}

	if ( $type != 'json' ) {
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
if ( $action == 'valmail' ) {

	$word   = texttosmall( $_REQUEST['title'] );
	$type   = $_REQUEST['type'];
	$sort   = '';
	$string = '';
	$pcount = $ccount = 0;
	$list   = [];

	$imail = str_replace( " ", "", $word );
	$imail = explode( ",", $imail );
	$count = count( $imail ) - 1;

	if ( $imail[ $count ] != '' && strlen( $imail[ $count ] ) > 2 ) {

		$sortp  .= " and replace(replace(replace(replace(replace(mail_url, '+', ''), '(', ''), ')', ''), ' ', ''), '-', '') LIKE '%".$imail[ $count ]."%' ";
		$result = $db -> getAll( "SELECT * FROM {$sqlname}clientcat WHERE clid>0 ".$sortp." and identity = '$identity' ORDER BY title" );
		$num    = count( $result );
		foreach ( $result as $data ) {

			$data['phone']    = ($data['fax'] != '') ? $data['phone'].", ".$data['fax'] : $data['phone'];
			$data['mail_url'] = ($data['mail_url'] != '') ? $data['mail_url'] : "";

			if ( get_accesse( (int)$data['clid'] ) != 'yes' && $acs_prava != 'on' ) {
				$data['phone'] = yimplode( ", ", hidePhone( $data['phone'] ) );
			}

			if ( get_accesse( (int)$data['clid'] ) != 'yes' && $acs_prava != 'on' ) {
				$data['mail_url'] = yimplode( ", ", hideEmail( $data['mail_url'] ) );
			}

			if ( $type != 'json' ) {
				$string .= '
					<div class="row p2">
	
						<div class="column12 grid-8">
							<div class="ellipsis fs-11">'.$data['mail'].'</div>
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
					"tel"   => $data['phone'],
					"email" => $data['mail_url'],
					"user"  => current_user( $data['iduser'] )
				];
			}

		}

		if ( $num < 1 && $type != 'json' ) {
			$string .= '<div class="green">Ура! Дубликатов нет. Можно добавить</div>';
		}

	}
	else {
		$string .= '<div class="red">Продолжайте набор</div>';
	}

	if ( $type != 'json' ) {
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
if ( $action == 'valsite' ) {

	$word   = texttosmall( $_REQUEST['title'] );
	$type   = $_REQUEST['type'];
	$sort   = '';
	$string = '';
	$pcount = $ccount = 0;
	$list   = [];

	$imail = str_replace( " ", "", $word );
	$imail = explode( ",", $imail );
	$count = count( $imail ) - 1;

	if ( $imail[ $count ] != '' && strlen( $imail[ $count ] ) > 2 ) {

		$sortp .= " and site_url LIKE '%".$imail[ $count ]."%' ";

		$result = $db -> getAll( "SELECT * FROM {$sqlname}clientcat WHERE clid > 0 ".$sortp." and identity = '$identity' ORDER BY title" );
		$num    = count( $result );

		foreach ( $result as $data ) {

			$data['phone']    = ($data['fax'] != '') ? $data['phone'].", ".$data['fax'] : $data['phone'];
			$data['site_url'] = ($data['site_url'] != '') ? $data['site_url'] : "";

			if ( get_accesse( (int)$data['clid'] ) != 'yes' && $acs_prava != 'on' ) {
				$data['phone'] = yimplode( ", ", hidePhone( $data['phone'] ) );
			}

			if ( $type != 'json' ) {
				$string .= '
				<div class="row p2">

					<div class="column12 grid-8">
						<div class="ellipsis fs-11">'.$data['site_url'].'</div>
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
					"tel"   => $data['phone'],
					"email" => $data['site_url'],
					"user"  => current_user( $data['iduser'] )
				];
			}

		}

	}
	else {
		$string .= '<div class="red">Продолжайте набор</div>';
	}

	if ( $type != 'json' ) {
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
if ( $action == 'valinn' ) {

	$inn      = trim( $_GET['inn'] );
	$kpp      = trim( $_GET['kpp'] );
	$inn_pole = $_GET['inn_pole'];
	$kpp_pole = $_GET['kpp_pole'];

	$clid = (int)$_GET['clidd'];

	if ( $clid > 0 ) {
		$cc = " and clid != '".$clid."'";
	}

	if ( $inn != '' && $kpp != '' ) {

		//print "SELECT * FROM {$sqlname}clientcat WHERE ".$inn_pole." LIKE '".$inn."' and ".$kpp_pole." LIKE '".$kpp."' ORDER BY title LIMIT 1";

		//print "SELECT title, clid FROM {$sqlname}clientcat WHERE ".$inn_pole." LIKE '".$inn."' and ".$kpp_pole." LIKE '".$kpp."' $cc and identity = '$identity' ORDER BY title LIMIT 1";

		$result = $db -> getRow( "SELECT title, clid FROM {$sqlname}clientcat WHERE ".$inn_pole." LIKE '".$inn."' and ".$kpp_pole." LIKE '".$kpp."' $cc and identity = '$identity' ORDER BY title LIMIT 1" );
		$title  = $result["title"];
		$clid   = $result["clid"];

		if ( $clid > 0 ) {
			print '{"title":"'.$title.'","clid":"'.$clid.'"}';
		}
		else {
			print '{"title":"","clid":""}';
		}

	}
	else {
		print '{"title":"","clid":""}';
	}

	exit();
}

// Формирование блоков для индивидуального редактирования полей карточки сделки

if ( $action == 'getFieldElement' ) {

	$client = get_client_info( $clid, "yes" );
	$hash = $client['type'];

	$string = '';

	$systemFields = [
		'head_clid',
		'pid'
	];

	$a = yimplode( ',', $systemFields, '"' );


	//доп фильтр по типу записи
	$s = " AND (fld_sub IS NULL OR fld_sub = '$hash')";

	if ( $fldvals != 'append' ) {

		$datas = $db -> getRow( "SELECT * FROM {$sqlname}field WHERE fld_tip='client' $s AND fld_name='$fldvals' AND fld_on='yes' AND fld_temp != 'hidden' AND identity = '$identity'" );

	}
	else {

		$datas = $db -> getAll( "SELECT * FROM {$sqlname}field WHERE fld_tip='client' $s AND fld_name NOT IN ($a) AND fld_on='yes' AND fld_temp != 'hidden' AND identity = '$identity'" );

	}

	if ( $fldnew == "new" ) {

		$fieldType = $db -> getRow( "select * from {$sqlname}field WHERE fld_tip='client' AND fld_name='$fldnewName' AND fld_temp != 'hidden' AND identity = '$identity'" );

		if ( $fieldType['fld_temp'] == '' || $fieldType['fld_temp'] == '--Обычное--' ) {
			$fieldType['fld_temp'] = 'text';
		}
		elseif ( $fieldType['fld_temp'] == 'inputlist' ) {
			$fieldType['fld_temp'] = 'select';
		}

		if ( $fieldType['fld_name'] == 'tip_cmr' || $fieldType['fld_name'] == 'clientpath' ) {
			$fieldType['fld_temp'] = 'select';
		}
		$fieldArray = [
			"name"  => $fieldType['fld_name'],
			"type"  => $fieldType['fld_temp'],
			"param" => "client",
			"id"    => $clid
		];

		print json_encode( $fieldArray );
		exit();

	}

	if ( $fldtip == "adres" ) {

		$string  = Elements::Adres( "value", $client[ $datas['fld_name'] ], [
			"class" => "wp100 yaddress",
			"other" => 'placeholder="Введите адрес"'
		] );

	}
	elseif ( $fldtip == "textarea" ) {

		$text = str_replace( "<br>", "\n", $client[ $datas['fld_name'] ] );
		$string  = Elements::TextArea( "value", $text );

	}
	elseif ( $fldtip == "select" ) { //список выбора

		$string = '';
		$s      = '';

		if ( $fldvals == 'idcategory' ) { //Отрасль

			$res = $db -> query( "SELECT * FROM {$sqlname}category WHERE tip='client' AND identity = '$identity' ORDER BY title" );
			$kol = $db -> affectedRows( $res );

			$values = [];

			foreach ( $res as $data ) {

				$values[] = [
					"id"    => $data['idcategory'],
					"title" => $data['title']
				];

			}

		}
		elseif ( $fldvals == 'clientpath' ) { //Источник клиента

			$result = $db -> getAll( "SELECT * FROM {$sqlname}clientpath WHERE identity = '$identity' ORDER by name" );

			foreach ( $result as $data ) {

				$values[] = [
					"id"    => $data['id'],
					"title" => $data['name']
				];

				if ( $client[ $datas['fld_name'] ] == $data['name'] ) {
					$client[ $datas['fld_name'] ] = $data['id'];
				}

			}

		}
		elseif ( $fldvals == 'tip_cmr' ) {

			$result = $db -> getAll( "SELECT id, title FROM {$sqlname}relations WHERE identity = '$identity' ORDER by title" );

			foreach ( $result as $data ) {

				$values[] = [
					"id"    => $data['title'],
					"title" => $data['title']
				];

			}

		}
		elseif ( $fldvals == 'territory' ) {

			$result = $db -> getAll( "SELECT idcategory, title FROM {$sqlname}territory_cat WHERE identity = '$identity' $sort ORDER by title" );

			foreach ( $result as $data ) {

				$values[] = [
					"id"    => $data['idcategory'],
					"title" => $data['title']
				];

			}

		}
		elseif ( $fldvals == 'pid' ) {

			$result = $db -> getAll( "SELECT pid, person FROM {$sqlname}personcat WHERE clid='$clid' AND identity = '$identity' ORDER by person" );

			foreach ( $result as $data ) {

				$values[] = [
					"id"    => $data['pid'],
					"title" => $data['person']
				];

			}

		}
		elseif ( $fldvals == "append" ) { // Добавление нового поля

			foreach ( $datas as $d ) {

				if ( $client[ $d['fld_name'] ] == '' ) {

					$values[] = [
						"id"    => $d['fld_name'],
						"title" => $d['fld_title']
					];
				}

			}

			$namefld = 'newfield';
			$other   = 'data-type="client"';

		}
		else {

			$val = yexplode( ",", (string)$datas['fld_var'] );
			$kol = count( $val );

			foreach ( $val as $data ) {

				$values[] = [
					"id"    => $data,
					"title" => $data
				];

			}
		}

		if ( $namefld == '' ) {
			$namefld = "value";
		}

		$string  = Elements::Select( $namefld, $values, [
			"sel"   => $client[ $datas['fld_name'] ],
			"req"   => $req,
			"other" => $other
		] );

	}
	elseif ( $fldtip == "multiselect" ) {//множественный выбор

		$data = [];

		if ( $fldvals == "coid1" ) { // для конкурентов

			$res = $db -> query( "SELECT * FROM {$sqlname}clientcat WHERE type='concurent' AND identity = '$identity'" );
			$kol = $db -> affectedRows( $res );

			$data = [];
			$k    = 0;

			foreach ( $res as $v ) {

				$data[] = [
					"id"    => $v['clid'],
					"title" => $v['title']
				];

			}

			$val = yexplode( ";", (string)$client[ $fldvals ] );

		}
		else {

			$vars = explode( ",", $datas['fld_var'] );

			foreach ( $vars as $v ) {

				$data[] = [
					"id"    => $v,
					"title" => $v
				];

			}

			$val = yexplode( ",", (string)$client[ $datas['fld_name'] ] );

		}

		$string  = Elements::MultiSelect( "value", $data, [
			"sel"  => $val,
			"req"  => 'yes',
			"func" => 'saveField(\'client\')'
		] );

	}
	elseif ( $fldtip == "radio" ) {

		$vars = explode( ",", $datas['fld_var'] );

		$string  = Elements::Radio( "value", $vars, ["sel" => $client[ $datas['fld_name'] ]] );

	}
	elseif ( $fldtip == "datum" ) {

		$string  = Elements::Date( "value", $client[ $datas['fld_name'] ], [
			"class" => "inputdate required wp100",
			"other" => 'autocomplete="off" placeholder="'.$datas['fld_title'].'"'
		] );

	}
	elseif ( $fldtip == "datetime" ) {

		$string  = Elements::DateTime( "value", $client[ $datas['fld_name'] ], [
			"class" => "inputdatetime required wp100",
			"other" => 'autocomplete="off" placeholder="'.$datas['fld_title'].'"'
		] );

	}
	else {

		$string  = Elements::InputText( "value", $client[ $datas['fld_name'] ], [
			"class" => "wp100",
			"other" => 'placeholder="'.$datas['fld_title'].'"'
		] );

	}

	if ( $string != '' ) {
		print $string;
	}

	exit();

}

if ( $action == 'columnOrderSave' ) {

	$hash   = $_REQUEST['hash'];
	$fields = $_REQUEST;

	unset( $fields['action'], $fields['hash'] );

	if(!in_array($hash, ['partner','contractor','concurent'])){
		$hash = "client";
	}

	$fields = array_flip( $fields );
	ksort( $fields );
	$fields = array_values( $fields );

	$names = [
		'dcreate'            => 'date_create',
		'user'               => 'iduser',
		'history'            => 'last_hist',
		//'last_hist'          => 'last_history',
		'last_history_descr' => 'last_history_descr',
		'relation'           => 'tip_cmr',
		'category'           => 'idcategory',
		'dogovor'            => 'last_dog',
		'email'              => 'mail',
	];

	//Загрузка настроек колонок для текущего пользователя
	$f = $rootpath."/cash/{$hash}s_columns_{$iduser1}.txt";

	$file = (file_exists( $f )) ? $f : $rootpath."/cash/columns_default_client.json";

	$currentColumns = json_decode( file_get_contents( $file ), true );

	//формируем данные новых колонок ( активных )
	$columns = $exists = [];
	foreach ( $fields as $field ) {

		$key = (array_key_exists( $field, $names)) ? strtr( $field, $names ) : $field;

		$columns[ $key ] = [
			"name"  => $currentColumns[ $key ]['name'],
			"width" => $currentColumns[ $key ]['width'],
			"on"    => "yes"
		];

		$exists[] = $key;

	}

	//проходим все оставшиеся колонки (не активные) и добавляем в конец массива
	foreach ( $currentColumns as $column => $value ) {

		if ( !in_array( $column, (array)$exists ) ) {
			$columns[ $column ] = [
				"name"  => $value['name'],
				"width" => $value['width'],
				"on"    => ""
			];
		}

	}

	file_put_contents( $f, json_encode_cyr( $columns ) );

	print "Сохранено";

	exit();

}
