<?php

declare(strict_types=1);

namespace Wmdk\FactFinderQueue\Service;

final class QueueMonitorQuery
{
    public const PAGE_SIZE = 100;
    public const DEFAULT_REFRESH_INTERVAL = 15;

    private const REFRESH_INTERVALS = [5, 10, 15, 20, 30];

    public const DISPLAY_COLUMNS = [
        'OXID', 'Channel', 'LASTSYNC', 'OXACTIVE', 'OXHIDDEN', 'OXTIMESTAMP',
        'ProductNumber', 'MasterProductNumber', 'EAN', 'Marke', 'Title', 'HasFromPrice',
        'FromPrice', 'Price', 'MSRP', 'Tax', 'FlourPrice', 'FlourMSRP', 'Stock', 'Deeplink',
    ];

    private const TEXT_FILTER_COLUMNS = [
        'OXID', 'ProductNumber', 'MasterProductNumber', 'EAN', 'Marke', 'Title', 'HasFromPrice',
    ];

    private const BOOLEAN_FILTER_COLUMNS = ['OXACTIVE', 'OXHIDDEN'];

    private const ENUM_FILTER_COLUMNS = ['Channel'];

    /**
     * @param mixed $input
     *
     * @return array<string, string>
     */
    public static function normalizeFilters($input): array
    {
        if (!is_array($input)) {
            return [];
        }

        $filters = [];

        foreach (self::DISPLAY_COLUMNS as $column) {
            if (!array_key_exists($column, $input) || !is_scalar($input[$column])) {
                continue;
            }

            $value = trim((string) $input[$column]);
            $type = self::filterType($column);

            if ($value === '' || $type === 'none') {
                continue;
            }

            if ($column === 'OXID') {
                $articleIds = self::normalizeOxidList($value);

                if ($articleIds !== [] && (count($articleIds) > 1 || $value !== $articleIds[0])) {
                    $value = implode(', ', $articleIds);
                }
            }

            if (in_array($type, ['boolean', 'stock'], true) && !in_array($value, ['0', '1'], true)) {
                continue;
            }

            $filters[$column] = $value;
        }

        return $filters;
    }

    /**
     * @param array<string, string> $filters
     *
     * @return array{sql: string, params: list<string>}
     */
    public static function buildWhere(array $filters): array
    {
        $conditions = [];
        $params = [];

        foreach (self::normalizeFilters($filters) as $column => $value) {
            switch (self::filterType($column)) {
                case 'boolean':
                case 'enum':
                    $conditions[] = sprintf('`%s` = ?', $column);
                    $params[] = $value;
                    break;

                case 'text':
                    if ($column === 'OXID') {
                        $articleIds = self::normalizeOxidList($value);

                        if (count($articleIds) > 1) {
                            $conditions[] = sprintf(
                                '`OXID` IN (%s)',
                                implode(', ', array_fill(0, count($articleIds), '?'))
                            );
                            array_push($params, ...$articleIds);
                            break;
                        }
                    }

                    $conditions[] = sprintf('`%s` LIKE ?', $column);
                    $params[] = '%' . $value . '%';
                    break;

                case 'stock':
                    $conditions[] = $value === '1' ? '`Stock` > 0' : '`Stock` <= 0';
                    break;
            }
        }

        return [
            'sql' => $conditions === [] ? '' : ' WHERE ' . implode(' AND ', $conditions),
            'params' => $params,
        ];
    }

    /**
     * Count article OXIDs which meet the same queue constraints as the export.
     *
     * The inner query intentionally mirrors the operational SQL documented for
     * the monitor. The outer COUNT turns its one-row-per-OXID result into the single value used by
     * both the status card and the ETA calculation.
     *
     * @return array{sql: string, params: array{int, int, int}}
     */
    public static function buildWaitingCount(bool $onlyActive, bool $hidden, int $stockMin): array
    {
        return [
            'sql' => 'SELECT COUNT(*) FROM ('
                . 'SELECT COUNT(*) AS `QueueRecords` FROM `wmdk_ff_export_queue` '
                . 'WHERE (' . QueueSyncSentinel::isUnsyncedSql('`LASTSYNC`') . ') '
                . 'AND (`OXACTIVE` = ?) '
                . 'AND (`OXHIDDEN` = ?) '
                . 'AND (' . QueueSyncSentinel::isUnsyncedSql('`OXTIMESTAMP`') . ') '
                . 'AND (`Stock` >= ?) '
                . 'GROUP BY `OXID`'
                . ') AS `WaitingArticles`',
            'params' => [
                (int) $onlyActive,
                (int) $hidden,
                $stockMin,
            ],
        ];
    }

    public static function normalizeSort(string $column): string
    {
        return in_array($column, self::DISPLAY_COLUMNS, true) ? $column : 'LASTSYNC';
    }

    public static function normalizeDirection(string $direction): string
    {
        return strtoupper($direction) === 'ASC' ? 'ASC' : 'DESC';
    }

    public static function normalizeRefreshInterval(int $interval): int
    {
        return in_array($interval, self::REFRESH_INTERVALS, true)
            ? $interval
            : self::DEFAULT_REFRESH_INTERVAL;
    }

    /** @return list<string> */
    public static function normalizeOxidList(string $value): array
    {
        preg_match_all(
            '/(?<![A-Za-z0-9_-])[A-Za-z0-9_-]{32}(?![A-Za-z0-9_-])/',
            trim($value),
            $matches
        );

        return array_values(array_unique($matches[0] ?? []));
    }

    public static function selectExpression(string $column): string
    {
        if ($column === 'FlourMSRP') {
            return 'IF(`MSRP` > 0, `MSRP`, `Price`) AS `FlourMSRP`';
        }

        return '`' . self::normalizeSort($column) . '`';
    }

    public static function filterType(string $column): string
    {
        if ($column === 'Stock') {
            return 'stock';
        }

        if (in_array($column, self::TEXT_FILTER_COLUMNS, true)) {
            return 'text';
        }

        if (in_array($column, self::BOOLEAN_FILTER_COLUMNS, true)) {
            return 'boolean';
        }

        if (in_array($column, self::ENUM_FILTER_COLUMNS, true)) {
            return 'enum';
        }

        return 'none';
    }
}
