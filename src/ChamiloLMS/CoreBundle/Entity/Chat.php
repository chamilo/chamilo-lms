<?php

namespace ChamiloLMS\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Chat
 *
 * @ORM\Table(name="chat", indexes={@ORM\Index(name="idx_chat_to_user", columns={"to_user"}), @ORM\Index(name="idx_chat_from_user", columns={"from_user"})})
 * @ORM\Entity
 */
class Chat
{
    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer", precision=0, scale=0, nullable=false, unique=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @var integer
     *
     * @ORM\Column(name="from_user", type="integer", precision=0, scale=0, nullable=true, unique=false)
     */
    private $fromUser;

    /**
     * @var integer
     *
     * @ORM\Column(name="to_user", type="integer", precision=0, scale=0, nullable=true, unique=false)
     */
    private $toUser;

    /**
     * @var string
     *
     * @ORM\Column(name="message", type="text", precision=0, scale=0, nullable=false, unique=false)
     */
    private $message;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="sent", type="datetime", precision=0, scale=0, nullable=false, unique=false)
     */
    private $sent;

    /**
     * @var integer
     *
     * @ORM\Column(name="recd", type="integer", precision=0, scale=0, nullable=false, unique=false)
     */
    private $recd;


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
     * Set fromUser
     *
     * @param integer $fromUser
     * @return Chat
     */
    public function setFromUser($fromUser)
    {
        $this->fromUser = $fromUser;

        return $this;
    }

    /**
     * Get fromUser
     *
     * @return integer 
     */
    public function getFromUser()
    {
        return $this->fromUser;
    }

    /**
     * Set toUser
     *
     * @param integer $toUser
     * @return Chat
     */
    public function setToUser($toUser)
    {
        $this->toUser = $toUser;

        return $this;
    }

    /**
     * Get toUser
     *
     * @return integer 
     */
    public function getToUser()
    {
        return $this->toUser;
    }

    /**
     * Set message
     *
     * @param string $message
     * @return Chat
     */
    public function setMessage($message)
    {
        $this->message = $message;

        return $this;
    }

    /**
     * Get message
     *
     * @return string 
     */
    public function getMessage()
    {
        return $this->message;
    }

    /**
     * Set sent
     *
     * @param \DateTime $sent
     * @return Chat
     */
    public function setSent($sent)
    {
        $this->sent = $sent;

        return $this;
    }

    /**
     * Get sent
     *
     * @return \DateTime 
     */
    public function getSent()
    {
        return $this->sent;
    }

    /**
     * Set recd
     *
     * @param integer $recd
     * @return Chat
     */
    public function setRecd($recd)
    {
        $this->recd = $recd;

        return $this;
    }

    /**
     * Get recd
     *
     * @return integer 
     */
    public function getRecd()
    {
        return $this->recd;
    }
}
