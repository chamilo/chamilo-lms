<?php

namespace Graphp\Algorithms;

use Fhaculty\Graph\Vertex;

/**
 * Basic algorithms for working with loop edges
 *
 * A loop (also called a self-loop or a "buckle") is an edge that connects a
 * Vertex to itself. A simple graph contains no loops.
 *
 * @link http://en.wikipedia.org/wiki/Loop_%28graph_theory%29
 */
class Loop extends BaseDual
{
    /**
     * checks whether this graph has any loops (edges from vertex to itself)
     *
     * @return bool
     * @uses Edge::isLoop()
     */
    public function hasLoop()
    {
        foreach ($this->set->getEdges() as $edge) {
            if ($edge->isLoop()) {
                return true;
            }
        }

        return false;
    }

    /**
     * checks whether this vertex has a loop (edge to itself)
     *
     * @return bool
     * @uses Edge::isLoop()
     */
    public function hasLoopVertex(Vertex $vertex)
    {
        foreach ($vertex->getEdges() as $edge) {
            if ($edge->isLoop()) {
                return true;
            }
        }

        return false;
    }
}
