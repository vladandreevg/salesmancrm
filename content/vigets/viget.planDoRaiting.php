<?php
/* ============================ */
/*         SalesMan CRM         */
/* ============================ */
/* (C) 2018 Vladislav Andreev   */
/*       SalesMan Project       */
/*        www.isaler.ru         */
/*        ver. 2018.x           */
/* ============================ */

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

$pdrInterval = $_COOKIE['pdrInterval'];

$year = date( 'Y' );
$mon  = date( 'm' );

$dataa = [];

$result = $db -> getAll( "
	SELECT 
	* 
	FROM {$sqlname}user 
	WHERE 
		iduser > 0 AND 
		acs_plan = 'on' AND 
		(secrty = 'yes' OR DATE_FORMAT(CompEnd, '%Y-%m') >= '$year-$mon') AND 
		iduser IN (SELECT iduser FROM {$sqlname}plan WHERE year = '$year' AND mon = '$mon' AND iduser IN (".yimplode( ",", get_people( $iduser1, "yes" ) ).") AND identity = '$identity') AND 
		identity = '$identity' 
	ORDER BY title
" );

foreach ( $result as $data ) {

	$ac_importt[ $data['iduser'] ] = explode( ";", $data['acs_import'] );

	$did_str                    = $dids = [];
	$kolfact                    = 0;
	$marfact                    = 0;
	$didss                      = '';
	$avatarr[ $data['iduser'] ] = "/assets/images/noavatar.png";

	if ( $data['avatar'] )
		$avatarr[ $data['iduser'] ] = "/cash/avatars/".$data['avatar'];

	$users[ $data['iduser'] ]['iduser'] = $data['iduser'];
	$users[ $data['iduser'] ]['tip']    = $data['tip'];

	//плановые показатели для текущего сотрудника
	$result1                  = $db -> getRow( "SELECT SUM(kol_plan) as kol, SUM(marga) as marga FROM {$sqlname}plan WHERE year = '$year' and mon = '$mon' and iduser = '".$data['iduser']."' and identity = '$identity'" );
	$kplan[ $data['iduser'] ] = $result1['kol'];
	$mplan[ $data['iduser'] ] = $result1['marga'];

	//список сделок сотрудника, с учетом подчиненных (Индивидуальный план или нет)
	if ( $ac_importt[ $data['iduser'] ][19] != 'on' )
		$sub = get_people( $data['iduser'] );

	else
		$sub = " and iduser = '".$data['iduser']."'";

	/**
	 * фактические показатели
	 */

	$dolya = [];

	// по оплаченным счетам
	if ( !$otherSettings[ 'planByClosed'] ) {
		$result3 = $db -> getAll( "SELECT * FROM {$sqlname}credit WHERE do = 'on' AND DATE_FORMAT(invoice_date, '%Y-%m') = '$year-$mon' $sub AND identity = '$identity' ORDER by did" );
	}
	//$result3 = $db -> getAll("SELECT * FROM {$sqlname}credit WHERE do = 'on' and DATE_FORMAT(invoice_date, '%Y-%m') = '".$year."-".$mon."' and did IN (SELECT did FROM {$sqlname}dogovor WHERE did > 0 ".$sub." and identity = '$identity') and identity = '$identity' ORDER by did");

	// по оплаченным счетам в закрытых сделках
	if ( $otherSettings[ 'planByClosed'] ) {
		$result3 = $db -> getAll( "SELECT * FROM {$sqlname}credit WHERE do = 'on' $sub AND (SELECT did FROM {$sqlname}dogovor WHERE did = {$sqlname}credit.did AND DATE_FORMAT(datum_close, '%Y-%m') = '$year-$mon' AND close = 'yes' AND identity = '$identity') > 0 AND identity = '$identity' ORDER BY did" );
	}

	foreach ( $result3 as $data3 ) {

		//расчет процента размера платежа от суммы сделки
		$result4 = $db -> getRow( "SELECT kol, marga FROM {$sqlname}dogovor WHERE did = '".$data3['did']."' AND identity = '$identity'" );
		$kolfact = pre_format( $result4["kol"] );//сумма всей сделки
		$marfact = pre_format( $result4["marga"] );//сумма всей сделки

		$dolya = $kolfact > 0 ? $data3['summa_credit'] / $kolfact : 0;//% оплаченной суммы от суммы по договору

		$kfact[ $data['iduser'] ] += $data3['summa_credit'];
		$mfact[ $data['iduser'] ] += $marfact * $dolya;

	}

	$kperc[ $data['iduser'] ] = ($kplan[ $data['iduser'] ] > 0) ? $kfact[ $data['iduser'] ] / $kplan[ $data['iduser'] ] * 100 : 0;
	$mperc[ $data['iduser'] ] = ($mplan[ $data['iduser'] ] > 0) ? $mfact[ $data['iduser'] ] / $mplan[ $data['iduser'] ] * 100 : 0;

	/**
	 * Суммы за всё время
	 */
	// по оплаченным счетам
	if ( !$otherSettings[ 'planByClosed'] ) {
		$res = $db -> getAll( "SELECT * FROM {$sqlname}credit WHERE do = 'on' $sub AND identity = '$identity' ORDER BY did" );
	}
	//$res = $db -> getAll("SELECT * FROM {$sqlname}credit WHERE do = 'on' and did IN (SELECT did FROM {$sqlname}dogovor WHERE did > 0 ".$sub." and identity = '$identity') and identity = '$identity' ORDER by did");

	// по оплаченным счетам в закрытых сделках
	if ( $otherSettings[ 'planByClosed'] ) {
		$res = $db -> getAll( "SELECT * FROM {$sqlname}credit WHERE do = 'on' $sub and (SELECT did FROM {$sqlname}dogovor WHERE did = {$sqlname}credit.did AND close = 'yes' AND identity = '$identity') > 0 AND identity = '$identity' ORDER BY did" );
	}
	//$res = $db -> getAll("SELECT * FROM {$sqlname}credit WHERE do = 'on' and did IN (SELECT did FROM {$sqlname}dogovor WHERE did > 0 $sub and close = 'yes' and identity = '$identity') and identity = '$identity' ORDER by did");

	$kolall = $margaall = 0;

	foreach ( $res as $da ) {

		//расчет процента размера платежа от суммы сделки
		$res4     = $db -> getRow( "SELECT kol, marga FROM {$sqlname}dogovor WHERE did = '$da[did]' and identity = '$identity'" );
		$kolall   += pre_format( $res4["kol"] );//сумма всей сделки
		$margaall += pre_format( $res4["marga"] );//сумма всей сделки

	}

	if ( $kplan[ $data['iduser'] ] > 0 || $mplan[ $data['iduser'] ] > 0 ) {

		$dataa[ $data['iduser'] ] = [
			"iduser"   => $data['iduser'],
			"avatar"   => $avatarr[ $data['iduser'] ],
			"kol"      => $kfact[ $data['iduser'] ],
			"kperc"    => round( $kperc[ $data['iduser'] ], 2 ),
			"marga"    => $mfact[ $data['iduser'] ],
			"mperc"    => round( $mperc[ $data['iduser'] ], 2 ),
			"kolall"   => $kolall,
			"margaall" => $margaall
		];

	}

}

function cmp($a, $b): bool {

	$pdrInterval = $_COOKIE['pdrInterval'];

	if ( $pdrInterval == 'desc' )
		return $b['mperc'] + $b['kperc'] < $a['mperc'] + $a['kperc'];
	else return $b['mperc'] + $b['kperc'] > $a['mperc'] + $a['kperc'];

}

usort( $dataa, 'cmp' );
?>
<STYLE type="text/css">
	<!--
	#userRaiting .raiting {
		width              : 170px;
		/*height:280px;*/
		display            : inline-block;
		padding            : 10px;
		border             : 0 dotted #ddd;
		box-sizing         : border-box;
		-moz-box-sizing    : border-box;
		-webkit-box-sizing : border-box;
	}

	#userRaiting .raiting:hover {
		-moz-box-shadow    : 0 0 5px #999;
		-webkit-box-shadow : 0 0 5px #999;
		box-shadow         : 0 0 5px #999;
	}

	#userRaiting .progressbar-completed,
	#userRaiting .status {
		height     : 0.4em;
		box-sizing : border-box;
	}

	#userRaiting .progressbar-completed div {
		display : inline;
	}

	#userRaiting .avatarbig {
		width                 : 40px;
		height                : 40px;
		margin                : 0 auto;
		border                : 2px solid #E74B3B;
		border-radius         : 200px;
		-webkit-border-radius : 200px;
		-moz-border-radius    : 200px;
	}

	#userRaiting .avatarbig--inner {
		width                 : 38px;
		height                : 38px;
		margin                : 0 auto;
		border                : 2px solid #FFF;
		padding-top           : -2px;
		border-radius         : 200px;
		-webkit-border-radius : 200px;
		-moz-border-radius    : 200px;
	}

	#userRaiting .avatar--mini {
		width                 : 30px;
		height                : 30px;
		border                : 2px solid #CCC;
		border-radius         : 200px;
		-webkit-border-radius : 200px;
		-moz-border-radius    : 200px;
	}

	#userRaiting .candidate {
		border : 2px solid #349C5A;
	}

	#userRaiting .loozer {
		border : 2px solid #DDD;
	}

	#userRaiting .raiting-mini {
		width              : 190px !important;
		display            : inline-block;
		padding            : 5px;
		border             : 0 dotted #ddd;
		box-sizing         : border-box;
		-moz-box-sizing    : border-box;
		-webkit-box-sizing : border-box;
	}

	#userRaiting .dwinner {
		border-top    : 5px dotted rgba(231, 75, 59, 0.3);
		margin-bottom : 5px;
	}

	#userRaiting .dcandidate {
		border-top    : 5px dotted rgba(21, 157, 130, 0.3);
		margin-bottom : 5px;
	}

	#userRaiting .dloozer {
		border-top    : 5px dotted #DDD;
		margin-bottom : 5px;
	}

	#userRaiting .progressbar {
		width                 : 100%;
		border                : #CCC 0 dotted;
		-moz-border-radius    : 1px;
		-webkit-border-radius : 1px;
		border-radius         : 1px;
		background            : rgba(250, 250, 250, 1);
		position              : relative;
	}

	#userRaiting .progressbar-completed {
		height       : 2.0em;
		line-height  : 2.0em;
		margin-left  : 0;
		padding-left : 0;
	}

	#userRaiting .progressbar-text {
		position : absolute;
		right    : 10px;
		top      : 5px;
	}

	#userRaiting .progressbar-head {
		position : absolute;
		left     : 10px;
		top      : 5px;
	}

	#userRaiting .progress-gray2 {
		background-image : -webkit-gradient(linear, 0.00% 50.00%, 100.00% 50.00%, color-stop(0%, rgba(207, 216, 220, 1)), color-stop(91.71%, rgba(207, 216, 220, 1.00)));
		background-image : -webkit-linear-gradient(0deg, rgba(207, 216, 220, 1.00) 0%, rgba(207, 216, 220, 1.00) 91.71%);
		background-image : linear-gradient(90deg, rgba(207, 216, 220, 1.00) 0%, rgba(207, 216, 220, 1.00) 91.71%);
	}

	#userRaiting .progress-green {
		background-image : -webkit-gradient(linear, 0.00% 50.00%, 100.00% 50.00%, color-stop(0%, rgba(0, 150, 136, 1)), color-stop(100%, rgba(0, 150, 136, 1.00)));
		background-image : -webkit-linear-gradient(0deg, rgba(0, 150, 136, 1) 0%, rgba(0, 150, 136, 1.00) 100%);
		background-image : linear-gradient(90deg, rgba(0, 150, 136, 1) 0%, rgba(0, 150, 136, 1.00) 100%);
	}

	#userRaiting .progress-green2 {
		background-image : -webkit-gradient(linear, 0.00% 50.00%, 100.00% 50.00%, color-stop(0%, rgba(26, 188, 156, 1)), color-stop(100%, rgba(26, 188, 156, 1.00)));
		background-image : -webkit-linear-gradient(0deg, rgba(26, 188, 156, 1) 0%, rgba(26, 188, 156, 1.00) 100%);
		background-image : linear-gradient(90deg, rgba(26, 188, 156, 1) 0%, rgba(26, 188, 156, 1.00) 100%);
	}

	#userRaiting .progress-blue {
		background-image : -webkit-gradient(linear, 0.00% 50.00%, 100.00% 50.00%, color-stop(0%, rgba(33, 150, 243, 1)), color-stop(100%, rgba(33, 150, 243, 1.00)));
		background-image : -webkit-linear-gradient(0deg, rgba(33, 150, 243, 1) 0%, rgba(33, 150, 243, 1.00) 100%);
		background-image : linear-gradient(90deg, rgba(33, 150, 243, 1) 0%, rgba(33, 150, 243, 1.00) 100%);
	}

	#userRaiting .progress-blue2 {
		background-image : -webkit-gradient(linear, 0.00% 50.00%, 100.00% 50.00%, color-stop(0%, rgba(100, 181, 246, 1)), color-stop(100%, rgba(100, 181, 246, 1.00)));
		background-image : -webkit-linear-gradient(0deg, rgba(100, 181, 246, 1) 0%, rgba(100, 181, 246, 1.00) 100%);
		background-image : linear-gradient(90deg, rgba(100, 181, 246, 1) 0%, rgba(100, 181, 246, 1.00) 100%);
	}

	#userRaiting .graybg22 {
		background : rgba(245, 245, 245, 1);
	}

	#userRaiting .ryear, #userRaiting .rmon {
		border     : 1px solid rgba(207, 216, 220, 1) !important;
		background : rgba(207, 216, 220, .3) !important;
	}

	#userRaiting .ryear.active,
	#userRaiting .rmon.active {
		border     : 1px solid rgba(231, 75, 59, 1.3) !important;
		background : rgba(231, 75, 59, 0.3) !important;
	}

	-->
