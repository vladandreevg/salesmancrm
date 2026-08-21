<?php
/* ============================ */
/* (C) 2016 Vladislav Andreev   */
/*       SalesMan Project       */
/*        www.isaler.ru         */
/*        ver. 2017.x           */

/* ============================ */

use Salesman\Price;

error_reporting(E_ERROR);
header("Pragma: no-cache");

$rootpath = dirname(__DIR__, 2);

include $rootpath."/inc/config.php";
include $rootpath."/inc/dbconnector.php";
include $rootpath."/inc/auth.php";
include $rootpath."/inc/func.php";
include $rootpath."/inc/settings.php";
include $rootpath."/inc/language/".$language.".php";

function h($string): string {
	return htmlspecialchars((string)$string, ENT_QUOTES, 'UTF-8');
}

$dname    = $don = [];
$fields   = [];
$price_in = '';

$result = $db -> query("SELECT * FROM {$sqlname}field WHERE fld_tip='price' AND fld_on='yes' and identity = '$identity' ORDER BY fld_order");
while ($data = $db -> fetch($result)) {

	$dname[$data['fld_name']] = $data['fld_title'];
	$dvar[$data['fld_name']]  = $data['fld_var'];
	$don[]                    = $data['fld_name'];

	if ($data['fld_name'] != 'price_in' && $data['fld_on'] == 'yes') {

		if ($data['fld_sub'] == 'hidden') {
			continue;
		}

		$fields[] = [
			"field" => $data['fld_name'],
			"title" => $data['fld_title'],
			"value" => $data['fld_var'],
			//"hidden" => $data['fld_sub'] == 'hidden' ? true : NULL,
		];

	}

}

//print_r($fields);
//exit();

if ($show_marga == 'yes') {
	$price_in = $dname['price_in'];
}

$columnfile = $rootpath.'/cash/price_columns_'.$iduser1.'.json';

// если настройки для пользователя изменены, то загржаем их
if (file_exists($columnfile)) {
	$def = json_decode(file_get_contents($columnfile), true);
}
// если нет, то формируем дефолтные значения
else {
	$def = [
		"artikul" => 120,
		"title"   => 250,
	];
	if ($show_marga == 'yes') {
		$def["price_in"] = 100;
	}
	foreach ($fields as $field) {
		$def[$field['field']] = 100;
	}
	$def["nds"]     = 60;
	$def["content"] = "";
}

//print_r($def);
//exit();

# ширина по умолчанию
$defaultWidth = $def;

$columnDefs = [
	"artikul" => [
		"enabled" => true,
		"class"   => "w".toWidth($defaultWidth['artikul'])." text-left drag--accept",
		"th"      => '<div class="ellipsis hand" id="x-artikul" onclick="changesort(\'artikul\')" title="Изменить порядок вывода">Артикул</div>',
		"td"      => function ($item) {
			return '<TD data-id="artikul"><span class="ellipsis"><B>'.h($item['artikul']).'</B></span></TD>';
		},
	],
	"title"   => [
		"enabled" => true,
		"class"   => "w".toWidth($defaultWidth['title'])." text-left drag--accept",
		"th"      => '<div class="ellipsis hand" id="x-title" onclick="changesort(\'title\')" title="Изменить порядок вывода">Название позиции</div>',
		"td"      => function ($item) use ($additionalword) {
			$cat   = ( $item['cat'] !== '' ) ? '<span class="smalltxt gray2">'.h($item['cat']).'</span>' : '';
			$match = '';
			if ($additionalword != '' && isset($item['additionalMatch'][0])) {
				$m     = $item['additionalMatch'][0];
				$match = '<br><div class="smalltxt broun ellipsis1" title="'.h($m['value']).'"><b>'.h($m['fieldname']).':</b> '.h($m['value']).'</div>';
			}
			return '
				<TD data-id="title">
					<SPAN class="ellipsis" title="'.h($item['title']).'">
						<a href="javascript:void(0)" onclick="doLoad(\'/modules/modcatalog/form.modcatalog.php?action=view&n_id='.$item['id'].'\');"><B>'.h($item['title']).'</B></a>
					</SPAN><br>
					'.$cat.'
					'.$match.'
				</TD>
				';
		},
	],
	"price_in" => [
		"enabled" => true,
		"class"   => "w".toWidth($defaultWidth['price_in'])." text-left drag--accept",
		"th"      => '<div class="ellipsis hand" id="x-price_in" onclick="changesort(\'price_in\')" title="Изменить порядок вывода">Закуп, '.h($valuta).'</div>',
		"td"      => function ($item) use ($field, $valuta) {
			return '
			<TD class="text-right" title="'.h($item['price_in'].' '.$valuta.'/'.$item['edizm']).'" data-id="pricee_1">
				<span>'.h($item['price_in']).'</span>
			</TD>
			';
		},
	],
	"nds"     => [
		"enabled" => true,
		"class"   => "w".toWidth($defaultWidth['nds'])." text-left drag--accept",
		"th"      => '<div class="ellipsis hand" id="x-nds" onclick="changesort(\'nds\')" title="Изменить порядок вывода">НДС</div>',
		"td"      => function ($item) {
			return '<TD class="text-right" data-id="nds"><span>'.h($item['nds']).' %</span></TD>';
		},
	],
	"content" => [
		"enabled" => true,
		"class"   => "text-left drag--accept",
		"th"      => '<b>Описание</b>',
		"td"      => function ($item) {
			return '<TD><div title="'.h($item['content']).'" class="ellipsis" data-id="content">'.h($item['content']).'</div></TD>';
		},
	],
];

