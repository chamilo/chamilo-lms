<?php

use Fhaculty\Graph\Graph;
use Graphp\Algorithms\Property\GraphProperty;

class PropertyGraphTest extends TestCase
{
    public function testEmptyIsEdgeless()
    {
        $graph = new Graph();

        $alg = new GraphProperty($graph);

        $this->assertTrue($alg->isNull());
        $this->assertTrue($alg->isEdgeless());
        $this->assertFalse($alg->isTrivial());
    }

    public function testSingleVertexIsTrivial()
    {
        $graph = new Graph();
        $graph->createVertex(1);

        $alg = new GraphProperty($graph);

        $this->assertFalse($alg->isNull());
        $this->assertTrue($alg->isEdgeless());
        $this->assertTrue($alg->isTrivial());
    }
}
