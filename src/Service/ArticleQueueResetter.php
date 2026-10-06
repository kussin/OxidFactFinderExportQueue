<?php

declare(strict_types=1);

namespace Wmdk\FactFinderQueue\Service;

use OxidEsales\Eshop\Core\DatabaseProvider;
use OxidEsales\EshopCommunity\Core\Database\Adapter\DatabaseInterface;

/**
 * Re-queues one article family after a worker requested a faster FACT Finder refresh.
 */
final class ArticleQueueResetter
{
    public function __construct(private readonly ?DatabaseInterface $database = null)
    {
    }

    /**
     * @return list<string> OXIDs whose queue rows were reset
     */
    public function reset(string $articleId, string $processIp): array
    {
        $database = $this->database ?? DatabaseProvider::getMaster(DatabaseProvider::FETCH_MODE_NUM);
        $articleIds = array_values(array_unique(array_map(
            'strval',
            $database->getCol(
                'SELECT DISTINCT related.OXID
                 FROM wmdk_ff_export_queue AS related
                 WHERE EXISTS (
                     SELECT 1
                     FROM wmdk_ff_export_queue AS selected
                     WHERE selected.OXID = ?
                       AND (
                           (
                               selected.ProductNumber <> ?
                               AND (
                                   related.ProductNumber = selected.ProductNumber
                                   OR related.MasterProductNumber = selected.ProductNumber
                               )
                           )
                           OR (
                               selected.MasterProductNumber <> ?
                               AND related.MasterProductNumber = selected.MasterProductNumber
                           )
                       )
                 )
                 ORDER BY related.OXID',
                [$articleId, '', '']
            )
        )));

        if ($articleIds === []) {
            return [];
        }

        $placeholders = implode(', ', array_fill(0, count($articleIds), '?'));
        $database->execute(
            sprintf(
                'UPDATE IGNORE wmdk_ff_export_queue
                 SET LASTSYNC = ?, ProcessIp = ?, OXTIMESTAMP = ?
                 WHERE OXID IN (%s)',
                $placeholders
            ),
            [QueueSyncSentinel::DATETIME, $processIp, QueueSyncSentinel::TIMESTAMP, ...$articleIds]
        );

        return $articleIds;
    }
}
