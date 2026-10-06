<?php

namespace Wmdk\FactFinderQueue\Command;

use OxidEsales\Eshop\Core\DatabaseProvider;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ResetQueueRecordsCommand extends Command
{
    private const LIKE_FILTERS = [
        'title-like' => 'Title',
        'brand-like' => 'Marke',
        'category-path-like' => 'CategoryPath',
    ];

    protected function configure(): void
    {
        $this
            ->setName('wmdkffexport:maintenance:reset')
            ->setDescription('Resets FACT Finder queue sync timestamps for records matching explicit filters.')
            ->addOption('lastsync-from', null, InputOption::VALUE_REQUIRED, 'Filter records with LASTSYNC greater than or equal to this datetime.')
            ->addOption('lastsync-to', null, InputOption::VALUE_REQUIRED, 'Filter records with LASTSYNC lower than or equal to this datetime.')
            ->addOption('oxtimestamp-from', null, InputOption::VALUE_REQUIRED, 'Filter records with OXTIMESTAMP greater than or equal to this datetime.')
            ->addOption('oxtimestamp-to', null, InputOption::VALUE_REQUIRED, 'Filter records with OXTIMESTAMP lower than or equal to this datetime.')
            ->addOption('title-like', null, InputOption::VALUE_REQUIRED, 'Filter records by Title using SQL LIKE.')
            ->addOption('brand-like', null, InputOption::VALUE_REQUIRED, 'Filter records by Marke using SQL LIKE.')
            ->addOption('category-path-like', null, InputOption::VALUE_REQUIRED, 'Filter records by CategoryPath using SQL LIKE.')
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'Only report affected records without updating them.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $db = DatabaseProvider::getDb();
        $where = $this->buildWhereClause($input);

        if ($where === '') {
            throw new \InvalidArgumentException('At least one reset filter is required.');
        }

        $count = (int) $db->getOne('SELECT COUNT(*) FROM `wmdk_ff_export_queue` WHERE ' . $where);

        if ($input->getOption('dry-run')) {
            $output->writeln(sprintf('Found %d FACT Finder queue entries matching the reset filters. No records were updated.', $count));

            return Command::SUCCESS;
        }

        $processMarker = $db->quote('wmdkffexport:maintenance:reset - ' . date('Y-m-d H:i:s'));
        $db->execute(
            'UPDATE IGNORE `wmdk_ff_export_queue` '
            . 'SET `LASTSYNC` = 0, `OXTIMESTAMP` = 0, `ProcessIp` = ' . $processMarker . ' '
            . 'WHERE ' . $where
        );

        $output->writeln(sprintf('Reset %d FACT Finder queue entries matching the reset filters.', $count));

        return Command::SUCCESS;
    }

    private function buildWhereClause(InputInterface $input): string
    {
        $db = DatabaseProvider::getDb();
        $conditions = [];

        foreach ([
            'lastsync-from' => ['LASTSYNC', '>='],
            'lastsync-to' => ['LASTSYNC', '<='],
            'oxtimestamp-from' => ['OXTIMESTAMP', '>='],
            'oxtimestamp-to' => ['OXTIMESTAMP', '<='],
        ] as $option => [$field, $operator]) {
            $value = $input->getOption($option);

            if ($value !== null && $value !== '') {
                $conditions[] = sprintf('`%s` %s %s', $field, $operator, $db->quote((string) $value));
            }
        }

        foreach (self::LIKE_FILTERS as $option => $field) {
            $value = $input->getOption($option);

            if ($value !== null && $value !== '') {
                $conditions[] = sprintf('`%s` LIKE %s', $field, $db->quote((string) $value));
            }
        }

        return implode(' AND ', $conditions);
    }
}
