<?php

/* See license terms in /license.txt */

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Sonata\Exporter\Handler;
use Sonata\Exporter\Source\ArraySourceIterator;
use Sonata\Exporter\Writer\CsvWriter;
use Sonata\Exporter\Writer\XlsWriter;
use Symfony\Component\Filesystem\Filesystem;

/**
 *  This is the export library for Chamilo.
 *  Include/require it in your code to use its functionality.
 */
class Export
{
    /**
     * Constructor.
     */
    public function __construct()
    {
    }

    /**
     * Export tabular data to CSV-file.
     *
     * @return mixed csv raw data | false if no data to export | string file path if success in $writeOnly mode
     */
    public static function arrayToCsv(array $data, string $filename = 'export', bool $writeOnly = false, string $enclosure = '"')
    {
        if (empty($data)) {
            return false;
        }

        $enclosure = !empty($enclosure) ? $enclosure : '"';
        $filePath = api_get_path(SYS_ARCHIVE_PATH).uniqid('').'.csv';
        $writer = new CsvWriter($filePath, ';', $enclosure, true);

        $source = new ArraySourceIterator($data);
        $handler = Handler::create($source, $writer);
        $handler->export();

        if (!$writeOnly) {
            DocumentManager::file_send_for_download($filePath, true, $filename.'.csv');
            exit;
        }

        return $filePath;
    }

    /**
     * Converts an array of data into a CSV file and optionally sends it for download.
     *
     * @return string|void Returns the file path if $writeOnly is true, otherwise sends the file for download and exits.
     */
    public static function arrayToCsvSimple(array $data, string $filename = 'export', bool $writeOnly = false, array $header = [])
    {
        $file = api_get_path(SYS_ARCHIVE_PATH) . uniqid('') . '.csv';

        $handle = fopen($file, 'w');

        if ($handle === false) {
            throw new \RuntimeException("Unable to create or open the file: $file");
        }

        if (!empty($header)) {
            fputcsv($handle, $header, ';');
        }

        foreach ($data as $row) {
            fputcsv($handle, (array)$row, ';');
        }

        fclose($handle);

        if (!$writeOnly) {
            DocumentManager::file_send_for_download($file, true, $filename . '.csv');
            unlink($file);
            exit;
        }

        return $file;
    }

    /**
     * Export tabular data to XLS-file.
     */
    public static function arrayToXls(array $data, string $filename = 'export')
    {
        if (empty($data)) {
            return false;
        }

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $rowNumber = 1;
        foreach ($data as $row) {
            $colNumber = 'A';
            foreach ($row as $cell) {
                $sheet->setCellValue($colNumber . $rowNumber, $cell);
                $colNumber++;
            }
            $rowNumber++;
        }

        $filePath = api_get_path(SYS_ARCHIVE_PATH).uniqid('').'.xlsx';
        $writer = new Xlsx($spreadsheet);
        $writer->save($filePath);

        DocumentManager::file_send_for_download($filePath, true, $filename.'.xlsx');
        exit;
    }

    /**
     * Export tabular data to XLS-file (as html table).
     *
     * @param array  $data
     * @param string $filename
     */
    public static function export_table_xls_html($data, $filename = 'export', $encoding = 'utf-8')
    {
        $file = api_get_path(SYS_ARCHIVE_PATH).uniqid('').'.xls';
        $handle = fopen($file, 'a+');
        $systemEncoding = api_get_system_encoding();
        fwrite($handle, '<!DOCTYPE html><html><meta http-equiv="Content-Type" content="text/html" charset="'.$encoding.'" /><body><table>');
        foreach ($data as $id => $row) {
            foreach ($row as $id2 => $row2) {
                $data[$id][$id2] = api_htmlentities($row2);
            }
        }
        foreach ($data as $row) {
            $string = implode("</td><td>", $row);
            $string = '<tr><td>'.$string.'</td></tr>';
            if ('utf-8' != $encoding) {
                $string = api_convert_encoding($string, $encoding, $systemEncoding);
            }
            fwrite($handle, $string."\n");
        }
        fwrite($handle, '</table></body></html>');
        fclose($handle);
        DocumentManager::file_send_for_download($file, true, $filename.'.xls');
        exit;
    }

