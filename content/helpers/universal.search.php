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

$rootpath = dirname(__DIR__, 2);

include $rootpath."/inc/config.php";
include $rootpath."/inc/dbconnector.php";
include $rootpath."/inc/auth.php";
include $rootpath."/inc/func.php";
include $rootpath."/inc/settings.php";

$thisfile = basename( __FILE__ );

$start = true;

$find       = cleanTotal($_REQUEST['unisearch']);
$find_start = '';

$rezults = [];
$error = NULL;

translate:

$countRes = 0;

if(strlen($find) < 3){

	$search = [
		"search" => $find,
		"word"   => $word,
		"error"  => $error,
		"result" => $rezults,
		"text"   => "Введите не менее 3 символов"
	];

	print json_encode_cyr( $search );

	exit();

}

//если наш имбицил ищет по URL, то достанем из поиска домен
if ( filter_var( $find, FILTER_VALIDATE_URL ) ) {

	$domain = parse_url( $find );
	$find   = $domain['host'];

}

$find  = untag( $find );
$oword = untag( $find );
$word  = str_replace( " ", "%", untag( $find ) );

//переводим в нижний регистр, т.к. в sql-запросе ищем в нижнем регистре
$dword = texttosmall( str_replace( " ", "%", trim( untag( $word ) ) ) );

$action = $_REQUEST['action'];
$strong = $_REQUEST['strong'];

$isPhone = false;

$ifields = (!empty( $_REQUEST['sch'] )) ? $_REQUEST['sch'] : [
	"title",
	"content",
	"phone",
	"adress",
	"email",
	"recv"
];

$i   = 1;
$exp = [
	'ооо',
	'зао',
	'оао',
	'ип'
];

$fieldClient = [];
$result      = $db -> getAll( "select * from {$sqlname}field where fld_tip='client' and fld_on='yes' and identity = '$identity' ORDER BY fld_order" );
foreach ( $result as $data ) {
	$fieldClient[ $data['fld_name'] ] = $data['fld_title'];
}

$fieldPerson = [];
$result      = $db -> getAll( "select * from {$sqlname}field where fld_tip='person' and fld_on='yes' and identity = '$identity' ORDER BY fld_order" );
foreach ( $result as $data ) {
	$fieldPerson[ $data['fld_name'] ] = $data['fld_title'];
}

$fieldDeal = [];
$result    = $db -> getAll( "select * from {$sqlname}field where fld_tip='dogovor' and (fld_name = 'adres' or fld_temp = 'adres') and fld_on='yes' and identity = '$identity' ORDER BY fld_order" );
foreach ( $result as $data ) {
	$fieldDeal[ $data['fld_name'] ] = $data['fld_title'];
}

//проверим - это номер телефона?
if ( strlen( prepareMobPhone( $find ) ) > 6 && in_array( "phone", (array)$ifields ) ) {

	$word    = $dword = substr( prepareMobPhone( $find ), 1 );
	$isPhone = true;

}

$htmlClients = '';

$clids = [];
$pids  = [];

$words = yexplode( "%", trim( $dword ) );
$sort  = '';
$so    = [];

$w = [];
foreach ( $words as $k => $v ) {

	//убираем лишние запятые, они мешают поиску
	if ( mb_strlen( trim( $v ), 'utf-8' ) > 2 && !in_array( $v, (array)$exp ) ) {
		$w[] = str_replace( ",", "", $v );
	}

}

$words = $w;

//print_r($words);

if ( $strong != 'yes' ) {

	if ( !empty( $words ) && in_array( "title", (array)$ifields ) ) {

		$regexp = [];

		asort( $words );

		foreach ( $words as $worda ) {

			if ( $worda != ' ' ) {
				$regexp[] = '('.$worda.')+';
			}

		}

		$so[] = "LOWER(cc.title) REGEXP '".implode( "(.*)?", $regexp )."'";

		$regexp = [];

		if ( !empty( $words ) ) {

			rsort( $words );

			foreach ( $words as $worda ) {

				if ( $worda != ' ' ) {
					$regexp[] = '('.$worda.')+';
				}

			}

		}

		$so[] = "LOWER(cc.title) REGEXP '".implode( "(.*)?", $regexp )."'";

	}
	elseif ( in_array( "title", (array)$ifields ) ) {
		$so[] = "cc.title LIKE '%".$words[0]."%'";
	}

}
else {
	$so[] = "cc.title LIKE '".$dword."%'";
}

