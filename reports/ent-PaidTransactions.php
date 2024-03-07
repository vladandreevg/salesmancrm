<?php
/**
 * @license  http://isaler.ru/
 * @author   Vladislav Andreev, http://iandreyev.ru/
 * @charset  UTF-8
 * @version  6.4
 */
?>
<?php
error_reporting( E_ERROR );
header( "Pragma: no-cache" );

include "../inc/config.php";
include "../inc/dbconnector.php";
include "../inc/auth.php";
include "../inc/settings.php";
include "../inc/func.php";

$action = $_REQUEST['action'];
$da1    = $_REQUEST['da1'];
$da2    = $_REQUEST['da2'];
$users  = $_REQUEST['user_list'];
$field  = $_REQUEST['field'];
$query  = $_REQUEST['field_query'];
$sort   = '';

if ( !$action )
	$action = 'list';

// Формирование сортировки по дате и сотрудникам
$thisfile = basename( $_SERVER['PHP_SELF'] );
$prefix   = $_SERVER['DOCUMENT_ROOT']."/";

if ( $users[0] < 1 )
	$sort .= str_replace( "iduser", $sqlname."dogovor.iduser", get_people( $iduser1 ) );
else               $sort .= " and ".$sqlname."dogovor.iduser IN (".implode( ",", $users ).")";

$sort .= " and (".$sqlname."dogovor.datum BETWEEN '".$da1." 00:00:01' and '".$da2." 23:59:59')";

for ( $i = 0; $i < count( $field ); $i++ ) {

	$exclude = [];
	if ( !in_array( $field[ $i ], $exclude ) and $field[ $i ] != '' )
		$sort .= " and ".$sqlname."dogovor.".$field[ $i ]."='".$query[ $i ]."'";

}

// Функция ввода id 'ов для отчета
if ( $action == "edit" ) {
	?>
	<div class="zagolovok">Ввод оплаченных сделок</div>
	<FORM action="reports/<?= $thisfile ?>" method="post" enctype="multipart/form-data" name="iform" id="iform" autocomplete="off">
		<INPUT type="hidden" name="action" id="action" value="list">
		<textarea name="deals" id="text" style="width:100%; height:200px"></textarea>
		<hr>
		<div class="infodiv">Укажите номера сделок для отчета(разделитель - " , ")</div>
		<hr>
		<div align="right">
			<A href="#" onClick="$('#iform').submit()" class="button">Сформировать</A>&nbsp;
			<A href="#" onClick="DClose()" class="button">Отмена</A>
		</div>
	</form>

	<!-- Обработчик формы-->
	<script>
		$('#iform').ajaxForm({
			beforeSubmit: function () {
				var $out = $('#message');

				$out.empty();

				$('#dialog').css('display', 'none');
				$('#dialog_container').css('display', 'none');
				$('#message').fadeTo(1, 1).css('display', 'block').append('<div id=loader><img src=/assets/images/loader.gif> Загрузка данных. Пожалуйста подождите...</div>');

			},
			success: function (data) {

				$('#contentdiv').html(data);
				$('#message').fadeTo(1, 0);

			}
		});
	</script>
	<?php
	exit();
}

