<?php

declare(strict_types=1);

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
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue
     */
    protected int $id;

    /**
     * @ORM\ManyToOne(targetEntity="Chamilo\CoreBundle\Entity\SkillRelItem", cascade={"persist"})
     * @ORM\JoinColumn(name="skill_rel_item_id", referencedColumnName="id", nullable=false)
     */
    protected SkillRelItem $skillRelItem;

    /**
     * @ORM\ManyToOne(targetEntity="Chamilo\CoreBundle\Entity\User", cascade={"persist"})
     * @ORM\JoinColumn(name="user_id", referencedColumnName="id", nullable=false)
     */
    protected User $user;

    /**
     * @ORM\Column(name="result_id", type="integer", nullable=true)
     */
    protected int $resultId;

    /**
     * @Gedmo\Timestampable(on="create")
     * @ORM\Column(name="created_by", type="integer", nullable=false)
     */
    protected int $createdBy;

    /**
     * @Gedmo\Timestampable(on="update")
     * @ORM\Column(name="updated_by", type="integer", nullable=false)
     */
    protected int $updatedBy;

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
     * @return SkillRelItem
     */
    public function getSkillRelItem()
    {
        return $this->skillRelItem;
    }

    /**
     * @return SkillRelItemRelUser
     */
    public function setSkillRelItem(SkillRelItem $skillRelItem)
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
     * @return SkillRelItemRelUser
     */
    public function setCreatedBy(int $createdBy)
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
     * @return SkillRelItemRelUser
     */
    public function setUpdatedBy(int $updatedBy)
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
     * @return SkillRelItemRelUser
     */
    public function setResultId(int $resultId)
    {
        $this->resultId = $resultId;

        return $this;
    }

    /**
     * @return string
     */
    public function getUserItemResultUrl(string $cidReq)
    {
        $resultId = $this->getResultId();

        return $this->getSkillRelItem()->getItemResultUrl($cidReq).'&id='.$resultId;
    }
}
