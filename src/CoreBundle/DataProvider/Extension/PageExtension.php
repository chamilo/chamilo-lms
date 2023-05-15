<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\DataProvider\Extension;

use ApiPlatform\Doctrine\Orm\Extension\QueryCollectionExtensionInterface;
use ApiPlatform\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use ApiPlatform\Metadata\Operation;
use Chamilo\CoreBundle\Entity\Page;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Security;

//use ApiPlatform\Core\Bridge\Doctrine\Orm\Extension\QueryItemExtensionInterface;
//use ApiPlatform\Core\Bridge\Doctrine\Orm\Extension\QueryItemExtensionInterface;

final class PageExtension implements QueryCollectionExtensionInterface //, QueryItemExtensionInterface
{
    public function __construct(
        private readonly Security $security,
        private readonly RequestStack $requestStack
    ) {
    }

    public function applyToCollection(
        QueryBuilder $queryBuilder,
        QueryNameGeneratorInterface $queryNameGenerator,
        string $resourceClass,
        Operation $operation = null,
        array $context = []
    ): void {
        $this->addWhere($queryBuilder, $resourceClass);
    }

    public function applyToItem(
        QueryBuilder $queryBuilder,
        QueryNameGeneratorInterface $queryNameGenerator,
        string $resourceClass,
        array $identifiers,
        string $operationName = null,
        array $context = []
    ): void {
        //$this->addWhere($queryBuilder, $resourceClass);
    }

    private function addWhere(QueryBuilder $qb, string $resourceClass): void
    {
        if (Page::class !== $resourceClass) {
            return;
        }

        $alias = $qb->getRootAliases()[0];

        $request = $this->requestStack->getCurrentRequest();
        $urlId = $request->getSession()->get('access_url_id');

        // Url filter by default.
        $qb
            ->andWhere("$alias.url = :url")
            ->setParameter('url', $urlId)
        ;

        if (!$this->security->isGranted('ROLE_ADMIN')) {
            $qb->andWhere("$alias.enabled = 1");
        }
    }
}
