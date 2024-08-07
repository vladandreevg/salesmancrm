<?php
/* ============================ */
/*         SalesMan CRM         */
/* ============================ */
/* (C) 2018 Vladislav Andreev   */
/*       SalesMan Project       */
/*        www.isaler.ru         */
/*        ver. 2018.x           */
/* ============================ */

/**
 * Построение меню
 */

use Salesman\User;

global $export_lock;
global $dblSettings;
global $sip_active;
global $sip_tip;
global $ymEnable;

global $otherSettings;

$current = $db -> getOne("SELECT current FROM {$sqlname}ver ORDER BY id DESC LIMIT 1");

$zuser = "Пользователь";

$root = dirname( __DIR__ );

//Пункты в ращделе "Связи"
$shares = 0;
if ($otherSettings['partner']) {
	$shares++;
}
if ($otherSettings['concurent']) {
	$shares++;
}
if ($otherSettings['contractor']) {
	$shares++;
}

//print_r($userSettings);

$sharesCount = 0;
if ($userSettings['dostup']['partner'] == 'on') {
	$sharesCount++;
}
if ($userSettings['dostup']['contractor'] == 'on') {
	$sharesCount++;
}
if ($userSettings['dostup']['concurent'] == 'on') {
	$sharesCount++;
}

$acs = $GLOBALS['acs'];

//var_dump($userRights);

