<?php
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
     * @var int
     *
     * @ORM\Column(name="cal_id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    protected $calId;

    /**
     * Set calType.
     *
     * @param string $calType
     *
     * @return PersonalAgendaRepeat
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
     * @return PersonalAgendaRepeat
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
     * @return PersonalAgendaRepeat
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
     * @return PersonalAgendaRepeat
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
