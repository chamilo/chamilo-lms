<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Entity\Repository;

use Chamilo\CoreBundle\Entity\SequenceResource;
use Doctrine\ORM\EntityRepository;
use Fhaculty\Graph\Set\Vertices;
use Fhaculty\Graph\Vertex;

/**
 * Class SequenceResourceRepository
 */
class SequenceResourceRepository extends EntityRepository
{
    /**
     * Find the SequenceResource based in the resourceId and type.
     *
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
        $result = ['requirements' => [], 'dependencies' => []];
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
     * Get the requirements for a resource only.
     *
     * @param int $resourceId The resource ID
     * @param int $type       The type of sequence resource
     *
     * @return array
     */
    public function getRequirements($resourceId, $type)
    {
        $sequencesResource = $this->findBy(['resourceId' => $resourceId, 'type' => $type]);

        $result = [];
        /** @var SequenceResource $sequenceResource */
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
                'requirements' => [],
            ];

            foreach ($from as $subVertex) {
                $vertexId = $subVertex->getId();
                $resource = null;
                switch ($type) {
                    case SequenceResource::SESSION_TYPE:
                        $repo = $this->getEntityManager()->getRepository('ChamiloCoreBundle:Session');
                        $resource = $repo->find($vertexId);

                        break;
                    case SequenceResource::COURSE_TYPE:
                        $repo = $this->getEntityManager()->getRepository('ChamiloCoreBundle:Course');
                        $resource = $repo->find($vertexId);

                        break;
                }

                if (null === $resource) {
                    continue;
                }

                $sequenceInfo['requirements'][$vertexId] = $resource;
            }

            $result[$sequence->getId()] = $sequenceInfo;
        }

        return $result;
    }

    /**
     * Get the requirements and dependencies within a sequence for a resource.
     *
     * @param int $resourceId The resource ID
     * @param int $type       The type of sequence resource
     *
     * @return array
     */
    public function getRequirementsAndDependenciesWithinSequences($resourceId, $type)
    {
        $sequencesResource = $this->findBy([
            'resourceId' => $resourceId,
            'type' => $type,
        ]);

        $result = [];

        /** @var SequenceResource $sequenceResource */
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
                'dependencies' => $dependencies,
            ];
        }

        return [
            'sequences' => $result,
        ];
    }

    /**
     * Get sessions from vertices.
     *
     * @param Vertices $verticesEdges The vertices
     *
     * @return array
     */
    protected function findSessionFromVerticesEdges(Vertices $verticesEdges)
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

    /**
     * Check if the ser has completed the requirements for the sequences.
     *
     * @param array $sequences The sequences
     * @param int   $type      The type of sequence resource
     * @param int   $userId
     *
     * @return array
     */
    public function checkRequirementsForUser(array $sequences, $type, $userId)
    {
        $sequenceList = [];
        $gradebookCategoryRepo = $this->getEntityManager()->getRepository('ChamiloCoreBundle:GradebookCategory');

        foreach ($sequences as $sequenceId => $sequence) {
            $item = [
                'name' => $sequence['name'],
                'requirements' => [],
            ];
            $resourceItem = null;

            foreach ($sequence['requirements'] as $resource) {
                switch ($type) {
                    case SequenceResource::SESSION_TYPE:
                        $id = $resource->getId();
                        $resourceItem = [
                            'name' => $resource->getName(),
                            'status' => true,
                        ];

                        $sessionsCourses = $resource->getCourses();

                        foreach ($sessionsCourses as $sessionCourse) {
                            $course = $sessionCourse->getCourse();

                            $gradebooks = $gradebookCategoryRepo->findBy(
                                [
                                    'courseCode' => $course->getCode(),
                                    'sessionId' => $resource->getId(),
                                    'isRequirement' => true,
                                ]
                            );

                            foreach ($gradebooks as $gradebook) {
                                $category = \Category::createCategoryObjectFromEntity($gradebook);

                                if (!empty($userId)) {
                                    $resourceItem['status'] = $resourceItem['status'] && \Category::userFinishedCourse(
                                        $userId,
                                        $category
                                    );
                                }
                            }
                        }

                        break;

                    case SequenceResource::COURSE_TYPE:
                        $id = $resource->getId();
                        $resourceItem = [
                            'name' => $resource->getTitle(),
                            'status' => true,
                        ];

                        $gradebooks = $gradebookCategoryRepo->findBy(
                            [
                                'courseCode' => $resource->getCode(),
                                'sessionId' => 0,
                                'isRequirement' => true,
                            ]
                        );

                        foreach ($gradebooks as $gradebook) {
                            $category = \Category::createCategoryObjectFromEntity($gradebook);

                            if (!empty($userId)) {
                                $resourceItem['status'] = $resourceItem['status'] && \Category::userFinishedCourse(
                                    $userId,
                                    $category
                                );
                            }
                        }

                        break;
                }

                if (empty($id)) {
                    continue;
                }

                $item['requirements'][$id] = $resourceItem;
            }
            $sequenceList[$sequenceId] = $item;
        }

        return $sequenceList;
    }

    /**
     * Check if at least one sequence are completed.
     *
     * @param array $sequences The sequences
     *
     * @return bool
     */
    public function checkSequenceAreCompleted(array $sequences)
    {
        foreach ($sequences as $sequence) {
            $status = true;

            foreach ($sequence['requirements'] as $item) {
                $status = $status && $item['status'];
            }

            if ($status) {
                return true;
            }
        }

        return false;
    }
}
