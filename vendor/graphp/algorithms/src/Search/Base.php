<?php

namespace Graphp\Algorithms\Search;

use Fhaculty\Graph\Exception\InvalidArgumentException;
use Fhaculty\Graph\Set\Vertices;
use Fhaculty\Graph\Vertex;
use Graphp\Algorithms\BaseVertex;

abstract class Base extends BaseVertex
{
    const DIRECTION_FORWARD = 0;
    const DIRECTION_REVERSE = 1;
    const DIRECTION_BOTH = 2;

    private $direction = self::DIRECTION_FORWARD;

    /**
     * set direction in which to follow adjacent vertices
     *
     * @param  int             $direction
     * @return $this (chainable)
     * @throws InvalidArgumentException
     * @see self::getVerticesAdjacent()
     */
    public function setDirection($direction)
    {
        if ($direction !== self::DIRECTION_FORWARD && $direction !== self::DIRECTION_REVERSE && $direction !== self::DIRECTION_BOTH) {
            throw new InvalidArgumentException('Invalid direction given');
        }
        $this->direction = $direction;

        return $this;
    }

    protected function getVerticesAdjacent(Vertex $vertex)
    {
        if ($this->direction === self::DIRECTION_FORWARD) {
            return $vertex->getVerticesEdgeTo();
        } elseif ($this->direction === self::DIRECTION_REVERSE) {
            return $vertex->getVerticesEdgeFrom();
        } else {
            return $vertex->getVerticesEdge();
        }
    }

    /**
     * get set of all Vertices that can be reached from start vertex
     *
     * @return Vertices
     */
    abstract public function getVertices();
}
