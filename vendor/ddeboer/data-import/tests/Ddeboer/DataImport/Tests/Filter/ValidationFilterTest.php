<?php

namespace Ddeboer\DataImport\Tests\Filter;

use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\Constraints;
use Ddeboer\DataImport\Filter\ValidatorFilter;
use Ddeboer\DataImport\Exception\ValidationException;

class ValidationFilterTest extends \PHPUnit_Framework_TestCase
{
    protected function setUp()
    {
        $this->validator = $this->getMock('Symfony\\Component\\Validator\\ValidatorInterface');
        $this->filter = new ValidatorFilter($this->validator);
    }

    public function testFilterWithValid()
    {
        $item = array('foo' => 'bar');

        $list = new ConstraintViolationList();

        $this->validator->expects($this->once())
            ->method('validateValue')
            ->will($this->returnValue($list));

        $this->assertTrue($this->filter->filter($item));
    }

    public function testFilterWithInvalidItem()
    {
        $item = array('foo' => 'bar');

        $violation = $this->getMock('Symfony\\Component\\Validator\\ConstraintViolationInterface');
        $list = new ConstraintViolationList(array($violation));

        $this->validator->expects($this->once())
            ->method('validateValue')
            ->will($this->returnValue($list));

        $this->assertFalse($this->filter->filter($item));

        $this->assertEquals(array(1 => $list), $this->filter->getViolations());
    }

    public function testStopOnFirstError()
    {
        $this->filter->throwExceptions();

        $item = array('foo' => 'bar');

        $violation = $this->getMock('Symfony\\Component\\Validator\\ConstraintViolationInterface');
        $list = new ConstraintViolationList(array($violation));

        $this->validator->expects($this->once())
            ->method('validateValue')
            ->will($this->returnValue($list));

        try {
            $this->filter->filter($item);
            $this->fail('ValidationException should be thrown');
        } catch (ValidationException $e) {
            $this->assertSame(1, $e->getLineNumber());
            $this->assertEquals($list, $e->getViolations());
        }
    }

    public function testFilterNonStrict()
    {
        $this->filter->setStrict(false);

        $item = array('foo' => true, 'bar' => true);

        $this->filter->add('foo', new Constraints\True());
        $this->assertTrue($this->filter->filter($item));
    }

    public function testFilterLineNumbers()
    {
        $this->filter->throwExceptions();

        $item = array('foo' => 'bar');

        $violation = $this->getMock('Symfony\\Component\\Validator\\ConstraintViolationInterface');
        $list = new ConstraintViolationList(array($violation));

        $this->validator->expects($this->exactly(2))
            ->method('validateValue')
            ->will($this->returnValue($list));

        try {
            $this->assertTrue($this->filter->filter($item));
            $this->fail('ValidationException should be thrown (1)');
        } catch (ValidationException $e) {
            $this->assertSame(1, $e->getLineNumber());
            $this->assertEquals($list, $e->getViolations());
        }

        try {
            $this->assertTrue($this->filter->filter($item));
            $this->fail('ValidationException should be thrown (2)');
        } catch (ValidationException $e) {
            $this->assertSame(2, $e->getLineNumber());
            $this->assertEquals($list, $e->getViolations());
        }
    }
}
