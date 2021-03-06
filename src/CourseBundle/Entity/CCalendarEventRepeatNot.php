<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CourseBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * CCalendarEventRepeatNot.
 *
 * @ORM\Table(
 *     name="c_calendar_event_repeat_not"
 * )
 * @ORM\Entity
 */
class CCalendarEventRepeatNot
{
    /**
     * @ORM\Column(name="iid", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue
     */
    protected int $iid;

    /**
     * @ORM\ManyToOne(targetEntity="Chamilo\CourseBundle\Entity\CCalendarEvent", inversedBy="repeatEvents")
     * @ORM\JoinColumn(name="cal_id", referencedColumnName="iid")
     */
    protected CCalendarEvent $event;

    /**
     * @ORM\Column(name="cal_date", type="integer")
     */
    protected int $calDate;

    /**
     * Set calDate.
     *
     * @return CCalendarEventRepeatNot
     */
    public function setCalDate(int $calDate)
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