</STYLE>

<div class="data mt5" id="userRaiting">

	<div class="flex-container float mt10 mb20 wp100 gray2 hidden">

		<div class="flex-string wp10"></div>
		<div class="flex-string wp70 cherta pb5">

			<div class="wp100 m0">
				За текущий период
			</div>

		</div>
		<div class="flex-string wp20 text-right cherta pb5">

			<div class="wp100 m0">
				За всё время
			</div>

		</div>

	</div>

	<?php
	//print_r($dataa);
	foreach ( $dataa as $iduser => $data ) {

		$p  = $data['kperc'] + $data['mperc'];
		$wk = $data['kperc'];
		$wm = $data['mperc'];
		$pk = 'progress-gray';
		$pm = 'progress-gray2';
		$a  = '';

		if ( $data['kperc'] > 100 ) {

			$pk = 'progress-green';
			$wk = '100%';

		}
		elseif ( is_between( $data['kperc'], 70, 100 ) ) {

			$pk = 'progress-blue';

		}

		if ( $data['mperc'] > 100 ) {

			$pm = 'progress-green2';
			$wm = '100%';

		}
		elseif ( is_between( $data['mperc'], 70, 100 ) ) {

			$pm = 'progress-blue2';

		}

		if ( is_between( $p, 0, 70 ) )
			$a = 'loozer';
		elseif ( $p >= 140 )
			$a = 'candidate';

		print '
		<div class="ha p5 mb5 hand" onClick="doLoad(\'reports/ent-userRaiting.php?action=planView&iduser='.$data['iduser'].'&mon='.$mon.'&year='.$year.'\')">
		
			<div class="flex-container float wp100 mb5">
		
				<div class="flex-string w60 uppercase Bold hidden-iphone"></div>
				<div class="flex-string float uppercase Bold gray2">
					'.current_user( $data['iduser'], "yes" ).'
				</div>
			
			</div>
			<div class="flex-container float">
			
				<div class="flex-string w60 hidden-iphone">
					<div class="avatarbig '.$a.'" style="background: url('.$data['avatar'].'); background-size:cover;" title="'.current_user( $data['iduser'], 'yes' ).'"></div>
				</div>
				<div class="flex-string float">
				
					<div class="wp100 m0" title="Оборот">
						<DIV class="progressbar wp100 graybg22">
							<div class="progressbar-text '.($data['kperc'] > 90 ? 'white' : '').'">'.$data['kperc'].'%</div>
							<div class="progressbar-head '.($data['kperc'] > 70 ? 'white' : '').'"><b>'.num_format( $data['kol'] ).'</b> из '.num_format( $kplan[ $data['iduser'] ] ).'</div>
							<DIV id="test" class="progressbar-completed '.$pk.'" style="width:'.$wk.'%;"></DIV>
						</DIV>
					</div>
					<div class="wp100 m0" title="Маржа">
						<DIV class="progressbar wp100">
							<div class="progressbar-text '.($data['mperc'] > 90 ? 'white' : '').'">'.$data['mperc'].'%</div>
							<div class="progressbar-head '.($data['mperc'] > 70 ? 'white' : '').'"><b>'.num_format( $data['marga'] ).'</b> из '.num_format( $mplan[ $data['iduser'] ] ).'</div>
							<DIV id="test" class="progressbar-completed '.$pm.'" style="width:'.$wm.'%;"></DIV>
						</DIV>
					</div>
					
				</div>
				<div class="flex-string w100 text-right">
				
					<div class="wp100 m0 progressbar bluebg-sub" title="Оборот за всё время">
						<div class="progressbar-completed pr5">'.num_format( $data['kolall'] ).'</div>
					</div>
					<div class="wp100 m0 progressbar bluebg-sub" title="Маржа за всё время">
						<div class="progressbar-completed pr5">'.num_format( $data['margaall'] ).'</div>
					</div>
					
				</div>
				
			</div>
		</div>
		';

	}
	?>

</div>

<script>

	function changeDoRaiting() {

		var current = getCookie('pdrInterval');
		var newparam = (current === 'desc') ? 'acs' : 'desc';

		setCookie('pdrInterval', newparam, {expires: 31536000});

		$("#plando").load("/content/vigets/viget.planDoRaiting.php").append('<div id="loader"><img src="/assets/images/loading.svg"></div>');

	}

</script>
