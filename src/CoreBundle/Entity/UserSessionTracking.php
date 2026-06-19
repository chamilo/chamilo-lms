<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Stores legacy user session tracking snapshots.
 */
#[ORM\Table(name: 'tracking_user')]
#[ORM\Index(name: 'idx_user_session_tracking_user_active', columns: ['userId', 'isActive'])]
#[ORM\Entity]
class UserSessionTracking
{
    #[ORM\Column(name: 'trackingId', type: 'bigint')]
    #[ORM\Id]
    #[ORM\GeneratedValue]
    protected ?string $trackingId = null;

    #[ORM\Column(name: 'userId', type: 'bigint', nullable: false)]
    protected string $userId;

    #[ORM\Column(name: 'sessionTime', type: 'string', length: 200, nullable: false)]
    protected string $sessionTime;

    #[ORM\Column(name: 'isActive', type: 'integer', nullable: false, options: ['default' => 1])]
    protected int $isActive = 1;

    public function getTrackingId(): ?string
    {
        return $this->trackingId;
    }

    public function getUserId(): string
    {
        return $this->userId;
    }

    public function setUserId(string|int $userId): self
    {
        $this->userId = (string) $userId;

        return $this;
    }

    public function getSessionTime(): string
    {
        return $this->sessionTime;
    }

    public function setSessionTime(string $sessionTime): self
    {
        $this->sessionTime = $sessionTime;

        return $this;
    }

    public function getIsActive(): int
    {
        return $this->isActive;
    }

    public function setIsActive(int $isActive): self
    {
        $this->isActive = $isActive;

        return $this;
    }
}
