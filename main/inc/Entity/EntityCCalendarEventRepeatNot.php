<?php



use Doctrine\ORM\Mapping as ORM;

/**
 * EntityCCalendarEventRepeatNot
 *
 * @Table(name="c_calendar_event_repeat_not")
 * @Entity
 */
class EntityCCalendarEventRepeatNot
{
    /**
     * @var integer
     *
     * @Column(name="c_id", type="integer", precision=0, scale=0, nullable=false, unique=false)
     * @Id
     * @GeneratedValue(strategy="NONE")
     */
    private $cId;

    /**
     * @var integer
     *
     * @Column(name="cal_id", type="integer", precision=0, scale=0, nullable=false, unique=false)
     * @Id
     * @GeneratedValue(strategy="NONE")
     */
    private $calId;

    /**
     * @var integer
     *
     * @Column(name="cal_date", type="integer", precision=0, scale=0, nullable=false, unique=false)
     * @Id
     * @GeneratedValue(strategy="NONE")
     */
    private $calDate;


    /**
     * Set cId
     *
     * @param integer $cId
     * @return EntityCCalendarEventRepeatNot
     */
    public function setCId($cId)
    {
        $this->cId = $cId;

        return $this;
    }

    /**
     * Get cId
     *
     * @return integer 
     */
    public function getCId()
    {
        return $this->cId;
    }

    /**
     * Set calId
     *
     * @param integer $calId
     * @return EntityCCalendarEventRepeatNot
     */
    public function setCalId($calId)
    {
        $this->calId = $calId;

        return $this;
    }

    /**
     * Get calId
     *
     * @return integer 
     */
    public function getCalId()
    {
        return $this->calId;
    }

    /**
     * Set calDate
     *
     * @param integer $calDate
     * @return EntityCCalendarEventRepeatNot
     */
    public function setCalDate($calDate)
    {
        $this->calDate = $calDate;

        return $this;
    }

    /**
     * Get calDate
     *
     * @return integer 
     */
    public function getCalDate()
    {
        return $this->calDate;
    }
}
