<?php

namespace Ddeboer\DataImport\Tests\Step;

use Ddeboer\DataImport\Step\FilterStep;

class FilterStepTest extends \PHPUnit_Framework_TestCase
{
    protected function setUp()
    {
        $this->filter = new FilterStep();
    }

    public function testProcess()
    {
        $this->filter->add(function ($v) { return in_array('bar', $v); });

        $d = ['foo'];
        $this->assertFalse($this->filter->process($d));

        $d = ['bar'];
        $this->assertTrue($this->filter->process($d));
    }

    public function testClone()
    {
        $reflection = new \ReflectionObject($this->filter);
        $property = $reflection->getProperty('filters');
        $property->setAccessible(true);

        $this->filter->add(function ($v) { return in_array('bar', $v); });
        $d = ['foo'];

        $this->filter->process($d);

        $this->assertCount(1, $property->getValue($this->filter));
    }
}
