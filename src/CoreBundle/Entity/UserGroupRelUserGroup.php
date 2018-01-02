<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * GroupRelGroup
 *
 * @ORM\Table(name="usergroup_rel_usergroup")
 * @ORM\Entity
 */
class UserGroupRelUserGroup
{
    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue
     */
    private $id;

    /**
     * @var integer
     *
     * @ORM\Column(name="group_id", type="integer", nullable=false)
     */
    private $groupId;

    /**
     * @var integer
     *
     * @ORM\Column(name="subgroup_id", type="integer", nullable=false)
     */
    private $subgroupId;

    /**
     * @var integer
     *
     * @ORM\Column(name="relation_type", type="integer", nullable=false)
     */
    private $relationType;

    /**
     * Set groupId
     *
     * @param integer $groupId
     *
     * @return $this
     */
    public function setGroupId($groupId)
    {
        $this->groupId = $groupId;

        return $this;
    }

    /**
     * Get groupId
     *
     * @return integer
     */
    public function getGroupId()
    {
        return $this->groupId;
    }

    /**
     * Set subgroupId
     *
     * @param integer $subgroupId
     * @return $this
     */
    public function setSubgroupId($subgroupId)
    {
        $this->subgroupId = $subgroupId;

        return $this;
    }

    /**
     * Get subgroupId
     *
     * @return integer
     */
    public function getSubgroupId()
    {
        return $this->subgroupId;
    }

    /**
     * Set relationType
     *
     * @param integer $relationType
     *
     * @return $this
     */
    public function setRelationType($relationType)
    {
        $this->relationType = $relationType;

        return $this;
    }

    /**
     * Get relationType
     *
     * @return integer
     */
    public function getRelationType()
    {
        return $this->relationType;
    }

    /**
     * Get id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }
}
