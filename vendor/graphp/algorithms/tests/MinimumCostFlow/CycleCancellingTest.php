<?php

use Fhaculty\Graph\Graph;
use Graphp\Algorithms\MinimumCostFlow\CycleCanceling;

class CycleCancellingTest extends BaseMcfTest
{
    protected function createAlgorithm(Graph $graph)
    {
        return new CycleCanceling($graph);
    }
}
