<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\DataProvider\Extension;

use ApiPlatform\Doctrine\Orm\Extension\QueryCollectionExtensionInterface;
use ApiPlatform\Doctrine\Orm\Extension\QueryItemExtensionInterface;
use ApiPlatform\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use ApiPlatform\Metadata\Operation;
use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\ResourceRestrictToGroupContextInterface;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Service\Assignment\AssignmentGroupCategoryResolver;
use Chamilo\CourseBundle\Entity\CGroup;
use Chamilo\CourseBundle\Entity\CStudentPublication;
use Doctrine\ORM\QueryBuilder;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

final readonly class GroupContextQueryExtension implements QueryCollectionExtensionInterface, QueryItemExtensionInterface
{
    public function __construct(
        private RequestStack $requestStack,
        private Security $security,
        private AssignmentGroupCategoryResolver $assignmentGroupCategoryResolver,
    ) {}

    public function applyToCollection(
        QueryBuilder $queryBuilder,
        QueryNameGeneratorInterface $queryNameGenerator,
        string $resourceClass,
        ?Operation $operation = null,
        array $context = []
    ): void {
        $this->applyGroupRestriction($queryBuilder, $queryNameGenerator, $resourceClass, $operation);
    }

    /**
     * @param array<string, mixed> $identifiers
     * @param array<string, mixed> $context
     */
    public function applyToItem(
        QueryBuilder $queryBuilder,
        QueryNameGeneratorInterface $queryNameGenerator,
        string $resourceClass,
        array $identifiers,
        ?Operation $operation = null,
        array $context = []
    ): void {
        $this->applyGroupRestriction($queryBuilder, $queryNameGenerator, $resourceClass, $operation);
    }

    private function applyGroupRestriction(
        QueryBuilder $queryBuilder,
        QueryNameGeneratorInterface $queryNameGenerator,
        string $resourceClass,
        ?Operation $operation
    ): void {
        // When API output is a DTO, $resourceClass may not be the entity class.
        $effectiveClass = $operation?->getClass() ?? $resourceClass;

        if (!is_a($effectiveClass, ResourceRestrictToGroupContextInterface::class, true)) {
            return;
        }

        $request = $this->requestStack->getMainRequest() ?? $this->requestStack->getCurrentRequest();
        if (null === $request) {
            return;
        }

        $gid = $request->query->getInt('gid', 0);

        $rootAliases = $queryBuilder->getRootAliases();
        if (empty($rootAliases)) {
            return;
        }

        $rootAlias = $rootAliases[0];
        $isAssignment = is_a($effectiveClass, CStudentPublication::class, true);

        $rnAlias = $queryNameGenerator->generateJoinAlias('resourceNode');
        $queryBuilder->join(\sprintf('%s.resourceNode', $rootAlias), $rnAlias);
        $queryBuilder->distinct();

        if ($gid > 0) {
            $rlAlias = $queryNameGenerator->generateJoinAlias('resourceLinks');
            $queryBuilder->join(\sprintf('%s.resourceLinks', $rnAlias), $rlAlias);

            if ($isAssignment) {
                $groupCategoryId = $this->getCurrentGroupCategoryId($request, $gid);
                $groupConditions = $queryBuilder->expr()->orX(
                    \sprintf('IDENTITY(%s.group) = :gid', $rlAlias),
                    \sprintf('%s.groupCategoryWorkId = :allGroupsWorkId', $rootAlias),
                );

                if ($groupCategoryId > 0) {
                    $groupConditions->add(\sprintf('%s.groupCategoryWorkId = :groupCategoryWorkId', $rootAlias));
                    $queryBuilder->setParameter('groupCategoryWorkId', $groupCategoryId);
                }

                $queryBuilder
                    ->andWhere($groupConditions)
                    ->setParameter('gid', $gid)
                    ->setParameter('allGroupsWorkId', AssignmentGroupCategoryResolver::ALL_GROUPS)
                ;

                return;
            }

            // Regular resource restricted to one concrete group.
            $queryBuilder
                ->andWhere(\sprintf('IDENTITY(%s.group) = :gid', $rlAlias))
                ->setParameter('gid', $gid)
            ;

            return;
        }

        // gid = 0 -> exclude any resource that has at least one group link.
        $rlGroupAlias = $queryNameGenerator->generateJoinAlias('groupLinks');
        $queryBuilder->leftJoin(
            \sprintf('%s.resourceLinks', $rnAlias),
            $rlGroupAlias,
            'WITH',
            \sprintf('%s.group IS NOT NULL', $rlGroupAlias)
        );

        $queryBuilder->andWhere(\sprintf('%s.id IS NULL', $rlGroupAlias));

        // Common group assignments must stay manageable from the normal
        // Assignment tool, but learners should only see them from their group
        // context. CidReqListener already validates access to gid before this
        // extension runs.
        if ($isAssignment && !$this->canManageCourseContext()) {
            $queryBuilder->andWhere(\sprintf('%s.groupCategoryWorkId = 0', $rootAlias));
        }
    }

    private function getCurrentGroupCategoryId(Request $request, int $gid): int
    {
        if (!$request->hasSession()) {
            return 0;
        }

        $group = $request->getSession()->get('group');
        if (!$group instanceof CGroup || (int) $group->getIid() !== $gid) {
            return 0;
        }

        $course = $request->getSession()->get('course');
        if (!$course instanceof Course) {
            return 0;
        }

        $session = $request->getSession()->get('session');
        $session = $session instanceof Session ? $session : null;

        return $this->assignmentGroupCategoryResolver->getEffectiveCategoryId($group, $course, $session);
    }

    private function canManageCourseContext(): bool
    {
        $roles = $this->security->getUser()?->getRoles() ?? [];

        return $this->security->isGranted('ROLE_ADMIN')
            || \in_array('ROLE_CURRENT_COURSE_TEACHER', $roles, true)
            || \in_array('ROLE_CURRENT_COURSE_SESSION_TEACHER', $roles, true);
    }
}
