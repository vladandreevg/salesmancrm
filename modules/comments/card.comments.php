<?php
/* ============================ */
/*         SalesMan CRM         */
/* ============================ */
/* (C) 2016 Vladislav Andreev   */
/*       SalesMan Project       */
/*        www.isaler.ru         */
/*        ver. 2019.x           */
/* ============================ */

use Salesman\Project;

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

$fpath = $GLOBALS['fpath'];

$comid  = (int)$_REQUEST['comid']; //id для вывода конкретной темы
$action = $_REQUEST['action'];

$accsess = false;
$author  = 0;
$users   = [];

if ( $comid > 0 ) {

	//Обеспечиваем доступ только для приглашенных пользователей
	$users = $db -> getCol( "select iduser from {$sqlname}comments_subscribe WHERE idcomment = '$comid' and identity = '$identity' ORDER BY id" );

	$result  = $db -> getRow( "select * from {$sqlname}comments where id = '$comid' and identity = '$identity'" );
	$author  = (int)$result["iduser"];
	$isClose = $result["isClose"];

}

if ( in_array( $iduser1, $users ) || $iduser1 == $author || $isadmin == 'on' ) {
	$accsess = true;
}

function get_subscribe_status($id): string {

	global $rootpath;

	include $rootpath."/inc/config.php";
	include $rootpath."/inc/dbconnector.php";
	include $rootpath."/inc/settings.php";

	$sqlname  = $GLOBALS['sqlname'];
	$identity = $GLOBALS['identity'];
	$iduser1  = $GLOBALS['iduser1'];
	$db       = $GLOBALS['db'];

	$count = (int)$db -> getOne( "select COUNT(*) from {$sqlname}comments_subscribe WHERE idcomment = '$id' and iduser = '$iduser1' and identity = '$identity'" ) + 0;

	return $count == 0 ? '<a href="javascript:void(0)" onclick="editComment(\''.$id.'\', \'subscribe\')" title="Подписаться на новые сообщения" class="smalltxt"><i class="icon-mail-alt blue"></i></a>&nbsp;' : '<a href="javascript:void(0)" onclick="editComment(\''.$id.'\', \'unsubscribe\')" title="Отписаться от новых сообщений" class="smalltxt"><i class="icon-mail red"></i></a>&nbsp;';
}

function view_files($fids): string {

	global $rootpath;

	include $rootpath."/inc/config.php";
	include $rootpath."/inc/settings.php";

	$sqlname  = $GLOBALS['sqlname'];
	$identity = $GLOBALS['identity'];
	$db       = $GLOBALS['db'];
	$fpath    = $GLOBALS['fpath'];
	$files    = [];
	$filess   = '';

	$fid = yexplode( ";", $fids );

	if ( !empty( $fid ) ) {

		foreach ( $fid as $id ) {

			$file = $db -> getRow( "select * from {$sqlname}file WHERE fid = '$id' and identity = '$identity'" );

			if ( $file['fname'] != '' ) {

				$ff = (isViewable( $file['ftitle'] )) ? '<A href="javascript:void(0)" onclick="fileDownload(\''.$id.'\',\'\',\'\')" class="gray"><i class="icon-eye broun" title="Просмотр"></i></A>&nbsp;' : '';

				$files[] = '<div class="pad3 inline">'.get_icon2( $file['fname'] ).'&nbsp;'.$file['ftitle'].'</a>&nbsp;'.$ff.'<a href="javascript:void(0)" onclick="fileDownload(\''.$id.'\',\'\',\'yes\')" title="Скачать" class="gray"><i class="icon-download blue"></i></a>&nbsp;['.num_format( filesize( "../../files/".$fpath.$file['fname'] ) / 1000 ).' kb.]</div>';

			}

		}

		if ( !empty( $files ) ) {
			$filess = implode( ";", $files );
		}

	}

	return $filess;

}

