<?php
global $skey;
global $ivc;
$res = $db -> getRow("select * from ".$sqlname."services WHERE folder = 'gravitel' and identity = '$identity'");
//print_r($res);
//print "skey=$skey\n";
//print "ivc=$ivc\n";
$api_key       = rij_decrypt($res["user_key"], $skey, $ivc);
$api_salt      = rij_decrypt($res["user_id"], $skey, $ivc);