<?php

use Fhaculty\Graph\Graph;
use Fhaculty\Graph\Walk;
use Graphp\Algorithms\Property\WalkProperty;

class WalkPropertyTest extends TestCase
{
    public function testTrivialGraph()
    {
        $graph = new Graph();
        $v1 = $graph->createVertex(1);

        $walk = Walk::factoryFromEdges(array(), $v1);

        $this->assertEquals(1, \count($walk->getVertices()));
        $this->assertEquals(0, \count($walk->getEdges()));

        $alg = new WalkProperty($walk);

        $this->assertFalse($alg->isLoop());
        $this->assertFalse($alg->hasLoop());

        $this->assertFalse($alg->isCycle());
        $this->assertFalse($alg->hasCycle());

        $this->assertTrue($alg->isPath());
        $this->assertTrue($alg->isSimple());

        $this->assertTrue($alg->isEulerian());
        $this->assertTrue($alg->isHamiltonian());
    }

    public function testLoop()
    {
        // 1 -- 1
        $graph = new Graph();
        $v1 = $graph->createVertex(1);
        $e1 = $v1->createEdge($v1);

        $walk = Walk::factoryFromEdges(array($e1), $v1);

        $alg = new WalkProperty($walk);

        $this->assertTrue($alg->isLoop());
        $this->assertTrue($alg->hasLoop());

        $this->assertTrue($alg->isCycle());
        $this->assertTrue($alg->hasCycle());

        $this->assertTrue($alg->isPath());
        $this->assertTrue($alg->isSimple());

        $this->assertTrue($alg->isEulerian());
        $this->assertTrue($alg->isHamiltonian());
    }

    public function testCycle()
    {
        // 1 -- 2 -- 1
        $graph = new Graph();
        $v1 = $graph->createVertex(1);
        $v2 = $graph->createVertex(2);
        $e1 = $v1->createEdge($v2);
        $e2 = $v2->createEdge($v1);

        $walk = Walk::factoryFromEdges(array($e1, $e2), $v1);

        $this->assertEquals(3, \count($walk->getVertices()));
        $this->assertEquals(2, \count($walk->getEdges()));

        $alg = new WalkProperty($walk);

        $this->assertTrue($alg->isCycle());
        $this->assertTrue($alg->hasCycle());
        $this->assertTrue($alg->isPath());
        $this->assertTrue($alg->isSimple());

        $this->assertTrue($alg->isEulerian());
        $this->assertTrue($alg->isHamiltonian());
    }

    public function testCircuit()
    {
        // 1 -> 2 -> 1, 2 -> 2
        $graph = new Graph();
        $v1 = $graph->createVertex(1);
        $v2 = $graph->createVertex(2);
        $e1 = $v1->createEdgeTo($v2);
        $e2 = $v2->createEdgeTo($v1);
        $e3 = $v2->createEdgeTo($v2);

        // 1 -> 2 -> 2 -> 1
        $walk = Walk::factoryFromEdges(array($e1, $e3, $e2), $v1);

        $this->assertEquals(array(1, 2, 2, 1), $walk->getVertices()->getIds());

        $alg = new WalkProperty($walk);

        $this->assertTrue($alg->isCycle());
        $this->assertTrue($alg->isCircuit());
    }

    public function testNonCircuit()
    {
        // 1 -> 2 -> 1, 2 -> 2
        $graph = new Graph();
        $v1 = $graph->createVertex(1);
        $v2 = $graph->createVertex(2);
        $e1 = $v1->createEdgeTo($v2);
        $e2 = $v2->createEdgeTo($v1);
        $e3 = $v2->createEdgeTo($v2);

        // non-circuit: taking loop twice
        // 1 -> 2 -> 2 -> 2 -> 1
        $walk = Walk::factoryFromEdges(array($e1, $e3, $e3, $e2), $v1);

        $this->assertEquals(array(1, 2, 2, 2, 1), $walk->getVertices()->getIds());

        $alg = new WalkProperty($walk);

        $this->assertTrue($alg->isCycle());
        $this->assertFalse($alg->isCircuit());
    }

    public function testDigon()
    {
        // 1 -> 2 -> 1
        $graph = new Graph();
        $v1 = $graph->createVertex(1);
        $v2 = $graph->createVertex(2);
        $e1 = $v1->createEdgeTo($v2);
        $e2 = $v2->createEdgeTo($v1);

        $walk = Walk::factoryFromEdges(array($e1, $e2), $v1);

        $alg = new WalkProperty($walk);

        $this->assertTrue($alg->isDigon());
    }

    public function testTriangle()
    {
        // 1 -> 2 -> 3 -> 1
        $graph = new Graph();
        $v1 = $graph->createVertex(1);
        $v2 = $graph->createVertex(2);
        $v3 = $graph->createVertex(3);
        $e1 = $v1->createEdgeTo($v2);
        $e2 = $v2->createEdgeTo($v3);
        $e3 = $v3->createEdgeTo($v1);

        $walk = Walk::factoryFromEdges(array($e1, $e2, $e3), $v1);

        $alg = new WalkProperty($walk);

        $this->assertTrue($alg->isTriangle());
    }

    public function testSimplePathWithinGraph()
    {
        // 1 -- 2 -- 2
        $graph = new Graph();
        $v1 = $graph->createVertex(1);
        $v2 = $graph->createVertex(2);
        $v1->createEdge($v2);
        $e2 = $v2->createEdge($v2);

        // only use "2 -- 2" part
        $walk = Walk::factoryFromEdges(array($e2), $v2);

        $this->assertEquals(2, \count($walk->getVertices()));
        $this->assertEquals(1, \count($walk->getEdges()));

        $alg = new WalkProperty($walk);

        $this->assertTrue($alg->isCycle());
        $this->assertTrue($alg->hasCycle());
        $this->assertTrue($alg->isPath());
        $this->assertTrue($alg->isSimple());

        $this->assertFalse($alg->isEulerian());
        $this->assertFalse($alg->isHamiltonian());
    }
}
