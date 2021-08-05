<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
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
     * @ORM\ManyToOne(targetEntity="Chamilo\CoreBundle\Entity\Skill", inversedBy="gradeBookCategories")
     * @ORM\JoinColumn(name="skill_id", referencedColumnName="id", onDelete="CASCADE")
     */
    protected Skill $skill;

    /**
     * @ORM\ManyToOne(targetEntity="Chamilo\CoreBundle\Entity\GradebookCategory", inversedBy="skills")
     * @ORM\JoinColumn(name="gradebook_id", referencedColumnName="id", onDelete="CASCADE")
     */
    protected GradebookCategory $gradeBookCategory;

    /**
     * @ORM\Column(name="type", type="string", length=10, nullable=false)
     */
    protected string $type;

    public function __construct()
    {
        $this->type = '';
    }

    public function setType(string $type): self
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

    public function getSkill(): Skill
    {
        return $this->skill;
    }

    public function setSkill(Skill $skill): self
    {
        $this->skill = $skill;

        return $this;
    }

    public function getGradeBookCategory(): GradebookCategory
    {
        return $this->gradeBookCategory;
    }

    public function setGradeBookCategory(GradebookCategory $gradeBookCategory): self
    {
        $this->gradeBookCategory = $gradeBookCategory;

        return $this;
    }
}
