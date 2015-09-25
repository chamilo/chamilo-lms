<?php

namespace Ddeboer\DataImport\Reader\Factory;

use Ddeboer\DataImport\Reader\CsvReader;

/**
 * Factory that creates CsvReaders
 *
 */
class CsvReaderFactory
{
    protected $delimiter;
    protected $enclosure;
    protected $escape;
    protected $headerRowNumber;
    protected $strict;

    public function __construct(
        $headerRowNumber = null,
        $strict = true,
        $delimiter = ',',
        $enclosure = '"',
        $escape = '\\'
    ) {
        $this->headerRowNumber = $headerRowNumber;
        $this->strict = $strict;
        $this->delimiter = $delimiter;
        $this->enclosure = $enclosure;
        $this->escape = $escape;
    }

    public function getReader(\SplFileObject $file)
    {
        $reader = new CsvReader($file, $this->delimiter, $this->enclosure, $this->escape);
        if (null !== $this->headerRowNumber) {
            $reader->setHeaderRowNumber($this->headerRowNumber);
        }

        $reader->setStrict($this->strict);

        return $reader;
    }
}
