<?php

namespace Chamilo\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * PersonalAgendaRepeatNot
 *
 * @ORM\Table(name="personal_agenda_repeat_not")
 * @ORM\Entity
 */
class PersonalAgendaRepeatNot
{
    /**
     * @var integer
     *
     * @ORM\Column(name="cal_id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="NONE")
     */
    private $calId;

    /**
     * @var integer
     *
     * @ORM\Column(name="cal_date", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="NONE")
     */
    private $calDate;



    /**
     * Set calId
     *
     * @param integer $calId
     * @return PersonalAgendaRepeatNot
     */
    public function setCalId($calId)
    {
        $this->calId = $calId;

        return $this;
    }

    /**
     * Get calId
     *
     * @return integer
     */
    public function getCalId()
    {
        return $this->calId;
    }

    /**
     * Set calDate
     *
     * @param integer $calDate
     * @return PersonalAgendaRepeatNot
     */
    public function setCalDate($calDate)
    {
        $this->calDate = $calDate;

        return $this;
    }

    /**
     * Get calDate
     *
     * @return integer
     */
    public function getCalDate()
    {
        return $this->calDate;
    }
}
