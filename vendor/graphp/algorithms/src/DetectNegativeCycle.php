<?php

namespace Graphp\Algorithms;

use Fhaculty\Graph\Exception\NegativeCycleException;
use Fhaculty\Graph\Exception\UnderflowException;
use Fhaculty\Graph\Graph;
use Fhaculty\Graph\Walk;
use Graphp\Algorithms\ShortestPath\MooreBellmanFord as SpMooreBellmanFord;

class DetectNegativeCycle extends BaseGraph
{
    /**
     * check if the input graph has any negative cycles
     *
     * @return bool
     * @uses AlgorithmDetectNegativeCycle::getCycleNegative()
     */
    public function hasCycleNegative()
    {
        try {
            $this->getCycleNegative();

            // cycle was found => okay
            return true;
        // no cycle found
        } catch (UnderflowException $ignore) {}

        return false;
    }

    /**
     * Searches all vertices for the first negative cycle
     *
     * @return Walk
     * @throws UnderflowException if there's no negative cycle
     * @uses AlgorithmSpMooreBellmanFord::getVertices()
     */
    public function getCycleNegative()
    {
        // remember vertices already visited, as they can not lead to a new cycle
        $verticesVisited = array();
        // check for all vertices
        foreach ($this->graph->getVertices()->getMap() as $vid => $vertex) {
            // skip vertices already visited
            if (!isset($verticesVisited[$vid])) {
                // start MBF algorithm on current vertex
                $alg = new SpMooreBellmanFord($vertex);

                try {
                    // try to get all connected vertices (or throw new cycle)
                    foreach ($alg->getVertices()->getIds() as $vid) {
                        // getting connected vertices succeeded, so skip over all of them
                        $verticesVisited[$vid] = true;
                    // no cycle found, check next vertex...
                    }
                // yey, negative cycle encountered => return
                } catch (NegativeCycleException $e) {
                    return $e->getCycle();
                }
            }
        // no more vertices to check => abort
        }
        throw new UnderflowException('No negative cycle found');
    }

    /**
     * create new graph clone with only vertices and edges in negative cycle
     *
     * @return Graph
     * @throws UnderflowException if there's no negative cycle
     * @uses AlgorithmDetectNegativeCycle::getCycleNegative()
     * @uses Walk::createGraph()
     */
    public function createGraph()
    {
        return $this->getCycleNegative()->createGraph();
    }
}
