<?php

namespace Chamilo\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * UsergroupRelUsergroup
 *
 * @ORM\Table(name="usergroup_rel_usergroup", indexes={@ORM\Index(name="usergroup_rel_usergroup_group_id", columns={"group_id"}), @ORM\Index(name="usergroup_rel_usergroup_subgroup_id", columns={"subgroup_id"}), @ORM\Index(name="usergroup_rel_usergroup_relation_type", columns={"relation_type"})})
 * @ORM\Entity
 */
class UsergroupRelUsergroup
{
    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer", precision=0, scale=0, nullable=false, unique=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var integer
     *
     * @ORM\Column(name="group_id", type="integer", precision=0, scale=0, nullable=false, unique=false)
     */
    private $groupId;

    /**
     * @var integer
     *
     * @ORM\Column(name="subgroup_id", type="integer", precision=0, scale=0, nullable=false, unique=false)
     */
    private $subgroupId;

    /**
     * @var integer
     *
     * @ORM\Column(name="relation_type", type="integer", precision=0, scale=0, nullable=false, unique=false)
     */
    private $relationType;


    /**
     * Get id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set groupId
     *
     * @param integer $groupId
     * @return UsergroupRelUsergroup
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
     * @return UsergroupRelUsergroup
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
     * @return UsergroupRelUsergroup
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
}
