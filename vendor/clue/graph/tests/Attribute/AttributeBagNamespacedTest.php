<?php

use Fhaculty\Graph\Graph;
use Fhaculty\Graph\Attribute\AttributeBagNamespaced;
use Fhaculty\Graph\Attribute\AttributeAware;
use Fhaculty\Graph\Attribute\AttributeBagContainer;

class AtributeBagNamespacedTest extends TestCase
{
    public function testBagContainer()
    {
        $container = new AttributeBagContainer();
        $bag = new AttributeBagNamespaced($container, 'test.');
        $this->assertSame($bag, $bag->getAttributeBag());

        $container->setAttribute('a.b', 'c');
        $container->setAttribute('test.d', 'e');

        $this->assertEquals('e', $bag->getAttribute('d'));

        $this->assertNull($bag->getAttribute('unknown'));
        $this->assertEquals('default', $bag->getAttribute('unknown', 'default'));

        $bag->setAttribute('d', 'test');

        $this->assertEquals('test', $bag->getAttribute('d'));
        $this->assertEquals('test', $container->getAttribute('test.d'));

        $bag->setAttributes(array('d' => 'd', 'e' => 'e'));

        $this->assertEquals(array('a.b' => 'c', 'test.d' => 'd', 'test.e' => 'e'), $container->getAttributes());
    }

    /**
     *
     * @param AttributeAware $entity
     * @dataProvider provideNamespacable
     */
    public function testReadableEntities(AttributeAware $entity)
    {
        $bag = new AttributeBagNamespaced($entity, 'test.');
        $this->assertSame($bag, $bag->getAttributeBag());

        $entity->setAttribute('a.b', 'c');
        $entity->setAttribute('test.d', 'e');

        $this->assertEquals('e', $bag->getAttribute('d'));

        $this->assertNull($bag->getAttribute('a.b'));
        $this->assertNull($bag->getAttribute('test.d'));

        $this->assertEquals(array('d' => 'e'), $bag->getAttributes());
    }

    public function provideNamespacable()
    {
        $graph = new Graph();
        $vertex = $graph->createVertex();
        $bag = $vertex->getAttributeBag();
        $subNamespace = new AttributeBagNamespaced($bag, 'prefix');

        return array(
            array($graph),
            array($vertex),
            array($bag),
            array($subNamespace),
        );
    }
}
