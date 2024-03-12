<?php
/* ============================ */
/*         SalesMan CRM         */
/* ============================ */
/* (C) 2016 Vladislav Andreev   */
/*       SalesMan Project       */
/*        www.isaler.ru         */
/*        ver. 2016.25          */
/* ============================ */

error_reporting( E_ERROR );

header( "Pragma: no-cache" );

$rootpath = dirname(__DIR__, 2);

require_once $rootpath."/inc/config.php";
require_once $rootpath."/inc/dbconnector.php";
require_once $rootpath."/inc/auth.php";
require_once $rootpath."/inc/func.php";
require_once $rootpath."/inc/settings.php";
require_once $rootpath."/inc/language/".$language.".php";

$thisfile = basename( __FILE__ );

//print_r($fieldsNames['dogovor']);

$tip    = $_REQUEST['tip'];
$action = $_REQUEST['action'];

if ( $tip == 'client' ) {
	$url = 'list';
}
else {
	$url = 'person';
}

$daytag = [
	"today"     => "Сегодня",
	"week"      => "Текущая неделя",
	"prevweek"  => "Предыдущая неделя",
	"nextweek"  => "Следующая неделя",
	"month"     => "Текущий месяц",
	"prevmonth" => "Предыдущий месяц",
	"nextmonth" => "Следующий месяц"
];

$ctypes = [
	"client"     => "Клиент. Юр.лицо",
	"person"     => "Клиент. Физ.лицо",
	"concurent"  => "Конкурент",
	"contractor" => "Поставщик",
	"partner"    => "Партнер"
];

if ( $action == "add_on" ) {

	$title = $_POST['title'];
	$tip   = $_POST['tip'];

	$field       = array_values( (array)$_REQUEST['field'] );
	$term        = array_values( (array)$_REQUEST['term'] );
	$field_query = array_values( (array)$_REQUEST['field_query'] );

	$share  = $_POST['share'];
	$squery = '';

	//Составляем массив записи
	$count = count( $field );
	for ( $i = 0; $i < $count; $i++ ) {

		$s = ($i > 0) ? ";" : "";

		$squery .= $s.$field[ $i ].":".$term[ $i ].":".$field_query[ $i ];

	}

	//вычисляем количество имеющихся записей
	$order = $db -> getOne( "select COUNT(*) from {$sqlname}search where tip='".$tip."' and iduser='".$iduser1."' and identity = '$identity'" ) + 1;

	//Обновляем данные для текущей записи
	try {

		$db -> query( "insert into {$sqlname}search (seid,tip,title,squery,sorder,iduser,share,identity) values (NULL, '".$tip."', '".$title."', '".$squery."', '".$order."', '".$iduser1."', '".$share."','$identity')" );

		$id = $db -> insertId();

		print '{"id":"'.$id.'", "title":"'.$title.'", "rez":"Сделано"}';

	}
	catch ( Exception $e ) {

		$mes = 'Ошибка'.$e -> getMessage().' в строке '.$e -> getCode();

		print '{"id":"", "rez":"'.$mes.'"}';

	}


	exit();
}
if ( $action == "edit_on" ) {

	$title = $_REQUEST['title'];
	$seid  = $_REQUEST['seid'];

	$field       = array_values( (array)$_REQUEST['field'] );
	$term        = array_values( (array)$_REQUEST['term'] );
	$field_query = array_values( (array)$_REQUEST['field_query'] );

	$share  = $_POST['share'];
	$squery = '';

	//Составляем массив записи
	$count = count( $field );
	for ( $i = 0; $i < $count; $i++ ) {

		$s = ($i > 0) ? ";" : "";

		$squery .= $s.$field[ $i ].":".$term[ $i ].":".$field_query[ $i ];

	}

	try {

		$db -> query( "update {$sqlname}search set title = '".$title."', squery = '".$squery."', share = '".$share."' where seid = '".$seid."' and identity = '$identity'" );

		print '{"id":"'.$seid.'", "title":"'.$title.'", "rez":"Сделано"}';

	}
	catch ( Exception $e ) {

		$mes = 'Ошибка'.$e -> getMessage().' в строке '.$e -> getCode();

		print '{"id":"", "rez":"'.$mes.'"}';

	}

	exit();
}
if ( $action == "edit_order" ) {

	$table1 = $_REQUEST['tbborder'];
	$err    = 0;

	//Обновляем данные для текущей записи
	foreach ( $table as $i => $row ) {

		$db -> query( "update {$sqlname}search set sorder = '$i' where seid = '$row' and identity = '$identity'" );

	}

	print 'Обновлено';

	exit();
}
if ( $action == "delete" ) {

	$seid = $_REQUEST['seid'];

	$db -> query( "delete from {$sqlname}search where seid = '".$seid."' and identity = '$identity'" );
	print "Запись удалена";

	exit();

}

if ( $action == '' ) {

	/*
	 * Массив расшифровки имен полей. Старт
	 */
	$name = [
		"date_create" => "Дата создания",
		"fav"         => "Ключевой клиент",
		"last_dog"    => "Дата последней закрытой сделки",
		"type"        => "Тип контрагента"
	];

	$exclude = [
		'clid',
		'pid'
	];
	$re      = $db -> query( "select fld_name, fld_title from {$sqlname}field where fld_tip='".$tip."' and identity = '$identity' order by fld_title" );
	while ($da = $db -> fetch( $re )) {

		$name[ $da['fld_name'] ] = $da['fld_title'];

	}

	$re = $db -> query( "SELECT * FROM {$sqlname}profile_cat WHERE tip != 'divider' and identity = '$identity' ORDER by ord" );
	while ($da = $db -> fetch( $re )) {

		$name[ 'profile--'.$da['id'] ] = $da['name'];

	}
	/*
	 * Массив расшифровки имен полей. Финиш
	 */
	?>
	<DIV class="zagolovok">Пользовательские представления</DIV>

	<div style="height:70vh; overflow:auto; position:relative;">

		<table id="tbborder" class="rowtable">
			<thead style="z-index: 100">
			<tr class="noDrag header_contaner">
				<TH class="w60 text-center nodrop">Порядок</TH>
				<TH class="nodrop">Название представления</TH>
				<TH class="w120 text-center nodrop">Действие</TH>
			</tr>
			</thead>
			<tbody>
			<?php
			$result = $db -> query( "select * from {$sqlname}search where tip='".$_REQUEST['tip']."' and (iduser='".$iduser1."' or share='yes') and identity = '$identity' order by sorder" );
			while ($data = $db -> fetch( $result )) {

				$tt = '';

				$share = ($data['share'] == 'yes') ? ' <i class="icon-users-1 red" title="Общее представление"></i>' : '';

				$squery = yexplode( ';', $data['squery'] );

				foreach ( $squery as $sqy ) {

					$sq = yexplode( ':', $sqy );

					/*[
						$field,
						$term,
						$query
					] = $sq;*/

					$field = $sq[0];
					$term  = $sq[1];
					$query = $sq[2];

					if ( $term == '!=' )
						$term = "не равно";
					if ( $term == 'LIKE' )
						$term = "содержит";
					if ( $term == 'NOT LIKE' )
						$term = "не содержит";

					switch ($field) {
						case 'iduser':
							$query = current_user( $query );
						break;
						case 'fav':
						case 'trash':
							$query = strtr( $query, [
								"no"  => "Нет",
								"yes" => "Да"
							] );
						break;
						case 'territory':
							$query = current_territory( $query );
						break;
						case 'idcategory':
							$query = current_category( $query );
						break;
						case 'clientpath':
							$query = current_clientpathbyid( $query );
						break;
						case 'loyalty':
							$query = current_loyalty( $query );
						break;
						default:

						break;
					}

					$tt .= '<b>'.strtr( $field, $name ).'</b> '.$term.' "<b>'.$query.'</b>"<br>';

				}

				?>
				<tr class="ha disable--select" id="<?= $data['seid'] ?>">
					<td class="w60 text-center"><?= $data['sorder'] ?></td>
					<td>
						<div class="fs-11"><b class="blue"><?= $data['title'] ?></b><?= $share ?>
							<i class="icon-angle-up hand togglerbox" data-id="block<?= $data['seid'] ?>" id="mapic"></i>
						</div>
						<div class="smalltxt gray2" id="block<?= $data['seid'] ?>">
							<?= $tt ?>
							<div class="mt5 broun">Владелец: <?= current_user( $data['iduser'], 'yes' ) ?></div>
						</div>
					</td>
					<td class="w120 text-center">
						<div class="pt5">
							&nbsp;&nbsp;<A href="javascript:void(0)" onclick="doLoad('/content/helpers/search.editor.client.php?action=add&seid=<?= $data['seid'] ?>&tip=<?= $tip ?>');" title="Клонировать"><i class="icon-paste green"></i></A>
							<?php
							if ( $data['iduser'] == $iduser1 || $isadmin == 'on' ) {
								?>
								&nbsp;&nbsp;
								<A href="javascript:void(0)" onclick="doLoad('/content/helpers/search.editor.client.php?action=edit&seid=<?= $data['seid'] ?>&tip=<?= $tip ?>');" title="Изменить"><i class="icon-pencil blue"></i></A>&nbsp;&nbsp;
								<A href="javascript:void(0)" onclick="sdelete('<?= $data['seid'] ?>');" title="Удалить"><i class="icon-cancel red"></i></A>
								<?php
							}
							else {
								print '&nbsp;&nbsp;<A href="javascript:void(0)" title="Изменить"><i class="icon-pencil gray"></i></A>&nbsp;&nbsp;<A href="javascript:void(0)" title="Удалить"><i class="icon-cancel gray"></i></A>';
							}
							?>
						</div>
					</td>
				</tr>
				<?php
			}
			?>
			</tbody>
		</table>

	</div>

	<hr>

	<div align="right">
		<A href="javascript:void(0)" onclick="refresh('resultdiv','/content/helpers/search.editor.client.php?action=add&seid=<?= $data['seid'] ?>&tip=<?= $tip ?>');" class="button">Добавить</A>&nbsp;
		<A href="javascript:void(0)" onclick="DClose()" class="button">Закрыть</A>
	</div>

	<?php

}

