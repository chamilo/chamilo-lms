<?php

namespace Graphp\Algorithms\TravelingSalesmanProblem;

use Fhaculty\Graph\Edge\Base as Edge;
use Fhaculty\Graph\Exception\UnexpectedValueException;
use Fhaculty\Graph\Exception\UnderflowException;
use Fhaculty\Graph\Graph;
use Fhaculty\Graph\Set\Edges;
use Fhaculty\Graph\Vertex;
use Graphp\Algorithms\TravelingSalesmanProblem\MinimumSpanningTree as AlgorithmTspMst;

class Bruteforce extends Base
{
    /**
     *
     * @var Graph
     */
    private $graph;

    /**
     * best weight so for (used for branch-and-bound)
     *
     * @var number|NULL
     */
    private $bestWeight;

    /**
     * reference to start vertex
     *
     * @var Vertex
     */
    private $startVertex;

    /**
     * total number of edges needed
     *
     * @var int
     */
    private $numEdges;

    /**
     * upper limit to use for branch-and-bound (BNB)
     *
     * @var float|NULL
     * @see self::setUpperLimit()
     */
    private $upperLimit = NULL;

    /**
     * whether to use branch-and-bound
     *
     * simply put, there's no valid reason why anybody would want to turn off this flag
     *
     * @var bool
     */
    private $branchAndBound = true;

    /**
     *
     * @param Graph $graph
     */
    public function __construct(Graph $graph)
    {
        $this->graph = $graph;
    }

    /**
     * explicitly set upper limit to use for branch-and-bound
     *
     * this method can be used to optimize the algorithm by providing an upper
     * bound of when to stop branching any further.
     *
     * @param  float $limit
     * @return self  $this (chainable)
     */
    public function setUpperLimit($limit)
    {
        $this->upperLimit = $limit;

        return $this;
    }

    /**
     * automatically sets upper limit to use for branch-and-bound from the MST heuristic
     *
     * @return self $this (chainable)
     * @uses AlgorithmTspMst
     */
    public function setUpperLimitMst()
    {
        $alg = new AlgorithmTspMst($this->graph);
        $this->upperLimit = $alg->getWeight();

        return $this;
    }

    protected function getVertexStart()
    {
        // actual start doesn't really matter as we're only considering complete graphs here
        return $this->graph->getVertices()->getVertexFirst();
    }

    protected function getGraph()
    {
        return $this->graph;
    }

    /**
     * get resulting (first) best circle of edges connecting all vertices
     *
     * @throws \Exception on error
     * @return Edges
     */
    public function getEdges()
    {
        $this->numEdges = \count($this->graph->getVertices());
        if ($this->numEdges < 3) {
            throw new UnderflowException('Needs at least 3 vertices');
        }

        // numEdges 3-12 should work

        $this->bestWeight = $this->upperLimit;
        $this->startVertex = $this->getVertexStart();

        $result = $this->step($this->startVertex,
                              0,
                              array(),
                              array()
                  );

        if ($result === NULL) {
            throw new UnexpectedValueException('No resulting solution for TSP found');
        }

        return new Edges($result);
    }

    /**
     *
     * @param  Vertex    $vertex          current point-of-view
     * @param  number    $totalWeight     total weight (so far)
     * @param  bool[] $visitedVertices
     * @param  Edge[]    $visitedEdges
     * @return Edge[]|null
     */
    private function step(Vertex $vertex, $totalWeight, array $visitedVertices, array $visitedEdges)
    {
        // stop recursion if best result is exceeded (branch and bound)
        if ($this->branchAndBound && $this->bestWeight !== NULL && $totalWeight >= $this->bestWeight) {
            return NULL;
        }
        // kreis geschlossen am Ende
        if ($vertex === $this->startVertex && \count($visitedEdges) === $this->numEdges) {
            // new best result
            $this->bestWeight = $totalWeight;

            return $visitedEdges;
        }

        // only visit each vertex once
        if (isset($visitedVertices[$vertex->getId()])) {
            return NULL;
        }
        $visitedVertices[$vertex->getId()] = true;

        $bestResult = NULL;

        // weiter verzweigen in alle vertices
        foreach ($vertex->getEdgesOut() as $edge) {
            // get target vertex of this edge
            $target = $edge->getVertexToFrom($vertex);

            $weight = $edge->getWeight();
            if ($weight < 0) {
                throw new UnexpectedValueException('Edge with negative weight "' . $weight . '" not supported');
            }

            $result = $this->step($target,
                                  $totalWeight + $weight,
                                  $visitedVertices,
                                  \array_merge($visitedEdges, array($edge))
                      );

            // new result found
            if ($result !== NULL) {
                // branch and bound enabled (default): returned result MUST be the new best result
                if($this->branchAndBound ||
                   // this is the first result, just use it anyway
                   $bestResult === NULL ||
                   // this is the new best result
                   $this->sumEdges($result) < $this->sumEdges($bestResult)){
                    $bestResult = $result;
                }
            }
        }

        return $bestResult;
    }

    /**
     * get sum of weight of given edges
     *
     * no need to optimize this further, as it's only evaluated if branchAndBound is disabled and
     * there's no valid reason why anybody would want to do so.
     *
     * @param  Edge[] $edges
     * @return float
     */
    private function sumEdges(array $edges)
    {
        $sum = 0;
        foreach ($edges as $edge) {
            $sum += $edge->getWeight();
        }

        return $sum;
    }
}
