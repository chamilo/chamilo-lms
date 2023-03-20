<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\DataProvider\Extension;

use ApiPlatform\Core\Bridge\Doctrine\Orm\Extension\QueryCollectionExtensionInterface;
//use ApiPlatform\Core\Bridge\Doctrine\Orm\Extension\QueryItemExtensionInterface;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use Chamilo\CoreBundle\Entity\Course;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Core\Security;

/**
 * Extension is called when loading api/courses.json.
 */
final class CourseExtension implements QueryCollectionExtensionInterface
{
    private Security $security;
    private RequestStack $requestStack;

    public function __construct(Security $security, RequestStack $request)
    {
        $this->security = $security;
        $this->requestStack = $request;
    }

    public function applyToCollection(QueryBuilder $queryBuilder, QueryNameGeneratorInterface $queryNameGenerator, string $resourceClass, string $operationName = null): void
    {
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
