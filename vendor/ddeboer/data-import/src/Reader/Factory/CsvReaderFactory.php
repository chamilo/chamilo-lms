<?php

namespace Ddeboer\DataImport\Reader\Factory;

use Ddeboer\DataImport\Reader\CsvReader;

/**
 * Factory that creates CsvReaders
 *
 * @author David de Boer <david@ddeboer.nl>
 */
class CsvReaderFactory
{
    /**
     * @var integer
     */
    protected $headerRowNumber;

    /**
     * @var boolean
     */
    protected $strict;

    /**
     * @var string
     */
    protected $delimiter;

    /**
     * @var string
     */
    protected $enclosure;

    /**
     * @var string
     */
    protected $escape;

    /**
     * @param integer $headerRowNumber
     * @param boolean $strict
     * @param string  $delimiter
     * @param string  $enclosure
     * @param string  $escape
     */
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

    /**
     * @param \SplFileObject $file
     *
     * @return CsvReader
     */
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
