<?php
/* ============================ */
/*         SalesMan CRM         */
/* ============================ */
/* (C) 2016 Vladislav Andreev   */
/*       SalesMan Project       */
/*        www.isaler.ru         */
/*        ver. 2019.x           */
/* ============================ */

error_reporting( E_ERROR );
header( "Pragma: no-cache" );

$rootpath = dirname(__DIR__, 2);

include $rootpath."/inc/config.php";
include $rootpath."/inc/dbconnector.php";
include $rootpath."/inc/auth.php";
include $rootpath."/inc/func.php";
include $rootpath."/inc/settings.php";

$thisfile = basename( __FILE__ );

$action = $_REQUEST['action'];

/**
 * Настройки экспресс-формы по умолчанию
 */
$edef = [
	"client" => [
		"title"      => [
			"active"   => "yes",
			"requered" => "yes",
			"more"     => "no",
			"comment"  => "Должно быть всегда включено и видимо",
		],
		"phone"      => [
			"active"   => "yes",
			"requered" => "no",
			"more"     => "no",
		],
		"fax"        => [
			"active"   => "no",
			"requered" => "no",
			"more"     => "no",
		],
		"mail_url"   => [
			"active"   => "yes",
			"requered" => "no",
			"more"     => "no",
		],
		"clientpath" => [
			"active"   => "yes",
			"requered" => "no",
			"more"     => "no",
		],
		"tip_cmr"    => [
			"active"   => "yes",
			"requered" => "no",
			"more"     => "no",
		],
		"idcategory" => [
			"active"   => "yes",
			"requered" => "no",
			"more"     => "no",
		],
		"des"        => [
			"active"   => "yes",
			"requered" => "no",
			"more"     => "yes",
		],
		"territory"  => [
			"active"   => "yes",
			"requered" => "no",
			"more"     => "yes",
		],
		"address"    => [
			"active"   => "yes",
			"requered" => "no",
			"more"     => "yes",
		],
		"site_url"   => [
			"active"   => "yes",
			"requered" => "no",
			"more"     => "yes",
		],
		"head_clid"  => [
			"active"   => "yes",
			"requered" => "no",
			"more"     => "yes",
		]
	],
	"person" => [
		"person"  => [
			"active"   => "yes",
			"requered" => "no",
			"more"     => "no",
			"comment"  => "Должно быть всегда включено и видимо",
		],
		"ptitle"  => [
			"active"   => "yes",
			"requered" => "no",
			"more"     => "no",
		],
		"tel"     => [
			"active"   => "yes",
			"requered" => "no",
			"more"     => "no",
		],
		"mob"     => [
			"active"   => "yes",
			"requered" => "no",
			"more"     => "no",
		],
		"mail"    => [
			"active"   => "yes",
			"requered" => "no",
			"more"     => "no",
		],
		"loyalty" => [
			"active"   => "yes",
			"requered" => "no",
			"more"     => "no",
		],
		"rol"     => [
			"active"   => "no",
			"requered" => "no",
			"more"     => "no",
		]
	]
];

$xtype = [
	"client"     => "Клиент",
	"partner"    => "Партнер",
	"contractor" => "Поставщик",
	"concurent"  => "Конкурент",
];

$a['client'] = array_keys( $edef['client'] );
$a['person'] = array_keys( $edef['person'] );

//массив из имен всех полей, доступных для формы
$fdef    = $edefault = [];
$fclient = array_map( static function($element) {
	return "'".$element."'";
}, $a['client'] );
$fperson = array_map( static function($element) {
	return "'".$element."'";
}, $a['person'] );

$fdef = array_merge( $fclient, $fperson );

//print_r($edef);
//exit();

