<?php

/* See license terms in /license.txt */

use Chamilo\CoreBundle\Component\Editor\Connector;
use Chamilo\CoreBundle\Component\Filesystem\Data;
use Ddeboer\DataImport\Writer\CsvWriter;
use Ddeboer\DataImport\Writer\ExcelWriter;
use MediaAlchemyst\Alchemyst;
use MediaAlchemyst\DriversContainer;
use Neutron\TemporaryFilesystem\Manager;
use Neutron\TemporaryFilesystem\TemporaryFilesystem;
use Symfony\Component\Filesystem\Filesystem;

/**
 *  This is the export library for Chamilo.
 *	Include/require it in your code to use its functionality.
 *	Several functions below are adaptations from functions distributed by www.nexen.net.
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
     * @param array  $data
     * @param string $filename
     * @param bool   $writeOnly Whether to only write on disk or also send for download
     * @param string $enclosure
     *
     * @return mixed csv raw data | false if no data to export | string file path if success in $writeOnly mode
     */
    public static function arrayToCsv($data, $filename = 'export', $writeOnly = false, $enclosure = '"')
    {
        if (empty($data)) {
            return false;
        }

        $enclosure = !empty($enclosure) ? $enclosure : '"';

        $filePath = api_get_path(SYS_ARCHIVE_PATH).uniqid('').'.csv';
        $stream = fopen($filePath, 'w');
        $writer = new CsvWriter(';', $enclosure, $stream, true);
        $writer->prepare();

        foreach ($data as $item) {
            if (empty($item)) {
                $writer->writeItem([]);
                continue;
            }
            $item = array_map('trim', $item);
            $writer->writeItem($item);
        }
        $writer->finish();

        if (!$writeOnly) {
            DocumentManager::file_send_for_download($filePath, true, $filename.'.csv');
            exit;
        }

        return $filePath;
    }

    /**
     * Export tabular data to XLS-file.
     *
     * @param array  $data
     * @param string $filename
     */
    public static function arrayToXls($data, $filename = 'export', $encoding = 'utf-8')
    {
        $filePath = api_get_path(SYS_ARCHIVE_PATH).uniqid('').'.xlsx';

        $file = new \SplFileObject($filePath, 'w');
        $writer = new ExcelWriter($file);
        @$writer->prepare();
        foreach ($data as $row) {
            @$writer->writeItem($row);
        }

        @$writer->finish();

        DocumentManager::file_send_for_download($filePath, true, $filename.'.xlsx');
        exit;
    }

    /**
     * Export tabular data to XLS-file included comments.
     *
     * @param array $data The comment by cell should be added with the prefix [comment] to be added ($txtCellValue.'[comment]'.$txtComment)
     */
    public static function arrayToXlsAndComments(
        array $data,
        string $filename = 'export'
    ) {
        $filePath = api_get_path(SYS_ARCHIVE_PATH).uniqid('').'.xlsx';
        $file = new \SplFileObject($filePath, 'w');

        $excel = @new PHPExcel();

        $type = 'Excel2007';
        $sheet = null;
        $row = 1;
        $prependHeaderRow = false;
        if (null !== $sheet && !$excel->sheetNameExists($sheet)) {
            $excel->removeSheetByIndex(0);
        }

        if (null !== $sheet) {
            if (!$excel->sheetNameExists($sheet)) {
                $excel->createSheet()->setTitle($sheet);
            }
            $excel->setActiveSheetIndexByName($sheet);
        }

        foreach ($data as $item) {
            $count = count($item);
            if ($prependHeaderRow && 1 == $row) {
                $headers = array_keys($item);

                for ($i = 0; $i < $count; $i++) {
                    @$excel->getActiveSheet()->setCellValueByColumnAndRow($i, $row, $headers[$i]);
                }
                $row++;
            }
            $values = array_values($item);
            for ($i = 0; $i < $count; $i++) {
                $txtComment = '';
                $txtValue = $values[$i];
                if (false !== strpos($values[$i], '[comment]')) {
                    list($txtValue, $txtComment) = explode('[comment]', $values[$i]);
                }
                @$excel->getActiveSheet()->setCellValueByColumnAndRow($i, $row, $txtValue);
                if (!empty($txtComment)) {
                    $columnLetter = PHPExcel_Cell::stringFromColumnIndex($i);
                    $coordinate = $columnLetter.$row;
                    @$excel->getActiveSheet()->getComment($coordinate)->getText()->createTextRun($txtComment);
                }
            }
            $row++;
        }

        $writer = \PHPExcel_IOFactory::createWriter($excel, $type);
        $writer->save($file->getPathname());
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
            if ($encoding != 'utf-8') {
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
     * Export hierarchical tabular data to XML-file.
     *
     * @param array  Hierarchical array of data to put in XML, each element presenting a 'name' and a 'value' property
     * @param string Name of file to be given to the user
     * @param string Name of common tag to place each line in
     * @param string Name of the root element. A root element should always be given.
     * @param string Encoding in which the data is provided
     */
    public static function export_complex_table_xml(
        $data,
        $filename = 'export',
        $wrapper_tagname = null,
        $encoding = 'ISO-8859-1'
    ) {
        $file = api_get_path(SYS_ARCHIVE_PATH).'/'.uniqid('').'.xml';
        $handle = fopen($file, 'a+');
        fwrite($handle, '<?xml version="1.0" encoding="'.$encoding.'"?>'."\n");

        if (!is_null($wrapper_tagname)) {
            fwrite($handle, '<'.$wrapper_tagname.'>');
        }
        $s = self::_export_complex_table_xml_helper($data);
        fwrite($handle, $s);
        if (!is_null($wrapper_tagname)) {
            fwrite($handle, '</'.$wrapper_tagname.'>'."\n");
        }
        fclose($handle);
        DocumentManager::file_send_for_download($file, true, $filename.'.xml');

        return false;
    }

    /**
     * Helper for the hierarchical XML exporter.
     *
     * @param   array   Hierarhical array composed of elements of type ('name'=>'xyz','value'=>'...')
     * @param   int     Level of recursivity. Allows the XML to be finely presented
     *
     * @return string The XML string to be inserted into the root element
     */
    public static function _export_complex_table_xml_helper($data, $level = 1)
    {
        if (count($data) < 1) {
            return '';
        }
        $string = '';
        foreach ($data as $row) {
            $string .= "\n".str_repeat("\t", $level).'<'.$row['name'].'>';
            if (is_array($row['value'])) {
                $string .= self::_export_complex_table_xml_helper($row['value'], $level + 1)."\n";
                $string .= str_repeat("\t", $level).'</'.$row['name'].'>';
            } else {
                $string .= $row['value'];
                $string .= '</'.$row['name'].'>';
            }
        }

        return $string;
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
        $table_tp_html = $table->toHtml();

        return $table_tp_html;
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
        $unoconv = api_get_configuration_value('unoconv.binaries');

        if (empty($unoconv)) {
            return false;
        }

        if (!empty($html)) {
            $fs = new Filesystem();
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
            }
        }
    }
}
