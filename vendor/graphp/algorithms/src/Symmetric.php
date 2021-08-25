<?php

namespace Graphp\Algorithms;

use Fhaculty\Graph\Edge\Directed as EdgeDirected;

/**
 * Basic algorithms for working with symmetric digraphs
 *
 * A directed graph is called symmetric if, for every arc that belongs to it,
 * the corresponding reversed arc (antiparallel directed edge) also belongs to it.
 *
 * @link http://en.wikipedia.org/wiki/Directed_graph#Classes_of_digraphs
 */
class Symmetric extends BaseGraph
{
    /**
     * checks whether this graph is symmetric (for every edge a->b there's also an edge b->a)
     *
     * @return bool
     * @uses Graph::getEdges()
     * @uses EdgeDirected::getVertexStart()
     * @uses EdgeDirected::getVertedEnd()
     * @uses Vertex::hasEdgeTo()
     */
    public function isSymmetric()
    {
        // check all edges
        foreach ($this->graph->getEdges() as $edge) {
            // only check directed edges (undirected ones are symmetric by definition)
            if ($edge instanceof EdgeDirected) {
                // check if end also has an edge to start
                if (!$edge->getVertexEnd()->hasEdgeTo($edge->getVertexStart())) {
                    return false;
                }
            }
        }

        return true;
    }
}
