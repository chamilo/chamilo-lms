<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Repository;

use Chamilo\CoreBundle\Entity\CourseCategory;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\Query\Expr\Join;

/**
 * Class CCourseCategoryRepository.
 *
 * @package Chamilo\CoreBundle\Repository
 */
class CourseCategoryRepository extends ServiceEntityRepository
{
    /**
     * CourseCategoryRepository constructor.
     *
     * @param ManagerRegistry $registry
     */
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CourseCategory::class);
    }

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
                'c = a.courseCategory'
            )
            ->where($qb->expr()->eq('a.url', $accessUrl))
            ->orderBy('c.treePos', 'ASC')
           ;

        if ($allowBaseCategories) {
            $qb->orWhere($qb->expr()->eq('a.url', 1));
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
                'c = a.courseCategory'
            )
            ->where(
                $qb->expr()->eq('a.url', $accessUrl)
            );

        if ($allowBaseCategories) {
            $qb->orWhere($qb->expr()->eq('a.url', 1));
        }

        $count = $qb->getQuery()->getSingleScalarResult();

        return (int) $count;
    }
}
