<?php
global $skey, $ivc, $identity;

$result_sip = $db -> getRow("SELECT * FROM {$sqlname}sip WHERE identity = '$identity'");

$sip['active']   = $result_sip["active"];
$sip['host']     = $result_sip["sip_host"];
$sip['context']  = $result_sip["sip_context"];
$sip['user']     = rij_decrypt($result_sip["sip_user"], $skey, $ivc);
$sip['secret']   = rij_decrypt($result_sip["sip_secret"], $skey, $ivc);
