<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\DataProvider\Extension;

use ApiPlatform\Doctrine\Orm\Extension\QueryCollectionExtensionInterface;
use ApiPlatform\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use ApiPlatform\Metadata\Operation;
use Chamilo\CoreBundle\Entity\MessageTag;
use Chamilo\CoreBundle\Helpers\UserHelper;
use Doctrine\ORM\QueryBuilder;

final readonly class MessageTagExtension implements QueryCollectionExtensionInterface // , QueryItemExtensionInterface
{
    public function __construct(
        private UserHelper $userHelper,
    ) {}

    public function applyToCollection(
        QueryBuilder $queryBuilder,
        QueryNameGeneratorInterface $queryNameGenerator,
        string $resourceClass,
        ?Operation $operation = null,
        array $context = []
    ): void {
        $this->addWhere($queryBuilder, $resourceClass);
    }

    private function addWhere(QueryBuilder $queryBuilder, string $resourceClass): void
    {
        if (MessageTag::class !== $resourceClass) {
            return;
        }

        $user = $this->userHelper->getCurrent();
        $alias = $queryBuilder->getRootAliases()[0];

        $queryBuilder
            ->andWhere($queryBuilder->expr()->eq("$alias.user", ':current'))
            ->setParameter('current', $user->getId())
        ;
    }
}
