<?php

use Fhaculty\Graph\Graph;
use Graphp\Algorithms\Directed as AlgorithmDirected;

class DirectedTest extends TestCase
{
    public function testGraphEmpty()
    {
        $graph = new Graph();

        $alg = new AlgorithmDirected($graph);

        $this->assertFalse($alg->hasDirected());
        $this->assertFalse($alg->hasUndirected());
        $this->assertFalse($alg->isMixed());
    }

    public function testGraphUndirected()
    {
        // 1 -- 2
        $graph = new Graph();
        $graph->createVertex(1)->createEdge($graph->createVertex(2));

        $alg = new AlgorithmDirected($graph);

        $this->assertFalse($alg->hasDirected());
        $this->assertTrue($alg->hasUndirected());
        $this->assertFalse($alg->isMixed());
    }

    public function testGraphDirected()
    {
        // 1 -> 2
        $graph = new Graph();
        $graph->createVertex(1)->createEdgeTo($graph->createVertex(2));

        $alg = new AlgorithmDirected($graph);

        $this->assertTrue($alg->hasDirected());
        $this->assertFalse($alg->hasUndirected());
        $this->assertFalse($alg->isMixed());
    }

    public function testGraphMixed()
    {
        // 1 -- 2 -> 3
        $graph = new Graph();
        $graph->createVertex(1)->createEdge($graph->createVertex(2));
        $graph->getVertex(2)->createEdgeTo($graph->createVertex(3));

        $alg = new AlgorithmDirected($graph);

        $this->assertTrue($alg->hasDirected());
        $this->assertTrue($alg->hasUndirected());
        $this->assertTrue($alg->isMixed());
    }
}
