<?php

use Fhaculty\Graph\Exception\OutOfBoundsException;
use Fhaculty\Graph\Graph;
use Fhaculty\Graph\Vertex;
use Fhaculty\Graph\Edge\Base as Edge;
use Fhaculty\Graph\Set\Edges;

class EdgesTest extends TestCase
{
    /**
     *
     * @param array $edges
     * @return Edges;
     */
    protected function createEdges(array $edges)
    {
        return Edges::factory($edges);
    }

    public function testFactory()
    {
        // 1 -> 1
        $graph = new Graph();
        $v1 = $graph->createVertex(1);
        $e1 = $v1->createEdgeTo($v1);

        $edgesFromArray = $this->createEdges(array($e1));
        $this->assertInstanceOf('Fhaculty\Graph\Set\Edges', $edgesFromArray);
        $this->assertSame($e1, $edgesFromArray->getEdgeFirst());

        $edgesFromEdges = Edges::factory($edgesFromArray);
        $this->assertSame($edgesFromArray, $edgesFromEdges);
    }

    public function testEmpty()
    {
        $edges = $this->createEdges(array());

        $this->assertEquals(0, $edges->count());
        $this->assertEquals(0, count($edges));
        $this->assertEquals(array(), $edges->getVector());
        $this->assertTrue($edges->isEmpty());
        $this->assertTrue($edges->getEdges()->isEmpty());
        $this->assertTrue($edges->getEdgesOrder(Edges::ORDER_WEIGHT)->isEmpty());
        $this->assertTrue($edges->getEdgesDistinct()->isEmpty());
        $this->assertTrue($edges->getEdgesMatch(function() { })->isEmpty());

        return $edges;
    }

    /**
     *
     * @param Edges $edges
     * @depends testEmpty
     * @expectedException UnderflowException
     */
    public function testEmptyDoesNotHaveFirst(Edges $edges)
    {
        $edges->getEdgeFirst();
    }

    /**
     *
     * @param Edges $edges
     * @depends testEmpty
     * @expectedException UnderflowException
     */
    public function testEmptyDoesNotHaveLast(Edges $edges)
    {
        $edges->getEdgeLast();
    }

    /**
     *
     * @param Edges $edges
     * @depends testEmpty
     * @expectedException UnderflowException
     */
    public function testEmptyDoesNotHaveOrdered(Edges $edges)
    {
        $edges->getEdgeOrder(Edges::ORDER_WEIGHT);
    }

    public function testTwo()
    {
        // 1 -- 2 -- 3
        $graph = new Graph();
        $v1 = $graph->createVertex(1);
        $v2 = $graph->createVertex(2);
        $v3 = $graph->createVertex(3);
        $e1 = $v1->createEdge($v2);
        $e2 = $v2->createEdge($v3);

        $edges = $this->createEdges(array($e1, $e2));

        $this->assertEquals(2, count($edges));

        $this->assertSame($e1, $edges->getEdgeFirst());
        $this->assertSame($e1, $edges->getEdgeIndex(0));

        $this->assertSame($e2, $edges->getEdgeLast());
        $this->assertSame($e2, $edges->getEdgeIndex(1));

        $this->assertEquals(0, $edges->getIndexEdge($e1));

        return $edges;
    }

    /**
     *
     * @param Edges $edges
     * @depends testTwo
     * @expectedException OutOfBoundsException
     */
    public function testTwoDoesNotContainIndex3(Edges $edges)
    {
        $edges->getEdgeIndex(3);
    }

    /**
     *
     * @param Edges $edges
     * @depends testTwo
     * @expectedException OutOfBoundsException
     */
    public function testTwoDoesNotContainEdge3(Edges $edges)
    {
        $graph = new Graph();
        $v3 = $graph->createVertex(3);
        $e3 = $v3->createEdge($v3);

        $edges->getIndexEdge($e3);
    }

    /**
     *
     * @param Edges $edges
     * @depends testTwo
     */
    public function testTwoAsMap(Edges $edges)
    {
        $distinct = $edges->getEdgesDistinct();

        $this->assertInstanceOf('Fhaculty\Graph\Set\Edges', $distinct);
        $this->assertEquals(2, count($distinct));
    }

    /**
     *
     * @param Edges $edges
     * @depends testTwo
     */
    public function testTwoRandom(Edges $edges)
    {
        $edgeRandom = $edges->getEdgeOrder(Edges::ORDER_RANDOM);

        $this->assertInstanceOf('Fhaculty\Graph\Edge\Base', $edgeRandom);
        $edges->getEdgeIndex($edges->getIndexEdge($edgeRandom));

        $edgesRandom = $edges->getEdgesOrder(Edges::ORDER_RANDOM);

        $this->assertInstanceOf('Fhaculty\Graph\Set\Edges', $edgesRandom);
        $this->assertEquals(2, count($edgesRandom));
    }

