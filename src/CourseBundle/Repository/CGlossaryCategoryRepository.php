<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CourseBundle\Repository;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Repository\ResourceRepository;
use Chamilo\CourseBundle\Entity\CGlossaryCategory;
use Doctrine\Persistence\ManagerRegistry;

final class CGlossaryCategoryRepository extends ResourceRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CGlossaryCategory::class);
    }

    public function findInCourseContext(int $categoryId, Course $course, ?Session $session): ?CGlossaryCategory
    {
        if ($categoryId <= 0) {
            return null;
        }

        $qb = $this->createQueryBuilder('category')
            ->innerJoin('category.resourceNode', 'node')
            ->innerJoin('node.resourceLinks', 'link')
            ->andWhere('category.iid = :categoryId')
            ->andWhere('link.course = :course')
            ->setParameter('categoryId', $categoryId)
            ->setParameter('course', $course)
            ->setMaxResults(1)
        ;

        if ($session instanceof Session) {
            $qb->andWhere('(link.session = :session OR link.session IS NULL)')
                ->setParameter('session', $session)
            ;
        } else {
            $qb->andWhere('link.session IS NULL');
        }

        $category = $qb->getQuery()->getOneOrNullResult();

        return $category instanceof CGlossaryCategory ? $category : null;
    }

    public function findOneByTitleInExactContext(string $title, Course $course, ?Session $session): ?CGlossaryCategory
    {
        $qb = $this->createQueryBuilder('category')
            ->innerJoin('category.resourceNode', 'node')
            ->innerJoin('node.resourceLinks', 'link')
            ->andWhere('category.title = :title')
            ->andWhere('link.course = :course')
            ->setParameter('title', $title)
            ->setParameter('course', $course)
            ->setMaxResults(1)
        ;

        if ($session instanceof Session) {
            $qb->andWhere('link.session = :session')
                ->setParameter('session', $session)
            ;
        } else {
            $qb->andWhere('link.session IS NULL');
        }

        $category = $qb->getQuery()->getOneOrNullResult();

        return $category instanceof CGlossaryCategory ? $category : null;
    }

    public function findInExactCourseContext(int $categoryId, Course $course, ?Session $session): ?CGlossaryCategory
    {
        if ($categoryId <= 0) {
            return null;
        }

        $qb = $this->createQueryBuilder('category')
            ->innerJoin('category.resourceNode', 'node')
            ->innerJoin('node.resourceLinks', 'link')
            ->andWhere('category.iid = :categoryId')
            ->andWhere('link.course = :course')
            ->setParameter('categoryId', $categoryId)
            ->setParameter('course', $course)
            ->setMaxResults(1)
        ;

        if ($session instanceof Session) {
            $qb->andWhere('link.session = :session')
                ->setParameter('session', $session)
            ;
        } else {
            $qb->andWhere('link.session IS NULL');
        }

        $category = $qb->getQuery()->getOneOrNullResult();

        return $category instanceof CGlossaryCategory ? $category : null;
    }
}
