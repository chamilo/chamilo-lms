<?php



use Doctrine\ORM\Mapping as ORM;

/**
 * EntityPersonalAgendaRepeatNot
 *
 * @Table(name="personal_agenda_repeat_not")
 * @Entity
 */
class EntityPersonalAgendaRepeatNot
{
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
     * Set calId
     *
     * @param integer $calId
     * @return EntityPersonalAgendaRepeatNot
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
     * @return EntityPersonalAgendaRepeatNot
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
