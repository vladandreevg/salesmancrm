<style type="text/css">
<!--
#apDiv1 {
	position:absolute;
	right:2px;
	top:376px;
	z-index:1;
	border-width:3px; 
	border-color:#FFFFFF; 
	border-style:solid
}
-->
</style>
	<div id="apDiv1">
	  <table width="400" border="0" cellpadding="3" cellspacing="0" background="images/bg2.png">
        <tr>
          <td width="60"><strong>Широта:</strong></td>
          <td><input name=lat type=text class="jcontent" id="lat" style="width: 99%; background-color:#FFFFFF"></td>
        </tr>
        <tr>
          <td><strong>Долгота:</strong></td>
          <td><input name=lan type=text class="jcontent" id="lan" style="width: 99%; background-color:#FFFFFF;"></td>
        </tr>
        <tr>
          <td><strong>Адрес:</strong></td>
          <td><input name=adress type=text class="jcontent" id="adress" style="width: 99%; background-color:#FFFFFF;"></td>
        </tr>
      </table>
</div>
<table border="0" cellspacing="0" cellpadding="3">
  <tr>
    <td>Адрес:</td>
    <td width="400"><input type="text" name="sityname" id="sityname" style="width:100%"></td>
    <td><img src="images/buttons/button.png" alt="" width="79" height="20" style="cursor:hand" onClick="getAdress()"></td>
  </tr>
</table>
<table width="100%" border="0" cellspacing="0" cellpadding="1" background="images/bg2.png">
  <tr>
    <td>
<div id="map_canvas" style="width: 100%; height: 400px; border-width:1px; border-color:#CCCCCC; border-style:solid"></div>    
    </td>
  </tr>
</table>
