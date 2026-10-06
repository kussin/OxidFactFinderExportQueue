<?php

declare(strict_types=1);

namespace Wmdk\FactFinderQueue\Service;

final class QueueFieldCalculator
{
    public static function saleAmount(float $price, float $msrp, string $sign = '%'): string
    {
        if ($price >= $msrp) {
            return '';
        }

        $discount = round(100 - (($price * 100) / $msrp), 0);

        return (string) floor($discount) . $sign;
    }
}
