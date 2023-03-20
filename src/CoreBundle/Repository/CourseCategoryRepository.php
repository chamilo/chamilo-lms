<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Repository;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\CourseCategory;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\Persistence\ManagerRegistry;

class CourseCategoryRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CourseCategory::class);
    }

    public function update(CourseCategory $category): void
    {
        $this->getEntityManager()->persist($category);
        $this->getEntityManager()->flush();
    }

    /**
     * Get all course categories in an access url.
     *
     * @return CourseCategory[]
     */
    public function findAllInAccessUrl(int $accessUrl, bool $allowBaseCategories = false, int $parentId = 0)
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
            ->orderBy('c.treePos', Criteria::ASC)
        ;

        if ($allowBaseCategories) {
            $qb->orWhere($qb->expr()->eq('a.url', 1));
        }

        if (!empty($parentId)) {
            $qb->andWhere($qb->expr()->eq('c.parent', $parentId));
        } else {
            $qb->andWhere($qb->expr()->isNull('c.parent'));
        }

        $query = $qb->getQuery();

        return $query->getResult();
    }

    /**
     * Get all categories in an access url and course id.
     *
     * @return array
     */
    public function getCategoriesByCourseIdAndAccessUrlId(int $accessUrl, int $courseId, bool $allowBaseCategories = false)
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
     * @return int
     */
    public function countAllInAccessUrl(int $accessUrl, bool $allowBaseCategories = false)
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
            )
        ;

        if ($allowBaseCategories) {
            $qb->orWhere($qb->expr()->eq('a.url', 1));
        }

        $count = $qb->getQuery()->getSingleScalarResult();

        return (int) $count;
    }

    public function updateCourseRelCategoryByCourse(Course $course, array $courseData): void
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

    public function deleteAsset(CourseCategory $category): void
    {
        $em = $this->getEntityManager();
        if ($category->hasAsset()) {
            $asset = $category->getAsset();
            $em->remove($asset);
            $em->flush();
        }
    }

    public function delete(CourseCategory $category): void
    {
        $em = $this->getEntityManager();
        $em->remove($category);
        $this->deleteAsset($category);
        $em->flush();
    }

    public function save(CourseCategory $category): void
    {
        $em = $this->getEntityManager();
        $em->persist($category);
        $em->flush();
    }
}
