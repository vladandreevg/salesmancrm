<?php
/* ============================ */
/*         SalesMan CRM         */
/* ============================ */
/* (C) 2018 Vladislav Andreev   */
/*       SalesMan Project       */
/*        www.isaler.ru         */
/*        ver. 2018.x           */
/* ============================ */

error_reporting(E_ERROR);
//ini_set( 'display_errors', 1 );

header("Pragma: no-cache");

$rootpath = dirname(__DIR__, 2);

include $rootpath."/inc/config.php";
include $rootpath."/inc/dbconnector.php";
include $rootpath."/inc/auth.php";
include $rootpath."/inc/func.php";
include $rootpath."/inc/settings.php";

$thisfile = basename(__FILE__);

$action = $_REQUEST['action'];

$db -> query("ALTER TABLE {$sqlname}callhistory CHANGE `src` `src` VARCHAR( 20 ) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL");
$db -> query("ALTER TABLE {$sqlname}callhistory CHANGE `dst` `dst` VARCHAR( 20 ) CHARACTER SET utf8 COLLATE utf8_general_ci NULL");

$options = $db -> getOne("SELECT params FROM {$sqlname}customsettings WHERE tip = 'sip' and identity = '$identity'");
$options = json_decode($options, true);

$sipProvider = [];
$folders = getDirList($rootpath."/content/pbx/");
foreach ($folders as $folder) {

	$file = $rootpath."/content/pbx/{$folder}/about.json";

	if (file_exists($file)) {

		$about = json_decode( file_get_contents($file), true );
		$sipProvider[$about['type']] = $about['name'];

	}

}

if ($action == 'save') {

	$sip_host     = $_REQUEST['sip_host'];
	$sip_port     = $_REQUEST['sip_port'];
	$sip_channel  = $_REQUEST['sip_channel'];
	$sip_context  = $_REQUEST['sip_context'];
	$sip_user     = rij_crypt($_REQUEST['sip_user'], $skey, $ivc);
	$sip_secret   = rij_crypt($_REQUEST['sip_secret'], $skey, $ivc);
	$sip_cdr      = $_REQUEST['sip_cdr'];
	$active       = $_REQUEST['active'];
	$tip          = $_REQUEST['tip'];
	$sip_numout   = $_REQUEST['sip_numout'];
	$sip_pfchange = $_REQUEST['sip_pfchange'];
	$sip_path     = $_REQUEST['sip_path'];
	$sipUser      = rij_crypt($_REQUEST['sipUser'], $skey, $ivc);
	$sipKey       = rij_crypt($_REQUEST['sipKey'], $skey, $ivc);
	$sip_secure   = $_REQUEST['sip_secure'];

	try {

		$db -> query("UPDATE {$sqlname}sip SET ?u WHERE identity = '$identity'", [
			'active' => $active,
			'tip'    => $tip
		]);
		print $mes = "Данные успешно сохранены";

	}
	catch (Exception $e) {

		print $mes = 'Ошибка'.$e -> getMessage().' в строке '.$e -> getCode();

	}

	//доп.опции телефонии
	$options = [
		"autoCreateLead" => $_REQUEST['autoCreateLead'],
		"domain"         => $_REQUEST['domain']
	];

	$id = (int)$db -> getOne("SELECT id FROM {$sqlname}customsettings WHERE tip = 'sip' and identity = '$identity'");

	if ($id > 0) {
		$db -> query("UPDATE {$sqlname}customsettings SET ?u WHERE tip = 'sip' and identity = '$identity'", [
			"datum"  => current_datumtime(),
			"params" => json_encode($options)
		]);
	}
	else {
		$db -> query("INSERT INTO {$sqlname}customsettings SET ?u", [
			"tip"      => "sip",
			"params"   => json_encode($options),
			"identity" => $identity
		]);
	}

	unlink($rootpath."/cash/".$fpath."settings.all.json");

	exit();

}
if ($action == 'getApiKey') {

	$keys = [];
	$res  = $db -> getAll("select api_key from {$sqlname}settings");

	foreach ($res as $da) {
		$keys[] = $da['api_key'];
	}

	function genkey() {

		$keys = $GLOBALS['keys'];

		$chars = "qazxswedcvfrtgbnhyujmkiolp1234567890QAZXSWEDCVFRTGBNHYUJMKIOLP";
		$max   = 30;
		$size  = StrLen($chars) - 1;
		$key   = NULL;

		while ($max--) $key .= $chars[rand(0, $size)];

		if (in_array($key, $keys)) {
			genkey();
		}

		return $key;

	}

	print $key = genkey();
	exit();
}

if (empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) != 'xmlhttprequest') {
	print '<div class="warning text-center"><br>Доступ запрещен.<br>Обратитесь к администратору.<br><br></div>';
	exit();
}

