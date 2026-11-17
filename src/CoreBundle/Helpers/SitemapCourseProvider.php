<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Helpers;

use Chamilo\CoreBundle\Entity\AccessUrl;
use Chamilo\CoreBundle\Entity\CatalogueCourseRelAccessUrlRelUsergroup;
use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\CourseRelUserCatalogue;
use Chamilo\CoreBundle\Entity\ExtraField;
use Chamilo\CoreBundle\Entity\ExtraFieldValues;
use Chamilo\CoreBundle\Repository\ExtraFieldRepository;
use Chamilo\CoreBundle\Repository\Node\CourseRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\QueryBuilder;

/**
 * Anonymous-only visibility rules for sitemap URLs, independent of the visitor's session.
 */
final readonly class SitemapCourseProvider
{
    public const int PAGE_SIZE = 5000;

    public function __construct(
        private CourseRepository $courseRepository,
        private EntityManagerInterface $entityManager,
        private AccessUrlHelper $accessUrlHelper,
        private CourseCatalogueHelper $courseCatalogueHelper,
        private ExtraFieldRepository $extraFieldRepository,
    ) {}

    public function countPublicCourses(AccessUrl $accessUrl): int
    {
        return (int) $this->createQueryBuilder($accessUrl)
            ->select('COUNT(DISTINCT c.id)')
            ->getQuery()
            ->getSingleScalarResult()
        ;
    }

    /**
     * @return list<int>
     */
    public function findPublicCourseIds(AccessUrl $accessUrl, int $page): array
    {
        if ($page < 1) {
            return [];
        }

        $rows = $this->createQueryBuilder($accessUrl)
            ->select('DISTINCT c.id')
            ->orderBy('c.id', 'ASC')
            ->setFirstResult(($page - 1) * self::PAGE_SIZE)
            ->setMaxResults(self::PAGE_SIZE)
            ->getQuery()
            ->getSingleColumnResult()
        ;

        return array_map('intval', $rows);
    }

    private function createQueryBuilder(AccessUrl $accessUrl): QueryBuilder
    {
        $qb = $this->courseRepository->createQueryBuilder('c')
            ->andWhere('c.visibility = :publicVisibility')
            ->andWhere('c.sticky = :notSticky')
            ->setParameter('publicVisibility', Course::OPEN_WORLD)
            ->setParameter('notSticky', false)
        ;

        if ($this->accessUrlHelper->isMultiple()) {
            $qb
                ->innerJoin('c.urls', 'courseUrl', Join::WITH, 'courseUrl.url = :accessUrlId')
                ->setParameter('accessUrlId', $accessUrl->getId())
            ;
        }

        $this->courseCatalogueHelper->addOnlySelectedCategoriesCondition($qb);
        $this->courseCatalogueHelper->addShowInCatalogueCondition($qb);

        // Any user-specific catalogue rule makes a course unsuitable for an anonymous sitemap.
        $privateRule = $this->entityManager->createQueryBuilder()
            ->select('privateRule.id')
            ->from(CourseRelUserCatalogue::class, 'privateRule')
            ->where('privateRule.course = c')
        ;
        $qb->andWhere($qb->expr()->not($qb->expr()->exists($privateRule->getDQL())));

        $hideField = $this->extraFieldRepository->findByVariable(ExtraField::COURSE_FIELD_TYPE, 'hide_from_catalog');
        if ($hideField instanceof ExtraField) {
            $hiddenValue = $this->entityManager->createQueryBuilder()
                ->select('hiddenValue.id')
                ->from(ExtraFieldValues::class, 'hiddenValue')
                ->where('hiddenValue.field = :hideField')
                ->andWhere('hiddenValue.itemId = c.id')
                ->andWhere('hiddenValue.fieldValue NOT IN (:visibleFieldValues)')
            ;

            $qb
                ->andWhere($qb->expr()->not($qb->expr()->exists($hiddenValue->getDQL())))
                ->setParameter('hideField', $hideField->getId())
                ->setParameter('visibleFieldValues', ['', '0'])
            ;
        }

        $catalogueRules = $this->entityManager->getRepository(CatalogueCourseRelAccessUrlRelUsergroup::class);
        if ($catalogueRules->hasRecordsForAccessUrl((int) $accessUrl->getId())) {
            // With catalogue rules present, only entries marked for everyone are public.
            $publicRule = $this->entityManager->createQueryBuilder()
                ->select('publicRule.id')
                ->from(CatalogueCourseRelAccessUrlRelUsergroup::class, 'publicRule')
                ->where('publicRule.course = c')
                ->andWhere('publicRule.accessUrl = :catalogueAccessUrl')
                ->andWhere('publicRule.usergroup IS NULL')
            ;

            $qb
                ->andWhere($qb->expr()->exists($publicRule->getDQL()))
                ->setParameter('catalogueAccessUrl', $accessUrl->getId())
            ;
        }

        return $qb;
    }
}
