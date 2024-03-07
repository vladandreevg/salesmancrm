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
$day4control = 10;
$services    = (array)isServices();

$stepInHold = customSettings('stepInHold');

if($stepInHold['step'] > 0 && $stepInHold['input'] != ''){

	$sort .= " AND ({$sqlname}dogovor.idcategory != '$stepInHold[step]' OR ({$sqlname}dogovor.idcategory = '$stepInHold[step]' AND DATE({$sqlname}dogovor.".$stepInHold['input'].") <= DATE(NOW()) )) ";

}
elseif($otherSettings['dateFieldForFreeze'] != ''){

	$sort .= " AND ({$sqlname}dogovor.isFrozen = '1' AND DATE({$sqlname}dogovor.".$otherSettings['dateFieldForFreeze'].") <= DATE(NOW()) ) ";

}

/**
 * Продление - только успешно закрытые сделки
 * Аренда - все сервисные сделки
 */
$query2 = "
	SELECT 
	* 
	FROM {$sqlname}dogovor 
	WHERE 
		(
			(
				COALESCE(close, 'no') != 'yes' AND 
				datum_plan BETWEEN '".current_datum( $day4control )." 00:00:01' AND '".current_datum( -$day4control )." 23:59:59' AND
				".(!empty( $services ) ? "tip NOT IN (".yimplode( ",", $services ).") AND" : "")."
				kol > 0
			) 
			OR 
			( 
				datum_end BETWEEN '".current_datum( $day4control )." 00:00:01' AND '".current_datum( -$day4control )." 23:59:59' AND
				( 
					(COALESCE(close, 'no') = 'yes' AND kol_fact > 0) 
					".(!empty( $services ) ? "OR tip IN (".yimplode( ",", $services ).")" : "")."
				)
			) 
		)
		AND 
		iduser IN (".yimplode( ",", get_people( $iduser1, "yes" ) ).") AND 
		identity = '$identity' 
	ORDER BY datum_end
";

$goodclosestatus = $db -> getCol("SELECT sid FROM {$sqlname}dogstatus WHERE result_close = 'win'");

//print
$query = "
	SELECT
	*
	FROM {$sqlname}dogovor
	WHERE
		(
			(
				COALESCE(close, 'no') != 'yes' AND
				".($stepInHold['step'] > 0 && $stepInHold['input'] != '' ? "
				( 
					({$sqlname}dogovor.idcategory != '$stepInHold[step]' AND datum_plan BETWEEN '".current_datum( $day4control )." 00:00:01' AND '".current_datum( -$day4control )." 23:59:59') OR 
					({$sqlname}dogovor.idcategory = '$stepInHold[step]' AND DATE({$sqlname}dogovor.".$stepInHold['input'].") <= DATE(NOW()) )
				) AND" : "datum_plan BETWEEN '".current_datum( $day4control )." 00:00:01' AND '".current_datum( -$day4control )." 23:59:59' AND")."
				".($otherSettings['dateFieldForFreeze'] != '' ? "
				( 
					({$sqlname}dogovor.isFrozen != '1' AND datum_plan BETWEEN '".current_datum( $day4control )." 00:00:01' AND '".current_datum( -$day4control )." 23:59:59') OR 
					({$sqlname}dogovor.isFrozen = '1' AND DATE({$sqlname}dogovor.".$otherSettings['dateFieldForFreeze'].") <= DATE(NOW()) )
				) AND" : "datum_plan BETWEEN '".current_datum( $day4control )." 00:00:01' AND '".current_datum( -$day4control )." 23:59:59' AND")."
				".(!empty( $services ) ? "tip NOT IN (".yimplode( ",", $services ).") AND" : "")."
				did > 0
				-- kol > 0
			)
			OR
			(
				datum_end BETWEEN '".current_datum( $day4control )." 00:00:01' AND '".current_datum( -$day4control )." 23:59:59' AND
				(
					(
						COALESCE(close, 'no') = 'yes' 
						-- and kol_fact > 0 
						".(!empty( $goodclosestatus ) ? "and sid IN (".yimplode( ",", $goodclosestatus ).")" : "")."
						".(!empty( $services ) ? "and tip NOT IN (".yimplode( ",", $services ).")" : "")."
					)
					".(!empty( $services ) ? "OR (tip IN (".yimplode( ",", $services ).") and close != 'yes')" : "")."
				)
			)
		)
		AND
		iduser IN (".yimplode( ",", get_people( $iduser1, "yes" ) ).") AND
		identity = '$identity'
	ORDER BY datum_end
