<?php

use Fhaculty\Graph\Graph;
use Graphp\Algorithms\MaximumMatching\Flow;
use PHPUnit\Framework\TestCase;

class FlowTest extends TestCase
{
//     /**
//      * run algorithm with small graph and check result against known result
//      */
//     public function testKnownResult()
//     {
//         $loader = new EdgeListBipartit(PATH_DATA . 'Matching_100_100.txt');
//         $loader->setEnableDirectedEdges(false);
//         $graph = $loader->createGraph();

//         $alg = new Flow($graph);
//         $this->assertEquals(100, $alg->getNumberOfMatches());
//     }

    public function testSingleEdge()
    {
        $graph = new Graph();
        $edge = $graph->createVertex(0)->setGroup(0)->createEdge($graph->createVertex(1)->setGroup(1));

        $alg = new Flow($graph);
        // correct number of edges
        $this->assertEquals(1, $alg->getNumberOfMatches());
        // actual edge instance returned
        $this->assertEquals(array($edge), $alg->getEdges()->getVector());

        // check
        $flowgraph = $alg->createGraph();
        $this->assertInstanceOf('Fhaculty\Graph\Graph', $flowgraph);
    }

    /**
     * expect exception for directed edges
     * @expectedException UnexpectedValueException
     */
    public function testInvalidDirected()
    {
        $graph = new Graph();
        $graph->createVertex(0)->setGroup(0)->createEdgeTo($graph->createVertex(1)->setGroup(1));

        $alg = new Flow($graph);
        $alg->getNumberOfMatches();
    }

    /**
     * expect exception for non-bipartit graphs
     * @expectedException UnexpectedValueException
     */
    public function testInvalidBipartit()
    {
        $graph = new Graph();
        $graph->createVertex(0)->setGroup(1)->createEdge($graph->createVertex(1)->setGroup(1));

        $alg = new Flow($graph);
        $alg->getNumberOfMatches();
    }
}
