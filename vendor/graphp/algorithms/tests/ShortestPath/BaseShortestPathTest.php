<?php

use Fhaculty\Graph\Graph;
use Fhaculty\Graph\Vertex;
use Graphp\Algorithms\ShortestPath\Base as ShortestPathAlg;

abstract class BaseShortestPathTest extends TestCase
{
    /**
     *
     * @param Vertex $vertex
     * @return ShortestPathAlg
     */
    abstract protected function createAlg(Vertex $vertex);

    abstract public function testGraphParallelNegative();

    public function testGraphTrivial()
    {
        // 1
        $graph = new Graph();
        $v1 = $graph->createVertex(1);

        $alg = $this->createAlg($v1);

        $this->assertFalse($alg->hasVertex($v1));
        //$this->assertEquals(0, $alg->getDistance($v1));
        $this->assertEquals(array(), $alg->getDistanceMap());
        $this->assertEquals(array(), $alg->getEdges()->getVector());
        //$this->assertEquals(array(), $alg->getEdgesTo($v1));
        $this->assertEquals(array(), $alg->getVertices()->getVector());
        $this->assertEquals(array(), $alg->getVertices()->getIds());

        $clone = $alg->createGraph();
        $this->assertGraphEquals($graph,$clone);
    }

    public function testGraphSingleLoop()
    {
        // 1 -[4]> 1
        $graph = new Graph();
        $v1 = $graph->createVertex(1);
        $e1 = $v1->createEdgeTo($v1)->setWeight(4);

        $alg = $this->createAlg($v1);

        $this->assertEquals(array($e1), $alg->getEdges()->getVector());

        $expectedWeight = $this->getExpectedWeight(array($e1));
        $this->assertTrue($alg->hasVertex($v1));
        $this->assertEquals($expectedWeight, $alg->getDistance($v1));
        $this->assertEquals(array(1 => $expectedWeight), $alg->getDistanceMap());
        $this->assertEquals(array($e1), $alg->getEdgesTo($v1)->getVector());
        $this->assertEquals(array(1 => $v1), $alg->getVertices()->getMap());
        $this->assertEquals(array(1), $alg->getVertices()->getIds());
    }

    public function testGraphCycle()
    {
        // 1 -[4]-> 2 -[2]-> 1
        $graph = new Graph();
        $v1 = $graph->createVertex(1);
        $v2 = $graph->createVertex(2);
        $e1 = $v1->createEdgeTo($v2)->setWeight(4);
        $e2 = $v2->createEdgeTo($v1)->setWeight(2);

        $alg = $this->createAlg($v1);

        //$this->assertEquals(array($e2, $e1), $alg->getEdges());

        $expectedWeight = $this->getExpectedWeight(array($e1));
        $this->assertTrue($alg->hasVertex($v2));
        $this->assertEquals(array($e1), $alg->getEdgesTo($v2)->getVector());
        $this->assertEquals($expectedWeight, $alg->getDistance($v2));

        $expectedWeight = $this->getExpectedWeight(array($e1, $e2));
        $this->assertTrue($alg->hasVertex($v1));
        $this->assertEquals(array($e1, $e2), $alg->getEdgesTo($v1)->getVector());
        $this->assertEquals($expectedWeight, $alg->getDistance($v1));

        $walk = $alg->getWalkTo($v1);
        $this->assertEquals(2, \count($walk->getEdges()));
    }

    /**
     * @expectedException OutOfBoundsException
     */
    public function testIsolatedVertexIsNotReachable()
    {
        // 1, 2
        $graph = new Graph();
        $v1 = $graph->createVertex(1);
        $v2 = $graph->createVertex(2);

        $alg = $this->createAlg($v1);

        $this->assertFalse($alg->hasVertex($v2));

        $alg->getEdgesTo($v2);
    }

    /**
     * @expectedException OutOfBoundsException
     */
    public function testSeparateGraphsAreNotReachable()
    {
        // 1
        $graph1 = new Graph();
        $vg1 = $graph1->createVertex(1);

        $graph2 = new Graph();
        $vg2 = $graph2->createVertex(1);

        $alg = $this->createAlg($vg1);

        $alg->getEdgesTo($vg2);
    }

    public function testGraphUnweighted()
    {
        // 1 -> 2
        $graph = new Graph();
        $v1 = $graph->createVertex(1);
        $v2 = $graph->createVertex(2);
        $e1 = $v1->createEdgeTo($v2);

        $alg = $this->createAlg($v1);

        $expectedWeight = $this->getExpectedWeight(array($e1));
        $this->assertEquals($expectedWeight, $alg->getDistance($v2));
        $this->assertEquals(array(2 => $expectedWeight), $alg->getDistanceMap());
        $this->assertEquals(array($e1), $alg->getEdges()->getVector());
        $this->assertEquals(array($e1), $alg->getEdgesTo($v2)->getVector());
        $this->assertEquals(array(2), $alg->getVertices()->getIds());
    }

    public function testGraphTwoComponents()
    {
        // 1 -[10]-> 2
        // 3 -[20]-> 4
        $graph = new Graph();
        $v1 = $graph->createVertex(1);
        $v2 = $graph->createVertex(2);
        $v3 = $graph->createVertex(3);
        $v4 = $graph->createVertex(4);
        $e1 = $v1->createEdgeTo($v2)->setWeight(10);
        $v3->createEdgeTo($v4)->setWeight(20);

        $alg = $this->createAlg($v1);

        $expectedWeight = $this->getExpectedWeight(array($e1));
        $this->assertEquals($expectedWeight, $alg->getDistance($v2));
        $this->assertEquals(array(2 => $expectedWeight), $alg->getDistanceMap());
        $this->assertEquals(array($e1), $alg->getEdges()->getVector());
        // $this->assertEquals(array(), $alg->getEdgesTo($v1));
        $this->assertEquals(array($e1), $alg->getEdgesTo($v2)->getVector());
        $this->assertEquals(array(2 => $v2), $alg->getVertices()->getMap());
        $this->assertEquals(array(2), $alg->getVertices()->getIds());
    }

    protected function getExpectedWeight($edges)
    {
        $sum = 0;
        foreach ($edges as $edge) {
            $sum += $edge->getWeight();
        }
        return $sum;
    }
}
