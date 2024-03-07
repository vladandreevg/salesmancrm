<?php
/* ============================ */
/* (C) 2018 Vladislav Andreev   */
/*       SalesMan Project       */
/*        www.isaler.ru         */
/*        ver. 2018.x           */
/* ============================ */
?>
<?php

use Salesman\Upload;

error_reporting( E_ERROR );

$rootpath = realpath( __DIR__.'/../../' );

include $rootpath."/inc/config.php";
include $rootpath."/inc/dbconnector.php";
include $rootpath."/inc/auth.php";
include $rootpath."/inc/func.php";
include $rootpath."/inc/settings.php";
include $rootpath."/inc/language/".$language.".php";

$thisfile = basename( __FILE__ );

$action = $_REQUEST['action'];

//Фунции рассылки
if ( $action == "edit" ) {

	$mid = $_POST['mid'];
	$do  = $_REQUEST['do'];

	$param['title']       = $_REQUEST['title'];
	$param['descr']       = $_REQUEST['descr'];
	$param['theme']       = $_REQUEST['theme'];
	$param['tip']         = $_REQUEST['tip'];
	$param['iduser']      = $_REQUEST['iduser'];
	$param['tpl_id']      = $_REQUEST['tpl_id'];
	$param['client_list'] = implode( ";", $_REQUEST['client_list'] );
	$param['person_list'] = implode( ";", $_REQUEST['person_list'] );
	$param['template']    = htmlspecialchars( str_replace( [
		"'",
		"\r\n",
		"\\r",
		"\\n"
	], "", str_replace( ['\"'], '"', $_REQUEST['content'] ) ) );

	$efile = (count( $_REQUEST['efile'] ) > 0) ? $_REQUEST['efile'] : [];
	$files = $_FILES['file'];

	//Проверяем наличие тематической папки
	$folder = $db -> getOne( "SELECT idcategory FROM ".$sqlname."file_cat WHERE title='Рассылки' and identity = '$identity'" ) + 0;
	if ( $folder == 0 ) {

		$db -> query( "insert into ".$sqlname."file_cat (idcategory,title,shared,identity) values(null, 'Рассылки', 'yes','$identity')" );
		$folder = $db -> insertId();

	}

	$message = $file_list = [];

	$upload = Upload ::upload();

	$message = array_merge( $message, $upload['message'] );

	foreach ( $upload['data'] as $file ) {

		$arg = [
			'ftitle'   => $file['title'],
			'fname'    => $file['name'],
			'ftype'    => $file['type'],
			'fver'     => '1',
			'iduser'   => $iduser1,
			'clid'     => $clid,
			'pid'      => $pid,
			'did'      => $did,
			'coid'     => $coid,
			'folder'   => $folder,
			'shared'   => $shared,
			"size"     => $file['size'],
			"datum"    => current_datumtime(),
			'identity' => $identity
		];

		$file_list[] = Upload ::edit( 0, $arg );

	}

	$param['file'] = implode( ";", array_unique( array_merge( $file_list, $efile ) ) );
	//конец - Загрузка файлов в хранилище

	//Запись данных о рассылке
	if ( $mid < 1 ) {

		$param['identity'] = $identity;

		$db -> query( "INSERT INTO ".$sqlname."mail SET ?u", $param );

		$mid = $db -> insertId();

	}
	elseif ( $mid > 0 ) {

		$param['datum'] = current_datumtime();

		$db -> query( "UPDATE ".$sqlname."mail SET ?u WHERE mid = '".$mid."' AND identity = '$identity'", $param );

	}

	//конец - Запись данных о рассылке

	$message[] = "Данные сохранены";

	$message = implode( '<br>', $message );

	print '{"result":"'.$message.'","doit":"'.$do.'","mid":"'.$mid.'"}';

	exit();

}
if ( $action == "delete" ) {

	$mid = $_REQUEST['id'];

	$db -> query( "delete from ".$sqlname."mail where mid = '".$mid."' and identity = '$identity'" );
	print "Сделано";

	exit();
}

if ( $action == "tpl.edit" ) {

	$id = $_REQUEST['tpl_id'];

	$params['name_tpl']    = $_REQUEST['name_tpl'];
	$params['content_tpl'] = htmlspecialchars( str_replace( [
		"'",
		"\r\n",
		"\\r",
		"\\n"
	], "", str_replace( ['\"'], '"', $_REQUEST['content'] ) ) );

	if ( $id > 0 ) {

		$db -> query( "UPDATE ".$sqlname."mail_tpl SET ?u where tpl_id = '$id'", $params );
		$message = "Сделано";

	}
	else {

		$params['identity'] = $identity;

		$db -> query( "INSERT INTO ".$sqlname."mail_tpl SET ?u", $params );

		$message = "Сделано";

	}

	print '{"result":"'.$message.'"}';

	exit();
}
if ( $action == "tpl.delete" ) {

	$tpl_id = $_REQUEST['id'];

	$db -> query( "delete from ".$sqlname."mail_tpl where tpl_id='".$tpl_id."' and identity = '$identity'" );

	print "Сделано";

	exit();
}

