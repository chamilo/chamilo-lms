<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CourseBundle\Repository;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\ResourceInterface;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Repository\ResourceRepository;
use Chamilo\CourseBundle\Entity\CShortcut;
use Doctrine\Persistence\ManagerRegistry;

final class CShortcutRepository extends ResourceRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CShortcut::class);
    }

    /**
     * Backward-compatible helper.
     * Returns the first shortcut found for the resource.
     */
    public function getShortcutFromResource(ResourceInterface $resource): ?CShortcut
    {
        return $this->createQueryBuilder('shortcut')
            ->andWhere('shortcut.shortCutNode = :shortcutNode')
            ->setParameter('shortcutNode', $resource->getResourceNode())
            ->orderBy('shortcut.id', 'ASC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }

    /**
     * Returns all shortcuts that point to the same resource node.
     *
     * @return CShortcut[]
     */
    public function getShortcutsFromResource(ResourceInterface $resource): array
    {
        return $this->createQueryBuilder('shortcut')
            ->innerJoin('shortcut.resourceNode', 'resourceNode')
            ->andWhere('shortcut.shortCutNode = :shortcutNode')
            ->setParameter('shortcutNode', $resource->getResourceNode())
            ->orderBy('resourceNode.id', 'ASC')
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * Returns assigned course IDs for the given resource.
     *
     * @return int[]
     */
    public function getAssignedCourseIdsFromResource(ResourceInterface $resource): array
    {
        $rows = $this->createQueryBuilder('shortcut')
            ->select('DISTINCT IDENTITY(link.course) AS courseId')
            ->innerJoin('shortcut.resourceNode', 'resourceNode')
            ->innerJoin('resourceNode.resourceLinks', 'link')
            ->andWhere('shortcut.shortCutNode = :shortcutNode')
            ->andWhere('link.course IS NOT NULL')
            ->setParameter('shortcutNode', $resource->getResourceNode())
            ->getQuery()
            ->getArrayResult()
        ;

        $courseIds = [];
        foreach ($rows as $row) {
            $courseId = (int) ($row['courseId'] ?? 0);
            if ($courseId > 0) {
                $courseIds[] = $courseId;
            }
        }

        return array_values(array_unique($courseIds));
    }

    /**
     * Returns the shortcut of a resource inside one concrete course.
     */
    public function findShortcutFromResourceInCourse(
        ResourceInterface $resource,
        Course $course
    ): ?CShortcut {
        return $this->createQueryBuilder('shortcut')
            ->innerJoin('shortcut.resourceNode', 'resourceNode')
            ->andWhere('shortcut.shortCutNode = :shortcutNode')
            ->andWhere('resourceNode.parent = :courseNode')
            ->setParameter('shortcutNode', $resource->getResourceNode())
            ->setParameter('courseNode', $course->getResourceNode())
            ->orderBy('shortcut.id', 'ASC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }

    /**
     * Creates or reuses the shortcut for a resource inside one concrete course.
     */
    public function addShortCut(
        ResourceInterface $resource,
        User $user,
        Course $course,
        ?Session $session = null
    ): CShortcut {
        $shortcut = $this->findShortcutFromResourceInCourse($resource, $course);

        if (null === $shortcut) {
            $shortcut = (new CShortcut())
                ->setTitle($resource->getResourceName())
                ->setShortCutNode($resource->getResourceNode())
                ->setCreator($user)
                ->setParent($course)
                ->addCourseLink($course, $session)
            ;

            $this->create($shortcut);

            return $shortcut;
        }

        $shortcut
            ->setTitle($resource->getResourceName())
            ->setShortCutNode($resource->getResourceNode())
        ;

        if (!$shortcut->getFirstResourceLinkFromCourseSession($course, $session)) {
            $shortcut->addCourseLink($course, $session);
        }

        $em = $this->getEntityManager();
        $em->persist($shortcut);
        $em->flush();

        return $shortcut;
    }

    public function hardDeleteShortcutsForCourse(ResourceInterface $resource, Course $course): int
    {
        $conn = $this->getEntityManager()->getConnection();

        $sql = <<<'SQL'
DELETE s
FROM c_shortcut s
INNER JOIN resource_node rn ON rn.id = s.resource_node_id
WHERE s.shortcut_node_id = :shortcutNodeId
  AND rn.parent_id = :courseNodeId
SQL;

        return $conn->executeStatement($sql, [
            'shortcutNodeId' => $resource->getResourceNode()->getId(),
            'courseNodeId' => $course->getResourceNode()->getId(),
        ]);
    }

    /**
     * Backward-compatible helper.
     * Removes the first shortcut found for the resource.
     */
    public function removeShortCut(ResourceInterface $resource): bool
    {
        $em = $this->getEntityManager();
        $shortcut = $this->getShortcutFromResource($resource);

        if (null !== $shortcut) {
            $em->remove($shortcut);
            $em->flush();

            return true;
        }

        return false;
    }

    /**
     * Removes the shortcut of a resource inside one concrete course.
     */
    public function removeShortCutFromCourse(ResourceInterface $resource, Course $course): bool
    {
        $em = $this->getEntityManager();
        $shortcut = $this->findShortcutFromResourceInCourse($resource, $course);

        if (null !== $shortcut) {
            $em->remove($shortcut);
            $em->flush();

            return true;
        }

        return false;
    }
}
