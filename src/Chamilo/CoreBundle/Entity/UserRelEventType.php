<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * UserRelEventType.
 *
 * @ORM\Table(name="user_rel_event_type", indexes={
 *     @ORM\Index(name="event_name_index", columns={"event_type_name"})
 * })
 * @ORM\Entity
 */
class UserRelEventType
{
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue
     */
    protected $id;

    /**
     * @var int
     *
     * @ORM\Column(name="user_id", type="integer", nullable=false)
     */
    protected $userId;

    /**
     * @var string
     *
     * @ORM\Column(name="event_type_name", type="string", length=255, nullable=false)
     */
    protected $eventTypeName;

    /**
     * Set userId.
     *
     * @param int $userId
     *
     * @return UserRelEventType
     */
    public function setUserId($userId)
    {
        $this->userId = $userId;

        return $this;
    }

    /**
     * Get userId.
     *
     * @return int
     */
    public function getUserId()
    {
        return $this->userId;
    }

    /**
     * Set eventTypeName.
     *
     * @param string $eventTypeName
     *
     * @return UserRelEventType
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
