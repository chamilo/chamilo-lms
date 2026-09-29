<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Helpers;

use Chamilo\CoreBundle\Entity\User;
use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Types\Types;

/**
 * Resolves which access URLs a ROLE_GLOBAL_ADMIN may manage, based on which URL(s) they are
 * registered in and the URL tree's parent/child structure.
 *
 * An admin registered in a URL that has no parent (the topmost URL of its tree) is
 * "unrestricted" and manages every URL, exactly like today. An admin registered only in a
 * non-root URL manages that URL and its descendants only. See getManagedUrlIds() for why the
 * unrestricted case is represented as null rather than an id list.
 */
final class AccessUrlScopeHelper
{
    /**
     * @var array<int, bool>
     */
    private array $unrestrictedCache = [];

    /**
     * @var array<int, int[]|null>
     */
    private array $managedUrlIdsCache = [];

    public function __construct(
        private readonly Connection $connection,
    ) {}

    public function isUnrestricted(User $user): bool
    {
        $userId = (int) $user->getId();
        if (!isset($this->unrestrictedCache[$userId])) {
            // fetchOne() returns false (not null) when no row matches.
            $this->unrestrictedCache[$userId] = false !== $this->connection->fetchOne(
                'SELECT 1
                   FROM access_url_rel_user r
                   INNER JOIN access_url a ON a.id = r.access_url_id
                  WHERE r.user_id = :userId
                    AND a.parent_id IS NULL
                  LIMIT 1',
                ['userId' => $userId],
                ['userId' => Types::INTEGER],
            );
        }

        return $this->unrestrictedCache[$userId];
    }

    /**
     * @return int[]|null null means unrestricted (no filter should be applied); a list means
     *                    exactly these access URL ids are managed
     */
    public function getManagedUrlIds(User $user): ?array
    {
        $userId = (int) $user->getId();
        if (\array_key_exists($userId, $this->managedUrlIdsCache)) {
            return $this->managedUrlIdsCache[$userId];
        }

        if ($this->isUnrestricted($user)) {
            return $this->managedUrlIdsCache[$userId] = null;
        }

        $ids = array_map(
            'intval',
            $this->connection->fetchFirstColumn(
                'SELECT DISTINCT descendant.id
                   FROM access_url_rel_user r
                   INNER JOIN access_url ancestor
                       ON ancestor.id = r.access_url_id
                   INNER JOIN access_url descendant
                       ON descendant.tree_root = ancestor.tree_root
                      AND descendant.lft >= ancestor.lft
                      AND descendant.rgt <= ancestor.rgt
                  WHERE r.user_id = :userId',
                ['userId' => $userId],
                ['userId' => Types::INTEGER],
            ),
        );

        return $this->managedUrlIdsCache[$userId] = $ids;
    }

    public function isUrlManaged(User $user, int $urlId): bool
    {
        $managedUrlIds = $this->getManagedUrlIds($user);

        return null === $managedUrlIds || \in_array($urlId, $managedUrlIds, true);
    }

    public function isUserManaged(User $user, int $targetUserId): bool
    {
        $managedUrlIds = $this->getManagedUrlIds($user);
        if (null === $managedUrlIds) {
            return true;
        }

        // fetchOne() returns false (not null) when no row matches.
        return false !== $this->connection->fetchOne(
            'SELECT 1
               FROM access_url_rel_user
              WHERE user_id = :targetUserId
                AND access_url_id IN (:managedUrlIds)
              LIMIT 1',
            ['targetUserId' => $targetUserId, 'managedUrlIds' => $managedUrlIds],
            ['targetUserId' => Types::INTEGER, 'managedUrlIds' => ArrayParameterType::INTEGER],
        );
    }

    public function isCourseManaged(User $user, int $courseId): bool
    {
        $managedUrlIds = $this->getManagedUrlIds($user);
        if (null === $managedUrlIds) {
            return true;
        }

        // fetchOne() returns false (not null) when no row matches.
        return false !== $this->connection->fetchOne(
            'SELECT 1
               FROM access_url_rel_course
              WHERE c_id = :courseId
                AND access_url_id IN (:managedUrlIds)
              LIMIT 1',
            ['courseId' => $courseId, 'managedUrlIds' => $managedUrlIds],
            ['courseId' => Types::INTEGER, 'managedUrlIds' => ArrayParameterType::INTEGER],
        );
    }

    /**
     * A ROLE_GLOBAL_ADMIN may only grant ROLE_GLOBAL_ADMIN to someone else (or to themselves)
     * if they are themselves unrestricted (registered in the topmost URL of a tree).
     */
    public function canGrantGlobalAdminRole(User $actor): bool
    {
        return $actor->hasRole('ROLE_GLOBAL_ADMIN') && $this->isUnrestricted($actor);
    }

    /**
     * An actor may always act on their own account. Acting on another user -- editing OR
     * deleting them, see UserVoter::EDIT / UserVoter::DELETE -- is otherwise confined by
     * the actor's URL scope:
     *
     * - unrestricted (registered at the topmost URL of a tree): may act on anyone, unchanged.
     * - ROLE_GLOBAL_ADMIN scoped to a subtree: may act on any user registered on that URL or
     *   any of its descendants (same rule as isUserManaged()).
     * - any other admin: confined to exactly the URL(s) they are directly registered to --
     *   no descendant expansion, even if one of those URLs itself has children. A plain
     *   admin managing one portal must not be able to reach users of a child portal just
     *   because the URLs happen to share a tree.
     */
    public function canEditUser(User $actor, User $target): bool
    {
        if ((int) $actor->getId() === (int) $target->getId()) {
            return true;
        }

        if ($this->isUnrestricted($actor)) {
            return true;
        }

        if ($actor->hasRole('ROLE_GLOBAL_ADMIN')) {
            return $this->isUserManaged($actor, (int) $target->getId());
        }

        return false !== $this->connection->fetchOne(
            'SELECT 1
               FROM access_url_rel_user actor_reg
               INNER JOIN access_url_rel_user target_reg
                   ON target_reg.access_url_id = actor_reg.access_url_id
              WHERE actor_reg.user_id = :actorId
                AND target_reg.user_id = :targetId
              LIMIT 1',
            ['actorId' => (int) $actor->getId(), 'targetId' => (int) $target->getId()],
            ['actorId' => Types::INTEGER, 'targetId' => Types::INTEGER],
        );
    }

    /**
     * @return int[] every access URL id in the subtree rooted at $urlId (including $urlId itself)
     */
    public function getDescendantUrlIds(int $urlId): array
    {
        return array_map(
            'intval',
            $this->connection->fetchFirstColumn(
                'SELECT descendant.id
                   FROM access_url ancestor
                   INNER JOIN access_url descendant
                       ON descendant.tree_root = ancestor.tree_root
                      AND descendant.lft >= ancestor.lft
                      AND descendant.rgt <= ancestor.rgt
                  WHERE ancestor.id = :urlId',
                ['urlId' => $urlId],
                ['urlId' => Types::INTEGER],
            ),
        );
    }
}
