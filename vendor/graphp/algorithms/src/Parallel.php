<?php

namespace Graphp\Algorithms;

use Fhaculty\Graph\Edge\Base as Edge;
use Fhaculty\Graph\Edge\Directed as DirectedEdge;
use Fhaculty\Graph\Set\Edges;
use LogicException;

/**
 * Basic algorithms for working with parallel edges
 *
 * Parallel edges (also called multiple edges or a multi-edge), are two or more
 * edges that are incident to the same two vertices. A simple graph has no
 * multiple edges.
 *
 * @link http://en.wikipedia.org/wiki/Multiple_edges
 */
class Parallel extends BaseGraph
{
    /**
     * checks whether this graph has any parallel edges (aka multigraph)
     *
     * @return bool
     * @uses Edge::hasEdgeParallel() for every edge
     */
    public function hasEdgeParallel()
    {
        foreach ($this->graph->getEdges() as $edge) {
            if ($this->hasEdgeParallelEdge($edge)) {
                return true;
            }
        }

        return false;
    }


    /**
     * checks whether this edge has any parallel edges
     *
     * @return bool
     * @uses Edge::getEdgesParallel()
     */
    public function hasEdgeParallelEdge(Edge $edge)
    {
        return !$this->getEdgesParallelEdge($edge)->isEmpty();
    }

    /**
     * get set of all Edges parallel to this edge (excluding self)
     *
     * @param Edge $edge
     * @return Edges
     * @throws LogicException
     */
    public function getEdgesParallelEdge(Edge $edge)
    {
        if ($edge instanceof DirectedEdge) {
            // get all edges between this edge's endpoints
            $edges = $edge->getVertexStart()->getEdgesTo($edge->getVertexEnd())->getVector();
        } else {
            // edge points into both directions (undirected/bidirectional edge)
            // also get all edges in other direction
            $ends  = $edge->getVertices();
            $edges = $ends->getVertexFirst()->getEdges()->getEdgesIntersection($ends->getVertexLast()->getEdges())->getVector();
        }

        $pos = \array_search($edge, $edges, true);
        assert($pos !== false);

        // exclude current edge from parallel edges
        unset($edges[$pos]);

        return new Edges(\array_values($edges));
    }
}