";

$res = $db -> query( $query );
while ($da = $db -> fetch( $res )) {

	//проверяем сделку на Сервисную
	$isService = isServices( (int)$da['did'] );

	//для сервисных сделок считаем сумму по спецификации
	if ( $isService ) {

		$speka     = (new Speka()) -> getSpekaData( (int)$da['did'] );
		$da['kol'] = $speka['summaItog'];

	}

	$type  = $isService ? "Аренда" : ($da['close'] == 'yes' ? "Повторная" : "Реализация");
	$color = $isService ? "red" : ($da['close'] == 'yes' ? "blue" : "green");
	$day   = $isService ? diffDate2( $da['datum_end'] ) : ($da['close'] != 'yes' ? diffDate2( $da['datum_plan'] ) : diffDate2( $da['datum_end'] ));
	$datum = $isService ? get_dateru( $da['datum_end'] ) : ($da['close'] != 'yes' ? get_dateru( $da['datum_plan'] ) : get_dateru( $da['datum_end'] ));
	$icon  = $isService ? '<i class="icon-arrows-cw green"></i>' : ($da['close'] == 'yes' ? '<i class="icon-cw blue"></i>' : '<i class="icon-briefcase broun"></i>');

	// для замороженных сделок
	if($da['idcategory'] == $stepInHold['step']){
		$icon = '<i class="icon-snowflake-o bluemint" title="Открыть в новом окне"></i>';
	}
	if($da['isFrozen'] == '1'){
		$icon = '<i class="icon-snowflake-o bluemint" title="Открыть в новом окне"></i>';
	}

	$dogovor[] = [
		"day"   => $day,
		"datum" => $datum,
		"id"    => (int)$da['did'],
		"clid"  => (int)$da['clid'],
		"pid"   => (int)$da['pid'],
		"title" => ((int)$da['clid'] > 0) ? current_client( (int)$da['clid'] ) : current_person( (int)$da['pid'] ),
		"type"  => $type,
		"color" => $color,
		"icon"  => $icon,
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

				<div class="flex-string wp100 pt5 mb5" title="<?= $deal['type'] ?>">
					<span class="Bold fs-12">
						<a href="javascript:void(0)" onClick="openDogovor('<?= $deal['id'] ?>')"><?= $deal['icon'] ?>&nbsp;<?= current_dogovor( $deal['id'] ) ?> <sup><?= $deal['step'] ?>%</sup></a>
					</span>
				</div>
				<div class="flex-string wp60" title="<?= $deal['title'] ?>">
					<span class="ellipsis fs-10 mt5">
						<?= ($deal['clid'] > 0 ? '<a href="javascript:void(0)" onclick="openClient(\''.$deal['clid'].'\')" title=""><i class="icon-building blue"></i>'.$deal['title'].'</a>' : '<a href="javascript:void(0)" onclick="openPerson(\''.$deal['pid'].'\')" title=""><i class="icon-user-1 blue"></i>'.$deal['title'].'</a>') ?>
					</span>
				</div>

				<div class="flex-string wp10" title="<?= $deal['datum'] ?>">
					<span class="ellipsis fs-10 Bold <?= $color ?>">
						<?= $znak.abs( $deal['day'] ) ?> дн.
					</span>
				</div>

				<div class="flex-string wp20">
					<span class="ellipsis fs-12 Bold <?= $deal['color'] ?>" title="<?= $deal['title'] ?>">
						<?= $deal['type'] ?>
					</span><br>
					<span class="gray2 fs-09"><?= num_format( $deal['summa'] ) ?></span>
				</div>

			</div>
			<?php
		}
		?>
	</div>
	<?php
}
else {
	print $lang['face']['DealsName'][1]." к реализации в период ±$day4control дней нет";
}