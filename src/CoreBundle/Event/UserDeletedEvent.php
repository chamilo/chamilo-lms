<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Event;

use Chamilo\CoreBundle\Entity\User;

class UserDeletedEvent extends AbstractEvent
{
    public const DELETE_TYPE_SOFT = 'soft';
    public const DELETE_TYPE_HARD = 'hard';

    /**
     * Available in TYPE_PRE and in TYPE_POST for soft-delete. Null in TYPE_POST for hard-delete.
     */
    public function getUser(): ?User
    {
        return $this->data['user'] ?? null;
    }

    /**
     * Available only in TYPE_POST for hard-delete.
     */
    public function getUserId(): ?int
    {
        return $this->data['userId'] ?? null;
    }

    /**
     * Available only in TYPE_POST for hard-delete.
     */
    public function getEmail(): ?string
    {
        return $this->data['email'] ?? null;
    }

    /**
     * Available only in TYPE_POST for hard-delete.
     */
    public function getUsername(): ?string
    {
        return $this->data['username'] ?? null;
    }

    /**
     * Available only in TYPE_POST for hard-delete. Full legacy user info array captured before deletion.
     */
    public function getUserInfo(): array
    {
        return $this->data['userInfo'] ?? [];
    }

    /**
     * IDs of users whose creatorId was reassigned to the fallback user. Available only in TYPE_POST for hard-delete.
     */
    public function getAffectedCreatorIds(): array
    {
        return $this->data['affectedIds'] ?? [];
    }

    /**
     * ID of the fallback user that inherited resources. Available only in TYPE_POST for hard-delete.
     */
    public function getFallbackId(): ?int
    {
        return $this->data['fallbackId'] ?? null;
    }

    /**
     * ID of the user who triggered the deletion. Available only in TYPE_POST for hard-delete.
     */
    public function getActorId(): ?int
    {
        return $this->data['actorId'] ?? null;
    }

    public function getDeleteType(): string
    {
        return $this->data['deleteType'] ?? self::DELETE_TYPE_SOFT;
    }

    public function isSoftDelete(): bool
    {
        return self::DELETE_TYPE_SOFT === $this->getDeleteType();
    }

    public function isHardDelete(): bool
    {
        return self::DELETE_TYPE_HARD === $this->getDeleteType();
    }
}
