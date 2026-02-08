<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\DataProvider\Extension;

use ApiPlatform\Doctrine\Orm\Extension\QueryCollectionExtensionInterface;
use ApiPlatform\Doctrine\Orm\Extension\QueryItemExtensionInterface;
use ApiPlatform\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use ApiPlatform\Metadata\Operation;
use Chamilo\CoreBundle\Entity\ResourceRestrictToGroupContextInterface;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\HttpFoundation\RequestStack;

final class GroupContextQueryExtension implements QueryCollectionExtensionInterface, QueryItemExtensionInterface
{
    public function __construct(
        private readonly RequestStack $requestStack,
    ) {}

    public function applyToCollection(
        QueryBuilder $queryBuilder,
        QueryNameGeneratorInterface $queryNameGenerator,
        string $resourceClass,
        ?Operation $operation = null,
        array $context = []
    ): void {
        $this->applyGroupRestriction($queryBuilder, $queryNameGenerator, $resourceClass, $operation);
    }

    public function applyToItem(
        QueryBuilder $queryBuilder,
        QueryNameGeneratorInterface $queryNameGenerator,
        string $resourceClass,
        array $identifiers,
        ?Operation $operation = null,
        array $context = []
    ): void {
        $this->applyGroupRestriction($queryBuilder, $queryNameGenerator, $resourceClass, $operation);
    }

    private function applyGroupRestriction(
        QueryBuilder $queryBuilder,
        QueryNameGeneratorInterface $queryNameGenerator,
        string $resourceClass,
        ?Operation $operation
    ): void {
        // When API output is a DTO, $resourceClass may not be the entity class.
        $effectiveClass = $operation?->getClass() ?? $resourceClass;

        if (!is_a($effectiveClass, ResourceRestrictToGroupContextInterface::class, true)) {
            return;
        }

        $request = $this->requestStack->getMainRequest() ?? $this->requestStack->getCurrentRequest();
        if (null === $request) {
            return;
        }

        $gid = $request->query->getInt('gid', 0);

        $rootAliases = $queryBuilder->getRootAliases();
        if (empty($rootAliases)) {
            return;
        }

        $rootAlias = $rootAliases[0];

        $rnAlias = $queryNameGenerator->generateJoinAlias('resourceNode');
        $queryBuilder->join(\sprintf('%s.resourceNode', $rootAlias), $rnAlias);
        $queryBuilder->distinct();
        if ($gid > 0) {
            $rlAlias = $queryNameGenerator->generateJoinAlias('resourceLinks');
            $queryBuilder->join(\sprintf('%s.resourceLinks', $rnAlias), $rlAlias);

            // Match group by identifier to avoid association-vs-int issues
            $queryBuilder
                ->andWhere(\sprintf('IDENTITY(%s.group) = :gid', $rlAlias))
                ->setParameter('gid', $gid)
            ;

            return;
        }

        // gid = 0 -> exclude any resource that has at least one group link
        $rlGroupAlias = $queryNameGenerator->generateJoinAlias('groupLinks');
        $queryBuilder->leftJoin(
            \sprintf('%s.resourceLinks', $rnAlias),
            $rlGroupAlias,
            'WITH',
            \sprintf('%s.group IS NOT NULL', $rlGroupAlias)
        );

        $queryBuilder->andWhere(\sprintf('%s.id IS NULL', $rlGroupAlias));
    }
}