//Формируем структуру меню
$menu = [
	//раздел "Клиенты"
	"client"    => [
		"name"    => "client",
		//идентификатор раздела
		"id"      => 'menuClients',
		//доступ в раздел
		"accesse" => ($acs['clientbase'] || $isadmin == 'on') ? "yes" : "",
		//блок меню
		"main"    => [
			"title"     => $lang['face']['ClientsName'][0],
			"icon"      => '<i class="icon-users-1"></i>',
			"url"       => 'clients#'.$userSettings['menuClient'],
			"class"     => '',
			"spanclass" => 'hidden-ipad hidden-netbook visible-iphone',
			"spanstyle" => '',
			"divclass"  => ''
		],
		//идентификатор подраздела
		"subid"   => 'menuclients',
		//Подразделы меню "Клиенты"
		"sub"     => [
			//нормальный раздел
			[
				"ismenu"  => true,
				"accesse" => ($userRights['client']['create'] && $otherSettings['expressForm']) || $isadmin == 'on' ? "yes" : "",
				"title"   => $lang['all']['Add']." ".$lang['all']['Express'],
				"icon"    => '<i class="icon-building"><i class="sup icon-direction"></i></i>',
				"url"     => '',
				"onclick" => 'expressClient()',
				"class"   => '',
				"type"    => 'client'
			],
			//разделитель
			[
				"ismenu"  => false,
				"accesse" => "yes",
				"class"   => "p0 header",
				"content" => '<hr>'
			],
			[
				"ismenu"  => true,
				"accesse" => "yes",
				"title"   => $lang['all']['List'].' '.$lang['face']['ClientsName'][1],
				"icon"    => '<i class="icon-building"></i>',
				"url"     => 'clients#'.$userSettings['menuClient'],
				"onclick" => '',
				"class"   => '',
				"type"    => 'client'
			],
			[
				"ismenu"  => true,
				"accesse" => "yes",
				"title"   => $lang['all']['List'].' '.$lang['face']['ContactsName'][1],
				"icon"    => '<i class="icon-users-1"></i>',
				"url"     => 'contacts#'.$userSettings['menuPerson'],
				"onclick" => '',
				"class"   => '',
				"type"    => 'contact'
			],
			[
				"ismenu"  => false,
				"accesse" => "yes",
				"class"   => "p0 header",
				"content" => '<hr>'
			],
			[
				"ismenu"  => true,
				"accesse" => ($userRights['contractors'] || $isadmin == 'on') ? "yes" : "",
				"title"   => $lang['face']['Agents'],
				"icon"    => '<i class="icon-flag"></i>',
				"url"     => 'clients#other',
				"onclick" => '',
				"class"   => '',
				"type"    => 'client'
			],
			[
				"ismenu"  => false,
				"accesse" => "yes",
				"class"   => "p0 header",
				"content" => '<hr>'
			],
			[
				"ismenu"  => true,
				"accesse" => ($userRights['client']['create'] || $isadmin == 'on') ? "yes" : "",
				"title"   => $lang['all']['Add'].' '.$lang['face']['ClientName'][1],
				"icon"    => '<i class="icon-building"><i class="sup icon-plus-circled"></i></i>',
				"url"     => '',
				"onclick" => "editClient('','add');",
				"class"   => '',
				"type"    => 'client'
			],
			[
				"ismenu"  => true,
				"accesse" => ($userRights['person']['create'] || $isadmin == 'on') ? "yes" : "",
				"title"   => $lang['all']['Add'].' '.$lang['face']['ContactName'][0],
				"icon"    => '<i class="icon-user-1"><i class="sup icon-plus-circled"></i></i>',
				"url"     => '',
				"onclick" => "editPerson('','add');",
				"class"   => '',
				"type"    => 'contact'
			],
			[
				"ismenu"  => false,
				"accesse" => ($isEntry == 'on') ? "yes" : "",
				"class"   => "p0 header",
				"content" => '<hr>'
			],
			[
				"ismenu"  => false,
				"accesse" => ($isEntry == 'on') ? "yes" : "",
				"class"   => "header",
				"content" => $lang['all']['Entry'][1]
			],
			[
				"ismenu"  => true,
				"accesse" => ($isEntry == 'on') ? "yes" : "",
				"title"   => $lang['all']['Entry'][1],
				"icon"    => '<i class="icon-phone-squared"></i>',
				"url"     => 'entry',
				"onclick" => '',
				"class"   => '',
				"type"    => 'client'
			],
			[
				"ismenu"  => true,
				"accesse" => ($isEntry == 'on') ? "yes" : "",
				"title"   => $lang['all']['Add'].' '.$lang['all']['Entry'][0],
				"icon"    => '<i class="icon-phone-squared"><i class="sup icon-plus-circled"></i></i>',
				"url"     => '',
				"onclick" => "editEntry('','edit');",
				"class"   => '',
				"type"    => 'client'
			],
			[
				"ismenu"  => false,
				"accesse" => ($isadmin == 'on' || $tipuser == 'Администратор' || $userRights['export'] || $userRights['import']) ? "yes" : "",
				"class"   => "header hidden-iphone",
				"content" => $lang['all']['Import'].'/'.$lang['all']['Export']
			],
			[
				"ismenu"  => true,
				"accesse" => ($isadmin == 'on' || $tipuser == 'Администратор' || $userRights['import']) ? "yes" : "",
				"title"   => $lang['all']['Import'].' '.$lang['face']['ClientsName'][1],
				"icon"    => '<i class="icon-database"></i>',
				"url"     => '',
				"onclick" => "doLoad('/content/helpers/client.import.php?action=import');",
				"class"   => 'hidden-iphone',
				"type"    => 'client'
			],
			[
				"ismenu"  => false,
				"accesse" => $userRights['import'] ? "yes" : "",
				"class"   => "header p0 hidden-iphone",
				"content" => '<hr>'
			],
			[
				"ismenu"  => true,
				"accesse" => $userRights['export'] && empty($export_lock) ? "yes" : "",
				"title"   => $lang['all']['Export'].' '.$lang['face']['ClientsName'][1],
				"icon"    => '<i class="icon-upload-1"><i class="sup icon-forward-1"></i></i>',
				"url"     => '',
				"onclick" => "doLoad('/content/helpers/client.export.php?datatype=client&action=get_export');",
				"class"   => 'hidden-iphone',
				"type"    => 'client'
			],
			[
				"ismenu"  => true,
				"accesse" => $userRights['export'] && empty($export_lock) ? "yes" : "",
				"title"   => $lang['all']['Export'].' '.$lang['face']['ContactsName'][1],
				"icon"    => '<i class="icon-upload-1"><i class="sup icon-forward-1"></i></i>',
				"url"     => '',
				"onclick" => "doLoad('/content/helpers/client.export.php?datatype=person&action=get_export');",
				"class"   => 'hidden-iphone',
				"type"    => 'contact'
			],
			[
				"ismenu"  => true,
				"accesse" => $userRights['export'] && !empty($export_lock) ? "yes" : "",
				"title"   => $lang['all']['Export'].' '.$lang['face']['ClientsName'][1],
				"icon"    => '<i class="icon-upload-1"><i class="sup icon-forward-1"></i></i>',
				"url"     => '',
				"onclick" => "doLoad('/content/ajax/export.lock.php?url=client.export.php&datatype=client&action=get_export');",
				"class"   => 'hidden-iphone',
				"type"    => 'client'
			],
			[
				"ismenu"  => true,
				"accesse" => $userRights['export'] && !empty($export_lock) ? "yes" : "",
				"title"   => $lang['all']['Export'].' '.$lang['face']['ContactsName'][1],
				"icon"    => '<i class="icon-upload-1"><i class="sup icon-forward-1"></i></i>',
				"url"     => '',
				"onclick" => "doLoad('/content/ajax/export.lock.php?url=client.export.php&datatype=client&action=get_export');",
				"class"   => 'hidden-iphone',
				"type"    => 'contact'
			],
			[
				"ismenu"  => false,
				"accesse" => ($dblSettings['active'] == 'yes' && in_array($iduser1, (array)$dblSettings['Coordinator'])) ? "yes" : "",
				"class"   => "header p0 hidden-iphone",
				"content" => '<hr>'
			],
			[
				"ismenu"  => true,
				"accesse" => ($dblSettings['active'] == 'yes' && in_array($iduser1, (array)$dblSettings['Coordinator'])) ? "yes" : "",
				"title"   => $lang['face']['Doubles'],
				"icon"    => '<i class="icon-columns"></i>',
				"url"     => '',
				"onclick" => "doubleModule.modal()",
				"class"   => 'hidden-iphone',
				"type"    => 'client'
			]
		]
	],
	//раздел "Продажи"
	"deal"      => [
		"name"    => "deal",
		//идентификатор раздела
		"id"      => 'menuDeal',
		//доступ в раздел
		"accesse" => $acs['dogbase'] ? "yes" : "",
		//блок меню
		"main"    => [
			"title"     => $lang['face']['DealsName'][0],
			"icon"      => '<i class="icon-briefcase-1"></i>',
			"url"       => 'deals#'.$userSettings['menuDeal'],
			"onclick"   => '',
			"class"     => '',
			"spanclass" => 'hidden-ipad hidden-netbook visible-iphone'
		],
		//идентификатор подраздела
		"subid"   => 'menudeals',
		//Подразделы меню "Продажи"
		"sub"     => [
			//нормальный раздел
			[
				"ismenu"  => true,
				"accesse" => ($userRights['deal']['create'] || $isadmin == 'on') ? "yes" : "",
				"title"   => $lang['all']['Add']." ".$lang['face']['DealName'][3],
				"icon"    => '<i class="icon-briefcase"><i class="sup icon-plus-circled"></i></i>',
				"url"     => '',
				"onclick" => "editDogovor('','add');",
				"class"   => '',
				"type"    => 'deal'
			],
			//разделитель
			[
				"ismenu"  => false,
				"accesse" => ($userRights['deal']['create'] || $isadmin == 'on') ? "yes" : "",
				"class"   => "p0 header",
				"content" => '<hr>'
			],
			[
				"ismenu"  => true,
				"accesse" => "yes",
				"title"   => $lang['all']['Active'][2]." ".$lang['face']['DealsName'][0],
				"icon"    => '<i class="icon-briefcase-1"></i>',
				"url"     => 'deals#'.$userSettings['menuDeal'],
				"onclick" => '',
				"class"   => '',
				"type"    => 'deal'
			],
			[
				"ismenu"  => true,
				"accesse" => ($userRights['alls'] || $isadmin == 'on') ? "yes" : "",
				"title"   => $lang['all']['All']." ".$lang['face']['DealsName'][0],
				"icon"    => '<i class="icon-users-1"></i>',
				"url"     => 'deals#all',
				"onclick" => '',
				"class"   => '',
				"type"    => 'deal'
			],
			[
				"ismenu"  => false,
				"accesse" => ($otherSettings['concurent']) ? "yes" : "",
				"class"   => "p0 header",
				"content" => '<hr>'
			],
			[
				"ismenu"  => true,
				"accesse" => ($otherSettings['credit']) ? "yes" : "",
				"title"   => $lang['docs']['AddedInvoices'],
				"icon"    => '<i class="icon-rouble"></i>',
				"url"     => 'contract#payment',
				"onclick" => "",
				"class"   => '',
				"type"    => 'deal'
			],
			[
				"ismenu"  => true,
				"accesse" => ($otherSettings['contract']) ? "yes" : "",
				"title"   => $lang['docs']['Doc'][1],
				"icon"    => '<i class="icon-doc-text-inv"></i>',
				"url"     => 'contract#contract',
				"onclick" => "",
				"class"   => '',
				"type"    => 'contract'
			],
			[
				"ismenu"  => true,
				"accesse" => ($complect_on == 'yes') ? "yes" : "",
				"title"   => $lang['face']['ControlPoints'],
				"icon"    => '<i class="icon-check"></i>',
				"url"     => 'cpoint',
				"onclick" => "",
				"class"   => '',
				"type"    => 'deal'
			],
			[
				"ismenu"  => true,
				"accesse" => "yes",
				"title"   => $lang['face']['Ankets'],
				"icon"    => '<i class="icon-doc-inv-alt"></i>',
				"url"     => '',
				"onclick" => '$anketa.modal()',
				"class"   => '',
				"type"    => 'deal'
			],
			[
				"ismenu"  => false,
				"accesse" => ($isadmin == 'on' || $tipuser == 'Администратор' || $userRights['export'] || $userRights['import']) ? "yes" : "",
				"class"   => "p0 header hidden-iphone",
				"content" => '<hr>'
			],
			[
				"ismenu"  => false,
				"accesse" => ($isadmin == 'on' || $tipuser == 'Администратор' || $userRights['export'] || $userRights['import']) ? "yes" : "",
				"class"   => "header hidden-iphone",
				"content" => $lang['all']['Import'].' '.$lang['all']['Export']
			],
			[
				"ismenu"  => true,
				"accesse" => ($isadmin == 'on' || $tipuser == 'Администратор' || $userRights['import']) ? "yes" : "",
				"title"   => $lang['all']['Import'].' '.$lang['face']['DealsName'][1],
				"icon"    => '<i class="icon-database"></i>',
				"url"     => '',
				"onclick" => "doLoad('/content/helpers/deal.import.php?action=import');",
				"class"   => 'hidden-iphone',
				"type"    => 'deal'
			],
			[
				"ismenu"  => true,
				"accesse" => ($isadmin == 'on' || $tipuser == 'Администратор' || $userRights['export']) ? "yes" : "",
				"title"   => $lang['all']['Export'].' '.$lang['face']['DealsName'][1],
				"icon"    => '<i class="icon-upload-1"><i class="sup icon-forward-1"></i></i>',
				"url"     => '',
				"onclick" => "doLoad('/content/helpers/deal.export.php?action=get_export');",
				"class"   => 'hidden-iphone',
				"type"    => 'deal'
			]
		]
	],
	//раздел "Календарь"
	"calendar"  => [
		"name"    => "calendar",
		//идентификатор раздела
		"id"      => 'menuCalendar',
		//доступ в раздел
		"accesse" => "yes",
		//блок меню
		"main"    => [
			"title"     => $lang['face']['BusName'][0],
			"icon"      => '<i class="icon-calendar-1"></i>',
			"url"       => 'calendar',
			"onclick"   => '',
			"class"     => '',
			"spanclass" => 'hidden-ipad hidden-netbook visible-iphone'
		],
		//идентификатор подраздела
		"subid"   => 'menucalendar',
		//Подразделы меню "Календарь"
		"sub"     => [
			[
				"ismenu"  => true,
				"accesse" => "yes",
				"title"   => $lang['all']['Add'].' '.$lang['face']['TodoName'][0],
				"icon"    => '<i class="icon-plus"></i>',
				"url"     => '',
				"onclick" => "addTask();",
				"class"   => '',
				"type"    => 'calendar'
			],
			[
				"ismenu"  => true,
				"accesse" => $userSettings['historyAddBlock'] == 'yes' ? null : "yes",
				"title"   => $lang['all']['Add'].' '.$lang['face']['ActName'][0],
				"icon"    => '<i class="icon-clock"></i>',
				"url"     => '',
				"onclick" => "addHistory();",
				"class"   => '',
				"type"    => 'calendar'
			],
			[
				"ismenu"  => $script !== 'index.php' && $script !== 'calendar.php',
				"accesse" => "yes",
				"title"   => $lang['face']['weekcalendar'],
				"icon"    => '<i class="icon-calendar-inv"></i>',
				"url"     => '',
				"onclick" => 'getWeekCalendar()',
				"class"   => '',
				"type"    => 'calendar'
			],
			[
				"ismenu"  => true,
				"accesse" => "yes",
				"title"   => $lang['face']['TodosName'][0].', '.$lang['face']['BusName'][0],
				"icon"    => '<i class="icon-calendar-1"></i>',
				"url"     => 'calendar',
				"onclick" => '',
				"class"   => '',
				"type"    => 'calendar'
			],
			[
				"ismenu"  => true,
				"accesse" => "yes",
				"title"   => $lang['face']['TodosName'][0].' '.$lang[ 'all' ][ 'All' ],
				"icon"    => '<i class="icon-calendar-1"></i>',
				"url"     => 'todo',
				"onclick" => '',
				"class"   => '',
				"type"    => 'todo'
			],
			[
				"ismenu"  => true,
				"accesse" => "yes",
				"title"   => $lang['all']['History'].' '.$lang['face']['ActsName'][1],
				"icon"    => '<i class="icon-clock"></i>',
				"url"     => 'history',
				"onclick" => '',
				"class"   => '',
				"type"    => 'calendar'
			],
			[
				"ismenu"  => true,
				"accesse" => ($sip_active == 'yes' && in_array($sip_tip, $sipHasCDR)) ? "yes" : "",
				"title"   => $lang['all']['History'].' '.$lang['all']['Call'][2],
				"icon"    => '<i class="icon-phone"></i>',
				"url"     => 'callhistory',
				"onclick" => "",
				"class"   => '',
				"type"    => 'calendar'
			],
			[
				"ismenu"  => true,
				"accesse" => "yes",
				"title"   => $lang['face']['BusWeekPrint'],
				"icon"    => '<i class="icon-doc-text-inv"></i>',
				"url"     => '/content/helpers/day.calendar.php',
				"target"  => '_blank',
				"onclick" => "",
				"class"   => 'hidden-iphone',
				"type"    => 'calendar'
			]
		]
	],
	//раздел "Отчеты"
	"analitics" => [
		"name"    => "analitics",
		//идентификатор раздела
		"id"      => 'menuReports',
		//доступ в раздел
		"accesse" => ($acs_analitics == 'on') ? "yes" : "",
		//блок меню
		"main"    => [
			"title"     => $lang['face']['Reports'],
			"icon"      => '<i class="icon-chart-line"></i>',
			"url"       => 'report',
			"onclick"   => '',
			"class"     => 'hidden-iphone',
			"spanclass" => 'hidden-ipad hidden-netbook'
		],
		//идентификатор подраздела
		"subid"   => 'menuanalitics',
		//Подразделы меню "Отчеты"
		"sub"     => [
			[
				"ismenu"  => true,
				"accesse" => "yes",
				"title"   => $lang['all']['All'].' '.$lang['face']['Reports'],
				"icon"    => '<i class="icon-chart-area"></i>',
				"url"     => 'report',
				"onclick" => "",
				"class"   => '',
				"type"    => 'analitics'
			]
		]
	],
	//раздел "Маркетинг"
	"marketing" => [
		"name"    => "marketing",
		//идентификатор раздела
		"id"      => 'menuMarketing',
		//доступ в раздел
		"accesse" => ($acs['marketing'] && ($mailout == 'yes' || $userRights['group'] || $modLeadActive == 'on')) ? "yes" : "",
		//блок меню
		"main"    => [
			"title"     => $lang['face']['Marketing'],
			"icon"      => '<i class="icon-shuffle-1"></i>',
			"url"       => '',
			"onclick"   => '',
			"class"     => '',
			"spanclass" => 'hidden visible-iphone'
		],
		//идентификатор подраздела
		"subid"   => 'menumarketing',
		//Подразделы меню "Маркетинг"
		"sub"     => [
			[
				"ismenu"  => true,
				"accesse" => ($mailout == 'yes' && $isCloud != "true" && $acs_maillist == 'on') ? "yes" : "",
				"title"   => $lang['face']['MailerIn'],
				"icon"    => '<i class="icon-mail-alt"></i>',
				"url"     => 'maillist',
				"onclick" => "",
				"class"   => 'hidden-iphone',
				"type"    => 'marketing'
			],
			[
				"ismenu"  => true,
				"accesse" => $userRights['group'] ? "yes" : "",
				"title"   => $lang['face']['Group'],
				"icon"    => '<i class="icon-sitemap"></i>',
				"url"     => 'group',
				"onclick" => "",
				"class"   => 'hidden-iphone',
				"type"    => 'marketing'
			],
			[
				"ismenu"   => true,
				"accesse"  => ($modLeadActive == 'on') ? "yes" : "",
				"title"    => $lang['face']['LeadManager'],
				"icon"     => '<i class="icon-sort-alt-down"></i>',
				"url"      => 'leads#lists',
				"onclick"  => "",
				"class"    => '',
				"type"     => 'marketing',
				"subsubid" => 'marketinglist',
				"subsub"   => [
					[
						"ismenu"  => true,
						"accesse" => "yes",
						"title"   => $lang['face']['Leads'],
						"icon"    => '<i class="icon-doc-text-inv"></i>',
						"url"     => 'leads#lists',
						"onclick" => "",
						"class"   => '',
						"type"    => 'marketing'
					],
					[
						"ismenu"  => true,
						"accesse" => "yes",
						"title"   => $lang['face']['UtmGen'],
						"icon"    => '<i class="icon-share"></i>',
						"url"     => 'leads#utms',
						"onclick" => "",
						"class"   => '',
						"type"    => 'marketing'
					]
				]
			]
		]
	],
	//раздел "Связи"
	"agents"    => [
		"name"    => "agents",
		//идентификатор раздела
		"id"      => 'menuContractors',
		//доступ в раздел
		"accesse" => ($sharesCount > 0 && $shares > 0) ? "yes" : "",
		//блок меню
		"main"    => [
			"title"     => $lang['face']['Agents'],
			"icon"      => '<i class="icon-share"></i>',
			"url"       => 'clients#contractor',
			"onclick"   => '',
			"class"     => '',
			"spanclass" => 'hidden visible-iphone'
		],
		//идентификатор подраздела
		"subid"   => 'menucontractors',
		//Подразделы меню "Связи"
		"sub"     => [
			[
				"ismenu"  => true,
				"accesse" => ($userSettings['dostup']['contractor'] == 'on' && $otherSettings['contractor']) ? "yes" : "",
				"title"   => $lang['agents']['Contractor'][1],
				"icon"    => '<i class="icon-flag"></i>',
				"url"     => 'clients#contractor',
				"onclick" => "",
				"class"   => '',
				"type"    => 'contractors'
			],
			[
				"ismenu"  => true,
				"accesse" => ($userSettings['dostup']['partner'] == 'on' && $otherSettings['partner']) ? "yes" : "",
				"title"   => $lang['agents']['Partner'][1],
				"icon"    => '<i class="icon-flag"></i>',
				"url"     => 'clients#partner',
				"onclick" => "",
				"class"   => '',
				"type"    => 'contractors'
			],
			[
				"ismenu"  => true,
				"accesse" => ($userSettings['dostup']['concurent'] == 'on' && $otherSettings['concurent']) ? "yes" : "",
				"title"   => $lang['agents']['Concurent'][1],
				"icon"    => '<i class="icon-flag"></i>',
				"url"     => 'clients#concurent',
				"onclick" => "",
				"class"   => '',
				"type"    => 'contractors'
			]
		]
	],
	//раздел "Финансы"
	"finance"   => [
		"name"    => "finance",
		//идентификатор раздела
		"id"      => 'menuFinance',
		//доступ в раздел
		"accesse" => $acs['finance'] ? "yes" : "",
		//блок меню
		"main"    => [
			"title"     => $lang['face']['Finance'],
			"icon"      => '<i class="icon-rouble"></i>',
			"url"       => 'contract#payment',
			"onclick"   => '',
			"class"     => '',
			"spanclass" => 'hidden visible-iphone'
		],
		//идентификатор подраздела
		"subid"   => 'menufinance',
		//Подразделы меню "Связи"
		"sub"     => [
			[
				"ismenu"  => true,
				"accesse" => ($otherSettings['credit']) ? "yes" : "",
				"title"   => $lang['docs']['AddedInvoices'],
				"icon"    => '<i class="icon-rouble"></i>',
				"url"     => 'contract#payment',
				"onclick" => "",
				"class"   => '',
				"type"    => 'finance'
			],
			[
				"ismenu"  => true,
				"accesse" => ($otherSettings['printInvoice'] && stripos($tipuser, 'Руководитель') !== false) ? "yes" : "",
				"title"   => $lang['docs']['Act'][1],
				"icon"    => '<i class="icon-doc-inv"></i>',
				"url"     => 'contract#akt',
				"onclick" => "",
				"class"   => '',
				"type"    => 'finance'
			],
			[
				"ismenu"  => true,
				"accesse" => ($otherSettings['contract']) ? "yes" : "",
				"title"   => $lang['docs']['Doc'][1],
				"icon"    => '<i class="icon-doc-text-inv"></i>',
				"url"     => 'contract#contract',
				"onclick" => "",
				"class"   => '',
				"type"    => 'finance'
			],
			[
				"ismenu"  => true,
				"accesse" => stripos($tipuser, 'Руководитель') !== false ? "yes" : "",
				"title"   => $lang['docs']['PlanProdazh'],
				"icon"    => '<i class="icon-chart-bar-1"></i>',
				"url"     => 'saleplan',
				"onclick" => "",
				"class"   => '',
				"type"    => 'finance'
			],
			[
				"ismenu"  => true,
				"accesse" => "yes",
				"title"   => $lang['docs']['Metrics'],
				"icon"    => '<i class="icon-sliders"></i>',
				"url"     => 'metrics',
				"onclick" => "",
				"class"   => '',
				"type"    => 'finance'
			],
			[
				"ismenu"  => false,
				"accesse" => $userRights['budjet'] ? "yes" : "",
				"class"   => "header",
				"content" => $lang['finance']['Budjet']
			],
			[
				"ismenu"  => true,
				"accesse" => $userRights['budjet'] && $userSettings['dostup']['budjet']['chart'] == 'yes' ? "yes" : "",
				"title"   => $lang['face']['DohodRashod'],
				"icon"    => '<i class="icon-chart-bar"></i>',
				"url"     => 'finance#budjet',
				"onclick" => "",
				"class"   => '',
				"type"    => 'finance'
			],
			[
				"ismenu"  => true,
				"accesse" => $userRights['budjet'] && $userSettings['dostup']['budjet']['statement'] ? "yes" : "",
				"title"   => $lang['face']['JournalStatement'],
				"icon"    => '<i class="icon-article-alt"></i>',
				"url"     => 'finance#statement',
				"onclick" => "",
				"class"   => '',
				"type"    => 'finance'
			],
			[
				"ismenu"  => true,
				"accesse" => $userRights['budjet'] && $userSettings['dostup']['budjet']['agents'] ? "yes" : "",
				"title"   => $lang['face']['SuppliersPartners'],
				"icon"    => '<i class="icon-users-1"><i class="icon-rouble sup fs-05"></i></i>',
				"url"     => 'finance#agents',
				"onclick" => "",
				"class"   => '',
				"type"    => 'finance'
			],
			[
				"ismenu"  => true,
				"accesse" => $userRights['budjet'] && $userSettings['dostup']['budjet']['journal'] ? "yes" : "",
				"title"   => $lang['face']['JournalRashod'],
				"icon"    => '<i class="icon-list-alt"></i>',
				"url"     => 'finance#journal',
				"onclick" => "",
				"class"   => '',
				"type"    => 'finance'
			],
			[
				"ismenu"  => true,
				"accesse" => ($userRights['budjet'] && $otherSettings['credit'] && $userSettings['dostup']['budjet']['payment']) ? "yes" : "",
				"title"   => $lang['face']['JournalPayment'],
				"icon"    => '<i class="icon-rouble"></i>',
				"url"     => 'finance#invoices',
				"onclick" => "",
				"class"   => '',
				"type"    => 'finance'
			]
		]
	],
	//раздел "Почтовик. Новый"
	"mailer"    => [
		"name"    => "mailer",
		//идентификатор раздела
		"id"      => 'menuMailer',
		//доступ в раздел
		"accesse" => $ymEnable ? "yes" : "",
		//блок меню
		"main"    => [
			"title"     => $lang['ymail']['Module'],
			"icon"      => '<i class="icon-mail-alt"></i>',
			"url"       => 'mailer#conversation',
			"onclick"   => '',
			"class"     => 'hidden-iphone',
			"spanclass" => 'hidden visible-iphone'
		],
		//идентификатор подраздела
		"subid"   => 'menuymail',
		//Подразделы меню "Почтовик"
		"sub"     => [
			[
				"ismenu"  => true,
				"accesse" => "yes",
				"title"   => $lang['ymail']['ComposeMail'],
				"icon"    => '<i class="icon-plus-circled-1"></i>',
				"url"     => '',
				"onclick" => '$mailer.compose()',
				"class"   => '',
				"type"    => 'mailer'
			],
			[
				"ismenu"  => false,
				"accesse" => "yes",
				"class"   => "p0 header",
				"content" => "<hr>"
			],
			[
				"ismenu"  => true,
				"accesse" => "yes",
				"title"   => $lang['ymail']['IncomingMail'],
				"icon"    => '<i class="icon-mail relati"><i class=" icon-forward-1 green my2"></i></i>',
				"url"     => 'mailer#inbox',
				"onclick" => "",
				"class"   => '',
				"type"    => 'mailer'
			],
			[
				"ismenu"  => true,
				"accesse" => "yes",
				"title"   => $lang['ymail']['SendedMail'],
				"icon"    => '<i class="icon-mail relati"><i class=" icon-reply green my2"></i></i>',
				"url"     => 'mailer#sended',
				"onclick" => "",
				"class"   => '',
				"type"    => 'mailer'
			],
			[
				"ismenu"  => true,
				"accesse" => "yes",
				"title"   => $lang['ymail']['DraftMail'],
				"icon"    => '<i class="icon-doc-text"></i>',
				"url"     => 'mailer#draft',
				"onclick" => "",
				"class"   => '',
				"type"    => 'mailer'
			],
			[
				"ismenu"  => false,
				"accesse" => "yes",
				"class"   => "p0 header",
				"content" => "<hr>"
			],
			[
				"ismenu"  => true,
				"accesse" => "yes",
				"title"   => $lang['ymail']['TrashMail'],
				"icon"    => '<i class="icon-trash"></i>',
				"url"     => 'mailer#trash',
				"onclick" => "",
				"class"   => '',
				"type"    => 'mailer'
			]
		]
	],
	//раздел "Сервисы"
	"services"  => [
		"name"    => "services",
		//идентификатор раздела
		"id"      => 'menuServises',
		//доступ в раздел
		"accesse" => "yes",
		//блок меню
		"main"    => [
			"title"     => $lang['face']['Servises'],
			"icon"      => '<i class="icon-ellipsis-vert"></i>',
			"url"       => '',
			"onclick"   => '',
			"class"     => '',
			"spanclass" => 'hidden visible-iphone'
		],
		//идентификатор подраздела
		"subid"   => 'menuservises',
		//Подразделы меню "Сервисы"
		"sub"     => [
			[
				"ismenu"  => true,
				"accesse" => "yes",
				"title"   => $lang['face']['FileList'],
				"icon"    => '<i class="icon-floppy"></i>',
				"url"     => 'uploads',
				"onclick" => "",
				"class"   => '',
				"type"    => 'services'
			],
			[
				"ismenu"  => true,
				"accesse" => ($otherSettings['price'] && $acs_price == 'on') ? "yes" : "",
				"title"   => $lang['face']['PriceList'],
				"icon"    => '<i class="icon-dollar"></i>',
				"url"     => 'price',
				"onclick" => "",
				"class"   => '',
				"type"    => 'services'
			],
			[
				"ismenu"  => true,
				"accesse" => "yes",
				"title"   => $lang['face']['KnowlegeBase'],
				"icon"    => '<i class="icon-graduation-cap"></i>',
				"url"     => 'knowledgebase',
				"onclick" => "",
				"class"   => '',
				"type"    => 'services'
			],
			[
				"ismenu"  => true,
				"accesse" => $otherSettings['comment'] ? "yes" : "",
				"title"   => $lang['face']['Comments'],
				"icon"    => '<i class="icon-chat"></i>',
				"url"     => 'comments',
				"onclick" => "",
				"class"   => '',
				"type"    => 'services'
			]
		]
	]
];

