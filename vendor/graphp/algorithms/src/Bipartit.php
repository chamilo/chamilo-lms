<?php

namespace Graphp\Algorithms;

use Fhaculty\Graph\Exception\UnexpectedValueException;
use Fhaculty\Graph\Graph;

class Bipartit extends BaseGraph
{
    /**
     * check whether this graph is bipartit
     *
     * @return bool
     * @uses AlgorithmBipartit::getColors()
     */
    public function isBipartit()
    {
        try {
            $this->getColors();

            return true;
        } catch (UnexpectedValueException $ignore) { }

        return false;
    }

    /**
     * checks whether the input graph's vertex groups are a valid bipartition
     *
     * @return bool
     * @uses AlgorithmGroups::isBipartit()
     */
    public function isBipartitGroups()
    {
        $alg = new Groups($this->graph);

        return $alg->isBipartit();
    }

    /**
     * get map of vertex ID to vertex color
     *
     * @return int[]
     * @throws UnexpectedValueException if graph is not bipartit
     * @uses AlgorithmBipartit::checkVertex() for every vertex not already colored
     */
    public function getColors()
    {
        $colors = array();

        // get color for each vertex
        foreach ($this->graph->getVertices()->getMap() as $vid => $startVertex) {
            if (!isset($colors[$vid])) {
                $queue = array($startVertex);
                // initialize each components color
                $colors[$vid] = 0;

                // breadth search all vertices in same component
                do {
                    // next vertex in color
                    $vertex = \array_shift($queue);
                    $color = $colors[$vertex->getId()];
                    $nextColor = 1-$color;

                    // scan all vertices connected to this vertex
                    foreach ($vertex->getVerticesEdge()->getMap() as $vid => $nextVertex) {
                        // color unknown, so expect next color for this vertex
                        if (!isset($colors[$vid])) {
                            $colors[$vid] = $nextColor;
                            $queue[] = $nextVertex;
                        // color is known but differs => can not be bipartit
                        } elseif ($colors[$vid] !== $nextColor) {
                            throw new UnexpectedValueException('Graph is not bipartit');
                        }
                    }
                } while ($queue);
            }
        }

        return $colors;
    }

    /**
     * get groups of vertices per color
     *
     * @return array[] array of arrays of vertices
     */
    public function getColorVertices()
    {
        $colors = $this->getColors();
        $ret = array(0 => array(), 1 => array());

        foreach ($this->graph->getVertices()->getMap() as $vid => $vertex) {
            $ret[$colors[$vid]][$vid] = $vertex;
        }

        return $ret;
    }

    /**
     * create new graph with valid groups set according to bipartition colors
     *
     * @return Graph
     * @throws UnexpectedValueException if graph is not bipartit
     * @uses AlgorithmBipartit::getColors()
     * @uses Graph::createGraphClone()
     * @uses Vertex::setGroup()
     */
    public function createGraphGroups()
    {
        $colors = $this->getColors();

        $graph = $this->graph->createGraphClone();
        foreach ($graph->getVertices()->getMap() as $vid => $vertex) {
            $vertex->setGroup($colors[$vid]);
        }

        return $graph;
    }
}
