<?php

namespace Fhaculty\Graph\Set;

use Fhaculty\Graph\Set\Vertices;

/**
 * Basic interface for every class that provides access to its Set of Vertices
 */
interface VerticesAggregate
{
    /**
     * @return Vertices
     */
    public function getVertices();
}
