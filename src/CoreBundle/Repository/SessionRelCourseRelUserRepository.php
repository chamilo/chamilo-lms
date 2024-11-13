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
            ->select('u.id AS userId, c.title AS courseTitle, lp.iid AS lpId, lpv.progress, IDENTITY(scu.session) AS sessionId')
            ->innerJoin('scu.user', 'u')
            ->innerJoin('scu.course', 'c')
            ->leftJoin(CLpView::class, 'lpv', 'WITH', 'lpv.user = u.id AND lpv.course = scu.course')
            ->leftJoin(CLp::class, 'lp', 'WITH', 'lp.iid = lpv.lp AND lp.iid IN (:lpIds)')
            ->where('scu.course = :courseId')
            ->andWhere('(lpv.progress < 100 OR lpv.progress IS NULL OR lpv.lp IS NULL)')
            ->setParameter('courseId', $courseId)
            ->setParameter('lpIds', $lpIds);

        return $qb->getQuery()->getResult();
    }
}
