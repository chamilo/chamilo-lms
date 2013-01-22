<?php



use Doctrine\ORM\Mapping as ORM;

/**
 * EntityAnnouncementRelGroup
 *
 * @Table(name="announcement_rel_group")
 * @Entity
 */
class EntityAnnouncementRelGroup
{
    /**
     * @var integer
     *
     * @Column(name="group_id", type="integer", precision=0, scale=0, nullable=false, unique=false)
     * @Id
     * @GeneratedValue(strategy="NONE")
     */
    private $groupId;

    /**
     * @var integer
     *
     * @Column(name="announcement_id", type="integer", precision=0, scale=0, nullable=false, unique=false)
     * @Id
     * @GeneratedValue(strategy="NONE")
     */
    private $announcementId;


    /**
     * Set groupId
     *
     * @param integer $groupId
     * @return EntityAnnouncementRelGroup
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
     * Set announcementId
     *
     * @param integer $announcementId
     * @return EntityAnnouncementRelGroup
     */
    public function setAnnouncementId($announcementId)
    {
        $this->announcementId = $announcementId;

        return $this;
    }

    /**
     * Get announcementId
     *
     * @return integer 
     */
    public function getAnnouncementId()
    {
        return $this->announcementId;
    }
}
