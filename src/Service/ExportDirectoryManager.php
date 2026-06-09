<?php

namespace Wmdk\FactFinderQueue\Service;

use OxidEsales\Eshop\Core\Registry;

class ExportDirectoryManager
{
    public function ensureDefaultDirectoryTree(): void
    {
        $sourceRoot = dirname(__DIR__, 2) . '/export';

        if (!is_dir($sourceRoot)) {
            return;
        }

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($sourceRoot, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST
        );

        $this->ensureDirectory($this->getShopRoot() . '/export');

        foreach ($iterator as $item) {
            if (!$item->isDir()) {
                continue;
            }

            $relativePath = substr($item->getPathname(), strlen($sourceRoot) + 1);
            $this->ensureDirectory($this->getShopRoot() . '/export/' . $relativePath);
        }
    }

    public function resolveConfiguredExportFile(string $fileName): string
    {
        $directory = $this->getConfiguredExportDirectory();
        $this->ensureDirectory($directory);

        return $this->normalizePath($directory . '/' . ltrim($fileName, '/\\'));
    }

    public function ensureConfiguredExportDirectory(): void
    {
        $this->ensureDirectory($this->getConfiguredExportDirectory());
    }

    private function getConfiguredExportDirectory(): string
    {
        $configuredDirectory = (string) (new ModuleSettingsReader())->getString(
            'sWmdkFFExportDirectory',
            'export/factfinder/productData/'
        );

        if ($configuredDirectory === '') {
            $configuredDirectory = 'export/factfinder/productData/';
        }

        if ($this->isAbsolutePath($configuredDirectory)) {
            return $this->normalizePath(rtrim($configuredDirectory, '/\\'));
        }

        return $this->normalizePath($this->getShopRoot() . '/' . trim($configuredDirectory, '/\\'));
    }

    private function ensureDirectory(string $directory): void
    {
        if (!is_dir($directory)) {
            mkdir($directory, 0775, true);
        }
    }

    private function getShopRoot(): string
    {
        $config = Registry::getConfig();
        $shopRoot = (string) $config->getConfigParam('sShopDir');

        if ($shopRoot === '') {
            $shopRoot = (string) $config->getShopConfVar('sShopDir');
        }

        return rtrim($shopRoot, '/\\');
    }

    private function isAbsolutePath(string $path): bool
    {
        return preg_match('~^(?:[a-zA-Z]:[\\\\/]|/)~', $path) === 1;
    }

    private function normalizePath(string $path): string
    {
        return str_replace('\\', '/', preg_replace('~/+~', '/', $path));
    }
}
