<?php

use Fhaculty\Graph\Graph;
use Graphp\Algorithms\MinimumCostFlow\SuccessiveShortestPath;

class SuccessiveShortestPathTest extends BaseMcfTest
{
    protected function createAlgorithm(Graph $graph)
    {
        return new SuccessiveShortestPath($graph);
    }
}
