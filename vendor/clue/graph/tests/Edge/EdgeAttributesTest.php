<?php

use Fhaculty\Graph\Graph;
use Fhaculty\Graph\Edge\Base as Edge;

class EdgeAttributesTest extends TestCase
{

    /**
     *
     * @var Edge
     */
    private $edge;

    public function setUp()
    {

        $graph = new Graph();
        $graph->createVertex(1);
        $graph->createVertex(2);

        // 1 -> 2
        $this->edge = $graph->getVertex(1)->createEdge($graph->getVertex(2));
    }

    public function testCanSetFlowAndCapacity()
    {
        $this->edge->setCapacity(100);
        $this->edge->setFlow(10);

        $this->assertEquals(90, $this->edge->getCapacityRemaining());
    }

    public function testCanSetFlowBeforeCapacity()
    {
        $this->edge->setFlow(20);

        $this->assertEquals(null, $this->edge->getCapacityRemaining());
    }

    /**
     * @expectedException RangeException
     */
    public function testFlowMustNotExceedCapacity()
    {
        $this->edge->setCapacity(20);
        $this->edge->setFlow(100);
    }

    /**
     * @expectedException RangeException
     */
    public function testCapacityMustBeGreaterThanFlow()
    {
        $this->edge->setFlow(100);
        $this->edge->setCapacity(20);
    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function testWeightMustBeNumeric()
    {
        $this->edge->setWeight("10");
    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function testCapacityMustBeNumeric()
    {
        $this->edge->setCapacity("10");
    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function testCapacityMustBePositive()
    {
        $this->edge->setCapacity(-10);
    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function testFlowMustBeNumeric()
    {
        $this->edge->setFlow("10");
    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function testFlowMustBePositive()
    {
        $this->edge->setFlow(-10);
    }
}
