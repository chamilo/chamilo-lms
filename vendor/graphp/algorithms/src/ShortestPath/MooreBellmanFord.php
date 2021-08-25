<?php

namespace Graphp\Algorithms\ShortestPath;

use Fhaculty\Graph\Exception\NegativeCycleException;
use Fhaculty\Graph\Exception\UnderflowException;
use Fhaculty\Graph\Set\Edges;
use Fhaculty\Graph\Vertex;
use Fhaculty\Graph\Walk;

/**
 * Moore-Bellman-Ford's shortest path algorithm
 *
 * It is slower than Dijkstra's algorithm for the same problem, but more
 * versatile, as it is capable of handling Graphs with negative Edge weights.
 *
 * Also known as just "Bellman–Ford algorithm".
 *
 * @link http://en.wikipedia.org/wiki/Bellman%E2%80%93Ford_algorithm
 */
class MooreBellmanFord extends Base
{
    /**
     * @param Edges    $edges
     * @param int[]    $totalCostOfCheapestPathTo
     * @param Vertex[] $predecessorVertexOfCheapestPathTo
     * @return Vertex|NULL
     */
    private function bigStep(Edges $edges, array &$totalCostOfCheapestPathTo, array &$predecessorVertexOfCheapestPathTo)
    {
        $changed = NULL;
        // check for all edges
        foreach ($edges as $edge) {
            // check for all "ends" of this edge (or for all targetes)
            foreach ($edge->getVerticesTarget() as $toVertex) {
                $fromVertex = $edge->getVertexFromTo($toVertex);

                // If the fromVertex already has a path
                if (isset($totalCostOfCheapestPathTo[$fromVertex->getId()])) {
                    // New possible costs of this path
                    $newCost = $totalCostOfCheapestPathTo[$fromVertex->getId()] + $edge->getWeight();
                    if (\is_infinite($newCost)) {
                        $newCost = $edge->getWeight() + 0;
                    }

                    // No path has been found yet
                    if (!isset($totalCostOfCheapestPathTo[$toVertex->getId()])
                            // OR this path is cheaper than the old path
                            || $totalCostOfCheapestPathTo[$toVertex->getId()] > $newCost){

                        $changed = $toVertex;
                        $totalCostOfCheapestPathTo[$toVertex->getId()] = $newCost;
                        $predecessorVertexOfCheapestPathTo[$toVertex->getId()] = $fromVertex;
                    }
                }
            }
        }

        return $changed;
    }

    /**
     * Calculate the Moore-Bellman-Ford-Algorithm and get all edges on shortest path for this vertex
     *
     * @return Edges
     * @throws NegativeCycleException if there is a negative cycle
     */
    public function getEdges()
    {
        // start node distance, add placeholder weight
        $totalCostOfCheapestPathTo  = array($this->vertex->getId() => INF);

        // predecessor
        $predecessorVertexOfCheapestPathTo  = array($this->vertex->getId() => $this->vertex);

        // the usal algorithm says we repeat (n-1) times.
        // but because we also want to check for loop edges on the start vertex,
        // we have to add an additional step:
        $numSteps = \count($this->vertex->getGraph()->getVertices());
        $edges = $this->vertex->getGraph()->getEdges();
        $changed = true;

        for ($i = 0; $i < $numSteps && $changed; ++$i) {
            $changed = $this->bigStep($edges, $totalCostOfCheapestPathTo, $predecessorVertexOfCheapestPathTo);
        }

        // no cheaper edge to start vertex found => remove placeholder weight
        if ($totalCostOfCheapestPathTo[$this->vertex->getId()] === INF) {
            unset($predecessorVertexOfCheapestPathTo[$this->vertex->getId()]);
        }

        // algorithm is done, build graph
        $returnEdges = $this->getEdgesCheapestPredecesor($predecessorVertexOfCheapestPathTo);

        // Check for negative cycles (only if last step didn't already finish anyway)
        // something is still changing...
        if ($changed && $changed = $this->bigStep($edges, $totalCostOfCheapestPathTo, $predecessorVertexOfCheapestPathTo)) {
            $cycle = Walk::factoryCycleFromPredecessorMap($predecessorVertexOfCheapestPathTo, $changed, Edges::ORDER_WEIGHT);
            throw new NegativeCycleException('Negative cycle found', 0, NULL, $cycle);
        }

        return $returnEdges;
    }

    /**
     * get negative cycle
     *
     * @return Walk
     * @throws UnderflowException if there's no negative cycle
     */
    public function getCycleNegative()
    {
        try {
            $this->getEdges();
        } catch (NegativeCycleException $e) {
            return $e->getCycle();
        }
        throw new UnderflowException('No cycle found');
    }
}
