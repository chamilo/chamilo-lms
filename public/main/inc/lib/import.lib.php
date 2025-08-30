<?php

/* For licensing terms, see /license.txt */

use League\Csv\Reader;
use PhpOffice\PhpSpreadsheet\Reader\Xls;

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
     * Guess the CSV delimiter by scanning the first non-empty line.
     */
    public static function detectCsvSeparator(string $filePath): ?string
    {
        if (!is_readable($filePath)) {
            return null;
        }
        $h = @fopen($filePath, 'rb');
        if (!$h) {
            return null;
        }
        $line = '';
        for ($i = 0; $i < 5 && !feof($h); $i++) {
            $line = fgets($h, 1024 * 1024);
            if ($line !== false && trim($line) !== '') {
                break;
            }
        }
        fclose($h);
        if ($line === false || $line === '') {
            return null;
        }

        $cands = [',', ';', "\t", '|'];
        $scores = array_fill_keys($cands, 0);
        $inQuotes = false;
        $len = strlen($line);
        for ($i = 0; $i < $len; $i++) {
            $ch = $line[$i];
            if ($ch === '"') {
                $prev = $i > 0 ? $line[$i - 1] : null;
                if ($prev !== '\\') {
                    $inQuotes = !$inQuotes;
                }
            } elseif (!$inQuotes && isset($scores[$ch])) {
                $scores[$ch]++;
            }
        }
        arsort($scores);
        $top = array_key_first($scores);
        return ($scores[$top] > 0) ? $top : null;
    }

    /**
     * Check if the CSV is comma-separated.
     * - Default (returnMessage=false): returns bool (true = OK, false = not comma).
     * - With returnMessage=true: returns true (OK) OR a string with the error message.
     */
    public static function assertCommaSeparated(string $filePath, bool $returnMessage = false): bool|string
    {
        $det = self::detectCsvSeparator($filePath);

        if ($det === null || $det === ',') {
            return true;
        }

        $msg = 'Semicolon (;) delimiter detected. This version of Chamilo requires comma (,) as the CSV separator. Please export your file again as CSV (comma-separated).';

        return $returnMessage ? get_lang($msg) : false;
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
    public static function csvToArray($filename, $delimiter = ','): array
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

        $reader = new Xls();
        $spreadsheet = $reader->load($filename);
        $sheet = $spreadsheet->getActiveSheet();

        return $sheet->toArray();
    }

    public static function csvColumnToArray($filename, $columnIndex = 0): array
    {
        if (empty($filename) || !is_readable($filename)) {
            return [];
        }

        $reader = Reader::createFromPath($filename, 'r');

        // Use semicolon as delimiter.
        $reader->setDelimiter(';');

        // Ensure we do not include BOM in data (defensive: only if the method exists).
        // In League CSV v9 the BOM is excluded by default; this call is just a safe guard.
        if (method_exists($reader, 'includeInputBOM')) {
            // Do not include the BOM as part of the first value.
            $reader->includeInputBOM(false);
        }

        // Skip empty records to avoid [null] rows.
        $reader->skipEmptyRecords();

        // Read the requested column as an iterator and materialize it.
        $values = iterator_to_array($reader->fetchColumn($columnIndex), false);

        // Extra safety: remove UTF-8 BOM if it somehow slipped into the first value.
        if (isset($values[0]) && is_string($values[0])) {
            // Remove leading BOM from the first element only.
            $values[0] = preg_replace('/^\xEF\xBB\xBF/u', '', $values[0]);
        }

        return $values;
    }
}
