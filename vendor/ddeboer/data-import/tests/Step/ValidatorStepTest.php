<?php

namespace Ddeboer\DataImport\Tests\Step;

use Ddeboer\DataImport\Step\ValidatorStep;
use Symfony\Component\Validator\Constraints;
use Symfony\Component\Validator\ConstraintViolationList;

class ValidatorStepTest extends \PHPUnit_Framework_TestCase
{
    protected function setUp()
    {
        $this->validator = $this->getMockBuilder('Symfony\Component\Validator\Validator')
            ->disableOriginalConstructor()
            ->getMock();

        $this->filter = new ValidatorStep($this->validator);
    }

    public function testProcess()
    {
        $data = [
            'title' => null,
        ];

        $this->filter->add('title', $constraint = new Constraints\NotNull());

        $this->validator->expects($this->once())
            ->method('validateValue')
            ->will($this->returnValue(
                $list = new ConstraintViolationList([
                    $this->getMockBuilder('Symfony\Component\Validator\ConstraintViolation')
                        ->disableOriginalConstructor()
                        ->getMock()
                ])
            ));

        $this->assertFalse($this->filter->process($data));

        $this->assertEquals([1 => $list], $this->filter->getViolations());
    }

    /**
     * @expectedException Ddeboer\DataImport\Exception\ValidationException
     */
    public function testProcessWithExceptions()
    {
        $data = [
            'title' => null,
        ];

        $this->filter->add('title', $constraint = new Constraints\NotNull());
        $this->filter->throwExceptions();

        $this->validator->expects($this->once())
        ->method('validateValue')
        ->will($this->returnValue(
        $list = new ConstraintViolationList([
            $this->getMockBuilder('Symfony\Component\Validator\ConstraintViolation')
            ->disableOriginalConstructor()
            ->getMock()
            ])
        ));

        $this->assertFalse($this->filter->process($data));
    }

    public function testPriority()
    {
        $this->assertEquals(128, $this->filter->getPriority());
    }
}
