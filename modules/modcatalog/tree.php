<?php

use Salesman\Storage;

$price = Storage::getCatalogHtml(0);
?>
<div id="folder" class="price-tree ifolder nano-content">
	<li data-id="500">
		<a href="javascript:void(0)" title="Все" class="category" data-id="0" data-alpine-devtools-right-click="">
			<i class="icon-folder blue"></i> [все]
		</a>
	</li>
	<?php
	print $price; ?>
</div>

<script>
	$(function () {
		
		//добавляем иконку раскрытия только категориям, у которых есть дочерний список
		$('.price-tree li').each(function () {
			
			var $li = $(this);
			var $childUl = $li.children('ul');
			var $icon = $('<i class="cat-toggle"></i>');
			
			if ($childUl.length) $icon.addClass('icon-angle-right');
			
			$li.children('a.category').prepend($icon);
			
		});
		
		//клик по иконке раскрывает/сворачивает дочерние категории
		$(document).on('click', '.price-tree .cat-toggle', function (e) {
			
			e.preventDefault();
			e.stopPropagation();
			
			var $icon = $(this);
			var $childUl = $icon.closest('li').children('ul');
			
			if (!$childUl.length) return;
			
			$childUl.toggleClass('open');
			$icon.toggleClass('icon-angle-right icon-angle-down');
			
		});
		
	});
</script>
