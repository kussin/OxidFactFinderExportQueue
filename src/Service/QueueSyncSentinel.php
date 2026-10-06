<?php

declare(strict_types=1);

namespace Wmdk\FactFinderQueue\Service;

/**
 * OXID 6 marks queue rows for export with the special SQL zero datetime. The migrated database
 * accepts that value when the narrowly scoped write uses IGNORE, even with strict and NO_ZERO_DATE
 * modes enabled. Keeping this established marker also avoids the timezone conversion which makes
 * the lower boundary of a TIMESTAMP column unsafe as a datetime literal.
 *
 * Every write using these constants must therefore use INSERT IGNORE or UPDATE IGNORE. Reads cast
 * the temporal column to CHAR so strict mode never has to parse the zero value as a date.
 */
final class QueueSyncSentinel
{
    public const ZERO = '0000-00-00 00:00:00';
    public const DATETIME = self::ZERO;
    public const TIMESTAMP = self::ZERO;

    /** Values written by the temporary strict-mode migration remain unsynchronised markers. */
    private const TRANSITIONAL_DATETIME = '1000-01-01 00:00:00';
    private const TRANSITIONAL_TIMESTAMP = '1970-01-01 00:00:01';

    /**
     * SQL predicate telling whether a row is still flagged for export.
     *
     * The column is cast to CHAR first: comparing a stored zero datetime against a literal is
     * itself rejected under NO_ZERO_DATE, so the value has to be read as text.
     */
    public static function isUnsyncedSql(string $column): string
    {
        return sprintf(
            "CAST(%s AS CHAR) IN ('%s', '%s', '%s')",
            $column,
            self::ZERO,
            self::TRANSITIONAL_DATETIME,
            self::TRANSITIONAL_TIMESTAMP
        );
    }
}
