<?php
/* ============================ */
/* (C) 2024 Vladislav Andreev   */
/*       SalesMan Project       */
/*        www.isaler.ru         */
/*        ver. 2024.x           */
/* ============================ */

use Salesman\Upload;

if (!$userRights['budjet']) {

	$folder_ex = $db -> getOne("SELECT idcategory FROM ".$sqlname."file_cat WHERE title = 'Бюджет' and identity = '$identity'");
	$fff       = " and idcategory != '$folder_ex'";

}
?>
<form id="pageform" name="pageform">
	<input id="page" name="page" type="hidden" value="1">
	<input id="idcategory" name="idcategory" type="hidden" value="">
	<input id="fcount" name="fcount" type="hidden" value="">
	<input id="ord" name="ord" type="hidden" value="fid">
	<input id="tuda" name="tuda" type="hidden" value="">

	<span id="flyitbox"></span>
	
	<DIV class="mainbg nano" id="lmenu">
		
		<div class="nano-content mt5">

			<div class="contaner" id="category">

				<div class="mb10">
					<B class="shad"><i class="icon-menu blue"></i>&nbsp;ПАПКИ</B>
					<div class="pull-aright inline">
						<A href="javascript:void(0)" onclick="editUpload('','cat.list','')" class="gray" title="Редактор папок"><i class="icon-pencil blue"></i></A>
					</div>
				</div>

				<div class="nano1 noscroll" style="height: 70vh;">

					<div id="zfolder" class="ifolder nano-content" style="min-height: 200px;">
						<?php
						$folder_ex = 0;
						if (!$userRights['budjet']) {

							$folder_ex = (int)$db -> getOne("SELECT idcategory FROM ".$sqlname."file_cat WHERE title='Бюджет' and identity = '$identity'");
							$fff       = " and idcategory != '".$folder_ex."'";

						}
						print '<div data-id="" data-title="" class="xfolder fol_it block hand Bold"><i class="icon-folder blue"></i>&nbsp;[все]</div>';
						?>

						<?php
						$catalog = Upload::getCatalogLine();
						foreach ($catalog as $key => $value) {

							if($folder_ex > 0 && $value['id']== $folder_ex){
								continue;
							}

							$padding = 'mt5 Bold';

							if((int)$value['level'] == 1){
								$padding = 'pl20';
							}
							elseif((int)$value['level'] > 1){
								$x = 20 + (int)$value['level'] * 10;
								$padding = "pl{$x} ml15 fs-09";
							}

							$folder  = ($value['level'] == 0 ? 'icon-folder-open deepblue' : ($value['level'] == 1 ? 'icon-folder-open blue' : 'icon-folder broun'));

							print '
							<div class="pt5">
								<div class="xfolder fol block ellipsis hand '.$padding.'" data-id="'.$value['id'].'" data-title="'.$value['title'].'">
									<div class="strelka w5 ml10 mr10"></div><i class="'.$folder.'"></i>'.($value['shared'] == 'yes' ? '&nbsp;<i class="icon-users-1 sup green" title="Общая папка"></i> ' : '').'&nbsp;'.$value['title'].'
								</div>
							</div>
							';

						}
						?>
					</div>

				</div>

			</div>

			<div class="contaner p5">

				<div class="ydropDown border flyit" data-id="ftype">
					<span>Тип файла</span>
					<span class="ydropText"></span>
					<i class="icon-angle-down pull-aright arrow"></i>
					<div class="yselectBox fly ftype" data-id="ftype">
						<div class="ydropString yRadio ellipsis">
							<label><input type="radio" name="ftype" id="ftype" value="" data-title="Любые" class="hidden"><i class="icon-folder blue"></i>&nbsp;Любые</label>
						</div>
						<div class="ydropString yRadio ellipsis">
							<label><input type="radio" name="ftype" id="ftype" value="img" data-title="Изображения" class="hidden"><?= get_icon2("x.png") ?>&nbsp;Изображения</label>
						</div>
						<div class="ydropString yRadio ellipsis">
							<label><input type="radio" name="ftype" id="ftype" value="doc" data-title="Документы" class="hidden"><?= get_icon2("x.docx") ?>&nbsp;Документы</label>
						</div>
						<div class="ydropString yRadio ellipsis">
							<label><input type="radio" name="ftype" id="ftype" value="pdf" data-title="Документы" class="hidden"><?= get_icon2("x.pdf") ?>&nbsp;Файлы PDF</label>
						</div>
						<div class="ydropString yRadio ellipsis">
							<label><input type="radio" name="ftype" id="ftype" value="zip" data-title="Архивы" class="hidden"><?= get_icon2("x.zip") ?>&nbsp;Архивы</label>
						</div>
					</div>
				</div>

			</div>

			<div class="contaner p5">
				<A href="javascript:void(0)" onclick="editUpload('','add','')" class="button orangebtn" style="display:block; margin:0; padding:10px"><i class="icon-plus-circled"></i>&nbsp;&nbsp;Загрузить файл</A>
			</div>

			<div class="contaner p5 hidden">
				<A href="javascript:void(0)" onclick="editUpload('','cat.list','')" class="abutton" style="display:block">Редактор папок</A>
			</div>

			<div class="contaner p5">
				<div><i class="icon-filter blue"></i>&nbsp;<b class="shad">Поиск</b></div>
				<DIV class="pad5">
					<div class="relativ">
						<input id="word" name="word" type="text" placeholder="Впишите запрос" class="searchwordinput" data-func="preconfigpage">
						<span class="idel"><a href="javascript:void(0)" title="Найти" onclick="preconfigpage();"><i class="icon-search blue"></i></a></span>
						<div class="smalltxt gray">По названию, описанию</div>
					</div>
				</DIV>
			</div>

		</div>
		
	</DIV>

</form>