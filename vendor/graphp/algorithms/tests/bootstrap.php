<?php

use Fhaculty\Graph\Edge\Directed;
use Fhaculty\Graph\Edge\Base as Edge;
use Fhaculty\Graph\Graph;
use Fhaculty\Graph\Vertex;
use PHPUnit\Framework\TestCase as BaseTestCase;

(include_once __DIR__ . '/../vendor/autoload.php') OR die(PHP_EOL . 'ERROR: composer autoloader not found, run "composer install" or see README for instructions' . PHP_EOL);

class TestCase extends BaseTestCase
{
    protected function assertGraphEquals(Graph $expected, Graph $actual)
    {
        $f = function(Graph $graph){
            $ret = \get_class($graph);
            $ret .= PHP_EOL . 'vertices: ' . \count($graph->getVertices());
            $ret .= PHP_EOL . 'edges: ' . \count($graph->getEdges());

            return $ret;
        };

        // assert graph base parameters are equal
        $this->assertEquals($f($expected), $f($actual));

        // next, assert that all vertices in both graphs are the same
        // each vertex has a unique ID, therefor it's easy to search a matching partner
        // do not use assertVertexEquals() in order to not increase assertion counter

        foreach ($expected->getVertices()->getMap() as $vid => $vertex) {
            $other = $actual->getVertex($vid);
            assert(isset($other));

            if ($this->getVertexDump($vertex) !== $this->getVertexDump($vertex)) {
                $this->fail();
            }
        }

        // next, assert that all edges in both graphs are the same
        // assertEdgeEquals() does not work, as the order of the edges is unknown
        // therefor, build an array of edge dump and make sure each entry has a match

        $edgesExpected = array();
        foreach ($expected->getEdges() as $edge) {
            $edgesExpected[] = $this->getEdgeDump($edge);
        }

        foreach ($actual->getEdges() as $edge) {
            $dump = $this->getEdgeDump($edge);

            $pos = \array_search($dump, $edgesExpected, true);
            if ($pos === false) {
                $this->fail('given edge ' . $dump . ' not found');
            } else {
                unset($edgesExpected[$pos]);
            }
        }
    }

    protected function assertVertexEquals(Vertex $expected, Vertex $actual)
    {
        $this->assertEquals($this->getVertexDump($expected), $this->getVertexDump($actual));
    }

    protected function assertEdgeEquals(Edge $expected, Edge $actual)
    {
        $this->assertEquals($this->getEdgeDump($expected), $this->getEdgeDump($actual));
    }

    private function getVertexDump(Vertex $vertex)
    {
        $ret = \get_class($vertex);

        $ret .= PHP_EOL . 'id: ' . $vertex->getId();
        $ret .= PHP_EOL . 'attributes: ' . \json_encode($vertex->getAttributeBag()->getAttributes());
        $ret .= PHP_EOL . 'balance: ' . $vertex->getBalance();
        $ret .= PHP_EOL . 'group: ' . $vertex->getGroup();

        return $ret;
    }

    private function getEdgeDump(Edge $edge)
    {
        $ret = \get_class($edge) . ' ';
        if ($edge instanceof Directed) {
            $ret .= $edge->getVertexStart()->getId() . ' -> ' . $edge->getVertexEnd()->getId();
        } else {
            $vertices = $edge->getVertices()->getIds();
            $ret .= $vertices[0] . ' -- ' . $vertices[1];
        }
        $ret .= PHP_EOL . 'flow: ' . $edge->getFlow();
        $ret .= PHP_EOL . 'capacity: ' . $edge->getCapacity();
        $ret .= PHP_EOL . 'weight: ' . $edge->getWeight();
        $ret .= PHP_EOL . 'attributes: ' . \json_encode($edge->getAttributeBag()->getAttributes());

        return $ret;
    }
}