if ( in_array( "content", (array)$ifields ) ) {
	$so[] = "cc.des LIKE '%".$dword."%'";
}
if ( in_array( "recv", (array)$ifields ) ) {
	$so[] = "cc.recv LIKE '%".$dword."%'";
}
if ( in_array( "email", (array)$ifields ) ) {
	$so[] = "cc.mail_url LIKE '%".$dword."%'";
}
if ( in_array( "email", (array)$ifields ) ) {
	$so[] = "cc.site_url LIKE '%".$dword."%'";
}
if ( in_array( "adress", (array)$ifields ) ) {
	$so[] = "cc.address LIKE '%".$dword."%'";
}
/*
if ( in_array( "phone", $ifields ) )
	$so[] = "regexp_replace(phone, '[ ()+-]', '') LIKE '%".$word."%'";
if ( in_array( "phone", $ifields ) )
	$so[] = "regexp_replace(fax, '[ ()+-]', '') LIKE '%".$word."%'";
*/
if ( in_array( "phone", (array)$ifields ) ) {
	$so[] = "replace(replace(replace(replace(replace(cc.phone, '+', ''), '(', ''), ')', ''), ' ', ''), '-', '') LIKE '%".$word."%'";
}
if ( in_array( "phone", (array)$ifields ) ) {
	$so[] = "replace(replace(replace(replace(replace(cc.fax, '+', ''), '(', ''), ')', ''), ' ', ''), '-', '') LIKE '%".$word."%'";
}

$sort = (!empty( $so )) ? "AND (".yimplode( " OR ", $so ).")" : "";

