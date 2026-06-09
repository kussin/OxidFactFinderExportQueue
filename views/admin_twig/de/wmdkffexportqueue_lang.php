<?php

$sLangName = 'Deutsch';

require __DIR__ . '/../../admin/de/wmdk_ffexportqueue_lang.php';
$adminLang = $aLang;

require __DIR__ . '/../../admin/de/module_options.php';
$moduleOptionsLang = $aLang;

$aLang = array_merge($adminLang, $moduleOptionsLang);
