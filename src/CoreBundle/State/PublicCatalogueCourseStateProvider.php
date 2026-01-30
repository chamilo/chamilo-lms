<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\State;

use ApiPlatform\Doctrine\Orm\Extension\FilterExtension;
use ApiPlatform\Doctrine\Orm\Extension\PaginationExtension;
use ApiPlatform\Doctrine\Orm\Util\QueryNameGenerator;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\ExtraFieldValues;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Helpers\AccessUrlHelper;
use Chamilo\CoreBundle\Helpers\UserHelper;
use Chamilo\CoreBundle\Repository\Node\CourseRepository;
use Chamilo\CoreBundle\Settings\SettingsManager;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @implements ProviderInterface<Course>
 */
readonly class PublicCatalogueCourseStateProvider implements ProviderInterface
{
    public const DEFAULT_PAGE_SIZE = 12;

    public function __construct(
        private FilterExtension $filterExtension,
        private PaginationExtension $paginationExtension,
        private UserHelper $userHelper,
        private CourseRepository $courseRepository,
        private SettingsManager $settingsManager,
        private AccessUrlHelper $accessUrlHelper,
        private TranslatorInterface $translator,
    ) {}

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): object|array|null
    {
        $user = $this->userHelper->getCurrent();
        $isAuthenticated = $user instanceof User;

        if (!$isAuthenticated
            && 'false' !== $this->settingsManager->getSetting('catalog.course_catalog_published', true)
        ) {
            throw new AccessDeniedHttpException(
                $this->translator->trans('Not allowed')
            );
        }

        $queryBuilder = $this->createQueryBuilder();
        $queryNameGenerator = new QueryNameGenerator();

        $this->filterExtension->applyToCollection(
            $queryBuilder,
            $queryNameGenerator,
            Course::class,
            $operation,
            $context
        );

        $this->paginationExtension->applyToCollection(
            $queryBuilder,
            $queryNameGenerator,
            Course::class,
            $operation,
            $context
        );

        /** @var object $paginator */
        $paginator = $this->paginationExtension->getResult(
            $queryBuilder,
            Course::class,
            $operation,
            $context
        );

        /** @var Course $course */
        foreach ($paginator as $course) {
            $course->subscribed = $course->hasSubscriptionByUser($user);
        }

        return $paginator;
    }

    private function createQueryBuilder(): QueryBuilder
    {
        $qb = $this->courseRepository->createQueryBuilder('c');

        if ($accessUrl = $this->accessUrlHelper->getCurrent()) {
            $qb
                ->innerJoin('c.urls', 'aurc')
                ->andWhere('aurc.url = :accessUrl')
                ->setParameter('accessUrl', $accessUrl->getId())
            ;
        }

        $onlyShowSelectedCourses = 'true' === $this->settingsManager->getSetting('catalog.only_show_selected_courses');
        $selectedCategories = $this->settingsManager->getSetting('catalog.only_show_course_from_selected_category');

        $visibilities = [Course::CLOSED, Course::HIDDEN];

        if ('true' === $this->settingsManager->getSetting('catalog.course_catalog_hide_private', true)) {
            $visibilities[] = Course::REGISTERED;
        }

        if (!empty($selectedCategories)) {
            $qb
                ->innerJoin('c.categories', 'cat')
                ->andWhere(
                    $qb->expr()->in('cat.code', ':selected_categories')
                )
                ->setParameter('selected_categories', $selectedCategories)
            ;
        }

        if ($onlyShowSelectedCourses) {
            $qb
                ->innerJoin(
                    ExtraFieldValues::class,
                    'efv',
                    Join::WITH,
                    $qb->expr()->eq('efv.fieldValue', '1')
                )
            ;
        }

        $qb
            ->andWhere($qb->expr()->notIn('c.visibility', ':visibilities'))
            ->setParameter('visibilities', $visibilities)
        ;

        return $qb;
    }
}
