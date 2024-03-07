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
$day4control = 20;
$services    = (array)isServices();

/**
 * Продление - только успешно закрытые сделки
 * Аренда - все сервисные сделки
 */
$query = "
	SELECT 
	* 
	FROM {$sqlname}dogovor 
	WHERE 
		(
			(datum_end BETWEEN DATE_ADD(CURDATE(), INTERVAL -$day4control DAY) and DATE_ADD(CURDATE(), INTERVAL $day4control DAY)) 
			OR 
			datum_close BETWEEN DATE_ADD(CURDATE(), INTERVAL -380 DAY) and DATE_ADD(CURDATE(), INTERVAL -350 DAY)
		) 
		AND
		( 
			(COALESCE(close, 'no') = 'yes' and kol_fact > 0) 
			".(!empty( $services ) ? "OR 
			(tip IN (".yimplode( ",", (array)isServices() )."))" : "")." 
		) 
		AND 
		iduser IN (".yimplode( ",", get_people( $iduser1, "yes" ) ).") AND 
		-- (SELECT COUNT(*) FROM {$sqlname}dogovor WHERE clid = clid and close != 'yes') AND
		identity = '$identity' 
	ORDER BY datum_end
";

$query1 = "
	SELECT 
	* 
	FROM {$sqlname}dogovor 
	WHERE 
		datum_end BETWEEN '".current_datum( $day4control )." 00:00:01' and '".current_datum( -$day4control )." 23:59:59' and
		( 
			(close = 'yes' and kol_fact > 0 and tip NOT IN (".yimplode( ",", $services ).")) 
			".(!empty( $services ) ? "OR (tip IN (".yimplode( ",", $services ).") and close != 'yes')" : "")."
		) and 
		iduser IN (".yimplode( ",", get_people( $iduser1, "yes" ) ).") and 
		identity = '$identity' 
	ORDER BY datum_end
";

$res = $db -> query( $query );
while ($da = $db -> fetch( $res )) {

	//проверяем сделку на Сервисную
	$isService = isServices( (int)$da['did'] );

	$apx = !empty( $services ) ? ($isService ? " and tip IN (".yimplode( ",", (array)isServices() ).")" : " and tip NOT IN (".yimplode( ",", isServices() ).")") : "";

	//число активных сделок по клиенту
	$acount = $db -> getOne( "SELECT COUNT(*) FROM {$sqlname}dogovor WHERE clid = '$da[clid]' $apx and close != 'yes'" ) + 0;

	//для сервисных сделок считаем сумму по спецификации
	if ( $isService ) {

		$speka = (new Speka()) -> getSpekaData( $da['did'] );

		$da['kol'] = $speka['summaItog'];

	}

	if ( $acount == 0 )
		$dogovor[] = [
			"day"   => $da['datum_end'] != '0000-00-00' ? diffDate2( $da['datum_end'] ) : diffDate2( $da['datum_close'] ),
			"id"    => $da['did'],
			"clid"  => $da['clid'],
			"pid"   => $da['pid'],
			"title" => ($da['clid'] > 0) ? current_client( $da['clid'] ) : current_person( $da['pid'] ),
			"type"  => $isService ? "Аренда" : "Повторно",
			"color" => $isService ? "orange" : "green",
			"icon"  => $isService ? '<i class="icon-arrows-cw green"></i>' : '<i class="icon-briefcase broun"></i>',
			"step"  => current_dogstepname( $da['idcategory'] ),
			"summa" => $da['kol']
		];

}

//пересортируем массив
function cmp($a, $b) {
	return $a['day'] - $b['day'];
}

usort( $dogovor, "cmp" );

if ( !empty( $dogovor ) ) {
	?>
	<div class="border--bottom">
		<?php
		foreach ( $dogovor as $deal ) {

			$znak  = "";
			$color = "green";

			if ( $deal['day'] == 0 )
				$color = "blue";
			elseif ( $deal['day'] < 0 )
				$color = "red";

			if ( $deal['day'] < 0 )
				$znak = "-";
			elseif ( $deal['day'] > 0 )
				$znak = "+";

			?>
			<div class="flex-container ha mb5 mob--card">

				<div class="flex-string wp100 mb5" title="<?= $deal['type'] ?>">
					<span class="ellipsis Bold fs-12 pt5 mb5">
						<a href="javascript:void(0)" onClick="openDogovor('<?= $deal['id'] ?>')"><?= $deal['icon'] ?>&nbsp;<?= current_dogovor( $deal['id'] ) ?></a>
					</span>
				</div>

				<div class="flex-string wp60" title="<?= $deal['title'] ?>">
					<span class="ellipsis fs-10" title="<?= $deal['title'] ?>">
						<?= ($deal['clid'] > 0 ? '<a href="javascript:void(0)" onclick="openClient(\''.$deal['clid'].'\')" title=""><i class="icon-building blue"></i>'.$deal['title'].'</a>' : '<a href="javascript:void(0)" onclick="openPerson(\''.$deal['pid'].'\')" title=""><i class="icon-user-1 blue"></i>'.$deal['title'].'</a>') ?>
					</span>
				</div>

				<div class="flex-string wp10">
					<span class="ellipsis fs-10 Bold <?= $color ?>">
						<?= $znak.abs( $deal['day'] ) ?> дн.
					</span>
				</div>

				<div class="flex-string wp20">
					<span class="ellipsis fs-11 Bold <?= $deal['color'] ?>" title="<?= $deal['title'] ?>">
						<?= $deal['type'] ?>
					</span><br>
					<span class="fs-09 gray2"><?= num_format( $deal['summa'] ) ?></span>
				</div>

			</div>
			<?php
		}
		?>
	</div>
	<?php
}
else {
	print $lang['face']['DealsName'][1]." к продлению в период ±$day4control дней нет";
}