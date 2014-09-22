<?php

namespace Chamilo\CourseBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * CCalendarEventRepeatNot
 *
 * @ORM\Table(name="c_calendar_event_repeat_not")
 * @ORM\Entity
 */
class CCalendarEventRepeatNot
{
    /**
     * @var integer
     *
     * @ORM\Column(name="iid", type="integer", precision=0, scale=0, nullable=false, unique=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $iid;

    /**
     * @var integer
     *
     * @ORM\Column(name="c_id", type="integer", precision=0, scale=0, nullable=false, unique=false)
     */
    private $cId;

    /**
     * @var integer
     *
     * @ORM\Column(name="cal_id", type="integer", precision=0, scale=0, nullable=false, unique=false)
     */
    private $calId;

    /**
     * @var integer
     *
     * @ORM\Column(name="cal_date", type="integer", precision=0, scale=0, nullable=false, unique=false)
     */
    private $calDate;


    /**
     * Get iid
     *
     * @return integer
     */
    public function getIid()
    {
        return $this->iid;
    }

    /**
     * Set cId
     *
     * @param integer $cId
     * @return CCalendarEventRepeatNot
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
     * @return CCalendarEventRepeatNot
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
     * @return CCalendarEventRepeatNot
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
