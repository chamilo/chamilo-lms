<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\DataProvider\Extension;

use ApiPlatform\Doctrine\Orm\Extension\QueryCollectionExtensionInterface;
use ApiPlatform\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use ApiPlatform\Metadata\Operation;
use Chamilo\CoreBundle\Entity\CourseCategory;
use Chamilo\CoreBundle\Helpers\AccessUrlHelper;
use Chamilo\CoreBundle\Repository\CourseCategoryRepository;
use Chamilo\CoreBundle\Settings\SettingsManager;
use Doctrine\ORM\QueryBuilder;

final readonly class CourseCategoryExtension implements QueryCollectionExtensionInterface
{
    public function __construct(
        private AccessUrlHelper $accessUrlHelper,
        private SettingsManager $settingsManager,
        private CourseCategoryRepository $courseCategoryRepository,
    ) {}

    public function applyToCollection(
        QueryBuilder $queryBuilder,
        QueryNameGeneratorInterface $queryNameGenerator,
        string $resourceClass,
        ?Operation $operation = null,
        array $context = []
    ): void {
        if (CourseCategory::class !== $resourceClass) {
            return;
        }

        $this->addWhere($queryBuilder);
    }

    private function addWhere(QueryBuilder $queryBuilder): void
    {
        $rootAlias = $queryBuilder->getRootAliases()[0];

        $accessUrlIds = [
            $this->accessUrlHelper->getCurrent()?->getId(),
        ];

        if ('true' === $this->settingsManager->getSetting('course.allow_base_course_category')) {
            $accessUrlIds[] = $this->accessUrlHelper->getFirstAccessUrl()?->getId();
        }

        // Sub-categories are visible wherever their top-level category is assigned.
        $categoryIds = $this->courseCategoryRepository->findIdsInAccessUrlTree(array_filter($accessUrlIds));

        if (empty($categoryIds)) {
            $queryBuilder->andWhere('1 = 0');

            return;
        }

        $queryBuilder
            ->andWhere($queryBuilder->expr()->in("$rootAlias.id", ':course_category_ids'))
            ->setParameter('course_category_ids', $categoryIds)
        ;
    }
}
