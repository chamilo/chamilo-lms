<?php

namespace ChamiloLMS\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * EventSent
 *
 * @ORM\Table(name="event_sent", indexes={@ORM\Index(name="event_name_index", columns={"event_type_name"})})
 * @ORM\Entity
 */
class EventSent
{
    /**
     * @var integer
     *
     * @ORM\Column(name="user_from", type="integer", nullable=false)
     */
    private $userFrom;

    /**
     * @var integer
     *
     * @ORM\Column(name="user_to", type="integer", nullable=true)
     */
    private $userTo;

    /**
     * @var string
     *
     * @ORM\Column(name="event_type_name", type="string", length=100, nullable=true)
     */
    private $eventTypeName;

    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;


}
