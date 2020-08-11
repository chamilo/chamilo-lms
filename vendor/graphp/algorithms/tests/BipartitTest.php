<?php

use Fhaculty\Graph\Graph;
use Graphp\Algorithms\Bipartit as AlgorithmBipartit;

class BipartitTest extends TestCase
{
    public function testGraphEmpty()
    {
        $graph = new Graph();

        $alg = new AlgorithmBipartit($graph);

        $this->assertTrue($alg->isBipartit());
        $this->assertEquals(array(), $alg->getColors());
        $this->assertEquals(array(0 => array(), 1 => array()), $alg->getColorVertices());
    }

    public function testGraphPairIsBipartit()
    {
        // 1 -> 2
        $graph = new Graph();
        $v1 = $graph->createVertex(1);
        $v2 = $graph->createVertex(2);
        $v1->createEdgeTo($v2);

        $alg = new AlgorithmBipartit($graph);

        $this->assertTrue($alg->isBipartit());
        $this->assertEquals(array(1 => 0, 2 => 1), $alg->getColors());
        $this->assertEquals(array(0 => array(1 => $v1), 1 => array(2 => $v2)), $alg->getColorVertices());

        return $alg;
    }

    /**
     *
     * @param AlgorithmBipartit $alg
     * @depends testGraphPairIsBipartit
     */
    public function testGraphPairBipartitGroups(AlgorithmBipartit $alg)
    {
        // graph does not have any groups assigned, so its groups are not bipartit
        $this->assertFalse($alg->isBipartitGroups());

        // create a cloned graph with groups assigned according to bipartition
        $graph = $alg->createGraphGroups();

        $this->assertInstanceOf('Fhaculty\Graph\Graph', $graph);

        $alg2 = new AlgorithmBipartit($graph);
        $this->assertTrue($alg2->isBipartitGroups());
    }

    public function testGraphTriangleCycleIsNotBipartit()
    {
        // 1 -> 2 --> 3 --> 1
        $graph = new Graph();
        $v1 = $graph->createVertex(1);
        $v2 = $graph->createVertex(2);
        $v3 = $graph->createVertex(3);
        $v1->createEdgeTo($v2);
        $v2->createEdgeTo($v3);
        $v3->createEdgeTo($v1);

        $alg = new AlgorithmBipartit($graph);

        $this->assertFalse($alg->isBipartit());

        return $alg;
    }

    /**
     *
     * @param AlgorithmBipartit $alg
     * @expectedException UnexpectedValueException
     * @depends testGraphTriangleCycleIsNotBipartit
     */
    public function testGraphTriangleCycleColorsInvalid(AlgorithmBipartit $alg)
    {
        $alg->getColors();
    }

    /**
     *
     * @param AlgorithmBipartit $alg
     * @expectedException UnexpectedValueException
     * @depends testGraphTriangleCycleIsNotBipartit
     */
    public function testGraphTriangleCycleColorVerticesInvalid(AlgorithmBipartit $alg)
    {
        $alg->getColorVertices();
    }
}
