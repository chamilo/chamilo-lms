<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query\Expr\Join;

/**
 * Class CCourseCategoryRepository
 * @package Chamilo\CoreBundle\Entity\Repository
 */
class CourseCategoryRepository extends EntityRepository
{
    /**
     * Get all course categories in an access url
     * @param int $accessUrl
     * @return array
     */
    public function findAllInAccessUrl($accessUrl)
    {
        $qb = $this->createQueryBuilder('c');
        $query = $qb
            ->innerJoin(
                'ChamiloCoreBundle:AccessUrlRelCourseCategory',
                'a',
                Join::WITH,
                'c = a.courseCategoryId'
            )
            ->where(
                $qb->expr()->eq('a.accessUrlId', $accessUrl)
            )
            ->getQuery();

        return $query->getResult();
    }

    /**
     * Get the number of course categories in an access url
     * @return int
     */
    public function countAllInAccessUrl($accessUrl)
    {
        $qb = $this->createQueryBuilder('c');
        $query = $qb
            ->select('COUNT(c)')
            ->innerJoin(
                'ChamiloCoreBundle:AccessUrlRelCourseCategory',
                'a',
                Join::WITH,
                'c = a.courseCategoryId'
            )
            ->where(
                $qb->expr()->eq('a.accessUrlId', $accessUrl)
            )
            ->getQuery();

        $count = $qb->getQuery()->getSingleScalarResult();

        return (int) $count;
    }
}