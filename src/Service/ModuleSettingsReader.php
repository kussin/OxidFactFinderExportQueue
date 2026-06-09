<?php

declare(strict_types=1);

namespace Wmdk\FactFinderQueue\Service;

use OxidEsales\Eshop\Core\Registry;
use Symfony\Component\Yaml\Yaml;

class ModuleSettingsReader
{
    private const MODULE_ID = 'wmdkffexportqueue';

    /**
     * @return array<string, mixed>
     */
    public function getAllSettings(): array
    {
        $path = $this->getProjectConfigurationPath();

        if ($path === '' || !is_readable($path)) {
            return [];
        }

        $configuration = Yaml::parseFile($path);
        $settings = [];

        foreach (($configuration['moduleSettings'] ?? []) as $name => $setting) {
            if (array_key_exists('value', $setting)) {
                $settings[$name] = $setting['value'];
            }
        }

        return $settings;
    }

    public function getString(string $settingName, string $default = ''): string
    {
        $configValue = Registry::getConfig()->getConfigParam($settingName);

        if (is_scalar($configValue) && (string) $configValue !== '') {
            return (string) $configValue;
        }

        $yamlValue = $this->getYamlSettingValue($settingName);

        if (is_scalar($yamlValue) && (string) $yamlValue !== '') {
            return (string) $yamlValue;
        }

        return $default;
    }

    private function getYamlSettingValue(string $settingName): mixed
    {
        $path = $this->getProjectConfigurationPath();

        if ($path === '' || !is_readable($path)) {
            return null;
        }

        return $this->getAllSettings()[$settingName] ?? null;
    }

    private function getProjectConfigurationPath(): string
    {
        $shopDir = rtrim((string) Registry::getConfig()->getConfigParam('sShopDir'), '/\\');
        $candidates = [];

        if ($shopDir !== '') {
            $candidates[] = dirname($shopDir) . '/var/configuration/shops/1/modules/' . self::MODULE_ID . '.yaml';
            $candidates[] = $shopDir . '/var/configuration/shops/1/modules/' . self::MODULE_ID . '.yaml';
        }

        $candidates[] = getcwd() . '/var/configuration/shops/1/modules/' . self::MODULE_ID . '.yaml';

        foreach ($candidates as $candidate) {
            if (is_readable($candidate)) {
                return $candidate;
            }
        }

        return '';
    }
}
