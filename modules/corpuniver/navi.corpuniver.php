<?php
/* ============================ */
/* (C) 2019 Vladislav Andreev   */
/*       SalesMan Project       */
/*        www.isaler.ru         */
/*        ver. 2019.x           */
/* ============================ */
/*   Developer: Ivan Drachyov   */
?>
<DIV class="mainbg nano" id="lmenu">

	<div class="nano-content mt5">

		<form id="pageform" name="pageform">
			<input type="hidden" name="page" id="page" value="1">
			<input id="idcat" name="idcat" type="hidden" value="">
			<input id="tag" name="tag" type="hidden" value="">

			<div class="contaner p5">

				<div class="relativ">
					<B class="shad uppercase"><i class="icon-menu blue"></i>&nbsp;Разделы</B>
					<?php if ($isadmin == "on" || in_array($iduser1, (array)$mdcsettings['Editor'])) { ?>
						<div class="pull-right">
							<A href="javascript:void(0)" onclick="editCourse('','cat.list');" class="gray" title="Редактор разделов"><i class="icon-pencil blue"></i></A>
						</div>
					<?php } ?>
				</div>
				<div class="nano relativ kbfolder mt20" style="max-height: 50vh;">
					<div id="folder" class="ifolder nano-content paddleft10" style="min-height:200px;">
						<a href="javascript:void(0)" data-id="" data-title="" class="fol_it"><i class="icon-folder blue"></i>&nbsp;[все]</a>
						<?php
						$result = $db -> getAll("SELECT * FROM ".$sqlname."corpuniver_course_cat WHERE subid = '0' and identity = '$identity' ORDER by title");
						foreach ($result as $data) {

							print '<a href="javascript:void(0)" class="fol mt5" data-id="'.$data['id'].'" data-title="'.$data['title'].'"><span class="ellipsis"><i class="icon-folder-open blue"></i>&nbsp;'.$data['title'].'</span></a>';

							$res = $db -> getAll("SELECT * FROM ".$sqlname."corpuniver_course_cat WHERE subid = '".$data['id']."' and identity = $identity ORDER by title");
							foreach ($res as $da) {

								print '<a href="javascript:void(0)" class="fol" data-id="'.$da['id'].'" data-title="'.$da['title'].'"><span class="ellipsis pl20"><div class="strelka w5 mr10"></div><i class="icon-folder gray2"></i>&nbsp;'.$da['title'].'</span></a>';

							}

						}
						?>
					</div>
				</div>

			</div>

			<div class="contaner p5 search">

				<div><i class="icon-search blue"></i>&nbsp;<b class="shad">Поиск</b></div>

				<div class="paddtop10">
					<div class="relativ">
						<input id="word" name="word" type="text" placeholder="Впишите запрос" class="searchwordinput" data-func="preconfigpage">
						<span class="idel">
							<i title="Очистить" onclick="$('#word').val('');" class="icon-block red hand"></i>
						</span>
					</div>
					<div class="smalltxt gray">По названию</div>
				</DIV>

			</div>

			<?php if ($isadmin == "yes" || in_array($iduser1, $mdcsettings['Editor'])) { ?>
				<div class="contaner p5 addbutton">
					<div class="div-center">
						<A href="javascript:void(0)" onclick="editCourse('','edit');" class="button full"><i class="icon-plus-circled white"></i>Добавить курс</A>
					</div>
				</div>
			<?php } ?>

		</form>

	</div>

</DIV>