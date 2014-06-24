<?php

namespace Exporter\Test\Source;

use Exporter\Source\XmlSourceIterator;

class XmlSourceIteratorTest extends \PHPUnit_Framework_TestCase
{
    protected $filename;

    public function setUp()
    {
        $this->filename = 'foobar.xml';

        if (is_file($this->filename)) {
            unlink($this->filename);
        }

        $xml= '<?xml version="1.0" ?><datas><data><sku><![CDATA[123]]></sku><ean><![CDATA[1234567891234]]></ean><name><![CDATA[Product é]]></name></data><data><sku><![CDATA[124]]></sku><ean><![CDATA[1234567891235]]></ean><name><![CDATA[Product @]]></name></data><data><sku><![CDATA[125]]></sku><ean><![CDATA[1234567891236]]></ean><name><![CDATA[Product 3 ©]]></name></data></datas>';
        file_put_contents($this->filename, $xml);
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
            $i++;
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
            $i++;
        }
        $this->assertEquals(3, $i);

        $i = 0;
        foreach ($iterator as $value) {
            $this->assertTrue(is_array($value));
            $this->assertEquals(3, count($value));
            $i++;
        }
        $this->assertEquals(3, $i);
    }

    public function tearDown()
    {
        unlink($this->filename);
    }
}