// Функция вывода отчета
if ( $action == 'list' ) {

	//Получение данных и формирование запроса
	$dids = yimplode( ",", yexplode( ",", str_replace( "\n", ",", $_REQUEST['deals'] ) ) );
	$sort = ($dids != '') ? " and ".$sqlname."speca.did IN ($dids)" : $sort;

	$q = "
	SELECT
		".$sqlname."speca.title as title,
		".$sqlname."speca.price as price,
		".$sqlname."speca.edizm as edizm,
		".$sqlname."speca.kol as kol,
		".$sqlname."speca.did as did,
		".$sqlname."dogovor.payer as payer,
		".$sqlname."user.title as user
	FROM ".$sqlname."speca
		LEFT JOIN ".$sqlname."dogovor ON ".$sqlname."speca.did = ".$sqlname."dogovor.did
		LEFT JOIN ".$sqlname."price ON ".$sqlname."speca.prid = ".$sqlname."price.n_id
		LEFT JOIN ".$sqlname."user ON ".$sqlname."dogovor.iduser = ".$sqlname."user.iduser
	WHERE
		".$sqlname."speca.spid > 0
		$sort
		and ".$sqlname."speca.identity = '$identity'
	ORDER BY ".$sqlname."speca.did
	";

	//перебираем сотрудников и считаем показатели
	$re = $db -> getAll( $q );
	foreach ( $re as $da ) {

		$credit = [];

		//найдем счета по сделке
		$r = $db -> getAll( "SELECT * FROM ".$sqlname."credit WHERE did = '".$da['did']."' and identity = '$identity'" );
		foreach ( $r as $d ) {

			if ( $d['do'] == 'on' )
				$do = '<i class="icon-plus-circled green"></i>';
			else $do = '<i class="icon-minus-circled gray"></i>';

			if ( $d['invoice'] == '' )
				$d['invoice'] = 'б/н';

			$credit[] = [
				"invoice" => $d['invoice'],
				"do"      => $do
			];

		}

		if ( $da['payer'] < 1 and $da['clid'] > 0 )
			$da['payer'] = $da['clid'];

		$list[] = [
			"did"    => $da['did'],
			"client" => current_client( $da['payer'] ),
			"title"  => $da['title'],
			"edizm"  => $da['edizm'],
			"kol"    => $da['kol'],
			"price"  => $da['price'],
			"sum"    => num_format( $list[ $i ]['kol'] * $list[ $i ]['price'] ),
			"user"   => $da['user']
		];

	}

}

// Функция экпорта отчета в Excel
if ( $action == "exportTransactions" ) {

	//Получение данных и формирование запроса
	$dids = yimplode( ",", yexplode( ",", str_replace( "\n", ",", $_REQUEST['deals'] ) ) );
	$sort = ($dids != '') ? " and ".$sqlname."speca.did IN ($dids)" : $sort;

	$q  = "
	SELECT
		".$sqlname."speca.title as title,
		".$sqlname."speca.price as price,
		".$sqlname."speca.edizm as edizm,
		".$sqlname."speca.kol as kol,
		".$sqlname."speca.did as did,
		".$sqlname."dogovor.payer as payer,
		".$sqlname."user.title as user
	FROM ".$sqlname."speca
		LEFT JOIN ".$sqlname."dogovor ON ".$sqlname."speca.did = ".$sqlname."dogovor.did
		LEFT JOIN ".$sqlname."price ON ".$sqlname."speca.prid = ".$sqlname."price.n_id
		LEFT JOIN ".$sqlname."user ON ".$sqlname."dogovor.iduser = ".$sqlname."user.iduser
	WHERE
		".$sqlname."speca.spid > 0
		$sort
		and identity = '$identity'
	ORDER BY ".$sqlname."speca.did
	";
	$da = $db -> getAll( $q );

	foreach ( $da as $d ) {


		$list[] = [
			"id"     => $d['did'],
			"client" => current_client( $d['payer'] ),
			"title"  => $d['title'],
			"edizm"  => $d['edizm'],
			"kol"    => $d['kol'],
			"price"  => $d['price'],
			"sum"    => num_format( $d['kol'] * $d['price'] ),
			"user"   => $d['user']
		];

	}

	$data = ["list" => $list];

	//Экспорт в Excel
	//include_once '../opensource/tbs_us/tbs_class.php';
	//include_once '../opensource/tbs_us/plugins/tbs_plugin_opentbs.php';
	
	require_once $rootpath."/vendor/tinybutstrong/opentbs/tbs_plugin_opentbs.php";

	$templateFile = 'templates/paidTransactionsTemp.xlsx';
	$outputFile   = 'exportPaidTransactions.xlsx';

	$TBS = new clsTinyButStrong(); // new instance of TBS
	$TBS -> PlugIn( TBS_INSTALL, OPENTBS_PLUGIN ); // load the OpenTBS plugin

	$TBS -> SetOption( 'noerr', true );
	$TBS -> LoadTemplate( $templateFile, OPENTBS_ALREADY_UTF8 );

	$TBS -> MergeBlock( 'list', $data['list'] );
	$TBS -> Show( OPENTBS_DOWNLOAD, $outputFile );

	exit();

}

