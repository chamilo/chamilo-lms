<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CourseBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * CGroupCategoryRelUser
 * Relates users to group categories for autogroup functionality.
 */
#[ORM\Table(name: 'c_group_category_rel_user')]
#[ORM\Entity]
class CGroupCategoryRelUser
{
    #[ORM\Id]
    #[ORM\Column(type: 'integer')]
    protected ?int $id = null;

    #[ORM\Id]
    #[ORM\ManyToOne(targetEntity: CGroupCategory::class)]
    #[ORM\JoinColumn(name: 'group_category_id', referencedColumnName: 'iid', nullable: false, onDelete: 'CASCADE')]
    protected ?CGroupCategory $groupCategory = null;

    #[ORM\Column(type: 'smallint', length: 1)]
    protected int $populationType;

    #[ORM\Column(type: 'integer')]
    protected int $populationId;

    #[ORM\Column(type: 'smallint', length: 1)]
    protected int $statusInCategory;

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

    public function getPopulationType(): int
    {
        return $this->populationType;
    }

    public function setPopulationType(int $populationType): self
    {
        $this->populationType = $populationType;

        return $this;
    }

    public function getPopulationId(): int
    {
        return $this->populationId;
    }

    public function setPopulationId(int $populationId): self
    {
        $this->populationId = $populationId;

        return $this;
    }

    public function getStatusInCategory(): int
    {
        return $this->statusInCategory;
    }

    public function setStatusInCategory(int $statusInCategory): self
    {
        $this->statusInCategory = $statusInCategory;

        return $this;
    }
}
