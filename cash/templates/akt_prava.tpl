<!DOCTYPE html>
<html lang="ru">
<head>
	<meta content="text/html; charset=utf-8" http-equiv="content-type"/>
	<title>АКТ №{{AktNumber}} от {{AktDate}} года</title>
	{{#forPDF}}
		<STYLE type="text/css">
			<!--
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
				margin           : 10mm 0mm 0 10mm;
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
				/*text-align: justify;*/
				overflow-wrap   : break-word; /* не поддерживает IE, Firefox; является копией word-wrap */
				word-wrap       : break-word;
				word-break      : normal; /* не поддерживает Opera12.14, значение keep-all не поддерживается IE, Chrome */
				line-break      : auto; /* нет поддержки для русского языка */
				-webkit-hyphens : auto;
				-ms-hyphens     : auto;
				hyphens         : auto;
				white-space     : normal;
				width           : 100%;
			}

			@media print {
				body {
					font-size          : 12px;
					background         : #FFFFFF;
					/*margin             : 0;*/
					padding            : 0 0 0 0;
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
				margin : 10px 10px 10px 10px;
			}

			.pull {
				text-align : justify;
			}

			.w193 {
				width : 100%;
			}

			.w176 {
				width : 100%;
			}

			.w60{
				width: 90px;
			}

			@media print {
				body {
					font-size          : 14px;
					background         : #FFFFFF;
					padding            : 0;
					margin             : 10mm 10mm 10mm 10mm;
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

	<h2><strong>АКТ приема-передачи прав №{{AktNumber}} от {{AktDate}} г. </strong></h2>

	<br>

	<hr size="2" width="100%" noshade style="color:black;margin:0;border-bottom:2px solid #000" align="center" class="bb2">

	<br>

	<div class="pull w176" style="width: 100%; display: block">
		{{compUrName}}, именуемый в дальнейшем Лицензиар, в лице {{compDirName}} действующего(ей) на основании {{compDirOsnovanie}}, с одной стороны, и {{castUrName}}, именуемое в дальнейшем Лицензиат, в лице {{castDirStatus}} {{castDirName}}, действующего на основании {{castDirOsnovanie}}, с другой стороны, вместе именуемые Стороны, а индивидуально - Сторона, подписали настоящий акт к {{offer}} {{ContractNumber}} от {{ContractDate}} г. (далее по тексту - Договор), заключенному между Сторонами, о нижеследующем:
	</div>

	<br>

	<div class="pull w176">1. В соответствии с условиями "Договора" "Лицензиар" передал "Лицензиату" права на использование следующего программного обеспечения:</div>

	<div style="margin-top:1.0pt; margin-bottom:15.0pt;">

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
				<td align="right" class="br" style="height:14px">Итого:  </td>
				<td width="81" align="right" class="bb br" style="height:14px"><b>{{AktSumma}}</b></td>
			</tr>
		</table>

	</div>

	<div class="pull w176" style="width: 100%; display: block">2. Права на программное обеспечение переданы в полном объеме способом, предусмотренным "Договором", в сроки, установленные "Договором". {{AktComment}}</div>
	<div class="pull w176" style="width: 100%; display: block">3. "Лицензиат" не имеет к "Лицензиару" каких-либо претензий относительно качества программного обеспечения.</div>
	<div class="pull w176" style="width: 100%; display: block">4. Настоящий акт подписан в 2 (двух) подлинных экземплярах на русском языке по одному для каждой из "Сторон".</div>

	<br>

	<hr size="2" width="100%" noshade style="color:black;margin:0;border-bottom:2px solid #000" align="center" class="bb2">

	<br>
	<br>
	<br>

	<div style="margin-top:1.0pt; margin-bottom:15.0pt;">

		<table width="100%" border="0" cellpadding="0" cellspacing="0" style="border-collapse:collapse">
			<tr>
				<td width="80" valign="top">Лицензиар</td>
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
				<td valign="top">Лицензиат</td>
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