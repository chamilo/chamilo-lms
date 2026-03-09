<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Helpers;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\CourseRelUserCatalogue;
use Chamilo\CoreBundle\Entity\ExtraField;
use Chamilo\CoreBundle\Entity\ExtraFieldValues;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Repository\ExtraFieldRepository;
use Chamilo\CoreBundle\Settings\SettingsManager;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\QueryBuilder;

readonly class CourseCatalogueHelper
{
    private EntityRepository $courseUserCatalogueRepo;

    public function __construct(
        private EntityManagerInterface $entityManager,
        private AccessUrlHelper $accessUrlHelper,
        private SettingsManager $settingsManager,
        private UserHelper $userHelper,
        private ExtraFieldRepository $extraFieldRepo,
    ) {
        $this->courseUserCatalogueRepo = $this->entityManager->getRepository(CourseRelUserCatalogue::class);
    }

    /**
     * Returns the IDs of courses that are restricted in the catalogue. If `$byUser` is set,
     * returns only those courses IDs that are allowed or not to be viewed in the catalogue for that user.
     */
    public function getRestrictedCoursesId(bool $visible = true, ?User $byUser = null): array
    {
        $qb = $this->courseUserCatalogueRepo->createQueryBuilder('tcruc');

        $qb
            ->select('c.id')
            ->distinct()
            ->innerJoin('tcruc.course', 'c')
        ;

        if ($this->accessUrlHelper->isMultiple()) {
            $qb
                ->innerJoin(
                    'c.urls',
                    'aurc',
                    Join::WITH,
                    $qb->expr()->eq('aurc.url', ':accessUrl')
                )
                ->setParameter('accessUrl', $this->accessUrlHelper->getCurrent()->getId())
            ;
        }

        $qb
            ->andWhere($qb->expr()->eq('tcruc.visible', ':visible'))
            ->setParameter('visible', $visible ? 1 : 0)
        ;

        if ($byUser) {
            $qb
                ->andWhere($qb->expr()->eq('tcruc.user', ':user'))
                ->setParameter('user', $byUser->getId())
            ;
        }

        return $qb->getQuery()->getSingleColumnResult();
    }

    /**
     * Adds the SQL conditions to filter courses only visible by the user in the catalogue.
     */
    public function addVisibilityCondition(
        QueryBuilder $qb,
        bool $hideClose = false,
        bool $checkHidePrivate = true,
    ): void {
        $excludedVisibilities = [];

        if ($hideClose) {
            $excludedVisibilities[] = Course::CLOSED;
            $excludedVisibilities[] = Course::HIDDEN;
        }

        if ($checkHidePrivate
            && 'true' === $this->settingsManager->getSetting('catalog.course_catalog_hide_private')
        ) {
            $excludedVisibilities[] = Course::REGISTERED;
        }

        if ($excludedVisibilities) {
            $qb
                ->andWhere($qb->expr()->notIn('c.visibility', ':excluded_visibilities'))
                ->setParameter('excluded_visibilities', $excludedVisibilities)
            ;
        }

        $currentUser = $this->userHelper->getCurrent();

        $restrictedCourses = $this->getRestrictedCoursesId();
        $allowedCoursesToCurrentUser = $this->getRestrictedCoursesId(true, $currentUser);

        if ($restrictedCourses) {
            $qb
                ->andWhere($qb->expr()->notIn('c.id', ':restricted_courses'))
                ->setParameter('restricted_courses', $restrictedCourses)
            ;

            if ($allowedCoursesToCurrentUser) {
                $qb
                    ->orWhere($qb->expr()->in('c.id', ':allowed_courses_to_current_user'))
                    ->setParameter('allowed_courses_to_current_user', $allowedCoursesToCurrentUser)
                ;
            }
        }

        $restrictedHiddenCourses = $this->getRestrictedCoursesId(false);
        $notAllowedCoursesToCurrentUser = $this->getRestrictedCoursesId(false, $currentUser);

        if ($restrictedHiddenCourses) {
            $qb
                ->andWhere($qb->expr()->notIn('c.id', ':restricted_hidden_courses'))
                ->setParameter('restricted_hidden_courses', $restrictedHiddenCourses)
            ;

            if ($notAllowedCoursesToCurrentUser) {
                $qb
                    ->orWhere($qb->expr()->notIn('c.id', ':not_allowed_courses_to_current_user'))
                    ->setParameter('not_allowed_courses_to_current_user', $notAllowedCoursesToCurrentUser)
                ;
            }
        }
    }

    public function addShowInCatalogueCondition(QueryBuilder $qb): void
    {
        if ('true' !== $this->settingsManager->getSetting('catalog.only_show_selected_courses')) {
            return;
        }

        $efShowInCatalogue = $this->extraFieldRepo->findByVariable(ExtraField::COURSE_FIELD_TYPE, 'show_in_catalogue');

        if (!$efShowInCatalogue) {
            return;
        }

        $qb
            ->innerJoin(
                ExtraFieldValues::class,
                'efv_show_in_catalogue',
                Join::WITH,
                $qb->expr()->andX(
                    $qb->expr()->eq('efv_show_in_catalogue.field', $efShowInCatalogue->getId()),
                    $qb->expr()->eq('efv_show_in_catalogue.itemId', 'c')
                )
            )
            ->andWhere($qb->expr()->eq('efv_show_in_catalogue.fieldValue', '1'))
        ;
    }

    public function addOnlySelectedCategoriesCondition(QueryBuilder $qb): void
    {
        $selectedCategories = $this->settingsManager->getSetting('catalog.only_show_course_from_selected_category');

        if (empty($selectedCategories)) {
            return;
        }

        $qb
            ->innerJoin('c.categories', 'cat')
            ->andWhere(
                $qb->expr()->in('cat.code', ':selected_categories')
            )
            ->setParameter('selected_categories', $selectedCategories)
        ;
    }

    public function addAvoidedCoursesCondition(QueryBuilder $qb): void
    {
        $isStudent = (bool) $this->userHelper->getCurrent()?->isStudent();
        $categoryToAvoid = $this->settingsManager->getSetting('course.course_category_code_to_use_as_model');

        $courseRepo = $this->entityManager->getRepository(Course::class);

        if (!empty($categoryToAvoid) && $isStudent) {
            $subQb = $courseRepo->createQueryBuilder('c2');

            $subQb
                ->join(
                    'c2.categories',
                    'cat2',
                    Join::WITH,
                    $qb->expr()->eq('c2', 'c')
                )
                ->where($qb->expr()->eq('cat2.code', ':category_to_avoid'))
            ;

            $qb
                ->andWhere(
                    $qb->expr()->not(
                        $qb->expr()->exists($subQb->getDQL())
                    )
                )
                ->setParameter('category_to_avoid', $categoryToAvoid)
            ;
        }

        $qb->andWhere(
            $qb->expr()->not($qb->expr()->eq('c.sticky', true))
        );

        $efHideFromCatalog = $this->extraFieldRepo->findByVariable(ExtraField::COURSE_FIELD_TYPE, 'hide_from_catalog');

        if ($efHideFromCatalog) {
            $qb
                ->leftJoin(
                    ExtraFieldValues::class,
                    'efv_hide_from_catalog',
                    Join::WITH,
                    $qb->expr()->andX(
                        $qb->expr()->eq('efv_hide_from_catalog.field', $efHideFromCatalog->getId()),
                        $qb->expr()->eq('efv_hide_from_catalog.itemId', 'c')
                    )
                )
                ->andWhere(
                    $qb->expr()->orX(
                        $qb->expr()->isNull('efv_hide_from_catalog'),
                        $qb->expr()->eq('efv_hide_from_catalog.fieldValue', $qb->expr()->literal('')),
                        $qb->expr()->eq('efv_hide_from_catalog.fieldValue', '0')
                    )
                )
            ;
        }
    }
}
