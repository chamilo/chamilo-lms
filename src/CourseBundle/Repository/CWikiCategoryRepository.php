<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CourseBundle\Repository;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Traits\NonResourceRepository;
use Chamilo\CoreBundle\Traits\Repository\ORM\NestedTreeRepositoryTrait;
use Chamilo\CourseBundle\Entity\CWikiCategory;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\DBAL\ArrayParameterType;
use Doctrine\Persistence\ManagerRegistry;

final class CWikiCategoryRepository extends ServiceEntityRepository
{
    use NestedTreeRepositoryTrait;
    use NonResourceRepository;

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CWikiCategory::class);

        $this->initializeTreeRepository($this->getEntityManager(), $this->getClassMetadata());
    }

    /**
     * @return array<int, CWikiCategory>
     */
    public function findByCourse(Course $course, ?Session $session = null): array
    {
        $queryBuilder = $this->createQueryBuilder('category')
            ->andWhere('category.course = :course')
            ->setParameter('course', $course)
            ->addOrderBy('category.lft', 'ASC')
            ->addOrderBy('category.id', 'ASC')
        ;

        if ($session instanceof Session) {
            $queryBuilder
                ->andWhere('category.session = :session')
                ->setParameter('session', $session)
            ;
        } else {
            $queryBuilder->andWhere('category.session IS NULL');
        }

        /** @var array<int, CWikiCategory> $categories */
        return $queryBuilder->getQuery()->getResult();
    }

    public function findOneInContext(int $categoryId, Course $course, ?Session $session = null): ?CWikiCategory
    {
        $queryBuilder = $this->createQueryBuilder('category')
            ->andWhere('category.id = :categoryId')
            ->andWhere('category.course = :course')
            ->setParameter('categoryId', $categoryId)
            ->setParameter('course', $course)
            ->setMaxResults(1)
        ;

        if ($session instanceof Session) {
            $queryBuilder
                ->andWhere('category.session = :session')
                ->setParameter('session', $session)
            ;
        } else {
            $queryBuilder->andWhere('category.session IS NULL');
        }

        $category = $queryBuilder->getQuery()->getOneOrNullResult();

        return $category instanceof CWikiCategory ? $category : null;
    }

    /**
     * @param array<int, int> $categoryIds
     *
     * @return array<int, CWikiCategory>
     */
    public function findByIdsInContext(array $categoryIds, Course $course, ?Session $session = null): array
    {
        $categoryIds = array_values(array_unique(array_filter(
            array_map('intval', $categoryIds),
            static fn (int $categoryId): bool => $categoryId > 0,
        )));

        if ([] === $categoryIds) {
            return [];
        }

        $queryBuilder = $this->createQueryBuilder('category')
            ->andWhere('category.id IN (:categoryIds)')
            ->andWhere('category.course = :course')
            ->setParameter('categoryIds', $categoryIds, ArrayParameterType::INTEGER)
            ->setParameter('course', $course)
            ->addOrderBy('category.lft', 'ASC')
            ->addOrderBy('category.id', 'ASC')
        ;

        if ($session instanceof Session) {
            $queryBuilder
                ->andWhere('category.session = :session')
                ->setParameter('session', $session)
            ;
        } else {
            $queryBuilder->andWhere('category.session IS NULL');
        }

        /** @var array<int, CWikiCategory> $categories */
        return $queryBuilder->getQuery()->getResult();
    }
}
