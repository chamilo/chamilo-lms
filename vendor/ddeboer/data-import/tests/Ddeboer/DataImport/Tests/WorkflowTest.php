<?php

namespace Ddeboer\DataImport\Tests;

use Ddeboer\DataImport\Exception\WriterException;
use Ddeboer\DataImport\Reader\ArrayReader;
use Ddeboer\DataImport\Writer\ArrayWriter;
use Ddeboer\DataImport\Workflow;
use Ddeboer\DataImport\Filter\CallbackFilter;
use Ddeboer\DataImport\ValueConverter\CallbackValueConverter;
use Ddeboer\DataImport\ItemConverter\CallbackItemConverter;
use Ddeboer\DataImport\Writer\CallbackWriter;
use Ddeboer\DataImport\Exception\SourceNotFoundException;

class WorkflowTest extends \PHPUnit_Framework_TestCase
{
    public function testAddCallbackFilter()
    {
        $this->getWorkflow()->addFilter(new CallbackFilter(function () {
            return true;
        }));
    }

    public function testAddCallbackValueConverter()
    {
        $this->getWorkflow()->addValueConverter('someField', new CallbackValueConverter(function($input) {
            return str_replace('-', '', $input);
        }));
    }

    public function testAddCallbackItemConverter()
    {
        $this->getWorkflow()->addItemConverter(new CallbackItemConverter(function(array $input) {
            foreach ($input as $k=>$v) {
                if (!$v) {
                    unset($input[$k]);
                }
            }

            return $input;
        }));
    }

    public function testAddCallbackWriter()
    {
        $this->getWorkflow()->addWriter(new CallbackWriter(function($item) {
//            var_dump($item);
        }));
    }

    public function testWriterIsPreparedAndFinished()
    {
        $writer = $this->getMockBuilder('\Ddeboer\DataImport\Writer\CallbackWriter')
            ->disableOriginalConstructor()
            ->getMock();

        $writer->expects($this->once())
            ->method('prepare');

        $writer->expects($this->once())
            ->method('finish');

        $this->getWorkflow()->addWriter($writer)
            ->process();
    }

    public function testMappingAnItem()
    {
        $originalData = array(array('foo' => 'bar'));

        $ouputTestData = array();

        $writer = new ArrayWriter($ouputTestData);
        $reader = new ArrayReader($originalData);

        $workflow = new Workflow($reader);

        $workflow->addMapping('foo', 'bar')
            ->addWriter($writer)
            ->process()
        ;

        $this->assertArrayHasKey('bar', $ouputTestData[0]);
    }

    public function testMapping()
    {
        $originalData = array(array(
            'foo' => 'bar',
            'baz' => array('another' => 'thing')
        ));

        $ouputTestData = array();

        $writer = new ArrayWriter($ouputTestData);
        $reader = new ArrayReader($originalData);

        $workflow = new Workflow($reader);

        $workflow->addMapping('foo', 'bazinga')
            ->addMapping('baz', array('another' => 'somethingelse'))
            ->addWriter($writer)
            ->process()
        ;

        $this->assertArrayHasKey('bazinga', $ouputTestData[0]);
        $this->assertArrayHasKey('somethingelse', $ouputTestData[0]['baz']);
    }

    public function testWorkflowWithObjects()
    {
        $reader = new ArrayReader(array(
            new Dummy('foo'),
            new Dummy('bar'),
            new Dummy('foobar'),
        ));

        $data = array();
        $writer = new ArrayWriter($data);

        $workflow = new Workflow($reader);
        $workflow->addWriter($writer);
        $workflow->addItemConverter(new CallbackItemConverter(function($item) {
            return array('name' => $item->name);
        }));
        $workflow->addValueConverter('name', new CallbackValueConverter(function($name) {
            return strrev($name);
        }));
        $workflow->process();

        $this->assertEquals(array(
            array('name' => 'oof'),
            array('name' => 'rab'),
            array('name' => 'raboof')
        ), $data);
    }

