<?php

namespace Graphp\Algorithms\MaximumMatching;

use Graphp\Algorithms\Directed;

use Fhaculty\Graph\Exception\LogicException;

use Fhaculty\Graph\Exception\UnexpectedValueException;

use Graphp\Algorithms\MaxFlow\EdmondsKarp as MaxFlowEdmondsKarp;
use Graphp\Algorithms\Groups;
use Fhaculty\Graph\Exception;
use Fhaculty\Graph\Set\Edges;

class Flow extends Base
{
    public function getEdges()
    {
        $alg = new Directed($this->graph);
        if ($alg->hasDirected()) {
            throw new UnexpectedValueException('Input graph contains directed edges');
        }

        $alg = new Groups($this->graph);
        if (!$alg->isBipartit()) {
            throw new UnexpectedValueException('Input graph does not have bipartit groups assigned to each vertex. Consider Using "AlgorithmBipartit::createGraph()" first');
        }

        // create temporary flow graph with supersource and supersink
        $graphFlow = $this->graph->createGraphCloneEdgeless();

        $superSource = $graphFlow->createVertex();
        $superSink   = $graphFlow->createVertex();

        $groups = $alg->getGroups();
        $groupA = $groups[0];
        $groupB = $groups[1];

        // connect supersource s* to set A and supersink t* to set B
        foreach ($graphFlow->getVertices() as $vertex) {
            // we want to skip over supersource & supersink as they do not have a partition assigned
            if ($vertex === $superSource || $vertex === $superSink) continue;

            $group = $vertex->getGroup();

            // source
            if ($group === $groupA) {
                $superSource->createEdgeTo($vertex)->setCapacity(1)->setFlow(0);

                // temporarily create edges from A->B for flow graph
                $originalVertex = $this->graph->getVertex($vertex->getId());
                foreach ($originalVertex->getVerticesEdgeTo() as $vertexTarget) {
                    $vertex->createEdgeTo($graphFlow->getVertex($vertexTarget->getId()))->setCapacity(1)->setFlow(0);
                }
            // sink
            } elseif ($group === $groupB) {
                $vertex->createEdgeTo($superSink)->setCapacity(1)->setFlow(0);
            } else {
                // @codeCoverageIgnoreStart
                throw new LogicException('Should not happen. Unknown set: ' + $belongingSet);
                // @codeCoverageIgnoreEnd
            }
        }

        // visualize($resultGraph);

        // calculate (s*, t*)-flow
        $algMaxFlow = new MaxFlowEdmondsKarp($superSource, $superSink);
        $resultGraph = $algMaxFlow->createGraph();

        // destroy temporary supersource and supersink again
        $resultGraph->getVertex($superSink->getId())->destroy();
        $resultGraph->getVertex($superSource->getId())->destroy();

        $returnEdges = array();
        foreach ($resultGraph->getEdges() as $edge) {
            // only keep matched edges
            if ($edge->getFlow() > 0) {
                $originalEdge = $this->graph->getEdgeClone($edge);
                $returnEdges []= $originalEdge;
            }
        }

        return new Edges($returnEdges);
    }
}
