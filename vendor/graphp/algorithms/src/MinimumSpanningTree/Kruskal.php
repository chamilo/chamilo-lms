<?php

namespace Graphp\Algorithms\MinimumSpanningTree;

use Fhaculty\Graph\Edge\Base as Edge;
use Fhaculty\Graph\Exception\UnexpectedValueException;
use Fhaculty\Graph\Graph;
use Fhaculty\Graph\Set\Edges;
use SplPriorityQueue;

class Kruskal extends Base
{
    /**
     * @var Graph
     */
    private $graph;

    public function __construct(Graph $inputGraph)
    {
        $this->graph = $inputGraph;
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
        // Sortiere Kanten im Graphen

        $sortedEdges = new SplPriorityQueue();

        // For all edges
        $this->addEdgesSorted($this->graph->getEdges(), $sortedEdges);

        $returnEdges = array();

        // next color to assign
        $colorNext = 0;
        // array(color1 => array(vid1, vid2, ...), color2=>...)
        $colorVertices = array();
        // array(vid1 => color1, vid2 => color1, ...)
        $colorOfVertices = array();

        // Füge billigste Kanten zu neuen Graphen hinzu und verschmelze teilgragen wenn es nötig ist (keine Kreise)
        // solange ich mehr als einen Graphen habe mit weniger als n-1 kanten (bei n knoten im original)
        foreach ($sortedEdges as $edge) {
            assert($edge instanceof Edge);
            // Gucke Kante an:

            $vertices = $edge->getVertices()->getIds();

            $aId = $vertices[0];
            $bId = $vertices[1];

            $aColor = isset($colorOfVertices[$aId]) ? $colorOfVertices[$aId] : NULL;
            $bColor = isset($colorOfVertices[$bId]) ? $colorOfVertices[$bId] : NULL;

            // 1. weder start noch end gehört zu einem graphen
                // => neuer Graph mit kanten
            if ($aColor === NULL && $bColor === NULL) {
                $colorOfVertices[$aId] = $colorNext;
                $colorOfVertices[$bId] = $colorNext;

                $colorVertices[$colorNext] = array($aId, $bId);

                ++$colorNext;

                // connect both vertices
                $returnEdges[] = $edge;
            }
            // 4. start xor end gehören zu einem graphen
                // => erweitere diesesn Graphen
            // Only b has color
            else if ($aColor === NULL && $bColor !== NULL) {
                // paint a in b's color
                $colorOfVertices[$aId] = $bColor;
                $colorVertices[$bColor][]=$aId;

                $returnEdges[] = $edge;
            // Only a has color
            } elseif ($aColor !== NULL && $bColor === NULL) {
                // paint b in a's color
                $colorOfVertices[$bId] = $aColor;
                $colorVertices[$aColor][]=$bId;

                $returnEdges[] = $edge;
            }
            // 3. start und end gehören zu unterschiedlichen graphen
                // => vereinigung
            // Different color
            else if ($aColor !== $bColor) {
                $betterColor = $aColor;
                $worseColor  = $bColor;

                // more vertices with color a => paint all in b in a's color
                if (\count($colorVertices[$bColor]) > \count($colorVertices[$aColor])) {
                    $betterColor = $bColor;
                    $worseColor = $aColor;
                }

                // search all vertices with color b
                foreach ($colorVertices[$worseColor] as $vid) {
                    $colorOfVertices[$vid] = $betterColor;
                    // repaint in a's color
                    $colorVertices[$betterColor][]=$vid;
                }
                // delete old color
                unset($colorVertices[$worseColor]);

                $returnEdges[] = $edge;
            }
            // 2. start und end gehören zum gleichen graphen => zirkel
            // => nichts machen
        }

        // definition of spanning tree: number of edges = number of vertices - 1
        // above algorithm does not check isolated edges or may otherwise return multiple connected components => force check
        if (\count($returnEdges) !== (\count($this->graph->getVertices()) - 1)) {
            throw new UnexpectedValueException('Graph is not connected');
        }

        return new Edges($returnEdges);
    }
}
