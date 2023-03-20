<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\DataProvider\Extension;

use ApiPlatform\Core\Bridge\Doctrine\Orm\Extension\QueryCollectionExtensionInterface;
//use ApiPlatform\Core\Bridge\Doctrine\Orm\Extension\QueryItemExtensionInterface;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use Chamilo\CoreBundle\Entity\ResourceLink;
use Chamilo\CourseBundle\Entity\CToolIntro;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Core\Security;

final class CToolIntroExtension implements QueryCollectionExtensionInterface
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

    private function addWhere(QueryBuilder $queryBuilder, string $resourceClass): void
    {
        if (CToolIntro::class !== $resourceClass) {
            return;
        }

        /*if ($this->security->isGranted('ROLE_ADMIN')) {
            return;
        }*/

        if (null === $user = $this->security->getUser()) {
            throw new AccessDeniedException('Access Denied.');
        }

        $request = $this->requestStack->getCurrentRequest();

        $courseId = $request->query->get('cid');
        $sessionId = $request->query->get('sid');
        $groupId = $request->query->get('gid');

        $rootAlias = $queryBuilder->getRootAliases()[0];

        $queryBuilder
            ->innerJoin("$rootAlias.resourceNode", 'node')
            ->innerJoin('node.resourceLinks', 'links')
        ;

        // Do not show deleted resources.
        $queryBuilder
            ->andWhere('links.visibility != :visibilityDeleted')
            ->setParameter('visibilityDeleted', ResourceLink::VISIBILITY_DELETED)
        ;

        $allowDraft =
            $this->security->isGranted('ROLE_ADMIN') ||
            $this->security->isGranted('ROLE_CURRENT_COURSE_TEACHER')
        ;

        if (!$allowDraft) {
            $queryBuilder
                ->andWhere('links.visibility != :visibilityDraft')
                ->setParameter('visibilityDraft', ResourceLink::VISIBILITY_DRAFT)
            ;
        }

        $queryBuilder
            ->andWhere('links.course = :course')
            ->setParameter('course', $courseId)
        ;

        if (empty($sessionId)) {
            $queryBuilder->andWhere('links.session IS NULL');
        } else {
            $queryBuilder
                ->andWhere('links.session = :session')
                ->setParameter('session', $sessionId)
            ;
        }

        if (empty($groupId)) {
            $queryBuilder->andWhere('links.group IS NULL');
        } else {
            $queryBuilder
                ->andWhere('links.group = :group')
                ->setParameter('group', $groupId)
            ;
        }
    }
}
