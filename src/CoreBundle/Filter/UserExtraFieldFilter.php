<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Filter;

use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\AbstractContextAwareFilter;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use Chamilo\CoreBundle\Entity\ExtraField;
use Chamilo\CoreBundle\Entity\ExtraFieldValues;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\PropertyInfo\Type;

class UserExtraFieldFilter extends AbstractContextAwareFilter
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
                'swagger' => [
                    'description' => 'Filter using a regex. This will appear in the Swagger documentation!',
                    'name' => 'Custom name to use in the Swagger documentation',
                    'type' => 'Will appear below the name in the Swagger documentation',
                ],
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
        string $operationName = null
    ): void {
        if (!$this->isPropertyEnabled($property, $resourceClass)) {
            return;
        }

        $alias = $queryBuilder->getRootAliases()[0];

        switch ($property) {
            case 'userExtraFieldName':
                $queryBuilder
                    ->innerJoin(
                        ExtraFieldValues::class,
                        'efv',
                        Join::WITH,
                        "$alias.user = efv.itemId"
                    )
                    ->innerJoin(ExtraField::class, 'ef', Join::WITH, 'efv.field = ef.id')
                    ->andWhere('ef.extraFieldType = :extraFieldType')
                    ->andWhere('ef.variable = :variable')
                ;

                $queryBuilder
                    ->setParameter('extraFieldType', ExtraField::USER_FIELD_TYPE)
                    ->setParameter('variable', $value)
                ;

                break;
            case 'userExtraFieldValue':
                $queryBuilder->andWhere('efv.value = :value');

                $queryBuilder->setParameter('value', $value);

                break;
        }
    }
}
