<?php

namespace Graphp\Algorithms;

use Graphp\Algorithms\BaseGraph;
use Fhaculty\Graph\Exception\UnexpectedValueException;

use Fhaculty\Graph\Graph;
use Fhaculty\Graph\Edge\Base as Edge;
use Fhaculty\Graph\Edge\Directed as EdgeDirected;

class TransposeGraph extends BaseGraph
{
    /**
     * create transpose graph
     *
     * @throws UnexpectedValueException if input graph has undirected edges
     * @return Graph
     * @uses Graph::createGraphCloneEdgeless()
     * @uses Graph::createEdgeClone()
     * @uses Graph::createEdgeCloneInverted()
     */
    public function createGraph()
    {
        $newgraph = $this->graph->createGraphCloneEdgeless();

        foreach ($this->graph->getEdges() as $edge) {
            if (!($edge instanceof EdgeDirected)) {
                throw new UnexpectedValueException('Edge is undirected');
            }
            $newgraph->createEdgeCloneInverted($edge);
        }

        return $newgraph;
    }
}
