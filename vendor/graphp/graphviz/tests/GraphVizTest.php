<?php

use Fhaculty\Graph\Exception\RuntimeException;
use Fhaculty\Graph\Exporter\Image;
use Fhaculty\Graph\Vertex;
use Fhaculty\Graph\Exception\OverflowException;
use Fhaculty\Graph\Exception\InvalidArgumentException;
use Fhaculty\Graph\Graph;
use Graphp\GraphViz\GraphViz;

class GraphVizTest extends TestCase
{
    private $graphViz;

    public function setUp()
    {
        $this->graphViz = new GraphViz();
    }

    public function testGraphEmpty()
    {
        $graph = new Graph();

        $expected = <<<VIZ
graph G {
}

VIZ;

        $this->assertEquals($expected, $this->graphViz->createScript($graph));
    }

    public function testGraphIsolatedVertices()
    {
        $graph = new Graph();
        $graph->createVertex('a');
        $graph->createVertex('b');

        $expected = <<<VIZ
graph G {
  "a"
  "b"
}

VIZ;

        $this->assertEquals($expected, $this->graphViz->createScript($graph));
    }

    public function testEscaping()
    {
        $graph = new Graph();
        $graph->createVertex('a');
        $graph->createVertex('b¹²³ is; ok\\ay, "right"?');
        $graph->createVertex(3);
        $graph->createVertex(4)->setAttribute('graphviz.label', 'normal');
        $graph->createVertex(5)->setAttribute('graphviz.label', GraphViz::raw('<raw>'));


        $expected = <<<VIZ
graph G {
  "a"
  "b¹²³ is; ok\\\\ay, &quot;right&quot;?"
  3
  4 [label="normal"]
  5 [label=<raw>]
}

VIZ;

        $this->assertEquals($expected, $this->graphViz->createScript($graph));
    }

    public function testGraphDirected()
    {
        $graph = new Graph();
        $graph->createVertex('a')->createEdgeTo($graph->createVertex('b'));

        $expected = <<<VIZ
digraph G {
  "a" -> "b"
}

VIZ;

        $this->assertEquals($expected, $this->graphViz->createScript($graph));
    }

    public function testGraphMixed()
    {
        // a -> b -- c
        $graph = new Graph();
        $graph->createVertex('a')->createEdgeTo($graph->createVertex('b'));
        $graph->createVertex('c')->createEdge($graph->getVertex('b'));

        $expected = <<<VIZ
digraph G {
  "a" -> "b"
  "c" -> "b" [dir="none"]
}

VIZ;

        $this->assertEquals($expected, $this->graphViz->createScript($graph));
    }


    public function testGraphUndirectedWithIsolatedVerticesFirst()
    {
        // a -- b -- c   d
        $graph = new Graph();
        $graph->createVertices(array('a', 'b', 'c', 'd'));
        $graph->getVertex('a')->createEdge($graph->getVertex('b'));
        $graph->getVertex('b')->createEdge($graph->getVertex('c'));

        $expected = <<<VIZ
graph G {
  "d"
  "a" -- "b"
  "b" -- "c"
}

VIZ;

        $this->assertEquals($expected, $this->graphViz->createScript($graph));
    }

    public function testVertexLabels()
    {
        $graph = new Graph();
        $graph->createVertex('a')->setBalance(1);
        $graph->createVertex('b')->setBalance(0);
        $graph->createVertex('c')->setBalance(-1);
        $graph->createVertex('d')->setAttribute('graphviz.label', 'test');
        $graph->createVertex('e')->setBalance(2)->setAttribute('graphviz.label', 'unnamed');

        $expected = <<<VIZ
graph G {
  "a" [label="a (+1)"]
  "b" [label="b (0)"]
  "c" [label="c (-1)"]
  "d" [label="test"]
  "e" [label="unnamed (+2)"]
}

VIZ;

        $this->assertEquals($expected, $this->graphViz->createScript($graph));
    }

    public function testEdgeLayoutAtributes()
    {
        $graph = new Graph();
        $graph->createVertex('1a')->createEdge($graph->createVertex('1b'));
        $graph->createVertex('2a')->createEdge($graph->createVertex('2b'))->setAttribute('graphviz.numeric', 20);
        $graph->createVertex('3a')->createEdge($graph->createVertex('3b'))->setAttribute('graphviz.textual', "forty");
        $graph->createVertex('4a')->createEdge($graph->createVertex('4b'))->getAttributeBag()->setAttributes(array('graphviz.1' => 1, 'graphviz.2' => 2));
        $graph->createVertex('5a')->createEdge($graph->createVertex('5b'))->getAttributeBag()->setAttributes(array('graphviz.a' => 'b', 'graphviz.c' => 'd'));

        $expected = <<<VIZ
graph G {
  "1a" -- "1b"
  "2a" -- "2b" [numeric=20]
  "3a" -- "3b" [textual="forty"]
  "4a" -- "4b" [1=1 2=2]
  "5a" -- "5b" [a="b" c="d"]
}

VIZ;

        $this->assertEquals($expected, $this->graphViz->createScript($graph));
    }

    public function testEdgeLabels()
    {
        $graph = new Graph();
        $graph->createVertex('1a')->createEdge($graph->createVertex('1b'));
        $graph->createVertex('2a')->createEdge($graph->createVertex('2b'))->setWeight(20);
        $graph->createVertex('3a')->createEdge($graph->createVertex('3b'))->setCapacity(30);
        $graph->createVertex('4a')->createEdge($graph->createVertex('4b'))->setFlow(40);
        $graph->createVertex('5a')->createEdge($graph->createVertex('5b'))->setFlow(50)->setCapacity(60);
        $graph->createVertex('6a')->createEdge($graph->createVertex('6b'))->setFlow(60)->setCapacity(70)->setWeight(80);
        $graph->createVertex('7a')->createEdge($graph->createVertex('7b'))->setFlow(70)->setAttribute('graphviz.label', 'prefixed');

        $expected = <<<VIZ
graph G {
  "1a" -- "1b"
  "2a" -- "2b" [label=20]
  "3a" -- "3b" [label="0/30"]
  "4a" -- "4b" [label="40/∞"]
  "5a" -- "5b" [label="50/60"]
  "6a" -- "6b" [label="60/70/80"]
  "7a" -- "7b" [label="prefixed 70/∞"]
}

VIZ;

        $this->assertEquals($expected, $this->graphViz->createScript($graph));
    }
}
