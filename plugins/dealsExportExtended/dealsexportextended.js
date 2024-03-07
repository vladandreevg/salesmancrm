$(function() {

	let $menu = '' +
		'<li class="hidden-iphone" data-type="deal">' +
		'  <a href="javascript:void(0)" class="navlink" onclick="dealsexportextended();">' +
		'    <span><i class="icon-upload-1"><i class="sup icon-forward-1"></i></i></span>' +
		'    <span class="">Экспорт сделок++</span>' +
		'  </a>' +
		'</li>';

	$('ul#menudeals').append($menu);

});

function dealsexportextended(){

	doLoad("/plugins/dealsExportExtended/");

}