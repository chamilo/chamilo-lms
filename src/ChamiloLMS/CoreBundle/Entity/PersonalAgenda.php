<?php

namespace ChamiloLMS\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * PersonalAgenda
 *
 * @ORM\Table(name="personal_agenda", indexes={@ORM\Index(name="idx_personal_agenda_user", columns={"user"}), @ORM\Index(name="idx_personal_agenda_parent", columns={"parent_event_id"})})
 * @ORM\Entity
 */
class PersonalAgenda
{
    /**
     * @var integer
     *
     * @ORM\Column(name="user", type="integer", nullable=true)
     */
    private $user;

    /**
     * @var string
     *
     * @ORM\Column(name="title", type="text", nullable=true)
     */
    private $title;

    /**
     * @var string
     *
     * @ORM\Column(name="text", type="text", nullable=true)
     */
    private $text;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="date", type="datetime", nullable=true)
     */
    private $date;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="enddate", type="datetime", nullable=true)
     */
    private $enddate;

    /**
     * @var string
     *
     * @ORM\Column(name="course", type="string", length=255, nullable=true)
     */
    private $course;

    /**
     * @var integer
     *
     * @ORM\Column(name="parent_event_id", type="integer", nullable=true)
     */
    private $parentEventId;

    /**
     * @var integer
     *
     * @ORM\Column(name="all_day", type="integer", nullable=false)
     */
    private $allDay;

    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;


}
