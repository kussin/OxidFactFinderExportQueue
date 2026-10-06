<?php

$sLangName = 'English';

require __DIR__ . '/../../admin/en/wmdk_ffexportqueue_lang.php';
$adminLang = $aLang;

require __DIR__ . '/../../admin/en/module_options.php';
$moduleOptionsLang = $aLang;

$aLang = array_merge($adminLang, $moduleOptionsLang);
