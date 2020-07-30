<?php

namespace Graphp\Algorithms\Property;

use Fhaculty\Graph\Walk;
use Graphp\Algorithms\Base as BaseAlgorithm;
use Graphp\Algorithms\Loop as AlgorithmLoop;

/**
 * Simple algorithms for working with Walk properties
 *
 * @see GraphProperty
 */
class WalkProperty extends BaseAlgorithm
{
    /**
     * the Walk to operate on
     *
     * @var Walk
     */
    protected $walk;

    /**
     * instantiate new WalkProperty algorithm
     *
     * @param Walk $walk
     */
    public function __construct(Walk $walk)
    {
        $this->walk = $walk;
    }

    /**
     * checks whether walk is a cycle (i.e. source vertex = target vertex)
     *
     * A cycle is also known as a closed path, a walk that is NOT a cycle is
     * also known as an open path.
     *
     * A walk with no edges is not considered a cycle. The shortest possible
     * cycle is a single loop edge:
     *
     * 1--\
     * ^  |
     * \--/
     *
     * The following Walk is also considered a valid cycle:
     *
     *      /->3--\
     *      |     |
     * 1 -> 2 -\  |
     * ^    ^  |  |
     * |    \--/  |
     * |          |
     * \----------/
     *
     * @return bool
     * @link http://en.wikipedia.org/wiki/Cycle_%28graph_theory%29
     * @see self::isCircuit()
     * @see self::isLoop()
     */
    public function isCycle()
    {
        $vertices = $this->walk->getVertices();
        return ($vertices->getVertexFirst() === $vertices->getVertexLast() && !$this->walk->getEdges()->isEmpty());
    }

    /**
     * checks whether this walk is a circuit (i.e. a cycle with no duplicate edges)
     *
     * A circuit is also known as a closed (=cycle) trail (=path), that has at
     * least one edge.
     *
     * The following Walk is considered both a valid cycle and a valid circuit:
     *
     * 1 -> 2 -> 3 -\
     * ^            |
     * |            |
     * \------------/
     *
     * The following Walk is also considered both a valid cycle and a valid circuit:
     *
     *      /->3--\
     *      |     |
     * 1 -> 2 -\  |
     * ^    ^  |  |
     * |    \--/  |
     * |          |
     * \----------/
     *
     * The later circuit walk can be expressed by its Vertex IDs as
     * "1, 2, 2, 3, 1". If however, the inner loop would be "walked along"
     * several times, the resulting walk would be expressed as
     * "1, 2, 2, 2, 3, 1", which would still be a valid cycle, but NOT a valid
     * circuit anymore.
     *
     * @return bool
     * @link http://www.proofwiki.org/wiki/Definition:Circuit
     * @uses self::isCycle()
     * @uses self::isPath()
     */
    public function isCircuit()
    {
        return ($this->isCycle() && $this->isPath());
    }

    /**
     * checks whether walk is a path (i.e. does not contain any duplicate edges)
     *
     * A path Walk is also known as a trail.
     *
     * @return bool
     * @uses self::hasArrayDuplicates()
     * @link http://www.proofwiki.org/wiki/Definition:Trail
     */
    public function isPath()
    {
        return !$this->hasArrayDuplicates($this->walk->getEdges()->getVector());
    }

    /**
     * checks whether walk contains a cycle (i.e. contains a duplicate vertex)
     *
     * A walk that CONTAINS a cycle does not neccessarily have to BE a cycle.
     * Conversely, a Walk that *is* a cycle, automatically always *contains* a
     * cycle.
     *
     * The following Walk is NOT a cycle, but it *contains* a valid cycle:
     *
     *      /->4
     *      |
     * 1 -> 2 -> 3 -\
     *      ^       |
     *      \-------/
     *
     * @return bool
     * @uses self::hasArrayDuplicates()
     * @see self::isCycle()
     */
    public function hasCycle()
    {
        return $this->hasArrayDuplicates($this->walk->getVertices()->getVector());
    }

    /**
     * checks whether this walk IS a loop (single edge connecting vertex A with vertex A again)
     *
     * A loop is the simplest possible cycle. As such, each loop is also a
     * cycle. Accordingly, every Walk that *is* a loop, automatically also *is*
     * a cycle and automatically *contains* a loop and automatically *contains*
     * a cycle.
     *
     * The following Walk represents a simple (directed) loop:
     *
     * 1--\
     * ^  |
     * \--/
     *
     * @return bool
     * @uses self::isCycle()
     * @see self::hasLoop()
     */
    public function isLoop()
    {
        return (\count($this->walk->getEdges()) === 1 && $this->isCycle());
    }

    /**
     * checks whether this walk HAS a loop (single edge connecting vertex A with vertex A again)
     *
     * The following Walk is NOT a valid loop, but it contains a valid loop:
     *
     *      /->3
     *      |
     * 1 -> 2 -\
     *      ^  |
     *      \--/
     *
     * @return bool
     * @uses AlgorithmLoop::hasLoop()
     * @see self::isLoop()
     */
    public function hasLoop()
    {
        $alg = new AlgorithmLoop($this->walk);

        return $alg->hasLoop();
    }

