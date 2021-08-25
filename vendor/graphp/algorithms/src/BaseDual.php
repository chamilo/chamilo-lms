<?php

namespace Graphp\Algorithms;

use Fhaculty\Graph\Graph;
use Fhaculty\Graph\Set\DualAggregate;
use Fhaculty\Graph\Walk;

/**
 * Abstract base class for algorithms that operate on a given Set instance
 *
 * @see Set
 * @deprecated
 */
abstract class BaseDual extends Base
{
    /**
     * Set to operate on
     *
     * @var DualAggregate
     */
    protected $set;

    /**
     * instantiate new algorithm
     *
     * @param Graph|Walk|DualAggregate $graphOrWalk either the Graph or Walk to operate on (or the common base class Set)
     */
    public function __construct(DualAggregate $graphOrWalk)
    {
        $this->set = $graphOrWalk;
    }
}
