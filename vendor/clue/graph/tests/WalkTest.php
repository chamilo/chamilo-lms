<?php

use Fhaculty\Graph\Graph;
use Fhaculty\Graph\Walk;
use Fhaculty\Graph\Algorithm\Property\WalkProperty;
use Fhaculty\Graph\Vertex;
use Fhaculty\Graph\Set\Edges;

class WalkTest extends TestCase
{
    /**
     * @expectedException UnderflowException
     */
    public function testWalkCanNotBeEmpty()
    {
        Walk::factoryCycleFromVertices(array());
    }

    public function testWalkPath()
    {
        // 1 -- 2 -- 3
        $graph = new Graph();
        $v1 = $graph->createVertex(1);
        $v2 = $graph->createVertex(2);
        $v3 = $graph->createVertex(3);
        $e1 = $v1->createEdgeTo($v2);
        $e2 = $v2->createEdgeTo($v3);

        $walk = Walk::factoryFromEdges(array($e1, $e2), $v1);

        $this->assertEquals(3, count($walk->getVertices()));
        $this->assertEquals(2, count($walk->getEdges()));
        $this->assertSame($v1, $walk->getVertices()->getVertexFirst());
        $this->assertSame($v3, $walk->getVertices()->getVertexLast());
        $this->assertSame(array($v1, $e1, $v2, $e2, $v3), $walk->getAlternatingSequence());
        $this->assertTrue($walk->isValid());

        $graphClone = $walk->createGraph();
        $this->assertGraphEquals($graph, $graphClone);

        return $walk;
    }

    /**
     * @param Walk $walk
     * @depends testWalkPath
     */
    public function testWalkPathInvalidateByDestroyingVertex(Walk $walk)
    {
        // delete v3
        $walk->getVertices()->getVertexLast()->destroy();

        $this->assertFalse($walk->isValid());
    }

    public function testWalkWithinGraph()
    {
        // 1 -- 2 -- 3
        $graph = new Graph();
        $v1 = $graph->createVertex(1);
        $v2 = $graph->createVertex(2);
        $v3 = $graph->createVertex(3);
        $e1 = $v1->createEdgeTo($v2);
        $e2 = $v2->createEdgeTo($v3);

        // construct partial walk "1 -- 2"
        $walk = Walk::factoryFromEdges(array($e1), $v1);

        $this->assertEquals(2, count($walk->getVertices()));
        $this->assertEquals(1, count($walk->getEdges()));
        $this->assertSame($v1, $walk->getVertices()->getVertexFirst());
        $this->assertSame($v2, $walk->getVertices()->getVertexLast());
        $this->assertSame(array($v1, $e1, $v2), $walk->getAlternatingSequence());
        $this->assertTrue($walk->isValid());

        $graphExpected = new Graph();
        $graphExpected->createVertex(1)->createEdgeTo($graphExpected->createVertex(2));

        $this->assertGraphEquals($graphExpected, $walk->createGraph());

        // construct same partial walk "1 -- 2"
        $walkVertices = Walk::factoryFromVertices(array($v1, $v2));

        $this->assertEquals(2, count($walkVertices->getVertices()));
        $this->assertEquals(1, count($walkVertices->getEdges()));

        $this->assertGraphEquals($graphExpected, $walkVertices->createGraph());

        return $walk;
    }

    public function testWalkLoop()
    {
        // 1 -- 1
        $graph = new Graph();
        $v1 = $graph->createVertex(1);
        $e1 = $v1->createEdge($v1);

        $walk = Walk::factoryFromEdges(array($e1), $v1);

        $this->assertEquals(2, count($walk->getVertices()));
        $this->assertEquals(1, count($walk->getEdges()));
        $this->assertSame($v1, $walk->getVertices()->getVertexFirst());
        $this->assertSame($v1, $walk->getVertices()->getVertexLast());
        $this->assertTrue($walk->isValid());

        return $walk;
    }

