<?php
$res        = $db -> getRow("select * from {$sqlname}services WHERE folder = 'onlinepbx' and identity = '$identity'");
$api_user   = rij_decrypt($res["user_id"], $skey, $ivc);
$api_secret = rij_decrypt($res["user_key"], $skey, $ivc);