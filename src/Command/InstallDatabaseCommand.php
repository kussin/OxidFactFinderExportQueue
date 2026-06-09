<?php

namespace Wmdk\FactFinderQueue\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Wmdk\FactFinderQueue\Setup\DatabaseInstaller;

class InstallDatabaseCommand extends Command
{
    public function __construct()
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setName('wmdkffexport:install:db')
            ->setDescription('Installs or updates the FACT Finder export queue database schema.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        (new DatabaseInstaller())->install();
        $output->writeln('FACT Finder export queue database schema is installed.');

        return Command::SUCCESS;
    }
}
