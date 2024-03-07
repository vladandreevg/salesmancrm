<?php
/**
 * @license  http://isaler.ru/
 * @author   Vladislav Andreev, http://iandreyev.ru/
 * @charset  UTF-8
 * @version  6.4
 */
?>
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

$otdel     = (array)$_REQUEST['otdel'];
$user      = (array)$_REQUEST['user'];
$user_list = (array)$_REQUEST['user_list'];

$thisfile = basename( $_SERVER['PHP_SELF'] );

if ( $action == 'get_users' ) {

	$users = [];
	$s     = implode( ',', $otdel );
	if ( $s != '' )
		$s = ' and otdel IN ('.$s.')';

	$result = $db -> query( "SELECT * FROM ".$sqlname."user WHERE iduser > 0 $s and identity = '$identity' ORDER BY title" );
	while ($data = $db -> fetch( $result )) {

		if ( in_array( $data['iduser'], $user ) or count( $user ) == 0 )
			$ch = 'yes';
		else $ch = '';

		$users[] = [
			"iduser" => $data['iduser'],
			"title"  => $data['title'],
			"ch"     => $ch
		];
	}

	print $u = json_encode_cyr( $users );

	exit();
}

$sort = '';

//массив выбранных пользователей
if ( count( $user ) > 0 )
	$sort = " and userchange.newuser IN (".implode( ",", $user ).")";
elseif ( count( $user_list ) > 0 )
	$sort = " and userchange.newuser IN (".implode( ",", $user_list ).")";
else $sort = str_replace( "iduser", "userchange.newuser", get_people( $iduser1 ) );

$q = "
SELECT
	userchange.datum as datum,
	userchange.olduser as olduser,
	userchange.newuser as newuser,
	userchange.comment as comment,
	userchange.clid as clid,
	userchange.did as did,
	".$sqlname."clientcat.title as client,
	".$sqlname."dogovor.title as dogovor,
	".$sqlname."dogovor.uid as uid
FROM userchange
	LEFT JOIN ".$sqlname."user ON userchange.newuser = ".$sqlname."user.iduser
	LEFT JOIN ".$sqlname."clientcat ON userchange.clid = ".$sqlname."clientcat.clid
	LEFT JOIN ".$sqlname."dogovor ON userchange.did = ".$sqlname."dogovor.did
WHERE
	userchange.datum BETWEEN '$da1 00:00:01' and '$da2 23:59:59'
	$sort
	and userchange.identity = '$identity'
ORDER BY userchange.datum";

$result = $db -> query( $q );// ;
while ($data = $db -> fetch( $result )) {

	if ( $data['clid'] < 1 ) {
		$data['clid']   = getDogData( $data['did'], 'clid' );
		$data['client'] = getClientData( $data['clid'], 'title' );
	}

	$rez[] = [
		"datum"   => $data['datum'],
		"olduser" => $data['olduser'],
		"newuser" => $data['newuser'],
		"clid"    => $data['clid'],
		"client"  => $data['client'],
		"did"     => $data['did'],
		"uid"     => $data['uid'],
		"dogovor" => $data['dogovor'],
		"comment" => $data['comment']
	];

}

if ( $_REQUEST['action'] == "get_csv" ) {

	$otchet[0] = "#;Дата;Старый куратор;Новый куратор;Клиент;Сделка;Комментарий";

	for ( $i = 0; $i < count( $rez ); $i++ ) {

		$g = $i + 1;
		if ( $rez[ $i ]['uid'] != '' )
			$s = $rez[ $i ]['uid'].": ";
		else $s = '';

		$otchet[] = $g.";".excel_date( $rez[ $i ]['datum'] ).";".current_user( $rez[ $i ]['olduser'] ).";".current_user( $rez[ $i ]['newuser'] ).";".$rez[ $i ]['client'].";".$s.$rez[ $i ]['dogovor'].";".$rez[ $i ]['comment'];

	}

	//создаем файл csv
	$filename = 'export_doganaliz.csv';
	$handle   = fopen( "../files/".$filename, 'w' );

	for ( $g = 0; $g < count( $otchet ); $g++ ) {
		$otchet[ $g ] = iconv( "UTF-8", "CP1251", str_replace( "<br>", "\t", $otchet[ $g ] ) );
		fwrite( $handle, $otchet[ $g ]."\n" );
	}
	fclose( $handle );
	header( 'Content-type: application/csv' );
	header( 'Content-Disposition: attachment; filename="'.$filename.'"' );

	readfile( "../files/".$filename );
	unlink( "../files/".$filename );
	exit();
}
?>
<br>
<table width="100%" border="0" cellspacing="0" cellpadding="5" class="noborder">
	<tr>
		<td width="25%">
			<div class="ydropDown">
				<span>Отделы</span><span class="ydropCount yotdel"><?= count( $otdel ) ?> выбрано</span><i class="icon-angle-down pull-aright"></i>
				<div class="yselectBox" style="height:auto; max-height: 200px;">
					<div class="yunSelect"><!--<i class="icon-cancel-circled2"></i>Снять выделение--></div>
					<div id="selectOtdel">
						<?php
						$result = $db -> query( "SELECT * FROM ".$sqlname."otdel_cat WHERE identity = '$identity' ORDER BY title" );
						while ($data = $db -> fetch( $result )) {
							?>
							<div class="ydropString ellipsis">
								<label>
									<input class="taskss" name="otdel[]" type="checkbox" id="otdel[]" value="<?= $data['idcategory'] ?>" <?php if ( in_array( $data['idcategory'], $otdel ) )
										print 'checked'; ?> onClick="getUserList()">&nbsp;<?= $data['title'] ?>
								</label>
							</div>
							<?php
						}
						?>
					</div>
				</div>
			</div>
		</td>
		<td width="25%">
			<div class="ydropDown">
				<span>Сотрудники</span><span class="ydropCount yuser"><?= count( $users ) ?> выбрано</span><i class="icon-angle-down pull-aright"></i>
				<div class="yselectBox" style="height:auto; max-height: 400px;">
					<div class="yunSelect"><!--<i class="icon-cancel-circled2"></i>Снять выделение--></div>
					<div id="ulists">
						<?php

						$s = implode( ',', $otdel );
						if ( $s != '' )
							$s = ' and otdel IN ('.$s.')';

						$result = $db -> query( "SELECT * FROM ".$sqlname."user WHERE iduser > 0 $s and identity = '$identity' ORDER BY title" );
						while ($data = $db -> fetch( $result )) {
							?>
							<div class="ydropString ellipsis">
								<label>
									<input class="taskss" name="user[]" type="checkbox" id="user[]" value="<?= $data['iduser'] ?>" <?php if ( in_array( $data['title'], $users ) or count( $users ) == 0 )
										print 'checked'; ?> onclick="userCount()">&nbsp;<?= $data['title'] ?>
								</label>
							</div>
						<?php } ?>
					</div>
				</div>
			</div>
		</td>
		<td width="25%"></td>
		<td></td>
	</tr>
