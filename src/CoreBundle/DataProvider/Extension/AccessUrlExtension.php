<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\DataProvider\Extension;

use ApiPlatform\Doctrine\Orm\Extension\QueryCollectionExtensionInterface;
use ApiPlatform\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use ApiPlatform\Metadata\Operation;
use Chamilo\CoreBundle\Entity\AccessUrl;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Helpers\AccessUrlScopeHelper;
use Doctrine\DBAL\ArrayParameterType;
use Doctrine\ORM\QueryBuilder;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

/**
 * Restricts GET /api/access_urls to the URLs a ROLE_GLOBAL_ADMIN manages. A plain
 * ROLE_ADMIN (who can already reach this collection at the resource's own security
 * level) is left untouched here — that broader, pre-existing access is a separate
 * concern from the global-admin subtree scoping this extension implements.
 */
final class AccessUrlExtension implements QueryCollectionExtensionInterface
{
    public function __construct(
        private readonly Security $security,
        private readonly AccessUrlScopeHelper $accessUrlScope,
    ) {}

    public function applyToCollection(
        QueryBuilder $queryBuilder,
        QueryNameGeneratorInterface $queryNameGenerator,
        string $resourceClass,
        ?Operation $operation = null,
        array $context = []
    ): void {
        if (AccessUrl::class !== $resourceClass || !$this->security->isGranted('ROLE_GLOBAL_ADMIN')) {
            return;
        }

        $user = $this->security->getUser();
        if (!$user instanceof User) {
            throw new AccessDeniedException('Access Denied AccessUrl');
        }

        $managedUrlIds = $this->accessUrlScope->getManagedUrlIds($user);
        if (null === $managedUrlIds) {
            return;
        }

        $alias = $queryBuilder->getRootAliases()[0];
        $queryBuilder->andWhere("$alias.id IN (:managedUrlIds)")
            ->setParameter('managedUrlIds', $managedUrlIds, ArrayParameterType::INTEGER)
        ;
    }
}
