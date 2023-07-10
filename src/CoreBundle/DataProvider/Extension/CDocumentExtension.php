<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\DataProvider\Extension;

use ApiPlatform\Doctrine\Orm\Extension\QueryCollectionExtensionInterface;
//use ApiPlatform\Core\Bridge\Doctrine\Orm\Extension\QueryItemExtensionInterface;
use ApiPlatform\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use ApiPlatform\Metadata\Operation;
use Chamilo\CoreBundle\Entity\ResourceLink;
use Chamilo\CourseBundle\Entity\CDocument;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Core\Security;

/**
 * Extension is called when loading api/documents.json.
 */
final class CDocumentExtension implements QueryCollectionExtensionInterface //, QueryItemExtensionInterface
{
    use CourseLinkExtensionTrait;

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

    /*public function applyToItem(QueryBuilder $queryBuilder, QueryNameGeneratorInterface $queryNameGenerator, string $resourceClass, array $identifiers, string $operationName = null, array $context = []): void
    {
        $this->addWhere($queryBuilder, $resourceClass);
    }*/

    private function addWhere(QueryBuilder $queryBuilder, string $resourceClass): void
    {
        if (CDocument::class !== $resourceClass) {
            return;
        }

        /*if ($this->security->isGranted('ROLE_ADMIN')) {
            return;
        }*/

        if (null === $user = $this->security->getUser()) {
            throw new AccessDeniedException('Access Denied.');
        }

        $request = $this->requestStack->getCurrentRequest();

        // Listing documents must contain the resource node parent (resourceNode.parent) and the course (cid)
        // At least the cid so the CourseListener can be called.
        $resourceParentId = $request->query->get('resourceNode_parent');
        $courseId = $request->query->getInt('cid');
        $sessionId = $request->query->getInt('sid');
        $groupId = $request->query->getInt('gid');

        if (empty($resourceParentId)) {
            throw new AccessDeniedException('resourceNode.parent is required');
        }

        if (empty($courseId)) {
            throw new AccessDeniedException('cid is required');
        }

        $this->addCourseLinkWithVisibilityConditions($queryBuilder, true, $courseId, $sessionId, $groupId);

        /*$queryBuilder->
            andWhere('node.creator = :current_user')
        ;*/
        //$queryBuilder->andWhere(sprintf('%s.node.creator = :current_user', $rootAlias));
        //$queryBuilder->setParameter('current_user', $user->getId());
    }
}
