<?php

namespace Wmdk\FactFinderQueue\Service;

class GzipCompressor
{
    public function compressFile(string $inputFile, int $level = 9): string
    {
        if (pathinfo($inputFile, PATHINFO_EXTENSION) === 'gz') {
            return $inputFile;
        }

        $input = fopen($inputFile, 'rb');

        if ($input === false) {
            throw new \RuntimeException(sprintf('Unable to open input file: %s', $inputFile));
        }

        $outputFile = $inputFile . '.gz';
        $output = gzopen($outputFile, 'wb' . $level);

        if ($output === false) {
            fclose($input);
            throw new \RuntimeException(sprintf('Unable to open output file: %s', $outputFile));
        }

        while (!feof($input)) {
            gzwrite($output, fread($input, 512 * 1024));
        }

        fclose($input);
        gzclose($output);

        return $outputFile;
    }
}
