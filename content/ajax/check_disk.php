<?php
/* ============================ */
/*         SalesMan CRM         */
/* ============================ */
/* (C) 2018 Vladislav Andreev   */
/*       SalesMan Project       */
/*        www.isaler.ru         */
/*        ver. 2018.x           */
/* ============================ */

$myDir = realpath(__DIR__.'/../..')."/files/".$fpath;

if (PHP_OS != "Linux") $myDir = str_replace("/","\\",$myDir);

$diskUsage = getFileLimit($myDir);