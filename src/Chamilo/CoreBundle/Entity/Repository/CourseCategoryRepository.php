<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query\Expr\Join;

/**
 * Class CCourseCategoryRepository.
 *
 * @package Chamilo\CoreBundle\Entity\Repository
 */
class CourseCategoryRepository extends EntityRepository
{
    /**
     * Get all course categories in an access url.
     *
     * @param int  $accessUrl
     * @param bool $allowBaseCategories
     *
     * @return array
     */
    public function findAllInAccessUrl($accessUrl, $allowBaseCategories = false)
    {
        $qb = $this->createQueryBuilder('c');
        $qb
            ->innerJoin(
                'ChamiloCoreBundle:AccessUrlRelCourseCategory',
                'a',
                Join::WITH,
                'c = a.courseCategoryId'
            )
            ->where($qb->expr()->eq('a.accessUrlId', $accessUrl))
            ->orderBy('c.treePos', 'ASC')
           ;

        if ($allowBaseCategories) {
            $qb->orWhere($qb->expr()->eq('a.accessUrlId', 1));
        }

        $query = $qb->getQuery();

        return $query->getResult();
    }

    /**
     * Get the number of course categories in an access url.
     *
     * @param int  $accessUrl
     * @param bool $allowBaseCategories
     *
     * @return int
     */
    public function countAllInAccessUrl($accessUrl, $allowBaseCategories = false)
    {
        $qb = $this->createQueryBuilder('c');
        $qb->select('COUNT(c)')
            ->innerJoin(
                'ChamiloCoreBundle:AccessUrlRelCourseCategory',
                'a',
                Join::WITH,
                'c = a.courseCategoryId'
            )
            ->where(
                $qb->expr()->eq('a.accessUrlId', $accessUrl)
            );

        if ($allowBaseCategories) {
            $qb->orWhere($qb->expr()->eq('a.accessUrlId', 1));
        }

        $count = $qb->getQuery()->getSingleScalarResult();

        return (int) $count;
    }
}
