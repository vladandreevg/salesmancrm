<?php
/* ============================ */
/* (C) 2016 Vladislav Andreev   */
/*       SalesMan Project       */
/*        www.isaler.ru         */
/*        ver. 2017.x           */
/* ============================ */

if (!$userRights['budjet']) {

	$folder_ex = $db -> getOne("SELECT idcategory FROM ".$sqlname."file_cat WHERE title = 'Бюджет' and identity = '$identity'");
	$fff       = " and idcategory != '$folder_ex'";

}
?>
<form id="pageform" name="pageform">
	<input id="page" name="page" type="hidden" value="1">
	<input id="idcat" name="idcat" type="hidden" value="">
	<input id="fcount" name="fcount" type="hidden" value="">
	<input id="ord" name="ord" type="hidden" value="fid">
	<input id="tuda" name="tuda" type="hidden" value="">

	<span id="flyitbox"></span>
	
	<DIV class="mainbg nano" id="lmenu">
		
		<div class="nano-content mt5">

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
						<div class="smalltxt gray">По названию, тэгам:</div>
					</div>
				</DIV>
			</div>

		</div>
		
	</DIV>

</form>