<?php

use Fhaculty\Graph\Graph;
use Graphp\Algorithms\MinimumCostFlow\Base;

abstract class BaseMcfTest extends TestCase
{
    /**
     *
     * @param Graph $graph
     * @return Base
     */
    abstract protected function createAlgorithm(Graph $graph);

    public function testNull()
    {
        $graph = new Graph();

        $alg = $this->createAlgorithm($graph);
        $this->assertEquals(0, $alg->getWeightFlow());
    }

    public function testSingleIntermediary()
    {
        $graph = new Graph();
        $graph->createVertex(1);

        $alg = $this->createAlgorithm($graph);
        $this->assertEquals(0, $alg->getWeightFlow());
    }

    public function testSimpleEdge()
    {
        // 1(+2) -[0/2/2]-> 2(-2)
        $graph = new Graph();
        $v1 = $graph->createVertex(1)->setBalance(2);
        $v2 = $graph->createVertex(2)->setBalance(-2);
        $v1->createEdgeTo($v2)->setWeight(2)->setCapacity(2);

        $alg = $this->createAlgorithm($graph);
        $this->assertEquals(4, $alg->getWeightFlow()); // 2x2
    }

    public function testMultipleSinks()
    {
        // 1(+2) -[0/2/2]-> 2(-1)
        //       -[0/4/-5]-> 3(-1)
        $graph = new Graph();
        $v1 = $graph->createVertex(1)->setBalance(2);
        $v2 = $graph->createVertex(2)->setBalance(-1);
        $v3 = $graph->createVertex(3)->setBalance(-1);
        $v1->createEdgeTo($v2)->setWeight(2)->setCapacity(2);
        $v1->createEdgeTo($v3)->setWeight(-5)->setCapacity(4);

        $alg = $this->createAlgorithm($graph);
        $this->assertEquals(-3, $alg->getWeightFlow()); // 1*2 + 1*-5
    }

    public function testIntermediaryVertices()
    {
        // 1(+2) -[0/1/4]-> 2 -[0/6/-2]-> 4(-2)
        //       -[0/4/5]-> 3 -[0/6/8]->
        $graph = new Graph();
        $v1 = $graph->createVertex(1)->setBalance(2);
        $v2 = $graph->createVertex(2);
        $v3 = $graph->createVertex(3);
        $v4 = $graph->createVertex(4)->setBalance(-2);
        $v1->createEdgeTo($v2)->setWeight(4)->setCapacity(1);
        $v2->createEdgeTo($v4)->setWeight(-2)->setCapacity(6);
        $v1->createEdgeTo($v3)->setWeight(5)->setCapacity(4);
        $v3->createEdgeTo($v4)->setWeight(8)->setCapacity(6);

        $alg = $this->createAlgorithm($graph);
        $this->assertEquals(15, $alg->getWeightFlow()); // 1*4 + 1*-2 + 1*5 + 1*8
    }

    public function testEdgeCapacities()
    {
        // 1(+2) -[0/3/4]-> 2 -[0/4/5]-> 3 ->[0/6/-2]-> 4(-2)
        $graph = new Graph();
        $v1 = $graph->createVertex(1)->setBalance(2);
        $v2 = $graph->createVertex(2);
        $v3 = $graph->createVertex(3);
        $v4 = $graph->createVertex(4)->setBalance(-2);
        $v1->createEdgeTo($v2)->setWeight(4)->setCapacity(3);
        $v2->createEdgeTo($v3)->setWeight(5)->setCapacity(4);
        $v3->createEdgeTo($v4)->setWeight(-2)->setCapacity(6);

        $alg = $this->createAlgorithm($graph);
        $this->assertEquals(14, $alg->getWeightFlow()); // 2*4 + 2*5 + 2*-2
    }