if ( $action == 'add' ) {

	if ( $_REQUEST['seid'] > 0 ) {

		$search          = $db -> getRow( "select * from ".$sqlname."search where seid = '".$_REQUEST['seid']."' and identity = '$identity' order by sorder" );
		$search['title'] .= "(клон)";

	}
	else {
		$search['title'] = "Выборка";
	}

	//$i = 0;

	$squery = yexplode( ';', (string)$search['squery'] );
	$count  = count( $squery );
	?>
	<DIV class="zagolovok">Создание поискового представления</DIV>

	<FORM action="/content/helpers/search.editor.client.php" method="post" enctype="multipart/form-data" name="Form" id="Form">
		<INPUT type="hidden" name="action" id="action" value="add_on">
		<INPUT name="tip" type="hidden" id="tip" value="<?= $tip ?>">
		<INPUT name="count" type="hidden" id="count" value="0">

		<div style="height:70vh; max-height:70vh; overflow-x:hidden; overflow-y:auto">

			<table id="fields">
				<tr class="noDrag">
					<td class="w100 nodrop text-right"><b>Название:</b></td>
					<td colspan="4" class="nodrop">
						<input name="title" type="text" id="title" class="required wp100" value="<?= $search['title'] ?>">
					</td>
				</tr>
				<?php
				for ( $i = 0; $i < $count; $i++ ) {

					$squery2 = explode( ':', $squery[ $i ] );

					$field       = $squery2[0];
					$term        = $squery2[1];
					$field_query = $squery2[2];

					/*[
						$field,
						$term,
						$field_query
					] = $squery2;*/

					$field = explode( "--", $field );
					?>
					<tr id="fld<?= $i ?>">
						<td class="text-right">Параметр:</td>
						<td>
							<select id="field<?= $i ?>" name="field[<?= $i ?>]" class="wp100" onchange="loadpole('inputf<?= $i ?>','field<?= $i ?>')">
								<option value="">--выбор--</option>
								<option value="date_create" <?php if ( $field[0] == 'date_create' )
									print "selected" ?>>Дата создания
								</option>
								<?php
								if ( $tip == 'client' ) {
									?>
									<option value="fav" <?php if ( $field[0] == 'fav' )
										print "selected" ?>>Ключевой клиент
									</option>
									<option value="type" <?php if ( $field[0] == 'type' )
										print "selected" ?>>Тип контрагента
									</option>
									<?php
								}

								$res = $db -> query( "select * from {$sqlname}field where fld_tip='".$tip."' and fld_on='yes' and fld_name NOT IN ('clid','pid') and identity = '$identity' order by fld_title" );
								while ($da = $db -> fetch( $res )) {

									?>
									<option value="<?= $da['fld_name'] ?>" <?php if ( $da['fld_name'] == $field[0] ) print "selected" ?>><?= $da['fld_title'] ?></option>
									<?php
								}
								?>
								<?php
								if ( $tip == 'client' ) {
									?>
									<option value="last_dog" <?php if ( $field[0] == 'last_dog') print "selected" ?>>Дата последней закрытой сделки
									</option>
									<?php
									$res = $db -> query( "SELECT * FROM {$sqlname}profile_cat WHERE tip!='divider' and identity = '$identity' ORDER by ord" );
									while ($da = $db -> fetch( $res )) {
										?>
										<option value="profile--<?= $da['id'] ?>" <?php if ( $da['id'] == $field[1] and $field[0] == 'profile' ) print "selected" ?>>Профиль:<?= $da['name'] ?></option>
										<?php
									}
								}
								?>
							</select>
						</td>
						<td class="w50">
							<select name="term[<?= $i ?>]" id="term[<?= $i ?>]">
								<option value=">=" <?php if ( $term == '>=' )
									print 'selected="selected"'; ?>>больше или равно
								</option>
								<option value="<=" <?php if ( $term == '<=' )
									print 'selected="selected"'; ?>>меньше или равно
								</option>
								<option value="=" <?php if ( $term == '=' )
									print 'selected="selected"'; ?>>равно
								</option>
								<option value="!=" <?php if ( $term == '!=' )
									print 'selected="selected"'; ?>>не равно
								</option>
								<option value="LIKE" <?php if ( $term == 'LIKE' )
									print 'selected="selected"'; ?>>содержит
								</option>
								<option value="NOT LIKE" <?php if ( $term == 'NOT LIKE' )
									print 'selected="selected"'; ?>>не содержит
								</option>
							</select>
						</td>
						<td class="w200">
							<div id="inputf<?= $i ?>">

								<?php
								switch ($field[0]) {
								case 'iduser':
									?>
									<select name="field_query[<?= $i ?>]" id="field_query[<?= $i ?>]" class="wp100">
										<?php
										$res = $db -> query( "SELECT * FROM {$sqlname}user WHERE iduser > 0 $sort and identity = '$identity' order by title" );
										while ($da = $db -> query( $res )) {
											?>
											<OPTION value="<?= $da['iduser'] ?>" <?php if ( $da['iduser'] == $field_query )
												print "selected" ?>><?= $da['title'] ?></OPTION>
										<?php } ?>
									</select>
								<?php
								break;
								case 'fav':
								?>
									<select name="field_query[<?= $i ?>]" id="field_query[<?= $i ?>]" class="wp100">
										<OPTION value="yes">Да</OPTION>
										<OPTION value="no">Нет</OPTION>
									</select>
								<?php
								break;
								case 'type':
								?>
									<select name="field_query[<?= $i ?>]" id="field_query[<?= $i ?>]" class="wp100">
										<OPTION value="client">Клиент. Юр.лицо</OPTION>
										<OPTION value="person">Клиент. Физ.лицо</OPTION>
										<OPTION value="concurent">Конкурент</OPTION>
										<OPTION value="contractor">Поставщик</OPTION>
										<OPTION value="partner">Партнер</OPTION>
									</select>
								<?php
								break;
								case 'trash':
								?>
									<select name="field_query[<?= $i ?>]" id="field_query[<?= $i ?>]" class="wp100">
										<OPTION value="no" <?php if ( $field_query == 'no' )
											print "selected" ?>>Нет
										</OPTION>
										<OPTION value="yes" <?php if ( $field_query == 'yes' )
											print "selected" ?>>Да
										</OPTION>
									</select>
								<?php
								break;
								case 'territory':
								?>
									<select name="field_query[<?= $i ?>]" id="field_query[<?= $i ?>]" class="wp100">
										<OPTION value="">--выбор--</OPTION>
										<?php
										$result = $db -> query( "SELECT * FROM {$sqlname}territory_cat WHERE identity = '$identity' order by title" );
										while ($data = $db -> fetch( $result )) { ?>
											<OPTION value="<?= $data['idcategory'] ?>" <?php if ( $data['idcategory'] == $field_query )
												print "selected" ?>><?= $data['title'] ?></OPTION>
										<?php } ?>
									</select>
								<?php
								break;
								case 'head_clid':
								?>
									<select name="field_query[<?= $i ?>]" id="field_query[<?= $i ?>]" class="wp100">
										<OPTION value="">--выбор--</OPTION>
										<?php
										$result = $db -> query( "SELECT * FROM {$sqlname}clientcat WHERE identity = '$identity' order by title" );
										while ($data = $db -> fetch( $result )) { ?>
											<OPTION value="<?= $data['clid'] ?>" <?php if ( $data['clid'] == $field_query )
												print "selected" ?>><?= $data['title'] ?></OPTION>
										<?php } ?>
									</select>
								<?php
								break;
								case 'tip_cmr':
								?>
									<select name="field_query[<?= $i ?>]" id="field_query[<?= $i ?>]" class="wp100">
										<OPTION value="">--выбор--</OPTION>
										<?php
										$result = $db -> query( "SELECT * FROM {$sqlname}relations WHERE identity = '$identity' ORDER by title" );
										while ($data = $db -> fetch( $result )) {
											?>
											<option <?php if ( $field_query == $data['title'] )
												print "selected" ?> value="<?= $data['title'] ?>"><?= $data['title'] ?></option>
										<?php } ?>
									</select>
								<?php
								break;
								case 'idcategory':
								?>
									<select name="field_query[<?= $i ?>]" id="field_query[<?= $i ?>]" class="wp100">
										<?php
										$result = $db -> query( "SELECT * FROM {$sqlname}category WHERE identity = '$identity' order by title" );
										while ($data = $db -> fetch( $result )) { ?>
											<OPTION value="<?= $data['idcategory'] ?>" <?php if ( $data['idcategory'] == $field_query )
												print "selected" ?>><?= $data['title'] ?></OPTION>
										<?php } ?>
									</select>
								<?php
								break;
								case 'clientpath':
								?>
									<select name="field_query[<?= $i ?>]" id="field_query[<?= $i ?>]" class="wp100">
										<?php
										$result = $db -> query( "SELECT * FROM {$sqlname}clientpath WHERE identity = '$identity' order by name" );
										while ($data = $db -> fetch( $result )) { ?>
											<OPTION value="<?= $data['id'] ?>" <?php if ( $data['id'] == $field_query )
												print "selected" ?>><?= $data['name'] ?></OPTION>
										<?php } ?>
									</select>
								<?php
								break;
								case 'loyalty':
								?>
									<select name="field_query[<?= $i ?>]" id="field_query[<?= $i ?>]" class="wp100">
										<OPTION value="">--выбор--</OPTION>
										<?php
										$result = $db -> query( "SELECT * FROM {$sqlname}loyal_cat WHERE identity = '$identity'" );
										while ($data = $db -> query( $result )) {
											?>
											<OPTION <?php if ( $data['idcategory'] == $field_query )
												print "selected"; ?> value="<?= $data['idcategory'] ?>"><?= $data['title'] ?></OPTION>
											<?php
										}
										?>
									</select>
								<?php
								break;
								case 'rol':
								?>
								<INPUT name="field_query[<?= $i ?>]" id="field_query[<?= $i ?>]" type="text" class="ac_input wp100" autocomplete="on" value="<?= $field_query ?>">
								<?php
								break;
								case 'date_create': case 'last_dog':
								?>
									<div class="variants">
										<INPUT name="field_query[<?= $i ?>]" id="field_query[<?= $i ?>]" type="text" class="ac_input datum wp100" autocomplete="off" value="<?= $field_query ?>">
										<div class="list">
											<span title="Динамично: Сегодня">{today}</span>
											<hr>
											<span title="Динамично: Текущая неделя">{week}</span>
											<span title="Динамично: Прошлая неделя">{prevweek}</span>
											<span title="Динамично: Следующая неделя">{nextweek}</span>
											<hr>
											<span title="Динамично: Текущий месяц">{month}</span>
											<span title="Динамично: Прошлый месяц">{prevmonth}</span>
											<span title="Динамично: Следующий месяц">{nextmonth}</span>
										</div>
									</div>
									<script type="text/javascript">
										$(function () {
											$("#field_query\\[<?=$i?>\\]").datepicker({
												dateFormat: 'yy-mm-dd',
												firstDay: 1,
												dayNamesMin: ['Вс', 'Пн', 'Вт', 'Ср', 'Чт', 'Пт', 'Сб'],
												monthNamesShort: ['Январь', 'Февраль', 'Март', 'Апрель', 'Май', 'Июнь', 'Июль', 'Август', 'Сентябрь', 'Октябрь', 'Ноябрь', 'Декабрь'],
												changeMonth: true,
												changeYear: true
											});
										});
									</script>
								<?php
								break;
								case 'profile':

									$result = $db -> getOne( "SELECT value FROM {$sqlname}profile_cat WHERE id = '".$field[1]."' and tip!='divider' and identity = '$identity' and identity = '$identity' ORDER by ord" );
									$value  = explode( ";", (string)$result );

									print '<select name="field_query['.$i.']" id="field_query['.$i.']" style="width:100%">';
									//Переберем все имеющиеся профили из каталога профилей
									foreach ( $value as $k => $xvalue ) {

										if ( $xvalue != '' ) {

											$value[ $k ] = trim( str_replace( [
												"\\n\\r",
												"\\n",
												"\\r",
												","
											], "", $xvalue ) );

											if ( $xvalue == $field_query )
												$s = "selected";
											else $s = '';

											print '<OPTION value="'.$xvalue.'" '.$s.'>'.$xvalue.'</OPTION>';

										}
									}
									print '</select>';

								break;
								default:

								//найдем тип текущего поля и если это дата, то поставим датапикер
								$res  = $db -> getRow( "SELECT fld_temp, fld_var FROM {$sqlname}field WHERE fld_name = '".$field[0]."' and fld_tip = '".$tip."' and identity = '$identity'" );
								$ptip = $res["fld_temp"];
								$vars = explode( ",", $res["fld_var"] );

								if (in_array( $ptip, [
									'textarea',
									'--Обычное--',
									'adres'
								] ) || $ptip == ''){
								?>
								<input type="text" id="field_query[<?= $i ?>]" name="field_query[<?= $i ?>]" value="<?= $field_query ?>" class="wp100">
								<?php
								}
								if ($ptip == 'select'){
								?>
									<select name="field_query[<?= $i ?>]" class="wp100" id="field_query[<?= $i ?>]">
										<option value="">--Выбор--</option>
										<?php
										foreach ( $vars as $var ) {
											?>
											<option value="<?= $var ?>" <?php if ( $var == $field_query )
												print 'selected' ?>><?= $var ?></option>
										<?php } ?>
									</select>
								<?php
								}
								if ($ptip == 'datum'){
								?>
									<div class="variants">
										<INPUT name="field_query[<?= $i ?>]" id="field_query[<?= $i ?>]" type="text" class="ac_input datum wp100" autocomplete="off" value="<?= $field_query ?>">
										<div class="list">
											<span title="Динамично: Сегодня">{today}</span>
											<hr>
											<span title="Динамично: Текущая неделя">{week}</span>
											<span title="Динамично: Прошлая неделя">{prevweek}</span>
											<span title="Динамично: Следующая неделя">{nextweek}</span>
											<hr>
											<span title="Динамично: Текущий месяц">{month}</span>
											<span title="Динамично: Прошлый месяц">{prevmonth}</span>
											<span title="Динамично: Следующий месяц">{nextmonth}</span>
										</div>
									</div>
									<script type="text/javascript">
										$(function () {
											$("#field_query\\[<?=$i?>\\]").datepicker({
												dateFormat: 'yy-mm-dd',
												firstDay: 1,
												dayNamesMin: ['Вс', 'Пн', 'Вт', 'Ср', 'Чт', 'Пт', 'Сб'],
												monthNamesShort: ['Январь', 'Февраль', 'Март', 'Апрель', 'Май', 'Июнь', 'Июль', 'Август', 'Сентябрь', 'Октябрь', 'Ноябрь', 'Декабрь'],
												changeMonth: true,
												changeYear: true
											});
										});
									</script>
									<?php
								}

								break;
								}
								?>

							</div>
						</td>
						<td class="w25 text-center">
							<a href="javascript:void(0)" onclick="removestring('fld<?= $i ?>')" title="Удалить поле"><i class="icon-cancel-circled red"></i></a>
						</td>
					</tr>
					<?php
				}
				?>
			</table>

			<div class="wp99 text-right">
				<a href="javascript:void(0)" onclick="addfstring()" class="button greenbtn fs-10">Добавить</a>
			</div>

		</div>

		<hr>

		<DIV class="button--pane text-right">
			<a href="javascript:void(0)" onclick="refresh('resultdiv','/content/helpers/search.editor.client.php?tip=<?= $tip ?>')" class="button pull-left" title="Назад к списку"><i class="icon-left-thin"></i></a>
			&nbsp;&nbsp;<label><input name="share" type="checkbox" value="yes"/>&nbsp;Общее&nbsp;<i class="icon-info-circled blue" title="Такое представление будет доступно для использования всеми сотрудниками организации"></i></label>
			&nbsp;<A href="javascript:void(0)" onclick="$('#Form').submit()" class="button">Сохранить</A>&nbsp;
		</DIV>
	</FORM>
	<?php

}
if ( $action == 'edit' ) {

	$search = $db -> getRow( "select * from ".$sqlname."search where seid = '".$_REQUEST['seid']."' and identity = '$identity'" );

	//список полей, в которых будем включать выбор = и !=
	$ravnoEnabled = [
		"idcategory",
		"iduser",
		"clientpath",
		"territory",
		"loyalty",
		"type",
		"fav"
	];

	//список полей, в которых будем включать выбор = и !=
	$likeEnabled = [
		"idcategory",
		"iduser",
		"clientpath",
		"territory",
		"loyalty",
		"fav"
	];

	//список полей, в которых будем включать выбор больше и меньше

	$i      = 0;
	$squery = explode( ';', $search['squery'] );
	$count  = count( $squery );
	?>
	<DIV class="zagolovok">Изменение поискового представления</DIV>

	<FORM action="/content/helpers/search.editor.client.php" method="post" enctype="multipart/form-data" name="Form" id="Form">
		<INPUT type="hidden" name="action" id="action" value="edit_on">
		<INPUT name="seid" type="hidden" id="seid" value="<?= $_REQUEST['seid'] ?>">
		<INPUT name="tip" type="hidden" id="tip" value="<?= $tip ?>">
		<INPUT name="count" type="hidden" id="count" value="<?= $count ?>">

		<div style="height:70vh; max-height:70vh; overflow-x:hidden; overflow-y:auto">

			<table id="fields">
				<tr class="noDrag">
					<td class="w100 nodrop text-right"><b>Название:</b></td>
					<td colspan="4" class="nodrop">
						<input name="title" type="text" id="title" class="required" value="<?= $search['title'] ?>" style="width:100%">
					</td>
				</tr>
				<?php
				for ( $i = 0; $i < $count; $i++ ) {

					$squery2 = explode( ':', $squery[ $i ] );

					$field       = $squery2[0];
					$term        = $squery2[1];
					$field_query = $squery2[2];

					/*[
						$field,
						$term,
						$field_query
					] = $squery2;*/

					$field = explode( "--", $field );
					?>
					<tr id="fld<?= $i ?>" height="30">
						<td class="text-right">Параметр:</td>
						<td>
							<select id="field<?= $i ?>" name="field[<?= $i ?>]" style="width:100%" onchange="loadpole('inputf<?= $i ?>','field<?= $i ?>')">
								<option value="">--выбор--</option>
								<option value="date_create" <?php if ( $field[0] == 'date_create' )
									print "selected" ?>>Дата создания
								</option>
								<?php
								if ( $tip == 'client' ) {
									?>
									<option value="fav" <?php if ( $field[0] == 'fav' )
										print "selected" ?>>Ключевой клиент
									</option>
									<option value="type" <?php if ( $field[0] == 'type' )
										print "selected" ?>>Тип контрагента
									</option>
									<?php
								}

								$res = $db -> query( "select * from {$sqlname}field where fld_tip='".$tip."' and fld_on='yes' and fld_name NOT IN ('clid','pid') and identity = '$identity' order by fld_title" );
								while ($data = $db -> fetch( $res )) {

									?>
									<option value="<?= $data['fld_name'] ?>" <?php if ( $data['fld_name'] == $field[0] )
										print "selected" ?>><?= $data['fld_title'] ?></option>
									<?php

								}

								if ( $tip == 'client' ) {
									?>
									<option value="last_dog" <?php if ( $field[0] == 'last_dog' )
										print "selected" ?>>Дата последней закрытой сделки
									</option>
									<?php
									$result = $db -> query( "SELECT * FROM {$sqlname}profile_cat WHERE tip!='divider' and identity = '$identity' ORDER by ord" );
									while ($data = $db -> fetch( $result )) {
										?>
										<option value="profile--<?= $data['id'] ?>" <?php if ( $data['id'] == $field[1] and $field[0] == 'profile' )
											print "selected" ?>>Профиль:<?= $data['name'] ?></option>
										<?php
									}
								}
								?>
							</select>
						</td>
						<td class="w50">
							<select name="term[<?= $i ?>]" id="term[<?= $i ?>]">
								<option value=">=" <?php if ( $term == '>=' )
									print 'selected="selected"'; ?>>больше или равно
								</option>
								<option value="<=" <?php if ( $term == '<=' )
									print 'selected="selected"'; ?>>меньше или равно
								</option>
								<option value="=" <?php if ( $term == '=' )
									print 'selected="selected"'; ?>>равно
								</option>
								<option value="!=" <?php if ( $term == '!=' )
									print 'selected="selected"'; ?>>не равно
								</option>
								<option value="LIKE" <?php if ( $term == 'LIKE' )
									print 'selected="selected"'; ?>>содержит
								</option>
								<option value="NOT LIKE" <?php if ( $term == 'NOT LIKE' )
									print 'selected="selected"'; ?>>не содержит
								</option>
							</select>
						</td>
						<td class="w200">
							<div id="inputf<?= $i ?>">

								<?php
								switch ($field[0]) {
								case 'iduser':
									?>
									<select name="field_query[<?= $i ?>]" id="field_query[<?= $i ?>]" class="wp100">
										<?php
										$result = $db -> query( "SELECT * FROM {$sqlname}user WHERE iduser > 0 ".$sort." and identity = '$identity' order by title" );
										while ($data = $db -> fetch( $result )) { ?>
											<OPTION value="<?= $data['iduser'] ?>" <?php if ( $data['iduser'] == $field_query )
												print "selected" ?>><?= $data['title'] ?></OPTION>
										<?php } ?>
									</select>
								<?php
								break;
								case 'trash':
								case 'fav':
								?>
									<select name="field_query[<?= $i ?>]" id="field_query[<?= $i ?>]" class="wp100">
										<OPTION value="no" <?php if ( $field_query == 'no' )
											print "selected" ?>>Нет
										</OPTION>
										<OPTION value="yes" <?php if ( $field_query == 'yes' )
											print "selected" ?>>Да
										</OPTION>
									</select>
								<?php
								break;
								case 'type':
								?>
									<select name="field_query[<?= $i ?>]" id="field_query[<?= $i ?>]" class="wp100">
										<OPTION value="client" <?php if ( $field_query == 'client' )
											print "selected" ?>>Клиент. Юр.лицо
										</OPTION>
										<OPTION value="person" <?php if ( $field_query == 'person' )
											print "selected" ?>>Клиент. Физ.лицо
										</OPTION>
										<OPTION value="concurent" <?php if ( $field_query == 'concurent' )
											print "selected" ?>>Конкурент
										</OPTION>
										<OPTION value="contractor" <?php if ( $field_query == 'contractor' )
											print "selected" ?>>Поставщик
										</OPTION>
										<OPTION value="partner" <?php if ( $field_query == 'partner' )
											print "selected" ?>>Партнер
										</OPTION>
									</select>
								<?php
								break;
								case 'territory':
								?>
									<select name="field_query[<?= $i ?>]" id="field_query[<?= $i ?>]" class="wp100">
										<OPTION value="">--выбор--</OPTION>
										<?php
										$result = $db -> query( "SELECT * FROM {$sqlname}territory_cat WHERE identity = '$identity' order by title" );
										while ($data = $db -> fetch( $result )) { ?>
											<OPTION value="<?= $data['idcategory'] ?>" <?php if ( $data['idcategory'] == $field_query )
												print "selected" ?>><?= $data['title'] ?></OPTION>
										<?php } ?>
									</select>
								<?php
								break;
								case 'head_clid':
								?>
									<select name="field_query[<?= $i ?>]" id="field_query[<?= $i ?>]" class="wp100">
										<OPTION value="">--выбор--</OPTION>
										<?php
										$result = $db -> query( "SELECT * FROM {$sqlname}clientcat WHERE identity = '$identity' order by title" );
										while ($data = $db -> fetch( $result )) { ?>
											<OPTION value="<?= $data['clid'] ?>" <?php if ( $data['clid'] == $field_query )
												print "selected" ?>><?= $data['title'] ?></OPTION>
										<?php } ?>
									</select>
								<?php
								break;
								case 'tip_cmr':
								?>
									<select name="field_query[<?= $i ?>]" id="field_query[<?= $i ?>]" class="wp100">
										<OPTION value="">--выбор--</OPTION>
										<?php
										$result = $db -> query( "SELECT * FROM {$sqlname}relations WHERE identity = '$identity' ORDER by title" );
										while ($data = $db -> fetch( $result )) {
											?>
											<option <?php if ( $field_query == $data['title'] )
												print "selected" ?> value="<?= $data['title'] ?>"><?= $data['title'] ?></option>
										<?php } ?>
									</select>
								<?php
								break;
								case 'idcategory':
								?>
									<select name="field_query[<?= $i ?>]" id="field_query[<?= $i ?>]" class="wp100">
										<?php
										$result = $db -> query( "SELECT * FROM {$sqlname}category WHERE identity = '$identity' order by title" );
										while ($data = $db -> fetch( $result )) { ?>
											<OPTION value="<?= $data['idcategory'] ?>" <?php if ( $data['idcategory'] == $field_query )
												print "selected" ?>><?= $data['title'] ?></OPTION>
										<?php } ?>
									</select>
								<?php
								break;
								case 'clientpath':
								?>
									<select name="field_query[<?= $i ?>]" id="field_query[<?= $i ?>]" class="wp100">
										<?php
										$result = $db -> query( "SELECT * FROM {$sqlname}clientpath WHERE identity = '$identity' order by name" );
										while ($data = $db -> fetch( $result )) { ?>
											<OPTION value="<?= $data['id'] ?>" <?php if ( $data['id'] == $field_query )
												print "selected" ?>><?= $data['name'] ?></OPTION>
										<?php } ?>
									</select>
								<?php
								break;
								case 'loyalty':
								?>
									<select name="field_query[<?= $i ?>]" id="field_query[<?= $i ?>]" class="wp100">
										<OPTION value="">--выбор--</OPTION>
										<?php
										$result = $db -> query( "SELECT * FROM {$sqlname}loyal_cat WHERE identity = '$identity'" );
										while ($data = $db -> fetch( $result )) {
											?>
											<OPTION <?php if ( $data['idcategory'] == $field_query )
												print "selected"; ?> value="<?= $data['idcategory'] ?>"><?= $data['title'] ?></OPTION>
											<?php
										}
										?>
									</select>
								<?php
								break;
								case 'rol':
								?>
								<INPUT name="field_query[<?= $i ?>]" id="field_query[<?= $i ?>]" type="text" class="ac_input wp100" autocomplete="on" value="<?= $field_query ?>">
								<?php
								break;
								case 'date_create':
								case 'last_dog':
								?>
									<div class="variants">
										<INPUT name="field_query[<?= $i ?>]" id="field_query[<?= $i ?>]" type="text" class="ac_input datum wp100" autocomplete="off" value="<?= $field_query ?>">
										<div class="list">
											<span title="Динамично: Сегодня">{today}</span>
											<hr>
											<span title="Динамично: Текущая неделя">{week}</span>
											<span title="Динамично: Прошлая неделя">{prevweek}</span>
											<span title="Динамично: Следующая неделя">{nextweek}</span>
											<hr>
											<span title="Динамично: Текущий месяц">{month}</span>
											<span title="Динамично: Прошлый месяц">{prevmonth}</span>
											<span title="Динамично: Следующий месяц">{nextmonth}</span>
										</div>
									</div>
									<script type="text/javascript">
										$(function () {
											$("#field_query\\[<?=$i?>\\]").datepicker({
												dateFormat: 'yy-mm-dd',
												firstDay: 1,
												dayNamesMin: ['Вс', 'Пн', 'Вт', 'Ср', 'Чт', 'Пт', 'Сб'],
												monthNamesShort: ['Январь', 'Февраль', 'Март', 'Апрель', 'Май', 'Июнь', 'Июль', 'Август', 'Сентябрь', 'Октябрь', 'Ноябрь', 'Декабрь'],
												changeMonth: true,
												changeYear: true
											});
										});
									</script>
								<?php
								break;
								case 'profile':

									$result = $db -> getOne( "SELECT value FROM {$sqlname}profile_cat WHERE id = '".$field[1]."' and tip!='divider' and identity = '$identity' and identity = '$identity'" );
									$value  = explode( ";", $result );

									print '<select name="field_query['.$i.']" id="field_query['.$i.']" class="wp100">';

									//Переберем все имеющиеся профили из каталога профилей
									foreach ( $value as $k => $xvalue ) {

										if ( $xvalue != '' ) {

											$xvalue = trim( str_replace( [
												"\\n\\r",
												"\\n",
												"\\r",
												","
											], "", $xvalue ) );
											if ( $xvalue == $field_query )
												$s = "selected";

											else $s = '';
											print '<OPTION value="'.$xvalue.'" '.$s.'>'.$xvalue.'</OPTION>';
										}
									}
									print '</select>';

								break;
								default:

								//найдем тип текущего поля и если это дата, то поставим датапикер
								$res  = $db -> getRow( "SELECT * FROM {$sqlname}field WHERE fld_name = '".$field[0]."' and fld_tip = '".$tip."' and identity = '$identity'" );
								$ptip = $res["fld_temp"];
								$vars = explode( ",", $res["fld_var"] );

								if ($ptip == 'textarea' or $ptip == '--Обычное--' or $ptip == 'adres' or $ptip == ''){
								?>
								<input type="text" id="field_query[<?= $i ?>]" name="field_query[<?= $i ?>]" value="<?= $field_query ?>" class="wp100"/>
								<?php
								}
								elseif (in_array( $ptip, [
									'select',
									'multiselect',
									'inputlist',
									'radio'
								] )){
								?>
									<select name="field_query[<?= $i ?>]" class="<?= $data_array_k['fld_required'] ?> wp100" id="field_query[<?= $i ?>]">
										<option value="">--Выбор--</option>
										<?php
										foreach ( $vars as $var ) {
											?>
											<option value="<?= $var ?>" <?php if ( $var == $field_query )
												print 'selected' ?>><?= $var ?></option>
										<?php } ?>
									</select>
								<?php
								}
								elseif ($ptip == 'datum'){
								?>
									<div class="variants">
										<INPUT name="field_query[<?= $i ?>]" id="field_query[<?= $i ?>]" type="text" class="ac_input datum wp100" autocomplete="off" value="<?= $field_query ?>">
										<div class="list">
											<span title="Динамично: Сегодня">{today}</span>
											<hr>
											<span title="Динамично: Текущая неделя">{week}</span>
											<span title="Динамично: Прошлая неделя">{prevweek}</span>
											<span title="Динамично: Следующая неделя">{nextweek}</span>
											<hr>
											<span title="Динамично: Текущий месяц">{month}</span>
											<span title="Динамично: Прошлый месяц">{prevmonth}</span>
											<span title="Динамично: Следующий месяц">{nextmonth}</span>
										</div>
									</div>
									<script type="text/javascript">
										$(function () {
											$("#field_query\\[<?=$i?>\\]").datepicker({
												dateFormat: 'yy-mm-dd',
												firstDay: 1,
												dayNamesMin: ['Вс', 'Пн', 'Вт', 'Ср', 'Чт', 'Пт', 'Сб'],
												monthNamesShort: ['Январь', 'Февраль', 'Март', 'Апрель', 'Май', 'Июнь', 'Июль', 'Август', 'Сентябрь', 'Октябрь', 'Ноябрь', 'Декабрь'],
												changeMonth: true,
												changeYear: true
											});
										});
									</script>
									<?php
								}
								break;
								}
								?>

							</div>
						</td>
						<td class="w25 text-center">
							<a href="javascript:void(0)" onclick="removestring('fld<?= $i ?>')" title="Удалить поле"><i class="icon-cancel-circled red"></i></a>
						</td>
					</tr>
					<?php
				}
				?>
			</table>

			<div class="wp99 text-right">
				<a href="javascript:void(0)" onclick="addfstring()" class="button greenbtn">Добавить</a>
			</div>

		</div>

		<hr>

		<DIV class="button--pane text-right">
			<div style="float:left">
				&nbsp;&nbsp;<a href="javascript:void(0)" onclick="refresh('resultdiv','/content/helpers/search.editor.client.php?tip=<?= $tip ?>')" class="button pull-left"><i class="icon-left-thin"></i></a>
			</div>
			<label><input name="share" <?php if ( $search['share'] == 'yes' )
					print 'checked'; ?> type="checkbox" value="yes"/>&nbsp;Общее&nbsp;<i class="icon-info-circled blue" title="Такое представление будет доступно для использования всеми сотрудниками организации"></i></label>
			<A href="javascript:void(0)" onclick="$('#Form').submit()" class="button">Сохранить</A>&nbsp;
		</DIV>
	</FORM>
	<?php

}

