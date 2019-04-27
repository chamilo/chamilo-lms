<?php

namespace Ddeboer\DataImport\Tests\Writer;

use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Helper\Table;

use Ddeboer\DataImport\Workflow\StepAggregator;
use Ddeboer\DataImport\Reader\ArrayReader;
use Ddeboer\DataImport\ItemConverter\MappingItemConverter;
use Ddeboer\DataImport\Writer\ConsoleTableWriter;

/**
 *  @author Igor Mukhin <igor.mukhin@gmail.com>
 */
class ConsoleTableWriterTest extends \PHPUnit_Framework_TestCase
{
    public function testRightColumnsHeadersNamesAfterItemConverter()
    {
        $data = array(
            array(
                'firstname'  => 'John',
                'lastname' => 'Doe'
            ),
            array(
                'firstname'  => 'Ivan',
                'lastname' => 'Sidorov'
            )
        );
        $reader = new ArrayReader($data);

        $output = new BufferedOutput();

        $table = $this->getMockBuilder('Symfony\Component\Console\Helper\Table')
            ->disableOriginalConstructor()
            ->getMock();

        $table->expects($this->at(2))
            ->method('addRow');

        $workflow = new StepAggregator($reader);
        $workflow
            ->addWriter(new ConsoleTableWriter($output, $table))
            ->process()
        ;
    }
}
