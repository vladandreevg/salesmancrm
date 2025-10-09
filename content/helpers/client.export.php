<?php
/* ============================ */
/*         SalesMan CRM         */
/* ============================ */
/* (C) 2016 Vladislav Andreev   */
/*       SalesMan Project       */
/*        www.isaler.ru         */
/*        ver. 2017.x           */
/* ============================ */
?>
<?php
set_time_limit( 0 );
error_reporting( E_ERROR );

$rootpath = realpath( __DIR__.'/../../' );

require_once $rootpath."/inc/config.php";
require_once $rootpath."/inc/dbconnector.php";
require_once $rootpath."/inc/auth.php";
require_once $rootpath."/inc/func.php";
require_once $rootpath."/inc/settings.php";
require_once $rootpath."/inc/language/".$language.".php";

$thisfile = basename( __FILE__ );

function cleanall( $string ) {
	$string = strip_tags( $string );
	$string = trim( $string );
	$string = ltrim( $string );
	$string = str_replace( "\"", "'", $string );
	$string = str_replace( "\n", " ", $string );
	$string = str_replace( "\r", " ", $string );
	$string = str_replace( "\n\r", " ", $string );
	$string = str_replace( "\r\n", " ", $string );
	$string = str_replace( "\\n", " ", $string );
	$string = str_replace( "\\r", " ", $string );
	$string = str_replace( "\\n\\r", " ", $string );
	$string = str_replace( "\\r\\n", " ", $string );
	$string = str_replace( "<", "'", $string );
	$string = str_replace( ">", "'", $string );
	$string = str_replace( "?", "", $string );
	$string = str_replace( "„", "", $string );
	$string = str_replace( "«", "", $string );
	$string = str_replace( "»", "", $string );
	/*$string = str_replace("?","&euro;",$string);
	$string = str_replace("„","&bdquo;",$string);
	$string = str_replace("«","&laquo;",$string);
	$string = str_replace("»","&raquo;",$string);*/
	$string = str_replace( "?", "", $string );
	$string = str_replace( "„", "", $string );
	$string = str_replace( "«", "", $string );
	$string = str_replace( "»", "", $string );
	$string = str_replace( ">", "", $string );
	$string = str_replace( "<", "", $string );
	$string = str_replace( "&amp;", "", $string );
	$string = str_replace( "#8220;", "", $string );
	$string = str_replace( "“", "", $string );
	$string = str_replace( "'", "", $string );
	$string = str_replace( "=", "", $string );
	$string = str_replace( ";", ". ", $string );
	$string = str_replace("&laquo;","",$string);
	$string = str_replace("&raquo;","",$string);

	return $string;
}

function clll( $string ) {
	$string = strip_tags( $string );
	$string = trim( $string );
	$string = ltrim( $string );
	$string = str_replace( "(", "'", $string );
	$string = str_replace( ")", "", $string );
	$string = str_replace( "&", "", $string );
	$string = str_replace( ";", ". ", $string );
	$string = str_replace( "javascript:void(0)", "", $string );
	$string = str_replace( "/", "", $string );

	return $string;
}

function n_format( $string ) {
	$string = str_replace( ",", ".", $string );
	$string = str_replace( " ", "", $string );
	$string = number_format( $string, 2, ',', '' );

	return $string;
}

function toCP1251( $string ) {
	return $string = iconv( "UTF-8", "windows-1251", $string );
}

$action   = $_GET[ 'action' ];
$code     = $_GET[ 'code' ];
$datatype = $_GET[ 'datatype' ];
//$history  = $_GET[ 'history' ];
//$profile  = $_GET[ 'profile' ];
//$dess     = $_GET[ 'des' ];
$format   = $_GET[ 'format' ];

if ( !$format ) $format = 'xml';

$file     = file_get_contents( $rootpath.'/cash/requisites.json' );
$recvName = json_decode( $file, true );