//тема в карточке обсуждения
if ( $action == "theme.card" ) {

	$result = $db -> query( "SELECT * FROM {$sqlname}comments WHERE id > 0 AND id = '$comid' AND idparent = 0 AND identity = '$identity' ORDER BY id" );
	//print $db -> lastQuery();
	$num    = $db -> numRows( $result );
	while ($data = $db -> fetch( $result )) {

		$users    = '';
		$userlist = [];

		$res = $db -> getCol( "SELECT iduser FROM {$sqlname}comments_subscribe WHERE idcomment = '".$data['id']."' and identity = '$identity'" );
		foreach ( $res as $iduser ) {

			$del = $iduser1 == $data['iduser'] || $isadmin == 'on' ? '<A href="javascript:void(0)" onclick="cf=confirm(\'Вы действительно хотите отписать сотрудника?\');if (cf)unsubscribeComment(\''.$data['id'].'\',\''.$iduser.'\');" title="Отписать"><i class="icon-cancel-circled red"></i></A>' : '';

			$users      .= '<div class="p10 bluebg-sub inline mr5 mb5"><i class="icon-user-1 blue"></i>'.current_user( $iduser, "yes" ).''.$del.' </div>';
			$userlist[] = $iduser;

		}

		$rcount = $db -> getOne( "select COUNT(*) from {$sqlname}comments_subscribe WHERE idcomment = '$comid' and iduser = '$iduser1' and identity = '$identity'" ) + 0;

		$buttons = '';

		if ( $accsess && $isClose != 'yes' ) {

			$buttons .= $rcount == 0 ? '<a href="javascript:void(0)" onclick="editComment(\''.$comid.'\', \'subscribe\')" title="Подписаться на новые сообщения" class="button greenbtn"><i class="icon-mail-alt white"></i>&nbsp;Подписаться</a>&nbsp;' : '<a href="javascript:void(0)" onclick="cf=confirm(\'Вы действительно хотите отписаться?\');if (cf)editComment(\''.$comid.'\', \'unsubscribe\')" title="Отписаться от новых сообщений" class="button redbtn"><i class="icon-mail white"></i>&nbsp;Отписаться</a>&nbsp;';

			$buttons .= '<a href="javascript:void(0)" onclick="editComment(\'\', \'add\', \''.$comid.'\')" title="Добавить комментарий" class="button m0"><i class="icon-chat-empty white"></i>Ответить</a>';

		}

		$attachments = view_files( $data['fid'] );

		print '
		<DIV class="fcontainer m0 mb10" data-id="card-theme">
		
			<DIV class="fcontainer1 hidden no-border bgwhite">
			
				<!--выводим опцию пригласить коллег-->
				<div class="pull-aright">'.
					($accsess && $isClose != 'yes' ? '<a href="javascript:void(0)" onclick="editComment(\''.$data['id'].'\',\'subscribe.user\');" title="Пригласить коллег" class="smalltxt"><i class="icon-users-1 blue"><i class="sup icon-plus"></i></i></a>&nbsp;&nbsp;&nbsp;&nbsp;' : '').'
				</div>
				<b>'.current_user( $data['iduser'] ).'</b>, '.get_hist( $data['datum'] ).'

			</DIV>
			
			<DIV class="fcontainer1 no-border bgwhite pb10">
			
				<div class="fs-11 flh-12">'.htmlspecialchars_decode( $data['content'] ).'</div>

			</DIV>
			
			'.($attachments != '' ? '
			
			<DIV class="fcontainer1 no-border pb10 bgwhite mb10">
			
				<div class="divider"><b>Вложения</b></div>
				<div class="mt20 pb10 table">
					'.$attachments.'
				</div>
				
			</DIV>' : '').'
			
			<DIV class="fcontainer1 no-border pb5 bgwhite">
			
				<div class="divider"><b>Участники обсуждения</b></div>
				<div class="mt20 pb10">
					<div class="wp100 block">'.$users.'</div>
				</div>
				
			</DIV>
			
			'.($buttons != '' ? '<div class="text-right mt20">'.$buttons.'</div>' : '').'
			
		</DIV>
		';

	}

	if ( $num == 0 ) {
		print '<div class="fcontainer">Обсуждений нет</div>';
	}

}

