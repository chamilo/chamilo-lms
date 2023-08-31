<?php

declare(strict_types=1);

namespace Chamilo\CoreBundle\DataProvider\Extension;

use Chamilo\CoreBundle\Entity\ResourceLink;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\Security\Core\Security;

trait CourseLinkExtensionTrait
{
    public function __construct(
        private readonly Security $security
    ) {
    }

    protected function addCourseLinkWithVisibilityConditions(
        QueryBuilder $queryBuilder,
        bool $checkVisibility,
        int $courseId,
        ?int $sessionId,
        ?int $groupId
    ): void {
        $rootAlias = $queryBuilder->getRootAliases()[0];

        $queryBuilder
            ->innerJoin("$rootAlias.resourceNode", 'node')
            ->innerJoin('node.resourceLinks', 'links')
        ;

        if ($checkVisibility) {
            $this->addVisibilityCondition($queryBuilder);
        }

        $this->addCourseLinkCondition($queryBuilder, $courseId, $sessionId, $groupId);
    }

    protected function addVisibilityCondition(QueryBuilder $queryBuilder): void
    {
        // Do not show deleted resources.
        $queryBuilder
            ->andWhere('links.visibility != :visibilityDeleted')
            ->setParameter('visibilityDeleted', ResourceLink::VISIBILITY_DELETED)
        ;

        $allowDraft =
            $this->security->isGranted('ROLE_ADMIN') ||
            $this->security->isGranted('ROLE_CURRENT_COURSE_TEACHER');

        if (!$allowDraft) {
            $queryBuilder
                ->andWhere('links.visibility != :visibilityDraft')
                ->setParameter('visibilityDraft', ResourceLink::VISIBILITY_DRAFT)
            ;
        }
    }

    protected function addCourseLinkCondition(
        QueryBuilder $queryBuilder,
        int $courseId,
        ?int $sessionId,
        ?int $groupId
    ): void {
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
