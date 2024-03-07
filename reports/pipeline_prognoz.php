<?php
error_reporting( E_ERROR );
ini_set( 'display_errors', 1 );
header( "Pragma: no-cache" );

$rootpath = realpath( __DIR__.'/../' );

include $rootpath."/inc/config.php";
include $rootpath."/inc/dbconnector.php";
include $rootpath."/inc/auth.php";
include $rootpath."/inc/func.php";
include $rootpath."/inc/settings.php";
include $rootpath."/inc/language/".$language.".php";

$action = $_REQUEST['action'];
$da1    = $_REQUEST['da1'];
$da2    = $_REQUEST['da2'];
$da     = $_REQUEST['da'];
$act    = $_REQUEST['act'];
$per    = $_REQUEST['per'];

if ( !$per )
	$per = 'nedelya';

$user_list    = (array)$_REQUEST['user_list'];
$clients_list = (array)$_REQUEST['clients_list'];
$persons_list = (array)$_REQUEST['persons_list'];
$fields       = (array)$_REQUEST['field'];
$field_query  = (array)$_REQUEST['field_query'];

//массив пользователей
$user_list = (!empty( $user_list )) ? $user_list : (array)get_people( $iduser1, "yes" );

$users = $db -> getIndCol("iduser", "SELECT iduser, title FROM {$sqlname}user WHERE iduser IN (".yimplode(",", $user_list).") AND identity = '$identity'");

//составляем запрос по параметрам сделок
$ar = [
	'sid',
	'close'
];
foreach ( $fields as $i => $field ) {

	if ( !in_array( $field, $ar ) && !in_array( $field, [
			'close',
			'mcid'
		] ) ) {
		$sort .= " deal.".$field." = '".$field_query[ $i ]."' AND ";
	}
	elseif ( $field == 'close' ) {
		$sort .= $field_query[ $i ] != 'yes' ? " COALESCE(deal.{$field}, 'no') != 'yes' AND " : " COALESCE(deal.{$field}, 'no') == 'yes' AND ";
	}
	elseif ( $field == 'mcid' ) {
		$mc = $field_query[ $i ];
	}

}

