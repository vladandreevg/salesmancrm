<?php
/* ============================ */
/*         SalesMan CRM         */
/* ============================ */
/* (C) 2016 Vladislav Andreev   */
/*       SalesMan Project       */
/*        www.isaler.ru         */
/*        ver. 2017.x           */
/* ============================ */

use Salesman\Akt;
use Salesman\Speka;

error_reporting(E_ERROR);
header("Pragma: no-cache");

$rootpath = dirname( __DIR__, 2 );

include $rootpath."/inc/config.php";
include $rootpath."/inc/dbconnector.php";
include $rootpath."/inc/auth.php";
include $rootpath."/inc/func.php";
include $rootpath."/inc/settings.php";
include $rootpath."/inc/language/".$language.".php";

$thisfile = basename( __FILE__ );

$action = untag($_REQUEST['action']);
$did    = (int)$_REQUEST['did'];

global $isCatalog;

$speca = (new Speka()) ->card($did);

if (!$speca['calculate']) {

	print '
	<div class="p10 m5">
		Расчет по спецификациям не включен в параметрах сделки. 
		'.($close != 'yes' ? '<a href="javascript:void(0)" onClick="cf=confirm(\'Вы действительно хотите включить расчет по спецификации?\');if (cf)editSpeca(\'\',\'change.calculate\',\''.$did.'\');" title="Включить" class="button mt10">Включить?</a>' : '').'
	</div>
	';

}
else {

	$html = file_get_contents( $rootpath.'/content/tpl/card.speka.mustache' );

	Mustache_Autoloader ::register();
	$m = new Mustache_Engine();

	print $html = $m -> render( $html, $speca );

}

$speca = (new Speka()) ->card($did, 'material');

if ($isCatalog == 'on' && $speca['calculate'] && count($speca['speca']) > 0) {
	?>

	<div class="divider mt20 mb10">Материалы</div>

	<?php
	$html = file_get_contents( $rootpath.'/content/tpl/card.speka.mustache' );

	Mustache_Autoloader ::register();
	$m = new Mustache_Engine();

	print $html = $m -> render( $html, $speca );
	?>

	<div class="infodiv mt5 fs-09 em">&nbsp; *Материалы не учитываются в сумме сделки, счетах и актах. Себестоимость материалов вычитается из Прибыли</div>

	<?php
}
?>
<script>

	if (isMobile) {

		$('.spekaTable').rtResponsiveTables();

	}

	/* ------------------------------------------------------------------
	 * Ручная сортировка позиций спецификации
	 * ------------------------------------------------------------------ */

	//стили sortable - инжектим один раз, чтобы не плодить дубли при перезагрузке вкладки
	if (!$('#spekaSortStyle').length) {

		$('<style id="spekaSortStyle">' +
			'.speka-dragcell{cursor:move}' +
			'.speka-drag{cursor:move;color:#9aa7b4}' +
			'.speka-drag:hover{color:#2b6cb0}' +
			'.speka-placeholder{height:38px;background:#f2f6fa;border:1px dashed #b9c9d8}' +
			'tr.speka-dragging{opacity:.6}' +
			'</style>').appendTo('head');

	}

	//полный порядок позиций: основная спецификация + материалы
	function spekaOrder() {

		var ids = [];

		$('.spekaTable').each(function () {

			$(this).find('tbody > tr[data-spid]').each(function () {
				ids.push($(this).data('spid'));
			});

		});

		return ids.join(',');

	}

	//пересчет нумерации строк без перезагрузки вкладки
	function spekaRenumber() {

		$('.spekaTable').each(function () {

			var i = 1;

			$(this).find('tbody > tr[data-spid]').each(function () {
				$(this).find('td.speka-num span').text(i++);
			});

		});

	}

	//сохранение порядка на сервере
	function spekaSave($table) {

		var did = $table.data('did');

		if (!did)
			return;

		$.post('/content/core/core.speca.php?action=sort&did=' + did, {spids: spekaOrder()}, function (data) {

			if (data.error !== undefined && data.error !== '' && data.error !== null) {

				Swal.fire('Ошибка', data.error, 'error');

				if (typeof settab === 'function')
					settab('7', false);

				return;

			}

			spekaRenumber();

		}, 'json')
			.fail(function () {

				//не сохранилось - вернем прежний порядок перерисовкой вкладки
				if (typeof settab === 'function')
					settab('7', false);

			});

	}

	//перетаскивание строк (десктоп)
	if (!isMobile) {

		$('.spekaSortable').each(function () {

			if ($(this).hasClass('ui-sortable'))
				return;

			$(this).sortable({
				handle: '.speka-drag',
				items: '> tr',
				axis: 'y',
				cursor: 'move',
				helper: 'clone',
				opacity: 0.8,
				tolerance: 'pointer',
				forcePlaceholderSize: true,
				placeholder: 'speka-placeholder',
				start: function (event, ui) {
					ui.item.addClass('speka-dragging');
				},
				stop: function (event, ui) {
					ui.item.removeClass('speka-dragging');
				},
				update: function () {
					spekaSave($(this).closest('table'));
				}
			}).disableSelection();

		});

	}

	//кнопки "выше"/"ниже" - работают и на мобильных
	$(document)
		.off('click.spekaMove', '.speka-move')
		.on('click.spekaMove', '.speka-move', function () {

			var $tr = $(this).closest('tr');

			if ($(this).data('dir') === 'up')
				$tr.prev('tr').before($tr);
			else
				$tr.next('tr').after($tr);

			spekaRenumber();
			spekaSave($tr.closest('table'));

			return false;

		});

</script>
