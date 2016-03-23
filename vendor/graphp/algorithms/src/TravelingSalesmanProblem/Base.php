<?php

namespace Graphp\Algorithms\TravelingSalesmanProblem;

use Fhaculty\Graph\Walk;
use Fhaculty\Graph\Vertex;
use Fhaculty\Graph\Edge\Base as Edge;
use Fhaculty\Graph\Set\Edges;
use Graphp\Algorithms\Base as AlgorithmBase;

abstract class Base extends AlgorithmBase
{
    /**
     * get resulting graph with the (first) best circle of edges connecting all vertices
     *
     * @throws Exception on error
     * @return Graph
     * @uses Tsp::getGraph()
     * @uses AlgorithmTsp::getEdges()
     * @uses Graph::createGraphCloneEdges()
     */
    public function createGraph()
    {
        return $this->getGraph()->createGraphCloneEdges($this->getEdges());
    }

    /**
     * get graph this algorithm operates on
     *
     * @return Graph
     */
    abstract protected function getGraph();

    /**
     * get start vertex this algorithm starts on
     *
     * @return Vertex
     */
    abstract protected function getVertexStart();

    /**
     * get (first) best circle connecting all vertices
     *
     * @return Walk
     * @uses AlgorithmTsp::getEdges()
     * @uses AlgorithmTsp::getVertexStart()
     * @uses Walk::factoryCycleFromEdges()
     */
    public function getCycle()
    {
        return Walk::factoryCycleFromEdges($this->getEdges(), $this->getVertexStart());
    }

    public function getWeight()
    {
        $weight = 0;
        foreach ($this->getEdges() as $edge) {
            $weight += $edge->getWeight();
        }

        return $weight;
    }

    /**
     * get array of edges connecting all vertices in a circle
     *
     * @return Edges
     */
    abstract public function getEdges();
}
