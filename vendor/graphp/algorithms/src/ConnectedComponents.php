<?php

namespace Graphp\Algorithms;

use Fhaculty\Graph\Exception\InvalidArgumentException;
use Fhaculty\Graph\Exception\UnderflowException;
use Fhaculty\Graph\Graph;
use Fhaculty\Graph\Vertex;
use Graphp\Algorithms\Search\BreadthFirst as SearchBreadthFirst;

/**
 * Algorithm for working with connected components
 *
 * @link http://en.wikipedia.org/wiki/Connected_component_%28graph_theory%29
 * @link http://mathworld.wolfram.com/ConnectedGraph.html
 * @link http://math.stackexchange.com/questions/50551/is-the-empty-graph-connected
 */
class ConnectedComponents extends BaseGraph
{
    /**
     * create subgraph with all vertices connected to given vertex (i.e. the connected component of ths given vertex)
     *
     * @param  Vertex                   $vertex
     * @return Graph
     * @throws InvalidArgumentException if given vertex is not from same graph
     * @uses AlgorithmSearchBreadthFirst::getVertices()
     * @uses Graph::createGraphCloneVertices()
     */
    public function createGraphComponentVertex(Vertex $vertex)
    {
        if ($vertex->getGraph() !== $this->graph) {
            throw new InvalidArgumentException('This graph does not contain the given vertex');
        }

        return $this->graph->createGraphCloneVertices($this->createSearch($vertex)->getVertices());
    }

    /**
     *
     * @param Vertex $vertex
     * @return SearchBreadthFirst
     */
    private function createSearch(Vertex $vertex)
    {
        $alg = new SearchBreadthFirst($vertex);

        // follow into both directions (loosely connected)
        return $alg->setDirection(SearchBreadthFirst::DIRECTION_BOTH);
    }

    /**
     * check whether this graph consists of only a single component
     *
     * If a Graph consists of only a single component, it is said to be a
     * connected Graph, otherwise it's called a disconnected Graph.
     *
     * This method returns exactly the same result as checking
     * <pre>($this->getNumberOfComponents() === 1)</pre>. However, using this
     * method is faster than calling getNumberOfComponents(), as it only has to
     * count all vertices in one component to see if the graph consists of only
     * a single component.
     *
     * As such, a null Graph (a Graph with no vertices) is not considered
     * connected here.
     *
     * @return bool
     * @see self::getNumberOfComponents()
     */
    public function isSingle()
    {
        try {
            $vertex = $this->graph->getVertices()->getVertexFirst();
        } catch (UnderflowException $e) {
            // no first vertex => empty graph => has zero components
            return false;
        }
        $alg = $this->createSearch($vertex);

        return (\count($this->graph->getVertices()) === \count($alg->getVertices()));
    }

    /**
     * count number of connected components
     *
     * A null Graph (a Graph with no vertices) will return 0 components.
     *
     * @return int number of components
     * @uses Graph::getVertices()
     * @uses AlgorithmSearchBreadthFirst::getVertices()
     */
    public function getNumberOfComponents()
    {
        $visitedVertices = array();
        $components = 0;

        // for each vertices
        foreach ($this->graph->getVertices()->getMap() as $vid => $vertex) {
            // did I visit this vertex before?
            if (!isset($visitedVertices[$vid])) {

                // get all vertices of this component
                $newVertices = $this->createSearch($vertex)->getVertices()->getIds();

                ++$components;

                // mark the vertices of this component as visited
                foreach ($newVertices as $vid) {
                    $visitedVertices[$vid] = true;
                }
            }
        }

        // return number of components
        return $components;
    }

    /**
     * separate input graph into separate independant and unconnected graphs
     *
     * @return Graph[]
     * @uses Graph::getVertices()
     * @uses AlgorithmSearchBreadthFirst::getVertices()
     */
    public function createGraphsComponents()
    {
        $visitedVertices = array();
        $graphs = array();

        // for each vertices
        foreach ($this->graph->getVertices()->getMap() as $vid => $vertex) {
            // did I visit this vertex before?
            if (!isset($visitedVertices[$vid])) {

                $alg = $this->createSearch($vertex);
                // get all vertices of this component
                $newVertices = $alg->getVertices();

                // mark the vertices of this component as visited
                foreach ($newVertices->getIds() as $vid) {
                    $visitedVertices[$vid] = true;
                }

                $graphs[] = $this->graph->createGraphCloneVertices($newVertices);
            }
        }

        return $graphs;
    }
}
