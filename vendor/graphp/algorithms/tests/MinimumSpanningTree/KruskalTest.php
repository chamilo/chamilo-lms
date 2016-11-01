<?php

use Fhaculty\Graph\Graph;
use Fhaculty\Graph\Vertex;
use Graphp\Algorithms\MinimumSpanningTree\Kruskal;

class KruskalTest extends BaseMstTest
{
    protected function createAlg(Vertex $vertex)
    {
        return new Kruskal($vertex->getGraph());
    }

    /**
     * @expectedException UnexpectedValueException
     */
    public function testNullGraphIsNotConsideredToBeConnected()
    {
        $graph = new Graph();

        $alg = new Kruskal($graph);
        $alg->getEdges();
    }
}
