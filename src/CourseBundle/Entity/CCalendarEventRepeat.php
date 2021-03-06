<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CourseBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * CCalendarEventRepeat.
 *
 * @ORM\Table(
 *     name="c_calendar_event_repeat",
 *     indexes={
 *     }
 * )
 * @ORM\Entity
 */
class CCalendarEventRepeat
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
     * @Assert\NotBlank()
     * @ORM\Column(name="cal_type", type="string", length=20, nullable=true)
     */
    protected ?string $calType;

    /**
     * @ORM\Column(name="cal_end", type="integer", nullable=true)
     */
    protected ?int $calEnd;

    /**
     * @ORM\Column(name="cal_frequency", type="integer", nullable=true)
     */
    protected ?int $calFrequency;

    /**
     * @ORM\Column(name="cal_days", type="string", length=7, nullable=true)
     */
    protected ?string $calDays;

    /**
     * Set calType.
     *
     * @param string $calType
     */
    public function setCalType($calType): self
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
     */
    public function setCalEnd($calEnd): self
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
     */
    public function setCalFrequency($calFrequency): self
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
     */
    public function setCalDays($calDays): self
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

    public function getEvent(): CCalendarEvent
    {
        return $this->event;
    }
}
