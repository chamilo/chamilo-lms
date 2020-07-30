<?php

namespace Graphp\Algorithms;

use Fhaculty\Graph\Edge\Directed as EdgeDirected;
use Fhaculty\Graph\Exception\UnexpectedValueException;
use Fhaculty\Graph\Vertex;

/**
 * Basic algorithms for working with flow graphs
 *
 * A flow network (also known as a transportation network) is a directed graph
 * where each edge has a capacity and each edge receives a flow.
 *
 * @link http://en.wikipedia.org/wiki/Flow_network
 * @see Algorithm\Balance
 */
class Flow extends BaseDual
{
    /**
     * check if this graph has any flow set (any edge has a non-NULL flow)
     *
     * @return bool
     * @uses Edge::getFlow()
     */
    public function hasFlow()
    {
        foreach ($this->set->getEdges() as $edge) {
            if ($edge->getFlow() !== NULL) {
                return true;
            }
        }

        return false;
    }

    /**
     * Calculates the flow for this Vertex: sum(outflow) - sum(inflow)
     *
     * Usually, vertices should have a resulting flow of 0: The sum of flows
     * entering a vertex must equal the sum of flows leaving a vertex. If the
     * resulting flow is < 0, this vertex is considered a sink (i.e. there's
     * more flow into this vertex). If the resulting flow is > 0, this vertex
     * is considered a "source" (i.e. there's more flow leaving this vertex).
     *
     * @param Vertex $vertex
     * @return float
     * @throws UnexpectedValueException if they are undirected edges
     * @see Vertex::getBalance()
     * @uses Vertex::getEdges()
     * @uses Edge::getFlow()
     */
    public function getFlowVertex(Vertex $vertex)
    {
        $sumOfFlow = 0;

        foreach ($vertex->getEdges() as $edge) {
            if (!($edge instanceof EdgeDirected)) {
                throw new UnexpectedValueException("TODO: undirected edges not suported yet");
            }

            // edge is an outgoing edge of this vertex
            if ($edge->hasVertexStart($vertex)) {
                // flowing out (flow is "pointing away")
                $sumOfFlow += $edge->getFlow();
                // this is an ingoing edge
            } else {
                // flowing in
                $sumOfFlow -= $edge->getFlow();
            }
        }

        return $sumOfFlow;
    }

    public function getBalance()
    {
        $balance = 0;
        // Sum for all vertices of value
        foreach ($this->set->getVertices() as $vertex) {
            $balance += $vertex->getBalance();
        }

        return $balance;
    }

    /**
     * check if the current flow is balanced (aka "balanced flow" or "b-flow")
     *
     * a flow is considered balanced if each edge's current flow does not exceed its
     * maximum capacity (which is always guaranteed due to the implementation
     * of Edge::setFlow()) and each vertices' flow (i.e. outflow-inflow) equals
     * its balance.
     *
     * checking whether the FLOW is balanced is not to be confused with checking
     * whether the GRAPH is balanced (see Graph::isBalanced() instead)
     *
     * @return bool
     * @see Degree::isBalanced() if you merely want to check indegree=outdegree
     * @uses self::getFlowVertex()
     * @uses Vertex::getBalance()
     */
    public function isBalancedFlow()
    {
        // no need to check for each edge: flow <= capacity (setters already check that)
        // check for each vertex: outflow-inflow = balance
        foreach ($this->set->getVertices() as $vertex) {
            if ($this->getFlowVertex($vertex) !== $vertex->getBalance()) {
                return false;
            }
        }

        return true;
    }
}