/**
 * Добавим экспресс-отчеты
 */
if ($acs_analitics == 'on') {

	//базовые экспресс-отчеты
	$express = [
		"ent-activitiesByTime.php"   => [
			"title" => "Активности по времени",
			"icon"  => '<i class="icon-clock"></i>'
		],
		"ent-voronkaComplex.php"     => [
			"title" => "Комплексная воронка",
			"icon"  => '<i class="icon-filter"></i>'
		],
		"ent-SalesFunnel.php"        => [
			"title" => "Воронка продаж",
			"icon"  => '<i class="icon-chart-line"></i>'
		],
		"ent-newDeals.php"           => [
			"title" => "Новые сделки",
			"icon"  => '<i class="icon-briefcase-1"></i>'
		],
		"ent-dealsPerDay.php"        => [
			"title" => "Сделки в работе",
			"icon"  => '<i class="icon-tools"></i>'
		],
		"ent-newClients.php"         => [
			"title" => "Новые клиенты",
			"icon"  => '<i class="icon-building"></i>'
		],
		"ent-PaymentsByUser.php"     => [
			"title" => "Оплаты по сотрудникам",
			"icon"  => '<i class="icon-rouble"></i>'
		],
		"ent-InvoiceStateByUser.php" => [
			"title" => "Статус выставленных счетов",
			"icon"  => '<i class="icon-rouble"></i>'
		],
		"sklad-InOut.php"            => [
			"title" => "Cклад. Движение по ордерам",
			"icon"  => '<i class="icon-archive"></i>'
		]
	];

	$ee = array_keys($express);

	$rep = [];

	$result = $db -> query("SELECT * FROM {$sqlname}reports WHERE file IN (".yimplode(",", $ee, "'").") and identity = '$identity' ORDER by title");
	while ($da = $db -> fetch($result)) {

		$show = true;

		$r     = $db -> getRow("SELECT roles, users FROM {$sqlname}reports WHERE rid = '$da[rid]' AND identity = '".$identity."'");
		$roles = yexplode(",", $r["roles"]);
		$users = yexplode(",", $r["users"]);

		if (!empty($roles) || !empty($users)) {
			$show = in_array( $tipuser, $roles ) || in_array( $iduser1, $users );
		}

		if ($show && file_exists('reports/'.$da['file'])) {
			$rep[] = [
				"ismenu"  => true,
				"accesse" => "yes",
				"title"   => $express[ $da['file'] ]['title'],
				"icon"    => $express[ $da['file'] ]['icon'],
				"url"     => "",
				"onclick" => "getSwindow('reports/".$da['file']."', '".$express[ $da['file'] ]['title']."')",
				"class"   => '',
				"type"    => 'analitics'
			];
		}

	}

	if (!empty($rep)) {

		array_unshift($rep, [
			"ismenu"  => false,
			"accesse" => "yes",
			"class"   => "p0 header",
			"content" => '<hr>'
		]);

		$menu['analitics']['sub'] = array_merge($menu['analitics']['sub'], $rep);

	}

}