if ( $action == 'view' ) {

	$seid = $_REQUEST['seid'];

	$search = $db -> getRow( "select * from ".$sqlname."search where seid = '".$seid."' and identity = '$identity' order by sorder" );

	/*
	 * Массив расшифровки имен полей. Старт
	 */
	$name = [
		"date_create" => "Дата создания",
		"fav"         => "Ключевой клиент",
		"type"        => "Тип контрагента",
		"last_dog"    => "Дата последней закрытой сделки"
	];

	$re = $db -> query( "select * from {$sqlname}field where fld_tip='".$search['tip']."' and identity = '$identity' order by fld_title" );
	while ($da = $db -> fetch( $re )) {

		$name[ $da['fld_name'] ] = $da['fld_title'];

	}

	$re = $db -> query( "SELECT * FROM {$sqlname}profile_cat WHERE tip != 'divider' and identity = '$identity' ORDER by ord" );
	while ($da = $db -> fetch( $re )) {

		$name[ 'profile--'.$da['id'] ] = $da['name'];

	}
	/*
	 * Массив расшифровки имен полей. Финиш
	 */

	$squery = yexplode( ';', $search['squery'] );

	foreach ( $squery as $sqy ) {

		$squery2 = yexplode( ':', $sqy );

		$field = $squery2[0];
		$term  = $squery2[1];
		$query = $squery2[2];

		/*[
			$field,
			$term,
			$field_query
		] = $squery2;*/

		if ( $term == '!=' )
			$term = "не равно";
		if ( $term == 'LIKE' )
			$term = "содержит";
		if ( $term == 'NOT LIKE' )
			$term = "не содержит";

		switch ($field) {
			case 'iduser':
				$query = current_user( (int)$query );
			break;
			case 'trash':
			case 'fav':
				$query = strtr( $query, [
					"no"  => "Нет",
					"yes" => "Да"
				] );
			break;
			case 'type':
				$query = strtr( $query, $ctypes );
			break;
			case 'territory':
				$query = current_territory( (int)$query );
			break;
			case 'idcategory':
				$query = current_category( (int)$query );
			break;
			case 'clientpath':
				$query = current_clientpathbyid( (int)$query );
			break;
			case 'loyalty':
				$query = current_loyalty( (int)$query );
			break;
			default:

			break;
		}

		if ( str_split($query)[0] == '{' ) $query = strtr( $query, $daytag );

		$tt .= '<b>'.strtr( $field, $name ).'</b> '.$term.' "<b>'.$query.'</b>"<br>';

	}

	print $tt;

	exit();

}

