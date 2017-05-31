<?php

use Fhaculty\Graph\Graph;
use Fhaculty\Graph\Vertex;

class VertexTest extends AbstractAttributeAwareTest
{
    public function setUp()
    {
        $this->graph = new Graph();
        $this->vertex = $this->graph->createVertex(1);
    }

    public function testPrecondition()
    {
        $this->assertCount(1, $this->graph->getVertices());
        $this->assertTrue($this->graph->hasVertex(1));
        $this->assertFalse($this->graph->hasVertex(2));
        $this->assertSame($this->vertex, $this->graph->getVertex(1));
    }

    public function testConstructor()
    {
        $v2 = new Vertex($this->graph, 2);

        $this->assertCount(2, $this->graph->getVertices());
        $this->assertTrue($this->graph->hasVertex(2));

        $this->assertSame($v2, $this->graph->getVertex(2));
    }

    /**
     * @expectedException OverflowException
     */
    public function testCanNotConstructDuplicateVertex()
    {
        $v2 = new Vertex($this->graph, 1);
    }

    public function testEdges()
    {
        // v1 -> v2, v1 -- v3, v1 <- v4
        $v2 = $this->graph->createVertex(2);
        $v3 = $this->graph->createVertex(3);
        $v4 = $this->graph->createVertex(4);
        $e1 = $this->vertex->createEdgeTo($v2);
        $e2 = $this->vertex->createEdge($v3);
        $e3 = $v4->createEdgeTo($this->vertex);

        $this->assertEquals(array($e1, $e2, $e3), $this->vertex->getEdges()->getVector());
        $this->assertEquals(array($e2, $e3), $this->vertex->getEdgesIn()->getVector());
        $this->assertEquals(array($e1, $e2), $this->vertex->getEdgesOut()->getVector());

        $this->assertTrue($this->vertex->hasEdgeTo($v2));
        $this->assertTrue($this->vertex->hasEdgeTo($v3));
        $this->assertFalse($this->vertex->hasEdgeTo($v4));

        $this->assertFalse($this->vertex->hasEdgeFrom($v2));
        $this->assertTrue($this->vertex->hasEdgeFrom($v3));
        $this->assertTrue($this->vertex->hasEdgeFrom($v4));

        $this->assertEquals(array($e1), $this->vertex->getEdgesTo($v2)->getVector());
        $this->assertEquals(array($e2), $this->vertex->getEdgesTo($v3)->getVector());
        $this->assertEquals(array(), $this->vertex->getEdgesTo($v4)->getVector());

        $this->assertEquals(array(), $this->vertex->getEdgesFrom($v2)->getVector());
        $this->assertEquals(array($e2), $this->vertex->getEdgesTo($v3)->getVector());
        $this->assertEquals(array($e3), $this->vertex->getEdgesFrom($v4)->getVector());

        $this->assertEquals(array($v2, $v3, $v4), $this->vertex->getVerticesEdge()->getVector());
        $this->assertEquals(array($v2, $v3), $this->vertex->getVerticesEdgeTo()->getVector());
        $this->assertEquals(array($v3, $v4), $this->vertex->getVerticesEdgeFrom()->getVector());
    }

    public function testBalance()
    {
        $this->vertex->setBalance(10);
        $this->assertEquals(10, $this->vertex->getBalance());
    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function testBalanceInvalid()
    {
        $this->vertex->setBalance("10");
    }

    public function testGroup()
    {
        $this->vertex->setGroup(2);
        $this->assertEquals(2, $this->vertex->getGroup());
    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function testGroupInvalid()
    {
        $this->vertex->setGroup("3");
    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function testCreateEdgeOtherGraphFails()
    {
        $graphOther = new Graph();

        $this->vertex->createEdge($graphOther->createVertex(2));
    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function testCreateEdgeToOtherGraphFails()
    {
        $graphOther = new Graph();

        $this->vertex->createEdgeTo($graphOther->createVertex(2));
    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function testRemoveInvalidEdge()
    {
        // 2 -- 3
        $v2 = $this->graph->createVertex(2);
        $v3 = $this->graph->createVertex(3);
        $edge = $v2->createEdge($v3);

        $this->vertex->removeEdge($edge);
    }

    protected function createAttributeAware()
    {
        return new Vertex(new Graph(), 1);
    }
}
