<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * PersonalAgendaRepeatNot.
 *
 * @ORM\Table(name="personal_agenda_repeat_not")
 * @ORM\Entity
 */
class PersonalAgendaRepeatNot
{
    /**
     * @ORM\Column(name="cal_id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="NONE")
     */
    protected int $calId;

    /**
     * @ORM\Column(name="cal_date", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="NONE")
     */
    protected int $calDate;

    /**
     * Set calId.
     *
     * @return PersonalAgendaRepeatNot
     */
    public function setCalId(int $calId)
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
     * @return PersonalAgendaRepeatNot
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
