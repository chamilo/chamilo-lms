<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\DataProvider\Extension;

use ApiPlatform\Core\Bridge\Doctrine\Orm\Extension\QueryCollectionExtensionInterface;
//use ApiPlatform\Core\Bridge\Doctrine\Orm\Extension\QueryItemExtensionInterface;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use Chamilo\CoreBundle\Entity\PersonalFile;
use Chamilo\CoreBundle\Entity\ResourceLink;
use Chamilo\CourseBundle\Entity\CDocument;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Core\Security;

/**
 * Extension is called when loading api/personal_files.json.
 */
final class PersonalFileExtension implements QueryCollectionExtensionInterface //, QueryItemExtensionInterface
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
        if (PersonalFile::class !== $resourceClass) {
            return;
        }

        // Admin can see everything.
        /*if ($this->security->isGranted('ROLE_ADMIN')) {
            return;
        }*/

        if (null === $user = $this->security->getUser()) {
            return;
        }

        $request = $this->requestStack->getCurrentRequest();
        $isShared = 1 === (int) $request->get('shared');

        $rootAlias = $queryBuilder->getRootAliases()[0];
        $queryBuilder
            ->innerJoin("$rootAlias.resourceNode", 'node');

        if ($isShared) {
            $queryBuilder->leftJoin('node.resourceLinks', 'links');

            /*$queryBuilder
                ->andWhere('links.visibility != :visibilityDeleted')
                ->setParameter('visibilityDeleted', ResourceLink::VISIBILITY_DELETED)
            ;*/

            $queryBuilder
                ->andWhere('links.visibility = :visibility')
                ->setParameter('visibility', ResourceLink::VISIBILITY_PUBLISHED)
            ;

            $queryBuilder
                ->orWhere('links.user = :userLink')
                ->setParameter('userLink', $user)
            ;
        } else {
            $queryBuilder->orWhere('node.creator = :current');
            $queryBuilder->setParameter('current', $user->getId());
        }
    }
}
