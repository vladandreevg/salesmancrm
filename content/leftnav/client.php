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
global $otherSettings;
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
				<input type="hidden" name="alf" id="alf" value="">
				<input name="ord" id="ord" type="hidden" value="<?= $preset['ord'] ?>">
				<input name="tuda" id="tuda" type="hidden" value="<?= $preset['tuda'] ?>">
				<input type="hidden" name="page" id="page" value="1">
				<input type="hidden" name="tiplist" id="tiplist" value="<?= $tip ?>">

				<?php if ( $tip == 'person' ) { ?>
					<input type="hidden" name="isperson" id="isperson" value="yes">
				<?php } ?>

				<span id="flyitbox"></span>

				<div class="nano graybg">

					<div class="nano-content">

						<div id="filterboxx" class="p5 pb20 mb20">
							<?php
							if ( $tip != 'person' ) {
								?>
								<div id="select_search" class="inline wp100">

									<div class="pt10 pb10" data-step="5" data-intro="<h1>Представления.</h1>Готовые наборы фильтров" data-position="right">

										<select name="list" id="list" class="wp100" onchange="change_us();">
											<optgroup label="Стандартные представления">
												<?php if ( $tipuser != "Поддержка продаж" ) { ?>
													<option value="my">Мои Клиенты</option>
													<option value="fav">Ключевые Клиенты</option>
													<option value="otdel">Клиенты Подчиненных</option>
												<?php } ?>
												<?php if ( $userRights['alls'] ) { ?>
													<option value="all">Все Клиенты</option>
												<?php } ?>
												<option value="trash">Корзина, Свободные</option>
											</optgroup>
											<?php
											$sharesCount = 0;
											if($userSettings['dostup']['partner'] == 'on') $sharesCount++;
											if($userSettings['dostup']['contractor'] == 'on') $sharesCount++;
											if($userSettings['dostup']['concurent'] == 'on') $sharesCount++;

											if ( $sharesCount > 0 && $shares > 0 ) {
											?>
												<optgroup label="Связи">
													<?php if ( $sharesCount > 1 ) { ?>
													<option value="other">Все связи</option>
													<?php } ?>
													<?php if ( $userSettings['dostup']['partner'] == 'on' && $otherSettings[ 'partner'] ) { ?>
													<option value="partner">Партнеры</option>
													<?php } ?>
													<?php if ( $userSettings['dostup']['contractor'] == 'on' && $otherSettings[ 'partner'] ) { ?>
														<option value="contractor">Поставщики</option>
													<?php } ?>
													<?php if ( $userSettings['dostup']['concurent'] == 'on' && $otherSettings[ 'concurent'] ) { ?>
														<option value="concurent">Конкуренты</option>
													<?php } ?>
												</optgroup>
											<?php } ?>
											<optgroup label="Настраиваемые представления" id="searchgroup">
												<?php
												$result = $db -> getAll( "select seid, title from {$sqlname}search where tip='client' and (iduser='".$iduser1."' or share = 'yes') and identity = '$identity' order by sorder" );
												foreach ( $result as $data ) {

													$s = ($tip == "search:".$data['seid']) ? "selected" : '';
													print '<option value="search:'.$data['seid'].'" '.$s.'>'.$data['title'].'</option>';

												}
												?>
											</optgroup>
										</select>

										<span class="smalltxt gray">Представления</span>
										<div class="inline">
											<a href="javascript:void(0)" onclick="doLoad('content/helpers/search.editor.client.php?tip=client');" title="Редактор представлений" data-step="6" data-intro="<h1>Редактор представлений.</h1>Поможет создать и использовать готовый набор фильтров" data-position="right" class="gray"><i class="icon-pencil blue"></i></a>
										</div>
										<div class="inline" id="pptt">
											<div class="tooltips" tooltip="Здесь будет расшифровка пользовательского представления" tooltip-position="bottom" tooltip-type="primary">
												<i class="icon-info-circled blue"></i>
											</div>
										</div>

									</div>

								</div>

								<div class="pb10">

									<div class="relativ">
										<input id="word" name="word" placeholder="Впишите запрос" class="wp100 searchwordinput" data-func="preconfigpage">
										<span class="idel"><a href="javascript:void(0)" title="Найти" onclick="preconfigpage();"><i class="icon-search blue"></i></a></span>
									</div>
									<div class="smalltxt pt5 right-text">
										<span class="inline"><span class="smalltxt gray">По полю</span>
											<SELECT name="tbl_list" id="tbl_list" class="w160 clean fs-10">
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
												$res     = $db -> getAll( "select * from {$sqlname}field where fld_tip='client' and fld_on='yes' and fld_name LIKE '%input%' and identity = '$identity' order by fld_title" );
												foreach ( $res as $data ) {

													print '<OPTION value="'.$data['fld_name'].'">'.$data['fld_title'].'</OPTION>';

												}
												?>
											</SELECT>
										</span>
									</div>

								</div>

								<div id="fullFilter">

									<div class="ydropDown flyit" data-id="groups">
										<span>Группы</span>
										<span class="ydropCount"><?= count( $groups ) ?> выбрано</span>
										<i class="icon-angle-down pull-aright"></i>
										<div class="yDoit action button hidden" onclick="preconfigpage()">Применить</div>
										<div class="yselectBox fly groups" data-id="groups">
											<div class="right-text">
												<div class="ySelectAll w0 inline" title="Выделить всё">
													<i class="icon-plus-circled"></i>Всё
												</div>
												<div class="yunSelect w0 inline" title="Снять выделение">
													<i class="icon-minus-circled"></i>Ничего
												</div>
											</div>
											<div class="ydropString ellipsis">
												<label class="gray2 Bold">
													<input class="taskss" name="groups[0]" type="checkbox" id="groups[0]" value="0" <?php if ( in_array( 0, $groups ) )
														print 'checked'; ?>>&nbsp;Вне групп
													<i class="icon-help-circled blue" title="В случае выбора поиск осуществляется только по клиентам, не входящим ни в какие группы"></i>
												</label>
											</div>
											<?php
											$res = $db -> getAll( "SELECT * FROM {$sqlname}group WHERE identity = '$identity' ORDER BY name" );
											foreach ( $res as $data ) {

												$s = ($data['service']) ? ' *' : '';

												?>
												<div class="ydropString ellipsis">
													<label>
														<input class="taskss" name="groups[]" type="checkbox" id="groups[]" value="<?= $data['id'] ?>" <?php if ( in_array( $data['id'], $groups ) )
															print 'checked'; ?>>&nbsp;<?= $s.$data['name'] ?>
													</label>
												</div>
											<?php } ?>
										</div>
									</div>

									<?php if ( $fieldsNames['client']['idcategory'] ) { ?>
										<div class="ydropDown flyit" data-id="category">
											<span><?= $fieldsNames['client']['idcategory'] ?></span>
											<span class="ydropCount"><?= count( $prcat ) ?> выбрано</span><i class="icon-angle-down pull-aright"></i>
											<div class="yDoit action button hidden" onclick="preconfigpage()">Применить</div>
											<div class="yselectBox fly category" data-id="category">
												<div class="right-text">
													<div class="ySelectAll w0 inline" title="Выделить всё">
														<i class="icon-plus-circled"></i>Всё
													</div>
													<div class="yunSelect w0 inline" title="Снять выделение">
														<i class="icon-minus-circled"></i>Ничего
													</div>
												</div>
												<?php
												$otrasl = [
													"client"     => "Клиент",
													"concurent"  => "Конкурент",
													"partner"    => "Партнер",
													"contractor" => "Поставщик"
												];
												$colors = [
													"client"     => "",
													"concurent"  => "red",
													"partner"    => "blue",
													"contractor" => "green"
												];
												$res = $db -> getAll( "SELECT * FROM {$sqlname}category WHERE identity = '$identity' ORDER BY title" );
												foreach ( $res as $data ) {
													?>
													<div class="ydropString ellipsis" title="<?= strtr( $data['tip'], $otrasl ) ?>" data-element="category" data-tip="<?=$data['tip']?>">
														<label>
															<input class="taskss" name="idcategory[<?= $i ?>]" type="checkbox" id="idcategory[<?= $i ?>]" value="<?= $data['idcategory'] ?>" <?php if ( in_array( $data['idcategory'], $prcat ) )
																print 'checked'; ?>>
															<span class="<?= strtr( $data['tip'], $colors ) ?>">&nbsp;<?= $data['title'] ?></span>
														</label>
													</div>
												<?php } ?>
											</div>
										</div>
									<?php } ?>

									<?php if ( $fieldsNames['client']['tip_cmr'] ) { ?>
										<div class="ydropDown flyit" data-id="cmr">
											<span><?= $fieldsNames['client']['tip_cmr'] ?></span>
											<span class="ydropCount"><?= count( $prcmr ) ?> выбрано</span><i class="icon-angle-down pull-aright"></i>
											<div class="yDoit action button hidden" onclick="preconfigpage()">Применить</div>
											<div class="yselectBox fly cmr" data-id="cmr">
												<div class="right-text">
													<div class="ySelectAll w0 inline" title="Выделить всё">
														<i class="icon-plus-circled"></i>Всё
													</div>
													<div class="yunSelect w0 inline" title="Снять выделение">
														<i class="icon-minus-circled"></i>Ничего
													</div>
												</div>
												<div class="ydropString ellipsis">
													<label>
														<input class="taskss" name="tip_cmr[]" type="checkbox" id="tip_cmr[]" value="0" <?php if ( in_array( "0", $prcmr ) )
															print 'checked'; ?>>&nbsp;Не указано
													</label>
												</div>
												<?php
												$i   = 1;
												$res = $db -> getAll( "SELECT * FROM {$sqlname}relations WHERE identity = '$identity' ORDER BY title" );
												foreach ( $res as $data ) {
													?>
													<div class="ydropString ellipsis">
														<label>
															<input class="taskss" name="tip_cmr[]" type="checkbox" id="tip_cmr[]" value="<?= $data['title'] ?>" <?php if ( in_array( $data['tip_cmr'], $prcmr ) )
																print 'checked'; ?>>&nbsp;<?= $data['title'] ?>
														</label>
													</div>
												<?php } ?>
											</div>
										</div>
									<?php } ?>

									<?php if ( $fieldsNames['client']['clientpath'] ) { ?>
										<div class="ydropDown flyit" data-id="path">
											<span><?= $fieldsNames['client']['clientpath'] ?></span>
											<span class="ydropCount"><?= count( $prcpath ) ?> выбрано</span><i class="icon-angle-down pull-aright"></i>
											<div class="yDoit action button hidden" onclick="preconfigpage()">Применить</div>
											<div class="yselectBox fly path" data-id="path">
												<div class="right-text">
													<div class="ySelectAll w0 inline" title="Выделить всё">
														<i class="icon-plus-circled"></i>Всё
													</div>
													<div class="yunSelect w0 inline" title="Снять выделение">
														<i class="icon-minus-circled"></i>Ничего
													</div>
												</div>
												<div class="ydropString ellipsis">
													<label>
														<input class="taskss" name="clientpath[]" type="checkbox" id="clientpath[]" value="0" <?php if ( in_array( "0", $prcpath ) )
															print 'checked'; ?>>&nbsp;Не указано
													</label>
												</div>
												<?php
												$i   = 1;
												$res = $db -> getAll( "SELECT * FROM {$sqlname}clientpath WHERE identity = '$identity' ORDER BY name" );
												foreach ( $res as $data ) {
													?>
													<div class="ydropString ellipsis">
														<label>
															<input class="taskss" name="clientpath[]" type="checkbox" id="clientpath[]" value="<?= $data['id'] ?>" <?php if ( in_array( $data['clientpath'], $prcpath ) )
																print 'checked'; ?>>&nbsp;<?= $data['name'] ?>
														</label>
													</div>
													<?php
													$i++;
												}
												?>
											</div>
										</div>
									<?php } ?>

									<div class="pb5" data-block="type">
										<SELECT name="type" id="type" class="wp100" onchange="">
											<OPTION value="">Тип: Все</OPTION>
											<OPTION value="client">Юр.лица</OPTION>
											<OPTION value="person">Физ.лица</OPTION>
										</SELECT>
										<!--<span class="smalltxt gray">По типу клиента</span>-->
									</div>

									<?php if ( $fieldsNames['client']['territory'] ) { ?>
									<div class="pb10" data-block="territory">
										<select name="territory" id="territory" class="wp100" onChange="">
											<option value=""><?php echo $fieldsNames['client']['territory'];?>: Все</option>
											<option value="0">Не указано</option>
											<?php
											$res = $db -> getAll( "SELECT * FROM {$sqlname}territory_cat WHERE identity = '$identity' ORDER by title" );
											foreach ( $res as $data ) {
												?>
												<option value="<?= $data['idcategory'] ?>" <?php if ( $data['idcategory'] == $preset['territory'] )
													print 'selected'; ?>><?= $data['title'] ?></option>
											<?php } ?>
										</select>
										<!--<span class="smalltxt gray">По Территории:</span>-->
									</div>
									<?php } ?>

									<hr>

									<div class="flex-container mb10" data-block="deal">

										<div class="flex-string wp60 flh-09">
											Дней без реализ. сделок более:<i class="icon-info-circled list blue" title="Число дней без закрытых сделок. Не учитывает активные сделки."></i>
										</div>
										<div class="flex-string wp40">
											<input type="number" id="dog_history" name="dog_history" onblur="" class="wp90">
										</div>

									</div>

									<div class="flex-container" data-block="activities">

										<div class="flex-string wp60 flh-09">
											Дней без активности более:<i class="icon-info-circled list blue" title="Число дней без активностей. Не учитывает системные события."></i>
										</div>
										<div class="flex-string wp40">
											<input type="number" id="client_history" name="client_history" onblur="" class="wp90">
										</div>

									</div>

									<div data-block="other">

										<hr>

										<div class="paddbott5 smalltxt Bold gray">Другие признаки:</div>
										<label class="margbot5 fs-10"><input type="checkbox" name="haveEmail" id="haveEmail" value="yes" onclick="">&nbsp;Только с Email</label>
										<label class="margbot5 fs-10"><input type="checkbox" name="havePhone" id="havePhone" value="yes" onclick="">&nbsp;Только с Телефоном</label>
										<label class="margbot5 fs-10"><input type="checkbox" name="haveTask" id="haveTask" value="yes" onclick="">&nbsp;Только без напоминаний</label>

									</div>

									<div data-block="activities">

										<hr>

										<div class="paddbott5 smalltxt Bold gray">Признаки активности:</div>
										<label class="block margbot5 fs-10"><input type="radio" name="haveHistory" id="haveHistory" value="" checked onclick="">&nbsp;Все</label>
										<label class="block margbot5 fs-10"><input type="radio" name="haveHistory" id="haveHistory" value="yes" onclick="">&nbsp;Только с активностями</label>
										<label class="block margbot5 fs-10"><input type="radio" name="haveHistory" id="haveHistory" value="no" onclick="">&nbsp;Только без активностей</label>

									</div>

									<div data-block="deal">

										<hr>

										<div class="paddbott5 smalltxt Bold gray">Признаки сделок:</div>
										<label class="block margbot5 fs-10"><input type="radio" name="otherParam" id="otherParam" value="" checked onclick="">&nbsp;Все</label>
										<label class="block margbot5 fs-10"><input type="radio" name="otherParam" id="otherParam" value="haveDeals" onclick="">&nbsp;Есть активные сделки</label>
										<label class="block margbot5 fs-10"><input type="radio" name="otherParam" id="otherParam" value="haveDealsClose" onclick="">&nbsp;Есть закрытые сделки</label>
										<label class="block margbot5 fs-10"><input type="radio" name="otherParam" id="otherParam" value="haveDealsClosePlus" onclick="">&nbsp;Есть успешные закрытые сделки</label>
										<label class="block fs-10"><input type="radio" name="otherParam" id="otherParam" value="haveNoDeals" onclick="">&nbsp;Без сделок</label>

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

								</div>
								<?php
							}
							if ( $tip == 'person' ) {
								?>
								<div id="select_search" class="inline wp100">

									<div class="pb10 pt10">

										<select name="list" id="list" class="wp100" onchange="change_us();">
											<optgroup label="Стандартные представления">
												<option value="my" <?php if ( $filter == 'my' )
													print "selected" ?>>Мои Контакты
												</option>
												<option value="otdel" <?php if ( $filter == 'otdel' )
													print "selected" ?>>Контакты отдела
												</option>
												<?php if ( $userRights['alls'] ) { ?>
													<option value="all" <?php if ( $filter == 'all' )
														print "selected" ?>>Все Контакты
													</option>
												<?php } ?>
											</optgroup>
											<optgroup label="Группы клиентов">
												<?php
												$result = $db -> getAll( "select id, name from {$sqlname}group WHERE identity = '$identity' ORDER by name" );
												foreach ( $result as $data ) {
													$s = ($data['service']) ? ' *' : '';
													print '<option value="group:'.$data['id'].'">'.$data['name'].$s.'</option>';
												}
												?>
											</optgroup>
											<optgroup label="Настраиваемые представления" id="searchgroup">
												<?php
												$result = $db -> getAll( "select seid, title from {$sqlname}search where tip='person' and (iduser='".$iduser1."' or share = 'yes') and identity = '$identity' order by sorder" );
												foreach ( $result as $data ) {
													print '<option value="search:'.$data['seid'].'">'.$data['title'].'</option>';
												}
												?>
											</optgroup>
										</select>

										<span class="smalltxt gray">Представления</span>

										<div class="inline">
											<a href="javascript:void(0)" onclick="doLoad('content/helpers/search.editor.client.php?tip=person');" title="Редактор представлений" data-step="6" data-intro="<h1>Редактор представлений.</h1>Поможет создать и использовать готовый набор фильтров" data-position="right" class="gray"><i class="icon-pencil blue"></i></a>
										</div>

										<div class="inline" id="pptt">
											<div class="tooltips" tooltip="Здесь будет расшифровка пользовательского представления" tooltip-position="bottom" tooltip-type="primary">
												<i class="icon-info-circled blue"></i>
											</div>
										</div>
									</div>

								</div>
								<div class="pb10">

									<div class="relativ">
										<input id="word" name="word" placeholder="Впишите запрос" class="wp100 searchwordinput" data-func="preconfigpage">
										<span class="idel"><a href="javascript:void(0)" title="Найти" onclick="preconfigpage();"><i class="icon-search blue"></i></a></span>
									</div>

									<div class="smalltxt pt5 right-text">
										<div class="inline"><span class="smalltxt gray">По полю</span>
											<select name="tbl_list" id="tbl_list" class="w160 clean fs-10">
												<option value="person">Ф.И.О.</option>
												<option value="tel">Телефон</option>
												<option value="mail">Адрес Email</option>
												<?php
												$exclude = [
													'clid',
													'pid'
												];
												$result  = $db -> getAll( "select fld_name, fld_title from {$sqlname}field where fld_tip='person' and fld_on='yes' and identity = '$identity' order by fld_title" );
												foreach ( $result as $data ) {
													if ( stripos( $data['fld_name'], 'input' ) !== false ) {
														print '<option value="'.$data['fld_name'].'">'.$data['fld_title'].'</option>';
													}
												}
												?>
											</SELECT>
										</div>
									</div>

								</div>

								<div id="fullFilter">

									<div class="pb10" data-block="clientpath">

										<select name="clientpath" id="clientpath" class="wp100" onChange="">
											<option value="">Все</option>
											<option value="0">Не указано</option>
											<?php
											$result = $db -> getAll( "SELECT id, name FROM {$sqlname}clientpath WHERE identity = '$identity' ORDER by name" );
											foreach ( $result as $data ) {
												$s = ($data['id'] == $preset['clientpath']) ? 'selected' : '';
												print '<option value="'.$data['id'].'" '.$s.'>'.$data['name'].'</option>';
											}
											?>
										</select>
										<span class="smalltxt gray">По Источнику</span>

									</div>

									<div class="pb10" data-block="loyalty">

										<select name="loyalty" id="loyalty" class="wp100" onChange="">
											<option value="">Все</option>
											<option value="0">Не указано</option>
											<?php
											$result = $db -> getAll( "SELECT idcategory, title FROM {$sqlname}loyal_cat WHERE identity = '$identity' ORDER by title" );
											foreach ( $result as $data ) {
												$s = ($data['idcategory'] == $preset['loyalty']) ? 'selected' : '';
												print '<option value="'.$data['idcategory'].'" '.$s.'>'.$data['title'].'</option>';
											}
											?>
										</select>
										<span class="smalltxt gray">По Лояльности</span>

									</div>

									<hr>

									<div>
										<div class="pb5 smalltxt Bold gray">Другие признаки:</div>
										<label class="mb5 fs-10"><input type="checkbox" name="haveEmail" id="haveEmail" value="yes" onclick="">&nbsp;Только с Email</label>
										<label class="mb5 fs-10"><input type="checkbox" name="havePhone" id="havePhone" value="yes" onclick="">&nbsp;Только с Телефоном</label>
										<label class="mb5 fs-10"><input type="checkbox" name="haveTask" id="haveTask" value="yes" onclick="">&nbsp;Только без напоминаний</label>
									</div>

								</div>
								<?php
							}

							if ( $tipuser != "Менеджер продаж" ) {

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

		<div class="contaner no-shadow" data-id="stat" data-step="7" data-intro="<h1>Статистика.</h1>Показывает статистическую информацию" data-position="right">

			<div class="shad mt5"><i class="icon-chart-pie blue"></i>&nbsp;Статистика&nbsp;</div>
			<div id="stat"></div>

		</div>

	</DIV>

</div>