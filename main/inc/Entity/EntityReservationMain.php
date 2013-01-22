<?php



use Doctrine\ORM\Mapping as ORM;

/**
 * EntityReservationMain
 *
 * @Table(name="reservation_main")
 * @Entity
 */
class EntityReservationMain
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
     * @Column(name="subid", type="integer", precision=0, scale=0, nullable=false, unique=false)
     */
    private $subid;

    /**
     * @var integer
     *
     * @Column(name="item_id", type="integer", precision=0, scale=0, nullable=false, unique=false)
     */
    private $itemId;

    /**
     * @var boolean
     *
     * @Column(name="auto_accept", type="boolean", precision=0, scale=0, nullable=false, unique=false)
     */
    private $autoAccept;

    /**
     * @var integer
     *
     * @Column(name="max_users", type="integer", precision=0, scale=0, nullable=false, unique=false)
     */
    private $maxUsers;

    /**
     * @var \DateTime
     *
     * @Column(name="start_at", type="datetime", precision=0, scale=0, nullable=false, unique=false)
     */
    private $startAt;

    /**
     * @var \DateTime
     *
     * @Column(name="end_at", type="datetime", precision=0, scale=0, nullable=false, unique=false)
     */
    private $endAt;

    /**
     * @var \DateTime
     *
     * @Column(name="subscribe_from", type="datetime", precision=0, scale=0, nullable=false, unique=false)
     */
    private $subscribeFrom;

    /**
     * @var \DateTime
     *
     * @Column(name="subscribe_until", type="datetime", precision=0, scale=0, nullable=false, unique=false)
     */
    private $subscribeUntil;

    /**
     * @var integer
     *
     * @Column(name="subscribers", type="integer", precision=0, scale=0, nullable=false, unique=false)
     */
    private $subscribers;

    /**
     * @var string
     *
     * @Column(name="notes", type="text", precision=0, scale=0, nullable=false, unique=false)
     */
    private $notes;

    /**
     * @var boolean
     *
     * @Column(name="timepicker", type="boolean", precision=0, scale=0, nullable=false, unique=false)
     */
    private $timepicker;

    /**
     * @var integer
     *
     * @Column(name="timepicker_min", type="integer", precision=0, scale=0, nullable=false, unique=false)
     */
    private $timepickerMin;

    /**
     * @var integer
     *
     * @Column(name="timepicker_max", type="integer", precision=0, scale=0, nullable=false, unique=false)
     */
    private $timepickerMax;


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
     * Set subid
     *
     * @param integer $subid
     * @return EntityReservationMain
     */
    public function setSubid($subid)
    {
        $this->subid = $subid;

        return $this;
    }

    /**
     * Get subid
     *
     * @return integer 
     */
    public function getSubid()
    {
        return $this->subid;
    }

    /**
     * Set itemId
     *
     * @param integer $itemId
     * @return EntityReservationMain
     */
    public function setItemId($itemId)
    {
        $this->itemId = $itemId;

        return $this;
    }

    /**
     * Get itemId
     *
     * @return integer 
     */
    public function getItemId()
    {
        return $this->itemId;
    }

    /**
     * Set autoAccept
     *
     * @param boolean $autoAccept
     * @return EntityReservationMain
     */
    public function setAutoAccept($autoAccept)
    {
        $this->autoAccept = $autoAccept;

        return $this;
    }

    /**
     * Get autoAccept
     *
     * @return boolean 
     */
    public function getAutoAccept()
    {
        return $this->autoAccept;
    }

    /**
     * Set maxUsers
     *
     * @param integer $maxUsers
     * @return EntityReservationMain
     */
    public function setMaxUsers($maxUsers)
    {
        $this->maxUsers = $maxUsers;

        return $this;
    }

    /**
     * Get maxUsers
     *
     * @return integer 
     */
    public function getMaxUsers()
    {
        return $this->maxUsers;
    }

    /**
     * Set startAt
     *
     * @param \DateTime $startAt
     * @return EntityReservationMain
     */
    public function setStartAt($startAt)
    {
        $this->startAt = $startAt;

        return $this;
    }

    /**
     * Get startAt
     *
     * @return \DateTime 
     */
    public function getStartAt()
    {
        return $this->startAt;
    }

    /**
     * Set endAt
     *
     * @param \DateTime $endAt
     * @return EntityReservationMain
     */
    public function setEndAt($endAt)
    {
        $this->endAt = $endAt;

        return $this;
    }

    /**
     * Get endAt
     *
     * @return \DateTime 
     */
    public function getEndAt()
    {
        return $this->endAt;
    }

    /**
     * Set subscribeFrom
     *
     * @param \DateTime $subscribeFrom
     * @return EntityReservationMain
     */
    public function setSubscribeFrom($subscribeFrom)
    {
        $this->subscribeFrom = $subscribeFrom;

        return $this;
    }

    /**
     * Get subscribeFrom
     *
     * @return \DateTime 
     */
    public function getSubscribeFrom()
    {
        return $this->subscribeFrom;
    }

    /**
     * Set subscribeUntil
     *
     * @param \DateTime $subscribeUntil
     * @return EntityReservationMain
     */
    public function setSubscribeUntil($subscribeUntil)
    {
        $this->subscribeUntil = $subscribeUntil;

        return $this;
    }

    /**
     * Get subscribeUntil
     *
     * @return \DateTime 
     */
    public function getSubscribeUntil()
    {
        return $this->subscribeUntil;
    }

    /**
     * Set subscribers
     *
     * @param integer $subscribers
     * @return EntityReservationMain
     */
    public function setSubscribers($subscribers)
    {
        $this->subscribers = $subscribers;

        return $this;
    }

    /**
     * Get subscribers
     *
     * @return integer 
     */
    public function getSubscribers()
    {
        return $this->subscribers;
    }

    /**
     * Set notes
     *
     * @param string $notes
     * @return EntityReservationMain
     */
    public function setNotes($notes)
    {
        $this->notes = $notes;

        return $this;
    }

    /**
     * Get notes
     *
     * @return string 
     */
    public function getNotes()
    {
        return $this->notes;
    }

    /**
     * Set timepicker
     *
     * @param boolean $timepicker
     * @return EntityReservationMain
     */
    public function setTimepicker($timepicker)
    {
        $this->timepicker = $timepicker;

        return $this;
    }

    /**
     * Get timepicker
     *
     * @return boolean 
     */
    public function getTimepicker()
    {
        return $this->timepicker;
    }

    /**
     * Set timepickerMin
     *
     * @param integer $timepickerMin
     * @return EntityReservationMain
     */
    public function setTimepickerMin($timepickerMin)
    {
        $this->timepickerMin = $timepickerMin;

        return $this;
    }

    /**
     * Get timepickerMin
     *
     * @return integer 
     */
    public function getTimepickerMin()
    {
        return $this->timepickerMin;
    }

    /**
     * Set timepickerMax
     *
     * @param integer $timepickerMax
     * @return EntityReservationMain
     */
    public function setTimepickerMax($timepickerMax)
    {
        $this->timepickerMax = $timepickerMax;

        return $this;
    }

    /**
     * Get timepickerMax
     *
     * @return integer 
     */
    public function getTimepickerMax()
    {
        return $this->timepickerMax;
    }
}
