<?php

namespace Graphp\Algorithms\Tree;

use Fhaculty\Graph\Exception\UnderflowException;
use Fhaculty\Graph\Exception\UnexpectedValueException;
use Fhaculty\Graph\Set\Vertices;
use Fhaculty\Graph\Vertex;
use Graphp\Algorithms\Tree\Base as Tree;

/**
 * Abstract algorithm base class for working with directed, rooted trees
 *
 * Directed trees have an designated root Vertex, which is the uppermost Vertex.
 * Every other Vertex is either a directed child of this root Vertex or an
 * indirect descendant (recursive child).
 *
 * There are two common implementations of directed trees:
 *
 * - Usual OutTree implementation where Edges "point away" from root Vertex
 *
 *          ROOT
 *          /  \
 *    A <--/    \--> B
 *                   \
 *                    \--> C
 *
 * - Alternative InTree implementation where Edges "point towards" root Vertex
 *
 *         ROOT
 *         ^  ^
 *        /    \
 *       A      B
 *              ^
 *               \
 *                C
 *
 * It's your choice on how to direct the edges, but make sure they all point in
 * the "same direction", or it will not be a valid tree anymore. However your
 * decision may be, in the above example, ROOT is always the root Vertex,
 * B is the parent of "C" and A, B are the children of ROOT.
 *
 * For performance reasons, except for `isTree()`, none of the below methods
 * check if the given Graph is actually a valid tree. So make sure to verify
 * `isTree()` returns `true` before relying on any of the methods.
 *
 * @link http://en.wikipedia.org/wiki/Arborescence_%28graph_theory%29
 * @link http://en.wikipedia.org/wiki/Spaghetti_stack
 * @see OutTree usual implementation where Edges "point away" from root vertex
 * @see InTree alternative implementation where Edges "point towards" root vertex
 */
abstract class BaseDirected extends Tree
{
    /**
     * get root vertex for this in-tree
     *
     * @return Vertex
     * @throws UnderflowException if given graph is empty or no possible root candidate was found (check isTree()!)
     * @uses Graph::getVertices() to iterate over each Vertex
     * @uses self::isVertexPossibleRoot() to check if any Vertex is a possible root candidate
     */
    public function getVertexRoot()
    {
        foreach ($this->graph->getVertices() as $vertex) {
            if ($this->isVertexPossibleRoot($vertex)) {
                return $vertex;
            }
        }
        throw new UnderflowException('No possible root found. Either empty graph or no Vertex with proper degree found.');
    }

    /**
     * checks if this is a tree
     *
     * @return bool
     * @uses self::getVertexRoot() to get root Vertex to start search from
     * @uses self::getVerticesSubtree() to count number of vertices connected to root
     */
    public function isTree()
    {
        try {
            $root = $this->getVertexRoot();
        } catch (UnderflowException $e) {
            return false;
        } catch (UnexpectedValueException $e) {
            return false;
        }

        try {
            $num = \count($this->getVerticesSubtree($root));
        } catch (UnexpectedValueException $e) {
            return false;
        }

        // check number of vertices reachable from root should match total number of vertices
        return ($num === \count($this->graph->getVertices()));
    }

    /**
     * get parent vertex for given $vertex
     *
     * @param Vertex $vertex
     * @throws UnderflowException if vertex has no parent (is a root vertex)
     * @throws UnexpectedValueException if vertex has more than one possible parent (check isTree()!)
     * @return Vertex
     * @uses self::getVerticesParents() to get array of parent vertices
     */
    public function getVertexParent(Vertex $vertex)
    {
        $parents = $this->getVerticesParent($vertex);
        if (\count($parents) !== 1) {
            if ($parents->isEmpty()) {
                throw new UnderflowException('No parents for given vertex found');
            } else {
                throw new UnexpectedValueException('More than one parent');
            }
        }
        return $parents->getVertexFirst();
    }

    /**
     * get array of child vertices for given $vertex
     *
     * @param Vertex $vertex
     * @return Vertices
     * @throws UnexpectedValueException if the given $vertex contains invalid / parallel edges (check isTree()!)
     */
    abstract public function getVerticesChildren(Vertex $vertex);

    /**
     * internal helper to get all parents vertices
     *
     * a valid tree vertex only ever has a single parent, except for the root,
     * which has none.
     *
     * @param Vertex $vertex
     * @return Vertices
     * @throws UnexpectedValueException if the given $vertex contains invalid / parallel edges (check isTree()!)
     */
    abstract protected function getVerticesParent(Vertex $vertex);

    /**
     * check if given vertex is a possible root (i.e. has no parent)
     *
     * @param Vertex $vertex
     * @return bool
     * @uses self::getVerticesParent()
     */
    protected function isVertexPossibleRoot(Vertex $vertex)
    {
        return (\count($this->getVerticesParent($vertex)) === 0);
    }

