<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\SkillBundle\Entity;

use Chamilo\UserBundle\Entity\User;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * SkillRelItemRelUser.
 *
 * @ORM\Table(name="skill_rel_item_rel_user")
 * ORM\Entity // uncomment if api_get_configuration_value('allow_skill_rel_items')
 */
class SkillRelItemRelUser
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
     * @var SkillRelItem
     * @ORM\ManyToOne(targetEntity="Chamilo\SkillBundle\Entity\SkillRelItem", cascade={"persist"})
     * @ORM\JoinColumn(name="skill_rel_item_id", referencedColumnName="id", nullable=false)
     */
    protected $skillRelItem;

    /**
     * @var User
     * @ORM\ManyToOne(targetEntity="Chamilo\UserBundle\Entity\User", cascade={"persist"})
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
     * SkillRelItemRelUser constructor.
     */
    public function __construct()
    {
        $this->createdAt = new \DateTime('now');
        $this->updatedAt = new \DateTime('now');
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
     * @return User
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * @param User $user
     *
     * @return SkillRelItemRelUser
     */
    public function setUser($user)
    {
        $this->user = $user;

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
        $url = $this->getSkillRelItem()->getItemResultUrl($cidReq).'&id='.$resultId;

        return $url;
    }
}
