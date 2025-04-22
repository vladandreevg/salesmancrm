/* ============================ */
/*         SalesMan CRM         */
/* ============================ */
/* (C) 2025 Vladislav Andreev   */
/*       SalesMan Project       */
/*         salesman.pro         */
/*         ver. 2025.1          */
/* ============================ */

/**
 * @description Диалоговое окно для выбора загруженных файлов
 * @author Vladislav Andreev
 * @version 2025.1
 */

let options = {}
let ExplorerEvents = new CustomEvent("ExplorerEvents");

$explorer = {
	// Инициализация
	init: function(opt) {
		
		$.Mustache.load('/content/explorer/tpl.explorer.mustache');
	
		$('.explorer').addClass('open')
		options = opt || {}
		
		$explorer.list(options)
	
	},
	close: function() {
		
		$('.explorer').removeClass('open')
		
	},
	// загрузка файлов
	list: function() {
		
		var str = $('#explorerForm').serialize();
		var url = '/content/explorer/index.php';
		var all;
		
		$('.explorer--filebox').append('<div class="contentloader"><img src="/assets/images/Services.svg" width="50px" height="50px"></div>');
		
		$.getJSON(url, str, function(viewData) {
			
			//viewData.options = opt
			//console.log(viewData)
			
			$('.explorer--filebox').empty().mustache('uploadTpl', viewData);
			
			var page = viewData.page;
			var pageall = viewData.pageall;
			
			all = viewData.all;
			
			var pg = 'Стр. '+page+' из '+pageall;
			
			if(pageall > 1){
				
				var prev = page - 1;
				var next = page + 1;
				
				if(page === 1)
					pg = pg + '&nbsp;<a href="javascript:void(0)" onclick="$explorer.changepage(\''+next+'\')" title="Следующая"><i class="icon-angle-right"></i></a>&nbsp;&nbsp;<a href="javascript:void(0)" onclick="$explorer.changepage(\''+pageall+'\')" title="Последняя"><i class="icon-angle-double-right"></i></a>&nbsp;';
				
				else if(page === pageall)
					pg = pg + '&nbsp;<a href="javascript:void(0)" onclick="$explorer.changepage(\'1\')" title="Начало"><i class="icon-angle-double-left"></i></a>&nbsp;&nbsp;<a href="javascript:void(0)" onclick="$explorer.changepage(\''+prev+'\')" title="Предыдущая"><i class="icon-angle-left"></i></a>&nbsp;';
				
				else
					pg = '&nbsp;<a href="javascript:void(0)" onclick="$explorer.changepage(\'1\')" title="Начало"><i class="icon-angle-double-left"></i></a>&nbsp;&nbsp;<a href="javascript:void(0)" onclick="$explorer.changepage(\''+prev+'\')" title="Предыдущая"><i class="icon-angle-left"></i></a>&nbsp;'+ pg+ '&nbsp;<a href="javascript:void(0)" onclick="$explorer.changepage(\''+next+'\')" title="Следующая"><i class="icon-angle-right"></i></a>&nbsp;&nbsp;<a href="javascript:void(0)" onclick="$explorer.changepage(\''+pageall+'\')" title="Последняя"><i class="icon-angle-double-right"></i></a>&nbsp;';
				
			}
			
			$('.explorer--pages').html(pg);
			
		})
			.done(function() {
				
				ExplorerEvents.fire({
					etype: 'explorer',
					action: 'loaded'
				})
				
			});
		
	},
	changepage: function(page) {
		
		$('#explorerForm').find('input[name="page"]').val(page)
		$explorer.list()
	
	},
	search: function() {
		
		$('#explorerForm').find('input[name="page"]').val(0)
		$explorer.list()
		
	}
}

$(document).off('keyup', '#explorerseach');
$(document).on('keyup', '#explorerseach', _.debounce(function () {
	
	$explorer.search()

}, 500))

function xtest(){
	
	console.log('test')
	
}