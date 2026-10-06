<?php

declare(strict_types=1);

namespace Wmdk\FactFinderQueue\Service;

final class QueueMonitorUrl
{
    public static function absoluteDeeplink(string $deeplink, string $shopUrl): string
    {
        $deeplink = trim($deeplink);
        $shopUrl = trim($shopUrl);

        if ($deeplink === '' || $shopUrl === '') {
            return '';
        }

        $scheme = strtolower((string) parse_url($deeplink, PHP_URL_SCHEME));

        if ($scheme !== '') {
            return in_array($scheme, ['http', 'https'], true) ? $deeplink : '';
        }

        if (str_starts_with($deeplink, '//')) {
            $shopScheme = strtolower((string) parse_url($shopUrl, PHP_URL_SCHEME));

            return in_array($shopScheme, ['http', 'https'], true) ? $shopScheme . ':' . $deeplink : '';
        }

        if (str_starts_with($deeplink, '/')) {
            $shopScheme = strtolower((string) parse_url($shopUrl, PHP_URL_SCHEME));
            $shopHost = (string) parse_url($shopUrl, PHP_URL_HOST);
            $shopPort = parse_url($shopUrl, PHP_URL_PORT);

            if (!in_array($shopScheme, ['http', 'https'], true) || $shopHost === '') {
                return '';
            }

            return $shopScheme . '://' . $shopHost . ($shopPort === null ? '' : ':' . $shopPort) . $deeplink;
        }

        return rtrim($shopUrl, '/') . '/' . ltrim($deeplink, '/');
    }
}
