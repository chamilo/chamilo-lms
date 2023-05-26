<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Stringable;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * User platform roles.
 */
#[ORM\Table(name: 'fos_group')]
#[ORM\Entity]
class Group implements Stringable
{
    #[ORM\Column(name: 'id', type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    protected ?int $id = null;

    #[Assert\NotBlank]
    #[ORM\Column(name: 'code', type: 'string', length: 40, nullable: false, unique: true)]
    protected string $code;

    /**
     * @var User[]|Collection
     */
    #[ORM\ManyToMany(targetEntity: \Chamilo\CoreBundle\Entity\User::class, mappedBy: 'groups')]
    protected Collection $users;

    public function __construct(
        #[Assert\NotBlank]
        #[ORM\Column(name: 'name', type: 'string', length: 255, unique: true, nullable: false)]
        protected string $name,
        #[ORM\Column(name: 'roles', type: 'array')]
        protected array $roles = [
        ]
    ) {
        $this->users = new ArrayCollection();
    }

    public function __toString(): string
    {
        return $this->getName() ?: '';
    }

    public function addRole(string $role): self
    {
        if (!$this->hasRole($role)) {
            $this->roles[] = strtoupper($role);
        }

        return $this;
    }

    public function hasRole(string $role): bool
    {
        return \in_array(strtoupper($role), $this->roles, true);
    }

    public function getRoles(): array
    {
        return $this->roles;
    }

    public function removeRole(string $role): self
    {
        if (false !== $key = array_search(strtoupper($role), $this->roles, true)) {
            unset($this->roles[$key]);
            $this->roles = array_values($this->roles);
        }

        return $this;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function setRoles(array $roles): self
    {
        $this->roles = $roles;

        return $this;
    }

    /**
     * Get id.
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return User[]|Collection
     */
    public function getUsers(): array|Collection
    {
        return $this->users;
    }

    public function getCode(): string
    {
        return $this->code;
    }

    public function setCode(string $code): self
    {
        $this->code = $code;

        return $this;
    }
}
