<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\DataProvider\Extension;

use ApiPlatform\Doctrine\Orm\Extension\QueryCollectionExtensionInterface;
use ApiPlatform\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use ApiPlatform\Metadata\Operation;
use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\ServiceHelper\AccessUrlHelper;
use Doctrine\ORM\QueryBuilder;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

// use ApiPlatform\Core\Bridge\Doctrine\Orm\Extension\QueryItemExtensionInterface;

/**
 * Extension is called when loading api/courses.json.
 */
final class CourseExtension implements QueryCollectionExtensionInterface
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
        $this->addWhere($queryBuilder, $resourceClass);
    }

    private function addWhere(QueryBuilder $queryBuilder, string $resourceClass): void
    {
        if (Course::class !== $resourceClass) {
            return;
        }

        if ($this->security->isGranted('ROLE_ADMIN')) {
            return;
        }

        if (null === $user = $this->security->getUser()) {
            throw new AccessDeniedException('Access Denied.');
        }

        if ($this->accessUrlHelper->isMultiple()) {
            $accessUrl = $this->accessUrlHelper->getCurrent();
            $rootAlias = $queryBuilder->getRootAliases()[0];

            $queryBuilder
                ->innerJoin("$rootAlias.urls", 'url_rel')
                ->andWhere('url_rel.url = :access_url_id')
                ->setParameter('access_url_id', $accessUrl->getId());
        }

        $rootAlias = $queryBuilder->getRootAliases()[0];

        $queryBuilder
            ->andWhere(sprintf('%s.visibility <> :visibility_hidden', $rootAlias))
            ->setParameter('visibility_hidden', Course::HIDDEN)
        ;
        $queryBuilder
            ->andWhere(sprintf('%s.visibility <> :visibility_closed', $rootAlias))
            ->setParameter('visibility_closed', Course::CLOSED)
        ;
    }
}
