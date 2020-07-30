<?php

use Fhaculty\Graph\Graph;
use Fhaculty\Graph\Vertex;
use Graphp\Algorithms\ShortestPath\BreadthFirst;

class BreadthFirstTest extends BaseShortestPathTest
{
    protected function createAlg(Vertex $vertex)
    {
        return new BreadthFirst($vertex);
    }

    public function testGraphParallelNegative()
    {
        // 1 -[10]-> 2
        // |         ^
        // \--[-1]---/
        $graph = new Graph();
        $v1 = $graph->createVertex(1);
        $v2 = $graph->createVertex(2);
        $e1 = $v1->createEdgeTo($v2)->setWeight(10);
        $v1->createEdgeTo($v2)->setWeight(-1);

        $alg = $this->createAlg($v1);

        $this->assertEquals(1, $alg->getDistance($v2));
        $this->assertEquals(array(2 => 1), $alg->getDistanceMap());
        $this->assertEquals(array($e1), $alg->getEdges()->getVector());
        $this->assertEquals(array($e1), $alg->getEdgesTo($v2)->getVector());
        $this->assertEquals(array(2 => $v2), $alg->getVertices()->getMap());
        $this->assertEquals(array(2), $alg->getVertices()->getIds());
    }

    protected function getExpectedWeight($edges)
    {
        return \count($edges);
    }
}