/**
 * Добавим меню модулей в основное меню
 * Меню д.б. подготовлено для вливания в массив
 *
 */
$resultm = $db -> query("SELECT * FROM {$sqlname}modules WHERE active = 'on' and mpath NOT IN ('wcdialog','entry') and identity = '$identity' ORDER by id");
while ($data = $db -> fetch($resultm)) {

	if (file_exists("modules/".$data['mpath']."/menu.php")) {
		include $root."/modules/".$data['mpath']."/menu.php";
	}

	if ($data['mpath'] === 'modworkplan') {
		include $root."/modules/workplan/menu.php";
	}

}

/**
 * Добавим плагины
 */
$pluginList       = json_decode(file_get_contents($root."/plugins/map.json"), true);
$pluginListCastom = (file_exists($root."/plugins/map.castom.json")) ? json_decode(file_get_contents($root."/plugins/map.castom.json"), true) : [];

$pluginList = array_merge($pluginList, $pluginListCastom);

$plg = [];

// пройдем папку с плагинами
clearstatcache();

$folders = scandir("plugins", 1);

//print_r($folders);

$list = [];
foreach ($folders as $folder) {

	if ( !in_array( $folder, [
			".",
			".."
		] ) && file_exists( "plugins/{$folder}/plugin.json" ) ) {

			$pluginAbout = json_decode(str_replace([
				"  ",
				"\t",
				"\n",
				"\r"
			], "", file_get_contents("plugins/{$folder}/plugin.json")), true);

			$pluginList[ $pluginAbout['package'] ] = [
				"name"      => $pluginAbout['name'],
				"icon"      => $pluginAbout['icon'],
				"interface" => $pluginAbout['menu'] ? "yes" : "no",
				"url"       => "plugins/{$folder}/",
			];

		}

}