$scheme = $_SERVER['HTTP_SCHEME'] ?? (((isset( $_SERVER['HTTPS'] ) && $_SERVER['HTTPS'] != 'off') || 443 == $_SERVER['SERVER_PORT']) ? 'https://' : 'http://');

if ( !$code && $action != 'get_export' ) {

	print "Доступ запрещен";
	exit();

}

if ( $datatype == '' ) {

	print "Не выбран тип данных для экспорта";
	exit();

}

if ( $action == 'get_export' ) {

	$code = $db -> getOne( "SELECT ses FROM ".$sqlname."user WHERE iduser='".$iduser1."' and identity = '$identity'" );

	$c = ( $datatype == 'client' ) ? "Клиентов" : "Контактов";

	$url = $scheme.$_SERVER[ 'HTTP_HOST' ].'/content/helpers/client.export.php?datatype='.$datatype.'&code='.$code.'&action=get_xml';
	?>
	<style>
		label.field {
			background    : var(--white);
			/*margin-right: 1px;*/
			margin-bottom : 1px;
			padding       : 5px;
			border        : 1px dashed var(--gray-superlite);
			border-radius : 3px;
			box-sizing    : border-box !important;
		}
	</style>
	<DIV class="zagolovok">Экспорт данных <?= $c ?></DIV>

	<div id="formtabs" style="max-height: 70vh; overflow-y: auto; overflow-x: hidden" class="p5">

		<div class="flex-container box--child">

			<div class="flex-string wp95">

				<div class="Bold uppercase fs-07 gray2">Использовать выборки</div>

				<?php if ( $datatype == 'client' ) { ?>
					<select name="tar" id="tar" class="wp100" onchange="furl()">
						<optgroup label="Стандартные представления">
							<option value="" selected>--все--</option>
							<option value="my">Мои Клиенты</option>
							<option value="fav">Избранные Клиенты</option>
							<option value="otdel">Клиенты Подчиненных</option>
							<option value="all">Все Клиенты</option>
							<option value="trash">Корзина, Свободные</option>
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
						<optgroup label="Группы клиентов">
							<?php
							$result = $db -> query( "select * from ".$sqlname."group WHERE identity = '$identity' ORDER by name" );
							while ( $data = $db -> fetch( $result ) ) {

								if ( $data[ 'service' ] ) $s = ' *';

								$q = "
					SELECT 
						COUNT(".$sqlname."grouplist.id)
					FROM ".$sqlname."grouplist 
					LEFT JOIN ".$sqlname."clientcat ON ".$sqlname."clientcat.clid = ".$sqlname."grouplist.clid
					LEFT JOIN ".$sqlname."personcat ON ".$sqlname."personcat.pid  = ".$sqlname."grouplist.pid
					WHERE 
						(".$sqlname."clientcat.phone != '' OR ".$sqlname."personcat.mob != '' OR ".$sqlname."personcat.tel != '') AND 
						".$sqlname."grouplist.gid = '".$data[ 'id' ]."' AND 
						".$sqlname."grouplist.clid > 0 AND 
						".$sqlname."grouplist.identity = '$identity'
				";

								$count = $db -> getOne( $q );

								print '<option value="group:'.$data[ 'id' ].'">'.$data[ 'name' ].$s.' ('.$count.')</option>';
							}
							?>
						</optgroup>
						<optgroup label="Настраиваемые представления">
							<?php
							$result = $db -> getAll( "select * from ".$sqlname."search where tip='client' and iduser='".$iduser1."' and identity = '$identity' order by sorder" );
							foreach ( $result as $da ) {
								print '<option value="search:'.$da[ 'seid' ].'">'.$da[ 'title' ].'</option>';
							}
							?>
						</optgroup>
					</select>
					<div class="fs-07 gray2">Выборку можно создать в разделе "Клиенты"</div>
					<?php
				}
				else {
					?>
					<select name="tar" id="tar" class="wp100" onchange="furl()">
						<optgroup label="Стандартные представления">
							<option value="" selected>--все--</option>
							<option value="my">Мои Контакты</option>
							<option value="otdel">Контакты отдела</option>
							<option value="all">Все Контакты</option>
						</optgroup>
						<optgroup label="Группы Контактов">
							<?php
							$result = $db -> query( "select * from ".$sqlname."group WHERE identity = '$identity' ORDER by name" );
							while ( $data = $db -> fetch( $result ) ) {

								if ( $data[ 'service' ] ) $s = ' *';

								$q = "
					SELECT 
						COUNT(".$sqlname."grouplist.id)
					FROM ".$sqlname."grouplist 
					LEFT JOIN ".$sqlname."clientcat ON ".$sqlname."clientcat.clid = ".$sqlname."grouplist.clid
					LEFT JOIN ".$sqlname."personcat ON ".$sqlname."personcat.pid  = ".$sqlname."grouplist.pid
					WHERE 
						(".$sqlname."clientcat.phone != '' OR ".$sqlname."personcat.mob != '' OR ".$sqlname."personcat.tel != '') AND 
						".$sqlname."grouplist.gid = '".$data[ 'id' ]."' AND 
						".$sqlname."grouplist.pid > 0 AND 
						".$sqlname."grouplist.identity = '$identity'
				";

								$count = $db -> getOne( $q );

								print '<option value="group:'.$data[ 'id' ].'">'.$data[ 'name' ].$s.' ('.$count.')</option>';
							}
							?>
						</optgroup>
						<optgroup label="Настраиваемые представления">
							<?php
							$result = $db -> getAll( "select * from ".$sqlname."search where tip='person' and iduser='".$iduser1."' and identity = '$identity' order by sorder" );
							foreach ( $result as $da ) {
								print '<option value="search:'.$da[ 'seid' ].'">'.$da[ 'title' ].'</option>';
							}
							?>
						</optgroup>
					</select>
					<div class="fs-07 gray2">Выборку можно создать в разделе "Контакты"</div>
				<?php } ?>

			</div>
			<div class="flex-string wp5">

				<div class="Bold uppercase fs-07 gray2">&nbsp;</div>
				<div class="tagsmenuToggler hand relativ mt5" data-id="fhelper">
					<span class="fs-14 blue mt5"><i class="icon-help-circled"></i></span>
					<div class="tagsmenu fly1 right hidden" id="fhelper" style="right:0; top: 100%">
						<div class="blok1 w350 fs-09">
							<ul>
								<li>Ознакомьтесь с Документацией на модуль [
									<a href="https://salesman.pro/docs/54" target="_blank" title="Перейти в Документацию">Справка</a> ]
								</li>
								<li>Вы можете использовать <b>поисковые выборки</b> для большей гибкости [
									<a href="https://salesman.pro/docs/45#searcheditor" target="_blank" title="Перейти в Документацию">Справка</a> ]
								</li>
								<li>Если экспорт идет в формате CSV, то данные необходимо
									<b>Импортировать</b> в Excel - Вкладка "Данные" / Из текста
								</li>
								<li>Чем больше информации экспортируется, тем дольше времени занимает этот процесс!</li>
								<li>Для
									<b class="red">исключения полей</b> укажите их в блоке "Исключить" - они не будут выведены в файле экспорта
								</li>
								<li>Возможна загрузка только данных контакта, присоединенного к сдекле. Если контактов несколько, то выбирается первый</li>
							</ul>

						</div>
					</div>
				</div>

			</div>

		</div>

		<div class="divider mt10 mb10"><i class="icon-plus-circled green"></i> Включить</div>

		<div class="flex-container">

			<div class="flex-string">

				<FORM method="post" enctype="multipart/form-data" name="exportInclude" id="exportInclude">
					<div class="flex-container box--child">
						<label class="flex-string wp50 field"><input name="include" id="include" type="checkbox" value="history" onclick="furl()">&nbsp;Включить 3 активности</label>
						<?php if ( $datatype == 'client' ) { ?>
							<label class="flex-string wp50 field"><input name="include" id="include" type="checkbox" value="profile" onclick="furl()">&nbsp;Профиль клиента</label>
							<label class="flex-string wp50 field"><input name="include" id="include" type="checkbox" value="recv" onclick="furl()">&nbsp;Реквизиты клиента</label>
							<label class="flex-string wp50 field hidden"><input name="include" id="include" type="checkbox" value="contact" onclick="furl()">&nbsp;Контакт (тел. + email)</label>
						<?php } ?>
					</div>
				</FORM>

			</div>

		</div>

		<div class="divider mt10 mb10"><i class="icon-minus-circled red"></i> Исключить</div>

		<div class="flex-container">

			<div class="flex-string">

				<FORM method="post" enctype="multipart/form-data" name="exportExclude" id="exportExclude">
					<div class="flex-container box--child">

						<?php
						if ( $datatype == 'client' ) {

							$exclude_array = [
								'clid',
								'title',
								'head_clid',
								'trash',
								'fav',
								'pid2',
								'recv'
							];

							$result = $db -> query( "select * from ".$sqlname."field where fld_tip='client' and fld_on='yes' and identity = '$identity' order by fld_order" );
							while ( $data = $db -> fetch( $result ) ) {

								if ( !in_array( $data[ 'fld_name' ], (array)$exclude_array ) ) {
									print '<label class="flex-string wp50 field"><input name="exclude" id="exclude" type="checkbox" value="'.$data['fld_name'].'" onclick="furl()">&nbsp;'.$data['fld_title'].'</label>';
								}

							}

						}
						if ( $datatype == 'person' ) {

							$exclude_array = [
								'person',
								'pid'
							];

							$result = $db -> query( "select * from ".$sqlname."field where fld_tip='person' and fld_on='yes' and identity = '$identity' order by fld_order" );
							while ( $data = $db -> fetch( $result ) ) {

								if ( !in_array( $data[ 'fld_name' ], (array)$exclude_array ) ) {
									print '<label class="flex-string wp50 field"><input name="exclude" id="exclude" type="checkbox" value="'.$data['fld_name'].'" onclick="furl()">&nbsp;'.$data['fld_title'].'</label>';
								}

							}

						}
						?>

					</div>
				</FORM>

			</div>

		</div>

		<div class="flex-container box--child">

			<div class="flex-string wp100 mt10">
				<div class="Bold uppercase fs-07 gray2">Ссылка для получения XML-данных</div>
				<textarea name="url" id="url" class="wp100" style="height:120px" readonly></textarea>
			</div>

		</div>

		<div class="space-50"></div>

	</div>

	<hr>

	<div class="button--pane text-right">

		<A href="javascript:void(0)" onClick="getFile('csv')" class="button greenbtn">Получить CSV</A>
		<A href="javascript:void(0)" onClick="getFile('xml')" class="button">Получить Excel</A>
		<A href="javascript:void(0)" onClick="DClose()" class="button">Закрыть</A>

	</div>
	<script>

		var url = '<?=$url?>';
		var fullurl = '';

		$(function () {

			furl();

			$('#dialog').css('width', '600px').center();

		});

		function furl() {

			var tar = $('#tar option:selected').val();
			var formInclude = $('#exportInclude').serializeArray()
			var formExclude = $('#exportExclude').serializeArray()

			var include = []
			var exclude = []

			//console.log(formInclude)
			//console.log(formExclude)

			$.each(formInclude, function (i, field) {
				include.push(field.value)
			});
			$.each(formExclude, function (i, field) {
				exclude.push(field.value)
			});

			//console.log(include)
			//console.log(exclude)

			var newurl = url + '&include=' + include.join(",") + '&exclude=' + exclude.join(",") + '&tar=' + tar + '&action=get_xml';

			fullurl = newurl + '&save=yes';

			$('#url').val(newurl);
		}

		function getFile(format){

			//window.open(fullurl + "&format=" + format, 'blank');

			window.location.href = fullurl + "&format=" + format;
			new DClose();

		}
	</script>
	<?php
}

