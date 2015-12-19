<?php

/*
 * This file is part of the Sonata package.
 *
 * (c) Thomas Rabaix <thomas.rabaix@sonata-project.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sonata\CoreBundle\Test\Validator\Constraints;

use Sonata\CoreBundle\Validator\Constraints\InlineConstraint;

/**
 * Test for InlineConstraint.
 *
 * @author Andrej Hudec <pulzarraider@gmail.com>
 */
class InlineConstraintTest extends \PHPUnit_Framework_TestCase
{
    public function testValidatedBy()
    {
        $constraint = new InlineConstraint(array('service' => 'foo', 'method' => 'bar'));
        $this->assertEquals('sonata.core.validator.inline', $constraint->validatedBy());
    }

    public function testIsClosure()
    {
        $constraint = new InlineConstraint(array('service' => 'foo', 'method' => 'bar'));
        $this->assertFalse($constraint->isClosure());

        $constraint = new InlineConstraint(array('service' => 'foo', 'method' => function () {}, 'serializingWarning' => true));
        $this->assertTrue($constraint->isClosure());
    }

    public function testGetClosure()
    {
        $closure = function () {return 'FOO';};

        $constraint = new InlineConstraint(array('service' => 'foo', 'method' => $closure, 'serializingWarning' => true));
        $this->assertEquals($closure, $constraint->getClosure());
    }

    public function testGetTargets()
    {
        $constraint = new InlineConstraint(array('service' => 'foo', 'method' => 'bar'));
        $this->assertEquals(InlineConstraint::CLASS_CONSTRAINT, $constraint->getTargets());
    }

    public function testGetRequiredOptions()
    {
        $constraint = new InlineConstraint(array('service' => 'foo', 'method' => 'bar'));
        $this->assertEquals(array('service', 'method'), $constraint->getRequiredOptions());
    }

    public function testGetMethod()
    {
        $constraint = new InlineConstraint(array('service' => 'foo', 'method' => 'bar'));
        $this->assertEquals('bar', $constraint->getMethod());
    }

    public function testGetService()
    {
        $constraint = new InlineConstraint(array('service' => 'foo', 'method' => 'bar'));
        $this->assertEquals('foo', $constraint->getService());
    }

    public function testClosureSerialization()
    {
        $constraint = new InlineConstraint(array('service' => 'foo', 'method' => function () {}, 'serializingWarning' => true));

        $expected = 'O:56:"Sonata\CoreBundle\Validator\Constraints\InlineConstraint":0:{}';

        $this->assertEquals($expected, serialize($constraint));

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

        $this->assertEquals($constraint->getService(), 'foo');
        $this->assertEquals($constraint->getMethod(), 'bar');
        $this->assertNull($constraint->getSerializingWarning());
    }
}
