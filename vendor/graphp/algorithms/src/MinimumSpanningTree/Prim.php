<?php

namespace Graphp\Algorithms\MinimumSpanningTree;

use Fhaculty\Graph\Exception\UnexpectedValueException;
use Fhaculty\Graph\Edge\Directed as EdgeDirected;
use Fhaculty\Graph\Set\Edges;
use Fhaculty\Graph\Vertex;
use \SplPriorityQueue;
use \Exception;

class Prim extends Base
{
    /**
     * @var Vertex
     */
    private $startVertex;

    public function __construct(Vertex $startVertex)
    {
        $this->startVertex = $startVertex;
    }

    /**
     *
     * @return Edges
     */
    public function getEdges()
    {
        // Initialize algorithm
        $edgeQueue = new SplPriorityQueue();
        $vertexCurrent = $this->startVertex;

        $markInserted = array();
        $returnEdges = array();

        // iterate n-1 times (per definition, resulting MST MUST have n-1 edges)
        for ($i = 0, $n = count($this->startVertex->getGraph()->getVertices()) - 1; $i < $n; ++$i) {
            $markInserted[$vertexCurrent->getId()] = true;

            // get unvisited vertex of the edge and add edges from new vertex
            // Add all edges from $currentVertex to priority queue
            $this->addEdgesSorted($vertexCurrent->getEdges(), $edgeQueue);

            do {
                try {
                    // Get next cheapest edge
                    $cheapestEdge = $edgeQueue->extract();
                    /* @var $cheapestEdge EdgeDirected */
                } catch (Exception $e) {
                    throw new UnexpectedValueException('Graph has more than one component', 0, $e);
                }

                // Check if edge is between unmarked and marked edge

                $vertices = $cheapestEdge->getVertices();
                $vertexA  = $vertices->getVertexFirst();
                $vertexB  = $vertices->getVertexLast();

            // Edge is between marked and unmared vertex
            } while (!(isset($markInserted[$vertexA->getId()]) XOR isset($markInserted[$vertexB->getId()])));

            // Cheapest Edge found, add edge to returnGraph
            $returnEdges []= $cheapestEdge;

            // set current vertex for next iteration in order to add its edges to queue
            if (isset($markInserted[$vertexA->getId()])) {
                $vertexCurrent = $vertexB;
            } else {
                $vertexCurrent = $vertexA;
            }
        }

        return new Edges($returnEdges);
    }

    protected function getGraph()
    {
        return $this->startVertex->getGraph();
    }
}
