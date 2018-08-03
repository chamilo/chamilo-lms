<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * GroupRelGroup.
 *
 * @ORM\Table(name="usergroup_rel_usergroup")
 * @ORM\Entity
 */
class UserGroupRelUserGroup
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
     * @var int
     *
     * @ORM\Column(name="group_id", type="integer", nullable=false)
     */
    protected $groupId;

    /**
     * @var int
     *
     * @ORM\Column(name="subgroup_id", type="integer", nullable=false)
     */
    protected $subgroupId;

    /**
     * @var int
     *
     * @ORM\Column(name="relation_type", type="integer", nullable=false)
     */
    protected $relationType;

    /**
     * Set groupId.
     *
     * @param int $groupId
     *
     * @return $this
     */
    public function setGroupId($groupId)
    {
        $this->groupId = $groupId;

        return $this;
    }

    /**
     * Get groupId.
     *
     * @return int
     */
    public function getGroupId()
    {
        return $this->groupId;
    }

    /**
     * Set subgroupId.
     *
     * @param int $subgroupId
     *
     * @return $this
     */
    public function setSubgroupId($subgroupId)
    {
        $this->subgroupId = $subgroupId;

        return $this;
    }

    /**
     * Get subgroupId.
     *
     * @return int
     */
    public function getSubgroupId()
    {
        return $this->subgroupId;
    }

    /**
     * Set relationType.
     *
     * @param int $relationType
     *
     * @return $this
     */
    public function setRelationType($relationType)
    {
        $this->relationType = $relationType;

        return $this;
    }

    /**
     * Get relationType.
     *
     * @return int
     */
    public function getRelationType()
    {
        return $this->relationType;
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
