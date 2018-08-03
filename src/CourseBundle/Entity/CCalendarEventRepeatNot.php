<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CourseBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * CCalendarEventRepeatNot.
 *
 * @ORM\Table(
 *  name="c_calendar_event_repeat_not",
 *  indexes={
 *      @ORM\Index(name="course", columns={"c_id"})
 *  }
 * )
 * @ORM\Entity
 */
class CCalendarEventRepeatNot
{
    /**
     * @var int
     *
     * @ORM\Column(name="iid", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue
     */
    protected $iid;

    /**
     * @var int
     *
     * @ORM\Column(name="c_id", type="integer")
     */
    protected $cId;

    /**
     * @var int
     *
     * @ORM\Column(name="cal_id", type="integer")
     */
    protected $calId;

    /**
     * @var int
     *
     * @ORM\Column(name="cal_date", type="integer")
     */
    protected $calDate;

    /**
     * Set cId.
     *
     * @param int $cId
     *
     * @return CCalendarEventRepeatNot
     */
    public function setCId($cId)
    {
        $this->cId = $cId;

        return $this;
    }

    /**
     * Get cId.
     *
     * @return int
     */
    public function getCId()
    {
        return $this->cId;
    }

    /**
     * Set calId.
     *
     * @param int $calId
     *
     * @return CCalendarEventRepeatNot
     */
    public function setCalId($calId)
    {
        $this->calId = $calId;

        return $this;
    }

    /**
     * Get calId.
     *
     * @return int
     */
    public function getCalId()
    {
        return $this->calId;
    }

    /**
     * Set calDate.
     *
     * @param int $calDate
     *
     * @return CCalendarEventRepeatNot
     */
    public function setCalDate($calDate)
    {
        $this->calDate = $calDate;

        return $this;
    }

    /**
     * Get calDate.
     *
     * @return int
     */
    public function getCalDate()
    {
        return $this->calDate;
    }
}
