<?php
/* ============================ */
/*         SalesMan CRM         */
/* ============================ */
/* (C) 2018 Vladislav Andreev   */
/*       SalesMan Project       */
/*         salesman.pro         */
/*        ver. 2018.x           */
/* ============================ */

//todo: проблемы с Drag'n'Drop, обновлением после отправки формы
?>

<div id="weekCal" class="tableCal bgwhite" style="display: block !important; max-height: 100vh; overflow-y: auto; overflow-x: hidden;"></div>

<script>

	var $swel = $('#swindow');
	var $swelm = $swel.find('#weekCal');

	$swel.find('.body').css({"max-height":"calc(100vh - 70px)","height":"calc(100vh - 70px)"});

	$(document).ready(function () {

		ShowModal.subscribe(function (eventArgs) {

			//console.log(eventArgs);

			if(eventArgs.etype === 'swindow' && eventArgs.action === 'closed'){

				$swel.find('.body').empty().css({"max-height":"calc(100vh - 120px)","height":"calc(100vh - 120px)"});

			}

			if(eventArgs.etype === 'dialog' && eventArgs.action === 'closed'){

				//getWeekCalendar();

				/*
				$.get('/content/lists/sw.weekcalendar.php', function (data) {

					$swel.find('.body').html(data);

				});
				*/

			}

		});

		$.Mustache.load('/content/tpl/dt.weekcalendar.mustache');

		$.ajax({
			type: "POST",
			url: "/content/desktop/weekcalendar.php",
			dataType: 'json',
			success: function (viewData) {

				$swelm.empty().mustache('weekcalendarTpl', viewData);

				viewData.language = $language;

				//$elm.find('.contentloader').remove();

				var wcoffset = $swelm.find('#today').offset();
				var wctop = wcoffset.top - 120;

				$swelm.scrollTop(wctop);

				var html = $swel.find('.weeks').html();
				var sw = $swel.find('.weeks').width();
				var spos = $swelm.position();

				//$swel.find('.tableHeader[data-id="sweekcal"]').html(html).css({"width":sw+"px","top":spos.top+"px","left":spos.left+"px"});

				$swelm.find('.hour--event.wtodocal').each(function () {
					$(this).draggable({
						containment: '.weekcalendar',
						cursor: 'move',
						helper: 'clone',
						revert: false,
						zIndex: 100
					});
				});

				$swelm.find('.adtask').each(function () {
					$(this).droppable({
						tolerance: "pointer",
						over: function (event, ui) {//если фигура над клеткой- выделяем её границей
							$(this).addClass('greenbg-sub');
						},
						out: function (event, ui) {//если фигура ушла- снимаем границу
							$(this).removeClass('greenbg-sub');
						},
						drop: function (event, ui) {//если бросили фигуру в клетку

							$(this).removeClass('greenbg-sub');//убираем выделение

							var olddatum = $(ui.draggable).data('old');
							var oldhour = $(ui.draggable).closest('.hour--block').data('hours');
							var newdatum = $(this).data('datum');
							var newhour = $(this).data('hours');
							var tid = $(ui.draggable).data('tid');

							var date1 = new Date();
							var date2 = new Date(newdatum);
							var timeDiff = Math.ceil(date2.getTime() - date1.getTime());
							var diffDays = Math.ceil(timeDiff / (1000 * 3600));

							//console.log(date1);
							//console.log(date2);

							if (diffDays >= 0) {

								var url = '/content/core/core.tasks.php?tid=' + tid + '&action=izmdatum&olddatum=' + olddatum + '&newdatum=' + newdatum + '&oldhour=' + oldhour + '&newhour=' + newhour;

								//console.log(url);

								$('#message').empty().fadeTo(1, 1).append('<div id=loader><img src=/assets/images/loader.gif> Загрузка данных. Пожалуйста подождите...</div>');
								$.post(url, function (data) {

									$('#message').fadeTo(1, 1).css('display', 'block').html(data);
									setTimeout(function () {
										$('#message').fadeTo(1000, 0);
									}, 20000);

									$.get('/content/lists/sw.weekcalendar.php', function (data) {

										$swel.find('.body').html(data);

									});

								});

							}

						},
						accept: '.adtask'
					});

				});

				setTimeout(function () {

					//$('.tableHeader[data-id="weekcal"]').html(html).css({"width": $('.ui-layout-center').actual('width') + "px"});

				}, 150);

			}
		});

	});

	$(document).on('click', '.actions', function () {

		var datum = $(this).closest('.hour--block').data('datum');
		doLoad('/content/forms/form.task.php?action=add&date=' + datum);

	});

	$(window).resize(function () {

		if (this.resizeTO) clearTimeout(this.resizeTO);
		this.resizeTO = setTimeout(function () {
			$(this).trigger('resizeEnd');
		}, 500);

	});
	$(window).bind('resizeEnd', function () {

	});
</script>