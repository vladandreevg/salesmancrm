# Внимание

Файлы в этой папке предназначены для DomPDF 0.8.3
На текущий момент эта версия работает, однако требует перенастройки всех шаблонов, т.к. Счета получаются сдвинутыми вправо

Папка "dompdf/dompdf/lib/fonts/" должна иметь права на запись

```php
$options = new \Dompdf\Options();
$options->set('A4','portrait');
$options->set('defaultPaperSize ','A4');
$options->set('fontHeightRatio','1.0');
$options->set('defaultMediaType ','screen');
$options->set('isHtml5ParserEnabled',true);
$options->set('isFontSubsettingEnabled',true);
$options->set('defaultFont','PT Sans');
$options->set('dpi',96);

$dompdf = new \Dompdf\Dompdf($options);
$dompdf -> loadHtml($html);
$dompdf -> render();
$output = $dompdf -> output();
```