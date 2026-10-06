<?php

declare(strict_types=1);

namespace Wmdk\FactFinderQueue\Service;

use OxidEsales\Eshop\Core\DatabaseProvider;

final class ArticleFamilyOxidResolver
{
    /** @return list<string> */
    public function resolve(string $articleId): array
    {
        $articleId = trim($articleId);

        if (preg_match('/^[A-Za-z0-9_-]{32}$/D', $articleId) !== 1) {
            return [];
        }

        $database = DatabaseProvider::getDb(DatabaseProvider::FETCH_MODE_ASSOC);
        $parentId = trim((string) $database->getOne(
            'SELECT `OXPARENTID` FROM `oxarticles` WHERE `OXID` = ?',
            [$articleId]
        ));
        $familyId = $parentId !== '' ? $parentId : $articleId;
        $articleIds = $database->getCol(
            'SELECT `OXID` FROM `oxarticles` '
            . 'WHERE `OXID` = ? OR `OXPARENTID` = ? '
            . 'ORDER BY CASE WHEN `OXID` = ? THEN 0 ELSE 1 END, `OXARTNUM`, `OXID`',
            [$familyId, $familyId, $familyId]
        );

        if ($articleIds === [] && $parentId !== '') {
            $articleIds = [$articleId];
        }

        return array_values(array_unique(array_filter(
            array_map('strval', $articleIds),
            static fn (string $value): bool => preg_match('/^[A-Za-z0-9_-]{32}$/D', $value) === 1
        )));
    }
}
