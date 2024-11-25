<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Repository;

use Chamilo\CoreBundle\Entity\SessionRelCourseRelUser;
use Chamilo\CourseBundle\Entity\CLp;
use Chamilo\CourseBundle\Entity\CLpView;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class SessionRelCourseRelUserRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, SessionRelCourseRelUser::class);
    }

    /**
     * Retrieves users from a course session and their LP progress.
     */
    public function getSessionCourseUsers(int $courseId, array $lpIds): array
    {
        $qb = $this->createQueryBuilder('scu')
            ->select('u.id AS userId, c.title AS courseTitle, lp.iid AS lpId, COALESCE(lpv.progress, 0) AS progress, IDENTITY(scu.session) AS sessionId')
            ->innerJoin('scu.user', 'u')
            ->innerJoin('scu.course', 'c')
            ->leftJoin(CLpView::class, 'lpv', 'WITH', 'lpv.user = u.id AND lpv.course = scu.course AND lpv.lp IN (:lpIds)')
            ->leftJoin(CLp::class, 'lp', 'WITH', 'lp.iid IN (:lpIds)')
            ->innerJoin('lp.resourceNode', 'rn')
            ->where('scu.course = :courseId')
            ->andWhere('rn.parent = c.resourceNode')
            ->andWhere('(lpv.progress < 100 OR lpv.progress IS NULL)')
            ->setParameter('courseId', $courseId)
            ->setParameter('lpIds', $lpIds);

        return $qb->getQuery()->getResult();
    }
}
