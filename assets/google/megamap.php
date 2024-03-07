<?php
//error_reporting(0);
//require("inc/config.php");

$dbhostname = "localhost";
$dbusername = "root";
$dbpassword = "";
$database = "stabbase";
@mysql_connect ($dbhostname , $dbusername , $dbpassword);
@mysql_select_db($database);

function parseToXML($htmlStr)
{
	$xmlStr=str_replace('<','(',$htmlStr);
	$xmlStr=str_replace('>',')',$xmlStr);
	$xmlStr=str_replace('"','&#8220;',$xmlStr);

	$xmlStr=str_replace('&','&',$xmlStr);
	return $xmlStr;
}

/*function parseToXML($htmlStr)
{

                $xmlStr = str_replace($htmlStr);
                $xmlStr = str_replace('\"','&#8220;',$xmlStr);
                $xmlStr = str_replace('\'','',$xmlStr);
				$xmlStr = str_replace('&nbsp;',' ',$xmlStr);
				$xmlStr = str_replace('<','(',$xmlStr);
				$xmlStr = str_replace('>',')',$xmlStr);
                $xmlStr = str_replace('\&','',$xmlStr);
                //$xmlStr = str_replace("<","&lt;",$xmlStr);
                //$xmlStr = str_replace('\\\"',"&quot;",$xmlStr);
                //$xmlStr = str_replace('\\"',"&quot;",$xmlStr);
                $xmlStr = str_replace('!','&#33;',$xmlStr);
                //$xmlStr = str_replace("\r\n","<br>",$xmlStr);
                //$xmlStr = str_replace("\n","<br>",$xmlStr);
                //$xmlStr = str_replace("%","&#37;",$xmlStr);
                //$xmlStr = str_replace("^ +","",$xmlStr);
                //$xmlStr = str_replace(" +$","",$xmlStr);
                //$xmlStr = str_replace(" +"," ",$xmlStr);
 
                return $xmlStr;
} */

// Выборка всех записей из таблицы markers
$query = "SELECT * FROM dogovor WHERE lat<>'' and idcategory<>'9'";
$result = mysql_query($query);
if (!$result) {
die('Неверный запрос: '. mysql_error());
}

header("Content-type: text/xml");

// Создание XML-кода, вывод родительского элемента
echo '<markers>';

// Цикл прохода по всем выбранным записи; создание узла для каждой
while ($row = @mysql_fetch_assoc($result)){
	  $query3 = "SELECT * FROM clientcat WHERE clid=".$row['clid'];
	  $result3 = @mysql_query($query3) or die("$query3 <b>failed!</b><br>".mysql_error());
      $client=mysql_result($result3, 0 , "title");
	  if ($row['idcategory']<'1') $row['idcategory']='1';
	  if ($row['idcategory']>'10') $row['idcategory']='1';
	  $query1 = "SELECT * FROM dogcategory WHERE idcategory=".$row['idcategory'];
	  $result1 = @mysql_query($query1) or die("$query1 <b>failed!</b><br>".mysql_error());
	  $categ=mysql_result($result1, 0 , "title");
// Вывод нового узла XML
echo '<marker ';
echo 'did="'. parseToXML($row['did']). '" ';
echo 'clid="'. parseToXML($row['clid']). '" ';
echo 'client="'. parseToXML($client). '" ';
echo 'categ="'. parseToXML($categ). '" ';
echo 'title="'. parseToXML($row['title']). '" ';
echo 'content="'. parseToXML($row['content']). '" ';
echo 'tip="'. parseToXML($row['tip']). '" ';
echo 'kol="'. parseToXML($row['kol']). '" ';
echo 'adres="'. parseToXML($row['adres']). '" ';
echo 'lat="'. $row['lat']. '" ';
echo 'lan="'. $row['lan']. '" ';
echo 'idcategory="'. $row['idcategory']. '" ';
echo '/>';
}

// Конец XML-файла
echo '</markers>';

?>
