<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Repository;

use Chamilo\CoreBundle\Entity\AbstractResource;
use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\ResourceLink;
use Chamilo\CoreBundle\Entity\Session;
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
     * @return array The list of tools with their IDs and titles.
     */
    public function getAvailableTools(): array
    {
        $queryBuilder = $this->_em->createQueryBuilder();
        $queryBuilder
            ->select('DISTINCT t.id, t.title')
            ->from('ChamiloCoreBundle:ResourceLink', 'rl')
            ->innerJoin('ChamiloCoreBundle:ResourceType', 'rt', 'WITH', 'rt.id = rl.resourceTypeGroup')
            ->innerJoin('ChamiloCoreBundle:Tool', 't', 'WITH', 't.id = rt.tool')
            ->where('rl.course IS NOT NULL')
            ->andWhere('t.title IN (:toolList)')
            ->setParameter('toolList', array_keys($this->toolList));

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
     * @return array The tool usage data including counts, last update timestamps, and dynamic links.
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
            ->from('ChamiloCoreBundle:ResourceLink', 'rl')
            ->innerJoin('ChamiloCoreBundle:ResourceType', 'rt', 'WITH', 'rt.id = rl.resourceTypeGroup')
            ->innerJoin('ChamiloCoreBundle:Tool', 't', 'WITH', 't.id = rt.tool')
            ->innerJoin('ChamiloCoreBundle:Course', 'c', 'WITH', 'c.id = rl.course')
            ->leftJoin('ChamiloCoreBundle:Session', 's', 'WITH', 's.id = rl.session')
            ->where($queryBuilder->expr()->in('t.id', ':toolIds'))
            ->groupBy('rl.course, rl.session, t.title')
            ->orderBy('t.title', 'ASC')
            ->addOrderBy('c.title', 'ASC')
            ->addOrderBy('s.title', 'ASC')
            ->setParameter('toolIds', $toolIds);

        $result = $queryBuilder->getQuery()->getArrayResult();

        return array_map(function ($row) {
            $toolName = $row['tool_name'];
            $baseLink = $this->toolList[$toolName] ?? null;

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

                $link .= '?' . http_build_query($queryParams);
            } else {
                $link = '-';
            }

            return [
                'tool_name' => $toolName,
                'session_id' => $row['session_id'],
                'session_name' => $row['session_name'] ?: '-',
                'course_id' => $row['course_id'],
                'course_name' => $row['course_name'],
                'resource_count' => (int)$row['resource_count'],
                'last_updated' => $row['last_updated'] ?: '-',
                'link' => $link,
            ];
        }, $result);
    }
}
