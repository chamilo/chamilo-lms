<?php

use Fhaculty\Graph\Graph;
use Graphp\Algorithms\ConnectedComponents as AlgorithmConnected;

class ConnectedComponentsTest extends TestCase
{
    public function testNullGraph()
    {
        $graph = new Graph();

        $alg = new AlgorithmConnected($graph);

        $this->assertEquals(0, $alg->getNumberOfComponents());
        $this->assertFalse($alg->isSingle());
        $this->assertCount(0, $alg->createGraphsComponents());
    }

    public function testGraphSingleTrivial()
    {
        $graph = new Graph();
        $graph->createVertex(1);

        $alg = new AlgorithmConnected($graph);

        $this->assertEquals(1, $alg->getNumberOfComponents());
        $this->assertTrue($alg->isSingle());

        $graphs = $alg->createGraphsComponents();

        $this->assertCount(1, $graphs);
        $this->assertGraphEquals($graph, \reset($graphs));
    }

    public function testGraphEdgeDirections()
    {
        // 1 -- 2 -> 3 <- 4
        $graph = new Graph();
        $graph->createVertex(1)->createEdge($graph->createVertex(2));
        $graph->getVertex(2)->createEdgeTo($graph->createVertex(3));
        $graph->createVertex(4)->createEdgeTo($graph->getVertex(3));

        $alg = new AlgorithmConnected($graph);

        $this->assertEquals(1, $alg->getNumberOfComponents());
        $this->assertTrue($alg->isSingle());

        $graphs = $alg->createGraphsComponents();

        $this->assertCount(1, $graphs);
        $this->assertGraphEquals($graph, \reset($graphs));
        $this->assertGraphEquals($graph, $alg->createGraphComponentVertex($graph->getVertex(1)));
    }

    public function testComponents()
    {
        // 1 -- 2, 3 -> 4, 5
        $graph = new Graph();
        $v1 = $graph->createVertex(1);
        $v2 = $graph->createVertex(2);
        $v3 = $graph->createVertex(3);
        $v4 = $graph->createVertex(4);
        $v5 = $graph->createVertex(5);
        $v1->createEdge($v2);
        $v3->createEdgeTo($v4);

        $alg = new AlgorithmConnected($graph);

        $this->assertEquals(3, $alg->getNumberOfComponents());
        $this->assertFalse($alg->isSingle());

        $graphs = $alg->createGraphsComponents();
        $this->assertCount(3, $graphs);

        $ge = new Graph();
        $ge->createVertex(1)->createEdge($ge->createVertex(2));
        $this->assertGraphEquals($ge, $alg->createGraphComponentVertex($v2));

        $ge = new Graph();
        $ge->createVertex(5);
        $this->assertEquals($ge, $alg->createGraphComponentVertex($v5));
    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function testInvalidVertexPassedToAlgorithm()
    {
        $graph = new Graph();

        $graph2 = new Graph();
        $v2 = $graph2->createVertex(12);

        $alg = new AlgorithmConnected($graph);
        $alg->createGraphComponentVertex($v2);
    }
}