$result = $db -> getAll( "
	SELECT 
	    cc.*,
		user.title AS user
	FROM {$sqlname}clientcat `cc`
		LEFT JOIN {$sqlname}user `user` ON cc.iduser = user.iduser
	WHERE 
	    cc.clid > 0 
	    $sort AND 
	    cc.identity = '$identity' 
	ORDER by cc.title
" );

//print $db -> lastQuery();
//exit();

$countRes += count( $result );

foreach ( $result as $data ) {

	if ( $acs_prava != 'on' && get_accesse( (int)$data['clid'] ) != 'yes' ) {

		$data['phone'] = yimplode( ", ", (array)hidePhone( $data['phone'] ) );
		$data['fax']   = yimplode( ", ", (array)hidePhone( $data['fax'] ) );

	}

	$dop = '';
	if ( $data['address'] ) {
		$dop .= '<div>'.$fieldClient['address'].': <b>'.highlighter( $oword, $data['address'] ).'</b></div>';
	}
	if ( $data['phone'] ) {
		$dop .= '<div>'.$fieldClient['phone'].': <b>'.highlighter( $oword, $data['phone'] ).'</b></div>';
	}
	if ( $data['fax'] ) {
		$dop .= '<div>'.$fieldClient['fax'].': <b>'.highlighter( $oword, $data['fax'] ).'</b></div>';
	}

	$rezults['client'][] = [
		"num"    => $i,
		"clid"   => $data['clid'],
		"title"  => highlighter( $oword, $data['title'] ),
		"uid"    => ($data['uid'] != '') ? $data['uid'] : NULL,
		"trash"  => ($data['trash'] == 'yes') ? 1 : NULL,
		"attach" => $dop,
		"user"   => $data['user']
	];

	$clids[] = $data['clid'];

	$i++;

}

//print $db -> lastQuery();

$htmlContacts = '';
$sort         = '';
$so           = [];

$words = (array)yexplode( "%", $dword );

if ( $strong != 'yes' ) {

	if ( !empty( $words ) && in_array( "title", (array)$ifields ) ) {

		$regexp = [];

		asort( $words );

		foreach ( $words as $worda ) {
			if ( $worda != ' ' ) {
				$regexp[] = '('.$worda.')+';
			}
		}


		$so[] = "LOWER(pc.person) REGEXP '".implode( "(.*)?", $regexp )."'";

		$regexp = [];

		if ( !empty( $words ) ) {

			rsort( $words );

			foreach ( $words as $worda ) {
				if ( $worda != ' ' ) {
					$regexp[] = '('.$worda.')+';
				}
			}

		}

		$so[] = "LOWER(pc.person) REGEXP '".implode( "(.*)?", $regexp )."'";

	}
	elseif ( in_array( "title", $ifields ) ) {
		$so[] = "pc.person LIKE '%".$dword."%'";
	}

}
else {
	$so[] = "pc.person LIKE '".$dword."%'";
}

if ( in_array( "content", $ifields ) ) {
	$so[] = "pc.ptitle LIKE '%".$dword."%'";
}
if ( in_array( "recv", $ifields ) ) {
	$so[] = "pc.social LIKE '%".$dword."%'";
}
if ( in_array( "email", $ifields ) ) {
	$so[] = "pc.mail LIKE '%".$dword."%'";
}
/*
if ( in_array( "phone", $ifields ) )
	$so[] = "regexp_replace(tel, '[ ()+-]', '') LIKE '%".$word."%'";
if ( in_array( "phone", $ifields ) )
	$so[] = "regexp_replace(fax, '[ ()+-]', '') LIKE '%".$word."%'";
if ( in_array( "phone", $ifields ) )
	$so[] = "regexp_replace(mob, '[ ()+-]', '') LIKE '%".$word."%'";
*/
if ( in_array( "phone", $ifields ) ) {
	$so[] = "replace(replace(replace(replace(replace(pc.tel, '+', ''), '(', ''), ')', ''), ' ', ''), '-', '') LIKE '%".$word."%'";
}
if ( in_array( "phone", $ifields ) ) {
	$so[] = "replace(replace(replace(replace(replace(pc.fax, '+', ''), '(', ''), ')', ''), ' ', ''), '-', '') LIKE '%".$word."%'";
}
if ( in_array( "phone", $ifields ) ) {
	$so[] = "replace(replace(replace(replace(replace( pc.mob, '+', ''), '(', ''), ')', ''), ' ', ''), '-', '') LIKE '%".$word."%'";
}

$sort = (!empty( $so )) ? "AND (".yimplode( " OR ", $so ).")" : "";

// regexp_replace(c.contact_num, '[ ()+-]', '')

if ( $sort != '' ) {

	$result   = $db -> getAll( "
		SELECT 
		    pc.*,
		    client.title AS client,
		    user.title AS user
		FROM {$sqlname}personcat `pc`
			LEFT JOIN {$sqlname}clientcat `client` ON pc.clid = client.clid
			LEFT JOIN {$sqlname}user `user` ON pc.iduser = user.iduser
		WHERE 
		    pc.pid > 0 
		    $sort AND 
		    pc.identity = '$identity' 
		ORDER by pc.person
	" );

	//print $db -> lastQuery();

	$countRes += count( (array)$result );

	foreach ( $result as $data ) {

		if ( $acs_prava != 'on' && get_accesse( 0, (int)$data['pid'] ) != 'yes' ) {

			$data['tel'] = yimplode( ", ", (array)hidePhone( $data['tel'] ) );
			$data['fax'] = yimplode( ", ", (array)hidePhone( $data['fax'] ) );
			$data['mob'] = yimplode( ", ", (array)hidePhone( $data['mob'] ) );

		}

		$dop = '';
		if ( $data['tel'] ) {
			$dop .= '<div>'.$fieldPerson['tel'].': <b>'.highlighter( $oword, $data['tel'] ).'</b></div>';
		}
		if ( $data['fax'] ) {
			$dop .= '<div>'.$fieldPerson['fax'].': <b>'.highlighter( $oword, $data['fax'] ).'</b></div>';
		}
		if ( $data['mob'] ) {
			$dop .= '<div>'.$fieldPerson['mob'].': <b>'.highlighter( $oword, $data['mob'] ).'</b></div>';
		}

		$rezults['person'][] = [
			"num"    => $i,
			"pid"    => $data['pid'],
			"title"  => highlighter( $oword, $data['person'] ),
			"trash"  => NULL,
			"attach" => $dop,
			"client" => highlighter( $word, $data['client'] ),
			"clid"   => $data['clid'] > 0 ? $data['clid'] : NULL,
			"user"   => $data['user']
		];

		$pids[] = $data['pid'];

		$i++;

	}

}

$so = [];

$words = (array)yexplode( "%", $dword );

if ( $strong != 'yes' ) {

	if ( !empty( $words ) && in_array( "title", $ifields ) ) {

		$regexp = [];

		asort( $words );

		foreach ( $words as $worda ) {
			if ( $worda != ' ' ) {
				$regexp[] = '('.$worda.')+';
			}
		}


		$so[] = "LOWER(deal.title) REGEXP '".implode( "(.*)?", $regexp )."'";

		$regexp = [];

		if ( !empty( $words ) ) {

			rsort( $words );

			foreach ( $words as $worda ) {
				if ( $worda != ' ' ) {
					$regexp[] = '('.$worda.')+';
				}
			}

		}

		$so[] = "LOWER(deal.title) REGEXP '".implode( "(.*)?", $regexp )."'";

	}
	elseif ( in_array( "title", (array)$ifields ) ) {

		$so[] = "deal.title LIKE '%".$oword."%'";

	}

}
else {
	$so[] = "deal.title LIKE '".$dword."%'";
}

if ( !empty( $clids ) ) {
	$so[] = 'deal.clid IN ('.implode( ", ", (array)$clids ).')';
}
if ( !empty( $pids ) ) {
	$so[] = 'deal.pid IN ('.implode( ", ", (array)$pids ).')';
}
if ( in_array( "content", (array)$ifields ) ) {
	$so[] = "deal.content LIKE '%".$dword."%'";
}
if ( in_array( "adres", (array)$ifields ) ) {
	$so[] = "deal.adres LIKE '%".$dword."%'";
}

foreach ( $fieldDeal as $input => $name ) {
	$so[] = "deal.$input LIKE '%".$dword."%'";
}

$sort = (!empty( $so )) ? "AND (".yimplode( " OR ", $so ).")" : "";


$htmlDogs = '';

if ( $sort != '' ) {

	$result   = $db -> getAll( "
		SELECT 
		    deal.*,
		    client.title AS client,
		    user.title AS user,
		    person.person AS person,
		    step.title AS step
		FROM {$sqlname}dogovor `deal`
			LEFT JOIN {$sqlname}clientcat `client` ON deal.clid = client.clid
			LEFT JOIN {$sqlname}personcat `person` ON deal.pid = person.pid
			LEFT JOIN {$sqlname}user `user` ON deal.iduser = user.iduser
			LEFT JOIN {$sqlname}dogcategory `step` ON deal.idcategory = step.idcategory
		WHERE 
		    deal.did > 0 
		    $sort AND 
		    deal.identity = '$identity' 
		ORDER by field(deal.close, 'no', 'yes'), deal.title
	" );
	$countRes += count( $result );

	foreach ( $result as $data ) {

		$client = '';

		$close = ($data['close'] == 'yes') ? '<i class="icon-lock red"></i>&nbsp;' : '';
		$uid   = ($data['uid'] != '') ? '<hr><div class="smalltxt">'.$data['uid'].'</div>' : '';

		if ( $data['clid'] > 0 ) {
			$client = '<div class="mt10 fs-11 text-wrap"><a href="javascript:void(0)" onclick="openClient(\''.$data['clid'].'\')" title="Открыть" class="inline blue"><i class="icon-building broun fs-09 flh-11"></i>'.highlighter( $oword, $data['client'] ).'<i class="icon-popup broun smalltxt"></i></a></div>';
		}
		elseif ( $data['pid'] > 0 ) {
			$client = '<div class="mt10 fs-11 text-wrap"><a href="javascript:void(0)" onclick="openPerson(\''.$data['pid'].'\')" title="Открыть" class="inline">'.highlighter( $oword, $data['person'] ).'&nbsp;<i class="icon-popup broun smalltxt"></i></a></div>';
		}

		$dop = '';
		foreach ( $fieldDeal as $k => $v ) {
			if ( $data[ $k ] != '' ) {
				$dop .= '<div>'.$v.': <b>'.$data[ $k ].'</b></div>';
			}
		}

		$summa = num_format( $data['kol'] ).' '.$valuta;

		if ( $acs_prava != 'on' && get_accesse( 0, 0, (int)$data['did'] ) != 'yes' ) {
			$summa = "*** ".$valuta;
		}

		$rezults['deal'][] = [
			"num"      => $i,
			"did"      => $data['did'],
			"title"    => highlighter( $oword, $data['title'] ),
			"close"    => ($data['close'] == 'yes') ? 1 : NULL,
			"step"     => $data['step'],
			"dateplan" => format_date_rus( $data['datum_plan'] ),
			"summa"    => $summa,
			"attach"   => $dop != '' ? $dop : NULL,
			"clid"     => $data['clid'] > 0 ? $data['clid'] : NULL,
			"client"   => highlighter( $oword, $data['client'] ),
			"uid"      => ($data['uid'] != '') ? $data['uid'] : NULL,
			"user"     => $data['user']
		];

		$i++;

	}

}

if ( empty($rezults['client']) && empty($rezults['person']) && empty($rezults['deal']) && !arrayFindInSet( 'входящ', [$find] ) ) {

	if ( $start ) {

		$start      = false;
		$find_start = $find;
		$find       = switcher( $find, 2 );
		goto translate;

	}
	else {

		$textResult = 'По запросам <b class="red">'.$find_start.'</b> и <b class="red">'.$find.'</b> ничего не найдено. &nbsp Уточните <div class="inline"><i class="icon-cog-1"></i>&nbsp;параметры</div>';
		$error      = true;
		$find       = $find_start;


	}

}
elseif ( $find_start != '' ) {
	$textResult = 'По запросу <b class="red">'.$find_start.'</b> ничего не найдено.<br>Показаны результаты по <b class="red">'.$find.'</b>';
}
elseif ( $countRes >= 10 ) {
	$textResult = 'Результатов по запросу <b class="red">'.$find.'</b>: '.$countRes.'.<br>Для более точного поиска используйте<i class="icon-cog-1"></i>Параметры';
}


// Возвращаем результаты поиска, исковым словом является либо найденное значение, либо 2-е искомое
$search = [
	"search" => $find,
	"word"   => $word,
	"error"  => $error,
	"result" => $rezults,
	"text"   => $textResult != '' ? $textResult : NULL
];

print json_encode_cyr( $search );

exit();



