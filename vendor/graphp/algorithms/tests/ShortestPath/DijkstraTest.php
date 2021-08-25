<?php

use Fhaculty\Graph\Graph;
use Fhaculty\Graph\Vertex;
use Graphp\Algorithms\ShortestPath\Dijkstra;

class DijkstraTest extends BaseShortestPathTest
{
    protected function createAlg(Vertex $vertex)
    {
        return new Dijkstra($vertex);
    }

    /**
     * @expectedException UnexpectedValueException
     */
    public function testGraphParallelNegative()
    {
        // 1 -[10]-> 2
        // |         ^
        // \--[-1]---/
        $graph = new Graph();
        $v1 = $graph->createVertex(1);
        $v2 = $graph->createVertex(2);
        $v1->createEdgeTo($v2)->setWeight(10);
        $v1->createEdgeTo($v2)->setWeight(-1);

        $alg = $this->createAlg($v1);

        $alg->getEdges();
    }
}
