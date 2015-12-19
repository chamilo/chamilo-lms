<?php

namespace Exporter\Test\Source;

use Exporter\Source\XmlSourceIterator;

class XmlSourceIteratorTest extends \PHPUnit_Framework_TestCase
{
    protected $filename;
    protected $filenameCustomTagNames;

    public function setUp()
    {
        $this->filename = 'source_xml.xml';
        $xml = '<?xml version="1.0" ?><datas><data><sku><![CDATA[123]]></sku><ean><![CDATA[1234567891234]]></ean><name><![CDATA[Product é]]></name></data><data><sku><![CDATA[124]]></sku><ean><![CDATA[1234567891235]]></ean><name><![CDATA[Product @]]></name></data><data><sku><![CDATA[125]]></sku><ean><![CDATA[1234567891236]]></ean><name><![CDATA[Product 3 ©]]></name></data></datas>';
        $this->createXmlFile($this->filename, $xml);

        // for custom tag names
        $this->filenameCustomTagNames = 'source_xml_custom_tag_names.xml';
        $xml = '<?xml version="1.0" ?><channel><item><sku><![CDATA[123]]></sku><ean><![CDATA[1234567891234]]></ean><name><![CDATA[Product é]]></name></item><item><sku><![CDATA[124]]></sku><ean><![CDATA[1234567891235]]></ean><name><![CDATA[Product @]]></name></item><item><sku><![CDATA[125]]></sku><ean><![CDATA[1234567891236]]></ean><name><![CDATA[Product 3 ©]]></name></item></channel>';
        $this->createXmlFile($this->filenameCustomTagNames, $xml);
    }

    public function testHandler()
    {
        $iterator = new XmlSourceIterator($this->filename);

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

    public function testRewind()
    {
        $iterator = new XmlSourceIterator($this->filename);

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

    public function testCustomTagNames()
    {
        $iterator = new XmlSourceIterator($this->filenameCustomTagNames, 'channel', 'item');

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

    public function tearDown()
    {
        unlink($this->filename);
        unlink($this->filenameCustomTagNames);
    }

    protected function createXmlFile($filename, $content)
    {
        if (is_file($filename)) {
            unlink($filename);
        }

        file_put_contents($filename, $content);
    }
}
