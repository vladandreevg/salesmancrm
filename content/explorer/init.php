<?php
/* ============================ */
/*         SalesMan CRM         */
/* ============================ */
/* (C) 2025 Vladislav Andreev   */
/*       SalesMan Project       */
/*         salesman.pro         */
/*         ver. 2025.1          */
/* ============================ */

/**
 * Формирование модального окна
 */

use Salesman\Upload;

?>
<script type="text/javascript" src="/assets/js/app.explorer.js"></script>
<div class="explorer">

	<FORM action="/content/explorer/index.php" method="post" enctype="multipart/form-data" name="explorerForm" id="explorerForm" onsubmit="return false;">
		<INPUT type="hidden" name="action" id="action" value="list">
		<INPUT type="hidden" name="page" id="page" value="1">

		<div class="explorer--block">

			<div class="explorer--header">
				Выбор загруженных файлов
				<div class="explorer--close" title="Закрыть" onclick="$explorer.close();">
					<i class="icon-cancel-circled"></i>
				</div>
			</div>

			<div class="explorer--form">

				<div class="explorer--folders">

					<div class="fs-09 gray-dark">Каталог</div>
					<select name="folder" id="folder" class="wp100" onchange="$explorer.search()">
						<OPTION value="">--Не выбрано--</OPTION>
						<?php
						$catalog = Upload ::getCatalogLine(0);
						foreach ($catalog as $key => $value) {

							if ($value['level'] <= 3) {

								$xshared = ( $value['shared'] == 'yes' ) ? ' - Общая' : "";

								print '<option value="'.$value['id'].'">'.( $value['level'] > 0 ? str_repeat('&nbsp;&nbsp;', $value['level']).'&rarr;&nbsp;' : '' ).$value['title'].' '.$xshared.'</option>';
							}

						}

						?>
					</select>
				</div>
				<div class="explorer--search">

					<div class="fs-09 gray-dark">Поиск по названию и описанию</div>
					<input type="text" name="seach" id="explorerseach" class="wp100" placeholder="Поиск по названию и описанию..."">

				</div>

			</div>

			<div class="explorer--files">

				<div class="divider">Файлы</div>

				<div class="explorer--filebox"></div>
				<div class="explorer--pages"></div>

			</div>

		</div>

		<div class="explorer--pages">
			<div class="explorer--page">1</div>
			<div class="explorer--page">1</div>
			<div class="explorer--page">1</div>
		</div>

	</FORM>

</div>