$plugin = $db -> query("SELECT * FROM {$sqlname}plugins WHERE active = 'on' and identity = '$identity' ORDER by name");
while ($data = $db -> fetch($plugin)) {

	if (file_exists("plugins/".$data['name'])) {

		$pth = 'plugins/'.$data['name'].'/data/'.$fpath;

		$settings = file_exists($pth.'settings.json') ? json_decode(file_get_contents($pth.'settings.json'), true) : [];

		$ax = file_exists($pth.'access.json') ? json_decode(file_get_contents($pth.'access.json'), true) : [];

		$access = (!empty($settings['access'])) ? $settings['access'] : $ax;

		//print $data['name']."\n";
		//print_r($access);
		//print "\n-------------\n";

		if (
			(
				(!empty($access) && in_array($iduser1, $access)) ||
				(empty($access) && $isadmin === 'on')
			) &&
			$pluginList[ $data['name'] ]['interface'] === 'yes'
		) {

			$plg[] = [
				"ismenu"  => true,
				"accesse" => "yes",
				"title"   => $pluginList[ $data['name'] ]['name'],
				"icon"    => '<i class="'.$pluginList[ $data['name'] ]['icon'].'"></i>',
				//"url"     => $pliginList[ $data['name'] ]['url'],
				"onclick" => 'openPlugin(\''.$pluginList[ $data['name'] ]['url'].'\')',
				//"target"  => "_blank",
				"class"   => "plugin--".$data['name'],
				"type"    => 'services'
			];

		}

	}

}

