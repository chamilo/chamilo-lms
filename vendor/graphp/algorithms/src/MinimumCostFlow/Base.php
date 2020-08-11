<?php

namespace Graphp\Algorithms\MinimumCostFlow;

use Graphp\Algorithms\BaseGraph;
use Graphp\Algorithms\Weight as AlgorithmWeight;
use Graphp\Algorithms\Flow as AlgorithmFlow;
use Fhaculty\Graph\Exception\UnderflowException;
use Fhaculty\Graph\Exception\UnexpectedValueException;
use Fhaculty\Graph\Graph;
use Fhaculty\Graph\Set\Edges;

abstract class Base extends BaseGraph
{
    /**
     * check if balance is okay and throw exception otherwise
     *
     * @return $this (chainable)
     * @throws UnexpectedValueException
     */
    protected function checkBalance()
    {
        $alg = new AlgorithmFlow($this->graph);
        $balance = $alg->getBalance();

        $tolerance = 0.000001;
        if ($balance >= $tolerance || $balance <= -$tolerance) {
            throw new UnexpectedValueException('The given graph is not balanced value is: ' . $balance);
        }

        return $this;
    }

    /**
     * helper used to add $newFlow to original edges of $clonedEdges in graph $resultGraph
     *
     * @param Graph  $resultGraph graph to look for original edges
     * @param Edges  $clonedEdges set of cloned edges to be modified
     * @param number $newFlow     flow to add
     * @uses Graph::getEdgeClone()
     * @uses Graph::getEdgeCloneInverted()
     * @uses Edge::getFlow()
     * @uses Edge::setFlow()
     */
    protected function addFlow(Graph $resultGraph, Edges $clonedEdges, $newFlow)
    {
        foreach ($clonedEdges as $clonedEdge) {
            try {
                // get edge from clone
                $edge = $resultGraph->getEdgeClone($clonedEdge);
                // add flow
                $edge->setFlow($edge->getFlow() + $newFlow);
            } catch (UnderflowException $ignore) {
                // if the edge doesn't exist => use the residual edge
                $edge = $resultGraph->getEdgeCloneInverted($clonedEdge);
                // remove flow
                $edge->setFlow($edge->getFlow() - $newFlow);
            }
        }
    }

    /**
     * calculate total weight along minimum-cost flow
     *
     * @return float
     * @uses self::createGraph()
     * @uses AlgorithmWeight::getWeightFlow()
     */
    public function getWeightFlow()
    {
        $alg = new AlgorithmWeight($this->createGraph());
        return $alg->getWeightFlow();
    }

    /**
     * create new resulting graph with minimum-cost flow on edges
     *
     * @return Graph
     * @throws UnexpectedValueException for undirected edges
     * @throws UnexpectedValueException if the graph has not enough capacity for the minimum-cost flow
     */
    abstract public function createGraph();
}
