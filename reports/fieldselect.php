<?php
/**
 * @license  http://isaler.ru/
 * @author   Vladislav Andreev, http://iandreyev.ru/
 * @charset  UTF-8
 * @version  6.4
 */
?>
<?php
error_reporting(0);
header("Pragma: no-cache");

include "../inc/config.php";
include "../inc/dbconnector.php";
include "../inc/auth.php";
include "../inc/settings.php";
include "../inc/func.php";

$tip = $_REQUEST['tip'];

if($_REQUEST['action']=="get_pole"){

	$pole = $_REQUEST['pole'];
	switch ($pole) {
		case 'iduser':
?>
			<select name="field_query[]" id="field_query[]" style="width:100%">
				<?php
				$query = "SELECT * FROM ".$sqlname."user ".$sort." and identity = '$identity' order by title";
				$result = $db->query($query);
				while ($data_array = $db->fetch($result)){ ?>
				<OPTION value="<?=$data_array['iduser']?>"><?=$data_array['title']?></OPTION>
				<?php } ?>
			</select>
<?php
		break;
		case 'close':
?>
			<select name="field_query[]" id="field_query[]" style="width:100%">
				<OPTION value="no" <?php if($field_query=='no') print "selected"?>>Активна</OPTION>
				<OPTION value="yes" <?php if($field_query=='yes') print "selected"?>>Закрыта</OPTION>
			</select>
<?php
		break;
		case 'idcategory':
?>
			<select name="field_query[]" id="field_query[]" style="width:100%">
			<?php
			$query = "SELECT * FROM ".$sqlname."dogcategory WHERE identity = '$identity' order by title";
			$result = $db->query($query);
			while ($data_array = $db->fetch($result)){ ?>
				<OPTION value="<?=$data_array['idcategory']?>"><?=$data_array['title']?>%</OPTION>
			<?php } ?>
			</select>
<?php
		break;
		case 'sid':
?>
			<select name="field_query[]" id="field_query[]" style="width:100%">
			<?php
			$query = "SELECT * FROM ".$sqlname."dogstatus WHERE identity = '$identity' order by title";
			$result = $db->query($query);
			while ($data_array = $db->fetch($result)){ ?>
				<OPTION value="<?=$data_array['sid']?>"><?=$data_array['title']?></OPTION>
			<?php } ?>
			</select>
<?php
		break;
		case 'tip':
?>
			<select name="field_query[]" id="field_query[]" style="width:100%">
				<OPTION value="">Не заполнено</OPTION>
			<?php
			$query = "SELECT * FROM ".$sqlname."dogtips WHERE identity = '$identity' order by title";
			$result = $db->query($query);
			while ($data_array = $db->fetch($result)){ ?>
				<OPTION value="<?=$data_array['tid']?>"><?=$data_array['title']?></OPTION>
			<?php } ?>
			</select>
<?php
		break;
		case 'direction':
?>
			<select name="field_query[]" id="field_query[]" style="width:100%">
				<OPTION value="">Не заполнено</OPTION>
			<?php
			$query = "SELECT * FROM ".$sqlname."direction WHERE identity = '$identity' order by title";
			$result = $db->query($query);
			while ($data_array = $db->fetch($result)){ ?>
				<OPTION value="<?=$data_array['id']?>"><?=$data_array['title']?></OPTION>
			<?php } ?>
			</select>
<?php
		break;
		case 'partner':
?>
			<select name="field_query[]" id="field_query[]" style="width:100%">
				<OPTION value="">Не заполнено</OPTION>
			<?php
			$query = "SELECT * FROM ".$sqlname."contractor WHERE tip='partner' and identity = '$identity' order by title";
			$result = $db->query($query);
			while ($data_array = $db->fetch($result)){ ?>
				<OPTION value="<?=$data_array['con_id']?>"><?=$data_array['title']?></OPTION>
			<?php } ?>
			</select>
<?php
		break;
		case 'con_id':
?>
			<select name="field_query[]" id="field_query[]" style="width:100%">
			<?php
			$query = "SELECT * FROM ".$sqlname."contractor WHERE tip='contractor' and identity = '$identity' order by title";
			$result = $db->query($query);
			while ($data_array = $db->fetch($result)){ ?>
				<OPTION value="<?=$data_array['con_id']?>"><?=$data_array['title']?></OPTION>
			<?php } ?>
			</select>
<?php
		break;
		case 'mcid':
?>
			<select name="field_query[<?=$i?>]" id="field_query[<?=$i?>]" style="width:100%">
				<?php
			$query = "SELECT * FROM ".$sqlname."mycomps WHERE identity = '$identity' order by name_shot";
			$result = $db->query($query);
			while ($data_array = $db->fetch($result)){ ?>
				<OPTION value="<?=$data_array['id']?>"><?=$data_array['name_shot']?></OPTION>
			<?php } ?>
			</select>
<?php
		break;
		case 'datum':
		case 'datum_plan':
		case 'datum_izm':
		case 'datum_start':
		case 'datum_end':
?>
		<input type="text" id="field_query[]" name="field_query[]" value="<?=$field_query?>" style="width:100%" />
		<script type="text/javascript">
			$(function() {
				$("#field_query\\[\\]").datepicker({ dateFormat: 'yy-mm-dd', firstDay: 1, dayNamesMin: ['Вс','Пн','Вт','Ср','Чт','Пт','Сб'], monthNamesShort: ['Январь','Февраль','Март','Апрель','Май','Июнь','Июль','Август','Сентябрь','Октябрь','Ноябрь','Декабрь'], changeMonth: true, changeYear: true});
			});
		</script>
<?php
		break;
		default:
?>
		<input type="text" id="field_query[]" name="field_query[]" value="<?=$field_query?>" style="width:100%" />
<?php
		break;
	}
}

