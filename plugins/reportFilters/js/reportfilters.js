$(document).ready(function () {

	// блок с перечнем сотрудников
	var $relm = $('#filteru');

	// мы в отчетах и список отделов не пуст
	if($display === 'reports' && $otdelSelect !== ''){

		// добавляем блок с селектом
		$relm.prepend('<div class="infodiv wp100"><div class="fs-07 gray">Сотрудники по отделам</div><span class="select">'+ $otdelSelect +'</span></div>').addClass('viewdiv');

		// начальный триггер
		$('#otdelSelect').trigger('change');

		// действия при выборе конкретного отдела
		$('#otdelSelect').on('change', function(){

			var $users = $('option:selected', this).data('users');

			// если выбран отдел
			if($users !== '') {

				//console.log($users);

				var $uArray = $('option:selected', this).data('users').split(",");

				//console.log($uArray);

				$('#param_user1').find('label').addClass('gray');

				$('#user_list\\[\\]').each(function () {

					var val = $(this).val();

					// выделяем сотрудников отдела
					if (in_array(val, $uArray)) {

						$(this).prop("checked", true);
						$(this).closest('label').removeClass('gray');

					}
					// у остальных снимаем выбор
					else {

						$(this).prop("checked", false);
						$(this).closest('label').addClass('gray');

					}

				});

			}
			// если нужно выделить всех сотрудников
			else{

				$('#user_list\\[\\]').prop('checked', true);
				$('#param_user1').find('label').removeClass('gray');

			}

		});

	}

});