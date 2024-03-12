<?php
/* ============================ */
/*         SalesMan CRM         */
/* ============================ */
/* (C) 2016 Vladislav Andreev   */
/*       SalesMan Project       */
/*        www.isaler.ru         */
/*        ver. 2017.x           */
/* ============================ */
error_reporting(E_ERROR);

$y = $_REQUEST['y'];
$m = $_REQUEST['m'];

$old = $_REQUEST['old'];

if (!$y) $y = date("Y", mktime(date('H'),date('i'),date('s'), date('m'), date('d'), date('Y')) + $tzone*3600);
if (!$m) $m = date("m", mktime(date('H'),date('i'),date('s'), date('m'), date('d'), date('Y')) + $tzone*3600);
?>
<div id="taskweek"></div>
<div style="height:5px"></div>
<script>
$( function() {

	var str = 'y=<?=$y?>&m=<?=$m?>&old=<?=$old?>';

	$.Mustache.load( '/content/tpl/lp.taskweek.mustache' );

	$('#taskweek').append('<img src="/assets/images/loading.svg">');

	$.getJSON('/content/desktop/taskweek.php', str, function(viewData) {

			$('#taskweek').empty().mustache('taskweekTpl', viewData).animate({scrollTop: 0}, 200);

		})
		.done(function() {

			$('#taskweek .tooltips').append("<span></span>");
			$('#taskweek .tooltips:not([tooltip-position])').attr('tooltip-position','bottom');
			$("#taskweek .tooltips").mouseenter(function(){
				$(this).find('span').empty().append($(this).attr('tooltip'));
			});

			if(!isMobile) $(".nano").nanoScroller();

		});

});
</script>