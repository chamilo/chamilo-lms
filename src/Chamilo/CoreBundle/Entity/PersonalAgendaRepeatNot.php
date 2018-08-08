<?php
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
     * @var int
     *
     * @ORM\Column(name="cal_id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="NONE")
     */
    protected $calId;

    /**
     * @var int
     *
     * @ORM\Column(name="cal_date", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="NONE")
     */
    protected $calDate;

    /**
     * Set calId.
     *
     * @param int $calId
     *
     * @return PersonalAgendaRepeatNot
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
     * @return PersonalAgendaRepeatNot
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
