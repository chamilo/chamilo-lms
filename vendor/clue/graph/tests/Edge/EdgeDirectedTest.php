<?php

class EdgeDirectedTest extends EdgeBaseTest
{
    protected function createEdge()
    {
        // 1 -> 2
        return $this->v1->createEdgeTo($this->v2);
    }

    protected function createEdgeLoop()
    {
        // 1 --\
        // ^   |
        // \---/
        return $this->v1->createEdgeTo($this->v1);
    }

    public function testVerticesEnds()
    {
        $this->assertEquals(array($this->v1), $this->edge->getVerticesStart()->getVector());
        $this->assertEquals(array($this->v2), $this->edge->getVerticesTarget()->getVector());
    }
}
