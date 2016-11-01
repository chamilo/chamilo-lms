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

use Sonata\CoreBundle\Tests\Fixtures\Bundle\Validator\FooValidatorService;
use Sonata\CoreBundle\Validator\ErrorElement;
use Sonata\CoreBundle\Validator\InlineValidator;
use Symfony\Component\Validator\Exception\ValidatorException;

/**
 * @author Ahmet Akbana <ahmetakbana@gmail.com>
 */
final class InlineValidatorTest extends \PHPUnit_Framework_TestCase
{
    private $container;
    private $constraintValidatorFactory;
    private $context;

    public function setUp()
    {
        $this->container = $this->getMock('Symfony\Component\DependencyInjection\ContainerInterface');
        $this->constraintValidatorFactory = $this->getMock(
            'Symfony\Component\Validator\ConstraintValidatorFactoryInterface'
        );
        $this->context = $this->getMock(
            interface_exists('Symfony\Component\Validator\Context\ExecutionContextInterface') ?
                'Symfony\Component\Validator\Context\ExecutionContextInterface' :
                'Symfony\Component\Validator\ExecutionContextInterface'
        );
    }

    public function testGetErrorElement()
    {
        $inlineValidator = new InlineValidator($this->container, $this->constraintValidatorFactory);

        $inlineValidator->initialize($this->context);

        $reflectorObject = new \ReflectionObject($inlineValidator);
        $reflectedMethod = $reflectorObject->getMethod('getErrorElement');
        $reflectedMethod->setAccessible(true);

        $errorElement = $reflectedMethod->invokeArgs($inlineValidator, array('foo'));

        $this->assertInstanceOf('Sonata\CoreBundle\Validator\ErrorElement', $errorElement);
        $this->assertSame('foo', $errorElement->getSubject());
    }

    public function testValidateWithConstraintIsClosure()
    {
        $this->setExpectedException('Symfony\Component\Validator\Exception\ValidatorException', 'foo is equal to foo');

        $constraint = $this->getMock('Symfony\Component\Validator\Constraint', array('isClosure', 'getClosure'));

        $constraint->expects($this->once())
            ->method('isClosure')
            ->willReturn(true);

        $constraint->expects($this->once())
            ->method('getClosure')
            ->willReturn(function (ErrorElement $errorElement, $value) {
                throw new ValidatorException($errorElement->getSubject().' is equal to '.$value);
            });

        $inlineValidator = new InlineValidator($this->container, $this->constraintValidatorFactory);

        $inlineValidator->initialize($this->context);

        $inlineValidator->validate('foo', $constraint);
    }

    public function testValidateWithConstraintGetServiceIsString()
    {
        $constraint = $this->getMock('Symfony\Component\Validator\Constraint', array(
            'isClosure',
            'getService',
            'getMethod',
        ));

        $constraint->expects($this->once())
            ->method('isClosure')
            ->willReturn(false);

        $constraint->expects($this->any())
            ->method('getService')
            ->willReturn('string');

        $constraint->expects($this->once())
            ->method('getMethod')
            ->willReturn('fooValidatorMethod');

        $this->container->expects($this->once())
            ->method('get')
            ->with('string')
            ->willReturn(new FooValidatorService());

        $inlineValidator = new InlineValidator($this->container, $this->constraintValidatorFactory);

        $inlineValidator->initialize($this->context);

        $this->setExpectedException('Symfony\Component\Validator\Exception\ValidatorException', 'foo is equal to foo');

        $inlineValidator->validate('foo', $constraint);
    }

    public function testValidateWithConstraintGetServiceIsNotString()
    {
        $constraint = $this->getMock('Symfony\Component\Validator\Constraint', array(
            'isClosure',
            'getService',
            'getMethod',
        ));

        $constraint->expects($this->once())
            ->method('isClosure')
            ->willReturn(false);

        $constraint->expects($this->any())
            ->method('getService')
            ->willReturn(new FooValidatorService());

        $constraint->expects($this->once())
            ->method('getMethod')
            ->willReturn('fooValidatorMethod');

        $inlineValidator = new InlineValidator($this->container, $this->constraintValidatorFactory);

        $inlineValidator->initialize($this->context);

        $this->setExpectedException('Symfony\Component\Validator\Exception\ValidatorException', 'foo is equal to foo');

        $inlineValidator->validate('foo', $constraint);
    }
}
