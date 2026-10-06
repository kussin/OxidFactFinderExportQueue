<?php

declare(strict_types=1);

namespace Wmdk\FactFinderQueue\Command;

use OxidEsales\Eshop\Core\DatabaseProvider;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Wmdk\FactFinderQueue\Service\FlourSaleAmountCalculator;
use Wmdk\FactFinderQueue\Service\QueueSyncSentinel;

final class SyncFlourFieldsCommand extends Command
{
    protected function configure(): void
    {
        $this
            ->setName('wmdkffexport:maintenance:sync-flour')
            ->setDescription('Synchronizes flour POS fields from oxarticles into the FACT Finder queue.')
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'Only report differing records without updating them.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $db = DatabaseProvider::getDb();
        $dryRun = (bool) $input->getOption('dry-run');
        $transactionStarted = false;

        try {
            $saleAmountExpression = FlourSaleAmountCalculator::sqlExpression('a');
            $differenceSql = $this->buildDifferenceSql($saleAmountExpression);
            $matchedRecords = (int) $db->getOne(
                'SELECT COUNT(*) FROM `wmdk_ff_export_queue` q INNER JOIN `oxarticles` a ON a.`OXID` = q.`OXID`'
            );
            $changedRecords = (int) $db->getOne(
                'SELECT COUNT(*) FROM `wmdk_ff_export_queue` q '
                . 'INNER JOIN `oxarticles` a ON a.`OXID` = q.`OXID` '
                . 'WHERE ' . $differenceSql
            );

            if (!$dryRun && $changedRecords > 0) {
                $db->startTransaction();
                $transactionStarted = true;

                $db->execute($this->buildUpdateSql($saleAmountExpression, $differenceSql));
                $db->commitTransaction();
                $transactionStarted = false;
            }

            $this->writeJson($output, [
                'success' => true,
                'command' => 'wmdkffexport:maintenance:sync-flour',
                'dry_run' => $dryRun,
                'matched_records' => $matchedRecords,
                'changed_records' => $changedRecords,
                'updated_records' => $dryRun ? 0 : $changedRecords,
                'lastsync' => QueueSyncSentinel::DATETIME,
                'oxtimestamp' => QueueSyncSentinel::TIMESTAMP,
            ]);

            return Command::SUCCESS;
        } catch (\Throwable $exception) {
            if ($transactionStarted) {
                $db->rollbackTransaction();
            }

            $this->writeJson($output, [
                'success' => false,
                'command' => 'wmdkffexport:maintenance:sync-flour',
                'dry_run' => $dryRun,
                'error' => $exception->getMessage(),
            ]);

            return Command::FAILURE;
        }
    }

    private function buildDifferenceSql(string $saleAmountExpression): string
    {
        return implode(' OR ', [
            'NOT (q.`FlourId` <=> NULLIF(a.`WMDKFLOURID`, ""))',
            'NOT (q.`FlourActive` <=> COALESCE(a.`WMDKFLOURACTIVE`, 0))',
            'NOT (q.`FlourPrice` <=> COALESCE(a.`WMDKFLOURWAREHOUSEPRICE`, 0))',
            sprintf('NOT (q.`FlourSaleAmount` <=> (%s))', $saleAmountExpression),
            'NOT (q.`FlourShortUrl` <=> a.`WMDKFLOURSHORTURL`)',
        ]);
    }

    private function buildUpdateSql(string $saleAmountExpression, string $differenceSql): string
    {
        $db = DatabaseProvider::getDb();

        return 'UPDATE IGNORE `wmdk_ff_export_queue` q '
            . 'INNER JOIN `oxarticles` a ON a.`OXID` = q.`OXID` '
            . 'SET q.`FlourId` = NULLIF(a.`WMDKFLOURID`, ""), '
            . 'q.`FlourActive` = COALESCE(a.`WMDKFLOURACTIVE`, 0), '
            . 'q.`FlourPrice` = COALESCE(a.`WMDKFLOURWAREHOUSEPRICE`, 0), '
            . 'q.`FlourSaleAmount` = ' . $saleAmountExpression . ', '
            . 'q.`FlourShortUrl` = a.`WMDKFLOURSHORTURL`, '
            . 'q.`LASTSYNC` = ' . $db->quote(QueueSyncSentinel::DATETIME) . ', '
            . 'q.`OXTIMESTAMP` = ' . $db->quote(QueueSyncSentinel::TIMESTAMP) . ', '
            . 'q.`ProcessIp` = ' . $db->quote('sync-flour - ' . date('Y-m-d H:i:s')) . ' '
            . 'WHERE ' . $differenceSql;
    }

    private function writeJson(OutputInterface $output, array $response): void
    {
        $output->writeln((string) json_encode(
            $response,
            JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR
        ));
    }
}
