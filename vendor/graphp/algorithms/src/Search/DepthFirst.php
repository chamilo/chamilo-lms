<?php

namespace Graphp\Algorithms\Search;

use Fhaculty\Graph\Vertex;
use Fhaculty\Graph\Set\Vertices;

class DepthFirst extends Base
{
    /**
     *
     * calculates the recursive algorithm
     *
     * fills $this->visitedVertices
     *
     * @param Vertex $vertex
     */
    private function recursiveDepthFirstSearch(Vertex $vertex, array & $visitedVertices)
    {
        // If I didn't visited this vertex before
        if (!isset($visitedVertices[$vertex->getId()])) {
            // Add Vertex to already visited vertices
            $visitedVertices[$vertex->getId()] = $vertex;

            // Get next vertices
            $nextVertices = $vertex->getVerticesEdgeTo();

            foreach ($nextVertices as $nextVertix) {
                // recursive call for next vertices
                $this->recursiveDepthFirstSearch($nextVertix, $visitedVertices);
            }
        }
    }

    private function iterativeDepthFirstSearch(Vertex $vertex)
    {
        $visited = array();
        $todo = array($vertex);
        while ($vertex = array_shift($todo)) {
            if (!isset($visited[$vertex->getId()])) {
                $visited[$vertex->getId()] = $vertex;

                foreach (array_reverse($this->getVerticesAdjacent($vertex)->getMap(), true) as $vid => $nextVertex) {
                    $todo[] = $nextVertex;
                }
            }
        }

        return new Vertices($visited);
    }

    /**
     * calculates a recursive depth-first search
     *
     * @return Vertices
     */
    public function getVertices()
    {
        return $this->iterativeDepthFirstSearch($this->vertex);

        $visitedVertices = array();
        $this->recursiveDepthFirstSearch($this->vertex, $visitedVertices);

        return $visitedVertices;
    }
}
