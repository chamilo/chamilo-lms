<?php

use Fhaculty\Graph\Graph;
use Graphp\Algorithms\MaxFlow\EdmondsKarp as AlgorithmMaxFlowEdmondsKarp;
use PHPUnit\Framework\TestCase;

class EdmondsKarpTest extends TestCase
{
    public function testEdgeDirected()
    {
        // 0 -[0/10]-> 1
        $graph = new Graph();
        $v0 = $graph->createVertex(0);
        $v1 = $graph->createVertex(1);

        $v0->createEdgeTo($v1)->setCapacity(10);

        // 0 -[10/10]-> 1
        $alg = new AlgorithmMaxFlowEdmondsKarp($v0, $v1);

        $this->assertEquals(10, $alg->getFlowMax());
    }

    public function testEdgesMultiplePaths()
    {
        // 0 -[0/5]---------> 1
        // |                  ^
        // |                  |
        // \-[0/7]-> 2 -[0/9]-/
        $graph = new Graph();
        $v0 = $graph->createVertex(0);
        $v1 = $graph->createVertex(1);
        $v2 = $graph->createVertex(2);

        $v0->createEdgeTo($v1)->setCapacity(5);
        $v0->createEdgeTo($v2)->setCapacity(7);
        $v2->createEdgeTo($v1)->setCapacity(9);

        // 0 -[5/5]---------> 1
        // |                  ^
        // |                  |
        // \-[7/7]-> 2 -[7/9]-/
        $alg = new AlgorithmMaxFlowEdmondsKarp($v0, $v1);

        $this->assertEquals(12, $alg->getFlowMax());
    }

    public function testEdgesMultiplePathsTwo()
    {
        // 0 -[0/5]---------> 1-[0/10]-> 3
        // |                  ^          |
        // |                  |          |
        // \-[0/7]-> 2 -[0/9]-/          |
        //           ^                   |
        //           \---[0/2]-----------/
        $graph = new Graph();
        $v0 = $graph->createVertex(0);
        $v1 = $graph->createVertex(1);
        $v2 = $graph->createVertex(2);
        $v3 = $graph->createVertex(3);

        $v0->createEdgeTo($v1)->setCapacity(5);
        $v0->createEdgeTo($v2)->setCapacity(7);
        $v2->createEdgeTo($v1)->setCapacity(9);
        $v1->createEdgeTo($v3)->setCapacity(10);
        $v3->createEdgeTo($v2)->setCapacity(2);

        $alg = new AlgorithmMaxFlowEdmondsKarp($v0, $v3);

        $this->assertEquals(10, $alg->getFlowMax());

        $alg = new AlgorithmMaxFlowEdmondsKarp($v0, $v2);

        $this->assertEquals(9, $alg->getFlowMax());
    }

    public function testEdgesMultiplePathsTree()
    {
        $graph = new Graph();
        $v0 = $graph->createVertex(0);
        $v1 = $graph->createVertex(1);
        $v2 = $graph->createVertex(2);
        $v3 = $graph->createVertex(3);

        $v0->createEdgeTo($v1)->setCapacity(4);
        $v0->createEdgeTo($v2)->setCapacity(2);
        $v1->createEdgeTo($v2)->setCapacity(3);
        $v1->createEdgeTo($v3)->setCapacity(1);
        $v2->createEdgeTo($v3)->setCapacity(6);

        $alg = new AlgorithmMaxFlowEdmondsKarp($v0, $v3);

        $this->assertEquals(6, $alg->getFlowMax());
    }

//     public function testEdgesParallel(){
//         $graph = new Graph();
//         $v0 = $graph->createVertex(0);
//         $v1 = $graph->createVertex(1);

//         $v0->createEdgeTo($v1)->setCapacity(3.4);
//         $v0->createEdgeTo($v1)->setCapacity(6.6);

//         $alg = new AlgorithmMaxFlowEdmondsKarp($v0, $v1);

//         $this->assertEquals(10, $alg->getFlowMax());
//     }

    /**
     * @expectedException UnexpectedValueException
     */
    public function testEdgesUndirected()
    {
        // 0 -[0/7]- 1
        $graph = new Graph();
        $v0 = $graph->createVertex(0);
        $v1 = $graph->createVertex(1);

        $v1->createEdge($v0)->setCapacity(7);

        // 0 -[7/7]- 1
        $alg = new AlgorithmMaxFlowEdmondsKarp($v0, $v1);

        $this->assertEquals(7, $alg->getFlowMax());
    }

    /**
     * run algorithm with bigger graph and check result against known result (will take several seconds)
     */
//     public function testKnownResultBig(){

//         $graph = $this->readGraph('G_1_2.txt');

//         $alg = new AlgorithmMaxFlowEdmondsKarp($graph->getVertex(0), $graph->getVertex(4));

//         $this->assertEquals(0.735802, $alg->getFlowMax());
//     }


    /**
     * @expectedException InvalidArgumentException
     */
    public function testInvalidFlowToOtherGraph()
    {
        $graph1 = new Graph();
        $vg1 = $graph1->createVertex(1);

        $graph2 = new Graph();
        $vg2 = $graph2->createVertex(2);

        new AlgorithmMaxFlowEdmondsKarp($vg1, $vg2);
    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function testInvalidFlowToSelf()
    {
        $graph = new Graph();
        $v1 = $graph->createVertex(1);

        new AlgorithmMaxFlowEdmondsKarp($v1, $v1);
    }

}
