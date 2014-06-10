<?php

namespace ChamiloLMS\CoreBundle\Entity;

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


}
