<?php

global $rootpath;
require_once $rootpath."/inc/head.php";


$sql = "CREATE TABLE IF NOT EXISTS `{$sqlname}price_files` (
    id INT AUTO_INCREMENT PRIMARY KEY,
    price_id INT,
    file_id INT UNSIGNED,
    datum DATETIME,
    identity INT
);";


$result  = $db->query($sql);

if($result){
 echo "Обновления примены";
}

