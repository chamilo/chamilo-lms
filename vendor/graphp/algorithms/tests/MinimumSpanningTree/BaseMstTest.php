<?php

use Fhaculty\Graph\Graph;
use Fhaculty\Graph\Vertex;
use Graphp\Algorithms\MinimumSpanningTree\Base as MstBase;

abstract class BaseMstTest extends TestCase
{
    /**
     * @param Vertex $vertex
     * @return MstBase
     */
    abstract protected function createAlg(Vertex $vertex);

    public function testIsolatedVertex()
    {
        $graph = new Graph();
        $v1 = $graph->createVertex(1);

        $alg = $this->createAlg($v1);

        $this->assertCount(0, $alg->getEdges());
        $this->assertEquals(0, $alg->getWeight());

        $graphMst = $alg->createGraph();
        $this->assertGraphEquals($graph, $graphMst);
    }

    public function testSingleEdge()
    {
        // 1 --[3]-- 2
        $graph = new Graph();
        $v1 = $graph->createVertex(1);
        $v2 = $graph->createVertex(2);
        $v1->createEdge($v2)->setWeight(3);

        $alg = $this->createAlg($v1);

        $this->assertCount(1, $alg->getEdges());
        $this->assertEquals(3, $alg->getWeight());
        $this->assertGraphEquals($graph, $alg->createGraph());
    }

    public function testSimpleGraph()
    {
        // 1 --[6]-- 2 --[9]-- 3 --[7]-- 4 --[8]-- 5
        $graph = new Graph();
        $v1 = $graph->createVertex(1);
        $v2 = $graph->createVertex(2);
        $v3 = $graph->createVertex(3);
        $v4 = $graph->createVertex(4);
        $v5 = $graph->createVertex(5);
        $v1->createEdge($v2)->setWeight(6);
        $v2->createEdge($v3)->setWeight(9);
        $v3->createEdge($v4)->setWeight(7);
        $v4->createEdge($v5)->setWeight(8);

        $alg = $this->createAlg($v1);

        $graphMst = $alg->createGraph();
        $this->assertGraphEquals($graph, $graphMst);
    }

    public function testFindingCheapestEdge()
    {
        //   /--[4]--\
        //  /         \
        // 1 ---[3]--- 2
        //  \         /
        //   \--[5]--/
        $graph = new Graph();
        $v1 = $graph->createVertex(1);
        $v2 = $graph->createVertex(2);
        $v1->createEdge($v2)->setWeight(4);
        $v1->createEdge($v2)->setWeight(3);
        $v1->createEdge($v2)->setWeight(5);

        $alg = $this->createAlg($v1);
        $edges = $alg->getEdges();

        $this->assertCount(1, $edges);
        $this->assertEquals(3, $edges->getEdgeFirst()->getWeight());
        $this->assertEquals(3, $alg->getWeight());
    }

    public function testFindingCheapestTree()
    {
        // 1 --[4]-- 2 --[5]-- 3
        //  \                 /
        //   \-------[6]-----/
        $graph = new Graph();
        $v1 = $graph->createVertex(1);
        $v2 = $graph->createVertex(2);
        $v3 = $graph->createVertex(3);
        $v1->createEdge($v2)->setWeight(4);
        $v2->createEdge($v3)->setWeight(5);
        $v3->createEdge($v1)->setWeight(6);

        // 1 --[4]-- 2 -- [5] -- 3
        $graphExpected = new Graph();
        $ve1 = $graphExpected->createVertex(1);
        $ve2 = $graphExpected->createVertex(2);
        $ve3 = $graphExpected->createVertex(3);
        $ve1->createEdge($ve2)->setWeight(4);
        $ve2->createEdge($ve3)->setWeight(5);

        $alg = $this->createAlg($v1);
        $this->assertCount(2, $alg->getEdges());
        $this->assertEquals(9, $alg->getWeight());
        $this->assertGraphEquals($graphExpected, $alg->createGraph());
    }

    public function testMixedGraphDirectionIsIgnored()
    {
        // 1 --[6]-> 2 --[7]-- 3 --[8]-- 4 <-[9]-- 5
        $graph = new Graph();
        $v1 = $graph->createVertex(1);
        $v2 = $graph->createVertex(2);
        $v3 = $graph->createVertex(3);
        $v4 = $graph->createVertex(4);
        $v5 = $graph->createVertex(5);
        $v1->createEdgeTo($v2)->setWeight(6);
        $v2->createEdge($v3)->setWeight(7);
        $v4->createEdge($v3)->setWeight(8);
        $v5->createEdgeTo($v4)->setWeight(9);

        $alg = $this->createAlg($v1);

        $this->assertCount(4, $alg->getEdges());
        $this->assertEquals(30, $alg->getWeight());
        $this->assertGraphEquals($graph, $alg->createGraph());
    }

    /**
     * @expectedException UnexpectedValueException
     */
    public function testMultipleComponentsFail()
    {
        // 1 --[1]-- 2, 3 --[1]-- 4
        $graph = new Graph();
        $v1 = $graph->createVertex(1);
        $v2 = $graph->createVertex(2);
        $v3 = $graph->createVertex(3);
        $v4 = $graph->createVertex(4);
        $v1->createEdge($v2)->setWeight(1);
        $v3->createEdge($v4)->setWeight(1);

        $alg = $this->createAlg($v1);
        $alg->getEdges();
    }

    /**
     * @expectedException UnexpectedValueException
     */
    public function testMultipleIsolatedVerticesFormMultipleComponentsFail()
    {
        // 1, 2
        $graph = new Graph();
        $v1 = $graph->createVertex(1);
        $graph->createVertex(2);

        $alg = $this->createAlg($v1);
        $alg->getEdges();
    }


}