?>

<span id="greenbutton" class="noprint mt20">
	<a href="javascript:void(0)" onclick="doSettingsR()" class="button"><i class="icon-cog-alt"></i>&nbsp;&nbsp;Указать сделки</a>&nbsp;
</span>
<a href="javascript:void(0)" onclick="Export()" title="Выгрузить в Excel оплаченные счета" class="button">Экпорт в Excel</a>
<div class="zagolovok_rep" align="center">
	<b>Оплаченные счета/сделки:</b>
</div>

<!--Получает введенные данные для экспорта-->
<input type="hidden" id="deals" name="deals" value="<?= $_REQUEST['deals'] ?>">

<hr>

<!--Формирование страницы -->
<div id="data">
	<table width="99.5%" border="0" align="center" cellpadding="5" cellspacing="0">
		<thead>
		<tr>
			<th class="w20">№ сделки</th>
			<th class="yw120">Заказчик</th>
			<th>Наименование</th>
			<th class="yw40">Ед.изм</th>
			<th class="yw40">Кол-во</th>
			<th class="yw100">Цена за ед., руб.</th>
			<th class="yw100">Цена итого, руб.</th>
			<th class="yw80">Сотрудник</th>

		</tr>
		</thead>
		<tbody>
		<?php
		$j = 1;
		for ( $i = 0; $i < count( $list ); $i++ ) {

			$url   = '';
			$class = '';

			if ( $list[ $i ]['prid'] > 0 ) {
				$url   = 'onclick = "editPrice(\''.$list[ $i ]['prid'].'\',\'view\')"';
				$class = 'hand';
			}
			?>
			<tr class="ha">
				<td><?= $list[ $i ]['did'] ?></td>
				<td>
					<div class="ellipsis">
						<a href="javascript:void(0)" onclick="viewClient('<?= $list[ $i ]['clid'] ?>')"><i class="icon-building broun"></i>&nbsp;<?= $list[ $i ]['client'] ?>
						</a></div>
				</td>
				<td>
					<div class="ellipsis <?= $class ?> Bold" <?= $url ?> title="<?= $list[ $i ]['title'] ?>"><?= $list[ $i ]['title'] ?></div>
					<br>
					<?php if ( $list[ $i ]['prid'] > 0 ) { ?>
						<div class="ellipsis smalltxt blue">Из прайса</div><?php } ?>
					<?php if ( $list[ $i ]['prid'] < 1 ) { ?>
						<div class="ellipsis smalltxt gray">Не прайсовая позиция</div><?php } ?>
				</td>
				<td align="right"><?= $list[ $i ]['edizm'] ?></td>
				<td align="right"><?= $list[ $i ]['kol'] ?></td>
				<td align="right"><?= num_format( $list[ $i ]['price'] ) ?></td>
				<td align="right"><b><?= num_format( $list[ $i ]['kol'] * $list[ $i ]['price'] ) ?></b></td>
				<td align="right"><?= $list[ $i ]['user'] ?></td>
			</tr>
			<?php
			$j++;
		}
		?>
		</tbody>
	</table>
</div>

<hr>
<div class="pad10 infodiv">
	Выборка происходит по id сделки. Вы можете выбрать необходимые сделки, нажав кнопку "Указать сделки".
</div>

<div class="h40"></div>
<div class="h40"></div>
<script>

	var urll = "reports/<?= $thisfile ?>";

	function doSettingsR() {

		var str = '?action=edit';
		doLoad(urll + str);

	}

	function Export() {
		var str = $('#selectreport').serialize();
		window.open('reports/' + $('#report option:selected').val() + '?action=exportTransactions&' + str);
	}

</script>





