<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Filter;

use ApiPlatform\Doctrine\Orm\Filter\AbstractFilter;
use ApiPlatform\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use ApiPlatform\Metadata\Operation;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Psr\Log\LoggerInterface;
use Symfony\Component\Serializer\NameConverter\NameConverterInterface;

class GlobalEventFilter extends AbstractFilter
{
    public function __construct(
        ManagerRegistry $managerRegistry,
        ?LoggerInterface $logger = null,
        ?array $properties = null,
        ?NameConverterInterface $nameConverter = null
    ) {
        parent::__construct($managerRegistry, $logger, $properties, $nameConverter);
    }

    protected function filterProperty(
        string $property,
        $value,
        QueryBuilder $queryBuilder,
        QueryNameGeneratorInterface $queryNameGenerator,
        string $resourceClass,
        ?Operation $operation = null,
        array $context = []
    ): void {
        $isGlobalType = isset($context['filters']['type']) && 'global' === $context['filters']['type'];
        if (!$isGlobalType) {
            return;
        }

        $rootAlias = $queryBuilder->getRootAliases()[0];
        $resourceNodeAlias = $queryNameGenerator->generateJoinAlias('resourceNode');
        $resourceLinkAlias = $queryNameGenerator->generateJoinAlias('resourceLink');
        $queryBuilder
            ->innerJoin("$rootAlias.resourceNode", $resourceNodeAlias)
            ->innerJoin("$resourceNodeAlias.resourceLinks", $resourceLinkAlias)
            ->andWhere("$resourceLinkAlias.course IS NULL")
            ->andWhere("$resourceLinkAlias.session IS NULL")
            ->andWhere("$resourceLinkAlias.group IS NULL")
            ->andWhere("$resourceLinkAlias.user IS NULL")
        ;
    }

    public function getDescription(string $resourceClass): array
    {
        return [
            'type' => [
                'property' => 'type',
                'type' => 'string',
                'required' => false,
                'description' => 'Filter events by type. Use "global" to get global events.',
            ],
        ];
    }
}
