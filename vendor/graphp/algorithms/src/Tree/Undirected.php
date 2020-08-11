<?php

namespace Graphp\Algorithms\Tree;

use Fhaculty\Graph\Edge\Undirected as UndirectedEdge;
use Fhaculty\Graph\Exception\UnexpectedValueException;
use Fhaculty\Graph\Set\Vertices;
use Fhaculty\Graph\Vertex;
use Graphp\Algorithms\Tree\Base as Tree;

/**
 * Undirected tree implementation
 *
 * An undirected tree is a connected Graph (single component) with no cycles.
 * Every undirected Tree is an undirected Graph, but not every undirected Graph
 * is an undirected Tree.
 *
 *    A
 *   / \
 *  B   C
 *     / \
 *    D   E
 *
 * Undirected trees do not have special root Vertices (like the above picture
 * might suggest). The above tree Graph can also be equivalently be pictured
 * like this:
 *
 *      C
 *     /|\
 *    / | \
 *   A  D  E
 *  /
 * B
 *
 * If you're looking for a tree with a designated root Vertex, use directed,
 * rooted trees (BaseDirected).
 *
 * @link http://en.wikipedia.org/wiki/Tree_%28graph_theory%29
 * @see BaseDirected if you're looking for directed, rooted trees
 */
class Undirected extends Tree
{
    /**
     * checks if this is a tree
     *
     * @return bool
     * @uses Vertices::isEmpty() to skip null Graphs (a Graph with no Vertices is *NOT* a valid tree)
     * @uses Vertices::getVertexFirst() to get get get random "root" Vertex to start search from
     * @uses self::getVerticesSubtreeRecursive() to count number of vertices connected to root
     */
    public function isTree()
    {
        if ($this->graph->getVertices()->isEmpty()) {
            return false;
        }

        // every vertex can represent a root vertex, so just pick one
        $root = $this->graph->getVertices()->getVertexFirst();

        $vertices = array();
        try {
            $this->getVerticesSubtreeRecursive($root, $vertices, null);
        } catch (UnexpectedValueException $e) {
            return false;
        }

        return (\count($vertices) === \count($this->graph->getVertices()));
    }

    /**
     * checks if the given $vertex is a leaf (outermost vertex with exactly one edge)
     *
     * @param Vertex $vertex
     * @return bool
     * @uses Degree::getDegreeVertex()
     */
    public function isVertexLeaf(Vertex $vertex)
    {
        return ($this->degree->getDegreeVertex($vertex) === 1);
    }

    /**
     * checks if the given $vertex is an internal vertex (inner vertex with at least 2 edges)
     *
     * @param Vertex $vertex
     * @return bool
     * @uses Degree::getDegreeVertex()
     */
    public function isVertexInternal(Vertex $vertex)
    {
        return ($this->degree->getDegreeVertex($vertex) >= 2);
    }

    /**
     * get subtree for given Vertex and ignore path to "parent" ignoreVertex
     *
     * @param Vertex      $vertex
     * @param Vertex[]    $vertices
     * @param Vertex|null $ignore
     * @throws UnexpectedValueException for cycles or directed edges (check isTree()!)
     * @uses self::getVerticesNeighbor()
     * @uses self::getVerticesSubtreeRecursive() to recurse into sub-subtrees
     */
    private function getVerticesSubtreeRecursive(Vertex $vertex, array &$vertices, Vertex $ignore = null)
    {
        if (isset($vertices[$vertex->getId()])) {
            // vertex already visited => must be a cycle
            throw new UnexpectedValueException('Vertex already visited');
        }
        $vertices[$vertex->getId()] = $vertex;

        foreach ($this->getVerticesNeighbor($vertex) as $vertexNeighboor) {
            if ($vertexNeighboor === $ignore) {
                // ignore source vertex only once
                $ignore = null;
                continue;
            }
            $this->getVerticesSubtreeRecursive($vertexNeighboor, $vertices, $vertex);
        }
    }

    /**
     * get neighbor vertices for given start vertex
     *
     * @param Vertex $vertex
     * @return Vertices (might include possible duplicates)
     * @throws UnexpectedValueException for directed edges
     * @uses Vertex::getEdges()
     * @uses Edge::getVertexToFrom()
     * @see Vertex::getVerticesEdge()
     */
    private function getVerticesNeighbor(Vertex $vertex)
    {
        $vertices = array();
        foreach ($vertex->getEdges() as $edge) {
            if (!$edge instanceof UndirectedEdge) {
                throw new UnexpectedValueException('Directed edge encountered');
            }
            $vertices[] = $edge->getVertexToFrom($vertex);
        }
        return new Vertices($vertices);
    }
}
