<?php
/* ============================ */
/*         SalesMan CRM         */
/* ============================ */
/* (C) 2016 Vladislav Andreev   */
/*       SalesMan Project       */
/*        www.isaler.ru         */
/*        ver. 2017.x           */
/* ============================ */

use Salesman\Elements;

?>
<div class="flex-container">

	<DIV class="flex-string" id="lmenu">

		<div class="contaner" data-step="4" data-intro="<h1>Панель фильтров и доп.информации.</h1>Здесь вы можете отфильтровать список клиентов по заданным параметрам" data-position="right" data-id="filterform" style="overflow: hidden">

			<div class="Bold fs-12 mb10 mt10">

				<i class="icon-filter blue"></i>&nbsp;Фильтры&nbsp;

				<div class="inline pull-aright">
					<a href="javascript:void(0)" title="Снять все фильтры" onclick="clearall();" class="gray"><i class="icon-filter blue"></i><i class="sup icon-cancel red"></i></a>&nbsp;&nbsp;
					<a href="javascript:void(0)" title="Обновить представление" onclick="configpage();"><i class="icon-arrows-cw blue"></i></a>
				</div>

			</div>

			<form action="" id="pageform" name="pageform" method="post" enctype="multipart/form-data">
			<input name="ord" id="ord" type="hidden" value="<?=$preset['ord']?>">
			<input name="tuda" id="tuda" type="hidden" value="<?=$preset['tuda']?>">
			<input type="hidden" name="page" id="page" value="1">
			<input type="hidden" name="tiplist" id="tiplist" value="<?=$tip?>">

				<span id="flyitbox"></span>

				<div class="nano graybg">

					<div class="nano-content">

						<div id="filterboxx" class="p5 pb20 mb20">

							<div id="select_search" class="inline wp100">
								<div class="paddtop10 paddbott10" data-step="5" data-intro="<h1>Представления.</h1>Готовые наборы фильтров" data-position="right">
									<select name="list" id="list" class="wp100" onchange="change_us();">
									<optgroup label="Стандартные представления">
										<option value="my" selected="selected">Мои <?=$lang['face']['DealsName']['0']?></option>
										<option value="otdel"><?=$lang['face']['DealsName']['0']?> Подчиненных</option>
										<?php if ($userRights['alls']){?>
										<option value="all">Все Активные <?=$lang['face']['DealsName']['0']?></option>
										<option value="alldeals">Все <?=$lang['face']['DealsName']['0']?></option>
										<option value="alldealsday">Все <?=$lang['face']['DealsName']['0']?>. За сегодня</option>
										<option value="alldealsweek">Все <?=$lang['face']['DealsName']['0']?>. За текущую неделю</option>
										<option value="alldealsmounth">Все <?=$lang['face']['DealsName']['0']?>. За текущий месяц</option>
										<?php } ?>
										<option value="close">Закрытые <?=$lang['face']['DealsName']['0']?></option>
										<option value="closedealsday">Закрытые <?=$lang['face']['DealsName']['0']?>. За сегодня</option>
										<option value="closedealsweek">Закрытые <?=$lang['face']['DealsName']['0']?>. За текущую неделю</option>
										<option value="closedealsmounth">Закрытые <?=$lang['face']['DealsName']['0']?>. За текущий месяц</option>
									</optgroup>
									<optgroup label="Пользовательские представления" id="searchgroup">
										<?php
										$result = $db -> query("select * from ".$sqlname."search where tip='dog' and (iduser='".$iduser1."' or share = 'yes') and identity = '$identity' order by sorder");
										while ($data = $db -> fetch($result)){
											print '<option value="search:'.$data['seid'].'">'.$data['title'].'</option>';
										}
										?>
									</optgroup>
									</select>
									<span class="smalltxt gray">Представления</span>
									<div class="inline">
										<a href="javascript:void(0)" onclick="doLoad('content/helpers/search.editor.deal.php');" title="Редактор представлений" data-step="6" data-intro="<h1>Редактор представлений.</h1>Поможет создать и использовать готовый набор фильтров" data-position="right" class="gray"><i class="icon-pencil blue"></i></a>
									</div>
									<div class="inline" id="pptt">
										<div class="tooltips" tooltip="Здесь будет расшифровка пользовательского представления" tooltip-position="bottom" tooltip-type="primary">
											<i class="icon-info-circled blue"></i>
										</div>
									</div>
								</div>
							</div>

							<div class="pb10">

								<div class="pb5 smalltxt Bold gray">Поиск по сделке:</div>

								<div class="relativ">
									<input id="word" name="word" placeholder="Впишите запрос" class="wp100 searchwordinput" data-func="preconfigpage">
									<span class="idel"><a href="javascript:void(0)" title="Найти" onclick="preconfigpage();"><i class="icon-search blue"></i></a></span>
								</div>

								<div class="smalltxt pt5 right-text">

									<span class="inline"><span class="smalltxt gray">По полю</span>
										<SELECT name="tbl_list" id="tbl_list"  class="w160 clean fs-10">
										<OPTION value="title">Название</OPTION>
										<OPTION value="titledes">Название и <?=$fieldsNames['dogovor']['content']?></OPTION>
										<OPTION value="titleclient">Название клиента</OPTION>
										<OPTION value="adres"><?=$fieldsNames['dogovor']['adres']?></OPTION>
										<?php
										$exclude = array('clid','pid');
										$res = $db -> query("select * from ".$sqlname."field where fld_tip='dogovor' and fld_on='yes' and fld_name LIKE '%input%' and identity = '$identity' order by fld_title");
										while ($data = $db -> fetch($res)){
											print '<OPTION value="'.$data['fld_name'].'">'.$data['fld_title'].'</OPTION>';
										}
										?>
										</SELECT>
									</span>

								</div>

							</div>

							<div class="pb10">

								<div class="pb5 smalltxt Bold gray">Поиск по клиенту:</div>

								<div class="relativ">
									<input id="client[word]" name="client[word]" placeholder="Впишите запрос" class="wp100" onkeydown="if(event.keyCode==13){ preconfigpage(); return false }">
									<span class="idel"><a href="javascript:void(0)" title="Найти" onclick="preconfigpage();"><i class="icon-search blue"></i></a></span>
								</div>
								<div class="smalltxt pt5 right-text">
										<span class="inline"><span class="smalltxt gray">По полю</span>
											<SELECT name="client[tbl_list]" id="client[tbl_list]" class="w160 clean fs-10">
												<OPTION value="title"><?= $fieldsNames['client']['title'] ?></OPTION>
												<OPTION value="titledes"><?= $fieldsNames['client']['title'] ?> и <?= $fieldsNames['client']['des'] ?></OPTION>
												<OPTION value="address"><?= $fieldsNames['client']['address'] ?></OPTION>
												<OPTION value="phone"><?= $fieldsNames['client']['phone'] ?></OPTION>
												<?php if ( $fieldsNames['client']['fax'] ) { ?>
													<OPTION value="fax"><?= $fieldsNames['client']['fax'] ?></OPTION><?php } ?>
												<?php if ( $fieldsNames['client']['mail_url'] ) { ?>
													<OPTION value="mail_url"><?= $fieldsNames['client']['mail_url'] ?></OPTION><?php } ?>
												<?php if ( $fieldsNames['client']['site_url'] ) { ?>
													<OPTION value="site_url"><?= $fieldsNames['client']['site_url'] ?></OPTION><?php } ?>
												<?php
												$exclude = [
													'clid',
													'pid'
												];
												$res     = $db -> getAll( "select * from ".$sqlname."field where fld_tip='client' and fld_on='yes' and fld_name LIKE '%input%' and identity = '$identity' order by fld_title" );
												foreach ( $res as $data ) {

													print '<OPTION value="'.$data['fld_name'].'">'.$data['fld_title'].'</OPTION>';

												}
												?>
											</SELECT>
										</span>
								</div>

							</div>

							<div id="fullFilter">

								<div class="ydropDown flyit" data-id="category">
									<span><?=$fieldsNames['dogovor']['idcategory']?></span>
									<span class="ydropCount"><?=count($prcat)?> выбрано</span><i class="icon-angle-down pull-aright"></i>
									<div class="yDoit action button hidden" onclick="preconfigpage()">Применить</div>
									<div class="yselectBox fly category" data-id="category">
										<div class="right-text">
											<div class="ySelectAll w0 inline" title="Выделить всё"><i class="icon-plus-circled"></i>Всё</div>
											<div class="yunSelect w0 inline" title="Снять выделение"><i class="icon-minus-circled"></i>Ничего</div>
										</div>
										<?php
										$result = $db -> query("SELECT * FROM ".$sqlname."dogcategory WHERE identity = '$identity' ORDER BY title");
										while ($data = $db -> fetch($result)){

											$ss = (in_array($data['idcategory'], $prcat)) ? 'checked' : '';
											?>
											<div class="ydropString ellipsis">
												<label>
													<input class="taskss" name="idcategory[<?=$i?>]" type="checkbox" id="idcategory[<?=$i?>]" value="<?=$data['idcategory']?>" <?=$ss?>>
													&nbsp;<?=$data['title']?>% - <?=$data['content']?>
												</label>
											</div>
										<?php }?>
									</div>
								</div>

								<div class="pb5">

									<select name="isOld" id="isOld" class="wp100" onchange="">
										<option value="">Статус: Все</option>
										<option value="older">Просроченные</option>
										<option value="futur">Актуальные</option>
									</select>
									<!--<span class="smalltxt gray">По актуальности</span>-->
								</div>

								<div class="pb5">

									<select name="tid" id="tid" class="wp100" onchange="">
										<option value=""><?=$fieldsNames['dogovor']['tip']?>: Все</option>
										<?php
										$result = $db -> query("SELECT * FROM ".$sqlname."dogtips WHERE identity = '$identity' ORDER BY title");
										while ($data = $db -> fetch($result)){

											$ss = ($data['tid'] == $preset['tid']) ? 'selected' : '';
											?>
											<option value="<?=$data['tid']?>" <?=$ss?>><?=$data['title']?></option>
										<?php } ?>
									</select>
									<!--<span class="smalltxt gray">По типу сделки</span>-->

								</div>

								<div class="pb10">

									<select name="direction" id="direction" class="wp100" onChange="">
										<option value="" selected><?=$fieldsNames['dogovor']['direction']?>: Все</option>
										<?php
										$result = $db -> query("SELECT * FROM ".$sqlname."direction WHERE identity = '$identity' ORDER BY title");
										while ($data = $db -> fetch($result)){

											$ss = ($data['id'] == $preset['direction']) ? 'selected' : '';
											?>
											<option value="<?=$data['id']?>" <?=$ss?>><?=$data['title']?></option>
										<?php }?>
									</select>
									<!--<span class="smalltxt gray">По направлению</span>-->

								</div>

								<div class="pb10">

									<select name="mcid" id="mcid" class="wp100" onChange="">
										<option value="" selected><?=$fieldsNames['dogovor']['mcid']?>: Все</option>
										<?php
										$result = $db -> query("SELECT * FROM ".$sqlname."mycomps WHERE identity = '$identity' ORDER BY name_shot");
										while ($data = $db -> fetch($result)){

											$ss = ($data['id'] == $preset['mcid']) ? 'selected' : '';
											?>
											<option value="<?=$data['id']?>" <?=$ss?>><?=$data['name_shot']?></option>
										<?php }?>
									</select>
									<!--<span class="smalltxt gray">По направлению</span>-->

								</div>

								<hr>

								<div>
									<div class="paddbott5 smalltxt Bold gray">Другие признаки:</div>

									<?php
									if($otherSettings['dateFieldForFreeze'] != ''){
									?>
									<div class="paddbott5 smalltxt Bold gray">Заморозка:</div>
									<label class="block margbot5 fs-10"><input type="radio" name="isFrozen" id="isFrozen" value="" checked onclick="">&nbsp;Все</label>
									<label class="block margbot5 fs-10"><input type="radio" name="isFrozen" id="isFrozen" value="1" onclick="">&nbsp;Только замороженные</label>
									<label class="block margbot5 fs-10"><input type="radio" name="isFrozen" id="isFrozen" value="0" onclick="">&nbsp;Только Не замороженные</label>

									<hr>
									<?php } ?>

									<label class="margbot5 fs-10"><input type="checkbox" name="haveTask" id="haveTask" value="yes" onclick="">&nbsp;Только без напоминаний</label>

									<hr>

									<div class="paddbott5 smalltxt Bold gray">Признаки активности:</div>
									<label class="block margbot5 fs-10"><input type="radio" name="haveHistory" id="haveHistory" value="" checked onclick="">&nbsp;Все</label>
									<label class="block margbot5 fs-10"><input type="radio" name="haveHistory" id="haveHistory" value="yes" onclick="">&nbsp;Только с активностями</label>
									<label class="block margbot5 fs-10"><input type="radio" name="haveHistory" id="haveHistory" value="no" onclick="">&nbsp;Только без активностей</label>

									<hr>

									<div class="paddbott5 smalltxt Bold gray">Признаки счетов:</div>
									<label class="block margbot5 fs-10"><input type="radio" name="haveCredit" id="haveCredit" value="" checked onclick="">&nbsp;Все</label>
									<label class="block margbot5 fs-10"><input type="radio" name="haveCredit" id="haveCredit" value="yes" onclick="">&nbsp;Есть счета</label>
									<label class="block fs-10"><input type="radio" name="haveCredit" id="haveCredit" value="no" onclick="">&nbsp;Без счетов</label>

								</div>

							</div>

							<?php
							if ( $tipuser != "Менеджер продаж" ) {

								$element = new Elements();
								$usd      = $element -> UsersSelect( "dostup", [
									"class" => ['wp100'],
									"sel"   => '-1',
									'self'  => true
								] );

								print '<hr>';
								print $usd;
								print '<span class="smalltxt gray">Доступы сотрудника к карточкам</span>';

							}
							?>

							<?php
							if($tipuser != "Менеджер продаж"){

								$element = new Elements();
								$us      = $element -> UsersSelect( "iduser", [
									"class" => ['wp100'],
									//"jsact" => "preconfigpage()",
									"sel"   => '-1',
									'self'  => true
								] );

								print '<hr>';
								print str_replace( "--выбор--", "Все", $us );
								print '<span class="smalltxt gray">Является куратором</span>';

							}
							?>
							<div class="space-20"></div>
							
						</div>

					</div>

				</div>

			</form>

		</div>

		<div class="wp100 pl10 pr10 apply-btn div-center">

			<a href="javascript:void(0)" onclick="preconfigpage()" class="button" title="Применить"><i class="icon-filter"></i> Применить фильтры</a>

		</div>

		<hr>

		<div class="contaner no-border no-shadow" data-id="stat" data-step="7" data-intro="<h1>Статистика.</h1>Показывает статистическую информацию" data-position="right">

			<div class="shad mt5"><i class="icon-chart-pie blue"></i>&nbsp;Статистика&nbsp;</div>
			<div id="stat">

				<table class="smalltxt border-bottom mt10">
					<tr>
						<td class="w80">Выбрано:</td>
						<td class="text-right"><b id="alls"></b> шт.<input type="hidden" name="allSelected" id="allSelected" value=""></td>
					</tr>
					<tr>
						<td>Оборот:</td>
						<td class="text-right"><b id="dealKol"></b> <?=$valuta?></td>
					</tr>
					<tr <?php if(!$otherSettings[ 'marga']) print 'class="hidden"';?>>
						<td>Маржа:</td>
						<td class="text-right"><b id="dealMarga"></b> <?=$valuta?></td>
					</tr>
				</table>

			</div>

		</div>

	</DIV>

</div>