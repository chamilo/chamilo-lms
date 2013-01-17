<?php



use Doctrine\ORM\Mapping as ORM;

/**
 * EntityUserRelEventType
 *
 * @Table(name="user_rel_event_type")
 * @Entity
 */
class EntityUserRelEventType
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
     * @Column(name="user_id", type="integer", precision=0, scale=0, nullable=false, unique=false)
     */
    private $userId;

    /**
     * @var string
     *
     * @Column(name="event_type_name", type="string", length=255, precision=0, scale=0, nullable=false, unique=false)
     */
    private $eventTypeName;


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
     * Set userId
     *
     * @param integer $userId
     * @return EntityUserRelEventType
     */
    public function setUserId($userId)
    {
        $this->userId = $userId;

        return $this;
    }

    /**
     * Get userId
     *
     * @return integer 
     */
    public function getUserId()
    {
        return $this->userId;
    }

    /**
     * Set eventTypeName
     *
     * @param string $eventTypeName
     * @return EntityUserRelEventType
     */
    public function setEventTypeName($eventTypeName)
    {
        $this->eventTypeName = $eventTypeName;

        return $this;
    }

    /**
     * Get eventTypeName
     *
     * @return string 
     */
    public function getEventTypeName()
    {
        return $this->eventTypeName;
    }
}
