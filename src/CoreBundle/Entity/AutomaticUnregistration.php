<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Stores legacy automatic course unregistration history.
 */
#[ORM\Table(name: 'unregister_automatic')]
#[ORM\Index(name: 'idx_automatic_unregistration_user_course', columns: ['userId', 'cId'])]
#[ORM\Entity]
class AutomaticUnregistration
{
    #[ORM\Column(name: 'id', type: 'bigint')]
    #[ORM\Id]
    #[ORM\GeneratedValue]
    protected ?string $id = null;

    #[ORM\Column(name: 'userId', type: 'integer', nullable: false)]
    protected int $userId;

    #[ORM\Column(name: 'cId', type: 'integer', nullable: false)]
    protected int $courseId;

    #[ORM\Column(name: 'dateDeleted', type: 'string', length: 500, nullable: false)]
    protected string $dateDeleted;

    #[ORM\Column(name: 'lastaccess', type: 'string', length: 500, nullable: false)]
    protected string $lastAccess;

    public function getId(): ?string
    {
        return $this->id;
    }

    public function getUserId(): int
    {
        return $this->userId;
    }

    public function setUserId(int $userId): self
    {
        $this->userId = $userId;

        return $this;
    }

    public function getCourseId(): int
    {
        return $this->courseId;
    }

    public function setCourseId(int $courseId): self
    {
        $this->courseId = $courseId;

        return $this;
    }

    public function getDateDeleted(): string
    {
        return $this->dateDeleted;
    }

    public function setDateDeleted(string $dateDeleted): self
    {
        $this->dateDeleted = $dateDeleted;

        return $this;
    }

    public function getLastAccess(): string
    {
        return $this->lastAccess;
    }

    public function setLastAccess(string $lastAccess): self
    {
        $this->lastAccess = $lastAccess;

        return $this;
    }
}
