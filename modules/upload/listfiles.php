<?php

var_dump(debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS));
exit;

$year = date('Y');
$y1 = $year - 1;
$y2 = $year + 1;

global $rootpath;
require_once $rootpath."/inc/head.php";
flush();
if (!$userRights['budjet']) {

    $folder_ex = $db -> getOne("SELECT idcategory FROM ".$sqlname."file_cat WHERE title = 'Бюджет' and identity = '$identity'");
    $fff       = " and idcategory != '$folder_ex'";

}
?>
<div class="contaner p5" id="pricecategory">

				<div class="mb10">
					<B class="shad"><i class="icon-menu blue"></i>&nbsp;ПАПКИ</B>
					<div class="pull-aright inline">
						<A href="javascript:void(0)" onclick="editUpload('','cat.list','')" class="gray" title="Редактор папок"><i class="icon-pencil blue"></i></A>
					</div>
				</div>

				<div class="nano" style="height: 70vh;">

					<div id="folder" class="ifolder nano-content" style="min-height: 200px;">
						<?php
						if (!$userRights['budjet']) {

							$folder_ex = $db -> getOne("SELECT idcategory FROM ".$sqlname."file_cat WHERE title='Бюджет' and identity = '$identity'");
							$fff       = " and idcategory != '".$folder_ex."'";

						}

						print '<a href="javascript:void(0)" data-id="" data-title="" class="fol_it block"><i class="icon-folder blue"></i>&nbsp;[все]</a>';

						$result = $db -> query("SELECT * FROM ".$sqlname."file_cat WHERE subid = '0' and idcategory>0 ".$fff." and identity = '$identity' ORDER by title");
						while ($data = $db -> fetch($result)) {

							print '<a href="javascript:void(0)" data-id="'.$data['idcategory'].'" data-title="'.$data['title'].'" class="fol block mt5 mb5"><div class="ellipsis"><i class="icon-folder-open blue"></i>'.($data['shared'] == 'yes' ? '&nbsp;<i class="icon-users-1 sup green" title="Общая папка"></i> ' : '').$data['title'].'</div></a>';

							$res = $db -> query("SELECT * FROM ".$sqlname."file_cat WHERE subid = '".$data['idcategory']."' and idcategory > 0 ".$fff." and identity = '$identity' ORDER by title");
							while ($da = $db -> fetch($res)) {

								print '<a href="javascript:void(0)" class="fol block" data-id="'.$da['idcategory'].'" data-title="'.$da['title'].'"><span class="ellipsis pl10"><div class="strelka w5 mr10"></div><i class="icon-folder gray2"></i>'.($da['shared'] == 'yes' ? '&nbsp;<i class="icon-users-1 sup green" title="Общая папка"></i> ' : '').'&nbsp;'.$da['title'].'</span></a>';

							}

						}
						?>
					</div>

				</div>

			</div>