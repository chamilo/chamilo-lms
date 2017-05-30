<?php

use Fhaculty\Graph\Vertex;
use Graphp\Algorithms\MinimumSpanningTree\Prim;

class PrimTest extends BaseMstTest
{
    protected function createAlg(Vertex $vertex)
    {
        return new Prim($vertex);
    }
}
