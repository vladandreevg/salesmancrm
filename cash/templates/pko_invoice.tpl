<!--Приходный кассовый ордер-->
<!--Базовый шаблон-->
<!DOCTYPE html>
<html lang="ru">
<head>
	<meta http-equiv="content-type" content="text/html; charset=utf-8"/>
	<title>Счет</title>
	{{#forPDF}}
	<STYLE type="text/css">
		body {
			font-size        : 8pt;
			font-family      : 'PT Sans', arial, tahoma, sans-serif;
			line-height      : 90%;
			background-color : #FFFFFF;
			color            : #000000;
			width            : 18cm;
			height           : 28.7cm;
			margin           : 10mm 10mm 10mm 20mm;
		}
		table, td {
			border-collapse : collapse;
			font-size       : 12px;
			line-height     : 90%;
		}
		.block{
			text-align: justify;
			font-style: normal;
			margin-top: 0.196in;
			width: 7.036in;
			margin-left: 0in;
			font-size: 10pt;
			cursor: default;
			font-weight: 400;
		}
		.mt0{
			margin-top:0;
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
		@import url("../../font/ptsansweb/stylesheet.css");
		body {
			font-size          : 8pt;
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
		h1 {
			font-size   : 22px;
			line-height : 135%;
		}
		h2 {
			font-size   : 16px;
			line-height : 115%;
		}
		.block{
			text-align: justify;
			font-style: normal;
			margin-top: 0.196in;
			width: 7.036in;
			margin-left: 0in;
			font-size: 10pt;
			cursor: default;
			font-weight: 400;
		}
		.mt0{
			margin-top:0;
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
		}
		-->
	</style>
	{{/forPRINT}}
</head>
<body>

<div style="margin:0 auto;" class="w176">

	
<div class="block">
	<table border="0" cellpadding="1" cellspacing="0" style="table-layout: fixed" width="648">
	<tbody>
	<tr>
		<td width="44"></td>
		<td width="28"></td>
		<td width="68"></td>
		<td width="68"></td>
		<td width="48"></td>
		<td width="37"></td>
		<td width="59"></td>
		<td width="14"></td>
		<td width="38"></td>
		<td width="40"></td>
		<td width="20"></td>
		<td width="188"></td>
	</tr>
	<tr style="height: 14px;">
		<td colspan="10" style="text-align: right; font-size: 8pt;">Форма № КО-1</td>
		<td style="font-size: 10pt;"></td>
		<td rowspan="2" valign="bottom" style="border-bottom: #000 1px solid; text-align: center; font-size: 8pt;"><b>{{compShotName}}</b></td>
	</tr>
	<tr style="height: 14px;">
		<td colspan="10" style="text-align: right; font-size: 8pt;">Утверждена Постановлением Госкомстата</td>
		<td></td>
	</tr>
	<tr style="height: 14px;">
		<td colspan="10" style="text-align: right; font-size: 8pt;">России от 18.08.98 г. № 88</td>
		<td></td>
		<td style="text-align: center; font-size: 8pt; vertical-align: top;"></td>
	</tr>
	<tr style="height: 14px;">
		<td colspan="6" rowspan="3" valign="bottom" style="border-bottom: #000 1px solid; text-align: center; font-size: 8pt;"><b>{{compUrName}}</b></td>
		<td colspan="4"></td>
		<td></td>
		<td style="text-align: center; font-size: 8pt;"><b>КВИТАНЦИЯ</b></td>
	</tr>
	<tr style="height: 15px;">
		<td colspan="2"></td>
		<td colspan="2" style="text-align: center; border-left: #000 1px solid; font-size: 8pt; border-top: #000 1px solid; border-right: #000 1px solid">Код</td>
		<td></td>
		<td rowspan="2" style="text-align: center; font-size: 8pt;">к приходному кассовому<br /> ордеру №{{Invoice}}</td>
	</tr>
	<tr style="height: 18px;">
		<td colspan="2" style="text-align: right; font-size: 7pt;">Форма по ОКУД</td>
		<td colspan="2" style="border-bottom: #000 1px solid; text-align: center; border-left: #000 2px solid; font-size: 8pt; border-top: #000 2px solid; border-right: #000 2px solid">0310001</td>
		<td></td>
	</tr>
	<tr style="height: 18px;">
		<td colspan="6" style="text-align: center; font-size: 7pt; vertical-align: top;">предприятие, организация</td>
		<td colspan="2" style="text-align: right; font-size: 7pt;">По ОКПО</td>
		<td colspan="2" style="border-bottom: #000 2px solid; text-align: center; border-left: #000 2px solid; font-size: 8pt; border-right: #000 2px solid">49140741</td>
		<td></td>
		<td rowspan="8" valign="top" style="text-align:left">
			<br>
			<div>Принято от: <b>{{castUrName}}</b></div><br>
			<div>Основание: <b>Расходная накладная {{ContractNumber}}</b></div><br>
			<div>Сумма: <b>{{InvoiceSummaPropis}}</b></div><br>
			<div>В том числе: <b>НДС 18% - {{nalogSumma}} руб.</b></div><br>
		</td>
	</tr>
	<tr style="height: 18px;">
		<td colspan="6"></td>
		<td colspan="2"></td>
		<td colspan="2"></td>
		<td></td>
	</tr>
	<tr style="height: 18px;">
		<td colspan="6"></td>
		<td colspan="2"></td>
		<td colspan="2"></td>
		<td></td>
	</tr>
	<tr style="height: 16px;">
		<td colspan="6" style="text-align: center; font-size: 8pt;"><b>ПРИХОДНЫЙ КАССОВЫЙ ОРДЕР</b></td>
		<td colspan="2" style="text-align: center; border-left: #000 1px solid; font-size: 7pt; vertical-align: top; border-top: #000 1px solid; border-right: #000 1px solid">Номер документа</td>
		<td colspan="2" style="text-align: center; font-size: 7pt; border-top: #000 1px solid; border-right: #000 1px solid">Дата<br> составления</td>
		<td></td>
	</tr>
	<tr style="height: 18px;">
		<td colspan="6"></td>
		<td colspan="2" style="text-align: center; font-size: 8pt; vertical-align: top; border: solid; border: #000 2px solid">{{Invoice}}</td>
		<td colspan="2" style="border-bottom: #000 2px solid; text-align: center; font-size: 8pt; border-top: #000 2px solid; border-right: #000 2px solid"><b>{{InvoiceDateShort}}</b></td>
		<td></td>
	</tr>
	<tr style="height: 18px;">
		<td colspan="10"></td>
		<td></td>
	</tr>
	<tr style="height: 18px;">
		<td rowspan="2" style="text-align: center; border-left: #000 1px solid; font-size: 7pt; border-top: #000 1px solid;">Дебет</td>
		<td colspan="4" style="text-align: center; border-left: #000 1px solid; font-size: 7pt; border-top: #000 1px solid;">Кредит</td>
		<td colspan="2" rowspan="2" style="text-align: center; border-left: #000 1px solid; font-size: 7pt; border-top: #000 1px solid;">Сумма, руб. коп.</td>
		<td colspan="2" rowspan="2" style="text-align: center; border-left: #000 1px solid; font-size: 7pt; border-top: #000 1px solid;">Код целевого назначения</td>
		<td rowspan="2" style="text-align: center; border-left: #000 1px solid; font-size: 7pt; border-top: #000 1px solid; border-right: #000 1px solid"></td>
		<td></td>
	</tr>
	<tr style="height: 42px;">
		<td style="text-align: center; border-left: #000 1px solid; border-top: #000 1px solid;"></td>
		<td style="text-align: center; border-left: #000 1px solid; font-size: 7pt; border-top: #000 1px solid;">код структур- ного подраз- деления</td>
		<td style="text-align: center; border-left: #000 1px solid; font-size: 7pt; border-top: #000 1px solid;">корреспонди- рующий счет, субсчет</td>
		<td style="text-align: center; border-left: #000 1px solid; font-size: 7pt; border-top: #000 1px solid;">код аналити-ческого учета</td>
		<td></td>
	</tr>
	<tr style="height: 26px;">
		<td style="border-bottom: #000 2px solid; text-align: center; border-left: #000 2px solid; font-size: 9pt; border-top: #000 2px solid;">50.1</td>
		<td style="border-bottom: #000 2px solid; text-align: center; border-left: #000 1px solid; font-size: 10pt; border-top: #000 2px solid;"></td>
		<td style="border-bottom: #000 2px solid; text-align: center; border-left: #000 1px solid; font-size: 9pt; border-top: #000 2px solid;"></td>
		<td style="border-bottom: #000 2px solid; text-align: center; border-left: #000 1px solid; font-size: 9pt; border-top: #000 2px solid;">62.1</td>
		<td style="border-bottom: #000 2px solid; text-align: center; border-left: #000 1px solid; font-size: 9pt; border-top: #000 2px solid;"></td>
		<td colspan="2" style="border-bottom: #000 2px solid; text-align: center; border-left: #000 1px solid; font-size: 7pt; border-top: #000 2px solid;">{{InvoiceSumma}}</td>
		<td colspan="2" style="border-bottom: #000 2px solid; text-align: center; border-left: #000 1px solid; font-size: 9pt; border-top: #000 2px solid;"></td>
		<td style="border-bottom: #000 2px solid; border-left: #000 1px solid; font-size: 7pt; border-top: #000 2px solid; border-right: #000 2px solid"></td>
		<td style="font-size: 10pt;"></td>
		<td style="border-bottom: #000 1px solid; font-size: 8pt; vertical-align: bottom; text-align:right"> {{InvoiceDate}}</td>
	</tr>
	</tbody>
	</table>
</div>

<div class="block mt0">
	<table border="0" cellpadding="1" cellspacing="0" style="table-layout: fixed" width="648">
	<tbody>
	<tr>
		<td width="463"></td>
		<td width="20"></td>
		<td width="192"></td>
	</tr>
	<tr>
		<td>
			<br>
			<div>Принято от: <b>{{castUrName}}</b><br></div>
			<div>Основание: <b>Расходная накладная {{ContractNumber}}</b><br></div>
			<div>Сумма: <b>{{InvoiceSummaPropis}}</b><br></div><br>
			<div>В том числе: <b>НДС 18% - {{nalogSumma}} руб.</b></div><br>
		</td>
		<td></td>
		<td valign="bottom">
		<div>М.П. (штампа)</div><br><br>
			<table width="100%">
			<tr>
				<td><b>Главный бухгалтер</b></td>
			</tr>
			<tr>
				<td style="border-bottom: #000 1px solid;">{{compDirSignature}}</td>
			</tr>
			<tr>
				<td><span style="font-size:7pt">расшифровка подписи</span></td>
			</tr>
			</table>
		</td>
	</tr>
	</tbody>
	</table>
</div>

<div class="block mt0">
	<table border="0" cellpadding="1" cellspacing="0" style="font-family: Arial; table-layout: fixed" width="648">
	<tbody>
	<tr>
		<td style="width: 122px; font-size: 10pt;"></td>
		<td style="width: 134px; font-size: 10pt;"></td>
		<td style="width: 33px; font-size: 10pt;"></td>
		<td style="width: 155px; font-size: 10pt;"></td>
		<td style="width: 36px; font-size: 10pt;"></td>
		<td style="width: 188px; font-size: 10pt;"></td>
	</tr>
	<tr>
		<td style="font-size: 8pt; font-weight: 700">Главный бухгалтер</td>
		<td style="border-bottom: #000 1px solid; font-size: 10pt;"></td>
		<td style="font-size: 10pt;"></td>
		<td style="border-bottom: #000 1px solid; text-align: right; font-size: 8pt; text-align: center;"><b>{{compDirSignature}}</b></td>
		<td style="font-size: 10pt;"></td>
		<td></td>
	</tr>
	<tr>
		<td style="font-size: 10pt;"></td>
		<td style="text-align: center; font-size: 7pt; vertical-align: top;">подпись</td>
		<td style="font-size: 10pt;"></td>
		<td style="text-align: center; font-size: 7pt; vertical-align: top;">расшифровка подписи</td>
		<td style="font-size: 10pt;"></td>
		<td><b>Кассир</b></td>
	</tr>
	<tr>
		<td style="font-size: 8pt; font-weight: 700">Получил кассир</td>
		<td style="border-bottom: #000 1px solid; font-size: 10pt;"></td>
		<td style="font-size: 10pt;"></td>
		<td style="border-bottom: #000 1px solid; text-align: right; font-size: 8pt; text-align: center;"><b>{{compDirSignature}}</b></td>
		<td style="font-size: 10pt;"></td>
		<td style="border-bottom: #000 1px solid; font-size: 8pt;">{{compDirSignature}}</td>
	</tr>
	<tr>
		<td style="font-size: 10pt;"></td>
		<td style="text-align: center; font-size: 7pt; vertical-align: top;">подпись</td>
		<td style="font-size: 10pt;"></td>
		<td style="text-align: center; font-size: 7pt; vertical-align: top;">расшифровка подписи</td>
		<td style="font-size: 10pt;"></td>
		<td style="font-size: 7pt; vertical-align: top;">подпись расшифровка подписи</td>
	</tr>
	</tbody>
	</table>
</div>


</div>

</body>
</html>