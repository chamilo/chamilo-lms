<?php



use Doctrine\ORM\Mapping as ORM;

/**
 * EntityGroupRelTag
 *
 * @Table(name="group_rel_tag")
 * @Entity
 */
class EntityGroupRelTag
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
     * @Column(name="tag_id", type="integer", precision=0, scale=0, nullable=false, unique=false)
     */
    private $tagId;

    /**
     * @var integer
     *
     * @Column(name="group_id", type="integer", precision=0, scale=0, nullable=false, unique=false)
     */
    private $groupId;


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
     * Set tagId
     *
     * @param integer $tagId
     * @return EntityGroupRelTag
     */
    public function setTagId($tagId)
    {
        $this->tagId = $tagId;

        return $this;
    }

    /**
     * Get tagId
     *
     * @return integer 
     */
    public function getTagId()
    {
        return $this->tagId;
    }

    /**
     * Set groupId
     *
     * @param integer $groupId
     * @return EntityGroupRelTag
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
}
