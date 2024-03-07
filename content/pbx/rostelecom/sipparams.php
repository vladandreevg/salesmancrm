<?php
$res = $db -> getRow("select * from ".$sqlname."services WHERE folder = 'rostelecom' and identity = '$identity'");
$api_key       = $res["user_key"];
$api_salt      = rij_decrypt($res["user_id"], $skey, $ivc);