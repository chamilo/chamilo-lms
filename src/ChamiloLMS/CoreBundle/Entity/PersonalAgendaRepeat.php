<?php

namespace ChamiloLMS\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * PersonalAgendaRepeat
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
     * @ORM\Column(name="cal_id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $calId;


}
