<?php

namespace Fhaculty\Graph\Set;

use Fhaculty\Graph\Set\Edges;

/**
 * Basic interface for every class that provides access to its Set of Edges
 */
interface EdgesAggregate
{
    /**
     * @return Edges
     */
    public function getEdges();
}
