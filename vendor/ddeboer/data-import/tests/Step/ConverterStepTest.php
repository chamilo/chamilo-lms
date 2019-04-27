<?php

namespace Ddeboer\DataImport\Tests\Step;

use Ddeboer\DataImport\Step\ConverterStep;

class ConverterStepTest extends \PHPUnit_Framework_TestCase
{
    private $step;

    protected function setUp()
    {
        $this->step = new ConverterStep();
    }

    public function testProcess()
    {
        $this->step->add(function() { return ['bar']; });

        $data = ['foo'];

        $this->step->process($data);

        $this->assertEquals(['bar'], $data);
    }
}