//в карточке клиента, контакта, сделки, проекта
if ( $action == "theme.extern" ) {

	$clid    = (int)$_REQUEST['clid'];
	$pid     = (int)$_REQUEST['pid'];
	$did     = (int)$_REQUEST['did'];
	$project = (int)$_REQUEST['project'];

	$allow = get_accesse( (int)$clid, (int)$pid, (int)$did );

	if ( $clid > 0 ) {
		$d = "clid = '$clid'";
	}
	if ( $pid > 0 ) {
		$d = "pid = '$pid'";
	}
	if ( $did > 0 ) {
		$d = "did = '$did'";
	}
	if ( $project > 0 ) {
		$d = "project = '$project'";
	}

	$result = $db -> query( "SELECT * FROM {$sqlname}comments WHERE id > 0 and $d and idparent = 0 and identity = '$identity' ORDER BY id" );
	$num    = $db -> numRows( $result );
	$j      = 0;
	while ($data = $db -> fetch( $result )) {

		//print $data['id'];

		//Обеспечиваем доступ только для приглашенных пользователей
		$users   = [];
		$accsess = false;

		$users = $db -> getCol( "SELECT iduser FROM {$sqlname}comments_subscribe WHERE idcomment = '".$data['id']."' and identity = '$identity' ORDER BY id" );

		$accsess = in_array( $iduser1, $users ) || $iduser1 == $data['iduser'] || $isadmin == 'on';

		if ( $data['isClose'] == 'yes' ) {

			$s    = 'Активировать';
			$t    = '&nbsp;<i class="icon-lock red" title="Закрыто"></i>';
			$tt   = get_sfdate( $data['dateClose'] );
			$icon = 'icon-lock';

		}
		else {

			$s    = 'Закрыть';
			$t    = '';
			$tt   = '';
			$icon = 'icon-lock-open';

		}

		if ( $num > 0 && $j > 0 ) {
			print '<hr class="margtop10 margbot10 blue">';
		}

		$j++;

		$btnn = '';

		//выводим опцию пригласить коллег
		if ( $accsess && $data['isClose'] != 'yes' ) {
			$btnn .= '<a href="javascript:void(0)" onclick="editComment(\''.$data['id'].'\', \'subscribe.user\', \'\')" title="Пригласить коллег" class="smalltxt gray"><i class="icon-users-1 blue"><i class="sup icon-plus"></i></i></a>&nbsp;';
		}

		//выводим статус подписки
		if ( $accsess ) {
			$btnn .= get_subscribe_status( $data['id'] );
		}

		//если текущий пользователь = автор темы, то может редактировать
		if ( $data['iduser'] == $iduser1 || $isadmin == 'on' ) {
			$btnn .= '<a href="javascript:void(0)" onclick="editComment(\''.$data['id'].'\', \'edit\', \'\')" title="Редактировать" class="gray"><i class="icon-pencil green"></i></a>&nbsp;';
		}

		//выводим ссылку для открытия в новом окне
		$btnn .= '<a href="card.comments?comid='.$data['id'].'" target="_blank" title="В новом окне" class="smalltxt"><i class="icon-list-alt blue"></i></a>&nbsp;';

		if ( $data['iduser'] == $iduser1 || $isadmin == 'on' ) {
			$btnn .= '<a href="javascript:void(0)" onclick="cf=confirm(\'Вы действительно хотите выполнить?\');if (cf)editComment(\''.$data['id'].'\', \'close\', \'\')" class="red" title="'.$s.'"><i class="'.$icon.'"></i></a>&nbsp;';
		}

		$users    = '';
		$userlist = [];

		$res = $db -> getCol( "SELECT iduser FROM {$sqlname}comments_subscribe WHERE idcomment = '".$data['id']."' and identity = '$identity'" );
		foreach ( $res as $iduser ) {

			$users      .= '<div class="pull-left"><i class="icon-user-1 blue"></i>'.current_user( $iduser, "yes" ).($iduser1 == $data['iduser'] ? '<A href="javascript:void(0)" onclick="cf=confirm(\'Вы действительно хотите отписать сотрудника?\');if (cf)unsubscribeComment(\''.$data['id'].'\',\''.$iduser.'\');" title="Отписать" class="gray"><i class="icon-cancel-circled red"></i></A>' : '').'; </div>';
			$userlist[] = $iduser;

		}

		$files = view_files( $data['fid'] );

		print '
		<h2 class="blue">
		
			<i class="icon-chat blue"></i> '.$data['title'].''.$t.'<sup class="fs-05">'.$tt.'</sup>
			
		</h2>
		<DIV class="fcontainer p0 mb10 pb10">
		
			<DIV class="fcontainer header gray2">
			
				<div class="pull-right">'.$btnn.'</div>
				
				<b>'.current_user( $data['iduser'] ).'</b>, '.get_hist( $data['datum'] ).'
				<span>&nbsp;<a href="card.comments?comid='.$data['id'].'" target="_blank" title="В новом окне" class="smalltxt"><i class="icon-list-alt blue"></i>&nbsp;В новом окне</a></span>

			</DIV>
			
			<DIV class="bgwhite p10 m0 border-bottom" style="max-height:250px; overflow:auto !important;">
			
				<div class="fs-11 flh-12">'.htmlspecialchars_decode( $data['content'] ).'</div>
				
			</DIV>
			
			'.($files != '' ? '
				<DIV class="fcontainer p0 no-border">
					<div class="m5 pb10 block">
						<div class="mb10 gray">Вложения</div>
						'.$files.'
					</div>
				</DIV>' : '').'
			
			<DIV class="fcontainer p0 no-border">
				<div class="m5 pb10 block">
					<div class="mb10 gray">Участники обсуждения</div>
					'.$users.'
				</div>
			</DIV>'

			.(($accsess && $data['isClose'] != 'yes') && (in_array( $iduser1, $userlist ) || $iduser1 == $data['iduser']) ? '<div class="text-right mr10"><a href="javascript:void(0)" onclick="editComment(\''.$data['id'].'\', \'add\', \''.$data['id'].'\')" title="Добавить комментарий" class="button greenbtn mt10 marg0"><i class="icon-chat-empty white"></i>Ответить</a></div>' : '');

		/**
		 * Ответы в теме
		 */
		$results = $db -> getAll( "SELECT * FROM {$sqlname}comments WHERE id > 0 and idparent = '".$data['id']."' and identity = '$identity' ORDER BY datum DESC" );
		$counts  = count( $results );

		$string = '';
		foreach ( $results as $datas ) {

			$btns = '';

			if ( $accsess && $data['isClose'] != 'yes' ) {

				if ( $iduser1 == (int)$datas['iduser'] || $iduser1 == (int)$data['iduser'] ) {

					$btns .= '<a href="javascript:void(0)" onclick="editComment(\''.$datas['id'].'\', \'edit\', \''.$data['id'].'\')" title="Редактировать" class="gray"><i class="icon-pencil blue"></i></a>&nbsp;';
					$btns .= '<a href="javascript:void(0)" onclick="cf=confirm(\'Вы действительно хотите удалить запись?\');if (cf)editComment(\''.$datas['id'].'\', \'delete\', \'\')" title="Удалить" class="gray"><i class="icon-cancel-circled red"></i></a>';

				}

			}

			$files = view_files( $datas['fid'] );

			$string .= '
			<div class="cdialogs fcontainer p0 mt10 relativ box-shadow focused">
				'.($btns != '' ? '<div class="panel pull-aright">'.$btns.'&nbsp;</div>' : '').'
				<div class="gray fs-07 m0 p10 inline no-border">
					 <b>'.current_user( $datas['iduser'] ).'</b>, '.get_hist( $datas['datum'] ).' ( '.diffDateTime( $datas['datum'] ).' )
				</div>
				<div class="p0 pt10">
				
					<div style="overflow:auto !important; max-height:250px;" class="p10 pl20 pb20 fs-10 flh-11 black bgwhite">
						'.str_replace( "header", "", htmlspecialchars_decode( $datas['content'] ) ).'
					</div>
					
					'.($files != '' ? '<div class="p10 graybg-lite">'.$files.'</div>' : '').'
						
				</div>
			</div>
			';

		}

		print '
		</DIV>';

		if ( $counts > 0 ) {

			print '
			
				<div data-id="answers" class="mt10 mb20 ml20">
					
					<div class="cardBlock pl15 mr5" style="height: 10px; overflow: hidden" data-height="10">
						<div class="divider mt20">Ответы</div>
						'.$string.'
					</div>
				
					<div class="div-center blue hand cardResizer fs-07" title="Развернуть" data-pozi="close">
						<i class="icon-angle-down"></i>'.$counts.' '.getMorph2( $counts, [
					'сообщение',
					'сообщения',
					'сообщений'
				] ).'<i class="icon-angle-down"></i>
					</div>
				
				</div>';

		}

	}

	if ( $num == 0 ) {
		print '<div class="fcontainer">Обсуждений нет</div>';
	}

}

