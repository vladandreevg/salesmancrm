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

$rootpath = dirname( __DIR__, 2 );

include $rootpath."/inc/config.php";
include $rootpath."/inc/dbconnector.php";
include $rootpath."/inc/auth.php";
include $rootpath."/inc/func.php";
include $rootpath."/inc/settings.php";
include $rootpath."/inc/language/".$language.".php";

$thisfile = basename( __FILE__ );

$tip = $_REQUEST['tip'];

$hide = ($tip != '') ? 'hidden' : '';

$sort = get_people($iduser1, "yes");

function cmp($a, $b) { return $a['day'] - $b['day']; }


$status = $_REQUEST['status'];

$query = "
SELECT
	".$sqlname."entry.ide as ide,
	".$sqlname."entry.datum as datum,
	".$sqlname."entry.datum_do as datum_do,
	".$sqlname."entry.pid as pid,
	".$sqlname."entry.clid as clid,
	".$sqlname."entry.did as did,
	".$sqlname."entry.content as content,
	".$sqlname."entry.status as status,
	".$sqlname."entry.iduser as iduser,
	".$sqlname."entry.autor as autor,
	".$sqlname."clientcat.title as client,
	".$sqlname."personcat.person as person,
	".$sqlname."dogovor.title as deal,
	".$sqlname."user.title as user
FROM ".$sqlname."entry
	LEFT JOIN ".$sqlname."user ON ".$sqlname."entry.iduser = ".$sqlname."user.iduser
	LEFT JOIN ".$sqlname."personcat ON ".$sqlname."entry.pid = ".$sqlname."personcat.pid
	LEFT JOIN ".$sqlname."clientcat ON ".$sqlname."entry.clid = ".$sqlname."clientcat.clid
	LEFT JOIN ".$sqlname."dogovor ON ".$sqlname."entry.did = ".$sqlname."dogovor.did
WHERE
	".$sqlname."entry.ide > 0 and
	".$sqlname."entry.status = '$status' and
	".$sqlname."entry.iduser IN (".implode(",", $sort).") and
	".$sqlname."entry.identity = '$identity'
ORDER BY ".$sqlname."entry.datum DESC LIMIT 10";

$result = $db -> getAll($query);
foreach ($result as $data) {

	$entry[] = [
		"day"    => round(diffDate2($data['datum']), 0),
		"clid"   => $data['clid'],
		"client" => $data['client'],
		"ide"    => $data['ide'],
		"user"   => $data['user'],
		"autor"  => current_user($data['autor'])
	];

}


	foreach ($entry as $k => $item) {

		$color = "green";
		$znak  = ($item['day'] < 0) ? "- " : "";

		print '
		<div class="flex-container float border-bottom p10 ha">
			
			<div class="flex-string w70">
			
				<div class="fs-12 Bold blue mb5">№'.$item['ide'].'</div>
				<div class="'.$color.'"><b>'.$znak.abs($item['day']).'</b> дн.</div>
				
			</div>
			<div class="flex-string float">
			
				<div class="mb5 black fs-12">
					<span class="ellipsis hand" title="'.$item['client'].'" onclick="openClient(\''.$item['clid'].'\')">
						<i class="icon-building gray"></i>'.$item['client'].'
					</span>
				</div>
				<div class="mb5">
					<span class="ellipsis" title="'.$item['user'].'"><i class="icon-user-1 blue"></i>'.$item['user'].'</span>
				</div>
				<div class="mb5">
					<span class="ellipsis" title="'.$item['autor'].'"><i class="icon-user-1 gray2"></i>'.$item['autor'].'</span>
				</div>
				
			</div>
			<div class="flex-string w30 hand" onclick="editDogovor(\''.$item['ide'].'\',\'fromentry\'); return false;" title="Преобразовать в Сделку">
				&nbsp;<i class="icon-briefcase-1 blue clearevents"></i>&nbsp;
			</div>
			<div class="flex-string w30 hand" onclick="editDogovor(\''.$item['ide'].'\',\'status\'); return false;" title="Закрыть обращение">
				&nbsp;<i class="icon-block red clearevents"></i>&nbsp;
			</div>
			
		</div>
		';

	}

	if (empty($entry) ) {
		print '
		<div class="flex-container p10 gray">
	
			<div class="flex-string">
				Нет информации
			</div>
	
		</div>
		';
	}