<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\State;

use ApiPlatform\Doctrine\Orm\Extension\FilterExtension;
use ApiPlatform\Doctrine\Orm\Extension\OrderExtension;
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
    private array $extensions;

    public function __construct(
        FilterExtension $filterExtension,
        OrderExtension $orderExtension,
        PaginationExtension $paginationExtension,
        private CourseRepository $courseRepository,
        private AccessUrlHelper $accessUrlHelper,
    ) {
        $this->extensions = [
            $filterExtension,
            $orderExtension,
            $paginationExtension,
        ];
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): array|object|null
    {
        $queryNameGenerator = new QueryNameGenerator();
        $queryBuilder = $this->createQueryBuilder($queryNameGenerator);

        foreach ($this->extensions as $extension) {
            $extension->applyToCollection($queryBuilder, $queryNameGenerator, Course::class, $operation, $context);

            if ($extension instanceof QueryResultCollectionExtensionInterface
                && $extension->supportsResult(Course::class, $operation, $context)
            ) {
                return $extension->getResult($queryBuilder, Course::class, $operation, $context);
            }
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