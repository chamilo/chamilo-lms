<?php

use Fhaculty\Graph\Graph;
use Graphp\Algorithms\Weight as AlgorithmWeight;

class WeightTest extends TestCase
{
    public function testGraphEmpty()
    {
        $graph = new Graph();

        $alg = new AlgorithmWeight($graph);

        $this->assertEquals(null, $alg->getWeight());
        $this->assertEquals(0, $alg->getWeightFlow());
        $this->assertEquals(null, $alg->getWeightMin());
        $this->assertFalse($alg->isWeighted());

        return $graph;
    }

    /**
     *
     * @param Graph $graph
     * @depends testGraphEmpty
     */
    public function testGraphSimple(Graph $graph)
    {
        // 1 -> 2
        $graph->createVertex(1)->createEdgeTo($graph->createVertex(2))->setWeight(3)->setFlow(4);

        $alg = new AlgorithmWeight($graph);

        $this->assertEquals(3, $alg->getWeight());
        $this->assertEquals(12, $alg->getWeightFlow());
        $this->assertEquals(3, $alg->getWeightMin());
        $this->assertTrue($alg->isWeighted());

        return $graph;
    }

    /**
     *
     * @param Graph $graph
     * @depends testGraphSimple
     */
    public function testGraphWithUnweightedEdges(Graph $graph)
    {
        $graph->createVertex(5)->createEdgeTo($graph->createVertex(6))->setFlow(7);

        $alg = new AlgorithmWeight($graph);

        $this->assertEquals(3, $alg->getWeight());
        $this->assertEquals(12, $alg->getWeightFlow());
        $this->assertEquals(3, $alg->getWeightMin());
        $this->assertTrue($alg->isWeighted());
    }
}
