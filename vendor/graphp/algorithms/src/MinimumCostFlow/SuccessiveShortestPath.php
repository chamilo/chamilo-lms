<?php

namespace Graphp\Algorithms\MinimumCostFlow;

use Fhaculty\Graph\Edge\Directed as EdgeDirected;
use Fhaculty\Graph\Exception\UnderflowException;
use Fhaculty\Graph\Exception\UnexpectedValueException;
use Fhaculty\Graph\Graph;
use Fhaculty\Graph\Set\Edges;
use Fhaculty\Graph\Vertex;
use Graphp\Algorithms\ResidualGraph;
use Graphp\Algorithms\ShortestPath\MooreBellmanFord as SpMooreBellmanFord;
use Graphp\Algorithms\Search\BreadthFirst as SearchBreadthFirst;

class SuccessiveShortestPath extends Base
{
    /**
     * @uses Graph::createGraphClone()
     * @uses ResidualGraph::createGraph()
     * @uses SpMooreBellmanFord::getEdgesTo(Vertex $targetVertex)
     * @see Base::createGraph()
     */
    public function createGraph()
    {
        $this->checkBalance();
        $resultGraph = $this->graph->createGraphClone();

        // initial balance to 0
        $vertices = $resultGraph->getVertices();
        foreach ($vertices as $vertex) {
            $vertex->setBalance(0);
        }

        // initial flow of edges
        $edges = $resultGraph->getEdges();
        foreach ($edges as $edge) {
            if (!($edge instanceof EdgeDirected)) {
                throw new UnexpectedValueException('Undirected edges are not supported for SuccessiveShortestPath');
            }

            // 0 if weight of edge is positive
            $flow = 0;

            // maximal flow if weight of edge is negative
            if ($edge->getWeight() < 0) {
                $flow = $edge->getCapacity();

                $startVertex = $edge->getVertexStart();
                $endVertex = $edge->getVertexEnd();

                // add balance to start- and end-vertex
                $this->addBalance($startVertex, $flow);
                $this->addBalance($endVertex, - $flow);
            }

            $edge->setFlow($flow);
        }

        // return or Exception inside this while
        while (true) {
            // create residual graph
            $algRG = new ResidualGraph($resultGraph);
            $residualGraph = $algRG->createGraph();

            // search for a source
            try {
                $sourceVertex = $this->getVertexSource($residualGraph);
            } catch (UnderflowException $ignore) {
                // no source is found => minimum-cost flow is found
                break;
            }

            // search for reachable target sink from this source
            try {
                $targetVertex = $this->getVertexSink($sourceVertex);
            } catch (UnderflowException $e) {
                // no target found => network does not have enough capacity
                throw new UnexpectedValueException('The graph has not enough capacity for the minimum-cost flow', 0, $e);
            }

            // calculate shortest path between source- and target-vertex
            $algSP = new SpMooreBellmanFord($sourceVertex);
            $edgesOnFlow = $algSP->getEdgesTo($targetVertex);

            // calculate the maximal possible flow
            // new flow is the maximal possible flow for this path
            $newflow    =    $this->graph->getVertex($sourceVertex->getId())->getBalance() - $sourceVertex->getBalance();
            $targetFlow = - ($this->graph->getVertex($targetVertex->getId())->getBalance() - $targetVertex->getBalance());

            // get minimum of source and target
            if ($targetFlow < $newflow) {
                $newflow = $targetFlow;
            }

            // get minimum of capacity remaining on path
            $minCapacity = $edgesOnFlow->getEdgeOrder(Edges::ORDER_CAPACITY_REMAINING)->getCapacityRemaining();
            if ($minCapacity < $newflow) {
                $newflow = $minCapacity;
            }

            // add the new flow to the path
            assert($newflow !== null);
            $this->addFlow($resultGraph, $edgesOnFlow, $newflow);

            // add balance to source and remove for the target sink
            $oriSourceVertex = $resultGraph->getVertex($sourceVertex->getId());
            $oriTargetVertex = $resultGraph->getVertex($targetVertex->getId());

            $this->addBalance($oriSourceVertex, $newflow);
            $this->addBalance($oriTargetVertex, - $newflow);
        }

        return $resultGraph;
    }

    /**
     * @param  Graph     $graph
     * @return Vertex a source vertex in the given graph
     * @throws UnderflowException if there is no left source vertex
     */
    private function getVertexSource(Graph $graph)
    {
        foreach ($graph->getVertices()->getMap() as $vid => $vertex) {
            if ($this->graph->getVertex($vid)->getBalance() - $vertex->getBalance() > 0) {
                return $vertex;
            }
        }
        throw new UnderflowException('No source vertex found in graph');
    }

    /**
     * @param  Vertex    $source
     * @return Vertex a sink-vertex that is reachable from the source
     * @throws UnderflowException if there is no reachable sink vertex
     * @uses BreadthFirst::getVertices()
     */
    private function getVertexSink(Vertex $source)
    {
        // search for reachable Vertices
        $algBFS = new SearchBreadthFirst($source);

        foreach ($algBFS->getVertices()->getMap() as $vid => $vertex) {
            if ($this->graph->getVertex($vid)->getBalance() - $vertex->getBalance() < 0) {
                return $vertex;
            }
        }
        throw new UnderflowException('No sink vertex connected to given source vertex found');
    }

    private function addBalance(Vertex $vertex, $balance)
    {
        $vertex->setBalance($vertex->getBalance() + $balance);
    }
}
