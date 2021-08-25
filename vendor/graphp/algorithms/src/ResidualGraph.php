<?php

namespace Graphp\Algorithms;

use Fhaculty\Graph\Edge\Base as Edge;
use Fhaculty\Graph\Edge\Directed as EdgeDirected;
use Fhaculty\Graph\Exception\UnexpectedValueException;
use Fhaculty\Graph\Graph;

class ResidualGraph extends BaseGraph
{
    private $keepNullCapacity = false;
    private $mergeParallelEdges = false;

    public function setKeepNullCapacity($toggle)
    {
        $this->keepNullCapacity = !!$toggle;

        return $this;
    }

    public function setMergeParallelEdges($toggle)
    {
        $this->mergeParallelEdges = !!$toggle;

        return $this;
    }

    /**
     * create residual graph
     *
     * @throws UnexpectedValueException if input graph has undirected edges or flow/capacity is not set
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

            $flow = $edge->getFlow();
            if ($flow === NULL) {
                throw new UnexpectedValueException('Flow not set');
            }

            $capacity = $edge->getCapacity();
            if ($capacity === NULL) {
                throw new UnexpectedValueException('Capacity not set');
            }

            // capacity is still available, clone remaining capacity into new edge
            if ($this->keepNullCapacity || $flow < $capacity) {
                $newEdge = $newgraph->createEdgeClone($edge)->setFlow(0)->setCapacity($capacity - $flow);

                if ($this->mergeParallelEdges) {
                    $this->mergeParallelEdges($newEdge);
                }
            }

            // flow is set, clone current flow as capacity for back-flow into new inverted edge (opposite direction)
            if ($this->keepNullCapacity || $flow > 0) {
                $newEdge = $newgraph->createEdgeCloneInverted($edge)->setFlow(0)->setCapacity($flow);

                // if weight is set, use negative weight for back-edges
                if ($newEdge->getWeight() !== NULL) {
                    $newEdge->setWeight(-$newEdge->getWeight());
                }

                if ($this->mergeParallelEdges) {
                    $this->mergeParallelEdges($newEdge);
                }
            }
        }

        return $newgraph;
    }

    /**
     * Will merge all edges that are parallel to to given edge
     *
     * @param Edge $newEdge
     */
    private function mergeParallelEdges(Edge $newEdge)
    {
        $alg = new Parallel($this->graph);
        $parallelEdges = $alg->getEdgesParallelEdge($newEdge)->getVector();

        if (!$parallelEdges) {
            return;
        }

        $mergedCapacity = 0;
        foreach ($parallelEdges as $parallelEdge) {
            $mergedCapacity += $parallelEdge->getCapacity();
        }

        $newEdge->setCapacity($newEdge->getCapacity() + $mergedCapacity);

        foreach ($parallelEdges as $parallelEdge) {
            $parallelEdge->destroy();
        }
    }
}
