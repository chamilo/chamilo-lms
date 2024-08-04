<?php

/* For licensing terms, see /license.txt */

use League\Csv\Reader;

/**
 * Class Import
 * This class provides some functions which can be used when importing data from
 * external files into Chamilo.
 */
class Import
{
    /**
     * @param string $path
     * @param bool   $setFirstRowAsHeader
     *
     * @return array
     */
    public static function csv_reader($path, $setFirstRowAsHeader = true)
    {
        return self::csvToArray($path);
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
     *
     * @param string $filename the path to the CSV-file which should be imported
     *
     * @return array returns an array (in the system encoding) that contains all data from the CSV-file
     */
    public static function csvToArray($filename, $delimiter = ';'): array
    {
        if (empty($filename)) {
            return [];
        }

        $reader = Reader::createFromPath($filename, 'r');
        if ($reader) {
            $reader->setDelimiter($delimiter);
            $reader->setHeaderOffset(0);
            $iterator = $reader->getRecords();

            return iterator_to_array($iterator);
        }

        return [];
    }

    /**
     * @param string $filename
     *
     * @return array
     */
    public static function xlsToArray($filename)
    {
        if (empty($filename)) {
            return [];
        }

        $reader = new \PhpOffice\PhpSpreadsheet\Reader\Xls();
        $spreadsheet = $reader->load($filename);
        $sheet = $spreadsheet->getActiveSheet();

        return $sheet->toArray();
    }
}
