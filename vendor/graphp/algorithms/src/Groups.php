<?php

namespace Graphp\Algorithms;

use Fhaculty\Graph\Set\Vertices;
use Fhaculty\Graph\Vertex;

class Groups extends BaseGraph
{
    /**
     * count total number of different groups assigned to vertices
     *
     * @return int
     * @uses AlgorithmGroups::getGroups()
     */
    public function getNumberOfGroups()
    {
        return \count($this->getGroups());
    }

    /**
     * checks whether the input graph's vertex groups are a valid bipartition
     *
     * @return bool
     * @see AlgorithmBipartit() if you do NOT want to take vertex groups into consideration
     * @uses AlgorithmGroups::getNumberOfGroups()
     * @uses Vertex::getGroup()
     */
    public function isBipartit()
    {
        // graph has to contain exactly 2 groups
        if ($this->getNumberOfGroups() !== 2) {
            return false;
        }

        // for each vertex
        foreach ($this->graph->getVertices() as $vertex) {
            // get current group
            $group = $vertex->getGroup();
            // for every neighbor vertex
            foreach ($vertex->getVerticesEdge() as $vertexNeighbor) {
                // vertex group must be other group
                if ($vertexNeighbor->getGroup() === $group) {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * get vector of all group numbers
     *
     * @return int[]
     * @uses Vertex::getGroup()
     */
    public function getGroups()
    {
        $groups = array();
        foreach ($this->graph->getVertices() as $vertex) {
            assert($vertex instanceof Vertex);
            $groups[$vertex->getGroup()] = true;
        }

        return \array_keys($groups);
    }

    /**
     * get set of all Vertices in the given group
     *
     * @param  int      $group
     * @return Vertices
     * @uses Vertex::getGroup()
     */
    public function getVerticesGroup($group)
    {
        $vertices = array();
        foreach ($this->graph->getVertices()->getMap() as $vid => $vertex) {
            if ($vertex->getGroup() === $group) {
                $vertices[$vid] = $vertex;
            }
        }

        return new Vertices($vertices);
    }
}
