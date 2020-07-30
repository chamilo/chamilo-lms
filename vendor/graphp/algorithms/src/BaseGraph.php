<?php

namespace Graphp\Algorithms;

use Fhaculty\Graph\Graph;

/**
 * Abstract base class for algorithms that operate on a given Graph instance
 *
 * @deprecated
 */
abstract class BaseGraph extends Base
{
    /**
     * Graph to operate on
     *
     * @var Graph
     */
    protected $graph;

    /**
     * instantiate new algorithm
     *
     * @param Graph $graph Graph to operate on
     */
    public function __construct(Graph $graph)
    {
        $this->graph = $graph;
    }
}
