<?php

namespace Graphp\Algorithms\Search;

use Fhaculty\Graph\Set\Vertices;

class DepthFirst extends Base
{
    /**
     * calculates an iterative depth-first search
     *
     * @return Vertices
     */
    public function getVertices()
    {
        $visited = array();
        $todo = array($this->vertex);
        while ($vertex = \array_shift($todo)) {
            if (!isset($visited[$vertex->getId()])) {
                $visited[$vertex->getId()] = $vertex;

                foreach (\array_reverse($this->getVerticesAdjacent($vertex)->getMap(), true) as $nextVertex) {
                    $todo[] = $nextVertex;
                }
            }
        }

        return new Vertices($visited);
    }
}