if (!empty($plg)) {
	$menu['services']['sub'][] = [
		"ismenu"   => true,
		"accesse"  => "yes",
		"title"    => $lang['face']['Plugins'],
		"icon"     => '<i class="icon-cog-alt orange"></i>',
		"url"      => '',
		"onclick"  => "",
		"class"    => 'hidden-iphone',
		"type"     => 'plugins',
		"subsubid" => 'pluginlist',
		"subsub"   => $plg
	];
}

/**
 * Переместим меню сервисов в конец
 */
$tmp['services'] = $menu['services'];
unset($menu['services']);
$menu = array_merge($menu, $tmp);

/**
 * Финальный массив для шаблонизатора Mustache
 */
$menu = [
	"list"  => array_values($menu),
	"items" => array_keys($menu)
];

/**
 * Загружаем шаблон
 */
$template  = file_get_contents($root."/inc/menu.mustache");
$mtemplate = file_get_contents($root."/inc/menu.mobile.mustache");

/**
 * Подключаем шаблонизатор
 */
//require_once "opensource/Mustache/Autoloader.php";
Mustache_Autoloader ::register();

/**
 * Составляем дерево основного меню
 */
$m    = new Mustache_Engine();
$html = $m -> render($template, $menu);

/**
 * Составляем дерево мобильного меню
 */