</table>
<br>
<div class="zagolovok_rep" align="center">
	<b>Лог смены Ответственных с <?= format_date_rus( $da1 ) ?>&nbsp;по&nbsp;<?= format_date_rus( $da2 ) ?></b> (<a href="#" onClick="generate_csv()" style="color:blue">Скачать CSV</a>):
</div>
<hr>
<TABLE width="98.5%" cellpadding="5" cellspacing="0">
	<thead>
	<TR class="header_contaner" height="28">
		<td width="20" align="center"><b>#</b></td>
		<td align="center" class="yw80"><b>Дата</b></td>
		<td align="center" class="yw100"><b>Старый куратор</b></td>
		<td align="center" class="yw100"><b>Новый куратор</b></td>
		<td align="center" class="yw250"><b>Клиент / Сделка</b></td>
		<td align="center" class="yw250"><b>Комментарий</b></td>
		<td align="center"></td>
	</TR>
	</thead>
	<?php
	for ( $i = 0; $i < count( $rez ); $i++ ) {

		if ( $rez[ $i ]['uid'] != '' )
			$s = "<b>".$rez[ $i ]['uid']."</b>: ";
		else $s = '';
		?>
		<TR class="ha" height="35">
			<TD width="2" align="right"><?= $i + 1 ?>.</TD>
			<TD align="center"><?= get_sfdate2( $rez[ $i ]['datum'] ) ?></TD>
			<TD align="right"><?= current_user( $rez[ $i ]['olduser'], 'yes' ) ?></TD>
			<TD align="right"><?= current_user( $rez[ $i ]['newuser'], 'yes' ) ?></TD>
			<TD>
				<?php if ( $rez[ $i ]['clid'] > 0 ) { ?>
					<div class="ellipsis">
						<A href="#" onclick="openClient(<?= $rez[ $i ]['clid'] ?>)" title="Открыть"><i class="icon-building broun"></i>&nbsp;<?= $rez[ $i ]['client'] ?>
						</A></div><br><?php } ?>
				<?php if ( $rez[ $i ]['did'] > 0 ) { ?>
					<div class="ellipsis">
					<A href="#" onclick="openDogovor(<?= $rez[ $i ]['did'] ?>)" title="Открыть"><i class="icon-briefcase blue"></i>&nbsp;<?= $s.$rez[ $i ]['dogovor'] ?>
					</A></div><?php } ?>
			</TD>
			<TD>
				<div class="ellipsis" title="<?= $rez[ $i ]['comment'] ?>"><?= $rez[ $i ]['comment'] ?></div>
			</TD>
			<TD></TD>
		</TR>
	<?php } ?>
</TABLE>
<div style="height:80px"></div>
<script>
	$(document).ready(function () {
		userCount();
	});

	function getUserList() {
		var str = $('#selectreport .taskss:checkbox:checked').serialize() + '&action=get_users';

		//console.log(str);
		$('#ulists').html('').append('<img src="/assets/images/loading.gif">');

		$.get('reports/<?=$thisfile?>', str, function (data) {

			var list = '';

			for (var i in data) {

				if (data[i].ch == 'yes') ch = 'checked';
				else ch = 'checked';

				list = list + '<div class="ydropString ellipsis"><label><input class="taskss" name="user[]" type="checkbox" id="user[]" value="' + data[i].iduser + '" ' + ch + '>&nbsp;' + data[i].title + '</label></div>';

			}

			//console.log(list);

			$('#ulists').html(list);

		}, 'json')
			.complete(function () {
				userCount();
			});
	}

	function userCount() {
		var l = $('#ulists input:checkbox:checked').length;
		var o = $('#selectOtdel input:checkbox:checked').length;
		$('.yuser').html(l + ' выбрано');
		$('.yotdel').html(o + ' выбрано');
	}
</script>