<?php

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Repository;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\CourseCategory;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\Persistence\ManagerRegistry;

/**
 * Class CCourseCategoryRepository.
 */
class CourseCategoryRepository extends ServiceEntityRepository
{
    /**
     * CourseCategoryRepository constructor.
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
     * Get all categories in an access url and course id.
     *
     * @param int  $accessUrl
     * @param int  $courseId
     * @param bool $allowBaseCategories
     *
     * @return array
     */
    public function getCategoriesByCourseIdAndAccessUrlId($accessUrl, $courseId, $allowBaseCategories = false)
    {
        $qb = $this->createQueryBuilder('c');
        $qb
            ->join('c.courses', 'a')
            ->join('c.urls', 'b')
            ->where($qb->expr()->eq('a.id', $courseId))
            ->andWhere($qb->expr()->eq('b.url', $accessUrl))
        ;

        if ($allowBaseCategories) {
            $qb->orWhere($qb->expr()->eq('b.url', 1));
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

    public function updateCourseRelCategoryByCourse(Course $course, $courseData)
    {
        $em = $this->getEntityManager();

        // Remove current categories
        foreach ($course->getCategories() as $category) {
            $course->removeCategory($category);
        }
        $em->persist($course);
        $em->flush();

        // Add new categories
        $courseCategories = new ArrayCollection();

        if (isset($courseData['course_categories'])) {
            foreach ($courseData['course_categories'] as $categoryId) {
                $courseCategory = $this->find($categoryId);
                $courseCategories->add($courseCategory);
            }
        }

        $course->setCategories($courseCategories);

        $em->persist($course);
        $em->flush();
    }
}
