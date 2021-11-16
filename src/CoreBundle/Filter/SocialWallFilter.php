<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Filter;

use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\AbstractContextAwareFilter;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use Chamilo\CoreBundle\Entity\SocialPost;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\PropertyInfo\Type;

class SocialWallFilter extends AbstractContextAwareFilter
{
    public function getDescription(string $resourceClass): array
    {
        if (!$this->properties) {
            return [];
        }

        $description = [];
        foreach ($this->properties as $property => $strategy) {
            $description["socialwall_$property"] = [
                'property' => $property,
                'type' => Type::BUILTIN_TYPE_RESOURCE,
                'required' => false,
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
        if ('socialwall_wallOwner' !== $property) {
            return;
        }

        $rootAlias = $queryBuilder->getRootAliases()[0];

        $queryBuilder
            ->andWhere(sprintf('%1$s.sender = :owner OR %1$s.userReceiver = :owner', $rootAlias))
            ->andWhere(sprintf('%s.type = :type', $rootAlias))
            ->setParameters(
                [
                    'owner' => $value,
                    'type' => SocialPost::TYPE_WALL_POST,
                ]
            )
        ;
    }
}
