<?php

namespace Graphp\Algorithms\TravelingSalesmanProblem;

use Fhaculty\Graph\Graph;
use Fhaculty\Graph\Set\Edges;
use Graphp\Algorithms\MinimumSpanningTree\Kruskal as MstKruskal;
use Graphp\Algorithms\Search\BreadthFirst as SearchDepthFirst;

class MinimumSpanningTree extends Base
{
    /**
     * @var Graph
     */
    private $graph;

    public function __construct(Graph $inputGraph)
    {
        $this->graph = $inputGraph;
    }

    protected function getVertexStart()
    {
        return $this->graph->getVertices()->getVertexFirst();
    }

    protected function getGraph()
    {
        return $this->graph;
    }

    /**
     * @return Edges
     */
    public function getEdges()
    {
        $returnEdges = array();

        // Create minimum spanning tree
        $minimumSpanningTreeAlgorithm = new MstKruskal($this->graph);
        $minimumSpanningTree = $minimumSpanningTreeAlgorithm->createGraph();

        $alg = new SearchDepthFirst($minimumSpanningTree->getVertices()->getVertexFirst());
        // Depth first search in minmum spanning tree (for the eulerian path)

        $startVertex = NULL;
        $oldVertex = NULL;

        // connect vertices in order of the depth first search
        foreach ($alg->getVertices() as $vertex) {

            // get vertex from the original graph (not from the depth first search)
            $vertex = $this->graph->getVertex($vertex->getId());
                                                                                // need to clone the edge from the original graph, therefore i need the original edge
            if ($startVertex === NULL) {
                $startVertex = $vertex;
            } else {
                // get edge(s) to clone, multiple edges are possible (returns an array if undirected edge)
                assert($oldVertex !== null);
                $returnEdges[] = $oldVertex->getEdgesTo($vertex)->getEdgeFirst();
            }

            $oldVertex = $vertex;
        }

        // connect last vertex with start vertex
        // multiple edges are possible (returns an array if undirected edge)
        assert($startVertex !== null && $oldVertex !== null);
        $returnEdges[] = $oldVertex->getEdgesTo($startVertex)->getEdgeFirst();

        return new Edges($returnEdges);
    }
}
