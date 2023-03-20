<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Entity;

use DateTime;
use Doctrine\ORM\Mapping as ORM;

/**
 * Chat.
 *
 * @ORM\Table(
 *     name="chat_video",
 *     options={"row_format"="DYNAMIC"},
 *     indexes={
 *         @ORM\Index(name="idx_chat_video_to_user", columns={"to_user"}),
 *         @ORM\Index(name="idx_chat_video_from_user", columns={"from_user"}),
 *         @ORM\Index(name="idx_chat_video_users", columns={"from_user", "to_user"}),
 *         @ORM\Index(name="idx_chat_video_room_name", columns={"room_name"})
 *     }
 * )
 * @ORM\Entity
 */
class ChatVideo
{
    /**
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    protected ?int $id = null;

    /**
     * @ORM\Column(name="from_user", type="integer", nullable=false)
     */
    protected int $fromUser;

    /**
     * @ORM\Column(name="to_user", type="integer", nullable=false)
     */
    protected int $toUser;

    /**
     * @ORM\Column(name="room_name", type="string", nullable=false)
     */
    protected string $roomName;

    /**
     * @ORM\Column(name="datetime", type="datetime", nullable=false)
     */
    protected DateTime $datetime;

    /**
     * Set fromUser.
     *
     * @return ChatVideo
     */
    public function setFromUser(int $fromUser)
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
     * @return ChatVideo
     */
    public function setToUser(int $toUser)
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
     * @return ChatVideo
     */
    public function setRoomName(string $roomName)
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
     * @return ChatVideo
     */
    public function setDatetime(DateTime $datetime)
    {
        $this->datetime = $datetime;

        return $this;
    }

    /**
     * Get datetime.
     *
     * @return DateTime
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
