<?php
/* ============================ */
/*         SalesMan CRM         */
/* ============================ */
/* (C) 2016 Vladislav Andreev   */
/*       SalesMan Project       */
/*        www.isaler.ru         */
/*        ver. 2017.x           */
/* ============================ */

#Задача - сделать Общие поисковые представления

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

global $userRights;

$tip    = $_REQUEST[ 'tip' ];
$action = $_REQUEST[ 'action' ];

/*
 * Массив расшифровки имен полей. Старт
 */
$name = [
	"datum"       => "Дата создания",
	"datum_plan"  => "Дата плановая",
	"datum_izm"   => "Дата изменения",
	"datum_start" => "Дата начала периода",
	"datum_end"   => "Дата конца периода",
	"close"       => "Статус",
	"sid"         => "Статус закрытия",
	"datum_close" => "Дата закрытия",
	"kol_fact"    => "Сумма факт.",
	"title"       => "Название",
	"coid"        => "Выигравший конкурент",
	"today"       => "Сегодня",
	"week"        => "Текущая неделя",
	"prevweek"    => "Предыдущая неделя",
	"nextweek"    => "Следующая неделя",
	"month"       => "Текущий месяц",
	"prevmonth"   => "Предыдущий месяц",
	"nextmonth"   => "Следующий месяц"
];

$daytag = [
	"today"     => "Сегодня",
	"week"      => "Текущая неделя",
	"prevweek"  => "Предыдущая неделя",
	"nextweek"  => "Следующая неделя",
	"month"     => "Текущий месяц",
	"prevmonth" => "Предыдущий месяц",
	"nextmonth" => "Следующий месяц"
];

if ( $action == '' ) {

	$exclude = [
		'clid',
		'pid'
	];

	$re = $db -> query( "select * from {$sqlname}field where fld_tip='dogovor' and identity = '$identity' order by fld_title" );
	while ( $da = $db -> fetch( $re ) ) {

		$name[ $da[ 'fld_name' ] ] = $da[ 'fld_title' ];

	}

	?>
	<DIV class="zagolovok">Пользовательские представления</DIV>

	<div style="height:70vh; overflow:auto">

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
			$result = $db -> query( "select * from {$sqlname}search where tip='dog' and (iduser='$iduser1' or share = 'yes') and identity = '$identity' order by sorder" );
			while ( $data = $db -> fetch( $result ) ) {

				$tt = '';

				$share = ( $data[ 'share' ] == 'yes' ) ? ' <i class="icon-users-1 red" title="Общее представление"></i>' : '';

				$squery = yexplode( ';', (string)$data[ 'squery' ] );

				foreach ($squery as $sqy) {

					$sq = yexplode( ':', $sqy );

					[
						$field,
						$term,
						$query
					] = $sq;

					if ( $term == '!=' ) $term = "не равно";
					if ( $term == 'LIKE' ) $term = "содержит";
					if ( $term == 'NOT LIKE' ) $term = "не содержит";

					switch ( $field ) {
						case 'iduser':
							$query = current_user( $query );
						break;
						case 'close':
							$query = strtr( $query, [
								"no"  => "Активна",
								"yes" => "Закрыта"
							] );
						break;
						case 'direction':
							$query = current_direction( (int)$query );
						break;
						case 'idcategory':
							$query = current_dogstepname( (int)$query ).'%';
						break;
						case 'mcid':
							$query = current_company( (int)$query );
						break;
						case 'tip':
							$query = current_dogtype( (int)$query );
						break;
						case 'clientpath':
							$query = current_clientpath( (int)$query );
						break;
						case 'loyalty':
							$query = current_loyalty( (int)$query );
						break;
						case 'sid':
							$quer  = current_dstatus( (int)$query );
							$query = $quer[ 'title' ];
						break;
						default:

						break;
					}

					$tt .= '<b>'.strtr( $field, $name ).'</b> '.$term.' "<b>'.$query.'</b>"<br>';

				}
				?>
				<tr class="ha disable--select" id="<?= $data[ 'seid' ] ?>">
					<td class="w60 text-center"><?= $data[ 'sorder' ] ?></td>
					<td>
						<div class="fs-11"><b class="blue"><?= $data[ 'title' ] ?></b><?= $share ?>
							<i class="icon-angle-up hand togglerbox" data-id="block<?= $data[ 'seid' ] ?>" id="mapic"></i>
						</div>
						<div class="hidden1 smalltxt gray2" id="block<?= $data[ 'seid' ] ?>">
							<?= $tt ?>
							<div class="mt5 broun">Владелец: <?= current_user( $data[ 'iduser' ], 'yes' ) ?></div>
						</div>
					</td>
					<td class="w120 text-center">
						<div class="paddtop5">
							&nbsp;&nbsp;<A href="javascript:void(0)" onClick="doLoad('content/helpers/search.editor.deal.php?action=add&seid=<?= $data[ 'seid' ] ?>&tip=<?= $tip ?>');" title="Клонировать"><i class="icon-paste green"></i></A>
							<?php
							if ( $data[ 'iduser' ] == $iduser1 || $isadmin == 'on' ) {
								?>
								&nbsp;&nbsp;
								<A href="javascript:void(0)" onClick="doLoad('content/helpers/search.editor.deal.php?action=edit&seid=<?= $data[ 'seid' ] ?>&tip=<?= $tip ?>');" title="Изменить"><i class="icon-pencil blue"></i></A>&nbsp;&nbsp;
								<A href="javascript:void(0)" onClick="sdelete('<?= $data[ 'seid' ] ?>');" title="Удалить"><i class="icon-cancel red"></i></A>
								<?php
							}
							else print '&nbsp;&nbsp;<A href="javascript:void(0)" title="Изменить"><i class="icon-pencil gray"></i></A>&nbsp;&nbsp;<A href="javascript:void(0)" title="Удалить"><i class="icon-cancel gray"></i></A>';
							?>
						</div>
					</td>
				</tr>
			<?php } ?>
			</tbody>
		</table>

	</div>

	<hr>

	<div class="text-right">
		<A href="javascript:void(0)" onClick="refresh('resultdiv','content/helpers/search.editor.deal.php?action=add&seid=<?= $data[ 'seid' ] ?>');" class="button">Добавить</A>&nbsp;
		<A href="javascript:void(0)" onClick="DClose()" class="button">Закрыть</A>
	</div>
	<?php
}

