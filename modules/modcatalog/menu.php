<?php
/* ============================ */
/*         SalesMan CRM         */
/* ============================ */
/* (C) 2018 Vladislav Andreev   */
/*       SalesMan Project       */
/*        www.isaler.ru         */
/*        ver. 2018.x           */
/* ============================ */

//настройки модуля
$msettings            = $db -> getOne( "SELECT settings FROM ".$sqlname."modcatalog_set WHERE identity = '$identity'" );
$msettings            = json_decode( (string)$msettings, true );
$msettings['mcSklad'] = 'yes';

$mcOn               = $db -> getOne( "SELECT active FROM ".$sqlname."modules WHERE mpath = 'modcatalog' and identity = '$identity'" );
$msettings['mpath'] = 'modcatalog';

if ( $mcOn == 'on' && $msettings['mcMenuTip'] == 'inMain' ) {

	$menu['sklad'] = [
		//идентификатор раздела
		"id"      => 'menuSklad',
		//доступ в раздел
		"accesse" => "yes",
		//блок меню
		"main"    => [
			"title"     => 'Каталог-Склад',
			"icon"      => '<i class="icon-archive"></i>',
			"url"       => $msettings['mpath'].'#catalog',
			"onclick"   => '',
			"class"     => 'hidden-netbook visible-normal visible-mobile',
			"spanclass" => 'hidden-normal'
		],
		//идентификатор подраздела
		"subid"   => 'menusklad',
		//Подразделы меню "Склад"
		"sub"     => [
			[
				"ismenu"  => true,
				"accesse" => "yes",
				"title"   => 'Позиции каталога',
				"icon"    => '<i class="icon-archive"></i>',
				"url"     => $msettings['mpath'].'#catalog',
				"onclick" => "",
				"class"   => '',
				"type"    => 'sklad'
			],
			[
				"ismenu"  => true,
				"accesse" => ($msettings['mcUseOrder'] == 'yes' && in_array( $iduser1, (array)$msettings['mcCoordinator'] )) ? "yes" : "",
				"title"   => 'Добавить позицию',
				"icon"    => '<i class="icon-archive"><i class="sup icon-plus-circled"></i></i>',
				"url"     => '',
				"onclick" => "doLoad('modules/modcatalog/form.modcatalog.php?action=edit')",
				"class"   => '',
				"type"    => 'sklad'
			],
			[
				"ismenu"  => true,
				"accesse" => ($msettings['mcUseOrder'] == 'yes' && in_array( $iduser1, (array)$msettings['mcCoordinator'] )) ? "yes" : "",
				"title"   => 'Ордера прихода/расхода',
				"icon"    => '<i class="icon-doc-text-inv"></i>',
				"url"     => $msettings['mpath'].'#order',
				"onclick" => "",
				"class"   => '',
				"type"    => 'sklad'
			],
			[
				"ismenu"  => true,
				"accesse" => "yes",
				"title"   => 'Заявки',
				"icon"    => '<i class="icon-doc-text-inv"></i>',
				"url"     => $msettings['mpath'].'#zayavka',
				"onclick" => "",
				"class"   => '',
				"type"    => 'sklad'
			],
			[
				"ismenu"  => true,
				"accesse" => "yes",
				"title"   => 'Добавить заявку',
				"icon"    => '<i class="icon-doc-text-inv"><i class="sup icon-plus-circled"></i></i>',
				"url"     => '',
				"onclick" => "doLoad('modules/modcatalog/form.modcatalog.php?action=editzayavka')",
				"class"   => '',
				"type"    => 'sklad'
			],
			[
				"ismenu"  => true,
				"accesse" => "yes",
				"title"   => 'Добавить заявку на поиск',
				"icon"    => '<i class="icon-doc-text-inv"><i class="sup icon-plus-circled"></i></i>',
				"url"     => '',
				"onclick" => "doLoad('modules/modcatalog/form.modcatalog.php?action=editzayavka&tip=cold')",
				"class"   => '',
				"type"    => 'sklad'
			],
			[
				"ismenu"  => false,
				"accesse" => "yes",
				"class"   => "p0 header",
				"content" => '<hr>'
			],
			[
				"ismenu"  => true,
				"accesse" => "yes",
				"title"   => 'Предложения',
				"icon"    => '<i class="icon-doc-text-inv"></i>',
				"url"     => $msettings['mpath'].'#offer',
				"onclick" => "",
				"class"   => '',
				"type"    => 'sklad'
			],
			[
				"ismenu"  => true,
				"accesse" => ($msettings['mcUseOrder'] == 'yes' && in_array( $iduser1, (array)$msettings['mcCoordinator'] )) ? "yes" : "",
				"title"   => 'Добавить предложение',
				"icon"    => '<i class="icon-doc-text-inv"><i class="sup icon-plus-circled"></i></i>',
				"url"     => '',
				"onclick" => "doLoad('modules/modcatalog/form.modcatalog.php?action=editoffer')",
				"class"   => '',
				"type"    => 'sklad'
			],
		]
	];

}
if ( $mcOn == 'on' && $msettings['mcMenuTip'] == 'inSub' ) {

	$skld = [
		"ismenu"  => true,
		"accesse" => "yes",
		"title"   => 'Каталог-склад',
		"icon"    => '<i class="icon-archive"></i>',
		"url"     => $msettings['mpath'].'#catalog',
		"onclick" => "",
		"class"   => '',
		"type"    => 'modules',
		"subsubid"  => 'modcatalog',
		"subsub"  => [
			[
				"ismenu"  => true,
				"accesse" => "yes",
				"title"   => 'Позиции каталога',
				"icon"    => '<i class="icon-archive"></i>',
				"url"     => $msettings['mpath'].'#catalog',
				"onclick" => "",
				"class"   => '',
				"type"    => 'sklad'
			],
			[
				"ismenu"  => true,
				"accesse" => ($msettings['mcUseOrder'] == 'yes' && in_array( $iduser1, (array)$msettings['mcCoordinator'] )) ? "yes" : "",
				"title"   => 'Добавить позицию',
				"icon"    => '<i class="icon-archive"><i class="sup icon-plus-circled"></i></i>',
				"url"     => '',
				"onclick" => "doLoad('modules/modcatalog/form.modcatalog.php?action=edit')",
				"class"   => '',
				"type"    => 'sklad'
			],
			[
				"ismenu"  => true,
				"accesse" => ($msettings['mcUseOrder'] == 'yes' && in_array( $iduser1, (array)$msettings['mcCoordinator'] )) ? "yes" : "",
				"title"   => 'Ордера прихода/расхода',
				"icon"    => '<i class="icon-doc-text-inv"></i>',
				"url"     => $msettings['mpath'].'#order',
				"onclick" => "",
				"class"   => '',
				"type"    => 'sklad'
			],
			[
				"ismenu"  => true,
				"accesse" => "yes",
				"title"   => 'Заявки',
				"icon"    => '<i class="icon-doc-text-inv"></i>',
				"url"     => $msettings['mpath'].'#zayavka',
				"onclick" => "",
				"class"   => '',
				"type"    => 'sklad'
			],
			[
				"ismenu"  => true,
				"accesse" => "yes",
				"title"   => 'Добавить заявку',
				"icon"    => '<i class="icon-doc-text-inv"><i class="sup icon-plus-circled"></i></i>',
				"url"     => '',
				"onclick" => "doLoad('modules/modcatalog/form.modcatalog.php?action=editzayavka')",
				"class"   => '',
				"type"    => 'sklad'
			],
			[
				"ismenu"  => true,
				"accesse" => "yes",
				"title"   => 'Добавить заявку на поиск',
				"icon"    => '<i class="icon-doc-text-inv"><i class="sup icon-plus-circled"></i></i>',
				"url"     => '',
				"onclick" => "doLoad('modules/modcatalog/form.modcatalog.php?action=editzayavka&tip=cold')",
				"class"   => '',
				"type"    => 'sklad'
			],
			[
				"ismenu"  => false,
				"accesse" => "yes",
				"class"   => "p0 header",
				"content" => '<hr>'
			],
			[
				"ismenu"  => true,
				"accesse" => "yes",
				"title"   => 'Предложения',
				"icon"    => '<i class="icon-doc-text-inv"></i>',
				"url"     => $msettings['mpath'].'#offer',
				"onclick" => "",
				"class"   => '',
				"type"    => 'sklad'
			],
			[
				"ismenu"  => true,
				"accesse" => ($msettings['mcUseOrder'] == 'yes' && in_array( $iduser1, (array)$msettings['mcCoordinator'] )) ? "yes" : "",
				"title"   => 'Добавить предложение',
				"icon"    => '<i class="icon-doc-text-inv"><i class="sup icon-plus-circled"></i></i>',
				"url"     => '',
				"onclick" => "doLoad('modules/modcatalog/form.modcatalog.php?action=editoffer')",
				"class"   => '',
				"type"    => 'sklad'
			],
		]
	];

	$menu['services']['sub'][] = $skld;

}