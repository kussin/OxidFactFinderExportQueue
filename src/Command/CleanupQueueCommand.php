<?php

namespace Wmdk\FactFinderQueue\Command;

use OxidEsales\Eshop\Core\DatabaseProvider;
use Wmdk\FactFinderQueue\Service\LogFilePathResolver;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class CleanupQueueCommand extends Command
{
    protected function configure(): void
    {
        $this
            ->setName('wmdkffexport:maintenance:cleanup')
            ->setDescription('Cleans up invalid FACT Finder queue records.')
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'Only report affected records without changing them.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $db = DatabaseProvider::getDb();
        $sql = <<<SQL
SELECT q.`OXID`
FROM `wmdk_ff_export_queue` q
LEFT JOIN `oxarticles` a ON a.`OXID` = q.`OXID`
WHERE a.`OXID` IS NULL
ORDER BY q.`OXID`
SQL;

        $result = $db->select($sql);
        $orphanOxids = [];

        if ($result !== false) {
            while (!$result->EOF) {
                $orphanOxids[] = (string) $result->fields[0];
                $result->fetchRow();
            }
        }

        $count = count($orphanOxids);
        $emptyProductNumberCount = $this->countEmptyProductNumberRecords();
        $this->writeCleanupLog($orphanOxids, $emptyProductNumberCount, (bool) $input->getOption('dry-run'));

        if ($input->getOption('dry-run')) {
            $output->writeln(sprintf('Found %d orphaned FACT Finder queue entries. No records were deleted.', $count));
            $output->writeln(sprintf('Found %d FACT Finder queue entries with empty ProductNumber. No records were updated.', $emptyProductNumberCount));

            return Command::SUCCESS;
        }

        if ($count > 0) {
            $db->execute(<<<SQL
DELETE q
FROM `wmdk_ff_export_queue` q
LEFT JOIN `oxarticles` a ON a.`OXID` = q.`OXID`
WHERE a.`OXID` IS NULL
SQL);
        }

        if ($emptyProductNumberCount > 0) {
            $this->markEmptyProductNumberRecords();
        }

        $output->writeln(sprintf('Deleted %d orphaned FACT Finder queue entries.', $count));
        $output->writeln(sprintf('Updated %d FACT Finder queue entries with empty ProductNumber.', $emptyProductNumberCount));

        return Command::SUCCESS;
    }

    private function countEmptyProductNumberRecords(): int
    {
        return (int) DatabaseProvider::getDb()->getOne(
            'SELECT COUNT(*) FROM `wmdk_ff_export_queue` WHERE `ProductNumber` = ""'
        );
    }

    private function markEmptyProductNumberRecords(): void
    {
        DatabaseProvider::getDb()->execute(<<<'SQL'
UPDATE `wmdk_ff_export_queue`
SET
    `OXACTIVE` = 0,
    `OXHIDDEN` = 1,
    `LASTSYNC` = 0,
    `OXTIMESTAMP` = 1,
    `ProcessIp` = CONCAT("wmdkffexport:maintenance:cleanup - ", NOW())
WHERE `ProductNumber` = ""
SQL);
    }

    private function writeCleanupLog(array $orphanOxids, int $emptyProductNumberCount, bool $dryRun): void
    {
        $resolver = new LogFilePathResolver();
        $logFile = $resolver->resolveFromSetting('sWmdkFFDebugLogFileCleanup', 'log/KUSSIN_FACTFINDER_CLEANUP.log');
        $resolver->ensureDirectoryForFile($logFile);

        $lines = [
            sprintf(
                '[%s] %s %d orphaned FACT Finder queue entries.',
                date('Y-m-d H:i:s'),
                $dryRun ? 'Detected' : 'Deleted',
                count($orphanOxids)
            ),
            sprintf(
                '[%s] %s %d FACT Finder queue entries with empty ProductNumber.',
                date('Y-m-d H:i:s'),
                $dryRun ? 'Detected' : 'Updated',
                $emptyProductNumberCount
            ),
        ];

        foreach ($orphanOxids as $oxid) {
            $lines[] = $oxid;
        }

        file_put_contents($logFile, implode(PHP_EOL, $lines) . PHP_EOL, FILE_APPEND);
    }
}
