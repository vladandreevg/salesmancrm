<?php
/* ============================ */
/*         SalesMan CRM         */
/* ============================ */
/* (C) 2016 Vladislav Andreev   */
/*       SalesMan Project       */
/*        www.isaler.ru         */
/*        ver. 2017.x           */
/* ============================ */

/**
 * Подключается перед закрывающим тегом </body>
 */

global $otherSettings, $ymEnable, $sip_active, $sip_tip, $sipHasCDR;

$about = [];
$sipcdr = false;
$siptype = '';

print '<input type="hidden" name="siptip" id="siptip" value="'.$sip_tip.'">';

$file = $rootpath."/content/pbx/{$sip_tip}/about.json";
if (file_exists($file)) {

	$about = json_decode( file_get_contents($file), true );

}

if ($sip_active == 'yes' && in_array("cdr", $about['functions']) ) {

	$sipcdr = true;
	$siptype = $sip_tip;

	print '
	<div id="peerpanel" class="refresh--icon caller--icon hidden" onclick="new CallWindowShow(\'hand\')" title="Состояние линий">
		<i class="icon-phone"></i>
	</div>
	<script src="/content/pbx/'.$sip_tip.'/worker.js?v=2024.1"></script>
	';

}

$sipcdr  = $sipcdr ? 1 : 0;
?>
<script>
	var $sipcdr = parseInt('<?=$sipcdr?>');

	$(function () {

		$('.refresh--panel').prepend( $('.pagerefresh') );

		if($sipcdr === 1){
			$('.refresh--panel').append( $('#peerpanel').removeClass('hidden') );
		}

		<?php
		if($ymEnable){

		//берем интервал проверки почты из настроек
		$yperiod = ($ym_param['ymailAutoCheckTimer'] > 0) ? (int)$ym_param['ymailAutoCheckTimer'] * 60000 : 10 * 60000;
		?>

		$yperiod = parseInt(<?=$yperiod?>);

		$mailer.check();
		$mailer.count();
		$mailer.get();

		Visibility.every(180000, 360000, $mailer.check);
		Visibility.every(120000, 240000, $mailer.count);
		Visibility.every($yperiod, $yperiod, $mailer.get);

		setInterval($mailer.get, $yperiod);

		<?php
		}
		if ($otherSettings['comment']) {

			print 'Visibility.every(150000, 300000, comments);';

		}
		?>

	});

</script>