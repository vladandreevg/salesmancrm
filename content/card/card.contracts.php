<?php
/* ============================ */
/*         SalesMan CRM         */
/* ============================ */
/* (C) 2016 Vladislav Andreev   */
/*       SalesMan Project       */
/*        www.isaler.ru         */
/*        ver. 2019.x           */
/* ============================ */

use Salesman\Akt;
use Salesman\Document;

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

$clid    = (int)$_REQUEST['clid'];
$pid     = (int)$_REQUEST['pid'];
$did     = (int)$_REQUEST['did'];
$docSort = untag($_COOKIE['docSort']);

//восстановим поля формы
$result = $db -> query( "select * from {$sqlname}field where fld_tip='dogovor' and fld_on='yes' and identity = '$identity'" );
while ($data = $db -> fetch( $result )) {

	$fields[]                   = $data['fld_name'];
	$fName[ $data['fld_name'] ] = $data['fld_title'];

}

if ( $action == "" ) {

	//todo: перевести на шаблон + метод Salesman\Document ::getDocuments()

	$docs = Document ::getDocuments( [
		"did"          => $did,
		"clid"         => $clid,
		"sort"         => $docSort
	] );

	$docs['isMobile'] = $isMobile ? true : NULL;
	$docs['Language'] = $lang;
	$docs['isCardDeal'] = $did > 0 ? true : NULL;
	$docs['isCardClient'] = $did < 1 && $clid > 0 ? true : NULL;

	//print_r($docs);

	$ssort = ($docSort == 'desc') ? '' : 'desc';
	$icon  = ($docSort == 'desc') ? 'icon-sort-alt-down' : 'icon-sort-alt-up';
	?>
	<div class="inline pull-left Bold" style="position:absolute; top:-30px">

		<a href="javascript:void(0)" onclick="setCookie('docSort', '<?= $ssort ?>', {expires:31536000}); settab('15')" class="gray" title="Изменить сортировку"><i class="<?= $icon ?> broun"></i> Сортировка</a>

	</div>
	<?php
	$complect = Akt ::getAktComplect( $did );

	if ( !is_null($complect) && $complect > 0 ) {
		print '<div class="infodiv mb5">Актами закрыто <b>'.$complect.'%</b> позиций спецификации</div>';
	}

	if ( !empty( $docs['list'] ) ) {

		$html = file_get_contents( $rootpath.'/content/tpl/card.contracts.mustache' );

		Mustache_Autoloader ::register();
		$m = new Mustache_Engine();

		print $html = $m -> render( $html, $docs );

	}

	if ( empty( $docs['list'] ) ) {
		print '<div class="fcontainer mp10">Документы отсутствуют</div>';
	}

}

