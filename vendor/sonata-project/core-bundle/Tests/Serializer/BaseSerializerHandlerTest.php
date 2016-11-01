<?php

/*
 * This file is part of the Sonata Project package.
 *
 * (c) Thomas Rabaix <thomas.rabaix@sonata-project.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sonata\CoreBundle\Tests\Serializer;

use JMS\Serializer\GraphNavigator;
use Sonata\CoreBundle\Tests\Fixtures\Bundle\Serializer\FooSerializer;

/**
 * @author Ahmet Akbana <ahmetakbana@gmail.com>
 */
final class BaseSerializerHandlerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @group legacy
     *
     * NEXT_MAJOR : this should call setFormats method
     */
    public function testGetSubscribingMethodsWithDefaultFormats()
    {
        $manager = $this->getMock('Sonata\CoreBundle\Model\ManagerInterface');

        $serializer = new FooSerializer($manager);

        $expectedMethods = array(
            array(
                'direction' => GraphNavigator::DIRECTION_SERIALIZATION,
                'format' => 'json',
                'type' => 'foo',
                'method' => 'serializeObjectToId',
            ),
            array(
                'direction' => GraphNavigator::DIRECTION_DESERIALIZATION,
                'format' => 'json',
                'type' => 'foo',
                'method' => 'deserializeObjectFromId',
            ),
            array(
                'direction' => GraphNavigator::DIRECTION_SERIALIZATION,
                'format' => 'xml',
                'type' => 'foo',
                'method' => 'serializeObjectToId',
            ),
            array(
                'direction' => GraphNavigator::DIRECTION_DESERIALIZATION,
                'format' => 'xml',
                'type' => 'foo',
                'method' => 'deserializeObjectFromId',
            ),
            array(
                'direction' => GraphNavigator::DIRECTION_SERIALIZATION,
                'format' => 'yml',
                'type' => 'foo',
                'method' => 'serializeObjectToId',
            ),
            array(
                'direction' => GraphNavigator::DIRECTION_DESERIALIZATION,
                'format' => 'yml',
                'type' => 'foo',
                'method' => 'deserializeObjectFromId',
            ),
        );

        $methods = $serializer::getSubscribingMethods();

        $this->assertSame($methods, $expectedMethods);
    }

    public function testSetFormats()
    {
        $manager = $this->getMock('Sonata\CoreBundle\Model\ManagerInterface');

        $serializer = new FooSerializer($manager);

        $expectedMethods = array(
            array(
                'direction' => GraphNavigator::DIRECTION_SERIALIZATION,
                'format' => 'bar',
                'type' => 'foo',
                'method' => 'serializeObjectToId',
            ),
            array(
                'direction' => GraphNavigator::DIRECTION_DESERIALIZATION,
                'format' => 'bar',
                'type' => 'foo',
                'method' => 'deserializeObjectFromId',
            ),
        );

        $serializer::setFormats(array('bar'));

        $methods = $serializer::getSubscribingMethods();

        $this->assertSame($methods, $expectedMethods);
    }

    public function testAddFormats()
    {
        $manager = $this->getMock('Sonata\CoreBundle\Model\ManagerInterface');

        $serializer = new FooSerializer($manager);

        $expectedMethods = array(
            array(
                'direction' => GraphNavigator::DIRECTION_SERIALIZATION,
                'format' => 'bar',
                'type' => 'foo',
                'method' => 'serializeObjectToId',
            ),
            array(
                'direction' => GraphNavigator::DIRECTION_DESERIALIZATION,
                'format' => 'bar',
                'type' => 'foo',
                'method' => 'deserializeObjectFromId',
            ),
            array(
                'direction' => GraphNavigator::DIRECTION_SERIALIZATION,
                'format' => 'foo',
                'type' => 'foo',
                'method' => 'serializeObjectToId',
            ),
            array(
                'direction' => GraphNavigator::DIRECTION_DESERIALIZATION,
                'format' => 'foo',
                'type' => 'foo',
                'method' => 'deserializeObjectFromId',
            ),
        );

        $serializer::setFormats(array('bar'));

        $serializer::addFormat('foo');

        $methods = $serializer::getSubscribingMethods();

        $this->assertSame($methods, $expectedMethods);
    }

    public function testSerializeObjectToIdWithDataIsInstanceOfManager()
    {
        $modelInstance = $this->getMock(
            'Sonata\CoreBundle\Tests\Fixtures\Bundle\Serializer\FooSerializer',
            array('getId'),
            array(),
            '',
            false
        );

        $modelInstance->expects($this->once())
            ->method('getId')
            ->willReturn(1);

        $manager = $this->getMock('Sonata\CoreBundle\Model\ManagerInterface');
        $manager->expects($this->once())
            ->method('getClass')
            ->willReturn(get_class($modelInstance));

        $context = $this->getMock('JMS\Serializer\Context');

        $visitor = $this->getMock('JMS\Serializer\VisitorInterface');
        $visitor->expects($this->once())
            ->method('visitInteger')
            ->with(1, array('foo'), $context)
            ->willReturn(true);

        $serializer = new FooSerializer($manager);

        $this->assertTrue($serializer->serializeObjectToId($visitor, $modelInstance, array('foo'), $context));
    }

    public function testSerializeObjectToIdWithDataIsNotInstanceOfManager()
    {
        $modelInstance = $this->getMock(
            'Sonata\CoreBundle\Tests\Fixtures\Bundle\Serializer\FooSerializer',
            array(),
            array(),
            '',
            false
        );

        $manager = $this->getMock('Sonata\CoreBundle\Model\ManagerInterface');
        $manager->expects($this->once())
            ->method('getClass')
            ->willReturn('bar');

        $context = $this->getMock('JMS\Serializer\Context');

        $visitor = $this->getMock('JMS\Serializer\VisitorInterface');
        $visitor->expects($this->never())
            ->method('visitInteger');

        $serializer = new FooSerializer($manager);

        $serializer->serializeObjectToId($visitor, $modelInstance, array('foo'), $context);
    }

    public function testDeserializeObjectFromId()
    {
        $manager = $this->getMock('Sonata\CoreBundle\Model\ManagerInterface');
        $manager->expects($this->once())
            ->method('findOneBy')
            ->with(array('id' => 'foo'))
            ->willReturn('bar');

        $visitor = $this->getMock('JMS\Serializer\VisitorInterface');

        $serializer = new FooSerializer($manager);

        $this->assertSame('bar', $serializer->deserializeObjectFromId($visitor, 'foo', array()));
    }
}
