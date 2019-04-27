<?php

use Fhaculty\Graph\Exception\OutOfBoundsException;
use Fhaculty\Graph\Graph;
use Fhaculty\Graph\Vertex;
use Fhaculty\Graph\Set\Vertices;

abstract class BaseVerticesTest extends TestCase
{
    /**
     *
     * @param array $vertices
     * @return Vertices;
     */
    abstract protected function createVertices(array $vertices);

    public function testFactory()
    {
        $graph = new Graph();
        $vertex = $graph->createVertex();

        $verticesFromArray = $this->createVertices(array($vertex));
        $this->assertInstanceOf('Fhaculty\Graph\Set\Vertices', $verticesFromArray);
        $this->assertSame($vertex, $verticesFromArray->getVertexFirst());

        $verticesFromVertices = Vertices::factory($verticesFromArray);
        $this->assertSame($verticesFromArray, $verticesFromVertices);
    }

    public function testEmpty()
    {
        $vertices = $this->createVertices(array());

        $this->assertEquals(0, $vertices->count());
        $this->assertEquals(0, count($vertices));
        $this->assertEquals(array(), $vertices->getIds());
        $this->assertEquals(array(), $vertices->getMap());
        $this->assertEquals(array(), $vertices->getVector());
        $this->assertTrue($vertices->isEmpty());
        $this->assertTrue($vertices->getVertices()->isEmpty());
        $this->assertTrue($vertices->getVerticesOrder(Vertices::ORDER_ID)->isEmpty());
        $this->assertTrue($vertices->getVerticesDistinct()->isEmpty());
        $this->assertTrue($vertices->getVerticesMatch(function() { })->isEmpty());
        $this->assertFalse($vertices->hasDuplicates());

        return $vertices;
    }

    /**
     *
     * @param Vertices $vertices
     * @depends testEmpty
     * @expectedException UnderflowException
     */
    public function testEmptyDoesNotHaveFirst(Vertices $vertices)
    {
        $vertices->getVertexFirst();
    }

    /**
     *
     * @param Vertices $vertices
     * @depends testEmpty
     * @expectedException UnderflowException
     */
    public function testEmptyDoesNotHaveLast(Vertices $vertices)
    {
        $vertices->getVertexLast();
    }

    /**
     *
     * @param Vertices $vertices
     * @depends testEmpty
     * @expectedException UnderflowException
     */
    public function testEmptyDoesNotHaveOrdered(Vertices $vertices)
    {
        $vertices->getVertexOrder(Vertices::ORDER_ID);
    }

    public function testTwo()
    {
        $graph = new Graph();
        $v1 = $graph->createVertex(1);
        $v2 = $graph->createVertex(2);

        $vertices = $this->createVertices(array(1 => $v1, 2 => $v2));

        $this->assertTrue($vertices->hasVertexId(1));
        $this->assertTrue($vertices->hasVertexId(2));
        $this->assertFalse($vertices->hasVertexId(3));
        $this->assertEquals(2, count($vertices));

        $this->assertSame($v1, $vertices->getVertexFirst());
        $this->assertSame($v1, $vertices->getVertexId(1));

        $this->assertSame($v2, $vertices->getVertexLast());
        $this->assertSame($v2, $vertices->getVertexId(2));

        $this->assertEquals(1, $vertices->getIndexVertex($v1));
        $this->assertEquals(array(1, 2), $vertices->getIds());

        return $vertices;
    }

    /**
     *
     * @param Vertices $vertices
     * @depends testTwo
     * @expectedException OutOfBoundsException
     */
    public function testTwoDoesNotContainId3(Vertices $vertices)
    {
        $vertices->getVertexId(3);
    }

    /**
     *
     * @param Vertices $vertices
     * @depends testTwo
     * @expectedException OutOfBoundsException
     */
    public function testTwoDoesNotContainVertex3(Vertices $vertices)
    {
        $graph = new Graph();
        $v3 = $graph->createVertex(3);

        $vertices->getIndexVertex($v3);
    }

    /**
     *
     * @param Vertices $vertices
     * @depends testTwo
     */
    public function testTwoAsMap(Vertices $vertices)
    {
        $distinct = $vertices->getVerticesDistinct();

        $this->assertInstanceOf('Fhaculty\Graph\Set\VerticesMap', $distinct);
        $this->assertEquals(2, count($distinct));
        $this->assertEquals(array(1, 2), $distinct->getIds());
    }