if ( $action == "old" ) {

	//todo: перевести на шаблон + метод Salesman\Document ::getDocuments()

	$akt_num = '';
	$html    = [];

	$temps = [
		'akt_simple.htm' => 'Акт приема-передачи. Услуги',
		'akt_full.htm'   => 'Акт приема-передачи (расширенный). Услуги',
		'akt_prava.htm'  => 'Акт приема-передачи. Права'
	];

	$typeAkt       = $isAkt = [];
	$typeAktPeriod = [];

	$typeList = [];

	//типы документов
	$result = $db -> query( "SELECT * FROM {$sqlname}contract_type WHERE identity = '$identity'" );
	while ($data = $db -> fetch( $result )) {

		if ( $data['type'] == 'get_dogovor' ) {
			$typeDogovor[] = (int)$data['id'];
		}

		if ( $data['type'] == 'get_aktper' ) {
			$typeAktPeriod[] = (int)$data['id'];
		}

		if ( $data['type'] == 'get_akt' ) {
			$typeAkt[] = (int)$data['id'];
		}

		$typeList[ (int)$data['id'] ] = $data['title'];

	}

	$isAkt       = array_merge( $typeAkt, $typeAktPeriod );
	$isAktString = (count( $isAkt ) > 0) ? implode( ",", $isAkt ) : '';

	//print count($isAkt);

	$typeDogovor   = yimplode( ",", $typeDogovor );      //договоры
	$typeAktPeriod = yimplode( ",", $typeAktPeriod );  //акты ежемесячные
	$typeAkt       = yimplode( ",", $typeAkt );              //акты приема-передачи

	//составим запрос для вывода документов для текущей записи
	if ( $clid > 0 ) {

		$query = "select * from {$sqlname}contract WHERE (clid = '$clid' or payer = '$clid') and identity = '$identity'";
		$payer = getDogData( $did, "payer" );

	}
	if ( $pid > 0 ) {
		$query = "select * from {$sqlname}contract WHERE pid='$pid' and identity = '$identity'";
	}

	if ( $did > 0 ) {

		$resultt = $db -> getRow( "SELECT * FROM {$sqlname}dogovor WHERE did='$did' and identity = '$identity'" );
		$deid    = $resultt["dog_num"];
		$clid    = (int)$resultt["clid"];
		$payer   = (int)$resultt["payer"];
		$pid     = (int)$resultt["pid"];
		$close   = $resultt["close"];

		$query = "select * from {$sqlname}contract WHERE deid='$deid' or did = '$did' and identity = '$identity'";

	}

	//Проверим реквизит для счета, если включено выставление счетов
	if ( !$payer ) {
		$payer = $clid;
	}

	$recvz = get_client_recv( $payer, 'yes' );

	//Проверим, если ли у клиента договор и предложим привязать его к сделке
	$dcount = ($typeDogovor != '') ? (int)$db -> getOne( "SELECT COUNT(*) as count FROM {$sqlname}contract WHERE clid = '$clid' and idtype IN ($typeDogovor) and identity = '$identity'" ) : 0;

	$deid = (int)$db -> getOne( "SELECT deid FROM {$sqlname}contract WHERE did = '$did' and COALESCE((SELECT COUNT(*) FROM {$sqlname}contract_type WHERE {$sqlname}contract_type.type = 'get_dogovor' AND {$sqlname}contract_type.id = {$sqlname}contract.idtype), 0) > 0 and identity = '$identity'" ) + 0;

	// старая реализация, поэтому сейчас не работает
	if ( $dcount > 0 && $deid < 1 && $did > 0 ) {

		print '
		<div class="attention m0 mb10">
			<b>У клиента есть договор.</b> Вы можете привязать его к сделке: 
			<a href="javascript:void(0)" onclick="editDogovor(\''.$did.'\',\'append.contract\');" class="button bluebtn dotted m0 ml10" title="Привязать договор">Привязать</a>
		</div>
		';

	}

	//вывод актов по новой схеме

	//сервисные акты

	$akp = 0;

	if ( $isAktString != '' ) {

		if ( $did == 0 ) {
			$apx = "or (clid = '$clid' or payer = '$clid')";
		}

		$result = $db -> query( "SELECT * from {$sqlname}contract WHERE (did = '$did' $apx) and idtype IN ($isAktString) and identity = '$identity'" );
		while ($data = $db -> fetch( $result )) {

			if ( isServices( (int)$data['did'] ) ) {
				$isper = 'yes';
			}

			if ( $isper != 'yes' ) {
				$app = 'приёма-передачи';
			}
			if ( $isper == 'yes' ) {
				$app = '(Ежемесячный)';
			}

			$close = $db -> query( "SELECT close FROM {$sqlname}dogovor WHERE did='$data[did]' and identity = '$identity'" );

			$dealIcon = ($close == 'yes') ? "icon-lock red" : "icon-briefcase blue";

			$status = $db -> getRow( "SELECT color, title FROM {$sqlname}contract_status WHERE id = '$data[status]' and identity = '$identity'" );

			//статусы, применимые к текущему типу документоа
			$statuses = $db -> getAll( "SELECT * FROM {$sqlname}contract_status WHERE FIND_IN_SET('$data[idtype]', REPLACE({$sqlname}contract_status.tip, ';',',')) > 0 AND identity = '$identity' ORDER by ord" );

			if ( $status['title'] == '' ) {
				$status['title'] = '<span class="red">Не установлен</span>';
				$status['color'] = '#ccc';
			}

			$panel = (get_accesse($clid, $pid, $did) == "yes" && !$isMobile) ? '
			<DIV class="panel">

				<a href="javascript:void(0)" onclick="editAkt(\'edit\',\''.$data['deid'].'\',\'\',\''.$data['did'].'\')" title="'.$lang['all']['Edit'].'"><i class="icon-pencil blue"></i></a>&nbsp;
				<a href="javascript:void(0)" onclick="editAkt(\'mail\',\''.$data['deid'].'\',\'\',\''.$data['did'].'\')" title="Отправить Акт по Email"><i class="icon-mail-alt broun"></i></a>&nbsp;
				<a href="javascript:void(0)" onclick="editAkt(\'print\',\''.$data['deid'].'\',\'\',\''.$data['did'].'\')" title="На печать"><i class="icon-print green"></i></a>&nbsp;
				'.($isadmin == 'on' || $close != "yes" ? '<a href="javascript:void(0)" onclick="cf=confirm(\'Вы действительно хотите удалить Документ ? \'); if (cf)editAkt(\'delete\',\''.$data['deid'].'\',\'\',\''.$data['did'].'\')" title="'.$lang['all']['Delete'].'"><i class="icon-cancel-circled red"></i></a>&nbsp;' : '<a href="javascript:void(0)" class="gray2 list" title="Удалить может только администратор"><i class="icon-cancel-circled gray"></i></a>').'

			</DIV>
			' : '';

			$panelMob = (get_accesse($clid, $pid, $did) == "yes" && $isMobile) ? '
			<div class="flex-container1 mb10 mt10 ptb5 box--child">

				<div class="wp100 mob-pull-right mb10">

					<a href="javascript:void(0)" onclick="editAkt(\'edit\',\''.$data['deid'].'\',\'\',\''.$data['did'].'\')" title="'.$lang['all']['Edit'].'"><i class="icon-pencil blue"></i></a>&nbsp;
					<a href="javascript:void(0)" onclick="editAkt(\'mail\',\''.$data['deid'].'\',\'\',\''.$data['did'].'\')" title="Отправить Акт по Email"><i class="icon-mail-alt broun"></i></a>&nbsp;
					<a href="javascript:void(0)" onclick="editAkt(\'print\',\''.$data['deid'].'\',\'\',\''.$data['did'].'\')" title="На печать"><i class="icon-print green"></i></a>&nbsp;
					'.($isadmin == 'on' || $close != "yes" ? '<a href="javascript:void(0)" onclick="cf=confirm(\'Вы действительно хотите удалить Документ?\'); if (cf)editAkt(\'delete\',\''.$data['deid'].'\',\'\',\''.$data['did'].'\')" title="'.$lang['all']['Delete'].'"><i class="icon-cancel-circled red"></i></a>&nbsp;' : '<a href="javascript:void(0)" class="gray2 list" title="Удалить может только администратор"><i class="icon-cancel-circled gray"></i></a>').'

				</div>

			</div>' : '';

			$statuslog = '';
			if ( !empty( $statuses ) ) {

				$re = $db -> getAll( "
					SELECT 
						DATE_FORMAT({$sqlname}contract_statuslog.datum, '%d.%m.%Y %H:%s') as datum,
						{$sqlname}contract_statuslog.des as des,
						{$sqlname}contract_status.title as status,
						{$sqlname}contract_status.color as color
					FROM {$sqlname}contract_statuslog 
						LEFT JOIN {$sqlname}contract_status ON {$sqlname}contract_status.id = {$sqlname}contract_statuslog.status
					WHERE 
						{$sqlname}contract_statuslog.deid = '$data[deid]' and 
						{$sqlname}contract_statuslog.identity = '$identity' 
					ORDER BY {$sqlname}contract_statuslog.datum DESC
				" );

				if ( !empty( $re ) ) {

					foreach ( $re as $stat ) {

						$statuslog .= '
						<div class="flex-container border-bottom fs-09 mt5">
							<div class="flex-string wp25">'.$stat['datum'].'</div>
							<div class="flex-string wp75">
								<div class="Bold fs-11 ellipsis"><div class="colordiv" style="background-color:'.$stat['color'].'"></div>&nbsp;'.$stat['status'].'</div>
								<div class="gray2 fs-09 em">'.$stat['des'].'</div>
							</div>
						</div>
						';

					}

					if ( $statuslog != '' ) {

						$statuslog = '<div class="tagsmenuToggler hand mr15 pull-aright relativ" data-id="fhelper">
							<span class="fs-10 blue"><i class="icon-help-circled"></i> Лог</span>
							<div class="tagsmenu fly right hidden" id="fhelper" style="right:0; top: 100%">
								<div class="blok p10 w350">'.$statuslog.'</div>
							</div>
						</div>';

					}

				}

			}

			if ( $isper == 'yes' ) {
				$ainvoice = $db -> getRow("select * from {$sqlname}credit WHERE crid = '$data[crid]' and identity = '$identity'");
			}

			$template = Akt ::getTemplates( NULL, $data['title'] );

			//print_r($template);

			$aktComplect = Akt ::getComplect( (int)$data['deid'] );

			//print
			$html[ diffDate2( get_smdate( $data['datum'] ) ) ][] = '
			
			<DIV class="container1 mb5 focused">

				<div class="fcontainer p0 pt10 border--bottom">

					'.$panel.'

					<div class="fs-12 mb20 Bold gray2 mp10 ptb5lr15">

						<b><span class="uppercase blue">Акт '.$app.'</span> № '.$data['number'].'</b>

					</div>

					<div class="flex-container mb10 mt20 box--child">

						<div class="flex-string wp20 ptb5lr15 gray2">'.$lang['all']['Created'].':</div>
						<div class="flex-string wp80 ptb5lr15 relativ">'.format_date_rus_name( get_smdate( $data['datum'] ) ).'&nbsp;года</div>

					</div>

					'.(!empty( $statuses ) ? '
					<div class="flex-container mb10 mt10 box--child">

						<div class="flex-string wp20 ptb5lr15 gray2">Статус документа:</div>
						<div class="flex-string wp80 ptb5lr15">

							<div class="colordiv" style="background-color:'.$status['color'].'"></div>&nbsp;'.$status['title'].'
							<a href="javascript:void(0)" onclick="editContract(\''.$data['deid'].'\',\'contract.status\')" class="pull-aright gray">'.$lang['all']['Edit'].'</a>

							'.$statuslog.'

						</div>

					</div>' : '').'

					'.((int)$data['did'] > 0 && $did == 0 ? '
					<div class="flex-container mb10 mt10 box--child">

						<div class="flex-string wp20 ptb5lr15 gray2">Сделка:</div>
						<div class="flex-string wp80 ptb5lr15 relativ">
							<a href="javascript:void(0)" onclick="viewDogovor(\''.$data['did'].'\')" title="Просмотр"><i class="'.$dealIcon.'"></i>&nbsp;'.current_dogovor( $data['did'] ).'</a>
						</div>

					</div>' : '').'
					

					'.($isper != 'yes' ? '<div class="flex-container mb10 mt10 box--child">

						<div class="flex-string wp20 ptb5lr15 gray2">Комплектность:</div>
						<div class="flex-string wp80 ptb5lr15 relativ Bold">
							'.round( $aktComplect + 0.1, 0 ).'%
						</div>

					</div>' : '').'
					

					'.($isper != 'yes' ? '<div class="flex-container mb10 mt10 box--child">

						<div class="flex-string wp20 ptb5lr15 gray2">Шаблон:</div>
						<div class="flex-string wp80 ptb5lr15 relativ Bold">
							'.$template['title'].'
						</div>

					</div>' : '').'
			
					'.($isper == 'yes' ? '<div class="flex-container mb10 mt10 box--child">

						<div class="flex-string wp20 ptb5lr15 gray2">К счету:</div>
						<div class="flex-string wp80 ptb5lr15 relativ">
							№ <b>'.$ainvoice['invoice'].'</b>&nbsp;от<b>'.format_date_rus_name( get_smdate( $ainvoice['datum'] ) ).'</b>
						</div>

					</div>' : '').'
					

					'.$panelMob.'

				</div>

			</DIV>
			';

			$akp++;

		}

	}

	//обычные документы

	if ( $did > 0 && $deid != '' ) {
		$dd = " and (did = '$did' or deid = '$deid')";
	}
	elseif ( $did > 0 && $deid == '' ) {
		$dd = " and did = '$did'";
	}

	if ( $payer > 0 ) {
		$pd = " or payer = '$payer'";
	}

	if ( $typeAktPeriod != '' && $typeAkt != '' ) {
		$typeAkt .= ",".$typeAktPeriod;
	}
	elseif ( $typeAktPeriod != '' && $typeAkt == '' ) {
		$typeAkt .= $typeAktPeriod;
	}

	if ( $typeAkt != '' ) {
		$qq = 'and idtype NOT IN ('.$typeAkt.')';
	}

	$result = $db -> query( "SELECT * FROM {$sqlname}contract WHERE clid = '$clid' $dd $qq and identity = '$identity'" );
	while ($da = $db -> fetch( $result )) {

		$payerr = '';
		$color = '';

		if ( $payer > 0 ) {
			$payerr = '<a href="javascript:void(0)" onclick="openClient(\''.$payer.'\')" title="Плательщик"><b class=blue">'.current_client($payer).'</b></a>';
		}

		$close = $db -> getOne( "SELECT close FROM {$sqlname}dogovor WHERE did='$da[did]' and identity = '$identity'" );

		//статусы, применимые к текущему типу документоа
		$statuses = $db -> getAll( "SELECT * FROM {$sqlname}contract_status WHERE FIND_IN_SET('$da[idtype]', REPLACE({$sqlname}contract_status.tip, ';',',')) > 0 AND identity = '$identity' ORDER by ord" );

		$status = $db -> getRow( "SELECT color, title FROM {$sqlname}contract_status WHERE id = '$da[status]' and identity = '$identity'" );

		if ( $status['title'] == '' ) {

			$status['title'] = '<span class="red">Не установлен</span>';
			$status['color'] = '#ccc';

		}

		$dealIcon = ($close == 'yes') ? "icon-lock red" : "icon-briefcase blue";

		$panel = (get_accesse($clid, $pid, $did) == "yes" && !$isMobile) ? '
		<DIV class="panel">

			<a href="javascript:void(0)" onclick="editContract(\''.$da['deid'].'\',\'contract.edit\');" title="'.$lang['all']['Edit'].'"><i class="icon-pencil blue"></i></a>&nbsp;
			'.($isadmin == 'on' || $close != "yes" ? '<a href="javascript:void(0)" onclick="cf=confirm(\'Удалить Документ?\'); if (cf)editContract(\''.$da['deid'].'\',\'contract.delete\');" title="'.$lang['all']['Delete'].'"><i class="icon-cancel-circled red"></i></a>&nbsp;' : '<a href="javascript:void(0)" class="gray2 list" title="Удалить может только администратор"><i class="icon-cancel-circled gray"></i></a>&nbsp;').'

		</DIV>' : '';

		$panelMob = (get_accesse($clid, $pid, $did) == "yes" && $isMobile) ? '
		<div class="flex-container mb10 mt10 ptb5">

			<div class="wp100 mob-pull-right mb10">

				<a href="javascript:void(0)" onclick="editContract(\''.$da['deid'].'\',\'contract.edit\');" title="'.$lang['all']['Edit'].'"><i class="icon-pencil blue"></i></a>&nbsp;
				'.($isadmin == 'on' || $close != "yes" ? '<a href="javascript:void(0)" onclick="cf=confirm(\'Удалить Документ?\'); if (cf)editContract(\''.$da['deid'].'\',\'contract.delete\');" title="'.$lang['all']['Delete'].'"><i class="icon-cancel-circled red"></i></a>&nbsp;' : '<a href="javascript:void(0)" class="gray2 list" title="Удалить может только администратор"><i class="icon-cancel-circled gray"></i></a>&nbsp;').'

			</div>

		</div>' : '';

		$statuslog = '';
		if ( !empty( $statuses ) ) {

			$re = $db -> getAll( "
				SELECT 
					DATE_FORMAT({$sqlname}contract_statuslog.datum, '%d.%m.%Y %H:%s') as datum,
					{$sqlname}contract_statuslog.des as des,
					{$sqlname}contract_status.title as status,
					{$sqlname}contract_status.color as color
				FROM {$sqlname}contract_statuslog 
					LEFT JOIN {$sqlname}contract_status ON {$sqlname}contract_status.id = {$sqlname}contract_statuslog.status
				WHERE 
					{$sqlname}contract_statuslog.deid = '$da[deid]' and 
					{$sqlname}contract_statuslog.identity = '$identity' 
				ORDER BY {$sqlname}contract_statuslog.datum DESC
			" );

			if ( !empty( $re ) ) {

				foreach ( $re as $stat ) {

					$statuslog .= '
					<div class="flex-container border-bottom fs-09 mt5">
						<div class="flex-string wp25">'.$stat['datum'].'</div>
						<div class="flex-string wp75">
							<div class="Bold fs-11 ellipsis"><div class="colordiv" style="background-color:'.$stat['color'].'"></div>&nbsp;'.$stat['status'].'</div>
							<div class="gray2 fs-09 em">'.$stat['des'].'</div>
						</div>
					</div>
					';

				}

				if ( $statuslog != '' ) {

					$statuslog = '
					<div class="tagsmenuToggler hand mr15 pull-aright relativ" data-id="fhelper">
						<span class="fs-10 blue"><i class="icon-help-circled"></i> Лог</span>
						<div class="tagsmenu fly right hidden" id="fhelper" style="right:0; top: 100%">
							<div class="blok p10 w350">'.$statuslog.'</div>
						</div>
					</div>';

				}

			}

		}

		//print $statuslog;

		$ftitle = explode( ";", $da['ftitle'] );
		$fname  = explode( ";", $da['fname'] );
		$last   = count( $ftitle ) - 1;
		$files  = '';

		if ( $da['ftitle'] != '' ) {

			foreach ($ftitle as $i => $xftitle) {

				$a = '';

				if ( $xftitle != '' ) {

					$file = str_replace( ".".getExtention( $fname[ $i ] ), "", $fname[ $i ] );

					if ( isViewable( $fname[ $i ] ) ) {
						$a = '&nbsp;[ <A href="javascript:void(0)" onclick="fileDownload(\'\',\''.$fname[$i].'\',\'\',\''.$xftitle.'\')" title="Просмотр"><i class="icon-eye broun"></i></A> ]&nbsp;';
					}

					if ( in_array( getExtention( $fname[ $i ] ), [
							"docx",
							"doc",
							"rtf",
							"xlsx",
							"pptx",
							"ppt"
						] ) && !file_exists( $rootpath."/files/".$fpath.$file.'.pdf' ) ) {

						$a .= '&nbsp;[ <A href="javascript:void(0)" onclick="doc2PDF(\'\',\''.$fname[$i].'\',\'\',\''.$xftitle.'\',\''.$da['deid'].'\')" title="Преобразовать в PDF"><i class="icon-file-pdf red"></i>в PDF</A> ]&nbsp;';

					}

					$files .= '<div class="pb5">&nbsp;<A href="javascript:void(0)" onclick="fileDownload(\'\',\''.$fname[ $i ].'\',\'yes\',\''.$xftitle.'\')" title="Скачать">'.get_icon2( $xftitle ).'&nbsp;<B>'.$xftitle.'</B></A>&nbsp;[ '.num_format( filesize( $rootpath."/files/".$fpath.$fname[ $i ] ) / 1000 ).' kb. ]'.$a.'</div>';

				}

			}

		}

		if ( $files == '' ) {
			$files = 'Нет файлов';
		}

		$hpayer = (!in_array( 'payer', $fields )) ? 'hidden' : '';

		$day = diffDate2( $da['datum_end'] );

		if ( is_between( $day, 0, 7 ) ) {
			$color = 'orangebg-sub';
		}
		elseif ( is_between( $day, 0, 30 ) ) {
			$color = 'bluebg-sub';
		}
		elseif ( is_between( $day, -14, 0 ) ) {
			$color = 'yellowbg-sub';
		}
		elseif ( is_between( $day, -30, -14 ) ) {
			$color = 'redbg-sub';
		}
		elseif ( $day < -30 ) {
			$color = 'graybg gray2';
		}

		//print
		$html[ diffDate2( $da['datum_start'] ) ][] = '
		<DIV class="container1 mb5 focused p5 '.(diffDate2( $da['datum_end'] ) < 0 ? $color : '').'">

			<div class="fcontainer p0 m0 pt10">

				'.$panel.'

				<div class="fs-12 mb20 gray2 ptb5lr15 mp10">

					<div class="Bold"><span class="uppercase blue">'.$da['title'].'</span> № '.$da['number'].'</div>
					<div class="gray2 fs-07 mt5">Тип: '.$typeList[ $da['idtype'] ].'</div>

				</div>

				<DIV class="box--child border--bottom mt10">

					<div class="flex-container mb10 mt10 ptb5">

						<div class="flex-string wp20 ptb5lr15 gray2">'.$lang['all']['Created'].':</div>
						<div class="flex-string wp80 ptb5lr15 relativ">
							'.format_date_rus_name( $da['datum_start'] ).'&nbsp;года
						</div>

					</div>

					'.(!empty( $statuses ) ? '
					<div class="flex-container mb10 mt10">

						<div class="flex-string wp20 gray2 ptb5lr15">Статус документа:</div>
						<div class="flex-string wp80 ptb5lr15 relativ">

							<div class="colordiv" style="background-color:'.$status['color'].'"></div>&nbsp;'.$status['title'].'
							<a href="javascript:void(0)" onclick="editContract(\''.$da['deid'].'\',\'contract.status\')" class="pull-aright gray">'.$lang['all']['Edit'].'</a>

							'.$statuslog.'

						</div>

					</div>' : '').'

					'.((int)$da['did'] > 0 ? '
					<div class="flex-container mb10 mt10">

						<div class="flex-string wp20 ptb5lr15 gray2">'.$lang['face']['DealName'][0].':</div>
						<div class="flex-string wp80 ptb5lr15 relativ" style="word-wrap: break-word;">
							<a href="javascript:void(0)" onclick="viewDogovor(\''.$da['did'].'\')" title="Просмотр"><i class="'.$dealIcon.'"></i>&nbsp;'.current_dogovor( $da['did'] ).'</a>
						</div>

					</div>' : '').'

					'.($da['datum_end'] != "0000-00-00" ? '
					<div class="flex-container mb10 mt10">

						<div class="flex-string wp20 ptb5lr15 gray2">Действует до:</div>
						<div class="flex-string wp80 ptb5lr15 relativ '.(diffDate2( $da['datum_end'] ) < 0 ? 'red' : 'green').'" style="word-wrap: break-word;">
							'.format_date_rus_name( $da['datum_end'] ).'&nbsp;года
						</div>

					</div>' : '').'
				
					<div class="flex-container mb10 mt10 '.$hpayer.'">

						<div class="flex-string wp20 ptb5lr15 gray2">'.$fName['payer'].':</div>
						<div class="flex-string wp80 ptb5lr15 relativ" style="word-wrap: break-word;">
							'.$payerr.'
						</div>

					</div>

					'.($da['des'] != '' ? '
					<div class="flex-container mb10 mt10 '.$hpayer.'">

						<div class="flex-string wp20 ptb5lr15 gray2">Описание:</div>
						<div class="flex-string wp80 ptb5lr15 relativ" style="word-wrap: break-word;">
							'.$da['des'].'
						</div>

					</div>' : '').'

					<div class="flex-container mb10 mt10">

						<div class="flex-string wp20 ptb5lr15 gray2">'.$lang['face']['Files'].':</div>
						<div class="flex-string wp80 ptb5lr15 relativ" style="word-wrap: break-word;">
							'.$files.'
						</div>

					</div>

					'.$panelMob.'

				</DIV>

			</div>
			
		</DIV>';

	}

	//сортируем массив документов по давности
	if ( $docSort != 'desc' ) {
		krsort($html);
	}
	else {
		ksort($html);
	}

	$ssort = ($docSort == 'desc') ? '' : 'desc';
	$icon  = ($docSort == 'desc') ? 'icon-sort-alt-down' : 'icon-sort-alt-up';
	?>
	<div class="inline pull-left Bold" style="position:absolute; top:-30px">

		<a href="javascript:void(0)" onclick="setCookie('docSort', '<?= $ssort ?>', {expires:31536000}); settab('15')" class="gray" title="Изменить сортировку"><i class="<?= $icon ?> broun"></i> Сортировка</a>

	</div>
	<?php
	$complect = round( Akt ::getAktComplect( $did ), 0 );

	if ( $complect > 0 ) {
		print '<div class="infodiv mb5">Актами закрыто <b>'.$complect.'%</b> позиций спецификации</div>';
	}

	foreach ( $html as $key => $item ) {

		print yimplode( "", $item );

	}

	if ( empty( $html ) ) {
		print '<div class="fcontainer mp10">Документы отсутствуют</div>';
	}

}

if ( $action == "view" ) {

	$deid = (int)$_GET['deid'];

	$result = $db -> query( "select * from {$sqlname}contract WHERE deid='".$deid."' and identity = '$identity'" );
	while ($da = $db -> fetch( $result )) {

		if ( $da['datum_end'] == "0000-00-00" ) {
			$da['datum_end'] = "__";
		}
		if ( $da['payer'] > 0 ) {
			$payerr = '<a href="/card.client?clid='.$da['payer'].'" target="blank" title="Плательщик"><b class=blue">'.current_client( $da['payer'] ).'</b></a>';
		}
		?>
		<div class="zagolovok"><?= $lang['docs']['Doc'][0] ?> № <?= $da['number'] ?></div>

		<TABLE class="noborder">
			<?php
			if ( $da['title'] ) {
				?>
				<TR height="25">
					<TD width="125" nowrap>
						<DIV class="fnameForm"><?= $lang['all']['Name'] ?>:</DIV>
					</TD>
					<TD><B><?= $da['title'] ?></B></TD>
				</TR>
			<?php } ?>
			<?php if ( $payerr != '' ) { ?>
				<TR height="25">
					<TD nowrap valign="top">
						<DIV class="fnameCold"><?= $lang['all']['Payer'] ?>:</DIV>
					</TD>
					<TD valign="top">
						<div class="fpoleCold"><?= $payerr ?></div>
					</TD>
				</TR>
			<?php } ?>
			<?php if ( (int)$da['clid'] > 0 ) { ?>
				<TR height="25">
					<TD nowrap valign="top">
						<DIV class="fnameCold"><?= $lang['all']['Customer'] ?>:</DIV>
					</TD>
					<TD valign="top">
						<div class="fpoleCold">
							<a href="javascript:void(0)" onclick="openClient('=<?= $da['clid'] ?>')" title="Плательщик"><b class=blue"><?= current_client( $da['clid'] ) ?></b></a>
						</div>
					</TD>
				</TR>
			<?php } ?>
			<TR height="25">
				<TD width="125" nowrap>
					<DIV class="fnameForm">Подписан:&nbsp;</DIV>
				</TD>
				<TD><B><?= format_date_rus_name( $da['datum_start'] ) ?>&nbsp;года</B></TD>
			</TR>
			<?php if ( $da['datum_end'] != "0000-00-00" ) { ?>
				<TR height="25">
					<TD>
						<DIV class="fnameForm">Действует до:</DIV>
					</TD>
					<TD><B><?= format_date_rus_name( $da['datum_end'] ) ?>&nbsp;года</B></TD>
				</TR>
			<?php } ?>
			<?php if ( $da['des'] != "" ) { ?>
				<TR height="25">
					<TD valign="top">
						<DIV class="fnameForm">Описание:</DIV>
					</TD>
					<TD><?= $da['des'] ?></TD>
				</TR>
			<?php } ?>
			<TR height="25">
				<TD nowrap valign="top">
					<DIV class="fnameForm"><?= $lang['face']['Files'] ?>:</DIV>
				</TD>
				<TD>
					<?php
					$ftitle = explode( ";", $da['ftitle'] );
					$fname  = explode( ";", $da['fname'] );
					$last   = count( $ftitle ) - 1;

					foreach ($ftitle as $i => $xftitle) {

						$a = '';

						if ( $xftitle != '' ) {

							if ( isViewable( $fname[ $i ] ) ) {
								$a = '<A href="javascript:void(0)" onclick="fileDownload(\'\',\''.$fname[$i].'\',\'\',\''.$xftitle.'\')" title="Просмотр"><i class="icon-eye broun"></i></A>&nbsp;';
							}

							print get_icon2( $xftitle ).'&nbsp;'.$a.'<A href="javascript:void(0)" onclick="fileDownload(\'\',\''.$fname[ $i ].'\',\'yes\',\''.$xftitle.'\')" title="Скачать"><i class="icon-download blue"></i></A>&nbsp;<B>'.$xftitle.'</B>&nbsp;['.num_format( filesize( $rootpath."/files/".$fpath.$fname[ $i ] ) / 1000 ).' kb.]';

							if ( $i < $last ) {
								print '<br>';
							}

						}

					}
					?>
				</TD>
			</TR>
		</TABLE>
		<br>
		<?php

	}

}