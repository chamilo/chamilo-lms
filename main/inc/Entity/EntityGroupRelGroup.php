<?php



use Doctrine\ORM\Mapping as ORM;

/**
 * EntityGroupRelGroup
 *
 * @Table(name="group_rel_group")
 * @Entity
 */
class EntityGroupRelGroup
{
    /**
     * @var integer
     *
     * @Column(name="id", type="integer", precision=0, scale=0, nullable=false, unique=false)
     * @Id
     * @GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @var integer
     *
     * @Column(name="group_id", type="integer", precision=0, scale=0, nullable=false, unique=false)
     */
    private $groupId;

    /**
     * @var integer
     *
     * @Column(name="subgroup_id", type="integer", precision=0, scale=0, nullable=false, unique=false)
     */
    private $subgroupId;

    /**
     * @var integer
     *
     * @Column(name="relation_type", type="integer", precision=0, scale=0, nullable=false, unique=false)
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
     * @return EntityGroupRelGroup
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
     * @return EntityGroupRelGroup
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
     * @return EntityGroupRelGroup
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
