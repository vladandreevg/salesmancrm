<?php
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

		<form id="pageform" name="pageform">
			<input type="hidden" name="page" id="page" value="1">
			<input type="hidden" name="tar" id="tar" value="contract">
			<input type="hidden" name="ord" id="ord" value="datum_start">
			<input type="hidden" name="tuda" id="tuda" value="desc">
			<input name="tiplist" id="tiplist" type="hidden" value="">

			<span id="flyitbox"></span>

			<div class="contaner" data-id="filter">

				<div class="nano">

					<div class="nano-content box--child">

						<div class="Bold fs-12 shad mt10 mb10 pl10" id="cfilter">
							<i class="icon-filter blue"></i>&nbsp;<b class="shad">Фильтры</b>
						</div>

						<div class="contaner-contract p5 hidden">

							<div class="pt10 pb10 mm">
								<div class="relativ">
									<input id="wordc" name="wordc" type="text" placeholder="Впишите запрос" class="searchwordinput" data-func="search">
									<span class="idel"><a href="javascript:void(0)" title="Найти" onclick="search();"><i class="icon-search blue"></i></a></span>
								</div>
								<div class="smalltxt gray">По названию и тегам документа, по названию клиента, сделки</div>
							</div>

							<hr>

							<div class="pl10 pt5 pb5 mm">

								<div class="string">
									<label><input name="oldonly" type="radio" id="oldonly" value="" onClick="search();">&nbsp;Все</label>
								</div>

								<div class="string">
									<label><input name="oldonly" type="radio" id="oldonly" value="old" onClick="search();"><span class="bullet-mini graybg"></span>&nbsp;Просроченные > 30 дн.</label>
								</div>

								<div class="string">
									<label><input name="oldonly" type="radio" id="oldonly" value="old30" onClick="search();"><span class="bullet-mini redbg"></span>&nbsp;Просроченные на 14 - 30 дн.</label>
								</div>

								<div class="string">
									<label><input name="oldonly" type="radio" id="oldonly" value="old14" onClick="search();"><span class="bullet-mini yellowbg"></span>&nbsp;Просроченные на <= 14 дн.</label>
								</div>

								<div class="string">
									<label><input name="oldonly" type="radio" id="oldonly" value="mounth" onClick="search();"><span class="bullet-mini bluebg"></span>&nbsp;30 дней до конца</label>
								</div>

								<div class="string">
									<label><input name="oldonly" type="radio" id="oldonly" value="week" onClick="search();"><span class="bullet-mini orangebg"></span>&nbsp;1 неделя до конца</label>
								</div>

							</div>

							<hr>

							<div class="pt10 pb5 mm"><b>По типу</b></div>

							<div class="mb10" id="doctype">

								<div class="ydropDown flyit" data-id="doctype">
									<span>Тип</span>
									<span class="ydropCount">0 выбрано</span><i class="icon-angle-down pull-aright"></i>
									<div class="yselectBox fly doctype" data-id="doctype">

										<div class="right-text">
											<div class="ySelectAll w0 inline" title="Выделить всё">
												<i class="icon-plus-circled"></i>Всё
											</div>
											<div class="yunSelect w0 inline" title="Снять выделение">
												<i class="icon-minus-circled"></i
												>Ничего
											</div>
										</div>

										<?php
										$result = $db -> query("SELECT * FROM ".$sqlname."contract_type WHERE COALESCE(type, '') NOT IN ('get_akt','get_aktper') and identity = '$identity' ORDER BY title");
										while ($data = $db -> fetch($result)) {
											?>
											<div class="ydropString ellipsis">
												<label>
													<input class="taskss" name="type[]" type="checkbox" id="type[]" value="<?= $data['id'] ?>" onClick="search()">&nbsp;&nbsp;<?= $data['title'] ?>
												</label>
											</div>
										<?php } ?>
									</div>
								</div>
							</div>

						</div>

						<div class="contaner-payment p5 hidden">

							<DIV id="filter">

								<div class="pt10 pb10">
									<div class="relativ">
										<input id="wordp" name="wordp" type="text" placeholder="Впишите запрос" class="searchwordinput" data-func="search">
										<span class="idel"><a href="javascript:void(0)" title="Найти" onClick="search();"><i class="icon-search blue"></i></a></span>
									</div>
									<span class="smalltxt gray">По номеру счета, договора, названию Клиента или Сделки</span>
								</div>

								<hr>

								<div class="pt10 pb10"><b>По оплате</b></div>

								<div>
									<div class="ellipsis block pl10 mb10">
										<label><input name="pay1" type="checkbox" id="pay1" value="yes" onClick="search();" checked/>&nbsp;Оплаченные</label>
									</div>
									<div class="ellipsis block pl10">
										<label><input name="pay2" type="checkbox" id="pay2" value="yes" onClick="search();" checked/>&nbsp;Неоплаченные</label>
									</div>
								</div>

								<hr>

								<div class="pt10"><b>По сотруднику</b></div>

								<div class="pt5 pb10">
									<?php
									$usr = (stripos($tipuser, 'Руководител') === false || $isadmin != 'on') ? $iduser1 : "-1";
									$element = new Elements();
									print $element -> UsersSelect('iduser', [
										"class" => "wp97",
										"users" => get_people($iduser1, "yes"),
										"sel"   => $usr,
										"jsact" => "search();"
									]);
									?>
								</div>

							</DIV>

						</div>

						<div class="contaner-akt p5 hidden">

							<div class="pt10 mt10">

								<div class="relativ">
									<input id="worda" name="worda" type="text" placeholder="Впишите запрос" class="searchwordinput" data-func="search">
									<span class="idel"><a href="javascript:void(0)" title="Найти" onClick="search();"><i class="icon-search blue"></i></a></span>
								</div>
								<div class="smalltxt gray">По номеру документа, счета, по названию клиента, сделки</div>

							</div>

							<hr>

							<div class="pt10 pb10"><b>По типу сделки</b></div>

							<div>
								<div class="ellipsis block pl10 mb10">
									<label><input name="isService" type="radio" id="isService" value="" onClick="search();" checked/>&nbsp;Все</label>
								</div>
								<div class="ellipsis block pl10 mb10">
									<label><input name="isService" type="radio" id="isService" value="no" onClick="search();"/>&nbsp;Обычная</label>
								</div>
								<div class="ellipsis block pl10">
									<label><input name="isService" type="radio" id="isService" value="yes" onClick="search();"/>&nbsp;Сервисная</label>
								</div>
							</div>

							<hr>

							<div class="pt10 pb10"><b>По сотруднику</b></div>

							<div class="pb5">
								<?php
								$usr = (stripos($tipuser, 'Руководител') === false || $isadmin != 'on') ? $iduser1 : "-1";
								$element = new Elements();
								print $element -> UsersSelect('idusera', [
									"class" => "wp97",
									"users" => get_people($iduser1, "yes"),
									"sel"   => $usr,
									"jsact" => "search();"
								]);
								?>
							</div>

						</div>

						<div class="contaner-mc p5">

							<DIV id="filter">

								<div class="pt10"><b>По компаниям</b></div>

								<div class="pt5 pb5">

									<?php
									$element = new Elements();
									print $element -> mycompSelect('mc', [
										"class" => "wp97",
										"sel"   => -1,
										"jsact" => "search();"
									]);
									?>

								</div>

							</DIV>

						</div>

						<div class="contaner-status hidden mt10">

							<div class="mb15"><b>По статусу документа</b></div>

							<div class="mb10 pl10" id="doctype">
								<?php
								$result = $db -> query("SELECT * FROM ".$sqlname."contract_status WHERE identity = '$identity' ORDER BY ord");
								while ($data = $db -> fetch($result)) {

									print '<div class="ellipsis mb5"><label><input name="status[]" type="checkbox" id="status[]" value="'.$data['id'].'" onClick="search();" />&nbsp;<span class="bullet-mini" style="margin-bottom:1px; background-color:'.$data['color'].'"></span>&nbsp;'.$data['title'].'</label></div><br>';

								}
								?>
							</div>

						</div>

					</div>

				</div>

			</div>

			<div class="contaner" data-id="stat">

				<a href="javascript:void(0)" onclick="getSwindow('/reports/ent-InvoiceStateByUser.php', 'Статус выставленных счетов')" class="greenbtn button wp100" title="Показать аналитику"><i class="icon-chart-line"></i> Статистика</a>

			</div>

		</form>

	</DIV>

</div>

