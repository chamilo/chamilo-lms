<?php

namespace Graphp\Algorithms\Search;

use Fhaculty\Graph\Vertex;
use Fhaculty\Graph\Graph;
use Fhaculty\Graph\Set\Vertices;

class BreadthFirst extends Base
{
    /**
     *
     * @return Vertices
     */
    public function getVertices()
    {
        $queue = array($this->vertex);
        // to not add vertices twice in array visited
        $mark = array($this->vertex->getId() => true);
        // visited vertices
        $visited = array();

        do {
            // get first from queue
            $t = array_shift($queue);
            // save as visited
            $visited[$t->getId()]= $t;

            // get next vertices
            foreach ($this->getVerticesAdjacent($t)->getMap() as $id => $vertex) {
                // if not "touched" before
                if (!isset($mark[$id])) {
                    // add to queue
                    $queue[] = $vertex;
                    // and mark
                    $mark[$id] = true;
                }
            }

        // untill queue is empty
        } while ($queue);

        return new Vertices($visited);
    }
}
