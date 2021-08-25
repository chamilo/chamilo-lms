<?php

namespace Graphp\Algorithms\Tree;

use Fhaculty\Graph\Graph;
use Fhaculty\Graph\Set\Vertices;
use Fhaculty\Graph\Vertex;
use Graphp\Algorithms\BaseGraph;
use Graphp\Algorithms\Degree;

/**
 * Abstract base class for tree algorithms
 *
 * This abstract base class provides the base interface for working with
 * graphs that represent a tree.
 *
 * A tree is a connected Graph (single component) with no cycles. Every Tree is
 * a Graph, but not every Graph is a Tree. A null Graph (a Graph with no Vertices
 * and thus no Edges) is *NOT* considered a valid Tree, as it is not considered
 * connected (@see ConnectedComponents and @link)
 *
 *    A
 *   / \
 *  B   C
 *     / \
 *    D   E
 *
 * Special cases are undirected trees (like the one pictured above), handled via
 * Tree\Undirected and directed, rooted trees (InTree and OutTree), handled via
 * Tree\BaseDirected.
 *
 * @link http://en.wikipedia.org/wiki/Tree_%28graph_theory%29
 * @link http://en.wikipedia.org/wiki/Tree_%28data_structure%29
 * @link http://mathoverflow.net/questions/120536/is-the-empty-graph-a-tree
 * @see Undirected for an implementation of these algorithms on (undirected) trees
 * @see BaseDirected for an abstract implementation of these algorithms on directed, rooted trees
 */
abstract class Base extends BaseGraph
{
    /**
     * @var Degree
     */
    protected $degree;

    public function __construct(Graph $graph)
    {
        parent::__construct($graph);

        $this->degree = new Degree($graph);
    }

    /**
     * checks whether the given graph is actually a tree
     *
     * @return bool
     */
    abstract public function isTree();

    /**
     * checks if the given $vertex is a leaf (outermost vertext)
     *
     * leaf vertex is also known as leaf node, external node or terminal node
     *
     * @param Vertex $vertex
     * @return bool
     */
    abstract public function isVertexLeaf(Vertex $vertex);

    /**
     * checks if the given $vertex is an internal vertex (somewhere in the "middle" of the tree)
     *
     * internal vertex is also known as inner node (inode) or branch node
     *
     * @param Vertex $vertex
     * @return bool
     */
    abstract public function isVertexInternal(Vertex $vertex);

    /**
     * get array of leaf vertices (outermost vertices with no children)
     *
     * @return Vertices
     * @uses Graph::getVertices()
     * @uses self::isVertexLeaf()
     */
    public function getVerticesLeaf()
    {
        return $this->graph->getVertices()->getVerticesMatch(array($this, 'isVertexLeaf'));
    }

    /**
     * get array of internal vertices
     *
     * @return Vertices
     * @uses Graph::getVertices()
     * @uses self::isVertexInternal()
     */
    public function getVerticesInternal()
    {
        return $this->graph->getVertices()->getVerticesMatch(array($this, 'isVertexInternal'));
    }
}