if($_REQUEST['action']=='get_client'){
?>
<DIV class="zagolovok">Выбор</DIV>
<table width="860" border="0" cellpadding="2" cellspacing="2">
<tr>
	<td colspan="4"><div id="divider" align="center"><b>Фильтры</b></div></td>
</tr>
<tr>
	<td width="160" align="right"><b>Название:</b></td>
	<td width="260"><input name="title_org" type="text" id="title_org" style="width: 97%;" onkeyup="seachClients()" /></td>
	<td width="160" align="right"><b>Территория:</b></td>
	<td>
	<select name="territory_org" id="territory_org" onchange="seachClients()">
		<option value="">--Выбор--</option>
<?php
$query = "SELECT * FROM ".$sqlname."territory_cat WHERE identity = '$identity' ORDER BY title";
$result = $db->query($query);
while ($data_array = $db->fetch($result)){
?>
		<option value="<?=$data_array['idcategory']?>"><?=$data_array['title']?></option>
<?php } ?>
	</select>
	</td>
</tr>
<tr>
	<td align="right"><b>Адрес:</b></td>
	<td><input  name="address_org" rows="3" id="address_org" style="width: 97%;" onkeyup="seachClients()" /></td>
	<td align="right"><b>Телефон:</b></td>
	<td><input name="phone_org" type="text" id="phone_org" style="width: 90%;" onkeyup="seachClients()" /></td>
</tr>
<tr>
	<td align="right"><b>Имеет сайт:</b></td>
	<td>
	<select name="site_url_org" id="site_url_org" onchange="seachClients()">
		<option value="">--Выбор--</option>
		<option value="yes">Есть сайт</option>
		<option value="no">Нет сайта</option>
	</select></td>
	<td align="right"><b>Ответственный:</b></td>
	<td>
	<select name="iduser_org" id="iduser_org" onchange="seachClients()">
		<option value="">--Выбор--</option>
<?php
$query = "SELECT * FROM ".$sqlname."user WHERE identity = '$identity'";
$result = $db->query($query);
while ($data_array = $db->fetch($result)){
?>
		<option value="<?=$data_array['iduser']?>"><?=$data_array['title']?></option>
<?php }?>
	</select></td>
</tr>
<tr>
	<td align="right"><b>Категория:</b></td>
	<td>
	<select name="idcategory_org" id="idcategory_org" onchange="seachClients()">
		<option value="">--Выбор--</option>
<?php
$query = "SELECT * FROM ".$sqlname."category WHERE identity = '$identity' ORDER BY title";
$result = $db->query($query);
while ($data_array = $db->fetch($result)){
?>
		<option <?php if ($data_array['idcategory']==$idcategory) print "selected"; ?> value="<?=$data_array['idcategory']?>"><?=$data_array['title']?></option>
<?php }?>
	</select></td>
	<td align="right"><b>Тип отношений:</b></td>
	<td>
	<select name="tip_cmr_org" id="tip_cmr_org" onchange="seachClients()">
		<option value="">--Выбор--</option>
	<?php
		$result_a = $db->query("SELECT * FROM ".$sqlname."relations WHERE identity = '$identity' ORDER by title");
		while ($data_arraya = $db->fetch($result_a)){
		?>
		<option value="<?=$data_arraya['title']?>"><?=$data_arraya['title']?></option>
	<?php }?>
	</select>
	</td>
</tr>
<tr>
	<td colspan="4"><div id="divider" align="center"><b>Клиенты</b></div></td>
</tr>
<tr>
	<td colspan="2"><b class="green">Выбранные:</b></td>
	<td colspan="2"><b>Результат поиска:</b></td>
</tr>
<tr>
	<td colspan="2" valign="top">
		<select name="client_list[]" size="13" multiple="multiple" id="client_list[]" style="width: 100%; height:200px; background:#8DCBE2; color:#000"></select>
	</td>
	<td colspan="2" align="right" valign="top">
	<select name="client_org" size="13" multiple="multiple" id="client_org" style="width: 100%; height:200px;"></select>
	</td>
</tr>
<tr>
	<td valign="top" nowrap><a href="javascript:void(0)" onclick="removeAll();"><i class="icon-sitemap red"></i>&nbsp;Все</a>&nbsp;<a href="javascript:void(0)" onclick="removeSel();"><i class="icon-commerical-building red"></i>&nbsp;Выбранное</a></td>
	<td align="right" valign="top">Выбрано элементов: <b><span id="sel_value" class="red"></span></b>&nbsp;</td>
	<td valign="top"><a href="javascript:void(0)" onclick="addAll()"><i class="icon-sitemap red"></i>&nbsp;Все</a>&nbsp;<a href="javascript:void(0)" onclick="addSel()"><i class="icon-commerical-building red"></i>&nbsp;Выбранное</a></td>
	<td align="right" valign="top">Всего элементов: <b><span id="all_value" class="red"></span></b>&nbsp;</td>
</tr>
<tr>
	<td colspan="4">&nbsp;</td>
</tr>
</table>
<div align="right">
	<A href="javascript:void(0)" id="sender" onClick="selectClientsRep();" class="button">Сохранить</A>&nbsp;
	<A href="javascript:void(0)" onClick="DClose()" class="button"><SPAN>Отмена</SPAN></A>
</div>
<SCRIPT type="text/javascript">

	$(document).ready( function() {
		$('#dialog').css('width','862px');
		var sell = $('#clients_list\\[\\] option');
		$('#client_list\\[\\]').append(sell);
		seachClients();
		$('#dialog').center();
	});

	function seachClients(){
		$('#client_list\\[\\] option').attr('selected', 'yes');
		var list = $('#client_list\\[\\]').serialize();

		var url = 'content/helpers/client.helpers.php?action=get_clients&report=yes&title='+$('#title_org').val()+'&address='+$('#address_org').val()+'&phone='+$('#phone_org').val()+'&site_url='+$('#site_url_org option:selected').val()+'&category[]='+$('#idcategory_org option:selected').val()+'&territory='+$('#territory_org option:selected').val()+'&iduser='+$('#iduser_org option:selected').val()+'&tip_cmr[]='+urlEncodeData($('#tip_cmr_org option:selected').val())+'&'+list;

		$.get(url, function(data){

			$('#client_org').html(data);

			var s = $('#client_org option').length;
			var t = $('#client_list\\[\\] option').length;
			$('#all_value').html(s);
			$('#sel_value').html(t);

		});

		/*$('#client_org').load(url)
		.ajaxStop(function(){
			var s = $('#client_org option').length;
			var t = $('#client_list\\[\\] option').length;
			$('#all_value').html(s);
			$('#sel_value').html(t);
		});*/

		$('#client_list\\[\\] option').removeAttr('selected');
	}

	function addAll(){
		var sel = $('#client_org option');
		$('#client_list\\[\\]').append(sel);
		$('#client_list\\[\\] option').attr('selected', 'yes');
		seachClients();
	}

	function removeAll(){
		$('#client_list\\[\\] option').remove();
		$('#client_list\\[\\] option').attr('selected', 'yes');
		seachClients();
	}

	function addSel(){
		var sel = $('#client_org option:selected');
		$('#client_list\\[\\]').append(sel);
		$('#client_list\\[\\] option').attr('selected', 'yes');
		seachClients();
	}

	function removeSel(){
		$('#client_list\\[\\] option:selected').remove();
		$('#client_list\\[\\] option').attr('selected', 'yes');
		seachClients();
	}

	function selectClientsRep(){
		var sel = $('#client_list\\[\\] option');
		$('#clients_list\\[\\]').append(sel);
		DClose();
	}
</SCRIPT>
<?php
}
if($_REQUEST['action']=='get_person'){
?>
<DIV class="zagolovok">Выбор</DIV>
<table width="860" border="0" cellpadding="2" cellspacing="2">
<tr>
	<td colspan="4"><div id="divider" align="center"><b>Фильтр</b></div></td>
</tr>
<tr>
	<td width="160" align="right"><b>Ф.И.О.:</b></td>
	<td width="260"><input name="person_p" type="text"  id="person_p" style="width: 97%;" onkeyup="SeachPerson()" /></td>
	<td width="160" align="right"><b>Лояльность:</b></td>
	<td>
	<select name="loyalty_p" id="loyalty_p" onchange="SeachPerson()">
		<option value="">--Выбор--</option>
<?php
$query = "SELECT * FROM ".$sqlname."loyal_cat WHERE identity = '$identity'";
$result = $db->query($query);
while ($data_array = $db->fetch($result)){
 ?>
		<option <?php if ($data_array['idcategory']==$loyalty) print "selected"; ?> value="<?=$data_array['idcategory']?>"><?=$data_array['title']?></option>
<?php }?>
	</select>
	</td>
</tr>
<tr>
	<td align="right"><b>Телефон:</b></td>
	<td><input name="tel_p" type="text"  id="tel_p" style="width: 97%;" onkeyup="SeachPerson()" /></td>
	<td align="right"><b>Роль:</b></td>
	<td><input name="rol_p" id="rol_p" type="text" style="width:90%" class="ac_input" autocomplete="on" onkeyup="SeachPerson()" /></td>
</tr>
<tr>
	<td align="right"><b>Факс:</b></td>
	<td><input name="fax_p" type="text"  id="fax_p" style="width: 97%;" onkeyup="SeachPerson()" /></td>
	<td align="right"><b>Ответственный:</b></td>
	<td>
	<select name="iduser_p" id="iduser_p" onchange="SeachPerson()">
		<option value="">--Выбор--</option>
<?php
$query = "SELECT * FROM ".$sqlname."user WHERE identity = '$identity'";
$result = $db->query($query);
while ($data_array = $db->fetch($result)){
?>
		<option <?php if ($data_array['iduser']==$iduser) print "selected"; ?> value="<?=$data_array['iduser']?>"><?=$data_array['title']?></option>
<?php }?>
	</select></td>
</tr>
<tr>
	<td colspan="4"><div id="divider" align="center"><b>Контакты</b></div></td>
</tr>
<tr>
	<td colspan="2"><b class="green">Выбранные:</b></td>
	<td colspan="2"><b>Результат поиска:</b></td>
</tr>
<tr>
	<td colspan="2" valign="top">
<select name="person_list[]" size="13" multiple="multiple" id="person_list[]" style="width: 100%; height:200px; background:#8DCBE2; color:#000">
<?php
if($person_list!=''){
$person = explode(";", $person_list);
$countp = count($person);
for($i=0;$i<$countp;$i++){
	$result_p = $db->query("select * from ".$sqlname."personcat where pid='".$person[$i]." and identity = '$identity'");
	$persona=$db->fetchnorm($result_p, 0 , "person");
?>
<option value="<?=$person[$i]?>"><?=$persona?></option>
<?php }
}
?>
</select>
	</td>
	<td colspan="2" align="right" valign="top"><select name="person_org" size="13" multiple="multiple" id="person_org" style="width: 100%; height:200px;"></select></td>
</tr>
<tr>
	<td valign="top"><a href="javascript:void(0)" onclick="removeAllp();"><i class="icon-sitemap red"></i>&nbsp;Все</a>&nbsp;<a href="javascript:void(0)" onclick="removeSelp();"><i class="icon-commerical-building red"></i>&nbsp;Выбранное</a></td>
	<td align="right" valign="top">Выбрано элементов: <b><span id="sel_value_p" class="red"></span></b>&nbsp;</td>
	<td valign="top"><a href="javascript:void(0)" onclick="addAllp()"><i class="icon-sitemap red"></i>&nbsp;Все</a>&nbsp;<a href="javascript:void(0)" onclick="addSelp()"><i class="icon-commerical-building red"></i>&nbsp;Выбранное</a></td>
	<td align="right" valign="top">Всего элементов: <b><span id="all_value_p" class="red"></span></b>&nbsp;</td>
</tr>
<tr>
	<td colspan="4">&nbsp;</td>
</tr>
</table>
<div align="right"><A href="javascript:void(0)" id="sender" onClick="selectPersonsRep();" class="button"><SPAN>Сохранить</SPAN></A>&nbsp;<A href="javascript:void(0)" onClick="DClose()" class="button"><SPAN>Отмена</SPAN></A></div><br />
<SCRIPT type="text/javascript">

	$(document).ready( function() {
		$('#dialog').css('width','862px');
		var sell = $('#persons_list\\[\\] option');
		$('#person_list\\[\\]').append(sell);
		SeachPerson();
	});

	function SeachPerson(){
		$('#person_list\\[\\] option').attr('selected', 'yes');
		var list = $('#person_list\\[\\]').serialize();
		var url = 'content/card/person.helpers.php?action=get_clients&report=yes&person='+$('#person_p').val()+'&loyalty='+$('#loyalty_p').val()+'&tel='+$('#tel_p').val()+'&rol='+$('#rol_p').val()+'&fax='+$('#fax_p').val()+'&iduser='+$('#iduser_p option:selected').val()+'&'+list;
		$('#person_org').load(url)
		.ajaxStop(function(){
			var s = $('#person_org option').size();
			var t = $('#person_list\\[\\] option').size();
			$('#all_value_p').html(s);
			$('#sel_value_p').html(t);
		});
		$('#person_list\\[\\] option').removeAttr('selected');
		//alert(list);
	}

	function addAllp(){
		var sel = $('#person_org option');
		$('#person_list\\[\\]').append(sel);
		$('#person_list\\[\\] option').attr('selected', 'yes');
		SeachPerson();
	}

	function removeAllp(){
		$('#person_list\\[\\] option').remove();
		$('#person_list\\[\\] option').attr('selected', 'yes');
		SeachPerson();
	}

	function addSelp(){
		var sel = $('#person_org option:selected');
		$('#person_list\\[\\]').append(sel);
		$('#person_list\\[\\] option').attr('selected', 'yes');
		SeachPerson();
	}

	function removeSelp(){
		$('#person_list\\[\\] option:selected').remove();
		$('#person_list\\[\\] option').attr('selected', 'yes');
		SeachPerson();
	}

	function selectPersonsRep(){
		var sel = $('#person_list\\[\\] option');
		$('#persons_list\\[\\]').append(sel);
		DClose();
	}
</SCRIPT>
<?php
}
?>