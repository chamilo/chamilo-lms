<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CourseBundle\Repository;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\ResourceInterface;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Repository\ResourceRepository;
use Chamilo\CoreBundle\Repository\ResourceWithLinkInterface;
use Chamilo\CourseBundle\Entity\CForum;
use Chamilo\CourseBundle\Entity\CGroup;
use Chamilo\CourseBundle\Entity\CLp;
use Chamilo\CourseBundle\Entity\CLpItem;
use Chamilo\CourseBundle\Entity\CLpView;
use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Exception;
use InvalidArgumentException;
use Symfony\Component\Routing\RouterInterface;

/**
 * @extends ResourceRepository<CLp>
 */
final class CLpRepository extends ResourceRepository implements ResourceWithLinkInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CLp::class);
    }

    public function createLp(CLp $lp): void
    {
        if (null !== $lp->getResourceNode()) {
            throw new Exception('Lp should not have a resource node during creation');
        }

        $lpItem = (new CLpItem())
            ->setTitle('root')
            ->setPath('root')
            ->setLp($lp)
            ->setItemType('root')
        ;
        $lp->getItems()->add($lpItem);
        $this->create($lp);
    }

    public function findForumByCourse(CLp $lp, Course $course, ?Session $session = null): ?CForum
    {
        $forums = $lp->getForums();
        $result = null;
        foreach ($forums as $forum) {
            $links = $forum->getResourceNode()->getResourceLinks();
            foreach ($links as $link) {
                if ($link->getCourse() === $course && $link->getSession() === $session) {
                    $result = $forum;

                    break 2;
                }
            }
        }

        return $result;
    }

    public function findAllByCourse(
        Course $course,
        ?Session $session = null,
        ?string $title = null,
        ?int $active = null,
        bool $onlyPublished = true,
        ?int $categoryId = null,
        ?CGroup $group = null,
    ): QueryBuilder {
        $qb = $this->getResourcesByCourse($course, $session, $group, null, $onlyPublished, true);

        /*if ($onlyPublished) {
            $this->addDateFilterQueryBuilder(new DateTime(), $qb);
        }*/
        // $this->addCategoryQueryBuilder($categoryId, $qb);
        // $this->addActiveQueryBuilder($active, $qb);
        // $this->addNotDeletedQueryBuilder($qb);
        $this->addTitleQueryBuilder($title, $qb);

        return $qb;
    }

    public function getLink(ResourceInterface $resource, RouterInterface $router, array $extraParams = []): string
    {
        $courseNodeId = $resource instanceof CLp
            ? (int) ($resource->getResourceNode()?->getParent()?->getId() ?? 0)
            : 0;
        if ($courseNodeId <= 0) {
            $fallbackParams = array_merge(
                [
                    'lp_id' => $resource->getResourceIdentifier(),
                    'action' => 'view',
                ],
                $extraParams,
            );

            return '/main/lp/lp_controller.php?'.http_build_query($fallbackParams);
        }

        unset($extraParams['action'], $extraParams['lp_id'], $extraParams['node']);
        $extraParams['origin'] = $extraParams['origin'] ?? 'learnpath';
        $extraParams['isStudentView'] = $extraParams['isStudentView'] ?? 'true';

        $url = $router->generate('resources_lp_runtime', [
            'node' => $courseNodeId,
            'lpId' => $resource->getResourceIdentifier(),
        ]);

        return [] === $extraParams ? $url : $url.'?'.http_build_query($extraParams);
    }

    public function findAutoLaunchableLPByCourseAndSession(Course $course, ?Session $session = null): ?int
    {
        $qb = $this->getResourcesByCourse($course, $session)
            ->select('resource.iid')
            ->andWhere('resource.autolaunch = 1')
        ;

        $qb->setMaxResults(1);

        $result = $qb->getQuery()->getOneOrNullResult();

        return $result ? $result['iid'] : null;
    }

    protected function addNotDeletedQueryBuilder(?QueryBuilder $qb = null): QueryBuilder
    {
        $qb = $this->getOrCreateQueryBuilder($qb);

        $qb->andWhere('resource.active <> -1');

        return $qb;
    }

    public function getLpSessionId(int $lpId): ?int
    {
        $lp = $this->find($lpId);

        if (!$lp) {
            return null;
        }

        $resourceNode = $lp->getResourceNode();
        if ($resourceNode) {
            $link = $resourceNode->getResourceLinks()->first();

            if ($link && $link->getSession()) {
                return (int) $link->getSession()->getId();
            }
        }

        return null;
    }

    public function findScormByCourse(Course $course): array
    {
        return $this->createQueryBuilder('lp')
            ->innerJoin('lp.resourceNode', 'rn')
            ->innerJoin('rn.resourceLinks', 'rl')
            ->andWhere('rl.course = :course')
            ->andWhere('lp.lpType = :scormType')
            ->setParameters([
                'course' => $course,
                'scormType' => CLp::SCORM_TYPE,
            ])
            ->getQuery()
            ->getResult()
        ;
    }

    public function lastProgressForUser(iterable $lps, User $user, ?Session $session): array
    {
        $lpIds = [];
        foreach ($lps as $lp) {
            $id = (int) $lp->getIid();
            if ($id > 0) {
                $lpIds[] = $id;
            }
        }
        if (!$lpIds) {
            return [];
        }

        $em = $this->getEntityManager();
        $qb = $em->createQueryBuilder();
        $sub = $session
            ? 'SELECT MAX(v2.iid) FROM '.CLpView::class.' v2
                 WHERE v2.user = :user AND v2.session = :session AND v2.lp = v.lp'
            : 'SELECT MAX(v2.iid) FROM '.CLpView::class.' v2
                 WHERE v2.user = :user AND v2.session IS NULL AND v2.lp = v.lp';

        $qb->select('IDENTITY(v.lp) AS lp_id', 'COALESCE(v.progress, 0) AS progress')
            ->from(CLpView::class, 'v')
            ->where($qb->expr()->in('v.lp', ':lpIds'))
            ->andWhere('v.user = :user')
            ->andWhere($session ? 'v.session = :session' : 'v.session IS NULL')
            ->andWhere('v.iid = ('.$sub.')')
            ->setParameter('lpIds', $lpIds)
            ->setParameter('user', $user)
        ;

        if ($session) {
            $qb->setParameter('session', $session);
        }

        $rows = $qb->getQuery()->getArrayResult();

        $map = array_fill_keys($lpIds, 0);
        foreach ($rows as $r) {
            $map[(int) $r['lp_id']] = (int) $r['progress'];
        }

        return $map;
    }

    /**
     * @param array<int, int> $orderedLpIds
     */
    public function reorderByIds(
        int $courseId,
        ?int $sessionId,
        array $orderedLpIds,
        ?int $categoryId = null,
        ?int $groupId = null,
    ): void {
        if ([] === $orderedLpIds) {
            throw new InvalidArgumentException('The learning path order cannot be empty.');
        }

        if (\count($orderedLpIds) !== \count(array_unique($orderedLpIds))) {
            throw new InvalidArgumentException('The learning path order contains duplicate identifiers.');
        }

        $entityManager = $this->getEntityManager();
        $course = $entityManager->getReference(Course::class, $courseId);
        $session = null !== $sessionId ? $entityManager->getReference(Session::class, $sessionId) : null;
        $group = null !== $groupId ? $entityManager->getReference(CGroup::class, $groupId) : null;

        $queryBuilder = $this->createQueryBuilder('lp')
            ->addSelect('resourceNode', 'resourceLink')
            ->join('lp.resourceNode', 'resourceNode')
            ->join('resourceNode.resourceLinks', 'resourceLink')
            ->where('lp.iid IN (:ids)')
            ->andWhere('IDENTITY(resourceLink.course) = :courseId')
            ->setParameter('ids', $orderedLpIds, ArrayParameterType::INTEGER)
            ->setParameter('courseId', $courseId, Types::INTEGER)
        ;

        if (null !== $sessionId) {
            $sessionExpression = null === $groupId
                ? '(IDENTITY(resourceLink.session) = :sessionId OR resourceLink.session IS NULL)'
                : 'IDENTITY(resourceLink.session) = :sessionId';

            $queryBuilder
                ->andWhere($sessionExpression)
                ->setParameter('sessionId', $sessionId, Types::INTEGER)
            ;
        } else {
            $queryBuilder->andWhere('resourceLink.session IS NULL');
        }

        if (null !== $groupId) {
            $queryBuilder
                ->andWhere('IDENTITY(resourceLink.group) = :groupId')
                ->setParameter('groupId', $groupId, Types::INTEGER)
            ;
        } else {
            $queryBuilder->andWhere('resourceLink.group IS NULL');
        }

        if (null !== $categoryId) {
            $queryBuilder
                ->andWhere('IDENTITY(lp.category) = :categoryId')
                ->setParameter('categoryId', $categoryId, Types::INTEGER)
            ;
        } else {
            $queryBuilder->andWhere('lp.category IS NULL');
        }

        /** @var array<int, CLp> $learningPaths */
        $learningPaths = $queryBuilder->getQuery()->getResult();
        if (\count($learningPaths) !== \count($orderedLpIds)) {
            throw new InvalidArgumentException('The order contains learning paths outside the current context.');
        }

        $linksByLearningPathId = [];
        $positions = [];

        foreach ($learningPaths as $learningPath) {
            $resourceNode = $learningPath->getResourceNode();
            if (null === $resourceNode) {
                throw new InvalidArgumentException('A learning path has no resource node.');
            }

            $resourceLink = $resourceNode->getResourceLinkByContext($course, $session, $group);
            if (null === $resourceLink && null !== $session && null === $group) {
                $baseCourseLink = $resourceNode->getResourceLinkByContext($course);
                if (null !== $baseCourseLink) {
                    continue;
                }
            }

            if (null === $resourceLink) {
                throw new InvalidArgumentException('A learning path is not linked to the current context.');
            }

            $learningPathId = (int) $learningPath->getIid();
            $linksByLearningPathId[$learningPathId] = $resourceLink;
            $positions[] = $resourceLink->getDisplayOrder();
        }

        if ([] === $linksByLearningPathId) {
            return;
        }

        sort($positions);
        $position = $positions[0];

        foreach ($orderedLpIds as $learningPathId) {
            if (!isset($linksByLearningPathId[$learningPathId])) {
                continue;
            }

            $linksByLearningPathId[$learningPathId]->setDisplayOrder($position);
            ++$position;
        }

        $entityManager->flush();
    }
}
