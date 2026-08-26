<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\State;

use ApiPlatform\Doctrine\Orm\Extension\FilterExtension;
use ApiPlatform\Doctrine\Orm\Extension\OrderExtension;
use ApiPlatform\Doctrine\Orm\Extension\PaginationExtension;
use ApiPlatform\Doctrine\Orm\Extension\QueryResultCollectionExtensionInterface;
use ApiPlatform\Doctrine\Orm\State\CollectionProvider;
use ApiPlatform\Doctrine\Orm\Util\QueryNameGenerator;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use Chamilo\CoreBundle\Entity\CourseRelUser;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Helpers\AccessUrlHelper;
use Chamilo\CoreBundle\Helpers\AccessUrlScopeHelper;
use Chamilo\CoreBundle\Helpers\UserHelper;
use Chamilo\CoreBundle\Repository\CourseRelUserRepository;
use Chamilo\CoreBundle\Repository\Node\CourseRepository;
use Chamilo\CoreBundle\Security\Authorization\Voter\CourseVoter;
use Doctrine\ORM\QueryBuilder;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

/**
 * @template-implements ProviderInterface<object>
 */
final class CourseRelUserCollectionStateProvider implements ProviderInterface
{
    private array $extensions;

    public function __construct(
        private readonly CollectionProvider $collectionProvider,
        private readonly CourseRelUserRepository $courseRelUserRepository,
        private readonly UserHelper $userHelper,
        private readonly Security $security,
        private readonly CourseRepository $courseRepo,
        private readonly AccessUrlHelper $accessUrlHelper,
        private readonly AccessUrlScopeHelper $accessUrlScope,
        FilterExtension $filterExtension,
        OrderExtension $orderExtension,
        PaginationExtension $paginationExtension,
    ) {
        $this->extensions = [$filterExtension, $orderExtension, $paginationExtension];
    }

    /**
     * @param array<string, mixed> $uriVariables
     * @param array<string, mixed> $context
     *
     * @return iterable<object>|object|null
     */
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): array|object|null
    {
        if (!$operation instanceof GetCollection) {
            return $this->collectionProvider->provide($operation, $uriVariables, $context);
        }

        $currentUser = $this->userHelper->getCurrent();

        // Privileged roles: full collection, scoped to the caller's own portal(s) in a
        // multi-URL install. An unrestricted admin (or a single-URL install) pays nothing --
        // see resolvePrivilegedScopeUrlIds().
        if (
            $this->security->isGranted('ROLE_ADMIN')
            || $this->security->isGranted('ROLE_SUPER_ADMIN')
            || $this->security->isGranted('ROLE_GLOBAL_ADMIN')
        ) {
            $scopeUrlIds = $this->resolvePrivilegedScopeUrlIds($currentUser);

            if (null === $scopeUrlIds) {
                return $this->collectionProvider->provide($operation, $uriVariables, $context);
            }

            $qb = $this->courseRelUserRepository->createQueryBuilder('cru');
            $qb->innerJoin('cru.course', 'scope_course')
                ->innerJoin('scope_course.urls', 'scope_course_rel')
                ->innerJoin('scope_course_rel.url', 'scope_url')
                ->andWhere('scope_url.id IN (:scopeUrlIds)')
                ->setParameter('scopeUrlIds', $scopeUrlIds)
                ->distinct()
            ;

            return $this->applyExtensionsAndGetResult($qb, $operation, $context);
        }

        // Students and other authenticated users: restrict to their own subscriptions.
        if (!$currentUser instanceof User) {
            throw new AccessDeniedException('User not authenticated.');
        }

        if ($context['filters']['course'] ?? null) {
            $course = $this->courseRepo->find($context['filters']['course']);

            if (!$this->security->isGranted(CourseVoter::VIEW, $course)) {
                throw new AccessDeniedException();
            }
        }

        $qb = $this->courseRelUserRepository->createQueryBuilder('cru');
        $qb
            ->andWhere(
                $qb->expr()->exists(
                    'SELECT 1 FROM '.CourseRelUser::class.' my_cru
                     WHERE my_cru.course = cru.course
                       AND my_cru.user = :currentUser'
                )
            )
            ->setParameter('currentUser', $currentUser->getId())
        ;

        return $this->applyExtensionsAndGetResult($qb, $operation, $context);
    }

    /**
     * @return int[]|null null means "no scoping needed" -- either a single-URL install, or the
     *                    caller is unrestricted -- and the caller must delegate to the default
     *                    CollectionProvider unchanged
     */
    private function resolvePrivilegedScopeUrlIds(?User $currentUser): ?array
    {
        if (!$this->accessUrlHelper->isMultiple()) {
            return null;
        }

        // ROLE_SUPER_ADMIN no longer exists in the role hierarchy (config/packages/security.yaml);
        // kept as a defensive check in case something still grants it directly.
        if ($this->security->isGranted('ROLE_SUPER_ADMIN') || !$currentUser instanceof User) {
            return null;
        }

        if ($this->security->isGranted('ROLE_GLOBAL_ADMIN')) {
            // null from getManagedUrlIds() means unrestricted (registered in the topmost URL of
            // a tree) -- see AccessUrlScopeHelper.
            return $this->accessUrlScope->getManagedUrlIds($currentUser);
        }

        // A plain ROLE_ADMIN (not a global admin) is confined to the portal serving this
        // request, same as any non-admin user -- their authority is a property of the current
        // URL, not of a managed subtree.
        $currentUrl = $this->accessUrlHelper->getCurrent();

        return (null !== $currentUrl && null !== $currentUrl->getId()) ? [(int) $currentUrl->getId()] : null;
    }

    /**
     * @param array<string, mixed> $context
     *
     * @return iterable<object>
     */
    private function applyExtensionsAndGetResult(QueryBuilder $qb, Operation $operation, array $context): iterable
    {
        $queryNameGenerator = new QueryNameGenerator();
        $items = [];

        foreach ($this->extensions as $extension) {
            $extension->applyToCollection($qb, $queryNameGenerator, CourseRelUser::class, $operation, $context);

            if (
                $extension instanceof QueryResultCollectionExtensionInterface
                && $extension->supportsResult(CourseRelUser::class, $operation, $context)
            ) {
                $items = $extension->getResult($qb, CourseRelUser::class, $operation, $context);
            }
        }

        return [] !== $items ? $items : $qb->getQuery()->getResult();
    }
}
