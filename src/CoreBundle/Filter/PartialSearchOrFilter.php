<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Filter;

use ApiPlatform\Doctrine\Orm\Filter\AbstractFilter;
use ApiPlatform\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use ApiPlatform\Metadata\Operation;
use Doctrine\ORM\QueryBuilder;
use InvalidArgumentException;

class PartialSearchOrFilter extends AbstractFilter
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
        $valueParameter = ':'.$queryNameGenerator->generateParameterName($property);
        $queryBuilder->setParameter($valueParameter, '%'.$value.'%');

        $ors = [];

        foreach (array_keys($this->properties) as $field) {
            // Detect if field is a relation (e.g. "user.username")
            if (str_contains($field, '.')) {
                [$relation, $subField] = explode('.', $field, 2);
                $joinAlias = $relation.'_alias';

                // Ensure the join is only added once
                if (!\in_array($joinAlias, $queryBuilder->getAllAliases(), true)) {
                    $queryBuilder->leftJoin("$alias.$relation", $joinAlias);
                }

                $ors[] = $queryBuilder->expr()->like(
                    "$joinAlias.$subField",
                    $valueParameter
                );
            } else {
                $ors[] = $queryBuilder->expr()->like(
                    "$alias.$field",
                    $valueParameter
                );
            }
        }

        $queryBuilder->andWhere($queryBuilder->expr()->orX(...$ors));
    }

    public function getDescription(string $resourceClass): array
    {
        return [
            'search' => [
                'property' => null,
                'type' => 'string',
                'required' => false,
                'description' => 'It does a "Search OR" using LIKE %%text%% on the listed fields (supports nested like user.username)',
            ],
        ];
    }
}
