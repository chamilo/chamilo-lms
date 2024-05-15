<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Filter;

use ApiPlatform\Doctrine\Orm\Filter\AbstractFilter;
use ApiPlatform\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use ApiPlatform\Metadata\Operation;
use Doctrine\ORM\QueryBuilder;
use InvalidArgumentException;

class SearchOr extends AbstractFilter
{
    protected function filterProperty(
        string $property,
        $value,
        QueryBuilder $queryBuilder,
        QueryNameGeneratorInterface $queryNameGenerator,
        string $resourceClass,
        ?Operation $operation = null,
        array $context = []
    ): void {
        if ('search' !== $property) {
            return;
        }

        if (empty($value)) {
            throw new InvalidArgumentException('The property must not be empty.');
        }

        $alias = $queryBuilder->getRootAliases()[0];
        $valueParameter = $queryNameGenerator->generateParameterName($property);

        $ors = [];

        foreach (array_keys($this->properties) as $field) {
            $ors[] = $queryBuilder->expr()->like(
                "$alias.$field",
                ":$valueParameter"
            );

            $queryBuilder->setParameter($valueParameter, "%$value%");
        }

        $queryBuilder
            ->andWhere(
                $queryBuilder->expr()->orX(...$ors)
            )
        ;
    }

    public function getDescription(string $resourceClass): array
    {
        return [
            'search' => [
                'property' => null,
                'type' => 'string',
                'required' => false,
                'description' => 'Do a "Search OR" across multiple fields',
            ],
        ];
    }
}
