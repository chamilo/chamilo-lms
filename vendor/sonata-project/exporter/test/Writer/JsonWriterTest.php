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
use Exporter\Writer\JsonWriter;

class JsonWriterTest extends AbstractTypedWriterTestCase
{
    protected $filename;

    public function setUp()
    {
        parent::setUp();
        $this->filename = 'foobar.json';

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

    public function testWrite()
    {
        $writer = new JsonWriter($this->filename, ',', '');
        $writer->open();

        $writer->write(array('john "2', 'doe', '1'));
        $writer->write(array('john 3', 'doe', '1'));

        $writer->close();

        $expected = '[["john \"2","doe","1"],["john 3","doe","1"]]';
        $content = file_get_contents($this->filename);

        $this->assertEquals($expected, $content);

        $expected = array(
            array('john "2', 'doe', '1'),
            array('john 3', 'doe', '1'),
        );

        $this->assertEquals($expected, json_decode($content, false));
    }

    protected function getWriter()
    {
        return new JsonWriter('/tmp/whatever.json');
    }
}
