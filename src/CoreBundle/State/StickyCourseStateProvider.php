<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\State;

use ApiPlatform\Doctrine\Orm\Extension\FilterExtension;
use ApiPlatform\Doctrine\Orm\Extension\PaginationExtension;
use ApiPlatform\Doctrine\Orm\Extension\QueryResultCollectionExtensionInterface;
use ApiPlatform\Doctrine\Orm\Util\QueryNameGenerator;
use ApiPlatform\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Helpers\AccessUrlHelper;
use Chamilo\CoreBundle\Repository\Node\CourseRepository;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\QueryBuilder;

/**
 * @implements ProviderInterface<Course>
 */
readonly class StickyCourseStateProvider implements ProviderInterface
{
    public function __construct(
        private FilterExtension $filterExtension,
        private PaginationExtension $paginationExtension,
        private CourseRepository $courseRepository,
        private AccessUrlHelper $accessUrlHelper,
    ) {}

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): array|object|null
    {
        $queryNameGenerator = new QueryNameGenerator();
        $queryBuilder = $this->createQueryBuilder($queryNameGenerator);

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

        if ($this->paginationExtension instanceof QueryResultCollectionExtensionInterface
            && $this->paginationExtension->supportsResult(Course::class, $operation, $context)
        ) {
            return $this->paginationExtension->getResult($queryBuilder, Course::class, $operation, $context);
        }

        return $queryBuilder->getQuery()->getResult();
    }

    private function createQueryBuilder(QueryNameGeneratorInterface $queryNameGenerator): QueryBuilder
    {
        $qb = $this->courseRepository->createQueryBuilder('c');

        $aurcAlias = $queryNameGenerator->generateJoinAlias('aurc');
        $urlParam = $queryNameGenerator->generateParameterName('accessUrl');
        $visibilityParam = $queryNameGenerator->generateParameterName('avoid_visibility');

        if ($this->accessUrlHelper->isMultiple()) {
            $qb
                ->innerJoin(
                    'c.urls',
                    $aurcAlias,
                    Join::WITH,
                    $qb->expr()->eq("$aurcAlias.url", ":$urlParam")
                )
                ->setParameter($urlParam, $this->accessUrlHelper->getCurrent()->getId())
            ;
        }

        $qb
            ->andWhere($qb->expr()->eq('c.sticky', $qb->expr()->literal(true)))
            ->andWhere($qb->expr()->notIn('c.visibility', ":$visibilityParam"))
            ->setParameter($visibilityParam, [Course::HIDDEN, Course::CLOSED])
        ;

        return $qb;
    }
}