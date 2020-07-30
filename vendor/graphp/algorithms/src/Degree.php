<?php

namespace Graphp\Algorithms;

use Fhaculty\Graph\Exception\UnderflowException;
use Fhaculty\Graph\Exception\UnexpectedValueException;
use Fhaculty\Graph\Vertex;

/**
 * Basic algorithms for working with the degrees of Graphs.
 *
 * The degree (or valency) of a Vertex of a Graph is the number of Edges
 * incident to the Vertex, with Loops counted twice.
 *
 * @link http://en.wikipedia.org/wiki/Degree_%28graph_theory%29
 * @link http://en.wikipedia.org/wiki/Regular_graph
 */
class Degree extends BaseGraph
{
    /**
     * get degree for k-regular-graph (only if each vertex has the same degree)
     *
     * @return int
     * @throws UnderflowException       if graph is empty
     * @throws UnexpectedValueException if graph is not regular (i.e. vertex degrees are not equal)
     * @uses self::getDegreeVertex()
     * @see self::isRegular()
     */
    public function getDegree()
    {
        // get initial degree of any start vertex to compare others to
        $degree = $this->getDegreeVertex($this->graph->getVertices()->getVertexFirst());

        foreach ($this->graph->getVertices() as $vertex) {
            assert($vertex instanceof Vertex);
            $i = $this->getDegreeVertex($vertex);

            if ($i !== $degree) {
                throw new UnexpectedValueException('Graph is not k-regular (vertex degrees differ)');
            }
        }

        return $degree;
    }

    /**
     * get minimum degree of vertices
     *
     * @return int
     * @throws UnderflowException       if graph is empty
     * @uses Vertices::getVertexOrder()
     * @uses self::getDegreeVertex()
     */
    public function getDegreeMin()
    {
        return $this->getDegreeVertex($this->graph->getVertices()->getVertexOrder(array($this, 'getDegreeVertex')));
    }

    /**
     * get maximum degree of vertices
     *
     * @return int
     * @throws UnderflowException       if graph is empty
     * @uses Vertices::getVertexOrder()
     * @uses self::getDegreeVertex()
     */
    public function getDegreeMax()
    {
        return $this->getDegreeVertex($this->graph->getVertices()->getVertexOrder(array($this, 'getDegreeVertex'), true));
    }

    /**
     * checks whether this graph is regular, i.e. each vertex has the same indegree/outdegree
     *
     * @return bool
     * @uses self::getDegree()
     */
    public function isRegular()
    {
        // an empty graph is considered regular
        if ($this->graph->getVertices()->isEmpty()) {
            return true;
        }
        try {
            $this->getDegree();

            return true;
        } catch (UnexpectedValueException $ignore) { }

        return false;
    }

    /**
     * checks whether the indegree of every vertex equals its outdegree
     *
     * @return bool
     * @uses self::getDegreeInVertex()
     * @uses self::getDegreeOutVertex()
     */
    public function isBalanced()
    {
        foreach ($this->graph->getVertices() as $vertex) {
            if ($this->getDegreeInVertex($vertex) !== $this->getDegreeOutVertex($vertex)) {
                return false;
            }
        }

        return true;
    }

    /**
     * checks whether this vertex is a source, i.e. its indegree is zero
     *
     * @param Vertex $vertex
     * @return bool
     * @uses Edge::hasVertexTarget()
     * @see self::getDegreeInVertex()
     */
    public function isVertexSource(Vertex $vertex)
    {
        foreach ($vertex->getEdges() as $edge) {
            if ($edge->hasVertexTarget($vertex)) {
                return false;
            }
        }

        // reach this point: no edge to this vertex
        return true;
    }

    /**
     * checks whether this vertex is a sink, i.e. its outdegree is zero
     *
     * @param Vertex $vertex
     * @return bool
     * @uses Edge::hasVertexStart()
     * @see self::getDegreeOutVertex()
     */
    public function isVertexSink(Vertex $vertex)
    {
        foreach ($vertex->getEdges() as $edge) {
            if ($edge->hasVertexStart($vertex)) {
                return false;
            }
        }

        // reach this point: no edge away from this vertex
        return true;
    }

    /**
     * get degree of this vertex (total number of edges)
     *
     * vertex degree counts the total number of edges attached to this vertex
     * regardless of whether they're directed or not. loop edges are counted
     * twice as both start and end form a 'line' to the same vertex.
     *
     * @param Vertex $vertex
     * @return int
     * @see self::getDegreeInVertex()
     * @see self::getDegreeOutVertex()
     */
    public function getDegreeVertex(Vertex $vertex)
    {
        return \count($vertex->getEdges());
    }

    /**
     * check whether this vertex is isolated (i.e. has no edges attached)
     *
     * @param Vertex $vertex
     * @return bool
     */
    public function isVertexIsolated(Vertex $vertex)
    {
        return $vertex->getEdges()->isEmpty();
    }

    /**
     * get indegree of this vertex (number of edges TO this vertex)
     *
     * @param Vertex $vertex
     * @return int
     * @uses Edge::hasVertexTarget()
     * @see self::getDegreeVertex()
     */
    public function getDegreeInVertex($vertex)
    {
        $n = 0;
        foreach ($vertex->getEdges() as $edge) {
            if ($edge->hasVertexTarget($vertex)) {
                ++$n;
            }
        }

        return $n;
    }

    /**
     * get outdegree of this vertex (number of edges FROM this vertex TO other vertices)
     *
     * @param Vertex $vertex
     * @return int
     * @uses Edge::hasVertexStart()
     * @see self::getDegreeVertex()
     */
    public function getDegreeOutVertex(Vertex $vertex)
    {
        $n = 0;
        foreach ($vertex->getEdges() as $edge) {
            if ($edge->hasVertexStart($vertex)) {
                ++$n;
            }
        }

        return $n;
    }
}