//доп.поля формы
$re = $db -> query( "
	SELECT * 
	FROM {$sqlname}field 
	WHERE 
	    fld_tip IN ('client', 'person') and 
	    fld_on='yes' and 
	    (fld_name LIKE '%input%' or fld_name IN (".implode( ",", $fdef ).")) and 
	    identity = '$identity' 
	ORDER BY fld_order
" );
while ($da = $db -> fetch( $re )) {

	$active = $req = 'no';
	$more   = 'yes';

	if ( in_array( $da['fld_name'], $a[ $da['fld_tip'] ] ) ) {

		$active = $edef[ $da['fld_tip'] ][ $da['fld_name'] ]['active'];
		$req    = $edef[ $da['fld_tip'] ][ $da['fld_name'] ]['requered'];
		$more   = $edef[ $da['fld_tip'] ][ $da['fld_name'] ]['more'];

	}

	if ( $da['fld_tip'] == 'person' && in_array( $da['fld_name'], [
			"fax",
			"clientpath"
		] ) ){
			continue;
		}

	$edefault[ $da['fld_tip'] ][ $da['fld_name'] ] = [
		"active"   => $active,
		"requered" => $req,
		"more"     => $more,
		"sub"      =>  $da['fld_sub']
	];

}

//print_r($edefault);
//exit();

//массив из имен всех полей, доступных для формы
$edefaultlist['client'] = array_keys( $edefault['client'] );
$edefaultlist['person'] = array_keys( $edefault['person'] );


if ( $action == "save" ) {

	unset( $_REQUEST['action'] );

	$data   = $_REQUEST;
	$params = [];

	foreach ( $data as $tip => $field ) {

		foreach ( $field as $input => $v ) {

			//$v = $data[$tip][ $input ];

			$v['req']    = ($v['req'] != '') ? $v['req'] : "no";
			$v['active'] = ($v['active'] != '') ? $v['active'] : "no";
			$v['more']   = ($v['more'] != '') ? $v['more'] : "no";

			if ( in_array( $input, [
				'title',
				'person'
			] ) ) {

				$v['active'] = 'yes';
				$v['more']   = 'no';

			}

			$params[ $tip ][ $input ] = [
				"active"   => $v['active'],
				"requered" => $v['req'],
				"more"     => $v['more'],
			];

		}

	}

	//print_r($params);
	//exit();

	$id = (int)$db -> getOne( "select id from {$sqlname}customsettings where tip='eform' and identity = '$identity'" );

	if ( $id > 0 ) {
		$db -> query("UPDATE {$sqlname}customsettings SET ?u WHERE tip = 'eform' and identity = '$identity'", [
			"datum"  => current_datumtime(),
			"params" => json_encode($params)
		]);
	}
	else {
		$db -> query("INSERT INTO {$sqlname}customsettings SET ?u", [
			"tip"      => "eform",
			"params"   => json_encode($params),
			"identity" => $identity
		]);
	}

	print "Запись обновлена";

	exit();

}

if ( $action == '' ) {

	//Значение полей формы клиента (общая форма)
	$fieds = $db -> getAll( "SELECT * FROM {$sqlname}field where fld_tip='client' and fld_on='yes' and identity = '$identity' order by fld_order" );

	//Настройки экспресс-формы
	$efields = json_decode( $db -> getOne( "select params from {$sqlname}customsettings where tip='eform' and identity = '$identity'" ), true );

	//print_r($efields);

	//массив полей, задействованных в форме
	$fieldlist['client'] = array_keys( $efields['client'] );
	$fieldlist['person'] = array_keys( $efields['person'] );

	//если параметры пусты, то задаем дефолтные
	if ( count( $efields ) == 0 ) {
		$efields   = $edefault;
		$fieldlist = $edefaultlist;
	}

	//массив активированных полей, но отсутствующих в выборке
	$diffNoActive['client'] = array_diff( $edefaultlist['client'], $fieldlist['client'] );
	$diffNoActive['person'] = array_diff( $edefaultlist['person'], $fieldlist['person'] );

	/**
	 * Добавим отсутствующие поля в выборку
	 */
	foreach ( $diffNoActive as $tip => $fields ) {

		foreach ( $fields as $input => $val ) {

			$efields[ $tip ][ $val ] = [
				"active"   => "no",
				"requered" => "no",
				"more"     => "yes",
				"sub"      => $edefault[ $tip ][ $val ]['sub']
			];

		}

	}

	//print_r($edefaultlist);
	//print_r($efields);
	//print_r($diffNoActive);

	//массив удаленных полей, но присутствующих в выборке в выборке
	$diffNoExists['client'] = array_diff( $fieldlist['client'], $edefaultlist['client'] );
	$diffNoExists['person'] = array_diff( $fieldlist['person'], $edefaultlist['person'] );

	//print_r($diffNoExists);
	?>

	<h2>&nbsp;Раздел: "Настройка экспресс-формы"</h2>

	<DIV class="mt15">

		<FORM action="content/admin/<?php echo $thisfile; ?>" method="post" enctype="multipart/form-data" name="setForm" id="setForm">
			<INPUT type="hidden" name="action" id="action" value="save">

			<div class="infodiv">

				<h3 class="red m5">Внимание:</h3>
				<ul class="simple list">
					<li class="m0 p0"><b>Клиент:</b> поле "title" всегда должно быть активно</li>
					<li class="m0 p0"><b>Контакт:</b> поле "person" всегда должно быть активно</li>
					<li class="m0 p0">
						<b>Контакт:</b> не рекомендуется делать обязательных полей, т.к. в этом случае обязательные поля всегда нужно будет заполнять
					</li>
				</ul>

				<div class="warning"><b class="red">Важно!</b> Поддеживается тип карточки только "Клиент"</div>

			</div>

			<?php
			foreach ( $efields as $tip => $field ) {

				print '<div class="fs-14 Bold mb15 mt20 pl5 blue">Настройка блока '.strtr( $tip, [
						"client" => "Данные клиента",
						"person" => "Данные контакта"
					] ).':</div>';

				print '
				<table id="table-'.$tip.'" class="rowtable">
				<thead class="hidden-iphone sticked--top">
				<tr class="th40">
					<TH class="w30"></TH>
					<TH class="w130 nodrop">Системное имя</TH>
					<TH class="w350 nodrop">Название поля</TH>
					<TH class="w100 nodrop">Обязательное</TH>
					<TH class="w100 nodrop flh-10">В секции "ещё поля"</TH>
					<TH class="nodrop"></TH>
				</tr>
				</thead>
				';

				foreach ( $field as $input => $value ) {

					$s = ($value['active'] == "yes") ? "checked" : "";
					$r = ($value['requered'] == "yes") ? "checked" : "";
					$m = ($value['more'] == "yes") ? "checked" : "";

					$u = $t = $d = $c = '';

					if ( !in_array( $input, $edefaultlist[ $tip ] ) ) {

						$u = "imdoit";
						$t = '<i class="icon-minus-circled red list" title="Поле отключено в настройках форм или не поддерживается"></i>';
						$d = 'disabled';
						$c = 'gray';

					}

					$value['comment'] = (in_array( $input, [
						'title',
						'person'
					] )) ? 'Должно быть всегда включено и видимо' : '';

					if( !empty($value['sub']) ){
						$value['comment'] = $xtype[$value['sub']];
					}

					?>
					<tr class="ha <?= $c ?> th40" id="<?= $input ?>">
						<td class="w30 text-center">
							<input type="hidden" name="<?= $tip ?>[<?= $input ?>][name]" id="<?= $tip ?>[<?= $input ?>][name]" value="yes">
							<input type="checkbox" name="<?= $tip ?>[<?= $input ?>][active]" id="<?= $tip ?>[<?= $input ?>][active]" value="yes" <?= $s ?> <?= $d ?>>
						</td>
						<td class="w130">
							<span class="fs-12 Bold gray2 clearevents <?= $u ?>"><label for="<?= $tip ?>[<?= $input ?>][active]"><?= $input ?><?= $t ?></label></span>
						</td>
						<td class="w350">
							<div class="fs-12 Bold clearevents"><?= strtr( $input, $fieldsNames[ $tip ] ) ?></div>
						</td>
						<td class="w100 text-center">
							<input type="checkbox" name="<?= $tip ?>[<?= $input ?>][req]" id="<?= $tip ?>[<?= $input ?>][req]" value="yes" <?= $r ?> <?= $d ?>>
						</td>
						<td class="w100 text-center">
							<div class="<?= $g ?>">
								<input type="checkbox" name="<?= $tip ?>[<?= $input ?>][more]" id="<?= $tip ?>[<?= $input ?>][more]" value="yes" <?= $m ?> <?= $d ?>>
							</div>
						</td>
						<td class="red flh-10"><?= $value['comment'] ?></td>
					</tr>
					<?php
				}

				print '</table>';

				print '<div style="height: 30px"></div>';
			}
			?>

		</FORM>

	</DIV>

	<div class="pagerefresh refresh--icon admn red" onclick="$('#setForm').trigger('submit')" title="Сохранить"><i class="icon-ok"></i></div>
	<div class="pagerefresh refresh--icon admn orange" onclick="openlink('https://salesman.pro/docs/122')" title="Документация"><i class="icon-help"></i></div>

	<div class="space-100"></div>

	<script>

		$('#setForm').ajaxForm({
			beforeSubmit: function () {

				var $out = $('#message');
				var em = checkRequired();

				if (em === false) return false;

				$('#dialog').css('display', 'none');
				$('#dialog_container').css('display', 'none');

				$out.fadeTo(1, 1).css('display', 'block').append('<div id=loader><img src=/assets/images/loader.gif> Загрузка данных. Пожалуйста подождите...</div>');

				return true;

			},
			success: function (data) {


				razdel('efields');

				$('#message').fadeTo(1, 1).css('display', 'block').html(data);
				setTimeout(function () {
					$('#message').fadeTo(1000, 0);
				}, 20000);

			}
		});

		$("#table-client").disableSelection().tableDnD({
			onDragClass: "tableDrag"
		});
		$("#table-person").disableSelection().tableDnD({
			onDragClass: "tableDrag"
		});

	</script>
	<?php
}
?>