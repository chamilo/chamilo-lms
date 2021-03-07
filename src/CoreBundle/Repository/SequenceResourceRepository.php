<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Repository;

use Category;
use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\GradebookCategory;
use Chamilo\CoreBundle\Entity\SequenceResource;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Entity\SessionRelUser;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Fhaculty\Graph\Set\Vertices;
use Fhaculty\Graph\Vertex;
use SessionManager;

class SequenceResourceRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, SequenceResource::class);
    }

    /**
     * Find the SequenceResource based in the resourceId and type.
     */
    public function findRequirementForResource(int $resourceId, int $type): ?SequenceResource
    {
        return $this->findOneBy([
            'resourceId' => $resourceId,
            'type' => $type,
        ]);
    }

    /**
     * @todo implement for all types only work for sessions
     *
     * @return array
     */
    public function getRequirementAndDependencies(int $resourceId, int $type)
    {
        $sequence = $this->findRequirementForResource($resourceId, $type);
        $result = [
            'requirements' => [],
            'dependencies' => [],
        ];
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
     * order to deleted.
     */
    public function deleteSequenceResource(int $resourceId, int $type): void
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
                if ($subSequence->hasGraph()) {
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
    public function getRequirements(int $resourceId, int $type)
    {
        $sequencesResource = $this->findBy([
            'resourceId' => $resourceId,
            'type' => $type,
        ]);
        $em = $this->getEntityManager();
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
    public function getRequirementsAndDependenciesWithinSequences(int $resourceId, int $type)
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
     *
     * @return array
     */
    public function checkRequirementsForUser(array $sequences, int $type, int $userId)
    {
        $sequenceList = [];
        $em = $this->getEntityManager();
        $gradebookCategoryRepo = $em->getRepository(GradebookCategory::class);

        $sessionUserList = [];
        if (SequenceResource::COURSE_TYPE === $type) {
            $criteria = [
                'user' => $userId,
            ];
            $sessions = $em->getRepository(SessionRelUser::class)->findBy($criteria);
            if ([] !== $sessions) {
                foreach ($sessions as $sessionRelUser) {
                    $sessionUserList[] = $sessionRelUser->getSession()->getId();
                }
            }
        }

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
                        $status = $this->checkCourseRequirements($userId, $resource, 0);

                        if (!$status) {
                            $sessionsInCourse = SessionManager::get_session_by_course($id);
                            foreach ($sessionsInCourse as $session) {
                                if (in_array($session['id'], $sessionUserList, true)) {
                                    $status = $this->checkCourseRequirements($userId, $resource, $session['id']);
                                    if ($status) {
                                        break;
                                    }
                                }
                            }
                        }

                        $resourceItem = [
                            'name' => $resource->getTitle(),
                            'status' => $status,
                        ];

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

    public function checkCourseRequirements(int $userId, Course $course, int $sessionId = 0): bool
    {
        $em = $this->getEntityManager();

        $gradebookCategoryRepo = $em->getRepository(GradebookCategory::class);
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
                if (!$userFinishedCourse) {
                    $status = false;

                    break;
                }
            } elseif (!$userFinishedCourse) {
                $status = false;

                break;
            }
        }

        return $status;
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

    /**
     * Get sessions from vertices.
     *
     * @param Vertices $verticesEdges The vertices
     *
     * @return array
     */
    protected function findVerticesEdges(Vertices $verticesEdges, int $type)
    {
        $sessionVertices = [];
        $em = $this->getEntityManager();

        foreach ($verticesEdges as $supVertex) {
            $vertexId = $supVertex->getId();
            switch ($type) {
                case SequenceResource::SESSION_TYPE:
                    $resource = $em->getRepository(Session::class)->find($vertexId);

                    break;
                case SequenceResource::COURSE_TYPE:
                    $resource = $em->getRepository(Course::class)->find($vertexId);

                    break;
            }

            if (empty($resource)) {
                continue;
            }

            $sessionVertices[$vertexId] = $resource;
        }

        return $sessionVertices;
    }
}
