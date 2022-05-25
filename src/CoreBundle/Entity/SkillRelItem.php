<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Entity;

use DateTime;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;

/**
 * @ORM\Table(name="skill_rel_item")
 * @ORM\Entity
 */
class SkillRelItem
{
    use TimestampableEntity;

    /**
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue
     */
    protected ?int $id = null;

    /**
     * @ORM\ManyToOne(targetEntity="Chamilo\CoreBundle\Entity\Skill", inversedBy="items")
     * @ORM\JoinColumn(name="skill_id", referencedColumnName="id", onDelete="CASCADE")
     */
    protected Skill $skill;

    /**
     * See ITEM_TYPE_* constants in api.lib.php.
     *
     * @ORM\Column(name="item_type", type="integer", nullable=false)
     */
    protected int $itemType;

    /**
     * iid value.
     *
     * @ORM\Column(name="item_id", type="integer", nullable=false)
     */
    protected int $itemId;

    /**
     * A text expressing what has to be achieved
     * (view, finish, get more than X score, finishing all children skills, etc),.
     *
     * @ORM\Column(name="obtain_conditions", type="string", length=255, nullable=true)
     */
    protected ?string $obtainConditions = null;

    /**
     * if it requires validation by a teacher.
     *
     * @ORM\Column(name="requires_validation", type="boolean")
     */
    protected bool $requiresValidation;

    /**
     *  Set to false if this is a children skill used only to obtain a higher-level skill,
     * so a skill with is_real = false never appears in a student portfolio/backpack.
     *
     * @ORM\Column(name="is_real", type="boolean")
     */
    protected bool $isReal;

    /**
     * @ORM\Column(name="c_id", type="integer", nullable=true)
     */
    protected ?int $courseId = null;

    /**
     * @ORM\Column(name="session_id", type="integer", nullable=true)
     */
    protected ?int $sessionId = null;

    /**
     * @ORM\Column(name="created_by", type="integer", nullable=false)
     */
    protected int $createdBy;

    /**
     * @ORM\Column(name="updated_by", type="integer", nullable=false)
     */
    protected int $updatedBy;

    public function __construct()
    {
        $this->createdAt = new DateTime('now');
        $this->updatedAt = new DateTime('now');
        $this->isReal = false;
        $this->requiresValidation = false;
    }

    /**
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

    /**
     * @return int
     */
    public function getItemId()
    {
        return $this->itemId;
    }

    /**
     * @return SkillRelItem
     */
    public function setItemId(int $itemId)
    {
        $this->itemId = $itemId;

        return $this;
    }

    /**
     * @return string
     */
    public function getObtainConditions()
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

    /**
     * @return DateTime
     */
    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    /**
     * @return SkillRelItem
     */
    public function setCreatedAt(DateTime $createdAt)
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    /**
     * @return DateTime
     */
    public function getUpdatedAt()
    {
        return $this->updatedAt;
    }

    /**
     * @return SkillRelItem
     */
    public function setUpdatedAt(DateTime $updatedAt)
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    /**
     * @return int
     */
    public function getCreatedBy()
    {
        return $this->createdBy;
    }

    public function setCreatedBy(int $createdBy): self
    {
        $this->createdBy = $createdBy;

        return $this;
    }

    /**
     * @return int
     */
    public function getUpdatedBy()
    {
        return $this->updatedBy;
    }

    public function setUpdatedBy(int $updatedBy): self
    {
        $this->updatedBy = $updatedBy;

        return $this;
    }

    /**
     * @return int
     */
    public function getItemType()
    {
        return $this->itemType;
    }

    public function setItemType(int $itemType): self
    {
        $this->itemType = $itemType;

        return $this;
    }

    /**
     * @return int
     */
    public function getCourseId()
    {
        return $this->courseId;
    }

    public function setCourseId(int $courseId): self
    {
        $this->courseId = $courseId;

        return $this;
    }

    /**
     * @return int
     */
    public function getSessionId()
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
        switch ($this->getItemType()) {
            case ITEM_TYPE_EXERCISE:
                $url = 'exercise/exercise_show.php?action=qualify&'.$cidReq;

                break;
            case ITEM_TYPE_STUDENT_PUBLICATION:
                $url = 'work/view.php?'.$cidReq;

                break;
        }

        return $url;
    }

    public function getItemResultList(string $cidReq): string
    {
        $url = '';
        switch ($this->getItemType()) {
            case ITEM_TYPE_EXERCISE:
                $url = 'exercise/exercise_report.php?'.$cidReq.'&id='.$this->getItemId();

                break;
            case ITEM_TYPE_STUDENT_PUBLICATION:
                $url = 'work/work_list_all.php?'.$cidReq.'&id='.$this->getItemId();

                break;
        }

        return $url;
    }
}
