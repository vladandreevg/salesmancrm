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
			<input id="idcat" name="idcat" type="hidden" value=""/>
			<input id="ord" name="ord" type="hidden" value="title">
			<input id="tuda" name="tuda" type="hidden" value="">

			<div class="contaner p5" id="pricecategory">

				<div class="margbot10">
					<B class="shad"><i class="icon-menu blue"></i>&nbsp;КАТЕГОРИИ</B>
					<a href="javascript:void(0)" onclick="editPrice('','cat.list');" class="pull-right gray"><i class="icon-pencil"></i></a>
				</div>
				<div class="nano" id="nanobody" style="height: 70vh;">

					<div id="folder" class="ifolder nano-content" style="min-height: 200px;">

						<div class="pt5">
							<div class="fol_it block ellipsis hand" data-id="" data-title="">
								<i class="icon-folder blue"></i>&nbsp;[все]
							</div>
						</div>

						<?php

						use Salesman\Price;

						$catalog = Price::getPriceCatalog(0);
						foreach ($catalog as $key => $value) {
							
							$padding = 'mt5 Bold';
							
							if((int)$value['level'] == 1){
								$padding = 'pl20';
							}
							elseif((int)$value['level'] > 1){
								$x = 20 + (int)$value['level'] * 10;
								$padding = "pl{$x} ml15 fs-09";
							}

							$folder  = ($value['level'] == 0 ? 'icon-folder-open deepblue' : ($value['level'] == 1 ? 'icon-folder-open blue' : 'icon-folder broun'));
							//$padding = ($value['level'] == 0 ? 'mt5 Bold' : ($value['level'] == 1 ? 'pl20' : 'pl20 ml15 fs-09'));

							print '
							<div class="pt5">
								<div class="fol block ellipsis hand '.$padding.'" data-id="'.$value['id'].'" data-title="'.$value['title'].'">
									<div class="strelka w5 ml10 mr10"></div><i class="'.$folder.'"></i>&nbsp;'.$value['title'].'
								</div>
							</div>
							';

						}
						?>

					</div>
				</div>

			</div>

			<div class="contaner p5">

				<div><i class="icon-filter blue"></i>&nbsp;<b class="shad">Поиск</b></div>

				<div class="paddtop10">

					<div class="relativ">
						<input id="word" name="word" type="text" placeholder="Впишите запрос" class="searchwordinput" data-func="preconfigpage">
						<span class="idel"><a href="javascript:void(0)" title="Найти" onclick="preconfigpage();"><i class="icon-search blue"></i></a></span>
					</div>
					<div class="smalltxt gray">По названию, описанию, артикулу</div>

				</DIV>

				<hr>

				<div class="pt10 pb10 mm">
					<div class="string">
						<label><input name="old" type="checkbox" id="old" value="yes" onclick="preconfigpage();"/>&nbsp;в т.ч. Архивные</label>
					</div>
					<div class="string">
						<label><input name="oldonly" type="checkbox" id="oldonly" value="yes" onclick="preconfigpage();"/>&nbsp;только Архивные</label>
					</div>
					<hr>
					<div class="string">
						<label><input name="fromcat" type="checkbox" id="fromcat" value="yes" onclick="preconfigpage();"/>&nbsp;только из указанной категории</label>
					</div>
				</div>

			</div>

			<div class="contaner p5">
				<div class="div-center">
					<A href="javascript:void(0)" onclick="editPrice('','edit');" class="button" style="display:block"><i class="icon-plus-circled white"></i>Добавить</A>
				</div>
			</div>

		</form>

	</div>
</DIV>