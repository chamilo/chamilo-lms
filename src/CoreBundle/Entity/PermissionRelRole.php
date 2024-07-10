<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Entity;

use Chamilo\CoreBundle\Repository\PermissionRelRoleRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: PermissionRelRoleRepository::class)]
#[ORM\Table(name: 'permission_rel_roles')]
class PermissionRelRole
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Permission::class)]
    #[ORM\JoinColumn(nullable: false)]
    private Permission $permission;

    #[Assert\NotBlank]
    #[ORM\Column(type: 'string', length: 50)]
    private string $roleCode;

    #[ORM\Column(type: 'boolean')]
    private bool $changeable;

    #[ORM\Column(type: 'datetime')]
    private \DateTime $updatedAt;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getPermission(): Permission
    {
        return $this->permission;
    }

    public function setPermission(Permission $permission): self
    {
        $this->permission = $permission;
        return $this;
    }

    public function getRoleCode(): string
    {
        return $this->roleCode;
    }

    public function setRoleCode(string $roleCode): self
    {
        $this->roleCode = $roleCode;
        return $this;
    }

    public function isChangeable(): bool
    {
        return $this->changeable;
    }

    public function setChangeable(bool $changeable): self
    {
        $this->changeable = $changeable;
        return $this;
    }

    public function getUpdatedAt(): \DateTime
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(\DateTime $updatedAt): self
    {
        $this->updatedAt = $updatedAt;
        return $this;
    }
}
