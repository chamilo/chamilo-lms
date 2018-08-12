<?php

use Fhaculty\Graph\Graph;
use Graphp\Algorithms\Tree\InTree;

class InTreeTest extends BaseDirectedTest
{
    protected function createGraphTree()
    {
        // c1 -> root <- c2
        $graph = new Graph();
        $root = $graph->createVertex();

        $c1 = $graph->createVertex();
        $c1->createEdgeTo($root);

        $c2 = $graph->createVertex();
        $c2->createEdgeTo($root);

        return $graph;
    }

    protected function createTreeAlg(Graph $graph)
    {
        return new InTree($graph);
    }

    protected function createGraphNonTree()
    {
        // v1 -> v2 <- v3 -> v4
        $graph = new Graph();
        $graph->createVertex('v1')->createEdgeTo($graph->createVertex('v2'));
        $graph->createVertex('v3')->createEdgeTo($graph->getVertex('v2'));
        $graph->getVertex('v3')->createEdgeTo($graph->createVertex('v4'));

        return $graph;
    }

    protected function createGraphParallelEdge()
    {
        // v1 <- v2, v1 <- v2
        $graph = new Graph();
        $graph->createVertex('v2')->createEdgeTo($graph->createVertex('v1'));
        $graph->getVertex('v2')->createEdgeTo($graph->getVertex('v1'));

        return $graph;
    }
}
