<?php

/*
 * This file is part of the Sonata package.
 *
 * (c) Thomas Rabaix <thomas.rabaix@sonata-project.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Exporter\Test\Source;

use Exporter\Writer\FormattedBoolWriter;

/**
 * Format boolean before use another writer.
 *
 * @author Robin Chalas <robin.chalas@gmail.com>
 */
class FormattedBoolWriterTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var string
     */
    protected $trueLabel;

    /**
     * @var string
     */
    protected $falseLabel;

    public function setUp()
    {
        $this->trueLabel = 'yes';
        $this->falseLabel = 'no';
    }

    public function testValidDataFormat()
    {
        $data = array('john', 'doe', false, true);
        $expected =  array('john', 'doe', 'no', 'yes');
        $mock = $this->getMockBuilder('Exporter\Writer\XlsWriter')
                       ->setConstructorArgs(array('formatedbool.xls', false))
                       ->getMock();
        $mock->expects($this->any())
               ->method('write')
               ->with($this->equalTo($expected));
        $writer = new FormattedBoolWriter($mock, $this->trueLabel, $this->falseLabel);
        $writer->open();
        $writer->write($data);
        $writer->close();
    }
}
