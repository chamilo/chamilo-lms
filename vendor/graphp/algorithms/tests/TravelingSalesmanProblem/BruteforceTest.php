<?php

use Fhaculty\Graph\Graph;
use Graphp\Algorithms\TravelingSalesmanProblem\Bruteforce;

class BruteforceTest extends TestCase
{
    public function testGetWeightReturnsExpectedWeightForSimpleCycle()
    {
        $graph = new Graph();
        $a = $graph->createVertex();
        $b = $graph->createVertex();
        $c = $graph->createVertex();
        $a->createEdgeTo($b)->setWeight(1);
        $b->createEdgeTo($c)->setWeight(2);
        $c->createEdgeTo($a)->setWeight(3);

        $alg = new Bruteforce($graph);

        $this->assertEquals(6, $alg->getWeight());
    }

    public function testSetUpperLimitMstSetsExactLimitForSimpleCycle()
    {
        $graph = new Graph();
        $a = $graph->createVertex();
        $b = $graph->createVertex();
        $c = $graph->createVertex();
        $a->createEdgeTo($b)->setWeight(1);
        $b->createEdgeTo($c)->setWeight(2);
        $c->createEdgeTo($a)->setWeight(3);

        $alg = new Bruteforce($graph);
        $alg->setUpperLimitMst();

        $ref = new ReflectionProperty($alg, 'upperLimit');
        $ref->setAccessible(true);

        $this->assertEquals(6, $ref->getValue($alg));
    }
}
