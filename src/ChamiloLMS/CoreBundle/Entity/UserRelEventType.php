<?php

namespace ChamiloLMS\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * UserRelEventType
 *
 * @ORM\Table(name="user_rel_event_type", indexes={@ORM\Index(name="event_name_index", columns={"event_type_name"})})
 * @ORM\Entity
 */
class UserRelEventType
{
    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer", precision=0, scale=0, nullable=false, unique=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var integer
     *
     * @ORM\Column(name="user_id", type="integer", precision=0, scale=0, nullable=false, unique=false)
     */
    private $userId;

    /**
     * @var string
     *
     * @ORM\Column(name="event_type_name", type="string", length=255, precision=0, scale=0, nullable=false, unique=false)
     */
    private $eventTypeName;


    /**
     * Get id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set userId
     *
     * @param integer $userId
     * @return UserRelEventType
     */
    public function setUserId($userId)
    {
        $this->userId = $userId;

        return $this;
    }

    /**
     * Get userId
     *
     * @return integer
     */
    public function getUserId()
    {
        return $this->userId;
    }

    /**
     * Set eventTypeName
     *
     * @param string $eventTypeName
     * @return UserRelEventType
     */
    public function setEventTypeName($eventTypeName)
    {
        $this->eventTypeName = $eventTypeName;

        return $this;
    }

    /**
     * Get eventTypeName
     *
     * @return string
     */
    public function getEventTypeName()
    {
        return $this->eventTypeName;
    }
}
