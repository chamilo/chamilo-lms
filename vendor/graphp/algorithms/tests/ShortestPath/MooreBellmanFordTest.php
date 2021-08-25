<?php

use Fhaculty\Graph\Graph;
use Fhaculty\Graph\Vertex;
use Graphp\Algorithms\ShortestPath\MooreBellmanFord;

class MooreBellmanFordTest extends BaseShortestPathTest
{
    protected function createAlg(Vertex $vertex)
    {
        return new MooreBellmanFord($vertex);
    }

    public function testGraphParallelNegative()
    {
        // 1 -[10]-> 2
        // |         ^
        // \--[-1]---/
        $graph = new Graph();
        $v1 = $graph->createVertex(1);
        $v2 = $graph->createVertex(2);
        $v1->createEdgeTo($v2)->setWeight(10);
        $e2 = $v1->createEdgeTo($v2)->setWeight(-1);

        $alg = $this->createAlg($v1);

        // $this->assertEquals(0, $alg->getDistance($v1));
        $this->assertEquals(-1, $alg->getDistance($v2));
        $this->assertEquals(array(2 => -1), $alg->getDistanceMap());
        $this->assertEquals(array($e2), $alg->getEdges()->getVector());
        //$this->assertEquals(array(), $alg->getEdgesTo($v1));
        $this->assertEquals(array($e2), $alg->getEdgesTo($v2)->getVector());
        $this->assertEquals(array(2 => $v2), $alg->getVertices()->getMap());
        $this->assertEquals(array(2), $alg->getVertices()->getIds());

        return $alg;
    }

    /**
     * @param MooreBellmanFord $alg
     * @depends testGraphParallelNegative
     * @expectedException UnderflowException
     */
    public function testNoNegativeCycle(MooreBellmanFord $alg)
    {
        $alg->getCycleNegative();
    }

    public function testUndirectedNegativeWeightIsCycle()
    {
        // 1 -[-10]- 2
        $graph = new Graph();
        $v1 = $graph->createVertex(1);
        $v2 = $graph->createVertex(2);
        $v1->createEdge($v2)->setWeight(-10);

        $alg = $this->createAlg($v1);

        $cycle = $alg->getCycleNegative();

        $this->assertInstanceOf('Fhaculty\Graph\Walk', $cycle);
    }

    public function testLoopNegativeWeightIsCycle()
    {
        // 1 -[-10]-> 1
        $graph = new Graph();
        $v1 = $graph->createVertex(1);
        $v1->createEdge($v1)->setWeight(-10);

        $alg = $this->createAlg($v1);

        $cycle = $alg->getCycleNegative();

        $this->assertInstanceOf('Fhaculty\Graph\Walk', $cycle);
    }

    public function testNegativeComponentHasCycle()
    {
        // 1 -[1]-> 2     3 --[-1]--> 4
        //                ^           |
        //                \---[-2]----/
        $graph = new Graph();
        $v1 = $graph->createVertex(1);
        $v2 = $graph->createVertex(2);
        $v3 = $graph->createVertex(3);
        $v4 = $graph->createVertex(4);
        $v1->createEdgeTo($v2)->setWeight(1);
        $v3->createEdgeTo($v4)->setWeight(-1);
        $v4->createEdgeTo($v3)->setWeight(-2);

        // second component has a cycle
        $alg = $this->createAlg($v3);
        $cycle = $alg->getCycleNegative();
        assert(isset($cycle));

        // first component does not have a cycle
        $alg = $this->createAlg($v1);
        $this->expectException('UnderflowException');
        $alg->getCycleNegative();
    }

    public function expectException($class)
    {
        if (\method_exists($this, 'setExpectedException')) {
            $this->setExpectedException($class);
        } else {
            parent::expectException($class);
        }
    }
}
