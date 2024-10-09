<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Repository;


use Chamilo\CoreBundle\Entity\CourseRelUser;
use Chamilo\CoreBundle\Entity\SessionRelCourseRelUser;
use Chamilo\CourseBundle\Entity\CLp;
use Chamilo\CourseBundle\Entity\CLpView;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class CourseRelUserRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CourseRelUser::class);
    }

    /**
     * Retrieves users from a course, with or without a session, and their LP progress.
     */
    public function getCourseUsers(int $courseId, array $lpIds, bool $checkSession = false): array
    {
        $qb = $this->createQueryBuilder('cu')
            ->select('u.id AS userId, c.title AS courseTitle, lp.iid AS lpId, lpv.progress')
            ->innerJoin('cu.user', 'u')
            ->innerJoin('cu.course', 'c')
            ->leftJoin(CLpView::class, 'lpv', 'WITH', 'lpv.user = u.id')
            ->leftJoin(CLp::class, 'lp', 'WITH', 'lp.iid = lpv.lp')
            ->where('cu.course = :courseId')
            ->andWhere('lp.iid IN (:lpIds)')
            ->setParameter('courseId', $courseId)
            ->setParameter('lpIds', $lpIds)
            ->andWhere('(lpv.progress < 100 OR lpv.progress IS NULL)');

        if ($checkSession) {
            $qb->addSelect('IDENTITY(scu.session) AS sessionId')
                ->leftJoin(SessionRelCourseRelUser::class, 'scu', 'WITH', 'scu.user = u AND scu.course = cu.course')
                ->andWhere('scu.session IS NOT NULL');
        }

        return $qb->getQuery()->getResult();
    }
}
