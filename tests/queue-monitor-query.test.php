<?php

declare(strict_types=1);

// Isolated query-policy regression for the FACT Finder Monitor.
//
//   php tests/queue-monitor-query.test.php

require dirname(__DIR__) . '/src/Service/QueueSyncSentinel.php';
require dirname(__DIR__) . '/src/Service/QueueMonitorQuery.php';
require dirname(__DIR__) . '/src/Service/QueueMonitorUrl.php';

use Wmdk\FactFinderQueue\Service\QueueMonitorQuery;
use Wmdk\FactFinderQueue\Service\QueueSyncSentinel;
use Wmdk\FactFinderQueue\Service\QueueMonitorUrl;

function assertMonitorValue($expected, $actual, string $message): void
{
    if ($expected !== $actual) {
        fwrite(STDERR, sprintf("FAIL %s: expected %s, got %s\n", $message, var_export($expected, true), var_export($actual, true)));
        exit(1);
    }

    echo "ok   {$message}\n";
}

$filters = QueueMonitorQuery::normalizeFilters([
    'OXID' => 'abc',
    'Channel' => 'wh1_live_de',
    'LASTSYNC' => '2026-10-05',
    'OXTIMESTAMP' => '2026-10-05',
    'OXACTIVE' => '1',
    'HasFromPrice' => '1',
    'FlourSaleAmount' => '20',
    'FlourMSRP' => '100',
    'Price' => '99.99',
    'Stock' => '1',
    'Unknown' => 'ignored',
]);

assertMonitorValue(false, array_key_exists('Price', $filters), 'numeric columns are not filterable');
assertMonitorValue(false, array_key_exists('Unknown', $filters), 'unknown columns are rejected');
assertMonitorValue(false, array_key_exists('LASTSYNC', $filters), 'LASTSYNC is sortable but not filterable');
assertMonitorValue(false, array_key_exists('OXTIMESTAMP', $filters), 'OXTIMESTAMP is sortable but not filterable');
assertMonitorValue(false, array_key_exists('FlourSaleAmount', $filters), 'FlourSaleAmount is not a monitor column');
assertMonitorValue(false, array_key_exists('FlourMSRP', $filters), 'FlourMSRP is sortable but not filterable');
assertMonitorValue('1', $filters['Stock'] ?? null, 'Stock accepts the has-stock selector');
assertMonitorValue('LASTSYNC', QueueMonitorQuery::normalizeSort(''), 'empty sort columns default to LASTSYNC');
assertMonitorValue('LASTSYNC', QueueMonitorQuery::normalizeSort('DROP TABLE queue'), 'invalid sort columns default to LASTSYNC');
assertMonitorValue('DESC', QueueMonitorQuery::normalizeDirection('invalid'), 'invalid sort directions use descending');
assertMonitorValue(5, QueueMonitorQuery::normalizeRefreshInterval(5), 'five-second monitor refresh is allowed');
assertMonitorValue(10, QueueMonitorQuery::normalizeRefreshInterval(10), 'ten-second monitor refresh is allowed');
assertMonitorValue(15, QueueMonitorQuery::normalizeRefreshInterval(15), 'fifteen-second monitor refresh is allowed');
assertMonitorValue(20, QueueMonitorQuery::normalizeRefreshInterval(20), 'twenty-second monitor refresh is allowed');
assertMonitorValue(30, QueueMonitorQuery::normalizeRefreshInterval(30), 'thirty-second monitor refresh is allowed');
assertMonitorValue(15, QueueMonitorQuery::normalizeRefreshInterval(0), 'invalid monitor refresh uses fifteen seconds');
assertMonitorValue(100, QueueMonitorQuery::PAGE_SIZE, 'page size is capped at 100 records');
assertMonitorValue('0000-00-00 00:00:00', QueueSyncSentinel::DATETIME, 'LASTSYNC uses the OXID 6 zero-date marker');
assertMonitorValue('0000-00-00 00:00:00', QueueSyncSentinel::TIMESTAMP, 'OXTIMESTAMP uses the OXID 6 zero-date marker');

$where = QueueMonitorQuery::buildWhere($filters);
assertMonitorValue(true, str_contains($where['sql'], '`OXID` LIKE ?'), 'text filters use LIKE');
assertMonitorValue(true, str_contains($where['sql'], '`HasFromPrice` LIKE ?'), 'VARCHAR flags retain the classic text filter');
assertMonitorValue(true, str_contains($where['sql'], '`Stock` > 0'), 'has-stock filter selects positive stock');
assertMonitorValue(['%abc%', 'wh1_live_de', '1', '%1%'], $where['params'], 'filter parameters preserve display order');