    /**
     * checks whether this walk is a digon (a pair of parallel edges in a multigraph or a pair of antiparallel edges in a digraph)
     *
     * A digon is a cycle connecting exactly two distinct vertices with exactly
     * two distinct edges.
     *
     * The following Graph represents a digon in an undirected Graph:
     *
     *  /--\
     * 1    2
     *  \--/
     *
     * The following Graph represents a digon as a set of antiparallel directed
     * Edges in a directed Graph:
     *
     * 1 -> 2
     * ^    |
     * |    |
     * \----/
     *
     * @return bool
     * @uses self::hasArrayDuplicates()
     * @uses self::isCycle()
     */
    public function isDigon()
    {
        // exactly 2 edges
        return (\count($this->walk->getEdges()) === 2 &&
                // no duplicate edges
                !$this->hasArrayDuplicates($this->walk->getEdges()->getVector()) &&
                // exactly two distinct vertices
                \count($this->walk->getVertices()->getVerticesDistinct()) === 2 &&
                // this is actually a cycle
                $this->isCycle());
    }

    /**
     * checks whether this walk is a triangle (a simple cycle with exactly three distinct vertices)
     *
     * The following Graph is a valid directed triangle:
     *
     * 1->2->3
     * ^     |
     * \-----/
     *
     * @return bool
     * @uses self::isCycle()
     */
    public function isTriangle()
    {
        // exactly 3 (implicitly distinct) edges
        return (\count($this->walk->getEdges()) === 3 &&
                // exactly three distinct vertices
                \count($this->walk->getVertices()->getVerticesDistinct()) === 3 &&
                // this is actually a cycle
                $this->isCycle());
    }

    /**
     * check whether this walk is simple
     *
     * contains no duplicate/repeated vertices (and thus no duplicate edges either)
     * other than the starting and ending vertices of cycles.
     *
     * A simple Walk is also known as a chain.
     *
     * The term "simple walk" is somewhat related to a walk with no cycles. If
     * a Walk has a cycle, it is not simple - with one single exception: a Walk
     * that IS a cycle automatically also contains a cycle, but if it contains
     * no "further" additional cycles, it is considered a simple cycle.
     *
     * The following Graph represents a (very) simple Walk:
     *
     * 1 -- 2
     *
     * The following Graph IS a cycle and is simple:
     *
     * 1 -> 2
     * ^    |
     * \----/
     *
     * The following Graph contains a cycle and is NOT simple:
     *
     *      /->4
     *      |
     * 1 -> 2 -> 3 -\
     *      ^       |
     *      \-------/
     *
     * The following Graph IS a cycle and thus automatically contains a cycle.
     * Due to the additional "inner" cycle (loop at vertex 2), it is NOT simple:
     *
     *      /->3--\
     *      |     |
     * 1 -> 2 -\  |
     * ^    ^  |  |
     * |    \--/  |
     * |          |
     * \----------/
     *
     * @return bool
     * @uses self::isCycle()
     * @uses self::hasArrayDuplicates()
     * @see self::hasCycle()
     */
    public function isSimple()
    {
        $vertices = $this->walk->getVertices()->getVector();
        // ignore starting vertex for cycles as it's always the same as ending vertex
        if ($this->isCycle()) {
            unset($vertices[0]);
        }

        return !$this->hasArrayDuplicates($vertices);
    }

    /**
     * checks whether walk is hamiltonian (i.e. walk over ALL VERTICES of the graph)
     *
     * A hamiltonian Walk is also known as a spanning walk.
     *
     * @return bool
     * @see self::isEulerian() if you want to check for all EDGES instead of VERTICES
     * @uses self::isArrayContentsEqual()
     * @link http://en.wikipedia.org/wiki/Hamiltonian_path
     */
    public function isHamiltonian()
    {
        $vertices = $this->walk->getVertices()->getVector();
        // ignore starting vertex for cycles as it's always the same as ending vertex
        if ($this->isCycle()) {
            unset($vertices[0]);
        }
        return $this->isArrayContentsEqual($vertices, $this->walk->getGraph()->getVertices()->getVector());
    }

    /**
     * checks whether walk is eulerian (i.e. a walk over ALL EDGES of the graph)
     *
     * @return bool
     * @see self::isHamiltonian() if you want to check for all VERTICES instead of EDGES
     * @uses self::isArrayContentsEqual()
     * @link http://en.wikipedia.org/wiki/Eulerian_path
     */
    public function isEulerian()
    {
        return $this->isArrayContentsEqual($this->walk->getEdges()->getVector(), $this->walk->getGraph()->getEdges()->getVector());
    }

    /**
     * checks whether ths given array contains duplicate identical entries
     *
     * @param  array $array
     * @return bool
     */
    private function hasArrayDuplicates($array)
    {
        $compare = array();
        foreach ($array as $element) {
            // duplicate element found
            if (\in_array($element, $compare, true)) {
                return true;
            } else {
                // add element to temporary array to check for duplicates
                $compare [] = $element;
            }
        }

        return false;
    }

    /**
     * checks whether the contents of array a equals those of array b (ignore keys and order but otherwise strict check)
     *
     * @param  array   $a
     * @param  array   $b
     * @return bool
     */
    private function isArrayContentsEqual($a, $b)
    {
        foreach ($b as $one) {
            $pos = \array_search($one, $a, true);
            if ($pos === false) {
                return false;
            } else {
                unset($a[$pos]);
            }
        }

        return $a ? false : true;
    }
}
