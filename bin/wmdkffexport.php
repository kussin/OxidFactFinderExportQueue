<?php

require_once dirname(__FILE__) . '/../bootstrap.php';

function wmdkffexport_usage(int $exitCode = 1): void
{
    $usage = <<<TXT
Usage:
  php source/bin/wmdkffexport.php <action> [options]

Actions:
  queue
  reset
  export
  ts
  sooqr
  doofinder
  flour

Options:
  --channel=<channel>   (required for export/ts/sooqr/doofinder/flour)
  --shop-id=<id>        (required for export/sooqr/doofinder/flour)
  --lang=<lang>         (required for export/sooqr/doofinder/flour)
  --flour-id=<id>       (optional for flour)

Examples:
  php source/bin/wmdkffexport.php queue
  php source/bin/wmdkffexport.php reset
  php source/bin/wmdkffexport.php export --channel=wh1_live_de --shop-id=1 --lang=0
  php source/bin/wmdkffexport.php export --channel=wh1_live_en --shop-id=1 --lang=1
  php source/bin/wmdkffexport.php ts --channel=wh1_live_de
  php source/bin/wmdkffexport.php sooqr --channel=wh1_live_de --shop-id=1 --lang=0
  php source/bin/wmdkffexport.php doofinder --channel=wh1_live_de --shop-id=1 --lang=0
  php source/bin/wmdkffexport.php flour --channel=wh1_live_de --shop-id=1 --lang=0 --flour-id=1
TXT;

    fwrite(STDERR, $usage . PHP_EOL);
    exit($exitCode);
}

$action = $argv[1] ?? null;
if ($action === null || in_array($action, ['-h', '--help', 'help'], true)) {
    wmdkffexport_usage(0);
}

$options = getopt('', ['channel:', 'shop-id:', 'lang:', 'flour-id::']);

$controllerMap = [
    'queue' => 'wmdkffexport_queue',
    'reset' => 'wmdkffexport_reset',
    'export' => 'wmdkffexport_export',
    'ts' => 'wmdkffexport_ts',
    'sooqr' => 'wmdkffexport_sooqr',
    'doofinder' => 'wmdkffexport_doofinder',
    'flour' => 'wmdkffexport_flour',
];

if (!isset($controllerMap[$action])) {
    wmdkffexport_usage();
}

$params = ['cl' => $controllerMap[$action]];
$required = [];

switch ($action) {
    case 'export':
    case 'sooqr':
    case 'doofinder':
    case 'flour':
        $required = ['channel', 'shop-id', 'lang'];
        break;
    case 'ts':
        $required = ['channel'];
        break;
}

foreach ($required as $key) {
    if (!isset($options[$key]) || $options[$key] === '') {
        fwrite(STDERR, 'Missing required option: --' . $key . PHP_EOL);
        wmdkffexport_usage();
    }
}

if (isset($options['channel'])) {
    $params['channel'] = $options['channel'];
}
if (isset($options['shop-id'])) {
    $params['shop_id'] = $options['shop-id'];
}
if (isset($options['lang'])) {
    $params['lang'] = $options['lang'];
}
if ($action === 'flour' && isset($options['flour-id']) && $options['flour-id'] !== false) {
    $params['flour_id'] = $options['flour-id'];
}

$_GET = array_merge($_GET, $params);
$_REQUEST = array_merge($_REQUEST, $params);
$_SERVER['REQUEST_METHOD'] = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$_SERVER['DOCUMENT_ROOT'] = $_SERVER['DOCUMENT_ROOT'] ?? realpath(__DIR__ . '/..');
$_SERVER['SCRIPT_NAME'] = $_SERVER['SCRIPT_NAME'] ?? '/index.php';
$_SERVER['REQUEST_URI'] = $_SERVER['REQUEST_URI'] ?? '/index.php?' . http_build_query($_GET);
$_SERVER['SCRIPT_URI'] = $_SERVER['SCRIPT_URI'] ?? $_SERVER['REQUEST_URI'];
$_SERVER['HTTP_HOST'] = $_SERVER['HTTP_HOST'] ?? 'localhost';
$_SERVER['REMOTE_ADDR'] = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';

OxidEsales\EshopCommunity\Core\Oxid::run();

$myConfig = OxidEsales\Eshop\Core\Registry::getConfig();
$myConfig->pageClose();
