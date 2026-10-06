<?php

declare(strict_types=1);

// Isolated regression for the OXID 6 flour POS discount calculation. No shop bootstrap or database access.
//
//   php tests/flour-sale-amount.test.php

require dirname(__DIR__) . '/src/Service/FlourSaleAmountCalculator.php';
require dirname(__DIR__) . '/src/Traits/FlourTrait.php';

use Wmdk\FactFinderQueue\Service\FlourSaleAmountCalculator;
use Wmdk\FactFinderQueue\Traits\FlourTrait;

final class FlourSelectionHarness
{
    use FlourTrait;

    public function applyMsrpFallback(string $selection): string
    {
        return $this->_getFlourExportSelectionMsrpFallback($selection);
    }

    public function applySaleAmount(string $selection): string
    {
        return $this->_getFlourExportSelectionSaleAmount($selection);
    }
}

function assertFlourSaleAmount(string $expected, string $actual, string $message): void
{
    if ($expected !== $actual) {
        fwrite(STDERR, sprintf("FAIL %s: expected %s, got %s\n", $message, var_export($expected, true), var_export($actual, true)));
        exit(1);
    }

    echo "ok   {$message}\n";
}

assertFlourSaleAmount('20', FlourSaleAmountCalculator::calculate(80.0, 100.0, 90.0), 'calculates the discount percentage');
assertFlourSaleAmount('20', FlourSaleAmountCalculator::calculate(80.0, 0.0, 100.0), 'falls back to the selling price without MSRP');
assertFlourSaleAmount('', FlourSaleAmountCalculator::calculate(0.0, 100.0, 100.0), 'empty for a missing flour price');
assertFlourSaleAmount('', FlourSaleAmountCalculator::calculate(80.0, 0.0, 0.0), 'empty without a reference price');

$sqlExpression = FlourSaleAmountCalculator::sqlExpression('a');

if (!str_contains($sqlExpression, '100 -') || !str_contains($sqlExpression, 'OXTPRICE') || !str_contains($sqlExpression, 'OXPRICE')) {
    fwrite(STDERR, "FAIL SQL expression does not preserve the OXID 6 discount and fallback semantics.\n");
    exit(1);
}

echo "ok   SQL expression preserves discount and fallback semantics\n";

$harness = new FlourSelectionHarness();
$selection = $harness->applyMsrpFallback('`MSRP`, MSRP AS `FlourMsrp`, `FlourSaleAmount`');
$selection = $harness->applySaleAmount($selection);

if (!str_contains($selection, 'IF(`MSRP` > 0, `MSRP`, `Price`) AS `FlourMsrp`')) {
    fwrite(STDERR, "FAIL Flour export does not use the selling price as its MSRP fallback.\n");
    exit(1);
}

if (!str_contains($selection, 'ROUND(100 - ((`FlourPrice` * 100) / IF(`MSRP` > 0, `MSRP`, `Price`)), 0)')) {
    fwrite(STDERR, "FAIL Flour export does not recalculate the OXID 6 discount.\n");
    exit(1);
}

echo "ok   Flour export selection preserves discount and fallback semantics\n";
