<?php
/* ============================ */
/*         SalesMan CRM         */
/* ============================ */
/* (C) 2016 Vladislav Andreev   */
/*       SalesMan Project       */
/*        www.isaler.ru         */
/*        ver. 2017.x           */
/* ============================ */
?>
<?php
error_reporting( E_ERROR );
ini_set( 'display_errors', 1 );
header( "Pragma: no-cache" );

$rootpath = realpath( __DIR__.'/../../' );

include $rootpath."/inc/config.php";
include $rootpath."/inc/dbconnector.php";
include $rootpath."/inc/auth.php";
include $rootpath."/inc/func.php";
include $rootpath."/inc/settings.php";
include $rootpath."/inc/language/".$language.".php";

$thisfile = basename( __FILE__ );

$clid   = (int)$_REQUEST['clid'];
$action = untag( $_REQUEST['action'] );

if ( $action == 'add' ) {

	$post_pole  = array_keys( $_POST );//массив имен передаваемых полей
	$post_param = array_values( $_POST );//массив передаваемых параметров

	$exclude = [
		'action',
		'clid'
	];

	//массив полей
	for ( $i = 0; $i < count( $post_pole ); $i++ ) {

		$value[ $i ] = implode( ";", (array)$post_param[ $i ] );

		//найдем id каталога профилей
		$id[ $i ] = $db -> getOne( "SELECT id FROM ".$sqlname."profile_cat where pole = '".$post_pole[ $i ]."' and identity = '$identity'" );

		//сформируем массив передаваемых значений имя профиля -> значение
		if ( !in_array( $post_name[ $i ], $exclude ) )
			$ar[] = [
				"id"    => $id[ $i ],
				"pole"  => $post_pole[ $i ],
				"value" => $value[ $i ]
			];

	}

	$result = $db -> getAll( "SELECT * FROM ".$sqlname."profile_cat WHERE identity = '$identity'" );
	foreach ( $result as $data ) {

		$key = '';

		//найдем id массива ar по ключу
		for ( $i = 0; $i < count( $ar ); $i++ ) {

			if ( $ar[ $i ]['pole'] == $data['pole'] )
				$key = $i;

		}

		//$key = array_search($data['pole'], $ar);

		//если значение заполнено
		if ( $ar[ $key ]['pole'] != '' ) {

			//Проверим существование текущего поля в базе
			$count = $db -> getOne( "SELECT COUNT(*) FROM ".$sqlname."profile WHERE clid = '".$clid."' and id = '".$data['id']."' and identity = '$identity'" ) + 0;

			if ( $count == 0 ) {

				$db -> query( "insert into ".$sqlname."profile (pfid,id,clid,value,identity) values(null, '".$data['id']."', '".$clid."', '".$ar[ $key ]['value']."','$identity')" );

			}
			else {

				$db -> query( "update ".$sqlname."profile set value = '".$ar[ $key ]['value']."' where clid = '".$clid."' and id = '".$data['id']."' and identity = '$identity'" );

			}

		}
		else $db -> query( "update ".$sqlname."profile set value = '' where clid = '".$clid."' and id = '".$data['id']."' and identity = '$identity'" );

	}

	print "Профиль обновлен";
	exit();

}
if ( $action == 'add_new' ) {

	$post_name  = array_keys( $_POST );//массив имен передаваемых полей
	$post_param = array_values( $_POST );//массив передаваемых параметров
	$exclude    = [
		'action',
		'clid'
	];

	for ( $i = 0; $i < count( $post_name ); $i++ ) {

		$value[ $i ] = implode( ";", $post_param[ $i ] );
		//сформируем массив передаваемых значений имя профиля -> значение
		if ( !in_array( $post_name[ $i ], $exclude ) )
			$ar[] = [
				"name"  => $post_name[ $i ],
				"value" => $value[ $i ]
			];

	}

	for ( $i = 0; $i < count( $ar ); $i++ ) {

		//Найдем id поля
		$id = $db -> getOne( "SELECT id FROM ".$sqlname."profile_cat where pole = '".$ar[ $i ]['name']."' and identity = '$identity'" ) + 0;

		if ( $id > 0 ) {

			//Проверим существование текущего поля в базе
			$kol[ $i ] = $db -> getOne( "SELECT COUNT(*) FROM ".$sqlname."profile WHERE clid = '".$clid."' and id = '".$id."' and identity = '$identity'" ) + 0;

			if ( $kol[ $i ] == 0 ) {

				$db -> query( "insert into ".$sqlname."profile (pfid,id,clid,value,identity) values(null, '".$id."', '".$clid."', '".$ar[ $i ]['value']."','$identity')" );

			}
			else {

				$db -> query( "update ".$sqlname."profile set value = '".$ar[ $i ]['value']."' where clid = '".$clid."' and id = '".$id."' and identity = '$identity'" );

			}

		}

	}

	print "Профиль обновлен";
	exit();
}
if ( $action == 'del' ) {

	$id   = $_REQUEST['id'];
	$clid = $_REQUEST['clid'];

	$db -> query( "delete from ".$sqlname."profile where id = '".$id."' and clid='".$clid."' and identity = '$identity'" );

	print "Профиль обновлен";

	exit();
}
if ( $action == '' ) {

	print '<DIV id="profile"><DIV class="flex-container wp100">';

	//считываем профиль полностью и формируем представление
	$result = $db -> getAll( "SELECT * FROM ".$sqlname."profile_cat WHERE identity = '$identity' ORDER by ord" );
	foreach ( $result as $data ) {

		$val = '';

		$value = yexplode( ";", $db -> getOne( "SELECT value FROM ".$sqlname."profile WHERE clid = '".$clid."' and id = '".$data['id']."' and identity = '$identity'" ) );

		for ( $i = 0; $i < count( $value ); $i++ ) {

			if ( $value[ $i ] != '' )
				$val .= '<div class="tagsfull white">'.$value[ $i ].'</div>';
			else $val .= '<div class="tags gray">--не заполнен--</div>';

		}

		if ( count( $value ) == 0 )
			$val = '<div class="tags gray">--не заполнен--</div>';

		//$width = $data['pwidth'] - 5;
		//$width = ($data['pwidth'] < 50) ? "50" : "100";

		if ( $data['pwidth'] <= 25 )
			$width = "wp25";
		elseif ( $data['pwidth'] <= 50 )
			$width = "wp50";
		elseif ( $data['pwidth'] <= 100 )
			$width = "wp100";

		if ( $data['tip'] != 'divider' ) {
			print '
				<div class="flex-string '.$width.' mb15">
				
					<div class="fname pb3 fs-10">'.$data['name'].':</div>
					<div class="text-content relativ">
						'.$val.'&nbsp;
						<a href="javascript:void(0)" class="idel gray blue" onClick="clear_profile(\''.$data['id'].'\')" title="Очистить"><i class="icon-cancel-circled blue"></i></a>
					</div>
					
				</div>';
		}

		if ( $data['tip'] == 'divider' )
			print '<div id="divider" class="wp100 text-center mb20 block"><b>'.$data['name'].'</b></div>';

		$val = '';

	}
	print '</DIV></DIV>';

	exit();
}
if ( $action == 'profil' ) {
	?>
	<DIV class="zagolovok">Профиль клиента "<?= current_client( $clid ) ?>"</DIV>

	<DIV id="profile" style="overflow-y: auto; overflow-x: hidden" class="p10 flex-container">
		<?php
		//считываем профиль полностью и формируем представление
		$result = $db -> getAll( "SELECT * FROM ".$sqlname."profile_cat WHERE identity = '$identity' ORDER by ord" );
		foreach ( $result as $data ) {

			$value = explode( ";", $db -> getOne( "SELECT value FROM ".$sqlname."profile WHERE clid = '".$clid."' and id = '".$data['id']."' and identity = '$identity'" ) );

			for ( $i = 0; $i < count( $value ); $i++ ) {

				if ( $value[ $i ] != '' )
					$val .= '<div class="tagsfull white">'.$value[ $i ].'</div>';
				else $val .= '<div class="tags gray">--не заполнен--</div>';

			}

			if ( $data['pwidth'] <= 25 )
				$width = "wp25";
			elseif ( $data['pwidth'] <= 50 )
				$width = "wp50";
			elseif ( $data['pwidth'] <= 100 )
				$width = "wp100";

			if ( $data['tip'] != 'divider' ) {

				print '
				<div class="flex-string '.$width.' mb15">
					<div class="fname pb3">'.$data['name'].':</div>
					<div class="text-content">'.$val.'&nbsp;</div>
				</div>';

			}

			if ( $data['tip'] == 'divider' )
				print '<div id="divider" class="wp100 text-center mb20 block"><b>'.$data['name'].'</b></div>';

			$val = '';

		}
		?>
	</DIV>

	<hr>

	<div class="button--pane pull-aright">

		<a href="javascript:void(0)" onclick="openClient('<?= $clid ?>')" class="button"><i class="icon-building"></i>Карточка</a>

	</div>
	<?php
}

