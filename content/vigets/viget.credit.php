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

if( !isset($otherSettings[ 'creditAlert' ]) ){
	$otherSettings[ 'creditAlert' ] = 10;
}

$credit = [];

$query = "
SELECT
	{$sqlname}credit.crid as crid,
	{$sqlname}credit.did as did,
	{$sqlname}credit.clid as clid,
	{$sqlname}credit.pid as pid,
	{$sqlname}credit.do as do,
	{$sqlname}credit.invoice as invoice,
	{$sqlname}credit.summa_credit as summa,
	{$sqlname}credit.datum_credit as pdatum,
	{$sqlname}credit.invoice_date as idatum,
	{$sqlname}credit.iduser as iduser,
	{$sqlname}dogovor.title as deal,
	{$sqlname}dogovor.close as close,
	{$sqlname}dogovor.idcategory as step,
	{$sqlname}dogovor.payer as payer
FROM {$sqlname}credit
	LEFT JOIN {$sqlname}dogovor ON {$sqlname}credit.did = {$sqlname}dogovor.did
WHERE
	{$sqlname}credit.crid > 0 and
	{$sqlname}credit.do != 'on' and
	{$sqlname}credit.datum_credit BETWEEN '".current_datum( (int)$otherSettings[ 'creditAlert' ] )." 00:00:01' and '".current_datum( -(int)$otherSettings[ 'creditAlert' ] )." 23:59:59' and
	{$sqlname}credit.iduser IN (".implode( ",", get_people( $iduser1, 'yes' ) ).") and
	COALESCE({$sqlname}dogovor.close, 'no') != 'yes' and 
	{$sqlname}credit.identity = '$identity'
ORDER BY {$sqlname}credit.datum_credit";

$result = $db -> getAll( $query );
foreach ( $result as $data ) {

	$clid   = $pid = 0;
	$person = $client = '';

	//проверяем сделку на Сервисную
	$isService = isServices( (int)$data[ 'did' ] );

	//для сервисных сделок считаем сумму по спецификации
	if ( $isService ) {

		$speka = (new Speka()) -> getSpekaData( (int)$data[ 'did' ] );

		$data[ 'summa' ] = $speka[ 'summaItog' ];

	}

	if ( (int)$payer > 0 ) {

		$client = $db -> getOne( "SELECT title FROM {$sqlname}clientcat WHERE clid = '".$payer."' and identity = '$identity'" );
		$clid   = $payer;

	}
	elseif ( (int)$data[ 'clid' ] > 0 ) {

		$client = $db -> getOne( "SELECT title FROM {$sqlname}clientcat WHERE clid='".$data[ 'clid' ]."' and identity = '$identity'" );
		$clid   = (int)$data[ 'clid' ];

	}
	else {

		$person = $db -> getOne( "SELECT person FROM {$sqlname}personcat WHERE pid='".$data[ 'pid' ]."' and identity = '$identity'" );
		$pid    = (int)$data[ 'pid' ];

	}

	$invoice = ( $data[ 'invoice' ] == '' ) ? "б/н" : $data[ 'invoice' ];

	$credit[] = [
		"day"     => round( diffDate2( $data[ 'pdatum' ] ) ),
		"datum"   => get_dateru( $data[ 'pdatum' ] ),
		"summa"   => num_format( $data[ 'summa' ] ),
		"client"  => $client,
		"clid"    => (int)$clid,
		"person"  => $person,
		"pid"     => (int)$pid,
		"invoice" => $invoice,
		"did"     => (int)$data[ 'did' ],
		"step"    => current_dogstepname( (int)$data[ 'step' ] ),
		"icon"    => $isService ? '<i class="icon-arrows-cw green"></i>' : '<i class="icon-briefcase broun"></i>',
		"crid"    => (int)$data[ 'crid' ],
		"deal"    => $data[ 'deal' ],
	];

}

//пересортируем массив
function cmp( $a, $b ) { return $a[ 'day' ] - $b[ 'day' ]; }

usort( $credit, "cmp" );

if ( empty( $credit ) ) {

	print "Платежей в ближайшие ±".$otherSettings[ 'creditAlert' ]." дней нет";

}
else {
	?>
	<div class="border--bottom">
		<?php
		foreach ( $credit as $deal ) {

			$znak  = "";
			$color = "green";

			if ( $deal[ 'day' ] == 0 ) $color = "blue";
			elseif ( $deal[ 'day' ] < 0 ) $color = "red";

			if ( $deal[ 'day' ] < 0 ) $znak = "-";
			elseif ( $deal[ 'day' ] > 0 ) $znak = "+";

			?>
			<div class="flex-container ha mb5 mob--card">

				<div class="flex-string wp100 pt5 mb5">
					<span class="Bold fs-12" title="<?= $deal[ 'type' ] ?>">
						<a href="javascript:void(0)" onClick="openDogovor('<?= $deal[ 'did' ] ?>','7')"><?= $deal[ 'icon' ] ?>&nbsp;<?= $deal[ 'deal' ] ?> <sup><?= $deal[ 'step' ] ?>%</sup></a>
					</span>
				</div>
				<div class="flex-string wp60" title="<?= $deal[ 'title' ] ?>">
					<span class="ellipsis fs-10 mt5">
						<?= ( $deal[ 'clid' ] > 0 ? '<a href="javascript:void(0)" onclick="openClient(\''.$deal[ 'clid' ].'\')" title=""><i class="icon-building blue"></i>'.$deal[ 'client' ].'</a>' : '<a href="javascript:void(0)" onclick="openPerson(\''.$deal[ 'pid' ].'\')" title=""><i class="icon-user-1 blue"></i>'.$deal[ 'person' ].'</a>' ) ?>
					</span>
				</div>

				<div class="flex-string wp10" title="<?= $deal[ 'datum' ] ?>">
					<span class="ellipsis fs-11 Bold <?= $color ?>">
						<?= $znak.abs( $deal[ 'day' ] ) ?> дн.
					</span>
				</div>

				<div class="flex-string wp20 hand" onclick="<?= $otherSettings[ 'printInvoice'] ? "editCredit('".$deal[ 'crid' ]."','credit.view')" : "openDogovor('".$deal[ 'did' ]."','7')" ?>">
					<span class="ellipsis fs-12 Bold blue" title="<?= $deal[ 'title' ] ?>">
						Сч. №<?= $deal[ 'invoice' ] ?>
					</span><br>
					<span class="gray2 fs-09" title="Сумма к оплате: <?= $deal[ 'summa' ] ?> руб."><?= $deal[ 'summa' ] ?></span>
				</div>

			</div>
			<?php
		}
		?>
	</div>
<?php } ?>