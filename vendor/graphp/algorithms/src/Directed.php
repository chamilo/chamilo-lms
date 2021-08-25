<?php

namespace Graphp\Algorithms;

use Fhaculty\Graph\Edge\Directed as EdgeDirected;
use Fhaculty\Graph\Edge\Undirected as EdgeUndirected;

/**
 * Basic algorithms for working with the undirected or directed Graphs (digraphs) / Walks.
 *
 * @link http://en.wikipedia.org/wiki/Glossary_of_graph_theory#Direction
 * @link http://en.wikipedia.org/wiki/Digraph_%28mathematics%29
 */
class Directed extends BaseDual
{
    /**
     * checks whether the graph has any directed edges
     *
     * This method is intentionally not named "isDirected()" (aka digraph),
     * because that might be misleading in regards to empty and/or mixed graphs.
     *
     * @return bool
     */
    public function hasDirected()
    {
        foreach ($this->set->getEdges() as $edge) {
            if ($edge instanceof EdgeDirected) {
                return true;
            }
        }

        return false;
    }

    /**
     * checks whether the graph has any undirected edges
     *
     * This method is intentionally not named "isUndirected()",
     * because that might be misleading in regards to empty and/or mixed graphs.
     *
     * @return bool
     */
    public function hasUndirected()
    {
        foreach ($this->set->getEdges() as $edge) {
            if ($edge instanceof EdgeUndirected) {
                return true;
            }
        }

        return false;
    }

    /**
     * checks whether this is a mixed graph (contains both directed and undirected edges)
     *
     * @return bool
     * @uses self::hasDirected()
     * @uses self::hasUndirected()
     */
    public function isMixed()
    {
        return ($this->hasDirected() && $this->hasUndirected());
    }
}
