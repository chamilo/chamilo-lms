<?php



use Doctrine\ORM\Mapping as ORM;

/**
 * EntityReservationSubscription
 *
 * @Table(name="reservation_subscription")
 * @Entity
 */
class EntityReservationSubscription
{
    /**
     * @var integer
     *
     * @Column(name="dummy", type="integer", precision=0, scale=0, nullable=false, unique=false)
     * @Id
     * @GeneratedValue(strategy="IDENTITY")
     */
    private $dummy;

    /**
     * @var integer
     *
     * @Column(name="user_id", type="integer", precision=0, scale=0, nullable=false, unique=false)
     */
    private $userId;

    /**
     * @var integer
     *
     * @Column(name="reservation_id", type="integer", precision=0, scale=0, nullable=false, unique=false)
     */
    private $reservationId;

    /**
     * @var boolean
     *
     * @Column(name="accepted", type="boolean", precision=0, scale=0, nullable=false, unique=false)
     */
    private $accepted;

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
     * Get dummy
     *
     * @return integer 
     */
    public function getDummy()
    {
        return $this->dummy;
    }

    /**
     * Set userId
     *
     * @param integer $userId
     * @return EntityReservationSubscription
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
     * Set reservationId
     *
     * @param integer $reservationId
     * @return EntityReservationSubscription
     */
    public function setReservationId($reservationId)
    {
        $this->reservationId = $reservationId;

        return $this;
    }

    /**
     * Get reservationId
     *
     * @return integer 
     */
    public function getReservationId()
    {
        return $this->reservationId;
    }

    /**
     * Set accepted
     *
     * @param boolean $accepted
     * @return EntityReservationSubscription
     */
    public function setAccepted($accepted)
    {
        $this->accepted = $accepted;

        return $this;
    }

    /**
     * Get accepted
     *
     * @return boolean 
     */
    public function getAccepted()
    {
        return $this->accepted;
    }

    /**
     * Set startAt
     *
     * @param \DateTime $startAt
     * @return EntityReservationSubscription
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
     * @return EntityReservationSubscription
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
}
