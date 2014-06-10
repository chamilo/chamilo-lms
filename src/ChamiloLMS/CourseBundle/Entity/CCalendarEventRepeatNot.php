<?php

namespace ChamiloLMS\CourseBundle\Entity;

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
     * @ORM\Column(name="c_id", type="integer", nullable=false)
     */
    private $cId;

    /**
     * @var integer
     *
     * @ORM\Column(name="cal_id", type="integer", nullable=false)
     */
    private $calId;

    /**
     * @var integer
     *
     * @ORM\Column(name="cal_date", type="integer", nullable=false)
     */
    private $calDate;

    /**
     * @var integer
     *
     * @ORM\Column(name="iid", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $iid;


}
