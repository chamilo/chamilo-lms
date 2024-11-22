<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Entity;

use DateTime;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;

#[ORM\Table(name: 'skill_rel_item')]
#[ORM\Entity]
class SkillRelItem
{
    use TimestampableEntity;

    #[ORM\Column(name: 'id', type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue]
    protected ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Skill::class, inversedBy: 'items')]
    #[ORM\JoinColumn(name: 'skill_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    protected Skill $skill;

    /**
     * See ITEM_TYPE_* constants in api.lib.php.
     */
    #[ORM\Column(name: 'item_type', type: 'integer', nullable: false)]
    protected int $itemType;

    /**
     * iid value.
     */
    #[ORM\Column(name: 'item_id', type: 'integer', nullable: false)]
    protected int $itemId;

    /**
     * A text expressing what has to be achieved
     * (view, finish, get more than X score, finishing all children skills, etc),.
     */
    #[ORM\Column(name: 'obtain_conditions', type: 'string', length: 255, nullable: true)]
    protected ?string $obtainConditions = null;

    /**
     * if it requires validation by a teacher.
     */
    #[ORM\Column(name: 'requires_validation', type: 'boolean')]
    protected bool $requiresValidation;

    /**
     *  Set to false if this is a children skill used only to obtain a higher-level skill,
     * so a skill with is_real = false never appears in a student portfolio/backpack.
     */
    #[ORM\Column(name: 'is_real', type: 'boolean')]
    protected bool $isReal;

    #[ORM\Column(name: 'c_id', type: 'integer', nullable: true)]
    protected ?int $courseId = null;

    #[ORM\Column(name: 'session_id', type: 'integer', nullable: true)]
    protected ?int $sessionId = null;

    #[ORM\Column(name: 'created_by', type: 'integer', nullable: false)]
    protected int $createdBy;

    #[ORM\Column(name: 'updated_by', type: 'integer', nullable: false)]
    protected int $updatedBy;

    public function __construct()
    {
        $this->createdAt = new DateTime('now');
        $this->updatedAt = new DateTime('now');
        $this->isReal = false;
        $this->requiresValidation = false;
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

    public function getItemId(): int
    {
        return $this->itemId;
    }

    public function setItemId(int $itemId): static
    {
        $this->itemId = $itemId;

        return $this;
    }

    public function getObtainConditions(): ?string
    {
        return $this->obtainConditions;
    }

    public function setObtainConditions(string $obtainConditions): self
    {
        $this->obtainConditions = $obtainConditions;

        return $this;
    }

    public function isRequiresValidation(): bool
    {
        return $this->requiresValidation;
    }

    public function setRequiresValidation(bool $requiresValidation): self
    {
        $this->requiresValidation = $requiresValidation;

        return $this;
    }

    public function isReal(): bool
    {
        return $this->isReal;
    }

    public function setIsReal(bool $isReal): self
    {
        $this->isReal = $isReal;

        return $this;
    }

    public function getCreatedAt(): DateTime
    {
        return $this->createdAt;
    }

    public function setCreatedAt(DateTime $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getUpdatedAt(): DateTime
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(DateTime $updatedAt): static
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    public function getCreatedBy(): int
    {
        return $this->createdBy;
    }

    public function setCreatedBy(int $createdBy): self
    {
        $this->createdBy = $createdBy;

        return $this;
    }

    public function getUpdatedBy(): int
    {
        return $this->updatedBy;
    }

    public function setUpdatedBy(int $updatedBy): self
    {
        $this->updatedBy = $updatedBy;

        return $this;
    }

    public function getItemType(): int
    {
        return $this->itemType;
    }

    public function setItemType(int $itemType): self
    {
        $this->itemType = $itemType;

        return $this;
    }

    public function getCourseId(): ?int
    {
        return $this->courseId;
    }

    public function setCourseId(int $courseId): self
    {
        $this->courseId = $courseId;

        return $this;
    }

    public function getSessionId(): ?int
    {
        return $this->sessionId;
    }

    public function setSessionId(int $sessionId): self
    {
        $this->sessionId = $sessionId;

        return $this;
    }

    public function getItemResultUrl(string $cidReq): string
    {
        $url = '';

        return match ($this->getItemType()) {
            ITEM_TYPE_EXERCISE => 'exercise/exercise_show.php?action=qualify&'.$cidReq,
            ITEM_TYPE_STUDENT_PUBLICATION => 'work/view.php?'.$cidReq,
            default => $url,
        };
    }

    public function getItemResultList(string $cidReq): string
    {
        $url = '';

        return match ($this->getItemType()) {
            ITEM_TYPE_EXERCISE => 'exercise/exercise_report.php?'.$cidReq.'&id='.$this->getItemId(),
            ITEM_TYPE_STUDENT_PUBLICATION => 'work/work_list_all.php?'.$cidReq.'&id='.$this->getItemId(),
            default => $url,
        };
    }
}
