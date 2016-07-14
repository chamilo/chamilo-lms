<?php

/*
 * This file is part of the Sonata Project package.
 *
 * (c) Thomas Rabaix <thomas.rabaix@sonata-project.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Exporter\Writer;

/**
 * Generate a Xml Excel file.
 *
 * @author Vincent Touzet <vincent.touzet@gmail.com>
 */
class XmlExcelWriter implements WriterInterface
{
    /**
     * @var string|null
     */
    protected $filename = null;

    /**
     * @var resource|null
     */
    protected $file = null;

    /**
     * @var bool
     */
    protected $showHeaders;

    /**
     * @var mixed|null
     */
    protected $columnsType = null;

    /**
     * @var int
     */
    protected $position = 0;

    /**
     * @var string
     */
    protected $header = '<?xml version="1.0"?><?mso-application progid="Excel.Sheet"?><Workbook xmlns="urn:schemas-microsoft-com:office:spreadsheet" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns:x2="http://schemas.microsoft.com/office/excel/2003/xml" xmlns:ss="urn:schemas-microsoft-com:office:spreadsheet" xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:html="http://www.w3.org/TR/REC-html40" xmlns:c="urn:schemas-microsoft-com:office:component:spreadsheet"><OfficeDocumentSettings xmlns="urn:schemas-microsoft-com:office:office"></OfficeDocumentSettings><ExcelWorkbook xmlns="urn:schemas-microsoft-com:office:excel"></ExcelWorkbook><Worksheet ss:Name="Sheet 1"><Table>';
    protected $footer = '</Table></Worksheet></Workbook>';

    /**
     * @param string $filename
     * @param bool   $showHeaders
     * @param mixed  $columnsType Define cells type to use
     *                            If string: force all cells to the given type. e.g: 'Number'
     *                            If array: force only given cells. e.g: array('ean'=>'String', 'price'=>'Number')
     *                            If null: will guess the type. 'Number' if value is numeric, 'String' otherwise
     */
    public function __construct($filename, $showHeaders = true, $columnsType = null)
    {
        $this->filename = $filename;
        $this->showHeaders = $showHeaders;
        $this->columnsType = $columnsType;

        if (is_file($filename)) {
            throw new \RuntimeException(sprintf('The file %s already exist', $filename));
        }
    }

    public function open()
    {
        $this->file = fopen($this->filename, 'w');
        fwrite($this->file, $this->header);
    }

    /**
     * @param array $data
     */
    public function write(array $data)
    {
        if ($this->position == 0 && $this->showHeaders) {
            $header = array_keys($data);
            fwrite($this->file, $this->getXmlString($header));
            ++$this->position;
        }

        fwrite($this->file, $this->getXmlString($data));
        ++$this->position;
    }

    public function close()
    {
        fwrite($this->file, $this->footer);
        fclose($this->file);
    }

    /**
     * Prepare and return XML string for MS Excel XML from array.
     *
     * @param array $fields
     *
     * @return string
     */
    private function getXmlString(array $fields = array())
    {
        $xmlData = array();
        $xmlData[] = '<Row>';
        foreach ($fields as $key => $value) {
            $value = htmlspecialchars($value);

            $value = str_replace(array("\r\n", "\r", "\n"), '&#10;', $value);
            $dataType = 'String';
            if ($this->position != 0 || !$this->showHeaders) {
                $dataType = $this->getDataType($key, $value);
            }
            $xmlData[] = '<Cell><Data ss:Type="'.$dataType.'">'.$value.'</Data></Cell>';
        }
        $xmlData[] = '</Row>';

        return implode('', $xmlData);
    }

    /**
     * @param string $key
     * @param string $value
     *
     * @return string
     */
    private function getDataType($key, $value)
    {
        $dataType = null;
        if (!is_null($this->columnsType)) {
            if (is_string($this->columnsType)) {
                $dataType = $this->columnsType;
            } elseif (is_array($this->columnsType)) {
                if (array_key_exists($key, $this->columnsType)) {
                    $dataType = $this->columnsType[$key];
                }
            }
        }
        if (is_null($dataType)) {
            // guess the type
            if (is_numeric($value)) {
                $dataType = 'Number';
            } else {
                $dataType = 'String';
            }
        }

        return $dataType;
    }
}
