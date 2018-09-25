<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * SkillRelGradebook.
 *
 * @ORM\Table(name="skill_rel_gradebook")
 * @ORM\Entity
 */
class SkillRelGradebook
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
     * @ORM\Column(name="gradebook_id", type="integer", nullable=false)
     */
    protected $gradebookId;

    /**
     * @var int
     *
     * @ORM\Column(name="skill_id", type="integer", nullable=false)
     */
    protected $skillId;

    /**
     * @var string
     *
     * @ORM\Column(name="type", type="string", length=10, nullable=false)
     */
    protected $type;

    /**
     * Set gradebookId.
     *
     * @param int $gradebookId
     *
     * @return SkillRelGradebook
     */
    public function setGradebookId($gradebookId)
    {
        $this->gradebookId = $gradebookId;

        return $this;
    }

    /**
     * Get gradebookId.
     *
     * @return int
     */
    public function getGradebookId()
    {
        return $this->gradebookId;
    }

    /**
     * Set skillId.
     *
     * @param int $skillId
     *
     * @return SkillRelGradebook
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
     * Set type.
     *
     * @param string $type
     *
     * @return SkillRelGradebook
     */
    public function setType($type)
    {
        $this->type = $type;

        return $this;
    }

    /**
     * Get type.
     *
     * @return string
     */
    public function getType()
    {
        return $this->type;
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
