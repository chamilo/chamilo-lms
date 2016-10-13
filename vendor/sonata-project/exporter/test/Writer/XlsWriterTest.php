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
use Exporter\Writer\XlsWriter;

class XlsWriterTest extends AbstractTypedWriterTestCase
{
    protected $filename;

    public function setUp()
    {
        parent::setUp();
        $this->filename = 'foobar.xls';

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

    public function testValidDataFormat()
    {
        $writer = new XlsWriter($this->filename, false);
        $writer->open();

        $writer->write(array('john "2', 'doe', '1'));
        $writer->close();

        $expected = '<html><head><meta http-equiv="Content-Type" content="text/html; charset=utf-8" /><meta name=ProgId content=Excel.Sheet><meta name=Generator content="https://github.com/sonata-project/exporter"></head><body><table><tr><td>john "2</td><td>doe</td><td>1</td></tr></table></body></html>';

        $this->assertEquals($expected, trim(file_get_contents($this->filename)));
    }

    public function testWithHeaders()
    {
        $writer = new XlsWriter($this->filename);
        $writer->open();

        $writer->write(array('firtname' => 'john "2', 'surname' => 'doe', 'year' => '1'));
        $writer->close();

        $expected = '<html><head><meta http-equiv="Content-Type" content="text/html; charset=utf-8" /><meta name=ProgId content=Excel.Sheet><meta name=Generator content="https://github.com/sonata-project/exporter"></head><body><table><tr><th>firtname</th><th>surname</th><th>year</th></tr><tr><td>john "2</td><td>doe</td><td>1</td></tr></table></body></html>';

        $this->assertEquals($expected, trim(file_get_contents($this->filename)));
    }

    protected function getWriter()
    {
        return new XlsWriter('/tmp/whatever.xls', false);
    }
}
