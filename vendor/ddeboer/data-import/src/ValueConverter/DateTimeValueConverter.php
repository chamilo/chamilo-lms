<?php

namespace Ddeboer\DataImport\ValueConverter;

use \Ddeboer\DataImport\Exception\UnexpectedValueException;

/**
 * Convert an date string into another date string
 * Eg. You want to change the format of a string OR
 * If no output specified, return DateTime instance
 *
 * @author David de Boer <david@ddeboer.nl>
 */
class DateTimeValueConverter
{
    /**
     * Date time format
     *
     * @var string
     *
     * @see http://php.net/manual/en/datetime.createfromformat.php
     */
    protected $inputFormat;

    /**
     * Date time format
     *
     * @var string
     *
     * @see http://php.net/manual/en/datetime.createfromformat.php
     */
    protected $outputFormat;

    /**
     * @param string $inputFormat
     * @param string $outputFormat
     */
    public function __construct($inputFormat = null, $outputFormat = null)
    {
        $this->inputFormat  = $inputFormat;
        $this->outputFormat = $outputFormat;
    }

    /**
     * Convert string to date time object then convert back to a string
     * using specified format
     *
     * If no output format specified then return
     * the \DateTime instance
     *
     * @param mixed $input
     * @return \DateTime|string
     * @throws UnexpectedValueException
     */
    public function __invoke($input)
    {
        if (!$input) {
            return;
        }

        if ($this->inputFormat) {
            $date = \DateTime::createFromFormat($this->inputFormat, $input);
            if (false === $date) {
                throw new UnexpectedValueException(
                    $input . ' is not a valid date/time according to format ' . $this->inputFormat
                );
            }
        } else {
            $date = new \DateTime($input);
        }

        if ($this->outputFormat) {
            return $date->format($this->outputFormat);
        }

        //if no output format specified we just return the \DateTime instance
        return $date;
    }
}
