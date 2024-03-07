<?php
/* ============================ */
/*         SalesMan CRM         */
/* ============================ */
/* (C) 2018 Vladislav Andreev   */
/*       SalesMan Project       */
/*        www.isaler.ru         */
/*        ver. 2018.x           */
/* ============================ */

use Salesman\Speka;

error_reporting( E_ERROR );
header( "Pragma: no-cache" );

$rootpath = dirname( __DIR__, 2 );

include $rootpath."/inc/config.php";
include $rootpath."/inc/dbconnector.php";
include $rootpath."/inc/auth.php";
include $rootpath."/inc/func.php";
include $rootpath."/inc/settings.php";
include $rootpath."/inc/language/".$language.".php";

$thisfile = basename( __FILE__ );

$dogovor     = [];
$day4control = 30;

/**
 * Продление - только успешно закрытые сделки
 * Аренда - все сервисные сделки
 */
$query = "
	SELECT 
	* 
	FROM {$sqlname}dogovor 
	WHERE 
		COALESCE(close, 'no') = 'yes' AND
		-- datum_close BETWEEN '".current_datum( $day4control )." 00:00:01' and '".current_datum( -$day4control )." 23:59:59' and
		iduser IN (".yimplode( ",", get_people( $iduser1, "yes" ) ).") and 
		identity = '$identity' 
	ORDER BY datum_close DESC
	LIMIT 15
";
$res   = $db -> query( $query );
while ( $da = $db -> fetch( $res ) ) {

	//проверяем сделку на Сервисную
	$isService = isServices( (int)$da[ 'did' ] );

	//для сервисных сделок считаем сумму по спецификации
	if ( $isService ) {

		$speka = (new Speka()) -> getSpekaData( (int)$da[ 'did' ] );

		$da[ 'kol' ] = $speka[ 'summaItog' ];

	}

	$status = $db -> getOne( "SELECT title FROM {$sqlname}dogstatus WHERE sid = '".$da[ 'sid' ]."' and identity = '$identity'" );

	if ( stripos( $status, 'Победа' ) !== false ) $class = 'blue';
	elseif ( stripos( $status, 'Отказ' ) !== false ) $class = 'red';
	elseif ( stripos( $status, 'Отмен' ) !== false ) $class = 'gray';
	elseif ( stripos( $status, 'Проиг' ) !== false ) $class = 'red';


	$dogovor[] = [
		"day"    => diffDate2( $da[ 'datum_close' ] ),
		"datum"  => get_dateru( $da[ 'datum_close' ] ),
		"id"     => $da[ 'did' ],
		"clid"   => $da[ 'clid' ],
		"pid"    => $da[ 'pid' ],
		"title"  => ( $da[ 'clid' ] > 0 ) ? current_client( $da[ 'clid' ] ) : current_person( $da[ 'pid' ] ),
		"type"   => $isService ? "Аренда" : "Продажа",
		"color"  => $isService ? "red" : "blue",
		"class"  => $class,
		"icon"   => $isService ? '<i class="icon-arrows-cw green"></i>' : '<i class="icon-briefcase broun"></i>',
		"status" => $status,
		"step"   => current_dogstepname( $da[ 'idcategory' ] ),
		"summa"  => $da[ 'kol_fact' ],
		"deal"   => $da[ 'title' ],
	];

}

//пересортируем массив
function cmp( $a, $b ) { return $b[ 'day' ] - $a[ 'day' ]; }

usort( $dogovor, "cmp" );

if ( !empty( $dogovor ) ) {
	?>
	<div class="border--bottom">
		<?php
		foreach ( $dogovor as $deal ) {

			$znak  = "";
			$color = "";

			if ( $deal[ 'day' ] < 0 ) $znak = "-";
			elseif ( $deal[ 'day' ] > 0 ) $znak = "+";

			?>
			<div class="flex-container ha mb5 mob--card">

				<div class="flex-string wp100 pt5 mb5" title="<?= $deal[ 'deal' ] ?>">
					<span class="Bold fs-12">
						<a href="javascript:void(0)" onClick="openDogovor('<?= $deal[ 'id' ] ?>')"><?= $deal[ 'icon' ] ?>&nbsp;<?= $deal[ 'deal' ] ?> <sup><?= $deal[ 'step' ] ?>%</sup></a>
					</span>
				</div>

				<div class="flex-string wp60" title="<?= $deal[ 'title' ] ?>">
					<span class="ellipsis fs-10">
						<?= ( $deal[ 'clid' ] > 0 ? '<a href="javascript:void(0)" onclick="openClient(\''.$deal[ 'clid' ].'\')" title=""><i class="icon-building blue"></i>'.$deal[ 'title' ].'</a>' : '<a href="javascript:void(0)" onclick="openPerson(\''.$deal[ 'pid' ].'\')" title=""><i class="icon-user-1 blue"></i>'.$deal[ 'title' ].'</a>' ) ?>
					</span>
				</div>

				<div class="flex-string wp10" title="<?= $deal[ 'datum' ] ?>">
					<span class="ellipsis fs-10 Bold <?= $color ?>">
						<?= $znak.abs( $deal[ 'day' ] ) ?> дн.
					</span>
				</div>

				<div class="flex-string wp20">
					<span class="ellipsis fs-12 Bold <?= $deal[ 'class' ] ?>" title="<?= $deal[ 'status' ] ?>">
						<?= $deal[ 'status' ] ?>
					</span><br>
					<span class="gray2 fs-09"><?= num_format( $deal[ 'summa' ] ) ?></span><br>
					<span class="ellipsis fs-07 mt5 <?= $deal[ 'color' ] ?>" title="<?= $deal[ 'type' ] ?>">
						<?= $deal[ 'type' ] ?>
					</span>
				</div>

			</div>
			<?php
		}
		?>
	</div>
	<?php
}
else {
	print "Записей не найдено";
}