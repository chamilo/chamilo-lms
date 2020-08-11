<?php

namespace Graphp\Algorithms\ShortestPath;

use Fhaculty\Graph\Exception\UnexpectedValueException;
use Fhaculty\Graph\Set\Edges;
use SplPriorityQueue;

/**
 * Commonly used Dijkstra's shortest path algorithm
 *
 * This is asymptotically the fastest known single-source shortest-path
 * algorithm for arbitrary graphs with non-negative weights. If your Graph
 * contains an Edge with negative weight, if will throw an
 * UnexpectedValueException. Consider using (the slower) MooreBellmanFord
 * algorithm instead.
 *
 * @link http://en.wikipedia.org/wiki/Dijkstra%27s_algorithm
 * @see MooreBellmanFord
 */
class Dijkstra extends Base
{
    /**
     * get all edges on shortest path for this vertex
     *
     * @return Edges
     * @throws UnexpectedValueException when encountering an Edge with negative weight
     */
    public function getEdges()
    {
        $totalCostOfCheapestPathTo  = Array();
        // start node distance
        $totalCostOfCheapestPathTo[$this->vertex->getId()] = INF;

        // just to get the cheapest vertex in the correct order
        $cheapestVertex = new SplPriorityQueue();
        $cheapestVertex->insert($this->vertex, 0);

        // predecessor
        $predecesVertexOfCheapestPathTo  = Array();
        $predecesVertexOfCheapestPathTo[$this->vertex->getId()] = $this->vertex;

        // mark vertices when their cheapest path has been found
        $usedVertices  = Array();

        $isFirst = true;

        // Repeat until all vertices have been marked
        $totalCountOfVertices = \count($this->vertex->getGraph()->getVertices());
        for ($i = 0; $i < $totalCountOfVertices; ++$i) {
            $currentVertex = NULL;
            $currentVertexId = NULL;
            $isEmpty = false;
            do {
                // if the priority queue is empty there are isolated vertices, but the algorithm visited all other vertices
                if ($cheapestVertex->isEmpty()) {
                    $isEmpty = true;
                    break;
                }
                // Get cheapest unmarked vertex
                $currentVertex = $cheapestVertex->extract();
                $currentVertexId = $currentVertex->getId();
            // Vertices can be in the priority queue multiple times, with different path costs (if vertex is already marked, this is an old unvalid entry)
            } while (isset($usedVertices[$currentVertexId]));

            // catch "algorithm ends" condition
            if ($isEmpty) {
                break;
            }

            if ($isFirst) {
                $isFirst = false;
            } else {
                // mark this vertex
                $usedVertices[$currentVertexId] = true;
            }

            // check for all edges of current vertex if there is a cheaper path (or IN OTHER WORDS: Add reachable nodes from currently added node and refresh the current possible distances)
            foreach ($currentVertex->getEdgesOut() as $edge) {
                $weight = $edge->getWeight();
                if ($weight < 0) {
                    throw new UnexpectedValueException('Djkstra not supported for negative weights - Consider using MooreBellmanFord');
                }

                $targetVertex = $edge->getVertexToFrom($currentVertex);
                $targetVertexId = $targetVertex->getId();

                // if the targetVertex is marked, the cheapest path for this vertex has already been found (no negative edges) {
                if (!isset($usedVertices[$targetVertexId])) {
                    // calculate new cost to vertex
                    $newCostsToTargetVertex = $totalCostOfCheapestPathTo[$currentVertexId] + $weight;
                    if (\is_infinite($newCostsToTargetVertex)) {
                        $newCostsToTargetVertex = $weight;
                    }

                    if ((!isset($predecesVertexOfCheapestPathTo[$targetVertexId]))
                           // is the new path cheaper?
                           || $totalCostOfCheapestPathTo[$targetVertexId] > $newCostsToTargetVertex){

                        // Not an update, just an new insert with lower cost
                        $cheapestVertex->insert($targetVertex, - $newCostsToTargetVertex);
                                                                                                    // so the lowest cost will be extraced first
                                                                                                    // and higher cost will be skipped during extraction

                        // update/set costs found with the new connection
                        $totalCostOfCheapestPathTo[$targetVertexId] = $newCostsToTargetVertex;
                        // update/set predecessor vertex from the new connection
                        $predecesVertexOfCheapestPathTo[$targetVertexId] = $currentVertex;
                    }
                }
            }
        }

        if ($totalCostOfCheapestPathTo[$this->vertex->getId()] === INF) {
            unset($predecesVertexOfCheapestPathTo[$this->vertex->getId()]);
        }

        // algorithm is done, return resulting edges
        return $this->getEdgesCheapestPredecesor($predecesVertexOfCheapestPathTo);
    }
}
