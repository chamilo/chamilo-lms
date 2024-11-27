<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Table(name: 'skill_rel_gradebook')]
#[ORM\Entity]
class SkillRelGradebook
{
    #[ORM\Column(name: 'id', type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue]
    protected ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Skill::class, inversedBy: 'gradeBookCategories')]
    #[ORM\JoinColumn(name: 'skill_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    protected Skill $skill;

    #[ORM\ManyToOne(targetEntity: GradebookCategory::class, inversedBy: 'skills')]
    #[ORM\JoinColumn(name: 'gradebook_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    protected GradebookCategory $gradeBookCategory;

    #[ORM\Column(name: 'type', type: 'string', length: 10, nullable: false)]
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

    public function getType(): string
    {
        return $this->type;
    }

    public function getId(): ?int
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
