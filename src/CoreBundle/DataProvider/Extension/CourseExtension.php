<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\DataProvider\Extension;

use ApiPlatform\Doctrine\Orm\Extension\QueryCollectionExtensionInterface;
use ApiPlatform\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use ApiPlatform\Metadata\Operation;
use Chamilo\CoreBundle\Entity\Course;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Core\Security;

//use ApiPlatform\Core\Bridge\Doctrine\Orm\Extension\QueryItemExtensionInterface;

/**
 * Extension is called when loading api/courses.json.
 */
final class CourseExtension implements QueryCollectionExtensionInterface
{
    public function __construct(
        private readonly Security $security
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

    /*public function applyToItem(QueryBuilder $queryBuilder, QueryNameGeneratorInterface $queryNameGenerator, string $resourceClass, array $identifiers, string $operationName = null, array $context = []): void
    {
        error_log('applyToItem');
        $this->addWhere($queryBuilder, $resourceClass);
    }*/

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
