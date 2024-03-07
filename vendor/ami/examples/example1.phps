<?php
include_once '../AmiLib.php';
$config['server'] = '192.168.15.182';
$config['port'] = 5038;
$config['username'] = 'user';
$config['password'] = 'pass';
$config['authtype'] = 'plaintext';
$config['debug'] = true;
$config['log'] = true;
$config['logfile'] = '/tmp/ami.log';


$x = new AmiLib($config);

print_r($config);

if ($x->connect()) {

	$peer = $x->sendRequest("Sippeers");
	print_r($peer);


	$params['ActionID'] = 'command-001';
	$params['Filename'] = 'extensions.conf';


	$getfile = $x->sendRequest("GetConfig",$params);

	print_r($getfile);

	$show = $x->commandExecute("sip show peers");

	print_r($show);

	$x->disconnect();
} else {
	echo "erro";
}

?>
