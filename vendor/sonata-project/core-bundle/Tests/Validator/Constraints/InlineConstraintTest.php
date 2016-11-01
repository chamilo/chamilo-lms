<?php

/*
 * This file is part of the Sonata Project package.
 *
 * (c) Thomas Rabaix <thomas.rabaix@sonata-project.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sonata\CoreBundle\Test\Validator\Constraints;

use Sonata\CoreBundle\Validator\Constraints\InlineConstraint;

/**
 * @author Andrej Hudec <pulzarraider@gmail.com>
 */
class InlineConstraintTest extends \PHPUnit_Framework_TestCase
{
    public function testValidatedBy()
    {
        $constraint = new InlineConstraint(array('service' => 'foo', 'method' => 'bar'));
        $this->assertSame('sonata.core.validator.inline', $constraint->validatedBy());
    }

    public function testIsClosure()
    {
        $constraint = new InlineConstraint(array('service' => 'foo', 'method' => 'bar'));
        $this->assertFalse($constraint->isClosure());

        $constraint = new InlineConstraint(array('service' => 'foo', 'method' => function () {
        }, 'serializingWarning' => true));
        $this->assertTrue($constraint->isClosure());
    }

    public function testGetClosure()
    {
        $closure = function () {
            return 'FOO';
        };

        $constraint = new InlineConstraint(array('service' => 'foo', 'method' => $closure, 'serializingWarning' => true));
        $this->assertSame($closure, $constraint->getClosure());
    }

    public function testGetTargets()
    {
        $constraint = new InlineConstraint(array('service' => 'foo', 'method' => 'bar'));
        $this->assertSame(InlineConstraint::CLASS_CONSTRAINT, $constraint->getTargets());
    }

    public function testGetRequiredOptions()
    {
        $constraint = new InlineConstraint(array('service' => 'foo', 'method' => 'bar'));
        $this->assertSame(array('service', 'method'), $constraint->getRequiredOptions());
    }

    public function testGetMethod()
    {
        $constraint = new InlineConstraint(array('service' => 'foo', 'method' => 'bar'));
        $this->assertSame('bar', $constraint->getMethod());
    }

    public function testGetService()
    {
        $constraint = new InlineConstraint(array('service' => 'foo', 'method' => 'bar'));
        $this->assertSame('foo', $constraint->getService());
    }

    public function testClosureSerialization()
    {
        $constraint = new InlineConstraint(array('service' => 'foo', 'method' => function () {
        }, 'serializingWarning' => true));

        $expected = 'O:56:"Sonata\CoreBundle\Validator\Constraints\InlineConstraint":0:{}';

        $this->assertSame($expected, serialize($constraint));

        $constraint = unserialize($expected);

        $this->assertInstanceOf('Closure', $constraint->getMethod());
        $this->assertEmpty($constraint->getService());
        $this->assertTrue($constraint->getSerializingWarning());
    }

    public function testStandardSerialization()
    {
        $constraint = new InlineConstraint(array('service' => 'foo', 'method' => 'bar'));

        $data = serialize($constraint);

        $constraint = unserialize($data);

        $this->assertSame($constraint->getService(), 'foo');
        $this->assertSame($constraint->getMethod(), 'bar');
        $this->assertNull($constraint->getSerializingWarning());
    }

    public function testSerializingWarningIsFalseWithServiceIsNotString()
    {
        $this->setExpectedException(
            'RuntimeException',
            'You are using a closure with the `InlineConstraint`, this constraint'.
            ' cannot be serialized. You need to re-attach the `InlineConstraint` on each request.'.
            ' Once done, you can set the `serializingWarning` option to `true` to avoid this message.');

        new InlineConstraint(array('service' => 1, 'method' => 'foo', 'serializingWarning' => false));
    }

    public function testSerializingWarningIsFalseWithMethodIsNotString()
    {
        $this->setExpectedException(
            'RuntimeException',
            'You are using a closure with the `InlineConstraint`, this constraint'.
            ' cannot be serialized. You need to re-attach the `InlineConstraint` on each request.'.
            ' Once done, you can set the `serializingWarning` option to `true` to avoid this message.');

        new InlineConstraint(array('service' => 'foo', 'method' => 1, 'serializingWarning' => false));
    }
}
