<?php

declare(strict_types=1);

namespace Wmdk\FactFinderQueue\Service;

use OxidEsales\Eshop\Application\Model\Article;
use OxidEsales\Eshop\Core\DatabaseProvider;
use OxidEsales\Eshop\Core\Registry;

class QueueArticleMarker
{
    private const DB_RETRY_ATTEMPTS = 4;
    private const DB_RETRY_BASE_DELAY_US = 100000;

    public function markArticle(string $articleId): void
    {
        if ($articleId === '') {
            return;
        }

        /** @var Article $article */
        $article = oxNew(Article::class);

        if (!$article->load($articleId)) {
            return;
        }

        $loadedArticleId = (string) $article->getFieldData('oxid');
        $articleNumber = (string) $article->getFieldData('oxartnum');
        $isActive = (int) $article->getFieldData('oxactive');

        $this->touchArticle($loadedArticleId, $articleNumber, $isActive);

        if ((int) $article->getFieldData('oxvarcount') > 0) {
            $this->touchVariants($loadedArticleId);
        }
    }

    private function touchArticle(string $articleId, string $articleNumber, int $isActive = 1): void
    {
        $this->resetExistingQueueRecords($articleId, $articleNumber, $isActive);

        foreach ($this->getChannelList() as $channel) {
            if ($this->isInQueue($articleId, $channel['code'], $channel['shop_id'], $channel['lang_id'])) {
                $this->updateArticle($articleId, $channel['code'], $channel['shop_id'], $channel['lang_id'], $isActive);
                continue;
            }

            $this->insertArticle($articleId, $channel['code'], $channel['shop_id'], $channel['lang_id']);
        }
    }

    private function touchVariants(string $parentArticleId): void
    {
        $database = DatabaseProvider::getDb(false);
        $result = $database->select(
            'SELECT OXID, OXACTIVE FROM oxarticles WHERE OXPARENTID = ? ORDER BY OXVARSELECT ASC',
            [$parentArticleId]
        );

        if ($result === false || $result->count() <= 0) {
            return;
        }

        while (!$result->EOF) {
            $this->touchArticleById((string) $result->fields[0], (int) $result->fields[1]);
            $result->fetchRow();
        }
    }

    private function touchArticleById(string $articleId, int $isActive = 1): void
    {
        /** @var Article $article */
        $article = oxNew(Article::class);

        if (!$article->load($articleId)) {
            return;
        }

        $this->touchArticle(
            (string) $article->getFieldData('oxid'),
            (string) $article->getFieldData('oxartnum'),
            $isActive
        );
    }

    /**
     * @return array<int, array{code: string, shop_id: int, lang_id: int}>
     */
    private function getChannelList(): array
    {
        $channelList = [];
        $channelConfig = (new ModuleSettingsReader())->getString('sWmdkFFGeneralChannelList');

        foreach (explode(',', $channelConfig) as $channel) {
            $parts = explode('::', trim($channel));

            if (count($parts) < 3 || $parts[0] === '') {
                continue;
            }

            $channelList[] = [
                'code' => $parts[0],
                'shop_id' => (int) $parts[1],
                'lang_id' => (int) $parts[2],
            ];
        }

        return $channelList;
    }

    private function isInQueue(string $articleId, string $channel, int $shopId = 1, int $languageId = 0): bool
    {
        $result = DatabaseProvider::getDb()->select(
            'SELECT COUNT(*) FROM wmdk_ff_export_queue WHERE OXID = ? AND Channel = ? AND OXSHOPID = ? AND LANG = ?',
            [$articleId, $channel, $shopId, $languageId]
        );

        return $result !== false && $result->count() > 0 && (int) $result->fields[0] > 0;
    }

    private function insertArticle(string $articleId, string $channel, int $shopId = 1, int $languageId = 0): void
    {
        $this->executeWithRetry(
            'INSERT IGNORE INTO wmdk_ff_export_queue
                (OXID, Channel, OXSHOPID, LANG, LASTSYNC, ProcessIp, OXTIMESTAMP, OXACTIVE)
             VALUES
                (?, ?, ?, ?, "0000-00-00 00:00:00", ?, "0000-00-00 00:00:00", "1")',
            [$articleId, $channel, $shopId, $languageId, $this->getClientIp()]
        );

        $this->executeWithRetry(
            'UPDATE oxarticles SET WMDK_FFQUEUE = "1" WHERE OXID = ?',
            [$articleId]
        );
    }

    private function resetExistingQueueRecords(string $articleId, string $articleNumber, int $isActive = 1): void
    {
        $this->executeWithRetry(
            'UPDATE wmdk_ff_export_queue
             SET LASTSYNC = "0000-00-00 00:00:00",
                 ProcessIp = ?,
                 OXTIMESTAMP = "0000-00-00 00:00:00",
                 OXACTIVE = ?
             WHERE OXID = ?
                OR ProductNumber = ?
                OR MasterProductNumber = ?',
            [
                $this->getClientIp(),
                $isActive,
                $articleId,
                $articleNumber,
                $articleNumber,
            ]
        );
    }

    private function updateArticle(
        string $articleId,
        string $channel,
        int $shopId = 1,
        int $languageId = 0,
        int $isActive = 1
    ): void {
        $this->executeWithRetry(
            'UPDATE wmdk_ff_export_queue
             SET LASTSYNC = "0000-00-00 00:00:00",
                 ProcessIp = ?,
                 OXTIMESTAMP = "0000-00-00 00:00:00",
                 OXACTIVE = ?
             WHERE OXID = ? AND Channel = ? AND OXSHOPID = ? AND LANG = ?',
            [$this->getClientIp(), $isActive, $articleId, $channel, $shopId, $languageId]
        );
    }

    private function getClientIp(): string
    {
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            return (string) $_SERVER['HTTP_CLIENT_IP'];
        }

        if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            return (string) $_SERVER['HTTP_X_FORWARDED_FOR'];
        }

        return (string) ($_SERVER['REMOTE_ADDR'] ?? 'wmdkffexport:admin-save');
    }

    /**
     * @param array<int, mixed> $parameters
     */
    private function executeWithRetry(string $query, array $parameters = []): void
    {
        $attempt = 0;

        while (true) {
            try {
                DatabaseProvider::getDb()->execute($query, $parameters);
                return;
            } catch (\Throwable $exception) {
                if (!$this->isRetryableDbError($exception) || $attempt >= self::DB_RETRY_ATTEMPTS - 1) {
                    throw $exception;
                }

                $attempt++;
                usleep((self::DB_RETRY_BASE_DELAY_US * $attempt) + random_int(0, 50000));
            }
        }
    }

    private function isRetryableDbError(\Throwable $exception): bool
    {
        $message = strtolower($exception->getMessage());

        return str_contains($message, '1205')
            || str_contains($message, '1213')
            || str_contains($message, 'lock wait timeout exceeded')
            || str_contains($message, 'deadlock found');
    }
}
