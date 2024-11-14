<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Filter;

use ApiPlatform\Doctrine\Orm\Filter\AbstractFilter;
use ApiPlatform\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use ApiPlatform\Metadata\Operation;
use Doctrine\ORM\QueryBuilder;
use InvalidArgumentException;

class MatchSearchFilter extends AbstractFilter
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

        $matchFields = [];

        foreach (array_keys($this->properties) as $field) {
            $matchFields[] = "$alias.$field";
        }

        $queryBuilder
            ->andWhere('MATCH ('.implode(', ', $matchFields).') AGAINST ('.$valueParameter.' IN BOOLEAN MODE) > 0')
            ->setParameter($valueParameter, '+"'.$value.'"')
        ;
    }

    public function getDescription(string $resourceClass): array
    {
        return [
            'search' => [
                'property' => null,
                'type' => 'string',
                'required' => false,
                'description' => 'It does a "Search OR" using `MATCH AGAINST` to search for fields that contain `text`',
            ],
        ];
    }
}