if ( $action == '' ) {

	$list = $xcount = [];

	//массив пользователей
	foreach ( $user_list as $i => $user ) {

		$j = 0;

		$resultt = $db -> getAll( "SELECT * FROM {$sqlname}clientcat WHERE iduser = '$user' and identity = '$identity' ORDER BY title" );
		foreach ( $resultt as $xdata ) {

			$dogs = [];
			$counts  = [];

			$resultm = $db -> getAll( "
				SELECT * 
				FROM {$sqlname}dogovor 
				WHERE 
					(
						(close != 'yes' and datum_plan between '$da1 00:00:00' and '$da2 23:59:59') or 
						(close = 'yes' and datum_close between '$da1 00:00:00' and '$da2 23:59:59')
					) and 
					clid = '".$xdata['clid']."' and 
					identity = '$identity' 
				ORDER BY datum_plan
			" );
			foreach ( $resultm as $data ) {

				$stitle = $db -> getOne( "SELECT content FROM {$sqlname}dogcategory  WHERE idcategory='".$data['idcategory']."' and identity = '$identity'" );

				if ( $data['close'] == 'yes' ) {

					$close = 'yes';
					$icon  = '<i class="icon-lock red"></i>';
					$datum = $data['datum_close'];
					$kolp  = $data['kol_fact'];

				}
				else {

					$close = 'no';
					$icon  = '<i class="icon-briefcase-1 broun"></i>';
					$datum = $data['datum_plan'];
					$kolp  = $data['kol'];

				}

				//массив сделок i-го пользователя j-го клиента
				$dogs[] = [
					"did"     => $data['did'],
					"clid"    => $data['clid'],
					"pid"     => $data['pid'],
					"step"    => current_dogstepname( (int)$data['idcategory'] ),
					"stepdes" => $stitle,
					"title"   => $data['title'],
					"kolp"    => $kolp,
					"datum"   => $datum,
					"type"    => current_dogtype( (int)$data['tip'] ),
					"close"   => $close,
					"icon"    => $icon
				];

				$vess = intval( current_dogstepname( (int)$data['idcategory'] ) );

				$counts['summa'] += $data['kol'];
				$counts['ves']   += $data['kol'] * $vess / 100;
				$counts['counts']++;

			}

			//массив клиентов по пользователю
			if($counts['counts'] > 0) {
				$list[ $user ][ (int)$xdata['clid'] ] = [
					"client" => $xdata['title'],
					"deals"  => $dogs,
					"summa"  => $counts['summa'],
					"ves"    => $counts['ves'],
					"counts" => $counts['counts'],
				];
			}

			$xcount[ $user ]['summa'] += $counts['summa'];
			$xcount[ $user ]['ves'] += $counts['ves'];
			$xcount[ $user ]['count'] += $counts['counts'];

		}

	}

	//print_r($list);

	?>
	<style>
		<!--
		#salestepss .pheader {
			display     : block;
			border      : 0 solid #79b7e7;
			background  : #78909C;
			font-weight : bold;
			height      : 30px;
			line-height : 30px;
			color       : #fff;
			font-size   : 12px;
		}
		#salestepss .pstring {
			background  : #CFD8DC;
			font-weight : bold;
			overflow    : hidden !important;
		}
		#salestepss .stringg:hover {
			background : #FF6;
		}
		#salestepss .stringg {
			border-bottom : 1px dotted #78909C;
			overflow      : hidden !important;
			box-sizing    : border-box;
		}
		#salestepss .column_1 {
			width       : 60%;
			display     : inline-block;
			line-height : 30px;
			float       : left;
			overflow    : hidden !important;
		}
		#salestepss .column_2 {
			width       : 15%;
			display     : inline-block;
			line-height : 30px;
			float       : left;
			overflow    : hidden !important;
		}
		#salestepss .column_3 {
			width       : 7%;
			display     : inline-block;
			line-height : 30px;
			float       : left;
			overflow    : hidden !important;
		}
		#salestepss .column_4 {
			width       : 15%;
			display     : inline-block;
			line-height : 30px;
			float       : left;
			overflow    : hidden !important;
		}
		#salestepss .user {
			background : #FFF9C4;
			cursor     : pointer;
		}
		#salestepss .pad20 {
			width   : 20px;
			display : inline-block;
		}
		#salestepss .pad40 {
			width   : 40px;
			display : inline-block;
		}
		#salestepss .pad60 {
			width   : 60px;
			display : inline-block;
		}
		#salestepss .cur {
			cursor : pointer;
		}
		#salestepss .sb {
			font-size   : 1em;
			font-weight : bold;
			background  : #E6E6FA;
		}
		-->
	</style>
	<div class="zagolovok_rep">&nbsp;&nbsp;Pipeline. Ожидаемый приход по Сотрудникам</div>
	<hr>
	<div id="salestepss" style="margin:10px 30px">

		<div class="pheader sticked--top text-center">
			<div class="column_1">[Наименование Клиента]</div>
			<div class="column_2">[Сумма, <?= $valuta ?>]</div>
			<div class="column_4">[Вес, <?= $valuta ?>]</div>
			<div class="column_3">[Кол.]</div>
		</div>
		<?php
		foreach ( $list as $user => $items ) {
			?>
			<div class="stringg pstring">
				<div class="column_1 pl5"><?= $users[ $user ] ?></div>
				<div class="column_2 text-right"><?= num_format( $xcount[ $user ]['summa'] ) ?></div>
				<div class="column_4 text-right"><?= num_format( $xcount[ $user ]['ves'] ) ?></div>
				<div class="column_3 text-right"><?= number_format( $xcount[ $user ]['count'], 0, '.', ' ' ); ?></div>
			</div>
			<?php
			foreach ($items as $clid => $item) {

				?>
				<div class="togglerbox hand stringg user stepname" data-id="block_<?= $user ?>_<?= $clid ?>" title="Показать/Скрыть">
					<div class="column_1" title="<?= $item['client'] ?>">
						<div class="inline w20">&nbsp;</div>&nbsp;<i class="icon-angle-down" id="mapic"></i>&nbsp;<?= $item['client'] ?>
					</div>
					<div class="column_2 text-right">&nbsp;<?= num_format( $item['summa'] ) ?></div>
					<div class="column_4 text-right">&nbsp;<?= num_format( $item['ves'] ) ?></div>
					<div class="column_3 text-right">&nbsp;<?= number_format( $item['counts'], 0, ',', ' ' ); ?></div>
				</div>
				<div id="block_<?= $user ?>_<?= $clid ?>" class="hidden">
					<?php
					foreach ($item['deals'] as $deal) {
						?>
						<div class="stringg cur" onClick="viewDogovor('<?= $deal['did'] ?>')" title="Просмотр сделки">
							<div class="column_1">
								<div class="inline pl20">&nbsp;</div><?= $deal['icon'] ?>[<b class="blue" title="<?= $deal['stepdes'] ?>"><?= $deal['step'] ?>%</b>]&nbsp;<b><?= $deal['title'] ?></b>&nbsp;[<b><?= format_date_rus( $deal['datum'] ) ?></b>]&nbsp;[<span class="blue"><?= $deal['type'] ?></span>]
							</div>
							<div class="column_2 text-right"><?= num_format( $deal['kolp'] ) ?></div>
							<div class="column_4 text-right">&nbsp;</div>
							<div class="column_3 text-right">&nbsp;</div>
						</div>
						<?php
					}
					?>
				</div>
				<?php

			}
			?>
			<?php
		}
		?>
		<div id="chart"></div>
	</div>
	<hr>
	<div class="formdiv">Вы можете использовать параметры: Период, Сотрудники, Сделки. В отчете выводятся
		<b>Активные</b> (по плановой дате) и <b>Закрытые</b> (по дате закрытия) сделки
	</div>
	<div style="height: 65px;"></div>
<?php } ?>