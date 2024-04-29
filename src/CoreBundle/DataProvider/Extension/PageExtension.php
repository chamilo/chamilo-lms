<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\DataProvider\Extension;

use ApiPlatform\Doctrine\Orm\Extension\QueryCollectionExtensionInterface;
use ApiPlatform\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use ApiPlatform\Metadata\Operation;
use Chamilo\CoreBundle\Entity\Page;
use Chamilo\CoreBundle\ServiceHelper\AccessUrlHelper;
use Doctrine\ORM\QueryBuilder;
use Symfony\Bundle\SecurityBundle\Security;

final class PageExtension implements QueryCollectionExtensionInterface // , QueryItemExtensionInterface
{
    public function __construct(
        private readonly Security $security,
        private readonly AccessUrlHelper $accessUrlHelper,
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

    private function addWhere(QueryBuilder $qb, string $resourceClass): void
    {
        if (Page::class !== $resourceClass) {
            return;
        }

        $alias = $qb->getRootAliases()[0];

        $url = $this->accessUrlHelper->getCurrent();

        // Url filter by default.
        $qb
            ->andWhere("$alias.url = :url")
            ->setParameter('url', $url->getId())
        ;

        if (!$this->security->isGranted('ROLE_ADMIN')) {
            $qb->andWhere("$alias.enabled = 1");
        }
    }
}