if ( $action == "get_pole" ) {

	$pole = $_REQUEST['pole'];
	$i    = $_REQUEST['i'] - 1;
	$pole = explode( "--", $pole );

	switch ($pole[0]) {
		case 'iduser':
			?>
			<div class="select">
				<select name="field_query[<?= $i ?>]" id="field_query[<?= $i ?>]" class="wp100">
					<?php
					$result = $db -> query( "SELECT * FROM {$sqlname}user WHERE identity = '$identity' order by title" );
					while ($data = $db -> fetch( $result )) { ?>
						<OPTION value="<?= $data['iduser'] ?>"><?= $data['title'] ?></OPTION>
					<?php } ?>
				</select>
			</div>
			<?php
		break;
		case 'fav':
			?>
			<div class="select">
				<select name="field_query[<?= $i ?>]" id="field_query[<?= $i ?>]" class="wp100">
					<OPTION value="yes">Да</OPTION>
					<OPTION value="no">Нет</OPTION>
				</select>
			</div>
			<?php
		break;
		case 'type':
			?>
			<select name="field_query[<?= $i ?>]" id="field_query[<?= $i ?>]" class="wp100">
				<OPTION value="client" <?php if ( $field_query == 'client' )
					print "selected" ?>>Клиент. Юр.лицо
				</OPTION>
				<OPTION value="person" <?php if ( $field_query == 'person' )
					print "selected" ?>>Клиент. Физ.лицо
				</OPTION>
				<OPTION value="concurent" <?php if ( $field_query == 'concurent' )
					print "selected" ?>>Конкурент
				</OPTION>
				<OPTION value="contractor" <?php if ( $field_query == 'contractor' )
					print "selected" ?>>Поставщик
				</OPTION>
				<OPTION value="partner" <?php if ( $field_query == 'partner' )
					print "selected" ?>>Партнер
				</OPTION>
			</select>
			<?php
		break;
		case 'trash':
			?>
			<div class="select">
				<select name="field_query[<?= $i ?>]" id="field_query[<?= $i ?>]" class="wp100">
					<OPTION value="no">Нет</OPTION>
					<OPTION value="yes">Да</OPTION>
				</select>
			</div>
			<?php
		break;
		case 'territory':
			?>
			<div class="select">
				<select name="field_query[<?= $i ?>]" id="field_query[<?= $i ?>]" class="wp100">
					<option value="">--выбор--</option>
					<?php
					$result = $db -> query( "SELECT * FROM {$sqlname}territory_cat WHERE identity = '$identity' order by title" );
					while ($data = $db -> fetch( $result )) { ?>
						<OPTION value="<?= $data['idcategory'] ?>"><?= $data['title'] ?></OPTION>
					<?php } ?>
				</select>
			</div>
			<?php
		break;
		case 'head_clid':
			?>
			<div class="select">
				<select name="field_query[<?= $i ?>]" id="field_query[<?= $i ?>]" class="wp100">
					<option value="">--выбор--</option>
					<?php
					$result = $db -> query( "SELECT * FROM {$sqlname}clientcat WHERE identity = '$identity' order by title" );
					while ($data = $db -> fetch( $result )) { ?>
						<OPTION value="<?= $data['clid'] ?>"><?= $data['title'] ?></OPTION>
					<?php } ?>
				</select>
			</div>
			<?php
		break;
		case 'tip_cmr':
			?>
			<div class="select">
				<select name="field_query[<?= $i ?>]" id="field_query[<?= $i ?>]" class="wp100">
					<option value="">--выбор--</option>
					<?php
					$result = $db -> query( "SELECT * FROM {$sqlname}relations WHERE identity = '$identity' ORDER by title" );
					while ($data = $db -> fetch( $result )) {
						?>
						<option value="<?= $data['title'] ?>"><?= $data['title'] ?></option>
					<?php } ?>
				</select>
			</div>
			<?php
		break;
		case 'loyalty':
			?>
			<div class="select">
				<select name="field_query[<?= $i ?>]" id="field_query[<?= $i ?>]" class="wp100">
					<option value="">--выбор--</option>
					<?php
					$result = $db -> query( "SELECT * FROM {$sqlname}loyal_cat WHERE identity = '$identity' ORDER by title" );
					while ($data = $db -> fetch( $result )) {
						?>
						<OPTION <?php if ( $data['idcategory'] == $loyalty )
							print "selected"; ?> value="<?= $data['idcategory'] ?>"><?= $data['title'] ?></OPTION>
						<?php
					}
					?>
				</select>
			</div>
			<?php
		break;
		case 'rol':
			?>
			<INPUT name="field_query[<?= $i ?>]" id="field_query[<?= $i ?>]" type="text" class="ac_input wp100" autocomplete="on">
			<?php
		break;
		case 'last_dog':
			?>
			<div class="variants">
				<INPUT name="field_query[<?= $i ?>]" id="field_query[<?= $i ?>]" type="text" class="ac_input datum wp100" autocomplete="on">
				<div class="list">
					<span title="Динамично: Сегодня">{today}</span>
					<hr>
					<span title="Динамично: Текущая неделя">{week}</span>
					<span title="Динамично: Прошлая неделя">{prevweek}</span>
					<span title="Динамично: Следующая неделя">{nextweek}</span>
					<hr>
					<span title="Динамично: Текущий месяц">{month}</span>
					<span title="Динамично: Прошлый месяц">{prevmonth}</span>
					<span title="Динамично: Следующий месяц">{nextmonth}</span>
				</div>
			</div>
			<script type="text/javascript">
				$(function () {
					$("#field_query\\[<?=$i?>\\]").datepicker({
						dateFormat: 'yy-mm-dd',
						firstDay: 1,
						dayNamesMin: ['Вс', 'Пн', 'Вт', 'Ср', 'Чт', 'Пт', 'Сб'],
						monthNamesShort: ['Январь', 'Февраль', 'Март', 'Апрель', 'Май', 'Июнь', 'Июль', 'Август', 'Сентябрь', 'Октябрь', 'Ноябрь', 'Декабрь'],
						changeMonth: true,
						changeYear: true
					});
				});
			</script>
			<?php
		break;
		case 'date_create':
			?>
			<div class="variants">
				<INPUT name="field_query[<?= $i ?>]" id="field_query[<?= $i ?>]" type="text" class="ac_input datum wp100" autocomplete="off" value="">
				<div class="list">
					<span title="Динамично: Сегодня">{today}</span>
					<hr>
					<span title="Динамично: Текущая неделя">{week}</span>
					<span title="Динамично: Прошлая неделя">{prevweek}</span>
					<span title="Динамично: Следующая неделя">{nextweek}</span>
					<hr>
					<span title="Динамично: Текущий месяц">{month}</span>
					<span title="Динамично: Прошлый месяц">{prevmonth}</span>
					<span title="Динамично: Следующий месяц">{nextmonth}</span>
				</div>
			</div>
			<script type="text/javascript">
				$(function () {
					$("#field_query\\[<?=$i?>\\]").datepicker({
						dateFormat: 'yy-mm-dd',
						firstDay: 1,
						dayNamesMin: ['Вс', 'Пн', 'Вт', 'Ср', 'Чт', 'Пт', 'Сб'],
						monthNamesShort: ['Январь', 'Февраль', 'Март', 'Апрель', 'Май', 'Июнь', 'Июль', 'Август', 'Сентябрь', 'Октябрь', 'Ноябрь', 'Декабрь'],
						changeMonth: true,
						changeYear: true
					});
				});
			</script>
			<?php
		break;
		case 'idcategory':
			?>
			<div class="select">
				<select name="field_query[<?= $i ?>]" id="field_query[<?= $i ?>]" class="wp100">
					<option value="">--выбор--</option>
					<?php
					$result = $db -> query( "SELECT * FROM {$sqlname}category WHERE identity = '$identity' order by title" );
					while ($data = $db -> fetch( $result )) { ?>
						<OPTION value="<?= $data['idcategory'] ?>"><?= $data['title'] ?></OPTION>
					<?php } ?>
				</select>
			</div>
			<?php
		break;
		case 'clientpath':
			?>
			<div class="select">
				<select name="field_query[<?= $i ?>]" id="field_query[<?= $i ?>]" class="wp100">
					<option value="">--выбор--</option>
					<?php
					$result = $db -> query( "SELECT * FROM {$sqlname}clientpath WHERE identity = '$identity' order by name" );
					while ($data = $db -> fetch( $result )) { ?>
						<OPTION value="<?= $data['id'] ?>"><?= $data['name'] ?></OPTION>
					<?php } ?>
				</select>
			</div>
			<?php
		break;
		case 'profile':

			$result = $db -> getRow( "SELECT value, tip FROM {$sqlname}profile_cat WHERE id = '".$pole[1]."' and tip!='divider' and identity = '$identity' ORDER by ord" );

			$value = explode( ";", $result["value"] );
			$tip   = $result["tip"];

			if ( $tip != 'text' and $tip != 'input' ) {

				print '
				<div class="select">
				<select name="field_query['.$i.']" id="field_query['.$i.']" class="wp100">';

				//Переберем все имеющиеся профили из каталога профилей
				foreach ( $value as $val ) {

					$val = trim( str_replace( [
						"\\n\\r",
						"\\n",
						"\\r",
						","
					], "", $val ) );
					if ( $val != '' )
						print '<OPTION value="'.$val.'">'.$val.'</OPTION>';

				}

				print '</select></div>';
			}
			else {
				print '<INPUT name="field_query['.$i.']" id="field_query['.$i.']" type="text" class="ac_input wp100" autocomplete="on" >';
			}

		break;
		default:

			//найдем тип текущего поля и если это дата, то поставим датапикер
			$res  = $db -> getRow( "SELECT fld_temp, fld_var FROM {$sqlname}field WHERE fld_name = '".$pole[0]."' and fld_tip = '".$_REQUEST['tip']."' and identity = '$identity'" );
			$ptip = $res["fld_temp"];
			$vars = explode( ",", $res["fld_var"] );

			if ( $ptip == 'textarea' or $ptip == '--Обычное--' or $ptip == 'adres' or $ptip == '' ) {

				print '<input type="text" id="field_query['.$i.']" name="field_query['.$i.']" value="'.$field_query.'" class="wp100" />';

			}
			if ( in_array( $ptip, [
				'select',
				'multiselect',
				'inputlist',
				'radio'
			] ) ) {
				?>
				<div class="select">
					<select name="field_query[<?= $i ?>]" class="wp100" id="field_query[<?= $i ?>]">
						<option value="">--Выбор--</option>
						<?php
						foreach ( $vars as $var ) {
							?>
							<option value="<?= $var ?>" <?php if ( $var == $field_query )
								print 'selected' ?>><?= $var ?></option>
						<?php } ?>
					</select>
				</div>
				<?php
			}
			if ( $ptip == 'datum' ) {
				?>
				<div class="variants">
					<input type="text" id="field_query[<?= $i ?>]" name="field_query[<?= $i ?>]" value="" class="wp100"/>
					<div class="list">
						<span title="Динамично: Сегодня">{today}</span>
						<hr>
						<span title="Динамично: Текущая неделя">{week}</span>
						<span title="Динамично: Прошлая неделя">{prevweek}</span>
						<span title="Динамично: Следующая неделя">{nextweek}</span>
						<hr>
						<span title="Динамично: Текущий месяц">{month}</span>
						<span title="Динамично: Прошлый месяц">{prevmonth}</span>
						<span title="Динамично: Следующий месяц">{nextmonth}</span>
					</div>
				</div>
				<script type="text/javascript">
					$(function () {
						$("#field_query\\[<?=$i?>\\]").datepicker({
							dateFormat: 'yy-mm-dd',
							firstDay: 1,
							dayNamesMin: ['Вс', 'Пн', 'Вт', 'Ср', 'Чт', 'Пт', 'Сб'],
							monthNamesShort: ['Январь', 'Февраль', 'Март', 'Апрель', 'Май', 'Июнь', 'Июль', 'Август', 'Сентябрь', 'Октябрь', 'Ноябрь', 'Декабрь'],
							changeMonth: true,
							changeYear: true
						});
					});
				</script>
				<?php
			}

		break;
	}

	exit();

}

