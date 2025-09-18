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
use Chamilo\CourseBundle\Entity\CLp;
use Chamilo\CourseBundle\Entity\CLpItem;
use Chamilo\CourseBundle\Entity\CLpView;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Exception;
use Symfony\Component\Routing\RouterInterface;

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
        ?int $categoryId = null
    ): QueryBuilder {
        $qb = $this->getResourcesByCourse($course, $session, null, null, true, true);

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
        $params = [
            'lp_id' => $resource->getResourceIdentifier(),
            'action' => 'view',
        ];
        if (!empty($extraParams)) {
            $params = array_merge($params, $extraParams);
        }

        return '/main/lp/lp_controller.php?'.http_build_query($params);
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

    public function reorderByIds(int $courseId, ?int $sessionId, array $orderedLpIds, ?int $categoryId = null): void
    {
        if (!$orderedLpIds) {
            return;
        }

        $em = $this->getEntityManager();
        $course = $em->getReference(Course::class, $courseId);
        $session = $sessionId ? $em->getReference(Session::class, $sessionId) : null;

        $qb = $this->createQueryBuilder('lp')
            ->addSelect('rn', 'rl')
            ->join('lp.resourceNode', 'rn')
            ->join('rn.resourceLinks', 'rl')
            ->where('lp.iid IN (:ids)')
            ->andWhere('rl.course = :course')->setParameter('course', $course)
            ->setParameter('ids', $orderedLpIds)
        ;

        if ($session) {
            $qb->andWhere('rl.session = :sid')->setParameter('sid', $session);
        } else {
            $qb->andWhere('rl.session IS NULL');
        }

        if (null !== $categoryId) {
            $qb->andWhere('lp.category = :cat')->setParameter('cat', $categoryId);
        } else {
            $qb->andWhere('lp.category IS NULL');
        }

        /** @var CLp[] $lps */
        $lps = $qb->getQuery()->getResult();
        $linksByLpId = [];
        $positions = [];
        foreach ($lps as $lp) {
            $link = $lp->getResourceNode()->getResourceLinkByContext($course, $session);
            if (!$link) {
                continue;
            }
            $linksByLpId[(int) $lp->getIid()] = $link;
            $positions[] = (int) $link->getDisplayOrder();
        }
        if (!$linksByLpId) {
            return;
        }

        sort($positions);
        $start = $positions[0];

        $pos = $start;
        foreach ($orderedLpIds as $lpId) {
            if (!isset($linksByLpId[$lpId])) {
                continue;
            }
            $linksByLpId[$lpId]->setDisplayOrder($pos);
            $pos++;
        }

        $em->flush();
    }
}