if ( $action == 'edit' ) {
	?>
	<DIV class="zagolovok"><B>Профилирование клиента</B></DIV>

	<FORM action="content/card/card.profile.php" method="post" enctype="multipart/form-data" name="Form" id="Form">
		<INPUT type="hidden" name="action" id="action" value="add">
		<INPUT type="hidden" name="clid" id="clid" value="<?= $clid ?>">

		<DIV id="profile" style="overflow-y: auto; overflow-x: hidden">

			<div class="forma pad5">
				<?php
				$i      = 0;
				$result = $db -> getAll( "SELECT * FROM ".$sqlname."profile_cat WHERE identity = '$identity' ORDER by ord" );
				foreach ( $result as $data ) {

					//это варианты из шаблона профиля
					$variant[ $i ] = yexplode( ';', $data['value'] );

					//это ввыбранные варианты в профиле конкретного клиента
					$value = $db -> getOne( "SELECT value FROM ".$sqlname."profile WHERE clid = '".$clid."' and id = '".$data['id']."' and identity = '$identity'" );

					if ( $data['tip'] == 'input' ) {
						$pole = '<INPUT name="'.$data['pole'].'[]" id="'.$data['pole'].'[]" value="'.trim( $value ).'" type="text" style="width:99%">';
					}
					if ( $data['tip'] == 'text' ) {
						$pole = '<textarea name="'.$data['pole'].'[]" id="'.$data['pole'].'[]" rows="2" style="width:99%">'.$value.'</textarea>';
					}
					if ( $data['tip'] == 'select' ) {
						for ( $j = 0; $j < count( $variant[ $i ] ); $j++ ) {

							$sel = ($value == $variant[ $i ][ $j ]) ? 'selected' : '';
							$v   .= '<option value="'.trim( $variant[ $i ][ $j ] ).'" '.$sel.'>'.trim( $variant[ $i ][ $j ] ).'</option>';

						}
						$pole = '<SELECT name="'.$data['pole'].'[]" id="'.$data['pole'].'[]" style="width:99%"><option value="">--выбор--</option>'.$v.'</SELECT>';
					}
					if ( $data['tip'] == 'checkbox' ) {

						$xvalue = [];
						if ( $value != '' ) {
							$xvalue = (array)yexplode( ';', (string)$value );
						}

						for ( $j = 0; $j < count( $variant[ $i ] ); $j++ ) {

							$sel = (in_array( trim( $variant[ $i ][ $j ] ), $xvalue )) ? 'checked="checked"' : '';

							if ( $variant[ $i ] != '' )
								$v .= '<div class="checkbox"><label><input type="checkbox" name="'.$data['pole'].'[]" id="'.$data['pole'].'[]" value="'.trim( $variant[ $i ][ $j ] ).'" '.$sel.'><span class="custom-checkbox"><i class="icon-ok"></i></span><span class="pl10">'.$variant[ $i ][ $j ].'</span></label></div>';
						}

						$pole = $v;
					}
					if ( $data['tip'] == 'radio' ) {

						for ( $j = 0; $j < count( $variant[ $i ] ); $j++ ) {

							if ( trim( $variant[ $i ][ $j ] ) == trim( $value ) )
								$sel = 'checked="checked"';
							else $sel = '';

							if ( $variant[ $i ] != '' )
								$v .= '<div class="radio"><label><input type="radio" name="'.$data['pole'].'[]" id="'.$data['pole'].'[]" value="'.trim( $variant[ $i ][ $j ] ).'" '.$sel.'><span class="custom-radio success1"><i class="icon-radio-check"></i></span><span class="title pl10">'.$variant[ $i ][ $j ].'</span></label></div>';
						}
						$pole = $v;
					}
					if ( $data['tip'] != 'divider' ) {
						?>
						<div class="fdiv">
							<div class="fname"><b><?= $data['name'] ?>:</b></div>
							<div class="fpole" style="width:99%"><?= $pole ?></div>
						</div>
						<?php
					}
					if ( $data['tip'] == 'divider' )
						print '<div id="divider" style="width:97%; float:left; margin-top:10px" align="center"><b>'.$data['name'].'</b></div>';
					$i++;
					$v = '';
				}
				?>
			</div>

		</DIV>

		<hr>

		<div class="button--pane pull-aright">

			<A href="javascript:void(0)" onClick="$('#Form').submit();" class="button">Сохранить</A>&nbsp;
			<A href="javascript:void(0)" onClick="DClose()" class="button">Отмена</A>

		</div>
	</FORM>
	<?php
}
?>
<script>

	var hh = $('#dialog_container').actual('height') * 0.90;
	var hh2 = hh - $('.zagolovok').actual('outerHeight') - $('.button--pane').actual('outerHeight') - 30;

	if ($(window).width() > 990) {
		$('#dialog').css({'width': '800px'});
	}
	else {
		$('#dialog').css('width', '90vw');
	}

	$('#profile').css('max-height', hh2);

	$(document).ready(function () {

		$('#dialog').center();

	});

	$('#Form').ajaxForm({
		beforeSubmit: function () {

			var $out = $('#message');
			var em = checkRequired();

			if (em === false) return false;

			$('#dialog').css('display', 'none');
			$('#dialog_container').css('display', 'none');

			$out.css('display', 'block').append('<div id=loader><img src=/assets/images/loader.gif> Загрузка данных. Пожалуйста подождите...</div>');

			return true;

		},
		success: function (data) {

			$('#dialog').css('display', 'none');
			$('#resultdiv').empty();
			$('#dialog_container').css('display', 'none');
			$('#dialog').css('width', '500px');

			settab('9');

			$('#message').fadeTo(1, 1).css('display', 'block').html(data);
			setTimeout(function () {
				$('#message').fadeTo(1000, 0);
			}, 20000);

		}
	});

	function clear_profile(id) {

		var url = 'content/card/card.profile.php?id=' + id + '&action=del&clid=<?=$clid?>';
		var cf = confirm('Вы действительно хотите очистить указанный признак профиля?');
		if (cf) {

			$.post(url, function (data) {
				$('#tab9').load('content/card/card.profile.php?clid=<?=$clid?>');
				$('#message').fadeTo(1, 1).css('display', 'block').html(data);
				setTimeout(function () {
					$('#message').fadeTo(1000, 0);
				}, 20000);
			});

		}

	}
</script>