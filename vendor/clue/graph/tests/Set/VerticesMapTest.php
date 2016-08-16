<?php

use Fhaculty\Graph\Set\VerticesMap;

class VerticesMapTest extends BaseVerticesTest
{
    protected function createVertices(array $vertices)
    {
        return new VerticesMap($vertices);
    }
}
