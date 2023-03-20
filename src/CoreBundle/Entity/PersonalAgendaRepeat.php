<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * PersonalAgendaRepeat.
 *
 * @ORM\Table(name="personal_agenda_repeat")
 * @ORM\Entity
 */
class PersonalAgendaRepeat
{
    /**
     * @ORM\Column(name="cal_id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    protected int $calId;

    /**
     * @ORM\Column(name="cal_type", type="string", length=20, nullable=true)
     */
    protected ?string $calType = null;

    /**
     * @ORM\Column(name="cal_end", type="integer", nullable=true)
     */
    protected ?int $calEnd = null;

    /**
     * @ORM\Column(name="cal_frequency", type="integer", nullable=true)
     */
    protected ?int $calFrequency = null;

    /**
     * @ORM\Column(name="cal_days", type="string", length=7, nullable=true)
     */
    protected ?string $calDays = null;

    /**
     * Set calType.
     *
     * @return PersonalAgendaRepeat
     */
    public function setCalType(string $calType)
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
     * @return PersonalAgendaRepeat
     */
    public function setCalEnd(int $calEnd)
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
     * @return PersonalAgendaRepeat
     */
    public function setCalFrequency(int $calFrequency)
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
     * @return PersonalAgendaRepeat
     */
    public function setCalDays(string $calDays)
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

    /**
     * Get calId.
     *
     * @return int
     */
    public function getCalId()
    {
        return $this->calId;
    }
}