    /**
     * @expectedException \Ddeboer\DataImport\Exception\UnexpectedTypeException
     */
    public function testItemConverterWhichReturnObjects()
    {
        $reader = new ArrayReader(array(
            new Dummy('foo'),
            new Dummy('bar'),
            new Dummy('foobar'),
        ));

        $data = array();
        $writer = new ArrayWriter($data);

        $workflow = new Workflow($reader);
        $workflow->addWriter($writer);
        $workflow->addItemConverter(new CallbackItemConverter(function($item) {
            return $item;
        }));

        $workflow->process();
    }

    /**
     * @expectedException \Ddeboer\DataImport\Exception\UnexpectedTypeException
     */
    public function testItemConverterWithObjectsAndNoItemConverters()
    {
        $reader = new ArrayReader(array(
            new Dummy('foo'),
            new Dummy('bar'),
            new Dummy('foobar'),
        ));

        $data = array();
        $writer = new ArrayWriter($data);

        $workflow = new Workflow($reader);
        $workflow->addWriter($writer);

        $workflow->process();
    }

    public function testFilterPriority()
    {
        $offsetFilter = $this->getMockBuilder('\Ddeboer\DataImport\Filter\OffsetFilter')
            ->disableOriginalConstructor()
            ->setMethods(array('filter'))
            ->getMock();
        $offsetFilter->expects($this->never())->method('filter');

        $validatorFilter = $this->getMockBuilder('\Ddeboer\DataImport\Filter\ValidatorFilter')
            ->disableOriginalConstructor()
            ->setMethods(array('filter'))
            ->getMock();
        $validatorFilter->expects($this->exactly(3))
            ->method('filter')
            ->will($this->returnValue(false));

        $this->getWorkflow()
            ->addFilter($offsetFilter)
            ->addFilter($validatorFilter)
            ->process();
    }

    public function testFilterPriorityOverride()
    {
        $offsetFilter = $this->getMockBuilder('\Ddeboer\DataImport\Filter\OffsetFilter')
            ->disableOriginalConstructor()
            ->setMethods(array('filter'))
            ->getMock();
        $offsetFilter->expects($this->exactly(3))
            ->method('filter')
            ->will($this->returnValue(false));

        $validatorFilter = $this->getMockBuilder('\Ddeboer\DataImport\Filter\ValidatorFilter')
            ->disableOriginalConstructor()
            ->setMethods(array('filter'))
            ->getMock();
        $validatorFilter->expects($this->never())->method('filter');

        $this->getWorkflow()
            ->addFilter($offsetFilter, 257)
            ->addFilter($validatorFilter)
            ->process();
    }

    public function testFilterExecution()
    {
        $result = array();
        $workflow = $this->getWorkflow();
        $workflow
            ->addWriter(new ArrayWriter($result))
            ->addFilter(new CallbackFilter(function ($item) {
                return 'James' === $item['first'];
            }))
            ->process()
        ;

        $this->assertCount(1, $result);
    }

    public function testAddFilterAfterConversion()
    {
        $filterCalledIncrementor = 0;
        $afterConversionFilterCalledIncrementor = 0;

        $workflow = $this->getWorkflow();

        $workflow->addFilter(new CallbackFilter(function () use (&$filterCalledIncrementor) {
            ++$filterCalledIncrementor;
            return true;
        }));

        $workflow->addFilterAfterConversion(new CallbackFilter(function () use (&$afterConversionFilterCalledIncrementor) {
            ++$afterConversionFilterCalledIncrementor;
            return true;
        }));

        $workflow->process();

        //there are two rows in reader, so every filter should be called thrice
        $this->assertEquals(3, $filterCalledIncrementor);
        $this->assertEquals(3, $afterConversionFilterCalledIncrementor);
    }

    public function testExceptionInterfaceThrownFromWriterIsCaught()
    {
        $originalData = array(array('foo' => 'bar'));
        $reader = new ArrayReader($originalData);

        $array = array();
        $writer = $this->getMock('Ddeboer\DataImport\Writer\ArrayWriter', array(), array(&$array));

        $exception = new SourceNotFoundException("Log me!");

        $writer->expects($this->once())
            ->method('writeItem')
            ->with($originalData[0])
            ->will($this->throwException($exception));

        $logger = $this->getMock('Psr\Log\LoggerInterface');
        $logger->expects($this->once())
            ->method('error')
            ->with($exception->getMessage());


        $workflow = new Workflow($reader, $logger);
        $workflow->setSkipItemOnFailure(true);
        $workflow->addWriter($writer);
        $workflow->process();
    }

