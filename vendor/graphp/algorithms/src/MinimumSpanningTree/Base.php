<?php

namespace Graphp\Algorithms\MinimumSpanningTree;

use Fhaculty\Graph\Edge\Base as Edge;
use Fhaculty\Graph\Graph;
use Fhaculty\Graph\Set\Edges;
use Graphp\Algorithms\Base as AlgorithmBase;
use SplPriorityQueue;

/**
 * Abstract base class for minimum spanning tree (MST) algorithms
 *
 * A minimum spanning tree of a graph is a subgraph that is a tree and connects
 * all the vertices together while minimizing the total sum of all edges'
 * weights.
 *
 * A spanning tree thus requires a connected graph (single connected component),
 * otherwise we can span multiple trees (spanning forest) within each component.
 * Because a null graph (a Graph with no vertices) is not considered connected,
 * it also can not contain a spanning tree.
 *
 * Most authors demand that the input graph has to be undirected, whereas this
 * library supports also directed and mixed graphs. The actual direction of the
 * edge will be ignored, only its incident vertices will be checked. This is
 * done in order to be consistent to how ConnectedComponents are checked.
 *
 * @link http://en.wikipedia.org/wiki/Minimum_Spanning_Tree
 * @link http://en.wikipedia.org/wiki/Spanning_Tree
 * @link http://mathoverflow.net/questions/120536/is-the-empty-graph-a-tree
 */
abstract class Base extends AlgorithmBase
{
    /**
     * create new resulting graph with only edges on minimum spanning tree
     *
     * @return Graph
     * @uses self::getGraph()
     * @uses self::getEdges()
     * @uses Graph::createGraphCloneEdges()
     */
    public function createGraph()
    {
        return $this->getGraph()->createGraphCloneEdges($this->getEdges());
    }

    /**
     * get all edges on minimum spanning tree
     *
     * @return Edges
     */
    abstract public function getEdges();

    /**
     * return reference to current Graph
     *
     * @return Graph
     */
    abstract protected function getGraph();

    /**
     * get total weight of minimum spanning tree
     *
     * @return float
     */
    public function getWeight()
    {
        return $this->getEdges()->getSumCallback(function (Edge $edge) {
            return $edge->getWeight();
        });
    }

    /**
     * helper method to add a set of Edges to the given set of sorted edges
     *
     * @param Edges            $edges
     * @param SplPriorityQueue $sortedEdges
     */
    protected function addEdgesSorted(Edges $edges, SplPriorityQueue $sortedEdges)
    {
        // For all edges
        foreach ($edges as $edge) {
            assert($edge instanceof Edge);
            // ignore loops (a->a)
            if (!$edge->isLoop()) {
                // Add edges with negative weight because of order in stl
                $sortedEdges->insert($edge, -$edge->getWeight());
            }
        }
    }
}