/*Обсуждения в карточке обсуждения*/
if ( $action == "comment.list" ) {

	$results = $db -> query( "SELECT * FROM {$sqlname}comments WHERE id > 0 and idparent = '$comid' and identity = '$identity' ORDER BY datum DESC" );
	$num     = $db -> numRows( $results );
	while ($datas = $db -> fetch( $results )) {

		$shtml = isHTML( $datas['content'] ) ? htmlspecialchars_decode( $datas['content'] ) : nl2br( link_it( $datas['content'] ) );

		$btns = '';

		if ( $accsess ) {

			if ( $iduser1 == (int)$datas['iduser'] || $iduser1 == (int)$data['iduser'] ) {

				$btns .= '<a href="javascript:void(0)" onclick="editComment(\''.$datas['id'].'\', \'edit\')" title="Редактировать" class="gray blue"><i class="icon-pencil blue"></i></a>&nbsp;';
				$btns .= '<a href="javascript:void(0)" onclick="cf=confirm(\'Вы действительно хотите удалить запись?\');if (cf)editComment(\''.$datas['id'].'\', \'delete\')" title="Удалить" class="gray red"><i class="icon-cancel-circled"></i></a>';
			}


		}

		$files = view_files( $datas['fid'] );

		$avatar = $db -> getOne( "SELECT avatar FROM {$sqlname}user WHERE iduser = '$datas[iduser]'" );
		$avatar = ($avatar == '') ? "/assets/images/noavatar.png" : "./cash/avatars/".$avatar;

		print '
		<div class="cdialogs fcontainer box-shadow p0 relativ mb10 mt10">
		
			'.($btns != '' ? '<div class="panel">'.$btns.'</div>' : '').'
			<div class="p10 gray fs-09">
			
				<div class="avatar--micro" style="background: url('.$avatar.'); background-size:cover;vertical-align: middle;" title="'.current_user( $datas['iduser'], 'yes' ).'"></div>
				<div class="inline ml5"><b>'.current_user( $datas['iduser'], "yes" ).'</b>, '.get_hist( $datas['datum'] ).' ( '.diffDateTime( $datas['datum'] ).' )</div>
				
			</div>
		
			<div class="fs-10 flh-12 p15">'.$shtml.'</div>
			'.($files != '' ? '<div class="p10 graybg-lite">'.$files.'</div>' : '').'
			
		</div>';

	}

	if ( $num == 0 ) {
		print '<div class="fcontainer">Здесь пока никто не писал</div>';
	}

	if ( $accsess && $isClose != 'yes' ) {
		print '<div class="text-right pad5"><a href="javascript:void(0)" onclick="editComment(\'\', \'add\', \''.$comid.'\')" title="Добавить комментарий" class="button"><i class="icon-chat-empty white"></i>Ответить</a></div>';
	}
}

