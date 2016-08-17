<?php

/*
 * This file is part of the Sonata Project package.
 *
 * (c) Thomas Rabaix <thomas.rabaix@sonata-project.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Exporter\Test\Writer;

use Exporter\Writer\XmlExcelWriter;

class XmlExcelWriterTest extends \PHPUnit_Framework_TestCase
{
    protected $filename;

    public function setUp()
    {
        $this->filename = 'foobar.csv';

        if (is_file($this->filename)) {
            unlink($this->filename);
        }
    }

    public function tearDown()
    {
        unlink($this->filename);
    }

    public function testWriter()
    {
        $writer = new XmlExcelWriter($this->filename, false);
        $writer->open();

        $writer->write(array(' john', 'doe &', 'é'));

        $writer->close();

        $expected = '<Row><Cell><Data ss:Type="String"> john</Data></Cell><Cell><Data ss:Type="String">doe &amp;</Data></Cell><Cell><Data ss:Type="String">é</Data></Cell></Row>';

        $this->assertTrue(strstr(file_get_contents($this->filename), $expected) !== false);
    }

    public function testWithHeaders()
    {
        $writer = new XmlExcelWriter($this->filename, true);
        $writer->open();

        $writer->write(array('name' => 'john', 'surname' => 'doe ', 'year' => '2001'));

        $writer->close();

        $expected = '<Row><Cell><Data ss:Type="String">name</Data></Cell><Cell><Data ss:Type="String">surname</Data></Cell><Cell><Data ss:Type="String">year</Data></Cell></Row>';
        $expected .= '<Row><Cell><Data ss:Type="String">john</Data></Cell><Cell><Data ss:Type="String">doe </Data></Cell><Cell><Data ss:Type="Number">2001</Data></Cell></Row';

        $this->assertTrue(strstr(file_get_contents($this->filename), $expected) !== false);
    }

    public function testForceTypes()
    {
        // force all cells to have Number type
        $writer = new XmlExcelWriter($this->filename, false, 'Number');
        $writer->open();

        $writer->write(array('name' => 'john', 'surname' => 'doe ', 'year' => '2001'));

        $writer->close();

        $expected = '<Row><Cell><Data ss:Type="Number">john</Data></Cell><Cell><Data ss:Type="Number">doe </Data></Cell><Cell><Data ss:Type="Number">2001</Data></Cell></Row>';

        $this->assertTrue(strstr(file_get_contents($this->filename), $expected) !== false);
    }

    public function testForceTypesWithHeaders()
    {
        // force all cells to have Number type
        $writer = new XmlExcelWriter($this->filename, true, 'Number');
        $writer->open();

        $writer->write(array('name' => 'john', 'surname' => 'doe ', 'year' => '2001'));

        $writer->close();

        $expected = '<Row><Cell><Data ss:Type="String">name</Data></Cell><Cell><Data ss:Type="String">surname</Data></Cell><Cell><Data ss:Type="String">year</Data></Cell></Row>';
        $expected .= '<Row><Cell><Data ss:Type="Number">john</Data></Cell><Cell><Data ss:Type="Number">doe </Data></Cell><Cell><Data ss:Type="Number">2001</Data></Cell></Row>';

        $this->assertTrue(strstr(file_get_contents($this->filename), $expected) !== false);
    }

    public function testSpecificTypes()
    {
        // define type for specific cell
        $writer = new XmlExcelWriter($this->filename, false, array('year' => 'String', 'surname' => 'Number'));
        $writer->open();

        $writer->write(array('name' => 'john', 'surname' => 'doe ', 'year' => '2001'));

        $writer->close();

        $expected = '<Row><Cell><Data ss:Type="String">john</Data></Cell><Cell><Data ss:Type="Number">doe </Data></Cell><Cell><Data ss:Type="String">2001</Data></Cell></Row>';

        $this->assertTrue(strstr(file_get_contents($this->filename), $expected) !== false);
    }
}