if ( in_array( $action, [
	'add',
	'edit'
] ) ) {

	$opt = $prof = '';

	if ( $tip == 'client' ) {

		$result = $db -> query( "SELECT * FROM {$sqlname}profile_cat WHERE tip!='divider' and identity = '$identity' ORDER by ord" );
		while ($data = $db -> fetch( $result )) {
			$prof .= '<option value="profile--'.$data['id'].'">Профиль:'.$data['name'].'</option>';
		}

	}
	if ( $tip == 'client' ) {

		$opt .= '<option value="date_create">Дата создания</option><option value="trash">В корзине</option><option value="fav">Ключевой клиент</option><option value="type">Тип контрагента</option><option value="last_dog">Дата последней закрытой сделки</option>';

		$result = $db -> query( "select * from {$sqlname}field where fld_tip='client' and fld_name NOT IN ('clid','pid') and fld_on='yes' and identity = '$identity' order by fld_title" );
		while ($data = $db -> fetch( $result )) {

			$opt .= '<option value="'.$data['fld_name'].'">'.$data['fld_title'].'</option>';

		}

		$opt .= $prof;

	}
	if ( $tip == 'person' ) {

		$opt .= '<option value="date_create">Дата создания</option>';

		$result = $db -> query( "select * from {$sqlname}field where fld_tip='person' and fld_name NOT IN ('clid','pid') and fld_on='yes' and identity = '$identity' order by fld_title" );
		while ($data = $db -> fetch( $result )) {

			$opt .= '<option value="'.$data['fld_name'].'">'.$data['fld_title'].'</option>';

		}

	}

}
?>
<script src="/assets/js/tableHeadFixer/tableHeadFixer.js"></script>
<script>

	$(document).ready(function () {

		$('#dialog').css('width', '800px').center();

		$("#tbborder").tableHeadFixer({'head': true, 'foot': false, 'z-index': 12000}).find('th').css('z-index', '100');

		$('#Form').ajaxForm({
			dataType: 'json',
			beforeSubmit: function () {

				var $out = $('#message');
				var em = checkRequired();

				if (em === false) return false;

				$out.css('display', 'block').append('<div id=loader><img src=/assets/images/loader.gif> Загрузка данных. Пожалуйста подождите...</div>');
				return true;

			},
			success: function (data) {

				var tip = $('#tip').val();

				$('#message').fadeTo(1, 1).css('display', 'block').html(data.rez);
				setTimeout(function () {
					$('#message').fadeTo(1000, 0);
				}, 20000);

				if (data.id != '') {

					$('#resultdiv').load('/content/helpers/search.editor.client.php?tip=' + tip);

					if ($('#action').val() == 'add_on')
						$('#list #searchgroup').append('<option value="search:' + data.id + '">' + data.title + '</option>');

					if ($('#action').val() == 'edit_on')
						$('#list #searchgroup option[value="search:' + data.id + '"]').text(data.title);

				}

			}
		});

		$('#dialog').center();

	});

	$("#tbborder").tableDnD({
		onDragClass: "tableDrag",
		onDrop: function (table, row) {
			var str = '' + $('#tbborder').tableDnDSerialize();
			var url = '/content/helpers/search.editor.client.php?action=edit_order';

			$.post(url, str, function (data) {

				$('#message').fadeTo(1, 1).css('display', 'block').html(data);
				setTimeout(function () {
					$('#message').fadeTo(1000, 0);
				}, 20000);
				$('#resultdiv').load('/content/helpers/search.editor.client.php?tip=<?=$tip?>');

			});
		}
	});
	$("#fields").tableDnD({onDragClass: "tableDrag"});

	function addfstring() {

		var i = $("#fields tr").size() - 1;
		var j = i + 1;

		var htmltr = '<tr id="fld' + i + '" data-id="' + i + '"><td align="right">Параметр:</td><td><div class="select"><select id="field' + i + '" name="field\[' + i + '\]" style="width:100%" onchange="loadpole(\'inputf' + i + '\',\'field' + i + '\')"><option value="">--выбор--</option><?=$opt?></select></div></td><td width="50"><div class="select"><select name="term\[' + i + '\]" id="term\[' + i + '\]"><option value=">=">больше или равно</option><option value="<=">меньше или равно</option><option value="=">равно</option><option value="!=">не равно</option><option value="LIKE">содержит</option><option value="NOT LIKE">не содержит</option></select></div></td><td width="200"><div id="inputf' + i + '"><input type="text" id="field_query\[' + i + '\]" name="field_query\[' + i + '\]" value="" style="width:100%" /></div></td><td width="25" align="right"><a href="javascript:void(0)" onclick="removestring(\'fld' + i + '\')" title="Удалить поле"><i class="icon-cancel-circled red"></i></a></td></tr>';

		$('#fields').append(htmltr).tableDnD({onDragClass: "tableDrag"});

		$("#count").val(j);

	}

	function removestring(string) {
		if ($("#fields tr").size() > 1) {
			$('#' + string).remove();
		}
	}

	function loadpole(string, pole) {

		var i = $("#fields tr").size() - 1;
		$('#' + string).load('/content/helpers/search.editor.client.php?tip=<?=$tip?>&action=get_pole&pole=' + $('#' + pole).val() + '&i=' + i);

	}

	function sdelete(str) {
		var url = '/content/helpers/search.editor.client.php?action=delete&tip=<?=$tip?>&seid=' + str;
		$.post(url, function (data) {

			$('#message').fadeTo(1, 1).css('display', 'block').html(data);
			setTimeout(function () {
				$('#message').fadeTo(1000, 0);
			}, 20000);

			$('#resultdiv').load('/content/helpers/search.editor.client.php?tip=<?=$tip?>');
			$('#list [value="search:' + str + '"]').remove();
		});
	}

</script>