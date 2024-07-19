<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\DataProvider\Extension;

use ApiPlatform\Doctrine\Orm\Extension\QueryCollectionExtensionInterface;
use ApiPlatform\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use ApiPlatform\Metadata\Operation;
use Chamilo\CoreBundle\Entity\CourseRelUser;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\ServiceHelper\AccessUrlHelper;
use Doctrine\ORM\QueryBuilder;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

final class CourseRelUserExtension implements QueryCollectionExtensionInterface
{
    public function __construct(
        private readonly Security $security,
        private readonly AccessUrlHelper $accessUrlHelper
    ) {}

    public function applyToCollection(
        QueryBuilder $queryBuilder,
        QueryNameGeneratorInterface $queryNameGenerator,
        string $resourceClass,
        ?Operation $operation = null,
        array $context = []
    ): void {
        if ($this->security->isGranted('ROLE_ADMIN')) {
           return;
        }

        if ($this->accessUrlHelper->isMultiple()) {
            $accessUrl = $this->accessUrlHelper->getCurrent();
            $rootAlias = $queryBuilder->getRootAliases()[0];

            $queryBuilder
                ->innerJoin("$rootAlias.course", 'c')
                ->innerJoin('c.urls', 'url_rel')
                ->andWhere('url_rel.url = :access_url_id')
                ->setParameter('access_url_id', $accessUrl->getId());
        }

        if (!$this->security->isGranted('ROLE_ADMIN')) {
            if (CourseRelUser::class === $resourceClass) {
                if ('collection_query' === $operation?->getName()) {
                    /** @var User|null $user */
                    if (null === $user = $this->security->getUser()) {
                        throw new AccessDeniedException('Access Denied.');
                    }

                    $rootAlias = $queryBuilder->getRootAliases()[0];
                    $queryBuilder->andWhere(sprintf('%s.user = :current_user', $rootAlias));
                    $queryBuilder->setParameter('current_user', $user->getId());
                }
            }
        }

        $this->addWhere($queryBuilder, $resourceClass);
    }

    private function addWhere(QueryBuilder $queryBuilder, string $resourceClass): void
    {
        if (CourseRelUser::class !== $resourceClass) {
            return;
        }

        if ($this->security->isGranted('ROLE_ADMIN')) {
            return;
        }

        // Need to be logged in to access the list.
        if (null === $user = $this->security->getUser()) {
            throw new AccessDeniedException('Access Denied.');
        }
    }
}
