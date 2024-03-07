<?php
$result_sip = $db -> getRow("SELECT * FROM ".$sqlname."sip WHERE identity = '$identity'");

$sip['active']   = $active       = $result_sip["active"];
$sip['host']     = $sip_host     = $result_sip["sip_host"];
$sip['port']     = $sip_port     = $result_sip["sip_port"];
$sip['channel']  = $sip_channel  = $result_sip["sip_channel"];
$sip['context']  = $sip_context  = $result_sip["sip_context"];
$sip['user']     = $sip_user     = rij_decrypt($result_sip["sip_user"], $skey, $ivc);
$sip['secret']   = $sip_secret   = rij_decrypt($result_sip["sip_secret"], $skey, $ivc);
$sip['numout']   = $sip_numout   = $result_sip["sip_numout"];
$sip['pfchange'] = $sip_pfchange = $result_sip["sip_pfchange"];
$sip['cdr']      = $sip_cdr      = $result_sip["sip_cdr"];
$sip['secure']   = $sip_secure   = $result_sip["sip_secure"];