$m     = new Mustache_Engine();
$mhtml = $m -> render($mtemplate, $menu);
?>
<div class="menu--block relativ" data-step="1" data-intro="<h1>Главное меню разделов и действий.</h1>Здесь вы можете перейти к полному списку Клиентов, Контактов, Сделок и другим" data-position="bottom-middle-aligned">

	<div id="menu" class="disable--select">

		<ul class="hov" onclick="window.location.assign('/')" title="<?= $lang['all']['Desktop'] ?>">
			<li>
				<div class="mnu home logo-large" onmouseover="logoSwitch()" onmouseout="logoSwitch()">
					<div id="logo" style="background: url(<?= $logo ?>) no-repeat center center; background-size:contain;"></div>
					<div id="home" class="center hidden" title="<?= $lang['all']['Desktop'] ?>">
						<i class="icon-home white"></i></div>
				</div>
				<div class="mnu logo-small">
					<div id="home" class="center" title="<?= $lang['all']['Desktop'] ?>">
						<i class="icon-home white"></i>
					</div>
				</div>
			</li>
		</ul>

		<?php
		if ($hidemenu != 'yes' && (!$isMobile || $detect -> isTablet())) {
			print $html;
			?>
			<ul class="hov hidden visible-ipad visible-iphone">
				<li>
					<div class="mnu" title="<?= $lang['face']['Servises'] ?>"><i class="icon-menu white"></i></div>
					<ul class="mob--right">
						<?php if ( $ymEnable ) { ?>
							<li>
								<a class="navlink" href="mailer#conversation"><span><i class="icon-mail"></i></span><?= $lang['ymail']['Module'] ?></a>
							</li>
						<?php } ?>
						<?php if ($mailout == 'yes') { ?>
							<li>
								<a class="navlink" href="/maillist"><span><i class="icon-mail-alt"></i></span><?= $lang['face']['MailerIn'] ?></a>
							</li>
						<?php } ?>
						<?php if ($userRights['group']) { ?>
							<li><a class="navlink" href="/group"><span><i class="icon-th-list"></i></span><?= $lang['face']['Group'] ?></a></li>
						<?php } ?>
						<li>
							<a href="/reports" class="navlink"><span><i class="icon-chart-area"></i></span><?= $lang['face']['Reports'] ?></a>
						</li>
						<li>
							<a class="navlink" href="/files"><span><i class="icon-floppy"></i></span><?= $lang['face']['FileList'] ?></a>
						</li>
						<li>
							<a class="navlink" href="/knowledgebase"><span><i class="icon-graduation-cap"></i></span><?= $lang['face']['KnowlegeBase'] ?></a>
						</li>
						<?php if ($otherSettings['comment']) { ?>
							<li><a class="navlink" href="/comments"><span><i class="icon-chat"></i></span><?= $lang['face']['Comments'] ?></a></li>
						<?php } ?>
						<?php if ($otherSettings['credit']) { ?>
							<li><a class="navlink" href="/price"><span><i class="icon-dollar"></i></span><?= $lang['face']['PriceList'] ?></a></li>
						<?php } ?>
						<li><a class="navlink" href="/clients#contractor"><span><i class="icon-flag"></i></span><?= $lang['agents']['Contractor'][1] ?></a></li>
						<?php if ($otherSettings['partner']) { ?>
							<li><a class="navlink" href="/clients#partner"><span><i class="icon-flag"></i></span><?= $lang['agents']['Partner'][1] ?></a></li>
						<?php } ?>
						<?php if ($otherSettings['concurent']) { ?>
							<li><a class="navlink" href="/clients#concurent"><span><i class="icon-flag"></i></span><?= $lang['agents']['Concurent'][1] ?></a></li>
						<?php } ?>
					</ul>
				</li>
			</ul>
			<?php
		}
		?>

	</div>

	<div id="menumobile" class="mnu menuToggler hov visible-iphone disable--select">
		<i class="icon-menu"></i>
	</div>

	<div class="menu--header"></div>

	<div class="menu--mobile hidden">

		<?= ($isMobile ? $mhtml : '') ?>

		<div class="space-60"></div>

	</div>

	<div class="menu--right relativ">

		<!--блок уведомлений-->
		<div class="menu--notify popblock" data-id="notify">

			<i class="icon-bell-alt"></i>
			<div class="sup hidden">0</div>

			<div class="popblock-menu right p5 fs-09" style="right: -5px;">

				<div class="popblock-items overflow-y notifys yw300" style="max-height: 70vh;"></div>

			</div>

		</div>

		<div id="menuavatar" class="useravatar disable--select relativ" data-step="2" data-intro="<h1>Аватар пользователя.</h1>Кликните мышкой на Аватар, чтобы открыть <b>Меню управления</b>." data-position="bottom-right-aligned">

			<div class="">

				<span class="avatar ml10" style="background: url(<?= $avatar ?>); background-size:cover;"></span>
				<span class="avatar-text hidden-ipad">
					<span class="avatar-user">
						<span class=""><?= current_user($iduser1, 'yes') ?></span>
						<span class="avatar-subtext"><?= $lang['role'][ $tipuser ] ?></span>
					</span>
				</span>
				<span class="avatar-icon"><i class="icon-angle-down"></i></span>

			</div>

			<div class="avatar--menu hidden" data-step="3" data-intro="<h1>Меню управления</h1>Здесь же находится доступ в Панель управления для Настройки системы" data-position="left">

				<?php
				$lica = ($tipuser != 'Администратор') ? User ::userArrayMenu($iduser1) : User ::userArrayMenu(null, 0, null);
				$otdels = $db -> getIndCol("idcategory", "SELECT idcategory, title FROM {$sqlname}otdel_cat WHERE identity = '$identity'");

				$ww = (count($lica) > 1) ? '' : 'one';

				//$isCloud = true;
				//$balance_trial_left = 12;
				?>

				<div class="items <?= $ww ?>">

					<?php
					if (count($lica) > 1) {
						?>
						<div class="item">

							<?php
							if ($tipuser != "Менеджер продаж" || $tipuser != 'Специалист' || $tipuser != 'Поддержка продаж') {
								?>
								<div class="nano">
									<div class="nano-content">

										<div class="title"><?= $lang['face']['User'][1] ?></div>
										<?php
										//Вывод подчиненных
										$usersActive   = '';
										$usersUnActive = '';
										foreach ($lica as $k => $data) {

											if ($data['id'] != $iduser1) {

												$avatar = ($data['avatar'] && file_exists($root."/cash/avatars/".$data['avatar'])) ? "/cash/avatars/".$data['avatar'] : "/assets/images/noavatar.png";

												if ($data['secrty'] == 'yes') {

													$usersActive .= '
													<div class="string iuser" data-id="'.$data['id'].'">
													
														<div class="for--avatar">
															<div class="avatar--micro" style="background: url('.$avatar.'); background-size:cover;"></div>
														</div>
														
														<div onclick="doLoad(\'/content/ajax/user.info.php?iduser='.$data['id'].'\');" title="'.$lang['all']['Show'].'">
															<div class="Bold">'.$data['name'].'</div>
															<div class="fs-07 gray">'.$lang['role'][ $data['tip'] ].'</div>
															'.( (int)$data['otdel'] > 0 ? '<div class="fs-07 ellipsis">'.$otdels[$data['otdel']].'</div>' : '' ).'
														</div>
														
														<div>
															<a href="javascript:void(0)" onclick="asUser(\''.$iduser1.'\',\''.$data['id'].'\')" title="'.$lang['face']['UserReplace'].'" class="gray blue"><i class="icon-users-1 blue"></i></a>
														</div>
														
													</div>';

												}
												else {

													$usersUnActive .= '
												<div class="string">
													
													<div class="for--avatar">
													
														<div class="avatar--micro pull-left mr10" style="background: url('.$avatar.'); background-size:cover;"></div>
													
													</div>
													
													<div onclick="doLoad(\'content/ajax/user.info.php?iduser='.$data['id'].'\');" class="gray" title="'.$lang['face']['UsersNotActive'].'. '.$lang['all']['Show'].'">
														<div class="Bold">'.$data['name'].'</div>
														<div class="fs-07 gray">'.$lang['role'][ $data['tip'] ].'</div>
													</div>
													
													<div>
														<a href="javascript:void(0)" onclick="asUser(\''.$iduser1.'\',\''.$data['id'].'\')" title="'.$lang['face']['UserReplace'].'" class="gray blue"><i class="icon-users-1 blue"></i></a>
													</div>
													
												</div>';

												}

											}

										}
										print $usersActive.($usersUnActive != '' ? '<div class="title">'.$lang['face']['UsersNotActive'].'</div>'.$usersUnActive : '');
										?>
									</div>
								</div>
							<?php } ?>

						</div>
					<?php } ?>
					<div class="item">

						<div class="product p5">

							<a href="<?= $productInfo['site'] ?>" target="blank" title="Перейти на сайт" class="top"><?= $productInfo['name'] ?> v.<b><?= $bdVersion ?></b></a>
							<div class="fs-07"><?= $lang['face']['Build'] ?>:<b class="blue"><?= $sysVersion['build'] ?></b>
							</div>
							<?php
							if ($sysVersion['version'] != $bdVersion) {
								print '
									<div class="warning bgwhite fs-09 flh-11 p5 m0 mt5 text-center">
										<b class="red"><i class="icon-attention-1 red"></i>Внимание!</b><br>
										Дистрибутив не соответствует БД<br>
										Версия в БД: <b>'.$bdVersion.'</b><br>
										Возможно требуется <a href="/_install/update.php" title="Перейти к обновлению">обновление</a>
									</div>';
							}
							?>

						</div>

						<?= $trialCounterSub ?>

						<?php
						if ($isCloud && $isadmin == 'on') {

							print '
									<div class="title noborder pb0">Биллинг</div>
								';

							include $root."/billing/balance.php";

							if ($balance_trial_left >= 0) {
								print '
									<div class="string two noborder cursor-default">
										<span>Пробный период</span>
										<span class="text-right"><b>'.$balance_trial_left.' дн.</b></span>
									</div>';
							}

							if ($balance_bonuses > 0) {
								print '
									<div class="string two noborder cursor-default">
										<span>Бонусные баллы</span>
										<span class="text-right"><b>'.$balance_bonuses.'<i class="icon-gift"></i></b></span>
									</div>';
							}

							print '
								<div class="string two cursor-default">
									<span>Текущий баланс</span>
									<span class="text-right"><b>'.$balance_rub.'<i class="icon-rouble"></i></b></span>
								</div>';

							print '
								<a href="/billing.php" class="string">
									<i class="icon-rouble green"></i>&nbsp;Панель Биллинга&nbsp;
								</a>
								';

						}

						//массив сотрудников, которых замещает текущий сотрудник
						$zamm = $db -> getCol("SELECT iduser FROM {$sqlname}user WHERE zam = '$iduser1' and identity = '$identity'");

						//Вывод замещаемых сотрудников
						if (!empty($zamm)) {

							print '<div class="title noborder pb0">'.$lang['face']['UserReplaced'][1].'</div>';

							for ($z = 0, $zMax = count($zamm); $z < $zMax; $z++) {
								?>
								<div class="string asuser" onclick="asUser('<?= $iduser1 ?>','<?= $zamm[ $z ] ?>')" title="<?= $lang['face']['LogInAs'] ?>">
									<i class="icon-users-1 gray2"></i>&nbsp;<?= current_user($zamm[ $z ], 'yes') ?>
								</div>
								<?php
							}

						}
						if ($_COOKIE['old'] != '') {

							$zuser = '<div class="title noborder">'.$lang['face']['UserReplaced'][0].'</div>';
							?>

							<div class="title noborder pb0">Вернуться</div>
							<div class="string" onclick="asUser('','')" title="<?= $lang['face']['LogInToMyAccount'] ?>">
								<i class="icon-user-1 green" title="<?= $lang['face']['Iam'] ?>"></i><?= current_user($_COOKIE['old'], "yes") ?>
							</div>

							<div class="string hidden">
								<i class="icon-user-1 red" title="<?= $lang['face']['UserReplaced'][2] ?>"></i><?= current_user($iduser1, 'yes') ?>
							</div>

						<?php } ?>

						<div class="title noborder"><?= $lang['face']['Profile'] ?></div>
						<div class="string" onclick="viewUser('<?= $iduser1 ?>')">
							<i class="icon-user-1 blue"></i>&nbsp;<?= $lang['face']['MyProfile'] ?>
						</div>
						<div class="string" onclick="doLoad('/content/ajax/user.settings.php?action=edit')">
							<i class="icon-cog blue"></i>&nbsp;<?= $lang['face']['MySettings'] ?>
						</div>
						<?php if ($isadmin == 'on' || $tipuser == 'Администратор') { ?>
							<div class="title noborder"><?= $lang['face']['Control'] ?></div>
							<a href="/iadmin" class="string"><i class="icon-cog-alt red"></i>&nbsp;<?= $lang['face']['ControlPanel'] ?>&nbsp;</a>
							<?php
							if (!$isCloud) {
								?>
								<div class="string" onclick="doLoad('/content/admin/backup_small.php?path=<?= $url_base ?>&action=bfile')">
									<i class="icon-database broun2"></i>&nbsp;<?= $lang['face']['CreateBackupDB'] ?>&nbsp;
								</div>
								<div class="string" onclick="window.open('/_install/update.php')">
									<i class="icon-cw blue"></i>&nbsp;Раздел обновления&nbsp;
								</div>
								<?php
							}
						}
						?>
						<div class="string Bold" onclick="doLoad('/content/helpers/whatsnew.php')">
							<i class="icon-article-alt orange"></i>&nbsp;Что нового в версии?&nbsp;
						</div>
						<div class="string Bold" onclick="window.open('<?= $productInfo['site'] ?>/docs/')">
							<i class="icon-help-circled bluemint"></i>&nbsp;<?= $lang['face']['Documentation'] ?>&nbsp;
						</div>
						<div class="string exit Bold" onclick="window.location.href = '/login?action=logout'">
							<i class="icon-off blue"></i>&nbsp;<?= $lang['face']['Logout'] ?>&nbsp;
						</div>

					</div>

				</div>

			</div>

		</div>

	</div>

</div>
<div id="menusub"></div>