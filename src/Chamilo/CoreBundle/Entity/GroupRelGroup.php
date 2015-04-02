<?php

namespace Chamilo\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * GroupRelGroup
 *
 * @ORM\Table(name="group_rel_group", indexes={@ORM\Index(name="group_id", columns={"group_id"}), @ORM\Index(name="subgroup_id", columns={"subgroup_id"}), @ORM\Index(name="relation_type", columns={"relation_type"})})
 * @ORM\Entity
 */
class GroupRelGroup
{
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
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;



    /**
     * Set groupId
     *
     * @param integer $groupId
     * @return GroupRelGroup
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
     * @return GroupRelGroup
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
     * @return GroupRelGroup
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
