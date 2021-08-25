<?php

use Fhaculty\Graph\Graph;
use Graphp\Algorithms\Flow as AlgorithmFlow;

class FlowaTest extends TestCase
{
    public function testGraphEmpty()
    {
        $graph = new Graph();

        $alg = new AlgorithmFlow($graph);

        $this->assertFalse($alg->hasFlow());
        $this->assertEquals(0, $alg->getBalance());
        $this->assertTrue($alg->isBalancedFlow());

        return $graph;
    }

    public function testEdgeWithZeroFlowIsConsideredFlow()
    {
        // 1 -> 2
        $graph = new Graph();
        $graph->createVertex(1)->createEdgeTo($graph->createVertex(2))->setFlow(0);


        $alg = new AlgorithmFlow($graph);

        $this->assertTrue($alg->hasFlow());
        $this->assertEquals(0, $alg->getFlowVertex($graph->getVertex(1)));
        $this->assertEquals(0, $alg->getFlowVertex($graph->getVertex(2)));
    }

    /**
     *
     * @param Graph $graph
     * @depends testGraphEmpty
     */
    public function testGraphSimple(Graph $graph)
    {
        // 1 -> 2
        $graph->createVertex(1)->createEdgeTo($graph->createVertex(2));

        $alg = new AlgorithmFlow($graph);

        $this->assertFalse($alg->hasFlow());
        $this->assertEquals(0, $alg->getFlowVertex($graph->getVertex(1)));
        $this->assertEquals(0, $alg->getFlowVertex($graph->getVertex(2)));

        return $graph;
    }

    /**
     *
     * @param Graph $graph
     * @depends testGraphSimple
     */
    public function testGraphWithUnweightedEdges(Graph $graph)
    {
        // additional flow edge: 2 -> 3
        $graph->getVertex(2)->createEdgeTo($graph->createVertex(3))->setFlow(10);

        $alg = new AlgorithmFlow($graph);

        $this->assertTrue($alg->hasFlow());
        $this->assertEquals(10, $alg->getFlowVertex($graph->getVertex(2)));
        $this->assertEquals(-10, $alg->getFlowVertex($graph->getVertex(3)));
    }

    public function testGraphBalance()
    {
        // source(+100) -> sink(-10)
        $graph = new Graph();
        $graph->createVertex('source')->setBalance(100);
        $graph->createVertex('sink')->setBalance(-10);

        $alg = new AlgorithmFlow($graph);

        $this->assertEquals(90, $alg->getBalance());
        $this->assertFalse($alg->isBalancedFlow());
    }

    /**
     * @expectedException UnexpectedValueException
     */
    public function testVertexWithUndirectedEdgeHasInvalidFlow()
    {
        // 1 -- 2
        $graph = new Graph();
        $graph->createVertex(1)->createEdge($graph->createVertex(2))->setFlow(10);


        $alg = new AlgorithmFlow($graph);

        $alg->getFlowVertex($graph->getVertex(1));
    }
}
