<?php
/* ============================ */
/*         SalesMan CRM         */
/* ============================ */
/* (C) 2016 Vladislav Andreev   */
/*       SalesMan Project       */
/*        www.isaler.ru         */
/*        ver. 2016.20          */
/* ============================ */

?>
<DIV class="bgray nano1 noscroll" id="lmenu">

	<div class="nano-content mt5">

		<form id="pageform" name="pageform" method="post" enctype="multipart/form-data">
			<input type="hidden" name="idcat" id="idcat" value="">
			<input type="hidden" name="tar" id="tar" value="catalog">
			<input type="hidden" name="page" id="page">
			<input type="hidden" name="ord" id="ord" value="title"/>
			<input type="hidden" name="tuda" id="tuda" value=""/>

			<div class="contaner p5 contaner-zayavka1 razdel hidden">
				<i class="icon-menu blue"></i>&nbsp;<B class="shad">Разделы</B><br><br>

				<A href="#catalog" onclick="razdel('catalog')" class="abutton catalog" style="display:block">Каталог</A>
				<A href="#sklad" onclick="razdel('sklad')" class="abutton sklad" style="display:block">Позиции по складам</A>
				<A href="#zayavka" onclick="razdel('zayavka')" class="abutton zayavka" style="display:block">Заявки</A>
				<A href="#poz" onclick="razdel('poz')" class="abutton poz" style="display:block">Позиции заявок</A>
				<A href="#rez" onclick="razdel('rez')" class="abutton rez" style="display:block">Позиции резерва</A>
				<hr>
				<A href="#offer" onclick="razdel('offer')" class="abutton offer" style="display:block">Предложения</A>
				<A href="#order" onclick="razdel('order')" class="abutton order" style="display:block">Ордеры</A>
				<hr>
				<A href="#move" onclick="razdel('move')" class="abutton move" style="display:block">История перемещений</A>
			</div>

			<div class="contaner p5 contaner-catalog relative hidden">

				<div class="shad">
					<i class="icon-archive blue"></i>&nbsp;КАТЕГОРИИ&nbsp;
				</div>

				<div class="pull-right" id="resizer">
					<a href="javascript:void(0)" onclick="catalogResize()" class="smalltxt blue"><i class="icon-resize-small"></i></a>
				</div>

				<div class="nano mt10" style="max-height: 70vh;" id="catbox">

					<div id="folder" class="ifolder nano-content pl5" style="min-height: 300px;">

						<a href="javascript:void(0)" data-id="" data-title="" class="fol_it block"><i class="icon-folder blue"></i>&nbsp;[все]</a>
						<?php

						use Salesman\Elements;
						use Salesman\Price;

						$catalog = Price::getPriceCatalog();
						foreach ( $catalog as $key => $value ) {

							if (
								empty( $msettings['mcPriceCat'] ) ||
								in_array( $value['id'], (array)$msettings['mcPriceCat'] ) ||
								in_array( $value['sub'], (array)$msettings['mcPriceCat'] )
							) {

								$folder = ($value['level'] == 0 ? 'icon-folder-open deepblue' : ($value['level'] == 1 ? 'icon-folder-open blue' : 'icon-folder broun'));
								$padding = ($value['level'] == 0 ? 'mt5 Bold' : ($value['level'] == 1 ? 'pl20' : 'pl20 ml15 fs-09'));

								print '
								<div class="pt5" title="'.$value['title'].'">
									<a href="javascript:void(0)" class="fol block ellipsis hand '.$padding.'" data-id="'.$value['id'].'" data-title="'.$value['title'].'">
										<div class="strelka w5 mr10"></div><i class="'.$folder.'"></i>&nbsp;'.$value['title'].'
									</a>
								</div>
								';

							}

						}
						?>

					</div>

				</div>

			</div>

			<?php
			if ( in_array( $iduser1, (array)$msettings['mcCoordinator'] ) ) {
				?>
				<div class="contaner contaner-catalog hidden">

					<A href="javascript:void(0)" onclick="doLoad('/modules/modcatalog/form.modcatalog.php?action=edit')" class="button block"><i class="icon-archive"><i class="sup icon-plus-circled"></i></i>&nbsp;&nbsp;Добавить позицию</A>

				</div>
			<?php } ?>

			<div class="contaner p5 contaner-catalog hidden">

				<div class="shad">
					<i class="icon-search blue"></i>&nbsp;ПОИСК&nbsp;
				</div>

				<DIV class="pad5" id="searchbox">

					<div class="relativ cleared paddtop5">
						<input id="wordc" name="wordc" type="text" placeholder="Впишите запрос" class="searchwordinput" data-func="preconfigpage">
						<span class="idel red clearinputs paddtop10" onclick="preconfigpage();"><i class="icon-block-1" title="Очистить"></i></span>
						<div class="smalltxt gray">По названию, описанию, артикулу</div>
					</div>

					<div class="paddtop10 hidden">
						<select name="status" id="status" class="wp100" onchange="preconfigpage()">
							<option value="">--все--</option>
							<option value="0">Продан</option>
							<option value="1">Под заказ</option>
							<option value="2">Ожидается</option>
							<option value="3">В наличии</option>
							<option value="4">Резерв</option>
						</select>
						<div class="smalltxt gray">По статусу</div>
					</div>

				</DIV>
			</div>

			<div class="contaner p5 contaner-order hidden">

				<div class="shad">
					<i class="icon-search blue"></i>&nbsp;ПОИСК&nbsp;
				</div>

				<DIV class="pad5" id="searchboxo">

					<div class="relativ cleared paddtop5">
						<input id="wordo" name="wordo" type="text" placeholder="Впишите запрос" class="searchwordinput" data-func="preconfigpage">
						<span class="idel red clearinputs paddtop10" onclick="preconfigpage();"><i class="icon-block-1" title="Очистить"></i></span>
						<span class="smalltxt gray">По полям "Сдал", "Принял"</span>
					</div>

					<div class="p10 mt20">

						<div class="ellipsis block mb10">
							<label><input class="directt" name="tip[]" type="checkbox" id="tip[]" value="income" onclick="preconfigpage()"/>&nbsp;&nbsp;<i class="icon-down-big smalltxt green"></i>&nbsp;<B class="green">Приходный</B></label>
						</div>

						<div class="ellipsis block">
							<label><input class="directt" name="tip[]" type="checkbox" id="tip[]" value="outcome" onclick="preconfigpage()"/>&nbsp;&nbsp;<i class="icon-up-big smalltxt red"></i>&nbsp;<B class="red">Расходный</B></label>
						</div>

					</div>

				</DIV>

				<hr>

				<div class="pb10 gray uppercase div-center">Добавить ордер</div>

				<div class="div-center flex-container button--group">

					<div id="greenbutton" class="flex-string">
						<a class="button" onclick="doLoad('/modules/modcatalog/form.modcatalog.php?action=editakt&tip=income');" title="Добавить ордер"><i class="icon-plus-circled"></i>Приходный</a>
					</div>
					<div id="redbutton" class="flex-string">
						<a class="button" onclick="doLoad('/modules/modcatalog/form.modcatalog.php?action=editakt&tip=outcome')" title="Добавить ордер"><i class="icon-minus-circled"></i>Расходный</a>
					</div>

				</div>

			</div>

			<?php if ( in_array( $iduser1, (array)$msettings['mcCoordinator'] ) ) { ?>
				<div class="contaner contaner-offer hidden">

					<A href="javascript:void(0)" onclick="doLoad('/modules/modcatalog/form.modcatalog.php?action=editoffer')" class="button block"><i class="icon-archive"><i class="sup icon-plus-circled"></i></i>&nbsp;&nbsp;Добавить предложение</A>

				</div>
			<?php } ?>

			<div class="contaner p5 contaner-zayavka hidden">

				<div class="togglerbox shad">
					<i class="icon-search blue"></i>&nbsp;ФИЛЬТР&nbsp;
				</div>
				<DIV class="pad5" id="searchboxz">

					<div class="pad10 mt20">

						<div class="ellipsis block mb10">
							<label><input class="directt" name="zstatus[]" type="checkbox" id="status[]" value="0" onclick="preconfigpage()"/>&nbsp;&nbsp;<i class="icon-clock smalltxt broun"></i>&nbsp;<B class="broun">Создана</B></label>
						</div>
						<div class="ellipsis block mb10">
							<label><input class="directt" name="zstatus[]" type="checkbox" id="status[]" value="1" onclick="preconfigpage()"/>&nbsp;&nbsp;<i class="icon-tools smalltxt blue"></i>&nbsp;<B class="blue">В работе</B></label>
						</div>
						<div class="ellipsis block mb10">
							<label><input class="directt" name="zstatus[]" type="checkbox" id="status[]" value="2" onclick="preconfigpage()"/>&nbsp;&nbsp;<i class="icon-ok smalltxt green"></i>&nbsp;<B class="green">Выполнено</B></label>
						</div>
						<div class="ellipsis block mb10">
							<label><input class="directt" name="zstatus[]" type="checkbox" id="status[]" value="3" onclick="preconfigpage()"/>&nbsp;&nbsp;<i class="icon-cancel-circled smalltxt gray"></i>&nbsp;<B class="gray2">Отменено</B></label>
						</div>

					</div>

					<hr>

					<div class="paddtop10">

						<?php
						$element = new Elements();
						print $element -> UsersSelect( 'ziduser', [
							"class" => "wp100",
							"users" => get_people( $iduser1, "yes" ),
							"jsact" => "configpage();"
						] );
						?>
						<div class="smalltxt gray">По сотруднику</div>
					</div>

				</DIV>
			</div>

			<div class="contaner p5 contaner-sklad-sub hidden">

				<div class="shad">
					<i class="icon-search blue"></i>&nbsp;ПОИСК&nbsp;
				</div>

				<DIV class="pad5" id="searchbox">
					<div class="relativ cleared paddtop5">
						<input id="words" name="words" type="text" placeholder="Впишите запрос" class="searchwordinput" data-func="preconfigpage">
						<span class="idel red clearinputs paddtop10" onclick="preconfigpage();"><i class="icon-block-1" title="Очистить"></i></span>
						<div class="smalltxt gray">По названию, описанию, артикулу, серийному номеру</div>
					</div>
				</DIV>

			</div>

			<div class="contaner p5 contaner-sklad hidden">

				<div class="shad">
					<i class="icon-search blue"></i>&nbsp;СКЛАДЫ&nbsp;
				</div>

				<DIV class="pad5" id="searchboxz">
					<div class="pad10">
						<?php
						$res = $db -> getAll( "SELECT id, name_shot FROM ".$sqlname."mycomps WHERE identity = '$identity'" );
						foreach ( $res as $da ) {

							print '<div class="gray2 mb10 Bold">'.$da['name_shot'].'</div>';

							$result = $db -> getAll( "SELECT id, title FROM ".$sqlname."modcatalog_sklad WHERE mcid = '".$da['id']."' and identity = '$identity' ORDER BY title" );
							foreach ( $result as $data ) {
								?>
								<div class="ellipsis fs-09 flh-12 pad3 pl10 mb5">
									<label><input name="sklad[]" type="checkbox" id="sklad[]" value="<?= $data['id'] ?>" onclick="preconfigpage()"><?= $data['title'] ?>
									</label>
								</div><br>
								<?php
							}
						}
						?>
					</div>
				</DIV>
			</div>

			<?php if ( $msettings['mcSkladPoz'] == "yes" ) { ?>
				<div class="contaner p5 contaner-sklad-sub hidden">

					<DIV class="p10">

						<div class="ellipsis block mb10">
							<label><input class="sstatus" name="sstatus[]" type="checkbox" id="sstatus[]" value="in" onclick="preconfigpage()" checked/>&nbsp;&nbsp;<B class="green">На складе</B></label>
						</div>
						<div class="ellipsis block mb10">
							<label><input class="sstatus" name="sstatus[]" type="checkbox" id="sstatus[]" value="out" onclick="preconfigpage()"/>&nbsp;&nbsp;<B class="gray2">Реализована</B></label>
						</div>

					</DIV>
				</div>
			<?php } ?>

			<div class="contaner p5 contaner-reserv hidden">

				<div class="shad">
					<i class="icon-search blue"></i>&nbsp;ПОИСК&nbsp;
				</div>

				<DIV class="pad5" id="searchbox">

					<div class="relativ cleared paddtop5">
						<input id="wordr" name="wordr" type="text" placeholder="Впишите запрос" class="searchwordinput" data-func="preconfigpage">
						<span class="idel red clearinputs paddtop10" onclick="preconfigpage();"><i class="icon-block-1" title="Очистить"></i></span>
						<div class="smalltxt gray">По названию, описанию, артикулу</div>
					</div>

				</DIV>

			</div>

			<div class="contaner p5 contaner-zayavka hidden">

				<div class="div-center flex-container button--group">
					<div id="bluebutton" class="flex-string">
						<a class="button" onclick="doLoad('/modules/modcatalog/form.modcatalog.php?action=editzayavka')" title="Добавить заявку"><i class="icon-plus-circled"></i>По каталогу</a>
					</div>
					<div id="greenbutton" class="flex-string">
						<a class="button" onclick="doLoad('/modules/modcatalog/form.modcatalog.php?action=editzayavka&tip=cold')" title="Добавить заявку"><i class="icon-plus-circled"></i>На поиск</a>
					</div>
				</div>
			</div>

		</form>

		<div class="h35"></div>

	</div>
</DIV>