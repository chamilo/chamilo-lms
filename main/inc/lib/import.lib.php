<?php
/* For licensing terms, see /license.txt */

use Ddeboer\DataImport\Workflow;
use Ddeboer\DataImport\Reader\CsvReader;
use Ddeboer\DataImport\Writer\ArrayWriter;

/**
 * Class Import
 * This class provides some functions which can be used when importing data from
 * external files into Chamilo.
 * @package	 chamilo.library
 *
 */
class Import
{
    /**
     * @param string $path
     * @param bool $setFirstRowAsHeader
     * @return CsvReader
     */
    public static function csv_reader($path, $setFirstRowAsHeader = true)
    {
        if (empty($path)) {
            return false;
        }

        $file = new \SplFileObject($path);
        $csvReader = new CsvReader($file, ';');

        if ($setFirstRowAsHeader) {
            $csvReader->setHeaderRowNumber(0);
        }

        return $csvReader;
    }

    /**
     * Reads a CSV-file into an array. The first line of the CSV-file should contain the array-keys.
     * The encoding of the input file is tried to be detected.
     * The elements of the returned array are encoded in the system encoding.
     * Example:
     *   FirstName;LastName;Email
     *   John;Doe;john.doe@mail.com
     *   Adam;Adams;adam@mail.com
     *  returns
     *   $result [0]['FirstName'] = 'John';
     *   $result [0]['LastName'] = 'Doe';
     *   $result [0]['Email'] = 'john.doe@mail. com';
     *   $result [1]['FirstName'] = 'Adam';
     *   ...
     * @param string $filename	The path to the CSV-file which should be imported.
     * @return array Returns an array (in the system encoding) that contains all data from the CSV-file.
     *
     *
     * @deprecated use cvs_reader instead
     */
    public static function csvToArray($filename)
    {
        $csvReader = self::csv_reader($filename);
        $resultArray = [];
        if ($csvReader) {
            $workflow = new Workflow\StepAggregator($csvReader);
            $writer = new ArrayWriter($resultArray);
            $workflow->addWriter($writer)->process();
        }

        return $resultArray;
    }
}
