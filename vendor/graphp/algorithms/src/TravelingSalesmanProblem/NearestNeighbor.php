<?php

namespace Graphp\Algorithms\TravelingSalesmanProblem;

use Fhaculty\Graph\Exception\UnexpectedValueException;
use Fhaculty\Graph\Set\Edges;
use Fhaculty\Graph\Vertex;
use SplPriorityQueue;

class NearestNeighbor extends Base
{
    /**
     * @var Vertex
     */
    private $vertex;

    public function __construct(Vertex $startVertex)
    {
         $this->vertex = $startVertex;
    }

    protected function getVertexStart()
    {
        return $this->vertex;
    }

    protected function getGraph()
    {
        return $this->vertex->getGraph();
    }

    /**
     * @return Edges
     */
    public function getEdges()
    {
        $returnEdges = array();

        $n = \count($this->vertex->getGraph()->getVertices());

        $vertex = $nextVertex = $this->vertex;
        $visitedVertices = array($vertex->getId() => true);

        for ($i = 0; $i < $n - 1; ++$i,
                                    // n-1 steps (spanning tree)
                                    $vertex = $nextVertex) {

            // get all edges from the aktuel vertex
            $edges = $vertex->getEdgesOut();

            $sortedEdges = new SplPriorityQueue();

            // sort the edges
            foreach ($edges as $edge) {
                $sortedEdges->insert($edge, - $edge->getWeight());
            }

            // Untill first is found: get cheepest edge
            foreach ($sortedEdges as $edge) {

                // Get EndVertex of this edge
                $nextVertex = $edge->getVertexToFrom($vertex);

                // is unvisited
                if (!isset($visitedVertices[$nextVertex->getId()])) {
                    break;
                }
            }

            // check if there is a way i can use
            if (isset($visitedVertices[$nextVertex->getId()])) {
                throw new UnexpectedValueException('Graph is not complete - can\'t find an edge to unconnected vertex');
            }

            $visitedVertices[$nextVertex->getId()] = TRUE;

            // clone edge in new Graph
            assert(isset($edge));
            $returnEdges[] = $edge;

        }

        // check if there is a way from end edge to start edge
        // get first connecting edge
        // connect the last vertex with the start vertex
        $returnEdges[] = $vertex->getEdgesTo($this->vertex)->getEdgeFirst();

        return new Edges($returnEdges);
    }
}