    /**
     * @param Walk $walk
     * @depends testWalkLoop
     */
    public function testWalkInvalidByDestroyingEdge(Walk $walk)
    {
        // destroy first edge found
        foreach ($walk->getEdges() as $edge) {
            $edge->destroy();
            break;
        }

        $this->assertFalse($walk->isValid());
    }

    public function testWalkLoopCycle()
    {
        // 1 -- 1
        $graph = new Graph();
        $v1 = $graph->createVertex(1);
        $e1 = $v1->createEdge($v1);

        $walk = Walk::factoryCycleFromEdges(array($e1), $v1);

        $this->assertEquals(2, count($walk->getVertices()));
        $this->assertEquals(1, count($walk->getEdges()));
        $this->assertSame($v1, $walk->getVertices()->getVertexFirst());
        $this->assertSame($v1, $walk->getVertices()->getVertexLast());
        $this->assertTrue($walk->isValid());
    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function testWalkCycleFromVerticesIncomplete()
    {
        // 1 -- 2 -- 1
        $graph = new Graph();
        $v1 = $graph->createVertex(1);
        $v2 = $graph->createVertex(2);
        $e1 = $v1->createEdge($v2);
        $e2 = $v2->createEdge($v1);

        // should actually be [v1, v2, v1]
        Walk::factoryCycleFromVertices(array($v1, $v2));
    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function testWalkCycleInvalid()
    {
        // 1 -- 2
        $graph = new Graph();
        $v1 = $graph->createVertex(1);
        $v2 = $graph->createVertex(2);
        $e1 = $v1->createEdge($v2);

        Walk::factoryCycleFromEdges(array($e1), $v1);
    }

    public function testLoopCycle()
    {
        // 1 --\
        // ^   |
        // \---/
        $graph = new Graph();
        $v1 = $graph->createVertex(1);
        $e1 = $v1->createEdgeTo($v1);

        $cycle = Walk::factoryCycleFromEdges(array($e1), $v1);
        $this->assertGraphEquals($graph, $cycle->createGraph());

        $cycle = Walk::factoryCycleFromPredecessorMap(array(1 => $v1), $v1);
        $this->assertGraphEquals($graph, $cycle->createGraph());

        $cycle = Walk::factoryCycleFromVertices(array($v1, $v1));
        $this->assertGraphEquals($graph, $cycle->createGraph());

        $this->assertCount(2, $cycle->getVertices());
        $this->assertCount(1, $cycle->getEdges());
        $this->assertSame($v1, $cycle->getVertices()->getVertexFirst());
        $this->assertSame($v1, $cycle->getVertices()->getVertexLast());
        $this->assertTrue($cycle->isValid());

        return $v1;
    }

    /**
     *
     * @param Vertex $v1
     * @depends testLoopCycle
     * @expectedException InvalidArgumentException
     */
    public function testFactoryCycleFromVerticesIncomplete(Vertex $v1)
    {
        // should actually be [v1, v1]
        Walk::factoryCycleFromVertices(array($v1));
    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function testInvalidPredecessors()
    {
        $graph = new Graph();
        $v1 = $graph->createVertex(1);

        Walk::factoryCycleFromPredecessorMap(array(), $v1);
    }

    public function testFactoryFromVertices()
    {
        // 1 -- 2
        // \----/
        $graph = new Graph();
        $v1 = $graph->createVertex(1);
        $v2 = $graph->createVertex(2);
        $e1 = $v1->createEdge($v2)->setWeight(10);
        $e2 = $v1->createEdge($v2)->setWeight(20);

        // any edge in walk
        $walk = Walk::factoryFromVertices(array($v1, $v2));

        // edge with weight 10
        $walk = Walk::factoryFromVertices(array($v1, $v2), Edges::ORDER_WEIGHT);
        $this->assertSame($e1, $walk->getEdges()->getEdgeFirst());

        // edge with weight 20
        $walk = Walk::factoryFromVertices(array($v1, $v2), Edges::ORDER_WEIGHT, true);
        $this->assertSame($e2, $walk->getEdges()->getEdgeFirst());
    }
}
