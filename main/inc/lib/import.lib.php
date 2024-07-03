<?php

/* For licensing terms, see /license.txt */

use Ddeboer\DataImport\Reader\ExcelReader;
use League\Csv\Reader;
use Symfony\Component\DomCrawler\Crawler;

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
    public static function csvToArray($filename)
    {
        if (empty($filename)) {
            return [];
        }

        $reader = Reader::createFromPath($filename, 'r');
        if ($reader) {
            $reader->setDelimiter(';');
            $reader->stripBom(true);
            /*$contents = $reader->__toString();
            if (!Utf8::isUtf8($contents)) {
                // If file is not in utf8 try converting to ISO-8859-15
                if ($reader->getStreamFilterMode() == 1) {
                    $reader->appendStreamFilter('convert.iconv.ISO-8859-15/UTF-8');
                }
            }*/

            $iterator = $reader->fetchAssoc(0);

            return iterator_to_array($iterator);
        }

        return [];
    }

    public static function csvColumnToArray($filename, $columnIndex = 0): array
    {
        if (empty($filename)) {
            return [];
        }

        $reader = Reader::createFromPath($filename, 'r');

        if (!$reader) {
            return [];
        }

        $reader->setDelimiter(';');
        $reader->stripBom(true);

        $iterator = $reader->fetchColumn($columnIndex);

        return iterator_to_array($iterator);
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

        $file = new \SplFileObject($filename);

        return new ExcelReader($file, 0);
    }

    /**
     * @param string $file
     *
     * @return Crawler
     */
    public static function xml($file)
    {
        return self::xmlFromString(file_get_contents($file));
    }

    /**
     * @param string $contents
     *
     * @return Crawler
     */
    public static function xmlFromString($contents)
    {
        @libxml_disable_entity_loader(true);

        $crawler = new Crawler();
        $crawler->addXmlContent($contents);

        return $crawler;
    }
}
