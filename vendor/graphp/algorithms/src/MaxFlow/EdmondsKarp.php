<?php

namespace Graphp\Algorithms\MaxFlow;

use Fhaculty\Graph\Edge\Directed as EdgeDirected;
use Fhaculty\Graph\Exception\InvalidArgumentException;
use Fhaculty\Graph\Exception\OutOfBoundsException;
use Fhaculty\Graph\Exception\UnderflowException;
use Fhaculty\Graph\Exception\UnexpectedValueException;
use Fhaculty\Graph\Graph;
use Fhaculty\Graph\Set\Edges;
use Fhaculty\Graph\Vertex;
use Graphp\Algorithms\Base;
use Graphp\Algorithms\ResidualGraph;
use Graphp\Algorithms\ShortestPath\BreadthFirst;

class EdmondsKarp extends Base
{
    /**
     * @var Vertex
     */
    private $startVertex;

    /**
     * @var Vertex
     */
    private $destinationVertex;

    /**
     * @param Vertex $startVertex       the vertex where the flow search starts
     * @param Vertex $destinationVertex the vertex where the flow search ends (destination)
     */
    public function __construct(Vertex $startVertex, Vertex $destinationVertex)
    {
        if ($startVertex === $destinationVertex) {
            throw new InvalidArgumentException('Start and destination must not be the same vertex');
        }
        if ($startVertex->getGraph() !== $destinationVertex->getGraph()) {
            throw new InvalidArgumentException('Start and target vertex have to be in the same graph instance');
        }
        $this->startVertex = $startVertex;
        $this->destinationVertex = $destinationVertex;
    }

    /**
     * Returns max flow graph
     *
     * @return Graph
     * @throws UnexpectedValueException for undirected edges
     */
    public function createGraph()
    {
        $graphResult = $this->startVertex->getGraph()->createGraphClone();

        // initialize null flow and check edges
        foreach ($graphResult->getEdges() as $edge) {
            if (!($edge instanceof EdgeDirected)) {
                throw new UnexpectedValueException('Undirected edges not supported for edmonds karp');
            }
            $edge->setFlow(0);
        }

        $idA = $this->startVertex->getId();
        $idB = $this->destinationVertex->getId();

        do {
            // Generate new residual graph and repeat
            $residualAlgorithm = new ResidualGraph($graphResult);
            $graphResidual = $residualAlgorithm->createGraph();

            // 1. Search _shortest_ (number of hops and cheapest) path from s -> t
            $alg = new BreadthFirst($graphResidual->getVertex($idA));
            try {
                $pathFlow = $alg->getWalkTo($graphResidual->getVertex($idB));
            } catch (OutOfBoundsException $e) {
                $pathFlow = NULL;
            }

            // If path exists add the new flow to graph
            if ($pathFlow) {
                // 2. get max flow from path
                $maxFlowValue = $pathFlow->getEdges()->getEdgeOrder(Edges::ORDER_CAPACITY)->getCapacity();

                // 3. add flow to path
                foreach ($pathFlow->getEdges() as $edge) {
                    // try to look for forward edge to increase flow
                    try {
                        $originalEdge = $graphResult->getEdgeClone($edge);
                        $originalEdge->setFlow($originalEdge->getFlow() + $maxFlowValue);
                    // forward edge not found, look for back edge to decrease flow
                    } catch (UnderflowException $e) {
                        $originalEdge = $graphResult->getEdgeCloneInverted($edge);
                        $originalEdge->setFlow($originalEdge->getFlow() - $maxFlowValue);
                    }
                }
            }

        // repeat while we still finds paths with residual capacity to add flow to
        } while ($pathFlow);

        return $graphResult;
    }

    /**
     * Returns max flow value
     *
     * @return float
     */
    public function getFlowMax()
    {
        $resultGraph = $this->createGraph();

        $start = $resultGraph->getVertex($this->startVertex->getId());
        $maxFlow = 0;
        foreach ($start->getEdgesOut() as $edge) {
            $maxFlow = $maxFlow + $edge->getFlow();
        }

        return $maxFlow;
    }
}
