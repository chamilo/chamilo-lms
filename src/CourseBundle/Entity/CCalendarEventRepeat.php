<?php

/* For licensing terms, see /license.txt */

namespace Chamilo\CourseBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * CCalendarEventRepeat.
 *
 * @ORM\Table(
 *  name="c_calendar_event_repeat",
 *  indexes={
 *  }
 * )
 * @ORM\Entity
 */
class CCalendarEventRepeat
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
     * @var string
     *
     * @ORM\Column(name="cal_type", type="string", length=20, nullable=true)
     */
    protected $calType;

    /**
     * @var int
     *
     * @ORM\Column(name="cal_end", type="integer", nullable=true)
     */
    protected $calEnd;

    /**
     * @var int
     *
     * @ORM\Column(name="cal_frequency", type="integer", nullable=true)
     */
    protected $calFrequency;

    /**
     * @var string
     *
     * @ORM\Column(name="cal_days", type="string", length=7, nullable=true)
     */
    protected $calDays;

    /**
     * Set calType.
     *
     * @param string $calType
     *
     * @return CCalendarEventRepeat
     */
    public function setCalType($calType)
    {
        $this->calType = $calType;

        return $this;
    }

    /**
     * Get calType.
     *
     * @return string
     */
    public function getCalType()
    {
        return $this->calType;
    }

    /**
     * Set calEnd.
     *
     * @param int $calEnd
     *
     * @return CCalendarEventRepeat
     */
    public function setCalEnd($calEnd)
    {
        $this->calEnd = $calEnd;

        return $this;
    }

    /**
     * Get calEnd.
     *
     * @return int
     */
    public function getCalEnd()
    {
        return $this->calEnd;
    }

    /**
     * Set calFrequency.
     *
     * @param int $calFrequency
     *
     * @return CCalendarEventRepeat
     */
    public function setCalFrequency($calFrequency)
    {
        $this->calFrequency = $calFrequency;

        return $this;
    }

    /**
     * Get calFrequency.
     *
     * @return int
     */
    public function getCalFrequency()
    {
        return $this->calFrequency;
    }

    /**
     * Set calDays.
     *
     * @param string $calDays
     *
     * @return CCalendarEventRepeat
     */
    public function setCalDays($calDays)
    {
        $this->calDays = $calDays;

        return $this;
    }

    /**
     * Get calDays.
     *
     * @return string
     */
    public function getCalDays()
    {
        return $this->calDays;
    }
}
