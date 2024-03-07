<?php
$res = $db -> getRow("select * from ".$sqlname."services WHERE folder = 'mango' and identity = '$identity'");
$api_key       = rij_decrypt($res["user_key"], $skey, $ivc);
$api_salt      = rij_decrypt($res["user_id"], $skey, $ivc);