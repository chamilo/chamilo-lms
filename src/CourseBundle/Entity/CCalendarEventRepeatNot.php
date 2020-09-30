<?php

/* For licensing terms, see /license.txt */

namespace Chamilo\CourseBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * CCalendarEventRepeatNot.
 *
 * @ORM\Table(
 *  name="c_calendar_event_repeat_not"
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
     * @var CCalendarEvent
     *
     * @ORM\ManyToOne(targetEntity="Chamilo\CourseBundle\Entity\CCalendarEvent", inversedBy="repeatEvents")
     * @ORM\JoinColumn(name="cal_id", referencedColumnName="iid")
     */
    protected $event;

    /**
     * @var int
     *
     * @ORM\Column(name="cal_date", type="integer")
     */
    protected $calDate;

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
