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
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Helpers\AccessUrlHelper;
use Chamilo\CoreBundle\Helpers\CourseCatalogueHelper;
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
        private CourseCatalogueHelper $courseCatalogueHelper,
    ) {}

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): array|object|null
    {
        $user = $this->userHelper->getCurrent();
        $isAuthenticated = $user instanceof User;

        if (!$isAuthenticated
            && 'false' !== $this->settingsManager->getSetting('catalog.course_catalog_published', true)
        ) {
            throw new AccessDeniedHttpException($this->translator->trans('Not allowed'));
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

        /*dd(
            $queryBuilder->getQuery()->getSQL(),
            $queryBuilder->getParameters()->toArray(),
        );*/

        /** @var Course $course */
        foreach ($paginator as $course) {
            $course->subscribed = $course->hasSubscriptionByUser($user);
        }

        return $paginator;
    }

    private function createQueryBuilder(): QueryBuilder
    {
        $qb = $this->courseRepository->createQueryBuilder('c');

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

        $this->courseCatalogueHelper->addAvoidedCoursesCondition($qb);
        $this->courseCatalogueHelper->addOnlySelectedCategoriesCondition($qb);
        $this->courseCatalogueHelper->addShowInCatalogueCondition($qb);
        $this->courseCatalogueHelper->addVisibilityCondition($qb, true);

        return $qb;
    }
}