/*Обсуждения в списке обсуждений*/
if ( $action == "commentlist.view" ) {

	$id = (int)$_REQUEST['id'];

	if ( $id == 0 ) {
		print '
		<div id="emptymessage" class="gray miditxt"><i class="icon-monitor icon-3x gray"></i><br><b class="red">Упс.</b>&nbsp;&nbsp;<b>Не выбрано обсуждение для просмотра</b></div>
		';
	}

	$url = [];

	$users = $db -> getCol( "SELECT iduser FROM {$sqlname}comments_subscribe WHERE idcomment = '$id' and identity = '$identity' ORDER BY id" );

	$result  = $db -> getRow( "SELECT * FROM {$sqlname}comments WHERE id = '$id' and identity = '$identity'" );
	$author  = (int)$result["iduser"];
	$isClose = $result["isClose"];

	$accsess = in_array( $iduser1, $users ) || $iduser1 == $author /*|| $isadmin == 'on'*/;

	$theme = $db -> getRow( "SELECT * FROM {$sqlname}comments WHERE id = '$id' and idparent = 0 and identity = '$identity'" );

	//выводим опцию пригласить коллег
	$invite = ($accsess && $iduser1 == $author && $isClose != 'yes') ? '<a href="javascript:void(0)" onclick="editComment(\''.$id.'\',\'subscribe.user\');" title="Пригласить коллег" class="gray blue"><i class="icon-plus-circled-1 blue"><i class="sup icon-user-1 fs-05" style="left: -5px"></i></i><span class="hidden-iphone">&nbsp;Пригласить</span></a>&nbsp;&nbsp;&nbsp;' : '';

	$change = ($accsess && $iduser1 == $author && $isClose != 'yes') ? '<div onclick="editComment(\''.$id.'\',\'edit\');" title="Изменить" class="item ha hand"><i class="icon-pencil blue"></i>&nbsp;Редактировать</div>' : '';

	$delete = ( ($accsess && $iduser1 == $author && $isClose != 'yes') || $isadmin == 'on') ? '<div onclick="cf=confirm(\'Вы действительно хотите удалить запись?\');if (cf)editComment(\''.$id.'\',\'delete\');" title="Удалить" class="item ha hand"><i class="icon-cancel-circled red"></i>&nbsp;Удалить</div>' : '';

	//$closed = ($isClose == 'yes') ? '<i class="icon-lock red" title="Закрыта"></i> Обсуждение закрыто' : '';

	$closed = $iduser1 == $author ? (
		($accsess && $isClose == 'on' ? '<div onclick="cf=confirm(\'Вы действительно хотите активировать обсуждение?\');if (cf)editComment(\''.$id.'\', \'close\', \'\')" class="item ha hand" title="Активировать"><i class="icon-lock"></i> Открыть</div>' : '<div onclick="cf=confirm(\'Вы действительно хотите закрыть обсуждение?\');if (cf)editComment(\''.$id.'\', \'close\', \'\')" class="item ha hand" title="Закрыть"><i class="icon-lock-open broun"></i>&nbsp;Завершить</div>')
	) : '';

	if ( $theme['project'] > 0 ) {

		$project = Project ::info( $theme['project'] );

		$url [] = '<a href="javascript:void(0)" onclick="openProject(\''.$theme['project'].'\')"><i class="icon-buffer blue"></i>&nbsp;'.$project['project']['name'].'</a>&nbsp;';

		if ( (int)$theme['clid'] == 0 ) {
			$theme['clid'] = (int)$project['project']['clid'];
		}

		if ( (int)$theme['did'] == 0) {
			$theme['did'] = (int)$project['project']['did'];
		}

	}

	if ( (int)$theme['clid'] > 0 ) {
		$url[] = '<a href="javascript:void(0)" onclick="openClient(\''.$theme['clid'].'\')"><i class="icon-building blue"></i>&nbsp;'.current_client( $theme['clid'] ).'</a>&nbsp;';
	}

	if ( (int)$theme['pid'] > 0 ) {
		$url[] = '<a href="javascript:void(0)" onclick="openPerson(\''.$theme['pid'].'\')"><i class="icon-user-1 blue"></i>&nbsp;'.current_person( $theme['pid'] ).'</a>&nbsp;';
	}

	if ( (int)$theme['did'] > 0 ) {
		$url [] = '<a href="javascript:void(0)" onclick="openDogovor(\''.$theme['did'].'\')"><i class="icon-briefcase blue"></i>&nbsp;'.current_dogovor( $theme['did'] ).'</a>&nbsp;';
	}

	$ustring = '';
	foreach ( $users as $i => $user ) {

		$del = ($accsess && ($iduser1 == (int)$user || $iduser1 == (int)$author)) ? '<A href="javascript:void(0)" onclick="cf=confirm(\'Вы действительно хотите отписать сотрудника?\');if (cf)unsubscribeComment(\''.$id.'\',\''.$user.'\');" title="Отписать" class="gray"><i class="icon-cancel-circled red"></i></A>' : '';

		$ustring .= '<div class="inline"><i class="icon-user-1 blue"></i>'.current_user( $user, "yes" ).$del.'; </div>';

	}

	$res = (int)$db -> getOne( "SELECT COUNT(*) FROM {$sqlname}comments_subscribe WHERE idcomment = '$id' and iduser = '$iduser1' and identity = '$identity'" );

	$actionU = $actionX = '';

	$html = isHTML( $theme['content'] ) ? htmlspecialchars_decode( $theme['content'] ) : nl2br( link_it( $theme['content'] ) );

	if ( $accsess && $isClose != 'yes' ) {

		$actionU .= ($res == 0) ? '<span class="pull-left1"><a href="javascript:void(0)" onclick="editComment(\''.$id.'\', \'subscribe\')" title="Подписаться на новые сообщения" class="gray blue"><i class="icon-mail-alt blue"></i><span class="hidden-iphone">&nbsp;Подписаться</span></a>&nbsp;&nbsp;</span>' : '<span class="pull-left1"><a href="javascript:void(0)" onclick="cf=confirm(\'Вы действительно хотите отписаться?\');if (cf)editComment(\''.$id.'\', \'unsubscribe\')" title="Отписаться от новых сообщений" class="gray red"><i class="icon-mail-alt red"></i><span class="hidden-iphone">&nbsp;Отписаться</span></a>&nbsp;&nbsp;</span>';

		$actionX .= '<a href="javascript:void(0)" onclick="editComment(\'\', \'add\', \''.$id.'\')" title="Добавить комментарий" class="pad10"><i class="icon-forward-1 blue"></i><span class="hidden-iphone">Ответить (редактор)</span></a>';

	}
	else {
		$actionX .= '<a href="javascript:void(0)" onclick="editComment(\''.$id.'\', \'subscribe\')" title="Подписаться" class="pad10"><i class="icon-mail-alt blue"></i><span class="hidden-iphone">Подписаться</span></a>';
	}

	print '

	<DIV class="kbaction hidden">

		<span class="pull-left">
		
			<a href="javascript:void(0)" onclick="editComment(\''.$id.'\',\'open\');" title="В новом окне" class="gray"><i class="icon-chat green"></i></a>&nbsp;&nbsp;
			'.($isMobile ? '
			<div class="gray inline">
				<a href="javascript:void(0)" onclick="$(\'.ui-layout-east\').removeClass(\'open\'); $(\'#contentdiv\').find(\'tr\').removeClass(\'current\');" title=""><i class="icon-cancel-circled"></i> Закрыть</a>
			</div>' : '').'
			<span class="hidden-iphone">'.$invite.'</span>
			'.$actionU.'
			
		</span>
		
		<div class="inline hidden-iphone1 '.($change == '' && $delete == '' && $closed == '' ? 'hidden' : '').'">
		
			<a href="javascript:void(0)" class="tagsmenuToggler hand hidden-ipad1" title="Действия"><b>Действия</b>&nbsp;<i class="icon-angle-down" id="mapi"></i></a>

			<div class="tagsmenu toright hidden mr10">

				<div class="items noBold fs-09">
				
					'.$change.'
					'.$delete.'
					'.$closed.'

				</div>

			</div>
		
			<div class="flex-container button--group hidden">
	
				<div class="flex-string">
					'.$change.'
				</div>
				
				<div class="flex-string">
					'.$delete.'
				</div>
				
				<div class="flex-string">
					'.$closed.'
				</div>
				
			</div>
		
		</div>
		
	</DIV>

	<DIV class="fcontainer1 viewdiv1">
	
		<h2 class="p10 marg0 blue theme">'.$theme['title'].'</h2>
		
		<DIV class="gray2 fs-09 pl10 mt10 author">Автор: <b>'.current_user( $theme['iduser'], "yes" ).'</b>, Начата: '.get_sfdate( $theme['datum'] ).'</DIV>
		<div class="pl10 mt10">'.yimplode( ",&nbsp;&nbsp;", $url ).'</div>
		
		<DIV class="bgwhite mt10 mb10 p10">
			<div class="mb10 fs-11 flh-12">'.$html.'</div>
			<div class="fs-09">'.view_files( $theme['fid'] ).'</div>
		</DIV>

		<DIV class="pad5 em fs-09"><b>Участники обсуждения:</b>'.$ustring.'</DIV>
		
		<DIV class="text-right p10">'.$actionX.'</DIV>
		
	</DIV>
	
	';

	//Форма быстрого комментария

	if ( $accsess && $isClose != 'yes' ) {
		print '
			<div class="pad10 viewdiv mt5">
				<FORM method="post" action="modules/comments/core.comments.php" enctype="multipart/form-data" name="eForm" id="eForm">
				<input name="idparent" id="idparent" type="hidden" value="'.$id.'">
				<INPUT name="action" id="action" type="hidden" value="edit">
				<div>
					<textarea id="content" name="content" class="wp100 required" style="height:80px" placeholder="Быстрый ответ"></textarea>
				</div>
				<div class="filebox wp100 hidden">
					<div class="eupload relativ">
						<input name="file[]" id="file[]" type="file" onchange="addefile();" class="file wp100" multiple>
						<div class="idel hand delbox" title="Очистить"><i class="icon-cancel-circled red"></i></div>
					</div>
				</div>
				<hr>
				<div class="text-right">
					<a href="javascript:void(0)" onclick="$(\'.filebox\').toggleClass(\'hidden\')" class="gray pull-left" title="Прикрепить файлы"><i class="icon-attach-1 blue"></i></a>
					<A href="javascript:void(0)" onclick="$(\'#eForm\').submit()" class="button greenbtn marg0">Ответить</A>&nbsp;
				</div>
			</div>
		';
	}

	//Комментарии
	$string = '';

	$comments = $db -> query( "select * from {$sqlname}comments WHERE id > 0 and idparent = '$id' and identity = '$identity' ORDER BY datum DESC" );
	$num      = $db -> numRows( $comments );
	while ($datas = $db -> fetch( $comments )) {

		$actions = '';

		if ( $accsess && ($iduser1 == (int)$datas['iduser'] || $iduser1 == (int)$data['iduser']) ) {

			$actions .= '<a href="javascript:void(0)" onclick="editComment(\''.$datas['id'].'\', \'edit\')" title="Редактировать" class="gray"><i class="icon-pencil blue"></i></a>&nbsp;';
			$actions .= '<a href="javascript:void(0)" onclick="cf=confirm(\'Вы действительно хотите удалить запись?\');if (cf)editComment(\''.$datas['id'].'\', \'delete\')" title="Удалить" class="gray"><i class="icon-cancel-circled red"></i></a>';

		}

		$shtml = isHTML( $datas['content'] ) ? htmlspecialchars_decode( $datas['content'] ) : nl2br( link_it( $datas['content'] ) );
		//$shtml = nl2br(htmlspecialchars_decode(link_it($datas['content'])));

		$files = view_files( $datas['fid'] );

		$avatar = $db -> getOne( "SELECT avatar FROM {$sqlname}user WHERE iduser = '$datas[iduser]'" );
		$avatar = ($avatar == '') ? "/assets/images/noavatar.png" : "./cash/avatars/".$avatar;

		$string .= '
		<div class="cdialogs box-shadow fcontainer p0 mt10 ml201 box-shadow focused graybg-lite1 relativ">
			'.($actions != '' ? '<div class="panel">'.$actions.'</div>' : '').'
			<div class="fs-07 gray p10">
			
				<div class="avatar--micro" style="background: url('.$avatar.'); background-size:cover;vertical-align: middle;" title="'.current_user( $datas['iduser'], 'yes' ).'"></div>
				<div class="inline ml5"><b>'.current_user( $datas['iduser'], "yes" ).'</b>, '.get_sfdate( $datas['datum'] ).' ( '.diffDateTime( $datas['datum'] ).' )</div>
				
			</div>
			<div class="bgwhite1 mt10 mb10 p10">
				<div class="fs-11 flh-12">'.$shtml.'</div>
			</div>
			'.($files != '' ? '<div class="p10 fs-09 graybg-lite">'.$files.'</div>' : '').'
		</div>
		';

	}

	print '
	<div class="relativ">
		<div class="wp80" style="padding-left: 20%">
			'.$string.'
		</div>
	</div>
	';

	if ( $num == 0 ) {
		print '<div class="mt10 p10 viewdiv">Здесь пока никто не писал</div>';
	}

	print '<div class="space-100"></div>';

	?>
	<script>

		$('#content').autoHeight(200);

		$('#eForm').ajaxForm({

			dataType: 'json',
			beforeSubmit: function () {

				var $out = $('#message');
				var em = checkRequired();

				if (em === false) return false;

				$out.empty().css('display', 'block').append('<div id="loader"><img src="/assets/images/loader.gif"> Загрузка данных. Пожалуйста подождите...</div>');
				return true;


			},
			success: function (data) {

				$('#message').fadeTo(1, 1).css('display', 'block').html(data.mes);
				setTimeout(function () {
					$('#message').fadeTo(1000, 0);
				}, 5000);

				var ida = data.id;

				if ($('#tar').is('input')) {

					editComment(ida, 'viewshort', '');

				}

			}

		});

		function addefile() {

			var htmltr = '<div class="eupload relativ"><input name="file[]" id="file[]" type="file" onchange="addefile();" class="file wp100" multiple><div class="idel hand clearinputs" title="Очистить"><i class="icon-cancel-circled red"></i></div></div>';

			$('.filebox').append(htmltr);

		}

		$(document).on('click', '.delbox', function () {

			var count = $('.eupload').length;

			if (count === 1) $(this).closest('.eupload').find('#file\\[\\]').val('');
			else $(this).closest('.eupload').remove();

		});

	</script>
	<?php

	exit();

}

