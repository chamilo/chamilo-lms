<?php

namespace Graphp\Algorithms\Search;

use Fhaculty\Graph\Set\Vertices;

class BreadthFirst extends Base
{
    /**
     * @param int $maxDepth
     * @return Vertices
     */
    public function getVertices($maxDepth = PHP_INT_MAX)
    {
        $queue = array($this->vertex);
        // to not add vertices twice in array visited
        $mark = array($this->vertex->getId() => true);
        // visited vertices
        $visited = array();

        // keep track of depth
        $currentDepth = 0;
        $nodesThisLevel = 1;
        $nodesNextLevel = 0;

        do {
            // get first from queue
            $t = \array_shift($queue);
            // save as visited
            $visited[$t->getId()] = $t;

            // get next vertices
            $children = $this->getVerticesAdjacent($t);

            // track depth
            $nodesNextLevel = $children->count();
            if (--$nodesThisLevel === 0) {
                if (++$currentDepth > $maxDepth) {
                    return new Vertices($visited);
                }
                $nodesThisLevel = $nodesNextLevel;
                $nodesNextLevel = 0;
            }

            // process next vertices
            foreach ($children->getMap() as $id => $vertex) {
                // if not "touched" before
                if (!isset($mark[$id])) {
                    // add to queue
                    $queue[] = $vertex;
                    // and mark
                    $mark[$id] = true;
                }
            }

            // until queue is empty
        } while ($queue);

        return new Vertices($visited);
    }
}
