<?php

declare(strict_types=1);

// Isolated regression for the calculated sale field inherited from OXID 6.
//
//   php tests/queue-field-calculator.test.php

require dirname(__DIR__) . '/src/Service/QueueFieldCalculator.php';

use Wmdk\FactFinderQueue\Service\QueueFieldCalculator;

function assertQueueField($expected, $actual, string $message): void
{
    if ($expected !== $actual) {
        fwrite(STDERR, sprintf("FAIL %s: expected %s, got %s\n", $message, var_export($expected, true), var_export($actual, true)));
        exit(1);
    }

    echo "ok   {$message}\n";
}

assertQueueField('34%', QueueFieldCalculator::saleAmount(66.4, 100.0), 'rounds the discount before flooring it');
assertQueueField('20', QueueFieldCalculator::saleAmount(80.0, 100.0, ''), 'supports a discount without a percentage sign');
assertQueueField('', QueueFieldCalculator::saleAmount(100.0, 100.0), 'does not mark a non-discounted product');
