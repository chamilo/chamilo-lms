<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Repository;

use Chamilo\CoreBundle\Entity\AbstractResource;
use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\ResourceLink;
use Chamilo\CoreBundle\Entity\ResourceNode;
use Chamilo\CoreBundle\Entity\ResourceType;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Entity\Tool;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Entity\Usergroup;
use Chamilo\CourseBundle\Entity\CGroup;
use Doctrine\ORM\EntityManagerInterface;
use Gedmo\Sortable\Entity\Repository\SortableRepository;

/**
 * @template-extends SortableRepository<ResourceLink>
 */
class ResourceLinkRepository extends SortableRepository
{
    private array $toolList = [
        'course_description' => '/main/course_description/index.php',
        'document' => '/resources/document/%resource_node_id%/',
        'learnpath' => '/main/lp/lp_controller.php',
        'link' => '/resources/links/%resource_node_id%/',
        'quiz' => '/main/exercise/exercise.php',
        'announcement' => '/main/announcements/announcements.php',
        'glossary' => '/resources/glossary/%resource_node_id%/',
        'attendance' => '/main/attendance/index.php',
        'course_progress' => '/main/course_progress/index.php',
        'agenda' => '/resources/ccalendarevent',
        'forum' => '/main/forum/index.php',
        'student_publication' => '/resources/assignment/%resource_node_id%',
        'survey' => '/main/survey/survey_list.php',
        'notebook' => '/main/notebook/index.php',
    ];

    public function __construct(EntityManagerInterface $em)
    {
        parent::__construct($em, $em->getClassMetadata(ResourceLink::class));
    }

    public function remove(ResourceLink $resourceLink): void
    {
        $em = $this->getEntityManager();

        // To move the resource link at the end to reorder the list
        $resourceLink->setDisplayOrder(-1);

        $em->flush();
        // soft delete handled by Gedmo\SoftDeleteable
        $em->remove($resourceLink);
        $em->flush();
    }

    public function removeByResourceInContext(
        AbstractResource $resource,
        Course $course,
        ?Session $session = null,
        ?CGroup $group = null,
        ?Usergroup $usergroup = null,
        ?User $user = null,
    ): void {
        $link = $resource->getResourceNode()->getResourceLinkByContext($course, $session, $group, $usergroup, $user);

        if ($link) {
            $this->remove($link);
        }
    }

    /**
     * Retrieves the list of available tools filtered by a predefined tool list.
     *
     * @return array the list of tools with their IDs and titles
     */
    public function getAvailableTools(): array
    {
        $queryBuilder = $this->_em->createQueryBuilder();
        $queryBuilder
            ->select('DISTINCT t.id, t.title')
            ->from(ResourceLink::class, 'rl')
            ->innerJoin(ResourceType::class, 'rt', 'WITH', 'rt.id = rl.resourceTypeGroup')
            ->innerJoin(Tool::class, 't', 'WITH', 't.id = rt.tool')
            ->where('rl.course IS NOT NULL')
            ->andWhere('t.title IN (:toolList)')
            ->setParameter('toolList', array_keys($this->toolList))
        ;

        $result = $queryBuilder->getQuery()->getArrayResult();

        $tools = [];
        foreach ($result as $row) {
            $tools[$row['id']] = ucfirst(str_replace('_', ' ', $row['title']));
        }

        return $tools;
    }

    /**
     * Retrieves a usage report of tools with dynamic links.
     *
     * @return array the tool usage data including counts, last update timestamps, and dynamic links
     */
    public function getToolUsageReportByTools(array $toolIds): array
    {
        $queryBuilder = $this->_em->createQueryBuilder();

        $queryBuilder
            ->select(
                'COUNT(rl.id) AS resource_count',
                'IDENTITY(rl.course) AS course_id',
                'IDENTITY(rl.session) AS session_id',
                'IDENTITY(c.resourceNode) AS course_resource_node_id',
                't.title AS tool_name',
                'c.title AS course_name',
                's.title AS session_name',
                'MAX(rl.updatedAt) AS last_updated'
            )
            ->from(ResourceLink::class, 'rl')
            ->innerJoin(ResourceType::class, 'rt', 'WITH', 'rt.id = rl.resourceTypeGroup')
            ->innerJoin(Tool::class, 't', 'WITH', 't.id = rt.tool')
            ->innerJoin(Course::class, 'c', 'WITH', 'c.id = rl.course')
            ->leftJoin(Session::class, 's', 'WITH', 's.id = rl.session')
            ->where($queryBuilder->expr()->in('t.id', ':toolIds'))
            ->groupBy('rl.course, rl.session, t.title')
            ->orderBy('t.title', 'ASC')
            ->addOrderBy('c.title', 'ASC')
            ->addOrderBy('s.title', 'ASC')
            ->setParameter('toolIds', $toolIds)
        ;

        $result = $queryBuilder->getQuery()->getArrayResult();

        return array_map(function ($row) {
            $toolName = $row['tool_name'];
            $baseLink = $this->toolList[$toolName] ?? null;
            $link = '-';
            if ($baseLink) {
                $link = str_replace(
                    ['%resource_node_id%'],
                    [$row['course_resource_node_id']],
                    $baseLink
                );

                $queryParams = [
                    'cid' => $row['course_id'],
                ];

                if (!empty($row['session_id'])) {
                    $queryParams['sid'] = $row['session_id'];
                }

                $link .= '?'.http_build_query($queryParams);
            }

            return [
                'tool_name' => $toolName,
                'session_id' => $row['session_id'],
                'session_name' => $row['session_name'] ?: '-',
                'course_id' => $row['course_id'],
                'course_name' => $row['course_name'],
                'resource_count' => (int) $row['resource_count'],
                'last_updated' => $row['last_updated'] ?: '-',
                'link' => $link,
            ];
        }, $result);
    }

