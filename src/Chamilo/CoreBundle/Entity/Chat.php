<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Entity;

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
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue()
     */
    protected $id;

    /**
     * @var int
     *
     * @ORM\Column(name="from_user", type="integer", nullable=true)
     */
    protected $fromUser;

    /**
     * @var int
     *
     * @ORM\Column(name="to_user", type="integer", nullable=true)
     */
    protected $toUser;

    /**
     * @var string
     *
     * @ORM\Column(name="message", type="text", nullable=false)
     */
    protected $message;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="sent", type="datetime", nullable=false)
     */
    protected $sent;

    /**
     * @var int
     *
     * @ORM\Column(name="recd", type="integer", nullable=false)
     */
    protected $recd;

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
     * @param int $fromUser
     *
     * @return Chat
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
     * @return Chat
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
     * Set message.
     *
     * @param string $message
     *
     * @return Chat
     */
    public function setMessage($message)
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
     * @param \DateTime $sent
     *
     * @return Chat
     */
    public function setSent($sent)
    {
        $this->sent = $sent;

        return $this;
    }

    /**
     * Get sent.
     *
     * @return \DateTime
     */
    public function getSent()
    {
        return $this->sent;
    }

    /**
     * Set recd.
     *
     * @param int $recd
     *
     * @return Chat
     */
    public function setRecd($recd)
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
