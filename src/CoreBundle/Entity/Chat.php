<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Entity;

use DateTime;
use Doctrine\ORM\Mapping as ORM;

/**
 * Chat.
 *
 * @ORM\Table(name="chat", indexes={
 *     @ORM\Index(name="idx_chat_to_user", columns={"to_user"}),
 *     @ORM\Index(name="idx_chat_from_user", columns={"from_user"})
 * })
 * @ORM\Entity
 */
class Chat
{
    /**
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue()
     */
    protected ?int $id = null;

    /**
     * @ORM\Column(name="from_user", type="integer", nullable=true)
     */
    protected ?int $fromUser = null;

    /**
     * @ORM\Column(name="to_user", type="integer", nullable=true)
     */
    protected ?int $toUser = null;

    /**
     * @ORM\Column(name="message", type="text", nullable=false)
     */
    protected string $message;

    /**
     * @ORM\Column(name="sent", type="datetime", nullable=false)
     */
    protected DateTime $sent;

    /**
     * @ORM\Column(name="recd", type="integer", nullable=false)
     */
    protected int $recd;

    /**
     * Get id.
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set fromUser.
     *
     * @return Chat
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
     * @return Chat
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
     * Set message.
     *
     * @return Chat
     */
    public function setMessage(string $message)
    {
        $this->message = $message;

        return $this;
    }

    /**
     * Get message.
     *
     * @return string
     */
    public function getMessage()
    {
        return $this->message;
    }

    /**
     * Set sent.
     *
     * @return Chat
     */
    public function setSent(DateTime $sent)
    {
        $this->sent = $sent;

        return $this;
    }

    /**
     * Get sent.
     *
     * @return DateTime
     */
    public function getSent()
    {
        return $this->sent;
    }

    /**
     * Set recd.
     *
     * @return Chat
     */
    public function setRecd(int $recd)
    {
        $this->recd = $recd;

        return $this;
    }

    /**
     * Get recd.
     *
     * @return int
     */
    public function getRecd()
    {
        return $this->recd;
    }
}
