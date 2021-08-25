<?php

use Fhaculty\Graph\Graph;
use Graphp\Algorithms\DetectNegativeCycle;

class DetectNegativeCycleTest extends TestCase
{
    public function testNullGraph()
    {
        $graph = new Graph();

        $alg = new DetectNegativeCycle($graph);

        $this->assertFalse($alg->hasCycleNegative());

        return $alg;
    }

    /**
     *
     * @param DetectNegativeCycle $alg
     * @depends testNullGraph
     * @expectedException UnderflowException
     */
    public function testNullGraphHasNoCycle(DetectNegativeCycle $alg)
    {
        $alg->getCycleNegative();
    }

    /**
     *
     * @param DetectNegativeCycle $alg
     * @depends testNullGraph
     * @expectedException UnderflowException
     */
    public function testNullGraphHasNoCycleGraph(DetectNegativeCycle $alg)
    {
        $alg->createGraph();
    }

    public function testNegativeLoop()
    {
        // 1 --[-1]--> 1
        $graph = new Graph();
        $v1 = $graph->createVertex(1);
        $e1 = $v1->createEdgeTo($v1)->setWeight(-1);

        $alg = new DetectNegativeCycle($graph);

        $this->assertTrue($alg->hasCycleNegative());

        $cycle = $alg->getCycleNegative();

        $this->assertCount(1, $cycle->getEdges());
        $this->assertCount(2, $cycle->getVertices());
        $this->assertEquals($e1, $cycle->getEdges()->getEdgeFirst());
        $this->assertEquals($v1, $cycle->getVertices()->getVertexFirst());
    }

    public function testNegativeCycle()
    {
        // 1 --[-1]--> 2
        // ^           |
        // \---[-2]----/
        $graph = new Graph();
        $v1 = $graph->createVertex(1);
        $v2 = $graph->createVertex(2);
        $v1->createEdgeTo($v2)->setWeight(-1);
        $v2->createEdgeTo($v1)->setWeight(-2);

        $alg = new DetectNegativeCycle($graph);

        $this->assertTrue($alg->hasCycleNegative());

        $cycle = $alg->getCycleNegative();

        $this->assertCount(2, $cycle->getEdges());
        $this->assertCount(3, $cycle->getVertices());
    }

    public function testNegativeUndirectedIsNegativeCycle()
    {
        // 1 --[-1]-- 2
        $graph = new Graph();
        $v1 = $graph->createVertex(1);
        $v2 = $graph->createVertex(2);
        $v1->createEdge($v2)->setWeight(-1);

        $alg = new DetectNegativeCycle($graph);

        $this->assertTrue($alg->hasCycleNegative());

        $cycle = $alg->getCycleNegative();

        $this->assertCount(2, $cycle->getEdges());
        $this->assertCount(3, $cycle->getVertices());
    }

    public function testNegativeCycleSubgraph()
    {
        // 1 --[1]--> 2 --[1]--> 3 --[1]--> 4
        //                       ^          |
        //                       \---[-2]---/
        $graph = new Graph();
        $v1 = $graph->createVertex(1);
        $v2 = $graph->createVertex(2);
        $v3 = $graph->createVertex(3);
        $v4 = $graph->createVertex(4);
        $v1->createEdgeTo($v2)->setWeight(1);
        $v2->createEdgeTo($v3)->setWeight(1);
        $v3->createEdgeTo($v4)->setWeight(1);
        $v4->createEdgeTo($v3)->setWeight(-2);

        $alg = new DetectNegativeCycle($graph);

        $this->assertTrue($alg->hasCycleNegative());

        $cycle = $alg->getCycleNegative();

        $this->assertCount(2, $cycle->getEdges());
        $this->assertCount(3, $cycle->getVertices());
        $this->assertTrue($cycle->getVertices()->hasVertexId(3));
        $this->assertTrue($cycle->getVertices()->hasVertexId(4));
    }

    public function testNegativeComponents()
    {
        // 1 -- 2     3 --[-1]--> 4
        //            ^           |
        //            \---[-2]----/
        $graph = new Graph();
        $v1 = $graph->createVertex(1);
        $v2 = $graph->createVertex(2);
        $v3 = $graph->createVertex(3);
        $v4 = $graph->createVertex(4);
        $v1->createEdge($v2);
        $v3->createEdgeTo($v4)->setWeight(-1);
        $v4->createEdgeTo($v3)->setWeight(-2);

        $alg = new DetectNegativeCycle($graph);

        $this->assertTrue($alg->hasCycleNegative());

        $cycle = $alg->getCycleNegative();

        $this->assertCount(2, $cycle->getEdges());
        $this->assertCount(3, $cycle->getVertices());
        $this->assertTrue($cycle->getVertices()->hasVertexId(3));
        $this->assertTrue($cycle->getVertices()->hasVertexId(4));
    }
}