    /**
     * Export tabular data to XML-file.
     *
     * @param array  Simple array of data to put in XML
     * @param string Name of file to be given to the user
     * @param string Name of common tag to place each line in
     * @param string Name of the root element. A root element should always be given.
     * @param string Encoding in which the data is provided
     */
    public static function arrayToXml(
        $data,
        $filename = 'export',
        $item_tagname = 'item',
        $wrapper_tagname = null,
        $encoding = null
    ) {
        if (empty($encoding)) {
            $encoding = api_get_system_encoding();
        }
        $file = api_get_path(SYS_ARCHIVE_PATH).'/'.uniqid('').'.xml';
        $handle = fopen($file, 'a+');
        fwrite($handle, '<?xml version="1.0" encoding="'.$encoding.'"?>'."\n");
        if (!is_null($wrapper_tagname)) {
            fwrite($handle, "\t".'<'.$wrapper_tagname.'>'."\n");
        }
        foreach ($data as $row) {
            fwrite($handle, '<'.$item_tagname.'>'."\n");
            foreach ($row as $key => $value) {
                fwrite($handle, "\t\t".'<'.$key.'>'.$value.'</'.$key.'>'."\n");
            }
            fwrite($handle, "\t".'</'.$item_tagname.'>'."\n");
        }
        if (!is_null($wrapper_tagname)) {
            fwrite($handle, '</'.$wrapper_tagname.'>'."\n");
        }
        fclose($handle);
        DocumentManager::file_send_for_download($file, true, $filename.'.xml');
        exit;
    }

    /**
     * @param array $data table to be read with the HTML_table class
     */
    public static function export_table_pdf($data, $params = [])
    {
        $table_html = self::convert_array_to_html($data, $params);
        $params['format'] = isset($params['format']) ? $params['format'] : 'A4';
        $params['orientation'] = isset($params['orientation']) ? $params['orientation'] : 'P';

        $pdf = new PDF($params['format'], $params['orientation'], $params);
        $pdf->html_to_pdf_with_template($table_html);
    }

    /**
     * @param string $html
     * @param array  $params
     */
    public static function export_html_to_pdf($html, $params = [])
    {
        $params['format'] = isset($params['format']) ? $params['format'] : 'A4';
        $params['orientation'] = isset($params['orientation']) ? $params['orientation'] : 'P';

        $pdf = new PDF($params['format'], $params['orientation'], $params);
        $pdf->html_to_pdf_with_template($html);
    }

    /**
     * @param array $data
     * @param array $params
     *
     * @return string
     */
    public static function convert_array_to_html($data, $params = [])
    {
        $headers = $data[0];
        unset($data[0]);

        $header_attributes = isset($params['header_attributes']) ? $params['header_attributes'] : [];
        $table = new HTML_Table(['class' => 'table table-hover table-striped data_table', 'repeat_header' => '1']);
        $row = 0;
        $column = 0;
        foreach ($headers as $header) {
            $table->setHeaderContents($row, $column, $header);
            $attributes = [];
            if (isset($header_attributes) && isset($header_attributes[$column])) {
                $attributes = $header_attributes[$column];
            }
            if (!empty($attributes)) {
                $table->updateCellAttributes($row, $column, $attributes);
            }
            $column++;
        }
        $row++;
        foreach ($data as &$printable_data_row) {
            $column = 0;
            foreach ($printable_data_row as &$printable_data_cell) {
                $table->setCellContents($row, $column, $printable_data_cell);
                //$table->updateCellAttributes($row, $column, $atributes);
                $column++;
            }
            $table->updateRowAttributes($row, $row % 2 ? 'class="row_even"' : 'class="row_odd"', true);
            $row++;
        }

        return $table->toHtml();
    }

    /**
     * Export HTML content in a ODF document.
     *
     * @param string $html
     * @param string $name
     * @param string $format
     *
     * @return bool
     */
    public static function htmlToOdt($html, $name, $format = 'odt')
    {
        $unoconv = api_get_setting('platform.unoconv_binaries');

        if (empty($unoconv)) {
            return false;
        }

        if (!empty($html)) {
            /*$fs = new Filesystem();
            $paths = [
                'root_sys' => api_get_path(SYS_PATH),
                'path.temp' => api_get_path(SYS_ARCHIVE_PATH),
            ];
            $connector = new Connector();

            $drivers = new DriversContainer();
            $drivers['configuration'] = [
                'unoconv.binaries' => $unoconv,
                'unoconv.timeout' => 60,
            ];

            $tempFilesystem = TemporaryFilesystem::create();
            $manager = new Manager($tempFilesystem, $fs);
            $alchemyst = new Alchemyst($drivers, $manager);

            $dataFileSystem = new Data($paths, $fs, $connector, $alchemyst);
            $content = $dataFileSystem->convertRelativeToAbsoluteUrl($html);
            $filePath = $dataFileSystem->putContentInTempFile(
                $content,
                api_replace_dangerous_char($name),
                'html'
            );

            $try = true;

            while ($try) {
                try {
                    $convertedFile = $dataFileSystem->transcode(
                        $filePath,
                        $format
                    );

                    $try = false;
                    DocumentManager::file_send_for_download(
                        $convertedFile,
                        false,
                        $name.'.'.$format
                    );
                } catch (Exception $e) {
                    // error_log($e->getMessage());
                }
            }*/
        }
    }
}
