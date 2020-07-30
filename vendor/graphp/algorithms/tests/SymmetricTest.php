<?php

use Fhaculty\Graph\Graph;
use Graphp\Algorithms\Symmetric as AlgorithmSymmetric;

class SymmetricTest extends TestCase
{
    public function testGraphEmpty()
    {
        $graph = new Graph();

        $alg = new AlgorithmSymmetric($graph);

        $this->assertTrue($alg->isSymmetric());
    }

    public function testGraphIsolated()
    {
        $graph = new Graph();
        $graph->createVertex(1);
        $graph->createVertex(2);

        $alg = new AlgorithmSymmetric($graph);

        $this->assertTrue($alg->isSymmetric());
    }

    public function testGraphSingleArcIsNotSymmetricr()
    {
        // 1 -> 2
        $graph = new Graph();
        $graph->createVertex(1)->createEdgeTo($graph->createVertex(2));

        $alg = new AlgorithmSymmetric($graph);

        $this->assertFalse($alg->isSymmetric());
    }

    public function testGraphAntiparallelIsSymmetricr()
    {
        // 1 -> 2 -> 1
        $graph = new Graph();
        $graph->createVertex(1)->createEdgeTo($graph->createVertex(2));
        $graph->getVertex(2)->createEdgeTo($graph->getVertex(1));

        $alg = new AlgorithmSymmetric($graph);

        $this->assertTrue($alg->isSymmetric());
    }

    public function testGraphSingleUndirectedIsSymmetricr()
    {
        // 1 -- 2
        $graph = new Graph();
        $graph->createVertex(1)->createEdge($graph->createVertex(2));

        $alg = new AlgorithmSymmetric($graph);

        $this->assertTrue($alg->isSymmetric());
    }
}
