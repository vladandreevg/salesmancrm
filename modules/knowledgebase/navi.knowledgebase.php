<?php
/* ============================ */
/* (C) 2016 Vladislav Andreev   */
/*       SalesMan Project       */
/*        www.isaler.ru         */
/*        ver. 2017.x           */
/* ============================ */
?>
<DIV class="mainbg nano" id="lmenu">
	<div class="nano-content mt5">

		<form id="pageform" name="pageform">
			<input type="hidden" name="page" id="page" value="1">
			<input id="idcat" name="idcat" type="hidden" value="0">
			<input id="tag" name="tag" type="hidden" value="">

			<div class="contaner p5">
				<div class="margbot10 relativ">
					<B class="shad uppercase"><i class="icon-menu blue"></i>&nbsp;Разделы</B>
					<div class="pull-right">
						<A href="javascript:void(0)" onclick="editKb('','cat.list');" class="gray" title="Редактор разделов"><i class="icon-pencil blue"></i></A>
					</div>
				</div>
				<div class="nano relativ kbfolder" style="max-height: 50vh;">
					<div id="folder" class="ifolder nano-content paddleft10" style="min-height:200px;">
						<a href="javascript:void(0)" data-id="" data-title="" class="fol_it mt5"><i class="icon-folder blue"></i>&nbsp;[все]</a>
						<?php
						use Salesman\Knowledgebase;
						$knowledgebase = new Knowledgebase();
						$list = $knowledgebase -> categorylist(0);
						foreach ($list as $item){

							if( $item['level'] == 0 ) {
								print '<a href="javascript:void(0)" class="fol mt5" data-id="'.$item['id'].'" data-title="'.$item['title'].'"><span class="ellipsis"><i class="icon-folder-open blue"></i>&nbsp;'.$item['title'].'</span></a>';
							}
							else{
								print '<a href="javascript:void(0)" class="fol" data-id="'.$item['id'].'" data-title="'.$item['title'].'"><span class="ellipsis pl20"><div class="strelka w5 mr10"></div><i class="icon-folder gray2"></i>&nbsp;'.$item['title'].'</span></a>';
							}

						}
						?>
					</div>
				</div>
			</div>

			<div class="contaner p5 addbutton">
				<div class="div-center">
					<A href="javascript:void(0)" onclick="editKb('','edit');" class="button full"><i class="icon-plus-circled white"></i>Добавить</A>
				</div>
			</div>

			<div class="contaner p5 search">
				<div><i class="icon-filter blue"></i>&nbsp;<b class="shad">Поиск</b></div>
				<div class="paddtop10">
					<div class="relativ">
						<input id="word" name="word" type="text" placeholder="Впишите запрос" class="searchwordinput" data-func="preconfigpage">
						<span class="idel"><a href="javascript:void(0)" title="Найти" onclick="preconfigpage();"><i class="icon-search blue"></i></a></span>
					</div>
					<div class="smalltxt gray">По названию, содержанию</div>
				</DIV>
			</div>

		</form>

		<div class="contaner p5 flex-container" id="tagbox">
			<?php
			$list = $knowledgebase -> taglist(NULL, true);
			foreach ($list as $data) {

				print '<div class="tags flex-string p2 text-center fs-07" data-tag="'.$data['tag'].'">'.$data['tag'].'<sup class="gray2">'.$data['count'].'</sup></div>';

			}
			?>
		</div>

		<div class="space-50"></div>

	</div>
</DIV>