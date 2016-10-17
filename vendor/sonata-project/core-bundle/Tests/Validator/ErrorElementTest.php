<?php

/*
 * This file is part of the Sonata Project package.
 *
 * (c) Thomas Rabaix <thomas.rabaix@sonata-project.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sonata\CoreBundle\Tests\Validator;

use Sonata\CoreBundle\Tests\Fixtures\Bundle\Entity\Foo;
use Sonata\CoreBundle\Validator\ErrorElement;
use Symfony\Component\Validator\Constraints\NotNull;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Validator\ExecutionContextInterface as LegacyExecutionContextInterface;

/**
 * @author Andrej Hudec <pulzarraider@gmail.com>
 */
class ErrorElementTest extends \PHPUnit_Framework_TestCase
{
    private $errorElement;
    private $context;
    private $contextualValidator;
    private $subject;

    protected function setUp()
    {
        $constraintValidatorFactory = $this->getMock('Symfony\Component\Validator\ConstraintValidatorFactoryInterface');

        $this->context = $this->getMock(interface_exists('Symfony\Component\Validator\Context\ExecutionContextInterface') ? 'Symfony\Component\Validator\Context\ExecutionContextInterface' : 'Symfony\Component\Validator\ExecutionContextInterface');
        $this->context->expects($this->once())
                ->method('getPropertyPath')
                ->will($this->returnValue('bar'));

        if ($this->context instanceof ExecutionContextInterface) {
            $builder = $this->getMock('Symfony\Component\Validator\Violation\ConstraintViolationBuilderInterface');
            $builder->expects($this->any())
                ->method($this->anything())
                ->will($this->returnSelf());

            $this->context->expects($this->any())
                ->method('buildViolation')
                ->willReturn($builder);

            $validator = $this->getMock('Symfony\Component\Validator\Validator\ValidatorInterface');

            $this->contextualValidator = $this->getMock('Symfony\Component\Validator\Validator\ContextualValidatorInterface');
            $this->contextualValidator->expects($this->any())
                ->method($this->anything())
                ->will($this->returnSelf());
            $validator->expects($this->any())
                ->method('inContext')
                ->willReturn($this->contextualValidator);

            $this->context->expects($this->any())
                ->method('getValidator')
                ->willReturn($validator);
        }

        $this->subject = new Foo();

        $this->errorElement = new ErrorElement($this->subject, $constraintValidatorFactory, $this->context, 'foo_core');
    }

    public function testGetSubject()
    {
        $this->assertSame($this->subject, $this->errorElement->getSubject());
    }

    public function testGetErrorsEmpty()
    {
        $this->assertSame(array(), $this->errorElement->getErrors());
    }

    public function testGetErrors()
    {
        $this->errorElement->addViolation('Foo error message', array('bar_param' => 'bar_param_lvalue'), 'BAR');
        $this->assertSame(array(array('Foo error message', array('bar_param' => 'bar_param_lvalue'), 'BAR')), $this->errorElement->getErrors());
    }

    public function testAddViolation()
    {
        $this->errorElement->addViolation(array('Foo error message', array('bar_param' => 'bar_param_lvalue'), 'BAR'));
        $this->assertSame(array(array('Foo error message', array('bar_param' => 'bar_param_lvalue'), 'BAR')), $this->errorElement->getErrors());
    }

    public function testAddConstraint()
    {
        $constraint = new NotNull();
        if ($this->context instanceof LegacyExecutionContextInterface) {
            $this->context->expects($this->once())
                ->method('validateValue')
                ->with($this->equalTo($this->subject), $this->equalTo($constraint), $this->equalTo(''), $this->equalTo('foo_core'))
                ->will($this->returnValue(null));
        } else {
            $this->contextualValidator->expects($this->once())
                ->method('atPath')
                ->with('');
            $this->contextualValidator->expects($this->once())
                ->method('validate')
                ->with($this->subject, $constraint, array('foo_core'));
        }

        $this->errorElement->addConstraint($constraint);
    }

    public function testWith()
    {
        $constraint = new NotNull();

        if ($this->context instanceof LegacyExecutionContextInterface) {
            $this->context->expects($this->once())
                ->method('validateValue')
                ->with($this->equalTo(null), $this->equalTo($constraint), $this->equalTo('bar'), $this->equalTo('foo_core'))
                ->will($this->returnValue(null));
        } else {
            $this->contextualValidator->expects($this->once())
                ->method('atPath')
                ->with('bar');
            $this->contextualValidator->expects($this->once())
                ->method('validate')
                ->with(null, $constraint, array('foo_core'));
        }

        $this->errorElement->with('bar');
        $this->errorElement->addConstraint($constraint);
        $this->errorElement->end();
    }

    public function testCall()
    {
        $constraint = new NotNull();

        if ($this->context instanceof LegacyExecutionContextInterface) {
            $this->context->expects($this->once())
                ->method('validateValue')
                ->with($this->equalTo(null), $this->equalTo($constraint), $this->equalTo('bar'), $this->equalTo('foo_core'))
                ->will($this->returnValue(null));
        } else {
            $this->contextualValidator->expects($this->once())
                ->method('atPath')
                ->with('bar');
            $this->contextualValidator->expects($this->once())
                ->method('validate')
                ->with(null, $constraint, array('foo_core'));
        }

        $this->errorElement->with('bar');
        $this->errorElement->assertNotNull();
        $this->errorElement->end();
    }

    public function testCallException()
    {
        $this->setExpectedException('RuntimeException', 'Unable to recognize the command');

        $this->errorElement->with('bar');
        $this->errorElement->baz();
    }

    public function testGetFullPropertyPath()
    {
        $this->errorElement->with('baz');
        $this->assertSame('bar.baz', $this->errorElement->getFullPropertyPath());
        $this->errorElement->end();

        $this->assertSame('bar', $this->errorElement->getFullPropertyPath());
    }

    public function testFluidInterface()
    {
        $constraint = new NotNull();

        if ($this->context instanceof LegacyExecutionContextInterface) {
            $this->context->expects($this->any())
                ->method('validateValue')
                ->with($this->equalTo($this->subject), $this->equalTo($constraint), $this->equalTo(''), $this->equalTo('foo_core'))
                ->will($this->returnValue(null));
        } else {
            $this->contextualValidator->expects($this->any())
                ->method('atPath')
                ->with('');
            $this->contextualValidator->expects($this->any())
                ->method('validate')
                ->with($this->subject, $constraint, array('foo_core'));
        }

        $this->assertSame($this->errorElement, $this->errorElement->with('baz'));
        $this->assertSame($this->errorElement, $this->errorElement->end());
        $this->assertSame($this->errorElement, $this->errorElement->addViolation('Foo error message', array('bar_param' => 'bar_param_lvalue'), 'BAR'));
        $this->assertSame($this->errorElement, $this->errorElement->addConstraint($constraint));
        $this->assertSame($this->errorElement, $this->errorElement->assertNotNull());
    }

    public function testExceptionIsThrownWhenContextIsString()
    {
        $constraintValidatorFactory = $this->getMock('Symfony\Component\Validator\ConstraintValidatorFactoryInterface');

        $this->setExpectedException(
            'InvalidArgumentException',
            'Argument 3 passed to Sonata\CoreBundle\Validator\ErrorElement::__construct() must be an instance of '.
            'Symfony\Component\Validator\ExecutionContextInterface or '.
            'Symfony\Component\Validator\Context\ExecutionContextInterface.'
        );

        $this->errorElement = new ErrorElement($this->subject, $constraintValidatorFactory, 'foo', 'foo_core');
    }
}
