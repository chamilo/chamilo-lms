<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CourseBundle\Entity;

use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Traits\UserTrait;
use Doctrine\ORM\Mapping as ORM;

/**
 * Represents the invitation of a user to join a group in a specific category.
 */
#[ORM\Table(name: 'c_autogroup_user_invitation')]
#[ORM\Entity]
class CAutogroupUserInvitation
{
    use UserTrait;

    #[ORM\Id]
    #[ORM\Column(type: 'integer')]
    #[ORM\GeneratedValue]
    protected ?int $id = null;

    #[ORM\ManyToOne(targetEntity: CGroupCategory::class)]
    #[ORM\JoinColumn(name: 'group_category_id', referencedColumnName: 'iid', nullable: false, onDelete: 'CASCADE')]
    protected ?CGroupCategory $groupCategory = null;

    #[ORM\ManyToOne(targetEntity: CGroup::class)]
    #[ORM\JoinColumn(name: 'group_id', referencedColumnName: 'iid', nullable: false, onDelete: 'CASCADE')]
    protected ?CGroup $group = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    protected ?User $user = null;

    #[ORM\Column(type: 'boolean', nullable: true)]
    protected ?bool $confirm = null;

    public function __construct() {}

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(int $id): self
    {
        $this->id = $id;

        return $this;
    }

    public function getGroupCategory(): ?CGroupCategory
    {
        return $this->groupCategory;
    }

    public function setGroupCategory(?CGroupCategory $groupCategory): self
    {
        $this->groupCategory = $groupCategory;

        return $this;
    }

    public function getGroup(): ?CGroup
    {
        return $this->group;
    }

    public function setGroup(?CGroup $group): self
    {
        $this->group = $group;

        return $this;
    }

    public function getConfirm(): ?bool
    {
        return $this->confirm;
    }

    public function setConfirm(?bool $confirm): self
    {
        $this->confirm = $confirm;

        return $this;
    }
}
