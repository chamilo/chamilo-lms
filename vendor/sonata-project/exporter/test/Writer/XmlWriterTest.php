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

use Exporter\Test\AbstractTypedWriterTestCase;
use Exporter\Writer\XmlWriter;

class XmlWriterTest extends AbstractTypedWriterTestCase
{
    protected $filename;

    public function setUp()
    {
        parent::setUp();
        $this->filename = 'foobar.xml';

        if (is_file($this->filename)) {
            unlink($this->filename);
        }
    }

    public function tearDown()
    {
        if (is_file($this->filename)) {
            unlink($this->filename);
        }
    }

    /**
     * @expectedException \RuntimeException
     */
    public function testArrayDataFormat()
    {
        $writer = new XmlWriter($this->filename);
        $writer->open();

        $writer->write(array('firstname' => 'john "2', 'lastname' => 'doe', 'id' => '1', 'tags' => array('foo', 'bar')));
        $writer->close();
    }

    public function testInvalidDataFormat()
    {
        $writer = new XmlWriter($this->filename);
        $writer->open();

        $writer->write(array('firstname' => 'john 1', 'lastname' => 'doe', 'id' => '1'));
        $writer->write(array('firstname' => 'john 3', 'lastname' => 'doe', 'id' => '1'));
        $writer->close();

        $expected = <<<'XML'
<?xml version="1.0" ?>
<datas>
<data>
<firstname><![CDATA[john 1]]></firstname>
<lastname><![CDATA[doe]]></lastname>
<id><![CDATA[1]]></id>
</data>
<data>
<firstname><![CDATA[john 3]]></firstname>
<lastname><![CDATA[doe]]></lastname>
<id><![CDATA[1]]></id>
</data>
</datas>
XML;

        $this->assertEquals($expected, file_get_contents($this->filename));
    }

    protected function getWriter()
    {
        return new XmlWriter('/tmp/whatever.xml');
    }
}