    /**
     * checks if the given $vertex is a leaf (outermost vertex with no children)
     *
     * @param Vertex $vertex
     * @return bool
     * @uses self::getVerticesChildren() to check given vertex has no children
     */
    public function isVertexLeaf(Vertex $vertex)
    {
        return (\count($this->getVerticesChildren($vertex)) === 0);
    }

    /**
     * checks if the given $vertex is an internal vertex (has children and is not root)
     *
     * @param Vertex $vertex
     * @return bool
     * @uses self::getVerticesParent() to check given vertex has a parent (is not root)
     * @uses self::getVerticesChildren() to check given vertex has children (is not a leaf)
     * @see \Graphp\Algorithms\Tree\Base::isVertexInternal() for more information
     */
    public function isVertexInternal(Vertex $vertex)
    {
        return (!$this->getVerticesParent($vertex)->isEmpty() && !$this->getVerticesChildren($vertex)->isEmpty());
    }

    /**
     * get degree of tree (maximum number of children)
     *
     * @return int
     * @throws UnderflowException for empty graphs
     * @uses Graph::getVertices()
     * @uses self::getVerticesChildren()
     */
    public function getDegree()
    {
        $max = null;
        foreach ($this->graph->getVertices() as $vertex) {
            $num = \count($this->getVerticesChildren($vertex));
            if ($max === null || $num > $max) {
                $max = $num;
            }
        }
        if ($max === null) {
            throw new UnderflowException('No vertices found');
        }
        return $max;
    }

    /**
     * get depth of given $vertex (number of edges between root vertex)
     *
     * root has depth zero
     *
     * @param Vertex $vertex
     * @return int
     * @throws UnderflowException for empty graphs
     * @throws UnexpectedValueException if there's no path to root node (check isTree()!)
     * @uses self::getVertexRoot()
     * @uses self::getVertexParent() for each step
     */
    public function getDepthVertex(Vertex $vertex)
    {
        $root = $this->getVertexRoot();

        $depth = 0;
        while ($vertex !== $root) {
            $vertex = $this->getVertexParent($vertex);
            ++$depth;
        }
        return $depth;
    }

    /**
     * get height of this tree (longest downward path to a leaf)
     *
     * a single vertex graph has height zero
     *
     * @return int
     * @throws UnderflowException for empty graph
     * @uses self::getVertexRoot()
     * @uses self::getHeightVertex()
     */
    public function getHeight()
    {
        return $this->getHeightVertex($this->getVertexRoot());
    }

    /**
     * get height of given vertex (longest downward path to a leaf)
     *
     * leafs has height zero
     *
     * @param Vertex $vertex
     * @return int
     * @uses self::getVerticesChildren() to get children of given vertex
     * @uses self::getHeightVertex() to recurse into sub-children
     */
    public function getHeightVertex(Vertex $vertex)
    {
        $max = 0;
        foreach ($this->getVerticesChildren($vertex) as $vertex) {
            $height = $this->getHeightVertex($vertex) + 1;
            if ($height > $max) {
                $max = $height;
            }
        }
        return $max;
    }

    /**
     * get all vertices that are in the subtree of the given $vertex (which IS included)
     *
     * root vertex will return the whole tree, leaf vertices will only return themselves
     *
     * @param Vertex $vertex
     * @throws UnexpectedValueException if there are invalid edges (check isTree()!)
     * @return Vertices
     * @uses self::getVerticesSubtreeRecursive()
     * @uses self::getVerticesSubtree()
     */
    public function getVerticesSubtree(Vertex $vertex)
    {
        $vertices = array();
        $this->getVerticesSubtreeRecursive($vertex, $vertices);

        return new Vertices($vertices);
    }

    /**
     * helper method to get recursively get subtree for given $vertex
     *
     * @param Vertex   $vertex
     * @param Vertex[] $vertices
     * @throws UnexpectedValueException if multiple links were found to the given edge (check isTree()!)
     * @uses self::getVerticesChildren()
     * @uses self::getVerticesSubtreeRecursive() to recurse into subtrees
     */
    private function getVerticesSubtreeRecursive(Vertex $vertex, array &$vertices)
    {
        $vid = $vertex->getId();
        if (isset($vertices[$vid])) {
            throw new UnexpectedValueException('Multiple links found');
        }
        $vertices[$vid] = $vertex;

        foreach ($this->getVerticesChildren($vertex) as $vertexChild) {
            $this->getVerticesSubtreeRecursive($vertexChild, $vertices);
        }
    }

    /**
     * get all vertices below the given $vertex (which is NOT included)
     *
     * think of this as the recursive version of getVerticesChildren()
     *
     * @param Vertex $vertex
     * @return Vertices
     * @throws UnexpectedValueException if there are invalid edges (check isTree()!)
     * @uses self::getVerticesSubtree()
     */
    public function getVerticesDescendant(Vertex $vertex)
    {
        $vertices = $this->getVerticesSubtree($vertex)->getMap();
        unset($vertices[$vertex->getId()]);

        return new Vertices($vertices);
    }
}
