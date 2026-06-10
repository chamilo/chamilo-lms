<?php

declare(strict_types=1);

namespace Chamilo\CoreBundle\DataProvider\Extension;

use Chamilo\CoreBundle\Entity\ResourceLink;
use Doctrine\ORM\QueryBuilder;

/**
 * You should inject Symfony\Bundle\SecurityBundle\Security in the constructor.
 */
trait CourseLinkExtensionTrait
{
    protected function addCourseLinkWithVisibilityConditions(QueryBuilder $queryBuilder, bool $checkVisibility): void
    {
        $rootAlias = $queryBuilder->getRootAliases()[0];

        $joins = $queryBuilder->getDQLPart('join');

        if (empty($joins[$rootAlias])
            || !array_filter($joins[$rootAlias], fn ($j) => 'node' === $j->getAlias())
        ) {
            $queryBuilder->innerJoin("$rootAlias.resourceNode", 'node');
        }

        if (empty($joins[$rootAlias])
            || !array_filter($joins[$rootAlias], fn ($j) => 'resource_links' === $j->getAlias())
        ) {
            $queryBuilder->innerJoin('node.resourceLinks', 'resource_links');
        }

        if ($checkVisibility) {
            $this->addVisibilityCondition($queryBuilder);
        }
    }

    protected function addVisibilityCondition(QueryBuilder $queryBuilder): void
    {
        if ($this->canViewDraftResources()) {
            return;
        }

        $queryBuilder
            ->andWhere('resource_links.visibility != :visibilityDraft')
            ->setParameter('visibilityDraft', ResourceLink::VISIBILITY_DRAFT)
        ;
    }

    /**
     * Whether the current user may see draft (unpublished) course resources:
     * platform admins and teachers of the current course/session.
     *
     * Context roles (ROLE_CURRENT_COURSE_*) live in User::$temporaryRoles and appear in
     * $user->getRoles(), but Symfony's AbstractToken::getRoleNames() only returns roles
     * fixed at token-creation time, so isGranted() misses them — read them from the User.
     */
    protected function canViewDraftResources(): bool
    {
        $roles = $this->security->getUser()?->getRoles() ?? [];

        return $this->security->isGranted('ROLE_ADMIN')
            || \in_array('ROLE_CURRENT_COURSE_TEACHER', $roles, true)
            || \in_array('ROLE_CURRENT_COURSE_SESSION_TEACHER', $roles, true);
    }
}
