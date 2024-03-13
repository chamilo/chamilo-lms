<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\DataProvider\Extension;

use ApiPlatform\Doctrine\Orm\Extension\QueryCollectionExtensionInterface;
use ApiPlatform\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use ApiPlatform\Metadata\Operation;
use Chamilo\CoreBundle\Traits\ControllerTrait;
use Chamilo\CoreBundle\Traits\CourseControllerTrait;
use Chamilo\CourseBundle\Entity\CTool;
use Doctrine\ORM\QueryBuilder;

class CToolExtension implements QueryCollectionExtensionInterface
{
    use ControllerTrait;
    use CourseControllerTrait;

    public function applyToCollection(
        QueryBuilder $queryBuilder,
        QueryNameGeneratorInterface $queryNameGenerator,
        string $resourceClass,
        ?Operation $operation = null,
        array $context = []
    ): void {
        if (CTool::class !== $resourceClass) {
            return;
        }

        $alias = $queryBuilder->getRootAliases()[0];

        $queryBuilder
            ->innerJoin("$alias.resourceNode", 'resource_node')
            ->innerJoin('resource_node.resourceLinks', 'resource_links')
            ->andWhere(
                $queryBuilder->expr()->notIn("$alias.title", ['course_tool', 'course_homepage'])
            )
        ;
    }
}
