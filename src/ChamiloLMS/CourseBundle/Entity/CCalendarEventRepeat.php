<?php

namespace ChamiloLMS\CourseBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * CCalendarEventRepeat
 *
 * @ORM\Table(name="c_calendar_event_repeat")
 * @ORM\Entity
 */
class CCalendarEventRepeat
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
     * @var string
     *
     * @ORM\Column(name="cal_type", type="string", length=20, nullable=true)
     */
    private $calType;

    /**
     * @var integer
     *
     * @ORM\Column(name="cal_end", type="integer", nullable=true)
     */
    private $calEnd;

    /**
     * @var integer
     *
     * @ORM\Column(name="cal_frequency", type="integer", nullable=true)
     */
    private $calFrequency;

    /**
     * @var string
     *
     * @ORM\Column(name="cal_days", type="string", length=7, nullable=true)
     */
    private $calDays;

    /**
     * @var integer
     *
     * @ORM\Column(name="iid", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $iid;


}
