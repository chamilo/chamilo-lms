<?php

namespace Ddeboer\DataImport\Reader\Factory;

use Ddeboer\DataImport\Reader\ExcelReader;

/**
 * Factory that creates ExcelReaders
 *
 * @author David de Boer <david@ddeboer.nl>
 */
class ExcelReaderFactory
{
    /**
     * @var integer
     */
    protected $headerRowNumber;

    /**
     * @var integer
     */
    protected $activeSheet;

    /**
     * @param integer $headerRowNumber
     * @param integer $activeSheet
     */
    public function __construct($headerRowNumber = null, $activeSheet = null)
    {
        $this->headerRowNumber = $headerRowNumber;
        $this->activeSheet = $activeSheet;
    }

    /**
     * @param \SplFileObject $file
     *
     * @return ExcelReader
     */
    public function getReader(\SplFileObject $file)
    {
        return new ExcelReader($file, $this->headerRowNumber, $this->activeSheet);
    }
}
