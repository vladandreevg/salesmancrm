<!DOCTYPE html>
<html lang="ru">
<head>
	<meta content="text/html; charset=utf-8" http-equiv="content-type"/>
	<title>АКТ №{{AktNumber}} от {{AktDate}} года</title>
	{{#forPDF}}
		<STYLE type="text/css">
			<!--
			@import url("../../font/ptsansweb/stylesheet.css");

			body {
				font-size        : 12px;
				font-family      : 'PT Sans', arial, tahoma, sans-serif;
				line-height      : 90%;
				background-color : #FFFFFF;
				color            : #000000;
				/*width            : 21cm;
				height           : 29.7cm;
				margin           : 37mm 6mm 27mm 26mm;*/
				width            : 18cm;
				height           : 28.7cm;
				margin           : 10mm 20mm 20mm 10mm;
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

			.w60{
				width: 60px;
			}

			.w176 {
				width : 100%;
			}

			.pull {
				text-align: justify;
				overflow-wrap   : break-word; /* не поддерживает IE, Firefox; является копией word-wrap */
				word-wrap       : break-word;
				word-break      : normal; /* не поддерживает Opera12.14, значение keep-all не поддерживается IE, Chrome */
				line-break      : auto; /* нет поддержки для русского языка */
				-webkit-hyphens : auto;
				-ms-hyphens     : auto;
				hyphens         : auto;
				white-space     : normal;
				width           : 95%;
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
			}

			-->
		</STYLE>
	{{/forPDF}}
	{{#forPRINT}}
		<STYLE type="text/css">
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

			.w60{
				width: 90px;
			}

			@media print {
				body {
					font-size          : 14px;
					background         : #FFFFFF;
					padding            : 0;
					margin             : 0 20px 20px 40px;
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
					width : 17.6cm;
				}

				.pad1 {
					padding : 0;
				}
			}

			-->
		</STYLE>
	{{/forPRINT}}
</head>
<body>
<div class="pad1 w193" style="margin:0 auto;">

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

	<h2><strong>АКТ №{{AktNumber}} от {{AktDate}} г. </strong></h2>

	<hr size="2" width="100%" noshade style="color:black;margin:0;border-bottom:2px solid #000" align="center" class="bb2">

	<br>

	<div style="margin-top:1.0pt; margin-bottom:15.0pt;">

		<table width="100%" border="0" cellpadding="0" cellspacing="0">
			<tr>
				<td width="60" class="pad1">Поставщик:</td>
				<td class="pad1">{{compUrName}}</td>
			</tr>
			<tr>
				<td class="pad1">Покупатель:</td>
				<td class="pad1">
					<b>{{castUrName}}</b>; <b>ИНН: {{castInn}}</b; <b>КПП: {{castKpp}}</b>
				</td>
			</tr>
		</table>

		<br>

		<table width="100%" border="0" cellpadding="4" cellspacing="0">
			<tr>
				<td width="20" align="center" valign="middle" bgcolor="#E9E9E9" style="height:18pt" class="bt br bb bl">№</td>
				<td align="center" valign="middle" bgcolor="#E9E9E9" class="bt bb br">Товары (работы, услуги)</td>
				<td width="50" align="center" valign="middle" bgcolor="#E9E9E9" class="bt bb br">Кол.</td>
				{{#dopName}}
					<td width="70" align="center" valign="middle" bgcolor="#E9E9E9" class="bt bb br">{{dopName}}</td>
				{{/dopName}}
				<td width="30" align="center" valign="middle" bgcolor="#E9E9E9" class="bt bb br">Ед.</td>
				<td width="60" align="center" valign="middle" bgcolor="#E9E9E9" class="bt bb br">Цена</td>
				{{#nalogTitle}}
					<td width="60" align="center" valign="middle" bgcolor="#E9E9E9" class="bt bb br">{{nalogTitle}}</td>
				{{/nalogTitle}}
				<td width="80" align="center" valign="middle" bgcolor="#E9E9E9" class="bt bb br">Сумма</td>
			</tr>
			{{#speka}}
				<tr class="small">
					<td width="20" align="center" class="bt br bb bl">{{Number}}</td>
					<td align="left" valign="middle" class="bb br">
						<div style="display: block; width:96%;">{{#Artikul}}{{Artikul}}{{/Artikul}}   <b>{{Title}}</b>
						</div>
						<em>{{Comments}}</em>
					</td>
					<td width="50" align="right" valign="middle" class="bb br">{{Kol}}</td>
					{{#dopName}}
						<td width="70" align="right" valign="middle" class="bb br">{{Dop}}</td>
					{{/dopName}}
					<td width="30" align="center" valign="middle" class="bb br">{{Edizm}}</td>
					<td width="60" align="right" valign="middle" class="bb br">{{Price}}</td>
					{{#nalogTitle}}
						<td width="60" align="right" valign="middle" class="bb br">{{Nalog}}</td>
					{{/nalogTitle}}
					<td width="80" align="right" valign="middle" class="bb br">{{Summa}}</td>
				</tr>
			{{/speka}}
		</table>

		<table width="100%" border="0" cellpadding="4" cellspacing="0">
			<tr>
				<td class="br" align="right">Итого:  </td>
				<td width="81" align="right" class="bb br" style="height:14px">{{ItogSumma}}</td>
			</tr>
			<tr>
				<td align="right" class="br" style="height:14px">{{nalogName}}:  </td>
				<td width="81" align="right" class="bb br" style="height:14px">{{nalogSumma}}</td>
			</tr>
			<tr>
				<td align="right" class="br" style="height:14px">Всего к оплате:  </td>
				<td width="81" align="right" class="bb br"><b>{{AktSumma}}</b></td>
			</tr>
		</table>

	</div>

	<p style="margin-top:5px;">
		Всего оказано услуг на сумму:    <b>{{AktSumma}}</b> ( {{AktSummaPropis}} )
	</p>

	<p style="margin-top:5.0pt; margin-bottom:20pt">Перечисленные услуги выполнены полностью и в срок. Заказчик претензий по объему, качеству и срокам оказания услуг не имеет.</p>
	<p style="margin-top:5.0pt; margin-bottom:60pt">{{AktComment}}</p>

	<br>

	<hr size="2" width="100%" noshade style="color:black;margin:0;border-bottom:2px solid #000" align="center" class="bb2">

	<br>
	<br>
	<br>

	<div style="margin-top:1.0pt; margin-bottom:15.0pt;">

		<table width="100%" border="0" cellpadding="0" cellspacing="0" style="border-collapse:collapse">
			<tr>
				<td width="80" valign="top">Исполнитель</td>
				<td align="center" class="bb">{{compDirStatus}}</td>
				<td width="19"></td>
				<td width="161" valign="top" class="bb">
					{{^noSignature}}
						<div style="position:relative">
							<div style="position: absolute; z-index: 10; margin-top: 2px; width: 167px; height: 165px; top: -100px; left: 9px;">
								<img src="{{signature}}" width="180">
							</div>
						</div>
					{{/noSignature}}
				</td>
				<td width="19"></td>
				<td width="120" align="center" class="bb">
					{{^noSignature}}{{compDirSignature}}{{/noSignature}}
				</td>
			</tr>
			<tr>
				<td valign="top"></td>
				<td align="center" valign="top"><span style="font-size:10px">должность</span></td>
				<td></td>
				<td align="center" valign="top"><span style="font-size:10px">подпись</span></td>
				<td></td>
				<td align="center" valign="top"><span style="font-size:10px">расшифровка подписи</span></td>
			</tr>
			<tr height="40">
				<td></td>
				<td> </td>
				<td></td>
				<td> </td>
				<td></td>
				<td> </td>
			</tr>
			<tr>
				<td valign="top">Заказчик</td>
				<td align="center" class="bb"> </td>
				<td></td>
				<td valign="top" class="bb"> </td>
				<td></td>
				<td align="center" class="bb"> </td>
			</tr>
			<tr>
				<td></td>
				<td align="center" valign="top"><span style="font-size:10px">должность</span></td>
				<td></td>
				<td align="center" valign="top"><span style="font-size:10px">подпись</span></td>
				<td></td>
				<td align="center" valign="top"><span style="font-size:10px">расшифровка подписи</span></td>
			</tr>
		</table>

	</div>

</div>
</body>
</html>