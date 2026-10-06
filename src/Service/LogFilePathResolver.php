<?php

namespace Wmdk\FactFinderQueue\Service;

use OxidEsales\Eshop\Core\Registry;

class LogFilePathResolver
{
    private const LEGACY_LOG_FILE_MAP = [
        'log/WMDK_FF_QUEUE.log' => 'log/KUSSIN_FACTFINDER_QUEUE.log',
        'log/WMDK_FF_EXPORT.log' => 'log/KUSSIN_FACTFINDER_EXPORT.log',
        'log/WMDK_FF_STOCK.log' => 'log/KUSSIN_FACTFINDER_STOCK.log',
        'log/WMDK_FF_CLONEDATTRIBUTES.log' => 'log/KUSSIN_FACTFINDER_CLONED_ATTRIBUTES.log',
        'log/WMDKFFEXPORTQUEUE.DEBUG.log' => 'log/KUSSIN_FACTFINDER_DEBUG.log',
    ];

    public function resolveFromSetting(string $settingName, string $defaultRelativePath): string
    {
        $configuredPath = (string) (new ModuleSettingsReader())->getString($settingName, $defaultRelativePath);
        $configuredPath = $configuredPath !== '' ? $configuredPath : $defaultRelativePath;
        $configuredPath = $this->normalizeLegacyPath($configuredPath);

        if ($this->isAbsolutePath($configuredPath)) {
            return $this->normalizePath($configuredPath);
        }

        return $this->normalizePath($this->getShopRoot() . '/' . ltrim($configuredPath, '/\\'));
    }

    public function ensureDirectoryForFile(string $filePath): void
    {
        $directory = dirname($filePath);

        if (!is_dir($directory)) {
            mkdir($directory, 0775, true);
        }
    }

    private function normalizeLegacyPath(string $path): string
    {
        $normalized = ltrim(str_replace('\\', '/', $path), '/');

        return self::LEGACY_LOG_FILE_MAP[$normalized] ?? $path;
    }

    private function getShopRoot(): string
    {
        $config = Registry::getConfig();
        $shopRoot = (string) $config->getConfigParam('sShopDir');

        if ($shopRoot === '') {
            $shopRoot = (string) $config->getShopConfVar('sShopDir');
        }

        if ($shopRoot === '') {
            $shopRoot = (string) ($_SERVER['DOCUMENT_ROOT'] ?? '');
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
