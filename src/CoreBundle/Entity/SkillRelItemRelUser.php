<?php

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Entity;

use Chamilo\CoreBundle\Traits\UserTrait;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Gedmo\Timestampable\Traits\TimestampableEntity;

/**
 * SkillRelItemRelUser.
 *
 * @ORM\Table(name="skill_rel_item_rel_user")
 * @ORM\Entity
 */
class SkillRelItemRelUser
{
    use TimestampableEntity;
    use UserTrait;

    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue
     */
    protected $id;

    /**
     * @var SkillRelItem
     *
     * @ORM\ManyToOne(targetEntity="Chamilo\CoreBundle\Entity\SkillRelItem", cascade={"persist"})
     * @ORM\JoinColumn(name="skill_rel_item_id", referencedColumnName="id", nullable=false)
     */
    protected $skillRelItem;

    /**
     * @var User
     *
     * @ORM\ManyToOne(targetEntity="Chamilo\CoreBundle\Entity\User", cascade={"persist"})
     * @ORM\JoinColumn(name="user_id", referencedColumnName="id", nullable=false)
     */
    protected $user;

    /**
     * @var int
     *
     * @ORM\Column(name="result_id", type="integer", nullable=true)
     */
    protected $resultId;

    /**
     * @var int
     *
     * @Gedmo\Timestampable(on="create")
     * @ORM\Column(name="created_by", type="integer", nullable=false)
     */
    protected $createdBy;

    /**
     * @var int
     *
     * @Gedmo\Timestampable(on="update")
     * @ORM\Column(name="updated_by", type="integer", nullable=false)
     */
    protected $updatedBy;

    /**
     * SkillRelItemRelUser constructor.
     */
    public function __construct()
    {
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
     * @return SkillRelItemRelUser
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }

    /**
     * @return SkillRelItem
     */
    public function getSkillRelItem()
    {
        return $this->skillRelItem;
    }

    /**
     * @param SkillRelItem $skillRelItem
     *
     * @return SkillRelItemRelUser
     */
    public function setSkillRelItem($skillRelItem)
    {
        $this->skillRelItem = $skillRelItem;

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
     * @return SkillRelItemRelUser
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
     * @return SkillRelItemRelUser
     */
    public function setUpdatedBy($updatedBy)
    {
        $this->updatedBy = $updatedBy;

        return $this;
    }

    /**
     * @return int
     */
    public function getResultId()
    {
        return $this->resultId;
    }

    /**
     * @param int $resultId
     *
     * @return SkillRelItemRelUser
     */
    public function setResultId($resultId)
    {
        $this->resultId = $resultId;

        return $this;
    }

    /**
     * @param string $cidReq
     *
     * @return string
     */
    public function getUserItemResultUrl($cidReq)
    {
        $resultId = $this->getResultId();

        return $this->getSkillRelItem()->getItemResultUrl($cidReq).'&id='.$resultId;
    }
}
