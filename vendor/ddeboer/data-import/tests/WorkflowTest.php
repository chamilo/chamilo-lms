<?php

namespace Ddeboer\DataImport\Tests;

use Ddeboer\DataImport\Exception\WriterException;
use Ddeboer\DataImport\Reader\ArrayReader;
use Ddeboer\DataImport\Step\ConverterStep;
use Ddeboer\DataImport\Step\FilterStep;
use Ddeboer\DataImport\Step\MappingStep;
use Ddeboer\DataImport\Step\ValueConverterStep;
use Ddeboer\DataImport\Writer\ArrayWriter;
use Ddeboer\DataImport\Workflow\StepAggregator;
use Ddeboer\DataImport\Filter\CallbackFilter;
use Ddeboer\DataImport\ValueConverter\CallbackValueConverter;
use Ddeboer\DataImport\ItemConverter\CallbackItemConverter;
use Ddeboer\DataImport\Writer\CallbackWriter;
use Ddeboer\DataImport\Exception\SourceNotFoundException;

class WorkflowTest extends \PHPUnit_Framework_TestCase
{
    public function testAddStep()
    {
        $step = $this->getMock('Ddeboer\DataImport\Step');

        $this->getWorkflow()->addStep($step);
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

    public function testWorkflowWithObjects()
    {
        $reader = new ArrayReader(array(
            new Dummy('foo'),
            new Dummy('bar'),
            new Dummy('foobar'),
        ));

        $data = array();
        $writer = new ArrayWriter($data);

        $workflow = new StepAggregator($reader);
        $workflow->addWriter($writer);

        $converterStep = new ConverterStep([
            function($item) { return array('name' => $item->name); }
        ]);

        $valueStep = new ValueConverterStep();
        $valueStep->add('[name]', function($name) { return strrev($name); });

        $workflow->addStep($converterStep)->addStep($valueStep);
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

        $workflow = new StepAggregator($reader);
        $workflow->addWriter($writer);

        $converterStep = new ConverterStep();
        $converterStep->add(function($item) { return $item; });

        $workflow->addStep($converterStep)->process();
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

        $workflow = new StepAggregator($reader);
        $workflow->addWriter($writer);

        $workflow->process();
    }

    public function testFilterPriority()
    {
        $offsetFilter = $this->getMockBuilder('\Ddeboer\DataImport\Filter\OffsetFilter')
            ->disableOriginalConstructor()
            ->setMethods(array('__invoke'))
            ->getMock();
        $offsetFilter->expects($this->never())->method('filter');

        $validatorFilter = $this->getMockBuilder('\Ddeboer\DataImport\Filter\ValidatorFilter')
            ->disableOriginalConstructor()
            ->setMethods(array('__invoke'))
            ->getMock();
        $validatorFilter->expects($this->exactly(3))
            ->method('__invoke')
            ->will($this->returnValue(false));

        $filterStep = (new FilterStep())
            ->add($offsetFilter)
            ->add($validatorFilter);

        $this->getWorkflow()
            ->addStep($filterStep)
            ->process();
    }

    public function testFilterPriorityOverride()
    {
        $offsetFilter = $this->getMockBuilder('\Ddeboer\DataImport\Filter\OffsetFilter')
            ->disableOriginalConstructor()
            ->setMethods(array('__invoke'))
            ->getMock();
        $offsetFilter->expects($this->exactly(3))
            ->method('__invoke')
            ->will($this->returnValue(false));

        $validatorFilter = $this->getMockBuilder('\Ddeboer\DataImport\Filter\ValidatorFilter')
            ->disableOriginalConstructor()
            ->setMethods(array('__invoke'))
            ->getMock();
        $validatorFilter->expects($this->never())->method('filter');

        $filterStep = (new FilterStep())
            ->add($offsetFilter, 257)
            ->add($validatorFilter);

        $this->getWorkflow()
            ->addStep($filterStep)
            ->process();
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


        $workflow = new StepAggregator($reader);
        $workflow->setLogger($logger);
        $workflow->setSkipItemOnFailure(true);
        $workflow->addWriter($writer);
        $workflow->process();
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
        $this->assertEmpty($result->getExceptions());
        $this->assertSame(null, $result->getName());
    }

    public function testMultipleMappingsForAnItemAfterAnotherItemConverterwasAdded()
    {
        $originalData = array(array('foo' => 'bar', 'baz' => 'value'));

        $outputTestData = array();

        $writer = new ArrayWriter($outputTestData);
        $reader = new ArrayReader($originalData);

        $workflow = new StepAggregator($reader);

        $converterStep = new ConverterStep();

        // add a dummy item converter
        $converterStep->add(function($item) { return $item; });

        $mappingStep = (new MappingStep())
            ->map('[foo]', '[bar]')
            ->map('[baz]', '[bazzoo]');

        // add multiple mappings
        $workflow
            ->addStep($converterStep)
            ->addStep($mappingStep)
            ->addWriter($writer)
            ->process()
        ;

        $this->assertArrayHasKey('bar', $outputTestData[0]);
        $this->assertArrayHasKey('bazzoo', $outputTestData[0]);
    }

    public function _testWorkflowResultWithExceptionThrowFromWriter()
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
        $this->assertSame(array($e), iterator_to_array($result->getExceptions()));
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

        return new StepAggregator($reader);
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
