<?php

use Fhaculty\Graph\Graph;
use Graphp\Algorithms\Loop as AlgorithmLoop;

class LoopTest extends TestCase
{
    public function testGraphEmpty()
    {
        $graph = new Graph();

        $alg = new AlgorithmLoop($graph);

        $this->assertFalse($alg->hasLoop());
    }

    public function testGraphWithMixedCircuitIsNotConsideredLoop()
    {
        // 1 -> 2
        // 2 -- 1
        $graph = new Graph();
        $v1 = $graph->createVertex(1);
        $v2 = $graph->createVertex(2);
        $v1->createEdgeTo($v2);
        $v2->createEdge($v1);

        $alg = new AlgorithmLoop($graph);

        $this->assertFalse($alg->hasLoop());
        $this->assertFalse($alg->hasLoopVertex($v1));
        $this->assertFalse($alg->hasLoopVertex($v2));
    }

    public function testGraphUndirectedLoop()
    {
        // 1 -- 1
        $graph = new Graph();
        $graph->createVertex(1)->createEdge($v1 = $graph->getVertex(1));

        $alg = new AlgorithmLoop($graph);

        $this->assertTrue($alg->hasLoop());
        $this->assertTrue($alg->hasLoopVertex($v1));
    }

    public function testGraphDirectedLoop()
    {
        // 1 -> 1
        $graph = new Graph();
        $graph->createVertex(1)->createEdgeTo($v1 = $graph->getVertex(1));

        $alg = new AlgorithmLoop($graph);

        $this->assertTrue($alg->hasLoop());
        $this->assertTrue($alg->hasLoopVertex($v1));
    }
}
