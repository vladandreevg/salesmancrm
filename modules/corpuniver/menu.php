<?php
/* ============================ */
/*         SalesMan CRM         */
/* ============================ */
/* (C) 2019 Vladislav Andreev   */
/*       SalesMan Project       */
/*         salesman.pro         */
/*          ver. 2019.х         */
/* ============================ */
/*   Developer: Ivan Drachyov   */

//настройки модуля
$mdcset      = $db -> getRow("SELECT * FROM ".$sqlname."modules WHERE mpath = 'corpuniver' and identity = '$identity'");
$mdcsettings = json_decode($mdcset['content'], true);

$modOn = $db -> getOne("SELECT active FROM ".$sqlname."modules WHERE mpath = 'corpuniver' and identity = '$identity'");

$mdcset['mpath'] = 'corpuniver';

if ($modOn == 'on' && ($mdcsettings['MenuTip'] != 'inSub' && !$isMobile)) {

	$menu['corpuniver'] = [
		//идентификатор раздела
		"id"      => 'menucorpuniver',
		//доступ в раздел
		"accesse" => "yes",
		//блок меню
		"main"    => [
			"title"     => $mdcset['title'],
			"icon"      => '<i class="'.$mdcset['icon'].'"></i>',
			"url"       => $mdcset['mpath'],
			"onclick"   => '',
			"class"     => 'hidden-iphone',
			"spanclass" => 'hidden'
		],
		//идентификатор подраздела
		"subid"   => 'menucorpuniver',
		//Подразделы меню "Университет"
		"sub"     => [
			/*[
				"ismenu"  => true,
				"accesse" => "yes",
				"title"   => 'Добавить курс',
				"icon"    => '<i class="icon-plus-circled-1"></i>',
				"url"     => '',
				"onclick" => "editCourse('', 'edit')",
				"class"   => '',
				"type"    => 'corpuniver'
			],*/
			[
				"ismenu"  => true,
				"accesse" => "yes",
				"title"   => 'Курсы',
				"icon"    => '<i class="icon-graduation-cap-1"></i>',
				"url"     => $mdcset['mpath'],
				"onclick" => "",
				"class"   => '',
				"type"    => 'corpuniver'
			]

		]
	];

}
if ($modOn == 'on' && ($mdcsettings['MenuTip'] == 'inSub' || $isMobile)) {

	$wplan = [
		"ismenu"  => true,
		"accesse" => "yes",
		"title"   => $mdcset['title'],
		"icon"    => '<i class="'.$mdcset['icon'].'"></i>',
		"url"     => $mdcset['mpath'],
		"onclick" => "",
		"class"   => '',
		"type"    => 'modules',
		"subsubid"  => 'corpuniver',
		"subsub"  => [
			/*[
				"ismenu"  => true,
				"accesse" => "yes",
				"title"   => 'Добавить курс',
				"icon"    => '<i class="icon-plus-circled-1"></i>',
				"url"     => '',
				"onclick" => "editCourse('', 'edit')",
				"class"   => '',
				"type"    => 'corpuniver'
			],*/
			[
				"ismenu"  => true,
				"accesse" => "yes",
				"title"   => 'Курсы',
				"icon"    => '<i class="icon-graduation-cap-1"></i>',
				"url"     => $mdcset['mpath'],
				"onclick" => "",
				"class"   => '',
				"type"    => 'corpuniver'
			]
		]
	];

	$menu[ 'services' ][ 'sub' ][] = $wplan;

}
