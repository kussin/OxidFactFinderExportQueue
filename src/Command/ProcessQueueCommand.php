<?php

namespace Wmdk\FactFinderQueue\Command;

use OxidEsales\Eshop\Core\DatabaseProvider;
use OxidEsales\Eshop\Core\Registry;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ProcessQueueCommand extends AbstractLegacyViewCommand
{
    protected string $commandName = 'wmdkffexport:cron:queue';
    protected string $viewClass = 'wmdkffexport_queue';
    protected string $viewFile = 'views/wmdkffexport_queue.php';
    protected string $description = 'Processes pending FACT Finder export queue entries.';

    public function __construct()
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        parent::configure();
        $this->addOption('diagnose-selection', null, InputOption::VALUE_NONE, 'Print queue selection diagnostics before processing.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        if ($input->getOption('diagnose-selection')) {
            $this->printSelectionDiagnostics($output);
        }

        return parent::execute($input, $output);
    }

    private function printSelectionDiagnostics(OutputInterface $output): void
    {
        $config = Registry::getConfig();
        $limit = (int) ((string) $config->getConfigParam('sWmdkFFQueueLimit') !== '' ? $config->getConfigParam('sWmdkFFQueueLimit') : 150);
        $articleStatus = (string) ((string) $config->getConfigParam('iArticleStatus') !== '' ? $config->getConfigParam('iArticleStatus') : 1);
        $minStock = (int) ((string) $config->getConfigParam('iArticleMinStock') !== '' ? $config->getConfigParam('iArticleMinStock') : 0);

        $where = '(`wmdk_ff_export_queue`.`OXID` = `oxarticles`.`OXID`)'
            . ($articleStatus !== '' ? ' AND (`wmdk_ff_export_queue`.`OXACTIVE` = ' . (int) $articleStatus . ')' : '')
            . ' AND (`wmdk_ff_export_queue`.`Stock` >= ' . $minStock . ')';

        $count = (int) DatabaseProvider::getDb()->getOne(
            'SELECT COUNT(*) FROM `wmdk_ff_export_queue`, `oxarticles` WHERE ' . $where
        );
        $outdated = (int) DatabaseProvider::getDb()->getOne(
            'SELECT COUNT(*) FROM `wmdk_ff_export_queue`, `oxarticles` WHERE '
            . $where
            . ' AND (UNIX_TIMESTAMP(`oxarticles`.`OXTIMESTAMP`) > UNIX_TIMESTAMP(`wmdk_ff_export_queue`.`LASTSYNC`))'
        );

        $output->writeln(sprintf(
            'Queue selection diagnostics: matching=%d, outdated=%d, limit=%d, articleStatus=%s, minStock=%d.',
            $count,
            $outdated,
            $limit,
            $articleStatus === '' ? '<empty>' : $articleStatus,
            $minStock
        ));
    }
}