    public function testNullValueIsConverted()
    {
        $workflow = $this->getWorkflow();
        $valueConverter = $this->getMockBuilder('Ddeboer\DataImport\ValueConverter\ValueConverterInterface')
            ->getMock()
        ;
        $valueConverter->expects($this->exactly(3))->method('convert');
        $workflow->addValueConverter('first', $valueConverter);
        $workflow->process();
    }

    public function testWorkflowThrowsExceptionIfNameNotString()
    {
        $reader = $this->getMock('Ddeboer\DataImport\Reader\ReaderInterface');
        $this->setExpectedException("InvalidArgumentException", "Name identifier should be a string. Given: 'stdClass'");
        $workflow = new Workflow($reader, null, new \stdClass);
    }

    public function testWorkflowResultWhenAllSuccessful()
    {
        $workflow   = $this->getWorkflow();
        $result     = $workflow->process();

        $this->assertInstanceOf('Ddeboer\DataImport\Result', $result);
        $this->assertInstanceOf('DateTime', $result->getStartTime());
        $this->assertInstanceOf('DateTime', $result->getEndTime());
        $this->assertInstanceOf('DateInterval', $result->getElapsed());
        $this->assertInstanceOf('Ddeboer\DataImport\Result', $result);
        $this->assertSame(3, $result->getTotalProcessedCount());
        $this->assertSame(3, $result->getSuccessCount());
        $this->assertSame(0, $result->getErrorCount());
        $this->assertFalse($result->hasErrors());
        $this->assertSame(array(), $result->getExceptions());
        $this->assertSame(null, $result->getName());
    }

    public function testMultipleMappingsForAnItemAfterAnotherItemConverterwasAdded()
    {
        $originalData = array(array('foo' => 'bar', 'baz' => 'value'));

        $ouputTestData = array();

        $writer = new ArrayWriter($ouputTestData);
        $reader = new ArrayReader($originalData);

        $workflow = new Workflow($reader);

        // add a dummy item converter
        $workflow->addItemConverter(new CallbackItemConverter(function($item) {
                return $item;
            }));

        // add multiple mappings
        $workflow
            ->addMapping('foo', 'bar')
            ->addMapping('baz', 'bazzoo')
            ->addWriter($writer)
            ->process()
        ;

        $this->assertArrayHasKey('bar', $ouputTestData[0]);
        $this->assertArrayHasKey('bazzoo', $ouputTestData[0]);
    }

    public function testWorkflowResultWithExceptionThrowFromWriter()
    {
        $workflow   = $this->getWorkflow();
        $workflow->setSkipItemOnFailure(true);
        $writer     = $this->getMock('Ddeboer\DataImport\Writer\WriterInterface');

        $e = new WriterException();

        $writer
            ->expects($this->at(1))
            ->method('writeItem')
            ->with(array('first' => 'James', 'last'  => 'Bond'))
            ->will($this->throwException($e));

        $workflow->addWriter($writer);
        $result = $workflow->process();

        $this->assertInstanceOf('Ddeboer\DataImport\Result', $result);
        $this->assertInstanceOf('DateTime', $result->getStartTime());
        $this->assertInstanceOf('DateTime', $result->getEndTime());
        $this->assertInstanceOf('DateInterval', $result->getElapsed());
        $this->assertInstanceOf('Ddeboer\DataImport\Result', $result);
        $this->assertSame(3, $result->getTotalProcessedCount());
        $this->assertSame(2, $result->getSuccessCount());
        $this->assertSame(1, $result->getErrorCount());
        $this->assertTrue($result->hasErrors());
        $this->assertSame(array($e), $result->getExceptions());
        $this->assertSame(null, $result->getName());
    }

    protected function getWorkflow()
    {
        $reader = new ArrayReader(array(
            array(
                'first' => 'James',
                'last'  => 'Bond'
            ),
            array(
                'first' => 'Miss',
                'last'  => 'Moneypenny'
            ),
            array(
                'first' => null,
                'last'  => 'Doe'
            )
        ));

        return new Workflow($reader);
    }
}

class Dummy
{
    public $name;

    public function __construct($name)
    {
        $this->name = $name;
    }
}
