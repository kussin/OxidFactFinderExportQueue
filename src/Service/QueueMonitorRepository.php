<?php

declare(strict_types=1);

namespace Wmdk\FactFinderQueue\Service;

use OxidEsales\Eshop\Core\DatabaseProvider;

final class QueueMonitorRepository
{
    private const TABLE = 'wmdk_ff_export_queue';

    /** @return list<array<string, mixed>> */
    public function findPage(array $filters, string $sort, string $direction, int $page): array
    {
        $filter = QueueMonitorQuery::buildWhere($filters);
        $sort = QueueMonitorQuery::normalizeSort($sort);
        $direction = QueueMonitorQuery::normalizeDirection($direction);
        $offset = max(0, $page - 1) * QueueMonitorQuery::PAGE_SIZE;
        $columns = implode(', ', array_map(
            static fn (string $column): string => QueueMonitorQuery::selectExpression($column),
            QueueMonitorQuery::DISPLAY_COLUMNS
        ));

        return DatabaseProvider::getDb(DatabaseProvider::FETCH_MODE_ASSOC)->getAll(
            sprintf(
                'SELECT %s FROM `%s`%s ORDER BY `%s` %s LIMIT %d OFFSET %d',
                $columns,
                self::TABLE,
                $filter['sql'],
                $sort,
                $direction,
                QueueMonitorQuery::PAGE_SIZE,
                $offset
            ),
            $filter['params']
        );
    }

    public function count(array $filters): int
    {
        $filter = QueueMonitorQuery::buildWhere($filters);

        return (int) DatabaseProvider::getDb()->getOne(
            sprintf('SELECT COUNT(*) FROM `%s`%s', self::TABLE, $filter['sql']),
            $filter['params']
        );
    }

    public function countWaiting(bool $onlyActive, bool $hidden, int $stockMin): int
    {
        $query = QueueMonitorQuery::buildWaitingCount($onlyActive, $hidden, $stockMin);

        return (int) DatabaseProvider::getDb()->getOne($query['sql'], $query['params']);
    }

    /** @return list<string> */
    public function getChannels(): array
    {
        return array_values(array_map(
            'strval',
            DatabaseProvider::getDb()->getCol(
                sprintf('SELECT DISTINCT `Channel` FROM `%s` ORDER BY `Channel`', self::TABLE)
            )
        ));
    }

    /** @return list<string> */
    public function getColumnNames(): array
    {
        $rows = DatabaseProvider::getDb(DatabaseProvider::FETCH_MODE_ASSOC)->getAll(
            sprintf('SHOW COLUMNS FROM `%s`', self::TABLE)
        );

        return array_values(array_filter(array_map(
            static fn (array $row): string => (string) ($row['Field'] ?? $row['FIELD'] ?? ''),
            $rows
        )));
    }

    public function selectForExport(array $filters, string $sort, string $direction)
    {
        $filter = QueueMonitorQuery::buildWhere($filters);
        $sort = QueueMonitorQuery::normalizeSort($sort);
        $direction = QueueMonitorQuery::normalizeDirection($direction);

        return DatabaseProvider::getDb(DatabaseProvider::FETCH_MODE_ASSOC)->select(
            sprintf('SELECT * FROM `%s`%s ORDER BY `%s` %s', self::TABLE, $filter['sql'], $sort, $direction),
            $filter['params']
        );
    }

    /**
     * Reset every queue row for each selected OXID, across all channels, shops, and languages.
     *
     * @param list<string> $articleIds
     *
     * @return array{articles: int, records: int}
     */
    public function resetArticles(array $articleIds): array
    {
        $articleIds = array_map(
            static fn ($value): string => trim((string) $value),
            array_filter($articleIds, 'is_scalar')
        );
        $articleIds = array_values(array_unique(array_filter(
            $articleIds,
            static fn (string $value): bool => strlen($value) === 32
        )));
        $articleIds = array_slice($articleIds, 0, QueueMonitorQuery::PAGE_SIZE);

        if ($articleIds === []) {
            return ['articles' => 0, 'records' => 0];
        }

        $database = DatabaseProvider::getMaster(DatabaseProvider::FETCH_MODE_ASSOC);
        $placeholders = implode(', ', array_fill(0, count($articleIds), '?'));
        $records = (int) $database->getOne(
            sprintf('SELECT COUNT(*) FROM `%s` WHERE `OXID` IN (%s)', self::TABLE, $placeholders),
            $articleIds
        );

        $database->execute(
            sprintf(
                'UPDATE IGNORE `%s` SET `LASTSYNC` = ?, `OXTIMESTAMP` = ?, `ProcessIp` = ? WHERE `OXID` IN (%s)',
                self::TABLE,
                $placeholders
            ),
            [QueueSyncSentinel::DATETIME, QueueSyncSentinel::TIMESTAMP, 'ff-monitor - ' . date('Y-m-d H:i:s'), ...$articleIds]
        );

        return ['articles' => count($articleIds), 'records' => $records];
    }
}
