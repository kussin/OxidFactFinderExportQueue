<?php

declare(strict_types=1);

namespace Wmdk\FactFinderQueue\Service;

final class FlourSaleAmountCalculator
{
    public static function calculate(float $flourPrice, float $msrp, float $fallbackPrice): string
    {
        $referencePrice = $msrp > 0 ? $msrp : $fallbackPrice;

        if ($flourPrice <= 0 || $referencePrice <= 0) {
            return '';
        }

        return (string) round(100 - (($flourPrice * 100) / $referencePrice), 0);
    }

    public static function sqlExpression(string $articleAlias = 'a'): string
    {
        if (!preg_match('/^[a-z][a-z0-9_]*$/i', $articleAlias)) {
            throw new \InvalidArgumentException('Invalid SQL table alias.');
        }

        $flourPrice = sprintf('COALESCE(%s.`WMDKFLOURWAREHOUSEPRICE`, 0)', $articleAlias);
        $referencePrice = sprintf(
            'COALESCE(NULLIF(%1$s.`OXTPRICE`, 0), NULLIF(%1$s.`OXPRICE`, 0), 0)',
            $articleAlias
        );

        return sprintf(
            "CASE WHEN %1\$s > 0 AND %2\$s > 0 THEN CAST(ROUND(100 - ((%1\$s * 100) / %2\$s), 0) AS CHAR) ELSE '' END",
            $flourPrice,
            $referencePrice
        );
    }
}
