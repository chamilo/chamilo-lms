<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Filter;

use ApiPlatform\Doctrine\Orm\Filter\AbstractFilter;
use ApiPlatform\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use ApiPlatform\Metadata\Operation;
use Chamilo\CoreBundle\Entity\ExtraField;
use Chamilo\CoreBundle\Entity\ExtraFieldValues;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\PropertyInfo\Type;

class UserExtraFieldFilter extends AbstractFilter
{
    public function getDescription(string $resourceClass): array
    {
        if (!$this->properties) {
            return [];
        }

        $description = [];

        foreach ($this->properties as $property => $strategy) {
            $description[$property] = [
                'property' => $property,
                'type' => Type::BUILTIN_TYPE_STRING,
                'required' => false,
                'description' => 'Properties to use as filters. To search by a user extra field',
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
        array $context = []
    ): void {
        if (!$this->isPropertyEnabled($property, $resourceClass)) {
            return;
        }

        $alias = $queryBuilder->getRootAliases()[0];

        switch ($property) {
            case 'userExtraFieldName':
                $efvAlias = $queryNameGenerator->generateJoinAlias('efv');
                $efAlias = $queryNameGenerator->generateJoinAlias('ef');
                $itemTypeName = $queryNameGenerator->generateParameterName('itemType');
                $variableName = $queryNameGenerator->generateParameterName('variable');

                $queryBuilder
                    ->innerJoin(
                        ExtraFieldValues::class,
                        $efvAlias,
                        Join::WITH,
                        "$alias.user = $efvAlias.itemId"
                    )
                    ->innerJoin("$efvAlias.field", $efAlias)
                    ->andWhere("$efAlias.itemType = :$itemTypeName")
                    ->andWhere("$efAlias.variable = :$variableName")
                    ->andWhere("$efvAlias.fieldValue = :uef_value")
                ;

                $queryBuilder
                    ->setParameter($itemTypeName, ExtraField::USER_FIELD_TYPE)
                    ->setParameter($variableName, $value)
                ;

                break;

            case 'userExtraFieldValue':
                $queryBuilder->setParameter('uef_value', $value);

                break;
        }
    }
}
