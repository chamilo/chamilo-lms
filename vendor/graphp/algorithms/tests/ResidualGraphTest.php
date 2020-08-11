<?php

use Fhaculty\Graph\Graph;
use Graphp\Algorithms\ResidualGraph;

class ResidualGraphTest extends TestCase
{
    public function testGraphEmpty()
    {
        $graph = new Graph();

        $alg = new ResidualGraph($graph);
        $residual = $alg->createGraph();

        $this->assertGraphEquals($graph, $residual);
    }

    /**
     * test an edge with capacity unused
     */
    public function testEdgeUnused()
    {
        $graph = new Graph();

        $graph->createVertex(0)->createEdgeTo($graph->createVertex(1))->setFlow(0)
                                                                      ->setCapacity(2)
                                                                      ->setWeight(3);

        $alg = new ResidualGraph($graph);
        $residual = $alg->createGraph();

        $this->assertGraphEquals($graph, $residual);
    }

    /**
     * test an edge with capacity completely used
     */
    public function testEdgeUsed()
    {
        $graph = new Graph();

        $graph->createVertex(0)->createEdgeTo($graph->createVertex(1))->setFlow(2)
                                                                      ->setCapacity(2)
                                                                      ->setWeight(3);

        $alg = new ResidualGraph($graph);
        $residual = $alg->createGraph();

        $expected = new Graph();
        $expected->createVertex(1)->createEdgeTo($expected->createVertex(0))->setFlow(0)
                                                                            ->setCapacity(2)
                                                                            ->setWeight(-3);

        $this->assertGraphEquals($expected, $residual);
    }

    /**
     * test an edge with capacity remaining
     */
    public function testEdgePartial()
    {
        $graph = new Graph();

        $graph->createVertex(0)->createEdgeTo($graph->createVertex(1))->setFlow(1)
                                                                    ->setCapacity(2)
                                                                    ->setWeight(3);

        $alg = new ResidualGraph($graph);
        $residual = $alg->createGraph();

        $expected = new Graph();
        $expected->createVertex(0);
        $expected->createVertex(1);

        // remaining edge
        $expected->getVertex(0)->createEdgeTo($expected->getVertex(1))->setFlow(0)
                                                                      ->setCapacity(1)
                                                                      ->setWeight(3);

        // back edge
        $expected->getVertex(1)->createEdgeTo($expected->getVertex(0))->setFlow(0)
                                                                      ->setCapacity(1)
                                                                      ->setWeight(-3);

        $this->assertGraphEquals($expected, $residual);
    }

    public function testResidualGraphCanOptionallyKeepNullCapacityForEdgeWithZeroFlow()
    {
        // 1 -[0/2]-> 2
        $graph = new Graph();
        $v1 = $graph->createVertex(1);
        $v2 = $graph->createVertex(2);
        $v1->createEdgeTo($v2)->setFlow(0)->setCapacity(2);

        // 1 -[0/2]-> 2
        // ^          |
        // \--[0/0]---/
        $expected = new Graph();
        $v1 = $expected->createVertex(1);
        $v2 = $expected->createVertex(2);
        $v1->createEdgeTo($v2)->setFlow(0)->setCapacity(2);
        $v2->createEdgeTo($v1)->setFlow(0)->setCapacity(0);

        $alg = new ResidualGraph($graph);
        $alg->setKeepNullCapacity(true);
        $residual = $alg->createGraph();

        $this->assertGraphEquals($expected, $residual);
    }

    public function testResidualGraphCanOptionallyKeepNullCapacityForEdgeWithZeroCapacityRemaining()
    {
        // 1 -[2/2]-> 2
        $graph = new Graph();
        $v1 = $graph->createVertex(1);
        $v2 = $graph->createVertex(2);
        $v1->createEdgeTo($v2)->setFlow(2)->setCapacity(2);

        // 1 -[0/0]-> 2
        // ^          |
        // \--[0/2]---/
        $expected = new Graph();
        $v1 = $expected->createVertex(1);
        $v2 = $expected->createVertex(2);
        $v1->createEdgeTo($v2)->setFlow(0)->setCapacity(0);
        $v2->createEdgeTo($v1)->setFlow(0)->setCapacity(2);

        $alg = new ResidualGraph($graph);
        $alg->setKeepNullCapacity(true);
        $residual = $alg->createGraph();

        $this->assertGraphEquals($expected, $residual);
    }

    public function testParallelEdgesCanBeMerged()
    {
        // 1 -[1/2]-> 2
        // |          ^
        // \--[2/3]---/
        $graph = new Graph();
        $v1 = $graph->createVertex(1);
        $v2 = $graph->createVertex(2);
        $v1->createEdgeTo($v2)->setFlow(1)->setCapacity(2);
        $v1->createEdgeTo($v2)->setFlow(2)->setCapacity(3);

        // 1 -[0/2]-> 2
        // ^          |
        // \--[0/3]---/
        $expected = new Graph();
        $v1 = $expected->createVertex(1);
        $v2 = $expected->createVertex(2);
        $v1->createEdgeTo($v2)->setFlow(0)->setCapacity(2);
        $v2->createEdgeTo($v1)->setFlow(0)->setCapacity(3);

        $alg = new ResidualGraph($graph);
        $alg->setMergeParallelEdges(true);
        $residual = $alg->createGraph();

        $this->assertGraphEquals($expected, $residual);
    }

    /**
     * expect exception for undirected edges
     * @expectedException UnexpectedValueException
     */
    public function testInvalidUndirected()
    {
        $graph = new Graph();

        $graph->createVertex()->createEdge($graph->createVertex())->setFlow(1)
                                                                  ->setCapacity(2);

        $alg = new ResidualGraph($graph);
        $alg->createGraph();
    }

    /**
     * expect exception for edges with no flow
     * @expectedException UnexpectedValueException
     */
    public function testInvalidNoFlow()
    {
        $graph = new Graph();

        $graph->createVertex()->createEdgeTo($graph->createVertex())->setCapacity(1);

        $alg = new ResidualGraph($graph);
        $alg->createGraph();
    }

    /**
     * expect exception for edges with no capacity
     * @expectedException UnexpectedValueException
     */
    public function testInvalidNoCapacity()
    {
        $graph = new Graph();

        $graph->createVertex()->createEdgeTo($graph->createVertex())->setFlow(1);

        $alg = new ResidualGraph($graph);
        $alg->createGraph();
    }

}
