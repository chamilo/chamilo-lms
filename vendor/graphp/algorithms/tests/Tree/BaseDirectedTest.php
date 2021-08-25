<?php

use Fhaculty\Graph\Graph;
use Fhaculty\Graph\Set\Vertices;
use Graphp\Algorithms\Tree\BaseDirected;

abstract class BaseDirectedTest extends TestCase
{
    /**
     *
     * @param Graph $graph
     * @return BaseDirected
     */
    abstract protected function createTreeAlg(Graph $graph);

    /**
     * @return Graph
     */
    abstract protected function createGraphNonTree();

    /**
     * @return Graph
     */
    abstract protected function createGraphTree();

    /**
     * @return Graph
     */
    abstract protected function createGraphParallelEdge();

    public function testNullGraph()
    {
        $graph = new Graph();

        $tree = $this->createTreeAlg($graph);
        $this->assertFalse($tree->isTree());
        $this->assertTrue($tree->getVerticesLeaf()->isEmpty());
        $this->assertTrue($tree->getVerticesInternal()->isEmpty());

        return $tree;
    }

    /**
     * @param BaseDirected $tree
     * @depends testNullGraph
     * @expectedException UnderflowException
     */
    public function testEmptyGraphDoesNotHaveRootVertex(BaseDirected $tree)
    {
        $tree->getVertexRoot();
    }

    /**
     * @param BaseDirected $tree
     * @depends testNullGraph
     * @expectedException UnderflowException
     */
    public function testEmptyGraphDoesNotHaveDegree(BaseDirected $tree)
    {
        $tree->getDegree();
    }

    /**
     * @param BaseDirected $tree
     * @depends testNullGraph
     * @expectedException UnderflowException
     */
    public function testEmptyGraphDoesNotHaveHeight(BaseDirected $tree)
    {
        $tree->getHeight();
    }

    public function testGraphTree()
    {
        $graph = $this->createGraphTree();
        $root = $graph->getVertices()->getVertexFirst();

        $nonRoot = $graph->getVertices()->getMap();
        unset($nonRoot[$root->getId()]);
        $nonRoot = new Vertices($nonRoot);

        $c1 = $nonRoot->getVertexFirst();

        $tree = $this->createTreeAlg($graph);

        $this->assertTrue($tree->isTree());
        $this->assertSame($root, $tree->getVertexRoot());
        $this->assertSame($graph->getVertices()->getVector(), $tree->getVerticesSubtree($root)->getVector());
        $this->assertSame($nonRoot->getVector(), $tree->getVerticesChildren($root)->getVector());
        $this->assertSame($nonRoot->getVector(), $tree->getVerticesDescendant($root)->getVector());
        $this->assertSame($nonRoot->getVector(), $tree->getVerticesLeaf()->getVector());
        $this->assertSame(array(), $tree->getVerticesInternal()->getVector());
        $this->assertSame($root, $tree->getVertexParent($c1));
        $this->assertSame(array(), $tree->getVerticesChildren($c1)->getVector());
        $this->assertSame(array(), $tree->getVerticesDescendant($c1)->getVector());
        $this->assertSame(array($c1), $tree->getVerticesSubtree($c1)->getVector());
        $this->assertEquals(2, $tree->getDegree());
        $this->assertEquals(0, $tree->getDepthVertex($root));
        $this->assertEquals(1, $tree->getDepthVertex($c1));
        $this->assertEquals(1, $tree->getHeight());
        $this->assertEquals(1, $tree->getHeightVertex($root));
        $this->assertEquals(0, $tree->getHeightvertex($c1));

        return $tree;
    }

    /**
     *
     * @param BaseDirected $tree
     * @depends testGraphTree
     * @expectedException UnderflowException
     */
    public function testGraphTreeRootDoesNotHaveParent(BaseDirected $tree)
    {
        $root = $tree->getVertexRoot();
        $tree->getVertexParent($root);
    }

    public function testNonTree()
    {
        $graph = $this->createGraphNonTree();

        $tree = $this->createTreeAlg($graph);

        $this->assertFalse($tree->isTree());
    }

    /**
     * @expectedException UnexpectedValueException
     */
    public function testNonTreeVertexHasMoreThanOneParent()
    {
        $graph = $this->createGraphNonTree();

        $tree = $this->createTreeAlg($graph);

        $tree->getVertexParent($graph->getVertex('v3'));
    }

    public function testGraphWithParallelEdgeIsNotTree()
    {
        $graph = $this->createGraphParallelEdge();

        $tree = $this->createTreeAlg($graph);

        $this->assertFalse($tree->isTree());
    }

    public function testGraphWithLoopIsNotTree()
    {
        // v1 -> v1
        $graph = new Graph();
        $graph->createVertex('v1')->createEdgeTo($graph->getVertex('v1'));

        $tree = $this->createTreeAlg($graph);

        $this->assertFalse($tree->isTree());
    }

    /**
     * @expectedException UnexpectedValueException
     */
    public function testGraphWithLoopCanNotGetSubgraph()
    {
        // v1 -> v1
        $graph = new Graph();
        $graph->createVertex('v1')->createEdgeTo($graph->getVertex('v1'));

        $tree = $this->createTreeAlg($graph);

        $tree->getVerticesSubtree($graph->getVertex('v1'));
    }

    public function testGraphWithUndirectedEdgeIsNotTree()
    {
        // v1 -- v2
        $graph = new Graph();
        $graph->createVertex('v1')->createEdge($graph->createVertex('v2'));

        $tree = $this->createTreeAlg($graph);

        $this->assertFalse($tree->isTree());
    }

    public function testGraphWithMixedEdgesIsNotTree()
    {
        // v1 -> v2 -- v3 -> v4
        $graph = new Graph();
        $graph->createVertex('v1')->createEdgeTo($graph->createVertex('v2'));
        $graph->getVertex('v2')->createEdge($graph->createVertex('v3'));
        $graph->getVertex('v3')->createEdgeTo($graph->createVertex('v4'));

        $tree = $this->createTreeAlg($graph);

        $this->assertFalse($tree->isTree());
    }
}