    public function testEdgeFlows()
    {
        // 1(+4) ---[3/4/2]---> 2 ---[3/3/3]---> 4(-4)
        //  |                   |                  ^
        //  |                [0/2/1]               |
        //  |                   ↓                  |
        //  \-------[1/2/2]---> 3 ---[1/5/1]-------/
        $graph = new Graph();
        $v1 = $graph->createVertex(1)->setBalance(4);
        $v2 = $graph->createVertex(2);
        $v3 = $graph->createVertex(3);
        $v4 = $graph->createVertex(4)->setBalance(-4);
        $v1->createEdgeTo($v2)->setFlow(3)->setCapacity(4)->setWeight(2);
        $v2->createEdgeTo($v4)->setFlow(3)->setCapacity(3)->setWeight(3);
        $v1->createEdgeTo($v3)->setFlow(1)->setCapacity(2)->setWeight(2);
        $v3->createEdgeTo($v4)->setFlow(1)->setCapacity(5)->setWeight(1);
        $v2->createEdgeTo($v3)->setFlow(0)->setCapacity(2)->setWeight(1);

        $alg = $this->createAlgorithm($graph);
        $this->assertEquals(14, $alg->getWeightFlow()); // 4*1 + 2*2 + 2*1 + 2*2
    }

    /**
     * @expectedException UnexpectedValueException
     */
    public function testEdgeCapacityInsufficientFails()
    {
        // 1(+2) -[0/1]-> 2(-2)
        $graph = new Graph();
        $v1 = $graph->createVertex(1)->setBalance(2);
        $v2 = $graph->createVertex(2)->setBalance(-2);
        $v1->createEdgeTo($v2)->setCapacity(1);

        $alg = $this->createAlgorithm($graph);
        $alg->getWeightFlow();
    }

    /**
     * @expectedException UnexpectedValueException
     */
    public function testEdgeCapacityUnsetFails()
    {
        // 1(+2) -> 2(-2)
        $graph = new Graph();
        $v1 = $graph->createVertex(1)->setBalance(2);
        $v2 = $graph->createVertex(2)->setBalance(-2);
        $v1->createEdgeTo($v2);

        $alg = $this->createAlgorithm($graph);
        $alg->getWeightFlow();
    }

    /**
     * @expectedException UnexpectedValueException
     */
    public function testIsolatedVerticesFail()
    {
        // 1(+2), 2(-2)
        $graph = new Graph();
        $graph->createVertex(1)->setBalance(2);
        $graph->createVertex(2)->setBalance(-2);

        $alg = $this->createAlgorithm($graph);
        $alg->getWeightFlow();
    }

    /**
     * @expectedException UnexpectedValueException
     */
    public function testUnbalancedFails()
    {
        // 1(+2) -> 2(-3)
        $graph = new Graph();
        $v1 = $graph->createVertex(1)->setBalance(2);
        $v2 = $graph->createVertex(2)->setBalance(-3);
        $v1->createEdgeTo($v2)->setCapacity(3);

        $alg = $this->createAlgorithm($graph);
        $alg->getWeightFlow();
    }

    /**
     * @expectedException UnexpectedValueException
     */
    public function testUndirectedFails()
    {
        // 1(+2) -- 2(-2)
        $graph = new Graph();
        $v1 = $graph->createVertex(1)->setBalance(2);
        $v2 = $graph->createVertex(2)->setBalance(-2);
        $v1->createEdge($v2)->setCapacity(2);

        $alg = $this->createAlgorithm($graph);
        $alg->getWeightFlow();
    }

    /**
     * @expectedException UnexpectedValueException
     */
    public function testUndirectedNegativeCycleFails()
    {
        // 1(+2) -[0/2/-1]- 2(-2)
        $graph = new Graph();
        $v1 = $graph->createVertex(1)->setBalance(2);
        $v2 = $graph->createVertex(2)->setBalance(-2);
        $v1->createEdge($v2)->setCapacity(2)->setWeight(-1);

        $alg = $this->createAlgorithm($graph);
        $alg->getWeightFlow();
    }
}