/*Ответ к обуждению*/
if ( $action == "comment.one" ) {

	$id = $_REQUEST['id'];

	$comment = $db -> getRow( "SELECT * FROM {$sqlname}comments WHERE id = '$id' and identity = '$identity' ORDER BY datum DESC" );


	exit();

}

/*последние ответы в панели*/
if ( $action == "listpanel" ) {

	//список тем, на которые подписан пользователь или является автором
	$idcomment = [];

	$coms  = [];
	$c     = [];
	$count = 0;
	$html  = '';
	$x     = 5;

	//выводим список тем
	$c = $db -> getCol( "
		SELECT 
			{$sqlname}comments.id
		FROM {$sqlname}comments
		WHERE 
			{$sqlname}comments.idparent = '0' AND
			{$sqlname}comments.id IN (SELECT idcomment FROM {$sqlname}comments_subscribe WHERE iduser = '$iduser1') AND
			{$sqlname}comments.isClose != 'yes' AND
			{$sqlname}comments.lastCommentDate > NOW() - INTERVAL 5 DAY AND
			{$sqlname}comments.identity = '$identity'
		ORDER BY {$sqlname}comments.lastCommentDate DESC
	" );

	if ( count( $c ) > 0 ) {

		$c    = implode( ",", $c );
		$coms = [];

		$results = $db -> query( "SELECT * FROM {$sqlname}comments WHERE idparent IN ($c) and identity = '$identity' ORDER BY datum DESC LIMIT 10" );
		//$num = $db->numRows($results);
		while ($datas = $db -> fetch( $results )) {

			if ( !in_array( $datas['idparent'], $coms ) ) {

				$title = $db -> getOne( "SELECT title FROM {$sqlname}comments WHERE id = '".$datas['idparent']."' and identity = '$identity'" );

				$html .= '
				<div class="replay">
					<div class="clink">
						<span class="ellipsis"><a href="card.comments?comid='.$datas['idparent'].'" target="blank" title="Перейти к обсуждению"><i class="icon-comment blue"></i>&nbsp;<b>'.mb_substr( untag( htmlspecialchars_decode( $title ) ), 0, 51, 'utf-8' ).'</b></a></span>
					</div>
					<div class="content">
						'.mb_substr( untag( htmlspecialchars_decode( $datas['content'] ) ), 0, 71, 'utf-8' ).'..<br>
						<div class="text-right pt5 pb5"><a href="javascript:void(0)" onclick="doLoad(\'modules/comments/form.comments.php?idparent='.$datas['idparent'].'&action=add&hideEditor=yes\');"><i class="icon-forward-1"></i>Ответить</a></div>
					</div>
					<div class="smalltext text-right"><span class="gray" style="float:left">'.diffDateTime( $datas['datum'] ).' назад</span><i class="icon-user-1 blue"></i>&nbsp;'.current_user( $datas['iduser'] ).'</div>
				</div>
				';

				$coms[] = $datas['idparent'];
				$count++;

			}

		}

		print '<div class="gray2 fs-09 p5 pt10 div-center">Показаны '.$count.' '.morph( $count, "ответ", "ответа", "ответов" ).' за '.$x.' '.morph( $x, "день", "дня", "дней" ).'</div><hr><div>'.$html.'</div>';

	}
	else {
		print '<div class="replay p10">Нет ответов в течение '.$x.' дней</div>';
	}

	unset( $db );

	exit();

}
if ( $action == "numpanel" ) {

	/**
	 * см. vigets/notify.counts.php
	 */

	$com = 0;

	$c = $db -> getCol( "
		SELECT 
			{$sqlname}comments.id 
		FROM {$sqlname}comments 
		WHERE 
			{$sqlname}comments.idparent = '0' AND 
			{$sqlname}comments.id IN (SELECT idcomment FROM {$sqlname}comments_subscribe WHERE iduser = '$iduser1') AND 
			{$sqlname}comments.isClose != 'yes' AND 
			{$sqlname}comments.lastCommentDate > NOW() - INTERVAL 5 DAY AND 
			{$sqlname}comments.identity = '$identity' 
		ORDER BY {$sqlname}comments.lastCommentDate DESC
	" );

	if ( count( $c ) > 0 ) {

		$c    = implode( ",", $c );
		$coms = [];

		$results = $db -> query( "SELECT id, idparent FROM {$sqlname}comments WHERE idparent IN ($c) and identity = '$identity' ORDER BY datum DESC LIMIT 10" );
		while ($datas = $db -> fetch( $results )) {

			if ( !in_array( $datas['idparent'], $coms ) ) {

				$coms[] = $datas['idparent'];
				$com++;

			}

		}

	}

	unset( $db );

	print $com;

	exit();

}