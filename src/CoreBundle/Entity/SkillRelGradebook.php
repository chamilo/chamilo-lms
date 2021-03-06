<?php

declare(strict_types=1);

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
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue
     */
    protected int $id;

    /**
     * @ORM\Column(name="gradebook_id", type="integer", nullable=false)
     */
    protected int $gradebookId;

    /**
     * @ORM\Column(name="skill_id", type="integer", nullable=false)
     */
    protected int $skillId;

    /**
     * @ORM\Column(name="type", type="string", length=10, nullable=false)
     */
    protected string $type;

    /**
     * Set gradebookId.
     *
     * @return SkillRelGradebook
     */
    public function setGradebookId(int $gradebookId)
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
     * @return SkillRelGradebook
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
     * Set type.
     *
     * @return SkillRelGradebook
     */
    public function setType(string $type)
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
