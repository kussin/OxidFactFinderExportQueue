<?php

namespace Wmdk\FactFinderQueue\Command;

use OxidEsales\Eshop\Core\Registry;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Wmdk\FactFinderQueue\Service\ExportDirectoryManager;
use Wmdk\FactFinderQueue\Service\ModuleSettingsReader;

abstract class AbstractLegacyViewCommand extends Command
{
    protected string $commandName;
    protected string $viewClass;
    protected string $viewFile;
    protected string $description = '';
    protected bool $requiresChannel = false;
    protected bool $requiresShopContext = false;
    protected bool $supportsFlourId = false;

    protected function configure(): void
    {
        $this->setName($this->commandName);

        if ($this->description !== '') {
            $this->setDescription($this->description);
        }

        $this
            ->addOption('channel', null, InputOption::VALUE_REQUIRED, 'Export channel code.')
            ->addOption('shop-id', null, InputOption::VALUE_REQUIRED, 'OXID shop id.')
            ->addOption('lang', null, InputOption::VALUE_REQUIRED, 'OXID language id.')
            ->addOption('flour-id', null, InputOption::VALUE_OPTIONAL, 'flour POS id.')
            ->addOption('cron', null, InputOption::VALUE_NONE, 'Mark this execution as an explicit cron run.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->validateInput($input);
        $this->prepareRequest($input);
        (new ExportDirectoryManager())->ensureConfiguredExportDirectory();
        $this->loadViewClass();

        $view = oxNew($this->viewClass);
        $template = $view->render();
        $response = $this->getResponse($view);
        $response = $this->normalizeResponse($response, $input);

        if ($input->getOption('cron')) {
            return Command::SUCCESS;
        }

        if ($response !== []) {
            $output->writeln(json_encode($response, JSON_THROW_ON_ERROR));
        } else {
            $output->writeln(sprintf('Executed %s, rendered template %s.', $this->viewClass, $template));
        }

        return Command::SUCCESS;
    }

    private function validateInput(InputInterface $input): void
    {
        $requiredOptions = [];

        if ($this->requiresChannel) {
            $requiredOptions[] = 'channel';
        }

        if ($this->requiresShopContext) {
            $requiredOptions[] = 'shop-id';
            $requiredOptions[] = 'lang';
        }

        foreach ($requiredOptions as $optionName) {
            if ((string) $input->getOption($optionName) === '') {
                throw new \InvalidArgumentException(sprintf('Missing required option --%s.', $optionName));
            }
        }
    }

    private function prepareRequest(InputInterface $input): void
    {
        $params = [
            'cl' => $this->viewClass,
        ];

        if ($input->getOption('channel') !== null) {
            $params['channel'] = (string) $input->getOption('channel');
        }

        if ($input->getOption('shop-id') !== null) {
            $params['shop_id'] = (string) $input->getOption('shop-id');
        }

        if ($input->getOption('lang') !== null) {
            $params['lang'] = (string) $input->getOption('lang');
        }

        if ($this->supportsFlourId && $input->getOption('flour-id') !== null) {
            $params['flour_id'] = (string) $input->getOption('flour-id');
        }

        $_GET = array_merge($_GET, $params);
        $_REQUEST = array_merge($_REQUEST, $params);
        $_SERVER['REQUEST_METHOD'] = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        if (empty($_SERVER['DOCUMENT_ROOT'])) {
            $_SERVER['DOCUMENT_ROOT'] = rtrim((string) Registry::getConfig()->getConfigParam('sShopDir'), '/');
        }
        $_SERVER['SCRIPT_NAME'] = $_SERVER['SCRIPT_NAME'] ?? '/index.php';
        $_SERVER['REQUEST_URI'] = $_SERVER['REQUEST_URI'] ?? '/index.php?' . http_build_query($_GET);
        $_SERVER['SCRIPT_URI'] = $_SERVER['SCRIPT_URI'] ?? $_SERVER['REQUEST_URI'];
        $_SERVER['HTTP_HOST'] = $_SERVER['HTTP_HOST'] ?? parse_url(Registry::getConfig()->getConfigParam('sShopURL'), PHP_URL_HOST) ?: 'localhost';
        $_SERVER['REMOTE_ADDR'] = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
        $_SERVER['WMDKFFEXPORT_IS_CRON'] = $input->getOption('cron') ? '1' : '0';

        $this->applyLegacyConfigDefaults();
    }

    private function applyLegacyConfigDefaults(): void
    {
        $config = Registry::getConfig();

        foreach ((new ModuleSettingsReader())->getAllSettings() as $name => $value) {
            $config->setConfigParam($name, $value);
        }

        $defaults = [
            'sWmdkFFQueueLimit' => '150',
            'iArticleStatus' => '1',
            'iArticleMinStock' => '0',
            'sWmdkFFQueueAttributeGlue' => '|',
            'sWmdkFFQueueFlagTopseller' => '10',
            'sWmdkFFQueuePhpLimitTimeout' => '900',
            'sWmdkFFQueuePhpLimitMemory' => '512M',
            'sWmdkFFQueueResetLimit' => '75',
            'bWmdkFFQueueEnableFromPrice' => '1',
            'bWmdkFFQueueUpdateSiblings' => '0',
            'bWmdkFFQueueUseCategoryPath' => '0',
            'sWmdkFFDebugCronjobIpList' => '',
            'sWmdkFFDebugLogFileQueue' => 'log/KUSSIN_FACTFINDER_QUEUE.log',
            'sWmdkFFDebugLogFileExport' => 'log/KUSSIN_FACTFINDER_EXPORT.log',
            'sWmdkFFDebugLogFileStock' => 'log/KUSSIN_FACTFINDER_STOCK.log',
            'sWmdkFFDebugLogFileClonedAttributes' => 'log/KUSSIN_FACTFINDER_CLONED_ATTRIBUTES.log',
            'sWmdkFFDebugLogFileCleanup' => 'log/KUSSIN_FACTFINDER_CLEANUP.log',
        ];

        foreach ($defaults as $name => $value) {
            if ((string) $config->getConfigParam($name) === '') {
                $config->setConfigParam($name, $value);
            }
        }
    }

    private function normalizeResponse(array $response, InputInterface $input): array
    {
        unset($response['template'], $response['process_ip'], $response['imported_product_reviews']);
        $response['cronjob'] = (bool) $input->getOption('cron');

        return $response;
    }

    private function loadViewClass(): void
    {
        if (class_exists($this->viewClass, false)) {
            return;
        }

        $viewPath = dirname(__DIR__, 2) . '/' . $this->viewFile;

        if (!is_readable($viewPath)) {
            throw new \RuntimeException(sprintf('Legacy view file is not readable: %s', $viewPath));
        }

        require_once $viewPath;

        if (!class_exists($this->viewClass, false)) {
            throw new \RuntimeException(sprintf('Legacy view class was not loaded: %s', $this->viewClass));
        }
    }

    private function getResponse(object $view): array
    {
        $reflection = new \ReflectionObject($view);

        while ($reflection !== false) {
            if ($reflection->hasProperty('_aResponse')) {
                $property = $reflection->getProperty('_aResponse');
                $property->setAccessible(true);

                return (array) $property->getValue($view);
            }

            $reflection = $reflection->getParentClass();
        }

        return [];
    }
}