if ($action == '') {

	$result_set = $db -> getRow("select * from {$sqlname}sip WHERE identity = '$identity'");
	$active     = $result_set['active'];
	$tip        = $result_set['tip'];
	?>
	<FORM action="/content/admin/sip.editor.php" method="post" enctype="multipart/form-data" name="set" id="set">
		<!--<INPUT type="hidden" name="action" id="action" value="save">-->
		
		<h2 class="blue mt20 mb20 pl5">Включение интеграции и выбор провайдера</h2>
		
		<div class="flex-container mt20 box--child">
			
			<div class="flex-string wp20 right-text fs-12 black Bold">Активен:</div>
			<div class="flex-string wp80 pl10">
				
				<div class="inline paddright15 margleft5 mb10">
					
					<div class="radio">
						<label>
							<input name="active" type="radio" id="active" <?php
							if ($active == 'yes') print "checked" ?> value="yes">
							<span class="custom-radio success1"><i class="icon-radio-check"></i></span>
							<span class="title Bold">Да</span>
						</label>
					</div>
				
				</div>
				<div class="inline paddright15 margleft5 mb10">
					
					<div class="radio">
						<label>
							<input name="active" type="radio" id="active" <?php
							if ($active != 'yes') print "checked" ?> value="no">
							<span class="custom-radio alert"><i class="icon-radio-check"></i></span>
							<span class="title Bold">Нет</span>
						</label>
					</div>
				
				</div>
				<div class="fs-10 gray2 em">
					Если <b class="blue">выключено</b>, то система инициации звонка будет отключена
				</div>
			
			</div>
		
		</div>
		
		<hr>
		
		<h2 class="blue mt20 mb20 pl5">Доп.опции</h2>
		
		<div class="flex-container mt20 box--child">
			
			<div class="flex-string wp20 right-text fs-12 black Bold">Создавать заявки:</div>
			<div class="flex-string wp80 pl10">
				
				<div class="inline paddright15 margleft5 mb10">
					
					<div class="checkbox">
						<label>
							<input name="autoCreateLead" type="checkbox" id="autoCreateLead" <?php
							if ($options['autoCreateLead'] == 'yes') print "checked" ?> value="yes">
							<span class="custom-checkbox success1"><i class="icon-ok"></i></span>
							<span class="title Bold">Вкл.</span>
						</label>
					</div>
				
				</div>
				<div class="fs-10 gray2 em">
					Если
					<b class="blue">включено</b>, то система будет автоматически создавать Заявку (если активен модуль "Сборщик заявок"), если номер телефона отсутствует в базе
					
					<div class="warning black w0 m0 mt10">
						<b class="red">ВНИМАНИЕ!</b> Эта опция требует времени на выполнение, поэтому возможна задержка всплытия окна телефонии
					</div>
				</div>
			
			</div>
		
		</div>
		
		<hr>
		
		<h2 class="blue mt20 mb20 pl5">Провайдер телефонии</h2>
		
		<div class="flex-container mt20 box--child">

			<?php
			foreach ($sipProvider as $path => $name) {

				$s = ( $tip == $path ) ? "checked" : "";

				?>
				<div class="flex-string pr15 ml5 mb10 viewdiv w200">

					<div class="radio">
						<label>
							<input name="tip" type="radio" id="tip" <?= $s ?> value="<?= $path ?>" class="tip">
							<span class="custom-radio success1"><i class="icon-radio-check"></i></span>
							<span class="title Bold"><?= $name ?></span>
						</label>
					</div>

				</div>
				<?php
			}
			?>
		
		</div>
		
		<div id="userdata" class="mb20"></div>
		
		<div class="button--group1 box--child" style="position: fixed; bottom: 40px; left: 380px; z-index: 100;">
			<a href="javascript:void(0)" class="button" onclick="sipSave()">Сохранить</a>
		</DIV>
		
		<div class="space-100"></div>
	
	</FORM>
	<script src="/assets/js/clipboard.js/clipboard.min.js"></script>
	<script>
		
		var clipboard
		
		$(function () {
			switchSIP();
		});
		
		$('input#active').on('change', function () {
			switchSIP();
		});
		
		$('input.tip').on('change', function () {
			switchSIP();
		});
		
		$('#set').ajaxForm({
			beforeSubmit: function () {
				var $out = $('#message');
				$out.empty();
				$out.css('display', 'block').append('<div id="loader"><img src="/assets/images/loader.gif"> Загрузка данных. Пожалуйста подождите...</div>');
				return true;
			},
			success: function (data) {

				razdel(hash);
				
				$('#message').fadeTo(1, 1).css('display', 'block').html(data);
				setTimeout(function () {
					$('#message').fadeTo(1000, 0);
				}, 20000);
				
			}
		});

		function sipSave(){

			var tip = $('#tip:checked').val();
			var url = "/content/pbx/" + tip + "/settings.php?action=save";
			var str = $('#set').serialize();

			$.post(url, str, function (data) {

				$('#message').fadeTo(1, 1).css('display', 'block').html(data);
				setTimeout(function () {
					$('#message').fadeTo(1000, 0);
				}, 20000);

			});

		}
		
		function switchSIP() {
			
			var tip = $('#tip:checked').val();
			var active = $('#active:checked').val();
			
			if (active !== 'yes') {
				
				$('#userdata').empty();
				
			}
			else if (tip !== 'undefined' && tip !== '' && active === 'yes' && active !== 'undefined') {
				
				$('.refresh--panel').find('.admn').remove();
				
				$('#userdata')
					.load('/content/pbx/' + tip + '/settings.php?action=').append('<div id="loader"><img src="/assets/images/loading.gif"> Загрузка данных. Пожалуйста подождите...</div>');
				
				setTimeout(function () {
					
					$('.refresh--panel').prepend($('.pagerefresh'));
					
				}, 500);
				
			}
			
		}
		
		function getKey() {
			
			var url = '/content/admin/<?php echo $thisfile; ?>?action=getApiKey';
			
			$.post(url, function (data) {
				$('#telefum_key').val(data);
			});
			
		}
		
		function checkConnection() {

			var tip = $('#tip:checked').val();
			var url = "/content/pbx/" + tip + "/settings.php?action=check";
			var str = $('#set').serialize();
			
			$('#sipress').removeClass('hidden').append('<img src="/assets/images/loading.gif">');
			
			$.post(url, str, function (data) {
				if (data) {
					$('#sipress').html(data);
				}
				return false;
			});
			
		}
	
	</script>
	<?php
} ?>