foreach ($fields as $field) {
	$columnDefs[$field['field']] = [
		"enabled" => true,
		"class"   => "w".toWidth($defaultWidth[$field['field']])." text-left drag--accept",
		"th"      => '<div class="ellipsis hand" id="x-'.$field['field'].'" onclick="changesort(\''.$field['field'].'\')" title="Изменить порядок вывода">'.h($field['title']).', '.h($valuta).'</div>',
		"td"      => function ($item) use ($field, $valuta) {
			return '
			<TD class="text-right" title="'.h($item['title'].' '.$valuta.'/'.$item['edizm']).'" data-id="pricee_1">
				<span>'.h($item[$field['field']]).'</span>
			</TD>
			';
		},
	];
}

//print_r($columnDefs);
//exit();

# порядок вывода колонок по умолчанию
$defaultHeader = array_keys($def);

# порядок вывода задается параметром header (перечень допустимых полей через запятую или массивом)
$requestedHeader = $_REQUEST['header'] ?? NULL;
if (is_string($requestedHeader) && $requestedHeader !== '') {
	$requestedHeader = explode(',', $requestedHeader);
}

$header = [];
if (is_array($requestedHeader)) {
	foreach ($requestedHeader as $field) {
		if (isset($columnDefs[$field]) && !in_array($field, $header)) {
			$header[] = $field;
		}
	}
}

# поля, не указанные в header, добавляются в конец в порядке по умолчанию
foreach ($defaultHeader as $field) {
	if (!in_array($field, $header)) {
		$header[] = $field;
	}
}

# из итогового порядка исключаются отключенные колонки
$header = array_values(array_filter($header, static function ($field) use ($columnDefs) {
	return !empty($columnDefs[$field]['enabled']);
}));

# TH-колонки в заданном порядке (первая - checkbox, последняя - действия - фиксированы в шаблоне)
$columns = [];
foreach ($header as $field) {
	$columns[] = [
		"field" => $field,
		"class" => $columnDefs[$field]['class'],
		"th"    => $columnDefs[$field]['th'],
	];
}

/*$header = [
	"nprice_in" => $price_in,
	"fields"    => $fields,
	"nprice_1"  => $price_1,
	"nprice_2"  => $price_2,
	"nprice_3"  => $price_3,
];*/

$xlists = Price::getPriceList($_REQUEST);

$list = [];
foreach ($xlists['list'] as $da) {

	$item = [
		"id"      => $da['id'],
		"artikul" => $da['artikul'],
		"cat"     => $da['category'],
		"title"   => $da['title'],
		"price_in"  => num_format($da['price_in']),
		"edizm"   => $da['edizm'],
		"nds"     => (int)$da['nds'],
		"content" => $da['content'],
		"archive" => ( $da['archive'] == 'yes' ) ? '1' : '',
	];

	foreach ($da['fields'] as $field) {
		$item[$field['field']] = $field['value'];
	}

	$cols = [];
	foreach ($header as $field) {
		$cols[] = [
			"field" => $field,
			"td"    => $columnDefs[$field]['td']($item)
		];
	}
	$item['cols'] = $cols;

	$list[] = $item;

}

$lists = [
	"list"    => $list,
	"page"    => $xlists['page'],
	"pageall" => $xlists['pageall'],
	"ord"     => $xlists['ord'],
	"desc"    => $xlists['desc'],
	"header"  => $header,
	"columns" => $columns,
	"valuta"  => $valuta,
	"show_marga" => $show_marga
];

file_put_contents($rootpath."/cash/price_lists.json", json_encode($lists, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));

print json_encode_cyr($lists);

exit();