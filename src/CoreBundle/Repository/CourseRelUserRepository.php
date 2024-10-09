<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Repository;

use Chamilo\CoreBundle\Entity\CourseRelUser;
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
     * Retrieves users from a course and their LP progress (without session).
     */
    public function getCourseUsers(int $courseId, array $lpIds): array
    {
        $qb = $this->createQueryBuilder('cu')
            ->select('u.id AS userId, c.title AS courseTitle, lp.iid AS lpId, lpv.progress')
            ->innerJoin('cu.user', 'u')
            ->innerJoin('cu.course', 'c')
            ->leftJoin(CLpView::class, 'lpv', 'WITH', 'lpv.user = u.id AND lpv.course = cu.course')
            ->leftJoin(CLp::class, 'lp', 'WITH', 'lp.iid = lpv.lp OR lp.iid IN (:lpIds)')
            ->where('cu.course = :courseId')
            ->setParameter('courseId', $courseId)
            ->setParameter('lpIds', $lpIds)
            ->andWhere('(lpv.progress < 100 OR lpv.progress IS NULL)');

        return $qb->getQuery()->getResult();
    }
}