if ( $action == 'startMailing' ) {

	$id = $_REQUEST['mid'];

	//счетчик отправленных писем
	$smcount = 0;
	$files   = [];

	//параметры рассылки
	$maillist             = $db -> getRow( "select * from ".$sqlname."mail where mid = '".$id."' and identity = '$identity'" );
	$maillist['template'] = htmlspecialchars_decode( $maillist['template'] );

	$clients = yexplode( ";", $maillist["client_list"] );//список полуателей клиенты
	$ccount  = count( $clients );

	$persons = yexplode( ";", $maillist["person_list"] );//список полуателей контакты
	$pcount  = count( $persons );

	$clist = yexplode( ";", $maillist["clist_do"] );//список, кому отправили
	$plist = yexplode( ";", $maillist["plist_do"] );//список, кому отправили

	$attach = yexplode( ";", $maillist['file'] );
	for ( $i = 0; $i < count( $attach ); $i++ ) {

		$r = $db -> getRow( "select ftitle, fname from ".$sqlname."file where fid='".$attach[ $i ]."' and identity = '".$GLOBALS['identity']."'" );

		$files[] = [
			"file" => $r["fname"],
			"name" => $r["ftitle"]
		];

	}

	$images   = [];
	$ym_fpath = $rootpath.'/files/'.$fpath;


	preg_match_all( '/<img[^>]+src="?\'?([^"\']+)"?\'?[^>]*>/i', $maillist['template'], $images, PREG_SET_ORDER );

	foreach ( $images as $img ) {

		$imgSrc  = $img[1];
		$imgOrig = array_pop( yexplode( "/", $img[1] ) );

		if ( file_exists( $ym_fpath."kb/".$imgOrig ) ) {

			$imgBase64 = base64_encode( file_get_contents( $ym_fpath."kb/".$imgOrig ) );
			$src       = 'data:'.mime_content_type( $ym_fpath."kb/".$imgOrig ).';base64,'.$imgBase64;

			$maillist['template'] = str_replace( $imgSrc, $src, $maillist['template'] );

		}

	}

	//print_r($files);
	//exit();

	if ( $maillist['do'] != 'on' ) {

		$r       = $db -> getRow( "select * from ".$sqlname."settings WHERE id = '$identity'" );
		$company = $r["company"];
		$cfull   = $r["company_full"];
		$csite   = $r["company_site"];
		$cmail   = $r["company_mail"];
		$cphone  = $r["company_phone"];
		$cfax    = $r["company_fax"];

		//От кого письмо отправляется
		if ( $maillist['iduser'] == '0' ) { //от компании

			$phone    = $cphone;
			$fax      = $cfax;
			$mob      = '';
			$frommail = $cmail;
			$fromname = $company;

			$of = $db -> getOne( "select office from ".$sqlname."user where iduser='".$iduser1."' and identity = '$identity'" );

			$office = $db -> getOne( "SELECT title FROM ".$sqlname."office_cat WHERE idcategory = '".$of."' and identity = '$identity'" );

		}
		else {

			$r        = $db -> getRow( "select * from ".$sqlname."user where iduser='".$maillist['iduser']."' and identity = '$identity'" );
			$manager  = $r["title"];
			$phone    = $r["phone"];
			$fax      = $r["fax"];
			$mob      = $r["mob"];
			$frommail = $r["email"];
			$of       = $r["office"];

			$office = $db -> getOne( "SELECT title FROM ".$sqlname."office_cat WHERE idcategory='".$office."' and identity = '$identity'" );

		}

		//получаем данные шаблона
		$content_tpl = $maillist['template'];

		//формируем сообщение с заменой слов-шаблонов
		$content_tpl = str_replace( '{company}', $company, $content_tpl );
		$content_tpl = str_replace( '{office}', $office, $content_tpl );
		$content_tpl = str_replace( '{phone}', "тел.".$phone, $content_tpl );
		$content_tpl = str_replace( '{fax}', "факс.".$fax, $content_tpl );
		$content_tpl = str_replace( '{mob}', "моб.".$mob, $content_tpl );
		$content_tpl = str_replace( '{email}', '<a href="mailto:'.$frommail.'>'.$frommail.'</a>', $content_tpl );
		$content_tpl = str_replace( '{company_full}', $cfull, $content_tpl );
		$content_tpl = str_replace( '{company_site}', '<a href="http://'.str_replace( 'http://', '', $csite ).'>'.$csite.'</a>', $content_tpl );
		$content_tpl = str_replace( '{manager}', $fromname, $content_tpl );
		$content_tpl = str_replace( '\n', '<br>', $content_tpl );

		//отправляем сообщение
		$too  = '';
		$good = 0;
		$err  = 0;
		$msg  = '';

		if ( $ccount > 0 ) {

			for ( $i = 0; $i < $ccount; $i++ ) {

				if ( !in_array( $clients[ $i ], (array)$clist ) && $smcount < 20 ) {//не будем отправлять тем, кому уже отправлено

					$r      = $db -> getRow( "SELECT title, mail_url FROM ".$sqlname."clientcat where clid='".$clients[ $i ]."' and identity = '$identity'" );
					$toname = str_replace( [
						', ЗАО',
						', ООО',
						', ОАО',
						', ИП'
					], "", $r["title"] );
					$tomail = yexplode( ";", str_replace( ",", ";", $r["mail_url"] ), 0 );

					//формируем массив получателей-персон для общей расслыки
					if ( $tomail != '' ) {

						$html .= "<html><head><title>".$maillist['theme']."</title><STYLE type=\"text/css\"><!-- BODY { color:#000; FONT-SIZE: 13px; FONT-FAMILY: tahoma, arial;} --></STYLE></head><body>";
						$html .= str_replace( '{client}', $toname, $content_tpl );
						$html .= "</body></html>";

						if ( $isCloud ) {

							//if ( mymail2( $tomail, $toname, $frommail, $fromname, $maillist[ 'theme' ], $html, $maillist[ 'file' ] ) == '' ) {
							if ( mailto( [
									$tomail,
									$toname,
									$frommail,
									$fromname,
									$maillist['theme'],
									$html,
									$files
								] ) == '' ) {

								//добавим запись в карточку клиента
								addHistorty( [
									"tip"      => "Исх.Почта",
									"datum"    => current_datumtime(),
									"des"      => "Рассылка: ".$maillist['title'].", Описание рассылки - ".$maillist['descr'],
									"iduser"   => $iduser1,
									"clid"     => $clients[ $i ],
									"identity" => $identity
								] );

								//добавим в список отправленных
								$clist[] = $clients[ $i ];

								$smcount++;
								$good++;

							}
							else {

								$msg .= '<br>'.$mailsender_rez;
								print $mailsender_rez;
								$err++;

							}

						}
						else {

							//if ( mailer( $tomail, $toname, $frommail, $fromname, $maillist[ 'theme' ], $html, $files ) == '' ) {
							if ( mailto( [
									$tomail,
									$toname,
									$frommail,
									$fromname,
									$maillist['theme'],
									$html,
									$files
								] ) == '' ) {


								//добавим запись в карточку
								addHistorty( [
									"tip"      => "Исх.Почта",
									"datum"    => current_datumtime(),
									"des"      => "Рассылка: ".$maillist['title'].", Описание рассылки - ".$maillist['descr'],
									"iduser"   => $iduser1,
									"clid"     => $clients[ $i ],
									"identity" => $identity
								] );

								//добавим в список отправленных
								$clist[] = $clients[ $i ];

								$smcount++;
								$good++;

							}
							else {

								$msg .= '<br>'.$mailsender_rez;
								print $mailsender_rez;
								$err++;

							}

						}

						$header  = '';
						$body    = '';
						$html    = '';
						$to      = '';
						$content = '';
						//отправили.конец

					}
					else {
						$clist[] = $clients[ $i ];//добавим в список отправленных
					}
				}
			}

			$ppp = implode( ";", $clist );

			$db -> query( "update ".$sqlname."mail set clist_do = '".$ppp."' where mid = '".$id."' and identity = '$identity'" );

		}

		if ( $pcount > 0 ) {

			for ( $i = 0; $i < $pcount; $i++ ) {

				if ( !in_array( $persons[ $i ], (array)$plist ) && $smcount < 20 ) {//не будем отправлять тем, кому уже отправлено

					$r      = $db -> getRow( "SELECT person, mail FROM ".$sqlname."personcat where pid='".$persons[ $i ]."' and identity = '$identity'" );
					$toname = $r["person"];
					$tomail = yexplode( ";", str_replace( ",", ";", $r["mail"] ), 0 );

					//формируем массив получателей-персон для общей расслыки
					if ( $tomail != '' ) {

						$html .= "<html><head><title>".$maillist['theme']."</title><STYLE type=\"text/css\"><!-- BODY { color:#000; FONT-SIZE: 13px; FONT-FAMILY: tahoma, arial;} --></STYLE></head><body>";
						$html .= str_replace( '{client}', $toname, $content_tpl );
						$html .= "</body></html>";

						if ( $isCloud ) {

							//if ( mymail2( $tomail, $toname, $frommail, $fromname, $maillist[ 'theme' ], $html, $maillist[ 'file' ] ) == '' ) {
							if ( mailto( [
									$tomail,
									$toname,
									$frommail,
									$fromname,
									$maillist['theme'],
									$html,
									$files
								] ) == '' ) {


								//добавим запись в карточку персоны
								addHistorty( [
									"tip"      => "Исх.Почта",
									"datum"    => current_datumtime(),
									"des"      => "Рассылка: ".$maillist['title'].", Описание рассылки - ".$maillist['descr'],
									"iduser"   => $iduser1,
									"pid"      => $persons[ $i ],
									"identity" => $identity
								] );

								//добавим в список отправленных
								$plist[] = $persons[ $i ];

								$smcount++;
								$good++;

							}
							else {

								$msg .= '<br>'.$mailsender_rez;
								print $mailsender_rez;
								$err++;

							}

						}
						else {

							//if ( mailer( $tomail, $toname, $frommail, $fromname, $maillist[ 'theme' ], $html, $files ) == '' ) {
							if ( mailto( [
									$tomail,
									$toname,
									$frommail,
									$fromname,
									$maillist['theme'],
									$html,
									$files
								] ) == '' ) {


								//добавим запись в карточку персоны
								addHistorty( [
									"tip"      => "Исх.Почта",
									"datum"    => current_datumtime(),
									"des"      => "Рассылка: ".$maillist['title'].", Описание рассылки - ".$maillist['descr'],
									"iduser"   => $iduser1,
									"pid"      => $persons[ $i ],
									"identity" => $identity
								] );

								//добавим в список отправленных
								$plist[] = $persons[ $i ];

								$smcount++;
								$good++;

							}
							else {

								$msg .= '<br>'.$mailsender_rez;
								print $mailsender_rez;
								$err++;

							}

						}

						$header  = '';
						$body    = '';
						$html    = '';
						$to      = '';
						$content = '';
						//отправили.конец

					}
					else {

						$plist[] = $persons[ $i ];//добавим в список отправленных

					}

				}

			}

			$ppp = implode( ";", (array)$plist );

			$db -> query( "update ".$sqlname."mail set plist_do = '".$ppp."' where mid = '".$id."' and identity = '$identity'" );

		}

	}
	else $ret = "Рассылка не запущена";

	//считаем остатки

	$re = $db -> getRow( "select clist_do, plist_do from ".$sqlname."mail where mid='".$id."' and identity = '$identity'" );

	//списки, кому отправили
	$ccountdo = count( yexplode( ";", $re["clist_do"] ) );
	$pcountdo = count( yexplode( ";", $re["plist_do"] ) );

	$delta = ($ccount + $pcount) - ($ccountdo + $pcountdo);

	$call = $ccount + $pcount;
	$cdo  = $ccountdo + $pcountdo;

	$meter = pre_format( $cdo / $call );

	if ( $delta == 0 ) {

		$db -> query( "update ".$sqlname."mail set do = 'on' where mid = '".$id."' and identity = '$identity'" );

		//$rezult = '{"result":"end","message":"Рассылка закончена","all":"'.$call.'","sent":"'.$cdo.'","meter":"1"}';

		$rezult = json_encode_cyr( [
			"result"  => "end",
			"message" => "Рассылка закончена",
			"all"     => $call,
			"sent"    => $cdo,
			"meter"   => 1
		] );

	}
	else {

		//$rezult = '{"result":"resume","all":"'.$call.'","sent":"<b>'.$cdo.'</b> '.getMorph2( $cdo, ['письмо', 'письма', 'писем'] ).'","meter":"'.$meter.'"}';

		$rezult = json_encode_cyr( [
			"result" => "resume",
			"all"    => $call,
			"sent"   => $cdo.' '.getMorph2( $cdo, [
					'письмо',
					'письма',
					'писем'
				] ),
			"meter"  => $meter
		] );

	}

	print $rezult;

}