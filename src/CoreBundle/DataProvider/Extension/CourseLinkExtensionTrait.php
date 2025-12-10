<?php

declare(strict_types=1);

namespace Chamilo\CoreBundle\DataProvider\Extension;

use Chamilo\CoreBundle\Entity\ResourceLink;
use Doctrine\ORM\QueryBuilder;
use Symfony\Bundle\SecurityBundle\Security;

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
        $allowDraft =
            $this->security->isGranted('ROLE_ADMIN')
            || $this->security->isGranted('ROLE_CURRENT_COURSE_TEACHER');

        if (!$allowDraft) {
            $queryBuilder
                ->andWhere('resource_links.visibility != :visibilityDraft')
                ->setParameter('visibilityDraft', ResourceLink::VISIBILITY_DRAFT)
            ;
        }
    }
}
