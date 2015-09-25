<?php

namespace Graphp\Algorithms;

use Graphp\Algorithms\BaseGraph;
use Fhaculty\Graph\Exception\UnexpectedValueException;
use Fhaculty\Graph\Vertex;
use Fhaculty\Graph\Set\Vertices;
use Fhaculty\Graph\Graph;

/**
 * topological sorting / order, also known as toposort / topsort, commonly used in resolving dependencies
 *
 * @author clue
 * @link http://en.wikipedia.org/wiki/Topological_sorting
 */
class TopologicalSort extends BaseGraph
{
    /**
     * run algorithm and return an ordered/sorted set of Vertices
     *
     * the topologic sorting may be non-unique depending on your edges. this
     * algorithm tries to keep the order of vertices as added to the graph in
     * this case.
     *
     * @return Vertices
     */
    public function getVertices()
    {
        $tsl = array();
        $visited = array();

        // TODO: find alternative to recursive algorithm to avoid hitting recursion limit with ~100 nodes
        // TODO: avoid having to reverse all vertices multiple times

        foreach(array_reverse($this->graph->getVertices()->getVector()) as $vertex) {
            $this->visit($vertex, $visited, $tsl);
        }

        return new Vertices(array_reverse($tsl, true));
    }

    protected function visit(Vertex $vertex, array &$visited, array &$tsl)
    {
        $vid = $vertex->getId();
        if (isset($visited[$vid])) {
            if ($visited[$vid] === false) {
                // temporary mark => not a DAG
                throw new UnexpectedValueException('Not a DAG');
            }
            // otherwise already marked/visisted => no need to check again
        } else {
            // temporary mark
            $visited[$vid] = false;

            foreach (array_reverse($vertex->getVerticesEdgeTo()->getVector()) as $v) {
                $this->visit($v, $visited, $tsl);
            }

            // mark as visited and include in result
            $visited[$vid] = true;
            $tsl[$vid] = $vertex;
        }
    }
}
