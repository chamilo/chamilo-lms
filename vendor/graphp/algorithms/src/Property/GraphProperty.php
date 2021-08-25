<?php

namespace Graphp\Algorithms\Property;

use Graphp\Algorithms\BaseGraph;

/**
 * Simple algorithms for working with Graph properties
 *
 * @link https://en.wikipedia.org/wiki/Graph_property
 */
class GraphProperty extends BaseGraph
{
    /**
     * checks whether this graph has no edges
     *
     * Also known as empty Graph. An empty Graph contains no edges, but can
     * possibly contain any number of isolated vertices.
     *
     * @return bool
     */
    public function isEdgeless()
    {
        return $this->graph->getEdges()->isEmpty();
    }

    /**
     * checks whether this graph is a null graph (no vertex - and thus no edges)
     *
     * Each Edge is incident to two Vertices, or in case of an loop Edge,
     * incident to the same Vertex twice. As such an Edge can not exist when
     * no Vertices exist. So if we check we have no Vertices, we can also be
     * sure that no Edges exist either.
     *
     * @return bool
     */
    public function isNull()
    {
        return $this->graph->getVertices()->isEmpty();
    }

    /**
     * checks whether this graph is trivial (one vertex and no edges)
     *
     * @return bool
     */
    public function isTrivial()
    {
        return ($this->graph->getEdges()->isEmpty() && \count($this->graph->getVertices()) === 1);
    }
}