$familyIds = [
    '0c7231735d7865a48d3ed6b84dcaf62f',
    '5146ecbc835288daf329eb33d2d037bb',
    '6ab0d539e0a545ad2e4bf75fc3ba9732',
];
$familyFilters = QueueMonitorQuery::normalizeFilters([
    'OXID' => sprintf(
        "OXID = '%s' OR OXID = '%s' OR OXID = '%s'",
        $familyIds[0],
        $familyIds[1],
        $familyIds[2]
    ),
]);
$familyWhere = QueueMonitorQuery::buildWhere($familyFilters);
assertMonitorValue(implode(', ', $familyIds), $familyFilters['OXID'] ?? null, 'multiple OXIDs are normalized for display');
assertMonitorValue(true, str_contains($familyWhere['sql'], '`OXID` IN (?, ?, ?)'), 'multiple OXIDs use an IN condition');
assertMonitorValue($familyIds, $familyWhere['params'], 'multiple OXIDs remain parameterized');
assertMonitorValue(
    $familyIds,
    QueueMonitorQuery::normalizeOxidList(implode('; ', $familyIds)),
    'multiple OXIDs accept common separators'
);
$deduplicatedFamily = QueueMonitorQuery::normalizeFilters([
    'OXID' => $familyIds[0] . ',' . $familyIds[0],
]);
assertMonitorValue($familyIds[0], $deduplicatedFamily['OXID'] ?? null, 'duplicate OXIDs are collapsed safely');

$noStock = QueueMonitorQuery::buildWhere(['Stock' => '0']);
assertMonitorValue(true, str_contains($noStock['sql'], '`Stock` <= 0'), 'no-stock filter includes zero and negative stock');
assertMonitorValue([], $noStock['params'], 'stock filter does not require a SQL parameter');
assertMonitorValue(
    'IF(`MSRP` > 0, `MSRP`, `Price`) AS `FlourMSRP`',
    QueueMonitorQuery::selectExpression('FlourMSRP'),
    'FlourMSRP uses the same MSRP fallback as the flour export'
);
assertMonitorValue('FlourMSRP', QueueMonitorQuery::normalizeSort('FlourMSRP'), 'FlourMSRP remains sortable');
assertMonitorValue(
    'https://dev1.warehouse-one.de/elvine/nicole-jacke-2025-black-s.html',
    QueueMonitorUrl::absoluteDeeplink(
        'elvine/nicole-jacke-2025-black-s.html',
        'https://dev1.warehouse-one.de/'
    ),
    'relative deeplinks use the current storefront URL'
);
assertMonitorValue(
    'https://example.org/product.html',
    QueueMonitorUrl::absoluteDeeplink('https://example.org/product.html', 'https://dev1.warehouse-one.de/'),
    'absolute HTTP deeplinks remain unchanged'
);
assertMonitorValue('', QueueMonitorUrl::absoluteDeeplink('javascript:alert(1)', 'https://dev1.warehouse-one.de/'), 'unsafe deeplink schemes are rejected');

$waiting = QueueMonitorQuery::buildWaitingCount(true, false, -3);
assertMonitorValue(true, str_contains($waiting['sql'], 'GROUP BY `OXID`'), 'waiting count groups all queue records by article OXID');
assertMonitorValue(false, str_contains($waiting['sql'], 'SUM(`QueueRecords`)'), 'ETA uses the same distinct article count');
assertMonitorValue(true, str_contains($waiting['sql'], "CAST(`LASTSYNC` AS CHAR) IN ('0000-00-00 00:00:00'"), 'waiting count recognizes the zero LASTSYNC marker');
assertMonitorValue(true, str_contains($waiting['sql'], "CAST(`OXTIMESTAMP` AS CHAR) IN ('0000-00-00 00:00:00'"), 'waiting count recognizes the zero OXTIMESTAMP marker');
assertMonitorValue(true, str_contains($waiting['sql'], "'1000-01-01 00:00:00'"), 'waiting count retains the transitional DATETIME marker');
assertMonitorValue(true, str_contains($waiting['sql'], "'1970-01-01 00:00:01'"), 'waiting count retains the transitional TIMESTAMP marker');
assertMonitorValue(
    [1, 0, -3],
    $waiting['params'],
    'waiting count uses export active, hidden, and minimum-stock settings'
);