    /**
     *
     * @param Edges $edges
     * @depends testTwo
     */
    public function testTwoIterator(Edges $edges)
    {
        $this->assertInstanceOf('Iterator', $edges->getIterator());

        $values = array_values(iterator_to_array($edges));
        $this->assertEquals($edges->getVector(), $values);
    }

    /**
     *
     * @param Edges $edges
     * @depends testTwo
     */
    public function testTwoMatch(Edges $edges)
    {
        $edgesMatch = $edges->getEdgesMatch(array($this, 'returnTrue'));
        $this->assertEquals($edges->getVector(), $edgesMatch->getVector());

        $edgeMatch = $edges->getEdgeMatch(array($this, 'returnTrue'));
        $this->assertEquals($edges->getEdgeFirst(), $edgeMatch);
    }

    /**
     *
     * @param Edges $edges
     * @depends testTwo
     */
    public function testTwoMatchEmpty(Edges $edges)
    {
        $edgesMatch = $edges->getEdgesMatch(array($this, 'returnFalse'));
        $this->assertCount(0, $edgesMatch);
    }

    /**
     *
     * @param Edges $edges
     * @depends testTwo
     * @expectedException UnderflowException
     */
    public function testTwoMatchFail(Edges $edges)
    {
        $edges->getEdgeMatch(array($this, 'returnFalse'));
    }

    public function returnTrue(Edge $edge)
    {
        return true;
    }

    public function returnFalse(Edge $edge)
    {
        return false;
    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function testGetEdgeOrderInvalidSortBy()
    {
        // 1 -> 1
        $graph = new Graph();
        $v1 = $graph->createVertex(1);
        $v1->createEdgeTo($v1);

        $edges = $graph->getEdges();

        $edges->getEdgeOrder('not a valid callback');
    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function testGetEdgesOrderInvalidSortBy()
    {
        $edges = $this->createEdges(array());

        $edges->getEdgesOrder('not a valid callback');
    }

    public function testOrderByGroup()
    {
        $graph = new Graph();
        $v1 = $graph->createVertex(1);
        $v2 = $graph->createVertex(2);
        $v1->createEdge($v2)->setWeight(1);
        $v1->createEdge($v2)->setWeight(100);
        $v1->createEdge($v2)->setWeight(5);
        $v1->createEdge($v2)->setWeight(100);
        $v1->createEdge($v2)->setWeight(100);
        $v1->createEdge($v2)->setWeight(2);
        $biggest = $v1->createEdge($v2)->setWeight(200);

        $edges = $graph->getEdges();
        $edgesOrdered = $edges->getEdgesOrder(Edges::ORDER_WEIGHT);

        $this->assertInstanceOf('Fhaculty\Graph\Set\Edges', $edgesOrdered);
        $this->assertEquals(1, $edgesOrdered->getEdgeFirst()->getWeight());
        $this->assertEquals(200, $edgesOrdered->getEdgeLast()->getWeight());

        $this->assertSame($biggest, $edgesOrdered->getEdgeLast());
        $this->assertSame($biggest, $edges->getEdgeOrder(Edges::ORDER_WEIGHT, true));

        $sumweights = function(Edge $edge) {
            return $edge->getWeight();
        };
        $this->assertSame(508, $edges->getSumCallback($sumweights));
        $this->assertSame(508, $edgesOrdered->getSumCallback($sumweights));
    }

    public function testIntersection()
    {
        $graph = new Graph();
        $v1 = $graph->createVertex(1);
        $v2 = $graph->createVertex(2);
        $e1 = $v1->createEdge($v2);
        $e2 = $v1->createEdge($v2);
        $e3 = $v1->createEdge($v2);

        $edges1 = $this->createEdges(array($e1, $e2));
        $edges2 = $this->createEdges(array($e2, $e3));

        $edges3 = $edges1->getEdgesIntersection($edges2);
        $this->assertCount(1, $edges3);
        $this->assertEquals($e2, $edges3->getEdgeFirst());
    }

    public function testIntersectionDuplicates()
    {
        $graph = new Graph();
        $v1 = $graph->createVertex(1);
        $v2 = $graph->createVertex(2);
        $e1 = $v1->createEdge($v2);

        $edges1 = $this->createEdges(array($e1, $e1, $e1));
        $edges2 = $this->createEdges(array($e1, $e1));

        $edges3 = $edges1->getEdgesIntersection($edges2);
        $this->assertCount(2, $edges3);
    }

    public function testIntersectionEmpty()
    {
        $edges1 = new Edges();
        $edges2 = new Edges();

        $edges3 = $edges1->getEdgesIntersection($edges2);
        $this->assertCount(0, $edges3);
    }
}
