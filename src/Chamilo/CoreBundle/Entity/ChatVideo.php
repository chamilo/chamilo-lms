<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Chat.
 *
 * @ORM\Table(
 *      name="chat_video", indexes={
 *          @ORM\Index(name="idx_chat_video_to_user", columns={"to_user"}),
 *          @ORM\Index(name="idx_chat_video_from_user", columns={"from_user"}),
 *          @ORM\Index(name="idx_chat_video_users", columns={"from_user", "to_user"}),
 *          @ORM\Index(name="idx_chat_video_room_name", columns={"room_name"})
 *      }
 * )
 * @ORM\Entity
 */
class ChatVideo
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
     * @ORM\Column(name="from_user", type="integer", nullable=false)
     */
    protected $fromUser;

    /**
     * @var int
     *
     * @ORM\Column(name="to_user", type="integer", nullable=false)
     */
    protected $toUser;

    /**
     * @var string
     *
     * @ORM\Column(name="room_name", type="string", nullable=false)
     */
    protected $roomName;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="datetime", type="datetime", nullable=false)
     */
    protected $datetime;

    /**
     * Set fromUser.
     *
     * @param int $fromUser
     *
     * @return ChatVideo
     */
    public function setFromUser($fromUser)
    {
        $this->fromUser = $fromUser;

        return $this;
    }

    /**
     * Get fromUser.
     *
     * @return int
     */
    public function getFromUser()
    {
        return $this->fromUser;
    }

    /**
     * Set toUser.
     *
     * @param int $toUser
     *
     * @return ChatVideo
     */
    public function setToUser($toUser)
    {
        $this->toUser = $toUser;

        return $this;
    }

    /**
     * Get toUser.
     *
     * @return int
     */
    public function getToUser()
    {
        return $this->toUser;
    }

    /**
     * Set room_name.
     *
     * @param string $roomName
     *
     * @return ChatVideo
     */
    public function setRoomName($roomName)
    {
        $this->roomName = $roomName;

        return $this;
    }

    /**
     * Get room_name.
     *
     * @return string
     */
    public function getRoomName()
    {
        return $this->roomName;
    }

    /**
     * Set datetime.
     *
     * @param \DateTime $datetime
     *
     * @return ChatVideo
     */
    public function setDatetime($datetime)
    {
        $this->datetime = $datetime;

        return $this;
    }

    /**
     * Get datetime.
     *
     * @return \DateTime
     */
    public function getDatetime()
    {
        return $this->datetime;
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
