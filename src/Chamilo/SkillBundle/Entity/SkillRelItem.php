<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\SkillBundle\Entity;

use Chamilo\CoreBundle\Entity\Skill;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * SkillRelItem.
 *
 * @ORM\Table(name="skill_rel_item")
 * ORM\Entity // uncomment if api_get_configuration_value('allow_skill_rel_items')
 */
class SkillRelItem
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
     * @ORM\ManyToOne(targetEntity="Chamilo\CoreBundle\Entity\Skill", inversedBy="items")
     * @ORM\JoinColumn(name="skill_id", referencedColumnName="id")
     */
    protected $skill;

    /**
     * See ITEM_TYPE_* constants in api.lib.php.
     *
     * @var int
     *
     * @ORM\Column(name="item_type", type="integer", nullable=false)
     */
    protected $itemType;

    /**
     * iid value.
     *
     * @var int
     *
     * @ORM\Column(name="item_id", type="integer", nullable=false)
     */
    protected $itemId;

    /**
     * A text expressing what has to be achieved
     * (view, finish, get more than X score, finishing all children skills, etc),.
     *
     * @var string
     *
     * @ORM\Column(name="obtain_conditions", type="string", length=255, nullable=true)
     */
    protected $obtainConditions;

    /**
     * if it requires validation by a teacher.
     *
     * @var bool
     *
     * @ORM\Column(name="requires_validation", type="boolean")
     */
    protected $requiresValidation;

    /**
     *  Set to false if this is a children skill used only to obtain a higher-level skill,
     * so a skill with is_real = false never appears in a student portfolio/backpack.
     *
     * @var bool
     *
     * @ORM\Column(name="is_real", type="boolean")
     */
    protected $isReal;

    /**
     * @var int
     *
     * @ORM\Column(name="c_id", type="integer", nullable=true)
     */
    protected $courseId;

    /**
     * @var int
     *
     * @ORM\Column(name="session_id", type="integer", nullable=true)
     */
    protected $sessionId;

    /**
     * @var \DateTime
     *
     * @Gedmo\Timestampable(on="create")
     * @ORM\Column(name="created_at", type="datetime")
     */
    protected $createdAt;

    /**
     * @var \DateTime
     *
     * @Gedmo\Timestampable(on="update")
     * @ORM\Column(name="updated_at", type="datetime")
     */
    protected $updatedAt;

    /**
     * @var int
     *
     * @ORM\Column(name="created_by", type="integer", nullable=false)
     */
    protected $createdBy;

    /**
     * @var int
     *
     * @ORM\Column(name="updated_by", type="integer", nullable=false)
     */
    protected $updatedBy;

    /**
     * SkillRelItem constructor.
     */
    public function __construct()
    {
        $this->createdAt = new \DateTime('now');
        $this->updatedAt = new \DateTime('now');
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

    /**
     * @param int $id
     *
     * @return SkillRelItem
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }

    /**
     * @return Skill
     */
    public function getSkill()
    {
        return $this->skill;
    }

    /**
     * @param mixed $skill
     *
     * @return SkillRelItem
     */
    public function setSkill($skill)
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
     * @param int $itemId
     *
     * @return SkillRelItem
     */
    public function setItemId($itemId)
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

    /**
     * @param string $obtainConditions
     *
     * @return SkillRelItem
     */
    public function setObtainConditions($obtainConditions)
    {
        $this->obtainConditions = $obtainConditions;

        return $this;
    }

    /**
     * @return bool
     */
    public function isRequiresValidation()
    {
        return $this->requiresValidation;
    }

    /**
     * @param bool $requiresValidation
     *
     * @return SkillRelItem
     */
    public function setRequiresValidation($requiresValidation)
    {
        $this->requiresValidation = $requiresValidation;

        return $this;
    }

    /**
     * @return bool
     */
    public function isReal()
    {
        return $this->isReal;
    }

    /**
     * @param bool $isReal
     *
     * @return SkillRelItem
     */
    public function setIsReal($isReal)
    {
        $this->isReal = $isReal;

        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    /**
     * @param \DateTime $createdAt
     *
     * @return SkillRelItem
     */
    public function setCreatedAt($createdAt)
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getUpdatedAt()
    {
        return $this->updatedAt;
    }

    /**
     * @param \DateTime $updatedAt
     *
     * @return SkillRelItem
     */
    public function setUpdatedAt($updatedAt)
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

    /**
     * @param int $createdBy
     *
     * @return SkillRelItem
     */
    public function setCreatedBy($createdBy)
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

    /**
     * @param int $updatedBy
     *
     * @return SkillRelItem
     */
    public function setUpdatedBy($updatedBy)
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

    /**
     * @param int $itemType
     *
     * @return SkillRelItem
     */
    public function setItemType($itemType)
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

    /**
     * @param int $courseId
     *
     * @return SkillRelItem
     */
    public function setCourseId($courseId)
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

    /**
     * @param int $sessionId
     *
     * @return SkillRelItem
     */
    public function setSessionId($sessionId)
    {
        $this->sessionId = $sessionId;

        return $this;
    }

    /**
     * @param string $cidReq
     *
     * @return string
     */
    public function getItemResultUrl($cidReq)
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

    /**
     * @param string $cidReq
     *
     * @return string
     */
    public function getItemResultList($cidReq)
    {
        $url = '';
        switch ($this->getItemType()) {
            case ITEM_TYPE_EXERCISE:
                $url = 'exercise/exercise_report.php?'.$cidReq.'&exerciseId='.$this->getItemId();
                break;
            case ITEM_TYPE_STUDENT_PUBLICATION:
                $url = 'work/work_list_all.php?'.$cidReq.'&id='.$this->getItemId();
                break;
        }

        return $url;
    }
}