if ( $action == 'add' ) {

	if ( $_REQUEST[ 'seid' ] > 0 ) {

		$search            = $db -> getRow( "select * from ".$sqlname."search where seid = '".$_REQUEST[ 'seid' ]."' and identity = '$identity' order by sorder" );
		$search[ 'title' ] .= "(клон)";

	}
	else $search[ 'title' ] = "Выборка";

	?>
	<DIV class="zagolovok">Создание поискового представления</DIV>

	<FORM action="/content/helpers/search.editor.deal.php" method="post" enctype="multipart/form-data" name="Form" id="Form">
		<INPUT type="hidden" name="action" id="action" value="add_on">
		<INPUT name="tip" type="hidden" id="tip" value="dog">

		<div style="height:70vh; max-height:70vh; overflow-x:hidden; overflow-y:auto">

			<table id="fields">
				<tr class="noDrag">
					<td width="100" class="nodrop">
						<div class="fnameForm"><b>Название:</b></div>
					</td>
					<td colspan="4" class="nodrop">
						<input name="title" type="text" id="title" class="required wp100" value="<?= $search[ 'title' ] ?>">
					</td>
				</tr>
				<?php
				$squery = yexplode( ';', (string)$search[ 'squery' ] );
				$count  = count( $squery );

				for ( $i = 0; $i < $count; $i++ ) {

					$squery2     = explode( ':', $squery[ $i ] );

					//$field       = $squery2[ 0 ];
					//$term        = $squery2[ 1 ];
					//$field_query = $squery2[ 2 ];

					[
						$field,
						$term,
						$field_query
					] = $squery2;

					?>
					<tr id="fld<?= $i ?>">
						<td class="text-right">Параметр:</td>
						<td>
							<select id="field<?= $i ?>" name="field[<?= $i ?>]" onchange="loadpole('inputf<?= $i ?>','field<?= $i ?>')" class="wp100">
								<optgroup label="Общие">
									<option <?php if ( $field == 'datum' ) print "selected" ?> value="datum">Дата создания</option>
									<option <?php if ( $field == 'datum_plan' ) print "selected" ?> value="datum_plan">Дата плановая</option>
									<option <?php if ( $field == 'datum_izm' ) print "selected" ?> value="datum_izm">Дата изменения</option>
									<option <?php if ( $field == 'datum_start' ) print "selected" ?> value="datum_start">Дата начала</option>
									<option <?php if ( $field == 'datum_end' ) print "selected" ?> value="datum_end">Дата конца</option>
									<option <?php if ( $field == 'idcategory' ) print "selected" ?> value="idcategory">Этап</option>
									<option <?php if ( $field == 'title' ) print "selected" ?> value="title"><?=$fieldsNames['dogovor']['title']?></option>
									<option <?php if ( $field == 'tip' ) print "selected" ?> value="tip">Тип</option>
									<option <?php if ( $field == 'direction' ) print "selected" ?> value="direction"><?=$fieldsNames['dogovor']['direction']?></option>
									<option <?php if ( $field == 'kol' ) print "selected" ?> value="kol">Сумма</option>
									<option <?php if ( $field == 'marga' ) print "selected" ?> value="marga">Маржа</option>
									<option <?php if ( $field == 'adres' ) print "selected" ?> value="adres">Адрес</option>
									<option <?php if ( $field == 'iduser' ) print "selected" ?> value="iduser">Ответственный</option>
									<option <?php if ( $field == 'partner' ) print "selected" ?> value="partner">Партнер</option>
									<option <?php if ( $field == 'con_id' ) print "selected" ?> value="con_id">Подрядчик</option>
									<option <?php if ( $field == 'mcid' ) print "selected" ?> value="mcid"><?=$fieldsNames['dogovor']['mcid']?></option>
									<?php
									$result = $db -> query( "select * from {$sqlname}field where fld_tip='dogovor' and fld_on='yes' and fld_name LIKE '%input%' and identity = '$identity' order by fld_title" );
									while ( $data = $db -> fetch( $result ) ) {

										if ( $data[ 'fld_name' ] == $field ) $s = 'selected';
										else $s = '';
										print '<option value="'.$data[ 'fld_name' ].'" '.$s.'>'.$data[ 'fld_title' ].'</option>';

									}
									?>
								</optgroup>
								<optgroup label="Закрытые сделки">
									<option <?php if ( $field == 'datum_close' ) print "selected" ?> value="datum_close">Дата закрытия</option>
									<option <?php if ( $field == 'close' ) print "selected" ?> value="close">Активна/Закрыта</option>
									<option <?php if ( $field == 'sid' ) print "selected" ?> value="sid">Статус закрытия</option>
									<option <?php if ( $field == 'kol_fact' ) print "selected" ?> value="kol_fact">Сумма закрытия</option>
									<option <?php if ( $field == 'coid' ) print "selected" ?> value="coid">Выигравший конкурент</option>
								</optgroup>
							</select>
						</td>
						<td class="w50">
							<select name="term[<?= $i ?>]" id="term[<?= $i ?>]">
								<option value=">" <?php if ( $term == '>' ) print "selected" ?>>больше</option>
								<option value=">=" <?php if ( $term == '>=' ) print "selected" ?>>больше или равно</option>
								<option value="<" <?php if ( $term == '<' ) print "selected" ?>>меньше</option>
								<option value="<=" <?php if ( $term == '<=' ) print "selected" ?>>меньше или равно</option>
								<option value="=" <?php if ( $term == '=' ) print "selected" ?>>равно</option>
								<option value="!=" <?php if ( $term == '!=' ) print "selected" ?>>не равно</option>
								<option value="LIKE" <?php if ( $term == 'LIKE' ) print "selected" ?>>содержит</option>
								<option value="NOT LIKE" <?php if ( $term == 'NOT LIKE' ) print "selected" ?>>не содержит</option>
							</select>
						</td>
						<td class="w200">
							<div id="inputf<?= $i ?>">
								<?php
								switch ( $field ) {
								case 'iduser':
									?>
									<div class="select">
										<select name="field_query[<?= $i ?>]" id="field_query[<?= $i ?>]" class="wp100">
											<?php
											$result = $db -> query( "SELECT * FROM {$sqlname}user WHERE iduser > 0 $sort and identity = '$identity' order by title" );
											while ( $data = $db -> fetch( $result ) ) {
												?>
												<OPTION value="<?= $data[ 'iduser' ] ?>" <?php if ( $data[ 'iduser' ] == $field_query ) print "selected" ?>><?= $data[ 'title' ] ?></OPTION>
											<?php } ?>
										</select>
									</div>
								<?php
								break;
								case 'close':
								?>
									<div class="select">
										<select name="field_query[<?= $i ?>]" id="field_query[<?= $i ?>]" class="wp100">
											<OPTION value="no" <?php if ( $field_query == 'no' ) print "selected" ?>>Активна</OPTION>
											<OPTION value="yes" <?php if ( $field_query == 'yes' ) print "selected" ?>>Закрыта</OPTION>
										</select>
									</div>
								<?php
								break;
								case 'idcategory':
								?>
									<div class="select">
										<select name="field_query[<?= $i ?>]" id="field_query[<?= $i ?>]" class="wp100">
											<?php
											$result = $db -> query( "SELECT * FROM {$sqlname}dogcategory WHERE identity = '$identity' order by title" );
											while ( $data = $db -> fetch( $result ) ) { ?>
												<OPTION value="<?= $data[ 'idcategory' ] ?>" <?php if ( $data[ 'idcategory' ] == $field_query ) print "selected" ?>><?= $data[ 'title' ] ?>%</OPTION>
											<?php } ?>
										</select>
									</div>
								<?php
								break;
								case 'sid':
								?>
									<div class="select">
										<select name="field_query[<?= $i ?>]" id="field_query[<?= $i ?>]" class="wp100">
											<?php
											$result = $db -> query( "SELECT * FROM {$sqlname}dogstatus WHERE identity = '$identity' order by title" );
											while ( $data = $db -> fetch( $result ) ) { ?>
												<OPTION value="<?= $data[ 'sid' ] ?>"><?= $data[ 'title' ] ?></OPTION>
											<?php } ?>
										</select>
									</div>
								<?php
								break;
								case 'tip':
								?>
									<div class="select">
										<select name="field_query[<?= $i ?>]" id="field_query[<?= $i ?>]" class="wp100">
											<OPTION value="">Не заполнено</OPTION>
											<?php
											$result = $db -> query( "SELECT * FROM {$sqlname}dogtips WHERE identity = '$identity' order by title" );
											while ( $data = $db -> fetch( $result ) ) { ?>
												<OPTION value="<?= $data[ 'tid' ] ?>" <?php if ( $data[ 'tid' ] == $field_query ) print "selected" ?>><?= $data[ 'title' ] ?></OPTION>
											<?php } ?>
										</select>
									</div>
								<?php
								break;
								case 'direction':
								?>
									<div class="select">
										<select name="field_query[<?= $i ?>]" id="field_query[<?= $i ?>]" class="wp100">
											<OPTION value="">Не заполнено</OPTION>
											<?php
											$result = $db -> query( "SELECT * FROM {$sqlname}direction WHERE identity = '$identity' order by title" );
											while ( $data = $db -> fetch( $result ) ) { ?>
												<OPTION value="<?= $data[ 'id' ] ?>" <?php if ( $data[ 'id' ] == $field_query ) print "selected" ?>><?= $data[ 'title' ] ?></OPTION>
											<?php } ?>
										</select>
									</div>
								<?php
								break;
								case 'mcid':
								?>
									<div class="select">
										<select name="field_query[<?= $i ?>]" id="field_query[<?= $i ?>]" class="wp100">
											<?php
											$result = $db -> query( "SELECT * FROM {$sqlname}mycomps WHERE identity = '$identity' order by name_shot" );
											while ( $data = $db -> fetch( $result ) ) { ?>
												<OPTION value="<?= $data[ 'id' ] ?>" <?php if ( $data[ 'id' ] == $field_query ) print "selected" ?>><?= $data[ 'name_shot' ] ?></OPTION>
											<?php } ?>
										</select>
									</div>
								<?php
								break;
								case 'partner':
								?>
									<div class="select">
										<select name="field_query[<?= $i ?>]" id="field_query[<?= $i ?>]" class="wp100">
											<OPTION value="">Не заполнено</OPTION>
											<?php
											$result = $db -> query( "SELECT * FROM {$sqlname}contractor WHERE tip='partner' and identity = '$identity' order by title" );
											while ( $data = $db -> fetch( $result ) ) { ?>
												<OPTION value="<?= $data[ 'con_id' ] ?>" <?php if ( $data[ 'con_id' ] == $field_query ) print "selected" ?>><?= $data[ 'title' ] ?></OPTION>
											<?php } ?>
										</select>
									</div>
								<?php
								break;
								case 'con_id':
								?>
									<div class="select">
										<select name="field_query[<?= $i ?>]" id="field_query[<?= $i ?>]" class="wp100">
											<?php
											$result = $db -> query( "SELECT * FROM {$sqlname}contractor WHERE tip='contractor' and identity = '$identity' order by title" );
											while ( $data = $db -> fetch( $result ) ) { ?>
												<OPTION value="<?= $data[ 'con_id' ] ?>" <?php if ( $data[ 'con_id' ] == $field_query ) print "selected" ?>><?= $data[ 'title' ] ?></OPTION>
											<?php } ?>
										</select>
									</div>
								<?php
								break;
								case 'datum':
								case 'datum_plan':
								case 'datum_izm':
								case 'datum_start':
								case 'datum_end':
								case 'datum_close':
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
								default:

								//найдем тип текущего поля и если это дата, то поставим датапикер
								$res  = $db -> getRow( "SELECT * FROM {$sqlname}field WHERE fld_name = '".$pole."' and fld_tip = 'dogovor' and identity = '$identity'" );
								$ptip = $res[ "fld_temp" ];
								$vars = explode( ",", (string)$res[ "fld_var" ] );

								if ( $ptip == 'textarea' || $ptip == '--Обычное--' || $ptip == 'adres' || $ptip == '' ) {

									print '<input type="text" id="field_query['.$i.']" name="field_query['.$i.']" value="'.$field_query.'"class="wp100" />';

								}
								if ( $ptip == 'select' || $ptip == 'multiselect' ){
								?>
									<div class="select">
										<select name="field_query[<?= $i ?>]" id="field_query[<?= $i ?>]" class="wp100">
											<option value="">--Выбор--</option>
											<?php
											foreach ($vars as $var) {
												?>
												<option value="<?= $var ?>" <?php if ( $var == $field_query ) print 'selected' ?>><?= $var ?></option>
											<?php } ?>
										</select>
									</div>
								<?php
								}
								if ( $ptip == 'datum' || $ptip == 'datetime' ){
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

				&nbsp;&nbsp;<a href="javascript:void(0)" onClick="refresh('resultdiv','content/helpers/search.editor.deal.php')" class="button pull-left"><i class="icon-left-thin"></i></a>

			</div>
			<label><input name="share" <?php if ( $search[ 'share' ] == 'yes' ) print 'checked'; ?> type="checkbox" value="yes"/>&nbsp;Общее&nbsp;<i class="icon-info-circled blue" title="Такое представление будет доступно для использования всеми сотрудниками организации"></i></label>
			<A href="javascript:void(0)" onClick="$('#Form').submit()" class="button">Сохранить</A>&nbsp;
		</DIV>
	</FORM>
	<?php
}
if ( $action == 'edit' ) {

	$search = $db -> getRow( "select * from {$sqlname}search where seid = '".$_REQUEST[ 'seid' ]."' and identity = '$identity' order by sorder" );

	?>
	<DIV class="zagolovok">Изменение поискового представления</DIV>

	<FORM action="/content/helpers/search.editor.deal.php" method="post" enctype="multipart/form-data" name="Form" id="Form">
		<INPUT type="hidden" name="action" id="action" value="edit_on">
		<INPUT name="seid" type="hidden" id="seid" value="<?= $_REQUEST[ 'seid' ] ?>">

		<div style="height:70vh; max-height:70vh; overflow-x:hidden; overflow-y:auto">

			<table id="fields">
				<tr class="noDrag">
					<td class="w100 nodrop">
						<div class="fnameForm"><b>Название:</b></div>
					</td>
					<td colspan="4" class="nodrop">
						<input name="title" type="text" id="title" value="<?= $search[ 'title' ] ?>" class="required wp100">
					</td>
				</tr>
				<?php
				$squery = yexplode( ';', (string)$search[ 'squery' ] );
				$count  = count( $squery );

				for ( $i = 0; $i < $count; $i++ ) {

					$squery2     = explode( ':', $squery[ $i ] );

					//$field       = $squery2[ 0 ];
					//$term        = $squery2[ 1 ];
					//$field_query = $squery2[ 2 ];

					[
						$field,
						$term,
						$field_query
					] = $squery2;

					?>
					<tr id="fld<?= $i ?>">
						<td class="text-right">Параметр:</td>
						<td>
							<select id="field<?= $i ?>" name="field[<?= $i ?>]" class="wp100" onchange="loadpole('inputf<?= $i ?>','field<?= $i ?>')">
								<optgroup label="Общие">
									<option <?php if ( $field == 'datum' ) print "selected" ?> value="datum">Дата создания</option>
									<option <?php if ( $field == 'datum_plan' ) print "selected" ?> value="datum_plan">Дата плановая</option>
									<option <?php if ( $field == 'datum_izm' ) print "selected" ?> value="datum_izm">Дата изменения</option>
									<option <?php if ( $field == 'datum_start' ) print "selected" ?> value="datum_start">Дата начала</option>
									<option <?php if ( $field == 'datum_end' ) print "selected" ?> value="datum_end">Дата конца</option>
									<?php
									if($fieldsNames['dogovor']['idcategory'] != ''){
									?>
									<option <?php if ( $field == 'idcategory' ) print "selected" ?> value="idcategory"><?=$fieldsNames['dogovor']['idcategory']?></option>
									<?php } ?>
									<option <?php if ( $field == 'title' ) print "selected" ?> value="title">Название сделки</option>
									<?php
									if($fieldsNames['dogovor']['tip'] != ''){
									?>
									<option <?php if ( $field == 'tip' ) print "selected" ?> value="tip"><?=$fieldsNames['dogovor']['tip']?></option>
									<?php } ?>
									<?php
									if($fieldsNames['dogovor']['direction'] != ''){
									?>
									<option <?php if ( $field == 'direction' ) print "selected" ?> value="direction"><?=$fieldsNames['dogovor']['direction']?></option>
									<?php } ?>
									<option <?php if ( $field == 'kol' ) print "selected" ?> value="kol">Сумма</option>
									<option <?php if ( $field == 'marga' ) print "selected" ?> value="marga">Маржа</option>
									<?php
									if($fieldsNames['dogovor']['adres'] != ''){
									?>
									<option <?php if ( $field == 'adres' ) print "selected" ?> value="adres"><?=$fieldsNames['dogovor']['adres']?></option>
									<?php } ?>
									<option <?php if ( $field == 'iduser' ) print "selected" ?> value="iduser"><?=$fieldsNames['dogovor']['iduser']?></option>
									<option <?php if ( $field == 'partner' ) print "selected" ?> value="partner">Партнер</option>
									<option <?php if ( $field == 'con_id' ) print "selected" ?> value="con_id">Подрядчик</option>
									<option <?php if ( $field == 'mcid' ) print "selected" ?> value="mcid"><?=$fieldsNames['dogovor']['mcid']?></option>
									<?php
									$result = $db -> query( "select * from {$sqlname}field where fld_tip='dogovor' and fld_on='yes' and fld_name LIKE '%input%' and identity = '$identity' order by fld_title" );
									while ( $data = $db -> fetch( $result ) ) {

										if ( $data[ 'fld_name' ] == $field ) $s = 'selected';
										else $s = '';
										print '<option value="'.$data[ 'fld_name' ].'" '.$s.'>'.$data[ 'fld_title' ].'</option>';

									}
									?>
								</optgroup>
								<optgroup label="Закрытые сделки">
									<option <?php if ( $field == 'datum_close' ) print "selected" ?> value="datum_close">Дата закрытия</option>
									<option <?php if ( $field == 'close' ) print "selected" ?> value="close">Активна/Закрыта</option>
									<option <?php if ( $field == 'sid' ) print "selected" ?> value="sid">Статус закрытия</option>
									<option <?php if ( $field == 'kol_fact' ) print "selected" ?> value="kol_fact">Сумма закрытия</option>
									<option <?php if ( $field == 'coid' ) print "selected" ?> value="coid">Выигравший конкурент</option>
								</optgroup>
							</select>
						</td>
						<td class="w50">
							<select name="term[<?= $i ?>]" id="term[<?= $i ?>]">
								<option value=">" <?php if ( $term == '>' ) print "selected" ?>>больше</option>
								<option value=">=" <?php if ( $term == '>=' ) print "selected" ?>>больше или равно</option>
								<option value="<" <?php if ( $term == '<' ) print "selected" ?>>меньше</option>
								<option value="<=" <?php if ( $term == '<=' ) print "selected" ?>>меньше или равно</option>
								<option value="=" <?php if ( $term == '=' ) print "selected" ?>>равно</option>
								<option value="!=" <?php if ( $term == '!=' ) print "selected" ?>>не равно</option>
								<option value="LIKE" <?php if ( $term == 'LIKE' ) print "selected" ?>>содержит</option>
								<option value="NOT LIKE" <?php if ( $term == 'NOT LIKE' ) print "selected" ?>>не содержит</option>
							</select>
						</td>
						<td class="w200">
							<div id="inputf<?= $i ?>">
								<?php
								switch ( $field ) {
									case 'iduser':
										?>
										<div class="select">
											<select name="field_query[<?= $i ?>]" id="field_query[<?= $i ?>]" class="wp100">
												<?php
												$result = $db -> query( "SELECT * FROM {$sqlname}user WHERE identity = '$identity' order by title" );
												while ( $data = $db -> fetch( $result ) ) {
													?>
													<OPTION value="<?= $data[ 'iduser' ] ?>" <?php if ( $data[ 'iduser' ] == $field_query ) print "selected"; ?>><?= $data[ 'title' ] ?></OPTION>
												<?php } ?>
											</select>
										</div>
										<?php
									break;
									case 'close':
										?>
										<div class="select">
											<select name="field_query[<?= $i ?>]" id="field_query[<?= $i ?>]" class="wp100">
												<OPTION value="no" <?php if ( $field_query == 'no' ) print "selected" ?>>Активна</OPTION>
												<OPTION value="yes" <?php if ( $field_query == 'yes' ) print "selected" ?>>Закрыта</OPTION>
											</select>
										</div>
										<?php
									break;
									case 'idcategory':
										?>
										<div class="select">
											<select name="field_query[<?= $i ?>]" id="field_query[<?= $i ?>]" class="wp100">
												<?php
												$result = $db -> query( "SELECT * FROM {$sqlname}dogcategory WHERE identity = '$identity' order by title" );
												while ( $data = $db -> fetch( $result ) ) { ?>
													<OPTION value="<?= $data[ 'idcategory' ] ?>" <?php if ( $data[ 'idcategory' ] == $field_query ) print "selected" ?>><?= $data[ 'title' ] ?>%</OPTION>
												<?php } ?>
											</select>
										</div>
										<?php
									break;
									case 'sid':
										?>
										<div class="select">
											<select name="field_query[<?= $i ?>]" id="field_query[<?= $i ?>]" class="wp100">
												<?php
												$result = $db -> query( "SELECT * FROM {$sqlname}dogstatus WHERE identity = '$identity' order by title" );
												while ( $data = $db -> fetch( $result ) ) { ?>
													<OPTION value="<?= $data[ 'sid' ] ?>" <?php if ( $data[ 'sid' ] == $field_query ) print "selected" ?>><?= $data[ 'title' ] ?></OPTION>
												<?php } ?>
											</select>
										</div>
										<?php
									break;
									case 'tip':
										?>
										<div class="select">
											<select name="field_query[<?= $i ?>]" id="field_query[<?= $i ?>]" class="wp100">
												<OPTION value="">Не заполнено</OPTION>
												<?php
												$result = $db -> query( "SELECT * FROM {$sqlname}dogtips WHERE identity = '$identity' order by title" );
												while ( $data = $db -> fetch( $result ) ) { ?>
													<OPTION value="<?= $data[ 'tid' ] ?>" <?php if ( $data[ 'tid' ] == $field_query ) print "selected" ?>><?= $data[ 'title' ] ?></OPTION>
												<?php } ?>
											</select>
										</div>
										<?php
									break;
									case 'direction':
										?>
										<div class="select">
											<select name="field_query[<?= $i ?>]" id="field_query[<?= $i ?>]" class="wp100">
												<OPTION value="">Не заполнено</OPTION>
												<?php
												$result = $db -> query( "SELECT * FROM {$sqlname}direction WHERE identity = '$identity' order by title" );
												while ( $data = $db -> fetch( $result ) ) { ?>
													<OPTION value="<?= $data[ 'id' ] ?>" <?php if ( $data[ 'id' ] == $field_query ) print "selected" ?>><?= $data[ 'title' ] ?></OPTION>
												<?php } ?>
											</select>
										</div>
										<?php
									break;
									case 'mcid':
										?>
										<div class="select">
											<select name="field_query[<?= $i ?>]" id="field_query[<?= $i ?>]" class="wp100">
												<?php
												$result = $db -> query( "SELECT * FROM {$sqlname}mycomps WHERE identity = '$identity' order by name_shot" );
												while ( $data = $db -> fetch( $result ) ) { ?>
													<OPTION value="<?= $data[ 'id' ] ?>" <?php if ( $data[ 'id' ] == $field_query ) print "selected" ?>><?= $data[ 'name_shot' ] ?></OPTION>
												<?php } ?>
											</select>
										</div>
										<?php
									break;
									case 'partner':
										?>
										<div class="select">
											<select name="field_query[<?= $i ?>]" id="field_query[<?= $i ?>]" class="wp100">
												<OPTION value="">Не заполнено</OPTION>
												<?php
												$result = $db -> query( "SELECT * FROM {$sqlname}contractor WHERE tip='partner' and identity = '$identity' order by title" );
												while ( $data = $db -> fetch( $result ) ) { ?>
													<OPTION value="<?= $data[ 'con_id' ] ?>" <?php if ( $data[ 'con_id' ] == $field_query ) print "selected" ?>><?= $data[ 'title' ] ?></OPTION>
												<?php } ?>
											</select>
										</div>
										<?php
									break;
									case 'con_id':
										?>
										<div class="select">
											<select name="field_query[<?= $i ?>]" id="field_query[<?= $i ?>]" class="wp100">
												<?php
												$result = $db -> query( "SELECT * FROM {$sqlname}contractor WHERE tip='contractor' and identity = '$identity' order by title" );
												while ( $data = $db -> fetch( $result ) ) { ?>
													<OPTION value="<?= $data[ 'con_id' ] ?>" <?php if ( $data[ 'con_id' ] == $field_query ) print "selected" ?>><?= $data[ 'title' ] ?></OPTION>
												<?php } ?>
											</select>
										</div>
										<?php
									break;
									case 'datum':
									case 'datum_plan':
									case 'datum_izm':
									case 'datum_start':
									case 'datum_end':
									case 'datum_close':
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
										<?php
									break;
									default:

										//найдем тип текущего поля и если это дата, то поставим датапикер
										$res = $db -> getRow( "SELECT * FROM {$sqlname}field WHERE fld_name = '".$field."' and fld_tip = 'dogovor' and identity = '$identity'" );
										$ptip = $res[ "fld_temp" ];
										$vars = explode( ",", $res[ "fld_var" ] );

										if ( $ptip == 'textarea' || $ptip == '--Обычное--' || $ptip == 'adres' || $ptip == '' ) {

											print '<input type="text" id="field_query['.$i.']" name="field_query['.$i.']" value="'.$field_query.'"class="wp100" />';

										}
										if ( in_array( $ptip, [
											'select',
											'multiselect',
											'inputlist',
											'radio'
										] ) ) {
											?>
											<div class="select">
												<select name="field_query[<?= $i ?>]" id="field_query[<?= $i ?>]" class="wp100">
													<option value="">--Выбор--</option>
													<?php
													foreach ($vars as $var) {
														?>
														<option value="<?= $var ?>" <?php if ( $var == $field_query ) print 'selected' ?>><?= $var ?></option>
													<?php } ?>
												</select>
											</div>
											<?php
										}
										if ( $ptip == 'datum' || $ptip == 'datetime' ) {
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
				&nbsp;&nbsp;<a href="javascript:void(0)" onClick="refresh('resultdiv','content/helpers/search.editor.deal.php')" class="button pull-left"><i class="icon-left-thin"></i></a>
			</div>
			<label><input name="share" <?php if ( $search[ 'share' ] == 'yes' ) print 'checked'; ?> type="checkbox" value="yes"/>&nbsp;Общее&nbsp;<i class="icon-info-circled blue" title="Такое представление будет доступно для использования всеми сотрудниками организации"></i></label>
			<A href="javascript:void(0)" onClick="$('#Form').submit()" class="button">Сохранить</A>&nbsp;
		</DIV>
	</FORM>
	<script>
		$('.datum').datepicker({
			dateFormat: 'yy-mm-dd',
			firstDay: 1,
			dayNamesMin: ['Вс', 'Пн', 'Вт', 'Ср', 'Чт', 'Пт', 'Сб'],
			monthNamesShort: ['Январь', 'Февраль', 'Март', 'Апрель', 'Май', 'Июнь', 'Июль', 'Август', 'Сентябрь', 'Октябрь', 'Ноябрь', 'Декабрь'],
			changeMonth: true,
			changeYear: true
		});
	</script>
	<?php

}

if ( $action == 'view' ) {

	$seid = $_REQUEST[ 'seid' ];

	$search = $db -> getRow( "select * from ".$sqlname."search where seid = '".$seid."' and identity = '$identity' order by sorder" );

	/*
	 * Массив расшифровки имен полей. Старт
	 */
	$name = [
		"datum"       => "Дата создания",
		"datum_plan"  => "Дата плановая",
		"datum_izm"   => "Дата изменения",
		"datum_start" => "Дата начала периода",
		"datum_end"   => "Дата конца периода",
		"close"       => "Статус",
		"sid"         => "Статус закрытия",
		"datum_close" => "Дата закрытия",
		"kol_fact"    => "Сумма факт.",
		"title"       => $fieldsNames['dogovor']['title'],
		"coid"        => "Выигравший конкурент"
	];

	$exclude = [
		'clid',
		'pid'
	];
	$re      = $db -> query( "select fld_name, fld_title from {$sqlname}field where fld_tip='dogovor' and identity = '$identity' order by fld_title" );
	while ( $da = $db -> fetch( $re ) ) {

		$name[ $da[ 'fld_name' ] ] = $da[ 'fld_title' ];

	}

	/*
	 * Массив расшифровки имен полей. Финиш
	 */

	$squery = yexplode( ';', $search[ 'squery' ] );

	foreach ($squery as $sqy) {

		$sq = yexplode( ':', $sqy );

		$field = $sq[ 0 ];
		$term  = $sq[ 1 ];
		$query = $sq[ 2 ];

		/*[
			$field,
			$term,
			$query
		] = $sq;*/

		if ( $term == '!=' ) $term = "не равно";
		if ( $term == 'LIKE' ) $term = "содержит";
		if ( $term == 'NOT LIKE' ) $term = "не содержит";

		switch ( $field ) {
			case 'iduser':
				$query = current_user( $query );
			break;
			case 'close':
				$query = strtr( $query, [
					"no"  => "Активна",
					"yes" => "Закрыта"
				] );
			break;
			case 'direction':
				$query = current_direction( (int)$query );
			break;
			case 'mcid':
				$query = current_company( (int)$query );
			break;
			case 'idcategory':
				$query = current_dogstepname( (int)$query ).'%';
			break;
			case 'clientpath':
				$query = current_clientpath( (int)$query );
			break;
			case 'loyalty':
				$query = current_loyalty( (int)$query );
			break;
			case 'sid':
				$quer  = current_dstatus( (int)$query );
				$query = $quer[ 'title' ];
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

if ( $action == "add_on" ) {

	$title = $_REQUEST[ 'title' ];

	$field       = array_values( $_REQUEST[ 'field' ] );
	$term        = array_values( $_REQUEST[ 'term' ] );
	$field_query = array_values( $_REQUEST[ 'field_query' ] );

	$share  = $_POST[ 'share' ];
	$squery = '';

	//Составляем массив записи
	$count = count( $field );
	for ( $i = 0; $i < $count; $i++ ) {

		$s = ( $i > 0 ) ? ";" : "";

		$squery .= $s.$field[ $i ].":".$term[ $i ].":".$field_query[ $i ];

	}

	//вычисляем количество имеющихся записей
	$order = $db -> getOne( "select COUNT(*) from {$sqlname}search where tip='dog' and iduser='".$iduser1."' and identity = '$identity' order by sorder" ) + 1;

	//Обновляем данные для текущей записи
	try {

		$db -> query( "insert into {$sqlname}search (seid,tip,title,squery,sorder,iduser,share,identity) values (NULL, 'dog', '".$title."', '".$squery."', '".$order."', '".$iduser1."', '".$share."','$identity')" );

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

	$title = $_REQUEST[ 'title' ];
	$seid  = $_REQUEST[ 'seid' ];

	$field       = array_values( $_REQUEST[ 'field' ] );
	$term        = array_values( $_REQUEST[ 'term' ] );
	$field_query = array_values( $_REQUEST[ 'field_query' ] );

	$share  = $_POST[ 'share' ];
	$squery = '';

	//Составляем массив записи
	$count = count( $field );

	for ( $i = 0; $i < $count; $i++ ) {

		$s      = ( $i > 0 ) ? ";" : "";
		$squery .= $s.$field[ $i ].":".$term[ $i ].":".$field_query[ $i ];

	}

	//Обновляем данные для текущей записи
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

	$table = $_REQUEST[ 'tbborder' ];
	$err    = 0;

	//Обновляем данные для текущей записи
	foreach ($table as $i => $tbl) {

		$db -> query( "update {$sqlname}search set sorder = '$i' where seid = '$tbl' and identity = '$identity'" );

	}

	print 'Обновлено. Ошибок: '.$err;

	exit();

}

if ( $action == "delete" ) {

	$seid = $_REQUEST[ 'seid' ];

	$db -> query( "delete from {$sqlname}search where seid = '".$seid."' and identity = '$identity'" );
	print "Запись удалена";

	exit();

}

if ( $action == "get_pole" ) {

	$pole         = $_REQUEST[ 'pole' ];
	$i            = $_REQUEST[ 'i' ] - 1;

	switch ( $pole ) {
		case 'iduser':
			?>
			<div class="select">
				<select name="field_query[<?= $i ?>]" id="field_query[<?= $i ?>]" class="wp100">
					<?php
					$result = $db -> query( "SELECT * FROM ".$sqlname."user WHERE identity = '$identity' order by title" );
					while ( $data = $db -> fetch( $result ) ) { ?>
						<OPTION value="<?= $data[ 'iduser' ] ?>"><?= $data[ 'title' ] ?></OPTION>
					<?php } ?>
				</select>
			</div>
			<?php
		break;
		case 'close':
			?>
			<div class="select">
				<select name="field_query[<?= $i ?>]" id="field_query[<?= $i ?>]" class="wp100">
					<OPTION value="no" <?php if ( $field_query == 'no' ) print "selected" ?>>Активна</OPTION>
					<OPTION value="yes" <?php if ( $field_query == 'yes' ) print "selected" ?>>Закрыта</OPTION>
				</select>
			</div>
			<?php
		break;
		case 'idcategory':
			?>
			<div class="select">
				<select name="field_query[<?= $i ?>]" id="field_query[<?= $i ?>]" class="wp100">
					<?php
					$result = $db -> query( "SELECT * FROM ".$sqlname."dogcategory WHERE identity = '$identity' order by title" );
					while ( $data = $db -> fetch( $result ) ) { ?>
						<OPTION value="<?= $data[ 'idcategory' ] ?>"><?= $data[ 'title' ] ?>%</OPTION>
					<?php } ?>
				</select>
			</div>
			<?php
		break;
		case 'sid':
			?>
			<div class="select">
				<select name="field_query[<?= $i ?>]" id="field_query[<?= $i ?>]" class="wp100">
					<?php
					$result = $db -> query( "SELECT * FROM ".$sqlname."dogstatus WHERE identity = '$identity' order by title" );
					while ( $data = $db -> fetch( $result ) ) { ?>
						<OPTION value="<?= $data[ 'sid' ] ?>"><?= $data[ 'title' ] ?></OPTION>
					<?php } ?>
				</select>
			</div>
			<?php
		break;
		case 'tip':
			?>
			<div class="select">
				<select name="field_query[<?= $i ?>]" id="field_query[<?= $i ?>]" class="wp100">
					<OPTION value="">Не заполнено</OPTION>
					<?php
					$result = $db -> query( "SELECT * FROM ".$sqlname."dogtips WHERE identity = '$identity' order by title" );
					while ( $data = $db -> fetch( $result ) ) { ?>
						<OPTION value="<?= $data[ 'tid' ] ?>"><?= $data[ 'title' ] ?></OPTION>
					<?php } ?>
				</select>
			</div>
			<?php
		break;
		case 'direction':
			?>
			<div class="select">
				<select name="field_query[<?= $i ?>]" id="field_query[<?= $i ?>]" class="wp100">
					<OPTION value="">Не заполнено</OPTION>
					<?php
					$result = $db -> query( "SELECT * FROM ".$sqlname."direction WHERE identity = '$identity' order by title" );
					while ( $data = $db -> fetch( $result ) ) { ?>
						<OPTION value="<?= $data[ 'id' ] ?>"><?= $data[ 'title' ] ?></OPTION>
					<?php } ?>
				</select>
			</div>
			<?php
		break;
		case 'mcid':
			?>
			<div class="select">
				<select name="field_query[<?= $i ?>]" id="field_query[<?= $i ?>]" class="wp100">
					<?php
					$result = $db -> query( "SELECT * FROM ".$sqlname."mycomps WHERE identity = '$identity' order by name_shot" );
					while ( $data = $db -> fetch( $result ) ) { ?>
						<OPTION value="<?= $data[ 'id' ] ?>" <?php if ( $data[ 'id' ] == $field_query ) print "selected" ?>><?= $data[ 'name_shot' ] ?></OPTION>
					<?php } ?>
				</select>
			</div>
			<?php
		break;
		case 'partner':
			?>
			<div class="select">
				<select name="field_query[<?= $i ?>]" id="field_query[<?= $i ?>]" class="wp100">
					<OPTION value="">Не заполнено</OPTION>
					<?php
					$result = $db -> query( "SELECT * FROM ".$sqlname."contractor WHERE tip='partner' and identity = '$identity' order by title" );
					while ( $data = $db -> fetch( $result ) ) { ?>
						<OPTION value="<?= $data[ 'con_id' ] ?>"><?= $data[ 'title' ] ?></OPTION>
					<?php } ?>
				</select>
			</div>
			<?php
		break;
		case 'con_id':
			?>
			<div class="select">
				<select name="field_query[<?= $i ?>]" id="field_query[<?= $i ?>]" class="wp100">
					<?php
					$result = $db -> query( "SELECT * FROM ".$sqlname."contractor WHERE tip='contractor' and identity = '$identity' order by title" );
					while ( $data = $db -> fetch( $result ) ) { ?>
						<OPTION value="<?= $data[ 'con_id' ] ?>"><?= $data[ 'title' ] ?></OPTION>
					<?php } ?>
				</select>
			</div>
			<?php
		break;
		case 'datum':
		case 'datum_plan':
		case 'datum_izm':
		case 'datum_start':
		case 'datum_end':
		case 'datum_close':
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
			<?php
		break;
		default:

			//print $pole;

			//найдем тип текущего поля и если это дата, то поставим датапикер
			$res = $db -> getRow( "SELECT * FROM ".$sqlname."field WHERE fld_name = '".$pole."' and fld_tip = 'dogovor' and identity = '$identity'" );
			$ptip = $res[ "fld_temp" ];
			$vars = explode( ",", $res[ "fld_var" ] );

			if ( $ptip == 'textarea' || $ptip == '--Обычное--' || $ptip == 'adres' || $ptip == '' ) {

				print '<input type="text" id="field_query['.$i.']" name="field_query['.$i.']" value="'.$field_query.'"class="wp100" />';

			}
			if ( in_array( $ptip, [
				'select',
				'multiselect',
				'inputlist',
				'radio'
			] ) ) {
				?>
				<div class="select">
					<select name="field_query[<?= $i ?>]" id="field_query[<?= $i ?>]" class="wp100">
						<option value="">--Выбор--</option>
						<?php
						foreach ($vars as $var) {
							?>
							<option value="<?= $var ?>" <?php if ( $var == $field_query ) print 'selected' ?>><?= $var ?></option>
						<?php } ?>
					</select>
				</div>
				<?php
			}
			if ( $ptip == 'datum' || $ptip == 'datetime' ) {
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
				<?php
			}

		break;
	}

	?>
	<script>
		$('.datum').datepicker({
			dateFormat: 'yy-mm-dd',
			firstDay: 1,
			dayNamesMin: ['Вс', 'Пн', 'Вт', 'Ср', 'Чт', 'Пт', 'Сб'],
			monthNamesShort: ['Январь', 'Февраль', 'Март', 'Апрель', 'Май', 'Июнь', 'Июль', 'Август', 'Сентябрь', 'Октябрь', 'Ноябрь', 'Декабрь'],
			changeMonth: true,
			changeYear: true
		});
	</script>
	<?php

	exit();
}

if ( $action == 'update_select' ) {
	?>

	<div class="paddtop10 paddbott10">
		<div class="select">
			<select name="list" id="list" class="jcontent" style="width: 100%;" onchange="$('#tid').val(''); $('#idcategory').val(''); change_us(); get_user(); configpage();">
				<optgroup label="Стандартные представления">
					<option value="my" <?php if ( $tar == 'my' ) print "selected" ?>>Мои Сделки</option>
					<option value="otdel" <?php if ( $tar == 'otdel' ) print "selected" ?>>Сделки Подчиненных</option>
					<?php if ( $tipuser != "Менеджер продаж" || $userRights['alls'] ) { ?>
					<option value="all" <?php if ( $tar == 'all' ) print "selected" ?>>Все Сделки</option>
					<?php } ?>
					<option value="close" <?php if ( $tar == 'close' ) print "selected" ?>>Закрытые сделки</option>
				</optgroup>
				<optgroup label="Пользовательские представления">
					<?php
					$result = $db -> query( "select * from {$sqlname}search where tip='dog' and (iduser='".$iduser1."' or share='yes') and identity = '$identity' order by sorder" );
					while ( $data = $db -> fetch( $result ) ) {
						print '<option value="search:'.$data[ 'seid' ].'">'.$data[ 'title' ].'</option>';
					}
					?>
				</optgroup>
			</select>
		</div>
		<span class="smalltxt gray">Представления</span>
		<a href="javascript:void(0)" onclick="doLoad('content/helpers/search.editor.deal.php?tip=dog');" title="Редактор представлений" data-step="6" data-intro="<h1>Редактор представлений.</h1>Поможет создать и использовать готовый набор фильтров" data-position="right"><i class="icon-pencil blue"></i></a>
	</div>
	<?php
}

if ( in_array( $action, [
	'add',
	'edit'
] ) ) {

	$opt    = '';
	$result = $db -> query( "select * from {$sqlname}field where fld_tip='dogovor' and fld_on='yes' and fld_name LIKE '%input%' and identity = '$identity' order by fld_title" );
	while ( $data = $db -> fetch( $result ) ) {

		$opt .= '<option value="'.$data[ 'fld_name' ].'">'.$data[ 'fld_title' ].'</option>';

	}

}

$option = '';
if($fieldsNames['dogovor']['idcategory'] !== ''){
	$option .= '<option value="idcategory">'.$fieldsNames['dogovor']['idcategory'].'</option>';
}
if($fieldsNames['dogovor']['tip'] !== ''){
	$option .= '<option value="tip">'.$fieldsNames['dogovor']['tip'].'</option>';
}
if($fieldsNames['dogovor']['direction'] !== ''){
	$option .= '<option value="direction">'.$fieldsNames['dogovor']['direction'].'</option>';
}
if($fieldsNames['dogovor']['mcid'] !== ''){
	$option .= '<option value="mcid">'.$fieldsNames['dogovor']['mcid'].'</option>';
}

?>
<script src="/assets/js/tableHeadFixer/tableHeadFixer.js"></script>
<script>

	/*<option value="tip"><?=$fieldsNames['dogovor']['tip']?></option><option value="direction"><?=$fieldsNames['dogovor']['direction']?></option><option value="adres"><?=$fieldsNames['dogovor']['adres']?></option><option value="mcid"><?=$fieldsNames['dogovor']['mcid']?></option>*/

	$(function () {

		$('#dialog').css('width', '702px');

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

				$('#message').fadeTo(1, 1).css('display', 'block').html(data.rez);
				setTimeout(function () {
					$('#message').fadeTo(1000, 0);
				}, 20000);

				if (data.id != '') {

					$('#resultdiv').load('content/helpers/search.editor.deal.php');

					if ($('#action').val() == 'add_on')
						$('#list #searchgroup').append('<option value="search:' + data.id + '">' + data.title + '</option>');

					if ($('#action').val() == 'edit_on')
						$('#list #searchgroup option[value="search:' + data.id + '"]').text(data.title);

				}

			}
		});

		$('.datum').datepicker({
			dateFormat: 'yy-mm-dd',
			firstDay: 1,
			dayNamesMin: ['Вс', 'Пн', 'Вт', 'Ср', 'Чт', 'Пт', 'Сб'],
			monthNamesShort: ['Январь', 'Февраль', 'Март', 'Апрель', 'Май', 'Июнь', 'Июль', 'Август', 'Сентябрь', 'Октябрь', 'Ноябрь', 'Декабрь'],
			changeMonth: true,
			changeYear: true
		});

		$('#dialog').center();
	});

	$("#tbborder").tableDnD({
		onDragClass: "tableDrag",
		onDrop: function (table, row) {
			var str = '' + $('#tbborder').tableDnDSerialize();
			var url = 'content/helpers/search.editor.deal.php?action=edit_order';

			$.post(url, str, function (data) {

				$('#message').empty().css('display', 'block').html(data).fadeOut(10000);
				$('#resultdiv').load('content/helpers/search.editor.deal.php');

			});
		}
	}).disableSelection();

	$("#fields").tableDnD({onDragClass: "tableDrag"});

	function addfstring() {

		var i = $("#fields tr").size() - 1;
		var htmltr = '<tr id="fld' + i + '"><td align="right">Параметр:</td><td><div class="select"><select id="field' + i + '" name="field\[' + i + '\]"class="wp100" onchange="loadpole(\'inputf' + i + '\',\'field' + i + '\')"><optgroup label="Общие"><option value="">--выбор--</option><option value="datum">Дата создания</option><option value="datum_plan">Дата плановая</option><option value="datum_izm">Дата изменения</option><option value="datum_start">Дата начала</option><option value="datum_end">Дата завершения</option><option value="iduser"><?=$fieldsNames['dogovor']['iduser']?></option><option value="kol">Сумма</option><option value="marga">Маржа</option><option value="title">Название</option><?=$option.$opt?></optgroup><optgroup label="Закрытые сделки"><option value="datum_close">Дата закрытия</option><option value="close">Активна/Закрыта</option><option value="sid">Статус закрытия</option><option value="kol_fact">Сумма закрытия</option><option value="coid">Выигравший конкурент</option></optgroup></select></div></td><td width="50"><div class="select"><select name="term\[\]" id="term\[\]"><option value=">">больше</option><option value=">=">больше или равно</option><option value="<">меньше</option><option value="<=">меньше или равно</option><option value="=">равно</option><option value="!=">не равно</option><option value="LIKE">содержит</option><option value="NOT LIKE">не содержит</option></select></div></td><td width="200"><div id="inputf' + i + '"><input type="text" id="field_query\[\]" name="field_query\[\]" value=""class="wp100" /></div></td><td width="25" align="center"><a href="javascript:void(0)" onclick="removestring(\'fld' + i + '\')" title="Удалить поле"><i class="icon-cancel-circled red"></i></a></td></tr>';

		$('#fields').append(htmltr);

		$("#fields").tableDnD({onDragClass: "tableDrag"});
	}

	function removestring(string) {
		if ($("#fields tr").size() > 1) {
			$('#' + string).remove();
		}
	}

	function loadpole(string, pole) {
		var i = $("#fields tr").size() - 1;

		$.get('/content/helpers/search.editor.deal.php?action=get_pole&pole=' + $('#' + pole).val() + '&i=' + i, function (data) {
			$('#' + string).html(data);
		})
			.complete(function () {
				$('.datum').datepicker({
					dateFormat: 'yy-mm-dd',
					firstDay: 1,
					dayNamesMin: ['Вс', 'Пн', 'Вт', 'Ср', 'Чт', 'Пт', 'Сб'],
					monthNamesShort: ['Январь', 'Февраль', 'Март', 'Апрель', 'Май', 'Июнь', 'Июль', 'Август', 'Сентябрь', 'Октябрь', 'Ноябрь', 'Декабрь'],
					changeMonth: true,
					changeYear: true
				});
			});

	}

	function sdelete(str) {
		var url = 'content/helpers/search.editor.deal.php?action=delete&seid=' + str;
		$.post(url, function (data) {

			$('#message').empty().css('display', 'block').html(data).fadeOut(10000);
			$('#resultdiv').load('content/helpers/search.editor.deal.php');

			$('#list [value="search:' + str + '"]').remove();

		});
	}

</script>