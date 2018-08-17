<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * EventSent.
 *
 * @ORM\Table(name="event_sent", indexes={@ORM\Index(name="event_name_index", columns={"event_type_name"})})
 * @ORM\Entity
 */
class EventSent
{
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    protected $id;

    /**
     * @var int
     *
     * @ORM\Column(name="user_from", type="integer", nullable=false)
     */
    protected $userFrom;

    /**
     * @var int
     *
     * @ORM\Column(name="user_to", type="integer", nullable=true)
     */
    protected $userTo;

    /**
     * @var string
     *
     * @ORM\Column(name="event_type_name", type="string", length=100, nullable=true)
     */
    protected $eventTypeName;

    /**
     * Set userFrom.
     *
     * @param int $userFrom
     *
     * @return EventSent
     */
    public function setUserFrom($userFrom)
    {
        $this->userFrom = $userFrom;

        return $this;
    }

    /**
     * Get userFrom.
     *
     * @return int
     */
    public function getUserFrom()
    {
        return $this->userFrom;
    }

    /**
     * Set userTo.
     *
     * @param int $userTo
     *
     * @return EventSent
     */
    public function setUserTo($userTo)
    {
        $this->userTo = $userTo;

        return $this;
    }

    /**
     * Get userTo.
     *
     * @return int
     */
    public function getUserTo()
    {
        return $this->userTo;
    }

    /**
     * Set eventTypeName.
     *
     * @param string $eventTypeName
     *
     * @return EventSent
     */
    public function setEventTypeName($eventTypeName)
    {
        $this->eventTypeName = $eventTypeName;

        return $this;
    }

    /**
     * Get eventTypeName.
     *
     * @return string
     */
    public function getEventTypeName()
    {
        return $this->eventTypeName;
    }

    /**
     * Get id.
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }
}
