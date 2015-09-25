<?php

use Fhaculty\Graph\Graph;
use Graphp\Algorithms\Tree\OutTree;

class OutTreeTest extends BaseDirectedTest
{
    protected function createGraphTree()
    {
        // c1 <- root -> c2
        $graph = new Graph();
        $root = $graph->createVertex();

        $c1 = $graph->createVertex();
        $root->createEdgeTo($c1);

        $c2 = $graph->createVertex();
        $root->createEdgeTo($c2);

        return $graph;
    }

    protected function createTreeAlg(Graph $graph)
    {
        return new OutTree($graph);
    }

    protected function createGraphNonTree()
    {
        // v1 -> v3 <- v2 -> v4
        $graph = new Graph();
        $graph->createVertex('v1')->createEdgeTo($graph->createVertex('v3'));
        $graph->createVertex('v2')->createEdgeTo($graph->getVertex('v3'));
        $graph->getVertex('v2')->createEdgeTo($graph->createVertex('v4'));

        return $graph;
    }

    protected function createGraphParallelEdge()
    {
        // v1 -> v2, v1 -> v2
        $graph = new Graph();
        $graph->createVertex('v1')->createEdgeTo($graph->createVertex('v2'));
        $graph->getVertex('v1')->createEdgeTo($graph->getVertex('v2'));

        return $graph;
    }
}