if ( $action == 'get_xml' ) {

	$includes = yexplode(",", (string)$_REQUEST['include']);
	$excludes = yexplode(",", (string)$_REQUEST['exclude']);

	$result   = $db -> getRow( "SELECT iduser, identity FROM ".$sqlname."user WHERE ses='".$code."'" );
	$iduser   = $result[ "iduser" ];
	$identity = $result[ "identity" ];

	if ( $iduser == '' ) {

		print 'Доступ запрещен';
		exit();

	}

	$tar = ( $_REQUEST[ 'tar' ] == '' ) ? "all" : $_REQUEST[ 'tar' ];

	if ( !$iduser1 ) $iduser1 = $iduser;

	if ( $datatype == "client" ) {

		//Исключаем поля
		$exclude_array = [
			'clid',
			'head_clid',
			'trash',
			'fav',
			'dostup',
			'creator',
			'editor'
		];

		/*$exclude_array = array_unshift($exclude_array
			'clid',
			'head_clid',
			'trash',
			'fav',
			'pid2',
			'recv2'
		);*/

		//получим поля, которые надо также исключить по запросу
		/*$result = $db -> query( "select * from ".$sqlname."field where fld_tip='client' and fld_on='yes' and identity = '$identity' order by fld_order" );
		while ( $data = $db -> fetch( $result ) ) {

			if ( $_REQUEST[ $data[ 'fld_name' ] ] == 'no' ) $exclude_array[] = $data[ 'fld_name' ];

		}*/
		foreach ($excludes as $ex) {
			$exclude_array[] = $ex;
		}

		$fields = $db -> getCol( "select fld_name from ".$sqlname."field where fld_tip='client' and fld_on = 'yes' and fld_name LIKE '%input%' and identity = '$identity'" );

		array_unshift( $fields, 'uid', 'title', 'idcategory', 'date_create', 'pid', 'type', 'phone', 'fax', 'site_url', 'mail_url', 'iduser', 'tip_cmr', 'des', 'scheme', 'address', 'clientpath', 'last_hist', 'last_dog', 'recv' );

		foreach ( $fields as $key => $field ) {

			if ( in_array( $field, (array)$exclude_array ) ) unset( $fields[ $key ] );

		}

		$recTitle = [
			"Юр.Название",
			$recvName[ 'recvInn' ],
			$recvName[ 'recvKpp' ],
			$recvName[ 'recvBankName' ],
			$recvName[ 'recvBankKs' ],
			$recvName[ 'recvBankRs' ],
			$recvName[ 'recvBankBik' ],
			$recvName[ 'recvOkpo' ],
			$recvName[ 'recvOgrn' ],
			"Руководитель",
			"Руководитель2",
			"Должность",
			"Должность2",
			"Основание",
			"Юр.Адрес"
		];

		$typeTitle = [
			"client"     => "Клиент.Юр.лицо",
			"person"     => "Клиент.Физ.лицо",
			"concurent"  => "Конкурент",
			"contractor" => "Конкурент",
			"partner"    => "Партнер"
		];

		$query = getFilterQuery( 'client', [
			'filter' => $tar,
			'fields' => $fields
		], false );

		//конечный запрос
		$query .= " ORDER BY ".$sqlname."clientcat.title";

		$clients = [];
		$header  = [];
		$g       = 0;

		//формируем запрос в БД
		$result = $db -> query( $query );
		while ( $da = $db -> fetch( $result ) ) {

			$header        = [
				"Client.ID",
				"Client.UID",
				"Создан",
				"Название"
			];
			$clients[ $g ] = [
				$da[ 'clid' ],
				$da[ 'uid' ],
				$da[ 'date_create' ],
				cleanall( $da[ 'title' ] )
			];

			$i = 3;

			$res = $db -> getAll( "select * from ".$sqlname."field where fld_tip='client' and fld_on='yes' and identity = '$identity' order by fld_order" );
			foreach ( $res as $d ) {

				if ( !in_array( $d[ 'fld_name' ], (array)$exclude_array ) && $d[ 'fld_name' ] != 'recv' ) {

					$string = '';

					switch ( $d[ 'fld_name' ] ) {
						case 'iduser':
							$string = cleanall( $da[ 'user' ] );
						break;
						case 'pid':
							$string = cleanall( $da[ 'person' ] );
						break;
						case 'idcategory':
							$string = $da[ 'category' ];
						break;
						case 'clientpath':
							$string = $da[ 'clientpath' ];
						break;
						case 'territory':
							$string = $da[ 'territory' ];
						break;
						case 'des':
							$string = cleanall( html2text( $da[ 'des' ] ) );
						break;
						case 'recv':
							$recv = cleanall( $da[ $d[ 'fld_name' ] ] );
						break;
						default:
							$string = cleanall( $da[ $d[ 'fld_name' ] ] );
						break;
					}

					$header[ $i ]        = str_replace( " ", ".", cleanall( $d[ 'fld_title' ] ) );
					$clients[ $g ][ $i ] = $string;

					if ( $d[ 'fld_name' ] == 'pid' ) {

						$prsn = personinfo($da[ 'pid' ]);

						$i++;

						$header[ $i ]        = "Должность";
						$clients[ $g ][ $i ] = $prsn['post'];

						$i++;

						$header[ $i ]        = "Email";
						$clients[ $g ][ $i ] = yexplode( ",", (string)$prsn['email'], 0 );

						$i++;

						$header[ $i ]        = "Телефон";
						$clients[ $g ][ $i ] = yexplode( ",", (string)$prsn['phone'], 0 );

						$i++;

						$header[ $i ]        = "Мобильный";
						$clients[ $g ][ $i ] = yexplode( ",", (string)$prsn['mob'], 0 );

					}

					$i++;

				}

			}

			$clients[ $g ][ $i ] = strtr( $da[ 'type' ], $typeTitle );
			$header[ $i ]        = "ТипКлиента";

			$i++;

			//реквизиты
			if ( in_array( "recv", (array)$includes) ) {

				$recvArray = explode( ";", $da[ 'recv' ] );
				for ($j = 0, $jMax = count($recTitle); $j < $jMax; $j++ ) {

					$clients[ $g ][ $i ] = cleanall( $recvArray[ $j ] );
					$header[ $i ]        = $recTitle[ $j ];

					$i++;

				}

			}

			//активности
			if ( in_array( "history", (array)$includes) ) {

				$hist = '';
				for ( $k = 0; $k < 3; $k++ ) {

					$j = $k + 1;

					$resh        = $db -> getRow( "select datum, des from ".$sqlname."history WHERE clid='".$da[ 'clid' ]."' and tip NOT IN ('ЛогCRM','СобытиеCRM') and identity = '$identity' ORDER BY cid DESC LIMIT ".$k.", ".$j );
					$datum[ $k ] = $resh[ "datum" ];
					$des[ $k ]   = cleanall( $resh[ "des" ] );

					if ( $des[ $k ] ) $hist .= $datum[ $k ].": ".$des[ $k ].";";

				}

				$clients[ $g ][ $i ] = cleanall( $hist );
				$header[ $i ]        = 'Активности';

				$i++;
			}

			if ( in_array( "profile", (array)$includes) ) {

				//считываем профиль
				$resultpc = $db -> getAll( "SELECT * FROM ".$sqlname."profile_cat WHERE tip!='divider' and identity = '$identity' ORDER by ord" );
				foreach ( $resultpc as $data ) {

					$profilev = $db -> getOne( "SELECT value FROM ".$sqlname."profile WHERE clid = '".$da[ 'clid' ]."' and id = '".$data[ 'id' ]."' and identity = '$identity'" );

					$clients[ $g ][ $i ] = cleanall( $profilev );
					$header[ $i ]        = str_replace( " ", ".", cleanall( $data[ 'name' ] ) );
					$i++;
				}

			}

			$g++;
		}

		array_unshift( $clients, $header );

		if ( $format == 'xml' ) {

			logger( '3', 'Скачивание данных в XML', $iduser1 );

			//require_once("opensource/class/php-excel.class.php");


			if ( $_REQUEST[ 'save' ] == 'yes' ) {

				Shuchkin\SimpleXLSXGen::fromArray( $clients )->downloadAs('export.clients.xlsx');

			}
			else {

				$xls = new Excel_XML( 'UTF-8', true, 'Clients' );
				$xls -> addArray( $clients );
				$xls -> printXML();

			}

		}
		if ( $format == 'csv' ) {

			//проходим массив и формируем csv-файл
			$filename = 'export.clients'.$identity.'.csv';
			$fp       = fopen( $rootpath.'/files/'.$filename, 'w' );

			//перекодируем
			for ($i = 0, $iMax = count($clients); $i < $iMax; $i++ ) {

				for ($j = 0, $jMax = count($clients[ $i ]); $j < $jMax; $j++ ) {

					$clients[ $i ][ $j ] = toCP1251( $clients[ $i ][ $j ] );

				}

			}

			foreach ( $clients as $fields ) {
				fputcsv( $fp, $fields, ";" );
			}

			fclose( $fp );

			logger( '4', 'Скачивание данных в CSV', $iduser1 );

			header( 'Content-type: application/csv' );
			header( 'Content-Disposition: attachment; filename="'.$filename.'"' );

			readfile( $rootpath.'/files/'.$filename );
			unlink( $rootpath.'/files/'.$filename );

		}

	}
	if ( $datatype == "person" ) {

		$socTitle = [
			'Блог',
			'Сайт',
			'Twitter',
			'ICQ',
			'Skype',
			'Google+',
			'Facebook',
			'VKontakte'
		];

		$sort = '';

		//получим поля, которые надо исключить также
		/*$result        = $db -> query( "select * from ".$sqlname."field where fld_tip='person' and fld_on='yes' and identity = '$identity' order by fld_order" );
		while ( $data = $db -> fetch( $result ) ) {

			if ( $_REQUEST[ $data[ 'fld_name' ] ] == 'no' ) $exclude_array[] = $data[ 'fld_name' ];

		}*/
		$exclude_array = [];
		foreach ($excludes as $ex) {
			$exclude_array[] = $ex;
		}
		$exclude_array[] = ['pid'];

		$fields = $db -> getCol( "select fld_name from ".$sqlname."field where fld_tip='person' and fld_on = 'yes' and fld_name LIKE '%input%' and identity = '$identity'" );

		array_unshift( $fields, 'person', 'ptitle', 'clid', 'tel', 'mob', 'mail', 'iduser', 'rol', 'clientpath' );

		foreach ( $fields as $key => $field ) {

			if ( in_array( $field, (array)$exclude_array ) ) unset( $fields[ $key ] );

		}

		$query = getFilterQuery( 'person', [
			'filter' => $tar,
			'fields' => $fields
		], false );

		$query .= " ORDER by ".$sqlname."personcat.person";
		//exit();

		$clients = [];
		$header  = [];
		$g       = 0;

		//формируем запрос в БД
		$result = $db -> query( $query );
		while ( $da = $db -> fetch( $result ) ) {

			$header        = [
				"Person.ID"
			];
			$clients[ $g ] = [
				$da[ 'pid' ]
			];

			$i = 2;

			$res = $db -> getAll( "select * from ".$sqlname."field where fld_tip='person' and fld_on='yes' and identity = '$identity' order by fld_order" );
			foreach ( $res as $d ) {

				if ( !in_array( $d[ 'fld_name' ], (array)$exclude_array ) ) {

					$string = '';

					switch ( $d[ 'fld_name' ] ) {
						case 'iduser':
							$string = cleanall( $da[ 'user' ] );
						break;
						case 'clid':
							$string = cleanall( $da[ 'client' ] );
						break;
						case 'rol':
							$string = $da[ 'role' ];
						break;
						case 'clientpath':
							$string = $da[ 'clientpath' ];
						break;
						case 'loyalty':
							$string = $da[ 'loyalty' ];
						break;
						case 'social':
							$social = cleanall( $da[ 'social' ] );
						break;
						default:
							$string = cleanall( $da[ $d[ 'fld_name' ] ] );
						break;
					}

					$clients[ $g ][ $i ] = cleanall( $string );
					$header[ $i ]        = str_replace( " ", ".", cleanall( $d[ 'fld_title' ] ) );
					$i++;

					if ( $d[ 'fld_name' ] == 'clid' ) {

						$clients[ $g ][ $i ] = $da[ 'clid' ];
						$header[ $i ]        = 'Client.ID';
						$i++;

					}
					if ( $d[ 'fld_name' ] == 'clid' ) {

						$clients[ $g ][ $i ] = cleanall( getClientData( $da[ 'clid' ], "uid" ) );
						$header[ $i ]        = 'Client.UID';
						$i++;

					}

				}

			}

			if ( !in_array( "social", (array)$exclude_array ) ) {

				$socArray = explode( ";", $social );
				for ($j = 0, $jMax = count($socTitle); $j < $jMax; $j++ ) {

					$clients[ $g ][ $i ] = cleanall( $socArray[ $j ] );
					$header[ $i ]        = cleanall( $socTitle[ $j ] );

					$i++;

				}
				$social   = '';
				$socArray = [];

			}

			if ( in_array( "history", (array)$includes) ) {

				$hist = '';
				for ( $k = 0; $k < 3; $k++ ) {

					$j = $k + 1;

					$resulth     = $db -> getRow( "select datum, des from ".$sqlname."history WHERE pid='".$da[ 'pid' ]."' and tip NOT IN ('ЛогCRM','СобытиеCRM') and identity = '$identity' ORDER BY cid DESC LIMIT ".$k.", ".$j );
					$datum[ $k ] = cleanall( $resulth[ "datum" ] );
					$des[ $k ]   = cleanall( $resulth[ "des" ] );

					if ( $des[ $k ] ) $hist .= $datum[ $k ].":".$des[ $k ].";";

				}

				$clients[ $g ][ $i ] = cleanall( $hist );
				$header[ $i ]        = 'Активности';

				$i++;
			}

			$g++;
		}

		array_unshift( $clients, $header );

		if ( $format == 'xml' ) {

			logger( '3', 'Скачивание данных в XML', $iduser1 );

			/*
			$xls = new Excel_XML( 'UTF-8', true, 'Persons' );
			$xls -> addArray( $clients );

			if ( $_REQUEST[ 'save' ] == 'yes' ) {
				$xls -> generateXML( 'export.persons'.$identity );
			}
			else {
				print $xls -> printXML();
			}
			*/

			if ( $_REQUEST[ 'save' ] == 'yes' ) {

				Shuchkin\SimpleXLSXGen::fromArray( $clients )->downloadAs('export.persons.xlsx');

			}
			else {

				$xls = new Excel_XML( 'UTF-8', true, 'Persons' );
				$xls -> addArray( $clients );
				$xls -> printXML();

			}

		}
		if ( $format == 'csv' ) {

			//проходим массив и формируем csv-файл
			$filename = 'export.persons'.$identity.'.csv';
			$fp       = fopen( $rootpath.'/files/'.$filename, 'w' );

			//перекодируем
			for ($i = 0, $iMax = count($clients); $i < $iMax; $i++ ) {

				for ($j = 0, $jMax = count($clients[ $i ]); $j < $jMax; $j++ ) {

					$clients[ $i ][ $j ] = toCP1251( cleanall( $clients[ $i ][ $j ] ) );

				}

			}

			foreach ( $clients as $fields ) {

				fputcsv( $fp, $fields, ';' );
			}

			fclose( $fp );

			logger( '4', 'Скачивание данных в CSV', $iduser1 );

			header( 'Content-type: application/csv' );
			header( 'Content-Disposition: attachment; filename="'.$filename.'"' );

			readfile( $rootpath.'/files/'.$filename );
			unlink( $rootpath.'/files/'.$filename );

		}

	}

}