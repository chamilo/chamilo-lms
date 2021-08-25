<?php

namespace Graphp\Algorithms;

/**
 * Basic algorithms for working with complete graphs
 *
 * A complete graph is a graph in which every pair of vertices is connected
 * by an edge.
 *
 * @link http://en.wikipedia.org/wiki/Complete_graph
 * @link http://mathworld.wolfram.com/CompleteGraph.html
 */
class Complete extends BaseGraph
{
    /**
     * checks whether this graph is complete (every vertex has an edge to any other vertex)
     *
     * @return bool
     * @uses Graph::getVertices()
     * @uses Vertex::hasEdgeTo()
     */
    public function isComplete()
    {
        // copy of array (separate iterator but same vertices)
        $c = $vertices = $this->graph->getVertices()->getVector();
        // from each vertex
        foreach ($vertices as $vertex) {
            // to each vertex
            foreach ($c as $other) {
                // missing edge => fail
                if ($other !== $vertex && !$vertex->hasEdgeTo($other)) {
                    return false;
                }
            }
        }

        return true;
    }
}
