<?php

namespace Ddeboer\DataImport\ValueConverter;

use Ddeboer\DataImport\Exception\UnexpectedValueException;

/**
 * Convert an date time object into string
 */
class DateTimeToStringValueConverter
{
    /**
     * Date time format
     *
     * @var string
     * @see http://php.net/manual/en/datetime.createfromformat.php
     */
    protected $outputFormat;

    /**
     * @param string $outputFormat
     */
    public function __construct($outputFormat = 'Y-m-d H:i:s')
    {
        $this->outputFormat = $outputFormat;
    }

    /**
     * Convert string to date time object
     * using specified format
     *
     * @param mixed $input
     * @return \DateTime|string
     * @throws UnexpectedValueException
     */
    public function convert($input)
    {
        if (!$input) {
            return;
        }

        if (!($input instanceof \DateTime)) {
            throw new UnexpectedValueException('Input must be DateTime object.');
        }

        return $input->format($this->outputFormat);
    }
}