    /**
     * Find the parent link (folder link) for a given parent node in a specific context.
     *
     * This is used when creating new document links so that the link hierarchy
     * is context-aware (course/session/group/usergroup/user).
     */
    public function findParentLinkForContext(
        ResourceNode $parentNode,
        ?Course $course,
        ?Session $session,
        ?CGroup $group,
        ?Usergroup $usergroup,
        ?User $user
    ): ?ResourceLink {
        $qb = $this->createQueryBuilder('rl')
            ->andWhere('rl.resourceNode = :parentNode')
            ->setParameter('parentNode', $parentNode->getId())
            ->andWhere('rl.deletedAt IS NULL')
            ->setMaxResults(1)
        ;

        // Match course context
        if (null !== $course) {
            $qb
                ->andWhere('rl.course = :course')
                ->setParameter('course', $course->getId())
            ;
        } else {
            $qb->andWhere('rl.course IS NULL');
        }

        // Match session context
        if (null !== $session) {
            $qb
                ->andWhere('rl.session = :session')
                ->setParameter('session', $session->getId())
            ;
        } else {
            $qb->andWhere('rl.session IS NULL');
        }

        // Match group context
        if (null !== $group) {
            $qb
                ->andWhere('rl.group = :group')
                ->setParameter('group', $group->getIid())
            ;
        } else {
            $qb->andWhere('rl.group IS NULL');
        }

        if (null !== $usergroup) {
            $qb
                ->andWhere('rl.userGroup = :usergroup')
                ->setParameter('usergroup', $usergroup->getId())
            ;
        } else {
            $qb->andWhere('rl.userGroup IS NULL');
        }

        // Match user context
        if (null !== $user) {
            $qb
                ->andWhere('rl.user = :user')
                ->setParameter('user', $user->getId())
            ;
        } else {
            $qb->andWhere('rl.user IS NULL');
        }

        return $qb->getQuery()->getOneOrNullResult();
    }

    /**
     * Find the link of a resource in a given context.
     *
     * This is mostly used by document move operations to update the link parent
     * only in the current context.
     */
    public function findLinkForResourceInContext(
        AbstractResource $resource,
        ?Course $course,
        ?Session $session,
        ?CGroup $group,
        ?Usergroup $usergroup,
        ?User $user
    ): ?ResourceLink {
        $resourceNode = $resource->getResourceNode();
        if (null === $resourceNode) {
            return null;
        }

        $qb = $this->createQueryBuilder('rl')
            ->andWhere('rl.resourceNode = :resourceNode')
            ->setParameter('resourceNode', $resourceNode->getId())
            ->andWhere('rl.deletedAt IS NULL')
            ->setMaxResults(1)
        ;

        // Match course context
        if (null !== $course) {
            $qb
                ->andWhere('rl.course = :course')
                ->setParameter('course', $course->getId())
            ;
        } else {
            $qb->andWhere('rl.course IS NULL');
        }

        // Match session context
        if (null !== $session) {
            $qb
                ->andWhere('rl.session = :session')
                ->setParameter('session', $session->getId())
            ;
        } else {
            $qb->andWhere('rl.session IS NULL');
        }

        // Match group context
        if (null !== $group) {
            $qb
                ->andWhere('rl.group = :group')
                ->setParameter('group', $group->getIid())
            ;
        } else {
            $qb->andWhere('rl.group IS NULL');
        }

        if (null !== $usergroup) {
            $qb
                ->andWhere('rl.userGroup = :usergroup')
                ->setParameter('usergroup', $usergroup->getId())
            ;
        } else {
            $qb->andWhere('rl.userGroup IS NULL');
        }

        // Match user context
        if (null !== $user) {
            $qb
                ->andWhere('rl.user = :user')
                ->setParameter('user', $user->getId())
            ;
        } else {
            $qb->andWhere('rl.user IS NULL');
        }

        return $qb->getQuery()->getOneOrNullResult();
    }
}
