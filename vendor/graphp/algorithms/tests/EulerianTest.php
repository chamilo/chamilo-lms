<?php

use Fhaculty\Graph\Graph;
use Graphp\Algorithms\Eulerian as AlgorithmEulerian;

class EulerianTest extends TestCase
{
    public function testGraphEmpty()
    {
        $graph = new Graph();

        $alg = new AlgorithmEulerian($graph);

        $this->assertFalse($alg->hasCycle());
    }

    public function testGraphPairHasNoCycle()
    {
        // 1 -- 2
        $graph = new Graph();
        $v1 = $graph->createVertex(1);
        $v2 = $graph->createVertex(2);
        $v1->createEdge($v2);

        $alg = new AlgorithmEulerian($graph);

        $this->assertFalse($alg->hasCycle());
    }

    public function testGraphTriangleCycleIsNotBipartit()
    {
        // 1 -- 2 -- 3 -- 1
        $graph = new Graph();
        $v1 = $graph->createVertex(1);
        $v2 = $graph->createVertex(2);
        $v3 = $graph->createVertex(3);
        $v1->createEdge($v2);
        $v2->createEdge($v3);
        $v3->createEdge($v1);

        $alg = new AlgorithmEulerian($graph);

        $this->assertTrue($alg->hasCycle());
    }
}
