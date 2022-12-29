<?php

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Entity\Repository;

use Category;
use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\GradebookCategory;
use Chamilo\CoreBundle\Entity\SequenceResource;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Entity\SessionRelUser;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Fhaculty\Graph\Set\Vertices;
use Fhaculty\Graph\Vertex;
use SessionManager;

/**
 * Class SequenceResourceRepository.
 */
class SequenceResourceRepository extends EntityRepository
{
    public const VERTICES_TYPE_REQ = 'requirements';
    public const VERTICES_TYPE_DEP = 'dependents';

    /**
     * Find the SequenceResource based in the resourceId and type.
     *
     * @param int $resourceId
     * @param int $type
     */
    public function findRequirementForResource($resourceId, $type): ?SequenceResource
    {
        return $this->findOneBy(['resourceId' => $resourceId, 'type' => $type]);
    }

    /**
     * @todo implement for all types only work for sessions
     *
     * @param int $resourceId
     * @param int $type
     */
    public function getRequirementAndDependencies($resourceId, $type): array
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
                $sessionInfo['admin_link'] = '<a href="'.SessionManager::getAdminPath($vertexId).'">'.$sessionInfo['name'].'</a>';
                $result['requirements'][] = $sessionInfo;
            }

            $to = $vertex->getVerticesEdgeTo();
            foreach ($to as $subVertex) {
                $vertexId = $subVertex->getId();
                $sessionInfo = api_get_session_info($vertexId);
                $sessionInfo['admin_link'] = '<a href="'.SessionManager::getAdminPath($vertexId).'">'.$sessionInfo['name'].'</a>';
                $result['dependencies'][] = $sessionInfo;
            }
        }

        return $result;
    }

    /**
     * Deletes a node and check in all the dependencies if the node exists in
     * order to delete.
     *
     * @param int $resourceId
     * @param int $type
     *
     * @throws ORMException
     * @throws OptimisticLockException
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
     */
    public function getRequirements($resourceId, $type): array
    {
        return $this->getRequirementsOrDependents($resourceId, $type, self::VERTICES_TYPE_REQ);
    }

    /**
     * Get the requirements for a resource only.
     */
    public function getDependents(int $resourceId, int $type): array
    {
        return $this->getRequirementsOrDependents($resourceId, $type, self::VERTICES_TYPE_DEP);
    }

    /**
     * Get the requirements and dependencies within a sequence for a resource.
     */
    public function getRequirementsAndDependenciesWithinSequences(int $resourceId, int $type): array
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

            $requirements = $this->findVerticesEdges($from, $type);
            $dependencies = $this->findVerticesEdges($to, $type);

            $result[$sequence->getId()] = [
                'name' => $sequence->getName(),
                'requirements' => $requirements,
                'dependencies' => $dependencies,
            ];
        }

        return $result;
    }

    /**
     * Check if the ser has completed the requirements for the sequences.
     *
     * @param array $sequences The sequences
     * @param int   $type      The type of sequence resource
     * @param int   $userId
     * @param int   $sessionId
     */
    public function checkRequirementsForUser(array $sequences, int $type, $userId, $sessionId = 0): array
    {
        return $this->checkRequirementsOrDependentsForUser(
            $sequences,
            $type,
            self::VERTICES_TYPE_REQ,
            $userId,
            $sessionId
        );
    }

    /**
     * Check if the ser has completed the requirements for the sequences.
     *
     * @param array $sequences The sequences
     * @param int   $type      The type of sequence resource
     * @param int   $userId
     * @param int   $sessionId
     */
    public function checkDependentsForUser(array $sequences, int $type, $userId, $sessionId = 0): array
    {
        return $this->checkRequirementsOrDependentsForUser(
            $sequences,
            $type,
            self::VERTICES_TYPE_DEP,
            $userId,
            $sessionId
        );
    }

    public function checkCourseRequirements($userId, Course $course, $sessionId): bool
    {
        $em = $this->getEntityManager();
        $sessionId = (int) $sessionId;

        $gradebookCategoryRepo = $em->getRepository('ChamiloCoreBundle:GradebookCategory');
        $gradebooks = $gradebookCategoryRepo->findBy(
            [
                'courseCode' => $course->getCode(),
                'sessionId' => $sessionId,
                'isRequirement' => true,
            ]
        );

        if (empty($gradebooks)) {
            return false;
        }

        $status = true;
        foreach ($gradebooks as $gradebook) {
            $category = Category::createCategoryObjectFromEntity($gradebook);
            $userFinishedCourse = Category::userFinishedCourse(
                $userId,
                $category,
                true
            );
            if (0 === $sessionId) {
                if (false === $userFinishedCourse) {
                    $status = false;
                    break;
                }
            } else {
                if (false === $userFinishedCourse) {
                    $status = false;
                    break;
                }
            }
        }

        return $status;
    }

    /**
     * Check if at least one sequence are completed.
     */
    public function checkSequenceAreCompleted(array $sequences, $itemType = self::VERTICES_TYPE_REQ): bool
    {
        foreach ($sequences as $sequence) {
            $status = true;

            foreach ($sequence[$itemType] as $item) {
                $status = $status && $item['status'];
            }

            if ($status) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get sessions from vertices.
     */
    protected function findVerticesEdges(Vertices $verticesEdges, int $type): array
    {
        $sessionVertices = [];
        $em = $this->getEntityManager();

        foreach ($verticesEdges as $supVertex) {
            $vertexId = $supVertex->getId();
            switch ($type) {
                case SequenceResource::SESSION_TYPE:
                    $resource = $em->getRepository('ChamiloCoreBundle:Session')->find($vertexId);
                    break;
                case SequenceResource::COURSE_TYPE:
                    $resource = $em->getRepository('ChamiloCoreBundle:Course')->find($vertexId);
                    break;
            }

            if (empty($resource)) {
                continue;
            }

            $sessionVertices[$vertexId] = $resource;
        }

        return $sessionVertices;
    }

    private function checkRequirementsOrDependentsForUser(
        array $sequences,
        int $resourceType,
        string $itemType,
        int $userId,
        int $sessionId = 0
    ): array {
        $sequenceList = [];
        $em = $this->getEntityManager();
        $gradebookCategoryRepo = $em->getRepository(GradebookCategory::class);

        $sessionUserList = [];
        $checkOnlySameSession = api_get_configuration_value('course_sequence_valid_only_in_same_session');
        if (SequenceResource::COURSE_TYPE === $resourceType) {
            if ($checkOnlySameSession) {
                $sessionUserList = [$sessionId];
            } else {
                $criteria = ['user' => $userId];
                $sessions = $em->getRepository('ChamiloCoreBundle:SessionRelUser')->findBy($criteria);
                if ($sessions) {
                    /** @var SessionRelUser $sessionRelUser */
                    foreach ($sessions as $sessionRelUser) {
                        $sessionUserList[] = $sessionRelUser->getSession()->getId();
                    }
                }
            }
        }

        foreach ($sequences as $sequenceId => $sequence) {
            $item = [
                'name' => $sequence['name'],
                $itemType => [],
            ];
            $resourceItem = null;

            foreach ($sequence[$itemType] as $resource) {
                switch ($resourceType) {
                    case SequenceResource::SESSION_TYPE:
                        /** @var Session $resource */
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
                                $category = Category::createCategoryObjectFromEntity($gradebook);
                                if (!empty($userId)) {
                                    $resourceItem['status'] = $resourceItem['status'] && Category::userFinishedCourse(
                                        $userId,
                                        $category
                                    );
                                }
                            }
                        }
                        break;
                    case SequenceResource::COURSE_TYPE:
                        $id = $resource->getId();
                        $checkSessionId = 0;
                        if ($checkOnlySameSession) {
                            $checkSessionId = $sessionId;
                        }
                        $status = $this->checkCourseRequirements($userId, $resource, $checkSessionId);

                        if (false === $status) {
                            $sessionsInCourse = SessionManager::get_session_by_course($id);
                            foreach ($sessionsInCourse as $session) {
                                if (in_array($session['id'], $sessionUserList)) {
                                    $status = $this->checkCourseRequirements($userId, $resource, $session['id']);
                                    if (true === $status) {
                                        break;
                                    }
                                }
                            }
                        }

                        $resourceItem = [
                            'name' => $resource->getTitle(),
                            'code' => $resource->getCode(),
                            'status' => $status,
                        ];

                        break;
                }

                if (empty($id)) {
                    continue;
                }

                $item[$itemType][$id] = $resourceItem;
            }
            $sequenceList[$sequenceId] = $item;
        }

        return $sequenceList;
    }

    /**
     * Get the requirements or dependants for a resource only.
     */
    private function getRequirementsOrDependents($resourceId, int $resourceType, string $itemType): array
    {
        $em = $this->getEntityManager();

        $sequencesResource = $this->findBy(['resourceId' => $resourceId, 'type' => $resourceType]);
        $result = [];

        /** @var SequenceResource $sequenceResource */
        foreach ($sequencesResource as $sequenceResource) {
            if (!$sequenceResource->hasGraph()) {
                continue;
            }

            $sequence = $sequenceResource->getSequence();
            $graph = $sequence->getUnSerializeGraph();
            $vertex = $graph->getVertex($resourceId);

            if (self::VERTICES_TYPE_REQ === $itemType) {
                $edges = $vertex->getVerticesEdgeFrom();
            } else {
                $edges = $vertex->getVerticesEdgeTo();
            }

            $sequenceInfo = [
                'name' => $sequence->getName(),
                $itemType => [],
            ];

            foreach ($edges as $edge) {
                $vertexId = $edge->getId();
                $resource = null;
                switch ($resourceType) {
                    case SequenceResource::SESSION_TYPE:
                        $repo = $em->getRepository(Session::class);
                        $resource = $repo->find($vertexId);

                        break;
                    case SequenceResource::COURSE_TYPE:
                        $repo = $em->getRepository(Course::class);
                        $resource = $repo->find($vertexId);

                        break;
                }

                if (null === $resource) {
                    continue;
                }

                $sequenceInfo[$itemType][$vertexId] = $resource;
            }

            $result[$sequence->getId()] = $sequenceInfo;
        }

        return $result;
    }
}
