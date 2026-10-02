<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Repository;

use Chamilo\CoreBundle\Entity\AccessUrlRelCourseCategory;
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
     * Get all course categories in an access url, including the sub-categories
     * of the categories assigned to it.
     *
     * @return CourseCategory[]
     */
    public function findAllInAccessUrl(int $accessUrl, bool $allowBaseCategories = false, int $parentId = 0): array
    {
        $accessUrlIds = [$accessUrl];

        if ($allowBaseCategories) {
            $accessUrlIds[] = 1;
        }

        $categoryIds = $this->findIdsInAccessUrlTree($accessUrlIds);

        if (empty($categoryIds)) {
            return [];
        }

        $qb = $this->createQueryBuilder('c');
        $qb
            ->where($qb->expr()->in('c.id', ':categoryIds'))
            ->setParameter('categoryIds', $categoryIds)
            ->orderBy('c.treePos', Criteria::ASC)
        ;

        if (!empty($parentId)) {
            $qb->andWhere($qb->expr()->eq('c.parent', ':parentId'))
                ->setParameter('parentId', $parentId)
            ;
        } else {
            $qb->andWhere($qb->expr()->isNull('c.parent'));
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * IDs of the categories visible in the given access urls: the categories assigned
     * to one of them, plus the whole tree of sub-categories below each one.
     *
     * @param int[] $accessUrlIds
     *
     * @return int[]
     */
    public function findIdsInAccessUrlTree(array $accessUrlIds): array
    {
        $ids = $this->createQueryBuilder('c')
            ->select('c.id')
            ->innerJoin(
                AccessUrlRelCourseCategory::class,
                'a',
                Join::WITH,
                'c = a.courseCategory'
            )
            ->where('a.url IN (:accessUrlIds)')
            ->setParameter('accessUrlIds', array_map('intval', $accessUrlIds))
            ->getQuery()
            ->getSingleColumnResult()
        ;

        return $this->findWithDescendantIds($ids);
    }

    /**
     * The given category IDs plus the IDs of every category below them in the tree.
     *
     * @param int[] $categoryIds
     *
     * @return int[]
     */
    public function findWithDescendantIds(array $categoryIds): array
    {
        $ids = array_values(array_unique(array_map('intval', $categoryIds)));
        $parentIds = $ids;

        while (!empty($parentIds)) {
            $childIds = $this->createQueryBuilder('c')
                ->select('c.id')
                ->where('c.parent IN (:parentIds)')
                ->andWhere('c.id NOT IN (:knownIds)')
                ->setParameter('parentIds', $parentIds)
                ->setParameter('knownIds', $ids)
                ->getQuery()
                ->getSingleColumnResult()
            ;
            $parentIds = array_map('intval', $childIds);
            $ids = array_merge($ids, $parentIds);
        }

        return $ids;
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
                AccessUrlRelCourseCategory::class,
                'a',
                Join::ON,
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
