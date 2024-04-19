<?php

declare(strict_types=1);

namespace Chamilo\CoreBundle\DataProvider\Extension;

use Chamilo\CoreBundle\Entity\ResourceLink;
use Doctrine\ORM\QueryBuilder;
use Symfony\Bundle\SecurityBundle\Security;

trait CourseLinkExtensionTrait
{
    public function __construct(
        private readonly Security $security
    ) {}

    protected function addCourseLinkWithVisibilityConditions(QueryBuilder $queryBuilder, bool $checkVisibility): void
    {
        $rootAlias = $queryBuilder->getRootAliases()[0];

        $queryBuilder
            ->innerJoin("$rootAlias.resourceNode", 'node')
            ->innerJoin('node.resourceLinks', 'resource_links')
        ;

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
