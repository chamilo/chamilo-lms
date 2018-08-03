<?php
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
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue
     */
    protected $id;

    /**
     * @var int
     *
     * @ORM\Column(name="skill_id", type="integer", nullable=false)
     */
    protected $skillId;

    /**
     * @var int
     *
     * @ORM\Column(name="parent_id", type="integer", nullable=false)
     */
    protected $parentId;

    /**
     * @var int
     *
     * @ORM\Column(name="relation_type", type="integer", nullable=false)
     */
    protected $relationType;

    /**
     * @var int
     *
     * @ORM\Column(name="level", type="integer", nullable=false)
     */
    protected $level;

    /**
     * Set skillId.
     *
     * @param int $skillId
     *
     * @return SkillRelSkill
     */
    public function setSkillId($skillId)
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
     * @param int $parentId
     *
     * @return SkillRelSkill
     */
    public function setParentId($parentId)
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
     * @param int $relationType
     *
     * @return SkillRelSkill
     */
    public function setRelationType($relationType)
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
     * @param int $level
     *
     * @return SkillRelSkill
     */
    public function setLevel($level)
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