    /**
     *
     * @param Vertices $vertices
     * @depends testTwo
     */
    public function testTwoRandom(Vertices $vertices)
    {
        $vertexRandom = $vertices->getVertexOrder(Vertices::ORDER_RANDOM);

        $this->assertInstanceOf('Fhaculty\Graph\Vertex', $vertexRandom);
        $this->assertTrue($vertices->hasVertexId($vertexRandom->getId()));

        $verticesRandom = $vertices->getVerticesOrder(Vertices::ORDER_RANDOM);

        $this->assertInstanceOf('Fhaculty\Graph\Set\Vertices', $verticesRandom);
        $this->assertEquals(2, count($verticesRandom));
    }

    /**
     *
     * @param Vertices $vertices
     * @depends testTwo
     */
    public function testTwoIterator(Vertices $vertices)
    {
        $this->assertInstanceOf('Iterator', $vertices->getIterator());

        $values = array_values(iterator_to_array($vertices));
        $this->assertEquals($vertices->getVector(), $values);
    }

    /**
     *
     * @param Vertices $vertices
     * @depends testTwo
     */
    public function testTwoMatch(Vertices $vertices)
    {
        $verticesMatch = $vertices->getVerticesMatch(array($this, 'returnTrue'));
        $this->assertEquals($vertices->getVector(), $verticesMatch->getVector());

        $vertexMatch = $vertices->getVertexMatch(array($this, 'returnTrue'));
        $this->assertEquals($vertices->getVertexFirst(), $vertexMatch);
    }

    public function returnTrue(Vertex $vertex)
    {
        return true;
    }

    public function testOrderByGroup()
    {
        $graph = new Graph();
        $graph->createVertex()->setGroup(1);
        $graph->createVertex()->setGroup(100);
        $graph->createVertex()->setGroup(5);
        $graph->createVertex()->setGroup(100);
        $graph->createVertex()->setGroup(100);
        $graph->createVertex()->setGroup(2);
        $biggest = $graph->createVertex()->setGroup(200);

        $vertices = $graph->getVertices();
        $verticesOrdered = $vertices->getVerticesOrder(Vertices::ORDER_GROUP);

        $this->assertInstanceOf('Fhaculty\Graph\Set\Vertices', $verticesOrdered);
        $this->assertEquals(1, $verticesOrdered->getVertexFirst()->getGroup());
        $this->assertEquals(200, $verticesOrdered->getVertexLast()->getGroup());

        $this->assertSame($biggest, $verticesOrdered->getVertexLast());
        $this->assertSame($biggest, $vertices->getVertexOrder(Vertices::ORDER_GROUP, true));

        $sumgroups = function(Vertex $vertex) {
            return $vertex->getGroup();
        };
        $this->assertSame(508, $vertices->getSumCallback($sumgroups));
        $this->assertSame(508, $verticesOrdered->getSumCallback($sumgroups));
    }

    /**
     *
     * @param Vertices $vertices
     * @depends testEmpty
     */
    public function testEmptyIntersectionSelf(Vertices $vertices)
    {
        $verticesIntersection = $vertices->getVerticesIntersection($vertices);
        $this->assertCount(0, $verticesIntersection);
    }

    /**
     *
     * @param Vertices $verticesEmpty
     * @param Vertices $verticesTwo
     * @depends testEmpty
     * @depends testTwo
     */
    public function testEmptyIntersectionTwo(Vertices $verticesEmpty, Vertices $verticesTwo)
    {
        $verticesIntersection = $verticesEmpty->getVerticesIntersection($verticesTwo);
        $this->assertCount(0, $verticesIntersection);
    }

    /**
     *
     * @param Vertices $vertices
     * @depends testTwo
     */
    public function testTwoIntersectionSelf(Vertices $vertices)
    {
        $verticesIntersection = $vertices->getVerticesIntersection($vertices);
        $this->assertCount(2, $verticesIntersection);
        $this->assertEquals($vertices->getMap(), $verticesIntersection->getMap());
    }

    /**
     *
     * @param Vertices $verticesTwo
     * @param Vertices $verticesEmpty
     * @depends testTwo
     * @depends testEmpty
     */
    public function testTwoIntersectionEmpty(Vertices $verticesTwo, Vertices $verticesEmpty)
    {
        $verticesIntersection = $verticesTwo->getVerticesIntersection($verticesEmpty);
        $this->assertCount(0, $verticesIntersection);
    }
}
