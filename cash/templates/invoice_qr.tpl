<!--Счет с QRcode-->
<!DOCTYPE html>
<html lang="ru">
<head>
  <meta http-equiv="content-type" content="text/html; charset=utf-8"/>
  <title>Счет</title>
    {{#forPDF}}
  <STYLE type="text/css">
	  body {
		  font-size        : 12px;
		  font-family      : 'PT Sans', arial, tahoma, sans-serif;
		  line-height      : 90%;
		  background-color : #FFFFFF;
		  color            : #000000;
		  width            : 18cm;
		  height           : 28.7cm;
		  margin           : 10mm 10mm 10mm 10mm;
	  }
	  table, td {
		  border-collapse : collapse;
		  font-size       : 12px;
		  line-height     : 90%;
	  }
	  .small td, .small tr {
		  font-size     : 12px;
		  padding-left  : 5px;
		  padding-right : 5px;
	  }
	  .bt {
		  border-top : 1px solid #333;
	  }
	  .bb {
		  border-bottom : 1px solid #333;
	  }
	  .bbn {
		  border-bottom : 0;
	  }
	  .br {
		  border-right : 1px solid #333;
	  }
	  .bl {
		  border-left : 1px solid #333;
	  }
	  h1 {
		  font-size : 22px;
	  }
	  p {
		  padding : 0;
		  margin  : 0;
	  }
	  .print {
		  display : none;
	  }

	  .w60 {
		  width : 60px;
	  }
	  .pull {
		  text-align      : left;
		  overflow-wrap   : break-word; /* не поддерживает IE, Firefox; является копией word-wrap */
		  word-wrap       : break-word;
		  word-break      : normal; /* не поддерживает Opera12.14, значение keep-all не поддерживается IE, Chrome */
		  line-break      : auto; /* нет поддержки для русского языка */
		  -webkit-hyphens : auto;
		  -ms-hyphens     : auto;
		  hyphens         : auto;
		  white-space     : normal;
		  width           : 99%;
	  }

	  @media print {
		  body {
			  font-size          : 12px;
			  background         : #FFFFFF;
			  margin             : 0;
			  padding            : 10px 0 0 0;
			  width              : auto;
			  height             : auto;
			  box-shadow         : 0 0 0 #FFFFFF;
			  -moz-box-shadow    : 0 0 0 #FFFFFF;
			  -webkit-box-shadow : 0 0 0 #FFFFFF;
		  }

		  html {
			  background : #FFFFFF;
			  padding    : 0;
		  }

		  .w193 {
			  width : 100%;
		  }

		  .w176 {
			  width : 100%;
		  }

		  .pad1 {
			  padding : 0;
		  }

		  .print {
			  display    : block;
			  margin-top : 10px;
		  }

		  .w60 {
			  width : 90px;
		  }
	  }
	  -->
  </STYLE>
  {{/forPDF}}
    {{#forPRINT}}
  <style type="text/css">
	  <!--
	  @import url("/assets/font/ptsansweb/stylesheet.css");
	  body {
		  font-size          : 14px;
		  font-family        : 'PT Sans', arial, tahoma, sans-serif;
		  background-color   : #FFF;
		  color              : #000000;
		  box-shadow         : 0 1px 1px #999;
		  -webkit-box-shadow : 0 1px 1px #999;
		  -moz-box-shadow    : 0 1px 1px #999;
		  width              : 21cm;
		  height             : 29.7cm;
		  margin             : 0 auto;
		  padding            : 20px 40px 20px 60px;
	  }
	  html {
		  background : #CCCCCC;
		  padding    : 10px;
	  }
	  table, td {
		  border-collapse : collapse;
		  padding         : 2px;
		  font-size       : 12px;
	  }
	  .small td, .small tr {
		  font-size : 12px;
	  }
	  .bt {
		  border-top : 1px solid #333;
	  }
	  .bb {
		  border-bottom : 1px solid #333;
	  }
	  .br {
		  border-right : 1px solid #333;
	  }
	  .bl {
		  border-left : 1px solid #333;
	  }
	  h1 {
		  font-size   : 22px;
		  line-height : 135%;
	  }
	  h2 {
		  font-size   : 16px;
		  line-height : 115%;
	  }
	  .pad1 {
		  margin : 40px 20px 20px 40px;
	  }
	  .pull {
		  text-align : justify;
	  }
	  .w193 {
		  width : 100%;
	  }
	  .w176 {
		  width : 95%;
	  }

	  .w60 {
		  width : 90px;
	  }
	  @media print {
		  body {
			  font-size          : 14px;
			  background         : #FFFFFF;
			  padding            : 0;
			  margin             : 0 20px 20px 20px;
			  width              : auto;
			  height             : auto;
			  box-shadow         : 0 0 0 #FFFFFF;
			  -moz-box-shadow    : 0 0 0 #FFFFFF;
			  -webkit-box-shadow : 0 0 0 #FFFFFF;
		  }

		  html {
			  background : #FFFFFF;
			  padding    : 0;
		  }

		  .w193 {
			  width : 100%;
		  }

		  .w176 {
			  width : 100%;
		  }

		  .pad1 {
			  padding : 0;
		  }
	  }
	  -->
  </style>
  {{/forPRINT}}
</head>
<body>

<div style="margin:0 auto;" class="w176">

  <div class="print"></div>

  <table width="100%" border="0" cellpadding="0" cellspacing="0">
	<tr>
	  <td><strong>{{compUrName}}</strong></td>
	  <td width="190" align="right">
          {{#logo}}
		<div style="position:relative">
		  <div style="position: absolute; z-index: 10; width: 167px; height: 31px; {{#forPRINT}}top:0;{{/forPRINT}}{{#forPDF}}top:-30px;{{/forPDF}} right:0">
			<img src="{{logo}}" style="max-width:180px; max-height:30px; float:right; height:30px">
		  </div>
		</div>
		{{/logo}}
	  </td>
	</tr>
  </table>

  <br>

  <p style="font-size:8pt;color:black; margin-bottom:0;">Образец заполнения платежного поручения</p>

    {{#forPDF}}
  <table border="0" cellspacing="0" cellpadding="3" width="100%">
	<tr>
	  <td rowspan="6" valign="top" width="100" class="bt br bl bb">
		<img src="{{qrcode}}" width="150">
	  </td>
	  <td colspan="4" rowspan="2" valign="top" class="bt br bl bbn"><b>{{compBankName}}</b></td>
	  <td width="60" class="bt br bb">БИК</td>
	  <td width="100" class="bt br bl">{{compBankBik}}</td>
	</tr>
	<tr>
	  <td class="br">Сч. №</td>
	  <td class="br">{{compBankKs}}</td>
	</tr>
	<tr>
	  <td colspan="4" class="bb br bl">Банк получателя</td>
	  <td class="bb br"></td>
	  <td class="bb br"></td>
	</tr>
	<tr>
	  <td width="20" class="bb br bl">ИНН</td>
	  <td width="100" class="bb br">{{compInn}}</td>
	  <td width="20" class="bb br">КПП</td>
	  <td class="bb br">{{compKpp}}</td>
	  <td width="60" class="br">Сч. №</td>
	  <td class="br">{{compBankRs}}</td>
	</tr>
	<tr>
	  <td colspan="4" class="br bl"><b>{{compUrName}}</b></td>
	  <td class="br"></td>
	  <td class="br"></td>
	</tr>
	<tr>
	  <td colspan="4" class="bb br bl"><span style="font-size:8pt">Получатель</span></td>
	  <td class="bb br"></td>
	  <td class="bb br"></td>
	</tr>
  </table>
    {{/forPDF}}
    {{#forPRINT}}
  <table border="0" cellspacing="0" cellpadding="3" width="100%">
	<tr>
	  <td rowspan="6" valign="top" width="150" class="bt br bl bb">
		<img src="{{qrcode}}" width="150">
	  </td>
	  <td colspan="4" rowspan="2" valign="top" class="bt br bl bbn"><b>{{compBankName}}</b></td>
	  <td width="60" class="bt br bb">БИК</td>
	  <td width="130" class="bt br bl">{{compBankBik}}</td>
	</tr>
	<tr>
	  <td class="br">Сч. №</td>
	  <td class="br">{{compBankKs}}</td>
	</tr>
	<tr>
	  <td colspan="4" class="bb br bl">Банк получателя</td>
	  <td class="bb br"></td>
	  <td class="bb br"></td>
	</tr>
	<tr>
	  <td width="30" class="bb br bl">ИНН</td>
	  <td width="120" class="bb br">{{compInn}}</td>
	  <td width="30" class="bb br">КПП</td>
	  <td class="bb br">{{compKpp}}</td>
	  <td width="60" class="br">Сч. №</td>
	  <td class="br">{{compBankRs}}</td>
	</tr>
	<tr>
	  <td colspan="4" class="br bl"><b>{{compUrName}}</b></td>
	  <td class="br"></td>
	  <td class="br"></td>
	</tr>
	<tr>
	  <td colspan="4" class="bb br bl"><span style="font-size:8pt">Получатель</span></td>
	  <td class="bb br"></td>
	  <td class="bb br"></td>
	</tr>
  </table>
  {{/forPRINT}}

  <br><br>
  <div align="center" style="text-align:center"><h1>Счет {{offer}} №{{Invoice}} от {{InvoiceDate}} г.</h1></div>

  <table width="100%" border="0" cellpadding="0" cellspacing="0">
	<tr>
	  <td width="60" class="pad1">Поставщик:</td>
	  <td class="pad1">{{compUrName}}</td>
	</tr>
	<tr>
	  <td class="pad1">Покупатель:</td>
	  <td class="pad1">
		<b>{{castUrName}}</b>; <b>ИНН: {{castInn}}</b>; <b>КПП: {{castKpp}}</b>
	  </td>
	</tr>
  </table>

  <br>

  <table width="100%" border="0" cellpadding="4" cellspacing="0">
	<tr>
	  <td width="20" align="center" valign="middle" bgcolor="#E9E9E9" style="height:18pt" class="bt br bb bl">№</td>
	  <td align="center" valign="middle" bgcolor="#E9E9E9" class="bt bb br">Товары (работы, услуги)</td>
	  <td width="40" align="center" valign="middle" bgcolor="#E9E9E9" class="bt bb br">Кол.</td>
        {{#dopName}}
	  <td width="30" align="center" valign="middle" bgcolor="#E9E9E9" class="bt bb br">{{dopName}}</td>
	  {{/dopName}}
	  <td width="30" align="center" valign="middle" bgcolor="#E9E9E9" class="bt bb br">Ед.</td>
	  <td width="60" align="center" valign="middle" bgcolor="#E9E9E9" class="bt bb br">Цена</td>
        {{#nalogTitle}}
	  <td width="60" align="center" valign="middle" bgcolor="#E9E9E9" class="bt bb br">{{nalogTitle}}</td>
	  {{/nalogTitle}}
	  <td width="70" align="center" valign="middle" bgcolor="#E9E9E9" class="bt bb br w80">Сумма</td>
	</tr>
      {{#speka}}
	<tr class="small">
	  <td width="20" align="center" class="bt br bb bl">{{Number}}</td>
	  <td align="left" valign="middle" class="bb br">
		<div style="display: block; width:96%;">{{#Artikul}}[{{Artikul}}]  {{/Artikul}}<b>{{Title}}</b></div>
		<em>{{Comments}}</em>
	  </td>
	  <td width="50" align="right" valign="middle" class="bb br">{{Kol}}</td>
        {{#dopName}}
	  <td width="30" align="right" valign="middle" class="bb br">{{Dop}}</td>
	  {{/dopName}}
	  <td width="30" align="center" valign="middle" class="bb br">{{Edizm}}</td>
	  <td width="60" align="right" valign="middle" class="bb br">{{Price}}</td>
        {{#nalogTitle}}
	  <td width="60" align="right" valign="middle" class="bb br">{{Nalog}}</td>
	  {{/nalogTitle}}
	  <td width="70" align="right" valign="middle" class="bb br w80">{{Summa}}</td>
	</tr>
	{{/speka}}
	<tr>
	  <td colspan="3"></td>
        {{#dopName}}
	  <td></td>
	  {{/dopName}}
        {{#nalogTitle}}
	  <td></td>
	  {{/nalogTitle}}
	  <td width="120" colspan="2" class="br" align="right">Итого:</td>
	  <td width="70" align="right" class="bb br" style="height:14px">{{InvoiceSumma}}</td>
	</tr>
	<tr>
	  <td colspan="3"></td>
        {{#dopName}}
	  <td></td>
	  {{/dopName}}
        {{#nalogTitle}}
	  <td></td>
	  {{/nalogTitle}}
	  <td width="120" colspan="2" align="right" class="br" style="height:14px">{{nalogName}}:</td>
	  <td width="70" align="right" class="bb br" style="height:14px">{{nalogSumma}}</td>
	</tr>
	<tr>
	  <td colspan="3"></td>
        {{#dopName}}
	  <td></td>
	  {{/dopName}}
        {{#nalogTitle}}
	  <td></td>
	  {{/nalogTitle}}
	  <td width="120" colspan="2" align="right" class="br" style="height:14px">Всего к оплате:  </td>
	  <td width="70" align="right" class="bb br"><b>{{InvoiceSumma}}</b></td>
	</tr>
  </table>

  <br>
  <br>

  <p style="margin-top:5px;">
	Всего к оплате:    <b>{{InvoiceSummaPropis}}</b>
  </p>

  <hr size="2" width="100%" noshade style="color:black;margin:0" align="center">

  <br>
  <br>
  <br>

  <table width="100%" border="0" cellpadding="0" cellspacing="0" style="border-collapse:collapse">
	<tr>
	  <td width="80" valign="top">Поставщик</td>
	  <td align="center" class="bb">{{compDirStatus}}</td>
	  <td width="161" valign="top" class="bb">
          {{^noSignature}}
		<div style="position:relative">
		  <div style="position: absolute; z-index: 10; margin-top: 2px; width: 167px; height: 165px; top: -100px; left: 9px;">
			<img src="{{signature}}" width="180">
		  </div>
		</div>
		{{/noSignature}}
	  </td>
	  <td width="19" valign="top"></td>
	  <td width="120" align="center" class="bb">
          {{compDirSignature}}
	  </td>
	</tr>
	<tr>
	  <td valign="top"></td>
	  <td align="center" valign="top"><span style="font-size:10px">должность</span></td>
	  <td align="center" valign="top"><span style="font-size:10px">подпись</span></td>
	  <td width="19" valign="top"></td>
	  <td align="center" valign="top"><span style="font-size:10px">расшифровка подписи</span></td>
	</tr>
  </table>

  <br>

  <div style="width: 100%; display: block">
      {{{suffix}}}
  </div>

</div>

</body>
</html>