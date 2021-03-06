<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * SkillRelSkill.
 *
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
     * @ORM\Column(name="skill_id", type="integer", nullable=false)
     */
    protected int $skillId;

    /**
     * @ORM\Column(name="parent_id", type="integer", nullable=false)
     */
    protected int $parentId;

    /**
     * @ORM\Column(name="relation_type", type="integer", nullable=false)
     */
    protected int $relationType;

    /**
     * @ORM\Column(name="level", type="integer", nullable=false)
     */
    protected int $level;

    /**
     * Set skillId.
     *
     * @return SkillRelSkill
     */
    public function setSkillId(int $skillId)
    {
        $this->skillId = $skillId;

        return $this;
    }

    /**
     * Get skillId.
     *
     * @return int
     */
    public function getSkillId()
    {
        return $this->skillId;
    }

    /**
     * Set parentId.
     *
     * @return SkillRelSkill
     */
    public function setParentId(int $parentId)
    {
        $this->parentId = $parentId;

        return $this;
    }

    /**
     * Get parentId.
     *
     * @return int
     */
    public function getParentId()
    {
        return $this->parentId;
    }

    /**
     * Set relationType.
     *
     * @return SkillRelSkill
     */
    public function setRelationType(int $relationType)
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

    /**
     * Set level.
     *
     * @return SkillRelSkill
     */
    public function setLevel(int $level)
    {
        $this->level = $level;

        return $this;
    }

    /**
     * Get level.
     *
     * @return int
     */
    public function getLevel()
    {
        return $this->level;
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
}
