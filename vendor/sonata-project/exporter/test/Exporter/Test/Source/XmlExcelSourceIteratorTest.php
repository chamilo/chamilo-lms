<?php

namespace Exporter\Test\Source;

use Exporter\Source\XmlExcelSourceIterator;

class XmlExcelSourceIteratorTest extends \PHPUnit_Framework_TestCase
{
    protected $filename;
    protected $headers = array('sku', 'ean', 'name');

    public function setUp()
    {
        $this->filename = 'foobar.xml';
        $this->filenameSS = 'foobar_ss.xml';

        if (is_file($this->filename)) {
            unlink($this->filename);
        }
        if (is_file($this->filenameSS)) {
            unlink($this->filenameSS);
        }

        $xml = '<?xml version="1.0"?><?mso-application progid="Excel.Sheet"?><Workbook xmlns="urn:schemas-microsoft-com:office:spreadsheet" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns:x2="http://schemas.microsoft.com/office/excel/2003/xml" xmlns:ss="urn:schemas-microsoft-com:office:spreadsheet" xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:html="http://www.w3.org/TR/REC-html40" xmlns:c="urn:schemas-microsoft-com:office:component:spreadsheet"><OfficeDocumentSettings xmlns="urn:schemas-microsoft-com:office:office"></OfficeDocumentSettings><ExcelWorkbook xmlns="urn:schemas-microsoft-com:office:excel"></ExcelWorkbook><Worksheet ss:Name="Sheet 1"><Table><Row><Cell><Data ss:Type="String">sku</Data></Cell><Cell><Data ss:Type="String">ean</Data></Cell><Cell><Data ss:Type="String">name</Data></Cell></Row><Row><Cell><Data ss:Type="String">123</Data></Cell><Cell><Data ss:Type="String">1234567891234</Data></Cell><Cell><Data ss:Type="String">Product &#xE9;</Data></Cell></Row><Row><Cell><Data ss:Type="String">124</Data></Cell><Cell><Data ss:Type="String">1234567891235</Data></Cell><Cell><Data ss:Type="String">Product @</Data></Cell></Row><Row><Cell><Data ss:Type="String">125</Data></Cell><Cell><Data ss:Type="String">1234567891236</Data></Cell><Cell><Data ss:Type="String">Product 3 &#xA9;</Data></Cell></Row></Table></Worksheet></Workbook>';
        file_put_contents($this->filename, $xml);
        $xmlSS = '<?xml version="1.0"?><?mso-application progid="Excel.Sheet"?><Workbook xmlns="urn:schemas-microsoft-com:office:spreadsheet" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns:x2="http://schemas.microsoft.com/office/excel/2003/xml" xmlns:ss="urn:schemas-microsoft-com:office:spreadsheet" xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:html="http://www.w3.org/TR/REC-html40" xmlns:c="urn:schemas-microsoft-com:office:component:spreadsheet"><OfficeDocumentSettings xmlns="urn:schemas-microsoft-com:office:office"></OfficeDocumentSettings><ExcelWorkbook xmlns="urn:schemas-microsoft-com:office:excel"></ExcelWorkbook><Worksheet ss:Name="Sheet 1"><ss:Table><ss:Row><ss:Cell><Data ss:Type="String">sku</Data></ss:Cell><ss:Cell><Data ss:Type="String">ean</Data></ss:Cell><ss:Cell><Data ss:Type="String">name</Data></ss:Cell></ss:Row><ss:Row><ss:Cell><Data ss:Type="String">123</Data></ss:Cell><ss:Cell><Data ss:Type="String">1234567891234</Data></ss:Cell><ss:Cell><Data ss:Type="String">Product é</Data></ss:Cell></ss:Row><ss:Row><ss:Cell><Data ss:Type="String">124</Data></ss:Cell><ss:Cell><Data ss:Type="String">1234567891235</Data></ss:Cell><ss:Cell><Data ss:Type="String">Product @</Data></ss:Cell></ss:Row><ss:Row><ss:Cell><Data ss:Type="String">125</Data></ss:Cell><ss:Cell><Data ss:Type="String">1234567891236</Data></ss:Cell><ss:Cell><Data ss:Type="String">Product 3 ©</Data></ss:Cell></ss:Row></ss:Table></Worksheet></Workbook>';
        file_put_contents($this->filenameSS, $xmlSS);
    }

    public function testHandler()
    {
        $iterator = new XmlExcelSourceIterator($this->filename);

        $i = 0;
        foreach ($iterator as $value) {
            $this->assertTrue(is_array($value));
            $this->assertEquals(3, count($value));
            $keys = array_keys($value);
            $this->assertEquals($i, $iterator->key());
            $this->assertEquals('sku', $keys[0]);
            $this->assertEquals('ean', $keys[1]);
            $this->assertEquals('name', $keys[2]);
            ++$i;
        }
        $this->assertEquals(3, $i);
    }

    public function testHandlerSS()
    {
        $iterator = new XmlExcelSourceIterator($this->filenameSS);

        $i = 0;
        foreach ($iterator as $value) {
            $this->assertTrue(is_array($value));
            $this->assertEquals(3, count($value));
            $keys = array_keys($value);
            $this->assertEquals($i, $iterator->key());
            $this->assertEquals('sku', $keys[0]);
            $this->assertEquals('ean', $keys[1]);
            $this->assertEquals('name', $keys[2]);
            ++$i;
        }
        $this->assertEquals(3, $i);
    }

    public function testNoHeaders()
    {
        $iterator = new XmlExcelSourceIterator($this->filename, false);

        $i = 0;
        foreach ($iterator as $value) {
            $this->assertTrue(is_array($value));
            $this->assertEquals(3, count($value));
            $this->assertEquals($i, $iterator->key());
            ++$i;
        }
        $this->assertEquals(4, $i);
    }

    public function testRewind()
    {
        $iterator = new XmlExcelSourceIterator($this->filename);

        $i = 0;
        foreach ($iterator as $value) {
            $this->assertTrue(is_array($value));
            $this->assertEquals(3, count($value));
            ++$i;
        }
        $this->assertEquals(3, $i);

        $i = 0;
        foreach ($iterator as $value) {
            $this->assertTrue(is_array($value));
            $this->assertEquals(3, count($value));
            ++$i;
        }
        $this->assertEquals(3, $i);
    }

    public function tearDown()
    {
        unlink($this->filename);
        unlink($this->filenameSS);
    }
}
