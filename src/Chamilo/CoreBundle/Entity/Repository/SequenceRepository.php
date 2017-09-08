<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Entity\Repository;

use Chamilo\CoreBundle\Entity\SequenceResource;
use Doctrine\ORM\EntityRepository;
use Fhaculty\Graph\Vertex;
use Fhaculty\Graph\Set\Vertices;

/**
 * Class SequenceRepository
 * The functions inside this class should return an instance of QueryBuilder
 *
 * @package Chamilo\CoreBundle\Entity\Repository
 */
class SequenceRepository extends EntityRepository
{
    /**
     * Find the SequenceResource based in the resourceId and type
     * @param int $resourceId
     * @param int $type
     *
     * @return SequenceResource
     */
    public function findRequirementForResource($resourceId, $type)
    {
        return $this->findOneBy(['resourceId' => $resourceId, 'type' => $type]);
    }

    /**
     * @todo implement for all types only work for sessions
     *
     * @param int $resourceId
     * @param int $type
     *
     * @return array
     */
    public function getRequirementAndDependencies($resourceId, $type)
    {
        $sequence = $this->findRequirementForResource($resourceId, $type);
        $result = ['requirements' => '', 'dependencies' => ''];
        if ($sequence && $sequence->hasGraph()) {
            $graph = $sequence->getSequence()->getUnSerializeGraph();
            $vertex = $graph->getVertex($resourceId);
            $from = $vertex->getVerticesEdgeFrom();

            foreach ($from as $subVertex) {
                $vertexId = $subVertex->getId();
                $sessionInfo = api_get_session_info($vertexId);
                $sessionInfo['admin_link'] = '<a href="'.\SessionManager::getAdminPath($vertexId).'">'.$sessionInfo['name'].'</a>';
                $result['requirements'][] = $sessionInfo;
            }

            $to = $vertex->getVerticesEdgeTo();
            foreach ($to as $subVertex) {
                $vertexId = $subVertex->getId();
                $sessionInfo = api_get_session_info($vertexId);
                $sessionInfo['admin_link'] = '<a href="'.\SessionManager::getAdminPath($vertexId).'">'.$sessionInfo['name'].'</a>';
                $result['dependencies'][] = $sessionInfo;
            }
        }

        return $result;
    }

    /**
     * Deletes a node and check in all the dependencies if the node exists in
     * order to deleted.
     *
     * @param int $resourceId
     * @param int $type
     * @return boolean
     */
    public function deleteResource($resourceId, $type)
    {
        $sequence = $this->findRequirementForResource($resourceId, $type);
        if ($sequence && $sequence->hasGraph()) {
            $em = $this->getEntityManager();
            $graph = $sequence->getSequence()->getUnSerializeGraph();
            $mainVertex = $graph->getVertex($resourceId);
            $vertices = $graph->getVertices();

            /** @var Vertex $vertex */
            foreach ($vertices as $vertex) {
                $subResourceId = $vertex->getId();
                $subSequence = $this->findRequirementForResource($subResourceId, $type);
                if ($sequence && $subSequence->hasGraph()) {
                    $graph = $subSequence->getSequence()->getUnSerializeGraph();
                    $subMainVertex = $graph->getVertex($resourceId);
                    $subMainVertex->destroy();
                    $subSequence->getSequence()->setGraphAndSerialize($graph);
                    $em->persist($subSequence);
                }
            }

            $mainVertex->destroy();
            $em->remove($sequence);
            $em->flush();
        }
    }

    /**
     * Get the requirements for a resource only
     * @param int $resourceId The resource ID
     * @param int $type The type of sequence resource
     *
     * @return array
     */
    public function getRequirements($resourceId, $type)
    {
        $sequencesResource = $this->findBy([
            'resourceId' => $resourceId,
            'type' => $type
        ]);

        $result = [];

        foreach ($sequencesResource as $sequenceResource) {
            if (!$sequenceResource->hasGraph()) {
                continue;
            }

            $sequence = $sequenceResource->getSequence();
            $graph = $sequence->getUnSerializeGraph();
            $vertex = $graph->getVertex($resourceId);
            $from = $vertex->getVerticesEdgeFrom();

            $sequenceInfo = [
                'name' => $sequence->getName(),
                'requirements' => []
            ];

            foreach ($from as $subVertex) {
                $vertexId = $subVertex->getId();

                switch ($type) {
                    case SequenceResource::SESSION_TYPE:
                        $session = $this->getEntityManager()->getReference(
                            'ChamiloCoreBundle:Session',
                            $vertexId
                        );

                        if (empty($session)) {
                            break;
                        }

                        $sequenceInfo['requirements'][$vertexId] = $session;
                        break;
                }
            }

            $result[$sequence->getId()] = $sequenceInfo;
        }

        return $result;
    }

    /**
     * Get the requirements and dependencies within a sequence for a resource
     * @param int $resourceId The resource ID
     * @param int $type The type of sequence resource
     *
     * @return array
     */
    public function getRequirementsAndDependenciesWithinSequences($resourceId, $type)
    {
        $sequencesResource = $this->findBy([
            'resourceId' => $resourceId,
            'type' => $type
        ]);

        $result = [];

        foreach ($sequencesResource as $sequenceResource) {
            if (!$sequenceResource->hasGraph()) {
                continue;
            }

            $sequence = $sequenceResource->getSequence();
            $graph = $sequence->getUnSerializeGraph();
            $vertex = $graph->getVertex($resourceId);
            $from = $vertex->getVerticesEdgeFrom();
            $to = $vertex->getVerticesEdgeTo();

            $requirements = [];
            $dependencies = [];

            switch ($type) {
                case SequenceResource::SESSION_TYPE:
                    $requirements = $this->findSessionFromVerticesEdges($from);
                    $dependencies = $this->findSessionFromVerticesEdges($to);
                    break;
            }

            $result[$sequence->getId()] = [
                'name' => $sequence->getName(),
                'requirements' => $requirements,
                'dependencies' => $dependencies
            ];
        }

        return [
            'sequences' => $result
        ];
    }

    /**
     * Get sessions from vertices
     * @param Vertices $verticesEdges The vertices
     *
     * @return array
     */
    private function findSessionFromVerticesEdges(Vertices $verticesEdges)
    {
        $sessionVertices = [];
        foreach ($verticesEdges as $supVertex) {
            $vertexId = $supVertex->getId();
            $session = $this->getEntityManager()->getReference(
                'ChamiloCoreBundle:Session',
                $vertexId
            );

            if (empty($session)) {
                continue;
            }

            $sessionVertices[$vertexId] = $session;
        }

        return $sessionVertices;
    }
}
