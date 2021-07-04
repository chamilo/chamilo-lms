<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table(name="skill_rel_skill")
 * @ORM\Entity
 */
class SkillRelSkill
{
    /**
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue
     */
    protected int $id;

    /**
     * @ORM\ManyToOne(targetEntity="Chamilo\CoreBundle\Entity\Skill", inversedBy="skills")
     * @ORM\JoinColumn(name="skill_id", referencedColumnName="id")
     */
    protected Skill $skill;

    /**
     * @ORM\ManyToOne(targetEntity="Skill")
     * @ORM\JoinColumn(name="parent_id", referencedColumnName="id", onDelete="SET NULL")
     */
    protected ?Skill $parent = null;

    /**
     * @ORM\Column(name="relation_type", type="integer", nullable=false)
     */
    protected int $relationType;

    /**
     * @ORM\Column(name="level", type="integer", nullable=false)
     */
    protected int $level;

    public function getParent(): ?Skill
    {
        return $this->parent;
    }

    public function setParent(?Skill $parent): self
    {
        $this->parent = $parent;

        return $this;
    }

    public function setRelationType(int $relationType): self
    {
        $this->relationType = $relationType;

        return $this;
    }

    /**
     * Get relationType.
     *
     * @return int
     */
    public function getRelationType()
    {
        return $this->relationType;
    }

    public function setLevel(int $level): self
    {
        $this->level = $level;

        return $this;
    }

    public function getLevel(): int
    {
        return $this->level;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getSkill(): Skill
    {
        return $this->skill;
    }

    public function setSkill(Skill $skill): self
    {
        $this->skill = $skill;

        return $this;
    }
}
