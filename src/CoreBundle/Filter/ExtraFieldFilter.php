<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Filter;

use ApiPlatform\Doctrine\Orm\Filter\AbstractFilter;
use ApiPlatform\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use ApiPlatform\Metadata\Operation;
use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\ExtraField;
use Chamilo\CoreBundle\Entity\ExtraFieldValues;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Entity\User;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\QueryBuilder;
use InvalidArgumentException;

class ExtraFieldFilter extends AbstractFilter
{
    private string $fieldProperty = 'extrafield';
    private string $fieldValueProperty = 'extrafieldvalue';

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

        $efRepo = $this->managerRegistry->getRepository(ExtraField::class);

        $countExtraFields = \count($context['filters'][$this->fieldProperty] ?? []);
        $countExtraFieldValues = \count($context['filters'][$this->fieldValueProperty] ?? []);

        if ($countExtraFields !== $countExtraFieldValues) {
            throw new InvalidArgumentException('Extra field variables and values must have the same length.');
        }

        $itemType = $this->getItemTypeFromResource($resourceClass);

        $alias = $queryBuilder->getRootAliases()[0];

        if ($property === $this->fieldProperty) {
            foreach ($value as $idx => $fieldVariable) {
                $ef = $efRepo->findOneBy([
                    'itemType' => $itemType,
                    'variable' => $fieldVariable,
                    'filter' => true,
                    'visibleToSelf' => true,
                ]);

                if (!$ef) {
                    throw new InvalidArgumentException(\sprintf('Extra field "%s" not found.', $fieldVariable));
                }

                $efvAlias = $queryNameGenerator->generateJoinAlias('efv');

                $queryBuilder
                    ->innerJoin(
                        ExtraFieldValues::class,
                        $efvAlias,
                        Join::WITH,
                        $queryBuilder->expr()->andX(
                            $queryBuilder->expr()->eq("$efvAlias.field", $ef->getId()),
                            $queryBuilder->expr()->eq("$alias.id", "$efvAlias.itemId")
                        )
                    )
                    ->andWhere($queryBuilder->expr()->eq("$efvAlias.fieldValue", ":valueName_$idx"))
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

    private function getItemTypeFromResource(string $resourceClass): int
    {
        return match ($resourceClass) {
            User::class => ExtraField::USER_FIELD_TYPE,
            Course::class => ExtraField::COURSE_FIELD_TYPE,
            Session::class => ExtraField::SESSION_FIELD_TYPE,
            default => throw new InvalidArgumentException('Invalid resource class.'),
        };
    }
}
