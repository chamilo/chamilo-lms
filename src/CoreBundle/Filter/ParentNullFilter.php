<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Filter;

use ApiPlatform\Doctrine\Orm\Filter\AbstractFilter;
use ApiPlatform\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use ApiPlatform\Metadata\Operation;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Psr\Log\LoggerInterface;
use Symfony\Component\Serializer\NameConverter\NameConverterInterface;

class ParentNullFilter extends AbstractFilter
{
    public function __construct(
        ManagerRegistry $managerRegistry,
        ?LoggerInterface $logger = null,
        ?array $properties = null,
        ?NameConverterInterface $nameConverter = null,
    ) {
        parent::__construct($managerRegistry, $logger, $properties, $nameConverter);
    }

    public function getDescription(string $resourceClass): array
    {
        $description = [];

        foreach ($this->properties as $property => $strategy) {
            $description[$property.'[exists]'] = [
                'property' => $property,
                'type' => 'bool',
                'required' => false,
                'description' => sprintf('Filter on %s: IS NULL or IS NOT NULL', $property),
            ];
        }

        return $description;
    }

    protected function filterProperty(
        string $property,
               $value,
        QueryBuilder $queryBuilder,
        QueryNameGeneratorInterface $queryNameGenerator,
        string $resourceClass,
        ?Operation $operation = null,
        array $context = [],
    ): void {
        if (!array_key_exists($property, $this->properties)) {
            return;
        }

        $alias = $queryBuilder->getRootAliases()[0];

        if (str_contains($property, '.')) {
            $parts = explode('.', $property);
            $joinAlias = $queryNameGenerator->generateJoinAlias($parts[0]);
            $queryBuilder->leftJoin(sprintf('%s.%s', $alias, $parts[0]), $joinAlias);
            $field = sprintf('%s.%s', $joinAlias, $parts[1]);
        } else {
            $field = sprintf('%s.%s', $alias, $property);
        }

        if ($value === 'true' || $value === true || $value === '1' || $value === 1) {
            $queryBuilder->andWhere(sprintf('%s IS NOT NULL', $field));
        } else {
            $queryBuilder->andWhere(sprintf('%s IS NULL', $field));
        }
    }
}
