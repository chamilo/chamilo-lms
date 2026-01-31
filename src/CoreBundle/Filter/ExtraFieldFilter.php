<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Filter;

use ApiPlatform\Doctrine\Orm\Filter\AbstractFilter;
use ApiPlatform\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use ApiPlatform\Metadata\Operation;
use Chamilo\CoreBundle\Entity\ExtraField;
use Chamilo\CoreBundle\Entity\ExtraFieldValues;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use InvalidArgumentException;
use Psr\Log\LoggerInterface;
use Symfony\Component\Serializer\NameConverter\NameConverterInterface;

class ExtraFieldFilter extends AbstractFilter
{
    private string $fieldProperty = 'extrafield';
    private string $fieldValueProperty = 'extrafieldvalue';

    public function __construct(
        ManagerRegistry $managerRegistry,
        private readonly int $itemType = ExtraField::USER_FIELD_TYPE,
        ?LoggerInterface $logger = null,
        ?array $properties = null,
        ?NameConverterInterface $nameConverter = null
    ) {
        parent::__construct($managerRegistry, $logger, $properties, $nameConverter);
    }

    public function getDescription(string $resourceClass): array
    {
        return [
            "$this->fieldProperty[]" => [
                'is_collection' => true,
                'property' => null,
                'type' => 'string',
                'required' => false,
                'description' => 'Extra field variables',
                'openapi' => [
                    'schema' => [
                        'type' => 'array',
                        'items' => [
                            'type' => 'string',
                        ],
                    ],
                    'allowEmptyValue' => false,
                ],
            ],
            "$this->fieldValueProperty[]" => [
                'is_collection' => true,
                'property' => null,
                'type' => 'string',
                'required' => false,
                'description' => 'Extra field values',
                'openapi' => [
                    'schema' => [
                        'type' => 'array',
                        'items' => [
                            'type' => 'string',
                        ],
                    ],
                    'allowEmptyValue' => false,
                ],
            ],
        ];
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
        if (!\in_array($property, [$this->fieldProperty, $this->fieldValueProperty])) {
            return;
        }

        $countExtraFields = \count($context['filters'][$this->fieldProperty] ?? []);
        $countExtraFieldValues = \count($context['filters'][$this->fieldValueProperty] ?? []);

        if ($countExtraFields !== $countExtraFieldValues) {
            throw new InvalidArgumentException('Extra field variables and values must have the same length.');
        }

        $alias = $queryBuilder->getRootAliases()[0];

        if ($property === $this->fieldProperty) {
            foreach ($value as $idx => $fieldVariable) {
                $efvAlias = $queryNameGenerator->generateJoinAlias('efv');
                $efAlias = $queryNameGenerator->generateJoinAlias('ef');
                $itemTypeName = $queryNameGenerator->generateParameterName('itemType');
                $variableName = $queryNameGenerator->generateParameterName('variable');

                $queryBuilder
                    ->innerJoin(
                        ExtraFieldValues::class,
                        $efvAlias,
                        Join::WITH,
                        "$alias.id = $efvAlias.itemId"
                    )
                    ->innerJoin("$efvAlias.field", $efAlias)
                    ->andWhere($queryBuilder->expr()->eq("$efAlias.itemType", ":$itemTypeName"))
                    ->andWhere($queryBuilder->expr()->eq("$efAlias.variable", ":$variableName"))
                    ->andWhere($queryBuilder->expr()->eq("$efvAlias.fieldValue", ":valueName_$idx"))
                    ->setParameter($itemTypeName, $this->itemType)
                    ->setParameter($variableName, $fieldVariable)
                ;
            }

            return;
        }

        if ($property === $this->fieldValueProperty) {
            foreach ($value as $idx => $fieldValue) {
                $queryBuilder->setParameter("valueName_$idx", $fieldValue);
            }
        }
    }
}
