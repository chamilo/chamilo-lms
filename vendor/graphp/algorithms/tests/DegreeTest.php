<?php

use Fhaculty\Graph\Exception\UnderflowException;
use Fhaculty\Graph\Exception\UnexpectedValueException;
use Fhaculty\Graph\Graph;
use Graphp\Algorithms\Degree as AlgorithmDegree;

class DegreeTest extends TestCase
{
    public function testGraphEmpty()
    {
        $graph = new Graph();

        $alg = new AlgorithmDegree($graph);

        try {
            $alg->getDegree();
            $this->fail();
        } catch (UnderflowException $e) { }

        try {
            $alg->getDegreeMin();
            $this->fail();
        } catch (UnderflowException $e) { }

        try {
            $alg->getDegreeMax();
            $this->fail();
        } catch (UnderflowException $e) { }

        $this->assertTrue($alg->isRegular());
        $this->assertTrue($alg->isBalanced());
    }

    public function testGraphIsolated()
    {
        $graph = new Graph();
        $graph->createVertex(1);
        $graph->createVertex(2);

        $alg = new AlgorithmDegree($graph);

        $this->assertEquals(0, $alg->getDegree());
        $this->assertEquals(0, $alg->getDegreeMin());
        $this->assertEquals(0, $alg->getDegreeMax());
        $this->assertTrue($alg->isRegular());
        $this->assertTrue($alg->isBalanced());
    }

    public function testGraphIrregular()
    {
        // 1 -> 2 -> 3
        $graph = new Graph();
        $v1 = $graph->createVertex(1);
        $v2 = $graph->createVertex(2);
        $v3 = $graph->createVertex(3);
        $v1->createEdgeTo($v2);
        $v2->createEdgeTo($v3);

        $alg = new AlgorithmDegree($graph);

        try {
            $this->assertEquals(0, $alg->getDegree());
            $this->fail();
        } catch (UnexpectedValueException $e) { }

        $this->assertEquals(1, $alg->getDegreeMin());
        $this->assertEquals(2, $alg->getDegreeMax());
        $this->assertFalse($alg->isRegular());
        $this->assertFalse($alg->isBalanced());


        $this->assertEquals(0, $alg->getDegreeInVertex($v1));
        $this->assertEquals(1, $alg->getDegreeOutVertex($v1));
        $this->assertEquals(1, $alg->getDegreeVertex($v1));
        $this->assertFalse($alg->isVertexIsolated($v1));
        $this->assertFalse($alg->isVertexSink($v1));
        $this->assertTrue($alg->isVertexSource($v1));

        $this->assertEquals(1, $alg->getDegreeInVertex($v2));
        $this->assertEquals(1, $alg->getDegreeOutVertex($v2));
        $this->assertEquals(2, $alg->getDegreeVertex($v2));
        $this->assertFalse($alg->isVertexIsolated($v2));
        $this->assertFalse($alg->isVertexSink($v2));
        $this->assertFalse($alg->isVertexSource($v2));

        $this->assertEquals(1, $alg->getDegreeInVertex($v3));
        $this->assertEquals(0, $alg->getDegreeOutVertex($v3));
        $this->assertEquals(1, $alg->getDegreeVertex($v3));
        $this->assertFalse($alg->isVertexIsolated($v3));
        $this->assertTrue($alg->isVertexSink($v3));
        $this->assertFalse($alg->isVertexSource($v3));
    }
}
