<?php

use Fhaculty\Graph\Set\Vertices;
use Fhaculty\Graph\Graph;
use Fhaculty\Graph\Exception\InvalidArgumentException;

class VerticesTest extends BaseVerticesTest
{
    protected function createVertices(array $vertices)
    {
        return new Vertices($vertices);
    }

    public function testFactoryEmptyArray()
    {
        $vertices = Vertices::factory(array());

        $this->assertInstanceOf('Fhaculty\Graph\Set\Vertices', $vertices);
        $this->assertTrue($vertices->isEmpty());
    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function testGetVertexOrderInvalidSortBy()
    {
        $graph = new Graph();
        $graph->createVertex(1);

        $vertices = $graph->getVertices();

        $vertices->getVertexOrder('not a valid callback');
    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function testGetVicesOrderInvalidSortBy()
    {
        $vertices = $this->createVertices(array());

        $vertices->getVerticesOrder('not a valid callback');
    }

    public function testDuplicates()
    {
        $graph = new Graph();
        $v1 = $graph->createVertex(1);

        $vertices = $this->createVertices(array($v1, $v1, $v1));

        $this->assertInstanceOf('Fhaculty\Graph\Set\Vertices', $vertices);
        $this->assertCount(3, $vertices);
        $this->assertTrue($vertices->hasDuplicates());

        $verticesDistinct = $vertices->getVerticesDistinct();

        $this->assertInstanceOf('Fhaculty\Graph\Set\Vertices', $verticesDistinct);
        $this->assertCount(1, $verticesDistinct);
        $this->assertFalse($verticesDistinct->hasDuplicates());

        $this->assertSame($verticesDistinct, $verticesDistinct->getVerticesDistinct());
    }
}
