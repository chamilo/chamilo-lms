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
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Entity\UserRelUser;
use Chamilo\CoreBundle\Helpers\UserHelper;
use Chamilo\CoreBundle\Repository\Node\UserRepository;
use Symfony\Bundle\SecurityBundle\Security;

/**
 * State provider for GET /api/users (collection).
 *
 * - Admins, teachers and session managers receive the full "user:read" group
 *   and no extra visibility filter: they can list all platform users.
 *
 * - Every other authenticated role receives only "user:read:public" (id,
 *   username, firstname, lastname, illustrationUrl) and the result set is
 *   restricted to users they are already related to via UserRelUser (friends,
 *   boss/HRM relationships, etc.) plus themselves.
 *
 * All registered API Platform query filters (search, order, pagination) still
 * apply on top of the visibility constraint.
 *
 * @template-implements ProviderInterface<User>
 */
final class UserCollectionStateProvider implements ProviderInterface
{
    private array $extensions;

    public function __construct(
        private readonly CollectionProvider $collectionProvider,
        private readonly UserRepository $userRepository,
        private readonly UserHelper $userHelper,
        private readonly Security $security,
        FilterExtension $filterExtension,
        OrderExtension $orderExtension,
        PaginationExtension $paginationExtension,
    ) {
        $this->extensions = [$filterExtension, $orderExtension, $paginationExtension];
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): array|object|null
    {
        if (!$operation instanceof GetCollection) {
            return $this->collectionProvider->provide($operation, $uriVariables, $context);
        }

        $currentUser = $this->userHelper->getCurrent();

        // Privileged roles: delegate entirely to the default provider with full groups.
        if (
            $this->security->isGranted('ROLE_ADMIN')
            || $this->security->isGranted('ROLE_SUPER_ADMIN')
            || $this->security->isGranted('ROLE_GLOBAL_ADMIN')
            || $this->security->isGranted('ROLE_TEACHER')
            || $this->security->isGranted('ROLE_SESSION_MANAGER')
        ) {
            return $this->collectionProvider->provide($operation, $uriVariables, $context);
        }

        // Unprivileged users: restrict visible rows.
        // Field restriction (email/phone/roles) is handled separately by
        // UserSerializerContextBuilder, which swaps the normalization group to
        // "user:read:public" before the serializer runs.

        // Build a visibility-scoped QueryBuilder and apply all API Platform
        //    extensions on top (so search filters, ordering and pagination work).
        if (!$currentUser instanceof User) {
            return [];
        }

        $qb = $this->userRepository->createQueryBuilder('u');

        $qb->andWhere(
            $qb->expr()->orX(
                // The user themselves.
                'u = :currentUser',
                // Relationships the current user initiated.
                $qb->expr()->exists(
                    'SELECT 1 FROM '.UserRelUser::class.' uru1
                     WHERE uru1.user = :currentUser
                       AND uru1.friend = u
                       AND uru1.relationType NOT IN (:deletedRel)'
                ),
                // Relationships where the current user is the target.
                $qb->expr()->exists(
                    'SELECT 1 FROM '.UserRelUser::class.' uru2
                     WHERE uru2.friend = :currentUser
                       AND uru2.user = u
                       AND uru2.relationType NOT IN (:deletedRel)'
                ),
            )
        )
            ->setParameter('currentUser', $currentUser->getId())
            ->setParameter('deletedRel', [UserRelUser::USER_RELATION_TYPE_DELETED])
        ;

        $queryNameGenerator = new QueryNameGenerator();
        $items = [];

        foreach ($this->extensions as $extension) {
            $extension->applyToCollection($qb, $queryNameGenerator, User::class, $operation, $context);

            if (
                $extension instanceof QueryResultCollectionExtensionInterface
                && $extension->supportsResult(User::class, $operation, $context)
            ) {
                $items = $extension->getResult($qb, User::class, $operation, $context);
            }
        }

        return [] !== $items ? $items : $qb->getQuery()->getResult();
    }
}
